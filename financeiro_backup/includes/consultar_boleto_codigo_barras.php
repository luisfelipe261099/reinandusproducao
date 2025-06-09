<?php
/**
 * Função para consultar o status de um boleto via API do Itaú usando o código de barras
 * Implementação baseada na documentação oficial da API de Cobrança v2 do Itaú
 */

/**
 * Consulta o boleto usando o código de barras
 * 
 * @param string $codigo_barras Código de barras do boleto
 * @param object $db Objeto de conexão com o banco de dados
 * @return array Resultado da operação
 */
function consultarBoletoCodigoBarras($codigo_barras, $db) {
    try {
        // Log para depuração
        error_log("Iniciando consulta do boleto com código de barras: $codigo_barras");

        if (empty($codigo_barras)) {
            error_log("Código de barras não fornecido para consulta");
            return ['status' => 'erro', 'mensagem' => 'Código de barras não fornecido para consulta.'];
        }

        // Remove espaços e caracteres não numéricos
        $codigo_barras = preg_replace('/[^0-9]/', '', $codigo_barras);

        // Verifica se o código de barras tem o tamanho correto (44 dígitos)
        if (strlen($codigo_barras) != 44) {
            error_log("Código de barras inválido: $codigo_barras (tamanho: " . strlen($codigo_barras) . ")");
            return ['status' => 'erro', 'mensagem' => 'Código de barras inválido. Deve ter 44 dígitos.'];
        }

        // Configurações da API do Itaú
        $client_id     = "8a7ee29a-f20d-43b8-b3f5-c559862669a9";
        $client_secret = "a6a29bfe-bec3-4619-b1c0-5653e6322ba0";
        $token_url     = "https://sts.itau.com.br/api/oauth/token";
        $consultar_url = "https://api.itau.com.br/cobranca/v2/boletos/consulta-codigo-barras";
        $certFile      = __DIR__ . '/../../certificados/Certificado.crt';
        $keyFile       = __DIR__ . '/../../certificados/ARQUIVO_CHAVE_PRIVADA.key';

        // Verifica se os certificados existem
        if (!file_exists($certFile) || !file_exists($keyFile)) {
            error_log("Certificados não encontrados. Não é possível consultar o boleto.");
            error_log("Caminho do certificado: $certFile");
            error_log("Caminho da chave privada: $keyFile");
            return ['status' => 'erro', 'mensagem' => 'Certificados não encontrados. Não é possível consultar o boleto.'];
        }

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

        // Obter token de acesso
        error_log("Tentando obter token de acesso para consultar o boleto");

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

        // Gerar UUID para correlation-id
        $correlation_id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        // Preparar o payload para a consulta
        $payload = json_encode([
            'codigoBarras' => $codigo_barras
        ]);

        // Configurações do cURL para consultar o boleto
        curl_setopt_array($curl, [
            CURLOPT_URL => $consultar_url,
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
                "x-itau-flow-id: cobranca"
            ],
            CURLOPT_SSLCERT => $certFile,
            CURLOPT_SSLKEY => $keyFile,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_VERBOSE => true
        ]);

        // Executa a requisição
        error_log("Executando requisição para consultar o boleto com código de barras: $codigo_barras");
        $response = curl_exec($curl);
        $err = curl_error($curl);
        $info = curl_getinfo($curl);

        // Log das informações da requisição
        error_log("Informações da requisição de consulta: " . json_encode($info));
        error_log("Resposta da requisição de consulta: " . $response);

        curl_close($curl);

        if ($err) {
            error_log("Erro ao consultar boleto: $err");
            return ['status' => 'erro', 'mensagem' => 'Erro ao consultar boleto: ' . $err];
        }

        // Verifica o código de status HTTP
        $http_code = $info['http_code'];

        // Código 200 indica sucesso na consulta
        if ($http_code == 200) {
            // Decodifica a resposta
            $boleto_data = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("Erro ao decodificar resposta JSON: " . json_last_error_msg() . ". Resposta: $response");
                return ['status' => 'erro', 'mensagem' => 'Erro ao decodificar resposta JSON: ' . json_last_error_msg()];
            }

            error_log("Boleto consultado com sucesso: " . json_encode($boleto_data));
            return [
                'status' => 'sucesso',
                'mensagem' => 'Boleto consultado com sucesso.',
                'dados' => $boleto_data,
                'codigo_barras' => $codigo_barras
            ];
        }
        // Código 404 indica que o boleto não foi encontrado
        else if ($http_code == 404) {
            error_log("Boleto não encontrado na API: Código de barras $codigo_barras");
            return [
                'status' => 'nao_encontrado',
                'mensagem' => 'Boleto não encontrado na API do banco.',
                'codigo_barras' => $codigo_barras
            ];
        }
        // Outros códigos de erro
        else {
            error_log("Erro HTTP ao consultar boleto: $http_code. Resposta: $response");

            // Tenta decodificar a resposta para obter mais detalhes do erro
            $error_details = json_decode($response, true);
            $error_message = "Erro HTTP ao consultar boleto: $http_code.";

            if (json_last_error() === JSON_ERROR_NONE && isset($error_details['mensagem'])) {
                $error_message .= " Mensagem: " . $error_details['mensagem'];
            }

            error_log($error_message);
            return [
                'status' => 'erro',
                'mensagem' => $error_message,
                'codigo_barras' => $codigo_barras
            ];
        }

    } catch (Exception $e) {
        error_log('Erro ao consultar boleto: ' . $e->getMessage());

        return [
            'status' => 'erro',
            'mensagem' => 'Erro ao consultar boleto: ' . $e->getMessage()
        ];
    }
}
?>
