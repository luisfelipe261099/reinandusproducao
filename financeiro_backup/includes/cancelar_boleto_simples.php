<?php
/**
 * Função simplificada para cancelar boletos via API do Itaú
 * Usa o mesmo formato que funcionou para a geração de boletos
 */

/**
 * Cancela um boleto bancário via API do Itaú usando o formato simplificado
 *
 * @param int $boleto_id ID do boleto a ser cancelado
 * @param object $db Objeto de conexão com o banco de dados
 * @return array Resultado da operação
 */
function cancelarBoletoSimples($boleto_id, $db) {
    try {
        // Log para depuração
        error_log("Iniciando cancelamento simplificado do boleto ID: $boleto_id");

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

        // Configurações da API do Itaú
        $client_id     = "8a7ee29a-f20d-43b8-b3f5-c559862669a9";
        $client_secret = "a6a29bfe-bec3-4619-b1c0-5653e6322ba0";
        $token_url     = "https://sts.itau.com.br/api/oauth/token";
        // Usando a URL base sem o nosso número no caminho
        $cancelar_url  = "https://api.itau.com.br/cash_management/v2/boletos/cancelamento";
        $certFile      = __DIR__ . '/../../certificados/Certificado.crt';
        $keyFile       = __DIR__ . '/../../certificados/ARQUIVO_CHAVE_PRIVADA.key';

        // Verifica se os certificados existem
        if (!file_exists($certFile) || !file_exists($keyFile)) {
            error_log("Certificados não encontrados. Verifique se os arquivos existem em: $certFile e $keyFile");
            return ['status' => 'erro', 'mensagem' => 'Certificados não encontrados. Verifique os logs para mais detalhes.'];
        }

        // Obter token de acesso
        $ch = curl_init($token_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials&client_id=$client_id&client_secret=$client_secret");
        curl_setopt($ch, CURLOPT_SSLCERT, $certFile);
        curl_setopt($ch, CURLOPT_SSLKEY, $keyFile);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpcode != 200) {
            error_log("Erro ao obter token de acesso: $httpcode - $response");
            return ['status' => 'erro', 'mensagem' => 'Erro ao obter token de acesso: ' . $response];
        }

        $token_data = json_decode($response, true);
        $access_token = $token_data['access_token'];

        // Monta o payload para a API de cancelamento
        // Formato ajustado com base na documentação do Itaú
        $payload = [
            "beneficiario" => [
                "cpf_cnpj_beneficiario" => "34119578000163",
                "agencia_beneficiario" => "0978",
                "conta_beneficiario" => "27155",
                "digito_verificador_conta" => "1"
            ],
            "lista_boletos" => [
                [
                    "codigo_carteira" => "109",
                    "nosso_numero" => $boleto['nosso_numero'],
                    "data_cancelamento" => date('Y-m-d'),
                    "codigo_motivo_cancelamento" => "1" // 1 = Solicitação do cliente
                ]
            ]
        ];

        // Log detalhado do nosso número para diagnóstico
        error_log("Nosso número usado no cancelamento: " . $boleto['nosso_numero']);
        error_log("Comprimento do nosso número: " . strlen($boleto['nosso_numero']));

        // Log do payload para depuração
        error_log("Enviando payload para API de cancelamento do Itaú: " . json_encode($payload));

        // Chamar API de cancelamento
        $ch = curl_init($cancelar_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_SSLCERT, $certFile);
        curl_setopt($ch, CURLOPT_SSLKEY, $keyFile);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            "Authorization: Bearer $access_token"
        ]);

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);

        // Log detalhado da requisição para diagnóstico
        $info = curl_getinfo($ch);

        curl_close($ch);

        // Log da resposta bruta para depuração
        error_log("Resposta bruta da API de cancelamento do Itaú (HTTP $httpcode): $response");
        if (!empty($curl_error)) {
            error_log("Erro cURL: $curl_error");
        }

        // Log dos detalhes da requisição
        error_log("Detalhes da requisição: " . json_encode($info));
        error_log("URL completa: " . $info['url']);
        error_log("Método HTTP: " . ($info['request_size'] > 0 ? "POST" : "GET"));
        error_log("Código de resposta: " . $info['http_code']);
        error_log("Tempo total: " . $info['total_time'] . " segundos");

        if ($httpcode >= 200 && $httpcode < 300) {
            // Cancelamento bem-sucedido na API
            error_log("Boleto cancelado com sucesso na API do Itaú: ID $boleto_id");

            // Atualiza o status do boleto para cancelado
            $result = $db->update('boletos', [
                'status' => 'cancelado',
                'data_cancelamento' => date('Y-m-d H:i:s'),
                'observacoes' => ($boleto['observacoes'] ? $boleto['observacoes'] . "\n" : "") . "Cancelado via API em " . date('d/m/Y H:i:s')
            ], 'id = ?', [$boleto_id]);

            if ($result === false) {
                error_log("Erro ao atualizar o status do boleto ID: $boleto_id");
                return [
                    'status' => 'aviso',
                    'mensagem' => 'Boleto cancelado na API, mas houve um erro ao atualizar o status no sistema.'
                ];
            }

            // Registra o cancelamento na tabela de histórico
            try {
                $db->insert('boletos_historico', [
                    'boleto_id' => $boleto_id,
                    'acao' => 'cancelamento_api',
                    'data' => date('Y-m-d H:i:s'),
                    'usuario_id' => isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : null,
                    'detalhes' => 'Cancelado via API do Itaú (método simplificado)'
                ]);
            } catch (Exception $e) {
                error_log("Erro ao registrar histórico de cancelamento: " . $e->getMessage());
                // Não retorna erro, apenas loga
            }

            return [
                'status' => 'sucesso',
                'mensagem' => 'Boleto cancelado com sucesso.'
            ];
        } else {
            // Erro no cancelamento na API
            error_log("Erro ao cancelar boleto na API do Itaú: ID $boleto_id, HTTP $httpcode, Resposta: $response");

            // Verifica se é um erro específico que indica que o boleto já está cancelado
            $response_data = json_decode($response, true);
            if (isset($response_data['mensagem']) &&
                (strpos(strtolower($response_data['mensagem']), 'cancelado') !== false ||
                 strpos(strtolower($response_data['mensagem']), 'baixado') !== false)) {

                error_log("Boleto já está cancelado/baixado na API do Itaú: ID $boleto_id");

                // Atualiza o status do boleto para cancelado
                $result = $db->update('boletos', [
                    'status' => 'cancelado',
                    'data_cancelamento' => date('Y-m-d H:i:s'),
                    'observacoes' => ($boleto['observacoes'] ? $boleto['observacoes'] . "\n" : "") . "Boleto já estava cancelado/baixado na API em " . date('d/m/Y H:i:s')
                ], 'id = ?', [$boleto_id]);

                if ($result === false) {
                    error_log("Erro ao atualizar o status do boleto ID: $boleto_id");
                    return [
                        'status' => 'aviso',
                        'mensagem' => 'Boleto já estava cancelado na API, mas houve um erro ao atualizar o status no sistema.'
                    ];
                }

                return [
                    'status' => 'sucesso',
                    'mensagem' => 'Boleto já estava cancelado/baixado na API. Status atualizado no sistema.'
                ];
            }

            return [
                'status' => 'erro',
                'mensagem' => 'Erro ao cancelar boleto: ' . $response
            ];
        }
    } catch (Exception $e) {
        error_log('Erro ao cancelar boleto: ' . $e->getMessage());

        return [
            'status' => 'erro',
            'mensagem' => 'Erro ao cancelar boleto: ' . $e->getMessage()
        ];
    }
}
?>
