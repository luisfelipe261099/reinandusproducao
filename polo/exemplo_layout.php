<?php
/**
 * Página de exemplo usando o novo layout padronizado
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
$titulo_pagina = 'Exemplo de Layout Padronizado';

// Define botões adicionais para o título (opcional)
$botoes_titulo = '
<a href="index.php" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded inline-flex items-center text-sm">
    <i class="fas fa-arrow-left mr-2"></i> Voltar
</a>
<a href="menu_teste.php" class="bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded inline-flex items-center text-sm ml-2">
    <i class="fas fa-list mr-2"></i> Testar Menu
</a>
';

// Define CSS adicional (opcional)
$extra_css = '
<style>
    .exemplo-card {
        transition: transform 0.3s ease;
    }
    .exemplo-card:hover {
        transform: translateY(-5px);
    }
</style>
';

// Define JavaScript adicional (opcional)
$extra_js = '
<script>
    // Script específico para esta página
    document.addEventListener("DOMContentLoaded", function() {
        const cards = document.querySelectorAll(".exemplo-card");
        cards.forEach(card => {
            card.addEventListener("click", function() {
                alert("Você clicou em um card de exemplo!");
            });
        });
    });
</script>
';

// Inclui o início do layout
include 'includes/layout_start.php';
?>

<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <h2 class="text-xl font-bold text-gray-800 mb-4">Exemplo de Layout Padronizado</h2>
    <p class="text-gray-600 mb-4">Esta página demonstra o uso do novo layout padronizado para todas as páginas do polo.</p>
    
    <div class="bg-blue-50 p-4 rounded-lg mb-4">
        <h3 class="text-lg font-semibold text-blue-800 mb-2">Benefícios do Layout Padronizado</h3>
        <ul class="list-disc pl-5 text-blue-600">
            <li>Consistência visual em todas as páginas</li>
            <li>Menu lateral com rolagem que funciona corretamente</li>
            <li>Conteúdo principal posicionado corretamente</li>
            <li>Responsividade em diferentes tamanhos de tela</li>
            <li>Facilidade de manutenção e atualização</li>
        </ul>
    </div>
    
    <div class="bg-green-50 p-4 rounded-lg">
        <h3 class="text-lg font-semibold text-green-800 mb-2">Como Usar</h3>
        <p class="text-green-600 mb-2">Para usar este layout em novas páginas:</p>
        <ol class="list-decimal pl-5 text-green-600">
            <li>Defina as variáveis necessárias: <code>$titulo_pagina</code>, <code>$botoes_titulo</code> (opcional), <code>$extra_css</code> (opcional), <code>$extra_js</code> (opcional)</li>
            <li>Inclua o arquivo <code>includes/layout_start.php</code> no início da página</li>
            <li>Adicione o conteúdo específico da página</li>
            <li>Inclua o arquivo <code>includes/layout_end.php</code> no final da página</li>
        </ol>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
    <?php for ($i = 1; $i <= 6; $i++): ?>
    <div class="bg-white rounded-xl shadow-sm p-6 exemplo-card cursor-pointer">
        <div class="flex items-center mb-4">
            <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-500 mr-3">
                <i class="fas fa-<?php echo ['star', 'heart', 'bell', 'bookmark', 'chart-bar', 'cog'][$i-1]; ?>"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-800">Exemplo <?php echo $i; ?></h3>
        </div>
        <p class="text-gray-600">Este é um exemplo de card que usa o layout padronizado. Clique para ver uma interação.</p>
    </div>
    <?php endfor; ?>
</div>

<div class="bg-white rounded-xl shadow-sm p-6">
    <h2 class="text-xl font-bold text-gray-800 mb-4">Responsividade</h2>
    <p class="text-gray-600 mb-4">O layout é totalmente responsivo e se adapta a diferentes tamanhos de tela.</p>
    
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white">
            <thead>
                <tr>
                    <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Dispositivo</th>
                    <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Tamanho da Tela</th>
                    <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Comportamento do Menu</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="py-2 px-4 border-b border-gray-200">Desktop</td>
                    <td class="py-2 px-4 border-b border-gray-200">≥ 1024px</td>
                    <td class="py-2 px-4 border-b border-gray-200">Menu lateral fixo, conteúdo ajustado</td>
                </tr>
                <tr>
                    <td class="py-2 px-4 border-b border-gray-200">Tablet</td>
                    <td class="py-2 px-4 border-b border-gray-200">768px - 1023px</td>
                    <td class="py-2 px-4 border-b border-gray-200">Menu lateral fixo, conteúdo ajustado</td>
                </tr>
                <tr>
                    <td class="py-2 px-4 border-b border-gray-200">Mobile</td>
                    <td class="py-2 px-4 border-b border-gray-200">< 768px</td>
                    <td class="py-2 px-4 border-b border-gray-200">Menu oculto por padrão, expansível</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<?php
// Inclui o fim do layout
include 'includes/layout_end.php';
?>
