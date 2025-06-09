<?php
/**
 * AJAX para buscar indicadores rápidos do módulo secretaria
 */

require_once '../includes/init.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

header('Content-Type: application/json');

// Verifica autenticação
if (!Auth::isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit;
}

try {
    $db = Database::getInstance();
    
    // Total de alunos
    $totalAlunos = 0;
    try {
        $result = $db->fetchOne("SELECT COUNT(*) as total FROM alunos WHERE status = 'ativo'");
        $totalAlunos = $result['total'] ?? 0;
    } catch (Exception $e) {
        // Tabela pode não existir ainda
    }
    
    // Matrículas ativas
    $matriculasAtivas = 0;
    try {
        $result = $db->fetchOne("SELECT COUNT(*) as total FROM matriculas WHERE status = 'ativa'");
        $matriculasAtivas = $result['total'] ?? 0;
    } catch (Exception $e) {
        // Tabela pode não existir ainda
    }
    
    // Total de cursos
    $totalCursos = 0;
    try {
        $result = $db->fetchOne("SELECT COUNT(*) as total FROM cursos WHERE status = 'ativo'");
        $totalCursos = $result['total'] ?? 0;
    } catch (Exception $e) {
        // Tabela pode não existir ainda
    }
    
    echo json_encode([
        'success' => true,
        'total_alunos' => $totalAlunos,
        'matriculas_ativas' => $matriculasAtivas,
        'total_cursos' => $totalCursos
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor',
        'total_alunos' => 0,
        'matriculas_ativas' => 0,
        'total_cursos' => 0
    ]);
}
