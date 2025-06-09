<?php
// Inicializa o sistema
require_once __DIR__ . '/includes/init.php';

// Instancia o banco de dados
$db = Database::getInstance();

echo "<h1>Verificação da Atualização da Tabela Polos</h1>";

// Verifica se as colunas foram adicionadas
$colunas = ['cidade', 'cidade_ibge'];
$todas_colunas_existem = true;

echo "<h2>Verificação de Colunas</h2>";
foreach ($colunas as $coluna) {
    $sql = "SHOW COLUMNS FROM `polos` LIKE '{$coluna}'";
    $result = $db->fetchOne($sql);
    
    if ($result) {
        echo "<p style='color: green;'>✓ Coluna '{$coluna}' existe na tabela polos.</p>";
    } else {
        echo "<p style='color: red;'>✗ Coluna '{$coluna}' não existe na tabela polos.</p>";
        $todas_colunas_existem = false;
    }
}

// Verifica se os dados foram migrados
if ($todas_colunas_existem) {
    echo "<h2>Verificação de Migração de Dados</h2>";
    
    // Conta polos com cidade preenchida
    $sql = "SELECT COUNT(*) as total FROM `polos` WHERE `cidade` IS NOT NULL AND `cidade` != ''";
    $result = $db->fetchOne($sql);
    $total_com_cidade = $result['total'] ?? 0;
    
    // Conta total de polos
    $sql = "SELECT COUNT(*) as total FROM `polos`";
    $result = $db->fetchOne($sql);
    $total_polos = $result['total'] ?? 0;
    
    echo "<p>Total de polos: {$total_polos}</p>";
    echo "<p>Polos com cidade preenchida: {$total_com_cidade}</p>";
    
    if ($total_com_cidade > 0) {
        echo "<p style='color: green;'>✓ Dados de cidade foram migrados.</p>";
        
        // Mostra alguns exemplos
        $sql = "SELECT id, nome, cidade, cidade_ibge FROM `polos` WHERE `cidade` IS NOT NULL LIMIT 5";
        $exemplos = $db->fetchAll($sql);
        
        if (!empty($exemplos)) {
            echo "<h3>Exemplos de dados migrados:</h3>";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>Nome</th><th>Cidade</th><th>Código IBGE</th></tr>";
            
            foreach ($exemplos as $exemplo) {
                echo "<tr>";
                echo "<td>{$exemplo['id']}</td>";
                echo "<td>{$exemplo['nome']}</td>";
                echo "<td>{$exemplo['cidade']}</td>";
                echo "<td>{$exemplo['cidade_ibge']}</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        }
    } else {
        echo "<p style='color: red;'>✗ Dados de cidade não foram migrados.</p>";
    }
    
    // Verifica se a coluna cidade_id ainda existe
    $sql = "SHOW COLUMNS FROM `polos` LIKE 'cidade_id'";
    $result = $db->fetchOne($sql);
    
    if ($result) {
        echo "<p style='color: orange;'>! A coluna 'cidade_id' ainda existe na tabela polos.</p>";
    } else {
        echo "<p style='color: green;'>✓ A coluna 'cidade_id' foi removida da tabela polos.</p>";
    }
}
?>