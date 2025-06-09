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

// Define o tipo de transação (receita, despesa ou todos)
$tipo = $_GET['tipo'] ?? 'todos';

// Processa as ações
switch ($action) {
    case 'nova':
        // Verifica permissão para criar
        if (!Auth::hasPermission('financeiro', 'criar')) {
            setMensagem('erro', 'Você não tem permissão para criar transações.');
            redirect('transacoes.php');
            exit;
        }

        // Carrega as categorias
        $sql = "SELECT * FROM categorias_financeiras WHERE status = 'ativo'";
        if ($tipo !== 'todos') {
            $sql .= " AND tipo = ?";
            $categorias = $db->fetchAll($sql, [$tipo]);
        } else {
            $categorias = $db->fetchAll($sql);
        }

        // Carrega as contas bancárias
        $sql = "SELECT * FROM contas_bancarias WHERE status = 'ativo' ORDER BY nome";
        $contas = $db->fetchAll($sql);

        // Define o título da página
        $titulo_pagina = 'Nova Transação';
        $view = 'form';
        break;

    case 'editar':
        // Verifica permissão para editar
        if (!Auth::hasPermission('financeiro', 'editar')) {
            setMensagem('erro', 'Você não tem permissão para editar transações.');
            redirect('transacoes.php');
            exit;
        }

        // Verifica se o ID foi informado
        if (!isset($_GET['id']) || empty($_GET['id'])) {
            setMensagem('erro', 'ID da transação não informado.');
            redirect('transacoes.php');
            exit;
        }

        // Busca a transação
        $id = (int)$_GET['id'];
        $sql = "SELECT * FROM transacoes WHERE id = ?";
        $transacao = $db->fetchOne($sql, [$id]);

        if (!$transacao) {
            setMensagem('erro', 'Transação não encontrada.');
            redirect('transacoes.php');
            exit;
        }

        // Carrega as categorias
        $sql = "SELECT * FROM categorias_financeiras WHERE status = 'ativo'";
        $categorias = $db->fetchAll($sql);

        // Carrega as contas bancárias
        $sql = "SELECT * FROM contas_bancarias WHERE status = 'ativo' ORDER BY nome";
        $contas = $db->fetchAll($sql);

        // Define o título da página
        $titulo_pagina = 'Editar Transação';
        $view = 'form';
        break;

    case 'salvar':
        // Verifica permissão para criar/editar
        if (!Auth::hasPermission('financeiro', 'criar') && !Auth::hasPermission('financeiro', 'editar')) {
            setMensagem('erro', 'Você não tem permissão para criar/editar transações.');
            redirect('transacoes.php');
            exit;
        }

        // Verifica se é POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            setMensagem('erro', 'Método inválido.');
            redirect('transacoes.php');
            exit;
        }

        // Obtém os dados do formulário
        $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
        $tipo = $_POST['tipo'] ?? '';
        $descricao = $_POST['descricao'] ?? '';
        $valor = str_replace(',', '.', $_POST['valor'] ?? 0);
        $data_transacao = $_POST['data_transacao'] ?? date('Y-m-d');
        $categoria_id = !empty($_POST['categoria_id']) ? (int)$_POST['categoria_id'] : null;
        $conta_id = !empty($_POST['conta_id']) ? (int)$_POST['conta_id'] : null;
        $forma_pagamento = $_POST['forma_pagamento'] ?? null;
        $status = $_POST['status'] ?? 'efetivada';
        $observacoes = $_POST['observacoes'] ?? null;

        // Validação básica
        $erros = [];

        if (empty($tipo) || !in_array($tipo, ['receita', 'despesa', 'transferencia'])) {
            $erros[] = 'O tipo da transação é obrigatório.';
        }

        if (empty($descricao)) {
            $erros[] = 'A descrição é obrigatória.';
        }

        if (empty($valor) || !is_numeric($valor) || $valor <= 0) {
            $erros[] = 'O valor deve ser um número maior que zero.';
        }

        if (empty($data_transacao) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_transacao)) {
            $erros[] = 'A data da transação é obrigatória e deve estar no formato correto.';
        }

        // Se houver erros, redireciona de volta para o formulário
        if (!empty($erros)) {
            $_SESSION['form_data'] = $_POST;
            $_SESSION['form_errors'] = $erros;

            if ($id) {
                redirect("transacoes.php?action=editar&id=$id");
            } else {
                redirect('transacoes.php?action=nova');
            }
            exit;
        }

        try {
            // Inicia a transação
            $db->beginTransaction();

            // Prepara os dados para inserção/atualização
            $dados = [
                'tipo' => $tipo,
                'descricao' => $descricao,
                'valor' => $valor,
                'data_transacao' => $data_transacao,
                'categoria_id' => $categoria_id,
                'conta_id' => $conta_id,
                'forma_pagamento' => $forma_pagamento,
                'status' => $status,
                'observacoes' => $observacoes,
                'updated_at' => date('Y-m-d H:i:s')
            ];

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

            // Atualiza ou insere a transação
            if ($id) {
                // Busca a transação atual para comparar valores
                $sql = "SELECT * FROM transacoes WHERE id = ?";
                $transacao_atual = $db->fetchOne($sql, [$id]);

                // Atualiza a transação
                $db->update('transacoes', $dados, 'id = ?', [$id]);

                // Atualiza o saldo da conta, se necessário
                if ($status === 'efetivada' && $conta_id) {
                    // Se a transação anterior estava efetivada, reverte o efeito
                    if ($transacao_atual['status'] === 'efetivada' && $transacao_atual['conta_id']) {
                        $valor_antigo = $transacao_atual['valor'];
                        $tipo_antigo = $transacao_atual['tipo'];
                        $conta_antiga = $transacao_atual['conta_id'];

                        // Reverte o efeito da transação antiga
                        if ($tipo_antigo === 'receita') {
                            $sql = "UPDATE contas_bancarias SET saldo_atual = saldo_atual - ? WHERE id = ?";
                            $db->query($sql, [$valor_antigo, $conta_antiga]);
                        } elseif ($tipo_antigo === 'despesa') {
                            $sql = "UPDATE contas_bancarias SET saldo_atual = saldo_atual + ? WHERE id = ?";
                            $db->query($sql, [$valor_antigo, $conta_antiga]);
                        }
                    }

                    // Aplica o efeito da nova transação
                    if ($tipo === 'receita') {
                        $sql = "UPDATE contas_bancarias SET saldo_atual = saldo_atual + ?, data_saldo = ? WHERE id = ?";
                        $db->query($sql, [$valor, $data_transacao, $conta_id]);
                    } elseif ($tipo === 'despesa') {
                        $sql = "UPDATE contas_bancarias SET saldo_atual = saldo_atual - ?, data_saldo = ? WHERE id = ?";
                        $db->query($sql, [$valor, $data_transacao, $conta_id]);
                    }
                }

                $mensagem = 'Transação atualizada com sucesso.';
            } else {
                // Adiciona a data de criação
                $dados['created_at'] = date('Y-m-d H:i:s');

                // Insere a transação
                $id = $db->insert('transacoes', $dados);

                // Atualiza o saldo da conta, se necessário
                if ($status === 'efetivada' && $conta_id) {
                    if ($tipo === 'receita') {
                        $sql = "UPDATE contas_bancarias SET saldo_atual = saldo_atual + ?, data_saldo = ? WHERE id = ?";
                        $db->query($sql, [$valor, $data_transacao, $conta_id]);
                    } elseif ($tipo === 'despesa') {
                        $sql = "UPDATE contas_bancarias SET saldo_atual = saldo_atual - ?, data_saldo = ? WHERE id = ?";
                        $db->query($sql, [$valor, $data_transacao, $conta_id]);
                    }
                }

                $mensagem = 'Transação cadastrada com sucesso.';
            }

            // Confirma a transação
            $db->commit();

            // Define a mensagem de sucesso
            setMensagem('sucesso', $mensagem);

            // Redireciona para a listagem
            redirect('transacoes.php');
            exit;

        } catch (Exception $e) {
            // Desfaz a transação em caso de erro
            $db->rollBack();

            // Define a mensagem de erro
            setMensagem('erro', 'Erro ao salvar a transação: ' . $e->getMessage());

            // Redireciona de volta para o formulário
            if ($id) {
                redirect("transacoes.php?action=editar&id=$id");
            } else {
                redirect('transacoes.php?action=nova');
            }
            exit;
        }
        break;

    case 'excluir':
        // Verifica permissão para excluir
        if (!Auth::hasPermission('financeiro', 'excluir')) {
            setMensagem('erro', 'Você não tem permissão para excluir transações.');
            redirect('transacoes.php');
            exit;
        }

        // Verifica se o ID foi informado
        if (!isset($_GET['id']) || empty($_GET['id'])) {
            setMensagem('erro', 'ID da transação não informado.');
            redirect('transacoes.php');
            exit;
        }

        // Busca a transação
        $id = (int)$_GET['id'];
        $sql = "SELECT * FROM transacoes WHERE id = ?";
        $transacao = $db->fetchOne($sql, [$id]);

        if (!$transacao) {
            setMensagem('erro', 'Transação não encontrada.');
            redirect('transacoes.php');
            exit;
        }

        try {
            // Inicia a transação
            $db->beginTransaction();

            // Se a transação estava efetivada, reverte o efeito no saldo da conta
            if ($transacao['status'] === 'efetivada' && $transacao['conta_id']) {
                if ($transacao['tipo'] === 'receita') {
                    $sql = "UPDATE contas_bancarias SET saldo_atual = saldo_atual - ? WHERE id = ?";
                    $db->query($sql, [$transacao['valor'], $transacao['conta_id']]);
                } elseif ($transacao['tipo'] === 'despesa') {
                    $sql = "UPDATE contas_bancarias SET saldo_atual = saldo_atual + ? WHERE id = ?";
                    $db->query($sql, [$transacao['valor'], $transacao['conta_id']]);
                }
            }

            // Exclui a transação
            $db->delete('transacoes', 'id = ?', [$id]);

            // Confirma a transação
            $db->commit();

            // Define a mensagem de sucesso
            setMensagem('sucesso', 'Transação excluída com sucesso.');

        } catch (Exception $e) {
            // Desfaz a transação em caso de erro
            $db->rollBack();

            // Define a mensagem de erro
            setMensagem('erro', 'Erro ao excluir a transação: ' . $e->getMessage());
        }

        // Redireciona para a listagem
        redirect('transacoes.php');
        exit;
        break;

    case 'visualizar':
        // Verifica se o ID foi informado
        if (!isset($_GET['id']) || empty($_GET['id'])) {
            setMensagem('erro', 'ID da transação não informado.');
            redirect('transacoes.php');
            exit;
        }

        // Busca a transação com informações relacionadas
        $id = (int)$_GET['id'];
        $sql = "SELECT t.*, c.nome as categoria_nome, cb.nome as conta_nome
                FROM transacoes t
                LEFT JOIN categorias_financeiras c ON t.categoria_id = c.id
                LEFT JOIN contas_bancarias cb ON t.conta_id = cb.id
                WHERE t.id = ?";
        $transacao = $db->fetchOne($sql, [$id]);

        if (!$transacao) {
            setMensagem('erro', 'Transação não encontrada.');
            redirect('transacoes.php');
            exit;
        }

        // Define o título da página
        $titulo_pagina = 'Visualizar Transação';
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

        // Filtro por tipo
        if ($tipo !== 'todos') {
            $where[] = "t.tipo = ?";
            $params[] = $tipo;
        }

        // Filtro por categoria
        if (isset($_GET['categoria_id']) && !empty($_GET['categoria_id'])) {
            $where[] = "t.categoria_id = ?";
            $params[] = (int)$_GET['categoria_id'];
            $filtros['categoria_id'] = (int)$_GET['categoria_id'];
        }

        // Filtro por conta
        if (isset($_GET['conta_id']) && !empty($_GET['conta_id'])) {
            $where[] = "t.conta_id = ?";
            $params[] = (int)$_GET['conta_id'];
            $filtros['conta_id'] = (int)$_GET['conta_id'];
        }

        // Filtro por status
        if (isset($_GET['status']) && !empty($_GET['status'])) {
            $where[] = "t.status = ?";
            $params[] = $_GET['status'];
            $filtros['status'] = $_GET['status'];
        }

        // Filtro por período
        if (isset($_GET['data_inicio']) && !empty($_GET['data_inicio'])) {
            $where[] = "t.data_transacao >= ?";
            $params[] = $_GET['data_inicio'];
            $filtros['data_inicio'] = $_GET['data_inicio'];
        }

        if (isset($_GET['data_fim']) && !empty($_GET['data_fim'])) {
            $where[] = "t.data_transacao <= ?";
            $params[] = $_GET['data_fim'];
            $filtros['data_fim'] = $_GET['data_fim'];
        }

        // Filtro por termo de busca
        if (isset($_GET['termo']) && !empty($_GET['termo'])) {
            $where[] = "(t.descricao LIKE ? OR t.observacoes LIKE ?)";
            $params[] = '%' . $_GET['termo'] . '%';
            $params[] = '%' . $_GET['termo'] . '%';
            $filtros['termo'] = $_GET['termo'];
        }

        // Monta a cláusula WHERE
        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        // Consulta para contar o total de registros
        $sql = "SELECT COUNT(*) as total FROM transacoes t $whereClause";
        $resultado = $db->fetchOne($sql, $params);
        $total_registros = $resultado['total'] ?? 0;

        // Calcula o total de páginas
        $total_paginas = ceil($total_registros / $por_pagina);

        // Consulta para buscar as transações
        $sql = "SELECT t.*, c.nome as categoria_nome, cb.nome as conta_nome
                FROM transacoes t
                LEFT JOIN categorias_financeiras c ON t.categoria_id = c.id
                LEFT JOIN contas_bancarias cb ON t.conta_id = cb.id
                $whereClause
                ORDER BY t.data_transacao DESC, t.id DESC
                LIMIT $offset, $por_pagina";
        $transacoes = $db->fetchAll($sql, $params);

        // Carrega as categorias para o filtro
        $sql = "SELECT * FROM categorias_financeiras";
        if ($tipo !== 'todos') {
            $sql .= " WHERE tipo = ?";
            $categorias = $db->fetchAll($sql, [$tipo]);
        } else {
            $categorias = $db->fetchAll($sql);
        }

        // Carrega as contas bancárias para o filtro
        $sql = "SELECT * FROM contas_bancarias ORDER BY nome";
        $contas = $db->fetchAll($sql);

        // Define o título da página
        $titulo_pagina = 'Transações Financeiras';
        if ($tipo === 'receita') {
            $titulo_pagina = 'Receitas';
        } elseif ($tipo === 'despesa') {
            $titulo_pagina = 'Despesas';
        }

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
                    <?php include_once __DIR__ . "/views/$view.php"; ?>
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
    </script>
</body>
</html>
