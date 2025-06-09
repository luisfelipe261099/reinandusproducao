<?php
/**
 * Página de Dashboard do Aluno no AVA
 * Mostra os cursos em andamento, progresso e atividades recentes
 */

// Inicializa o sistema
require_once 'includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Instancia o banco de dados
$db = Database::getInstance();

// Busca os cursos do aluno (simulado para demonstração)
$usuario_id = getUsuarioId();

// Em um sistema real, você buscaria os cursos matriculados pelo aluno
// Aqui, vamos buscar todos os cursos para simular
$sql = "SELECT * FROM ava_cursos WHERE status IN ('ativo', 'publicado') ORDER BY titulo LIMIT 6";
$cursos = $db->fetchAll($sql);

// Adiciona informações de progresso simuladas
foreach ($cursos as $key => $curso) {
    // Progresso aleatório para demonstração
    $cursos[$key]['progresso'] = rand(0, 100);

    // Data de início simulada
    $cursos[$key]['data_inicio'] = date('Y-m-d', strtotime('-' . rand(1, 30) . ' days'));

    // Data de último acesso simulada
    $cursos[$key]['ultimo_acesso'] = date('Y-m-d H:i:s', strtotime('-' . rand(0, 7) . ' days'));

    // Status do curso para o aluno
    if ($cursos[$key]['progresso'] == 100) {
        $cursos[$key]['status_aluno'] = 'concluido';
    } elseif ($cursos[$key]['progresso'] > 0) {
        $cursos[$key]['status_aluno'] = 'em_andamento';
    } else {
        $cursos[$key]['status_aluno'] = 'nao_iniciado';
    }
}

// Busca as atividades recentes (simulado para demonstração)
$atividades = [];

// Tipos de atividades para simulação
$tipos_atividades = ['aula_assistida', 'quiz_respondido', 'material_baixado', 'curso_iniciado', 'curso_concluido'];

// Gera atividades aleatórias
for ($i = 0; $i < 10; $i++) {
    $tipo = $tipos_atividades[array_rand($tipos_atividades)];
    $curso = $cursos[array_rand($cursos)];

    $atividade = [
        'tipo' => $tipo,
        'curso_id' => $curso['id'],
        'curso_nome' => $curso['titulo'],
        'data' => date('Y-m-d H:i:s', strtotime('-' . rand(0, 14) . ' days -' . rand(0, 23) . ' hours')),
    ];

    // Adiciona detalhes específicos por tipo
    switch ($tipo) {
        case 'aula_assistida':
            $atividade['aula_nome'] = 'Aula ' . rand(1, 10) . ': ' . ['Introdução', 'Conceitos Básicos', 'Prática', 'Avançado', 'Revisão'][array_rand(['Introdução', 'Conceitos Básicos', 'Prática', 'Avançado', 'Revisão'])];
            break;
        case 'quiz_respondido':
            $atividade['quiz_nome'] = 'Quiz ' . rand(1, 5);
            $atividade['pontuacao'] = rand(60, 100) . '%';
            break;
        case 'material_baixado':
            $atividade['material_nome'] = ['Apostila', 'Slides', 'Exercícios', 'Leitura Complementar', 'Guia Rápido'][array_rand(['Apostila', 'Slides', 'Exercícios', 'Leitura Complementar', 'Guia Rápido'])];
            break;
    }

    $atividades[] = $atividade;
}

// Ordena as atividades por data (mais recentes primeiro)
usort($atividades, function($a, $b) {
    return strtotime($b['data']) - strtotime($a['data']);
});

// Define o título da página
$titulo_pagina = 'Meus Cursos';
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
        .dashboard-header {
            background-color: #1E40AF;
            color: white;
        }

        .progress-bar {
            height: 0.5rem;
            background-color: #e5e7eb;
            border-radius: 9999px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            border-radius: 9999px;
        }

        .progress-fill-low {
            background-color: #ef4444;
        }

        .progress-fill-medium {
            background-color: #f59e0b;
        }

        .progress-fill-high {
            background-color: #10b981;
        }

        .curso-card {
            transition: all 0.3s ease;
        }

        .curso-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .curso-image {
            height: 160px;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-color: #E5E7EB;
        }

        .curso-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 0.25rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 9999px;
        }

        .badge-concluido {
            background-color: #D1FAE5;
            color: #059669;
        }

        .badge-em-andamento {
            background-color: #FEF3C7;
            color: #D97706;
        }

        .badge-nao-iniciado {
            background-color: #E5E7EB;
            color: #4B5563;
        }

        .atividade-item {
            position: relative;
            padding-left: 2rem;
            padding-bottom: 1.5rem;
        }

        .atividade-item:before {
            content: '';
            position: absolute;
            left: 0.4rem;
            top: 0.4rem;
            width: 0.75rem;
            height: 0.75rem;
            border-radius: 50%;
            background-color: #3B82F6;
            z-index: 1;
        }

        .atividade-item:after {
            content: '';
            position: absolute;
            left: 0.75rem;
            top: 1.25rem;
            width: 1px;
            height: calc(100% - 1.25rem);
            background-color: #E5E7EB;
        }

        .atividade-item:last-child:after {
            display: none;
        }

        .atividade-icon {
            width: 2.5rem;
            height: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.375rem;
            margin-right: 0.75rem;
        }

        .icon-aula { background-color: #EDE9FE; color: #7C3AED; }
        .icon-quiz { background-color: #FEF3C7; color: #D97706; }
        .icon-material { background-color: #DBEAFE; color: #2563EB; }
        .icon-curso-iniciado { background-color: #E0F2FE; color: #0369A1; }
        .icon-curso-concluido { background-color: #D1FAE5; color: #059669; }
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
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100">
                <!-- Cabeçalho do Dashboard -->
                <div class="dashboard-header py-6 px-6">
                    <div class="container mx-auto">
                        <div class="flex justify-between items-center">
                            <div>
                                <h1 class="text-2xl font-bold">Meus Cursos</h1>
                                <p class="text-blue-200 mt-1">Bem-vindo(a) ao seu ambiente de aprendizagem</p>
                            </div>
                            <div>
                                <a href="ava_cursos.php" class="bg-white text-blue-600 hover:bg-blue-50 font-medium py-2 px-4 rounded-md inline-flex items-center">
                                    <i class="fas fa-th-large mr-2"></i> Todos os Cursos
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="container mx-auto p-6">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Coluna Principal -->
                        <div class="lg:col-span-2">
                            <!-- Cursos em Andamento -->
                            <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
                                <div class="px-6 py-4 border-b border-gray-200">
                                    <h2 class="text-lg font-semibold text-gray-800">Cursos em Andamento</h2>
                                </div>
                                <div class="p-6">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <?php foreach ($cursos as $curso): ?>
                                            <?php if ($curso['status_aluno'] === 'em_andamento'): ?>
                                            <div class="curso-card bg-white border border-gray-200 rounded-lg overflow-hidden shadow-sm relative">
                                                <div class="curso-image" style="background-image: url('https://via.placeholder.com/400x200/3B82F6/FFFFFF?text=<?php echo urlencode($curso['titulo']); ?>')"></div>
                                                <div class="curso-badge badge-em-andamento">Em andamento</div>
                                                <div class="p-4">
                                                    <h3 class="font-medium text-gray-900 mb-1"><?php echo htmlspecialchars($curso['titulo']); ?></h3>
                                                    <div class="text-sm text-gray-500 mb-3">
                                                        <div>Último acesso: <?php echo date('d/m/Y', strtotime($curso['ultimo_acesso'])); ?></div>
                                                    </div>
                                                    <div class="mb-2">
                                                        <div class="flex justify-between text-xs mb-1">
                                                            <span>Progresso</span>
                                                            <span class="font-medium"><?php echo $curso['progresso']; ?>%</span>
                                                        </div>
                                                        <div class="progress-bar">
                                                            <?php
                                                            $progress_class = 'progress-fill-low';
                                                            if ($curso['progresso'] >= 70) {
                                                                $progress_class = 'progress-fill-high';
                                                            } elseif ($curso['progresso'] >= 30) {
                                                                $progress_class = 'progress-fill-medium';
                                                            }
                                                            ?>
                                                            <div class="progress-fill <?php echo $progress_class; ?>" style="width: <?php echo $curso['progresso']; ?>%"></div>
                                                        </div>
                                                    </div>
                                                    <div class="mt-4">
                                                        <a href="ava_visualizar_curso.php?id=<?php echo $curso['id']; ?>" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                                                            <i class="fas fa-play-circle mr-2"></i> Continuar Curso
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>

                                        <?php if (!array_filter($cursos, function($c) { return $c['status_aluno'] === 'em_andamento'; })): ?>
                                        <div class="col-span-2 text-center py-8 text-gray-500">
                                            <div class="text-4xl mb-4"><i class="fas fa-book-open"></i></div>
                                            <p class="mb-4">Você não tem cursos em andamento.</p>
                                            <a href="ava_cursos.php" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                                <i class="fas fa-search mr-2"></i> Explorar Cursos
                                            </a>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Cursos Disponíveis -->
                            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                                <div class="px-6 py-4 border-b border-gray-200">
                                    <h2 class="text-lg font-semibold text-gray-800">Meus Cursos</h2>
                                </div>
                                <div class="p-6">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <?php foreach ($cursos as $curso): ?>
                                            <?php if ($curso['status_aluno'] !== 'em_andamento'): ?>
                                            <div class="curso-card bg-white border border-gray-200 rounded-lg overflow-hidden shadow-sm relative">
                                                <div class="curso-image" style="background-image: url('https://via.placeholder.com/400x200/3B82F6/FFFFFF?text=<?php echo urlencode($curso['titulo']); ?>')"></div>
                                                <?php if ($curso['status_aluno'] === 'concluido'): ?>
                                                <div class="curso-badge badge-concluido">Concluído</div>
                                                <?php elseif ($curso['status_aluno'] === 'nao_iniciado'): ?>
                                                <div class="curso-badge badge-nao-iniciado">Não iniciado</div>
                                                <?php endif; ?>
                                                <div class="p-4">
                                                    <h3 class="font-medium text-gray-900 mb-1"><?php echo htmlspecialchars($curso['titulo']); ?></h3>
                                                    <div class="text-sm text-gray-500 mb-3">
                                                        <?php if ($curso['status_aluno'] === 'concluido'): ?>
                                                        <div>Concluído em: <?php echo date('d/m/Y', strtotime($curso['ultimo_acesso'])); ?></div>
                                                        <?php else: ?>
                                                        <div>Disponível desde: <?php echo date('d/m/Y', strtotime($curso['data_inicio'])); ?></div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php if ($curso['status_aluno'] === 'concluido'): ?>
                                                    <div class="mb-2">
                                                        <div class="flex justify-between text-xs mb-1">
                                                            <span>Progresso</span>
                                                            <span class="font-medium">100%</span>
                                                        </div>
                                                        <div class="progress-bar">
                                                            <div class="progress-fill progress-fill-high" style="width: 100%"></div>
                                                        </div>
                                                    </div>
                                                    <?php endif; ?>
                                                    <div class="mt-4">
                                                        <?php if ($curso['status_aluno'] === 'concluido'): ?>
                                                        <a href="ava_visualizar_curso.php?id=<?php echo $curso['id']; ?>" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700">
                                                            <i class="fas fa-redo-alt mr-2"></i> Revisar Curso
                                                        </a>
                                                        <?php else: ?>
                                                        <a href="ava_visualizar_curso.php?id=<?php echo $curso['id']; ?>" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                                                            <i class="fas fa-play-circle mr-2"></i> Iniciar Curso
                                                        </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Coluna Lateral -->
                        <div class="lg:col-span-1">
                            <!-- Atividades Recentes -->
                            <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
                                <div class="px-6 py-4 border-b border-gray-200">
                                    <h2 class="text-lg font-semibold text-gray-800">Atividades Recentes</h2>
                                </div>
                                <div class="p-6">
                                    <?php if (empty($atividades)): ?>
                                    <div class="text-center text-gray-500 py-4">
                                        <p>Nenhuma atividade recente.</p>
                                    </div>
                                    <?php else: ?>
                                    <div class="space-y-4">
                                        <?php foreach ($atividades as $atividade): ?>
                                        <div class="atividade-item">
                                            <div class="flex">
                                                <?php
                                                $icon_class = '';
                                                $icon = '';

                                                switch ($atividade['tipo']) {
                                                    case 'aula_assistida':
                                                        $icon_class = 'icon-aula';
                                                        $icon = 'fas fa-play';
                                                        break;
                                                    case 'quiz_respondido':
                                                        $icon_class = 'icon-quiz';
                                                        $icon = 'fas fa-question';
                                                        break;
                                                    case 'material_baixado':
                                                        $icon_class = 'icon-material';
                                                        $icon = 'fas fa-file-download';
                                                        break;
                                                    case 'curso_iniciado':
                                                        $icon_class = 'icon-curso-iniciado';
                                                        $icon = 'fas fa-book-open';
                                                        break;
                                                    case 'curso_concluido':
                                                        $icon_class = 'icon-curso-concluido';
                                                        $icon = 'fas fa-check-circle';
                                                        break;
                                                }
                                                ?>
                                                <div class="atividade-icon <?php echo $icon_class; ?>">
                                                    <i class="<?php echo $icon; ?>"></i>
                                                </div>
                                                <div>
                                                    <div class="font-medium">
                                                        <?php
                                                        switch ($atividade['tipo']) {
                                                            case 'aula_assistida':
                                                                echo 'Assistiu a aula "' . htmlspecialchars($atividade['aula_nome']) . '"';
                                                                break;
                                                            case 'quiz_respondido':
                                                                echo 'Respondeu o ' . htmlspecialchars($atividade['quiz_nome']) . ' com ' . $atividade['pontuacao'] . ' de acertos';
                                                                break;
                                                            case 'material_baixado':
                                                                echo 'Baixou o material "' . htmlspecialchars($atividade['material_nome']) . '"';
                                                                break;
                                                            case 'curso_iniciado':
                                                                echo 'Iniciou o curso';
                                                                break;
                                                            case 'curso_concluido':
                                                                echo 'Concluiu o curso';
                                                                break;
                                                        }
                                                        ?>
                                                    </div>
                                                    <div class="text-sm text-gray-500">
                                                        <a href="ava_visualizar_curso.php?id=<?php echo $atividade['curso_id']; ?>" class="text-blue-600 hover:text-blue-800">
                                                            <?php echo htmlspecialchars($atividade['curso_nome']); ?>
                                                        </a>
                                                    </div>
                                                    <div class="text-xs text-gray-400 mt-1">
                                                        <?php echo date('d/m/Y \à\s H:i', strtotime($atividade['data'])); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Certificados -->
                            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                                <div class="px-6 py-4 border-b border-gray-200">
                                    <h2 class="text-lg font-semibold text-gray-800">Meus Certificados</h2>
                                </div>
                                <div class="p-6">
                                    <?php
                                    $cursos_concluidos = array_filter($cursos, function($c) {
                                        return $c['status_aluno'] === 'concluido';
                                    });
                                    ?>

                                    <?php if (empty($cursos_concluidos)): ?>
                                    <div class="text-center text-gray-500 py-4">
                                        <p>Você ainda não possui certificados.</p>
                                        <p class="mt-2 text-sm">Conclua um curso para obter seu certificado.</p>
                                    </div>
                                    <?php else: ?>
                                    <div class="space-y-4">
                                        <?php foreach ($cursos_concluidos as $curso): ?>
                                        <div class="flex items-center p-3 border border-gray-200 rounded-lg">
                                            <div class="text-green-500 mr-3">
                                                <i class="fas fa-certificate text-2xl"></i>
                                            </div>
                                            <div class="flex-1">
                                                <div class="font-medium"><?php echo htmlspecialchars($curso['titulo']); ?></div>
                                                <div class="text-xs text-gray-500">Concluído em <?php echo date('d/m/Y', strtotime($curso['ultimo_acesso'])); ?></div>
                                            </div>
                                            <a href="#" class="text-blue-600 hover:text-blue-800">
                                                <i class="fas fa-download"></i>
                                            </a>
                                        </div>
                                        <?php endforeach; ?>
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
    </script>
</body>
</html>
