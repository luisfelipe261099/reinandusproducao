<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="p-6 border-b border-gray-200">
        <h2 class="text-xl font-bold text-gray-800">Cadastro Rápido de Disciplinas</h2>
        <p class="text-sm text-gray-600 mt-1">Preencha apenas os campos essenciais para agilizar o cadastro</p>
    </div>

    <form action="disciplinas.php?action=salvar" method="post" class="p-6" id="form-disciplina">
        <?php if (isset($disciplina['id'])): ?>
        <input type="hidden" name="id" value="<?php echo $disciplina['id']; ?>">
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Nome -->
            <div class="col-span-1 md:col-span-2">
                <label for="nome" class="block text-sm font-medium text-gray-700 mb-1">Nome da Disciplina *</label>
                <input type="text" name="nome" id="nome" value="<?php echo isset($disciplina['nome']) ? htmlspecialchars($disciplina['nome']) : ''; ?>" required class="form-input w-full" placeholder="Ex: Matemática Aplicada">
            </div>

            <!-- Curso -->
            <div>
                <label for="curso_id" class="block text-sm font-medium text-gray-700 mb-1">Curso *</label>
                <div class="flex gap-2">
                    <?php if (empty($cursos)): ?>
                    <div class="flex-1">
                        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-3 mb-2">
                            <p class="text-sm text-yellow-700">
                                Não há cursos cadastrados. <a href="cursos.php?action=novo" class="font-medium underline">Cadastre um curso</a> primeiro.
                            </p>
                        </div>
                        <select name="curso_id" id="curso_id" class="form-select w-full" required disabled>
                            <option value="">Nenhum curso disponível</option>
                        </select>
                    </div>
                    <?php else: ?>
                    <select name="curso_id" id="curso_id" class="form-select flex-1" required>
                        <option value="">Selecione um curso...</option>
                        <?php foreach ($cursos as $curso): ?>
                        <option value="<?php echo $curso['id']; ?>" <?php echo isset($disciplina['curso_id']) && $disciplina['curso_id'] == $curso['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($curso['nome']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" id="fixar-curso" class="btn-secondary text-sm px-3 py-2" title="Fixar curso para próximos cadastros">
                        <i class="fas fa-thumbtack"></i>
                    </button>
                    <?php endif; ?>
                </div>
                <div id="curso-fixado-info" class="hidden mt-2 p-2 bg-blue-50 border border-blue-200 rounded text-sm text-blue-700">
                    <i class="fas fa-info-circle mr-1"></i>
                    Curso fixado para próximos cadastros. <button type="button" id="desfixar-curso" class="underline">Desfixar</button>
                </div>
            </div>

            <!-- Carga Horária -->
            <div>
                <label for="carga_horaria" class="block text-sm font-medium text-gray-700 mb-1">Carga Horária (horas) *</label>
                <input type="number" name="carga_horaria" id="carga_horaria" value="<?php echo isset($disciplina['carga_horaria']) ? $disciplina['carga_horaria'] : '60'; ?>" min="1" required class="form-input w-full" placeholder="Ex: 60">
            </div>

            <!-- Status -->
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" id="status" class="form-select w-full">
                    <option value="ativo" <?php echo isset($disciplina['status']) && $disciplina['status'] === 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                    <option value="inativo" <?php echo isset($disciplina['status']) && $disciplina['status'] === 'inativo' ? 'selected' : ''; ?>>Inativo</option>
                </select>
            </div>

            <!-- Período -->
            <div>
                <label for="periodo" class="block text-sm font-medium text-gray-700 mb-1">Período</label>
                <input type="text" name="periodo" id="periodo" value="<?php echo isset($disciplina['periodo']) ? htmlspecialchars($disciplina['periodo']) : ''; ?>" class="form-input w-full" placeholder="Ex: 1º Período">
            </div>

            <!-- Professor -->
            <div>
                <label for="professor_padrao_id" class="block text-sm font-medium text-gray-700 mb-1">Professor</label>
                <div class="flex gap-2">
                    <select name="professor_padrao_id" id="professor_padrao_id" class="form-select flex-1">
                        <option value="">Selecione um professor...</option>
                        <?php foreach ($professores as $professor): ?>
                        <option value="<?php echo $professor['id']; ?>" <?php echo isset($disciplina['professor_padrao_id']) && $disciplina['professor_padrao_id'] == $professor['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($professor['nome']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" id="novo-professor-btn" class="btn-secondary text-sm px-3 py-2" title="Cadastrar novo professor">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>

            <!-- ID Legado -->
            <div>
                <label for="id_legado" class="block text-sm font-medium text-gray-700 mb-1">ID Legado</label>
                <input type="text" name="id_legado" id="id_legado" value="<?php echo isset($disciplina['id_legado']) ? htmlspecialchars($disciplina['id_legado']) : ''; ?>" class="form-input w-full" placeholder="ID do sistema anterior">
            </div>

        </div>

        <!-- Seção de Turmas -->
        <?php if (!empty($turmas)): ?>
        <div class="mt-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Associar a Turmas</h3>
            <div class="bg-gray-50 p-4 rounded-lg">
                <p class="text-sm text-gray-600 mb-3">Selecione as turmas onde esta disciplina será ministrada:</p>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    <?php foreach ($turmas as $turma): ?>
                    <label class="flex items-center">
                        <input type="checkbox"
                               name="turmas[]"
                               value="<?php echo $turma['id']; ?>"
                               <?php echo (isset($disciplina['turmas_selecionadas']) && in_array($turma['id'], $disciplina['turmas_selecionadas'])) ? 'checked' : ''; ?>
                               class="mr-2 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-gray-700"><?php echo htmlspecialchars($turma['nome']); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
                <div class="mt-3 text-xs text-gray-500">
                    <i class="fas fa-info-circle mr-1"></i>
                    A disciplina será automaticamente vinculada às turmas selecionadas com status "planejada".
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="mt-6 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <button type="button" id="limpar-form" class="btn-outline text-sm">
                    <i class="fas fa-eraser mr-1"></i> Limpar Formulário
                </button>
                <?php if (!isset($disciplina['id'])): ?>
                <label class="flex items-center">
                    <input type="checkbox" id="continuar-cadastrando" class="mr-2">
                    <span class="text-sm text-gray-600">Continuar cadastrando após salvar</span>
                </label>
                <?php endif; ?>
            </div>
            <div class="flex items-center space-x-3">
                <a href="disciplinas.php" class="btn-secondary">Cancelar</a>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save mr-1"></i>
                    <?php echo isset($disciplina['id']) ? 'Atualizar Disciplina' : 'Salvar Disciplina'; ?>
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Modal para Cadastro Rápido de Professor -->
<div id="modal-professor" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Cadastrar Novo Professor</h3>
            </div>
            <form id="form-professor" class="p-6">
                <div class="space-y-4">
                    <div>
                        <label for="professor-nome" class="block text-sm font-medium text-gray-700 mb-1">Nome *</label>
                        <input type="text" id="professor-nome" name="nome" required class="form-input w-full" placeholder="Nome completo do professor">
                    </div>
                    <div>
                        <label for="professor-email" class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                        <input type="email" id="professor-email" name="email" class="form-input w-full" placeholder="email@exemplo.com">
                    </div>
                    <div>
                        <label for="professor-formacao" class="block text-sm font-medium text-gray-700 mb-1">Formação</label>
                        <input type="text" id="professor-formacao" name="formacao" class="form-input w-full" placeholder="Ex: Mestrado em Matemática">
                    </div>
                </div>
                <div class="mt-6 flex items-center justify-end space-x-3">
                    <button type="button" id="cancelar-professor" class="btn-secondary">Cancelar</button>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-plus mr-1"></i> Cadastrar Professor
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Elementos do formulário
    const formDisciplina = document.getElementById('form-disciplina');
    const cursoSelect = document.getElementById('curso_id');
    const fixarCursoBtn = document.getElementById('fixar-curso');
    const desfixarCursoBtn = document.getElementById('desfixar-curso');
    const cursoFixadoInfo = document.getElementById('curso-fixado-info');
    const novoProfessorBtn = document.getElementById('novo-professor-btn');
    const modalProfessor = document.getElementById('modal-professor');
    const formProfessor = document.getElementById('form-professor');
    const cancelarProfessorBtn = document.getElementById('cancelar-professor');
    const limparFormBtn = document.getElementById('limpar-form');
    const continuarCadastrandoCheck = document.getElementById('continuar-cadastrando');
    const professorSelect = document.getElementById('professor_padrao_id');

    // Gerenciamento de curso fixado
    let cursoFixado = localStorage.getItem('disciplina_curso_fixado');

    if (cursoFixado && cursoSelect) {
        cursoSelect.value = cursoFixado;
        cursoFixadoInfo.classList.remove('hidden');
    }

    if (fixarCursoBtn) {
        fixarCursoBtn.addEventListener('click', function() {
            const cursoSelecionado = cursoSelect.value;
            if (cursoSelecionado) {
                localStorage.setItem('disciplina_curso_fixado', cursoSelecionado);
                cursoFixadoInfo.classList.remove('hidden');
            } else {
                alert('Selecione um curso primeiro');
            }
        });
    }

    if (desfixarCursoBtn) {
        desfixarCursoBtn.addEventListener('click', function() {
            localStorage.removeItem('disciplina_curso_fixado');
            cursoFixadoInfo.classList.add('hidden');
        });
    }

    // Modal de cadastro de professor
    if (novoProfessorBtn) {
        novoProfessorBtn.addEventListener('click', function() {
            modalProfessor.classList.remove('hidden');
            document.getElementById('professor-nome').focus();
        });
    }

    if (cancelarProfessorBtn) {
        cancelarProfessorBtn.addEventListener('click', function() {
            modalProfessor.classList.add('hidden');
            formProfessor.reset();
        });
    }

    // Fechar modal ao clicar fora
    modalProfessor.addEventListener('click', function(e) {
        if (e.target === modalProfessor) {
            modalProfessor.classList.add('hidden');
            formProfessor.reset();
        }
    });

    // Cadastro de professor via AJAX
    if (formProfessor) {
        formProfessor.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(formProfessor);

            fetch('disciplinas.php?action=cadastrar_professor', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Adiciona o novo professor ao select
                    const option = document.createElement('option');
                    option.value = data.professor.id;
                    option.textContent = data.professor.nome;
                    option.selected = true;
                    professorSelect.appendChild(option);

                    // Fecha o modal e limpa o formulário
                    modalProfessor.classList.add('hidden');
                    formProfessor.reset();

                    // Feedback visual
                    const feedback = document.createElement('div');
                    feedback.className = 'fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded shadow-lg z-50';
                    feedback.textContent = 'Professor cadastrado com sucesso!';
                    document.body.appendChild(feedback);

                    setTimeout(() => {
                        feedback.remove();
                    }, 3000);
                } else {
                    alert('Erro ao cadastrar professor: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao cadastrar professor');
            });
        });
    }

    // Limpar formulário
    if (limparFormBtn) {
        limparFormBtn.addEventListener('click', function() {
            if (confirm('Tem certeza que deseja limpar todos os campos?')) {
                formDisciplina.reset();

                // Restaura curso fixado se houver
                if (cursoFixado) {
                    cursoSelect.value = cursoFixado;
                }
            }
        });
    }

    // Submissão do formulário principal
    if (formDisciplina) {
        formDisciplina.addEventListener('submit', function(e) {
            // Se a opção "continuar cadastrando" estiver marcada
            if (continuarCadastrandoCheck && continuarCadastrandoCheck.checked) {
                // Permite o envio normal, mas prepara para limpar o formulário após o sucesso
                setTimeout(() => {
                    // Verifica se houve redirecionamento (indicando sucesso)
                    if (window.location.href.includes('action=visualizar')) {
                        // Volta para o formulário de nova disciplina
                        window.location.href = 'disciplinas.php?action=nova';
                    }
                }, 100);
            }
        });
    }

    // Atalhos de teclado
    document.addEventListener('keydown', function(e) {
        // Ctrl+S para salvar
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            formDisciplina.submit();
        }

        // Escape para fechar modal
        if (e.key === 'Escape' && !modalProfessor.classList.contains('hidden')) {
            modalProfessor.classList.add('hidden');
            formProfessor.reset();
        }
    });
});
</script>
