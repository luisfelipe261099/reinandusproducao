<?php
// Inicializa o sistema
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/chamados_functions.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica se o usuário tem permissão para visualizar turmas
exigirPermissao('chamados', 'visualizar');

// Instancia o banco de dados
$db = Database::getInstance();

// Verifica se os parâmetros foram informados
if (!isset($_GET['polo_id']) || empty($_GET['polo_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['erro' => 'Polo não informado.']);
    exit;
}

$polo_id = (int)$_GET['polo_id'];
$termo = $_GET['termo'] ?? '';

// Verifica se o usuário tem permissão para acessar este polo
if (getUsuarioTipo() == 'polo') {
    $usuario = $db->fetchOne("SELECT polo_id FROM usuarios WHERE id = ?", [getUsuarioId()]);
    if ($usuario['polo_id'] != $polo_id) {
        header('Content-Type: application/json');
        echo json_encode(['erro' => 'Você não tem permissão para acessar este polo.']);
        exit;
    }
}

// Busca as turmas do polo
try {
    $turmas = buscarTurmasPorPolo($db, $polo_id, $termo);

    // Retorna as turmas em formato JSON
    header('Content-Type: application/json');
    echo json_encode(['turmas' => $turmas]);
} catch (Exception $e) {
    // Log do erro para debug
    error_log("Erro na busca de turmas: " . $e->getMessage());

    // Retorna mensagem de erro
    header('Content-Type: application/json');
    echo json_encode(['erro' => 'Erro ao buscar turmas: ' . $e->getMessage()]);
}
exit;
