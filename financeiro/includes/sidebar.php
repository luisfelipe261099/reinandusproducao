<!-- Sidebar Moderno - Módulo Financeiro -->
<div id="sidebar" class="sidebar sidebar-expanded bg-green-800 text-white flex flex-col w-64 min-h-screen fixed left-0 top-0 transition-all duration-300 z-40">
    <!-- Logo -->
    <div class="p-4 flex items-center justify-between bg-green-900 border-b border-green-700">
        <div class="flex items-center sidebar-logo-full">
            <div class="w-10 h-10 bg-green-600 rounded-lg flex items-center justify-center shadow-lg">
                <i class="fas fa-dollar-sign text-white text-xl"></i>
            </div>
            <div class="ml-3">
                <h1 class="text-white font-bold text-lg">Financeiro</h1>
                <p class="text-green-200 text-xs">Faciência ERP</p>
            </div>
        </div>
        <!-- Botão de toggle para mobile -->
        <button id="toggle-sidebar" class="text-white focus:outline-none lg:hidden">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <!-- Menu de Navegação -->
    <div class="flex-1 overflow-y-auto">
        <div class="px-3 py-4">
            <!-- Dashboard -->
            <div class="mb-6">
                <a href="index.php" class="sidebar-item flex items-center py-3 px-4 text-white hover:bg-green-700 rounded-md <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active bg-green-600' : ''; ?>">
                    <i class="fas fa-tachometer-alt w-6"></i>
                    <span class="sidebar-label">Dashboard</span>
                </a>
            </div>

            <!-- Recursos Humanos -->
            <div class="mb-4">
                <p class="text-xs text-green-200 uppercase font-semibold mb-2">Recursos Humanos</p>
                <a href="funcionarios.php" class="sidebar-item flex items-center py-3 px-4 text-white hover:bg-green-700 rounded-md <?php echo basename($_SERVER['PHP_SELF']) == 'funcionarios.php' ? 'active bg-green-600' : ''; ?>">
                    <i class="fas fa-users w-6"></i>
                    <span class="sidebar-label">Funcionários</span>
                </a>
                <a href="folha_pagamento.php" class="sidebar-item flex items-center py-3 px-4 text-white hover:bg-green-700 rounded-md <?php echo basename($_SERVER['PHP_SELF']) == 'folha_pagamento.php' ? 'active bg-green-600' : ''; ?>">
                    <i class="fas fa-money-check-alt w-6"></i>
                    <span class="sidebar-label">Folha de Pagamento</span>
                </a>
            </div>

            <!-- Contas e Pagamentos -->
            <div class="mb-4">
                <p class="text-xs text-green-200 uppercase font-semibold mb-2">Contas e Pagamentos</p>
                <a href="contas_pagar.php" class="sidebar-item flex items-center py-3 px-4 text-white hover:bg-green-700 rounded-md <?php echo basename($_SERVER['PHP_SELF']) == 'contas_pagar.php' ? 'active bg-green-600' : ''; ?>">
                    <i class="fas fa-file-invoice w-6"></i>
                    <span class="sidebar-label">Contas a Pagar</span>
                </a>
                <a href="contas_receber.php" class="sidebar-item flex items-center py-3 px-4 text-white hover:bg-green-700 rounded-md <?php echo basename($_SERVER['PHP_SELF']) == 'contas_receber.php' ? 'active bg-green-600' : ''; ?>">
                    <i class="fas fa-file-invoice-dollar w-6"></i>
                    <span class="sidebar-label">Contas a Receber</span>
                </a>
                <a href="mensalidades.php" class="sidebar-item flex items-center py-3 px-4 text-white hover:bg-green-700 rounded-md <?php echo basename($_SERVER['PHP_SELF']) == 'mensalidades.php' ? 'active bg-green-600' : ''; ?>">
                    <i class="fas fa-graduation-cap w-6"></i>
                    <span class="sidebar-label">Mensalidades</span>
                </a>
                <a href="cobranca_polos.php" class="sidebar-item flex items-center py-3 px-4 text-white hover:bg-green-700 rounded-md <?php echo basename($_SERVER['PHP_SELF']) == 'cobranca_polos.php' ? 'active bg-green-600' : ''; ?>">
                    <i class="fas fa-building w-6"></i>
                    <span class="sidebar-label">Cobrança de Polos</span>
                </a>
            </div>

            <!-- Gestão Financeira -->
            <div class="mb-4">
                <p class="text-xs text-green-200 uppercase font-semibold mb-2">Gestão Financeira</p>
                <a href="fluxo_caixa.php" class="sidebar-item flex items-center py-3 px-4 text-white hover:bg-green-700 rounded-md <?php echo basename($_SERVER['PHP_SELF']) == 'fluxo_caixa.php' ? 'active bg-green-600' : ''; ?>">
                    <i class="fas fa-chart-line w-6"></i>
                    <span class="sidebar-label">Fluxo de Caixa</span>
                </a>
                <a href="contas_bancarias.php" class="sidebar-item flex items-center py-3 px-4 text-white hover:bg-green-700 rounded-md <?php echo basename($_SERVER['PHP_SELF']) == 'contas_bancarias.php' ? 'active bg-green-600' : ''; ?>">
                    <i class="fas fa-university w-6"></i>
                    <span class="sidebar-label">Contas Bancárias</span>
                </a>
                <a href="categorias.php" class="sidebar-item flex items-center py-3 px-4 text-white hover:bg-green-700 rounded-md <?php echo basename($_SERVER['PHP_SELF']) == 'categorias.php' ? 'active bg-green-600' : ''; ?>">
                    <i class="fas fa-tags w-6"></i>
                    <span class="sidebar-label">Categorias</span>
                </a>
                <a href="boletos.php" class="sidebar-item flex items-center py-3 px-4 text-white hover:bg-green-700 rounded-md <?php echo basename($_SERVER['PHP_SELF']) == 'boletos.php' ? 'active bg-green-600' : ''; ?>">
                    <i class="fas fa-barcode w-6"></i>
                    <span class="sidebar-label">Boletos Bancários</span>
                </a>
            </div>

            <!-- Relatórios e Análises -->
            <div class="mb-4">
                <p class="text-xs text-green-200 uppercase font-semibold mb-2">Relatórios</p>
                <a href="relatorios.php" class="sidebar-item flex items-center py-3 px-4 text-white hover:bg-green-700 rounded-md <?php echo basename($_SERVER['PHP_SELF']) == 'relatorios.php' ? 'active bg-green-600' : ''; ?>">
                    <i class="fas fa-chart-bar w-6"></i>
                    <span class="sidebar-label">Relatórios</span>
                </a>
                <a href="demonstrativos.php" class="sidebar-item flex items-center py-3 px-4 text-white hover:bg-green-700 rounded-md <?php echo basename($_SERVER['PHP_SELF']) == 'demonstrativos.php' ? 'active bg-green-600' : ''; ?>">
                    <i class="fas fa-file-chart-line w-6"></i>
                    <span class="sidebar-label">Demonstrativos</span>
                </a>
            </div>

            <!-- Configurações -->
            <div class="mb-4">
                <p class="text-xs text-green-200 uppercase font-semibold mb-2">Sistema</p>
                <a href="configuracoes.php" class="sidebar-item flex items-center py-3 px-4 text-white hover:bg-green-700 rounded-md <?php echo basename($_SERVER['PHP_SELF']) == 'configuracoes.php' ? 'active bg-green-600' : ''; ?>">
                    <i class="fas fa-cog w-6"></i>
                    <span class="sidebar-label">Configurações</span>
                </a>
                <a href="../index.php" class="sidebar-item flex items-center py-3 px-4 text-green-200 hover:text-white hover:bg-green-700 rounded-md">
                    <i class="fas fa-arrow-left w-6"></i>
                    <span class="sidebar-label">Voltar ao Sistema</span>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Overlay para mobile -->
<div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden lg:hidden"></div>

<!-- CSS para efeitos do sidebar -->
<style>
/* Garantir que o sidebar seja visível em desktop */
#sidebar {
    display: flex !important;
    position: fixed !important;
    left: 0 !important;
    top: 0 !important;
    width: 256px !important;
    height: 100vh !important;
    z-index: 40 !important;
}

.sidebar-item {
    transition: all 0.3s ease;
    border-radius: 0.375rem;
    margin-bottom: 0.25rem;
}

.sidebar-item:hover {
    background-color: rgba(21, 128, 61, 0.7) !important;
    transform: translateX(4px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.sidebar-item.active {
    background-color: rgba(22, 163, 74, 1) !important;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    border-left: 4px solid #86efac;
}

.sidebar-item i {
    width: 1.5rem;
    text-align: center;
    margin-right: 0.75rem;
}

/* Efeito hover nos ícones */
.sidebar-item:hover i {
    transform: scale(1.1);
    color: #dcfce7;
}

/* Responsividade para mobile */
@media (max-width: 1024px) {
    #sidebar {
        transform: translateX(-100%) !important;
        transition: transform 0.3s ease !important;
    }

    #sidebar.sidebar-expanded {
        transform: translateX(0) !important;
    }
}
</style>
