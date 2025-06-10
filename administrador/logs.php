<?php
/**
 * ============================================================================
 * LOGS E AUDITORIA - MÓDULO ADMINISTRADOR
 * ============================================================================
 *
 * Página para visualização e análise de logs do sistema:
 * - Logs de login e logout
 * - Ações dos usuários
 * - Tentativas de acesso
 * - Filtros avançados
 * - Exportação de relatórios
 *
 * @author Sistema Faciência ERP
 * @version 1.0
 * @since 2025-06-10
 *
 * ============================================================================
 */

// Inicializa o módulo administrador
require_once __DIR__ . '/includes/init.php';

// Exige acesso administrativo
exigirAcessoAdministrador();

// ============================================================================
// PROCESSAMENTO DE FILTROS
// ============================================================================

$filtro_modulo = $_GET['filtro_modulo'] ?? '';
$filtro_acao = $_GET['filtro_acao'] ?? '';
$filtro_usuario = $_GET['filtro_usuario'] ?? '';
$filtro_data_inicio = $_GET['filtro_data_inicio'] ?? '';
$filtro_data_fim = $_GET['filtro_data_fim'] ?? '';
$filtro_ip = $_GET['filtro_ip'] ?? '';
$filtro_busca = $_GET['busca'] ?? '';
$pagina_atual = (int)($_GET['pagina'] ?? 1);
$itens_por_pagina = 50;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

// Instância do banco de dados
$db = Database::getInstance();

// ============================================================================
// CONSTRUÇÃO DA QUERY COM FILTROS
// ============================================================================

$where = [];
$params = [];

if ($filtro_modulo) {
    $where[] = "l.modulo = ?";
    $params[] = $filtro_modulo;
}

if ($filtro_acao) {
    $where[] = "l.acao = ?";
    $params[] = $filtro_acao;
}

if ($filtro_usuario) {
    $where[] = "l.usuario_id = ?";
    $params[] = $filtro_usuario;
}

if ($filtro_data_inicio) {
    $where[] = "l.created_at >= ?";
    $params[] = $filtro_data_inicio . ' 00:00:00';
}

if ($filtro_data_fim) {
    $where[] = "l.created_at <= ?";
    $params[] = $filtro_data_fim . ' 23:59:59';
}

if ($filtro_ip) {
    $where[] = "l.ip_origem = ?";
    $params[] = $filtro_ip;
}

if ($filtro_busca) {
    $where[] = "(l.descricao LIKE ? OR l.dados_novos LIKE ? OR l.dados_antigos LIKE ?)";
    $params[] = "%{$filtro_busca}%";
    $params[] = "%{$filtro_busca}%";
    $params[] = "%{$filtro_busca}%";
}

$where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// ============================================================================
// CARREGAMENTO DE DADOS
// ============================================================================

// Conta o total de logs
$sql_count = "SELECT COUNT(*) as total FROM logs_sistema l {$where_clause}";
$total_registros = $db->fetchOne($sql_count, $params)['total'] ?? 0;
$total_paginas = ceil($total_registros / $itens_por_pagina);

// Busca os logs
$sql = "SELECT 
            l.*,
            u.nome as usuario_nome,
            u.email as usuario_email,
            u.tipo as usuario_tipo
        FROM logs_sistema l
        LEFT JOIN usuarios u ON l.usuario_id = u.id
        {$where_clause}
        ORDER BY l.created_at DESC
        LIMIT ? OFFSET ?";

$params[] = $itens_por_pagina;
$params[] = $offset;

$logs = $db->fetchAll($sql, $params);

// Obtém estatísticas de logs
$stats_modulos = $db->fetchAll("
    SELECT modulo, COUNT(*) as total 
    FROM logs_sistema 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY modulo 
    ORDER BY total DESC
");

$stats_acoes = $db->fetchAll("
    SELECT acao, COUNT(*) as total 
    FROM logs_sistema 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    GROUP BY acao 
    ORDER BY total DESC 
    LIMIT 10
");

$stats_usuarios_ativos = $db->fetchAll("
    SELECT 
        l.usuario_id,
        u.nome,
        u.email,
        COUNT(*) as total_acoes,
        MAX(l.created_at) as ultima_acao
    FROM logs_sistema l
    LEFT JOIN usuarios u ON l.usuario_id = u.id
    WHERE l.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        AND l.usuario_id IS NOT NULL
    GROUP BY l.usuario_id, u.nome, u.email
    ORDER BY total_acoes DESC
    LIMIT 10
");

// Obtém lista de módulos para filtro
$modulos_disponiveis = $db->fetchAll("SELECT DISTINCT modulo FROM logs_sistema ORDER BY modulo");

// Obtém lista de ações para filtro
$acoes_disponiveis = $db->fetchAll("SELECT DISTINCT acao FROM logs_sistema ORDER BY acao");

// Obtém IPs mais ativos
$ips_ativos = $db->fetchAll("
    SELECT 
        ip_origem,
        COUNT(*) as total,
        COUNT(DISTINCT usuario_id) as usuarios_unicos,
        MAX(created_at) as ultima_atividade
    FROM logs_sistema 
    WHERE ip_origem IS NOT NULL 
        AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    GROUP BY ip_origem 
    ORDER BY total DESC 
    LIMIT 10
");

// Registra acesso aos logs
registrarAcaoAdministrativa('logs_visualizacao', 'Acesso à página de logs e auditoria', [
    'filtros_aplicados' => count($where),
    'total_logs_encontrados' => $total_registros
]);

$titulo_pagina = 'Logs e Auditoria';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faciência ERP - <?php echo htmlspecialchars($titulo_pagina); ?></title>
    
    <!-- CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --admin-primary: #DC2626;
            --admin-primary-dark: #B91C1C;
        }
        
        .admin-nav {
            background: linear-gradient(135deg, var(--admin-primary), var(--admin-primary-dark));
        }
        
        .admin-card {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(220, 38, 38, 0.1);
        }
        
        .log-row:hover {
            background-color: #FEF2F2;
        }
        
        .log-details {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }
        
        .log-details.expanded {
            max-height: 500px;
        }
        
        .acao-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .acao-login { background: #D1FAE5; color: #065F46; }
        .acao-logout { background: #E5E7EB; color: #374151; }
        .acao-login_falha { background: #FEE2E2; color: #991B1B; }
        .acao-criar { background: #DBEAFE; color: #1E40AF; }
        .acao-editar { background: #FEF3C7; color: #92400E; }
        .acao-excluir { background: #FEE2E2; color: #991B1B; }
        .acao-default { background: #F3F4F6; color: #4B5563; }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <header class="admin-nav text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <a href="index.php" class="flex items-center text-white hover:text-gray-200">
                        <i class="fas fa-shield-alt text-2xl mr-3"></i>
                        <h1 class="text-xl font-bold">Administração</h1>
                    </a>
                </div>
                
                <nav class="hidden md:flex space-x-1">
                    <a href="index.php" class="px-3 py-2 rounded text-white hover:bg-white hover:bg-opacity-10">Dashboard</a>
                    <a href="usuarios.php" class="px-3 py-2 rounded text-white hover:bg-white hover:bg-opacity-10">Usuários</a>
                    <a href="logs.php" class="px-3 py-2 rounded bg-white bg-opacity-20 text-white">Logs</a>
                    <a href="configuracoes.php" class="px-3 py-2 rounded text-white hover:bg-white hover:bg-opacity-10">Config</a>
                </nav>
                
                <div class="flex items-center space-x-4">
                    <span class="text-sm"><?php echo htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['user']['nome'] ?? 'Usuário'); ?></span>
                    <a href="../logout.php" class="text-white hover:text-gray-200">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <!-- Cabeçalho -->
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-900">Logs e Auditoria</h2>
            <p class="mt-2 text-gray-600">Monitoramento de atividades do sistema</p>
        </div>

        <!-- Estatísticas Rápidas -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <!-- Total de Logs -->
            <div class="admin-card p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-file-alt text-2xl text-blue-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Total de Logs</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($total_registros); ?></p>
                    </div>
                </div>
            </div>

            <!-- Atividade Hoje -->
            <div class="admin-card p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-chart-line text-2xl text-green-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Logs Hoje</p>
                        <p class="text-2xl font-bold text-gray-900">
                            <?php 
                            $logs_hoje = array_sum(array_column($stats_acoes, 'total'));
                            echo number_format($logs_hoje); 
                            ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Usuários Ativos -->
            <div class="admin-card p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-users text-2xl text-purple-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Usuários Ativos</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo count($stats_usuarios_ativos); ?></p>
                    </div>
                </div>
            </div>

            <!-- IPs Únicos -->
            <div class="admin-card p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-globe text-2xl text-orange-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">IPs Únicos</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo count($ips_ativos); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Layout de duas colunas -->
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8 mb-8">
            <!-- Sidebar com estatísticas (1/4) -->
            <div class="space-y-6">
                <!-- Módulos Mais Ativos -->
                <div class="admin-card">
                    <div class="p-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Módulos (7 dias)</h3>
                    </div>
                    <div class="p-4">
                        <?php foreach (array_slice($stats_modulos, 0, 8) as $modulo): ?>
                        <div class="flex justify-between items-center py-2">
                            <span class="text-sm text-gray-600"><?php echo htmlspecialchars($modulo['modulo']); ?></span>
                            <span class="text-sm font-medium text-gray-900"><?php echo number_format($modulo['total']); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- IPs Mais Ativos -->
                <div class="admin-card">
                    <div class="p-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">IPs Ativos (24h)</h3>
                    </div>
                    <div class="p-4">
                        <?php foreach (array_slice($ips_ativos, 0, 5) as $ip): ?>
                        <div class="py-2">
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-mono text-gray-600"><?php echo htmlspecialchars($ip['ip_origem']); ?></span>
                                <span class="text-sm text-gray-900"><?php echo number_format($ip['total']); ?></span>
                            </div>
                            <div class="text-xs text-gray-500">
                                <?php echo $ip['usuarios_unicos']; ?> usuário(s)
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Conteúdo principal (3/4) -->
            <div class="lg:col-span-3">
                <!-- Filtros Avançados -->
                <div class="admin-card mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Filtros Avançados</h3>
                        <form method="GET" action="logs.php">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Módulo</label>
                                    <select name="filtro_modulo" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500">
                                        <option value="">Todos os módulos</option>
                                        <?php foreach ($modulos_disponiveis as $modulo): ?>
                                        <option value="<?php echo $modulo['modulo']; ?>" <?php echo $filtro_modulo === $modulo['modulo'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($modulo['modulo']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Ação</label>
                                    <select name="filtro_acao" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500">
                                        <option value="">Todas as ações</option>
                                        <?php foreach ($acoes_disponiveis as $acao): ?>
                                        <option value="<?php echo $acao['acao']; ?>" <?php echo $filtro_acao === $acao['acao'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($acao['acao']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                                    <input type="text" name="busca" value="<?php echo htmlspecialchars($filtro_busca); ?>" 
                                           placeholder="Descrição, dados..." 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500">
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Data Início</label>
                                    <input type="date" name="filtro_data_inicio" value="<?php echo htmlspecialchars($filtro_data_inicio); ?>" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Data Fim</label>
                                    <input type="date" name="filtro_data_fim" value="<?php echo htmlspecialchars($filtro_data_fim); ?>" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">IP</label>
                                    <input type="text" name="filtro_ip" value="<?php echo htmlspecialchars($filtro_ip); ?>" 
                                           placeholder="IP específico..." 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500">
                                </div>
                            </div>
                            
                            <div class="flex space-x-2">
                                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                                    <i class="fas fa-filter mr-2"></i>Aplicar Filtros
                                </button>
                                <a href="logs.php" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
                                    <i class="fas fa-times mr-2"></i>Limpar                                </a>
                                <button type="button" onclick="exportarLogs()" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                                    <i class="fas fa-download mr-2"></i>Exportar
                                </button>
                                <button type="button" onclick="limparLogsAntigos()" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                                    <i class="fas fa-trash mr-2"></i>Limpar Antigos
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Lista de Logs -->
                <div class="admin-card">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">
                            Logs do Sistema (<?php echo number_format($total_registros); ?> encontrados)
                        </h3>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data/Hora</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Usuário</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Módulo</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ação</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Descrição</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">IP</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="6" class="px-4 py-4 text-center text-gray-500">
                                        Nenhum log encontrado com os filtros aplicados.
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                <tr class="log-row cursor-pointer" onclick="toggleLogDetails(<?php echo $log['id']; ?>)">
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm">
                                        <?php if ($log['usuario_nome']): ?>
                                        <div>
                                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($log['usuario_nome']); ?></div>
                                            <div class="text-gray-500"><?php echo htmlspecialchars($log['usuario_tipo'] ?? ''); ?></div>
                                        </div>
                                        <?php else: ?>
                                        <span class="text-gray-500">Sistema</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($log['modulo']); ?>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <?php
                                        $classe_acao = 'acao-default';
                                        if (strpos($log['acao'], 'login') !== false) {
                                            $classe_acao = strpos($log['acao'], 'falha') !== false ? 'acao-login_falha' : 'acao-login';
                                        } elseif (strpos($log['acao'], 'logout') !== false) {
                                            $classe_acao = 'acao-logout';
                                        } elseif (strpos($log['acao'], 'criar') !== false || strpos($log['acao'], 'novo') !== false) {
                                            $classe_acao = 'acao-criar';
                                        } elseif (strpos($log['acao'], 'editar') !== false || strpos($log['acao'], 'atualizar') !== false) {
                                            $classe_acao = 'acao-editar';
                                        } elseif (strpos($log['acao'], 'excluir') !== false || strpos($log['acao'], 'deletar') !== false) {
                                            $classe_acao = 'acao-excluir';
                                        }
                                        ?>
                                        <span class="acao-badge <?php echo $classe_acao; ?>">
                                            <?php echo htmlspecialchars($log['acao']); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 text-sm text-gray-900">
                                        <div class="max-w-xs truncate">
                                            <?php echo htmlspecialchars($log['descricao']); ?>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm font-mono text-gray-500">
                                        <?php echo htmlspecialchars($log['ip_origem'] ?? '-'); ?>
                                    </td>
                                </tr>
                                
                                <!-- Detalhes expandidos do log -->
                                <tr id="details-<?php echo $log['id']; ?>" class="log-details">
                                    <td colspan="6" class="px-4 py-4 bg-gray-50">
                                        <div class="space-y-4">
                                            <div>
                                                <h4 class="font-medium text-gray-900">Descrição Completa:</h4>
                                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($log['descricao']); ?></p>
                                            </div>
                                            
                                            <?php if ($log['dispositivo']): ?>
                                            <div>
                                                <h4 class="font-medium text-gray-900">Dispositivo:</h4>
                                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($log['dispositivo']); ?></p>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <?php if ($log['dados_novos']): ?>
                                            <div>
                                                <h4 class="font-medium text-gray-900">Dados da Ação:</h4>
                                                <pre class="text-xs text-gray-600 bg-white p-2 rounded border overflow-x-auto"><?php echo htmlspecialchars($log['dados_novos']); ?></pre>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <?php if ($log['dados_antigos']): ?>
                                            <div>
                                                <h4 class="font-medium text-gray-900">Dados Anteriores:</h4>
                                                <pre class="text-xs text-gray-600 bg-white p-2 rounded border overflow-x-auto"><?php echo htmlspecialchars($log['dados_antigos']); ?></pre>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Paginação -->
                    <?php if ($total_paginas > 1): ?>
                    <div class="px-6 py-4 border-t border-gray-200">
                        <div class="flex justify-between items-center">
                            <div class="text-sm text-gray-500">
                                Mostrando <?php echo ($offset + 1); ?> a <?php echo min($offset + $itens_por_pagina, $total_registros); ?> 
                                de <?php echo number_format($total_registros); ?> logs
                            </div>
                            
                            <div class="flex space-x-2">
                                <?php if ($pagina_atual > 1): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina_atual - 1])); ?>" 
                                   class="px-3 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                                    Anterior
                                </a>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $pagina_atual - 2); $i <= min($total_paginas, $pagina_atual + 2); $i++): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $i])); ?>" 
                                   class="px-3 py-2 <?php echo $i === $pagina_atual ? 'bg-red-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> rounded">
                                    <?php echo $i; ?>
                                </a>
                                <?php endfor; ?>
                                
                                <?php if ($pagina_atual < $total_paginas): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina_atual + 1])); ?>" 
                                   class="px-3 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                                    Próxima
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleLogDetails(logId) {
            const details = document.getElementById('details-' + logId);
            if (details.classList.contains('expanded')) {
                details.classList.remove('expanded');
            } else {
                // Fecha todos os outros detalhes primeiro
                document.querySelectorAll('.log-details.expanded').forEach(el => {
                    el.classList.remove('expanded');
                });
                details.classList.add('expanded');
            }
        }
          async function exportarLogs() {
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Exportando...';
            btn.disabled = true;
            
            try {
                // Coletar filtros atuais
                const formData = new FormData();
                formData.append('acao', 'exportar_logs');
                
                // Adicionar filtros
                const dataInicio = document.querySelector('input[name="data_inicio"]')?.value;
                const dataFim = document.querySelector('input[name="data_fim"]')?.value;
                const modulo = document.querySelector('select[name="filtro_modulo"]')?.value;
                const acao = document.querySelector('select[name="filtro_acao"]')?.value;
                const busca = document.querySelector('input[name="busca"]')?.value;
                
                if (dataInicio) formData.append('data_inicio', dataInicio);
                if (dataFim) formData.append('data_fim', dataFim);
                if (modulo) formData.append('modulo', modulo);
                if (acao) formData.append('acao', acao);
                if (busca) formData.append('busca', busca);
                
                const response = await fetch('includes/ajax.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Fazer download do arquivo
                    const link = document.createElement('a');
                    link.href = result.url_download;
                    link.download = result.arquivo;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    
                    alert('Logs exportados com sucesso!');
                } else {
                    alert('Erro ao exportar logs: ' + result.message);
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro ao exportar logs. Tente novamente.');
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }

        async function limparLogsAntigos() {
            const dias = prompt('Quantos dias de logs manter? (será removido tudo mais antigo que isso)', '30');
            
            if (!dias || isNaN(dias) || parseInt(dias) < 1) {
                alert('Número de dias inválido');
                return;
            }
            
            if (!confirm(`Tem certeza que deseja remover todos os logs com mais de ${dias} dias?\n\nEsta ação não pode ser desfeita!`)) {
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('acao', 'limpar_logs');
                formData.append('dias', dias);
                
                const response = await fetch('includes/ajax.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert(result.message);
                    window.location.reload();
                } else {
                    alert('Erro: ' + result.message);
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro ao limpar logs. Tente novamente.');
            }
        }
        
        // Auto-refresh a cada 30 segundos se não houver filtros aplicados
        <?php if (empty($where)): ?>
        setTimeout(() => {
            window.location.reload();
        }, 30000);
        <?php endif; ?>
    </script>
</body>
</html>
