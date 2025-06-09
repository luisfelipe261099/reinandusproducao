<?php
/**
 * Contas a Receber - Módulo Financeiro
 */

require_once '../includes/init.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

// Verifica autenticação e permissão
Auth::requireLogin();
$userType = Auth::getUserType();
if (!in_array($userType, ['financeiro', 'admin_master'])) {
    $_SESSION['error'] = 'Você não tem permissão para acessar o módulo financeiro.';
    header('Location: ../index.php');
    exit;
}

$db = Database::getInstance();
$action = $_GET['action'] ?? 'listar';
$contaId = $_GET['id'] ?? null;

// Processa ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'salvar') {
        try {
            $dados = [
                'descricao' => $_POST['descricao'],
                'valor' => str_replace(['R$', '.', ','], ['', '', '.'], $_POST['valor']),
                'data_vencimento' => $_POST['data_vencimento'],
                'cliente_nome' => $_POST['cliente_nome'] ?? null,
                'cliente_tipo' => $_POST['cliente_tipo'] ?? 'terceiro',
                'categoria_id' => $_POST['categoria_id'] ?? null,
                'observacoes' => $_POST['observacoes'] ?? null,
                'usuario_id' => Auth::getUserId()
            ];

            if ($contaId) {
                $dados['updated_at'] = date('Y-m-d H:i:s');
                $db->update('contas_receber', $dados, 'id = ?', [$contaId]);
                $_SESSION['success'] = 'Conta a receber atualizada com sucesso!';
            } else {
                $db->insert('contas_receber', $dados);
                $_SESSION['success'] = 'Conta a receber cadastrada com sucesso!';
            }

            header('Location: contas_receber.php');
            exit;

        } catch (Exception $e) {
            $_SESSION['error'] = 'Erro ao salvar conta: ' . $e->getMessage();
        }
    }

    if ($action === 'receber' && $contaId) {
        try {
            $dados = [
                'status' => 'recebido',
                'data_recebimento' => $_POST['data_recebimento'] ?? date('Y-m-d'),
                'forma_recebimento' => $_POST['forma_recebimento'] ?? null,
                'conta_bancaria_id' => $_POST['conta_bancaria_id'] ?? null,
                'observacoes' => $_POST['observacoes'] ?? null,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $db->update('contas_receber', $dados, 'id = ?', [$contaId]);

            // Registra a transação financeira
            $conta = $db->fetchOne("SELECT * FROM contas_receber WHERE id = ?", [$contaId]);
            if ($conta) {
                $transacao = [
                    'tipo' => 'receita',
                    'descricao' => $conta['descricao'],
                    'valor' => $conta['valor'],
                    'data_transacao' => $dados['data_recebimento'],
                    'categoria_id' => $conta['categoria_id'],
                    'conta_bancaria_id' => $dados['conta_bancaria_id'],
                    'forma_pagamento' => $dados['forma_recebimento'],
                    'referencia_tipo' => 'conta_receber',
                    'referencia_id' => $contaId,
                    'observacoes' => $dados['observacoes'],
                    'usuario_id' => Auth::getUserId()
                ];

                $db->insert('transacoes_financeiras', $transacao);
            }

            $_SESSION['success'] = 'Recebimento registrado com sucesso!';
            header('Location: contas_receber.php');
            exit;

        } catch (Exception $e) {
            $_SESSION['error'] = 'Erro ao registrar recebimento: ' . $e->getMessage();
        }
    }
}

// Busca dados para exibição
if ($action === 'editar' && $contaId) {
    try {
        $conta = $db->fetchOne("SELECT * FROM contas_receber WHERE id = ?", [$contaId]);
        if (!$conta) {
            $_SESSION['error'] = 'Conta não encontrada.';
            header('Location: contas_receber.php');
            exit;
        }
    } catch (Exception $e) {
        $_SESSION['error'] = 'Erro ao buscar conta: ' . $e->getMessage();
        header('Location: contas_receber.php');
        exit;
    }
}

// Busca categorias para o formulário
try {
    $categorias = $db->fetchAll("
        SELECT * FROM categorias_financeiras
        WHERE tipo = 'receita' AND status = 'ativo'
        ORDER BY nome
    ");
} catch (Exception $e) {
    $categorias = [];
}

// Busca contas bancárias
try {
    $contasBancarias = $db->fetchAll("
        SELECT * FROM contas_bancarias
        WHERE status = 'ativo'
        ORDER BY nome
    ");
} catch (Exception $e) {
    $contasBancarias = [];
}

if ($action === 'listar') {
    $filtro = $_GET['filtro'] ?? '';
    $busca = $_GET['busca'] ?? '';
    $clienteTipo = $_GET['cliente_tipo'] ?? '';

    $where = "1=1";
    $params = [];

    if ($filtro === 'pendentes') {
        $where .= " AND status = 'pendente'";
    } elseif ($filtro === 'recebidas') {
        $where .= " AND status = 'recebido'";
    } elseif ($filtro === 'vencidas') {
        $where .= " AND status = 'pendente' AND data_vencimento < CURDATE()";
    } elseif ($filtro === 'vencendo') {
        $where .= " AND status = 'pendente' AND data_vencimento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
    }

    if ($clienteTipo) {
        $where .= " AND cliente_tipo = ?";
        $params[] = $clienteTipo;
    }

    if ($busca) {
        $where .= " AND (descricao LIKE ? OR cliente_nome LIKE ?)";
        $params[] = "%$busca%";
        $params[] = "%$busca%";
    }

    try {
        $contas = $db->fetchAll("
            SELECT cr.*, cf.nome as categoria_nome
            FROM contas_receber cr
            LEFT JOIN categorias_financeiras cf ON cr.categoria_id = cf.id
            WHERE $where
            ORDER BY cr.data_vencimento ASC, cr.id DESC
        ", $params);
    } catch (Exception $e) {
        $contas = [];
        $tabelasNaoExistem = true;
    }
}

$pageTitle = 'Contas a Receber';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Faciência ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/financeiro.css">
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col ml-64">
            <!-- Header -->
            <?php include 'includes/header.php'; ?>

            <!-- Content -->
            <main class="flex-1 p-6 overflow-y-auto">
                <div class="max-w-7xl mx-auto">

                    <?php if (isset($tabelasNaoExistem)): ?>
                    <!-- Alerta de configuração -->
                    <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm">
                                    O módulo financeiro precisa ser configurado primeiro.
                                    <a href="setup.php" class="font-medium underline">Clique aqui para configurar</a>
                                </p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($action === 'listar'): ?>
                    <!-- Listagem de Contas a Receber -->
                    <div class="mb-8">
                        <div class="flex justify-between items-center mb-6">
                            <div>
                                <h1 class="text-3xl font-bold text-gray-900">Contas a Receber</h1>
                                <p class="text-gray-600 mt-2">Gerencie as contas a receber da instituição</p>
                            </div>
                            <a href="contas_receber.php?action=nova" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                                <i class="fas fa-plus mr-2"></i>Nova Conta a Receber
                            </a>
                        </div>

                        <!-- Filtros -->
                        <div class="bg-white rounded-lg shadow p-4 mb-6">
                            <form method="GET" class="flex flex-wrap gap-4 items-end">
                                <input type="hidden" name="action" value="listar">
                                <div class="flex-1 min-w-64">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                                    <input type="text" name="busca" value="<?php echo htmlspecialchars($_GET['busca'] ?? ''); ?>"
                                           placeholder="Descrição ou cliente..."
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                    <select name="filtro" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                        <option value="">Todas</option>
                                        <option value="pendentes" <?php echo ($_GET['filtro'] ?? '') === 'pendentes' ? 'selected' : ''; ?>>Pendentes</option>
                                        <option value="vencidas" <?php echo ($_GET['filtro'] ?? '') === 'vencidas' ? 'selected' : ''; ?>>Vencidas</option>
                                        <option value="vencendo" <?php echo ($_GET['filtro'] ?? '') === 'vencendo' ? 'selected' : ''; ?>>Vencendo (7 dias)</option>
                                        <option value="recebidas" <?php echo ($_GET['filtro'] ?? '') === 'recebidas' ? 'selected' : ''; ?>>Recebidas</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Cliente</label>
                                    <select name="cliente_tipo" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                        <option value="">Todos</option>
                                        <option value="aluno" <?php echo ($_GET['cliente_tipo'] ?? '') === 'aluno' ? 'selected' : ''; ?>>Alunos</option>
                                        <option value="polo" <?php echo ($_GET['cliente_tipo'] ?? '') === 'polo' ? 'selected' : ''; ?>>Polos</option>
                                        <option value="terceiro" <?php echo ($_GET['cliente_tipo'] ?? '') === 'terceiro' ? 'selected' : ''; ?>>Terceiros</option>
                                    </select>
                                </div>
                                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                                    <i class="fas fa-search mr-2"></i>Buscar
                                </button>
                            </form>
                        </div>

                        <!-- Tabela -->
                        <div class="bg-white rounded-lg shadow overflow-hidden">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-green-600">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Descrição</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Cliente</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Valor</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Vencimento</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Ações</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php if (empty($contas)): ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                            <?php if (isset($tabelasNaoExistem)): ?>
                                            Configure o módulo financeiro primeiro.
                                            <?php else: ?>
                                            Nenhuma conta a receber encontrada.
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($contas as $conta): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($conta['descricao']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($conta['categoria_nome'] ?? ''); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?php echo htmlspecialchars($conta['cliente_nome'] ?? '-'); ?></div>
                                            <div class="text-sm text-gray-500">
                                                <span class="px-2 py-1 text-xs rounded-full
                                                    <?php
                                                    $clienteTipo = $conta['cliente_tipo'] ?? 'terceiro';
                                                    switch($clienteTipo) {
                                                        case 'aluno': echo 'bg-blue-100 text-blue-800'; break;
                                                        case 'polo': echo 'bg-purple-100 text-purple-800'; break;
                                                        default: echo 'bg-gray-100 text-gray-800';
                                                    }
                                                    ?>">
                                                    <?php echo ucfirst($clienteTipo); ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            R$ <?php echo number_format($conta['valor'], 2, ',', '.'); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php
                                            $dataVencimento = new DateTime($conta['data_vencimento']);
                                            $hoje = new DateTime();
                                            $diff = $hoje->diff($dataVencimento);

                                            echo $dataVencimento->format('d/m/Y');

                                            if ($conta['status'] === 'pendente') {
                                                if ($dataVencimento < $hoje) {
                                                    echo '<br><span class="text-red-600 text-xs">Vencida há ' . $diff->days . ' dia(s)</span>';
                                                } elseif ($diff->days <= 7) {
                                                    echo '<br><span class="text-yellow-600 text-xs">Vence em ' . $diff->days . ' dia(s)</span>';
                                                }
                                            }
                                            ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php
                                            $statusClass = 'bg-gray-100 text-gray-800';
                                            $statusText = ucfirst($conta['status']);

                                            if ($conta['status'] === 'recebido') {
                                                $statusClass = 'bg-green-100 text-green-800';
                                                $statusText = 'Recebida';
                                            } elseif ($conta['status'] === 'pendente') {
                                                $dataVencimento = new DateTime($conta['data_vencimento']);
                                                $hoje = new DateTime();

                                                if ($dataVencimento < $hoje) {
                                                    $statusClass = 'bg-red-100 text-red-800';
                                                    $statusText = 'Vencida';
                                                } elseif ($dataVencimento->diff($hoje)->days <= 7) {
                                                    $statusClass = 'bg-yellow-100 text-yellow-800';
                                                    $statusText = 'Vencendo';
                                                } else {
                                                    $statusClass = 'bg-blue-100 text-blue-800';
                                                    $statusText = 'Pendente';
                                                }
                                            }
                                            ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $statusClass; ?>">
                                                <?php echo $statusText; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="contas_receber.php?action=editar&id=<?php echo $conta['id']; ?>"
                                               class="text-green-600 hover:text-green-900 mr-3">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($conta['status'] === 'pendente'): ?>
                                            <button onclick="abrirModalRecebimento(<?php echo $conta['id']; ?>, '<?php echo htmlspecialchars($conta['descricao']); ?>', <?php echo $conta['valor']; ?>)"
                                                    class="text-blue-600 hover:text-blue-900 mr-3">
                                                <i class="fas fa-hand-holding-usd"></i>
                                            </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <?php else: ?>
                    <!-- Formulário de Conta a Receber -->
                    <div class="mb-8">
                        <div class="flex items-center mb-6">
                            <a href="contas_receber.php" class="text-green-600 hover:text-green-800 mr-4">
                                <i class="fas fa-arrow-left"></i>
                            </a>
                            <div>
                                <h1 class="text-3xl font-bold text-gray-900">
                                    <?php echo $action === 'nova' ? 'Nova Conta a Receber' : 'Editar Conta a Receber'; ?>
                                </h1>
                                <p class="text-gray-600 mt-2">Preencha os dados da conta a receber</p>
                            </div>
                        </div>

                        <form method="POST" class="bg-white rounded-lg shadow p-6">
                            <input type="hidden" name="action" value="salvar">

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Descrição *</label>
                                    <input type="text" name="descricao" required
                                           value="<?php echo htmlspecialchars($conta['descricao'] ?? ''); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Valor *</label>
                                    <input type="text" name="valor" required data-mask="currency"
                                           value="<?php echo isset($conta['valor']) ? 'R$ ' . number_format($conta['valor'], 2, ',', '.') : ''; ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Data de Vencimento *</label>
                                    <input type="date" name="data_vencimento" required
                                           value="<?php echo $conta['data_vencimento'] ?? ''; ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Cliente</label>
                                    <input type="text" name="cliente_nome"
                                           value="<?php echo htmlspecialchars($conta['cliente_nome'] ?? ''); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Cliente</label>
                                    <select name="cliente_tipo" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                        <option value="terceiro" <?php echo ($conta['cliente_tipo'] ?? 'terceiro') === 'terceiro' ? 'selected' : ''; ?>>Terceiro</option>
                                        <option value="aluno" <?php echo ($conta['cliente_tipo'] ?? '') === 'aluno' ? 'selected' : ''; ?>>Aluno</option>
                                        <option value="polo" <?php echo ($conta['cliente_tipo'] ?? '') === 'polo' ? 'selected' : ''; ?>>Polo</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Categoria</label>
                                    <select name="categoria_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                        <option value="">Selecione uma categoria</option>
                                        <?php foreach ($categorias as $categoria): ?>
                                        <option value="<?php echo $categoria['id']; ?>"
                                                <?php echo ($conta['categoria_id'] ?? '') == $categoria['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($categoria['nome']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                                    <textarea name="observacoes" rows="3"
                                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"><?php echo htmlspecialchars($conta['observacoes'] ?? ''); ?></textarea>
                                </div>
                            </div>

                            <div class="mt-8 flex justify-end space-x-3">
                                <a href="contas_receber.php" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400">
                                    Cancelar
                                </a>
                                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                                    <i class="fas fa-save mr-2"></i>Salvar
                                </button>
                            </div>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Scripts -->
    <script src="js/financeiro.js"></script>
    <script>
    function abrirModalRecebimento(id, descricao, valor) {
        const modal = Financeiro.Modal.show('Registrar Recebimento', `
            <form id="form-recebimento" method="POST">
                <input type="hidden" name="action" value="receber">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Conta</label>
                        <p class="text-sm text-gray-900">${descricao}</p>
                        <p class="text-sm text-gray-600">R$ ${valor.toLocaleString('pt-BR', {minimumFractionDigits: 2})}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data do Recebimento</label>
                        <input type="date" name="data_recebimento" value="${new Date().toISOString().split('T')[0]}" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Forma de Recebimento</label>
                        <select name="forma_recebimento" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                            <option value="">Selecione</option>
                            <option value="dinheiro">Dinheiro</option>
                            <option value="pix">PIX</option>
                            <option value="transferencia">Transferência</option>
                            <option value="cartao_debito">Cartão de Débito</option>
                            <option value="cartao_credito">Cartão de Crédito</option>
                            <option value="cheque">Cheque</option>
                            <option value="boleto">Boleto</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                        <textarea name="observacoes" rows="2"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"></textarea>
                    </div>
                </div>
            </form>
        `, {
            confirmText: 'Registrar Recebimento',
            onConfirm: `document.getElementById('form-recebimento').action = 'contas_receber.php?action=receber&id=${id}'; document.getElementById('form-recebimento').submit();`
        });
    }
    </script>
</body>
</html>
