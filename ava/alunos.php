<?php
/**
 * Listagem de Alunos do AVA
 * Exibe os alunos matriculados nos cursos do polo no Ambiente Virtual de Aprendizagem
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

// Parâmetros de paginação
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$itens_por_pagina = 5;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

// Parâmetros de filtro
$filtro_curso = isset($_GET['curso']) ? $_GET['curso'] : '';
$filtro_status = isset($_GET['status']) ? $_GET['status'] : '';
$filtro_busca = isset($_GET['busca']) ? $_GET['busca'] : '';

// Constrói a consulta SQL com filtros
$sql_where = "WHERE ac.polo_id = ?";
$params = [$polo_id];

if (!empty($filtro_curso)) {
    $sql_where .= " AND am.curso_id = ?";
    $params[] = $filtro_curso;
}

if (!empty($filtro_status)) {
    $sql_where .= " AND am.status = ?";
    $params[] = $filtro_status;
}

if (!empty($filtro_busca)) {
    $sql_where .= " AND (a.nome LIKE ? OR a.email LIKE ? OR a.cpf LIKE ?)";
    $params[] = "%$filtro_busca%";
    $params[] = "%$filtro_busca%";
    $params[] = "%$filtro_busca%";
}

// Conta o total de alunos
$sql = "SELECT COUNT(*) as total
        FROM ava_matriculas am
        JOIN alunos a ON am.aluno_id = a.id
        JOIN ava_cursos ac ON am.curso_id = ac.id
        $sql_where";
$resultado = $db->fetchOne($sql, $params);
$total_alunos = $resultado['total'] ?? 0;

// Calcula o total de páginas
$total_paginas = ceil($total_alunos / $itens_por_pagina);

// Verifica se a tabela ava_progresso existe
$sql_check = "SHOW TABLES LIKE 'ava_progresso'";
$tabela_progresso_existe = $db->fetchOne($sql_check);

// Verifica se a tabela ava_modulos existe
$sql_check = "SHOW TABLES LIKE 'ava_modulos'";
$tabela_modulos_existe = $db->fetchOne($sql_check);

// Busca os alunos com paginação
if ($tabela_progresso_existe && $tabela_modulos_existe) {
    // Se ambas as tabelas existem, usa a consulta original
    $sql = "SELECT am.*, a.nome as aluno_nome, a.email as aluno_email, a.cpf as aluno_cpf,
            ac.titulo as curso_titulo, ac.categoria as curso_categoria,
            (SELECT COUNT(*) FROM ava_progresso ap WHERE ap.matricula_id = am.id AND ap.concluido = 1) as modulos_concluidos,
            (SELECT COUNT(*) FROM ava_modulos amod WHERE amod.curso_id = am.curso_id) as total_modulos
            FROM ava_matriculas am
            JOIN alunos a ON am.aluno_id = a.id
            JOIN ava_cursos ac ON am.curso_id = ac.id
            $sql_where
            ORDER BY am.created_at DESC
            LIMIT $itens_por_pagina OFFSET $offset";
} else {
    // Se alguma das tabelas não existe, usa uma consulta simplificada
    $sql = "SELECT am.*, a.nome as aluno_nome, a.email as aluno_email, a.cpf as aluno_cpf,
            ac.titulo as curso_titulo, ac.categoria as curso_categoria,
            0 as modulos_concluidos, 0 as total_modulos
            FROM ava_matriculas am
            JOIN alunos a ON am.aluno_id = a.id
            JOIN ava_cursos ac ON am.curso_id = ac.id
            $sql_where
            ORDER BY am.created_at DESC
            LIMIT $itens_por_pagina OFFSET $offset";
}
$alunos = $db->fetchAll($sql, $params);

// Busca os cursos disponíveis para o filtro
$sql = "SELECT id, titulo FROM ava_cursos WHERE polo_id = ? ORDER BY titulo";
$cursos = $db->fetchAll($sql, [$polo_id]);

// Define o título da página
$titulo_pagina = 'Alunos do AVA';
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

        .progress-bar {
            width: 100%;
            height: 8px;
            background-color: #E5E7EB;
            border-radius: 4px;
            overflow: hidden;
        }
        .progress-bar-fill {
            height: 100%;
            background-color: #6A5ACD;
            border-radius: 4px;
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
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <?php include 'includes/header.php'; ?>

            <!-- Main -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
                <div class="container mx-auto">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-800"><?php echo $titulo_pagina; ?></h1>
                            <p class="text-gray-600">Gerencie os alunos matriculados nos cursos do seu polo no AVA</p>
                        </div>
                        <a href="../polo/alunos.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                            <i class="fas fa-users mr-2"></i> Todos os Alunos
                        </a>
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

                    <!-- Filtros -->
                    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">Filtros</h2>
                        <form action="alunos.php" method="get" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label for="curso" class="block text-sm font-medium text-gray-700 mb-1">Curso</label>
                                <select id="curso" name="curso" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Todos</option>
                                    <?php foreach ($cursos as $curso): ?>
                                    <option value="<?php echo $curso['id']; ?>" <?php echo $filtro_curso == $curso['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($curso['titulo']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select id="status" name="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Todos</option>
                                    <option value="ativo" <?php echo $filtro_status === 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                                    <option value="inativo" <?php echo $filtro_status === 'inativo' ? 'selected' : ''; ?>>Inativo</option>
                                    <option value="pendente" <?php echo $filtro_status === 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                                    <option value="concluido" <?php echo $filtro_status === 'concluido' ? 'selected' : ''; ?>>Concluído</option>
                                </select>
                            </div>
                            <div>
                                <label for="busca" class="block text-sm font-medium text-gray-700 mb-1">Busca</label>
                                <input type="text" id="busca" name="busca" value="<?php echo htmlspecialchars($filtro_busca); ?>" placeholder="Nome, e-mail ou CPF" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div class="flex items-end">
                                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                                    <i class="fas fa-search mr-2"></i> Filtrar
                                </button>
                                <a href="alunos.php" class="ml-2 inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                    <i class="fas fa-times mr-2"></i> Limpar
                                </a>
                            </div>
                        </form>
                    </div>

                    <!-- Listagem de Alunos -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-800">Alunos Matriculados (<?php echo $total_alunos; ?>)</h2>
                        </div>
                        <div class="overflow-x-auto">
                            <?php if (empty($alunos)): ?>
                            <div class="text-center text-gray-500 py-8">
                                <p>Nenhum aluno encontrado.</p>
                            </div>
                            <?php else: ?>
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aluno</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Curso</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Progresso</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data de Matrícula</th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($alunos as $aluno): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600">
                                                    <?php echo strtoupper(substr($aluno['aluno_nome'], 0, 1)); ?>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($aluno['aluno_nome']); ?></div>
                                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($aluno['aluno_email']); ?></div>
                                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($aluno['aluno_cpf']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?php echo htmlspecialchars($aluno['curso_titulo']); ?></div>
                                            <div class="text-xs text-gray-500"><?php echo htmlspecialchars($aluno['curso_categoria']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php
                                            $progresso = 0;
                                            if ($aluno['total_modulos'] > 0) {
                                                $progresso = ($aluno['modulos_concluidos'] / $aluno['total_modulos']) * 100;
                                            }
                                            ?>
                                            <div class="text-sm text-gray-900 mb-1"><?php echo round($progresso); ?>% concluído</div>
                                            <div class="progress-bar">
                                                <div class="progress-bar-fill" style="width: <?php echo $progresso; ?>%"></div>
                                            </div>
                                            <div class="text-xs text-gray-500 mt-1"><?php echo $aluno['modulos_concluidos']; ?> de <?php echo $aluno['total_modulos']; ?> módulos</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="status-badge status-<?php echo $aluno['status']; ?>">
                                                <?php
                                                if ($aluno['status'] === 'ativo') echo 'Ativo';
                                                elseif ($aluno['status'] === 'inativo') echo 'Inativo';
                                                elseif ($aluno['status'] === 'pendente') echo 'Pendente';
                                                elseif ($aluno['status'] === 'concluido') echo 'Concluído';
                                                ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date('d/m/Y', strtotime($aluno['created_at'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a href="aluno_visualizar.php?id=<?php echo $aluno['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">Detalhes</a>
                                            <a href="aluno_progresso.php?id=<?php echo $aluno['id']; ?>" class="text-green-600 hover:text-green-900">Progresso</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>

                            <!-- Paginação -->
                            <?php if ($total_paginas > 1): ?>
                            <div class="pagination py-4">
                                <?php if ($pagina_atual > 1): ?>
                                <a href="alunos.php?pagina=<?php echo $pagina_atual - 1; ?>&curso=<?php echo urlencode($filtro_curso); ?>&status=<?php echo urlencode($filtro_status); ?>&busca=<?php echo urlencode($filtro_busca); ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                                <?php endif; ?>

                                <?php
                                $inicio = max(1, $pagina_atual - 2);
                                $fim = min($total_paginas, $pagina_atual + 2);

                                if ($inicio > 1) {
                                    echo '<a href="alunos.php?pagina=1&curso=' . urlencode($filtro_curso) . '&status=' . urlencode($filtro_status) . '&busca=' . urlencode($filtro_busca) . '">1</a>';
                                    if ($inicio > 2) {
                                        echo '<span>...</span>';
                                    }
                                }

                                for ($i = $inicio; $i <= $fim; $i++) {
                                    if ($i == $pagina_atual) {
                                        echo '<span>' . $i . '</span>';
                                    } else {
                                        echo '<a href="alunos.php?pagina=' . $i . '&curso=' . urlencode($filtro_curso) . '&status=' . urlencode($filtro_status) . '&busca=' . urlencode($filtro_busca) . '">' . $i . '</a>';
                                    }
                                }

                                if ($fim < $total_paginas) {
                                    if ($fim < $total_paginas - 1) {
                                        echo '<span>...</span>';
                                    }
                                    echo '<a href="alunos.php?pagina=' . $total_paginas . '&curso=' . urlencode($filtro_curso) . '&status=' . urlencode($filtro_status) . '&busca=' . urlencode($filtro_busca) . '">' . $total_paginas . '</a>';
                                }
                                ?>

                                <?php if ($pagina_atual < $total_paginas): ?>
                                <a href="alunos.php?pagina=<?php echo $pagina_atual + 1; ?>&curso=<?php echo urlencode($filtro_curso); ?>&status=<?php echo urlencode($filtro_status); ?>&busca=<?php echo urlencode($filtro_busca); ?>">
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
