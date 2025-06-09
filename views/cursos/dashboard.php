<!-- Dashboard de Cursos -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <!-- Estatísticas Gerais -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Estatísticas Gerais</h3>
        <div class="grid grid-cols-2 gap-4">
            <div class="bg-blue-50 rounded-lg p-4">
                <p class="text-sm text-gray-500">Total de Cursos</p>
                <p class="text-2xl font-bold text-blue-600"><?php echo $stats['total_cursos']; ?></p>
            </div>
            <div class="bg-green-50 rounded-lg p-4">
                <p class="text-sm text-gray-500">Cursos Ativos</p>
                <p class="text-2xl font-bold text-green-600"><?php echo $stats['cursos_ativos']; ?></p>
            </div>
            <div class="bg-purple-50 rounded-lg p-4">
                <p class="text-sm text-gray-500">Total de Alunos</p>
                <p class="text-2xl font-bold text-purple-600"><?php echo $stats['total_alunos']; ?></p>
            </div>
            <div class="bg-yellow-50 rounded-lg p-4">
                <p class="text-sm text-gray-500">Turmas Ativas</p>
                <p class="text-2xl font-bold text-yellow-600"><?php echo $stats['turmas_ativas']; ?></p>
            </div>
        </div>
    </div>

    <!-- Distribuição por Modalidade -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Distribuição por Modalidade</h3>
        <div class="h-64">
            <canvas id="modalidadeChart"></canvas>
        </div>
    </div>

    <!-- Distribuição por Nível -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Distribuição por Nível</h3>
        <div class="h-64">
            <canvas id="nivelChart"></canvas>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Cursos Mais Populares -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Cursos Mais Populares</h3>
        <div class="h-80">
            <canvas id="cursosPopularesChart"></canvas>
        </div>
    </div>

    <!-- Tendência de Matrículas -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Tendência de Matrículas</h3>
        <div class="h-80">
            <canvas id="matriculasTrendChart"></canvas>
        </div>
    </div>
</div>

<!-- Cursos Recentes e Ações Rápidas -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Cursos Recentes -->
    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-800">Cursos Recentes</h3>
            <a href="cursos.php?action=listar" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                Ver Todos <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>

        <?php if (empty($cursos_recentes)): ?>
        <p class="text-gray-500 text-sm">Nenhum curso recente encontrado.</p>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nível</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Modalidade</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($cursos_recentes as $curso): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-8 w-8">
                                    <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center">
                                        <span class="text-blue-600 font-bold text-xs"><?php echo strtoupper(substr($curso['nome'], 0, 1)); ?></span>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <a href="cursos.php?action=visualizar&id=<?php echo $curso['id']; ?>" class="text-sm font-medium text-gray-900 hover:text-blue-600"><?php echo htmlspecialchars($curso['nome']); ?></a>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                <?php
                                $niveis = [
                                    'graduacao' => 'Graduação',
                                    'pos_graduacao' => 'Pós-Graduação',
                                    'mestrado' => 'Mestrado',
                                    'doutorado' => 'Doutorado',
                                    'tecnico' => 'Técnico',
                                    'extensao' => 'Extensão'
                                ];
                                echo isset($curso['nivel']) ? ($niveis[$curso['nivel']] ?? $curso['nivel']) : 'N/A';
                                ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                <?php
                                $modalidades = [
                                    'presencial' => 'Presencial',
                                    'ead' => 'EAD',
                                    'hibrido' => 'Híbrido'
                                ];
                                echo isset($curso['modalidade']) ? ($modalidades[$curso['modalidade']] ?? $curso['modalidade']) : 'N/A';
                                ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo isset($curso['created_at']) ? date('d/m/Y', strtotime($curso['created_at'])) : 'N/A'; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Ações Rápidas -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Ações Rápidas</h3>

        <div class="grid grid-cols-1 gap-3">
            <a href="cursos.php?action=novo" class="flex items-center p-4 bg-blue-50 hover:bg-blue-100 rounded-lg transition-all">
                <div class="bg-blue-100 p-3 rounded-full mr-4">
                    <i class="fas fa-plus text-blue-500"></i>
                </div>
                <div>
                    <h4 class="font-medium text-gray-800">Novo Curso</h4>
                    <p class="text-sm text-gray-500">Adicionar um novo curso ao sistema</p>
                </div>
            </a>

            <a href="turmas.php?action=nova" class="flex items-center p-4 bg-green-50 hover:bg-green-100 rounded-lg transition-all">
                <div class="bg-green-100 p-3 rounded-full mr-4">
                    <i class="fas fa-users text-green-500"></i>
                </div>
                <div>
                    <h4 class="font-medium text-gray-800">Nova Turma</h4>
                    <p class="text-sm text-gray-500">Criar uma nova turma para um curso</p>
                </div>
            </a>

            <a href="disciplinas.php?action=nova" class="flex items-center p-4 bg-purple-50 hover:bg-purple-100 rounded-lg transition-all">
                <div class="bg-purple-100 p-3 rounded-full mr-4">
                    <i class="fas fa-book text-purple-500"></i>
                </div>
                <div>
                    <h4 class="font-medium text-gray-800">Nova Disciplina</h4>
                    <p class="text-sm text-gray-500">Adicionar uma nova disciplina</p>
                </div>
            </a>

            <a href="relatorios.php?tipo=cursos" class="flex items-center p-4 bg-yellow-50 hover:bg-yellow-100 rounded-lg transition-all">
                <div class="bg-yellow-100 p-3 rounded-full mr-4">
                    <i class="fas fa-chart-bar text-yellow-500"></i>
                </div>
                <div>
                    <h4 class="font-medium text-gray-800">Relatórios</h4>
                    <p class="text-sm text-gray-500">Gerar relatórios detalhados</p>
                </div>
            </a>
        </div>
    </div>
</div>

<!-- Scripts para os gráficos -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gráfico de Distribuição por Modalidade
    const modalidadeCtx = document.getElementById('modalidadeChart').getContext('2d');
    const modalidadeChart = new Chart(modalidadeCtx, {
        type: 'doughnut',
        data: {
            labels: ['Presencial', 'EAD', 'Híbrido'],
            datasets: [{
                data: [
                    <?php echo $stats['modalidade_presencial']; ?>,
                    <?php echo $stats['modalidade_ead']; ?>,
                    <?php echo $stats['modalidade_hibrido']; ?>
                ],
                backgroundColor: [
                    'rgba(54, 162, 235, 0.7)',
                    'rgba(75, 192, 192, 0.7)',
                    'rgba(153, 102, 255, 0.7)'
                ],
                borderColor: [
                    'rgba(54, 162, 235, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)'
                ],
                borderWidth: 1
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

    // Gráfico de Distribuição por Nível
    const nivelCtx = document.getElementById('nivelChart').getContext('2d');
    const nivelChart = new Chart(nivelCtx, {
        type: 'doughnut',
        data: {
            labels: ['Graduação', 'Pós-Graduação', 'Mestrado', 'Doutorado', 'Técnico', 'Extensão'],
            datasets: [{
                data: [
                    <?php echo $stats['nivel_graduacao']; ?>,
                    <?php echo $stats['nivel_pos_graduacao']; ?>,
                    <?php echo $stats['nivel_mestrado']; ?>,
                    <?php echo $stats['nivel_doutorado']; ?>,
                    <?php echo $stats['nivel_tecnico']; ?>,
                    <?php echo $stats['nivel_extensao']; ?>
                ],
                backgroundColor: [
                    'rgba(255, 99, 132, 0.7)',
                    'rgba(54, 162, 235, 0.7)',
                    'rgba(255, 206, 86, 0.7)',
                    'rgba(75, 192, 192, 0.7)',
                    'rgba(153, 102, 255, 0.7)',
                    'rgba(255, 159, 64, 0.7)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)',
                    'rgba(255, 159, 64, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 12
                    }
                }
            }
        }
    });

    // Gráfico de Cursos Mais Populares
    const cursosPopularesCtx = document.getElementById('cursosPopularesChart').getContext('2d');
    const cursosPopularesChart = new Chart(cursosPopularesCtx, {
        type: 'bar',
        data: {
            labels: [
                <?php foreach ($cursos_populares as $curso): ?>
                '<?php echo addslashes($curso['nome']); ?>',
                <?php endforeach; ?>
            ],
            datasets: [{
                label: 'Número de Alunos',
                data: [
                    <?php foreach ($cursos_populares as $curso): ?>
                    <?php echo $curso['total_alunos']; ?>,
                    <?php endforeach; ?>
                ],
                backgroundColor: 'rgba(54, 162, 235, 0.7)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });

    // Gráfico de Tendência de Matrículas
    const matriculasTrendCtx = document.getElementById('matriculasTrendChart').getContext('2d');
    const matriculasTrendChart = new Chart(matriculasTrendCtx, {
        type: 'line',
        data: {
            labels: [
                <?php foreach ($matriculas_por_mes as $mes): ?>
                '<?php echo $mes['mes_nome']; ?>',
                <?php endforeach; ?>
            ],
            datasets: [{
                label: 'Matrículas',
                data: [
                    <?php foreach ($matriculas_por_mes as $mes): ?>
                    <?php echo $mes['total']; ?>,
                    <?php endforeach; ?>
                ],
                fill: false,
                backgroundColor: 'rgba(75, 192, 192, 0.7)',
                borderColor: 'rgba(75, 192, 192, 1)',
                tension: 0.4,
                pointBackgroundColor: 'rgba(75, 192, 192, 1)',
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: 'rgba(75, 192, 192, 1)'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
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
});
</script>
