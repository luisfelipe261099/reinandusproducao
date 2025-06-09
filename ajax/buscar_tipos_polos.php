<?php
// Inclui o arquivo de configuração
require_once '../config.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Usuário não autenticado']);
    exit;
}

// Busca os tipos de polos
$sql = "SELECT id, nome, descricao FROM tipos_polos WHERE status = 'ativo' ORDER BY nome ASC";

try {
    $tipos_polos = $db->fetchAll($sql);
    
    header('Content-Type: application/json');
    echo json_encode(['tipos_polos' => $tipos_polos ?: []]);
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Erro ao buscar tipos de polos: ' . $e->getMessage()]);
}
