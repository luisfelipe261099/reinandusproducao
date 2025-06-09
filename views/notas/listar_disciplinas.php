<?php
/**
 * View: listar_disciplinas.php
 * Lista todas as disciplinas de um curso/turma com opções para adicionar novas
 */
?>
<div class="card mb-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <h3 class="text-sm font-medium text-gray-500 mb-1">Curso</h3>
            <p class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($curso['nome']); ?></p>
            
            <?php if (!empty($curso['sigla'])): ?>
            <span class="badge badge-secondary mt-2"><?php echo htmlspecialchars($curso['sigla']); ?></span>
            <?php endif; ?>
        </div>
        <div>
            <h3 class="text-sm font-medium text-gray-500 mb-1">Turma</h3>
            <p class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($turma['nome']); ?></p>
            
            <?php
            $turnos = [
                'manha' => 'Manhã',
                'tarde' => 'Tarde',
                'noite' => 'Noite',
                'integral' => 'Integral'
            ];
            $turno = isset($turnos[$turma['turno']]) ? $turnos[$turma['turno']] : ucfirst($turma['turno']);
            
            $status_badges = [
                'planejada' => '<span class="badge badge-warning">Planejada</span>',
                'em_andamento' => '<span class="badge badge-success">Em andamento</span>',
                'concluida' => '<span class="badge badge-secondary">Concluída</span>',
                'cancelada' => '<span class="badge badge-danger">Cancelada</span>'
            ];
            $status = isset($status_badges[$turma['status']]) ? $status_badges[$turma['status']] : '<span class="badge badge-secondary">' . ucfirst($turma['status']) . '</span>';
            ?>
            <div class="flex space-x-2 mt-2">
                <span class="badge badge-primary"><?php echo $turno; ?></span>
                <?php echo $status; ?>
            </div>
        </div>
    </div>
</div>

<div class="flex justify-end mb-4">
    <button type="button" class="btn-primary mr-2" onclick="mostrarModalNovaDisciplina()">
        <i class="fas fa-plus mr-2"></i> Nova Disciplina
    </button>
    <button type="button" class="btn-secondary" onclick="mostrarModalAssociarDisciplina()">
        <i class="fas fa-link mr-2"></i> Associar Disciplina Existente
    </button>
</div>

<?php if (empty($disciplinas)): ?>
<div class="card bg-yellow-50 border-l-4 border-yellow-500">
    <div class="flex items-center">
        <div class="flex-shrink-0">
            <i class="fas fa-exclamation-circle text-yellow-500 text-2xl mr-4"></i>
        </div>
        <div>
            <h3 class="text-lg font-medium text-yellow-800">Nenhuma disciplina encontrada</h3>
            <p class="text-yellow-700 mt-1">Não há disciplinas ativas disponíveis para este curso.</p>
            <p class="text-yellow-700 mt-2">
                Utilize os botões acima para adicionar uma nova disciplina ou associar uma disciplina existente.
            </p>
        </div>
    </div>
</div>
<?php else: ?>
<div class="card p-0 overflow-hidden">
    <div class="p-4 bg-gray-50 border-b">
        <h2 class="text-xl font-semibold text-gray-800">Disciplinas disponíveis para lançamento de notas</h2>
        <p class="text-sm text-gray-600 mt-1">Selecione uma disciplina para lançar notas para os alunos desta turma.</p>
    </div>
    
    <ul class="divide-y divide-gray-200">
        <?php foreach ($disciplinas as $disciplina): ?>
        <li class="hover:bg-gray-50">
            <a href="notas.php?action=lancar_notas&curso_id=<?php echo $curso_id; ?>&turma_id=<?php echo $turma_id; ?>&disciplina_id=<?php echo $disciplina['id']; ?>" 
               class="block p-4">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="text-lg font-medium text-gray-800"><?php echo htmlspecialchars($disciplina['nome']); ?></h3>
                        
                        <div class="flex items-center mt-2">
                            <?php if (!empty($disciplina['codigo'])): ?>
                            <span class="badge badge-secondary mr-2"><?php echo htmlspecialchars($disciplina['codigo']); ?></span>
                            <?php endif; ?>
                            
                            <span class="text-sm text-gray-500">
                                <i class="fas fa-clock mr-1"></i> <?php echo intval($disciplina['carga_horaria']); ?> horas
                            </span>
                        </div>
                    </div>
                    
                    <div class="flex flex-col items-end">
                        <span class="badge <?php echo $disciplina['total_notas_lancadas'] > 0 ? 'badge-success' : 'badge-warning'; ?>">
                            <?php echo $disciplina['total_notas_lancadas'] > 0 ? 'Notas lançadas' : 'Pendente'; ?>
                        </span>
                        
                        <?php if ($disciplina['total_notas_lancadas'] > 0): ?>
                        <span class="text-xs text-gray-500 mt-1">Última atualização: <?php echo date('d/m/Y'); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="mt-6">
    <a href="notas.php?action=listar_turmas&curso_id=<?php echo $curso_id; ?>" class="btn-secondary">
        <i class="fas fa-arrow-left mr-2"></i> Voltar para Turmas
    </a>
</div>

<!-- Modal para Nova Disciplina -->
<div id="modalNovaDisciplina" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Adicionar Nova Disciplina</h3>
            <button type="button" class="text-gray-400 hover:text-gray-500" onclick="fecharModal('modalNovaDisciplina')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form action="notas.php?action=adicionar_disciplina" method="post" class="space-y-4">
            <input type="hidden" name="curso_id" value="<?php echo $curso_id; ?>">
            <input type="hidden" name="turma_id" value="<?php echo $turma_id; ?>">
            
            <div>
                <label for="nome" class="block text-sm font-medium text-gray-700 mb-1">Nome da Disciplina <span class="text-red-500">*</span></label>
                <input type="text" name="nome" id="nome" class="form-input w-full" required>
            </div>
            
            <div>
                <label for="codigo" class="block text-sm font-medium text-gray-700 mb-1">Código</label>
                <input type="text" name="codigo" id="codigo" class="form-input w-full" placeholder="Ex: MAT101">
            </div>
            
            <div>
                <label for="carga_horaria" class="block text-sm font-medium text-gray-700 mb-1">Carga Horária <span class="text-red-500">*</span></label>
                <input type="number" name="carga_horaria" id="carga_horaria" class="form-input w-full" value="60" min="1" required>
            </div>
            
            <div class="pt-4 border-t border-gray-200">
                <button type="submit" class="btn-primary w-full">
                    <i class="fas fa-plus mr-2"></i> Adicionar Disciplina
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal para Associar Disciplina Existente -->
<div id="modalAssociarDisciplina" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Associar Disciplina Existente</h3>
            <button type="button" class="text-gray-400 hover:text-gray-500" onclick="fecharModal('modalAssociarDisciplina')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form action="notas.php?action=associar_disciplina" method="post">
            <input type="hidden" name="curso_id" value="<?php echo $curso_id; ?>">
            <input type="hidden" name="turma_id" value="<?php echo $turma_id; ?>">
            
            <div class="mb-4">
                <label for="busca_disciplina" class="block text-sm font-medium text-gray-700 mb-1">Buscar Disciplina</label>
                <div class="relative">
                    <input type="text" id="busca_disciplina" class="form-input w-full pl-10" placeholder="Digite o nome ou código da disciplina">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-400"></i>
                    </div>
                </div>
            </div>
            
            <div class="max-h-60 overflow-y-auto mb-4 border border-gray-200 rounded-md">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Selecionar</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Carga Horária</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200" id="disciplinas_disponiveis">
                        <!-- As disciplinas serão carregadas aqui via JavaScript -->
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                                Digite para buscar disciplinas disponíveis...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div id="sem_disciplinas" class="bg-yellow-50 p-4 rounded-md mb-4 hidden">
                <p class="text-yellow-700 text-sm">
                    Não foram encontradas disciplinas correspondentes à sua busca.
                </p>
            </div>
            
            <div class="pt-4 border-t border-gray-200 flex justify-end">
                <button type="button" class="btn-secondary mr-2" onclick="fecharModal('modalAssociarDisciplina')">
                    Cancelar
                </button>
                <button type="submit" id="btn_associar" class="btn-primary" disabled>
                    <i class="fas fa-link mr-2"></i> Associar Disciplinas Selecionadas
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Funções para mostrar/esconder os modais
    function mostrarModalNovaDisciplina() {
        document.getElementById('modalNovaDisciplina').classList.remove('hidden');
    }
    
    function mostrarModalAssociarDisciplina() {
        document.getElementById('modalAssociarDisciplina').classList.remove('hidden');
        carregarDisciplinasDisponiveis('');
    }
    
    function fecharModal(id) {
        document.getElementById(id).classList.add('hidden');
    }
    
    // Busca de disciplinas disponíveis
    let timeoutId;
    document.getElementById('busca_disciplina').addEventListener('input', function() {
        clearTimeout(timeoutId);
        const termo = this.value;
        
        timeoutId = setTimeout(function() {
            carregarDisciplinasDisponiveis(termo);
        }, 300);
    });
    
    // Carregar disciplinas disponíveis
    function carregarDisciplinasDisponiveis(termo) {
        const cursoId = <?php echo $curso_id; ?>;
        const turmaId = <?php echo $turma_id; ?>;
        
        fetch(`buscar_disciplinas.php?curso_id=${cursoId}&turma_id=${turmaId}&termo=${encodeURIComponent(termo)}`)
            .then(response => response.json())
            .then(data => {
                const tbody = document.getElementById('disciplinas_disponiveis');
                const semDisciplinas = document.getElementById('sem_disciplinas');
                const btnAssociar = document.getElementById('btn_associar');
                
                if (data.length === 0) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                                Nenhuma disciplina encontrada.
                            </td>
                        </tr>
                    `;
                    semDisciplinas.classList.remove('hidden');
                    btnAssociar.disabled = true;
                } else {
                    tbody.innerHTML = '';
                    semDisciplinas.classList.add('hidden');
                    
                    data.forEach(disciplina => {
                        tbody.innerHTML += `
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <input type="checkbox" name="disciplina_ids[]" value="${disciplina.id}" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded disciplina-checkbox">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    ${disciplina.nome}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    ${disciplina.codigo || 'N/A'}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    ${disciplina.carga_horaria} horas
                                </td>
                            </tr>
                        `;
                    });
                    
                    // Adicionar eventos para os checkboxes
                    document.querySelectorAll('.disciplina-checkbox').forEach(checkbox => {
                        checkbox.addEventListener('change', () => {
                            const algumSelecionado = [...document.querySelectorAll('.disciplina-checkbox')].some(cb => cb.checked);
                            btnAssociar.disabled = !algumSelecionado;
                        });
                    });
                }
            })
            .catch(error => {
                console.error('Erro ao buscar disciplinas:', error);
                document.getElementById('disciplinas_disponiveis').innerHTML = `
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-sm text-red-500">
                            Erro ao buscar disciplinas. Tente novamente.
                        </td>
                    </tr>
                `;
            });
    }
    
    // Fechar modais ao clicar fora
    window.addEventListener('click', function(event) {
        const modalNovaDisciplina = document.getElementById('modalNovaDisciplina');
        const modalAssociarDisciplina = document.getElementById('modalAssociarDisciplina');
        
        if (event.target === modalNovaDisciplina) {
            fecharModal('modalNovaDisciplina');
        } else if (event.target === modalAssociarDisciplina) {
            fecharModal('modalAssociarDisciplina');
        }
    });
</script>