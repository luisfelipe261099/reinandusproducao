<?php
// Obtém os filtros
$filtro_curso = isset($_GET['curso_id']) ? (int)$_GET['curso_id'] : 0;
$filtro_polo = isset($_GET['polo_id']) ? (int)$_GET['polo_id'] : 0;
$filtro_turma = isset($_GET['turma_id']) ? (int)$_GET['turma_id'] : 0;
$filtro_periodo = isset($_GET['periodo']) ? $_GET['periodo'] : 'ultimo_semestre';
$filtro_data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : '';
$filtro_data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : '';

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

// Obtém a lista de cursos para o filtro
$sql_cursos = "SELECT id, nome FROM cursos WHERE status = 'ativo' ORDER BY nome ASC";
$cursos = $db->fetchAll($sql_cursos);

// Obtém a lista de polos para o filtro
$sql_polos = "SELECT id, nome FROM polos WHERE status = 'ativo' ORDER BY nome ASC";
$polos = $db->fetchAll($sql_polos);

// Obtém a lista de turmas para o filtro
$sql_turmas = "SELECT t.id, t.nome, c.nome as curso_nome, p.nome as polo_nome 
               FROM turmas t 
               JOIN cursos c ON t.curso_id = c.id 
               JOIN polos p ON t.polo_id = p.id 
               WHERE t.status IN ('planejada', 'em_andamento') 
               ORDER BY t.nome ASC";
$turmas = $db->fetchAll($sql_turmas);

// Constrói a consulta SQL para o relatório de desempenho
$sql_desempenho = "SELECT 
                    c.nome as curso_nome,
                    p.nome as polo_nome,
                    t.nome as turma_nome,
                    COUNT(DISTINCT m.id) as total_matriculas,
                    COUNT(DISTINCT CASE WHEN m.status = 'ativo' THEN m.id END) as matriculas_ativas,
                    COUNT(DISTINCT CASE WHEN m.status = 'concluído' THEN m.id END) as matriculas_concluidas,
                    COUNT(DISTINCT CASE WHEN m.status = 'trancado' THEN m.id END) as matriculas_trancadas,
                    COUNT(DISTINCT CASE WHEN m.status = 'cancelado' THEN m.id END) as matriculas_canceladas,
                    AVG(nd.nota) as media_notas,
                    AVG(nd.frequencia) as media_frequencia
                FROM matriculas m
                JOIN cursos c ON m.curso_id = c.id
                JOIN polos p ON m.polo_id = p.id
                LEFT JOIN turmas t ON m.turma_id = t.id
                LEFT JOIN notas_disciplinas nd ON m.id = nd.matricula_id
                WHERE m.data_matricula BETWEEN ? AND ?";

$params_desempenho = [$filtro_data_inicio, $filtro_data_fim];

// Aplica os filtros
if ($filtro_curso > 0) {
    $sql_desempenho .= " AND c.id = ?";
    $params_desempenho[] = $filtro_curso;
}

if ($filtro_polo > 0) {
    $sql_desempenho .= " AND p.id = ?";
    $params_desempenho[] = $filtro_polo;
}

if ($filtro_turma > 0) {
    $sql_desempenho .= " AND t.id = ?";
    $params_desempenho[] = $filtro_turma;
}

$sql_desempenho .= " GROUP BY c.id, p.id, t.id ORDER BY c.nome, p.nome, t.nome";

// Executa a consulta
$dados_desempenho = $db->fetchAll($sql_desempenho, $params_desempenho);

// Calcula os totais
$total_matriculas = 0;
$total_ativas = 0;
$total_concluidas = 0;
$total_trancadas = 0;
$total_canceladas = 0;
$soma_notas = 0;
$soma_frequencia = 0;
$count_notas = 0;
$count_frequencia = 0;

foreach ($dados_desempenho as $dado) {
    $total_matriculas += $dado['total_matriculas'];
    $total_ativas += $dado['matriculas_ativas'];
    $total_concluidas += $dado['matriculas_concluidas'];
    $total_trancadas += $dado['matriculas_trancadas'];
    $total_canceladas += $dado['matriculas_canceladas'];
    
    if ($dado['media_notas'] !== null) {
        $soma_notas += $dado['media_notas'] * $dado['total_matriculas'];
        $count_notas += $dado['total_matriculas'];
    }
    
    if ($dado['media_frequencia'] !== null) {
        $soma_frequencia += $dado['media_frequencia'] * $dado['total_matriculas'];
        $count_frequencia += $dado['total_matriculas'];
    }
}

$media_geral_notas = $count_notas > 0 ? $soma_notas / $count_notas : 0;
$media_geral_frequencia = $count_frequencia > 0 ? $soma_frequencia / $count_frequencia : 0;
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Relatório de Desempenho Acadêmico</h1>
        
        <div class="flex space-x-2">
            <a href="relatorios.php?tipo=estatisticas" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                <i class="fas fa-chart-pie mr-2"></i> Ver Estatísticas
            </a>
            <button onclick="window.print()" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                <i class="fas fa-print mr-2"></i> Imprimir
            </button>
        </div>
    </div>
    
    <!-- Filtros -->
    <div class="bg-white shadow-md rounded-lg p-4 mb-6 print:hidden">
        <h2 class="text-lg font-semibold mb-4">Filtros</h2>
        
        <form action="relatorios.php" method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <input type="hidden" name="tipo" value="desempenho">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Curso</label>
                <select name="curso_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    <option value="0">Todos os Cursos</option>
                    <?php foreach ($cursos as $curso): ?>
                    <option value="<?php echo $curso['id']; ?>" <?php echo $filtro_curso == $curso['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($curso['nome']); ?>
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
                <label class="block text-sm font-medium text-gray-700 mb-1">Turma</label>
                <select name="turma_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    <option value="0">Todas as Turmas</option>
                    <?php foreach ($turmas as $turma): ?>
                    <option value="<?php echo $turma['id']; ?>" <?php echo $filtro_turma == $turma['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($turma['nome'] . ' - ' . $turma['curso_nome'] . ' (' . $turma['polo_nome'] . ')'); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
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
            
            <div class="flex items-end">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    <i class="fas fa-filter mr-2"></i> Filtrar
                </button>
                
                <a href="relatorios.php?tipo=desempenho" class="ml-2 bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
                    <i class="fas fa-times mr-2"></i> Limpar
                </a>
            </div>
        </form>
    </div>
    
    <!-- Resumo -->
    <div class="bg-white shadow-md rounded-lg p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4">Resumo do Desempenho</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                <h3 class="text-sm font-medium text-blue-800 mb-2">Total de Matrículas</h3>
                <p class="text-2xl font-bold text-blue-600"><?php echo $total_matriculas; ?></p>
            </div>
            
            <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                <h3 class="text-sm font-medium text-green-800 mb-2">Matrículas Ativas</h3>
                <p class="text-2xl font-bold text-green-600"><?php echo $total_ativas; ?> <span class="text-sm font-normal">(<?php echo $total_matriculas > 0 ? round(($total_ativas / $total_matriculas) * 100, 1) : 0; ?>%)</span></p>
            </div>
            
            <div class="bg-purple-50 p-4 rounded-lg border border-purple-200">
                <h3 class="text-sm font-medium text-purple-800 mb-2">Média Geral de Notas</h3>
                <p class="text-2xl font-bold text-purple-600"><?php echo number_format($media_geral_notas, 1, ',', '.'); ?></p>
            </div>
            
            <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                <h3 class="text-sm font-medium text-yellow-800 mb-2">Média Geral de Frequência</h3>
                <p class="text-2xl font-bold text-yellow-600"><?php echo number_format($media_geral_frequencia, 1, ',', '.'); ?>%</p>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-indigo-50 p-4 rounded-lg border border-indigo-200">
                <h3 class="text-sm font-medium text-indigo-800 mb-2">Matrículas Concluídas</h3>
                <p class="text-xl font-bold text-indigo-600"><?php echo $total_concluidas; ?> <span class="text-sm font-normal">(<?php echo $total_matriculas > 0 ? round(($total_concluidas / $total_matriculas) * 100, 1) : 0; ?>%)</span></p>
            </div>
            
            <div class="bg-orange-50 p-4 rounded-lg border border-orange-200">
                <h3 class="text-sm font-medium text-orange-800 mb-2">Matrículas Trancadas</h3>
                <p class="text-xl font-bold text-orange-600"><?php echo $total_trancadas; ?> <span class="text-sm font-normal">(<?php echo $total_matriculas > 0 ? round(($total_trancadas / $total_matriculas) * 100, 1) : 0; ?>%)</span></p>
            </div>
            
            <div class="bg-red-50 p-4 rounded-lg border border-red-200">
                <h3 class="text-sm font-medium text-red-800 mb-2">Matrículas Canceladas</h3>
                <p class="text-xl font-bold text-red-600"><?php echo $total_canceladas; ?> <span class="text-sm font-normal">(<?php echo $total_matriculas > 0 ? round(($total_canceladas / $total_matriculas) * 100, 1) : 0; ?>%)</span></p>
            </div>
        </div>
    </div>
    
    <!-- Gráficos -->
    <div class="bg-white shadow-md rounded-lg p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4">Gráficos de Desempenho</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h3 class="text-md font-medium text-gray-700 mb-2">Distribuição de Matrículas por Status</h3>
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 h-64">
                    <canvas id="graficoStatusMatriculas"></canvas>
                </div>
            </div>
            
            <div>
                <h3 class="text-md font-medium text-gray-700 mb-2">Média de Notas por Curso</h3>
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 h-64">
                    <canvas id="graficoNotasCursos"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tabela de Dados -->
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <h2 class="text-lg font-semibold p-4 border-b">Dados Detalhados</h2>
        
        <?php if (empty($dados_desempenho)): ?>
        <div class="p-6 text-center">
            <p class="text-gray-500">Nenhum dado encontrado para os filtros selecionados.</p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Curso</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Polo</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Turma</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Matrículas</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ativas</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Concluídas</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Média Notas</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Média Frequência</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($dados_desempenho as $dado): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            <?php echo htmlspecialchars($dado['curso_nome']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo htmlspecialchars($dado['polo_nome']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo htmlspecialchars($dado['turma_nome'] ?: 'N/A'); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo $dado['total_matriculas']; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo $dado['matriculas_ativas']; ?> (<?php echo $dado['total_matriculas'] > 0 ? round(($dado['matriculas_ativas'] / $dado['total_matriculas']) * 100, 1) : 0; ?>%)
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo $dado['matriculas_concluidas']; ?> (<?php echo $dado['total_matriculas'] > 0 ? round(($dado['matriculas_concluidas'] / $dado['total_matriculas']) * 100, 1) : 0; ?>%)
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo $dado['media_notas'] !== null ? number_format($dado['media_notas'], 1, ',', '.') : 'N/A'; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo $dado['media_frequencia'] !== null ? number_format($dado['media_frequencia'], 1, ',', '.') . '%' : 'N/A'; ?>
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
    
    // Gráfico de distribuição de matrículas por status
    const ctxStatus = document.getElementById('graficoStatusMatriculas').getContext('2d');
    new Chart(ctxStatus, {
        type: 'pie',
        data: {
            labels: ['Ativas', 'Concluídas', 'Trancadas', 'Canceladas'],
            datasets: [{
                data: [<?php echo $total_ativas; ?>, <?php echo $total_concluidas; ?>, <?php echo $total_trancadas; ?>, <?php echo $total_canceladas; ?>],
                backgroundColor: [
                    'rgba(46, 204, 113, 0.7)',
                    'rgba(52, 152, 219, 0.7)',
                    'rgba(243, 156, 18, 0.7)',
                    'rgba(231, 76, 60, 0.7)'
                ],
                borderColor: [
                    'rgba(46, 204, 113, 1)',
                    'rgba(52, 152, 219, 1)',
                    'rgba(243, 156, 18, 1)',
                    'rgba(231, 76, 60, 1)'
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
    
    // Gráfico de média de notas por curso
    const ctxNotas = document.getElementById('graficoNotasCursos').getContext('2d');
    new Chart(ctxNotas, {
        type: 'bar',
        data: {
            labels: [
                <?php 
                $cursos_unicos = [];
                foreach ($dados_desempenho as $dado) {
                    if (!in_array($dado['curso_nome'], $cursos_unicos)) {
                        $cursos_unicos[] = $dado['curso_nome'];
                        echo "'" . addslashes($dado['curso_nome']) . "', ";
                    }
                }
                ?>
            ],
            datasets: [{
                label: 'Média de Notas',
                data: [
                    <?php 
                    $notas_por_curso = [];
                    $count_por_curso = [];
                    
                    foreach ($dados_desempenho as $dado) {
                        if (!isset($notas_por_curso[$dado['curso_nome']])) {
                            $notas_por_curso[$dado['curso_nome']] = 0;
                            $count_por_curso[$dado['curso_nome']] = 0;
                        }
                        
                        if ($dado['media_notas'] !== null) {
                            $notas_por_curso[$dado['curso_nome']] += $dado['media_notas'] * $dado['total_matriculas'];
                            $count_por_curso[$dado['curso_nome']] += $dado['total_matriculas'];
                        }
                    }
                    
                    foreach ($cursos_unicos as $curso) {
                        $media = $count_por_curso[$curso] > 0 ? $notas_por_curso[$curso] / $count_por_curso[$curso] : 0;
                        echo number_format($media, 1) . ", ";
                    }
                    ?>
                ],
                backgroundColor: 'rgba(155, 89, 182, 0.7)',
                borderColor: 'rgba(155, 89, 182, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 10
                }
            }
        }
    });
</script>
