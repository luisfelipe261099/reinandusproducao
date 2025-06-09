<?php
/**
 * Funções para formatação e validação do Nosso Número para a API do Itaú
 */

/**
 * Gera variações do nosso número para tentar diferentes formatos na API
 * 
 * @param string $nosso_numero Nosso número original
 * @return array Array com diferentes formatos do nosso número
 */
function gerarVariacoesNossoNumero($nosso_numero) {
    // Remove caracteres não numéricos
    $nosso_numero_limpo = preg_replace('/[^0-9]/', '', $nosso_numero);
    
    // Inicializa o array de variações
    $variacoes = [];
    
    // Adiciona o nosso número original
    $variacoes[] = $nosso_numero;
    
    // Adiciona o nosso número limpo (apenas números)
    if ($nosso_numero_limpo !== $nosso_numero) {
        $variacoes[] = $nosso_numero_limpo;
    }
    
    // Adiciona o nosso número com 8 dígitos (padrão da carteira 109 do Itaú)
    if (strlen($nosso_numero_limpo) != 8) {
        $variacoes[] = str_pad($nosso_numero_limpo, 8, '0', STR_PAD_LEFT);
    }
    
    // Adiciona o nosso número sem zeros à esquerda
    $sem_zeros = ltrim($nosso_numero_limpo, '0');
    if ($sem_zeros !== $nosso_numero_limpo) {
        $variacoes[] = $sem_zeros;
    }
    
    // Adiciona o nosso número com prefixo da carteira (109)
    if (strpos($nosso_numero, '109') !== 0) {
        $variacoes[] = '109' . $nosso_numero_limpo;
    }
    
    // Adiciona o nosso número sem prefixo da carteira
    if (strpos($nosso_numero, '109') === 0) {
        $variacoes[] = substr($nosso_numero, 3);
    }
    
    // Adiciona o nosso número com DAC (dígito verificador)
    // O DAC é calculado usando o módulo 10 sobre o nosso número
    $dac = calcularModulo10($nosso_numero_limpo);
    $variacoes[] = $nosso_numero_limpo . $dac;
    
    // Adiciona o nosso número com agência e conta
    $variacoes[] = '0978' . $nosso_numero_limpo; // Agência 0978
    $variacoes[] = '27155' . $nosso_numero_limpo; // Conta 27155
    
    // Remove duplicatas e retorna
    return array_unique($variacoes);
}

/**
 * Calcula o dígito verificador usando o módulo 10
 * 
 * @param string $num Número para calcular o dígito verificador
 * @return int Dígito verificador
 */
function calcularModulo10($num) {
    $soma = 0;
    $peso = 2;
    
    // Percorre o número da direita para a esquerda
    for ($i = strlen($num) - 1; $i >= 0; $i--) {
        $resultado = (int)$num[$i] * $peso;
        
        // Se o resultado for maior que 9, soma os algarismos
        if ($resultado > 9) {
            $resultado = (int)($resultado / 10) + ($resultado % 10);
        }
        
        $soma += $resultado;
        $peso = $peso == 2 ? 1 : 2;
    }
    
    $resto = $soma % 10;
    return $resto == 0 ? 0 : 10 - $resto;
}

/**
 * Consulta o boleto em diferentes formatos de nosso número
 * 
 * @param string $nosso_numero_original Nosso número original
 * @param object $db Objeto de conexão com o banco de dados
 * @return array Resultado da consulta
 */
function consultarBoletoMultiFormato($nosso_numero_original, $db) {
    // Inclui a função de consulta
    require_once __DIR__ . '/consultar_boleto_api.php';
    
    // Gera variações do nosso número
    $variacoes = gerarVariacoesNossoNumero($nosso_numero_original);
    
    // Log das variações
    error_log("Tentando consultar boleto com diferentes formatos de nosso número. Original: $nosso_numero_original");
    error_log("Variações geradas: " . json_encode($variacoes));
    
    // Tenta consultar com cada variação
    foreach ($variacoes as $nosso_numero) {
        error_log("Tentando consultar com nosso número: $nosso_numero");
        
        $resultado = consultarBoletoBancario($nosso_numero, $db);
        
        // Se encontrou o boleto, retorna o resultado
        if ($resultado['status'] === 'sucesso') {
            error_log("Boleto encontrado com nosso número: $nosso_numero");
            
            // Adiciona o nosso número usado para encontrar o boleto
            $resultado['nosso_numero_encontrado'] = $nosso_numero;
            
            return $resultado;
        }
    }
    
    // Se não encontrou com nenhuma variação, retorna não encontrado
    error_log("Boleto não encontrado com nenhuma variação do nosso número");
    return [
        'status' => 'nao_encontrado',
        'mensagem' => 'Boleto não encontrado na API do banco com nenhuma variação do nosso número.'
    ];
}

/**
 * Baixa o boleto em diferentes formatos de nosso número
 * 
 * @param string $nosso_numero_original Nosso número original
 * @param object $db Objeto de conexão com o banco de dados
 * @param int $boleto_id ID do boleto no sistema
 * @return array Resultado da baixa
 */
function baixarBoletoMultiFormato($nosso_numero_original, $db, $boleto_id) {
    // Inclui as funções necessárias
    require_once __DIR__ . '/consultar_boleto_api.php';
    
    // Gera variações do nosso número
    $variacoes = gerarVariacoesNossoNumero($nosso_numero_original);
    
    // Log das variações
    error_log("Tentando baixar boleto com diferentes formatos de nosso número. Original: $nosso_numero_original");
    error_log("Variações geradas: " . json_encode($variacoes));
    
    // Primeiro, tenta consultar para ver se o boleto existe
    foreach ($variacoes as $nosso_numero) {
        error_log("Tentando consultar com nosso número: $nosso_numero");
        
        $resultado_consulta = consultarBoletoBancario($nosso_numero, $db);
        
        // Se encontrou o boleto, tenta baixá-lo
        if ($resultado_consulta['status'] === 'sucesso') {
            error_log("Boleto encontrado com nosso número: $nosso_numero. Tentando baixar...");
            
            // Configurações da API do Itaú
            $client_id     = "8a7ee29a-f20d-43b8-b3f5-c559862669a9";
            $client_secret = "a6a29bfe-bec3-4619-b1c0-5653e6322ba0";
            $token_url     = "https://sts.itau.com.br/api/oauth/token";
            $baixar_url    = "https://api.itau.com.br/cobranca/v2/boletos/$nosso_numero/baixas";
            $certFile      = __DIR__ . '/../../certificados/Certificado.crt';
            $keyFile       = __DIR__ . '/../../certificados/ARQUIVO_CHAVE_PRIVADA.key';
            
            // Verifica se os certificados existem
            if (!file_exists($certFile) || !file_exists($keyFile)) {
                error_log("Certificados não encontrados. Não é possível baixar o boleto.");
                continue; // Tenta a próxima variação
            }
            
            // Verifica se o cURL está disponível
            if (!function_exists('curl_init')) {
                error_log("cURL não está disponível no servidor");
                continue; // Tenta a próxima variação
            }
            
            $curl = curl_init();
            if ($curl === false) {
                error_log("Falha ao inicializar cURL");
                continue; // Tenta a próxima variação
            }
            
            // Obter token de acesso
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
            
            $response = curl_exec($curl);
            $err = curl_error($curl);
            $info = curl_getinfo($curl);
            
            if ($err || $info['http_code'] != 200) {
                error_log("Erro ao obter token: $err. HTTP Code: " . $info['http_code']);
                continue; // Tenta a próxima variação
            }
            
            $token_data = json_decode($response, true);
            if (!isset($token_data['access_token'])) {
                error_log("Resposta inválida ao obter token");
                continue; // Tenta a próxima variação
            }
            
            $access_token = $token_data['access_token'];
            
            // Gerar UUID para correlation-id
            $correlation_id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
            
            // Preparar payload para baixa do boleto
            $payload = json_encode([
                'codigoBaixa' => 'OUTROS',
                'dataBaixa' => date('Y-m-d') // Data atual no formato ISO 8601 (AAAA-MM-DD)
            ]);
            
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
                    "x-itau-flow-id: cobranca"
                ],
                CURLOPT_SSLCERT => $certFile,
                CURLOPT_SSLKEY => $keyFile,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_VERBOSE => true
            ]);
            
            $response = curl_exec($curl);
            $err = curl_error($curl);
            $info = curl_getinfo($curl);
            
            curl_close($curl);
            
            // Códigos de sucesso: 200 (OK) ou 204 (No Content)
            if ($info['http_code'] == 200 || $info['http_code'] == 204) {
                error_log("Boleto baixado com sucesso usando nosso número: $nosso_numero");
                
                // Atualiza o boleto no banco de dados
                try {
                    // Busca o boleto atual
                    $boleto = $db->fetchOne("SELECT * FROM boletos WHERE id = ?", [$boleto_id]);
                    
                    // Atualiza o status e o nosso número (se for diferente)
                    $dados_update = [
                        'status' => 'cancelado',
                        'data_cancelamento' => date('Y-m-d H:i:s'),
                        'observacoes' => ($boleto['observacoes'] ? $boleto['observacoes'] . "\n" : "") . 
                                        "Baixado via API em " . date('d/m/Y H:i:s') . 
                                        ($nosso_numero !== $nosso_numero_original ? " (Nosso número corrigido: $nosso_numero)" : "")
                    ];
                    
                    // Se o nosso número usado for diferente do original, atualiza
                    if ($nosso_numero !== $nosso_numero_original) {
                        $dados_update['nosso_numero'] = $nosso_numero;
                    }
                    
                    $result = $db->update('boletos', $dados_update, 'id = ?', [$boleto_id]);
                    
                    if ($result === false) {
                        error_log("Erro ao atualizar o status do boleto ID: $boleto_id");
                        return ['status' => 'erro', 'mensagem' => 'Boleto baixado na API, mas houve erro ao atualizar o status no sistema.'];
                    }
                    
                    // Registra o histórico
                    $db->insert('boletos_historico', [
                        'boleto_id' => $boleto_id,
                        'acao' => 'baixa_api',
                        'data' => date('Y-m-d H:i:s'),
                        'usuario_id' => isset($_SESSION['usuario']['id']) ? $_SESSION['usuario']['id'] : null,
                        'detalhes' => 'Baixado via API do Itaú' . 
                                    ($nosso_numero !== $nosso_numero_original ? " (Nosso número corrigido: $nosso_numero)" : "")
                    ]);
                    
                    return [
                        'status' => 'sucesso',
                        'mensagem' => 'Boleto baixado com sucesso.' . 
                                    ($nosso_numero !== $nosso_numero_original ? " (Nosso número corrigido: $nosso_numero)" : ""),
                        'nosso_numero_usado' => $nosso_numero
                    ];
                } catch (Exception $e) {
                    error_log("Erro ao atualizar o status do boleto: " . $e->getMessage());
                    return ['status' => 'erro', 'mensagem' => 'Boleto baixado na API, mas houve erro ao atualizar o status no sistema: ' . $e->getMessage()];
                }
            } else {
                error_log("Erro ao baixar boleto com nosso número $nosso_numero: HTTP " . $info['http_code'] . " - $response");
            }
        }
    }
    
    // Se não conseguiu baixar com nenhuma variação, retorna erro
    error_log("Não foi possível baixar o boleto com nenhuma variação do nosso número");
    return [
        'status' => 'nao_encontrado',
        'mensagem' => 'Não foi possível baixar o boleto. O boleto não foi encontrado na API do banco com nenhuma variação do nosso número.'
    ];
}
?>
