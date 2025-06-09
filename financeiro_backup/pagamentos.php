<?php
/**
 * Página de listagem de pagamentos
 */

// Inclui os arquivos necessários
require_once __DIR__ . '/../includes/init.php';

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

// Parâmetros de filtro
$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : date('m');
$ano = isset($_GET['ano']) ? (int)$_GET['ano'] : date('Y');
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Constrói a consulta SQL
$sql = "SELECT p.*, f.nome as funcionario_nome
        FROM pagamentos p
        LEFT JOIN funcionarios f ON p.funcionario_id = f.id
        WHERE 1=1";
$params = [];

// Aplica os filtros
if ($mes > 0) {
    $sql .= " AND MONTH(p.data_pagamento) = ?";
    $params[] = $mes;
}

if ($ano > 0) {
    $sql .= " AND YEAR(p.data_pagamento) = ?";
    $params[] = $ano;
}

if (!empty($status)) {
    $sql .= " AND p.status = ?";
    $params[] = $status;
}

// Ordena os resultados
$sql .= " ORDER BY p.data_pagamento DESC, f.nome";

// Executa a consulta
$pagamentos = $db->fetchAll($sql, $params);

// Calcula o total
$total = 0;
foreach ($pagamentos as $pagamento) {
    if ($pagamento['status'] != 'cancelado') {
        $total += $pagamento['valor'];
    }
}

// Define o título da página
$titulo_pagina = 'Pagamentos';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faciência ERP - <?php echo $titulo_pagina; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../css/styles.css">
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
                    <h1 class="text-3xl font-bold text-gray-800 mb-6"><?php echo $titulo_pagina; ?></h1>

                    <!-- Mensagens -->
                    <?php include __DIR__ . '/../includes/mensagens.php'; ?>

                    <!-- Filtros -->
                    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                        <form method="get" class="flex flex-wrap items-end gap-4">
                            <!-- Mês -->
                            <div>
                                <label for="mes" class="block text-sm font-medium text-gray-700 mb-1">Mês</label>
                                <select name="mes" id="mes" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500">
                                    <option value="0">Todos</option>
                                    <?php for ($i = 1; $i <= 12; $i++): ?>
                                        <option value="<?php echo $i; ?>" <?php echo $mes == $i ? 'selected' : ''; ?>>
                                            <?php echo date('F', mktime(0, 0, 0, $i, 1, 2000)); ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>

                            <!-- Ano -->
                            <div>
                                <label for="ano" class="block text-sm font-medium text-gray-700 mb-1">Ano</label>
                                <select name="ano" id="ano" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500">
                                    <option value="0">Todos</option>
                                    <?php for ($i = date('Y'); $i >= date('Y') - 5; $i--): ?>
                                        <option value="<?php echo $i; ?>" <?php echo $ano == $i ? 'selected' : ''; ?>>
                                            <?php echo $i; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>

                            <!-- Status -->
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select name="status" id="status" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500">
                                    <option value="">Todos</option>
                                    <option value="pendente" <?php echo $status === 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                                    <option value="pago" <?php echo $status === 'pago' ? 'selected' : ''; ?>>Pago</option>
                                    <option value="cancelado" <?php echo $status === 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                                </select>
                            </div>

                            <!-- Botão Filtrar -->
                            <div>
                                <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded">
                                    <i class="fas fa-filter mr-2"></i> Filtrar
                                </button>
                            </div>

                            <!-- Botão Limpar -->
                            <div>
                                <a href="pagamentos.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
                                    <i class="fas fa-eraser mr-2"></i> Limpar
                                </a>
                            </div>

                            <!-- Botão Novo Pagamento -->
                            <div class="ml-auto">
                                <a href="pagamento_form.php" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                    <i class="fas fa-plus mr-2"></i> Novo Pagamento
                                </a>
                            </div>
                        </form>
                    </div>

                    <!-- Lista de Pagamentos -->
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <div class="p-6 border-b">
                            <div class="flex justify-between items-center">
                                <h2 class="text-xl font-bold text-gray-800">Pagamentos</h2>
                                <div class="text-lg font-bold text-gray-800">
                                    Total: R$ <?php echo number_format($total, 2, ',', '.'); ?>
                                </div>
                            </div>
                        </div>

                        <?php if (empty($pagamentos)): ?>
                            <div class="p-6 text-center text-gray-500">
                                <p>Nenhum pagamento encontrado.</p>
                            </div>
                        <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full bg-white">
                                    <thead>
                                        <tr class="bg-gray-100 text-gray-600 uppercase text-sm leading-normal">
                                            <th class="py-3 px-6 text-left">Funcionário</th>
                                            <th class="py-3 px-6 text-left">Data</th>
                                            <th class="py-3 px-6 text-right">Valor</th>
                                            <th class="py-3 px-6 text-center">Status</th>
                                            <th class="py-3 px-6 text-center">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody class="text-gray-600 text-sm">
                                        <?php foreach ($pagamentos as $pagamento): ?>
                                            <tr class="border-b border-gray-200 hover:bg-gray-50">
                                                <td class="py-3 px-6"><?php echo $pagamento['funcionario_nome']; ?></td>
                                                <td class="py-3 px-6"><?php echo date('d/m/Y', strtotime($pagamento['data_pagamento'])); ?></td>
                                                <td class="py-3 px-6 text-right">R$ <?php echo number_format($pagamento['valor'], 2, ',', '.'); ?></td>
                                                <td class="py-3 px-6 text-center">
                                                    <?php if ($pagamento['status'] == 'pago'): ?>
                                                        <span class="bg-green-100 text-green-800 py-1 px-3 rounded-full text-xs">Pago</span>
                                                    <?php elseif ($pagamento['status'] == 'pendente'): ?>
                                                        <span class="bg-yellow-100 text-yellow-800 py-1 px-3 rounded-full text-xs">Pendente</span>
                                                    <?php else: ?>
                                                        <span class="bg-red-100 text-red-800 py-1 px-3 rounded-full text-xs">Cancelado</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="py-3 px-6 text-center">
                                                    <div class="flex item-center justify-center">
                                                        <a href="pagamento_form.php?id=<?php echo $pagamento['id']; ?>" class="text-blue-600 hover:text-blue-900 mx-1" title="Editar">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <?php if ($pagamento['status'] == 'pendente'): ?>
                                                            <a href="pagamento_status.php?id=<?php echo $pagamento['id']; ?>&status=pago" class="text-green-600 hover:text-green-900 mx-1" title="Marcar como Pago">
                                                                <i class="fas fa-check-circle"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                        <?php if ($pagamento['status'] != 'cancelado'): ?>
                                                            <a href="pagamento_status.php?id=<?php echo $pagamento['id']; ?>&status=cancelado" class="text-red-600 hover:text-red-900 mx-1" title="Cancelar" onclick="return confirm('Tem certeza que deseja cancelar este pagamento?');">
                                                                <i class="fas fa-times-circle"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
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
            sidebar.classList.toggle('hidden');
        });
    </script>
</body>
</html>
