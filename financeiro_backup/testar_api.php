<?php
/**
 * Página para testar a conexão com a API do Itaú
 */

// Inclui os arquivos necessários
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/includes/corrigir_nosso_numero.php';

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

// Verifica se foi passado um ID de boleto para teste
$boleto_id = isset($_GET['boleto_id']) ? (int)$_GET['boleto_id'] : 0;
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Resultados do teste
$resultados = [];

// Processa o teste da API
if ($action === 'testar' && $boleto_id > 0) {
    try {
        // Busca os dados do boleto
        $boleto = $db->fetchOne("SELECT * FROM boletos WHERE id = ?", [$boleto_id]);
        
        if (!$boleto) {
            throw new Exception("Boleto não encontrado.");
        }
        
        // Configurações da API do Itaú
        $client_id     = "8a7ee29a-f20d-43b8-b3f5-c559862669a9";
        $client_secret = "a6a29bfe-bec3-4619-b1c0-5653e6322ba0";
        $token_url     = "https://sts.itau.com.br/api/oauth/token";
        $certFile      = __DIR__ . '/../certificados/Certificado.crt';
        $keyFile       = __DIR__ . '/../certificados/ARQUIVO_CHAVE_PRIVADA.key';
        
        // Verifica se os certificados existem
        if (!file_exists($certFile) || !file_exists($keyFile)) {
            throw new Exception("Certificados não encontrados. Verifique se os arquivos existem em: $certFile e $keyFile");
        }
        
        // Corrige o formato do nosso número
        $nosso_numero_original = $boleto['nosso_numero'];
        $nosso_numero_corrigido = corrigirFormatoNossoNumero($nosso_numero_original);
        
        $resultados[] = [
            'etapa' => 'Verificação do Nosso Número',
            'status' => 'info',
            'mensagem' => "Nosso número original: $nosso_numero_original<br>Nosso número corrigido: $nosso_numero_corrigido"
        ];
        
        // Testa a obtenção do token
        $curl = curl_init();
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
        
        if ($err) {
            $resultados[] = [
                'etapa' => 'Obtenção do Token',
                'status' => 'erro',
                'mensagem' => "Erro ao obter token: $err"
            ];
        } else {
            $http_code = $info['http_code'];
            if ($http_code != 200) {
                $resultados[] = [
                    'etapa' => 'Obtenção do Token',
                    'status' => 'erro',
                    'mensagem' => "Erro HTTP ao obter token: $http_code. Resposta: $response"
                ];
            } else {
                $token_data = json_decode($response, true);
                if (!isset($token_data['access_token'])) {
                    $resultados[] = [
                        'etapa' => 'Obtenção do Token',
                        'status' => 'erro',
                        'mensagem' => "Resposta inválida ao obter token: $response"
                    ];
                } else {
                    $access_token = $token_data['access_token'];
                    $resultados[] = [
                        'etapa' => 'Obtenção do Token',
                        'status' => 'sucesso',
                        'mensagem' => "Token obtido com sucesso: " . substr($access_token, 0, 10) . "..."
                    ];
                    
                    // Testa a consulta do boleto
                    $consultar_url = "https://api.itau.com.br/cobranca/v2/boletos/$nosso_numero_corrigido";
                    
                    // Gerar UUID para correlation-id
                    $correlation_id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                        mt_rand(0, 0xffff),
                        mt_rand(0, 0x0fff) | 0x4000,
                        mt_rand(0, 0x3fff) | 0x8000,
                        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                    );
                    
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
                    
                    $response = curl_exec($curl);
                    $err = curl_error($curl);
                    $info = curl_getinfo($curl);
                    
                    if ($err) {
                        $resultados[] = [
                            'etapa' => 'Consulta do Boleto',
                            'status' => 'erro',
                            'mensagem' => "Erro ao consultar boleto: $err"
                        ];
                    } else {
                        $http_code = $info['http_code'];
                        if ($http_code == 200) {
                            $boleto_data = json_decode($response, true);
                            $resultados[] = [
                                'etapa' => 'Consulta do Boleto',
                                'status' => 'sucesso',
                                'mensagem' => "Boleto encontrado com sucesso! Situação: " . ($boleto_data['situacao'] ?? 'Não informada')
                            ];
                        } else if ($http_code == 404) {
                            $resultados[] = [
                                'etapa' => 'Consulta do Boleto',
                                'status' => 'erro',
                                'mensagem' => "Boleto não encontrado na API (HTTP 404). Resposta: $response"
                            ];
                            
                            // Testa variações do nosso número
                            $resultados[] = [
                                'etapa' => 'Teste de Variações',
                                'status' => 'info',
                                'mensagem' => "Testando variações do nosso número..."
                            ];
                            
                            // Variações a testar
                            $variacoes = [
                                'Sem zeros à esquerda' => ltrim($nosso_numero_corrigido, '0'),
                                'Com prefixo 109' => '109' . $nosso_numero_corrigido,
                                'Últimos 8 dígitos' => substr(preg_replace('/[^0-9]/', '', $nosso_numero_original), -8),
                                'Primeiros 8 dígitos' => substr(preg_replace('/[^0-9]/', '', $nosso_numero_original), 0, 8)
                            ];
                            
                            $encontrado = false;
                            
                            foreach ($variacoes as $descricao => $variacao) {
                                $consultar_url = "https://api.itau.com.br/cobranca/v2/boletos/$variacao";
                                
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
                                
                                $response = curl_exec($curl);
                                $err = curl_error($curl);
                                $info = curl_getinfo($curl);
                                
                                if (!$err && $info['http_code'] == 200) {
                                    $encontrado = true;
                                    $boleto_data = json_decode($response, true);
                                    $resultados[] = [
                                        'etapa' => "Variação: $descricao",
                                        'status' => 'sucesso',
                                        'mensagem' => "Boleto encontrado com a variação '$variacao'! Situação: " . ($boleto_data['situacao'] ?? 'Não informada')
                                    ];
                                    
                                    // Atualiza o nosso número no banco de dados
                                    $db->update('boletos', [
                                        'nosso_numero' => $variacao,
                                        'observacoes' => ($boleto['observacoes'] ? $boleto['observacoes'] . "\n" : "") . 
                                                        "Nosso número atualizado de '$nosso_numero_original' para '$variacao' em " . date('d/m/Y H:i:s')
                                    ], 'id = ?', [$boleto_id]);
                                    
                                    $resultados[] = [
                                        'etapa' => 'Atualização do Nosso Número',
                                        'status' => 'sucesso',
                                        'mensagem' => "Nosso número atualizado no banco de dados para: $variacao"
                                    ];
                                    
                                    break;
                                } else {
                                    $resultados[] = [
                                        'etapa' => "Variação: $descricao",
                                        'status' => 'erro',
                                        'mensagem' => "Boleto não encontrado com a variação '$variacao'"
                                    ];
                                }
                            }
                            
                            if (!$encontrado) {
                                $resultados[] = [
                                    'etapa' => 'Teste de Variações',
                                    'status' => 'erro',
                                    'mensagem' => "Nenhuma variação do nosso número foi encontrada na API."
                                ];
                            }
                        } else {
                            $resultados[] = [
                                'etapa' => 'Consulta do Boleto',
                                'status' => 'erro',
                                'mensagem' => "Erro HTTP ao consultar boleto: $http_code. Resposta: $response"
                            ];
                        }
                    }
                }
            }
        }
        
        curl_close($curl);
        
    } catch (Exception $e) {
        $resultados[] = [
            'etapa' => 'Erro Geral',
            'status' => 'erro',
            'mensagem' => "Erro ao testar API: " . $e->getMessage()
        ];
    }
}

// Define o título da página
$titulo_pagina = 'Testar API do Itaú';
$titulo_pagina_completo = 'Faciência ERP - ' . $titulo_pagina;
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

        .test-success {
            background-color: #D1FAE5;
            border-left: 4px solid #059669;
        }

        .test-error {
            background-color: #FEE2E2;
            border-left: 4px solid #DC2626;
        }

        .test-info {
            background-color: #DBEAFE;
            border-left: 4px solid #3B82F6;
        }

        .test-title {
            font-weight: bold;
            margin-bottom: 0.5rem;
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
                        <h2 class="text-xl font-bold mb-4">Teste de Conexão com a API do Itaú</h2>

                        <div class="mb-6 p-4 bg-blue-50 border-l-4 border-blue-400 text-blue-700">
                            <p class="font-bold">Informação:</p>
                            <p>Esta ferramenta testa a conexão com a API do Itaú e verifica se um boleto específico pode ser encontrado.</p>
                            <p class="mt-2">O teste inclui:</p>
                            <ul class="list-disc list-inside mt-2 ml-4">
                                <li>Verificação dos certificados</li>
                                <li>Obtenção do token de acesso</li>
                                <li>Consulta do boleto com o nosso número</li>
                                <li>Teste de variações do nosso número (se necessário)</li>
                            </ul>
                        </div>

                        <form action="testar_api.php" method="get" class="mb-6">
                            <div class="mb-4">
                                <label for="boleto_id" class="block text-gray-700 font-bold mb-2">ID do Boleto para Teste:</label>
                                <input type="number" id="boleto_id" name="boleto_id" value="<?php echo $boleto_id; ?>" required
                                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </div>
                            <input type="hidden" name="action" value="testar">
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                <i class="fas fa-vial mr-2"></i> Executar Teste
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
                                <a href="gerar_boleto.php?action=visualizar&id=<?php echo $boleto_id; ?>" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
                                    <i class="fas fa-arrow-left mr-2"></i> Voltar para o Boleto
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
