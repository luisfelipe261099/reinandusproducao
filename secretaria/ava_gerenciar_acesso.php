<?php
/**
 * Página de Gerenciamento de Acesso ao AVA
 * Permite à secretaria liberar ou revogar o acesso dos polos ao AVA
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

// Define os parâmetros de paginação
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$itens_por_pagina = 10;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

// Define os parâmetros de busca
$busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';
$filtro_status = isset($_GET['status']) ? $_GET['status'] : '';

// Processa as ações
switch ($action) {
    case 'liberar':
        // Verifica se o ID do polo foi informado
        if (!isset($_GET['polo_id']) || empty($_GET['polo_id'])) {
            setMensagem('erro', 'ID do polo não informado.');
            redirect('ava_gerenciar_acesso.php');
            exit;
        }

        $polo_id = (int)$_GET['polo_id'];
        $usuario_id = getUsuarioId();
        $observacoes = $_POST['observacoes'] ?? '';

        // Verifica se o polo existe
        $sql = "SELECT * FROM polos WHERE id = ?";
        $polo = $db->fetchOne($sql, [$polo_id]);

        if (!$polo) {
            setMensagem('erro', 'Polo não encontrado.');
            redirect('ava_gerenciar_acesso.php');
            exit;
        }

        // Verifica se o polo já tem acesso ao AVA
        $sql = "SELECT * FROM ava_polos_acesso WHERE polo_id = ?";
        $acesso = $db->fetchOne($sql, [$polo_id]);

        if ($acesso) {
            // Atualiza o acesso
            $sql = "UPDATE ava_polos_acesso SET
                    liberado = 1,
                    data_liberacao = NOW(),
                    liberado_por = ?,
                    observacoes = ?,
                    updated_at = NOW()
                    WHERE polo_id = ?";
            $db->query($sql, [$usuario_id, $observacoes, $polo_id]);
        } else {
            // Insere novo acesso
            $sql = "INSERT INTO ava_polos_acesso (polo_id, liberado, data_liberacao, liberado_por, observacoes, created_at, updated_at)
                    VALUES (?, 1, NOW(), ?, ?, NOW(), NOW())";
            $db->query($sql, [$polo_id, $usuario_id, $observacoes]);
        }

        setMensagem('sucesso', 'Acesso ao AVA liberado para o polo ' . $polo['nome'] . '.');
        redirect('ava_gerenciar_acesso.php');
        break;

    case 'revogar':
        // Verifica se o ID do polo foi informado
        if (!isset($_GET['polo_id']) || empty($_GET['polo_id'])) {
            setMensagem('erro', 'ID do polo não informado.');
            redirect('ava_gerenciar_acesso.php');
            exit;
        }

        $polo_id = (int)$_GET['polo_id'];
        $usuario_id = getUsuarioId();
        $observacoes = $_POST['observacoes'] ?? '';

        // Verifica se o polo existe
        $sql = "SELECT * FROM polos WHERE id = ?";
        $polo = $db->fetchOne($sql, [$polo_id]);

        if (!$polo) {
            setMensagem('erro', 'Polo não encontrado.');
            redirect('ava_gerenciar_acesso.php');
            exit;
        }

        // Verifica se o polo tem acesso ao AVA
        $sql = "SELECT * FROM ava_polos_acesso WHERE polo_id = ?";
        $acesso = $db->fetchOne($sql, [$polo_id]);

        if ($acesso) {
            // Atualiza o acesso
            $sql = "UPDATE ava_polos_acesso SET
                    liberado = 0,
                    liberado_por = ?,
                    observacoes = ?,
                    updated_at = NOW()
                    WHERE polo_id = ?";
            $db->query($sql, [$usuario_id, $observacoes, $polo_id]);

            setMensagem('sucesso', 'Acesso ao AVA revogado para o polo ' . $polo['nome'] . '.');
        } else {
            setMensagem('erro', 'O polo ' . $polo['nome'] . ' não possui acesso ao AVA.');
        }

        redirect('ava_gerenciar_acesso.php');
        break;

    case 'listar':
    default:
        // Busca todos os polos
        try {
            // Verifica se as tabelas existem
            $check_polos = $db->fetchOne("SHOW TABLES LIKE 'polos'");
            $check_ava_polos_acesso = $db->fetchOne("SHOW TABLES LIKE 'ava_polos_acesso'");
            $check_usuarios = $db->fetchOne("SHOW TABLES LIKE 'usuarios'");

            if ($check_polos) {
                if ($check_ava_polos_acesso && $check_usuarios) {
                    // Construir a consulta base
                    $sqlBase = "FROM polos p
                                LEFT JOIN ava_polos_acesso apa ON p.id = apa.polo_id
                                LEFT JOIN usuarios u ON apa.liberado_por = u.id
                                WHERE p.status = 'ativo'";

                    // Adicionar condições de busca
                    $params = [];
                    if (!empty($busca)) {
                        $sqlBase .= " AND (p.nome LIKE ? OR p.responsavel LIKE ? OR p.email LIKE ? OR p.cidade LIKE ? OR p.estado LIKE ?)";
                        $busca_param = "%{$busca}%";
                        $params = array_merge($params, [$busca_param, $busca_param, $busca_param, $busca_param, $busca_param]);
                    }

                    // Adicionar filtro de status
                    if (!empty($filtro_status)) {
                        if ($filtro_status === 'liberado') {
                            $sqlBase .= " AND apa.liberado = 1";
                        } elseif ($filtro_status === 'revogado') {
                            $sqlBase .= " AND apa.liberado = 0";
                        } elseif ($filtro_status === 'nao_configurado') {
                            $sqlBase .= " AND apa.liberado IS NULL";
                        }
                    }

                    // Consulta para contar o total de registros
                    $sqlCount = "SELECT COUNT(*) as total " . $sqlBase;
                    $total = $db->fetchOne($sqlCount, $params);
                    $total_registros = $total['total'] ?? 0;

                    // Calcular total de páginas
                    $total_paginas = ceil($total_registros / $itens_por_pagina);

                    // Ajustar página atual se necessário
                    if ($pagina_atual > $total_paginas && $total_paginas > 0) {
                        $pagina_atual = $total_paginas;
                        $offset = ($pagina_atual - 1) * $itens_por_pagina;
                    }

                    // Consulta para buscar os polos com paginação
                    $sql = "SELECT p.*,
                            CASE WHEN apa.liberado = 1 THEN 'liberado' WHEN apa.liberado = 0 THEN 'revogado' ELSE 'nao_configurado' END as status_ava,
                            apa.data_liberacao, u.nome as liberado_por_nome, apa.observacoes
                            " . $sqlBase . "
                            ORDER BY p.nome
                            LIMIT {$offset}, {$itens_por_pagina}";
                } else {
                    // Se alguma tabela não existe, busca apenas os polos
                    $sqlBase = "FROM polos p WHERE p.status = 'ativo'";

                    // Adicionar condições de busca
                    $params = [];
                    if (!empty($busca)) {
                        $sqlBase .= " AND (p.nome LIKE ? OR p.responsavel LIKE ? OR p.email LIKE ? OR p.cidade LIKE ? OR p.estado LIKE ?)";
                        $busca_param = "%{$busca}%";
                        $params = array_merge($params, [$busca_param, $busca_param, $busca_param, $busca_param, $busca_param]);
                    }

                    // Consulta para contar o total de registros
                    $sqlCount = "SELECT COUNT(*) as total " . $sqlBase;
                    $total = $db->fetchOne($sqlCount, $params);
                    $total_registros = $total['total'] ?? 0;

                    // Calcular total de páginas
                    $total_paginas = ceil($total_registros / $itens_por_pagina);

                    // Ajustar página atual se necessário
                    if ($pagina_atual > $total_paginas && $total_paginas > 0) {
                        $pagina_atual = $total_paginas;
                        $offset = ($pagina_atual - 1) * $itens_por_pagina;
                    }

                    $sql = "SELECT p.*,
                            'nao_configurado' as status_ava,
                            NULL as data_liberacao, NULL as liberado_por_nome, NULL as observacoes
                            " . $sqlBase . "
                            ORDER BY p.nome
                            LIMIT {$offset}, {$itens_por_pagina}";
                }

                $polos = $db->fetchAll($sql, $params);
            } else {
                // Se a tabela polos não existe, cria um array vazio
                $polos = [];

                // Exibe uma mensagem informando que a tabela não existe
                setMensagem('erro', 'A tabela polos não existe no banco de dados. Entre em contato com o administrador do sistema.');
            }
        } catch (Exception $e) {
            // Em caso de erro, cria um array vazio
            $polos = [];

            // Exibe uma mensagem de erro
            setMensagem('erro', 'Erro ao buscar polos: ' . $e->getMessage());
        }
        break;
}

// Define o título da página
$titulo_pagina = 'Gerenciamento de Acesso ao AVA';
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
        .status-liberado { background-color: #D1FAE5; color: #059669; }
        .status-revogado { background-color: #FEE2E2; color: #DC2626; }
        .status-nao_configurado { background-color: #F3F4F6; color: #6B7280; }

        /* Estilos para melhorar a responsividade da tabela */
        @media (max-width: 1024px) {
            .table-container {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            .table-container table {
                min-width: 800px;
            }
        }

        /* Estilos para os botões de ação */
        .action-button {
            display: inline-flex;
            align-items: center;
            padding: 0.375rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 0.375rem;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .action-button i {
            margin-right: 0.25rem;
        }

        .action-button-green {
            background-color: #D1FAE5;
            color: #059669;
        }

        .action-button-green:hover {
            background-color: #A7F3D0;
        }

        .action-button-red {
            background-color: #FEE2E2;
            color: #DC2626;
        }

        .action-button-red:hover {
            background-color: #FECACA;
        }

        .action-button-blue {
            background-color: #DBEAFE;
            color: #2563EB;
        }

        .action-button-blue:hover {
            background-color: #BFDBFE;
        }

        /* Estilos para o campo de busca */
        .search-container {
            position: relative;
        }

        .search-container i {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6B7280;
        }

        .search-input {
            padding-left: 2.5rem;
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

                    <!-- Lista de Polos -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                                <h2 class="text-lg font-semibold text-gray-800">Polos Cadastrados</h2>

                                <!-- Formulário de busca e filtros -->
                                <form action="ava_gerenciar_acesso.php" method="get" class="flex flex-col sm:flex-row gap-2 w-full md:w-auto">
                                    <div class="flex-1 min-w-[200px] search-container">
                                        <i class="fas fa-search"></i>
                                        <input type="text" name="busca" value="<?php echo htmlspecialchars($busca); ?>" placeholder="Buscar polo..." class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 search-input">
                                    </div>
                                    <div>
                                        <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                            <option value="">Todos os status</option>
                                            <option value="liberado" <?php echo $filtro_status === 'liberado' ? 'selected' : ''; ?>>Liberado</option>
                                            <option value="revogado" <?php echo $filtro_status === 'revogado' ? 'selected' : ''; ?>>Revogado</option>
                                            <option value="nao_configurado" <?php echo $filtro_status === 'nao_configurado' ? 'selected' : ''; ?>>Não Configurado</option>
                                        </select>
                                    </div>
                                    <div class="flex gap-2">
                                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 flex items-center">
                                            <i class="fas fa-search mr-1"></i> Buscar
                                        </button>
                                        <?php if (!empty($busca) || !empty($filtro_status)): ?>
                                        <a href="ava_gerenciar_acesso.php" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 flex items-center">
                                            <i class="fas fa-times mr-1"></i> Limpar
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="p-6">
                            <?php if (empty($polos)): ?>
                            <div class="text-center text-gray-500 py-4">
                                <p>Nenhum polo encontrado.</p>
                            </div>
                            <?php else: ?>
                            <div class="overflow-x-auto table-container">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Polo</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status AVA</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Responsável</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contato</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Liberado Por</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data Liberação</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($polos as $polo): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                <div class="flex flex-wrap justify-center gap-2">
                                                    <?php if ($polo['status_ava'] !== 'liberado'): ?>
                                                    <a href="#" onclick="abrirModalLiberar(<?php echo $polo['id']; ?>, '<?php echo htmlspecialchars($polo['nome']); ?>')" class="action-button action-button-green">
                                                        <i class="fas fa-check"></i> Liberar
                                                    </a>
                                                    <?php else: ?>
                                                    <a href="#" onclick="abrirModalRevogar(<?php echo $polo['id']; ?>, '<?php echo htmlspecialchars($polo['nome']); ?>')" class="action-button action-button-red">
                                                        <i class="fas fa-times"></i> Revogar
                                                    </a>
                                                    <?php endif; ?>

                                                    <?php if ($polo['status_ava'] === 'liberado'): ?>
                                                    <a href="ava_cursos_polo.php?polo_id=<?php echo $polo['id']; ?>" class="action-button action-button-blue">
                                                        <i class="fas fa-book"></i> Cursos
                                                    </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($polo['nome'] ?? 'N/A'); ?></div>
                                                <div class="text-sm text-gray-500">
                                                    <?php
                                                    $localizacao = [];
                                                    if (!empty($polo['cidade'])) $localizacao[] = $polo['cidade'];
                                                    if (!empty($polo['estado'])) $localizacao[] = $polo['estado'];
                                                    echo htmlspecialchars(implode('/', $localizacao) ?: 'Localização não informada');
                                                    ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="status-badge status-<?php echo $polo['status_ava']; ?>">
                                                    <?php
                                                    if ($polo['status_ava'] === 'liberado') echo 'Liberado';
                                                    elseif ($polo['status_ava'] === 'revogado') echo 'Revogado';
                                                    else echo 'Não Configurado';
                                                    ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($polo['responsavel'] ?? 'Não informado'); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($polo['email'] ?? 'Não informado'); ?></div>
                                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($polo['telefone'] ?? 'Não informado'); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($polo['liberado_por_nome'] ?? 'N/A'); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">
                                                    <?php echo $polo['data_liberacao'] ? date('d/m/Y H:i', strtotime($polo['data_liberacao'])) : 'N/A'; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Paginação -->
                            <?php if ($total_paginas > 1): ?>
                            <div class="mt-6 flex justify-between items-center">
                                <div class="text-sm text-gray-700">
                                    Mostrando <span class="font-medium"><?php echo min(($pagina_atual - 1) * $itens_por_pagina + 1, $total_registros); ?></span> a
                                    <span class="font-medium"><?php echo min($pagina_atual * $itens_por_pagina, $total_registros); ?></span> de
                                    <span class="font-medium"><?php echo $total_registros; ?></span> resultados
                                </div>
                                <div class="flex space-x-1">
                                    <?php
                                    // Parâmetros da URL para manter filtros na paginação
                                    $url_params = [];
                                    if (!empty($busca)) $url_params[] = "busca=" . urlencode($busca);
                                    if (!empty($filtro_status)) $url_params[] = "status=" . urlencode($filtro_status);
                                    $url_params_str = !empty($url_params) ? '&' . implode('&', $url_params) : '';

                                    // Botão Anterior
                                    if ($pagina_atual > 1): ?>
                                    <a href="?pagina=<?php echo $pagina_atual - 1 . $url_params_str; ?>" class="px-3 py-1 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                                        Anterior
                                    </a>
                                    <?php else: ?>
                                    <span class="px-3 py-1 bg-gray-100 border border-gray-300 rounded-md text-sm font-medium text-gray-400 cursor-not-allowed">
                                        Anterior
                                    </span>
                                    <?php endif; ?>

                                    <?php
                                    // Determinar quais páginas mostrar
                                    $start_page = max(1, $pagina_atual - 2);
                                    $end_page = min($total_paginas, $pagina_atual + 2);

                                    // Mostrar primeira página se estiver muito longe
                                    if ($start_page > 1): ?>
                                    <a href="?pagina=1<?php echo $url_params_str; ?>" class="px-3 py-1 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                                        1
                                    </a>
                                    <?php if ($start_page > 2): ?>
                                    <span class="px-3 py-1 text-gray-500">...</span>
                                    <?php endif; ?>
                                    <?php endif; ?>

                                    <?php
                                    // Páginas numeradas
                                    for ($i = $start_page; $i <= $end_page; $i++): ?>
                                    <?php if ($i == $pagina_atual): ?>
                                    <span class="px-3 py-1 bg-blue-600 border border-blue-600 rounded-md text-sm font-medium text-white">
                                        <?php echo $i; ?>
                                    </span>
                                    <?php else: ?>
                                    <a href="?pagina=<?php echo $i . $url_params_str; ?>" class="px-3 py-1 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                                        <?php echo $i; ?>
                                    </a>
                                    <?php endif; ?>
                                    <?php endfor; ?>

                                    <?php
                                    // Mostrar última página se estiver muito longe
                                    if ($end_page < $total_paginas): ?>
                                    <?php if ($end_page < $total_paginas - 1): ?>
                                    <span class="px-3 py-1 text-gray-500">...</span>
                                    <?php endif; ?>
                                    <a href="?pagina=<?php echo $total_paginas . $url_params_str; ?>" class="px-3 py-1 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                                        <?php echo $total_paginas; ?>
                                    </a>
                                    <?php endif; ?>

                                    <!-- Botão Próximo -->
                                    <?php if ($pagina_atual < $total_paginas): ?>
                                    <a href="?pagina=<?php echo $pagina_atual + 1 . $url_params_str; ?>" class="px-3 py-1 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                                        Próximo
                                    </a>
                                    <?php else: ?>
                                    <span class="px-3 py-1 bg-gray-100 border border-gray-300 rounded-md text-sm font-medium text-gray-400 cursor-not-allowed">
                                        Próximo
                                    </span>
                                    <?php endif; ?>
                                </div>
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

    <!-- Modal Liberar Acesso -->
    <div id="modalLiberar" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Liberar Acesso ao AVA</h3>
                <button type="button" onclick="fecharModalLiberar()" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="formLiberar" action="" method="post">
                <p class="mb-4">Você está prestes a liberar o acesso ao AVA para o polo <span id="poloNomeLiberar" class="font-semibold"></span>.</p>

                <div class="mb-4">
                    <label for="observacoesLiberar" class="block text-sm font-medium text-gray-700 mb-1">Observações (opcional)</label>
                    <textarea id="observacoesLiberar" name="observacoes" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50"></textarea>
                </div>

                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="fecharModalLiberar()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">Cancelar</button>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">Liberar Acesso</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Revogar Acesso -->
    <div id="modalRevogar" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Revogar Acesso ao AVA</h3>
                <button type="button" onclick="fecharModalRevogar()" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="formRevogar" action="" method="post">
                <p class="mb-4">Você está prestes a revogar o acesso ao AVA para o polo <span id="poloNomeRevogar" class="font-semibold"></span>.</p>
                <p class="mb-4 text-red-600">Atenção: Esta ação impedirá que o polo acesse o AVA, mas não excluirá os cursos e conteúdos já cadastrados.</p>

                <div class="mb-4">
                    <label for="observacoesRevogar" class="block text-sm font-medium text-gray-700 mb-1">Motivo da revogação (opcional)</label>
                    <textarea id="observacoesRevogar" name="observacoes" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50"></textarea>
                </div>

                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="fecharModalRevogar()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">Cancelar</button>
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">Revogar Acesso</button>
                </div>
            </form>
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

        // Modal Liberar Acesso
        function abrirModalLiberar(poloId, poloNome) {
            document.getElementById('poloNomeLiberar').textContent = poloNome;
            document.getElementById('formLiberar').action = 'ava_gerenciar_acesso.php?action=liberar&polo_id=' + poloId;
            document.getElementById('modalLiberar').classList.remove('hidden');
        }

        function fecharModalLiberar() {
            document.getElementById('modalLiberar').classList.add('hidden');
        }

        // Modal Revogar Acesso
        function abrirModalRevogar(poloId, poloNome) {
            document.getElementById('poloNomeRevogar').textContent = poloNome;
            document.getElementById('formRevogar').action = 'ava_gerenciar_acesso.php?action=revogar&polo_id=' + poloId;
            document.getElementById('modalRevogar').classList.remove('hidden');
        }

        function fecharModalRevogar() {
            document.getElementById('modalRevogar').classList.add('hidden');
        }
    </script>
</body>
</html>
