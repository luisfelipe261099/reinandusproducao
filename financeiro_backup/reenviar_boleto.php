<?php
/**
 * Página para reenviar o registro de um boleto para a API do Itaú
 */

// Inclui os arquivos necessários
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/includes/processar_boleto.php';

// Verifica se o usuário está logado
exigirLogin();

// Verifica se o usuário tem permissão para acessar esta página
if (!Auth::hasPermission('financeiro', 'editar')) {
    setMensagem('erro', 'Você não tem permissão para reenviar boletos.');
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
    setMensagem('aviso', 'Não é possível reenviar um boleto cancelado.');
    redirect('gerar_boleto.php?action=visualizar&id=' . $id);
    exit;
}

// Verifica se o boleto já está pago
if ($boleto['status'] === 'pago') {
    setMensagem('aviso', 'Não é possível reenviar um boleto já pago.');
    redirect('gerar_boleto.php?action=visualizar&id=' . $id);
    exit;
}

// Processa o reenvio do boleto
if ($action === 'confirmar') {
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
            throw new Exception("Certificados não encontrados. Não é possível reenviar o boleto.");
        }

        // Formata os dados para a API
        $cpf_pagador = preg_replace('/[^0-9]/', '', $boleto['cpf_pagador']);
        $cep = preg_replace('/[^0-9]/', '', $boleto['cep']);

        // Formata o valor conforme esperado pela API do Itaú (sem pontos ou vírgulas)
        $valor = $boleto['valor']; // Já está no formato correto no banco
        $valor_centavos = (int)(round($valor * 100)); // Converte para centavos (inteiro)

        $data_emissao = date('Y-m-d');
        $data_vencimento = $boleto['data_vencimento'];

        // Gera um novo nosso número no formato exigido pela carteira 109 do Itaú
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
                    "tipo_boleto"         => "a_vista",
                    "forma_envio"         => "nao_envia",
                    "pagador"             => [
                        "pessoa" => [
                            "nome_pessoa"       => $boleto['nome_pagador'],
                            "tipo_pessoa"       => [
                                "codigo_tipo_pessoa" => strlen($cpf_pagador) > 11 ? "J" : "F",
                                "numero_cadastro_nacional_pessoa_juridica" => strlen($cpf_pagador) > 11 ? $cpf_pagador : "",
                                "numero_cadastro_pessoa_fisica" => strlen($cpf_pagador) <= 11 ? $cpf_pagador : ""
                            ]
                        ],
                        "endereco" => [
                            "nome_logradouro" => $boleto['endereco'],
                            "nome_bairro"     => $boleto['bairro'],
                            "nome_cidade"     => $boleto['cidade'],
                            "sigla_UF"        => $boleto['uf'],
                            "numero_CEP"      => $cep
                        ]
                    ],
                    "dados_individuais_boleto" => [[
                        "numero_nosso_numero" => $numero_nosso_numero,
                        "data_vencimento"     => $data_vencimento,
                        "data_limite_pagamento" => $data_vencimento,
                        "valor_titulo"        => (string)$valor_centavos,
                        "texto_uso_beneficiario" => $boleto['descricao'],
                        "texto_seu_numero"    => "12345"
                    ]],
                    "multa"                 => [
                        "codigo_tipo_multa"      => "02",
                        "quantidade_dias_multa"  => 1,
                        "percentual_multa"       => "000000000200" // 2%
                    ],
                    "juros"                 => [
                        "codigo_tipo_juros"      => "B",
                        "quantidade_dias_juros"  => 1,
                        "percentual_juros"       => "000000000100" // 1%
                    ]
                ]
            ]
        ];

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

        if ($err || $info['http_code'] != 200) {
            throw new Exception("Erro ao obter token de acesso: " . ($err ? $err : "HTTP " . $info['http_code']));
        }

        $token_data = json_decode($response, true);
        if (!isset($token_data['access_token'])) {
            throw new Exception("Resposta inválida ao obter token de acesso");
        }

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
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($httpcode < 200 || $httpcode >= 300) {
            throw new Exception("Erro ao reenviar boleto: HTTP $httpcode - $response");
        }

        // Processa a resposta
        $result = json_decode($response, true);

        // Extrai os dados do boleto da resposta
        $nosso_numero = $numero_nosso_numero;
        $linha_digitavel = "";
        $codigo_barras = "";
        $url_boleto = "";

        // Busca recursiva para encontrar os dados do boleto na resposta
        function buscarDadosRecursivo($array, &$nosso_numero, &$linha_digitavel, &$codigo_barras, &$url_boleto) {
            foreach ($array as $key => $value) {
                if ($key === 'numero_linha_digitavel') {
                    $linha_digitavel = $value;
                } elseif ($key === 'numero_codigo_barras') {
                    $codigo_barras = $value;
                } elseif ($key === 'url_acesso_boleto') {
                    $url_boleto = $value;
                } elseif ($key === 'numero_nosso_numero') {
                    $nosso_numero = $value;
                } elseif (is_array($value)) {
                    buscarDadosRecursivo($value, $nosso_numero, $linha_digitavel, $codigo_barras, $url_boleto);
                }
            }
        }

        buscarDadosRecursivo($result, $nosso_numero, $linha_digitavel, $codigo_barras, $url_boleto);

        // Atualiza o boleto no banco de dados
        $dados_update = [
            'nosso_numero' => $nosso_numero,
            'linha_digitavel' => $linha_digitavel,
            'codigo_barras' => $codigo_barras,
            'url_boleto' => $url_boleto,
            'observacoes' => ($boleto['observacoes'] ? $boleto['observacoes'] . "\n" : "") . 
                            "Boleto reenviado para a API em " . date('d/m/Y H:i:s') . 
                            ". Novo nosso número: $nosso_numero",
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $result = $db->update('boletos', $dados_update, 'id = ?', [$id]);

        if ($result === false) {
            throw new Exception("Erro ao atualizar o boleto no banco de dados");
        }

        // Registra o histórico
        $db->insert('boletos_historico', [
            'boleto_id' => $id,
            'acao' => 'reenvio_api',
            'data' => date('Y-m-d H:i:s'),
            'usuario_id' => isset($_SESSION['usuario']['id']) ? $_SESSION['usuario']['id'] : null,
            'detalhes' => "Boleto reenviado para a API. Novo nosso número: $nosso_numero"
        ]);

        setMensagem('sucesso', 'Boleto reenviado com sucesso para a API. Novo nosso número: ' . $nosso_numero);
        redirect('gerar_boleto.php?action=visualizar&id=' . $id);
        exit;
    } catch (Exception $e) {
        setMensagem('erro', 'Erro ao reenviar boleto: ' . $e->getMessage());
        redirect('reenviar_boleto.php?id=' . $id);
        exit;
    }
}

// Define o título da página
$titulo_pagina = 'Reenviar Boleto';
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

        .card {
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
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
                        <h1 class="text-2xl font-bold mb-6 text-purple-800">Reenviar Boleto para API</h1>

                        <div class="mb-6">
                            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
                                <p class="text-blue-700 font-medium">Atenção:</p>
                                <p class="text-blue-700 mt-2">Esta ação irá reenviar o boleto para a API do banco, gerando um novo nosso número. O boleto atual será substituído pelo novo.</p>
                                <p class="text-blue-700 mt-2">Use esta opção quando:</p>
                                <ul class="list-disc list-inside mt-2 text-blue-700">
                                    <li>O boleto não foi registrado corretamente no banco</li>
                                    <li>O nosso número está incorreto no sistema</li>
                                    <li>O boleto não é encontrado na API do banco</li>
                                </ul>
                            </div>

                            <div class="bg-gray-100 p-4 rounded-lg mb-6">
                                <p><strong>Número do Boleto:</strong> <?php echo $boleto['id']; ?></p>
                                <p><strong>Nosso Número Atual:</strong> <?php echo $boleto['nosso_numero']; ?></p>
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
                                <a href="reenviar_boleto.php?action=confirmar&id=<?php echo $id; ?>" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded" onclick="return confirm('Tem certeza que deseja reenviar este boleto para a API? Esta ação irá gerar um novo nosso número.');">
                                    <i class="fas fa-sync-alt mr-2"></i> Reenviar para API
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
