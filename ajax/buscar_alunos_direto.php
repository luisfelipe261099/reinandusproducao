<?php
// Inicializa o sistema
require_once __DIR__ . '/../includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Instancia o banco de dados
$db = Database::getInstance();

// Obtém o termo de busca
$termo = $_GET['termo'] ?? '';

// Verifica se o termo de busca foi fornecido
if (empty($termo) || strlen($termo) < 3) {
    // Retorna um array vazio se o termo for muito curto
    header('Content-Type: application/json');
    echo json_encode(['alunos' => [], 'total' => 0]);
    exit;
}

try {
    // Busca alunos que correspondem ao termo (nome, email ou CPF)
    $sql = "SELECT id, nome, email, cpf FROM alunos 
            WHERE nome LIKE ? OR email LIKE ? OR cpf LIKE ? 
            ORDER BY nome ASC 
            LIMIT 100";
    
    $params = ["%{$termo}%", "%{$termo}%", "%{$termo}%"];
    $alunos = $db->fetchAll($sql, $params) ?: [];
    
    // Retorna os resultados como JSON
    header('Content-Type: application/json');
    echo json_encode([
        'alunos' => $alunos,
        'total' => count($alunos)
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    // Em caso de erro, retorna um erro 500
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'error' => 'Erro ao buscar alunos: ' . $e->getMessage(),
        'alunos' => [],
        'total' => 0
    ], JSON_UNESCAPED_UNICODE);
}
