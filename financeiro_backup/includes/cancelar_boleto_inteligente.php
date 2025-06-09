<?php
/**
 * Função para cancelar boletos de forma inteligente, usando a mesma API que foi usada para gerar o boleto
 */

// Inclui os arquivos necessários
require_once __DIR__ . '/baixar_boleto_diretrizes.php';
require_once __DIR__ . '/cancelar_boleto_cash_management.php';
require_once __DIR__ . '/verificar_boleto_itau.php';

/**
 * Cancela um boleto de forma inteligente, usando a mesma API que foi usada para gerar o boleto
 * 
 * @param int $boleto_id ID do boleto a ser cancelado
 * @param object $db Objeto de conexão com o banco de dados
 * @return array Resultado da operação
 */
function cancelarBoletoInteligente($boleto_id, $db) {
    try {
        // Log para depuração
        error_log("Iniciando cancelamento inteligente do boleto - ID: $boleto_id");

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

        // Verifica se o nosso_numero está definido
        if (empty($boleto['nosso_numero'])) {
            error_log("Nosso número não definido para o boleto ID: $boleto_id");
            return ['status' => 'erro', 'mensagem' => 'Nosso número não definido para este boleto.'];
        }

        // Primeiro, verifica se o boleto existe no Itaú
        $verificacao = verificarBoletoItau($boleto_id, $db);
        
        if ($verificacao['status'] === 'sucesso') {
            // Boleto encontrado, verifica qual API foi usada
            $api_tipo = isset($verificacao['api_tipo']) ? $verificacao['api_tipo'] : 
                        (isset($boleto['api_tipo']) ? $boleto['api_tipo'] : 'cobranca');
            
            error_log("Boleto encontrado no Itaú. Tipo de API: $api_tipo");
            
            // Cancela o boleto usando a API correta
            if ($api_tipo === 'cash_management') {
                error_log("Cancelando boleto via API cash_management");
                return cancelarBoletoCashManagement($boleto_id, $db);
            } else {
                error_log("Cancelando boleto via API de cobrança (diretrizes)");
                return baixarBoletoDiretrizes($boleto_id, $db);
            }
        } else {
            // Boleto não encontrado, tenta ambas as APIs
            error_log("Boleto não encontrado no Itaú. Tentando ambas as APIs...");
            
            // Primeiro tenta a API que foi usada para gerar o boleto (se conhecida)
            $api_tipo = isset($boleto['api_tipo']) ? $boleto['api_tipo'] : 'cash_management';
            
            if ($api_tipo === 'cash_management') {
                error_log("Tentando primeiro a API cash_management");
                $resultado = cancelarBoletoCashManagement($boleto_id, $db);
                
                if ($resultado['status'] === 'sucesso') {
                    return $resultado;
                }
                
                error_log("Falha na API cash_management. Tentando API de cobrança...");
                return baixarBoletoDiretrizes($boleto_id, $db);
            } else {
                error_log("Tentando primeiro a API de cobrança");
                $resultado = baixarBoletoDiretrizes($boleto_id, $db);
                
                if ($resultado['status'] === 'sucesso') {
                    return $resultado;
                }
                
                error_log("Falha na API de cobrança. Tentando API cash_management...");
                return cancelarBoletoCashManagement($boleto_id, $db);
            }
        }
    } catch (Exception $e) {
        error_log('Erro ao cancelar boleto de forma inteligente: ' . $e->getMessage());

        return [
            'status' => 'erro',
            'mensagem' => 'Erro ao cancelar boleto: ' . $e->getMessage()
        ];
    }
}
?>
