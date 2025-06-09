<div id="sidebar" class="sidebar sidebar-expanded bg-blue-800 text-white flex flex-col">
    <!-- Logo -->
    <div class="p-4 flex items-center justify-between">
        <div class="flex items-center sidebar-logo-full">
            <div class="w-10 h-10 rounded-md bg-blue-600 flex items-center justify-center text-white font-bold text-xl">F</div>
            <span class="ml-3 text-xl font-bold">Faciência ERP</span>
        </div>
        <button id="toggle-sidebar" class="text-white focus:outline-none">
            <i class="fas fa-chevron-left"></i>
        </button>
    </div>

    <!-- Menu -->
    <div class="flex-1 overflow-y-auto py-4">
        <nav class="px-2 space-y-1">
            <!-- Dashboard -->
            <a href="index.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?> flex items-center py-3 px-4 text-white">
                <i class="fas fa-tachometer-alt w-6"></i>
                <span class="sidebar-label ml-3">Dashboard</span>
            </a>

            <!-- Ambiente Virtual de Aprendizagem -->
            <div class="mt-4 px-4">
                <h3 class="text-xs font-semibold text-gray-300 uppercase tracking-wider sidebar-label">Ambiente Virtual</h3>
            </div>
            <a href="ava_dashboard.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'ava_dashboard.php' ? 'active' : ''; ?> flex items-center py-3 px-4 text-white">
                <i class="fas fa-home w-6"></i>
                <span class="sidebar-label ml-3">Início AVA</span>
            </a>
            <a href="ava_cursos.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'ava_cursos.php' || basename($_SERVER['PHP_SELF']) === 'ava_cursos_novo.php' || basename($_SERVER['PHP_SELF']) === 'ava_curso_editar.php' || basename($_SERVER['PHP_SELF']) === 'ava_curso_conteudo.php' ? 'active' : ''; ?> flex items-center py-3 px-4 text-white">
                <i class="fas fa-book w-6"></i>
                <span class="sidebar-label ml-3">Meus Cursos</span>
            </a>
            <a href="ava_cursos_novo.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'ava_cursos_novo.php' ? 'active' : ''; ?> flex items-center py-3 px-4 text-white">
                <i class="fas fa-plus-circle w-6"></i>
                <span class="sidebar-label ml-3">Novo Curso</span>
            </a>
            <a href="ava_alunos.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'ava_alunos.php' || basename($_SERVER['PHP_SELF']) === 'ava_curso_alunos.php' ? 'active' : ''; ?> flex items-center py-3 px-4 text-white">
                <i class="fas fa-users w-6"></i>
                <span class="sidebar-label ml-3">Alunos do AVA</span>
            </a>
            <a href="ava_relatorios.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'ava_relatorios.php' ? 'active' : ''; ?> flex items-center py-3 px-4 text-white">
                <i class="fas fa-chart-bar w-6"></i>
                <span class="sidebar-label ml-3">Relatórios AVA</span>
            </a>

            <!-- Acadêmico -->
            <div class="mt-4 px-4">
                <h3 class="text-xs font-semibold text-gray-300 uppercase tracking-wider sidebar-label">Acadêmico</h3>
            </div>
            <a href="alunos.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'alunos.php' ? 'active' : ''; ?> flex items-center py-3 px-4 text-white">
                <i class="fas fa-user-graduate w-6"></i>
                <span class="sidebar-label ml-3">Alunos</span>
            </a>
            <a href="matriculas.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'matriculas.php' ? 'active' : ''; ?> flex items-center py-3 px-4 text-white">
                <i class="fas fa-file-alt w-6"></i>
                <span class="sidebar-label ml-3">Matrículas</span>
            </a>
            <a href="notas.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'notas.php' ? 'active' : ''; ?> flex items-center py-3 px-4 text-white">
                <i class="fas fa-clipboard-list w-6"></i>
                <span class="sidebar-label ml-3">Notas e Frequências</span>
            </a>
            <a href="documentos.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'documentos.php' ? 'active' : ''; ?> flex items-center py-3 px-4 text-white">
                <i class="fas fa-file-pdf w-6"></i>
                <span class="sidebar-label ml-3">Documentos</span>
            </a>

            <!-- Financeiro -->
            <div class="mt-4 px-4">
                <h3 class="text-xs font-semibold text-gray-300 uppercase tracking-wider sidebar-label">Financeiro</h3>
            </div>
            <a href="financeiro.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'financeiro.php' ? 'active' : ''; ?> flex items-center py-3 px-4 text-white">
                <i class="fas fa-money-bill-wave w-6"></i>
                <span class="sidebar-label ml-3">Financeiro</span>
            </a>

            <!-- Chamados -->
            <div class="mt-4 px-4">
                <h3 class="text-xs font-semibold text-gray-300 uppercase tracking-wider sidebar-label">Suporte</h3>
            </div>
            <a href="chamados.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'chamados.php' ? 'active' : ''; ?> flex items-center py-3 px-4 text-white">
                <i class="fas fa-ticket-alt w-6"></i>
                <span class="sidebar-label ml-3">Meus Chamados</span>
            </a>
            <a href="chamados_novo.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'chamados_novo.php' ? 'active' : ''; ?> flex items-center py-3 px-4 text-white">
                <i class="fas fa-plus-circle w-6"></i>
                <span class="sidebar-label ml-3">Novo Chamado</span>
            </a>
            <a href="ava_ajuda.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'ava_ajuda.php' ? 'active' : ''; ?> flex items-center py-3 px-4 text-white">
                <i class="fas fa-question-circle w-6"></i>
                <span class="sidebar-label ml-3">Ajuda e Tutoriais</span>
            </a>
        </nav>
    </div>
</div>
