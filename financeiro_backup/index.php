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

// Obtém estatísticas financeiras
try {
    // Total de receitas no mês atual
    $inicio_mes = date('Y-m-01');
    $fim_mes = date('Y-m-t');

    $sql = "SELECT COALESCE(SUM(valor), 0) as total FROM transacoes
            WHERE tipo = 'receita' AND data_transacao BETWEEN ? AND ?";
    $receitas_mes = $db->fetchOne($sql, [$inicio_mes, $fim_mes]);
    $total_receitas_mes = $receitas_mes ? $receitas_mes['total'] : 0;

    // Total de despesas no mês atual
    $sql = "SELECT COALESCE(SUM(valor), 0) as total FROM transacoes
            WHERE tipo = 'despesa' AND data_transacao BETWEEN ? AND ?";
    $despesas_mes = $db->fetchOne($sql, [$inicio_mes, $fim_mes]);
    $total_despesas_mes = $despesas_mes ? $despesas_mes['total'] : 0;

    // Saldo do mês
    $saldo_mes = $total_receitas_mes - $total_despesas_mes;

    // Transações recentes
    $sql = "SELECT t.*, c.nome as categoria_nome
            FROM transacoes t
            LEFT JOIN categorias_financeiras c ON t.categoria_id = c.id
            ORDER BY t.data_transacao DESC, t.id DESC
            LIMIT 10";
    $transacoes_recentes = $db->fetchAll($sql);

    // Receitas por categoria
    $sql = "SELECT c.nome, COALESCE(SUM(t.valor), 0) as total
            FROM categorias_financeiras c
            LEFT JOIN transacoes t ON c.id = t.categoria_id AND t.tipo = 'receita'
                AND t.data_transacao BETWEEN ? AND ?
            WHERE c.tipo = 'receita'
            GROUP BY c.id
            ORDER BY total DESC
            LIMIT 5";
    $receitas_por_categoria = $db->fetchAll($sql, [$inicio_mes, $fim_mes]);

    // Despesas por categoria
    $sql = "SELECT c.nome, COALESCE(SUM(t.valor), 0) as total
            FROM categorias_financeiras c
            LEFT JOIN transacoes t ON c.id = t.categoria_id AND t.tipo = 'despesa'
                AND t.data_transacao BETWEEN ? AND ?
            WHERE c.tipo = 'despesa'
            GROUP BY c.id
            ORDER BY total DESC
            LIMIT 5";
    $despesas_por_categoria = $db->fetchAll($sql, [$inicio_mes, $fim_mes]);

    // Contas a receber em aberto
    $sql = "SELECT COUNT(*) as total, COALESCE(SUM(valor), 0) as valor_total
            FROM contas_receber
            WHERE status = 'pendente'";
    $contas_receber = $db->fetchOne($sql);
    $total_contas_receber = $contas_receber ? $contas_receber['total'] : 0;
    $valor_contas_receber = $contas_receber ? $contas_receber['valor_total'] : 0;

    // Contas a pagar em aberto
    $sql = "SELECT COUNT(*) as total, COALESCE(SUM(valor), 0) as valor_total
            FROM contas_pagar
            WHERE status = 'pendente'";
    $contas_pagar = $db->fetchOne($sql);
    $total_contas_pagar = $contas_pagar ? $contas_pagar['total'] : 0;
    $valor_contas_pagar = $contas_pagar ? $contas_pagar['valor_total'] : 0;

} catch (Exception $e) {
    // Em caso de erro, define valores padrão
    error_log('Erro ao buscar estatísticas financeiras: ' . $e->getMessage());
    $total_receitas_mes = 0;
    $total_despesas_mes = 0;
    $saldo_mes = 0;
    $transacoes_recentes = [];
    $receitas_por_categoria = [];
    $despesas_por_categoria = [];
    $total_contas_receber = 0;
    $valor_contas_receber = 0;
    $total_contas_pagar = 0;
    $valor_contas_pagar = 0;
}

// Verifica se as tabelas financeiras existem
$tabelas_existem = true;
try {
    $tabelas = ['transacoes', 'categorias_financeiras', 'contas_receber', 'contas_pagar'];
    foreach ($tabelas as $tabela) {
        $sql = "SHOW TABLES LIKE '$tabela'";
        $result = $db->fetchOne($sql);
        if (!$result) {
            $tabelas_existem = false;
            break;
        }
    }
} catch (Exception $e) {
    $tabelas_existem = false;
    error_log('Erro ao verificar tabelas financeiras: ' . $e->getMessage());
}

// Define o título da página
$titulo_pagina = 'Dashboard Financeiro';
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

        /* Estilos específicos para o dashboard financeiro */
        .card {
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .fade-in {
            animation: fadeIn 0.5s ease-out forwards;
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

                    <div class="flex justify-between items-center mb-8">
                        <p class="text-gray-600">Gestão financeira completa da instituição</p>

                        <div class="flex space-x-3">
                            <a href="transacoes.php?action=nova" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded">
                                <i class="fas fa-plus mr-2"></i> Nova Transação
                            </a>
                            <div class="relative" id="acoes-dropdown">
                                <button id="acoes-button" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-2 px-4 rounded flex items-center">
                                    <i class="fas fa-cog mr-2"></i> Ações <i class="fas fa-chevron-down ml-1"></i>
                                </button>
                                <div id="acoes-menu" class="hidden absolute right-0 mt-2 w-56 bg-white rounded-md shadow-lg z-10 overflow-hidden">
                                    <div class="py-1">
                                        <a href="contas_receber.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            <i class="fas fa-file-invoice-dollar mr-2 text-green-500"></i> Contas a Receber
                                        </a>
                                        <a href="contas_pagar.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            <i class="fas fa-file-invoice mr-2 text-red-500"></i> Contas a Pagar
                                        </a>
                                        <a href="relatorios.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            <i class="fas fa-chart-bar mr-2 text-blue-500"></i> Relatórios
                                        </a>
                                        <a href="categorias.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            <i class="fas fa-tags mr-2 text-purple-500"></i> Categorias
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if (!$tabelas_existem): ?>
                    <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm">
                                    As tabelas necessárias para o módulo financeiro não foram encontradas.
                                    <a href="setup.php" class="font-medium underline">Clique aqui</a> para configurar o módulo financeiro.
                                </p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Resumo Financeiro -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <!-- Receitas do Mês -->
                        <div class="bg-white rounded-xl shadow-sm p-6 card fade-in" style="animation-delay: 0.1s">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-green-100 rounded-full p-3">
                                    <i class="fas fa-arrow-down text-green-600 text-xl"></i>
                                </div>
                                <div class="ml-4">
                                    <h2 class="text-sm font-medium text-gray-500">Receitas do Mês</h2>
                                    <p class="text-2xl font-bold text-gray-800">R$ <?php echo number_format($total_receitas_mes, 2, ',', '.'); ?></p>
                                </div>
                            </div>
                            <div class="mt-3">
                                <a href="transacoes.php?tipo=receita" class="text-sm text-green-600 hover:text-green-800">Ver detalhes</a>
                            </div>
                        </div>

                        <!-- Despesas do Mês -->
                        <div class="bg-white rounded-xl shadow-sm p-6 card fade-in" style="animation-delay: 0.2s">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-red-100 rounded-full p-3">
                                    <i class="fas fa-arrow-up text-red-600 text-xl"></i>
                                </div>
                                <div class="ml-4">
                                    <h2 class="text-sm font-medium text-gray-500">Despesas do Mês</h2>
                                    <p class="text-2xl font-bold text-gray-800">R$ <?php echo number_format($total_despesas_mes, 2, ',', '.'); ?></p>
                                </div>
                            </div>
                            <div class="mt-3">
                                <a href="transacoes.php?tipo=despesa" class="text-sm text-red-600 hover:text-red-800">Ver detalhes</a>
                            </div>
                        </div>

                        <!-- Saldo do Mês -->
                        <div class="bg-white rounded-xl shadow-sm p-6 card fade-in" style="animation-delay: 0.3s">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-blue-100 rounded-full p-3">
                                    <i class="fas fa-balance-scale text-blue-600 text-xl"></i>
                                </div>
                                <div class="ml-4">
                                    <h2 class="text-sm font-medium text-gray-500">Saldo do Mês</h2>
                                    <p class="text-2xl font-bold <?php echo $saldo_mes >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                        R$ <?php echo number_format($saldo_mes, 2, ',', '.'); ?>
                                    </p>
                                </div>
                            </div>
                            <div class="mt-3">
                                <a href="relatorios.php?tipo=fluxo_caixa" class="text-sm text-blue-600 hover:text-blue-800">Ver relatório</a>
                            </div>
                        </div>
                    </div>

                    <!-- Contas a Pagar e Receber -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <!-- Contas a Receber -->
                        <div class="bg-white rounded-xl shadow-sm overflow-hidden card fade-in" style="animation-delay: 0.4s">
                            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                                <h3 class="text-lg font-semibold text-gray-800">Contas a Receber</h3>
                                <a href="contas_receber.php" class="text-sm text-blue-600 hover:text-blue-800">Ver todas</a>
                            </div>
                            <div class="p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <div>
                                        <p class="text-sm text-gray-500">Total de contas pendentes</p>
                                        <p class="text-xl font-bold text-gray-800"><?php echo $total_contas_receber; ?></p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Valor total a receber</p>
                                        <p class="text-xl font-bold text-green-600">R$ <?php echo number_format($valor_contas_receber, 2, ',', '.'); ?></p>
                                    </div>
                                </div>
                                <a href="contas_receber.php?action=nova" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded w-full text-center block">
                                    <i class="fas fa-plus mr-2"></i> Nova Conta a Receber
                                </a>
                            </div>
                        </div>

                        <!-- Contas a Pagar -->
                        <div class="bg-white rounded-xl shadow-sm overflow-hidden card fade-in" style="animation-delay: 0.5s">
                            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                                <h3 class="text-lg font-semibold text-gray-800">Contas a Pagar</h3>
                                <a href="contas_pagar.php" class="text-sm text-blue-600 hover:text-blue-800">Ver todas</a>
                            </div>
                            <div class="p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <div>
                                        <p class="text-sm text-gray-500">Total de contas pendentes</p>
                                        <p class="text-xl font-bold text-gray-800"><?php echo $total_contas_pagar; ?></p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Valor total a pagar</p>
                                        <p class="text-xl font-bold text-red-600">R$ <?php echo number_format($valor_contas_pagar, 2, ',', '.'); ?></p>
                                    </div>
                                </div>
                                <a href="contas_pagar.php?action=nova" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded w-full text-center block">
                                    <i class="fas fa-plus mr-2"></i> Nova Conta a Pagar
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Transações Recentes e Gráficos -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <!-- Transações Recentes -->
                        <div class="md:col-span-2 bg-white rounded-xl shadow-sm overflow-hidden card fade-in" style="animation-delay: 0.6s">
                            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                                <h3 class="text-lg font-semibold text-gray-800">Transações Recentes</h3>
                                <a href="transacoes.php" class="text-sm text-blue-600 hover:text-blue-800">Ver todas</a>
                            </div>
                            <?php if (empty($transacoes_recentes)): ?>
                            <div class="p-6 text-center text-gray-500">
                                <p>Nenhuma transação encontrada.</p>
                                <p class="mt-2">
                                    <a href="transacoes.php?action=nova" class="text-blue-600 hover:text-blue-800">
                                        <i class="fas fa-plus mr-1"></i> Adicionar Transação
                                    </a>
                                </p>
                            </div>
                            <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descrição</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categoria</th>
                                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Valor</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($transacoes_recentes as $transacao): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">
                                                    <?php echo date('d/m/Y', strtotime($transacao['data_transacao'])); ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($transacao['descricao']); ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">
                                                    <?php echo htmlspecialchars($transacao['categoria_nome'] ?? 'Sem categoria'); ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                                <div class="text-sm font-medium <?php echo $transacao['tipo'] === 'receita' ? 'text-green-600' : 'text-red-600'; ?>">
                                                    <?php echo $transacao['tipo'] === 'receita' ? '+' : '-'; ?> R$ <?php echo number_format($transacao['valor'], 2, ',', '.'); ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Distribuição por Categorias -->
                        <div class="bg-white rounded-xl shadow-sm overflow-hidden card fade-in" style="animation-delay: 0.7s">
                            <div class="px-6 py-4 border-b border-gray-200">
                                <h3 class="text-lg font-semibold text-gray-800">Distribuição por Categorias</h3>
                            </div>
                            <div class="p-6">
                                <div class="mb-4">
                                    <h4 class="text-sm font-medium text-gray-500 mb-2">Receitas</h4>
                                    <?php if (empty($receitas_por_categoria)): ?>
                                    <p class="text-sm text-gray-500">Nenhuma receita registrada neste mês.</p>
                                    <?php else: ?>
                                    <div class="space-y-2">
                                        <?php foreach ($receitas_por_categoria as $categoria): ?>
                                        <div>
                                            <div class="flex justify-between items-center mb-1">
                                                <span class="text-xs font-medium text-gray-600">
                                                    <?php echo htmlspecialchars($categoria['nome']); ?>
                                                </span>
                                                <span class="text-xs font-medium text-gray-900">
                                                    R$ <?php echo number_format($categoria['total'], 2, ',', '.'); ?>
                                                </span>
                                            </div>
                                            <div class="w-full bg-gray-200 rounded-full h-1.5">
                                                <div class="bg-green-600 h-1.5 rounded-full" style="width: <?php echo min(100, ($categoria['total'] / max(1, $total_receitas_mes)) * 100); ?>%"></div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <div>
                                    <h4 class="text-sm font-medium text-gray-500 mb-2">Despesas</h4>
                                    <?php if (empty($despesas_por_categoria)): ?>
                                    <p class="text-sm text-gray-500">Nenhuma despesa registrada neste mês.</p>
                                    <?php else: ?>
                                    <div class="space-y-2">
                                        <?php foreach ($despesas_por_categoria as $categoria): ?>
                                        <div>
                                            <div class="flex justify-between items-center mb-1">
                                                <span class="text-xs font-medium text-gray-600">
                                                    <?php echo htmlspecialchars($categoria['nome']); ?>
                                                </span>
                                                <span class="text-xs font-medium text-gray-900">
                                                    R$ <?php echo number_format($categoria['total'], 2, ',', '.'); ?>
                                                </span>
                                            </div>
                                            <div class="w-full bg-gray-200 rounded-full h-1.5">
                                                <div class="bg-red-600 h-1.5 rounded-full" style="width: <?php echo min(100, ($categoria['total'] / max(1, $total_despesas_mes)) * 100); ?>%"></div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
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

        // Toggle ações dropdown
        document.getElementById('acoes-button').addEventListener('click', function() {
            const menu = document.getElementById('acoes-menu');
            menu.classList.toggle('hidden');
        });

        // Close ações dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const menu = document.getElementById('acoes-menu');
            const button = document.getElementById('acoes-button');

            if (!menu.contains(event.target) && !button.contains(event.target)) {
                menu.classList.add('hidden');
            }
        });
    </script>
</body>
</html>
