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
                "codigo_carteira" => "109",
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
            ]]
        ];
        
        // Simula resposta da API para teste
        $nosso_numero = $numero_nosso_numero;
        $linha_digitavel = '34191.12345 67890.101112 13141.516171 8 ' . date('ymd') . sprintf('%010d', floatval($dados['valor']) * 100);
        $codigo_barras = '34198' . date('ymd') . sprintf('%010d', floatval($dados['valor']) * 100) . '191123456789010111213141516171';
        $url_boleto = 'https://exemplo.com/boleto/' . $nosso_numero . '.pdf';
        
        // Salva o boleto usando a função compatível
        $boleto_id = salvarBoletoCompativel($db, $dados, [
            'nosso_numero' => $nosso_numero,
            'linha_digitavel' => $linha_digitavel,
            'codigo_barras' => $codigo_barras,
            'url_boleto' => $url_boleto,
            'ambiente' => 'teste'
        ]);
        
        // Gera o PDF do boleto se possível
        try {
            require_once __DIR__ . '/boleto_pdf.php';
            $pdf_info = gerarPDFBoleto($db, $boleto_id);
        } catch (Exception $e) {
            error_log("Erro ao gerar PDF: " . $e->getMessage());
            $pdf_info = null;
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
?>
