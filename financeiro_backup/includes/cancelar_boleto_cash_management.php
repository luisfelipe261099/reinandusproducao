<?php
// Inclui o arquivo de configurações
require_once __DIR__ . '/config_helper.php';

/**
 * Função para cancelar um boleto via API cash_management do Itaú
 * Esta função usa a mesma API que foi usada para gerar o boleto
 *
 * @param int $boleto_id ID do boleto a ser cancelado
 * @param object $db Objeto de conexão com o banco de dados
 * @return array Resultado da operação
 */
function cancelarBoletoCashManagement($boleto_id, $db) {
    try {
        // Log para depuração
        error_log("Iniciando cancelamento do boleto via API cash_management - ID: $boleto_id");

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

        // Configurações da API do Itaú - EXATAMENTE as mesmas usadas na geração
        $client_id     = "8a7ee29a-f20d-43b8-b3f5-c559862669a9";
        $client_secret = "a6a29bfe-bec3-4619-b1c0-5653e6322ba0";

        // Obtém as URLs com base no ambiente configurado
        $token_url     = obterUrlTokenItau($db);
        $base_url      = obterUrlApiItau('cash_management', $db);
        $cancelar_url  = $base_url . "/" . $boleto['nosso_numero'] . "/cancelamento";

        // Obtém o ambiente atual para registro
        $ambiente      = obterConfiguracao('api_itau_ambiente', 'teste', $db);

        $certFile      = __DIR__ . '/../../certificados/Certificado.crt';
        $keyFile       = __DIR__ . '/../../certificados/ARQUIVO_CHAVE_PRIVADA.key';

        // Log das URLs
        error_log("Ambiente da API: $ambiente");
        error_log("URL do token: $token_url");
        error_log("URL de cancelamento: $cancelar_url");

        // Verifica se os certificados existem
        if (!file_exists($certFile) || !file_exists($keyFile)) {
            error_log("Certificados não encontrados: $certFile e $keyFile");
            return ['status' => 'erro', 'mensagem' => 'Certificados não encontrados.'];
        }

        // Obter token de acesso
        error_log("Tentando obter token de acesso para cancelar o boleto ID: $boleto_id");

        // Verifica se o cURL está disponível
        if (!function_exists('curl_init')) {
            error_log("cURL não está disponível no servidor");
            return ['status' => 'erro', 'mensagem' => 'cURL não está disponível no servidor.'];
        }

        $curl = curl_init();
        if ($curl === false) {
            error_log("Falha ao inicializar cURL");
            return ['status' => 'erro', 'mensagem' => 'Falha ao inicializar cURL.'];
        }

        // Configurações do cURL para obter o token
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
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("Erro ao decodificar resposta JSON: " . json_last_error_msg() . ". Resposta: $response");
            return ['status' => 'erro', 'mensagem' => 'Erro ao decodificar resposta JSON: ' . json_last_error_msg()];
        }

        if (!isset($token_data['access_token'])) {
            error_log("Resposta inválida ao obter token: $response");
            return ['status' => 'erro', 'mensagem' => 'Resposta inválida da API do banco ao obter token.'];
        }

        $access_token = $token_data['access_token'];
        error_log("Token de acesso obtido com sucesso");

        // Cancelar o boleto
        error_log("Tentando cancelar o boleto ID: $boleto_id via API cash_management");
        error_log("URL de cancelamento: $cancelar_url");

        // Configurações do cURL para cancelar o boleto
        curl_setopt_array($curl, [
            CURLOPT_URL => $cancelar_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "", // Não precisa de payload para cancelamento
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer $access_token",
                "Content-Type: application/json"
            ],
            CURLOPT_SSLCERT => $certFile,
            CURLOPT_SSLKEY => $keyFile,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_VERBOSE => true
        ]);

        // Executa a requisição
        error_log("Executando requisição para cancelar o boleto ID: $boleto_id, Nosso Número: {$boleto['nosso_numero']}");
        $response = curl_exec($curl);
        $err = curl_error($curl);
        $info = curl_getinfo($curl);

        // Log das informações da requisição
        error_log("Informações da requisição de cancelamento: " . json_encode($info));
        error_log("Resposta da requisição de cancelamento: $response");

        curl_close($curl);

        if ($err) {
            error_log("Erro ao cancelar boleto: $err");
            return ['status' => 'erro', 'mensagem' => 'Erro ao cancelar boleto: ' . $err];
        }

        // Verifica o código de status HTTP
        $http_code = $info['http_code'];

        // Códigos de sucesso: 200 (OK) ou 204 (No Content)
        if ($http_code == 200 || $http_code == 204) {
            // Atualiza o status do boleto para cancelado
            try {
                $update_result = $db->update('boletos', [
                    'status' => 'cancelado',
                    'data_cancelamento' => date('Y-m-d H:i:s'),
                    'observacoes' => ($boleto['observacoes'] ? $boleto['observacoes'] . "\n" : "") .
                                    "Cancelado via API cash_management em " . date('d/m/Y H:i:s')
                ], 'id = ?', [$boleto_id]);

                if ($update_result === false) {
                    error_log("Erro ao atualizar o status do boleto ID: $boleto_id após cancelamento na API");
                    return ['status' => 'erro', 'mensagem' => 'Boleto cancelado na API, mas houve erro ao atualizar o status no sistema.'];
                }

                // Registra o cancelamento na tabela de histórico
                try {
                    $db->insert('boletos_historico', [
                        'boleto_id' => $boleto_id,
                        'acao' => 'cancelamento_api_cash_management',
                        'data' => date('Y-m-d H:i:s'),
                        'usuario_id' => isset($_SESSION['usuario']['id']) ? $_SESSION['usuario']['id'] : null,
                        'detalhes' => 'Cancelado via API cash_management do Itaú'
                    ]);
                } catch (Exception $e) {
                    error_log("Erro ao registrar histórico de cancelamento: " . $e->getMessage());
                    // Não retorna erro, apenas loga
                }

                error_log("Boleto cancelado com sucesso via API cash_management: ID $boleto_id");
                return [
                    'status' => 'sucesso',
                    'mensagem' => 'Boleto cancelado com sucesso.'
                ];
            } catch (Exception $e) {
                error_log("Erro ao atualizar o status do boleto após cancelamento na API: " . $e->getMessage());
                return ['status' => 'erro', 'mensagem' => 'Boleto cancelado na API, mas houve erro ao atualizar o status no sistema: ' . $e->getMessage()];
            }
        } else {
            error_log("Erro HTTP ao cancelar boleto: $http_code. Resposta: $response");

            // Tenta decodificar a resposta para obter mais detalhes do erro
            $error_details = json_decode($response, true);
            $error_message = "Erro HTTP ao cancelar boleto: $http_code.";

            if (json_last_error() === JSON_ERROR_NONE && isset($error_details['mensagem'])) {
                $error_message .= " Mensagem: " . $error_details['mensagem'];
            }

            return ['status' => 'erro', 'mensagem' => $error_message];
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
