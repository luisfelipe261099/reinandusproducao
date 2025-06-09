<?php
/**
 * Script para adicionar colunas faltantes à tabela ava_aulas
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
    echo "<h1>Correção da Tabela ava_aulas</h1>";
    
    // Verifica se a tabela ava_aulas existe
    $sql_check = "SHOW TABLES LIKE 'ava_aulas'";
    $tabela_existe = $db->fetchOne($sql_check);
    
    if (!$tabela_existe) {
        echo "<p style='color: red;'>A tabela ava_aulas não existe. Execute o script de criação de tabelas primeiro.</p>";
    } else {
        echo "<p style='color: green;'>A tabela ava_aulas existe.</p>";
        
        // Verifica as colunas da tabela ava_aulas
        $sql = "DESCRIBE ava_aulas";
        $colunas = $db->fetchAll($sql);
        
        $colunas_existentes = [];
        foreach ($colunas as $coluna) {
            $colunas_existentes[] = $coluna['Field'];
        }
        
        echo "<p>Colunas existentes: " . implode(', ', $colunas_existentes) . "</p>";
        
        // Lista de colunas que devem existir na tabela ava_aulas
        $colunas_necessarias = [
            'url_video' => "ALTER TABLE ava_aulas ADD COLUMN url_video VARCHAR(255) NULL AFTER conteudo",
            'arquivo' => "ALTER TABLE ava_aulas ADD COLUMN arquivo VARCHAR(255) NULL AFTER url_video",
            'duracao' => "ALTER TABLE ava_aulas ADD COLUMN duracao INT(11) NULL AFTER arquivo"
        ];
        
        // Verifica quais colunas estão faltando
        $colunas_faltantes = array_diff(array_keys($colunas_necessarias), $colunas_existentes);
        
        if (empty($colunas_faltantes)) {
            echo "<p style='color: green;'>Todas as colunas necessárias já existem na tabela ava_aulas.</p>";
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
    echo "<p style='color: green;'>Verificação e correção da tabela ava_aulas concluída!</p>";
    echo "<p><a href='../index.php'>Voltar para a página inicial</a></p>";
    
} catch (Exception $e) {
    echo "<h2>Erro</h2>";
    echo "<p style='color: red;'>Ocorreu um erro durante a verificação da tabela: " . $e->getMessage() . "</p>";
    echo "<p><a href='../index.php'>Voltar para a página inicial</a></p>";
}
?>
