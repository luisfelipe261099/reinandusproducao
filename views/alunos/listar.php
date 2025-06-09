<!-- Filtros e Busca -->
<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <div class="flex flex-col space-y-4">
        <!-- Filtros de Status -->
        <div class="flex flex-col md:flex-row md:items-center space-y-2 md:space-y-0 md:space-x-4">
            <label class="text-sm font-medium text-gray-700">Status:</label>
            <div class="flex space-x-2">
                <a href="alunos.php<?php echo isset($polo_id) ? '?polo_id='.$polo_id : ''; ?><?php echo isset($curso_id) ? (isset($polo_id) ? '&' : '?').'curso_id='.$curso_id : ''; ?><?php echo isset($turma_id) ? (isset($polo_id) || isset($curso_id) ? '&' : '?').'turma_id='.$turma_id : ''; ?>" class="<?php echo $status === 'todos' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'; ?> px-3 py-1 rounded-md text-sm">Todos</a>
                <a href="alunos.php?status=ativo<?php echo isset($polo_id) ? '&polo_id='.$polo_id : ''; ?><?php echo isset($curso_id) ? '&curso_id='.$curso_id : ''; ?><?php echo isset($turma_id) ? '&turma_id='.$turma_id : ''; ?>" class="<?php echo $status === 'ativo' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?> px-3 py-1 rounded-md text-sm">Ativos</a>
                <a href="alunos.php?status=inativo<?php echo isset($polo_id) ? '&polo_id='.$polo_id : ''; ?><?php echo isset($curso_id) ? '&curso_id='.$curso_id : ''; ?><?php echo isset($turma_id) ? '&turma_id='.$turma_id : ''; ?>" class="<?php echo $status === 'inativo' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800'; ?> px-3 py-1 rounded-md text-sm">Inativos</a>
            </div>
        </div>

        <!-- Filtros Avançados -->
        <form id="filtro-form" action="alunos.php" method="get">
            <?php if (isset($status) && $status !== 'todos'): ?>
            <input type="hidden" name="status" value="<?php echo $status; ?>">
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Filtro por Polo -->
                <div>
                    <label for="polo_id" class="block text-sm font-medium text-gray-700 mb-1">Polo:</label>
                    <select id="polo_id" name="polo_id" class="form-select text-sm w-full">
                        <option value="">Todos os Polos</option>
                        <?php foreach ($polos as $polo): ?>
                        <option value="<?php echo $polo['id']; ?>" <?php echo isset($polo_id) && $polo_id == $polo['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($polo['nome']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Filtro por Curso -->
                <div>
                    <label for="curso_id" class="block text-sm font-medium text-gray-700 mb-1">Curso:</label>
                    <select id="curso_id" name="curso_id" class="form-select text-sm w-full">
                        <option value="">Todos os Cursos</option>
                        <?php foreach ($cursos as $curso): ?>
                        <option value="<?php echo $curso['id']; ?>" <?php echo isset($curso_id) && $curso_id == $curso['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($curso['nome']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Filtro por Turma -->
                <div>
                    <label for="turma_id" class="block text-sm font-medium text-gray-700 mb-1">Turma:</label>
                    <select id="turma_id" name="turma_id" class="form-select text-sm w-full">
                        <option value="">Todas as Turmas</option>
                        <?php foreach ($turmas as $turma): ?>
                        <option value="<?php echo $turma['id']; ?>" <?php echo isset($turma_id) && $turma_id == $turma['id'] ? 'selected' : ''; ?>
                                data-curso="<?php echo $turma['curso_id']; ?>">
                            <?php echo htmlspecialchars($turma['nome']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mt-4 flex justify-end">
                <button type="submit" class="btn-primary">
                    <i class="fas fa-filter mr-2"></i> Aplicar Filtros
                </button>
            </div>
        </form>

        <!-- Busca -->
        <div class="flex items-center space-x-2 mt-2">
            <form action="alunos.php" method="get" class="flex items-center space-x-2 w-full">
                <input type="hidden" name="action" value="buscar">
                <?php if (isset($status) && $status !== 'todos'): ?>
                <input type="hidden" name="status" value="<?php echo $status; ?>">
                <?php endif; ?>
                <?php if (isset($polo_id) && $polo_id): ?>
                <input type="hidden" name="polo_id" value="<?php echo $polo_id; ?>">
                <?php endif; ?>
                <?php if (isset($curso_id) && $curso_id): ?>
                <input type="hidden" name="curso_id" value="<?php echo $curso_id; ?>">
                <?php endif; ?>
                <?php if (isset($turma_id) && $turma_id): ?>
                <input type="hidden" name="turma_id" value="<?php echo $turma_id; ?>">
                <?php endif; ?>
                <select name="campo" class="form-select text-sm">
                    <option value="nome" <?php echo isset($campo) && $campo === 'nome' ? 'selected' : ''; ?>>Nome</option>
                    <option value="email" <?php echo isset($campo) && $campo === 'email' ? 'selected' : ''; ?>>E-mail</option>
                    <option value="cpf" <?php echo isset($campo) && $campo === 'cpf' ? 'selected' : ''; ?>>CPF</option>
                    <option value="id_legado" <?php echo isset($campo) && $campo === 'id_legado' ? 'selected' : ''; ?>>ID Legado</option>
                </select>
                <input type="text" name="termo" value="<?php echo isset($termo) ? htmlspecialchars($termo) : ''; ?>" placeholder="Buscar alunos..." class="form-input text-sm flex-grow">
                <button type="submit" class="btn-primary py-2">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
// Filtra as turmas com base no curso selecionado
document.addEventListener('DOMContentLoaded', function() {
    const cursoSelect = document.getElementById('curso_id');
    const turmaSelect = document.getElementById('turma_id');

    if (cursoSelect && turmaSelect) {
        // Adiciona o evento de change para o curso
        cursoSelect.addEventListener('change', function() {
            const cursoId = this.value;
            const turmaOptions = turmaSelect.querySelectorAll('option');

            turmaOptions.forEach(option => {
                if (option.value === '' || !cursoId || option.getAttribute('data-curso') === cursoId) {
                    option.style.display = '';
                } else {
                    option.style.display = 'none';
                }
            });

            // Reset turma selection if current selection is not valid for the selected course
            const currentTurma = turmaSelect.value;
            const currentTurmaOption = turmaSelect.querySelector(`option[value="${currentTurma}"]`);

            if (currentTurma && currentTurmaOption && currentTurmaOption.style.display === 'none') {
                turmaSelect.value = '';
            }
        });

        // Trigger change event to initialize the filter
        cursoSelect.dispatchEvent(new Event('change'));
    }
});
</script>

<!-- Listagem de Alunos -->
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <?php if (empty($alunos)): ?>
    <div class="p-6 text-center text-gray-500">
        <p>Nenhum aluno encontrado.</p>
    </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">E-mail</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CPF</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID Legado</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($alunos as $aluno): ?>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10">
                                <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                    <span class="text-blue-600 font-bold"><?php echo strtoupper(substr($aluno['nome'], 0, 1)); ?></span>
                                </div>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($aluno['nome']); ?></div>
                                <div class="text-sm text-gray-500"><?php echo !empty($aluno['telefone']) ? htmlspecialchars($aluno['telefone']) : 'Não informado'; ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($aluno['email']); ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900"><?php echo formatarCpf($aluno['cpf']); ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900"><?php echo !empty($aluno['id_legado']) ? htmlspecialchars($aluno['id_legado']) : '-'; ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $aluno['status'] === 'ativo' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                            <?php echo $aluno['status'] === 'ativo' ? 'Ativo' : 'Inativo'; ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div class="flex justify-end space-x-2">
                            <a href="alunos.php?action=visualizar&id=<?php echo $aluno['id']; ?>" class="text-blue-600 hover:text-blue-900" title="Visualizar">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="alunos.php?action=editar&id=<?php echo $aluno['id']; ?>" class="text-indigo-600 hover:text-indigo-900" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="documentos_aluno.php?id=<?php echo $aluno['id']; ?>" class="text-purple-600 hover:text-purple-900" title="Ver Documentos Pessoais">
                                <i class="fas fa-id-card"></i>
                            </a>
                            <a href="javascript:void(0);" onclick="confirmarExclusao(<?php echo $aluno['id']; ?>, '<?php echo addslashes($aluno['nome']); ?>')" class="text-red-600 hover:text-red-900" title="Excluir">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Paginação -->
    <?php if (isset($total_paginas) && $total_paginas > 1): ?>
    <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-gray-700">
                    Mostrando <span class="font-medium"><?php echo ($pagina - 1) * $por_pagina + 1; ?></span> a <span class="font-medium"><?php echo min($pagina * $por_pagina, $total_alunos); ?></span> de <span class="font-medium"><?php echo $total_alunos; ?></span> resultados
                </p>
            </div>
            <div>
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                    <?php if ($pagina > 1): ?>
                    <a href="alunos.php?pagina=<?php echo $pagina - 1; ?><?php echo $status !== 'todos' ? '&status=' . $status : ''; ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                        <span class="sr-only">Anterior</span>
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $pagina - 2); $i <= min($total_paginas, $pagina + 2); $i++): ?>
                    <a href="alunos.php?pagina=<?php echo $i; ?><?php echo $status !== 'todos' ? '&status=' . $status : ''; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 <?php echo $i === $pagina ? 'bg-blue-50 text-blue-600' : 'bg-white text-gray-500 hover:bg-gray-50'; ?> text-sm font-medium">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>

                    <?php if ($pagina < $total_paginas): ?>
                    <a href="alunos.php?pagina=<?php echo $pagina + 1; ?><?php echo $status !== 'todos' ? '&status=' . $status : ''; ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                        <span class="sr-only">Próxima</span>
                        <i class="fas fa-chevron-right"></i>
                    </a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Modal de Confirmação de Exclusão -->
<div id="modal-exclusao" class="fixed z-10 inset-0 overflow-y-auto hidden">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                        <i class="fas fa-exclamation-triangle text-red-600"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            Confirmar Exclusão
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500" id="modal-message">
                                Tem certeza que deseja excluir este aluno?
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <a href="#" id="btn-confirmar-exclusao" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                    Confirmar
                </a>
                <button type="button" onclick="fecharModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Cancelar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function confirmarExclusao(id, nome) {
        document.getElementById('modal-message').textContent = `Tem certeza que deseja excluir o aluno "${nome}"?`;
        document.getElementById('btn-confirmar-exclusao').href = `alunos.php?action=excluir&id=${id}`;
        document.getElementById('modal-exclusao').classList.remove('hidden');
    }

    function fecharModal() {
        document.getElementById('modal-exclusao').classList.add('hidden');
    }
</script>
