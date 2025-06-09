<?php
/**
 * AJAX para buscar detalhes de um boleto
 */

require_once '../../includes/init.php';
require_once '../../includes/Database.php';
require_once '../../includes/Auth.php';

header('Content-Type: application/json');

// Verifica autenticação
if (!Auth::isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit;
}

// Verifica permissão
$userType = Auth::getUserType();
if (!in_array($userType, ['financeiro', 'admin_master'])) {
    echo json_encode(['success' => false, 'message' => 'Sem permissão']);
    exit;
}

$boletoId = $_GET['id'] ?? null;

if (!$boletoId) {
    echo json_encode(['success' => false, 'message' => 'ID do boleto não informado']);
    exit;
}

try {
    $db = Database::getInstance();
    
    $boleto = $db->fetchOne("
        SELECT b.*, 
               CASE 
                   WHEN b.tipo = 'mensalidade' THEN a.nome
                   WHEN b.tipo = 'polo' THEN p.nome
                   ELSE b.nome_pagador
               END as pagador_nome
        FROM boletos b
        LEFT JOIN alunos a ON b.tipo = 'mensalidade' AND b.referencia_id = a.id
        LEFT JOIN polos p ON b.tipo = 'polo' AND b.referencia_id = p.id
        WHERE b.id = ?
    ", [$boletoId]);
    
    if (!$boleto) {
        echo json_encode(['success' => false, 'message' => 'Boleto não encontrado']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'boleto' => $boleto
    ]);
    
} catch (Exception $e) {
    error_log('Erro ao buscar detalhes do boleto: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
}
