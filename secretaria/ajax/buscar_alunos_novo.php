<?php
/**
 * Script AJAX para buscar alunos com base em filtros
 * Versão completamente reescrita para garantir que a busca funcione corretamente
 */

// Inclui o arquivo de configuração
require_once '../config.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Usuário não autenticado']);
    exit;
}

// Captura os parâmetros da requisição
$termo = isset($_GET['termo']) ? trim($_GET['termo']) : '';
$polo_id = isset($_GET['polo_id']) ? (int)$_GET['polo_id'] : 0;
$curso_id = isset($_GET['curso_id']) ? (int)$_GET['curso_id'] : 0;
$turma_id = isset($_GET['turma_id']) ? (int)$_GET['turma_id'] : 0;
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$por_pagina = isset($_GET['por_pagina']) ? (int)$_GET['por_pagina'] : 20;

// Validação dos parâmetros
if ($pagina < 1) $pagina = 1;
if ($por_pagina < 1 || $por_pagina > 100) $por_pagina = 20;

// Calcula o offset para paginação
$offset = ($pagina - 1) * $por_pagina;

// Log dos parâmetros recebidos
error_log("Parâmetros recebidos: termo='$termo', polo_id=$polo_id, curso_id=$curso_id, turma_id=$turma_id, pagina=$pagina, por_pagina=$por_pagina");

// ABORDAGEM SIMPLIFICADA: Busca direta na tabela de alunos com JOINs opcionais
$sql_base = "FROM alunos a";
$joins = [];
$where = [];
$params = [];
$group_by = "GROUP BY a.id";

// Adiciona JOIN para matrículas (sempre necessário para exibir informações relacionadas)
$joins[] = "LEFT JOIN matriculas m ON a.id = m.aluno_id";
$joins[] = "LEFT JOIN cursos c ON m.curso_id = c.id";
$joins[] = "LEFT JOIN polos p ON m.polo_id = p.id"; // Corrigido: polo_id está na matrícula, não no curso

// Adiciona condição de busca por termo (busca em vários campos)
if (!empty($termo) && strlen($termo) >= 3) {
    // Log para depuração
    error_log("Buscando por termo: '$termo'");

    // Cria uma condição OR para buscar em vários campos
    $termo_conditions = [];

    // Busca por nome (case insensitive)
    $termo_conditions[] = "LOWER(a.nome) LIKE LOWER(?)";
    $params[] = "%{$termo}%";

    // Busca por CPF (remove pontuação para comparar)
    $termo_conditions[] = "REPLACE(REPLACE(REPLACE(a.cpf, '.', ''), '-', ''), ' ', '') LIKE ?";
    $params[] = "%" . preg_replace('/[^0-9]/', '', $termo) . "%";

    // Busca por email
    $termo_conditions[] = "LOWER(a.email) LIKE LOWER(?)";
    $params[] = "%{$termo}%";

    // Busca por ID legado
    $termo_conditions[] = "a.id_legado LIKE ?";
    $params[] = "%{$termo}%";

    // Busca por matrícula
    $termo_conditions[] = "m.numero LIKE ?";
    $params[] = "%{$termo}%";

    // Combina todas as condições de busca com OR
    $where[] = "(" . implode(" OR ", $termo_conditions) . ")";

    // Log das condições de busca
    error_log("Condições de busca por termo: " . implode(" OR ", $termo_conditions));
}

// Adiciona filtros específicos
// Adiciona filtro por polo
if ($polo_id > 0) {
    $where[] = "p.id = ?";
    $params[] = $polo_id;
    error_log("Filtro por polo: $polo_id");
}

// Adiciona filtro por curso
if ($curso_id > 0) {
    $where[] = "c.id = ?";
    $params[] = $curso_id;
    error_log("Filtro por curso: $curso_id");
}

// Adiciona filtro por turma
if ($turma_id > 0) {
    $where[] = "m.turma_id = ?";
    $params[] = $turma_id;
    error_log("Filtro por turma: $turma_id");
}

// Constrói a cláusula JOIN
$join_clause = implode(" ", $joins);

// Constrói a cláusula WHERE
$where_clause = "";
if (!empty($where)) {
    $where_clause = "WHERE " . implode(" AND ", $where);
}

// Log da cláusula WHERE
error_log("Cláusula WHERE: $where_clause");

// Constrói a consulta SQL para contar o total de registros
$sql_count = "SELECT COUNT(DISTINCT a.id) as total $sql_base $join_clause $where_clause";

// Constrói a consulta SQL para buscar os alunos
$sql = "SELECT DISTINCT
        a.id,
        a.nome,
        a.cpf,
        a.email,
        a.id_legado,
        m.numero as matricula,
        c.nome as curso_nome,
        p.nome as polo_nome
        $sql_base
        $join_clause
        $where_clause
        $group_by
        ORDER BY a.nome ASC
        LIMIT $por_pagina OFFSET $offset";

// Log das consultas SQL completas com parâmetros
error_log("SQL Count: $sql_count");
error_log("SQL Busca: $sql");
error_log("Parâmetros: " . json_encode($params));

try {
    // Executa a consulta de contagem
    $result_count = $db->fetchOne($sql_count, $params);
    $total = $result_count ? (int)$result_count['total'] : 0;

    // Calcula informações de paginação
    $total_paginas = ceil($total / $por_pagina);

    // Executa a consulta principal
    $alunos = $db->fetchAll($sql, $params) ?: [];

    // Log dos resultados
    error_log("Total de alunos encontrados: $total");
    error_log("Alunos na página atual: " . count($alunos));

    // Prepara a resposta
    $resposta = [
        'alunos' => $alunos,
        'paginacao' => [
            'pagina_atual' => $pagina,
            'total_paginas' => $total_paginas,
            'por_pagina' => $por_pagina,
            'total_registros' => $total,
            'offset' => $offset
        ],
        'debug' => [
            'sql' => $sql,
            'sql_count' => $sql_count,
            'params' => $params,
            'filtros' => [
                'termo' => $termo,
                'polo_id' => $polo_id,
                'curso_id' => $curso_id,
                'turma_id' => $turma_id
            ],
            'joins' => $joins,
            'where_clause' => $where_clause,
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ];

    // Se não encontrou nenhum aluno, adiciona uma mensagem mais detalhada
    if ($total === 0) {
        $resposta['mensagem'] = 'Nenhum aluno encontrado com os critérios de busca informados.';
        error_log("Busca não retornou resultados para termo='$termo', polo_id=$polo_id, curso_id=$curso_id, turma_id=$turma_id");
    }

    // Retorna a resposta em formato JSON
    header('Content-Type: application/json');

    // Sanitiza os dados para evitar problemas de codificação
    $sanitized_alunos = [];
    if (isset($resposta['alunos']) && is_array($resposta['alunos'])) {
        foreach ($resposta['alunos'] as $aluno) {
            $sanitized_aluno = [];
            foreach ($aluno as $key => $value) {
                // Converte para UTF-8 e remove caracteres problemáticos
                if (is_string($value)) {
                    $value = preg_replace('/[\x00-\x1F\x7F]/u', '', $value);
                }
                $sanitized_aluno[$key] = $value;
            }
            $sanitized_alunos[] = $sanitized_aluno;
        }
    }
    $resposta['alunos'] = $sanitized_alunos;

    // Tenta codificar com tratamento de erro
    $json = json_encode($resposta, JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_UNESCAPED_UNICODE);

    // Se falhar, retorna um JSON mínimo válido
    if ($json === false) {
        error_log("Erro ao codificar JSON: " . json_last_error_msg());
        echo '{"alunos":[],"paginacao":{"pagina_atual":1,"total_paginas":0,"por_pagina":20,"total_registros":0},"error":"Erro ao processar dados"}';
    } else {
        echo $json;
    }
    exit;
} catch (Exception $e) {
    // Log do erro
    error_log("ERRO AO BUSCAR ALUNOS: " . $e->getMessage());
    error_log("SQL que causou o erro: $sql");
    error_log("Parâmetros: " . json_encode($params));
    error_log("Stack trace: " . $e->getTraceAsString());

    // Retorna mensagem de erro
    header('Content-Type: application/json');
    $error_response = [
        'error' => 'Erro ao buscar alunos: ' . preg_replace('/[\x00-\x1F\x7F]/u', '', $e->getMessage()),
        'alunos' => [],
        'paginacao' => [
            'pagina_atual' => $pagina,
            'total_paginas' => 0,
            'por_pagina' => $por_pagina,
            'total_registros' => 0,
            'offset' => $offset
        ]
    ];

    // Tenta codificar com tratamento de erro
    $json = json_encode($error_response, JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_UNESCAPED_UNICODE);

    // Se falhar, retorna um JSON mínimo válido
    if ($json === false) {
        error_log("Erro ao codificar JSON de erro: " . json_last_error_msg());
        echo '{"alunos":[],"paginacao":{"pagina_atual":1,"total_paginas":0,"por_pagina":20,"total_registros":0},"error":"Erro ao buscar alunos"}';
    } else {
        echo $json;
    }
    exit;
}
