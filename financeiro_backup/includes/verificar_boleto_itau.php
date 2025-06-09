<?php
/**
 * Função para verificar se um boleto existe no Itaú
 */

/**
 * Verifica se um boleto existe no Itaú
 * 
 * @param int $boleto_id ID do boleto a ser verificado
 * @param object $db Objeto de conexão com o banco de dados
 * @return array Resultado da verificação
 */
function verificarBoletoItau($boleto_id, $db) {
    try {
        // Log para depuração
        error_log("Iniciando verificação do boleto no Itaú - ID: $boleto_id");

        // Busca os dados do boleto
        $boleto = $db->fetchOne("SELECT * FROM boletos WHERE id = ?", [$boleto_id]);

        if (!$boleto) {
            error_log("Boleto não encontrado: ID $boleto_id");
            return ['status' => 'erro', 'mensagem' => 'Boleto não encontrado.'];
        }

        // Log dos dados do boleto
        error_log("Dados do boleto: " . json_encode($boleto));

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
        
        // URL de consulta
        $consultar_url = "https://api.itau.com.br/cobranca/v2/boletos/$nosso_numero";
        
        $certFile      = __DIR__ . '/../../certificados/Certificado.crt';
        $keyFile       = __DIR__ . '/../../certificados/ARQUIVO_CHAVE_PRIVADA.key';

        // Log das informações
        error_log("Nosso número original: {$boleto['nosso_numero']}");
        error_log("Nosso número para API: $nosso_numero");
        error_log("URL de consulta: $consultar_url");

        // Verifica se os certificados existem
        if (!file_exists($certFile) || !file_exists($keyFile)) {
            error_log("Certificados não encontrados: $certFile e $keyFile");
            return ['status' => 'erro', 'mensagem' => 'Certificados não encontrados.'];
        }

        // Obter token de acesso
        error_log("Tentando obter token de acesso para verificar o boleto ID: $boleto_id");

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

        // Gerar UUID para correlation-id
        $correlation_id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        // Configurações do cURL para consultar o boleto
        curl_setopt_array($curl, [
            CURLOPT_URL => $consultar_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer $access_token",
                "Content-Type: application/json",
                "x-itau-correlation-id: $correlation_id",
                "x-itau-flow-id: cobranca"
            ],
            CURLOPT_SSLCERT => $certFile,
            CURLOPT_SSLKEY => $keyFile,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_VERBOSE => true
        ]);

        // Executa a requisição
        error_log("Executando requisição para consultar o boleto ID: $boleto_id, Nosso Número: $nosso_numero");
        $response = curl_exec($curl);
        $err = curl_error($curl);
        $info = curl_getinfo($curl);

        // Log das informações da requisição
        error_log("Informações da requisição de consulta: " . json_encode($info));
        error_log("Resposta da requisição de consulta: " . ($response ?: "Vazia"));

        curl_close($curl);

        if ($err) {
            error_log("Erro ao consultar boleto: $err");
            return ['status' => 'erro', 'mensagem' => 'Erro ao consultar boleto: ' . $err];
        }

        // Verifica o código de status HTTP
        $http_code = $info['http_code'];
        
        if ($http_code == 200) {
            // Boleto encontrado
            $boleto_data = json_decode($response, true);
            
            error_log("Boleto encontrado no Itaú: " . json_encode($boleto_data));
            
            return [
                'status' => 'sucesso',
                'mensagem' => 'Boleto encontrado no Itaú.',
                'dados' => $boleto_data
            ];
        } else if ($http_code == 404) {
            // Boleto não encontrado
            error_log("Boleto não encontrado no Itaú: Nosso Número $nosso_numero");
            
            // Tenta verificar se o boleto existe na API cash_management
            return verificarBoletoCashManagement($boleto_id, $db, $access_token, $certFile, $keyFile);
        } else {
            // Outro erro
            error_log("Erro HTTP ao consultar boleto: $http_code. Resposta: $response");
            
            // Tenta decodificar a resposta para obter mais detalhes do erro
            $error_message = "Erro HTTP ao consultar boleto: $http_code.";
            
            if ($response) {
                $error_details = json_decode($response, true);
                if (json_last_error() === JSON_ERROR_NONE && isset($error_details['mensagem'])) {
                    $error_message .= " Mensagem: " . $error_details['mensagem'];
                }
            }
            
            return ['status' => 'erro', 'mensagem' => $error_message];
        }

    } catch (Exception $e) {
        error_log('Erro ao verificar boleto: ' . $e->getMessage());

        return [
            'status' => 'erro',
            'mensagem' => 'Erro ao verificar boleto: ' . $e->getMessage()
        ];
    }
}

/**
 * Verifica se um boleto existe na API cash_management do Itaú
 * 
 * @param int $boleto_id ID do boleto a ser verificado
 * @param object $db Objeto de conexão com o banco de dados
 * @param string $access_token Token de acesso à API
 * @param string $certFile Caminho para o certificado
 * @param string $keyFile Caminho para a chave privada
 * @return array Resultado da verificação
 */
function verificarBoletoCashManagement($boleto_id, $db, $access_token, $certFile, $keyFile) {
    try {
        // Log para depuração
        error_log("Iniciando verificação do boleto na API cash_management - ID: $boleto_id");

        // Busca os dados do boleto
        $boleto = $db->fetchOne("SELECT * FROM boletos WHERE id = ?", [$boleto_id]);

        if (!$boleto) {
            error_log("Boleto não encontrado: ID $boleto_id");
            return ['status' => 'erro', 'mensagem' => 'Boleto não encontrado.'];
        }

        // Nosso número (apenas dígitos, sem formatação)
        $nosso_numero = preg_replace('/[^0-9]/', '', $boleto['nosso_numero']);
        
        // URL de consulta
        $consultar_url = "https://api.itau.com.br/cash_management/v2/boletos/$nosso_numero";
        
        // Log das informações
        error_log("Nosso número para API cash_management: $nosso_numero");
        error_log("URL de consulta cash_management: $consultar_url");

        // Gerar UUID para correlation-id
        $correlation_id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        // Configurações do cURL para consultar o boleto
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $consultar_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer $access_token",
                "Content-Type: application/json",
                "x-itau-correlation-id: $correlation_id",
                "x-itau-flow-id: cash_management"
            ],
            CURLOPT_SSLCERT => $certFile,
            CURLOPT_SSLKEY => $keyFile,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_VERBOSE => true
        ]);

        // Executa a requisição
        error_log("Executando requisição para consultar o boleto na API cash_management - ID: $boleto_id, Nosso Número: $nosso_numero");
        $response = curl_exec($curl);
        $err = curl_error($curl);
        $info = curl_getinfo($curl);

        // Log das informações da requisição
        error_log("Informações da requisição de consulta cash_management: " . json_encode($info));
        error_log("Resposta da requisição de consulta cash_management: " . ($response ?: "Vazia"));

        curl_close($curl);

        if ($err) {
            error_log("Erro ao consultar boleto na API cash_management: $err");
            return ['status' => 'erro', 'mensagem' => 'Boleto não encontrado em nenhuma API do Itaú.'];
        }

        // Verifica o código de status HTTP
        $http_code = $info['http_code'];
        
        if ($http_code == 200) {
            // Boleto encontrado na API cash_management
            $boleto_data = json_decode($response, true);
            
            error_log("Boleto encontrado na API cash_management: " . json_encode($boleto_data));
            
            // Atualiza o tipo de API no banco de dados
            try {
                $db->update('boletos', [
                    'api_tipo' => 'cash_management',
                    'observacoes' => ($boleto['observacoes'] ? $boleto['observacoes'] . "\n" : "") . 
                                    "Boleto encontrado na API cash_management em " . date('d/m/Y H:i:s')
                ], 'id = ?', [$boleto_id]);
            } catch (Exception $e) {
                error_log("Erro ao atualizar tipo de API do boleto: " . $e->getMessage());
                // Não interrompe o processo
            }
            
            return [
                'status' => 'sucesso',
                'mensagem' => 'Boleto encontrado na API cash_management do Itaú.',
                'dados' => $boleto_data,
                'api_tipo' => 'cash_management'
            ];
        } else {
            // Boleto não encontrado em nenhuma API
            error_log("Boleto não encontrado em nenhuma API do Itaú: Nosso Número $nosso_numero");
            
            return [
                'status' => 'erro',
                'mensagem' => 'Boleto não encontrado em nenhuma API do Itaú.'
            ];
        }

    } catch (Exception $e) {
        error_log('Erro ao verificar boleto na API cash_management: ' . $e->getMessage());

        return [
            'status' => 'erro',
            'mensagem' => 'Erro ao verificar boleto na API cash_management: ' . $e->getMessage()
        ];
    }
}
?>
