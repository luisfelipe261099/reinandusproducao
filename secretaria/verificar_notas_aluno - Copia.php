<?php
/**
 * Script para verificar e corrigir as notas de um aluno específico
 */

// Inicializa o sistema
require_once __DIR__ . '/includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica se o usuário tem permissão para acessar o módulo de notas
exigirPermissao('notas');

// Instancia o banco de dados
$db = Database::getInstance();

// Aluno específico para verificar
$aluno_id = 24346;

// Busca os dados do aluno
$sql = "SELECT * FROM alunos WHERE id = ?";
$aluno = $db->fetchOne($sql, [$aluno_id]);

if (!$aluno) {
    die("Aluno não encontrado.");
}

echo "<h1>Verificação de Notas - Aluno: {$aluno['nome']}</h1>";

// Busca as matrículas do aluno
$sql = "SELECT m.*, c.nome as curso_nome, t.nome as turma_nome
        FROM matriculas m
        LEFT JOIN cursos c ON m.curso_id = c.id
        LEFT JOIN turmas t ON m.turma_id = t.id
        WHERE m.aluno_id = ? AND m.status = 'ativo'
        ORDER BY m.created_at DESC";
$matriculas = $db->fetchAll($sql, [$aluno_id]) ?: [];

echo "<p>Total de matrículas: " . count($matriculas) . "</p>";

// Para cada matrícula, verifica as notas
foreach ($matriculas as $matricula) {
    echo "<div style='margin: 20px 0; padding: 15px; border: 1px solid #ccc; border-radius: 5px;'>";
    echo "<h2>Matrícula ID: {$matricula['id']}</h2>";
    echo "<p>Curso: {$matricula['curso_nome']}</p>";
    echo "<p>Turma: {$matricula['turma_nome']}</p>";
    
    // Busca as notas desta matrícula
    $sql = "SELECT COUNT(*) as total FROM notas_disciplinas WHERE matricula_id = ?";
    $resultado = $db->fetchOne($sql, [$matricula['id']]);
    $total_notas = $resultado['total'] ?? 0;
    
    echo "<p>Total de notas encontradas: {$total_notas}</p>";
    
    if ($total_notas > 0) {
        // Lista as notas
        $sql = "SELECT nd.*, d.nome as disciplina_nome, d.codigo as disciplina_codigo
                FROM notas_disciplinas nd
                LEFT JOIN disciplinas d ON nd.disciplina_id = d.id
                WHERE nd.matricula_id = ?
                ORDER BY d.nome ASC";
        $notas = $db->fetchAll($sql, [$matricula['id']]) ?: [];
        
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr>
                <th>ID</th>
                <th>Disciplina</th>
                <th>Nota</th>
                <th>Frequência</th>
                <th>Situação</th>
                <th>Data Lançamento</th>
              </tr>";
        
        foreach ($notas as $nota) {
            echo "<tr>";
            echo "<td>{$nota['id']}</td>";
            echo "<td>" . ($nota['disciplina_nome'] ?? "ID: {$nota['disciplina_id']}") . "</td>";
            echo "<td>{$nota['nota']}</td>";
            echo "<td>{$nota['frequencia']}%</td>";
            echo "<td>{$nota['situacao']}</td>";
            echo "<td>" . ($nota['data_lancamento'] ? date('d/m/Y', strtotime($nota['data_lancamento'])) : '-') . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p style='color: red;'>Nenhuma nota encontrada para esta matrícula.</p>";
        
        // Verifica se existem notas sem matrícula para este aluno
        $sql = "SELECT nd.id, nd.disciplina_id, nd.nota, nd.frequencia, nd.situacao,
                       d.nome as disciplina_nome, d.codigo as disciplina_codigo
                FROM notas_disciplinas nd
                LEFT JOIN disciplinas d ON nd.disciplina_id = d.id
                WHERE (nd.matricula_id IS NULL OR nd.matricula_id = 0)
                ORDER BY d.nome ASC";
        $notas_sem_matricula = $db->fetchAll($sql) ?: [];
        
        if (!empty($notas_sem_matricula)) {
            echo "<p>Encontradas " . count($notas_sem_matricula) . " notas sem matrícula que poderiam ser associadas:</p>";
            
            echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr>
                    <th>ID</th>
                    <th>Disciplina</th>
                    <th>Nota</th>
                    <th>Frequência</th>
                    <th>Situação</th>
                    <th>Ação</th>
                  </tr>";
            
            foreach ($notas_sem_matricula as $nota) {
                echo "<tr>";
                echo "<td>{$nota['id']}</td>";
                echo "<td>" . ($nota['disciplina_nome'] ?? "ID: {$nota['disciplina_id']}") . "</td>";
                echo "<td>{$nota['nota']}</td>";
                echo "<td>{$nota['frequencia']}%</td>";
                echo "<td>{$nota['situacao']}</td>";
                echo "<td><a href='?associar_nota={$nota['id']}&matricula_id={$matricula['id']}'>Associar</a></td>";
                echo "</tr>";
            }
            
            echo "</table>";
        }
    }
    
    echo "</div>";
}

// Processa a associação de nota
if (isset($_GET['associar_nota']) && isset($_GET['matricula_id'])) {
    $nota_id = (int)$_GET['associar_nota'];
    $matricula_id = (int)$_GET['matricula_id'];
    
    $sql = "UPDATE notas_disciplinas SET matricula_id = ? WHERE id = ?";
    try {
        $db->query($sql, [$matricula_id, $nota_id]);
        echo "<div style='background-color: #d4edda; color: #155724; padding: 15px; margin: 15px 0; border-radius: 5px;'>";
        echo "Nota ID {$nota_id} associada com sucesso à matrícula ID {$matricula_id}";
        echo "</div>";
        echo "<p><a href='verificar_notas_aluno.php'>Atualizar página</a></p>";
    } catch (Exception $e) {
        echo "<div style='background-color: #f8d7da; color: #721c24; padding: 15px; margin: 15px 0; border-radius: 5px;'>";
        echo "Erro ao associar nota: " . $e->getMessage();
        echo "</div>";
    }
}

// Adiciona botão para voltar ao boletim
echo "<p><a href='notas.php?action=boletim&aluno_id={$aluno_id}' style='display: inline-block; padding: 10px 15px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Ver Boletim</a></p>";
?>
