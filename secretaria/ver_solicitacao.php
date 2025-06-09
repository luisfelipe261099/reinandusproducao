<?php
// Inicializa o sistema
require_once __DIR__ . '/../includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica se o usuário tem permissão para acessar o módulo de chamados
exigirPermissao('chamados', 'visualizar');

// Instancia o banco de dados
$db = Database::getInstance();

// Obtém o ID da solicitação
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    $_SESSION['mensagem'] = 'Solicitação inválida.';
    $_SESSION['mensagem_tipo'] = 'erro';
    header('Location: index.php');
    exit;
}

// Verifica se o usuário é do tipo polo
$is_polo = getUsuarioTipo() == 'polo';
$polo_id = null;

if ($is_polo) {
    $usuario = $db->fetchOne("SELECT polo_id FROM usuarios WHERE id = ?", [getUsuarioId()]);
    $polo_id = $usuario['polo_id'];
}

// Obtém os dados da solicitação
$sql = "SELECT sd.*, 
               a.nome as aluno_nome, a.cpf as aluno_cpf, a.email as aluno_email,
               a.curso_id, c.nome as curso_nome, c.carga_horaria as curso_carga_horaria,
               p.nome as polo_nome, p.id as polo_id,
               td.nome as tipo_documento_nome, td.id as tipo_documento_id,
               u.nome as solicitante_nome, u.tipo as solicitante_tipo
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
    header('Location: index.php');
    exit;
}

// Verifica se o usuário tem permissão para visualizar a solicitação
if ($is_polo && $solicitacao['polo_id'] != $polo_id) {
    $_SESSION['mensagem'] = 'Você não tem permissão para visualizar esta solicitação.';
    $_SESSION['mensagem_tipo'] = 'erro';
    header('Location: index.php');
    exit;
}

// Obtém o documento gerado (se existir)
$documento = null;
if (!empty($solicitacao['documento_id'])) {
    $sql = "SELECT * FROM documentos_emitidos WHERE id = ?";
    $documento = $db->fetchOne($sql, [$solicitacao['documento_id']]);
}

// Define o título da página
$titulo_pagina = 'Visualizar Solicitação de Documento';

// Inicia o buffer de saída para as views
ob_start();

// Inclui a view
include __DIR__ . '/../views/chamados/ver_solicitacao.php';

// Obtém o conteúdo do buffer e limpa
$conteudo = ob_get_clean();

// Inclui o template
include __DIR__ . '/template.php';