<?php
/**
 * Endpoint para buscar professores por nome
 */

// Inicializa o sistema
require_once __DIR__ . '/includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica se o usuário tem permissão para acessar o módulo de professores
exigirPermissao('professores');

// Instancia o banco de dados
$db = Database::getInstance();

// Obtém o termo de busca
$termo = $_GET['termo'] ?? '';

// Verifica se o termo de busca foi fornecido
if (empty($termo) || strlen($termo) < 3) {
    // Retorna um array vazio se o termo for muito curto
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}

try {
    // Busca professores que correspondem ao termo (nome)
    $sql = "SELECT id, nome FROM professores 
            WHERE nome LIKE ? 
            ORDER BY nome ASC 
            LIMIT 10";
    
    $params = ["%{$termo}%"];
    $professores = $db->fetchAll($sql, $params) ?: [];
    
    // Retorna os resultados como JSON
    header('Content-Type: application/json');
    echo json_encode($professores);
} catch (Exception $e) {
    // Em caso de erro, retorna um erro 500
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['erro' => 'Erro ao buscar professores: ' . $e->getMessage()]);
}
