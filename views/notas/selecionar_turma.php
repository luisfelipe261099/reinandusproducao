<!-- Seleção de Curso e Turma para Lançamento de Notas -->
<div class="bg-white rounded-xl shadow-sm p-6">
    <div class="mb-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-2">Selecionar Curso e Turma</h2>
        <p class="text-sm text-gray-600">Escolha o curso e a turma para lançar as notas dos alunos.</p>
    </div>

    <form method="GET" action="notas.php" class="space-y-6">
        <input type="hidden" name="action" value="lancar">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Seleção de Curso -->
            <div>
                <label for="curso_id" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-graduation-cap mr-2 text-blue-500"></i>
                    Curso
                </label>
                <select id="curso_id" 
                        name="curso_id" 
                        required
                        onchange="carregarTurmas(this.value, 'turma_id')"
                        class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    <option value="">Selecione um curso...</option>
                    <?php foreach ($cursos as $curso): ?>
                    <option value="<?php echo $curso['id']; ?>" <?php echo ($_GET['curso_id'] ?? '') == $curso['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($curso['nome']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Seleção de Turma -->
            <div>
                <label for="turma_id" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-users mr-2 text-green-500"></i>
                    Turma
                </label>
                <select id="turma_id" 
                        name="turma_id" 
                        required
                        <?php echo empty($_GET['curso_id']) ? 'disabled' : ''; ?>
                        class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    <option value="">
                        <?php echo empty($_GET['curso_id']) ? 'Selecione um curso primeiro...' : 'Carregando turmas...'; ?>
                    </option>
                </select>
            </div>
        </div>
        
        <!-- Botões de Ação -->
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center space-y-3 sm:space-y-0 pt-6 border-t border-gray-200">
            <div class="flex space-x-3">
                <button type="submit" 
                        id="btn-continuar"
                        disabled
                        class="inline-flex items-center px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:bg-gray-400 disabled:cursor-not-allowed">
                    <i class="fas fa-arrow-right mr-2"></i>
                    Continuar
                </button>
                
                <a href="notas.php" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Voltar
                </a>
            </div>
            
            <div class="text-xs text-gray-500">
                <i class="fas fa-info-circle mr-1"></i>
                Após selecionar, você escolherá a disciplina
            </div>
        </div>
    </form>
</div>

<!-- Informações Adicionais -->
<div class="mt-6 bg-blue-50 border border-blue-200 rounded-xl p-4">
    <div class="flex">
        <div class="flex-shrink-0">
            <i class="fas fa-lightbulb text-blue-400"></i>
        </div>
        <div class="ml-3">
            <h3 class="text-sm font-medium text-blue-800">Como funciona o lançamento de notas</h3>
            <div class="mt-2 text-sm text-blue-700">
                <ol class="list-decimal list-inside space-y-1">
                    <li>Selecione o curso e a turma desejada</li>
                    <li>Escolha a disciplina ou cadastre uma nova se necessário</li>
                    <li>Lance as notas e frequências para todos os alunos da turma</li>
                    <li>As notas podem ser editadas posteriormente se necessário</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Turmas Recentes (se houver) -->
<?php
// Buscar turmas com atividade recente de notas
$turmas_recentes = $db->fetchAll("
    SELECT DISTINCT c.nome as curso_nome, t.id as turma_id, t.nome as turma_nome, 
           COUNT(nd.id) as total_notas,
           MAX(nd.updated_at) as ultima_atividade
    FROM turmas t
    JOIN cursos c ON t.curso_id = c.id
    LEFT JOIN matriculas m ON t.id = m.turma_id
    LEFT JOIN notas_disciplinas nd ON m.id = nd.matricula_id
    WHERE t.status IN ('em_andamento', 'planejada') 
    AND c.status = 'ativo'
    AND nd.updated_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY t.id, c.nome, t.nome
    ORDER BY ultima_atividade DESC
    LIMIT 5
") ?: [];
?>

<?php if (!empty($turmas_recentes)): ?>
<div class="mt-6 bg-white rounded-xl shadow-sm p-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">
        <i class="fas fa-clock mr-2 text-orange-500"></i>
        Turmas com Atividade Recente
    </h3>
    
    <div class="space-y-3">
        <?php foreach ($turmas_recentes as $turma): ?>
        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
            <div>
                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($turma['turma_nome']); ?></div>
                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($turma['curso_nome']); ?></div>
            </div>
            <div class="flex items-center space-x-4">
                <div class="text-xs text-gray-500">
                    <span class="font-medium"><?php echo $turma['total_notas']; ?></span> notas
                </div>
                <div class="text-xs text-gray-500">
                    <?php echo date('d/m/Y', strtotime($turma['ultima_atividade'])); ?>
                </div>
                <a href="notas.php?action=lancar&turma_id=<?php echo $turma['turma_id']; ?>" 
                   class="inline-flex items-center px-3 py-1 border border-transparent text-xs leading-4 font-medium rounded text-blue-700 bg-blue-100 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-plus mr-1"></i>
                    Lançar
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const cursoSelect = document.getElementById('curso_id');
    const turmaSelect = document.getElementById('turma_id');
    const btnContinuar = document.getElementById('btn-continuar');
    
    // Carrega turmas se já tiver curso selecionado
    if (cursoSelect.value) {
        carregarTurmas(cursoSelect.value, 'turma_id');
    }
    
    // Monitora mudanças nos selects para habilitar/desabilitar botão
    function verificarFormulario() {
        const cursoSelecionado = cursoSelect.value;
        const turmaSelecionada = turmaSelect.value;
        
        if (cursoSelecionado && turmaSelecionada) {
            btnContinuar.disabled = false;
        } else {
            btnContinuar.disabled = true;
        }
    }
    
    cursoSelect.addEventListener('change', function() {
        turmaSelect.disabled = !this.value;
        if (!this.value) {
            turmaSelect.innerHTML = '<option value="">Selecione um curso primeiro...</option>';
        }
        verificarFormulario();
    });
    
    turmaSelect.addEventListener('change', verificarFormulario);
    
    // Verifica estado inicial
    verificarFormulario();
});

// Função global para carregar turmas (reutilizada do arquivo principal)
function carregarTurmas(cursoId, turmaSelectId) {
    const turmaSelect = document.getElementById(turmaSelectId);
    
    if (!turmaSelect) return;
    
    // Limpa as opções
    turmaSelect.innerHTML = '<option value="">Carregando...</option>';
    
    if (!cursoId) {
        turmaSelect.innerHTML = '<option value="">Selecione um curso primeiro</option>';
        turmaSelect.disabled = true;
        return;
    }
    
    fetch(`notas.php?action=ajax_turmas&curso_id=${cursoId}`)
        .then(response => response.json())
        .then(data => {
            turmaSelect.innerHTML = '<option value="">Selecione uma turma</option>';
            data.forEach(turma => {
                const selected = '<?php echo $_GET['turma_id'] ?? ''; ?>' == turma.id ? 'selected' : '';
                turmaSelect.innerHTML += `<option value="${turma.id}" ${selected}>${turma.nome}</option>`;
            });
            turmaSelect.disabled = false;
            
            // Dispara evento para verificar formulário
            turmaSelect.dispatchEvent(new Event('change'));
        })
        .catch(error => {
            console.error('Erro ao carregar turmas:', error);
            turmaSelect.innerHTML = '<option value="">Erro ao carregar turmas</option>';
            turmaSelect.disabled = true;
        });
}
</script>
