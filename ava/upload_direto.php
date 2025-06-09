<?php
/**
 * Script de upload direto - não depende de nenhum outro arquivo
 * Criado especificamente para resolver problemas de upload em produção
 */

// Configuração para exibir erros
ini_set('display_errors', 0); // Desativado em produção
error_reporting(E_ALL);

// Função para registrar mensagens no log
function logMessage($message) {
    $date = date('Y-m-d H:i:s');
    $logFile = __DIR__ . '/upload_log.txt';
    file_put_contents($logFile, "[$date] $message\n", FILE_APPEND);
}

// Função para retornar erros em formato JSON
function returnError($message, $details = null) {
    header('Content-Type: application/json');
    $response = [
        'success' => false,
        'message' => $message
    ];
    
    if ($details !== null) {
        $response['debug'] = $details;
    }
    
    echo json_encode($response);
    exit;
}

// Função para retornar sucesso em formato JSON
function returnSuccess($message, $data = []) {
    header('Content-Type: application/json');
    $response = [
        'success' => true,
        'message' => $message
    ];
    
    foreach ($data as $key => $value) {
        $response[$key] = $value;
    }
    
    echo json_encode($response);
    exit;
}

// Registra início do processamento
logMessage("Iniciando processamento de upload direto");

// Verifica se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    returnError('Método não permitido. Use POST.');
}

// Verifica se o arquivo foi enviado
if (!isset($_FILES['imagem'])) {
    returnError('Nenhum arquivo foi enviado.');
}

logMessage("Arquivo recebido: " . json_encode($_FILES['imagem']));

if ($_FILES['imagem']['error'] !== UPLOAD_ERR_OK) {
    $error_message = 'Erro no upload do arquivo.';
    
    // Mensagens de erro específicas
    switch ($_FILES['imagem']['error']) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            $error_message = 'O arquivo é muito grande. Limite do PHP: ' . ini_get('upload_max_filesize');
            break;
        case UPLOAD_ERR_PARTIAL:
            $error_message = 'O upload do arquivo foi interrompido.';
            break;
        case UPLOAD_ERR_NO_FILE:
            $error_message = 'Nenhum arquivo foi enviado.';
            break;
        case UPLOAD_ERR_NO_TMP_DIR:
            $error_message = 'Pasta temporária não encontrada.';
            break;
        case UPLOAD_ERR_CANT_WRITE:
            $error_message = 'Falha ao gravar o arquivo.';
            break;
        case UPLOAD_ERR_EXTENSION:
            $error_message = 'Upload interrompido por extensão.';
            break;
    }
    
    logMessage("Erro no upload: " . $error_message);
    returnError($error_message, [
        'error_code' => $_FILES['imagem']['error']
    ]);
}

// Verifica o tipo de arquivo
$allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
$file_type = $_FILES['imagem']['type'];

logMessage("Tipo do arquivo: " . $file_type);

if (!in_array($file_type, $allowed_types)) {
    logMessage("Tipo de arquivo não permitido: " . $file_type);
    returnError('Tipo de arquivo não permitido. Apenas JPG, JPEG, PNG e GIF são aceitos.');
}

// Verifica o tamanho do arquivo (máximo 5MB)
$max_size = 5 * 1024 * 1024; // 5MB
if ($_FILES['imagem']['size'] > $max_size) {
    logMessage("Arquivo muito grande: " . $_FILES['imagem']['size'] . " bytes");
    returnError('O arquivo é muito grande. O tamanho máximo permitido é 5MB.');
}

// Determina o caminho para o upload
// Caminho absoluto para o diretório de uploads em produção
$upload_dir = '/home/u682219090/domains/lfmtecnologia.com/public_html/reinandushomologacao/uploads/ava/imagens';

// Obtém o ID do polo, se fornecido
$polo_id = isset($_POST['polo_id']) ? (int)$_POST['polo_id'] : 1;
logMessage("ID do polo: " . $polo_id);

// Diretório específico do polo
$polo_dir = $upload_dir . '/' . $polo_id;

// Cria o diretório se não existir
if (!file_exists($upload_dir)) {
    logMessage("Criando diretório principal: " . $upload_dir);
    if (!mkdir($upload_dir, 0755, true)) {
        logMessage("Falha ao criar diretório principal: " . error_get_last()['message']);
        returnError('Falha ao criar diretório de upload.');
    }
}

if (!file_exists($polo_dir)) {
    logMessage("Criando diretório do polo: " . $polo_dir);
    if (!mkdir($polo_dir, 0755, true)) {
        logMessage("Falha ao criar diretório do polo: " . error_get_last()['message']);
        returnError('Falha ao criar diretório do polo.');
    }
}

// Gera um nome único para o arquivo
$file_extension = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
$file_name = uniqid('img_') . '_' . time() . '.' . $file_extension;
$file_path = $polo_dir . '/' . $file_name;

logMessage("Caminho do arquivo: " . $file_path);

// Move o arquivo para o diretório de destino
if (!move_uploaded_file($_FILES['imagem']['tmp_name'], $file_path)) {
    $error = error_get_last();
    logMessage("Falha ao mover o arquivo: " . ($error ? $error['message'] : 'Erro desconhecido'));
    
    // Tenta uma abordagem alternativa
    if (!copy($_FILES['imagem']['tmp_name'], $file_path)) {
        $error = error_get_last();
        logMessage("Falha também ao copiar o arquivo: " . ($error ? $error['message'] : 'Erro desconhecido'));
        returnError('Falha ao mover o arquivo para o diretório de destino.');
    } else {
        logMessage("Arquivo copiado com sucesso usando copy()");
    }
} else {
    logMessage("Arquivo movido com sucesso");
}

// Verifica se o arquivo foi realmente criado
if (!file_exists($file_path)) {
    logMessage("Arquivo não existe após a operação de cópia");
    returnError('O arquivo não foi criado no destino.');
}

// Caminho relativo para uso no HTML
$file_url = '/uploads/ava/imagens/' . $polo_id . '/' . $file_name;
logMessage("URL do arquivo: " . $file_url);

// Texto alternativo
$alt_text = isset($_POST['alt_text']) ? $_POST['alt_text'] : '';

// Retorna sucesso
logMessage("Upload concluído com sucesso");
returnSuccess('Upload realizado com sucesso.', [
    'file_url' => $file_url,
    'file_name' => $file_name,
    'alt_text' => $alt_text
]);
