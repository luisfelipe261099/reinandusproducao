<?php
// Obtém os filtros
$filtro_periodo = isset($_GET['periodo']) ? $_GET['periodo'] : 'ultimo_ano';
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

// Estatísticas gerais
$sql_estatisticas_gerais = "SELECT 
                            (SELECT COUNT(*) FROM alunos WHERE status = 'ativo') as total_alunos_ativos,
                            (SELECT COUNT(*) FROM alunos) as total_alunos,
                            (SELECT COUNT(*) FROM cursos WHERE status = 'ativo') as total_cursos_ativos,
                            (SELECT COUNT(*) FROM cursos) as total_cursos,
                            (SELECT COUNT(*) FROM polos WHERE status = 'ativo') as total_polos_ativos,
                            (SELECT COUNT(*) FROM polos) as total_polos,
                            (SELECT COUNT(*) FROM turmas WHERE status IN ('planejada', 'em_andamento')) as total_turmas_ativas,
                            (SELECT COUNT(*) FROM turmas) as total_turmas,
                            (SELECT COUNT(*) FROM matriculas WHERE status = 'ativo') as total_matriculas_ativas,
                            (SELECT COUNT(*) FROM matriculas) as total_matriculas";
$estatisticas_gerais = $db->fetchOne($sql_estatisticas_gerais);

// Estatísticas de matrículas por período
$sql_matriculas_periodo = "SELECT 
                           DATE_FORMAT(data_matricula, '%Y-%m') as mes,
                           COUNT(*) as total_matriculas
                           FROM matriculas
                           WHERE data_matricula BETWEEN ? AND ?
                           GROUP BY DATE_FORMAT(data_matricula, '%Y-%m')
                           ORDER BY mes ASC";
$matriculas_periodo = $db->fetchAll($sql_matriculas_periodo, [$filtro_data_inicio, $filtro_data_fim]);

// Estatísticas de alunos por curso
$sql_alunos_por_curso = "SELECT 
                         c.nome as curso_nome,
                         COUNT(m.id) as total_alunos
                         FROM cursos c
                         LEFT JOIN matriculas m ON c.id = m.curso_id AND m.status = 'ativo'
                         WHERE c.status = 'ativo'
                         GROUP BY c.id
                         ORDER BY total_alunos DESC
                         LIMIT 10";
$alunos_por_curso = $db->fetchAll($sql_alunos_por_curso);

// Estatísticas de alunos por polo
$sql_alunos_por_polo = "SELECT 
                        p.nome as polo_nome,
                        COUNT(m.id) as total_alunos
                        FROM polos p
                        LEFT JOIN matriculas m ON p.id = m.polo_id AND m.status = 'ativo'
                        WHERE p.status = 'ativo'
                        GROUP BY p.id
                        ORDER BY total_alunos DESC
                        LIMIT 10";
$alunos_por_polo = $db->fetchAll($sql_alunos_por_polo);

// Estatísticas de documentos emitidos
$sql_documentos_emitidos = "SELECT 
                           td.nome as tipo_documento,
                           COUNT(d.id) as total_documentos
                           FROM tipos_documentos td
                           LEFT JOIN documentos d ON td.id = d.tipo_documento_id
                           WHERE td.status = 'ativo'
                           GROUP BY td.id
                           ORDER BY total_documentos DESC";
$documentos_emitidos = $db->fetchAll($sql_documentos_emitidos);

// Estatísticas de chamados por categoria
$sql_chamados_categoria = "SELECT 
                          cc.nome as categoria,
                          COUNT(c.id) as total_chamados
                          FROM categorias_chamados cc
                          LEFT JOIN chamados c ON cc.id = c.categoria_id
                          WHERE cc.status = 'ativo'
                          GROUP BY cc.id
                          ORDER BY total_chamados DESC";
$chamados_categoria = $db->fetchAll($sql_chamados_categoria);

// Estatísticas de chamados por status
$sql_chamados_status = "SELECT 
                       status,
                       COUNT(*) as total_chamados
                       FROM chamados
                       GROUP BY status
                       ORDER BY total_chamados DESC";
$chamados_status = $db->fetchAll($sql_chamados_status);
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Relatório de Estatísticas</h1>
        
        <div class="flex space-x-2">
            <a href="relatorios.php?tipo=desempenho" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                <i class="fas fa-chart-line mr-2"></i> Ver Desempenho
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
            <input type="hidden" name="tipo" value="estatisticas">
            
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
                
                <a href="relatorios.php?tipo=estatisticas" class="ml-2 bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
                    <i class="fas fa-times mr-2"></i> Limpar
                </a>
            </div>
        </form>
    </div>
    
    <!-- Estatísticas Gerais -->
    <div class="bg-white shadow-md rounded-lg p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4">Estatísticas Gerais</h2>
        
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                <h3 class="text-sm font-medium text-blue-800 mb-2">Alunos Ativos</h3>
                <p class="text-2xl font-bold text-blue-600"><?php echo $estatisticas_gerais['total_alunos_ativos']; ?> <span class="text-sm font-normal">/ <?php echo $estatisticas_gerais['total_alunos']; ?></span></p>
            </div>
            
            <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                <h3 class="text-sm font-medium text-green-800 mb-2">Cursos Ativos</h3>
                <p class="text-2xl font-bold text-green-600"><?php echo $estatisticas_gerais['total_cursos_ativos']; ?> <span class="text-sm font-normal">/ <?php echo $estatisticas_gerais['total_cursos']; ?></span></p>
            </div>
            
            <div class="bg-purple-50 p-4 rounded-lg border border-purple-200">
                <h3 class="text-sm font-medium text-purple-800 mb-2">Polos Ativos</h3>
                <p class="text-2xl font-bold text-purple-600"><?php echo $estatisticas_gerais['total_polos_ativos']; ?> <span class="text-sm font-normal">/ <?php echo $estatisticas_gerais['total_polos']; ?></span></p>
            </div>
            
            <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                <h3 class="text-sm font-medium text-yellow-800 mb-2">Turmas Ativas</h3>
                <p class="text-2xl font-bold text-yellow-600"><?php echo $estatisticas_gerais['total_turmas_ativas']; ?> <span class="text-sm font-normal">/ <?php echo $estatisticas_gerais['total_turmas']; ?></span></p>
            </div>
        </div>
    </div>
    
    <!-- Gráficos -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <!-- Matrículas por Período -->
        <div class="bg-white shadow-md rounded-lg p-6">
            <h2 class="text-lg font-semibold mb-4">Matrículas por Período</h2>
            <div class="h-64">
                <canvas id="graficoMatriculasPeriodo"></canvas>
            </div>
        </div>
        
        <!-- Alunos por Curso -->
        <div class="bg-white shadow-md rounded-lg p-6">
            <h2 class="text-lg font-semibold mb-4">Top 10 Cursos por Número de Alunos</h2>
            <div class="h-64">
                <canvas id="graficoAlunosPorCurso"></canvas>
            </div>
        </div>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <!-- Alunos por Polo -->
        <div class="bg-white shadow-md rounded-lg p-6">
            <h2 class="text-lg font-semibold mb-4">Top 10 Polos por Número de Alunos</h2>
            <div class="h-64">
                <canvas id="graficoAlunosPorPolo"></canvas>
            </div>
        </div>
        
        <!-- Documentos Emitidos -->
        <div class="bg-white shadow-md rounded-lg p-6">
            <h2 class="text-lg font-semibold mb-4">Documentos Emitidos por Tipo</h2>
            <div class="h-64">
                <canvas id="graficoDocumentosEmitidos"></canvas>
            </div>
        </div>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <!-- Chamados por Categoria -->
        <div class="bg-white shadow-md rounded-lg p-6">
            <h2 class="text-lg font-semibold mb-4">Chamados por Categoria</h2>
            <div class="h-64">
                <canvas id="graficoChamadosCategoria"></canvas>
            </div>
        </div>
        
        <!-- Chamados por Status -->
        <div class="bg-white shadow-md rounded-lg p-6">
            <h2 class="text-lg font-semibold mb-4">Chamados por Status</h2>
            <div class="h-64">
                <canvas id="graficoChamadosStatus"></canvas>
            </div>
        </div>
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
    
    // Gráfico de matrículas por período
    const ctxMatriculas = document.getElementById('graficoMatriculasPeriodo').getContext('2d');
    new Chart(ctxMatriculas, {
        type: 'line',
        data: {
            labels: [
                <?php 
                foreach ($matriculas_periodo as $matricula) {
                    $mes_ano = explode('-', $matricula['mes']);
                    $mes = $mes_ano[1];
                    $ano = $mes_ano[0];
                    $nome_mes = '';
                    
                    switch ($mes) {
                        case '01': $nome_mes = 'Jan'; break;
                        case '02': $nome_mes = 'Fev'; break;
                        case '03': $nome_mes = 'Mar'; break;
                        case '04': $nome_mes = 'Abr'; break;
                        case '05': $nome_mes = 'Mai'; break;
                        case '06': $nome_mes = 'Jun'; break;
                        case '07': $nome_mes = 'Jul'; break;
                        case '08': $nome_mes = 'Ago'; break;
                        case '09': $nome_mes = 'Set'; break;
                        case '10': $nome_mes = 'Out'; break;
                        case '11': $nome_mes = 'Nov'; break;
                        case '12': $nome_mes = 'Dez'; break;
                    }
                    
                    echo "'" . $nome_mes . "/" . $ano . "', ";
                }
                ?>
            ],
            datasets: [{
                label: 'Matrículas',
                data: [
                    <?php 
                    foreach ($matriculas_periodo as $matricula) {
                        echo $matricula['total_matriculas'] . ", ";
                    }
                    ?>
                ],
                backgroundColor: 'rgba(52, 152, 219, 0.2)',
                borderColor: 'rgba(52, 152, 219, 1)',
                borderWidth: 2,
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    
    // Gráfico de alunos por curso
    const ctxCursos = document.getElementById('graficoAlunosPorCurso').getContext('2d');
    new Chart(ctxCursos, {
        type: 'bar',
        data: {
            labels: [
                <?php 
                foreach ($alunos_por_curso as $curso) {
                    echo "'" . addslashes($curso['curso_nome']) . "', ";
                }
                ?>
            ],
            datasets: [{
                label: 'Alunos',
                data: [
                    <?php 
                    foreach ($alunos_por_curso as $curso) {
                        echo $curso['total_alunos'] . ", ";
                    }
                    ?>
                ],
                backgroundColor: 'rgba(46, 204, 113, 0.7)',
                borderColor: 'rgba(46, 204, 113, 1)',
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
    
    // Gráfico de alunos por polo
    const ctxPolos = document.getElementById('graficoAlunosPorPolo').getContext('2d');
    new Chart(ctxPolos, {
        type: 'bar',
        data: {
            labels: [
                <?php 
                foreach ($alunos_por_polo as $polo) {
                    echo "'" . addslashes($polo['polo_nome']) . "', ";
                }
                ?>
            ],
            datasets: [{
                label: 'Alunos',
                data: [
                    <?php 
                    foreach ($alunos_por_polo as $polo) {
                        echo $polo['total_alunos'] . ", ";
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
                    beginAtZero: true
                }
            },
            indexAxis: 'y'
        }
    });
    
    // Gráfico de documentos emitidos
    const ctxDocumentos = document.getElementById('graficoDocumentosEmitidos').getContext('2d');
    new Chart(ctxDocumentos, {
        type: 'pie',
        data: {
            labels: [
                <?php 
                foreach ($documentos_emitidos as $documento) {
                    echo "'" . addslashes($documento['tipo_documento']) . "', ";
                }
                ?>
            ],
            datasets: [{
                data: [
                    <?php 
                    foreach ($documentos_emitidos as $documento) {
                        echo $documento['total_documentos'] . ", ";
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
    
    // Gráfico de chamados por categoria
    const ctxChamadosCategoria = document.getElementById('graficoChamadosCategoria').getContext('2d');
    new Chart(ctxChamadosCategoria, {
        type: 'doughnut',
        data: {
            labels: [
                <?php 
                foreach ($chamados_categoria as $chamado) {
                    echo "'" . addslashes($chamado['categoria']) . "', ";
                }
                ?>
            ],
            datasets: [{
                data: [
                    <?php 
                    foreach ($chamados_categoria as $chamado) {
                        echo $chamado['total_chamados'] . ", ";
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
    
    // Gráfico de chamados por status
    const ctxChamadosStatus = document.getElementById('graficoChamadosStatus').getContext('2d');
    new Chart(ctxChamadosStatus, {
        type: 'bar',
        data: {
            labels: [
                <?php 
                foreach ($chamados_status as $chamado) {
                    $status_text = '';
                    switch ($chamado['status']) {
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
                label: 'Chamados',
                data: [
                    <?php 
                    foreach ($chamados_status as $chamado) {
                        echo $chamado['total_chamados'] . ", ";
                    }
                    ?>
                ],
                backgroundColor: [
                    'rgba(52, 152, 219, 0.7)',
                    'rgba(241, 196, 15, 0.7)',
                    'rgba(155, 89, 182, 0.7)',
                    'rgba(52, 73, 94, 0.7)',
                    'rgba(46, 204, 113, 0.7)',
                    'rgba(231, 76, 60, 0.7)',
                    'rgba(127, 140, 141, 0.7)'
                ],
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
            }
        }
    });
</script>
