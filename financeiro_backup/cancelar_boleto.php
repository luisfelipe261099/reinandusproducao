<?php
/**
 * Página para cancelar boletos
 */

// Inclui os arquivos necessários
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/includes/processar_boleto.php';
// O arquivo baixar_boleto_api.php já inclui consultar_boleto_api.php, que inclui corrigir_nosso_numero.php
require_once __DIR__ . '/includes/baixar_boleto_api.php';

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

// Processa o cancelamento do boleto
if (($action === 'confirmar' || $action === 'confirmar_local' || $action === 'forcar_local') && $id > 0) {
    // Verifica se é para cancelar apenas localmente
    $apenas_local = ($action === 'confirmar_local' || $action === 'forcar_local');

    // Verifica se é para forçar o cancelamento local (ignorando a API completamente)
    $forcar_local = ($action === 'forcar_local');

    if ($forcar_local) {
        // Busca os dados do boleto
        $boleto = $db->fetchOne("SELECT * FROM boletos WHERE id = ?", [$id]);

        if ($boleto) {
            // Atualiza o status do boleto para cancelado
            $result = $db->update('boletos', [
                'status' => 'cancelado',
                'data_cancelamento' => date('Y-m-d H:i:s'),
                'observacoes' => ($boleto['observacoes'] ? $boleto['observacoes'] . "\n" : "") .
                                "CANCELAMENTO FORÇADO apenas no sistema em " . date('d/m/Y H:i:s')
            ], 'id = ?', [$id]);

            // Registra o cancelamento na tabela de histórico
            $db->insert('boletos_historico', [
                'boleto_id' => $id,
                'acao' => 'cancelamento_forcado',
                'data' => date('Y-m-d H:i:s'),
                'usuario_id' => isset($_SESSION['usuario']['id']) ? $_SESSION['usuario']['id'] : null,
                'detalhes' => 'Cancelamento forçado apenas no sistema (ignorando API)'
            ]);

            $resultado = [
                'status' => 'sucesso',
                'mensagem' => 'Boleto CANCELADO FORÇADAMENTE apenas no sistema. ATENÇÃO: O boleto continua ativo no banco!'
            ];
        } else {
            $resultado = [
                'status' => 'erro',
                'mensagem' => 'Boleto não encontrado.'
            ];
        }
    } else {
        // Chama a função para baixar o boleto via API v2
        $resultado = baixarBoletoBancario($id, $db, $apenas_local);
    }

    // Define a mensagem de acordo com o resultado
    if ($resultado['status'] === 'sucesso') {
        setMensagem('sucesso', $resultado['mensagem']);
    } elseif ($resultado['status'] === 'aviso') {
        setMensagem('aviso', $resultado['mensagem']);
    } else {
        setMensagem('erro', $resultado['mensagem']);
    }

    // Redireciona para a página de visualização do boleto
    redirect('gerar_boleto.php?action=visualizar&id=' . $id);
    exit;
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
$titulo_pagina = 'Baixar/Cancelar Boleto';
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
                        <h1 class="text-2xl font-bold mb-6 text-purple-800">Baixar/Cancelar Boleto</h1>

                        <div class="mb-6">
                            <p class="text-red-600 font-bold mb-4">Atenção: Esta ação não pode ser desfeita!</p>
                            <p class="mb-4">Você está prestes a cancelar o boleto abaixo:</p>

                            <div class="bg-gray-100 p-4 rounded-lg mb-6">
                                <p><strong>Número do Boleto:</strong> <?php echo $boleto['id']; ?></p>
                                <p><strong>Pagador:</strong> <?php echo $boleto['nome_pagador']; ?></p>
                                <p><strong>Valor:</strong> R$ <?php echo number_format($boleto['valor'], 2, ',', '.'); ?></p>
                                <p><strong>Vencimento:</strong> <?php echo date('d/m/Y', strtotime($boleto['data_vencimento'])); ?></p>
                                <p><strong>Descrição:</strong> <?php echo $boleto['descricao']; ?></p>
                            </div>

                            <p class="mb-4">Ao cancelar este boleto:</p>
                            <ul class="list-disc list-inside mb-6">
                                <li>O boleto será marcado como cancelado no sistema</li>
                                <li>O boleto será cancelado junto ao banco Itaú</li>
                                <li>O pagador não poderá mais efetuar o pagamento deste boleto</li>
                            </ul>

                            <div class="mb-6 p-4 bg-blue-50 border-l-4 border-blue-400 text-blue-700">
                                <p class="font-bold">Informação:</p>
                                <p>O boleto será baixado (cancelado) utilizando a API de Cobrança v2 do Itaú. Você tem três opções:</p>
                                <ol class="list-decimal list-inside mt-2 ml-4">
                                    <li>Baixar via API do banco (recomendado) - O boleto será baixado no banco e cancelado no sistema</li>
                                    <li>Cancelar apenas no sistema interno - O boleto continuará ativo no banco</li>
                                    <li>FORÇAR Cancelamento - Use apenas em casos excepcionais quando as outras opções falharem</li>
                                </ol>
                                <p class="mt-2 font-bold text-red-600">IMPORTANTE: Mesmo que o sistema indique sucesso no cancelamento via API, é recomendável verificar no sistema do Itaú se o boleto foi realmente cancelado.</p>
                            </div>

                            <div class="flex flex-wrap gap-4">
                                <a href="cancelar_boleto.php?action=confirmar&id=<?php echo $id; ?>" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                    Baixar via API do Banco
                                </a>
                                <a href="cancelar_boleto.php?action=confirmar_local&id=<?php echo $id; ?>" class="bg-orange-500 hover:bg-orange-600 text-white font-bold py-2 px-4 rounded">
                                    Cancelar Apenas no Sistema
                                </a>
                                <a href="cancelar_boleto.php?action=forcar_local&id=<?php echo $id; ?>"
                                   class="bg-red-700 hover:bg-red-800 text-white font-bold py-2 px-4 rounded"
                                   onclick="return confirm('ATENÇÃO: Esta ação irá forçar o cancelamento APENAS no sistema, ignorando completamente a API. O boleto continuará ativo no banco. Tem certeza que deseja continuar?');">
                                    FORÇAR Cancelamento
                                </a>
                                <a href="gerar_boleto.php?action=visualizar&id=<?php echo $id; ?>" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
                                    Voltar
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
