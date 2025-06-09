                </div>
            </main>

            <!-- Footer -->
            <?php include 'footer.php'; ?>
        </div>
    </div>

    <script src="../js/layout-fixes.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle sidebar
            const toggleButton = document.getElementById('toggle-sidebar');
            if (toggleButton) {
                toggleButton.addEventListener('click', function() {
                    const sidebar = document.getElementById('sidebar');
                    sidebar.classList.toggle('sidebar-collapsed');
                    sidebar.classList.toggle('sidebar-expanded');

                    const labels = document.querySelectorAll('.sidebar-label');
                    labels.forEach(label => {
                        label.classList.toggle('hidden');
                    });
                });
            }

            // Toggle user menu
            const userMenuButton = document.getElementById('user-menu-button');
            if (userMenuButton) {
                userMenuButton.addEventListener('click', function() {
                    const menu = document.getElementById('user-menu');
                    menu.classList.toggle('hidden');
                });
            }

            // Close user menu when clicking outside
            document.addEventListener('click', function(event) {
                const menu = document.getElementById('user-menu');
                const button = document.getElementById('user-menu-button');

                if (menu && button && !menu.contains(event.target) && !button.contains(event.target)) {
                    menu.classList.add('hidden');
                }
            });
        });
    </script>
    <?php if (isset($extra_js)): ?>
    <?php echo $extra_js; ?>
    <?php endif; ?>
</body>
</html>
