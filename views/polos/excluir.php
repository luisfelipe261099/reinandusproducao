<?php
// As funções executarConsulta e executarConsultaAll já estão definidas no arquivo principal

// Verifica se o ID foi informado
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Redireciona para a listagem com mensagem de erro
    $_SESSION['mensagem'] = 'ID do polo não informado.';
    $_SESSION['mensagem_tipo'] = 'erro';
    header('Location: polos.php');
    exit;
}

$id = (int)$_GET['id'];

// Verifica se o polo existe
$sql = "SELECT id, nome FROM polos WHERE id = ?";
$polo = executarConsulta($db, $sql, [$id]);

if (!$polo) {
    // Redireciona para a listagem com mensagem de erro
    $_SESSION['mensagem'] = 'Polo não encontrado.';
    $_SESSION['mensagem_tipo'] = 'erro';
    header('Location: polos.php');
    exit;
}

// Verifica se existem cursos associados ao polo
$sql = "SELECT COUNT(*) as total FROM cursos WHERE polo_id = ?";
$resultado = executarConsulta($db, $sql, [$id]);
$total_cursos = $resultado['total'] ?? 0;

if ($total_cursos > 0) {
    // Redireciona para a listagem com mensagem de erro
    $_SESSION['mensagem'] = 'Não é possível excluir o polo pois existem cursos associados a ele.';
    $_SESSION['mensagem_tipo'] = 'erro';
    header('Location: polos.php?action=visualizar&id=' . $id);
    exit;
}

try {
    // Exclui o polo
    $sql = "DELETE FROM polos WHERE id = ?";
    $db->query($sql, [$id]);

    // Redireciona para a listagem com mensagem de sucesso
    $_SESSION['mensagem'] = 'Polo excluído com sucesso!';
    $_SESSION['mensagem_tipo'] = 'sucesso';
    header('Location: polos.php');
    exit;
} catch (Exception $e) {
    // Registra o erro no log
    error_log('Erro ao excluir polo: ' . $e->getMessage());

    // Redireciona com mensagem de erro
    $_SESSION['mensagem'] = 'Erro ao excluir o polo: ' . $e->getMessage();
    $_SESSION['mensagem_tipo'] = 'erro';
    header('Location: polos.php?action=visualizar&id=' . $id);
    exit;
}
