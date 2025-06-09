/**
 * Este script força o menu lateral a ficar sempre expandido
 * Ele é carregado em todas as páginas e tem precedência sobre qualquer outro script
 */

// Função para forçar o menu a ficar sempre expandido
function forceExpandedMenu() {
    // Seleciona os elementos do menu
    const sidebar = document.getElementById('sidebar');
    const toggleButton = document.getElementById('toggle-sidebar');
    const sidebarLabels = document.querySelectorAll('.sidebar-label');
    const sidebarLogo = document.querySelector('.sidebar-logo-full');
    
    if (sidebar) {
        // Remove a classe de menu colapsado
        sidebar.classList.remove('sidebar-collapsed');
        // Adiciona a classe de menu expandido
        sidebar.classList.add('sidebar-expanded');
        // Define a largura do menu
        sidebar.style.width = '250px';
        
        // Garante que os labels estejam visíveis
        sidebarLabels.forEach(label => {
            label.classList.remove('hidden');
        });
        
        // Garante que o logo completo esteja visível
        if (sidebarLogo) {
            sidebarLogo.classList.remove('hidden');
        }
        
        // Oculta o botão de toggle
        if (toggleButton) {
            toggleButton.style.display = 'none';
        }
        
        // Salva a preferência do usuário como sempre expandido
        localStorage.setItem('sidebar-collapsed', 'false');
        
        // Ajusta o conteúdo principal
        const mainContent = document.querySelector('.main-content') || 
                           document.querySelector('.flex-1.flex.flex-col.overflow-hidden') ||
                           document.querySelector('#content-wrapper');
        
        if (mainContent) {
            mainContent.style.marginLeft = '250px';
            mainContent.style.width = 'calc(100% - 250px)';
        }
    }
    
    // Sobrescreve o evento de clique do botão de toggle
    if (toggleButton) {
        // Remove todos os event listeners existentes
        const newToggleButton = toggleButton.cloneNode(true);
        toggleButton.parentNode.replaceChild(newToggleButton, toggleButton);
        
        // Adiciona um novo event listener que não faz nada
        newToggleButton.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        });
    }
}

// Executa a função quando o DOM estiver carregado
document.addEventListener('DOMContentLoaded', forceExpandedMenu);

// Executa a função novamente após um pequeno delay para garantir que ela tenha precedência
setTimeout(forceExpandedMenu, 100);

// Executa a função periodicamente para garantir que o menu permaneça expandido
setInterval(forceExpandedMenu, 1000);
