<?php
/**
 * Página para excluir um funcionário
 */

// Inclui os arquivos necessários
require_once __DIR__ . '/../includes/init.php';

// Verifica se o usuário está logado
exigirLogin();

// Verifica se o usuário tem permissão para acessar esta página
if (!Auth::hasPermission('financeiro', 'excluir')) {
    setMensagem('erro', 'Você não tem permissão para acessar esta página.');
    redirect('funcionarios.php');
    exit;
}

// Inicializa a conexão com o banco de dados
$db = Database::getInstance();

// Verifica se o ID foi informado
if (!isset($_GET['id'])) {
    setMensagem('erro', 'ID do funcionário não informado.');
    redirect('funcionarios.php');
    exit;
}

$id = (int)$_GET['id'];

// Busca os dados do funcionário
$funcionario = $db->fetchOne("SELECT * FROM funcionarios WHERE id = ?", [$id]);

if (!$funcionario) {
    setMensagem('erro', 'Funcionário não encontrado.');
    redirect('funcionarios.php');
    exit;
}

// Verifica se o funcionário tem pagamentos
$pagamentos = $db->fetchAll("SELECT * FROM pagamentos WHERE funcionario_id = ?", [$id]);

if (!empty($pagamentos)) {
    setMensagem('erro', 'Não é possível excluir o funcionário pois existem pagamentos associados a ele.');
    redirect('funcionarios.php');
    exit;
}

try {
    // Exclui o funcionário
    $result = $db->delete('funcionarios', ['id' => $id]);
    
    if ($result) {
        setMensagem('sucesso', 'Funcionário excluído com sucesso.');
    } else {
        setMensagem('erro', 'Erro ao excluir o funcionário.');
    }
} catch (Exception $e) {
    setMensagem('erro', 'Erro ao excluir o funcionário: ' . $e->getMessage());
}

// Redireciona de volta para a página de funcionários
redirect('funcionarios.php');
exit;
