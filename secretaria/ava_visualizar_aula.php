<?php
/**
 * Página de Visualização de Aula do AVA
 * Permite visualizar uma aula específica como se fosse um aluno
 */

// Inicializa o sistema
require_once 'includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica se o ID da aula foi informado
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setMensagem('erro', 'ID da aula não informado.');
    redirect('ava_cursos.php');
    exit;
}

$aula_id = (int)$_GET['id'];

// Instancia o banco de dados
$db = Database::getInstance();

// Busca a aula
$sql = "SELECT a.*, m.titulo as modulo_titulo, m.curso_id, c.titulo as curso_titulo
        FROM ava_aulas a
        LEFT JOIN ava_modulos m ON a.modulo_id = m.id
        LEFT JOIN ava_cursos c ON m.curso_id = c.id
        WHERE a.id = ?";
$aula = $db->fetchOne($sql, [$aula_id]);

if (!$aula) {
    setMensagem('erro', 'Aula não encontrada.');
    redirect('ava_cursos.php');
    exit;
}

// Busca os materiais complementares da aula
$sql = "SELECT * FROM ava_materiais WHERE aula_id = ? ORDER BY ordem, id";
$materiais = $db->fetchAll($sql, [$aula_id]);

// Busca as questões do quiz se for uma aula do tipo quiz
$questoes = [];
if ($aula['tipo'] === 'quiz') {
    $sql = "SELECT * FROM ava_questoes WHERE aula_id = ? ORDER BY ordem, id";
    $questoes = $db->fetchAll($sql, [$aula_id]);

    // Para cada questão, busca as alternativas ou cria alternativas simuladas
    foreach ($questoes as $key => $questao) {
        // Verifica se a tabela ava_alternativas existe
        try {
            $sql = "SELECT * FROM ava_alternativas WHERE questao_id = ? ORDER BY ordem, id";
            $alternativas = $db->fetchAll($sql, [$questao['id']]);

            // Se não houver alternativas, cria algumas simuladas
            if (empty($alternativas)) {
                $alternativas = criarAlternativasSimuladas($questao);
            }
        } catch (Exception $e) {
            // Se a tabela não existir, cria alternativas simuladas
            $alternativas = criarAlternativasSimuladas($questao);
        }

        $questoes[$key]['alternativas'] = $alternativas;
    }
}

// Função para criar alternativas simuladas para demonstração
function criarAlternativasSimuladas($questao) {
    $alternativas = [];

    // Verifica se há opções definidas no campo 'opcoes' da questão
    if (!empty($questao['opcoes'])) {
        // Se houver, usa as opções definidas
        $opcoes = json_decode($questao['opcoes'], true);
        if (is_array($opcoes)) {
            foreach ($opcoes as $index => $opcao) {
                $alternativas[] = [
                    'id' => 'sim_' . $questao['id'] . '_' . ($index + 1),
                    'questao_id' => $questao['id'],
                    'texto' => $opcao,
                    'correta' => ($questao['resposta_correta'] == $index + 1) ? 1 : 0,
                    'ordem' => $index + 1
                ];
            }
            return $alternativas;
        }
    }

    // Se não houver opções definidas, cria alternativas genéricas
    $textos = [
        'Alternativa A',
        'Alternativa B',
        'Alternativa C',
        'Alternativa D'
    ];

    foreach ($textos as $index => $texto) {
        $alternativas[] = [
            'id' => 'sim_' . $questao['id'] . '_' . ($index + 1),
            'questao_id' => $questao['id'],
            'texto' => $texto,
            'correta' => ($index == 0) ? 1 : 0,
            'ordem' => $index + 1
        ];
    }

    return $alternativas;
}

// Busca as aulas do mesmo módulo para navegação
$sql = "SELECT id, titulo, tipo FROM ava_aulas WHERE modulo_id = ? ORDER BY ordem, id";
$aulas_modulo = $db->fetchAll($sql, [$aula['modulo_id']]);

// Encontra a aula anterior e a próxima
$aula_anterior = null;
$proxima_aula = null;
$encontrou_atual = false;

foreach ($aulas_modulo as $a) {
    if ($encontrou_atual) {
        $proxima_aula = $a;
        break;
    }

    if ($a['id'] == $aula_id) {
        $encontrou_atual = true;
    } else {
        $aula_anterior = $a;
    }
}

// Define o título da página
$titulo_aula = isset($aula['titulo']) ? $aula['titulo'] : 'Aula';
$titulo_curso = isset($aula['curso_titulo']) ? $aula['curso_titulo'] : 'Curso';
$titulo_pagina = $titulo_aula . ' - ' . $titulo_curso;
$titulo_pagina_completo = 'Faciência ERP - ' . $titulo_pagina;

// Registra a visualização da aula (em um sistema real, isso seria usado para tracking)
$usuario_id = getUsuarioId();
$data_atual = date('Y-m-d H:i:s');

// Em um sistema real, você registraria a visualização em uma tabela como ava_visualizacoes
// $sql = "INSERT INTO ava_visualizacoes (aula_id, usuario_id, data_visualizacao) VALUES (?, ?, ?)";
// $db->execute($sql, [$aula_id, $usuario_id, $data_atual]);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo_pagina_completo; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdn.plyr.io/3.7.2/plyr.css" />
    <script src="https://cdn.plyr.io/3.7.2/plyr.polyfilled.js"></script>
    <style>
        .aula-header {
            background-color: #1E40AF;
            color: white;
        }

        .aula-content {
            min-height: 400px;
        }

        .aula-navigation {
            display: flex;
            justify-content: space-between;
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid #e5e7eb;
        }

        .nav-button {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s;
        }

        .nav-button-prev {
            background-color: #e5e7eb;
            color: #4b5563;
        }

        .nav-button-prev:hover {
            background-color: #d1d5db;
        }

        .nav-button-next {
            background-color: #2563eb;
            color: white;
        }

        .nav-button-next:hover {
            background-color: #1d4ed8;
        }

        .material-item {
            display: flex;
            align-items: center;
            padding: 0.75rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            margin-bottom: 0.5rem;
            transition: all 0.2s;
        }

        .material-item:hover {
            background-color: #f9fafb;
        }

        .material-icon {
            width: 2.5rem;
            height: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.375rem;
            margin-right: 0.75rem;
        }

        .material-pdf { background-color: #fee2e2; color: #dc2626; }
        .material-doc { background-color: #dbeafe; color: #2563eb; }
        .material-img { background-color: #e0f2fe; color: #0369a1; }
        .material-zip { background-color: #fef3c7; color: #d97706; }
        .material-link { background-color: #d1fae5; color: #059669; }

        .quiz-question {
            margin-bottom: 1.5rem;
            padding: 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
        }

        .quiz-options {
            margin-top: 1rem;
        }

        .quiz-option {
            display: flex;
            align-items: center;
            padding: 0.75rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            margin-bottom: 0.5rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .quiz-option:hover {
            background-color: #f9fafb;
        }

        .quiz-option input {
            margin-right: 0.75rem;
        }

        .video-container {
            position: relative;
            padding-bottom: 56.25%; /* 16:9 */
            height: 0;
            overflow: hidden;
            max-width: 100%;
            border-radius: 0.375rem;
            margin-bottom: 1.5rem;
        }

        .video-container iframe,
        .video-container video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border-radius: 0.375rem;
        }

        .aula-sidebar {
            border-left: 1px solid #e5e7eb;
        }

        .aula-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .aula-list-item {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #e5e7eb;
            transition: all 0.2s;
        }

        .aula-list-item:hover {
            background-color: #f9fafb;
        }

        .aula-list-item.active {
            background-color: #eff6ff;
            border-left: 3px solid #2563eb;
        }

        .aula-list-item-icon {
            width: 1.5rem;
            height: 1.5rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin-right: 0.5rem;
            font-size: 0.75rem;
        }

        .aula-video { background-color: #ede9fe; color: #7c3aed; }
        .aula-texto { background-color: #e0f2fe; color: #0369a1; }
        .aula-quiz { background-color: #fef3c7; color: #d97706; }
        .aula-arquivo { background-color: #dbeafe; color: #2563eb; }
        .aula-link { background-color: #d1fae5; color: #059669; }
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
                <!-- Cabeçalho da Aula -->
                <div class="aula-header py-6 px-6">
                    <div class="container mx-auto">
                        <div class="flex justify-between items-center">
                            <div>
                                <div class="text-sm mb-1 flex space-x-4">
                                    <a href="ava_visualizar_curso.php?id=<?php echo $aula['curso_id']; ?>" class="text-blue-200 hover:text-white">
                                        <i class="fas fa-arrow-left mr-1"></i> Voltar para <?php echo htmlspecialchars($titulo_curso); ?>
                                    </a>
                                    <a href="ava_dashboard_aluno.php" class="text-green-200 hover:text-white">
                                        <i class="fas fa-graduation-cap mr-1"></i> Meus Cursos
                                    </a>
                                </div>
                                <h1 class="text-2xl font-bold"><?php echo htmlspecialchars($titulo_aula); ?></h1>
                                <div class="text-blue-200 mt-1">
                                    <span class="mr-3">
                                        <i class="fas fa-book mr-1"></i> <?php echo htmlspecialchars($aula['modulo_titulo']); ?>
                                    </span>
                                    <?php if (!empty($aula['duracao_minutos'])): ?>
                                    <span>
                                        <i class="fas fa-clock mr-1"></i> <?php echo $aula['duracao_minutos']; ?> minutos
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div>
                                <div class="text-sm text-blue-200">
                                    <i class="fas fa-eye mr-1"></i> Visualizando como aluno
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="container mx-auto p-6">
                    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                        <!-- Conteúdo da Aula -->
                        <div class="lg:col-span-3">
                            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                                <div class="p-6">
                                    <div class="aula-content">
                                        <?php if ($aula['tipo'] === 'video'): ?>
                                            <!-- Conteúdo de Vídeo -->
                                            <div class="video-container">
                                                <?php
                                                $conteudo = isset($aula['conteudo']) ? $aula['conteudo'] : '';
                                                if (!empty($conteudo) && (strpos($conteudo, 'youtube.com') !== false || strpos($conteudo, 'youtu.be') !== false)):
                                                ?>
                                                    <?php
                                                    // Extrair o ID do vídeo do YouTube
                                                    $video_id = '';
                                                    if (preg_match('/(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $conteudo, $matches)) {
                                                        $video_id = $matches[1];
                                                    }
                                                    ?>
                                                    <iframe src="https://www.youtube.com/embed/<?php echo $video_id; ?>" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                                                <?php elseif (!empty($conteudo) && strpos($conteudo, 'vimeo.com') !== false): ?>
                                                    <?php
                                                    // Extrair o ID do vídeo do Vimeo
                                                    $video_id = '';
                                                    if (preg_match('/vimeo\.com\/([0-9]+)/', $conteudo, $matches)) {
                                                        $video_id = $matches[1];
                                                    }
                                                    ?>
                                                    <iframe src="https://player.vimeo.com/video/<?php echo $video_id; ?>" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>
                                                <?php elseif (!empty($conteudo)): ?>
                                                    <video controls crossorigin playsinline id="player">
                                                        <source src="<?php echo $conteudo; ?>" type="video/mp4" />
                                                    </video>
                                                    <script>
                                                        const player = new Plyr('#player', {
                                                            controls: ['play-large', 'play', 'progress', 'current-time', 'mute', 'volume', 'captions', 'settings', 'pip', 'airplay', 'fullscreen'],
                                                        });
                                                    </script>
                                                <?php else: ?>
                                                    <div class="text-center py-8 text-gray-500">
                                                        <p>Nenhum conteúdo de vídeo disponível.</p>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php elseif ($aula['tipo'] === 'texto'): ?>
                                            <!-- Conteúdo de Texto -->
                                            <div class="prose max-w-none">
                                                <?php echo isset($aula['conteudo']) ? $aula['conteudo'] : 'Nenhum conteúdo disponível.'; ?>
                                            </div>
                                        <?php elseif ($aula['tipo'] === 'quiz'): ?>
                                            <!-- Conteúdo de Quiz -->
                                            <div class="quiz-container">
                                                <form id="quiz-form">
                                                    <?php foreach ($questoes as $index => $questao): ?>
                                                    <div class="quiz-question">
                                                        <div class="font-medium text-lg"><?php echo ($index + 1) . '. ' . htmlspecialchars($questao['pergunta']); ?></div>
                                                        <div class="quiz-options">
                                                            <?php foreach ($questao['alternativas'] as $alternativa): ?>
                                                            <label class="quiz-option">
                                                                <input type="radio" name="questao_<?php echo $questao['id']; ?>" value="<?php echo $alternativa['id']; ?>">
                                                                <span><?php echo htmlspecialchars($alternativa['texto']); ?></span>
                                                            </label>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                    <?php endforeach; ?>

                                                    <div class="mt-6">
                                                        <button type="button" id="submit-quiz" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                                            Enviar Respostas
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        <?php elseif ($aula['tipo'] === 'arquivo'): ?>
                                            <!-- Conteúdo de Arquivo -->
                                            <div class="text-center py-8">
                                                <div class="text-6xl text-blue-500 mb-4">
                                                    <i class="fas fa-file-download"></i>
                                                </div>
                                                <h3 class="text-xl font-medium mb-4">Arquivo para Download</h3>
                                                <?php if (isset($aula['conteudo']) && !empty($aula['conteudo'])): ?>
                                                <p class="text-gray-600 mb-6">Clique no botão abaixo para baixar o arquivo.</p>
                                                <a href="<?php echo $aula['conteudo']; ?>" download class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 inline-flex items-center">
                                                    <i class="fas fa-download mr-2"></i> Baixar Arquivo
                                                </a>
                                                <?php else: ?>
                                                <p class="text-gray-600 mb-6">Nenhum arquivo disponível para download.</p>
                                                <?php endif; ?>
                                            </div>
                                        <?php elseif ($aula['tipo'] === 'link'): ?>
                                            <!-- Conteúdo de Link -->
                                            <div class="text-center py-8">
                                                <div class="text-6xl text-green-500 mb-4">
                                                    <i class="fas fa-link"></i>
                                                </div>
                                                <h3 class="text-xl font-medium mb-4">Link Externo</h3>
                                                <?php if (isset($aula['conteudo']) && !empty($aula['conteudo'])): ?>
                                                <p class="text-gray-600 mb-6">Clique no botão abaixo para acessar o conteúdo externo.</p>
                                                <a href="<?php echo $aula['conteudo']; ?>" target="_blank" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 inline-flex items-center">
                                                    <i class="fas fa-external-link-alt mr-2"></i> Acessar Conteúdo
                                                </a>
                                                <?php else: ?>
                                                <p class="text-gray-600 mb-6">Nenhum link disponível.</p>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-center py-8 text-gray-500">
                                                <p>Tipo de conteúdo não suportado.</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <?php if (!empty($aula['descricao'])): ?>
                                    <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                                        <h3 class="text-lg font-medium mb-2">Sobre esta aula</h3>
                                        <div class="text-gray-700">
                                            <?php echo nl2br(htmlspecialchars($aula['descricao'])); ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <?php if (!empty($materiais)): ?>
                                    <div class="mt-6">
                                        <h3 class="text-lg font-medium mb-3">Materiais Complementares</h3>
                                        <div class="space-y-2">
                                            <?php foreach ($materiais as $material): ?>
                                                <?php
                                                $icon_class = 'material-link';
                                                $icon = 'fas fa-link';

                                                $arquivo_url = !empty($material['arquivo_path']) ? $material['arquivo_path'] : $material['link_url'];
                                                $ext = pathinfo($arquivo_url, PATHINFO_EXTENSION);
                                                if (in_array($ext, ['pdf'])) {
                                                    $icon_class = 'material-pdf';
                                                    $icon = 'fas fa-file-pdf';
                                                } elseif (in_array($ext, ['doc', 'docx', 'txt'])) {
                                                    $icon_class = 'material-doc';
                                                    $icon = 'fas fa-file-word';
                                                } elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                                                    $icon_class = 'material-img';
                                                    $icon = 'fas fa-file-image';
                                                } elseif (in_array($ext, ['zip', 'rar'])) {
                                                    $icon_class = 'material-zip';
                                                    $icon = 'fas fa-file-archive';
                                                }
                                                ?>
                                                <a href="<?php echo $arquivo_url; ?>" target="_blank" class="material-item">
                                                    <div class="material-icon <?php echo $icon_class; ?>">
                                                        <i class="<?php echo $icon; ?>"></i>
                                                    </div>
                                                    <div>
                                                        <div class="font-medium"><?php echo htmlspecialchars($material['titulo']); ?></div>
                                                        <?php if (!empty($material['descricao'])): ?>
                                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($material['descricao']); ?></div>
                                                        <?php endif; ?>
                                                    </div>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <div class="aula-navigation">
                                        <?php if ($aula_anterior): ?>
                                        <a href="ava_visualizar_aula.php?id=<?php echo $aula_anterior['id']; ?>" class="nav-button nav-button-prev">
                                            <i class="fas fa-arrow-left mr-2"></i> Aula Anterior
                                        </a>
                                        <?php else: ?>
                                        <div></div>
                                        <?php endif; ?>

                                        <?php if ($proxima_aula): ?>
                                        <a href="ava_visualizar_aula.php?id=<?php echo $proxima_aula['id']; ?>" class="nav-button nav-button-next">
                                            Próxima Aula <i class="fas fa-arrow-right ml-2"></i>
                                        </a>
                                        <?php else: ?>
                                        <a href="ava_visualizar_curso.php?id=<?php echo $aula['curso_id']; ?>" class="nav-button nav-button-next">
                                            Concluir Módulo <i class="fas fa-check ml-2"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sidebar da Aula -->
                        <div class="lg:col-span-1">
                            <div class="bg-white rounded-xl shadow-sm overflow-hidden aula-sidebar">
                                <div class="px-4 py-3 border-b border-gray-200">
                                    <h3 class="font-medium">Aulas do Módulo</h3>
                                </div>
                                <div class="p-2">
                                    <ul class="aula-list">
                                        <?php foreach ($aulas_modulo as $a): ?>
                                            <?php
                                            $icon_class = 'aula-texto';
                                            $icon = 'fas fa-file-alt';

                                            switch ($a['tipo']) {
                                                case 'video':
                                                    $icon_class = 'aula-video';
                                                    $icon = 'fas fa-play';
                                                    break;
                                                case 'quiz':
                                                    $icon_class = 'aula-quiz';
                                                    $icon = 'fas fa-question';
                                                    break;
                                                case 'arquivo':
                                                    $icon_class = 'aula-arquivo';
                                                    $icon = 'fas fa-file-download';
                                                    break;
                                                case 'link':
                                                    $icon_class = 'aula-link';
                                                    $icon = 'fas fa-link';
                                                    break;
                                            }
                                            ?>
                                            <li class="aula-list-item <?php echo ($a['id'] == $aula_id) ? 'active' : ''; ?>">
                                                <a href="ava_visualizar_aula.php?id=<?php echo $a['id']; ?>" class="flex items-center">
                                                    <div class="aula-list-item-icon <?php echo $icon_class; ?>">
                                                        <i class="<?php echo $icon; ?>"></i>
                                                    </div>
                                                    <span class="text-sm <?php echo ($a['id'] == $aula_id) ? 'font-medium' : ''; ?>"><?php echo htmlspecialchars($a['titulo']); ?></span>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
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

        // Quiz submission
        if (document.getElementById('submit-quiz')) {
            document.getElementById('submit-quiz').addEventListener('click', function() {
                // Em um sistema real, você enviaria as respostas para o servidor
                // Aqui, apenas simulamos o feedback

                alert('Respostas enviadas com sucesso! Em um ambiente real, você receberia feedback sobre suas respostas.');

                // Desabilitar o botão após o envio
                this.disabled = true;
                this.textContent = 'Respostas Enviadas';
                this.classList.remove('bg-blue-600', 'hover:bg-blue-700');
                this.classList.add('bg-green-600');
            });
        }
    </script>
</body>
</html>
