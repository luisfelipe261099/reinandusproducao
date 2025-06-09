<?php
/**
 * Página de visualização de documentos dos alunos
 * Incluída pelo index.php quando solicitada
 */

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica se o usuário tem permissão de secretaria
if (getUsuarioTipo() !== 'secretaria' && getUsuarioTipo() !== 'admin') {
    setMensagem('erro', 'Você não tem permissão para acessar esta página.');
    redirect('index.php');
    exit;
}

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

// Busca o polo do aluno
if (!empty($aluno['polo_id'])) {
    $sql = "SELECT nome FROM polos WHERE id = ?";
    $polo = $db->fetchOne($sql, [$aluno['polo_id']]);
    $polo_nome = $polo ? $polo['nome'] : 'Polo não encontrado';
} else {
    $polo_nome = 'Não definido';
}

// Busca os documentos do aluno
$sql = "SELECT da.*, tdp.nome as tipo_documento_nome, tdp.obrigatorio
        FROM documentos_alunos da
        JOIN tipos_documentos_pessoais tdp ON da.tipo_documento_id = tdp.id
        WHERE da.aluno_id = ?
        ORDER BY da.created_at DESC";
$documentos = $db->fetchAll($sql, [$aluno_id]);

// Carrega os tipos de documentos obrigatórios que o aluno ainda não enviou
$sql = "SELECT tdp.*
        FROM tipos_documentos_pessoais tdp
        WHERE tdp.obrigatorio = 1 AND tdp.status = 'ativo'
        AND NOT EXISTS (
            SELECT 1 FROM documentos_alunos da
            WHERE da.aluno_id = ? AND da.tipo_documento_id = tdp.id AND da.status != 'rejeitado'
        )
        ORDER BY tdp.nome";
$documentos_pendentes = $db->fetchAll($sql, [$aluno_id]);

// Define o título da página
$titulo_pagina = 'Documentos do Aluno: ' . $aluno['nome'];
?>

<div class="container mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800"><?php echo $titulo_pagina; ?></h1>
        <a href="index.php?page=upload_documento&id=<?php echo $aluno_id; ?>" class="btn-primary">
            <i class="fas fa-upload mr-2"></i> Enviar Documento
        </a>
    </div>

    <!-- Informações do Aluno -->
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Informações do Aluno</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
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
            <div>
                <p class="text-sm text-gray-500">Polo</p>
                <p class="font-medium"><?php echo htmlspecialchars($polo_nome); ?></p>
            </div>
        </div>
    </div>

    <?php if (!empty($documentos_pendentes)): ?>
    <!-- Documentos Pendentes -->
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-triangle text-yellow-400"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-yellow-800">Documentos Obrigatórios Pendentes</h3>
                <div class="mt-2 text-sm text-yellow-700">
                    <p>O aluno possui os seguintes documentos obrigatórios pendentes:</p>
                    <ul class="mt-2 pl-5 list-disc">
                        <?php foreach ($documentos_pendentes as $documento): ?>
                        <li><?php echo htmlspecialchars($documento['nome']); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="index.php?page=upload_documento&id=<?php echo $aluno_id; ?>" class="inline-flex items-center mt-2 text-blue-600 hover:text-blue-800">
                        <i class="fas fa-upload mr-1"></i> Enviar documentos pendentes
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Lista de Documentos -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800">Documentos do Aluno</h2>
        </div>
        <div class="p-6">
            <?php if (empty($documentos)): ?>
            <div class="text-center text-gray-500 py-4">
                <p>Nenhum documento encontrado para este aluno.</p>
                <a href="index.php?page=upload_documento&id=<?php echo $aluno_id; ?>" class="btn-primary inline-block mt-4">Enviar Documento</a>
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo de Documento</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data de Upload</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Obrigatório</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($documentos as $documento): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($documento['tipo_documento_nome']); ?></div>
                                <?php if (!empty($documento['numero_documento'])): ?>
                                <div class="text-sm text-gray-500">Nº <?php echo htmlspecialchars($documento['numero_documento']); ?></div>
                                <?php endif; ?>
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
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo $documento['obrigatorio'] ? 'Sim' : 'Não'; ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex flex-wrap justify-center gap-2">
                                    <a href="uploads/documentos_alunos/<?php echo $documento['arquivo']; ?>" target="_blank" class="inline-flex items-center px-2.5 py-1.5 bg-blue-100 text-blue-800 text-xs font-medium rounded hover:bg-blue-200">
                                        <i class="fas fa-eye mr-1"></i> Visualizar
                                    </a>
                                    
                                    <?php if ($documento['status'] === 'pendente'): ?>
                                    <a href="index.php?page=documentos_aluno&action=aprovar&id=<?php echo $aluno_id; ?>&documento_id=<?php echo $documento['id']; ?>" class="inline-flex items-center px-2.5 py-1.5 bg-green-100 text-green-800 text-xs font-medium rounded hover:bg-green-200">
                                        <i class="fas fa-check mr-1"></i> Aprovar
                                    </a>
                                    <a href="index.php?page=documentos_aluno&action=rejeitar&id=<?php echo $aluno_id; ?>&documento_id=<?php echo $documento['id']; ?>" class="inline-flex items-center px-2.5 py-1.5 bg-red-100 text-red-800 text-xs font-medium rounded hover:bg-red-200">
                                        <i class="fas fa-times mr-1"></i> Rejeitar
                                    </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($documento['status'] === 'rejeitado'): ?>
                                    <a href="index.php?page=upload_documento&id=<?php echo $aluno_id; ?>&tipo=<?php echo $documento['tipo_documento_id']; ?>" class="inline-flex items-center px-2.5 py-1.5 bg-yellow-100 text-yellow-800 text-xs font-medium rounded hover:bg-yellow-200">
                                        <i class="fas fa-redo mr-1"></i> Reenviar
                                    </a>
                                    <?php endif; ?>
                                </div>
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

<?php
// Processa a aprovação ou rejeição de documento
if (isset($_GET['action']) && ($_GET['action'] === 'aprovar' || $_GET['action'] === 'rejeitar')) {
    $documento_id = $_GET['documento_id'] ?? null;
    
    if (!$documento_id) {
        setMensagem('erro', 'ID do documento não informado.');
        redirect('index.php?page=documentos_aluno&id=' . $aluno_id);
        exit;
    }
    
    // Verifica se o documento existe e pertence ao aluno
    $sql = "SELECT * FROM documentos_alunos WHERE id = ? AND aluno_id = ?";
    $documento = $db->fetchOne($sql, [$documento_id, $aluno_id]);
    
    if (!$documento) {
        setMensagem('erro', 'Documento não encontrado ou não pertence ao aluno.');
        redirect('index.php?page=documentos_aluno&id=' . $aluno_id);
        exit;
    }
    
    // Atualiza o status do documento
    $status = $_GET['action'] === 'aprovar' ? 'aprovado' : 'rejeitado';
    $sql = "UPDATE documentos_alunos SET status = ?, updated_at = NOW() WHERE id = ?";
    $db->query($sql, [$status, $documento_id]);
    
    // Redireciona para a visualização dos documentos do aluno
    setMensagem('sucesso', 'Documento ' . ($status === 'aprovado' ? 'aprovado' : 'rejeitado') . ' com sucesso!');
    redirect('index.php?page=documentos_aluno&id=' . $aluno_id);
    exit;
}
?>
