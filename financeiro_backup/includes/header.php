<header class="bg-white shadow-sm h-16 flex items-center px-6">
    <button id="toggle-sidebar" class="text-gray-500 focus:outline-none mr-6">
        <i class="fas fa-bars"></i>
    </button>
    
    <h1 class="text-xl font-semibold text-gray-800">Módulo Financeiro</h1>
    
    <div class="ml-auto flex items-center">
        <!-- Notificações -->
        <div class="relative mr-4">
            <button class="text-gray-500 hover:text-gray-700 focus:outline-none">
                <i class="fas fa-bell"></i>
                <?php if (isset($total_notificacoes) && $total_notificacoes > 0): ?>
                <span class="absolute top-0 right-0 -mt-1 -mr-1 bg-red-500 text-white text-xs rounded-full h-4 w-4 flex items-center justify-center"><?php echo $total_notificacoes; ?></span>
                <?php endif; ?>
            </button>
        </div>
        
        <!-- Menu do usuário -->
        <div class="relative">
            <button id="user-menu-button" class="flex items-center focus:outline-none">
                <div class="w-8 h-8 rounded-full bg-gray-300 flex items-center justify-center text-gray-700 font-semibold mr-2">
                    <?php echo isset($_SESSION['usuario']['nome']) ? substr($_SESSION['usuario']['nome'], 0, 1) : 'U'; ?>
                </div>
                <span class="text-gray-700 mr-1"><?php echo isset($_SESSION['usuario']['nome']) ? $_SESSION['usuario']['nome'] : 'Usuário'; ?></span>
                <i class="fas fa-chevron-down text-gray-500 text-xs"></i>
            </button>
            
            <div id="user-menu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10">
                <a href="../perfil.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Meu Perfil</a>
                <a href="../configuracoes.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Configurações</a>
                <div class="border-t border-gray-100"></div>
                <a href="../logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Sair</a>
            </div>
        </div>
    </div>
</header>
