<?php include 'views/matriculas/dashboard.php'; ?>

<!-- Filtros e Busca -->
<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <div class="flex flex-col space-y-4">
        <!-- Filtros de Status -->
        <div class="flex flex-col md:flex-row md:items-center space-y-2 md:space-y-0 md:space-x-4">
            <label class="text-sm font-medium text-gray-700">Status:</label>
            <div class="flex flex-wrap gap-2">
                <a href="matriculas.php<?php echo isset($aluno_id) ? '?aluno_id='.$aluno_id : ''; ?><?php echo isset($curso_id) ? (isset($aluno_id) ? '&' : '?').'curso_id='.$curso_id : ''; ?><?php echo isset($turma_id) ? (isset($aluno_id) || isset($curso_id) ? '&' : '?').'turma_id='.$turma_id : ''; ?><?php echo isset($polo_id) ? (isset($aluno_id) || isset($curso_id) || isset($turma_id) ? '&' : '?').'polo_id='.$polo_id : ''; ?>" class="<?php echo $status === 'todos' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'; ?> px-3 py-1 rounded-md text-sm">Todos</a>
                <a href="matriculas.php?status=ativo<?php echo isset($aluno_id) ? '&aluno_id='.$aluno_id : ''; ?><?php echo isset($curso_id) ? '&curso_id='.$curso_id : ''; ?><?php echo isset($turma_id) ? '&turma_id='.$turma_id : ''; ?><?php echo isset($polo_id) ? '&polo_id='.$polo_id : ''; ?>" class="<?php echo $status === 'ativo' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?> px-3 py-1 rounded-md text-sm">Ativos</a>
                <a href="matriculas.php?status=pendente<?php echo isset($aluno_id) ? '&aluno_id='.$aluno_id : ''; ?><?php echo isset($curso_id) ? '&curso_id='.$curso_id : ''; ?><?php echo isset($turma_id) ? '&turma_id='.$turma_id : ''; ?><?php echo isset($polo_id) ? '&polo_id='.$polo_id : ''; ?>" class="<?php echo $status === 'pendente' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800'; ?> px-3 py-1 rounded-md text-sm">Pendentes</a>
                <a href="matriculas.php?status=concluido<?php echo isset($aluno_id) ? '&aluno_id='.$aluno_id : ''; ?><?php echo isset($curso_id) ? '&curso_id='.$curso_id : ''; ?><?php echo isset($turma_id) ? '&turma_id='.$turma_id : ''; ?><?php echo isset($polo_id) ? '&polo_id='.$polo_id : ''; ?>" class="<?php echo $status === 'concluido' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'; ?> px-3 py-1 rounded-md text-sm">Concluídos</a>
                <a href="matriculas.php?status=cancelado<?php echo isset($aluno_id) ? '&aluno_id='.$aluno_id : ''; ?><?php echo isset($curso_id) ? '&curso_id='.$curso_id : ''; ?><?php echo isset($turma_id) ? '&turma_id='.$turma_id : ''; ?><?php echo isset($polo_id) ? '&polo_id='.$polo_id : ''; ?>" class="<?php echo $status === 'cancelado' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800'; ?> px-3 py-1 rounded-md text-sm">Cancelados</a>
                <a href="matriculas.php?status=trancado<?php echo isset($aluno_id) ? '&aluno_id='.$aluno_id : ''; ?><?php echo isset($curso_id) ? '&curso_id='.$curso_id : ''; ?><?php echo isset($turma_id) ? '&turma_id='.$turma_id : ''; ?><?php echo isset($polo_id) ? '&polo_id='.$polo_id : ''; ?>" class="<?php echo $status === 'trancado' ? 'bg-gray-300 text-gray-800' : 'bg-gray-100 text-gray-800'; ?> px-3 py-1 rounded-md text-sm">Trancados</a>
            </div>
        </div>

        <!-- Filtros Avançados -->
        <form id="filtro-form" action="matriculas.php" method="get">
            <?php if (isset($status) && $status !== 'todos'): ?>
            <input type="hidden" name="status" value="<?php echo $status; ?>">
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Filtro por Aluno -->
                <div>
                    <label for="aluno_id" class="block text-sm font-medium text-gray-700 mb-1">Aluno:</label>
                    <select id="aluno_id" name="aluno_id" class="form-select text-sm w-full">
                        <option value="">Todos os Alunos</option>
                        <?php foreach ($alunos as $aluno): ?>
                        <option value="<?php echo $aluno['id']; ?>" <?php echo isset($aluno_id) && $aluno_id == $aluno['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($aluno['nome']); ?>
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
                        <option value="<?php echo $turma['id']; ?>" <?php echo isset($turma_id) && $turma_id == $turma['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($turma['nome']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

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
            </div>

            <div class="mt-4 flex justify-end">
                <button type="submit" class="btn-primary">
                    <i class="fas fa-filter mr-2"></i> Aplicar Filtros
                </button>
            </div>
        </form>

        <!-- Busca -->
        <div class="flex items-center space-x-2 mt-2">
            <form action="matriculas.php" method="get" class="flex items-center space-x-2 w-full">
                <input type="hidden" name="action" value="buscar">
                <?php if (isset($status) && $status !== 'todos'): ?>
                <input type="hidden" name="status" value="<?php echo $status; ?>">
                <?php endif; ?>
                <?php if (isset($aluno_id) && $aluno_id): ?>
                <input type="hidden" name="aluno_id" value="<?php echo $aluno_id; ?>">
                <?php endif; ?>
                <?php if (isset($curso_id) && $curso_id): ?>
                <input type="hidden" name="curso_id" value="<?php echo $curso_id; ?>">
                <?php endif; ?>
                <?php if (isset($turma_id) && $turma_id): ?>
                <input type="hidden" name="turma_id" value="<?php echo $turma_id; ?>">
                <?php endif; ?>
                <?php if (isset($polo_id) && $polo_id): ?>
                <input type="hidden" name="polo_id" value="<?php echo $polo_id; ?>">
                <?php endif; ?>
                <select name="campo" class="form-select text-sm">
                    <option value="aluno" <?php echo isset($campo) && $campo === 'aluno' ? 'selected' : ''; ?>>Aluno</option>
                    <option value="curso" <?php echo isset($campo) && $campo === 'curso' ? 'selected' : ''; ?>>Curso</option>
                    <option value="turma" <?php echo isset($campo) && $campo === 'turma' ? 'selected' : ''; ?>>Turma</option>
                    <option value="id_legado" <?php echo isset($campo) && $campo === 'id_legado' ? 'selected' : ''; ?>>ID Legado</option>
                </select>
                <input type="text" name="termo" value="<?php echo isset($termo) ? htmlspecialchars($termo) : ''; ?>" placeholder="Buscar matrículas..." class="form-input text-sm flex-grow">
                <button type="submit" class="btn-primary py-2">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Listagem de Matrículas -->
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <?php if (empty($matriculas)): ?>
    <div class="p-6 text-center text-gray-500">
        <p>Nenhuma matrícula encontrada.</p>
        <p class="mt-2">
            <a href="matriculas.php?action=nova" class="text-blue-600 hover:text-blue-800">
                <i class="fas fa-plus mr-1"></i> Adicionar Nova Matrícula
            </a>
        </p>
    </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aluno</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Curso/Polo</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Turma</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data/Status</th>
                    <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-32">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($matriculas as $matricula): ?>
                <tr class="hover:bg-gray-50">
                    <!-- Aluno -->
                    <td class="px-4 py-3">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-8 w-8">
                                <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center">
                                    <span class="text-blue-600 font-bold text-sm"><?php echo isset($matricula['aluno_nome']) ? strtoupper(substr($matricula['aluno_nome'], 0, 1)) : '?'; ?></span>
                                </div>
                            </div>
                            <div class="ml-3">
                                <div class="text-sm font-medium text-gray-900">
                                    <?php if (isset($matricula['aluno_nome'])): ?>
                                    <a href="alunos.php?action=visualizar&id=<?php echo $matricula['aluno_id']; ?>" class="hover:text-blue-600">
                                        <?php echo htmlspecialchars($matricula['aluno_nome']); ?>
                                    </a>
                                    <?php else: ?>
                                    <span class="text-gray-500">Aluno não encontrado</span>
                                    <?php endif; ?>
                                </div>
                                <?php if (isset($matricula['aluno_email']) && !empty($matricula['aluno_email'])): ?>
                                <div class="text-xs text-gray-500">
                                    <?php echo htmlspecialchars($matricula['aluno_email']); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>

                    <!-- Curso/Polo -->
                    <td class="px-4 py-3">
                        <div class="text-sm font-medium text-gray-900">
                            <?php if (isset($matricula['curso_nome'])): ?>
                            <a href="cursos.php?action=visualizar&id=<?php echo $matricula['curso_id']; ?>" class="hover:text-blue-600">
                                <?php echo htmlspecialchars($matricula['curso_nome']); ?>
                            </a>
                            <?php else: ?>
                            <span class="text-gray-500">Curso não encontrado</span>
                            <?php endif; ?>
                        </div>
                        <?php if (isset($matricula['polo_nome']) && !empty($matricula['polo_nome'])): ?>
                        <div class="text-xs text-gray-500">
                            <i class="fas fa-map-marker-alt mr-1"></i>
                            <a href="polos.php?action=visualizar&id=<?php echo $matricula['polo_id']; ?>" class="hover:text-blue-600">
                                <?php echo htmlspecialchars($matricula['polo_nome']); ?>
                            </a>
                        </div>
                        <?php endif; ?>
                    </td>

                    <!-- Turma -->
                    <td class="px-4 py-3">
                        <div class="text-sm text-gray-900">
                            <?php if (isset($matricula['turma_nome']) && !empty($matricula['turma_nome'])): ?>
                            <a href="turmas.php?action=visualizar&id=<?php echo $matricula['turma_id']; ?>" class="hover:text-blue-600">
                                <?php echo htmlspecialchars($matricula['turma_nome']); ?>
                            </a>
                            <?php else: ?>
                            <span class="text-gray-500 text-xs">Sem turma</span>
                            <?php endif; ?>
                        </div>
                    </td>

                    <!-- Data/Status -->
                    <td class="px-4 py-3">
                        <div class="text-sm text-gray-900 mb-1">
                            <?php
                            if (isset($matricula['data_inicio']) && !empty($matricula['data_inicio'])) {
                                try {
                                    echo date('d/m/Y', strtotime($matricula['data_inicio']));
                                } catch (Exception $e) {
                                    echo 'Data inválida';
                                }
                            } else {
                                echo 'Não definida';
                            }
                            ?>
                        </div>
                        <span class="px-2 inline-flex text-xs leading-4 font-semibold rounded-full
                            <?php
                            if (isset($matricula['status'])) {
                                switch ($matricula['status']) {
                                    case 'ativo':
                                        echo 'bg-green-100 text-green-800';
                                        break;
                                    case 'pendente':
                                        echo 'bg-yellow-100 text-yellow-800';
                                        break;
                                    case 'concluido':
                                        echo 'bg-blue-100 text-blue-800';
                                        break;
                                    case 'cancelado':
                                        echo 'bg-red-100 text-red-800';
                                        break;
                                    case 'trancado':
                                        echo 'bg-gray-300 text-gray-800';
                                        break;
                                    default:
                                        echo 'bg-gray-100 text-gray-800';
                                }
                            } else {
                                echo 'bg-gray-100 text-gray-800';
                            }
                            ?>">
                            <?php
                            if (isset($matricula['status'])) {
                                switch ($matricula['status']) {
                                    case 'ativo':
                                        echo 'Ativo';
                                        break;
                                    case 'pendente':
                                        echo 'Pendente';
                                        break;
                                    case 'concluido':
                                        echo 'Concluído';
                                        break;
                                    case 'cancelado':
                                        echo 'Cancelado';
                                        break;
                                    case 'trancado':
                                        echo 'Trancado';
                                        break;
                                    default:
                                        echo ucfirst($matricula['status']);
                                }
                            } else {
                                echo 'N/A';
                            }
                            ?>
                        </span>
                    </td>

                    <!-- Ações -->
                    <td class="px-4 py-3 text-center">
                        <div class="flex justify-center space-x-2">
                            <a href="matriculas.php?action=visualizar&id=<?php echo $matricula['id']; ?>"
                               class="inline-flex items-center px-2 py-1 border border-transparent text-xs leading-4 font-medium rounded text-blue-700 bg-blue-100 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                               title="Visualizar">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="matriculas.php?action=editar&id=<?php echo $matricula['id']; ?>"
                               class="inline-flex items-center px-2 py-1 border border-transparent text-xs leading-4 font-medium rounded text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                               title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button onclick="confirmarExclusao(<?php echo $matricula['id']; ?>)"
                                    class="inline-flex items-center px-2 py-1 border border-transparent text-xs leading-4 font-medium rounded text-red-700 bg-red-100 hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                                    title="Excluir">
                                <i class="fas fa-trash"></i>
                            </button>
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
                    Mostrando <span class="font-medium"><?php echo ($pagina - 1) * $por_pagina + 1; ?></span> a <span class="font-medium"><?php echo min($pagina * $por_pagina, $total_matriculas); ?></span> de <span class="font-medium"><?php echo $total_matriculas; ?></span> resultados
                </p>
            </div>
            <div>
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                    <?php if ($pagina > 1): ?>
                    <a href="matriculas.php?pagina=<?php echo $pagina - 1; ?><?php echo $status !== 'todos' ? '&status=' . $status : ''; ?><?php echo $aluno_id ? '&aluno_id=' . $aluno_id : ''; ?><?php echo $curso_id ? '&curso_id=' . $curso_id : ''; ?><?php echo $turma_id ? '&turma_id=' . $turma_id : ''; ?><?php echo $polo_id ? '&polo_id=' . $polo_id : ''; ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                        <span class="sr-only">Anterior</span>
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $pagina - 2); $i <= min($total_paginas, $pagina + 2); $i++): ?>
                    <a href="matriculas.php?pagina=<?php echo $i; ?><?php echo $status !== 'todos' ? '&status=' . $status : ''; ?><?php echo $aluno_id ? '&aluno_id=' . $aluno_id : ''; ?><?php echo $curso_id ? '&curso_id=' . $curso_id : ''; ?><?php echo $turma_id ? '&turma_id=' . $turma_id : ''; ?><?php echo $polo_id ? '&polo_id=' . $polo_id : ''; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 <?php echo $i === $pagina ? 'bg-blue-50 text-blue-600' : 'bg-white text-gray-500 hover:bg-gray-50'; ?> text-sm font-medium">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>

                    <?php if ($pagina < $total_paginas): ?>
                    <a href="matriculas.php?pagina=<?php echo $pagina + 1; ?><?php echo $status !== 'todos' ? '&status=' . $status : ''; ?><?php echo $aluno_id ? '&aluno_id=' . $aluno_id : ''; ?><?php echo $curso_id ? '&curso_id=' . $curso_id : ''; ?><?php echo $turma_id ? '&turma_id=' . $turma_id : ''; ?><?php echo $polo_id ? '&polo_id=' . $polo_id : ''; ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
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
