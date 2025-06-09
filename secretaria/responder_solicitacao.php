<?php
// Inicializa o sistema
require_once __DIR__ . '/../includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica se o usuário tem permissão para acessar o módulo de chamados
exigirPermissao('chamados', 'editar');

// Verifica se o usuário é do tipo polo (polos não podem responder solicitações)
if (getUsuarioTipo() == 'polo') {
    $_SESSION['mensagem'] = 'Você não tem permissão para responder solicitações.';
    $_SESSION['mensagem_tipo'] = 'erro';
    header('Location: index.php');
    exit;
}

// Instancia o banco de dados
$db = Database::getInstance();

// Obtém o ID da solicitação
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    $_SESSION['mensagem'] = 'Solicitação inválida.';
    $_SESSION['mensagem_tipo'] = 'erro';
    header('Location: index.php?view=solicitacoes');
    exit;
}

// Obtém os dados da solicitação
$sql = "SELECT sd.*, 
               a.nome as aluno_nome, a.cpf as aluno_cpf, a.email as aluno_email,
               a.curso_id, c.nome as curso_nome, c.carga_horaria as curso_carga_horaria,
               p.nome as polo_nome, p.id as polo_id,
               td.nome as tipo_documento_nome, td.id as tipo_documento_id,
               u.nome as solicitante_nome
        FROM solicitacoes_documentos sd
        JOIN alunos a ON sd.aluno_id = a.id
        LEFT JOIN cursos c ON a.curso_id = c.id
        JOIN polos p ON sd.polo_id = p.id
        JOIN tipos_documentos td ON sd.tipo_documento_id = td.id
        LEFT JOIN usuarios u ON sd.solicitante_id = u.id
        WHERE sd.id = ?";
$solicitacao = $db->fetchOne($sql, [$id]);

// Verifica se a solicitação existe
if (!$solicitacao) {
    $_SESSION['mensagem'] = 'Solicitação não encontrada.';
    $_SESSION['mensagem_tipo'] = 'erro';
    header('Location: index.php?view=solicitacoes');
    exit;
}

// Verifica se a solicitação já foi respondida
if ($solicitacao['status'] != 'solicitado') {
    $_SESSION['mensagem'] = 'Esta solicitação já foi respondida.';
    $_SESSION['mensagem_tipo'] = 'erro';
    header('Location: ver_solicitacao.php?id=' . $id);
    exit;
}

// Processa o formulário de resposta
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = isset($_POST['status']) ? $_POST['status'] : '';
    $observacoes = isset($_POST['observacoes']) ? $_POST['observacoes'] : '';
    
    // Valida os dados
    $erros = [];
    
    if (empty($status)) {
        $erros[] = 'O status é obrigatório.';
    }
    
    if (empty($erros)) {
        // Atualiza a solicitação
        $dados = [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Adiciona observações se fornecidas
        if (!empty($observacoes)) {
            $novas_observacoes = date('d/m/Y H:i') . ' - ' . getUsuarioNome() . ": \n" . $observacoes . "\n\n";
            
            // Concatena com observações existentes se houver
            if (!empty($solicitacao['observacoes'])) {
                $novas_observacoes .= $solicitacao['observacoes'];
            }
            
            $dados['observacoes'] = $novas_observacoes;
        }
        
        // Atualiza a solicitação
        $atualizado = $db->update('solicitacoes_documentos', $dados, 'id = ?', [$id]);
        
        if ($atualizado) {
            // Se o status for 'pronto', gerar o documento
            if ($status === 'pronto') {
                // Redireciona para a página de geração de documento
                header('Location: ../documentos.php?action=gerar_documento_solicitacao&id=' . $id);
                exit;
            }
            
            $_SESSION['mensagem'] = 'Solicitação respondida com sucesso.';
            $_SESSION['mensagem_tipo'] = 'sucesso';
            header('Location: ver_solicitacao.php?id=' . $id);
            exit;
        } else {
            $erros[] = 'Erro ao atualizar a solicitação.';
        }
    }
}

// Define o título da página
$titulo_pagina = 'Responder Solicitação de Documento';

// Inicia o buffer de saída para as views
ob_start();

// Inclui a view
include __DIR__ . '/../views/chamados/responder_solicitacao.php';

// Obtém o conteúdo do buffer e limpa
$conteudo = ob_get_clean();

// Inclui o template
include __DIR__ . '/template.php';