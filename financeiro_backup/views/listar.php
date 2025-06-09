<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800"><?php echo $titulo_pagina; ?></h1>
        
        <div class="flex space-x-2">
            <a href="transacoes.php?action=nova<?php echo $tipo !== 'todos' ? '&tipo=' . $tipo : ''; ?>" class="btn-primary">
                <i class="fas fa-plus mr-2"></i> Nova <?php echo $tipo === 'despesa' ? 'Despesa' : ($tipo === 'receita' ? 'Receita' : 'Transação'); ?>
            </a>
            <a href="index.php" class="btn-secondary">
                <i class="fas fa-tachometer-alt mr-2"></i> Dashboard
            </a>
        </div>
    </div>
    
    <!-- Filtros -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Filtros</h3>
        </div>
        <div class="p-6">
            <form action="transacoes.php" method="get" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <input type="hidden" name="tipo" value="<?php echo $tipo; ?>">
                
                <div>
                    <label for="termo" class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                    <input type="text" name="termo" id="termo" value="<?php echo $filtros['termo'] ?? ''; ?>" class="form-input w-full" placeholder="Descrição ou observações">
                </div>
                
                <div>
                    <label for="categoria_id" class="block text-sm font-medium text-gray-700 mb-1">Categoria</label>
                    <select name="categoria_id" id="categoria_id" class="form-select w-full">
                        <option value="">Todas as categorias</option>
                        <?php foreach ($categorias as $categoria): ?>
                        <option value="<?php echo $categoria['id']; ?>" <?php echo (isset($filtros['categoria_id']) && $filtros['categoria_id'] == $categoria['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($categoria['nome']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label for="conta_id" class="block text-sm font-medium text-gray-700 mb-1">Conta</label>
                    <select name="conta_id" id="conta_id" class="form-select w-full">
                        <option value="">Todas as contas</option>
                        <?php foreach ($contas as $conta): ?>
                        <option value="<?php echo $conta['id']; ?>" <?php echo (isset($filtros['conta_id']) && $filtros['conta_id'] == $conta['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($conta['nome']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" id="status" class="form-select w-full">
                        <option value="">Todos os status</option>
                        <option value="efetivada" <?php echo (isset($filtros['status']) && $filtros['status'] === 'efetivada') ? 'selected' : ''; ?>>Efetivada</option>
                        <option value="pendente" <?php echo (isset($filtros['status']) && $filtros['status'] === 'pendente') ? 'selected' : ''; ?>>Pendente</option>
                        <option value="cancelada" <?php echo (isset($filtros['status']) && $filtros['status'] === 'cancelada') ? 'selected' : ''; ?>>Cancelada</option>
                    </select>
                </div>
                
                <div>
                    <label for="data_inicio" class="block text-sm font-medium text-gray-700 mb-1">Data Inicial</label>
                    <input type="date" name="data_inicio" id="data_inicio" value="<?php echo $filtros['data_inicio'] ?? ''; ?>" class="form-input w-full">
                </div>
                
                <div>
                    <label for="data_fim" class="block text-sm font-medium text-gray-700 mb-1">Data Final</label>
                    <input type="date" name="data_fim" id="data_fim" value="<?php echo $filtros['data_fim'] ?? ''; ?>" class="form-input w-full">
                </div>
                
                <div class="md:col-span-3 flex justify-end space-x-2">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-search mr-2"></i> Filtrar
                    </button>
                    <a href="transacoes.php<?php echo $tipo !== 'todos' ? '?tipo=' . $tipo : ''; ?>" class="btn-secondary">
                        <i class="fas fa-times mr-2"></i> Limpar Filtros
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Listagem de Transações -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-800">
                <?php echo $total_registros; ?> <?php echo $total_registros === 1 ? 'transação encontrada' : 'transações encontradas'; ?>
            </h3>
            
            <div class="flex space-x-2">
                <?php if ($tipo === 'todos'): ?>
                <a href="transacoes.php?tipo=receita<?php echo !empty($_SERVER['QUERY_STRING']) ? '&' . preg_replace('/(\?|&)tipo=[^&]*/', '', $_SERVER['QUERY_STRING']) : ''; ?>" class="text-sm text-green-600 hover:text-green-800">
                    <i class="fas fa-arrow-down mr-1"></i> Apenas Receitas
                </a>
                <a href="transacoes.php?tipo=despesa<?php echo !empty($_SERVER['QUERY_STRING']) ? '&' . preg_replace('/(\?|&)tipo=[^&]*/', '', $_SERVER['QUERY_STRING']) : ''; ?>" class="text-sm text-red-600 hover:text-red-800">
                    <i class="fas fa-arrow-up mr-1"></i> Apenas Despesas
                </a>
                <?php else: ?>
                <a href="transacoes.php<?php echo !empty($_SERVER['QUERY_STRING']) ? '?' . preg_replace('/(\?|&)tipo=[^&]*/', '', $_SERVER['QUERY_STRING']) : ''; ?>" class="text-sm text-blue-600 hover:text-blue-800">
                    <i class="fas fa-list mr-1"></i> Todas as Transações
                </a>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (empty($transacoes)): ?>
        <div class="p-6 text-center text-gray-500">
            <p>Nenhuma transação encontrada com os filtros selecionados.</p>
            <p class="mt-2">
                <a href="transacoes.php?action=nova<?php echo $tipo !== 'todos' ? '&tipo=' . $tipo : ''; ?>" class="text-blue-600 hover:text-blue-800">
                    <i class="fas fa-plus mr-1"></i> Adicionar <?php echo $tipo === 'despesa' ? 'Despesa' : ($tipo === 'receita' ? 'Receita' : 'Transação'); ?>
                </a>
            </p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descrição</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categoria</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Conta</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Valor</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($transacoes as $transacao): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                <?php echo date('d/m/Y', strtotime($transacao['data_transacao'])); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($transacao['descricao']); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                <?php echo htmlspecialchars($transacao['categoria_nome'] ?? 'Sem categoria'); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                <?php echo htmlspecialchars($transacao['conta_nome'] ?? 'Sem conta'); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                <?php echo $transacao['status'] === 'efetivada' ? 'bg-green-100 text-green-800' : 
                                        ($transacao['status'] === 'pendente' ? 'bg-yellow-100 text-yellow-800' : 
                                        'bg-red-100 text-red-800'); ?>">
                                <?php echo $transacao['status'] === 'efetivada' ? 'Efetivada' : 
                                        ($transacao['status'] === 'pendente' ? 'Pendente' : 'Cancelada'); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <div class="text-sm font-medium <?php echo $transacao['tipo'] === 'receita' ? 'text-green-600' : 'text-red-600'; ?>">
                                <?php echo $transacao['tipo'] === 'receita' ? '+' : '-'; ?> R$ <?php echo number_format($transacao['valor'], 2, ',', '.'); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="transacoes.php?action=visualizar&id=<?php echo $transacao['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3" title="Visualizar">
                                <i class="fas fa-eye"></i>
                            </a>
                            <?php if (Auth::hasPermission('financeiro', 'editar')): ?>
                            <a href="transacoes.php?action=editar&id=<?php echo $transacao['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <?php endif; ?>
                            <?php if (Auth::hasPermission('financeiro', 'excluir')): ?>
                            <a href="javascript:void(0);" onclick="confirmarExclusao(<?php echo $transacao['id']; ?>)" class="text-red-600 hover:text-red-900" title="Excluir">
                                <i class="fas fa-trash"></i>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <!-- Paginação -->
        <?php if ($total_paginas > 1): ?>
        <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm text-gray-700">
                        Mostrando <span class="font-medium"><?php echo ($pagina - 1) * $por_pagina + 1; ?></span> a <span class="font-medium"><?php echo min($pagina * $por_pagina, $total_registros); ?></span> de <span class="font-medium"><?php echo $total_registros; ?></span> resultados
                    </p>
                </div>
                <div>
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                        <?php if ($pagina > 1): ?>
                        <a href="transacoes.php?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina - 1])); ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                            <span class="sr-only">Anterior</span>
                            <i class="fas fa-chevron-left"></i>
                        </a>
                        <?php endif; ?>

                        <?php for ($i = max(1, $pagina - 2); $i <= min($total_paginas, $pagina + 2); $i++): ?>
                        <a href="transacoes.php?<?php echo http_build_query(array_merge($_GET, ['pagina' => $i])); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 <?php echo $i === $pagina ? 'bg-blue-50 text-blue-600' : 'bg-white text-gray-500 hover:bg-gray-50'; ?> text-sm font-medium">
                            <?php echo $i; ?>
                        </a>
                        <?php endfor; ?>

                        <?php if ($pagina < $total_paginas): ?>
                        <a href="transacoes.php?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina + 1])); ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                            <span class="sr-only">Próxima</span>
                            <i class="fas fa-chevron-right"></i>
                        </a>
                        <?php endif; ?>
                    </nav>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal de confirmação de exclusão -->
<div id="modal-exclusao" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
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
                            <p class="text-sm text-gray-500">
                                Tem certeza que deseja excluir esta transação? Esta ação não pode ser desfeita.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <a id="btn-confirmar-exclusao" href="#" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
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
    function confirmarExclusao(id) {
        const modal = document.getElementById('modal-exclusao');
        const btnConfirmar = document.getElementById('btn-confirmar-exclusao');
        
        modal.classList.remove('hidden');
        btnConfirmar.href = 'transacoes.php?action=excluir&id=' + id;
    }
    
    function fecharModal() {
        const modal = document.getElementById('modal-exclusao');
        modal.classList.add('hidden');
    }
</script>
