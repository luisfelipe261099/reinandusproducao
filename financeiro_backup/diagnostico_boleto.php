<?php
/**
 * Página de diagnóstico avançado para boletos
 * Esta página tenta várias abordagens para consultar o boleto no Itaú
 */

// Inclui os arquivos necessários
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/includes/consultar_boleto_api.php';
require_once __DIR__ . '/includes/consultar_boleto_codigo_barras.php';

// Verifica se o usuário está logado
exigirLogin();

// Verifica se o usuário tem permissão para acessar esta página
if (!Auth::hasPermission('financeiro', 'visualizar')) {
    setMensagem('erro', 'Você não tem permissão para verificar o status de boletos.');
    redirect('gerar_boleto.php?action=listar');
    exit;
}

// Inicializa a conexão com o banco de dados
$db = Database::getInstance();

// Verifica se foi passado um ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Verifica se foi passada uma ação
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Busca os dados do boleto
$boleto = $db->fetchOne("SELECT b.*,
                        CASE
                            WHEN b.tipo_entidade = 'aluno' THEN a.nome
                            WHEN b.tipo_entidade = 'polo' THEN p.nome
                            ELSE b.nome_pagador
                        END as nome_pagador
                        FROM boletos b
                        LEFT JOIN alunos a ON b.entidade_id = a.id AND b.tipo_entidade = 'aluno'
                        LEFT JOIN polos p ON b.entidade_id = p.id AND b.tipo_entidade = 'polo'
                        WHERE b.id = ?", [$id]);

// Verifica se o boleto existe
if (!$boleto) {
    setMensagem('erro', 'Boleto não encontrado.');
    redirect('gerar_boleto.php?action=listar');
    exit;
}

// Resultados das verificações
$resultados = [];

// Processa a verificação do status do boleto
if ($action === 'diagnosticar') {
    // 1. Tenta consultar com o formato exato do nosso número
    $nosso_numero_original = $boleto['nosso_numero'];
    $nosso_numero_formatado = "109/" . $nosso_numero_original . "-2"; // Formato: 109/XXXXXXXX-2
    
    $resultados['formato_exato'] = consultarBoletoFormatoExato($boleto['nosso_numero'], $db);
    
    // 2. Tenta consultar com o código de barras
    if (!empty($boleto['codigo_barras'])) {
        $resultados['codigo_barras'] = consultarBoletoCodigoBarras($boleto['codigo_barras'], $db);
    } else {
        $resultados['codigo_barras'] = [
            'status' => 'erro',
            'mensagem' => 'Código de barras não disponível para este boleto.'
        ];
    }
    
    // 3. Verifica o ambiente da API
    $resultados['ambiente'] = verificarAmbienteAPI();
    
    // 4. Verifica o status do boleto no sistema
    $resultados['sistema'] = [
        'status' => 'info',
        'mensagem' => 'Status do boleto no sistema: ' . ucfirst($boleto['status']),
        'dados' => [
            'status' => $boleto['status'],
            'data_emissao' => $boleto['data_emissao'],
            'data_vencimento' => $boleto['data_vencimento'],
            'data_cancelamento' => $boleto['data_cancelamento'],
            'data_pagamento' => $boleto['data_pagamento'],
            'valor' => $boleto['valor'],
            'observacoes' => $boleto['observacoes']
        ]
    ];
}

// Função para verificar o ambiente da API
function verificarAmbienteAPI() {
    try {
        // Configurações da API do Itaú
        $client_id     = "8a7ee29a-f20d-43b8-b3f5-c559862669a9";
        $client_secret = "a6a29bfe-bec3-4619-b1c0-5653e6322ba0";
        $token_url     = "https://sts.itau.com.br/api/oauth/token";
        $certFile      = __DIR__ . '/../certificados/Certificado.crt';
        $keyFile       = __DIR__ . '/../certificados/ARQUIVO_CHAVE_PRIVADA.key';
        
        // Verifica se os certificados existem
        if (!file_exists($certFile) || !file_exists($keyFile)) {
            return [
                'status' => 'erro',
                'mensagem' => 'Certificados não encontrados. Verifique os caminhos: ' . $certFile . ' e ' . $keyFile
            ];
        }
        
        // Inicializa o cURL
        $curl = curl_init();
        
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
        $response = curl_exec($curl);
        $err = curl_error($curl);
        $info = curl_getinfo($curl);
        
        curl_close($curl);
        
        if ($err) {
            return [
                'status' => 'erro',
                'mensagem' => 'Erro ao comunicar com a API do banco: ' . $err
            ];
        }
        
        // Verifica o código de status HTTP
        $http_code = $info['http_code'];
        if ($http_code != 200) {
            return [
                'status' => 'erro',
                'mensagem' => "Erro HTTP ao obter token: $http_code. Resposta: $response"
            ];
        }
        
        // Decodifica a resposta
        $token_data = json_decode($response, true);
        if (!isset($token_data['access_token'])) {
            return [
                'status' => 'erro',
                'mensagem' => 'Resposta inválida da API do banco ao obter token.'
            ];
        }
        
        // Verifica o ambiente com base na URL
        $ambiente = strpos($token_url, 'api.itau.com.br') !== false ? 'Produção' : 'Homologação';
        
        return [
            'status' => 'sucesso',
            'mensagem' => 'Conexão com o ambiente de ' . $ambiente . ' estabelecida com sucesso.',
            'dados' => [
                'ambiente' => $ambiente,
                'url' => $token_url,
                'token_obtido' => true
            ]
        ];
    } catch (Exception $e) {
        return [
            'status' => 'erro',
            'mensagem' => 'Erro ao verificar ambiente da API: ' . $e->getMessage()
        ];
    }
}

// Define o título da página
$titulo_pagina = 'Diagnóstico Avançado de Boleto';
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

        /* Estilos específicos para boletos */
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 9999px;
        }

        .status-pendente {
            background-color: #FEF3C7;
            color: #D97706;
        }

        .status-pago {
            background-color: #D1FAE5;
            color: #059669;
        }

        .status-cancelado {
            background-color: #FEE2E2;
            color: #DC2626;
        }

        .status-vencido {
            background-color: #FEE2E2;
            color: #DC2626;
        }

        .status-desconhecido {
            background-color: #E5E7EB;
            color: #4B5563;
        }

        .diagnostic-section {
            margin-bottom: 1.5rem;
            border: 1px solid #E5E7EB;
            border-radius: 0.5rem;
            overflow: hidden;
        }

        .diagnostic-header {
            padding: 0.75rem 1rem;
            background-color: #F3F4F6;
            border-bottom: 1px solid #E5E7EB;
            font-weight: 600;
        }

        .diagnostic-content {
            padding: 1rem;
            background-color: #FFFFFF;
        }

        .diagnostic-success {
            border-color: #86EFAC;
        }

        .diagnostic-success .diagnostic-header {
            background-color: #ECFDF5;
            border-color: #86EFAC;
            color: #166534;
        }

        .diagnostic-error {
            border-color: #FCA5A5;
        }

        .diagnostic-error .diagnostic-header {
            background-color: #FEF2F2;
            border-color: #FCA5A5;
            color: #B91C1C;
        }

        .diagnostic-warning {
            border-color: #FCD34D;
        }

        .diagnostic-warning .diagnostic-header {
            background-color: #FFFBEB;
            border-color: #FCD34D;
            color: #B45309;
        }

        .diagnostic-info {
            border-color: #93C5FD;
        }

        .diagnostic-info .diagnostic-header {
            background-color: #EFF6FF;
            border-color: #93C5FD;
            color: #1E40AF;
        }

        .code-block {
            font-family: monospace;
            background-color: #F3F4F6;
            padding: 0.75rem;
            border-radius: 0.25rem;
            white-space: pre-wrap;
            overflow-x: auto;
            font-size: 0.875rem;
            line-height: 1.5;
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
                        <h1 class="text-2xl font-bold mb-6 text-purple-800">Diagnóstico Avançado do Boleto</h1>

                        <div class="mb-6">
                            <div class="mb-6 p-4 bg-blue-50 border-l-4 border-blue-400 text-blue-700">
                                <p class="font-bold">Informação:</p>
                                <p>Esta página realiza um diagnóstico avançado do boleto, tentando várias abordagens para consultar o status no Itaú.</p>
                                <p class="mt-2">O sistema tentará consultar usando o formato exato do nosso número, o código de barras e verificará o ambiente da API.</p>
                            </div>

                            <div class="bg-gray-100 p-4 rounded-lg mb-6">
                                <p><strong>Número do Boleto:</strong> <?php echo $boleto['id']; ?></p>
                                <p><strong>Nosso Número (Original):</strong> <?php echo $boleto['nosso_numero']; ?></p>
                                <p><strong>Nosso Número (Formato Itaú):</strong> <span class="font-mono bg-blue-100 px-2 py-1 rounded">109/<?php echo $boleto['nosso_numero']; ?>-2</span></p>
                                <p><strong>Código de Barras:</strong> <span class="font-mono bg-blue-100 px-2 py-1 rounded"><?php echo $boleto['codigo_barras']; ?></span></p>
                                <p><strong>Pagador:</strong> <?php echo $boleto['nome_pagador']; ?></p>
                                <p><strong>Valor:</strong> R$ <?php echo number_format($boleto['valor'], 2, ',', '.'); ?></p>
                                <p><strong>Vencimento:</strong> <?php echo date('d/m/Y', strtotime($boleto['data_vencimento'])); ?></p>
                                <p><strong>Status no Sistema:</strong> 
                                    <span class="status-badge <?php 
                                        echo $boleto['status'] === 'pendente' ? 'status-pendente' : 
                                            ($boleto['status'] === 'pago' ? 'status-pago' : 
                                                ($boleto['status'] === 'cancelado' ? 'status-cancelado' : 
                                                    ($boleto['status'] === 'vencido' ? 'status-vencido' : 'status-desconhecido'))); 
                                    ?>">
                                        <?php echo ucfirst($boleto['status']); ?>
                                    </span>
                                </p>
                            </div>

                            <?php if ($action === 'diagnosticar'): ?>
                            <div class="mb-6">
                                <h2 class="text-xl font-bold mb-4">Resultados do Diagnóstico</h2>
                                
                                <!-- Resultado da consulta com formato exato -->
                                <div class="diagnostic-section <?php 
                                    echo $resultados['formato_exato']['status'] === 'sucesso' ? 'diagnostic-success' : 
                                        ($resultados['formato_exato']['status'] === 'nao_encontrado' ? 'diagnostic-warning' : 'diagnostic-error'); 
                                ?>">
                                    <div class="diagnostic-header">
                                        <i class="<?php 
                                            echo $resultados['formato_exato']['status'] === 'sucesso' ? 'fas fa-check-circle text-green-600' : 
                                                ($resultados['formato_exato']['status'] === 'nao_encontrado' ? 'fas fa-exclamation-triangle text-yellow-600' : 'fas fa-times-circle text-red-600'); 
                                        ?> mr-2"></i>
                                        Consulta com Formato Exato do Nosso Número
                                    </div>
                                    <div class="diagnostic-content">
                                        <p class="mb-2"><strong>Formato usado:</strong> <span class="font-mono"><?php echo $nosso_numero_formatado; ?></span></p>
                                        <p class="mb-2"><strong>Resultado:</strong> <?php echo $resultados['formato_exato']['mensagem']; ?></p>
                                        
                                        <?php if ($resultados['formato_exato']['status'] === 'sucesso'): ?>
                                        <div class="mt-4">
                                            <p class="mb-2"><strong>Detalhes do boleto:</strong></p>
                                            <div class="code-block"><?php echo json_encode($resultados['formato_exato']['dados'], JSON_PRETTY_PRINT); ?></div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Resultado da consulta com código de barras -->
                                <div class="diagnostic-section <?php 
                                    echo $resultados['codigo_barras']['status'] === 'sucesso' ? 'diagnostic-success' : 
                                        ($resultados['codigo_barras']['status'] === 'nao_encontrado' ? 'diagnostic-warning' : 'diagnostic-error'); 
                                ?>">
                                    <div class="diagnostic-header">
                                        <i class="<?php 
                                            echo $resultados['codigo_barras']['status'] === 'sucesso' ? 'fas fa-check-circle text-green-600' : 
                                                ($resultados['codigo_barras']['status'] === 'nao_encontrado' ? 'fas fa-exclamation-triangle text-yellow-600' : 'fas fa-times-circle text-red-600'); 
                                        ?> mr-2"></i>
                                        Consulta com Código de Barras
                                    </div>
                                    <div class="diagnostic-content">
                                        <?php if (isset($resultados['codigo_barras']['codigo_barras'])): ?>
                                        <p class="mb-2"><strong>Código de barras usado:</strong> <span class="font-mono"><?php echo $resultados['codigo_barras']['codigo_barras']; ?></span></p>
                                        <?php endif; ?>
                                        <p class="mb-2"><strong>Resultado:</strong> <?php echo $resultados['codigo_barras']['mensagem']; ?></p>
                                        
                                        <?php if ($resultados['codigo_barras']['status'] === 'sucesso'): ?>
                                        <div class="mt-4">
                                            <p class="mb-2"><strong>Detalhes do boleto:</strong></p>
                                            <div class="code-block"><?php echo json_encode($resultados['codigo_barras']['dados'], JSON_PRETTY_PRINT); ?></div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Verificação do ambiente da API -->
                                <div class="diagnostic-section <?php 
                                    echo $resultados['ambiente']['status'] === 'sucesso' ? 'diagnostic-success' : 'diagnostic-error'; 
                                ?>">
                                    <div class="diagnostic-header">
                                        <i class="<?php 
                                            echo $resultados['ambiente']['status'] === 'sucesso' ? 'fas fa-check-circle text-green-600' : 'fas fa-times-circle text-red-600'; 
                                        ?> mr-2"></i>
                                        Verificação do Ambiente da API
                                    </div>
                                    <div class="diagnostic-content">
                                        <p class="mb-2"><strong>Resultado:</strong> <?php echo $resultados['ambiente']['mensagem']; ?></p>
                                        
                                        <?php if ($resultados['ambiente']['status'] === 'sucesso' && isset($resultados['ambiente']['dados'])): ?>
                                        <div class="mt-4">
                                            <p class="mb-2"><strong>Detalhes do ambiente:</strong></p>
                                            <div class="code-block"><?php echo json_encode($resultados['ambiente']['dados'], JSON_PRETTY_PRINT); ?></div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Status do boleto no sistema -->
                                <div class="diagnostic-section diagnostic-info">
                                    <div class="diagnostic-header">
                                        <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                                        Status do Boleto no Sistema
                                    </div>
                                    <div class="diagnostic-content">
                                        <p class="mb-2"><strong>Status:</strong> <?php echo ucfirst($boleto['status']); ?></p>
                                        
                                        <div class="mt-4">
                                            <p class="mb-2"><strong>Detalhes do boleto no sistema:</strong></p>
                                            <div class="code-block"><?php echo json_encode($resultados['sistema']['dados'], JSON_PRETTY_PRINT); ?></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Conclusão do diagnóstico -->
                                <div class="mt-6 p-4 bg-indigo-50 border-l-4 border-indigo-400 text-indigo-700">
                                    <p class="font-bold">Conclusão do Diagnóstico:</p>
                                    <?php
                                    // Determina a conclusão com base nos resultados
                                    $encontrado_api = $resultados['formato_exato']['status'] === 'sucesso' || $resultados['codigo_barras']['status'] === 'sucesso';
                                    $ambiente_ok = $resultados['ambiente']['status'] === 'sucesso';
                                    $status_sistema = $boleto['status'];
                                    
                                    if ($encontrado_api) {
                                        echo '<p>O boleto foi encontrado na API do Itaú. ';
                                        
                                        if ($status_sistema === 'cancelado') {
                                            echo 'O status no sistema está como "Cancelado", o que está correto se o boleto foi cancelado anteriormente.</p>';
                                        } else {
                                            echo 'O status no sistema está como "' . ucfirst($status_sistema) . '", mas o boleto existe na API do Itaú.</p>';
                                            echo '<p class="mt-2">Recomendação: Verifique se o status no sistema está correto e atualize-o se necessário.</p>';
                                        }
                                    } else {
                                        if ($ambiente_ok) {
                                            echo '<p>O boleto não foi encontrado na API do Itaú, mas a conexão com o ambiente da API está funcionando corretamente.</p>';
                                            
                                            if ($status_sistema === 'cancelado') {
                                                echo '<p class="mt-2">Como o status no sistema está como "Cancelado", é possível que o boleto tenha sido cancelado anteriormente e não esteja mais disponível na API do Itaú.</p>';
                                            } else {
                                                echo '<p class="mt-2">Possíveis causas:</p>';
                                                echo '<ul class="list-disc ml-6 mt-2">';
                                                echo '<li>O boleto não foi registrado corretamente no Itaú</li>';
                                                echo '<li>O nosso número ou código de barras está incorreto</li>';
                                                echo '<li>O boleto foi cancelado anteriormente no banco</li>';
                                                echo '</ul>';
                                            }
                                        } else {
                                            echo '<p>Não foi possível estabelecer conexão com a API do Itaú. Verifique as configurações de conexão e os certificados.</p>';
                                        }
                                    }
                                    ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <div class="flex flex-wrap gap-4">
                                <a href="diagnostico_boleto.php?action=diagnosticar&id=<?php echo $id; ?>" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">
                                    <i class="fas fa-stethoscope mr-2"></i> Executar Diagnóstico
                                </a>
                                <a href="gerar_boleto.php?action=visualizar&id=<?php echo $id; ?>" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
                                    <i class="fas fa-arrow-left mr-2"></i> Voltar
                                </a>
                            </div>
                        </div>
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
