<?php
/**
 * Gerenciamento de Cursos do AVA para o Polo
 * Permite ao polo listar, filtrar e gerenciar seus cursos no AVA
 */

// Inicializa o sistema
require_once '../includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica se o usuário é do tipo polo
if (getUsuarioTipo() !== 'polo') {
    setMensagem('erro', 'Você não tem permissão para acessar esta página.');
    redirect('../index.php');
    exit;
}

// Instancia o banco de dados
$db = Database::getInstance();

// Obtém o ID do polo
$polo_id = getUsuarioPoloId();

// Verifica se o polo tem acesso ao AVA
$sql = "SELECT * FROM ava_polos_acesso WHERE polo_id = ? AND liberado = 1";
$acesso = $db->fetchOne($sql, [$polo_id]);

if (!$acesso) {
    setMensagem('erro', 'Seu polo não possui acesso liberado ao AVA. Entre em contato com a secretaria para mais informações.');
    redirect('index.php');
    exit;
}

// Processa a exclusão de curso
if (isset($_GET['action']) && $_GET['action'] === 'excluir' && isset($_GET['id'])) {
    $curso_id = (int)$_GET['id'];
    
    // Verifica se o curso pertence ao polo
    $sql = "SELECT * FROM ava_cursos WHERE id = ? AND polo_id = ?";
    $curso = $db->fetchOne($sql, [$curso_id, $polo_id]);
    
    if (!$curso) {
        setMensagem('erro', 'Curso não encontrado ou não pertence ao seu polo.');
        redirect('ava_cursos.php');
        exit;
    }
    
    // Verifica se o curso pode ser excluído (apenas cursos em rascunho)
    if ($curso['status'] !== 'rascunho') {
        setMensagem('erro', 'Apenas cursos em rascunho podem ser excluídos.');
        redirect('ava_cursos.php');
        exit;
    }
    
    try {
        // Exclui o curso e todos os seus dados relacionados (módulos, aulas, etc.)
        // As restrições de chave estrangeira com ON DELETE CASCADE cuidarão de excluir os dados relacionados
        $sql = "DELETE FROM ava_cursos WHERE id = ?";
        $db->query($sql, [$curso_id]);
        
        setMensagem('sucesso', 'Curso excluído com sucesso.');
    } catch (Exception $e) {
        setMensagem('erro', 'Erro ao excluir o curso: ' . $e->getMessage());
    }
    
    redirect('ava_cursos.php');
    exit;
}

// Processa a alteração de status do curso
if (isset($_GET['action']) && $_GET['action'] === 'status' && isset($_GET['id']) && isset($_GET['status'])) {
    $curso_id = (int)$_GET['id'];
    $novo_status = $_GET['status'];
    
    // Verifica se o status é válido
    $status_validos = ['rascunho', 'revisao', 'publicado', 'arquivado'];
    if (!in_array($novo_status, $status_validos)) {
        setMensagem('erro', 'Status inválido.');
        redirect('ava_cursos.php');
        exit;
    }
    
    // Verifica se o curso pertence ao polo
    $sql = "SELECT * FROM ava_cursos WHERE id = ? AND polo_id = ?";
    $curso = $db->fetchOne($sql, [$curso_id, $polo_id]);
    
    if (!$curso) {
        setMensagem('erro', 'Curso não encontrado ou não pertence ao seu polo.');
        redirect('ava_cursos.php');
        exit;
    }
    
    try {
        // Atualiza o status do curso
        $sql = "UPDATE ava_cursos SET status = ?, updated_at = NOW()";
        
        // Se o status for 'publicado', atualiza a data de publicação
        if ($novo_status === 'publicado') {
            $sql .= ", data_publicacao = NOW()";
        }
        
        $sql .= " WHERE id = ?";
        $db->query($sql, [$novo_status, $curso_id]);
        
        setMensagem('sucesso', 'Status do curso atualizado com sucesso.');
    } catch (Exception $e) {
        setMensagem('erro', 'Erro ao atualizar o status do curso: ' . $e->getMessage());
    }
    
    redirect('ava_cursos.php');
    exit;
}

// Filtros
$filtro_status = $_GET['status'] ?? '';
$filtro_categoria = $_GET['categoria'] ?? '';
$filtro_busca = $_GET['busca'] ?? '';

// Constrói a consulta SQL com os filtros
$sql = "SELECT ac.*, 
        (SELECT COUNT(*) FROM ava_matriculas am WHERE am.curso_id = ac.id) as total_alunos,
        (SELECT COUNT(*) FROM ava_modulos am WHERE am.curso_id = ac.id) as total_modulos,
        cat.nome as categoria_nome, cat.cor as categoria_cor
        FROM ava_cursos ac
        LEFT JOIN ava_categorias cat ON ac.categoria = cat.nome
        WHERE ac.polo_id = ?";

$params = [$polo_id];

if (!empty($filtro_status)) {
    $sql .= " AND ac.status = ?";
    $params[] = $filtro_status;
}

if (!empty($filtro_categoria)) {
    $sql .= " AND ac.categoria = ?";
    $params[] = $filtro_categoria;
}

if (!empty($filtro_busca)) {
    $sql .= " AND (ac.titulo LIKE ? OR ac.descricao LIKE ?)";
    $params[] = "%$filtro_busca%";
    $params[] = "%$filtro_busca%";
}

$sql .= " ORDER BY ac.created_at DESC";

// Executa a consulta
$cursos = $db->fetchAll($sql, $params);

// Busca as categorias para o filtro
$sql = "SELECT * FROM ava_categorias WHERE status = 'ativo' ORDER BY nome";
$categorias = $db->fetchAll($sql);

// Define o título da página
$titulo_pagina = 'Gerenciamento de Cursos';
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
        
        .dropdown {
            position: relative;
            display: inline-block;
        }
        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: white;
            min-width: 160px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            z-index: 1;
            border-radius: 0.375rem;
            overflow: hidden;
        }
        .dropdown-content a {
            color: #4B5563;
            padding: 0.5rem 1rem;
            text-decoration: none;
            display: block;
            font-size: 0.875rem;
            transition: background-color 0.2s;
        }
        .dropdown-content a:hover {
            background-color: #F3F4F6;
        }
        .dropdown:hover .dropdown-content {
            display: block;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <?php include '../includes/sidebar_polo.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <?php include '../includes/header_polo.php'; ?>

            <!-- Main -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
                <div class="container mx-auto">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-800"><?php echo $titulo_pagina; ?></h1>
                            <p class="text-gray-600">Gerencie os cursos do seu polo no AVA</p>
                        </div>
                        <a href="ava_cursos_novo.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
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
                        <form action="ava_cursos.php" method="get" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select id="status" name="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-500 focus:ring-opacity-50">
                                    <option value="">Todos</option>
                                    <option value="rascunho" <?php echo $filtro_status === 'rascunho' ? 'selected' : ''; ?>>Rascunho</option>
                                    <option value="revisao" <?php echo $filtro_status === 'revisao' ? 'selected' : ''; ?>>Em Revisão</option>
                                    <option value="publicado" <?php echo $filtro_status === 'publicado' ? 'selected' : ''; ?>>Publicado</option>
                                    <option value="arquivado" <?php echo $filtro_status === 'arquivado' ? 'selected' : ''; ?>>Arquivado</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="categoria" class="block text-sm font-medium text-gray-700 mb-1">Categoria</label>
                                <select id="categoria" name="categoria" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-500 focus:ring-opacity-50">
                                    <option value="">Todas</option>
                                    <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?php echo $categoria['nome']; ?>" <?php echo $filtro_categoria === $categoria['nome'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($categoria['nome']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label for="busca" class="block text-sm font-medium text-gray-700 mb-1">Busca</label>
                                <input type="text" id="busca" name="busca" value="<?php echo htmlspecialchars($filtro_busca); ?>" placeholder="Título ou descrição" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-500 focus:ring-opacity-50">
                            </div>
                            
                            <div class="flex items-end">
                                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                                    <i class="fas fa-search mr-2"></i> Filtrar
                                </button>
                                
                                <?php if (!empty($filtro_status) || !empty($filtro_categoria) || !empty($filtro_busca)): ?>
                                <a href="ava_cursos.php" class="ml-2 inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                    <i class="fas fa-times mr-2"></i> Limpar
                                </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>

                    <!-- Lista de Cursos -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                            <h2 class="text-lg font-semibold text-gray-800">Cursos</h2>
                            <span class="text-gray-600"><?php echo count($cursos); ?> curso(s) encontrado(s)</span>
                        </div>
                        <div class="p-6">
                            <?php if (empty($cursos)): ?>
                            <div class="text-center text-gray-500 py-4">
                                <p>Nenhum curso encontrado com os filtros selecionados.</p>
                                <?php if (!empty($filtro_status) || !empty($filtro_categoria) || !empty($filtro_busca)): ?>
                                <a href="ava_cursos.php" class="inline-flex items-center mt-2 text-indigo-600 hover:text-indigo-800">
                                    <i class="fas fa-times mr-1"></i> Limpar filtros
                                </a>
                                <?php else: ?>
                                <a href="ava_cursos_novo.php" class="inline-flex items-center mt-4 px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                                    <i class="fas fa-plus mr-2"></i> Criar Novo Curso
                                </a>
                                <?php endif; ?>
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
                                            <div>
                                                <i class="fas fa-clock mr-1"></i> <?php echo $curso['carga_horaria'] ?? 0; ?>h
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
                                                <?php echo $curso['data_publicacao'] ? 'Publicado em ' . date('d/m/Y', strtotime($curso['data_publicacao'])) : 'Não publicado'; ?>
                                            </span>
                                            <div class="dropdown">
                                                <button class="inline-flex items-center px-3 py-1.5 bg-indigo-100 text-indigo-800 text-xs font-medium rounded hover:bg-indigo-200">
                                                    <i class="fas fa-ellipsis-h"></i>
                                                </button>
                                                <div class="dropdown-content">
                                                    <a href="ava_curso_editar.php?id=<?php echo $curso['id']; ?>">
                                                        <i class="fas fa-edit mr-2"></i> Editar
                                                    </a>
                                                    <a href="ava_curso_conteudo.php?id=<?php echo $curso['id']; ?>">
                                                        <i class="fas fa-list mr-2"></i> Gerenciar Conteúdo
                                                    </a>
                                                    <a href="ava_curso_alunos.php?id=<?php echo $curso['id']; ?>">
                                                        <i class="fas fa-users mr-2"></i> Gerenciar Alunos
                                                    </a>
                                                    
                                                    <?php if ($curso['status'] === 'rascunho'): ?>
                                                    <a href="ava_cursos.php?action=status&id=<?php echo $curso['id']; ?>&status=revisao" onclick="return confirm('Enviar este curso para revisão?');">
                                                        <i class="fas fa-clipboard-check mr-2"></i> Enviar para Revisão
                                                    </a>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($curso['status'] === 'revisao'): ?>
                                                    <a href="ava_cursos.php?action=status&id=<?php echo $curso['id']; ?>&status=publicado" onclick="return confirm('Publicar este curso?');">
                                                        <i class="fas fa-check-circle mr-2"></i> Publicar
                                                    </a>
                                                    <a href="ava_cursos.php?action=status&id=<?php echo $curso['id']; ?>&status=rascunho" onclick="return confirm('Voltar este curso para rascunho?');">
                                                        <i class="fas fa-undo mr-2"></i> Voltar para Rascunho
                                                    </a>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($curso['status'] === 'publicado'): ?>
                                                    <a href="ava_cursos.php?action=status&id=<?php echo $curso['id']; ?>&status=arquivado" onclick="return confirm('Arquivar este curso?');">
                                                        <i class="fas fa-archive mr-2"></i> Arquivar
                                                    </a>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($curso['status'] === 'arquivado'): ?>
                                                    <a href="ava_cursos.php?action=status&id=<?php echo $curso['id']; ?>&status=publicado" onclick="return confirm('Restaurar este curso?');">
                                                        <i class="fas fa-undo mr-2"></i> Restaurar
                                                    </a>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($curso['status'] === 'rascunho'): ?>
                                                    <a href="ava_cursos.php?action=excluir&id=<?php echo $curso['id']; ?>" onclick="return confirm('Tem certeza que deseja excluir este curso? Esta ação não pode ser desfeita.');" class="text-red-600 hover:text-red-800">
                                                        <i class="fas fa-trash mr-2"></i> Excluir
                                                    </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>

            <!-- Footer -->
            <?php include '../includes/footer_polo.php'; ?>
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
