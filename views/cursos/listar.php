<!-- Filtros e Busca -->
<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <div class="flex flex-col space-y-4">
        <!-- Filtros de Status -->
        <div class="flex flex-col md:flex-row md:items-center space-y-2 md:space-y-0 md:space-x-4">
            <label class="text-sm font-medium text-gray-700">Status:</label>
            <div class="flex space-x-2">
                <a href="cursos.php<?php echo isset($modalidade) && $modalidade !== 'todas' ? '?modalidade='.$modalidade : ''; ?><?php echo isset($nivel) && $nivel !== 'todos' ? (isset($modalidade) && $modalidade !== 'todas' ? '&' : '?').'nivel='.$nivel : ''; ?>" class="<?php echo $status === 'todos' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'; ?> px-3 py-1 rounded-md text-sm">Todos</a>
                <a href="cursos.php?status=ativo<?php echo isset($modalidade) && $modalidade !== 'todas' ? '&modalidade='.$modalidade : ''; ?><?php echo isset($nivel) && $nivel !== 'todos' ? '&nivel='.$nivel : ''; ?>" class="<?php echo $status === 'ativo' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?> px-3 py-1 rounded-md text-sm">Ativos</a>
                <a href="cursos.php?status=inativo<?php echo isset($modalidade) && $modalidade !== 'todas' ? '&modalidade='.$modalidade : ''; ?><?php echo isset($nivel) && $nivel !== 'todos' ? '&nivel='.$nivel : ''; ?>" class="<?php echo $status === 'inativo' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800'; ?> px-3 py-1 rounded-md text-sm">Inativos</a>
            </div>
        </div>

        <!-- Filtros Avançados -->
        <form id="filtro-form" action="cursos.php" method="get">
            <?php if (isset($status) && $status !== 'todos'): ?>
            <input type="hidden" name="status" value="<?php echo $status; ?>">
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Filtro por Modalidade -->
                <div>
                    <label for="modalidade" class="block text-sm font-medium text-gray-700 mb-1">Modalidade:</label>
                    <select id="modalidade" name="modalidade" class="form-select text-sm w-full">
                        <option value="todas" <?php echo $modalidade === 'todas' ? 'selected' : ''; ?>>Todas as Modalidades</option>
                        <option value="presencial" <?php echo $modalidade === 'presencial' ? 'selected' : ''; ?>>Presencial</option>
                        <option value="ead" <?php echo $modalidade === 'ead' ? 'selected' : ''; ?>>EAD</option>
                        <option value="hibrido" <?php echo $modalidade === 'hibrido' ? 'selected' : ''; ?>>Híbrido</option>
                    </select>
                </div>

                <!-- Filtro por Nível -->
                <div>
                    <label for="nivel" class="block text-sm font-medium text-gray-700 mb-1">Nível:</label>
                    <select id="nivel" name="nivel" class="form-select text-sm w-full">
                        <option value="todos" <?php echo $nivel === 'todos' ? 'selected' : ''; ?>>Todos os Níveis</option>
                        <option value="graduacao" <?php echo $nivel === 'graduacao' ? 'selected' : ''; ?>>Graduação</option>
                        <option value="pos_graduacao" <?php echo $nivel === 'pos_graduacao' ? 'selected' : ''; ?>>Pós-Graduação</option>
                        <option value="mestrado" <?php echo $nivel === 'mestrado' ? 'selected' : ''; ?>>Mestrado</option>
                        <option value="doutorado" <?php echo $nivel === 'doutorado' ? 'selected' : ''; ?>>Doutorado</option>
                        <option value="tecnico" <?php echo $nivel === 'tecnico' ? 'selected' : ''; ?>>Técnico</option>
                        <option value="extensao" <?php echo $nivel === 'extensao' ? 'selected' : ''; ?>>Extensão</option>
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
            <form action="cursos.php" method="get" class="flex items-center space-x-2 w-full">
                <input type="hidden" name="action" value="buscar">
                <?php if (isset($status) && $status !== 'todos'): ?>
                <input type="hidden" name="status" value="<?php echo $status; ?>">
                <?php endif; ?>
                <?php if (isset($modalidade) && $modalidade !== 'todas'): ?>
                <input type="hidden" name="modalidade" value="<?php echo $modalidade; ?>">
                <?php endif; ?>
                <?php if (isset($nivel) && $nivel !== 'todos'): ?>
                <input type="hidden" name="nivel" value="<?php echo $nivel; ?>">
                <?php endif; ?>
                <select name="campo" class="form-select text-sm">
                    <option value="nome" <?php echo isset($campo) && $campo === 'nome' ? 'selected' : ''; ?>>Nome</option>
                    <option value="codigo" <?php echo isset($campo) && $campo === 'codigo' ? 'selected' : ''; ?>>Código</option>
                    <option value="id_legado" <?php echo isset($campo) && $campo === 'id_legado' ? 'selected' : ''; ?>>ID Legado</option>
                </select>
                <input type="text" name="termo" value="<?php echo isset($termo) ? htmlspecialchars($termo) : ''; ?>" placeholder="Buscar cursos..." class="form-input text-sm flex-grow">
                <button type="submit" class="btn-primary py-2">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Listagem de Cursos -->
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <?php if (empty($cursos)): ?>
    <div class="p-6 text-center text-gray-500">
        <p>Nenhum curso encontrado.</p>
    </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nível</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Modalidade</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duração</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($cursos as $curso): ?>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10">
                                <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                    <span class="text-blue-600 font-bold"><?php echo strtoupper(substr($curso['nome'], 0, 1)); ?></span>
                                </div>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($curso['nome']); ?></div>
                                <div class="text-sm text-gray-500">
                                    <?php
                                    if (isset($curso['area_nome']) && !empty($curso['area_nome'])) {
                                        echo htmlspecialchars($curso['area_nome']);
                                    } else if (!empty($curso['area_id'])) {
                                        echo 'Área ID: ' . $curso['area_id'];
                                    } else {
                                        echo 'Área não definida';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900"><?php echo isset($curso['codigo']) ? htmlspecialchars($curso['codigo']) : 'N/A'; ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">
                            <?php
                            $niveis = [
                                'graduacao' => 'Graduação',
                                'pos_graduacao' => 'Pós-Graduação',
                                'mestrado' => 'Mestrado',
                                'doutorado' => 'Doutorado',
                                'tecnico' => 'Técnico',
                                'extensao' => 'Extensão'
                            ];
                            echo isset($curso['nivel']) ? ($niveis[$curso['nivel']] ?? $curso['nivel']) : 'N/A';
                            ?>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">
                            <?php
                            $modalidades = [
                                'presencial' => 'Presencial',
                                'ead' => 'EAD',
                                'hibrido' => 'Híbrido'
                            ];
                            echo isset($curso['modalidade']) ? ($modalidades[$curso['modalidade']] ?? $curso['modalidade']) : 'N/A';
                            ?>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900"><?php echo isset($curso['duracao_meses']) && $curso['duracao_meses'] ? $curso['duracao_meses'] . ' meses' : 'N/A'; ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo isset($curso['status']) && $curso['status'] === 'ativo' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                            <?php echo isset($curso['status']) && $curso['status'] === 'ativo' ? 'Ativo' : 'Inativo'; ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div class="flex justify-end space-x-2">
                            <a href="cursos.php?action=visualizar&id=<?php echo $curso['id']; ?>" class="text-blue-600 hover:text-blue-900" title="Visualizar">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="cursos.php?action=editar&id=<?php echo $curso['id']; ?>" class="text-indigo-600 hover:text-indigo-900" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="javascript:void(0);" onclick="confirmarExclusao(<?php echo $curso['id']; ?>, '<?php echo addslashes($curso['nome']); ?>')" class="text-red-600 hover:text-red-900" title="Excluir">
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
                    Mostrando <span class="font-medium"><?php echo ($pagina - 1) * $por_pagina + 1; ?></span> a <span class="font-medium"><?php echo min($pagina * $por_pagina, $total_cursos); ?></span> de <span class="font-medium"><?php echo $total_cursos; ?></span> resultados
                </p>
            </div>
            <div>
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                    <?php if ($pagina > 1): ?>
                    <a href="cursos.php?pagina=<?php echo $pagina - 1; ?><?php echo $status !== 'todos' ? '&status=' . $status : ''; ?><?php echo $modalidade !== 'todas' ? '&modalidade=' . $modalidade : ''; ?><?php echo $nivel !== 'todos' ? '&nivel=' . $nivel : ''; ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                        <span class="sr-only">Anterior</span>
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $pagina - 2); $i <= min($total_paginas, $pagina + 2); $i++): ?>
                    <a href="cursos.php?pagina=<?php echo $i; ?><?php echo $status !== 'todos' ? '&status=' . $status : ''; ?><?php echo $modalidade !== 'todas' ? '&modalidade=' . $modalidade : ''; ?><?php echo $nivel !== 'todos' ? '&nivel=' . $nivel : ''; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 <?php echo $i === $pagina ? 'bg-blue-50 text-blue-600' : 'bg-white text-gray-500 hover:bg-gray-50'; ?> text-sm font-medium">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>

                    <?php if ($pagina < $total_paginas): ?>
                    <a href="cursos.php?pagina=<?php echo $pagina + 1; ?><?php echo $status !== 'todos' ? '&status=' . $status : ''; ?><?php echo $modalidade !== 'todas' ? '&modalidade=' . $modalidade : ''; ?><?php echo $nivel !== 'todos' ? '&nivel=' . $nivel : ''; ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
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
                                Tem certeza que deseja excluir este curso?
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
        document.getElementById('modal-message').textContent = `Tem certeza que deseja excluir o curso "${nome}"?`;
        document.getElementById('btn-confirmar-exclusao').href = `cursos.php?action=excluir&id=${id}`;
        document.getElementById('modal-exclusao').classList.remove('hidden');
    }

    function fecharModal() {
        document.getElementById('modal-exclusao').classList.add('hidden');
    }
</script>
