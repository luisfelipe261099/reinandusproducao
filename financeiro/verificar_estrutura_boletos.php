<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/Database.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    
    // Verificar estrutura da tabela boletos
    $colunas = $db->fetchAll("DESCRIBE boletos");
    
    echo json_encode([
        'status' => 'sucesso',
        'colunas' => $colunas
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'erro',
        'mensagem' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
