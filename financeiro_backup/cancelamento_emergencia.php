<?php
/**
 * Página para cancelamento de emergência de boletos
 * Esta página cancela boletos diretamente no banco de dados, sem tentar comunicação com a API do banco
 */

// Inclui os arquivos necessários
require_once __DIR__ . '/../includes/init.php';

// Verifica se o usuário está logado
exigirLogin();

// Verifica se o usuário tem permissão para acessar esta página
if (!Auth::hasPermission('financeiro', 'editar')) {
    setMensagem('erro', 'Você não tem permissão para cancelar boletos.');
    redirect('gerar_boleto.php?action=listar');
    exit;
}

// Inicializa a conexão com o banco de dados
$db = Database::getInstance();

// Verifica se foi passado um ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Verifica se foi passado uma ação
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Processa o cancelamento de emergência
if ($action === 'confirmar' && $id > 0) {
    try {
        // Busca os dados do boleto
        $boleto = $db->fetchOne("SELECT * FROM boletos WHERE id = ?", [$id]);
        
        if (!$boleto) {
            throw new Exception("Boleto não encontrado.");
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
        
        // Atualiza o status do boleto para cancelado
        $result = $db->update('boletos', [
            'status' => 'cancelado',
            'data_cancelamento' => date('Y-m-d H:i:s'),
            'observacoes' => ($boleto['observacoes'] ? $boleto['observacoes'] . "\n" : "") . 
                            "!!! CANCELAMENTO DE EMERGÊNCIA !!! Realizado em " . date('d/m/Y H:i:s') . 
                            " por " . $_SESSION['usuario']['nome']
        ], 'id = ?', [$id]);
        
        if ($result === false) {
            throw new Exception("Erro ao atualizar o status do boleto.");
        }
        
        // Registra o cancelamento na tabela de histórico
        $db->insert('boletos_historico', [
            'boleto_id' => $id,
            'acao' => 'cancelamento_emergencia',
            'data' => date('Y-m-d H:i:s'),
            'usuario_id' => isset($_SESSION['usuario']['id']) ? $_SESSION['usuario']['id'] : null,
            'detalhes' => '!!! CANCELAMENTO DE EMERGÊNCIA !!! Realizado sem comunicação com a API do banco.'
        ]);
        
        // Registra log detalhado
        error_log("CANCELAMENTO DE EMERGÊNCIA - Boleto ID: $id, Usuário: {$_SESSION['usuario']['nome']}, IP: {$_SERVER['REMOTE_ADDR']}");
        
        setMensagem('sucesso', 'Boleto cancelado com sucesso (CANCELAMENTO DE EMERGÊNCIA). ATENÇÃO: O boleto pode continuar ativo no banco!');
        redirect('gerar_boleto.php?action=visualizar&id=' . $id);
        exit;
    } catch (Exception $e) {
        setMensagem('erro', 'Erro ao cancelar boleto: ' . $e->getMessage());
        redirect('cancelamento_emergencia.php?id=' . $id);
        exit;
    }
}

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

// Define o título da página
$titulo_pagina = 'Cancelamento de Emergência';
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

        .emergency-banner {
            background-color: #DC2626;
            color: white;
            text-align: center;
            padding: 1rem;
            font-weight: bold;
            font-size: 1.25rem;
            margin-bottom: 1.5rem;
            border-radius: 0.5rem;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                background-color: #DC2626;
            }
            50% {
                background-color: #991B1B;
            }
            100% {
                background-color: #DC2626;
            }
        }

        .emergency-box {
            border: 3px dashed #DC2626;
            padding: 1.5rem;
            border-radius: 0.5rem;
            background-color: #FEF2F2;
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

                    <div class="emergency-banner">
                        <i class="fas fa-exclamation-triangle mr-2"></i> MODO DE CANCELAMENTO DE EMERGÊNCIA <i class="fas fa-exclamation-triangle ml-2"></i>
                    </div>

                    <div class="bg-white shadow-md rounded-lg p-6">
                        <h1 class="text-2xl font-bold mb-6 text-red-800">Cancelamento de Emergência de Boleto</h1>

                        <div class="mb-6 emergency-box">
                            <p class="text-red-600 font-bold mb-4 text-xl">ATENÇÃO: PROCEDIMENTO DE EMERGÊNCIA!</p>
                            <p class="mb-4">Este procedimento cancela o boleto <strong>APENAS NO SISTEMA</strong>, sem fazer qualquer tentativa de comunicação com a API do banco.</p>
                            <p class="mb-4">Use este procedimento <strong>SOMENTE</strong> quando todas as outras opções de cancelamento falharem.</p>
                            <p class="mb-4 font-bold">Consequências deste procedimento:</p>
                            <ul class="list-disc list-inside mb-6">
                                <li>O boleto será marcado como cancelado no sistema</li>
                                <li>O boleto <strong>NÃO</strong> será cancelado no banco Itaú</li>
                                <li>O pagador <strong>AINDA PODERÁ</strong> efetuar o pagamento deste boleto</li>
                                <li>Esta ação será registrada como um procedimento de emergência</li>
                                <li>O usuário que realizou esta ação será registrado nos logs</li>
                            </ul>

                            <p class="mb-4">Você está prestes a cancelar o boleto abaixo:</p>

                            <div class="bg-gray-100 p-4 rounded-lg mb-6">
                                <p><strong>Número do Boleto:</strong> <?php echo $boleto['id']; ?></p>
                                <p><strong>Pagador:</strong> <?php echo $boleto['nome_pagador']; ?></p>
                                <p><strong>Valor:</strong> R$ <?php echo number_format($boleto['valor'], 2, ',', '.'); ?></p>
                                <p><strong>Vencimento:</strong> <?php echo date('d/m/Y', strtotime($boleto['data_vencimento'])); ?></p>
                                <p><strong>Descrição:</strong> <?php echo $boleto['descricao']; ?></p>
                                <p><strong>Nosso Número:</strong> <?php echo $boleto['nosso_numero']; ?></p>
                            </div>

                            <div class="flex flex-wrap gap-4">
                                <a href="cancelamento_emergencia.php?action=confirmar&id=<?php echo $id; ?>" 
                                   class="bg-red-700 hover:bg-red-800 text-white font-bold py-2 px-4 rounded"
                                   onclick="return confirm('ATENÇÃO: Você está prestes a realizar um CANCELAMENTO DE EMERGÊNCIA. Este procedimento cancela o boleto APENAS NO SISTEMA, sem comunicação com o banco. O boleto continuará ativo no banco. Tem absoluta certeza que deseja continuar?');">
                                    <i class="fas fa-exclamation-triangle mr-2"></i> CONFIRMAR CANCELAMENTO DE EMERGÊNCIA
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
