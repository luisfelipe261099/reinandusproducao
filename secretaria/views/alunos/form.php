<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <form action="alunos.php?action=salvar" method="post" class="p-6">
        <?php if (isset($aluno['id'])): ?>
        <input type="hidden" name="id" value="<?php echo $aluno['id']; ?>">
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Informações Pessoais -->
            <div class="col-span-1 md:col-span-2">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Informações Pessoais</h2>
            </div>

            <!-- Nome -->
            <div class="col-span-1 md:col-span-2">
                <label for="nome" class="block text-sm font-medium text-gray-700 mb-1">Nome Completo *</label>
                <input type="text" name="nome" id="nome" value="<?php echo isset($aluno['nome']) ? htmlspecialchars($aluno['nome']) : ''; ?>" required class="form-input w-full">
            </div>

            <!-- CPF -->
            <div>
                <label for="cpf" class="block text-sm font-medium text-gray-700 mb-1">CPF *</label>
                <input type="text" name="cpf" id="cpf" value="<?php echo isset($aluno['cpf']) ? htmlspecialchars($aluno['cpf']) : ''; ?>" required class="form-input w-full" maxlength="14" placeholder="000.000.000-00">
            </div>

            <!-- Data de Nascimento -->
            <div>
                <label for="data_nascimento" class="block text-sm font-medium text-gray-700 mb-1">Data de Nascimento</label>
                <input type="date" name="data_nascimento" id="data_nascimento" value="<?php echo isset($aluno['data_nascimento']) ? htmlspecialchars($aluno['data_nascimento']) : ''; ?>" class="form-input w-full">
            </div>

            <!-- E-mail -->
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">E-mail *</label>
                <input type="email" name="email" id="email" value="<?php echo isset($aluno['email']) ? htmlspecialchars($aluno['email']) : ''; ?>" required class="form-input w-full">
            </div>

            <!-- Telefone -->
            <div>
                <label for="telefone" class="block text-sm font-medium text-gray-700 mb-1">Telefone</label>
                <input type="text" name="telefone" id="telefone" value="<?php echo isset($aluno['telefone']) ? htmlspecialchars($aluno['telefone']) : ''; ?>" class="form-input w-full" placeholder="(00) 00000-0000">
            </div>

            <!-- Endereço -->
            <div class="col-span-1 md:col-span-2">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 mt-4">Endereço</h2>
            </div>

            <!-- Endereço -->
            <div class="col-span-1 md:col-span-2">
                <label for="endereco" class="block text-sm font-medium text-gray-700 mb-1">Endereço</label>
                <input type="text" name="endereco" id="endereco" value="<?php echo isset($aluno['endereco']) ? htmlspecialchars($aluno['endereco']) : ''; ?>" class="form-input w-full">
            </div>

            <!-- Cidade -->
            <div>
                <label for="cidade" class="block text-sm font-medium text-gray-700 mb-1">Cidade</label>
                <input type="text" name="cidade" id="cidade" value="<?php echo isset($aluno['cidade']) ? htmlspecialchars($aluno['cidade']) : ''; ?>" class="form-input w-full">
            </div>

            <!-- Estado -->
            <div>
                <label for="estado" class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                <select name="estado" id="estado" class="form-select w-full">
                    <option value="">Selecione...</option>
                    <option value="AC" <?php echo isset($aluno['estado']) && $aluno['estado'] === 'AC' ? 'selected' : ''; ?>>Acre</option>
                    <option value="AL" <?php echo isset($aluno['estado']) && $aluno['estado'] === 'AL' ? 'selected' : ''; ?>>Alagoas</option>
                    <option value="AP" <?php echo isset($aluno['estado']) && $aluno['estado'] === 'AP' ? 'selected' : ''; ?>>Amapá</option>
                    <option value="AM" <?php echo isset($aluno['estado']) && $aluno['estado'] === 'AM' ? 'selected' : ''; ?>>Amazonas</option>
                    <option value="BA" <?php echo isset($aluno['estado']) && $aluno['estado'] === 'BA' ? 'selected' : ''; ?>>Bahia</option>
                    <option value="CE" <?php echo isset($aluno['estado']) && $aluno['estado'] === 'CE' ? 'selected' : ''; ?>>Ceará</option>
                    <option value="DF" <?php echo isset($aluno['estado']) && $aluno['estado'] === 'DF' ? 'selected' : ''; ?>>Distrito Federal</option>
                    <option value="ES" <?php echo isset($aluno['estado']) && $aluno['estado'] === 'ES' ? 'selected' : ''; ?>>Espírito Santo</option>
                    <option value="GO" <?php echo isset($aluno['estado']) && $aluno['estado'] === 'GO' ? 'selected' : ''; ?>>Goiás</option>
                    <option value="MA" <?php echo isset($aluno['estado']) && $aluno['estado'] === 'MA' ? 'selected' : ''; ?>>Maranhão</option>
                    <option value="MT" <?php echo isset($aluno['estado']) && $aluno['estado'] === 'MT' ? 'selected' : ''; ?>>Mato Grosso</option>
                    <option value="MS" <?php echo isset($aluno['estado']) && $aluno['estado'] === 'MS' ? 'selected' : ''; ?>>Mato Grosso do Sul</option>
                    <option value="MG" <?php echo isset($aluno['estado']) && $aluno['estado'] === 'MG' ? 'selected' : ''; ?>>Minas Gerais</option>
                    <option value="PA" <?php echo isset($aluno['estado']) && $aluno['estado'] === 'PA' ? 'selected' : ''; ?>>Pará</option>
                    <option value="PB" <?php echo isset($aluno['estado']) && $aluno['estado'] === 'PB' ? 'selected' : ''; ?>>Paraíba</option>
                    <option value="PR" <?php echo isset($aluno['estado']) && $aluno['estado'] === 'PR' ? 'selected' : ''; ?>>Paraná</option>
                    <option value="PE" <?php echo isset($aluno['estado']) && $aluno['estado'] === 'PE' ? 'selected' : ''; ?>>Pernambuco</option>
                    <option value="PI" <?php echo isset($aluno['estado']) && $aluno['estado'] === 'PI' ? 'selected' : ''; ?>>Piauí</option>
                    <option value="RJ" <?php echo isset($aluno['estado']) && $aluno['estado'] === 'RJ' ? 'selected' : ''; ?>>Rio de Janeiro</option>
                    <option value="RN" <?php echo isset($aluno['estado']) && $aluno['estado'] === 'RN' ? 'selected' : ''; ?>>Rio Grande do Norte</option>
                    <option value="RS" <?php echo isset($aluno['estado']) && $aluno['estado'] === 'RS' ? 'selected' : ''; ?>>Rio Grande do Sul</option>
                    <option value="RO" <?php echo isset($aluno['estado']) && $aluno['estado'] === 'RO' ? 'selected' : ''; ?>>Rondônia</option>
                    <option value="RR" <?php echo isset($aluno['estado']) && $aluno['estado'] === 'RR' ? 'selected' : ''; ?>>Roraima</option>
                    <option value="SC" <?php echo isset($aluno['estado']) && $aluno['estado'] === 'SC' ? 'selected' : ''; ?>>Santa Catarina</option>
                    <option value="SP" <?php echo isset($aluno['estado']) && $aluno['estado'] === 'SP' ? 'selected' : ''; ?>>São Paulo</option>
                    <option value="SE" <?php echo isset($aluno['estado']) && $aluno['estado'] === 'SE' ? 'selected' : ''; ?>>Sergipe</option>
                    <option value="TO" <?php echo isset($aluno['estado']) && $aluno['estado'] === 'TO' ? 'selected' : ''; ?>>Tocantins</option>
                </select>
            </div>

            <!-- CEP -->
            <div>
                <label for="cep" class="block text-sm font-medium text-gray-700 mb-1">CEP</label>
                <input type="text" name="cep" id="cep" value="<?php echo isset($aluno['cep']) ? htmlspecialchars($aluno['cep']) : ''; ?>" class="form-input w-full" maxlength="9" placeholder="00000-000">
            </div>

            <!-- Informações Acadêmicas -->
            <div class="col-span-1 md:col-span-2">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 mt-4">Informações Acadêmicas</h2>
                <p class="text-sm text-gray-500 mb-4">Estes campos são opcionais e podem ser preenchidos posteriormente.</p>
            </div>

            <!-- Polo -->
            <div>
                <label for="polo_id" class="block text-sm font-medium text-gray-700 mb-1">Polo</label>
                <select name="polo_id" id="polo_id" class="form-select w-full">
                    <option value="">Selecione um polo...</option>
                    <?php foreach ($polos as $polo): ?>
                    <option value="<?php echo $polo['id']; ?>" <?php echo isset($aluno['polo_id']) && $aluno['polo_id'] == $polo['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($polo['nome']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Curso -->
            <div>
                <label for="curso_id" class="block text-sm font-medium text-gray-700 mb-1">Curso</label>
                <select name="curso_id" id="curso_id" class="form-select w-full">
                    <option value="">Selecione um curso...</option>
                    <?php foreach ($cursos as $curso): ?>
                    <option value="<?php echo $curso['id']; ?>" <?php echo isset($aluno['curso_id']) && $aluno['curso_id'] == $curso['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($curso['nome']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Turma -->
            <div>
                <label for="turma_id" class="block text-sm font-medium text-gray-700 mb-1">Turma</label>
                <select name="turma_id" id="turma_id" class="form-select w-full">
                    <option value="">Selecione uma turma...</option>
                    <?php foreach ($turmas as $turma): ?>
                    <option value="<?php echo $turma['id']; ?>"
                            <?php echo isset($aluno['turma_id']) && $aluno['turma_id'] == $turma['id'] ? 'selected' : ''; ?>
                            data-curso="<?php echo $turma['curso_id']; ?>">
                        <?php echo htmlspecialchars($turma['nome']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Informações Adicionais -->
            <div class="col-span-1 md:col-span-2">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 mt-4">Informações Adicionais</h2>
            </div>

            <!-- ID Legado -->
            <div>
                <label for="id_legado" class="block text-sm font-medium text-gray-700 mb-1">ID Legado</label>
                <input type="text" name="id_legado" id="id_legado" value="<?php echo isset($aluno['id_legado']) ? htmlspecialchars($aluno['id_legado']) : ''; ?>" class="form-input w-full">
                <p class="text-xs text-gray-500 mt-1">Identificador do aluno no sistema legado</p>
            </div>

            <!-- Status -->
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" id="status" class="form-select w-full">
                    <option value="ativo" <?php echo isset($aluno['status']) && $aluno['status'] === 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                    <option value="inativo" <?php echo isset($aluno['status']) && $aluno['status'] === 'inativo' ? 'selected' : ''; ?>>Inativo</option>
                </select>
            </div>
        </div>

        <div class="mt-6 flex items-center justify-end space-x-3">
            <a href="alunos.php" class="btn-secondary">Cancelar</a>
            <button type="submit" class="btn-primary">Salvar</button>
        </div>
    </form>
</div>

<script>
    // Máscara para CPF
    document.getElementById('cpf').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 11) value = value.slice(0, 11);

        if (value.length > 9) {
            value = value.replace(/^(\d{3})(\d{3})(\d{3})(\d{2}).*/, '$1.$2.$3-$4');
        } else if (value.length > 6) {
            value = value.replace(/^(\d{3})(\d{3})(\d{0,3}).*/, '$1.$2.$3');
        } else if (value.length > 3) {
            value = value.replace(/^(\d{3})(\d{0,3}).*/, '$1.$2');
        }

        e.target.value = value;
    });

    // Máscara para telefone
    document.getElementById('telefone').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 11) value = value.slice(0, 11);

        if (value.length > 10) {
            value = value.replace(/^(\d{2})(\d{5})(\d{4}).*/, '($1) $2-$3');
        } else if (value.length > 6) {
            value = value.replace(/^(\d{2})(\d{4})(\d{0,4}).*/, '($1) $2-$3');
        } else if (value.length > 2) {
            value = value.replace(/^(\d{2})(\d{0,5}).*/, '($1) $2');
        }

        e.target.value = value;
    });

    // Máscara para CEP
    document.getElementById('cep').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 8) value = value.slice(0, 8);

        if (value.length > 5) {
            value = value.replace(/^(\d{5})(\d{0,3}).*/, '$1-$2');
        }

        e.target.value = value;
    });

    // Filtra as turmas com base no curso selecionado
    document.addEventListener('DOMContentLoaded', function() {
        const cursoSelect = document.getElementById('curso_id');
        const turmaSelect = document.getElementById('turma_id');

        if (cursoSelect && turmaSelect) {
            cursoSelect.addEventListener('change', function() {
                const cursoId = this.value;
                const turmaOptions = turmaSelect.querySelectorAll('option');

                turmaOptions.forEach(option => {
                    if (option.value === '' || !cursoId || option.getAttribute('data-curso') === cursoId) {
                        option.style.display = '';
                    } else {
                        option.style.display = 'none';
                    }
                });

                // Reset turma selection if current selection is not valid for the selected course
                const currentTurma = turmaSelect.value;
                const currentTurmaOption = turmaSelect.querySelector(`option[value="${currentTurma}"]`);

                if (currentTurma && currentTurmaOption && currentTurmaOption.style.display === 'none') {
                    turmaSelect.value = '';
                }
            });

            // Trigger change event to initialize the filter
            cursoSelect.dispatchEvent(new Event('change'));
        }
    });
</script>
