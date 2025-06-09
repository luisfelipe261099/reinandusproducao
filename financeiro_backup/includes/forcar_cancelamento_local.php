<?php
/**
 * Função para forçar o cancelamento de um boleto apenas no sistema local
 * Usar apenas quando não for possível cancelar o boleto na API do banco
 */

/**
 * Força o cancelamento de um boleto apenas no sistema local
 * 
 * @param int $boleto_id ID do boleto a ser cancelado
 * @param string $motivo Motivo do cancelamento forçado
 * @param object $db Objeto de conexão com o banco de dados
 * @return array Resultado da operação
 */
function forcarCancelamentoLocal($boleto_id, $motivo, $db) {
    try {
        // Log para depuração
        error_log("Iniciando cancelamento forçado do boleto - ID: $boleto_id");
        error_log("Motivo: $motivo");

        // Busca os dados do boleto
        $boleto = $db->fetchOne("SELECT * FROM boletos WHERE id = ?", [$boleto_id]);

        if (!$boleto) {
            error_log("Boleto não encontrado: ID $boleto_id");
            return ['status' => 'erro', 'mensagem' => 'Boleto não encontrado.'];
        }

        // Log dos dados do boleto
        error_log("Dados do boleto: " . json_encode($boleto));

        // Verifica se o boleto já está cancelado
        if ($boleto['status'] === 'cancelado') {
            error_log("Boleto já está cancelado: ID $boleto_id");
            return ['status' => 'aviso', 'mensagem' => 'Boleto já está cancelado.'];
        }

        // Verifica se o boleto já está pago
        if ($boleto['status'] === 'pago') {
            error_log("Tentativa de cancelar boleto já pago: ID $boleto_id");
            return ['status' => 'erro', 'mensagem' => 'Não é possível cancelar um boleto já pago.'];
        }

        // Atualiza o status do boleto para cancelado
        try {
            $update_result = $db->update('boletos', [
                'status' => 'cancelado', 
                'data_cancelamento' => date('Y-m-d H:i:s'),
                'observacoes' => ($boleto['observacoes'] ? $boleto['observacoes'] . "\n" : "") . 
                                "CANCELAMENTO FORÇADO em " . date('d/m/Y H:i:s') . "\n" .
                                "Motivo: $motivo\n" .
                                "ATENÇÃO: Este boleto foi cancelado apenas no sistema local. " .
                                "Se o boleto já foi registrado no banco, ele ainda pode ser pago pelo cliente. " .
                                "Recomenda-se verificar no portal do banco se o boleto foi cancelado corretamente."
            ], 'id = ?', [$boleto_id]);

            if ($update_result === false) {
                error_log("Erro ao atualizar o status do boleto ID: $boleto_id após cancelamento forçado");
                return ['status' => 'erro', 'mensagem' => 'Erro ao atualizar o status do boleto no sistema.'];
            }

            // Registra o cancelamento na tabela de histórico
            try {
                $db->insert('boletos_historico', [
                    'boleto_id' => $boleto_id,
                    'acao' => 'cancelamento_forcado',
                    'data' => date('Y-m-d H:i:s'),
                    'usuario_id' => isset($_SESSION['usuario']['id']) ? $_SESSION['usuario']['id'] : null,
                    'detalhes' => "CANCELAMENTO FORÇADO\nMotivo: $motivo"
                ]);
            } catch (Exception $e) {
                error_log("Erro ao registrar histórico de cancelamento forçado: " . $e->getMessage());
                // Não retorna erro, apenas loga
            }

            error_log("Boleto cancelado forçadamente com sucesso: ID $boleto_id");
            return [
                'status' => 'sucesso',
                'mensagem' => 'Boleto cancelado forçadamente com sucesso no sistema local.'
            ];
        } catch (Exception $e) {
            error_log("Erro ao atualizar o status do boleto após cancelamento forçado: " . $e->getMessage());
            return ['status' => 'erro', 'mensagem' => 'Erro ao atualizar o status do boleto no sistema: ' . $e->getMessage()];
        }

    } catch (Exception $e) {
        error_log('Erro ao cancelar boleto forçadamente: ' . $e->getMessage());

        return [
            'status' => 'erro',
            'mensagem' => 'Erro ao cancelar boleto forçadamente: ' . $e->getMessage()
        ];
    }
}
?>
