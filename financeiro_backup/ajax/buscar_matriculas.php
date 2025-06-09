<?php
// Inicializa o sistema
require_once __DIR__ . '/../../includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica se o usuário tem permissão para acessar o módulo financeiro
if (!Auth::hasPermission('financeiro', 'visualizar')) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Sem permissão para acessar este recurso.']);
    exit;
}

// Instancia o banco de dados
$db = Database::getInstance();

// Verifica se o ID do aluno foi informado
if (!isset($_GET['aluno_id']) || empty($_GET['aluno_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'ID do aluno não informado.']);
    exit;
}

$aluno_id = (int)$_GET['aluno_id'];

// Busca as matrículas do aluno
$sql = "SELECT m.id, m.numero, c.nome as curso_nome, p.nome as polo_nome
        FROM matriculas m
        LEFT JOIN cursos c ON m.curso_id = c.id
        LEFT JOIN polos p ON c.polo_id = p.id
        WHERE m.aluno_id = ? AND m.status = 'ativo'
        ORDER BY m.id DESC";
$matriculas = $db->fetchAll($sql, [$aluno_id]);

// Retorna as matrículas em formato JSON
header('Content-Type: application/json');
echo json_encode(['matriculas' => $matriculas]);
exit;
?>
