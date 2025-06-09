<?php
/**
 * Função para baixar (cancelar) um boleto via API do Itaú
 * Implementação baseada nas diretrizes oficiais da API de Cobrança Registrada do Itaú
 */

/**
 * Baixa (cancela) um boleto via API do Itaú
 * 
 * @param int $boleto_id ID do boleto a ser baixado
 * @param object $db Objeto de conexão com o banco de dados
 * @return array Resultado da operação
 */
function baixarBoletoDiretrizes($boleto_id, $db) {
    try {
        // Log para depuração
        error_log("Iniciando baixa do boleto via API do Itaú - ID: $boleto_id");

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
            error_log("Tentativa de baixar boleto já pago: ID $boleto_id");
            return ['status' => 'erro', 'mensagem' => 'Não é possível baixar um boleto já pago.'];
        }

        // Verifica se o nosso_numero está definido
        if (empty($boleto['nosso_numero'])) {
            error_log("Nosso número não definido para o boleto ID: $boleto_id");
            return ['status' => 'erro', 'mensagem' => 'Nosso número não definido para este boleto.'];
        }

        // Configurações da API do Itaú
        $client_id     = "8a7ee29a-f20d-43b8-b3f5-c559862669a9";
        $client_secret = "a6a29bfe-bec3-4619-b1c0-5653e6322ba0";
        $token_url     = "https://sts.itau.com.br/api/oauth/token"; // URL de teste confirmada
        
        // Nosso número (apenas dígitos, sem formatação)
        $nosso_numero = preg_replace('/[^0-9]/', '', $boleto['nosso_numero']);
        
        // URL de baixa conforme diretrizes
        $baixar_url    = "https://api.itau.com.br/cobranca/v2/boletos/$nosso_numero/baixas";
        
        $certFile      = __DIR__ . '/../../certificados/Certificado.crt';
        $keyFile       = __DIR__ . '/../../certificados/ARQUIVO_CHAVE_PRIVADA.key';

        // Log das informações
        error_log("Nosso número original: {$boleto['nosso_numero']}");
        error_log("Nosso número para API: $nosso_numero");
        error_log("URL de baixa: $baixar_url");

        // Verifica se os certificados existem
        if (!file_exists($certFile) || !file_exists($keyFile)) {
            error_log("Certificados não encontrados: $certFile e $keyFile");
            return ['status' => 'erro', 'mensagem' => 'Certificados não encontrados.'];
        }

        // Obter token de acesso
        error_log("Tentando obter token de acesso para baixar o boleto ID: $boleto_id");

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $token_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "grant_type=client_credentials&client_id=$client_id&client_secret=$client_secret",
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/x-www-form-urlencoded"
            ],
            CURLOPT_SSLCERT => $certFile,
            CURLOPT_SSLKEY => $keyFile,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_VERBOSE => true
        ]);

        // Executa a requisição
        error_log("Executando requisição para obter token de acesso");
        $response = curl_exec($curl);
        $err = curl_error($curl);
        $info = curl_getinfo($curl);

        // Log das informações da requisição
        error_log("Informações da requisição: " . json_encode($info));

        if ($err) {
            error_log("Erro ao obter token: $err");
            return ['status' => 'erro', 'mensagem' => 'Erro ao comunicar com a API do banco: ' . $err];
        }

        // Verifica o código de status HTTP
        $http_code = $info['http_code'];
        if ($http_code != 200) {
            error_log("Erro HTTP ao obter token: $http_code. Resposta: $response");
            return ['status' => 'erro', 'mensagem' => "Erro HTTP ao obter token: $http_code"];
        }

        // Decodifica a resposta
        $token_data = json_decode($response, true);
        if (!isset($token_data['access_token'])) {
            error_log("Resposta inválida ao obter token: $response");
            return ['status' => 'erro', 'mensagem' => 'Resposta inválida da API do banco ao obter token.'];
        }

        $access_token = $token_data['access_token'];
        error_log("Token de acesso obtido com sucesso");

        // Gerar UUID para correlation-id (obrigatório conforme diretrizes)
        $correlation_id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        // Preparar payload para baixa do boleto conforme diretrizes
        $payload = json_encode([
            'codigoBaixa' => 'OUTROS',
            'dataBaixa' => date('Y-m-d') // Data atual no formato ISO 8601 (AAAA-MM-DD)
        ]);

        error_log("Payload para baixa do boleto: $payload");
        error_log("Correlation ID: $correlation_id");

        // Configurações do cURL para baixar o boleto
        curl_setopt_array($curl, [
            CURLOPT_URL => $baixar_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer $access_token",
                "Content-Type: application/json",
                "x-itau-correlation-id: $correlation_id",
                "x-itau-flow-id: cobranca" // Obrigatório conforme diretrizes
            ],
            CURLOPT_SSLCERT => $certFile,
            CURLOPT_SSLKEY => $keyFile,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_VERBOSE => true
        ]);

        // Executa a requisição
        error_log("Executando requisição para baixar o boleto ID: $boleto_id, Nosso Número: $nosso_numero");
        $response = curl_exec($curl);
        $err = curl_error($curl);
        $info = curl_getinfo($curl);

        // Log das informações da requisição
        error_log("Informações da requisição de baixa: " . json_encode($info));
        error_log("Resposta da requisição de baixa: " . ($response ?: "Vazia"));

        curl_close($curl);

        if ($err) {
            error_log("Erro ao baixar boleto: $err");
            return ['status' => 'erro', 'mensagem' => 'Erro ao baixar boleto: ' . $err];
        }

        // Verifica o código de status HTTP
        $http_code = $info['http_code'];
        
        // Códigos de sucesso: 200 (OK) ou 204 (No Content) conforme diretrizes
        if ($http_code == 200 || $http_code == 204) {
            // Atualiza o status do boleto para cancelado
            try {
                $update_result = $db->update('boletos', [
                    'status' => 'cancelado', 
                    'data_cancelamento' => date('Y-m-d H:i:s'),
                    'observacoes' => ($boleto['observacoes'] ? $boleto['observacoes'] . "\n" : "") . 
                                    "Baixado via API do Itaú em " . date('d/m/Y H:i:s') . "\n" .
                                    "Nosso número: $nosso_numero\n" .
                                    "Código de baixa: OUTROS\n" .
                                    "Data de baixa: " . date('d/m/Y')
                ], 'id = ?', [$boleto_id]);

                if ($update_result === false) {
                    error_log("Erro ao atualizar o status do boleto ID: $boleto_id após baixa na API");
                    return ['status' => 'erro', 'mensagem' => 'Boleto baixado na API, mas houve erro ao atualizar o status no sistema.'];
                }

                // Registra a baixa na tabela de histórico
                try {
                    $db->insert('boletos_historico', [
                        'boleto_id' => $boleto_id,
                        'acao' => 'baixa_api',
                        'data' => date('Y-m-d H:i:s'),
                        'usuario_id' => isset($_SESSION['usuario']['id']) ? $_SESSION['usuario']['id'] : null,
                        'detalhes' => "Baixado via API do Itaú\nNosso número: $nosso_numero\nCódigo de baixa: OUTROS"
                    ]);
                } catch (Exception $e) {
                    error_log("Erro ao registrar histórico de baixa: " . $e->getMessage());
                    // Não retorna erro, apenas loga
                }

                error_log("Boleto baixado com sucesso via API: ID $boleto_id");
                return [
                    'status' => 'sucesso',
                    'mensagem' => 'Boleto baixado com sucesso.'
                ];
            } catch (Exception $e) {
                error_log("Erro ao atualizar o status do boleto após baixa na API: " . $e->getMessage());
                return ['status' => 'erro', 'mensagem' => 'Boleto baixado na API, mas houve erro ao atualizar o status no sistema: ' . $e->getMessage()];
            }
        } else {
            // Tratamento específico para cada código de erro conforme diretrizes
            $error_message = "Erro HTTP ao baixar boleto: $http_code.";
            
            switch ($http_code) {
                case 400:
                    $error_message .= " Parâmetros inválidos.";
                    break;
                case 401:
                    $error_message .= " Token inválido ou expirado.";
                    break;
                case 404:
                    $error_message .= " Nosso número não encontrado.";
                    break;
                case 409:
                    $error_message .= " Conflito: título já pago ou em outro estado que impede baixa.";
                    break;
                default:
                    if ($http_code >= 500) {
                        $error_message .= " Erro no servidor do banco.";
                    }
            }
            
            // Tenta decodificar a resposta para obter mais detalhes do erro
            if ($response) {
                $error_details = json_decode($response, true);
                if (json_last_error() === JSON_ERROR_NONE && isset($error_details['mensagem'])) {
                    $error_message .= " Mensagem: " . $error_details['mensagem'];
                }
            }
            
            error_log($error_message);
            return ['status' => 'erro', 'mensagem' => $error_message];
        }

    } catch (Exception $e) {
        error_log('Erro ao baixar boleto: ' . $e->getMessage());

        return [
            'status' => 'erro',
            'mensagem' => 'Erro ao baixar boleto: ' . $e->getMessage()
        ];
    }
}
?>
