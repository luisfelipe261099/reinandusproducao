<?php
// Ativa o modo de exibição de erros para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Função para registrar erros personalizados
function logError($message, $file = null, $line = null) {
    $error_message = date('[Y-m-d H:i:s]') . " - {$message}";
    
    if ($file) {
        $error_message .= " in {$file}";
        
        if ($line) {
            $error_message .= " on line {$line}";
        }
    }
    
    error_log($error_message);
}

// Manipulador de erros personalizado
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    $error_message = "Error [{$errno}]: {$errstr} in {$errfile} on line {$errline}";
    error_log($error_message);
    
    // Se for um erro fatal, exibe uma mensagem amigável
    if (in_array($errno, [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        echo "<h1>Ocorreu um erro no sistema</h1>";
        echo "<p>Por favor, entre em contato com o administrador.</p>";
        exit(1);
    }
    
    return true;
}

// Define o manipulador de erros personalizado
set_error_handler("customErrorHandler");

// Manipulador de exceções não capturadas
function uncaughtExceptionHandler($exception) {
    $error_message = "Uncaught Exception: " . $exception->getMessage() . 
                     " in " . $exception->getFile() . 
                     " on line " . $exception->getLine() . 
                     "\nStack trace: " . $exception->getTraceAsString();
    error_log($error_message);
    
    echo "<h1>Ocorreu um erro no sistema</h1>";
    echo "<p>Por favor, entre em contato com o administrador.</p>";
    exit(1);
}

// Define o manipulador de exceções não capturadas
set_exception_handler("uncaughtExceptionHandler");

// Manipulador de erros fatais
register_shutdown_function(function() {
    $error = error_get_last();
    
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $error_message = "Fatal Error: {$error['message']} in {$error['file']} on line {$error['line']}";
        error_log($error_message);
        
        echo "<h1>Ocorreu um erro fatal no sistema</h1>";
        echo "<p>Por favor, entre em contato com o administrador.</p>";
    }
});

// Inclui o arquivo de configuração
require_once 'config.php';
