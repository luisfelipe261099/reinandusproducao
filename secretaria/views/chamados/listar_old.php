<?php
// Define o título da página
$titulo_pagina = 'Listagem de Chamados';

// Obtém os filtros
$filtro_status = isset($_GET['status']) ? $_GET['status'] : '';
$filtro_tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
$filtro_departamento = isset($_GET['departamento']) ? $_GET['departamento'] : '';
$filtro_categoria = isset($_GET['categoria']) ? (int)$_GET['categoria'] : 0;
$filtro_data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : '';
$filtro_data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : '';
$filtro_busca = isset($_GET['busca']) ? $_GET['busca'] : '';

// Constrói a consulta SQL
$sql = "SELECT c.*, cc.nome as categoria_nome, cc.cor as categoria_cor, cc.icone as categoria_icone,
               u_solicitante.nome as solicitante_nome, u_responsavel.nome as responsavel_nome,
               p.nome as polo_nome, a.nome as aluno_nome
        FROM chamados c
        JOIN categorias_chamados cc ON c.categoria_id = cc.id
        JOIN usuarios u_solicitante ON c.solicitante_id = u_solicitante.id
        LEFT JOIN usuarios u_responsavel ON c.responsavel_id = u_responsavel.id
        LEFT JOIN polos p ON c.polo_id = p.id
        LEFT JOIN alunos a ON c.aluno_id = a.id
        WHERE 1=1";

$params = [];

// Aplica os filtros
if ($is_polo) {
    // Se for um polo, mostra apenas os chamados do polo
    $sql .= " AND c.polo_id = ?";
    $params[] = $polo_id;
} elseif ($usuario_tipo !== 'admin_master' && $usuario_tipo !== 'diretoria') {
    // Se não for admin ou diretoria, mostra apenas os chamados do departamento do usuário
    $departamento_usuario = '';
    switch ($usuario_tipo) {
        case 'secretaria_academica':
        case 'secretaria_documentos':
            $departamento_usuario = 'secretaria';
            break;
        case 'financeiro':
            $departamento_usuario = 'financeiro';
            break;
        default:
            $departamento_usuario = strtolower($usuario_tipo);
            break;
    }

    $sql .= " AND (c.departamento = ? OR c.solicitante_id = ? OR c.responsavel_id = ?)";
    $params[] = $departamento_usuario;
    $params[] = $_SESSION['usuario']['id'];
    $params[] = $_SESSION['usuario']['id'];
}

if (!empty($filtro_status)) {
    $sql .= " AND c.status = ?";
    $params[] = $filtro_status;
}

if (!empty($filtro_tipo)) {
    $sql .= " AND c.tipo = ?";
    $params[] = $filtro_tipo;
}

if (!empty($filtro_departamento)) {
    $sql .= " AND c.departamento = ?";
    $params[] = $filtro_departamento;
}

if (!empty($filtro_categoria)) {
    $sql .= " AND c.categoria_id = ?";
    $params[] = $filtro_categoria;
}

if (!empty($filtro_data_inicio)) {
    $sql .= " AND DATE(c.data_abertura) >= ?";
    $params[] = $filtro_data_inicio;
}

if (!empty($filtro_data_fim)) {
    $sql .= " AND DATE(c.data_abertura) <= ?";
    $params[] = $filtro_data_fim;
}

if (!empty($filtro_busca)) {
    $sql .= " AND (c.codigo LIKE ? OR c.titulo LIKE ? OR c.descricao LIKE ?)";
    $busca = "%{$filtro_busca}%";
    $params[] = $busca;
    $params[] = $busca;
    $params[] = $busca;
}

// Ordena os resultados
$sql .= " ORDER BY c.data_abertura DESC";

// Executa a consulta
$chamados = $db->fetchAll($sql, $params);

// Obtém as categorias para o filtro
$sql_categorias = "SELECT * FROM categorias_chamados WHERE status = 'ativo'";
if ($is_polo) {
    $sql_categorias .= " AND tipo = 'polo'";
}
$sql_categorias .= " ORDER BY ordem ASC";
$categorias = $db->fetchAll($sql_categorias);

// Não é necessário incluir o cabeçalho aqui, pois já está incluído no arquivo principal
?>

<div class="container mx-auto">

    <!-- Filtros -->
    <div class="bg-white shadow-md rounded-lg p-4 mb-6">
        <h2 class="text-lg font-semibold mb-4">Filtros</h2>

        <form action="chamados.php" method="GET" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    <option value="">Todos</option>
                    <option value="aberto" <?php echo $filtro_status === 'aberto' ? 'selected' : ''; ?>>Aberto</option>
                    <option value="em_andamento" <?php echo $filtro_status === 'em_andamento' ? 'selected' : ''; ?>>Em Andamento</option>
                    <option value="aguardando_resposta" <?php echo $filtro_status === 'aguardando_resposta' ? 'selected' : ''; ?>>Aguardando Resposta</option>
                    <option value="aguardando_aprovacao" <?php echo $filtro_status === 'aguardando_aprovacao' ? 'selected' : ''; ?>>Aguardando Aprovação</option>
                    <option value="resolvido" <?php echo $filtro_status === 'resolvido' ? 'selected' : ''; ?>>Resolvido</option>
                    <option value="cancelado" <?php echo $filtro_status === 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                    <option value="fechado" <?php echo $filtro_status === 'fechado' ? 'selected' : ''; ?>>Fechado</option>
                </select>
            </div>

            <?php if (!$is_polo): ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                <select name="tipo" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    <option value="">Todos</option>
                    <option value="interno" <?php echo $filtro_tipo === 'interno' ? 'selected' : ''; ?>>Interno</option>
                    <option value="polo" <?php echo $filtro_tipo === 'polo' ? 'selected' : ''; ?>>Polo</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Departamento</label>
                <select name="departamento" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    <option value="">Todos</option>
                    <option value="secretaria" <?php echo $filtro_departamento === 'secretaria' ? 'selected' : ''; ?>>Secretaria</option>
                    <option value="financeiro" <?php echo $filtro_departamento === 'financeiro' ? 'selected' : ''; ?>>Financeiro</option>
                    <option value="suporte" <?php echo $filtro_departamento === 'suporte' ? 'selected' : ''; ?>>Suporte</option>
                    <option value="diretoria" <?php echo $filtro_departamento === 'diretoria' ? 'selected' : ''; ?>>Diretoria</option>
                </select>
            </div>
            <?php endif; ?>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Categoria</label>
                <select name="categoria" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    <option value="">Todas</option>
                    <?php foreach ($categorias as $categoria): ?>
                    <option value="<?php echo $categoria['id']; ?>" <?php echo $filtro_categoria == $categoria['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($categoria['nome']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Data Início</label>
                <input type="date" name="data_inicio" value="<?php echo $filtro_data_inicio; ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Data Fim</label>
                <input type="date" name="data_fim" value="<?php echo $filtro_data_fim; ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Busca</label>
                <input type="text" name="busca" value="<?php echo htmlspecialchars($filtro_busca); ?>" placeholder="Código, título ou descrição" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
            </div>

            <div class="flex items-end">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    <i class="fas fa-search mr-2"></i> Filtrar
                </button>

                <a href="chamados.php" class="ml-2 bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
                    <i class="fas fa-times mr-2"></i> Limpar
                </a>
            </div>
        </form>
    </div>

    <!-- Listagem de Chamados -->
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <?php if (empty($chamados)): ?>
        <div class="p-6 text-center">
            <p class="text-gray-500">Nenhum chamado encontrado.</p>
        </div>
        <?php else: ?>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Título</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categoria</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Abertura</th>
                    <?php if (!$is_polo): ?>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Solicitante</th>
                    <?php endif; ?>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($chamados as $chamado): ?>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        <?php echo htmlspecialchars($chamado['codigo']); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?php echo htmlspecialchars($chamado['titulo']); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" style="background-color: <?php echo $chamado['categoria_cor']; ?>; color: white;">
                            <i class="fas fa-<?php echo $chamado['categoria_icone']; ?> mr-1"></i>
                            <?php echo htmlspecialchars($chamado['categoria_nome']); ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php
                        $status_class = '';
                        $status_text = '';

                        switch ($chamado['status']) {
                            case 'aberto':
                                $status_class = 'bg-blue-100 text-blue-800';
                                $status_text = 'Aberto';
                                break;
                            case 'em_andamento':
                                $status_class = 'bg-yellow-100 text-yellow-800';
                                $status_text = 'Em Andamento';
                                break;
                            case 'aguardando_resposta':
                                $status_class = 'bg-purple-100 text-purple-800';
                                $status_text = 'Aguardando Resposta';
                                break;
                            case 'aguardando_aprovacao':
                                $status_class = 'bg-indigo-100 text-indigo-800';
                                $status_text = 'Aguardando Aprovação';
                                break;
                            case 'resolvido':
                                $status_class = 'bg-green-100 text-green-800';
                                $status_text = 'Resolvido';
                                break;
                            case 'cancelado':
                                $status_class = 'bg-red-100 text-red-800';
                                $status_text = 'Cancelado';
                                break;
                            case 'fechado':
                                $status_class = 'bg-gray-100 text-gray-800';
                                $status_text = 'Fechado';
                                break;
                        }
                        ?>
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                            <?php echo $status_text; ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?php echo date('d/m/Y H:i', strtotime($chamado['data_abertura'])); ?>
                    </td>
                    <?php if (!$is_polo): ?>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?php echo htmlspecialchars($chamado['solicitante_nome']); ?>
                        <?php if ($chamado['polo_id']): ?>
                        <br><span class="text-xs text-gray-400">Polo: <?php echo htmlspecialchars($chamado['polo_nome']); ?></span>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <a href="chamados.php?action=visualizar&id=<?php echo $chamado['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                            <i class="fas fa-eye"></i>
                        </a>

                        <?php if ($permissoes['nivel_acesso'] === 'editar' || $permissoes['nivel_acesso'] === 'total'): ?>
                        <a href="chamados.php?action=alterar_status&id=<?php echo $chamado['id']; ?>" class="text-yellow-600 hover:text-yellow-900 mr-3">
                            <i class="fas fa-edit"></i>
                        </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

<?php
// Não é necessário incluir o rodapé aqui, pois já está incluído no arquivo principal
?>
