<?php
require_once 'includes/config.php';
require_once 'includes/Database.php';
require_once 'includes/functions.php';

$db = Database::getInstance();

echo "=== DATABASE STATUS CHECK ===\n";

// Check total students
$result = $db->fetchOne('SELECT COUNT(*) as total FROM alunos');
echo 'Total students in database: ' . $result['total'] . "\n";

// Check recent students with CPF
$students = $db->fetchAll('SELECT id, nome, cpf, email, created_at FROM alunos ORDER BY created_at DESC LIMIT 5');
echo "\nRecent students:\n";
foreach ($students as $student) {
    echo "ID: {$student['id']}, Nome: {$student['nome']}, CPF: {$student['cpf']}, Email: {$student['email']}, Created: {$student['created_at']}\n";
}

// Check total matriculas
$result = $db->fetchOne('SELECT COUNT(*) as total FROM matriculas');
echo "\nTotal matriculas: " . $result['total'] . "\n";

// Check recent matriculas
$matriculas = $db->fetchAll('SELECT id, aluno_id, curso_id, turma_id, created_at FROM matriculas ORDER BY created_at DESC LIMIT 5');
echo "\nRecent matriculas:\n";
foreach ($matriculas as $matricula) {
    echo "ID: {$matricula['id']}, Aluno ID: {$matricula['aluno_id']}, Curso ID: {$matricula['curso_id']}, Turma ID: {$matricula['turma_id']}, Created: {$matricula['created_at']}\n";
}

// Check for sample CPF in different formats
echo "\n=== CPF TESTING ===\n";
$test_cpfs = ['123.456.789-10', '12345678910'];
foreach ($test_cpfs as $cpf) {
    $cpf_limpo = preg_replace('/[^0-9]/', '', $cpf);
    $sql = "SELECT id, nome, cpf FROM alunos WHERE REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', '') = ?";
    $result = $db->fetchOne($sql, [$cpf_limpo]);
    echo "CPF '{$cpf}' (limpo: '{$cpf_limpo}') - Found: " . ($result ? "YES (ID: {$result['id']}, Nome: {$result['nome']})" : "NO") . "\n";
}

// Check database tables structure
echo "\n=== TABLE STRUCTURE ===\n";
try {
    $result = $db->fetchAll("DESCRIBE alunos");
    echo "Alunos table has " . count($result) . " columns\n";
    
    $result = $db->fetchAll("DESCRIBE matriculas");
    echo "Matriculas table has " . count($result) . " columns\n";
} catch (Exception $e) {
    echo "Error checking table structure: " . $e->getMessage() . "\n";
}

// Test transaction functionality
echo "\n=== TRANSACTION TEST ===\n";
try {
    echo "Testing beginTransaction()... ";
    $db->beginTransaction();
    echo "OK\n";
    
    echo "Testing inTransaction()... ";
    echo ($db->inTransaction() ? "YES" : "NO") . "\n";
    
    echo "Testing rollback()... ";
    $db->rollback();
    echo "OK\n";
    
    echo "After rollback inTransaction()... ";
    echo ($db->inTransaction() ? "YES" : "NO") . "\n";
} catch (Exception $e) {
    echo "Transaction test failed: " . $e->getMessage() . "\n";
}

echo "\n=== TEST COMPLETE ===\n";
?>
