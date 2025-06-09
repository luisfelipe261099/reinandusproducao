<?php
// Inicializa o sistema
require_once __DIR__ . '/../includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica se o usuário tem permissão para visualizar chamados
exigirPermissao('chamados', 'visualizar');

// Instancia o banco de dados
$db = Database::getInstance();

// Verifica se o ID da solicitação foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setMensagem('erro', 'ID da solicitação não informado.');
    redirect('index.php?view=chamados_site');
    exit;
}

$id = (int)$_GET['id'];

// Busca a solicitação
$sql = "SELECT * FROM solicitacoes_site WHERE id = ?";
$solicitacao = $db->fetchOne($sql, [$id]);

if (!$solicitacao) {
    setMensagem('erro', 'Solicitação não encontrada.');
    redirect('index.php?view=chamados_site');
    exit;
}

// Busca os emails enviados para esta solicitação
$sql = "SELECT e.*, u.nome as usuario_nome 
        FROM emails_enviados e 
        LEFT JOIN usuarios u ON e.usuario_id = u.id 
        WHERE e.solicitacao_id = ? 
        ORDER BY e.data_envio DESC";
$emails = $db->fetchAll($sql, [$id]);

// Define o título da página
$titulo_pagina = 'Histórico de Emails - ' . $solicitacao['protocolo'];

// Inicia o buffer de saída
ob_start();
?>

<div class="bg-white p-6 rounded-lg shadow-md">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold"><?php echo $titulo_pagina; ?></h1>
            <p class="text-gray-600">Solicitação: <?php echo htmlspecialchars($solicitacao['protocolo']); ?></p>
        </div>
        <div>
            <a href="visualizar_site.php?id=<?php echo $id; ?>" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-2 px-4 rounded inline-flex items-center mr-2">
                <i class="fas fa-arrow-left mr-2"></i> Voltar
            </a>
            <a href="index.php?view=chamados_site" class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded inline-flex items-center">
                <i class="fas fa-list mr-2"></i> Listar Chamados
            </a>
        </div>
    </div>

    <!-- Informações da Solicitação -->
    <div class="bg-white shadow-md rounded-lg p-6 mb-6">
        <h2 class="text-lg font-medium text-gray-900 mb-4">Informações da Solicitação</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-2">Empresa</h3>
                <p class="text-base font-medium text-gray-900"><?php echo htmlspecialchars($solicitacao['nome_empresa']); ?></p>
                <p class="text-sm text-gray-600">CNPJ: <?php echo htmlspecialchars($solicitacao['cnpj']); ?></p>
            </div>
            
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-2">Solicitante</h3>
                <p class="text-base font-medium text-gray-900"><?php echo htmlspecialchars($solicitacao['nome_solicitante']); ?></p>
                <p class="text-sm text-gray-600">
                    Email: <?php echo htmlspecialchars($solicitacao['email']); ?><br>
                    Telefone: <?php echo htmlspecialchars($solicitacao['telefone']); ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Lista de Emails Enviados -->
    <div class="bg-white shadow-md rounded-lg p-6">
        <h2 class="text-lg font-medium text-gray-900 mb-4">Emails Enviados</h2>
        
        <?php if (empty($emails)): ?>
        <div class="bg-yellow-50 p-4 rounded-md text-yellow-800">
            <p class="flex items-center">
                <i class="fas fa-exclamation-circle mr-2"></i>
                Nenhum email foi enviado para esta solicitação ainda.
            </p>
        </div>
        <?php else: ?>
        <div class="space-y-6">
            <?php foreach ($emails as $email): ?>
            <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50">
                <div class="flex justify-between items-start mb-2">
                    <h3 class="text-md font-medium text-gray-900"><?php echo htmlspecialchars($email['assunto']); ?></h3>
                    <span class="text-sm text-gray-500"><?php echo date('d/m/Y H:i', strtotime($email['data_envio'])); ?></span>
                </div>
                
                <div class="text-sm text-gray-700 mb-3 whitespace-pre-line"><?php echo htmlspecialchars($email['mensagem']); ?></div>
                
                <div class="flex justify-between items-center">
                    <div>
                        <?php if ($email['arquivo_nome']): ?>
                        <span class="inline-flex items-center text-sm text-blue-600">
                            <i class="fas fa-paperclip mr-1"></i>
                            <?php echo htmlspecialchars($email['arquivo_nome']); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="text-sm text-gray-500">
                        Enviado por: <?php echo htmlspecialchars($email['usuario_nome']); ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <div class="mt-6">
            <a href="visualizar_site.php?id=<?php echo $id; ?>" class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded inline-flex items-center">
                <i class="fas fa-paper-plane mr-2"></i> Enviar Novo Email
            </a>
        </div>
    </div>
</div>

<?php
// Obtém o conteúdo do buffer e limpa
$conteudo = ob_get_clean();

// Inclui o template
include __DIR__ . '/template.php';
?>
