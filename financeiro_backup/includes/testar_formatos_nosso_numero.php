<?php
/**
 * Função para testar diferentes formatos do nosso número na API do Itaú
 */

// Inclui o arquivo de configurações
require_once __DIR__ . '/config_helper.php';

/**
 * Testa diferentes formatos do nosso número na API do Itaú
 * 
 * @param string $nosso_numero Nosso número original
 * @param string $access_token Token de acesso à API
 * @param string $certFile Caminho para o certificado
 * @param string $keyFile Caminho para a chave privada
 * @param string $api_type Tipo da API (cash_management ou cobranca)
 * @param object $db Objeto de conexão com o banco de dados
 * @return array Resultado do teste com o formato correto, se encontrado
 */
function testarFormatosNossoNumero($nosso_numero, $access_token, $certFile, $keyFile, $api_type = 'cash_management', $db = null) {
    // Log para depuração
    error_log("Testando diferentes formatos do nosso número: $nosso_numero");
    
    // Se não foi passado um objeto de conexão, cria um
    if ($db === null) {
        $db = Database::getInstance();
    }
    
    // Obtém a URL base da API
    $base_url = obterUrlApiItau($api_type, $db);
    
    // Variações a testar
    $variacoes = [
        'original' => $nosso_numero,
        'sem_zeros' => ltrim($nosso_numero, '0'),
        'com_zeros' => str_pad($nosso_numero, 8, '0', STR_PAD_LEFT),
        'ultimos_8' => substr(preg_replace('/[^0-9]/', '', $nosso_numero), -8),
        'primeiros_8' => substr(preg_replace('/[^0-9]/', '', $nosso_numero), 0, 8),
        'sem_digito' => preg_replace('/[^0-9]/', '', $nosso_numero),
        'com_prefixo_109' => '109' . str_pad($nosso_numero, 8, '0', STR_PAD_LEFT),
        'com_prefixo_109_sem_zeros' => '109' . ltrim($nosso_numero, '0'),
        'com_prefixo_109_ultimos_8' => '109' . substr(preg_replace('/[^0-9]/', '', $nosso_numero), -8),
        'com_prefixo_109_primeiros_8' => '109' . substr(preg_replace('/[^0-9]/', '', $nosso_numero), 0, 8),
        'com_prefixo_109_sem_digito' => '109' . preg_replace('/[^0-9]/', '', $nosso_numero),
    ];
    
    // Adiciona variações com dígito verificador
    if (strlen($nosso_numero) >= 8) {
        $variacoes['com_dv'] = substr($nosso_numero, 0, 8) . calcularDigitoVerificadorItau(substr($nosso_numero, 0, 8));
        $variacoes['ultimos_8_com_dv'] = substr(preg_replace('/[^0-9]/', '', $nosso_numero), -8) . 
                                        calcularDigitoVerificadorItau(substr(preg_replace('/[^0-9]/', '', $nosso_numero), -8));
    }
    
    // Resultados
    $resultados = [];
    
    // Testa cada variação
    foreach ($variacoes as $descricao => $variacao) {
        // Log para depuração
        error_log("Testando variação '$descricao': $variacao");
        
        // URL de consulta
        $consultar_url = "$base_url/$variacao";
        
        // Log da URL
        error_log("URL de consulta: $consultar_url");
        
        // Gerar UUID para correlation-id
        $correlation_id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
        
        // Configurações do cURL
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
                "x-itau-flow-id: cobranca"
            ],
            CURLOPT_SSLCERT => $certFile,
            CURLOPT_SSLKEY => $keyFile,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_VERBOSE => true
        ]);
        
        // Executa a requisição
        $response = curl_exec($curl);
        $err = curl_error($curl);
        $info = curl_getinfo($curl);
        
        // Log das informações da requisição
        error_log("Informações da requisição: " . json_encode($info));
        
        if ($err) {
            error_log("Erro ao consultar boleto com variação '$descricao': $err");
            $resultados[$descricao] = [
                'status' => 'erro',
                'mensagem' => "Erro ao consultar boleto: $err",
                'variacao' => $variacao
            ];
        } else {
            $http_code = $info['http_code'];
            if ($http_code == 200) {
                error_log("Boleto encontrado com variação '$descricao': $variacao");
                
                // Decodifica a resposta
                $boleto_data = json_decode($response, true);
                
                $resultados[$descricao] = [
                    'status' => 'sucesso',
                    'mensagem' => "Boleto encontrado com a variação '$variacao'! Situação: " . ($boleto_data['situacao'] ?? 'Não informada'),
                    'variacao' => $variacao,
                    'dados' => $boleto_data
                ];
                
                // Retorna imediatamente o primeiro formato que funcionar
                curl_close($curl);
                return [
                    'status' => 'sucesso',
                    'formato_encontrado' => $descricao,
                    'nosso_numero' => $variacao,
                    'dados' => $boleto_data
                ];
            } else {
                error_log("Boleto não encontrado com variação '$descricao': HTTP $http_code");
                $resultados[$descricao] = [
                    'status' => 'erro',
                    'mensagem' => "Boleto não encontrado com a variação '$variacao'",
                    'variacao' => $variacao,
                    'http_code' => $http_code
                ];
            }
        }
    }
    
    // Se chegou aqui, nenhum formato funcionou
    curl_close($curl);
    
    return [
        'status' => 'erro',
        'mensagem' => 'Nenhuma variação do nosso número foi encontrada na API.',
        'resultados' => $resultados
    ];
}

/**
 * Calcula o dígito verificador do nosso número no padrão Itaú
 * 
 * @param string $nosso_numero Nosso número (8 dígitos)
 * @return int Dígito verificador
 */
function calcularDigitoVerificadorItau($nosso_numero) {
    // Garante que o nosso número tenha 8 dígitos
    $nosso_numero = str_pad($nosso_numero, 8, '0', STR_PAD_LEFT);
    
    // Agência e conta (fixos para a Faciência)
    $agencia = '0978';
    $conta = '27155';
    
    // Carteira
    $carteira = '109';
    
    // Concatena agência + conta + carteira + nosso número
    $numero_completo = $agencia . $conta . $carteira . $nosso_numero;
    
    // Pesos para o cálculo do dígito verificador
    $pesos = [2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2];
    
    // Calcula a soma ponderada
    $soma = 0;
    for ($i = 0; $i < strlen($numero_completo); $i++) {
        $produto = (int)$numero_completo[$i] * $pesos[$i];
        $soma += ($produto > 9) ? intval($produto / 10) + ($produto % 10) : $produto;
    }
    
    // Calcula o dígito verificador
    $resto = $soma % 10;
    $dv = ($resto == 0) ? 0 : 10 - $resto;
    
    return $dv;
}
?>
