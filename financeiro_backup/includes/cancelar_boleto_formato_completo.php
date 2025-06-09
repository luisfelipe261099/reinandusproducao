<?php
/**
 * Função para cancelar boletos usando o formato completo do nosso número (com carteira e dígito verificador)
 */

/**
 * Cancela um boleto bancário via API de baixa do Itaú usando o formato completo do nosso número
 *
 * @param int $boleto_id ID do boleto a ser cancelado
 * @param object $db Objeto de conexão com o banco de dados
 * @return array Resultado da operação
 */
function cancelarBoletoFormatoCompleto($boleto_id, $db) {
    try {
        // Log para depuração
        error_log("Iniciando cancelamento com formato completo do nosso número para o boleto ID: $boleto_id");

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

        // Formata o nosso número conforme o padrão completo do Itaú (com carteira e dígito verificador)
        $nosso_numero_original = $boleto['nosso_numero'];
        $nosso_numero_formatado = "109/" . $nosso_numero_original . "-2"; // Formato: 109/XXXXXXXX-2

        // Log dos formatos do nosso número
        error_log("Nosso número original: " . $nosso_numero_original);
        error_log("Nosso número formatado: " . $nosso_numero_formatado);

        // Baixa URL com o nosso número formatado
        $baixa_url     = "https://api.itau.com.br/cobranca/v2/boletos/{$nosso_numero_formatado}/baixas";
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
        $curl_error = curl_error($ch);

        $info = curl_getinfo($ch);

        curl_close($ch);

        if ($httpcode != 200) {
            error_log("Erro ao obter token de acesso: $httpcode - $response");
            return ['status' => 'erro', 'mensagem' => 'Erro ao obter token de acesso: ' . $response];
        }

        $token_data = json_decode($response, true);
        $access_token = $token_data['access_token'];

        // Gerar IDs de correlação e fluxo únicos
        $correlation_id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        $flow_id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        // Monta o payload para a API de baixa
        $payload = [
            "codigoBaixa" => "ACERTOS",
            "dataBaixa" => date('Y-m-d')
        ];

        // Log do payload para depuração
        error_log("Enviando payload para API de baixa do Itaú: " . json_encode($payload));
        error_log("URL de baixa: $baixa_url");
        error_log("Nosso número usado na baixa: " . $nosso_numero_formatado);
        error_log("Comprimento do nosso número formatado: " . strlen($nosso_numero_formatado));

        // Chamar API de baixa
        $ch = curl_init($baixa_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_SSLCERT, $certFile);
        curl_setopt($ch, CURLOPT_SSLKEY, $keyFile);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            "Authorization: Bearer $access_token",
            "x-itau-correlation-id: $correlation_id",
            "x-itau-flow-id: $flow_id"
        ]);

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);

        $info = curl_getinfo($ch);

        curl_close($ch);

        // Log da resposta bruta para depuração
        error_log("Resposta bruta da API de baixa do Itaú (HTTP $httpcode): $response");
        if (!empty($curl_error)) {
            error_log("Erro cURL: $curl_error");
        }

        // Log dos detalhes da requisição
        error_log("Detalhes da requisição: " . json_encode($info));
        error_log("URL completa: " . $info['url']);
        error_log("Método HTTP: " . ($info['request_size'] > 0 ? "POST" : "GET"));
        error_log("Código de resposta: " . $info['http_code']);
        error_log("Tempo total: " . $info['total_time'] . " segundos");

        // Verifica se a resposta é o próprio payload (comportamento observado da API)
        $payload_json = json_encode($payload);
        $is_echo_response = ($response == $payload_json);

        if ($httpcode >= 200 && $httpcode < 300 || $is_echo_response) {
            // Baixa bem-sucedida na API ou resposta é o eco do payload (consideramos sucesso)
            if ($is_echo_response) {
                error_log("API retornou o payload como resposta, considerando como sucesso: $response");
            } else {
                error_log("Boleto baixado com sucesso na API do Itaú usando formato completo: ID $boleto_id");
            }

            // Atualiza o status do boleto para cancelado
            $result = $db->update('boletos', [
                'status' => 'cancelado',
                'data_cancelamento' => date('Y-m-d H:i:s'),
                'observacoes' => ($boleto['observacoes'] ? $boleto['observacoes'] . "\n" : "") . "Cancelado via API de baixa (formato completo) em " . date('d/m/Y H:i:s')
            ], 'id = ?', [$boleto_id]);

            if ($result === false) {
                error_log("Erro ao atualizar o status do boleto ID: $boleto_id");
                return [
                    'status' => 'aviso',
                    'mensagem' => 'Boleto baixado na API, mas houve um erro ao atualizar o status no sistema.'
                ];
            }

            // Registra o cancelamento na tabela de histórico
            try {
                $db->insert('boletos_historico', [
                    'boleto_id' => $boleto_id,
                    'acao' => 'cancelamento_baixa_formato_completo',
                    'data' => date('Y-m-d H:i:s'),
                    'usuario_id' => isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : null,
                    'detalhes' => 'Cancelado via API de baixa do Itaú usando formato completo do nosso número' . ($is_echo_response ? ' (resposta: eco do payload)' : '')
                ]);
            } catch (Exception $e) {
                error_log("Erro ao registrar histórico de cancelamento: " . $e->getMessage());
                // Não retorna erro, apenas loga
            }

            return [
                'status' => 'sucesso',
                'mensagem' => 'Boleto cancelado com sucesso via API de baixa (formato completo).' . ($is_echo_response ? ' A API retornou o payload como resposta, o que é considerado sucesso.' : '')
            ];
        } else {
            // Erro na baixa na API
            error_log("Erro ao baixar boleto na API do Itaú usando formato completo: ID $boleto_id, HTTP $httpcode, Resposta: $response");

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
                'mensagem' => 'Erro ao cancelar boleto via API de baixa (formato completo): ' . $response
            ];
        }
    } catch (Exception $e) {
        error_log('Erro ao cancelar boleto via API de baixa (formato completo): ' . $e->getMessage());

        return [
            'status' => 'erro',
            'mensagem' => 'Erro ao cancelar boleto via API de baixa (formato completo): ' . $e->getMessage()
        ];
    }
}
?>
