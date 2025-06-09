<?php
// Obtém os dados do chamado
$sql = "SELECT c.*, cc.nome as categoria_nome, cc.cor as categoria_cor, cc.icone as categoria_icone,
               u_solicitante.nome as solicitante_nome, u_responsavel.nome as responsavel_nome,
               p.nome as polo_nome, a.nome as aluno_nome
        FROM chamados c
        JOIN categorias_chamados cc ON c.categoria_id = cc.id
        JOIN usuarios u_solicitante ON c.solicitante_id = u_solicitante.id
        LEFT JOIN usuarios u_responsavel ON c.responsavel_id = u_responsavel.id
        LEFT JOIN polos p ON c.polo_id = p.id
        LEFT JOIN alunos a ON c.aluno_id = a.id
        WHERE c.id = ?";
$chamado = $db->fetchOne($sql, [$id]);

// Verifica se o chamado existe
if (!$chamado) {
    $_SESSION['mensagem'] = 'Chamado não encontrado.';
    $_SESSION['mensagem_tipo'] = 'erro';
    header('Location: chamados.php');
    exit;
}

// Verifica se o usuário tem permissão para visualizar o chamado
if ($is_polo && $chamado['polo_id'] != $polo_id) {
    $_SESSION['mensagem'] = 'Você não tem permissão para visualizar este chamado.';
    $_SESSION['mensagem_tipo'] = 'erro';
    header('Location: chamados.php');
    exit;
}

// Obtém as respostas do chamado
$sql = "SELECT cr.*, u.nome as usuario_nome, u.tipo as usuario_tipo
        FROM chamados_respostas cr
        JOIN usuarios u ON cr.usuario_id = u.id
        WHERE cr.chamado_id = ?
        ORDER BY cr.data_resposta ASC";
$respostas = $db->fetchAll($sql, [$id]);

// Obtém os anexos do chamado
$sql = "SELECT ca.*, u.nome as usuario_nome
        FROM chamados_anexos ca
        JOIN usuarios u ON ca.usuario_id = u.id
        WHERE ca.chamado_id = ? AND ca.resposta_id IS NULL
        ORDER BY ca.created_at ASC";
$anexos = $db->fetchAll($sql, [$id]);

// Obtém os documentos solicitados (se for um chamado de solicitação de documento)
$documentos = [];
if (strpos($chamado['categoria_nome'], 'Solicitação de') !== false) {
    $sql = "SELECT cd.*, td.nome as tipo_documento_nome, a.nome as aluno_nome
            FROM chamados_documentos cd
            JOIN tipos_documentos td ON cd.tipo_documento_id = td.id
            JOIN alunos a ON cd.aluno_id = a.id
            WHERE cd.chamado_id = ?
            ORDER BY cd.created_at ASC";
    $documentos = $db->fetchAll($sql, [$id]);
}

// Define o título da página
$titulo_pagina = 'Visualizar Chamado: ' . $chamado['codigo'];

// Inclui o cabeçalho
include __DIR__ . '/../../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800"><?php echo $titulo_pagina; ?></h1>
        
        <div class="flex space-x-2">
            <a href="chamados.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
                <i class="fas fa-arrow-left mr-2"></i> Voltar
            </a>
            
            <?php if ($chamado['status'] !== 'fechado' && $chamado['status'] !== 'cancelado'): ?>
            <a href="chamados.php?action=responder&id=<?php echo $id; ?>" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                <i class="fas fa-reply mr-2"></i> Responder
            </a>
            <?php endif; ?>
            
            <?php if (($permissoes['nivel_acesso'] === 'editar' || $permissoes['nivel_acesso'] === 'total') && $chamado['status'] !== 'fechado'): ?>
            <a href="chamados.php?action=alterar_status&id=<?php echo $id; ?>" class="bg-yellow-600 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded">
                <i class="fas fa-edit mr-2"></i> Alterar Status
            </a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Detalhes do Chamado -->
    <div class="bg-white shadow-md rounded-lg p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div>
                <h2 class="text-lg font-semibold mb-4">Informações Gerais</h2>
                
                <div class="mb-3">
                    <span class="block text-sm font-medium text-gray-700">Código:</span>
                    <span class="block text-sm text-gray-900"><?php echo htmlspecialchars($chamado['codigo']); ?></span>
                </div>
                
                <div class="mb-3">
                    <span class="block text-sm font-medium text-gray-700">Categoria:</span>
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" style="background-color: <?php echo $chamado['categoria_cor']; ?>; color: white;">
                        <i class="fas fa-<?php echo $chamado['categoria_icone']; ?> mr-1"></i>
                        <?php echo htmlspecialchars($chamado['categoria_nome']); ?>
                    </span>
                </div>
                
                <div class="mb-3">
                    <span class="block text-sm font-medium text-gray-700">Status:</span>
                    <?php
                    $status_class = '';
                    $status_text = '';
                    
                    switch ($chamado['status']) {
                        case 'aberto':
                            $status_class = 'bg-blue-100 text-blue-800';
                            $status_text = 'Aberto';
                            break;
                        case 'em_andamento':
                            $status_class = 'bg-yellow-100 text-yellow-800';
                            $status_text = 'Em Andamento';
                            break;
                        case 'aguardando_resposta':
                            $status_class = 'bg-purple-100 text-purple-800';
                            $status_text = 'Aguardando Resposta';
                            break;
                        case 'aguardando_aprovacao':
                            $status_class = 'bg-indigo-100 text-indigo-800';
                            $status_text = 'Aguardando Aprovação';
                            break;
                        case 'resolvido':
                            $status_class = 'bg-green-100 text-green-800';
                            $status_text = 'Resolvido';
                            break;
                        case 'cancelado':
                            $status_class = 'bg-red-100 text-red-800';
                            $status_text = 'Cancelado';
                            break;
                        case 'fechado':
                            $status_class = 'bg-gray-100 text-gray-800';
                            $status_text = 'Fechado';
                            break;
                    }
                    ?>
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                        <?php echo $status_text; ?>
                    </span>
                </div>
                
                <div class="mb-3">
                    <span class="block text-sm font-medium text-gray-700">Prioridade:</span>
                    <?php
                    $prioridade_class = '';
                    $prioridade_text = '';
                    
                    switch ($chamado['prioridade']) {
                        case 'baixa':
                            $prioridade_class = 'bg-green-100 text-green-800';
                            $prioridade_text = 'Baixa';
                            break;
                        case 'media':
                            $prioridade_class = 'bg-yellow-100 text-yellow-800';
                            $prioridade_text = 'Média';
                            break;
                        case 'alta':
                            $prioridade_class = 'bg-orange-100 text-orange-800';
                            $prioridade_text = 'Alta';
                            break;
                        case 'urgente':
                            $prioridade_class = 'bg-red-100 text-red-800';
                            $prioridade_text = 'Urgente';
                            break;
                    }
                    ?>
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $prioridade_class; ?>">
                        <?php echo $prioridade_text; ?>
                    </span>
                </div>
            </div>
            
            <div>
                <h2 class="text-lg font-semibold mb-4">Datas</h2>
                
                <div class="mb-3">
                    <span class="block text-sm font-medium text-gray-700">Data de Abertura:</span>
                    <span class="block text-sm text-gray-900"><?php echo date('d/m/Y H:i', strtotime($chamado['data_abertura'])); ?></span>
                </div>
                
                <?php if ($chamado['data_ultima_atualizacao']): ?>
                <div class="mb-3">
                    <span class="block text-sm font-medium text-gray-700">Última Atualização:</span>
                    <span class="block text-sm text-gray-900"><?php echo date('d/m/Y H:i', strtotime($chamado['data_ultima_atualizacao'])); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($chamado['data_fechamento']): ?>
                <div class="mb-3">
                    <span class="block text-sm font-medium text-gray-700">Data de Fechamento:</span>
                    <span class="block text-sm text-gray-900"><?php echo date('d/m/Y H:i', strtotime($chamado['data_fechamento'])); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($chamado['tempo_resolucao']): ?>
                <div class="mb-3">
                    <span class="block text-sm font-medium text-gray-700">Tempo de Resolução:</span>
                    <span class="block text-sm text-gray-900">
                        <?php
                        $horas = floor($chamado['tempo_resolucao'] / 60);
                        $minutos = $chamado['tempo_resolucao'] % 60;
                        echo $horas . 'h ' . $minutos . 'min';
                        ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>
            
            <div>
                <h2 class="text-lg font-semibold mb-4">Responsáveis</h2>
                
                <div class="mb-3">
                    <span class="block text-sm font-medium text-gray-700">Solicitante:</span>
                    <span class="block text-sm text-gray-900"><?php echo htmlspecialchars($chamado['solicitante_nome']); ?></span>
                </div>
                
                <?php if ($chamado['responsavel_id']): ?>
                <div class="mb-3">
                    <span class="block text-sm font-medium text-gray-700">Responsável:</span>
                    <span class="block text-sm text-gray-900"><?php echo htmlspecialchars($chamado['responsavel_nome']); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($chamado['departamento']): ?>
                <div class="mb-3">
                    <span class="block text-sm font-medium text-gray-700">Departamento:</span>
                    <span class="block text-sm text-gray-900">
                        <?php
                        switch ($chamado['departamento']) {
                            case 'secretaria':
                                echo 'Secretaria';
                                break;
                            case 'financeiro':
                                echo 'Financeiro';
                                break;
                            case 'suporte':
                                echo 'Suporte';
                                break;
                            case 'diretoria':
                                echo 'Diretoria';
                                break;
                        }
                        ?>
                    </span>
                </div>
                <?php endif; ?>
                
                <?php if ($chamado['polo_id']): ?>
                <div class="mb-3">
                    <span class="block text-sm font-medium text-gray-700">Polo:</span>
                    <span class="block text-sm text-gray-900"><?php echo htmlspecialchars($chamado['polo_nome']); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($chamado['aluno_id']): ?>
                <div class="mb-3">
                    <span class="block text-sm font-medium text-gray-700">Aluno:</span>
                    <span class="block text-sm text-gray-900"><?php echo htmlspecialchars($chamado['aluno_nome']); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="mt-6">
            <h2 class="text-lg font-semibold mb-2">Título</h2>
            <p class="text-gray-900"><?php echo htmlspecialchars($chamado['titulo']); ?></p>
        </div>
        
        <div class="mt-6">
            <h2 class="text-lg font-semibold mb-2">Descrição</h2>
            <div class="bg-gray-50 p-4 rounded-md">
                <p class="text-gray-900 whitespace-pre-line"><?php echo htmlspecialchars($chamado['descricao']); ?></p>
            </div>
        </div>
        
        <?php if (!empty($anexos)): ?>
        <div class="mt-6">
            <h2 class="text-lg font-semibold mb-2">Anexos</h2>
            <ul class="divide-y divide-gray-200">
                <?php foreach ($anexos as $anexo): ?>
                <li class="py-2">
                    <a href="<?php echo htmlspecialchars($anexo['caminho_arquivo']); ?>" target="_blank" class="text-blue-600 hover:text-blue-900">
                        <i class="fas fa-paperclip mr-2"></i>
                        <?php echo htmlspecialchars($anexo['nome_arquivo']); ?>
                    </a>
                    <span class="text-xs text-gray-500 ml-2">
                        (<?php echo formatarTamanhoArquivo($anexo['tamanho_arquivo']); ?>)
                    </span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($documentos)): ?>
        <div class="mt-6">
            <h2 class="text-lg font-semibold mb-2">Documentos Solicitados</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aluno</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantidade</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valor</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($documentos as $documento): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo htmlspecialchars($documento['tipo_documento_nome']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo htmlspecialchars($documento['aluno_nome']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo $documento['quantidade']; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                R$ <?php echo number_format($documento['valor_total'], 2, ',', '.'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                $status_class = '';
                                $status_text = '';
                                
                                switch ($documento['status']) {
                                    case 'solicitado':
                                        $status_class = 'bg-blue-100 text-blue-800';
                                        $status_text = 'Solicitado';
                                        break;
                                    case 'processando':
                                        $status_class = 'bg-yellow-100 text-yellow-800';
                                        $status_text = 'Processando';
                                        break;
                                    case 'pronto':
                                        $status_class = 'bg-green-100 text-green-800';
                                        $status_text = 'Pronto';
                                        break;
                                    case 'entregue':
                                        $status_class = 'bg-indigo-100 text-indigo-800';
                                        $status_text = 'Entregue';
                                        break;
                                    case 'cancelado':
                                        $status_class = 'bg-red-100 text-red-800';
                                        $status_text = 'Cancelado';
                                        break;
                                }
                                ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                                    <?php echo $status_text; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Respostas do Chamado -->
    <div class="bg-white shadow-md rounded-lg p-6">
        <h2 class="text-lg font-semibold mb-4">Histórico de Respostas</h2>
        
        <?php if (empty($respostas)): ?>
        <p class="text-gray-500">Nenhuma resposta registrada.</p>
        <?php else: ?>
        <div class="space-y-6">
            <?php foreach ($respostas as $resposta): ?>
            <?php
            // Verifica se a resposta é visível para o solicitante
            if (!$resposta['visivel_solicitante'] && $is_polo) {
                continue;
            }
            
            // Define as classes CSS com base no tipo de resposta
            $resposta_class = '';
            $resposta_bg = '';
            
            switch ($resposta['tipo']) {
                case 'resposta':
                    $resposta_class = 'border-blue-500';
                    $resposta_bg = 'bg-blue-50';
                    break;
                case 'nota_interna':
                    $resposta_class = 'border-yellow-500';
                    $resposta_bg = 'bg-yellow-50';
                    break;
                case 'alteracao_status':
                    $resposta_class = 'border-purple-500';
                    $resposta_bg = 'bg-purple-50';
                    break;
                case 'sistema':
                    $resposta_class = 'border-gray-500';
                    $resposta_bg = 'bg-gray-50';
                    break;
            }
            ?>
            <div class="border-l-4 <?php echo $resposta_class; ?> pl-4">
                <div class="flex justify-between items-start">
                    <div>
                        <span class="font-medium text-gray-900"><?php echo htmlspecialchars($resposta['usuario_nome']); ?></span>
                        <span class="text-xs text-gray-500 ml-2">
                            <?php
                            switch ($resposta['usuario_tipo']) {
                                case 'admin_master':
                                    echo 'Administrador';
                                    break;
                                case 'diretoria':
                                    echo 'Diretoria';
                                    break;
                                case 'secretaria_academica':
                                case 'secretaria_documentos':
                                    echo 'Secretaria';
                                    break;
                                case 'financeiro':
                                    echo 'Financeiro';
                                    break;
                                case 'polo':
                                    echo 'Polo';
                                    break;
                                case 'professor':
                                    echo 'Professor';
                                    break;
                                case 'aluno':
                                    echo 'Aluno';
                                    break;
                                default:
                                    echo $resposta['usuario_tipo'];
                                    break;
                            }
                            ?>
                        </span>
                    </div>
                    <span class="text-xs text-gray-500">
                        <?php echo date('d/m/Y H:i', strtotime($resposta['data_resposta'])); ?>
                    </span>
                </div>
                
                <?php if ($resposta['tipo'] === 'nota_interna'): ?>
                <div class="mt-1 text-xs text-yellow-700 font-semibold">
                    <i class="fas fa-eye-slash mr-1"></i> Nota Interna (não visível para o solicitante)
                </div>
                <?php endif; ?>
                
                <div class="mt-2 <?php echo $resposta_bg; ?> p-3 rounded-md">
                    <p class="text-gray-900 whitespace-pre-line"><?php echo htmlspecialchars($resposta['mensagem']); ?></p>
                </div>
                
                <?php
                // Obtém os anexos da resposta
                $sql = "SELECT ca.*, u.nome as usuario_nome
                        FROM chamados_anexos ca
                        JOIN usuarios u ON ca.usuario_id = u.id
                        WHERE ca.resposta_id = ?
                        ORDER BY ca.created_at ASC";
                $anexos_resposta = $db->fetchAll($sql, [$resposta['id']]);
                
                if (!empty($anexos_resposta)):
                ?>
                <div class="mt-2">
                    <h4 class="text-sm font-medium text-gray-700 mb-1">Anexos:</h4>
                    <ul class="space-y-1">
                        <?php foreach ($anexos_resposta as $anexo): ?>
                        <li>
                            <a href="<?php echo htmlspecialchars($anexo['caminho_arquivo']); ?>" target="_blank" class="text-blue-600 hover:text-blue-900 text-sm">
                                <i class="fas fa-paperclip mr-1"></i>
                                <?php echo htmlspecialchars($anexo['nome_arquivo']); ?>
                            </a>
                            <span class="text-xs text-gray-500 ml-1">
                                (<?php echo formatarTamanhoArquivo($anexo['tamanho_arquivo']); ?>)
                            </span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <?php if ($chamado['status'] !== 'fechado' && $chamado['status'] !== 'cancelado'): ?>
        <div class="mt-6">
            <a href="chamados.php?action=responder&id=<?php echo $id; ?>" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                <i class="fas fa-reply mr-2"></i> Responder
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Função para formatar o tamanho do arquivo
function formatarTamanhoArquivo($tamanho) {
    if ($tamanho < 1024) {
        return $tamanho . ' B';
    } elseif ($tamanho < 1048576) {
        return round($tamanho / 1024, 2) . ' KB';
    } elseif ($tamanho < 1073741824) {
        return round($tamanho / 1048576, 2) . ' MB';
    } else {
        return round($tamanho / 1073741824, 2) . ' GB';
    }
}

// Inclui o rodapé
include __DIR__ . '/../../includes/footer.php';
?>
