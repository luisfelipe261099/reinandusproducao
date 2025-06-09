<?php
// Inclui o arquivo de correção de nosso número
require_once __DIR__ . '/corrigir_nosso_numero.php';

/**
 * Função para consultar o status de um boleto via API do Itaú usando o formato exato
 * Implementação baseada no formato exato que aparece no sistema do Itaú: 109/XXXXXXXX-2
 *
 * @param string $nosso_numero Nosso número do boleto (formato original)
 * @param object $db Objeto de conexão com o banco de dados
 * @return array Resultado da operação com status e detalhes do boleto
 */
function consultarBoletoFormatoExato($nosso_numero, $db) {
    try {
        // Log para depuração
        error_log("Iniciando consulta do boleto com formato exato do Itaú. Nosso Número original: $nosso_numero");

        if (empty($nosso_numero)) {
            error_log("Nosso número não fornecido para consulta");
            return ['status' => 'erro', 'mensagem' => 'Nosso número não fornecido para consulta.'];
        }

        // Formata o nosso número no padrão exato do Itaú: 109/XXXXXXXX-2
        $nosso_numero_limpo = preg_replace('/[^0-9]/', '', $nosso_numero);

        // Se começar com 109, remove
        if (strpos($nosso_numero_limpo, '109') === 0) {
            $nosso_numero_limpo = substr($nosso_numero_limpo, 3);
        }

        // Pega os 8 dígitos principais
        if (strlen($nosso_numero_limpo) > 8) {
            $nosso_numero_limpo = substr($nosso_numero_limpo, -8);
        } else {
            $nosso_numero_limpo = str_pad($nosso_numero_limpo, 8, '0', STR_PAD_LEFT);
        }

        // Formata no padrão exato do Itaú
        $nosso_numero_formatado = "109/{$nosso_numero_limpo}-2";

        // Log para depuração
        error_log("Nosso número original: $nosso_numero");
        error_log("Nosso número formatado para consulta: $nosso_numero_formatado");

        // Configurações da API do Itaú
        $client_id     = "8a7ee29a-f20d-43b8-b3f5-c559862669a9";
        $client_secret = "a6a29bfe-bec3-4619-b1c0-5653e6322ba0";
        $token_url     = "https://sts.itau.com.br/api/oauth/token";
        $consultar_url = "https://api.itau.com.br/cobranca/v2/boletos/$nosso_numero_formatado";
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
        error_log("Executando requisição para consultar o boleto com Nosso Número formatado: $nosso_numero_formatado");
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
                'nosso_numero_formatado' => $nosso_numero_formatado
            ];
        }
        // Código 404 indica que o boleto não foi encontrado
        else if ($http_code == 404) {
            error_log("Boleto não encontrado na API: Nosso Número formatado $nosso_numero_formatado");
            return [
                'status' => 'nao_encontrado',
                'mensagem' => 'Boleto não encontrado na API do banco.',
                'nosso_numero_formatado' => $nosso_numero_formatado
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
                'nosso_numero_formatado' => $nosso_numero_formatado
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

/**
 * Função para consultar o status de um boleto via API do Itaú
 * Implementação baseada na documentação oficial da API de Cobrança v2 do Itaú
 *
 * @param string $nosso_numero Nosso número do boleto a ser consultado
 * @param object $db Objeto de conexão com o banco de dados
 * @return array Resultado da operação com status e detalhes do boleto
 */
function consultarBoletoBancario($nosso_numero, $db) {
    try {
        // Log para depuração
        error_log("Iniciando consulta do boleto com Nosso Número: $nosso_numero");

        if (empty($nosso_numero)) {
            error_log("Nosso número não fornecido para consulta");
            return ['status' => 'erro', 'mensagem' => 'Nosso número não fornecido para consulta.'];
        }

        // Corrige o formato do nosso número para o padrão exigido pela API
        $nosso_numero_original = $nosso_numero;
        $nosso_numero = corrigirFormatoNossoNumero($nosso_numero);

        // Log para depuração
        error_log("Nosso número original: $nosso_numero_original");
        error_log("Nosso número corrigido para consulta: $nosso_numero");

        // Configurações da API do Itaú
        $client_id     = "8a7ee29a-f20d-43b8-b3f5-c559862669a9";
        $client_secret = "a6a29bfe-bec3-4619-b1c0-5653e6322ba0";
        $token_url     = "https://sts.itau.com.br/api/oauth/token";
        $consultar_url = "https://api.itau.com.br/cobranca/v2/boletos/$nosso_numero";
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
        error_log("Executando requisição para consultar o boleto com Nosso Número: $nosso_numero");
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
                'dados' => $boleto_data
            ];
        }
        // Código 404 indica que o boleto não foi encontrado
        else if ($http_code == 404) {
            error_log("Boleto não encontrado na API: Nosso Número $nosso_numero");
            return [
                'status' => 'nao_encontrado',
                'mensagem' => 'Boleto não encontrado na API do banco.'
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
            return ['status' => 'erro', 'mensagem' => $error_message];
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
