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
    case 'editar':
        // Verifica permissão para editar
        if (!Auth::hasPermission('financeiro', 'editar')) {
            setMensagem('erro', 'Você não tem permissão para editar configurações financeiras de polos.');
            redirect('polos_financeiro.php');
            exit;
        }

        // Verifica se o ID foi informado
        if (!isset($_GET['id']) || empty($_GET['id'])) {
            setMensagem('erro', 'ID do polo não informado.');
            redirect('polos_financeiro.php');
            exit;
        }

        // Busca o polo
        $id = (int)$_GET['id'];
        $sql = "SELECT p.*, pf.*
                FROM polos p
                LEFT JOIN polos_financeiro pf ON p.id = pf.polo_id
                WHERE p.id = ?";
        $polo = $db->fetchOne($sql, [$id]);

        if (!$polo) {
            setMensagem('erro', 'Polo não encontrado.');
            redirect('polos_financeiro.php');
            exit;
        }

        // Busca os tipos de polo
        $sql = "SELECT * FROM tipos_polos ORDER BY nome";
        $tipos_polos = $db->fetchAll($sql);

        // Busca as configurações financeiras do polo
        $sql = "SELECT pf.*, tp.nome as tipo_nome
                FROM polos_financeiro pf
                JOIN tipos_polos tp ON pf.tipo_polo_id = tp.id
                WHERE pf.polo_id = ?";
        $configuracoes = $db->fetchAll($sql, [$id]);

        // Define o título da página
        $titulo_pagina = 'Editar Configurações Financeiras do Polo: ' . $polo['nome'];
        $view = 'editar';
        break;

    case 'salvar':
        // Verifica permissão para editar
        if (!Auth::hasPermission('financeiro', 'editar')) {
            setMensagem('erro', 'Você não tem permissão para editar configurações financeiras de polos.');
            redirect('polos_financeiro.php');
            exit;
        }

        // Verifica se é POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            setMensagem('erro', 'Método inválido.');
            redirect('polos_financeiro.php');
            exit;
        }

        // Obtém os dados do formulário
        $polo_id = isset($_POST['polo_id']) ? (int)$_POST['polo_id'] : null;
        $tipo_polo_id = isset($_POST['tipo_polo_id']) ? (int)$_POST['tipo_polo_id'] : null;
        $taxa_inicial = !empty($_POST['taxa_inicial']) ? str_replace(',', '.', $_POST['taxa_inicial']) : null;
        $valor_por_documento = !empty($_POST['valor_por_documento']) ? str_replace(',', '.', $_POST['valor_por_documento']) : null;
        $taxa_inicial_paga = isset($_POST['taxa_inicial_paga']) ? 1 : 0;
        $data_pagamento_taxa = !empty($_POST['data_pagamento_taxa']) ? $_POST['data_pagamento_taxa'] : null;
        $pacotes_adquiridos = isset($_POST['pacotes_adquiridos']) ? (int)$_POST['pacotes_adquiridos'] : 0;
        $observacoes = $_POST['observacoes'] ?? null;

        // Validação básica
        if (!$polo_id) {
            setMensagem('erro', 'Polo não informado.');
            redirect('polos_financeiro.php');
            exit;
        }

        if (!$tipo_polo_id) {
            setMensagem('erro', 'Tipo de polo não informado.');
            redirect('polos_financeiro.php?action=editar&id=' . $polo_id);
            exit;
        }

        try {
            // Verifica se já existe uma configuração para este polo e tipo
            $sql = "SELECT id FROM polos_financeiro WHERE polo_id = ? AND tipo_polo_id = ?";
            $config = $db->fetchOne($sql, [$polo_id, $tipo_polo_id]);

            // Prepara os dados
            $dados = [
                'polo_id' => $polo_id,
                'tipo_polo_id' => $tipo_polo_id,
                'taxa_inicial' => $taxa_inicial,
                'valor_por_documento' => $valor_por_documento,
                'taxa_inicial_paga' => $taxa_inicial_paga,
                'data_pagamento_taxa' => $data_pagamento_taxa,
                'pacotes_adquiridos' => $pacotes_adquiridos,
                'observacoes' => $observacoes,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            // Atualiza ou insere a configuração
            if ($config) {
                $db->update('polos_financeiro', $dados, 'id = ?', [$config['id']]);
                $mensagem = 'Configurações financeiras do polo atualizadas com sucesso.';
            } else {
                $dados['created_at'] = date('Y-m-d H:i:s');
                $dados['documentos_disponiveis'] = $pacotes_adquiridos;
                $dados['documentos_emitidos'] = 0;
                $dados['valor_total_pago'] = 0;
                $db->insert('polos_financeiro', $dados);
                $mensagem = 'Configurações financeiras do polo cadastradas com sucesso.';
            }

            // Registra no histórico se a taxa inicial foi paga
            if ($taxa_inicial_paga && $data_pagamento_taxa) {
                // Verifica se já existe um registro de pagamento de taxa inicial
                $sql = "SELECT id FROM polos_financeiro_historico
                        WHERE polo_id = ? AND tipo_polo_id = ? AND tipo_transacao = 'taxa_inicial'";
                $historico = $db->fetchOne($sql, [$polo_id, $tipo_polo_id]);

                if (!$historico) {
                    $dados_historico = [
                        'polo_id' => $polo_id,
                        'tipo_polo_id' => $tipo_polo_id,
                        'tipo_transacao' => 'taxa_inicial',
                        'valor' => $taxa_inicial,
                        'quantidade' => 1,
                        'data_transacao' => $data_pagamento_taxa,
                        'descricao' => 'Pagamento da taxa inicial',
                        'usuario_id' => $_SESSION['usuario']['id'],
                        'created_at' => date('Y-m-d H:i:s')
                    ];

                    $db->insert('polos_financeiro_historico', $dados_historico);
                }
            }

            // Define a mensagem de sucesso
            setMensagem('sucesso', $mensagem);

        } catch (Exception $e) {
            // Define a mensagem de erro
            setMensagem('erro', 'Erro ao salvar as configurações financeiras: ' . $e->getMessage());
        }

        // Redireciona para a página de edição
        redirect('polos_financeiro.php?action=editar&id=' . $polo_id);
        exit;
        break;

    case 'adicionar_tipo':
        // Verifica permissão para editar
        if (!Auth::hasPermission('financeiro', 'editar')) {
            setMensagem('erro', 'Você não tem permissão para editar configurações financeiras de polos.');
            redirect('polos_financeiro.php');
            exit;
        }

        // Verifica se é POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            setMensagem('erro', 'Método inválido.');
            redirect('polos_financeiro.php');
            exit;
        }

        // Obtém os dados do formulário
        $polo_id = isset($_POST['polo_id']) ? (int)$_POST['polo_id'] : null;
        $tipo_polo_id = isset($_POST['tipo_polo_id']) ? (int)$_POST['tipo_polo_id'] : null;

        // Validação básica
        if (!$polo_id || !$tipo_polo_id) {
            setMensagem('erro', 'Polo ou tipo de polo não informado.');
            redirect('polos_financeiro.php');
            exit;
        }

        try {
            // Verifica se já existe uma associação entre o polo e o tipo
            $sql = "SELECT id FROM polos_tipos WHERE polo_id = ? AND tipo_polo_id = ?";
            $associacao = $db->fetchOne($sql, [$polo_id, $tipo_polo_id]);

            if (!$associacao) {
                // Insere a associação
                $dados = [
                    'polo_id' => $polo_id,
                    'tipo_polo_id' => $tipo_polo_id,
                    'created_at' => date('Y-m-d H:i:s')
                ];

                $db->insert('polos_tipos', $dados);

                // Cria a configuração financeira padrão
                $sql = "SELECT * FROM tipos_polos_financeiro WHERE tipo_polo_id = ?";
                $config_padrao = $db->fetchOne($sql, [$tipo_polo_id]);

                if ($config_padrao) {
                    $dados_financeiro = [
                        'polo_id' => $polo_id,
                        'tipo_polo_id' => $tipo_polo_id,
                        'taxa_inicial' => $config_padrao['taxa_inicial'],
                        'valor_por_documento' => $config_padrao['taxa_por_documento'],
                        'pacotes_adquiridos' => 0,
                        'documentos_disponiveis' => 0,
                        'documentos_emitidos' => 0,
                        'valor_total_pago' => 0,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];

                    $db->insert('polos_financeiro', $dados_financeiro);
                }

                setMensagem('sucesso', 'Tipo de polo adicionado com sucesso.');
            } else {
                setMensagem('info', 'Este tipo de polo já está associado a este polo.');
            }

        } catch (Exception $e) {
            setMensagem('erro', 'Erro ao adicionar tipo de polo: ' . $e->getMessage());
        }

        // Redireciona para a página de edição
        redirect('polos_financeiro.php?action=editar&id=' . $polo_id);
        exit;
        break;

    case 'remover_tipo':
        // Verifica permissão para editar
        if (!Auth::hasPermission('financeiro', 'editar')) {
            setMensagem('erro', 'Você não tem permissão para editar configurações financeiras de polos.');
            redirect('polos_financeiro.php');
            exit;
        }

        // Obtém os dados da URL
        $polo_id = isset($_GET['polo_id']) ? (int)$_GET['polo_id'] : null;
        $tipo_polo_id = isset($_GET['tipo_id']) ? (int)$_GET['tipo_id'] : null;

        // Validação básica
        if (!$polo_id || !$tipo_polo_id) {
            setMensagem('erro', 'Polo ou tipo de polo não informado.');
            redirect('polos_financeiro.php');
            exit;
        }

        try {
            // Remove a associação
            $db->delete('polos_tipos', 'polo_id = ? AND tipo_polo_id = ?', [$polo_id, $tipo_polo_id]);

            // Remove a configuração financeira
            $db->delete('polos_financeiro', 'polo_id = ? AND tipo_polo_id = ?', [$polo_id, $tipo_polo_id]);

            setMensagem('sucesso', 'Tipo de polo removido com sucesso.');

        } catch (Exception $e) {
            setMensagem('erro', 'Erro ao remover tipo de polo: ' . $e->getMessage());
        }

        // Redireciona para a página de edição
        redirect('polos_financeiro.php?action=editar&id=' . $polo_id);
        exit;
        break;

    case 'historico':
        // Verifica se o ID foi informado
        if (!isset($_GET['id']) || empty($_GET['id'])) {
            setMensagem('erro', 'ID do polo não informado.');
            redirect('polos_financeiro.php');
            exit;
        }

        // Busca o polo
        $id = (int)$_GET['id'];
        $sql = "SELECT * FROM polos WHERE id = ?";
        $polo = $db->fetchOne($sql, [$id]);

        if (!$polo) {
            setMensagem('erro', 'Polo não encontrado.');
            redirect('polos_financeiro.php');
            exit;
        }

        // Busca o histórico financeiro do polo
        $sql = "SELECT h.*, tp.nome as tipo_nome, u.nome as usuario_nome
                FROM polos_financeiro_historico h
                JOIN tipos_polos tp ON h.tipo_polo_id = tp.id
                LEFT JOIN usuarios u ON h.usuario_id = u.id
                WHERE h.polo_id = ?
                ORDER BY h.data_transacao DESC, h.id DESC";
        $historico = $db->fetchAll($sql, [$id]);

        // Define o título da página
        $titulo_pagina = 'Histórico Financeiro do Polo: ' . $polo['nome'];
        $view = 'historico';
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

        // Filtro por nome
        if (isset($_GET['nome']) && !empty($_GET['nome'])) {
            $where[] = "p.nome LIKE ?";
            $params[] = '%' . $_GET['nome'] . '%';
            $filtros['nome'] = $_GET['nome'];
        }

        // Filtro por tipo de polo
        if (isset($_GET['tipo_id']) && !empty($_GET['tipo_id'])) {
            $where[] = "EXISTS (SELECT 1 FROM polos_tipos pt WHERE pt.polo_id = p.id AND pt.tipo_polo_id = ?)";
            $params[] = (int)$_GET['tipo_id'];
            $filtros['tipo_id'] = (int)$_GET['tipo_id'];
        }

        // Filtro por status
        if (isset($_GET['status']) && !empty($_GET['status'])) {
            $where[] = "p.status = ?";
            $params[] = $_GET['status'];
            $filtros['status'] = $_GET['status'];
        }

        // Monta a cláusula WHERE
        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        // Consulta para contar o total de registros
        $sql = "SELECT COUNT(*) as total FROM polos p $whereClause";
        $resultado = $db->fetchOne($sql, $params);
        $total_registros = $resultado['total'] ?? 0;

        // Calcula o total de páginas
        $total_paginas = ceil($total_registros / $por_pagina);

        // Consulta para buscar os polos
        $sql = "SELECT p.*,
                (SELECT COUNT(*) FROM polos_tipos pt WHERE pt.polo_id = p.id) as total_tipos,
                (SELECT GROUP_CONCAT(tp.nome SEPARATOR ', ') FROM polos_tipos pt
                 JOIN tipos_polos tp ON pt.tipo_polo_id = tp.id
                 WHERE pt.polo_id = p.id) as tipos_nomes
                FROM polos p
                $whereClause
                ORDER BY p.nome
                LIMIT $offset, $por_pagina";
        $polos = $db->fetchAll($sql, $params);

        // Busca os tipos de polo para o filtro
        $sql = "SELECT * FROM tipos_polos ORDER BY nome";
        $tipos_polos = $db->fetchAll($sql);

        // Define o título da página
        $titulo_pagina = 'Polos - Configurações Financeiras';
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

        /* Estilos específicos para polos financeiros */
        .card {
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .tipo-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 9999px;
            background-color: #E5E7EB;
            color: #4B5563;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .tipo-badge-pos {
            background-color: #DBEAFE;
            color: #1E40AF;
        }

        .tipo-badge-ext {
            background-color: #D1FAE5;
            color: #065F46;
        }

        .tipo-badge-grad {
            background-color: #FEF3C7;
            color: #92400E;
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
                    $view_file = __DIR__ . "/views/polos_financeiro/$view.php";
                    if (file_exists($view_file)) {
                        include $view_file;
                    } else {
                        echo '<div class="bg-white rounded-lg shadow-sm p-6">';
                        echo '<p class="text-gray-600">A visualização solicitada não foi encontrada. Por favor, crie o arquivo <strong>' . $view_file . '</strong>.</p>';
                        echo '<p class="mt-4"><a href="polos_financeiro.php" class="text-blue-600 hover:text-blue-800">Voltar para a listagem</a></p>';
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
    </script>
</body>
</html>
