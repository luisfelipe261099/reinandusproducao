<?php
// Verifica se o ID do polo foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setMensagem('erro', 'ID do polo não fornecido.');
    redirect('polos.php');
    exit;
}

// Obtém o ID do polo
$polo_id = (int)$_GET['id'];

// Verifica se o polo existe
$db = Database::getInstance();
$sql = "SELECT * FROM polos WHERE id = ?";
$polo = $db->fetchOne($sql, [$polo_id]);

if (!$polo) {
    setMensagem('erro', 'Polo não encontrado.');
    redirect('polos.php');
    exit;
}

// Busca as informações financeiras do polo
$sql = "SELECT * FROM polos_financeiro WHERE polo_id = ? LIMIT 1";
$financeiro = $db->fetchOne($sql, [$polo_id]);

// Se não existir, inicializa um array vazio
if (!$financeiro) {
    $financeiro = [];
}
?>

<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Editar Informações Financeiras - <?php echo htmlspecialchars($polo['nome']); ?></h1>
        
        <div class="flex space-x-2">
            <a href="polos.php?action=editar&id=<?php echo $polo_id; ?>" class="btn-secondary">
                <i class="fas fa-arrow-left mr-2"></i> Voltar
            </a>
        </div>
    </div>
    
    <?php if (isset($_SESSION['mensagem'])): ?>
        <div class="mb-6 p-4 rounded-md <?php echo $_SESSION['mensagem_tipo'] === 'sucesso' ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'; ?>">
            <?php echo $_SESSION['mensagem']; ?>
            <?php unset($_SESSION['mensagem'], $_SESSION['mensagem_tipo']); ?>
        </div>
    <?php endif; ?>
    
    <form action="polos.php?action=salvar_financeiro_novo" method="POST" class="space-y-6">
        <input type="hidden" name="polo_id" value="<?php echo $polo_id; ?>">
        
        <?php include 'form_financeiro_novo.php'; ?>
        
        <div class="flex justify-end space-x-2 mt-6">
            <a href="polos.php?action=editar&id=<?php echo $polo_id; ?>" class="btn-secondary">
                <i class="fas fa-times mr-2"></i> Cancelar
            </a>
            <button type="submit" class="btn-primary">
                <i class="fas fa-save mr-2"></i> Salvar Informações Financeiras
            </button>
        </div>
    </form>
</div>
