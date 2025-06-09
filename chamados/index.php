<?php
// Inicializa o sistema
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/chamados_functions.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica se o usuário tem permissão para acessar o módulo de chamados
exigirPermissao('chamados', 'visualizar');

// Instancia o banco de dados
$db = Database::getInstance();

// Define o título da página
$titulo_pagina = 'Chamados';

// Determina se estamos visualizando chamados, solicitações de documentos ou chamados do site
$view_type = isset($_GET['view']) ? $_GET['view'] : 'chamados';

// Obtém os parâmetros de filtro
$filtros = [];
$filtros['tipo'] = isset($_GET['tipo']) ? $_GET['tipo'] : '';
$filtros['subtipo'] = isset($_GET['subtipo']) ? $_GET['subtipo'] : '';
$filtros['status'] = isset($_GET['status']) ? $_GET['status'] : '';
$filtros['polo_id'] = isset($_GET['polo_id']) ? (int)$_GET['polo_id'] : '';
$filtros['data_inicio'] = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : '';
$filtros['data_fim'] = isset($_GET['data_fim']) ? $_GET['data_fim'] : '';
$filtros['busca'] = isset($_GET['busca']) ? $_GET['busca'] : '';
$filtros['tipo_solicitacao'] = isset($_GET['tipo_solicitacao']) ? $_GET['tipo_solicitacao'] : '';

// Obtém a página atual
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$pagina = max(1, $pagina); // Garante que a página seja pelo menos 1

// Define a quantidade de itens por página
$itens_por_pagina = 20;

// Se o usuário for do tipo polo, filtra apenas os chamados do seu polo
$polo_id = null;
if (getUsuarioTipo() == 'polo') {
    $usuario = $db->fetchOne("SELECT polo_id FROM usuarios WHERE id = ?", [getUsuarioId()]);
    $polo_id = $usuario['polo_id'];
    $filtros['polo_id'] = $polo_id;
}

if ($view_type == 'chamados') {
    // Busca os chamados com os filtros aplicados
    $resultado = buscarChamados($db, $filtros, $pagina, $itens_por_pagina);
    $chamados = $resultado['chamados'];
    $total_registros = $resultado['total_registros'];
    $total_paginas = $resultado['total_paginas'];
} else if ($view_type == 'solicitacoes') {
    // Busca solicitações de documentos
    $where = [];
    $params = [];

    // Adiciona filtro de polo se necessário
    if ($polo_id) {
        $where[] = "sd.polo_id = ?";
        $params[] = $polo_id;
    } else if (!empty($filtros['polo_id'])) {
        $where[] = "sd.polo_id = ?";
        $params[] = $filtros['polo_id'];
    }

    // Adiciona filtro de status se fornecido
    if (!empty($filtros['status'])) {
        $where[] = "sd.status = ?";
        $params[] = $filtros['status'];
    }

    // Adiciona filtro de data se fornecido
    if (!empty($filtros['data_inicio'])) {
        $where[] = "DATE(sd.created_at) >= ?";
        $params[] = $filtros['data_inicio'];
    }

    if (!empty($filtros['data_fim'])) {
        $where[] = "DATE(sd.created_at) <= ?";
        $params[] = $filtros['data_fim'];
    }

    // Adiciona filtro de busca se fornecido
    if (!empty($filtros['busca'])) {
        $where[] = "(a.nome LIKE ? OR a.cpf LIKE ? OR a.email LIKE ?)";
        $params[] = "%" . $filtros['busca'] . "%";
        $params[] = "%" . $filtros['busca'] . "%";
        $params[] = "%" . $filtros['busca'] . "%";
    }

    // Constrói a cláusula WHERE
    $where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

    // Conta o total de solicitações
    $sql_count = "SELECT COUNT(*) as total FROM solicitacoes_documentos sd
                 LEFT JOIN alunos a ON sd.aluno_id = a.id
                 $where_clause";
    $count_result = $db->fetchOne($sql_count, $params);
    $total_registros = $count_result['total'];

    // Calcula o total de páginas
    $total_paginas = ceil($total_registros / $itens_por_pagina);

    // Calcula o offset
    $offset = ($pagina - 1) * $itens_por_pagina;

    // Busca as solicitações de documentos
    $sql = "SELECT sd.*,
            a.nome as aluno_nome, a.cpf as aluno_cpf, a.email as aluno_email,
            p.nome as polo_nome,
            td.nome as tipo_documento_nome,
            u.nome as solicitante_nome
            FROM solicitacoes_documentos sd
            LEFT JOIN alunos a ON sd.aluno_id = a.id
            LEFT JOIN polos p ON sd.polo_id = p.id
            LEFT JOIN tipos_documentos td ON sd.tipo_documento_id = td.id
            LEFT JOIN usuarios u ON sd.solicitante_id = u.id
            $where_clause
            ORDER BY sd.created_at DESC
            LIMIT $offset, $itens_por_pagina";

    $solicitacoes = $db->fetchAll($sql, $params);
} else if ($view_type == 'chamados_site') {
    // Busca solicitações do site
    $where = [];
    $params = [];

    // Adiciona filtro de tipo de solicitação se fornecido
    if (!empty($filtros['tipo_solicitacao'])) {
        $where[] = "tipo_solicitacao = ?";
        $params[] = $filtros['tipo_solicitacao'];
    }

    // Adiciona filtro de status se fornecido
    if (!empty($filtros['status'])) {
        $where[] = "status = ?";
        $params[] = $filtros['status'];
    }

    // Adiciona filtro de data se fornecido
    if (!empty($filtros['data_inicio'])) {
        $where[] = "DATE(data_solicitacao) >= ?";
        $params[] = $filtros['data_inicio'];
    }

    if (!empty($filtros['data_fim'])) {
        $where[] = "DATE(data_solicitacao) <= ?";
        $params[] = $filtros['data_fim'];
    }

    // Adiciona filtro de busca se fornecido
    if (!empty($filtros['busca'])) {
        $where[] = "(protocolo LIKE ? OR nome_empresa LIKE ? OR cnpj LIKE ? OR nome_solicitante LIKE ? OR email LIKE ?)";
        $busca = "%" . $filtros['busca'] . "%";
        $params[] = $busca;
        $params[] = $busca;
        $params[] = $busca;
        $params[] = $busca;
        $params[] = $busca;
    }

    // Constrói a cláusula WHERE
    $where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

    // Conta o total de solicitações do site
    $sql_count = "SELECT COUNT(*) as total FROM solicitacoes_site $where_clause";
    $count_result = $db->fetchOne($sql_count, $params);
    $total_registros = $count_result['total'];

    // Calcula o total de páginas
    $total_paginas = ceil($total_registros / $itens_por_pagina);

    // Calcula o offset
    $offset = ($pagina - 1) * $itens_por_pagina;

    // Busca as solicitações do site
    $sql = "SELECT * FROM solicitacoes_site
            $where_clause
            ORDER BY data_solicitacao DESC
            LIMIT $offset, $itens_por_pagina";

    $solicitacoes_site = $db->fetchAll($sql, $params);
}

// Busca os polos para o filtro (apenas para usuários que não são do tipo polo)
$polos = [];
if (getUsuarioTipo() != 'polo') {
    $sql = "SELECT id, nome FROM polos WHERE status = 'ativo' ORDER BY nome";
    $polos = $db->fetchAll($sql);
}

// Inicia o buffer de saída para as views
ob_start();

// Inclui a view apropriada
if ($view_type == 'chamados') {
    include __DIR__ . '/../views/chamados/listar.php';
} else if ($view_type == 'solicitacoes') {
    include __DIR__ . '/../views/chamados/listar_solicitacoes.php';
} else if ($view_type == 'chamados_site') {
    include __DIR__ . '/../views/chamados/listar_site.php';
}

// Obtém o conteúdo do buffer e limpa
$conteudo = ob_get_clean();

// Inclui o template
include __DIR__ . '/template.php';