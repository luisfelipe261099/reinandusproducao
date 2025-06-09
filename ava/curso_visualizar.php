<?php
/**
 * Visualização de Curso do AVA
 * Exibe os detalhes de um curso específico no Ambiente Virtual de Aprendizagem
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

// Verifica se o ID do curso foi informado
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setMensagem('erro', 'Curso não encontrado.');
    redirect('cursos.php');
    exit;
}

$curso_id = (int)$_GET['id'];

// Busca o curso
$sql = "SELECT ac.*, cat.nome as categoria_nome, cat.cor as categoria_cor
        FROM ava_cursos ac
        LEFT JOIN ava_categorias cat ON ac.categoria = cat.nome
        WHERE ac.id = ? AND ac.polo_id = ?";
$curso = $db->fetchOne($sql, [$curso_id, $polo_id]);

if (!$curso) {
    setMensagem('erro', 'Curso não encontrado ou você não tem permissão para acessá-lo.');
    redirect('cursos.php');
    exit;
}

// Verifica se a tabela ava_modulos existe
$sql_check = "SHOW TABLES LIKE 'ava_modulos'";
$tabela_modulos_existe = $db->fetchOne($sql_check);

// Busca os módulos do curso
$modulos = [];
if ($tabela_modulos_existe) {
    $sql = "SELECT * FROM ava_modulos WHERE curso_id = ? ORDER BY ordem, id";
    $modulos = $db->fetchAll($sql, [$curso_id]);
}

// Verifica se a tabela ava_matriculas existe
$sql_check = "SHOW TABLES LIKE 'ava_matriculas'";
$tabela_matriculas_existe = $db->fetchOne($sql_check);

// Busca os alunos matriculados no curso
$alunos = [];
if ($tabela_matriculas_existe) {
    $sql = "SELECT am.*, a.nome as aluno_nome, a.email as aluno_email, a.cpf as aluno_cpf
            FROM ava_matriculas am
            JOIN alunos a ON am.aluno_id = a.id
            WHERE am.curso_id = ?
            ORDER BY a.nome";
    $alunos = $db->fetchAll($sql, [$curso_id]);
}

// Define o título da página
$titulo_pagina = 'Visualizar Curso';
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
        .curso-header {
            background-size: cover;
            background-position: center;
            height: 200px;
            position: relative;
            border-radius: 0.5rem 0.5rem 0 0;
        }
        .curso-header-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to bottom, rgba(0,0,0,0.1), rgba(0,0,0,0.7));
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            border-radius: 0.5rem 0.5rem 0 0;
        }
        .curso-title {
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .curso-category {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            color: white;
            margin-bottom: 0.5rem;
        }
        .curso-meta {
            display: flex;
            color: white;
            font-size: 0.875rem;
            gap: 1rem;
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
        
        .info-card {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .info-card-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #111827;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #E5E7EB;
        }
        
        .module-card {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .module-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        .module-title {
            font-weight: 600;
            color: #111827;
        }
        .module-status {
            font-size: 0.75rem;
            padding: 0.125rem 0.5rem;
            border-radius: 9999px;
        }
        .module-status-ativo { background-color: #D1FAE5; color: #059669; }
        .module-status-inativo { background-color: #FEE2E2; color: #DC2626; }
        
        .aluno-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #E5E7EB;
        }
        .aluno-item:last-child {
            border-bottom: none;
        }
        .aluno-avatar {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 50%;
            background-color: #F3F4F6;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            color: #6A5ACD;
            font-weight: 600;
        }
        .aluno-info {
            flex: 1;
        }
        .aluno-name {
            font-weight: 500;
            color: #111827;
            margin-bottom: 0.25rem;
        }
        .aluno-email {
            font-size: 0.875rem;
            color: #6B7280;
        }
        .aluno-cpf {
            font-size: 0.75rem;
            color: #6B7280;
            margin-top: 0.25rem;
        }
        .aluno-status {
            font-size: 0.75rem;
            padding: 0.125rem 0.5rem;
            border-radius: 9999px;
            margin-left: 0.5rem;
        }
        .aluno-status-ativo { background-color: #D1FAE5; color: #059669; }
        .aluno-status-inativo { background-color: #FEE2E2; color: #DC2626; }
        .aluno-status-pendente { background-color: #FEF3C7; color: #D97706; }
        .aluno-status-concluido { background-color: #E0E7FF; color: #4F46E5; }
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
                            <p class="text-gray-600">Detalhes do curso no Ambiente Virtual de Aprendizagem</p>
                        </div>
                        <div class="flex space-x-2">
                            <a href="cursos.php" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                <i class="fas fa-arrow-left mr-2"></i> Voltar
                            </a>
                            <a href="curso_editar.php?id=<?php echo $curso['id']; ?>" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                                <i class="fas fa-edit mr-2"></i> Editar
                            </a>
                        </div>
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

                    <!-- Cabeçalho do Curso -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
                        <div class="curso-header" style="background-image: url('<?php echo !empty($curso['imagem']) ? $curso['imagem'] : '../uploads/ava/default-course.jpg'; ?>');">
                            <div class="curso-header-overlay">
                                <span class="curso-category" style="background-color: <?php echo $curso['categoria_cor'] ?? '#6A5ACD'; ?>">
                                    <?php echo htmlspecialchars($curso['categoria_nome'] ?? $curso['categoria'] ?? 'Geral'); ?>
                                </span>
                                <h2 class="curso-title"><?php echo htmlspecialchars($curso['titulo']); ?></h2>
                                <div class="curso-meta">
                                    <span><i class="fas fa-clock mr-1"></i> <?php echo $curso['carga_horaria']; ?> horas</span>
                                    <span><i class="fas fa-calendar-alt mr-1"></i> Criado em <?php echo date('d/m/Y', strtotime($curso['created_at'])); ?></span>
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
                        </div>
                        <div class="p-6">
                            <div class="mb-6">
                                <h3 class="text-lg font-semibold text-gray-800 mb-2">Descrição</h3>
                                <p class="text-gray-600"><?php echo nl2br(htmlspecialchars($curso['descricao'])); ?></p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-800 mb-1">Preço</h3>
                                    <p class="text-gray-600">
                                        <?php if (!empty($curso['preco'])): ?>
                                        R$ <?php echo number_format($curso['preco'], 2, ',', '.'); ?>
                                        <?php if (!empty($curso['preco_promocional'])): ?>
                                        <span class="line-through text-gray-400 ml-2">R$ <?php echo number_format($curso['preco_promocional'], 2, ',', '.'); ?></span>
                                        <?php endif; ?>
                                        <?php else: ?>
                                        Gratuito
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-800 mb-1">Data de Início</h3>
                                    <p class="text-gray-600">
                                        <?php echo !empty($curso['data_inicio']) ? date('d/m/Y', strtotime($curso['data_inicio'])) : 'Não definida'; ?>
                                    </p>
                                </div>
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-800 mb-1">Data de Término</h3>
                                    <p class="text-gray-600">
                                        <?php echo !empty($curso['data_fim']) ? date('d/m/Y', strtotime($curso['data_fim'])) : 'Não definida'; ?>
                                    </p>
                                </div>
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-800 mb-1">Visibilidade</h3>
                                    <p class="text-gray-600">
                                        <?php echo $curso['visibilidade'] === 'publico' ? 'Público' : 'Privado'; ?>
                                    </p>
                                </div>
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-800 mb-1">Destaque</h3>
                                    <p class="text-gray-600">
                                        <?php echo $curso['destaque'] ? 'Sim' : 'Não'; ?>
                                    </p>
                                </div>
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-800 mb-1">Última Atualização</h3>
                                    <p class="text-gray-600">
                                        <?php echo date('d/m/Y H:i', strtotime($curso['updated_at'])); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Coluna da Esquerda - Detalhes do Curso -->
                        <div class="lg:col-span-2">
                            <!-- Requisitos -->
                            <?php if (!empty($curso['requisitos'])): ?>
                            <div class="info-card">
                                <h3 class="info-card-title">Requisitos</h3>
                                <div class="text-gray-600">
                                    <?php echo nl2br(htmlspecialchars($curso['requisitos'])); ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Público-Alvo -->
                            <?php if (!empty($curso['publico_alvo'])): ?>
                            <div class="info-card">
                                <h3 class="info-card-title">Público-Alvo</h3>
                                <div class="text-gray-600">
                                    <?php echo nl2br(htmlspecialchars($curso['publico_alvo'])); ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Objetivos -->
                            <?php if (!empty($curso['objetivos'])): ?>
                            <div class="info-card">
                                <h3 class="info-card-title">Objetivos</h3>
                                <div class="text-gray-600">
                                    <?php echo nl2br(htmlspecialchars($curso['objetivos'])); ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Metodologia -->
                            <?php if (!empty($curso['metodologia'])): ?>
                            <div class="info-card">
                                <h3 class="info-card-title">Metodologia</h3>
                                <div class="text-gray-600">
                                    <?php echo nl2br(htmlspecialchars($curso['metodologia'])); ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Avaliação -->
                            <?php if (!empty($curso['avaliacao'])): ?>
                            <div class="info-card">
                                <h3 class="info-card-title">Avaliação</h3>
                                <div class="text-gray-600">
                                    <?php echo nl2br(htmlspecialchars($curso['avaliacao'])); ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Certificação -->
                            <?php if (!empty($curso['certificacao'])): ?>
                            <div class="info-card">
                                <h3 class="info-card-title">Certificação</h3>
                                <div class="text-gray-600">
                                    <?php echo nl2br(htmlspecialchars($curso['certificacao'])); ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Módulos -->
                            <div class="info-card">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="info-card-title mb-0 pb-0 border-0">Módulos</h3>
                                    <a href="modulos.php?curso_id=<?php echo $curso['id']; ?>" class="inline-flex items-center px-3 py-1.5 border border-transparent rounded-md text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                                        <i class="fas fa-plus mr-1"></i> Gerenciar Módulos
                                    </a>
                                </div>
                                
                                <?php if (empty($modulos)): ?>
                                <div class="text-center text-gray-500 py-4">
                                    <p>Nenhum módulo cadastrado para este curso.</p>
                                </div>
                                <?php else: ?>
                                <div class="space-y-4">
                                    <?php foreach ($modulos as $modulo): ?>
                                    <div class="module-card">
                                        <div class="module-header">
                                            <h4 class="module-title"><?php echo htmlspecialchars($modulo['titulo']); ?></h4>
                                            <span class="module-status module-status-<?php echo $modulo['status']; ?>">
                                                <?php echo $modulo['status'] === 'ativo' ? 'Ativo' : 'Inativo'; ?>
                                            </span>
                                        </div>
                                        <p class="text-sm text-gray-600">
                                            <?php echo !empty($modulo['descricao']) ? htmlspecialchars($modulo['descricao']) : 'Sem descrição disponível.'; ?>
                                        </p>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Coluna da Direita - Alunos Matriculados -->
                        <div>
                            <div class="info-card">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="info-card-title mb-0 pb-0 border-0">Alunos Matriculados</h3>
                                    <a href="matriculas.php?curso_id=<?php echo $curso['id']; ?>" class="inline-flex items-center px-3 py-1.5 border border-transparent rounded-md text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                                        <i class="fas fa-user-plus mr-1"></i> Matricular
                                    </a>
                                </div>
                                
                                <?php if (empty($alunos)): ?>
                                <div class="text-center text-gray-500 py-4">
                                    <p>Nenhum aluno matriculado neste curso.</p>
                                </div>
                                <?php else: ?>
                                <div>
                                    <?php foreach ($alunos as $aluno): ?>
                                    <div class="aluno-item">
                                        <div class="aluno-avatar">
                                            <?php echo strtoupper(substr($aluno['aluno_nome'], 0, 1)); ?>
                                        </div>
                                        <div class="aluno-info">
                                            <div class="flex items-center">
                                                <div class="aluno-name"><?php echo htmlspecialchars($aluno['aluno_nome']); ?></div>
                                                <span class="aluno-status aluno-status-<?php echo $aluno['status']; ?>">
                                                    <?php
                                                    if ($aluno['status'] === 'ativo') echo 'Ativo';
                                                    elseif ($aluno['status'] === 'inativo') echo 'Inativo';
                                                    elseif ($aluno['status'] === 'pendente') echo 'Pendente';
                                                    elseif ($aluno['status'] === 'concluido') echo 'Concluído';
                                                    ?>
                                                </span>
                                            </div>
                                            <div class="aluno-email"><?php echo htmlspecialchars($aluno['aluno_email']); ?></div>
                                            <div class="aluno-cpf"><?php echo htmlspecialchars($aluno['aluno_cpf']); ?></div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Ações Rápidas -->
                            <div class="info-card">
                                <h3 class="info-card-title">Ações Rápidas</h3>
                                <div class="space-y-2">
                                    <a href="curso_editar.php?id=<?php echo $curso['id']; ?>" class="flex items-center p-2 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition-colors">
                                        <i class="fas fa-edit w-5 text-indigo-600"></i>
                                        <span class="ml-2 text-indigo-800">Editar Curso</span>
                                    </a>
                                    <a href="modulos.php?curso_id=<?php echo $curso['id']; ?>" class="flex items-center p-2 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                                        <i class="fas fa-layer-group w-5 text-blue-600"></i>
                                        <span class="ml-2 text-blue-800">Gerenciar Módulos</span>
                                    </a>
                                    <a href="matriculas.php?curso_id=<?php echo $curso['id']; ?>" class="flex items-center p-2 bg-green-50 rounded-lg hover:bg-green-100 transition-colors">
                                        <i class="fas fa-user-plus w-5 text-green-600"></i>
                                        <span class="ml-2 text-green-800">Matricular Alunos</span>
                                    </a>
                                    <a href="relatorio_curso.php?id=<?php echo $curso['id']; ?>" class="flex items-center p-2 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors">
                                        <i class="fas fa-chart-bar w-5 text-purple-600"></i>
                                        <span class="ml-2 text-purple-800">Relatório do Curso</span>
                                    </a>
                                </div>
                            </div>
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
