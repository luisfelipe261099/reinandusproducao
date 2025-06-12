<?php
/**
 * AJAX para excluir boleto
 */

header('Content-Type: application/json');

// Inclui os arquivos necessários
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/Auth.php';

// Verifica se o usuário está logado
if (!Auth::check()) {
    echo json_encode(['success' => false, 'message' => 'Usuário não logado']);
    exit;
}

// Verifica permissão
if (!Auth::hasPermission('financeiro', 'excluir')) {
    echo json_encode(['success' => false, 'message' => 'Sem permissão para excluir boletos']);
    exit;
}

try {
    // Lê os dados JSON
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['id'])) {
        echo json_encode(['success' => false, 'message' => 'ID do boleto não informado']);
        exit;
    }
    
    $boleto_id = (int)$input['id'];
      // Conecta ao banco
    $db = Database::getInstance();
    
    // Busca o boleto
    $boleto = $db->fetchOne("SELECT * FROM boletos WHERE id = ?", [$boleto_id]);
    
    if (!$boleto) {
        echo json_encode(['success' => false, 'message' => 'Boleto não encontrado']);
        exit;
    }
    
    // Verifica se o boleto pode ser excluído
    if ($boleto['status'] === 'pago') {
        echo json_encode(['success' => false, 'message' => 'Não é possível excluir um boleto pago']);
        exit;
    }
    
    // Log da exclusão
    error_log("Exclusão de boleto solicitada - ID: $boleto_id, Usuário: " . $_SESSION['usuario']['nome']);
    
    // Inicia transação
    $db->beginTransaction();
    
    try {
        // Se o boleto foi gerado via API e está pendente, tenta cancelar primeiro
        if ($boleto['status'] === 'pendente' && !empty($boleto['nosso_numero'])) {
            // Aqui você pode implementar chamada à API para cancelar
            // Por enquanto, vamos apenas marcar como cancelado localmente
            
            $db->update('boletos', [
                'status' => 'cancelado',
                'data_cancelamento' => date('Y-m-d H:i:s'),
                'observacoes' => ($boleto['observacoes'] ? $boleto['observacoes'] . "\n" : "") . 
                                "Cancelado automaticamente antes da exclusão em " . date('d/m/Y H:i:s')
            ], 'id = ?', [$boleto_id]);
            
            // Log
            error_log("Boleto cancelado automaticamente antes da exclusão - ID: $boleto_id");
        }
          // Remove arquivos PDF relacionados (se existirem)
        $arquivosPdf = [
            __DIR__ . '/../../uploads/boletos/boleto_' . $boleto_id . '.pdf',
            __DIR__ . '/../../uploads/boletos/' . $boleto_id . '.pdf',
            __DIR__ . '/../../uploads/boletos/boleto_' . $boleto_id . '.html'
        ];
        
        foreach ($arquivosPdf as $pdf_path) {
            if (file_exists($pdf_path)) {
                unlink($pdf_path);
                error_log("Arquivo PDF removido: $pdf_path");
            }
        }
        
        // Exclui o boleto
        $result = $db->delete('boletos', 'id = ?', [$boleto_id]);
        
        if (!$result) {
            throw new Exception('Erro ao excluir boleto do banco de dados');
        }
        
        // Commit da transação
        $db->commit();
        
        // Log de sucesso
        error_log("Boleto excluído com sucesso - ID: $boleto_id");
        
        echo json_encode([
            'success' => true, 
            'message' => 'Boleto excluído com sucesso'
        ]);
        
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Erro ao excluir boleto: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Erro ao excluir boleto: ' . $e->getMessage()
    ]);
}
?>
