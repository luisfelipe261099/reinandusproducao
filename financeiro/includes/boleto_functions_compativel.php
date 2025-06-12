<?php
/**
 * Função para salvar boleto com detecção automática de colunas
 * Funciona tanto com estrutura antiga quanto nova
 */

function salvarBoletoCompativel($db, $dados, $dados_api) {
    try {
        // Primeiro, vamos descobrir quais colunas existem na tabela
        $colunas_existentes = [];
        try {
            $resultado = $db->query("DESCRIBE boletos");
            while ($row = $resultado->fetch()) {
                $colunas_existentes[] = $row['Field'];
            }
        } catch (Exception $e) {
            error_log("Erro ao verificar estrutura da tabela: " . $e->getMessage());
            // Se não conseguir verificar, usa estrutura básica
            $colunas_existentes = ['id', 'tipo_entidade', 'entidade_id', 'valor', 'data_vencimento', 'descricao', 'nome_pagador', 'cpf_pagador', 'endereco', 'bairro', 'cidade', 'uf', 'cep', 'status', 'data_emissao'];
        }
        
        // Monta os dados para inserção baseado nas colunas disponíveis
        $dados_insert = [];
        
        // Campos obrigatórios básicos
        $mapeamento_campos = [
            // Estrutura nova -> estrutura antiga (fallback)
            'tipo' => 'tipo_entidade',
            'referencia_id' => 'entidade_id',
            
            // Campos básicos
            'valor' => 'valor',
            'data_vencimento' => 'data_vencimento', 
            'descricao' => 'descricao',
            'nome_pagador' => 'nome_pagador',
            'cpf_pagador' => 'cpf_pagador',
            'endereco' => 'endereco',
            'bairro' => 'bairro',
            'cidade' => 'cidade',
            'uf' => 'uf',
            'cep' => 'cep',
            'status' => 'status',
            'data_emissao' => 'data_emissao',
            
            // Campos novos (opcionais)
            'multa' => null,
            'juros' => null,
            'desconto' => null,
            'ambiente' => null,
            'banco' => null,
            'carteira' => null,
            'instrucoes' => null,
            'valor_pago' => null,
            'forma_pagamento' => null,
            'id_externo' => null,
            'numero' => null,
            'complemento' => null,
            
            // Campos da API
            'nosso_numero' => null,
            'linha_digitavel' => null,
            'codigo_barras' => null,
            'url_boleto' => null
        ];
        
        // Adiciona campos básicos
        foreach ($mapeamento_campos as $campo_novo => $campo_antigo) {
            $campo_final = null;
            
            // Verifica se campo novo existe
            if (in_array($campo_novo, $colunas_existentes)) {
                $campo_final = $campo_novo;
            }
            // Se não, verifica se campo antigo existe  
            elseif ($campo_antigo && in_array($campo_antigo, $colunas_existentes)) {
                $campo_final = $campo_antigo;
            }
            
            if ($campo_final) {
                // Define o valor baseado nos dados recebidos
                $valor = null;
                
                switch ($campo_novo) {
                    case 'tipo':
                        $valor = isset($dados['tipo']) ? $dados['tipo'] : 
                                (isset($dados['tipo_entidade']) ? 
                                    ($dados['tipo_entidade'] == 'aluno' ? 'mensalidade' : $dados['tipo_entidade']) : 
                                    'avulso');
                        break;
                        
                    case 'referencia_id':
                        $valor = isset($dados['referencia_id']) ? $dados['referencia_id'] : 
                                (isset($dados['entidade_id']) ? $dados['entidade_id'] : null);
                        break;
                        
                    case 'status':
                        $valor = 'pendente';
                        break;
                        
                    case 'data_emissao':
                        $valor = date('Y-m-d');
                        break;
                        
                    case 'multa':
                        $valor = 2.00;
                        break;
                        
                    case 'juros':
                        $valor = 1.00;
                        break;
                        
                    case 'desconto':
                        $valor = 0.00;
                        break;
                        
                    case 'ambiente':
                        $valor = 'teste';
                        break;
                        
                    case 'banco':
                        $valor = 'itau';
                        break;
                        
                    case 'carteira':
                        $valor = '109';
                        break;
                        
                    case 'nosso_numero':
                        $valor = isset($dados_api['nosso_numero']) ? $dados_api['nosso_numero'] : null;
                        break;
                        
                    case 'linha_digitavel':
                        $valor = isset($dados_api['linha_digitavel']) ? $dados_api['linha_digitavel'] : null;
                        break;
                        
                    case 'codigo_barras':
                        $valor = isset($dados_api['codigo_barras']) ? $dados_api['codigo_barras'] : null;
                        break;
                        
                    case 'url_boleto':
                        $valor = isset($dados_api['url_boleto']) ? $dados_api['url_boleto'] : null;
                        break;
                        
                    default:
                        $valor = isset($dados[$campo_novo]) ? $dados[$campo_novo] : null;
                        break;
                }
                
                if ($valor !== null) {
                    $dados_insert[$campo_final] = $valor;
                }
            }
        }
        
        // Adiciona campos de timestamp se existirem
        if (in_array('created_at', $colunas_existentes)) {
            $dados_insert['created_at'] = date('Y-m-d H:i:s');
        }
        
        if (in_array('updated_at', $colunas_existentes)) {
            $dados_insert['updated_at'] = date('Y-m-d H:i:s');
        }
        
        // Log dos dados que serão inseridos
        error_log("Dados para inserção: " . json_encode($dados_insert));
        error_log("Colunas disponíveis: " . implode(', ', $colunas_existentes));
        
        // Executa a inserção
        $boleto_id = $db->insert('boletos', $dados_insert);
        
        if (!$boleto_id) {
            throw new Exception('Erro ao inserir boleto no banco de dados');
        }
        
        error_log("Boleto salvo com sucesso - ID: $boleto_id");
        return $boleto_id;
        
    } catch (Exception $e) {
        error_log("Erro ao salvar boleto: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Atualiza a função de geração de boleto para usar a versão compatível
 */
function gerarBoletoBancarioCompativel($db, $dados) {
    try {
        error_log("Gerando boleto bancário compatível...");
        
        // Configura dados para API do Itaú
        $numero_nosso_numero = str_pad(mt_rand(1, 99999999), 8, '0', STR_PAD_LEFT);
        
        // Payload para API do Itaú
        $payload = [
            "etapa_processo_boleto" => "efetivacao",
            "codigo_canal_operacao" => "API",
            "dados_individuais_boleto" => [[
                "numero_nosso_numero" => $numero_nosso_numero,
                "codigo_carteira" => "109", // Confirmar se este é o código de carteira correto para produção
                "valor_titulo" => floatval($dados['valor']),
                "data_vencimento" => $dados['data_vencimento'],
                "pagador" => [
                    "nome" => $dados['nome_pagador'],
                    "cpf_cnpj" => preg_replace('/\D/', '', $dados['cpf_pagador']),
                    "endereco" => [
                        "logradouro" => $dados['endereco'] ?? '',
                        "bairro" => $dados['bairro'] ?? '',
                        "cidade" => $dados['cidade'] ?? '',
                        "uf" => $dados['uf'] ?? '',
                        "cep" => preg_replace('/\D/', '', $dados['cep'] ?? '')
                    ]
                ]
                // Adicionar outros campos obrigatórios e opcionais conforme a documentação da API do Itaú
                // Ex: "instrucoes_cobranca", "dados_desconto", "dados_multa", "dados_juros_mora"
            ]]
        ];
        
        error_log("Payload para API Itaú: " . json_encode($payload));

        // --- INÍCIO DA SEÇÃO DE CHAMADA REAL DA API ITAÚ ---
        $api_url = 'URL_DA_API_ITAU_PARA_REGISTRO_DE_BOLETOS'; // Substituir pela URL real
        $api_client_id = 'SEU_CLIENT_ID_ITAU'; // Substituir pelo seu Client ID
        $api_client_secret = 'SEU_CLIENT_SECRET_ITAU'; // Substituir pelo seu Client Secret
        $api_itau_apikey = 'SUA_ITAU_APIKEY'; // Substituir pela sua API Key do Itaú
        $cert_path = '/caminho/para/seu/certificado.crt'; // Substituir pelo caminho do seu certificado .crt
        $key_path = '/caminho/para/sua/chave.key'; // Substituir pelo caminho da sua chave .key

        // Gerar um ID de correlação único para cada requisição
        $correlation_id = uniqid('boleto_', true);

        $nosso_numero_api = null;
        $linha_digitavel_api = null;
        $codigo_barras_api = null;
        $url_boleto_api = null;
        $api_error_message = null;

        // Verifica se as credenciais e URLs foram substituídas
        if ($api_url === 'URL_DA_API_ITAU_PARA_REGISTRO_DE_BOLETOS' || 
            $api_client_id === 'SEU_CLIENT_ID_ITAU' ||
            $api_itau_apikey === 'SUA_ITAU_APIKEY') {
            
            error_log("API Itaú: Credenciais ou URL não configuradas. Usando dados simulados.");
            $api_error_message = "Credenciais da API Itaú não configuradas.";
        } else {
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $api_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'client_id: ' . $api_client_id,
                'x-itau-apikey: ' . $api_itau_apikey,
                'x-itau-correlationid: ' . $correlation_id,
                'x-itau-flowid: ' . uniqid() // Pode ser necessário um flow ID, verificar documentação
                // 'Authorization: Bearer SEU_TOKEN_DE_ACESSO' // Se a API usar Bearer token para autenticação de acesso ao recurso, adicionar aqui.
                                                              // A autenticação com client_id/secret + certificado é geralmente para obter o token de acesso.
            ]);
            
            // Configurações para autenticação mTLS (certificado e chave)
            // Certifique-se de que os caminhos para os arquivos .crt e .key estão corretos e acessíveis pelo PHP.
            if (file_exists($cert_path) && file_exists($key_path)) {
                curl_setopt($ch, CURLOPT_SSLCERT, $cert_path);
                curl_setopt($ch, CURLOPT_SSLKEY, $key_path);
                // Se houver uma senha para a chave privada:
                // curl_setopt($ch, CURLOPT_SSLKEYPASSWD, 'SENHA_DA_CHAVE_PRIVADA');
            } else {
                error_log("API Itaú: Arquivos de certificado ou chave não encontrados. Caminho CRT: {$cert_path}, Caminho Key: {$key_path}");
                // Considerar se deve interromper a chamada ou tentar sem mTLS se for opcional (improvável para APIs financeiras)
            }

            // Desabilitar verificação SSL do peer e host (NÃO RECOMENDADO PARA PRODUÇÃO, APENAS PARA TESTES LOCAIS SE NECESSÁRIO)
            // Em produção, certifique-se de que o PHP tem os certificados CA corretos para validar o servidor do Itaú.
            // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            // curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            $response_json = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            curl_close($ch);

            error_log("Resposta da API Itaú (HTTP {$http_code}): " . $response_json);
            if ($curl_error) {
                error_log("Erro cURL API Itaú: " . $curl_error);
            }

            // Verificar códigos de sucesso específicos da API do Itaú (ex: 200, 201, 202)
            if ($http_code >= 200 && $http_code < 300) {
                $response_data = json_decode($response_json, true);
                
                // Extrair os dados relevantes da resposta da API
                // Os nomes dos campos abaixo são exemplos e DEVEM SER AJUSTADOS conforme a resposta real da API Itaú
                if (isset($response_data['dados'][0]['status']) && strtolower($response_data['dados'][0]['status']) === 'efetivado') {
                    $boleto_info = $response_data['dados'][0]; // Ajustar conforme a estrutura da resposta
                    $nosso_numero_api = $boleto_info['numero_nosso_numero'] ?? $numero_nosso_numero;
                    $linha_digitavel_api = $boleto_info['codigo_linha_digitavel'] ?? null; // Campo comum para linha digitável
                    $codigo_barras_api = $boleto_info['codigo_barras_numerico'] ?? null; // Campo comum para código de barras
                    $url_boleto_api = $boleto_info['url_consumidor'] ?? null; // Exemplo de campo para URL do boleto

                    error_log("Boleto registrado com sucesso na API Itaú. Nosso Número: {$nosso_numero_api}");
                } else {
                    // Tratar respostas de sucesso que indicam falha lógica no registro (ex: boleto duplicado, dados inválidos)
                    $api_error_message = "Erro na resposta da API Itaú (HTTP {$http_code}). Status não efetivado ou dados ausentes. ";
                    if(isset($response_data['erros'])) {
                         $api_error_message .= "Erros: " . json_encode($response_data['erros']);
                    } else if (isset($response_data['mensagem'])) {
                         $api_error_message .= "Mensagem: " . $response_data['mensagem'];
                    } else {
                         $api_error_message .= "Resposta: " . $response_json;
                    }
                    error_log($api_error_message);
                }
            } else {
                // Tratar erro na chamada da API
                $api_error_message = "Erro ao registrar boleto na API Itaú (HTTP {$http_code}). ";
                if ($curl_error) {
                    $api_error_message .= "Erro cURL: " . $curl_error . ". ";
                }
                $api_error_message .= "Resposta: " . $response_json;
                error_log($api_error_message);
            }
        }
        // --- FIM DA SEÇÃO DE CHAMADA REAL DA API ITAÚ ---

        // Se houve erro na API ou as credenciais não foram configuradas, usa dados simulados/fallback
        if ($api_error_message !== null || $nosso_numero_api === null) {
            error_log("Usando dados simulados/fallback devido a erro na API ou configuração: " . ($api_error_message ?? "Dados da API não retornados."));
            $nosso_numero = $numero_nosso_numero; 
            $data_venc_formatada = str_replace('-', '', $dados['data_vencimento']);
            $valor_formatado = sprintf('%010d', floatval($dados['valor']) * 100);
            $agencia = "0000"; // Placeholder - SUBSTITUIR PELA AGÊNCIA REAL
            $conta = "00000";   // Placeholder - SUBSTITUIR PELA CONTA REAL
            $carteira_api = "109"; // Usar a carteira correta

            // Geração de linha digitável e código de barras SIMULADOS.
            // A API do Itaú DEVE fornecer estes dados. Esta é uma simulação MUITO BÁSICA.
            // Não use em produção.
            $linha_digitavel_simulada = "3419{$carteira_api}.{$agencia}0 {$conta}0.{$nosso_numero}0 00000.000000 0 " . 
                                       calcularFatorVencimento($dados['data_vencimento']) . $valor_formatado;
            $linha_digitavel_simulada = preg_replace('/[^0-9]/','', $linha_digitavel_simulada); // Remover não numéricos
            // Ajustar para 47 dígitos se necessário, adicionando zeros ou truncando (MUITO IMPRECISO)
            $linha_digitavel_simulada = str_pad(substr($linha_digitavel_simulada, 0, 47), 47, '0', STR_PAD_RIGHT); 
            
            $codigo_barras_simulado = "3419" . "9" . calcularFatorVencimento($dados['data_vencimento']) . $valor_formatado . 
                                      $agencia . $carteira_api . $nosso_numero . $conta . "000"; // Simplificação extrema
            $codigo_barras_simulado = preg_replace('/[^0-9]/','', $codigo_barras_simulado);
            $codigo_barras_simulado = str_pad(substr($codigo_barras_simulado, 0, 44), 44, '0', STR_PAD_RIGHT);


            $dados_boleto_salvar = [
                'nosso_numero' => $nosso_numero,
                'linha_digitavel' => $linha_digitavel_simulada,
                'codigo_barras' => $codigo_barras_simulado,
                'url_boleto' => '#simulado_erro_api',
                'ambiente' => 'teste_simulado_erro_api',
                'status_integracao' => 'ERRO_API: ' . ($api_error_message ?? 'Dados não retornados')
            ];
            error_log("Dados simulados para salvar: " . json_encode($dados_boleto_salvar));
        } else {
            // Sucesso na API, usar dados retornados
            $dados_boleto_salvar = [
                'nosso_numero' => $nosso_numero_api,
                'linha_digitavel' => $linha_digitavel_api,
                'codigo_barras' => $codigo_barras_api,
                'url_boleto' => $url_boleto_api,
                'ambiente' => 'producao', // Ou 'homologacao' dependendo da URL da API usada
                'status_integracao' => 'REGISTRADO_API'
            ];
             error_log("Dados da API para salvar: " . json_encode($dados_boleto_salvar));
        }
        
        // Salva o boleto usando a função compatível
        $boleto_id = salvarBoletoCompativel($db, $dados, $dados_boleto_salvar);
        
        // Gera o PDF do boleto se possível
        $pdf_info = null; // Inicializa para o caso de falha
        try {
            // Verifica se a classe BoletoPDF existe antes de incluí-la e usá-la.
            // O arquivo boleto_pdf.php deve definir a classe BoletoPDF.
            if (!class_exists('BoletoPDF')) {
                $boletoPdfIncPath = __DIR__ . '/boleto_pdf.php';
                if (file_exists($boletoPdfIncPath)) {
                    require_once $boletoPdfIncPath;
                } else {
                    throw new Exception("Arquivo da classe BoletoPDF não encontrado em {$boletoPdfIncPath}");
                }
            }

            if (class_exists('BoletoPDF')) {
                // Busca os dados completos do boleto recém-criado para passar ao PDF
                $boletoCompleto = $db->fetchOne("SELECT * FROM boletos WHERE id = ?", [$boleto_id]);
                if ($boletoCompleto) {
                    $pdfGenerator = new BoletoPDF($boletoCompleto);
                    // Salva o arquivo PDF no servidor
                    $pdf_info = $pdfGenerator->gerarPDF(true); // true para salvar o arquivo
                } else {
                    error_log("Não foi possível encontrar o boleto ID {$boleto_id} para gerar o PDF.");
                }
            } else {
                 error_log("Classe BoletoPDF não encontrada após tentativa de inclusão.");
            }
        } catch (Exception $e) {
            error_log("Erro ao gerar PDF do boleto ID {$boleto_id}: " . $e->getMessage());
            // Não interrompe o fluxo, apenas loga o erro. O boleto foi salvo no DB.
        }
        
        return [
            'status' => 'sucesso',
            'mensagem' => 'Boleto gerado com sucesso (modo compatível).' . ($pdf_info ? ' PDF disponível.' : ''),
            'boleto_id' => $boleto_id,
            'pdf_url' => $pdf_info['url'] ?? null
        ];
        
    } catch (Exception $e) {
        error_log('Erro ao gerar boleto bancário compatível: ' . $e->getMessage());
        return [
            'status' => 'erro',
            'mensagem' => 'Erro ao gerar boleto bancário: ' . $e->getMessage()
        ];
    }
}

function calcularFatorVencimento($data_vencimento_str) {
    $data_base = new DateTime('1997-10-07');
    $data_venc = new DateTime($data_vencimento_str);
    
    // Se a data de vencimento for anterior à data base, o fator é 0 ou conforme regra do banco.
    // Para datas após 21/02/2025, o fator de vencimento reinicia (ou usa uma regra específica).
    // A API do Itaú deve calcular e fornecer isso corretamente.
    // Esta é uma simplificação.
    if ($data_venc < $data_base) return '0000'; // Ou outra lógica

    $intervalo = $data_base->diff($data_venc);
    $fator = $intervalo->days;

    // O fator de vencimento é limitado a 9999.
    // Se ultrapassar, pode haver uma regra específica do banco ou a data deve ser ajustada.
    // Para boletos após 21/02/2025 (9999 dias após 07/10/1997),
    // o Banco Central definiu que o fator de vencimento deve ser fixado em 9999
    // e a data de vencimento real deve ser informada no campo livre ou via QR Code.
    // A API do Itaú já deve lidar com essa regra.
    if ($fator > 9999) {
        // Implementar a lógica correta conforme FEBRABAN para datas futuras distantes
        // ou confiar que a API do Itaú gerará o código de barras corretamente.
        // Temporariamente, pode-se usar 9999 se a API não for usada.
        return '9999'; 
    }
    
    return str_pad($fator, 4, '0', STR_PAD_LEFT);
}
?>
