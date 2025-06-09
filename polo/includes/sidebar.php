<?php
/**
 * Barra lateral do módulo de Polo
 */
?>
<link rel="stylesheet" href="../css/sidebar.css">

<aside id="sidebar" class="bg-gray-800 text-white sidebar-expanded">
    <!-- Cabeçalho fixo do sidebar -->
    <div id="sidebar-header" class="p-4 flex items-center justify-center border-b border-gray-700">
        <h2 class="text-xl font-bold">Faciência ERP</h2>
    </div>

    <!-- Conteúdo com rolagem do sidebar -->
    <div id="sidebar-content">
        <nav class="mt-5">
            <ul>
            <li class="mb-1">
                <a href="index.php" class="flex items-center py-3 px-4 text-gray-300 hover:bg-gray-700 hover:text-white <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'bg-gray-700 text-white' : ''; ?>">
                    <i class="fas fa-tachometer-alt w-5"></i>
                    <span class="sidebar-label ml-3">Dashboard</span>
                </a>
            </li>

            <!-- Ambiente Virtual de Aprendizagem -->
            <li class="mt-4 px-4">
                <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider sidebar-label">Ambiente Virtual</h3>
            </li>
            <li class="mb-1">
                <a href="../ava/dashboard.php" class="flex items-center py-3 px-4 text-gray-300 hover:bg-gray-700 hover:text-white <?php echo strpos($_SERVER['PHP_SELF'], 'ava/dashboard.php') !== false ? 'bg-gray-700 text-white' : ''; ?>">
                    <i class="fas fa-home w-5"></i>
                    <span class="sidebar-label ml-3">Início AVA</span>
                </a>
            </li>
            <li class="mb-1">
                <a href="../ava/cursos.php" class="flex items-center py-3 px-4 text-gray-300 hover:bg-gray-700 hover:text-white <?php echo strpos($_SERVER['PHP_SELF'], 'ava/cursos.php') !== false ? 'bg-gray-700 text-white' : ''; ?>">
                    <i class="fas fa-book w-5"></i>
                    <span class="sidebar-label ml-3">Meus Cursos</span>
                </a>
            </li>
            <li class="mb-1">
                <a href="../ava/cursos_novo.php" class="flex items-center py-3 px-4 text-gray-300 hover:bg-gray-700 hover:text-white <?php echo strpos($_SERVER['PHP_SELF'], 'ava/cursos_novo.php') !== false ? 'bg-gray-700 text-white' : ''; ?>">
                    <i class="fas fa-plus-circle w-5"></i>
                    <span class="sidebar-label ml-3">Novo Curso</span>
                </a>
            </li>
            <li class="mb-1">
                <a href="../ava/alunos.php" class="flex items-center py-3 px-4 text-gray-300 hover:bg-gray-700 hover:text-white <?php echo strpos($_SERVER['PHP_SELF'], 'ava/alunos.php') !== false ? 'bg-gray-700 text-white' : ''; ?>">
                    <i class="fas fa-users w-5"></i>
                    <span class="sidebar-label ml-3">Alunos do AVA</span>
                </a>
            </li>

            <!-- Acadêmico -->
            <li class="mt-4 px-4">
                <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider sidebar-label">Acadêmico</h3>
            </li>
            <li class="mb-1">
                <a href="alunos.php" class="flex items-center py-3 px-4 text-gray-300 hover:bg-gray-700 hover:text-white <?php echo basename($_SERVER['PHP_SELF']) == 'alunos.php' ? 'bg-gray-700 text-white' : ''; ?>">
                    <i class="fas fa-user-graduate w-5"></i>
                    <span class="sidebar-label ml-3">Alunos</span>
                </a>
            </li>
            <li class="mb-1">
                <a href="matriculas.php" class="flex items-center py-3 px-4 text-gray-300 hover:bg-gray-700 hover:text-white <?php echo basename($_SERVER['PHP_SELF']) == 'matriculas.php' ? 'bg-gray-700 text-white' : ''; ?>">
                    <i class="fas fa-id-card w-5"></i>
                    <span class="sidebar-label ml-3">Matrículas</span>
                </a>
            </li>
            <li class="mb-1">
                <a href="documentos.php" class="flex items-center py-3 px-4 text-gray-300 hover:bg-gray-700 hover:text-white <?php echo basename($_SERVER['PHP_SELF']) == 'documentos.php' ? 'bg-gray-700 text-white' : ''; ?>">
                    <i class="fas fa-file-alt w-5"></i>
                    <span class="sidebar-label ml-3">Documentos</span>
                </a>
            </li>
            <li class="mb-1">
                <a href="turmas.php" class="flex items-center py-3 px-4 text-gray-300 hover:bg-gray-700 hover:text-white <?php echo basename($_SERVER['PHP_SELF']) == 'turmas.php' ? 'bg-gray-700 text-white' : ''; ?>">
                    <i class="fas fa-users w-5"></i>
                    <span class="sidebar-label ml-3">Turmas</span>
                </a>
            </li>
            <li class="mb-1">
                <a href="cursos.php" class="flex items-center py-3 px-4 text-gray-300 hover:bg-gray-700 hover:text-white <?php echo basename($_SERVER['PHP_SELF']) == 'cursos.php' ? 'bg-gray-700 text-white' : ''; ?>">
                    <i class="fas fa-graduation-cap w-5"></i>
                    <span class="sidebar-label ml-3">Cursos</span>
                </a>
            </li>

            <!-- Financeiro -->
            <li class="mt-4 px-4">
                <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider sidebar-label">Financeiro</h3>
            </li>
            <li class="mb-1">
                <a href="financeiro.php" class="flex items-center py-3 px-4 text-gray-300 hover:bg-gray-700 hover:text-white <?php echo basename($_SERVER['PHP_SELF']) == 'financeiro.php' ? 'bg-gray-700 text-white' : ''; ?>">
                    <i class="fas fa-file-invoice-dollar w-5"></i>
                    <span class="sidebar-label ml-3">Financeiro do Polo</span>
                </a>
            </li>

            <!-- Suporte -->
            <li class="mt-4 px-4">
                <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider sidebar-label">Suporte</h3>
            </li>
            <li class="mb-1">
                <a href="chamados.php" class="flex items-center py-3 px-4 text-gray-300 hover:bg-gray-700 hover:text-white <?php echo basename($_SERVER['PHP_SELF']) == 'chamados.php' ? 'bg-gray-700 text-white' : ''; ?>">
                    <i class="fas fa-ticket-alt w-5"></i>
                    <span class="sidebar-label ml-3">Chamados</span>
                </a>
            </li>
            <li class="mb-1">
                <a href="../ava/ajuda.php" class="flex items-center py-3 px-4 text-gray-300 hover:bg-gray-700 hover:text-white <?php echo strpos($_SERVER['PHP_SELF'], 'ava/ajuda.php') !== false ? 'bg-gray-700 text-white' : ''; ?>">
                    <i class="fas fa-question-circle w-5"></i>
                    <span class="sidebar-label ml-3">Ajuda</span>
                </a>
            </li>

            <!-- Ferramentas -->
            <li class="mt-4 px-4">
                <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider sidebar-label">Ferramentas</h3>
            </li>
            <li class="mb-1">
                <a href="relatorios.php" class="flex items-center py-3 px-4 text-gray-300 hover:bg-gray-700 hover:text-white">
                    <i class="fas fa-chart-bar w-5"></i>
                    <span class="sidebar-label ml-3">Relatórios</span>
                </a>
            </li>
            <li class="mb-1">
                <a href="configuracoes.php" class="flex items-center py-3 px-4 text-gray-300 hover:bg-gray-700 hover:text-white">
                    <i class="fas fa-cog w-5"></i>
                    <span class="sidebar-label ml-3">Configurações</span>
                </a>
            </li>
            <li class="mb-1">
                <a href="perfil.php" class="flex items-center py-3 px-4 text-gray-300 hover:bg-gray-700 hover:text-white">
                    <i class="fas fa-user-cog w-5"></i>
                    <span class="sidebar-label ml-3">Meu Perfil</span>
                </a>
            </li>
        </ul>
    </nav>
    </div> <!-- Fechamento da div#sidebar-content -->
</aside>

<script>
// Garante que a rolagem do sidebar funcione corretamente
document.addEventListener('DOMContentLoaded', function() {
    const sidebarContent = document.getElementById('sidebar-content');

    // Verifica se o conteúdo do sidebar é maior que a altura da janela
    function checkSidebarHeight() {
        const windowHeight = window.innerHeight;
        const sidebarHeaderHeight = document.getElementById('sidebar-header').offsetHeight;
        const availableHeight = windowHeight - sidebarHeaderHeight;

        // Ajusta a altura máxima do conteúdo do sidebar
        sidebarContent.style.maxHeight = availableHeight + 'px';
    }

    // Executa a verificação inicial
    checkSidebarHeight();

    // Executa a verificação quando a janela for redimensionada
    window.addEventListener('resize', checkSidebarHeight);
});
</script>
