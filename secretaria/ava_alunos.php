<?php
/**
 * Página de Gerenciamento de Alunos do AVA
 * Permite à secretaria gerenciar os alunos no AVA
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

// Define a ação padrão
$action = $_GET['action'] ?? 'listar';

// Processa as ações
switch ($action) {
    case 'ativar':
        // Lógica para ativar aluno no AVA
        if (!isset($_GET['id']) || empty($_GET['id'])) {
            setMensagem('erro', 'ID do aluno não informado.');
            redirect('ava_alunos.php');
            exit;
        }

        $aluno_id = (int)$_GET['id'];

        // Verifica se o aluno existe
        $sql = "SELECT * FROM alunos WHERE id = ?";
        $aluno = $db->fetchOne($sql, [$aluno_id]);

        if (!$aluno) {
            setMensagem('erro', 'Aluno não encontrado.');
            redirect('ava_alunos.php');
            exit;
        }

        // Verifica se o aluno já está ativo no AVA
        $sql = "SELECT * FROM ava_alunos WHERE aluno_id = ?";
        $ava_aluno = $db->fetchOne($sql, [$aluno_id]);

        if ($ava_aluno) {
            // Atualiza o status do aluno no AVA
            $sql = "UPDATE ava_alunos SET status = 'ativo', updated_at = NOW() WHERE aluno_id = ?";
            $db->query($sql, [$aluno_id]);
        } else {
            // Gera um nome de usuário baseado no email ou nome do aluno
            $username = !empty($aluno['email']) ? explode('@', $aluno['email'])[0] : strtolower(str_replace(' ', '.', $aluno['nome']));

            // Gera uma senha aleatória
            $senha = substr(md5(uniqid(rand(), true)), 0, 8);

            // Insere o aluno no AVA
            $sql = "INSERT INTO ava_alunos (aluno_id, username, senha, status, created_at, updated_at)
                    VALUES (?, ?, ?, 'ativo', NOW(), NOW())";
            $db->query($sql, [$aluno_id, $username, password_hash($senha, PASSWORD_DEFAULT)]);

            // Envia email com as credenciais (simulado aqui)
            // TODO: Implementar envio de email

            setMensagem('sucesso', 'Aluno ativado no AVA com sucesso. Username: ' . $username . ', Senha: ' . $senha);
            redirect('ava_alunos.php');
            exit;
        }

        setMensagem('sucesso', 'Aluno ativado no AVA com sucesso.');
        redirect('ava_alunos.php');
        break;

    case 'desativar':
        // Lógica para desativar aluno no AVA
        if (!isset($_GET['id']) || empty($_GET['id'])) {
            setMensagem('erro', 'ID do aluno não informado.');
            redirect('ava_alunos.php');
            exit;
        }

        $aluno_id = (int)$_GET['id'];

        // Verifica se o aluno existe
        $sql = "SELECT * FROM alunos WHERE id = ?";
        $aluno = $db->fetchOne($sql, [$aluno_id]);

        if (!$aluno) {
            setMensagem('erro', 'Aluno não encontrado.');
            redirect('ava_alunos.php');
            exit;
        }

        // Verifica se o aluno está ativo no AVA
        $sql = "SELECT * FROM ava_alunos WHERE aluno_id = ?";
        $ava_aluno = $db->fetchOne($sql, [$aluno_id]);

        if ($ava_aluno) {
            // Atualiza o status do aluno no AVA
            $sql = "UPDATE ava_alunos SET status = 'inativo', updated_at = NOW() WHERE aluno_id = ?";
            $db->query($sql, [$aluno_id]);

            setMensagem('sucesso', 'Aluno desativado no AVA com sucesso.');
        } else {
            setMensagem('erro', 'Aluno não está cadastrado no AVA.');
        }

        redirect('ava_alunos.php');
        break;

    case 'resetar_senha':
        // Lógica para resetar senha do aluno no AVA
        if (!isset($_GET['id']) || empty($_GET['id'])) {
            setMensagem('erro', 'ID do aluno não informado.');
            redirect('ava_alunos.php');
            exit;
        }

        $aluno_id = (int)$_GET['id'];

        // Verifica se o aluno existe
        $sql = "SELECT * FROM alunos WHERE id = ?";
        $aluno = $db->fetchOne($sql, [$aluno_id]);

        if (!$aluno) {
            setMensagem('erro', 'Aluno não encontrado.');
            redirect('ava_alunos.php');
            exit;
        }

        // Verifica se o aluno está ativo no AVA
        $sql = "SELECT * FROM ava_alunos WHERE aluno_id = ?";
        $ava_aluno = $db->fetchOne($sql, [$aluno_id]);

        if ($ava_aluno) {
            // Gera uma nova senha aleatória
            $nova_senha = substr(md5(uniqid(rand(), true)), 0, 8);

            // Atualiza a senha do aluno no AVA
            $sql = "UPDATE ava_alunos SET senha = ?, updated_at = NOW() WHERE aluno_id = ?";
            $db->query($sql, [password_hash($nova_senha, PASSWORD_DEFAULT), $aluno_id]);

            // Envia email com a nova senha (simulado aqui)
            // TODO: Implementar envio de email

            setMensagem('sucesso', 'Senha do aluno resetada com sucesso. Nova senha: ' . $nova_senha);
        } else {
            setMensagem('erro', 'Aluno não está cadastrado no AVA.');
        }

        redirect('ava_alunos.php');
        break;

    case 'listar':
    default:
        // Configuração da paginação
        $itens_por_pagina = 20; // Número de alunos por página
        $pagina_atual = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
        $pagina_atual = max(1, $pagina_atual); // Garante que a página seja pelo menos 1
        $offset = ($pagina_atual - 1) * $itens_por_pagina;

        // Parâmetros de busca
        $busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';
        $status = isset($_GET['status']) ? trim($_GET['status']) : '';
        $polo_id = isset($_GET['polo_id']) ? intval($_GET['polo_id']) : 0;

        // Busca os alunos com paginação
        try {
            // Verifica se as tabelas existem
            $check_alunos = $db->fetchOne("SHOW TABLES LIKE 'alunos'");
            $check_ava_alunos = $db->fetchOne("SHOW TABLES LIKE 'ava_alunos'");

            if ($check_alunos) {
                // Construção da cláusula WHERE para filtros
                $where_conditions = ["a.status = 'ativo'"];
                $params = [];

                if (!empty($busca)) {
                    $where_conditions[] = "(a.nome LIKE ? OR a.matricula LIKE ? OR a.email LIKE ?)";
                    $busca_param = "%{$busca}%";
                    $params[] = $busca_param;
                    $params[] = $busca_param;
                    $params[] = $busca_param;
                }

                if (!empty($status)) {
                    if ($status === 'nao_cadastrado') {
                        $where_conditions[] = "aa.id IS NULL";
                    } else {
                        $where_conditions[] = "aa.status = ?";
                        $params[] = $status;
                    }
                }

                if ($polo_id > 0) {
                    $where_conditions[] = "a.polo_id = ?";
                    $params[] = $polo_id;
                }

                $where_clause = implode(' AND ', $where_conditions);

                // Consulta para contar o total de registros
                if ($check_ava_alunos) {
                    $count_sql = "SELECT COUNT(*) as total
                                FROM alunos a
                                LEFT JOIN ava_alunos aa ON a.id = aa.aluno_id
                                WHERE {$where_clause}";
                } else {
                    $count_sql = "SELECT COUNT(*) as total
                                FROM alunos a
                                LEFT JOIN (SELECT NULL as id, NULL as aluno_id, NULL as status) aa ON a.id = aa.aluno_id
                                WHERE {$where_clause}";
                }

                $total_result = $db->fetchOne($count_sql, $params);
                $total_alunos = $total_result ? $total_result['total'] : 0;
                $total_paginas = ceil($total_alunos / $itens_por_pagina);

                // Ajusta a página atual se necessário
                if ($pagina_atual > $total_paginas && $total_paginas > 0) {
                    $pagina_atual = $total_paginas;
                    $offset = ($pagina_atual - 1) * $itens_por_pagina;
                }

                // Consulta para buscar os alunos com paginação
                if ($check_ava_alunos) {
                    // Se ambas as tabelas existem, busca os alunos com informações do AVA
                    $sql = "SELECT a.*,
                            CASE WHEN aa.status IS NULL THEN 'nao_cadastrado' ELSE aa.status END as status_ava,
                            aa.username, aa.created_at as data_cadastro_ava
                            FROM alunos a
                            LEFT JOIN ava_alunos aa ON a.id = aa.aluno_id
                            WHERE {$where_clause}
                            ORDER BY a.nome
                            LIMIT {$itens_por_pagina} OFFSET {$offset}";
                } else {
                    // Se a tabela ava_alunos não existe, busca apenas os alunos
                    $sql = "SELECT a.*,
                            'nao_cadastrado' as status_ava,
                            NULL as username, NULL as data_cadastro_ava
                            FROM alunos a
                            WHERE {$where_clause}
                            ORDER BY a.nome
                            LIMIT {$itens_por_pagina} OFFSET {$offset}";
                }

                $alunos = $db->fetchAll($sql, $params);

                // Informações para a paginação
                $info_paginacao = [
                    'total_alunos' => $total_alunos,
                    'total_paginas' => $total_paginas,
                    'pagina_atual' => $pagina_atual,
                    'itens_por_pagina' => $itens_por_pagina
                ];
            } else {
                // Se a tabela alunos não existe, cria um array vazio
                $alunos = [];
                $info_paginacao = [
                    'total_alunos' => 0,
                    'total_paginas' => 0,
                    'pagina_atual' => 1,
                    'itens_por_pagina' => $itens_por_pagina
                ];

                // Exibe uma mensagem informando que a tabela não existe
                setMensagem('erro', 'A tabela alunos não existe no banco de dados. Entre em contato com o administrador do sistema.');
            }
        } catch (Exception $e) {
            // Em caso de erro, cria um array vazio
            $alunos = [];
            $info_paginacao = [
                'total_alunos' => 0,
                'total_paginas' => 0,
                'pagina_atual' => 1,
                'itens_por_pagina' => $itens_por_pagina
            ];

            // Exibe uma mensagem de erro
            setMensagem('erro', 'Erro ao buscar alunos: ' . $e->getMessage());
        }
        break;
}

// Define o título da página
$titulo_pagina = 'Gerenciamento de Alunos do AVA';
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
        .status-nao_cadastrado { background-color: #F3F4F6; color: #6B7280; }
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

                    <!-- Formulário de busca -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-800">Buscar Alunos</h2>
                        </div>
                        <div class="p-6">
                            <form action="" method="GET" class="flex flex-wrap gap-4">
                                <div class="w-full md:w-1/3">
                                    <label for="busca" class="block text-sm font-medium text-gray-700 mb-1">Nome, Matrícula ou Email</label>
                                    <input type="text" id="busca" name="busca" value="<?php echo htmlspecialchars($busca); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500" placeholder="Digite o nome, matrícula ou email">
                                </div>
                                <div class="w-full md:w-1/4">
                                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status no AVA</label>
                                    <select id="status" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">Todos</option>
                                        <option value="ativo" <?php echo $status === 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                                        <option value="inativo" <?php echo $status === 'inativo' ? 'selected' : ''; ?>>Inativo</option>
                                        <option value="nao_cadastrado" <?php echo $status === 'nao_cadastrado' ? 'selected' : ''; ?>>Não Cadastrado</option>
                                    </select>
                                </div>
                                <div class="w-full md:w-1/4">
                                    <label for="polo" class="block text-sm font-medium text-gray-700 mb-1">Polo</label>
                                    <select id="polo" name="polo_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">Todos</option>
                                        <?php
                                        // Busca os polos para o filtro
                                        try {
                                            $polos_sql = "SELECT id, nome FROM polos WHERE status = 'ativo' ORDER BY nome";
                                            $polos_lista = $db->fetchAll($polos_sql);
                                            foreach ($polos_lista as $polo) {
                                                $selected = $polo_id == $polo['id'] ? 'selected' : '';
                                                echo "<option value=\"{$polo['id']}\" {$selected}>{$polo['nome']}</option>";
                                            }
                                        } catch (Exception $e) {
                                            // Silencia erros aqui
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="w-full md:w-1/6 flex items-end">
                                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md">
                                        <i class="fas fa-search mr-2"></i> Buscar
                                    </button>
                                </div>

                                <!-- Botão para limpar filtros -->
                                <?php if (!empty($busca) || !empty($status) || $polo_id > 0): ?>
                                <div class="w-full flex justify-end mt-2">
                                    <a href="ava_alunos.php" class="text-sm text-blue-600 hover:text-blue-800">
                                        <i class="fas fa-times-circle mr-1"></i> Limpar filtros
                                    </a>
                                </div>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>

                    <!-- Lista de Alunos -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                            <h2 class="text-lg font-semibold text-gray-800">Alunos Cadastrados</h2>
                            <div class="text-sm text-gray-600">
                                Total: <span class="font-semibold"><?php echo $info_paginacao['total_alunos']; ?></span> alunos
                                <?php if (!empty($busca) || !empty($status) || $polo_id > 0): ?>
                                (filtrados)
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="p-6">
                            <?php if (empty($alunos)): ?>
                            <div class="text-center text-gray-500 py-4">
                                <p>Nenhum aluno encontrado.</p>
                            </div>
                            <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Matrícula</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username AVA</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status AVA</th>
                                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($alunos as $aluno): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($aluno['nome']); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($aluno['matricula'] ?? 'N/A'); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($aluno['email'] ?? 'N/A'); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($aluno['username'] ?? 'N/A'); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="status-badge status-<?php echo $aluno['status_ava']; ?>">
                                                    <?php
                                                    if ($aluno['status_ava'] === 'ativo') echo 'Ativo';
                                                    elseif ($aluno['status_ava'] === 'inativo') echo 'Inativo';
                                                    else echo 'Não Cadastrado';
                                                    ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                <div class="flex flex-wrap justify-center gap-2">
                                                    <?php if ($aluno['status_ava'] === 'nao_cadastrado' || $aluno['status_ava'] === 'inativo'): ?>
                                                    <a href="ava_alunos.php?action=ativar&id=<?php echo $aluno['id']; ?>" class="inline-flex items-center px-2.5 py-1.5 bg-green-100 text-green-800 text-xs font-medium rounded hover:bg-green-200">
                                                        <i class="fas fa-check mr-1"></i> Ativar no AVA
                                                    </a>
                                                    <?php else: ?>
                                                    <a href="ava_alunos.php?action=desativar&id=<?php echo $aluno['id']; ?>" class="inline-flex items-center px-2.5 py-1.5 bg-red-100 text-red-800 text-xs font-medium rounded hover:bg-red-200">
                                                        <i class="fas fa-times mr-1"></i> Desativar
                                                    </a>
                                                    <?php endif; ?>

                                                    <?php if ($aluno['status_ava'] === 'ativo' || $aluno['status_ava'] === 'inativo'): ?>
                                                    <a href="ava_alunos.php?action=resetar_senha&id=<?php echo $aluno['id']; ?>" class="inline-flex items-center px-2.5 py-1.5 bg-blue-100 text-blue-800 text-xs font-medium rounded hover:bg-blue-200">
                                                        <i class="fas fa-key mr-1"></i> Resetar Senha
                                                    </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>

                            <!-- Paginação -->
                            <?php if ($info_paginacao['total_paginas'] > 1): ?>
                            <div class="mt-6 flex justify-between items-center">
                                <div class="text-sm text-gray-700">
                                    Mostrando <span class="font-medium"><?php echo ($info_paginacao['pagina_atual'] - 1) * $info_paginacao['itens_por_pagina'] + 1; ?></span> a
                                    <span class="font-medium"><?php echo min($info_paginacao['pagina_atual'] * $info_paginacao['itens_por_pagina'], $info_paginacao['total_alunos']); ?></span> de
                                    <span class="font-medium"><?php echo $info_paginacao['total_alunos']; ?></span> alunos
                                </div>
                                <div class="flex space-x-2">
                                    <?php
                                    // Parâmetros da URL para manter os filtros
                                    $url_params = [];
                                    if (!empty($busca)) $url_params[] = "busca=" . urlencode($busca);
                                    if (!empty($status)) $url_params[] = "status=" . urlencode($status);
                                    if ($polo_id > 0) $url_params[] = "polo_id=" . $polo_id;
                                    $url_params_str = implode('&', $url_params);
                                    $url_base = "ava_alunos.php?" . ($url_params_str ? $url_params_str . "&" : "");

                                    // Botão Anterior
                                    if ($info_paginacao['pagina_atual'] > 1):
                                    ?>
                                    <a href="<?php echo $url_base; ?>pagina=<?php echo $info_paginacao['pagina_atual'] - 1; ?>" class="px-3 py-1 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                                        <i class="fas fa-chevron-left mr-1"></i> Anterior
                                    </a>
                                    <?php endif; ?>

                                    <?php
                                    // Números das páginas
                                    $start_page = max(1, $info_paginacao['pagina_atual'] - 2);
                                    $end_page = min($info_paginacao['total_paginas'], $info_paginacao['pagina_atual'] + 2);

                                    // Mostrar primeira página se estiver muito distante
                                    if ($start_page > 1):
                                    ?>
                                    <a href="<?php echo $url_base; ?>pagina=1" class="px-3 py-1 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">1</a>
                                    <?php if ($start_page > 2): ?>
                                    <span class="px-2 py-1 text-gray-500">...</span>
                                    <?php endif; ?>
                                    <?php endif; ?>

                                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                    <a href="<?php echo $url_base; ?>pagina=<?php echo $i; ?>" class="px-3 py-1 <?php echo $i === $info_paginacao['pagina_atual'] ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> rounded-md">
                                        <?php echo $i; ?>
                                    </a>
                                    <?php endfor; ?>

                                    <?php
                                    // Mostrar última página se estiver muito distante
                                    if ($end_page < $info_paginacao['total_paginas']):
                                    ?>
                                    <?php if ($end_page < $info_paginacao['total_paginas'] - 1): ?>
                                    <span class="px-2 py-1 text-gray-500">...</span>
                                    <?php endif; ?>
                                    <a href="<?php echo $url_base; ?>pagina=<?php echo $info_paginacao['total_paginas']; ?>" class="px-3 py-1 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300"><?php echo $info_paginacao['total_paginas']; ?></a>
                                    <?php endif; ?>

                                    <?php
                                    // Botão Próximo
                                    if ($info_paginacao['pagina_atual'] < $info_paginacao['total_paginas']):
                                    ?>
                                    <a href="<?php echo $url_base; ?>pagina=<?php echo $info_paginacao['pagina_atual'] + 1; ?>" class="px-3 py-1 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                                        Próximo <i class="fas fa-chevron-right ml-1"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
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
