<?php
/**
 * Script para adicionar um aluno de exemplo ao banco de dados
 * Este script é útil quando não há alunos cadastrados no sistema
 */

// Inicializa o sistema
require_once '../includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Instancia o banco de dados
$db = Database::getInstance();

try {
    echo "<h1>Adição de Aluno de Exemplo</h1>";
    
    // Verifica se já existem alunos no sistema
    $sql = "SELECT COUNT(*) as total FROM alunos";
    $resultado = $db->fetchOne($sql);
    
    if ($resultado['total'] > 0) {
        echo "<p style='color: green;'>Já existem " . $resultado['total'] . " alunos cadastrados no sistema.</p>";
        
        // Lista os primeiros 5 alunos
        $sql = "SELECT id, nome, email, cpf FROM alunos ORDER BY id LIMIT 5";
        $alunos = $db->fetchAll($sql);
        
        echo "<p>Alunos disponíveis para visualização:</p>";
        echo "<ul>";
        foreach ($alunos as $aluno) {
            echo "<li><strong>" . htmlspecialchars($aluno['nome']) . "</strong> (ID: " . $aluno['id'] . ") - " . 
                 htmlspecialchars($aluno['email']) . " - CPF: " . htmlspecialchars($aluno['cpf']) . " - " .
                 "<a href='aluno_visualizar.php?id=" . $aluno['id'] . "'>Visualizar</a></li>";
        }
        echo "</ul>";
        
        echo "<p>Você pode acessar qualquer um destes alunos para visualizar seus detalhes e progresso.</p>";
    } else {
        echo "<p style='color: red;'>Não existem alunos cadastrados no sistema. Adicionando aluno de exemplo...</p>";
        
        // Obtém o ID do polo atual
        $polo_id = $_SESSION['usuario_polo_id'] ?? 1;
        
        // Dados do aluno de exemplo
        $dados_aluno = [
            'nome' => 'Aluno Exemplo',
            'email' => 'aluno.exemplo@email.com',
            'cpf' => '123.456.789-00',
            'rg' => '12.345.678-9',
            'data_nascimento' => '1990-01-01',
            'telefone' => '(11) 98765-4321',
            'endereco' => 'Rua Exemplo, 123',
            'bairro' => 'Centro',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'cep' => '01234-567',
            'polo_id' => $polo_id,
            'status' => 'ativo',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Insere o aluno no banco de dados
        $aluno_id = $db->insert('alunos', $dados_aluno);
        
        if ($aluno_id) {
            echo "<p style='color: green;'>Aluno de exemplo adicionado com sucesso! ID: " . $aluno_id . "</p>";
            
            // Verifica se existem cursos no sistema
            $sql = "SELECT COUNT(*) as total FROM ava_cursos";
            $resultado = $db->fetchOne($sql);
            
            if ($resultado['total'] > 0) {
                // Busca o primeiro curso disponível
                $sql = "SELECT id FROM ava_cursos ORDER BY id LIMIT 1";
                $curso = $db->fetchOne($sql);
                
                if ($curso) {
                    // Dados da matrícula
                    $dados_matricula = [
                        'aluno_id' => $aluno_id,
                        'curso_id' => $curso['id'],
                        'status' => 'ativo',
                        'data_matricula' => date('Y-m-d'),
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                    
                    // Insere a matrícula no banco de dados
                    $matricula_id = $db->insert('ava_matriculas', $dados_matricula);
                    
                    if ($matricula_id) {
                        echo "<p style='color: green;'>Matrícula criada com sucesso! ID: " . $matricula_id . "</p>";
                    } else {
                        echo "<p style='color: orange;'>Não foi possível criar uma matrícula para o aluno.</p>";
                    }
                }
            } else {
                echo "<p style='color: orange;'>Não existem cursos cadastrados no sistema. Não foi possível criar uma matrícula para o aluno.</p>";
            }
            
            echo "<p>Você pode acessar o aluno de exemplo através do link abaixo:</p>";
            echo "<p><a href='aluno_visualizar.php?id=" . $aluno_id . "' class='btn btn-primary'>Visualizar Aluno de Exemplo</a></p>";
        } else {
            echo "<p style='color: red;'>Erro ao adicionar aluno de exemplo.</p>";
        }
    }
    
    echo "<h2>Conclusão</h2>";
    echo "<p><a href='dashboard.php'>Voltar para a Dashboard</a></p>";
    
} catch (Exception $e) {
    echo "<h2>Erro</h2>";
    echo "<p style='color: red;'>Ocorreu um erro: " . $e->getMessage() . "</p>";
    echo "<p><a href='dashboard.php'>Voltar para a Dashboard</a></p>";
}
?>
