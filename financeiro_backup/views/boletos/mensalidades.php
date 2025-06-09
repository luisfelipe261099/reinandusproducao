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

<div class="mb-6">
    <div class="flex justify-between items-center mb-4">
        <div class="flex space-x-2">
            <a href="gerar_boleto.php" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded inline-flex items-center">
                <i class="fas fa-arrow-left mr-2"></i> Voltar
            </a>
            <a href="gerar_boleto.php?action=listar" class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded inline-flex items-center">
                <i class="fas fa-list mr-2"></i> Listar Boletos
            </a>
        </div>
    </div>

    <!-- Filtros -->
    <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
        <form action="gerar_boleto.php" method="get" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <input type="hidden" name="action" value="mensalidades">

            <div>
                <label for="aluno" class="block text-sm font-medium text-gray-700 mb-1">Aluno</label>
                <input type="text" name="aluno" id="aluno" value="<?php echo htmlspecialchars($filtros['aluno'] ?? ''); ?>" placeholder="Nome ou CPF..." class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
            </div>

            <div>
                <label for="polo_id" class="block text-sm font-medium text-gray-700 mb-1">Polo</label>
                <select name="polo_id" id="polo_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    <option value="">Todos os polos</option>
                    <?php foreach ($polos as $polo): ?>
                    <option value="<?php echo $polo['id']; ?>" <?php echo (isset($filtros['polo_id']) && $filtros['polo_id'] == $polo['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($polo['nome']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="curso_id" class="block text-sm font-medium text-gray-700 mb-1">Curso</label>
                <select name="curso_id" id="curso_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    <option value="">Todos os cursos</option>
                    <?php foreach ($cursos as $curso): ?>
                    <option value="<?php echo $curso['id']; ?>" <?php echo (isset($filtros['curso_id']) && $filtros['curso_id'] == $curso['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($curso['nome']); ?>
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
                <a href="gerar_boleto.php?action=mensalidades" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-2 px-4 rounded">
                    <i class="fas fa-times mr-2"></i> Limpar
                </a>
            </div>
        </form>
    </div>

    <!-- Resumo -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow-sm p-4 card">
            <h3 class="text-sm font-medium text-gray-500 mb-2">Total de mensalidades pendentes</h3>
            <p class="text-2xl font-bold text-gray-800"><?php echo $total_registros; ?></p>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-4 card">
            <h3 class="text-sm font-medium text-gray-500 mb-2">Valor total a receber</h3>
            <?php
            $valor_total = 0;
            foreach ($mensalidades as $mensalidade) {
                $valor_total += $mensalidade['valor'] + $mensalidade['acrescimo'] - $mensalidade['desconto'];
            }
            ?>
            <p class="text-2xl font-bold text-green-600">R$ <?php echo number_format($valor_total, 2, ',', '.'); ?></p>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-4 card">
            <h3 class="text-sm font-medium text-gray-500 mb-2">Mensalidades vencidas</h3>
            <?php
            $mensalidades_vencidas = 0;
            $hoje = date('Y-m-d');
            foreach ($mensalidades as $mensalidade) {
                if ($mensalidade['data_vencimento'] < $hoje) {
                    $mensalidades_vencidas++;
                }
            }
            ?>
            <p class="text-2xl font-bold <?php echo $mensalidades_vencidas > 0 ? 'text-red-600' : 'text-gray-800'; ?>">
                <?php echo $mensalidades_vencidas; ?>
            </p>
        </div>
    </div>

    <!-- Listagem -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <?php if (empty($mensalidades)): ?>
        <div class="p-6 text-center text-gray-500">
            <p>Nenhuma mensalidade pendente encontrada para o período selecionado.</p>
        </div>
        <?php else: ?>
        <form action="gerar_boleto.php?action=processar_mensalidades" method="post">
            <div class="p-4 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
                <div>
                    <label class="inline-flex items-center">
                        <input type="checkbox" id="selecionar_todos" class="form-checkbox h-5 w-5 text-blue-600">
                        <span class="ml-2 text-gray-700">Selecionar todas</span>
                    </label>
                </div>
                <div>
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded inline-flex items-center">
                        <i class="fas fa-file-invoice-dollar mr-2"></i> Gerar Boletos Selecionados
                    </button>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-10">
                                <span class="sr-only">Selecionar</span>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aluno</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descrição</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vencimento</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valor</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($mensalidades as $mensalidade): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <input type="checkbox" name="mensalidade_ids[]" value="<?php echo $mensalidade['id']; ?>" class="form-checkbox h-5 w-5 text-blue-600 mensalidade-checkbox">
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($mensalidade['aluno_nome']); ?>
                                </div>
                                <div class="text-xs text-gray-500">
                                    <?php if ($mensalidade['aluno_cpf']): ?>
                                    CPF: <?php echo htmlspecialchars($mensalidade['aluno_cpf']); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="text-xs text-gray-500">
                                    <?php if ($mensalidade['polo_nome']): ?>
                                    Polo: <?php echo htmlspecialchars($mensalidade['polo_nome']); ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">
                                    <?php echo htmlspecialchars($mensalidade['descricao']); ?>
                                </div>
                                <?php if ($mensalidade['curso_nome']): ?>
                                <div class="text-xs text-gray-500">
                                    Curso: <?php echo htmlspecialchars($mensalidade['curso_nome']); ?>
                                </div>
                                <?php endif; ?>
                                <?php if ($mensalidade['numero_parcela'] && $mensalidade['total_parcelas'] > 1): ?>
                                <div class="text-xs text-gray-500">
                                    Parcela <?php echo $mensalidade['numero_parcela']; ?>/<?php echo $mensalidade['total_parcelas']; ?>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                $hoje = date('Y-m-d');
                                $vencido = $mensalidade['data_vencimento'] < $hoje;
                                ?>
                                <div class="text-sm <?php echo $vencido ? 'text-red-600 font-semibold' : 'text-gray-900'; ?>">
                                    <?php echo date('d/m/Y', strtotime($mensalidade['data_vencimento'])); ?>
                                    <?php if ($vencido): ?>
                                    <span class="text-xs ml-1">(vencido)</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    R$ <?php echo number_format($mensalidade['valor'], 2, ',', '.'); ?>
                                </div>
                                <?php if ($mensalidade['desconto'] > 0 || $mensalidade['acrescimo'] > 0): ?>
                                <div class="text-xs text-gray-500">
                                    <?php if ($mensalidade['desconto'] > 0): ?>
                                    Desconto: R$ <?php echo number_format($mensalidade['desconto'], 2, ',', '.'); ?><br>
                                    <?php endif; ?>
                                    <?php if ($mensalidade['acrescimo'] > 0): ?>
                                    Acréscimo: R$ <?php echo number_format($mensalidade['acrescimo'], 2, ',', '.'); ?><br>
                                    <?php endif; ?>
                                    Total: R$ <?php echo number_format($mensalidade['valor'] + $mensalidade['acrescimo'] - $mensalidade['desconto'], 2, ',', '.'); ?>
                                </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </form>

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
                    <a href="gerar_boleto.php?action=mensalidades&pagina=1<?php echo !empty($filtros) ? '&' . http_build_query($filtros) : ''; ?>" class="px-3 py-1 rounded-md bg-gray-200 text-gray-700 hover:bg-gray-300">
                        <i class="fas fa-angle-double-left"></i>
                    </a>
                    <a href="gerar_boleto.php?action=mensalidades&pagina=<?php echo $pagina - 1; ?><?php echo !empty($filtros) ? '&' . http_build_query($filtros) : ''; ?>" class="px-3 py-1 rounded-md bg-gray-200 text-gray-700 hover:bg-gray-300">
                        <i class="fas fa-angle-left"></i>
                    </a>
                    <?php endif; ?>

                    <?php
                    $inicio = max(1, $pagina - 2);
                    $fim = min($total_paginas, $pagina + 2);

                    for ($i = $inicio; $i <= $fim; $i++):
                    ?>
                    <a href="gerar_boleto.php?action=mensalidades&pagina=<?php echo $i; ?><?php echo !empty($filtros) ? '&' . http_build_query($filtros) : ''; ?>" class="px-3 py-1 rounded-md <?php echo $i === $pagina ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>

                    <?php if ($pagina < $total_paginas): ?>
                    <a href="gerar_boleto.php?action=mensalidades&pagina=<?php echo $pagina + 1; ?><?php echo !empty($filtros) ? '&' . http_build_query($filtros) : ''; ?>" class="px-3 py-1 rounded-md bg-gray-200 text-gray-700 hover:bg-gray-300">
                        <i class="fas fa-angle-right"></i>
                    </a>
                    <a href="gerar_boleto.php?action=mensalidades&pagina=<?php echo $total_paginas; ?><?php echo !empty($filtros) ? '&' . http_build_query($filtros) : ''; ?>" class="px-3 py-1 rounded-md bg-gray-200 text-gray-700 hover:bg-gray-300">
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

<script>
    // Selecionar/deselecionar todas as mensalidades
    document.getElementById('selecionar_todos').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.mensalidade-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
    });
</script>
