<?php
/**
 * Endpoint para buscar alunos por nome, email ou CPF
 */

// Inicializa o sistema
require_once __DIR__ . '/includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica se o usuário tem permissão para acessar o módulo de alunos
exigirPermissao('alunos');

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
    // Busca alunos que correspondem ao termo (nome, email ou CPF)
    $sql = "SELECT id, nome, email, cpf FROM alunos 
            WHERE nome LIKE ? OR email LIKE ? OR cpf LIKE ? 
            ORDER BY nome ASC 
            LIMIT 10";
    
    $params = ["%{$termo}%", "%{$termo}%", "%{$termo}%"];
    $alunos = $db->fetchAll($sql, $params) ?: [];
    
    // Retorna os resultados como JSON
    header('Content-Type: application/json');
    echo json_encode($alunos);
} catch (Exception $e) {
    // Em caso de erro, retorna um erro 500
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['erro' => 'Erro ao buscar alunos: ' . $e->getMessage()]);
}
