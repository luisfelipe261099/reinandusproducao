<?php
/**
 * ============================================================================
 * BUSCAR POLOS POR CURSO - AJAX
 * ============================================================================
 * 
 * Este arquivo é responsável por buscar os polos relacionados a um curso
 * específico via AJAX para o formulário de matrículas.
 * 
 * @author Sistema Faciência ERP
 * @version 1.0
 * @since 2025-06-12
 * ============================================================================
 */

// Configurações de resposta JSON
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

try {
    // Inicializa o sistema
    require_once __DIR__ . '/../includes/init.php';
    
    // Verifica se o usuário está autenticado
    exigirLogin();
    
    // Verifica se o usuário tem permissão para acessar matrículas
    exigirPermissao('matriculas');
    
    // Obtém a instância do banco de dados
    $db = Database::getInstance();
    
    // Obtém o ID do curso via GET
    $curso_id = $_GET['curso_id'] ?? null;
    
    if (empty($curso_id) || !is_numeric($curso_id)) {
        throw new Exception('ID do curso é obrigatório e deve ser numérico.');
    }
    
    // Busca os polos relacionados ao curso
    $sql = "SELECT DISTINCT p.id, p.nome, p.cidade, p.estado 
            FROM polos p
            INNER JOIN cursos c ON c.polo_id = p.id 
            WHERE c.id = ? AND p.status = 'ativo'
            ORDER BY p.nome ASC";
    
    $polos = $db->fetchAll($sql, [$curso_id]);
    
    // Se não encontrou polos pelo relacionamento direto curso->polo, 
    // busca pelos polos das turmas do curso
    if (empty($polos)) {
        $sql = "SELECT DISTINCT p.id, p.nome, p.cidade, p.estado 
                FROM polos p
                INNER JOIN turmas t ON t.polo_id = p.id 
                WHERE t.curso_id = ? AND t.status = 'ativo' AND p.status = 'ativo'
                ORDER BY p.nome ASC";
        
        $polos = $db->fetchAll($sql, [$curso_id]);
    }
    
    // Se ainda não encontrou polos, retorna todos os polos ativos
    if (empty($polos)) {
        $sql = "SELECT p.id, p.nome, p.cidade, p.estado 
                FROM polos p
                WHERE p.status = 'ativo'
                ORDER BY p.nome ASC";
        
        $polos = $db->fetchAll($sql);
    }
    
    // Formata os dados para o JSON
    $polos_formatados = [];
    foreach ($polos as $polo) {
        $polos_formatados[] = [
            'id' => $polo['id'],
            'nome' => $polo['nome'] . (isset($polo['cidade']) && $polo['cidade'] ? ' - ' . $polo['cidade'] : ''),
            'cidade' => $polo['cidade'] ?? '',
            'estado' => $polo['estado'] ?? ''
        ];
    }
    
    // Retorna os dados em JSON
    echo json_encode([
        'success' => true,
        'polos' => $polos_formatados,
        'total' => count($polos_formatados),
        'message' => count($polos_formatados) > 0 ? 'Polos encontrados.' : 'Nenhum polo encontrado para este curso.'
    ]);
    
} catch (Exception $e) {
    // Registra o erro no log
    error_log('Erro ao buscar polos: ' . $e->getMessage());
    
    // Retorna erro em JSON
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'polos' => []
    ]);
}
?>
