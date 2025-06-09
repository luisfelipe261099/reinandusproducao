<?php
/**
 * Busca AJAX de alunos para evitar travamento na página de boletos
 */

require_once '../../includes/init.php';
require_once '../../includes/Database.php';
require_once '../../includes/Auth.php';

// Verifica autenticação
Auth::requireLogin();
$userType = Auth::getUserType();
if (!in_array($userType, ['financeiro', 'admin_master'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Acesso negado']);
    exit;
}

// Verifica se é uma requisição AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(400);
    echo json_encode(['error' => 'Requisição inválida']);
    exit;
}

$db = Database::getInstance();

try {
    $termo = $_GET['termo'] ?? '';
    $limite = min(intval($_GET['limite'] ?? 20), 50); // Máximo 50 resultados
    
    if (strlen($termo) < 2) {
        echo json_encode(['alunos' => [], 'total' => 0]);
        exit;
    }
    
    // Remove caracteres especiais do CPF para busca
    $termo_cpf = preg_replace('/[^0-9]/', '', $termo);
    
    // Monta a consulta SQL
    $sql = "SELECT id, nome, cpf, email 
            FROM alunos 
            WHERE (nome LIKE ? OR cpf LIKE ?)
            ORDER BY nome ASC 
            LIMIT ?";
    
    $params = [
        "%{$termo}%",
        "%{$termo_cpf}%",
        $limite
    ];
    
    $alunos = $db->fetchAll($sql, $params);
    
    // Conta o total de resultados (sem limite)
    $sql_count = "SELECT COUNT(*) as total 
                  FROM alunos 
                  WHERE (nome LIKE ? OR cpf LIKE ?)";
    
    $count_params = [
        "%{$termo}%",
        "%{$termo_cpf}%"
    ];
    
    $total_result = $db->fetchOne($sql_count, $count_params);
    $total = $total_result['total'] ?? 0;
    
    // Formata os dados para retorno
    $alunos_formatados = [];
    foreach ($alunos as $aluno) {
        $alunos_formatados[] = [
            'id' => $aluno['id'],
            'nome' => $aluno['nome'],
            'cpf' => $aluno['cpf'],
            'email' => $aluno['email'] ?? '',
            'cpf_formatado' => formatarCpf($aluno['cpf']),
            'display' => $aluno['nome'] . ' - ' . formatarCpf($aluno['cpf'])
        ];
    }
    
    echo json_encode([
        'alunos' => $alunos_formatados,
        'total' => $total,
        'limite_atingido' => count($alunos) >= $limite
    ]);
    
} catch (Exception $e) {
    error_log("Erro na busca de alunos: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno do servidor']);
}

/**
 * Formata CPF para exibição
 */
function formatarCpf($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    if (strlen($cpf) === 11) {
        return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
    }
    return $cpf;
}
?>
