<?php
// Inicializa o sistema
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/chamados_functions.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica se o usuário tem permissão para abrir chamados
exigirPermissao('chamados', 'criar');

// Instancia o banco de dados
$db = Database::getInstance();

// Define o título da página
$titulo_pagina = 'Novo Chamado';

// Obtém o tipo de usuário
$tipo_usuario = getUsuarioTipo();

// Obtém o ID do polo do usuário (se for do tipo polo)
$polo_id = null;
if ($tipo_usuario == 'polo') {
    $usuario = $db->fetchOne("SELECT polo_id FROM usuarios WHERE id = ?", [getUsuarioId()]);
    $polo_id = $usuario['polo_id'];
}

// Busca os polos (apenas para usuários que não são do tipo polo)
$polos = [];
if ($tipo_usuario != 'polo') {
    $sql = "SELECT id, nome FROM polos WHERE status = 'ativo' ORDER BY nome";
    $polos = $db->fetchAll($sql);
}

// Inicia o buffer de saída para as views
ob_start();

// Inclui a view
include __DIR__ . '/../views/chamados/novo_sistema.php';

// Obtém o conteúdo do buffer e limpa
$conteudo = ob_get_clean();

// Inclui o template
include __DIR__ . '/template.php';
