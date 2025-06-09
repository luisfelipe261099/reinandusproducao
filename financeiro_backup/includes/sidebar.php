<div id="sidebar" class="sidebar-expanded bg-gradient-to-b from-purple-800 to-purple-900 text-white w-64 min-h-screen overflow-y-auto transition-all duration-300 ease-in-out">
    <div class="flex flex-col h-full">
        <!-- Logo -->
        <div class="flex items-center justify-center h-16 border-b border-purple-700">
            <a href="../index.php" class="text-xl font-bold text-white">
                <span class="text-white">Faciência</span> <span class="text-blue-300">ERP</span>
            </a>
            <button id="toggle-sidebar" class="ml-auto mr-4 text-white focus:outline-none">
                <i class="fas fa-chevron-left"></i>
            </button>
        </div>

        <!-- Menu -->
        <nav class="flex-1 py-4">
            <!-- Dashboard -->
            <a href="index.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?> flex items-center py-3 px-4 text-white">
                <i class="fas fa-tachometer-alt w-6"></i>
                <span class="sidebar-label ml-3">Dashboard</span>
            </a>

            <!-- Financeiro -->
            <div class="mt-4 px-4">
                <h3 class="text-xs font-semibold text-gray-300 uppercase tracking-wider sidebar-label">Financeiro</h3>
            </div>
            <a href="index.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?> flex items-center py-3 px-4 text-white">
                <i class="fas fa-chart-bar w-6"></i>
                <span class="sidebar-label ml-3">Dashboard</span>
            </a>
            <a href="transacoes.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'transacoes.php' ? 'active' : ''; ?> flex items-center py-3 px-4 text-white">
                <i class="fas fa-exchange-alt w-6"></i>
                <span class="sidebar-label ml-3">Transações</span>
            </a>
            <a href="mensalidades.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'mensalidades.php' ? 'active' : ''; ?> flex items-center py-3 px-4 text-white">
                <i class="fas fa-money-check-alt w-6"></i>
                <span class="sidebar-label ml-3">Mensalidades</span>
            </a>
            <a href="contas_receber.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'contas_receber.php' ? 'active' : ''; ?> flex items-center py-3 px-4 text-white">
                <i class="fas fa-file-invoice-dollar w-6"></i>
                <span class="sidebar-label ml-3">Contas a Receber</span>
            </a>
            <a href="contas_pagar.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'contas_pagar.php' ? 'active' : ''; ?> flex items-center py-3 px-4 text-white">
                <i class="fas fa-file-invoice w-6"></i>
                <span class="sidebar-label ml-3">Contas a Pagar</span>
            </a>
            <a href="contas_bancarias.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'contas_bancarias.php' ? 'active' : ''; ?> flex items-center py-3 px-4 text-white">
                <i class="fas fa-university w-6"></i>
                <span class="sidebar-label ml-3">Contas Bancárias</span>
            </a>
            <a href="polos_financeiro.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'polos_financeiro.php' ? 'active' : ''; ?> flex items-center py-3 px-4 text-white">
                <i class="fas fa-university w-6"></i>
                <span class="sidebar-label ml-3">Polos Financeiro</span>
            </a>
            <a href="gerar_boleto.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'gerar_boleto.php' ? 'active' : ''; ?> flex items-center py-3 px-4 text-white">
                <i class="fas fa-barcode w-6"></i>
                <span class="sidebar-label ml-3">Gerar Boletos</span>
            </a>

            <!-- Recursos Humanos -->
            <a href="funcionarios.php" class="nav-item <?php echo in_array(basename($_SERVER['PHP_SELF']), ['funcionarios.php', 'funcionario_form.php', 'funcionario_delete.php']) ? 'active' : ''; ?> flex items-center py-3 px-4 text-white">
                <i class="fas fa-users w-6"></i>
                <span class="sidebar-label ml-3">Funcionários</span>
            </a>
            <a href="pagamentos.php" class="nav-item <?php echo in_array(basename($_SERVER['PHP_SELF']), ['pagamentos.php', 'pagamento_form.php', 'pagamento_status.php']) ? 'active' : ''; ?> flex items-center py-3 px-4 text-white">
                <i class="fas fa-money-bill-wave w-6"></i>
                <span class="sidebar-label ml-3">Pagamentos</span>
            </a>
            <a href="agendamentos.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'agendamentos.php' ? 'active' : ''; ?> flex items-center py-3 px-4 text-white">
                <i class="fas fa-calendar-alt w-6"></i>
                <span class="sidebar-label ml-3">Agendamentos</span>
            </a>


            <!-- Voltar -->
            <div class="mt-4 px-4">
                <h3 class="text-xs font-semibold text-gray-300 uppercase tracking-wider sidebar-label">Navegação</h3>
            </div>
            <a href="../index.php" class="nav-item flex items-center py-3 px-4 text-white">
                <i class="fas fa-arrow-left w-6"></i>
                <span class="sidebar-label ml-3">Voltar para Secretaria</span>
            </a>
        </nav>
    </div>
</div>
