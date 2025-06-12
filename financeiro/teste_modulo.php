<?php
require_once '../includes/init.php';
require_once '../includes/Database.php';

$db = Database::getInstance();

echo "=== TESTE DO MÓDULO FINANCEIRO ===\n\n";

// Verifica se a tabela categorias_financeiras existe
try {
    $tableExists = $db->fetchOne("SHOW TABLES LIKE 'categorias_financeiras'");
    if ($tableExists) {
        echo "✓ Tabela categorias_financeiras já existe\n";
        
        // Verifica se tem registros
        $count = $db->fetchOne("SELECT COUNT(*) as total FROM categorias_financeiras");
        echo "  - Total de categorias: " . $count['total'] . "\n";
        
        // Lista as categorias
        $categorias = $db->fetchAll("SELECT nome, tipo, cor FROM categorias_financeiras");
        foreach ($categorias as $cat) {
            echo "  - {$cat['nome']} ({$cat['tipo']}) - Cor: {$cat['cor']}\n";
        }
    } else {
        echo "❌ Tabela categorias_financeiras NÃO existe\n";
    }
} catch (Exception $e) {
    echo "❌ Erro ao verificar categorias_financeiras: " . $e->getMessage() . "\n";
}

echo "\n";

// Verifica se a tabela boletos existe
try {
    $tableExists = $db->fetchOne("SHOW TABLES LIKE 'boletos'");
    if ($tableExists) {
        echo "✓ Tabela boletos já existe\n";
        
        // Verifica se tem registros
        $count = $db->fetchOne("SELECT COUNT(*) as total FROM boletos");
        echo "  - Total de boletos: " . $count['total'] . "\n";
        
        // Lista os últimos 3 boletos
        $boletos = $db->fetchAll("SELECT id, descricao, valor, status, created_at FROM boletos ORDER BY created_at DESC LIMIT 3");
        foreach ($boletos as $boleto) {
            echo "  - Boleto #{$boleto['id']}: {$boleto['descricao']} - R$ {$boleto['valor']} ({$boleto['status']}) - {$boleto['created_at']}\n";
        }
    } else {
        echo "❌ Tabela boletos NÃO existe\n";
    }
} catch (Exception $e) {
    echo "❌ Erro ao verificar boletos: " . $e->getMessage() . "\n";
}

echo "\n";

// Lista todas as tabelas financeiras
try {
    $tabelas = $db->fetchAll("SHOW TABLES LIKE '%financ%' OR SHOW TABLES LIKE 'boletos' OR SHOW TABLES LIKE 'conta%' OR SHOW TABLES LIKE 'mensalidade%'");
    echo "=== TABELAS DO MÓDULO FINANCEIRO ===\n";
    foreach ($tabelas as $tabela) {
        $nomeTabela = array_values($tabela)[0];
        echo "✓ " . $nomeTabela . "\n";
    }
} catch (Exception $e) {
    echo "❌ Erro ao listar tabelas: " . $e->getMessage() . "\n";
}

echo "\n=== FIM DO TESTE ===\n";
?>
