<!-- Filtros e Busca -->
<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <form method="GET" action="notas.php" class="space-y-4">
        <input type="hidden" name="action" value="listar">

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Campo de Busca -->
            <div class="lg:col-span-2">
                <label for="termo" class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                <div class="flex">
                    <select name="campo" class="rounded-l-md border-gray-300 border-r-0 focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                        <option value="aluno_nome" <?php echo $campo === 'aluno_nome' ? 'selected' : ''; ?>>Nome do Aluno</option>
                        <option value="aluno_cpf" <?php echo $campo === 'aluno_cpf' ? 'selected' : ''; ?>>CPF do Aluno</option>
                        <option value="disciplina" <?php echo $campo === 'disciplina' ? 'selected' : ''; ?>>Disciplina</option>
                        <option value="curso" <?php echo $campo === 'curso' ? 'selected' : ''; ?>>Curso</option>
                        <option value="turma" <?php echo $campo === 'turma' ? 'selected' : ''; ?>>Turma</option>
                    </select>
                    <input type="text" id="termo" name="termo" value="<?php echo htmlspecialchars($termo); ?>"
                           placeholder="Digite sua busca..."
                           class="flex-1 rounded-r-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                </div>
            </div>

            <!-- Filtro por Curso -->
            <div>
                <label for="curso_id" class="block text-sm font-medium text-gray-700 mb-1">Curso</label>
                <select id="curso_id" name="curso_id" onchange="carregarTurmas(this.value, 'turma_id'); carregarDisciplinas(this.value, 'disciplina_id');" class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    <option value="">Todos os cursos</option>
                    <?php foreach ($cursos as $curso): ?>
                    <option value="<?php echo $curso['id']; ?>" <?php echo $curso_id == $curso['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($curso['nome']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Filtro por Situação -->
            <div>
                <label for="situacao" class="block text-sm font-medium text-gray-700 mb-1">Situação</label>
                <select id="situacao" name="situacao" class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    <option value="">Todas as situações</option>
                    <option value="cursando" <?php echo $situacao === 'cursando' ? 'selected' : ''; ?>>Cursando</option>
                    <option value="aprovado" <?php echo $situacao === 'aprovado' ? 'selected' : ''; ?>>Aprovado</option>
                    <option value="reprovado" <?php echo $situacao === 'reprovado' ? 'selected' : ''; ?>>Reprovado</option>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Filtro por Turma -->
            <div>
                <label for="turma_id" class="block text-sm font-medium text-gray-700 mb-1">Turma</label>
                <select id="turma_id" name="turma_id" class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    <option value="">Todas as turmas</option>
                    <?php foreach ($turmas as $turma): ?>
                    <option value="<?php echo $turma['id']; ?>" <?php echo $turma_id == $turma['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($turma['nome']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Filtro por Disciplina -->
            <div>
                <label for="disciplina_id" class="block text-sm font-medium text-gray-700 mb-1">Disciplina</label>
                <select id="disciplina_id" name="disciplina_id" class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    <option value="">Todas as disciplinas</option>
                    <?php foreach ($disciplinas as $disciplina): ?>
                    <option value="<?php echo $disciplina['id']; ?>" <?php echo $disciplina_id == $disciplina['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($disciplina['nome']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center space-y-2 sm:space-y-0">
            <div class="flex space-x-2">
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-search mr-2"></i>
                    Buscar
                </button>
                <a href="notas.php" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-times mr-2"></i>
                    Limpar
                </a>
                <a href="notas.php?action=lancar" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    <i class="fas fa-plus mr-2"></i>
                    Lançar Notas
                </a>
            </div>

            <div class="text-sm text-gray-500">
                <?php echo number_format($total_notas); ?> nota(s) encontrada(s)
            </div>
        </div>
    </form>
</div>

<!-- Listagem de Notas -->
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <?php if (empty($notas)): ?>
    <div class="p-6 text-center text-gray-500">
        <div class="mb-4">
            <i class="fas fa-clipboard-list text-4xl text-gray-300"></i>
        </div>
        <p class="text-lg font-medium">Nenhuma nota encontrada</p>
        <p class="mt-2">Tente ajustar os filtros ou adicionar novas notas ao sistema.</p>
    </div>
    <?php else: ?>

    <!-- Layout para Desktop -->
    <div class="hidden lg:block">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aluno</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Disciplina</th>
                        <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Nota</th>
                        <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Frequência</th>
                        <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Situação</th>
                        <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                        <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-32">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($notas as $nota): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <div>
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($nota['aluno_nome']); ?></div>
                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($nota['aluno_cpf']); ?></div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-sm text-gray-900"><?php echo htmlspecialchars($nota['disciplina_nome']); ?></div>
                            <div class="text-xs text-gray-500"><?php echo htmlspecialchars($nota['curso_nome']); ?></div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <?php if ($nota['nota'] !== null): ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                <?php
                                if ($nota['nota'] >= 7) echo 'bg-green-100 text-green-800';
                                elseif ($nota['nota'] >= 5) echo 'bg-yellow-100 text-yellow-800';
                                else echo 'bg-red-100 text-red-800';
                                ?>">
                                <?php echo number_format($nota['nota'], 1); ?>
                            </span>
                            <?php else: ?>
                            <span class="text-gray-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <?php if ($nota['frequencia'] !== null): ?>
                            <span class="text-sm text-gray-900"><?php echo number_format($nota['frequencia'], 1); ?>%</span>
                            <?php else: ?>
                            <span class="text-gray-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                <?php
                                switch ($nota['situacao']) {
                                    case 'aprovado':
                                        echo 'bg-green-100 text-green-800';
                                        break;
                                    case 'reprovado':
                                        echo 'bg-red-100 text-red-800';
                                        break;
                                    case 'cursando':
                                    default:
                                        echo 'bg-blue-100 text-blue-800';
                                }
                                ?>">
                                <?php echo ucfirst($nota['situacao']); ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="text-sm text-gray-900"><?php echo date('d/m/Y', strtotime($nota['data_lancamento'])); ?></div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-center space-x-2">
                                <a href="notas.php?action=editar&id=<?php echo $nota['id']; ?>" class="inline-flex items-center px-2 py-1 border border-transparent text-xs leading-4 font-medium rounded text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button onclick="confirmarExclusao(<?php echo $nota['id']; ?>, '<?php echo addslashes($nota['aluno_nome']); ?>')" class="inline-flex items-center px-2 py-1 border border-transparent text-xs leading-4 font-medium rounded text-red-700 bg-red-100 hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500" title="Excluir">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Layout para Mobile/Tablet -->
    <div class="lg:hidden">
        <div class="divide-y divide-gray-200">
            <?php foreach ($notas as $nota): ?>
            <div class="p-4 hover:bg-gray-50">
                <div class="flex items-center justify-between">
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-gray-900 truncate"><?php echo htmlspecialchars($nota['aluno_nome']); ?></div>
                        <div class="text-sm text-gray-500 truncate"><?php echo htmlspecialchars($nota['disciplina_nome']); ?></div>
                        <div class="text-xs text-gray-400"><?php echo htmlspecialchars($nota['curso_nome']); ?></div>
                    </div>
                    <div class="flex items-center space-x-2 ml-4">
                        <?php if ($nota['nota'] !== null): ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            <?php
                            if ($nota['nota'] >= 7) echo 'bg-green-100 text-green-800';
                            elseif ($nota['nota'] >= 5) echo 'bg-yellow-100 text-yellow-800';
                            else echo 'bg-red-100 text-red-800';
                            ?>">
                            <?php echo number_format($nota['nota'], 1); ?>
                        </span>
                        <?php endif; ?>

                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                            <?php
                            switch ($nota['situacao']) {
                                case 'aprovado':
                                    echo 'bg-green-100 text-green-800';
                                    break;
                                case 'reprovado':
                                    echo 'bg-red-100 text-red-800';
                                    break;
                                case 'cursando':
                                default:
                                    echo 'bg-blue-100 text-blue-800';
                            }
                            ?>">
                            <?php echo ucfirst($nota['situacao']); ?>
                        </span>
                    </div>
                </div>

                <div class="mt-3 flex items-center justify-between">
                    <div class="flex items-center space-x-4 text-xs text-gray-500">
                        <?php if ($nota['frequencia'] !== null): ?>
                        <span>
                            <i class="fas fa-percentage mr-1"></i>
                            <?php echo number_format($nota['frequencia'], 1); ?>%
                        </span>
                        <?php endif; ?>
                        <span>
                            <i class="fas fa-calendar mr-1"></i>
                            <?php echo date('d/m/Y', strtotime($nota['data_lancamento'])); ?>
                        </span>
                    </div>

                    <div class="flex space-x-1">
                        <a href="notas.php?action=editar&id=<?php echo $nota['id']; ?>" class="inline-flex items-center p-2 border border-transparent rounded-full text-indigo-600 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" title="Editar">
                            <i class="fas fa-edit text-xs"></i>
                        </a>
                        <button onclick="confirmarExclusao(<?php echo $nota['id']; ?>, '<?php echo addslashes($nota['aluno_nome']); ?>')" class="inline-flex items-center p-2 border border-transparent rounded-full text-red-600 bg-red-100 hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500" title="Excluir">
                            <i class="fas fa-trash text-xs"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Paginação -->
<?php if ($total_paginas > 1): ?>
<div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6 rounded-b-xl mt-6">
    <div class="flex-1 flex justify-between sm:hidden">
        <?php if ($pagina > 1): ?>
        <a href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina - 1])); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
            Anterior
        </a>
        <?php endif; ?>

        <?php if ($pagina < $total_paginas): ?>
        <a href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina + 1])); ?>" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
            Próxima
        </a>
        <?php endif; ?>
    </div>

    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
        <div>
            <p class="text-sm text-gray-700">
                Mostrando
                <span class="font-medium"><?php echo ($pagina - 1) * $por_pagina + 1; ?></span>
                até
                <span class="font-medium"><?php echo min($pagina * $por_pagina, $total_notas); ?></span>
                de
                <span class="font-medium"><?php echo number_format($total_notas); ?></span>
                resultados
            </p>
        </div>
        <div>
            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                <?php if ($pagina > 1): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina - 1])); ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <?php endif; ?>

                <?php
                $inicio = max(1, $pagina - 2);
                $fim = min($total_paginas, $pagina + 2);

                for ($i = $inicio; $i <= $fim; $i++):
                ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $i])); ?>" class="relative inline-flex items-center px-4 py-2 border text-sm font-medium <?php echo $i == $pagina ? 'z-10 bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'; ?>">
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>

                <?php if ($pagina < $total_paginas): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina + 1])); ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                    <i class="fas fa-chevron-right"></i>
                </a>
                <?php endif; ?>
            </nav>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal de Confirmação de Exclusão -->
<div id="modal-exclusao" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <i class="fas fa-exclamation-triangle text-red-600"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mt-2">Confirmar Exclusão</h3>
            <div class="mt-2 px-7 py-3">
                <p id="modal-message" class="text-sm text-gray-500"></p>
            </div>
            <div class="items-center px-4 py-3">
                <button onclick="fecharModal()" class="px-4 py-2 bg-gray-500 text-white text-base font-medium rounded-md w-24 mr-2 hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-300">
                    Cancelar
                </button>
                <a id="btn-confirmar-exclusao" href="#" class="px-4 py-2 bg-red-600 text-white text-base font-medium rounded-md w-24 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-300">
                    Excluir
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function confirmarExclusao(id, nome) {
    document.getElementById('modal-message').textContent = `Tem certeza que deseja excluir a nota de "${nome}"?`;
    document.getElementById('btn-confirmar-exclusao').href = `notas.php?action=excluir&id=${id}`;
    document.getElementById('modal-exclusao').classList.remove('hidden');
}

function fecharModal() {
    document.getElementById('modal-exclusao').classList.add('hidden');
}
</script>
