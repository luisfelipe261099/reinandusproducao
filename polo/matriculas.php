<?php
/**
 * Gerenciamento de Matrículas do Polo
 */

// Inicializa o sistema
require_once __DIR__ . '/../includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica se o usuário é do tipo polo
if (getUsuarioTipo() !== 'polo') {
    setMensagem('erro', 'Você não tem permissão para acessar esta página.');
    redirect('../index.php');
    exit;
}

// Função para formatar CPF
function formatarCpf($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    if (strlen($cpf) == 11) {
        return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
    }
    return $cpf;
}

// Instancia o banco de dados
$db = Database::getInstance();

// Obtém o ID do polo associado ao usuário
$usuario_id = getUsuarioId();
$sql = "SELECT id FROM polos WHERE responsavel_id = ?";
$resultado = $db->fetchOne($sql, [$usuario_id]);
$polo_id = $resultado['id'] ?? null;

if (!$polo_id) {
    setMensagem('erro', 'Não foi possível identificar o polo associado ao seu usuário.');
    redirect('index.php');
    exit;
}

// Define a ação padrão
$action = $_GET['action'] ?? 'listar';

// Carrega os dados conforme a ação
switch ($action) {
    case 'nova':
        // Carrega os cursos disponíveis para o polo
        $sql = "SELECT c.id, c.nome, c.nivel, c.modalidade, c.carga_horaria
                FROM cursos c
                WHERE c.status = 'ativo' AND
                      (c.polo_id = ? OR
                       EXISTS (SELECT 1 FROM turmas t WHERE t.curso_id = c.id AND t.polo_id = ?))
                ORDER BY c.nome";
        $cursos = $db->fetchAll($sql, [$polo_id, $polo_id]);

        // Carrega as turmas disponíveis para o polo
        $sql = "SELECT t.id, t.nome, t.curso_id, t.turno, t.data_inicio, t.data_fim, t.status
                FROM turmas t
                WHERE (t.status = 'planejada' OR t.status = 'em_andamento') AND t.polo_id = ?
                ORDER BY t.nome";
        $turmas = $db->fetchAll($sql, [$polo_id]);

        // Define o título da página
        $titulo_pagina = 'Nova Matrícula';
        break;

    default: // listar
        // Configuração da paginação
        $pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
        $itens_por_pagina = 15; // Reduzido para melhor visualização
        $offset = ($pagina_atual - 1) * $itens_por_pagina;

        // Filtros
        $filtro_status = isset($_GET['status']) ? $_GET['status'] : '';
        $filtro_curso = isset($_GET['curso_id']) ? (int)$_GET['curso_id'] : 0;
        $filtro_busca = isset($_GET['busca']) ? $_GET['busca'] : '';

        // Construção da cláusula WHERE com filtros
        $where_conditions = ["m.polo_id = ?"];
        $params = [$polo_id];

        if (!empty($filtro_status)) {
            $where_conditions[] = "m.status = ?";
            $params[] = $filtro_status;
        }

        if (!empty($filtro_curso)) {
            $where_conditions[] = "m.curso_id = ?";
            $params[] = $filtro_curso;
        }

        if (!empty($filtro_busca)) {
            $where_conditions[] = "(a.nome LIKE ? OR a.cpf LIKE ? OR c.nome LIKE ?)";
            $params[] = "%{$filtro_busca}%";
            $params[] = "%{$filtro_busca}%";
            $params[] = "%{$filtro_busca}%";
        }

        $where_clause = implode(" AND ", $where_conditions);

        // Consulta para contar o total de registros
        $sql_count = "SELECT COUNT(*) as total
                      FROM matriculas m
                      JOIN alunos a ON m.aluno_id = a.id
                      LEFT JOIN cursos c ON m.curso_id = c.id
                      LEFT JOIN turmas t ON m.turma_id = t.id
                      WHERE {$where_clause}";
        $result_count = $db->fetchOne($sql_count, $params);
        $total_registros = $result_count['total'];
        $total_paginas = ceil($total_registros / $itens_por_pagina);

        // Carrega as matrículas do polo com paginação
        $sql = "SELECT m.id, m.data_matricula, m.status,
                       a.nome as aluno_nome, a.cpf as aluno_cpf,
                       c.nome as curso_nome,
                       t.nome as turma_nome
                FROM matriculas m
                JOIN alunos a ON m.aluno_id = a.id
                LEFT JOIN cursos c ON m.curso_id = c.id
                LEFT JOIN turmas t ON m.turma_id = t.id
                WHERE {$where_clause}
                ORDER BY m.data_matricula DESC
                LIMIT {$offset}, {$itens_por_pagina}";
        $matriculas = $db->fetchAll($sql, $params);

        // Carrega cursos para o filtro
        $sql_cursos = "SELECT DISTINCT c.id, c.nome
                       FROM cursos c
                       JOIN matriculas m ON c.id = m.curso_id
                       WHERE m.polo_id = ?
                       ORDER BY c.nome";
        $cursos_filtro = $db->fetchAll($sql_cursos, [$polo_id]);

        // Define o título da página
        $titulo_pagina = 'Gerenciar Matrículas';
        break;
}
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
        .badge {
            border-radius: 9999px;
            padding: 0.25rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-primary { background-color: #3B82F6; color: white; }
        .badge-warning { background-color: #F59E0B; color: white; }
        .badge-danger { background-color: #EF4444; color: white; }
        .badge-success { background-color: #10B981; color: white; }

        .btn-primary {
            background-color: #3B82F6;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 500;
            transition: background-color 0.2s;
        }
        .btn-primary:hover { background-color: #2563EB; }

        .btn-secondary {
            background-color: #6B7280;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 500;
            transition: background-color 0.2s;
        }
        .btn-secondary:hover { background-color: #4B5563; }
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
                        <h1 class="text-2xl font-bold text-gray-800"><?php echo $titulo_pagina; ?></h1>
                        <?php if ($action === 'listar'): ?>
                        <a href="matriculas.php?action=nova" class="btn-primary">
                            <i class="fas fa-plus mr-2"></i> Nova Matrícula
                        </a>
                        <?php endif; ?>
                    </div>

                    <?php if (isset($_SESSION['mensagem'])): ?>
                    <div class="bg-<?php echo $_SESSION['mensagem_tipo'] === 'sucesso' ? 'green' : 'red'; ?>-100 border-l-4 border-<?php echo $_SESSION['mensagem_tipo'] === 'sucesso' ? 'green' : 'red'; ?>-500 text-<?php echo $_SESSION['mensagem_tipo'] === 'sucesso' ? 'green' : 'red'; ?>-700 p-4 mb-6">
                        <?php
                        // Verifica se a mensagem é um array e converte para string se necessário
                        if (is_array($_SESSION['mensagem'])) {
                            echo "Mensagem do sistema: " . print_r($_SESSION['mensagem'], true);
                        } else {
                            echo $_SESSION['mensagem'];
                        }
                        ?>
                    </div>
                    <?php
                    // Limpa a mensagem da sessão
                    unset($_SESSION['mensagem']);
                    unset($_SESSION['mensagem_tipo']);
                    endif;
                    ?>

                    <?php if ($action === 'listar'): ?>
                    <!-- Lista de Matrículas -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-800">Matrículas do Polo</h2>
                        </div>

                        <!-- Filtros -->
                        <div class="p-4 border-b border-gray-200 bg-gray-50">
                            <form action="matriculas.php" method="get" class="flex flex-wrap gap-3">
                                <div class="flex-1 min-w-[200px]">
                                    <label for="busca" class="block text-xs font-medium text-gray-700 mb-1">Buscar</label>
                                    <input type="text" id="busca" name="busca" value="<?php echo htmlspecialchars($filtro_busca); ?>"
                                           placeholder="Nome, CPF ou curso"
                                           class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                                </div>

                                <div class="w-[150px]">
                                    <label for="status" class="block text-xs font-medium text-gray-700 mb-1">Status</label>
                                    <select id="status" name="status" class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                                        <option value="">Todos</option>
                                        <option value="ativo" <?php echo $filtro_status === 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                                        <option value="trancado" <?php echo $filtro_status === 'trancado' ? 'selected' : ''; ?>>Trancado</option>
                                        <option value="cancelado" <?php echo $filtro_status === 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                                        <option value="concluido" <?php echo $filtro_status === 'concluido' ? 'selected' : ''; ?>>Concluído</option>
                                    </select>
                                </div>

                                <div class="w-[200px]">
                                    <label for="curso_id" class="block text-xs font-medium text-gray-700 mb-1">Curso</label>
                                    <select id="curso_id" name="curso_id" class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                                        <option value="">Todos os cursos</option>
                                        <?php foreach ($cursos_filtro as $curso): ?>
                                        <option value="<?php echo $curso['id']; ?>" <?php echo $filtro_curso == $curso['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($curso['nome']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="flex items-end">
                                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm">
                                        <i class="fas fa-search mr-1"></i> Filtrar
                                    </button>
                                </div>

                                <?php if (!empty($filtro_status) || !empty($filtro_curso) || !empty($filtro_busca)): ?>
                                <div class="flex items-end">
                                    <a href="matriculas.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md text-sm">
                                        <i class="fas fa-times mr-1"></i> Limpar
                                    </a>
                                </div>
                                <?php endif; ?>
                            </form>
                        </div>

                        <div class="p-4">
                            <?php if (empty($matriculas)): ?>
                            <div class="text-center text-gray-500 py-4">
                                <p>Nenhuma matrícula encontrada.</p>
                                <a href="matriculas.php?action=nova" class="btn-primary inline-block mt-4">Nova Matrícula</a>
                            </div>
                            <?php else: ?>
                            <!-- Contador de resultados -->
                            <div class="mb-4 text-sm text-gray-600">
                                Exibindo <?php echo count($matriculas); ?> de <?php echo $total_registros; ?> matrículas
                            </div>

                            <!-- Tabela responsiva com menos colunas -->
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aluno</th>
                                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Curso</th>
                                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Turma</th>
                                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">Data</th>
                                            <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($matriculas as $matricula): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-3 py-3">
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($matricula['aluno_nome']); ?></div>
                                                <div class="text-xs text-gray-500"><?php echo formatarCpf($matricula['aluno_cpf']); ?></div>
                                            </td>
                                            <td class="px-3 py-3">
                                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($matricula['curso_nome'] ?? 'Não definido'); ?></div>
                                            </td>
                                            <td class="px-3 py-3 hidden md:table-cell">
                                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($matricula['turma_nome'] ?? 'Não definido'); ?></div>
                                            </td>
                                            <td class="px-3 py-3 hidden sm:table-cell">
                                                <div class="text-sm text-gray-900"><?php echo date('d/m/Y', strtotime($matricula['data_matricula'])); ?></div>
                                            </td>
                                            <td class="px-3 py-3 text-center">
                                                <span class="badge <?php
                                                    echo $matricula['status'] === 'ativo' ? 'badge-success' :
                                                        ($matricula['status'] === 'inativo' ? 'badge-danger' :
                                                        ($matricula['status'] === 'trancado' ? 'badge-warning' : 'badge-secondary'));
                                                ?>">
                                                    <?php
                                                        echo $matricula['status'] === 'ativo' ? 'Ativo' :
                                                            ($matricula['status'] === 'inativo' ? 'Inativo' :
                                                            ($matricula['status'] === 'trancado' ? 'Trancado' :
                                                            ucfirst($matricula['status'])));
                                                    ?>
                                                </span>
                                            </td>
                                            <td class="px-3 py-3 text-center">
                                                <a href="#" class="text-blue-600 hover:text-blue-900 mx-1" title="Visualizar">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="#" class="text-green-600 hover:text-green-900 mx-1" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Paginação -->
                            <?php if ($total_paginas > 1): ?>
                            <div class="mt-6 flex justify-center">
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Paginação">
                                    <!-- Botão Anterior -->
                                    <?php if ($pagina_atual > 1): ?>
                                    <a href="matriculas.php?pagina=<?php echo $pagina_atual - 1; ?><?php echo !empty($filtro_status) ? '&status=' . $filtro_status : ''; ?><?php echo !empty($filtro_curso) ? '&curso_id=' . $filtro_curso : ''; ?><?php echo !empty($filtro_busca) ? '&busca=' . urlencode($filtro_busca) : ''; ?>"
                                       class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                        <span class="sr-only">Anterior</span>
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                    <?php else: ?>
                                    <span class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400 cursor-not-allowed">
                                        <span class="sr-only">Anterior</span>
                                        <i class="fas fa-chevron-left"></i>
                                    </span>
                                    <?php endif; ?>

                                    <!-- Números das Páginas -->
                                    <?php
                                    $start_page = max(1, $pagina_atual - 2);
                                    $end_page = min($total_paginas, $pagina_atual + 2);

                                    if ($start_page > 1) {
                                        echo '<a href="matriculas.php?pagina=1' .
                                             (!empty($filtro_status) ? '&status=' . $filtro_status : '') .
                                             (!empty($filtro_curso) ? '&curso_id=' . $filtro_curso : '') .
                                             (!empty($filtro_busca) ? '&busca=' . urlencode($filtro_busca) : '') .
                                             '" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">1</a>';

                                        if ($start_page > 2) {
                                            echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                                        }
                                    }

                                    for ($i = $start_page; $i <= $end_page; $i++) {
                                        if ($i == $pagina_atual) {
                                            echo '<span class="relative inline-flex items-center px-4 py-2 border border-blue-500 bg-blue-50 text-sm font-medium text-blue-600">' . $i . '</span>';
                                        } else {
                                            echo '<a href="matriculas.php?pagina=' . $i .
                                                 (!empty($filtro_status) ? '&status=' . $filtro_status : '') .
                                                 (!empty($filtro_curso) ? '&curso_id=' . $filtro_curso : '') .
                                                 (!empty($filtro_busca) ? '&busca=' . urlencode($filtro_busca) : '') .
                                                 '" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">' . $i . '</a>';
                                        }
                                    }

                                    if ($end_page < $total_paginas) {
                                        if ($end_page < $total_paginas - 1) {
                                            echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                                        }

                                        echo '<a href="matriculas.php?pagina=' . $total_paginas .
                                             (!empty($filtro_status) ? '&status=' . $filtro_status : '') .
                                             (!empty($filtro_curso) ? '&curso_id=' . $filtro_curso : '') .
                                             (!empty($filtro_busca) ? '&busca=' . urlencode($filtro_busca) : '') .
                                             '" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">' . $total_paginas . '</a>';
                                    }
                                    ?>

                                    <!-- Botão Próximo -->
                                    <?php if ($pagina_atual < $total_paginas): ?>
                                    <a href="matriculas.php?pagina=<?php echo $pagina_atual + 1; ?><?php echo !empty($filtro_status) ? '&status=' . $filtro_status : ''; ?><?php echo !empty($filtro_curso) ? '&curso_id=' . $filtro_curso : ''; ?><?php echo !empty($filtro_busca) ? '&busca=' . urlencode($filtro_busca) : ''; ?>"
                                       class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                        <span class="sr-only">Próximo</span>
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                    <?php else: ?>
                                    <span class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400 cursor-not-allowed">
                                        <span class="sr-only">Próximo</span>
                                        <i class="fas fa-chevron-right"></i>
                                    </span>
                                    <?php endif; ?>
                                </nav>
                            </div>
                            <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php elseif ($action === 'nova'): ?>
                    <!-- Formulário de Nova Matrícula -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-800">Nova Matrícula</h2>
                        </div>
                        <div class="p-6">
                            <p class="text-gray-600 mb-6">Para realizar uma nova matrícula, preencha o formulário abaixo com os dados do aluno e do curso.</p>

                            <form action="processar_matricula.php" method="post" enctype="multipart/form-data" class="space-y-6">
                                <input type="hidden" name="polo_id" value="<?php echo $polo_id; ?>">

                                <!-- Dados do Aluno -->
                                <div class="bg-gray-50 p-4 rounded-md">
                                    <h3 class="text-md font-medium text-gray-800 mb-4">Dados Pessoais do Aluno</h3>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label for="aluno_nome" class="block text-sm font-medium text-gray-700 mb-1">Nome Completo*</label>
                                            <input type="text" id="aluno_nome" name="aluno_nome" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                                        </div>
                                        <div>
                                            <label for="aluno_nome_social" class="block text-sm font-medium text-gray-700 mb-1">Nome Social</label>
                                            <input type="text" id="aluno_nome_social" name="aluno_nome_social" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                                        </div>
                                        <div>
                                            <label for="aluno_cpf" class="block text-sm font-medium text-gray-700 mb-1">CPF*</label>
                                            <input type="text" id="aluno_cpf" name="aluno_cpf" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" placeholder="000.000.000-00">
                                        </div>
                                        <div>
                                            <label for="aluno_rg" class="block text-sm font-medium text-gray-700 mb-1">RG</label>
                                            <input type="text" id="aluno_rg" name="aluno_rg" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                                        </div>
                                        <div>
                                            <label for="aluno_data_nascimento" class="block text-sm font-medium text-gray-700 mb-1">Data de Nascimento</label>
                                            <input type="date" id="aluno_data_nascimento" name="aluno_data_nascimento" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                                        </div>
                                        <div>
                                            <label for="aluno_sexo" class="block text-sm font-medium text-gray-700 mb-1">Sexo</label>
                                            <select id="aluno_sexo" name="aluno_sexo" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                                                <option value="">Selecione</option>
                                                <option value="masculino">Masculino</option>
                                                <option value="feminino">Feminino</option>
                                                <option value="outro">Outro</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Dados de Contato -->
                                <div class="bg-gray-50 p-4 rounded-md">
                                    <h3 class="text-md font-medium text-gray-800 mb-4">Dados de Contato</h3>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label for="aluno_email" class="block text-sm font-medium text-gray-700 mb-1">E-mail*</label>
                                            <input type="email" id="aluno_email" name="aluno_email" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                                        </div>
                                        <div>
                                            <label for="aluno_telefone" class="block text-sm font-medium text-gray-700 mb-1">Telefone</label>
                                            <input type="text" id="aluno_telefone" name="aluno_telefone" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" placeholder="(00) 0000-0000">
                                        </div>
                                        <div>
                                            <label for="aluno_celular" class="block text-sm font-medium text-gray-700 mb-1">Celular</label>
                                            <input type="text" id="aluno_celular" name="aluno_celular" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" placeholder="(00) 00000-0000">
                                        </div>
                                    </div>
                                </div>

                                <!-- Endereço -->
                                <div class="bg-gray-50 p-4 rounded-md">
                                    <h3 class="text-md font-medium text-gray-800 mb-4">Endereço</h3>

                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div class="md:col-span-2">
                                            <label for="aluno_endereco" class="block text-sm font-medium text-gray-700 mb-1">Logradouro</label>
                                            <input type="text" id="aluno_endereco" name="aluno_endereco" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                                        </div>
                                        <div>
                                            <label for="aluno_numero" class="block text-sm font-medium text-gray-700 mb-1">Número</label>
                                            <input type="text" id="aluno_numero" name="aluno_numero" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                                        </div>
                                        <div>
                                            <label for="aluno_bairro" class="block text-sm font-medium text-gray-700 mb-1">Bairro</label>
                                            <input type="text" id="aluno_bairro" name="aluno_bairro" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                                        </div>
                                        <div>
                                            <label for="aluno_cidade" class="block text-sm font-medium text-gray-700 mb-1">Cidade</label>
                                            <input type="text" id="aluno_cidade" name="aluno_cidade" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                                        </div>
                                        <div>
                                            <label for="aluno_estado" class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                                            <input type="text" id="aluno_estado" name="aluno_estado" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" maxlength="2">
                                        </div>
                                        <div>
                                            <label for="aluno_cep" class="block text-sm font-medium text-gray-700 mb-1">CEP</label>
                                            <input type="text" id="aluno_cep" name="aluno_cep" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" placeholder="00000-000">
                                        </div>
                                    </div>
                                </div>

                                <!-- Dados da Matrícula -->
                                <div class="bg-gray-50 p-4 rounded-md">
                                    <h3 class="text-md font-medium text-gray-800 mb-4">Dados da Matrícula</h3>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label for="curso_id" class="block text-sm font-medium text-gray-700 mb-1">Curso*</label>
                                            <select id="curso_id" name="curso_id" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                                                <option value="">Selecione um curso</option>
                                                <?php foreach ($cursos as $curso): ?>
                                                <option value="<?php echo $curso['id']; ?>"><?php echo htmlspecialchars($curso['nome']); ?>
                                                    <?php if (!empty($curso['nivel'])): ?>
                                                        (<?php echo ucfirst($curso['nivel']); ?>)
                                                    <?php endif; ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div>
                                            <label for="turma_id" class="block text-sm font-medium text-gray-700 mb-1">Turma*</label>
                                            <select id="turma_id" name="turma_id" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                                                <option value="">Selecione primeiro um curso</option>
                                                <?php foreach ($turmas as $turma): ?>
                                                <option value="<?php echo $turma['id']; ?>" data-curso-id="<?php echo $turma['curso_id']; ?>" style="display: none;">
                                                    <?php echo htmlspecialchars($turma['nome']); ?>
                                                    <?php if (!empty($turma['turno'])): ?>
                                                        - <?php echo ucfirst($turma['turno']); ?>
                                                    <?php endif; ?>
                                                    <?php if (!empty($turma['data_inicio'])): ?>
                                                        (Início: <?php echo date('d/m/Y', strtotime($turma['data_inicio'])); ?>)
                                                    <?php endif; ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div>
                                            <label for="data_matricula" class="block text-sm font-medium text-gray-700 mb-1">Data da Matrícula*</label>
                                            <input type="date" id="data_matricula" name="data_matricula" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" value="<?php echo date('Y-m-d'); ?>">
                                        </div>
                                        <div>
                                            <label for="data_ingresso" class="block text-sm font-medium text-gray-700 mb-1">Data de Ingresso</label>
                                            <input type="date" id="data_ingresso" name="data_ingresso" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" value="<?php echo date('Y-m-d'); ?>">
                                        </div>
                                        <div>
                                            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status*</label>
                                            <select id="status" name="status" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                                                <option value="ativo">Ativo</option>
                                                <option value="trancado">Trancado</option>
                                                <option value="cancelado">Cancelado</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label for="previsao_conclusao" class="block text-sm font-medium text-gray-700 mb-1">Previsão de Conclusão</label>
                                            <input type="date" id="previsao_conclusao" name="previsao_conclusao" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                                        </div>
                                    </div>

                                    <div class="mt-4 p-3 bg-blue-50 rounded-md">
                                        <p class="text-sm text-blue-700">
                                            <i class="fas fa-info-circle mr-2"></i>
                                            Selecione primeiro o curso para ver as turmas disponíveis.
                                        </p>
                                    </div>
                                </div>

                                <!-- Documentos do Aluno -->
                                <div class="bg-gray-50 p-4 rounded-md">
                                    <h3 class="text-md font-medium text-gray-800 mb-4">Documentos do Aluno</h3>

                                    <div class="mb-4 p-3 bg-yellow-50 rounded-md">
                                        <p class="text-sm text-yellow-700">
                                            <i class="fas fa-info-circle mr-2"></i>
                                            O envio de documentos é opcional neste momento. Você poderá adicionar ou atualizar os documentos posteriormente.
                                        </p>
                                    </div>

                                    <?php
                                    // Verifica se a tabela tipos_documentos_pessoais existe
                                    $tabela_existe = false;
                                    try {
                                        $result = $db->query("SHOW TABLES LIKE 'tipos_documentos_pessoais'");
                                        $tabela_existe = !empty($result);

                                        if ($tabela_existe) {
                                            // Busca tipos de documentos obrigatórios
                                            $sql = "SELECT id, nome, descricao, obrigatorio FROM tipos_documentos_pessoais WHERE status = 'ativo' ORDER BY obrigatorio DESC, nome";
                                            $tipos_documentos = $db->fetchAll($sql);
                                        }
                                    } catch (Exception $e) {
                                        error_log('Erro ao verificar tabela tipos_documentos_pessoais: ' . $e->getMessage());
                                    }
                                    ?>

                                    <?php if ($tabela_existe && !empty($tipos_documentos)): ?>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <?php foreach ($tipos_documentos as $tipo_documento): ?>
                                        <div>
                                            <label for="documento_<?php echo $tipo_documento['id']; ?>" class="block text-sm font-medium text-gray-700 mb-1">
                                                <?php echo htmlspecialchars($tipo_documento['nome']); ?>
                                                <?php if ($tipo_documento['obrigatorio']): ?>
                                                <span class="text-red-500">*</span>
                                                <?php endif; ?>
                                                <?php if (!empty($tipo_documento['descricao'])): ?>
                                                <span class="text-xs text-gray-500 ml-1">(<?php echo htmlspecialchars($tipo_documento['descricao']); ?>)</span>
                                                <?php endif; ?>
                                            </label>
                                            <input type="file" id="documento_<?php echo $tipo_documento['id']; ?>" name="documentos[<?php echo $tipo_documento['id']; ?>]" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" accept=".pdf,.jpg,.jpeg,.png">
                                            <input type="hidden" name="documento_obrigatorio[<?php echo $tipo_documento['id']; ?>]" value="<?php echo $tipo_documento['obrigatorio']; ?>">
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php else: ?>
                                    <div class="text-center text-gray-500 py-4">
                                        <p>Não foi possível carregar os tipos de documentos. Você poderá adicionar documentos após a matrícula.</p>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <div class="flex justify-between pt-4">
                                    <a href="matriculas.php" class="btn-secondary">Cancelar</a>
                                    <button type="submit" class="btn-primary">Salvar Matrícula</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </main>

            <!-- Footer -->
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>

    <!-- Botão flutuante para voltar ao topo -->
    <button id="btn-voltar-topo" class="fixed bottom-6 right-6 bg-blue-600 hover:bg-blue-700 text-white rounded-full p-3 shadow-lg z-50 hidden">
        <i class="fas fa-arrow-up"></i>
    </button>

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

        // Close user menu when clicking outside
        document.addEventListener('click', function(event) {
            const menu = document.getElementById('user-menu');
            const button = document.getElementById('user-menu-button');

            if (!menu.contains(event.target) && !button.contains(event.target)) {
                menu.classList.add('hidden');
            }
        });

        // Botão voltar ao topo
        const btnVoltarTopo = document.getElementById('btn-voltar-topo');

        // Mostrar/ocultar botão com base na posição da rolagem
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                btnVoltarTopo.classList.remove('hidden');
            } else {
                btnVoltarTopo.classList.add('hidden');
            }
        });

        // Rolar para o topo quando o botão é clicado
        btnVoltarTopo.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        <?php if ($action === 'nova'): ?>
        // Filtrar turmas com base no curso selecionado
        document.getElementById('curso_id').addEventListener('change', function() {
            const cursoId = this.value;
            const turmaSelect = document.getElementById('turma_id');
            const turmaOptions = turmaSelect.querySelectorAll('option');

            // Resetar o select de turmas
            turmaSelect.value = '';

            // Atualizar o texto da primeira opção
            if (cursoId === '') {
                turmaOptions[0].textContent = 'Selecione primeiro um curso';
            } else {
                turmaOptions[0].textContent = 'Selecione uma turma';

                // Verificar se há turmas disponíveis para este curso
                let turmasDisponiveis = false;

                turmaOptions.forEach((option, index) => {
                    if (index === 0) return; // Pular a primeira opção

                    const optionCursoId = option.getAttribute('data-curso-id');
                    if (optionCursoId === cursoId) {
                        turmasDisponiveis = true;
                        option.style.display = '';
                    } else {
                        option.style.display = 'none';
                    }
                });

                if (!turmasDisponiveis) {
                    // Se não houver turmas para este curso, mostrar mensagem
                    turmaOptions[0].textContent = 'Nenhuma turma disponível para este curso';
                }
            }
        });

        // Formatar CPF
        document.getElementById('aluno_cpf').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) {
                value = value.substring(0, 11);
            }

            if (value.length > 9) {
                value = value.replace(/^(\d{3})(\d{3})(\d{3})(\d{2}).*/, '$1.$2.$3-$4');
            } else if (value.length > 6) {
                value = value.replace(/^(\d{3})(\d{3})(\d{0,3}).*/, '$1.$2.$3');
            } else if (value.length > 3) {
                value = value.replace(/^(\d{3})(\d{0,3}).*/, '$1.$2');
            }

            e.target.value = value;
        });

        // Formatar telefone
        document.getElementById('aluno_telefone').addEventListener('input', function(e) {
            formatarTelefone(e);
        });

        // Formatar celular
        document.getElementById('aluno_celular').addEventListener('input', function(e) {
            formatarTelefone(e);
        });

        // Formatar CEP
        document.getElementById('aluno_cep').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 8) {
                value = value.substring(0, 8);
            }

            if (value.length > 5) {
                value = value.replace(/^(\d{5})(\d{0,3}).*/, '$1-$2');
            }

            e.target.value = value;
        });

        // Função para formatar telefone
        function formatarTelefone(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) {
                value = value.substring(0, 11);
            }

            if (value.length > 10) {
                value = value.replace(/^(\d{2})(\d{5})(\d{4}).*/, '($1) $2-$3');
            } else if (value.length > 6) {
                value = value.replace(/^(\d{2})(\d{4})(\d{0,4}).*/, '($1) $2-$3');
            } else if (value.length > 2) {
                value = value.replace(/^(\d{2})(\d{0,5}).*/, '($1) $2');
            }

            e.target.value = value;
        }
        <?php endif; ?>
    </script>
</body>
</html>
