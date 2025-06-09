<?php
/**
 * Formulário para registrar pagamentos de funcionários
 */

// Inclui os arquivos necessários
require_once __DIR__ . '/../../includes/init.php';

// Verifica se o usuário está logado
exigirLogin();

// Verifica se o usuário tem permissão para acessar esta página
if (!Auth::hasPermission('financeiro', 'editar')) {
    setMensagem('erro', 'Você não tem permissão para acessar esta página.');
    redirect('index.php');
    exit;
}

// Inicializa a conexão com o banco de dados
$db = Database::getInstance();

// Verifica se é edição ou novo pagamento
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$funcionario_id = isset($_GET['funcionario_id']) ? (int)$_GET['funcionario_id'] : 0;
$pagamento = [];

if ($id > 0) {
    // Busca os dados do pagamento
    $pagamento = $db->fetchOne("SELECT * FROM pagamentos WHERE id = ?", [$id]);
    
    if (!$pagamento) {
        setMensagem('erro', 'Pagamento não encontrado.');
        redirect('pagamentos.php');
        exit;
    }
    
    $funcionario_id = $pagamento['funcionario_id'];
    $titulo_pagina = 'Editar Pagamento';
} else {
    $titulo_pagina = 'Novo Pagamento';
    
    // Inicializa com valores padrão
    $pagamento = [
        'funcionario_id' => $funcionario_id,
        'valor' => '',
        'data_pagamento' => date('Y-m-d'),
        'status' => 'pendente'
    ];
}

// Busca os dados do funcionário
if ($funcionario_id > 0) {
    $funcionario = $db->fetchOne("SELECT * FROM funcionarios WHERE id = ?", [$funcionario_id]);
    
    if (!$funcionario) {
        setMensagem('erro', 'Funcionário não encontrado.');
        redirect('index.php');
        exit;
    }
} else {
    // Lista de funcionários para o select
    $funcionarios = $db->fetchAll("SELECT id, nome FROM funcionarios WHERE status = 'ativo' ORDER BY nome");
}

// Processa o formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Captura os dados do formulário
    $pagamento = [
        'funcionario_id' => $_POST['funcionario_id'] ?? $funcionario_id,
        'valor' => str_replace(',', '.', $_POST['valor'] ?? ''),
        'data_pagamento' => $_POST['data_pagamento'] ?? '',
        'status' => $_POST['status'] ?? 'pendente'
    ];
    
    // Validação básica
    $erros = [];
    
    if (empty($pagamento['funcionario_id'])) {
        $erros[] = 'O funcionário é obrigatório.';
    }
    
    if (empty($pagamento['valor']) || !is_numeric($pagamento['valor'])) {
        $erros[] = 'O valor é obrigatório e deve ser um valor numérico.';
    }
    
    if (empty($pagamento['data_pagamento'])) {
        $erros[] = 'A data de pagamento é obrigatória.';
    }
    
    // Se não houver erros, salva os dados
    if (empty($erros)) {
        try {
            if ($id > 0) {
                // Atualiza o pagamento existente
                $result = $db->update('pagamentos', $pagamento, ['id' => $id]);
                $mensagem = 'Pagamento atualizado com sucesso.';
            } else {
                // Insere um novo pagamento
                $result = $db->insert('pagamentos', $pagamento);
                $id = $result;
                $mensagem = 'Pagamento registrado com sucesso.';
            }
            
            if ($result) {
                // Cria ou atualiza a conta a pagar correspondente
                $funcionario = $db->fetchOne("SELECT * FROM funcionarios WHERE id = ?", [$pagamento['funcionario_id']]);
                
                // Verifica se já existe uma conta a pagar para este pagamento
                $conta_pagar_rh = $db->fetchOne("SELECT * FROM contas_pagar_rh WHERE pagamento_id = ?", [$id]);
                
                if ($conta_pagar_rh) {
                    // Atualiza a conta a pagar existente
                    $conta_pagar = [
                        'descricao' => 'Pagamento de salário - ' . $funcionario['nome'],
                        'valor' => $pagamento['valor'],
                        'data_vencimento' => $pagamento['data_pagamento'],
                        'data_pagamento' => $pagamento['status'] == 'pago' ? $pagamento['data_pagamento'] : null,
                        'status' => $pagamento['status'],
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                    
                    $db->update('contas_pagar', $conta_pagar, ['id' => $conta_pagar_rh['conta_pagar_id']]);
                } else {
                    // Cria uma nova conta a pagar
                    $conta_pagar = [
                        'descricao' => 'Pagamento de salário - ' . $funcionario['nome'],
                        'valor' => $pagamento['valor'],
                        'data_vencimento' => $pagamento['data_pagamento'],
                        'data_pagamento' => $pagamento['status'] == 'pago' ? $pagamento['data_pagamento'] : null,
                        'categoria' => 'folha_pagamento',
                        'fornecedor' => $funcionario['nome'],
                        'status' => $pagamento['status'],
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                    
                    $conta_pagar_id = $db->insert('contas_pagar', $conta_pagar);
                    
                    if ($conta_pagar_id) {
                        // Cria a relação entre o pagamento e a conta a pagar
                        $db->insert('contas_pagar_rh', [
                            'pagamento_id' => $id,
                            'conta_pagar_id' => $conta_pagar_id
                        ]);
                    }
                }
                
                setMensagem('sucesso', $mensagem);
                redirect('pagamentos.php');
                exit;
            } else {
                $erros[] = 'Erro ao salvar os dados. Tente novamente.';
            }
        } catch (Exception $e) {
            $erros[] = 'Erro ao salvar os dados: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faciência ERP - <?php echo $titulo_pagina; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../../css/styles.css">
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <?php include '../includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <?php include '../includes/header.php'; ?>

            <!-- Main -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
                <div class="container mx-auto">
                    <h1 class="text-3xl font-bold text-gray-800 mb-6"><?php echo $titulo_pagina; ?></h1>

                    <!-- Mensagens de erro -->
                    <?php if (!empty($erros)): ?>
                        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
                            <ul class="list-disc list-inside">
                                <?php foreach ($erros as $erro): ?>
                                    <li><?php echo $erro; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <!-- Formulário -->
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <form method="post" class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Funcionário -->
                                <?php if ($funcionario_id > 0): ?>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Funcionário</label>
                                        <div class="px-3 py-2 border border-gray-300 rounded-md bg-gray-100">
                                            <?php echo $funcionario['nome']; ?>
                                            <input type="hidden" name="funcionario_id" value="<?php echo $funcionario_id; ?>">
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div>
                                        <label for="funcionario_id" class="block text-sm font-medium text-gray-700 mb-1">Funcionário</label>
                                        <select name="funcionario_id" id="funcionario_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500" required>
                                            <option value="">Selecione um funcionário</option>
                                            <?php foreach ($funcionarios as $func): ?>
                                                <option value="<?php echo $func['id']; ?>" <?php echo $pagamento['funcionario_id'] == $func['id'] ? 'selected' : ''; ?>>
                                                    <?php echo $func['nome']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                <?php endif; ?>

                                <!-- Valor -->
                                <div>
                                    <label for="valor" class="block text-sm font-medium text-gray-700 mb-1">Valor</label>
                                    <input type="text" name="valor" id="valor" value="<?php echo number_format($pagamento['valor'], 2, ',', '.'); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500" required>
                                </div>

                                <!-- Data de Pagamento -->
                                <div>
                                    <label for="data_pagamento" class="block text-sm font-medium text-gray-700 mb-1">Data de Pagamento</label>
                                    <input type="date" name="data_pagamento" id="data_pagamento" value="<?php echo $pagamento['data_pagamento']; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500" required>
                                </div>

                                <!-- Status -->
                                <div>
                                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                    <select name="status" id="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500">
                                        <option value="pendente" <?php echo $pagamento['status'] === 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                                        <option value="pago" <?php echo $pagamento['status'] === 'pago' ? 'selected' : ''; ?>>Pago</option>
                                        <option value="cancelado" <?php echo $pagamento['status'] === 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Botões -->
                            <div class="mt-6 flex justify-end">
                                <a href="<?php echo $funcionario_id > 0 ? 'index.php' : 'pagamentos.php'; ?>" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded mr-2">
                                    Cancelar
                                </a>
                                <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded">
                                    Salvar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>

            <!-- Footer -->
            <?php include '../includes/footer.php'; ?>
        </div>
    </div>

    <script>
        // Máscara para valor
        document.getElementById('valor').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = (parseInt(value) / 100).toFixed(2).replace('.', ',');
            e.target.value = value;
        });
    </script>
</body>
</html>
