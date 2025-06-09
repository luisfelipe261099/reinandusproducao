<?php
// Determina o caminho base para os links
$base_path = '../';

// Obtém o nome do arquivo atual
$current_file = basename($_SERVER['PHP_SELF']);

// Determina a página atual para destacar no menu
$pagina_atual = str_replace('.php', '', $current_file);
?>

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
            <a href="<?php echo $base_path; ?>secretaria_dashboard.php" class="nav-item <?php echo $pagina_atual === 'secretaria_dashboard' ? 'active' : ''; ?> flex items-center py-3 px-4 text-white">
                <i class="fas fa-tachometer-alt w-6"></i>
                <span class="sidebar-label ml-3">Dashboard</span>
            </a>

            <!-- Acadêmico -->
            <div class="mt-4 px-4">
                <h3 class="text-xs font-semibold text-gray-300 uppercase tracking-wider sidebar-label">Acadêmico</h3>
            </div>
            <a href="<?php echo $base_path; ?>alunos.php" class="nav-item <?php echo $pagina_atual === 'alunos' ? 'active' : ''; ?> flex items-center py-3 px-4 text-white">
                <i class="fas fa-user-graduate w-6"></i>
                <span class="sidebar-label ml-3">Alunos</span>
            </a>
            <a href="<?php echo $base_path; ?>matriculas.php" class="nav-item <?php echo $pagina_atual === 'matriculas' ? 'active' : ''; ?> flex items-center py-3 px-4 text-white">
                <i class="fas fa-file-alt w-6"></i>
                <span class="sidebar-label ml-3">Matrículas</span>
            </a>
            <a href="<?php echo $base_path; ?>notas.php" class="nav-item <?php echo $pagina_atual === 'notas' ? 'active' : ''; ?> flex items-center py-3 px-4 text-white">
                <i class="fas fa-clipboard-list w-6"></i>
                <span class="sidebar-label ml-3">Notas e Frequências</span>
            </a>
            <a href="<?php echo $base_path; ?>declaracoes.php" class="nav-item <?php echo $pagina_atual === 'declaracoes' ? 'active' : ''; ?> flex items-center py-3 px-4 text-white">
                <i class="fas fa-file-alt w-6"></i>
                <span class="sidebar-label ml-3">Declarações</span>
            </a>
            <a href="<?php echo $base_path; ?>historicos.php" class="nav-item <?php echo $pagina_atual === 'historicos' ? 'active' : ''; ?> flex items-center py-3 px-4 text-white">
                <i class="fas fa-graduation-cap w-6"></i>
                <span class="sidebar-label ml-3">Históricos</span>
            </a>

            <!-- Estrutura -->
            <div class="mt-4 px-4">
                <h3 class="text-xs font-semibold text-gray-300 uppercase tracking-wider sidebar-label">Estrutura</h3>
            </div>
            <a href="<?php echo $base_path; ?>polos.php" class="nav-item <?php echo $pagina_atual === 'polos' ? 'active' : ''; ?> flex items-center py-3 px-4 text-white">
                <i class="fas fa-university w-6"></i>
                <span class="sidebar-label ml-3">Polos</span>
            </a>
            <a href="<?php echo $base_path; ?>cursos.php" class="nav-item <?php echo $pagina_atual === 'cursos' ? 'active' : ''; ?> flex items-center py-3 px-4 text-white">
                <i class="fas fa-graduation-cap w-6"></i>
                <span class="sidebar-label ml-3">Cursos</span>
            </a>
            <a href="<?php echo $base_path; ?>turmas.php" class="nav-item <?php echo $pagina_atual === 'turmas' ? 'active' : ''; ?> flex items-center py-3 px-4 text-white">
                <i class="fas fa-users w-6"></i>
                <span class="sidebar-label ml-3">Turmas</span>
            </a>
            <a href="<?php echo $base_path; ?>disciplinas.php" class="nav-item <?php echo $pagina_atual === 'disciplinas' ? 'active' : ''; ?> flex items-center py-3 px-4 text-white">
                <i class="fas fa-book w-6"></i>
                <span class="sidebar-label ml-3">Disciplinas</span>
            </a>

            <!-- Financeiro -->
            <div class="mt-4 px-4">
                <h3 class="text-xs font-semibold text-gray-300 uppercase tracking-wider sidebar-label">Financeiro</h3>
            </div>
            <a href="<?php echo $base_path; ?>financeiro/index.php" class="nav-item flex items-center py-3 px-4 text-white">
                <i class="fas fa-money-bill-wave w-6"></i>
                <span class="sidebar-label ml-3">Módulo Financeiro</span>
            </a>

            <!-- Chamados -->
            <div class="mt-4 px-4">
                <h3 class="text-xs font-semibold text-gray-300 uppercase tracking-wider sidebar-label">Chamados</h3>
            </div>
            <a href="index.php" class="nav-item <?php echo in_array($pagina_atual, ['index', 'novo', 'visualizar']) ? 'active' : ''; ?> flex items-center py-3 px-4 text-white">
                <i class="fas fa-ticket-alt w-6"></i>
                <span class="sidebar-label ml-3">Gerenciar Chamados</span>
            </a>

            <!-- Relatórios -->
            <div class="mt-4 px-4">
                <h3 class="text-xs font-semibold text-gray-300 uppercase tracking-wider sidebar-label">Relatórios</h3>
            </div>
            <a href="<?php echo $base_path; ?>relatorios.php?tipo=desempenho" class="nav-item flex items-center py-3 px-4 text-white">
                <i class="fas fa-chart-line w-6"></i>
                <span class="sidebar-label ml-3">Desempenho</span>
            </a>
            <a href="<?php echo $base_path; ?>relatorios.php?tipo=estatisticas" class="nav-item flex items-center py-3 px-4 text-white">
                <i class="fas fa-chart-pie w-6"></i>
                <span class="sidebar-label ml-3">Estatísticas</span>
            </a>
            <a href="<?php echo $base_path; ?>relatorios.php?tipo=documentos" class="nav-item flex items-center py-3 px-4 text-white">
                <i class="fas fa-file-alt w-6"></i>
                <span class="sidebar-label ml-3">Documentos</span>
            </a>
            <a href="<?php echo $base_path; ?>relatorios.php?tipo=chamados" class="nav-item flex items-center py-3 px-4 text-white">
                <i class="fas fa-ticket-alt w-6"></i>
                <span class="sidebar-label ml-3">Chamados</span>
            </a>
        </nav>
    </div>
</div>
