<?php
/**
 * Página para verificar o status de um boleto no Itaú
 */

// Inclui os arquivos necessários
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/includes/verificar_status_boleto.php';

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

// Resultado da verificação
$resultado = null;

// Processa a verificação do status do boleto
if ($action === 'verificar') {
    $resultado = verificarStatusBoleto($id, $db);
    
    if ($resultado['status'] === 'sucesso') {
        setMensagem('sucesso', 'Status do boleto verificado com sucesso.');
    } else {
        setMensagem('erro', $resultado['mensagem']);
    }
}

// Define o título da página
$titulo_pagina = 'Verificar Status do Boleto';
$titulo_pagina_completo = 'Faciência ERP - ' . $titulo_pagina;

// Formata o nosso número conforme o padrão completo do Itaú
$nosso_numero_original = $boleto['nosso_numero'];
$nosso_numero_formatado = "109/" . $nosso_numero_original . "-2"; // Formato: 109/XXXXXXXX-2

// Determina o tipo de API usado para gerar o boleto
$api_tipo = isset($boleto['api_tipo']) ? $boleto['api_tipo'] : 'Desconhecido';
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

        .api-info-box {
            background-color: #F0FDF4;
            border: 1px solid #86EFAC;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .api-info-title {
            font-weight: bold;
            margin-bottom: 0.5rem;
            color: #166534;
        }

        .api-info-value {
            font-family: monospace;
            font-size: 1.25rem;
            background-color: #ECFDF5;
            padding: 0.5rem;
            border-radius: 0.25rem;
            border: 1px dashed #86EFAC;
        }

        .result-box {
            background-color: #F3F4F6;
            border: 1px solid #D1D5DB;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .result-title {
            font-weight: bold;
            margin-bottom: 0.5rem;
            color: #1F2937;
        }

        .result-value {
            font-family: monospace;
            font-size: 1rem;
            background-color: #FFFFFF;
            padding: 0.5rem;
            border-radius: 0.25rem;
            border: 1px dashed #D1D5DB;
            white-space: pre-wrap;
            max-height: 300px;
            overflow-y: auto;
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
                        <h1 class="text-2xl font-bold mb-6 text-purple-800">Verificar Status do Boleto</h1>

                        <div class="mb-6">
                            <div class="mb-6 p-4 bg-blue-50 border-l-4 border-blue-400 text-blue-700">
                                <p class="font-bold">Informação:</p>
                                <p>Esta página verifica o status atual do boleto no Itaú.</p>
                                <p class="mt-2">O sistema tentará consultar o status usando o nosso número original e o formato completo.</p>
                            </div>

                            <div class="api-info-box">
                                <div class="api-info-title">API Usada na Geração:</div>
                                <div class="api-info-value"><?php echo ucfirst($api_tipo); ?></div>
                                
                                <div class="api-info-title mt-4">Método de Consulta:</div>
                                <div class="api-info-value">API de Consulta (cobranca/v2/boletos/{nossoNumero})</div>
                            </div>

                            <div class="nosso-numero-box">
                                <div class="nosso-numero-title">Nosso Número (Original):</div>
                                <div class="nosso-numero-value"><?php echo $nosso_numero_original; ?></div>
                                
                                <div class="nosso-numero-title mt-4">Nosso Número (Formato Completo):</div>
                                <div class="nosso-numero-value"><?php echo $nosso_numero_formatado; ?></div>
                            </div>

                            <div class="bg-gray-100 p-4 rounded-lg mb-6">
                                <p><strong>Número do Boleto:</strong> <?php echo $boleto['id']; ?></p>
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

                            <?php if ($resultado): ?>
                            <div class="mb-6">
                                <h2 class="text-xl font-bold mb-4">Resultado da Verificação</h2>
                                
                                <?php if ($resultado['status'] === 'sucesso'): ?>
                                <div class="p-4 bg-green-50 border-l-4 border-green-400 text-green-700 mb-4">
                                    <p class="font-bold">Consulta realizada com sucesso!</p>
                                </div>
                                
                                <div class="bg-gray-100 p-4 rounded-lg mb-4">
                                    <p><strong>Status no Itaú:</strong> 
                                        <span class="status-badge <?php 
                                            echo $resultado['status_boleto'] === 'pendente' ? 'status-pendente' : 
                                                ($resultado['status_boleto'] === 'pago' ? 'status-pago' : 
                                                    ($resultado['status_boleto'] === 'cancelado' ? 'status-cancelado' : 
                                                        ($resultado['status_boleto'] === 'vencido' ? 'status-vencido' : 'status-desconhecido'))); 
                                        ?>">
                                            <?php echo ucfirst($resultado['status_boleto']); ?>
                                        </span>
                                    </p>
                                    <p><strong>Situação no Itaú:</strong> <?php echo $resultado['situacao_boleto']; ?></p>
                                </div>
                                
                                <div class="result-box">
                                    <div class="result-title">Detalhes da Resposta:</div>
                                    <div class="result-value"><?php echo json_encode($resultado['detalhes'], JSON_PRETTY_PRINT); ?></div>
                                </div>
                                <?php else: ?>
                                <div class="p-4 bg-red-50 border-l-4 border-red-400 text-red-700 mb-4">
                                    <p class="font-bold">Erro na consulta:</p>
                                    <p><?php echo $resultado['mensagem']; ?></p>
                                    <?php if (isset($resultado['codigo_http'])): ?>
                                    <p class="mt-2"><strong>Código HTTP:</strong> <?php echo $resultado['codigo_http']; ?></p>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>

                            <div class="flex flex-wrap gap-4">
                                <a href="verificar_status_boleto.php?action=verificar&id=<?php echo $id; ?>" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                    <i class="fas fa-search mr-2"></i> Verificar Status
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
