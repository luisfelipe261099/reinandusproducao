<!-- Formulário de Edição de Nota -->
<div class="bg-white rounded-xl shadow-sm p-6">
    <form method="POST" action="notas.php" class="space-y-6">
        <input type="hidden" name="action" value="salvar">
        <input type="hidden" name="id" value="<?php echo $nota['id']; ?>">
        <input type="hidden" name="matricula_id" value="<?php echo $nota['matricula_id']; ?>">
        <input type="hidden" name="disciplina_id" value="<?php echo $nota['disciplina_id']; ?>">
        
        <!-- Informações do Aluno e Disciplina -->
        <div class="bg-gray-50 rounded-lg p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Informações do Aluno</h3>
                    <div class="space-y-1">
                        <p class="text-sm text-gray-900 font-medium"><?php echo htmlspecialchars($nota['aluno_nome']); ?></p>
                        <p class="text-xs text-gray-500">Curso: <?php echo htmlspecialchars($nota['curso_nome']); ?></p>
                        <?php if (!empty($nota['turma_nome'])): ?>
                        <p class="text-xs text-gray-500">Turma: <?php echo htmlspecialchars($nota['turma_nome']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div>
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Disciplina</h3>
                    <div class="space-y-1">
                        <p class="text-sm text-gray-900 font-medium"><?php echo htmlspecialchars($nota['disciplina_nome']); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Campos de Nota -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Nota -->
            <div>
                <label for="nota" class="block text-sm font-medium text-gray-700 mb-1">
                    Nota <span class="text-gray-400">(0 a 10)</span>
                </label>
                <input type="number" 
                       id="nota" 
                       name="nota" 
                       value="<?php echo $nota['nota'] !== null ? number_format($nota['nota'], 1, '.', '') : ''; ?>"
                       min="0" 
                       max="10" 
                       step="0.1"
                       placeholder="Ex: 8.5"
                       class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
            </div>
            
            <!-- Frequência -->
            <div>
                <label for="frequencia" class="block text-sm font-medium text-gray-700 mb-1">
                    Frequência <span class="text-gray-400">(0 a 100%)</span>
                </label>
                <div class="relative">
                    <input type="number" 
                           id="frequencia" 
                           name="frequencia" 
                           value="<?php echo $nota['frequencia'] !== null ? number_format($nota['frequencia'], 1, '.', '') : ''; ?>"
                           min="0" 
                           max="100" 
                           step="0.1"
                           placeholder="Ex: 85.5"
                           class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 pr-8">
                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                        <span class="text-gray-500 text-sm">%</span>
                    </div>
                </div>
            </div>
            
            <!-- Horas Aula -->
            <div>
                <label for="horas_aula" class="block text-sm font-medium text-gray-700 mb-1">
                    Horas Aula
                </label>
                <input type="number" 
                       id="horas_aula" 
                       name="horas_aula" 
                       value="<?php echo $nota['horas_aula'] !== null ? $nota['horas_aula'] : ''; ?>"
                       min="0" 
                       step="1"
                       placeholder="Ex: 40"
                       class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Data de Lançamento -->
            <div>
                <label for="data_lancamento" class="block text-sm font-medium text-gray-700 mb-1">
                    Data de Lançamento
                </label>
                <input type="date" 
                       id="data_lancamento" 
                       name="data_lancamento" 
                       value="<?php echo $nota['data_lancamento']; ?>"
                       class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
            </div>
            
            <!-- Situação -->
            <div>
                <label for="situacao" class="block text-sm font-medium text-gray-700 mb-1">
                    Situação
                </label>
                <select id="situacao" 
                        name="situacao" 
                        class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    <option value="cursando" <?php echo $nota['situacao'] === 'cursando' ? 'selected' : ''; ?>>Cursando</option>
                    <option value="aprovado" <?php echo $nota['situacao'] === 'aprovado' ? 'selected' : ''; ?>>Aprovado</option>
                    <option value="reprovado" <?php echo $nota['situacao'] === 'reprovado' ? 'selected' : ''; ?>>Reprovado</option>
                </select>
            </div>
        </div>
        
        <!-- Observações -->
        <div>
            <label for="observacoes" class="block text-sm font-medium text-gray-700 mb-1">
                Observações
            </label>
            <textarea id="observacoes" 
                      name="observacoes" 
                      rows="4"
                      placeholder="Observações sobre o desempenho do aluno..."
                      class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50"><?php echo htmlspecialchars($nota['observacoes'] ?? ''); ?></textarea>
        </div>
        
        <!-- Botões de Ação -->
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center space-y-3 sm:space-y-0 pt-6 border-t border-gray-200">
            <div class="flex space-x-3">
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-save mr-2"></i>
                    Salvar Alterações
                </button>
                
                <a href="notas.php" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-times mr-2"></i>
                    Cancelar
                </a>
            </div>
            
            <div class="text-xs text-gray-500">
                <i class="fas fa-info-circle mr-1"></i>
                Última atualização: <?php echo date('d/m/Y H:i', strtotime($nota['updated_at'])); ?>
            </div>
        </div>
    </form>
</div>

<!-- Dicas e Informações -->
<div class="mt-6 bg-blue-50 border border-blue-200 rounded-xl p-4">
    <div class="flex">
        <div class="flex-shrink-0">
            <i class="fas fa-lightbulb text-blue-400"></i>
        </div>
        <div class="ml-3">
            <h3 class="text-sm font-medium text-blue-800">Dicas para Lançamento de Notas</h3>
            <div class="mt-2 text-sm text-blue-700">
                <ul class="list-disc list-inside space-y-1">
                    <li>A nota deve estar entre 0 e 10, podendo usar decimais (ex: 8.5)</li>
                    <li>A frequência é calculada em porcentagem (0% a 100%)</li>
                    <li>Horas aula representa a carga horária cumprida pelo aluno</li>
                    <li>A situação será automaticamente atualizada baseada na nota e frequência</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validação em tempo real da nota
    const notaInput = document.getElementById('nota');
    const frequenciaInput = document.getElementById('frequencia');
    const situacaoSelect = document.getElementById('situacao');
    
    function validarNota() {
        const nota = parseFloat(notaInput.value);
        const frequencia = parseFloat(frequenciaInput.value);
        
        if (!isNaN(nota) && !isNaN(frequencia)) {
            // Sugestão automática de situação baseada na nota e frequência
            if (nota >= 7 && frequencia >= 75) {
                if (situacaoSelect.value === 'cursando') {
                    situacaoSelect.value = 'aprovado';
                }
            } else if (nota < 5 || frequencia < 75) {
                if (situacaoSelect.value === 'cursando') {
                    situacaoSelect.value = 'reprovado';
                }
            }
        }
    }
    
    if (notaInput) {
        notaInput.addEventListener('blur', validarNota);
    }
    
    if (frequenciaInput) {
        frequenciaInput.addEventListener('blur', validarNota);
    }
    
    // Validação do formulário antes do envio
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const nota = parseFloat(notaInput.value);
            const frequencia = parseFloat(frequenciaInput.value);
            
            if (!isNaN(nota) && (nota < 0 || nota > 10)) {
                e.preventDefault();
                alert('A nota deve estar entre 0 e 10.');
                notaInput.focus();
                return;
            }
            
            if (!isNaN(frequencia) && (frequencia < 0 || frequencia > 100)) {
                e.preventDefault();
                alert('A frequência deve estar entre 0 e 100%.');
                frequenciaInput.focus();
                return;
            }
        });
    }
});
</script>
