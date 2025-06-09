<?php
// Inicializa o sistema
require_once __DIR__ . '/includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Instancia o banco de dados
$db = Database::getInstance();

// Define o título da página
$titulo_pagina = 'Solicitar Documentos';

// Verifica se o formulário foi enviado
$mensagem = '';
$tipo_mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtém os dados do formulário
    $nome_empresa = $_POST['nome_empresa'] ?? '';
    $cnpj = $_POST['cnpj'] ?? '';
    $nome_solicitante = $_POST['nome_solicitante'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $email = $_POST['email'] ?? '';
    $link_planilha = $_POST['link_planilha'] ?? '';
    $tipo_solicitacao = $_POST['tipo_solicitacao'] ?? '';
    $quantidade = (int)($_POST['quantidade'] ?? 0);
    $observacao = $_POST['observacao'] ?? '';
    
    // Validação básica
    $erros = [];
    
    if (empty($nome_empresa)) {
        $erros[] = 'O nome da empresa é obrigatório.';
    }
    
    if (empty($cnpj)) {
        $erros[] = 'O CNPJ é obrigatório.';
    }
    
    if (empty($nome_solicitante)) {
        $erros[] = 'O nome do solicitante é obrigatório.';
    }
    
    if (empty($telefone)) {
        $erros[] = 'O telefone é obrigatório.';
    }
    
    if (empty($email)) {
        $erros[] = 'O e-mail é obrigatório.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros[] = 'O e-mail informado é inválido.';
    }
    
    if (empty($tipo_solicitacao)) {
        $erros[] = 'O tipo de solicitação é obrigatório.';
    }
    
    if ($quantidade <= 0) {
        $erros[] = 'A quantidade deve ser maior que zero.';
    }
    
    // Se não houver erros, salva a solicitação
    if (empty($erros)) {
        try {
            // Gera um protocolo único
            $protocolo = 'DOC-' . date('YmdHis') . '-' . rand(1000, 9999);
            
            // Prepara os dados para inserção
            $dados = [
                'protocolo' => $protocolo,
                'nome_empresa' => $nome_empresa,
                'cnpj' => $cnpj,
                'nome_solicitante' => $nome_solicitante,
                'telefone' => $telefone,
                'email' => $email,
                'link_planilha' => $link_planilha,
                'tipo_solicitacao' => $tipo_solicitacao,
                'quantidade' => $quantidade,
                'observacao' => $observacao,
                'data_solicitacao' => date('Y-m-d H:i:s'),
                'status' => 'Pendente'
            ];
            
            // Insere a solicitação no banco de dados
            $db->insert('solicitacoes_site', $dados);
            
            // Define a mensagem de sucesso
            $mensagem = 'Solicitação enviada com sucesso! Seu protocolo é: ' . $protocolo;
            $tipo_mensagem = 'sucesso';
            
            // Limpa os dados do formulário
            $nome_empresa = $cnpj = $nome_solicitante = $telefone = $email = $link_planilha = $tipo_solicitacao = $observacao = '';
            $quantidade = 0;
            
        } catch (Exception $e) {
            $mensagem = 'Erro ao enviar a solicitação: ' . $e->getMessage();
            $tipo_mensagem = 'erro';
        }
    } else {
        $mensagem = implode('<br>', $erros);
        $tipo_mensagem = 'erro';
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
    <link rel="stylesheet" href="css/styles.css">
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
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <div class="flex justify-between items-center mb-6">
                            <div>
                                <h1 class="text-2xl font-bold"><?php echo $titulo_pagina; ?></h1>
                                <p class="text-gray-600">Preencha o formulário abaixo para solicitar documentos</p>
                            </div>
                            <div>
                                <a href="chamados/index.php?view=chamados_site" class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded">
                                    <i class="fas fa-list mr-2"></i> Ver Solicitações
                                </a>
                            </div>
                        </div>

                        <?php if (!empty($mensagem)): ?>
                        <div class="mb-6 p-4 rounded-md <?php echo $tipo_mensagem === 'sucesso' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                            <?php echo $mensagem; ?>
                        </div>
                        <?php endif; ?>

                        <form action="solicitar_documentos.php" method="post" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Dados da Empresa -->
                                <div>
                                    <label for="nome_empresa" class="block text-sm font-medium text-gray-700 mb-1">Nome da Empresa <span class="text-red-600">*</span></label>
                                    <input type="text" id="nome_empresa" name="nome_empresa" value="<?php echo htmlspecialchars($nome_empresa ?? ''); ?>" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                                </div>

                                <div>
                                    <label for="cnpj" class="block text-sm font-medium text-gray-700 mb-1">CNPJ <span class="text-red-600">*</span></label>
                                    <input type="text" id="cnpj" name="cnpj" value="<?php echo htmlspecialchars($cnpj ?? ''); ?>" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                                </div>

                                <!-- Dados do Solicitante -->
                                <div>
                                    <label for="nome_solicitante" class="block text-sm font-medium text-gray-700 mb-1">Nome do Solicitante <span class="text-red-600">*</span></label>
                                    <input type="text" id="nome_solicitante" name="nome_solicitante" value="<?php echo htmlspecialchars($nome_solicitante ?? ''); ?>" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                                </div>

                                <div>
                                    <label for="telefone" class="block text-sm font-medium text-gray-700 mb-1">Telefone <span class="text-red-600">*</span></label>
                                    <input type="text" id="telefone" name="telefone" value="<?php echo htmlspecialchars($telefone ?? ''); ?>" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                                </div>

                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">E-mail <span class="text-red-600">*</span></label>
                                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                                </div>

                                <div>
                                    <label for="link_planilha" class="block text-sm font-medium text-gray-700 mb-1">Link da Planilha</label>
                                    <input type="url" id="link_planilha" name="link_planilha" value="<?php echo htmlspecialchars($link_planilha ?? ''); ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                                    <p class="text-xs text-gray-500 mt-1">Link para planilha com detalhes dos documentos (Google Sheets, OneDrive, etc.)</p>
                                </div>

                                <!-- Dados da Solicitação -->
                                <div>
                                    <label for="tipo_solicitacao" class="block text-sm font-medium text-gray-700 mb-1">Tipo de Solicitação <span class="text-red-600">*</span></label>
                                    <select id="tipo_solicitacao" name="tipo_solicitacao" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                                        <option value="">Selecione...</option>
                                        <option value="certificado" <?php echo ($tipo_solicitacao ?? '') === 'certificado' ? 'selected' : ''; ?>>Certificado</option>
                                        <option value="declaracao" <?php echo ($tipo_solicitacao ?? '') === 'declaracao' ? 'selected' : ''; ?>>Declaração</option>
                                        <option value="historico" <?php echo ($tipo_solicitacao ?? '') === 'historico' ? 'selected' : ''; ?>>Histórico</option>
                                        <option value="outro" <?php echo ($tipo_solicitacao ?? '') === 'outro' ? 'selected' : ''; ?>>Outro</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="quantidade" class="block text-sm font-medium text-gray-700 mb-1">Quantidade <span class="text-red-600">*</span></label>
                                    <input type="number" id="quantidade" name="quantidade" value="<?php echo htmlspecialchars($quantidade ?? 1); ?>" min="1" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                                </div>

                                <div class="md:col-span-2">
                                    <label for="observacao" class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                                    <textarea id="observacao" name="observacao" rows="4" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50"><?php echo htmlspecialchars($observacao ?? ''); ?></textarea>
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded">
                                    <i class="fas fa-paper-plane mr-2"></i> Enviar Solicitação
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>

            <!-- Footer -->
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>

    <script>
        // Máscara para CNPJ
        document.getElementById('cnpj').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 14) value = value.slice(0, 14);
            
            if (value.length > 12) {
                value = value.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/, '$1.$2.$3/$4-$5');
            } else if (value.length > 8) {
                value = value.replace(/^(\d{2})(\d{3})(\d{3})(\d+)$/, '$1.$2.$3/$4');
            } else if (value.length > 5) {
                value = value.replace(/^(\d{2})(\d{3})(\d+)$/, '$1.$2.$3');
            } else if (value.length > 2) {
                value = value.replace(/^(\d{2})(\d+)$/, '$1.$2');
            }
            
            e.target.value = value;
        });

        // Máscara para telefone
        document.getElementById('telefone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) value = value.slice(0, 11);
            
            if (value.length > 10) {
                value = value.replace(/^(\d{2})(\d{5})(\d{4})$/, '($1) $2-$3');
            } else if (value.length > 6) {
                value = value.replace(/^(\d{2})(\d{4})(\d+)$/, '($1) $2-$3');
            } else if (value.length > 2) {
                value = value.replace(/^(\d{2})(\d+)$/, '($1) $2');
            }
            
            e.target.value = value;
        });
    </script>
</body>
</html>
