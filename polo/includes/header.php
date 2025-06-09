<?php
/**
 * Cabeçalho do módulo de Polo
 */
?>
<header class="bg-white shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
        <div class="flex items-center">
            <button id="toggle-sidebar" class="mr-4 text-gray-500 focus:outline-none lg:hidden">
                <i class="fas fa-bars"></i>
            </button>
            <h1 class="text-xl font-semibold text-gray-800">
                <?php if (isset($titulo_pagina)): ?>
                    <?php echo $titulo_pagina; ?>
                <?php else: ?>
                    <i class="fas fa-university text-blue-600 mr-2"></i>
                    Polo: <strong><?php echo getUsuarioPoloNome(); ?></strong>
                <?php endif; ?>
            </h1>
        </div>
        <div class="flex items-center space-x-4">
            <!-- Links rápidos -->
            <div class="hidden md:flex items-center space-x-3">
                <a href="../ava/cursos_novo.php" class="text-sm text-blue-600 hover:text-blue-800">
                    <i class="fas fa-plus-circle mr-1"></i> Novo Curso
                </a>
                <a href="chamados_novo.php" class="text-sm text-blue-600 hover:text-blue-800">
                    <i class="fas fa-ticket-alt mr-1"></i> Abrir Chamado
                </a>
            </div>

            <!-- Menu do usuário -->
            <div class="relative" id="user-menu-container">
                <button id="user-menu-button" class="flex items-center text-gray-700 focus:outline-none">
                    <div class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center text-white">
                        <?php echo substr(getUsuarioNome(), 0, 1); ?>
                    </div>
                    <span class="ml-2 hidden md:block"><?php echo getUsuarioNome(); ?></span>
                    <i class="fas fa-chevron-down ml-2 text-xs"></i>
                </button>
                <div id="user-menu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10">
                    <a href="../perfil.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        <i class="fas fa-user mr-2"></i> Meu Perfil
                    </a>
                    <a href="../ava/dashboard.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        <i class="fas fa-laptop mr-2"></i> Ambiente Virtual
                    </a>
                    <a href="../alterar_senha.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        <i class="fas fa-key mr-2"></i> Alterar Senha
                    </a>
                    <div class="border-t border-gray-100 my-1"></div>
                    <a href="../logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                        <i class="fas fa-sign-out-alt mr-2"></i> Sair
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>
