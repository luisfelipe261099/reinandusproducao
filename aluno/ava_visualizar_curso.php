<?php
// Este arquivo é incluído a partir de ava.php
// Variáveis disponíveis: $curso_atual, $matricula, $aluno_id, $curso_id

// Verifica se as variáveis necessárias estão definidas
if (!isset($curso_atual) || !isset($matricula) || !isset($aluno_id) || !isset($curso_id)) {
    echo '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <div class="ml-3">
                <p>Erro ao carregar o curso. Parâmetros inválidos.</p>
            </div>
        </div>
    </div>';
    return;
}

// Obtém os módulos do curso
$modulos = $db->query("SELECT * FROM ava_modulos 
                      WHERE curso_id = ? AND status = 'ativo' 
                      ORDER BY ordem ASC", [$curso_id]);

// Obtém as aulas de cada módulo
$aulas_por_modulo = [];
$total_aulas = 0;
$aulas_concluidas = 0;

foreach ($modulos as $modulo) {
    $aulas = $db->query("SELECT a.* FROM ava_aulas a 
                        WHERE a.modulo_id = ? AND a.status = 'ativo' 
                        ORDER BY a.ordem ASC", [$modulo['id']]);
    
    $aulas_por_modulo[$modulo['id']] = $aulas;
    $total_aulas += count($aulas);
    
    // Verifica quais aulas foram concluídas
    foreach ($aulas as $aula) {
        $progresso_aula = $db->query("SELECT * FROM ava_progresso_aulas 
                                     WHERE matricula_id = ? AND aula_id = ?", 
                                     [$matricula, $aula['id']]);
        
        if (count($progresso_aula) > 0 && $progresso_aula[0]['status'] === 'concluida') {
            $aulas_concluidas++;
        }
    }
}

// Calcula o progresso real
$progresso_real = $total_aulas > 0 ? round(($aulas_concluidas / $total_aulas) * 100) : 0;

// Atualiza o progresso na matrícula se for diferente
if ($progresso_real != $curso_atual['progresso']) {
    $db->query("UPDATE ava_matriculas SET progresso = ? WHERE id = ?", 
               [$progresso_real, $matricula]);
}

// Obtém a próxima aula não concluída
$proxima_aula = null;

foreach ($modulos as $modulo) {
    if ($proxima_aula) break;
    
    foreach ($aulas_por_modulo[$modulo['id']] as $aula) {
        $progresso_aula = $db->query("SELECT * FROM ava_progresso_aulas 
                                     WHERE matricula_id = ? AND aula_id = ?", 
                                     [$matricula, $aula['id']]);
        
        if (count($progresso_aula) === 0 || $progresso_aula[0]['status'] !== 'concluida') {
            $proxima_aula = $aula;
            break;
        }
    }
}
?>

<div class="flex flex-col md:flex-row mb-6">
    <div class="md:w-2/3 pr-0 md:pr-6 mb-6 md:mb-0">
        <a href="ava.php" class="text-indigo-600 hover:text-indigo-800 mb-4 inline-block">
            <i class="fas fa-arrow-left mr-2"></i> Voltar para Meus Cursos
        </a>
        
        <h2 class="text-2xl font-bold text-gray-800 mb-2"><?php echo $curso_atual['titulo']; ?></h2>
        
        <div class="flex items-center mb-4">
            <span class="text-sm text-gray-500 mr-4">
                <i class="fas fa-clock mr-1"></i> <?php echo $curso_atual['carga_horaria']; ?> horas
            </span>
            
            <span class="text-sm text-gray-500 mr-4">
                <i class="fas fa-book mr-1"></i> <?php echo $total_aulas; ?> aulas
            </span>
            
            <span class="text-sm text-gray-500">
                <i class="fas fa-signal mr-1"></i> <?php echo ucfirst($curso_atual['nivel'] ?? 'Básico'); ?>
            </span>
        </div>
        
        <div class="mb-6">
            <div class="flex justify-between text-sm mb-1">
                <span>Progresso do Curso</span>
                <span><?php echo $progresso_real; ?>%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2.5">
                <div class="bg-indigo-600 h-2.5 rounded-full" style="width: <?php echo $progresso_real; ?>%"></div>
            </div>
        </div>
        
        <?php if (!empty($curso_atual['descricao'])): ?>
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">Sobre o Curso</h3>
            <div class="prose max-w-none">
                <?php echo $curso_atual['descricao']; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($proxima_aula): ?>
        <div class="bg-indigo-50 border border-indigo-100 rounded-lg p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">Continue de Onde Parou</h3>
            <div class="flex items-center">
                <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center mr-4">
                    <?php if ($proxima_aula['tipo'] === 'video'): ?>
                    <i class="fas fa-play-circle text-indigo-600 text-xl"></i>
                    <?php elseif ($proxima_aula['tipo'] === 'quiz'): ?>
                    <i class="fas fa-question-circle text-indigo-600 text-xl"></i>
                    <?php else: ?>
                    <i class="fas fa-book-open text-indigo-600 text-xl"></i>
                    <?php endif; ?>
                </div>
                <div class="flex-1">
                    <h4 class="font-medium text-gray-900"><?php echo $proxima_aula['titulo']; ?></h4>
                    <p class="text-sm text-gray-500">
                        <?php 
                        // Obtém o nome do módulo
                        foreach ($modulos as $modulo) {
                            if ($modulo['id'] == $proxima_aula['modulo_id']) {
                                echo $modulo['titulo'];
                                break;
                            }
                        }
                        ?>
                    </p>
                </div>
                <a href="ava.php?curso_id=<?php echo $curso_id; ?>&aula_id=<?php echo $proxima_aula['id']; ?>" class="btn btn-primary">
                    <i class="fas fa-play mr-2"></i> Continuar
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="md:w-1/3">
        <div class="bg-white rounded-lg shadow-sm overflow-hidden sticky top-20">
            <div class="p-4 bg-indigo-600 text-white">
                <h3 class="font-semibold">Conteúdo do Curso</h3>
                <p class="text-xs text-indigo-100 mt-1"><?php echo $aulas_concluidas; ?> de <?php echo $total_aulas; ?> aulas concluídas</p>
            </div>
            
            <div class="divide-y divide-gray-100 max-h-[600px] overflow-y-auto">
                <?php foreach ($modulos as $modulo): ?>
                <div class="modulo">
                    <div class="modulo-header p-4 bg-gray-50 font-medium flex justify-between items-center cursor-pointer" data-modulo-id="<?php echo $modulo['id']; ?>">
                        <div class="modulo-title">
                            <?php echo $modulo['titulo']; ?>
                            <i class="fas fa-chevron-down ml-2 text-gray-400 text-xs transition-transform"></i>
                        </div>
                        
                        <?php
                        // Calcula o progresso do módulo
                        $aulas_modulo = $aulas_por_modulo[$modulo['id']];
                        $total_aulas_modulo = count($aulas_modulo);
                        $aulas_concluidas_modulo = 0;
                        
                        foreach ($aulas_modulo as $aula) {
                            $progresso_aula = $db->query("SELECT * FROM ava_progresso_aulas 
                                                         WHERE matricula_id = ? AND aula_id = ?", 
                                                         [$matricula, $aula['id']]);
                            
                            if (count($progresso_aula) > 0 && $progresso_aula[0]['status'] === 'concluida') {
                                $aulas_concluidas_modulo++;
                            }
                        }
                        
                        $progresso_modulo = $total_aulas_modulo > 0 ? round(($aulas_concluidas_modulo / $total_aulas_modulo) * 100) : 0;
                        ?>
                        
                        <span class="text-xs px-2 py-1 rounded-full <?php echo $progresso_modulo === 100 ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                            <?php echo $progresso_modulo; ?>%
                        </span>
                    </div>
                    
                    <div id="modulo-content-<?php echo $modulo['id']; ?>" class="modulo-content hidden">
                        <ul class="divide-y divide-gray-100">
                            <?php foreach ($aulas_por_modulo[$modulo['id']] as $aula): ?>
                            <?php
                            // Verifica se a aula foi concluída
                            $progresso_aula = $db->query("SELECT * FROM ava_progresso_aulas 
                                                         WHERE matricula_id = ? AND aula_id = ?", 
                                                         [$matricula, $aula['id']]);
                            
                            $aula_concluida = count($progresso_aula) > 0 && $progresso_aula[0]['status'] === 'concluida';
                            ?>
                            <li class="p-4 hover:bg-gray-50">
                                <a href="ava.php?curso_id=<?php echo $curso_id; ?>&aula_id=<?php echo $aula['id']; ?>" class="flex items-center">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center mr-3 <?php echo $aula_concluida ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-600'; ?>">
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
                                    <?php if ($aula_concluida): ?>
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
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Scripts específicos da página
$page_scripts = '
// Toggle módulos
document.querySelectorAll(".modulo-header").forEach(function(header) {
    header.addEventListener("click", function() {
        const moduloId = this.getAttribute("data-modulo-id");
        const content = document.getElementById("modulo-content-" + moduloId);
        const icon = this.querySelector(".modulo-title i");

        content.classList.toggle("hidden");
        icon.classList.toggle("rotate-180");
    });
});

// Abre o módulo da próxima aula automaticamente
document.addEventListener("DOMContentLoaded", function() {
    ' . ($proxima_aula ? 'const proximoModuloId = "' . $proxima_aula['modulo_id'] . '";' : 'const proximoModuloId = null;') . '
    
    if (proximoModuloId) {
        const header = document.querySelector(`.modulo-header[data-modulo-id="${proximoModuloId}"]`);
        if (header) {
            header.click();
        }
    }
});
';
?>
