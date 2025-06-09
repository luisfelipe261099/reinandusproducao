<?php
// Obtém os filtros
$filtro_periodo = isset($_GET['periodo']) ? $_GET['periodo'] : 'ultimo_mes';
$filtro_data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : '';
$filtro_data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : '';
$filtro_tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
$filtro_status = isset($_GET['status']) ? $_GET['status'] : '';
$filtro_departamento = isset($_GET['departamento']) ? $_GET['departamento'] : '';

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

// Constrói a consulta SQL para o relatório de chamados
$sql_chamados = "SELECT 
                c.id,
                c.codigo,
                c.titulo,
                c.tipo,
                c.status,
                c.departamento,
                c.data_abertura,
                c.data_fechamento,
                cc.nome as categoria_nome,
                u_solicitante.nome as solicitante_nome,
                u_responsavel.nome as responsavel_nome,
                p.nome as polo_nome
                FROM chamados c
                JOIN categorias_chamados cc ON c.categoria_id = cc.id
                JOIN usuarios u_solicitante ON c.solicitante_id = u_solicitante.id
                LEFT JOIN usuarios u_responsavel ON c.responsavel_id = u_responsavel.id
                LEFT JOIN polos p ON c.polo_id = p.id
                WHERE c.data_abertura BETWEEN ? AND ?";

$params_chamados = [$filtro_data_inicio, $filtro_data_fim];

// Aplica os filtros
if (!empty($filtro_tipo)) {
    $sql_chamados .= " AND c.tipo = ?";
    $params_chamados[] = $filtro_tipo;
}

if (!empty($filtro_status)) {
    $sql_chamados .= " AND c.status = ?";
    $params_chamados[] = $filtro_status;
}

if (!empty($filtro_departamento)) {
    $sql_chamados .= " AND c.departamento = ?";
    $params_chamados[] = $filtro_departamento;
}

$sql_chamados .= " ORDER BY c.data_abertura DESC";

// Executa a consulta
$chamados = $db->fetchAll($sql_chamados, $params_chamados);

// Estatísticas de chamados
$sql_estatisticas = "SELECT 
                    COUNT(*) as total_chamados,
                    COUNT(CASE WHEN c.status IN ('aberto', 'em_andamento', 'aguardando_resposta', 'aguardando_aprovacao') THEN 1 END) as chamados_abertos,
                    COUNT(CASE WHEN c.status = 'resolvido' THEN 1 END) as chamados_resolvidos,
                    COUNT(CASE WHEN c.status IN ('fechado', 'cancelado') THEN 1 END) as chamados_fechados,
                    AVG(CASE WHEN c.data_fechamento IS NOT NULL THEN TIMESTAMPDIFF(HOUR, c.data_abertura, c.data_fechamento) END) as tempo_medio_resolucao
                    FROM chamados c
                    WHERE c.data_abertura BETWEEN ? AND ?";

$params_estatisticas = [$filtro_data_inicio, $filtro_data_fim];

// Aplica os filtros nas estatísticas
if (!empty($filtro_tipo)) {
    $sql_estatisticas .= " AND c.tipo = ?";
    $params_estatisticas[] = $filtro_tipo;
}

if (!empty($filtro_departamento)) {
    $sql_estatisticas .= " AND c.departamento = ?";
    $params_estatisticas[] = $filtro_departamento;
}

// Executa a consulta de estatísticas
$estatisticas = $db->fetchOne($sql_estatisticas, $params_estatisticas);

// Estatísticas por tipo
$sql_por_tipo = "SELECT 
                c.tipo,
                COUNT(*) as total_chamados
                FROM chamados c
                WHERE c.data_abertura BETWEEN ? AND ?
                GROUP BY c.tipo";

$params_por_tipo = [$filtro_data_inicio, $filtro_data_fim];

// Executa a consulta de estatísticas por tipo
$estatisticas_por_tipo = $db->fetchAll($sql_por_tipo, $params_por_tipo);

// Estatísticas por departamento
$sql_por_departamento = "SELECT 
                        c.departamento,
                        COUNT(*) as total_chamados
                        FROM chamados c
                        WHERE c.data_abertura BETWEEN ? AND ? AND c.departamento IS NOT NULL
                        GROUP BY c.departamento";

$params_por_departamento = [$filtro_data_inicio, $filtro_data_fim];

// Executa a consulta de estatísticas por departamento
$estatisticas_por_departamento = $db->fetchAll($sql_por_departamento, $params_por_departamento);

// Estatísticas por status
$sql_por_status = "SELECT 
                  c.status,
                  COUNT(*) as total_chamados
                  FROM chamados c
                  WHERE c.data_abertura BETWEEN ? AND ?
                  GROUP BY c.status";

$params_por_status = [$filtro_data_inicio, $filtro_data_fim];

// Executa a consulta de estatísticas por status
$estatisticas_por_status = $db->fetchAll($sql_por_status, $params_por_status);
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Relatório de Chamados</h1>
        
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
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                <select name="tipo" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    <option value="">Todos</option>
                    <option value="interno" <?php echo $filtro_tipo === 'interno' ? 'selected' : ''; ?>>Interno</option>
                    <option value="polo" <?php echo $filtro_tipo === 'polo' ? 'selected' : ''; ?>>Polo</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    <option value="">Todos</option>
                    <option value="aberto" <?php echo $filtro_status === 'aberto' ? 'selected' : ''; ?>>Aberto</option>
                    <option value="em_andamento" <?php echo $filtro_status === 'em_andamento' ? 'selected' : ''; ?>>Em Andamento</option>
                    <option value="aguardando_resposta" <?php echo $filtro_status === 'aguardando_resposta' ? 'selected' : ''; ?>>Aguardando Resposta</option>
                    <option value="aguardando_aprovacao" <?php echo $filtro_status === 'aguardando_aprovacao' ? 'selected' : ''; ?>>Aguardando Aprovação</option>
                    <option value="resolvido" <?php echo $filtro_status === 'resolvido' ? 'selected' : ''; ?>>Resolvido</option>
                    <option value="cancelado" <?php echo $filtro_status === 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                    <option value="fechado" <?php echo $filtro_status === 'fechado' ? 'selected' : ''; ?>>Fechado</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Departamento</label>
                <select name="departamento" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    <option value="">Todos</option>
                    <option value="secretaria" <?php echo $filtro_departamento === 'secretaria' ? 'selected' : ''; ?>>Secretaria</option>
                    <option value="financeiro" <?php echo $filtro_departamento === 'financeiro' ? 'selected' : ''; ?>>Financeiro</option>
                    <option value="suporte" <?php echo $filtro_departamento === 'suporte' ? 'selected' : ''; ?>>Suporte</option>
                    <option value="diretoria" <?php echo $filtro_departamento === 'diretoria' ? 'selected' : ''; ?>>Diretoria</option>
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
        <h2 class="text-lg font-semibold mb-4">Resumo de Chamados</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                <h3 class="text-sm font-medium text-blue-800 mb-2">Total de Chamados</h3>
                <p class="text-2xl font-bold text-blue-600"><?php echo $estatisticas['total_chamados']; ?></p>
            </div>
            
            <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                <h3 class="text-sm font-medium text-yellow-800 mb-2">Chamados Abertos</h3>
                <p class="text-2xl font-bold text-yellow-600"><?php echo $estatisticas['chamados_abertos']; ?> <span class="text-sm font-normal">(<?php echo $estatisticas['total_chamados'] > 0 ? round(($estatisticas['chamados_abertos'] / $estatisticas['total_chamados']) * 100, 1) : 0; ?>%)</span></p>
            </div>
            
            <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                <h3 class="text-sm font-medium text-green-800 mb-2">Chamados Resolvidos</h3>
                <p class="text-2xl font-bold text-green-600"><?php echo $estatisticas['chamados_resolvidos']; ?> <span class="text-sm font-normal">(<?php echo $estatisticas['total_chamados'] > 0 ? round(($estatisticas['chamados_resolvidos'] / $estatisticas['total_chamados']) * 100, 1) : 0; ?>%)</span></p>
            </div>
            
            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                <h3 class="text-sm font-medium text-gray-800 mb-2">Tempo Médio de Resolução</h3>
                <p class="text-2xl font-bold text-gray-600">
                    <?php 
                    if ($estatisticas['tempo_medio_resolucao']) {
                        $horas = floor($estatisticas['tempo_medio_resolucao']);
                        $minutos = round(($estatisticas['tempo_medio_resolucao'] - $horas) * 60);
                        echo $horas . 'h ' . $minutos . 'min';
                    } else {
                        echo 'N/A';
                    }
                    ?>
                </p>
            </div>
        </div>
    </div>
    
    <!-- Gráficos -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <!-- Chamados por Status -->
        <div class="bg-white shadow-md rounded-lg p-6">
            <h2 class="text-lg font-semibold mb-4">Chamados por Status</h2>
            <div class="h-64">
                <canvas id="graficoChamadosStatus"></canvas>
            </div>
        </div>
        
        <!-- Chamados por Tipo -->
        <div class="bg-white shadow-md rounded-lg p-6">
            <h2 class="text-lg font-semibold mb-4">Chamados por Tipo</h2>
            <div class="h-64">
                <canvas id="graficoChamadosTipo"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Tabela de Chamados -->
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <h2 class="text-lg font-semibold p-4 border-b">Lista de Chamados</h2>
        
        <?php if (empty($chamados)): ?>
        <div class="p-6 text-center">
            <p class="text-gray-500">Nenhum chamado encontrado para os filtros selecionados.</p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Título</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categoria</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Solicitante</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data Abertura</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($chamados as $chamado): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            <?php echo htmlspecialchars($chamado['codigo']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo htmlspecialchars($chamado['titulo']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo htmlspecialchars($chamado['categoria_nome']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php
                            $status_class = '';
                            $status_text = '';
                            
                            switch ($chamado['status']) {
                                case 'aberto':
                                    $status_class = 'bg-blue-100 text-blue-800';
                                    $status_text = 'Aberto';
                                    break;
                                case 'em_andamento':
                                    $status_class = 'bg-yellow-100 text-yellow-800';
                                    $status_text = 'Em Andamento';
                                    break;
                                case 'aguardando_resposta':
                                    $status_class = 'bg-purple-100 text-purple-800';
                                    $status_text = 'Aguardando Resposta';
                                    break;
                                case 'aguardando_aprovacao':
                                    $status_class = 'bg-indigo-100 text-indigo-800';
                                    $status_text = 'Aguardando Aprovação';
                                    break;
                                case 'resolvido':
                                    $status_class = 'bg-green-100 text-green-800';
                                    $status_text = 'Resolvido';
                                    break;
                                case 'cancelado':
                                    $status_class = 'bg-red-100 text-red-800';
                                    $status_text = 'Cancelado';
                                    break;
                                case 'fechado':
                                    $status_class = 'bg-gray-100 text-gray-800';
                                    $status_text = 'Fechado';
                                    break;
                            }
                            ?>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                                <?php echo $status_text; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo htmlspecialchars($chamado['solicitante_nome']); ?>
                            <?php if ($chamado['polo_nome']): ?>
                            <span class="text-xs text-gray-400">(<?php echo htmlspecialchars($chamado['polo_nome']); ?>)</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo date('d/m/Y H:i', strtotime($chamado['data_abertura'])); ?>
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
<script>
    // Função para alternar a visibilidade dos campos de data personalizada
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
    }
    
    // Gráfico de chamados por status
    const ctxStatus = document.getElementById('graficoChamadosStatus').getContext('2d');
    new Chart(ctxStatus, {
        type: 'pie',
        data: {
            labels: [
                <?php 
                foreach ($estatisticas_por_status as $status) {
                    $status_text = '';
                    switch ($status['status']) {
                        case 'aberto': $status_text = 'Aberto'; break;
                        case 'em_andamento': $status_text = 'Em Andamento'; break;
                        case 'aguardando_resposta': $status_text = 'Aguardando Resposta'; break;
                        case 'aguardando_aprovacao': $status_text = 'Aguardando Aprovação'; break;
                        case 'resolvido': $status_text = 'Resolvido'; break;
                        case 'cancelado': $status_text = 'Cancelado'; break;
                        case 'fechado': $status_text = 'Fechado'; break;
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
                    'rgba(52, 152, 219, 0.7)', // Aberto
                    'rgba(241, 196, 15, 0.7)', // Em Andamento
                    'rgba(155, 89, 182, 0.7)', // Aguardando Resposta
                    'rgba(52, 73, 94, 0.7)',   // Aguardando Aprovação
                    'rgba(46, 204, 113, 0.7)', // Resolvido
                    'rgba(231, 76, 60, 0.7)',  // Cancelado
                    'rgba(127, 140, 141, 0.7)' // Fechado
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
    
    // Gráfico de chamados por tipo
    const ctxTipo = document.getElementById('graficoChamadosTipo').getContext('2d');
    new Chart(ctxTipo, {
        type: 'doughnut',
        data: {
            labels: [
                <?php 
                foreach ($estatisticas_por_tipo as $tipo) {
                    echo "'" . ($tipo['tipo'] === 'interno' ? 'Interno' : 'Polo') . "', ";
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
                    'rgba(52, 152, 219, 0.7)', // Interno
                    'rgba(46, 204, 113, 0.7)'  // Polo
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
