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

// Busca os tipos de polo associados a este polo
$sql = "SELECT pt.tipo_polo_id, tp.nome
        FROM polos_tipos pt
        JOIN tipos_polos tp ON pt.tipo_polo_id = tp.id
        WHERE pt.polo_id = ?";
$tipos_polo = $db->fetchAll($sql, [$polo_id]);

// Se não houver tipos associados, exibe uma mensagem
if (empty($tipos_polo)) {
    setMensagem('erro', 'Este polo não possui tipos associados. Por favor, edite o polo e adicione pelo menos um tipo.');
    redirect('polos.php?action=editar&id=' . $polo_id);
    exit;
}

// Busca as informações financeiras para cada tipo de polo
$financeiro_por_tipo = [];
foreach ($tipos_polo as $tipo) {
    $sql = "SELECT * FROM polos_financeiro WHERE polo_id = ? AND tipo_polo_id = ?";
    $financeiro = $db->fetchOne($sql, [$polo_id, $tipo['tipo_polo_id']]);

    // Se não existir, inicializa um array vazio
    if (!$financeiro) {
        $financeiro = ['tipo_polo_id' => $tipo['tipo_polo_id']];
    }

    $financeiro_por_tipo[$tipo['tipo_polo_id']] = [
        'financeiro' => $financeiro,
        'nome' => $tipo['nome']
    ];
}

// Obtém o tipo selecionado (se houver)
$tipo_selecionado = isset($_GET['tipo']) ? (int)$_GET['tipo'] : (isset($tipos_polo[0]) ? $tipos_polo[0]['tipo_polo_id'] : 0);

// Verifica se o tipo selecionado existe para este polo
$tipo_valido = false;
foreach ($tipos_polo as $tipo) {
    if ($tipo['tipo_polo_id'] == $tipo_selecionado) {
        $tipo_valido = true;
        break;
    }
}

// Se o tipo não for válido, usa o primeiro tipo disponível
if (!$tipo_valido && !empty($tipos_polo)) {
    $tipo_selecionado = $tipos_polo[0]['tipo_polo_id'];
}

// Obtém as informações financeiras do tipo selecionado
$financeiro = isset($financeiro_por_tipo[$tipo_selecionado]['financeiro']) ?
    $financeiro_por_tipo[$tipo_selecionado]['financeiro'] : [];
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

    <!-- Abas para os diferentes tipos de polo -->
    <div class="mb-6">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8">
                <?php foreach ($tipos_polo as $tipo): ?>
                    <a href="polos.php?action=financeiro&id=<?php echo $polo_id; ?>&tipo=<?php echo $tipo['tipo_polo_id']; ?>"
                       class="<?php echo $tipo['tipo_polo_id'] == $tipo_selecionado ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> py-4 px-1 border-b-2 font-medium text-sm">
                        <?php echo htmlspecialchars($tipo['nome']); ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>
    </div>

    <form action="polos.php?action=salvar_financeiro_novo" method="POST" class="space-y-6">
        <input type="hidden" name="polo_id" value="<?php echo $polo_id; ?>">
        <input type="hidden" name="tipo_polo_id" value="<?php echo $tipo_selecionado; ?>">

        <div class="bg-white shadow-md rounded-lg p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">
                Informações Financeiras - <?php echo htmlspecialchars($financeiro_por_tipo[$tipo_selecionado]['nome']); ?>
            </h2>

            <?php include 'form_financeiro_novo.php'; ?>
        </div>

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
