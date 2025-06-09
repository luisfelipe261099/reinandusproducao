<?php
/**
 * Script de upload ultra simplificado
 * Salva o arquivo em um diretório temporário e retorna a URL
 * Não depende de nenhum outro arquivo ou banco de dados
 */

// Desativa exibição de erros para o usuário
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Função para log
function log_message($message) {
    $log_file = __DIR__ . '/upload_temp_log.txt';
    $date = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$date] $message\n", FILE_APPEND);
}

// Inicia o log
log_message("Iniciando upload temporário");
log_message("Método: " . $_SERVER['REQUEST_METHOD']);

// Verifica se é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

// Verifica se o arquivo foi enviado
if (!isset($_FILES['imagem']) || $_FILES['imagem']['error'] !== UPLOAD_ERR_OK) {
    $error = isset($_FILES['imagem']) ? $_FILES['imagem']['error'] : 'Arquivo não enviado';
    log_message("Erro no upload: " . $error);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erro no upload do arquivo', 'error_code' => $error]);
    exit;
}

// Log do arquivo recebido
log_message("Arquivo recebido: " . $_FILES['imagem']['name'] . " (" . $_FILES['imagem']['size'] . " bytes)");

// Cria diretório temporário se não existir
$temp_dir = __DIR__ . '/temp_uploads';
if (!file_exists($temp_dir)) {
    log_message("Criando diretório temporário: " . $temp_dir);
    if (!mkdir($temp_dir, 0755)) {
        log_message("Falha ao criar diretório temporário");
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Falha ao criar diretório temporário']);
        exit;
    }
}

// Gera nome único para o arquivo
$file_extension = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
$file_name = 'img_' . uniqid() . '_' . time() . '.' . $file_extension;
$file_path = $temp_dir . '/' . $file_name;

log_message("Salvando arquivo em: " . $file_path);

// Move o arquivo para o diretório temporário
if (!move_uploaded_file($_FILES['imagem']['tmp_name'], $file_path)) {
    log_message("Falha ao mover arquivo: " . error_get_last()['message']);
    
    // Tenta copiar como alternativa
    if (!copy($_FILES['imagem']['tmp_name'], $file_path)) {
        log_message("Falha também ao copiar arquivo: " . error_get_last()['message']);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Falha ao salvar o arquivo']);
        exit;
    }
    
    log_message("Arquivo copiado com sucesso usando copy()");
} else {
    log_message("Arquivo movido com sucesso");
}

// Verifica se o arquivo foi realmente criado
if (!file_exists($file_path)) {
    log_message("Arquivo não existe após a operação");
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Arquivo não foi criado']);
    exit;
}

// URL relativa para o arquivo
$file_url = 'temp_uploads/' . $file_name;
log_message("URL do arquivo: " . $file_url);

// Retorna sucesso
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'Upload realizado com sucesso',
    'file_url' => $file_url,
    'file_name' => $file_name,
    'alt_text' => isset($_POST['alt_text']) ? $_POST['alt_text'] : ''
]);

log_message("Upload concluído com sucesso");
