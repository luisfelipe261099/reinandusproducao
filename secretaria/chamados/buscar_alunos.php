<?php
// Inicializa o sistema
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/chamados_functions.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica se o usuário tem permissão para visualizar alunos
exigirPermissao('chamados', 'visualizar');

// Instancia o banco de dados
$db = Database::getInstance();

// Obtém os parâmetros
$polo_id = isset($_GET['polo_id']) ? (int)$_GET['polo_id'] : null;
$termo = $_GET['termo'] ?? '';

// Se o usuário for do tipo polo, só pode buscar alunos do seu próprio polo
if (getUsuarioTipo() == 'polo') {
    $usuario = $db->fetchOne("SELECT polo_id FROM usuarios WHERE id = ?", [getUsuarioId()]);
    $polo_id = $usuario['polo_id']; // Força usar o polo do usuário
}

// Busca os alunos
if ($polo_id) {
    // Se um polo foi especificado, busca apenas os alunos desse polo
    $alunos = buscarAlunosPorPolo($db, $polo_id, $termo);
} else {
    // Se nenhum polo foi especificado, busca todos os alunos (apenas para usuários não-polo)
    if (getUsuarioTipo() != 'polo') {
        $alunos = buscarTodosAlunos($db, $termo);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['erro' => 'Polo não informado.']);
        exit;
    }
}

// Retorna os alunos em formato JSON
header('Content-Type: application/json');
echo json_encode(['alunos' => $alunos]);
exit;
