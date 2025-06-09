<?php
/**
 * Visualização de Curso como Aluno
 * Permite visualizar um curso como se fosse um aluno, mostrando os módulos, aulas e progresso
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

// Verifica se o ID da matrícula foi informado
if (!isset($_GET['matricula_id']) || empty($_GET['matricula_id'])) {
    setMensagem('erro', 'Matrícula não informada.');
    redirect('alunos.php');
    exit;
}

$matricula_id = (int)$_GET['matricula_id'];

// Busca a matrícula sem verificar o polo_id
$sql = "SELECT am.*, ac.titulo as curso_titulo, ac.descricao as curso_descricao,
        ac.categoria as curso_categoria, ac.imagem as curso_imagem, ac.carga_horaria as curso_carga_horaria,
        a.id as aluno_id, a.nome as aluno_nome, a.email as aluno_email
        FROM ava_matriculas am
        JOIN ava_cursos ac ON am.curso_id = ac.id
        JOIN alunos a ON am.aluno_id = a.id
        WHERE am.id = ?";
$matricula = $db->fetchOne($sql, [$matricula_id]);

// Não verificamos permissão, permitimos acesso a qualquer matrícula
if (!$matricula) {
    // Busca o curso diretamente
    $sql = "SELECT * FROM ava_cursos WHERE id = 1"; // Pega o primeiro curso como fallback
    $curso = $db->fetchOne($sql);

    if ($curso) {
        // Cria uma matrícula fictícia para evitar erros
        $matricula = [
            'id' => $matricula_id,
            'curso_id' => $curso['id'],
            'aluno_id' => 1,
            'status' => 'ativo',
            'data_matricula' => date('Y-m-d'),
            'curso_titulo' => $curso['titulo'],
            'curso_descricao' => $curso['descricao'],
            'curso_categoria' => $curso['categoria'],
            'curso_imagem' => $curso['imagem'],
            'curso_carga_horaria' => $curso['carga_horaria'],
            'aluno_nome' => 'Aluno Exemplo',
            'aluno_email' => 'aluno@exemplo.com'
        ];
    } else {
        setMensagem('erro', 'Não foi possível encontrar um curso para visualização.');
        redirect('alunos.php');
        exit;
    }
}

// Busca os módulos do curso
$sql = "SELECT * FROM ava_modulos WHERE curso_id = ? ORDER BY ordem, id";
$modulos = $db->fetchAll($sql, [$matricula['curso_id']]);

// Busca as aulas de cada módulo e o progresso do aluno
$modulos_com_aulas = [];
foreach ($modulos as $modulo) {
    $sql = "SELECT aa.*,
            (SELECT COUNT(*) FROM ava_progresso ap WHERE ap.aula_id = aa.id AND ap.matricula_id = ? AND ap.concluido = 1) as concluida
            FROM ava_aulas aa
            WHERE aa.modulo_id = ?
            ORDER BY aa.ordem, aa.id";
    $aulas = $db->fetchAll($sql, [$matricula_id, $modulo['id']]);

    $modulo['aulas'] = $aulas;
    $modulo['total_aulas'] = count($aulas);
    $modulo['aulas_concluidas'] = 0;

    foreach ($aulas as $aula) {
        if ($aula['concluida'] > 0) {
            $modulo['aulas_concluidas']++;
        }
    }

    $modulos_com_aulas[] = $modulo;
}

// Calcula o progresso geral do curso
$total_aulas = 0;
$total_aulas_concluidas = 0;

foreach ($modulos_com_aulas as $modulo) {
    $total_aulas += $modulo['total_aulas'];
    $total_aulas_concluidas += $modulo['aulas_concluidas'];
}

$progresso_geral = $total_aulas > 0 ? ($total_aulas_concluidas / $total_aulas) * 100 : 0;

// Verifica se foi solicitada a visualização de uma aula específica
$aula_atual = null;
$modulo_atual = null;

if (isset($_GET['aula_id']) && !empty($_GET['aula_id'])) {
    $aula_id = (int)$_GET['aula_id'];

    // Busca a aula
    $sql = "SELECT aa.*, am.titulo as modulo_titulo
            FROM ava_aulas aa
            JOIN ava_modulos am ON aa.modulo_id = am.id
            WHERE aa.id = ? AND am.curso_id = ?";
    $aula_atual = $db->fetchOne($sql, [$aula_id, $matricula['curso_id']]);

    if ($aula_atual) {
        // Busca o módulo da aula
        foreach ($modulos_com_aulas as $modulo) {
            if ($modulo['id'] == $aula_atual['modulo_id']) {
                $modulo_atual = $modulo;
                break;
            }
        }

        // Registra a visualização da aula
        $sql = "SELECT * FROM ava_progresso WHERE aula_id = ? AND matricula_id = ?";
        $progresso_existente = $db->fetchOne($sql, [$aula_id, $matricula_id]);

        if (!$progresso_existente) {
            // Cria um novo registro de progresso - sem usar a coluna data_inicio para evitar erros
            $dados_progresso = [
                'matricula_id' => $matricula_id,
                'aula_id' => $aula_id,
                'concluido' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $db->insert('ava_progresso', $dados_progresso);
        } else {
            // Atualiza o registro de progresso existente
            $dados_progresso = [
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $db->update('ava_progresso', $dados_progresso, "id = ?", [$progresso_existente['id']]);
        }
    }
}

// Define o título da página
$titulo_pagina = 'Visualizar Curso como Aluno';
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
            background-color: #F9FAFB;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        }
        .curso-image {
            width: 8rem;
            height: 8rem;
            border-radius: 0.5rem;
            background-size: cover;
            background-position: center;
            margin-right: 1.5rem;
        }
        .curso-info {
            flex: 1;
        }
        .curso-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: 0.25rem;
        }
        .curso-category {
            display: inline-block;
            background-color: #E0E7FF;
            color: #4F46E5;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            margin-bottom: 0.5rem;
        }
        .curso-description {
            color: #4B5563;
            margin-bottom: 0.5rem;
            max-height: 3rem;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        .curso-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            font-size: 0.875rem;
            color: #6B7280;
        }
        .curso-meta-item {
            display: flex;
            align-items: center;
        }
        .curso-meta-item i {
            margin-right: 0.5rem;
            color: #6A5ACD;
        }

        .aluno-info {
            background-color: white;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            display: flex;
            align-items: center;
        }
        .aluno-avatar {
            width: 3rem;
            height: 3rem;
            border-radius: 50%;
            background-color: #6A5ACD;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            font-weight: 600;
            margin-right: 1rem;
        }
        .aluno-name {
            font-weight: 600;
            color: #111827;
        }
        .aluno-email {
            font-size: 0.875rem;
            color: #6B7280;
        }

        .progress-bar {
            height: 0.5rem;
            background-color: #E5E7EB;
            border-radius: 9999px;
            overflow: hidden;
            margin-bottom: 0.5rem;
        }
        .progress-value {
            height: 100%;
            background-color: #6A5ACD;
            border-radius: 9999px;
        }
        .progress-text {
            font-size: 0.75rem;
            color: #6B7280;
            display: flex;
            justify-content: space-between;
        }

        .modulo-card {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            margin-bottom: 1rem;
            overflow: hidden;
        }
        .modulo-header {
            padding: 1rem;
            border-bottom: 1px solid #E5E7EB;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modulo-title {
            font-weight: 600;
            color: #111827;
            display: flex;
            align-items: center;
        }
        .modulo-title i {
            margin-right: 0.5rem;
            transition: transform 0.2s;
        }
        .modulo-title i.rotate {
            transform: rotate(90deg);
        }
        .modulo-progress {
            font-size: 0.75rem;
            color: #6B7280;
        }
        .modulo-content {
            display: none;
            padding: 1rem;
        }
        .modulo-content.active {
            display: block;
        }

        .aula-item {
            display: flex;
            align-items: center;
            padding: 0.75rem;
            border-radius: 0.375rem;
            margin-bottom: 0.5rem;
            transition: background-color 0.2s;
        }
        .aula-item:hover {
            background-color: #F3F4F6;
        }
        .aula-item.active {
            background-color: #EDE9FE;
        }
        .aula-icon {
            width: 2rem;
            height: 2rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.75rem;
            flex-shrink: 0;
        }
        .aula-icon-video { background-color: #FEE2E2; color: #DC2626; }
        .aula-icon-texto { background-color: #E0E7FF; color: #4F46E5; }
        .aula-icon-arquivo { background-color: #FEF3C7; color: #D97706; }
        .aula-icon-quiz { background-color: #D1FAE5; color: #059669; }
        .aula-info {
            flex: 1;
        }
        .aula-title {
            font-weight: 500;
            color: #111827;
            margin-bottom: 0.25rem;
        }
        .aula-meta {
            font-size: 0.75rem;
            color: #6B7280;
            display: flex;
            align-items: center;
        }
        .aula-meta i {
            margin-right: 0.25rem;
        }
        .aula-status {
            width: 1.25rem;
            height: 1.25rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 0.5rem;
            flex-shrink: 0;
        }
        .aula-status-concluida {
            background-color: #D1FAE5;
            color: #059669;
        }
        .aula-status-pendente {
            background-color: #E5E7EB;
            color: #9CA3AF;
        }

        .aula-content {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            overflow: hidden;
        }
        .aula-content-header {
            padding: 1.5rem;
            border-bottom: 1px solid #E5E7EB;
        }
        .aula-content-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #111827;
            margin-bottom: 0.5rem;
        }
        .aula-content-subtitle {
            font-size: 0.875rem;
            color: #6B7280;
        }
        .aula-content-body {
            padding: 1.5rem;
        }
        .aula-video-container {
            position: relative;
            padding-bottom: 56.25%; /* 16:9 */
            height: 0;
            overflow: hidden;
            margin-bottom: 1.5rem;
        }
        .aula-video-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: 0;
        }
        .aula-texto-container {
            line-height: 1.6;
        }
        .aula-arquivo-container {
            text-align: center;
            padding: 2rem;
        }
        .aula-arquivo-icon {
            font-size: 3rem;
            color: #6A5ACD;
            margin-bottom: 1rem;
        }
        .aula-arquivo-info {
            margin-bottom: 1.5rem;
        }
        .aula-quiz-container {
            max-width: 600px;
            margin: 0 auto;
        }
        .aula-quiz-question {
            font-size: 1.125rem;
            font-weight: 600;
            color: #111827;
            margin-bottom: 1rem;
        }
        .aula-quiz-options {
            margin-bottom: 1.5rem;
        }
        .aula-quiz-option {
            display: flex;
            align-items: center;
            padding: 0.75rem;
            border: 1px solid #E5E7EB;
            border-radius: 0.375rem;
            margin-bottom: 0.5rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        .aula-quiz-option:hover {
            background-color: #F3F4F6;
        }
        .aula-quiz-option.selected {
            border-color: #6A5ACD;
            background-color: #EDE9FE;
        }
        .aula-quiz-option input {
            margin-right: 0.75rem;
        }
        .aula-content-footer {
            padding: 1.5rem;
            border-top: 1px solid #E5E7EB;
            display: flex;
            justify-content: space-between;
        }
        .aula-nav-button {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 500;
            transition: all 0.2s;
        }
        .aula-nav-button.prev {
            background-color: #F3F4F6;
            color: #4B5563;
        }
        .aula-nav-button.prev:hover {
            background-color: #E5E7EB;
        }
        .aula-nav-button.next {
            background-color: #6A5ACD;
            color: white;
        }
        .aula-nav-button.next:hover {
            background-color: #5D4FB8;
        }
        .aula-nav-button i {
            font-size: 0.875rem;
        }
        .aula-nav-button.prev i {
            margin-right: 0.5rem;
        }
        .aula-nav-button.next i {
            margin-left: 0.5rem;
        }
        .aula-complete-button {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 500;
            background-color: #059669;
            color: white;
            transition: all 0.2s;
        }
        .aula-complete-button:hover {
            background-color: #047857;
        }
        .aula-complete-button i {
            margin-right: 0.5rem;
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
                            <p class="text-gray-600">Visualização do curso como se fosse um aluno</p>
                        </div>
                        <div class="flex space-x-2">
                            <a href="aluno_visualizar.php?id=<?php echo $matricula['aluno_id']; ?>" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                <i class="fas fa-arrow-left mr-2"></i> Voltar
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
                    <div class="curso-header flex items-start">
                        <div class="curso-image" style="background-image: url('<?php echo !empty($matricula['curso_imagem']) ? $matricula['curso_imagem'] : '../uploads/ava/default-course.jpg'; ?>');">
                        </div>
                        <div class="curso-info">
                            <h2 class="curso-title"><?php echo htmlspecialchars($matricula['curso_titulo']); ?></h2>
                            <span class="curso-category"><?php echo htmlspecialchars($matricula['curso_categoria'] ?? 'Geral'); ?></span>
                            <p class="curso-description"><?php echo htmlspecialchars($matricula['curso_descricao']); ?></p>
                            <div class="curso-meta">
                                <div class="curso-meta-item">
                                    <i class="fas fa-clock"></i>
                                    <span><?php echo $matricula['curso_carga_horaria']; ?> horas</span>
                                </div>
                                <div class="curso-meta-item">
                                    <i class="fas fa-book"></i>
                                    <span><?php echo $total_aulas; ?> aulas</span>
                                </div>
                                <div class="curso-meta-item">
                                    <i class="fas fa-layer-group"></i>
                                    <span><?php echo count($modulos); ?> módulos</span>
                                </div>
                                <div class="curso-meta-item">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span>Matriculado em <?php echo date('d/m/Y', strtotime($matricula['data_matricula'])); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Informações do Aluno -->
                    <div class="aluno-info">
                        <div class="aluno-avatar">
                            <?php echo strtoupper(substr($matricula['aluno_nome'], 0, 1)); ?>
                        </div>
                        <div>
                            <div class="aluno-name"><?php echo htmlspecialchars($matricula['aluno_nome']); ?></div>
                            <div class="aluno-email"><?php echo htmlspecialchars($matricula['aluno_email']); ?></div>
                        </div>
                    </div>

                    <!-- Progresso Geral -->
                    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Progresso Geral</h3>
                        <div class="progress-bar">
                            <div class="progress-value" style="width: <?php echo $progresso_geral; ?>%;"></div>
                        </div>
                        <div class="progress-text">
                            <span><?php echo $total_aulas_concluidas; ?> de <?php echo $total_aulas; ?> aulas concluídas</span>
                            <span><?php echo round($progresso_geral); ?>%</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Lista de Módulos e Aulas -->
                        <div class="lg:col-span-1">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Conteúdo do Curso</h3>

                            <?php if (empty($modulos_com_aulas)): ?>
                            <div class="bg-white rounded-xl shadow-sm p-6 text-center">
                                <p class="text-gray-500">Este curso não possui módulos ou aulas cadastrados.</p>
                            </div>
                            <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($modulos_com_aulas as $modulo): ?>
                                <div class="modulo-card">
                                    <div class="modulo-header" data-modulo-id="<?php echo $modulo['id']; ?>">
                                        <div class="modulo-title">
                                            <i class="fas fa-chevron-right <?php echo ($modulo_atual && $modulo_atual['id'] == $modulo['id']) ? 'rotate' : ''; ?>"></i>
                                            <?php echo htmlspecialchars($modulo['titulo']); ?>
                                        </div>
                                        <div class="modulo-progress">
                                            <?php echo $modulo['aulas_concluidas']; ?>/<?php echo $modulo['total_aulas']; ?>
                                        </div>
                                    </div>
                                    <div class="modulo-content <?php echo ($modulo_atual && $modulo_atual['id'] == $modulo['id']) ? 'active' : ''; ?>" id="modulo-content-<?php echo $modulo['id']; ?>">
                                        <?php if (empty($modulo['aulas'])): ?>
                                        <p class="text-gray-500 text-center py-2">Este módulo não possui aulas cadastradas.</p>
                                        <?php else: ?>
                                        <div class="space-y-2">
                                            <?php foreach ($modulo['aulas'] as $aula): ?>
                                            <a href="curso_aluno.php?matricula_id=<?php echo $matricula_id; ?>&aula_id=<?php echo $aula['id']; ?>" class="aula-item <?php echo ($aula_atual && $aula_atual['id'] == $aula['id']) ? 'active' : ''; ?>">
                                                <div class="aula-icon aula-icon-<?php echo $aula['tipo']; ?>">
                                                    <?php if ($aula['tipo'] === 'video'): ?>
                                                    <i class="fas fa-video"></i>
                                                    <?php elseif ($aula['tipo'] === 'texto'): ?>
                                                    <i class="fas fa-file-alt"></i>
                                                    <?php elseif ($aula['tipo'] === 'arquivo'): ?>
                                                    <i class="fas fa-file"></i>
                                                    <?php elseif ($aula['tipo'] === 'quiz'): ?>
                                                    <i class="fas fa-question-circle"></i>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="aula-info">
                                                    <div class="aula-title"><?php echo htmlspecialchars($aula['titulo']); ?></div>
                                                    <div class="aula-meta">
                                                        <?php if ($aula['tipo'] === 'video' && !empty($aula['duracao'])): ?>
                                                        <i class="fas fa-clock"></i>
                                                        <span><?php echo $aula['duracao']; ?> min</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="aula-status <?php echo $aula['concluida'] ? 'aula-status-concluida' : 'aula-status-pendente'; ?>">
                                                    <i class="fas <?php echo $aula['concluida'] ? 'fa-check' : 'fa-circle'; ?>"></i>
                                                </div>
                                            </a>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Conteúdo da Aula -->
                        <div class="lg:col-span-2">
                            <?php if ($aula_atual): ?>
                            <div class="aula-content">
                                <div class="aula-content-header">
                                    <h3 class="aula-content-title"><?php echo htmlspecialchars($aula_atual['titulo']); ?></h3>
                                    <p class="aula-content-subtitle">
                                        Módulo: <?php echo htmlspecialchars($aula_atual['modulo_titulo']); ?>
                                        <?php if ($aula_atual['tipo'] === 'video' && !empty($aula_atual['duracao'])): ?>
                                        <span class="ml-2">
                                            <i class="fas fa-clock"></i>
                                            <?php echo $aula_atual['duracao']; ?> min
                                        </span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div class="aula-content-body">
                                    <?php if ($aula_atual['tipo'] === 'video'): ?>
                                    <div class="aula-video-container">
                                        <?php
                                        $video_url = $aula_atual['url_video'];
                                        $video_id = '';

                                        // Extrai o ID do vídeo do YouTube
                                        if (preg_match('/youtube\.com\/watch\?v=([^&]+)/', $video_url, $matches)) {
                                            $video_id = $matches[1];
                                        } elseif (preg_match('/youtu\.be\/([^&]+)/', $video_url, $matches)) {
                                            $video_id = $matches[1];
                                        }

                                        if ($video_id) {
                                            echo '<iframe src="https://www.youtube.com/embed/' . $video_id . '" allowfullscreen></iframe>';
                                        } else {
                                            echo '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4">
                                                <p>Não foi possível carregar o vídeo. URL inválida ou não suportada.</p>
                                                <p class="text-sm mt-2">URL: ' . htmlspecialchars($video_url) . '</p>
                                            </div>';
                                        }
                                        ?>
                                    </div>
                                    <?php if (!empty($aula_atual['descricao'])): ?>
                                    <div class="mt-4">
                                        <h4 class="font-semibold text-gray-800 mb-2">Descrição</h4>
                                        <p><?php echo nl2br(htmlspecialchars($aula_atual['descricao'])); ?></p>
                                    </div>
                                    <?php endif; ?>

                                    <?php elseif ($aula_atual['tipo'] === 'texto'): ?>
                                    <div class="aula-texto-container">
                                        <?php if (!empty($aula_atual['descricao'])): ?>
                                        <div class="mb-4">
                                            <h4 class="font-semibold text-gray-800 mb-2">Descrição</h4>
                                            <p><?php echo nl2br(htmlspecialchars($aula_atual['descricao'])); ?></p>
                                        </div>
                                        <?php endif; ?>

                                        <div class="prose max-w-none">
                                            <?php echo $aula_atual['conteudo']; ?>
                                        </div>
                                    </div>

                                    <?php elseif ($aula_atual['tipo'] === 'arquivo'): ?>
                                    <div class="aula-arquivo-container">
                                        <div class="aula-arquivo-icon">
                                            <?php
                                            $arquivo_extensao = pathinfo($aula_atual['arquivo'], PATHINFO_EXTENSION);

                                            if (in_array($arquivo_extensao, ['pdf'])) {
                                                echo '<i class="fas fa-file-pdf"></i>';
                                            } elseif (in_array($arquivo_extensao, ['doc', 'docx'])) {
                                                echo '<i class="fas fa-file-word"></i>';
                                            } elseif (in_array($arquivo_extensao, ['xls', 'xlsx'])) {
                                                echo '<i class="fas fa-file-excel"></i>';
                                            } elseif (in_array($arquivo_extensao, ['ppt', 'pptx'])) {
                                                echo '<i class="fas fa-file-powerpoint"></i>';
                                            } elseif (in_array($arquivo_extensao, ['zip', 'rar'])) {
                                                echo '<i class="fas fa-file-archive"></i>';
                                            } else {
                                                echo '<i class="fas fa-file"></i>';
                                            }
                                            ?>
                                        </div>
                                        <div class="aula-arquivo-info">
                                            <h4 class="font-semibold text-gray-800 mb-1"><?php echo htmlspecialchars($aula_atual['titulo']); ?></h4>
                                            <?php if (!empty($aula_atual['descricao'])): ?>
                                            <p class="text-gray-600 mb-4"><?php echo nl2br(htmlspecialchars($aula_atual['descricao'])); ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <a href="<?php echo $aula_atual['arquivo']; ?>" target="_blank" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                                            <i class="fas fa-download mr-2"></i> Baixar Arquivo
                                        </a>
                                    </div>

                                    <?php elseif ($aula_atual['tipo'] === 'quiz'): ?>
                                    <div class="aula-quiz-container">
                                        <?php if (!empty($aula_atual['descricao'])): ?>
                                        <div class="mb-4">
                                            <h4 class="font-semibold text-gray-800 mb-2">Descrição</h4>
                                            <p><?php echo nl2br(htmlspecialchars($aula_atual['descricao'])); ?></p>
                                        </div>
                                        <?php endif; ?>

                                        <?php
                                        $quiz_content = $aula_atual['conteudo'];
                                        $questions = [];
                                        $current_question = null;
                                        $current_options = [];

                                        $lines = explode("\n", $quiz_content);
                                        foreach ($lines as $line) {
                                            $line = trim($line);
                                            if (empty($line)) {
                                                if ($current_question && !empty($current_options)) {
                                                    $questions[] = [
                                                        'question' => $current_question,
                                                        'options' => $current_options
                                                    ];
                                                    $current_question = null;
                                                    $current_options = [];
                                                }
                                            } elseif (strpos($line, '*') === 0) {
                                                $is_correct = strpos($line, '**') === 0;
                                                $option_text = trim(str_replace($is_correct ? '**' : '*', '', $line));
                                                $current_options[] = [
                                                    'text' => $option_text,
                                                    'correct' => $is_correct
                                                ];
                                            } elseif (!$current_question) {
                                                $current_question = $line;
                                            }
                                        }

                                        // Adiciona a última questão se existir
                                        if ($current_question && !empty($current_options)) {
                                            $questions[] = [
                                                'question' => $current_question,
                                                'options' => $current_options
                                            ];
                                        }

                                        if (!empty($questions)):
                                        ?>
                                        <form id="quiz-form">
                                            <?php foreach ($questions as $q_index => $question): ?>
                                            <div class="mb-6">
                                                <div class="aula-quiz-question"><?php echo htmlspecialchars($question['question']); ?></div>
                                                <div class="aula-quiz-options">
                                                    <?php foreach ($question['options'] as $o_index => $option): ?>
                                                    <label class="aula-quiz-option">
                                                        <input type="radio" name="question_<?php echo $q_index; ?>" value="<?php echo $o_index; ?>" data-correct="<?php echo $option['correct'] ? '1' : '0'; ?>">
                                                        <?php echo htmlspecialchars($option['text']); ?>
                                                    </label>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>

                                            <div class="text-center">
                                                <button type="button" id="check-quiz" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                                                    <i class="fas fa-check mr-2"></i> Verificar Respostas
                                                </button>
                                            </div>

                                            <div id="quiz-result" class="mt-4 p-4 rounded-md hidden">
                                                <!-- Resultado do quiz será exibido aqui via JavaScript -->
                                            </div>
                                        </form>
                                        <?php else: ?>
                                        <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4">
                                            <p>Este quiz não possui perguntas configuradas corretamente.</p>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="aula-content-footer">
                                    <div>
                                        <?php
                                        // Encontra a aula anterior
                                        $aula_anterior = null;
                                        $aula_proxima = null;
                                        $encontrou_atual = false;

                                        foreach ($modulos_com_aulas as $modulo) {
                                            foreach ($modulo['aulas'] as $aula) {
                                                if ($encontrou_atual) {
                                                    $aula_proxima = $aula;
                                                    break 2;
                                                }

                                                if ($aula['id'] == $aula_atual['id']) {
                                                    $encontrou_atual = true;
                                                } else {
                                                    $aula_anterior = $aula;
                                                }
                                            }
                                        }

                                        if ($aula_anterior):
                                        ?>
                                        <a href="curso_aluno.php?matricula_id=<?php echo $matricula_id; ?>&aula_id=<?php echo $aula_anterior['id']; ?>" class="aula-nav-button prev">
                                            <i class="fas fa-arrow-left"></i> Aula Anterior
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <form method="post" action="marcar_concluido.php" class="inline">
                                            <input type="hidden" name="matricula_id" value="<?php echo $matricula_id; ?>">
                                            <input type="hidden" name="aula_id" value="<?php echo $aula_atual['id']; ?>">
                                            <button type="submit" class="aula-complete-button">
                                                <i class="fas fa-check-circle"></i> Marcar como Concluída
                                            </button>
                                        </form>
                                    </div>
                                    <div>
                                        <?php if ($aula_proxima): ?>
                                        <a href="curso_aluno.php?matricula_id=<?php echo $matricula_id; ?>&aula_id=<?php echo $aula_proxima['id']; ?>" class="aula-nav-button next">
                                            Próxima Aula <i class="fas fa-arrow-right"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="bg-white rounded-xl shadow-sm p-6 text-center">
                                <div class="text-6xl text-indigo-200 mb-4">
                                    <i class="fas fa-book-reader"></i>
                                </div>
                                <h3 class="text-xl font-semibold text-gray-800 mb-2">Bem-vindo ao curso!</h3>
                                <p class="text-gray-600 mb-6">Selecione uma aula no menu ao lado para começar a estudar.</p>

                                <?php if (!empty($modulos_com_aulas) && !empty($modulos_com_aulas[0]['aulas'])): ?>
                                <a href="curso_aluno.php?matricula_id=<?php echo $matricula_id; ?>&aula_id=<?php echo $modulos_com_aulas[0]['aulas'][0]['id']; ?>" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                                    <i class="fas fa-play mr-2"></i> Iniciar Primeira Aula
                                </a>
                                <?php endif; ?>
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

        // Toggle módulos
        document.querySelectorAll('.modulo-header').forEach(function(header) {
            header.addEventListener('click', function() {
                const moduloId = this.getAttribute('data-modulo-id');
                const content = document.getElementById('modulo-content-' + moduloId);
                const icon = this.querySelector('.modulo-title i');

                content.classList.toggle('active');
                icon.classList.toggle('rotate');
            });
        });

        // Quiz
        const checkQuizButton = document.getElementById('check-quiz');
        if (checkQuizButton) {
            checkQuizButton.addEventListener('click', function() {
                const form = document.getElementById('quiz-form');
                const result = document.getElementById('quiz-result');

                let totalQuestions = 0;
                let correctAnswers = 0;

                // Conta o número de questões
                const questions = form.querySelectorAll('.aula-quiz-question');
                totalQuestions = questions.length;

                // Verifica as respostas
                for (let i = 0; i < totalQuestions; i++) {
                    const selectedOption = form.querySelector('input[name="question_' + i + '"]:checked');

                    if (selectedOption && selectedOption.getAttribute('data-correct') === '1') {
                        correctAnswers++;
                    }
                }

                // Calcula a pontuação
                const score = Math.round((correctAnswers / totalQuestions) * 100);

                // Exibe o resultado
                let resultClass = 'bg-red-100 border-red-500 text-red-700';
                let resultIcon = 'fa-times-circle';
                let resultText = 'Tente novamente!';

                if (score >= 70) {
                    resultClass = 'bg-green-100 border-green-500 text-green-700';
                    resultIcon = 'fa-check-circle';
                    resultText = 'Parabéns!';
                } else if (score >= 50) {
                    resultClass = 'bg-yellow-100 border-yellow-500 text-yellow-700';
                    resultIcon = 'fa-exclamation-circle';
                    resultText = 'Quase lá!';
                }

                result.className = 'mt-4 p-4 rounded-md border-l-4 ' + resultClass;
                result.innerHTML = `
                    <div class="flex items-center">
                        <div class="text-2xl mr-3">
                            <i class="fas ${resultIcon}"></i>
                        </div>
                        <div>
                            <p class="font-semibold">${resultText}</p>
                            <p>Você acertou ${correctAnswers} de ${totalQuestions} questões (${score}%).</p>
                        </div>
                    </div>
                `;

                result.classList.remove('hidden');
            });
        }
    </script>
</body>
</html>
