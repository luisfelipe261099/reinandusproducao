<?php
// Inclui o arquivo de configuração
require_once '../config.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Usuário não autenticado']);
    exit;
}

// Obtém o ID do polo
$polo_id = isset($_GET['polo_id']) ? (int)$_GET['polo_id'] : 0;

if ($polo_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'ID do polo não informado']);
    exit;
}

try {
    // Busca os tipos de polo associados
    $sql = "SELECT pt.*, tp.nome as tipo_nome, tp.descricao as tipo_descricao
            FROM polos_tipos pt
            JOIN tipos_polos tp ON pt.tipo_polo_id = tp.id
            WHERE pt.polo_id = ?
            ORDER BY tp.nome ASC";
    $tipos_polo = $db->fetchAll($sql, [$polo_id]);
    
    // Busca as informações financeiras do polo
    $sql = "SELECT pf.*, tp.nome as tipo_nome, tpf.taxa_inicial, tpf.taxa_por_documento, 
                   tpf.pacote_documentos, tpf.valor_pacote
            FROM polos_financeiro pf
            JOIN tipos_polos tp ON pf.tipo_polo_id = tp.id
            JOIN tipos_polos_financeiro tpf ON pf.tipo_polo_id = tpf.tipo_polo_id
            WHERE pf.polo_id = ?
            ORDER BY tp.nome ASC";
    $financeiro = $db->fetchAll($sql, [$polo_id]);
    
    // Busca o histórico financeiro do polo
    $sql = "SELECT pfh.*, tp.nome as tipo_nome, u.nome as usuario_nome
            FROM polos_financeiro_historico pfh
            JOIN tipos_polos tp ON pfh.tipo_polo_id = tp.id
            LEFT JOIN usuarios u ON pfh.usuario_id = u.id
            WHERE pfh.polo_id = ?
            ORDER BY pfh.data_transacao DESC, pfh.created_at DESC
            LIMIT 50";
    $historico = $db->fetchAll($sql, [$polo_id]);
    
    header('Content-Type: application/json');
    echo json_encode([
        'tipos_polo' => $tipos_polo ?: [],
        'financeiro' => $financeiro ?: [],
        'historico' => $historico ?: []
    ]);
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Erro ao buscar informações financeiras: ' . $e->getMessage()]);
}
