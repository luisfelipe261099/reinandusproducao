<?php
/**
 * Script para criar a tabela de matrículas
 */

// Inicializa o sistema
require_once __DIR__ . '/includes/init.php';

// Instancia o banco de dados
$db = Database::getInstance();

try {
    // Verifica se a tabela matriculas existe
    $sql = "SHOW TABLES LIKE 'matriculas'";
    $result = $db->fetchOne($sql);
    
    if (!$result) {
        echo "A tabela 'matriculas' não existe no banco de dados.<br>";
        
        // Cria a tabela se não existir
        echo "Criando a tabela 'matriculas'...<br>";
        
        $sql = "CREATE TABLE matriculas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            aluno_id INT NOT NULL,
            curso_id INT NOT NULL,
            turma_id INT,
            data_inicio DATE,
            data_fim DATE,
            status ENUM('pendente', 'ativo', 'cancelado', 'concluido', 'trancado') DEFAULT 'pendente',
            forma_pagamento VARCHAR(50),
            valor_total DECIMAL(10,2) DEFAULT 0.00,
            observacoes TEXT,
            id_legado VARCHAR(50),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (aluno_id),
            INDEX (curso_id),
            INDEX (turma_id),
            INDEX (status)
        )";
        
        $db->query($sql);
        
        echo "Tabela 'matriculas' criada com sucesso.<br>";
        
        // Insere algumas matrículas de exemplo
        echo "Inserindo matrículas de exemplo...<br>";
        
        // Verifica se existem alunos e cursos
        $sql = "SELECT COUNT(*) as total FROM alunos";
        $result = $db->fetchOne($sql);
        $total_alunos = $result['total'] ?? 0;
        
        $sql = "SELECT COUNT(*) as total FROM cursos";
        $result = $db->fetchOne($sql);
        $total_cursos = $result['total'] ?? 0;
        
        if ($total_alunos > 0 && $total_cursos > 0) {
            // Busca os IDs dos alunos
            $sql = "SELECT id FROM alunos LIMIT 10";
            $alunos = $db->fetchAll($sql);
            
            // Busca os IDs dos cursos
            $sql = "SELECT id FROM cursos";
            $cursos = $db->fetchAll($sql);
            
            // Cria matrículas para os últimos 6 meses
            $status = ['pendente', 'ativo', 'cancelado', 'concluido', 'trancado'];
            
            for ($i = 0; $i < 30; $i++) {
                $aluno_id = $alunos[array_rand($alunos)]['id'];
                $curso_id = $cursos[array_rand($cursos)]['id'];
                $status_matricula = $status[array_rand($status)];
                
                // Data aleatória nos últimos 6 meses
                $dias_atras = rand(0, 180);
                $data = date('Y-m-d H:i:s', strtotime("-{$dias_atras} days"));
                
                $matricula = [
                    'aluno_id' => $aluno_id,
                    'curso_id' => $curso_id,
                    'status' => $status_matricula,
                    'valor_total' => rand(500, 2000),
                    'created_at' => $data,
                    'updated_at' => $data
                ];
                
                $db->insert('matriculas', $matricula);
                echo "Matrícula inserida para o aluno ID {$aluno_id} no curso ID {$curso_id}.<br>";
            }
            
            echo "Matrículas de exemplo inseridas com sucesso.<br>";
        } else {
            echo "Não foi possível inserir matrículas de exemplo pois não existem alunos ou cursos cadastrados.<br>";
        }
    } else {
        echo "A tabela 'matriculas' já existe no banco de dados.<br>";
        
        // Conta o número de registros
        $sql = "SELECT COUNT(*) as total FROM matriculas";
        $result = $db->fetchOne($sql);
        
        echo "Total de registros na tabela 'matriculas': " . $result['total'] . "<br>";
        
        if ($result['total'] == 0) {
            echo "A tabela 'matriculas' está vazia. Inserindo matrículas de exemplo...<br>";
            
            // Verifica se existem alunos e cursos
            $sql = "SELECT COUNT(*) as total FROM alunos";
            $result = $db->fetchOne($sql);
            $total_alunos = $result['total'] ?? 0;
            
            $sql = "SELECT COUNT(*) as total FROM cursos";
            $result = $db->fetchOne($sql);
            $total_cursos = $result['total'] ?? 0;
            
            if ($total_alunos > 0 && $total_cursos > 0) {
                // Busca os IDs dos alunos
                $sql = "SELECT id FROM alunos LIMIT 10";
                $alunos = $db->fetchAll($sql);
                
                // Busca os IDs dos cursos
                $sql = "SELECT id FROM cursos";
                $cursos = $db->fetchAll($sql);
                
                // Cria matrículas para os últimos 6 meses
                $status = ['pendente', 'ativo', 'cancelado', 'concluido', 'trancado'];
                
                for ($i = 0; $i < 30; $i++) {
                    $aluno_id = $alunos[array_rand($alunos)]['id'];
                    $curso_id = $cursos[array_rand($cursos)]['id'];
                    $status_matricula = $status[array_rand($status)];
                    
                    // Data aleatória nos últimos 6 meses
                    $dias_atras = rand(0, 180);
                    $data = date('Y-m-d H:i:s', strtotime("-{$dias_atras} days"));
                    
                    $matricula = [
                        'aluno_id' => $aluno_id,
                        'curso_id' => $curso_id,
                        'status' => $status_matricula,
                        'valor_total' => rand(500, 2000),
                        'created_at' => $data,
                        'updated_at' => $data
                    ];
                    
                    $db->insert('matriculas', $matricula);
                    echo "Matrícula inserida para o aluno ID {$aluno_id} no curso ID {$curso_id}.<br>";
                }
                
                echo "Matrículas de exemplo inseridas com sucesso.<br>";
            } else {
                echo "Não foi possível inserir matrículas de exemplo pois não existem alunos ou cursos cadastrados.<br>";
            }
        }
    }
    
    echo "<br><a href='cursos.php' class='btn-primary'>Voltar para a página de cursos</a>";
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
