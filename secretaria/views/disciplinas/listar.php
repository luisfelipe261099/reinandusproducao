<!-- Dashboard de Disciplinas -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <!-- Total de Disciplinas -->
    <div class="bg-white rounded-xl shadow-sm p-4">
        <div class="flex items-center">
            <div class="flex-shrink-0 bg-blue-100 rounded-full p-3">
                <i class="fas fa-book text-blue-600 text-xl"></i>
            </div>
            <div class="ml-4">
                <h2 class="text-sm font-medium text-gray-500">Total de Disciplinas</h2>
                <p class="text-2xl font-bold text-gray-800"><?php echo $total_disciplinas ?? 0; ?></p>
            </div>
        </div>
        <div class="mt-3">
            <a href="disciplinas.php?action=listar&status=todos" class="text-sm text-blue-600 hover:text-blue-800">Ver todas</a>
        </div>
    </div>

    <!-- Disciplinas Ativas -->
    <div class="bg-white rounded-xl shadow-sm p-4">
        <div class="flex items-center">
            <div class="flex-shrink-0 bg-green-100 rounded-full p-3">
                <i class="fas fa-check-circle text-green-600 text-xl"></i>
            </div>
            <div class="ml-4">
                <h2 class="text-sm font-medium text-gray-500">Disciplinas Ativas</h2>
                <p class="text-2xl font-bold text-gray-800"><?php echo $total_ativas ?? 0; ?></p>
            </div>
        </div>
        <div class="mt-3">
            <a href="disciplinas.php?action=listar&status=ativo" class="text-sm text-green-600 hover:text-green-800">Ver ativas</a>
        </div>
    </div>

    <!-- Disciplinas Inativas -->
    <div class="bg-white rounded-xl shadow-sm p-4">
        <div class="flex items-center">
            <div class="flex-shrink-0 bg-red-100 rounded-full p-3">
                <i class="fas fa-times-circle text-red-600 text-xl"></i>
            </div>
            <div class="ml-4">
                <h2 class="text-sm font-medium text-gray-500">Disciplinas Inativas</h2>
                <p class="text-2xl font-bold text-gray-800"><?php echo $total_inativas ?? 0; ?></p>
            </div>
        </div>
        <div class="mt-3">
            <a href="disciplinas.php?action=listar&status=inativo" class="text-sm text-red-600 hover:text-red-800">Ver inativas</a>
        </div>
    </div>

    <!-- Disciplinas Recentes -->
    <div class="bg-white rounded-xl shadow-sm p-4">
        <div class="flex items-center">
            <div class="flex-shrink-0 bg-purple-100 rounded-full p-3">
                <i class="fas fa-calendar-alt text-purple-600 text-xl"></i>
            </div>
            <div class="ml-4">
                <h2 class="text-sm font-medium text-gray-500">Disciplinas Recentes</h2>
                <p class="text-2xl font-bold text-gray-800"><?php echo $total_recentes ?? 0; ?></p>
            </div>
        </div>
        <div class="mt-3">
            <a href="disciplinas.php?action=listar&ordenar=recentes" class="text-sm text-purple-600 hover:text-purple-800">Ver recentes</a>
        </div>
    </div>
</div>

<!-- Disciplinas Recentes e Cursos Populares -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <!-- Disciplinas Recentes -->
    <div class="md:col-span-2 bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Disciplinas Recentes</h3>
        </div>
        <?php if (empty($disciplinas_recentes)): ?>
        <div class="p-4 text-center text-gray-500">
            <p>Nenhuma disciplina recente encontrada.</p>
        </div>
        <?php else: ?>
        <div class="divide-y divide-gray-200">
            <?php foreach ($disciplinas_recentes as $disciplina): ?>
            <div class="p-4 hover:bg-gray-50">
                <div class="flex justify-between items-start">
                    <div>
                        <h4 class="text-sm font-medium text-gray-900">
                            <a href="disciplinas.php?action=visualizar&id=<?php echo $disciplina['id']; ?>" class="hover:text-blue-600">
                                <?php echo htmlspecialchars($disciplina['nome']); ?>
                            </a>
                        </h4>
                        <p class="text-xs text-gray-500 mt-1">
                            Curso: <?php echo htmlspecialchars($disciplina['curso_nome'] ?? 'Curso não encontrado'); ?>
                        </p>
                        <?php if (!empty($disciplina['professor_nome'])): ?>
                        <p class="text-xs text-gray-500 mt-1">
                            Professor: <?php echo htmlspecialchars($disciplina['professor_nome']); ?>
                        </p>
                        <?php endif; ?>
                    </div>
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                        <?php echo $disciplina['status'] === 'ativo' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                        <?php echo $disciplina['status'] === 'ativo' ? 'Ativo' : 'Inativo'; ?>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Cursos com Mais Disciplinas -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Cursos com Mais Disciplinas</h3>
        </div>
        <?php if (empty($cursos_populares)): ?>
        <div class="p-4 text-center text-gray-500">
            <p>Nenhum dado disponível</p>
        </div>
        <?php else: ?>
        <div class="p-4 space-y-4">
            <?php foreach ($cursos_populares as $curso): ?>
            <div>
                <div class="flex justify-between items-center mb-1">
                    <span class="text-sm font-medium text-gray-600 truncate" title="<?php echo htmlspecialchars($curso['nome']); ?>">
                        <?php echo htmlspecialchars($curso['nome']); ?>
                    </span>
                    <span class="text-sm font-medium text-gray-900"><?php echo $curso['total']; ?></span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2.5">
                    <div class="bg-indigo-600 h-2.5 rounded-full" style="width: <?php echo $curso['porcentagem']; ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Filtros e Busca -->
<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <div class="flex flex-col space-y-4">
        <!-- Filtros de Status -->
        <div class="flex flex-col md:flex-row md:items-center space-y-2 md:space-y-0 md:space-x-4">
            <label class="text-sm font-medium text-gray-700">Status:</label>
            <div class="flex flex-wrap gap-2">
                <a href="disciplinas.php?action=listar&status=todos<?php echo isset($curso_id) ? '&curso_id='.$curso_id : ''; ?>" class="<?php echo $status === 'todos' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'; ?> px-3 py-1 rounded-md text-sm">Todos</a>
                <a href="disciplinas.php?action=listar&status=ativo<?php echo isset($curso_id) ? '&curso_id='.$curso_id : ''; ?>" class="<?php echo $status === 'ativo' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?> px-3 py-1 rounded-md text-sm">Ativos</a>
                <a href="disciplinas.php?action=listar&status=inativo<?php echo isset($curso_id) ? '&curso_id='.$curso_id : ''; ?>" class="<?php echo $status === 'inativo' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800'; ?> px-3 py-1 rounded-md text-sm">Inativos</a>
            </div>
        </div>

        <!-- Filtros Avançados -->
        <form id="filtro-form" action="disciplinas.php" method="get">
            <input type="hidden" name="action" value="listar">
            <?php if (isset($status) && $status !== 'todos'): ?>
            <input type="hidden" name="status" value="<?php echo $status; ?>">
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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

                <!-- Busca -->
                <div>
                    <label for="termo" class="block text-sm font-medium text-gray-700 mb-1">Buscar:</label>
                    <div class="flex items-center space-x-2">
                        <select name="campo" class="form-select text-sm">
                            <option value="nome" <?php echo isset($campo) && $campo === 'nome' ? 'selected' : ''; ?>>Nome</option>
                            <option value="codigo" <?php echo isset($campo) && $campo === 'codigo' ? 'selected' : ''; ?>>Código</option>
                            <option value="id_legado" <?php echo isset($campo) && $campo === 'id_legado' ? 'selected' : ''; ?>>ID Legado</option>
                        </select>
                        <input type="text" name="termo" value="<?php echo isset($termo) ? htmlspecialchars($termo) : ''; ?>" placeholder="Buscar disciplinas..." class="form-input text-sm flex-grow">
                    </div>
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
            <form action="disciplinas.php" method="get" class="flex items-center space-x-2 w-full">
                <input type="hidden" name="action" value="buscar">
                <?php if (isset($status) && $status !== 'todos'): ?>
                <input type="hidden" name="status" value="<?php echo $status; ?>">
                <?php endif; ?>
                <?php if (isset($curso_id) && $curso_id): ?>
                <input type="hidden" name="curso_id" value="<?php echo $curso_id; ?>">
                <?php endif; ?>
                <select name="campo" class="form-select text-sm">
                    <option value="nome" <?php echo isset($campo) && $campo === 'nome' ? 'selected' : ''; ?>>Nome</option>
                    <option value="codigo" <?php echo isset($campo) && $campo === 'codigo' ? 'selected' : ''; ?>>Código</option>
                    <option value="id_legado" <?php echo isset($campo) && $campo === 'id_legado' ? 'selected' : ''; ?>>ID Legado</option>
                </select>
                <input type="text" name="termo" value="<?php echo isset($termo) ? htmlspecialchars($termo) : ''; ?>" placeholder="Buscar disciplinas..." class="form-input text-sm flex-grow">
                <button type="submit" class="btn-primary py-2">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Listagem de Disciplinas -->
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <?php
    // Log para depuração
    error_log("View - Total de disciplinas: " . (is_array($disciplinas) ? count($disciplinas) : 'não é um array'));

    if (empty($disciplinas)):
    ?>
    <div class="p-6 text-center text-gray-500">
        <p>Nenhuma disciplina encontrada.</p>
        <p class="mt-2">
            <a href="disciplinas.php?action=nova" class="text-blue-600 hover:text-blue-800">
                <i class="fas fa-plus mr-1"></i> Adicionar Nova Disciplina
            </a>
        </p>
        <!-- Mensagem de depuração -->
        <p class="mt-4 text-xs text-gray-400">
            Verifique se existem disciplinas cadastradas no banco de dados.
            Se você acabou de adicionar disciplinas, tente atualizar a página.
        </p>
    </div>
    <?php else: ?>
    <?php if ($status === 'todos' && empty($curso_id)): ?>
    <div class="p-4 bg-blue-50 text-blue-700 border-l-4 border-blue-500">
        <p><i class="fas fa-info-circle mr-2"></i> Exibindo as primeiras 200 disciplinas. Use os filtros acima para refinar sua busca.</p>
    </div>
    <?php endif; ?>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Curso</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Professor</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Carga Horária</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($disciplinas as $disciplina): ?>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">
                            <a href="disciplinas.php?action=visualizar&id=<?php echo $disciplina['id']; ?>" class="hover:text-blue-600">
                                <?php echo htmlspecialchars($disciplina['nome']); ?>
                            </a>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-500">
                            <?php echo !empty($disciplina['codigo']) ? htmlspecialchars($disciplina['codigo']) : '-'; ?>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">
                            <?php if (isset($disciplina['curso_nome'])): ?>
                            <a href="cursos.php?action=visualizar&id=<?php echo $disciplina['curso_id']; ?>" class="hover:text-blue-600">
                                <?php echo htmlspecialchars($disciplina['curso_nome']); ?>
                            </a>
                            <?php else: ?>
                            <span class="text-gray-500">Curso não encontrado</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">
                            <?php if (!empty($disciplina['professor_nome'])): ?>
                            <a href="professores.php?action=visualizar&id=<?php echo $disciplina['professor_id']; ?>" class="hover:text-blue-600">
                                <?php echo htmlspecialchars($disciplina['professor_nome']); ?>
                            </a>
                            <?php else: ?>
                            <span class="text-gray-500">-</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">
                            <?php echo !empty($disciplina['carga_horaria']) ? $disciplina['carga_horaria'] . 'h' : '-'; ?>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                            <?php echo $disciplina['status'] === 'ativo' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                            <?php echo $disciplina['status'] === 'ativo' ? 'Ativo' : 'Inativo'; ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div class="flex justify-end space-x-2">
                            <a href="disciplinas.php?action=visualizar&id=<?php echo $disciplina['id']; ?>" class="text-blue-600 hover:text-blue-900" title="Visualizar">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="disciplinas.php?action=editar&id=<?php echo $disciplina['id']; ?>" class="text-indigo-600 hover:text-indigo-900" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="javascript:void(0);" onclick="confirmarExclusao(<?php echo $disciplina['id']; ?>)" class="text-red-600 hover:text-red-900" title="Excluir">
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
                    Mostrando <span class="font-medium"><?php echo ($pagina - 1) * $por_pagina + 1; ?></span> a <span class="font-medium"><?php echo min($pagina * $por_pagina, $total_disciplinas); ?></span> de <span class="font-medium"><?php echo $total_disciplinas; ?></span> resultados
                </p>
            </div>
            <div>
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                    <?php if ($pagina > 1): ?>
                    <a href="disciplinas.php?action=listar&pagina=<?php echo $pagina - 1; ?><?php echo $status !== 'todos' ? '&status=' . $status : ''; ?><?php echo $curso_id ? '&curso_id=' . $curso_id : ''; ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                        <span class="sr-only">Anterior</span>
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $pagina - 2); $i <= min($total_paginas, $pagina + 2); $i++): ?>
                    <a href="disciplinas.php?action=listar&pagina=<?php echo $i; ?><?php echo $status !== 'todos' ? '&status=' . $status : ''; ?><?php echo $curso_id ? '&curso_id=' . $curso_id : ''; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 <?php echo $i === $pagina ? 'bg-blue-50 text-blue-600' : 'bg-white text-gray-500 hover:bg-gray-50'; ?> text-sm font-medium">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>

                    <?php if ($pagina < $total_paginas): ?>
                    <a href="disciplinas.php?action=listar&pagina=<?php echo $pagina + 1; ?><?php echo $status !== 'todos' ? '&status=' . $status : ''; ?><?php echo $curso_id ? '&curso_id=' . $curso_id : ''; ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
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
