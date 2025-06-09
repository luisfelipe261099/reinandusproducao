<?php
require_once '../includes/init.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

Auth::requireLogin();
$userType = Auth::getUserType();
if (!in_array($userType, ['financeiro', 'admin_master'])) {
    $_SESSION['error'] = 'Você não tem permissão para configurar o módulo financeiro.';
    header('Location: ../index.php');
    exit;
}

$db = Database::getInstance();
$mensagens = [];
$erros = [];

if ($_POST['action'] ?? '' === 'configurar') {
    try {
        // 1. Categorias financeiras
        $db->query("CREATE TABLE IF NOT EXISTS categorias_financeiras (
            id int(11) NOT NULL AUTO_INCREMENT,
            nome varchar(100) NOT NULL,
            tipo enum('receita','despesa') NOT NULL,
            cor varchar(7) DEFAULT '#3498db',
            status enum('ativo','inativo') NOT NULL DEFAULT 'ativo',
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $mensagens[] = '✓ Tabela categorias_financeiras criada.';

        // 2. Contas bancárias
        $db->query("CREATE TABLE IF NOT EXISTS contas_bancarias (
            id int(11) NOT NULL AUTO_INCREMENT,
            nome varchar(100) NOT NULL,
            tipo enum('corrente','poupanca','investimento','caixa') NOT NULL DEFAULT 'corrente',
            saldo_atual decimal(10,2) NOT NULL DEFAULT 0.00,
            status enum('ativo','inativo') NOT NULL DEFAULT 'ativo',
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $mensagens[] = '✓ Tabela contas_bancarias criada.';

        // 3. Funcionários
        $db->query("CREATE TABLE IF NOT EXISTS funcionarios (
            id int(11) NOT NULL AUTO_INCREMENT,
            nome varchar(255) NOT NULL,
            cpf varchar(14) NOT NULL,
            cargo varchar(100) NOT NULL,
            salario decimal(10,2) NOT NULL,
            status enum('ativo','inativo') NOT NULL DEFAULT 'ativo',
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $mensagens[] = '✓ Tabela funcionarios criada.';

        // 4. Contas a pagar
        $db->query("CREATE TABLE IF NOT EXISTS contas_pagar (
            id int(11) NOT NULL AUTO_INCREMENT,
            descricao varchar(255) NOT NULL,
            valor decimal(10,2) NOT NULL,
            data_vencimento date NOT NULL,
            data_pagamento date DEFAULT NULL,
            fornecedor_nome varchar(100) DEFAULT NULL,
            status enum('pendente','pago','cancelado') NOT NULL DEFAULT 'pendente',
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $mensagens[] = '✓ Tabela contas_pagar criada.';

        // 5. Contas a receber
        $db->query("CREATE TABLE IF NOT EXISTS contas_receber (
            id int(11) NOT NULL AUTO_INCREMENT,
            descricao varchar(255) NOT NULL,
            valor decimal(10,2) NOT NULL,
            data_vencimento date NOT NULL,
            data_recebimento date DEFAULT NULL,
            cliente_nome varchar(100) DEFAULT NULL,
            cliente_tipo enum('aluno','polo','terceiro') DEFAULT 'terceiro',
            status enum('pendente','recebido','cancelado') NOT NULL DEFAULT 'pendente',
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $mensagens[] = '✓ Tabela contas_receber criada.';

        // 6. Transações financeiras
        $db->query("CREATE TABLE IF NOT EXISTS transacoes_financeiras (
            id int(11) NOT NULL AUTO_INCREMENT,
            tipo enum('receita','despesa','transferencia') NOT NULL,
            descricao varchar(255) NOT NULL,
            valor decimal(10,2) NOT NULL,
            data_transacao date NOT NULL,
            status enum('efetivada','pendente','cancelada') NOT NULL DEFAULT 'efetivada',
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $mensagens[] = '✓ Tabela transacoes_financeiras criada.';

        // 7. Folha de pagamento
        $db->query("CREATE TABLE IF NOT EXISTS folha_pagamento (
            id int(11) NOT NULL AUTO_INCREMENT,
            funcionario_id int(11) NOT NULL,
            mes_referencia date NOT NULL,
            salario_base decimal(10,2) NOT NULL,
            inss decimal(10,2) DEFAULT 0.00,
            irrf decimal(10,2) DEFAULT 0.00,
            salario_liquido decimal(10,2) NOT NULL,
            data_pagamento date DEFAULT NULL,
            status enum('calculada','paga','cancelada') NOT NULL DEFAULT 'calculada',
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $mensagens[] = '✓ Tabela folha_pagamento criada.';

        // 8. Mensalidades de alunos
        $db->query("CREATE TABLE IF NOT EXISTS mensalidades_alunos (
            id int(11) NOT NULL AUTO_INCREMENT,
            aluno_id int(11) NOT NULL,
            valor decimal(10,2) NOT NULL,
            data_vencimento date NOT NULL,
            data_pagamento date DEFAULT NULL,
            mes_referencia date NOT NULL,
            status enum('pendente','pago','cancelado','isento') NOT NULL DEFAULT 'pendente',
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $mensagens[] = '✓ Tabela mensalidades_alunos criada.';

        // 9. Cobrança de polos
        $db->query("CREATE TABLE IF NOT EXISTS cobranca_polos (
            id int(11) NOT NULL AUTO_INCREMENT,
            polo_id int(11) NOT NULL,
            descricao varchar(255) NOT NULL,
            valor decimal(10,2) NOT NULL,
            data_vencimento date NOT NULL,
            data_pagamento date DEFAULT NULL,
            mes_referencia date NOT NULL,
            tipo_cobranca enum('mensalidade','taxa','outros') NOT NULL DEFAULT 'mensalidade',
            status enum('pendente','pago','cancelado') NOT NULL DEFAULT 'pendente',
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $mensagens[] = '✓ Tabela cobranca_polos criada.';

        // Inserir categorias básicas
        $categorias = [
            ['Mensalidades', 'receita', '#10b981'],
            ['Cobrança Polos', 'receita', '#059669'],
            ['Outras Receitas', 'receita', '#34d399'],
            ['Salários', 'despesa', '#ef4444'],
            ['Fornecedores', 'despesa', '#dc2626'],
            ['Despesas Gerais', 'despesa', '#f87171']
        ];

        foreach ($categorias as $categoria) {
            $db->query("INSERT IGNORE INTO categorias_financeiras (nome, tipo, cor) VALUES (?, ?, ?)", $categoria);
        }
        $mensagens[] = '✓ Categorias básicas inseridas.';

        // Criar conta padrão
        $db->query("INSERT IGNORE INTO contas_bancarias (nome, tipo, saldo_atual) VALUES ('Caixa Geral', 'caixa', 0.00)");
        $mensagens[] = '✓ Conta bancária padrão criada.';

        $mensagens[] = '🎉 Configuração concluída com sucesso!';
        
    } catch (Exception $e) {
        $erros[] = 'Erro: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Ultra Simples - Faciência ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center py-12 px-4">
        <div class="max-w-md w-full space-y-8">
            <div class="text-center">
                <div class="mx-auto h-12 w-12 bg-green-600 rounded-full flex items-center justify-center">
                    <i class="fas fa-dollar-sign text-white"></i>
                </div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">Setup Ultra Simples</h2>
                <p class="mt-2 text-center text-sm text-gray-600">Configuração garantida para qualquer servidor</p>
            </div>

            <?php if (!empty($mensagens)): ?>
            <div class="bg-green-50 border border-green-200 rounded-lg p-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle text-green-400 text-xl"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-green-800">Sucesso!</h3>
                        <div class="mt-2 text-sm text-green-700">
                            <ul class="list-disc list-inside space-y-1">
                                <?php foreach ($mensagens as $mensagem): ?>
                                <li><?php echo htmlspecialchars($mensagem); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div class="mt-4">
                            <a href="index.php" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                                <i class="fas fa-chart-line mr-2"></i>Ir para Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($erros)): ?>
            <div class="bg-red-50 border border-red-200 rounded-lg p-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-red-400 text-xl"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Erro</h3>
                        <div class="mt-2 text-sm text-red-700">
                            <ul class="list-disc list-inside space-y-1">
                                <?php foreach ($erros as $erro): ?>
                                <li><?php echo htmlspecialchars($erro); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (empty($mensagens)): ?>
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Configuração Ultra Simples</h3>
                <p class="text-sm text-gray-600 mb-6">Esta versão cria apenas as estruturas essenciais sem complexidades.</p>
                
                <form method="POST">
                    <input type="hidden" name="action" value="configurar">
                    <button type="submit" class="w-full bg-green-600 text-white py-2 px-4 rounded hover:bg-green-700">
                        <i class="fas fa-rocket mr-2"></i>Configurar Agora
                    </button>
                </form>
            </div>

            <div class="text-center">
                <a href="../index.php" class="text-green-600 hover:text-green-500 text-sm">
                    <i class="fas fa-arrow-left mr-1"></i>Voltar
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
