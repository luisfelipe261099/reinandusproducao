<?php
// Inicializa a sessão
session_start();

// Verifica se o aluno está logado
if (!isset($_SESSION['user_id']) || $_SESSION['user_tipo'] !== 'aluno') {
    // Redireciona para a página de login
    header('Location: ../login.php?redirect=aluno');
    exit;
}

// Inclui os arquivos necessários
require_once '../config.php';
require_once '../includes/functions.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

// Obtém os dados do aluno
$db = Database::getInstance();
$aluno_id = $_SESSION['user_id'];
$aluno = $db->fetchOne("SELECT * FROM alunos WHERE id = ?", [$aluno_id]);

// Verifica se o aluno existe
if (!$aluno) {
    // Destroi a sessão e redireciona para a página de login
    Auth::logout();
    header('Location: ../login.php?redirect=aluno&error=invalid_session');
    exit;
}

// Registra a atividade do aluno
$db->insert('alunos_atividades', [
    'aluno_id' => $aluno_id,
    'tipo' => 'acesso',
    'descricao' => 'Acesso à página: ' . $_SERVER['REQUEST_URI'],
    'ip' => $_SERVER['REMOTE_ADDR'],
    'created_at' => date('Y-m-d H:i:s')
]);

// Define o título da página
$titulo_pagina = isset($titulo_pagina) ? $titulo_pagina : 'Portal do Aluno';

// Obtém o nome da página atual
$pagina_atual = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faciência ERP - <?php echo $titulo_pagina; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="css/aluno.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Estilos específicos para a página atual */
        <?php if ($pagina_atual === 'index.php'): ?>
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow-md);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(255,255,255,0.1), rgba(255,255,255,0));
            pointer-events: none;
        }

        .stat-card.secondary {
            background: linear-gradient(135deg, var(--secondary-color), var(--secondary-dark));
        }

        .stat-card.warning {
            background: linear-gradient(135deg, var(--warning-color), #D97706);
        }

        .stat-card.info {
            background: linear-gradient(135deg, var(--info-color), #2563EB);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 0.875rem;
            opacity: 0.9;
        }

        .stat-icon {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 1.5rem;
            opacity: 0.5;
        }
        <?php endif; ?>

        <?php if ($pagina_atual === 'perfil.php'): ?>
        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: var(--shadow-md);
            margin-right: 2rem;
        }

        .profile-info h1 {
            font-size: 1.75rem;
            margin-bottom: 0.5rem;
        }

        .profile-info p {
            color: var(--text-secondary);
            margin-bottom: 1rem;
        }

        .profile-tabs {
            display: flex;
            border-bottom: 1px solid var(--neutral-200);
            margin-bottom: 2rem;
        }

        .profile-tab {
            padding: 1rem 1.5rem;
            font-weight: 500;
            color: var(--text-secondary);
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all var(--transition-fast) ease;
        }

        .profile-tab.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
        }

        .profile-tab:hover:not(.active) {
            color: var(--text-primary);
            border-bottom-color: var(--neutral-300);
        }
        <?php endif; ?>
    </style>
</head>
<body>
    <div class="app-container">
        <?php include 'sidebar.php'; ?>

        <div class="main-content" id="main-content">
            <header class="header">
                <div class="header-left">
                    <button id="mobile-toggle" class="lg:hidden p-2 rounded-full hover:bg-gray-100">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 class="text-xl font-semibold ml-2"><?php echo $titulo_pagina; ?></h1>
                </div>

                <div class="header-right flex items-center">
                    <div class="relative mr-4">
                        <button id="notifications-button" class="p-2 rounded-full hover:bg-gray-100 relative">
                            <i class="fas fa-bell"></i>
                            <?php
                            // Verifica se há notificações não lidas
                            $notificacoes_count = $db->fetchOne("SELECT COUNT(*) as total FROM notificacoes WHERE aluno_id = ? AND lida = 0", [$aluno_id]);
                            $total_notificacoes = $notificacoes_count['total'] ?? 0;

                            if ($total_notificacoes > 0):
                            ?>
                            <span class="absolute top-0 right-0 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
                                <?php echo $total_notificacoes; ?>
                            </span>
                            <?php endif; ?>
                        </button>

                        <div id="notifications-dropdown" class="hidden absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg z-50">
                            <div class="p-3 border-b border-gray-200">
                                <h3 class="font-semibold">Notificações</h3>
                            </div>
                            <div class="max-h-96 overflow-y-auto">
                                <?php
                                // Obtém as notificações do aluno
                                $notificacoes = $db->fetchAll("SELECT * FROM notificacoes WHERE aluno_id = ? ORDER BY created_at DESC LIMIT 10", [$aluno_id]);

                                if (count($notificacoes) > 0):
                                    foreach ($notificacoes as $notificacao):
                                ?>
                                <div class="p-3 border-b border-gray-100 hover:bg-gray-50 <?php echo $notificacao['lida'] ? '' : 'bg-blue-50'; ?>">
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0 mr-3">
                                            <i class="fas fa-bell text-blue-500"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium"><?php echo $notificacao['titulo']; ?></p>
                                            <p class="text-xs text-gray-500 mt-1"><?php echo $notificacao['mensagem']; ?></p>
                                            <p class="text-xs text-gray-400 mt-1"><?php echo date('d/m/Y H:i', strtotime($notificacao['created_at'])); ?></p>
                                        </div>
                                    </div>
                                </div>
                                <?php
                                    endforeach;
                                else:
                                ?>
                                <div class="p-4 text-center text-gray-500">
                                    <p>Nenhuma notificação encontrada.</p>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="p-2 border-t border-gray-200 text-center">
                                <a href="notificacoes.php" class="text-sm text-blue-600 hover:text-blue-800">Ver todas as notificações</a>
                            </div>
                        </div>
                    </div>

                    <div class="relative">
                        <button id="user-menu-button" class="flex items-center">
                            <img src="<?php echo !empty($aluno['foto_perfil']) ? $aluno['foto_perfil'] : '../assets/img/avatar-placeholder.png'; ?>" alt="Avatar" class="w-8 h-8 rounded-full mr-2">
                            <span class="hidden md:block font-medium"><?php echo $aluno['nome']; ?></span>
                            <i class="fas fa-chevron-down ml-2 text-gray-400 text-xs"></i>
                        </button>

                        <div id="user-menu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg z-50">
                            <a href="perfil.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-user mr-2"></i> Meu Perfil
                            </a>
                            <a href="configuracoes.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-cog mr-2"></i> Configurações
                            </a>
                            <div class="border-t border-gray-100"></div>
                            <a href="../logout.php?redirect=aluno" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-sign-out-alt mr-2"></i> Sair
                            </a>
                        </div>
                    </div>
                </div>
            </header>

            <main class="p-6">
                <!-- Conteúdo da página será inserido aqui -->
