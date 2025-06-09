<?php
/**
 * Setup do Módulo Financeiro
 * Cria as tabelas necessárias para o funcionamento do módulo
 */

require_once '../includes/init.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

// Verifica autenticação
Auth::requireLogin();

// Verifica se o usuário tem permissão
$userType = Auth::getUserType();
if (!in_array($userType, ['financeiro', 'admin_master'])) {
    $_SESSION['error'] = 'Você não tem permissão para configurar o módulo financeiro.';
    header('Location: ../index.php');
    exit;
}

$db = Database::getInstance();
$mensagens = [];
$erros = [];

// Processa a configuração se foi solicitada
if ($_POST['action'] ?? '' === 'configurar') {
    try {
        // 1. Tabela de categorias financeiras
        $db->query("
            CREATE TABLE IF NOT EXISTS `categorias_financeiras` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `nome` varchar(100) NOT NULL,
              `descricao` text DEFAULT NULL,
              `tipo` enum('receita','despesa') NOT NULL,
              `cor` varchar(7) DEFAULT '#3498db',
              `icone` varchar(50) DEFAULT NULL,
              `status` enum('ativo','inativo') NOT NULL DEFAULT 'ativo',
              `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              INDEX `idx_tipo` (`tipo`),
              INDEX `idx_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        $mensagens[] = 'Tabela categorias_financeiras criada com sucesso.';

        // 2. Tabela de contas bancárias
        $db->query("
            CREATE TABLE IF NOT EXISTS `contas_bancarias` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `nome` varchar(100) NOT NULL,
              `banco` varchar(100) DEFAULT NULL,
              `agencia` varchar(20) DEFAULT NULL,
              `conta` varchar(20) DEFAULT NULL,
              `tipo` enum('corrente','poupanca','investimento','caixa') NOT NULL DEFAULT 'corrente',
              `saldo_inicial` decimal(10,2) NOT NULL DEFAULT 0.00,
              `saldo_atual` decimal(10,2) NOT NULL DEFAULT 0.00,
              `data_saldo` date NOT NULL,
              `status` enum('ativo','inativo') NOT NULL DEFAULT 'ativo',
              `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              INDEX `idx_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        $mensagens[] = 'Tabela contas_bancarias criada com sucesso.';

        // 3. Tabela de funcionários (se não existir)
        $db->query("
            CREATE TABLE IF NOT EXISTS `funcionarios` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `nome` varchar(255) NOT NULL,
              `cpf` varchar(14) NOT NULL,
              `rg` varchar(20) DEFAULT NULL,
              `data_nascimento` date DEFAULT NULL,
              `data_admissao` date NOT NULL,
              `data_demissao` date DEFAULT NULL,
              `cargo` varchar(100) NOT NULL,
              `departamento` varchar(100) DEFAULT NULL,
              `salario` decimal(10,2) NOT NULL,
              `banco` varchar(100) DEFAULT NULL,
              `agencia` varchar(20) DEFAULT NULL,
              `conta` varchar(20) DEFAULT NULL,
              `tipo_conta` varchar(20) DEFAULT NULL,
              `pix` varchar(100) DEFAULT NULL,
              `email` varchar(255) DEFAULT NULL,
              `telefone` varchar(20) DEFAULT NULL,
              `endereco` text DEFAULT NULL,
              `cidade` varchar(100) DEFAULT NULL,
              `estado` varchar(2) DEFAULT NULL,
              `cep` varchar(10) DEFAULT NULL,
              `status` enum('ativo','inativo') NOT NULL DEFAULT 'ativo',
              `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
              `updated_at` timestamp DEFAULT NULL ON UPDATE current_timestamp(),
              PRIMARY KEY (`id`),
              UNIQUE KEY `cpf` (`cpf`),
              INDEX `idx_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        $mensagens[] = 'Tabela funcionarios criada com sucesso.';

        // 4. Tabela de contas a pagar
        $db->query("
            CREATE TABLE IF NOT EXISTS `contas_pagar` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `descricao` varchar(255) NOT NULL,
              `valor` decimal(10,2) NOT NULL,
              `data_vencimento` date NOT NULL,
              `data_pagamento` date DEFAULT NULL,
              `fornecedor_id` int(11) DEFAULT NULL,
              `fornecedor_nome` varchar(100) DEFAULT NULL,
              `categoria_id` int(11) DEFAULT NULL,
              `conta_bancaria_id` int(11) DEFAULT NULL,
              `forma_pagamento` varchar(50) DEFAULT NULL,
              `status` enum('pendente','pago','cancelado') NOT NULL DEFAULT 'pendente',
              `observacoes` text DEFAULT NULL,
              `comprovante_path` varchar(255) DEFAULT NULL,
              `usuario_id` int(11) DEFAULT NULL,
              `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `categoria_id` (`categoria_id`),
              KEY `conta_bancaria_id` (`conta_bancaria_id`),
              KEY `usuario_id` (`usuario_id`),
              INDEX `idx_status` (`status`),
              INDEX `idx_data_vencimento` (`data_vencimento`),

            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        $mensagens[] = 'Tabela contas_pagar criada com sucesso.';

        // 5. Tabela de contas a receber
        $db->query("
            CREATE TABLE IF NOT EXISTS `contas_receber` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `descricao` varchar(255) NOT NULL,
              `valor` decimal(10,2) NOT NULL,
              `data_vencimento` date NOT NULL,
              `data_recebimento` date DEFAULT NULL,
              `cliente_id` int(11) DEFAULT NULL,
              `cliente_nome` varchar(100) DEFAULT NULL,
              `cliente_tipo` enum('aluno','polo','terceiro') DEFAULT 'terceiro',
              `categoria_id` int(11) DEFAULT NULL,
              `conta_bancaria_id` int(11) DEFAULT NULL,
              `forma_recebimento` varchar(50) DEFAULT NULL,
              `status` enum('pendente','recebido','cancelado') NOT NULL DEFAULT 'pendente',
              `observacoes` text DEFAULT NULL,
              `comprovante_path` varchar(255) DEFAULT NULL,
              `usuario_id` int(11) DEFAULT NULL,
              `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `categoria_id` (`categoria_id`),
              KEY `conta_bancaria_id` (`conta_bancaria_id`),
              KEY `usuario_id` (`usuario_id`),
              INDEX `idx_status` (`status`),
              INDEX `idx_data_vencimento` (`data_vencimento`),
              INDEX `idx_cliente_tipo` (`cliente_tipo`),

            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        $mensagens[] = 'Tabela contas_receber criada com sucesso.';

        // 6. Tabela de transações (movimentações financeiras) - SEM foreign keys para evitar erros
        $db->query("
            CREATE TABLE IF NOT EXISTS `transacoes_financeiras` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `tipo` enum('receita','despesa','transferencia') NOT NULL,
              `descricao` varchar(255) NOT NULL,
              `valor` decimal(10,2) NOT NULL,
              `data_transacao` date NOT NULL,
              `categoria_id` int(11) DEFAULT NULL,
              `conta_bancaria_id` int(11) DEFAULT NULL,
              `conta_destino_id` int(11) DEFAULT NULL,
              `forma_pagamento` varchar(50) DEFAULT NULL,
              `referencia_tipo` enum('conta_pagar','conta_receber','folha_pagamento','outros') DEFAULT NULL,
              `referencia_id` int(11) DEFAULT NULL,
              `status` enum('efetivada','pendente','cancelada') NOT NULL DEFAULT 'efetivada',
              `observacoes` text DEFAULT NULL,
              `comprovante_path` varchar(255) DEFAULT NULL,
              `usuario_id` int(11) DEFAULT NULL,
              `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `categoria_id` (`categoria_id`),
              KEY `conta_bancaria_id` (`conta_bancaria_id`),
              KEY `conta_destino_id` (`conta_destino_id`),
              KEY `usuario_id` (`usuario_id`),
              INDEX `idx_tipo` (`tipo`),
              INDEX `idx_data_transacao` (`data_transacao`),
              INDEX `idx_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        $mensagens[] = 'Tabela transacoes_financeiras criada com sucesso.';

        // 7. Inserir categorias padrão
        $categorias_padrao = [
            // Receitas
            ['Mensalidades de Alunos', 'Receitas provenientes de mensalidades de alunos específicos', 'receita', '#10b981'],
            ['Cobrança de Polos', 'Receitas provenientes de cobrança de polos', 'receita', '#059669'],
            ['Outras Receitas', 'Outras receitas diversas', 'receita', '#34d399'],

            // Despesas
            ['Salários e Encargos', 'Pagamentos de funcionários CLT', 'despesa', '#ef4444'],
            ['Fornecedores', 'Pagamentos a fornecedores', 'despesa', '#dc2626'],
            ['Terceiros', 'Pagamentos a terceiros', 'despesa', '#f87171'],
            ['Despesas Operacionais', 'Despesas operacionais da instituição', 'despesa', '#fca5a5'],
            ['Aluguel', 'Pagamentos de aluguel', 'despesa', '#b91c1c'],
            ['Utilidades', 'Luz, água, telefone, internet', 'despesa', '#991b1b']
        ];

        foreach ($categorias_padrao as $categoria) {
            $db->query("
                INSERT IGNORE INTO categorias_financeiras (nome, descricao, tipo, cor)
                VALUES (?, ?, ?, ?)
            ", $categoria);
        }
        $mensagens[] = 'Categorias padrão inseridas com sucesso.';

        // 8. Criar conta bancária padrão
        $db->query("
            INSERT IGNORE INTO contas_bancarias (nome, tipo, saldo_inicial, saldo_atual, data_saldo)
            VALUES ('Caixa Geral', 'caixa', 0.00, 0.00, CURDATE())
        ");
        $mensagens[] = 'Conta bancária padrão criada com sucesso.';

        $mensagens[] = 'Configuração do módulo financeiro concluída com sucesso!';

    } catch (Exception $e) {
        $erros[] = 'Erro durante a configuração: ' . $e->getMessage();
    }
}

$pageTitle = 'Configuração do Módulo Financeiro';
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
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <div class="mx-auto h-12 w-12 bg-green-600 rounded-full flex items-center justify-center">
                    <i class="fas fa-dollar-sign text-white text-xl"></i>
                </div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    Configuração do Módulo Financeiro
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Configure as tabelas necessárias para o funcionamento do módulo
                </p>
            </div>

            <?php if (!empty($mensagens)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                <ul class="list-disc list-inside">
                    <?php foreach ($mensagens as $mensagem): ?>
                    <li><?php echo htmlspecialchars($mensagem); ?></li>
                    <?php endforeach; ?>
                </ul>
                <div class="mt-4">
                    <a href="index.php" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                        Ir para o Dashboard
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($erros)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                <ul class="list-disc list-inside">
                    <?php foreach ($erros as $erro): ?>
                    <li><?php echo htmlspecialchars($erro); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if (empty($mensagens)): ?>
            <form class="mt-8 space-y-6" method="POST">
                <input type="hidden" name="action" value="configurar">

                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">O que será criado:</h3>
                    <ul class="space-y-2 text-sm text-gray-600">
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            Tabela de categorias financeiras
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            Tabela de contas bancárias
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            Tabela de funcionários
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            Tabela de contas a pagar
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            Tabela de contas a receber
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            Tabela de transações financeiras
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            Categorias e dados padrão
                        </li>
                    </ul>
                </div>

                <div>
                    <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <i class="fas fa-cog text-green-500 group-hover:text-green-400"></i>
                        </span>
                        Configurar Módulo Financeiro
                    </button>
                </div>

                <div class="text-center">
                    <a href="../index.php" class="text-green-600 hover:text-green-500">
                        Voltar ao sistema principal
                    </a>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
