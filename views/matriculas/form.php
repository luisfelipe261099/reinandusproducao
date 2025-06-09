<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <form action="matriculas.php?action=salvar" method="post" class="p-6">
        <?php if (isset($matricula['id'])): ?>
        <input type="hidden" name="id" value="<?php echo $matricula['id']; ?>">
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Informações Básicas -->
            <div class="col-span-1 md:col-span-2">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Informações Básicas</h2>
            </div>

            <!-- Aluno -->
            <div>
                <label for="aluno_id" class="block text-sm font-medium text-gray-700 mb-1">Aluno *</label>
                <?php if (empty($alunos)): ?>
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-2">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">
                                Não há alunos cadastrados. <a href="alunos.php?action=novo" class="font-medium underline text-yellow-700 hover:text-yellow-600">Cadastre um aluno</a> antes de continuar.
                            </p>
                        </div>
                    </div>
                </div>
                <select name="aluno_id" id="aluno_id" class="form-select w-full" required disabled>
                    <option value="">Nenhum aluno disponível</option>
                </select>
                <?php else: ?>
                <div class="relative">
                    <input type="text" id="aluno_busca" class="form-input w-full" placeholder="Digite para buscar um aluno..." autocomplete="off">
                    <input type="hidden" name="aluno_id" id="aluno_id" value="<?php echo isset($matricula['aluno_id']) ? $matricula['aluno_id'] : ''; ?>" required>
                    <div id="aluno_nome_display" class="mt-2 text-sm font-medium <?php echo isset($matricula['aluno_id']) ? '' : 'hidden'; ?>">
                        <?php
                        if (isset($matricula['aluno_id'])) {
                            $aluno_selecionado = null;
                            foreach ($alunos as $aluno) {
                                if ($aluno['id'] == $matricula['aluno_id']) {
                                    $aluno_selecionado = $aluno;
                                    break;
                                }
                            }

                            if ($aluno_selecionado) {
                                echo '<span class="text-gray-700">Aluno selecionado:</span> <span class="text-blue-600">' . htmlspecialchars($aluno_selecionado['nome']) . '</span>';
                                if (!empty($aluno_selecionado['cpf'])) {
                                    echo ' <span class="text-gray-500">(' . htmlspecialchars($aluno_selecionado['cpf']) . ')</span>';
                                }
                            }
                        }
                        ?>
                    </div>
                    <div id="alunos_resultados" class="absolute z-10 w-full mt-1 bg-white shadow-lg rounded-md border border-gray-300 hidden max-h-60 overflow-y-auto"></div>
                </div>
                <?php endif; ?>
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
                    <option value="<?php echo $curso['id']; ?>" <?php echo isset($matricula['curso_id']) && $matricula['curso_id'] == $curso['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($curso['nome']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <?php endif; ?>
            </div>

            <!-- Turma -->
            <div>
                <label for="turma_id" class="block text-sm font-medium text-gray-700 mb-1">Turma</label>
                <select name="turma_id" id="turma_id" class="form-select w-full">
                    <option value="">Selecione uma turma (opcional)...</option>
                    <?php foreach ($turmas as $turma): ?>
                    <option value="<?php echo $turma['id']; ?>" <?php echo isset($matricula['turma_id']) && $matricula['turma_id'] == $turma['id'] ? 'selected' : ''; ?> data-curso-id="<?php echo $turma['curso_id']; ?>">
                        <?php echo htmlspecialchars($turma['nome']); ?> <?php echo isset($turma['curso_nome']) ? '(' . htmlspecialchars($turma['curso_nome']) . ')' : ''; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <p class="text-xs text-gray-500 mt-1">A turma deve pertencer ao curso selecionado.</p>
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
                    <option value="<?php echo $polo['id']; ?>" <?php echo isset($matricula['polo_id']) && $matricula['polo_id'] == $polo['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($polo['nome']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <?php endif; ?>
            </div>

            <!-- Status -->
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" id="status" class="form-select w-full">
                    <option value="ativo" <?php echo !isset($matricula['status']) || $matricula['status'] === 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                    <option value="pendente" <?php echo isset($matricula['status']) && $matricula['status'] === 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                    <option value="concluido" <?php echo isset($matricula['status']) && $matricula['status'] === 'concluido' ? 'selected' : ''; ?>>Concluído</option>
                    <option value="trancado" <?php echo isset($matricula['status']) && $matricula['status'] === 'trancado' ? 'selected' : ''; ?>>Trancado</option>
                    <option value="cancelado" <?php echo isset($matricula['status']) && $matricula['status'] === 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                </select>
            </div>

            <!-- Período -->
            <div class="col-span-1 md:col-span-2">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 mt-4">Período</h2>
            </div>

            <!-- Data de Matrícula -->
            <div>
                <label for="data_matricula" class="block text-sm font-medium text-gray-700 mb-1">Data de Matrícula</label>
                <input type="date" name="data_matricula" id="data_matricula" value="<?php echo isset($matricula['data_matricula']) && !empty($matricula['data_matricula']) ? date('Y-m-d', strtotime($matricula['data_matricula'])) : date('Y-m-d'); ?>" class="form-input w-full" required>
            </div>

            <!-- Data de Início -->
            <div>
                <label for="data_inicio" class="block text-sm font-medium text-gray-700 mb-1">Data de Início</label>
                <input type="date" name="data_inicio" id="data_inicio" value="<?php echo isset($matricula['data_inicio']) && !empty($matricula['data_inicio']) ? date('Y-m-d', strtotime($matricula['data_inicio'])) : date('Y-m-d'); ?>" class="form-input w-full" required>
            </div>

            <!-- Data de Término -->
            <div>
                <label for="data_fim" class="block text-sm font-medium text-gray-700 mb-1">Data de Término</label>
                <input type="date" name="data_fim" id="data_fim" value="<?php echo isset($matricula['data_fim']) && !empty($matricula['data_fim']) ? date('Y-m-d', strtotime($matricula['data_fim'])) : date('Y-m-d', strtotime('+1 year')); ?>" class="form-input w-full" required>
            </div>



            <!-- Informações Adicionais -->
            <div class="col-span-1 md:col-span-2">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 mt-4">Informações Adicionais</h2>
            </div>

            <!-- Observações -->
            <div class="col-span-1 md:col-span-2">
                <label for="observacoes" class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                <textarea name="observacoes" id="observacoes" rows="4" class="form-textarea w-full" placeholder="Digite as observações sobre a matrícula..."><?php echo isset($matricula['observacoes']) ? htmlspecialchars($matricula['observacoes']) : ''; ?></textarea>
            </div>

            <!-- ID Legado -->
            <div>
                <label for="id_legado" class="block text-sm font-medium text-gray-700 mb-1">ID Legado</label>
                <input type="text" name="id_legado" id="id_legado" value="<?php echo isset($matricula['id_legado']) ? htmlspecialchars($matricula['id_legado']) : ''; ?>" class="form-input w-full" placeholder="ID do sistema legado">
                <p class="text-xs text-gray-500 mt-1">Identificador no sistema legado, se aplicável.</p>
            </div>
        </div>

        <div class="mt-6 flex items-center justify-end space-x-3">
            <a href="matriculas.php" class="btn-secondary">Cancelar</a>
            <button type="submit" class="btn-primary">
                <?php echo isset($matricula['id']) ? 'Atualizar Matrícula' : 'Salvar Matrícula'; ?>
            </button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const cursoSelect = document.getElementById('curso_id');
        const turmaSelect = document.getElementById('turma_id');

        // Função para filtrar as turmas com base no curso selecionado
        function filtrarTurmas() {
            const cursoId = cursoSelect.value;

            // Mostra todas as opções de turma
            Array.from(turmaSelect.options).forEach(option => {
                if (option.value === '') {
                    option.style.display = 'block'; // Sempre mostra a opção "Selecione uma turma"
                } else {
                    const turmaCursoId = option.getAttribute('data-curso-id');

                    if (!cursoId || cursoId === turmaCursoId) {
                        option.style.display = 'block';
                    } else {
                        option.style.display = 'none';

                        // Se a opção atual estiver selecionada mas não corresponder ao curso, desseleciona
                        if (option.selected) {
                            option.selected = false;
                            turmaSelect.selectedIndex = 0;
                        }
                    }
                }
            });
        }

        // Filtra as turmas quando o curso é alterado
        cursoSelect.addEventListener('change', filtrarTurmas);

        // Filtra as turmas na inicialização
        filtrarTurmas();

        // Busca de alunos
        const alunoBuscaInput = document.getElementById('aluno_busca');
        const alunoIdInput = document.getElementById('aluno_id');
        const alunoNomeDisplay = document.getElementById('aluno_nome_display');
        const alunosResultados = document.getElementById('alunos_resultados');

        if (alunoBuscaInput) {
            // Inicializa o campo de busca com o nome do aluno selecionado
            if (alunoIdInput.value) {
                // O nome já está sendo exibido no display, não precisamos fazer nada aqui
            }

            // Evento de digitação na busca
            let timeoutId;
            alunoBuscaInput.addEventListener('input', function() {
                clearTimeout(timeoutId);
                const termo = this.value.trim();

                if (termo.length < 3) {
                    alunosResultados.innerHTML = '';
                    alunosResultados.classList.add('hidden');
                    return;
                }

                timeoutId = setTimeout(function() {
                    // Simula uma busca no lado do cliente com os alunos já carregados
                    // Em produção, isso seria uma chamada AJAX para buscar alunos do servidor
                    buscarAlunosPorNome(termo);
                }, 300);
            });

            // Fecha os resultados ao clicar fora
            document.addEventListener('click', function(e) {
                if (!alunosResultados.contains(e.target) && e.target !== alunoBuscaInput) {
                    alunosResultados.classList.add('hidden');
                }
            });
        }

        // Função para buscar alunos por nome
        function buscarAlunosPorNome(termo) {
            // Em um ambiente real, isso seria uma chamada AJAX
            // Aqui estamos simulando com uma chamada fetch para um endpoint de busca
            fetch('buscar_alunos.php?termo=' + encodeURIComponent(termo))
                .then(response => response.json())
                .then(data => {
                    alunosResultados.innerHTML = '';

                    if (data.length === 0) {
                        const noResultsItem = document.createElement('div');
                        noResultsItem.className = 'p-3 text-gray-500 text-center';
                        noResultsItem.textContent = 'Nenhum aluno encontrado';
                        alunosResultados.appendChild(noResultsItem);
                    } else {
                        data.forEach(aluno => {
                            const item = document.createElement('div');
                            item.className = 'p-3 hover:bg-gray-100 cursor-pointer';

                            const nome = document.createElement('div');
                            nome.className = 'font-medium';
                            nome.textContent = aluno.nome;

                            const info = document.createElement('div');
                            info.className = 'text-xs text-gray-500';
                            info.textContent = aluno.cpf ? `CPF: ${aluno.cpf}` : '';
                            if (aluno.email) {
                                info.textContent += aluno.cpf ? ` | ${aluno.email}` : aluno.email;
                            }

                            item.appendChild(nome);
                            item.appendChild(info);

                            item.addEventListener('click', function() {
                                selecionarAluno(aluno);
                            });

                            alunosResultados.appendChild(item);
                        });
                    }

                    alunosResultados.classList.remove('hidden');
                })
                .catch(error => {
                    console.error('Erro ao buscar alunos:', error);

                    // Fallback para busca local se a chamada AJAX falhar
                    const alunosArray = <?php echo json_encode($alunos); ?>;
                    const termoLower = termo.toLowerCase();
                    const resultados = alunosArray.filter(aluno =>
                        aluno.nome.toLowerCase().includes(termoLower) ||
                        (aluno.cpf && aluno.cpf.includes(termo))
                    ).slice(0, 10);

                    alunosResultados.innerHTML = '';

                    if (resultados.length === 0) {
                        const noResultsItem = document.createElement('div');
                        noResultsItem.className = 'p-3 text-gray-500 text-center';
                        noResultsItem.textContent = 'Nenhum aluno encontrado';
                        alunosResultados.appendChild(noResultsItem);
                    } else {
                        resultados.forEach(aluno => {
                            const item = document.createElement('div');
                            item.className = 'p-3 hover:bg-gray-100 cursor-pointer';

                            const nome = document.createElement('div');
                            nome.className = 'font-medium';
                            nome.textContent = aluno.nome;

                            const info = document.createElement('div');
                            info.className = 'text-xs text-gray-500';
                            info.textContent = aluno.cpf ? `CPF: ${aluno.cpf}` : '';
                            if (aluno.email) {
                                info.textContent += aluno.cpf ? ` | ${aluno.email}` : aluno.email;
                            }

                            item.appendChild(nome);
                            item.appendChild(info);

                            item.addEventListener('click', function() {
                                selecionarAluno(aluno);
                            });

                            alunosResultados.appendChild(item);
                        });
                    }

                    alunosResultados.classList.remove('hidden');
                });
        }

        // Função para selecionar um aluno
        function selecionarAluno(aluno) {
            alunoIdInput.value = aluno.id;
            alunoBuscaInput.value = '';

            alunoNomeDisplay.innerHTML = `<span class="text-gray-700">Aluno selecionado:</span> <span class="text-blue-600">${aluno.nome}</span>`;
            if (aluno.cpf) {
                alunoNomeDisplay.innerHTML += ` <span class="text-gray-500">(${aluno.cpf})</span>`;
            }

            alunoNomeDisplay.classList.remove('hidden');
            alunosResultados.classList.add('hidden');
        }
    });
</script>
