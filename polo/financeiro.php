<?php
/**
 * Módulo Financeiro do Polo
 * Permite visualizar e baixar boletos gerados para o polo
 */

// Inicializa o sistema
require_once __DIR__ . '/../includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica se o usuário é do tipo polo
if (getUsuarioTipo() !== 'polo') {
    setMensagem('erro', 'Você não tem permissão para acessar esta página.');
    redirect('../index.php');
    exit;
}

// Instancia o banco de dados
$db = Database::getInstance();

// Obtém o ID do polo associado ao usuário
$usuario_id = getUsuarioId();
$sql = "SELECT id FROM polos WHERE responsavel_id = ?";
$resultado = $db->fetchOne($sql, [$usuario_id]);
$polo_id = $resultado['id'] ?? null;

if (!$polo_id) {
    setMensagem('erro', 'Não foi possível identificar o polo associado ao seu usuário.');
    redirect('index.php');
    exit;
}

// Define a ação padrão
$action = $_GET['action'] ?? 'listar';

// Processa as ações
switch ($action) {
    case 'visualizar':
        // Verifica se o ID foi informado
        if (!isset($_GET['id']) || empty($_GET['id'])) {
            setMensagem('erro', 'ID do boleto não informado.');
            redirect('financeiro.php');
            exit;
        }

        // Busca o boleto
        $id = (int)$_GET['id'];
        $sql = "SELECT * FROM boletos WHERE id = ? AND tipo_entidade = 'polo' AND entidade_id = ?";
        $boleto = $db->fetchOne($sql, [$id, $polo_id]);

        if (!$boleto) {
            setMensagem('erro', 'Boleto não encontrado ou não pertence ao seu polo.');
            redirect('financeiro.php');
            exit;
        }

        // Define o título da página
        $titulo_pagina = 'Visualizar Boleto';
        break;

    case 'listar':
    default:
        // Parâmetros de filtro e paginação
        $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
        $por_pagina = 5; // Limitado a 5 por página conforme solicitado
        $offset = ($pagina - 1) * $por_pagina;

        // Filtros
        $filtros = [];
        $params = [$polo_id];
        $where = ["b.tipo_entidade = 'polo' AND b.entidade_id = ?"];

        // Filtro por status
        if (isset($_GET['status']) && !empty($_GET['status'])) {
            $where[] = "b.status = ?";
            $params[] = $_GET['status'];
            $filtros['status'] = $_GET['status'];
        }

        // Filtro por período
        if (isset($_GET['data_inicio']) && !empty($_GET['data_inicio'])) {
            $where[] = "b.data_vencimento >= ?";
            $params[] = $_GET['data_inicio'];
            $filtros['data_inicio'] = $_GET['data_inicio'];
        }

        if (isset($_GET['data_fim']) && !empty($_GET['data_fim'])) {
            $where[] = "b.data_vencimento <= ?";
            $params[] = $_GET['data_fim'];
            $filtros['data_fim'] = $_GET['data_fim'];
        }

        // Monta a cláusula WHERE
        $whereClause = "WHERE " . implode(" AND ", $where);

        // Consulta para contar o total de registros
        $sql = "SELECT COUNT(*) as total FROM boletos b $whereClause";
        $resultado = $db->fetchOne($sql, $params);
        $total_registros = $resultado['total'] ?? 0;

        // Calcula o total de páginas
        $total_paginas = ceil($total_registros / $por_pagina);

        // Consulta para buscar os boletos
        $sql = "SELECT b.* FROM boletos b $whereClause ORDER BY b.data_vencimento DESC, b.id DESC LIMIT $offset, $por_pagina";
        $boletos = $db->fetchAll($sql, $params);

        // Define o título da página
        $titulo_pagina = 'Financeiro - Meus Boletos';
        break;
}

// Define o título da página
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

        .btn-primary {
            background-color: #3B82F6;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 500;
            transition: background-color 0.2s;
        }
        .btn-primary:hover { background-color: #2563EB; }

        .btn-secondary {
            background-color: #6B7280;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 500;
            transition: background-color 0.2s;
        }
        .btn-secondary:hover { background-color: #4B5563; }

        .btn-success {
            background-color: #10B981;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 500;
            transition: background-color 0.2s;
        }
        .btn-success:hover { background-color: #059669; }
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
                    <h1 class="text-2xl font-bold text-gray-800 mb-6"><?php echo $titulo_pagina; ?></h1>

                    <?php
                    // Exibe mensagens de erro ou sucesso
                    if (isset($_SESSION['mensagem']) && isset($_SESSION['mensagem_tipo'])) {
                        echo '<div class="mb-4 ' . ($_SESSION['mensagem_tipo'] === 'erro' ? 'bg-red-100 border-red-400 text-red-700' : 'bg-green-100 border-green-400 text-green-700') . ' px-4 py-3 rounded relative border" role="alert">';
                        echo '<span class="block sm:inline">' . $_SESSION['mensagem'] . '</span>';
                        echo '</div>';
                        
                        // Limpa a mensagem da sessão
                        unset($_SESSION['mensagem']);
                        unset($_SESSION['mensagem_tipo']);
                    }
                    ?>

                    <?php if ($action === 'listar'): ?>
                    <!-- Listagem de Boletos -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <!-- Filtros -->
                        <div class="p-6 border-b border-gray-200">
                            <form action="financeiro.php" method="get" class="space-y-4">
                                <input type="hidden" name="action" value="listar">

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                        <select name="status" id="status" class="form-select w-full">
                                            <option value="">Todos</option>
                                            <option value="pendente" <?php echo isset($filtros['status']) && $filtros['status'] == 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                                            <option value="pago" <?php echo isset($filtros['status']) && $filtros['status'] == 'pago' ? 'selected' : ''; ?>>Pago</option>
                                            <option value="cancelado" <?php echo isset($filtros['status']) && $filtros['status'] == 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                                            <option value="vencido" <?php echo isset($filtros['status']) && $filtros['status'] == 'vencido' ? 'selected' : ''; ?>>Vencido</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label for="data_inicio" class="block text-sm font-medium text-gray-700 mb-1">Data Inicial</label>
                                        <input type="date" name="data_inicio" id="data_inicio" class="form-input w-full" value="<?php echo $filtros['data_inicio'] ?? ''; ?>">
                                    </div>

                                    <div>
                                        <label for="data_fim" class="block text-sm font-medium text-gray-700 mb-1">Data Final</label>
                                        <input type="date" name="data_fim" id="data_fim" class="form-input w-full" value="<?php echo $filtros['data_fim'] ?? ''; ?>">
                                    </div>
                                </div>

                                <div class="flex justify-end">
                                    <button type="submit" class="btn-primary">
                                        <i class="fas fa-search mr-2"></i> Filtrar
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Listagem -->
                        <div class="p-6">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-semibold text-gray-800">Meus Boletos</h3>
                            </div>

                            <?php if (empty($boletos)): ?>
                            <div class="bg-gray-50 p-4 rounded-lg text-center">
                                <p class="text-gray-600">Nenhum boleto encontrado.</p>
                            </div>
                            <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descrição</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valor</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vencimento</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($boletos as $boleto): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $boleto['id']; ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($boleto['descricao']); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">R$ <?php echo number_format($boleto['valor'], 2, ',', '.'); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo date('d/m/Y', strtotime($boleto['data_vencimento'])); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="status-badge <?php
                                                    if ($boleto['status'] == 'pendente') echo 'status-pendente';
                                                    elseif ($boleto['status'] == 'pago') echo 'status-pago';
                                                    elseif ($boleto['status'] == 'cancelado') echo 'status-cancelado';
                                                    elseif ($boleto['status'] == 'vencido') echo 'status-vencido';
                                                ?>">
                                                    <?php
                                                        if ($boleto['status'] == 'pendente') echo 'Pendente';
                                                        elseif ($boleto['status'] == 'pago') echo 'Pago';
                                                        elseif ($boleto['status'] == 'cancelado') echo 'Cancelado';
                                                        elseif ($boleto['status'] == 'vencido') echo 'Vencido';
                                                    ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex flex-wrap justify-center gap-2">
                                                    <a href="financeiro.php?action=visualizar&id=<?php echo $boleto['id']; ?>" class="inline-flex items-center px-2.5 py-1.5 bg-blue-100 text-blue-800 text-xs font-medium rounded hover:bg-blue-200">
                                                        <i class="fas fa-eye mr-1"></i> Ver
                                                    </a>
                                                    <?php if (!empty($boleto['url_boleto'])): ?>
                                                    <a href="<?php echo $boleto['url_boleto']; ?>" target="_blank" class="inline-flex items-center px-2.5 py-1.5 bg-green-100 text-green-800 text-xs font-medium rounded hover:bg-green-200">
                                                        <i class="fas fa-file-invoice-dollar mr-1"></i> Abrir
                                                    </a>
                                                    <?php endif; ?>
                                                    <a href="../download_boleto.php?id=<?php echo $boleto['id']; ?>" target="_blank" class="inline-flex items-center px-2.5 py-1.5 bg-purple-100 text-purple-800 text-xs font-medium rounded hover:bg-purple-200">
                                                        <i class="fas fa-download mr-1"></i> Download
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Paginação -->
                            <?php if ($total_paginas > 1): ?>
                            <div class="mt-6 flex justify-center">
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Paginação">
                                    <?php if ($pagina > 1): ?>
                                    <a href="financeiro.php?pagina=<?php echo $pagina - 1; ?><?php echo !empty($filtros) ? '&' . http_build_query($filtros) : ''; ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                        <span class="sr-only">Anterior</span>
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                    <?php endif; ?>

                                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                                    <a href="financeiro.php?pagina=<?php echo $i; ?><?php echo !empty($filtros) ? '&' . http_build_query($filtros) : ''; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium <?php echo $i == $pagina ? 'text-blue-600 bg-blue-50' : 'text-gray-700 hover:bg-gray-50'; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                    <?php endfor; ?>

                                    <?php if ($pagina < $total_paginas): ?>
                                    <a href="financeiro.php?pagina=<?php echo $pagina + 1; ?><?php echo !empty($filtros) ? '&' . http_build_query($filtros) : ''; ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                        <span class="sr-only">Próxima</span>
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                    <?php endif; ?>
                                </nav>
                            </div>
                            
                            <div class="mt-2 text-center text-sm text-gray-500">
                                Mostrando <?php echo count($boletos); ?> de <?php echo $total_registros; ?> boletos
                                (Página <?php echo $pagina; ?> de <?php echo $total_paginas; ?>)
                            </div>
                            <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php elseif ($action === 'visualizar'): ?>
                    <!-- Visualização de Boleto -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <div class="p-6">
                            <div class="flex justify-between items-center mb-6">
                                <h3 class="text-xl font-semibold text-gray-800">Detalhes do Boleto</h3>
                                <div class="flex space-x-2">
                                    <?php if (!empty($boleto['url_boleto'])): ?>
                                    <a href="<?php echo $boleto['url_boleto']; ?>" target="_blank" class="btn-success">
                                        <i class="fas fa-file-invoice-dollar mr-2"></i> Abrir Boleto
                                    </a>
                                    <?php endif; ?>
                                    <a href="../download_boleto.php?id=<?php echo $boleto['id']; ?>" target="_blank" class="btn-primary">
                                        <i class="fas fa-download mr-2"></i> Download PDF
                                    </a>
                                    <a href="financeiro.php" class="btn-secondary">
                                        <i class="fas fa-arrow-left mr-2"></i> Voltar
                                    </a>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Informações do Boleto -->
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <h4 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Informações do Boleto</h4>
                                    <div class="space-y-3">
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">ID:</span>
                                            <span class="font-medium"><?php echo $boleto['id']; ?></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Descrição:</span>
                                            <span class="font-medium"><?php echo htmlspecialchars($boleto['descricao']); ?></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Valor:</span>
                                            <span class="font-medium">R$ <?php echo number_format($boleto['valor'], 2, ',', '.'); ?></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Data de Emissão:</span>
                                            <span class="font-medium"><?php echo date('d/m/Y', strtotime($boleto['data_emissao'])); ?></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Data de Vencimento:</span>
                                            <span class="font-medium"><?php echo date('d/m/Y', strtotime($boleto['data_vencimento'])); ?></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Status:</span>
                                            <span class="status-badge <?php
                                                if ($boleto['status'] == 'pendente') echo 'status-pendente';
                                                elseif ($boleto['status'] == 'pago') echo 'status-pago';
                                                elseif ($boleto['status'] == 'cancelado') echo 'status-cancelado';
                                                elseif ($boleto['status'] == 'vencido') echo 'status-vencido';
                                            ?>">
                                                <?php
                                                    if ($boleto['status'] == 'pendente') echo 'Pendente';
                                                    elseif ($boleto['status'] == 'pago') echo 'Pago';
                                                    elseif ($boleto['status'] == 'cancelado') echo 'Cancelado';
                                                    elseif ($boleto['status'] == 'vencido') echo 'Vencido';
                                                ?>
                                            </span>
                                        </div>
                                        <?php if ($boleto['status'] == 'pago' && !empty($boleto['data_pagamento'])): ?>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Data de Pagamento:</span>
                                            <span class="font-medium"><?php echo date('d/m/Y', strtotime($boleto['data_pagamento'])); ?></span>
                                        </div>
                                        <?php endif; ?>
                                        <?php if (!empty($boleto['nosso_numero'])): ?>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Nosso Número:</span>
                                            <span class="font-medium"><?php echo $boleto['nosso_numero']; ?></span>
                                        </div>
                                        <?php endif; ?>
                                        <?php if (!empty($boleto['linha_digitavel'])): ?>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Linha Digitável:</span>
                                            <span class="font-medium"><?php echo $boleto['linha_digitavel']; ?></span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Informações do Pagador -->
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <h4 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Informações do Pagador</h4>
                                    <div class="space-y-3">
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Nome:</span>
                                            <span class="font-medium"><?php echo htmlspecialchars($boleto['nome_pagador']); ?></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">CPF/CNPJ:</span>
                                            <span class="font-medium"><?php echo htmlspecialchars($boleto['cpf_pagador']); ?></span>
                                        </div>
                                        <?php if (!empty($boleto['endereco'])): ?>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Endereço:</span>
                                            <span class="font-medium"><?php echo htmlspecialchars($boleto['endereco']); ?></span>
                                        </div>
                                        <?php endif; ?>
                                        <?php if (!empty($boleto['cidade']) && !empty($boleto['uf'])): ?>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Cidade/UF:</span>
                                            <span class="font-medium"><?php echo htmlspecialchars($boleto['cidade'] . '/' . $boleto['uf']); ?></span>
                                        </div>
                                        <?php endif; ?>
                                        <?php if (!empty($boleto['cep'])): ?>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">CEP:</span>
                                            <span class="font-medium"><?php echo htmlspecialchars($boleto['cep']); ?></span>
                                        </div>
                                        <?php endif; ?>
                                        <?php if (!empty($boleto['observacoes'])): ?>
                                        <div class="mt-4">
                                            <span class="text-gray-600 block mb-1">Observações:</span>
                                            <div class="bg-white p-3 rounded border border-gray-200 text-sm">
                                                <?php echo nl2br(htmlspecialchars($boleto['observacoes'])); ?>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
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
