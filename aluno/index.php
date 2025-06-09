<?php
// Define o título da página
$titulo_pagina = 'Dashboard';

// Inclui o cabeçalho
include 'includes/header.php';

// Obtém os dados do aluno
$aluno_id = $_SESSION['user_id'];

// Obtém a matrícula do aluno
$matricula = $db->fetchOne("SELECT * FROM matriculas WHERE aluno_id = ? AND status = 'ativo' ORDER BY data_matricula DESC LIMIT 1", [$aluno_id]);

// Obtém o curso do aluno
$curso = null;
if ($matricula) {
    $curso = $db->fetchOne("SELECT * FROM cursos WHERE id = ?", [$matricula['curso_id']]);
}

// Obtém a turma do aluno
$turma = null;
if ($matricula && $matricula['turma_id']) {
    $turma = $db->fetchOne("SELECT * FROM turmas WHERE id = ?", [$matricula['turma_id']]);
}

// Obtém o polo do aluno
$polo = null;
if ($matricula && $matricula['polo_id']) {
    $polo = $db->fetchOne("SELECT * FROM polos WHERE id = ?", [$matricula['polo_id']]);
}

// Obtém as disciplinas do curso
$disciplinas = [];
if ($curso) {
    $disciplinas = $db->fetchAll("SELECT * FROM disciplinas WHERE curso_id = ? AND status = 'ativo'", [$curso['id']]);
}

// Obtém as notas do aluno
$notas = [];
if ($matricula) {
    $notas = $db->fetchAll("SELECT nd.*, d.nome as disciplina_nome, d.carga_horaria
                        FROM notas_disciplinas nd
                        INNER JOIN disciplinas d ON nd.disciplina_id = d.id
                        WHERE nd.matricula_id = ?
                        ORDER BY nd.data_lancamento DESC", [$matricula['id']]);
}

// Calcula a média geral do aluno
$media_geral = 0;
$total_disciplinas_com_nota = 0;

if (count($notas) > 0) {
    $soma_notas = 0;
    foreach ($notas as $nota) {
        if ($nota['nota'] !== null) {
            $soma_notas += $nota['nota'];
            $total_disciplinas_com_nota++;
        }
    }

    if ($total_disciplinas_com_nota > 0) {
        $media_geral = $soma_notas / $total_disciplinas_com_nota;
    }
}

// Calcula o progresso do curso
$progresso_curso = 0;
if (count($disciplinas) > 0 && count($notas) > 0) {
    $disciplinas_aprovadas = 0;
    foreach ($notas as $nota) {
        if ($nota['situacao'] === 'aprovado') {
            $disciplinas_aprovadas++;
        }
    }

    $progresso_curso = ($disciplinas_aprovadas / count($disciplinas)) * 100;
}

// Obtém os documentos emitidos para o aluno
$documentos = $db->fetchAll("SELECT * FROM documentos_emitidos
                         WHERE aluno_id = ?
                         ORDER BY data_emissao DESC
                         LIMIT 5", [$aluno_id]);

// Obtém os próximos eventos do calendário
$eventos = $db->fetchAll("SELECT * FROM eventos
                      WHERE (turma_id = ? OR turma_id IS NULL)
                      AND data >= CURDATE()
                      ORDER BY data ASC
                      LIMIT 5", [$turma['id'] ?? 0]);

// Obtém as mensagens não lidas
$mensagens = $db->fetchAll("SELECT m.*, u.nome as remetente_nome FROM mensagens m
                        INNER JOIN usuarios u ON m.remetente_id = u.id
                        WHERE m.destinatario_id = ? AND m.destinatario_tipo = 'aluno' AND m.lida = 0
                        ORDER BY m.data_envio DESC
                        LIMIT 5", [$aluno_id]);

// Verifica se o aluno tem acesso ao AVA
$acesso_ava = $aluno['acesso_ava'] ?? 0;

// Obtém os cursos do AVA do aluno
$cursos_ava = [];
if ($acesso_ava) {
    $cursos_ava = $db->fetchAll("SELECT ac.*, m.progresso
                             FROM ava_cursos ac
                             INNER JOIN ava_matriculas m ON ac.id = m.curso_id
                             WHERE m.aluno_id = ?
                             ORDER BY m.data_matricula DESC", [$aluno_id]);
}

// Obtém as atividades recentes do aluno
$atividades = $db->fetchAll("SELECT * FROM alunos_atividades
                         WHERE aluno_id = ?
                         ORDER BY created_at DESC
                         LIMIT 10", [$aluno_id]);
?>

<div class="dashboard-welcome mb-8">
    <h2 class="text-2xl font-bold text-gray-800 mb-2">Olá, <?php echo $aluno['nome']; ?>!</h2>
    <p class="text-gray-600">Bem-vindo ao seu portal do aluno. Aqui você pode acompanhar seu progresso acadêmico, acessar documentos e muito mais.</p>
</div>

<div class="dashboard-stats">
    <div class="stat-card animate-fade-in" style="opacity: 0; transform: translateY(10px);">
        <div class="stat-icon">
            <i class="fas fa-graduation-cap"></i>
        </div>
        <div class="stat-value"><?php echo number_format($media_geral, 1, ',', '.'); ?></div>
        <div class="stat-label">Média Geral</div>
    </div>

    <div class="stat-card secondary animate-fade-in" style="opacity: 0; transform: translateY(10px);">
        <div class="stat-icon">
            <i class="fas fa-book"></i>
        </div>
        <div class="stat-value"><?php echo count($disciplinas); ?></div>
        <div class="stat-label">Disciplinas</div>
    </div>

    <div class="stat-card warning animate-fade-in" style="opacity: 0; transform: translateY(10px);">
        <div class="stat-icon">
            <i class="fas fa-file-alt"></i>
        </div>
        <div class="stat-value"><?php echo count($documentos); ?></div>
        <div class="stat-label">Documentos</div>
    </div>

    <div class="stat-card info animate-fade-in" style="opacity: 0; transform: translateY(10px);">
        <div class="stat-icon">
            <i class="fas fa-chart-line"></i>
        </div>
        <div class="stat-value"><?php echo round($progresso_curso); ?>%</div>
        <div class="stat-label">Progresso do Curso</div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Informações do Curso -->
    <div class="card animate-fade-in" style="opacity: 0; transform: translateY(10px);">
        <div class="card-header">
            <h3 class="card-title">Meu Curso</h3>
            <a href="cursos.php" class="text-sm text-blue-600 hover:text-blue-800">Ver detalhes</a>
        </div>
        <div class="card-body">
            <?php if ($curso): ?>
            <div class="flex items-center mb-4">
                <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center mr-4">
                    <i class="fas fa-graduation-cap text-blue-600 text-xl"></i>
                </div>
                <div>
                    <h4 class="font-semibold text-gray-900"><?php echo $curso['nome']; ?></h4>
                    <p class="text-sm text-gray-500"><?php echo $curso['tipo']; ?></p>
                </div>
            </div>

            <div class="border-t border-gray-100 pt-4">
                <div class="flex justify-between mb-2">
                    <span class="text-sm text-gray-600">Turma:</span>
                    <span class="text-sm font-medium"><?php echo $turma['nome'] ?? 'Não definida'; ?></span>
                </div>
                <div class="flex justify-between mb-2">
                    <span class="text-sm text-gray-600">Polo:</span>
                    <span class="text-sm font-medium"><?php echo $polo['nome'] ?? 'Não definido'; ?></span>
                </div>
                <div class="flex justify-between mb-2">
                    <span class="text-sm text-gray-600">Data de Matrícula:</span>
                    <span class="text-sm font-medium"><?php echo date('d/m/Y', strtotime($matricula['data_matricula'])); ?></span>
                </div>
                <div class="flex justify-between mb-2">
                    <span class="text-sm text-gray-600">Início do Curso:</span>
                    <span class="text-sm font-medium"><?php echo date('d/m/Y', strtotime($matricula['data_inicio'])); ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Previsão de Término:</span>
                    <span class="text-sm font-medium"><?php echo date('d/m/Y', strtotime($matricula['data_fim'])); ?></span>
                </div>
            </div>
            <?php else: ?>
            <div class="text-center py-6">
                <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-graduation-cap text-gray-400 text-2xl"></i>
                </div>
                <h4 class="font-medium text-gray-900 mb-1">Nenhum curso encontrado</h4>
                <p class="text-sm text-gray-500">Você ainda não está matriculado em nenhum curso.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Últimas Notas -->
    <div class="card animate-fade-in" style="opacity: 0; transform: translateY(10px);">
        <div class="card-header">
            <h3 class="card-title">Últimas Notas</h3>
            <a href="notas.php" class="text-sm text-blue-600 hover:text-blue-800">Ver todas</a>
        </div>
        <div class="card-body p-0">
            <?php if (count($notas) > 0): ?>
            <div class="divide-y divide-gray-100">
                <?php foreach (array_slice($notas, 0, 5) as $nota): ?>
                <div class="p-4 hover:bg-gray-50">
                    <div class="flex justify-between items-center">
                        <div>
                            <h4 class="font-medium text-gray-900"><?php echo $nota['disciplina_nome']; ?></h4>
                            <p class="text-xs text-gray-500 mt-1">
                                <?php echo date('d/m/Y', strtotime($nota['data_lancamento'])); ?>
                            </p>
                        </div>
                        <div class="text-right">
                            <span class="inline-block px-2 py-1 rounded-full text-sm font-medium <?php echo $nota['nota'] >= 7 ? 'bg-green-100 text-green-800' : ($nota['nota'] >= 5 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'); ?>">
                                <?php echo number_format($nota['nota'], 1, ',', '.'); ?>
                            </span>
                            <div class="text-xs text-gray-500 mt-1">
                                <?php echo ucfirst($nota['situacao']); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="text-center py-8">
                <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-chart-line text-gray-400 text-2xl"></i>
                </div>
                <h4 class="font-medium text-gray-900 mb-1">Nenhuma nota encontrada</h4>
                <p class="text-sm text-gray-500">Suas notas aparecerão aqui quando forem lançadas.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Próximos Eventos -->
    <div class="card animate-fade-in" style="opacity: 0; transform: translateY(10px);">
        <div class="card-header">
            <h3 class="card-title">Próximos Eventos</h3>
            <a href="calendario.php" class="text-sm text-blue-600 hover:text-blue-800">Ver calendário</a>
        </div>
        <div class="card-body p-0">
            <?php if (count($eventos) > 0): ?>
            <div class="divide-y divide-gray-100">
                <?php foreach ($eventos as $evento): ?>
                <div class="p-4 hover:bg-gray-50">
                    <div class="flex">
                        <div class="flex-shrink-0 mr-4">
                            <div class="w-12 h-12 rounded-lg bg-blue-100 flex flex-col items-center justify-center text-center">
                                <span class="text-xs font-medium text-blue-800"><?php echo date('M', strtotime($evento['data'])); ?></span>
                                <span class="text-lg font-bold text-blue-800"><?php echo date('d', strtotime($evento['data'])); ?></span>
                            </div>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-900"><?php echo $evento['titulo']; ?></h4>
                            <p class="text-xs text-gray-500 mt-1">
                                <?php echo date('H:i', strtotime($evento['hora_inicio'])); ?> - <?php echo date('H:i', strtotime($evento['hora_fim'])); ?>
                            </p>
                            <?php if (!empty($evento['local'])): ?>
                            <p class="text-xs text-gray-500 mt-1">
                                <i class="fas fa-map-marker-alt mr-1"></i> <?php echo $evento['local']; ?>
                            </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="text-center py-8">
                <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-calendar-alt text-gray-400 text-2xl"></i>
                </div>
                <h4 class="font-medium text-gray-900 mb-1">Nenhum evento próximo</h4>
                <p class="text-sm text-gray-500">Não há eventos agendados para os próximos dias.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Progresso do Curso -->
<?php if ($curso): ?>
<div class="mt-8 animate-fade-in" style="opacity: 0; transform: translateY(10px);">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Progresso do Curso</h3>

    <div class="card">
        <div class="card-body">
            <div class="flex flex-col md:flex-row">
                <div class="md:w-1/3 flex justify-center items-center mb-6 md:mb-0">
                    <div class="relative w-48 h-48">
                        <canvas id="progressChart"></canvas>
                        <div class="absolute inset-0 flex items-center justify-center flex-col">
                            <span class="text-3xl font-bold text-indigo-600"><?php echo round($progresso_curso); ?>%</span>
                            <span class="text-sm text-gray-500">Concluído</span>
                        </div>
                    </div>
                </div>

                <div class="md:w-2/3 md:pl-8">
                    <h4 class="font-semibold text-gray-900 mb-4">Disciplinas</h4>

                    <div class="space-y-4">
                        <?php
                        $disciplinas_status = [];
                        foreach ($disciplinas as $disciplina) {
                            $status = 'cursando';
                            $nota_valor = null;

                            foreach ($notas as $nota) {
                                if ($nota['disciplina_id'] == $disciplina['id']) {
                                    $status = $nota['situacao'];
                                    $nota_valor = $nota['nota'];
                                    break;
                                }
                            }

                            $disciplinas_status[] = [
                                'disciplina' => $disciplina,
                                'status' => $status,
                                'nota' => $nota_valor
                            ];
                        }

                        // Exibe apenas as primeiras 5 disciplinas
                        $disciplinas_exibir = array_slice($disciplinas_status, 0, 5);

                        foreach ($disciplinas_exibir as $item):
                        ?>
                        <div class="flex items-center">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center mr-3
                                <?php
                                if ($item['status'] === 'aprovado') {
                                    echo 'bg-green-100 text-green-600';
                                } elseif ($item['status'] === 'reprovado') {
                                    echo 'bg-red-100 text-red-600';
                                } else {
                                    echo 'bg-gray-100 text-gray-600';
                                }
                                ?>">
                                <?php
                                if ($item['status'] === 'aprovado') {
                                    echo '<i class="fas fa-check"></i>';
                                } elseif ($item['status'] === 'reprovado') {
                                    echo '<i class="fas fa-times"></i>';
                                } else {
                                    echo '<i class="fas fa-clock"></i>';
                                }
                                ?>
                            </div>
                            <div class="flex-1">
                                <div class="flex justify-between items-center">
                                    <h5 class="font-medium text-gray-900"><?php echo $item['disciplina']['nome']; ?></h5>
                                    <?php if ($item['nota'] !== null): ?>
                                    <span class="inline-block px-2 py-1 rounded-full text-xs font-medium
                                        <?php
                                        if ($item['nota'] >= 7) {
                                            echo 'bg-green-100 text-green-800';
                                        } elseif ($item['nota'] >= 5) {
                                            echo 'bg-yellow-100 text-yellow-800';
                                        } else {
                                            echo 'bg-red-100 text-red-800';
                                        }
                                        ?>">
                                        <?php echo number_format($item['nota'], 1, ',', '.'); ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-xs text-gray-500 mt-1">
                                    <?php echo $item['disciplina']['carga_horaria']; ?> horas •
                                    <?php
                                    if ($item['status'] === 'aprovado') {
                                        echo '<span class="text-green-600">Aprovado</span>';
                                    } elseif ($item['status'] === 'reprovado') {
                                        echo '<span class="text-red-600">Reprovado</span>';
                                    } else {
                                        echo '<span class="text-gray-600">Cursando</span>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <?php if (count($disciplinas) > 5): ?>
                        <div class="text-center mt-4">
                            <a href="cursos.php" class="text-sm text-indigo-600 hover:text-indigo-800">
                                Ver todas as <?php echo count($disciplinas); ?> disciplinas
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Cursos do AVA -->
<?php if ($acesso_ava && count($cursos_ava) > 0): ?>
<div class="mt-8">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Meus Cursos no AVA</h3>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($cursos_ava as $curso_ava): ?>
        <div class="card animate-fade-in" style="opacity: 0; transform: translateY(10px);">
            <div class="relative">
                <img src="<?php echo !empty($curso_ava['imagem']) ? $curso_ava['imagem'] : '../assets/img/curso-placeholder.jpg'; ?>" alt="<?php echo $curso_ava['titulo']; ?>" class="w-full h-40 object-cover rounded-t-lg">
                <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 to-transparent p-4">
                    <h4 class="text-white font-semibold"><?php echo $curso_ava['titulo']; ?></h4>
                </div>
            </div>
            <div class="p-4">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm text-gray-500"><?php echo $curso_ava['categoria'] ?? 'Geral'; ?></span>
                    <span class="text-xs px-2 py-1 bg-blue-100 text-blue-800 rounded-full"><?php echo $curso_ava['carga_horaria']; ?> horas</span>
                </div>
                <div class="mb-4">
                    <div class="flex justify-between text-sm mb-1">
                        <span>Progresso</span>
                        <span><?php echo $curso_ava['progresso']; ?>%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo $curso_ava['progresso']; ?>%"></div>
                    </div>
                </div>
                <a href="ava.php?curso_id=<?php echo $curso_ava['id']; ?>" class="btn btn-primary w-full">
                    <i class="fas fa-play-circle mr-2"></i> Continuar Curso
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Atividades Recentes -->
<div class="mt-8 animate-fade-in" style="opacity: 0; transform: translateY(10px);">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Atividades Recentes</h3>

    <div class="card">
        <div class="card-body p-0">
            <?php if (count($atividades) > 0): ?>
            <div class="divide-y divide-gray-100">
                <?php foreach ($atividades as $atividade): ?>
                <div class="p-4 hover:bg-gray-50">
                    <div class="flex">
                        <div class="flex-shrink-0 mr-4">
                            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                                <?php if ($atividade['tipo'] === 'acesso'): ?>
                                <i class="fas fa-sign-in-alt text-blue-600"></i>
                                <?php elseif ($atividade['tipo'] === 'documento'): ?>
                                <i class="fas fa-file-alt text-blue-600"></i>
                                <?php elseif ($atividade['tipo'] === 'nota'): ?>
                                <i class="fas fa-chart-line text-blue-600"></i>
                                <?php elseif ($atividade['tipo'] === 'ava'): ?>
                                <i class="fas fa-laptop text-blue-600"></i>
                                <?php else: ?>
                                <i class="fas fa-bell text-blue-600"></i>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div>
                            <p class="text-sm text-gray-900"><?php echo $atividade['descricao']; ?></p>
                            <p class="text-xs text-gray-500 mt-1">
                                <?php echo date('d/m/Y H:i', strtotime($atividade['created_at'])); ?>
                            </p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="text-center py-8">
                <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-history text-gray-400 text-2xl"></i>
                </div>
                <h4 class="font-medium text-gray-900 mb-1">Nenhuma atividade recente</h4>
                <p class="text-sm text-gray-500">Suas atividades aparecerão aqui conforme você utiliza o sistema.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Scripts específicos da página
$page_scripts = '
// Inicializa os gráficos
const ctx = document.getElementById("progressChart");
if (ctx) {
    new Chart(ctx, {
        type: "doughnut",
        data: {
            labels: ["Concluído", "Restante"],
            datasets: [{
                data: [' . $progresso_curso . ', ' . (100 - $progresso_curso) . '],
                backgroundColor: ["#4F46E5", "#E5E7EB"],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            cutout: "75%",
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
}
';

// Inclui o rodapé
include 'includes/footer.php';
?>
