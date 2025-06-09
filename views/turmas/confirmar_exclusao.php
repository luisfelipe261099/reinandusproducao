<div class="max-w-4xl mx-auto">
    <!-- Cabeçalho -->
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="h-12 w-12 rounded-full bg-red-100 flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                </div>
            </div>
            <div class="ml-4">
                <h1 class="text-2xl font-bold text-gray-900">Confirmar Exclusão da Turma</h1>
                <p class="text-gray-600">Esta ação não pode ser desfeita</p>
            </div>
        </div>
    </div>

    <!-- Informações da Turma -->
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Informações da Turma</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nome da Turma</label>
                <p class="text-sm text-gray-900 bg-gray-50 p-3 rounded-md"><?php echo htmlspecialchars($turma_dados['nome']); ?></p>
            </div>
            
            <?php if (!empty($turma_dados['codigo'])): ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Código</label>
                <p class="text-sm text-gray-900 bg-gray-50 p-3 rounded-md"><?php echo htmlspecialchars($turma_dados['codigo']); ?></p>
            </div>
            <?php endif; ?>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <p class="text-sm text-gray-900 bg-gray-50 p-3 rounded-md">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                        <?php
                        switch ($turma_dados['status']) {
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
                        ?>">
                        <?php
                        switch ($turma_dados['status']) {
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
                                echo ucfirst($turma_dados['status']);
                        }
                        ?>
                    </span>
                </p>
            </div>
            
            <?php if (!empty($turma_dados['data_inicio']) || !empty($turma_dados['data_fim'])): ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Período</label>
                <p class="text-sm text-gray-900 bg-gray-50 p-3 rounded-md">
                    <?php
                    $data_inicio = !empty($turma_dados['data_inicio']) ? date('d/m/Y', strtotime($turma_dados['data_inicio'])) : 'N/D';
                    $data_fim = !empty($turma_dados['data_fim']) ? date('d/m/Y', strtotime($turma_dados['data_fim'])) : 'N/D';
                    echo $data_inicio . ' até ' . $data_fim;
                    ?>
                </p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Aviso sobre Matrículas -->
    <div class="bg-red-50 border border-red-200 rounded-xl p-6 mb-6">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-lg font-medium text-red-800 mb-2">Atenção: Matrículas Vinculadas</h3>
                <p class="text-red-700 mb-4">
                    Esta turma possui <strong><?php echo $total_matriculas_vinculadas; ?> matrículas</strong> vinculadas a ela.
                </p>
                
                <div class="bg-white border border-red-200 rounded-lg p-4">
                    <h4 class="font-medium text-red-800 mb-2">O que acontecerá:</h4>
                    <ul class="list-disc list-inside text-sm text-red-700 space-y-1">
                        <li>As <strong><?php echo $total_matriculas_vinculadas; ?> matrículas</strong> serão <strong>desvinculadas</strong> desta turma</li>
                        <li>Os alunos <strong>permanecerão matriculados</strong> nos seus respectivos cursos</li>
                        <li>Apenas a <strong>referência à turma será removida</strong> das matrículas</li>
                        <li>A turma será <strong>excluída permanentemente</strong></li>
                        <li>Esta ação <strong>não pode ser desfeita</strong></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Botões de Ação -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center space-y-4 sm:space-y-0">
            <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-4">
                <a href="turmas.php" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Cancelar
                </a>
                
                <a href="turmas.php?action=visualizar&id=<?php echo $turma_dados['id']; ?>" class="inline-flex items-center px-4 py-2 border border-blue-300 rounded-md shadow-sm text-sm font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-eye mr-2"></i>
                    Ver Detalhes da Turma
                </a>
            </div>
            
            <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-4">
                <a href="turmas.php?action=excluir&id=<?php echo $turma_dados['id']; ?>&forcar=1" 
                   onclick="return confirm('ATENÇÃO: Esta ação irá desvincular <?php echo $total_matriculas_vinculadas; ?> matrículas e excluir a turma permanentemente. Tem certeza que deseja continuar?')"
                   class="inline-flex items-center px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    <i class="fas fa-trash mr-2"></i>
                    Desvincular e Excluir Turma
                </a>
            </div>
        </div>
        
        <div class="mt-4 p-4 bg-gray-50 rounded-lg">
            <p class="text-xs text-gray-600">
                <i class="fas fa-info-circle mr-1"></i>
                <strong>Dica:</strong> Se você não tem certeza, clique em "Ver Detalhes da Turma" para revisar as matrículas vinculadas antes de prosseguir com a exclusão.
            </p>
        </div>
    </div>
</div>
