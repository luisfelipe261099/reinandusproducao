<?php
// Este arquivo é incluído a partir de ava.php
// Variáveis disponíveis: $curso_atual, $matricula, $aluno_id, $curso_id, $aula_id

// Verifica se as variáveis necessárias estão definidas
if (!isset($curso_atual) || !isset($matricula) || !isset($aluno_id) || !isset($curso_id) || !isset($aula_id)) {
    echo '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <div class="ml-3">
                <p>Erro ao carregar a aula. Parâmetros inválidos.</p>
            </div>
        </div>
    </div>';
    return;
}

// Obtém os dados da aula
$aula_atual = $db->query("SELECT a.*, m.titulo as modulo_titulo, m.id as modulo_id
                         FROM ava_aulas a
                         INNER JOIN ava_modulos m ON a.modulo_id = m.id
                         WHERE a.id = ? AND a.status = 'ativo'", [$aula_id]);

if (count($aula_atual) === 0) {
    echo '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <div class="ml-3">
                <p>Aula não encontrada ou inativa.</p>
            </div>
        </div>
    </div>';
    return;
}

$aula_atual = $aula_atual[0];

// Verifica se a aula pertence ao curso atual
$modulo = $db->query("SELECT * FROM ava_modulos WHERE id = ? AND curso_id = ?",
                     [$aula_atual['modulo_id'], $curso_id]);

if (count($modulo) === 0) {
    echo '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <div class="ml-3">
                <p>Esta aula não pertence ao curso selecionado.</p>
            </div>
        </div>
    </div>';
    return;
}

// Obtém o progresso da aula
$progresso_aula = $db->query("SELECT * FROM ava_progresso_aulas
                             WHERE matricula_id = ? AND aula_id = ?",
                             [$matricula, $aula_id]);

$aula_iniciada = count($progresso_aula) > 0;
$aula_concluida = $aula_iniciada && $progresso_aula[0]['status'] === 'concluida';

// Se a aula não foi iniciada, registra o início
if (!$aula_iniciada) {
    $db->insert('ava_progresso_aulas', [
        'matricula_id' => $matricula,
        'aula_id' => $aula_id,
        'status' => 'em_andamento',
        'data_inicio' => date('Y-m-d H:i:s'),
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ]);

    $progresso_aula_id = $db->lastInsertId();
} else {
    $progresso_aula_id = $progresso_aula[0]['id'];

    // Atualiza o status para em andamento se não estiver concluída
    if ($progresso_aula[0]['status'] !== 'concluida') {
        $db->query("UPDATE ava_progresso_aulas SET status = 'em_andamento', updated_at = ? WHERE id = ?",
                   [date('Y-m-d H:i:s'), $progresso_aula_id]);
    }
}

// Obtém as aulas do módulo atual
$aulas_modulo = $db->query("SELECT * FROM ava_aulas
                           WHERE modulo_id = ? AND status = 'ativo'
                           ORDER BY ordem ASC", [$aula_atual['modulo_id']]);

// Encontra a aula anterior e a próxima
$aula_anterior = null;
$proxima_aula = null;
$aula_atual_encontrada = false;

foreach ($aulas_modulo as $aula) {
    if ($aula_atual_encontrada) {
        $proxima_aula = $aula;
        break;
    }

    if ($aula['id'] == $aula_id) {
        $aula_atual_encontrada = true;
    } else {
        $aula_anterior = $aula;
    }
}

// Obtém os materiais complementares da aula
$materiais = $db->query("SELECT * FROM ava_materiais
                        WHERE aula_id = ? AND status = 'ativo'
                        ORDER BY ordem ASC", [$aula_id]);

// Obtém as questões do quiz (se for uma aula do tipo quiz)
$questoes = [];
if ($aula_atual['tipo'] === 'quiz') {
    $questoes = $db->query("SELECT * FROM ava_questoes
                           WHERE aula_id = ? AND status = 'ativo'
                           ORDER BY ordem ASC", [$aula_id]);
}

// Processa o formulário de conclusão da aula
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['concluir_aula'])) {
    // Marca a aula como concluída
    $db->query("UPDATE ava_progresso_aulas SET
               status = 'concluida',
               data_conclusao = ?,
               updated_at = ?
               WHERE id = ?",
               [date('Y-m-d H:i:s'), date('Y-m-d H:i:s'), $progresso_aula_id]);

    // Redireciona para a próxima aula, se houver
    if ($proxima_aula) {
        header("Location: ava.php?curso_id={$curso_id}&aula_id={$proxima_aula['id']}");
        exit;
    } else {
        // Se não houver próxima aula, redireciona para a página do curso
        header("Location: ava.php?curso_id={$curso_id}&concluido=1");
        exit;
    }
}

// Processa o formulário de envio do quiz
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_quiz'])) {
    $respostas = $_POST['resposta'] ?? [];
    $total_questoes = count($questoes);
    $respostas_corretas = 0;

    foreach ($questoes as $questao) {
        $resposta_aluno = $respostas[$questao['id']] ?? '';
        $resposta_correta = $questao['resposta_correta'];
        $correta = ($resposta_aluno === $resposta_correta);

        if ($correta) {
            $respostas_corretas++;
        }

        // Registra a resposta do aluno
        $db->insert('ava_respostas_alunos', [
            'progresso_aula_id' => $progresso_aula_id,
            'questao_id' => $questao['id'],
            'resposta' => $resposta_aluno,
            'correta' => $correta ? 1 : 0,
            'pontos_obtidos' => $correta ? $questao['pontos'] : 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    // Calcula a nota
    $nota = $total_questoes > 0 ? ($respostas_corretas / $total_questoes) * 10 : 0;

    // Atualiza o progresso da aula
    $db->query("UPDATE ava_progresso_aulas SET
               status = 'concluida',
               data_conclusao = ?,
               nota = ?,
               updated_at = ?
               WHERE id = ?",
               [date('Y-m-d H:i:s'), $nota, date('Y-m-d H:i:s'), $progresso_aula_id]);

    // Redireciona para a próxima aula, se houver
    if ($proxima_aula) {
        header("Location: ava.php?curso_id={$curso_id}&aula_id={$proxima_aula['id']}&quiz_concluido=1");
        exit;
    } else {
        // Se não houver próxima aula, redireciona para a página do curso
        header("Location: ava.php?curso_id={$curso_id}&concluido=1&quiz_concluido=1");
        exit;
    }
}
?>

<div class="flex flex-col md:flex-row">
    <!-- Sidebar com lista de aulas -->
    <div class="md:w-1/4 md:pr-6 mb-6 md:mb-0">
        <a href="ava.php?curso_id=<?php echo $curso_id; ?>" class="text-indigo-600 hover:text-indigo-800 mb-4 inline-block">
            <i class="fas fa-arrow-left mr-2"></i> Voltar para o Curso
        </a>

        <div class="bg-white rounded-lg shadow-sm overflow-hidden sticky top-20">
            <div class="p-4 bg-indigo-600 text-white">
                <h3 class="font-semibold"><?php echo $aula_atual['modulo_titulo']; ?></h3>
            </div>

            <ul class="divide-y divide-gray-100">
                <?php foreach ($aulas_modulo as $aula): ?>
                <?php
                // Verifica se a aula foi concluída
                $progresso = $db->query("SELECT * FROM ava_progresso_aulas
                                        WHERE matricula_id = ? AND aula_id = ?",
                                        [$matricula, $aula['id']]);

                $concluida = count($progresso) > 0 && $progresso[0]['status'] === 'concluida';
                $atual = $aula['id'] == $aula_id;
                ?>
                <li class="<?php echo $atual ? 'bg-indigo-50' : ''; ?> hover:bg-gray-50">
                    <a href="ava.php?curso_id=<?php echo $curso_id; ?>&aula_id=<?php echo $aula['id']; ?>" class="flex items-center p-4">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center mr-3 <?php echo $concluida ? 'bg-green-100 text-green-600' : ($atual ? 'bg-indigo-100 text-indigo-600' : 'bg-gray-100 text-gray-600'); ?>">
                            <?php if ($aula['tipo'] === 'video'): ?>
                            <i class="fas fa-play-circle"></i>
                            <?php elseif ($aula['tipo'] === 'quiz'): ?>
                            <i class="fas fa-question-circle"></i>
                            <?php elseif ($aula['tipo'] === 'arquivo'): ?>
                            <i class="fas fa-file-alt"></i>
                            <?php elseif ($aula['tipo'] === 'link'): ?>
                            <i class="fas fa-link"></i>
                            <?php else: ?>
                            <i class="fas fa-book-open"></i>
                            <?php endif; ?>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-900"><?php echo $aula['titulo']; ?></h4>
                            <?php if ($aula['duracao_minutos']): ?>
                            <p class="text-xs text-gray-500 mt-1">
                                <i class="fas fa-clock mr-1"></i> <?php echo $aula['duracao_minutos']; ?> min
                            </p>
                            <?php endif; ?>
                        </div>
                        <?php if ($concluida): ?>
                        <div class="ml-auto">
                            <i class="fas fa-check-circle text-green-500"></i>
                        </div>
                        <?php endif; ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <!-- Conteúdo da aula -->
    <div class="md:w-3/4">
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-2xl font-bold text-gray-800"><?php echo $aula_atual['titulo']; ?></h2>
                <p class="text-gray-600 mt-1"><?php echo $aula_atual['modulo_titulo']; ?></p>
            </div>

            <div class="aula-content-body">
                <?php if ($aula_atual['tipo'] === 'video' && !empty($aula_atual['video_url'])): ?>
                <!-- Conteúdo de vídeo -->
                <div class="aspect-w-16 aspect-h-9 mb-6">
                    <iframe src="<?php echo $aula_atual['video_url']; ?>" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                </div>
                <?php endif; ?>

                <?php if (!empty($aula_atual['conteudo'])): ?>
                <!-- Conteúdo de texto -->
                <div class="aula-texto-container p-6">
                    <div class="prose max-w-none aula-content-formatted">
                        <?php
                        // Processa o conteúdo para melhorar a formatação
                        $conteudo = $aula_atual['conteudo'];

                        // Garante que haja espaço após emojis para melhor formatação
                        $emojis = array('📝', '👉', '🖥️', '👤', '🧾', '📧', '🎓', '📱', '✅', '📋', '🎉');
                        foreach ($emojis as $emoji) {
                            // Adiciona espaço após o emoji se não houver
                            $conteudo = str_replace($emoji, $emoji . ' ', $conteudo);

                            // Envolve o emoji em um span para estilização
                            $conteudo = str_replace($emoji . ' ', '<span class="emoji">' . $emoji . '</span> ', $conteudo);
                        }

                        // Adiciona quebras de linha após parágrafos com emojis
                        $linhas = explode("\n", $conteudo);
                        $conteudo_processado = '';

                        foreach ($linhas as $linha) {
                            $tem_emoji = false;
                            foreach ($emojis as $emoji) {
                                if (strpos($linha, $emoji) !== false) {
                                    $tem_emoji = true;
                                    break;
                                }
                            }

                            if ($tem_emoji) {
                                // Adiciona quebra de linha após a linha com emoji
                                $conteudo_processado .= $linha . "<br>\n";
                            } else {
                                $conteudo_processado .= $linha . "\n";
                            }
                        }

                        echo $conteudo_processado;
                        ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($aula_atual['tipo'] === 'arquivo' && !empty($aula_atual['arquivo_path'])): ?>
                <!-- Conteúdo de arquivo -->
                <div class="p-6">
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 flex items-center">
                        <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center mr-4">
                            <i class="fas fa-file-alt text-indigo-600 text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-medium text-gray-900">Material da Aula</h4>
                            <p class="text-sm text-gray-500">Clique no botão ao lado para baixar o arquivo.</p>
                        </div>
                        <a href="<?php echo $aula_atual['arquivo_path']; ?>" target="_blank" class="btn btn-primary">
                            <i class="fas fa-download mr-2"></i> Baixar
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($aula_atual['tipo'] === 'link' && !empty($aula_atual['link_url'])): ?>
                <!-- Conteúdo de link -->
                <div class="p-6">
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 flex items-center">
                        <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center mr-4">
                            <i class="fas fa-link text-indigo-600 text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-medium text-gray-900">Link Externo</h4>
                            <p class="text-sm text-gray-500">Clique no botão ao lado para acessar o conteúdo externo.</p>
                        </div>
                        <a href="<?php echo $aula_atual['link_url']; ?>" target="_blank" class="btn btn-primary">
                            <i class="fas fa-external-link-alt mr-2"></i> Acessar
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($aula_atual['tipo'] === 'quiz' && count($questoes) > 0): ?>
                <!-- Conteúdo de quiz -->
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Questionário</h3>

                    <?php if (!$aula_concluida): ?>
                    <form id="quiz-form" method="post" action="">
                        <?php foreach ($questoes as $index => $questao): ?>
                        <div class="aula-quiz-question bg-gray-50 border border-gray-200 rounded-lg p-4 mb-4">
                            <h4 class="font-medium text-gray-900 mb-2"><?php echo ($index + 1) . '. ' . $questao['pergunta']; ?></h4>

                            <?php if ($questao['tipo'] === 'multipla_escolha'): ?>
                            <?php
                            $opcoes = json_decode($questao['opcoes'], true);
                            if (is_array($opcoes)):
                            ?>
                            <div class="space-y-2">
                                <?php foreach ($opcoes as $letra => $opcao): ?>
                                <div class="flex items-center">
                                    <input type="radio" id="questao_<?php echo $questao['id']; ?>_<?php echo $letra; ?>" name="resposta[<?php echo $questao['id']; ?>]" value="<?php echo $letra; ?>" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                    <label for="questao_<?php echo $questao['id']; ?>_<?php echo $letra; ?>" class="ml-3 block text-sm font-medium text-gray-700">
                                        <?php echo $letra . ') ' . $opcao; ?>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                            <?php elseif ($questao['tipo'] === 'verdadeiro_falso'): ?>
                            <div class="space-y-2">
                                <div class="flex items-center">
                                    <input type="radio" id="questao_<?php echo $questao['id']; ?>_v" name="resposta[<?php echo $questao['id']; ?>]" value="V" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                    <label for="questao_<?php echo $questao['id']; ?>_v" class="ml-3 block text-sm font-medium text-gray-700">
                                        Verdadeiro
                                    </label>
                                </div>
                                <div class="flex items-center">
                                    <input type="radio" id="questao_<?php echo $questao['id']; ?>_f" name="resposta[<?php echo $questao['id']; ?>]" value="F" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                    <label for="questao_<?php echo $questao['id']; ?>_f" class="ml-3 block text-sm font-medium text-gray-700">
                                        Falso
                                    </label>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>

                        <div class="mt-6">
                            <button type="submit" name="enviar_quiz" class="btn btn-primary">
                                <i class="fas fa-check-circle mr-2"></i> Enviar Respostas
                            </button>
                        </div>
                    </form>
                    <?php else: ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="ml-3">
                                <p>Você já concluiu este questionário.</p>
                                <?php if (isset($progresso_aula[0]['nota'])): ?>
                                <p class="mt-2">Sua nota: <?php echo number_format($progresso_aula[0]['nota'], 1, ',', '.'); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if (count($materiais) > 0): ?>
                <!-- Materiais complementares -->
                <div class="p-6 border-t border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Materiais Complementares</h3>

                    <div class="space-y-3">
                        <?php foreach ($materiais as $material): ?>
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 flex items-center">
                            <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center mr-3">
                                <?php if ($material['tipo'] === 'pdf'): ?>
                                <i class="fas fa-file-pdf text-indigo-600"></i>
                                <?php elseif (in_array($material['tipo'], ['doc', 'docx'])): ?>
                                <i class="fas fa-file-word text-indigo-600"></i>
                                <?php elseif (in_array($material['tipo'], ['xls', 'xlsx'])): ?>
                                <i class="fas fa-file-excel text-indigo-600"></i>
                                <?php elseif (in_array($material['tipo'], ['ppt', 'pptx'])): ?>
                                <i class="fas fa-file-powerpoint text-indigo-600"></i>
                                <?php elseif ($material['tipo'] === 'imagem'): ?>
                                <i class="fas fa-file-image text-indigo-600"></i>
                                <?php elseif ($material['tipo'] === 'video'): ?>
                                <i class="fas fa-file-video text-indigo-600"></i>
                                <?php elseif ($material['tipo'] === 'audio'): ?>
                                <i class="fas fa-file-audio text-indigo-600"></i>
                                <?php elseif ($material['tipo'] === 'zip'): ?>
                                <i class="fas fa-file-archive text-indigo-600"></i>
                                <?php else: ?>
                                <i class="fas fa-file-alt text-indigo-600"></i>
                                <?php endif; ?>
                            </div>
                            <div class="flex-1">
                                <h4 class="font-medium text-gray-900"><?php echo $material['titulo']; ?></h4>
                                <?php if (!empty($material['descricao'])): ?>
                                <p class="text-xs text-gray-500 mt-1"><?php echo $material['descricao']; ?></p>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($material['arquivo_path'])): ?>
                            <a href="<?php echo $material['arquivo_path']; ?>" target="_blank" class="text-indigo-600 hover:text-indigo-800">
                                <i class="fas fa-download"></i>
                            </a>
                            <?php elseif (!empty($material['link_url'])): ?>
                            <a href="<?php echo $material['link_url']; ?>" target="_blank" class="text-indigo-600 hover:text-indigo-800">
                                <i class="fas fa-external-link-alt"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="p-6 border-t border-gray-200 bg-gray-50 flex justify-between items-center">
                <?php if ($aula_anterior): ?>
                <a href="ava.php?curso_id=<?php echo $curso_id; ?>&aula_id=<?php echo $aula_anterior['id']; ?>" class="btn btn-outline">
                    <i class="fas fa-arrow-left mr-2"></i> Aula Anterior
                </a>
                <?php else: ?>
                <div></div>
                <?php endif; ?>

                <div>
                    <?php if (!$aula_concluida && $aula_atual['tipo'] !== 'quiz'): ?>
                    <form method="post" action="" class="inline">
                        <button type="submit" name="concluir_aula" class="btn btn-primary">
                            <?php if ($proxima_aula): ?>
                            <i class="fas fa-check mr-2"></i> Marcar como Concluída e Avançar
                            <?php else: ?>
                            <i class="fas fa-check mr-2"></i> Marcar como Concluída
                            <?php endif; ?>
                        </button>
                    </form>
                    <?php elseif ($proxima_aula): ?>
                    <a href="ava.php?curso_id=<?php echo $curso_id; ?>&aula_id=<?php echo $proxima_aula['id']; ?>" class="btn btn-primary">
                        Próxima Aula <i class="fas fa-arrow-right ml-2"></i>
                    </a>
                    <?php else: ?>
                    <a href="ava.php?curso_id=<?php echo $curso_id; ?>" class="btn btn-primary">
                        <i class="fas fa-check-circle mr-2"></i> Concluir Módulo
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Scripts específicos da página
$page_scripts = '
// Adiciona estilos específicos para o conteúdo da aula
document.addEventListener("DOMContentLoaded", function() {
    // Adiciona classes para melhorar a aparência de vídeos incorporados
    const iframes = document.querySelectorAll(".aula-content-body iframe");
    iframes.forEach(iframe => {
        iframe.classList.add("w-full", "h-full", "absolute", "inset-0");

        // Adiciona wrapper se necessário
        if (!iframe.parentElement.classList.contains("aspect-w-16")) {
            const wrapper = document.createElement("div");
            wrapper.classList.add("aspect-w-16", "aspect-h-9", "mb-6");
            iframe.parentNode.insertBefore(wrapper, iframe);
            wrapper.appendChild(iframe);
        }
    });

    // Adiciona estilos CSS para melhorar a formatação
    const styleElement = document.createElement("style");
    styleElement.textContent = `
        .aula-content-formatted {
            font-size: 1.05rem;
            line-height: 1.7;
            color: #333;
        }

        .aula-content-formatted p {
            margin-bottom: 1.25rem;
            white-space: pre-line; /* Preserva quebras de linha */
        }

        /* Garante que imagens sejam exibidas corretamente */
        .aula-content-formatted img {
            display: block;
            margin: 2rem auto;
            max-width: 100%;
            height: auto;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .aula-content-formatted h2,
        .aula-content-formatted h3,
        .aula-content-formatted h4 {
            margin-top: 2rem;
            margin-bottom: 1rem;
            font-weight: 600;
            color: #1F2937;
        }

        .aula-content-formatted ul,
        .aula-content-formatted ol {
            margin-top: 1rem;
            margin-bottom: 1.5rem;
            padding-left: 1.5rem;
        }

        .aula-content-formatted li {
            margin-bottom: 0.75rem;
            position: relative;
        }

        .aula-content-formatted ul li::before {
            content: "•";
            position: absolute;
            left: -1.25rem;
            color: #4F46E5;
        }

        .aula-content-formatted ol {
            counter-reset: item;
        }

        .aula-content-formatted ol li {
            counter-increment: item;
        }

        .aula-content-formatted ol li::before {
            content: counter(item) ".";
            position: absolute;
            left: -1.5rem;
            color: #4F46E5;
            font-weight: 600;
        }

        /* Estilos para emojis */
        .emoji-paragraph {
            line-height: 1.8;
            letter-spacing: 0.01em;
        }

        /* Estilo específico para emojis */
        .aula-content-formatted p:first-letter {
            margin-right: 0.2em;
        }

        /* Garante que emojis tenham espaço adequado */
        .aula-content-formatted p span.emoji {
            display: inline-block;
            margin-right: 0.5em;
            font-size: 1.2em;
        }

        /* Estilos para imagens */
        .image-wrapper {
            margin: 2rem 0;
            display: flex;
            justify-content: center;
        }

        .aula-texto-container img {
            max-width: 100%;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
    `;
    document.head.appendChild(styleElement);

    // Melhora a formatação do conteúdo da aula
    const contentContainer = document.querySelector(".aula-content-formatted");
    if (contentContainer) {
        // Processa o conteúdo para garantir quebras de linha adequadas
        const paragraphs = contentContainer.querySelectorAll("p");
        paragraphs.forEach(p => {
            p.classList.add("mb-4", "leading-relaxed");

            // Verifica se o parágrafo contém emojis e ajusta o espaçamento
            if (p.innerHTML.match(/[\u{1F300}-\u{1F6FF}]/u)) {
                p.classList.add("emoji-paragraph");
            }
        });

        // Processa listas para melhorar a formatação
        const lists = contentContainer.querySelectorAll("ul, ol");
        lists.forEach(list => {
            list.classList.add("my-4", "pl-5");

            const items = list.querySelectorAll("li");
            items.forEach(item => {
                item.classList.add("mb-2", "leading-relaxed");
            });
        });
    }

    // Adiciona classes para melhorar a aparência de imagens
    const images = document.querySelectorAll(".aula-texto-container img");
    images.forEach(img => {
        // Adiciona classes para estilizar a imagem
        img.classList.add("rounded-lg", "shadow-sm", "my-6");
        img.style.maxWidth = "100%";
        img.style.height = "auto";

        // Centraliza a imagem e adiciona espaço adequado
        const parent = img.parentElement;
        if (parent.tagName !== "FIGURE") {
            // Cria um wrapper para a imagem
            const wrapper = document.createElement("div");
            wrapper.classList.add("image-wrapper", "flex", "justify-center", "my-6");
            img.parentNode.insertBefore(wrapper, img);
            wrapper.appendChild(img);
        }

        // Adiciona espaço após a imagem para separar do texto
        const nextElement = img.parentElement.nextElementSibling;
        if (nextElement && nextElement.tagName === "P") {
            nextElement.style.marginTop = "1.5rem";
        }
    });

    // Processa parágrafos com emojis para melhorar a formatação
    const emojiParagraphs = document.querySelectorAll(".aula-texto-container p");
    emojiParagraphs.forEach(p => {
        // Verifica se o texto contém emojis comuns
        const hasEmoji = /📝|👉|🖥️|👤|🧾|📧|🎓|📱|✅|📋|🎉/.test(p.textContent);

        if (hasEmoji) {
            // Adiciona espaçamento adequado para emojis
            p.style.lineHeight = "1.8";
            p.style.letterSpacing = "0.01em";
            p.style.marginBottom = "1.5rem";

            // Adiciona classe para estilização adicional
            p.classList.add("emoji-paragraph");

            // Substitui o conteúdo para adicionar quebras de linha após emojis
            let html = p.innerHTML;

            // Lista de emojis comuns para substituir
            const emojis = ["📝", "👉", "🖥️", "👤", "🧾", "📧", "🎓", "📱", "✅", "📋", "🎉"];

            // Para cada emoji, adiciona uma quebra de linha após ele
            emojis.forEach(emoji => {
                html = html.split(emoji + " ").join(emoji + "<br>");
            });

            p.innerHTML = html;
        }
    });

    // Adiciona classes para melhorar a aparência de tabelas
    const tables = document.querySelectorAll(".aula-texto-container table");
    tables.forEach(table => {
        table.classList.add("min-w-full", "divide-y", "divide-gray-200", "my-6");

        const ths = table.querySelectorAll("th");
        ths.forEach(th => {
            th.classList.add("px-6", "py-3", "bg-gray-50", "text-left", "text-xs", "font-medium", "text-gray-500", "uppercase", "tracking-wider");
        });

        const tds = table.querySelectorAll("td");
        tds.forEach(td => {
            td.classList.add("px-6", "py-4", "text-sm", "text-gray-500");
            // Remove whitespace-nowrap para permitir quebra de linha nas células
            td.classList.remove("whitespace-nowrap");
        });
    });
});
';
?>
