<?php
/**
 * Setup Básico do Módulo Financeiro
 * Versão simplificada - Use o arquivo SQL para configuração completa
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
$moduloConfigurado = false;

// Processa a configuração se foi solicitada
if ($_POST['action'] ?? '' === 'configurar') {
    try {
        // 1. Categorias financeiras
        $db->query("
            CREATE TABLE IF NOT EXISTS `categorias_financeiras` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `nome` varchar(100) NOT NULL,
              `tipo` enum('receita','despesa') NOT NULL,
              `cor` varchar(7) DEFAULT '#3498db',
              `status` enum('ativo','inativo') NOT NULL DEFAULT 'ativo',
              `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        $mensagens[] = '✓ Tabela categorias_financeiras criada.';

        // 2. Contas bancárias
        $db->query("
            CREATE TABLE IF NOT EXISTS `contas_bancarias` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `nome` varchar(100) NOT NULL,
              `tipo` enum('corrente','poupanca','investimento','caixa') NOT NULL DEFAULT 'corrente',
              `saldo_atual` decimal(10,2) NOT NULL DEFAULT 0.00,
              `status` enum('ativo','inativo') NOT NULL DEFAULT 'ativo',
              `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        $mensagens[] = '✓ Tabela contas_bancarias criada.';

        // 3. Funcionários
        $db->query("
            CREATE TABLE IF NOT EXISTS `funcionarios` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `nome` varchar(255) NOT NULL,
              `cpf` varchar(14) NOT NULL,
              `cargo` varchar(100) NOT NULL,
              `salario` decimal(10,2) NOT NULL,
              `status` enum('ativo','inativo') NOT NULL DEFAULT 'ativo',
              `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              UNIQUE KEY `cpf` (`cpf`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        $mensagens[] = '✓ Tabela funcionarios criada.';

        // 4. Contas a pagar
        $db->query("
            CREATE TABLE IF NOT EXISTS `contas_pagar` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `descricao` varchar(255) NOT NULL,
              `valor` decimal(10,2) NOT NULL,
              `data_vencimento` date NOT NULL,
              `data_pagamento` date DEFAULT NULL,
              `fornecedor_nome` varchar(100) DEFAULT NULL,
              `status` enum('pendente','pago','cancelado') NOT NULL DEFAULT 'pendente',
              `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        $mensagens[] = '✓ Tabela contas_pagar criada.';

        // 5. Contas a receber
        $db->query("
            CREATE TABLE IF NOT EXISTS `contas_receber` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `descricao` varchar(255) NOT NULL,
              `valor` decimal(10,2) NOT NULL,
              `data_vencimento` date NOT NULL,
              `data_recebimento` date DEFAULT NULL,
              `cliente_nome` varchar(100) DEFAULT NULL,
              `cliente_tipo` enum('aluno','polo','terceiro') DEFAULT 'terceiro',
              `status` enum('pendente','recebido','cancelado') NOT NULL DEFAULT 'pendente',
              `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        $mensagens[] = '✓ Tabela contas_receber criada.';

        // 6. Transações financeiras
        $db->query("
            CREATE TABLE IF NOT EXISTS `transacoes_financeiras` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `tipo` enum('receita','despesa','transferencia') NOT NULL,
              `descricao` varchar(255) NOT NULL,
              `valor` decimal(10,2) NOT NULL,
              `data_transacao` date NOT NULL,
              `status` enum('efetivada','pendente','cancelada') NOT NULL DEFAULT 'efetivada',
              `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        $mensagens[] = '✓ Tabela transacoes_financeiras criada.';

        // 7. Folha de pagamento
        $db->query("
            CREATE TABLE IF NOT EXISTS `folha_pagamento` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `funcionario_id` int(11) NOT NULL,
              `mes_referencia` date NOT NULL,
              `salario_base` decimal(10,2) NOT NULL,
              `inss` decimal(10,2) DEFAULT 0.00,
              `irrf` decimal(10,2) DEFAULT 0.00,
              `salario_liquido` decimal(10,2) NOT NULL,
              `data_pagamento` date DEFAULT NULL,
              `status` enum('calculada','paga','cancelada') NOT NULL DEFAULT 'calculada',
              `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        $mensagens[] = '✓ Tabela folha_pagamento criada.';

        // 8. Mensalidades de alunos
        $db->query("
            CREATE TABLE IF NOT EXISTS `mensalidades_alunos` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `aluno_id` int(11) NOT NULL,
              `valor` decimal(10,2) NOT NULL,
              `data_vencimento` date NOT NULL,
              `data_pagamento` date DEFAULT NULL,
              `mes_referencia` date NOT NULL,
              `status` enum('pendente','pago','cancelado','isento') NOT NULL DEFAULT 'pendente',
              `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        $mensagens[] = '✓ Tabela mensalidades_alunos criada.';

        // 9. Cobrança de polos
        $db->query("
            CREATE TABLE IF NOT EXISTS `cobranca_polos` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `polo_id` int(11) NOT NULL,
              `descricao` varchar(255) NOT NULL,
              `valor` decimal(10,2) NOT NULL,
              `data_vencimento` date NOT NULL,
              `data_pagamento` date DEFAULT NULL,
              `mes_referencia` date NOT NULL,
              `tipo_cobranca` enum('mensalidade','taxa','outros') NOT NULL DEFAULT 'mensalidade',
              `status` enum('pendente','pago','cancelado') NOT NULL DEFAULT 'pendente',
              `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        $mensagens[] = '✓ Tabela cobranca_polos criada.';

        // 10. BOLETOS BANCÁRIOS (TABELA PRINCIPAL QUE ESTAVA FALTANDO)
        $db->query("
            CREATE TABLE IF NOT EXISTS `boletos` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `tipo` enum('mensalidade','polo','avulso') NOT NULL,
              `referencia_id` int(11) DEFAULT NULL,
              `valor` decimal(10,2) NOT NULL,
              `data_vencimento` date NOT NULL,
              `descricao` varchar(255) NOT NULL,
              `nome_pagador` varchar(255) NOT NULL,
              `cpf_pagador` varchar(14) NOT NULL,
              `endereco` varchar(255) DEFAULT NULL,
              `bairro` varchar(100) DEFAULT NULL,
              `cidade` varchar(100) DEFAULT NULL,
              `uf` varchar(2) DEFAULT NULL,
              `cep` varchar(10) DEFAULT NULL,
              `multa` decimal(5,2) DEFAULT 2.00,
              `juros` decimal(5,2) DEFAULT 1.00,
              `nosso_numero` varchar(20) DEFAULT NULL,
              `linha_digitavel` varchar(100) DEFAULT NULL,
              `codigo_barras` varchar(100) DEFAULT NULL,
              `url_boleto` varchar(500) DEFAULT NULL,
              `ambiente` enum('teste','producao') DEFAULT 'teste',
              `status` enum('pendente','pago','vencido','cancelado') NOT NULL DEFAULT 'pendente',
              `data_pagamento` date DEFAULT NULL,
              `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        $mensagens[] = '✓ Tabela boletos criada.';

        // Inserir categorias básicas
        $categorias_basicas = [
            ['Mensalidades', 'receita', '#10b981'],
            ['Cobrança Polos', 'receita', '#059669'],
            ['Outras Receitas', 'receita', '#34d399'],
            ['Salários', 'despesa', '#ef4444'],
            ['Fornecedores', 'despesa', '#dc2626'],
            ['Despesas Gerais', 'despesa', '#f87171']
        ];

        foreach ($categorias_basicas as $categoria) {
            try {
                $db->query("
                    INSERT IGNORE INTO categorias_financeiras (nome, tipo, cor) 
                    VALUES (?, ?, ?)
                ", $categoria);
            } catch (Exception $e) {
                // Tenta inserir sem o campo cor se der erro
                $db->query("
                    INSERT IGNORE INTO categorias_financeiras (nome, tipo) 
                    VALUES (?, ?)
                ", [$categoria[0], $categoria[1]]);
            }
        }
        $mensagens[] = '✓ Categorias básicas inseridas.';

        // Criar conta padrão
        $db->query("
            INSERT IGNORE INTO contas_bancarias (nome, tipo, saldo_atual) 
            VALUES ('Caixa Geral', 'caixa', 0.00)
        ");
        $mensagens[] = '✓ Conta bancária padrão criada.';

        $mensagens[] = '🎉 Configuração básica concluída com sucesso!';
        
    } catch (Exception $e) {
        $erros[] = 'Erro: ' . $e->getMessage();
    }
} else {
    // Verifica se o módulo já está configurado
    try {
        $tabelasNecessarias = [
            'categorias_financeiras',
            'contas_bancarias',
            'funcionarios',
            'contas_pagar',
            'contas_receber',
            'transacoes_financeiras',
            'folha_pagamento',
            'mensalidades_alunos',
            'cobranca_polos',
            'boletos',
            'configuracoes_financeiras'
        ];
        
        $tabelasExistentes = 0;
        foreach ($tabelasNecessarias as $tabela) {
            $resultado = $db->fetchOne("SHOW TABLES LIKE ?", [$tabela]);
            if ($resultado) {
                $tabelasExistentes++;
            }
        }
        
        if ($tabelasExistentes >= 8) { // Se pelo menos 8 das 11 tabelas existem
            $moduloConfigurado = true;
            $mensagens[] = '✓ Módulo financeiro já está configurado!';
            $mensagens[] = "✓ {$tabelasExistentes} de " . count($tabelasNecessarias) . " tabelas encontradas.";
        }
        
    } catch (Exception $e) {
        $erros[] = 'Erro ao verificar configuração: ' . $e->getMessage();
    }
}

// Se o módulo já está configurado, redireciona automaticamente
if ($moduloConfigurado && empty($_GET['force'])) {
    header('Location: index.php');
    exit;
}

$pageTitle = 'Setup Básico do Módulo Financeiro';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Faciência ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-lg w-full space-y-8">
            <div class="text-center">
                <div class="mx-auto h-16 w-16 bg-green-600 rounded-full flex items-center justify-center">
                    <i class="fas fa-dollar-sign text-white text-2xl"></i>
                </div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    Setup Básico
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Configuração ultra-simplificada para máxima compatibilidade
                </p>
            </div>

            <?php if (!empty($mensagens)): ?>
            <div class="bg-green-50 border border-green-200 rounded-lg p-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle text-green-400 text-xl"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-green-800">Configuração Concluída!</h3>
                        <div class="mt-2 text-sm text-green-700">
                            <ul class="list-disc list-inside space-y-1">
                                <?php foreach ($mensagens as $mensagem): ?>
                                <li><?php echo htmlspecialchars($mensagem); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div class="mt-4">
                            <a href="index.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                <i class="fas fa-chart-line mr-2"></i>
                                Acessar Dashboard Financeiro
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
                        <h3 class="text-sm font-medium text-red-800">Erro na Configuração</h3>
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
            <?php endif; ?>            <?php if (empty($mensagens)): ?>
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-8">
                    <h3 class="text-lg font-medium text-gray-900 mb-6">Configuração do Módulo Financeiro</h3>
                    
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-info-circle text-blue-400 text-xl"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-blue-800">Método Recomendado</h3>
                                <div class="mt-2 text-sm text-blue-700">
                                    <p class="mb-2">Para evitar problemas de compatibilidade, recomendamos executar o arquivo SQL diretamente no banco de dados:</p>
                                    <ol class="list-decimal list-inside space-y-1">
                                        <li>Acesse seu phpMyAdmin, Workbench ou terminal MySQL</li>
                                        <li>Execute o arquivo: <code class="bg-blue-100 px-2 py-1 rounded">sql/setup_modulo_financeiro.sql</code></li>
                                        <li>Aguarde a execução completa</li>
                                        <li>Retorne aqui para acessar o módulo</li>
                                    </ol>
                                </div>
                                <div class="mt-4">
                                    <a href="../sql/setup_modulo_financeiro.sql" download class="inline-flex items-center px-3 py-2 border border-blue-300 text-sm font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200">
                                        <i class="fas fa-download mr-2"></i>
                                        Baixar Arquivo SQL
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 gap-4 mb-6">
                        <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                            <i class="fas fa-table text-green-600 mr-3"></i>
                            <span class="text-sm text-gray-700">11 tabelas principais com estrutura completa</span>
                        </div>
                        <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                            <i class="fas fa-tags text-green-600 mr-3"></i>
                            <span class="text-sm text-gray-700">10 categorias financeiras padrão</span>
                        </div>
                        <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                            <i class="fas fa-university text-green-600 mr-3"></i>
                            <span class="text-sm text-gray-700">2 contas bancárias padrão</span>
                        </div>
                        <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                            <i class="fas fa-cogs text-green-600 mr-3"></i>
                            <span class="text-sm text-gray-700">Configurações básicas do sistema</span>
                        </div>
                        <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                            <i class="fas fa-shield-alt text-green-600 mr-3"></i>
                            <span class="text-sm text-gray-700">Máxima compatibilidade MySQL/MariaDB</span>
                        </div>
                    </div>

                    <div class="border-t pt-6">
                        <h4 class="text-md font-medium text-gray-900 mb-3">Instruções Detalhadas:</h4>
                        <div class="prose text-sm text-gray-600">
                            <p><strong>1. Via phpMyAdmin:</strong></p>
                            <ul>
                                <li>Acesse seu phpMyAdmin</li>
                                <li>Selecione sua base de dados</li>
                                <li>Vá na aba "SQL"</li>
                                <li>Cole o conteúdo do arquivo SQL ou use "Importar"</li>
                                <li>Execute</li>
                            </ul>
                            
                            <p class="mt-4"><strong>2. Via Terminal MySQL:</strong></p>
                            <ul>
                                <li><code>mysql -u usuario -p base_dados < sql/setup_modulo_financeiro.sql</code></li>
                            </ul>
                            
                            <p class="mt-4"><strong>3. Via MySQL Workbench:</strong></p>
                            <ul>
                                <li>Abra o arquivo SQL no Workbench</li>
                                <li>Execute o script completo</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center">
                <a href="../index.php" class="text-green-600 hover:text-green-500 text-sm">
                    <i class="fas fa-arrow-left mr-1"></i>
                    Voltar ao sistema principal
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
