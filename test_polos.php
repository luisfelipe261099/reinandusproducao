<?php
require_once 'includes/init.php';

try {
    $db = Database::getInstance();
    
    // Teste 1: Consulta todos os polos
    $todos_polos = $db->fetchAll("SELECT id, nome, cidade, estado, status FROM polos ORDER BY nome ASC");
    echo "=== TODOS OS POLOS ===\n";
    echo "Total de polos: " . count($todos_polos) . "\n";
    foreach($todos_polos as $polo) {
        echo "ID: {$polo['id']}, Nome: {$polo['nome']}, Status: {$polo['status']}\n";
    }
    
    // Teste 2: Consulta apenas polos ativos
    $polos_ativos = $db->fetchAll("SELECT id, nome, cidade, estado FROM polos WHERE status = 'ativo' ORDER BY nome ASC");
    echo "\n=== POLOS ATIVOS ===\n";
    echo "Polos ativos: " . count($polos_ativos) . "\n";
    foreach($polos_ativos as $polo) {
        echo "ID: {$polo['id']}, Nome: {$polo['nome']}\n";
    }
    
    // Teste 3: Verifica estrutura da tabela polos
    $estrutura = $db->fetchAll("DESCRIBE polos");
    echo "\n=== ESTRUTURA DA TABELA POLOS ===\n";
    foreach($estrutura as $campo) {
        echo "Campo: {$campo['Field']}, Tipo: {$campo['Type']}, Null: {$campo['Null']}, Default: {$campo['Default']}\n";
    }
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
