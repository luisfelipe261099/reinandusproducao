<?php
/**
 * Script para verificar a conexão com o banco de dados
 */

// Inicializa o sistema
require_once __DIR__ . '/includes/init.php';

// Instancia o banco de dados
$db = Database::getInstance();

try {
    // Verifica se a conexão está funcionando
    $sql = "SELECT 1 as teste";
    $result = $db->fetchOne($sql);
    
    if ($result && isset($result['teste']) && $result['teste'] == 1) {
        echo "<p style='color:green;'>Conexão com o banco de dados estabelecida com sucesso!</p>";
    } else {
        echo "<p style='color:red;'>Erro ao testar a conexão com o banco de dados.</p>";
    }
    
    // Verifica a tabela cursos
    $sql = "SHOW TABLES LIKE 'cursos'";
    $result = $db->fetchOne($sql);
    
    if ($result) {
        echo "<p style='color:green;'>Tabela 'cursos' encontrada.</p>";
        
        // Conta os registros
        $sql = "SELECT COUNT(*) as total FROM cursos";
        $result = $db->fetchOne($sql);
        
        echo "<p>Total de cursos: " . ($result['total'] ?? 0) . "</p>";
        
        // Lista alguns cursos
        $sql = "SELECT id, nome, codigo FROM cursos LIMIT 5";
        $cursos = $db->fetchAll($sql);
        
        if (!empty($cursos)) {
            echo "<p>Primeiros 5 cursos:</p>";
            echo "<ul>";
            foreach ($cursos as $curso) {
                echo "<li>ID: " . $curso['id'] . " - Nome: " . $curso['nome'] . " - Código: " . ($curso['codigo'] ?? 'N/A') . "</li>";
            }
            echo "</ul>";
        } else {
            echo "<p style='color:orange;'>Nenhum curso encontrado na tabela.</p>";
        }
        
        // Verifica as colunas da tabela
        $sql = "SHOW COLUMNS FROM cursos";
        $colunas = $db->fetchAll($sql);
        
        echo "<p>Colunas da tabela 'cursos':</p>";
        echo "<ul>";
        foreach ($colunas as $coluna) {
            echo "<li>" . $coluna['Field'] . " - Tipo: " . $coluna['Type'] . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color:red;'>Tabela 'cursos' não encontrada.</p>";
    }
    
    // Verifica a tabela polos
    $sql = "SHOW TABLES LIKE 'polos'";
    $result = $db->fetchOne($sql);
    
    if ($result) {
        echo "<p style='color:green;'>Tabela 'polos' encontrada.</p>";
        
        // Conta os registros
        $sql = "SELECT COUNT(*) as total FROM polos";
        $result = $db->fetchOne($sql);
        
        echo "<p>Total de polos: " . ($result['total'] ?? 0) . "</p>";
        
        // Lista alguns polos
        $sql = "SELECT id, nome FROM polos LIMIT 5";
        $polos = $db->fetchAll($sql);
        
        if (!empty($polos)) {
            echo "<p>Primeiros 5 polos:</p>";
            echo "<ul>";
            foreach ($polos as $polo) {
                echo "<li>ID: " . $polo['id'] . " - Nome: " . $polo['nome'] . "</li>";
            }
            echo "</ul>";
        } else {
            echo "<p style='color:orange;'>Nenhum polo encontrado na tabela.</p>";
        }
        
        // Verifica as colunas da tabela
        $sql = "SHOW COLUMNS FROM polos";
        $colunas = $db->fetchAll($sql);
        
        echo "<p>Colunas da tabela 'polos':</p>";
        echo "<ul>";
        foreach ($colunas as $coluna) {
            echo "<li>" . $coluna['Field'] . " - Tipo: " . $coluna['Type'] . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color:red;'>Tabela 'polos' não encontrada.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red;'>Erro: " . $e->getMessage() . "</p>";
}

echo "<p><a href='turmas.php?action=nova'>Voltar para a página de nova turma</a></p>";
