<?php
/**
 * AJAX para buscar indicadores rápidos do módulo secretaria
 */

// Configuração básica
error_reporting(E_ALL);
ini_set('display_errors', 0); // Não exibir erros no output JSON

// Headers para JSON
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

try {
    // Inclui apenas os arquivos essenciais
    require_once __DIR__ . '/../includes/Database.php';
    
    // Conecta ao banco
    $db = Database::getInstance();
    
    // Inicializa contadores
    $totalAlunos = 0;
    $matriculasAtivas = 0;
    $totalCursos = 0;
    
    // Total de alunos ativos
    try {
        $result = $db->fetchOne("SELECT COUNT(*) as total FROM alunos WHERE status = 'ativo'");
        $totalAlunos = (int)($result['total'] ?? 0);
    } catch (Exception $e) {
        error_log("Erro ao buscar alunos: " . $e->getMessage());
    }
    
    // Matrículas ativas
    try {
        $result = $db->fetchOne("SELECT COUNT(*) as total FROM matriculas WHERE status = 'ativo'");
        $matriculasAtivas = (int)($result['total'] ?? 0);
    } catch (Exception $e) {
        error_log("Erro ao buscar matrículas: " . $e->getMessage());
    }
    
    // Total de cursos ativos
    try {
        $result = $db->fetchOne("SELECT COUNT(*) as total FROM cursos WHERE status = 'ativo'");
        $totalCursos = (int)($result['total'] ?? 0);
    } catch (Exception $e) {
        error_log("Erro ao buscar cursos: " . $e->getMessage());
    }
    
    // Retorna os dados
    echo json_encode([
        'success' => true,
        'total_alunos' => $totalAlunos,
        'matriculas_ativas' => $matriculasAtivas,
        'total_cursos' => $totalCursos,
        'timestamp' => time()
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Erro geral no indicadores_rapidos.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor',
        'total_alunos' => 0,
        'matriculas_ativas' => 0,
        'total_cursos' => 0,
        'timestamp' => time()
    ], JSON_UNESCAPED_UNICODE);
}
