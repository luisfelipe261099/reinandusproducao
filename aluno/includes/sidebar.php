<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <img src="../assets/img/logo.png" alt="Logo" class="h-8">
            <span class="sidebar-label ml-2">Faciência ERP</span>
        </div>
        <button id="sidebar-toggle" class="sidebar-toggle">
            <i class="fas fa-chevron-left"></i>
        </button>
    </div>

    <div class="sidebar-menu">
        <a href="index.php" class="sidebar-item <?php echo $pagina_atual === 'index.php' ? 'active' : ''; ?>">
            <div class="sidebar-icon">
                <i class="fas fa-home"></i>
            </div>
            <span class="sidebar-label">Dashboard</span>
        </a>

        <a href="perfil.php" class="sidebar-item <?php echo $pagina_atual === 'perfil.php' ? 'active' : ''; ?>">
            <div class="sidebar-icon">
                <i class="fas fa-user"></i>
            </div>
            <span class="sidebar-label">Meu Perfil</span>
        </a>

        <a href="cursos.php" class="sidebar-item <?php echo $pagina_atual === 'cursos.php' ? 'active' : ''; ?>">
            <div class="sidebar-icon">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <span class="sidebar-label">Meus Cursos</span>
        </a>

        <a href="notas.php" class="sidebar-item <?php echo $pagina_atual === 'notas.php' ? 'active' : ''; ?>">
            <div class="sidebar-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <span class="sidebar-label">Minhas Notas</span>
        </a>

        <a href="documentos.php" class="sidebar-item <?php echo $pagina_atual === 'documentos.php' ? 'active' : ''; ?>">
            <div class="sidebar-icon">
                <i class="fas fa-file-alt"></i>
            </div>
            <span class="sidebar-label">Documentos</span>
        </a>

        <?php if ($aluno['acesso_ava']): ?>
        <a href="ava.php" class="sidebar-item <?php echo $pagina_atual === 'ava.php' ? 'active' : ''; ?>">
            <div class="sidebar-icon">
                <i class="fas fa-laptop"></i>
            </div>
            <span class="sidebar-label">Ambiente AVA</span>
        </a>
        <?php endif; ?>

        <a href="financeiro.php" class="sidebar-item <?php echo $pagina_atual === 'financeiro.php' ? 'active' : ''; ?>">
            <div class="sidebar-icon">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <span class="sidebar-label">Financeiro</span>
        </a>

        <a href="calendario.php" class="sidebar-item <?php echo $pagina_atual === 'calendario.php' ? 'active' : ''; ?>">
            <div class="sidebar-icon">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <span class="sidebar-label">Calendário</span>
        </a>

        <a href="mensagens.php" class="sidebar-item <?php echo $pagina_atual === 'mensagens.php' ? 'active' : ''; ?>">
            <div class="sidebar-icon">
                <i class="fas fa-envelope"></i>
            </div>
            <span class="sidebar-label">Mensagens</span>
        </a>

        <a href="suporte.php" class="sidebar-item <?php echo $pagina_atual === 'suporte.php' ? 'active' : ''; ?>">
            <div class="sidebar-icon">
                <i class="fas fa-headset"></i>
            </div>
            <span class="sidebar-label">Suporte</span>
        </a>
    </div>

    <div class="mt-auto p-4 border-t border-gray-700">
        <div class="flex items-center">
            <img src="<?php echo !empty($aluno['foto_perfil']) ? $aluno['foto_perfil'] : '../assets/img/avatar-placeholder.png'; ?>" alt="Avatar" class="w-10 h-10 rounded-full">
            <div class="ml-3 sidebar-label">
                <div class="font-medium text-sm"><?php echo $aluno['nome']; ?></div>
                <div class="text-xs text-gray-400">Aluno</div>
            </div>
        </div>
        <a href="../logout.php?redirect=aluno" class="sidebar-item mt-4">
            <div class="sidebar-icon">
                <i class="fas fa-sign-out-alt"></i>
            </div>
            <span class="sidebar-label">Sair</span>
        </a>
    </div>
</aside>
