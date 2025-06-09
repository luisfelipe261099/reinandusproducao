<?php
/**
 * Script para verificar a tabela de cursos
 */

// Inicializa o sistema
require_once __DIR__ . '/includes/init.php';

// Instancia o banco de dados
$db = Database::getInstance();

try {
    // Verifica se a tabela cursos existe
    $sql = "SHOW TABLES LIKE 'cursos'";
    $result = $db->fetchOne($sql);
    
    if (!$result) {
        echo "A tabela 'cursos' não existe no banco de dados.<br>";
        exit;
    }
    
    echo "A tabela 'cursos' existe no banco de dados.<br>";
    
    // Verifica a estrutura da tabela
    $sql = "DESCRIBE cursos";
    $colunas = $db->fetchAll($sql);
    
    echo "Estrutura da tabela 'cursos':<br>";
    echo "<pre>";
    print_r($colunas);
    echo "</pre>";
    
    // Conta o número de registros
    $sql = "SELECT COUNT(*) as total FROM cursos";
    $result = $db->fetchOne($sql);
    
    echo "Total de registros na tabela 'cursos': " . $result['total'] . "<br>";
    
    // Lista os primeiros 10 registros
    if ($result['total'] > 0) {
        $sql = "SELECT * FROM cursos LIMIT 10";
        $cursos = $db->fetchAll($sql);
        
        echo "Primeiros registros da tabela 'cursos':<br>";
        echo "<pre>";
        print_r($cursos);
        echo "</pre>";
    }
    
} catch (Exception $e) {
    echo "Erro ao verificar a tabela 'cursos': " . $e->getMessage();
}
