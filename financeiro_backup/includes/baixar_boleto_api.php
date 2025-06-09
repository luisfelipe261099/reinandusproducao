<?php
// Inclui os arquivos necessários
require_once __DIR__ . '/consultar_boleto_api.php';

/**
 * Função para baixar (cancelar) um boleto via API do Itaú
 * Implementação baseada na documentação oficial da API de Cobrança v2 do Itaú
 *
 * @param int $boleto_id ID do boleto a ser cancelado
 * @param object $db Objeto de conexão com o banco de dados
 * @param bool $apenas_local Se true, cancela apenas no sistema local sem tentar a API
 * @return array Resultado da operação
 */
function baixarBoletoBancario($boleto_id, $db, $apenas_local = false) {
    try {
        // Log para depuração
        error_log("Iniciando baixa do boleto ID: $boleto_id" . ($apenas_local ? " (apenas local)" : ""));

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

        // Se for para cancelar apenas localmente, pula a chamada à API
        if ($apenas_local) {
            error_log("Cancelando boleto apenas localmente por solicitação do usuário: ID $boleto_id");

            try {
                $result = $db->update('boletos', [
                    'status' => 'cancelado',
                    'data_cancelamento' => date('Y-m-d H:i:s'),
                    'observacoes' => ($boleto['observacoes'] ? $boleto['observacoes'] . "\n" : "") . "Cancelado apenas no sistema interno em " . date('d/m/Y H:i:s')
                ], 'id = ?', [$boleto_id]);

                if ($result === false) {
                    error_log("Erro ao atualizar o status do boleto ID: $boleto_id");
                    return ['status' => 'erro', 'mensagem' => 'Erro ao atualizar o status do boleto.'];
                }

                // Registra o cancelamento na tabela de histórico
                try {
                    $db->insert('boletos_historico', [
                        'boleto_id' => $boleto_id,
                        'acao' => 'cancelamento_local',
                        'data' => date('Y-m-d H:i:s'),
                        'usuario_id' => isset($_SESSION['usuario']['id']) ? $_SESSION['usuario']['id'] : null,
                        'detalhes' => 'Cancelado apenas no sistema interno por solicitação do usuário'
                    ]);
                } catch (Exception $e) {
                    error_log("Erro ao registrar histórico de cancelamento: " . $e->getMessage());
                    // Não retorna erro, apenas loga
                }

                error_log("Boleto cancelado com sucesso (apenas no sistema interno): ID $boleto_id");
                return [
                    'status' => 'sucesso',
                    'mensagem' => 'Boleto cancelado com sucesso apenas no sistema interno. ATENÇÃO: O boleto continua ativo no banco!'
                ];
            } catch (Exception $e) {
                error_log("Erro ao atualizar o status do boleto: " . $e->getMessage());
                return ['status' => 'erro', 'mensagem' => 'Erro ao atualizar o status do boleto: ' . $e->getMessage()];
            }
        }

        // Corrige o formato do nosso número para o padrão exigido pela API
        $nosso_numero_original = $boleto['nosso_numero'];
        $nosso_numero_corrigido = corrigirFormatoNossoNumero($nosso_numero_original);

        // Formata o nosso número no padrão visual do Itaú (para logs e depuração)
        $nosso_numero_formatado = formatarNossoNumeroItau($nosso_numero_original);

        // Log para depuração
        error_log("Nosso número original: $nosso_numero_original");
        error_log("Nosso número corrigido para API: $nosso_numero_corrigido");
        error_log("Nosso número formatado Itaú: $nosso_numero_formatado");

        // Configurações da API do Itaú
        $client_id     = "8a7ee29a-f20d-43b8-b3f5-c559862669a9";
        $client_secret = "a6a29bfe-bec3-4619-b1c0-5653e6322ba0";
        $token_url     = "https://sts.itau.com.br/api/oauth/token";
        $baixar_url    = "https://api.itau.com.br/cobranca/v2/boletos/" . $nosso_numero_corrigido . "/baixas";
        $certFile      = __DIR__ . '/../../certificados/Certificado.crt';
        $keyFile       = __DIR__ . '/../../certificados/ARQUIVO_CHAVE_PRIVADA.key';

        // Registra a URL completa para depuração
        error_log("URL de baixa do boleto: $baixar_url");

        // Adiciona informação no banco de dados sobre o formato usado
        try {
            $db->update('boletos', [
                'observacoes' => ($boleto['observacoes'] ? $boleto['observacoes'] . "\n" : "") .
                                "Tentativa de baixa em " . date('d/m/Y H:i:s') .
                                ". Nosso número original: $nosso_numero_original" .
                                ". Nosso número API: $nosso_numero_corrigido" .
                                ". Nosso número Itaú: $nosso_numero_formatado"
            ], 'id = ?', [$boleto_id]);
        } catch (Exception $e) {
            error_log("Erro ao atualizar observações do boleto: " . $e->getMessage());
            // Não interrompe o processo
        }

        // Verifica se os certificados existem
        if (!file_exists($certFile) || !file_exists($keyFile)) {
            // Modo de teste - não chama a API real
            // Em ambiente de produção, isso seria um erro
            error_log("Certificados não encontrados. Usando modo de teste para cancelar o boleto ID: $boleto_id");
            error_log("Caminho do certificado: $certFile");
            error_log("Caminho da chave privada: $keyFile");

            try {
                // Atualiza o status do boleto para cancelado
                $result = $db->update('boletos', [
                    'status' => 'cancelado',
                    'data_cancelamento' => date('Y-m-d H:i:s'),
                    'observacoes' => ($boleto['observacoes'] ? $boleto['observacoes'] . "\n" : "") . "Cancelado em modo de teste em " . date('d/m/Y H:i:s')
                ], 'id = ?', [$boleto_id]);

                if ($result === false) {
                    error_log("Erro ao atualizar o status do boleto ID: $boleto_id");
                    return ['status' => 'erro', 'mensagem' => 'Erro ao atualizar o status do boleto.'];
                }

                // Registra o cancelamento na tabela de histórico
                try {
                    $db->insert('boletos_historico', [
                        'boleto_id' => $boleto_id,
                        'acao' => 'cancelamento_teste',
                        'data' => date('Y-m-d H:i:s'),
                        'usuario_id' => isset($_SESSION['usuario']['id']) ? $_SESSION['usuario']['id'] : null,
                        'detalhes' => 'Cancelado em modo de teste (certificados não encontrados)'
                    ]);
                } catch (Exception $e) {
                    error_log("Erro ao registrar histórico de cancelamento: " . $e->getMessage());
                    // Não retorna erro, apenas loga
                }

                error_log("Boleto cancelado com sucesso (modo de teste): ID $boleto_id");
                return [
                    'status' => 'sucesso',
                    'mensagem' => 'Boleto cancelado com sucesso (modo de teste).'
                ];
            } catch (Exception $e) {
                error_log("Erro ao atualizar o status do boleto: " . $e->getMessage());
                return ['status' => 'erro', 'mensagem' => 'Erro ao atualizar o status do boleto: ' . $e->getMessage()];
            }
        }

        // Verifica se o nosso_numero está definido
        if (empty($boleto['nosso_numero'])) {
            error_log("Nosso número não definido para o boleto ID: $boleto_id");

            // Atualiza o status do boleto para cancelado mesmo sem nosso_numero
            try {
                $result = $db->update('boletos', [
                    'status' => 'cancelado',
                    'data_cancelamento' => date('Y-m-d H:i:s'),
                    'observacoes' => ($boleto['observacoes'] ? $boleto['observacoes'] . "\n" : "") . "Cancelado localmente (sem nosso_numero) em " . date('d/m/Y H:i:s')
                ], 'id = ?', [$boleto_id]);

                if ($result === false) {
                    error_log("Erro ao atualizar o status do boleto ID: $boleto_id");
                    return ['status' => 'erro', 'mensagem' => 'Erro ao atualizar o status do boleto.'];
                }

                // Registra o cancelamento na tabela de histórico
                try {
                    $db->insert('boletos_historico', [
                        'boleto_id' => $boleto_id,
                        'acao' => 'cancelamento_local',
                        'data' => date('Y-m-d H:i:s'),
                        'usuario_id' => isset($_SESSION['usuario']['id']) ? $_SESSION['usuario']['id'] : null,
                        'detalhes' => 'Cancelado localmente (sem nosso_numero)'
                    ]);
                } catch (Exception $e) {
                    error_log("Erro ao registrar histórico de cancelamento: " . $e->getMessage());
                    // Não retorna erro, apenas loga
                }

                error_log("Boleto cancelado localmente (sem nosso_numero): ID $boleto_id");
                return [
                    'status' => 'sucesso',
                    'mensagem' => 'Boleto cancelado com sucesso (apenas no sistema local).'
                ];
            } catch (Exception $e) {
                error_log("Erro ao atualizar o status do boleto: " . $e->getMessage());
                return ['status' => 'erro', 'mensagem' => 'Erro ao atualizar o status do boleto: ' . $e->getMessage()];
            }
        }

        // Verifica se temos o ID da resposta da API (mais confiável que o nosso número)
        if (!empty($boleto['api_response_id'])) {
            error_log("Usando ID da resposta da API para baixar o boleto: {$boleto['api_response_id']}");

            // Aqui implementaríamos a chamada à API usando o ID da resposta
            // Como essa implementação depende da documentação específica da API do Itaú,
            // vamos apenas registrar que usaríamos esse método
            error_log("Método de baixa por ID da API não implementado. Continuando com método padrão.");
        }

        // Tenta consultar o boleto na API com diferentes formatos de nosso número
        error_log("Consultando boleto na API com diferentes formatos de nosso número: ID $boleto_id, Nosso Número {$boleto['nosso_numero']}");
        $consulta = consultarBoletoMultiFormato($boleto['nosso_numero'], $db);

        // Se o boleto não foi encontrado na API, tenta baixar com diferentes formatos
        if ($consulta['status'] === 'nao_encontrado') {
            error_log("Boleto não encontrado na consulta. Tentando baixar com diferentes formatos de nosso número: ID $boleto_id");

            // Tenta baixar o boleto com diferentes formatos de nosso número
            $resultado_baixa = baixarBoletoMultiFormato($boleto['nosso_numero'], $db, $boleto_id);

            // Se conseguiu baixar com algum formato, retorna sucesso
            if ($resultado_baixa['status'] === 'sucesso') {
                return $resultado_baixa;
            }

            // Se não conseguiu baixar com nenhum formato, cancela apenas localmente
            error_log("Não foi possível baixar o boleto com nenhum formato. Cancelando apenas localmente: ID $boleto_id");

            try {
                $result = $db->update('boletos', [
                    'status' => 'cancelado',
                    'data_cancelamento' => date('Y-m-d H:i:s'),
                    'observacoes' => ($boleto['observacoes'] ? $boleto['observacoes'] . "\n" : "") .
                                    "Cancelado localmente (boleto não encontrado na API com nenhuma variação do nosso número) em " . date('d/m/Y H:i:s')
                ], 'id = ?', [$boleto_id]);

                if ($result === false) {
                    error_log("Erro ao atualizar o status do boleto ID: $boleto_id");
                    return ['status' => 'erro', 'mensagem' => 'Erro ao atualizar o status do boleto.'];
                }

                // Registra o cancelamento na tabela de histórico
                try {
                    $db->insert('boletos_historico', [
                        'boleto_id' => $boleto_id,
                        'acao' => 'cancelamento_local',
                        'data' => date('Y-m-d H:i:s'),
                        'usuario_id' => isset($_SESSION['usuario']['id']) ? $_SESSION['usuario']['id'] : null,
                        'detalhes' => 'Cancelado localmente (boleto não encontrado na API com nenhuma variação do nosso número)'
                    ]);
                } catch (Exception $e) {
                    error_log("Erro ao registrar histórico de cancelamento: " . $e->getMessage());
                    // Não retorna erro, apenas loga
                }

                return [
                    'status' => 'sucesso',
                    'mensagem' => 'Boleto cancelado com sucesso (apenas no sistema local). Motivo: não encontrado na API com nenhuma variação do nosso número.'
                ];
            } catch (Exception $e) {
                error_log("Erro ao atualizar o status do boleto: " . $e->getMessage());
                return ['status' => 'erro', 'mensagem' => 'Erro ao atualizar o status do boleto: ' . $e->getMessage()];
            }
        }

        // Se o boleto já está baixado/cancelado na API, atualiza o status no sistema local
        if ($consulta['status'] === 'sucesso' && isset($consulta['dados']['situacao']) &&
            ($consulta['dados']['situacao'] === 'BAIXADO' || $consulta['dados']['situacao'] === 'CANCELADO')) {

            error_log("Boleto já está baixado/cancelado na API. Atualizando status no sistema local: ID $boleto_id");

            try {
                $result = $db->update('boletos', [
                    'status' => 'cancelado',
                    'data_cancelamento' => date('Y-m-d H:i:s'),
                    'observacoes' => ($boleto['observacoes'] ? $boleto['observacoes'] . "\n" : "") . "Status atualizado para cancelado (boleto já estava baixado/cancelado na API) em " . date('d/m/Y H:i:s')
                ], 'id = ?', [$boleto_id]);

                if ($result === false) {
                    error_log("Erro ao atualizar o status do boleto ID: $boleto_id");
                    return ['status' => 'erro', 'mensagem' => 'Erro ao atualizar o status do boleto.'];
                }

                // Registra o cancelamento na tabela de histórico
                try {
                    $db->insert('boletos_historico', [
                        'boleto_id' => $boleto_id,
                        'acao' => 'atualizacao_status',
                        'data' => date('Y-m-d H:i:s'),
                        'usuario_id' => isset($_SESSION['usuario']['id']) ? $_SESSION['usuario']['id'] : null,
                        'detalhes' => 'Status atualizado para cancelado (boleto já estava baixado/cancelado na API)'
                    ]);
                } catch (Exception $e) {
                    error_log("Erro ao registrar histórico de cancelamento: " . $e->getMessage());
                    // Não retorna erro, apenas loga
                }

                return [
                    'status' => 'sucesso',
                    'mensagem' => 'Boleto cancelado com sucesso. O boleto já estava baixado/cancelado na API.'
                ];
            } catch (Exception $e) {
                error_log("Erro ao atualizar o status do boleto: " . $e->getMessage());
                return ['status' => 'erro', 'mensagem' => 'Erro ao atualizar o status do boleto: ' . $e->getMessage()];
            }
        }

        // Se o boleto está pago na API, não permite cancelar
        if ($consulta['status'] === 'sucesso' && isset($consulta['dados']['situacao']) &&
            $consulta['dados']['situacao'] === 'PAGO') {

            error_log("Boleto já está pago na API. Não é possível cancelar: ID $boleto_id");

            return [
                'status' => 'erro',
                'mensagem' => 'Não é possível cancelar um boleto já pago. O boleto consta como PAGO na API do banco.'
            ];
        }

        // Se houve erro na consulta, mas não foi "não encontrado", loga o erro mas continua com a tentativa de baixa
        if ($consulta['status'] === 'erro') {
            error_log("Erro ao consultar boleto na API, mas continuando com tentativa de baixa: " . $consulta['mensagem']);
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
        error_log("Tentando obter token de acesso para cancelar o boleto ID: $boleto_id");

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

        // Preparar payload para baixa do boleto
        $payload = json_encode([
            'codigoBaixa' => 'OUTROS',
            'dataBaixa' => date('Y-m-d') // Data atual no formato ISO 8601 (AAAA-MM-DD)
        ]);

        error_log("Payload para baixa do boleto: $payload");

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

        // Executa a requisição
        error_log("Executando requisição para baixar o boleto ID: $boleto_id, Nosso Número: {$boleto['nosso_numero']}");
        $response = curl_exec($curl);
        $err = curl_error($curl);
        $info = curl_getinfo($curl);

        // Log das informações da requisição
        error_log("Informações da requisição de baixa: " . json_encode($info));
        error_log("Resposta da requisição de baixa: " . $response);

        curl_close($curl);

        if ($err) {
            error_log("Erro ao baixar boleto: $err");
            return ['status' => 'erro', 'mensagem' => 'Erro ao baixar boleto: ' . $err];
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
                    'observacoes' => ($boleto['observacoes'] ? $boleto['observacoes'] . "\n" : "") . "Baixado via API em " . date('d/m/Y H:i:s')
                ], 'id = ?', [$boleto_id]);

                if ($update_result === false) {
                    error_log("Erro ao atualizar o status do boleto ID: $boleto_id após baixa na API");
                    return ['status' => 'erro', 'mensagem' => 'Boleto baixado na API, mas houve erro ao atualizar o status no sistema.'];
                }

                // Registra o cancelamento na tabela de histórico
                try {
                    $db->insert('boletos_historico', [
                        'boleto_id' => $boleto_id,
                        'acao' => 'baixa_api',
                        'data' => date('Y-m-d H:i:s'),
                        'usuario_id' => isset($_SESSION['usuario']['id']) ? $_SESSION['usuario']['id'] : null,
                        'detalhes' => 'Baixado via API do Itaú (cobranca/v2/boletos/baixas)'
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
            error_log("Erro HTTP ao baixar boleto: $http_code. Resposta: $response");

            // Se o erro for 404 (boleto não encontrado na API) ou 403 (acesso proibido), cancela localmente
            if ($http_code == 404 || $http_code == 403) {
                $motivo = ($http_code == 404) ? "não encontrado na API" : "acesso proibido (403)";

                // Log detalhado para depuração
                error_log("Cancelando boleto localmente devido a erro $http_code ($motivo): ID $boleto_id");
                error_log("Detalhes do boleto não encontrado: Nosso Número: {$boleto['nosso_numero']}, Data Geração: {$boleto['data_geracao']}, Valor: {$boleto['valor']}");

                // Tenta decodificar a resposta para obter mais detalhes
                $error_details = json_decode($response, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    error_log("Detalhes da resposta da API: " . json_encode($error_details));
                }

                try {
                    $result = $db->update('boletos', [
                        'status' => 'cancelado',
                        'data_cancelamento' => date('Y-m-d H:i:s'),
                        'observacoes' => ($boleto['observacoes'] ? $boleto['observacoes'] . "\n" : "") . "Cancelado localmente após erro $http_code ($motivo) na API em " . date('d/m/Y H:i:s')
                    ], 'id = ?', [$boleto_id]);

                    if ($result === false) {
                        error_log("Erro ao atualizar o status do boleto ID: $boleto_id");
                        return ['status' => 'erro', 'mensagem' => 'Erro ao atualizar o status do boleto.'];
                    }

                    // Registra o cancelamento na tabela de histórico
                    try {
                        $db->insert('boletos_historico', [
                            'boleto_id' => $boleto_id,
                            'acao' => 'cancelamento_local_apos_erro_api',
                            'data' => date('Y-m-d H:i:s'),
                            'usuario_id' => isset($_SESSION['usuario']['id']) ? $_SESSION['usuario']['id'] : null,
                            'detalhes' => "Cancelado localmente após erro $http_code ($motivo) na API"
                        ]);
                    } catch (Exception $e) {
                        error_log("Erro ao registrar histórico de cancelamento: " . $e->getMessage());
                        // Não retorna erro, apenas loga
                    }

                    error_log("Boleto cancelado localmente ($motivo): ID $boleto_id");
                    return [
                        'status' => 'sucesso',
                        'mensagem' => "Boleto cancelado com sucesso (apenas no sistema local). Motivo: $motivo."
                    ];
                } catch (Exception $e) {
                    error_log("Erro ao atualizar o status do boleto: " . $e->getMessage());
                    return ['status' => 'erro', 'mensagem' => 'Erro ao atualizar o status do boleto: ' . $e->getMessage()];
                }
            }

            // Mensagem de erro mais detalhada para o erro 403
            if ($http_code == 403) {
                $mensagem = "Erro 403 (Acesso Proibido) ao baixar boleto. Isso pode ser causado por:\n";
                $mensagem .= "1. Certificados inválidos ou expirados\n";
                $mensagem .= "2. Credenciais inválidas (client_id/client_secret)\n";
                $mensagem .= "3. Falta de permissão para baixar este boleto\n";
                $mensagem .= "4. IP não autorizado a acessar a API\n";
                $mensagem .= "Verifique os logs para mais detalhes.";

                error_log($mensagem);
                return ['status' => 'erro', 'mensagem' => $mensagem];
            }

            // Tenta decodificar a resposta para obter mais detalhes do erro
            $error_details = json_decode($response, true);
            $error_message = "Erro HTTP ao baixar boleto: $http_code.";

            if (json_last_error() === JSON_ERROR_NONE && isset($error_details['mensagem'])) {
                $error_message .= " Mensagem: " . $error_details['mensagem'];
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
// As funções extrairNossoNumero8Digitos e formatarNossoNumeroItau foram movidas para corrigir_nosso_numero.php

/**
 * Tenta baixar um boleto usando diferentes formatos de nosso número
 *
 * @param string $nosso_numero Nosso número original
 * @param object $db Objeto de conexão com o banco de dados
 * @param int $boleto_id ID do boleto no sistema
 * @return array Resultado da operação
 */
function baixarBoletoMultiFormato($nosso_numero, $db, $boleto_id) {
    // Formatos a serem testados
    $formatos = [
        // Formato original
        'original' => $nosso_numero,

        // Apenas os 8 dígitos (sem carteira e sem DV)
        'base_8_digitos' => extrairNossoNumero8Digitos($nosso_numero),

        // Formato com carteira e sem DV
        'com_carteira' => '109' . extrairNossoNumero8Digitos($nosso_numero),

        // Formato completo (carteira/nosso_numero-DV)
        'completo' => formatarNossoNumeroItau($nosso_numero),

        // Formato sem barras e traços
        'sem_separadores' => preg_replace('/[\/\-\s]/', '', formatarNossoNumeroItau($nosso_numero)),

        // Formato sem barras e traços, sem o dígito verificador
        'sem_separadores_sem_dv' => preg_replace('/[\/\-\s]/', '', '109' . extrairNossoNumero8Digitos($nosso_numero)),

        // Apenas os 8 dígitos com zeros à esquerda
        'zeros_esquerda' => str_pad(extrairNossoNumero8Digitos($nosso_numero), 8, '0', STR_PAD_LEFT),

        // Formato exato como aparece no sistema do Itaú (sem modificações)
        'formato_itau_exato' => "109/" . extrairNossoNumero8Digitos($nosso_numero) . "-2"
    ];

    // Log dos formatos que serão testados
    error_log("Tentando baixar boleto ID $boleto_id com os seguintes formatos de nosso número:");
    foreach ($formatos as $tipo => $formato) {
        error_log("  - $tipo: $formato");
    }

    // Tenta baixar o boleto com cada formato
    foreach ($formatos as $tipo => $formato) {
        error_log("Tentando baixar boleto ID $boleto_id com formato $tipo: $formato");

        // Configura a URL de baixa com este formato
        $client_id     = "8a7ee29a-f20d-43b8-b3f5-c559862669a9";
        $client_secret = "a6a29bfe-bec3-4619-b1c0-5653e6322ba0";
        $token_url     = "https://sts.itau.com.br/api/oauth/token";
        $baixar_url    = "https://api.itau.com.br/cobranca/v2/boletos/" . $formato . "/baixas";
        $certFile      = __DIR__ . '/../../certificados/Certificado.crt';
        $keyFile       = __DIR__ . '/../../certificados/ARQUIVO_CHAVE_PRIVADA.key';

        error_log("URL de baixa: $baixar_url");

        // Verifica se os certificados existem
        if (!file_exists($certFile) || !file_exists($keyFile)) {
            error_log("Certificados não encontrados. Pulando tentativa com formato $tipo.");
            continue;
        }

        try {
            // Obter token de acesso
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $token_url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => "grant_type=client_credentials&client_id=$client_id&client_secret=$client_secret",
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/x-www-form-urlencoded'
                ],
                CURLOPT_SSLCERT => $certFile,
                CURLOPT_SSLKEY => $keyFile,
                CURLOPT_SSL_VERIFYPEER => true
            ]);

            $response = curl_exec($curl);
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            if ($http_code != 200) {
                error_log("Erro ao obter token para formato $tipo: HTTP $http_code");
                continue;
            }

            $token_data = json_decode($response, true);
            if (!isset($token_data['access_token'])) {
                error_log("Token não encontrado na resposta para formato $tipo");
                continue;
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
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => [
                    "Authorization: Bearer $access_token",
                    "Content-Type: application/json",
                    "x-itau-correlation-id: $correlation_id",
                    "x-itau-flow-id: cobranca"
                ],
                CURLOPT_SSLCERT => $certFile,
                CURLOPT_SSLKEY => $keyFile,
                CURLOPT_SSL_VERIFYPEER => true
            ]);

            // Executa a requisição
            $response = curl_exec($curl);
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            error_log("Resposta para formato $tipo: HTTP $http_code - $response");

            // Códigos de sucesso: 200 (OK) ou 204 (No Content)
            if ($http_code == 200 || $http_code == 204) {
                error_log("Sucesso ao baixar boleto com formato $tipo!");

                // Registra o sucesso no log com detalhes completos
                error_log("SUCESSO: Boleto ID $boleto_id baixado com sucesso via API usando formato $tipo: $formato");
                error_log("Resposta completa da API: $response");

                // Atualiza o status do boleto para cancelado
                $db->update('boletos', [
                    'status' => 'cancelado',
                    'data_cancelamento' => date('Y-m-d H:i:s'),
                    'observacoes' => "Baixado via API em " . date('d/m/Y H:i:s') . " usando formato $tipo: $formato"
                ], 'id = ?', [$boleto_id]);

                // Registra o cancelamento na tabela de histórico
                $db->insert('boletos_historico', [
                    'boleto_id' => $boleto_id,
                    'acao' => 'baixa_api',
                    'data' => date('Y-m-d H:i:s'),
                    'usuario_id' => isset($_SESSION['usuario']['id']) ? $_SESSION['usuario']['id'] : null,
                    'detalhes' => "Baixado via API do Itaú usando formato $tipo: $formato. HTTP $http_code. Resposta: $response"
                ]);

                // Adiciona uma observação importante para o usuário
                $mensagem = "Boleto baixado com sucesso usando formato $tipo: $formato. ";
                $mensagem .= "IMPORTANTE: Verifique no sistema do Itaú se o boleto foi realmente cancelado. ";
                $mensagem .= "Em alguns casos, a API retorna sucesso mas o boleto continua ativo no banco.";

                return [
                    'status' => 'sucesso',
                    'mensagem' => $mensagem
                ];
            }
        } catch (Exception $e) {
            error_log("Erro ao tentar baixar boleto com formato $tipo: " . $e->getMessage());
            // Continua para o próximo formato
        }
    }

    // Se chegou aqui, não conseguiu baixar com nenhum formato
    error_log("Não foi possível baixar o boleto com nenhum formato de nosso número");
    return [
        'status' => 'erro',
        'mensagem' => 'Não foi possível baixar o boleto com nenhum formato de nosso número'
    ];
}

// As funções consultarBoleto e consultarBoletoMultiFormato foram movidas para consultar_boleto_api.php
?>
