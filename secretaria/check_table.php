<?php
// Carrega as configurações
require_once 'config/config.php';

// Carrega as classes necessárias
require_once 'includes/Database.php';

// Conecta ao banco de dados
$db = Database::getInstance();

// Verifica a estrutura da tabela
try {
    $result = $db->fetchAll("SHOW COLUMNS FROM documentos_emitidos");
    echo "Estrutura da tabela documentos_emitidos:\n";
    print_r($result);
} catch (Exception $e) {
    echo "Erro ao verificar a estrutura da tabela: " . $e->getMessage() . "\n";
}
