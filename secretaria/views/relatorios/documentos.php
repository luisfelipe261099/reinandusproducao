<?php
/**
 * ================================================================
 *                    RELATÓRIO DE DOCUMENTOS
 * ================================================================
 * 
 * Utiliza as tabelas:
 * - documentos_emitidos: Documentos já emitidos/gerados
 * - solicitacoes_documentos: Solicitações de documentos
 * - tipos_documentos: Tipos de documentos disponíveis
 * - alunos: Dados dos alunos
 * - polos: Dados dos polos
 * 
 * ================================================================
 */

// === FUNÇÕES AUXILIARES ===

/**
 * Gera URL com parâmetros preservados para paginação
 * @param int $pagina Número da página
 * @return string URL completa
 */
function gerarUrlPaginacao($pagina, $filtro_tab, $registros_por_pagina) {
    $params = [
        'tipo' => 'documentos',
        'tab' => $filtro_tab,
        'pagina' => $pagina,
        'per_page' => $registros_por_pagina
    ];
    
    // Preserva filtros existentes
    if (isset($_GET['tipo_documento_id']) && $_GET['tipo_documento_id'] != '0') {
        $params['tipo_documento_id'] = $_GET['tipo_documento_id'];
    }
    if (isset($_GET['polo_id']) && $_GET['polo_id'] != '0') {
        $params['polo_id'] = $_GET['polo_id'];
    }
    if (isset($_GET['status']) && $_GET['status'] != '') {
        $params['status'] = $_GET['status'];
    }
    if (isset($_GET['data_inicio'])) {
        $params['data_inicio'] = $_GET['data_inicio'];
    }
    if (isset($_GET['data_fim'])) {
        $params['data_fim'] = $_GET['data_fim'];
    }
    
    return 'relatorios.php?' . http_build_query($params);
}

// === CONFIGURAÇÃO DE FILTROS E PAGINAÇÃO ===
$filtro_tipo_documento = isset($_GET['tipo_documento_id']) ? (int)$_GET['tipo_documento_id'] : 0;
$filtro_polo = isset($_GET['polo_id']) ? (int)$_GET['polo_id'] : 0;
$filtro_status = isset($_GET['status']) ? $_GET['status'] : '';
$filtro_data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : date('Y-m-d', strtotime('-6 months'));
$filtro_data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : date('Y-m-d');
$filtro_tab = isset($_GET['tab']) ? $_GET['tab'] : 'emitidos'; // emitidos ou solicitacoes

// Parâmetros de paginação
$pagina_atual = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$registros_por_pagina = isset($_GET['per_page']) ? max(10, min(100, (int)$_GET['per_page'])) : 25;
$offset = ($pagina_atual - 1) * $registros_por_pagina;

// === CARREGAMENTO DE DADOS AUXILIARES ===

// Obtém a lista de tipos de documentos para o filtro
$sql_tipos_documentos = "SELECT id, nome FROM tipos_documentos WHERE status = 'ativo' ORDER BY nome ASC";
$tipos_documentos = $db->fetchAll($sql_tipos_documentos) ?: [];

// Obtém a lista de polos para o filtro
$sql_polos = "SELECT id, nome FROM polos WHERE status = 'ativo' ORDER BY nome ASC";
$polos = $db->fetchAll($sql_polos) ?: [];

// === CONSULTAS PARA DOCUMENTOS EMITIDOS ===

if ($filtro_tab === 'emitidos') {
    // === CONTAGEM TOTAL PARA PAGINAÇÃO ===
    $sql_count = "SELECT COUNT(*) as total 
                  FROM documentos_emitidos de
                  WHERE de.data_emissao BETWEEN ? AND ?";
    
    $params_count = [$filtro_data_inicio, $filtro_data_fim];
    
    if ($filtro_tipo_documento > 0) {
        $sql_count .= " AND de.tipo_documento_id = ?";
        $params_count[] = $filtro_tipo_documento;
    }
    
    if ($filtro_polo > 0) {
        $sql_count .= " AND de.polo_id = ?";
        $params_count[] = $filtro_polo;
    }
    
    if (!empty($filtro_status)) {
        $sql_count .= " AND de.status = ?";
        $params_count[] = $filtro_status;
    }
    
    $total_registros = $db->fetchOne($sql_count, $params_count)['total'] ?? 0;
    $total_paginas = ceil($total_registros / $registros_por_pagina);

    // Constrói a consulta SQL para documentos emitidos
    $sql_documentos = "SELECT 
                       de.id,
                       de.numero_documento,
                       de.arquivo,
                       de.data_emissao,
                       de.data_validade,
                       de.codigo_verificacao,
                       de.status,
                       td.nome as tipo_documento,
                       a.nome as aluno_nome,
                       a.cpf as aluno_cpf,
                       p.nome as polo_nome,
                       c.nome as curso_nome,
                       sd.finalidade,
                       sd.quantidade,
                       sd.valor_total,
                       sd.pago
                       FROM documentos_emitidos de
                       LEFT JOIN tipos_documentos td ON de.tipo_documento_id = td.id
                       LEFT JOIN alunos a ON de.aluno_id = a.id
                       LEFT JOIN polos p ON de.polo_id = p.id
                       LEFT JOIN cursos c ON de.curso_id = c.id
                       LEFT JOIN solicitacoes_documentos sd ON de.solicitacao_id = sd.id
                       WHERE de.data_emissao BETWEEN ? AND ?";

    $params_documentos = [$filtro_data_inicio, $filtro_data_fim];

    // Aplica os filtros para documentos emitidos
    if ($filtro_tipo_documento > 0) {
        $sql_documentos .= " AND de.tipo_documento_id = ?";
        $params_documentos[] = $filtro_tipo_documento;
    }

    if ($filtro_polo > 0) {
        $sql_documentos .= " AND de.polo_id = ?";
        $params_documentos[] = $filtro_polo;
    }

    if (!empty($filtro_status)) {
        $sql_documentos .= " AND de.status = ?";
        $params_documentos[] = $filtro_status;
    }

    $sql_documentos .= " ORDER BY de.data_emissao DESC LIMIT ? OFFSET ?";
    $params_documentos[] = $registros_por_pagina;
    $params_documentos[] = $offset;

    // Executa a consulta
    $documentos = $db->fetchAll($sql_documentos, $params_documentos) ?: [];

    // === ESTATÍSTICAS PARA DOCUMENTOS EMITIDOS ===
    $sql_estatisticas = "SELECT 
                         COUNT(*) as total_documentos,
                         COUNT(CASE WHEN de.status = 'ativo' THEN 1 END) as documentos_ativos,
                         COUNT(CASE WHEN de.status = 'cancelado' THEN 1 END) as documentos_cancelados,
                         COUNT(CASE WHEN de.data_validade >= CURDATE() THEN 1 END) as documentos_validos,
                         COUNT(CASE WHEN de.data_validade < CURDATE() THEN 1 END) as documentos_vencidos
                         FROM documentos_emitidos de
                         WHERE de.data_emissao BETWEEN ? AND ?";

    $params_estatisticas = [$filtro_data_inicio, $filtro_data_fim];

    // Aplica os filtros nas estatísticas
    if ($filtro_tipo_documento > 0) {
        $sql_estatisticas .= " AND de.tipo_documento_id = ?";
        $params_estatisticas[] = $filtro_tipo_documento;
    }

    if ($filtro_polo > 0) {
        $sql_estatisticas .= " AND de.polo_id = ?";
        $params_estatisticas[] = $filtro_polo;
    }

    // Executa a consulta de estatísticas
    $estatisticas = $db->fetchOne($sql_estatisticas, $params_estatisticas) ?: [
        'total_documentos' => 0,
        'documentos_ativos' => 0,
        'documentos_cancelados' => 0,
        'documentos_validos' => 0,
        'documentos_vencidos' => 0
    ];

    // === ESTATÍSTICAS POR TIPO DE DOCUMENTO EMITIDO ===
    $sql_por_tipo = "SELECT 
                    td.nome as tipo_documento,
                    COUNT(*) as total_documentos
                    FROM documentos_emitidos de
                    LEFT JOIN tipos_documentos td ON de.tipo_documento_id = td.id
                    WHERE de.data_emissao BETWEEN ? AND ?";

    $params_por_tipo = [$filtro_data_inicio, $filtro_data_fim];

    // Aplica os filtros nas estatísticas por tipo
    if ($filtro_polo > 0) {
        $sql_por_tipo .= " AND de.polo_id = ?";
        $params_por_tipo[] = $filtro_polo;
    }

    if (!empty($filtro_status)) {
        $sql_por_tipo .= " AND de.status = ?";
        $params_por_tipo[] = $filtro_status;
    }

    $sql_por_tipo .= " GROUP BY td.id, td.nome ORDER BY total_documentos DESC";
    $estatisticas_por_tipo = $db->fetchAll($sql_por_tipo, $params_por_tipo) ?: [];

    // === ESTATÍSTICAS POR POLO PARA DOCUMENTOS EMITIDOS ===
    $sql_por_polo = "SELECT 
                    p.nome as polo_nome,
                    COUNT(*) as total_documentos
                    FROM documentos_emitidos de
                    LEFT JOIN polos p ON de.polo_id = p.id
                    WHERE de.data_emissao BETWEEN ? AND ?";

    $params_por_polo = [$filtro_data_inicio, $filtro_data_fim];

    // Aplica os filtros nas estatísticas por polo
    if ($filtro_tipo_documento > 0) {
        $sql_por_polo .= " AND de.tipo_documento_id = ?";
        $params_por_polo[] = $filtro_tipo_documento;
    }

    if (!empty($filtro_status)) {
        $sql_por_polo .= " AND de.status = ?";
        $params_por_polo[] = $filtro_status;
    }

    $sql_por_polo .= " GROUP BY p.id, p.nome ORDER BY total_documentos DESC";
    $estatisticas_por_polo = $db->fetchAll($sql_por_polo, $params_por_polo) ?: [];

} else {
    // === CONSULTAS PARA SOLICITAÇÕES DE DOCUMENTOS ===
    
    // === CONTAGEM TOTAL PARA PAGINAÇÃO ===
    $sql_count = "SELECT COUNT(*) as total 
                  FROM solicitacoes_documentos sd
                  WHERE sd.data_solicitacao BETWEEN ? AND ?";
    
    $params_count = [$filtro_data_inicio, $filtro_data_fim];
    
    if ($filtro_tipo_documento > 0) {
        $sql_count .= " AND sd.tipo_documento_id = ?";
        $params_count[] = $filtro_tipo_documento;
    }
    
    if ($filtro_polo > 0) {
        $sql_count .= " AND sd.polo_id = ?";
        $params_count[] = $filtro_polo;
    }
    
    if (!empty($filtro_status)) {
        $sql_count .= " AND sd.status = ?";
        $params_count[] = $filtro_status;
    }
    
    $total_registros = $db->fetchOne($sql_count, $params_count)['total'] ?? 0;
    $total_paginas = ceil($total_registros / $registros_por_pagina);
    
    // Constrói a consulta SQL para solicitações de documentos
    $sql_documentos = "SELECT 
                       sd.id,
                       sd.quantidade,
                       sd.finalidade,
                       sd.status,
                       sd.valor_total,
                       sd.pago,
                       sd.created_at as data_solicitacao,
                       sd.updated_at,
                       sd.observacoes,
                       td.nome as tipo_documento,
                       a.nome as aluno_nome,
                       a.cpf as aluno_cpf,
                       p.nome as polo_nome,
                       u.nome as solicitante_nome
                       FROM solicitacoes_documentos sd
                       LEFT JOIN tipos_documentos td ON sd.tipo_documento_id = td.id
                       LEFT JOIN alunos a ON sd.aluno_id = a.id
                       LEFT JOIN polos p ON sd.polo_id = p.id
                       LEFT JOIN usuarios u ON sd.solicitante_id = u.id
                       WHERE sd.data_solicitacao BETWEEN ? AND ?";

    $params_documentos = [$filtro_data_inicio, $filtro_data_fim];

    // Aplica os filtros para solicitações
    if ($filtro_tipo_documento > 0) {
        $sql_documentos .= " AND sd.tipo_documento_id = ?";
        $params_documentos[] = $filtro_tipo_documento;
    }

    if ($filtro_polo > 0) {
        $sql_documentos .= " AND sd.polo_id = ?";
        $params_documentos[] = $filtro_polo;
    }

    if (!empty($filtro_status)) {
        $sql_documentos .= " AND sd.status = ?";
        $params_documentos[] = $filtro_status;
    }

    $sql_documentos .= " ORDER BY sd.created_at DESC LIMIT ? OFFSET ?";
    $params_documentos[] = $registros_por_pagina;
    $params_documentos[] = $offset;

    // Executa a consulta
    $documentos = $db->fetchAll($sql_documentos, $params_documentos) ?: [];    // === ESTATÍSTICAS PARA SOLICITAÇÕES ===
    $sql_estatisticas = "SELECT 
                         COUNT(*) as total_documentos,
                         COUNT(CASE WHEN sd.status = 'solicitado' THEN 1 END) as solicitacoes_pendentes,
                         COUNT(CASE WHEN sd.status = 'processando' THEN 1 END) as solicitacoes_processando,
                         COUNT(CASE WHEN sd.status = 'pronto' THEN 1 END) as solicitacoes_prontas,
                         COUNT(CASE WHEN sd.status = 'entregue' THEN 1 END) as solicitacoes_entregues,
                         COUNT(CASE WHEN sd.pago = 1 THEN 1 END) as solicitacoes_pagas,
                         COALESCE(SUM(sd.valor_total), 0) as valor_total_solicitacoes
                         FROM solicitacoes_documentos sd
                         WHERE sd.data_solicitacao BETWEEN ? AND ?";

    $params_estatisticas = [$filtro_data_inicio, $filtro_data_fim];

    // Aplica os filtros nas estatísticas
    if ($filtro_tipo_documento > 0) {
        $sql_estatisticas .= " AND sd.tipo_documento_id = ?";
        $params_estatisticas[] = $filtro_tipo_documento;
    }

    if ($filtro_polo > 0) {
        $sql_estatisticas .= " AND sd.polo_id = ?";
        $params_estatisticas[] = $filtro_polo;
    }

    // Executa a consulta de estatísticas
    $estatisticas = $db->fetchOne($sql_estatisticas, $params_estatisticas) ?: [
        'total_documentos' => 0,
        'solicitacoes_pendentes' => 0,
        'solicitacoes_processando' => 0,
        'solicitacoes_prontas' => 0,
        'solicitacoes_entregues' => 0,
        'solicitacoes_pagas' => 0,
        'valor_total_solicitacoes' => 0
    ];

    // === ESTATÍSTICAS POR TIPO DE DOCUMENTO SOLICITADO ===
    $sql_por_tipo = "SELECT 
                    td.nome as tipo_documento,
                    COUNT(*) as total_documentos,
                    SUM(sd.quantidade) as total_quantidade
                    FROM solicitacoes_documentos sd
                    LEFT JOIN tipos_documentos td ON sd.tipo_documento_id = td.id
                    WHERE sd.data_solicitacao BETWEEN ? AND ?";

    $params_por_tipo = [$filtro_data_inicio, $filtro_data_fim];

    // Aplica os filtros nas estatísticas por tipo
    if ($filtro_polo > 0) {
        $sql_por_tipo .= " AND sd.polo_id = ?";
        $params_por_tipo[] = $filtro_polo;
    }

    if (!empty($filtro_status)) {
        $sql_por_tipo .= " AND sd.status = ?";
        $params_por_tipo[] = $filtro_status;
    }

    $sql_por_tipo .= " GROUP BY td.id, td.nome ORDER BY total_documentos DESC";
    $estatisticas_por_tipo = $db->fetchAll($sql_por_tipo, $params_por_tipo) ?: [];    // === ESTATÍSTICAS POR POLO PARA SOLICITAÇÕES ===
    $sql_por_polo = "SELECT 
                    p.nome as polo_nome,
                    COUNT(*) as total_documentos,
                    COALESCE(SUM(sd.valor_total), 0) as valor_total
                    FROM solicitacoes_documentos sd
                    LEFT JOIN polos p ON sd.polo_id = p.id
                    WHERE sd.data_solicitacao BETWEEN ? AND ?";

    $params_por_polo = [$filtro_data_inicio, $filtro_data_fim];

    // Aplica os filtros nas estatísticas por polo
    if ($filtro_tipo_documento > 0) {
        $sql_por_polo .= " AND sd.tipo_documento_id = ?";
        $params_por_polo[] = $filtro_tipo_documento;
    }

    if (!empty($filtro_status)) {
        $sql_por_polo .= " AND sd.status = ?";
        $params_por_polo[] = $filtro_status;
    }

    $sql_por_polo .= " GROUP BY p.id, p.nome ORDER BY total_documentos DESC";
    $estatisticas_por_polo = $db->fetchAll($sql_por_polo, $params_por_polo) ?: [];
}
?>
?>

<div class="container mx-auto px-4 py-8">
    <!-- Cabeçalho -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Relatório de Documentos</h1>
        
        <div class="flex space-x-2">
            <button onclick="window.print()" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                <i class="fas fa-print mr-2"></i> Imprimir
            </button>
            <a href="documentos.php" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                <i class="fas fa-file-alt mr-2"></i> Gerenciar Documentos
            </a>
        </div>
    </div>    <!-- Abas para alternar entre Documentos Emitidos e Solicitações -->
    <div class="mb-6 print:hidden">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8">
                <?php
                // Preserva filtros para as abas, mas remove paginação
                $params_aba = [];
                if (isset($_GET['tipo_documento_id']) && $_GET['tipo_documento_id'] != '0') {
                    $params_aba['tipo_documento_id'] = $_GET['tipo_documento_id'];
                }
                if (isset($_GET['polo_id']) && $_GET['polo_id'] != '0') {
                    $params_aba['polo_id'] = $_GET['polo_id'];
                }
                if (isset($_GET['status']) && $_GET['status'] != '') {
                    $params_aba['status'] = $_GET['status'];
                }
                if (isset($_GET['data_inicio'])) {
                    $params_aba['data_inicio'] = $_GET['data_inicio'];
                }
                if (isset($_GET['data_fim'])) {
                    $params_aba['data_fim'] = $_GET['data_fim'];
                }
                if (isset($_GET['per_page'])) {
                    $params_aba['per_page'] = $_GET['per_page'];
                }
                
                $url_emitidos = 'relatorios.php?tipo=documentos&tab=emitidos' . (empty($params_aba) ? '' : '&' . http_build_query($params_aba));
                $url_solicitacoes = 'relatorios.php?tipo=documentos&tab=solicitacoes' . (empty($params_aba) ? '' : '&' . http_build_query($params_aba));
                ?>
                <a href="<?php echo $url_emitidos; ?>" 
                   class="<?php echo $filtro_tab === 'emitidos' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                    <i class="fas fa-file-check mr-2"></i>Documentos Emitidos
                </a>
                <a href="<?php echo $url_solicitacoes; ?>" 
                   class="<?php echo $filtro_tab === 'solicitacoes' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                    <i class="fas fa-file-signature mr-2"></i>Solicitações de Documentos
                </a>
            </nav>
        </div>
    </div>
    
    <!-- Filtros -->
    <div class="bg-white shadow-md rounded-lg p-4 mb-6 print:hidden">
        <h2 class="text-lg font-semibold mb-4">Filtros</h2>
          <form action="relatorios.php" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="hidden" name="tipo" value="documentos">
            <input type="hidden" name="tab" value="<?php echo $filtro_tab; ?>">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Documento</label>
                <select name="tipo_documento_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    <option value="0">Todos os Tipos</option>
                    <?php foreach ($tipos_documentos as $tipo): ?>
                    <option value="<?php echo $tipo['id']; ?>" <?php echo $filtro_tipo_documento == $tipo['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($tipo['nome']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Polo</label>
                <select name="polo_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    <option value="0">Todos os Polos</option>
                    <?php foreach ($polos as $polo): ?>
                    <option value="<?php echo $polo['id']; ?>" <?php echo $filtro_polo == $polo['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($polo['nome']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    <option value="">Todos</option>
                    <?php if ($filtro_tab === 'emitidos'): ?>
                    <option value="ativo" <?php echo $filtro_status === 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                    <option value="cancelado" <?php echo $filtro_status === 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                    <?php else: ?>
                    <option value="solicitado" <?php echo $filtro_status === 'solicitado' ? 'selected' : ''; ?>>Solicitado</option>
                    <option value="processando" <?php echo $filtro_status === 'processando' ? 'selected' : ''; ?>>Processando</option>
                    <option value="pronto" <?php echo $filtro_status === 'pronto' ? 'selected' : ''; ?>>Pronto</option>
                    <option value="entregue" <?php echo $filtro_status === 'entregue' ? 'selected' : ''; ?>>Entregue</option>
                    <?php endif; ?>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Registros por Página</label>
                <select name="per_page" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    <option value="10" <?php echo $registros_por_pagina == 10 ? 'selected' : ''; ?>>10</option>
                    <option value="25" <?php echo $registros_por_pagina == 25 ? 'selected' : ''; ?>>25</option>
                    <option value="50" <?php echo $registros_por_pagina == 50 ? 'selected' : ''; ?>>50</option>
                    <option value="100" <?php echo $registros_por_pagina == 100 ? 'selected' : ''; ?>>100</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Data Início</label>
                <input type="date" name="data_inicio" value="<?php echo $filtro_data_inicio; ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Data Fim</label>
                <input type="date" name="data_fim" value="<?php echo $filtro_data_fim; ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
            </div>
            
            <div class="flex items-end col-span-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    <i class="fas fa-filter mr-2"></i> Filtrar
                </button>
                
                <a href="relatorios.php?tipo=documentos&tab=<?php echo $filtro_tab; ?>" class="ml-2 bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
                    <i class="fas fa-times mr-2"></i> Limpar
                </a>
            </div>
        </form>
    </div>    
    <!-- Resumo -->
    <div class="bg-white shadow-md rounded-lg p-6 mb-6">
        <?php if ($filtro_tab === 'emitidos'): ?>
        <h2 class="text-lg font-semibold mb-4">Resumo de Documentos Emitidos</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                <h3 class="text-sm font-medium text-blue-800 mb-2">Total Emitidos</h3>
                <p class="text-2xl font-bold text-blue-600"><?php echo $estatisticas['total_documentos']; ?></p>
            </div>
            
            <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                <h3 class="text-sm font-medium text-green-800 mb-2">Ativos</h3>
                <p class="text-2xl font-bold text-green-600"><?php echo $estatisticas['documentos_ativos']; ?> <span class="text-sm font-normal">(<?php echo $estatisticas['total_documentos'] > 0 ? round(($estatisticas['documentos_ativos'] / $estatisticas['total_documentos']) * 100, 1) : 0; ?>%)</span></p>
            </div>
            
            <div class="bg-red-50 p-4 rounded-lg border border-red-200">
                <h3 class="text-sm font-medium text-red-800 mb-2">Cancelados</h3>
                <p class="text-2xl font-bold text-red-600"><?php echo $estatisticas['documentos_cancelados']; ?> <span class="text-sm font-normal">(<?php echo $estatisticas['total_documentos'] > 0 ? round(($estatisticas['documentos_cancelados'] / $estatisticas['total_documentos']) * 100, 1) : 0; ?>%)</span></p>
            </div>
            
            <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                <h3 class="text-sm font-medium text-yellow-800 mb-2">Válidos</h3>
                <p class="text-2xl font-bold text-yellow-600"><?php echo $estatisticas['documentos_validos']; ?> <span class="text-sm font-normal">(<?php echo $estatisticas['total_documentos'] > 0 ? round(($estatisticas['documentos_validos'] / $estatisticas['total_documentos']) * 100, 1) : 0; ?>%)</span></p>
            </div>
            
            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                <h3 class="text-sm font-medium text-gray-800 mb-2">Vencidos</h3>
                <p class="text-2xl font-bold text-gray-600"><?php echo $estatisticas['documentos_vencidos']; ?> <span class="text-sm font-normal">(<?php echo $estatisticas['total_documentos'] > 0 ? round(($estatisticas['documentos_vencidos'] / $estatisticas['total_documentos']) * 100, 1) : 0; ?>%)</span></p>
            </div>
        </div>
        
        <?php else: ?>
        <h2 class="text-lg font-semibold mb-4">Resumo de Solicitações de Documentos</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
            <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                <h3 class="text-sm font-medium text-blue-800 mb-2">Total Solicitações</h3>
                <p class="text-2xl font-bold text-blue-600"><?php echo $estatisticas['total_documentos']; ?></p>
            </div>
            
            <div class="bg-orange-50 p-4 rounded-lg border border-orange-200">
                <h3 class="text-sm font-medium text-orange-800 mb-2">Pendentes</h3>
                <p class="text-2xl font-bold text-orange-600"><?php echo $estatisticas['solicitacoes_pendentes']; ?></p>
            </div>
            
            <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                <h3 class="text-sm font-medium text-yellow-800 mb-2">Processando</h3>
                <p class="text-2xl font-bold text-yellow-600"><?php echo $estatisticas['solicitacoes_processando']; ?></p>
            </div>
            
            <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                <h3 class="text-sm font-medium text-blue-800 mb-2">Prontas</h3>
                <p class="text-2xl font-bold text-blue-600"><?php echo $estatisticas['solicitacoes_prontas']; ?></p>
            </div>
            
            <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                <h3 class="text-sm font-medium text-green-800 mb-2">Entregues</h3>
                <p class="text-2xl font-bold text-green-600"><?php echo $estatisticas['solicitacoes_entregues']; ?></p>
            </div>
              <div class="bg-purple-50 p-4 rounded-lg border border-purple-200">
                <h3 class="text-sm font-medium text-purple-800 mb-2">Valor Total</h3>
                <p class="text-2xl font-bold text-purple-600">R$ <?php echo number_format($estatisticas['valor_total_solicitacoes'] ?: 0, 2, ',', '.'); ?></p>
                <p class="text-xs text-purple-500"><?php echo $estatisticas['solicitacoes_pagas'] ?: 0; ?> pagas</p>
            </div>
        </div>
        <?php endif; ?>
    </div>
      <!-- Gráficos -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <!-- Documentos por Tipo -->
        <div class="bg-white shadow-md rounded-lg p-6">
            <h2 class="text-lg font-semibold mb-4">
                <?php echo $filtro_tab === 'emitidos' ? 'Documentos Emitidos por Tipo' : 'Solicitações por Tipo de Documento'; ?>
            </h2>
            <div class="h-64">
                <canvas id="graficoDocumentosPorTipo"></canvas>
            </div>
        </div>
        
        <!-- Documentos por Polo -->
        <div class="bg-white shadow-md rounded-lg p-6">
            <h2 class="text-lg font-semibold mb-4">
                <?php echo $filtro_tab === 'emitidos' ? 'Documentos Emitidos por Polo' : 'Solicitações por Polo'; ?>
            </h2>
            <div class="h-64">
                <canvas id="graficoDocumentosPorPolo"></canvas>
            </div>
        </div>
    </div>
      <!-- Tabela de Documentos -->
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <h2 class="text-lg font-semibold p-4 border-b">
            <?php echo $filtro_tab === 'emitidos' ? 'Lista de Documentos Emitidos' : 'Lista de Solicitações de Documentos'; ?>
        </h2>
        
        <?php if (empty($documentos)): ?>
        <div class="p-6 text-center">
            <p class="text-gray-500">Nenhum documento encontrado para os filtros selecionados.</p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <?php if ($filtro_tab === 'emitidos'): ?>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Número</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aluno</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Curso</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Polo</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data Emissão</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Validade</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <?php else: ?>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aluno</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Polo</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantidade</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valor</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data Solicitação</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pago</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($documentos as $documento): ?>
                    <tr>
                        <?php if ($filtro_tab === 'emitidos'): ?>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            <?php echo htmlspecialchars($documento['numero_documento'] ?: 'N/A'); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo htmlspecialchars($documento['tipo_documento'] ?: 'N/A'); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo htmlspecialchars($documento['aluno_nome'] ?: 'N/A'); ?>
                            <?php if (!empty($documento['aluno_cpf'])): ?>
                            <br><span class="text-xs text-gray-400">CPF: <?php echo htmlspecialchars($documento['aluno_cpf']); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo htmlspecialchars($documento['curso_nome'] ?: 'N/A'); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo htmlspecialchars($documento['polo_nome'] ?: 'N/A'); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo $documento['data_emissao'] ? date('d/m/Y', strtotime($documento['data_emissao'])) : 'N/A'; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php 
                            if ($documento['data_validade']) {
                                $data_validade = date('d/m/Y', strtotime($documento['data_validade']));
                                $vencido = strtotime($documento['data_validade']) < time();
                                echo '<span class="' . ($vencido ? 'text-red-600 font-medium' : '') . '">' . $data_validade . '</span>';
                            } else {
                                echo 'N/A';
                            }
                            ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php
                            $status_class = '';
                            $status_text = '';
                            
                            switch ($documento['status']) {
                                case 'ativo':
                                    $status_class = 'bg-green-100 text-green-800';
                                    $status_text = 'Ativo';
                                    break;
                                case 'cancelado':
                                    $status_class = 'bg-red-100 text-red-800';
                                    $status_text = 'Cancelado';
                                    break;
                                default:
                                    $status_class = 'bg-gray-100 text-gray-800';
                                    $status_text = ucfirst($documento['status']);
                            }
                            ?>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                                <?php echo $status_text; ?>
                            </span>
                        </td>
                        <?php else: ?>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo htmlspecialchars($documento['tipo_documento'] ?: 'N/A'); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo htmlspecialchars($documento['aluno_nome'] ?: 'N/A'); ?>
                            <?php if (!empty($documento['aluno_cpf'])): ?>
                            <br><span class="text-xs text-gray-400">CPF: <?php echo htmlspecialchars($documento['aluno_cpf']); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo htmlspecialchars($documento['polo_nome'] ?: 'N/A'); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo $documento['quantidade'] ?: 1; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            R$ <?php echo number_format($documento['valor_total'] ?: 0, 2, ',', '.'); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo $documento['data_solicitacao'] ? date('d/m/Y', strtotime($documento['data_solicitacao'])) : 'N/A'; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php
                            $status_class = '';
                            $status_text = '';
                            
                            switch ($documento['status']) {
                                case 'solicitado':
                                    $status_class = 'bg-orange-100 text-orange-800';
                                    $status_text = 'Solicitado';
                                    break;
                                case 'processando':
                                    $status_class = 'bg-yellow-100 text-yellow-800';
                                    $status_text = 'Processando';
                                    break;
                                case 'pronto':
                                    $status_class = 'bg-blue-100 text-blue-800';
                                    $status_text = 'Pronto';
                                    break;
                                case 'entregue':
                                    $status_class = 'bg-green-100 text-green-800';
                                    $status_text = 'Entregue';
                                    break;
                                default:
                                    $status_class = 'bg-gray-100 text-gray-800';
                                    $status_text = ucfirst($documento['status']);
                            }
                            ?>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                                <?php echo $status_text; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php if ($documento['pago']): ?>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                Pago
                            </span>
                            <?php else: ?>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                Pendente
                            </span>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>        </div>
        <?php endif; ?>
    </div>    <!-- Paginação -->
    <?php if ($total_registros > 0): ?>
    <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6 print:hidden">
        <div class="flex-1 flex justify-between sm:hidden">
            <?php if ($pagina_atual > 1): ?>
            <a href="<?php echo gerarUrlPaginacao($pagina_atual - 1, $filtro_tab, $registros_por_pagina); ?>" 
               class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                Anterior
            </a>
            <?php endif; ?>
            
            <?php if ($pagina_atual < $total_paginas): ?>
            <a href="<?php echo gerarUrlPaginacao($pagina_atual + 1, $filtro_tab, $registros_por_pagina); ?>" 
               class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                Próximo
            </a>
            <?php endif; ?>
        </div>
        
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-gray-700">
                    Mostrando 
                    <span class="font-medium"><?php echo ($offset + 1); ?></span>
                    até 
                    <span class="font-medium"><?php echo min($offset + $registros_por_pagina, $total_registros); ?></span>
                    de 
                    <span class="font-medium"><?php echo $total_registros; ?></span>
                    <?php echo $filtro_tab === 'emitidos' ? 'documentos emitidos' : 'solicitações'; ?>
                </p>
            </div>
            
            <div>
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                    <!-- Primeira página -->
                    <?php if ($pagina_atual > 1): ?>
                    <a href="<?php echo gerarUrlPaginacao(1, $filtro_tab, $registros_por_pagina); ?>" 
                       class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                        <span class="sr-only">Primeira</span>
                        <i class="fas fa-angle-double-left h-5 w-5"></i>
                    </a>
                    
                    <!-- Página anterior -->
                    <a href="<?php echo gerarUrlPaginacao($pagina_atual - 1, $filtro_tab, $registros_por_pagina); ?>" 
                       class="relative inline-flex items-center px-2 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                        <span class="sr-only">Anterior</span>
                        <i class="fas fa-angle-left h-5 w-5"></i>
                    </a>
                    <?php endif; ?>
                    
                    <!-- Páginas numeradas -->
                    <?php
                    $inicio = max(1, $pagina_atual - 2);
                    $fim = min($total_paginas, $pagina_atual + 2);
                    
                    for ($i = $inicio; $i <= $fim; $i++):
                    ?>
                    <?php if ($i == $pagina_atual): ?>
                    <span aria-current="page" class="z-10 bg-blue-50 border-blue-500 text-blue-600 relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                        <?php echo $i; ?>
                    </span>
                    <?php else: ?>
                    <a href="<?php echo gerarUrlPaginacao($i, $filtro_tab, $registros_por_pagina); ?>" 
                       class="bg-white border-gray-300 text-gray-500 hover:bg-gray-50 relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                        <?php echo $i; ?>
                    </a>
                    <?php endif; ?>
                    <?php endfor; ?>
                    
                    <!-- Próxima página -->
                    <?php if ($pagina_atual < $total_paginas): ?>
                    <a href="<?php echo gerarUrlPaginacao($pagina_atual + 1, $filtro_tab, $registros_por_pagina); ?>" 
                       class="relative inline-flex items-center px-2 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                        <span class="sr-only">Próximo</span>
                        <i class="fas fa-angle-right h-5 w-5"></i>
                    </a>
                    
                    <!-- Última página -->
                    <a href="<?php echo gerarUrlPaginacao($total_paginas, $filtro_tab, $registros_por_pagina); ?>" 
                       class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                        <span class="sr-only">Última</span>
                        <i class="fas fa-angle-double-right h-5 w-5"></i>
                    </a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Gráfico de documentos por tipo
    const ctxTipo = document.getElementById('graficoDocumentosPorTipo').getContext('2d');
    new Chart(ctxTipo, {
        type: 'pie',
        data: {
            labels: [
                <?php 
                foreach ($estatisticas_por_tipo as $tipo) {
                    echo "'" . addslashes($tipo['tipo_documento']) . "', ";
                }
                ?>
            ],
            datasets: [{
                data: [
                    <?php 
                    foreach ($estatisticas_por_tipo as $tipo) {
                        echo $tipo['total_documentos'] . ", ";
                    }
                    ?>
                ],
                backgroundColor: [
                    'rgba(52, 152, 219, 0.7)',
                    'rgba(46, 204, 113, 0.7)',
                    'rgba(155, 89, 182, 0.7)',
                    'rgba(241, 196, 15, 0.7)',
                    'rgba(231, 76, 60, 0.7)',
                    'rgba(26, 188, 156, 0.7)',
                    'rgba(52, 73, 94, 0.7)',
                    'rgba(230, 126, 34, 0.7)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                }
            }
        }
    });
      // Gráfico de documentos por polo
    const ctxPolo = document.getElementById('graficoDocumentosPorPolo').getContext('2d');
    new Chart(ctxPolo, {
        type: 'bar',
        data: {
            labels: [
                <?php 
                foreach ($estatisticas_por_polo as $polo) {
                    echo "'" . addslashes($polo['polo_nome']) . "', ";
                }
                ?>
            ],
            datasets: [{
                label: '<?php echo $filtro_tab === "emitidos" ? "Documentos Emitidos" : "Solicitações"; ?>',
                data: [
                    <?php 
                    foreach ($estatisticas_por_polo as $polo) {
                        echo $polo['total_documentos'] . ", ";
                    }
                    ?>
                ],
                backgroundColor: 'rgba(52, 152, 219, 0.7)',
                borderColor: 'rgba(52, 152, 219, 1)',
                borderWidth: 1
            }<?php if ($filtro_tab === 'solicitacoes' && !empty($estatisticas_por_polo) && isset($estatisticas_por_polo[0]['valor_total'])): ?>,
            {
                label: 'Valor Total (R$)',
                data: [
                    <?php 
                    foreach ($estatisticas_por_polo as $polo) {
                        echo ($polo['valor_total'] ?: 0) . ", ";
                    }
                    ?>
                ],
                backgroundColor: 'rgba(46, 204, 113, 0.7)',
                borderColor: 'rgba(46, 204, 113, 1)',
                borderWidth: 1,
                yAxisID: 'y1'
            }
            <?php endif; ?>]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    position: 'left'
                }<?php if ($filtro_tab === 'solicitacoes'): ?>,
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    beginAtZero: true,
                    grid: {
                        drawOnChartArea: false,
                    },
                }
                <?php endif; ?>
            },
            indexAxis: 'y'
        }
    });
</script>
