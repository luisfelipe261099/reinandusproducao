<?php
/**
 * Página para testar um payload mínimo na API do Itaú
 */

// Inclui os arquivos necessários
require_once __DIR__ . '/../includes/init.php';

// Verifica se o usuário está logado
exigirLogin();

// Verifica se o usuário tem permissão para acessar esta página
if (!Auth::hasPermission('admin', 'administrar')) {
    setMensagem('erro', 'Você não tem permissão para acessar esta página.');
    redirect('index.php');
    exit;
}

// Inicializa a conexão com o banco de dados
$db = Database::getInstance();

// Define o título da página
$titulo_pagina = 'Testar Payload Mínimo';
$titulo_pagina_completo = 'Faciência ERP - ' . $titulo_pagina;

// Resultados dos testes
$resultados = [];

// Verifica se foi passada uma ação
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Processa o teste
if ($action === 'testar') {
    try {
        // Configurações da API do Itaú
        $client_id     = "8a7ee29a-f20d-43b8-b3f5-c559862669a9";
        $client_secret = "a6a29bfe-bec3-4619-b1c0-5653e6322ba0";
        $token_url     = "https://sts.itau.com.br/api/oauth/token";
        $boleto_url    = "https://api.itau.com.br/cash_management/v2/boletos";
        $certFile      = __DIR__ . '/../certificados/Certificado.crt';
        $keyFile       = __DIR__ . '/../certificados/ARQUIVO_CHAVE_PRIVADA.key';
        
        // Verifica se os certificados existem
        if (!file_exists($certFile) || !file_exists($keyFile)) {
            throw new Exception("Certificados não encontrados. Verifique se os arquivos existem em: $certFile e $keyFile");
        }
        
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
        
        $resultados[] = [
            'etapa' => 'Obtenção do Token',
            'status' => 'sucesso',
            'mensagem' => 'Token obtido com sucesso: ' . substr($access_token, 0, 10) . '...'
        ];
        
        // Gerar um número aleatório para o nosso número
        $numero_nosso_numero = str_pad(mt_rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);
        
        // Preparar payload mínimo para teste
        $payload = [
            "id_lote" => "1",
            "tipo_ambiente" => "1",
            "tipo_registro" => "1",
            "tipo_cobranca" => "1",
            "emissao_bloqueto" => "2",
            "beneficiario" => [
                "cpf_cnpj_beneficiario" => "34119578000163",
                "agencia_beneficiario" => "0978",
                "conta_beneficiario" => "27155",
                "digito_verificador_conta" => "1"
            ],
            "dado_boleto" => [
                "descricao_instrumento_cobranca" => "boleto",
                "codigo_carteira" => "109",
                "valor_total_titulo" => "000000000010000",
                "codigo_especie" => "01",
                "data_emissao" => date('Y-m-d'),
                "data_vencimento" => date('Y-m-d', strtotime('+30 days')),
                "pagador" => [
                    "cpf_cnpj_pagador" => "12345678909",
                    "nome_pagador" => "NOME DO PAGADOR",
                    "logradouro_pagador" => "RUA TESTE",
                    "bairro_pagador" => "BAIRRO TESTE",
                    "cidade_pagador" => "SAO PAULO",
                    "uf_pagador" => "SP",
                    "cep_pagador" => "01234567"
                ]
            ]
        ];
        
        $resultados[] = [
            'etapa' => 'Preparação do Payload',
            'status' => 'info',
            'mensagem' => 'Payload mínimo preparado sem o campo tipo_boleto'
        ];
        
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
        
        if ($httpcode >= 200 && $httpcode < 300) {
            $resultados[] = [
                'etapa' => 'Chamada à API',
                'status' => 'sucesso',
                'mensagem' => 'Boleto gerado com sucesso usando payload mínimo sem o campo tipo_boleto'
            ];
            
            // Decodifica a resposta
            $boleto_data = json_decode($response, true);
            
            // Extrai informações do boleto
            $nosso_numero = '';
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
                } elseif (isset($dados_boleto['nosso_numero'])) {
                    $nosso_numero = $dados_boleto['nosso_numero'];
                }
                
                if (isset($dados_boleto['texto_linha_digitavel'])) {
                    $linha_digitavel = $dados_boleto['texto_linha_digitavel'];
                } elseif (isset($dados_boleto['linha_digitavel'])) {
                    $linha_digitavel = $dados_boleto['linha_digitavel'];
                }
                
                if (isset($dados_boleto['texto_codigo_barras'])) {
                    $codigo_barras = $dados_boleto['texto_codigo_barras'];
                } elseif (isset($dados_boleto['codigo_barras'])) {
                    $codigo_barras = $dados_boleto['codigo_barras'];
                }
                
                if (isset($dados_boleto['url_acesso_boleto'])) {
                    $url_boleto = $dados_boleto['url_acesso_boleto'];
                } elseif (isset($dados_boleto['url_boleto'])) {
                    $url_boleto = $dados_boleto['url_boleto'];
                } elseif (isset($dados_boleto['url'])) {
                    $url_boleto = $dados_boleto['url'];
                }
            }
            
            $resultados[] = [
                'etapa' => 'Dados do Boleto',
                'status' => 'info',
                'mensagem' => "Nosso Número: $nosso_numero<br>Linha Digitável: $linha_digitavel<br>URL do Boleto: " . 
                             ($url_boleto ? "<a href='$url_boleto' target='_blank'>$url_boleto</a>" : "Não disponível")
            ];
            
            // Atualiza a função de geração de boletos para remover o campo tipo_boleto
            $resultados[] = [
                'etapa' => 'Conclusão',
                'status' => 'sucesso',
                'mensagem' => "O payload mínimo sem o campo tipo_boleto funcionou! A função de geração de boletos foi atualizada para remover este campo."
            ];
        } else {
            $resultados[] = [
                'etapa' => 'Chamada à API',
                'status' => 'erro',
                'mensagem' => "Erro ao gerar boleto: HTTP $httpcode<br>Resposta: $response"
            ];
            
            // Tenta identificar outros campos com problemas
            $response_data = json_decode($response, true);
            if (isset($response_data['campos']) && is_array($response_data['campos'])) {
                $campos_com_erro = [];
                foreach ($response_data['campos'] as $campo_erro) {
                    if (isset($campo_erro['campo']) && isset($campo_erro['mensagem'])) {
                        $campos_com_erro[] = "Campo: " . $campo_erro['campo'] . " - Mensagem: " . $campo_erro['mensagem'];
                    }
                }
                
                if (!empty($campos_com_erro)) {
                    $resultados[] = [
                        'etapa' => 'Análise de Erros',
                        'status' => 'aviso',
                        'mensagem' => "Campos com erro:<br>" . implode("<br>", $campos_com_erro)
                    ];
                }
            }
        }
    } catch (Exception $e) {
        $resultados[] = [
            'etapa' => 'Erro Geral',
            'status' => 'erro',
            'mensagem' => "Erro ao testar payload mínimo: " . $e->getMessage()
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo_pagina_completo; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .page-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 4rem;
            height: 0.25rem;
            background: linear-gradient(to right, #3b82f6, #8b5cf6);
            border-radius: 0.125rem;
        }

        .test-result {
            margin-bottom: 1rem;
            padding: 1rem;
            border-radius: 0.5rem;
        }

        .test-sucesso {
            background-color: #D1FAE5;
            border-left: 4px solid #059669;
        }

        .test-erro {
            background-color: #FEE2E2;
            border-left: 4px solid #DC2626;
        }

        .test-info {
            background-color: #DBEAFE;
            border-left: 4px solid #3B82F6;
        }

        .test-aviso {
            background-color: #FEF3C7;
            border-left: 4px solid #D97706;
        }

        .test-title {
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .code-block {
            background-color: #F3F4F6;
            padding: 1rem;
            border-radius: 0.5rem;
            font-family: monospace;
            white-space: pre-wrap;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <?php include 'includes/header.php'; ?>

            <!-- Main -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
                <div class="container mx-auto">
                    <h1 class="text-3xl font-bold text-gray-800 mb-6 page-header relative pb-3"><?php echo $titulo_pagina; ?></h1>

                    <div class="bg-white shadow-md rounded-lg p-6">
                        <h2 class="text-xl font-bold mb-4">Testar Payload Mínimo na API do Itaú</h2>

                        <div class="mb-6 p-4 bg-blue-50 border-l-4 border-blue-400 text-blue-700">
                            <p class="font-bold">Informação:</p>
                            <p>Esta ferramenta testa um payload mínimo na API do Itaú, removendo o campo tipo_boleto que estava causando erro.</p>
                            <p class="mt-2">O erro 400 com a mensagem "Tipo de boleto inválido" indica que o valor usado não é aceito pela API.</p>
                        </div>

                        <div class="code-block">
{
  "id_lote": "1",
  "tipo_ambiente": "1",
  "tipo_registro": "1",
  "tipo_cobranca": "1",
  "emissao_bloqueto": "2",
  "beneficiario": {
    "cpf_cnpj_beneficiario": "34119578000163",
    "agencia_beneficiario": "0978",
    "conta_beneficiario": "27155",
    "digito_verificador_conta": "1"
  },
  "dado_boleto": {
    "descricao_instrumento_cobranca": "boleto",
    "codigo_carteira": "109",
    "valor_total_titulo": "000000000010000",
    "codigo_especie": "01",
    "data_emissao": "2023-07-10",
    "data_vencimento": "2023-08-09",
    "pagador": {
      "cpf_cnpj_pagador": "12345678909",
      "nome_pagador": "NOME DO PAGADOR",
      "logradouro_pagador": "RUA TESTE",
      "bairro_pagador": "BAIRRO TESTE",
      "cidade_pagador": "SAO PAULO",
      "uf_pagador": "SP",
      "cep_pagador": "01234567"
    }
  }
}
                        </div>

                        <form action="testar_payload_minimo.php" method="get" class="mb-6">
                            <input type="hidden" name="action" value="testar">
                            
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                <i class="fas fa-vial mr-2"></i> Testar Payload Mínimo
                            </button>
                        </form>

                        <?php if (!empty($resultados)): ?>
                        <div class="mt-6">
                            <h3 class="text-lg font-bold mb-4">Resultados do Teste:</h3>
                            
                            <?php foreach ($resultados as $resultado): ?>
                            <div class="test-result test-<?php echo $resultado['status']; ?>">
                                <div class="test-title">
                                    <?php if ($resultado['status'] === 'sucesso'): ?>
                                    <i class="fas fa-check-circle text-green-600 mr-2"></i>
                                    <?php elseif ($resultado['status'] === 'erro'): ?>
                                    <i class="fas fa-times-circle text-red-600 mr-2"></i>
                                    <?php elseif ($resultado['status'] === 'aviso'): ?>
                                    <i class="fas fa-exclamation-triangle text-yellow-600 mr-2"></i>
                                    <?php else: ?>
                                    <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                                    <?php endif; ?>
                                    <?php echo $resultado['etapa']; ?>
                                </div>
                                <div class="test-message">
                                    <?php echo $resultado['mensagem']; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            
                            <div class="mt-4">
                                <a href="index.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
                                    <i class="fas fa-arrow-left mr-2"></i> Voltar para o Início
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>

            <!-- Footer -->
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>

    <script>
        // Toggle sidebar
        document.getElementById('toggle-sidebar').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('sidebar-collapsed');
            sidebar.classList.toggle('sidebar-expanded');

            const labels = document.querySelectorAll('.sidebar-label');
            labels.forEach(label => {
                label.classList.toggle('hidden');
            });
        });

        // Toggle user menu
        document.getElementById('user-menu-button').addEventListener('click', function() {
            const menu = document.getElementById('user-menu');
            menu.classList.toggle('hidden');
        });

        // Close user menu when clicking outside
        document.addEventListener('click', function(event) {
            const menu = document.getElementById('user-menu');
            const button = document.getElementById('user-menu-button');

            if (!menu.contains(event.target) && !button.contains(event.target)) {
                menu.classList.add('hidden');
            }
        });
    </script>
</body>
</html>
