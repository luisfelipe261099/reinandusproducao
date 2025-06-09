<?php
// Obtém os dados do chamado
$sql = "SELECT c.*, cc.nome as categoria_nome, cc.cor as categoria_cor, cc.icone as categoria_icone,
               u_solicitante.nome as solicitante_nome, u_responsavel.nome as responsavel_nome,
               p.nome as polo_nome, a.nome as aluno_nome
        FROM chamados c
        LEFT JOIN categorias_chamados cc ON c.categoria_id = cc.id
        LEFT JOIN usuarios u_solicitante ON c.solicitante_id = u_solicitante.id
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

// Verifica se o usuário tem permissão para responder o chamado
if ($is_polo && $chamado['polo_id'] != $polo_id) {
    $_SESSION['mensagem'] = 'Você não tem permissão para responder este chamado.';
    $_SESSION['mensagem_tipo'] = 'erro';
    header('Location: chamados.php');
    exit;
}

// Verifica se o chamado está fechado ou cancelado
if ($chamado['status'] === 'fechado' || $chamado['status'] === 'cancelado') {
    $_SESSION['mensagem'] = 'Não é possível responder a um chamado fechado ou cancelado.';
    $_SESSION['mensagem_tipo'] = 'erro';
    header('Location: chamados.php?action=visualizar&id=' . $id);
    exit;
}

// Processa o formulário de resposta
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtém os dados do formulário
    $mensagem = isset($_POST['mensagem']) ? trim($_POST['mensagem']) : '';
    $tipo = isset($_POST['tipo']) ? $_POST['tipo'] : 'resposta';
    $visivel_solicitante = isset($_POST['visivel_solicitante']) ? (int)$_POST['visivel_solicitante'] : 1;
    $alterar_status = isset($_POST['alterar_status']) ? (int)$_POST['alterar_status'] : 0;
    $novo_status = isset($_POST['novo_status']) ? $_POST['novo_status'] : '';
    
    // Validação básica
    $erros = [];
    
    if (empty($mensagem)) {
        $erros[] = 'A mensagem é obrigatória.';
    }
    
    if (!in_array($tipo, ['resposta', 'nota_interna'])) {
        $erros[] = 'Tipo de resposta inválido.';
    }
    
    if ($alterar_status && !in_array($novo_status, ['aberto', 'em_andamento', 'aguardando_resposta', 'aguardando_aprovacao', 'resolvido', 'cancelado', 'fechado'])) {
        $erros[] = 'Status inválido.';
    }
    
    // Se houver erros, redireciona de volta para o formulário
    if (!empty($erros)) {
        $_SESSION['mensagem'] = implode('<br>', $erros);
        $_SESSION['mensagem_tipo'] = 'erro';
        header('Location: chamados.php?action=responder&id=' . $id);
        exit;
    }
    
    try {
        // Inicia uma transação
        $db->beginTransaction();
        
        // Insere a resposta no banco de dados
        $sql = "INSERT INTO chamados_respostas (
                    chamado_id,
                    usuario_id,
                    mensagem,
                    tipo,
                    visivel_solicitante,
                    data_resposta,
                    created_at,
                    updated_at
                ) VALUES (?, ?, ?, ?, ?, NOW(), NOW(), NOW())";
        
        $params = [
            $id,
            Auth::getUserId(),
            $mensagem,
            $tipo,
            $visivel_solicitante
        ];
        
        $db->execute($sql, $params);
        $resposta_id = $db->lastInsertId();
        
        // Atualiza o status do chamado, se solicitado
        if ($alterar_status) {
            $sql = "UPDATE chamados SET 
                        status = ?,
                        data_ultima_atualizacao = NOW(),
                        updated_at = NOW()";
            
            $params = [$novo_status];
            
            // Se o status for 'resolvido' ou 'fechado', atualiza a data de fechamento
            if ($novo_status === 'resolvido' || $novo_status === 'fechado') {
                $sql .= ", data_fechamento = NOW(), 
                          tempo_resolucao = TIMESTAMPDIFF(MINUTE, data_abertura, NOW())";
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $id;
            
            $db->execute($sql, $params);
            
            // Registra a alteração de status no histórico
            $sql = "INSERT INTO chamados_respostas (
                        chamado_id,
                        usuario_id,
                        mensagem,
                        tipo,
                        visivel_solicitante,
                        data_resposta,
                        created_at,
                        updated_at
                    ) VALUES (?, ?, ?, 'alteracao_status', 1, NOW(), NOW(), NOW())";
            
            $mensagem_status = "Status alterado para '{$novo_status}'.";
            $params_status = [
                $id,
                Auth::getUserId(),
                $mensagem_status
            ];
            
            $db->execute($sql, $params_status);
        } else {
            // Atualiza apenas a data de última atualização
            $sql = "UPDATE chamados SET 
                        data_ultima_atualizacao = NOW(),
                        updated_at = NOW()
                    WHERE id = ?";
            
            $db->execute($sql, [$id]);
        }
        
        // Processa os anexos
        if (isset($_FILES['anexos']) && !empty($_FILES['anexos']['name'][0])) {
            $diretorio_anexos = 'uploads/chamados/' . $id . '/';
            
            // Cria o diretório se não existir
            if (!is_dir($diretorio_anexos)) {
                mkdir($diretorio_anexos, 0755, true);
            }
            
            // Formatos permitidos
            $formatos_permitidos = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png'];
            
            // Tamanho máximo (5MB)
            $tamanho_maximo = 5 * 1024 * 1024;
            
            // Processa cada arquivo
            $total_arquivos = count($_FILES['anexos']['name']);
            
            for ($i = 0; $i < $total_arquivos; $i++) {
                if ($_FILES['anexos']['error'][$i] === UPLOAD_ERR_OK) {
                    $nome_arquivo = $_FILES['anexos']['name'][$i];
                    $tamanho_arquivo = $_FILES['anexos']['size'][$i];
                    $tipo_arquivo = $_FILES['anexos']['type'][$i];
                    $arquivo_temp = $_FILES['anexos']['tmp_name'][$i];
                    
                    // Verifica o tamanho do arquivo
                    if ($tamanho_arquivo > $tamanho_maximo) {
                        continue;
                    }
                    
                    // Verifica a extensão do arquivo
                    $extensao = strtolower(pathinfo($nome_arquivo, PATHINFO_EXTENSION));
                    if (!in_array($extensao, $formatos_permitidos)) {
                        continue;
                    }
                    
                    // Gera um nome único para o arquivo
                    $nome_arquivo_unico = uniqid() . '.' . $extensao;
                    $caminho_arquivo = $diretorio_anexos . $nome_arquivo_unico;
                    
                    // Move o arquivo para o diretório de destino
                    if (move_uploaded_file($arquivo_temp, $caminho_arquivo)) {
                        // Insere o anexo no banco de dados
                        $sql = "INSERT INTO chamados_anexos (
                                    chamado_id,
                                    resposta_id,
                                    nome_arquivo,
                                    caminho_arquivo,
                                    tipo_arquivo,
                                    tamanho_arquivo,
                                    usuario_id,
                                    created_at
                                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
                        
                        $params_anexo = [
                            $id,
                            $resposta_id,
                            $nome_arquivo,
                            $caminho_arquivo,
                            $tipo_arquivo,
                            $tamanho_arquivo,
                            Auth::getUserId()
                        ];
                        
                        $db->execute($sql, $params_anexo);
                    }
                }
            }
        }
        
        // Confirma a transação
        $db->commit();
        
        $_SESSION['mensagem'] = 'Resposta enviada com sucesso.';
        $_SESSION['mensagem_tipo'] = 'sucesso';
        header('Location: chamados.php?action=visualizar&id=' . $id);
        exit;
    } catch (Exception $e) {
        // Desfaz a transação em caso de erro
        $db->rollBack();
        
        $_SESSION['mensagem'] = 'Erro ao enviar a resposta: ' . $e->getMessage();
        $_SESSION['mensagem_tipo'] = 'erro';
        header('Location: chamados.php?action=responder&id=' . $id);
        exit;
    }
}
?>

<div class="bg-white shadow-md rounded-lg p-6">
    <h2 class="text-lg font-semibold mb-4">Responder Chamado</h2>
    
    <div class="mb-6">
        <h3 class="text-md font-medium text-gray-700 mb-2">Informações do Chamado</h3>
        <div class="bg-gray-50 p-4 rounded-md">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($chamado['codigo']); ?> - <?php echo htmlspecialchars($chamado['titulo']); ?></p>
                    <p class="text-xs text-gray-500">
                        Aberto por <?php echo htmlspecialchars($chamado['solicitante_nome']); ?> em <?php echo date('d/m/Y H:i', strtotime($chamado['data_abertura'])); ?>
                    </p>
                </div>
                <div>
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" style="background-color: <?php echo $chamado['categoria_cor']; ?>; color: white;">
                        <i class="fas fa-<?php echo $chamado['categoria_icone']; ?> mr-1"></i>
                        <?php echo htmlspecialchars($chamado['categoria_nome']); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
    
    <form action="chamados.php?action=responder&id=<?php echo $id; ?>" method="POST" enctype="multipart/form-data">
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-1">Mensagem *</label>
            <textarea name="mensagem" rows="6" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" required></textarea>
        </div>
        
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Resposta</label>
            <div class="flex items-center space-x-4">
                <label class="inline-flex items-center">
                    <input type="radio" name="tipo" value="resposta" class="form-radio text-blue-600" checked>
                    <span class="ml-2">Resposta</span>
                </label>
                <?php if (!$is_polo): ?>
                <label class="inline-flex items-center">
                    <input type="radio" name="tipo" value="nota_interna" class="form-radio text-yellow-600">
                    <span class="ml-2">Nota Interna (não visível para o solicitante)</span>
                </label>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (!$is_polo): ?>
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-1">Visibilidade</label>
            <div class="flex items-center space-x-4">
                <label class="inline-flex items-center">
                    <input type="radio" name="visivel_solicitante" value="1" class="form-radio text-blue-600" checked>
                    <span class="ml-2">Visível para o solicitante</span>
                </label>
                <label class="inline-flex items-center">
                    <input type="radio" name="visivel_solicitante" value="0" class="form-radio text-yellow-600">
                    <span class="ml-2">Visível apenas para a equipe interna</span>
                </label>
            </div>
        </div>
        
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-1">Alterar Status do Chamado</label>
            <div class="flex items-center space-x-4">
                <label class="inline-flex items-center">
                    <input type="radio" name="alterar_status" value="0" class="form-radio text-blue-600" checked>
                    <span class="ml-2">Manter status atual (<?php echo $chamado['status']; ?>)</span>
                </label>
                <label class="inline-flex items-center">
                    <input type="radio" name="alterar_status" value="1" class="form-radio text-yellow-600">
                    <span class="ml-2">Alterar status</span>
                </label>
            </div>
            
            <div id="novo_status_container" class="mt-2 hidden">
                <select name="novo_status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    <option value="aberto" <?php echo $chamado['status'] === 'aberto' ? 'selected' : ''; ?>>Aberto</option>
                    <option value="em_andamento" <?php echo $chamado['status'] === 'em_andamento' ? 'selected' : ''; ?>>Em Andamento</option>
                    <option value="aguardando_resposta" <?php echo $chamado['status'] === 'aguardando_resposta' ? 'selected' : ''; ?>>Aguardando Resposta</option>
                    <option value="aguardando_aprovacao" <?php echo $chamado['status'] === 'aguardando_aprovacao' ? 'selected' : ''; ?>>Aguardando Aprovação</option>
                    <option value="resolvido" <?php echo $chamado['status'] === 'resolvido' ? 'selected' : ''; ?>>Resolvido</option>
                    <option value="cancelado" <?php echo $chamado['status'] === 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                    <option value="fechado" <?php echo $chamado['status'] === 'fechado' ? 'selected' : ''; ?>>Fechado</option>
                </select>
            </div>
        </div>
        <?php else: ?>
        <input type="hidden" name="visivel_solicitante" value="1">
        <input type="hidden" name="alterar_status" value="0">
        <?php endif; ?>
        
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-1">Anexos</label>
            <input type="file" name="anexos[]" multiple class="w-full border border-gray-300 rounded-md p-2">
            <p class="text-xs text-gray-500 mt-1">Você pode anexar até 5 arquivos (máximo 5MB cada). Formatos permitidos: PDF, DOC, DOCX, XLS, XLSX, JPG, PNG.</p>
        </div>
        
        <div class="flex justify-end">
            <a href="chamados.php?action=visualizar&id=<?php echo $id; ?>" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded mr-2">
                <i class="fas fa-times mr-2"></i> Cancelar
            </a>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                <i class="fas fa-paper-plane mr-2"></i> Enviar Resposta
            </button>
        </div>
    </form>
</div>

<script>
    // Script para mostrar/ocultar o seletor de status
    document.addEventListener('DOMContentLoaded', function() {
        const alterarStatusRadios = document.querySelectorAll('input[name="alterar_status"]');
        const novoStatusContainer = document.getElementById('novo_status_container');
        
        alterarStatusRadios.forEach(function(radio) {
            radio.addEventListener('change', function() {
                if (this.value === '1') {
                    novoStatusContainer.classList.remove('hidden');
                } else {
                    novoStatusContainer.classList.add('hidden');
                }
            });
        });
    });
</script>
