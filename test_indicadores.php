<?php
// Teste simples para verificar os indicadores
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testando conexão com banco de dados...\n";

try {
    require_once 'includes/init.php';
    require_once 'includes/Database.php';
    
    $db = Database::getInstance();
    echo "Conexão OK\n";
    
    // Teste alunos
    $result = $db->fetchOne("SELECT COUNT(*) as total FROM alunos WHERE status = 'ativo'");
    echo "Alunos ativos: " . ($result['total'] ?? 'NULL') . "\n";
    
    // Teste matrículas
    $result = $db->fetchOne("SELECT COUNT(*) as total FROM matriculas WHERE status = 'ativo'");
    echo "Matrículas ativas: " . ($result['total'] ?? 'NULL') . "\n";
    
    // Teste cursos
    $result = $db->fetchOne("SELECT COUNT(*) as total FROM cursos WHERE status = 'ativo'");
    echo "Cursos ativos: " . ($result['total'] ?? 'NULL') . "\n";
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
