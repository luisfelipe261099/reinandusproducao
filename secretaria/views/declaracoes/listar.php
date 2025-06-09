<?php if (isset($_SESSION['mensagem_verificacao'])): ?>
<div class="bg-<?php echo $_SESSION['mensagem_verificacao_tipo'] === 'sucesso' ? 'green' : 'red'; ?>-100 border-l-4 border-<?php echo $_SESSION['mensagem_verificacao_tipo'] === 'sucesso' ? 'green' : 'red'; ?>-500 text-<?php echo $_SESSION['mensagem_verificacao_tipo'] === 'sucesso' ? 'green' : 'red'; ?>-700 p-4 mb-6">
    <?php echo $_SESSION['mensagem_verificacao']; ?>
</div>
<?php
// Limpa a mensagem da sessão
unset($_SESSION['mensagem_verificacao']);
unset($_SESSION['mensagem_verificacao_tipo']);
endif;
?>

<!-- Estatísticas Rápidas -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <?php
    // Total de documentos
    $sql = "SELECT COUNT(*) as total FROM documentos_emitidos";
    $resultado = executarConsulta($db, $sql);
    $total_documentos = $resultado['total'] ?? 0;

    // Apenas declarações (tipo_documento_id = 2)
    $sql = "SELECT COUNT(*) as total FROM documentos_emitidos WHERE tipo_documento_id = 2";
    $resultado = executarConsulta($db, $sql);
    $total_declaracoes = $resultado['total'] ?? 0;

    // Documentos emitidos hoje
    $sql = "SELECT COUNT(*) as total FROM documentos_emitidos WHERE DATE(data_emissao) = CURDATE()";
    $resultado = executarConsulta($db, $sql);
    $emitidos_hoje = $resultado['total'] ?? 0;
    ?>

    <!-- Total de Documentos -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0 bg-blue-100 rounded-full p-3">
                <i class="fas fa-file-alt text-blue-500"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">Total de Documentos</p>
                <p class="text-2xl font-semibold text-gray-800"><?php echo $total_documentos; ?></p>
            </div>
        </div>
    </div>

    <!-- Declarações Emitidas -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0 bg-green-100 rounded-full p-3">
                <i class="fas fa-file-signature text-green-500"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">Declarações</p>
                <p class="text-2xl font-semibold text-gray-800"><?php echo $total_declaracoes; ?></p>
            </div>
        </div>
    </div>



    <!-- Emitidos Hoje -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0 bg-yellow-100 rounded-full p-3">
                <i class="fas fa-calendar-day text-yellow-500"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">Emitidos Hoje</p>
                <p class="text-2xl font-semibold text-gray-800"><?php echo $emitidos_hoje; ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Lista de Documentos -->
<div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
    <div class="p-6 border-b border-gray-200 flex justify-between items-center">
        <h3 class="text-xl font-semibold text-gray-800">Declarações Emitidas</h3>
        <div class="flex space-x-2">
            <a href="declaracoes.php?action=baixar_em_lote" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg text-sm">
                <i class="fas fa-download mr-2"></i> Baixar Declarações em Lote
            </a>
            <a href="declaracoes.php?action=selecionar_aluno" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm">
                <i class="fas fa-plus mr-2"></i> Emitir Nova Declaração
            </a>
        </div>
    </div>

    <!-- Filtros de busca -->
    <div class="p-6 border-b border-gray-200 bg-gray-50">
        <form action="declaracoes.php" method="get" class="space-y-4">
            <input type="hidden" name="action" value="listar">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="filtro_aluno" class="block text-sm font-medium text-gray-700 mb-1">Aluno</label>
                    <input type="text" id="filtro_aluno" name="filtro_aluno"
                           value="<?php echo htmlspecialchars($_GET['filtro_aluno'] ?? ''); ?>"
                           placeholder="Nome do aluno"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label for="filtro_tipo" class="block text-sm font-medium text-gray-700 mb-1">Tipo de Documento</label>
                    <select id="filtro_tipo" name="filtro_tipo"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Todos os tipos</option>
                        <?php
                        $sql_tipos = "SELECT id, nome FROM tipos_documentos ORDER BY nome";
                        $tipos = executarConsultaAll($db, $sql_tipos);
                        foreach ($tipos as $tipo):
                            $selected = ($_GET['filtro_tipo'] ?? '') == $tipo['id'] ? 'selected' : '';
                        ?>
                        <option value="<?php echo $tipo['id']; ?>" <?php echo $selected; ?>>
                            <?php echo htmlspecialchars($tipo['nome']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="filtro_data" class="block text-sm font-medium text-gray-700 mb-1">Data de Emissão</label>
                    <input type="date" id="filtro_data" name="filtro_data"
                           value="<?php echo htmlspecialchars($_GET['filtro_data'] ?? ''); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>

            <div class="flex justify-end space-x-2">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm">
                    <i class="fas fa-search mr-2"></i> Filtrar
                </button>
                <a href="declaracoes.php?action=listar" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg text-sm">
                    <i class="fas fa-eraser mr-2"></i> Limpar Filtros
                </a>
            </div>
        </form>
    </div>

    <?php
    // Consulta direta para verificar se existem documentos
    $sql_check = "SELECT COUNT(*) as total FROM documentos_emitidos";
    $check_result = $db->fetchOne($sql_check);
    $total_documentos_sistema = $check_result['total'] ?? 0;

    // Log para depuração
    error_log("Total de documentos no sistema: " . $total_documentos_sistema);

    // Construir a consulta com base nos filtros
    $where = [];
    $params = [];

    // Filtro fixo para mostrar apenas declarações (tipo_documento_id = 2)
    $where[] = "d.tipo_documento_id = ?";
    $params[] = 2;

    if (!empty($_GET['filtro_aluno'])) {
        $where[] = "a.nome LIKE ?";
        $params[] = "%" . $_GET['filtro_aluno'] . "%";
    }

    if (!empty($_GET['filtro_tipo']) && $_GET['filtro_tipo'] == 2) {
        // Já está filtrado para declarações, não precisa adicionar novamente
    }

    if (!empty($_GET['filtro_data'])) {
        $where[] = "DATE(d.data_emissao) = ?";
        $params[] = $_GET['filtro_data'];
    }

    $where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

    // Paginação
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 15;
    $offset = ($page - 1) * $limit;

    // Consulta simplificada para contar o total de registros
    if (empty($where)) {
        $total_registros = $total_documentos_sistema;
    } else {
        $sql_count = "SELECT COUNT(*) as total
                    FROM documentos_emitidos d
                    LEFT JOIN alunos a ON d.aluno_id = a.id
                    LEFT JOIN tipos_documentos td ON d.tipo_documento_id = td.id
                    $where_clause";
        $result_count = $db->fetchOne($sql_count, $params);
        $total_registros = $result_count['total'] ?? 0;
    }

    $total_paginas = ceil($total_registros / $limit);

    // Consulta ajustada para corresponder à estrutura real da tabela documentos_emitidos
    $sql = "SELECT d.id, d.numero_documento as numero, '' as titulo, d.data_emissao,
          d.codigo_verificacao, d.arquivo, d.aluno_id,
          a.nome as aluno_nome, a.cpf as aluno_cpf,
          td.nome as tipo_documento
       FROM documentos_emitidos d
       LEFT JOIN alunos a ON d.aluno_id = a.id
       LEFT JOIN tipos_documentos td ON d.tipo_documento_id = td.id
       $where_clause
       ORDER BY d.data_emissao DESC
       LIMIT $limit OFFSET $offset";

    try {
        // Adicione logs para depuração
        error_log("SQL: " . $sql);
        error_log("Params: " . json_encode($params));

        $documentos = $db->fetchAll($sql, $params);
        error_log("Documentos encontrados: " . count($documentos));

        // Se não encontrou documentos com os filtros, tente uma consulta mais simples
        if (empty($documentos) && !empty($where)) {
            error_log("Tentando consulta sem filtros como fallback");
            $sql_fallback = "SELECT d.id, d.numero_documento as numero, '' as titulo, d.data_emissao,
                      d.codigo_verificacao, d.arquivo, d.aluno_id,
                      a.nome as aluno_nome, a.cpf as aluno_cpf,
                      td.nome as tipo_documento
                   FROM documentos_emitidos d
                   LEFT JOIN alunos a ON d.aluno_id = a.id
                   LEFT JOIN tipos_documentos td ON d.tipo_documento_id = td.id
                   ORDER BY d.data_emissao DESC
                   LIMIT $limit";
            $documentos = $db->fetchAll($sql_fallback);
            error_log("Documentos encontrados (fallback): " . count($documentos));
        }
    } catch (Exception $e) {
        error_log("Erro ao buscar documentos: " . $e->getMessage());
        $documentos = [];
    }
    ?>

    <?php if (count($documentos) > 0): ?>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Número</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Título</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aluno</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data Emissão</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($documentos as $doc): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php echo htmlspecialchars($doc['numero'] ?? '-'); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php echo htmlspecialchars($doc['titulo'] ?? $doc['tipo_documento'] ?? '-'); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php if (!empty($doc['aluno_id']) && !empty($doc['aluno_nome'])): ?>
                        <a href="alunos.php?action=visualizar&id=<?php echo $doc['aluno_id']; ?>" class="text-blue-600 hover:text-blue-900">
                            <?php echo htmlspecialchars($doc['aluno_nome']); ?>
                        </a>
                        <?php else: ?>
                        <?php echo htmlspecialchars($doc['aluno_nome'] ?? '-'); ?>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php echo htmlspecialchars($doc['tipo_documento'] ?? '-'); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php echo !empty($doc['data_emissao']) ? date('d/m/Y', strtotime($doc['data_emissao'])) : '-'; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div class="flex justify-end space-x-3">
                            <?php if (!empty($doc['id'])): ?>
                            <a href="documentos.php?action=visualizar&id=<?php echo $doc['id']; ?>" class="text-blue-600 hover:text-blue-900" title="Visualizar">
                                <i class="fas fa-eye"></i>
                            </a>
                            <?php if (!empty($doc['arquivo'])): ?>
                            <a href="documentos.php?action=download&id=<?php echo $doc['id']; ?>" class="text-green-600 hover:text-green-900" title="Download">
                                <i class="fas fa-download"></i>
                            </a>
                            <?php endif; ?>
                            <?php else: ?>
                            <span class="text-gray-400"><i class="fas fa-eye-slash"></i></span>
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
    <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200">
        <div class="flex-1 flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-700">
                    Mostrando <span class="font-medium"><?php echo ($page - 1) * $limit + 1; ?></span> a
                    <span class="font-medium"><?php echo min($page * $limit, $total_registros); ?></span> de
                    <span class="font-medium"><?php echo $total_registros; ?></span> documentos
                </p>
            </div>
            <div>
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                    <?php if ($page > 1): ?>
                    <a href="?action=listar&page=<?php echo $page-1; ?><?php echo !empty($_GET['filtro_aluno']) ? '&filtro_aluno='.urlencode($_GET['filtro_aluno']) : ''; ?><?php echo !empty($_GET['filtro_tipo']) ? '&filtro_tipo='.$_GET['filtro_tipo'] : ''; ?><?php echo !empty($_GET['filtro_data']) ? '&filtro_data='.$_GET['filtro_data'] : ''; ?>"
                       class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                        <span class="sr-only">Anterior</span>
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($total_paginas, $page + 2); $i++): ?>
                    <a href="?action=listar&page=<?php echo $i; ?><?php echo !empty($_GET['filtro_aluno']) ? '&filtro_aluno='.urlencode($_GET['filtro_aluno']) : ''; ?><?php echo !empty($_GET['filtro_tipo']) ? '&filtro_tipo='.$_GET['filtro_tipo'] : ''; ?><?php echo !empty($_GET['filtro_data']) ? '&filtro_data='.$_GET['filtro_data'] : ''; ?>"
                       class="relative inline-flex items-center px-4 py-2 border border-gray-300 <?php echo $i == $page ? 'bg-blue-50 text-blue-600 z-10' : 'bg-white text-gray-500 hover:bg-gray-50'; ?> text-sm font-medium">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_paginas): ?>
                    <a href="?action=listar&page=<?php echo $page+1; ?><?php echo !empty($_GET['filtro_aluno']) ? '&filtro_aluno='.urlencode($_GET['filtro_aluno']) : ''; ?><?php echo !empty($_GET['filtro_tipo']) ? '&filtro_tipo='.$_GET['filtro_tipo'] : ''; ?><?php echo !empty($_GET['filtro_data']) ? '&filtro_data='.$_GET['filtro_data'] : ''; ?>"
                       class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                        <span class="sr-only">Próximo</span>
                        <i class="fas fa-chevron-right"></i>
                    </a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <div class="p-6 text-center">
        <div class="py-12">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-blue-100 text-blue-500 mb-4">
                <i class="fas fa-file-alt text-2xl"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Nenhum documento encontrado</h3>

            <?php if ($total_documentos_sistema == 0): ?>
            <p class="text-gray-500 mb-6">Não há documentos emitidos no sistema. Emita um documento para começar.</p>
            <?php else: ?>
            <p class="text-gray-500 mb-6">Não foram encontrados documentos com os filtros selecionados.</p>
            <?php endif; ?>

            <div class="flex justify-center space-x-4">
                <?php if (!empty($_GET['filtro_aluno']) || !empty($_GET['filtro_tipo']) || !empty($_GET['filtro_data'])): ?>
                <a href="documentos.php?action=listar" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg text-sm">
                    <i class="fas fa-sync-alt mr-2"></i> Limpar Filtros
                </a>
                <?php endif; ?>
                <a href="documentos.php?action=selecionar_aluno" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm">
                    <i class="fas fa-plus mr-2"></i> Emitir Novo Documento
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>







