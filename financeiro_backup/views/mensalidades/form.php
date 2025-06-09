<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800"><?php echo isset($mensalidade) ? 'Editar Mensalidade' : 'Nova Mensalidade'; ?></h2>
    </div>

    <?php
    // Exibe erros do formulário, se houver
    if (isset($_SESSION['form_errors']) && !empty($_SESSION['form_errors'])) {
        echo '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">';
        echo '<div class="flex">';
        echo '<div class="flex-shrink-0"><i class="fas fa-exclamation-triangle"></i></div>';
        echo '<div class="ml-3">';
        echo '<p class="text-sm font-medium">Por favor, corrija os seguintes erros:</p>';
        echo '<ul class="mt-1 text-sm list-disc list-inside">';
        foreach ($_SESSION['form_errors'] as $error) {
            echo '<li>' . htmlspecialchars($error) . '</li>';
        }
        echo '</ul>';
        echo '</div>';
        echo '</div>';
        echo '</div>';

        // Limpa os erros da sessão
        unset($_SESSION['form_errors']);
    }

    // Recupera dados do formulário, se houver
    $form_data = $_SESSION['form_data'] ?? [];
    unset($_SESSION['form_data']);

    // Define os valores dos campos
    $id = $mensalidade['id'] ?? $form_data['id'] ?? '';
    $aluno_id = $form_data['aluno_id'] ?? $mensalidade['aluno_id'] ?? '';
    $categoria_id = $form_data['categoria_id'] ?? $mensalidade['categoria_id'] ?? '';
    $plano_conta_id = $form_data['plano_conta_id'] ?? $mensalidade['plano_conta_id'] ?? '';
    $descricao = $form_data['descricao'] ?? $mensalidade['descricao'] ?? '';
    $valor = $form_data['valor'] ?? $mensalidade['valor'] ?? '';
    $desconto = $form_data['desconto'] ?? $mensalidade['desconto'] ?? '';
    $acrescimo = $form_data['acrescimo'] ?? $mensalidade['acrescimo'] ?? '';
    $data_vencimento = $form_data['data_vencimento'] ?? $mensalidade['data_vencimento'] ?? date('Y-m-d');
    $forma_pagamento = $form_data['forma_pagamento'] ?? $mensalidade['forma_pagamento'] ?? '';
    $status = $form_data['status'] ?? $mensalidade['status'] ?? 'pendente';
    $data_pagamento = $form_data['data_pagamento'] ?? $mensalidade['data_pagamento'] ?? '';
    $gerar_parcelas = $form_data['gerar_parcelas'] ?? '0';
    $total_parcelas = $form_data['total_parcelas'] ?? '1';
    $intervalo_dias = $form_data['intervalo_dias'] ?? '30';
    ?>

    <form action="mensalidades.php?action=salvar" method="post" class="p-6">
        <?php if ($id): ?>
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label for="aluno_id" class="block text-sm font-medium text-gray-700 mb-1">Aluno <span class="text-red-600">*</span></label>
                <div class="relative">
                    <input type="text" id="aluno_search" placeholder="Buscar aluno por nome ou CPF..." class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 mb-2">
                    <select name="aluno_id" id="aluno_id" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" <?php echo $id ? 'disabled' : ''; ?>>
                        <option value="">Selecione um aluno</option>
                        <?php foreach ($alunos as $aluno): ?>
                        <option value="<?php echo $aluno['id']; ?>" <?php echo $aluno_id == $aluno['id'] ? 'selected' : ''; ?> data-nome="<?php echo htmlspecialchars(strtolower($aluno['nome'])); ?>" data-cpf="<?php echo htmlspecialchars(strtolower($aluno['cpf'] ?? '')); ?>">
                            <?php echo htmlspecialchars($aluno['nome']); ?>
                            <?php if ($aluno['cpf']): ?> - CPF: <?php echo htmlspecialchars($aluno['cpf']); ?><?php endif; ?>
                            <?php if ($aluno['curso_nome']): ?> - Curso: <?php echo htmlspecialchars($aluno['curso_nome']); ?><?php endif; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if ($id): ?>
                <input type="hidden" name="aluno_id" value="<?php echo $aluno_id; ?>">
                <?php endif; ?>
                <p class="text-sm text-gray-500 mt-1">Se o aluno não aparecer na lista, <a href="../alunos.php?action=novo" target="_blank" class="text-blue-600 hover:underline">cadastre um novo aluno</a>.</p>
            </div>

            <div>
                <label for="categoria_id" class="block text-sm font-medium text-gray-700 mb-1">Categoria <span class="text-red-600">*</span></label>
                <select name="categoria_id" id="categoria_id" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    <option value="">Selecione uma categoria</option>
                    <?php foreach ($categorias as $categoria): ?>
                    <option value="<?php echo $categoria['id']; ?>" <?php echo $categoria_id == $categoria['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($categoria['nome']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <p class="text-sm text-gray-500 mt-1">
                    <a href="../scripts/criar_categorias.php" target="_blank" class="text-blue-600 hover:underline">Criar mais categorias</a>
                </p>
            </div>

            <div>
                <label for="plano_conta_id" class="block text-sm font-medium text-gray-700 mb-1">Plano de Contas <span class="text-red-600">*</span></label>
                <select name="plano_conta_id" id="plano_conta_id" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    <option value="">Selecione um plano de contas</option>
                    <?php foreach ($plano_contas as $plano): ?>
                    <option value="<?php echo $plano['id']; ?>" <?php echo $plano_conta_id == $plano['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($plano['codigo'] . ' - ' . $plano['descricao']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <p class="text-sm text-gray-500 mt-1">
                    <a href="../scripts/criar_plano_contas.php" target="_blank" class="text-blue-600 hover:underline">Criar mais planos de contas</a>
                </p>
            </div>

            <div>
                <label for="descricao" class="block text-sm font-medium text-gray-700 mb-1">Descrição <span class="text-red-600">*</span></label>
                <input type="text" name="descricao" id="descricao" value="<?php echo htmlspecialchars($descricao); ?>" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
            </div>

            <div>
                <label for="valor" class="block text-sm font-medium text-gray-700 mb-1">Valor (R$) <span class="text-red-600">*</span></label>
                <input type="text" name="valor" id="valor" value="<?php echo htmlspecialchars($valor); ?>" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" placeholder="0,00">
            </div>

            <div>
                <label for="data_vencimento" class="block text-sm font-medium text-gray-700 mb-1">Data de Vencimento <span class="text-red-600">*</span></label>
                <input type="date" name="data_vencimento" id="data_vencimento" value="<?php echo htmlspecialchars($data_vencimento); ?>" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
            </div>

            <div>
                <label for="desconto" class="block text-sm font-medium text-gray-700 mb-1">Desconto (R$)</label>
                <input type="text" name="desconto" id="desconto" value="<?php echo htmlspecialchars($desconto); ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" placeholder="0,00">
            </div>

            <div>
                <label for="acrescimo" class="block text-sm font-medium text-gray-700 mb-1">Acréscimo (R$)</label>
                <input type="text" name="acrescimo" id="acrescimo" value="<?php echo htmlspecialchars($acrescimo); ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" placeholder="0,00">
            </div>

            <div>
                <label for="forma_pagamento" class="block text-sm font-medium text-gray-700 mb-1">Forma de Pagamento</label>
                <select name="forma_pagamento" id="forma_pagamento" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    <option value="">Selecione</option>
                    <option value="dinheiro" <?php echo $forma_pagamento === 'dinheiro' ? 'selected' : ''; ?>>Dinheiro</option>
                    <option value="pix" <?php echo $forma_pagamento === 'pix' ? 'selected' : ''; ?>>PIX</option>
                    <option value="cartao_credito" <?php echo $forma_pagamento === 'cartao_credito' ? 'selected' : ''; ?>>Cartão de Crédito</option>
                    <option value="cartao_debito" <?php echo $forma_pagamento === 'cartao_debito' ? 'selected' : ''; ?>>Cartão de Débito</option>
                    <option value="boleto" <?php echo $forma_pagamento === 'boleto' ? 'selected' : ''; ?>>Boleto</option>
                    <option value="transferencia" <?php echo $forma_pagamento === 'transferencia' ? 'selected' : ''; ?>>Transferência Bancária</option>
                    <option value="outro" <?php echo $forma_pagamento === 'outro' ? 'selected' : ''; ?>>Outro</option>
                </select>
            </div>

            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status <span class="text-red-600">*</span></label>
                <select name="status" id="status" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    <option value="pendente" <?php echo $status === 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                    <option value="pago" <?php echo $status === 'pago' ? 'selected' : ''; ?>>Pago</option>
                    <option value="parcial" <?php echo $status === 'parcial' ? 'selected' : ''; ?>>Parcial</option>
                    <option value="cancelado" <?php echo $status === 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                </select>
            </div>

            <div id="data_pagamento_container" class="<?php echo $status !== 'pago' && $status !== 'parcial' ? 'hidden' : ''; ?>">
                <label for="data_pagamento" class="block text-sm font-medium text-gray-700 mb-1">Data de Pagamento</label>
                <input type="date" name="data_pagamento" id="data_pagamento" value="<?php echo htmlspecialchars($data_pagamento); ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
            </div>
        </div>

        <?php if (!$id): ?>
        <div class="bg-gray-50 p-4 rounded-lg mb-6">
            <div class="flex items-center mb-4">
                <input type="checkbox" name="gerar_parcelas" id="gerar_parcelas" value="1" <?php echo $gerar_parcelas === '1' ? 'checked' : ''; ?> class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                <label for="gerar_parcelas" class="ml-2 block text-sm text-gray-700">Gerar parcelas</label>
            </div>

            <div id="parcelas_container" class="<?php echo $gerar_parcelas !== '1' ? 'hidden' : ''; ?> grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="total_parcelas" class="block text-sm font-medium text-gray-700 mb-1">Número de Parcelas</label>
                    <input type="number" name="total_parcelas" id="total_parcelas" value="<?php echo htmlspecialchars($total_parcelas); ?>" min="1" max="36" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                </div>

                <div>
                    <label for="intervalo_dias" class="block text-sm font-medium text-gray-700 mb-1">Intervalo entre Parcelas (dias)</label>
                    <input type="number" name="intervalo_dias" id="intervalo_dias" value="<?php echo htmlspecialchars($intervalo_dias); ?>" min="1" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="flex justify-end space-x-3">
            <a href="mensalidades.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">Cancelar</a>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Salvar</button>
        </div>
    </form>
</div>

<script>
    // Mostrar/ocultar campo de data de pagamento conforme o status
    document.getElementById('status').addEventListener('change', function() {
        const dataPagamentoContainer = document.getElementById('data_pagamento_container');
        if (this.value === 'pago' || this.value === 'parcial') {
            dataPagamentoContainer.classList.remove('hidden');
            document.getElementById('data_pagamento').value = document.getElementById('data_pagamento').value || '<?php echo date('Y-m-d'); ?>';
        } else {
            dataPagamentoContainer.classList.add('hidden');
        }
    });

    // Mostrar/ocultar campos de parcelas
    const gerarParcelasCheckbox = document.getElementById('gerar_parcelas');
    if (gerarParcelasCheckbox) {
        gerarParcelasCheckbox.addEventListener('change', function() {
            const parcelasContainer = document.getElementById('parcelas_container');
            if (this.checked) {
                parcelasContainer.classList.remove('hidden');
            } else {
                parcelasContainer.classList.add('hidden');
            }
        });
    }

    // Formatar campos de valor
    document.querySelectorAll('input[name="valor"], input[name="desconto"], input[name="acrescimo"]').forEach(function(input) {
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = (parseInt(value) / 100).toFixed(2);
            e.target.value = value.replace('.', ',');
        });
    });

    // Funcionalidade de busca de alunos
    const alunoSearch = document.getElementById('aluno_search');
    const alunoSelect = document.getElementById('aluno_id');
    const alunoOptions = Array.from(alunoSelect.options).slice(1); // Ignora a opção "Selecione um aluno"

    alunoSearch.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();

        // Limpa o select mantendo apenas a primeira opção
        while (alunoSelect.options.length > 1) {
            alunoSelect.remove(1);
        }

        if (searchTerm === '') {
            // Se a busca estiver vazia, mostra todas as opções
            alunoOptions.forEach(option => {
                alunoSelect.add(option.cloneNode(true));
            });
        } else {
            // Filtra as opções que correspondem à busca
            const filteredOptions = alunoOptions.filter(option => {
                const nome = option.getAttribute('data-nome') || '';
                const cpf = option.getAttribute('data-cpf') || '';
                return nome.includes(searchTerm) || cpf.includes(searchTerm);
            });

            // Adiciona as opções filtradas
            filteredOptions.forEach(option => {
                alunoSelect.add(option.cloneNode(true));
            });

            // Se não houver resultados, mostra uma mensagem
            if (filteredOptions.length === 0) {
                const noResultOption = document.createElement('option');
                noResultOption.value = '';
                noResultOption.disabled = true;
                noResultOption.textContent = 'Nenhum aluno encontrado com esse termo';
                alunoSelect.add(noResultOption);
            }
        }
    });
</script>
