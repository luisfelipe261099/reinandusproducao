<?php
/**
 * Página de Visualização de um Curso do AVA
 * Permite à secretaria visualizar os detalhes de um curso cadastrado por um polo no AVA
 */

// Inicializa o sistema
require_once 'includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Qualquer usuário autenticado pode visualizar o curso como aluno

// Instancia o banco de dados
$db = Database::getInstance();

// Verifica se o ID do curso foi informado
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setMensagem('erro', 'ID do curso não informado.');
    redirect('ava_cursos.php');
    exit;
}

$curso_id = (int)$_GET['id'];

// Busca o curso
$sql = "SELECT ac.*,
        p.nome as polo_nome, p.id as polo_id,
        cat.nome as categoria_nome, cat.cor as categoria_cor
        FROM ava_cursos ac
        JOIN polos p ON ac.polo_id = p.id
        LEFT JOIN ava_categorias cat ON ac.categoria = cat.nome
        WHERE ac.id = ?";
$curso = $db->fetchOne($sql, [$curso_id]);

if (!$curso) {
    setMensagem('erro', 'Curso não encontrado.');
    redirect('ava_cursos.php');
    exit;
}

// Verificamos se o curso está disponível para visualização
if ($curso['status'] !== 'publicado' && $curso['status'] !== 'ativo' && getUsuarioTipo() !== 'admin' && getUsuarioTipo() !== 'secretaria') {
    setMensagem('erro', 'Este curso não está disponível para visualização.');
    redirect('ava_cursos.php');
    exit;
}

// Busca os módulos do curso
$sql = "SELECT * FROM ava_modulos WHERE curso_id = ? ORDER BY ordem";
$modulos = $db->fetchAll($sql, [$curso_id]);

// Busca as aulas de cada módulo
foreach ($modulos as &$modulo) {
    $sql = "SELECT * FROM ava_aulas WHERE modulo_id = ? ORDER BY ordem";
    $modulo['aulas'] = $db->fetchAll($sql, [$modulo['id']]);

    // Conta os materiais de cada aula
    foreach ($modulo['aulas'] as &$aula) {
        $sql = "SELECT COUNT(*) as total FROM ava_materiais WHERE aula_id = ?";
        $materiais = $db->fetchOne($sql, [$aula['id']]);
        $aula['total_materiais'] = $materiais['total'];

        // Se for um quiz, conta as questões
        if ($aula['tipo'] === 'quiz') {
            $sql = "SELECT COUNT(*) as total FROM ava_questoes WHERE aula_id = ?";
            $questoes = $db->fetchOne($sql, [$aula['id']]);
            $aula['total_questoes'] = $questoes['total'];
        }
    }
}

// Busca os alunos matriculados no curso
$sql = "SELECT am.*, a.nome as aluno_nome, a.email as aluno_email
        FROM ava_matriculas am
        JOIN alunos a ON am.aluno_id = a.id
        WHERE am.curso_id = ?
        ORDER BY am.data_matricula DESC";
$alunos = $db->fetchAll($sql, [$curso_id]);

// Define o título da página
$titulo_pagina = 'Curso: ' . $curso['titulo'];
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

        .curso-header {
            height: 250px;
            background-size: cover;
            background-position: center;
            position: relative;
            border-radius: 0.5rem;
            overflow: hidden;
            margin-bottom: 1.5rem;
        }
        .curso-header-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to bottom, rgba(0,0,0,0.3), rgba(0,0,0,0.8));
            padding: 2rem;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
        }
        .curso-title {
            color: white;
            font-weight: 700;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        .curso-subtitle {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1.1rem;
            margin-bottom: 1rem;
        }
        .curso-category {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
            color: white;
        }

        .modulo-card {
            border: 1px solid #E5E7EB;
            border-radius: 0.5rem;
            overflow: hidden;
            margin-bottom: 1.5rem;
        }
        .modulo-header {
            background-color: #F9FAFB;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #E5E7EB;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
        }
        .modulo-title {
            font-weight: 600;
            font-size: 1.1rem;
            color: #111827;
            display: flex;
            align-items: center;
        }
        .modulo-title i {
            margin-right: 0.75rem;
            color: #6A5ACD;
        }
        .modulo-content {
            padding: 1rem 1.5rem;
        }

        .aula-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #E5E7EB;
        }
        .aula-item:last-child {
            border-bottom: none;
        }
        .aula-icon {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 50%;
            background-color: #F3F4F6;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            color: #6A5ACD;
        }
        .aula-info {
            flex: 1;
        }
        .aula-title {
            font-weight: 500;
            color: #111827;
            margin-bottom: 0.25rem;
        }
        .aula-meta {
            font-size: 0.875rem;
            color: #6B7280;
            display: flex;
            align-items: center;
        }
        .aula-meta span {
            display: flex;
            align-items: center;
            margin-right: 1rem;
        }
        .aula-meta i {
            margin-right: 0.25rem;
        }

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
        .aluno-progress {
            width: 100px;
            margin-left: 1rem;
        }
        .progress-bar {
            width: 100%;
            height: 0.5rem;
            background-color: #E5E7EB;
            border-radius: 9999px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background-color: #6A5ACD;
            border-radius: 9999px;
        }
        .progress-text {
            font-size: 0.75rem;
            color: #6B7280;
            text-align: center;
            margin-top: 0.25rem;
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
                            <p class="text-gray-600">Visualizando detalhes do curso</p>
                        </div>
                        <div class="flex space-x-2">
                            <a href="ava_dashboard_aluno.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700">
                                <i class="fas fa-graduation-cap mr-2"></i> Meus Cursos
                            </a>
                            <a href="ava_cursos.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                                <i class="fas fa-arrow-left mr-2"></i> Voltar para Cursos
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
                    <div class="curso-header" style="background-image: url('<?php echo !empty($curso['imagem']) ? $curso['imagem'] : 'uploads/ava/default-course.jpg'; ?>');">
                        <div class="curso-category" style="background-color: <?php echo $curso['categoria_cor'] ?? '#6A5ACD'; ?>">
                            <?php echo htmlspecialchars($curso['categoria_nome'] ?? $curso['categoria'] ?? 'Geral'); ?>
                        </div>
                        <div class="curso-header-overlay">
                            <h1 class="curso-title"><?php echo htmlspecialchars($curso['titulo']); ?></h1>
                            <p class="curso-subtitle">Polo: <?php echo htmlspecialchars($curso['polo_nome']); ?></p>
                            <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                                <div class="flex flex-wrap items-center gap-4">
                                    <span class="status-badge status-<?php echo $curso['status']; ?>">
                                        <?php
                                        if ($curso['status'] === 'rascunho') echo 'Rascunho';
                                        elseif ($curso['status'] === 'revisao') echo 'Em Revisão';
                                        elseif ($curso['status'] === 'publicado') echo 'Publicado';
                                        elseif ($curso['status'] === 'arquivado') echo 'Arquivado';
                                        elseif ($curso['status'] === 'ativo') echo 'Ativo';
                                        else echo ucfirst($curso['status']);
                                        ?>
                                    </span>
                                    <span class="text-white">
                                        <i class="fas fa-clock mr-1"></i> <?php echo $curso['carga_horaria'] ?? 0; ?> horas
                                    </span>
                                    <span class="text-white">
                                        <i class="fas fa-layer-group mr-1"></i> <?php echo count($modulos); ?> módulos
                                    </span>
                                    <span class="text-white">
                                        <i class="fas fa-users mr-1"></i> <?php echo count($alunos); ?> alunos
                                    </span>
                                </div>
                                <div>
                                    <a href="#conteudo" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-blue-600 bg-white hover:bg-blue-50">
                                        <i class="fas fa-play-circle mr-2"></i> Iniciar Curso
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Coluna da Esquerda - Informações do Curso -->
                        <div class="lg:col-span-2">
                            <!-- Descrição do Curso -->
                            <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                                <h2 class="text-lg font-semibold text-gray-800 mb-4">Sobre o Curso</h2>
                                <div class="prose max-w-none">
                                    <?php if (!empty($curso['descricao'])): ?>
                                        <p><?php echo nl2br(htmlspecialchars($curso['descricao'])); ?></p>
                                    <?php else: ?>
                                        <p class="text-gray-500">Nenhuma descrição disponível.</p>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($curso['objetivos']) || !empty($curso['publico_alvo']) || !empty($curso['pre_requisitos'])): ?>
                                <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <?php if (!empty($curso['objetivos'])): ?>
                                    <div>
                                        <h3 class="text-md font-semibold text-gray-800 mb-2">Objetivos</h3>
                                        <p class="text-sm text-gray-600"><?php echo nl2br(htmlspecialchars($curso['objetivos'])); ?></p>
                                    </div>
                                    <?php endif; ?>

                                    <?php if (!empty($curso['publico_alvo'])): ?>
                                    <div>
                                        <h3 class="text-md font-semibold text-gray-800 mb-2">Público-Alvo</h3>
                                        <p class="text-sm text-gray-600"><?php echo nl2br(htmlspecialchars($curso['publico_alvo'])); ?></p>
                                    </div>
                                    <?php endif; ?>

                                    <?php if (!empty($curso['pre_requisitos'])): ?>
                                    <div>
                                        <h3 class="text-md font-semibold text-gray-800 mb-2">Pré-Requisitos</h3>
                                        <p class="text-sm text-gray-600"><?php echo nl2br(htmlspecialchars($curso['pre_requisitos'])); ?></p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Conteúdo do Curso -->
                            <div id="conteudo" class="bg-white rounded-xl shadow-sm p-6 mb-6">
                                <h2 class="text-lg font-semibold text-gray-800 mb-4">Conteúdo do Curso</h2>

                                <?php if (empty($modulos)): ?>
                                <div class="text-center text-gray-500 py-4">
                                    <p>Nenhum módulo cadastrado para este curso.</p>
                                </div>
                                <?php else: ?>
                                    <?php foreach ($modulos as $modulo): ?>
                                    <div class="modulo-card">
                                        <div class="modulo-header" onclick="toggleModulo(<?php echo $modulo['id']; ?>)">
                                            <div class="modulo-title">
                                                <i class="fas fa-folder"></i>
                                                <?php echo htmlspecialchars($modulo['titulo']); ?>
                                            </div>
                                            <div class="flex items-center">
                                                <span class="text-sm text-gray-500 mr-4">
                                                    <?php echo count($modulo['aulas']); ?> aulas
                                                </span>
                                                <i class="fas fa-chevron-down text-gray-400" id="modulo-icon-<?php echo $modulo['id']; ?>"></i>
                                            </div>
                                        </div>
                                        <div class="modulo-content hidden" id="modulo-content-<?php echo $modulo['id']; ?>">
                                            <?php if (empty($modulo['aulas'])): ?>
                                            <div class="text-center text-gray-500 py-2">
                                                <p>Nenhuma aula cadastrada para este módulo.</p>
                                            </div>
                                            <?php else: ?>
                                                <?php foreach ($modulo['aulas'] as $aula): ?>
                                                <div class="aula-item">
                                                    <div class="aula-icon">
                                                        <?php
                                                        if ($aula['tipo'] === 'video') echo '<i class="fas fa-play"></i>';
                                                        elseif ($aula['tipo'] === 'texto') echo '<i class="fas fa-file-alt"></i>';
                                                        elseif ($aula['tipo'] === 'quiz') echo '<i class="fas fa-question"></i>';
                                                        elseif ($aula['tipo'] === 'arquivo') echo '<i class="fas fa-file"></i>';
                                                        elseif ($aula['tipo'] === 'link') echo '<i class="fas fa-link"></i>';
                                                        ?>
                                                    </div>
                                                    <div class="aula-info">
                                                        <div class="aula-title"><?php echo htmlspecialchars($aula['titulo']); ?></div>
                                                        <div class="aula-meta">
                                                            <span>
                                                                <i class="fas fa-<?php
                                                                if ($aula['tipo'] === 'video') echo 'play';
                                                                elseif ($aula['tipo'] === 'texto') echo 'file-alt';
                                                                elseif ($aula['tipo'] === 'quiz') echo 'question';
                                                                elseif ($aula['tipo'] === 'arquivo') echo 'file';
                                                                elseif ($aula['tipo'] === 'link') echo 'link';
                                                                ?>"></i>
                                                                <?php
                                                                if ($aula['tipo'] === 'video') echo 'Vídeo';
                                                                elseif ($aula['tipo'] === 'texto') echo 'Texto';
                                                                elseif ($aula['tipo'] === 'quiz') echo 'Quiz';
                                                                elseif ($aula['tipo'] === 'arquivo') echo 'Arquivo';
                                                                elseif ($aula['tipo'] === 'link') echo 'Link';
                                                                ?>
                                                            </span>

                                                            <?php if ($aula['tipo'] === 'video' && !empty($aula['duracao_minutos'])): ?>
                                                            <span>
                                                                <i class="fas fa-clock"></i> <?php echo $aula['duracao_minutos']; ?> min
                                                            </span>
                                                            <?php endif; ?>

                                                            <?php if ($aula['tipo'] === 'quiz' && isset($aula['total_questoes'])): ?>
                                                            <span>
                                                                <i class="fas fa-list"></i> <?php echo $aula['total_questoes']; ?> questões
                                                            </span>
                                                            <?php endif; ?>

                                                            <?php if ($aula['total_materiais'] > 0): ?>
                                                            <span>
                                                                <i class="fas fa-paperclip"></i> <?php echo $aula['total_materiais']; ?> materiais
                                                            </span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <div class="ml-auto">
                                                        <a href="#" onclick="visualizarAula(<?php echo $aula['id']; ?>, '<?php echo htmlspecialchars(addslashes($aula['titulo'])); ?>')" class="inline-flex items-center px-3 py-1 bg-blue-100 text-blue-800 text-xs font-medium rounded hover:bg-blue-200">
                                                            <i class="fas fa-eye mr-1"></i> Visualizar
                                                        </a>
                                                    </div>
                                                </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Coluna da Direita - Alunos Matriculados -->
                        <div>
                            <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                                <h2 class="text-lg font-semibold text-gray-800 mb-4">Alunos Matriculados</h2>

                                <?php if (empty($alunos)): ?>
                                <div class="text-center text-gray-500 py-4">
                                    <p>Nenhum aluno matriculado neste curso.</p>
                                </div>
                                <?php else: ?>
                                    <?php foreach ($alunos as $aluno): ?>
                                    <div class="aluno-item">
                                        <div class="aluno-avatar">
                                            <?php echo strtoupper(substr($aluno['aluno_nome'], 0, 1)); ?>
                                        </div>
                                        <div class="aluno-info">
                                            <div class="aluno-name"><?php echo htmlspecialchars($aluno['aluno_nome']); ?></div>
                                            <div class="aluno-email"><?php echo htmlspecialchars($aluno['aluno_email']); ?></div>
                                        </div>
                                        <div class="aluno-progress">
                                            <div class="progress-bar">
                                                <div class="progress-fill" style="width: <?php echo $aluno['progresso']; ?>%;"></div>
                                            </div>
                                            <div class="progress-text"><?php echo $aluno['progresso']; ?>%</div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <!-- Informações Adicionais -->
                            <div class="bg-white rounded-xl shadow-sm p-6">
                                <h2 class="text-lg font-semibold text-gray-800 mb-4">Informações Adicionais</h2>

                                <div class="space-y-4">
                                    <div>
                                        <p class="text-sm text-gray-500">Nível</p>
                                        <p class="font-medium">
                                            <?php
                                            if ($curso['nivel'] === 'basico') echo 'Básico';
                                            elseif ($curso['nivel'] === 'intermediario') echo 'Intermediário';
                                            elseif ($curso['nivel'] === 'avancado') echo 'Avançado';
                                            else echo 'Não definido';
                                            ?>
                                        </p>
                                    </div>

                                    <div>
                                        <p class="text-sm text-gray-500">Data de Criação</p>
                                        <p class="font-medium"><?php echo date('d/m/Y H:i', strtotime($curso['created_at'])); ?></p>
                                    </div>

                                    <?php if ($curso['data_publicacao']): ?>
                                    <div>
                                        <p class="text-sm text-gray-500">Data de Publicação</p>
                                        <p class="font-medium"><?php echo date('d/m/Y H:i', strtotime($curso['data_publicacao'])); ?></p>
                                    </div>
                                    <?php endif; ?>

                                    <div>
                                        <p class="text-sm text-gray-500">Última Atualização</p>
                                        <p class="font-medium"><?php echo date('d/m/Y H:i', strtotime($curso['updated_at'])); ?></p>
                                    </div>

                                    <?php if (!empty($curso['video_apresentacao'])): ?>
                                    <div class="mt-4">
                                        <a href="<?php echo $curso['video_apresentacao']; ?>" target="_blank" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                                            <i class="fas fa-play mr-2"></i> Assistir Vídeo de Apresentação
                                        </a>
                                    </div>
                                    <?php endif; ?>
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

        // Toggle módulos
        function toggleModulo(id) {
            const content = document.getElementById('modulo-content-' + id);
            const icon = document.getElementById('modulo-icon-' + id);

            content.classList.toggle('hidden');

            if (content.classList.contains('hidden')) {
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
            } else {
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            }
        }

        // Visualizar aula
        function visualizarAula(id, titulo) {
            // Redireciona para a página de visualização da aula
            window.location.href = 'ava_visualizar_aula.php?id=' + id;
            return false; // Previne o comportamento padrão do link
        }
    </script>
</body>
</html>
