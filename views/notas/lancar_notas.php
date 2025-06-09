<?php
/**
 * View: lancar_notas.php
 * Formulário para lançar notas por disciplina
 */
?>
<div class="card mb-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <h3 class="text-sm font-medium text-gray-500 mb-1">Curso</h3>
            <p class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($curso['nome']); ?></p>
        </div>
        <div>
            <h3 class="text-sm font-medium text-gray-500 mb-1">Turma</h3>
            <p class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($turma['nome']); ?></p>
        </div>
        <div>
            <h3 class="text-sm font-medium text-gray-500 mb-1">Disciplina</h3>
            <p class="text-lg font-semibold text-gray-800">
                <?php echo htmlspecialchars($disciplina['nome']); ?>
                <?php if (!empty($disciplina['codigo'])): ?>
                <span class="text-sm text-gray-500">(<?php echo htmlspecialchars($disciplina['codigo']); ?>)</span>
                <?php endif; ?>
            </p>
        </div>
    </div>
    
    <div class="mt-4 pt-4 border-t border-gray-200">
        <div class="flex justify-between items-center">
            <input type="hidden" id="carga_horaria" value="<?php echo intval($disciplina['carga_horaria']); ?>">
            <p class="text-sm text-gray-600">
                <i class="fas fa-clock mr-1"></i> Carga horária: <?php echo intval($disciplina['carga_horaria']); ?> horas
            </p>
            
            <button type="button" id="preencher_campos" class="text-indigo-600 hover:text-indigo-900 text-sm">
                <i class="fas fa-magic mr-1"></i> Preencher campos automaticamente
            </button>
        </div>
    </div>
</div>

<?php if (empty($alunos)): ?>
<div class="card bg-yellow-50 border-l-4 border-yellow-500">
    <div class="flex items-center">
        <div class="flex-shrink-0">
            <i class="fas fa-exclamation-circle text-yellow-500 text-2xl mr-4"></i>
        </div>
        <div>
            <h3 class="text-lg font-medium text-yellow-800">Nenhum aluno matriculado</h3>
            <p class="text-yellow-700 mt-1">Não há alunos matriculados nesta turma ou as matrículas estão inativas.</p>
            <p class="text-yellow-700 mt-2">
                <a href="notas.php?action=listar_disciplinas&curso_id=<?php echo $curso_id; ?>&turma_id=<?php echo $turma_id; ?>" class="text-indigo-600 hover:text-indigo-800 underline">
                    Voltar para a lista de disciplinas
                </a>
            </p>
        </div>
    </div>
</div>
<?php else: ?>
<form action="notas.php?action=salvar_notas" method="post" class="space-y-6">
    <input type="hidden" name="curso_id" value="<?php echo $curso_id; ?>">
    <input type="hidden" name="turma_id" value="<?php echo $turma_id; ?>">
    <input type="hidden" name="disciplina_id" value="<?php echo $disciplina_id; ?>">
    
    <div class="card p-0 overflow-hidden">
        <div class="p-4 bg-gray-50 border-b flex justify-between items-center">
            <h2 class="text-xl font-semibold text-gray-800">Lançamento de Notas</h2>
            <span class="text-sm text-gray-600"><?php echo count($alunos); ?> alunos matriculados</span>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aluno</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Nota</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Freq. (%)</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Horas Aula</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Data Lançamento</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Situação</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Observações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($alunos as $index => $aluno): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <input type="hidden" name="matricula_id[]" value="<?php echo $aluno['matricula_id']; ?>">
                            <div class="flex flex-col">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($aluno['aluno_nome']); ?></div>
                                <?php if (!empty($aluno['aluno_cpf'])): ?>
                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($aluno['aluno_cpf']); ?></div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap">
                            <input type="text" 
                                   id="nota_<?php echo $index; ?>" 
                                   name="nota[]" 
                                   value="<?php echo isset($aluno['nota']) ? number_format((float)$aluno['nota'], 1, ',', '.') : ''; ?>" 
                                   class="form-input text-center w-16" 
                                   placeholder="0,0">
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap">
                            <input type="text" 
                                   id="frequencia_<?php echo $index; ?>" 
                                   name="frequencia[]" 
                                   value="<?php echo isset($aluno['frequencia']) ? number_format((float)$aluno['frequencia'], 1, ',', '.') : ''; ?>" 
                                   class="form-input text-center w-16" 
                                   placeholder="0">
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap">
                            <input type="text" 
                                   id="horas_aula_<?php echo $index; ?>" 
                                   name="horas_aula[]" 
                                   value="<?php echo isset($aluno['horas_aula']) ? number_format((float)$aluno['horas_aula'], 1, ',', '.') : ''; ?>" 
                                   class="form-input text-center w-16" 
                                   placeholder="0">
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap">
                            <input type="date" 
                                   id="data_lancamento_<?php echo $index; ?>" 
                                   name="data_lancamento[]" 
                                   value="<?php echo isset($aluno['data_lancamento']) ? $aluno['data_lancamento'] : date('Y-m-d'); ?>" 
                                   class="form-input w-32">
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap">
                            <select id="situacao_<?php echo $index; ?>" 
                                    name="situacao[]" 
                                    class="form-select w-28">
                                <option value="cursando" <?php echo (isset($aluno['situacao']) && $aluno['situacao'] === 'cursando') ? 'selected' : ''; ?>>Cursando</option>
                                <option value="aprovado" <?php echo (isset($aluno['situacao']) && $aluno['situacao'] === 'aprovado') ? 'selected' : ''; ?>>Aprovado</option>
                                <option value="reprovado" <?php echo (isset($aluno['situacao']) && $aluno['situacao'] === 'reprovado') ? 'selected' : ''; ?>>Reprovado</option>
                            </select>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <input type="text" 
                                   name="observacoes[]" 
                                   value="<?php echo isset($aluno['observacoes']) ? htmlspecialchars($aluno['observacoes']) : ''; ?>" 
                                   class="form-input w-full" 
                                   placeholder="Observações">
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="flex justify-between">
        <a href="notas.php?action=listar_disciplinas&curso_id=<?php echo $curso_id; ?>&turma_id=<?php echo $turma_id; ?>" class="btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i> Voltar
        </a>
        
        <button type="submit" class="btn-primary">
            <i class="fas fa-save mr-2"></i> Salvar Notas
        </button>
    </div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Botão para preencher campos automaticamente
        document.getElementById('preencher_campos').addEventListener('click', function() {
            if (!confirm('Deseja preencher automaticamente os campos vazios?')) {
                return;
            }
            
            const cargaHoraria = parseFloat(document.getElementById('carga_horaria').value) || 0;
            const hoje = new Date().toISOString().split('T')[0]; // Formato YYYY-MM-DD
            
            // Para cada aluno na tabela
            <?php foreach ($alunos as $index => $aluno): ?>
                // Somente preencher os campos vazios
                const notaInput = document.getElementById('nota_<?php echo $index; ?>');
                const freqInput = document.getElementById('frequencia_<?php echo $index; ?>');
                const horasInput = document.getElementById('horas_aula_<?php echo $index; ?>');
                const dataInput = document.getElementById('data_lancamento_<?php echo $index; ?>');
                
                if (!notaInput.value) {
                    notaInput.value = '0,0';
                }
                
                if (!freqInput.value) {
                    freqInput.value = '0,0';
                }
                
                if (!horasInput.value && cargaHoraria > 0) {
                    horasInput.value = cargaHoraria;
                }
                
                if (!dataInput.value) {
                    dataInput.value = hoje;
                }
                
                // Atualizar situação
                atualizarSituacao(<?php echo $index; ?>);
            <?php endforeach; ?>
        });
    });
</script>
<?php endif; ?>