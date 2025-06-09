<div class="row">
    <!-- Informações da Turma -->
    <div class="col-12 mb-4">
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-info-circle"></i> Informações da Turma
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <strong>Turma:</strong><br>
                        <?php echo htmlspecialchars($turma['nome']); ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Curso:</strong><br>
                        <?php echo htmlspecialchars($turma['curso_nome']); ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Polo:</strong><br>
                        <?php echo htmlspecialchars($turma['polo_nome']); ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Status:</strong><br>
                        <span class="badge badge-<?php echo $turma['status'] === 'em_andamento' ? 'success' : 'warning'; ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $turma['status'])); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Disciplinas da Turma -->
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-book"></i> Selecione uma Disciplina para Lançar Notas
                </h6>
            </div>
            <div class="card-body">
                <?php if (empty($disciplinas)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-book-open fa-4x text-muted mb-4"></i>
                    <h4 class="text-muted">Nenhuma disciplina vinculada</h4>
                    <p class="text-muted mb-4">
                        Esta turma não possui disciplinas vinculadas para lançamento de notas.
                    </p>
                    <a href="turmas_disciplinas.php?action=gerenciar&turma_id=<?php echo $turma_id; ?>" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Vincular Disciplinas
                    </a>
                </div>
                <?php else: ?>
                <div class="row">
                    <?php foreach ($disciplinas as $disciplina): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100 border-left-success">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="mr-3">
                                        <i class="fas fa-book fa-2x text-success"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="card-title mb-1">
                                            <?php echo htmlspecialchars($disciplina['disciplina_nome']); ?>
                                        </h6>
                                        <?php if ($disciplina['codigo']): ?>
                                        <small class="text-muted">
                                            Código: <?php echo htmlspecialchars($disciplina['codigo']); ?>
                                        </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <?php if ($disciplina['professor_nome']): ?>
                                <div class="mb-3">
                                    <small class="text-muted">
                                        <i class="fas fa-user-tie"></i> 
                                        <?php echo htmlspecialchars($disciplina['professor_nome']); ?>
                                    </small>
                                </div>
                                <?php endif; ?>
                                
                                <div class="row text-center">
                                    <div class="col-6">
                                        <div class="border-right">
                                            <div class="h6 mb-0 text-primary">
                                                <?php echo $disciplina['total_notas_lancadas']; ?>
                                            </div>
                                            <div class="small text-muted">Notas Lançadas</div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="h6 mb-0 text-info">
                                            <?php echo $disciplina['total_alunos']; ?>
                                        </div>
                                        <div class="small text-muted">Total Alunos</div>
                                    </div>
                                </div>
                                
                                <!-- Barra de Progresso -->
                                <?php 
                                $progresso = $disciplina['total_alunos'] > 0 ? 
                                    ($disciplina['total_notas_lancadas'] / $disciplina['total_alunos']) * 100 : 0;
                                $progresso_class = $progresso == 100 ? 'success' : ($progresso > 50 ? 'warning' : 'info');
                                ?>
                                <div class="mt-3">
                                    <div class="small text-muted mb-1">Progresso de Lançamento</div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-<?php echo $progresso_class; ?>" 
                                             style="width: <?php echo $progresso; ?>%"></div>
                                    </div>
                                    <div class="small text-muted mt-1"><?php echo number_format($progresso, 1); ?>%</div>
                                </div>
                                
                                <!-- Status da Disciplina -->
                                <div class="mt-3">
                                    <?php
                                    $status_classes = [
                                        'planejada' => 'warning',
                                        'em_andamento' => 'success',
                                        'concluida' => 'info',
                                        'cancelada' => 'danger'
                                    ];
                                    ?>
                                    <span class="badge badge-<?php echo $status_classes[$disciplina['status']]; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $disciplina['status'])); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent">
                                <a href="notas_turmas.php?action=lancar_notas&turma_id=<?php echo $turma_id; ?>&disciplina_id=<?php echo $disciplina['disciplina_id']; ?>" 
                                   class="btn btn-success btn-block">
                                    <i class="fas fa-clipboard-list"></i> Lançar Notas
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="mt-4 text-center">
                    <a href="turmas_disciplinas.php?action=gerenciar&turma_id=<?php echo $turma_id; ?>" class="btn btn-outline-primary">
                        <i class="fas fa-cogs"></i> Gerenciar Disciplinas da Turma
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}

.card:hover {
    transform: translateY(-2px);
    transition: transform 0.2s;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1) !important;
}
</style>
