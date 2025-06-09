<?php
// Inicializa o sistema
require_once __DIR__ . '/../includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica se o usuário tem permissão para acessar o módulo financeiro
if (!Auth::hasPermission('financeiro', 'administrar')) {
    setMensagem('erro', 'Você não tem permissão para configurar o módulo financeiro.');
    redirect('../index.php');
    exit;
}

// Instancia o banco de dados
$db = Database::getInstance();

// Verifica se o formulário foi enviado
$setup_concluido = false;
$mensagens = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'setup') {
    try {
        // Inicia a transação
        $db->beginTransaction();
        
        // Cria a tabela categorias_financeiras
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
              `updated_at` datetime DEFAULT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        $mensagens[] = 'Tabela categorias_financeiras criada com sucesso.';
        
        // Cria a tabela transacoes
        $db->query("
            CREATE TABLE IF NOT EXISTS `transacoes` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `tipo` enum('receita','despesa','transferencia') NOT NULL,
              `descricao` varchar(255) NOT NULL,
              `valor` decimal(10,2) NOT NULL,
              `data_transacao` date NOT NULL,
              `categoria_id` int(11) DEFAULT NULL,
              `conta_id` int(11) DEFAULT NULL,
              `forma_pagamento` varchar(50) DEFAULT NULL,
              `status` enum('efetivada','pendente','cancelada') NOT NULL DEFAULT 'efetivada',
              `observacoes` text DEFAULT NULL,
              `comprovante_path` varchar(255) DEFAULT NULL,
              `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` datetime DEFAULT NULL,
              PRIMARY KEY (`id`),
              KEY `categoria_id` (`categoria_id`),
              KEY `conta_id` (`conta_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        $mensagens[] = 'Tabela transacoes criada com sucesso.';
        
        // Cria a tabela contas_receber
        $db->query("
            CREATE TABLE IF NOT EXISTS `contas_receber` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `descricao` varchar(255) NOT NULL,
              `valor` decimal(10,2) NOT NULL,
              `data_vencimento` date NOT NULL,
              `data_recebimento` date DEFAULT NULL,
              `cliente_id` int(11) DEFAULT NULL,
              `cliente_nome` varchar(100) DEFAULT NULL,
              `categoria_id` int(11) DEFAULT NULL,
              `forma_recebimento` varchar(50) DEFAULT NULL,
              `status` enum('pendente','recebido','cancelado') NOT NULL DEFAULT 'pendente',
              `observacoes` text DEFAULT NULL,
              `comprovante_path` varchar(255) DEFAULT NULL,
              `transacao_id` int(11) DEFAULT NULL,
              `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` datetime DEFAULT NULL,
              PRIMARY KEY (`id`),
              KEY `categoria_id` (`categoria_id`),
              KEY `cliente_id` (`cliente_id`),
              KEY `transacao_id` (`transacao_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        $mensagens[] = 'Tabela contas_receber criada com sucesso.';
        
        // Cria a tabela contas_pagar
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
              `forma_pagamento` varchar(50) DEFAULT NULL,
              `status` enum('pendente','pago','cancelado') NOT NULL DEFAULT 'pendente',
              `observacoes` text DEFAULT NULL,
              `comprovante_path` varchar(255) DEFAULT NULL,
              `transacao_id` int(11) DEFAULT NULL,
              `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` datetime DEFAULT NULL,
              PRIMARY KEY (`id`),
              KEY `categoria_id` (`categoria_id`),
              KEY `fornecedor_id` (`fornecedor_id`),
              KEY `transacao_id` (`transacao_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        $mensagens[] = 'Tabela contas_pagar criada com sucesso.';
        
        // Cria a tabela contas_bancarias
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
              `updated_at` datetime DEFAULT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        $mensagens[] = 'Tabela contas_bancarias criada com sucesso.';
        
        // Insere categorias padrão para receitas
        $categorias_receita = [
            ['nome' => 'Mensalidades', 'descricao' => 'Mensalidades de alunos', 'tipo' => 'receita', 'cor' => '#2ecc71', 'icone' => 'fa-money-bill-wave'],
            ['nome' => 'Matrículas', 'descricao' => 'Taxas de matrícula', 'tipo' => 'receita', 'cor' => '#3498db', 'icone' => 'fa-user-graduate'],
            ['nome' => 'Documentos', 'descricao' => 'Emissão de documentos', 'tipo' => 'receita', 'cor' => '#9b59b6', 'icone' => 'fa-file-alt'],
            ['nome' => 'Cursos', 'descricao' => 'Venda de cursos', 'tipo' => 'receita', 'cor' => '#f1c40f', 'icone' => 'fa-book'],
            ['nome' => 'Outras Receitas', 'descricao' => 'Outras fontes de receita', 'tipo' => 'receita', 'cor' => '#1abc9c', 'icone' => 'fa-plus-circle']
        ];
        
        foreach ($categorias_receita as $categoria) {
            $db->insert('categorias_financeiras', array_merge($categoria, [
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]));
        }
        $mensagens[] = 'Categorias de receita padrão criadas com sucesso.';
        
        // Insere categorias padrão para despesas
        $categorias_despesa = [
            ['nome' => 'Salários', 'descricao' => 'Pagamento de funcionários', 'tipo' => 'despesa', 'cor' => '#e74c3c', 'icone' => 'fa-user-tie'],
            ['nome' => 'Aluguel', 'descricao' => 'Aluguel de imóveis', 'tipo' => 'despesa', 'cor' => '#e67e22', 'icone' => 'fa-building'],
            ['nome' => 'Serviços', 'descricao' => 'Serviços contratados', 'tipo' => 'despesa', 'cor' => '#f39c12', 'icone' => 'fa-tools'],
            ['nome' => 'Material', 'descricao' => 'Material de escritório e didático', 'tipo' => 'despesa', 'cor' => '#d35400', 'icone' => 'fa-shopping-cart'],
            ['nome' => 'Impostos', 'descricao' => 'Impostos e taxas', 'tipo' => 'despesa', 'cor' => '#c0392b', 'icone' => 'fa-receipt'],
            ['nome' => 'Outras Despesas', 'descricao' => 'Despesas diversas', 'tipo' => 'despesa', 'cor' => '#7f8c8d', 'icone' => 'fa-minus-circle']
        ];
        
        foreach ($categorias_despesa as $categoria) {
            $db->insert('categorias_financeiras', array_merge($categoria, [
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]));
        }
        $mensagens[] = 'Categorias de despesa padrão criadas com sucesso.';
        
        // Cria uma conta bancária padrão
        $db->insert('contas_bancarias', [
            'nome' => 'Caixa Principal',
            'tipo' => 'caixa',
            'saldo_inicial' => 0.00,
            'saldo_atual' => 0.00,
            'data_saldo' => date('Y-m-d'),
            'status' => 'ativo',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        $mensagens[] = 'Conta bancária padrão criada com sucesso.';
        
        // Confirma a transação
        $db->commit();
        
        // Define a flag de sucesso
        $setup_concluido = true;
        
        // Adiciona permissões para o módulo financeiro
        if (class_exists('Auth')) {
            $permissoes = [
                ['modulo' => 'financeiro', 'acao' => 'visualizar', 'descricao' => 'Visualizar informações financeiras'],
                ['modulo' => 'financeiro', 'acao' => 'criar', 'descricao' => 'Criar transações financeiras'],
                ['modulo' => 'financeiro', 'acao' => 'editar', 'descricao' => 'Editar transações financeiras'],
                ['modulo' => 'financeiro', 'acao' => 'excluir', 'descricao' => 'Excluir transações financeiras'],
                ['modulo' => 'financeiro', 'acao' => 'administrar', 'descricao' => 'Administrar configurações financeiras']
            ];
            
            foreach ($permissoes as $permissao) {
                // Verifica se a permissão já existe
                $sql = "SELECT id FROM permissoes WHERE modulo = ? AND acao = ?";
                $result = $db->fetchOne($sql, [$permissao['modulo'], $permissao['acao']]);
                
                if (!$result) {
                    $db->insert('permissoes', array_merge($permissao, [
                        'created_at' => date('Y-m-d H:i:s')
                    ]));
                }
            }
            $mensagens[] = 'Permissões do módulo financeiro configuradas com sucesso.';
        }
        
        // Adiciona a mensagem de sucesso
        setMensagem('sucesso', 'Módulo financeiro configurado com sucesso!');
        
    } catch (Exception $e) {
        // Desfaz a transação em caso de erro
        $db->rollBack();
        
        // Adiciona a mensagem de erro
        setMensagem('erro', 'Erro ao configurar o módulo financeiro: ' . $e->getMessage());
        
        // Redireciona para a página de setup
        redirect('setup.php');
        exit;
    }
}

// Define o título da página
$titulo_pagina = 'Configuração do Módulo Financeiro';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faciência ERP - <?php echo $titulo_pagina; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .page-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 4rem;
            height: 0.25rem;
            background: linear-gradient(to right, #3b82f6, #8b5cf6);
            border-radius: 0.125rem;
        }
    </style>
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
                    <h1 class="text-3xl font-bold text-gray-800 mb-6 page-header relative pb-3"><?php echo $titulo_pagina; ?></h1>
                    
                    <div class="flex justify-between items-center mb-8">
                        <p class="text-gray-600">Configure o módulo financeiro para começar a usar</p>
                        
                        <div class="flex space-x-3">
                            <a href="index.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-2 px-4 rounded">
                                <i class="fas fa-arrow-left mr-2"></i> Voltar
                            </a>
                        </div>
                    </div>
                    
                    <?php if ($setup_concluido): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-md shadow-sm">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check-circle text-green-500 text-xl"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-lg font-medium text-green-800">Configuração concluída com sucesso!</h3>
                                <div class="mt-2 text-sm text-green-700">
                                    <ul class="list-disc pl-5 space-y-1">
                                        <?php foreach ($mensagens as $mensagem): ?>
                                        <li><?php echo $mensagem; ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                <div class="mt-4">
                                    <a href="index.php" class="bg-green-500 hover:bg-green-600 text-white font-medium py-2 px-4 rounded inline-flex items-center">
                                        <i class="fas fa-home mr-2"></i> Ir para o Dashboard Financeiro
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-xl font-semibold text-gray-800">Configuração Inicial</h2>
                        </div>
                        <div class="p-6">
                            <p class="text-gray-600 mb-6">
                                Este assistente irá configurar o módulo financeiro, criando as tabelas necessárias e configurações iniciais.
                                Certifique-se de que você tem permissões de administrador antes de prosseguir.
                            </p>
                            
                            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6 rounded-md">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-exclamation-triangle text-yellow-400 text-xl"></i>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-lg font-medium text-yellow-800">Atenção</h3>
                                        <div class="mt-2 text-sm text-yellow-700">
                                            <p>Este processo criará novas tabelas no banco de dados. Se você já possui dados financeiros, é recomendável fazer um backup antes de prosseguir.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <h3 class="text-lg font-medium text-gray-800 mb-4">O seguinte será configurado:</h3>
                            <ul class="list-disc pl-5 mb-6 text-gray-600 space-y-2">
                                <li>Tabela de categorias financeiras</li>
                                <li>Tabela de transações</li>
                                <li>Tabela de contas a receber</li>
                                <li>Tabela de contas a pagar</li>
                                <li>Tabela de contas bancárias</li>
                                <li>Categorias padrão para receitas e despesas</li>
                                <li>Permissões para o módulo financeiro</li>
                            </ul>
                            
                            <form action="setup.php" method="post" class="mt-8">
                                <input type="hidden" name="action" value="setup">
                                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-md shadow-sm flex items-center">
                                    <i class="fas fa-cog mr-2"></i> Iniciar Configuração
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </main>
            
            <!-- Footer -->
            <?php include '../includes/footer.php'; ?>
        </div>
    </div>
    
    <script>
        // Toggle sidebar
        document.getElementById('toggle-sidebar').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('sidebar-collapsed');
            sidebar.classList.toggle('sidebar-expanded');
            
            const labels = document.querySelectorAll('.sidebar-label');
            labels.forEach(label => {
                label.classList.toggle('hidden');
            });
        });
        
        // Toggle user menu
        document.getElementById('user-menu-button').addEventListener('click', function() {
            const menu = document.getElementById('user-menu');
            menu.classList.toggle('hidden');
        });
        
        // Close user menu when clicking outside
        document.addEventListener('click', function(event) {
            const menu = document.getElementById('user-menu');
            const button = document.getElementById('user-menu-button');
            
            if (!menu.contains(event.target) && !button.contains(event.target)) {
                menu.classList.add('hidden');
            }
        });
    </script>
</body>
</html>
