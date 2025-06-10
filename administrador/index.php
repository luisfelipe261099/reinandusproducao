<?php
/**
 * ============================================================================
 * DASHBOARD ADMINISTRATIVO - SISTEMA FACIÊNCIA ERP
 * ============================================================================
 *
 * Painel principal de administração do sistema para gerenciamento de:
 * - Usuários e permissões
 * - Logs de sistema e auditoria
 * - Estatísticas gerais
 * - Configurações globais
 * - Monitoramento de acessos
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
// CARREGAMENTO DE DADOS PARA O DASHBOARD
// ============================================================================

try {
    // Obtém estatísticas gerais do sistema
    $estatisticas = obterEstatisticasGerais();
    
    // Obtém logs recentes (últimas 20 ações)
    $logs_recentes = obterLogsRecentes(20);
    
    // Obtém tentativas de login recentes
    $tentativas_login = obterTentativasLogin(15);
    
    // Calcula estatísticas de atividade
    $atividade_hoje = 0;
    $atividade_ontem = 0;
    $logins_hoje = 0;
    
    foreach ($logs_recentes as $log) {
        $data_log = date('Y-m-d', strtotime($log['created_at']));
        $hoje = date('Y-m-d');
        $ontem = date('Y-m-d', strtotime('-1 day'));
        
        if ($data_log === $hoje) {
            $atividade_hoje++;
            if ($log['acao'] === 'login') {
                $logins_hoje++;
            }
        } elseif ($data_log === $ontem) {
            $atividade_ontem++;
        }
    }
    
} catch (Exception $e) {
    error_log('Erro ao carregar dados do dashboard administrativo: ' . $e->getMessage());
    
    // Inicializa com valores padrão em caso de erro
    $estatisticas = [];
    $logs_recentes = [];
    $tentativas_login = [];
    $atividade_hoje = 0;
    $atividade_ontem = 0;
    $logins_hoje = 0;
}

// Registra acesso ao dashboard
registrarAcaoAdministrativa(
    'dashboard_acesso',
    'Acesso ao dashboard administrativo',
    ['pagina' => 'index.php']
);

// Define o título da página
$titulo_pagina = 'Dashboard Administrativo';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <!-- ================================================================== -->
    <!-- META TAGS E CONFIGURAÇÕES BÁSICAS -->
    <!-- ================================================================== -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Dashboard Administrativo - Sistema Faciência ERP">
    <meta name="author" content="Sistema Faciência ERP">
    <meta name="robots" content="noindex, nofollow">

    <!-- Título da página -->
    <title>Faciência ERP - <?php echo htmlspecialchars($titulo_pagina); ?></title>

    <!-- ================================================================== -->
    <!-- RECURSOS EXTERNOS (CDN) -->
    <!-- ================================================================== -->

    <!-- Tailwind CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js para gráficos -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- ================================================================== -->
    <!-- ESTILOS ESPECÍFICOS DO MÓDULO ADMINISTRATIVO -->
    <!-- ================================================================== -->
    <style>
        :root {
            --admin-primary: #DC2626;
            --admin-primary-dark: #B91C1C;
            --admin-secondary: #EF4444;
            --admin-accent: #FCA5A5;
            --admin-success: #10B981;
            --admin-warning: #F59E0B;
            --admin-danger: #EF4444;
            --admin-info: #3B82F6;
            --admin-light: #FEF2F2;
            --admin-dark: #1F2937;
            --border-radius: 0.5rem;
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: #F9FAFB;
        }

        /* ============================================================== */
        /* CARDS ADMINISTRATIVOS */
        /* ============================================================== */
        .admin-card {
            background: linear-gradient(135deg, #ffffff 0%, #fef9f9 100%);
            border-radius: var(--border-radius);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(220, 38, 38, 0.1);
            transition: var(--transition);
        }

        .admin-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            border-color: rgba(220, 38, 38, 0.2);
        }

        .stat-card {
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--admin-primary), var(--admin-secondary));
        }

        /* ============================================================== */
        /* BADGES E STATUS */
        /* ============================================================== */
        .badge-admin {
            background: linear-gradient(135deg, var(--admin-primary), var(--admin-secondary));
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-online {
            color: var(--admin-success);
        }

        .status-offline {
            color: #6B7280;
        }

        .status-warning {
            color: var(--admin-warning);
        }

        .status-danger {
            color: var(--admin-danger);
        }

        /* ============================================================== */
        /* TABELAS ADMINISTRATIVAS */
        /* ============================================================== */
        .admin-table {
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .admin-table thead {
            background: linear-gradient(135deg, var(--admin-light), #ffffff);
        }

        .admin-table th {
            padding: 1rem;
            font-weight: 600;
            color: var(--admin-dark);
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
        }

        .admin-table td {
            padding: 1rem;
            border-top: 1px solid #F3F4F6;
        }

        .admin-table tbody tr:hover {
            background-color: var(--admin-light);
        }

        /* ============================================================== */
        /* BOTÕES ADMINISTRATIVOS */
        /* ============================================================== */
        .btn-admin {
            background: linear-gradient(135deg, var(--admin-primary), var(--admin-primary-dark));
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: var(--border-radius);
            font-weight: 500;
            transition: var(--transition);
            border: none;
            cursor: pointer;
        }

        .btn-admin:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(220, 38, 38, 0.3);
        }

        .btn-admin-secondary {
            background: linear-gradient(135deg, #6B7280, #4B5563);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            font-weight: 500;
            transition: var(--transition);
            border: none;
            cursor: pointer;
        }

        /* ============================================================== */
        /* NAVEGAÇÃO ADMINISTRATVA */
        /* ============================================================== */
        .admin-nav {
            background: linear-gradient(135deg, var(--admin-primary), var(--admin-primary-dark));
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .admin-nav-item {
            color: rgba(255, 255, 255, 0.8);
            transition: var(--transition);
            padding: 0.75rem 1.5rem;
            border-radius: var(--border-radius);
            margin: 0.25rem;
        }

        .admin-nav-item:hover,
        .admin-nav-item.active {
            color: white;
            background: rgba(255, 255, 255, 0.1);
        }

        /* ============================================================== */
        /* INDICADORES DE ATIVIDADE */
        /* ============================================================== */
        .activity-indicator {
            position: relative;
        }

        .activity-indicator::before {
            content: '';
            position: absolute;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--admin-success);
            top: 0.25rem;
            right: 0.25rem;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        /* ============================================================== */
        /* RESPONSIVIDADE */
        /* ============================================================== */
        @media (max-width: 768px) {
            .admin-card {
                margin-bottom: 1rem;
            }
            
            .stat-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex flex-col">
        <!-- ================================================================== -->
        <!-- HEADER ADMINISTRATIVO -->
        <!-- ================================================================== -->
        <header class="admin-nav text-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center">
                        <i class="<?php echo MODULO_ICONE; ?> text-2xl mr-3"></i>
                        <h1 class="text-xl font-bold"><?php echo MODULO_TITULO; ?></h1>
                    </div>
                    
                    <nav class="hidden md:flex space-x-1">
                        <a href="index.php" class="admin-nav-item active">
                            <i class="fas fa-chart-line mr-2"></i>Dashboard
                        </a>
                        <a href="usuarios.php" class="admin-nav-item">
                            <i class="fas fa-users mr-2"></i>Usuários
                        </a>
                        <a href="logs.php" class="admin-nav-item">
                            <i class="fas fa-file-alt mr-2"></i>Logs
                        </a>
                        <a href="configuracoes.php" class="admin-nav-item">
                            <i class="fas fa-cogs mr-2"></i>Configurações
                        </a>
                        <a href="modulos.php" class="admin-nav-item">
                            <i class="fas fa-th-large mr-2"></i>Módulos
                        </a>
                    </nav>
                      <div class="flex items-center space-x-4">
                        <div class="text-sm">
                            <span class="opacity-75">Logado como:</span>
                            <span class="font-semibold"><?php echo htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['user']['nome'] ?? 'Usuário'); ?></span>
                        </div>
                        <a href="../logout.php" class="admin-nav-item">
                            <i class="fas fa-sign-out-alt mr-2"></i>Sair
                        </a>
                    </div>
                </div>
                
                <!-- Menu móvel -->
                <div class="md:hidden">
                    <nav class="flex flex-wrap gap-2 pb-4">
                        <a href="index.php" class="admin-nav-item active text-sm">Dashboard</a>
                        <a href="usuarios.php" class="admin-nav-item text-sm">Usuários</a>
                        <a href="logs.php" class="admin-nav-item text-sm">Logs</a>
                        <a href="configuracoes.php" class="admin-nav-item text-sm">Config</a>
                        <a href="modulos.php" class="admin-nav-item text-sm">Módulos</a>
                    </nav>
                </div>
            </div>
        </header>

        <!-- ================================================================== -->
        <!-- CONTEÚDO PRINCIPAL -->
        <!-- ================================================================== -->
        <main class="flex-1 max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 w-full">
            <!-- Mensagens de alerta -->
            <?php
            $alert = getAlert();
            if ($alert):
            ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $alert['type'] === 'success' ? 'bg-green-100 text-green-700 border border-green-300' : 'bg-red-100 text-red-700 border border-red-300'; ?>">
                <?php echo htmlspecialchars($alert['message']); ?>
            </div>
            <?php endif; ?>

            <!-- Cabeçalho da página -->
            <div class="mb-8">
                <h2 class="text-3xl font-bold text-gray-900">Dashboard Administrativo</h2>
                <p class="mt-2 text-gray-600">Visão geral e controle do sistema Faciência ERP</p>
                <div class="mt-2 text-sm text-gray-500">
                    <i class="fas fa-clock mr-1"></i>
                    Última atualização: <?php echo date('d/m/Y H:i:s'); ?>
                </div>
            </div>

            <!-- ============================================================== -->
            <!-- CARDS DE ESTATÍSTICAS -->
            <!-- ============================================================== -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total de Usuários -->
                <div class="admin-card stat-card p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Total de Usuários</p>
                            <p class="text-3xl font-bold text-gray-900"><?php echo number_format($estatisticas['total_usuarios_ativos'] ?? 0); ?></p>
                        </div>
                        <div class="bg-red-100 p-3 rounded-full">
                            <i class="fas fa-users text-red-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center text-sm">
                        <span class="text-green-600 font-medium flex items-center">
                            <i class="fas fa-arrow-up mr-1"></i>
                            +<?php echo $estatisticas['total_usuarios_inativos'] ?? 0; ?>
                        </span>
                        <span class="text-gray-500 ml-2">inativos/bloqueados</span>
                    </div>
                </div>

                <!-- Atividade Hoje -->
                <div class="admin-card stat-card p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Atividade Hoje</p>
                            <p class="text-3xl font-bold text-gray-900"><?php echo number_format($atividade_hoje); ?></p>
                        </div>
                        <div class="bg-blue-100 p-3 rounded-full activity-indicator">
                            <i class="fas fa-chart-line text-blue-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center text-sm">
                        <?php
                        $variacao = $atividade_ontem > 0 ? (($atividade_hoje - $atividade_ontem) / $atividade_ontem) * 100 : 0;
                        $cor_variacao = $variacao >= 0 ? 'text-green-600' : 'text-red-600';
                        $icone_variacao = $variacao >= 0 ? 'fa-arrow-up' : 'fa-arrow-down';
                        ?>
                        <span class="<?php echo $cor_variacao; ?> font-medium flex items-center">
                            <i class="fas <?php echo $icone_variacao; ?> mr-1"></i>
                            <?php echo number_format(abs($variacao), 1); ?>%
                        </span>
                        <span class="text-gray-500 ml-2">vs ontem</span>
                    </div>
                </div>

                <!-- Logins Hoje -->
                <div class="admin-card stat-card p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Logins Hoje</p>
                            <p class="text-3xl font-bold text-gray-900"><?php echo number_format($logins_hoje); ?></p>
                        </div>
                        <div class="bg-green-100 p-3 rounded-full">
                            <i class="fas fa-sign-in-alt text-green-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center text-sm">
                        <span class="text-blue-600 font-medium flex items-center">
                            <i class="fas fa-clock mr-1"></i>
                            Últimas 24h
                        </span>
                    </div>
                </div>

                <!-- Sistema Status -->
                <div class="admin-card stat-card p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Status do Sistema</p>
                            <p class="text-lg font-bold text-green-600">Operacional</p>
                        </div>
                        <div class="bg-green-100 p-3 rounded-full">
                            <i class="fas fa-server text-green-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center text-sm">
                        <span class="text-green-600 font-medium flex items-center">
                            <i class="fas fa-check-circle mr-1"></i>
                            Todos os módulos online
                        </span>
                    </div>
                </div>
            </div>

            <!-- ============================================================== -->
            <!-- SEÇÃO DE AÇÕES RÁPIDAS -->
            <!-- ============================================================== -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Ações Rápidas</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    <a href="../secretaria/index.php" class="admin-card p-4 text-center hover:shadow-md transition-all duration-200">
                        <i class="fas fa-graduation-cap text-2xl text-blue-600 mb-2"></i>
                        <p class="text-sm font-medium text-gray-700">Secretaria</p>
                    </a>
                    <a href="../financeiro/index.php" class="admin-card p-4 text-center hover:shadow-md transition-all duration-200">
                        <i class="fas fa-dollar-sign text-2xl text-green-600 mb-2"></i>
                        <p class="text-sm font-medium text-gray-700">Financeiro</p>
                    </a>
                    <a href="../polo/index.php" class="admin-card p-4 text-center hover:shadow-md transition-all duration-200">
                        <i class="fas fa-building text-2xl text-purple-600 mb-2"></i>
                        <p class="text-sm font-medium text-gray-700">Polos</p>
                    </a>
                    <a href="usuarios.php" class="admin-card p-4 text-center hover:shadow-md transition-all duration-200">
                        <i class="fas fa-user-cog text-2xl text-red-600 mb-2"></i>
                        <p class="text-sm font-medium text-gray-700">Usuários</p>
                    </a>
                    <a href="logs.php" class="admin-card p-4 text-center hover:shadow-md transition-all duration-200">
                        <i class="fas fa-search text-2xl text-yellow-600 mb-2"></i>
                        <p class="text-sm font-medium text-gray-700">Auditoria</p>
                    </a>
                    <a href="configuracoes.php" class="admin-card p-4 text-center hover:shadow-md transition-all duration-200">
                        <i class="fas fa-cogs text-2xl text-gray-600 mb-2"></i>
                        <p class="text-sm font-medium text-gray-700">Configurações</p>
                    </a>
                </div>
            </div>

            <!-- ============================================================== -->
            <!-- LAYOUT DE DUAS COLUNAS -->
            <!-- ============================================================== -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Logs Recentes -->
                <div class="admin-card">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <h3 class="text-lg font-semibold text-gray-900">Atividade Recente</h3>
                            <a href="logs.php" class="text-sm text-red-600 hover:text-red-800">Ver todos</a>
                        </div>
                    </div>
                    <div class="p-6">
                        <?php if (empty($logs_recentes)): ?>
                        <p class="text-gray-500 text-center py-4">Nenhuma atividade recente encontrada.</p>
                        <?php else: ?>
                        <div class="space-y-4 max-h-96 overflow-y-auto">
                            <?php foreach (array_slice($logs_recentes, 0, 10) as $log): ?>
                            <div class="flex items-start space-x-3 p-3 bg-gray-50 rounded-lg">
                                <div class="flex-shrink-0">
                                    <?php
                                    $icone_acao = 'fas fa-info-circle text-blue-500';
                                    switch ($log['acao']) {
                                        case 'login':
                                            $icone_acao = 'fas fa-sign-in-alt text-green-500';
                                            break;
                                        case 'login_falha':
                                            $icone_acao = 'fas fa-exclamation-triangle text-red-500';
                                            break;
                                        case 'logout':
                                            $icone_acao = 'fas fa-sign-out-alt text-gray-500';
                                            break;
                                        case 'criar':
                                        case 'salvar':
                                            $icone_acao = 'fas fa-plus text-green-500';
                                            break;
                                        case 'editar':
                                        case 'atualizar':
                                            $icone_acao = 'fas fa-edit text-yellow-500';
                                            break;
                                        case 'excluir':
                                            $icone_acao = 'fas fa-trash text-red-500';
                                            break;
                                    }
                                    ?>
                                    <i class="<?php echo $icone_acao; ?>"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($log['usuario_nome'] ?? 'Sistema'); ?>
                                    </p>
                                    <p class="text-sm text-gray-600">
                                        <?php echo htmlspecialchars($log['descricao']); ?>
                                    </p>
                                    <p class="text-xs text-gray-400">
                                        <?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?>
                                        • <?php echo htmlspecialchars($log['modulo']); ?>
                                    </p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Tentativas de Login -->
                <div class="admin-card">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <h3 class="text-lg font-semibold text-gray-900">Tentativas de Login</h3>
                            <a href="logs.php?filtro_acao=login" class="text-sm text-red-600 hover:text-red-800">Ver todos</a>
                        </div>
                    </div>
                    <div class="p-6">
                        <?php if (empty($tentativas_login)): ?>
                        <p class="text-gray-500 text-center py-4">Nenhuma tentativa de login recente.</p>
                        <?php else: ?>
                        <div class="space-y-3 max-h-96 overflow-y-auto">
                            <?php foreach (array_slice($tentativas_login, 0, 10) as $tentativa): ?>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <?php
                                    $icone_status = 'fas fa-check-circle text-green-500';
                                    $cor_status = 'text-green-600';
                                    $texto_status = 'Sucesso';
                                    
                                    if (in_array($tentativa['acao'], ['login_falha', 'login_bloqueado', 'login_recaptcha_falha'])) {
                                        $icone_status = 'fas fa-times-circle text-red-500';
                                        $cor_status = 'text-red-600';
                                        $texto_status = 'Falha';
                                    }
                                    ?>
                                    <i class="<?php echo $icone_status; ?>"></i>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($tentativa['usuario_nome'] ?? 'Usuário não identificado'); ?>
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            <?php echo date('d/m/Y H:i:s', strtotime($tentativa['created_at'])); ?>
                                        </p>
                                    </div>
                                </div>
                                <span class="text-xs font-medium <?php echo $cor_status; ?>">
                                    <?php echo $texto_status; ?>
                                </span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- ============================================================== -->
            <!-- DISTRIBUIÇÃO DE USUÁRIOS POR TIPO -->
            <!-- ============================================================== -->
            <div class="mt-8 admin-card">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Distribuição de Usuários por Tipo</h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <?php
                        $tipos_usuario = [
                            'admin_master' => ['Administradores', 'fas fa-shield-alt', 'text-red-600'],
                            'diretoria' => ['Diretoria', 'fas fa-user-tie', 'text-purple-600'],
                            'secretaria_academica' => ['Secretaria Acadêmica', 'fas fa-graduation-cap', 'text-blue-600'],
                            'financeiro' => ['Financeiro', 'fas fa-dollar-sign', 'text-green-600'],
                            'polo' => ['Polos', 'fas fa-building', 'text-orange-600'],
                            'professor' => ['Professores', 'fas fa-chalkboard-teacher', 'text-indigo-600'],
                            'aluno' => ['Alunos', 'fas fa-user-graduate', 'text-yellow-600']
                        ];
                        
                        foreach ($tipos_usuario as $tipo => $info):
                            $total = $estatisticas['usuarios_por_tipo'][$tipo] ?? 0;
                        ?>
                        <div class="text-center p-4 bg-gray-50 rounded-lg">
                            <i class="<?php echo $info[1]; ?> text-2xl <?php echo $info[2]; ?> mb-2"></i>
                            <p class="text-2xl font-bold text-gray-900"><?php echo number_format($total); ?></p>
                            <p class="text-sm text-gray-600"><?php echo $info[0]; ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </main>

        <!-- ================================================================== -->
        <!-- RODAPÉ -->
        <!-- ================================================================== -->
        <footer class="bg-white border-t border-gray-200 py-4">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center text-sm text-gray-500">
                    <div>
                        © <?php echo date('Y'); ?> Faciência ERP - Módulo Administrativo
                    </div>
                    <div>
                        Versão 1.0 • Desenvolvido para gestão completa do sistema
                    </div>
                </div>
            </div>
        </footer>
    </div>

    <!-- ================================================================== -->
    <!-- SCRIPTS -->
    <!-- ================================================================== -->
    <script>
        // Atualiza o relógio da página
        function atualizarHora() {
            const agora = new Date();
            const horaFormatada = agora.toLocaleString('pt-BR');
            
            // Atualiza todos os elementos de hora se existirem
            const elementosHora = document.querySelectorAll('.hora-atual');
            elementosHora.forEach(elemento => {
                elemento.textContent = horaFormatada;
            });
        }
        
        // Atualiza a hora a cada segundo
        setInterval(atualizarHora, 1000);
        
        // Adiciona animações aos cards
        document.addEventListener('DOMContentLoaded', function() {
            // Animação de entrada dos cards
            const cards = document.querySelectorAll('.admin-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
        
        // Log da inicialização do dashboard
        console.log(`
        ╔════════════════════════════════════════════════════════════════╗
        ║                    FACIÊNCIA ERP - ADMINISTRADOR               ║
        ║                      Dashboard Carregado                      ║
        ╠════════════════════════════════════════════════════════════════╣
        ║ 👥 Usuários: <?php echo str_pad($estatisticas['total_usuarios_ativos'] ?? 0, 3, ' ', STR_PAD_LEFT); ?>                                           ║
        ║ 📊 Atividade: <?php echo str_pad($atividade_hoje, 3, ' ', STR_PAD_LEFT); ?>                                          ║
        ║ 🔐 Logins: <?php echo str_pad($logins_hoje, 3, ' ', STR_PAD_LEFT); ?>                                             ║
        ║ 📝 Logs: <?php echo str_pad(count($logs_recentes), 3, ' ', STR_PAD_LEFT); ?>                                               ║
        ╚════════════════════════════════════════════════════════════════╝
        `);
    </script>
</body>
</html>
