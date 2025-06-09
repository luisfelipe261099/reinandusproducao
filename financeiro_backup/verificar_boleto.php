<?php
/**
 * Página para verificar o status de um boleto na API do Itaú
 */

// Inclui os arquivos necessários
require_once __DIR__ . '/../includes/init.php';
// Inclui o arquivo de formatação do nosso número que contém a função consultarBoletoMultiFormato
require_once __DIR__ . '/includes/formatar_nosso_numero.php';

// Verifica se o usuário está logado
exigirLogin();

// Verifica se o usuário tem permissão para acessar esta página
if (!Auth::hasPermission('financeiro', 'visualizar')) {
    setMensagem('erro', 'Você não tem permissão para verificar boletos.');
    redirect('gerar_boleto.php?action=listar');
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

// Verifica se o nosso_numero está definido
if (empty($boleto['nosso_numero'])) {
    setMensagem('erro', 'Este boleto não possui um número de registro (nosso número) definido.');
    redirect('gerar_boleto.php?action=visualizar&id=' . $id);
    exit;
}

// Formata o nosso número conforme o padrão completo do Itaú
$nosso_numero_original = $boleto['nosso_numero'];
$nosso_numero_formatado = formatarNossoNumeroItau($nosso_numero_original); // Formato: 109/XXXXXXXX-2

// Consulta o status do boleto na API usando múltiplos formatos
$resultado = consultarBoletoMultiFormato($boleto['nosso_numero'], $db);

// Define o título da página
$titulo_pagina = 'Verificar Status do Boleto';
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
                        <h1 class="text-2xl font-bold mb-6 text-purple-800">Status do Boleto na API</h1>

                        <div class="mb-6">
                            <div class="bg-gray-100 p-4 rounded-lg mb-6">
                                <p><strong>Número do Boleto:</strong> <?php echo $boleto['id']; ?></p>
                                <p><strong>Nosso Número (Original):</strong> <?php echo $boleto['nosso_numero']; ?></p>
                                <p><strong>Nosso Número (Formato Itaú):</strong> <span class="font-mono bg-blue-100 px-2 py-1 rounded"><?php echo $nosso_numero_formatado; ?></span></p>
                                <p><strong>Pagador:</strong> <?php echo $boleto['nome_pagador']; ?></p>
                                <p><strong>Valor:</strong> R$ <?php echo number_format($boleto['valor'], 2, ',', '.'); ?></p>
                                <p><strong>Vencimento:</strong> <?php echo date('d/m/Y', strtotime($boleto['data_vencimento'])); ?></p>
                                <p><strong>Status no Sistema:</strong>
                                    <span class="status-badge <?php
                                        echo $boleto['status'] === 'pendente' ? 'status-pendente' :
                                            ($boleto['status'] === 'pago' ? 'status-pago' :
                                                ($boleto['status'] === 'cancelado' ? 'status-cancelado' : 'status-vencido'));
                                    ?>">
                                        <?php echo ucfirst($boleto['status']); ?>
                                    </span>
                                </p>
                            </div>

                            <div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
                                <h2 class="text-xl font-bold mb-4">Resultado da Consulta na API</h2>

                                <?php if ($resultado['status'] === 'sucesso'): ?>
                                    <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-4">
                                        <p class="text-green-700 font-medium">Boleto encontrado na API com sucesso!</p>
                                        <?php if (isset($resultado['nosso_numero_encontrado']) && $resultado['nosso_numero_encontrado'] !== $boleto['nosso_numero']): ?>
                                        <p class="text-green-700 mt-2">
                                            <strong>Observação:</strong> O boleto foi encontrado com um formato diferente de nosso número:
                                            <span class="font-mono bg-green-100 px-2 py-1 rounded"><?php echo $resultado['nosso_numero_encontrado']; ?></span>
                                        </p>
                                        <?php endif; ?>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                        <div>
                                            <p><strong>Situação:</strong>
                                                <span class="status-badge <?php
                                                    echo $resultado['dados']['situacao'] === 'EMITIDO' ? 'status-pendente' :
                                                        ($resultado['dados']['situacao'] === 'PAGO' ? 'status-pago' :
                                                            ($resultado['dados']['situacao'] === 'BAIXADO' || $resultado['dados']['situacao'] === 'CANCELADO' ? 'status-cancelado' : 'status-vencido'));
                                                ?>">
                                                    <?php echo $resultado['dados']['situacao']; ?>
                                                </span>
                                            </p>
                                            <p><strong>Valor:</strong> R$ <?php echo number_format($resultado['dados']['valor'] / 100, 2, ',', '.'); ?></p>
                                            <p><strong>Vencimento:</strong> <?php echo date('d/m/Y', strtotime($resultado['dados']['dataVencimento'])); ?></p>
                                        </div>

                                        <?php if (isset($resultado['dados']['pagamento'])): ?>
                                        <div>
                                            <h3 class="font-bold mb-2">Informações de Pagamento</h3>
                                            <p><strong>Data:</strong> <?php echo date('d/m/Y', strtotime($resultado['dados']['pagamento']['data'])); ?></p>
                                            <p><strong>Valor Pago:</strong> R$ <?php echo number_format($resultado['dados']['pagamento']['valorPago'] / 100, 2, ',', '.'); ?></p>
                                        </div>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ($boleto['status'] !== 'cancelado' && ($resultado['dados']['situacao'] === 'BAIXADO' || $resultado['dados']['situacao'] === 'CANCELADO')): ?>
                                    <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-4">
                                        <p class="text-yellow-700">
                                            <strong>Atenção:</strong> O boleto consta como <?php echo $resultado['dados']['situacao']; ?> na API, mas está como <?php echo $boleto['status']; ?> no sistema.
                                        </p>
                                        <p class="mt-2">
                                            <a href="cancelar_boleto.php?id=<?php echo $id; ?>" class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-1 px-3 rounded text-sm">
                                                Atualizar Status no Sistema
                                            </a>
                                        </p>
                                    </div>
                                    <?php endif; ?>

                                    <?php if ($boleto['status'] !== 'pago' && $resultado['dados']['situacao'] === 'PAGO'): ?>
                                    <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-4">
                                        <p class="text-green-700">
                                            <strong>Atenção:</strong> O boleto consta como PAGO na API, mas está como <?php echo $boleto['status']; ?> no sistema.
                                        </p>
                                        <p class="mt-2">
                                            <a href="registrar_pagamento.php?id=<?php echo $id; ?>" class="bg-green-500 hover:bg-green-600 text-white font-medium py-1 px-3 rounded text-sm">
                                                Registrar Pagamento no Sistema
                                            </a>
                                        </p>
                                    </div>
                                    <?php endif; ?>

                                <?php elseif ($resultado['status'] === 'nao_encontrado'): ?>
                                    <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-4">
                                        <p class="text-yellow-700 font-medium">Boleto não encontrado na API do banco.</p>
                                        <p class="text-yellow-700 mt-2">O sistema tentou consultar o boleto usando o formato exato do Itaú (<span class="font-mono"><?php echo $nosso_numero_formatado; ?></span>), mas não foi encontrado.</p>
                                        <p class="text-yellow-700 mt-2">Isso pode ocorrer pelos seguintes motivos:</p>
                                        <ul class="list-disc list-inside mt-2 text-yellow-700">
                                            <li>O boleto não foi registrado corretamente no banco</li>
                                            <li>O nosso número está incorreto no sistema</li>
                                            <li>O boleto foi cancelado anteriormente no banco</li>
                                            <li>O ambiente da API (teste/produção) está incorreto</li>
                                        </ul>
                                    </div>

                                    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-4">
                                        <p class="text-blue-700 font-medium">Formato exato do Itaú usado na consulta:</p>
                                        <div class="mt-2 p-3 bg-white border border-blue-200 rounded">
                                            <p class="font-mono text-lg text-blue-800"><?php echo $nosso_numero_formatado; ?></p>
                                        </div>
                                        <p class="text-blue-700 mt-3">
                                            <i class="fas fa-info-circle mr-1"></i> Este é o formato exato que aparece no sistema do Itaú e deve ser usado para consultas e operações.
                                        </p>
                                    </div>

                                    <?php if ($boleto['status'] !== 'cancelado'): ?>
                                    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-4">
                                        <p class="text-blue-700">
                                            <strong>Recomendações:</strong> Como o boleto não foi encontrado na API, você tem as seguintes opções:
                                        </p>
                                        <div class="flex flex-wrap gap-2 mt-2">
                                            <a href="reenviar_boleto.php?id=<?php echo $id; ?>" class="bg-green-500 hover:bg-green-600 text-white font-medium py-1 px-3 rounded text-sm">
                                                <i class="fas fa-sync-alt mr-1"></i> Reenviar para API
                                            </a>
                                            <a href="cancelar_boleto.php?id=<?php echo $id; ?>" class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-1 px-3 rounded text-sm">
                                                <i class="fas fa-times-circle mr-1"></i> Cancelar no Sistema
                                            </a>
                                            <a href="cancelar_boleto.php?action=forcar_local&id=<?php echo $id; ?>"
                                               class="bg-red-700 hover:bg-red-800 text-white font-medium py-1 px-3 rounded text-sm"
                                               onclick="return confirm('ATENÇÃO: Esta ação irá forçar o cancelamento APENAS no sistema, ignorando completamente a API. O boleto continuará ativo no banco. Tem certeza que deseja continuar?');">
                                                <i class="fas fa-exclamation-triangle mr-1"></i> FORÇAR Cancelamento
                                            </a>
                                            <a href="cancelamento_emergencia.php?id=<?php echo $id; ?>"
                                               class="bg-black hover:bg-gray-900 text-white font-medium py-1 px-3 rounded text-sm">
                                                <i class="fas fa-radiation mr-1"></i> CANCELAMENTO DE EMERGÊNCIA
                                            </a>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-4">
                                        <p class="text-red-700 font-medium">Erro ao consultar o boleto na API:</p>
                                        <p class="text-red-700 mt-2"><?php echo $resultado['mensagem']; ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="flex justify-between">
                                <a href="gerar_boleto.php?action=visualizar&id=<?php echo $id; ?>" class="bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded">
                                    <i class="fas fa-arrow-left mr-2"></i> Voltar
                                </a>

                                <?php if ($resultado['status'] === 'sucesso' && $resultado['dados']['situacao'] === 'EMITIDO' && $boleto['status'] === 'pendente'): ?>
                                <a href="cancelar_boleto.php?id=<?php echo $id; ?>" class="bg-red-500 hover:bg-red-600 text-white font-medium py-2 px-4 rounded">
                                    <i class="fas fa-times-circle mr-2"></i> Cancelar Boleto
                                </a>
                                <?php endif; ?>
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
