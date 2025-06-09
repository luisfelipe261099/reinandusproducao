<?php
/**
 * Script para verificar a tabela de áreas de conhecimento
 */

// Inicializa o sistema
require_once __DIR__ . '/includes/init.php';

// Instancia o banco de dados
$db = Database::getInstance();

try {
    // Verifica se a tabela areas_conhecimento existe
    $sql = "SHOW TABLES LIKE 'areas_conhecimento'";
    $result = $db->fetchOne($sql);
    
    if (!$result) {
        echo "A tabela 'areas_conhecimento' não existe no banco de dados.<br>";
        
        // Cria a tabela se não existir
        echo "Criando a tabela 'areas_conhecimento'...<br>";
        
        $sql = "CREATE TABLE areas_conhecimento (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(100) NOT NULL,
            descricao TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        $db->query($sql);
        
        echo "Tabela 'areas_conhecimento' criada com sucesso.<br>";
        
        // Insere algumas áreas de conhecimento
        $areas = [
            ['nome' => 'Ciências Exatas e da Terra'],
            ['nome' => 'Ciências Biológicas'],
            ['nome' => 'Engenharias'],
            ['nome' => 'Ciências da Saúde'],
            ['nome' => 'Ciências Agrárias'],
            ['nome' => 'Ciências Sociais Aplicadas'],
            ['nome' => 'Ciências Humanas'],
            ['nome' => 'Linguística, Letras e Artes'],
            ['nome' => 'Multidisciplinar']
        ];
        
        foreach ($areas as $area) {
            $db->insert('areas_conhecimento', $area);
        }
        
        echo "Áreas de conhecimento inseridas com sucesso.<br>";
    } else {
        echo "A tabela 'areas_conhecimento' existe no banco de dados.<br>";
    }
    
    // Conta o número de registros
    $sql = "SELECT COUNT(*) as total FROM areas_conhecimento";
    $result = $db->fetchOne($sql);
    
    echo "Total de registros na tabela 'areas_conhecimento': " . $result['total'] . "<br>";
    
    // Lista todos os registros
    $sql = "SELECT * FROM areas_conhecimento";
    $areas = $db->fetchAll($sql);
    
    echo "Registros da tabela 'areas_conhecimento':<br>";
    echo "<pre>";
    print_r($areas);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "Erro ao verificar a tabela 'areas_conhecimento': " . $e->getMessage();
}
