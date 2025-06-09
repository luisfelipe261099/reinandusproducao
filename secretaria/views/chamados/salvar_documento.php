<?php
// Verifica se o usuário é um polo
if (!$is_polo) {
    $_SESSION['mensagem'] = 'Apenas polos podem solicitar documentos.';
    $_SESSION['mensagem_tipo'] = 'erro';
    header('Location: chamados.php');
    exit;
}

// Verifica se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['mensagem'] = 'Método de requisição inválido.';
    $_SESSION['mensagem_tipo'] = 'erro';
    header('Location: chamados.php?action=solicitar_documento');
    exit;
}

// Obtém os dados do formulário
$tipo_documento_id = isset($_POST['tipo_documento_id']) ? (int)$_POST['tipo_documento_id'] : 0;
$aluno_id = isset($_POST['aluno_id']) ? (int)$_POST['aluno_id'] : 0;
$quantidade = isset($_POST['quantidade']) ? (int)$_POST['quantidade'] : 0;
$finalidade = isset($_POST['finalidade']) ? trim($_POST['finalidade']) : '';

// Validação básica
$erros = [];

if (empty($tipo_documento_id)) {
    $erros[] = 'O tipo de documento é obrigatório.';
}

if (empty($aluno_id)) {
    $erros[] = 'O aluno é obrigatório.';
}

if (empty($quantidade) || $quantidade < 1) {
    $erros[] = 'A quantidade deve ser maior que zero.';
}

// Verifica se o tipo de documento existe
if ($tipo_documento_id > 0) {
    $sql = "SELECT * FROM tipos_documentos WHERE id = ? AND status = 'ativo'";
    $tipo_documento = $db->fetchOne($sql, [$tipo_documento_id]);
    
    if (!$tipo_documento) {
        $erros[] = 'Tipo de documento inválido.';
    }
}

// Verifica se o aluno existe e pertence ao polo
if ($aluno_id > 0) {
    $sql = "SELECT a.*, m.id as matricula_id, m.polo_id 
            FROM alunos a 
            JOIN matriculas m ON a.id = m.aluno_id 
            WHERE a.id = ? AND a.status = 'ativo' AND m.status IN ('ativo', 'concluído')";
    $aluno = $db->fetchOne($sql, [$aluno_id]);
    
    if (!$aluno) {
        $erros[] = 'Aluno inválido.';
    } elseif ($aluno['polo_id'] != $polo_id) {
        $erros[] = 'Este aluno não pertence ao seu polo.';
    }
}

// Verifica se o polo tem documentos disponíveis suficientes
$sql = "SELECT SUM(documentos_disponiveis) as total_disponiveis FROM polos_financeiro WHERE polo_id = ?";
$resultado = $db->fetchOne($sql, [$polo_id]);
$total_documentos_disponiveis = $resultado ? (int)$resultado['total_disponiveis'] : 0;

if ($quantidade > $total_documentos_disponiveis) {
    $erros[] = "Você não possui documentos disponíveis suficientes. Disponíveis: {$total_documentos_disponiveis}, Solicitados: {$quantidade}.";
}

// Se houver erros, redireciona de volta para o formulário
if (!empty($erros)) {
    $_SESSION['mensagem'] = implode('<br>', $erros);
    $_SESSION['mensagem_tipo'] = 'erro';
    header('Location: chamados.php?action=solicitar_documento');
    exit;
}

try {
    // Inicia uma transação
    $db->beginTransaction();
    
    // Calcula o valor unitário e total
    $valor_unitario = $tipo_documento['valor'];
    $valor_total = $valor_unitario * $quantidade;
    
    // Obtém a categoria de chamado para solicitação de documentos
    $sql = "SELECT * FROM categorias_chamados WHERE nome LIKE ? AND tipo = 'polo' AND status = 'ativo' LIMIT 1";
    $categoria = $db->fetchOne($sql, ['%' . $tipo_documento['nome'] . '%']);
    
    if (!$categoria) {
        // Se não encontrar uma categoria específica, usa a categoria genérica
        $sql = "SELECT * FROM categorias_chamados WHERE nome LIKE 'Solicitação de%' AND tipo = 'polo' AND status = 'ativo' LIMIT 1";
        $categoria = $db->fetchOne($sql, []);
    }
    
    if (!$categoria) {
        throw new Exception('Categoria de chamado para solicitação de documentos não encontrada.');
    }
    
    // Gera um código único para o chamado
    $codigo = 'DOC-' . date('Ymd') . '-' . mt_rand(1000, 9999);
    
    // Cria o título e a descrição do chamado
    $titulo = "Solicitação de {$tipo_documento['nome']} - {$aluno['nome']}";
    $descricao = "Solicitação de {$quantidade} {$tipo_documento['nome']} para o aluno {$aluno['nome']}.\n\n";
    
    if (!empty($finalidade)) {
        $descricao .= "Finalidade: {$finalidade}\n\n";
    }
    
    $descricao .= "Valor unitário: R$ " . number_format($valor_unitario, 2, ',', '.') . "\n";
    $descricao .= "Valor total: R$ " . number_format($valor_total, 2, ',', '.') . "\n";
    
    // Insere o chamado no banco de dados
    $sql = "INSERT INTO chamados (
                codigo,
                titulo,
                descricao,
                categoria_id,
                tipo,
                prioridade,
                status,
                solicitante_id,
                departamento,
                polo_id,
                aluno_id,
                data_abertura,
                data_ultima_atualizacao,
                created_at,
                updated_at
            ) VALUES (?, ?, ?, ?, 'polo', 'media', 'aberto', ?, 'secretaria', ?, ?, NOW(), NOW(), NOW(), NOW())";
    
    $params = [
        $codigo,
        $titulo,
        $descricao,
        $categoria['id'],
        Auth::getUserId(),
        $polo_id,
        $aluno_id
    ];
    
    $db->execute($sql, $params);
    $chamado_id = $db->lastInsertId();
    
    // Insere o documento solicitado
    $sql = "INSERT INTO chamados_documentos (
                chamado_id,
                tipo_documento_id,
                aluno_id,
                polo_id,
                quantidade,
                finalidade,
                status,
                valor_unitario,
                valor_total,
                pago,
                created_at,
                updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, 'solicitado', ?, ?, 0, NOW(), NOW())";
    
    $params = [
        $chamado_id,
        $tipo_documento_id,
        $aluno_id,
        $polo_id,
        $quantidade,
        $finalidade,
        $valor_unitario,
        $valor_total
    ];
    
    $db->execute($sql, $params);
    
    // Registra a abertura do chamado no histórico
    $sql = "INSERT INTO chamados_respostas (
                chamado_id,
                usuario_id,
                mensagem,
                tipo,
                visivel_solicitante,
                data_resposta,
                created_at,
                updated_at
            ) VALUES (?, ?, ?, 'sistema', 1, NOW(), NOW(), NOW())";
    
    $mensagem = "Solicitação de documento registrada com sucesso.";
    $params_resposta = [
        $chamado_id,
        Auth::getUserId(),
        $mensagem
    ];
    
    $db->execute($sql, $params_resposta);
    
    // Confirma a transação
    $db->commit();
    
    $_SESSION['mensagem'] = 'Solicitação de documento registrada com sucesso. Código: ' . $codigo;
    $_SESSION['mensagem_tipo'] = 'sucesso';
    header('Location: chamados.php?action=visualizar&id=' . $chamado_id);
    exit;
} catch (Exception $e) {
    // Desfaz a transação em caso de erro
    $db->rollBack();
    
    $_SESSION['mensagem'] = 'Erro ao registrar a solicitação de documento: ' . $e->getMessage();
    $_SESSION['mensagem_tipo'] = 'erro';
    header('Location: chamados.php?action=solicitar_documento');
    exit;
}
?>
