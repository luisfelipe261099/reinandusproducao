<?php
// Inclui o arquivo de configuração
require_once '../config.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Usuário não autenticado']);
    exit;
}

// Obtém o ID do curso
$curso_id = isset($_GET['curso_id']) ? (int)$_GET['curso_id'] : 0;

if ($curso_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'ID do curso não informado']);
    exit;
}

// Busca as turmas do curso
$sql = "SELECT id, nome, turno, status FROM turmas WHERE curso_id = ? AND status IN ('planejada', 'em_andamento') ORDER BY nome ASC";

try {
    // Registra no log para depuração
    error_log("SQL executado: " . $sql);
    error_log("Curso ID: " . $curso_id);

    $turmas = $db->fetchAll($sql, [$curso_id]) ?: [];
    error_log("Turmas encontradas (brutas): " . count($turmas));

    // Sanitiza os dados para evitar problemas de codificação
    $sanitized_turmas = [];
    foreach ($turmas as $turma) {
        $sanitized_turma = [];
        foreach ($turma as $key => $value) {
            // Converte para UTF-8 e remove caracteres problemáticos
            if (is_string($value)) {
                $value = preg_replace('/[\x00-\x1F\x7F]/u', '', $value);
            }
            $sanitized_turma[$key] = $value;
        }
        $sanitized_turmas[] = $sanitized_turma;
    }

    // Registra no log para depuração
    error_log("Turmas sanitizadas: " . count($sanitized_turmas));

    header('Content-Type: application/json');
    $response = ['turmas' => $sanitized_turmas];

    // Tenta codificar com tratamento de erro
    $json = json_encode($response, JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_UNESCAPED_UNICODE);

    // Se falhar, retorna um JSON mínimo válido
    if ($json === false) {
        error_log("Erro ao codificar JSON de turmas: " . json_last_error_msg());
        echo '{"turmas":[],"error":"Erro ao processar dados das turmas"}';
    } else {
        echo $json;
    }
} catch (Exception $e) {
    error_log("Erro ao buscar turmas: " . $e->getMessage());

    header('Content-Type: application/json');
    $error_message = preg_replace('/[\x00-\x1F\x7F]/u', '', $e->getMessage());
    $error_response = ['error' => 'Erro ao buscar turmas: ' . $error_message, 'turmas' => []];

    // Tenta codificar com tratamento de erro
    $json = json_encode($error_response, JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_UNESCAPED_UNICODE);

    // Se falhar, retorna um JSON mínimo válido
    if ($json === false) {
        error_log("Erro ao codificar JSON de erro: " . json_last_error_msg());
        echo '{"turmas":[],"error":"Erro ao buscar turmas"}';
    } else {
        echo $json;
    }
}
