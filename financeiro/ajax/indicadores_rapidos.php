<?php
/**
 * AJAX - Indicadores Rápidos do Header
 */

require_once '../../includes/init.php';
require_once '../../includes/Database.php';
require_once '../../includes/Auth.php';

// Verifica autenticação
if (!Auth::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autenticado']);
    exit;
}

// Verifica permissão
$userType = Auth::getUserType();
if (!in_array($userType, ['financeiro', 'admin_master'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Sem permissão']);
    exit;
}

header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    
    // Saldo do dia (simplificado - receitas menos despesas do dia)
    $saldoHoje = 0;
    try {
        $receitasHoje = $db->fetchOne("
            SELECT COALESCE(SUM(valor), 0) as total 
            FROM transacoes_financeiras 
            WHERE tipo = 'receita' 
            AND status = 'efetivada' 
            AND data_transacao = CURDATE()
        ")['total'] ?? 0;
        
        $despesasHoje = $db->fetchOne("
            SELECT COALESCE(SUM(valor), 0) as total 
            FROM transacoes_financeiras 
            WHERE tipo = 'despesa' 
            AND status = 'efetivada' 
            AND data_transacao = CURDATE()
        ")['total'] ?? 0;
        
        $saldoHoje = $receitasHoje - $despesasHoje;
    } catch (Exception $e) {
        // Se as tabelas não existem ainda, mantém 0
    }
    
    // Contas pendentes (a pagar + a receber)
    $contasPendentes = 0;
    try {
        $contasPagar = $db->fetchOne("
            SELECT COUNT(*) as total 
            FROM contas_pagar 
            WHERE status = 'pendente'
        ")['total'] ?? 0;
        
        $contasReceber = $db->fetchOne("
            SELECT COUNT(*) as total 
            FROM contas_receber 
            WHERE status = 'pendente'
        ")['total'] ?? 0;
        
        $contasPendentes = $contasPagar + $contasReceber;
    } catch (Exception $e) {
        // Se as tabelas não existem ainda, mantém 0
    }
    
    echo json_encode([
        'success' => true,
        'saldo_hoje' => (float) $saldoHoje,
        'contas_pendentes' => (int) $contasPendentes
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Erro interno do servidor',
        'message' => $e->getMessage()
    ]);
}
