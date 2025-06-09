<?php
/**
 * API para buscar polos
 */

// Inicializa o sistema
require_once __DIR__ . '/../includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Instancia o banco de dados
$db = Database::getInstance();

// Define o tipo de resposta como JSON
header('Content-Type: application/json');

// Verifica o método da requisição
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode([
        'success' => false,
        'message' => 'Método não permitido'
    ]);
    exit;
}

// Obtém os parâmetros da requisição
$termo = $_GET['termo'] ?? '';
$pagina = (int)($_GET['pagina'] ?? 1);
$por_pagina = (int)($_GET['por_pagina'] ?? 5); // Alterado para 5 por padrão

// Valida os parâmetros
if ($pagina < 1) {
    $pagina = 1;
}

if ($por_pagina < 1 || $por_pagina > 100) {
    $por_pagina = 5; // Alterado para 5 por padrão
}

// Calcula o offset
$offset = ($pagina - 1) * $por_pagina;

try {
    // Monta a consulta SQL
    $where = [];
    $params = [];

    if (!empty($termo)) {
        $where[] = "nome LIKE ?";
        $params[] = "%{$termo}%";
    }

    // Monta a cláusula WHERE
    $whereClause = '';
    if (!empty($where)) {
        $whereClause = "WHERE " . implode(" AND ", $where);
    }

    try {
        // Consulta direta para obter os polos
        $sql = "SELECT id, nome, cidade, '' as estado
                FROM polos
                {$whereClause}
                ORDER BY nome ASC
                LIMIT ? OFFSET ?";

        // Adiciona os parâmetros de paginação
        $params[] = $por_pagina;
        $params[] = $offset;

        // Executa a consulta
        $polos = $db->fetchAll($sql, $params);

        // Log da consulta
        error_log("Consulta principal executada com sucesso. Encontrados: " . count($polos));
    } catch (Exception $e) {
        // Log do erro
        error_log("Erro na consulta principal: " . $e->getMessage());
        $polos = [];
    }

    // Se não encontrou nenhum polo, tenta uma consulta mais simples
    if (empty($polos)) {
        try {
            $sql = "SELECT id, nome, '' as cidade, '' as estado FROM polos";
            if (!empty($termo)) {
                $sql .= " WHERE nome LIKE ?";
                $polos = $db->fetchAll($sql, ["%{$termo}%"]);
            } else {
                $polos = $db->fetchAll($sql);
            }

            // Log da consulta alternativa
            error_log("Consulta alternativa executada com sucesso. Encontrados: " . count($polos));
        } catch (Exception $e) {
            // Log do erro
            error_log("Erro na consulta alternativa: " . $e->getMessage());

            // Retorna um array vazio em caso de erro
            $polos = [];
        }
    }

    // Garante que todos os polos tenham os campos necessários
    foreach ($polos as &$polo) {
        if (!isset($polo['id'])) $polo['id'] = '';
        if (!isset($polo['nome'])) $polo['nome'] = '';
        if (!isset($polo['cidade'])) $polo['cidade'] = '';
        if (!isset($polo['estado'])) $polo['estado'] = '';
    }

    // Conta o total de polos
    $sql = "SELECT COUNT(*) as total FROM polos";
    if (!empty($termo)) {
        $sql .= " WHERE nome LIKE ?";
        $resultado = $db->fetchOne($sql, ["%{$termo}%"]);
    } else {
        $resultado = $db->fetchOne($sql);
    }
    $total = $resultado['total'] ?? 0;

    // Calcula o total de páginas
    $total_paginas = ceil($total / $por_pagina);

    // Log para depuração
    error_log('Busca de polos - Termo: ' . $termo . ', Página: ' . $pagina . ', Total encontrado: ' . count($polos));
    error_log('SQL executado: ' . $sql);
    error_log('Parâmetros: ' . json_encode($params));
    error_log('Resultados: ' . json_encode($polos));

    // Retorna os resultados
    $response = [
        'success' => true,
        'data' => [
            'polos' => $polos,
            'paginacao' => [
                'pagina' => $pagina,
                'por_pagina' => $por_pagina,
                'total' => $total,
                'total_paginas' => $total_paginas
            ]
        ]
    ];

    // Log da resposta
    error_log('Resposta: ' . json_encode($response));

    // Retorna os resultados
    echo json_encode($response);
} catch (Exception $e) {
    // Erro ao buscar polos
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar polos: ' . $e->getMessage()
    ]);
}
