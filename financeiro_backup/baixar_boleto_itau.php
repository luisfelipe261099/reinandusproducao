<?php
/**
 * Página para baixar boletos diretamente na API do Itaú usando o formato correto do nosso número
 */

// Inclui os arquivos necessários
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/includes/corrigir_nosso_numero.php';

// Verifica se o usuário está logado
exigirLogin();

// Verifica se o usuário tem permissão para acessar esta página
if (!Auth::hasPermission('financeiro', 'editar')) {
    setMensagem('erro', 'Você não tem permissão para baixar boletos.');
    redirect('gerar_boleto.php?action=listar');
    exit;
}

// Inicializa a conexão com o banco de dados
$db = Database::getInstance();

// Verifica se foi passado um ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Verifica se foi passado uma ação
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

// Verifica se o boleto já está cancelado
if ($boleto['status'] === 'cancelado') {
    setMensagem('aviso', 'Este boleto já está cancelado.');
    redirect('gerar_boleto.php?action=visualizar&id=' . $id);
    exit;
}

// Verifica se o boleto já está pago
if ($boleto['status'] === 'pago') {
    setMensagem('erro', 'Não é possível cancelar um boleto já pago.');
    redirect('gerar_boleto.php?action=visualizar&id=' . $id);
    exit;
}

// Processa a baixa do boleto
if ($action === 'confirmar') {
    try {
        // Corrige o formato do nosso número para o padrão exigido pela API
        $nosso_numero_original = $boleto['nosso_numero'];
        $nosso_numero_corrigido = corrigirFormatoNossoNumero($nosso_numero_original);
        
        // Formata o nosso número no padrão visual do Itaú
        $nosso_numero_formatado = formatarNossoNumeroItau($nosso_numero_original);
        
        // Log para depuração
        error_log("Baixando boleto ID: $id");
        error_log("Nosso número original: $nosso_numero_original");
        error_log("Nosso número corrigido para API: $nosso_numero_corrigido");
        error_log("Nosso número formatado Itaú: $nosso_numero_formatado");
        
        // Configurações da API do Itaú
        $client_id     = "8a7ee29a-f20d-43b8-b3f5-c559862669a9";
        $client_secret = "a6a29bfe-bec3-4619-b1c0-5653e6322ba0";
        $token_url     = "https://sts.itau.com.br/api/oauth/token";
        $baixar_url    = "https://api.itau.com.br/cobranca/v2/boletos/" . $nosso_numero_corrigido . "/baixas";
        $certFile      = __DIR__ . '/../certificados/Certificado.crt';
        $keyFile       = __DIR__ . '/../certificados/ARQUIVO_CHAVE_PRIVADA.key';
        
        // Verifica se os certificados existem
        if (!file_exists($certFile) || !file_exists($keyFile)) {
            throw new Exception("Certificados não encontrados. Verifique se os arquivos existem em: $certFile e $keyFile");
        }
        
        // Obter token de acesso
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
            throw new Exception("Erro ao obter token: $err");
        }
        
        if ($info['http_code'] != 200) {
            throw new Exception("Erro HTTP ao obter token: " . $info['http_code'] . ". Resposta: $response");
        }
        
        $token_data = json_decode($response, true);
        if (!isset($token_data['access_token'])) {
            throw new Exception("Resposta inválida ao obter token: $response");
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
        error_log("URL de baixa: $baixar_url");
        
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
        $response = curl_exec($curl);
        $err = curl_error($curl);
        $info = curl_getinfo($curl);
        
        // Log das informações da requisição
        error_log("Informações da requisição de baixa: " . json_encode($info));
        error_log("Resposta da requisição de baixa: " . $response);
        
        curl_close($curl);
        
        if ($err) {
            throw new Exception("Erro ao baixar boleto: $err");
        }
        
        // Verifica o código de status HTTP
        $http_code = $info['http_code'];
        
        // Códigos de sucesso: 200 (OK) ou 204 (No Content)
        if ($http_code == 200 || $http_code == 204) {
            // Atualiza o status do boleto para cancelado
            $result = $db->update('boletos', [
                'status' => 'cancelado', 
                'data_cancelamento' => date('Y-m-d H:i:s'),
                'observacoes' => ($boleto['observacoes'] ? $boleto['observacoes'] . "\n" : "") . 
                                "Baixado via API em " . date('d/m/Y H:i:s') . 
                                " usando nosso número: $nosso_numero_corrigido (formato Itaú: $nosso_numero_formatado)"
            ], 'id = ?', [$id]);
            
            if ($result === false) {
                throw new Exception("Erro ao atualizar o status do boleto após baixa na API");
            }
            
            // Registra o cancelamento na tabela de histórico
            $db->insert('boletos_historico', [
                'boleto_id' => $id,
                'acao' => 'baixa_api',
                'data' => date('Y-m-d H:i:s'),
                'usuario_id' => isset($_SESSION['usuario']['id']) ? $_SESSION['usuario']['id'] : null,
                'detalhes' => "Baixado via API do Itaú usando nosso número: $nosso_numero_corrigido (formato Itaú: $nosso_numero_formatado)"
            ]);
            
            setMensagem('sucesso', 'Boleto baixado com sucesso na API do Itaú.');
            redirect('gerar_boleto.php?action=visualizar&id=' . $id);
            exit;
        } else {
            // Tenta decodificar a resposta para obter mais detalhes do erro
            $error_details = json_decode($response, true);
            $error_message = "Erro HTTP ao baixar boleto: $http_code.";
            
            if (json_last_error() === JSON_ERROR_NONE && isset($error_details['mensagem'])) {
                $error_message .= " Mensagem: " . $error_details['mensagem'];
            }
            
            throw new Exception($error_message);
        }
    } catch (Exception $e) {
        setMensagem('erro', 'Erro ao baixar boleto: ' . $e->getMessage());
        redirect('baixar_boleto_itau.php?id=' . $id);
        exit;
    }
}

// Define o título da página
$titulo_pagina = 'Baixar Boleto no Itaú';
$titulo_pagina_completo = 'Faciência ERP - ' . $titulo_pagina;

// Formata o nosso número no padrão visual do Itaú
$nosso_numero_formatado = formatarNossoNumeroItau($boleto['nosso_numero']);
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

        .nosso-numero-box {
            background-color: #DBEAFE;
            border: 1px solid #93C5FD;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .nosso-numero-title {
            font-weight: bold;
            margin-bottom: 0.5rem;
            color: #1E40AF;
        }

        .nosso-numero-value {
            font-family: monospace;
            font-size: 1.25rem;
            background-color: #EFF6FF;
            padding: 0.5rem;
            border-radius: 0.25rem;
            border: 1px dashed #93C5FD;
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
                        <h1 class="text-2xl font-bold mb-6 text-purple-800">Baixar Boleto no Itaú</h1>

                        <div class="mb-6">
                            <div class="mb-6 p-4 bg-blue-50 border-l-4 border-blue-400 text-blue-700">
                                <p class="font-bold">Informação:</p>
                                <p>Esta página utiliza o formato correto do nosso número para baixar o boleto diretamente na API do Itaú.</p>
                                <p class="mt-2">O nosso número será formatado conforme o padrão exigido pelo Itaú (109/XXXXXXXX-Y).</p>
                            </div>

                            <div class="nosso-numero-box">
                                <div class="nosso-numero-title">Nosso Número (Banco de Dados):</div>
                                <div class="nosso-numero-value"><?php echo $boleto['nosso_numero']; ?></div>
                                
                                <div class="nosso-numero-title mt-4">Nosso Número (Formato Itaú):</div>
                                <div class="nosso-numero-value"><?php echo $nosso_numero_formatado; ?></div>
                                
                                <div class="nosso-numero-title mt-4">Nosso Número (Para API):</div>
                                <div class="nosso-numero-value"><?php echo corrigirFormatoNossoNumero($boleto['nosso_numero']); ?></div>
                            </div>

                            <div class="bg-gray-100 p-4 rounded-lg mb-6">
                                <p><strong>Número do Boleto:</strong> <?php echo $boleto['id']; ?></p>
                                <p><strong>Pagador:</strong> <?php echo $boleto['nome_pagador']; ?></p>
                                <p><strong>Valor:</strong> R$ <?php echo number_format($boleto['valor'], 2, ',', '.'); ?></p>
                                <p><strong>Vencimento:</strong> <?php echo date('d/m/Y', strtotime($boleto['data_vencimento'])); ?></p>
                                <p><strong>Status:</strong> 
                                    <span class="status-badge <?php 
                                        echo $boleto['status'] === 'pendente' ? 'status-pendente' : 
                                            ($boleto['status'] === 'pago' ? 'status-pago' : 
                                                ($boleto['status'] === 'cancelado' ? 'status-cancelado' : 'status-vencido')); 
                                    ?>">
                                        <?php echo ucfirst($boleto['status']); ?>
                                    </span>
                                </p>
                            </div>

                            <div class="flex flex-wrap gap-4">
                                <a href="baixar_boleto_itau.php?action=confirmar&id=<?php echo $id; ?>" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded" onclick="return confirm('Tem certeza que deseja baixar este boleto na API do Itaú?');">
                                    <i class="fas fa-check-circle mr-2"></i> Baixar Boleto na API
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
