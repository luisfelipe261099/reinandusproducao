<?php
/**
 * Página para gerenciar contas bancárias
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

// Verifica a ação a ser executada
$action = isset($_GET['action']) ? $_GET['action'] : 'listar';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Processa as ações
switch ($action) {
    case 'novo':
    case 'editar':
        // Verifica se o usuário tem permissão para editar
        if (!Auth::hasPermission('financeiro', 'editar')) {
            setMensagem('erro', 'Você não tem permissão para editar contas bancárias.');
            redirect('contas_bancarias.php');
            exit;
        }
        
        $conta = [
            'nome' => '',
            'banco' => '',
            'agencia' => '',
            'conta' => '',
            'tipo' => 'corrente',
            'saldo_inicial' => '0.00',
            'saldo_atual' => '0.00',
            'data_saldo' => date('Y-m-d'),
            'status' => 'ativo'
        ];
        
        if ($action === 'editar' && $id > 0) {
            $conta = $db->fetchOne("SELECT * FROM contas_bancarias WHERE id = ?", [$id]);
            
            if (!$conta) {
                setMensagem('erro', 'Conta bancária não encontrada.');
                redirect('contas_bancarias.php');
                exit;
            }
            
            $titulo_pagina = 'Editar Conta Bancária';
        } else {
            $titulo_pagina = 'Nova Conta Bancária';
        }
        
        // Processa o formulário
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Captura os dados do formulário
            $conta = [
                'nome' => $_POST['nome'] ?? '',
                'banco' => $_POST['banco'] ?? '',
                'agencia' => $_POST['agencia'] ?? '',
                'conta' => $_POST['conta'] ?? '',
                'tipo' => $_POST['tipo'] ?? 'corrente',
                'saldo_inicial' => str_replace(',', '.', $_POST['saldo_inicial'] ?? '0'),
                'saldo_atual' => str_replace(',', '.', $_POST['saldo_atual'] ?? '0'),
                'data_saldo' => $_POST['data_saldo'] ?? date('Y-m-d'),
                'status' => $_POST['status'] ?? 'ativo',
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // Validação básica
            $erros = [];
            
            if (empty($conta['nome'])) {
                $erros[] = 'O nome da conta é obrigatório.';
            }
            
            if (!is_numeric($conta['saldo_inicial'])) {
                $erros[] = 'O saldo inicial deve ser um valor numérico.';
            }
            
            if (!is_numeric($conta['saldo_atual'])) {
                $erros[] = 'O saldo atual deve ser um valor numérico.';
            }
            
            if (empty($conta['data_saldo'])) {
                $erros[] = 'A data do saldo é obrigatória.';
            }
            
            // Se não houver erros, salva os dados
            if (empty($erros)) {
                try {
                    if ($id > 0) {
                        // Atualiza a conta existente
                        $result = $db->update('contas_bancarias', $conta, ['id' => $id]);
                        $mensagem = 'Conta bancária atualizada com sucesso.';
                    } else {
                        // Adiciona a data de criação para novas contas
                        $conta['created_at'] = date('Y-m-d H:i:s');
                        
                        // Insere uma nova conta
                        $result = $db->insert('contas_bancarias', $conta);
                        $id = $result;
                        $mensagem = 'Conta bancária cadastrada com sucesso.';
                    }
                    
                    if ($result) {
                        setMensagem('sucesso', $mensagem);
                        redirect('contas_bancarias.php');
                        exit;
                    } else {
                        $erros[] = 'Erro ao salvar os dados. Tente novamente.';
                    }
                } catch (Exception $e) {
                    $erros[] = 'Erro ao salvar os dados: ' . $e->getMessage();
                }
            }
        }
        break;
        
    case 'ativar':
    case 'desativar':
        // Verifica se o usuário tem permissão para editar
        if (!Auth::hasPermission('financeiro', 'editar')) {
            setMensagem('erro', 'Você não tem permissão para alterar contas bancárias.');
            redirect('contas_bancarias.php');
            exit;
        }
        
        if ($id <= 0) {
            setMensagem('erro', 'ID da conta bancária não informado.');
            redirect('contas_bancarias.php');
            exit;
        }
        
        $status = ($action === 'ativar') ? 'ativo' : 'inativo';
        
        try {
            // Atualiza o status da conta
            $result = $db->update('contas_bancarias', 
                                 ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')], 
                                 ['id' => $id]);
            
            if ($result) {
                $mensagem = ($action === 'ativar') ? 'Conta bancária ativada com sucesso.' : 'Conta bancária desativada com sucesso.';
                setMensagem('sucesso', $mensagem);
            } else {
                setMensagem('erro', 'Erro ao atualizar a conta bancária.');
            }
        } catch (Exception $e) {
            setMensagem('erro', 'Erro ao atualizar a conta bancária: ' . $e->getMessage());
        }
        
        redirect('contas_bancarias.php');
        break;
        
    case 'excluir':
        // Verifica se o usuário tem permissão para excluir
        if (!Auth::hasPermission('financeiro', 'excluir')) {
            setMensagem('erro', 'Você não tem permissão para excluir contas bancárias.');
            redirect('contas_bancarias.php');
            exit;
        }
        
        if ($id <= 0) {
            setMensagem('erro', 'ID da conta bancária não informado.');
            redirect('contas_bancarias.php');
            exit;
        }
        
        // Verifica se a conta tem transações associadas
        $transacoes = $db->fetchOne("SELECT COUNT(*) as total FROM transacoes WHERE conta_id = ?", [$id]);
        
        if ($transacoes && $transacoes['total'] > 0) {
            setMensagem('erro', 'Não é possível excluir a conta bancária pois existem transações associadas a ela.');
            redirect('contas_bancarias.php');
            exit;
        }
        
        try {
            // Exclui a conta
            $result = $db->delete('contas_bancarias', ['id' => $id]);
            
            if ($result) {
                setMensagem('sucesso', 'Conta bancária excluída com sucesso.');
            } else {
                setMensagem('erro', 'Erro ao excluir a conta bancária.');
            }
        } catch (Exception $e) {
            setMensagem('erro', 'Erro ao excluir a conta bancária: ' . $e->getMessage());
        }
        
        redirect('contas_bancarias.php');
        break;
        
    case 'listar':
    default:
        // Busca todas as contas bancárias
        $contas = $db->fetchAll("SELECT * FROM contas_bancarias ORDER BY status DESC, nome ASC");
        
        // Calcula o saldo total
        $saldo_total = 0;
        foreach ($contas as $conta) {
            if ($conta['status'] === 'ativo') {
                $saldo_total += $conta['saldo_atual'];
            }
        }
        
        $titulo_pagina = 'Contas Bancárias';
        break;
}
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

                    <?php if ($action === 'listar'): ?>
                        <!-- Ações -->
                        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                            <div class="flex flex-wrap items-center justify-between">
                                <div>
                                    <h2 class="text-xl font-semibold text-gray-800 mb-2">Gerenciar Contas Bancárias</h2>
                                    <p class="text-gray-600">Gerencie suas contas bancárias e acompanhe os saldos.</p>
                                </div>
                                <div class="mt-4 md:mt-0">
                                    <a href="contas_bancarias.php?action=novo" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                        <i class="fas fa-plus mr-2"></i> Nova Conta Bancária
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Lista de Contas Bancárias -->
                        <div class="bg-white rounded-lg shadow-md overflow-hidden">
                            <div class="p-6 border-b">
                                <div class="flex justify-between items-center">
                                    <h2 class="text-xl font-bold text-gray-800">Contas Cadastradas</h2>
                                    <div class="text-lg font-bold text-gray-800">
                                        Saldo Total: R$ <?php echo number_format($saldo_total, 2, ',', '.'); ?>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if (empty($contas)): ?>
                                <div class="p-6 text-center text-gray-500">
                                    <p>Nenhuma conta bancária encontrada.</p>
                                    <p class="mt-2">
                                        <a href="contas_bancarias.php?action=novo" class="text-blue-600 hover:text-blue-800">
                                            <i class="fas fa-plus mr-1"></i> Cadastrar Conta Bancária
                                        </a>
                                    </p>
                                </div>
                            <?php else: ?>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full bg-white">
                                        <thead>
                                            <tr class="bg-gray-100 text-gray-600 uppercase text-sm leading-normal">
                                                <th class="py-3 px-6 text-left">Nome</th>
                                                <th class="py-3 px-6 text-left">Banco</th>
                                                <th class="py-3 px-6 text-left">Agência/Conta</th>
                                                <th class="py-3 px-6 text-left">Tipo</th>
                                                <th class="py-3 px-6 text-right">Saldo Atual</th>
                                                <th class="py-3 px-6 text-center">Status</th>
                                                <th class="py-3 px-6 text-center">Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody class="text-gray-600 text-sm">
                                            <?php foreach ($contas as $conta): ?>
                                                <tr class="border-b border-gray-200 hover:bg-gray-50">
                                                    <td class="py-3 px-6"><?php echo $conta['nome']; ?></td>
                                                    <td class="py-3 px-6"><?php echo $conta['banco']; ?></td>
                                                    <td class="py-3 px-6">
                                                        <?php if ($conta['agencia'] && $conta['conta']): ?>
                                                            <?php echo $conta['agencia']; ?> / <?php echo $conta['conta']; ?>
                                                        <?php elseif ($conta['agencia']): ?>
                                                            <?php echo $conta['agencia']; ?>
                                                        <?php elseif ($conta['conta']): ?>
                                                            <?php echo $conta['conta']; ?>
                                                        <?php else: ?>
                                                            -
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="py-3 px-6">
                                                        <?php 
                                                        $tipos = [
                                                            'corrente' => 'Conta Corrente',
                                                            'poupanca' => 'Poupança',
                                                            'investimento' => 'Investimento',
                                                            'caixa' => 'Caixa',
                                                            'cartao' => 'Cartão de Crédito'
                                                        ];
                                                        echo $tipos[$conta['tipo']] ?? $conta['tipo']; 
                                                        ?>
                                                    </td>
                                                    <td class="py-3 px-6 text-right">
                                                        <span class="<?php echo $conta['saldo_atual'] < 0 ? 'text-red-600' : 'text-green-600'; ?> font-medium">
                                                            R$ <?php echo number_format($conta['saldo_atual'], 2, ',', '.'); ?>
                                                        </span>
                                                    </td>
                                                    <td class="py-3 px-6 text-center">
                                                        <?php if ($conta['status'] === 'ativo'): ?>
                                                            <span class="bg-green-100 text-green-800 py-1 px-3 rounded-full text-xs">Ativo</span>
                                                        <?php else: ?>
                                                            <span class="bg-gray-100 text-gray-800 py-1 px-3 rounded-full text-xs">Inativo</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="py-3 px-6 text-center">
                                                        <div class="flex item-center justify-center">
                                                            <a href="contas_bancarias.php?action=editar&id=<?php echo $conta['id']; ?>" class="text-blue-600 hover:text-blue-900 mx-1" title="Editar">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <?php if ($conta['status'] === 'ativo'): ?>
                                                                <a href="contas_bancarias.php?action=desativar&id=<?php echo $conta['id']; ?>" class="text-yellow-600 hover:text-yellow-900 mx-1" title="Desativar" onclick="return confirm('Tem certeza que deseja desativar esta conta bancária?');">
                                                                    <i class="fas fa-pause-circle"></i>
                                                                </a>
                                                            <?php else: ?>
                                                                <a href="contas_bancarias.php?action=ativar&id=<?php echo $conta['id']; ?>" class="text-green-600 hover:text-green-900 mx-1" title="Ativar">
                                                                    <i class="fas fa-play-circle"></i>
                                                                </a>
                                                            <?php endif; ?>
                                                            <a href="contas_bancarias.php?action=excluir&id=<?php echo $conta['id']; ?>" class="text-red-600 hover:text-red-900 mx-1" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir esta conta bancária? Esta ação não poderá ser desfeita.');">
                                                                <i class="fas fa-trash"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <!-- Formulário -->
                        <div class="bg-white rounded-lg shadow-md overflow-hidden">
                            <form method="post" class="p-6">
                                <div class="mb-6">
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">Informações da Conta</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <!-- Nome -->
                                        <div>
                                            <label for="nome" class="block text-sm font-medium text-gray-700 mb-1">Nome da Conta</label>
                                            <input type="text" name="nome" id="nome" value="<?php echo $conta['nome']; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500" required>
                                        </div>

                                        <!-- Banco -->
                                        <div>
                                            <label for="banco" class="block text-sm font-medium text-gray-700 mb-1">Banco</label>
                                            <input type="text" name="banco" id="banco" value="<?php echo $conta['banco']; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500">
                                        </div>

                                        <!-- Agência -->
                                        <div>
                                            <label for="agencia" class="block text-sm font-medium text-gray-700 mb-1">Agência</label>
                                            <input type="text" name="agencia" id="agencia" value="<?php echo $conta['agencia']; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500">
                                        </div>

                                        <!-- Conta -->
                                        <div>
                                            <label for="conta" class="block text-sm font-medium text-gray-700 mb-1">Conta</label>
                                            <input type="text" name="conta" id="conta" value="<?php echo $conta['conta']; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500">
                                        </div>

                                        <!-- Tipo -->
                                        <div>
                                            <label for="tipo" class="block text-sm font-medium text-gray-700 mb-1">Tipo de Conta</label>
                                            <select name="tipo" id="tipo" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500">
                                                <option value="corrente" <?php echo $conta['tipo'] === 'corrente' ? 'selected' : ''; ?>>Conta Corrente</option>
                                                <option value="poupanca" <?php echo $conta['tipo'] === 'poupanca' ? 'selected' : ''; ?>>Poupança</option>
                                                <option value="investimento" <?php echo $conta['tipo'] === 'investimento' ? 'selected' : ''; ?>>Investimento</option>
                                                <option value="caixa" <?php echo $conta['tipo'] === 'caixa' ? 'selected' : ''; ?>>Caixa</option>
                                                <option value="cartao" <?php echo $conta['tipo'] === 'cartao' ? 'selected' : ''; ?>>Cartão de Crédito</option>
                                            </select>
                                        </div>

                                        <!-- Status -->
                                        <div>
                                            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                            <select name="status" id="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500">
                                                <option value="ativo" <?php echo $conta['status'] === 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                                                <option value="inativo" <?php echo $conta['status'] === 'inativo' ? 'selected' : ''; ?>>Inativo</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-6">
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">Saldo</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                        <!-- Saldo Inicial -->
                                        <div>
                                            <label for="saldo_inicial" class="block text-sm font-medium text-gray-700 mb-1">Saldo Inicial</label>
                                            <input type="text" name="saldo_inicial" id="saldo_inicial" value="<?php echo number_format($conta['saldo_inicial'], 2, ',', '.'); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500" required>
                                        </div>

                                        <!-- Saldo Atual -->
                                        <div>
                                            <label for="saldo_atual" class="block text-sm font-medium text-gray-700 mb-1">Saldo Atual</label>
                                            <input type="text" name="saldo_atual" id="saldo_atual" value="<?php echo number_format($conta['saldo_atual'], 2, ',', '.'); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500" required>
                                        </div>

                                        <!-- Data do Saldo -->
                                        <div>
                                            <label for="data_saldo" class="block text-sm font-medium text-gray-700 mb-1">Data do Saldo</label>
                                            <input type="date" name="data_saldo" id="data_saldo" value="<?php echo $conta['data_saldo']; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500" required>
                                        </div>
                                    </div>
                                </div>

                                <!-- Botões -->
                                <div class="mt-6 flex justify-end">
                                    <a href="contas_bancarias.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded mr-2">
                                        Cancelar
                                    </a>
                                    <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded">
                                        Salvar
                                    </button>
                                </div>
                            </form>
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
            sidebar.classList.toggle('hidden');
        });

        // Máscara para valores monetários
        document.addEventListener('DOMContentLoaded', function() {
            const camposMonetarios = document.querySelectorAll('#saldo_inicial, #saldo_atual');
            
            camposMonetarios.forEach(function(campo) {
                campo.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    value = (parseInt(value) / 100).toFixed(2).replace('.', ',');
                    e.target.value = value;
                });
            });
        });
    </script>
</body>
</html>
