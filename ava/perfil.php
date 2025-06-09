<?php
/**
 * Página de perfil do usuário
 */

// Inicializa o sistema
require_once '../includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Define o título da página
$page_title = 'Meu Perfil';

// Obtém os dados do usuário atual
$usuario_id = $_SESSION['usuario_id'];
$db = Database::getInstance();
$sql = "SELECT * FROM usuarios WHERE id = ?";
$usuario = $db->fetchOne($sql, [$usuario_id]);

// Inclui o início do layout
include 'includes/layout_start.php';
?>

<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <h1 class="text-2xl font-bold text-gray-800 mb-4">Meu Perfil</h1>
    <p class="text-gray-600 mb-4">Gerencie suas informações pessoais e preferências de conta.</p>
    
    <div class="bg-yellow-50 p-4 rounded-lg mb-4">
        <div class="flex items-center mb-2">
            <i class="fas fa-exclamation-triangle text-yellow-600 mr-2"></i>
            <h2 class="text-lg font-semibold text-yellow-800">Página em Desenvolvimento</h2>
        </div>
        <p class="text-yellow-600">Esta página está em desenvolvimento e será implementada em breve.</p>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <!-- Informações do Perfil -->
    <div class="md:col-span-2 bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Informações Pessoais</h2>
        
        <form action="#" method="post" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="form-group">
                    <label for="nome" class="block text-sm font-medium text-gray-700 mb-1">Nome Completo</label>
                    <input type="text" id="nome" name="nome" class="form-input" value="<?php echo htmlspecialchars($usuario['nome'] ?? 'Usuário'); ?>" disabled>
                </div>
                
                <div class="form-group">
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                    <input type="email" id="email" name="email" class="form-input" value="<?php echo htmlspecialchars($usuario['email'] ?? 'email@exemplo.com'); ?>" disabled>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="form-group">
                    <label for="telefone" class="block text-sm font-medium text-gray-700 mb-1">Telefone</label>
                    <input type="text" id="telefone" name="telefone" class="form-input" value="<?php echo htmlspecialchars($usuario['telefone'] ?? '(00) 00000-0000'); ?>" disabled>
                </div>
                
                <div class="form-group">
                    <label for="tipo" class="block text-sm font-medium text-gray-700 mb-1">Tipo de Usuário</label>
                    <input type="text" id="tipo" name="tipo" class="form-input" value="<?php echo htmlspecialchars(ucfirst($usuario['tipo'] ?? 'Usuário')); ?>" disabled>
                </div>
            </div>
            
            <div class="form-group">
                <label for="bio" class="block text-sm font-medium text-gray-700 mb-1">Biografia</label>
                <textarea id="bio" name="bio" class="form-textarea" disabled>Informações sobre o usuário não disponíveis.</textarea>
            </div>
            
            <button type="button" class="btn-primary opacity-50 cursor-not-allowed" disabled>
                <i class="fas fa-save mr-2"></i> Salvar Alterações
            </button>
        </form>
    </div>
    
    <!-- Sidebar do Perfil -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex flex-col items-center mb-6">
            <div class="w-32 h-32 rounded-full bg-gray-200 flex items-center justify-center text-gray-400 mb-4">
                <i class="fas fa-user text-4xl"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($usuario['nome'] ?? 'Usuário'); ?></h3>
            <p class="text-sm text-gray-600"><?php echo htmlspecialchars(ucfirst($usuario['tipo'] ?? 'Usuário')); ?></p>
        </div>
        
        <div class="border-t border-gray-200 pt-4">
            <h4 class="text-sm font-semibold text-gray-700 mb-3">Ações da Conta</h4>
            <ul class="space-y-2">
                <li>
                    <a href="#" class="flex items-center text-gray-600 hover:text-blue-600 opacity-50 cursor-not-allowed" disabled>
                        <i class="fas fa-lock w-5"></i>
                        <span class="ml-2">Alterar Senha</span>
                    </a>
                </li>
                <li>
                    <a href="#" class="flex items-center text-gray-600 hover:text-blue-600 opacity-50 cursor-not-allowed" disabled>
                        <i class="fas fa-bell w-5"></i>
                        <span class="ml-2">Notificações</span>
                    </a>
                </li>
                <li>
                    <a href="#" class="flex items-center text-gray-600 hover:text-blue-600 opacity-50 cursor-not-allowed" disabled>
                        <i class="fas fa-shield-alt w-5"></i>
                        <span class="ml-2">Privacidade</span>
                    </a>
                </li>
                <li>
                    <a href="../logout.php" class="flex items-center text-red-600 hover:text-red-700">
                        <i class="fas fa-sign-out-alt w-5"></i>
                        <span class="ml-2">Sair</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>

<?php
// Inclui o fim do layout
include 'includes/layout_end.php';
?>
