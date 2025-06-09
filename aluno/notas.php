<?php
// Define o título da página
$titulo_pagina = 'Minhas Notas';

// Inclui o cabeçalho
include 'includes/header.php';

// Obtém o ID do aluno
$aluno_id = $_SESSION['aluno_id'];

// Obtém a matrícula do aluno
$matricula = $db->query("SELECT * FROM matriculas WHERE aluno_id = ? AND status = 'ativo' ORDER BY data_matricula DESC LIMIT 1", [$aluno_id]);
$matricula = $matricula[0] ?? null;

// Obtém o curso do aluno
$curso = null;
if ($matricula) {
    $curso = $db->query("SELECT * FROM cursos WHERE id = ?", [$matricula['curso_id']]);
    $curso = $curso[0] ?? null;
}

// Obtém a turma do aluno
$turma = null;
if ($matricula && $matricula['turma_id']) {
    $turma = $db->query("SELECT * FROM turmas WHERE id = ?", [$matricula['turma_id']]);
    $turma = $turma[0] ?? null;
}

// Obtém as disciplinas do curso
$disciplinas = [];
if ($curso) {
    $disciplinas = $db->query("SELECT * FROM disciplinas WHERE curso_id = ? AND status = 'ativo' ORDER BY nome", [$curso['id']]);
}

// Obtém as notas do aluno
$notas = [];
if ($matricula) {
    $notas = $db->query("SELECT nd.*, d.nome as disciplina_nome, d.carga_horaria, d.codigo 
                        FROM notas_disciplinas nd 
                        INNER JOIN disciplinas d ON nd.disciplina_id = d.id 
                        WHERE nd.matricula_id = ? 
                        ORDER BY d.nome", [$matricula['id']]);
}

// Calcula a média geral do aluno
$media_geral = 0;
$total_disciplinas_com_nota = 0;
$total_aprovadas = 0;
$total_reprovadas = 0;
$total_cursando = 0;

if (count($notas) > 0) {
    $soma_notas = 0;
    
    foreach ($notas as $nota) {
        if ($nota['nota'] !== null) {
            $soma_notas += $nota['nota'];
            $total_disciplinas_com_nota++;
            
            if ($nota['situacao'] === 'aprovado') {
                $total_aprovadas++;
            } elseif ($nota['situacao'] === 'reprovado') {
                $total_reprovadas++;
            } else {
                $total_cursando++;
            }
        } else {
            $total_cursando++;
        }
    }
    
    if ($total_disciplinas_com_nota > 0) {
        $media_geral = $soma_notas / $total_disciplinas_com_nota;
    }
}

// Obtém as notas do AVA (se o aluno tiver acesso)
$notas_ava = [];
if ($aluno['acesso_ava']) {
    $notas_ava = $db->query("SELECT am.*, ac.titulo as curso_titulo 
                            FROM ava_matriculas am 
                            INNER JOIN ava_cursos ac ON am.curso_id = ac.id 
                            WHERE am.aluno_id = ? AND am.nota_final IS NOT NULL 
                            ORDER BY am.data_matricula DESC", [$aluno_id]);
}
?>

<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-800 mb-2">Minhas Notas</h2>
    <p class="text-gray-600">Acompanhe seu desempenho acadêmico e notas em todas as disciplinas.</p>
</div>

<?php if (!$matricula || !$curso): ?>
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

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="card animate-fade-in" style="opacity: 0; transform: translateY(10px);">
        <div class="p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center mr-4">
                    <i class="fas fa-graduation-cap text-indigo-600 text-xl"></i>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">Média Geral</h3>
                    <p class="text-3xl font-bold text-indigo-600"><?php echo number_format($media_geral, 1, ',', '.'); ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card animate-fade-in" style="opacity: 0; transform: translateY(10px);">
        <div class="p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center mr-4">
                    <i class="fas fa-check-circle text-green-600 text-xl"></i>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">Disciplinas Aprovadas</h3>
                    <p class="text-3xl font-bold text-green-600"><?php echo $total_aprovadas; ?> <span class="text-sm text-gray-500">de <?php echo count($disciplinas); ?></span></p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card animate-fade-in" style="opacity: 0; transform: translateY(10px);">
        <div class="p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center mr-4">
                    <i class="fas fa-book text-blue-600 text-xl"></i>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">Disciplinas Cursando</h3>
                    <p class="text-3xl font-bold text-blue-600"><?php echo $total_cursando; ?> <span class="text-sm text-gray-500">de <?php echo count($disciplinas); ?></span></p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-8 animate-fade-in" style="opacity: 0; transform: translateY(10px);">
    <div class="card-header">
        <h3 class="card-title">Notas por Disciplina</h3>
        <div class="text-sm text-gray-500">
            <?php echo $curso['nome']; ?> <?php echo $turma ? ' - ' . $turma['nome'] : ''; ?>
        </div>
    </div>
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
                <?php if (count($notas) > 0): ?>
                <?php foreach ($notas as $nota): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="font-medium text-gray-900"><?php echo $nota['disciplina_nome']; ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-500"><?php echo $nota['codigo'] ?? '-'; ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-500"><?php echo $nota['carga_horaria']; ?> horas</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php if ($nota['nota'] !== null): ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium <?php echo $nota['nota'] >= 7 ? 'bg-green-100 text-green-800' : ($nota['nota'] >= 5 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'); ?>">
                            <?php echo number_format($nota['nota'], 1, ',', '.'); ?>
                        </span>
                        <?php else: ?>
                        <span class="text-sm text-gray-500">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php if ($nota['frequencia'] !== null): ?>
                        <span class="text-sm <?php echo $nota['frequencia'] >= 75 ? 'text-green-600' : 'text-red-600'; ?>">
                            <?php echo number_format($nota['frequencia'], 1, ',', '.'); ?>%
                        </span>
                        <?php else: ?>
                        <span class="text-sm text-gray-500">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php if ($nota['situacao'] === 'aprovado'): ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            Aprovado
                        </span>
                        <?php elseif ($nota['situacao'] === 'reprovado'): ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            Reprovado
                        </span>
                        <?php else: ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            Cursando
                        </span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php else: ?>
                <tr>
                    <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                        Nenhuma nota registrada ainda.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($aluno['acesso_ava'] && count($notas_ava) > 0): ?>
<div class="card animate-fade-in" style="opacity: 0; transform: translateY(10px);">
    <div class="card-header">
        <h3 class="card-title">Notas dos Cursos Online (AVA)</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Curso
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Data de Conclusão
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Progresso
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Nota Final
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Certificado
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($notas_ava as $nota_ava): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="font-medium text-gray-900"><?php echo $nota_ava['curso_titulo']; ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php if ($nota_ava['data_conclusao']): ?>
                        <div class="text-sm text-gray-500"><?php echo date('d/m/Y', strtotime($nota_ava['data_conclusao'])); ?></div>
                        <?php else: ?>
                        <div class="text-sm text-gray-500">Em andamento</div>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="w-full bg-gray-200 rounded-full h-2.5 w-32">
                            <div class="bg-indigo-600 h-2.5 rounded-full" style="width: <?php echo $nota_ava['progresso']; ?>%"></div>
                        </div>
                        <div class="text-xs text-gray-500 mt-1"><?php echo $nota_ava['progresso']; ?>%</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php if ($nota_ava['nota_final'] !== null): ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium <?php echo $nota_ava['nota_final'] >= 7 ? 'bg-green-100 text-green-800' : ($nota_ava['nota_final'] >= 5 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'); ?>">
                            <?php echo number_format($nota_ava['nota_final'], 1, ',', '.'); ?>
                        </span>
                        <?php else: ?>
                        <span class="text-sm text-gray-500">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php if ($nota_ava['certificado_emitido']): ?>
                        <a href="certificados.php?id=<?php echo $nota_ava['id']; ?>" class="text-indigo-600 hover:text-indigo-900">
                            <i class="fas fa-certificate mr-1"></i> Ver Certificado
                        </a>
                        <?php else: ?>
                        <span class="text-sm text-gray-500">Não disponível</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php endif; ?>

<?php
// Inclui o rodapé
include 'includes/footer.php';
?>
