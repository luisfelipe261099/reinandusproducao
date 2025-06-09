            </main>
            
            <footer class="bg-white p-4 border-t border-gray-200 text-center text-gray-600 text-sm">
                <p>&copy; <?php echo date('Y'); ?> Faciência ERP. Todos os direitos reservados.</p>
            </footer>
        </div>
    </div>
    
    <script>
        // Toggle sidebar
        document.getElementById('sidebar-toggle').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('main-content');
            
            sidebar.classList.toggle('sidebar-collapsed');
            mainContent.classList.toggle('main-content-expanded');
            
            // Altera o ícone do botão
            const icon = this.querySelector('i');
            if (sidebar.classList.contains('sidebar-collapsed')) {
                icon.classList.remove('fa-chevron-left');
                icon.classList.add('fa-chevron-right');
            } else {
                icon.classList.remove('fa-chevron-right');
                icon.classList.add('fa-chevron-left');
            }
        });
        
        // Toggle mobile sidebar
        document.getElementById('mobile-toggle').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('open');
        });
        
        // Toggle user menu
        document.getElementById('user-menu-button').addEventListener('click', function() {
            const menu = document.getElementById('user-menu');
            menu.classList.toggle('hidden');
        });
        
        // Toggle notifications dropdown
        const notificationsButton = document.getElementById('notifications-button');
        if (notificationsButton) {
            notificationsButton.addEventListener('click', function() {
                const dropdown = document.getElementById('notifications-dropdown');
                dropdown.classList.toggle('hidden');
            });
        }
        
        // Fechar dropdowns ao clicar fora deles
        document.addEventListener('click', function(event) {
            const userMenuButton = document.getElementById('user-menu-button');
            const userMenu = document.getElementById('user-menu');
            const notificationsButton = document.getElementById('notifications-button');
            const notificationsDropdown = document.getElementById('notifications-dropdown');
            
            // Fechar menu do usuário
            if (userMenuButton && userMenu && !userMenuButton.contains(event.target) && !userMenu.contains(event.target)) {
                userMenu.classList.add('hidden');
            }
            
            // Fechar dropdown de notificações
            if (notificationsButton && notificationsDropdown && !notificationsButton.contains(event.target) && !notificationsDropdown.contains(event.target)) {
                notificationsDropdown.classList.add('hidden');
            }
        });
        
        // Marcar notificações como lidas
        const notificationItems = document.querySelectorAll('.notification-item');
        notificationItems.forEach(item => {
            item.addEventListener('click', function() {
                const notificationId = this.getAttribute('data-id');
                
                // Envia uma requisição AJAX para marcar a notificação como lida
                fetch('ajax/marcar_notificacao_lida.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'id=' + notificationId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove a classe de não lida
                        this.classList.remove('bg-blue-50');
                        
                        // Atualiza o contador de notificações
                        const badge = document.querySelector('#notifications-button .absolute');
                        if (badge) {
                            const count = parseInt(badge.textContent) - 1;
                            if (count > 0) {
                                badge.textContent = count;
                            } else {
                                badge.remove();
                            }
                        }
                    }
                });
            });
        });
        
        // Animações de entrada
        document.addEventListener('DOMContentLoaded', function() {
            const animateElements = document.querySelectorAll('.animate-fade-in');
            animateElements.forEach((el, index) => {
                setTimeout(() => {
                    el.style.opacity = '1';
                    el.style.transform = 'translateY(0)';
                }, 100 + (index * 50));
            });
        });
        
        <?php if (isset($page_scripts)): ?>
        <?php echo $page_scripts; ?>
        <?php endif; ?>
    </script>
</body>
</html>
