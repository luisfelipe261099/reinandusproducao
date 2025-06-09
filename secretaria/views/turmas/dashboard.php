<!-- Incluir Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>

<!-- Cards de Estatísticas -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <!-- Total de Turmas -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100 mr-4">
                <i class="fas fa-users text-blue-500 text-xl"></i>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">Total de Turmas</p>
                <p class="text-2xl font-bold text-gray-800"><?php echo isset($stats['total_turmas']) ? number_format($stats['total_turmas']) : '0'; ?></p>
            </div>
        </div>
    </div>

    <!-- Turmas em Andamento -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100 mr-4">
                <i class="fas fa-chalkboard-teacher text-green-500 text-xl"></i>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">Em Andamento</p>
                <p class="text-2xl font-bold text-gray-800"><?php echo isset($stats['turmas_em_andamento']) ? number_format($stats['turmas_em_andamento']) : '0'; ?></p>
            </div>
        </div>
    </div>

    <!-- Turmas Planejadas -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-yellow-100 mr-4">
                <i class="fas fa-calendar-alt text-yellow-500 text-xl"></i>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">Planejadas</p>
                <p class="text-2xl font-bold text-gray-800"><?php echo isset($stats['turmas_planejadas']) ? number_format($stats['turmas_planejadas']) : '0'; ?></p>
            </div>
        </div>
    </div>

    <!-- Total de Alunos Matriculados -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-purple-100 mr-4">
                <i class="fas fa-user-graduate text-purple-500 text-xl"></i>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">Alunos Matriculados</p>
                <p class="text-2xl font-bold text-gray-800"><?php echo isset($stats['total_alunos_matriculados']) ? number_format($stats['total_alunos_matriculados']) : '0'; ?></p>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <!-- Distribuição de Turmas por Status -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Distribuição por Status</h3>
        <div class="h-64">
            <?php if ($stats['total_turmas'] > 0): ?>
            <canvas id="chartTurmasPorStatus"></canvas>
            <?php else: ?>
            <div class="flex items-center justify-center h-full">
                <p class="text-gray-500 text-sm">Nenhuma turma encontrada no sistema.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Turmas por Polo -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Turmas por Polo</h3>
        <div class="h-64">
            <?php if (!empty($turmas_por_polo)): ?>
            <canvas id="chartTurmasPorPolo"></canvas>
            <?php else: ?>
            <div class="flex items-center justify-center h-full">
                <p class="text-gray-500 text-sm">Nenhuma turma por polo encontrada.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Turmas por Curso -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Turmas por Curso</h3>
        <div class="h-64">
            <?php if (!empty($turmas_por_curso)): ?>
            <canvas id="chartTurmasPorCurso"></canvas>
            <?php else: ?>
            <div class="flex items-center justify-center h-full">
                <p class="text-gray-500 text-sm">Nenhuma turma por curso encontrada.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Matrículas por Mês -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Matrículas por Mês</h3>
        <div class="h-64">
            <?php if (!empty($matriculas_por_mes)): ?>
            <canvas id="chartMatriculasPorMes"></canvas>
            <?php else: ?>
            <div class="flex items-center justify-center h-full">
                <p class="text-gray-500 text-sm">Nenhuma matrícula encontrada.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Turmas Recentes -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-800">Turmas Recentes</h3>
            <a href="turmas.php?action=listar" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                Ver Todas <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>

        <?php if (empty($turmas_recentes)): ?>
        <p class="text-gray-500 text-sm">Nenhuma turma recente encontrada.</p>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Turma</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Curso</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Polo</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($turmas_recentes as $turma): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-8 w-8">
                                    <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center">
                                        <span class="text-blue-600 font-bold text-xs"><?php echo strtoupper(substr($turma['nome'], 0, 1)); ?></span>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <a href="turmas.php?action=visualizar&id=<?php echo $turma['id']; ?>" class="text-sm font-medium text-gray-900 hover:text-blue-600"><?php echo htmlspecialchars($turma['nome']); ?></a>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900"><?php echo isset($turma['curso_nome']) ? htmlspecialchars($turma['curso_nome']) : 'N/A'; ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900"><?php echo isset($turma['polo_nome']) ? htmlspecialchars($turma['polo_nome']) : 'N/A'; ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                <?php
                                if (isset($turma['status'])) {
                                    switch ($turma['status']) {
                                        case 'planejada':
                                            echo 'bg-yellow-100 text-yellow-800';
                                            break;
                                        case 'em_andamento':
                                            echo 'bg-green-100 text-green-800';
                                            break;
                                        case 'concluida':
                                            echo 'bg-blue-100 text-blue-800';
                                            break;
                                        case 'cancelada':
                                            echo 'bg-red-100 text-red-800';
                                            break;
                                        default:
                                            echo 'bg-gray-100 text-gray-800';
                                    }
                                } else {
                                    echo 'bg-gray-100 text-gray-800';
                                }
                                ?>">
                                <?php
                                if (isset($turma['status'])) {
                                    switch ($turma['status']) {
                                        case 'planejada':
                                            echo 'Planejada';
                                            break;
                                        case 'em_andamento':
                                            echo 'Em Andamento';
                                            break;
                                        case 'concluida':
                                            echo 'Concluída';
                                            break;
                                        case 'cancelada':
                                            echo 'Cancelada';
                                            break;
                                        default:
                                            echo ucfirst($turma['status']);
                                    }
                                } else {
                                    echo 'N/A';
                                }
                                ?>
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

<!-- Ações Rápidas -->
<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Ações Rápidas</h3>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <a href="turmas.php?action=nova" class="flex flex-col items-center justify-center bg-blue-50 hover:bg-blue-100 transition-all p-4 rounded-lg">
            <div class="bg-blue-100 p-3 rounded-full mb-2">
                <i class="fas fa-plus text-blue-500"></i>
            </div>
            <span class="text-sm font-medium">Nova Turma</span>
        </a>

        <a href="turmas.php?action=listar" class="flex flex-col items-center justify-center bg-green-50 hover:bg-green-100 transition-all p-4 rounded-lg">
            <div class="bg-green-100 p-3 rounded-full mb-2">
                <i class="fas fa-list text-green-500"></i>
            </div>
            <span class="text-sm font-medium">Listar Turmas</span>
        </a>

        <a href="matriculas.php?action=nova" class="flex flex-col items-center justify-center bg-purple-50 hover:bg-purple-100 transition-all p-4 rounded-lg">
            <div class="bg-purple-100 p-3 rounded-full mb-2">
                <i class="fas fa-user-plus text-purple-500"></i>
            </div>
            <span class="text-sm font-medium">Nova Matrícula</span>
        </a>

        <a href="relatorios.php?tipo=turmas" class="flex flex-col items-center justify-center bg-yellow-50 hover:bg-yellow-100 transition-all p-4 rounded-lg">
            <div class="bg-yellow-100 p-3 rounded-full mb-2">
                <i class="fas fa-chart-bar text-yellow-500"></i>
            </div>
            <span class="text-sm font-medium">Relatórios</span>
        </a>
    </div>
</div>

<!-- Scripts para os gráficos -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Configurações comuns
    Chart.defaults.font.family = 'system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif';
    Chart.defaults.font.size = 12;
    Chart.defaults.color = '#6B7280';

    // Gráfico de Turmas por Status
    const chartTurmasPorStatus = document.getElementById('chartTurmasPorStatus');
    if (chartTurmasPorStatus) {
        const ctxStatus = chartTurmasPorStatus.getContext('2d');
        new Chart(ctxStatus, {
        type: 'doughnut',
        data: {
            labels: ['Em Andamento', 'Planejadas', 'Concluídas', 'Canceladas'],
            datasets: [{
                data: [
                    <?php echo isset($stats['turmas_em_andamento']) ? $stats['turmas_em_andamento'] : 0; ?>,
                    <?php echo isset($stats['turmas_planejadas']) ? $stats['turmas_planejadas'] : 0; ?>,
                    <?php echo isset($stats['turmas_concluidas']) ? $stats['turmas_concluidas'] : 0; ?>,
                    <?php echo isset($stats['turmas_canceladas']) ? $stats['turmas_canceladas'] : 0; ?>
                ],
                backgroundColor: [
                    '#10B981', // verde
                    '#F59E0B', // amarelo
                    '#3B82F6', // azul
                    '#EF4444'  // vermelho
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    }

    // Gráfico de Turmas por Polo
    const chartTurmasPorPolo = document.getElementById('chartTurmasPorPolo');
    if (chartTurmasPorPolo) {
        const ctxPolo = chartTurmasPorPolo.getContext('2d');
        new Chart(ctxPolo, {
        type: 'bar',
        data: {
            labels: [
                <?php foreach ($turmas_por_polo as $item): ?>
                '<?php echo addslashes($item['polo_nome']); ?>',
                <?php endforeach; ?>
            ],
            datasets: [{
                label: 'Turmas',
                data: [
                    <?php foreach ($turmas_por_polo as $item): ?>
                    <?php echo $item['total_turmas']; ?>,
                    <?php endforeach; ?>
                ],
                backgroundColor: '#8B5CF6',
                borderWidth: 0,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });

    }

    // Gráfico de Turmas por Curso
    const chartTurmasPorCurso = document.getElementById('chartTurmasPorCurso');
    if (chartTurmasPorCurso) {
        const ctxCurso = chartTurmasPorCurso.getContext('2d');
        new Chart(ctxCurso, {
        type: 'bar',
        data: {
            labels: [
                <?php foreach ($turmas_por_curso as $item): ?>
                '<?php echo addslashes($item['curso_nome']); ?>',
                <?php endforeach; ?>
            ],
            datasets: [{
                label: 'Turmas',
                data: [
                    <?php foreach ($turmas_por_curso as $item): ?>
                    <?php echo $item['total_turmas']; ?>,
                    <?php endforeach; ?>
                ],
                backgroundColor: '#EC4899',
                borderWidth: 0,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });

    }

    // Gráfico de Matrículas por Mês
    const chartMatriculasPorMes = document.getElementById('chartMatriculasPorMes');
    if (chartMatriculasPorMes) {
        const ctxMatriculas = chartMatriculasPorMes.getContext('2d');
        new Chart(ctxMatriculas, {
        type: 'line',
        data: {
            labels: [
                <?php foreach ($matriculas_por_mes as $item): ?>
                '<?php echo addslashes($item['mes_nome']); ?>',
                <?php endforeach; ?>
            ],
            datasets: [{
                label: 'Matrículas',
                data: [
                    <?php foreach ($matriculas_por_mes as $item): ?>
                    <?php echo $item['total']; ?>,
                    <?php endforeach; ?>
                ],
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                borderColor: '#3B82F6',
                borderWidth: 2,
                tension: 0.3,
                fill: true,
                pointBackgroundColor: '#3B82F6',
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
    }
});
</script>
