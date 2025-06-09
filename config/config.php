<?php
/**
 * Configurações gerais do sistema
 */

// Configurações de URL
define('BASE_URL', 'http://localhost');

// Configurações de diretórios
define('ROOT_DIR', dirname(__DIR__));
define('UPLOADS_DIR', ROOT_DIR . '/uploads');
define('DOCUMENTOS_DIR', UPLOADS_DIR . '/documentos');
define('TEMP_DIR', UPLOADS_DIR . '/temp');

// Configurações de upload
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10MB

// Configurações de paginação
define('ITEMS_PER_PAGE', 20);

// Configurações de segurança
define('HASH_COST', 10); // Custo do bcrypt

// Configurações de sessão
define('SESSION_NAME', 'faciencia_erp_session');
define('SESSION_LIFETIME', 360000); // 1 hora

// Configurações de email
define('MAIL_FROM', 'sistema@faciencia.edu.br');
define('MAIL_FROM_NAME', 'Sistema Faciência ERP');

// Configurações de log
define('LOG_ENABLED', true);
define('LOG_LEVEL', 'debug'); // debug, info, warning, error

// Configurações de data e hora
define('DATE_FORMAT', 'd/m/Y');
define('TIME_FORMAT', 'H:i:s');
define('DATETIME_FORMAT', 'd/m/Y H:i:s');

// Configurações de documentos
define('DOCUMENTO_PREFIX', 'FAC-DOC-');
define('DOCUMENTO_YEAR', date('Y'));

// Configurações de matrícula
define('MATRICULA_PREFIX', 'FAC-');
define('MATRICULA_YEAR', date('Y'));

// Carrega configurações do banco de dados
require_once __DIR__ . '/database.php';

// Inicializa diretórios necessários
if (!file_exists(UPLOADS_DIR)) {
    mkdir(UPLOADS_DIR, 0755, true);
}

if (!file_exists(DOCUMENTOS_DIR)) {
    mkdir(DOCUMENTOS_DIR, 0755, true);
}

if (!file_exists(TEMP_DIR)) {
    mkdir(TEMP_DIR, 0755, true);
}

// Configurações de sessão
session_name(SESSION_NAME);
session_set_cookie_params(SESSION_LIFETIME);

// Configurações de timezone
date_default_timezone_set('America/Sao_Paulo');

// Configurações de exibição de erros
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Função de autoload
spl_autoload_register(function ($class) {
    $paths = [
        ROOT_DIR . '/includes/',
        ROOT_DIR . '/models/',
        ROOT_DIR . '/controllers/'
    ];
    
    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});
