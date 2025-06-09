<?php
require_once '../includes/init.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

Auth::requireLogin();
$userType = Auth::getUserType();
if (!in_array($userType, ['financeiro', 'admin_master'])) {
    $_SESSION['error'] = 'Você não tem permissão para acessar o módulo financeiro.';
    header('Location: ../index.php');
    exit;
}

$db = Database::getInstance();
$action = $_GET['action'] ?? 'categorias';

// Processa ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';
    
    if ($postAction === 'salvar_categoria') {
        try {
            $dados = [
                'nome' => $_POST['nome'],
                'descricao' => $_POST['descricao'] ?? null,
                'tipo' => $_POST['tipo'],
                'cor' => $_POST['cor'] ?? '#3498db',
                'status' => $_POST['status'] ?? 'ativo'
            ];
            
            if ($_POST['id'] ?? '') {
                $db->update('categorias_financeiras', $dados, 'id = ?', [$_POST['id']]);
                $_SESSION['success'] = 'Categoria atualizada com sucesso!';
            } else {
                $db->insert('categorias_financeiras', $dados);
                $_SESSION['success'] = 'Categoria criada com sucesso!';
            }
            
            header('Location: configuracoes.php?action=categorias');
            exit;
        } catch (Exception $e) {
            $_SESSION['error'] = 'Erro ao salvar categoria: ' . $e->getMessage();
        }
    }
    
    if ($postAction === 'salvar_conta_bancaria') {
        try {
            $dados = [
                'nome' => $_POST['nome'],
                'banco' => $_POST['banco'] ?? null,
                'agencia' => $_POST['agencia'] ?? null,
                'conta' => $_POST['conta'] ?? null,
                'tipo' => $_POST['tipo'],
                'saldo_inicial' => str_replace(['R$', '.', ','], ['', '', '.'], $_POST['saldo_inicial']),
                'saldo_atual' => str_replace(['R$', '.', ','], ['', '', '.'], $_POST['saldo_atual']),
                'data_saldo' => $_POST['data_saldo'],
                'status' => $_POST['status'] ?? 'ativo'
            ];
            
            if ($_POST['id'] ?? '') {
                $db->update('contas_bancarias', $dados, 'id = ?', [$_POST['id']]);
                $_SESSION['success'] = 'Conta bancária atualizada com sucesso!';
            } else {
                $db->insert('contas_bancarias', $dados);
                $_SESSION['success'] = 'Conta bancária criada com sucesso!';
            }
            
            header('Location: configuracoes.php?action=contas_bancarias');
            exit;
        } catch (Exception $e) {
            $_SESSION['error'] = 'Erro ao salvar conta bancária: ' . $e->getMessage();
        }
    }
}

// Busca dados
try {
    if ($action === 'categorias') {
        $categorias = $db->fetchAll("SELECT * FROM categorias_financeiras ORDER BY tipo, nome");
    } elseif ($action === 'contas_bancarias') {
        $contasBancarias = $db->fetchAll("SELECT * FROM contas_bancarias ORDER BY nome");
    }
} catch (Exception $e) {
    $tabelasNaoExistem = true;
}

$pageTitle = 'Configurações Financeiras';
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
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="flex-1 flex flex-col ml-64">
            <?php include 'includes/header.php'; ?>
            
            <main class="flex-1 p-6 overflow-y-auto">
                <div class="max-w-7xl mx-auto">
                    
                    <?php if (isset($tabelasNaoExistem)): ?>
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
                    
                    <div class="mb-8">
                        <h1 class="text-3xl font-bold text-gray-900">Configurações Financeiras</h1>
                        <p class="text-gray-600 mt-2">Configure categorias, contas bancárias e outras configurações</p>
                    </div>

                    <!-- Tabs -->
                    <div class="mb-6">
                        <nav class="flex space-x-8">
                            <a href="?action=categorias" 
                               class="<?php echo $action === 'categorias' ? 'border-green-500 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                                <i class="fas fa-tags mr-2"></i>Categorias
                            </a>
                            <a href="?action=contas_bancarias" 
                               class="<?php echo $action === 'contas_bancarias' ? 'border-green-500 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                                <i class="fas fa-university mr-2"></i>Contas Bancárias
                            </a>
                        </nav>
                    </div>

                    <?php if ($action === 'categorias'): ?>
                    <!-- Categorias -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Lista de Categorias -->
                        <div class="bg-white rounded-lg shadow">
                            <div class="px-6 py-4 border-b border-gray-200">
                                <h3 class="text-lg font-semibold text-gray-900">Categorias Existentes</h3>
                            </div>
                            <div class="p-6">
                                <?php if (empty($categorias ?? [])): ?>
                                <p class="text-gray-500 text-center py-4">Nenhuma categoria encontrada.</p>
                                <?php else: ?>
                                <div class="space-y-3">
                                    <?php foreach ($categorias as $categoria): ?>
                                    <div class="flex items-center justify-between p-3 border rounded-lg">
                                        <div class="flex items-center">
                                            <div class="w-4 h-4 rounded-full mr-3" style="background-color: <?php echo $categoria['cor']; ?>"></div>
                                            <div>
                                                <p class="font-medium text-gray-900"><?php echo htmlspecialchars($categoria['nome']); ?></p>
                                                <p class="text-sm text-gray-500">
                                                    <?php echo ucfirst($categoria['tipo']); ?> • 
                                                    <?php echo ucfirst($categoria['status']); ?>
                                                </p>
                                            </div>
                                        </div>
                                        <button onclick="editarCategoria(<?php echo htmlspecialchars(json_encode($categoria)); ?>)" 
                                                class="text-green-600 hover:text-green-900">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Formulário de Categoria -->
                        <div class="bg-white rounded-lg shadow">
                            <div class="px-6 py-4 border-b border-gray-200">
                                <h3 class="text-lg font-semibold text-gray-900">Nova Categoria</h3>
                            </div>
                            <div class="p-6">
                                <form id="form-categoria" method="POST">
                                    <input type="hidden" name="action" value="salvar_categoria">
                                    <input type="hidden" name="id" id="categoria-id">
                                    
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Nome *</label>
                                            <input type="text" name="nome" id="categoria-nome" required 
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                        </div>
                                        
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo *</label>
                                            <select name="tipo" id="categoria-tipo" required 
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                                <option value="">Selecione</option>
                                                <option value="receita">Receita</option>
                                                <option value="despesa">Despesa</option>
                                            </select>
                                        </div>
                                        
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Cor</label>
                                            <input type="color" name="cor" id="categoria-cor" value="#3498db"
                                                   class="w-full h-10 border border-gray-300 rounded-md">
                                        </div>
                                        
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
                                            <textarea name="descricao" id="categoria-descricao" rows="3"
                                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"></textarea>
                                        </div>
                                        
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                            <select name="status" id="categoria-status"
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                                <option value="ativo">Ativo</option>
                                                <option value="inativo">Inativo</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-6 flex justify-end space-x-3">
                                        <button type="button" onclick="limparFormCategoria()" 
                                                class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400">
                                            Limpar
                                        </button>
                                        <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                                            <i class="fas fa-save mr-2"></i>Salvar
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <?php elseif ($action === 'contas_bancarias'): ?>
                    <!-- Contas Bancárias -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Lista de Contas -->
                        <div class="bg-white rounded-lg shadow">
                            <div class="px-6 py-4 border-b border-gray-200">
                                <h3 class="text-lg font-semibold text-gray-900">Contas Bancárias</h3>
                            </div>
                            <div class="p-6">
                                <?php if (empty($contasBancarias ?? [])): ?>
                                <p class="text-gray-500 text-center py-4">Nenhuma conta bancária encontrada.</p>
                                <?php else: ?>
                                <div class="space-y-3">
                                    <?php foreach ($contasBancarias as $conta): ?>
                                    <div class="flex items-center justify-between p-3 border rounded-lg">
                                        <div>
                                            <p class="font-medium text-gray-900"><?php echo htmlspecialchars($conta['nome']); ?></p>
                                            <p class="text-sm text-gray-500">
                                                <?php echo ucfirst($conta['tipo']); ?> • 
                                                Saldo: R$ <?php echo number_format($conta['saldo_atual'], 2, ',', '.'); ?>
                                            </p>
                                        </div>
                                        <button onclick="editarConta(<?php echo htmlspecialchars(json_encode($conta)); ?>)" 
                                                class="text-green-600 hover:text-green-900">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Formulário de Conta Bancária -->
                        <div class="bg-white rounded-lg shadow">
                            <div class="px-6 py-4 border-b border-gray-200">
                                <h3 class="text-lg font-semibold text-gray-900">Nova Conta Bancária</h3>
                            </div>
                            <div class="p-6">
                                <form id="form-conta" method="POST">
                                    <input type="hidden" name="action" value="salvar_conta_bancaria">
                                    <input type="hidden" name="id" id="conta-id">
                                    
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Nome *</label>
                                            <input type="text" name="nome" id="conta-nome" required 
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                        </div>
                                        
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo *</label>
                                            <select name="tipo" id="conta-tipo" required 
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                                <option value="">Selecione</option>
                                                <option value="corrente">Conta Corrente</option>
                                                <option value="poupanca">Poupança</option>
                                                <option value="investimento">Investimento</option>
                                                <option value="caixa">Caixa</option>
                                            </select>
                                        </div>
                                        
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Banco</label>
                                                <input type="text" name="banco" id="conta-banco"
                                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Agência</label>
                                                <input type="text" name="agencia" id="conta-agencia"
                                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                            </div>
                                        </div>
                                        
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Número da Conta</label>
                                            <input type="text" name="conta" id="conta-numero"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                        </div>
                                        
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Saldo Inicial</label>
                                                <input type="text" name="saldo_inicial" id="conta-saldo-inicial" data-mask="currency"
                                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Saldo Atual</label>
                                                <input type="text" name="saldo_atual" id="conta-saldo-atual" data-mask="currency"
                                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                            </div>
                                        </div>
                                        
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Data do Saldo</label>
                                            <input type="date" name="data_saldo" id="conta-data-saldo" value="<?php echo date('Y-m-d'); ?>"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                        </div>
                                        
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                            <select name="status" id="conta-status"
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                                <option value="ativo">Ativo</option>
                                                <option value="inativo">Inativo</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-6 flex justify-end space-x-3">
                                        <button type="button" onclick="limparFormConta()" 
                                                class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400">
                                            Limpar
                                        </button>
                                        <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                                            <i class="fas fa-save mr-2"></i>Salvar
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <script src="js/financeiro.js"></script>
    <script>
    function editarCategoria(categoria) {
        document.getElementById('categoria-id').value = categoria.id;
        document.getElementById('categoria-nome').value = categoria.nome;
        document.getElementById('categoria-tipo').value = categoria.tipo;
        document.getElementById('categoria-cor').value = categoria.cor;
        document.getElementById('categoria-descricao').value = categoria.descricao || '';
        document.getElementById('categoria-status').value = categoria.status;
    }

    function limparFormCategoria() {
        document.getElementById('form-categoria').reset();
        document.getElementById('categoria-id').value = '';
        document.getElementById('categoria-cor').value = '#3498db';
    }

    function editarConta(conta) {
        document.getElementById('conta-id').value = conta.id;
        document.getElementById('conta-nome').value = conta.nome;
        document.getElementById('conta-tipo').value = conta.tipo;
        document.getElementById('conta-banco').value = conta.banco || '';
        document.getElementById('conta-agencia').value = conta.agencia || '';
        document.getElementById('conta-numero').value = conta.conta || '';
        document.getElementById('conta-saldo-inicial').value = 'R$ ' + parseFloat(conta.saldo_inicial).toLocaleString('pt-BR', {minimumFractionDigits: 2});
        document.getElementById('conta-saldo-atual').value = 'R$ ' + parseFloat(conta.saldo_atual).toLocaleString('pt-BR', {minimumFractionDigits: 2});
        document.getElementById('conta-data-saldo').value = conta.data_saldo;
        document.getElementById('conta-status').value = conta.status;
    }

    function limparFormConta() {
        document.getElementById('form-conta').reset();
        document.getElementById('conta-id').value = '';
        document.getElementById('conta-data-saldo').value = '<?php echo date('Y-m-d'); ?>';
    }
    </script>
</body>
</html>
