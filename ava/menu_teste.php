<?php
/**
 * Página de teste para verificar o menu
 */

// Inicializa o sistema
require_once '../includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Define o título da página
$page_title = 'Teste de Menu';

// Inclui o início do layout
include 'includes/layout_start.php';
?>

<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <h1 class="text-2xl font-bold text-gray-800 mb-4">Teste de Menu</h1>
    <p class="text-gray-600 mb-4">Esta página foi criada para testar o novo layout do menu lateral com rolagem.</p>

    <div class="bg-blue-50 p-4 rounded-lg mb-4">
        <h2 class="text-lg font-semibold text-blue-800 mb-2">Instruções</h2>
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
        <h2 class="text-lg font-semibold text-green-800 mb-2">Nova Estrutura do Menu</h2>
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

<?php
// Inclui o fim do layout
include 'includes/layout_end.php';
?>
