<?php
/**
 * Página de logout
 */

// Inicializa o sistema
require_once __DIR__ . '/includes/init.php';

// Verifica se o usuário está autenticado
if (usuarioAutenticado()) {
    // Registra o log de logout
    registrarLog('usuarios', 'logout', 'Logout realizado com sucesso', getUsuarioId(), 'usuario');
    
    // Remove o cookie de "lembrar-me"
    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', time() - 3600, '/');
    }
    
    // Faz logout
    fazerLogout();
}

// Redireciona para a página de login
redirect('login.php?logout=success');
