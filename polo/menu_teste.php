<?php
/**
 * Página de teste para verificar o menu do polo
 */

// Inicializa o sistema
require_once __DIR__ . '/../includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica se o usuário é do tipo polo
if (getUsuarioTipo() !== 'polo') {
    setMensagem('erro', 'Você não tem permissão para acessar esta página.');
    redirect('../index.php');
    exit;
}

// Define o título da página
$titulo_pagina = 'Teste de Menu do Polo';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faciência ERP - <?php echo $titulo_pagina; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/layout-fixes.css">
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <?php include 'includes/header.php'; ?>

            <!-- Main -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
                <div class="container mx-auto">
                    <div class="flex justify-between items-center mb-6">
                        <h1 class="text-2xl font-bold text-gray-800"><?php echo $titulo_pagina; ?></h1>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-4">Teste de Menu do Polo</h2>
                        <p class="text-gray-600 mb-4">Esta página foi criada para testar o novo layout do menu lateral com rolagem.</p>

                        <div class="bg-blue-50 p-4 rounded-lg mb-4">
                            <h3 class="text-lg font-semibold text-blue-800 mb-2">Instruções</h3>
                            <p class="text-blue-600 mb-2">Verifique se:</p>
                            <ul class="list-disc pl-5 text-blue-600">
                                <li>O menu lateral está exibindo todos os itens corretamente</li>
                                <li>Você consegue rolar o menu para ver todos os itens</li>
                                <li>O menu não está sendo cortado na parte inferior</li>
                                <li>O menu não está aparecendo abaixo do footer</li>
                                <li>O botão de toggle do menu funciona corretamente</li>
                            </ul>
                        </div>

                        <div class="bg-green-50 p-4 rounded-lg">
                            <h3 class="text-lg font-semibold text-green-800 mb-2">Nova Estrutura do Menu</h3>
                            <p class="text-green-600 mb-2">O menu agora possui:</p>
                            <ul class="list-disc pl-5 text-green-600">
                                <li>Um cabeçalho fixo que não rola com o resto do conteúdo</li>
                                <li>Uma área de conteúdo com rolagem independente</li>
                                <li>Mais itens de menu para testar a rolagem</li>
                                <li>JavaScript para ajustar automaticamente a altura do menu</li>
                                <li>Estilização melhorada da barra de rolagem</li>
                            </ul>
                            <p class="text-green-600 mt-2">Tente redimensionar a janela do navegador para ver como o menu se adapta.</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-4">Conteúdo de Teste</h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <?php for ($i = 1; $i <= 9; $i++): ?>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h3 class="font-medium text-gray-800 mb-2">Item de Teste <?php echo $i; ?></h3>
                                <p class="text-gray-600">Este é um item de teste para verificar o layout da página.</p>
                            </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
            </main>

            <!-- Footer -->
            <?php include 'includes/footer.php'; ?>
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
</body>
</html>
