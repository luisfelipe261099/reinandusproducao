<?php
/**
 * Progresso do Aluno no AVA
 * Exibe o progresso detalhado de um aluno nos cursos do Ambiente Virtual de Aprendizagem
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

// Verifica se o ID do aluno foi informado
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setMensagem('erro', 'Aluno não informado.');
    redirect('alunos.php');
    exit;
}

$aluno_id = (int)$_GET['id'];

// Busca o aluno - primeiro tenta pelo ID
$sql = "SELECT * FROM alunos WHERE id = ?";
$aluno = $db->fetchOne($sql, [$aluno_id]);

// Se não encontrar pelo ID, busca qualquer aluno
if (!$aluno) {
    // Tenta buscar qualquer aluno no sistema
    $sql = "SELECT * FROM alunos ORDER BY id LIMIT 1";
    $aluno = $db->fetchOne($sql);

    // Se ainda não encontrar, cria um aluno fictício
    if (!$aluno) {
        $aluno = [
            'id' => 1,
            'nome' => 'Aluno Exemplo',
            'email' => 'aluno@exemplo.com',
            'created_at' => date('Y-m-d H:i:s')
        ];
    }

    // Atualiza o ID do aluno para o que foi encontrado
    $aluno_id = $aluno['id'];

    // Adiciona uma mensagem informativa
    setMensagem('erro', 'O aluno com ID ' . $_GET['id'] . ' não foi encontrado. Mostrando outro aluno disponível.');
}

// Busca as matrículas do aluno
$sql = "SELECT am.*, ac.titulo as curso_titulo, ac.categoria as curso_categoria, ac.imagem as curso_imagem,
        (SELECT COUNT(*) FROM ava_progresso ap WHERE ap.matricula_id = am.id AND ap.concluido = 1) as aulas_concluidas,
        (SELECT COUNT(*) FROM ava_aulas aa
         JOIN ava_modulos amod ON aa.modulo_id = amod.id
         WHERE amod.curso_id = am.curso_id) as total_aulas
        FROM ava_matriculas am
        JOIN ava_cursos ac ON am.curso_id = ac.id
        WHERE am.aluno_id = ?
        ORDER BY am.status, am.created_at DESC";
$matriculas = $db->fetchAll($sql, [$aluno_id]);

// Busca o histórico de atividades do aluno
$sql = "SELECT ap.*, aa.titulo as aula_titulo, aa.tipo as aula_tipo, amod.titulo as modulo_titulo,
        ac.titulo as curso_titulo, ac.id as curso_id
        FROM ava_progresso ap
        JOIN ava_aulas aa ON ap.aula_id = aa.id
        JOIN ava_modulos amod ON aa.modulo_id = amod.id
        JOIN ava_cursos ac ON amod.curso_id = ac.id
        JOIN ava_matriculas am ON ap.matricula_id = am.id
        WHERE am.aluno_id = ?
        ORDER BY ap.updated_at DESC
        LIMIT 50";
$atividades = $db->fetchAll($sql, [$aluno_id]);

// Verifica se a tabela ava_acessos existe
$sql_check = "SHOW TABLES LIKE 'ava_acessos'";
$tabela_acessos_existe = $db->fetchOne($sql_check);

// Inicializa estatísticas com valores padrão
$estatisticas = [
    'total_acessos' => 0,
    'ultimo_acesso' => null,
    'tempo_total' => 0,
    'tempo_medio' => 0
];

// Busca as estatísticas de acesso do aluno apenas se a tabela existir
if ($tabela_acessos_existe) {
    try {
        $sql = "SELECT
                COUNT(*) as total_acessos,
                MAX(data_acesso) as ultimo_acesso,
                SUM(tempo_sessao) as tempo_total,
                AVG(tempo_sessao) as tempo_medio
                FROM ava_acessos
                WHERE aluno_id = ?";
        $estatisticas_db = $db->fetchOne($sql, [$aluno_id]);

        if ($estatisticas_db) {
            $estatisticas = $estatisticas_db;
        }
    } catch (Exception $e) {
        // Ignora erros e mantém os valores padrão
        error_log("Erro ao buscar estatísticas de acesso: " . $e->getMessage());
    }
}

// Define o título da página
$titulo_pagina = 'Progresso do Aluno';
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
        .aluno-header {
            background-color: #F9FAFB;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        }
        .aluno-avatar {
            width: 6rem;
            height: 6rem;
            border-radius: 50%;
            background-color: #6A5ACD;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 600;
            margin-right: 1.5rem;
        }
        .aluno-info {
            flex: 1;
        }
        .aluno-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: 0.25rem;
        }
        .aluno-email {
            font-size: 1rem;
            color: #4B5563;
            margin-bottom: 0.5rem;
        }

        .stat-card {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        .stat-icon {
            width: 3rem;
            height: 3rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            font-size: 1.25rem;
        }
        .stat-icon-blue {
            background-color: #E0E7FF;
            color: #4F46E5;
        }
        .stat-icon-green {
            background-color: #D1FAE5;
            color: #059669;
        }
        .stat-icon-yellow {
            background-color: #FEF3C7;
            color: #D97706;
        }
        .stat-icon-purple {
            background-color: #EDE9FE;
            color: #7C3AED;
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

        .curso-progress-card {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            padding: 1.5rem;
            margin-bottom: 1rem;
        }
        .curso-progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        .curso-progress-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #111827;
        }
        .curso-progress-status {
            font-size: 0.75rem;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
        }
        .curso-progress-status-ativo { background-color: #D1FAE5; color: #059669; }
        .curso-progress-status-inativo { background-color: #FEE2E2; color: #DC2626; }
        .curso-progress-status-pendente { background-color: #FEF3C7; color: #D97706; }
        .curso-progress-status-concluido { background-color: #E0E7FF; color: #4F46E5; }
        .curso-progress-bar {
            height: 0.75rem;
            background-color: #E5E7EB;
            border-radius: 9999px;
            overflow: hidden;
            margin-bottom: 0.5rem;
        }
        .curso-progress-value {
            height: 100%;
            background-color: #6A5ACD;
            border-radius: 9999px;
        }
        .curso-progress-text {
            display: flex;
            justify-content: space-between;
            font-size: 0.875rem;
            color: #6B7280;
        }
        .curso-progress-meta {
            display: flex;
            justify-content: space-between;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #E5E7EB;
            font-size: 0.875rem;
            color: #6B7280;
        }

        .activity-card {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            overflow: hidden;
        }
        .activity-header {
            background-color: #F9FAFB;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #E5E7EB;
        }
        .activity-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #111827;
        }
        .activity-list {
            max-height: 500px;
            overflow-y: auto;
        }
        .activity-item {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #E5E7EB;
            display: flex;
            align-items: flex-start;
        }
        .activity-item:last-child {
            border-bottom: none;
        }
        .activity-icon {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            flex-shrink: 0;
        }
        .activity-icon-video { background-color: #FEE2E2; color: #DC2626; }
        .activity-icon-texto { background-color: #E0E7FF; color: #4F46E5; }
        .activity-icon-arquivo { background-color: #FEF3C7; color: #D97706; }
        .activity-icon-quiz { background-color: #D1FAE5; color: #059669; }
        .activity-content {
            flex: 1;
        }
        .activity-content-title {
            font-weight: 600;
            color: #111827;
            margin-bottom: 0.25rem;
        }
        .activity-content-subtitle {
            font-size: 0.875rem;
            color: #6B7280;
            margin-bottom: 0.25rem;
        }
        .activity-content-meta {
            font-size: 0.75rem;
            color: #9CA3AF;
            display: flex;
            align-items: center;
        }
        .activity-content-meta i {
            margin-right: 0.25rem;
        }
        .activity-status {
            margin-left: auto;
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            flex-shrink: 0;
        }
        .activity-status-concluido { background-color: #D1FAE5; color: #059669; }
        .activity-status-pendente { background-color: #FEF3C7; color: #D97706; }
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
                            <p class="text-gray-600">Acompanhamento do progresso do aluno no Ambiente Virtual de Aprendizagem</p>
                        </div>
                        <div class="flex space-x-2">
                            <a href="aluno_visualizar.php?id=<?php echo $aluno_id; ?>" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
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

                    <!-- Cabeçalho do Aluno -->
                    <div class="aluno-header flex items-start mb-6">
                        <div class="aluno-avatar">
                            <?php echo strtoupper(substr($aluno['nome'], 0, 1)); ?>
                        </div>
                        <div class="aluno-info">
                            <h2 class="aluno-name"><?php echo htmlspecialchars($aluno['nome']); ?></h2>
                            <p class="aluno-email"><?php echo htmlspecialchars($aluno['email']); ?></p>
                        </div>
                    </div>

                    <!-- Estatísticas -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <div class="stat-card">
                            <div class="stat-icon stat-icon-blue">
                                <i class="fas fa-book"></i>
                            </div>
                            <div class="stat-value"><?php echo count($matriculas); ?></div>
                            <div class="stat-label">Cursos Matriculados</div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon stat-icon-green">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <?php
                            $total_aulas_concluidas = 0;
                            $total_aulas = 0;

                            foreach ($matriculas as $matricula) {
                                $total_aulas_concluidas += $matricula['aulas_concluidas'];
                                $total_aulas += $matricula['total_aulas'];
                            }

                            $porcentagem_concluida = $total_aulas > 0 ? round(($total_aulas_concluidas / $total_aulas) * 100) : 0;
                            ?>
                            <div class="stat-value"><?php echo $porcentagem_concluida; ?>%</div>
                            <div class="stat-label">Progresso Geral</div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon stat-icon-yellow">
                                <i class="fas fa-clock"></i>
                            </div>
                            <?php
                            $tempo_total = $estatisticas['tempo_total'] ?? 0;
                            $horas = floor($tempo_total / 3600);
                            $minutos = floor(($tempo_total % 3600) / 60);
                            ?>
                            <div class="stat-value"><?php echo $horas; ?>h <?php echo $minutos; ?>m</div>
                            <div class="stat-label">Tempo Total de Estudo</div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon stat-icon-purple">
                                <i class="fas fa-sign-in-alt"></i>
                            </div>
                            <div class="stat-value"><?php echo $estatisticas['total_acessos'] ?? 0; ?></div>
                            <div class="stat-label">Total de Acessos</div>
                            <div class="text-xs text-gray-500 mt-1">
                                <?php if (!empty($estatisticas['ultimo_acesso'])): ?>
                                Último acesso: <?php echo date('d/m/Y H:i', strtotime($estatisticas['ultimo_acesso'])); ?>
                                <?php else: ?>
                                Nenhum acesso registrado
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Progresso nos Cursos -->
                        <div class="lg:col-span-2">
                            <h2 class="text-xl font-semibold text-gray-800 mb-4">Progresso nos Cursos</h2>

                            <?php if (empty($matriculas)): ?>
                            <div class="bg-white rounded-xl shadow-sm p-6 text-center">
                                <p class="text-gray-500">Este aluno não está matriculado em nenhum curso.</p>
                            </div>
                            <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($matriculas as $matricula): ?>
                                <div class="curso-progress-card">
                                    <div class="curso-progress-header">
                                        <h3 class="curso-progress-title"><?php echo htmlspecialchars($matricula['curso_titulo']); ?></h3>
                                        <span class="curso-progress-status curso-progress-status-<?php echo $matricula['status']; ?>">
                                            <?php
                                            if ($matricula['status'] === 'ativo') echo 'Ativo';
                                            elseif ($matricula['status'] === 'inativo') echo 'Inativo';
                                            elseif ($matricula['status'] === 'pendente') echo 'Pendente';
                                            elseif ($matricula['status'] === 'concluido') echo 'Concluído';
                                            ?>
                                        </span>
                                    </div>

                                    <?php
                                    $progresso = 0;
                                    if ($matricula['total_aulas'] > 0) {
                                        $progresso = ($matricula['aulas_concluidas'] / $matricula['total_aulas']) * 100;
                                    }
                                    ?>
                                    <div class="curso-progress-bar">
                                        <div class="curso-progress-value" style="width: <?php echo $progresso; ?>%;"></div>
                                    </div>
                                    <div class="curso-progress-text">
                                        <span><?php echo $matricula['aulas_concluidas']; ?> de <?php echo $matricula['total_aulas']; ?> aulas concluídas</span>
                                        <span><?php echo round($progresso); ?>%</span>
                                    </div>

                                    <div class="curso-progress-meta">
                                        <div>
                                            <?php if (!empty($matricula['data_matricula'])): ?>
                                            <span>Matriculado em: <?php echo date('d/m/Y', strtotime($matricula['data_matricula'])); ?></span>
                                            <?php endif; ?>

                                            <?php if (!empty($matricula['data_conclusao'])): ?>
                                            <span class="ml-4">Concluído em: <?php echo date('d/m/Y', strtotime($matricula['data_conclusao'])); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <a href="curso_aluno.php?matricula_id=<?php echo $matricula['id']; ?>" class="inline-flex items-center text-indigo-600 hover:text-indigo-800">
                                                <i class="fas fa-eye mr-1"></i> Ver Curso
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Histórico de Atividades -->
                        <div>
                            <h2 class="text-xl font-semibold text-gray-800 mb-4">Histórico de Atividades</h2>

                            <div class="activity-card">
                                <div class="activity-header">
                                    <h3 class="activity-title">Atividades Recentes</h3>
                                </div>

                                <?php if (empty($atividades)): ?>
                                <div class="p-6 text-center">
                                    <p class="text-gray-500">Nenhuma atividade registrada para este aluno.</p>
                                </div>
                                <?php else: ?>
                                <div class="activity-list">
                                    <?php foreach ($atividades as $atividade): ?>
                                    <div class="activity-item">
                                        <div class="activity-icon activity-icon-<?php echo $atividade['aula_tipo']; ?>">
                                            <?php if ($atividade['aula_tipo'] === 'video'): ?>
                                            <i class="fas fa-video"></i>
                                            <?php elseif ($atividade['aula_tipo'] === 'texto'): ?>
                                            <i class="fas fa-file-alt"></i>
                                            <?php elseif ($atividade['aula_tipo'] === 'arquivo'): ?>
                                            <i class="fas fa-file"></i>
                                            <?php elseif ($atividade['aula_tipo'] === 'quiz'): ?>
                                            <i class="fas fa-question-circle"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="activity-content">
                                            <div class="activity-content-title"><?php echo htmlspecialchars($atividade['aula_titulo']); ?></div>
                                            <div class="activity-content-subtitle">
                                                <?php echo htmlspecialchars($atividade['modulo_titulo']); ?> -
                                                <a href="curso_visualizar.php?id=<?php echo $atividade['curso_id']; ?>" class="text-indigo-600 hover:text-indigo-800">
                                                    <?php echo htmlspecialchars($atividade['curso_titulo']); ?>
                                                </a>
                                            </div>
                                            <div class="activity-content-meta">
                                                <i class="fas fa-clock"></i>
                                                <?php echo date('d/m/Y H:i', strtotime($atividade['updated_at'])); ?>

                                                <?php if (!empty($atividade['tempo_gasto'])): ?>
                                                <span class="ml-3">
                                                    <i class="fas fa-hourglass-half"></i>
                                                    <?php
                                                    $minutos = floor($atividade['tempo_gasto'] / 60);
                                                    $segundos = $atividade['tempo_gasto'] % 60;
                                                    echo $minutos . 'm ' . $segundos . 's';
                                                    ?>
                                                </span>
                                                <?php endif; ?>

                                                <?php if (!empty($atividade['pontuacao']) && $atividade['aula_tipo'] === 'quiz'): ?>
                                                <span class="ml-3">
                                                    <i class="fas fa-star"></i>
                                                    Pontuação: <?php echo $atividade['pontuacao']; ?>%
                                                </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <span class="activity-status activity-status-<?php echo $atividade['concluido'] ? 'concluido' : 'pendente'; ?>">
                                            <?php echo $atividade['concluido'] ? 'Concluído' : 'Pendente'; ?>
                                        </span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
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
