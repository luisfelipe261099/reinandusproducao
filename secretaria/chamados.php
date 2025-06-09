<?php
/**
 * Redirecionamento para o novo módulo de chamados
 */

// Inicializa o sistema
require_once __DIR__ . '/includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica as permissões do usuário
if (!Auth::hasPermission('chamados', 'visualizar')) {
    $_SESSION['mensagem'] = 'Você não tem permissão para acessar esta página.';
    $_SESSION['mensagem_tipo'] = 'erro';
    header('Location: index.php');
    exit;
}

// Redireciona para o novo módulo de chamados
header('Location: chamados/index.php');
exit;
