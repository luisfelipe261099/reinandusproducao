<!-- Sidebar Moderno - Módulo Secretaria -->
<div id="sidebar" class="sidebar sidebar-expanded bg-blue-800 text-white flex flex-col w-64 min-h-screen fixed left-0 top-0 transition-all duration-300 z-10">
    <!-- Logo -->
    <div class="p-4 flex items-center justify-between bg-blue-900 border-b border-blue-700">
        <div class="flex items-center sidebar-logo-full">
            <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center shadow-lg">
                <i class="fas fa-graduation-cap text-white text-xl"></i>
            </div>
            <div class="ml-3">
                <h1 class="text-white font-bold text-lg">Secretaria</h1>
                <p class="text-blue-200 text-xs">Faciência ERP</p>
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
                <a href="secretaria_dashboard.php" class="sidebar-item flex items-center py-3 px-4 text-white hover:bg-blue-700 rounded-md <?php echo basename($_SERVER['PHP_SELF']) == 'secretaria_dashboard.php' ? 'active bg-blue-600' : ''; ?>">
                    <i class="fas fa-tachometer-alt w-6"></i>
                    <span class="sidebar-label">Dashboard</span>
                </a>
            </div>

            <!-- Gestão Acadêmica -->
            <div class="mb-4">
                <p class="text-xs text-blue-200 uppercase font-semibold mb-2">Gestão Acadêmica</p>
                <a href="alunos.php" class="sidebar-item flex items-center py-3 px-4 text-white hover:bg-blue-700 rounded-md <?php echo basename($_SERVER['PHP_SELF']) == 'alunos.php' ? 'active bg-blue-600' : ''; ?>">
                    <i class="fas fa-user-graduate w-6"></i>
                    <span class="sidebar-label">Alunos</span>
                </a>
                <a href="matriculas.php" class="sidebar-item flex items-center py-3 px-4 text-white hover:bg-blue-700 rounded-md <?php echo basename($_SERVER['PHP_SELF']) == 'matriculas.php' ? 'active bg-blue-600' : ''; ?>">
                    <i class="fas fa-file-signature w-6"></i>
                    <span class="sidebar-label">Matrículas</span>
                </a>
                <a href="notas.php" class="sidebar-item flex items-center py-3 px-4 text-white hover:bg-blue-700 rounded-md <?php echo basename($_SERVER['PHP_SELF']) == 'notas.php' ? 'active bg-blue-600' : ''; ?>">
                    <i class="fas fa-clipboard-list w-6"></i>
                    <span class="sidebar-label">Notas e Frequências</span>
                </a>
                <a href="declaracoes.php" class="sidebar-item flex items-center py-3 px-4 text-white hover:bg-blue-700 rounded-md <?php echo basename($_SERVER['PHP_SELF']) == 'declaracoes.php' ? 'active bg-blue-600' : ''; ?>">
                    <i class="fas fa-file-alt w-6"></i>
                    <span class="sidebar-label">Declarações</span>
                </a>
                <a href="historicos.php" class="sidebar-item flex items-center py-3 px-4 text-white hover:bg-blue-700 rounded-md <?php echo basename($_SERVER['PHP_SELF']) == 'historicos.php' ? 'active bg-blue-600' : ''; ?>">
                    <i class="fas fa-graduation-cap w-6"></i>
                    <span class="sidebar-label">Históricos</span>
                </a>
            </div>

            <!-- Estrutura Institucional -->
            <div class="mb-4">
                <p class="text-xs text-blue-200 uppercase font-semibold mb-2">Estrutura Institucional</p>
                <a href="polos.php" class="sidebar-item flex items-center py-3 px-4 text-white hover:bg-blue-700 rounded-md <?php echo basename($_SERVER['PHP_SELF']) == 'polos.php' ? 'active bg-blue-600' : ''; ?>">
                    <i class="fas fa-university w-6"></i>
                    <span class="sidebar-label">Polos</span>
                </a>
                <a href="cursos.php" class="sidebar-item flex items-center py-3 px-4 text-white hover:bg-blue-700 rounded-md <?php echo basename($_SERVER['PHP_SELF']) == 'cursos.php' ? 'active bg-blue-600' : ''; ?>">
                    <i class="fas fa-graduation-cap w-6"></i>
                    <span class="sidebar-label">Cursos</span>
                </a>
                <a href="turmas.php" class="sidebar-item flex items-center py-3 px-4 text-white hover:bg-blue-700 rounded-md <?php echo basename($_SERVER['PHP_SELF']) == 'turmas.php' ? 'active bg-blue-600' : ''; ?>">
                    <i class="fas fa-users w-6"></i>
                    <span class="sidebar-label">Turmas</span>
                </a>
                <a href="disciplinas.php" class="sidebar-item flex items-center py-3 px-4 text-white hover:bg-blue-700 rounded-md <?php echo basename($_SERVER['PHP_SELF']) == 'disciplinas.php' ? 'active bg-blue-600' : ''; ?>">
                    <i class="fas fa-book w-6"></i>
                    <span class="sidebar-label">Disciplinas</span>
                </a>
            </div>

            <!-- Módulo Financeiro -->
            <div class="mb-4">
                <p class="text-xs text-blue-200 uppercase font-semibold mb-2">Financeiro</p>
                <a href="../financeiro/index.php" class="sidebar-item flex items-center py-3 px-4 text-white hover:bg-blue-700 rounded-md">
                    <i class="fas fa-dollar-sign w-6"></i>
                    <span class="sidebar-label">Módulo Financeiro</span>
                </a>
            </div>

            <!-- Ambiente Virtual de Aprendizagem -->
            <div class="mb-4">
                <p class="text-xs text-blue-200 uppercase font-semibold mb-2">Ambiente Virtual</p>
                <a href="ava_gerenciar_acesso.php" class="sidebar-item flex items-center py-3 px-4 text-white hover:bg-blue-700 rounded-md <?php echo basename($_SERVER['PHP_SELF']) == 'ava_gerenciar_acesso.php' ? 'active bg-blue-600' : ''; ?>">
                    <i class="fas fa-key w-6"></i>
                    <span class="sidebar-label">Gerenciar Acesso</span>
                </a>
                <a href="ava_cursos.php" class="sidebar-item flex items-center py-3 px-4 text-white hover:bg-blue-700 rounded-md <?php echo basename($_SERVER['PHP_SELF']) == 'ava_cursos.php' ? 'active bg-blue-600' : ''; ?>">
                    <i class="fas fa-laptop w-6"></i>
                    <span class="sidebar-label">Cursos do AVA</span>
                </a>
                <a href="ava_alunos.php" class="sidebar-item flex items-center py-3 px-4 text-white hover:bg-blue-700 rounded-md <?php echo basename($_SERVER['PHP_SELF']) == 'ava_alunos.php' ? 'active bg-blue-600' : ''; ?>">
                    <i class="fas fa-user-friends w-6"></i>
                    <span class="sidebar-label">Alunos do AVA</span>
                </a>
                <a href="ava_relatorios.php" class="sidebar-item flex items-center py-3 px-4 text-white hover:bg-blue-700 rounded-md <?php echo basename($_SERVER['PHP_SELF']) == 'ava_relatorios.php' ? 'active bg-blue-600' : ''; ?>">
                    <i class="fas fa-chart-bar w-6"></i>
                    <span class="sidebar-label">Relatórios do AVA</span>
                </a>
                <a href="ava_dashboard_aluno.php" class="sidebar-item flex items-center py-3 px-4 text-white hover:bg-blue-700 rounded-md <?php echo basename($_SERVER['PHP_SELF']) == 'ava_dashboard_aluno.php' ? 'active bg-blue-600' : ''; ?>">
                    <i class="fas fa-desktop w-6"></i>
                    <span class="sidebar-label">Ambiente do Aluno</span>
                </a>
            </div>

            <!-- Sistema de Chamados -->
            <div class="mb-4">
                <p class="text-xs text-blue-200 uppercase font-semibold mb-2">Suporte</p>
                <a href="chamados/index.php" class="sidebar-item flex items-center py-3 px-4 text-white hover:bg-blue-700 rounded-md <?php echo strpos($_SERVER['PHP_SELF'], 'chamados/') !== false ? 'active bg-blue-600' : ''; ?>">
                    <i class="fas fa-headset w-6"></i>
                    <span class="sidebar-label">Gerenciar Chamados</span>
                </a>
            </div>

            <!-- Relatórios e Análises -->
            <div class="mb-4">
                <p class="text-xs text-blue-200 uppercase font-semibold mb-2">Relatórios</p>
                <?php if (file_exists('relatorios.php')): ?>
                <a href="relatorios.php?tipo=desempenho" class="sidebar-item flex items-center py-3 px-4 text-white hover:bg-blue-700 rounded-md <?php echo basename($_SERVER['PHP_SELF']) === 'relatorios.php' && isset($_GET['tipo']) && $_GET['tipo'] === 'desempenho' ? 'active bg-blue-600' : ''; ?>">
                    <i class="fas fa-chart-line w-6"></i>
                    <span class="sidebar-label">Desempenho</span>
                </a>
                <a href="relatorios.php?tipo=estatisticas" class="sidebar-item flex items-center py-3 px-4 text-white hover:bg-blue-700 rounded-md <?php echo basename($_SERVER['PHP_SELF']) === 'relatorios.php' && isset($_GET['tipo']) && $_GET['tipo'] === 'estatisticas' ? 'active bg-blue-600' : ''; ?>">
                    <i class="fas fa-chart-pie w-6"></i>
                    <span class="sidebar-label">Estatísticas</span>
                </a>
                <a href="relatorios.php?tipo=documentos" class="sidebar-item flex items-center py-3 px-4 text-white hover:bg-blue-700 rounded-md <?php echo basename($_SERVER['PHP_SELF']) === 'relatorios.php' && isset($_GET['tipo']) && $_GET['tipo'] === 'documentos' ? 'active bg-blue-600' : ''; ?>">
                    <i class="fas fa-file-chart-line w-6"></i>
                    <span class="sidebar-label">Documentos</span>
                </a>
                <a href="relatorios.php?tipo=chamados" class="sidebar-item flex items-center py-3 px-4 text-white hover:bg-blue-700 rounded-md <?php echo basename($_SERVER['PHP_SELF']) === 'relatorios.php' && isset($_GET['tipo']) && $_GET['tipo'] === 'chamados' ? 'active bg-blue-600' : ''; ?>">
                    <i class="fas fa-ticket-alt w-6"></i>
                    <span class="sidebar-label">Chamados</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Overlay para mobile -->
<div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-5 hidden lg:hidden"></div>
