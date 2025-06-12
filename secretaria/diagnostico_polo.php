<?php
/**
 * Script de teste para diagnosticar problema do polo incorreto
 * Para o aluno ID 24599
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/init.php';
require_once '../includes/Database.php';

$db = Database::getInstance();
$aluno_id = 24599;

echo "=== DIAGNÓSTICO DO POLO PARA ALUNO ID: $aluno_id ===\n\n";

// 1. Verificar dados básicos do aluno
echo "1. DADOS BÁSICOS DO ALUNO:\n";
$sql_aluno = "SELECT id, nome, polo_id, curso_id, turma_id FROM alunos WHERE id = ?";
$aluno_basico = $db->fetchOne($sql_aluno, [$aluno_id]);
if ($aluno_basico) {
    echo "   Nome: {$aluno_basico['nome']}\n";
    echo "   Polo ID (campo polo_id): {$aluno_basico['polo_id']}\n";
    echo "   Curso ID: {$aluno_basico['curso_id']}\n";
    echo "   Turma ID: {$aluno_basico['turma_id']}\n";
} else {
    echo "   ALUNO NÃO ENCONTRADO!\n";
    exit;
}

// 2. Verificar matrículas do aluno
echo "\n2. MATRÍCULAS DO ALUNO:\n";
$sql_matriculas = "SELECT id, aluno_id, curso_id, polo_id, turma_id, status, data_matricula, created_at 
                   FROM matriculas 
                   WHERE aluno_id = ? 
                   ORDER BY created_at DESC";
$matriculas = $db->fetchAll($sql_matriculas, [$aluno_id]);
if ($matriculas) {
    foreach ($matriculas as $i => $matricula) {
        echo "   Matrícula " . ($i + 1) . ":\n";
        echo "     ID: {$matricula['id']}\n";
        echo "     Polo ID: {$matricula['polo_id']}\n";
        echo "     Curso ID: {$matricula['curso_id']}\n";
        echo "     Turma ID: {$matricula['turma_id']}\n";
        echo "     Status: {$matricula['status']}\n";
        echo "     Data Matrícula: {$matricula['data_matricula']}\n";
        echo "     Created At: {$matricula['created_at']}\n";
        echo "     ---\n";
    }
} else {
    echo "   NENHUMA MATRÍCULA ENCONTRADA!\n";
}

// 3. Verificar nomes dos polos
echo "\n3. NOMES DOS POLOS ENVOLVIDOS:\n";
$polos_ids = [];
if ($aluno_basico['polo_id']) {
    $polos_ids[] = $aluno_basico['polo_id'];
}
foreach ($matriculas as $matricula) {
    if ($matricula['polo_id'] && !in_array($matricula['polo_id'], $polos_ids)) {
        $polos_ids[] = $matricula['polo_id'];
    }
}

foreach ($polos_ids as $polo_id) {
    $sql_polo = "SELECT id, nome, razao_social FROM polos WHERE id = ?";
    $polo = $db->fetchOne($sql_polo, [$polo_id]);
    if ($polo) {
        echo "   Polo ID {$polo_id}: {$polo['nome']} (Razão Social: {$polo['razao_social']})\n";
    } else {
        echo "   Polo ID {$polo_id}: NÃO ENCONTRADO!\n";
    }
}

// 4. Simular a consulta atual da função buscarDadosAlunoCompletoParaDocumento
echo "\n4. SIMULAÇÃO DA CONSULTA ATUAL (MATRÍCULA ATIVA):\n";
$sql_atual = "SELECT a.*,
               c.nome as curso_nome,
               c.carga_horaria as curso_carga_horaria,
               t.carga_horaria as turma_carga_horaria,
               t.nome as turma_nome,
               t.id as turma_id,
               m.id as matricula_id,
               m.status as matricula_status,
               m.polo_id as matricula_polo_id,
               p.nome as polo_nome,
               p.razao_social as polo_razao_social,
               p.id as polo_id
            FROM alunos a
            LEFT JOIN matriculas m ON a.id = m.aluno_id AND m.status = 'ativo'
            LEFT JOIN cursos c ON m.curso_id = c.id
            LEFT JOIN turmas t ON m.turma_id = t.id
            LEFT JOIN polos p ON m.polo_id = p.id
            WHERE a.id = ?
            ORDER BY m.created_at DESC
            LIMIT 1";

$resultado = $db->fetchOne($sql_atual, [$aluno_id]);
if ($resultado) {
    echo "   Resultado encontrado:\n";
    echo "     Polo nome: " . ($resultado['polo_nome'] ?? 'NULL') . "\n";
    echo "     Polo ID: " . ($resultado['polo_id'] ?? 'NULL') . "\n";
    echo "     Matrícula ID: " . ($resultado['matricula_id'] ?? 'NULL') . "\n";
    echo "     Status Matrícula: " . ($resultado['matricula_status'] ?? 'NULL') . "\n";
} else {
    echo "   NENHUM RESULTADO ENCONTRADO!\n";
}

// 5. Simular consulta alternativa (qualquer matrícula)
echo "\n5. SIMULAÇÃO DA CONSULTA ALTERNATIVA (QUALQUER MATRÍCULA):\n";
$sql_alternativa = "SELECT a.*,
                   c.nome as curso_nome,
                   c.carga_horaria as curso_carga_horaria,
                   t.carga_horaria as turma_carga_horaria,
                   t.nome as turma_nome,
                   t.id as turma_id,
                   m.id as matricula_id,
                   m.status as matricula_status,
                   m.polo_id as matricula_polo_id,
                   p.nome as polo_nome,
                   p.razao_social as polo_razao_social,
                   p.id as polo_id
                FROM alunos a
                LEFT JOIN matriculas m ON a.id = m.aluno_id
                LEFT JOIN cursos c ON m.curso_id = c.id
                LEFT JOIN turmas t ON m.turma_id = t.id
                LEFT JOIN polos p ON m.polo_id = p.id
                WHERE a.id = ?
                ORDER BY m.created_at DESC
                LIMIT 1";

$resultado_alt = $db->fetchOne($sql_alternativa, [$aluno_id]);
if ($resultado_alt) {
    echo "   Resultado encontrado:\n";
    echo "     Polo nome: " . ($resultado_alt['polo_nome'] ?? 'NULL') . "\n";
    echo "     Polo ID: " . ($resultado_alt['polo_id'] ?? 'NULL') . "\n";
    echo "     Matrícula ID: " . ($resultado_alt['matricula_id'] ?? 'NULL') . "\n";
    echo "     Status Matrícula: " . ($resultado_alt['matricula_status'] ?? 'NULL') . "\n";
} else {
    echo "   NENHUM RESULTADO ENCONTRADO!\n";
}

echo "\n=== FIM DO DIAGNÓSTICO ===\n";
?>
