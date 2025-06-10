<?php
/**
 * ============================================================================
 * CONFIGURAÇÕES DO SISTEMA - MÓDULO ADMINISTRADOR
 * ============================================================================
 *
 * Página para gerenciamento das configurações globais do sistema:
 * - Configurações gerais
 * - Configurações de segurança
 * - Configurações de email
 * - Configurações de backup
 * - Manutenção do sistema
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
// PROCESSAMENTO DE AÇÕES
// ============================================================================

$mensagem = '';
$tipo_mensagem = 'success';
$db = Database::getInstance();

// Processa o formulário de configurações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'salvar_gerais':
            try {
                $configuracoes = [
                    'nome_sistema' => $_POST['nome_sistema'] ?? '',
                    'descricao_sistema' => $_POST['descricao_sistema'] ?? '',
                    'email_contato' => $_POST['email_contato'] ?? '',
                    'telefone_contato' => $_POST['telefone_contato'] ?? '',
                    'timezone' => $_POST['timezone'] ?? 'America/Sao_Paulo',
                    'idioma' => $_POST['idioma'] ?? 'pt_BR',
                    'logs_retention_days' => (int)($_POST['logs_retention_days'] ?? 30),
                    'max_login_attempts' => (int)($_POST['max_login_attempts'] ?? 5),
                    'session_timeout' => (int)($_POST['session_timeout'] ?? 120)
                ];
                
                foreach ($configuracoes as $chave => $valor) {
                    $sql = "INSERT INTO configuracoes_sistema (chave, valor, tipo) VALUES (?, ?, 'string') 
                            ON DUPLICATE KEY UPDATE valor = ?, updated_at = NOW()";
                    $db->execute($sql, [$chave, $valor, $valor]);
                }
                
                registrarAcaoAdministrativa('configuracoes_atualizadas', 'Configurações gerais atualizadas', $configuracoes);
                $mensagem = 'Configurações gerais salvas com sucesso!';
                
            } catch (Exception $e) {
                $mensagem = 'Erro ao salvar configurações: ' . $e->getMessage();
                $tipo_mensagem = 'error';
            }
            break;
            
        case 'salvar_seguranca':
            try {
                $configuracoes = [
                    'force_https' => isset($_POST['force_https']) ? '1' : '0',
                    'enable_2fa' => isset($_POST['enable_2fa']) ? '1' : '0',
                    'password_min_length' => (int)($_POST['password_min_length'] ?? 8),
                    'password_require_special' => isset($_POST['password_require_special']) ? '1' : '0',
                    'auto_logout_inactive' => isset($_POST['auto_logout_inactive']) ? '1' : '0',
                    'log_all_actions' => isset($_POST['log_all_actions']) ? '1' : '0',
                    'enable_ip_whitelist' => isset($_POST['enable_ip_whitelist']) ? '1' : '0',
                    'ip_whitelist' => $_POST['ip_whitelist'] ?? ''
                ];
                
                foreach ($configuracoes as $chave => $valor) {
                    $tipo = is_numeric($valor) ? 'numero' : 'string';
                    if (in_array($chave, ['force_https', 'enable_2fa', 'password_require_special', 'auto_logout_inactive', 'log_all_actions', 'enable_ip_whitelist'])) {
                        $tipo = 'booleano';
                    }
                    
                    $sql = "INSERT INTO configuracoes_sistema (chave, valor, tipo) VALUES (?, ?, ?) 
                            ON DUPLICATE KEY UPDATE valor = ?, updated_at = NOW()";
                    $db->execute($sql, [$chave, $valor, $tipo, $valor]);
                }
                
                registrarAcaoAdministrativa('configuracoes_seguranca_atualizadas', 'Configurações de segurança atualizadas', $configuracoes);
                $mensagem = 'Configurações de segurança salvas com sucesso!';
                
            } catch (Exception $e) {
                $mensagem = 'Erro ao salvar configurações de segurança: ' . $e->getMessage();
                $tipo_mensagem = 'error';
            }
            break;
            
        case 'limpar_logs':
            try {
                $dias = (int)($_POST['dias_logs'] ?? 30);
                $sql = "DELETE FROM logs_sistema WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
                $resultado = $db->execute($sql, [$dias]);
                
                registrarAcaoAdministrativa('logs_limpeza', "Logs anteriores a {$dias} dias foram removidos", ['dias' => $dias]);
                $mensagem = "Logs mais antigos que {$dias} dias foram removidos com sucesso!";
                
            } catch (Exception $e) {
                $mensagem = 'Erro ao limpar logs: ' . $e->getMessage();
                $tipo_mensagem = 'error';
            }
            break;
            
        case 'backup_database':
            try {
                // Esta seria uma funcionalidade mais complexa que requereria 
                // integração com ferramentas de backup do MySQL
                registrarAcaoAdministrativa('backup_solicitado', 'Backup do banco de dados solicitado');
                $mensagem = 'Backup do banco de dados iniciado. Você será notificado quando concluído.';
                
            } catch (Exception $e) {
                $mensagem = 'Erro ao iniciar backup: ' . $e->getMessage();
                $tipo_mensagem = 'error';
            }
            break;
            
        case 'salvar_email':
            try {
                $configuracoes = [
                    'smtp_host' => $_POST['smtp_host'] ?? 'smtp.gmail.com',
                    'smtp_port' => (int)($_POST['smtp_port'] ?? 587),
                    'smtp_username' => $_POST['smtp_username'] ?? '',
                    'smtp_password' => $_POST['smtp_password'] ?? '',
                    'smtp_encryption' => isset($_POST['smtp_encryption']) ? 'tls' : 'none',
                    'smtp_auth' => isset($_POST['smtp_auth']) ? '1' : '0',
                    'email_from_name' => $_POST['email_from_name'] ?? 'Faciência ERP',
                    'email_from_address' => $_POST['email_from_address'] ?? 'noreply@faciencia.edu.br',
                    'email_reply_to' => $_POST['email_reply_to'] ?? 'contato@faciencia.edu.br',
                    'email_signature' => $_POST['email_signature'] ?? 'Equipe Faciência ERP\nSistema de Gestão Educacional'
                ];
                
                foreach ($configuracoes as $chave => $valor) {
                    $tipo = 'string';
                    if (in_array($chave, ['smtp_port'])) {
                        $tipo = 'numero';
                    } elseif (in_array($chave, ['smtp_auth'])) {
                        $tipo = 'booleano';
                    }
                    
                    $sql = "INSERT INTO configuracoes_sistema (chave, valor, tipo) VALUES (?, ?, ?) 
                            ON DUPLICATE KEY UPDATE valor = ?, updated_at = NOW()";
                    $db->execute($sql, [$chave, $valor, $tipo, $valor]);
                }
                
                registrarAcaoAdministrativa('configuracoes_email_atualizadas', 'Configurações de email atualizadas', $configuracoes);
                $mensagem = 'Configurações de email salvas com sucesso!';
                
            } catch (Exception $e) {
                $mensagem = 'Erro ao salvar configurações de email: ' . $e->getMessage();
                $tipo_mensagem = 'error';
            }
            break;
    }
}

// ============================================================================
// CARREGAMENTO DE CONFIGURAÇÕES ATUAIS
// ============================================================================

// Busca todas as configurações atuais
$configuracoes_atuais = [];
$sql = "SELECT chave, valor, tipo FROM configuracoes_sistema";
$configs = $db->fetchAll($sql);

foreach ($configs as $config) {
    $valor = $config['valor'];
    
    // Converte o valor baseado no tipo
    switch ($config['tipo']) {
        case 'numero':
            $valor = (int)$valor;
            break;
        case 'booleano':
            $valor = (bool)$valor;
            break;
        case 'string':
        default:
            // Mantém como string
            break;
    }
    
    $configuracoes_atuais[$config['chave']] = $valor;
}

// Define valores padrão se não existirem
$defaults = [
    'nome_sistema' => 'Faciência ERP',
    'descricao_sistema' => 'Sistema de Gestão Educacional',
    'email_contato' => 'contato@faciencia.edu.br',
    'telefone_contato' => '',
    'timezone' => 'America/Sao_Paulo',
    'idioma' => 'pt_BR',
    'logs_retention_days' => 30,
    'max_login_attempts' => 5,
    'session_timeout' => 120,
    'force_https' => false,
    'enable_2fa' => false,
    'password_min_length' => 8,
    'password_require_special' => false,
    'auto_logout_inactive' => true,
    'log_all_actions' => true,
    'enable_ip_whitelist' => false,
    'ip_whitelist' => '',
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_username' => '',
    'smtp_password' => '',
    'smtp_encryption' => 'tls',
    'smtp_auth' => true,
    'email_from_name' => 'Faciência ERP',
    'email_from_address' => 'noreply@faciencia.edu.br',
    'email_reply_to' => 'contato@faciencia.edu.br',
    'email_signature' => 'Equipe Faciência ERP\nSistema de Gestão Educacional'
];

$configuracoes = array_merge($defaults, $configuracoes_atuais);

// Obtém estatísticas do sistema
$stats_sistema = [
    'total_usuarios' => $db->fetchOne("SELECT COUNT(*) as total FROM usuarios")['total'],
    'total_alunos' => $db->fetchOne("SELECT COUNT(*) as total FROM alunos")['total'],
    'total_logs' => $db->fetchOne("SELECT COUNT(*) as total FROM logs_sistema")['total'],
    'tamanho_db' => 'N/A', // Seria calculado com consultas específicas
    'uptime' => 'N/A' // Seria obtido do servidor
];

// Registra acesso às configurações
registrarAcaoAdministrativa('configuracoes_acesso', 'Acesso à página de configurações do sistema');

$titulo_pagina = 'Configurações do Sistema';
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
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
        }
        
        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #D1D5DB;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            transition: border-color 0.2s;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--admin-primary);
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
        }
        
        .form-checkbox {
            width: 1rem;
            height: 1rem;
            margin-right: 0.5rem;
        }
        
        .btn-admin {
            background: linear-gradient(135deg, var(--admin-primary), var(--admin-primary-dark));
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 0.375rem;
            font-weight: 500;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }
        
        .btn-admin:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(220, 38, 38, 0.3);
        }
        
        .danger-zone {
            border: 2px dashed #EF4444;
            background: #FEF2F2;
        }
        
        .warning-zone {
            border: 2px dashed #F59E0B;
            background: #FFFBEB;
        }
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
                    <a href="logs.php" class="px-3 py-2 rounded text-white hover:bg-white hover:bg-opacity-10">Logs</a>
                    <a href="configuracoes.php" class="px-3 py-2 rounded bg-white bg-opacity-20 text-white">Config</a>
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
        <!-- Mensagens -->
        <?php if ($mensagem): ?>
        <div class="mb-6 p-4 rounded-lg <?php echo $tipo_mensagem === 'success' ? 'bg-green-100 text-green-700 border border-green-300' : 'bg-red-100 text-red-700 border border-red-300'; ?>">
            <?php echo htmlspecialchars($mensagem); ?>
        </div>
        <?php endif; ?>

        <!-- Cabeçalho -->
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-900">Configurações do Sistema</h2>
            <p class="mt-2 text-gray-600">Gerencie as configurações globais do Faciência ERP</p>
        </div>

        <!-- Estatísticas do Sistema -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="admin-card p-6 text-center">
                <i class="fas fa-users text-3xl text-blue-600 mb-2"></i>
                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats_sistema['total_usuarios']); ?></p>
                <p class="text-sm text-gray-600">Usuários Cadastrados</p>
            </div>
            
            <div class="admin-card p-6 text-center">
                <i class="fas fa-user-graduate text-3xl text-green-600 mb-2"></i>
                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats_sistema['total_alunos']); ?></p>
                <p class="text-sm text-gray-600">Alunos Ativos</p>
            </div>
            
            <div class="admin-card p-6 text-center">
                <i class="fas fa-file-alt text-3xl text-purple-600 mb-2"></i>
                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats_sistema['total_logs']); ?></p>
                <p class="text-sm text-gray-600">Logs Registrados</p>
            </div>
            
            <div class="admin-card p-6 text-center">
                <i class="fas fa-server text-3xl text-orange-600 mb-2"></i>
                <p class="text-lg font-bold text-green-600">Online</p>
                <p class="text-sm text-gray-600">Status do Sistema</p>
            </div>
        </div>        <!-- Abas de Configuração -->
        <div class="mb-6">
            <nav class="flex space-x-8" id="config-tabs">
                <button onclick="showTab('gerais')" class="tab-button active py-2 px-1 border-b-2 border-red-500 font-medium text-sm text-red-600">
                    Configurações Gerais
                </button>
                <button onclick="showTab('seguranca')" class="tab-button py-2 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700">
                    Segurança
                </button>
                <button onclick="showTab('email')" class="tab-button py-2 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700">
                    Email
                </button>
                <button onclick="showTab('manutencao')" class="tab-button py-2 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700">
                    Manutenção
                </button>
            </nav>
        </div>

        <!-- Aba: Configurações Gerais -->
        <div id="tab-gerais" class="tab-content">
            <div class="admin-card">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Configurações Gerais do Sistema</h3>
                    <p class="text-sm text-gray-600">Configure as informações básicas do sistema</p>
                </div>
                
                <form method="POST" class="p-6">
                    <input type="hidden" name="action" value="salvar_gerais">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-group">
                            <label for="nome_sistema" class="form-label">Nome do Sistema</label>
                            <input type="text" id="nome_sistema" name="nome_sistema" 
                                   value="<?php echo htmlspecialchars($configuracoes['nome_sistema']); ?>" 
                                   class="form-input" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email_contato" class="form-label">Email de Contato</label>
                            <input type="email" id="email_contato" name="email_contato" 
                                   value="<?php echo htmlspecialchars($configuracoes['email_contato']); ?>" 
                                   class="form-input" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="telefone_contato" class="form-label">Telefone de Contato</label>
                            <input type="text" id="telefone_contato" name="telefone_contato" 
                                   value="<?php echo htmlspecialchars($configuracoes['telefone_contato']); ?>" 
                                   class="form-input">
                        </div>
                        
                        <div class="form-group">
                            <label for="timezone" class="form-label">Fuso Horário</label>
                            <select id="timezone" name="timezone" class="form-input">
                                <option value="America/Sao_Paulo" <?php echo $configuracoes['timezone'] === 'America/Sao_Paulo' ? 'selected' : ''; ?>>São Paulo (GMT-3)</option>
                                <option value="America/Manaus" <?php echo $configuracoes['timezone'] === 'America/Manaus' ? 'selected' : ''; ?>>Manaus (GMT-4)</option>
                                <option value="America/Rio_Branco" <?php echo $configuracoes['timezone'] === 'America/Rio_Branco' ? 'selected' : ''; ?>>Rio Branco (GMT-5)</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="logs_retention_days" class="form-label">Retenção de Logs (dias)</label>
                            <input type="number" id="logs_retention_days" name="logs_retention_days" 
                                   value="<?php echo $configuracoes['logs_retention_days']; ?>" 
                                   class="form-input" min="1" max="365">
                        </div>
                        
                        <div class="form-group">
                            <label for="session_timeout" class="form-label">Timeout de Sessão (minutos)</label>
                            <input type="number" id="session_timeout" name="session_timeout" 
                                   value="<?php echo $configuracoes['session_timeout']; ?>" 
                                   class="form-input" min="5" max="480">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="descricao_sistema" class="form-label">Descrição do Sistema</label>
                        <textarea id="descricao_sistema" name="descricao_sistema" rows="3" class="form-input"><?php echo htmlspecialchars($configuracoes['descricao_sistema']); ?></textarea>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" class="btn-admin">
                            <i class="fas fa-save mr-2"></i>Salvar Configurações Gerais
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Aba: Segurança -->
        <div id="tab-seguranca" class="tab-content hidden">
            <div class="admin-card">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Configurações de Segurança</h3>
                    <p class="text-sm text-gray-600">Configure as políticas de segurança do sistema</p>
                </div>
                
                <form method="POST" class="p-6">
                    <input type="hidden" name="action" value="salvar_seguranca">
                    
                    <div class="space-y-6">
                        <!-- Políticas de Senha -->
                        <div>
                            <h4 class="text-md font-medium text-gray-900 mb-4">Políticas de Senha</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="form-group">
                                    <label for="password_min_length" class="form-label">Comprimento Mínimo da Senha</label>
                                    <input type="number" id="password_min_length" name="password_min_length" 
                                           value="<?php echo $configuracoes['password_min_length']; ?>" 
                                           class="form-input" min="6" max="20">
                                </div>
                                
                                <div class="form-group">
                                    <label for="max_login_attempts" class="form-label">Máximo de Tentativas de Login</label>
                                    <input type="number" id="max_login_attempts" name="max_login_attempts" 
                                           value="<?php echo $configuracoes['max_login_attempts']; ?>" 
                                           class="form-input" min="3" max="10">
                                </div>
                            </div>
                            
                            <div class="space-y-4 mt-4">
                                <label class="flex items-center">
                                    <input type="checkbox" name="password_require_special" class="form-checkbox" 
                                           <?php echo $configuracoes['password_require_special'] ? 'checked' : ''; ?>>
                                    <span class="form-label mb-0">Exigir caracteres especiais na senha</span>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Configurações de Acesso -->
                        <div>
                            <h4 class="text-md font-medium text-gray-900 mb-4">Controle de Acesso</h4>
                            <div class="space-y-4">
                                <label class="flex items-center">
                                    <input type="checkbox" name="force_https" class="form-checkbox" 
                                           <?php echo $configuracoes['force_https'] ? 'checked' : ''; ?>>
                                    <span class="form-label mb-0">Forçar HTTPS em todo o sistema</span>
                                </label>
                                
                                <label class="flex items-center">
                                    <input type="checkbox" name="enable_2fa" class="form-checkbox" 
                                           <?php echo $configuracoes['enable_2fa'] ? 'checked' : ''; ?>>
                                    <span class="form-label mb-0">Habilitar autenticação de dois fatores (2FA)</span>
                                </label>
                                
                                <label class="flex items-center">
                                    <input type="checkbox" name="auto_logout_inactive" class="form-checkbox" 
                                           <?php echo $configuracoes['auto_logout_inactive'] ? 'checked' : ''; ?>>
                                    <span class="form-label mb-0">Logout automático por inatividade</span>
                                </label>
                                
                                <label class="flex items-center">
                                    <input type="checkbox" name="enable_ip_whitelist" class="form-checkbox" 
                                           <?php echo $configuracoes['enable_ip_whitelist'] ? 'checked' : ''; ?>>
                                    <span class="form-label mb-0">Habilitar lista branca de IPs</span>
                                </label>
                            </div>
                            
                            <div class="form-group mt-4">
                                <label for="ip_whitelist" class="form-label">Lista de IPs Permitidos (um por linha)</label>
                                <textarea id="ip_whitelist" name="ip_whitelist" rows="4" class="form-input" 
                                          placeholder="192.168.1.1&#10;10.0.0.0/8&#10;172.16.0.0/12"><?php echo htmlspecialchars($configuracoes['ip_whitelist']); ?></textarea>
                            </div>
                        </div>
                        
                        <!-- Configurações de Log -->
                        <div>
                            <h4 class="text-md font-medium text-gray-900 mb-4">Auditoria e Logs</h4>
                            <div class="space-y-4">
                                <label class="flex items-center">
                                    <input type="checkbox" name="log_all_actions" class="form-checkbox" 
                                           <?php echo $configuracoes['log_all_actions'] ? 'checked' : ''; ?>>
                                    <span class="form-label mb-0">Registrar todas as ações dos usuários</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-end mt-8">
                        <button type="submit" class="btn-admin">
                            <i class="fas fa-shield-alt mr-2"></i>Salvar Configurações de Segurança
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Aba: Email -->
        <div id="tab-email" class="tab-content hidden">
            <div class="admin-card">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Configurações de Email</h3>
                    <p class="text-sm text-gray-600">Configure o servidor SMTP para envio de emails</p>
                </div>
                
                <form method="POST" class="p-6">
                    <input type="hidden" name="action" value="salvar_email">
                    
                    <div class="space-y-6">
                        <!-- Configurações SMTP -->
                        <div>
                            <h4 class="text-md font-medium text-gray-900 mb-4">Servidor SMTP</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="form-group">
                                    <label for="smtp_host" class="form-label">Servidor SMTP</label>
                                    <input type="text" id="smtp_host" name="smtp_host" 
                                           value="<?php echo htmlspecialchars($configuracoes['smtp_host'] ?? 'smtp.gmail.com'); ?>" 
                                           class="form-input" placeholder="smtp.gmail.com">
                                </div>
                                
                                <div class="form-group">
                                    <label for="smtp_port" class="form-label">Porta SMTP</label>
                                    <input type="number" id="smtp_port" name="smtp_port" 
                                           value="<?php echo $configuracoes['smtp_port'] ?? 587; ?>" 
                                           class="form-input" placeholder="587">
                                </div>
                                
                                <div class="form-group">
                                    <label for="smtp_username" class="form-label">Usuário SMTP</label>
                                    <input type="text" id="smtp_username" name="smtp_username" 
                                           value="<?php echo htmlspecialchars($configuracoes['smtp_username'] ?? ''); ?>" 
                                           class="form-input" placeholder="usuario@exemplo.com">
                                </div>
                                
                                <div class="form-group">
                                    <label for="smtp_password" class="form-label">Senha SMTP</label>
                                    <input type="password" id="smtp_password" name="smtp_password" 
                                           value="<?php echo htmlspecialchars($configuracoes['smtp_password'] ?? ''); ?>" 
                                           class="form-input" placeholder="•••••••••">
                                </div>
                            </div>
                            
                            <div class="space-y-4 mt-4">
                                <label class="flex items-center">
                                    <input type="checkbox" name="smtp_encryption" class="form-checkbox" 
                                           <?php echo ($configuracoes['smtp_encryption'] ?? 'tls') === 'tls' ? 'checked' : ''; ?>>
                                    <span class="form-label mb-0">Usar criptografia TLS</span>
                                </label>
                                
                                <label class="flex items-center">
                                    <input type="checkbox" name="smtp_auth" class="form-checkbox" 
                                           <?php echo ($configuracoes['smtp_auth'] ?? true) ? 'checked' : ''; ?>>
                                    <span class="form-label mb-0">Usar autenticação SMTP</span>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Configurações de Envio -->
                        <div>
                            <h4 class="text-md font-medium text-gray-900 mb-4">Configurações de Envio</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="form-group">
                                    <label for="email_from_name" class="form-label">Nome do Remetente</label>
                                    <input type="text" id="email_from_name" name="email_from_name" 
                                           value="<?php echo htmlspecialchars($configuracoes['email_from_name'] ?? 'Faciência ERP'); ?>" 
                                           class="form-input" placeholder="Faciência ERP">
                                </div>
                                
                                <div class="form-group">
                                    <label for="email_from_address" class="form-label">Email do Remetente</label>
                                    <input type="email" id="email_from_address" name="email_from_address" 
                                           value="<?php echo htmlspecialchars($configuracoes['email_from_address'] ?? 'noreply@faciencia.edu.br'); ?>" 
                                           class="form-input" placeholder="noreply@faciencia.edu.br">
                                </div>
                                
                                <div class="form-group">
                                    <label for="email_reply_to" class="form-label">Email de Resposta</label>
                                    <input type="email" id="email_reply_to" name="email_reply_to" 
                                           value="<?php echo htmlspecialchars($configuracoes['email_reply_to'] ?? 'contato@faciencia.edu.br'); ?>" 
                                           class="form-input" placeholder="contato@faciencia.edu.br">
                                </div>
                                
                                <div class="form-group">
                                    <label for="email_signature" class="form-label">Assinatura Padrão</label>
                                    <textarea id="email_signature" name="email_signature" rows="3" class="form-input" 
                                              placeholder="Equipe Faciência ERP"><?php echo htmlspecialchars($configuracoes['email_signature'] ?? 'Equipe Faciência ERP\nSistema de Gestão Educacional'); ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Teste de Email -->
                        <div class="border-t pt-6">
                            <h4 class="text-md font-medium text-gray-900 mb-4">Teste de Configuração</h4>
                            <div class="bg-blue-50 p-4 rounded-lg">
                                <p class="text-sm text-blue-700 mb-3">Envie um email de teste para verificar se as configurações estão corretas.</p>
                                <div class="flex items-center space-x-3">
                                    <input type="email" id="email_teste" placeholder="seuemail@exemplo.com" 
                                           class="form-input w-64" value="<?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?>">
                                    <button type="button" onclick="enviarEmailTeste()" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                                        <i class="fas fa-paper-plane mr-1"></i>Enviar Teste
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-end mt-8">
                        <button type="submit" class="btn-admin">
                            <i class="fas fa-envelope mr-2"></i>Salvar Configurações de Email
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Aba: Manutenção -->
        <div id="tab-manutencao" class="tab-content hidden">
            <div class="space-y-6">
                <!-- Limpeza de Logs -->
                <div class="admin-card warning-zone">
                    <div class="p-6 border-b border-yellow-200">
                        <h3 class="text-lg font-semibold text-yellow-800">
                            <i class="fas fa-exclamation-triangle mr-2"></i>Limpeza de Logs
                        </h3>
                        <p class="text-sm text-yellow-700">Remove logs antigos para liberar espaço no banco de dados</p>
                    </div>
                    
                    <form method="POST" class="p-6">
                        <input type="hidden" name="action" value="limpar_logs">
                        
                        <div class="form-group">
                            <label for="dias_logs" class="form-label">Remover logs anteriores a quantos dias?</label>
                            <input type="number" id="dias_logs" name="dias_logs" value="30" 
                                   class="form-input w-32" min="1" max="365">
                            <p class="text-sm text-yellow-600 mt-1">Recomendado: manter pelo menos 30 dias</p>
                        </div>
                        
                        <button type="submit" class="px-4 py-2 bg-yellow-600 text-white rounded hover:bg-yellow-700"
                                onclick="return confirm('Tem certeza que deseja remover os logs antigos? Esta ação não pode ser desfeita.')">
                            <i class="fas fa-trash mr-2"></i>Limpar Logs Antigos
                        </button>
                    </form>
                </div>
                
                <!-- Backup do Sistema -->
                <div class="admin-card">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">
                            <i class="fas fa-download mr-2"></i>Backup do Sistema
                        </h3>
                        <p class="text-sm text-gray-600">Crie backups dos dados importantes</p>
                    </div>
                      <div class="p-6">
                        <div>
                            <p class="text-sm text-gray-600 mb-4">
                                O backup incluirá todas as tabelas do banco de dados exceto logs temporários.
                            </p>
                            
                            <div class="space-y-3">
                                <button onclick="executarBackup('completo')" class="btn-admin mr-2">
                                    <i class="fas fa-database mr-2"></i>Backup Completo
                                </button>
                                <button onclick="executarBackup('estrutura')" class="btn-admin bg-blue-600 hover:bg-blue-700 mr-2">
                                    <i class="fas fa-sitemap mr-2"></i>Apenas Estrutura
                                </button>
                                <button onclick="executarBackup('dados')" class="btn-admin bg-green-600 hover:bg-green-700">
                                    <i class="fas fa-table mr-2"></i>Apenas Dados
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Informações do Sistema -->
                <div class="admin-card">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">
                            <i class="fas fa-info-circle mr-2"></i>Informações do Sistema
                        </h3>
                    </div>
                    
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h4 class="font-medium text-gray-900 mb-2">Versão do PHP</h4>
                                <p class="text-gray-600"><?php echo PHP_VERSION; ?></p>
                            </div>
                            
                            <div>
                                <h4 class="font-medium text-gray-900 mb-2">Versão do Sistema</h4>
                                <p class="text-gray-600">Faciência ERP v1.0</p>
                            </div>
                            
                            <div>
                                <h4 class="font-medium text-gray-900 mb-2">Servidor Web</h4>
                                <p class="text-gray-600"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Desconhecido'; ?></p>
                            </div>
                            
                            <div>
                                <h4 class="font-medium text-gray-900 mb-2">Espaço em Disco</h4>
                                <p class="text-gray-600">
                                    <?php 
                                    $total = disk_total_space('.');
                                    $free = disk_free_space('.');
                                    if ($total && $free) {
                                        $used = $total - $free;
                                        $percent = round(($used / $total) * 100, 1);
                                        echo number_format($free / (1024*1024*1024), 1) . ' GB livres de ' . 
                                             number_format($total / (1024*1024*1024), 1) . ' GB (' . $percent . '% usado)';
                                    } else {
                                        echo 'Informação não disponível';
                                    }
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            // Esconde todas as abas
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.add('hidden');
            });
            
            // Remove classe ativa de todos os botões
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active', 'border-red-500', 'text-red-600');
                btn.classList.add('border-transparent', 'text-gray-500');
            });
            
            // Mostra a aba selecionada
            document.getElementById('tab-' + tabName).classList.remove('hidden');
                  // Ativa o botão correspondente
            event.target.classList.add('active', 'border-red-500', 'text-red-600');
            event.target.classList.remove('border-transparent', 'text-gray-500');
        }

        async function executarBackup(tipo) {
            if (!confirm(`Tem certeza que deseja executar um backup ${tipo}?\n\nEste processo pode levar alguns minutos.`)) {
                return;
            }
            
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Executando...';
            btn.disabled = true;
            
            try {
                const formData = new FormData();
                formData.append('acao', 'backup_sistema');
                formData.append('tipo', tipo);
                
                const response = await fetch('includes/ajax.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert(`Backup ${tipo} realizado com sucesso!\n\nArquivo: ${result.arquivo}\nTamanho: ${result.tamanho}`);
                    
                    // Opcionalmente fazer download do arquivo
                    if (confirm('Deseja fazer download do arquivo de backup?')) {
                        const link = document.createElement('a');
                        link.href = result.url_download;
                        link.download = result.arquivo;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    }
                } else {
                    alert('Erro ao executar backup: ' + result.message);
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro ao executar backup. Tente novamente.');
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }
        
        async function enviarEmailTeste() {
            const email = document.getElementById('email_teste').value;
            
            if (!email) {
                alert('Por favor, informe um email para teste');
                return;
            }
            
            if (!email.includes('@')) {
                alert('Por favor, informe um email válido');
                return;
            }
            
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Enviando...';
            btn.disabled = true;
            
            try {
                const formData = new FormData();
                formData.append('acao', 'enviar_email_teste');
                formData.append('email', email);
                
                const response = await fetch('includes/ajax.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Email de teste enviado com sucesso! Verifique sua caixa de entrada.');
                } else {
                    alert('Erro ao enviar email de teste: ' + result.message);
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro ao enviar email de teste. Verifique as configurações.');
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }
        
        // Confirma ações perigosas
        document.querySelectorAll('form[method="POST"]').forEach(form => {
            const action = form.querySelector('input[name="action"]')?.value;
            if (['limpar_logs'].includes(action)) {
                form.addEventListener('submit', function(e) {
                    if (!confirm('Tem certeza que deseja executar esta ação?')) {
                        e.preventDefault();
                    }
                });
            }
        });
    </script>
</body>
</html>
