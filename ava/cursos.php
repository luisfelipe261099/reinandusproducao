<?php
/**
 * Listagem de Cursos do AVA
 * Exibe os cursos do polo no Ambiente Virtual de Aprendizagem
 */

// Inicializa o sistema
require_once '../includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica se o usuário é do tipo polo
if (getUsuarioTipo() !== 'polo') {
    setMensagem('erro', 'Você não tem permissão para acessar esta página.');
    redirect('../polo/index.php');
    exit;
}

// Instancia o banco de dados
$db = Database::getInstance();

// Obtém o ID do polo
$polo_id = getUsuarioPoloId();

// Verifica se o polo tem acesso ao AVA
if (!$polo_id) {
    setMensagem('erro', 'Não foi possível identificar o polo associado ao seu usuário. Entre em contato com a secretaria para mais informações.');
    redirect('../polo/index.php');
    exit;
}

// Verifica se o polo existe
$sql = "SELECT * FROM polos WHERE id = ?";
$polo = $db->fetchOne($sql, [$polo_id]);

if (!$polo) {
    setMensagem('erro', 'Polo não encontrado no sistema. Entre em contato com a secretaria para mais informações.');
    redirect('../polo/index.php');
    exit;
}

// Verifica se o polo tem acesso ao AVA
$sql = "SELECT * FROM ava_polos_acesso WHERE polo_id = ?";
$acesso = $db->fetchOne($sql, [$polo_id]);

if (!$acesso || $acesso['liberado'] != 1) {
    setMensagem('erro', 'Seu polo não possui acesso liberado ao AVA. Entre em contato com a secretaria para mais informações.');
    redirect('../polo/index.php');
    exit;
}

// Parâmetros de paginação
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$itens_por_pagina = 5;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

// Parâmetros de filtro
$filtro_status = isset($_GET['status']) ? $_GET['status'] : '';
$filtro_categoria = isset($_GET['categoria']) ? $_GET['categoria'] : '';
$filtro_busca = isset($_GET['busca']) ? $_GET['busca'] : '';

// Constrói a consulta SQL com filtros
$sql_where = "WHERE ac.polo_id = ?";
$params = [$polo_id];

if (!empty($filtro_status)) {
    $sql_where .= " AND ac.status = ?";
    $params[] = $filtro_status;
}

if (!empty($filtro_categoria)) {
    $sql_where .= " AND ac.categoria = ?";
    $params[] = $filtro_categoria;
}

if (!empty($filtro_busca)) {
    $sql_where .= " AND (ac.titulo LIKE ? OR ac.descricao LIKE ?)";
    $params[] = "%$filtro_busca%";
    $params[] = "%$filtro_busca%";
}

// Conta o total de cursos
$sql = "SELECT COUNT(*) as total FROM ava_cursos ac $sql_where";
$resultado = $db->fetchOne($sql, $params);
$total_cursos = $resultado['total'] ?? 0;

// Calcula o total de páginas
$total_paginas = ceil($total_cursos / $itens_por_pagina);

// Busca os cursos com paginação
$sql = "SELECT ac.*,
        (SELECT COUNT(*) FROM ava_matriculas am WHERE am.curso_id = ac.id) as total_alunos,
        (SELECT COUNT(*) FROM ava_modulos am WHERE am.curso_id = ac.id) as total_modulos,
        cat.nome as categoria_nome, cat.cor as categoria_cor
        FROM ava_cursos ac
        LEFT JOIN ava_categorias cat ON ac.categoria = cat.nome
        $sql_where
        ORDER BY ac.created_at DESC
        LIMIT $itens_por_pagina OFFSET $offset";
$cursos = $db->fetchAll($sql, $params);

// Busca as categorias disponíveis para o filtro
$sql = "SELECT DISTINCT ac.categoria, cat.nome as categoria_nome, cat.cor as categoria_cor
        FROM ava_cursos ac
        LEFT JOIN ava_categorias cat ON ac.categoria = cat.nome
        WHERE ac.polo_id = ?
        ORDER BY ac.categoria";
$categorias = $db->fetchAll($sql, [$polo_id]);

// Define o título da página
$titulo_pagina = 'Cursos do AVA';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faciência ERP - <?php echo $titulo_pagina; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .curso-card {
            border: 1px solid #E5E7EB;
            border-radius: 0.5rem;
            overflow: hidden;
            transition: all 0.2s;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .curso-card:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transform: translateY(-2px);
        }
        .curso-header {
            height: 160px;
            background-size: cover;
            background-position: center;
            position: relative;
        }
        .curso-header-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to bottom, rgba(0,0,0,0.1), rgba(0,0,0,0.7));
            padding: 1rem;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
        }
        .curso-title {
            color: white;
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }
        .curso-category {
            position: absolute;
            top: 0.75rem;
            right: 0.75rem;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            color: white;
        }
        .curso-body {
            padding: 1rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .curso-stats {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
            font-size: 0.875rem;
            color: #6B7280;
        }
        .curso-description {
            color: #6B7280;
            font-size: 0.875rem;
            margin-bottom: 1rem;
            flex: 1;
        }
        .curso-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 0.75rem;
            border-top: 1px solid #E5E7EB;
            margin-top: auto;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 9999px;
        }
        .status-rascunho { background-color: #F3F4F6; color: #6B7280; }
        .status-revisao { background-color: #FEF3C7; color: #D97706; }
        .status-publicado { background-color: #D1FAE5; color: #059669; }
        .status-arquivado { background-color: #E0E7FF; color: #4F46E5; }

        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 2rem;
        }
        .pagination a, .pagination span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            margin: 0 0.25rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .pagination a {
            background-color: white;
            color: #4B5563;
            border: 1px solid #E5E7EB;
            text-decoration: none;
        }
        .pagination a:hover {
            background-color: #F3F4F6;
        }
        .pagination span {
            background-color: #6A5ACD;
            color: white;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <?php include 'includes/header.php'; ?>

            <!-- Main -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
                <div class="container mx-auto">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-800"><?php echo $titulo_pagina; ?></h1>
                            <p class="text-gray-600">Gerencie os cursos do seu polo no Ambiente Virtual de Aprendizagem</p>
                        </div>
                        <a href="cursos_novo.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                            <i class="fas fa-plus mr-2"></i> Novo Curso
                        </a>
                    </div>

                    <?php if (isset($_SESSION['mensagem']) && isset($_SESSION['mensagem_tipo'])): ?>
                    <div class="bg-<?php echo $_SESSION['mensagem_tipo'] === 'sucesso' ? 'green' : 'red'; ?>-100 border-l-4 border-<?php echo $_SESSION['mensagem_tipo'] === 'sucesso' ? 'green' : 'red'; ?>-500 text-<?php echo $_SESSION['mensagem_tipo'] === 'sucesso' ? 'green' : 'red'; ?>-700 p-4 mb-6">
                        <?php echo is_array($_SESSION['mensagem']) ? implode(', ', $_SESSION['mensagem']) : $_SESSION['mensagem']; ?>
                    </div>
                    <?php
                    // Limpa a mensagem da sessão
                    unset($_SESSION['mensagem']);
                    unset($_SESSION['mensagem_tipo']);
                    endif;
                    ?>

                    <!-- Filtros -->
                    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">Filtros</h2>
                        <form action="cursos.php" method="get" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select id="status" name="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Todos</option>
                                    <option value="rascunho" <?php echo $filtro_status === 'rascunho' ? 'selected' : ''; ?>>Rascunho</option>
                                    <option value="revisao" <?php echo $filtro_status === 'revisao' ? 'selected' : ''; ?>>Em Revisão</option>
                                    <option value="publicado" <?php echo $filtro_status === 'publicado' ? 'selected' : ''; ?>>Publicado</option>
                                    <option value="arquivado" <?php echo $filtro_status === 'arquivado' ? 'selected' : ''; ?>>Arquivado</option>
                                </select>
                            </div>
                            <div>
                                <label for="categoria" class="block text-sm font-medium text-gray-700 mb-1">Categoria</label>
                                <select id="categoria" name="categoria" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Todas</option>
                                    <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?php echo htmlspecialchars($categoria['categoria']); ?>" <?php echo $filtro_categoria === $categoria['categoria'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($categoria['categoria_nome'] ?? $categoria['categoria']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label for="busca" class="block text-sm font-medium text-gray-700 mb-1">Busca</label>
                                <input type="text" id="busca" name="busca" value="<?php echo htmlspecialchars($filtro_busca); ?>" placeholder="Título ou descrição" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div class="flex items-end">
                                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                                    <i class="fas fa-search mr-2"></i> Filtrar
                                </button>
                                <a href="cursos.php" class="ml-2 inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                    <i class="fas fa-times mr-2"></i> Limpar
                                </a>
                            </div>
                        </form>
                    </div>

                    <!-- Listagem de Cursos -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-800">Cursos (<?php echo $total_cursos; ?>)</h2>
                        </div>
                        <div class="p-6">
                            <?php if (empty($cursos)): ?>
                            <div class="text-center text-gray-500 py-4">
                                <p>Nenhum curso encontrado.</p>
                                <a href="cursos_novo.php" class="inline-flex items-center mt-4 px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                                    <i class="fas fa-plus mr-2"></i> Criar Novo Curso
                                </a>
                            </div>
                            <?php else: ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <?php foreach ($cursos as $curso): ?>
                                <div class="curso-card">
                                    <div class="curso-header" style="background-image: url('<?php echo !empty($curso['imagem']) ? $curso['imagem'] : '../uploads/ava/default-course.jpg'; ?>');">
                                        <div class="curso-category" style="background-color: <?php echo $curso['categoria_cor'] ?? '#6A5ACD'; ?>">
                                            <?php echo htmlspecialchars($curso['categoria_nome'] ?? $curso['categoria'] ?? 'Geral'); ?>
                                        </div>
                                        <div class="curso-header-overlay">
                                            <h3 class="curso-title"><?php echo htmlspecialchars($curso['titulo']); ?></h3>
                                            <span class="status-badge status-<?php echo $curso['status']; ?>">
                                                <?php
                                                if ($curso['status'] === 'rascunho') echo 'Rascunho';
                                                elseif ($curso['status'] === 'revisao') echo 'Em Revisão';
                                                elseif ($curso['status'] === 'publicado') echo 'Publicado';
                                                elseif ($curso['status'] === 'arquivado') echo 'Arquivado';
                                                ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="curso-body">
                                        <div class="curso-stats">
                                            <div>
                                                <i class="fas fa-users mr-1"></i> <?php echo $curso['total_alunos']; ?> alunos
                                            </div>
                                            <div>
                                                <i class="fas fa-layer-group mr-1"></i> <?php echo $curso['total_modulos']; ?> módulos
                                            </div>
                                        </div>
                                        <div class="curso-description">
                                            <?php
                                            $descricao = $curso['descricao'] ?? 'Sem descrição disponível.';
                                            echo strlen($descricao) > 100 ? substr($descricao, 0, 100) . '...' : $descricao;
                                            ?>
                                        </div>
                                        <div class="curso-footer">
                                            <span class="text-sm text-gray-500">
                                                <?php echo date('d/m/Y', strtotime($curso['created_at'])); ?>
                                            </span>
                                            <div>
                                                <a href="curso_visualizar.php?id=<?php echo $curso['id']; ?>" class="inline-flex items-center px-3 py-1.5 bg-blue-100 text-blue-800 text-xs font-medium rounded hover:bg-blue-200 mr-1">
                                                    <i class="fas fa-eye mr-1"></i> Ver
                                                </a>
                                                <a href="curso_editar.php?id=<?php echo $curso['id']; ?>" class="inline-flex items-center px-3 py-1.5 bg-indigo-100 text-indigo-800 text-xs font-medium rounded hover:bg-indigo-200">
                                                    <i class="fas fa-edit mr-1"></i> Editar
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Paginação -->
                            <?php if ($total_paginas > 1): ?>
                            <div class="pagination mt-6">
                                <?php if ($pagina_atual > 1): ?>
                                <a href="cursos.php?pagina=<?php echo $pagina_atual - 1; ?>&status=<?php echo urlencode($filtro_status); ?>&categoria=<?php echo urlencode($filtro_categoria); ?>&busca=<?php echo urlencode($filtro_busca); ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                                <?php endif; ?>

                                <?php
                                $inicio = max(1, $pagina_atual - 2);
                                $fim = min($total_paginas, $pagina_atual + 2);

                                if ($inicio > 1) {
                                    echo '<a href="cursos.php?pagina=1&status=' . urlencode($filtro_status) . '&categoria=' . urlencode($filtro_categoria) . '&busca=' . urlencode($filtro_busca) . '">1</a>';
                                    if ($inicio > 2) {
                                        echo '<span>...</span>';
                                    }
                                }

                                for ($i = $inicio; $i <= $fim; $i++) {
                                    if ($i == $pagina_atual) {
                                        echo '<span>' . $i . '</span>';
                                    } else {
                                        echo '<a href="cursos.php?pagina=' . $i . '&status=' . urlencode($filtro_status) . '&categoria=' . urlencode($filtro_categoria) . '&busca=' . urlencode($filtro_busca) . '">' . $i . '</a>';
                                    }
                                }

                                if ($fim < $total_paginas) {
                                    if ($fim < $total_paginas - 1) {
                                        echo '<span>...</span>';
                                    }
                                    echo '<a href="cursos.php?pagina=' . $total_paginas . '&status=' . urlencode($filtro_status) . '&categoria=' . urlencode($filtro_categoria) . '&busca=' . urlencode($filtro_busca) . '">' . $total_paginas . '</a>';
                                }
                                ?>

                                <?php if ($pagina_atual < $total_paginas): ?>
                                <a href="cursos.php?pagina=<?php echo $pagina_atual + 1; ?>&status=<?php echo urlencode($filtro_status); ?>&categoria=<?php echo urlencode($filtro_categoria); ?>&busca=<?php echo urlencode($filtro_busca); ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>

            <!-- Footer -->
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>

    <script>
        // Toggle sidebar
        document.getElementById('toggle-sidebar').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('sidebar-collapsed');
            sidebar.classList.toggle('sidebar-expanded');

            const labels = document.querySelectorAll('.sidebar-label');
            labels.forEach(label => {
                label.classList.toggle('hidden');
            });
        });

        // Toggle user menu
        document.getElementById('user-menu-button').addEventListener('click', function() {
            const menu = document.getElementById('user-menu');
            menu.classList.toggle('hidden');
        });
    </script>
</body>
</html>
