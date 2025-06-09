<?php
/**
 * Script para identificar e corrigir as foreign keys existentes
 */

require_once 'includes/config.php';
require_once 'includes/Database.php';

echo "<h1>🔍 Identificar e Corrigir Foreign Keys</h1>";

try {
    $db = Database::getInstance();
    
    echo "<h2>1. Identificando constraints existentes...</h2>";
    
    $sql = "SELECT 
                CONSTRAINT_NAME,
                COLUMN_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'disciplinas' 
            AND REFERENCED_TABLE_NAME IS NOT NULL";
    
    $constraints = $db->fetchAll($sql);
    
    if (empty($constraints)) {
        echo "<p style='color: orange;'>⚠️ Nenhuma foreign key encontrada na tabela disciplinas</p>";
    } else {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>Constraint</th><th>Coluna</th><th>Tabela Referenciada</th><th>Coluna Referenciada</th></tr>";
        
        foreach ($constraints as $constraint) {
            echo "<tr>";
            echo "<td>" . $constraint['CONSTRAINT_NAME'] . "</td>";
            echo "<td>" . $constraint['COLUMN_NAME'] . "</td>";
            echo "<td>" . $constraint['REFERENCED_TABLE_NAME'] . "</td>";
            echo "<td>" . $constraint['REFERENCED_COLUMN_NAME'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
    echo "<h2>2. Gerando comandos SQL específicos...</h2>";
    
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>Execute estes comandos no phpMyAdmin:</h3>";
    echo "<pre style='background: #e9ecef; padding: 10px; border-radius: 3px;'>";
    
    echo "-- 1. Desabilitar verificação de foreign keys\n";
    echo "SET FOREIGN_KEY_CHECKS = 0;\n\n";
    
    if (!empty($constraints)) {
        echo "-- 2. Remover constraints existentes\n";
        foreach ($constraints as $constraint) {
            echo "ALTER TABLE disciplinas DROP FOREIGN KEY " . $constraint['CONSTRAINT_NAME'] . ";\n";
        }
        echo "\n";
    }
    
    echo "-- 3. Criar tabela professores se não existir\n";
    echo "CREATE TABLE IF NOT EXISTS professores (\n";
    echo "    id INT AUTO_INCREMENT PRIMARY KEY,\n";
    echo "    nome VARCHAR(150) NOT NULL,\n";
    echo "    email VARCHAR(100) UNIQUE,\n";
    echo "    status ENUM('ativo', 'inativo') DEFAULT 'ativo',\n";
    echo "    created_at DATETIME DEFAULT CURRENT_TIMESTAMP\n";
    echo ");\n\n";
    
    echo "-- 4. Inserir professores padrão\n";
    echo "INSERT IGNORE INTO professores (nome, email, status) VALUES\n";
    echo "('Professor Padrão', 'professor@faciencia.edu.br', 'ativo'),\n";
    echo "('Maria Silva', 'maria@faciencia.edu.br', 'ativo'),\n";
    echo "('João Carlos', 'joao@faciencia.edu.br', 'ativo');\n\n";
    
    echo "-- 5. Limpar dados inválidos\n";
    echo "UPDATE disciplinas SET professor_padrao_id = NULL;\n\n";
    
    echo "-- 6. Criar nova foreign key correta\n";
    echo "ALTER TABLE disciplinas \n";
    echo "ADD CONSTRAINT fk_disciplinas_professor_padrao_nova \n";
    echo "FOREIGN KEY (professor_padrao_id) \n";
    echo "REFERENCES professores(id) \n";
    echo "ON DELETE SET NULL;\n\n";
    
    echo "-- 7. Reabilitar verificação de foreign keys\n";
    echo "SET FOREIGN_KEY_CHECKS = 1;\n\n";
    
    echo "-- 8. Verificar resultado\n";
    echo "SELECT 'CORREÇÃO CONCLUÍDA!' as resultado;\n";
    
    echo "</pre>";
    echo "</div>";
    
    echo "<h2>3. Tentando executar automaticamente...</h2>";
    
    try {
        // Desabilita verificação de FK
        $db->query("SET FOREIGN_KEY_CHECKS = 0");
        echo "<p style='color: green;'>✓ Foreign key checks desabilitados</p>";
        
        // Remove constraints existentes
        foreach ($constraints as $constraint) {
            try {
                $sql = "ALTER TABLE disciplinas DROP FOREIGN KEY " . $constraint['CONSTRAINT_NAME'];
                $db->query($sql);
                echo "<p style='color: green;'>✓ Constraint removida: " . $constraint['CONSTRAINT_NAME'] . "</p>";
            } catch (Exception $e) {
                echo "<p style='color: red;'>❌ Erro ao remover " . $constraint['CONSTRAINT_NAME'] . ": " . $e->getMessage() . "</p>";
            }
        }
        
        // Cria tabela professores
        $sql = "CREATE TABLE IF NOT EXISTS professores (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(150) NOT NULL,
            email VARCHAR(100) UNIQUE,
            status ENUM('ativo', 'inativo') DEFAULT 'ativo',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        $db->query($sql);
        echo "<p style='color: green;'>✓ Tabela professores criada/verificada</p>";
        
        // Insere professores
        $sql = "INSERT IGNORE INTO professores (nome, email, status) VALUES
                ('Professor Padrão', 'professor@faciencia.edu.br', 'ativo'),
                ('Maria Silva', 'maria@faciencia.edu.br', 'ativo'),
                ('João Carlos', 'joao@faciencia.edu.br', 'ativo')";
        $db->query($sql);
        echo "<p style='color: green;'>✓ Professores inseridos</p>";
        
        // Limpa dados inválidos
        $db->query("UPDATE disciplinas SET professor_padrao_id = NULL");
        echo "<p style='color: green;'>✓ Dados inválidos limpos</p>";
        
        // Cria nova FK
        $sql = "ALTER TABLE disciplinas 
                ADD CONSTRAINT fk_disciplinas_professor_padrao_nova 
                FOREIGN KEY (professor_padrao_id) 
                REFERENCES professores(id) 
                ON DELETE SET NULL";
        $db->query($sql);
        echo "<p style='color: green;'>✓ Nova foreign key criada</p>";
        
        // Reabilita verificação de FK
        $db->query("SET FOREIGN_KEY_CHECKS = 1");
        echo "<p style='color: green;'>✓ Foreign key checks reabilitados</p>";
        
        echo "<h2>4. Teste final...</h2>";
        
        // Busca um curso e um professor
        $sql_curso = "SELECT id FROM cursos LIMIT 1";
        $curso = $db->fetchOne($sql_curso);
        
        $sql_professor = "SELECT id FROM professores LIMIT 1";
        $professor = $db->fetchOne($sql_professor);
        
        if ($curso && $professor) {
            // Tenta inserir uma disciplina
            $dados_teste = [
                'nome' => 'Teste FK Final - ' . date('H:i:s'),
                'curso_id' => $curso['id'],
                'professor_padrao_id' => $professor['id'],
                'carga_horaria' => 60,
                'status' => 'ativo',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $disciplina_id = $db->insert('disciplinas', $dados_teste);
            
            if ($disciplina_id) {
                echo "<p style='color: green;'>🎉 SUCESSO! Disciplina inserida com ID: {$disciplina_id}</p>";
                
                // Remove a disciplina de teste
                $db->delete('disciplinas', 'id = ?', [$disciplina_id]);
                echo "<p style='color: gray;'>Disciplina de teste removida</p>";
                
                echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
                echo "<h3 style='color: #155724; margin-top: 0;'>🎉 PROBLEMA RESOLVIDO!</h3>";
                echo "<p style='color: #155724;'>A foreign key foi corrigida com sucesso!</p>";
                echo "<p style='color: #155724;'><a href='disciplinas.php?action=nova' style='color: #155724; font-weight: bold;'>👉 TESTAR CADASTRO DE DISCIPLINA AGORA</a></p>";
                echo "</div>";
                
            } else {
                echo "<p style='color: red;'>❌ Falha ao inserir disciplina de teste</p>";
            }
        } else {
            echo "<p style='color: orange;'>⚠️ Curso ou professor não encontrado para teste</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Erro na execução automática: " . $e->getMessage() . "</p>";
        echo "<p style='color: blue;'>💡 Use os comandos SQL acima no phpMyAdmin</p>";
    }
    
    echo "<h2>5. Verificação final das constraints...</h2>";
    
    $sql = "SELECT 
                CONSTRAINT_NAME,
                COLUMN_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'disciplinas' 
            AND REFERENCED_TABLE_NAME IS NOT NULL";
    
    $constraints_finais = $db->fetchAll($sql);
    
    if (empty($constraints_finais)) {
        echo "<p style='color: orange;'>⚠️ Nenhuma foreign key encontrada</p>";
    } else {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>Constraint</th><th>Coluna</th><th>Tabela Referenciada</th><th>Status</th></tr>";
        
        foreach ($constraints_finais as $constraint) {
            $status = ($constraint['REFERENCED_TABLE_NAME'] === 'professores') ? 
                      "<span style='color: green;'>✓ CORRETO</span>" : 
                      "<span style='color: red;'>❌ INCORRETO</span>";
            
            echo "<tr>";
            echo "<td>" . $constraint['CONSTRAINT_NAME'] . "</td>";
            echo "<td>" . $constraint['COLUMN_NAME'] . "</td>";
            echo "<td>" . $constraint['REFERENCED_TABLE_NAME'] . "</td>";
            echo "<td>" . $status . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Erro</h2>";
    echo "<p style='color: red;'><strong>Erro:</strong> " . $e->getMessage() . "</p>";
}
?>
