<?php
/**
 * Cobrança de Polos - Módulo Financeiro
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
$cobrancaId = $_GET['id'] ?? null;

// Processa ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'salvar') {
        try {
            $dados = [
                'polo_id' => $_POST['polo_id'],
                'descricao' => $_POST['descricao'],
                'valor' => str_replace(['R$', '.', ','], ['', '', '.'], $_POST['valor']),
                'data_vencimento' => $_POST['data_vencimento'],
                'mes_referencia' => $_POST['mes_referencia'],
                'tipo_cobranca' => $_POST['tipo_cobranca'],
                'observacoes' => $_POST['observacoes'] ?? null,
                'usuario_id' => Auth::getUserId()
            ];

            if ($cobrancaId) {
                $dados['updated_at'] = date('Y-m-d H:i:s');
                $db->update('cobranca_polos', $dados, 'id = ?', [$cobrancaId]);
                $_SESSION['success'] = 'Cobrança atualizada com sucesso!';
            } else {
                $db->insert('cobranca_polos', $dados);
                $_SESSION['success'] = 'Cobrança cadastrada com sucesso!';
            }

            header('Location: cobranca_polos.php');
            exit;

        } catch (Exception $e) {
            $_SESSION['error'] = 'Erro ao salvar cobrança: ' . $e->getMessage();
        }
    }

    if ($action === 'pagar' && $cobrancaId) {
        try {
            $dados = [
                'status' => 'pago',
                'data_pagamento' => $_POST['data_pagamento'] ?? date('Y-m-d'),
                'forma_pagamento' => $_POST['forma_pagamento'] ?? null,
                'observacoes' => $_POST['observacoes'] ?? null,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $db->update('cobranca_polos', $dados, 'id = ?', [$cobrancaId]);

            // Registra na conta a receber
            $cobranca = $db->fetchOne("
                SELECT cp.*, p.nome as polo_nome
                FROM cobranca_polos cp
                JOIN polos p ON cp.polo_id = p.id
                WHERE cp.id = ?
            ", [$cobrancaId]);

            if ($cobranca) {
                $contaReceber = [
                    'descricao' => $cobranca['descricao'] . ' - ' . $cobranca['polo_nome'],
                    'valor' => $cobranca['valor'],
                    'data_vencimento' => $cobranca['data_vencimento'],
                    'data_recebimento' => $dados['data_pagamento'],
                    'cliente_id' => $cobranca['polo_id'],
                    'cliente_nome' => $cobranca['polo_nome'],
                    'cliente_tipo' => 'polo',
                    'categoria_id' => 2, // Categoria "Cobrança de Polos"
                    'forma_recebimento' => $dados['forma_pagamento'],
                    'status' => 'recebido',
                    'observacoes' => $dados['observacoes'],
                    'usuario_id' => Auth::getUserId()
                ];

                $db->insert('contas_receber', $contaReceber);

                // Registra transação financeira
                $transacao = [
                    'tipo' => 'receita',
                    'descricao' => $contaReceber['descricao'],
                    'valor' => $cobranca['valor'],
                    'data_transacao' => $dados['data_pagamento'],
                    'categoria_id' => 2,
                    'forma_pagamento' => $dados['forma_pagamento'],
                    'referencia_tipo' => 'conta_receber',
                    'observacoes' => $dados['observacoes'],
                    'usuario_id' => Auth::getUserId()
                ];

                $db->insert('transacoes_financeiras', $transacao);
            }

            $_SESSION['success'] = 'Pagamento registrado com sucesso!';
            header('Location: cobranca_polos.php');
            exit;

        } catch (Exception $e) {
            $_SESSION['error'] = 'Erro ao registrar pagamento: ' . $e->getMessage();
        }
    }
}

// Busca dados para exibição
if ($action === 'editar' && $cobrancaId) {
    try {
        $cobranca = $db->fetchOne("SELECT * FROM cobranca_polos WHERE id = ?", [$cobrancaId]);
        if (!$cobranca) {
            $_SESSION['error'] = 'Cobrança não encontrada.';
            header('Location: cobranca_polos.php');
            exit;
        }
    } catch (Exception $e) {
        $_SESSION['error'] = 'Erro ao buscar cobrança: ' . $e->getMessage();
        header('Location: cobranca_polos.php');
        exit;
    }
}

// Busca polos para o formulário
try {
    $polos = $db->fetchAll("
        SELECT * FROM polos
        WHERE status = 'ativo'
        ORDER BY nome
    ");
} catch (Exception $e) {
    $polos = [];
}

if ($action === 'listar') {
    $filtro = $_GET['filtro'] ?? '';
    $busca = $_GET['busca'] ?? '';
    $mesReferencia = $_GET['mes_referencia'] ?? '';
    $tipoCobranca = $_GET['tipo_cobranca'] ?? '';

    $where = "1=1";
    $params = [];

    if ($filtro === 'pendentes') {
        $where .= " AND cp.status = 'pendente'";
    } elseif ($filtro === 'pagas') {
        $where .= " AND cp.status = 'pago'";
    } elseif ($filtro === 'vencidas') {
        $where .= " AND cp.status = 'pendente' AND cp.data_vencimento < CURDATE()";
    }

    if ($mesReferencia) {
        $where .= " AND cp.mes_referencia = ?";
        $params[] = $mesReferencia;
    }

    if ($tipoCobranca) {
        $where .= " AND cp.tipo_cobranca = ?";
        $params[] = $tipoCobranca;
    }

    if ($busca) {
        $where .= " AND (cp.descricao LIKE ? OR p.nome LIKE ?)";
        $params[] = "%$busca%";
        $params[] = "%$busca%";
    }

    try {
        $cobrancas = $db->fetchAll("
            SELECT cp.*, p.nome as polo_nome, p.cidade as polo_cidade
            FROM cobranca_polos cp
            JOIN polos p ON cp.polo_id = p.id
            WHERE $where
            ORDER BY cp.data_vencimento ASC, cp.id DESC
        ", $params);
    } catch (Exception $e) {
        $cobrancas = [];
        $tabelasNaoExistem = true;
    }
}

$pageTitle = 'Cobrança de Polos';
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
                    <!-- Listagem de Cobranças de Polos -->
                    <div class="mb-8">
                        <div class="flex justify-between items-center mb-6">
                            <div>
                                <h1 class="text-3xl font-bold text-gray-900">Cobrança de Polos</h1>
                                <p class="text-gray-600 mt-2">Gerencie as cobranças dos polos da instituição</p>
                            </div>
                            <a href="cobranca_polos.php?action=nova" class="bg-purple-600 text-white px-4 py-2 rounded-md hover:bg-purple-700">
                                <i class="fas fa-plus mr-2"></i>Nova Cobrança
                            </a>
                        </div>

                        <!-- Filtros -->
                        <div class="bg-white rounded-lg shadow p-4 mb-6">
                            <form method="GET" class="flex flex-wrap gap-4 items-end">
                                <input type="hidden" name="action" value="listar">
                                <div class="flex-1 min-w-64">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                                    <input type="text" name="busca" value="<?php echo htmlspecialchars($_GET['busca'] ?? ''); ?>"
                                           placeholder="Descrição ou polo..."
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                    <select name="filtro" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                                        <option value="">Todas</option>
                                        <option value="pendentes" <?php echo ($_GET['filtro'] ?? '') === 'pendentes' ? 'selected' : ''; ?>>Pendentes</option>
                                        <option value="vencidas" <?php echo ($_GET['filtro'] ?? '') === 'vencidas' ? 'selected' : ''; ?>>Vencidas</option>
                                        <option value="pagas" <?php echo ($_GET['filtro'] ?? '') === 'pagas' ? 'selected' : ''; ?>>Pagas</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                                    <select name="tipo_cobranca" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                                        <option value="">Todos</option>
                                        <option value="mensalidade" <?php echo ($_GET['tipo_cobranca'] ?? '') === 'mensalidade' ? 'selected' : ''; ?>>Mensalidade</option>
                                        <option value="taxa" <?php echo ($_GET['tipo_cobranca'] ?? '') === 'taxa' ? 'selected' : ''; ?>>Taxa</option>
                                        <option value="outros" <?php echo ($_GET['tipo_cobranca'] ?? '') === 'outros' ? 'selected' : ''; ?>>Outros</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Mês Ref.</label>
                                    <input type="month" name="mes_referencia" value="<?php echo $_GET['mes_referencia'] ?? ''; ?>"
                                           class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                                </div>
                                <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded-md hover:bg-purple-700">
                                    <i class="fas fa-search mr-2"></i>Buscar
                                </button>
                            </form>
                        </div>

                        <!-- Tabela -->
                        <div class="bg-white rounded-lg shadow overflow-hidden">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-purple-600">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Polo</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Descrição</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Tipo</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Valor</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Vencimento</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Ações</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php if (empty($cobrancas)): ?>
                                    <tr>
                                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                            <?php if (isset($tabelasNaoExistem)): ?>
                                            Configure o módulo financeiro primeiro.
                                            <?php else: ?>
                                            Nenhuma cobrança encontrada.
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($cobrancas as $cobranca): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($cobranca['polo_nome']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($cobranca['polo_cidade'] ?? ''); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?php echo htmlspecialchars($cobranca['descricao']); ?></div>
                                            <div class="text-sm text-gray-500">Ref: <?php echo date('m/Y', strtotime($cobranca['mes_referencia'])); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 py-1 text-xs rounded-full
                                                <?php
                                                switch($cobranca['tipo_cobranca']) {
                                                    case 'mensalidade': echo 'bg-blue-100 text-blue-800'; break;
                                                    case 'taxa': echo 'bg-yellow-100 text-yellow-800'; break;
                                                    default: echo 'bg-gray-100 text-gray-800';
                                                }
                                                ?>">
                                                <?php echo ucfirst($cobranca['tipo_cobranca']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            R$ <?php echo number_format($cobranca['valor'], 2, ',', '.'); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php
                                            $dataVencimento = new DateTime($cobranca['data_vencimento']);
                                            echo $dataVencimento->format('d/m/Y');

                                            if ($cobranca['status'] === 'pendente') {
                                                $hoje = new DateTime();
                                                if ($dataVencimento < $hoje) {
                                                    $diff = $hoje->diff($dataVencimento);
                                                    echo '<br><span class="text-red-600 text-xs">Vencida há ' . $diff->days . ' dia(s)</span>';
                                                }
                                            }
                                            ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php
                                            $statusClass = 'bg-gray-100 text-gray-800';
                                            $statusText = ucfirst($cobranca['status']);

                                            if ($cobranca['status'] === 'pago') {
                                                $statusClass = 'bg-green-100 text-green-800';
                                                $statusText = 'Paga';
                                            } elseif ($cobranca['status'] === 'pendente') {
                                                $dataVencimento = new DateTime($cobranca['data_vencimento']);
                                                $hoje = new DateTime();

                                                if ($dataVencimento < $hoje) {
                                                    $statusClass = 'bg-red-100 text-red-800';
                                                    $statusText = 'Vencida';
                                                } else {
                                                    $statusClass = 'bg-yellow-100 text-yellow-800';
                                                    $statusText = 'Pendente';
                                                }
                                            }
                                            ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $statusClass; ?>">
                                                <?php echo $statusText; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="cobranca_polos.php?action=editar&id=<?php echo $cobranca['id']; ?>"
                                               class="text-purple-600 hover:text-purple-900 mr-3">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($cobranca['status'] === 'pendente'): ?>
                                            <button onclick="abrirModalPagamento(<?php echo $cobranca['id']; ?>, '<?php echo htmlspecialchars($cobranca['polo_nome']); ?>', '<?php echo htmlspecialchars($cobranca['descricao']); ?>', <?php echo $cobranca['valor']; ?>)"
                                                    class="text-green-600 hover:text-green-900 mr-3">
                                                <i class="fas fa-money-check-alt"></i>
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
                    <!-- Formulário de Cobrança de Polo -->
                    <div class="mb-8">
                        <div class="flex items-center mb-6">
                            <a href="cobranca_polos.php" class="text-purple-600 hover:text-purple-800 mr-4">
                                <i class="fas fa-arrow-left"></i>
                            </a>
                            <div>
                                <h1 class="text-3xl font-bold text-gray-900">
                                    <?php echo $action === 'nova' ? 'Nova Cobrança de Polo' : 'Editar Cobrança de Polo'; ?>
                                </h1>
                                <p class="text-gray-600 mt-2">Preencha os dados da cobrança</p>
                            </div>
                        </div>

                        <form method="POST" class="bg-white rounded-lg shadow p-6">
                            <input type="hidden" name="action" value="salvar">

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Polo *</label>
                                    <select name="polo_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                                        <option value="">Selecione um polo</option>
                                        <?php foreach ($polos as $polo): ?>
                                        <option value="<?php echo $polo['id']; ?>"
                                                <?php echo ($cobranca['polo_id'] ?? '') == $polo['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($polo['nome'] . ' - ' . ($polo['cidade'] ?? '')); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Cobrança *</label>
                                    <select name="tipo_cobranca" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                                        <option value="">Selecione o tipo</option>
                                        <option value="mensalidade" <?php echo ($cobranca['tipo_cobranca'] ?? '') === 'mensalidade' ? 'selected' : ''; ?>>Mensalidade</option>
                                        <option value="taxa" <?php echo ($cobranca['tipo_cobranca'] ?? '') === 'taxa' ? 'selected' : ''; ?>>Taxa</option>
                                        <option value="outros" <?php echo ($cobranca['tipo_cobranca'] ?? '') === 'outros' ? 'selected' : ''; ?>>Outros</option>
                                    </select>
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Descrição *</label>
                                    <input type="text" name="descricao" required
                                           value="<?php echo htmlspecialchars($cobranca['descricao'] ?? ''); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Valor *</label>
                                    <input type="text" name="valor" required data-mask="currency"
                                           value="<?php echo isset($cobranca['valor']) ? 'R$ ' . number_format($cobranca['valor'], 2, ',', '.') : ''; ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Data de Vencimento *</label>
                                    <input type="date" name="data_vencimento" required
                                           value="<?php echo $cobranca['data_vencimento'] ?? ''; ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Mês de Referência *</label>
                                    <input type="month" name="mes_referencia" required
                                           value="<?php echo $cobranca['mes_referencia'] ?? date('Y-m'); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                                    <textarea name="observacoes" rows="3"
                                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500"><?php echo htmlspecialchars($cobranca['observacoes'] ?? ''); ?></textarea>
                                </div>
                            </div>

                            <div class="mt-8 flex justify-end space-x-3">
                                <a href="cobranca_polos.php" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400">
                                    Cancelar
                                </a>
                                <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded-md hover:bg-purple-700">
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
    function abrirModalPagamento(id, poloNome, descricao, valor) {
        const modal = Financeiro.Modal.show('Registrar Pagamento de Cobrança', `
            <form id="form-pagamento" method="POST">
                <input type="hidden" name="action" value="pagar">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cobrança</label>
                        <p class="text-sm text-gray-900">${poloNome}</p>
                        <p class="text-sm text-gray-600">${descricao}</p>
                        <p class="text-sm text-gray-600">Valor: R$ ${valor.toLocaleString('pt-BR', {minimumFractionDigits: 2})}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data do Pagamento</label>
                        <input type="date" name="data_pagamento" value="${new Date().toISOString().split('T')[0]}" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Forma de Pagamento</label>
                        <select name="forma_pagamento" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                            <option value="">Selecione</option>
                            <option value="dinheiro">Dinheiro</option>
                            <option value="pix">PIX</option>
                            <option value="transferencia">Transferência</option>
                            <option value="cartao_debito">Cartão de Débito</option>
                            <option value="cartao_credito">Cartão de Crédito</option>
                            <option value="boleto">Boleto</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                        <textarea name="observacoes" rows="2"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500"></textarea>
                    </div>
                </div>
            </form>
        `, {
            confirmText: 'Registrar Pagamento',
            onConfirm: `document.getElementById('form-pagamento').action = 'cobranca_polos.php?action=pagar&id=${id}'; document.getElementById('form-pagamento').submit();`
        });
    }
    </script>
</body>
</html>