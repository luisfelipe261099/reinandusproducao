<?php
// Inicializa o sistema
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/chamados_functions.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica se o usuário tem permissão para visualizar chamados
exigirPermissao('chamados', 'visualizar');

// Instancia o banco de dados
$db = Database::getInstance();

// Verifica se o ID do chamado foi informado
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['mensagem'] = 'ID do chamado não informado.';
    $_SESSION['mensagem_tipo'] = 'erro';
    header('Location: index.php');
    exit;
}

$chamado_id = (int)$_GET['id'];

// Busca o chamado
$chamado = buscarChamadoPorId($db, $chamado_id);

if (!$chamado) {
    $_SESSION['mensagem'] = 'Chamado não encontrado.';
    $_SESSION['mensagem_tipo'] = 'erro';
    header('Location: index.php');
    exit;
}

// Verifica se o usuário tem permissão para acessar este chamado
if (!usuarioTemPermissaoChamado($db, $chamado_id, getUsuarioId(), getUsuarioTipo())) {
    $_SESSION['mensagem'] = 'Você não tem permissão para acessar este chamado.';
    $_SESSION['mensagem_tipo'] = 'erro';
    header('Location: index.php');
    exit;
}

// Busca os alunos relacionados ao chamado
$alunos = buscarAlunosDoChamado($db, $chamado_id);

// Busca o histórico do chamado
$historico = buscarHistoricoChamado($db, $chamado_id);

// Define o título da página
$titulo_pagina = 'Chamado #' . $chamado_id;

// Inicia o buffer de saída para as views
ob_start();

// Inclui a view
include __DIR__ . '/../views/chamados/visualizar_sistema.php';

// Obtém o conteúdo do buffer e limpa
$conteudo = ob_get_clean();

// Inclui o template
include __DIR__ . '/template.php';
