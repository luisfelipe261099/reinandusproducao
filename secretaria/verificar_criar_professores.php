<?php
/**
 * Script para verificar e criar a tabela de professores
 */

// Carrega as configurações
require_once 'config/config.php';
require_once 'includes/Database.php';

try {
    // Conecta ao banco de dados
    $db = Database::getInstance();
    
    // Verifica se a tabela professores existe
    $sql = "SHOW TABLES LIKE 'professores'";
    $result = $db->fetchOne($sql);
    
    if (!$result) {
        echo "A tabela 'professores' não existe no banco de dados.<br>";
        
        // Cria a tabela se não existir
        echo "Criando a tabela 'professores'...<br>";
        
        $sql = "CREATE TABLE professores (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(150) NOT NULL,
            email VARCHAR(100),
            cpf VARCHAR(20),
            telefone VARCHAR(20),
            formacao VARCHAR(100),
            titulacao ENUM('graduacao', 'especializacao', 'mestrado', 'doutorado', 'pos_doutorado'),
            area_atuacao VARCHAR(100),
            status ENUM('ativo', 'inativo') DEFAULT 'ativo',
            id_legado VARCHAR(50),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (status)
        )";
        
        $db->query($sql);
        echo "Tabela 'professores' criada com sucesso!<br>";
        
        // Insere alguns professores de exemplo
        echo "Inserindo professores de exemplo...<br>";
        
        $professores = [
            [
                'nome' => 'Prof. João Silva',
                'email' => 'joao.silva@exemplo.com',
                'formacao' => 'Ciência da Computação',
                'titulacao' => 'doutorado',
                'area_atuacao' => 'Inteligência Artificial',
                'status' => 'ativo'
            ],
            [
                'nome' => 'Profa. Maria Santos',
                'email' => 'maria.santos@exemplo.com',
                'formacao' => 'Matemática',
                'titulacao' => 'mestrado',
                'area_atuacao' => 'Estatística',
                'status' => 'ativo'
            ],
            [
                'nome' => 'Prof. Carlos Oliveira',
                'email' => 'carlos.oliveira@exemplo.com',
                'formacao' => 'Administração',
                'titulacao' => 'mestrado',
                'area_atuacao' => 'Gestão Empresarial',
                'status' => 'ativo'
            ]
        ];
        
        foreach ($professores as $professor) {
            $db->insert('professores', $professor);
            echo "Professor '{$professor['nome']}' inserido.<br>";
        }
        
        echo "<br>Processo concluído com sucesso!<br>";
    } else {
        echo "A tabela 'professores' já existe no banco de dados.<br>";
        
        // Verifica quantos professores existem
        $sql = "SELECT COUNT(*) as total FROM professores";
        $result = $db->fetchOne($sql);
        echo "Total de professores cadastrados: " . $result['total'] . "<br>";
    }
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "<br>";
}
?>
