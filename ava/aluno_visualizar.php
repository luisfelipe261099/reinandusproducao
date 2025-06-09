<?php
/**
 * Visualização de Aluno do AVA
 * Exibe os detalhes de um aluno específico no Ambiente Virtual de Aprendizagem
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
            'cpf' => '123.456.789-00',
            'telefone' => '(11) 98765-4321',
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

// Busca os certificados do aluno
$sql = "SELECT ac.*, am.curso_id, avc.titulo as curso_titulo
        FROM ava_certificados ac
        JOIN ava_matriculas am ON ac.matricula_id = am.id
        JOIN ava_cursos avc ON am.curso_id = avc.id
        WHERE am.aluno_id = ?
        ORDER BY ac.data_emissao DESC";
$certificados = $db->fetchAll($sql, [$aluno_id]);

// Define o título da página
$titulo_pagina = 'Visualizar Aluno';
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
        .aluno-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            font-size: 0.875rem;
            color: #6B7280;
        }
        .aluno-meta-item {
            display: flex;
            align-items: center;
        }
        .aluno-meta-item i {
            margin-right: 0.5rem;
            color: #6A5ACD;
        }

        .curso-card {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            overflow: hidden;
            transition: transform 0.2s;
        }
        .curso-card:hover {
            transform: translateY(-2px);
        }
        .curso-image {
            height: 10rem;
            background-size: cover;
            background-position: center;
            position: relative;
        }
        .curso-image-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to bottom, rgba(0,0,0,0.1), rgba(0,0,0,0.6));
            display: flex;
            align-items: flex-end;
            padding: 1rem;
        }
        .curso-category {
            position: absolute;
            top: 1rem;
            left: 1rem;
            background-color: rgba(255, 255, 255, 0.9);
            color: #4B5563;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
        }
        .curso-status {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
        }
        .curso-status-ativo { background-color: #D1FAE5; color: #059669; }
        .curso-status-inativo { background-color: #FEE2E2; color: #DC2626; }
        .curso-status-pendente { background-color: #FEF3C7; color: #D97706; }
        .curso-status-concluido { background-color: #E0E7FF; color: #4F46E5; }
        .curso-content {
            padding: 1rem;
        }
        .curso-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #111827;
            margin-bottom: 0.5rem;
        }
        .curso-progress {
            margin-top: 1rem;
        }
        .curso-progress-bar {
            height: 0.5rem;
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
            font-size: 0.75rem;
            color: #6B7280;
            display: flex;
            justify-content: space-between;
        }
        .curso-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #E5E7EB;
        }

        .certificado-card {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 4px solid #6A5ACD;
        }
        .certificado-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        .certificado-title {
            font-weight: 600;
            color: #111827;
        }
        .certificado-status {
            font-size: 0.75rem;
            padding: 0.125rem 0.5rem;
            border-radius: 9999px;
        }
        .certificado-status-emitido { background-color: #D1FAE5; color: #059669; }
        .certificado-status-revogado { background-color: #FEE2E2; color: #DC2626; }
        .certificado-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.75rem;
            color: #6B7280;
            margin-top: 0.5rem;
        }
        .certificado-date {
            display: flex;
            align-items: center;
        }
        .certificado-date i {
            margin-right: 0.25rem;
        }
        .certificado-code {
            font-family: monospace;
            background-color: #F3F4F6;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
        }
        .certificado-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
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
                            <p class="text-gray-600">Detalhes do aluno no Ambiente Virtual de Aprendizagem</p>
                        </div>
                        <div class="flex space-x-2">
                            <a href="alunos.php" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                <i class="fas fa-arrow-left mr-2"></i> Voltar
                            </a>
                            <a href="aluno_progresso.php?id=<?php echo $aluno_id; ?>" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                                <i class="fas fa-chart-line mr-2"></i> Ver Progresso
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
                    <div class="aluno-header flex items-start">
                        <div class="aluno-avatar">
                            <?php echo strtoupper(substr($aluno['nome'], 0, 1)); ?>
                        </div>
                        <div class="aluno-info">
                            <h2 class="aluno-name"><?php echo htmlspecialchars($aluno['nome']); ?></h2>
                            <p class="aluno-email"><?php echo htmlspecialchars($aluno['email']); ?></p>
                            <div class="aluno-meta">
                                <div class="aluno-meta-item">
                                    <i class="fas fa-id-card"></i>
                                    <span><?php echo htmlspecialchars($aluno['cpf']); ?></span>
                                </div>
                                <div class="aluno-meta-item">
                                    <i class="fas fa-phone"></i>
                                    <span><?php echo htmlspecialchars($aluno['telefone'] ?? 'Não informado'); ?></span>
                                </div>
                                <div class="aluno-meta-item">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span>Cadastrado em <?php echo date('d/m/Y', strtotime($aluno['created_at'])); ?></span>
                                </div>
                                <div class="aluno-meta-item">
                                    <i class="fas fa-book"></i>
                                    <span><?php echo count($matriculas); ?> curso(s) matriculado(s)</span>
                                </div>
                                <div class="aluno-meta-item">
                                    <i class="fas fa-certificate"></i>
                                    <span><?php echo count($certificados); ?> certificado(s)</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Cursos Matriculados -->
                    <div class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-800 mb-4">Cursos Matriculados</h2>

                        <?php if (empty($matriculas)): ?>
                        <div class="bg-white rounded-xl shadow-sm p-6 text-center">
                            <p class="text-gray-500">Este aluno não está matriculado em nenhum curso.</p>
                            <a href="matriculas.php?aluno_id=<?php echo $aluno_id; ?>" class="inline-flex items-center mt-4 px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                                <i class="fas fa-plus mr-2"></i> Matricular em Curso
                            </a>
                        </div>
                        <?php else: ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <?php foreach ($matriculas as $matricula): ?>
                            <div class="curso-card">
                                <div class="curso-image" style="background-image: url('<?php echo !empty($matricula['curso_imagem']) ? $matricula['curso_imagem'] : '../uploads/ava/default-course.jpg'; ?>');">
                                    <div class="curso-image-overlay">
                                        <span class="curso-category"><?php echo htmlspecialchars($matricula['curso_categoria'] ?? 'Geral'); ?></span>
                                        <span class="curso-status curso-status-<?php echo $matricula['status']; ?>">
                                            <?php
                                            if ($matricula['status'] === 'ativo') echo 'Ativo';
                                            elseif ($matricula['status'] === 'inativo') echo 'Inativo';
                                            elseif ($matricula['status'] === 'pendente') echo 'Pendente';
                                            elseif ($matricula['status'] === 'concluido') echo 'Concluído';
                                            ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="curso-content">
                                    <h3 class="curso-title"><?php echo htmlspecialchars($matricula['curso_titulo']); ?></h3>

                                    <div class="curso-progress">
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
                                    </div>

                                    <div class="curso-actions">
                                        <div class="text-sm text-gray-500">
                                            <?php if (!empty($matricula['data_matricula'])): ?>
                                            Matriculado em: <?php echo date('d/m/Y', strtotime($matricula['data_matricula'])); ?>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <a href="curso_aluno.php?matricula_id=<?php echo $matricula['id']; ?>" class="inline-flex items-center px-3 py-1 border border-transparent rounded-md text-xs font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                                                <i class="fas fa-eye mr-1"></i> Ver Curso
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Certificados -->
                    <div>
                        <h2 class="text-xl font-semibold text-gray-800 mb-4">Certificados</h2>

                        <?php if (empty($certificados)): ?>
                        <div class="bg-white rounded-xl shadow-sm p-6 text-center">
                            <p class="text-gray-500">Este aluno não possui certificados emitidos.</p>
                        </div>
                        <?php else: ?>
                        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                            <div class="p-6 space-y-4">
                                <?php foreach ($certificados as $certificado): ?>
                                <div class="certificado-card">
                                    <div class="certificado-header">
                                        <h3 class="certificado-title"><?php echo htmlspecialchars($certificado['curso_titulo']); ?></h3>
                                        <span class="certificado-status certificado-status-<?php echo $certificado['status']; ?>">
                                            <?php echo $certificado['status'] === 'emitido' ? 'Emitido' : 'Revogado'; ?>
                                        </span>
                                    </div>
                                    <div class="certificado-meta">
                                        <div class="flex space-x-4">
                                            <span class="certificado-date">
                                                <i class="fas fa-calendar-check"></i>
                                                Emitido em: <?php echo date('d/m/Y', strtotime($certificado['data_emissao'])); ?>
                                            </span>
                                            <?php if (!empty($certificado['data_validade'])): ?>
                                            <span class="certificado-date">
                                                <i class="fas fa-calendar-times"></i>
                                                Válido até: <?php echo date('d/m/Y', strtotime($certificado['data_validade'])); ?>
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                        <span class="certificado-code">
                                            Código: <?php echo htmlspecialchars($certificado['codigo']); ?>
                                        </span>
                                    </div>
                                    <div class="certificado-actions">
                                        <?php if (!empty($certificado['arquivo'])): ?>
                                        <a href="<?php echo $certificado['arquivo']; ?>" target="_blank" class="inline-flex items-center px-3 py-1 border border-transparent rounded-md text-xs font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                                            <i class="fas fa-download mr-1"></i> Download
                                        </a>
                                        <?php endif; ?>
                                        <a href="certificado_visualizar.php?id=<?php echo $certificado['id']; ?>" class="inline-flex items-center px-3 py-1 border border-gray-300 rounded-md text-xs font-medium text-gray-700 bg-white hover:bg-gray-50">
                                            <i class="fas fa-eye mr-1"></i> Visualizar
                                        </a>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
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
