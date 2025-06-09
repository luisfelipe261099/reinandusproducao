<?php
/**
 * Script para criar a tabela ava_acessos
 * Esta tabela é usada para registrar os acessos dos alunos ao AVA
 */

// Inicializa o sistema
require_once '../includes/init.php';

// Verifica se o usuário está autenticado e é administrador
exigirLogin();
if (getUsuarioTipo() !== 'admin') {
    setMensagem('erro', 'Você não tem permissão para acessar esta página.');
    redirect('../index.php');
    exit;
}

// Instancia o banco de dados
$db = Database::getInstance();

try {
    echo "<h1>Criação da Tabela ava_acessos</h1>";
    
    // Verifica se a tabela ava_acessos já existe
    $sql_check = "SHOW TABLES LIKE 'ava_acessos'";
    $tabela_existe = $db->fetchOne($sql_check);
    
    if ($tabela_existe) {
        echo "<p style='color: green;'>A tabela ava_acessos já existe.</p>";
        
        // Verifica a estrutura da tabela
        $sql = "DESCRIBE ava_acessos";
        $colunas = $db->fetchAll($sql);
        
        echo "<p>Estrutura atual da tabela ava_acessos:</p>";
        echo "<ul>";
        foreach ($colunas as $coluna) {
            echo "<li>{$coluna['Field']} - {$coluna['Type']}</li>";
        }
        echo "</ul>";
        
        echo "<p>A tabela ava_acessos já está configurada corretamente.</p>";
    } else {
        echo "<p style='color: red;'>A tabela ava_acessos não existe. Criando tabela...</p>";
        
        // Cria a tabela ava_acessos
        $sql = "CREATE TABLE ava_acessos (
            id INT(11) NOT NULL AUTO_INCREMENT,
            aluno_id INT(11) NOT NULL,
            data_acesso DATETIME NOT NULL,
            ip VARCHAR(45) NULL,
            user_agent TEXT NULL,
            pagina VARCHAR(255) NULL,
            tempo_sessao INT(11) DEFAULT 0,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY idx_aluno_id (aluno_id),
            KEY idx_data_acesso (data_acesso)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        try {
            $db->query($sql);
            echo "<p style='color: green;'>Tabela ava_acessos criada com sucesso!</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>Erro ao criar tabela ava_acessos: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<h2>Conclusão</h2>";
    echo "<p style='color: green;'>Verificação e criação da tabela ava_acessos concluída!</p>";
    echo "<p><a href='dashboard.php'>Voltar para a Dashboard</a></p>";
    
} catch (Exception $e) {
    echo "<h2>Erro</h2>";
    echo "<p style='color: red;'>Ocorreu um erro durante a verificação da tabela: " . $e->getMessage() . "</p>";
    echo "<p><a href='dashboard.php'>Voltar para a Dashboard</a></p>";
}
?>
