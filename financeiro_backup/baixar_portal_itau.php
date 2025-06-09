<?php
/**
 * Página para instruções de baixa de boletos diretamente no portal do Itaú
 */

// Inclui os arquivos necessários
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/includes/corrigir_nosso_numero.php';

// Verifica se o usuário está logado
exigirLogin();

// Verifica se o usuário tem permissão para acessar esta página
if (!Auth::hasPermission('financeiro', 'visualizar')) {
    setMensagem('erro', 'Você não tem permissão para acessar esta página.');
    redirect('index.php');
    exit;
}

// Inicializa a conexão com o banco de dados
$db = Database::getInstance();

// Verifica se foi passado um ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

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

// Verifica se foi passada uma ação
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Processa a marcação como cancelado após baixa manual
if ($action === 'marcar_cancelado') {
    try {
        // Atualiza o status do boleto para cancelado
        $result = $db->update('boletos', [
            'status' => 'cancelado', 
            'data_cancelamento' => date('Y-m-d H:i:s'),
            'observacoes' => ($boleto['observacoes'] ? $boleto['observacoes'] . "\n" : "") . 
                            "Marcado como cancelado após baixa manual no portal do Itaú em " . date('d/m/Y H:i:s')
        ], 'id = ?', [$id]);
        
        if ($result === false) {
            throw new Exception("Erro ao atualizar o status do boleto");
        }
        
        // Registra o cancelamento na tabela de histórico
        $db->insert('boletos_historico', [
            'boleto_id' => $id,
            'acao' => 'cancelamento_manual',
            'data' => date('Y-m-d H:i:s'),
            'usuario_id' => isset($_SESSION['usuario']['id']) ? $_SESSION['usuario']['id'] : null,
            'detalhes' => "Marcado como cancelado após baixa manual no portal do Itaú"
        ]);
        
        setMensagem('sucesso', 'Boleto marcado como cancelado com sucesso.');
        redirect('gerar_boleto.php?action=visualizar&id=' . $id);
        exit;
    } catch (Exception $e) {
        setMensagem('erro', 'Erro ao marcar boleto como cancelado: ' . $e->getMessage());
        redirect('baixar_portal_itau.php?id=' . $id);
        exit;
    }
}

// Define o título da página
$titulo_pagina = 'Baixar Boleto no Portal do Itaú';
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

        .instruction-step {
            margin-bottom: 1.5rem;
            padding: 1rem;
            background-color: #F9FAFB;
            border-radius: 0.5rem;
            border-left: 4px solid #6B7280;
        }

        .instruction-step h3 {
            font-weight: bold;
            margin-bottom: 0.5rem;
            color: #4B5563;
        }

        .instruction-step p {
            margin-bottom: 0.5rem;
        }

        .instruction-step img {
            max-width: 100%;
            border: 1px solid #E5E7EB;
            border-radius: 0.25rem;
            margin-top: 0.5rem;
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
                        <h1 class="text-2xl font-bold mb-6 text-purple-800">Instruções para Baixar Boleto no Portal do Itaú</h1>

                        <div class="mb-6">
                            <div class="mb-6 p-4 bg-yellow-50 border-l-4 border-yellow-400 text-yellow-700">
                                <p class="font-bold">Atenção:</p>
                                <p>Como não foi possível baixar o boleto via API, você precisará baixá-lo manualmente no portal do Itaú.</p>
                                <p class="mt-2">Após realizar a baixa manual, retorne a esta página e clique em "Marcar como Cancelado" para atualizar o status no sistema.</p>
                            </div>

                            <div class="nosso-numero-box">
                                <div class="nosso-numero-title">Nosso Número (Banco de Dados):</div>
                                <div class="nosso-numero-value"><?php echo $boleto['nosso_numero']; ?></div>
                                
                                <div class="nosso-numero-title mt-4">Nosso Número (Formato Itaú):</div>
                                <div class="nosso-numero-value"><?php echo $nosso_numero_formatado; ?></div>
                                
                                <div class="nosso-numero-title mt-4">Linha Digitável:</div>
                                <div class="nosso-numero-value"><?php echo $boleto['linha_digitavel'] ?? 'Não disponível'; ?></div>
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

                            <h2 class="text-xl font-bold mb-4 text-gray-800">Passos para Baixar o Boleto no Portal do Itaú</h2>

                            <div class="instruction-step">
                                <h3>Passo 1: Acesse o Portal do Itaú</h3>
                                <p>Acesse o portal do Itaú Empresas com as credenciais da instituição.</p>
                                <p><a href="https://www.itau.com.br/empresas" target="_blank" class="text-blue-600 hover:text-blue-800">https://www.itau.com.br/empresas</a></p>
                            </div>

                            <div class="instruction-step">
                                <h3>Passo 2: Navegue até a Área de Cobrança</h3>
                                <p>No menu principal, acesse a área de "Cobrança" ou "Boletos".</p>
                            </div>

                            <div class="instruction-step">
                                <h3>Passo 3: Busque o Boleto</h3>
                                <p>Utilize o campo de busca para localizar o boleto usando o Nosso Número no formato Itaú:</p>
                                <p class="font-bold"><?php echo $nosso_numero_formatado; ?></p>
                                <p>Alternativamente, você pode buscar pela linha digitável:</p>
                                <p class="font-bold"><?php echo $boleto['linha_digitavel'] ?? 'Não disponível'; ?></p>
                            </div>

                            <div class="instruction-step">
                                <h3>Passo 4: Selecione a Opção de Baixa</h3>
                                <p>Após localizar o boleto, selecione a opção "Baixar", "Cancelar" ou "Dar Baixa" (a nomenclatura pode variar).</p>
                                <p>Geralmente, você precisará selecionar um motivo para a baixa, como "Solicitação do Cliente" ou "Acordo".</p>
                            </div>

                            <div class="instruction-step">
                                <h3>Passo 5: Confirme a Baixa</h3>
                                <p>Confirme a operação e aguarde a mensagem de sucesso.</p>
                                <p>Anote o número de protocolo ou tire um print da tela de confirmação para referência futura.</p>
                            </div>

                            <div class="instruction-step">
                                <h3>Passo 6: Atualize o Status no Sistema</h3>
                                <p>Após realizar a baixa no portal do Itaú, retorne a esta página e clique no botão "Marcar como Cancelado" abaixo para atualizar o status do boleto no sistema.</p>
                            </div>

                            <div class="flex flex-wrap gap-4 mt-6">
                                <a href="baixar_portal_itau.php?action=marcar_cancelado&id=<?php echo $id; ?>" 
                                   class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded"
                                   onclick="return confirm('Você já realizou a baixa do boleto no portal do Itaú? Esta ação apenas atualiza o status no sistema.');">
                                    <i class="fas fa-check-circle mr-2"></i> Marcar como Cancelado
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
