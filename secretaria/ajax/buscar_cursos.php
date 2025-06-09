<?php
// Inclui o arquivo de configuração
require_once '../config.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Usuário não autenticado']);
    exit;
}

// Obtém o ID do polo
$polo_id = isset($_GET['polo_id']) ? (int)$_GET['polo_id'] : 0;

// Busca os cursos do polo
$sql = "SELECT id, nome, nivel, modalidade FROM cursos WHERE status = 'ativo'";
$params = [];

if ($polo_id > 0) {
    $sql .= " AND polo_id = ?";
    $params[] = $polo_id;
}

$sql .= " ORDER BY nome ASC";

try {
    $cursos = $db->fetchAll($sql, $params) ?: [];

    // Sanitiza os dados para evitar problemas de codificação
    // Registra no log para depuração
    error_log("SQL executado: " . $sql);
    error_log("Parâmetros: " . json_encode($params));
    error_log("Cursos encontrados (brutos): " . count($cursos));

    $sanitized_cursos = [];
    foreach ($cursos as $curso) {
        $sanitized_curso = [];
        foreach ($curso as $key => $value) {
            // Converte para UTF-8 e remove caracteres problemáticos
            if (is_string($value)) {
                $value = preg_replace('/[\x00-\x1F\x7F]/u', '', $value);
            }
            $sanitized_curso[$key] = $value;
        }
        $sanitized_cursos[] = $sanitized_curso;
    }

    // Registra no log para depuração
    error_log("Cursos sanitizados: " . count($sanitized_cursos));

    header('Content-Type: application/json');
    $response = ['cursos' => $sanitized_cursos];

    // Tenta codificar com tratamento de erro
    $json = json_encode($response, JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_UNESCAPED_UNICODE);

    // Se falhar, retorna um JSON mínimo válido
    if ($json === false) {
        error_log("Erro ao codificar JSON de cursos: " . json_last_error_msg());
        echo '{"cursos":[],"error":"Erro ao processar dados dos cursos"}';
    } else {
        echo $json;
    }
} catch (Exception $e) {
    error_log("Erro ao buscar cursos: " . $e->getMessage());

    header('Content-Type: application/json');
    $error_message = preg_replace('/[\x00-\x1F\x7F]/u', '', $e->getMessage());
    $error_response = ['error' => 'Erro ao buscar cursos: ' . $error_message, 'cursos' => []];

    // Tenta codificar com tratamento de erro
    $json = json_encode($error_response, JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_UNESCAPED_UNICODE);

    // Se falhar, retorna um JSON mínimo válido
    if ($json === false) {
        error_log("Erro ao codificar JSON de erro: " . json_last_error_msg());
        echo '{"cursos":[],"error":"Erro ao buscar cursos"}';
    } else {
        echo $json;
    }
}
