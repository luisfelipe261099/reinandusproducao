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

        <?php if (getUsuarioTipo() != 'polo' || usuarioTemPermissao('chamados', 'criar')): ?>
        <div>
            <a href="novo.php" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                <i class="fas fa-plus mr-2"></i> Novo Chamado
            </a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Filtros -->
    <div class="mb-6 bg-gray-50 p-4 rounded-lg">
        <form action="" method="get" class="flex flex-wrap items-end gap-4">
            <input type="hidden" name="view" value="solicitacoes">

            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" id="status" class="border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Todos</option>
                    <option value="solicitado" <?php echo $filtros['status'] == 'solicitado' ? 'selected' : ''; ?>>Solicitado</option>
                    <option value="processando" <?php echo $filtros['status'] == 'processando' ? 'selected' : ''; ?>>Processando</option>
                    <option value="pronto" <?php echo $filtros['status'] == 'pronto' ? 'selected' : ''; ?>>Pronto</option>
                    <option value="entregue" <?php echo $filtros['status'] == 'entregue' ? 'selected' : ''; ?>>Entregue</option>
                    <option value="cancelado" <?php echo $filtros['status'] == 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                </select>
            </div>

            <?php if (getUsuarioTipo() != 'polo'): ?>
            <div>
                <label for="polo_id" class="block text-sm font-medium text-gray-700 mb-1">Polo</label>
                <select name="polo_id" id="polo_id" class="border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Todos</option>
                    <?php foreach ($polos as $polo): ?>
                    <option value="<?php echo $polo['id']; ?>" <?php echo $filtros['polo_id'] == $polo['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($polo['nome'] ?? ''); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div>
                <label for="data_inicio" class="block text-sm font-medium text-gray-700 mb-1">Data Início</label>
                <input type="date" name="data_inicio" id="data_inicio" value="<?php echo $filtros['data_inicio']; ?>" class="border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label for="data_fim" class="block text-sm font-medium text-gray-700 mb-1">Data Fim</label>
                <input type="date" name="data_fim" id="data_fim" value="<?php echo $filtros['data_fim']; ?>" class="border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label for="busca" class="block text-sm font-medium text-gray-700 mb-1">Busca</label>
                <input type="text" name="busca" id="busca" value="<?php echo htmlspecialchars($filtros['busca'] ?? ''); ?>" placeholder="Nome ou CPF do aluno" class="border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    <i class="fas fa-search mr-2"></i> Filtrar
                </button>
            </div>

            <div>
                <a href="?view=solicitacoes" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
                    <i class="fas fa-times mr-2"></i> Limpar
                </a>
            </div>
        </form>
    </div>

    <!-- Tabela de Solicitações -->
    <?php if (empty($solicitacoes)): ?>
    <div class="bg-yellow-50 p-4 rounded-lg text-center text-yellow-800 border border-yellow-200">
        <i class="fas fa-exclamation-circle text-2xl mb-2"></i>
        <p>Nenhuma solicitação de documento encontrada com os filtros informados.</p>
    </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border border-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="py-3 px-4 border-b text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                    <th class="py-3 px-4 border-b text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aluno</th>
                    <th class="py-3 px-4 border-b text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Documento</th>
                    <th class="py-3 px-4 border-b text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Polo</th>
                    <th class="py-3 px-4 border-b text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Solicitante</th>
                    <th class="py-3 px-4 border-b text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                    <th class="py-3 px-4 border-b text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="py-3 px-4 border-b text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach ($solicitacoes as $solicitacao): ?>
                <tr class="hover:bg-gray-50">
                    <td class="py-3 px-4 text-sm"><?php echo $solicitacao['id']; ?></td>
                    <td class="py-3 px-4">
                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($solicitacao['aluno_nome'] ?? ''); ?></div>
                        <div class="text-xs text-gray-500"><?php echo formatarCpf($solicitacao['aluno_cpf']); ?></div>
                    </td>
                    <td class="py-3 px-4">
                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($solicitacao['tipo_documento_nome'] ?? ''); ?></div>
                        <div class="text-xs text-gray-500">Qtd: <?php echo $solicitacao['quantidade']; ?></div>
                    </td>
                    <td class="py-3 px-4 text-sm"><?php echo htmlspecialchars($solicitacao['polo_nome'] ?? ''); ?></td>
                    <td class="py-3 px-4 text-sm"><?php echo htmlspecialchars($solicitacao['solicitante_nome'] ?? ''); ?></td>
                    <td class="py-3 px-4">
                        <div class="text-sm text-gray-900"><?php echo date('d/m/Y', strtotime($solicitacao['created_at'])); ?></div>
                        <div class="text-xs text-gray-500"><?php echo date('H:i', strtotime($solicitacao['created_at'])); ?></div>
                    </td>
                    <td class="py-3 px-4">
                        <?php
                        $status_class = '';
                        $status_text = '';

                        switch ($solicitacao['status']) {
                            case 'solicitado':
                                $status_class = 'bg-yellow-100 text-yellow-800';
                                $status_text = 'Solicitado';
                                break;
                            case 'processando':
                                $status_class = 'bg-blue-100 text-blue-800';
                                $status_text = 'Processando';
                                break;
                            case 'pronto':
                                $status_class = 'bg-green-100 text-green-800';
                                $status_text = 'Pronto';
                                break;
                            case 'entregue':
                                $status_class = 'bg-indigo-100 text-indigo-800';
                                $status_text = 'Entregue';
                                break;
                            case 'cancelado':
                                $status_class = 'bg-red-100 text-red-800';
                                $status_text = 'Cancelado';
                                break;
                        }
                        ?>
                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                            <?php echo $status_text; ?>
                        </span>
                    </td>
                    <td class="py-3 px-4 text-sm">
                        <a href="ver_solicitacao.php?id=<?php echo $solicitacao['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-2" title="Visualizar">
                            <i class="fas fa-eye"></i>
                        </a>

                        <?php if (getUsuarioTipo() != 'polo' && $solicitacao['status'] == 'solicitado'): ?>
                        <a href="responder_solicitacao.php?id=<?php echo $solicitacao['id']; ?>" class="text-green-600 hover:text-green-900 mr-2" title="Responder">
                            <i class="fas fa-reply"></i>
                        </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Paginação -->
    <?php if ($total_paginas > 1): ?>
    <div class="mt-6 flex justify-center">
        <div class="flex space-x-1">
            <?php if ($pagina > 1): ?>
            <a href="?view=solicitacoes&pagina=<?php echo $pagina - 1; ?><?php echo isset($_GET['status']) ? '&status=' . urlencode($_GET['status']) : ''; ?><?php echo isset($_GET['polo_id']) ? '&polo_id=' . urlencode($_GET['polo_id']) : ''; ?><?php echo isset($_GET['data_inicio']) ? '&data_inicio=' . urlencode($_GET['data_inicio']) : ''; ?><?php echo isset($_GET['data_fim']) ? '&data_fim=' . urlencode($_GET['data_fim']) : ''; ?><?php echo isset($_GET['busca']) ? '&busca=' . urlencode($_GET['busca']) : ''; ?>" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white rounded-md border border-gray-300 hover:bg-gray-50">
                Anterior
            </a>
            <?php endif; ?>

            <?php
            $inicio = max(1, $pagina - 2);
            $fim = min($total_paginas, $pagina + 2);

            if ($inicio > 1) {
                echo '<span class="px-4 py-2 text-sm font-medium text-gray-700">...</span>';
            }

            for ($i = $inicio; $i <= $fim; $i++) {
                if ($i == $pagina) {
                    echo '<span class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md">' . $i . '</span>';
                } else {
                    echo '<a href="?view=solicitacoes&pagina=' . $i . (isset($_GET['status']) ? '&status=' . urlencode($_GET['status']) : '') . (isset($_GET['polo_id']) ? '&polo_id=' . urlencode($_GET['polo_id']) : '') . (isset($_GET['data_inicio']) ? '&data_inicio=' . urlencode($_GET['data_inicio']) : '') . (isset($_GET['data_fim']) ? '&data_fim=' . urlencode($_GET['data_fim']) : '') . (isset($_GET['busca']) ? '&busca=' . urlencode($_GET['busca']) : '') . '" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white rounded-md border border-gray-300 hover:bg-gray-50">' . $i . '</a>';
                }
            }

            if ($fim < $total_paginas) {
                echo '<span class="px-4 py-2 text-sm font-medium text-gray-700">...</span>';
            }
            ?>

            <?php if ($pagina < $total_paginas): ?>
            <a href="?view=solicitacoes&pagina=<?php echo $pagina + 1; ?><?php echo isset($_GET['status']) ? '&status=' . urlencode($_GET['status']) : ''; ?><?php echo isset($_GET['polo_id']) ? '&polo_id=' . urlencode($_GET['polo_id']) : ''; ?><?php echo isset($_GET['data_inicio']) ? '&data_inicio=' . urlencode($_GET['data_inicio']) : ''; ?><?php echo isset($_GET['data_fim']) ? '&data_fim=' . urlencode($_GET['data_fim']) : ''; ?><?php echo isset($_GET['busca']) ? '&busca=' . urlencode($_GET['busca']) : ''; ?>" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white rounded-md border border-gray-300 hover:bg-gray-50">
                Próxima
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <!-- Contagem de registros -->
    <div class="mt-4 text-sm text-gray-600">
        <?php echo $total_registros; ?> registro(s) encontrado(s)
    </div>
</div>