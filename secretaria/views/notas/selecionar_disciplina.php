<!-- Seleção de Disciplina -->
<div class="bg-white rounded-xl shadow-sm p-6">
    <!-- Breadcrumb -->
    <div class="mb-6">
        <nav class="flex" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="notas.php?action=lancar" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600">
                        <i class="fas fa-home mr-2"></i>
                        Lançar Notas
                    </a>
                </li>
                <li>
                    <div class="flex items-center">
                        <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                        <span class="text-sm font-medium text-gray-500"><?php echo htmlspecialchars($curso['nome']); ?></span>
                    </div>
                </li>
                <li>
                    <div class="flex items-center">
                        <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                        <span class="text-sm font-medium text-gray-500"><?php echo htmlspecialchars($turma['nome']); ?></span>
                    </div>
                </li>
                <li aria-current="page">
                    <div class="flex items-center">
                        <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                        <span class="text-sm font-medium text-blue-600">Disciplina</span>
                    </div>
                </li>
            </ol>
        </nav>
    </div>

    <!-- Informações da Turma -->
    <div class="bg-gray-50 rounded-lg p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <h3 class="text-sm font-medium text-gray-700 mb-1">Curso Selecionado</h3>
                <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($curso['nome']); ?></p>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-700 mb-1">Turma Selecionada</h3>
                <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($turma['nome']); ?></p>
            </div>
        </div>
    </div>

    <div class="mb-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-2">Selecionar Disciplina</h2>
        <p class="text-sm text-gray-600">Escolha a disciplina para lançar as notas ou cadastre uma nova disciplina.</p>
    </div>

    <!-- Disciplinas Existentes -->
    <?php if (!empty($disciplinas)): ?>
    <div class="mb-8">
        <h3 class="text-md font-medium text-gray-800 mb-4">
            <i class="fas fa-book mr-2 text-purple-500"></i>
            Disciplinas Disponíveis
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($disciplinas as $disciplina): ?>
            <div class="border border-gray-200 rounded-lg p-4 hover:border-blue-300 hover:shadow-md transition-all cursor-pointer group"
                 onclick="selecionarDisciplina(<?php echo $disciplina['id']; ?>)">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <h4 class="text-sm font-medium text-gray-900 group-hover:text-blue-600">
                            <?php echo htmlspecialchars($disciplina['nome']); ?>
                        </h4>
                    </div>
                    <div class="ml-3">
                        <i class="fas fa-arrow-right text-gray-400 group-hover:text-blue-500"></i>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Cadastrar Nova Disciplina -->
    <div class="border-t border-gray-200 pt-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-md font-medium text-gray-800">
                <i class="fas fa-plus-circle mr-2 text-green-500"></i>
                Cadastrar Nova Disciplina
            </h3>
            <button type="button" 
                    id="btn-toggle-form"
                    onclick="toggleFormDisciplina()"
                    class="inline-flex items-center px-3 py-1 border border-gray-300 rounded-md text-xs font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-plus mr-1"></i>
                <span id="btn-toggle-text">Mostrar Formulário</span>
            </button>
        </div>

        <div id="form-nova-disciplina" class="hidden">
            <form method="POST" action="notas.php" class="space-y-4">
                <input type="hidden" name="action" value="nova_disciplina">
                <input type="hidden" name="curso_id" value="<?php echo $curso_id; ?>">
                <input type="hidden" name="turma_id" value="<?php echo $turma_id; ?>">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="nome" class="block text-sm font-medium text-gray-700 mb-1">
                            Nome da Disciplina <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="nome" 
                               name="nome" 
                               required
                               placeholder="Ex: Matemática Básica"
                               class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    </div>
                    
                    <div>
                        <label for="codigo" class="block text-sm font-medium text-gray-700 mb-1">
                            Código da Disciplina
                        </label>
                        <input type="text" 
                               id="codigo" 
                               name="codigo" 
                               placeholder="Ex: MAT001"
                               class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    </div>
                </div>
                
                <div>
                    <label for="carga_horaria" class="block text-sm font-medium text-gray-700 mb-1">
                        Carga Horária (horas)
                    </label>
                    <input type="number" 
                           id="carga_horaria" 
                           name="carga_horaria" 
                           min="1"
                           placeholder="Ex: 60"
                           class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                </div>
                
                <div class="flex space-x-3 pt-4">
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        <i class="fas fa-save mr-2"></i>
                        Cadastrar e Continuar
                    </button>
                    
                    <button type="button" 
                            onclick="toggleFormDisciplina()"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-times mr-2"></i>
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Botões de Navegação -->
    <div class="flex justify-between items-center pt-6 border-t border-gray-200 mt-6">
        <a href="notas.php?action=lancar" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <i class="fas fa-arrow-left mr-2"></i>
            Voltar
        </a>
        
        <div class="text-xs text-gray-500">
            <i class="fas fa-info-circle mr-1"></i>
            Selecione uma disciplina para continuar
        </div>
    </div>
</div>

<!-- Informações sobre Disciplinas -->
<div class="mt-6 bg-yellow-50 border border-yellow-200 rounded-xl p-4">
    <div class="flex">
        <div class="flex-shrink-0">
            <i class="fas fa-exclamation-triangle text-yellow-400"></i>
        </div>
        <div class="ml-3">
            <h3 class="text-sm font-medium text-yellow-800">Sobre as Disciplinas</h3>
            <div class="mt-2 text-sm text-yellow-700">
                <ul class="list-disc list-inside space-y-1">
                    <li>As disciplinas são vinculadas ao curso e podem ser reutilizadas em outras turmas</li>
                    <li>Se não encontrar a disciplina desejada, você pode cadastrar uma nova</li>
                    <li>O código da disciplina é opcional, mas ajuda na organização</li>
                    <li>A carga horária será usada para cálculos de frequência</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
function selecionarDisciplina(disciplinaId) {
    const url = `notas.php?action=lancar&curso_id=<?php echo $curso_id; ?>&turma_id=<?php echo $turma_id; ?>&disciplina_id=${disciplinaId}`;
    window.location.href = url;
}

function toggleFormDisciplina() {
    const form = document.getElementById('form-nova-disciplina');
    const btnText = document.getElementById('btn-toggle-text');
    const btnIcon = document.querySelector('#btn-toggle-form i');
    
    if (form.classList.contains('hidden')) {
        form.classList.remove('hidden');
        btnText.textContent = 'Ocultar Formulário';
        btnIcon.className = 'fas fa-minus mr-1';
        
        // Foca no primeiro campo
        document.getElementById('nome').focus();
    } else {
        form.classList.add('hidden');
        btnText.textContent = 'Mostrar Formulário';
        btnIcon.className = 'fas fa-plus mr-1';
    }
}

// Adiciona efeito hover nos cards de disciplina
document.addEventListener('DOMContentLoaded', function() {
    const disciplinaCards = document.querySelectorAll('[onclick^="selecionarDisciplina"]');
    
    disciplinaCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.classList.add('bg-blue-50');
        });
        
        card.addEventListener('mouseleave', function() {
            this.classList.remove('bg-blue-50');
        });
    });
});
</script>
