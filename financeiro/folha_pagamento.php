<?php
/**
 * Folha de Pagamento - Módulo Financeiro
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

$db = Database::getInstance();
$action = $_GET['action'] ?? 'listar';
$folhaId = $_GET['id'] ?? null;

// Processa ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'gerar_folha') {
        try {
            $mesReferencia = $_POST['mes_referencia'];
            $funcionariosIds = $_POST['funcionarios_ids'] ?? [];

            $geradas = 0;
            foreach ($funcionariosIds as $funcionarioId) {
                // Verifica se já existe folha para este funcionário no mês
                $existe = $db->fetchOne("
                    SELECT id FROM folha_pagamento
                    WHERE funcionario_id = ? AND mes_referencia = ?
                ", [$funcionarioId, $mesReferencia]);

                if (!$existe) {
                    $funcionario = $db->fetchOne("SELECT * FROM funcionarios WHERE id = ?", [$funcionarioId]);

                    if ($funcionario) {
                        // Cálculos básicos da folha
                        $salarioBase = $funcionario['salario'];
                        $inss = $salarioBase * 0.11; // 11% simplificado
                        $irrf = $salarioBase > 1903.98 ? ($salarioBase * 0.075) : 0; // Simplificado
                        $salarioLiquido = $salarioBase - $inss - $irrf;

                        $dados = [
                            'funcionario_id' => $funcionarioId,
                            'mes_referencia' => $mesReferencia,
                            'salario_base' => $salarioBase,
                            'inss' => $inss,
                            'irrf' => $irrf,
                            'salario_liquido' => $salarioLiquido,
                            'usuario_id' => Auth::getUserId()
                        ];

                        $db->insert('folha_pagamento', $dados);
                        $geradas++;
                    }
                }
            }

            $_SESSION['success'] = "$geradas folha(s) de pagamento gerada(s) com sucesso!";
            header('Location: folha_pagamento.php');
            exit;

        } catch (Exception $e) {
            $_SESSION['error'] = 'Erro ao gerar folhas: ' . $e->getMessage();
        }
    }

    if ($action === 'pagar' && $folhaId) {
        try {
            $dados = [
                'status' => 'paga',
                'data_pagamento' => $_POST['data_pagamento'] ?? date('Y-m-d'),
                'observacoes' => $_POST['observacoes'] ?? null,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $db->update('folha_pagamento', $dados, 'id = ?', [$folhaId]);

            // Registra na conta a pagar
            $folha = $db->fetchOne("
                SELECT fp.*, f.nome as funcionario_nome
                FROM folha_pagamento fp
                JOIN funcionarios f ON fp.funcionario_id = f.id
                WHERE fp.id = ?
            ", [$folhaId]);

            if ($folha) {
                $contaPagar = [
                    'descricao' => 'Salário - ' . $folha['funcionario_nome'] . ' (' . date('m/Y', strtotime($folha['mes_referencia'])) . ')',
                    'valor' => $folha['salario_liquido'],
                    'data_vencimento' => $dados['data_pagamento'],
                    'data_pagamento' => $dados['data_pagamento'],
                    'fornecedor_nome' => $folha['funcionario_nome'],
                    'categoria_id' => 4, // Categoria "Salários e Encargos"
                    'forma_pagamento' => 'transferencia',
                    'status' => 'pago',
                    'observacoes' => $dados['observacoes'],
                    'usuario_id' => Auth::getUserId()
                ];

                $db->insert('contas_pagar', $contaPagar);

                // Registra transação financeira
                $transacao = [
                    'tipo' => 'despesa',
                    'descricao' => $contaPagar['descricao'],
                    'valor' => $folha['salario_liquido'],
                    'data_transacao' => $dados['data_pagamento'],
                    'categoria_id' => 4,
                    'forma_pagamento' => 'transferencia',
                    'referencia_tipo' => 'folha_pagamento',
                    'referencia_id' => $folhaId,
                    'observacoes' => $dados['observacoes'],
                    'usuario_id' => Auth::getUserId()
                ];

                $db->insert('transacoes_financeiras', $transacao);
            }

            $_SESSION['success'] = 'Pagamento registrado com sucesso!';
            header('Location: folha_pagamento.php');
            exit;

        } catch (Exception $e) {
            $_SESSION['error'] = 'Erro ao registrar pagamento: ' . $e->getMessage();
        }
    }
}

// Busca dados para exibição
if ($action === 'gerar') {
    try {
        // Busca funcionários ativos
        $funcionarios = $db->fetchAll("
            SELECT * FROM funcionarios
            WHERE status = 'ativo'
            ORDER BY nome
        ");
    } catch (Exception $e) {
        $funcionarios = [];
        $_SESSION['error'] = 'Erro ao buscar funcionários: ' . $e->getMessage();
    }
}

if ($action === 'listar') {
    $filtro = $_GET['filtro'] ?? '';
    $busca = $_GET['busca'] ?? '';
    $mesReferencia = $_GET['mes_referencia'] ?? '';

    $where = "1=1";
    $params = [];

    if ($filtro === 'calculadas') {
        $where .= " AND fp.status = 'calculada'";
    } elseif ($filtro === 'pagas') {
        $where .= " AND fp.status = 'paga'";
    }

    if ($mesReferencia) {
        $where .= " AND fp.mes_referencia = ?";
        $params[] = $mesReferencia;
    }

    if ($busca) {
        $where .= " AND f.nome LIKE ?";
        $params[] = "%$busca%";
    }

    try {
        $folhas = $db->fetchAll("
            SELECT fp.*, f.nome as funcionario_nome, f.cargo
            FROM folha_pagamento fp
            JOIN funcionarios f ON fp.funcionario_id = f.id
            WHERE $where
            ORDER BY fp.mes_referencia DESC, f.nome ASC
        ", $params);
    } catch (Exception $e) {
        $folhas = [];
        $tabelasNaoExistem = true;
    }
}

$pageTitle = 'Folha de Pagamento';
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
                    <!-- Listagem de Folhas de Pagamento -->
                    <div class="mb-8">
                        <div class="flex justify-between items-center mb-6">
                            <div>
                                <h1 class="text-3xl font-bold text-gray-900">Folha de Pagamento</h1>
                                <p class="text-gray-600 mt-2">Gerencie a folha de pagamento dos funcionários</p>
                            </div>
                            <a href="folha_pagamento.php?action=gerar" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                                <i class="fas fa-plus mr-2"></i>Gerar Folha
                            </a>
                        </div>

                        <!-- Filtros -->
                        <div class="bg-white rounded-lg shadow p-4 mb-6">
                            <form method="GET" class="flex flex-wrap gap-4 items-end">
                                <input type="hidden" name="action" value="listar">
                                <div class="flex-1 min-w-64">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Buscar Funcionário</label>
                                    <input type="text" name="busca" value="<?php echo htmlspecialchars($_GET['busca'] ?? ''); ?>"
                                           placeholder="Nome do funcionário..."
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                    <select name="filtro" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                        <option value="">Todas</option>
                                        <option value="calculadas" <?php echo ($_GET['filtro'] ?? '') === 'calculadas' ? 'selected' : ''; ?>>Calculadas</option>
                                        <option value="pagas" <?php echo ($_GET['filtro'] ?? '') === 'pagas' ? 'selected' : ''; ?>>Pagas</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Mês de Referência</label>
                                    <input type="month" name="mes_referencia" value="<?php echo $_GET['mes_referencia'] ?? ''; ?>"
                                           class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                </div>
                                <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                                    <i class="fas fa-search mr-2"></i>Buscar
                                </button>
                            </form>
                        </div>

                        <!-- Tabela -->
                        <div class="bg-white rounded-lg shadow overflow-hidden">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-indigo-600">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Funcionário</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Cargo</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Mês Ref.</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Sal. Base</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Descontos</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Sal. Líquido</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Ações</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php if (empty($folhas)): ?>
                                    <tr>
                                        <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                                            <?php if (isset($tabelasNaoExistem)): ?>
                                            Configure o módulo financeiro primeiro.
                                            <?php else: ?>
                                            Nenhuma folha de pagamento encontrada.
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($folhas as $folha): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($folha['funcionario_nome']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo htmlspecialchars($folha['cargo']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo date('m/Y', strtotime($folha['mes_referencia'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            R$ <?php echo number_format($folha['salario_base'], 2, ',', '.'); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            R$ <?php echo number_format($folha['inss'] + $folha['irrf'], 2, ',', '.'); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            R$ <?php echo number_format($folha['salario_liquido'], 2, ',', '.'); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php
                                            $statusClass = 'bg-gray-100 text-gray-800';
                                            $statusText = ucfirst($folha['status']);

                                            if ($folha['status'] === 'paga') {
                                                $statusClass = 'bg-green-100 text-green-800';
                                                $statusText = 'Paga';
                                            } elseif ($folha['status'] === 'calculada') {
                                                $statusClass = 'bg-yellow-100 text-yellow-800';
                                                $statusText = 'Calculada';
                                            }
                                            ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $statusClass; ?>">
                                                <?php echo $statusText; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <?php if ($folha['status'] === 'calculada'): ?>
                                            <button onclick="abrirModalPagamento(<?php echo $folha['id']; ?>, '<?php echo htmlspecialchars($folha['funcionario_nome']); ?>', <?php echo $folha['salario_liquido']; ?>)"
                                                    class="text-green-600 hover:text-green-900 mr-3">
                                                <i class="fas fa-money-check-alt"></i>
                                            </button>
                                            <?php endif; ?>
                                            <a href="#" class="text-indigo-600 hover:text-indigo-900">
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
                    <!-- Formulário de Geração de Folha -->
                    <div class="mb-8">
                        <div class="flex items-center mb-6">
                            <a href="folha_pagamento.php" class="text-indigo-600 hover:text-indigo-800 mr-4">
                                <i class="fas fa-arrow-left"></i>
                            </a>
                            <div>
                                <h1 class="text-3xl font-bold text-gray-900">Gerar Folha de Pagamento</h1>
                                <p class="text-gray-600 mt-2">Selecione os funcionários e o mês de referência</p>
                            </div>
                        </div>

                        <form method="POST" class="bg-white rounded-lg shadow p-6">
                            <input type="hidden" name="action" value="gerar_folha">

                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Mês de Referência *</label>
                                <input type="month" name="mes_referencia" required
                                       value="<?php echo date('Y-m'); ?>"
                                       class="w-full max-w-xs px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>

                            <!-- Seleção de Funcionários -->
                            <div class="mb-6">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-lg font-medium text-gray-900">Selecionar Funcionários</h3>
                                    <div class="space-x-2">
                                        <button type="button" onclick="selecionarTodos()" class="text-indigo-600 hover:text-indigo-800 text-sm">
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
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Funcionário</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cargo</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Salário</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <?php if (empty($funcionarios)): ?>
                                            <tr>
                                                <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                                                    Nenhum funcionário ativo encontrado.
                                                </td>
                                            </tr>
                                            <?php else: ?>
                                            <?php foreach ($funcionarios as $funcionario): ?>
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <input type="checkbox" name="funcionarios_ids[]" value="<?php echo $funcionario['id']; ?>"
                                                           class="funcionario-checkbox">
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($funcionario['nome']); ?></div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    <?php echo htmlspecialchars($funcionario['cargo']); ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    R$ <?php echo number_format($funcionario['salario'], 2, ',', '.'); ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="flex justify-end space-x-3">
                                <a href="folha_pagamento.php" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400">
                                    Cancelar
                                </a>
                                <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                                    <i class="fas fa-plus mr-2"></i>Gerar Folha de Pagamento
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
    function abrirModalPagamento(id, funcionarioNome, salarioLiquido) {
        const modal = Financeiro.Modal.show('Registrar Pagamento de Salário', `
            <form id="form-pagamento" method="POST">
                <input type="hidden" name="action" value="pagar">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Funcionário</label>
                        <p class="text-sm text-gray-900">${funcionarioNome}</p>
                        <p class="text-sm text-gray-600">Salário Líquido: R$ ${salarioLiquido.toLocaleString('pt-BR', {minimumFractionDigits: 2})}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data do Pagamento</label>
                        <input type="date" name="data_pagamento" value="${new Date().toISOString().split('T')[0]}" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                        <textarea name="observacoes" rows="2"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                    </div>
                </div>
            </form>
        `, {
            confirmText: 'Registrar Pagamento',
            onConfirm: `document.getElementById('form-pagamento').action = 'folha_pagamento.php?action=pagar&id=${id}'; document.getElementById('form-pagamento').submit();`
        });
    }

    function toggleTodos(checkbox) {
        const checkboxes = document.querySelectorAll('.funcionario-checkbox');
        checkboxes.forEach(cb => cb.checked = checkbox.checked);
    }

    function selecionarTodos() {
        const checkboxes = document.querySelectorAll('.funcionario-checkbox');
        checkboxes.forEach(cb => cb.checked = true);
        document.getElementById('select-all').checked = true;
    }

    function deselecionarTodos() {
        const checkboxes = document.querySelectorAll('.funcionario-checkbox');
        checkboxes.forEach(cb => cb.checked = false);
        document.getElementById('select-all').checked = false;
    }
    </script>
</body>
</html>