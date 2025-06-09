<?php
/**
 * Cabeçalho do módulo AVA
 */

// Obtém o nome do usuário
$nome_usuario = getUsuarioNome();
$primeiro_nome = explode(' ', $nome_usuario)[0];

// Obtém o nome do polo
$polo_id = getUsuarioPoloId();
$nome_polo = '';

if ($polo_id) {
    $db = Database::getInstance();
    $sql = "SELECT nome FROM polos WHERE id = ?";
    $polo = $db->fetchOne($sql, [$polo_id]);
    
    if ($polo) {
        $nome_polo = $polo['nome'];
    }
}
?>
<header class="bg-white border-b border-gray-200 flex items-center justify-between p-4">
    <div class="flex items-center">
        <button id="toggle-sidebar" class="text-gray-500 focus:outline-none lg:hidden">
            <i class="fas fa-bars"></i>
        </button>
        <div class="ml-4 lg:ml-0">
            <h2 class="text-lg font-medium text-gray-900">Ambiente Virtual de Aprendizagem</h2>
            <?php if (!empty($nome_polo)): ?>
            <p class="text-sm text-gray-600">Polo: <?php echo htmlspecialchars($nome_polo); ?></p>
            <?php endif; ?>
        </div>
    </div>
    <div class="flex items-center">
        <div class="relative">
            <button id="user-menu-button" class="flex items-center space-x-2 focus:outline-none">
                <div class="w-8 h-8 rounded-full bg-indigo-600 flex items-center justify-center text-white">
                    <?php echo strtoupper(substr($primeiro_nome, 0, 1)); ?>
                </div>
                <span class="hidden md:block text-sm font-medium text-gray-700"><?php echo htmlspecialchars($primeiro_nome); ?></span>
                <i class="fas fa-chevron-down text-xs text-gray-400"></i>
            </button>
            <div id="user-menu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50">
                <a href="../polo/perfil.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Meu Perfil</a>
                <a href="../polo/configuracoes.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Configurações</a>
                <a href="../logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Sair</a>
            </div>
        </div>
    </div>
</header>
