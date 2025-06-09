<div class="row">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-users"></i> Selecione uma Turma para Lançar Notas
                </h6>
            </div>
            <div class="card-body">
                <?php if (empty($turmas)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-users fa-4x text-muted mb-4"></i>
                    <h4 class="text-muted">Nenhuma turma disponível</h4>
                    <p class="text-muted mb-4">
                        Não há turmas com disciplinas vinculadas para lançamento de notas.
                    </p>
                    <div class="alert alert-info">
                        <strong>Dica:</strong> Para lançar notas, é necessário:
                        <ul class="mb-0 mt-2 text-left">
                            <li>Ter turmas criadas e ativas</li>
                            <li>Vincular disciplinas às turmas</li>
                            <li>Ter alunos matriculados nas turmas</li>
                        </ul>
                    </div>
                    <a href="turmas_disciplinas.php" class="btn btn-primary">
                        <i class="fas fa-link"></i> Gerenciar Vínculos Turma-Disciplina
                    </a>
                </div>
                <?php else: ?>
                <div class="row">
                    <?php foreach ($turmas as $turma): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100 border-left-primary">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="mr-3">
                                        <i class="fas fa-users fa-2x text-primary"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h5 class="card-title mb-1">
                                            <?php echo htmlspecialchars($turma['turma_nome']); ?>
                                        </h5>
                                        <p class="card-text text-muted small mb-2">
                                            <strong>Curso:</strong> <?php echo htmlspecialchars($turma['curso_nome']); ?><br>
                                            <strong>Polo:</strong> <?php echo htmlspecialchars($turma['polo_nome']); ?>
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="row text-center mt-3">
                                    <div class="col-6">
                                        <div class="border-right">
                                            <div class="h5 mb-0 text-primary">
                                                <?php echo $turma['total_disciplinas']; ?>
                                            </div>
                                            <div class="small text-muted">Disciplinas</div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="h5 mb-0 text-success">
                                            <?php echo $turma['total_alunos']; ?>
                                        </div>
                                        <div class="small text-muted">Alunos</div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent">
                                <a href="notas_turmas.php?action=selecionar_disciplina&turma_id=<?php echo $turma['id']; ?>" 
                                   class="btn btn-primary btn-block">
                                    <i class="fas fa-arrow-right"></i> Selecionar Turma
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="mt-4 text-center">
                    <a href="turmas_disciplinas.php" class="btn btn-outline-primary">
                        <i class="fas fa-cogs"></i> Gerenciar Vínculos Turma-Disciplina
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.card:hover {
    transform: translateY(-2px);
    transition: transform 0.2s;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1) !important;
}

.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}
</style>
