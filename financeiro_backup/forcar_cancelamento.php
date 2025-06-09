<?php
/**
 * Página para forçar o cancelamento de um boleto apenas no sistema local
 * Usar apenas quando não for possível cancelar o boleto na API do banco
 */

// Inclui os arquivos necessários
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/includes/forcar_cancelamento_local.php';

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

// Verifica se foi passada uma ação
$action = isset($_POST['action']) ? $_POST['action'] : '';

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

// Processa o cancelamento forçado
if ($action === 'confirmar') {
    // Verifica se foi informado um motivo
    $motivo = isset($_POST['motivo']) ? trim($_POST['motivo']) : '';
    
    if (empty($motivo)) {
        setMensagem('erro', 'É obrigatório informar o motivo do cancelamento forçado.');
        redirect('forcar_cancelamento.php?id=' . $id);
        exit;
    }
    
    // Verifica se foi marcada a confirmação
    $confirmacao = isset($_POST['confirmacao']) ? (int)$_POST['confirmacao'] : 0;
    
    if ($confirmacao !== 1) {
        setMensagem('erro', 'É necessário marcar a confirmação para prosseguir com o cancelamento forçado.');
        redirect('forcar_cancelamento.php?id=' . $id);
        exit;
    }
    
    $resultado = forcarCancelamentoLocal($id, $motivo, $db);
    
    if ($resultado['status'] === 'sucesso') {
        setMensagem('sucesso', $resultado['mensagem']);
    } else {
        setMensagem('erro', $resultado['mensagem']);
    }
    
    redirect('gerar_boleto.php?action=visualizar&id=' . $id);
    exit;
}

// Define o título da página
$titulo_pagina = 'Forçar Cancelamento de Boleto';
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

        .warning-box {
            background-color: #FECACA;
            border: 2px solid #DC2626;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .warning-title {
            font-weight: bold;
            margin-bottom: 0.5rem;
            color: #991B1B;
            font-size: 1.25rem;
        }

        .warning-text {
            color: #7F1D1D;
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
                        <h1 class="text-2xl font-bold mb-6 text-red-800">Forçar Cancelamento de Boleto</h1>

                        <div class="mb-6">
                            <div class="warning-box">
                                <div class="warning-title">
                                    <i class="fas fa-exclamation-triangle mr-2"></i> ATENÇÃO: PROCEDIMENTO DE EMERGÊNCIA
                                </div>
                                <div class="warning-text">
                                    Esta operação cancela o boleto <strong>APENAS NO SISTEMA LOCAL</strong>, sem comunicação com o banco.
                                </div>
                                <div class="warning-text">
                                    Se o boleto já foi registrado no banco, ele ainda poderá ser pago pelo cliente mesmo após este cancelamento.
                                </div>
                                <div class="warning-text">
                                    <strong>Use esta opção apenas como último recurso, quando todas as outras tentativas de cancelamento falharem.</strong>
                                </div>
                                <div class="warning-text">
                                    Após o cancelamento forçado, recomenda-se verificar no portal do banco se o boleto foi cancelado corretamente.
                                </div>
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
                                <p><strong>Nosso Número:</strong> <?php echo $boleto['nosso_numero']; ?></p>
                            </div>

                            <form action="forcar_cancelamento.php" method="post" class="mb-6">
                                <input type="hidden" name="action" value="confirmar">
                                <input type="hidden" name="id" value="<?php echo $id; ?>">
                                
                                <div class="mb-4">
                                    <label for="motivo" class="block text-gray-700 font-bold mb-2">Motivo do Cancelamento Forçado:</label>
                                    <textarea id="motivo" name="motivo" rows="4" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required></textarea>
                                    <p class="text-sm text-gray-600 mt-1">Informe detalhadamente o motivo pelo qual está sendo necessário forçar o cancelamento.</p>
                                </div>
                                
                                <div class="mb-6">
                                    <label class="flex items-center">
                                        <input type="checkbox" name="confirmacao" value="1" class="form-checkbox h-5 w-5 text-red-600" required>
                                        <span class="ml-2 text-gray-700">
                                            Confirmo que entendo os riscos desta operação e que o boleto será cancelado apenas no sistema local, podendo ainda ser pago pelo cliente se já estiver registrado no banco.
                                        </span>
                                    </label>
                                </div>
                                
                                <div class="flex flex-wrap gap-4">
                                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded" onclick="return confirm('ATENÇÃO: Esta é uma operação de emergência. Tem certeza que deseja prosseguir?');">
                                        <i class="fas fa-exclamation-triangle mr-2"></i> Forçar Cancelamento
                                    </button>
                                    <a href="gerar_boleto.php?action=visualizar&id=<?php echo $id; ?>" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
                                        <i class="fas fa-arrow-left mr-2"></i> Voltar
                                    </a>
                                </div>
                            </form>
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
