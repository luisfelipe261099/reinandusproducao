<?php
/**
 * Página para verificar o ambiente da API do Itaú
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

// Verifica se foi passada uma ação
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Resultados do teste
$resultados = [];

// Processa o teste da API
if ($action === 'testar') {
    try {
        // Configurações da API do Itaú
        $client_id     = "8a7ee29a-f20d-43b8-b3f5-c559862669a9";
        $client_secret = "a6a29bfe-bec3-4619-b1c0-5653e6322ba0";
        
        // URLs de produção e teste
        $token_urls = [
            'producao' => "https://api.itau.com.br/api/oauth/token",
            'teste' => "https://sts.itau.com.br/api/oauth/token"
        ];
        
        $certFile      = __DIR__ . '/../certificados/Certificado.crt';
        $keyFile       = __DIR__ . '/../certificados/ARQUIVO_CHAVE_PRIVADA.key';
        
        // Verifica se os certificados existem
        if (!file_exists($certFile) || !file_exists($keyFile)) {
            throw new Exception("Certificados não encontrados. Verifique se os arquivos existem em: $certFile e $keyFile");
        }
        
        // Testa cada ambiente
        foreach ($token_urls as $ambiente => $token_url) {
            $resultados[] = [
                'etapa' => "Testando ambiente: $ambiente",
                'status' => 'info',
                'mensagem' => "URL: $token_url"
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
                    'etapa' => "Ambiente: $ambiente",
                    'status' => 'erro',
                    'mensagem' => "Erro ao obter token: $err"
                ];
            } else {
                $http_code = $info['http_code'];
                if ($http_code != 200) {
                    $resultados[] = [
                        'etapa' => "Ambiente: $ambiente",
                        'status' => 'erro',
                        'mensagem' => "Erro HTTP ao obter token: $http_code. Resposta: $response"
                    ];
                } else {
                    $token_data = json_decode($response, true);
                    if (!isset($token_data['access_token'])) {
                        $resultados[] = [
                            'etapa' => "Ambiente: $ambiente",
                            'status' => 'erro',
                            'mensagem' => "Resposta inválida ao obter token: $response"
                        ];
                    } else {
                        $access_token = $token_data['access_token'];
                        $resultados[] = [
                            'etapa' => "Ambiente: $ambiente",
                            'status' => 'sucesso',
                            'mensagem' => "Token obtido com sucesso: " . substr($access_token, 0, 10) . "..."
                        ];
                        
                        // Testa a consulta de um boleto de exemplo em cada ambiente
                        $nosso_numero = "03324289"; // Nosso número de exemplo
                        
                        $consultar_urls = [
                            'producao' => "https://api.itau.com.br/cobranca/v2/boletos/$nosso_numero",
                            'teste' => "https://api.itau.com.br/cobranca/v2/boletos/$nosso_numero"
                        ];
                        
                        // Gerar UUID para correlation-id
                        $correlation_id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                            mt_rand(0, 0xffff),
                            mt_rand(0, 0x0fff) | 0x4000,
                            mt_rand(0, 0x3fff) | 0x8000,
                            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                        );
                        
                        curl_setopt_array($curl, [
                            CURLOPT_URL => $consultar_urls[$ambiente],
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
                                'etapa' => "Consulta no ambiente: $ambiente",
                                'status' => 'erro',
                                'mensagem' => "Erro ao consultar boleto: $err"
                            ];
                        } else {
                            $http_code = $info['http_code'];
                            if ($http_code == 200) {
                                $boleto_data = json_decode($response, true);
                                $resultados[] = [
                                    'etapa' => "Consulta no ambiente: $ambiente",
                                    'status' => 'sucesso',
                                    'mensagem' => "Boleto encontrado com sucesso! Situação: " . ($boleto_data['situacao'] ?? 'Não informada')
                                ];
                            } else if ($http_code == 404) {
                                $resultados[] = [
                                    'etapa' => "Consulta no ambiente: $ambiente",
                                    'status' => 'aviso',
                                    'mensagem' => "Boleto não encontrado na API (HTTP 404). Este é o comportamento esperado para um boleto de exemplo."
                                ];
                            } else {
                                $resultados[] = [
                                    'etapa' => "Consulta no ambiente: $ambiente",
                                    'status' => 'erro',
                                    'mensagem' => "Erro HTTP ao consultar boleto: $http_code. Resposta: $response"
                                ];
                            }
                        }
                    }
                }
            }
            
            curl_close($curl);
        }
        
        // Determina qual ambiente está funcionando
        $ambiente_funcionando = null;
        foreach ($resultados as $resultado) {
            if (strpos($resultado['etapa'], 'Ambiente:') === 0 && $resultado['status'] === 'sucesso') {
                $ambiente_funcionando = str_replace('Ambiente: ', '', $resultado['etapa']);
                break;
            }
        }
        
        if ($ambiente_funcionando) {
            $resultados[] = [
                'etapa' => 'Conclusão',
                'status' => 'sucesso',
                'mensagem' => "O ambiente de $ambiente_funcionando está funcionando corretamente. Recomendamos usar este ambiente para todas as operações."
            ];
            
            // Atualiza a configuração no banco de dados
            try {
                $db->query("INSERT INTO configuracoes (chave, valor) VALUES ('api_itau_ambiente', ?) ON DUPLICATE KEY UPDATE valor = ?", [$ambiente_funcionando, $ambiente_funcionando]);
                
                $resultados[] = [
                    'etapa' => 'Configuração',
                    'status' => 'sucesso',
                    'mensagem' => "Configuração atualizada para usar o ambiente de $ambiente_funcionando."
                ];
            } catch (Exception $e) {
                $resultados[] = [
                    'etapa' => 'Configuração',
                    'status' => 'erro',
                    'mensagem' => "Erro ao atualizar configuração: " . $e->getMessage()
                ];
            }
        } else {
            $resultados[] = [
                'etapa' => 'Conclusão',
                'status' => 'erro',
                'mensagem' => "Nenhum ambiente está funcionando corretamente. Verifique as credenciais e certificados."
            ];
        }
        
    } catch (Exception $e) {
        $resultados[] = [
            'etapa' => 'Erro Geral',
            'status' => 'erro',
            'mensagem' => "Erro ao testar API: " . $e->getMessage()
        ];
    }
}

// Define o título da página
$titulo_pagina = 'Verificar Ambiente da API do Itaú';
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
                        <h2 class="text-xl font-bold mb-4">Verificação do Ambiente da API do Itaú</h2>

                        <div class="mb-6 p-4 bg-blue-50 border-l-4 border-blue-400 text-blue-700">
                            <p class="font-bold">Informação:</p>
                            <p>Esta ferramenta verifica qual ambiente da API do Itaú (produção ou teste) está funcionando corretamente com suas credenciais.</p>
                            <p class="mt-2">O teste inclui:</p>
                            <ul class="list-disc list-inside mt-2 ml-4">
                                <li>Verificação dos certificados</li>
                                <li>Obtenção do token de acesso em cada ambiente</li>
                                <li>Consulta de um boleto de exemplo em cada ambiente</li>
                            </ul>
                        </div>

                        <form action="verificar_ambiente_api.php" method="get" class="mb-6">
                            <input type="hidden" name="action" value="testar">
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                <i class="fas fa-vial mr-2"></i> Verificar Ambientes
                            </button>
                        </form>

                        <?php if (!empty($resultados)): ?>
                        <div class="mt-6">
                            <h3 class="text-lg font-bold mb-4">Resultados da Verificação:</h3>
                            
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
