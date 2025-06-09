<?php
// Inclui o arquivo de configuração
require_once 'config.php';

// Ativa o modo de exibição de erros para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Obtém uma instância do banco de dados
$db = Database::getInstance();

// Verifica a estrutura da tabela polos
try {
    $result = $db->fetchOne("SHOW TABLES LIKE 'polos'");
    
    if ($result) {
        echo "<h2>Estrutura da tabela 'polos':</h2>";
        
        $columns = $db->fetchAll("SHOW COLUMNS FROM polos");
        echo "<pre>";
        print_r($columns);
        echo "</pre>";
        
        // Verifica se existem registros na tabela
        $count = $db->fetchOne("SELECT COUNT(*) as total FROM polos");
        echo "<p>Total de registros: " . ($count['total'] ?? 0) . "</p>";
        
        if ($count && $count['total'] > 0) {
            // Mostra alguns registros de exemplo
            $polos = $db->fetchAll("SELECT * FROM polos LIMIT 3");
            echo "<h3>Exemplos de registros:</h3>";
            echo "<pre>";
            print_r($polos);
            echo "</pre>";
        }
    } else {
        echo "<p>A tabela 'polos' não existe.</p>";
    }
} catch (Exception $e) {
    echo "<p>Erro ao verificar a tabela 'polos': " . $e->getMessage() . "</p>";
}

// Verifica se existem tabelas relacionadas a tipos de polos
try {
    $tables = $db->fetchAll("SHOW TABLES LIKE '%polo%'");
    
    if (!empty($tables)) {
        echo "<h2>Tabelas relacionadas a polos:</h2>";
        echo "<ul>";
        foreach ($tables as $table) {
            $tableName = reset($table);
            echo "<li>" . $tableName . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>Não foram encontradas outras tabelas relacionadas a polos.</p>";
    }
} catch (Exception $e) {
    echo "<p>Erro ao verificar tabelas relacionadas: " . $e->getMessage() . "</p>";
}

// Verifica se existem tabelas relacionadas a financeiro
try {
    $tables = $db->fetchAll("SHOW TABLES LIKE '%financ%'");
    
    if (!empty($tables)) {
        echo "<h2>Tabelas relacionadas a financeiro:</h2>";
        echo "<ul>";
        foreach ($tables as $table) {
            $tableName = reset($table);
            echo "<li>" . $tableName . "</li>";
            
            // Mostra a estrutura da tabela
            $columns = $db->fetchAll("SHOW COLUMNS FROM " . $tableName);
            echo "<pre>";
            print_r($columns);
            echo "</pre>";
        }
        echo "</ul>";
    } else {
        echo "<p>Não foram encontradas tabelas relacionadas a financeiro.</p>";
    }
} catch (Exception $e) {
    echo "<p>Erro ao verificar tabelas financeiras: " . $e->getMessage() . "</p>";
}
