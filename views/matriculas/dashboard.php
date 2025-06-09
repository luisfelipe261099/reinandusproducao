<!-- Dashboard de Matrículas -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <!-- Total de Matrículas -->
    <div class="bg-white rounded-xl shadow-sm p-4">
        <div class="flex items-center">
            <div class="flex-shrink-0 bg-blue-100 rounded-full p-3">
                <i class="fas fa-user-graduate text-blue-600 text-xl"></i>
            </div>
            <div class="ml-4">
                <h2 class="text-sm font-medium text-gray-500">Total de Matrículas</h2>
                <p class="text-2xl font-bold text-gray-800"><?php echo $total_matriculas ?? 0; ?></p>
            </div>
        </div>
        <div class="mt-3">
            <a href="matriculas.php" class="text-sm text-blue-600 hover:text-blue-800">Ver todas</a>
        </div>
    </div>

    <!-- Matrículas Ativas -->
    <div class="bg-white rounded-xl shadow-sm p-4">
        <div class="flex items-center">
            <div class="flex-shrink-0 bg-green-100 rounded-full p-3">
                <i class="fas fa-check-circle text-green-600 text-xl"></i>
            </div>
            <div class="ml-4">
                <h2 class="text-sm font-medium text-gray-500">Matrículas Ativas</h2>
                <p class="text-2xl font-bold text-gray-800"><?php echo $total_ativas ?? 0; ?></p>
            </div>
        </div>
        <div class="mt-3">
            <a href="matriculas.php?status=ativo" class="text-sm text-green-600 hover:text-green-800">Ver ativas</a>
        </div>
    </div>

    <!-- Matrículas Pendentes -->
    <div class="bg-white rounded-xl shadow-sm p-4">
        <div class="flex items-center">
            <div class="flex-shrink-0 bg-yellow-100 rounded-full p-3">
                <i class="fas fa-clock text-yellow-600 text-xl"></i>
            </div>
            <div class="ml-4">
                <h2 class="text-sm font-medium text-gray-500">Matrículas Pendentes</h2>
                <p class="text-2xl font-bold text-gray-800"><?php echo $total_pendentes ?? 0; ?></p>
            </div>
        </div>
        <div class="mt-3">
            <a href="matriculas.php?status=pendente" class="text-sm text-yellow-600 hover:text-yellow-800">Ver pendentes</a>
        </div>
    </div>

    <!-- Matrículas Recentes -->
    <div class="bg-white rounded-xl shadow-sm p-4">
        <div class="flex items-center">
            <div class="flex-shrink-0 bg-purple-100 rounded-full p-3">
                <i class="fas fa-calendar-alt text-purple-600 text-xl"></i>
            </div>
            <div class="ml-4">
                <h2 class="text-sm font-medium text-gray-500">Matrículas Recentes</h2>
                <p class="text-2xl font-bold text-gray-800"><?php echo $total_recentes ?? 0; ?></p>
            </div>
        </div>
        <div class="mt-3">
            <a href="matriculas.php?ordenar=recentes" class="text-sm text-purple-600 hover:text-purple-800">Ver recentes</a>
        </div>
    </div>
</div>

<!-- Gráficos e Estatísticas -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
    <!-- Distribuição por Status -->
    <div class="bg-white rounded-xl shadow-sm p-4">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Distribuição por Status</h3>
        <div class="flex items-center justify-center h-64">
            <div class="w-full max-w-md">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm font-medium text-gray-600">Ativas</span>
                    <span class="text-sm font-medium text-gray-900"><?php echo $total_ativas ?? 0; ?></span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2.5">
                    <div class="bg-green-600 h-2.5 rounded-full" style="width: <?php echo $total_matriculas > 0 ? ($total_ativas / $total_matriculas * 100) : 0; ?>%"></div>
                </div>
                
                <div class="flex justify-between items-center mb-2 mt-4">
                    <span class="text-sm font-medium text-gray-600">Pendentes</span>
                    <span class="text-sm font-medium text-gray-900"><?php echo $total_pendentes ?? 0; ?></span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2.5">
                    <div class="bg-yellow-500 h-2.5 rounded-full" style="width: <?php echo $total_matriculas > 0 ? ($total_pendentes / $total_matriculas * 100) : 0; ?>%"></div>
                </div>
                
                <div class="flex justify-between items-center mb-2 mt-4">
                    <span class="text-sm font-medium text-gray-600">Concluídas</span>
                    <span class="text-sm font-medium text-gray-900"><?php echo $total_concluidas ?? 0; ?></span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2.5">
                    <div class="bg-blue-600 h-2.5 rounded-full" style="width: <?php echo $total_matriculas > 0 ? ($total_concluidas / $total_matriculas * 100) : 0; ?>%"></div>
                </div>
                
                <div class="flex justify-between items-center mb-2 mt-4">
                    <span class="text-sm font-medium text-gray-600">Canceladas/Trancadas</span>
                    <span class="text-sm font-medium text-gray-900"><?php echo ($total_canceladas + $total_trancadas) ?? 0; ?></span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2.5">
                    <div class="bg-red-500 h-2.5 rounded-full" style="width: <?php echo $total_matriculas > 0 ? (($total_canceladas + $total_trancadas) / $total_matriculas * 100) : 0; ?>%"></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Matrículas por Curso (Top 5) -->
    <div class="bg-white rounded-xl shadow-sm p-4">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Cursos Mais Populares</h3>
        <?php if (empty($cursos_populares)): ?>
        <div class="flex items-center justify-center h-64">
            <p class="text-gray-500">Nenhum dado disponível</p>
        </div>
        <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($cursos_populares as $curso): ?>
            <div>
                <div class="flex justify-between items-center mb-1">
                    <span class="text-sm font-medium text-gray-600 truncate" title="<?php echo htmlspecialchars($curso['nome']); ?>">
                        <?php echo htmlspecialchars($curso['nome']); ?>
                    </span>
                    <span class="text-sm font-medium text-gray-900"><?php echo $curso['total']; ?></span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2.5">
                    <div class="bg-indigo-600 h-2.5 rounded-full" style="width: <?php echo $curso['porcentagem']; ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Matrículas Recentes e Ações Rápidas -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <!-- Matrículas Recentes -->
    <div class="md:col-span-2 bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Matrículas Recentes</h3>
        </div>
        <?php if (empty($matriculas_recentes)): ?>
        <div class="p-4 text-center text-gray-500">
            <p>Nenhuma matrícula recente encontrada.</p>
        </div>
        <?php else: ?>
        <div class="divide-y divide-gray-200">
            <?php foreach ($matriculas_recentes as $matricula): ?>
            <div class="p-4 hover:bg-gray-50">
                <div class="flex justify-between items-start">
                    <div>
                        <h4 class="text-sm font-medium text-gray-900">
                            <a href="matriculas.php?action=visualizar&id=<?php echo $matricula['id']; ?>" class="hover:text-blue-600">
                                <?php echo htmlspecialchars($matricula['aluno_nome'] ?? 'Aluno não encontrado'); ?>
                            </a>
                        </h4>
                        <p class="text-xs text-gray-500 mt-1">
                            Curso: <?php echo htmlspecialchars($matricula['curso_nome'] ?? 'Curso não encontrado'); ?>
                        </p>
                        <p class="text-xs text-gray-500 mt-1">
                            Data: <?php echo date('d/m/Y', strtotime($matricula['created_at'])); ?>
                        </p>
                    </div>
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                        <?php 
                        if (isset($matricula['status'])) {
                            switch ($matricula['status']) {
                                case 'ativo':
                                    echo 'bg-green-100 text-green-800';
                                    break;
                                case 'pendente':
                                    echo 'bg-yellow-100 text-yellow-800';
                                    break;
                                case 'concluido':
                                    echo 'bg-blue-100 text-blue-800';
                                    break;
                                case 'cancelado':
                                    echo 'bg-red-100 text-red-800';
                                    break;
                                case 'trancado':
                                    echo 'bg-gray-300 text-gray-800';
                                    break;
                                default:
                                    echo 'bg-gray-100 text-gray-800';
                            }
                        } else {
                            echo 'bg-gray-100 text-gray-800';
                        }
                        ?>">
                        <?php 
                        if (isset($matricula['status'])) {
                            switch ($matricula['status']) {
                                case 'ativo':
                                    echo 'Ativo';
                                    break;
                                case 'pendente':
                                    echo 'Pendente';
                                    break;
                                case 'concluido':
                                    echo 'Concluído';
                                    break;
                                case 'cancelado':
                                    echo 'Cancelado';
                                    break;
                                case 'trancado':
                                    echo 'Trancado';
                                    break;
                                default:
                                    echo ucfirst($matricula['status']);
                            }
                        } else {
                            echo 'N/A';
                        }
                        ?>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="px-4 py-3 bg-gray-50 text-right">
            <a href="matriculas.php?ordenar=recentes" class="text-sm text-blue-600 hover:text-blue-800">Ver todas as matrículas recentes</a>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Ações Rápidas -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Ações Rápidas</h3>
        </div>
        <div class="p-4 space-y-4">
            <a href="matriculas.php?action=nova" class="block w-full py-2 px-4 bg-blue-600 hover:bg-blue-700 text-white text-center rounded-md">
                <i class="fas fa-plus mr-2"></i> Nova Matrícula
            </a>
            <a href="alunos.php?action=novo" class="block w-full py-2 px-4 bg-green-600 hover:bg-green-700 text-white text-center rounded-md">
                <i class="fas fa-user-plus mr-2"></i> Novo Aluno
            </a>
            <a href="relatorios.php?tipo=matriculas" class="block w-full py-2 px-4 bg-purple-600 hover:bg-purple-700 text-white text-center rounded-md">
                <i class="fas fa-chart-bar mr-2"></i> Relatório de Matrículas
            </a>
            <a href="financeiro.php?filtro=matriculas" class="block w-full py-2 px-4 bg-yellow-600 hover:bg-yellow-700 text-white text-center rounded-md">
                <i class="fas fa-money-bill-wave mr-2"></i> Financeiro de Matrículas
            </a>
        </div>
    </div>
</div>

<!-- Busca Rápida -->
<div class="bg-white rounded-xl shadow-sm p-4 mb-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Busca Rápida</h3>
    <form action="matriculas.php" method="get" class="flex flex-col md:flex-row md:items-end space-y-4 md:space-y-0 md:space-x-4">
        <input type="hidden" name="action" value="buscar">
        <div class="flex-grow">
            <label for="busca_rapida" class="block text-sm font-medium text-gray-700 mb-1">Nome do Aluno ou ID Legado</label>
            <input type="text" id="busca_rapida" name="termo" class="form-input w-full" placeholder="Digite o nome do aluno ou ID legado...">
        </div>
        <div>
            <button type="submit" class="w-full md:w-auto btn-primary py-2 px-6">
                <i class="fas fa-search mr-2"></i> Buscar
            </button>
        </div>
    </form>
</div>
