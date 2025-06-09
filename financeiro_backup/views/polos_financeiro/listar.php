<div class="mb-6">
    <!-- Filtros -->
    <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
        <form action="polos_financeiro.php" method="get" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="nome" class="block text-sm font-medium text-gray-700 mb-1">Nome do Polo</label>
                <input type="text" name="nome" id="nome" value="<?php echo htmlspecialchars($filtros['nome'] ?? ''); ?>" placeholder="Buscar por nome..." class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
            </div>
            
            <div>
                <label for="tipo_id" class="block text-sm font-medium text-gray-700 mb-1">Tipo de Polo</label>
                <select name="tipo_id" id="tipo_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    <option value="">Todos os tipos</option>
                    <?php foreach ($tipos_polos as $tipo): ?>
                    <option value="<?php echo $tipo['id']; ?>" <?php echo (isset($filtros['tipo_id']) && $filtros['tipo_id'] == $tipo['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($tipo['nome']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" id="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    <option value="">Todos</option>
                    <option value="ativo" <?php echo (isset($filtros['status']) && $filtros['status'] === 'ativo') ? 'selected' : ''; ?>>Ativo</option>
                    <option value="inativo" <?php echo (isset($filtros['status']) && $filtros['status'] === 'inativo') ? 'selected' : ''; ?>>Inativo</option>
                </select>
            </div>
            
            <div class="md:col-span-3 flex justify-end space-x-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded">
                    <i class="fas fa-search mr-2"></i> Filtrar
                </button>
                <a href="polos_financeiro.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-2 px-4 rounded">
                    <i class="fas fa-times mr-2"></i> Limpar
                </a>
            </div>
        </form>
    </div>
    
    <!-- Listagem -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <?php if (empty($polos)): ?>
        <div class="p-6 text-center text-gray-500">
            <p>Nenhum polo encontrado.</p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Polo</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipos</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($polos as $polo): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($polo['nome']); ?>
                            </div>
                            <?php if ($polo['razao_social']): ?>
                            <div class="text-xs text-gray-500">
                                <?php echo htmlspecialchars($polo['razao_social']); ?>
                            </div>
                            <?php endif; ?>
                            <?php if ($polo['cnpj']): ?>
                            <div class="text-xs text-gray-500">
                                CNPJ: <?php echo htmlspecialchars($polo['cnpj']); ?>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4">
                            <?php if ($polo['total_tipos'] > 0): ?>
                                <div class="flex flex-wrap">
                                    <?php 
                                    $tipos = explode(', ', $polo['tipos_nomes']);
                                    foreach ($tipos as $tipo): 
                                        $class = '';
                                        if (stripos($tipo, 'pós') !== false) {
                                            $class = 'tipo-badge-pos';
                                        } elseif (stripos($tipo, 'extensão') !== false) {
                                            $class = 'tipo-badge-ext';
                                        } elseif (stripos($tipo, 'graduação') !== false) {
                                            $class = 'tipo-badge-grad';
                                        }
                                    ?>
                                    <span class="tipo-badge <?php echo $class; ?>">
                                        <?php echo htmlspecialchars($tipo); ?>
                                    </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <span class="text-gray-500 text-sm">Nenhum tipo configurado</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $polo['status'] === 'ativo' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo $polo['status'] === 'ativo' ? 'Ativo' : 'Inativo'; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="polos_financeiro.php?action=editar&id=<?php echo $polo['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                <i class="fas fa-edit"></i> Configurar
                            </a>
                            <a href="polos_financeiro.php?action=historico&id=<?php echo $polo['id']; ?>" class="text-blue-600 hover:text-blue-900">
                                <i class="fas fa-history"></i> Histórico
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
                    <a href="polos_financeiro.php?pagina=1<?php echo !empty($filtros) ? '&' . http_build_query($filtros) : ''; ?>" class="px-3 py-1 rounded-md bg-gray-200 text-gray-700 hover:bg-gray-300">
                        <i class="fas fa-angle-double-left"></i>
                    </a>
                    <a href="polos_financeiro.php?pagina=<?php echo $pagina - 1; ?><?php echo !empty($filtros) ? '&' . http_build_query($filtros) : ''; ?>" class="px-3 py-1 rounded-md bg-gray-200 text-gray-700 hover:bg-gray-300">
                        <i class="fas fa-angle-left"></i>
                    </a>
                    <?php endif; ?>
                    
                    <?php
                    $inicio = max(1, $pagina - 2);
                    $fim = min($total_paginas, $pagina + 2);
                    
                    for ($i = $inicio; $i <= $fim; $i++):
                    ?>
                    <a href="polos_financeiro.php?pagina=<?php echo $i; ?><?php echo !empty($filtros) ? '&' . http_build_query($filtros) : ''; ?>" class="px-3 py-1 rounded-md <?php echo $i === $pagina ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>
                    
                    <?php if ($pagina < $total_paginas): ?>
                    <a href="polos_financeiro.php?pagina=<?php echo $pagina + 1; ?><?php echo !empty($filtros) ? '&' . http_build_query($filtros) : ''; ?>" class="px-3 py-1 rounded-md bg-gray-200 text-gray-700 hover:bg-gray-300">
                        <i class="fas fa-angle-right"></i>
                    </a>
                    <a href="polos_financeiro.php?pagina=<?php echo $total_paginas; ?><?php echo !empty($filtros) ? '&' . http_build_query($filtros) : ''; ?>" class="px-3 py-1 rounded-md bg-gray-200 text-gray-700 hover:bg-gray-300">
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
