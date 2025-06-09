<div class="mb-6">
    <div class="flex justify-between items-center mb-4">
        <div>
            <a href="contas_receber.php?action=nova" class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded inline-flex items-center">
                <i class="fas fa-plus mr-2"></i> Nova Conta a Receber
            </a>
        </div>
        
        <div class="flex space-x-2">
            <a href="contas_receber.php?status=pendente" class="<?php echo $status === 'pendente' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'; ?> hover:bg-blue-700 hover:text-white font-medium py-2 px-4 rounded">
                Pendentes
            </a>
            <a href="contas_receber.php?status=recebido" class="<?php echo $status === 'recebido' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'; ?> hover:bg-blue-700 hover:text-white font-medium py-2 px-4 rounded">
                Recebidas
            </a>
            <a href="contas_receber.php?status=cancelado" class="<?php echo $status === 'cancelado' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'; ?> hover:bg-blue-700 hover:text-white font-medium py-2 px-4 rounded">
                Canceladas
            </a>
            <a href="contas_receber.php?status=todos" class="<?php echo $status === 'todos' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'; ?> hover:bg-blue-700 hover:text-white font-medium py-2 px-4 rounded">
                Todas
            </a>
        </div>
    </div>
    
    <!-- Filtros -->
    <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
        <form action="contas_receber.php" method="get" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <input type="hidden" name="status" value="<?php echo htmlspecialchars($status); ?>">
            
            <div>
                <label for="termo" class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                <input type="text" name="termo" id="termo" value="<?php echo htmlspecialchars($filtros['termo'] ?? ''); ?>" placeholder="Descrição, cliente..." class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
            </div>
            
            <div>
                <label for="categoria_id" class="block text-sm font-medium text-gray-700 mb-1">Categoria</label>
                <select name="categoria_id" id="categoria_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    <option value="">Todas as categorias</option>
                    <?php foreach ($categorias as $categoria): ?>
                    <option value="<?php echo $categoria['id']; ?>" <?php echo (isset($filtros['categoria_id']) && $filtros['categoria_id'] == $categoria['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($categoria['nome']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label for="data_vencimento_inicio" class="block text-sm font-medium text-gray-700 mb-1">Vencimento</label>
                <div class="flex space-x-2">
                    <input type="date" name="data_vencimento_inicio" id="data_vencimento_inicio" value="<?php echo htmlspecialchars($filtros['data_vencimento_inicio'] ?? ''); ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    <span class="text-gray-500 self-center">até</span>
                    <input type="date" name="data_vencimento_fim" id="data_vencimento_fim" value="<?php echo htmlspecialchars($filtros['data_vencimento_fim'] ?? ''); ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                </div>
            </div>
            
            <div class="md:col-span-3 flex justify-end space-x-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded">
                    <i class="fas fa-search mr-2"></i> Filtrar
                </button>
                <a href="contas_receber.php?status=<?php echo htmlspecialchars($status); ?>" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-2 px-4 rounded">
                    <i class="fas fa-times mr-2"></i> Limpar
                </a>
            </div>
        </form>
    </div>
    
    <!-- Resumo -->
    <?php if ($status === 'pendente'): ?>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow-sm p-4 card">
            <h3 class="text-sm font-medium text-gray-500 mb-2">Total de contas pendentes</h3>
            <p class="text-2xl font-bold text-gray-800"><?php echo $total_registros; ?></p>
        </div>
        
        <div class="bg-white rounded-lg shadow-sm p-4 card">
            <h3 class="text-sm font-medium text-gray-500 mb-2">Valor total a receber</h3>
            <?php
            $valor_total = 0;
            foreach ($contas as $conta) {
                $valor_total += $conta['valor'];
            }
            ?>
            <p class="text-2xl font-bold text-green-600">R$ <?php echo number_format($valor_total, 2, ',', '.'); ?></p>
        </div>
        
        <div class="bg-white rounded-lg shadow-sm p-4 card">
            <h3 class="text-sm font-medium text-gray-500 mb-2">Contas vencidas</h3>
            <?php
            $contas_vencidas = 0;
            $hoje = date('Y-m-d');
            foreach ($contas as $conta) {
                if ($conta['data_vencimento'] < $hoje) {
                    $contas_vencidas++;
                }
            }
            ?>
            <p class="text-2xl font-bold <?php echo $contas_vencidas > 0 ? 'text-red-600' : 'text-gray-800'; ?>">
                <?php echo $contas_vencidas; ?>
            </p>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Listagem -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <?php if (empty($contas)): ?>
        <div class="p-6 text-center text-gray-500">
            <p>Nenhuma conta a receber encontrada.</p>
            <p class="mt-2">
                <a href="contas_receber.php?action=nova" class="text-blue-600 hover:text-blue-800">
                    <i class="fas fa-plus mr-1"></i> Adicionar Conta a Receber
                </a>
            </p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descrição</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vencimento</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valor</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($contas as $conta): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($conta['descricao']); ?>
                            </div>
                            <div class="text-xs text-gray-500">
                                <?php echo htmlspecialchars($conta['categoria_nome'] ?? 'Sem categoria'); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                <?php echo htmlspecialchars($conta['cliente_nome'] ?? 'Não informado'); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php
                            $hoje = date('Y-m-d');
                            $vencido = $conta['status'] === 'pendente' && $conta['data_vencimento'] < $hoje;
                            ?>
                            <div class="text-sm <?php echo $vencido ? 'vencido' : 'text-gray-900'; ?>">
                                <?php echo date('d/m/Y', strtotime($conta['data_vencimento'])); ?>
                                <?php if ($vencido): ?>
                                <span class="text-xs ml-1">(vencido)</span>
                                <?php endif; ?>
                            </div>
                            <?php if ($conta['data_recebimento']): ?>
                            <div class="text-xs text-gray-500">
                                Recebido em: <?php echo date('d/m/Y', strtotime($conta['data_recebimento'])); ?>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">
                                R$ <?php echo number_format($conta['valor'], 2, ',', '.'); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="status-badge <?php echo 'status-' . $conta['status']; ?>">
                                <?php
                                switch ($conta['status']) {
                                    case 'pendente':
                                        echo '<i class="fas fa-clock mr-1"></i> Pendente';
                                        break;
                                    case 'recebido':
                                        echo '<i class="fas fa-check-circle mr-1"></i> Recebido';
                                        break;
                                    case 'cancelado':
                                        echo '<i class="fas fa-ban mr-1"></i> Cancelado';
                                        break;
                                    default:
                                        echo htmlspecialchars($conta['status']);
                                }
                                ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="contas_receber.php?action=visualizar&id=<?php echo $conta['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                <i class="fas fa-eye"></i>
                            </a>
                            
                            <?php if ($conta['status'] === 'pendente'): ?>
                            <a href="contas_receber.php?action=receber&id=<?php echo $conta['id']; ?>" class="text-green-600 hover:text-green-900 mr-3">
                                <i class="fas fa-hand-holding-usd"></i>
                            </a>
                            <?php endif; ?>
                            
                            <a href="contas_receber.php?action=editar&id=<?php echo $conta['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                <i class="fas fa-edit"></i>
                            </a>
                            
                            <a href="#" onclick="confirmarExclusao(<?php echo $conta['id']; ?>); return false;" class="text-red-600 hover:text-red-900">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Paginação -->
        <?php if ($total_paginas > 1): ?>
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
            <div class="flex justify-between items-center">
                <div class="text-sm text-gray-700">
                    Mostrando <span class="font-medium"><?php echo min(($pagina - 1) * $por_pagina + 1, $total_registros); ?></span> a 
                    <span class="font-medium"><?php echo min($pagina * $por_pagina, $total_registros); ?></span> de 
                    <span class="font-medium"><?php echo $total_registros; ?></span> registros
                </div>
                
                <div class="flex space-x-1">
                    <?php if ($pagina > 1): ?>
                    <a href="contas_receber.php?pagina=1<?php echo http_build_query(array_merge($filtros, ['status' => $status])); ?>" class="px-3 py-1 rounded-md bg-gray-200 text-gray-700 hover:bg-gray-300">
                        <i class="fas fa-angle-double-left"></i>
                    </a>
                    <a href="contas_receber.php?pagina=<?php echo $pagina - 1; ?>&<?php echo http_build_query(array_merge($filtros, ['status' => $status])); ?>" class="px-3 py-1 rounded-md bg-gray-200 text-gray-700 hover:bg-gray-300">
                        <i class="fas fa-angle-left"></i>
                    </a>
                    <?php endif; ?>
                    
                    <?php
                    $inicio = max(1, $pagina - 2);
                    $fim = min($total_paginas, $pagina + 2);
                    
                    for ($i = $inicio; $i <= $fim; $i++):
                    ?>
                    <a href="contas_receber.php?pagina=<?php echo $i; ?>&<?php echo http_build_query(array_merge($filtros, ['status' => $status])); ?>" class="px-3 py-1 rounded-md <?php echo $i === $pagina ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>
                    
                    <?php if ($pagina < $total_paginas): ?>
                    <a href="contas_receber.php?pagina=<?php echo $pagina + 1; ?>&<?php echo http_build_query(array_merge($filtros, ['status' => $status])); ?>" class="px-3 py-1 rounded-md bg-gray-200 text-gray-700 hover:bg-gray-300">
                        <i class="fas fa-angle-right"></i>
                    </a>
                    <a href="contas_receber.php?pagina=<?php echo $total_paginas; ?>&<?php echo http_build_query(array_merge($filtros, ['status' => $status])); ?>" class="px-3 py-1 rounded-md bg-gray-200 text-gray-700 hover:bg-gray-300">
                        <i class="fas fa-angle-double-right"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal de confirmação de exclusão -->
<div id="modal-exclusao" class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 max-w-md w-full">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Confirmar Exclusão</h3>
        <p class="text-gray-700 mb-6">Tem certeza que deseja excluir esta conta a receber? Esta ação não pode ser desfeita.</p>
        <div class="flex justify-end space-x-3">
            <button id="btn-cancelar" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">Cancelar</button>
            <a id="btn-excluir" href="#" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">Excluir</a>
        </div>
    </div>
</div>

<script>
    function confirmarExclusao(id) {
        const modal = document.getElementById('modal-exclusao');
        const btnExcluir = document.getElementById('btn-excluir');
        const btnCancelar = document.getElementById('btn-cancelar');
        
        modal.classList.remove('hidden');
        btnExcluir.href = `contas_receber.php?action=excluir&id=${id}`;
        
        btnCancelar.addEventListener('click', function() {
            modal.classList.add('hidden');
        });
        
        // Fechar modal ao clicar fora dele
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.classList.add('hidden');
            }
        });
    }
</script>
