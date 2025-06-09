<?php
/**
 * Script para atualizar a estrutura do banco de dados
 * Adiciona campos para rastreamento da API
 */

// Inclui os arquivos necessários
require_once __DIR__ . '/../includes/init.php';

// Verifica se o usuário está logado e tem permissão de administrador
exigirLogin();
if (!Auth::hasPermission('admin', 'administrar')) {
    setMensagem('erro', 'Você não tem permissão para executar esta operação.');
    redirect('index.php');
    exit;
}

// Inicializa a conexão com o banco de dados
$db = Database::getInstance();

// Define as alterações a serem feitas
$alteracoes = [
    // Adiciona campos para rastreamento da API na tabela boletos
    "ALTER TABLE boletos ADD COLUMN IF NOT EXISTS api_ambiente VARCHAR(20) NULL COMMENT 'Ambiente da API (produção ou teste)'",
    "ALTER TABLE boletos ADD COLUMN IF NOT EXISTS api_token_id VARCHAR(100) NULL COMMENT 'ID do token usado na API'",
    "ALTER TABLE boletos ADD COLUMN IF NOT EXISTS api_response_id VARCHAR(100) NULL COMMENT 'ID da resposta da API'",
    "ALTER TABLE boletos ADD COLUMN IF NOT EXISTS api_request_data TEXT NULL COMMENT 'Dados da requisição enviada à API'",
    
    // Adiciona índices para melhorar a performance
    "ALTER TABLE boletos ADD INDEX idx_nosso_numero (nosso_numero)",
    "ALTER TABLE boletos ADD INDEX idx_status (status)",
    "ALTER TABLE boletos ADD INDEX idx_data_vencimento (data_vencimento)"
];

// Executa as alterações
$sucesso = true;
$mensagens = [];

foreach ($alteracoes as $sql) {
    try {
        $db->query($sql);
        $mensagens[] = "Sucesso: $sql";
    } catch (Exception $e) {
        $sucesso = false;
        $mensagens[] = "Erro: " . $e->getMessage() . " (SQL: $sql)";
    }
}

// Define o título da página
$titulo_pagina = 'Atualizar Banco de Dados';
$titulo_pagina_completo = 'Faciência ERP - ' . $titulo_pagina;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo_pagina_completo; ?></title>
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
                    <h1 class="text-3xl font-bold text-gray-800 mb-6">Atualizar Banco de Dados</h1>

                    <div class="bg-white shadow-md rounded-lg p-6">
                        <h2 class="text-xl font-bold mb-4">Resultado da Atualização</h2>

                        <?php if ($sucesso): ?>
                        <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-4">
                            <p class="text-green-700 font-medium">Banco de dados atualizado com sucesso!</p>
                        </div>
                        <?php else: ?>
                        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-4">
                            <p class="text-red-700 font-medium">Ocorreram erros durante a atualização do banco de dados.</p>
                        </div>
                        <?php endif; ?>

                        <div class="mt-4">
                            <h3 class="font-bold mb-2">Detalhes:</h3>
                            <div class="bg-gray-50 p-4 rounded-lg max-h-96 overflow-y-auto">
                                <ul class="list-disc list-inside">
                                    <?php foreach ($mensagens as $mensagem): ?>
                                    <li class="<?php echo strpos($mensagem, 'Erro') === 0 ? 'text-red-600' : 'text-green-600'; ?> mb-1">
                                        <?php echo $mensagem; ?>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>

                        <div class="mt-6">
                            <a href="index.php" class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded">
                                <i class="fas fa-home mr-2"></i> Voltar para o Início
                            </a>
                        </div>
                    </div>
                </div>
            </main>

            <!-- Footer -->
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>
</body>
</html>
