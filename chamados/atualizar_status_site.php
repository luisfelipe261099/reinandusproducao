<?php
// Inicializa o sistema
require_once __DIR__ . '/../includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica se o usuário tem permissão para editar chamados
exigirPermissao('chamados', 'editar');

// Verifica se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setMensagem('erro', 'Método inválido.');
    redirect('index.php?view=chamados_site');
    exit;
}

// Verifica se o ID foi fornecido
if (!isset($_POST['id']) || empty($_POST['id'])) {
    setMensagem('erro', 'ID da solicitação não informado.');
    redirect('index.php?view=chamados_site');
    exit;
}

// Obtém os dados do formulário
$id = (int)$_POST['id'];
$status = $_POST['status'] ?? '';
$observacao = $_POST['observacao'] ?? '';

// Validação básica
if (empty($status)) {
    setMensagem('erro', 'O status é obrigatório.');
    redirect("visualizar_site.php?id=$id");
    exit;
}

// Instancia o banco de dados
$db = Database::getInstance();

try {
    // Verifica se a solicitação existe
    $sql = "SELECT * FROM solicitacoes_site WHERE id = ?";
    $solicitacao = $db->fetchOne($sql, [$id]);
    
    if (!$solicitacao) {
        setMensagem('erro', 'Solicitação não encontrada.');
        redirect('index.php?view=chamados_site');
        exit;
    }
    
    // Atualiza o status da solicitação
    $dados = [
        'status' => $status,
        'observacao' => $observacao
    ];
    
    $db->update('solicitacoes_site', $dados, 'id = ?', [$id]);
    
    // Registra o log
    registrarLog(
        'solicitacoes_site',
        'editar',
        "Status da solicitação ID: {$id} atualizado para {$status}",
        $id,
        'solicitacoes_site'
    );
    
    // Define a mensagem de sucesso
    setMensagem('sucesso', 'Status da solicitação atualizado com sucesso.');
    
    // Redireciona de volta para a página de visualização
    redirect("visualizar_site.php?id=$id");
    
} catch (Exception $e) {
    // Define a mensagem de erro
    setMensagem('erro', 'Erro ao atualizar o status da solicitação: ' . $e->getMessage());
    
    // Redireciona de volta para a página de visualização
    redirect("visualizar_site.php?id=$id");
}
?>
