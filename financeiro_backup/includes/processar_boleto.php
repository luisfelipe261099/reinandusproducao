<?php
/**
 * Processa a geração de boletos via API do Itaú
 */

/**
 * Gera a linha digitável a partir do código de barras
 * @param string $codigoBarras O código de barras do boleto
 * @return string A linha digitável gerada ou string vazia em caso de erro
 */
function gerarLinhaDigitavel($codigoBarras) {
    // Verifica se o código de barras tem o tamanho correto
    if (strlen($codigoBarras) != 44 && strlen($codigoBarras) != 47) {
        error_log("Código de barras com tamanho inválido: " . strlen($codigoBarras) . " caracteres");
        return '';
    }

    // Se o código de barras tiver 47 caracteres, extrai os 44 dígitos numéricos
    if (strlen($codigoBarras) == 47) {
        $codigoBarras = preg_replace('/[^0-9]/', '', $codigoBarras);
    }

    // Verifica novamente se temos 44 dígitos
    if (strlen($codigoBarras) != 44) {
        error_log("Código de barras inválido após limpeza: " . strlen($codigoBarras) . " caracteres");
        return '';
    }

    try {
        // Extrai os campos do código de barras
        $campo1 = substr($codigoBarras, 0, 4) . substr($codigoBarras, 19, 1) . substr($codigoBarras, 20, 4);
        $campo2 = substr($codigoBarras, 24, 10);
        $campo3 = substr($codigoBarras, 34, 10);
        $campo4 = substr($codigoBarras, 4, 1); // Dígito verificador geral
        $campo5 = substr($codigoBarras, 5, 14); // Valor ou fator de vencimento + valor

        // Calcula os dígitos verificadores de cada campo
        $dv1 = calcularDigitoVerificador($campo1);
        $dv2 = calcularDigitoVerificador($campo2);
        $dv3 = calcularDigitoVerificador($campo3);

        // Formata a linha digitável
        $linhaDigitavel =
            substr($campo1, 0, 5) . '.' . substr($campo1, 5) . $dv1 . ' ' .
            substr($campo2, 0, 5) . '.' . substr($campo2, 5) . $dv2 . ' ' .
            substr($campo3, 0, 5) . '.' . substr($campo3, 5) . $dv3 . ' ' .
            $campo4 . ' ' .
            $campo5;

        return $linhaDigitavel;
    } catch (Exception $e) {
        error_log("Erro ao gerar linha digitável: " . $e->getMessage());
        return '';
    }
}

/**
 * Calcula o dígito verificador de um campo da linha digitável
 * @param string $campo O campo para calcular o dígito verificador
 * @return int O dígito verificador calculado
 */
function calcularDigitoVerificador($campo) {
    $soma = 0;
    $peso = 2;
    $tamanho = strlen($campo);

    // Multiplica cada dígito pelo seu peso e soma
    for ($i = $tamanho - 1; $i >= 0; $i--) {
        $soma += $campo[$i] * $peso;
        $peso = ($peso == 2) ? 1 : 2;
    }

    // Calcula o dígito verificador
    $resto = $soma % 10;
    $dv = ($resto == 0) ? 0 : 10 - $resto;

    return $dv;
}

/**
 * Função recursiva para encontrar um valor em um array multidimensional
 * @param array $array O array a ser pesquisado
 * @param string $key A chave a ser encontrada
 * @return mixed|null O valor encontrado ou null se não encontrado
 */
function findValueInArray($array, $key) {
    // Caso base: se não for um array, retorna null
    if (!is_array($array)) {
        return null;
    }

    // Verifica se a chave existe diretamente no array
    if (isset($array[$key])) {
        return $array[$key];
    }

    // Busca recursivamente em cada elemento do array
    foreach ($array as $value) {
        if (is_array($value)) {
            $result = findValueInArray($value, $key);
            if ($result !== null) {
                return $result;
            }
        }
    }

    // Não encontrou
    return null;
}


function processarBoleto($dados, $db) {
    try {
        // Validação básica dos dados
        if (empty($dados['descricao'])) {
            return ['status' => 'erro', 'mensagem' => 'A descrição do boleto é obrigatória.'];
        }

        if (empty($dados['valor']) || !is_numeric(str_replace(',', '.', $dados['valor']))) {
            return ['status' => 'erro', 'mensagem' => 'O valor do boleto é obrigatório e deve ser um número.'];
        }

        if (empty($dados['data_vencimento'])) {
            return ['status' => 'erro', 'mensagem' => 'A data de vencimento é obrigatória.'];
        }

        // Verifica o tipo de entidade
        $tipo_entidade = $dados['tipo_entidade'] ?? '';
        $entidade_id = null;
        $nome_pagador = '';
        $cpf_pagador = '';
        $endereco = '';
        $bairro = '';
        $cidade = '';
        $uf = '';
        $cep = '';

        if ($tipo_entidade === 'aluno') {
            if (empty($dados['aluno_id'])) {
                return ['status' => 'erro', 'mensagem' => 'Selecione um aluno.'];
            }

            $entidade_id = (int)$dados['aluno_id'];

            // Busca os dados do aluno
            $sql = "SELECT a.*, e.logradouro, e.numero, e.complemento, e.bairro, e.cidade, e.uf, e.cep
                    FROM alunos a
                    LEFT JOIN enderecos e ON a.id = e.aluno_id
                    WHERE a.id = ?";
            $aluno = $db->fetchOne($sql, [$entidade_id]);

            if (!$aluno) {
                return ['status' => 'erro', 'mensagem' => 'Aluno não encontrado.'];
            }

            $nome_pagador = $aluno['nome'];
            $cpf_pagador = $aluno['cpf'];
            $endereco = $aluno['logradouro'] . ', ' . $aluno['numero'] . ($aluno['complemento'] ? ' - ' . $aluno['complemento'] : '');
            $bairro = $aluno['bairro'];
            $cidade = $aluno['cidade'];
            $uf = $aluno['uf'];
            $cep = $aluno['cep'];

        } elseif ($tipo_entidade === 'polo') {
            if (empty($dados['polo_id'])) {
                return ['status' => 'erro', 'mensagem' => 'Selecione um polo.'];
            }

            $entidade_id = (int)$dados['polo_id'];

            // Busca os dados do polo
            $sql = "SELECT * FROM polos WHERE id = ?";
            $polo = $db->fetchOne($sql, [$entidade_id]);

            if (!$polo) {
                return ['status' => 'erro', 'mensagem' => 'Polo não encontrado.'];
            }

            $nome_pagador = $polo['nome'];
            $cpf_pagador = $polo['cnpj'];
            $endereco = $polo['endereco'];
            $bairro = $polo['bairro'];
            $cidade = $polo['cidade'];
            $uf = $polo['uf'];
            $cep = $polo['cep'];

        } elseif ($tipo_entidade === 'avulso') {
            if (empty($dados['nome_pagador'])) {
                return ['status' => 'erro', 'mensagem' => 'O nome do pagador é obrigatório.'];
            }

            if (empty($dados['cpf_pagador'])) {
                return ['status' => 'erro', 'mensagem' => 'O CPF/CNPJ do pagador é obrigatório.'];
            }

            $nome_pagador = $dados['nome_pagador'];
            $cpf_pagador = $dados['cpf_pagador'];
            $endereco = $dados['logradouro'] . ', ' . $dados['numero'];
            $bairro = $dados['bairro'];
            $cidade = $dados['cidade'];
            $uf = $dados['uf'];
            $cep = $dados['cep'];

        } else {
            return ['status' => 'erro', 'mensagem' => 'Tipo de entidade inválido.'];
        }

        // Formata o valor
        $valor = str_replace(',', '.', $dados['valor']);

        // Verifica o tipo de boleto
        $tipo_boleto = $dados['tipo_boleto'] ?? 'a vista';
        $boletos_gerados = [];

        // Inicia a transação
        $db->beginTransaction();

        if ($tipo_boleto === 'a vista') {
            // Gera um único boleto
            $resultado = gerarBoletoBancario($db, [
                'tipo_entidade' => $tipo_entidade,
                'entidade_id' => $entidade_id,
                'nome_pagador' => $nome_pagador,
                'cpf_pagador' => $cpf_pagador,
                'endereco' => $endereco,
                'bairro' => $bairro,
                'cidade' => $cidade,
                'uf' => $uf,
                'cep' => $cep,
                'descricao' => $dados['descricao'],
                'valor' => $valor,
                'data_vencimento' => $dados['data_vencimento'],
                'multa' => $dados['multa'] ?? 2,
                'juros' => $dados['juros'] ?? 1,
                'instrucoes' => $dados['instrucoes'] ?? ''
            ]);

            if ($resultado['status'] === 'sucesso') {
                $boletos_gerados[] = $resultado['boleto_id'];
            } else {
                // Desfaz a transação em caso de erro
                $db->rollBack();
                return $resultado;
            }

        } elseif ($tipo_boleto === 'parcelado') {
            // Verifica os dados de parcelamento
            $numero_parcelas = (int)($dados['numero_parcelas'] ?? 2);
            $intervalo_dias = (int)($dados['intervalo_dias'] ?? 30);

            if ($numero_parcelas < 2 || $numero_parcelas > 12) {
                return ['status' => 'erro', 'mensagem' => 'Número de parcelas inválido.'];
            }

            // Calcula o valor de cada parcela
            $valor_parcela = round($valor / $numero_parcelas, 2);
            $valor_ultima_parcela = $valor - ($valor_parcela * ($numero_parcelas - 1));

            // Data de vencimento da primeira parcela
            $data_vencimento = new DateTime($dados['data_vencimento']);

            // Gera os boletos para cada parcela
            for ($i = 1; $i <= $numero_parcelas; $i++) {
                $valor_atual = ($i == $numero_parcelas) ? $valor_ultima_parcela : $valor_parcela;
                $descricao_parcela = $dados['descricao'] . ' - Parcela ' . $i . '/' . $numero_parcelas;

                $resultado = gerarBoletoBancario($db, [
                    'tipo_entidade' => $tipo_entidade,
                    'entidade_id' => $entidade_id,
                    'nome_pagador' => $nome_pagador,
                    'cpf_pagador' => $cpf_pagador,
                    'endereco' => $endereco,
                    'bairro' => $bairro,
                    'cidade' => $cidade,
                    'uf' => $uf,
                    'cep' => $cep,
                    'descricao' => $descricao_parcela,
                    'valor' => $valor_atual,
                    'data_vencimento' => $data_vencimento->format('Y-m-d'),
                    'multa' => $dados['multa'] ?? 2,
                    'juros' => $dados['juros'] ?? 1,
                    'instrucoes' => $dados['instrucoes'] ?? '',
                    'grupo_boletos' => 'P' . time() // Identificador do grupo de parcelas
                ]);

                if ($resultado['status'] === 'sucesso') {
                    $boletos_gerados[] = $resultado['boleto_id'];
                } else {
                    // Desfaz a transação em caso de erro
                    $db->rollBack();
                    return $resultado;
                }

                // Avança para a próxima data de vencimento
                $data_vencimento->modify('+' . $intervalo_dias . ' days');
            }

        } elseif ($tipo_boleto === 'recorrente') {
            // Verifica os dados de recorrência
            $numero_recorrencias = (int)($dados['numero_recorrencias'] ?? 2);
            $dia_vencimento = (int)($dados['dia_vencimento'] ?? 10);

            if ($numero_recorrencias < 2 || $numero_recorrencias > 36) {
                return ['status' => 'erro', 'mensagem' => 'Número de recorrências inválido.'];
            }

            if ($dia_vencimento < 1 || $dia_vencimento > 28) {
                return ['status' => 'erro', 'mensagem' => 'Dia de vencimento inválido.'];
            }

            // Data de vencimento da primeira recorrência
            $data_vencimento = new DateTime($dados['data_vencimento']);

            // Gera os boletos para cada recorrência
            for ($i = 1; $i <= $numero_recorrencias; $i++) {
                $descricao_recorrencia = $dados['descricao'] . ' - ' . $data_vencimento->format('m/Y');

                $resultado = gerarBoletoBancario($db, [
                    'tipo_entidade' => $tipo_entidade,
                    'entidade_id' => $entidade_id,
                    'nome_pagador' => $nome_pagador,
                    'cpf_pagador' => $cpf_pagador,
                    'endereco' => $endereco,
                    'bairro' => $bairro,
                    'cidade' => $cidade,
                    'uf' => $uf,
                    'cep' => $cep,
                    'descricao' => $descricao_recorrencia,
                    'valor' => $valor,
                    'data_vencimento' => $data_vencimento->format('Y-m-d'),
                    'multa' => $dados['multa'] ?? 2,
                    'juros' => $dados['juros'] ?? 1,
                    'instrucoes' => $dados['instrucoes'] ?? '',
                    'grupo_boletos' => 'R' . time() // Identificador do grupo de recorrências
                ]);

                if ($resultado['status'] === 'sucesso') {
                    $boletos_gerados[] = $resultado['boleto_id'];
                } else {
                    // Desfaz a transação em caso de erro
                    $db->rollBack();
                    return $resultado;
                }

                // Avança para o próximo mês
                $data_vencimento->modify('first day of next month');
                $data_vencimento->setDate(
                    (int)$data_vencimento->format('Y'),
                    (int)$data_vencimento->format('m'),
                    min($dia_vencimento, (int)$data_vencimento->format('t'))
                );
            }
        }

        // Confirma a transação
        $db->commit();

        // Retorna o resultado
        $total_boletos = count($boletos_gerados);
        if ($total_boletos === 1) {
            return [
                'status' => 'sucesso',
                'mensagem' => 'Boleto gerado com sucesso.',
                'boleto_id' => $boletos_gerados[0]
            ];
        } else {
            return [
                'status' => 'sucesso',
                'mensagem' => $total_boletos . ' boletos gerados com sucesso.',
                'boletos_ids' => $boletos_gerados
            ];
        }

    } catch (Exception $e) {
        // Desfaz a transação em caso de erro
        if ($db->inTransaction()) {
            $db->rollBack();
        }

        // Registra o erro
        error_log('Erro ao gerar boleto: ' . $e->getMessage());

        return [
            'status' => 'erro',
            'mensagem' => 'Erro ao gerar boleto: ' . $e->getMessage()
        ];
    }
}

/**
 * Gera um boleto bancário via API do Itaú
 */
function gerarBoletoBancario($db, $dados) {
    try {
        // Configurações da API do Itaú
        $client_id     = "8a7ee29a-f20d-43b8-b3f5-c559862669a9";
        $client_secret = "a6a29bfe-bec3-4619-b1c0-5653e6322ba0";
        $token_url     = "https://sts.itau.com.br/api/oauth/token";
        $boleto_url    = "https://api.itau.com.br/cash_management/v2/boletos";
        $certFile      = __DIR__ . '/../../certificados/Certificado.crt';
        $keyFile       = __DIR__ . '/../../certificados/ARQUIVO_CHAVE_PRIVADA.key';

        // Verifica se os certificados existem
        if (!file_exists($certFile) || !file_exists($keyFile)) {
            // Modo de teste - não chama a API real
            // Em ambiente de produção, isso seria um erro

            // Formata o valor para o banco de dados
            $valor = str_replace(',', '.', $dados['valor']);
            $valor_centavos = (int)(round($valor * 100));
            $dados['valor_formatado'] = number_format($valor_centavos / 100, 2, '.', '');

            // Gera o nosso número no formato exigido pela carteira 109 do Itaú
            // Para a carteira 109, o nosso número deve ter 8 dígitos
            $nosso_numero = str_pad(mt_rand(1, 99999999), 8, '0', STR_PAD_LEFT);

            // Gera uma linha digitável aleatória para simulação
            $linha_digitavel = '34191.12345 67890.101112 13141.516171 8 ' . date('ymd') . '0000100000';

            // Gera um código de barras aleatório para simulação
            $codigo_barras = '34198' . date('ymd') . '0000100000191123456789010111213141516171';

            // Log para depuração no modo de teste
            error_log("Modo de teste - Nosso número: $nosso_numero");
            error_log("Modo de teste - Linha digitável: $linha_digitavel");
            error_log("Modo de teste - Código de barras: $codigo_barras");

            // Salva o boleto no banco de dados
            $boleto_id = salvarBoleto($db, $dados, [
                'nosso_numero' => $nosso_numero,
                'linha_digitavel' => $linha_digitavel,
                'codigo_barras' => $codigo_barras,
                'url_boleto' => 'https://exemplo.com/boleto/' . $nosso_numero . '.pdf'
            ]);

            return [
                'status' => 'sucesso',
                'mensagem' => 'Boleto gerado com sucesso (modo de teste).',
                'boleto_id' => $boleto_id
            ];
        }

        // Formata os dados para a API
        $cpf_pagador = preg_replace('/[^0-9]/', '', $dados['cpf_pagador']);
        $cep = preg_replace('/[^0-9]/', '', $dados['cep']);

        // Formata o valor conforme esperado pela API do Itaú (sem pontos ou vírgulas)
        $valor = str_replace(',', '.', $dados['valor']); // Garante que estamos trabalhando com ponto como separador decimal
        $valor_centavos = (int)(round($valor * 100)); // Converte para centavos (inteiro)
        // Mantemos o valor formatado para uso no banco de dados
        $valor_formatado = number_format($valor_centavos / 100, 2, '.', ''); // Formata com 2 casas decimais

        $data_emissao = date('Y-m-d');
        $data_vencimento = $dados['data_vencimento'];

        // Gera o nosso número no formato exigido pela carteira 109 do Itaú
        // Para a carteira 109, o nosso número deve ter 8 dígitos
        $numero_nosso_numero = str_pad(mt_rand(1, 99999999), 8, '0', STR_PAD_LEFT);

        // Monta o payload para a API
        $payload = [
            "data" => [
                "etapa_processo_boleto"   => "efetivacao",
                "codigo_canal_operacao"   => "API",
                "beneficiario"            => [
                    "id_beneficiario" => 861600271717
                ],
                "dado_boleto"             => [
                    "descricao_instrumento_cobranca" => "boleto",
                    // Removido completamente o campo tipo_boleto que estava causando erro
                    "codigo_carteira"       => "109",
                    "valor_total_titulo"    => (string)$valor_centavos,
                    "codigo_especie"        => "01",
                    "valor_abatimento"      => "000",
                    "data_emissao"          => $data_emissao,
                    "indicador_pagamento_parcial" => true,
                    "quantidade_maximo_parcial"   => 0,
                    "pagador"               => [
                        "pessoa"  => [
                            "nome_pessoa" => $dados['nome_pagador'],
                            "tipo_pessoa" => [
                                "codigo_tipo_pessoa" => strlen($cpf_pagador) <= 11 ? "F" : "J",
                                "numero_cadastro_pessoa_fisica" => $cpf_pagador
                            ]
                        ],
                        "endereco" => [
                            "nome_logradouro" => $dados['endereco'],
                            "nome_bairro"     => $dados['bairro'],
                            "nome_cidade"     => $dados['cidade'],
                            "sigla_UF"        => $dados['uf'],
                            "numero_CEP"      => $cep
                        ]
                    ],
                    "dados_individuais_boleto" => [[
                        "numero_nosso_numero" => $numero_nosso_numero,
                        "data_vencimento"     => $data_vencimento,
                        "data_limite_pagamento" => $data_vencimento,
                        "valor_titulo"        => (string)$valor_centavos,
                        "texto_uso_beneficiario" => $dados['descricao'],
                        "texto_seu_numero"    => "12345"
                    ]],
                    "multa"                 => [
                        "codigo_tipo_multa"      => "02",
                        "quantidade_dias_multa"  => 1,
                        "percentual_multa"       => sprintf("%012d", $dados['multa'] * 100000)
                    ],
                    "juros"                 => [
                        "codigo_tipo_juros"      => 90,
                        "quantidade_dias_juros"  => 1,
                        "percentual_juros"       => sprintf("%012d", $dados['juros'] * 100000)
                    ],
                    "recebimento_divergente"=> [
                        "codigo_tipo_autorizacao" => "01"
                    ],
                    "instrucao_cobranca"    => [[
                        "codigo_instrucao_cobranca" => "1",
                        "quantidade_dias_apos_vencimento" => 2,
                        "dia_util"               => false
                    ]],
                    "desconto_expresso"     => false
                ]
            ]
        ];

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
        curl_close($ch);

        if ($httpcode != 200) {
            throw new Exception("Erro ao obter token de acesso: $httpcode - $response");
        }

        $token_data = json_decode($response, true);
        $access_token = $token_data['access_token'];

        // Verificar o formato do nosso número antes de enviar
        if (strlen($numero_nosso_numero) != 8) {
            throw new Exception("Nosso número inválido: deve ter 8 dígitos, mas tem " . strlen($numero_nosso_numero));
        }

        // Log do payload para depuração
        error_log("Enviando payload para API do Itaú: " . json_encode($payload));

        // Chamar API de boletos
        $ch = curl_init($boleto_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_SSLCERT, $certFile);
        curl_setopt($ch, CURLOPT_SSLKEY, $keyFile);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            "Authorization: Bearer $access_token"
        ]);

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        // Log da resposta bruta para depuração
        error_log("Resposta bruta da API do Itaú (HTTP $httpcode): $response");
        if (!empty($curl_error)) {
            error_log("Erro cURL: $curl_error");
        }

        if ($httpcode < 200 || $httpcode >= 300) {
            // Log detalhado para depuração
            error_log("Erro na API do Itaú - Payload enviado: " . json_encode($payload));
            error_log("Erro na API do Itaú - Resposta recebida: $response");

            throw new Exception("Erro ao gerar boleto: $httpcode - $response");
        }

        $boleto_data = json_decode($response, true);

        // Log da resposta completa da API para depuração
        error_log("Resposta completa da API do Itaú: " . json_encode($boleto_data));

        // Extrair informações do boleto com verificação detalhada da estrutura
        $nosso_numero = $numero_nosso_numero; // Valor padrão
        $linha_digitavel = '';
        $codigo_barras = '';
        $url_boleto = '';

        // Verifica se a estrutura esperada existe na resposta
        if (isset($boleto_data['data']) &&
            isset($boleto_data['data']['dado_boleto']) &&
            isset($boleto_data['data']['dado_boleto']['dados_individuais_boleto']) &&
            is_array($boleto_data['data']['dado_boleto']['dados_individuais_boleto']) &&
            !empty($boleto_data['data']['dado_boleto']['dados_individuais_boleto'])) {

            $dados_boleto = $boleto_data['data']['dado_boleto']['dados_individuais_boleto'][0];

            // Extrai os dados com verificação de existência
            if (isset($dados_boleto['numero_nosso_numero'])) {
                $nosso_numero = $dados_boleto['numero_nosso_numero'];
                error_log("Nosso número encontrado: $nosso_numero");
            } elseif (isset($dados_boleto['nosso_numero'])) {
                $nosso_numero = $dados_boleto['nosso_numero'];
                error_log("Nosso número encontrado (campo alternativo): $nosso_numero");
            }

            if (isset($dados_boleto['texto_linha_digitavel'])) {
                $linha_digitavel = $dados_boleto['texto_linha_digitavel'];
                error_log("Linha digitável encontrada: $linha_digitavel");
            } elseif (isset($dados_boleto['linha_digitavel'])) {
                $linha_digitavel = $dados_boleto['linha_digitavel'];
                error_log("Linha digitável encontrada (campo alternativo): $linha_digitavel");
            }

            // Se não encontrou a linha digitável, mas tem o código de barras, tenta gerar a linha digitável
            if (empty($linha_digitavel) && !empty($codigo_barras)) {
                error_log("Tentando gerar linha digitável a partir do código de barras...");
                $linha_digitavel = gerarLinhaDigitavel($codigo_barras);
                if (!empty($linha_digitavel)) {
                    error_log("Linha digitável gerada: $linha_digitavel");
                }
            }

            if (isset($dados_boleto['texto_codigo_barras'])) {
                $codigo_barras = $dados_boleto['texto_codigo_barras'];
                error_log("Código de barras encontrado: $codigo_barras");
            } elseif (isset($dados_boleto['codigo_barras'])) {
                $codigo_barras = $dados_boleto['codigo_barras'];
                error_log("Código de barras encontrado (campo alternativo): $codigo_barras");
            }

            // Verifica se o código de barras tem o tamanho correto (44 caracteres para boletos)
            if (!empty($codigo_barras)) {
                // Remove caracteres não numéricos
                $codigo_barras_limpo = preg_replace('/[^0-9]/', '', $codigo_barras);

                if (strlen($codigo_barras_limpo) < 44) {
                    error_log("Código de barras incompleto: $codigo_barras_limpo (" . strlen($codigo_barras_limpo) . " caracteres). Tentando completar...");

                    // Completa o código de barras com zeros à direita até atingir 44 caracteres
                    $codigo_barras = str_pad($codigo_barras_limpo, 44, '0', STR_PAD_RIGHT);
                    error_log("Código de barras completado: $codigo_barras");
                } else if (strlen($codigo_barras_limpo) > 44) {
                    error_log("Código de barras muito longo: $codigo_barras_limpo (" . strlen($codigo_barras_limpo) . " caracteres). Truncando...");

                    // Trunca o código de barras para 44 caracteres
                    $codigo_barras = substr($codigo_barras_limpo, 0, 44);
                    error_log("Código de barras truncado: $codigo_barras");
                } else {
                    $codigo_barras = $codigo_barras_limpo;
                }
            }

            if (isset($dados_boleto['url_acesso_boleto'])) {
                $url_boleto = $dados_boleto['url_acesso_boleto'];
                error_log("URL do boleto encontrada: $url_boleto");
            } elseif (isset($dados_boleto['url_boleto'])) {
                $url_boleto = $dados_boleto['url_boleto'];
                error_log("URL do boleto encontrada (campo alternativo 1): $url_boleto");
            } elseif (isset($dados_boleto['url'])) {
                $url_boleto = $dados_boleto['url'];
                error_log("URL do boleto encontrada (campo alternativo 2): $url_boleto");
            }
        } else {
            // Estrutura diferente da esperada - tenta encontrar os campos em outros locais
            error_log("Estrutura da resposta da API do Itaú diferente da esperada. Tentando localizar campos em outros locais.");

            // Busca recursiva por campos específicos na resposta
            $nosso_numero = findValueInArray($boleto_data, 'numero_nosso_numero') ??
                           findValueInArray($boleto_data, 'nosso_numero') ??
                           $numero_nosso_numero;

            $linha_digitavel = findValueInArray($boleto_data, 'texto_linha_digitavel') ??
                              findValueInArray($boleto_data, 'linha_digitavel') ??
                              '';

            $codigo_barras = findValueInArray($boleto_data, 'texto_codigo_barras') ??
                            findValueInArray($boleto_data, 'codigo_barras') ??
                            '';

            // Verifica se o código de barras tem o tamanho correto (44 caracteres para boletos)
            if (!empty($codigo_barras)) {
                // Remove caracteres não numéricos
                $codigo_barras_limpo = preg_replace('/[^0-9]/', '', $codigo_barras);

                if (strlen($codigo_barras_limpo) < 44) {
                    error_log("Código de barras incompleto (busca recursiva): $codigo_barras_limpo (" . strlen($codigo_barras_limpo) . " caracteres). Tentando completar...");

                    // Completa o código de barras com zeros à direita até atingir 44 caracteres
                    $codigo_barras = str_pad($codigo_barras_limpo, 44, '0', STR_PAD_RIGHT);
                    error_log("Código de barras completado: $codigo_barras");
                } else if (strlen($codigo_barras_limpo) > 44) {
                    error_log("Código de barras muito longo (busca recursiva): $codigo_barras_limpo (" . strlen($codigo_barras_limpo) . " caracteres). Truncando...");

                    // Trunca o código de barras para 44 caracteres
                    $codigo_barras = substr($codigo_barras_limpo, 0, 44);
                    error_log("Código de barras truncado: $codigo_barras");
                } else {
                    $codigo_barras = $codigo_barras_limpo;
                }
            }

            $url_boleto = findValueInArray($boleto_data, 'url_acesso_boleto') ??
                         findValueInArray($boleto_data, 'url_boleto') ??
                         findValueInArray($boleto_data, 'url') ??
                         '';

            // Se não encontrou a linha digitável, mas tem o código de barras, tenta gerar a linha digitável
            if (empty($linha_digitavel) && !empty($codigo_barras)) {
                error_log("Tentando gerar linha digitável a partir do código de barras (busca recursiva)...");
                $linha_digitavel = gerarLinhaDigitavel($codigo_barras);
                if (!empty($linha_digitavel)) {
                    error_log("Linha digitável gerada (busca recursiva): $linha_digitavel");
                }
            }

            error_log("Valores encontrados na busca recursiva: nosso_numero=$nosso_numero, linha_digitavel=$linha_digitavel, codigo_barras=$codigo_barras, url_boleto=$url_boleto");
        }

        // Adiciona o valor formatado aos dados para salvar no banco de dados
        $dados['valor_formatado'] = $valor_formatado;

        // Verificação final dos dados antes de salvar
        if (empty($codigo_barras)) {
            error_log("ATENÇÃO: Código de barras vazio antes de salvar no banco de dados.");
        } else {
            error_log("Código de barras final: $codigo_barras (" . strlen($codigo_barras) . " caracteres)");
        }

        if (empty($linha_digitavel)) {
            error_log("ATENÇÃO: Linha digitável vazia antes de salvar no banco de dados.");
        } else {
            error_log("Linha digitável final: $linha_digitavel");
        }

        // Salva o boleto no banco de dados com dados adicionais para rastreamento
        $boleto_id = salvarBoleto($db, $dados, [
            'nosso_numero' => $nosso_numero,
            'linha_digitavel' => $linha_digitavel,
            'codigo_barras' => $codigo_barras,
            'url_boleto' => $url_boleto,
            'api_ambiente' => 'producao', // Indica o ambiente usado (produção ou teste)
            'api_tipo' => 'cash_management', // Indica o tipo de API usado para gerar o boleto
            'api_token_id' => $token_data['access_token'] ?? null, // Armazena o token usado (parcial)
            'api_response_id' => isset($result['data']['id_remessa']) ? $result['data']['id_remessa'] : null, // ID da remessa na API
            'api_request_data' => json_encode($payload) // Armazena o payload enviado
        ]);

        return [
            'status' => 'sucesso',
            'mensagem' => 'Boleto gerado com sucesso.',
            'boleto_id' => $boleto_id
        ];

    } catch (Exception $e) {
        // Registra o erro
        error_log('Erro ao gerar boleto bancário: ' . $e->getMessage());

        return [
            'status' => 'erro',
            'mensagem' => 'Erro ao gerar boleto bancário: ' . $e->getMessage()
        ];
    }
}

/**
 * Salva o boleto no banco de dados
 */
function salvarBoleto($db, $dados, $resultado_api) {
    // Log para depuração
    error_log("Salvando boleto no banco de dados - Dados da API: " . json_encode($resultado_api));

    // Prepara os dados para inserção
    $boleto = [
        'tipo_entidade' => $dados['tipo_entidade'],
        'entidade_id' => $dados['entidade_id'],
        'nome_pagador' => $dados['nome_pagador'],
        'cpf_pagador' => $dados['cpf_pagador'],
        'endereco' => $dados['endereco'],
        'bairro' => $dados['bairro'],
        'cidade' => $dados['cidade'],
        'uf' => $dados['uf'],
        'cep' => $dados['cep'],
        'descricao' => $dados['descricao'],
        'valor' => isset($dados['valor_formatado']) ? $dados['valor_formatado'] : $dados['valor'],
        'data_emissao' => date('Y-m-d'),
        'data_vencimento' => $dados['data_vencimento'],
        'status' => 'pendente',
        'nosso_numero' => $resultado_api['nosso_numero'],
        'linha_digitavel' => $resultado_api['linha_digitavel'],
        'codigo_barras' => $resultado_api['codigo_barras'],
        'url_boleto' => $resultado_api['url_boleto'],
        'grupo_boletos' => $dados['grupo_boletos'] ?? null,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];

    // Adiciona campos de rastreamento da API se disponíveis
    if (isset($resultado_api['api_ambiente'])) {
        $boleto['api_ambiente'] = $resultado_api['api_ambiente'];
    }

    // Adiciona o tipo de API usado (cash_management ou cobranca)
    $boleto['api_tipo'] = isset($resultado_api['api_tipo']) ? $resultado_api['api_tipo'] : 'cash_management';

    if (isset($resultado_api['api_token_id'])) {
        // Armazena apenas os primeiros 10 caracteres do token por segurança
        $token = $resultado_api['api_token_id'];
        $boleto['api_token_id'] = substr($token, 0, 10) . '...';
    }

    if (isset($resultado_api['api_response_id'])) {
        $boleto['api_response_id'] = $resultado_api['api_response_id'];
    }

    if (isset($resultado_api['api_request_data'])) {
        // Limita o tamanho dos dados armazenados
        $request_data = $resultado_api['api_request_data'];
        $boleto['api_request_data'] = substr($request_data, 0, 1000);
    }

    // Insere o boleto no banco de dados
    $boleto_id = $db->insert('boletos', $boleto);

    // Log para depuração
    error_log("Boleto inserido no banco de dados - ID: $boleto_id");
    error_log("Dados do boleto: " . json_encode($boleto));

    return $boleto_id;
}

/**
 * Cancela um boleto bancário via API do Itaú
 * @param int $boleto_id ID do boleto a ser cancelado
 * @param object $db Objeto de conexão com o banco de dados
 * @param bool $apenas_local Se true, cancela apenas no sistema local sem tentar a API
 * @return array Resultado da operação
 */
function cancelarBoletoBancario($boleto_id, $db, $apenas_local = false) {
    try {
        // Log para depuração
        error_log("Iniciando cancelamento do boleto ID: $boleto_id" . ($apenas_local ? " (apenas local)" : ""));

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
                        'usuario_id' => isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : null,
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

        // Configurações da API do Itaú
        $client_id     = "8a7ee29a-f20d-43b8-b3f5-c559862669a9";
        $client_secret = "a6a29bfe-bec3-4619-b1c0-5653e6322ba0";
        $token_url     = "https://sts.itau.com.br/api/oauth/token";
        $cancelar_url  = "https://api.itau.com.br/cash_management/v2/boletos/" . $boleto['nosso_numero'] . "/cancelamento";
        $certFile      = __DIR__ . '/../../certificados/Certificado.crt';
        $keyFile       = __DIR__ . '/../../certificados/ARQUIVO_CHAVE_PRIVADA.key';

        // Verifica se os certificados existem
        if (!file_exists($certFile) || !file_exists($keyFile)) {
            // Modo de teste - não chama a API real
            // Em ambiente de produção, isso seria um erro
            error_log("Certificados não encontrados. Usando modo de teste para cancelar o boleto ID: $boleto_id");
            error_log("Caminho do certificado: $certFile");
            error_log("Caminho da chave privada: $keyFile");

            try {
                // Atualiza o status do boleto para cancelado
                $result = $db->update('boletos', ['status' => 'cancelado', 'data_cancelamento' => date('Y-m-d H:i:s')], 'id = ?', [$boleto_id]);

                if ($result === false) {
                    error_log("Erro ao atualizar o status do boleto ID: $boleto_id");
                    return ['status' => 'erro', 'mensagem' => 'Erro ao atualizar o status do boleto.'];
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
        error_log("Tentando cancelar o boleto ID: $boleto_id via API");

        // Verifica se o nosso_numero está definido
        if (empty($boleto['nosso_numero'])) {
            error_log("Nosso número não definido para o boleto ID: $boleto_id");

            // Atualiza o status do boleto para cancelado mesmo sem nosso_numero
            try {
                $result = $db->update('boletos', ['status' => 'cancelado', 'data_cancelamento' => date('Y-m-d H:i:s')], 'id = ?', [$boleto_id]);

                if ($result === false) {
                    error_log("Erro ao atualizar o status do boleto ID: $boleto_id");
                    return ['status' => 'erro', 'mensagem' => 'Erro ao atualizar o status do boleto.'];
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

        // Configurações do cURL para cancelar o boleto
        curl_setopt_array($curl, [
            CURLOPT_URL => $cancelar_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
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

        curl_close($curl);

        if ($err) {
            error_log("Erro ao cancelar boleto: $err");
            return ['status' => 'erro', 'mensagem' => 'Erro ao cancelar boleto: ' . $err];
        }

        // Verifica o código de status HTTP
        $http_code = $info['http_code'];
        if ($http_code < 200 || $http_code >= 300) {
            error_log("Erro HTTP ao cancelar boleto: $http_code. Resposta: $response");

            // Se o erro for 404 (boleto não encontrado na API) ou 403 (acesso proibido), cancela localmente
            if ($http_code == 404 || $http_code == 403) {
                $motivo = ($http_code == 404) ? "não encontrado na API" : "acesso proibido (403)";
                error_log("Cancelando boleto localmente devido a erro $http_code ($motivo): ID $boleto_id");

                try {
                    $result = $db->update('boletos', ['status' => 'cancelado', 'data_cancelamento' => date('Y-m-d H:i:s')], 'id = ?', [$boleto_id]);

                    if ($result === false) {
                        error_log("Erro ao atualizar o status do boleto ID: $boleto_id");
                        return ['status' => 'erro', 'mensagem' => 'Erro ao atualizar o status do boleto.'];
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
                $mensagem = "Erro 403 (Acesso Proibido) ao cancelar boleto. Isso pode ser causado por:\n";
                $mensagem .= "1. Certificados inválidos ou expirados\n";
                $mensagem .= "2. Credenciais inválidas (client_id/client_secret)\n";
                $mensagem .= "3. Falta de permissão para cancelar este boleto\n";
                $mensagem .= "4. IP não autorizado a acessar a API\n";
                $mensagem .= "Verifique os logs para mais detalhes.";

                error_log($mensagem);
                return ['status' => 'erro', 'mensagem' => $mensagem];
            }

            return ['status' => 'erro', 'mensagem' => "Erro HTTP ao cancelar boleto: $http_code. Verifique os logs para mais detalhes."];
        }

        // Decodifica a resposta
        $result = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("Erro ao decodificar resposta JSON: " . json_last_error_msg() . ". Resposta: $response");
            return ['status' => 'erro', 'mensagem' => 'Erro ao decodificar resposta JSON: ' . json_last_error_msg()];
        }

        // Verifica se o cancelamento foi bem-sucedido
        if (isset($result['status']) && $result['status'] === 'CANCELADO') {
            // Atualiza o status do boleto para cancelado
            try {
                $update_result = $db->update('boletos', ['status' => 'cancelado', 'data_cancelamento' => date('Y-m-d H:i:s')], 'id = ?', [$boleto_id]);

                if ($update_result === false) {
                    error_log("Erro ao atualizar o status do boleto ID: $boleto_id após cancelamento na API");
                    return ['status' => 'erro', 'mensagem' => 'Boleto cancelado na API, mas houve erro ao atualizar o status no sistema.'];
                }

                error_log("Boleto cancelado com sucesso via API: ID $boleto_id");
                return [
                    'status' => 'sucesso',
                    'mensagem' => 'Boleto cancelado com sucesso.'
                ];
            } catch (Exception $e) {
                error_log("Erro ao atualizar o status do boleto após cancelamento na API: " . $e->getMessage());
                return ['status' => 'erro', 'mensagem' => 'Boleto cancelado na API, mas houve erro ao atualizar o status no sistema: ' . $e->getMessage()];
            }
        } else {
            error_log("Erro ao cancelar boleto. Resposta: $response");
            return ['status' => 'erro', 'mensagem' => 'Erro ao cancelar boleto. Verifique os logs para mais detalhes.'];
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
