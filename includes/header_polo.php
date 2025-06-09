<header class="bg-white shadow-sm h-16 flex items-center justify-between px-6">
    <div class="flex items-center">
        <span class="text-gray-800 font-medium">
            <i class="fas fa-university text-blue-600 mr-2"></i>
            Polo: <strong><?php echo getUsuarioPoloNome(); ?></strong>
        </span>
    </div>
    <div class="flex items-center space-x-4">
        <!-- Links rápidos -->
        <div class="hidden md:flex items-center space-x-3">
            <a href="ava_cursos_novo.php" class="text-sm text-blue-600 hover:text-blue-800">
                <i class="fas fa-plus-circle mr-1"></i> Novo Curso
            </a>
            <a href="chamados_novo.php" class="text-sm text-blue-600 hover:text-blue-800">
                <i class="fas fa-ticket-alt mr-1"></i> Abrir Chamado
            </a>
        </div>

        <!-- Separador -->
        <div class="hidden md:block h-6 w-px bg-gray-300"></div>

        <!-- Menu do usuário -->
        <div class="relative">
            <button id="user-menu-button" class="flex items-center focus:outline-none">
                <span class="mr-2 text-gray-700"><?php echo getUsuarioNome(); ?></span>
                <div class="w-8 h-8 rounded-full bg-blue-600 flex items-center justify-center text-white">
                    <?php echo strtoupper(substr(getUsuarioNome(), 0, 1)); ?>
                </div>
            </button>
            <div id="user-menu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10">
                <a href="perfil.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-user mr-2"></i> Meu Perfil
                </a>
                <a href="ava_dashboard.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-laptop mr-2"></i> Ambiente Virtual
                </a>
                <a href="alterar_senha.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-key mr-2"></i> Alterar Senha
                </a>
                <div class="border-t border-gray-100 my-1"></div>
                <a href="../logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                    <i class="fas fa-sign-out-alt mr-2"></i> Sair
                </a>
            </div>
        </div>
    </div>
</header>
