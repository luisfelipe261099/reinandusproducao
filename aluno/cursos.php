<?php
// Define o título da página
$titulo_pagina = 'Meus Cursos';

// Inclui o cabeçalho
include 'includes/header.php';

// Obtém o ID do aluno
$aluno_id = $_SESSION['aluno_id'];

// Obtém todas as matrículas do aluno
$matriculas = $db->query("SELECT m.*, c.nome as curso_nome, c.tipo as curso_tipo, t.nome as turma_nome, p.nome as polo_nome 
                         FROM matriculas m 
                         INNER JOIN cursos c ON m.curso_id = c.id 
                         LEFT JOIN turmas t ON m.turma_id = t.id 
                         LEFT JOIN polos p ON m.polo_id = p.id 
                         WHERE m.aluno_id = ? 
                         ORDER BY m.data_matricula DESC", [$aluno_id]);

// Obtém os cursos do AVA (se o aluno tiver acesso)
$cursos_ava = [];
if ($aluno['acesso_ava']) {
    $cursos_ava = $db->query("SELECT ac.*, am.progresso, am.id as matricula_id, am.status, am.data_matricula 
                             FROM ava_cursos ac 
                             INNER JOIN ava_matriculas am ON ac.id = am.curso_id 
                             WHERE am.aluno_id = ? 
                             ORDER BY am.data_matricula DESC", [$aluno_id]);
}

// Obtém detalhes do curso selecionado (se houver)
$curso_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$curso_detalhes = null;
$disciplinas = [];
$notas = [];

if ($curso_id > 0) {
    // Verifica se o aluno está matriculado neste curso
    $matricula = null;
    foreach ($matriculas as $m) {
        if ($m['curso_id'] == $curso_id) {
            $matricula = $m;
            break;
        }
    }
    
    if ($matricula) {
        // Obtém os detalhes do curso
        $curso_detalhes = $db->query("SELECT * FROM cursos WHERE id = ?", [$curso_id]);
        $curso_detalhes = $curso_detalhes[0] ?? null;
        
        // Obtém as disciplinas do curso
        $disciplinas = $db->query("SELECT * FROM disciplinas 
                                  WHERE curso_id = ? AND status = 'ativo' 
                                  ORDER BY nome", [$curso_id]);
        
        // Obtém as notas do aluno neste curso
        $notas = $db->query("SELECT nd.*, d.nome as disciplina_nome, d.carga_horaria, d.codigo 
                            FROM notas_disciplinas nd 
                            INNER JOIN disciplinas d ON nd.disciplina_id = d.id 
                            WHERE nd.matricula_id = ? 
                            ORDER BY d.nome", [$matricula['id']]);
    }
}
?>

<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-800 mb-2">Meus Cursos</h2>
    <p class="text-gray-600">Visualize informações sobre seus cursos, disciplinas e progresso acadêmico.</p>
</div>

<?php if (count($matriculas) === 0 && count($cursos_ava) === 0): ?>
<div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6">
    <div class="flex">
        <div class="flex-shrink-0">
            <i class="fas fa-exclamation-circle"></i>
        </div>
        <div class="ml-3">
            <p>Você não está matriculado em nenhum curso no momento.</p>
        </div>
    </div>
</div>
<?php else: ?>

<?php if ($curso_detalhes): ?>
<!-- Detalhes do Curso -->
<div class="mb-6">
    <a href="cursos.php" class="text-indigo-600 hover:text-indigo-800 mb-4 inline-block">
        <i class="fas fa-arrow-left mr-2"></i> Voltar para Meus Cursos
    </a>
    
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?php echo $curso_detalhes['nome']; ?></h3>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                <?php echo $curso_detalhes['tipo'] ?? 'Curso'; ?>
            </span>
        </div>
        <div class="card-body">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <h4 class="text-lg font-semibold text-gray-800 mb-4">Informações do Curso</h4>
                    
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Matrícula:</span>
                            <span class="text-sm font-medium"><?php echo $matricula['id']; ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Turma:</span>
                            <span class="text-sm font-medium"><?php echo $matricula['turma_nome'] ?? 'Não definida'; ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Polo:</span>
                            <span class="text-sm font-medium"><?php echo $matricula['polo_nome'] ?? 'Não definido'; ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Data de Matrícula:</span>
                            <span class="text-sm font-medium"><?php echo date('d/m/Y', strtotime($matricula['data_matricula'])); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Início do Curso:</span>
                            <span class="text-sm font-medium"><?php echo date('d/m/Y', strtotime($matricula['data_inicio'])); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Previsão de Término:</span>
                            <span class="text-sm font-medium"><?php echo date('d/m/Y', strtotime($matricula['data_fim'])); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Status:</span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                <?php 
                                if ($matricula['status'] === 'ativo') {
                                    echo 'bg-green-100 text-green-800';
                                } elseif ($matricula['status'] === 'concluído') {
                                    echo 'bg-blue-100 text-blue-800';
                                } elseif ($matricula['status'] === 'trancado') {
                                    echo 'bg-yellow-100 text-yellow-800';
                                } elseif ($matricula['status'] === 'cancelado') {
                                    echo 'bg-red-100 text-red-800';
                                } else {
                                    echo 'bg-gray-100 text-gray-800';
                                }
                                ?>">
                                <?php echo ucfirst($matricula['status']); ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <div>
                    <h4 class="text-lg font-semibold text-gray-800 mb-4">Progresso do Curso</h4>
                    
                    <?php
                    // Calcula o progresso do curso
                    $total_disciplinas = count($disciplinas);
                    $disciplinas_aprovadas = 0;
                    $disciplinas_reprovadas = 0;
                    $disciplinas_cursando = 0;
                    
                    foreach ($notas as $nota) {
                        if ($nota['situacao'] === 'aprovado') {
                            $disciplinas_aprovadas++;
                        } elseif ($nota['situacao'] === 'reprovado') {
                            $disciplinas_reprovadas++;
                        } else {
                            $disciplinas_cursando++;
                        }
                    }
                    
                    $progresso = $total_disciplinas > 0 ? round(($disciplinas_aprovadas / $total_disciplinas) * 100) : 0;
                    ?>
                    
                    <div class="mb-4">
                        <div class="flex justify-between text-sm mb-1">
                            <span>Progresso Geral</span>
                            <span><?php echo $progresso; ?>%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                            <div class="bg-indigo-600 h-2.5 rounded-full" style="width: <?php echo $progresso; ?>%"></div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-3 gap-4 mb-4">
                        <div class="bg-green-50 rounded-lg p-3 text-center">
                            <div class="text-2xl font-bold text-green-600"><?php echo $disciplinas_aprovadas; ?></div>
                            <div class="text-xs text-gray-600">Aprovadas</div>
                        </div>
                        <div class="bg-yellow-50 rounded-lg p-3 text-center">
                            <div class="text-2xl font-bold text-yellow-600"><?php echo $disciplinas_cursando; ?></div>
                            <div class="text-xs text-gray-600">Cursando</div>
                        </div>
                        <div class="bg-red-50 rounded-lg p-3 text-center">
                            <div class="text-2xl font-bold text-red-600"><?php echo $disciplinas_reprovadas; ?></div>
                            <div class="text-xs text-gray-600">Reprovadas</div>
                        </div>
                    </div>
                    
                    <?php
                    // Calcula a média geral
                    $media_geral = 0;
                    $total_disciplinas_com_nota = 0;
                    
                    foreach ($notas as $nota) {
                        if ($nota['nota'] !== null) {
                            $media_geral += $nota['nota'];
                            $total_disciplinas_com_nota++;
                        }
                    }
                    
                    if ($total_disciplinas_com_nota > 0) {
                        $media_geral = $media_geral / $total_disciplinas_com_nota;
                    }
                    ?>
                    
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Média Geral:</span>
                        <span class="text-sm font-medium"><?php echo number_format($media_geral, 1, ',', '.'); ?></span>
                    </div>
                </div>
            </div>
            
            <h4 class="text-lg font-semibold text-gray-800 mb-4">Disciplinas</h4>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Disciplina
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Código
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Carga Horária
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Nota
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Frequência
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Situação
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (count($disciplinas) > 0): ?>
                        <?php foreach ($disciplinas as $disciplina): ?>
                        <?php
                        // Encontra a nota para esta disciplina
                        $nota_disciplina = null;
                        foreach ($notas as $nota) {
                            if ($nota['disciplina_id'] == $disciplina['id']) {
                                $nota_disciplina = $nota;
                                break;
                            }
                        }
                        ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-medium text-gray-900"><?php echo $disciplina['nome']; ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500"><?php echo $disciplina['codigo'] ?? '-'; ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500"><?php echo $disciplina['carga_horaria']; ?> horas</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($nota_disciplina && $nota_disciplina['nota'] !== null): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium <?php echo $nota_disciplina['nota'] >= 7 ? 'bg-green-100 text-green-800' : ($nota_disciplina['nota'] >= 5 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'); ?>">
                                    <?php echo number_format($nota_disciplina['nota'], 1, ',', '.'); ?>
                                </span>
                                <?php else: ?>
                                <span class="text-sm text-gray-500">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($nota_disciplina && $nota_disciplina['frequencia'] !== null): ?>
                                <span class="text-sm <?php echo $nota_disciplina['frequencia'] >= 75 ? 'text-green-600' : 'text-red-600'; ?>">
                                    <?php echo number_format($nota_disciplina['frequencia'], 1, ',', '.'); ?>%
                                </span>
                                <?php else: ?>
                                <span class="text-sm text-gray-500">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($nota_disciplina): ?>
                                <?php if ($nota_disciplina['situacao'] === 'aprovado'): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Aprovado
                                </span>
                                <?php elseif ($nota_disciplina['situacao'] === 'reprovado'): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    Reprovado
                                </span>
                                <?php else: ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    Cursando
                                </span>
                                <?php endif; ?>
                                <?php else: ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    Não Iniciada
                                </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                Nenhuma disciplina encontrada para este curso.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php else: ?>

<!-- Lista de Cursos -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <?php if (count($matriculas) > 0): ?>
    <div class="card animate-fade-in" style="opacity: 0; transform: translateY(10px);">
        <div class="card-header">
            <h3 class="card-title">Cursos Presenciais</h3>
        </div>
        <div class="card-body p-0">
            <div class="divide-y divide-gray-100">
                <?php foreach ($matriculas as $matricula): ?>
                <div class="p-4 hover:bg-gray-50">
                    <div class="flex items-start">
                        <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center mr-4">
                            <i class="fas fa-graduation-cap text-indigo-600 text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-medium text-gray-900"><?php echo $matricula['curso_nome']; ?></h4>
                            <div class="flex flex-wrap gap-2 mt-1">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                    <?php echo $matricula['curso_tipo'] ?? 'Curso'; ?>
                                </span>
                                
                                <?php if (!empty($matricula['turma_nome'])): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    <?php echo $matricula['turma_nome']; ?>
                                </span>
                                <?php endif; ?>
                                
                                <?php if (!empty($matricula['polo_nome'])): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <?php echo $matricula['polo_nome']; ?>
                                </span>
                                <?php endif; ?>
                                
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                    <?php 
                                    if ($matricula['status'] === 'ativo') {
                                        echo 'bg-green-100 text-green-800';
                                    } elseif ($matricula['status'] === 'concluído') {
                                        echo 'bg-blue-100 text-blue-800';
                                    } elseif ($matricula['status'] === 'trancado') {
                                        echo 'bg-yellow-100 text-yellow-800';
                                    } elseif ($matricula['status'] === 'cancelado') {
                                        echo 'bg-red-100 text-red-800';
                                    } else {
                                        echo 'bg-gray-100 text-gray-800';
                                    }
                                    ?>">
                                    <?php echo ucfirst($matricula['status']); ?>
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 mt-2">
                                <i class="fas fa-calendar-alt mr-1"></i> Matrícula: <?php echo date('d/m/Y', strtotime($matricula['data_matricula'])); ?>
                            </p>
                        </div>
                        <div>
                            <a href="cursos.php?id=<?php echo $matricula['curso_id']; ?>" class="btn btn-outline btn-sm">
                                <i class="fas fa-eye mr-1"></i> Detalhes
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($aluno['acesso_ava'] && count($cursos_ava) > 0): ?>
    <div class="card animate-fade-in" style="opacity: 0; transform: translateY(10px);">
        <div class="card-header">
            <h3 class="card-title">Cursos Online (AVA)</h3>
        </div>
        <div class="card-body p-0">
            <div class="divide-y divide-gray-100">
                <?php foreach ($cursos_ava as $curso): ?>
                <div class="p-4 hover:bg-gray-50">
                    <div class="flex items-start">
                        <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center mr-4">
                            <i class="fas fa-laptop text-blue-600 text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-medium text-gray-900"><?php echo $curso['titulo']; ?></h4>
                            <div class="flex flex-wrap gap-2 mt-1">
                                <?php if (!empty($curso['categoria'])): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                    <?php echo $curso['categoria']; ?>
                                </span>
                                <?php endif; ?>
                                
                                <?php if (!empty($curso['nivel'])): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                    <?php echo ucfirst($curso['nivel']); ?>
                                </span>
                                <?php endif; ?>
                                
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                    <?php 
                                    if ($curso['status'] === 'ativo') {
                                        echo 'bg-green-100 text-green-800';
                                    } elseif ($curso['status'] === 'concluido') {
                                        echo 'bg-blue-100 text-blue-800';
                                    } elseif ($curso['status'] === 'trancado') {
                                        echo 'bg-yellow-100 text-yellow-800';
                                    } elseif ($curso['status'] === 'cancelado') {
                                        echo 'bg-red-100 text-red-800';
                                    } else {
                                        echo 'bg-gray-100 text-gray-800';
                                    }
                                    ?>">
                                    <?php echo ucfirst($curso['status']); ?>
                                </span>
                            </div>
                            <div class="mt-2">
                                <div class="flex justify-between text-xs mb-1">
                                    <span>Progresso</span>
                                    <span><?php echo $curso['progresso']; ?>%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-1.5">
                                    <div class="bg-blue-600 h-1.5 rounded-full" style="width: <?php echo $curso['progresso']; ?>%"></div>
                                </div>
                            </div>
                        </div>
                        <div>
                            <a href="ava.php?curso_id=<?php echo $curso['id']; ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-play-circle mr-1"></i> Acessar
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php endif; ?>

<?php
// Inclui o rodapé
include 'includes/footer.php';
?>
