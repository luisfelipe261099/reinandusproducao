<?php
/**
 * Script para criar a tabela de configurações
 */

// Inclui os arquivos necessários
require_once __DIR__ . '/../includes/init.php';

// Verifica se o usuário está logado
exigirLogin();

// Verifica se o usuário tem permissão para acessar esta página
if (!Auth::hasPermission('admin', 'administrar')) {
    setMensagem('erro', 'Você não tem permissão para acessar esta página.');
    redirect('index.php');
    exit;
}

// Inicializa a conexão com o banco de dados
$db = Database::getInstance();

// Define o título da página
$titulo_pagina = 'Criar Tabela de Configurações';
$titulo_pagina_completo = 'Faciência ERP - ' . $titulo_pagina;

// Verifica se a tabela já existe
$tabela_existe = $db->fetchOne("SHOW TABLES LIKE 'configuracoes'");

// Resultados da operação
$resultados = [];

// Verifica se foi passada uma ação
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Processa a criação da tabela
if ($action === 'criar') {
    try {
        // SQL para criar a tabela
        $sql = "CREATE TABLE IF NOT EXISTS `configuracoes` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `chave` varchar(100) NOT NULL,
            `valor` text NOT NULL,
            `descricao` text,
            `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `chave_unique` (`chave`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        // Executa o SQL
        $db->query($sql);
        
        $resultados[] = [
            'etapa' => 'Criação da Tabela',
            'status' => 'sucesso',
            'mensagem' => 'Tabela de configurações criada com sucesso.'
        ];
        
        // Insere as configurações padrão
        $configs = [
            [
                'chave' => 'api_itau_ambiente',
                'valor' => 'teste',
                'descricao' => 'Ambiente da API do Itaú (teste ou producao)'
            ],
            [
                'chave' => 'api_itau_token_url_teste',
                'valor' => 'https://sts.itau.com.br/api/oauth/token',
                'descricao' => 'URL para obtenção de token no ambiente de teste do Itaú'
            ],
            [
                'chave' => 'api_itau_token_url_producao',
                'valor' => 'https://api.itau.com.br/api/oauth/token',
                'descricao' => 'URL para obtenção de token no ambiente de produção do Itaú'
            ],
            [
                'chave' => 'api_itau_cash_management_url_teste',
                'valor' => 'https://api.itau.com.br/cash_management/v2/boletos',
                'descricao' => 'URL base da API cash_management no ambiente de teste do Itaú'
            ],
            [
                'chave' => 'api_itau_cash_management_url_producao',
                'valor' => 'https://api.itau.com.br/cash_management/v2/boletos',
                'descricao' => 'URL base da API cash_management no ambiente de produção do Itaú'
            ],
            [
                'chave' => 'api_itau_cobranca_url_teste',
                'valor' => 'https://api.itau.com.br/cobranca/v2/boletos',
                'descricao' => 'URL base da API cobranca no ambiente de teste do Itaú'
            ],
            [
                'chave' => 'api_itau_cobranca_url_producao',
                'valor' => 'https://api.itau.com.br/cobranca/v2/boletos',
                'descricao' => 'URL base da API cobranca no ambiente de produção do Itaú'
            ]
        ];
        
        foreach ($configs as $config) {
            try {
                // Verifica se a configuração já existe
                $config_existe = $db->fetchOne("SELECT id FROM configuracoes WHERE chave = ?", [$config['chave']]);
                
                if ($config_existe) {
                    // Atualiza a configuração existente
                    $db->update('configuracoes', [
                        'valor' => $config['valor'],
                        'descricao' => $config['descricao'],
                        'updated_at' => date('Y-m-d H:i:s')
                    ], 'chave = ?', [$config['chave']]);
                    
                    $resultados[] = [
                        'etapa' => 'Configuração: ' . $config['chave'],
                        'status' => 'sucesso',
                        'mensagem' => 'Configuração atualizada com sucesso.'
                    ];
                } else {
                    // Insere uma nova configuração
                    $db->insert('configuracoes', [
                        'chave' => $config['chave'],
                        'valor' => $config['valor'],
                        'descricao' => $config['descricao'],
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                    
                    $resultados[] = [
                        'etapa' => 'Configuração: ' . $config['chave'],
                        'status' => 'sucesso',
                        'mensagem' => 'Configuração inserida com sucesso.'
                    ];
                }
            } catch (Exception $e) {
                $resultados[] = [
                    'etapa' => 'Configuração: ' . $config['chave'],
                    'status' => 'erro',
                    'mensagem' => 'Erro ao inserir/atualizar configuração: ' . $e->getMessage()
                ];
            }
        }
        
    } catch (Exception $e) {
        $resultados[] = [
            'etapa' => 'Criação da Tabela',
            'status' => 'erro',
            'mensagem' => 'Erro ao criar tabela de configurações: ' . $e->getMessage()
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo_pagina_completo; ?></title>
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

        .test-result {
            margin-bottom: 1rem;
            padding: 1rem;
            border-radius: 0.5rem;
        }

        .test-sucesso {
            background-color: #D1FAE5;
            border-left: 4px solid #059669;
        }

        .test-erro {
            background-color: #FEE2E2;
            border-left: 4px solid #DC2626;
        }

        .test-info {
            background-color: #DBEAFE;
            border-left: 4px solid #3B82F6;
        }

        .test-aviso {
            background-color: #FEF3C7;
            border-left: 4px solid #D97706;
        }

        .test-title {
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
    </style>
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
                    <h1 class="text-3xl font-bold text-gray-800 mb-6 page-header relative pb-3"><?php echo $titulo_pagina; ?></h1>

                    <div class="bg-white shadow-md rounded-lg p-6">
                        <h2 class="text-xl font-bold mb-4">Criar Tabela de Configurações</h2>

                        <div class="mb-6 p-4 bg-blue-50 border-l-4 border-blue-400 text-blue-700">
                            <p class="font-bold">Informação:</p>
                            <p>Esta ferramenta cria a tabela de configurações no banco de dados e insere as configurações padrão.</p>
                            <p class="mt-2">A tabela de configurações é usada para armazenar configurações do sistema, como o ambiente da API do Itaú.</p>
                        </div>

                        <?php if ($tabela_existe): ?>
                        <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-400 text-green-700">
                            <p class="font-bold">Tabela já existe:</p>
                            <p>A tabela de configurações já existe no banco de dados.</p>
                            <p class="mt-2">Você pode atualizar as configurações padrão clicando no botão abaixo.</p>
                        </div>
                        <?php else: ?>
                        <div class="mb-6 p-4 bg-yellow-50 border-l-4 border-yellow-400 text-yellow-700">
                            <p class="font-bold">Tabela não existe:</p>
                            <p>A tabela de configurações não existe no banco de dados.</p>
                            <p class="mt-2">Clique no botão abaixo para criar a tabela e inserir as configurações padrão.</p>
                        </div>
                        <?php endif; ?>

                        <form action="criar_tabela_configuracoes.php" method="get" class="mb-6">
                            <input type="hidden" name="action" value="criar">
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                <i class="fas fa-database mr-2"></i> <?php echo $tabela_existe ? 'Atualizar Configurações' : 'Criar Tabela e Configurações'; ?>
                            </button>
                        </form>

                        <?php if (!empty($resultados)): ?>
                        <div class="mt-6">
                            <h3 class="text-lg font-bold mb-4">Resultados da Operação:</h3>
                            
                            <?php foreach ($resultados as $resultado): ?>
                            <div class="test-result test-<?php echo $resultado['status']; ?>">
                                <div class="test-title">
                                    <?php if ($resultado['status'] === 'sucesso'): ?>
                                    <i class="fas fa-check-circle text-green-600 mr-2"></i>
                                    <?php elseif ($resultado['status'] === 'erro'): ?>
                                    <i class="fas fa-times-circle text-red-600 mr-2"></i>
                                    <?php elseif ($resultado['status'] === 'aviso'): ?>
                                    <i class="fas fa-exclamation-triangle text-yellow-600 mr-2"></i>
                                    <?php else: ?>
                                    <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                                    <?php endif; ?>
                                    <?php echo $resultado['etapa']; ?>
                                </div>
                                <div class="test-message">
                                    <?php echo $resultado['mensagem']; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            
                            <div class="mt-4">
                                <a href="index.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
                                    <i class="fas fa-arrow-left mr-2"></i> Voltar para o Início
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>
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
