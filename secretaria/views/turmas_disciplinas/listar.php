<div class="row">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-link"></i> Vínculos Turmas-Disciplinas
                </h6>
            </div>
            <div class="card-body">
                <?php if (empty($turmas)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Nenhuma turma encontrada</h5>
                    <p class="text-muted">Não há turmas ativas ou em planejamento no momento.</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="dataTable">
                        <thead class="thead-light">
                            <tr>
                                <th>Turma</th>
                                <th>Curso</th>
                                <th>Polo</th>
                                <th>Status</th>
                                <th>Disciplinas</th>
                                <th>Alunos</th>
                                <th width="120">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($turmas as $turma): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($turma['turma_nome']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($turma['curso_nome']); ?></td>
                                <td><?php echo htmlspecialchars($turma['polo_nome']); ?></td>
                                <td>
                                    <?php
                                    $status_classes = [
                                        'planejada' => 'warning',
                                        'em_andamento' => 'success',
                                        'concluida' => 'info',
                                        'cancelada' => 'danger'
                                    ];
                                    $status_labels = [
                                        'planejada' => 'Planejada',
                                        'em_andamento' => 'Em Andamento',
                                        'concluida' => 'Concluída',
                                        'cancelada' => 'Cancelada'
                                    ];
                                    ?>
                                    <span class="badge badge-<?php echo $status_classes[$turma['status']]; ?>">
                                        <?php echo $status_labels[$turma['status']]; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-primary">
                                        <?php echo $turma['total_disciplinas']; ?> disciplina(s)
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-info">
                                        <?php echo $turma['total_alunos']; ?> aluno(s)
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="turmas_disciplinas.php?action=gerenciar&turma_id=<?php echo $turma['id']; ?>" 
                                           class="btn btn-sm btn-primary" title="Gerenciar Disciplinas">
                                            <i class="fas fa-cogs"></i>
                                        </a>
                                        <a href="notas.php?turma_id=<?php echo $turma['id']; ?>" 
                                           class="btn btn-sm btn-success" title="Lançar Notas">
                                            <i class="fas fa-clipboard-list"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#dataTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Portuguese-Brasil.json"
        },
        "order": [[ 0, "asc" ]],
        "pageLength": 25,
        "responsive": true
    });
});
</script>
