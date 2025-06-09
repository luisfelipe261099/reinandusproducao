<?php
/**
 * Script para adicionar colunas faltantes à tabela ava_progresso
 * Este script deve ser executado uma vez para corrigir a estrutura da tabela
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
    echo "<h1>Correção da Tabela ava_progresso</h1>";
    
    // Verifica se a tabela ava_progresso existe
    $sql_check = "SHOW TABLES LIKE 'ava_progresso'";
    $tabela_existe = $db->fetchOne($sql_check);
    
    if (!$tabela_existe) {
        echo "<p style='color: red;'>A tabela ava_progresso não existe. Criando tabela...</p>";
        
        // Cria a tabela ava_progresso
        $sql = "CREATE TABLE IF NOT EXISTS ava_progresso (
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
        
        try {
            $db->query($sql);
            echo "<p style='color: green;'>Tabela ava_progresso criada com sucesso!</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>Erro ao criar tabela ava_progresso: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color: green;'>A tabela ava_progresso existe.</p>";
        
        // Verifica as colunas da tabela ava_progresso
        $sql = "DESCRIBE ava_progresso";
        $colunas = $db->fetchAll($sql);
        
        $colunas_existentes = [];
        foreach ($colunas as $coluna) {
            $colunas_existentes[] = $coluna['Field'];
        }
        
        echo "<p>Colunas existentes: " . implode(', ', $colunas_existentes) . "</p>";
        
        // Lista de colunas que devem existir na tabela ava_progresso
        $colunas_necessarias = [
            'data_inicio' => "ALTER TABLE ava_progresso ADD COLUMN data_inicio DATETIME NULL AFTER tempo_gasto",
            'data_conclusao' => "ALTER TABLE ava_progresso ADD COLUMN data_conclusao DATETIME NULL AFTER data_inicio",
            'pontuacao' => "ALTER TABLE ava_progresso ADD COLUMN pontuacao INT(11) NULL AFTER concluido",
            'tempo_gasto' => "ALTER TABLE ava_progresso ADD COLUMN tempo_gasto INT(11) NULL AFTER pontuacao"
        ];
        
        // Verifica quais colunas estão faltando
        $colunas_faltantes = array_diff(array_keys($colunas_necessarias), $colunas_existentes);
        
        if (empty($colunas_faltantes)) {
            echo "<p style='color: green;'>Todas as colunas necessárias já existem na tabela ava_progresso.</p>";
        } else {
            echo "<p>Colunas faltantes: " . implode(', ', $colunas_faltantes) . "</p>";
            
            // Adiciona as colunas faltantes
            foreach ($colunas_faltantes as $coluna) {
                $sql = $colunas_necessarias[$coluna];
                
                try {
                    $db->query($sql);
                    echo "<p style='color: green;'>Coluna $coluna adicionada com sucesso!</p>";
                } catch (Exception $e) {
                    echo "<p style='color: red;'>Erro ao adicionar coluna $coluna: " . $e->getMessage() . "</p>";
                }
            }
        }
    }
    
    echo "<h2>Conclusão</h2>";
    echo "<p style='color: green;'>Verificação e correção da tabela ava_progresso concluída!</p>";
    echo "<p><a href='../index.php'>Voltar para a página inicial</a></p>";
    
} catch (Exception $e) {
    echo "<h2>Erro</h2>";
    echo "<p style='color: red;'>Ocorreu um erro durante a verificação da tabela: " . $e->getMessage() . "</p>";
    echo "<p><a href='../index.php'>Voltar para a página inicial</a></p>";
}
?>
