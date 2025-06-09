<?php
// Obtém os filtros
$filtro_tipo_documento = isset($_GET['tipo_documento_id']) ? (int)$_GET['tipo_documento_id'] : 0;
$filtro_polo = isset($_GET['polo_id']) ? (int)$_GET['polo_id'] : 0;
$filtro_status = isset($_GET['status']) ? $_GET['status'] : '';
$filtro_data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : date('Y-m-d', strtotime('-6 months'));
$filtro_data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : date('Y-m-d');

// Obtém a lista de tipos de documentos para o filtro
$sql_tipos_documentos = "SELECT id, nome FROM tipos_documentos WHERE status = 'ativo' ORDER BY nome ASC";
$tipos_documentos = $db->fetchAll($sql_tipos_documentos);

// Obtém a lista de polos para o filtro
$sql_polos = "SELECT id, nome FROM polos WHERE status = 'ativo' ORDER BY nome ASC";
$polos = $db->fetchAll($sql_polos);

// Constrói a consulta SQL para o relatório de documentos
$sql_documentos = "SELECT 
                   d.id,
                   d.titulo,
                   td.nome as tipo_documento,
                   a.nome as aluno_nome,
                   p.nome as polo_nome,
                   d.data_emissao,
                   d.status,
                   d.arquivo_nome
                   FROM documentos d
                   JOIN tipos_documentos td ON d.tipo_documento_id = td.id
                   JOIN alunos a ON d.aluno_id = a.id
                   JOIN polos p ON a.polo_id = p.id
                   WHERE d.data_emissao BETWEEN ? AND ?";

$params_documentos = [$filtro_data_inicio, $filtro_data_fim];

// Aplica os filtros
if ($filtro_tipo_documento > 0) {
    $sql_documentos .= " AND d.tipo_documento_id = ?";
    $params_documentos[] = $filtro_tipo_documento;
}

if ($filtro_polo > 0) {
    $sql_documentos .= " AND p.id = ?";
    $params_documentos[] = $filtro_polo;
}

if (!empty($filtro_status)) {
    $sql_documentos .= " AND d.status = ?";
    $params_documentos[] = $filtro_status;
}

$sql_documentos .= " ORDER BY d.data_emissao DESC";

// Executa a consulta
$documentos = $db->fetchAll($sql_documentos, $params_documentos);

// Estatísticas de documentos
$sql_estatisticas = "SELECT 
                     COUNT(*) as total_documentos,
                     COUNT(CASE WHEN d.status = 'ativo' THEN 1 END) as documentos_ativos,
                     COUNT(CASE WHEN d.status = 'inativo' THEN 1 END) as documentos_inativos,
                     COUNT(CASE WHEN d.status = 'cancelado' THEN 1 END) as documentos_cancelados
                     FROM documentos d
                     WHERE d.data_emissao BETWEEN ? AND ?";

$params_estatisticas = [$filtro_data_inicio, $filtro_data_fim];

// Aplica os filtros nas estatísticas
if ($filtro_tipo_documento > 0) {
    $sql_estatisticas .= " AND d.tipo_documento_id = ?";
    $params_estatisticas[] = $filtro_tipo_documento;
}

if ($filtro_polo > 0) {
    $sql_estatisticas .= " AND (SELECT polo_id FROM alunos WHERE id = d.aluno_id) = ?";
    $params_estatisticas[] = $filtro_polo;
}

// Executa a consulta de estatísticas
$estatisticas = $db->fetchOne($sql_estatisticas, $params_estatisticas);

// Estatísticas por tipo de documento
$sql_por_tipo = "SELECT 
                td.nome as tipo_documento,
                COUNT(*) as total_documentos
                FROM documentos d
                JOIN tipos_documentos td ON d.tipo_documento_id = td.id
                WHERE d.data_emissao BETWEEN ? AND ?";

$params_por_tipo = [$filtro_data_inicio, $filtro_data_fim];

// Aplica os filtros nas estatísticas por tipo
if ($filtro_polo > 0) {
    $sql_por_tipo .= " AND (SELECT polo_id FROM alunos WHERE id = d.aluno_id) = ?";
    $params_por_tipo[] = $filtro_polo;
}

if (!empty($filtro_status)) {
    $sql_por_tipo .= " AND d.status = ?";
    $params_por_tipo[] = $filtro_status;
}

$sql_por_tipo .= " GROUP BY td.id ORDER BY total_documentos DESC";

// Executa a consulta de estatísticas por tipo
$estatisticas_por_tipo = $db->fetchAll($sql_por_tipo, $params_por_tipo);

// Estatísticas por polo
$sql_por_polo = "SELECT 
                p.nome as polo_nome,
                COUNT(*) as total_documentos
                FROM documentos d
                JOIN alunos a ON d.aluno_id = a.id
                JOIN polos p ON a.polo_id = p.id
                WHERE d.data_emissao BETWEEN ? AND ?";

$params_por_polo = [$filtro_data_inicio, $filtro_data_fim];

// Aplica os filtros nas estatísticas por polo
if ($filtro_tipo_documento > 0) {
    $sql_por_polo .= " AND d.tipo_documento_id = ?";
    $params_por_polo[] = $filtro_tipo_documento;
}

if (!empty($filtro_status)) {
    $sql_por_polo .= " AND d.status = ?";
    $params_por_polo[] = $filtro_status;
}

$sql_por_polo .= " GROUP BY p.id ORDER BY total_documentos DESC";

// Executa a consulta de estatísticas por polo
$estatisticas_por_polo = $db->fetchAll($sql_por_polo, $params_por_polo);
?>

<div class="container mx-auto px-4 py-8">
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
    </div>
    
    <!-- Filtros -->
    <div class="bg-white shadow-md rounded-lg p-4 mb-6 print:hidden">
        <h2 class="text-lg font-semibold mb-4">Filtros</h2>
        
        <form action="relatorios.php" method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <input type="hidden" name="tipo" value="documentos">
            
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
                    <option value="ativo" <?php echo $filtro_status === 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                    <option value="inativo" <?php echo $filtro_status === 'inativo' ? 'selected' : ''; ?>>Inativo</option>
                    <option value="cancelado" <?php echo $filtro_status === 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
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
            
            <div class="flex items-end">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    <i class="fas fa-filter mr-2"></i> Filtrar
                </button>
                
                <a href="relatorios.php?tipo=documentos" class="ml-2 bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
                    <i class="fas fa-times mr-2"></i> Limpar
                </a>
            </div>
        </form>
    </div>
    
    <!-- Resumo -->
    <div class="bg-white shadow-md rounded-lg p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4">Resumo de Documentos</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                <h3 class="text-sm font-medium text-blue-800 mb-2">Total de Documentos</h3>
                <p class="text-2xl font-bold text-blue-600"><?php echo $estatisticas['total_documentos']; ?></p>
            </div>
            
            <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                <h3 class="text-sm font-medium text-green-800 mb-2">Documentos Ativos</h3>
                <p class="text-2xl font-bold text-green-600"><?php echo $estatisticas['documentos_ativos']; ?> <span class="text-sm font-normal">(<?php echo $estatisticas['total_documentos'] > 0 ? round(($estatisticas['documentos_ativos'] / $estatisticas['total_documentos']) * 100, 1) : 0; ?>%)</span></p>
            </div>
            
            <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                <h3 class="text-sm font-medium text-yellow-800 mb-2">Documentos Inativos</h3>
                <p class="text-2xl font-bold text-yellow-600"><?php echo $estatisticas['documentos_inativos']; ?> <span class="text-sm font-normal">(<?php echo $estatisticas['total_documentos'] > 0 ? round(($estatisticas['documentos_inativos'] / $estatisticas['total_documentos']) * 100, 1) : 0; ?>%)</span></p>
            </div>
            
            <div class="bg-red-50 p-4 rounded-lg border border-red-200">
                <h3 class="text-sm font-medium text-red-800 mb-2">Documentos Cancelados</h3>
                <p class="text-2xl font-bold text-red-600"><?php echo $estatisticas['documentos_cancelados']; ?> <span class="text-sm font-normal">(<?php echo $estatisticas['total_documentos'] > 0 ? round(($estatisticas['documentos_cancelados'] / $estatisticas['total_documentos']) * 100, 1) : 0; ?>%)</span></p>
            </div>
        </div>
    </div>
    
    <!-- Gráficos -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <!-- Documentos por Tipo -->
        <div class="bg-white shadow-md rounded-lg p-6">
            <h2 class="text-lg font-semibold mb-4">Documentos por Tipo</h2>
            <div class="h-64">
                <canvas id="graficoDocumentosPorTipo"></canvas>
            </div>
        </div>
        
        <!-- Documentos por Polo -->
        <div class="bg-white shadow-md rounded-lg p-6">
            <h2 class="text-lg font-semibold mb-4">Documentos por Polo</h2>
            <div class="h-64">
                <canvas id="graficoDocumentosPorPolo"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Tabela de Documentos -->
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <h2 class="text-lg font-semibold p-4 border-b">Lista de Documentos</h2>
        
        <?php if (empty($documentos)): ?>
        <div class="p-6 text-center">
            <p class="text-gray-500">Nenhum documento encontrado para os filtros selecionados.</p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Título</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aluno</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Polo</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data Emissão</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($documentos as $documento): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            <?php echo htmlspecialchars($documento['titulo']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo htmlspecialchars($documento['tipo_documento']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo htmlspecialchars($documento['aluno_nome']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo htmlspecialchars($documento['polo_nome']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo date('d/m/Y', strtotime($documento['data_emissao'])); ?>
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
                                case 'inativo':
                                    $status_class = 'bg-yellow-100 text-yellow-800';
                                    $status_text = 'Inativo';
                                    break;
                                case 'cancelado':
                                    $status_class = 'bg-red-100 text-red-800';
                                    $status_text = 'Cancelado';
                                    break;
                            }
                            ?>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                                <?php echo $status_text; ?>
                            </span>
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
                label: 'Documentos',
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
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            },
            indexAxis: 'y'
        }
    });
</script>
