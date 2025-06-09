<?php
/**
 * Função para verificar o status de um boleto no Itaú
 */

/**
 * Verifica o status de um boleto no Itaú
 * 
 * @param int $boleto_id ID do boleto a ser verificado
 * @param object $db Objeto de conexão com o banco de dados
 * @return array Resultado da operação
 */
function verificarStatusBoleto($boleto_id, $db) {
    try {
        // Log para depuração
        error_log("Iniciando verificação de status do boleto ID: $boleto_id");

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
        $token_url     = "https://sts.itau.com.br/api/oauth/token";
        
        // Formata o nosso número conforme o padrão do Itaú
        $nosso_numero = $boleto['nosso_numero'];
        
        // Tenta primeiro com o formato original
        $consulta_url  = "https://api.itau.com.br/cobranca/v2/boletos/{$nosso_numero}";
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

        // Log da URL de consulta
        error_log("URL de consulta: $consulta_url");
        error_log("Nosso número usado na consulta: " . $nosso_numero);

        // Chamar API de consulta
        $ch = curl_init($consulta_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPGET, true);
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
        error_log("Resposta bruta da API de consulta do Itaú (HTTP $httpcode): $response");
        if (!empty($curl_error)) {
            error_log("Erro cURL: $curl_error");
        }
        
        // Log dos detalhes da requisição
        error_log("Detalhes da requisição: " . json_encode($info));
        error_log("URL completa: " . $info['url']);
        error_log("Método HTTP: GET");
        error_log("Código de resposta: " . $info['http_code']);
        error_log("Tempo total: " . $info['total_time'] . " segundos");

        // Se a primeira tentativa falhar, tenta com o formato completo
        if ($httpcode != 200) {
            error_log("Consulta com formato original falhou. Tentando com formato completo...");
            
            // Formata o nosso número com o formato completo
            $nosso_numero_formatado = "109/" . $nosso_numero . "-2"; // Formato: 109/XXXXXXXX-2
            $consulta_url  = "https://api.itau.com.br/cobranca/v2/boletos/{$nosso_numero_formatado}";
            
            error_log("Nova URL de consulta: $consulta_url");
            error_log("Nosso número formatado: " . $nosso_numero_formatado);
            
            // Chamar API de consulta com formato completo
            $ch = curl_init($consulta_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPGET, true);
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
            error_log("Resposta bruta da API de consulta do Itaú (formato completo) (HTTP $httpcode): $response");
            if (!empty($curl_error)) {
                error_log("Erro cURL: $curl_error");
            }
            
            // Log dos detalhes da requisição
            error_log("Detalhes da requisição: " . json_encode($info));
            error_log("URL completa: " . $info['url']);
            error_log("Método HTTP: GET");
            error_log("Código de resposta: " . $info['http_code']);
            error_log("Tempo total: " . $info['total_time'] . " segundos");
        }

        // Processa a resposta
        if ($httpcode == 200) {
            // Consulta bem-sucedida
            $response_data = json_decode($response, true);
            
            // Extrai o status do boleto
            $status_boleto = 'desconhecido';
            $situacao_boleto = '';
            
            if (isset($response_data['situacao'])) {
                $situacao_boleto = $response_data['situacao'];
                
                // Mapeia a situação para um status
                switch (strtolower($situacao_boleto)) {
                    case 'aberto':
                    case 'pendente':
                        $status_boleto = 'pendente';
                        break;
                    case 'pago':
                    case 'liquidado':
                        $status_boleto = 'pago';
                        break;
                    case 'baixado':
                    case 'cancelado':
                        $status_boleto = 'cancelado';
                        break;
                    case 'vencido':
                        $status_boleto = 'vencido';
                        break;
                    default:
                        $status_boleto = 'desconhecido';
                }
            }
            
            error_log("Status do boleto no Itaú: $status_boleto (situação: $situacao_boleto)");
            
            // Atualiza o status do boleto no sistema se necessário
            if ($status_boleto != 'desconhecido' && $status_boleto != $boleto['status']) {
                error_log("Atualizando status do boleto de '{$boleto['status']}' para '$status_boleto'");
                
                $result = $db->update('boletos', [
                    'status' => $status_boleto,
                    'observacoes' => ($boleto['observacoes'] ? $boleto['observacoes'] . "\n" : "") . "Status atualizado via consulta à API do Itaú em " . date('d/m/Y H:i:s') . " (situação: $situacao_boleto)"
                ], 'id = ?', [$boleto_id]);
                
                if ($result === false) {
                    error_log("Erro ao atualizar o status do boleto ID: $boleto_id");
                }
            }
            
            return [
                'status' => 'sucesso',
                'mensagem' => 'Status do boleto consultado com sucesso.',
                'status_boleto' => $status_boleto,
                'situacao_boleto' => $situacao_boleto,
                'detalhes' => $response_data
            ];
        } else {
            // Erro na consulta
            error_log("Erro ao consultar status do boleto na API do Itaú: ID $boleto_id, HTTP $httpcode, Resposta: $response");
            
            return [
                'status' => 'erro',
                'mensagem' => 'Erro ao consultar status do boleto: ' . $response,
                'codigo_http' => $httpcode
            ];
        }
    } catch (Exception $e) {
        error_log('Erro ao verificar status do boleto: ' . $e->getMessage());

        return [
            'status' => 'erro',
            'mensagem' => 'Erro ao verificar status do boleto: ' . $e->getMessage()
        ];
    }
}
?>
