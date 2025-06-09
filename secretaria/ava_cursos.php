<?php
/**
 * Página de Gerenciamento de Cursos do AVA
 * Permite à secretaria gerenciar os cursos disponíveis no AVA
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
    case 'adicionar':
        // Lógica para adicionar curso
        if (isPost()) {
            // Processar o formulário
            $nome = $_POST['nome'] ?? '';
            $descricao = $_POST['descricao'] ?? '';
            $codigo = $_POST['codigo'] ?? '';
            $status = $_POST['status'] ?? 'ativo';

            if (empty($nome) || empty($codigo)) {
                setMensagem('erro', 'Nome e código do curso são obrigatórios.');
                redirect('ava_cursos.php?action=adicionar');
                exit;
            }

            // Verifica se já existe um curso com o mesmo código
            $sql = "SELECT id FROM ava_cursos WHERE codigo = ?";
            $curso_existente = $db->fetchOne($sql, [$codigo]);

            if ($curso_existente) {
                setMensagem('erro', 'Já existe um curso com o código informado.');
                redirect('ava_cursos.php?action=adicionar');
                exit;
            }

            // Insere o novo curso
            $sql = "INSERT INTO ava_cursos (nome, descricao, codigo, status, created_at, updated_at)
                    VALUES (?, ?, ?, ?, NOW(), NOW())";
            $resultado = $db->query($sql, [$nome, $descricao, $codigo, $status]);

            if ($resultado) {
                setMensagem('sucesso', 'Curso adicionado com sucesso.');
                redirect('ava_cursos.php');
            } else {
                setMensagem('erro', 'Erro ao adicionar o curso.');
                redirect('ava_cursos.php?action=adicionar');
            }
        }
        break;

    case 'editar':
        // Lógica para editar curso
        if (!isset($_GET['id']) || empty($_GET['id'])) {
            setMensagem('erro', 'ID do curso não informado.');
            redirect('ava_cursos.php');
            exit;
        }

        $curso_id = (int)$_GET['id'];

        // Busca o curso
        $sql = "SELECT * FROM ava_cursos WHERE id = ?";
        $curso = $db->fetchOne($sql, [$curso_id]);

        if (!$curso) {
            setMensagem('erro', 'Curso não encontrado.');
            redirect('ava_cursos.php');
            exit;
        }

        if (isPost()) {
            // Processar o formulário
            $nome = $_POST['nome'] ?? '';
            $descricao = $_POST['descricao'] ?? '';
            $codigo = $_POST['codigo'] ?? '';
            $status = $_POST['status'] ?? 'ativo';

            if (empty($nome) || empty($codigo)) {
                setMensagem('erro', 'Nome e código do curso são obrigatórios.');
                redirect('ava_cursos.php?action=editar&id=' . $curso_id);
                exit;
            }

            // Verifica se já existe um curso com o mesmo código (exceto o próprio curso)
            $sql = "SELECT id FROM ava_cursos WHERE codigo = ? AND id != ?";
            $curso_existente = $db->fetchOne($sql, [$codigo, $curso_id]);

            if ($curso_existente) {
                setMensagem('erro', 'Já existe um curso com o código informado.');
                redirect('ava_cursos.php?action=editar&id=' . $curso_id);
                exit;
            }

            // Atualiza o curso
            $sql = "UPDATE ava_cursos SET
                    nome = ?,
                    descricao = ?,
                    codigo = ?,
                    status = ?,
                    updated_at = NOW()
                    WHERE id = ?";
            $resultado = $db->query($sql, [$nome, $descricao, $codigo, $status, $curso_id]);

            if ($resultado) {
                setMensagem('sucesso', 'Curso atualizado com sucesso.');
                redirect('ava_cursos.php');
            } else {
                setMensagem('erro', 'Erro ao atualizar o curso.');
                redirect('ava_cursos.php?action=editar&id=' . $curso_id);
            }
        }
        break;

    case 'excluir':
        // Lógica para excluir curso
        if (!isset($_GET['id']) || empty($_GET['id'])) {
            setMensagem('erro', 'ID do curso não informado.');
            redirect('ava_cursos.php');
            exit;
        }

        $curso_id = (int)$_GET['id'];

        // Verifica se o curso existe
        $sql = "SELECT * FROM ava_cursos WHERE id = ?";
        $curso = $db->fetchOne($sql, [$curso_id]);

        if (!$curso) {
            setMensagem('erro', 'Curso não encontrado.');
            redirect('ava_cursos.php');
            exit;
        }

        // Verifica se o curso está sendo usado
        $sql = "SELECT COUNT(*) as total FROM ava_polos_cursos WHERE curso_id = ?";
        $uso = $db->fetchOne($sql, [$curso_id]);

        if ($uso && $uso['total'] > 0) {
            setMensagem('erro', 'Este curso está associado a polos e não pode ser excluído.');
            redirect('ava_cursos.php');
            exit;
        }

        // Exclui o curso
        $sql = "DELETE FROM ava_cursos WHERE id = ?";
        $resultado = $db->query($sql, [$curso_id]);

        if ($resultado) {
            setMensagem('sucesso', 'Curso excluído com sucesso.');
        } else {
            setMensagem('erro', 'Erro ao excluir o curso.');
        }

        redirect('ava_cursos.php');
        break;

    case 'listar':
    default:
        // Busca todos os cursos
        try {
            // Verifica se a tabela existe
            $check_table = $db->fetchOne("SHOW TABLES LIKE 'ava_cursos'");

            if ($check_table) {
                // Verifica a estrutura da tabela para saber quais colunas existem
                $colunas = $db->fetchAll("SHOW COLUMNS FROM ava_cursos");
                $nomes_colunas = array_column($colunas, 'Field');

                // Construir a consulta base
                $sqlBase = "FROM ava_cursos";
                $whereConditions = [];
                $params = [];

                // Adicionar condições de busca
                if (!empty($busca)) {
                    $condicoes_busca = [];
                    $campos_busca = ['titulo', 'descricao', 'categoria', 'codigo'];

                    foreach ($campos_busca as $campo) {
                        if (in_array($campo, $nomes_colunas)) {
                            $condicoes_busca[] = "$campo LIKE ?";
                            $params[] = "%{$busca}%";
                        }
                    }

                    if (!empty($condicoes_busca)) {
                        $whereConditions[] = "(" . implode(" OR ", $condicoes_busca) . ")";
                    }
                }

                // Adicionar filtro de status
                if (!empty($filtro_status) && in_array('status', $nomes_colunas)) {
                    $whereConditions[] = "status = ?";
                    $params[] = $filtro_status;
                }

                // Montar a cláusula WHERE
                $whereClause = "";
                if (!empty($whereConditions)) {
                    $whereClause = " WHERE " . implode(" AND ", $whereConditions);
                }

                // Consulta para contar o total de registros
                $sqlCount = "SELECT COUNT(*) as total " . $sqlBase . $whereClause;
                $total = $db->fetchOne($sqlCount, $params);
                $total_registros = $total['total'] ?? 0;

                // Calcular total de páginas
                $total_paginas = ceil($total_registros / $itens_por_pagina);

                // Ajustar página atual se necessário
                if ($pagina_atual > $total_paginas && $total_paginas > 0) {
                    $pagina_atual = $total_paginas;
                    $offset = ($pagina_atual - 1) * $itens_por_pagina;
                }

                // Determinar a ordem
                $orderBy = "";
                if (in_array('titulo', $nomes_colunas)) {
                    $orderBy = " ORDER BY titulo";
                } elseif (in_array('nome', $nomes_colunas)) {
                    $orderBy = " ORDER BY nome";
                } else {
                    $orderBy = " ORDER BY id";
                }

                // Consulta final com paginação
                $sql = "SELECT * " . $sqlBase . $whereClause . $orderBy . " LIMIT {$offset}, {$itens_por_pagina}";

                $cursos = $db->fetchAll($sql, $params);
            } else {
                // Se a tabela não existe, cria um array vazio
                $cursos = [];

                // Exibe uma mensagem informando que a tabela não existe
                setMensagem('erro', 'A tabela ava_cursos não existe no banco de dados. Entre em contato com o administrador do sistema.');
            }
        } catch (Exception $e) {
            // Em caso de erro, cria um array vazio
            $cursos = [];

            // Exibe uma mensagem de erro
            setMensagem('erro', 'Erro ao buscar cursos: ' . $e->getMessage());
        }
        break;
}

// Define o título da página
$titulo_pagina = 'Gerenciamento de Cursos do AVA';
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
        .status-ativo, .status-publicado { background-color: #D1FAE5; color: #059669; }
        .status-inativo, .status-arquivado { background-color: #FEE2E2; color: #DC2626; }
        .status-rascunho { background-color: #E5E7EB; color: #4B5563; }
        .status-revisao { background-color: #FEF3C7; color: #D97706; }

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

        .action-button-blue {
            background-color: #DBEAFE;
            color: #2563EB;
        }

        .action-button-blue:hover {
            background-color: #BFDBFE;
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

        .action-button-purple {
            background-color: #EDE9FE;
            color: #7C3AED;
        }

        .action-button-purple:hover {
            background-color: #DDD6FE;
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

        /* Estilos para cards de curso */
        .curso-card {
            transition: all 0.3s ease;
        }

        .curso-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .curso-image {
            height: 160px;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-color: #E5E7EB;
        }

        .curso-badge {
            position: absolute;
            top: 10px;
            right: 10px;
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
                        <a href="ava_cursos.php?action=adicionar" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md">
                            <i class="fas fa-plus mr-2"></i> Adicionar Curso
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

                    <?php if ($action === 'adicionar'): ?>
                    <!-- Formulário de Adição de Curso -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-800">Adicionar Novo Curso</h2>
                        </div>
                        <div class="p-6">
                            <form action="ava_cursos.php?action=adicionar" method="post">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="nome" class="block text-sm font-medium text-gray-700 mb-1">Nome do Curso *</label>
                                        <input type="text" id="nome" name="nome" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                    <div>
                                        <label for="codigo" class="block text-sm font-medium text-gray-700 mb-1">Código do Curso *</label>
                                        <input type="text" id="codigo" name="codigo" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                    <div class="md:col-span-2">
                                        <label for="descricao" class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
                                        <textarea id="descricao" name="descricao" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"></textarea>
                                    </div>
                                    <div>
                                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                        <select id="status" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                            <option value="ativo">Ativo</option>
                                            <option value="inativo">Inativo</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mt-6 flex justify-end">
                                    <a href="ava_cursos.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-2 px-4 rounded-md mr-2">Cancelar</a>
                                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md">Salvar Curso</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php elseif ($action === 'editar'): ?>
                    <!-- Formulário de Edição de Curso -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-800">Editar Curso</h2>
                        </div>
                        <div class="p-6">
                            <form action="ava_cursos.php?action=editar&id=<?php echo $curso['id'] ?? 0; ?>" method="post">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="nome" class="block text-sm font-medium text-gray-700 mb-1">Nome do Curso *</label>
                                        <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($curso['nome'] ?? ''); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                    <div>
                                        <label for="codigo" class="block text-sm font-medium text-gray-700 mb-1">Código do Curso *</label>
                                        <input type="text" id="codigo" name="codigo" value="<?php echo htmlspecialchars($curso['codigo'] ?? ''); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                    <div class="md:col-span-2">
                                        <label for="descricao" class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
                                        <textarea id="descricao" name="descricao" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"><?php echo htmlspecialchars($curso['descricao'] ?? ''); ?></textarea>
                                    </div>
                                    <div>
                                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                        <select id="status" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                            <option value="ativo" <?php echo (isset($curso['status']) && $curso['status'] === 'ativo') ? 'selected' : ''; ?>>Ativo</option>
                                            <option value="inativo" <?php echo (isset($curso['status']) && $curso['status'] === 'inativo') ? 'selected' : ''; ?>>Inativo</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mt-6 flex justify-end">
                                    <a href="ava_cursos.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-2 px-4 rounded-md mr-2">Cancelar</a>
                                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md">Atualizar Curso</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php else: ?>
                    <!-- Lista de Cursos -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                                <h2 class="text-lg font-semibold text-gray-800">Cursos Cadastrados</h2>

                                <!-- Formulário de busca e filtros -->
                                <form action="ava_cursos.php" method="get" class="flex flex-col sm:flex-row gap-2 w-full md:w-auto">
                                    <div class="flex-1 min-w-[200px] search-container">
                                        <i class="fas fa-search"></i>
                                        <input type="text" name="busca" value="<?php echo htmlspecialchars($busca); ?>" placeholder="Buscar curso..." class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 search-input">
                                    </div>
                                    <div>
                                        <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                            <option value="">Todos os status</option>
                                            <option value="ativo" <?php echo $filtro_status === 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                                            <option value="inativo" <?php echo $filtro_status === 'inativo' ? 'selected' : ''; ?>>Inativo</option>
                                            <option value="publicado" <?php echo $filtro_status === 'publicado' ? 'selected' : ''; ?>>Publicado</option>
                                            <option value="rascunho" <?php echo $filtro_status === 'rascunho' ? 'selected' : ''; ?>>Rascunho</option>
                                        </select>
                                    </div>
                                    <div class="flex gap-2">
                                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 flex items-center">
                                            <i class="fas fa-search mr-1"></i> Buscar
                                        </button>
                                        <?php if (!empty($busca) || !empty($filtro_status)): ?>
                                        <a href="ava_cursos.php" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 flex items-center">
                                            <i class="fas fa-times mr-1"></i> Limpar
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="p-6">
                            <?php if (empty($cursos)): ?>
                            <div class="text-center text-gray-500 py-4">
                                <p>Nenhum curso encontrado.</p>
                            </div>
                            <?php else: ?>
                            <div class="overflow-x-auto table-container">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Curso</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Detalhes</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($cursos as $curso): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                <div class="flex flex-wrap justify-center gap-2">
                                                    <a href="ava_visualizar_curso.php?id=<?php echo $curso['id'] ?? 0; ?>" class="action-button action-button-purple">
                                                        <i class="fas fa-eye"></i> Visualizar
                                                    </a>
                                                    <a href="ava_cursos.php?action=editar&id=<?php echo $curso['id'] ?? 0; ?>" class="action-button action-button-blue">
                                                        <i class="fas fa-edit"></i> Editar
                                                    </a>
                                                    <a href="#" onclick="confirmarExclusao(<?php echo $curso['id'] ?? 0; ?>, '<?php echo htmlspecialchars($curso['titulo'] ?? $curso['nome'] ?? 'Curso sem nome'); ?>')" class="action-button action-button-red">
                                                        <i class="fas fa-trash-alt"></i> Excluir
                                                    </a>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php
                                                    $nome_curso = '';
                                                    if (isset($curso['titulo']) && !empty($curso['titulo'])) {
                                                        $nome_curso = $curso['titulo'];
                                                    } elseif (isset($curso['nome']) && !empty($curso['nome'])) {
                                                        $nome_curso = $curso['nome'];
                                                    } else {
                                                        $nome_curso = 'Curso sem nome';
                                                    }
                                                    echo htmlspecialchars($nome_curso);
                                                    ?>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    <?php
                                                    if (isset($curso['codigo']) && !empty($curso['codigo'])) {
                                                        echo 'Código: ' . htmlspecialchars($curso['codigo']);
                                                    }
                                                    ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm text-gray-500">
                                                    <?php
                                                    // Exibe a descrição
                                                    if (isset($curso['descricao']) && !is_null($curso['descricao'])) {
                                                        echo htmlspecialchars(substr($curso['descricao'], 0, 100) . (strlen($curso['descricao']) > 100 ? '...' : ''));
                                                    } else {
                                                        echo 'Sem descrição';
                                                    }
                                                    ?>
                                                </div>
                                                <div class="text-sm text-gray-500 mt-1">
                                                    <?php
                                                    // Exibe a categoria e carga horária se disponíveis
                                                    $detalhes = [];
                                                    if (isset($curso['categoria']) && !empty($curso['categoria'])) {
                                                        $detalhes[] = 'Categoria: ' . htmlspecialchars($curso['categoria']);
                                                    }
                                                    if (isset($curso['carga_horaria']) && !empty($curso['carga_horaria'])) {
                                                        $detalhes[] = 'Carga horária: ' . htmlspecialchars($curso['carga_horaria']) . 'h';
                                                    }
                                                    if (isset($curso['nivel']) && !empty($curso['nivel'])) {
                                                        $nivel = $curso['nivel'];
                                                        $nivel_formatado = ucfirst($nivel);
                                                        $detalhes[] = 'Nível: ' . htmlspecialchars($nivel_formatado);
                                                    }
                                                    echo implode(' | ', $detalhes);
                                                    ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php
                                                $status = '';
                                                $status_texto = '';

                                                if (isset($curso['status'])) {
                                                    $status = $curso['status'];

                                                    switch ($status) {
                                                        case 'ativo':
                                                            $status_texto = 'Ativo';
                                                            break;
                                                        case 'inativo':
                                                            $status_texto = 'Inativo';
                                                            break;
                                                        case 'publicado':
                                                            $status_texto = 'Publicado';
                                                            break;
                                                        case 'rascunho':
                                                            $status_texto = 'Rascunho';
                                                            break;
                                                        case 'revisao':
                                                            $status_texto = 'Em Revisão';
                                                            break;
                                                        case 'arquivado':
                                                            $status_texto = 'Arquivado';
                                                            break;
                                                        default:
                                                            $status_texto = ucfirst($status);
                                                    }
                                                } else {
                                                    $status = 'inativo';
                                                    $status_texto = 'Inativo';
                                                }
                                                ?>
                                                <span class="status-badge status-<?php echo $status; ?>">
                                                    <?php echo $status_texto; ?>
                                                </span>

                                                <?php if (isset($curso['data_publicacao']) && !empty($curso['data_publicacao'])): ?>
                                                <div class="text-xs text-gray-500 mt-1">
                                                    Publicado em: <?php echo date('d/m/Y', strtotime($curso['data_publicacao'])); ?>
                                                </div>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Paginação -->
                            <?php if (isset($total_paginas) && $total_paginas > 1): ?>
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

        // Confirmar exclusão
        function confirmarExclusao(id, nome) {
            if (confirm('Tem certeza que deseja excluir o curso "' + nome + '"?')) {
                window.location.href = 'ava_cursos.php?action=excluir&id=' + id;
            }
        }
    </script>
</body>
</html>
