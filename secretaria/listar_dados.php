<?php
require_once '../includes/init.php';
require_once '../includes/Database.php';

$db = Database::getInstance();

echo "=== LISTAGEM DE TURMAS ===\n";
$sql = "SELECT t.id, t.nome, t.curso_id, t.polo_id, t.status, c.nome as curso_nome, p.nome as polo_nome 
        FROM turmas t 
        LEFT JOIN cursos c ON t.curso_id = c.id 
        LEFT JOIN polos p ON t.polo_id = p.id 
        ORDER BY t.id";
$turmas = $db->fetchAll($sql);

if ($turmas) {
    foreach ($turmas as $turma) {
        echo "ID: {$turma['id']} | Nome: {$turma['nome']} | Curso: {$turma['curso_nome']} (ID: {$turma['curso_id']}) | Polo: {$turma['polo_nome']} (ID: {$turma['polo_id']}) | Status: {$turma['status']}\n";
    }
} else {
    echo "Nenhuma turma encontrada.\n";
}

echo "\n=== LISTAGEM DE POLOS ===\n";
$sql = "SELECT id, nome, cidade, estado, status FROM polos ORDER BY id";
$polos = $db->fetchAll($sql);

if ($polos) {
    foreach ($polos as $polo) {
        echo "ID: {$polo['id']} | Nome: {$polo['nome']} | Cidade: {$polo['cidade']} | Estado: {$polo['estado']} | Status: {$polo['status']}\n";
    }
} else {
    echo "Nenhum polo encontrado.\n";
}

echo "\n=== LISTAGEM DE CURSOS ===\n";
$sql = "SELECT id, nome, polo_id, status FROM cursos ORDER BY id";
$cursos = $db->fetchAll($sql);

if ($cursos) {
    foreach ($cursos as $curso) {
        echo "ID: {$curso['id']} | Nome: {$curso['nome']} | Polo ID: {$curso['polo_id']} | Status: {$curso['status']}\n";
    }
} else {
    echo "Nenhum curso encontrado.\n";
}

echo "\n=== VERIFICAÇÃO DA MATRÍCULA 10191 ===\n";
$sql = "SELECT m.*, a.nome as aluno_nome, c.nome as curso_nome 
        FROM matriculas m 
        LEFT JOIN alunos a ON m.aluno_id = a.id 
        LEFT JOIN cursos c ON m.curso_id = c.id 
        WHERE m.id = 10191";
$matricula = $db->fetchOne($sql);

if ($matricula) {
    echo "Matrícula encontrada:\n";
    echo "ID: {$matricula['id']}\n";
    echo "Aluno: {$matricula['aluno_nome']} (ID: {$matricula['aluno_id']})\n";
    echo "Curso: {$matricula['curso_nome']} (ID: {$matricula['curso_id']})\n";
    echo "Turma ID: {$matricula['turma_id']}\n";
    echo "Polo ID: {$matricula['polo_id']}\n";
    echo "Status: {$matricula['status']}\n";
} else {
    echo "Matrícula 10191 não encontrada.\n";
}
?>
