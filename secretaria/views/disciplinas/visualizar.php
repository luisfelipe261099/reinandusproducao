<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="p-6">
        <!-- Cabeçalho com informações principais -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
            <div>
                <h2 class="text-xl font-bold text-gray-800">
                    <?php echo htmlspecialchars($disciplina['nome']); ?>
                    <?php if (!empty($disciplina['codigo'])): ?>
                    <span class="text-sm text-gray-500 ml-2">(Código: <?php echo htmlspecialchars($disciplina['codigo']); ?>)</span>
                    <?php endif; ?>
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    <?php if (!empty($disciplina['id_legado'])): ?>
                    ID Legado: <?php echo htmlspecialchars($disciplina['id_legado']); ?> |
                    <?php endif; ?>
                    Criada em: <?php echo date('d/m/Y', strtotime($disciplina['created_at'])); ?>
                    <?php if ($disciplina['created_at'] != $disciplina['updated_at']): ?>
                    | Atualizada em: <?php echo date('d/m/Y', strtotime($disciplina['updated_at'])); ?>
                    <?php endif; ?>
                </p>
            </div>

            <div class="mt-4 md:mt-0">
                <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full
                    <?php echo $disciplina['status'] === 'ativo' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                    <?php echo $disciplina['status'] === 'ativo' ? 'Ativo' : 'Inativo'; ?>
                </span>
            </div>
        </div>

        <!-- Informações do Curso -->
        <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">Informações do Curso</h3>
            <div class="bg-gray-50 rounded-lg p-4">
                <h4 class="text-lg font-medium text-gray-900">
                    <?php if (isset($disciplina['curso_nome'])): ?>
                    <a href="cursos.php?action=visualizar&id=<?php echo $disciplina['curso_id']; ?>" class="hover:text-blue-600">
                        <?php echo htmlspecialchars($disciplina['curso_nome']); ?>
                    </a>
                    <?php else: ?>
                    <span class="text-gray-500">Curso não encontrado</span>
                    <?php endif; ?>
                </h4>
                <?php if (!empty($disciplina['periodo'])): ?>
                <div class="mt-2 flex items-center text-sm text-gray-500">
                    <i class="fas fa-calendar-alt mr-1.5 text-gray-400"></i>
                    Período: <?php echo htmlspecialchars($disciplina['periodo']); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Informações do Professor -->
        <?php if (!empty($disciplina['professor_nome'])): ?>
        <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">Professor Responsável</h3>
            <div class="bg-gray-50 rounded-lg p-4">
                <h4 class="text-lg font-medium text-gray-900">
                    <a href="professores.php?action=visualizar&id=<?php echo $disciplina['professor_padrao_id']; ?>" class="hover:text-blue-600">
                        <?php echo htmlspecialchars($disciplina['professor_nome']); ?>
                    </a>
                </h4>
            </div>
        </div>
        <?php endif; ?>

        <!-- Carga Horária -->
        <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">Carga Horária</h3>
            <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-2xl font-bold text-gray-900">
                    <?php echo !empty($disciplina['carga_horaria']) ? $disciplina['carga_horaria'] : '0'; ?> horas
                </p>
            </div>
        </div>

        <!-- Ementa -->
        <?php if (!empty($disciplina['ementa'])): ?>
        <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">Ementa</h3>
            <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-gray-700 whitespace-pre-line"><?php echo nl2br(htmlspecialchars($disciplina['ementa'])); ?></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Objetivos -->
        <?php if (!empty($disciplina['objetivos'])): ?>
        <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">Objetivos</h3>
            <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-gray-700 whitespace-pre-line"><?php echo nl2br(htmlspecialchars($disciplina['objetivos'])); ?></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Bibliografia -->
        <?php if (!empty($disciplina['bibliografia'])): ?>
        <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">Bibliografia</h3>
            <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-gray-700 whitespace-pre-line"><?php echo nl2br(htmlspecialchars($disciplina['bibliografia'])); ?></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Botões de Ação -->
        <div class="mt-8 flex flex-col sm:flex-row sm:justify-end space-y-3 sm:space-y-0 sm:space-x-3">
            <a href="disciplinas_novo.php?action=listar" class="btn-secondary">
                <i class="fas fa-arrow-left mr-2"></i> Voltar para a Lista
            </a>
            <a href="disciplinas_novo.php?action=editar&id=<?php echo $disciplina['id']; ?>" class="btn-primary">
                <i class="fas fa-edit mr-2"></i> Editar Disciplina
            </a>
        </div>
    </div>
</div>
