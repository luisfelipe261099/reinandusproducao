<?php
// As funções executarConsulta e executarConsultaAll já estão definidas no arquivo principal

// Configuração da paginação
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$itens_por_pagina = 10;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

// Filtros de busca
$busca = $_GET['busca'] ?? '';
$status_filtro = $_GET['status'] ?? '';

// Construção da consulta SQL
$where_conditions = [];
$params = [];

if (!empty($busca)) {
    // Busca em todos os campos relevantes
    $where_conditions[] = "(p.nome LIKE ? OR p.responsavel LIKE ? OR p.cnpj LIKE ? OR p.telefone LIKE ? OR p.email LIKE ? OR p.cidade LIKE ?)";
    $params[] = "%$busca%";
    $params[] = "%$busca%";
    $params[] = "%$busca%";
    $params[] = "%$busca%";
    $params[] = "%$busca%";
    $params[] = "%$busca%";
}

if (!empty($status_filtro)) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filtro;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Consulta para contar o total de registros
$sql_count = "SELECT COUNT(*) as total FROM polos p $where_clause";
$resultado = executarConsulta($db, $sql_count, $params);
$total_registros = $resultado['total'] ?? 0;

// Cálculo do total de páginas
$total_paginas = ceil($total_registros / $itens_por_pagina);

// Consulta para buscar os polos
$sql = "SELECT p.* FROM polos p $where_clause ORDER BY p.nome ASC LIMIT $itens_por_pagina OFFSET $offset";
$polos = executarConsultaAll($db, $sql, $params);

?>
<div class="container mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Polos Educacionais</h1>
        <a href="polos.php?action=novo" class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded">
            <i class="fas fa-plus mr-2"></i> Novo Polo
        </a>
    </div>

    <?php if (isset($_SESSION['mensagem']) && isset($_SESSION['mensagem_tipo'])): ?>
    <div class="bg-<?php echo $_SESSION['mensagem_tipo'] === 'sucesso' ? 'green' : 'red'; ?>-100 border-l-4 border-<?php echo $_SESSION['mensagem_tipo'] === 'sucesso' ? 'green' : 'red'; ?>-500 text-<?php echo $_SESSION['mensagem_tipo'] === 'sucesso' ? 'green' : 'red'; ?>-700 p-4 mb-6">
        <?php
        // Verifica se a mensagem é um array e converte para string se necessário
        if (is_array($_SESSION['mensagem'])) {
            echo "Mensagem do sistema: " . print_r($_SESSION['mensagem'], true);
        } else {
            echo $_SESSION['mensagem'];
        }
        ?>
    </div>
    <?php
    // Limpa a mensagem da sessão
    unset($_SESSION['mensagem']);
    unset($_SESSION['mensagem_tipo']);
    endif;
    ?>

    <!-- Filtros de busca -->
    <div class="bg-white shadow-md rounded-lg p-6 mb-6">
        <form action="polos.php" method="GET" class="flex flex-wrap items-end space-y-4 md:space-y-0 md:space-x-4">
            <div class="w-full md:w-1/3">
                <label for="busca" class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                <input type="text" id="busca" name="busca" value="<?php echo htmlspecialchars($busca); ?>" placeholder="Nome, cidade ou estado" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
            </div>

            <div class="w-full md:w-1/4">
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select id="status" name="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    <option value="">Todos</option>
                    <option value="ativo" <?php echo $status_filtro === 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                    <option value="inativo" <?php echo $status_filtro === 'inativo' ? 'selected' : ''; ?>>Inativo</option>
                </select>
            </div>

            <div class="flex space-x-2">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded">
                    <i class="fas fa-search mr-2"></i> Filtrar
                </button>

                <a href="polos.php" class="bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded">
                    <i class="fas fa-times mr-2"></i> Limpar
                </a>
            </div>
        </form>
    </div>

    <!-- Tabela de polos -->
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <?php if (empty($polos)): ?>
        <div class="p-6 text-center text-gray-500">
            <p>Nenhum polo encontrado.</p>
        </div>
        <?php else: ?>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cidade/UF</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Responsável</th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($polos as $polo): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">
                            <a href="polos.php?action=visualizar&id=<?php echo $polo['id']; ?>" class="hover:text-blue-600">
                                <?php echo htmlspecialchars($polo['nome']); ?>
                            </a>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">
                            <?php
                            // Exibe o campo cidade (texto)
                            echo !empty($polo['cidade']) ? htmlspecialchars($polo['cidade']) : 'Não informado';
                            ?>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">
                            <?php echo htmlspecialchars($polo['responsavel'] ?? ''); ?>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo ($polo['status'] ?? '') === 'ativo' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                            <?php echo ($polo['status'] ?? '') === 'ativo' ? 'Ativo' : 'Inativo'; ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                        <a href="polos.php?action=visualizar&id=<?php echo $polo['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3" title="Visualizar">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="polos.php?action=editar&id=<?php echo $polo['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="polos.php?action=excluir&id=<?php echo $polo['id']; ?>" class="text-red-600 hover:text-red-900" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir este polo?');">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Paginação -->
        <?php if ($total_paginas > 1): ?>
        <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm text-gray-700">
                        Mostrando <span class="font-medium"><?php echo ($offset + 1); ?></span> a
                        <span class="font-medium"><?php echo min($offset + $itens_por_pagina, $total_registros); ?></span> de
                        <span class="font-medium"><?php echo $total_registros; ?></span> resultados
                    </p>
                </div>
                <div>
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                        <?php if ($pagina_atual > 1): ?>
                        <a href="polos.php?pagina=<?php echo ($pagina_atual - 1); ?>&busca=<?php echo urlencode($busca); ?>&status=<?php echo urlencode($status_filtro); ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                            <span class="sr-only">Anterior</span>
                            <i class="fas fa-chevron-left"></i>
                        </a>
                        <?php endif; ?>

                        <?php for ($i = max(1, $pagina_atual - 2); $i <= min($total_paginas, $pagina_atual + 2); $i++): ?>
                        <a href="polos.php?pagina=<?php echo $i; ?>&busca=<?php echo urlencode($busca); ?>&status=<?php echo urlencode($status_filtro); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 <?php echo $i === $pagina_atual ? 'bg-blue-50 text-blue-600' : 'bg-white text-gray-500 hover:bg-gray-50'; ?> text-sm font-medium">
                            <?php echo $i; ?>
                        </a>
                        <?php endfor; ?>

                        <?php if ($pagina_atual < $total_paginas): ?>
                        <a href="polos.php?pagina=<?php echo ($pagina_atual + 1); ?>&busca=<?php echo urlencode($busca); ?>&status=<?php echo urlencode($status_filtro); ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
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
</div>