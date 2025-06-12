<?php
require_once '../includes/init.php';
require_once '../includes/Database.php';

echo "=== TESTE DO MÓDULO FINANCEIRO ===\n";

try {
    $db = Database::getInstance();
    echo "✓ Conexão com banco OK\n";

    // Verifica se a tabela boletos existe
    $tableExists = $db->fetchOne("SHOW TABLES LIKE 'boletos'");
    if ($tableExists) {
        echo "✓ Tabela 'boletos' existe\n";
        
        // Conta boletos
        $count = $db->fetchOne("SELECT COUNT(*) as total FROM boletos");
        echo "✓ Total de boletos: " . $count['total'] . "\n";
        
        // Lista últimos 5 boletos
        $boletos = $db->fetchAll("SELECT id, nome_pagador, valor, status, created_at FROM boletos ORDER BY created_at DESC LIMIT 5");
        echo "\n=== ÚLTIMOS BOLETOS ===\n";
        foreach ($boletos as $boleto) {
            echo "ID: {$boleto['id']} - {$boleto['nome_pagador']} - R$ " . number_format($boleto['valor'], 2, ',', '.') . " - {$boleto['status']} - {$boleto['created_at']}\n";
        }
    } else {
        echo "❌ Tabela 'boletos' NÃO existe\n";
        echo "➤ Execute setup_basico.php para criar as tabelas\n";
    }

    // Verifica outras tabelas importantes
    $tabelas = ['categorias_financeiras', 'contas_bancarias', 'alunos', 'polos'];
    foreach ($tabelas as $tabela) {
        $exists = $db->fetchOne("SHOW TABLES LIKE '$tabela'");
        echo ($exists ? "✓" : "❌") . " Tabela '$tabela'" . ($exists ? " existe" : " NÃO existe") . "\n";
    }

} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}

echo "\n=== TESTE CONCLUÍDO ===\n";
?>
