<?php
/**
 * Mensalidades de Alunos - Módulo Financeiro
 */

require_once '../includes/init.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

// Verifica autenticação e permissão
Auth::requireLogin();
$userType = Auth::getUserType();
if (!in_array($userType, ['financeiro', 'admin_master'])) {
    $_SESSION['error'] = 'Você não tem permissão para acessar o módulo financeiro.';
    header('Location: ../index.php');
    exit;
}

// Registra log de acesso ao módulo de mensalidades
if (function_exists('registrarLog')) {
    registrarLog(
        'financeiro',
        'acesso_mensalidades',
        'Usuário acessou o módulo de gestão de mensalidades',
        null,
        null,
        null,
        [
            'user_id' => Auth::getUserId(),
            'user_type' => $userType,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Desconhecido',
            'timestamp' => date('Y-m-d H:i:s')
        ]
    );
}

$db = Database::getInstance();
$action = $_GET['action'] ?? 'listar';
$mensalidadeId = $_GET['id'] ?? null;

// Processa ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'gerar_mensalidades') {
        try {
            $mesReferencia = $_POST['mes_referencia'];
            $alunosIds = $_POST['alunos_ids'] ?? [];
            $valor = str_replace(['R$', '.', ','], ['', '', '.'], $_POST['valor']);
            $dataVencimento = $_POST['data_vencimento'];

            $geradas = 0;
            foreach ($alunosIds as $alunoId) {
                // Verifica se já existe mensalidade para este aluno no mês
                $existe = $db->fetchOne("
                    SELECT id FROM mensalidades_alunos
                    WHERE aluno_id = ? AND mes_referencia = ?
                ", [$alunoId, $mesReferencia]);

                if (!$existe) {
                    $aluno = $db->fetchOne("SELECT nome FROM alunos WHERE id = ?", [$alunoId]);

                    $dados = [
                        'aluno_id' => $alunoId,
                        'valor' => $valor,
                        'data_vencimento' => $dataVencimento,
                        'mes_referencia' => $mesReferencia,
                        'descricao' => 'Mensalidade - ' . date('m/Y', strtotime($mesReferencia)),
                        'usuario_id' => Auth::getUserId()
                    ];

                    $db->insert('mensalidades_alunos', $dados);
                    $geradas++;
                }
            }

            $_SESSION['success'] = "$geradas mensalidade(s) gerada(s) com sucesso!";
            header('Location: mensalidades.php');
            exit;

        } catch (Exception $e) {
            $_SESSION['error'] = 'Erro ao gerar mensalidades: ' . $e->getMessage();
        }
    }

    if ($action === 'pagar' && $mensalidadeId) {
        try {
            $dados = [
                'status' => 'pago',
                'data_pagamento' => $_POST['data_pagamento'] ?? date('Y-m-d'),
                'valor_pago' => str_replace(['R$', '.', ','], ['', '', '.'], $_POST['valor_pago']),
                'forma_pagamento' => $_POST['forma_pagamento'] ?? null,
                'observacoes' => $_POST['observacoes'] ?? null,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $db->update('mensalidades_alunos', $dados, 'id = ?', [$mensalidadeId]);

            // Registra na conta a receber
            $mensalidade = $db->fetchOne("
                SELECT ma.*, a.nome as aluno_nome
                FROM mensalidades_alunos ma
                JOIN alunos a ON ma.aluno_id = a.id
                WHERE ma.id = ?
            ", [$mensalidadeId]);

            if ($mensalidade) {
                $contaReceber = [
                    'descricao' => 'Mensalidade - ' . $mensalidade['aluno_nome'],
                    'valor' => $dados['valor_pago'],
                    'data_vencimento' => $mensalidade['data_vencimento'],
                    'data_recebimento' => $dados['data_pagamento'],
                    'cliente_id' => $mensalidade['aluno_id'],
                    'cliente_nome' => $mensalidade['aluno_nome'],
                    'cliente_tipo' => 'aluno',
                    'categoria_id' => 1, // Categoria "Mensalidades de Alunos"
                    'forma_recebimento' => $dados['forma_pagamento'],
                    'status' => 'recebido',
                    'observacoes' => $dados['observacoes'],
                    'usuario_id' => Auth::getUserId()
                ];

                $db->insert('contas_receber', $contaReceber);

                // Registra transação financeira
                $transacao = [
                    'tipo' => 'receita',
                    'descricao' => $contaReceber['descricao'],
                    'valor' => $dados['valor_pago'],
                    'data_transacao' => $dados['data_pagamento'],
                    'categoria_id' => 1,
                    'forma_pagamento' => $dados['forma_pagamento'],
                    'referencia_tipo' => 'conta_receber',
                    'observacoes' => $dados['observacoes'],
                    'usuario_id' => Auth::getUserId()
                ];

                $db->insert('transacoes_financeiras', $transacao);
            }

            $_SESSION['success'] = 'Pagamento registrado com sucesso!';
            header('Location: mensalidades.php');
            exit;

        } catch (Exception $e) {
            $_SESSION['error'] = 'Erro ao registrar pagamento: ' . $e->getMessage();
        }
    }
}

// Busca dados para exibição
if ($action === 'gerar') {
    try {
        // Busca alunos ativos que podem ter mensalidades
        $alunos = $db->fetchAll("
            SELECT a.id, a.nome, a.email, c.nome as curso_nome
            FROM alunos a
            LEFT JOIN cursos c ON a.curso_id = c.id
            WHERE a.status = 'ativo'
            ORDER BY a.nome
        ");
    } catch (Exception $e) {
        $alunos = [];
        $_SESSION['error'] = 'Erro ao buscar alunos: ' . $e->getMessage();
    }
}

if ($action === 'listar') {
    $filtro = $_GET['filtro'] ?? '';
    $busca = $_GET['busca'] ?? '';
    $mesReferencia = $_GET['mes_referencia'] ?? '';

    $where = "1=1";
    $params = [];

    if ($filtro === 'pendentes') {
        $where .= " AND ma.status = 'pendente'";
    } elseif ($filtro === 'pagas') {
        $where .= " AND ma.status = 'pago'";
    } elseif ($filtro === 'vencidas') {
        $where .= " AND ma.status = 'pendente' AND ma.data_vencimento < CURDATE()";
    }

    if ($mesReferencia) {
        $where .= " AND ma.mes_referencia = ?";
        $params[] = $mesReferencia;
    }

    if ($busca) {
        $where .= " AND (a.nome LIKE ? OR a.email LIKE ?)";
        $params[] = "%$busca%";
        $params[] = "%$busca%";
    }

    try {
        $mensalidades = $db->fetchAll("
            SELECT ma.*, a.nome as aluno_nome, a.email as aluno_email, c.nome as curso_nome
            FROM mensalidades_alunos ma
            JOIN alunos a ON ma.aluno_id = a.id
            LEFT JOIN cursos c ON ma.curso_id = c.id
            WHERE $where
            ORDER BY ma.data_vencimento ASC, a.nome ASC
        ", $params);
    } catch (Exception $e) {
        $mensalidades = [];
        $tabelasNaoExistem = true;
    }
}

$pageTitle = 'Mensalidades de Alunos';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Faciência ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/financeiro.css">
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col ml-64">
            <!-- Header -->
            <?php include 'includes/header.php'; ?>

            <!-- Content -->
            <main class="flex-1 p-6 overflow-y-auto">
                <div class="max-w-7xl mx-auto">

                    <?php if (isset($tabelasNaoExistem)): ?>
                    <!-- Alerta de configuração -->
                    <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm">
                                    O módulo financeiro precisa ser configurado primeiro.
                                    <a href="setup.php" class="font-medium underline">Clique aqui para configurar</a>
                                </p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($action === 'listar'): ?>
                    <!-- Listagem de Mensalidades -->
                    <div class="mb-8">
                        <div class="flex justify-between items-center mb-6">
                            <div>
                                <h1 class="text-3xl font-bold text-gray-900">Mensalidades de Alunos</h1>
                                <p class="text-gray-600 mt-2">Gerencie as mensalidades dos alunos específicos</p>
                            </div>
                            <a href="mensalidades.php?action=gerar" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                                <i class="fas fa-plus mr-2"></i>Gerar Mensalidades
                            </a>
                        </div>

                        <!-- Filtros -->
                        <div class="bg-white rounded-lg shadow p-4 mb-6">
                            <form method="GET" class="flex flex-wrap gap-4 items-end">
                                <input type="hidden" name="action" value="listar">
                                <div class="flex-1 min-w-64">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Buscar Aluno</label>
                                    <input type="text" name="busca" value="<?php echo htmlspecialchars($_GET['busca'] ?? ''); ?>"
                                           placeholder="Nome ou email do aluno..."
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                    <select name="filtro" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">Todas</option>
                                        <option value="pendentes" <?php echo ($_GET['filtro'] ?? '') === 'pendentes' ? 'selected' : ''; ?>>Pendentes</option>
                                        <option value="vencidas" <?php echo ($_GET['filtro'] ?? '') === 'vencidas' ? 'selected' : ''; ?>>Vencidas</option>
                                        <option value="pagas" <?php echo ($_GET['filtro'] ?? '') === 'pagas' ? 'selected' : ''; ?>>Pagas</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Mês de Referência</label>
                                    <input type="month" name="mes_referencia" value="<?php echo $_GET['mes_referencia'] ?? ''; ?>"
                                           class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                                    <i class="fas fa-search mr-2"></i>Buscar
                                </button>
                            </form>
                        </div>

                        <!-- Tabela -->
                        <div class="bg-white rounded-lg shadow overflow-hidden">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-blue-600">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Aluno</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Curso</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Mês Ref.</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Valor</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Vencimento</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Ações</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php if (empty($mensalidades)): ?>
                                    <tr>
                                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                            <?php if (isset($tabelasNaoExistem)): ?>
                                            Configure o módulo financeiro primeiro.
                                            <?php else: ?>
                                            Nenhuma mensalidade encontrada.
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($mensalidades as $mensalidade): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($mensalidade['aluno_nome']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($mensalidade['aluno_email']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo htmlspecialchars($mensalidade['curso_nome'] ?? '-'); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo date('m/Y', strtotime($mensalidade['mes_referencia'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            R$ <?php echo number_format($mensalidade['valor'], 2, ',', '.'); ?>
                                            <?php if ($mensalidade['valor_pago'] && $mensalidade['valor_pago'] != $mensalidade['valor']): ?>
                                            <br><span class="text-green-600 text-xs">Pago: R$ <?php echo number_format($mensalidade['valor_pago'], 2, ',', '.'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php
                                            $dataVencimento = new DateTime($mensalidade['data_vencimento']);
                                            echo $dataVencimento->format('d/m/Y');

                                            if ($mensalidade['status'] === 'pendente') {
                                                $hoje = new DateTime();
                                                if ($dataVencimento < $hoje) {
                                                    $diff = $hoje->diff($dataVencimento);
                                                    echo '<br><span class="text-red-600 text-xs">Vencida há ' . $diff->days . ' dia(s)</span>';
                                                }
                                            }
                                            ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php
                                            $statusClass = 'bg-gray-100 text-gray-800';
                                            $statusText = ucfirst($mensalidade['status']);

                                            if ($mensalidade['status'] === 'pago') {
                                                $statusClass = 'bg-green-100 text-green-800';
                                                $statusText = 'Paga';
                                            } elseif ($mensalidade['status'] === 'pendente') {
                                                $dataVencimento = new DateTime($mensalidade['data_vencimento']);
                                                $hoje = new DateTime();

                                                if ($dataVencimento < $hoje) {
                                                    $statusClass = 'bg-red-100 text-red-800';
                                                    $statusText = 'Vencida';
                                                } else {
                                                    $statusClass = 'bg-yellow-100 text-yellow-800';
                                                    $statusText = 'Pendente';
                                                }
                                            }
                                            ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $statusClass; ?>">
                                                <?php echo $statusText; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <?php if ($mensalidade['status'] === 'pendente'): ?>
                                            <button onclick="abrirModalPagamento(<?php echo $mensalidade['id']; ?>, '<?php echo htmlspecialchars($mensalidade['aluno_nome']); ?>', <?php echo $mensalidade['valor']; ?>)"
                                                    class="text-green-600 hover:text-green-900 mr-3">
                                                <i class="fas fa-money-check-alt"></i>
                                            </button>
                                            <?php endif; ?>
                                            <a href="#" class="text-blue-600 hover:text-blue-900">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <?php elseif ($action === 'gerar'): ?>
                    <!-- Formulário de Geração de Mensalidades -->
                    <div class="mb-8">
                        <div class="flex items-center mb-6">
                            <a href="mensalidades.php" class="text-blue-600 hover:text-blue-800 mr-4">
                                <i class="fas fa-arrow-left"></i>
                            </a>
                            <div>
                                <h1 class="text-3xl font-bold text-gray-900">Gerar Mensalidades</h1>
                                <p class="text-gray-600 mt-2">Selecione os alunos e configure as mensalidades</p>
                            </div>
                        </div>

                        <form method="POST" class="bg-white rounded-lg shadow p-6">
                            <input type="hidden" name="action" value="gerar_mensalidades">

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Mês de Referência *</label>
                                    <input type="month" name="mes_referencia" required
                                           value="<?php echo date('Y-m'); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Valor da Mensalidade *</label>
                                    <input type="text" name="valor" required data-mask="currency"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Data de Vencimento *</label>
                                    <input type="date" name="data_vencimento" required
                                           value="<?php echo date('Y-m-10'); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                            </div>

                            <!-- Seleção de Alunos -->
                            <div class="mb-6">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-lg font-medium text-gray-900">Selecionar Alunos</h3>
                                    <div class="space-x-2">
                                        <button type="button" onclick="selecionarTodos()" class="text-blue-600 hover:text-blue-800 text-sm">
                                            Selecionar Todos
                                        </button>
                                        <button type="button" onclick="deselecionarTodos()" class="text-red-600 hover:text-red-800 text-sm">
                                            Desselecionar Todos
                                        </button>
                                    </div>
                                </div>

                                <div class="max-h-96 overflow-y-auto border border-gray-300 rounded-md">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50 sticky top-0">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    <input type="checkbox" id="select-all" onchange="toggleTodos(this)">
                                                </th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aluno</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Curso</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <?php if (empty($alunos)): ?>
                                            <tr>
                                                <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                                                    Nenhum aluno ativo encontrado.
                                                </td>
                                            </tr>
                                            <?php else: ?>
                                            <?php foreach ($alunos as $aluno): ?>
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <input type="checkbox" name="alunos_ids[]" value="<?php echo $aluno['id']; ?>"
                                                           class="aluno-checkbox">
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($aluno['nome']); ?></div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    <?php echo htmlspecialchars($aluno['curso_nome'] ?? '-'); ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <?php echo htmlspecialchars($aluno['email']); ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="flex justify-end space-x-3">
                                <a href="mensalidades.php" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400">
                                    Cancelar
                                </a>
                                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                                    <i class="fas fa-plus mr-2"></i>Gerar Mensalidades
                                </button>
                            </div>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Scripts -->
    <script src="js/financeiro.js"></script>
    <script>
    function abrirModalPagamento(id, alunoNome, valor) {
        const modal = Financeiro.Modal.show('Registrar Pagamento de Mensalidade', `
            <form id="form-pagamento" method="POST">
                <input type="hidden" name="action" value="pagar">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Aluno</label>
                        <p class="text-sm text-gray-900">${alunoNome}</p>
                        <p class="text-sm text-gray-600">Valor: R$ ${valor.toLocaleString('pt-BR', {minimumFractionDigits: 2})}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data do Pagamento</label>
                        <input type="date" name="data_pagamento" value="${new Date().toISOString().split('T')[0]}" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Valor Pago</label>
                        <input type="text" name="valor_pago" value="R$ ${valor.toLocaleString('pt-BR', {minimumFractionDigits: 2})}" required data-mask="currency"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Forma de Pagamento</label>
                        <select name="forma_pagamento" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Selecione</option>
                            <option value="dinheiro">Dinheiro</option>
                            <option value="pix">PIX</option>
                            <option value="transferencia">Transferência</option>
                            <option value="cartao_debito">Cartão de Débito</option>
                            <option value="cartao_credito">Cartão de Crédito</option>
                            <option value="boleto">Boleto</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                        <textarea name="observacoes" rows="2"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                </div>
            </form>
        `, {
            confirmText: 'Registrar Pagamento',
            onConfirm: `document.getElementById('form-pagamento').action = 'mensalidades.php?action=pagar&id=${id}'; document.getElementById('form-pagamento').submit();`
        });

        // Aplicar máscara no campo valor_pago
        const valorPagoInput = modal.querySelector('[name="valor_pago"]');
        if (valorPagoInput) {
            Financeiro.Forms.maskCurrency(valorPagoInput);
        }
    }

    function toggleTodos(checkbox) {
        const checkboxes = document.querySelectorAll('.aluno-checkbox');
        checkboxes.forEach(cb => cb.checked = checkbox.checked);
    }

    function selecionarTodos() {
        const checkboxes = document.querySelectorAll('.aluno-checkbox');
        checkboxes.forEach(cb => cb.checked = true);
        document.getElementById('select-all').checked = true;
    }

    function deselecionarTodos() {
        const checkboxes = document.querySelectorAll('.aluno-checkbox');
        checkboxes.forEach(cb => cb.checked = false);
        document.getElementById('select-all').checked = false;
    }
    </script>
</body>
</html>