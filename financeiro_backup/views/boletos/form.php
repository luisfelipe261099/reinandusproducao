<?php
// Exibe mensagens de erro ou sucesso
if (isset($_SESSION['mensagem'])) {
    if (is_array($_SESSION['mensagem'])) {
        $tipo = $_SESSION['mensagem']['tipo'];
        $texto = $_SESSION['mensagem']['texto'];
    } else {
        // Compatibilidade com o formato antigo
        $tipo = isset($_SESSION['mensagem_tipo']) ? $_SESSION['mensagem_tipo'] : 'erro';
        $texto = $_SESSION['mensagem'];
    }

    echo '<div class="mb-4 ' . ($tipo == 'erro' ? 'bg-red-100 border-red-400 text-red-700' : 'bg-green-100 border-green-400 text-green-700') . ' px-4 py-3 rounded relative border" role="alert">';
    echo '<span class="block sm:inline">' . $texto . '</span>';
    echo '</div>';

    unset($_SESSION['mensagem']);
    if (isset($_SESSION['mensagem_tipo'])) {
        unset($_SESSION['mensagem_tipo']);
    }
}
?>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="p-6">
        <form id="form-gerar-boleto" action="gerar_boleto.php?action=gerar" method="post" class="space-y-6">
            <!-- Tipo de Entidade -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Tipo de Boleto</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="flex items-center">
                        <input type="radio" id="tipo_aluno" name="tipo_entidade" value="aluno" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" checked>
                        <label for="tipo_aluno" class="ml-2 block text-sm text-gray-700">Aluno</label>
                    </div>
                    <div class="flex items-center">
                        <input type="radio" id="tipo_polo" name="tipo_entidade" value="polo" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="tipo_polo" class="ml-2 block text-sm text-gray-700">Polo</label>
                    </div>
                    <div class="flex items-center">
                        <input type="radio" id="tipo_avulso" name="tipo_entidade" value="avulso" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="tipo_avulso" class="ml-2 block text-sm text-gray-700">Boleto Avulso</label>
                    </div>
                </div>
            </div>

            <!-- Seleção de Entidade (Aluno/Polo) -->
            <div id="selecao_aluno" class="mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Selecionar Aluno</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="aluno_id" class="block text-sm font-medium text-gray-700 mb-1">Aluno</label>
                        <select name="aluno_id" id="aluno_id" class="form-select w-full">
                            <option value="">Selecione um aluno...</option>
                            <?php foreach ($alunos as $aluno): ?>
                            <option value="<?php echo $aluno['id']; ?>"><?php echo htmlspecialchars($aluno['nome']); ?> (CPF: <?php echo htmlspecialchars($aluno['cpf']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="matricula_id" class="block text-sm font-medium text-gray-700 mb-1">Matrícula (opcional)</label>
                        <select name="matricula_id" id="matricula_id" class="form-select w-full" disabled>
                            <option value="">Selecione um aluno primeiro...</option>
                        </select>
                    </div>
                </div>
            </div>

            <div id="selecao_polo" class="mb-6 hidden">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Selecionar Polo</h3>
                <div>
                    <label for="polo_id" class="block text-sm font-medium text-gray-700 mb-1">Polo</label>
                    <select name="polo_id" id="polo_id" class="form-select w-full">
                        <option value="">Selecione um polo...</option>
                        <?php foreach ($polos as $polo): ?>
                        <option value="<?php echo $polo['id']; ?>"><?php echo htmlspecialchars($polo['nome']); ?> <?php echo !empty($polo['cnpj']) ? '(CNPJ: ' . htmlspecialchars($polo['cnpj']) . ')' : ''; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div id="dados_avulso" class="mb-6 hidden">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Dados do Pagador</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="nome_pagador" class="block text-sm font-medium text-gray-700 mb-1">Nome do Pagador *</label>
                        <input type="text" name="nome_pagador" id="nome_pagador" class="form-input w-full">
                    </div>
                    <div>
                        <label for="cpf_pagador" class="block text-sm font-medium text-gray-700 mb-1">CPF/CNPJ do Pagador *</label>
                        <input type="text" name="cpf_pagador" id="cpf_pagador" class="form-input w-full" placeholder="Apenas números">
                    </div>
                    <div>
                        <label for="logradouro" class="block text-sm font-medium text-gray-700 mb-1">Logradouro *</label>
                        <input type="text" name="logradouro" id="logradouro" class="form-input w-full">
                    </div>
                    <div>
                        <label for="numero" class="block text-sm font-medium text-gray-700 mb-1">Número *</label>
                        <input type="text" name="numero" id="numero" class="form-input w-full">
                    </div>
                    <div>
                        <label for="bairro" class="block text-sm font-medium text-gray-700 mb-1">Bairro *</label>
                        <input type="text" name="bairro" id="bairro" class="form-input w-full">
                    </div>
                    <div>
                        <label for="cidade" class="block text-sm font-medium text-gray-700 mb-1">Cidade *</label>
                        <input type="text" name="cidade" id="cidade" class="form-input w-full">
                    </div>
                    <div>
                        <label for="uf" class="block text-sm font-medium text-gray-700 mb-1">UF *</label>
                        <select name="uf" id="uf" class="form-select w-full">
                            <option value="">Selecione...</option>
                            <option value="AC">Acre</option>
                            <option value="AL">Alagoas</option>
                            <option value="AP">Amapá</option>
                            <option value="AM">Amazonas</option>
                            <option value="BA">Bahia</option>
                            <option value="CE">Ceará</option>
                            <option value="DF">Distrito Federal</option>
                            <option value="ES">Espírito Santo</option>
                            <option value="GO">Goiás</option>
                            <option value="MA">Maranhão</option>
                            <option value="MT">Mato Grosso</option>
                            <option value="MS">Mato Grosso do Sul</option>
                            <option value="MG">Minas Gerais</option>
                            <option value="PA">Pará</option>
                            <option value="PB">Paraíba</option>
                            <option value="PR">Paraná</option>
                            <option value="PE">Pernambuco</option>
                            <option value="PI">Piauí</option>
                            <option value="RJ">Rio de Janeiro</option>
                            <option value="RN">Rio Grande do Norte</option>
                            <option value="RS">Rio Grande do Sul</option>
                            <option value="RO">Rondônia</option>
                            <option value="RR">Roraima</option>
                            <option value="SC">Santa Catarina</option>
                            <option value="SP">São Paulo</option>
                            <option value="SE">Sergipe</option>
                            <option value="TO">Tocantins</option>
                        </select>
                    </div>
                    <div>
                        <label for="cep" class="block text-sm font-medium text-gray-700 mb-1">CEP *</label>
                        <input type="text" name="cep" id="cep" class="form-input w-full" placeholder="Apenas números">
                    </div>
                </div>
            </div>

            <!-- Dados do Boleto -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Dados do Boleto</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="descricao" class="block text-sm font-medium text-gray-700 mb-1">Descrição *</label>
                        <input type="text" name="descricao" id="descricao" class="form-input w-full" required>
                    </div>
                    <div>
                        <label for="valor" class="block text-sm font-medium text-gray-700 mb-1">Valor (R$) *</label>
                        <input type="text" name="valor" id="valor" class="form-input w-full" placeholder="0,00" required>
                    </div>
                    <div>
                        <label for="data_vencimento" class="block text-sm font-medium text-gray-700 mb-1">Data de Vencimento *</label>
                        <input type="date" name="data_vencimento" id="data_vencimento" class="form-input w-full" required>
                    </div>
                    <div>
                        <label for="tipo_boleto" class="block text-sm font-medium text-gray-700 mb-1">Tipo de Boleto *</label>
                        <select name="tipo_boleto" id="tipo_boleto" class="form-select w-full" required>
                            <option value="a vista">À Vista</option>
                            <option value="parcelado">Parcelado</option>
                            <option value="recorrente">Recorrente</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Opções de Parcelamento (inicialmente oculto) -->
            <div id="opcoes_parcelamento" class="mb-6 hidden">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Opções de Parcelamento</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="numero_parcelas" class="block text-sm font-medium text-gray-700 mb-1">Número de Parcelas *</label>
                        <select name="numero_parcelas" id="numero_parcelas" class="form-select w-full">
                            <?php for ($i = 2; $i <= 12; $i++): ?>
                            <option value="<?php echo $i; ?>"><?php echo $i; ?> parcelas</option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div>
                        <label for="intervalo_dias" class="block text-sm font-medium text-gray-700 mb-1">Intervalo em Dias *</label>
                        <select name="intervalo_dias" id="intervalo_dias" class="form-select w-full">
                            <option value="30">30 dias</option>
                            <option value="15">15 dias</option>
                            <option value="7">7 dias</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Opções de Recorrência (inicialmente oculto) -->
            <div id="opcoes_recorrencia" class="mb-6 hidden">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Opções de Recorrência</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="numero_recorrencias" class="block text-sm font-medium text-gray-700 mb-1">Número de Recorrências *</label>
                        <select name="numero_recorrencias" id="numero_recorrencias" class="form-select w-full">
                            <?php for ($i = 2; $i <= 36; $i++): ?>
                            <option value="<?php echo $i; ?>"><?php echo $i; ?> meses</option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div>
                        <label for="dia_vencimento" class="block text-sm font-medium text-gray-700 mb-1">Dia do Vencimento *</label>
                        <select name="dia_vencimento" id="dia_vencimento" class="form-select w-full">
                            <?php for ($i = 1; $i <= 28; $i++): ?>
                            <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Opções Adicionais -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Opções Adicionais</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="multa" class="block text-sm font-medium text-gray-700 mb-1">Multa (%)</label>
                        <input type="text" name="multa" id="multa" class="form-input w-full" value="2" placeholder="0,00">
                    </div>
                    <div>
                        <label for="juros" class="block text-sm font-medium text-gray-700 mb-1">Juros ao Mês (%)</label>
                        <input type="text" name="juros" id="juros" class="form-input w-full" value="1" placeholder="0,00">
                    </div>
                    <div>
                        <label for="instrucoes" class="block text-sm font-medium text-gray-700 mb-1">Instruções</label>
                        <textarea name="instrucoes" id="instrucoes" rows="3" class="form-textarea w-full"></textarea>
                    </div>
                </div>
            </div>

            <div class="flex justify-end space-x-3">
                <a href="gerar_boleto.php?action=mensalidades" class="btn-secondary">
                    <i class="fas fa-calendar-alt mr-2"></i> Gerar Boletos de Mensalidades
                </a>
                <a href="gerar_boleto.php?action=listar" class="btn-secondary">
                    <i class="fas fa-list mr-2"></i> Ver Boletos Gerados
                </a>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-barcode mr-2"></i> Gerar Boleto
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Controle de exibição dos campos com base no tipo de entidade
    document.querySelectorAll('input[name="tipo_entidade"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const selecaoAluno = document.getElementById('selecao_aluno');
            const selecaoPolo = document.getElementById('selecao_polo');
            const dadosAvulso = document.getElementById('dados_avulso');

            selecaoAluno.classList.add('hidden');
            selecaoPolo.classList.add('hidden');
            dadosAvulso.classList.add('hidden');

            if (this.value === 'aluno') {
                selecaoAluno.classList.remove('hidden');
            } else if (this.value === 'polo') {
                selecaoPolo.classList.remove('hidden');
            } else if (this.value === 'avulso') {
                dadosAvulso.classList.remove('hidden');
            }
        });
    });

    // Controle de exibição dos campos com base no tipo de boleto
    document.getElementById('tipo_boleto').addEventListener('change', function() {
        const opcoesParcelamento = document.getElementById('opcoes_parcelamento');
        const opcoesRecorrencia = document.getElementById('opcoes_recorrencia');

        opcoesParcelamento.classList.add('hidden');
        opcoesRecorrencia.classList.add('hidden');

        if (this.value === 'parcelado') {
            opcoesParcelamento.classList.remove('hidden');
        } else if (this.value === 'recorrente') {
            opcoesRecorrencia.classList.remove('hidden');
        }
    });

    // Carregar matrículas do aluno selecionado
    document.getElementById('aluno_id').addEventListener('change', function() {
        const alunoId = this.value;
        const matriculaSelect = document.getElementById('matricula_id');

        // Limpa as opções atuais
        matriculaSelect.innerHTML = '<option value="">Carregando matrículas...</option>';
        matriculaSelect.disabled = true;

        if (!alunoId) {
            matriculaSelect.innerHTML = '<option value="">Selecione um aluno primeiro...</option>';
            return;
        }

        // Faz a requisição AJAX para buscar as matrículas do aluno
        fetch(`ajax/buscar_matriculas.php?aluno_id=${alunoId}`)
            .then(response => response.json())
            .then(data => {
                matriculaSelect.innerHTML = '<option value="">Selecione uma matrícula (opcional)...</option>';

                if (data.matriculas && data.matriculas.length > 0) {
                    data.matriculas.forEach(matricula => {
                        const option = document.createElement('option');
                        option.value = matricula.id;
                        option.textContent = `${matricula.numero} - ${matricula.curso_nome}`;
                        matriculaSelect.appendChild(option);
                    });
                    matriculaSelect.disabled = false;
                } else {
                    matriculaSelect.innerHTML = '<option value="">Nenhuma matrícula encontrada</option>';
                }
            })
            .catch(error => {
                console.error('Erro ao carregar matrículas:', error);
                matriculaSelect.innerHTML = '<option value="">Erro ao carregar matrículas</option>';
            });
    });

    // Formatação de campos
    document.getElementById('valor').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        value = (parseInt(value) / 100).toFixed(2).replace('.', ',');
        e.target.value = value;
    });

    document.getElementById('cpf_pagador').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        e.target.value = value;
    });

    document.getElementById('cep').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        e.target.value = value;
    });
</script>
