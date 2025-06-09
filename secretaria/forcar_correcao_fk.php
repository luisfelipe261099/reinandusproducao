<?php
/**
 * Script para FORÇAR a correção da foreign key removendo TODAS as constraints problemáticas
 */

require_once 'includes/config.php';
require_once 'includes/Database.php';

echo "<h1>🔨 FORÇAR Correção da Foreign Key</h1>";
echo "<p><strong>Este script vai FORÇAR a remoção de todas as constraints problemáticas!</strong></p>";

try {
    $db = Database::getInstance();
    
    echo "<h2>1. Listando TODAS as constraints da tabela disciplinas...</h2>";
    
    $sql = "SELECT 
                CONSTRAINT_NAME,
                COLUMN_NAME,
                REFERENCED_TABLE_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'disciplinas' 
            AND REFERENCED_TABLE_NAME IS NOT NULL";
    
    $constraints = $db->fetchAll($sql);
    
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Constraint</th><th>Coluna</th><th>Tabela Referenciada</th><th>Ação</th></tr>";
    
    $constraints_removidas = 0;
    
    foreach ($constraints as $constraint) {
        echo "<tr>";
        echo "<td>" . $constraint['CONSTRAINT_NAME'] . "</td>";
        echo "<td>" . $constraint['COLUMN_NAME'] . "</td>";
        echo "<td>" . $constraint['REFERENCED_TABLE_NAME'] . "</td>";
        
        // Tenta remover TODAS as constraints
        try {
            $sql_drop = "ALTER TABLE disciplinas DROP FOREIGN KEY " . $constraint['CONSTRAINT_NAME'];
            $db->query($sql_drop);
            echo "<td style='color: green;'>✓ REMOVIDA</td>";
            $constraints_removidas++;
        } catch (Exception $e) {
            echo "<td style='color: red;'>❌ Erro: " . $e->getMessage() . "</td>";
        }
        
        echo "</tr>";
    }
    
    echo "</table>";
    echo "<p><strong>Total de constraints removidas: {$constraints_removidas}</strong></p>";
    
    echo "<h2>2. Criando tabela professores...</h2>";
    
    $sql = "CREATE TABLE IF NOT EXISTS professores (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(150) NOT NULL,
        email VARCHAR(100) UNIQUE,
        cpf VARCHAR(20) UNIQUE,
        telefone VARCHAR(20),
        formacao VARCHAR(100),
        titulacao ENUM('graduacao', 'especializacao', 'mestrado', 'doutorado', 'pos_doutorado') DEFAULT 'graduacao',
        area_atuacao VARCHAR(100),
        status ENUM('ativo', 'inativo') DEFAULT 'ativo',
        id_legado VARCHAR(50),
        observacoes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_status (status),
        INDEX idx_nome (nome)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $db->query($sql);
    echo "<p style='color: green;'>✓ Tabela professores criada/verificada.</p>";
    
    echo "<h2>3. Inserindo professores...</h2>";
    
    $sql = "INSERT IGNORE INTO professores (nome, email, status, formacao, titulacao) VALUES
            ('Professor Padrão', 'professor@faciencia.edu.br', 'ativo', 'Licenciatura em Pedagogia', 'mestrado'),
            ('Maria Silva Santos', 'maria.silva@faciencia.edu.br', 'ativo', 'Licenciatura em Matemática', 'especializacao'),
            ('João Carlos Oliveira', 'joao.carlos@faciencia.edu.br', 'ativo', 'Bacharelado em Administração', 'mestrado')";
    
    $db->query($sql);
    
    $sql = "SELECT COUNT(*) as total FROM professores";
    $total = $db->fetchOne($sql);
    echo "<p style='color: green;'>✓ Total de professores: " . $total['total'] . "</p>";
    
    echo "<h2>4. Limpando dados inválidos...</h2>";
    
    // Remove TODOS os professor_padrao_id inválidos
    $sql = "UPDATE disciplinas SET professor_padrao_id = NULL WHERE professor_padrao_id IS NOT NULL";
    $db->query($sql);
    echo "<p style='color: green;'>✓ Todos os professor_padrao_id foram limpos temporariamente.</p>";
    
    echo "<h2>5. Criando nova foreign key...</h2>";
    
    try {
        $sql = "ALTER TABLE disciplinas 
                ADD CONSTRAINT fk_disciplinas_professor_padrao_nova 
                FOREIGN KEY (professor_padrao_id) 
                REFERENCES professores(id) 
                ON DELETE SET NULL 
                ON UPDATE CASCADE";
        
        $db->query($sql);
        echo "<p style='color: green;'>✅ Nova foreign key criada com sucesso!</p>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Erro ao criar foreign key: " . $e->getMessage() . "</p>";
        
        // Tenta uma abordagem mais simples
        echo "<p>Tentando abordagem alternativa...</p>";
        
        try {
            $sql = "ALTER TABLE disciplinas 
                    ADD CONSTRAINT fk_disc_prof 
                    FOREIGN KEY (professor_padrao_id) 
                    REFERENCES professores(id)";
            
            $db->query($sql);
            echo "<p style='color: green;'>✅ Foreign key alternativa criada!</p>";
            
        } catch (Exception $e2) {
            echo "<p style='color: red;'>❌ Erro na abordagem alternativa: " . $e2->getMessage() . "</p>";
        }
    }
    
    echo "<h2>6. Teste final...</h2>";
    
    try {
        // Busca um curso e um professor
        $sql_curso = "SELECT id FROM cursos LIMIT 1";
        $curso = $db->fetchOne($sql_curso);
        
        $sql_professor = "SELECT id FROM professores LIMIT 1";
        $professor = $db->fetchOne($sql_professor);
        
        if ($curso && $professor) {
            echo "<p>Curso encontrado: ID " . $curso['id'] . "</p>";
            echo "<p>Professor encontrado: ID " . $professor['id'] . "</p>";
            
            // Tenta inserir uma disciplina
            $dados_teste = [
                'nome' => 'Teste FK - ' . date('H:i:s'),
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
                echo "<p style='color: gray;'>Disciplina de teste removida.</p>";
                
                echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
                echo "<h3 style='color: #155724; margin-top: 0;'>🎉 PROBLEMA RESOLVIDO!</h3>";
                echo "<p style='color: #155724;'>A foreign key foi corrigida com sucesso!</p>";
                echo "<p style='color: #155724;'><a href='disciplinas.php?action=nova' style='color: #155724;'><strong>👉 Cadastrar disciplina agora</strong></a></p>";
                echo "</div>";
                
            } else {
                echo "<p style='color: red;'>❌ Falha ao inserir disciplina de teste</p>";
            }
        } else {
            echo "<p style='color: orange;'>⚠️ Curso ou professor não encontrado para teste</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Erro no teste final: " . $e->getMessage() . "</p>";
    }
    
    echo "<h2>7. Status final das constraints...</h2>";
    
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
        echo "<p style='color: orange;'>⚠️ Nenhuma foreign key encontrada na tabela disciplinas</p>";
    } else {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>Constraint</th><th>Coluna</th><th>Tabela Referenciada</th><th>Coluna Referenciada</th></tr>";
        
        foreach ($constraints_finais as $constraint) {
            $cor = ($constraint['REFERENCED_TABLE_NAME'] === 'professores') ? 'green' : 'red';
            echo "<tr style='color: {$cor};'>";
            echo "<td>" . $constraint['CONSTRAINT_NAME'] . "</td>";
            echo "<td>" . $constraint['COLUMN_NAME'] . "</td>";
            echo "<td>" . $constraint['REFERENCED_TABLE_NAME'] . "</td>";
            echo "<td>" . $constraint['REFERENCED_COLUMN_NAME'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Erro Geral</h2>";
    echo "<p style='color: red;'><strong>Erro:</strong> " . $e->getMessage() . "</p>";
    echo "<p style='color: red;'><strong>Linha:</strong> " . $e->getLine() . "</p>";
}
?>
