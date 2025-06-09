<?php
// Inicializa o sistema
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/chamados_functions.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica se o usuário tem permissão para acessar o módulo de chamados
exigirPermissao('chamados', 'visualizar');

// Instancia o banco de dados
$db = Database::getInstance();

// Verifica se o ID foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setMensagem('erro', 'ID da solicitação não informado.');
    redirect('index.php?view=chamados_site');
    exit;
}

$id = (int)$_GET['id'];

// Busca a solicitação do site
$sql = "SELECT * FROM solicitacoes_site WHERE id = ?";
$solicitacao = $db->fetchOne($sql, [$id]);

if (!$solicitacao) {
    setMensagem('erro', 'Solicitação não encontrada.');
    redirect('index.php?view=chamados_site');
    exit;
}

// Define o título da página
$titulo_pagina = 'Detalhes da Solicitação do Site';

// Inicia o buffer de saída
ob_start();
?>

<div class="bg-white p-6 rounded-lg shadow-md">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold"><?php echo $titulo_pagina; ?></h1>
            <p class="text-gray-600">Protocolo: <?php echo htmlspecialchars($solicitacao['protocolo']); ?></p>
        </div>
        <div>
            <a href="historico_emails.php?id=<?php echo $solicitacao['id']; ?>" class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded inline-flex items-center mr-2">
                <i class="fas fa-envelope mr-2"></i> Histórico de Emails
            </a>
            <a href="index.php?view=chamados_site" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-2 px-4 rounded inline-flex items-center">
                <i class="fas fa-arrow-left mr-2"></i> Voltar
            </a>
        </div>
    </div>

    <?php
    // Exibe mensagens de erro do formulário
    if (isset($_SESSION['form_errors']) && !empty($_SESSION['form_errors'])) {
        echo '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">';
        echo '<ul class="list-disc pl-5">';
        foreach ($_SESSION['form_errors'] as $error) {
            echo '<li>' . htmlspecialchars($error) . '</li>';
        }
        echo '</ul>';
        echo '</div>';
        unset($_SESSION['form_errors']);
    }
    ?>

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
                    Telefone: <?php echo htmlspecialchars($solicitacao['telefone']); ?><br>
                    Email: <?php echo htmlspecialchars($solicitacao['email']); ?>
                </p>
            </div>

            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-2">Tipo de Solicitação</h3>
                <p class="text-base font-medium text-gray-900"><?php echo htmlspecialchars(ucfirst($solicitacao['tipo_solicitacao'])); ?></p>
                <p class="text-sm text-gray-600">Quantidade: <?php echo htmlspecialchars($solicitacao['quantidade']); ?></p>
            </div>

            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-2">Status</h3>
                <?php
                $status_class = '';
                switch ($solicitacao['status']) {
                    case 'Pendente':
                        $status_class = 'bg-yellow-100 text-yellow-800';
                        break;
                    case 'Em Análise':
                        $status_class = 'bg-blue-100 text-blue-800';
                        break;
                    case 'Concluído':
                        $status_class = 'bg-green-100 text-green-800';
                        break;
                    case 'Cancelado':
                        $status_class = 'bg-red-100 text-red-800';
                        break;
                    default:
                        $status_class = 'bg-gray-100 text-gray-800';
                }
                ?>
                <span class="px-2 py-1 inline-flex text-sm font-semibold rounded-full <?php echo $status_class; ?>">
                    <?php echo htmlspecialchars($solicitacao['status']); ?>
                </span>
                <p class="text-sm text-gray-600 mt-2">Data: <?php echo date('d/m/Y H:i', strtotime($solicitacao['data_solicitacao'])); ?></p>
            </div>

            <?php if ($solicitacao['link_planilha']): ?>
            <div class="md:col-span-2">
                <h3 class="text-sm font-medium text-gray-500 mb-2">Link da Planilha</h3>
                <a href="<?php echo htmlspecialchars($solicitacao['link_planilha']); ?>" target="_blank" class="text-blue-600 hover:text-blue-800 inline-flex items-center">
                    <i class="fas fa-file-excel mr-2"></i> Abrir Planilha
                </a>
            </div>
            <?php endif; ?>

            <?php if ($solicitacao['observacao']): ?>
            <div class="md:col-span-2">
                <h3 class="text-sm font-medium text-gray-500 mb-2">Observação</h3>
                <div class="bg-gray-50 p-4 rounded-md">
                    <p class="text-sm text-gray-700 whitespace-pre-line"><?php echo htmlspecialchars($solicitacao['observacao']); ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Formulário para atualizar status -->
    <div class="bg-white shadow-md rounded-lg p-6 mb-6">
        <h2 class="text-lg font-medium text-gray-900 mb-4">Atualizar Status</h2>

        <form action="atualizar_status_site.php" method="post" class="space-y-4">
            <input type="hidden" name="id" value="<?php echo $solicitacao['id']; ?>">

            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Novo Status</label>
                <select id="status" name="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    <option value="Pendente" <?php echo $solicitacao['status'] == 'Pendente' ? 'selected' : ''; ?>>Pendente</option>
                    <option value="Em Análise" <?php echo $solicitacao['status'] == 'Em Análise' ? 'selected' : ''; ?>>Em Análise</option>
                    <option value="Concluído" <?php echo $solicitacao['status'] == 'Concluído' ? 'selected' : ''; ?>>Concluído</option>
                    <option value="Cancelado" <?php echo $solicitacao['status'] == 'Cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                </select>
            </div>

            <div>
                <label for="observacao" class="block text-sm font-medium text-gray-700 mb-1">Observação</label>
                <textarea id="observacao" name="observacao" rows="4" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50"><?php echo htmlspecialchars($solicitacao['observacao']); ?></textarea>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded">
                    <i class="fas fa-save mr-2"></i> Salvar Alterações
                </button>
            </div>
        </form>
    </div>

    <!-- Formulário para enviar resposta por email -->
    <div class="bg-white shadow-md rounded-lg p-6">
        <h2 class="text-lg font-medium text-gray-900 mb-4">Enviar Resposta por Email</h2>

        <form action="enviar_resposta_site.php" method="post" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="id" value="<?php echo $solicitacao['id']; ?>">
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($solicitacao['email']); ?>">
            <input type="hidden" name="nome_solicitante" value="<?php echo htmlspecialchars($solicitacao['nome_solicitante']); ?>">
            <input type="hidden" name="protocolo" value="<?php echo htmlspecialchars($solicitacao['protocolo']); ?>">

            <div>
                <label for="assunto" class="block text-sm font-medium text-gray-700 mb-1">Assunto do Email</label>
                <input type="text" id="assunto" name="assunto" value="Resposta à Solicitação: <?php echo htmlspecialchars($solicitacao['protocolo']); ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
            </div>

            <div>
                <label for="mensagem" class="block text-sm font-medium text-gray-700 mb-1">Mensagem</label>
                <textarea id="mensagem" name="mensagem" rows="6" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">Prezado(a) <?php echo htmlspecialchars($solicitacao['nome_solicitante']); ?>,

Em resposta à sua solicitação (Protocolo: <?php echo htmlspecialchars($solicitacao['protocolo']); ?>), agradecemos pelo contato.

<?php if ($solicitacao['tipo_solicitacao'] == 'orçamento'): ?>Conforme solicitado, estamos enviando as informações referentes ao seu orçamento. Caso necessite de esclarecimentos adicionais, não hesite em nos contatar.<?php else: ?>Sua solicitação foi processada com sucesso e estamos enviando as informações solicitadas. Se precisar de qualquer esclarecimento adicional, estamos à disposição.<?php endif; ?>

Atenciosamente,
Equipe FaCiencia</textarea>
            </div>

            <div>
                <label for="drive_link" class="block text-sm font-medium text-gray-700 mb-1">Link do Google Drive (opcional)</label>
                <input type="text" id="drive_link" name="drive_link" placeholder="https://drive.google.com/file/d/..." class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                <p class="text-xs text-gray-500 mt-1">Insira um link compartilhável do Google Drive</p>
            </div>

            <div>
                <label for="arquivo" class="block text-sm font-medium text-gray-700 mb-1">Anexar Arquivo (opcional)</label>
                <input type="file" id="arquivo" name="arquivo" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                <p class="text-xs text-gray-500 mt-1">Formatos aceitos: ZIP, PDF, DOC, DOCX, XLS, XLSX (Tamanho máximo: 10MB)</p>
            </div>

            <div class="flex justify-between">
                <div class="flex items-center">
                    <input type="checkbox" id="atualizar_status" name="atualizar_status" value="1" checked class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <label for="atualizar_status" class="ml-2 text-sm text-gray-700">Atualizar status para "Concluído" após envio</label>
                </div>

                <button type="submit" class="bg-green-500 hover:bg-green-600 text-white font-medium py-2 px-4 rounded">
                    <i class="fas fa-paper-plane mr-2"></i> Enviar Email
                </button>
            </div>
        </form>
    </div>
</div>

<?php
// Obtém o conteúdo do buffer e limpa
$conteudo = ob_get_clean();

// Inclui o template
include __DIR__ . '/template.php';
?>
