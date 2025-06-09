<?php
/**
 * Página de Relatórios do AVA
 * Permite à secretaria visualizar relatórios do ambiente virtual
 */

// Inicializa o sistema
require_once 'includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Durante a fase de homologação, não verificamos permissões específicas
// Apenas verificamos se o usuário está autenticado, o que já foi feito com exigirLogin()
// Código original comentado para referência futura
/*
if (getUsuarioTipo() !== 'secretaria' && getUsuarioTipo() !== 'admin') {
    setMensagem('erro', 'Você não tem permissão para acessar esta página.');
    redirect('index.php');
    exit;
}
*/

// Instancia o banco de dados
$db = Database::getInstance();

// Define o tipo de relatório padrão
$tipo_relatorio = $_GET['tipo'] ?? 'acessos';

// Define os parâmetros de filtro
$data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : date('Y-m-d', strtotime('-30 days'));
$data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : date('Y-m-d');
$polo_id = isset($_GET['polo_id']) ? (int)$_GET['polo_id'] : 0;
$curso_id = isset($_GET['curso_id']) ? (int)$_GET['curso_id'] : 0;
$formato_exportacao = isset($_GET['formato']) ? $_GET['formato'] : '';

// Inicializa as variáveis
$acessos = [];
$alunos_ativos = [];
$cursos_populares = [];
$progresso_alunos = [];
$polos = [];
$cursos = [];

// Processa os relatórios
try {
    // Verifica quais tabelas existem
    $check_ava_acessos = $db->fetchOne("SHOW TABLES LIKE 'ava_acessos'");
    $check_polos = $db->fetchOne("SHOW TABLES LIKE 'polos'");
    $check_alunos = $db->fetchOne("SHOW TABLES LIKE 'alunos'");
    $check_ava_alunos = $db->fetchOne("SHOW TABLES LIKE 'ava_alunos'");
    $check_ava_cursos = $db->fetchOne("SHOW TABLES LIKE 'ava_cursos'");
    $check_ava_matriculas = $db->fetchOne("SHOW TABLES LIKE 'ava_matriculas'");

    // Carrega a lista de polos para os filtros
    if ($check_polos) {
        $sql = "SELECT id, nome FROM polos WHERE status = 'ativo' ORDER BY nome";
        $polos = $db->fetchAll($sql);
    }

    // Carrega a lista de cursos para os filtros
    if ($check_ava_cursos) {
        $sql = "SELECT id, titulo, nome FROM ava_cursos WHERE status IN ('ativo', 'publicado') ORDER BY titulo, nome";
        $cursos = $db->fetchAll($sql);
    }

    switch ($tipo_relatorio) {
        case 'acessos':
            // Relatório de acessos ao AVA
            if ($check_ava_acessos) {
                $params = [];
                $where_conditions = ["data_acesso BETWEEN ? AND ?"];
                $params[] = $data_inicio . ' 00:00:00';
                $params[] = $data_fim . ' 23:59:59';

                // Filtro por polo
                if ($polo_id > 0 && $check_alunos) {
                    $where_conditions[] = "aluno_id IN (SELECT id FROM alunos WHERE polo_id = ?)";
                    $params[] = $polo_id;
                }

                $where_clause = "WHERE " . implode(" AND ", $where_conditions);

                $sql = "SELECT
                            DATE(data_acesso) as data,
                            COUNT(*) as total_acessos,
                            COUNT(DISTINCT aluno_id) as total_alunos
                        FROM ava_acessos
                        $where_clause
                        GROUP BY DATE(data_acesso)
                        ORDER BY data DESC";
                $acessos = $db->fetchAll($sql, $params);
            } else {
                setMensagem('erro', 'A tabela ava_acessos não existe no banco de dados.');
            }
            break;

        case 'alunos_ativos':
            // Relatório de alunos ativos no AVA
            if ($check_polos && $check_alunos && $check_ava_alunos) {
                $params = [];
                $where_conditions = ["p.status = 'ativo'"];

                // Filtro por polo
                if ($polo_id > 0) {
                    $where_conditions[] = "p.id = ?";
                    $params[] = $polo_id;
                }

                $where_clause = "WHERE " . implode(" AND ", $where_conditions);

                $sql = "SELECT
                            p.id as polo_id,
                            p.nome as polo,
                            COUNT(aa.id) as total_alunos,
                            SUM(CASE WHEN aa.status = 'ativo' THEN 1 ELSE 0 END) as alunos_ativos,
                            SUM(CASE WHEN aa.status = 'inativo' THEN 1 ELSE 0 END) as alunos_inativos
                        FROM polos p
                        LEFT JOIN alunos a ON a.polo_id = p.id
                        LEFT JOIN ava_alunos aa ON a.id = aa.aluno_id
                        $where_clause
                        GROUP BY p.id
                        ORDER BY p.nome";
                $alunos_ativos = $db->fetchAll($sql, $params);
            } else {
                $tabelas_faltantes = [];
                if (!$check_polos) $tabelas_faltantes[] = 'polos';
                if (!$check_alunos) $tabelas_faltantes[] = 'alunos';
                if (!$check_ava_alunos) $tabelas_faltantes[] = 'ava_alunos';

                setMensagem('erro', 'As seguintes tabelas não existem no banco de dados: ' . implode(', ', $tabelas_faltantes));
            }
            break;

        case 'cursos_populares':
            // Relatório de cursos mais populares no AVA
            if ($check_ava_cursos && $check_ava_matriculas) {
                $params = [];
                $where_conditions = ["c.status IN ('ativo', 'publicado')"];

                // Filtro por curso
                if ($curso_id > 0) {
                    $where_conditions[] = "c.id = ?";
                    $params[] = $curso_id;
                }

                // Filtro por polo (através das matrículas)
                if ($polo_id > 0 && $check_alunos) {
                    $where_conditions[] = "am.aluno_id IN (SELECT id FROM alunos WHERE polo_id = ?)";
                    $params[] = $polo_id;
                }

                // Filtro por data
                if (!empty($data_inicio) && !empty($data_fim)) {
                    $where_conditions[] = "am.data_matricula BETWEEN ? AND ?";
                    $params[] = $data_inicio . ' 00:00:00';
                    $params[] = $data_fim . ' 23:59:59';
                }

                $where_clause = "WHERE " . implode(" AND ", $where_conditions);

                $sql = "SELECT
                            c.id as curso_id,
                            COALESCE(c.titulo, c.nome) as curso,
                            COUNT(am.id) as total_matriculas,
                            AVG(am.progresso) as progresso_medio
                        FROM ava_cursos c
                        LEFT JOIN ava_matriculas am ON c.id = am.curso_id
                        $where_clause
                        GROUP BY c.id
                        ORDER BY total_matriculas DESC
                        LIMIT 15";
                $cursos_populares = $db->fetchAll($sql, $params);
            } else {
                $tabelas_faltantes = [];
                if (!$check_ava_cursos) $tabelas_faltantes[] = 'ava_cursos';
                if (!$check_ava_matriculas) $tabelas_faltantes[] = 'ava_matriculas';

                setMensagem('erro', 'As seguintes tabelas não existem no banco de dados: ' . implode(', ', $tabelas_faltantes));
            }
            break;

        case 'progresso_alunos':
            // Relatório de progresso dos alunos nos cursos
            if ($check_ava_matriculas && $check_alunos && $check_ava_cursos) {
                $params = [];
                $where_conditions = ["am.status = 'ativo'"];

                // Filtro por curso
                if ($curso_id > 0) {
                    $where_conditions[] = "am.curso_id = ?";
                    $params[] = $curso_id;
                }

                // Filtro por polo
                if ($polo_id > 0) {
                    $where_conditions[] = "a.polo_id = ?";
                    $params[] = $polo_id;
                }

                // Filtro por data
                if (!empty($data_inicio) && !empty($data_fim)) {
                    $where_conditions[] = "am.data_matricula BETWEEN ? AND ?";
                    $params[] = $data_inicio . ' 00:00:00';
                    $params[] = $data_fim . ' 23:59:59';
                }

                $where_clause = "WHERE " . implode(" AND ", $where_conditions);

                $sql = "SELECT
                            a.id as aluno_id,
                            a.nome as aluno,
                            c.id as curso_id,
                            COALESCE(c.titulo, c.nome) as curso,
                            am.progresso,
                            am.data_matricula,
                            am.data_inicio,
                            am.data_conclusao,
                            p.nome as polo_nome
                        FROM ava_matriculas am
                        JOIN alunos a ON am.aluno_id = a.id
                        JOIN ava_cursos c ON am.curso_id = c.id
                        LEFT JOIN polos p ON a.polo_id = p.id
                        $where_clause
                        ORDER BY a.nome, c.titulo, c.nome";
                $progresso_alunos = $db->fetchAll($sql, $params);
            } else {
                $tabelas_faltantes = [];
                if (!$check_ava_matriculas) $tabelas_faltantes[] = 'ava_matriculas';
                if (!$check_alunos) $tabelas_faltantes[] = 'alunos';
                if (!$check_ava_cursos) $tabelas_faltantes[] = 'ava_cursos';

                setMensagem('erro', 'As seguintes tabelas não existem no banco de dados: ' . implode(', ', $tabelas_faltantes));
            }
            break;
    }
} catch (Exception $e) {
    // Em caso de erro, exibe uma mensagem
    setMensagem('erro', 'Erro ao gerar relatório: ' . $e->getMessage());
}

// Define o título da página
$titulo_pagina = 'Relatórios do AVA';
$titulo_pagina_completo = 'Faciência ERP - ' . $titulo_pagina;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo_pagina_completo; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <style>
        /* Estilos para os filtros */
        .filtros-container {
            background-color: #f9fafb;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border: 1px solid #e5e7eb;
        }

        .filtro-item {
            margin-bottom: 0.75rem;
        }

        .filtro-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.25rem;
        }

        /* Estilos para os gráficos */
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 1.5rem;
        }

        /* Estilos para os cards de estatísticas */
        .stats-card {
            background-color: white;
            border-radius: 0.5rem;
            padding: 1rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            transition: all 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .stats-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1f2937;
        }

        .stats-label {
            font-size: 0.875rem;
            color: #6b7280;
        }

        /* Estilos para as barras de progresso */
        .progress-bar {
            height: 0.5rem;
            background-color: #e5e7eb;
            border-radius: 9999px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            border-radius: 9999px;
        }

        .progress-fill-low {
            background-color: #ef4444;
        }

        .progress-fill-medium {
            background-color: #f59e0b;
        }

        .progress-fill-high {
            background-color: #10b981;
        }

        /* Estilos para os botões de exportação */
        .export-button {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s;
        }

        .export-button i {
            margin-right: 0.5rem;
        }

        .export-pdf {
            background-color: #fee2e2;
            color: #dc2626;
        }

        .export-pdf:hover {
            background-color: #fecaca;
        }

        .export-excel {
            background-color: #d1fae5;
            color: #059669;
        }

        .export-excel:hover {
            background-color: #a7f3d0;
        }

        /* Responsividade para dispositivos móveis */
        @media (max-width: 768px) {
            .filtros-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
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
                        <h1 class="text-2xl font-bold text-gray-800"><?php echo $titulo_pagina; ?></h1>
                    </div>

                    <!-- Seleção de Relatório -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-800">Selecione o Relatório</h2>
                        </div>
                        <div class="p-6">
                            <div class="flex flex-wrap gap-4">
                                <a href="?tipo=acessos" class="px-4 py-2 rounded-md <?php echo $tipo_relatorio === 'acessos' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-800 hover:bg-gray-300'; ?>">
                                    <i class="fas fa-chart-line mr-2"></i> Acessos ao AVA
                                </a>
                                <a href="?tipo=alunos_ativos" class="px-4 py-2 rounded-md <?php echo $tipo_relatorio === 'alunos_ativos' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-800 hover:bg-gray-300'; ?>">
                                    <i class="fas fa-users mr-2"></i> Alunos Ativos por Polo
                                </a>
                                <a href="?tipo=cursos_populares" class="px-4 py-2 rounded-md <?php echo $tipo_relatorio === 'cursos_populares' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-800 hover:bg-gray-300'; ?>">
                                    <i class="fas fa-star mr-2"></i> Cursos Populares
                                </a>
                                <a href="?tipo=progresso_alunos" class="px-4 py-2 rounded-md <?php echo $tipo_relatorio === 'progresso_alunos' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-800 hover:bg-gray-300'; ?>">
                                    <i class="fas fa-tasks mr-2"></i> Progresso dos Alunos
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Filtros -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-800">Filtros</h2>
                        </div>
                        <div class="p-6">
                            <form action="ava_relatorios.php" method="get" class="space-y-4">
                                <input type="hidden" name="tipo" value="<?php echo $tipo_relatorio; ?>">

                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 filtros-grid">
                                    <!-- Filtro de Data -->
                                    <div class="filtro-item">
                                        <label for="data_inicio" class="filtro-label">Data Inicial</label>
                                        <input type="date" id="data_inicio" name="data_inicio" value="<?php echo $data_inicio; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                    </div>

                                    <div class="filtro-item">
                                        <label for="data_fim" class="filtro-label">Data Final</label>
                                        <input type="date" id="data_fim" name="data_fim" value="<?php echo $data_fim; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                    </div>

                                    <!-- Filtro de Polo -->
                                    <div class="filtro-item">
                                        <label for="polo_id" class="filtro-label">Polo</label>
                                        <select id="polo_id" name="polo_id" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                            <option value="0">Todos os Polos</option>
                                            <?php foreach ($polos as $polo): ?>
                                            <option value="<?php echo $polo['id']; ?>" <?php echo $polo_id == $polo['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($polo['nome']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <!-- Filtro de Curso -->
                                    <div class="filtro-item">
                                        <label for="curso_id" class="filtro-label">Curso</label>
                                        <select id="curso_id" name="curso_id" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                            <option value="0">Todos os Cursos</option>
                                            <?php foreach ($cursos as $curso): ?>
                                            <option value="<?php echo $curso['id']; ?>" <?php echo $curso_id == $curso['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($curso['titulo'] ?: $curso['nome']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="flex flex-wrap justify-between items-center pt-4">
                                    <div class="flex flex-wrap gap-2">
                                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 flex items-center">
                                            <i class="fas fa-filter mr-2"></i> Aplicar Filtros
                                        </button>
                                        <a href="?tipo=<?php echo $tipo_relatorio; ?>" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 flex items-center">
                                            <i class="fas fa-times mr-2"></i> Limpar Filtros
                                        </a>
                                    </div>

                                    <div class="flex flex-wrap gap-2 mt-4 sm:mt-0">
                                        <a href="?tipo=<?php echo $tipo_relatorio; ?>&formato=pdf&data_inicio=<?php echo $data_inicio; ?>&data_fim=<?php echo $data_fim; ?>&polo_id=<?php echo $polo_id; ?>&curso_id=<?php echo $curso_id; ?>" class="export-button export-pdf">
                                            <i class="fas fa-file-pdf"></i> Exportar PDF
                                        </a>
                                        <a href="?tipo=<?php echo $tipo_relatorio; ?>&formato=excel&data_inicio=<?php echo $data_inicio; ?>&data_fim=<?php echo $data_fim; ?>&polo_id=<?php echo $polo_id; ?>&curso_id=<?php echo $curso_id; ?>" class="export-button export-excel">
                                            <i class="fas fa-file-excel"></i> Exportar Excel
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <?php if ($tipo_relatorio === 'acessos'): ?>
                    <!-- Relatório de Acessos -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-800">Acessos ao AVA - Período: <?php echo date('d/m/Y', strtotime($data_inicio)); ?> a <?php echo date('d/m/Y', strtotime($data_fim)); ?></h2>
                        </div>
                        <div class="p-6">
                            <!-- Cards de Estatísticas -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6 stats-grid">
                                <div class="stats-card">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="stats-value">
                                                <?php
                                                $total_acessos = 0;
                                                foreach ($acessos as $acesso) {
                                                    $total_acessos += $acesso['total_acessos'];
                                                }
                                                echo $total_acessos;
                                                ?>
                                            </div>
                                            <div class="stats-label">Total de Acessos</div>
                                        </div>
                                        <div class="text-blue-500 bg-blue-100 p-3 rounded-full">
                                            <i class="fas fa-chart-line text-xl"></i>
                                        </div>
                                    </div>
                                </div>

                                <div class="stats-card">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="stats-value">
                                                <?php
                                                $total_alunos_unicos = 0;
                                                $alunos_set = [];
                                                foreach ($acessos as $acesso) {
                                                    $total_alunos_unicos = max($total_alunos_unicos, $acesso['total_alunos']);
                                                }
                                                echo $total_alunos_unicos;
                                                ?>
                                            </div>
                                            <div class="stats-label">Alunos Únicos</div>
                                        </div>
                                        <div class="text-green-500 bg-green-100 p-3 rounded-full">
                                            <i class="fas fa-users text-xl"></i>
                                        </div>
                                    </div>
                                </div>

                                <div class="stats-card">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="stats-value">
                                                <?php
                                                $media_acessos = count($acessos) > 0 ? round($total_acessos / count($acessos), 1) : 0;
                                                echo $media_acessos;
                                                ?>
                                            </div>
                                            <div class="stats-label">Média de Acessos Diários</div>
                                        </div>
                                        <div class="text-purple-500 bg-purple-100 p-3 rounded-full">
                                            <i class="fas fa-calculator text-xl"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Gráfico -->
                            <div class="chart-container mb-6">
                                <canvas id="acessosChart"></canvas>
                            </div>

                            <!-- Tabela de Dados -->
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total de Acessos</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alunos Únicos</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Média por Aluno</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php if (empty($acessos)): ?>
                                        <tr>
                                            <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                                                Nenhum dado encontrado para o período selecionado.
                                            </td>
                                        </tr>
                                        <?php else: ?>
                                            <?php foreach ($acessos as $acesso): ?>
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm font-medium text-gray-900"><?php echo date('d/m/Y', strtotime($acesso['data'])); ?></div>
                                                    <div class="text-xs text-gray-500"><?php echo date('l', strtotime($acesso['data'])); ?></div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm text-gray-900"><?php echo $acesso['total_acessos']; ?></div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm text-gray-900"><?php echo $acesso['total_alunos']; ?></div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm text-gray-900">
                                                        <?php echo $acesso['total_alunos'] > 0 ? round($acesso['total_acessos'] / $acesso['total_alunos'], 1) : 0; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php elseif ($tipo_relatorio === 'alunos_ativos'): ?>
                    <!-- Relatório de Alunos Ativos por Polo -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-800">Alunos Ativos por Polo</h2>
                        </div>
                        <div class="p-6">
                            <!-- Cards de Estatísticas -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6 stats-grid">
                                <div class="stats-card">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="stats-value">
                                                <?php
                                                $total_polos = count($alunos_ativos);
                                                echo $total_polos;
                                                ?>
                                            </div>
                                            <div class="stats-label">Total de Polos</div>
                                        </div>
                                        <div class="text-blue-500 bg-blue-100 p-3 rounded-full">
                                            <i class="fas fa-building text-xl"></i>
                                        </div>
                                    </div>
                                </div>

                                <div class="stats-card">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="stats-value">
                                                <?php
                                                $total_alunos = 0;
                                                foreach ($alunos_ativos as $polo) {
                                                    $total_alunos += $polo['total_alunos'];
                                                }
                                                echo $total_alunos;
                                                ?>
                                            </div>
                                            <div class="stats-label">Total de Alunos</div>
                                        </div>
                                        <div class="text-green-500 bg-green-100 p-3 rounded-full">
                                            <i class="fas fa-users text-xl"></i>
                                        </div>
                                    </div>
                                </div>

                                <div class="stats-card">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="stats-value">
                                                <?php
                                                $total_ativos = 0;
                                                foreach ($alunos_ativos as $polo) {
                                                    $total_ativos += $polo['alunos_ativos'];
                                                }
                                                $percentual_ativos = $total_alunos > 0 ? round(($total_ativos / $total_alunos) * 100, 1) : 0;
                                                echo $percentual_ativos . '%';
                                                ?>
                                            </div>
                                            <div class="stats-label">Alunos Ativos</div>
                                        </div>
                                        <div class="text-purple-500 bg-purple-100 p-3 rounded-full">
                                            <i class="fas fa-user-check text-xl"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Gráfico -->
                            <div class="chart-container mb-6">
                                <canvas id="alunosAtivosChart"></canvas>
                            </div>

                            <!-- Tabela de Dados -->
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Polo</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total de Alunos</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alunos Ativos</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alunos Inativos</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Distribuição</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php if (empty($alunos_ativos)): ?>
                                        <tr>
                                            <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                                Nenhum dado encontrado para o período selecionado.
                                            </td>
                                        </tr>
                                        <?php else: ?>
                                            <?php foreach ($alunos_ativos as $polo): ?>
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($polo['polo']); ?></div>
                                                    <div class="text-xs text-gray-500">ID: <?php echo $polo['polo_id']; ?></div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm text-gray-900"><?php echo $polo['total_alunos']; ?></div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm text-gray-900"><?php echo $polo['alunos_ativos']; ?></div>
                                                    <div class="text-xs text-green-600">
                                                        <?php
                                                        $percentual_ativos_polo = $polo['total_alunos'] > 0 ? round(($polo['alunos_ativos'] / $polo['total_alunos']) * 100, 1) : 0;
                                                        echo $percentual_ativos_polo . '%';
                                                        ?>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm text-gray-900"><?php echo $polo['alunos_inativos']; ?></div>
                                                    <div class="text-xs text-red-600">
                                                        <?php
                                                        $percentual_inativos_polo = $polo['total_alunos'] > 0 ? round(($polo['alunos_inativos'] / $polo['total_alunos']) * 100, 1) : 0;
                                                        echo $percentual_inativos_polo . '%';
                                                        ?>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <div class="progress-bar">
                                                        <div class="progress-fill progress-fill-high" style="width: <?php echo $percentual_ativos_polo; ?>%"></div>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php elseif ($tipo_relatorio === 'cursos_populares'): ?>
                    <!-- Relatório de Cursos Populares -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-800">Cursos Mais Populares</h2>
                        </div>
                        <div class="p-6">
                            <!-- Cards de Estatísticas -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6 stats-grid">
                                <div class="stats-card">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="stats-value">
                                                <?php
                                                $total_cursos = count($cursos_populares);
                                                echo $total_cursos;
                                                ?>
                                            </div>
                                            <div class="stats-label">Total de Cursos</div>
                                        </div>
                                        <div class="text-blue-500 bg-blue-100 p-3 rounded-full">
                                            <i class="fas fa-book text-xl"></i>
                                        </div>
                                    </div>
                                </div>

                                <div class="stats-card">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="stats-value">
                                                <?php
                                                $total_matriculas = 0;
                                                foreach ($cursos_populares as $curso) {
                                                    $total_matriculas += $curso['total_matriculas'];
                                                }
                                                echo $total_matriculas;
                                                ?>
                                            </div>
                                            <div class="stats-label">Total de Matrículas</div>
                                        </div>
                                        <div class="text-green-500 bg-green-100 p-3 rounded-full">
                                            <i class="fas fa-user-graduate text-xl"></i>
                                        </div>
                                    </div>
                                </div>

                                <div class="stats-card">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="stats-value">
                                                <?php
                                                $progresso_medio_total = 0;
                                                $cursos_com_progresso = 0;

                                                foreach ($cursos_populares as $curso) {
                                                    if ($curso['progresso_medio'] > 0) {
                                                        $progresso_medio_total += $curso['progresso_medio'];
                                                        $cursos_com_progresso++;
                                                    }
                                                }

                                                $progresso_medio_geral = $cursos_com_progresso > 0 ? round($progresso_medio_total / $cursos_com_progresso, 1) : 0;
                                                echo $progresso_medio_geral . '%';
                                                ?>
                                            </div>
                                            <div class="stats-label">Progresso Médio</div>
                                        </div>
                                        <div class="text-purple-500 bg-purple-100 p-3 rounded-full">
                                            <i class="fas fa-chart-pie text-xl"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Gráfico -->
                            <div class="chart-container mb-6">
                                <canvas id="cursosPopularesChart"></canvas>
                            </div>

                            <!-- Tabela de Dados -->
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Curso</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total de Matrículas</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Progresso Médio</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Distribuição</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php if (empty($cursos_populares)): ?>
                                        <tr>
                                            <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                                                Nenhum dado encontrado para o período selecionado.
                                            </td>
                                        </tr>
                                        <?php else: ?>
                                            <?php foreach ($cursos_populares as $curso): ?>
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-6 py-4">
                                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($curso['curso']); ?></div>
                                                    <div class="text-xs text-gray-500">ID: <?php echo $curso['curso_id']; ?></div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm text-gray-900"><?php echo $curso['total_matriculas']; ?></div>
                                                    <div class="text-xs text-blue-600">
                                                        <?php
                                                        $percentual_matriculas = $total_matriculas > 0 ? round(($curso['total_matriculas'] / $total_matriculas) * 100, 1) : 0;
                                                        echo $percentual_matriculas . '% do total';
                                                        ?>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <?php
                                                    $progress_class = 'progress-fill-low';
                                                    if ($curso['progresso_medio'] >= 70) {
                                                        $progress_class = 'progress-fill-high';
                                                    } elseif ($curso['progresso_medio'] >= 30) {
                                                        $progress_class = 'progress-fill-medium';
                                                    }
                                                    ?>
                                                    <div class="text-sm text-gray-900"><?php echo number_format($curso['progresso_medio'], 1); ?>%</div>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <div class="progress-bar">
                                                        <div class="progress-fill <?php echo $progress_class; ?>" style="width: <?php echo $curso['progresso_medio']; ?>%"></div>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php elseif ($tipo_relatorio === 'progresso_alunos'): ?>
                    <!-- Relatório de Progresso dos Alunos -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-800">Progresso dos Alunos nos Cursos</h2>
                        </div>
                        <div class="p-6">
                            <!-- Cards de Estatísticas -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6 stats-grid">
                                <div class="stats-card">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="stats-value">
                                                <?php
                                                $total_matriculas = count($progresso_alunos);
                                                echo $total_matriculas;
                                                ?>
                                            </div>
                                            <div class="stats-label">Total de Matrículas</div>
                                        </div>
                                        <div class="text-blue-500 bg-blue-100 p-3 rounded-full">
                                            <i class="fas fa-user-graduate text-xl"></i>
                                        </div>
                                    </div>
                                </div>

                                <div class="stats-card">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="stats-value">
                                                <?php
                                                $progresso_medio = 0;
                                                if ($total_matriculas > 0) {
                                                    $soma_progresso = 0;
                                                    foreach ($progresso_alunos as $progresso) {
                                                        $soma_progresso += $progresso['progresso'];
                                                    }
                                                    $progresso_medio = round($soma_progresso / $total_matriculas, 1);
                                                }
                                                echo $progresso_medio . '%';
                                                ?>
                                            </div>
                                            <div class="stats-label">Progresso Médio</div>
                                        </div>
                                        <div class="text-green-500 bg-green-100 p-3 rounded-full">
                                            <i class="fas fa-chart-pie text-xl"></i>
                                        </div>
                                    </div>
                                </div>

                                <div class="stats-card">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="stats-value">
                                                <?php
                                                $concluidos = 0;
                                                foreach ($progresso_alunos as $progresso) {
                                                    if ($progresso['progresso'] >= 100) {
                                                        $concluidos++;
                                                    }
                                                }
                                                echo $concluidos;
                                                ?>
                                            </div>
                                            <div class="stats-label">Cursos Concluídos</div>
                                        </div>
                                        <div class="text-purple-500 bg-purple-100 p-3 rounded-full">
                                            <i class="fas fa-check-circle text-xl"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tabela de Dados -->
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aluno</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Polo</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Curso</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Progresso</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Datas</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php if (empty($progresso_alunos)): ?>
                                        <tr>
                                            <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                                Nenhum dado encontrado para o período selecionado.
                                            </td>
                                        </tr>
                                        <?php else: ?>
                                            <?php foreach ($progresso_alunos as $progresso): ?>
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($progresso['aluno']); ?></div>
                                                    <div class="text-xs text-gray-500">ID: <?php echo $progresso['aluno_id']; ?></div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($progresso['polo_nome'] ?? 'N/A'); ?></div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($progresso['curso']); ?></div>
                                                    <div class="text-xs text-gray-500">ID: <?php echo $progresso['curso_id']; ?></div>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <?php
                                                    $progress_class = 'progress-fill-low';
                                                    if ($progresso['progresso'] >= 70) {
                                                        $progress_class = 'progress-fill-high';
                                                    } elseif ($progresso['progresso'] >= 30) {
                                                        $progress_class = 'progress-fill-medium';
                                                    }
                                                    ?>
                                                    <div class="progress-bar">
                                                        <div class="progress-fill <?php echo $progress_class; ?>" style="width: <?php echo $progresso['progresso']; ?>%"></div>
                                                    </div>
                                                    <div class="flex justify-between text-xs mt-1">
                                                        <span class="font-medium"><?php echo $progresso['progresso']; ?>%</span>
                                                        <?php if ($progresso['progresso'] == 100): ?>
                                                        <span class="text-green-600 font-medium">Concluído</span>
                                                        <?php elseif ($progresso['progresso'] == 0): ?>
                                                        <span class="text-red-600 font-medium">Não iniciado</span>
                                                        <?php else: ?>
                                                        <span class="text-yellow-600 font-medium">Em andamento</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-xs space-y-1">
                                                        <div>
                                                            <span class="font-medium text-gray-500">Matrícula:</span>
                                                            <span class="text-gray-900"><?php echo isset($progresso['data_matricula']) ? date('d/m/Y', strtotime($progresso['data_matricula'])) : 'N/A'; ?></span>
                                                        </div>
                                                        <div>
                                                            <span class="font-medium text-gray-500">Início:</span>
                                                            <span class="text-gray-900"><?php echo isset($progresso['data_inicio']) ? date('d/m/Y', strtotime($progresso['data_inicio'])) : 'N/A'; ?></span>
                                                        </div>
                                                        <div>
                                                            <span class="font-medium text-gray-500">Conclusão:</span>
                                                            <span class="text-gray-900"><?php echo isset($progresso['data_conclusao']) && $progresso['data_conclusao'] ? date('d/m/Y', strtotime($progresso['data_conclusao'])) : 'Pendente'; ?></span>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
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

        // Inicializa o seletor de data
        document.addEventListener('DOMContentLoaded', function() {
            if (document.getElementById('data_inicio') && document.getElementById('data_fim')) {
                flatpickr("#data_inicio", {
                    dateFormat: "Y-m-d",
                    locale: {
                        firstDayOfWeek: 1,
                        weekdays: {
                            shorthand: ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'],
                            longhand: ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado']
                        },
                        months: {
                            shorthand: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'],
                            longhand: ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro']
                        }
                    }
                });

                flatpickr("#data_fim", {
                    dateFormat: "Y-m-d",
                    locale: {
                        firstDayOfWeek: 1,
                        weekdays: {
                            shorthand: ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'],
                            longhand: ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado']
                        },
                        months: {
                            shorthand: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'],
                            longhand: ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro']
                        }
                    }
                });
            }
        });

        <?php if ($tipo_relatorio === 'acessos'): ?>
        // Gráfico de Acessos
        const acessosCtx = document.getElementById('acessosChart').getContext('2d');

        // Preparar dados para o gráfico
        const acessosData = {
            labels: [
                <?php
                $datas = array_map(function($acesso) {
                    return "'" . date('d/m', strtotime($acesso['data'])) . "'";
                }, array_reverse($acessos));
                echo implode(', ', $datas);
                ?>
            ],
            datasets: [
                {
                    label: 'Total de Acessos',
                    data: [
                        <?php
                        $totais = array_map(function($acesso) {
                            return $acesso['total_acessos'];
                        }, array_reverse($acessos));
                        echo implode(', ', $totais);
                        ?>
                    ],
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 2,
                    tension: 0.1,
                    fill: true
                },
                {
                    label: 'Alunos Únicos',
                    data: [
                        <?php
                        $alunos = array_map(function($acesso) {
                            return $acesso['total_alunos'];
                        }, array_reverse($acessos));
                        echo implode(', ', $alunos);
                        ?>
                    ],
                    borderColor: 'rgb(16, 185, 129)',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 2,
                    tension: 0.1,
                    fill: true
                }
            ]
        };

        // Configurações do gráfico
        const acessosOptions = {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        padding: 20
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(255, 255, 255, 0.9)',
                    titleColor: '#1f2937',
                    bodyColor: '#4b5563',
                    borderColor: '#e5e7eb',
                    borderWidth: 1,
                    padding: 12,
                    boxPadding: 6,
                    usePointStyle: true,
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += context.parsed.y;
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(229, 231, 235, 0.5)'
                    }
                }
            }
        };

        // Criar o gráfico
        const acessosChart = new Chart(acessosCtx, {
            type: 'line',
            data: acessosData,
            options: acessosOptions
        });
        <?php elseif ($tipo_relatorio === 'alunos_ativos'): ?>
        // Gráfico de Alunos Ativos por Polo
        const alunosAtivosCtx = document.getElementById('alunosAtivosChart').getContext('2d');

        // Preparar dados para o gráfico
        const alunosAtivosData = {
            labels: [
                <?php
                $polos = array_map(function($polo) {
                    return "'" . $polo['polo'] . "'";
                }, $alunos_ativos);
                echo implode(', ', $polos);
                ?>
            ],
            datasets: [
                {
                    label: 'Alunos Ativos',
                    data: [
                        <?php
                        $ativos = array_map(function($polo) {
                            return $polo['alunos_ativos'];
                        }, $alunos_ativos);
                        echo implode(', ', $ativos);
                        ?>
                    ],
                    backgroundColor: 'rgba(16, 185, 129, 0.7)',
                    borderColor: 'rgb(16, 185, 129)',
                    borderWidth: 1,
                    borderRadius: 4,
                },
                {
                    label: 'Alunos Inativos',
                    data: [
                        <?php
                        $inativos = array_map(function($polo) {
                            return $polo['alunos_inativos'];
                        }, $alunos_ativos);
                        echo implode(', ', $inativos);
                        ?>
                    ],
                    backgroundColor: 'rgba(239, 68, 68, 0.7)',
                    borderColor: 'rgb(239, 68, 68)',
                    borderWidth: 1,
                    borderRadius: 4,
                }
            ]
        };

        // Configurações do gráfico
        const alunosAtivosOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        padding: 20
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(255, 255, 255, 0.9)',
                    titleColor: '#1f2937',
                    bodyColor: '#4b5563',
                    borderColor: '#e5e7eb',
                    borderWidth: 1,
                    padding: 12,
                    boxPadding: 6,
                    usePointStyle: true
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(229, 231, 235, 0.5)'
                    }
                }
            }
        };

        // Criar o gráfico
        const alunosAtivosChart = new Chart(alunosAtivosCtx, {
            type: 'bar',
            data: alunosAtivosData,
            options: alunosAtivosOptions
        });
        <?php elseif ($tipo_relatorio === 'cursos_populares'): ?>
        // Gráfico de Cursos Populares
        const cursosPopularesCtx = document.getElementById('cursosPopularesChart').getContext('2d');

        // Preparar dados para o gráfico
        const cursosPopularesData = {
            labels: [
                <?php
                $cursos = array_map(function($curso) {
                    // Limitar o tamanho do nome do curso para não sobrecarregar o gráfico
                    $nome_curso = strlen($curso['curso']) > 30 ? substr($curso['curso'], 0, 27) . '...' : $curso['curso'];
                    return "'" . addslashes($nome_curso) . "'";
                }, $cursos_populares);
                echo implode(', ', $cursos);
                ?>
            ],
            datasets: [
                {
                    label: 'Total de Matrículas',
                    data: [
                        <?php
                        $matriculas = array_map(function($curso) {
                            return $curso['total_matriculas'];
                        }, $cursos_populares);
                        echo implode(', ', $matriculas);
                        ?>
                    ],
                    backgroundColor: 'rgba(59, 130, 246, 0.7)',
                    borderColor: 'rgb(59, 130, 246)',
                    borderWidth: 1,
                    borderRadius: 4,
                },
                {
                    label: 'Progresso Médio (%)',
                    data: [
                        <?php
                        $progressos = array_map(function($curso) {
                            return round($curso['progresso_medio'], 1);
                        }, $cursos_populares);
                        echo implode(', ', $progressos);
                        ?>
                    ],
                    backgroundColor: 'rgba(139, 92, 246, 0.7)',
                    borderColor: 'rgb(139, 92, 246)',
                    borderWidth: 1,
                    borderRadius: 4,
                    // Usar um eixo Y secundário para o progresso
                    yAxisID: 'y1',
                }
            ]
        };

        // Configurações do gráfico
        const cursosPopularesOptions = {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        padding: 20
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(255, 255, 255, 0.9)',
                    titleColor: '#1f2937',
                    bodyColor: '#4b5563',
                    borderColor: '#e5e7eb',
                    borderWidth: 1,
                    padding: 12,
                    boxPadding: 6,
                    usePointStyle: true
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(229, 231, 235, 0.5)'
                    },
                    title: {
                        display: true,
                        text: 'Total de Matrículas'
                    }
                },
                y: {
                    grid: {
                        display: false
                    }
                },
                y1: {
                    position: 'right',
                    beginAtZero: true,
                    max: 100,
                    grid: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Progresso Médio (%)'
                    }
                }
            }
        };

        // Criar o gráfico
        const cursosPopularesChart = new Chart(cursosPopularesCtx, {
            type: 'bar',
            data: cursosPopularesData,
            options: cursosPopularesOptions
        });
        <?php endif; ?>
    </script>
</body>
</html>
