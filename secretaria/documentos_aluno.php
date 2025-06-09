<?php
/**
 * Página alternativa para visualização de documentos dos alunos
 * Esta é uma versão simplificada para testar o acesso
 */

// Inicializa o sistema
require_once 'includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Obtém o ID do aluno
$aluno_id = $_GET['id'] ?? null;

// Verifica se o ID do aluno foi informado
if (!$aluno_id) {
    setMensagem('erro', 'ID do aluno não informado.');
    redirect('alunos.php');
    exit;
}

// Instancia o banco de dados
$db = Database::getInstance();

// Busca o aluno
$sql = "SELECT * FROM alunos WHERE id = ?";
$aluno = $db->fetchOne($sql, [$aluno_id]);

if (!$aluno) {
    setMensagem('erro', 'Aluno não encontrado.');
    redirect('alunos.php');
    exit;
}

// Busca os documentos do aluno
$sql = "SELECT da.*, tdp.nome as tipo_documento_nome
        FROM documentos_alunos da
        JOIN tipos_documentos_pessoais tdp ON da.tipo_documento_id = tdp.id
        WHERE da.aluno_id = ?
        ORDER BY da.created_at DESC";
$documentos = $db->fetchAll($sql, [$aluno_id]);

// Define o título da página
$titulo_pagina = 'Documentos do Aluno: ' . $aluno['nome'];
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
                    <div class="flex justify-between items-center mb-6">
                        <h1 class="text-2xl font-bold text-gray-800"><?php echo $titulo_pagina; ?></h1>
                    </div>

                    <?php if (isset($_SESSION['mensagem']) && isset($_SESSION['mensagem_tipo'])): ?>
                    <div class="bg-<?php echo $_SESSION['mensagem_tipo'] === 'sucesso' ? 'green' : 'red'; ?>-100 border-l-4 border-<?php echo $_SESSION['mensagem_tipo'] === 'sucesso' ? 'green' : 'red'; ?>-500 text-<?php echo $_SESSION['mensagem_tipo'] === 'sucesso' ? 'green' : 'red'; ?>-700 p-4 mb-6">
                        <?php echo is_array($_SESSION['mensagem']) ? implode(', ', $_SESSION['mensagem']) : $_SESSION['mensagem']; ?>
                    </div>
                    <?php
                    // Limpa a mensagem da sessão
                    unset($_SESSION['mensagem']);
                    unset($_SESSION['mensagem_tipo']);
                    endif;
                    ?>

                    <!-- Informações do Aluno -->
                    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">Informações do Aluno</h2>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <p class="text-sm text-gray-500">Nome</p>
                                <p class="font-medium"><?php echo htmlspecialchars($aluno['nome']); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">CPF</p>
                                <p class="font-medium"><?php echo formatarCpf($aluno['cpf']); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">E-mail</p>
                                <p class="font-medium"><?php echo htmlspecialchars($aluno['email']); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Lista de Documentos -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-800">Documentos do Aluno</h2>
                        </div>
                        <div class="p-6">
                            <?php if (empty($documentos)): ?>
                            <div class="text-center text-gray-500 py-4">
                                <p>Nenhum documento encontrado para este aluno.</p>
                            </div>
                            <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo de Documento</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data de Upload</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($documentos as $documento): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($documento['tipo_documento_nome']); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900"><?php echo date('d/m/Y', strtotime($documento['data_upload'])); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                                    <?php
                                                    if ($documento['status'] === 'aprovado') {
                                                        echo 'bg-green-100 text-green-800';
                                                    } elseif ($documento['status'] === 'pendente') {
                                                        echo 'bg-yellow-100 text-yellow-800';
                                                    } else {
                                                        echo 'bg-red-100 text-red-800';
                                                    }
                                                    ?>">
                                                    <?php
                                                    if ($documento['status'] === 'aprovado') {
                                                        echo 'Aprovado';
                                                    } elseif ($documento['status'] === 'pendente') {
                                                        echo 'Pendente';
                                                    } else {
                                                        echo 'Rejeitado';
                                                    }
                                                    ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <a href="uploads/documentos_alunos/<?php echo $documento['arquivo']; ?>" target="_blank" class="text-blue-600 hover:text-blue-900">Visualizar</a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mt-6">
                        <a href="alunos.php?action=visualizar&id=<?php echo $aluno_id; ?>" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gray-600 hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                            <i class="fas fa-arrow-left mr-2"></i> Voltar para o Aluno
                        </a>
                    </div>
                </div>
            </main>

            <!-- Footer -->
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>

    <script src="js/main.js"></script>
</body>
</html>
