<?php
/**
 * Matricular Alunos em Curso do AVA
 * Permite matricular alunos em um curso específico do Ambiente Virtual de Aprendizagem
 */

// Inicializa o sistema
require_once '../includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica se o usuário é do tipo polo
if (getUsuarioTipo() !== 'polo') {
    setMensagem('erro', 'Você não tem permissão para acessar esta página.');
    redirect('../polo/index.php');
    exit;
}

// Instancia o banco de dados
$db = Database::getInstance();

// Obtém o ID do polo
$polo_id = getUsuarioPoloId();

// Verifica se o polo tem acesso ao AVA
if (!$polo_id) {
    setMensagem('erro', 'Não foi possível identificar o polo associado ao seu usuário. Entre em contato com a secretaria para mais informações.');
    redirect('../polo/index.php');
    exit;
}

// Verifica se o polo existe
$sql = "SELECT * FROM polos WHERE id = ?";
$polo = $db->fetchOne($sql, [$polo_id]);

if (!$polo) {
    setMensagem('erro', 'Polo não encontrado no sistema. Entre em contato com a secretaria para mais informações.');
    redirect('../polo/index.php');
    exit;
}

// Verifica se o polo tem acesso ao AVA
$sql = "SELECT * FROM ava_polos_acesso WHERE polo_id = ?";
$acesso = $db->fetchOne($sql, [$polo_id]);

if (!$acesso || $acesso['liberado'] != 1) {
    setMensagem('erro', 'Seu polo não possui acesso liberado ao AVA. Entre em contato com a secretaria para mais informações.');
    redirect('../polo/index.php');
    exit;
}

// Verifica se o ID do curso foi informado
if (!isset($_GET['curso_id']) || empty($_GET['curso_id'])) {
    setMensagem('erro', 'Curso não informado.');
    redirect('cursos.php');
    exit;
}

$curso_id = (int)$_GET['curso_id'];

// Busca o curso
$sql = "SELECT * FROM ava_cursos WHERE id = ? AND polo_id = ?";
$curso = $db->fetchOne($sql, [$curso_id, $polo_id]);

if (!$curso) {
    setMensagem('erro', 'Curso não encontrado ou você não tem permissão para acessá-lo.');
    redirect('cursos.php');
    exit;
}

// Parâmetros de paginação
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$itens_por_pagina = 5;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

// Parâmetros de filtro
$filtro_busca = isset($_GET['busca']) ? $_GET['busca'] : '';

// Constrói a consulta SQL com filtros
$sql_where = "WHERE a.polo_id = ?";
$params = [$polo_id];

if (!empty($filtro_busca)) {
    $sql_where .= " AND (a.nome LIKE ? OR a.email LIKE ? OR a.cpf LIKE ?)";
    $params[] = "%$filtro_busca%";
    $params[] = "%$filtro_busca%";
    $params[] = "%$filtro_busca%";
}

// Conta o total de alunos
$sql = "SELECT COUNT(*) as total FROM alunos a $sql_where";
$resultado = $db->fetchOne($sql, $params);
$total_alunos = $resultado['total'] ?? 0;

// Calcula o total de páginas
$total_paginas = ceil($total_alunos / $itens_por_pagina);

// Busca os alunos com paginação
$sql = "SELECT a.*,
        (SELECT COUNT(*) FROM ava_matriculas am WHERE am.aluno_id = a.id AND am.curso_id = ?) as ja_matriculado
        FROM alunos a
        $sql_where
        ORDER BY a.nome
        LIMIT $itens_por_pagina OFFSET $offset";
array_unshift($params, $curso_id); // Adiciona o curso_id como primeiro parâmetro
$alunos = $db->fetchAll($sql, $params);

// Busca os alunos já matriculados no curso
$sql = "SELECT am.*, a.nome as aluno_nome, a.email as aluno_email, a.cpf as aluno_cpf
        FROM ava_matriculas am
        JOIN alunos a ON am.aluno_id = a.id
        WHERE am.curso_id = ?
        ORDER BY a.nome";
$matriculados = $db->fetchAll($sql, [$curso_id]);

// Processa o formulário de matrícula
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['matricular']) && !empty($_POST['alunos'])) {
        $alunos_ids = $_POST['alunos'];
        $erros = [];
        $sucessos = 0;

        foreach ($alunos_ids as $aluno_id) {
            // Verifica se o aluno já está matriculado
            $sql = "SELECT id FROM ava_matriculas WHERE aluno_id = ? AND curso_id = ?";
            $matricula_existente = $db->fetchOne($sql, [$aluno_id, $curso_id]);

            if ($matricula_existente) {
                $erros[] = "Aluno ID $aluno_id já está matriculado neste curso.";
                continue;
            }

            // Insere a matrícula
            try {
                $dados = [
                    'aluno_id' => $aluno_id,
                    'curso_id' => $curso_id,
                    'status' => 'ativo',
                    'data_matricula' => date('Y-m-d H:i:s'),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                $db->insert('ava_matriculas', $dados);
                $sucessos++;
            } catch (Exception $e) {
                $erros[] = "Erro ao matricular aluno ID $aluno_id: " . $e->getMessage();
            }
        }

        if ($sucessos > 0) {
            setMensagem('sucesso', "$sucessos aluno(s) matriculado(s) com sucesso!");
        }

        if (!empty($erros)) {
            setMensagem('erro', implode('<br>', $erros));
        }

        redirect("matriculas.php?curso_id=$curso_id");
        exit;
    } elseif (isset($_POST['cancelar_matricula']) && !empty($_POST['matricula_id'])) {
        $matricula_id = (int)$_POST['matricula_id'];

        // Verifica se a matrícula existe e pertence ao curso
        $sql = "SELECT am.* FROM ava_matriculas am
                JOIN ava_cursos ac ON am.curso_id = ac.id
                WHERE am.id = ? AND ac.polo_id = ?";
        $matricula = $db->fetchOne($sql, [$matricula_id, $polo_id]);

        if (!$matricula) {
            setMensagem('erro', 'Matrícula não encontrada ou você não tem permissão para cancelá-la.');
            redirect("matriculas.php?curso_id=$curso_id");
            exit;
        }

        // Cancela a matrícula
        try {
            $sql = "DELETE FROM ava_matriculas WHERE id = ?";
            $db->query($sql, [$matricula_id]);

            setMensagem('sucesso', 'Matrícula cancelada com sucesso!');
        } catch (Exception $e) {
            setMensagem('erro', 'Erro ao cancelar matrícula: ' . $e->getMessage());
        }

        redirect("matriculas.php?curso_id=$curso_id");
        exit;
    }
}

// Define o título da página
$titulo_pagina = 'Matricular Alunos';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faciência ERP - <?php echo $titulo_pagina; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .aluno-item {
            display: flex;
            align-items: center;
            padding: 0.75rem;
            border-bottom: 1px solid #E5E7EB;
        }
        .aluno-item:last-child {
            border-bottom: none;
        }
        .aluno-avatar {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 50%;
            background-color: #F3F4F6;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            color: #6A5ACD;
            font-weight: 600;
        }
        .aluno-info {
            flex: 1;
        }
        .aluno-name {
            font-weight: 500;
            color: #111827;
            margin-bottom: 0.25rem;
        }
        .aluno-email {
            font-size: 0.875rem;
            color: #6B7280;
        }
        .aluno-cpf {
            font-size: 0.75rem;
            color: #6B7280;
            margin-top: 0.25rem;
        }

        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 2rem;
        }
        .pagination a, .pagination span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            margin: 0 0.25rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .pagination a {
            background-color: white;
            color: #4B5563;
            border: 1px solid #E5E7EB;
            text-decoration: none;
        }
        .pagination a:hover {
            background-color: #F3F4F6;
        }
        .pagination span {
            background-color: #6A5ACD;
            color: white;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 9999px;
        }
        .status-ativo { background-color: #D1FAE5; color: #059669; }
        .status-inativo { background-color: #FEE2E2; color: #DC2626; }
        .status-pendente { background-color: #FEF3C7; color: #D97706; }
        .status-concluido { background-color: #E0E7FF; color: #4F46E5; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <?php include 'includes/header.php'; ?>

            <!-- Main -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
                <div class="container mx-auto">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-800"><?php echo $titulo_pagina; ?></h1>
                            <p class="text-gray-600">Matricular alunos no curso: <strong><?php echo htmlspecialchars($curso['titulo']); ?></strong></p>
                        </div>
                        <div class="flex space-x-2">
                            <a href="curso_visualizar.php?id=<?php echo $curso_id; ?>" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                <i class="fas fa-arrow-left mr-2"></i> Voltar
                            </a>
                        </div>
                    </div>

                    <?php if (isset($_SESSION['mensagem']) && isset($_SESSION['mensagem_tipo'])): ?>
                    <div class="bg-<?php echo $_SESSION['mensagem_tipo'] === 'sucesso' ? 'green' : 'red'; ?>-100 border-l-4 border-<?php echo $_SESSION['mensagem_tipo'] === 'sucesso' ? 'green' : 'red'; ?>-500 text-<?php echo $_SESSION['mensagem_tipo'] === 'sucesso' ? 'green' : 'red'; ?>-700 p-4 mb-6">
                        <?php echo is_array($_SESSION['mensagem']) ? implode(', ', $_SESSION['mensagem']) : $_SESSION['mensagem']; ?>
                    </div>
                    <?php
                    // Limpa a mensagem da sessão
                    unset($_SESSION['mensagem']);
                    unset($_SESSION['mensagem_tipo']);
                    endif;
                    ?>

                    <!-- Alunos Matriculados -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-800">Alunos Matriculados (<?php echo count($matriculados); ?>)</h2>
                        </div>
                        <div class="p-6">
                            <?php if (empty($matriculados)): ?>
                            <div class="text-center text-gray-500 py-4">
                                <p>Nenhum aluno matriculado neste curso.</p>
                            </div>
                            <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($matriculados as $matricula): ?>
                                <div class="aluno-item bg-gray-50 rounded-lg">
                                    <div class="aluno-avatar">
                                        <?php echo strtoupper(substr($matricula['aluno_nome'], 0, 1)); ?>
                                    </div>
                                    <div class="aluno-info">
                                        <div class="flex items-center">
                                            <div class="aluno-name"><?php echo htmlspecialchars($matricula['aluno_nome']); ?></div>
                                            <span class="status-badge status-<?php echo $matricula['status']; ?> ml-2">
                                                <?php
                                                if ($matricula['status'] === 'ativo') echo 'Ativo';
                                                elseif ($matricula['status'] === 'inativo') echo 'Inativo';
                                                elseif ($matricula['status'] === 'pendente') echo 'Pendente';
                                                elseif ($matricula['status'] === 'concluido') echo 'Concluído';
                                                ?>
                                            </span>
                                        </div>
                                        <div class="aluno-email"><?php echo htmlspecialchars($matricula['aluno_email']); ?></div>
                                        <div class="aluno-cpf"><?php echo htmlspecialchars($matricula['aluno_cpf']); ?></div>
                                        <?php if (!empty($matricula['data_matricula'])): ?>
                                        <div class="text-xs text-gray-500 mt-1">
                                            Matriculado em: <?php echo date('d/m/Y H:i', strtotime($matricula['data_matricula'])); ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="ml-4">
                                        <form method="post" onsubmit="return confirm('Tem certeza que deseja cancelar esta matrícula?');">
                                            <input type="hidden" name="matricula_id" value="<?php echo $matricula['id']; ?>">
                                            <button type="submit" name="cancelar_matricula" class="inline-flex items-center px-3 py-1.5 border border-transparent rounded-md text-xs font-medium text-white bg-red-600 hover:bg-red-700">
                                                <i class="fas fa-times mr-1"></i> Cancelar
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Filtro de Alunos -->
                    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">Buscar Alunos</h2>
                        <form action="matriculas.php" method="get" class="flex items-end space-x-4">
                            <input type="hidden" name="curso_id" value="<?php echo $curso_id; ?>">
                            <div class="flex-1">
                                <label for="busca" class="block text-sm font-medium text-gray-700 mb-1">Nome, E-mail ou CPF</label>
                                <input type="text" id="busca" name="busca" value="<?php echo htmlspecialchars($filtro_busca); ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                                    <i class="fas fa-search mr-2"></i> Buscar
                                </button>
                            </div>
                            <div>
                                <a href="matriculas.php?curso_id=<?php echo $curso_id; ?>" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                    <i class="fas fa-times mr-2"></i> Limpar
                                </a>
                            </div>
                        </form>
                    <!-- Lista de Alunos para Matricular -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
                        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                            <h2 class="text-lg font-semibold text-gray-800">Alunos Disponíveis (<?php echo $total_alunos; ?>)</h2>
                            <?php if (!empty($alunos)): ?>
                            <button type="submit" form="form-matricular" name="matricular" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700">
                                <i class="fas fa-user-plus mr-2"></i> Matricular Selecionados
                            </button>
                            <?php endif; ?>
                        </div>
                        <div class="p-6">
                            <?php if (empty($alunos)): ?>
                            <div class="text-center text-gray-500 py-4">
                                <p>Nenhum aluno encontrado.</p>
                            </div>
                            <?php else: ?>
                            <form id="form-matricular" method="post" action="matriculas.php?curso_id=<?php echo $curso_id; ?>">
                                <div class="space-y-4">
                                    <?php foreach ($alunos as $aluno): ?>
                                    <div class="aluno-item bg-gray-50 rounded-lg flex items-center">
                                        <div class="mr-3">
                                            <input type="checkbox" id="aluno_<?php echo $aluno['id']; ?>" name="alunos[]" value="<?php echo $aluno['id']; ?>" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded" <?php echo $aluno['ja_matriculado'] ? 'disabled checked' : ''; ?>>
                                        </div>
                                        <div class="aluno-avatar">
                                            <?php echo strtoupper(substr($aluno['nome'], 0, 1)); ?>
                                        </div>
                                        <div class="aluno-info">
                                            <div class="aluno-name"><?php echo htmlspecialchars($aluno['nome']); ?></div>
                                            <div class="aluno-email"><?php echo htmlspecialchars($aluno['email']); ?></div>
                                            <div class="aluno-cpf"><?php echo htmlspecialchars($aluno['cpf']); ?></div>
                                        </div>
                                        <?php if ($aluno['ja_matriculado']): ?>
                                        <div class="ml-auto">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <i class="fas fa-check mr-1"></i> Já Matriculado
                                            </span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </form>

                            <!-- Paginação -->
                            <?php if ($total_paginas > 1): ?>
                            <div class="pagination mt-6">
                                <?php if ($pagina_atual > 1): ?>
                                <a href="matriculas.php?curso_id=<?php echo $curso_id; ?>&pagina=<?php echo $pagina_atual - 1; ?>&busca=<?php echo urlencode($filtro_busca); ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                                <?php endif; ?>

                                <?php
                                $inicio = max(1, $pagina_atual - 2);
                                $fim = min($total_paginas, $pagina_atual + 2);

                                if ($inicio > 1) {
                                    echo '<a href="matriculas.php?curso_id=' . $curso_id . '&pagina=1&busca=' . urlencode($filtro_busca) . '">1</a>';
                                    if ($inicio > 2) {
                                        echo '<span>...</span>';
                                    }
                                }

                                for ($i = $inicio; $i <= $fim; $i++) {
                                    if ($i == $pagina_atual) {
                                        echo '<span>' . $i . '</span>';
                                    } else {
                                        echo '<a href="matriculas.php?curso_id=' . $curso_id . '&pagina=' . $i . '&busca=' . urlencode($filtro_busca) . '">' . $i . '</a>';
                                    }
                                }

                                if ($fim < $total_paginas) {
                                    if ($fim < $total_paginas - 1) {
                                        echo '<span>...</span>';
                                    }
                                    echo '<a href="matriculas.php?curso_id=' . $curso_id . '&pagina=' . $total_paginas . '&busca=' . urlencode($filtro_busca) . '">' . $total_paginas . '</a>';
                                }
                                ?>

                                <?php if ($pagina_atual < $total_paginas): ?>
                                <a href="matriculas.php?curso_id=<?php echo $curso_id; ?>&pagina=<?php echo $pagina_atual + 1; ?>&busca=<?php echo urlencode($filtro_busca); ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>

            <!-- Footer -->
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>

    <script>
        // Toggle sidebar
        document.getElementById('toggle-sidebar').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('sidebar-collapsed');
            sidebar.classList.toggle('sidebar-expanded');

            const labels = document.querySelectorAll('.sidebar-label');
            labels.forEach(label => {
                label.classList.toggle('hidden');
            });
        });

        // Toggle user menu
        document.getElementById('user-menu-button').addEventListener('click', function() {
            const menu = document.getElementById('user-menu');
            menu.classList.toggle('hidden');
        });
    </script>
</body>
</html>