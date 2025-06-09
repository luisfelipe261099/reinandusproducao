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

// Verifica se o usuário tem permissão para alterar o status do chamado
if ($is_polo) {
    $_SESSION['mensagem'] = 'Você não tem permissão para alterar o status de chamados.';
    $_SESSION['mensagem_tipo'] = 'erro';
    header('Location: chamados.php');
    exit;
}

// Verifica se o chamado está fechado ou cancelado
if ($chamado['status'] === 'fechado' || $chamado['status'] === 'cancelado') {
    $_SESSION['mensagem'] = 'Não é possível alterar o status de um chamado fechado ou cancelado.';
    $_SESSION['mensagem_tipo'] = 'erro';
    header('Location: chamados.php?action=visualizar&id=' . $id);
    exit;
}

// Processa o formulário de alteração de status
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtém os dados do formulário
    $novo_status = isset($_POST['novo_status']) ? $_POST['novo_status'] : '';
    $observacao = isset($_POST['observacao']) ? trim($_POST['observacao']) : '';
    $responsavel_id = isset($_POST['responsavel_id']) && !empty($_POST['responsavel_id']) ? (int)$_POST['responsavel_id'] : null;
    
    // Validação básica
    $erros = [];
    
    if (!in_array($novo_status, ['aberto', 'em_andamento', 'aguardando_resposta', 'aguardando_aprovacao', 'resolvido', 'cancelado', 'fechado'])) {
        $erros[] = 'Status inválido.';
    }
    
    // Se houver erros, redireciona de volta para o formulário
    if (!empty($erros)) {
        $_SESSION['mensagem'] = implode('<br>', $erros);
        $_SESSION['mensagem_tipo'] = 'erro';
        header('Location: chamados.php?action=alterar_status&id=' . $id);
        exit;
    }
    
    try {
        // Inicia uma transação
        $db->beginTransaction();
        
        // Atualiza o status do chamado
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
        
        // Atualiza o responsável, se informado
        if ($responsavel_id) {
            $sql .= ", responsavel_id = ?";
            $params[] = $responsavel_id;
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
        
        if (!empty($observacao)) {
            $mensagem_status .= "\n\nObservação: {$observacao}";
        }
        
        if ($responsavel_id) {
            $sql_usuario = "SELECT nome FROM usuarios WHERE id = ?";
            $usuario = $db->fetchOne($sql_usuario, [$responsavel_id]);
            
            if ($usuario) {
                $mensagem_status .= "\n\nResponsável atribuído: {$usuario['nome']}";
            }
        }
        
        $params_status = [
            $id,
            Auth::getUserId(),
            $mensagem_status
        ];
        
        $db->execute($sql, $params_status);
        
        // Confirma a transação
        $db->commit();
        
        $_SESSION['mensagem'] = 'Status do chamado alterado com sucesso.';
        $_SESSION['mensagem_tipo'] = 'sucesso';
        header('Location: chamados.php?action=visualizar&id=' . $id);
        exit;
    } catch (Exception $e) {
        // Desfaz a transação em caso de erro
        $db->rollBack();
        
        $_SESSION['mensagem'] = 'Erro ao alterar o status do chamado: ' . $e->getMessage();
        $_SESSION['mensagem_tipo'] = 'erro';
        header('Location: chamados.php?action=alterar_status&id=' . $id);
        exit;
    }
}

// Obtém a lista de usuários para atribuir como responsável
$sql_usuarios = "SELECT id, nome, tipo FROM usuarios WHERE status = 'ativo' AND tipo != 'polo' AND tipo != 'aluno' ORDER BY nome ASC";
$usuarios = $db->fetchAll($sql_usuarios);
?>

<div class="bg-white shadow-md rounded-lg p-6">
    <h2 class="text-lg font-semibold mb-4">Alterar Status do Chamado</h2>
    
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
    
    <form action="chamados.php?action=alterar_status&id=<?php echo $id; ?>" method="POST">
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-1">Status Atual</label>
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
        
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-1">Novo Status *</label>
            <select name="novo_status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" required>
                <option value="aberto" <?php echo $chamado['status'] === 'aberto' ? 'selected' : ''; ?>>Aberto</option>
                <option value="em_andamento" <?php echo $chamado['status'] === 'em_andamento' ? 'selected' : ''; ?>>Em Andamento</option>
                <option value="aguardando_resposta" <?php echo $chamado['status'] === 'aguardando_resposta' ? 'selected' : ''; ?>>Aguardando Resposta</option>
                <option value="aguardando_aprovacao" <?php echo $chamado['status'] === 'aguardando_aprovacao' ? 'selected' : ''; ?>>Aguardando Aprovação</option>
                <option value="resolvido" <?php echo $chamado['status'] === 'resolvido' ? 'selected' : ''; ?>>Resolvido</option>
                <option value="cancelado" <?php echo $chamado['status'] === 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                <option value="fechado" <?php echo $chamado['status'] === 'fechado' ? 'selected' : ''; ?>>Fechado</option>
            </select>
        </div>
        
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-1">Atribuir Responsável</label>
            <select name="responsavel_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                <option value="">Selecione um responsável (opcional)</option>
                <?php foreach ($usuarios as $usuario): ?>
                <option value="<?php echo $usuario['id']; ?>" <?php echo $chamado['responsavel_id'] == $usuario['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($usuario['nome']); ?> (<?php echo ucfirst($usuario['tipo']); ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-1">Observação</label>
            <textarea name="observacao" rows="4" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50"></textarea>
            <p class="text-xs text-gray-500 mt-1">Adicione uma observação sobre a alteração de status (opcional).</p>
        </div>
        
        <div class="flex justify-end">
            <a href="chamados.php?action=visualizar&id=<?php echo $id; ?>" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded mr-2">
                <i class="fas fa-times mr-2"></i> Cancelar
            </a>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                <i class="fas fa-save mr-2"></i> Salvar Alterações
            </button>
        </div>
    </form>
</div>
