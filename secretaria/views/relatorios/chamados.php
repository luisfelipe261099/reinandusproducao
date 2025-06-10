<?php
/**
 * Gera URL para paginação mantendo os filtros atuais
 * @param int $pagina Número da página
 * @return string URL completa
 */
function gerarUrlPaginacao($pagina) {
    $params = [
        'tipo' => 'chamados',
        'pagina' => $pagina,
        'per_page' => $_GET['per_page'] ?? 25
    ];
    
    // Preserva filtros existentes
    if (isset($_GET['periodo']) && $_GET['periodo'] != '') {
        $params['periodo'] = $_GET['periodo'];
    }
    if (isset($_GET['data_inicio']) && $_GET['data_inicio'] != '') {
        $params['data_inicio'] = $_GET['data_inicio'];
    }
    if (isset($_GET['data_fim']) && $_GET['data_fim'] != '') {
        $params['data_fim'] = $_GET['data_fim'];
    }
    if (isset($_GET['tipo_solicitacao']) && $_GET['tipo_solicitacao'] != '') {
        $params['tipo_solicitacao'] = $_GET['tipo_solicitacao'];
    }
    if (isset($_GET['status']) && $_GET['status'] != '') {
        $params['status'] = $_GET['status'];
    }
    
    return 'relatorios.php?' . http_build_query($params);
}

// Obtém os filtros
$filtro_periodo = isset($_GET['periodo']) ? $_GET['periodo'] : 'ultimo_mes';
$filtro_data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : '';
$filtro_data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : '';
$filtro_tipo_solicitacao = isset($_GET['tipo_solicitacao']) ? $_GET['tipo_solicitacao'] : '';
$filtro_status = isset($_GET['status']) ? $_GET['status'] : '';

// Define as datas com base no período selecionado
if (empty($filtro_data_inicio) || empty($filtro_data_fim)) {
    switch ($filtro_periodo) {
        case 'ultimo_mes':
            $filtro_data_inicio = date('Y-m-d', strtotime('-1 month'));
            $filtro_data_fim = date('Y-m-d');
            break;
        case 'ultimo_trimestre':
            $filtro_data_inicio = date('Y-m-d', strtotime('-3 months'));
            $filtro_data_fim = date('Y-m-d');
            break;
        case 'ultimo_semestre':
            $filtro_data_inicio = date('Y-m-d', strtotime('-6 months'));
            $filtro_data_fim = date('Y-m-d');
            break;
        case 'ultimo_ano':
            $filtro_data_inicio = date('Y-m-d', strtotime('-1 year'));
            $filtro_data_fim = date('Y-m-d');
            break;
        case 'personalizado':
            // Mantém as datas informadas
            break;
    }
}

// Constrói a consulta SQL para contar o total de solicitações
$sql_count = "SELECT COUNT(*) as total
              FROM solicitacoes_s s
              WHERE s.data_solicitacao BETWEEN ? AND ?";

$params_count = [$filtro_data_inicio . ' 00:00:00', $filtro_data_fim . ' 23:59:59'];

// Aplica os filtros na contagem
if (!empty($filtro_tipo_solicitacao)) {
    $sql_count .= " AND s.tipo_solicitacao = ?";
    $params_count[] = $filtro_tipo_solicitacao;
}

if (!empty($filtro_status)) {
    $sql_count .= " AND s.status = ?";
    $params_count[] = $filtro_status;
}

// Executa a consulta de contagem
$resultado_count = $db->fetchOne($sql_count, $params_count);
$total_registros = $resultado_count['total'];

// Calcula a paginação
$total_paginas = ceil($total_registros / $registros_por_pagina);

// Constrói a consulta SQL para o relatório de solicitações
$sql_chamados = "SELECT 
                s.id,
                s.protocolo,
                s.nome_empresa,
                s.cnpj,
                s.nome_solicitante,
                s.telefone,
                s.email,
                s.tipo_solicitacao,
                s.quantidade,
                s.observacao,
                s.data_solicitacao,
                s.status
                FROM solicitacoes_s s
                WHERE s.data_solicitacao BETWEEN ? AND ?";

$params_chamados = [$filtro_data_inicio . ' 00:00:00', $filtro_data_fim . ' 23:59:59'];

// Aplica os filtros
if (!empty($filtro_tipo_solicitacao)) {
    $sql_chamados .= " AND s.tipo_solicitacao = ?";
    $params_chamados[] = $filtro_tipo_solicitacao;
}

if (!empty($filtro_status)) {
    $sql_chamados .= " AND s.status = ?";
    $params_chamados[] = $filtro_status;
}

$sql_chamados .= " ORDER BY s.data_solicitacao DESC LIMIT $registros_por_pagina OFFSET $offset";

// Executa a consulta
$chamados = $db->fetchAll($sql_chamados, $params_chamados);

// Estatísticas de solicitações
$sql_estatisticas = "SELECT 
                    COUNT(*) as total_chamados,
                    COUNT(CASE WHEN s.status = 'Pendente' THEN 1 END) as chamados_abertos,
                    COUNT(CASE WHEN s.status = 'Concluido' THEN 1 END) as chamados_resolvidos,
                    COUNT(CASE WHEN s.status IN ('Cancelado', 'Rejeitado') THEN 1 END) as chamados_fechados,
                    0 as tempo_medio_resolucao
                    FROM solicitacoes_s s
                    WHERE s.data_solicitacao BETWEEN ? AND ?";

$params_estatisticas = [$filtro_data_inicio . ' 00:00:00', $filtro_data_fim . ' 23:59:59'];

// Aplica os filtros nas estatísticas
if (!empty($filtro_tipo_solicitacao)) {
    $sql_estatisticas .= " AND s.tipo_solicitacao = ?";
    $params_estatisticas[] = $filtro_tipo_solicitacao;
}

// Executa a consulta de estatísticas
$estatisticas = $db->fetchOne($sql_estatisticas, $params_estatisticas);

// Estatísticas por tipo
$sql_por_tipo = "SELECT 
                s.tipo_solicitacao,
                COUNT(*) as total_chamados
                FROM solicitacoes_s s
                WHERE s.data_solicitacao BETWEEN ? AND ?
                GROUP BY s.tipo_solicitacao";

$params_por_tipo = [$filtro_data_inicio . ' 00:00:00', $filtro_data_fim . ' 23:59:59'];

// Executa a consulta de estatísticas por tipo
$estatisticas_por_tipo = $db->fetchAll($sql_por_tipo, $params_por_tipo);

// Estatísticas por status
$sql_por_status = "SELECT 
                  s.status,
                  COUNT(*) as total_chamados
                  FROM solicitacoes_s s
                  WHERE s.data_solicitacao BETWEEN ? AND ?
                  GROUP BY s.status";

$params_por_status = [$filtro_data_inicio . ' 00:00:00', $filtro_data_fim . ' 23:59:59'];

// Executa a consulta de estatísticas por status
$estatisticas_por_status = $db->fetchAll($sql_por_status, $params_por_status);
?>

<div class="container mx-auto px-4 py-8">    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Relatório de Solicitações do Site</h1>
        
        <div class="flex space-x-2">
            <button onclick="window.print()" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                <i class="fas fa-print mr-2"></i> Imprimir
            </button>
            <a href="chamados.php" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                <i class="fas fa-ticket-alt mr-2"></i> Gerenciar Chamados
            </a>
        </div>
    </div>
    
    <!-- Filtros -->
    <div class="bg-white shadow-md rounded-lg p-4 mb-6 print:hidden">
        <h2 class="text-lg font-semibold mb-4">Filtros</h2>
        
        <form action="relatorios.php" method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <input type="hidden" name="tipo" value="chamados">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Período</label>
                <select name="periodo" id="periodo" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" onchange="toggleDataPersonalizada()">
                    <option value="ultimo_mes" <?php echo $filtro_periodo === 'ultimo_mes' ? 'selected' : ''; ?>>Último Mês</option>
                    <option value="ultimo_trimestre" <?php echo $filtro_periodo === 'ultimo_trimestre' ? 'selected' : ''; ?>>Último Trimestre</option>
                    <option value="ultimo_semestre" <?php echo $filtro_periodo === 'ultimo_semestre' ? 'selected' : ''; ?>>Último Semestre</option>
                    <option value="ultimo_ano" <?php echo $filtro_periodo === 'ultimo_ano' ? 'selected' : ''; ?>>Último Ano</option>
                    <option value="personalizado" <?php echo $filtro_periodo === 'personalizado' ? 'selected' : ''; ?>>Personalizado</option>
                </select>
            </div>
            
            <div id="data_inicio_container" class="<?php echo $filtro_periodo !== 'personalizado' ? 'hidden' : ''; ?>">
                <label class="block text-sm font-medium text-gray-700 mb-1">Data Início</label>
                <input type="date" name="data_inicio" value="<?php echo $filtro_data_inicio; ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
            </div>
            
            <div id="data_fim_container" class="<?php echo $filtro_periodo !== 'personalizado' ? 'hidden' : ''; ?>">
                <label class="block text-sm font-medium text-gray-700 mb-1">Data Fim</label>
                <input type="date" name="data_fim" value="<?php echo $filtro_data_fim; ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
            </div>            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Solicitação</label>
                <select name="tipo_solicitacao" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    <option value="">Todos</option>
                    <option value="Certificado" <?php echo $filtro_tipo_solicitacao === 'Certificado' ? 'selected' : ''; ?>>Certificado</option>
                    <option value="Diploma" <?php echo $filtro_tipo_solicitacao === 'Diploma' ? 'selected' : ''; ?>>Diploma</option>
                    <option value="Declaracao" <?php echo $filtro_tipo_solicitacao === 'Declaracao' ? 'selected' : ''; ?>>Declaração</option>
                    <option value="Historico" <?php echo $filtro_tipo_solicitacao === 'Historico' ? 'selected' : ''; ?>>Histórico</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    <option value="">Todos</option>
                    <option value="Pendente" <?php echo $filtro_status === 'Pendente' ? 'selected' : ''; ?>>Pendente</option>
                    <option value="Concluido" <?php echo $filtro_status === 'Concluido' ? 'selected' : ''; ?>>Concluído</option>
                    <option value="Cancelado" <?php echo $filtro_status === 'Cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                    <option value="Rejeitado" <?php echo $filtro_status === 'Rejeitado' ? 'selected' : ''; ?>>Rejeitado</option>
                </select>
            </div>
            
            <div class="flex items-end">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    <i class="fas fa-filter mr-2"></i> Filtrar
                </button>
                
                <a href="relatorios.php?tipo=chamados" class="ml-2 bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
                    <i class="fas fa-times mr-2"></i> Limpar
                </a>
            </div>
        </form>
    </div>
      <!-- Resumo -->
    <div class="bg-white shadow-md rounded-lg p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4">Resumo de Solicitações</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                <h3 class="text-sm font-medium text-blue-800 mb-2">Total de Solicitações</h3>
                <p class="text-2xl font-bold text-blue-600"><?php echo $estatisticas['total_chamados']; ?></p>
            </div>
            
            <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                <h3 class="text-sm font-medium text-yellow-800 mb-2">Pendentes</h3>
                <p class="text-2xl font-bold text-yellow-600"><?php echo $estatisticas['chamados_abertos']; ?> <span class="text-sm font-normal">(<?php echo $estatisticas['total_chamados'] > 0 ? round(($estatisticas['chamados_abertos'] / $estatisticas['total_chamados']) * 100, 1) : 0; ?>%)</span></p>
            </div>
            
            <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                <h3 class="text-sm font-medium text-green-800 mb-2">Concluídas</h3>
                <p class="text-2xl font-bold text-green-600"><?php echo $estatisticas['chamados_resolvidos']; ?> <span class="text-sm font-normal">(<?php echo $estatisticas['total_chamados'] > 0 ? round(($estatisticas['chamados_resolvidos'] / $estatisticas['total_chamados']) * 100, 1) : 0; ?>%)</span></p>
            </div>
            
            <div class="bg-red-50 p-4 rounded-lg border border-red-200">
                <h3 class="text-sm font-medium text-red-800 mb-2">Canceladas/Rejeitadas</h3>
                <p class="text-2xl font-bold text-red-600"><?php echo $estatisticas['chamados_fechados']; ?> <span class="text-sm font-normal">(<?php echo $estatisticas['total_chamados'] > 0 ? round(($estatisticas['chamados_fechados'] / $estatisticas['total_chamados']) * 100, 1) : 0; ?>%)</span></p>
            </div>
        </div>
    </div>
      <!-- Gráficos -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <!-- Solicitações por Status -->
        <div class="bg-white shadow-md rounded-lg p-6">
            <h2 class="text-lg font-semibold mb-4">Solicitações por Status</h2>
            <div class="h-64">
                <canvas id="graficoChamadosStatus"></canvas>
            </div>
        </div>
        
        <!-- Solicitações por Tipo -->
        <div class="bg-white shadow-md rounded-lg p-6">
            <h2 class="text-lg font-semibold mb-4">Solicitações por Tipo</h2>
            <div class="h-64">
                <canvas id="graficoChamadosTipo"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Controles de Paginação e Registros por Página -->
    <div class="bg-white shadow-md rounded-lg p-4 mb-6 print:hidden">
        <div class="flex justify-between items-center flex-wrap gap-4">
            <div class="flex items-center space-x-2">
                <label for="registros_por_pagina" class="text-sm font-medium text-gray-700">
                    Registros por página:
                </label>
                <select name="registros_por_pagina" id="registros_por_pagina" 
                        class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-sm"
                        onchange="alterarRegistrosPorPagina()">
                    <option value="10" <?php echo $registros_por_pagina == 10 ? 'selected' : ''; ?>>10</option>
                    <option value="25" <?php echo $registros_por_pagina == 25 ? 'selected' : ''; ?>>25</option>
                    <option value="50" <?php echo $registros_por_pagina == 50 ? 'selected' : ''; ?>>50</option>
                    <option value="100" <?php echo $registros_por_pagina == 100 ? 'selected' : ''; ?>>100</option>
                </select>
            </div>

            <div class="text-sm text-gray-600">
                Mostrando <?php echo min($offset + 1, $total_registros); ?> a 
                <?php echo min($offset + $registros_por_pagina, $total_registros); ?> 
                de <?php echo $total_registros; ?> registros
            </div>

            <?php if ($total_paginas > 1): ?>
            <nav class="flex items-center space-x-1" aria-label="Pagination">
                <!-- Primeira página -->
                <?php if ($pagina_atual > 1): ?>
                <a href="<?php echo gerarUrlPaginacao(1); ?>" 
                   class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <i class="fas fa-angle-double-left mr-1"></i> Primeira
                </a>
                <?php endif; ?>

                <!-- Página anterior -->
                <?php if ($pagina_atual > 1): ?>
                <a href="<?php echo gerarUrlPaginacao($pagina_atual - 1); ?>" 
                   class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <i class="fas fa-angle-left mr-1"></i> Anterior
                </a>
                <?php endif; ?>

                <!-- Páginas numéricas -->
                <?php
                $inicio_paginacao = max(1, $pagina_atual - 2);
                $fim_paginacao = min($total_paginas, $pagina_atual + 2);
                
                for ($i = $inicio_paginacao; $i <= $fim_paginacao; $i++):
                    if ($i == $pagina_atual):
                ?>
                <span class="inline-flex items-center px-3 py-2 text-sm font-medium text-blue-600 bg-blue-50 border border-blue-300 rounded-md">
                    <?php echo $i; ?>
                </span>
                <?php else: ?>
                <a href="<?php echo gerarUrlPaginacao($i); ?>" 
                   class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <?php echo $i; ?>
                </a>
                <?php 
                    endif;
                endfor; 
                ?>

                <!-- Próxima página -->
                <?php if ($pagina_atual < $total_paginas): ?>
                <a href="<?php echo gerarUrlPaginacao($pagina_atual + 1); ?>" 
                   class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    Próxima <i class="fas fa-angle-right ml-1"></i>
                </a>
                <?php endif; ?>

                <!-- Última página -->
                <?php if ($pagina_atual < $total_paginas): ?>
                <a href="<?php echo gerarUrlPaginacao($total_paginas); ?>" 
                   class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    Última <i class="fas fa-angle-double-right ml-1"></i>
                </a>
                <?php endif; ?>
            </nav>
            <?php endif; ?>
        </div>
    </div>
      <!-- Tabela de Solicitações -->
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <h2 class="text-lg font-semibold p-4 border-b">Lista de Solicitações do Site</h2>
        
        <?php if (empty($chamados)): ?>
        <div class="p-6 text-center">
            <p class="text-gray-500">Nenhuma solicitação encontrada para os filtros selecionados.</p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Protocolo</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Empresa</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Solicitante</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantidade</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data Solicitação</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($chamados as $chamado): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            <?php echo htmlspecialchars($chamado['protocolo']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <div>
                                <div class="font-medium"><?php echo htmlspecialchars($chamado['nome_empresa']); ?></div>
                                <div class="text-xs text-gray-400"><?php echo htmlspecialchars($chamado['cnpj']); ?></div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <div>
                                <div class="font-medium"><?php echo htmlspecialchars($chamado['nome_solicitante']); ?></div>
                                <div class="text-xs text-gray-400"><?php echo htmlspecialchars($chamado['email']); ?></div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo htmlspecialchars($chamado['tipo_solicitacao']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo number_format($chamado['quantidade']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php
                            $status_class = '';
                            $status_text = '';
                            
                            switch ($chamado['status']) {
                                case 'Pendente':
                                    $status_class = 'bg-yellow-100 text-yellow-800';
                                    $status_text = 'Pendente';
                                    break;
                                case 'Concluido':
                                    $status_class = 'bg-green-100 text-green-800';
                                    $status_text = 'Concluído';
                                    break;
                                case 'Cancelado':
                                    $status_class = 'bg-red-100 text-red-800';
                                    $status_text = 'Cancelado';
                                    break;
                                case 'Rejeitado':
                                    $status_class = 'bg-red-100 text-red-800';
                                    $status_text = 'Rejeitado';
                                    break;
                                default:
                                    $status_class = 'bg-gray-100 text-gray-800';
                                    $status_text = $chamado['status'];
                                    break;
                            }
                            ?>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                                <?php echo $status_text; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo date('d/m/Y H:i', strtotime($chamado['data_solicitacao'])); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>    // Função para alternar a visibilidade dos campos de data personalizada
    function toggleDataPersonalizada() {
        const periodo = document.getElementById('periodo').value;
        const dataInicioContainer = document.getElementById('data_inicio_container');
        const dataFimContainer = document.getElementById('data_fim_container');
        
        if (periodo === 'personalizado') {
            dataInicioContainer.classList.remove('hidden');
            dataFimContainer.classList.remove('hidden');
        } else {
            dataInicioContainer.classList.add('hidden');
            dataFimContainer.classList.add('hidden');
        }
    }    // Função para alterar registros por página
    function alterarRegistrosPorPagina() {
        const registrosPorPagina = document.getElementById('registros_por_pagina').value;
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set('per_page', registrosPorPagina);
        urlParams.set('pagina', '1'); // Volta para a primeira página
        window.location.search = urlParams.toString();
    }
      // Gráfico de solicitações por status
    const ctxStatus = document.getElementById('graficoChamadosStatus').getContext('2d');
    new Chart(ctxStatus, {
        type: 'pie',
        data: {
            labels: [
                <?php 
                foreach ($estatisticas_por_status as $status) {
                    $status_text = '';
                    switch ($status['status']) {
                        case 'Pendente': $status_text = 'Pendente'; break;
                        case 'Concluido': $status_text = 'Concluído'; break;
                        case 'Cancelado': $status_text = 'Cancelado'; break;
                        case 'Rejeitado': $status_text = 'Rejeitado'; break;
                        default: $status_text = $status['status']; break;
                    }
                    echo "'" . $status_text . "', ";
                }
                ?>
            ],
            datasets: [{
                data: [
                    <?php 
                    foreach ($estatisticas_por_status as $status) {
                        echo $status['total_chamados'] . ", ";
                    }
                    ?>
                ],
                backgroundColor: [
                    'rgba(241, 196, 15, 0.7)', // Pendente
                    'rgba(46, 204, 113, 0.7)', // Concluído
                    'rgba(231, 76, 60, 0.7)',  // Cancelado
                    'rgba(231, 76, 60, 0.8)'   // Rejeitado
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
      // Gráfico de solicitações por tipo
    const ctxTipo = document.getElementById('graficoChamadosTipo').getContext('2d');
    new Chart(ctxTipo, {
        type: 'doughnut',
        data: {
            labels: [
                <?php 
                foreach ($estatisticas_por_tipo as $tipo) {
                    echo "'" . htmlspecialchars($tipo['tipo_solicitacao']) . "', ";
                }
                ?>
            ],
            datasets: [{
                data: [
                    <?php 
                    foreach ($estatisticas_por_tipo as $tipo) {
                        echo $tipo['total_chamados'] . ", ";
                    }
                    ?>
                ],
                backgroundColor: [
                    'rgba(52, 152, 219, 0.7)', // Certificado
                    'rgba(46, 204, 113, 0.7)', // Diploma
                    'rgba(155, 89, 182, 0.7)', // Declaracao
                    'rgba(241, 196, 15, 0.7)'  // Historico
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
</script>
