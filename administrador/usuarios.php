<?php
/**
 * ============================================================================
 * GERENCIAMENTO DE USUÁRIOS - MÓDULO ADMINISTRADOR
 * ============================================================================
 *
 * Página para gerenciamento completo de usuários do sistema:
 * - Listagem de usuários
 * - Criação de novos usuários
 * - Edição de usuários existentes
 * - Controle de status e permissões
 * - Histórico de ações dos usuários
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

$action = $_GET['action'] ?? 'listar';
$user_id = $_GET['id'] ?? null;
$mensagem = '';
$tipo_mensagem = 'success';

// Instância do banco de dados
$db = Database::getInstance();

// Processa as ações
switch ($action) {
    case 'ativar':
        if ($user_id) {
            try {
                $db->update('usuarios', ['status' => 'ativo'], 'id = ?', [$user_id]);
                registrarAcaoAdministrativa('usuario_ativado', "Usuário ID {$user_id} foi ativado", ['user_id' => $user_id]);
                $mensagem = 'Usuário ativado com sucesso!';
            } catch (Exception $e) {
                $mensagem = 'Erro ao ativar usuário: ' . $e->getMessage();
                $tipo_mensagem = 'error';
            }
        }
        break;
        
    case 'desativar':
        if ($user_id) {
            try {
                $db->update('usuarios', ['status' => 'inativo'], 'id = ?', [$user_id]);
                registrarAcaoAdministrativa('usuario_desativado', "Usuário ID {$user_id} foi desativado", ['user_id' => $user_id]);
                $mensagem = 'Usuário desativado com sucesso!';
            } catch (Exception $e) {
                $mensagem = 'Erro ao desativar usuário: ' . $e->getMessage();
                $tipo_mensagem = 'error';
            }
        }
        break;
        
    case 'bloquear':
        if ($user_id) {
            try {
                $db->update('usuarios', ['status' => 'bloqueado'], 'id = ?', [$user_id]);
                registrarAcaoAdministrativa('usuario_bloqueado', "Usuário ID {$user_id} foi bloqueado", ['user_id' => $user_id]);
                $mensagem = 'Usuário bloqueado com sucesso!';
            } catch (Exception $e) {
                $mensagem = 'Erro ao bloquear usuário: ' . $e->getMessage();
                $tipo_mensagem = 'error';
            }
        }
        break;
        
    case 'resetar_senha':
        if ($user_id) {
            try {
                $nova_senha = '123456'; // Senha padrão temporária
                $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                
                $db->update('usuarios', ['senha' => $senha_hash], 'id = ?', [$user_id]);
                registrarAcaoAdministrativa('senha_resetada', "Senha do usuário ID {$user_id} foi resetada", ['user_id' => $user_id]);
                $mensagem = "Senha resetada com sucesso! Nova senha temporária: {$nova_senha}";
            } catch (Exception $e) {
                $mensagem = 'Erro ao resetar senha: ' . $e->getMessage();
                $tipo_mensagem = 'error';
            }
        }
        break;
}

// ============================================================================
// CARREGAMENTO DE DADOS
// ============================================================================

// Parâmetros de filtro e paginação
$filtro_tipo = $_GET['filtro_tipo'] ?? '';
$filtro_status = $_GET['filtro_status'] ?? '';
$filtro_busca = $_GET['busca'] ?? '';
$pagina_atual = (int)($_GET['pagina'] ?? 1);
$itens_por_pagina = 20;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

// Constrói a query com filtros
$where = [];
$params = [];

if ($filtro_tipo) {
    $where[] = "u.tipo = ?";
    $params[] = $filtro_tipo;
}

if ($filtro_status) {
    $where[] = "u.status = ?";
    $params[] = $filtro_status;
}

if ($filtro_busca) {
    $where[] = "(u.nome LIKE ? OR u.email LIKE ? OR u.cpf LIKE ?)";
    $params[] = "%{$filtro_busca}%";
    $params[] = "%{$filtro_busca}%";
    $params[] = "%{$filtro_busca}%";
}

$where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Conta o total de registros
$sql_count = "SELECT COUNT(*) as total FROM usuarios u {$where_clause}";
$total_registros = $db->fetchOne($sql_count, $params)['total'] ?? 0;
$total_paginas = ceil($total_registros / $itens_por_pagina);

// Busca os usuários
$sql = "SELECT u.*
        FROM usuarios u
        {$where_clause}
        ORDER BY u.created_at DESC
        LIMIT ? OFFSET ?";

$params[] = $itens_por_pagina;
$params[] = $offset;

$usuarios = $db->fetchAll($sql, $params);

// Adiciona dados de atividade para os usuários da página atual (mais eficiente)
foreach ($usuarios as &$usuario) {
    $usuario['atividade_30d'] = 0; // Valor padrão
    $usuario['ultimo_login'] = null; // Valor padrão
    
    // Busca atividade dos últimos 30 dias (apenas se necessário)
    try {
        $atividade = $db->fetchOne("SELECT COUNT(*) as total FROM logs_sistema WHERE usuario_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)", [$usuario['id']]);
        $usuario['atividade_30d'] = $atividade['total'] ?? 0;
        
        // Busca último login
        $ultimo_login = $db->fetchOne("SELECT MAX(created_at) as ultimo FROM logs_sistema WHERE usuario_id = ? AND acao = 'login'", [$usuario['id']]);
        $usuario['ultimo_login'] = $ultimo_login['ultimo'] ?? null;
    } catch (Exception $e) {
        // Em caso de erro, mantém valores padrão
        error_log("Erro ao buscar atividade do usuário {$usuario['id']}: " . $e->getMessage());
    }
}

// Obtém estatísticas
$stats_usuarios = $db->fetchAll("SELECT tipo, status, COUNT(*) as total FROM usuarios GROUP BY tipo, status");

// Registra acesso à página
registrarAcaoAdministrativa('usuarios_listagem', 'Acesso à listagem de usuários', [
    'filtros' => [
        'tipo' => $filtro_tipo,
        'status' => $filtro_status,
        'busca' => $filtro_busca
    ],
    'pagina' => $pagina_atual
]);

$titulo_pagina = 'Gerenciamento de Usuários';
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
        
        .btn-admin {
            background: linear-gradient(135deg, var(--admin-primary), var(--admin-primary-dark));
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .btn-admin:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(220, 38, 38, 0.3);
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .status-ativo { background: #D1FAE5; color: #065F46; }
        .status-inativo { background: #FEE2E2; color: #991B1B; }
        .status-bloqueado { background: #FEF3C7; color: #92400E; }
        
        .tipo-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .tipo-admin_master { background: #FEE2E2; color: #991B1B; }
        .tipo-diretoria { background: #EDE9FE; color: #5B21B6; }
        .tipo-secretaria_academica { background: #DBEAFE; color: #1E40AF; }
        .tipo-financeiro { background: #D1FAE5; color: #065F46; }
        .tipo-polo { background: #FED7AA; color: #9A3412; }
        .tipo-professor { background: #E0E7FF; color: #3730A3; }
        .tipo-aluno { background: #FEF3C7; color: #92400E; }
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
                    <a href="usuarios.php" class="px-3 py-2 rounded bg-white bg-opacity-20 text-white">Usuários</a>
                    <a href="logs.php" class="px-3 py-2 rounded text-white hover:bg-white hover:bg-opacity-10">Logs</a>
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
        <!-- Mensagens -->
        <?php if ($mensagem): ?>
        <div class="mb-6 p-4 rounded-lg <?php echo $tipo_mensagem === 'success' ? 'bg-green-100 text-green-700 border border-green-300' : 'bg-red-100 text-red-700 border border-red-300'; ?>">
            <?php echo htmlspecialchars($mensagem); ?>
        </div>
        <?php endif; ?>

        <!-- Cabeçalho -->
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-900">Gerenciamento de Usuários</h2>
            <p class="mt-2 text-gray-600">Controle total sobre usuários do sistema</p>
        </div>

        <!-- Estatísticas Rápidas -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <?php
            $stats_totais = ['ativo' => 0, 'inativo' => 0, 'bloqueado' => 0, 'total' => 0];
            foreach ($stats_usuarios as $stat) {
                $stats_totais[$stat['status']] += $stat['total'];
                $stats_totais['total'] += $stat['total'];
            }
            ?>
            <div class="admin-card p-4 text-center">
                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats_totais['total']); ?></p>
                <p class="text-sm text-gray-600">Total de Usuários</p>
            </div>
            <div class="admin-card p-4 text-center">
                <p class="text-2xl font-bold text-green-600"><?php echo number_format($stats_totais['ativo']); ?></p>
                <p class="text-sm text-gray-600">Ativos</p>
            </div>
            <div class="admin-card p-4 text-center">
                <p class="text-2xl font-bold text-red-600"><?php echo number_format($stats_totais['inativo']); ?></p>
                <p class="text-sm text-gray-600">Inativos</p>
            </div>
            <div class="admin-card p-4 text-center">
                <p class="text-2xl font-bold text-yellow-600"><?php echo number_format($stats_totais['bloqueado']); ?></p>
                <p class="text-sm text-gray-600">Bloqueados</p>
            </div>
        </div>

        <!-- Filtros e Busca -->
        <div class="admin-card mb-6">
            <div class="p-6">
                <form method="GET" action="usuarios.php" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                        <input type="text" name="busca" value="<?php echo htmlspecialchars($filtro_busca); ?>" 
                               placeholder="Nome, email ou CPF..." 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                        <select name="filtro_tipo" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500">
                            <option value="">Todos os tipos</option>
                            <option value="admin_master" <?php echo $filtro_tipo === 'admin_master' ? 'selected' : ''; ?>>Admin Master</option>
                            <option value="diretoria" <?php echo $filtro_tipo === 'diretoria' ? 'selected' : ''; ?>>Diretoria</option>
                            <option value="secretaria_academica" <?php echo $filtro_tipo === 'secretaria_academica' ? 'selected' : ''; ?>>Secretaria Acadêmica</option>
                            <option value="financeiro" <?php echo $filtro_tipo === 'financeiro' ? 'selected' : ''; ?>>Financeiro</option>
                            <option value="polo" <?php echo $filtro_tipo === 'polo' ? 'selected' : ''; ?>>Polo</option>
                            <option value="professor" <?php echo $filtro_tipo === 'professor' ? 'selected' : ''; ?>>Professor</option>
                            <option value="aluno" <?php echo $filtro_tipo === 'aluno' ? 'selected' : ''; ?>>Aluno</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="filtro_status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500">
                            <option value="">Todos os status</option>
                            <option value="ativo" <?php echo $filtro_status === 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                            <option value="inativo" <?php echo $filtro_status === 'inativo' ? 'selected' : ''; ?>>Inativo</option>
                            <option value="bloqueado" <?php echo $filtro_status === 'bloqueado' ? 'selected' : ''; ?>>Bloqueado</option>
                        </select>
                    </div>
                    
                    <div class="flex items-end">
                        <button type="submit" class="btn-admin mr-2">
                            <i class="fas fa-search mr-2"></i>Filtrar
                        </button>
                        <a href="usuarios.php" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
                            <i class="fas fa-times mr-2"></i>Limpar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Lista de Usuários -->
        <div class="admin-card">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-900">
                        Lista de Usuários (<?php echo number_format($total_registros); ?> encontrados)
                    </h3>
                    <button class="btn-admin" onclick="abrirModalNovoUsuario()">
                        <i class="fas fa-plus mr-2"></i>Novo Usuário
                    </button>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuário</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Último Login</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Atividade</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($usuarios)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                Nenhum usuário encontrado com os filtros aplicados.
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($usuarios as $usuario): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                            <i class="fas fa-user text-gray-600"></i>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($usuario['nome']); ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            <?php echo htmlspecialchars($usuario['email']); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="tipo-badge tipo-<?php echo $usuario['tipo']; ?>">
                                    <?php
                                    $tipos_labels = [
                                        'admin_master' => 'Admin Master',
                                        'diretoria' => 'Diretoria',
                                        'secretaria_academica' => 'Secretaria',
                                        'financeiro' => 'Financeiro',
                                        'polo' => 'Polo',
                                        'professor' => 'Professor',
                                        'aluno' => 'Aluno'
                                    ];
                                    echo $tipos_labels[$usuario['tipo']] ?? $usuario['tipo'];
                                    ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="status-badge status-<?php echo $usuario['status']; ?>">
                                    <?php echo ucfirst($usuario['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php 
                                if ($usuario['ultimo_login']) {
                                    echo date('d/m/Y H:i', strtotime($usuario['ultimo_login']));
                                } else {
                                    echo 'Nunca';
                                }
                                ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo number_format($usuario['atividade_30d']); ?> ações (30d)
                            </td>                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <div class="flex space-x-2">                                    <button onclick="abrirModalEditarUsuario(
                                        <?php echo $usuario['id']; ?>, 
                                        '<?php echo addslashes($usuario['nome']); ?>', 
                                        '<?php echo addslashes($usuario['email']); ?>', 
                                        '<?php echo $usuario['tipo'] ?? $usuario['tipo_usuario'] ?? 'secretaria_academica'; ?>', 
                                        null
                                    )" 
                                    class="text-blue-600 hover:text-blue-900" title="Editar usuário">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    
                                    <?php if ($usuario['status'] === 'ativo'): ?>
                                    <button onclick="alterarStatusUsuario(<?php echo $usuario['id']; ?>, 'desativar', '<?php echo addslashes($usuario['nome']); ?>')" 
                                            class="text-yellow-600 hover:text-yellow-900" title="Desativar usuário">
                                        <i class="fas fa-pause"></i>
                                    </button>
                                    <?php else: ?>
                                    <button onclick="alterarStatusUsuario(<?php echo $usuario['id']; ?>, 'ativar', '<?php echo addslashes($usuario['nome']); ?>')" 
                                            class="text-green-600 hover:text-green-900" title="Ativar usuário">
                                        <i class="fas fa-play"></i>
                                    </button>
                                    <?php endif; ?>
                                      <?php if (($usuario['status'] ?? '') === 'bloqueado'): ?>
                                    <button onclick="alterarStatusUsuario(<?php echo $usuario['id']; ?>, 'desbloquear', '<?php echo addslashes($usuario['nome']); ?>')" 
                                            class="text-orange-600 hover:text-orange-900" title="Desbloquear usuário">
                                        <i class="fas fa-unlock"></i>
                                    </button>
                                    <?php else: ?>
                                    <button onclick="alterarStatusUsuario(<?php echo $usuario['id']; ?>, 'bloquear', '<?php echo addslashes($usuario['nome']); ?>')" 
                                            class="text-red-600 hover:text-red-900" title="Bloquear usuário">
                                        <i class="fas fa-ban"></i>
                                    </button>
                                    <?php endif; ?>
                                    
                                    <button onclick="abrirModalResetarSenha(<?php echo $usuario['id']; ?>, '<?php echo addslashes($usuario['nome']); ?>')" 
                                            class="text-purple-600 hover:text-purple-900" title="Resetar senha">
                                        <i class="fas fa-key"></i>
                                    </button>
                                    
                                    <a href="logs.php?filtro_usuario=<?php echo $usuario['id']; ?>" 
                                       class="text-gray-600 hover:text-gray-900" title="Ver logs do usuário">
                                        <i class="fas fa-history"></i>
                                    </a>
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
                        de <?php echo number_format($total_registros); ?> resultados
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

    <!-- Modal Novo Usuário -->
    <div id="modalNovoUsuario" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-1/2 lg:w-1/3 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between pb-3">
                    <h3 class="text-lg font-semibold text-gray-900">Novo Usuário</h3>
                    <button onclick="fecharModal('modalNovoUsuario')" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <form id="formNovoUsuario" onsubmit="criarUsuario(event)">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nome Completo *</label>
                            <input type="text" name="nome" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                            <input type="email" name="email" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Senha *</label>
                            <input type="password" name="senha" required minlength="6"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500">
                            <p class="text-xs text-gray-500 mt-1">Mínimo 6 caracteres</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Usuário *</label>
                            <select name="tipo_usuario" required onchange="togglePoloField(this)"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500">
                                <option value="">Selecione...</option>
                                <option value="admin_master">Administrador Master</option>
                                <option value="diretoria">Diretoria</option>
                                <option value="secretaria_academica">Secretaria Acadêmica</option>
                                <option value="financeiro">Financeiro</option>
                                <option value="polo">Polo</option>
                                <option value="professor">Professor</option>
                                <option value="aluno">Aluno</option>
                            </select>
                        </div>
                        
                        <div id="campoPoloId" class="hidden">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Polo</label>
                            <select name="polo_id"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500">
                                <option value="">Selecione um polo...</option>
                                <?php
                                $stmt = $conn->prepare("SELECT id, nome FROM polos WHERE ativo = 1 ORDER BY nome");
                                $stmt->execute();
                                $polos = $stmt->get_result();
                                while ($polo = $polos->fetch_assoc()) {
                                    echo "<option value='{$polo['id']}'>{$polo['nome']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-3 mt-6 pt-3 border-t">
                        <button type="button" onclick="fecharModal('modalNovoUsuario')" 
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                            Cancelar
                        </button>
                        <button type="submit" id="btnCriarUsuario"
                                class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                            <i class="fas fa-save mr-1"></i> Criar Usuário
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Usuário -->
    <div id="modalEditarUsuario" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-1/2 lg:w-1/3 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between pb-3">
                    <h3 class="text-lg font-semibold text-gray-900">Editar Usuário</h3>
                    <button onclick="fecharModal('modalEditarUsuario')" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <form id="formEditarUsuario" onsubmit="editarUsuario(event)">
                    <input type="hidden" name="usuario_id" id="editUsuarioId">
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nome Completo *</label>
                            <input type="text" name="nome" id="editNome" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                            <input type="email" name="email" id="editEmail" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Usuário *</label>
                            <select name="tipo_usuario" id="editTipoUsuario" required onchange="togglePoloFieldEdit(this)"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500">
                                <option value="">Selecione...</option>
                                <option value="admin_master">Administrador Master</option>
                                <option value="diretoria">Diretoria</option>
                                <option value="secretaria_academica">Secretaria Acadêmica</option>
                                <option value="financeiro">Financeiro</option>
                                <option value="polo">Polo</option>
                                <option value="professor">Professor</option>
                                <option value="aluno">Aluno</option>
                            </select>
                        </div>
                        
                        <div id="campoPoloIdEdit" class="hidden">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Polo</label>
                            <select name="polo_id" id="editPoloId"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500">
                                <option value="">Selecione um polo...</option>
                                <?php
                                $polos->data_seek(0); // Reset do cursor
                                while ($polo = $polos->fetch_assoc()) {
                                    echo "<option value='{$polo['id']}'>{$polo['nome']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-3 mt-6 pt-3 border-t">
                        <button type="button" onclick="fecharModal('modalEditarUsuario')" 
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                            Cancelar
                        </button>
                        <button type="submit" id="btnEditarUsuario"
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            <i class="fas fa-save mr-1"></i> Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Resetar Senha -->
    <div id="modalResetarSenha" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-1/2 lg:w-1/3 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between pb-3">
                    <h3 class="text-lg font-semibold text-gray-900">Resetar Senha</h3>
                    <button onclick="fecharModal('modalResetarSenha')" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <form id="formResetarSenha" onsubmit="resetarSenha(event)">
                    <input type="hidden" name="usuario_id" id="resetUsuarioId">
                    
                    <div class="mb-4">
                        <p class="text-sm text-gray-600 mb-4">
                            Usuário: <span id="resetUsuarioNome" class="font-semibold"></span>
                        </p>
                        
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nova Senha</label>
                        <input type="password" name="nova_senha" minlength="6" 
                               placeholder="Deixe em branco para gerar automaticamente"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500">
                        <p class="text-xs text-gray-500 mt-1">Se deixar em branco, uma senha será gerada automaticamente</p>
                    </div>
                    
                    <div class="flex justify-end space-x-3 mt-6 pt-3 border-t">
                        <button type="button" onclick="fecharModal('modalResetarSenha')" 
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                            Cancelar
                        </button>
                        <button type="submit" id="btnResetarSenha"
                                class="px-4 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-700">
                            <i class="fas fa-key mr-1"></i> Resetar Senha
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function abrirModalNovoUsuario() {
            document.getElementById('modalNovoUsuario').classList.remove('hidden');
            document.getElementById('formNovoUsuario').reset();
        }

        function abrirModalEditarUsuario(usuarioId, nome, email, tipoUsuario, poloId) {
            document.getElementById('modalEditarUsuario').classList.remove('hidden');
            document.getElementById('editUsuarioId').value = usuarioId;
            document.getElementById('editNome').value = nome;
            document.getElementById('editEmail').value = email;
            document.getElementById('editTipoUsuario').value = tipoUsuario;
            document.getElementById('editPoloId').value = poloId || '';
            
            // Mostrar/ocultar campo polo
            const campoPoloEdit = document.getElementById('campoPoloIdEdit');
            if (tipoUsuario === 'polo' || tipoUsuario === 'aluno') {
                campoPoloEdit.classList.remove('hidden');
            } else {
                campoPoloEdit.classList.add('hidden');
            }
        }

        function abrirModalResetarSenha(usuarioId, nome) {
            document.getElementById('modalResetarSenha').classList.remove('hidden');
            document.getElementById('resetUsuarioId').value = usuarioId;
            document.getElementById('resetUsuarioNome').textContent = nome;
            document.getElementById('formResetarSenha').reset();
            document.getElementById('resetUsuarioId').value = usuarioId;
        }

        function fecharModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }

        function togglePoloField(select) {
            const campoPoloId = document.getElementById('campoPoloId');
            if (select.value === 'polo' || select.value === 'aluno') {
                campoPoloId.classList.remove('hidden');
            } else {
                campoPoloId.classList.add('hidden');
            }
        }

        function togglePoloFieldEdit(select) {
            const campoPoloIdEdit = document.getElementById('campoPoloIdEdit');
            if (select.value === 'polo' || select.value === 'aluno') {
                campoPoloIdEdit.classList.remove('hidden');
            } else {
                campoPoloIdEdit.classList.add('hidden');
            }
        }

        async function criarUsuario(event) {
            event.preventDefault();
            
            const btn = document.getElementById('btnCriarUsuario');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Criando...';
            btn.disabled = true;
            
            try {
                const formData = new FormData(event.target);
                formData.append('acao', 'criar_usuario');
                
                const response = await fetch('includes/ajax.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Usuário criado com sucesso!');
                    fecharModal('modalNovoUsuario');
                    window.location.reload();
                } else {
                    alert('Erro: ' + result.message);
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro ao criar usuário. Tente novamente.');
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }

        async function editarUsuario(event) {
            event.preventDefault();
            
            const btn = document.getElementById('btnEditarUsuario');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Salvando...';
            btn.disabled = true;
            
            try {
                const formData = new FormData(event.target);
                formData.append('acao', 'editar_usuario');
                
                const response = await fetch('includes/ajax.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Usuário atualizado com sucesso!');
                    fecharModal('modalEditarUsuario');
                    window.location.reload();
                } else {
                    alert('Erro: ' + result.message);
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro ao editar usuário. Tente novamente.');
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }

        async function resetarSenha(event) {
            event.preventDefault();
            
            const btn = document.getElementById('btnResetarSenha');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Resetando...';
            btn.disabled = true;
            
            try {
                const formData = new FormData(event.target);
                formData.append('acao', 'resetar_senha');
                
                const response = await fetch('includes/ajax.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    let message = 'Senha resetada com sucesso!';
                    if (result.nova_senha) {
                        message += '\n\nNova senha: ' + result.nova_senha;
                        message += '\n\nAnote esta senha, pois ela não será exibida novamente.';
                    }
                    alert(message);
                    fecharModal('modalResetarSenha');
                    window.location.reload();
                } else {
                    alert('Erro: ' + result.message);
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro ao resetar senha. Tente novamente.');
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }

        async function alterarStatusUsuario(usuarioId, status, nome) {
            const acoes = {
                'ativar': 'ativar',
                'desativar': 'desativar',
                'bloquear': 'bloquear',
                'desbloquear': 'desbloquear'
            };
            
            if (!confirm(`Tem certeza que deseja ${acoes[status]} o usuário "${nome}"?`)) {
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('acao', 'alterar_status_usuario');
                formData.append('usuario_id', usuarioId);
                formData.append('status', status);
                
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
                alert('Erro ao alterar status do usuário. Tente novamente.');
            }
        }

        // Fechar modal ao clicar fora dele
        window.onclick = function(event) {
            const modals = ['modalNovoUsuario', 'modalEditarUsuario', 'modalResetarSenha'];
            modals.forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (event.target == modal) {
                    fecharModal(modalId);
                }
            });
        }
        
        // Auto-atualizar página a cada 5 minutos
        setTimeout(() => {
            window.location.reload();
        }, 300000);
    </script>
</body>
</html>
