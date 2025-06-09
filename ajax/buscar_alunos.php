<?php
// Inclui o arquivo de configuração
require_once '../config.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Usuário não autenticado']);
    exit;
}

// Inicializa variáveis
$termo = isset($_GET['termo']) ? trim($_GET['termo']) : '';
$polo_id = isset($_GET['polo_id']) ? (int)$_GET['polo_id'] : 0;
$curso_id = isset($_GET['curso_id']) ? (int)$_GET['curso_id'] : 0;
$turma_id = isset($_GET['turma_id']) ? (int)$_GET['turma_id'] : 0;

// Parâmetros de paginação
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$por_pagina = isset($_GET['por_pagina']) ? (int)$_GET['por_pagina'] : 20;

// Garante que os valores sejam válidos
if ($pagina < 1) $pagina = 1;
if ($por_pagina < 1 || $por_pagina > 100) $por_pagina = 20;

// Calcula o offset para a consulta SQL
$offset = ($pagina - 1) * $por_pagina;

// Registra os parâmetros recebidos para debug
error_log("Busca de alunos - Parâmetros: termo='$termo', polo_id=$polo_id, curso_id=$curso_id, turma_id=$turma_id");

// Verifica se há pelo menos um parâmetro de busca
if (empty($termo) && $polo_id == 0 && $curso_id == 0 && $turma_id == 0) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Informe pelo menos um parâmetro de busca (termo, polo, curso ou turma)']);
    exit;
}

// Constrói a consulta com base nos parâmetros
$where = [];
$params = [];

// Adiciona condições de busca por termo - busca direta na tabela de alunos
if (!empty($termo) && strlen($termo) >= 3) {
    // Busca por nome, CPF ou ID legado diretamente na tabela de alunos
    $where[] = "(a.nome LIKE ? OR a.cpf LIKE ? OR a.id_legado LIKE ? OR a.email LIKE ?)";
    $params[] = "%{$termo}%";
    $params[] = "%{$termo}%";
    $params[] = "%{$termo}%";
    $params[] = "%{$termo}%";

    // Também busca por número de matrícula, mas como condição opcional (OR)
    $matricula_where = "OR EXISTS (SELECT 1 FROM matriculas m2 WHERE m2.aluno_id = a.id AND m2.numero LIKE ?)";
    $where[0] .= " $matricula_where";
    $params[] = "%{$termo}%";
}

// Armazena os filtros de polo, curso e turma para uso posterior
$filtros_adicionais = [];
$params_filtros = [];

// Prepara condições de filtro como opções adicionais
if ($polo_id > 0) {
    $filtros_adicionais[] = "EXISTS (SELECT 1 FROM matriculas m3 JOIN cursos c3 ON m3.curso_id = c3.id WHERE m3.aluno_id = a.id AND c3.polo_id = ?)";
    $params_filtros[] = $polo_id;
}

if ($curso_id > 0) {
    $filtros_adicionais[] = "EXISTS (SELECT 1 FROM matriculas m4 WHERE m4.aluno_id = a.id AND m4.curso_id = ?)";
    $params_filtros[] = $curso_id;
}

if ($turma_id > 0) {
    $filtros_adicionais[] = "EXISTS (SELECT 1 FROM matriculas m5 WHERE m5.aluno_id = a.id AND m5.turma_id = ?)";
    $params_filtros[] = $turma_id;
}

// Adiciona os filtros apenas se houver um termo de busca ou se forem os únicos filtros
if (!empty($filtros_adicionais)) {
    if (!empty($where)) {
        // Se já existe um termo de busca, adiciona os filtros como condições AND
        $filtro_combinado = implode(" AND ", $filtros_adicionais);
        $where[] = "($filtro_combinado)";
        $params = array_merge($params, $params_filtros);
    } else {
        // Se não há termo de busca, os filtros são as únicas condições
        $where = $filtros_adicionais;
        $params = $params_filtros;
    }
}

// Constrói a cláusula WHERE
$whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Constrói a consulta SQL para contar o total de registros - busca direta na tabela de alunos
$sqlCount = "SELECT COUNT(*) as total FROM alunos a $whereClause";

// Constrói a consulta SQL para buscar os alunos com paginação - busca direta na tabela de alunos
$sql = "SELECT a.id, a.nome, a.cpf, a.email, a.id_legado,
        (SELECT m.numero FROM matriculas m WHERE m.aluno_id = a.id AND m.status = 'ativo' ORDER BY m.id DESC LIMIT 1) as matricula,
        (SELECT c.nome FROM matriculas m JOIN cursos c ON m.curso_id = c.id WHERE m.aluno_id = a.id AND m.status = 'ativo' ORDER BY m.id DESC LIMIT 1) as curso_nome,
        (SELECT p.nome FROM matriculas m JOIN cursos c ON m.curso_id = c.id JOIN polos p ON c.polo_id = p.id WHERE m.aluno_id = a.id AND m.status = 'ativo' ORDER BY m.id DESC LIMIT 1) as polo_nome
        FROM alunos a
        $whereClause
        ORDER BY a.nome ASC
        LIMIT $por_pagina OFFSET $offset";

// Adiciona log para debug
error_log("Consulta SQL simplificada para buscar diretamente na tabela de alunos");

// Registra a consulta SQL para debug com mais detalhes
error_log("=== BUSCA DIRETA NA TABELA DE ALUNOS ===");
error_log("SQL Count: $sqlCount");
error_log("SQL Busca: $sql");
error_log("Parâmetros: " . json_encode($params));
error_log("Termo de busca: '$termo'");
error_log("Filtros: polo_id=$polo_id, curso_id=$curso_id, turma_id=$turma_id");
error_log("Paginação: pagina=$pagina, por_pagina=$por_pagina, offset=$offset");

try {
    // Executa a consulta de contagem
    $resultCount = $db->fetchOne($sqlCount, $params);
    $total = $resultCount ? (int)$resultCount['total'] : 0;

    // Calcula informações de paginação
    $total_paginas = ceil($total / $por_pagina);

    // Executa a consulta principal
    $alunos = $db->fetchAll($sql, $params);

    // Prepara a resposta
    $resposta = [
        'alunos' => $alunos ?: [],
        'paginacao' => [
            'pagina_atual' => $pagina,
            'total_paginas' => $total_paginas,
            'por_pagina' => $por_pagina,
            'total_registros' => $total,
            'offset' => $offset
        ]
    ];

    header('Content-Type: application/json');
    echo json_encode($resposta);
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Erro ao buscar alunos: ' . $e->getMessage()]);
}
