<?php
/**
 * Script para verificar a estrutura da tabela disciplinas
 */

// Carrega as configurações
require_once 'config/config.php';
require_once 'includes/Database.php';

try {
    // Conecta ao banco de dados
    $db = Database::getInstance();
    
    echo "<h2>Estrutura da tabela disciplinas</h2>";
    
    // Verifica a estrutura da tabela disciplinas
    $sql = "DESCRIBE disciplinas";
    $colunas = $db->fetchAll($sql);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Chave</th><th>Padrão</th><th>Extra</th></tr>";
    
    foreach ($colunas as $coluna) {
        echo "<tr>";
        echo "<td>{$coluna['Field']}</td>";
        echo "<td>{$coluna['Type']}</td>";
        echo "<td>{$coluna['Null']}</td>";
        echo "<td>{$coluna['Key']}</td>";
        echo "<td>{$coluna['Default']}</td>";
        echo "<td>{$coluna['Extra']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<h2>Constraints da tabela disciplinas</h2>";
    
    // Verifica as constraints
    $sql = "SELECT 
                CONSTRAINT_NAME,
                COLUMN_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME
            FROM 
                INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE 
                TABLE_SCHEMA = 'u682219090_faciencia_erp' 
                AND TABLE_NAME = 'disciplinas' 
                AND REFERENCED_TABLE_NAME IS NOT NULL";
    
    $constraints = $db->fetchAll($sql);
    
    if (!empty($constraints)) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Nome da Constraint</th><th>Coluna</th><th>Tabela Referenciada</th><th>Coluna Referenciada</th></tr>";
        
        foreach ($constraints as $constraint) {
            echo "<tr>";
            echo "<td>{$constraint['CONSTRAINT_NAME']}</td>";
            echo "<td>{$constraint['COLUMN_NAME']}</td>";
            echo "<td>{$constraint['REFERENCED_TABLE_NAME']}</td>";
            echo "<td>{$constraint['REFERENCED_COLUMN_NAME']}</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>Nenhuma constraint de chave estrangeira encontrada.</p>";
    }
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "<br>";
}
?>
