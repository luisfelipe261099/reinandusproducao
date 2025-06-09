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
$action = $_GET['action'] ?? 'form';

// Processa as ações
switch ($action) {
    case 'gerar':
        // Verifica permissão para criar
        if (!Auth::hasPermission('financeiro', 'criar')) {
            setMensagem('erro', 'Você não tem permissão para gerar boletos.');
            redirect('gerar_boleto.php');
            exit;
        }

        // Verifica se é POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            setMensagem('erro', 'Método inválido.');
            redirect('gerar_boleto.php');
            exit;
        }

        // Inclui o processador de boletos
        require_once __DIR__ . '/includes/processar_boleto.php';

        // Processa a geração do boleto
        $resultado = processarBoleto($_POST, $db);

        if ($resultado['status'] === 'sucesso') {
            setMensagem('sucesso', $resultado['mensagem']);
        } else {
            setMensagem('erro', $resultado['mensagem']);
        }

        // Redireciona para o formulário
        redirect('gerar_boleto.php');
        exit;
        break;

    case 'visualizar':
        // Verifica se o ID foi informado
        if (!isset($_GET['id']) || empty($_GET['id'])) {
            setMensagem('erro', 'ID do boleto não informado.');
            redirect('gerar_boleto.php');
            exit;
        }

        // Busca o boleto
        $id = (int)$_GET['id'];
        $sql = "SELECT * FROM boletos WHERE id = ?";
        $boleto = $db->fetchOne($sql, [$id]);

        if (!$boleto) {
            setMensagem('erro', 'Boleto não encontrado.');
            redirect('gerar_boleto.php');
            exit;
        }

        // Log para depuração
        error_log("Visualizando boleto - ID: {$boleto['id']}");
        error_log("Dados do boleto: " . json_encode($boleto));

        // Define o título da página
        $titulo_pagina = 'Visualizar Boleto';
        $view = 'visualizar';
        break;

    case 'marcar_pago':
        // Verifica permissão para editar
        if (!Auth::hasPermission('financeiro', 'editar')) {
            setMensagem('erro', 'Você não tem permissão para marcar boletos como pagos.');
            redirect('gerar_boleto.php?action=listar');
            exit;
        }

        // Verifica se o ID foi informado
        if (!isset($_GET['id']) || empty($_GET['id'])) {
            setMensagem('erro', 'ID do boleto não informado.');
            redirect('gerar_boleto.php?action=listar');
            exit;
        }

        // Busca o boleto
        $id = (int)$_GET['id'];
        $sql = "SELECT * FROM boletos WHERE id = ?";
        $boleto = $db->fetchOne($sql, [$id]);

        if (!$boleto) {
            setMensagem('erro', 'Boleto não encontrado.');
            redirect('gerar_boleto.php?action=listar');
            exit;
        }

        // Verifica se o boleto já está pago ou cancelado
        if ($boleto['status'] != 'pendente') {
            setMensagem('erro', 'Este boleto não pode ser marcado como pago pois seu status é ' . $boleto['status'] . '.');
            redirect('gerar_boleto.php?action=visualizar&id=' . $id);
            exit;
        }

        // Atualiza o status do boleto
        $dados = [
            'status' => 'pago',
            'data_pagamento' => date('Y-m-d'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $db->update('boletos', $dados, 'id = ?', [$id]);

        setMensagem('sucesso', 'Boleto marcado como pago com sucesso.');
        redirect('gerar_boleto.php?action=visualizar&id=' . $id);
        exit;
        break;

    case 'cancelar':
        // Verifica permissão para editar
        if (!Auth::hasPermission('financeiro', 'editar')) {
            setMensagem('erro', 'Você não tem permissão para cancelar boletos.');
            redirect('gerar_boleto.php?action=listar');
            exit;
        }

        // Verifica se o ID foi informado
        if (!isset($_GET['id']) || empty($_GET['id'])) {
            setMensagem('erro', 'ID do boleto não informado.');
            redirect('gerar_boleto.php?action=listar');
            exit;
        }

        // Redireciona para a página de cancelamento
        $id = (int)$_GET['id'];
        redirect('cancelar_boleto.php?id=' . $id);
        exit;
        break;

    case 'listar':
        // Parâmetros de filtro e paginação
        $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
        $por_pagina = 20;
        $offset = ($pagina - 1) * $por_pagina;

        // Filtros
        $filtros = [];
        $params = [];
        $where = [];

        // Filtro por tipo (aluno, polo, avulso)
        if (isset($_GET['tipo']) && !empty($_GET['tipo'])) {
            $where[] = "b.tipo_entidade = ?";
            $params[] = $_GET['tipo'];
            $filtros['tipo'] = $_GET['tipo'];
        }

        // Filtro por status
        if (isset($_GET['status']) && !empty($_GET['status'])) {
            $where[] = "b.status = ?";
            $params[] = $_GET['status'];
            $filtros['status'] = $_GET['status'];
        }

        // Filtro por período
        if (isset($_GET['data_inicio']) && !empty($_GET['data_inicio'])) {
            $where[] = "b.data_vencimento >= ?";
            $params[] = $_GET['data_inicio'];
            $filtros['data_inicio'] = $_GET['data_inicio'];
        }

        if (isset($_GET['data_fim']) && !empty($_GET['data_fim'])) {
            $where[] = "b.data_vencimento <= ?";
            $params[] = $_GET['data_fim'];
            $filtros['data_fim'] = $_GET['data_fim'];
        }

        // Monta a cláusula WHERE
        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        // Consulta para contar o total de registros
        $sql = "SELECT COUNT(*) as total FROM boletos b $whereClause";
        $resultado = $db->fetchOne($sql, $params);
        $total_registros = $resultado['total'] ?? 0;

        // Calcula o total de páginas
        $total_paginas = ceil($total_registros / $por_pagina);

        // Consulta para buscar os boletos
        $sql = "SELECT b.*,
                CASE
                    WHEN b.tipo_entidade = 'aluno' THEN a.nome
                    WHEN b.tipo_entidade = 'polo' THEN p.nome
                    ELSE b.nome_pagador
                END as nome_entidade
                FROM boletos b
                LEFT JOIN alunos a ON b.entidade_id = a.id AND b.tipo_entidade = 'aluno'
                LEFT JOIN polos p ON b.entidade_id = p.id AND b.tipo_entidade = 'polo'
                $whereClause
                ORDER BY b.data_vencimento DESC, b.id DESC
                LIMIT $offset, $por_pagina";
        $boletos = $db->fetchAll($sql, $params);

        // Define o título da página
        $titulo_pagina = 'Boletos Gerados';
        $view = 'listar';
        break;

    case 'mensalidades':
        // Verifica permissão para visualizar
        if (!Auth::hasPermission('financeiro', 'visualizar')) {
            setMensagem('erro', 'Você não tem permissão para visualizar mensalidades.');
            redirect('gerar_boleto.php');
            exit;
        }

        // Parâmetros de filtro e paginação
        $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
        $por_pagina = 20;
        $offset = ($pagina - 1) * $por_pagina;

        // Filtros
        $filtros = [];
        $params = [];
        $where = ["lf.status = 'pendente'"]; // Apenas mensalidades pendentes

        // Filtro por aluno
        if (isset($_GET['aluno']) && !empty($_GET['aluno'])) {
            $termo = '%' . $_GET['aluno'] . '%';
            $where[] = "(a.nome LIKE ? OR a.cpf LIKE ?)";
            $params[] = $termo;
            $params[] = $termo;
            $filtros['aluno'] = $_GET['aluno'];
        }

        // Filtro por polo
        if (isset($_GET['polo_id']) && !empty($_GET['polo_id'])) {
            $where[] = "m.polo_id = ?";
            $params[] = (int)$_GET['polo_id'];
            $filtros['polo_id'] = (int)$_GET['polo_id'];
        }

        // Filtro por curso
        if (isset($_GET['curso_id']) && !empty($_GET['curso_id'])) {
            $where[] = "m.curso_id = ?";
            $params[] = (int)$_GET['curso_id'];
            $filtros['curso_id'] = (int)$_GET['curso_id'];
        }

        // Filtro por período de vencimento
        if (isset($_GET['data_vencimento_inicio']) && !empty($_GET['data_vencimento_inicio'])) {
            $where[] = "lf.data_vencimento >= ?";
            $params[] = $_GET['data_vencimento_inicio'];
            $filtros['data_vencimento_inicio'] = $_GET['data_vencimento_inicio'];
        }

        if (isset($_GET['data_vencimento_fim']) && !empty($_GET['data_vencimento_fim'])) {
            $where[] = "lf.data_vencimento <= ?";
            $params[] = $_GET['data_vencimento_fim'];
            $filtros['data_vencimento_fim'] = $_GET['data_vencimento_fim'];
        }

        // Filtro por mês atual (padrão)
        if (empty($filtros)) {
            $inicio_mes = date('Y-m-01');
            $fim_mes = date('Y-m-t');
            $where[] = "lf.data_vencimento BETWEEN ? AND ?";
            $params[] = $inicio_mes;
            $params[] = $fim_mes;
            $filtros['data_vencimento_inicio'] = $inicio_mes;
            $filtros['data_vencimento_fim'] = $fim_mes;
        }

        // Monta a cláusula WHERE
        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        // Consulta para contar o total de registros
        $sql = "SELECT COUNT(*) as total
                FROM lancamentos_financeiros lf
                LEFT JOIN alunos a ON lf.aluno_id = a.id
                LEFT JOIN matriculas m ON a.id = m.aluno_id AND m.status = 'ativo'
                $whereClause";
        $resultado = $db->fetchOne($sql, $params);
        $total_registros = $resultado['total'] ?? 0;

        // Calcula o total de páginas
        $total_paginas = ceil($total_registros / $por_pagina);

        // Consulta para buscar as mensalidades
        $sql = "SELECT lf.*, a.nome as aluno_nome, a.cpf as aluno_cpf,
                       c.nome as curso_nome, p.nome as polo_nome,
                       a.endereco, a.bairro, a.cidade, a.estado as uf, a.cep
                FROM lancamentos_financeiros lf
                LEFT JOIN alunos a ON lf.aluno_id = a.id
                LEFT JOIN matriculas m ON a.id = m.aluno_id AND m.status = 'ativo'
                LEFT JOIN cursos c ON m.curso_id = c.id
                LEFT JOIN polos p ON m.polo_id = p.id
                $whereClause
                ORDER BY lf.data_vencimento ASC, lf.id DESC
                LIMIT $offset, $por_pagina";
        $mensalidades = $db->fetchAll($sql, $params);

        // Carrega os polos para o filtro
        $sql = "SELECT id, nome FROM polos WHERE status = 'ativo' ORDER BY nome ASC";
        $polos = $db->fetchAll($sql);

        // Carrega os cursos para o filtro
        $sql = "SELECT id, nome FROM cursos WHERE status = 'ativo' ORDER BY nome ASC";
        $cursos = $db->fetchAll($sql);

        // Define o título da página
        $titulo_pagina = 'Gerar Boletos de Mensalidades';
        $view = 'mensalidades';
        break;

    case 'processar_mensalidades':
        // Verifica permissão para criar
        if (!Auth::hasPermission('financeiro', 'criar')) {
            setMensagem('erro', 'Você não tem permissão para gerar boletos.');
            redirect('gerar_boleto.php?action=mensalidades');
            exit;
        }

        // Verifica se é POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            setMensagem('erro', 'Método inválido.');
            redirect('gerar_boleto.php?action=mensalidades');
            exit;
        }

        // Verifica se foram selecionadas mensalidades
        if (!isset($_POST['mensalidade_ids']) || empty($_POST['mensalidade_ids'])) {
            setMensagem('erro', 'Selecione pelo menos uma mensalidade para gerar boleto.');
            redirect('gerar_boleto.php?action=mensalidades');
            exit;
        }

        // Inclui o processador de boletos
        require_once __DIR__ . '/includes/processar_boleto.php';

        // Inicia a transação
        $db->beginTransaction();

        try {
            $mensalidade_ids = $_POST['mensalidade_ids'];
            $boletos_gerados = 0;
            $erros = [];

            foreach ($mensalidade_ids as $mensalidade_id) {
                // Busca a mensalidade
                $sql = "SELECT lf.*, a.nome as aluno_nome, a.cpf as aluno_cpf,
                               a.endereco, a.bairro, a.cidade, a.estado as uf, a.cep
                        FROM lancamentos_financeiros lf
                        LEFT JOIN alunos a ON lf.aluno_id = a.id
                        LEFT JOIN matriculas m ON a.id = m.aluno_id AND m.status = 'ativo'
                        WHERE lf.id = ? AND lf.status = 'pendente'";
                $mensalidade = $db->fetchOne($sql, [(int)$mensalidade_id]);

                if (!$mensalidade) {
                    $erros[] = "Mensalidade ID $mensalidade_id não encontrada ou não está pendente.";
                    continue;
                }

                // Verifica se já existe um boleto para esta mensalidade
                $sql = "SELECT COUNT(*) as total FROM boletos WHERE mensalidade_id = ?";
                $resultado = $db->fetchOne($sql, [(int)$mensalidade_id]);

                if ($resultado['total'] > 0) {
                    $erros[] = "Já existe um boleto para a mensalidade ID $mensalidade_id.";
                    continue;
                }

                // Prepara os dados para o boleto
                $dados_boleto = [
                    'tipo_entidade' => 'aluno',
                    'entidade_id' => $mensalidade['aluno_id'],
                    'nome_pagador' => $mensalidade['aluno_nome'],
                    'cpf_pagador' => $mensalidade['aluno_cpf'],
                    'endereco' => $mensalidade['endereco'] ?? '',
                    'bairro' => $mensalidade['bairro'] ?? '',
                    'cidade' => $mensalidade['cidade'] ?? '',
                    'uf' => $mensalidade['uf'] ?? '',
                    'cep' => $mensalidade['cep'] ?? '',
                    'descricao' => $mensalidade['descricao'],
                    'valor' => $mensalidade['valor'] - $mensalidade['desconto'] + $mensalidade['acrescimo'],
                    'data_vencimento' => $mensalidade['data_vencimento'],
                    'multa' => 2,
                    'juros' => 1,
                    'instrucoes' => 'Mensalidade referente a ' . $mensalidade['descricao'],
                    'mensalidade_id' => $mensalidade_id
                ];

                // Gera o boleto
                $resultado = gerarBoletoBancario($db, $dados_boleto);

                if ($resultado['status'] === 'sucesso') {
                    $boletos_gerados++;

                    // Atualiza a mensalidade com o ID do boleto
                    $db->update('boletos', ['mensalidade_id' => $mensalidade_id], 'id = ?', [$resultado['boleto_id']]);
                } else {
                    $erros[] = "Erro ao gerar boleto para mensalidade ID $mensalidade_id: " . $resultado['mensagem'];
                }
            }

            // Confirma a transação
            $db->commit();

            // Define a mensagem de sucesso ou erro
            if ($boletos_gerados > 0) {
                $mensagem = "Foram gerados $boletos_gerados boletos com sucesso.";
                if (!empty($erros)) {
                    $mensagem .= " Porém, ocorreram os seguintes erros: " . implode("; ", $erros);
                    setMensagem('info', $mensagem);
                } else {
                    setMensagem('sucesso', $mensagem);
                }
            } else {
                setMensagem('erro', "Não foi possível gerar nenhum boleto. Erros: " . implode("; ", $erros));
            }

            // Redireciona para a listagem de boletos
            redirect('gerar_boleto.php?action=listar');
            exit;

        } catch (Exception $e) {
            // Desfaz a transação em caso de erro
            $db->rollBack();

            // Log detalhado do erro
            error_log('ERRO ao gerar boletos de mensalidades: ' . $e->getMessage());
            error_log('Trace: ' . $e->getTraceAsString());

            // Define a mensagem de erro
            setMensagem('erro', 'Erro ao gerar boletos: ' . $e->getMessage());

            // Redireciona para a listagem de mensalidades
            redirect('gerar_boleto.php?action=mensalidades');
            exit;
        }
        break;

    case 'form':
    default:
        // Carrega os alunos para o formulário
        $sql = "SELECT id, nome, cpf FROM alunos WHERE status = 'ativo' ORDER BY nome ASC LIMIT 100";
        $alunos = $db->fetchAll($sql);

        // Carrega os polos para o formulário
        $sql = "SELECT id, nome, cnpj FROM polos WHERE status = 'ativo' ORDER BY nome ASC";
        $polos = $db->fetchAll($sql);

        // Define o título da página
        $titulo_pagina = 'Gerar Boleto';
        $view = 'form';
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

        /* Estilos específicos para boletos */
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

        .status-vencido {
            background-color: #FEE2E2;
            color: #DC2626;
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
                    $view_file = __DIR__ . "/views/boletos/$view.php";
                    if (file_exists($view_file)) {
                        include $view_file;
                    } else {
                        echo '<div class="bg-white rounded-lg shadow-sm p-6">';
                        echo '<p class="text-gray-600">A visualização solicitada não foi encontrada. Por favor, crie o arquivo <strong>' . $view_file . '</strong>.</p>';
                        echo '<p class="mt-4"><a href="gerar_boleto.php" class="text-blue-600 hover:text-blue-800">Voltar para o formulário</a></p>';
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
