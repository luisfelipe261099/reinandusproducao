<?php
/**
 * Script para verificar as notas do aluno FABIO PERICLES RIBEIRO JOSE
 */

// Inicializa o sistema
require_once __DIR__ . '/includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica se o usuário tem permissão para acessar o módulo de notas
exigirPermissao('notas');

// Instancia o banco de dados
$db = Database::getInstance();

// ID do aluno FABIO PERICLES RIBEIRO JOSE
$aluno_id = 25489;

// Busca os dados do aluno
$sql = "SELECT * FROM alunos WHERE id = ?";
$aluno = $db->fetchOne($sql, [$aluno_id]);

if (!$aluno) {
    die("Aluno não encontrado.");
}

echo "<h1>Verificação de Notas - Aluno: {$aluno['nome']} (ID: {$aluno_id})</h1>";

// Busca as matrículas do aluno
$sql = "SELECT m.*, c.nome as curso_nome, t.nome as turma_nome
        FROM matriculas m
        LEFT JOIN cursos c ON m.curso_id = c.id
        LEFT JOIN turmas t ON m.turma_id = t.id
        WHERE m.aluno_id = ?
        ORDER BY m.created_at DESC";
$matriculas = $db->fetchAll($sql, [$aluno_id]) ?: [];

echo "<p>Total de matrículas encontradas: " . count($matriculas) . "</p>";

// Para cada matrícula, verifica as notas
foreach ($matriculas as $matricula) {
    echo "<div style='margin: 20px 0; padding: 15px; border: 1px solid #ccc; border-radius: 5px;'>";
    echo "<h2>Matrícula ID: {$matricula['id']}</h2>";
    echo "<p>Curso: " . ($matricula['curso_nome'] ?? 'Não informado') . "</p>";
    echo "<p>Turma: " . ($matricula['turma_nome'] ?? 'Não informada') . "</p>";
    echo "<p>Status: {$matricula['status']}</p>";
    
    // Busca as notas desta matrícula diretamente na tabela notas_disciplinas
    $sql = "SELECT * FROM notas_disciplinas WHERE matricula_id = ?";
    $notas_raw = $db->fetchAll($sql, [$matricula['id']]) ?: [];
    
    echo "<p>Total de notas encontradas diretamente na tabela: " . count($notas_raw) . "</p>";
    
    if (!empty($notas_raw)) {
        // Busca os detalhes das disciplinas para cada nota
        $notas = [];
        foreach ($notas_raw as $nota) {
            // Busca os dados da disciplina
            $sql = "SELECT * FROM disciplinas WHERE id = ?";
            $disciplina = $db->fetchOne($sql, [$nota['disciplina_id']]) ?: ['nome' => 'Disciplina não encontrada'];
            
            // Adiciona os dados da disciplina à nota
            $nota['disciplina_nome'] = $disciplina['nome'] ?? '';
            $nota['disciplina_codigo'] = $disciplina['codigo'] ?? '';
            $nota['carga_horaria'] = $disciplina['carga_horaria'] ?? '';
            
            $notas[] = $nota;
        }
        
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
            echo "<td>{$nota['disciplina_nome']} (" . ($nota['disciplina_codigo'] ?? 'N/A') . ")</td>";
            echo "<td>{$nota['nota']}</td>";
            echo "<td>{$nota['frequencia']}%</td>";
            echo "<td>{$nota['situacao']}</td>";
            echo "<td>" . ($nota['data_lancamento'] ? date('d/m/Y', strtotime($nota['data_lancamento'])) : '-') . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        // Adiciona link para o boletim
        echo "<p><a href='notas.php?action=boletim&aluno_id={$aluno_id}&matricula_id={$matricula['id']}' target='_blank'>Ver Boletim desta Matrícula</a></p>";
    } else {
        echo "<p style='color: red;'>Nenhuma nota encontrada para esta matrícula.</p>";
    }
    
    echo "</div>";
}

// Agora vamos verificar a consulta SQL usada no boletim
echo "<h2>Verificação da Consulta SQL do Boletim</h2>";

// Simula a consulta usada no boletim
if (!empty($matriculas)) {
    $matricula_teste = $matriculas[0];
    $matricula_id = $matricula_teste['id'];
    
    echo "<p>Testando consulta para a matrícula ID: {$matricula_id}</p>";
    
    // Consulta 1: Consulta completa com joins
    $sql1 = "SELECT nd.*, d.nome as disciplina_nome, d.codigo as disciplina_codigo, d.carga_horaria,
                   p.nome as professor_nome
            FROM notas_disciplinas nd
            JOIN disciplinas d ON nd.disciplina_id = d.id
            LEFT JOIN professores p ON d.professor_id = p.id
            WHERE nd.matricula_id = ?
            ORDER BY d.nome ASC";
    
    try {
        $notas1 = $db->fetchAll($sql1, [$matricula_id]) ?: [];
        echo "<p>Consulta 1 (com joins): " . count($notas1) . " notas encontradas</p>";
        
        if (empty($notas1)) {
            // Consulta 2: Consulta simplificada sem joins
            $sql2 = "SELECT * FROM notas_disciplinas WHERE matricula_id = ?";
            $notas2 = $db->fetchAll($sql2, [$matricula_id]) ?: [];
            echo "<p>Consulta 2 (sem joins): " . count($notas2) . " notas encontradas</p>";
            
            if (!empty($notas2)) {
                echo "<p style='color: red;'>Problema identificado: A consulta com joins não retorna resultados, mas a consulta direta sim.</p>";
                echo "<p>Isso pode indicar um problema com os joins ou com os dados nas tabelas relacionadas.</p>";
                
                // Verifica se as disciplinas existem
                foreach ($notas2 as $nota) {
                    $sql = "SELECT * FROM disciplinas WHERE id = ?";
                    $disciplina = $db->fetchOne($sql, [$nota['disciplina_id']]);
                    
                    if (!$disciplina) {
                        echo "<p style='color: red;'>Disciplina ID {$nota['disciplina_id']} não encontrada para a nota ID {$nota['id']}.</p>";
                    }
                }
            }
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Erro na consulta: " . $e->getMessage() . "</p>";
    }
}

// Adiciona botão para voltar
echo "<p><a href='notas.php?action=boletim' style='display: inline-block; padding: 10px 15px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Voltar para Boletins</a></p>";
?>
