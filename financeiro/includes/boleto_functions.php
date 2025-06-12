<?php
/**
 * Funções para geração de boletos bancários via API do Itaú
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
            return gerarBoletoTeste($db, $dados);
        }

        // Formata os dados para a API
        $cpf_pagador = preg_replace('/[^0-9]/', '', $dados['cpf_pagador']);
        $cep = preg_replace('/[^0-9]/', '', $dados['cep']);
        $valor = floatval($dados['valor']);
        $valor_centavos = (int)(round($valor * 100));

        $data_emissao = date('Y-m-d');
        $data_vencimento = $dados['data_vencimento'];

        // Gera o nosso número no formato exigido pela carteira 109 do Itaú
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
        curl_close($ch);

        if ($httpcode < 200 || $httpcode >= 300) {
            throw new Exception("Erro ao gerar boleto: $httpcode - $response");
        }

        $boleto_data = json_decode($response, true);

        // Extrair informações do boleto
        $nosso_numero = $numero_nosso_numero;
        $linha_digitavel = '';
        $codigo_barras = '';
        $url_boleto = '';

        if (isset($boleto_data['data']['dado_boleto']['dados_individuais_boleto'][0])) {
            $dados_boleto = $boleto_data['data']['dado_boleto']['dados_individuais_boleto'][0];
            
            $nosso_numero = $dados_boleto['numero_nosso_numero'] ?? $numero_nosso_numero;
            $linha_digitavel = $dados_boleto['texto_linha_digitavel'] ?? '';
            $codigo_barras = $dados_boleto['texto_codigo_barras'] ?? '';
            $url_boleto = $dados_boleto['url_acesso_boleto'] ?? '';
        }        // Salva o boleto no banco de dados
        $boleto_id = salvarBoleto($db, $dados, [
            'nosso_numero' => $nosso_numero,
            'linha_digitavel' => $linha_digitavel,
            'codigo_barras' => $codigo_barras,
            'url_boleto' => $url_boleto,
            'ambiente' => 'producao'
        ]);

        // Gera o PDF do boleto
        $pdf_info = gerarPDFBoleto($db, $boleto_id);

        return [
            'status' => 'sucesso',
            'mensagem' => 'Boleto gerado com sucesso via API do Itaú.' . ($pdf_info ? ' PDF disponível.' : ''),
            'boleto_id' => $boleto_id,
            'pdf_url' => $pdf_info['url'] ?? null
        ];

    } catch (Exception $e) {
        error_log('Erro ao gerar boleto bancário: ' . $e->getMessage());
        return [
            'status' => 'erro',
            'mensagem' => 'Erro ao gerar boleto bancário: ' . $e->getMessage()
        ];
    }
}

function gerarBoletoTeste($db, $dados) {
    try {
        $valor = floatval($dados['valor']);
        
        // Gera dados simulados para teste
        $nosso_numero = str_pad(mt_rand(1, 99999999), 8, '0', STR_PAD_LEFT);
        $linha_digitavel = '34191.12345 67890.101112 13141.516171 8 ' . date('ymd') . sprintf('%010d', $valor * 100);
        $codigo_barras = '34198' . date('ymd') . sprintf('%010d', $valor * 100) . '191123456789010111213141516171';
        $url_boleto = 'https://exemplo.com/boleto/' . $nosso_numero . '.pdf';        // Salva o boleto no banco de dados
        $boleto_id = salvarBoleto($db, $dados, [
            'nosso_numero' => $nosso_numero,
            'linha_digitavel' => $linha_digitavel,
            'codigo_barras' => $codigo_barras,
            'url_boleto' => $url_boleto,
            'ambiente' => 'teste'
        ]);

        // Gera o PDF do boleto
        $pdf_info = gerarPDFBoleto($db, $boleto_id);

        return [
            'status' => 'sucesso',
            'mensagem' => 'Boleto gerado com sucesso (modo de teste).' . ($pdf_info ? ' PDF disponível.' : ''),
            'boleto_id' => $boleto_id,
            'pdf_url' => $pdf_info['url'] ?? null
        ];

    } catch (Exception $e) {
        error_log('Erro ao gerar boleto de teste: ' . $e->getMessage());
        return [
            'status' => 'erro',
            'mensagem' => 'Erro ao gerar boleto de teste: ' . $e->getMessage()
        ];
    }
}

function salvarBoleto($db, $dados, $dadosBoleto) {
    try {
        // Verifica quais colunas existem na tabela boletos
        $colunas_existentes = [];
        try {
            $describe = $db->query("DESCRIBE boletos");
            while ($row = $describe->fetch()) {
                $colunas_existentes[] = $row['Field'];
            }
        } catch (Exception $e) {
            error_log('Erro ao verificar estrutura da tabela boletos: ' . $e->getMessage());
            // Lista básica de colunas que sempre existem
            $colunas_existentes = ['id', 'tipo_entidade', 'entidade_id', 'valor', 'data_vencimento', 'descricao', 
                                 'nome_pagador', 'cpf_pagador', 'endereco', 'bairro', 'cidade', 'uf', 'cep',
                                 'nosso_numero', 'linha_digitavel', 'codigo_barras', 'url_boleto', 'status'];
        }

        // Monta os dados base que sempre existem
        $dadosInsert = [
            'tipo_entidade' => $dados['tipo_entidade'],
            'entidade_id' => $dados['entidade_id'] ?: null,
            'valor' => $dados['valor'],
            'data_vencimento' => $dados['data_vencimento'],
            'descricao' => $dados['descricao'],
            'nome_pagador' => $dados['nome_pagador'],
            'cpf_pagador' => $dados['cpf_pagador'],
            'endereco' => $dados['endereco'],
            'bairro' => $dados['bairro'],
            'cidade' => $dados['cidade'],
            'uf' => $dados['uf'],
            'cep' => $dados['cep'],
            'nosso_numero' => $dadosBoleto['nosso_numero'],
            'linha_digitavel' => $dadosBoleto['linha_digitavel'],
            'codigo_barras' => $dadosBoleto['codigo_barras'],
            'url_boleto' => $dadosBoleto['url_boleto'],
            'status' => 'pendente'
        ];

        // Adiciona colunas opcionais apenas se existirem
        if (in_array('multa', $colunas_existentes)) {
            $dadosInsert['multa'] = $dados['multa'] ?? 2.00;
        }
        
        if (in_array('juros', $colunas_existentes)) {
            $dadosInsert['juros'] = $dados['juros'] ?? 1.00;
        }
        
        if (in_array('ambiente', $colunas_existentes)) {
            $dadosInsert['ambiente'] = $dadosBoleto['ambiente'] ?? 'teste';
        }
        
        if (in_array('banco', $colunas_existentes)) {
            $dadosInsert['banco'] = $dados['banco'] ?? 'itau';
        }
        
        if (in_array('carteira', $colunas_existentes)) {
            $dadosInsert['carteira'] = $dados['carteira'] ?? '109';
        }
        
        if (in_array('desconto', $colunas_existentes)) {
            $dadosInsert['desconto'] = $dados['desconto'] ?? 0.00;
        }
        
        if (in_array('data_emissao', $colunas_existentes)) {
            $dadosInsert['data_emissao'] = date('Y-m-d');
        }
        
        if (in_array('created_at', $colunas_existentes)) {
            $dadosInsert['created_at'] = date('Y-m-d H:i:s');
        }

        // Log para debug
        error_log("Salvando boleto com dados: " . json_encode($dadosInsert));

        $boleto_id = $db->insert('boletos', $dadosInsert);
        
        error_log("Boleto salvo com ID: $boleto_id");
        
        return $boleto_id;

    } catch (Exception $e) {
        error_log('Erro ao salvar boleto no banco: ' . $e->getMessage());
        error_log('Dados que tentou inserir: ' . json_encode($dadosInsert ?? []));
        throw $e;
    }
}

function findValueInArray($array, $key) {
    if (is_array($array)) {
        if (isset($array[$key])) {
            return $array[$key];
        }
        foreach ($array as $value) {
            if (is_array($value)) {
                $result = findValueInArray($value, $key);
                if ($result !== null) {
                    return $result;
                }
            }
        }
    }
    return null;
}

function gerarLinhaDigitavel($codigoBarras) {
    if (strlen($codigoBarras) != 44) {
        return '';
    }
    
    // Implementação simplificada da linha digitável
    // Em produção, use uma biblioteca específica para isso
    $campo1 = substr($codigoBarras, 0, 4) . substr($codigoBarras, 32, 5);
    $campo2 = substr($codigoBarras, 37, 10);
    $campo3 = substr($codigoBarras, 47, 10);
    $campo4 = substr($codigoBarras, 4, 1);
    $campo5 = substr($codigoBarras, 5, 14);
    
    return $campo1 . ' ' . $campo2 . ' ' . $campo3 . ' ' . $campo4 . ' ' . $campo5;
}

/**
 * Gera o PDF do boleto após criação
 */
function gerarPDFBoleto($db, $boleto_id) {
    try {
        // Busca os dados completos do boleto
        $boleto = $db->fetchOne("
            SELECT b.*,
                   CASE
                       WHEN b.tipo_entidade = 'aluno' THEN a.nome
                       WHEN b.tipo_entidade = 'polo' THEN p.nome
                       ELSE b.nome_pagador
                   END as pagador_nome
            FROM boletos b
            LEFT JOIN alunos a ON b.tipo_entidade = 'aluno' AND b.entidade_id = a.id
            LEFT JOIN polos p ON b.tipo_entidade = 'polo' AND b.entidade_id = p.id
            WHERE b.id = ?
        ", [$boleto_id]);
        
        if (!$boleto) {
            throw new Exception("Boleto não encontrado para gerar PDF");
        }
        
        // Verifica se tem dados suficientes para gerar o PDF
        if (empty($boleto['linha_digitavel']) && empty($boleto['codigo_barras'])) {
            error_log("Boleto {$boleto_id} não tem dados suficientes para gerar PDF");
            return false;
        }
        
        // Inclui a classe de PDF
        require_once __DIR__ . '/boleto_pdf.php';
        
        // Cria o gerador de PDF
        $pdfGenerator = new BoletoPDF($boleto);
        
        // Gera e salva o PDF
        $pdf_info = $pdfGenerator->gerarPDF(true);
        
        // Atualiza o banco com o caminho do PDF
        $db->update('boletos', [
            'url_boleto' => $pdf_info['url']
        ], 'id = ?', [$boleto_id]);
        
        return $pdf_info;
        
    } catch (Exception $e) {
        error_log("Erro ao gerar PDF do boleto {$boleto_id}: " . $e->getMessage());
        return false;
    }
}
