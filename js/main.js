// Main JavaScript para o sistema

document.addEventListener('DOMContentLoaded', function() {
    // Controle do sidebar
    const sidebar = document.getElementById('sidebar');
    const toggleButton = document.getElementById('toggle-sidebar');
    const overlay = document.getElementById('sidebar-overlay');

    // Toggle sidebar no mobile
    if (toggleButton) {
        toggleButton.addEventListener('click', function() {
            sidebar.classList.toggle('sidebar-expanded');
            overlay.classList.toggle('hidden');
        });
    }

    // Fecha sidebar ao clicar no overlay
    if (overlay) {
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('sidebar-expanded');
            overlay.classList.add('hidden');
        });
    }

    // Fecha sidebar ao redimensionar para desktop
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 1024) {
            sidebar.classList.remove('sidebar-expanded');
            overlay.classList.add('hidden');
        }
    });

    // Destaca o item de menu ativo
    const currentPath = window.location.pathname;
    const currentPage = currentPath.substring(currentPath.lastIndexOf('/') + 1);
    const menuItems = document.querySelectorAll('.nav-item');

    menuItems.forEach(item => {
        const href = item.getAttribute('href');
        if (href) {
            const hrefPage = href.split('?')[0];
            if (hrefPage === currentPage || currentPage.startsWith(hrefPage)) {
                item.classList.add('active');
            }
        }
    });

    // User menu dropdown
    const userMenuButton = document.getElementById('user-menu-button');
    if (userMenuButton) {
        userMenuButton.addEventListener('click', function() {
            const menu = document.getElementById('user-menu');
            menu.classList.toggle('hidden');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const userMenu = document.getElementById('user-menu');
            if (userMenu && !userMenuButton.contains(event.target) && !userMenu.contains(event.target)) {
                userMenu.classList.add('hidden');
            }
        });
    }

    // Mensagens de alerta
    const alertMessages = document.querySelectorAll('.alert-message');
    if (alertMessages.length > 0) {
        alertMessages.forEach(alert => {
            setTimeout(() => {
                alert.classList.add('opacity-0');
                setTimeout(() => {
                    alert.remove();
                }, 300);
            }, 5000);
        });
    }

    // Formatação de campos
    const formatters = {
        cpf: function(value) {
            return value
                .replace(/\D/g, '')
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d{1,2})/, '$1-$2')
                .replace(/(-\d{2})\d+?$/, '$1');
        },
        telefone: function(value) {
            return value
                .replace(/\D/g, '')
                .replace(/(\d{2})(\d)/, '($1) $2')
                .replace(/(\d{5})(\d)/, '$1-$2')
                .replace(/(-\d{4})\d+?$/, '$1');
        },
        cep: function(value) {
            return value
                .replace(/\D/g, '')
                .replace(/(\d{5})(\d)/, '$1-$2')
                .replace(/(-\d{3})\d+?$/, '$1');
        }
    };

    // Aplica formatação aos campos
    document.querySelectorAll('[data-format]').forEach(input => {
        const format = input.getAttribute('data-format');
        if (formatters[format]) {
            input.addEventListener('input', function(e) {
                e.target.value = formatters[format](e.target.value);
            });
        }
    });
});
