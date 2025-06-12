<?php
// Teste básico de conectividade
echo "=== TESTE DE CONECTIVIDADE ===\n";

// Teste 1: Configurações
echo "1. Testando configurações...\n";
require_once 'config/database.php';
echo "   DB_HOST: " . DB_HOST . "\n";
echo "   DB_NAME: " . DB_NAME . "\n";
echo "   DB_USER: " . DB_USER . "\n";
echo "   DB_PASS: " . (strlen(DB_PASS) > 0 ? "***DEFINIDA***" : "VAZIA") . "\n";

// Teste 2: Conexão PDO direta
echo "\n2. Testando conexão PDO direta...\n";
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    echo "   ✓ Conexão PDO estabelecida com sucesso!\n";
    
    // Teste uma consulta simples
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM polos");
    $result = $stmt->fetch();
    echo "   ✓ Total de polos: " . $result['total'] . "\n";
    
    // Teste polos ativos
    $stmt = $pdo->prepare("SELECT id, nome, status FROM polos WHERE status = ? ORDER BY nome ASC");
    $stmt->execute(['ativo']);
    $polos_ativos = $stmt->fetchAll();
    echo "   ✓ Polos ativos: " . count($polos_ativos) . "\n";
    
    foreach($polos_ativos as $polo) {
        echo "     - ID: {$polo['id']}, Nome: {$polo['nome']}, Status: {$polo['status']}\n";
    }
    
} catch (Exception $e) {
    echo "   ✗ Erro na conexão: " . $e->getMessage() . "\n";
}

echo "\n=== FIM DO TESTE ===\n";
?>
