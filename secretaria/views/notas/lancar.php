<!-- Lançamento de Notas -->
<div class="space-y-6">
    <!-- Breadcrumb -->
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <a href="notas.php?action=lancar" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600">
                    <i class="fas fa-home mr-2"></i>
                    Lançar Notas
                </a>
            </li>
            <li>
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <span class="text-sm font-medium text-gray-500"><?php echo htmlspecialchars($curso['nome']); ?></span>
                </div>
            </li>
            <li>
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <span class="text-sm font-medium text-gray-500"><?php echo htmlspecialchars($turma['nome']); ?></span>
                </div>
            </li>
            <li aria-current="page">
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <span class="text-sm font-medium text-blue-600"><?php echo htmlspecialchars($disciplina['nome']); ?></span>
                </div>
            </li>
        </ol>
    </nav>

    <!-- Informações da Sessão -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="text-center md:text-left">
                <h3 class="text-sm font-medium text-gray-700 mb-1">Curso</h3>
                <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($curso['nome']); ?></p>
            </div>
            <div class="text-center md:text-left">
                <h3 class="text-sm font-medium text-gray-700 mb-1">Turma</h3>
                <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($turma['nome']); ?></p>
            </div>
            <div class="text-center md:text-left">
                <h3 class="text-sm font-medium text-gray-700 mb-1">Disciplina</h3>
                <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($disciplina['nome']); ?></p>
            </div>
        </div>
    </div>

    <!-- Formulário de Lançamento -->
    <?php if (empty($alunos)): ?>
    <div class="bg-white rounded-xl shadow-sm p-6 text-center">
        <div class="mb-4">
            <i class="fas fa-user-slash text-4xl text-gray-300"></i>
        </div>
        <h3 class="text-lg font-medium text-gray-900 mb-2">Nenhum aluno encontrado</h3>
        <p class="text-gray-500 mb-4">Esta turma não possui alunos matriculados ativos.</p>
        <a href="notas.php?action=lancar&curso_id=<?php echo $curso_id; ?>&turma_id=<?php echo $turma_id; ?>"
           class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
            <i class="fas fa-arrow-left mr-2"></i>
            Voltar
        </a>
    </div>
    <?php else: ?>

    <form method="POST" action="notas.php" class="bg-white rounded-xl shadow-sm overflow-hidden">
        <input type="hidden" name="action" value="salvar_lancamento">
        <input type="hidden" name="curso_id" value="<?php echo $curso_id; ?>">
        <input type="hidden" name="turma_id" value="<?php echo $turma_id; ?>">
        <input type="hidden" name="disciplina_id" value="<?php echo $disciplina_id; ?>">

        <!-- Cabeçalho do Formulário -->
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-lg font-medium text-gray-900">Lançamento de Notas</h3>
                    <p class="text-sm text-gray-500 mt-1"><?php echo count($alunos); ?> aluno(s) encontrado(s)</p>
                </div>
                <div class="mt-3 sm:mt-0 flex space-x-2">
                    <button type="button" onclick="preencherTodos()" class="inline-flex items-center px-3 py-1 border border-gray-300 rounded-md text-xs font-medium text-gray-700 bg-white hover:bg-gray-50">
                        <i class="fas fa-fill mr-1"></i>
                        Preencher Todos
                    </button>
                    <button type="button" onclick="limparTodos()" class="inline-flex items-center px-3 py-1 border border-gray-300 rounded-md text-xs font-medium text-gray-700 bg-white hover:bg-gray-50">
                        <i class="fas fa-eraser mr-1"></i>
                        Limpar Todos
                    </button>
                </div>
            </div>
        </div>

        <!-- Layout Desktop -->
        <div class="hidden lg:block">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aluno</th>
                            <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-24">Nota</th>
                            <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-24">Freq. %</th>
                            <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-24">H. Aula</th>
                            <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-32">Situação</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Observações</th>
                            <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-20">Ação</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($alunos as $index => $aluno): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <div>
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($aluno['nome']); ?></div>
                                    <div class="text-xs text-gray-500"><?php echo htmlspecialchars($aluno['cpf']); ?></div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <input type="number"
                                       name="notas[<?php echo $aluno['matricula_id']; ?>][nota]"
                                       value="<?php echo $aluno['nota'] !== null ? number_format($aluno['nota'], 1, '.', '') : ''; ?>"
                                       min="0"
                                       max="10"
                                       step="0.1"
                                       placeholder="0.0"
                                       class="w-full text-center rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 text-sm">
                            </td>
                            <td class="px-4 py-3 text-center">
                                <input type="number"
                                       name="notas[<?php echo $aluno['matricula_id']; ?>][frequencia]"
                                       value="<?php echo $aluno['frequencia'] !== null ? number_format($aluno['frequencia'], 1, '.', '') : ''; ?>"
                                       min="0"
                                       max="100"
                                       step="0.1"
                                       placeholder="0.0"
                                       class="w-full text-center rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 text-sm">
                            </td>
                            <td class="px-4 py-3 text-center">
                                <input type="number"
                                       name="notas[<?php echo $aluno['matricula_id']; ?>][horas_aula]"
                                       value="<?php echo $aluno['horas_aula'] ?: ''; ?>"
                                       min="0"
                                       step="1"
                                       placeholder="0"
                                       class="w-full text-center rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 text-sm">
                            </td>
                            <td class="px-4 py-3 text-center">
                                <select name="notas[<?php echo $aluno['matricula_id']; ?>][situacao]"
                                        class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 text-sm">
                                    <option value="cursando" <?php echo ($aluno['situacao'] ?? 'cursando') === 'cursando' ? 'selected' : ''; ?>>Cursando</option>
                                    <option value="aprovado" <?php echo ($aluno['situacao'] ?? '') === 'aprovado' ? 'selected' : ''; ?>>Aprovado</option>
                                    <option value="reprovado" <?php echo ($aluno['situacao'] ?? '') === 'reprovado' ? 'selected' : ''; ?>>Reprovado</option>
                                </select>
                            </td>
                            <td class="px-4 py-3">
                                <input type="text"
                                       name="notas[<?php echo $aluno['matricula_id']; ?>][observacoes]"
                                       value="<?php echo htmlspecialchars($aluno['observacoes'] ?? ''); ?>"
                                       placeholder="Observações..."
                                       class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 text-sm">
                            </td>
                            <td class="px-4 py-3 text-center">
                                <button type="button"
                                        onclick="salvarAlunoIndividual(<?php echo $aluno['matricula_id']; ?>)"
                                        class="bg-green-600 hover:bg-green-700 text-white px-2 py-1 rounded text-xs font-medium transition-colors duration-200 flex items-center gap-1 mx-auto">
                                    <i class="fas fa-save text-xs"></i>
                                    Salvar
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Layout Mobile -->
        <div class="lg:hidden divide-y divide-gray-200">
            <?php foreach ($alunos as $index => $aluno): ?>
            <div class="p-4">
                <div class="mb-3">
                    <h4 class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($aluno['nome']); ?></h4>
                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($aluno['cpf']); ?></p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Nota</label>
                        <input type="number"
                               name="notas[<?php echo $aluno['matricula_id']; ?>][nota]"
                               value="<?php echo $aluno['nota'] !== null ? number_format($aluno['nota'], 1, '.', '') : ''; ?>"
                               min="0"
                               max="10"
                               step="0.1"
                               placeholder="0.0"
                               class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Freq. %</label>
                        <input type="number"
                               name="notas[<?php echo $aluno['matricula_id']; ?>][frequencia]"
                               value="<?php echo $aluno['frequencia'] !== null ? number_format($aluno['frequencia'], 1, '.', '') : ''; ?>"
                               min="0"
                               max="100"
                               step="0.1"
                               placeholder="0.0"
                               class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">H. Aula</label>
                        <input type="number"
                               name="notas[<?php echo $aluno['matricula_id']; ?>][horas_aula]"
                               value="<?php echo $aluno['horas_aula'] ?: ''; ?>"
                               min="0"
                               step="1"
                               placeholder="0"
                               class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Situação</label>
                        <select name="notas[<?php echo $aluno['matricula_id']; ?>][situacao]"
                                class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 text-sm">
                            <option value="cursando" <?php echo ($aluno['situacao'] ?? 'cursando') === 'cursando' ? 'selected' : ''; ?>>Cursando</option>
                            <option value="aprovado" <?php echo ($aluno['situacao'] ?? '') === 'aprovado' ? 'selected' : ''; ?>>Aprovado</option>
                            <option value="reprovado" <?php echo ($aluno['situacao'] ?? '') === 'reprovado' ? 'selected' : ''; ?>>Reprovado</option>
                        </select>
                    </div>
                </div>

                <div class="mt-3">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Observações</label>
                    <input type="text"
                           name="notas[<?php echo $aluno['matricula_id']; ?>][observacoes]"
                           value="<?php echo htmlspecialchars($aluno['observacoes'] ?? ''); ?>"
                           placeholder="Observações..."
                           class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 text-sm">
                </div>

                <div class="mt-3 text-center">
                    <button type="button"
                            onclick="salvarAlunoIndividual(<?php echo $aluno['matricula_id']; ?>)"
                            class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200 inline-flex items-center gap-2">
                        <i class="fas fa-save"></i>
                        Salvar Aluno
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Rodapé do Formulário -->
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center space-y-3 sm:space-y-0">
                <div class="flex space-x-3">
                    <button type="submit" class="inline-flex items-center px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-save mr-2"></i>
                        Salvar Notas
                    </button>

                    <a href="notas.php?action=lancar&curso_id=<?php echo $curso_id; ?>&turma_id=<?php echo $turma_id; ?>"
                       class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Voltar
                    </a>
                </div>

                <div class="text-xs text-gray-500">
                    <i class="fas fa-info-circle mr-1"></i>
                    Preencha apenas os campos que deseja salvar
                </div>
            </div>
        </div>
    </form>
    <?php endif; ?>
</div>

<script>
// Funções para preencher e limpar todos os campos
function preencherTodos() {
    const nota = prompt('Digite a nota para todos os alunos (0-10):');
    const frequencia = prompt('Digite a frequência para todos os alunos (0-100):');
    const horasAula = prompt('Digite as horas-aula para todos os alunos:');

    if (nota !== null && nota !== '') {
        // Normaliza formato: substitui vírgula por ponto
        const notaValue = parseFloat(nota.replace(',', '.'));
        if (notaValue >= 0 && notaValue <= 10) {
            document.querySelectorAll('input[name*="[nota]"]').forEach(input => {
                input.value = notaValue.toFixed(1);
                // Dispara evento para atualizar situação
                input.dispatchEvent(new Event('blur'));
            });
        } else {
            alert('Nota deve estar entre 0 e 10!');
        }
    }

    if (frequencia !== null && frequencia !== '') {
        // Normaliza formato: substitui vírgula por ponto
        const freqValue = parseFloat(frequencia.replace(',', '.'));
        if (freqValue >= 0 && freqValue <= 100) {
            document.querySelectorAll('input[name*="[frequencia]"]').forEach(input => {
                input.value = freqValue.toFixed(1);
                // Dispara evento para atualizar situação
                input.dispatchEvent(new Event('blur'));
            });
        } else {
            alert('Frequência deve estar entre 0 e 100!');
        }
    }

    if (horasAula !== null && horasAula !== '') {
        const horasValue = parseInt(horasAula);
        if (horasValue >= 0) {
            document.querySelectorAll('input[name*="[horas_aula]"]').forEach(input => {
                input.value = horasValue;
            });
        } else {
            alert('Horas-aula deve ser um número positivo!');
        }
    }
}

function limparTodos() {
    if (confirm('Tem certeza que deseja limpar todos os campos?')) {
        document.querySelectorAll('input[name*="[nota]"], input[name*="[frequencia]"], input[name*="[horas_aula]"], input[name*="[observacoes]"]').forEach(input => {
            input.value = '';
        });

        document.querySelectorAll('select[name*="[situacao]"]').forEach(select => {
            select.value = 'cursando';
        });
    }
}

// Função para salvar aluno individual
function salvarAlunoIndividual(matriculaId) {
    // Coleta os dados do aluno específico
    const notaInput = document.querySelector(`input[name="notas[${matriculaId}][nota]"]`);
    const frequenciaInput = document.querySelector(`input[name="notas[${matriculaId}][frequencia]"]`);
    const horasInput = document.querySelector(`input[name="notas[${matriculaId}][horas_aula]"]`);
    const situacaoSelect = document.querySelector(`select[name="notas[${matriculaId}][situacao]"]`);
    const observacoesInput = document.querySelector(`input[name="notas[${matriculaId}][observacoes]"]`);

    // Verifica se há pelo menos um campo preenchido
    const temDados = (notaInput && notaInput.value.trim() !== '') ||
                     (frequenciaInput && frequenciaInput.value.trim() !== '') ||
                     (horasInput && horasInput.value.trim() !== '') ||
                     (observacoesInput && observacoesInput.value.trim() !== '');

    if (!temDados) {
        alert('Preencha pelo menos um campo para este aluno antes de salvar.');
        return;
    }

    // Cria um formulário temporário com apenas os dados deste aluno
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'notas.php';

    // Adiciona campos hidden
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = 'salvar_lancamento';
    form.appendChild(actionInput);

    const cursoInput = document.createElement('input');
    cursoInput.type = 'hidden';
    cursoInput.name = 'curso_id';
    cursoInput.value = '<?php echo $curso_id; ?>';
    form.appendChild(cursoInput);

    const turmaInput = document.createElement('input');
    turmaInput.type = 'hidden';
    turmaInput.name = 'turma_id';
    turmaInput.value = '<?php echo $turma_id; ?>';
    form.appendChild(turmaInput);

    const disciplinaInput = document.createElement('input');
    disciplinaInput.type = 'hidden';
    disciplinaInput.name = 'disciplina_id';
    disciplinaInput.value = '<?php echo $disciplina_id; ?>';
    form.appendChild(disciplinaInput);

    // Adiciona os dados do aluno
    if (notaInput && notaInput.value.trim() !== '') {
        const nota = document.createElement('input');
        nota.type = 'hidden';
        nota.name = `notas[${matriculaId}][nota]`;
        nota.value = notaInput.value;
        form.appendChild(nota);
    }

    if (frequenciaInput && frequenciaInput.value.trim() !== '') {
        const frequencia = document.createElement('input');
        frequencia.type = 'hidden';
        frequencia.name = `notas[${matriculaId}][frequencia]`;
        frequencia.value = frequenciaInput.value;
        form.appendChild(frequencia);
    }

    if (horasInput && horasInput.value.trim() !== '') {
        const horas = document.createElement('input');
        horas.type = 'hidden';
        horas.name = `notas[${matriculaId}][horas_aula]`;
        horas.value = horasInput.value;
        form.appendChild(horas);
    }

    if (situacaoSelect) {
        const situacao = document.createElement('input');
        situacao.type = 'hidden';
        situacao.name = `notas[${matriculaId}][situacao]`;
        situacao.value = situacaoSelect.value;
        form.appendChild(situacao);
    }

    if (observacoesInput && observacoesInput.value.trim() !== '') {
        const observacoes = document.createElement('input');
        observacoes.type = 'hidden';
        observacoes.name = `notas[${matriculaId}][observacoes]`;
        observacoes.value = observacoesInput.value;
        form.appendChild(observacoes);
    }

    // Submete o formulário
    document.body.appendChild(form);
    form.submit();
}

// Formatação automática e validação
document.addEventListener('DOMContentLoaded', function() {
    // Função para normalizar e formatar números
    function formatarNumero(valor, decimais = 1) {
        if (!valor || valor === '') return '';

        // Substitui vírgula por ponto
        const valorNormalizado = valor.toString().replace(',', '.');
        const numero = parseFloat(valorNormalizado);

        if (isNaN(numero)) return '';

        return numero.toFixed(decimais);
    }

    function atualizarSituacao(matriculaId) {
        const notaInput = document.querySelector(`input[name="notas[${matriculaId}][nota]"]`);
        const frequenciaInput = document.querySelector(`input[name="notas[${matriculaId}][frequencia]"]`);
        const situacaoSelect = document.querySelector(`select[name="notas[${matriculaId}][situacao]"]`);

        if (!notaInput || !frequenciaInput || !situacaoSelect) return;

        const nota = parseFloat(notaInput.value.replace(',', '.')) || 0;
        const frequencia = parseFloat(frequenciaInput.value.replace(',', '.')) || 0;

        // Se não há dados, mantém cursando
        if (nota === 0 && frequencia === 0) {
            situacaoSelect.value = 'cursando';
            return;
        }

        // Regras de aprovação
        if (frequencia < 75) {
            situacaoSelect.value = 'reprovado'; // Reprovado por frequência
        } else if (nota < 7) {
            situacaoSelect.value = 'reprovado'; // Reprovado por nota
        } else {
            situacaoSelect.value = 'aprovado'; // Aprovado
        }
    }

    // Adiciona formatação automática para campos de nota
    document.querySelectorAll('input[name*="[nota]"]').forEach(input => {
        input.addEventListener('blur', function() {
            if (this.value !== '') {
                const valorFormatado = formatarNumero(this.value, 1);
                if (valorFormatado !== '') {
                    const numero = parseFloat(valorFormatado);
                    if (numero >= 0 && numero <= 10) {
                        this.value = valorFormatado;
                    } else {
                        alert('Nota deve estar entre 0 e 10!');
                        this.focus();
                        return;
                    }
                }
            }

            // Atualiza situação
            const match = this.name.match(/notas\[(\d+)\]/);
            if (match) {
                atualizarSituacao(match[1]);
            }
        });
    });

    // Adiciona formatação automática para campos de frequência
    document.querySelectorAll('input[name*="[frequencia]"]').forEach(input => {
        input.addEventListener('blur', function() {
            if (this.value !== '') {
                const valorFormatado = formatarNumero(this.value, 1);
                if (valorFormatado !== '') {
                    const numero = parseFloat(valorFormatado);
                    if (numero >= 0 && numero <= 100) {
                        this.value = valorFormatado;
                    } else {
                        alert('Frequência deve estar entre 0 e 100!');
                        this.focus();
                        return;
                    }
                }
            }

            // Atualiza situação
            const match = this.name.match(/notas\[(\d+)\]/);
            if (match) {
                atualizarSituacao(match[1]);
            }
        });
    });

    // Adiciona validação para horas-aula
    document.querySelectorAll('input[name*="[horas_aula]"]').forEach(input => {
        input.addEventListener('blur', function() {
            if (this.value !== '') {
                const numero = parseInt(this.value);
                if (isNaN(numero) || numero < 0) {
                    alert('Horas-aula deve ser um número positivo!');
                    this.focus();
                    return;
                }
                this.value = numero;
            }
        });
    });
});
</script>