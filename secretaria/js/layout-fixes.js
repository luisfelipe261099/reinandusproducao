/**
 * Script para corrigir problemas de layout
 */

document.addEventListener('DOMContentLoaded', function() {
    // Garante que o conteúdo principal tenha a margem correta
    function adjustMainContentMargin() {
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.querySelector('.main-content');
        
        if (!sidebar || !mainContent) return;
        
        const isSidebarCollapsed = sidebar.classList.contains('sidebar-collapsed');
        const isMobile = window.innerWidth <= 768;
        
        if (isMobile) {
            mainContent.style.marginLeft = '0';
            mainContent.style.width = '100%';
        } else {
            if (isSidebarCollapsed) {
                mainContent.style.marginLeft = '70px';
                mainContent.style.width = 'calc(100% - 70px)';
            } else {
                mainContent.style.marginLeft = '250px';
                mainContent.style.width = 'calc(100% - 250px)';
            }
        }
    }
    
    // Ajusta a altura do conteúdo do sidebar
    function adjustSidebarContentHeight() {
        const sidebarContent = document.getElementById('sidebar-content');
        const sidebarHeader = document.getElementById('sidebar-header');
        
        if (!sidebarContent || !sidebarHeader) return;
        
        const windowHeight = window.innerHeight;
        const headerHeight = sidebarHeader.offsetHeight;
        const availableHeight = windowHeight - headerHeight;
        
        sidebarContent.style.maxHeight = availableHeight + 'px';
    }
    
    // Executa os ajustes iniciais
    adjustMainContentMargin();
    adjustSidebarContentHeight();
    
    // Adiciona listeners para redimensionamento da janela
    window.addEventListener('resize', function() {
        adjustMainContentMargin();
        adjustSidebarContentHeight();
    });
    
    // Adiciona listener para o botão de toggle do sidebar
    const toggleButton = document.getElementById('toggle-sidebar');
    if (toggleButton) {
        toggleButton.addEventListener('click', function() {
            setTimeout(adjustMainContentMargin, 10); // Pequeno delay para garantir que as classes foram atualizadas
        });
    }
});
