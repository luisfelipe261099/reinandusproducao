<?php
// Configuração para exibir erros
ini_set('display_errors', 1);
error_reporting(E_ALL);

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

// Verifica se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    returnError('Método não permitido. Use POST.');
}

// Verifica se o arquivo foi enviado
if (!isset($_FILES['imagem'])) {
    returnError('Nenhum arquivo foi enviado.');
}

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
    
    returnError($error_message, [
        'error_code' => $_FILES['imagem']['error'],
        'file_info' => $_FILES['imagem']
    ]);
}

// Verifica o tipo de arquivo
$allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
$file_type = $_FILES['imagem']['type'];

// Verifica o tamanho do arquivo (máximo 5MB)
$max_size = 5 * 1024 * 1024; // 5MB
if ($_FILES['imagem']['size'] > $max_size) {
    returnError('O arquivo é muito grande. O tamanho máximo permitido é 5MB.');
}

// Determina o caminho para o upload
$base_path = '';
$upload_dir = '';

// Verifica se estamos em ambiente de produção
if (strpos(__FILE__, '/home/u682219090/domains/lfmtecnologia.com/public_html/reinandushomologacao/') !== false) {
    $base_path = '/home/u682219090/domains/lfmtecnologia.com/public_html/reinandushomologacao/';
    $upload_dir = $base_path . 'uploads/temp';
} else {
    $base_path = dirname(dirname(__FILE__)) . '/';
    $upload_dir = $base_path . 'uploads/temp';
}

// Cria o diretório se não existir
if (!file_exists($upload_dir)) {
    if (!mkdir($upload_dir, 0755, true)) {
        returnError('Falha ao criar diretório de upload.');
    }
}

// Gera um nome único para o arquivo
$file_extension = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
$file_name = uniqid('img_') . '_' . time() . '.' . $file_extension;
$file_path = $upload_dir . '/' . $file_name;

// Move o arquivo para o diretório de destino
if (!move_uploaded_file($_FILES['imagem']['tmp_name'], $file_path)) {
    returnError('Falha ao mover o arquivo para o diretório de destino.', [
        'from' => $_FILES['imagem']['tmp_name'],
        'to' => $file_path,
        'error' => error_get_last()
    ]);
}

// Caminho relativo para uso no HTML
if (strpos(__FILE__, '/home/u682219090/domains/lfmtecnologia.com/public_html/reinandushomologacao/') !== false) {
    $file_url = str_replace('/home/u682219090/domains/lfmtecnologia.com/public_html/reinandushomologacao/', '', $file_path);
} else {
    $file_url = str_replace($base_path, '', $file_path);
}

// Retorna sucesso
returnSuccess('Upload realizado com sucesso.', [
    'file_url' => $file_url,
    'file_name' => $file_name,
    'file_path' => $file_path,
    'base_path' => $base_path
]);
