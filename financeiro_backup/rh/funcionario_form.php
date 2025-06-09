<?php
/**
 * Formulário para cadastro e edição de funcionários
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

// Verifica se é edição ou novo cadastro
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$funcionario = [];

if ($id > 0) {
    // Busca os dados do funcionário
    $funcionario = $db->fetchOne("SELECT * FROM funcionarios WHERE id = ?", [$id]);
    
    if (!$funcionario) {
        setMensagem('erro', 'Funcionário não encontrado.');
        redirect('index.php');
        exit;
    }
    
    $titulo_pagina = 'Editar Funcionário';
} else {
    $titulo_pagina = 'Novo Funcionário';
    
    // Inicializa com valores padrão
    $funcionario = [
        'nome' => '',
        'cpf' => '',
        'cargo' => '',
        'departamento' => '',
        'salario' => '',
        'data_admissao' => date('Y-m-d'),
        'status' => 'ativo'
    ];
}

// Processa o formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Captura os dados do formulário
    $funcionario = [
        'nome' => $_POST['nome'] ?? '',
        'cpf' => $_POST['cpf'] ?? '',
        'cargo' => $_POST['cargo'] ?? '',
        'departamento' => $_POST['departamento'] ?? '',
        'salario' => str_replace(',', '.', $_POST['salario'] ?? ''),
        'data_admissao' => $_POST['data_admissao'] ?? '',
        'status' => $_POST['status'] ?? 'ativo'
    ];
    
    // Validação básica
    $erros = [];
    
    if (empty($funcionario['nome'])) {
        $erros[] = 'O nome é obrigatório.';
    }
    
    if (empty($funcionario['cpf'])) {
        $erros[] = 'O CPF é obrigatório.';
    }
    
    if (empty($funcionario['cargo'])) {
        $erros[] = 'O cargo é obrigatório.';
    }
    
    if (empty($funcionario['salario']) || !is_numeric($funcionario['salario'])) {
        $erros[] = 'O salário é obrigatório e deve ser um valor numérico.';
    }
    
    if (empty($funcionario['data_admissao'])) {
        $erros[] = 'A data de admissão é obrigatória.';
    }
    
    // Se não houver erros, salva os dados
    if (empty($erros)) {
        try {
            if ($id > 0) {
                // Atualiza o funcionário existente
                $result = $db->update('funcionarios', $funcionario, ['id' => $id]);
                $mensagem = 'Funcionário atualizado com sucesso.';
            } else {
                // Insere um novo funcionário
                $result = $db->insert('funcionarios', $funcionario);
                $id = $result;
                $mensagem = 'Funcionário cadastrado com sucesso.';
            }
            
            if ($result) {
                setMensagem('sucesso', $mensagem);
                redirect('index.php');
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
                                <!-- Nome -->
                                <div>
                                    <label for="nome" class="block text-sm font-medium text-gray-700 mb-1">Nome</label>
                                    <input type="text" name="nome" id="nome" value="<?php echo $funcionario['nome']; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500" required>
                                </div>

                                <!-- CPF -->
                                <div>
                                    <label for="cpf" class="block text-sm font-medium text-gray-700 mb-1">CPF</label>
                                    <input type="text" name="cpf" id="cpf" value="<?php echo $funcionario['cpf']; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500" required>
                                </div>

                                <!-- Cargo -->
                                <div>
                                    <label for="cargo" class="block text-sm font-medium text-gray-700 mb-1">Cargo</label>
                                    <input type="text" name="cargo" id="cargo" value="<?php echo $funcionario['cargo']; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500" required>
                                </div>

                                <!-- Departamento -->
                                <div>
                                    <label for="departamento" class="block text-sm font-medium text-gray-700 mb-1">Departamento</label>
                                    <input type="text" name="departamento" id="departamento" value="<?php echo $funcionario['departamento']; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500">
                                </div>

                                <!-- Salário -->
                                <div>
                                    <label for="salario" class="block text-sm font-medium text-gray-700 mb-1">Salário</label>
                                    <input type="text" name="salario" id="salario" value="<?php echo number_format($funcionario['salario'], 2, ',', '.'); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500" required>
                                </div>

                                <!-- Data de Admissão -->
                                <div>
                                    <label for="data_admissao" class="block text-sm font-medium text-gray-700 mb-1">Data de Admissão</label>
                                    <input type="date" name="data_admissao" id="data_admissao" value="<?php echo $funcionario['data_admissao']; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500" required>
                                </div>

                                <!-- Status -->
                                <div>
                                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                    <select name="status" id="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500">
                                        <option value="ativo" <?php echo $funcionario['status'] === 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                                        <option value="inativo" <?php echo $funcionario['status'] === 'inativo' ? 'selected' : ''; ?>>Inativo</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Botões -->
                            <div class="mt-6 flex justify-end">
                                <a href="index.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded mr-2">
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
        // Máscara para CPF
        document.getElementById('cpf').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) value = value.slice(0, 11);
            
            if (value.length > 9) {
                value = value.replace(/^(\d{3})(\d{3})(\d{3})(\d{2}).*/, '$1.$2.$3-$4');
            } else if (value.length > 6) {
                value = value.replace(/^(\d{3})(\d{3})(\d{0,3}).*/, '$1.$2.$3');
            } else if (value.length > 3) {
                value = value.replace(/^(\d{3})(\d{0,3}).*/, '$1.$2');
            }
            
            e.target.value = value;
        });

        // Máscara para salário
        document.getElementById('salario').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = (parseInt(value) / 100).toFixed(2).replace('.', ',');
            e.target.value = value;
        });
    </script>
</body>
</html>
