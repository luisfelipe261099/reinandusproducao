<div class="bg-white p-6 rounded-lg shadow-md">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold"><?php echo $titulo_pagina; ?></h1>
            <div class="flex mt-2 space-x-4">
                <a href="?view=chamados" class="text-gray-600 hover:text-blue-600 <?php echo $view_type == 'chamados' ? 'font-bold text-blue-600' : ''; ?>">
                    <i class="fas fa-ticket-alt mr-1"></i> Chamados
                </a>
                <a href="?view=solicitacoes" class="text-gray-600 hover:text-blue-600 <?php echo $view_type == 'solicitacoes' ? 'font-bold text-blue-600' : ''; ?>">
                    <i class="fas fa-file-alt mr-1"></i> Solicitações de Documentos
                </a>
                <a href="?view=chamados_site" class="text-gray-600 hover:text-blue-600 <?php echo $view_type == 'chamados_site' ? 'font-bold text-blue-600' : ''; ?>">
                    <i class="fas fa-globe mr-1"></i> Chamados Site
                </a>
            </div>
        </div>

        <div>
            <a href="../solicitar_documentos.php" class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded">
                <i class="fas fa-plus mr-2"></i> Nova Solicitação
            </a>
        </div>
    </div>

    <!-- Filtros -->
    <div class="bg-white shadow-md rounded-lg p-6 mb-6">
        <h2 class="text-lg font-medium text-gray-900 mb-4">Filtros</h2>
        <form action="index.php" method="GET" class="space-y-4">
            <input type="hidden" name="view" value="chamados_site">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label for="tipo_solicitacao" class="block text-sm font-medium text-gray-700 mb-1">Tipo de Solicitação</label>
                    <select id="tipo_solicitacao" name="tipo_solicitacao" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                        <option value="">Todos</option>
                        <option value="certificado" <?php echo $filtros['tipo_solicitacao'] == 'certificado' ? 'selected' : ''; ?>>Certificado</option>
                        <option value="declaracao" <?php echo $filtros['tipo_solicitacao'] == 'declaracao' ? 'selected' : ''; ?>>Declaração</option>
                        <option value="historico" <?php echo $filtros['tipo_solicitacao'] == 'historico' ? 'selected' : ''; ?>>Histórico</option>
                        <option value="outro" <?php echo $filtros['tipo_solicitacao'] == 'outro' ? 'selected' : ''; ?>>Outro</option>
                    </select>
                </div>

                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select id="status" name="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                        <option value="">Todos</option>
                        <option value="Pendente" <?php echo $filtros['status'] == 'Pendente' ? 'selected' : ''; ?>>Pendente</option>
                        <option value="Em Análise" <?php echo $filtros['status'] == 'Em Análise' ? 'selected' : ''; ?>>Em Análise</option>
                        <option value="Concluído" <?php echo $filtros['status'] == 'Concluído' ? 'selected' : ''; ?>>Concluído</option>
                        <option value="Cancelado" <?php echo $filtros['status'] == 'Cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                    </select>
                </div>

                <div>
                    <label for="data_inicio" class="block text-sm font-medium text-gray-700 mb-1">Data Inicial</label>
                    <input type="date" id="data_inicio" name="data_inicio" value="<?php echo $filtros['data_inicio']; ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                </div>

                <div>
                    <label for="data_fim" class="block text-sm font-medium text-gray-700 mb-1">Data Final</label>
                    <input type="date" id="data_fim" name="data_fim" value="<?php echo $filtros['data_fim']; ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                </div>

                <div>
                    <label for="busca" class="block text-sm font-medium text-gray-700 mb-1">Busca</label>
                    <input type="text" id="busca" name="busca" value="<?php echo htmlspecialchars($filtros['busca']); ?>" placeholder="Buscar por protocolo, empresa ou solicitante" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded">
                    <i class="fas fa-search mr-2"></i> Filtrar
                </button>
            </div>
        </form>
    </div>

    <!-- Listagem de Chamados do Site -->
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <?php if (empty($solicitacoes_site)): ?>
        <div class="p-6 text-center text-gray-500">
            <p>Nenhuma solicitação do site encontrada.</p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Protocolo</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Empresa</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Solicitante</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantidade</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($solicitacoes_site as $solicitacao): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            <?php echo htmlspecialchars($solicitacao['protocolo']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo htmlspecialchars($solicitacao['nome_empresa']); ?>
                            <div class="text-xs text-gray-400">
                                CNPJ: <?php echo htmlspecialchars($solicitacao['cnpj']); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo htmlspecialchars($solicitacao['nome_solicitante']); ?>
                            <div class="text-xs text-gray-400">
                                Tel: <?php echo htmlspecialchars($solicitacao['telefone']); ?><br>
                                Email: <?php echo htmlspecialchars($solicitacao['email']); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo htmlspecialchars(ucfirst($solicitacao['tipo_solicitacao'])); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo htmlspecialchars($solicitacao['quantidade']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo date('d/m/Y H:i', strtotime($solicitacao['data_solicitacao'])); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php
                            $status_class = '';
                            $status_text = $solicitacao['status'];

                            switch ($solicitacao['status']) {
                                case 'Pendente':
                                    $status_class = 'bg-yellow-100 text-yellow-800';
                                    break;
                                case 'Em Análise':
                                    $status_class = 'bg-blue-100 text-blue-800';
                                    break;
                                case 'Concluído':
                                    $status_class = 'bg-green-100 text-green-800';
                                    break;
                                case 'Cancelado':
                                    $status_class = 'bg-red-100 text-red-800';
                                    break;
                                default:
                                    $status_class = 'bg-gray-100 text-gray-800';
                            }
                            ?>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                                <?php echo $status_text; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <a href="visualizar_site.php?id=<?php echo $solicitacao['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3" title="Visualizar detalhes">
                                <i class="fas fa-eye"></i>
                            </a>
                            <?php if ($solicitacao['link_planilha']): ?>
                            <a href="<?php echo htmlspecialchars($solicitacao['link_planilha']); ?>" target="_blank" class="text-purple-600 hover:text-purple-900" title="Abrir planilha">
                                <i class="fas fa-file-excel"></i>
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
                        Mostrando <span class="font-medium"><?php echo ($pagina - 1) * $itens_por_pagina + 1; ?></span> a
                        <span class="font-medium"><?php echo min($pagina * $itens_por_pagina, $total_registros); ?></span> de
                        <span class="font-medium"><?php echo $total_registros; ?></span> resultados
                    </p>
                </div>
                <div>
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm space-x-1" aria-label="Pagination">
                        <?php if ($pagina > 1): ?>
                        <a href="?view=chamados_site&pagina=<?php echo $pagina - 1; ?>&tipo_solicitacao=<?php echo $filtros['tipo_solicitacao']; ?>&status=<?php echo $filtros['status']; ?>&data_inicio=<?php echo $filtros['data_inicio']; ?>&data_fim=<?php echo $filtros['data_fim']; ?>&busca=<?php echo urlencode($filtros['busca']); ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                            <span class="sr-only">Anterior</span>
                            <i class="fas fa-chevron-left"></i>
                        </a>
                        <?php endif; ?>

                        <?php
                        $start_page = max(1, $pagina - 2);
                        $end_page = min($total_paginas, $pagina + 2);

                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                        <a href="?view=chamados_site&pagina=<?php echo $i; ?>&tipo_solicitacao=<?php echo $filtros['tipo_solicitacao']; ?>&status=<?php echo $filtros['status']; ?>&data_inicio=<?php echo $filtros['data_inicio']; ?>&data_fim=<?php echo $filtros['data_fim']; ?>&busca=<?php echo urlencode($filtros['busca']); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium <?php echo $i == $pagina ? 'text-blue-600 bg-blue-50' : 'text-gray-700 hover:bg-gray-50'; ?>">
                            <?php echo $i; ?>
                        </a>
                        <?php endfor; ?>

                        <?php if ($pagina < $total_paginas): ?>
                        <a href="?view=chamados_site&pagina=<?php echo $pagina + 1; ?>&tipo_solicitacao=<?php echo $filtros['tipo_solicitacao']; ?>&status=<?php echo $filtros['status']; ?>&data_inicio=<?php echo $filtros['data_inicio']; ?>&data_fim=<?php echo $filtros['data_fim']; ?>&busca=<?php echo urlencode($filtros['busca']); ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
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


