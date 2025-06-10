<?php
/**
 * Dashboard do AVA para o Polo
 * Página principal do Ambiente Virtual de Aprendizagem para o polo
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

// Registra log de acesso ao dashboard do AVA
if (function_exists('registrarLog')) {
    registrarLog(
        'ava',
        'acesso_dashboard',
        'Usuário acessou o dashboard do AVA',
        $polo_id,
        'polo',
        null,
        [
            'user_id' => getUsuarioId(),
            'polo_id' => $polo_id,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Desconhecido',
            'timestamp' => date('Y-m-d H:i:s')
        ]
    );
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

if (!$acesso) {
    // Se não encontrou registro, tenta criar um
    try {
        $sql = "INSERT INTO ava_polos_acesso (polo_id, liberado, data_liberacao, liberado_por, created_at, updated_at)
                VALUES (?, 1, NOW(), 1, NOW(), NOW())";
        $db->query($sql, [$polo_id]);

        // Busca o registro recém-criado
        $sql = "SELECT * FROM ava_polos_acesso WHERE polo_id = ?";
        $acesso = $db->fetchOne($sql, [$polo_id]);
    } catch (Exception $e) {
        // Ignora o erro e continua com $acesso = null
    }
}

if (!$acesso || $acesso['liberado'] != 1) {
    setMensagem('erro', 'Seu polo não possui acesso liberado ao AVA. Entre em contato com a secretaria para mais informações.');
    redirect('../polo/index.php');
    exit;
}

// Busca informações do polo
$sql = "SELECT * FROM polos WHERE id = ?";
$polo = $db->fetchOne($sql, [$polo_id]);

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

// Conta os cursos por status
$cursos_por_status = [
    'rascunho' => 0,
    'revisao' => 0,
    'publicado' => 0,
    'arquivado' => 0
];

foreach ($cursos as $curso) {
    $cursos_por_status[$curso['status']]++;
}

// Busca os alunos matriculados em cursos do polo
$sql = "SELECT COUNT(DISTINCT am.aluno_id) as total_alunos
        FROM ava_matriculas am
        JOIN ava_cursos ac ON am.curso_id = ac.id
        WHERE ac.polo_id = ?";
$total_alunos = $db->fetchOne($sql, [$polo_id]);

// Busca os últimos alunos matriculados
$sql = "SELECT am.*, a.nome as aluno_nome, a.email as aluno_email, ac.titulo as curso_titulo
        FROM ava_matriculas am
        JOIN alunos a ON am.aluno_id = a.id
        JOIN ava_cursos ac ON am.curso_id = ac.id
        WHERE ac.polo_id = ?
        ORDER BY am.data_matricula DESC
        LIMIT 5";
$ultimas_matriculas = $db->fetchAll($sql, [$polo_id]);

// Define o título da página
$titulo_pagina = 'Dashboard do AVA';
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

        .stat-card {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            padding: 1.5rem;
            display: flex;
            align-items: center;
        }
        .stat-icon {
            width: 3rem;
            height: 3rem;
            border-radius: 9999px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            color: white;
            font-size: 1.25rem;
        }
        .stat-info {
            flex: 1;
        }
        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: 0.25rem;
        }
        .stat-label {
            font-size: 0.875rem;
            color: #6B7280;
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
        .aluno-curso {
            font-size: 0.75rem;
            color: #6A5ACD;
            margin-top: 0.25rem;
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
                            <p class="text-gray-600">Bem-vindo ao Ambiente Virtual de Aprendizagem</p>
                        </div>
                        <a href="cursos.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                            <i class="fas fa-book mr-2"></i> Gerenciar Cursos
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

                    <!-- Estatísticas -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                        <div class="stat-card">
                            <div class="stat-icon" style="background-color: #6A5ACD;">
                                <i class="fas fa-book"></i>
                            </div>
                            <div class="stat-info">
                                <div class="stat-value"><?php echo count($cursos); ?></div>
                                <div class="stat-label">Total de Cursos</div>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon" style="background-color: #059669;">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-info">
                                <div class="stat-value"><?php echo $cursos_por_status['publicado']; ?></div>
                                <div class="stat-label">Cursos Publicados</div>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon" style="background-color: #D97706;">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-info">
                                <div class="stat-value"><?php echo $cursos_por_status['rascunho'] + $cursos_por_status['revisao']; ?></div>
                                <div class="stat-label">Cursos em Desenvolvimento</div>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon" style="background-color: #4F46E5;">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-info">
                                <div class="stat-value"><?php echo $total_alunos['total_alunos'] ?? 0; ?></div>
                                <div class="stat-label">Alunos Matriculados</div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Coluna da Esquerda - Cursos Recentes -->
                        <div class="lg:col-span-2">
                            <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
                                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                                    <h2 class="text-lg font-semibold text-gray-800">Cursos Recentes</h2>
                                    <a href="cursos.php" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                                        Ver Todos <i class="fas fa-arrow-right ml-1"></i>
                                    </a>
                                </div>
                                <div class="p-6">
                                    <?php if (empty($cursos)): ?>
                                    <div class="text-center text-gray-500 py-4">
                                        <p>Nenhum curso cadastrado.</p>
                                        <a href="cursos_novo.php" class="inline-flex items-center mt-4 px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                                            <i class="fas fa-plus mr-2"></i> Criar Novo Curso
                                        </a>
                                    </div>
                                    <?php else: ?>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <?php
                                        // Exibe apenas os 4 cursos mais recentes
                                        $cursos_recentes = array_slice($cursos, 0, 4);
                                        foreach ($cursos_recentes as $curso):
                                        ?>
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
                                                    <a href="curso_editar.php?id=<?php echo $curso['id']; ?>" class="inline-flex items-center px-3 py-1.5 bg-indigo-100 text-indigo-800 text-xs font-medium rounded hover:bg-indigo-200">
                                                        <i class="fas fa-edit mr-1"></i> Editar
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Status dos Cursos -->
                            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                                <div class="px-6 py-4 border-b border-gray-200">
                                    <h2 class="text-lg font-semibold text-gray-800">Status dos Cursos</h2>
                                </div>
                                <div class="p-6">
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                        <div class="bg-gray-50 p-4 rounded-lg text-center">
                                            <div class="text-3xl font-bold text-gray-600"><?php echo $cursos_por_status['rascunho']; ?></div>
                                            <div class="text-sm text-gray-500 mt-1">Rascunhos</div>
                                        </div>

                                        <div class="bg-yellow-50 p-4 rounded-lg text-center">
                                            <div class="text-3xl font-bold text-yellow-600"><?php echo $cursos_por_status['revisao']; ?></div>
                                            <div class="text-sm text-yellow-500 mt-1">Em Revisão</div>
                                        </div>

                                        <div class="bg-green-50 p-4 rounded-lg text-center">
                                            <div class="text-3xl font-bold text-green-600"><?php echo $cursos_por_status['publicado']; ?></div>
                                            <div class="text-sm text-green-500 mt-1">Publicados</div>
                                        </div>

                                        <div class="bg-indigo-50 p-4 rounded-lg text-center">
                                            <div class="text-3xl font-bold text-indigo-600"><?php echo $cursos_por_status['arquivado']; ?></div>
                                            <div class="text-sm text-indigo-500 mt-1">Arquivados</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Coluna da Direita - Últimas Matrículas -->
                        <div>
                            <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
                                <div class="px-6 py-4 border-b border-gray-200">
                                    <h2 class="text-lg font-semibold text-gray-800">Últimas Matrículas</h2>
                                </div>
                                <div class="p-6">
                                    <?php if (empty($ultimas_matriculas)): ?>
                                    <div class="text-center text-gray-500 py-4">
                                        <p>Nenhuma matrícula recente.</p>
                                    </div>
                                    <?php else: ?>
                                        <?php foreach ($ultimas_matriculas as $matricula): ?>
                                        <div class="aluno-item">
                                            <div class="aluno-avatar">
                                                <?php echo strtoupper(substr($matricula['aluno_nome'], 0, 1)); ?>
                                            </div>
                                            <div class="aluno-info">
                                                <div class="aluno-name"><?php echo htmlspecialchars($matricula['aluno_nome']); ?></div>
                                                <div class="aluno-email"><?php echo htmlspecialchars($matricula['aluno_email']); ?></div>
                                                <div class="aluno-curso">
                                                    <i class="fas fa-book mr-1"></i> <?php echo htmlspecialchars($matricula['curso_titulo']); ?>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Links Rápidos -->
                            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                                <div class="px-6 py-4 border-b border-gray-200">
                                    <h2 class="text-lg font-semibold text-gray-800">Links Rápidos</h2>
                                </div>
                                <div class="p-6">
                                    <div class="space-y-4">
                                        <a href="cursos_novo.php" class="flex items-center p-3 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition-colors">
                                            <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 mr-3">
                                                <i class="fas fa-plus"></i>
                                            </div>
                                            <div>
                                                <div class="font-medium text-indigo-800">Criar Novo Curso</div>
                                                <div class="text-sm text-indigo-600">Adicione um novo curso ao AVA</div>
                                            </div>
                                        </a>

                                        <a href="../polo/alunos.php" class="flex items-center p-3 bg-green-50 rounded-lg hover:bg-green-100 transition-colors">
                                            <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center text-green-600 mr-3">
                                                <i class="fas fa-users"></i>
                                            </div>
                                            <div>
                                                <div class="font-medium text-green-800">Gerenciar Alunos</div>
                                                <div class="text-sm text-green-600">Visualize e gerencie alunos matriculados</div>
                                            </div>
                                        </a>

                                        <a href="alunos.php" class="flex items-center p-3 bg-pink-50 rounded-lg hover:bg-pink-100 transition-colors">
                                            <div class="w-10 h-10 rounded-full bg-pink-100 flex items-center justify-center text-pink-600 mr-3">
                                                <i class="fas fa-user-graduate"></i>
                                            </div>
                                            <div>
                                                <div class="font-medium text-pink-800">Alunos do AVA</div>
                                                <div class="text-sm text-pink-600">Visualize alunos e seu progresso no AVA</div>
                                            </div>
                                        </a>

                                        <a href="relatorios.php" class="flex items-center p-3 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                                            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 mr-3">
                                                <i class="fas fa-chart-bar"></i>
                                            </div>
                                            <div>
                                                <div class="font-medium text-blue-800">Relatórios</div>
                                                <div class="text-sm text-blue-600">Acesse relatórios e estatísticas</div>
                                            </div>
                                        </a>

                                        <a href="ajuda.php" class="flex items-center p-3 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors">
                                            <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center text-purple-600 mr-3">
                                                <i class="fas fa-question-circle"></i>
                                            </div>
                                            <div>
                                                <div class="font-medium text-purple-800">Ajuda e Suporte</div>
                                                <div class="text-sm text-purple-600">Obtenha ajuda sobre o AVA</div>
                                            </div>
                                        </a>

                                        <?php
                                        // Busca uma matrícula ativa para visualizar como aluno (sem restrição de polo)
                                        $sql = "SELECT am.id, ac.titulo
                                               FROM ava_matriculas am
                                               JOIN ava_cursos ac ON am.curso_id = ac.id
                                               JOIN alunos a ON am.aluno_id = a.id
                                               ORDER BY am.created_at DESC LIMIT 1";
                                        $matricula_demo = $db->fetchOne($sql);

                                        if ($matricula_demo):
                                        ?>
                                        <a href="curso_aluno.php?matricula_id=<?php echo $matricula_demo['id']; ?>" class="flex items-center p-3 bg-teal-50 rounded-lg hover:bg-teal-100 transition-colors">
                                            <div class="w-10 h-10 rounded-full bg-teal-100 flex items-center justify-center text-teal-600 mr-3">
                                                <i class="fas fa-user-graduate"></i>
                                            </div>
                                            <div>
                                                <div class="font-medium text-teal-800">Visualizar como Aluno</div>
                                                <div class="text-sm text-teal-600">Veja o curso <?php echo htmlspecialchars(substr($matricula_demo['titulo'], 0, 20) . (strlen($matricula_demo['titulo']) > 20 ? '...' : '')); ?> na visão do aluno</div>
                                            </div>
                                        </a>
                                        <?php endif; ?>

                                        <?php if (getUsuarioTipo() === 'admin'): ?>
                                        <a href="atualizar_tabelas.php" class="flex items-center p-3 bg-red-50 rounded-lg hover:bg-red-100 transition-colors mb-3">
                                            <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center text-red-600 mr-3">
                                                <i class="fas fa-database"></i>
                                            </div>
                                            <div>
                                                <div class="font-medium text-red-800">Atualizar Tabelas</div>
                                                <div class="text-sm text-red-600">Atualizar estrutura das tabelas do AVA</div>
                                            </div>
                                        </a>

                                        <a href="sql_fix_aulas.php" class="flex items-center p-3 bg-yellow-50 rounded-lg hover:bg-yellow-100 transition-colors mb-3">
                                            <div class="w-10 h-10 rounded-full bg-yellow-100 flex items-center justify-center text-yellow-600 mr-3">
                                                <i class="fas fa-tools"></i>
                                            </div>
                                            <div>
                                                <div class="font-medium text-yellow-800">Corrigir Tabela Aulas</div>
                                                <div class="text-sm text-yellow-600">Adicionar colunas faltantes à tabela de aulas</div>
                                            </div>
                                        </a>

                                        <a href="sql_fix_progresso.php" class="flex items-center p-3 bg-orange-50 rounded-lg hover:bg-orange-100 transition-colors mb-3">
                                            <div class="w-10 h-10 rounded-full bg-orange-100 flex items-center justify-center text-orange-600 mr-3">
                                                <i class="fas fa-wrench"></i>
                                            </div>
                                            <div>
                                                <div class="font-medium text-orange-800">Corrigir Tabela Progresso</div>
                                                <div class="text-sm text-orange-600">Adicionar colunas faltantes à tabela de progresso</div>
                                            </div>
                                        </a>

                                        <a href="fix_progresso_table.php" class="flex items-center p-3 bg-red-50 rounded-lg hover:bg-red-100 transition-colors mb-3">
                                            <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center text-red-600 mr-3">
                                                <i class="fas fa-exclamation-triangle"></i>
                                            </div>
                                            <div>
                                                <div class="font-medium text-red-800">Recriar Tabela Progresso</div>
                                                <div class="text-sm text-red-600">Solução de emergência: recria a tabela de progresso</div>
                                            </div>
                                        </a>

                                        <a href="sql_fix_acessos.php" class="flex items-center p-3 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors mb-3">
                                            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 mr-3">
                                                <i class="fas fa-chart-line"></i>
                                            </div>
                                            <div>
                                                <div class="font-medium text-blue-800">Criar Tabela Acessos</div>
                                                <div class="text-sm text-blue-600">Cria a tabela para estatísticas de acesso</div>
                                            </div>
                                        </a>

                                        <a href="sql_add_aluno_exemplo.php" class="flex items-center p-3 bg-green-50 rounded-lg hover:bg-green-100 transition-colors mb-3">
                                            <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center text-green-600 mr-3">
                                                <i class="fas fa-user-plus"></i>
                                            </div>
                                            <div>
                                                <div class="font-medium text-green-800">Adicionar Aluno Exemplo</div>
                                                <div class="text-sm text-green-600">Cria um aluno de exemplo para visualização</div>
                                            </div>
                                        </a>

                                        <a href="menu_teste.php" class="flex items-center p-3 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition-colors mb-3">
                                            <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 mr-3">
                                                <i class="fas fa-list"></i>
                                            </div>
                                            <div>
                                                <div class="font-medium text-indigo-800">Teste de Menu</div>
                                                <div class="text-sm text-indigo-600">Testar o novo layout do menu lateral</div>
                                            </div>
                                        </a>

                                        <a href="exemplo_layout.php" class="flex items-center p-3 bg-green-50 rounded-lg hover:bg-green-100 transition-colors">
                                            <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center text-green-600 mr-3">
                                                <i class="fas fa-file-code"></i>
                                            </div>
                                            <div>
                                                <div class="font-medium text-green-800">Exemplo de Layout</div>
                                                <div class="text-sm text-green-600">Ver exemplo do novo layout padronizado</div>
                                            </div>
                                        </a>
                                        <?php endif; ?>
                                    </div>
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
