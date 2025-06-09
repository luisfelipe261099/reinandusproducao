<?php
/**
 * API para buscar disciplinas disponíveis
 */

// Inicializa o sistema
require_once __DIR__ . '/includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica se o usuário tem permissão para acessar o módulo de notas
exigirPermissao('notas');

// Instancia o banco de dados
$db = Database::getInstance();

// Obter parâmetros
$curso_id = isset($_GET['curso_id']) ? intval($_GET['curso_id']) : 0;
$turma_id = isset($_GET['turma_id']) ? intval($_GET['turma_id']) : 0;
$termo = isset($_GET['termo']) ? trim($_GET['termo']) : '';

// Validar parâmetros
if (!$curso_id || !$turma_id) {
    header('Content-Type: application/json');
    echo json_encode(['erro' => 'Parâmetros inválidos']);
    exit;
}

try {
    // Construir a consulta SQL
    $sql = "SELECT d.id, d.nome, d.codigo, d.carga_horaria
            FROM disciplinas d
            WHERE d.curso_id = ?";
    
    $params = [$curso_id];
    
    // Adicionar filtro de busca
    if (!empty($termo)) {
        $sql .= " AND (d.nome LIKE ? OR d.codigo LIKE ?)";
        $params[] = "%{$termo}%";
        $params[] = "%{$termo}%";
    }
    
    // Ordenar resultados
    $sql .= " ORDER BY d.nome ASC";
    
    // Executar consulta
    $disciplinas = [];
    $stmt = $db->fetchAll($sql, $params);
    
    if ($stmt) {
        $disciplinas = $stmt;
    }
    
    // Retornar resultados
    header('Content-Type: application/json');
    echo json_encode($disciplinas);
    
} catch (Exception $e) {
    // Retornar erro
    header('Content-Type: application/json');
    echo json_encode(['erro' => $e->getMessage()]);
}