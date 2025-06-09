<?php
/**
 * Página de Visualização de Cursos do AVA de um Polo
 * Permite à secretaria visualizar os cursos cadastrados por um polo no AVA
 */

// Inicializa o sistema
require_once 'includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica se o usuário tem permissão de secretaria ou admin
if (getUsuarioTipo() !== 'secretaria' && getUsuarioTipo() !== 'admin') {
    setMensagem('erro', 'Você não tem permissão para acessar esta página.');
    redirect('index.php');
    exit;
}

// Instancia o banco de dados
$db = Database::getInstance();

// Verifica se o ID do polo foi informado
if (!isset($_GET['polo_id']) || empty($_GET['polo_id'])) {
    setMensagem('erro', 'ID do polo não informado.');
    redirect('ava_gerenciar_acesso.php');
    exit;
}

$polo_id = (int)$_GET['polo_id'];

// Verifica se o polo existe
$sql = "SELECT * FROM polos WHERE id = ?";
$polo = $db->fetchOne($sql, [$polo_id]);

if (!$polo) {
    setMensagem('erro', 'Polo não encontrado.');
    redirect('ava_gerenciar_acesso.php');
    exit;
}

// Verifica se o polo tem acesso ao AVA
$sql = "SELECT * FROM ava_polos_acesso WHERE polo_id = ? AND liberado = 1";
$acesso = $db->fetchOne($sql, [$polo_id]);

if (!$acesso) {
    setMensagem('erro', 'O polo ' . $polo['nome'] . ' não possui acesso liberado ao AVA.');
    redirect('ava_gerenciar_acesso.php');
    exit;
}

// Busca os cursos do polo
$sql = "SELECT ac.*, 
        (SELECT COUNT(*) FROM ava_matriculas am WHERE am.curso_id = ac.id) as total_alunos,
        (SELECT COUNT(*) FROM ava_modulos am WHERE am.curso_id = ac.id) as total_modulos,
        cat.nome as categoria_nome, cat.cor as categoria_cor
        FROM ava_cursos ac
        LEFT JOIN ava_categorias cat ON ac.categoria = cat.nome
        WHERE ac.polo_id = ?
        ORDER BY ac.created_at DESC";
$cursos = $db->fetchAll($sql, [$polo_id]);

// Define o título da página
$titulo_pagina = 'Cursos do AVA - ' . $polo['nome'];
$titulo_pagina_completo = 'Faciência ERP - ' . $titulo_pagina;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo_pagina_completo; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
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
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <?php include 'includes/header.php'; ?>

            <!-- Main -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
                <div class="container mx-auto">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-800"><?php echo $titulo_pagina; ?></h1>
                            <p class="text-gray-600">Visualizando cursos do AVA cadastrados pelo polo</p>
                        </div>
                        <a href="ava_gerenciar_acesso.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gray-600 hover:bg-gray-700">
                            <i class="fas fa-arrow-left mr-2"></i> Voltar
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

                    <!-- Informações do Polo -->
                    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">Informações do Polo</h2>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <p class="text-sm text-gray-500">Nome</p>
                                <p class="font-medium"><?php echo htmlspecialchars($polo['nome']); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Responsável</p>
                                <p class="font-medium"><?php echo htmlspecialchars($polo['responsavel'] ?? 'Não informado'); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Contato</p>
                                <p class="font-medium"><?php echo htmlspecialchars($polo['email'] ?? 'Não informado'); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Acesso ao AVA</p>
                                <p class="font-medium text-green-600">Liberado desde <?php echo date('d/m/Y', strtotime($acesso['data_liberacao'])); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Cursos do Polo -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
                        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                            <h2 class="text-lg font-semibold text-gray-800">Cursos Cadastrados</h2>
                            <span class="text-gray-600"><?php echo count($cursos); ?> curso(s) encontrado(s)</span>
                        </div>
                        <div class="p-6">
                            <?php if (empty($cursos)): ?>
                            <div class="text-center text-gray-500 py-4">
                                <p>Nenhum curso cadastrado por este polo.</p>
                            </div>
                            <?php else: ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <?php foreach ($cursos as $curso): ?>
                                <div class="curso-card">
                                    <div class="curso-header" style="background-image: url('<?php echo !empty($curso['imagem']) ? $curso['imagem'] : 'uploads/ava/default-course.jpg'; ?>');">
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
                                            echo strlen($descricao) > 150 ? substr($descricao, 0, 150) . '...' : $descricao;
                                            ?>
                                        </div>
                                        <div class="curso-footer">
                                            <span class="text-sm text-gray-500">
                                                <?php echo $curso['data_publicacao'] ? 'Publicado em ' . date('d/m/Y', strtotime($curso['data_publicacao'])) : 'Não publicado'; ?>
                                            </span>
                                            <a href="ava_visualizar_curso.php?id=<?php echo $curso['id']; ?>" class="inline-flex items-center px-3 py-1.5 bg-blue-100 text-blue-800 text-xs font-medium rounded hover:bg-blue-200">
                                                <i class="fas fa-eye mr-1"></i> Visualizar
                                            </a>
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
