<div class="row">
    <!-- Informações da Disciplina -->
    <div class="col-12 mb-4">
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-info-circle"></i> Informações da Disciplina
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-2">
                        <strong>Turma:</strong><br>
                        <?php echo htmlspecialchars($info['turma_nome']); ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Disciplina:</strong><br>
                        <?php echo htmlspecialchars($info['disciplina_nome']); ?>
                        <?php if ($info['codigo']): ?>
                        <small class="text-muted">(<?php echo htmlspecialchars($info['codigo']); ?>)</small>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-2">
                        <strong>Curso:</strong><br>
                        <?php echo htmlspecialchars($info['curso_nome']); ?>
                    </div>
                    <div class="col-md-2">
                        <strong>Polo:</strong><br>
                        <?php echo htmlspecialchars($info['polo_nome']); ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Professor:</strong><br>
                        <?php echo $info['professor_nome'] ? htmlspecialchars($info['professor_nome']) : '<span class="text-muted">Não definido</span>'; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulário de Lançamento de Notas -->
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-clipboard-list"></i> Lançamento de Notas (<?php echo count($alunos); ?> alunos)
                </h6>
                <div>
                    <button type="button" class="btn btn-sm btn-info" onclick="preencherTodos()">
                        <i class="fas fa-fill"></i> Preencher Todos
                    </button>
                    <button type="button" class="btn btn-sm btn-warning" onclick="limparTodos()">
                        <i class="fas fa-eraser"></i> Limpar Todos
                    </button>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($alunos)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-user-graduate fa-4x text-muted mb-4"></i>
                    <h4 class="text-muted">Nenhum aluno matriculado</h4>
                    <p class="text-muted">Esta turma não possui alunos matriculados.</p>
                </div>
                <?php else: ?>
                <form action="notas_turmas.php?action=salvar_notas&turma_id=<?php echo $turma_id; ?>&disciplina_id=<?php echo $disciplina_id; ?>" 
                      method="post" id="formNotas">
                    
                    <!-- Controles de Preenchimento Rápido -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <label class="small text-muted">Nota padrão:</label>
                            <input type="number" id="notaPadrao" class="form-control form-control-sm" 
                                   min="0" max="10" step="0.1" placeholder="Ex: 7.5">
                        </div>
                        <div class="col-md-3">
                            <label class="small text-muted">Frequência padrão:</label>
                            <input type="number" id="frequenciaPadrao" class="form-control form-control-sm" 
                                   min="0" max="100" step="0.1" placeholder="Ex: 85">
                        </div>
                        <div class="col-md-3">
                            <label class="small text-muted">Situação padrão:</label>
                            <select id="situacaoPadrao" class="form-control form-control-sm">
                                <option value="">Selecione...</option>
                                <option value="cursando">Cursando</option>
                                <option value="aprovado">Aprovado</option>
                                <option value="reprovado">Reprovado</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="button" class="btn btn-sm btn-primary" onclick="aplicarPadrao()">
                                <i class="fas fa-magic"></i> Aplicar Padrão
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th width="30">#</th>
                                    <th>Aluno</th>
                                    <th width="100">Nota (0-10)</th>
                                    <th width="120">Frequência (%)</th>
                                    <th width="120">Situação</th>
                                    <th width="200">Observações</th>
                                    <th width="80">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($alunos as $index => $aluno): ?>
                                <tr>
                                    <td class="text-center"><?php echo $index + 1; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($aluno['aluno_nome']); ?></strong>
                                        <br>
                                        <small class="text-muted">
                                            CPF: <?php echo htmlspecialchars($aluno['cpf'] ?? 'Não informado'); ?>
                                            <?php if ($aluno['numero_matricula']): ?>
                                            | Mat: <?php echo htmlspecialchars($aluno['numero_matricula']); ?>
                                            <?php endif; ?>
                                        </small>
                                    </td>
                                    <td>
                                        <input type="number" 
                                               name="notas[<?php echo $aluno['matricula_id']; ?>][nota]" 
                                               class="form-control form-control-sm nota-input" 
                                               min="0" max="10" step="0.1"
                                               value="<?php echo $aluno['nota'] ?? ''; ?>"
                                               placeholder="0.0">
                                    </td>
                                    <td>
                                        <input type="number" 
                                               name="notas[<?php echo $aluno['matricula_id']; ?>][frequencia]" 
                                               class="form-control form-control-sm frequencia-input" 
                                               min="0" max="100" step="0.1"
                                               value="<?php echo $aluno['frequencia'] ?? ''; ?>"
                                               placeholder="0.0">
                                    </td>
                                    <td>
                                        <select name="notas[<?php echo $aluno['matricula_id']; ?>][situacao]" 
                                                class="form-control form-control-sm situacao-input">
                                            <option value="cursando" <?php echo ($aluno['situacao'] ?? 'cursando') === 'cursando' ? 'selected' : ''; ?>>
                                                Cursando
                                            </option>
                                            <option value="aprovado" <?php echo ($aluno['situacao'] ?? '') === 'aprovado' ? 'selected' : ''; ?>>
                                                Aprovado
                                            </option>
                                            <option value="reprovado" <?php echo ($aluno['situacao'] ?? '') === 'reprovado' ? 'selected' : ''; ?>>
                                                Reprovado
                                            </option>
                                        </select>
                                    </td>
                                    <td>
                                        <textarea name="notas[<?php echo $aluno['matricula_id']; ?>][observacoes]" 
                                                  class="form-control form-control-sm" 
                                                  rows="2" 
                                                  placeholder="Observações..."><?php echo htmlspecialchars($aluno['observacoes'] ?? ''); ?></textarea>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($aluno['nota_id']): ?>
                                        <span class="badge badge-success" title="Nota já lançada em <?php echo date('d/m/Y', strtotime($aluno['data_lancamento'])); ?>">
                                            <i class="fas fa-check"></i> Lançada
                                        </span>
                                        <?php else: ?>
                                        <span class="badge badge-secondary">
                                            <i class="fas fa-clock"></i> Pendente
                                        </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i>
                                As notas serão salvas automaticamente. Campos vazios serão ignorados.
                            </small>
                        </div>
                        <div>
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-save"></i> Salvar Notas
                            </button>
                        </div>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function preencherTodos() {
    if (confirm('Isso irá preencher todos os campos vazios com valores padrão. Continuar?')) {
        aplicarPadrao();
    }
}

function limparTodos() {
    if (confirm('Isso irá limpar todos os campos. Continuar?')) {
        document.querySelectorAll('.nota-input').forEach(input => input.value = '');
        document.querySelectorAll('.frequencia-input').forEach(input => input.value = '');
        document.querySelectorAll('.situacao-input').forEach(select => select.value = 'cursando');
    }
}

function aplicarPadrao() {
    const notaPadrao = document.getElementById('notaPadrao').value;
    const frequenciaPadrao = document.getElementById('frequenciaPadrao').value;
    const situacaoPadrao = document.getElementById('situacaoPadrao').value;
    
    if (notaPadrao) {
        document.querySelectorAll('.nota-input').forEach(input => {
            if (!input.value) input.value = notaPadrao;
        });
    }
    
    if (frequenciaPadrao) {
        document.querySelectorAll('.frequencia-input').forEach(input => {
            if (!input.value) input.value = frequenciaPadrao;
        });
    }
    
    if (situacaoPadrao) {
        document.querySelectorAll('.situacao-input').forEach(select => {
            select.value = situacaoPadrao;
        });
    }
}

// Validação em tempo real
document.addEventListener('DOMContentLoaded', function() {
    // Validação de notas
    document.querySelectorAll('.nota-input').forEach(input => {
        input.addEventListener('input', function() {
            const valor = parseFloat(this.value);
            if (valor < 0 || valor > 10) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });
    });
    
    // Validação de frequência
    document.querySelectorAll('.frequencia-input').forEach(input => {
        input.addEventListener('input', function() {
            const valor = parseFloat(this.value);
            if (valor < 0 || valor > 100) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });
    });
    
    // Confirmação antes de enviar
    document.getElementById('formNotas').addEventListener('submit', function(e) {
        const notasPreenchidas = document.querySelectorAll('.nota-input').length;
        let notasComValor = 0;
        
        document.querySelectorAll('.nota-input').forEach(input => {
            if (input.value) notasComValor++;
        });
        
        if (notasComValor === 0) {
            if (!confirm('Nenhuma nota foi preenchida. Deseja continuar mesmo assim?')) {
                e.preventDefault();
            }
        }
    });
});
</script>
