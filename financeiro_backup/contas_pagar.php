<?php
// Inicializa o sistema
require_once __DIR__ . '/../includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica se o usuário tem permissão para acessar o módulo financeiro
if (!Auth::hasPermission('financeiro', 'visualizar')) {
    setMensagem('erro', 'Você não tem permissão para acessar o módulo financeiro.');
    redirect('../index.php');
    exit;
}

// Instancia o banco de dados
$db = Database::getInstance();

// Define a ação padrão
$action = $_GET['action'] ?? 'listar';

// Processa as ações
switch ($action) {
    case 'nova':
        // Verifica permissão para criar
        if (!Auth::hasPermission('financeiro', 'criar')) {
            setMensagem('erro', 'Você não tem permissão para criar contas a pagar.');
            redirect('contas_pagar.php');
            exit;
        }

        // Carrega as categorias de despesa
        $sql = "SELECT * FROM categorias_financeiras WHERE tipo = 'despesa' ORDER BY nome";
        $categorias = $db->fetchAll($sql);

        // Define o título da página
        $titulo_pagina = 'Nova Conta a Pagar';
        $view = 'form';
        break;

    case 'editar':
        // Verifica permissão para editar
        if (!Auth::hasPermission('financeiro', 'editar')) {
            setMensagem('erro', 'Você não tem permissão para editar contas a pagar.');
            redirect('contas_pagar.php');
            exit;
        }

        // Verifica se o ID foi informado
        if (!isset($_GET['id']) || empty($_GET['id'])) {
            setMensagem('erro', 'ID da conta a pagar não informado.');
            redirect('contas_pagar.php');
            exit;
        }

        // Busca a conta a pagar
        $id = (int)$_GET['id'];
        $sql = "SELECT * FROM contas_pagar WHERE id = ?";
        $conta = $db->fetchOne($sql, [$id]);

        if (!$conta) {
            setMensagem('erro', 'Conta a pagar não encontrada.');
            redirect('contas_pagar.php');
            exit;
        }

        // Carrega as categorias de despesa
        $sql = "SELECT * FROM categorias_financeiras WHERE tipo = 'despesa' ORDER BY nome";
        $categorias = $db->fetchAll($sql);

        // Define o título da página
        $titulo_pagina = 'Editar Conta a Pagar';
        $view = 'form';
        break;

    case 'salvar':
        // Verifica permissão para criar/editar
        if (!Auth::hasPermission('financeiro', 'criar') && !Auth::hasPermission('financeiro', 'editar')) {
            setMensagem('erro', 'Você não tem permissão para criar/editar contas a pagar.');
            redirect('contas_pagar.php');
            exit;
        }

        // Verifica se é POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            setMensagem('erro', 'Método inválido.');
            redirect('contas_pagar.php');
            exit;
        }

        // Obtém os dados do formulário
        $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
        $descricao = $_POST['descricao'] ?? '';
        $valor = str_replace(',', '.', $_POST['valor'] ?? 0);
        $data_vencimento = $_POST['data_vencimento'] ?? date('Y-m-d');
        $data_pagamento = !empty($_POST['data_pagamento']) ? $_POST['data_pagamento'] : null;
        $fornecedor_id = !empty($_POST['fornecedor_id']) ? (int)$_POST['fornecedor_id'] : null;
        $fornecedor_nome = $_POST['fornecedor_nome'] ?? null;
        $categoria_id = !empty($_POST['categoria_id']) ? (int)$_POST['categoria_id'] : null;
        $forma_pagamento = $_POST['forma_pagamento'] ?? null;
        $status = $_POST['status'] ?? 'pendente';
        $observacoes = $_POST['observacoes'] ?? null;

        // Validação básica
        $erros = [];

        if (empty($descricao)) {
            $erros[] = 'A descrição é obrigatória.';
        }

        if (empty($valor) || !is_numeric($valor) || $valor <= 0) {
            $erros[] = 'O valor deve ser um número maior que zero.';
        }

        if (empty($data_vencimento) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_vencimento)) {
            $erros[] = 'A data de vencimento é obrigatória e deve estar no formato correto.';
        }

        if (!empty($data_pagamento) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_pagamento)) {
            $erros[] = 'A data de pagamento deve estar no formato correto.';
        }

        // Se houver erros, redireciona de volta para o formulário
        if (!empty($erros)) {
            $_SESSION['form_data'] = $_POST;
            $_SESSION['form_errors'] = $erros;

            if ($id) {
                redirect("contas_pagar.php?action=editar&id=$id");
            } else {
                redirect('contas_pagar.php?action=nova');
            }
            exit;
        }

        try {
            // Inicia a transação
            $db->beginTransaction();

            // Prepara os dados para inserção/atualização
            $dados = [
                'descricao' => $descricao,
                'valor' => $valor,
                'data_vencimento' => $data_vencimento,
                'data_pagamento' => $data_pagamento,
                'fornecedor_id' => $fornecedor_id,
                'fornecedor_nome' => $fornecedor_nome,
                'categoria_id' => $categoria_id,
                'forma_pagamento' => $forma_pagamento,
                'status' => $status,
                'observacoes' => $observacoes,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            // Log para debug
            error_log("Dados para salvar conta a pagar: " . json_encode($dados));

            // Upload de comprovante, se houver
            if (isset($_FILES['comprovante']) && $_FILES['comprovante']['error'] === UPLOAD_ERR_OK) {
                $diretorio_upload = '../uploads/financeiro/';
                if (!is_dir($diretorio_upload)) {
                    mkdir($diretorio_upload, 0755, true);
                }

                $nome_arquivo = uniqid() . '_' . basename($_FILES['comprovante']['name']);
                $caminho_arquivo = $diretorio_upload . $nome_arquivo;

                if (move_uploaded_file($_FILES['comprovante']['tmp_name'], $caminho_arquivo)) {
                    $dados['comprovante_path'] = 'uploads/financeiro/' . $nome_arquivo;
                }
            }

            // Se a conta foi paga, cria uma transação
            if ($status === 'pago' && $data_pagamento) {
                // Verifica se já existe uma transação associada
                $transacao_id = null;
                if ($id) {
                    $sql = "SELECT transacao_id FROM contas_pagar WHERE id = ?";
                    $result = $db->fetchOne($sql, [$id]);
                    $transacao_id = $result ? $result['transacao_id'] : null;
                }

                // Dados da transação
                $dados_transacao = [
                    'tipo' => 'despesa',
                    'descricao' => "Pagamento: $descricao",
                    'valor' => $valor,
                    'data_transacao' => $data_pagamento,
                    'categoria_id' => $categoria_id,
                    'forma_pagamento' => $forma_pagamento,
                    'status' => 'efetivada',
                    'observacoes' => $observacoes,
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                if (isset($dados['comprovante_path'])) {
                    $dados_transacao['comprovante_path'] = $dados['comprovante_path'];
                }

                // Atualiza ou insere a transação
                if ($transacao_id) {
                    $db->update('transacoes', $dados_transacao, 'id = ?', [$transacao_id]);
                } else {
                    $dados_transacao['created_at'] = date('Y-m-d H:i:s');
                    $transacao_id = $db->insert('transacoes', $dados_transacao);
                    $dados['transacao_id'] = $transacao_id;
                }
            } else if ($status !== 'pago') {
                // Se a conta não foi paga, remove a transação associada, se houver
                if ($id) {
                    $sql = "SELECT transacao_id FROM contas_pagar WHERE id = ?";
                    $result = $db->fetchOne($sql, [$id]);
                    $transacao_id = $result ? $result['transacao_id'] : null;

                    if ($transacao_id) {
                        $db->delete('transacoes', 'id = ?', [$transacao_id]);
                        $dados['transacao_id'] = null;
                    }
                }
            }

            // Atualiza ou insere a conta a pagar
            if ($id) {
                $result = $db->update('contas_pagar', $dados, 'id = ?', [$id]);
                error_log("Atualização de conta a pagar - ID: $id, Resultado: " . ($result ? "sucesso" : "falha"));
                $mensagem = 'Conta a pagar atualizada com sucesso.';
            } else {
                $dados['created_at'] = date('Y-m-d H:i:s');
                try {
                    error_log("Tentando inserir conta a pagar: " . json_encode($dados));
                    $id = $db->insert('contas_pagar', $dados);
                    error_log("Inserção de conta a pagar - Novo ID: $id");
                    $mensagem = 'Conta a pagar cadastrada com sucesso.';
                } catch (Exception $insertEx) {
                    error_log("Erro ao inserir conta a pagar: " . $insertEx->getMessage());
                    throw $insertEx;
                }
            }

            // Confirma a transação
            $db->commit();

            // Define a mensagem de sucesso
            setMensagem('sucesso', $mensagem);

            // Redireciona para a listagem
            redirect('contas_pagar.php');
            exit;

        } catch (Exception $e) {
            // Desfaz a transação em caso de erro
            $db->rollBack();

            // Define a mensagem de erro
            setMensagem('erro', 'Erro ao salvar a conta a pagar: ' . $e->getMessage());

            // Redireciona de volta para o formulário
            if ($id) {
                redirect("contas_pagar.php?action=editar&id=$id");
            } else {
                redirect('contas_pagar.php?action=nova');
            }
            exit;
        }
        break;

    case 'excluir':
        // Verifica permissão para excluir
        if (!Auth::hasPermission('financeiro', 'excluir')) {
            setMensagem('erro', 'Você não tem permissão para excluir contas a pagar.');
            redirect('contas_pagar.php');
            exit;
        }

        // Verifica se o ID foi informado
        if (!isset($_GET['id']) || empty($_GET['id'])) {
            setMensagem('erro', 'ID da conta a pagar não informado.');
            redirect('contas_pagar.php');
            exit;
        }

        // Busca a conta a pagar
        $id = (int)$_GET['id'];
        $sql = "SELECT * FROM contas_pagar WHERE id = ?";
        $conta = $db->fetchOne($sql, [$id]);

        if (!$conta) {
            setMensagem('erro', 'Conta a pagar não encontrada.');
            redirect('contas_pagar.php');
            exit;
        }

        try {
            // Inicia a transação
            $db->beginTransaction();

            // Se houver uma transação associada, exclui também
            if ($conta['transacao_id']) {
                $db->delete('transacoes', 'id = ?', [$conta['transacao_id']]);
            }

            // Exclui a conta a pagar
            $db->delete('contas_pagar', 'id = ?', [$id]);

            // Confirma a transação
            $db->commit();

            // Define a mensagem de sucesso
            setMensagem('sucesso', 'Conta a pagar excluída com sucesso.');

        } catch (Exception $e) {
            // Desfaz a transação em caso de erro
            $db->rollBack();

            // Define a mensagem de erro
            setMensagem('erro', 'Erro ao excluir a conta a pagar: ' . $e->getMessage());
        }

        // Redireciona para a listagem
        redirect('contas_pagar.php');
        exit;
        break;

    case 'pagar':
        // Verifica permissão para editar
        if (!Auth::hasPermission('financeiro', 'editar')) {
            setMensagem('erro', 'Você não tem permissão para pagar contas.');
            redirect('contas_pagar.php');
            exit;
        }

        // Verifica se o ID foi informado
        if (!isset($_GET['id']) || empty($_GET['id'])) {
            setMensagem('erro', 'ID da conta a pagar não informado.');
            redirect('contas_pagar.php');
            exit;
        }

        // Busca a conta a pagar
        $id = (int)$_GET['id'];
        $sql = "SELECT * FROM contas_pagar WHERE id = ?";
        $conta = $db->fetchOne($sql, [$id]);

        if (!$conta) {
            setMensagem('erro', 'Conta a pagar não encontrada.');
            redirect('contas_pagar.php');
            exit;
        }

        // Se a conta já foi paga, redireciona
        if ($conta['status'] === 'pago') {
            setMensagem('info', 'Esta conta já foi paga.');
            redirect('contas_pagar.php');
            exit;
        }

        // Carrega as categorias de despesa
        $sql = "SELECT * FROM categorias_financeiras WHERE tipo = 'despesa' ORDER BY nome";
        $categorias = $db->fetchAll($sql);

        // Define o título da página
        $titulo_pagina = 'Pagar Conta';
        $view = 'pagar';
        break;

    case 'visualizar':
        // Verifica se o ID foi informado
        if (!isset($_GET['id']) || empty($_GET['id'])) {
            setMensagem('erro', 'ID da conta a pagar não informado.');
            redirect('contas_pagar.php');
            exit;
        }

        // Busca a conta a pagar com informações relacionadas
        $id = (int)$_GET['id'];
        $sql = "SELECT cp.*, c.nome as categoria_nome
                FROM contas_pagar cp
                LEFT JOIN categorias_financeiras c ON cp.categoria_id = c.id
                WHERE cp.id = ?";
        $conta = $db->fetchOne($sql, [$id]);

        if (!$conta) {
            setMensagem('erro', 'Conta a pagar não encontrada.');
            redirect('contas_pagar.php');
            exit;
        }

        // Define o título da página
        $titulo_pagina = 'Visualizar Conta a Pagar';
        $view = 'visualizar';
        break;

    case 'listar':
    default:
        // Parâmetros de filtro e paginação
        $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
        $por_pagina = 20;
        $offset = ($pagina - 1) * $por_pagina;

        // Filtros
        $filtros = [];
        $params = [];
        $where = [];

        // Filtro por status
        $status = $_GET['status'] ?? 'pendente';
        if ($status !== 'todos') {
            $where[] = "cp.status = ?";
            $params[] = $status;
        }
        $filtros['status'] = $status;

        // Filtro por categoria
        if (isset($_GET['categoria_id']) && !empty($_GET['categoria_id'])) {
            $where[] = "cp.categoria_id = ?";
            $params[] = (int)$_GET['categoria_id'];
            $filtros['categoria_id'] = (int)$_GET['categoria_id'];
        }

        // Filtro por fornecedor
        if (isset($_GET['fornecedor_id']) && !empty($_GET['fornecedor_id'])) {
            $where[] = "cp.fornecedor_id = ?";
            $params[] = (int)$_GET['fornecedor_id'];
            $filtros['fornecedor_id'] = (int)$_GET['fornecedor_id'];
        }

        // Filtro por período de vencimento
        if (isset($_GET['data_vencimento_inicio']) && !empty($_GET['data_vencimento_inicio'])) {
            $where[] = "cp.data_vencimento >= ?";
            $params[] = $_GET['data_vencimento_inicio'];
            $filtros['data_vencimento_inicio'] = $_GET['data_vencimento_inicio'];
        }

        if (isset($_GET['data_vencimento_fim']) && !empty($_GET['data_vencimento_fim'])) {
            $where[] = "cp.data_vencimento <= ?";
            $params[] = $_GET['data_vencimento_fim'];
            $filtros['data_vencimento_fim'] = $_GET['data_vencimento_fim'];
        }

        // Filtro por período de pagamento
        if (isset($_GET['data_pagamento_inicio']) && !empty($_GET['data_pagamento_inicio'])) {
            $where[] = "cp.data_pagamento >= ?";
            $params[] = $_GET['data_pagamento_inicio'];
            $filtros['data_pagamento_inicio'] = $_GET['data_pagamento_inicio'];
        }

        if (isset($_GET['data_pagamento_fim']) && !empty($_GET['data_pagamento_fim'])) {
            $where[] = "cp.data_pagamento <= ?";
            $params[] = $_GET['data_pagamento_fim'];
            $filtros['data_pagamento_fim'] = $_GET['data_pagamento_fim'];
        }

        // Filtro por termo de busca
        if (isset($_GET['termo']) && !empty($_GET['termo'])) {
            $where[] = "(cp.descricao LIKE ? OR cp.fornecedor_nome LIKE ? OR cp.observacoes LIKE ?)";
            $params[] = '%' . $_GET['termo'] . '%';
            $params[] = '%' . $_GET['termo'] . '%';
            $params[] = '%' . $_GET['termo'] . '%';
            $filtros['termo'] = $_GET['termo'];
        }

        // Monta a cláusula WHERE
        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        // Consulta para contar o total de registros
        $sql = "SELECT COUNT(*) as total FROM contas_pagar cp $whereClause";
        $resultado = $db->fetchOne($sql, $params);
        $total_registros = $resultado['total'] ?? 0;

        // Calcula o total de páginas
        $total_paginas = ceil($total_registros / $por_pagina);

        // Consulta para buscar as contas a pagar
        $sql = "SELECT cp.*, c.nome as categoria_nome
                FROM contas_pagar cp
                LEFT JOIN categorias_financeiras c ON cp.categoria_id = c.id
                $whereClause
                ORDER BY cp.data_vencimento ASC, cp.id DESC
                LIMIT $offset, $por_pagina";
        $contas = $db->fetchAll($sql, $params);

        // Carrega as categorias para o filtro
        $sql = "SELECT * FROM categorias_financeiras WHERE tipo = 'despesa' ORDER BY nome";
        $categorias = $db->fetchAll($sql);

        // Define o título da página
        $titulo_pagina = 'Contas a Pagar';
        $view = 'listar';
        break;
}

// Define o título da página
$titulo_pagina_completo = 'Faciência ERP - ' . $titulo_pagina;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo_pagina_completo; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .page-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 4rem;
            height: 0.25rem;
            background: linear-gradient(to right, #3b82f6, #8b5cf6);
            border-radius: 0.125rem;
        }

        /* Estilos específicos para contas a pagar */
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 9999px;
        }

        .status-pendente {
            background-color: #FEF3C7;
            color: #D97706;
        }

        .status-pago {
            background-color: #D1FAE5;
            color: #059669;
        }

        .status-cancelado {
            background-color: #FEE2E2;
            color: #DC2626;
        }

        .vencido {
            color: #DC2626;
            font-weight: 600;
        }

        .card {
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
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
                    <h1 class="text-3xl font-bold text-gray-800 mb-6 page-header relative pb-3"><?php echo $titulo_pagina; ?></h1>

                    <!-- Conteúdo da página -->
                    <?php
                    // Verifica se a view existe
                    $view_file = __DIR__ . "/views/contas_pagar/$view.php";
                    if (file_exists($view_file)) {
                        include $view_file;
                    } else {
                        echo '<div class="bg-white rounded-lg shadow-sm p-6">';
                        echo '<p class="text-gray-600">A visualização solicitada não foi encontrada. Por favor, crie o arquivo <strong>' . $view_file . '</strong>.</p>';
                        echo '<p class="mt-4"><a href="contas_pagar.php" class="text-blue-600 hover:text-blue-800">Voltar para a listagem</a></p>';
                        echo '</div>';
                    }
                    ?>
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

        // Close user menu when clicking outside
        document.addEventListener('click', function(event) {
            const menu = document.getElementById('user-menu');
            const button = document.getElementById('user-menu-button');

            if (!menu.contains(event.target) && !button.contains(event.target)) {
                menu.classList.add('hidden');
            }
        });

        // Datepicker initialization (if needed)
        if (typeof flatpickr !== 'undefined') {
            flatpickr('.datepicker', {
                dateFormat: 'Y-m-d',
                locale: 'pt'
            });
        }
    </script>
</body>
</html>
