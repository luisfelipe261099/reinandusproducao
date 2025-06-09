<?php
require_once '../includes/init.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

Auth::requireLogin();
$userType = Auth::getUserType();
if (!in_array($userType, ['financeiro', 'admin_master'])) {
    $_SESSION['error'] = 'Você não tem permissão para acessar o módulo financeiro.';
    header('Location: ../index.php');
    exit;
}

$db = Database::getInstance();
$relatorio = $_GET['relatorio'] ?? '';
$dataInicio = $_GET['data_inicio'] ?? date('Y-m-01');
$dataFim = $_GET['data_fim'] ?? date('Y-m-t');

$dados = [];
try {
    if ($relatorio === 'receitas_despesas') {
        $dados = $db->fetchAll("
            SELECT 
                DATE(data_transacao) as data,
                tipo,
                SUM(valor) as total
            FROM transacoes_financeiras 
            WHERE data_transacao BETWEEN ? AND ? AND status = 'efetivada'
            GROUP BY DATE(data_transacao), tipo
            ORDER BY data_transacao
        ", [$dataInicio, $dataFim]);
    } elseif ($relatorio === 'categorias') {
        $dados = $db->fetchAll("
            SELECT 
                cf.nome as categoria,
                tf.tipo,
                SUM(tf.valor) as total,
                COUNT(*) as quantidade
            FROM transacoes_financeiras tf
            LEFT JOIN categorias_financeiras cf ON tf.categoria_id = cf.id
            WHERE tf.data_transacao BETWEEN ? AND ? AND tf.status = 'efetivada'
            GROUP BY tf.categoria_id, tf.tipo
            ORDER BY total DESC
        ", [$dataInicio, $dataFim]);
    } elseif ($relatorio === 'contas_pagar') {
        $dados = $db->fetchAll("
            SELECT 
                descricao,
                fornecedor_nome,
                valor,
                data_vencimento,
                data_pagamento,
                status
            FROM contas_pagar 
            WHERE data_vencimento BETWEEN ? AND ?
            ORDER BY data_vencimento
        ", [$dataInicio, $dataFim]);
    } elseif ($relatorio === 'contas_receber') {
        $dados = $db->fetchAll("
            SELECT 
                descricao,
                cliente_nome,
                cliente_tipo,
                valor,
                data_vencimento,
                data_recebimento,
                status
            FROM contas_receber 
            WHERE data_vencimento BETWEEN ? AND ?
            ORDER BY data_vencimento
        ", [$dataInicio, $dataFim]);
    }
} catch (Exception $e) {
    $tabelasNaoExistem = true;
}

$pageTitle = 'Relatórios Financeiros';
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
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="flex-1 flex flex-col ml-64">
            <?php include 'includes/header.php'; ?>
            
            <main class="flex-1 p-6 overflow-y-auto">
                <div class="max-w-7xl mx-auto">
                    
                    <?php if (isset($tabelasNaoExistem)): ?>
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
                    
                    <div class="mb-8">
                        <h1 class="text-3xl font-bold text-gray-900">Relatórios Financeiros</h1>
                        <p class="text-gray-600 mt-2">Gere relatórios detalhados das movimentações financeiras</p>
                    </div>

                    <!-- Seleção de Relatório -->
                    <div class="bg-white rounded-lg shadow p-6 mb-6">
                        <form method="GET" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Relatório</label>
                                    <select name="relatorio" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                        <option value="">Selecione um relatório</option>
                                        <option value="receitas_despesas" <?php echo $relatorio === 'receitas_despesas' ? 'selected' : ''; ?>>Receitas x Despesas</option>
                                        <option value="categorias" <?php echo $relatorio === 'categorias' ? 'selected' : ''; ?>>Por Categorias</option>
                                        <option value="contas_pagar" <?php echo $relatorio === 'contas_pagar' ? 'selected' : ''; ?>>Contas a Pagar</option>
                                        <option value="contas_receber" <?php echo $relatorio === 'contas_receber' ? 'selected' : ''; ?>>Contas a Receber</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Data Início</label>
                                    <input type="date" name="data_inicio" value="<?php echo $dataInicio; ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Data Fim</label>
                                    <input type="date" name="data_fim" value="<?php echo $dataFim; ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                </div>
                                <div class="flex items-end">
                                    <button type="submit" class="w-full bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                                        <i class="fas fa-chart-bar mr-2"></i>Gerar Relatório
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <?php if ($relatorio && !empty($dados)): ?>
                    <!-- Relatório Gerado -->
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                            <h3 class="text-lg font-semibold text-gray-900">
                                <?php 
                                $titulos = [
                                    'receitas_despesas' => 'Relatório de Receitas x Despesas',
                                    'categorias' => 'Relatório por Categorias',
                                    'contas_pagar' => 'Relatório de Contas a Pagar',
                                    'contas_receber' => 'Relatório de Contas a Receber'
                                ];
                                echo $titulos[$relatorio] ?? 'Relatório';
                                ?>
                            </h3>
                            <button onclick="window.print()" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                                <i class="fas fa-print mr-2"></i>Imprimir
                            </button>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <?php if ($relatorio === 'receitas_despesas'): ?>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                                        <?php elseif ($relatorio === 'categorias'): ?>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Categoria</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantidade</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                                        <?php elseif ($relatorio === 'contas_pagar'): ?>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Descrição</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fornecedor</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Valor</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vencimento</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <?php elseif ($relatorio === 'contas_receber'): ?>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Descrição</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Valor</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vencimento</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($dados as $item): ?>
                                    <tr>
                                        <?php if ($relatorio === 'receitas_despesas'): ?>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo date('d/m/Y', strtotime($item['data'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                <?php echo $item['tipo'] === 'receita' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                <?php echo ucfirst($item['tipo']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium 
                                            <?php echo $item['tipo'] === 'receita' ? 'text-green-600' : 'text-red-600'; ?>">
                                            R$ <?php echo number_format($item['total'], 2, ',', '.'); ?>
                                        </td>
                                        
                                        <?php elseif ($relatorio === 'categorias'): ?>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo htmlspecialchars($item['categoria'] ?? 'Sem categoria'); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                <?php echo $item['tipo'] === 'receita' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                <?php echo ucfirst($item['tipo']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo $item['quantidade']; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium 
                                            <?php echo $item['tipo'] === 'receita' ? 'text-green-600' : 'text-red-600'; ?>">
                                            R$ <?php echo number_format($item['total'], 2, ',', '.'); ?>
                                        </td>
                                        
                                        <?php elseif ($relatorio === 'contas_pagar'): ?>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo htmlspecialchars($item['descricao']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo htmlspecialchars($item['fornecedor_nome'] ?? '-'); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            R$ <?php echo number_format($item['valor'], 2, ',', '.'); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo date('d/m/Y', strtotime($item['data_vencimento'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                <?php 
                                                switch($item['status']) {
                                                    case 'pago': echo 'bg-green-100 text-green-800'; break;
                                                    case 'pendente': echo 'bg-yellow-100 text-yellow-800'; break;
                                                    default: echo 'bg-gray-100 text-gray-800';
                                                }
                                                ?>">
                                                <?php echo ucfirst($item['status']); ?>
                                            </span>
                                        </td>
                                        
                                        <?php elseif ($relatorio === 'contas_receber'): ?>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo htmlspecialchars($item['descricao']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo htmlspecialchars($item['cliente_nome'] ?? '-'); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo ucfirst($item['cliente_tipo']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            R$ <?php echo number_format($item['valor'], 2, ',', '.'); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo date('d/m/Y', strtotime($item['data_vencimento'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                <?php 
                                                switch($item['status']) {
                                                    case 'recebido': echo 'bg-green-100 text-green-800'; break;
                                                    case 'pendente': echo 'bg-yellow-100 text-yellow-800'; break;
                                                    default: echo 'bg-gray-100 text-gray-800';
                                                }
                                                ?>">
                                                <?php echo ucfirst($item['status']); ?>
                                            </span>
                                        </td>
                                        <?php endif; ?>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php elseif ($relatorio): ?>
                    <div class="bg-white rounded-lg shadow p-6 text-center">
                        <i class="fas fa-chart-bar text-gray-400 text-4xl mb-4"></i>
                        <p class="text-gray-500">Nenhum dado encontrado para o período selecionado.</p>
                    </div>
                    <?php else: ?>
                    <!-- Cards de Relatórios Disponíveis -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <a href="?relatorio=receitas_despesas&data_inicio=<?php echo $dataInicio; ?>&data_fim=<?php echo $dataFim; ?>" 
                           class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
                            <div class="flex items-center">
                                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-chart-line text-green-600 text-xl"></i>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-semibold text-gray-900">Receitas x Despesas</h3>
                                    <p class="text-sm text-gray-600">Comparativo diário</p>
                                </div>
                            </div>
                        </a>

                        <a href="?relatorio=categorias&data_inicio=<?php echo $dataInicio; ?>&data_fim=<?php echo $dataFim; ?>" 
                           class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
                            <div class="flex items-center">
                                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-tags text-blue-600 text-xl"></i>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-semibold text-gray-900">Por Categorias</h3>
                                    <p class="text-sm text-gray-600">Agrupado por categoria</p>
                                </div>
                            </div>
                        </a>

                        <a href="?relatorio=contas_pagar&data_inicio=<?php echo $dataInicio; ?>&data_fim=<?php echo $dataFim; ?>" 
                           class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
                            <div class="flex items-center">
                                <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-file-invoice text-red-600 text-xl"></i>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-semibold text-gray-900">Contas a Pagar</h3>
                                    <p class="text-sm text-gray-600">Relatório detalhado</p>
                                </div>
                            </div>
                        </a>

                        <a href="?relatorio=contas_receber&data_inicio=<?php echo $dataInicio; ?>&data_fim=<?php echo $dataFim; ?>" 
                           class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
                            <div class="flex items-center">
                                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-file-invoice-dollar text-purple-600 text-xl"></i>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-semibold text-gray-900">Contas a Receber</h3>
                                    <p class="text-sm text-gray-600">Relatório detalhado</p>
                                </div>
                            </div>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <script src="js/financeiro.js"></script>
</body>
</html>
