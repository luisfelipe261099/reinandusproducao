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

    <!-- Disciplinas Vinculadas -->
    <div class="col-12 mb-4">
        <div class="card shadow">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-book"></i> Disciplinas Vinculadas (<?php echo count($disciplinas_vinculadas); ?>)
                </h6>
                <button type="button" class="btn btn-sm btn-success" data-toggle="modal" data-target="#modalVincularDisciplina">
                    <i class="fas fa-plus"></i> Vincular Disciplina
                </button>
            </div>
            <div class="card-body">
                <?php if (empty($disciplinas_vinculadas)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Nenhuma disciplina vinculada</h5>
                    <p class="text-muted">Clique em "Vincular Disciplina" para adicionar disciplinas a esta turma.</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th>Disciplina</th>
                                <th>Código</th>
                                <th>Professor</th>
                                <th>Período</th>
                                <th>C.H.</th>
                                <th>Status</th>
                                <th width="120">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($disciplinas_vinculadas as $disc): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($disc['disciplina_nome']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($disc['codigo'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($disc['professor_nome'] ?? 'Não definido'); ?></td>
                                <td><?php echo htmlspecialchars($disc['periodo_letivo'] ?? '-'); ?></td>
                                <td>
                                    <?php echo $disc['carga_horaria_turma'] ?? $disc['carga_padrao']; ?>h
                                </td>
                                <td>
                                    <?php
                                    $status_classes = [
                                        'planejada' => 'warning',
                                        'em_andamento' => 'success',
                                        'concluida' => 'info',
                                        'cancelada' => 'danger'
                                    ];
                                    ?>
                                    <span class="badge badge-<?php echo $status_classes[$disc['status']]; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $disc['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-sm btn-primary" 
                                                onclick="editarVinculo(<?php echo $disc['disciplina_id']; ?>)"
                                                title="Editar Vínculo">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="notas.php?turma_id=<?php echo $turma_id; ?>&disciplina_id=<?php echo $disc['disciplina_id']; ?>" 
                                           class="btn btn-sm btn-success" title="Lançar Notas">
                                            <i class="fas fa-clipboard-list"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger" 
                                                onclick="confirmarDesvinculo(<?php echo $disc['disciplina_id']; ?>)"
                                                title="Desvincular">
                                            <i class="fas fa-unlink"></i>
                                        </button>
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

<!-- Modal Vincular Disciplina -->
<div class="modal fade" id="modalVincularDisciplina" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Vincular Disciplina à Turma</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="turmas_disciplinas.php?action=vincular&turma_id=<?php echo $turma_id; ?>" method="post">
                <div class="modal-body">
                    <?php if (empty($disciplinas_disponiveis)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Todas as disciplinas do curso já estão vinculadas a esta turma.
                    </div>
                    <?php else: ?>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="disciplina_id">Disciplina *</label>
                                <select name="disciplina_id" id="disciplina_id" class="form-control" required>
                                    <option value="">Selecione uma disciplina...</option>
                                    <?php foreach ($disciplinas_disponiveis as $disc): ?>
                                    <option value="<?php echo $disc['id']; ?>">
                                        <?php echo htmlspecialchars($disc['nome']); ?>
                                        <?php if ($disc['codigo']): ?>
                                        (<?php echo htmlspecialchars($disc['codigo']); ?>)
                                        <?php endif; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="professor_id">Professor</label>
                                <select name="professor_id" id="professor_id" class="form-control">
                                    <option value="">Selecione um professor...</option>
                                    <?php foreach ($professores as $prof): ?>
                                    <option value="<?php echo $prof['id']; ?>">
                                        <?php echo htmlspecialchars($prof['nome']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="periodo_letivo">Período Letivo</label>
                                <input type="text" name="periodo_letivo" id="periodo_letivo" class="form-control" 
                                       placeholder="Ex: 1º Semestre 2024">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="carga_horaria_turma">Carga Horária (horas)</label>
                                <input type="number" name="carga_horaria_turma" id="carga_horaria_turma" class="form-control" 
                                       placeholder="Deixe vazio para usar padrão da disciplina">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="data_inicio">Data Início</label>
                                <input type="date" name="data_inicio" id="data_inicio" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="data_fim">Data Fim</label>
                                <input type="date" name="data_fim" id="data_fim" class="form-control">
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label for="observacoes">Observações</label>
                                <textarea name="observacoes" id="observacoes" class="form-control" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <?php if (!empty($disciplinas_disponiveis)): ?>
                    <button type="submit" class="btn btn-success">Vincular Disciplina</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function confirmarDesvinculo(disciplinaId) {
    if (confirm('Tem certeza que deseja desvincular esta disciplina da turma?')) {
        window.location.href = `turmas_disciplinas.php?action=desvincular&turma_id=<?php echo $turma_id; ?>&disciplina_id=${disciplinaId}`;
    }
}

function editarVinculo(disciplinaId) {
    // Implementar modal de edição se necessário
    alert('Funcionalidade de edição em desenvolvimento');
}
</script>
