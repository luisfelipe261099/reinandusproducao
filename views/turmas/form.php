<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <form action="turmas.php?action=salvar" method="post" class="p-6">
        <?php if (isset($turma['id'])): ?>
        <input type="hidden" name="id" value="<?php echo $turma['id']; ?>">
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Informações Básicas -->
            <div class="col-span-1 md:col-span-2">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Informações Básicas</h2>
            </div>

            <!-- Nome -->
            <div>
                <label for="nome" class="block text-sm font-medium text-gray-700 mb-1">Nome da Turma *</label>
                <input type="text" name="nome" id="nome" value="<?php echo isset($turma['nome']) ? htmlspecialchars($turma['nome']) : ''; ?>" required class="form-input w-full">
            </div>



            <!-- Curso -->
            <div>
                <label for="curso_id" class="block text-sm font-medium text-gray-700 mb-1">Curso *</label>
                <?php if (empty($cursos)): ?>
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-2">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">
                                Não há cursos cadastrados. <a href="cursos.php?action=novo" class="font-medium underline text-yellow-700 hover:text-yellow-600">Cadastre um curso</a> antes de continuar.
                            </p>
                        </div>
                    </div>
                </div>
                <select name="curso_id" id="curso_id" class="form-select w-full" required disabled>
                    <option value="">Nenhum curso disponível</option>
                </select>
                <?php else: ?>
                <select name="curso_id" id="curso_id" class="form-select w-full" required>
                    <option value="">Selecione um curso...</option>
                    <?php foreach ($cursos as $curso): ?>
                    <option value="<?php echo $curso['id']; ?>" <?php echo isset($turma['curso_id']) && $turma['curso_id'] == $curso['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($curso['nome']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <?php endif; ?>
            </div>

            <!-- Polo -->
            <div>
                <label for="polo_id" class="block text-sm font-medium text-gray-700 mb-1">Polo *</label>
                <?php if (empty($polos)): ?>
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-2">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">
                                Não há polos cadastrados. <a href="polos.php?action=novo" class="font-medium underline text-yellow-700 hover:text-yellow-600">Cadastre um polo</a> antes de continuar.
                            </p>
                        </div>
                    </div>
                </div>
                <select name="polo_id" id="polo_id" class="form-select w-full" required disabled>
                    <option value="">Nenhum polo disponível</option>
                </select>
                <?php else: ?>
                <select name="polo_id" id="polo_id" class="form-select w-full" required>
                    <option value="">Selecione um polo...</option>
                    <?php foreach ($polos as $polo): ?>
                    <option value="<?php echo $polo['id']; ?>" <?php echo isset($turma['polo_id']) && $turma['polo_id'] == $polo['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($polo['nome']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <?php endif; ?>
            </div>

            <!-- Professor -->
            <div>
                <label for="professor_id" class="block text-sm font-medium text-gray-700 mb-1">Professor Responsável</label>
                <select name="professor_id" id="professor_id" class="form-select w-full">
                    <option value="">Selecione um professor...</option>
                    <?php foreach ($professores as $professor): ?>
                    <option value="<?php echo $professor['id']; ?>" <?php echo isset($turma['professor_coordenador_id']) && $turma['professor_coordenador_id'] == $professor['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($professor['nome']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Turno -->
            <div>
                <label for="turno" class="block text-sm font-medium text-gray-700 mb-1">Turno</label>
                <select name="turno" id="turno" class="form-select w-full">
                    <option value="manha" <?php echo isset($turma['turno']) && $turma['turno'] === 'manha' ? 'selected' : ''; ?>>Manhã</option>
                    <option value="tarde" <?php echo isset($turma['turno']) && $turma['turno'] === 'tarde' ? 'selected' : ''; ?>>Tarde</option>
                    <option value="noite" <?php echo isset($turma['turno']) && $turma['turno'] === 'noite' ? 'selected' : ''; ?>>Noite</option>
                    <option value="integral" <?php echo isset($turma['turno']) && $turma['turno'] === 'integral' ? 'selected' : ''; ?>>Integral</option>
                </select>
            </div>

            <!-- Status -->
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" id="status" class="form-select w-full">
                    <option value="planejada" <?php echo isset($turma['status']) && $turma['status'] === 'planejada' ? 'selected' : ''; ?>>Planejada</option>
                    <option value="em_andamento" <?php echo isset($turma['status']) && $turma['status'] === 'em_andamento' ? 'selected' : ''; ?>>Em Andamento</option>
                    <option value="concluida" <?php echo isset($turma['status']) && $turma['status'] === 'concluida' ? 'selected' : ''; ?>>Concluída</option>
                    <option value="cancelada" <?php echo isset($turma['status']) && $turma['status'] === 'cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                </select>
            </div>

            <!-- Período e Horários -->
            <div class="col-span-1 md:col-span-2">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 mt-4">Período e Horários</h2>
            </div>

            <!-- Data de Início -->
            <div>
                <label for="data_inicio" class="block text-sm font-medium text-gray-700 mb-1">Data de Início</label>
                <input type="date" name="data_inicio" id="data_inicio" value="<?php echo isset($turma['data_inicio']) && !empty($turma['data_inicio']) ? date('Y-m-d', strtotime($turma['data_inicio'])) : ''; ?>" class="form-input w-full">
            </div>

            <!-- Data de Término -->
            <div>
                <label for="data_fim" class="block text-sm font-medium text-gray-700 mb-1">Data de Término</label>
                <input type="date" name="data_fim" id="data_fim" value="<?php echo isset($turma['data_fim']) && !empty($turma['data_fim']) ? date('Y-m-d', strtotime($turma['data_fim'])) : ''; ?>" class="form-input w-full">
            </div>

            <!-- Horário -->
            <div>
                <label for="horario" class="block text-sm font-medium text-gray-700 mb-1">Horário</label>
                <input type="text" name="horario" id="horario" value="<?php echo isset($turma['horario']) ? htmlspecialchars($turma['horario']) : ''; ?>" placeholder="Ex: Segunda a Sexta, 19h às 22h" class="form-input w-full">
            </div>

            <!-- Vagas -->
            <div>
                <label for="vagas" class="block text-sm font-medium text-gray-700 mb-1">Número de Vagas</label>
                <input type="number" name="vagas" id="vagas" value="<?php echo isset($turma['vagas_total']) ? $turma['vagas_total'] : '30'; ?>" min="1" class="form-input w-full">
            </div>

            <!-- Carga Horária -->
            <div>
                <label for="carga_horaria" class="block text-sm font-medium text-gray-700 mb-1">Carga Horária (horas)</label>
                <input type="number" name="carga_horaria" id="carga_horaria" value="<?php echo isset($turma['carga_horaria']) ? $turma['carga_horaria'] : ''; ?>" min="1" placeholder="Ex: 360" class="form-input w-full">
                <p class="text-xs text-gray-500 mt-1">Carga horária total da turma em horas</p>
            </div>

            <!-- Informações Adicionais -->
            <div class="col-span-1 md:col-span-2">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 mt-4">Informações Adicionais</h2>
            </div>

            <!-- Observações -->
            <div class="col-span-1 md:col-span-2">
                <label for="observacoes" class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                <textarea name="observacoes" id="observacoes" rows="4" class="form-textarea w-full"><?php echo isset($turma['observacoes']) ? htmlspecialchars($turma['observacoes']) : ''; ?></textarea>
            </div>

            <!-- ID Legado -->
            <div>
                <label for="id_legado" class="block text-sm font-medium text-gray-700 mb-1">ID Legado</label>
                <input type="text" name="id_legado" id="id_legado" value="<?php echo isset($turma['id_legado']) ? htmlspecialchars($turma['id_legado']) : ''; ?>" class="form-input w-full">
                <p class="text-xs text-gray-500 mt-1">Identificador da turma no sistema legado</p>
            </div>
        </div>

        <div class="mt-6 flex items-center justify-end space-x-3">
            <a href="turmas.php" class="btn-secondary">Cancelar</a>
            <button type="submit" class="btn-primary">Salvar</button>
        </div>
    </form>
</div>

<script>
(function() {
    'use strict';

    // Evita execução múltipla
    if (window.turmasFormInitialized) {
        return;
    }
    window.turmasFormInitialized = true;

    document.addEventListener('DOMContentLoaded', function() {
        // Elementos do formulário
        const dataInicio = document.getElementById('data_inicio');
        const dataFim = document.getElementById('data_fim');
        const status = document.getElementById('status');

        // Função para validar datas
        function validarDatas() {
            if (!dataInicio?.value || !dataFim?.value) return;

            if (dataInicio.value.length === 10 && dataFim.value.length === 10) {
                if (dataInicio.value > dataFim.value) {
                    alert('A data de término não pode ser anterior à data de início.');
                    dataFim.value = '';
                    dataFim.focus();
                    return false;
                }
            }
            return true;
        }

        // Função para atualizar status baseado nas datas
        function atualizarStatusComBaseNasDatas() {
            if (!dataInicio?.value || !dataFim?.value || !status) return;

            if (dataInicio.value.length !== 10 || dataFim.value.length !== 10) return;

            const hoje = new Date();
            hoje.setHours(0, 0, 0, 0);

            const inicio = new Date(dataInicio.value);
            const fim = new Date(dataFim.value);

            // Verifica se as datas são válidas
            if (isNaN(inicio.getTime()) || isNaN(fim.getTime())) return;

            if (inicio > hoje) {
                status.value = 'planejada';
            } else if (fim < hoje) {
                status.value = 'concluida';
            } else {
                status.value = 'em_andamento';
            }
        }

        // Adiciona event listeners apenas se os elementos existem
        if (dataInicio && dataFim) {
            dataInicio.addEventListener('blur', function() {
                validarDatas();
                atualizarStatusComBaseNasDatas();
            });

            dataFim.addEventListener('blur', function() {
                validarDatas();
                atualizarStatusComBaseNasDatas();
            });

            // Validação no submit do formulário
            const form = dataInicio.closest('form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    if (!validarDatas()) {
                        e.preventDefault();
                    }
                });
            }
        }
    });
})();
</script>
