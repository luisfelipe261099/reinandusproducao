<!-- Filtros e Busca -->
<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <div class="flex flex-col space-y-4">
        <!-- Filtros de Status -->
        <div class="flex flex-col md:flex-row md:items-center space-y-2 md:space-y-0 md:space-x-4">
            <label class="text-sm font-medium text-gray-700">Status:</label>
            <div class="flex flex-wrap gap-2">
                <a href="turmas.php<?php echo isset($curso_id) ? '?curso_id='.$curso_id : ''; ?><?php echo isset($polo_id) ? (isset($curso_id) ? '&' : '?').'polo_id='.$polo_id : ''; ?>" class="<?php echo $status === 'todos' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'; ?> px-3 py-1 rounded-md text-sm">Todos</a>
                <a href="turmas.php?status=planejada<?php echo isset($curso_id) ? '&curso_id='.$curso_id : ''; ?><?php echo isset($polo_id) ? '&polo_id='.$polo_id : ''; ?>" class="<?php echo $status === 'planejada' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800'; ?> px-3 py-1 rounded-md text-sm">Planejadas</a>
                <a href="turmas.php?status=em_andamento<?php echo isset($curso_id) ? '&curso_id='.$curso_id : ''; ?><?php echo isset($polo_id) ? '&polo_id='.$polo_id : ''; ?>" class="<?php echo $status === 'em_andamento' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?> px-3 py-1 rounded-md text-sm">Em Andamento</a>
                <a href="turmas.php?status=concluida<?php echo isset($curso_id) ? '&curso_id='.$curso_id : ''; ?><?php echo isset($polo_id) ? '&polo_id='.$polo_id : ''; ?>" class="<?php echo $status === 'concluida' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'; ?> px-3 py-1 rounded-md text-sm">Concluídas</a>
                <a href="turmas.php?status=cancelada<?php echo isset($curso_id) ? '&curso_id='.$curso_id : ''; ?><?php echo isset($polo_id) ? '&polo_id='.$polo_id : ''; ?>" class="<?php echo $status === 'cancelada' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800'; ?> px-3 py-1 rounded-md text-sm">Canceladas</a>
            </div>
        </div>

        <!-- Filtros Avançados -->
        <form id="filtro-form" action="turmas.php" method="get">
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
            <form action="turmas.php" method="get" class="flex items-center space-x-2 w-full">
                <input type="hidden" name="action" value="buscar">
                <?php if (isset($status) && $status !== 'todos'): ?>
                <input type="hidden" name="status" value="<?php echo $status; ?>">
                <?php endif; ?>
                <?php if (isset($curso_id) && $curso_id): ?>
                <input type="hidden" name="curso_id" value="<?php echo $curso_id; ?>">
                <?php endif; ?>
                <?php if (isset($polo_id) && $polo_id): ?>
                <input type="hidden" name="polo_id" value="<?php echo $polo_id; ?>">
                <?php endif; ?>
                <select name="campo" class="form-select text-sm">
                    <option value="nome" <?php echo isset($campo) && $campo === 'nome' ? 'selected' : ''; ?>>Nome</option>
                    <option value="codigo" <?php echo isset($campo) && $campo === 'codigo' ? 'selected' : ''; ?>>Código</option>
                    <option value="id_legado" <?php echo isset($campo) && $campo === 'id_legado' ? 'selected' : ''; ?>>ID Legado</option>
                </select>
                <input type="text" name="termo" value="<?php echo isset($termo) ? htmlspecialchars($termo) : ''; ?>" placeholder="Buscar turmas..." class="form-input text-sm flex-grow">
                <button type="submit" class="btn-primary py-2">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Listagem de Turmas -->
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <?php if (empty($turmas)): ?>
    <div class="p-6 text-center text-gray-500">
        <p>Nenhuma turma encontrada.</p>
        <p class="mt-2">
            <a href="turmas.php?action=nova" class="text-blue-600 hover:text-blue-800">
                <i class="fas fa-plus mr-1"></i> Adicionar Nova Turma
            </a>
        </p>
    </div>
    <?php else: ?>

    <!-- Layout para Desktop -->
    <div class="hidden lg:block">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Turma</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Curso/Polo</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Período</th>
                        <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Carga Horária</th>
                        <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Alunos</th>
                        <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-32">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($turmas as $turma): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-8 w-8">
                                    <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center">
                                        <span class="text-blue-600 font-bold text-sm"><?php echo strtoupper(substr($turma['nome'], 0, 1)); ?></span>
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($turma['nome']); ?></div>
                                    <?php if (isset($turma['codigo']) && !empty($turma['codigo'])): ?>
                                    <div class="text-xs text-gray-500"><?php echo htmlspecialchars($turma['codigo']); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-sm text-gray-900"><?php echo isset($turma['curso_nome']) ? htmlspecialchars($turma['curso_nome']) : 'N/A'; ?></div>
                            <div class="text-xs text-gray-500"><?php echo isset($turma['polo_nome']) ? htmlspecialchars($turma['polo_nome']) : 'N/A'; ?></div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-sm text-gray-900">
                                <?php
                                $data_inicio = isset($turma['data_inicio']) && !empty($turma['data_inicio']) ? date('d/m/Y', strtotime($turma['data_inicio'])) : 'N/D';
                                $data_fim = isset($turma['data_fim']) && !empty($turma['data_fim']) ? date('d/m/Y', strtotime($turma['data_fim'])) : 'N/D';
                                echo $data_inicio;
                                ?>
                            </div>
                            <div class="text-xs text-gray-500">até <?php echo $data_fim; ?></div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="text-sm text-gray-900">
                                <?php
                                if (isset($turma['carga_horaria']) && !empty($turma['carga_horaria'])) {
                                    echo $turma['carga_horaria'] . 'h';
                                } else {
                                    echo '<span class="text-gray-400">N/D</span>';
                                }
                                ?>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                <?php echo isset($turma['total_alunos']) ? $turma['total_alunos'] : '0'; ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                <?php
                                if (isset($turma['status'])) {
                                    switch ($turma['status']) {
                                        case 'planejada':
                                            echo 'bg-yellow-100 text-yellow-800';
                                            break;
                                        case 'em_andamento':
                                            echo 'bg-green-100 text-green-800';
                                            break;
                                        case 'concluida':
                                            echo 'bg-blue-100 text-blue-800';
                                            break;
                                        case 'cancelada':
                                            echo 'bg-red-100 text-red-800';
                                            break;
                                        default:
                                            echo 'bg-gray-100 text-gray-800';
                                    }
                                } else {
                                    echo 'bg-gray-100 text-gray-800';
                                }
                                ?>">
                                <?php
                                if (isset($turma['status'])) {
                                    switch ($turma['status']) {
                                        case 'planejada':
                                            echo 'Planejada';
                                            break;
                                        case 'em_andamento':
                                            echo 'Em Andamento';
                                            break;
                                        case 'concluida':
                                            echo 'Concluída';
                                            break;
                                        case 'cancelada':
                                            echo 'Cancelada';
                                            break;
                                        default:
                                            echo ucfirst($turma['status']);
                                    }
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-center space-x-2">
                                <a href="turmas.php?action=visualizar&id=<?php echo $turma['id']; ?>" class="inline-flex items-center px-2 py-1 border border-transparent text-xs leading-4 font-medium rounded text-blue-700 bg-blue-100 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" title="Visualizar">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="turmas.php?action=editar&id=<?php echo $turma['id']; ?>" class="inline-flex items-center px-2 py-1 border border-transparent text-xs leading-4 font-medium rounded text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button onclick="confirmarExclusao(<?php echo $turma['id']; ?>, '<?php echo addslashes($turma['nome']); ?>')" class="inline-flex items-center px-2 py-1 border border-transparent text-xs leading-4 font-medium rounded text-red-700 bg-red-100 hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500" title="Excluir">
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
            <?php foreach ($turmas as $turma): ?>
            <div class="p-4 hover:bg-gray-50">
                <div class="flex items-center justify-between">
                    <div class="flex items-center flex-1 min-w-0">
                        <div class="flex-shrink-0 h-10 w-10">
                            <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                <span class="text-blue-600 font-bold"><?php echo strtoupper(substr($turma['nome'], 0, 1)); ?></span>
                            </div>
                        </div>
                        <div class="ml-4 flex-1 min-w-0">
                            <div class="text-sm font-medium text-gray-900 truncate"><?php echo htmlspecialchars($turma['nome']); ?></div>
                            <div class="text-sm text-gray-500 truncate"><?php echo isset($turma['curso_nome']) ? htmlspecialchars($turma['curso_nome']) : 'N/A'; ?></div>
                            <div class="text-xs text-gray-400"><?php echo isset($turma['polo_nome']) ? htmlspecialchars($turma['polo_nome']) : 'N/A'; ?></div>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2 ml-4">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                            <?php
                            if (isset($turma['status'])) {
                                switch ($turma['status']) {
                                    case 'planejada':
                                        echo 'bg-yellow-100 text-yellow-800';
                                        break;
                                    case 'em_andamento':
                                        echo 'bg-green-100 text-green-800';
                                        break;
                                    case 'concluida':
                                        echo 'bg-blue-100 text-blue-800';
                                        break;
                                    case 'cancelada':
                                        echo 'bg-red-100 text-red-800';
                                        break;
                                    default:
                                        echo 'bg-gray-100 text-gray-800';
                                }
                            } else {
                                echo 'bg-gray-100 text-gray-800';
                            }
                            ?>">
                            <?php
                            if (isset($turma['status'])) {
                                switch ($turma['status']) {
                                    case 'planejada':
                                        echo 'Planejada';
                                        break;
                                    case 'em_andamento':
                                        echo 'Andamento';
                                        break;
                                    case 'concluida':
                                        echo 'Concluída';
                                        break;
                                    case 'cancelada':
                                        echo 'Cancelada';
                                        break;
                                    default:
                                        echo ucfirst($turma['status']);
                                }
                            } else {
                                echo 'N/A';
                            }
                            ?>
                        </span>
                    </div>
                </div>

                <div class="mt-3 flex items-center justify-between">
                    <div class="flex items-center space-x-4 text-xs text-gray-500">
                        <span>
                            <i class="fas fa-calendar mr-1"></i>
                            <?php
                            $data_inicio = isset($turma['data_inicio']) && !empty($turma['data_inicio']) ? date('d/m/Y', strtotime($turma['data_inicio'])) : 'N/D';
                            $data_fim = isset($turma['data_fim']) && !empty($turma['data_fim']) ? date('d/m/Y', strtotime($turma['data_fim'])) : 'N/D';
                            echo $data_inicio . ' - ' . $data_fim;
                            ?>
                        </span>
                        <span>
                            <i class="fas fa-clock mr-1"></i>
                            <?php
                            if (isset($turma['carga_horaria']) && !empty($turma['carga_horaria'])) {
                                echo $turma['carga_horaria'] . 'h';
                            } else {
                                echo 'N/D';
                            }
                            ?>
                        </span>
                        <span>
                            <i class="fas fa-users mr-1"></i>
                            <?php echo isset($turma['total_alunos']) ? $turma['total_alunos'] : '0'; ?> alunos
                        </span>
                    </div>

                    <div class="flex space-x-1">
                        <a href="turmas.php?action=visualizar&id=<?php echo $turma['id']; ?>" class="inline-flex items-center p-2 border border-transparent rounded-full text-blue-600 bg-blue-100 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" title="Visualizar">
                            <i class="fas fa-eye text-xs"></i>
                        </a>
                        <a href="turmas.php?action=editar&id=<?php echo $turma['id']; ?>" class="inline-flex items-center p-2 border border-transparent rounded-full text-indigo-600 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" title="Editar">
                            <i class="fas fa-edit text-xs"></i>
                        </a>
                        <button onclick="confirmarExclusao(<?php echo $turma['id']; ?>, '<?php echo addslashes($turma['nome']); ?>')" class="inline-flex items-center p-2 border border-transparent rounded-full text-red-600 bg-red-100 hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500" title="Excluir">
                            <i class="fas fa-trash text-xs"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Paginação -->
    <?php if (isset($total_paginas) && $total_paginas > 1): ?>
    <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-gray-700">
                    Mostrando <span class="font-medium"><?php echo ($pagina - 1) * $por_pagina + 1; ?></span> a <span class="font-medium"><?php echo min($pagina * $por_pagina, $total_turmas); ?></span> de <span class="font-medium"><?php echo $total_turmas; ?></span> resultados
                </p>
            </div>
            <div>
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                    <?php if ($pagina > 1): ?>
                    <a href="turmas.php?pagina=<?php echo $pagina - 1; ?><?php echo $status !== 'todos' ? '&status=' . $status : ''; ?><?php echo $curso_id ? '&curso_id=' . $curso_id : ''; ?><?php echo $polo_id ? '&polo_id=' . $polo_id : ''; ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                        <span class="sr-only">Anterior</span>
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $pagina - 2); $i <= min($total_paginas, $pagina + 2); $i++): ?>
                    <a href="turmas.php?pagina=<?php echo $i; ?><?php echo $status !== 'todos' ? '&status=' . $status : ''; ?><?php echo $curso_id ? '&curso_id=' . $curso_id : ''; ?><?php echo $polo_id ? '&polo_id=' . $polo_id : ''; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 <?php echo $i === $pagina ? 'bg-blue-50 text-blue-600' : 'bg-white text-gray-500 hover:bg-gray-50'; ?> text-sm font-medium">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>

                    <?php if ($pagina < $total_paginas): ?>
                    <a href="turmas.php?pagina=<?php echo $pagina + 1; ?><?php echo $status !== 'todos' ? '&status=' . $status : ''; ?><?php echo $curso_id ? '&curso_id=' . $curso_id : ''; ?><?php echo $polo_id ? '&polo_id=' . $polo_id : ''; ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
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
<div id="modal-exclusao" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <i class="fas fa-exclamation-triangle text-red-600"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mt-2">Confirmar Exclusão</h3>
            <div class="mt-2 px-7 py-3">
                <p id="modal-message" class="text-sm text-gray-500"></p>
                <p class="text-xs text-gray-400 mt-2">
                    <i class="fas fa-info-circle mr-1"></i>
                    Se houver matrículas vinculadas, você será direcionado para uma página de confirmação.
                </p>
            </div>
            <div class="items-center px-4 py-3">
                <button onclick="fecharModal()" class="px-4 py-2 bg-gray-500 text-white text-base font-medium rounded-md w-24 mr-2 hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-300">
                    Cancelar
                </button>
                <a id="btn-confirmar-exclusao" href="#" class="px-4 py-2 bg-red-600 text-white text-base font-medium rounded-md w-24 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-300">
                    Continuar
                </a>
            </div>
        </div>
    </div>
</div>
