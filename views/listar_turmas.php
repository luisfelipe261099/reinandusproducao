<?php
/**
 * View: listar_cursos.php
 * Lista todos os cursos disponíveis
 */
?>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php if (empty($cursos)): ?>
    <div class="col-span-3">
        <div class="card bg-yellow-50 border-l-4 border-yellow-500">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-circle text-yellow-500 text-2xl mr-4"></i>
                </div>
                <div>
                    <h3 class="text-lg font-medium text-yellow-800">Nenhum curso encontrado</h3>
                    <p class="text-yellow-700 mt-1">Não há cursos ativos disponíveis para lançamento de notas.</p>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
        <?php foreach ($cursos as $curso): ?>
        <div class="curso-card card">
            <div class="flex flex-col h-full">
                <div class="flex justify-between items-start mb-4">
                    <h2 class="text-xl font-semibold text-gray-800"><?php echo htmlspecialchars($curso['nome']); ?></h2>
                    <?php if (!empty($curso['sigla'])): ?>
                    <span class="badge badge-secondary"><?php echo htmlspecialchars($curso['sigla']); ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="flex-grow">
                    <div class="flex flex-wrap gap-2 mb-4">
                        <span class="badge badge-primary"><?php echo htmlspecialchars(ucfirst($curso['modalidade'])); ?></span>
                        <span class="badge badge-primary"><?php echo htmlspecialchars(str_replace('_', ' ', ucfirst($curso['nivel']))); ?></span>
                    </div>
                    
                    <div class="mb-4">
                        <p class="text-gray-600">
                            <i class="fas fa-users mr-2"></i> 
                            <?php echo $curso['total_turmas']; ?> turma(s)
                        </p>
                    </div>
                </div>
                
                <div class="flex justify-end mt-4">
                    <a href="notas.php?action=listar_turmas&curso_id=<?php echo $curso['id']; ?>" class="btn-primary">
                        <i class="fas fa-arrow-right mr-2"></i> Selecionar
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>