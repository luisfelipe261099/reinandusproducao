<?php
// Exibe mensagens de erro ou sucesso
if (isset($_SESSION['mensagem'])) {
    if (is_array($_SESSION['mensagem'])) {
        $tipo = $_SESSION['mensagem']['tipo'];
        $texto = $_SESSION['mensagem']['texto'];
    } else {
        // Compatibilidade com o formato antigo
        $tipo = isset($_SESSION['mensagem_tipo']) ? $_SESSION['mensagem_tipo'] : 'erro';
        $texto = $_SESSION['mensagem'];
    }

    echo '<div class="mb-4 ' . ($tipo == 'erro' ? 'bg-red-100 border-red-400 text-red-700' : 'bg-green-100 border-green-400 text-green-700') . ' px-4 py-3 rounded relative border" role="alert">';
    echo '<span class="block sm:inline">' . $texto . '</span>';
    echo '</div>';

    unset($_SESSION['mensagem']);
    if (isset($_SESSION['mensagem_tipo'])) {
        unset($_SESSION['mensagem_tipo']);
    }
}
?>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <!-- Filtros -->
    <div class="p-6 border-b border-gray-200">
        <form action="gerar_boleto.php" method="get" class="space-y-4">
            <input type="hidden" name="action" value="listar">

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label for="tipo" class="block text-sm font-medium text-gray-700 mb-1">Tipo de Entidade</label>
                    <select name="tipo" id="tipo" class="form-select w-full">
                        <option value="">Todos</option>
                        <option value="aluno" <?php echo isset($filtros['tipo']) && $filtros['tipo'] == 'aluno' ? 'selected' : ''; ?>>Aluno</option>
                        <option value="polo" <?php echo isset($filtros['tipo']) && $filtros['tipo'] == 'polo' ? 'selected' : ''; ?>>Polo</option>
                        <option value="avulso" <?php echo isset($filtros['tipo']) && $filtros['tipo'] == 'avulso' ? 'selected' : ''; ?>>Avulso</option>
                    </select>
                </div>

                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" id="status" class="form-select w-full">
                        <option value="">Todos</option>
                        <option value="pendente" <?php echo isset($filtros['status']) && $filtros['status'] == 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                        <option value="pago" <?php echo isset($filtros['status']) && $filtros['status'] == 'pago' ? 'selected' : ''; ?>>Pago</option>
                        <option value="cancelado" <?php echo isset($filtros['status']) && $filtros['status'] == 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                        <option value="vencido" <?php echo isset($filtros['status']) && $filtros['status'] == 'vencido' ? 'selected' : ''; ?>>Vencido</option>
                    </select>
                </div>

                <div>
                    <label for="data_inicio" class="block text-sm font-medium text-gray-700 mb-1">Data Inicial</label>
                    <input type="date" name="data_inicio" id="data_inicio" class="form-input w-full" value="<?php echo $filtros['data_inicio'] ?? ''; ?>">
                </div>

                <div>
                    <label for="data_fim" class="block text-sm font-medium text-gray-700 mb-1">Data Final</label>
                    <input type="date" name="data_fim" id="data_fim" class="form-input w-full" value="<?php echo $filtros['data_fim'] ?? ''; ?>">
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="btn-primary">
                    <i class="fas fa-search mr-2"></i> Filtrar
                </button>
            </div>
        </form>
    </div>

    <!-- Listagem -->
    <div class="p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-800">Boletos Gerados</h3>
            <div class="flex space-x-2">
                <a href="gerar_boleto.php?action=mensalidades" class="btn-secondary">
                    <i class="fas fa-calendar-alt mr-2"></i> Gerar Boletos de Mensalidades
                </a>
                <a href="gerar_boleto.php" class="btn-primary">
                    <i class="fas fa-plus mr-2"></i> Novo Boleto
                </a>
            </div>
        </div>

        <?php if (empty($boletos)): ?>
        <div class="bg-gray-50 p-4 rounded-lg text-center">
            <p class="text-gray-600">Nenhum boleto encontrado.</p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descrição</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pagador</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valor</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vencimento</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($boletos as $boleto): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $boleto['id']; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($boleto['descricao']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($boleto['nome_entidade']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">R$ <?php echo number_format($boleto['valor'], 2, ',', '.'); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo date('d/m/Y', strtotime($boleto['data_vencimento'])); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="status-badge <?php
                                if ($boleto['status'] == 'pendente') echo 'status-pendente';
                                elseif ($boleto['status'] == 'pago') echo 'status-pago';
                                elseif ($boleto['status'] == 'cancelado') echo 'status-cancelado';
                                elseif ($boleto['status'] == 'vencido') echo 'status-vencido';
                            ?>">
                                <?php
                                    if ($boleto['status'] == 'pendente') echo 'Pendente';
                                    elseif ($boleto['status'] == 'pago') echo 'Pago';
                                    elseif ($boleto['status'] == 'cancelado') echo 'Cancelado';
                                    elseif ($boleto['status'] == 'vencido') echo 'Vencido';
                                ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <a href="gerar_boleto.php?action=visualizar&id=<?php echo $boleto['id']; ?>" class="text-blue-600 hover:text-blue-900" title="Visualizar">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if (!empty($boleto['url_boleto'])): ?>
                                <a href="<?php echo $boleto['url_boleto']; ?>" target="_blank" class="text-green-600 hover:text-green-900" title="Abrir Boleto">
                                    <i class="fas fa-file-invoice-dollar"></i>
                                </a>
                                <?php endif; ?>
                                <a href="download_boleto.php?id=<?php echo $boleto['id']; ?>" target="_blank" class="text-blue-600 hover:text-blue-900" title="Download PDF">
                                    <i class="fas fa-download"></i>
                                </a>
                                <?php if ($boleto['status'] == 'pendente'): ?>
                                <a href="gerar_boleto.php?action=marcar_pago&id=<?php echo $boleto['id']; ?>" class="text-green-600 hover:text-green-900" title="Marcar como Pago" onclick="return confirm('Deseja marcar este boleto como pago?');">
                                    <i class="fas fa-check-circle"></i>
                                </a>
                                <a href="gerar_boleto.php?action=cancelar&id=<?php echo $boleto['id']; ?>" class="text-red-600 hover:text-red-900" title="Cancelar Boleto" onclick="return confirm('Deseja cancelar este boleto?');">
                                    <i class="fas fa-times-circle"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginação -->
        <?php if ($total_paginas > 1): ?>
        <div class="mt-4 flex justify-center">
            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                <?php if ($pagina > 1): ?>
                <a href="gerar_boleto.php?action=listar&pagina=<?php echo $pagina - 1; ?><?php echo !empty($filtros) ? '&' . http_build_query($filtros) : ''; ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                    <span class="sr-only">Anterior</span>
                    <i class="fas fa-chevron-left"></i>
                </a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                <a href="gerar_boleto.php?action=listar&pagina=<?php echo $i; ?><?php echo !empty($filtros) ? '&' . http_build_query($filtros) : ''; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium <?php echo $i == $pagina ? 'text-blue-600 bg-blue-50' : 'text-gray-700 hover:bg-gray-50'; ?>">
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>

                <?php if ($pagina < $total_paginas): ?>
                <a href="gerar_boleto.php?action=listar&pagina=<?php echo $pagina + 1; ?><?php echo !empty($filtros) ? '&' . http_build_query($filtros) : ''; ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                    <span class="sr-only">Próxima</span>
                    <i class="fas fa-chevron-right"></i>
                </a>
                <?php endif; ?>
            </nav>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
