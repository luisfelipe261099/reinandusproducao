<?php
/**
 * Script para corrigir a estrutura da tabela ava_progresso
 * Este script cria a tabela com a estrutura correta se ela não existir
 * ou a recria completamente se existir mas estiver com problemas
 */

// Inicializa o sistema
require_once '../includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Instancia o banco de dados
$db = Database::getInstance();

try {
    // Verifica se a tabela ava_progresso existe
    $sql_check = "SHOW TABLES LIKE 'ava_progresso'";
    $tabela_existe = $db->fetchOne($sql_check);
    
    if ($tabela_existe) {
        // Faz backup dos dados existentes
        $sql = "SELECT * FROM ava_progresso";
        $dados_existentes = $db->fetchAll($sql);
        
        // Remove a tabela existente
        $sql = "DROP TABLE ava_progresso";
        $db->query($sql);
        
        echo "<p>Tabela ava_progresso removida para recriação.</p>";
    }
    
    // Cria a tabela ava_progresso com a estrutura correta
    $sql = "CREATE TABLE ava_progresso (
        id INT(11) NOT NULL AUTO_INCREMENT,
        matricula_id INT(11) NOT NULL,
        aula_id INT(11) NOT NULL,
        concluido TINYINT(1) NOT NULL DEFAULT 0,
        pontuacao INT(11) NULL,
        tempo_gasto INT(11) NULL,
        data_inicio DATETIME NULL,
        data_conclusao DATETIME NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY idx_matricula_id (matricula_id),
        KEY idx_aula_id (aula_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $db->query($sql);
    echo "<p>Tabela ava_progresso criada com sucesso!</p>";
    
    // Restaura os dados se existiam anteriormente
    if (isset($dados_existentes) && !empty($dados_existentes)) {
        $count = 0;
        foreach ($dados_existentes as $dado) {
            $dados_insert = [
                'matricula_id' => $dado['matricula_id'],
                'aula_id' => $dado['aula_id'],
                'concluido' => $dado['concluido'],
                'created_at' => $dado['created_at'],
                'updated_at' => $dado['updated_at']
            ];
            
            // Adiciona campos opcionais se existirem nos dados originais
            if (isset($dado['pontuacao'])) {
                $dados_insert['pontuacao'] = $dado['pontuacao'];
            }
            
            if (isset($dado['tempo_gasto'])) {
                $dados_insert['tempo_gasto'] = $dado['tempo_gasto'];
            }
            
            $db->insert('ava_progresso', $dados_insert);
            $count++;
        }
        
        echo "<p>$count registros restaurados na tabela ava_progresso.</p>";
    }
    
    echo "<p>Correção da tabela ava_progresso concluída com sucesso!</p>";
    echo "<p><a href='dashboard.php'>Voltar para a Dashboard</a></p>";
    
} catch (Exception $e) {
    echo "<p>Erro ao corrigir a tabela ava_progresso: " . $e->getMessage() . "</p>";
    echo "<p><a href='dashboard.php'>Voltar para a Dashboard</a></p>";
}
?>
