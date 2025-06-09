<?php
/**
 * Fluxo de Caixa - Módulo Financeiro
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

// Parâmetros de filtro
$dataInicio = $_GET['data_inicio'] ?? date('Y-m-01'); // Primeiro dia do mês atual
$dataFim = $_GET['data_fim'] ?? date('Y-m-t'); // Último dia do mês atual
$categoria = $_GET['categoria'] ?? '';

// Busca transações para o período
try {
    $where = "tf.data_transacao BETWEEN ? AND ?";
    $params = [$dataInicio, $dataFim];
    
    if ($categoria) {
        $where .= " AND tf.categoria_id = ?";
        $params[] = $categoria;
    }
    
    $transacoes = $db->fetchAll("
        SELECT tf.*, cf.nome as categoria_nome, cb.nome as conta_bancaria_nome
        FROM transacoes_financeiras tf
        LEFT JOIN categorias_financeiras cf ON tf.categoria_id = cf.id
        LEFT JOIN contas_bancarias cb ON tf.conta_bancaria_id = cb.id
        WHERE $where AND tf.status = 'efetivada'
        ORDER BY tf.data_transacao DESC, tf.id DESC
    ", $params);
    
    // Calcula totais
    $totalReceitas = 0;
    $totalDespesas = 0;
    $receitasPorCategoria = [];
    $despesasPorCategoria = [];
    $movimentacaoDiaria = [];
    
    foreach ($transacoes as $transacao) {
        $data = $transacao['data_transacao'];
        $valor = (float) $transacao['valor'];
        $categoria_nome = $transacao['categoria_nome'] ?? 'Sem categoria';
        
        if ($transacao['tipo'] === 'receita') {
            $totalReceitas += $valor;
            $receitasPorCategoria[$categoria_nome] = ($receitasPorCategoria[$categoria_nome] ?? 0) + $valor;
            $movimentacaoDiaria[$data]['receitas'] = ($movimentacaoDiaria[$data]['receitas'] ?? 0) + $valor;
        } elseif ($transacao['tipo'] === 'despesa') {
            $totalDespesas += $valor;
            $despesasPorCategoria[$categoria_nome] = ($despesasPorCategoria[$categoria_nome] ?? 0) + $valor;
            $movimentacaoDiaria[$data]['despesas'] = ($movimentacaoDiaria[$data]['despesas'] ?? 0) + $valor;
        }
    }
    
    $saldoPeriodo = $totalReceitas - $totalDespesas;
    
} catch (Exception $e) {
    $transacoes = [];
    $totalReceitas = 0;
    $totalDespesas = 0;
    $saldoPeriodo = 0;
    $receitasPorCategoria = [];
    $despesasPorCategoria = [];
    $movimentacaoDiaria = [];
    $tabelasNaoExistem = true;
}

// Busca categorias para filtro
try {
    $categorias = $db->fetchAll("
        SELECT * FROM categorias_financeiras 
        WHERE status = 'ativo' 
        ORDER BY tipo, nome
    ");
} catch (Exception $e) {
    $categorias = [];
}

$pageTitle = 'Fluxo de Caixa';
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                    
                    <!-- Header da página -->
                    <div class="mb-8">
                        <h1 class="text-3xl font-bold text-gray-900">Fluxo de Caixa</h1>
                        <p class="text-gray-600 mt-2">Acompanhe as movimentações financeiras da instituição</p>
                    </div>

                    <!-- Filtros -->
                    <div class="bg-white rounded-lg shadow p-4 mb-6">
                        <form method="GET" class="flex flex-wrap gap-4 items-end">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Data Início</label>
                                <input type="date" name="data_inicio" value="<?php echo $dataInicio; ?>"
                                       class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Data Fim</label>
                                <input type="date" name="data_fim" value="<?php echo $dataFim; ?>"
                                       class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Categoria</label>
                                <select name="categoria" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                    <option value="">Todas as categorias</option>
                                    <?php foreach ($categorias as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo $categoria == $cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['nome']) . ' (' . ucfirst($cat['tipo']) . ')'; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                                <i class="fas fa-filter mr-2"></i>Filtrar
                            </button>
                            <a href="fluxo_caixa.php" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400">
                                <i class="fas fa-times mr-2"></i>Limpar
                            </a>
                        </form>
                    </div>

                    <!-- Cards de Resumo -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                        <!-- Total Receitas -->
                        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-green-500">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                                        <i class="fas fa-arrow-up text-white text-sm"></i>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-600">Total Receitas</p>
                                    <p class="text-2xl font-semibold text-green-600">R$ <?php echo number_format($totalReceitas, 2, ',', '.'); ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Total Despesas -->
                        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-red-500">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-red-500 rounded-md flex items-center justify-center">
                                        <i class="fas fa-arrow-down text-white text-sm"></i>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-600">Total Despesas</p>
                                    <p class="text-2xl font-semibold text-red-600">R$ <?php echo number_format($totalDespesas, 2, ',', '.'); ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Saldo do Período -->
                        <div class="bg-white rounded-lg shadow p-6 border-l-4 <?php echo $saldoPeriodo >= 0 ? 'border-blue-500' : 'border-orange-500'; ?>">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 <?php echo $saldoPeriodo >= 0 ? 'bg-blue-500' : 'bg-orange-500'; ?> rounded-md flex items-center justify-center">
                                        <i class="fas fa-balance-scale text-white text-sm"></i>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-600">Saldo do Período</p>
                                    <p class="text-2xl font-semibold <?php echo $saldoPeriodo >= 0 ? 'text-blue-600' : 'text-orange-600'; ?>">
                                        R$ <?php echo number_format($saldoPeriodo, 2, ',', '.'); ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Total de Transações -->
                        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-purple-500">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                                        <i class="fas fa-exchange-alt text-white text-sm"></i>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-600">Total Transações</p>
                                    <p class="text-2xl font-semibold text-purple-600"><?php echo count($transacoes); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Gráficos -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                        <!-- Gráfico de Receitas por Categoria -->
                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Receitas por Categoria</h3>
                            <div class="h-64">
                                <canvas id="receitasChart"></canvas>
                            </div>
                        </div>

                        <!-- Gráfico de Despesas por Categoria -->
                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Despesas por Categoria</h3>
                            <div class="h-64">
                                <canvas id="despesasChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Tabela de Transações -->
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Transações do Período</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descrição</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categoria</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valor</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Conta</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php if (empty($transacoes)): ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                            <?php if (isset($tabelasNaoExistem)): ?>
                                            Configure o módulo financeiro primeiro.
                                            <?php else: ?>
                                            Nenhuma transação encontrada no período.
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($transacoes as $transacao): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo date('d/m/Y', strtotime($transacao['data_transacao'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($transacao['descricao']); ?></div>
                                            <?php if ($transacao['observacoes']): ?>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($transacao['observacoes']); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo htmlspecialchars($transacao['categoria_nome'] ?? '-'); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                <?php echo $transacao['tipo'] === 'receita' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                <?php echo $transacao['tipo'] === 'receita' ? 'Receita' : 'Despesa'; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium 
                                            <?php echo $transacao['tipo'] === 'receita' ? 'text-green-600' : 'text-red-600'; ?>">
                                            <?php echo $transacao['tipo'] === 'receita' ? '+' : '-'; ?>R$ <?php echo number_format($transacao['valor'], 2, ',', '.'); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo htmlspecialchars($transacao['conta_bancaria_nome'] ?? '-'); ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Scripts -->
    <script src="js/financeiro.js"></script>
    <script>
    // Dados para os gráficos
    const receitasData = <?php echo json_encode($receitasPorCategoria); ?>;
    const despesasData = <?php echo json_encode($despesasPorCategoria); ?>;

    // Gráfico de Receitas
    const receitasCtx = document.getElementById('receitasChart').getContext('2d');
    new Chart(receitasCtx, {
        type: 'doughnut',
        data: {
            labels: Object.keys(receitasData),
            datasets: [{
                data: Object.values(receitasData),
                backgroundColor: [
                    '#10b981', '#059669', '#34d399', '#6ee7b7', '#a7f3d0'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Gráfico de Despesas
    const despesasCtx = document.getElementById('despesasChart').getContext('2d');
    new Chart(despesasCtx, {
        type: 'doughnut',
        data: {
            labels: Object.keys(despesasData),
            datasets: [{
                data: Object.values(despesasData),
                backgroundColor: [
                    '#ef4444', '#dc2626', '#f87171', '#fca5a5', '#fecaca'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    </script>
</body>
</html>
