<?php
// Inicializa o sistema
require_once __DIR__ . '/../includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica se o usuário tem permissão para acessar o módulo de chamados
exigirPermissao('chamados', 'visualizar');

// Define o título da página
$titulo_pagina = 'Ajuda - Solicitações de Documentos';

// Inicia o buffer de saída para as views
ob_start();

// Inclui a view
include __DIR__ . '/../views/chamados/help_solicitacoes.php';

// Obtém o conteúdo do buffer e limpa
$conteudo = ob_get_clean();

// Inclui o template
include __DIR__ . '/template.php';