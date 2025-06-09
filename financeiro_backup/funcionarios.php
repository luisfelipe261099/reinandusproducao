<?php
/**
 * Página de listagem de funcionários
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

// Busca estatísticas básicas
$total_funcionarios = $db->fetchOne("SELECT COUNT(*) as total FROM funcionarios WHERE status = 'ativo'")['total'] ?? 0;
$total_folha = $db->fetchOne("SELECT SUM(salario) as total FROM funcionarios WHERE status = 'ativo'")['total'] ?? 0;

// Busca os funcionários ativos
$funcionarios = $db->fetchAll("SELECT * FROM funcionarios WHERE status = 'ativo' ORDER BY nome");

// Define o título da página
$titulo_pagina = 'Funcionários';
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

                    <!-- Dashboard Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <!-- Total Funcionários -->
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <div class="flex justify-between items-center">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-600">Funcionários Ativos</h3>
                                    <p class="text-3xl font-bold text-gray-800"><?php echo $total_funcionarios; ?></p>
                                </div>
                                <div class="text-4xl text-purple-500">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Total Folha de Pagamento -->
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <div class="flex justify-between items-center">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-600">Folha de Pagamento</h3>
                                    <p class="text-3xl font-bold text-gray-800">R$ <?php echo number_format($total_folha, 2, ',', '.'); ?></p>
                                </div>
                                <div class="text-4xl text-purple-500">
                                    <i class="fas fa-money-check-alt"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Lista de Funcionários -->
                    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-8">
                        <div class="flex justify-between items-center p-6 border-b">
                            <h2 class="text-xl font-bold text-gray-800">Funcionários</h2>
                            <a href="funcionario_form.php" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded">
                                <i class="fas fa-plus mr-2"></i> Novo Funcionário
                            </a>
                        </div>

                        <?php if (empty($funcionarios)): ?>
                            <div class="p-6 text-center text-gray-500">
                                <p>Nenhum funcionário cadastrado.</p>
                            </div>
                        <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full bg-white">
                                    <thead>
                                        <tr class="bg-gray-100 text-gray-600 uppercase text-sm leading-normal">
                                            <th class="py-3 px-6 text-left">Nome</th>
                                            <th class="py-3 px-6 text-left">CPF</th>
                                            <th class="py-3 px-6 text-left">Cargo</th>
                                            <th class="py-3 px-6 text-left">Departamento</th>
                                            <th class="py-3 px-6 text-right">Salário</th>
                                            <th class="py-3 px-6 text-center">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody class="text-gray-600 text-sm">
                                        <?php foreach ($funcionarios as $funcionario): ?>
                                            <tr class="border-b border-gray-200 hover:bg-gray-50">
                                                <td class="py-3 px-6"><?php echo $funcionario['nome']; ?></td>
                                                <td class="py-3 px-6"><?php echo $funcionario['cpf']; ?></td>
                                                <td class="py-3 px-6"><?php echo $funcionario['cargo']; ?></td>
                                                <td class="py-3 px-6"><?php echo $funcionario['departamento']; ?></td>
                                                <td class="py-3 px-6 text-right">R$ <?php echo number_format($funcionario['salario'], 2, ',', '.'); ?></td>
                                                <td class="py-3 px-6 text-center">
                                                    <div class="flex item-center justify-center">
                                                        <a href="funcionario_form.php?id=<?php echo $funcionario['id']; ?>" class="text-blue-600 hover:text-blue-900 mx-1" title="Editar">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="pagamento_form.php?funcionario_id=<?php echo $funcionario['id']; ?>" class="text-green-600 hover:text-green-900 mx-1" title="Registrar Pagamento">
                                                            <i class="fas fa-money-bill-wave"></i>
                                                        </a>
                                                        <a href="funcionario_delete.php?id=<?php echo $funcionario['id']; ?>" class="text-red-600 hover:text-red-900 mx-1" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir este funcionário?');">
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

                    <!-- Ações Rápidas -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <a href="funcionario_form.php" class="bg-white rounded-lg shadow-md p-6 flex items-center justify-center flex-col text-center hover:bg-purple-50">
                            <i class="fas fa-user-plus text-4xl text-purple-600 mb-2"></i>
                            <span class="text-gray-800 font-medium">Novo Funcionário</span>
                        </a>
                        <a href="pagamentos.php" class="bg-white rounded-lg shadow-md p-6 flex items-center justify-center flex-col text-center hover:bg-purple-50">
                            <i class="fas fa-money-bill-wave text-4xl text-purple-600 mb-2"></i>
                            <span class="text-gray-800 font-medium">Pagamentos</span>
                        </a>
                        <a href="index.php" class="bg-white rounded-lg shadow-md p-6 flex items-center justify-center flex-col text-center hover:bg-purple-50">
                            <i class="fas fa-arrow-left text-4xl text-purple-600 mb-2"></i>
                            <span class="text-gray-800 font-medium">Voltar para Dashboard</span>
                        </a>
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
