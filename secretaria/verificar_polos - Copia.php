<?php
/**
 * Script para verificar a estrutura da tabela polos e adicionar o campo mec
 */

// Inicializa o sistema
require_once 'includes/init.php';

// Instancia o banco de dados
$db = Database::getInstance();

try {
    echo "<h1>Verificação da tabela polos</h1>";
    
    // Verifica se a tabela polos existe
    $sql = "SHOW TABLES LIKE 'polos'";
    $tabela_existe = $db->fetchOne($sql);
    
    if (!$tabela_existe) {
        echo "<p style='color:red;'>A tabela polos não existe!</p>";
        exit;
    }
    
    echo "<p style='color:green;'>A tabela polos existe.</p>";
    
    // Verifica se o campo mec existe
    $sql = "SHOW COLUMNS FROM polos LIKE 'mec'";
    $campo_existe = $db->fetchOne($sql);
    
    if ($campo_existe) {
        echo "<p style='color:green;'>O campo 'mec' já existe na tabela polos.</p>";
    } else {
        echo "<p style='color:orange;'>O campo 'mec' não existe na tabela polos. Adicionando...</p>";
        
        // Adiciona o campo mec
        $sql = "ALTER TABLE polos ADD COLUMN mec VARCHAR(255) NULL COMMENT 'Nome do polo registrado no MEC'";
        $db->query($sql);
        
        echo "<p style='color:green;'>Campo 'mec' adicionado com sucesso!</p>";
    }
    
    // Mostra a estrutura atual da tabela
    $sql = "DESCRIBE polos";
    $estrutura = $db->fetchAll($sql);
    
    echo "<h2>Estrutura atual da tabela polos</h2>";
    echo "<pre>";
    print_r($estrutura);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>Erro: " . $e->getMessage() . "</p>";
}
