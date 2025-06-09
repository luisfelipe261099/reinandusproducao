<?php
/**
 * Módulo Financeiro - Página Principal
 * Sistema de Gestão Financeira da Faculdade
 */

// Inclui arquivos necessários
require_once '../includes/init.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

// Verifica autenticação
Auth::requireLogin();

// Verifica se o usuário tem permissão para acessar o financeiro
$userType = Auth::getUserType();
if (!in_array($userType, ['financeiro', 'admin_master'])) {
    $_SESSION['error'] = 'Você não tem permissão para acessar o módulo financeiro.';
    header('Location: ../index.php');
    exit;
}

// Conecta ao banco de dados
$db = Database::getInstance();

// Busca dados para o dashboard
try {
    // Total de funcionários ativos
    $totalFuncionarios = $db->fetchOne("SELECT COUNT(*) as total FROM funcionarios WHERE status = 'ativo'")['total'] ?? 0;
    
    // Total de contas a pagar pendentes
    $contasPagarPendentes = $db->fetchOne("SELECT COUNT(*) as total FROM contas_pagar WHERE status = 'pendente'")['total'] ?? 0;
    
    // Total de contas a receber pendentes
    $contasReceberPendentes = $db->fetchOne("SELECT COUNT(*) as total FROM contas_receber WHERE status = 'pendente'")['total'] ?? 0;
    
    // Valor total a pagar este mês
    $valorPagarMes = $db->fetchOne("
        SELECT COALESCE(SUM(valor), 0) as total 
        FROM contas_pagar 
        WHERE status = 'pendente' 
        AND MONTH(data_vencimento) = MONTH(CURRENT_DATE()) 
        AND YEAR(data_vencimento) = YEAR(CURRENT_DATE())
    ")['total'] ?? 0;
    
    // Valor total a receber este mês
    $valorReceberMes = $db->fetchOne("
        SELECT COALESCE(SUM(valor), 0) as total 
        FROM contas_receber 
        WHERE status = 'pendente' 
        AND MONTH(data_vencimento) = MONTH(CURRENT_DATE()) 
        AND YEAR(data_vencimento) = YEAR(CURRENT_DATE())
    ")['total'] ?? 0;
    
    // Saldo atual (simplificado)
    $saldoAtual = $valorReceberMes - $valorPagarMes;
    
} catch (Exception $e) {
    // Se houver erro (tabelas não existem), define valores padrão
    $totalFuncionarios = 0;
    $contasPagarPendentes = 0;
    $contasReceberPendentes = 0;
    $valorPagarMes = 0;
    $valorReceberMes = 0;
    $saldoAtual = 0;
    $tabelasNaoExistem = true;
}

$pageTitle = 'Dashboard Financeiro';
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
                    <!-- Page Header -->
                    <div class="mb-8">
                        <h1 class="text-3xl font-bold text-gray-900">Dashboard Financeiro</h1>
                        <p class="text-gray-600 mt-2">Visão geral das finanças da instituição</p>
                    </div>

                    <?php if (isset($tabelasNaoExistem)): ?>
                    <!-- Alerta de configuração -->
                    <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm">
                                    O módulo financeiro precisa ser configurado. As tabelas necessárias serão criadas automaticamente.
                                    <a href="setup.php" class="font-medium underline">Clique aqui para configurar</a>
                                </p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Cards de Resumo -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <!-- Total Funcionários -->
                        <div class="bg-white rounded-lg shadow p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                                        <i class="fas fa-users text-white text-sm"></i>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-600">Funcionários Ativos</p>
                                    <p class="text-2xl font-semibold text-gray-900"><?php echo number_format($totalFuncionarios); ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Contas a Pagar -->
                        <div class="bg-white rounded-lg shadow p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-red-500 rounded-md flex items-center justify-center">
                                        <i class="fas fa-file-invoice text-white text-sm"></i>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-600">Contas a Pagar</p>
                                    <p class="text-2xl font-semibold text-gray-900"><?php echo number_format($contasPagarPendentes); ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Contas a Receber -->
                        <div class="bg-white rounded-lg shadow p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                                        <i class="fas fa-file-invoice-dollar text-white text-sm"></i>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-600">Contas a Receber</p>
                                    <p class="text-2xl font-semibold text-gray-900"><?php echo number_format($contasReceberPendentes); ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Saldo do Mês -->
                        <div class="bg-white rounded-lg shadow p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 <?php echo $saldoAtual >= 0 ? 'bg-green-500' : 'bg-red-500'; ?> rounded-md flex items-center justify-center">
                                        <i class="fas fa-chart-line text-white text-sm"></i>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-600">Saldo do Mês</p>
                                    <p class="text-2xl font-semibold <?php echo $saldoAtual >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                        R$ <?php echo number_format($saldoAtual, 2, ',', '.'); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Ações Rápidas -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Gestão de Funcionários</h3>
                            <div class="space-y-3">
                                <a href="funcionarios.php" class="block w-full bg-blue-600 text-white text-center py-2 px-4 rounded-md hover:bg-blue-700 transition-colors">
                                    <i class="fas fa-users mr-2"></i>Gerenciar Funcionários
                                </a>
                                <a href="folha_pagamento.php" class="block w-full bg-blue-100 text-blue-700 text-center py-2 px-4 rounded-md hover:bg-blue-200 transition-colors">
                                    <i class="fas fa-money-check-alt mr-2"></i>Folha de Pagamento
                                </a>
                            </div>
                        </div>

                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Contas e Pagamentos</h3>
                            <div class="space-y-3">
                                <a href="contas_pagar.php" class="block w-full bg-red-600 text-white text-center py-2 px-4 rounded-md hover:bg-red-700 transition-colors">
                                    <i class="fas fa-file-invoice mr-2"></i>Contas a Pagar
                                </a>
                                <a href="contas_receber.php" class="block w-full bg-green-600 text-white text-center py-2 px-4 rounded-md hover:bg-green-700 transition-colors">
                                    <i class="fas fa-file-invoice-dollar mr-2"></i>Contas a Receber
                                </a>
                            </div>
                        </div>

                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Relatórios e Análises</h3>
                            <div class="space-y-3">
                                <a href="fluxo_caixa.php" class="block w-full bg-purple-600 text-white text-center py-2 px-4 rounded-md hover:bg-purple-700 transition-colors">
                                    <i class="fas fa-chart-line mr-2"></i>Fluxo de Caixa
                                </a>
                                <a href="relatorios.php" class="block w-full bg-purple-100 text-purple-700 text-center py-2 px-4 rounded-md hover:bg-purple-200 transition-colors">
                                    <i class="fas fa-chart-bar mr-2"></i>Relatórios
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Resumo Financeiro -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Valores do Mês -->
                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Resumo do Mês Atual</h3>
                            <div class="space-y-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Total a Receber:</span>
                                    <span class="text-green-600 font-semibold">R$ <?php echo number_format($valorReceberMes, 2, ',', '.'); ?></span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Total a Pagar:</span>
                                    <span class="text-red-600 font-semibold">R$ <?php echo number_format($valorPagarMes, 2, ',', '.'); ?></span>
                                </div>
                                <hr>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-900 font-semibold">Saldo Previsto:</span>
                                    <span class="<?php echo $saldoAtual >= 0 ? 'text-green-600' : 'text-red-600'; ?> font-bold">
                                        R$ <?php echo number_format($saldoAtual, 2, ',', '.'); ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Próximos Vencimentos -->
                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Próximos Vencimentos</h3>
                            <div class="text-center text-gray-500 py-8">
                                <i class="fas fa-calendar-alt text-4xl mb-4"></i>
                                <p>Carregue os dados para ver os próximos vencimentos</p>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Scripts -->
    <script src="js/financeiro.js"></script>
</body>
</html>
