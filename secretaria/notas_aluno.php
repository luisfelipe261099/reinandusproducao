<?php
/**
 * Função para carregar as notas do aluno via AJAX
 * Adicione este arquivo como notas_aluno.php
 */

// Inicializa o sistema
require_once __DIR__ . '/includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica se o usuário tem permissão
exigirPermissao('notas');

// Instancia o banco de dados
$db = Database::getInstance();

// Obtém o ID do aluno
$aluno_id = isset($_GET['aluno_id']) ? intval($_GET['aluno_id']) : 0;

if (!$aluno_id) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'ID do aluno não fornecido'
    ]);
    exit;
}

try {
    // Busca informações do aluno
    $sql = "SELECT nome FROM alunos WHERE id = ?";
    $aluno = $db->fetchOne($sql, [$aluno_id]);
    
    if (!$aluno) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Aluno não encontrado'
        ]);
        exit;
    }
    
    // Busca todas as matrículas do aluno
    $sql = "SELECT id FROM matriculas WHERE aluno_id = ?";
    $matriculas = $db->fetchAll($sql, [$aluno_id]) ?: [];
    
    if (empty($matriculas)) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'aluno' => $aluno,
            'notas' => [],
            'message' => 'Nenhuma matrícula encontrada para este aluno'
        ]);
        exit;
    }
    
    // Extrai os IDs das matrículas
    $matricula_ids = array_column($matriculas, 'id');
    
    // Prepara os placeholders para a consulta IN
    $placeholders = implode(',', array_fill(0, count($matricula_ids), '?'));
    
    // Busca todas as notas associadas às matrículas do aluno
    $sql = "SELECT nd.*, 
                  d.nome as disciplina_nome, 
                  d.codigo as disciplina_codigo, 
                  c.nome as curso_nome,
                  t.nome as turma_nome
           FROM notas_disciplinas nd
           JOIN disciplinas d ON nd.disciplina_id = d.id
           JOIN matriculas m ON nd.matricula_id = m.id
           LEFT JOIN turmas t ON m.turma_id = t.id
           LEFT JOIN cursos c ON m.curso_id = c.id
           WHERE nd.matricula_id IN ($placeholders)
           ORDER BY c.nome, d.nome";
    
    $notas = $db->fetchAll($sql, $matricula_ids) ?: [];
    
    // Retorna os dados em formato JSON
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'aluno' => $aluno,
        'notas' => $notas
    ]);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar notas: ' . $e->getMessage()
    ]);
}