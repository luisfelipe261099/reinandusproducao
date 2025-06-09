<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800"><?php echo $titulo_pagina; ?></h1>
        
        <div class="flex space-x-2">
            <a href="transacoes.php<?php echo isset($_GET['tipo']) && $_GET['tipo'] !== 'todos' ? '?tipo=' . $_GET['tipo'] : ''; ?>" class="btn-secondary">
                <i class="fas fa-arrow-left mr-2"></i> Voltar
            </a>
        </div>
    </div>
    
    <?php
    // Exibe erros do formulário, se houver
    if (isset($_SESSION['form_errors']) && !empty($_SESSION['form_errors'])) {
        echo '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">';
        echo '<div class="flex">';
        echo '<div class="flex-shrink-0"><i class="fas fa-exclamation-triangle"></i></div>';
        echo '<div class="ml-3">';
        echo '<p class="text-sm font-medium">Por favor, corrija os seguintes erros:</p>';
        echo '<ul class="mt-1 text-sm list-disc list-inside">';
        foreach ($_SESSION['form_errors'] as $erro) {
            echo '<li>' . $erro . '</li>';
        }
        echo '</ul>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        
        // Limpa os erros da sessão
        unset($_SESSION['form_errors']);
    }
    
    // Recupera os dados do formulário, se houver
    $form_data = $_SESSION['form_data'] ?? [];
    unset($_SESSION['form_data']);
    
    // Define os valores padrão
    $id = $transacao['id'] ?? null;
    $tipo = $form_data['tipo'] ?? ($transacao['tipo'] ?? ($_GET['tipo'] ?? 'receita'));
    $descricao = $form_data['descricao'] ?? ($transacao['descricao'] ?? '');
    $valor = $form_data['valor'] ?? ($transacao['valor'] ?? '');
    $data_transacao = $form_data['data_transacao'] ?? ($transacao['data_transacao'] ?? date('Y-m-d'));
    $categoria_id = $form_data['categoria_id'] ?? ($transacao['categoria_id'] ?? '');
    $conta_id = $form_data['conta_id'] ?? ($transacao['conta_id'] ?? '');
    $forma_pagamento = $form_data['forma_pagamento'] ?? ($transacao['forma_pagamento'] ?? '');
    $status = $form_data['status'] ?? ($transacao['status'] ?? 'efetivada');
    $observacoes = $form_data['observacoes'] ?? ($transacao['observacoes'] ?? '');
    ?>
    
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800">Dados da Transação</h2>
        </div>
        <div class="p-6">
            <form action="transacoes.php?action=salvar" method="post" enctype="multipart/form-data">
                <?php if ($id): ?>
                <input type="hidden" name="id" value="<?php echo $id; ?>">
                <?php endif; ?>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Tipo de Transação -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Transação</label>
                        <div class="flex space-x-4">
                            <label class="inline-flex items-center">
                                <input type="radio" name="tipo" value="receita" <?php echo $tipo === 'receita' ? 'checked' : ''; ?> class="form-radio text-green-600">
                                <span class="ml-2 text-gray-700">Receita</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="tipo" value="despesa" <?php echo $tipo === 'despesa' ? 'checked' : ''; ?> class="form-radio text-red-600">
                                <span class="ml-2 text-gray-700">Despesa</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="tipo" value="transferencia" <?php echo $tipo === 'transferencia' ? 'checked' : ''; ?> class="form-radio text-blue-600">
                                <span class="ml-2 text-gray-700">Transferência</span>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Status -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <div class="flex space-x-4">
                            <label class="inline-flex items-center">
                                <input type="radio" name="status" value="efetivada" <?php echo $status === 'efetivada' ? 'checked' : ''; ?> class="form-radio text-green-600">
                                <span class="ml-2 text-gray-700">Efetivada</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="status" value="pendente" <?php echo $status === 'pendente' ? 'checked' : ''; ?> class="form-radio text-yellow-600">
                                <span class="ml-2 text-gray-700">Pendente</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="status" value="cancelada" <?php echo $status === 'cancelada' ? 'checked' : ''; ?> class="form-radio text-red-600">
                                <span class="ml-2 text-gray-700">Cancelada</span>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Descrição -->
                    <div class="md:col-span-2">
                        <label for="descricao" class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
                        <input type="text" name="descricao" id="descricao" value="<?php echo htmlspecialchars($descricao); ?>" class="form-input w-full" required>
                    </div>
                    
                    <!-- Valor -->
                    <div>
                        <label for="valor" class="block text-sm font-medium text-gray-700 mb-1">Valor (R$)</label>
                        <input type="text" name="valor" id="valor" value="<?php echo htmlspecialchars($valor); ?>" class="form-input w-full" required>
                    </div>
                    
                    <!-- Data da Transação -->
                    <div>
                        <label for="data_transacao" class="block text-sm font-medium text-gray-700 mb-1">Data da Transação</label>
                        <input type="date" name="data_transacao" id="data_transacao" value="<?php echo $data_transacao; ?>" class="form-input w-full" required>
                    </div>
                    
                    <!-- Categoria -->
                    <div>
                        <label for="categoria_id" class="block text-sm font-medium text-gray-700 mb-1">Categoria</label>
                        <select name="categoria_id" id="categoria_id" class="form-select w-full">
                            <option value="">Selecione uma categoria</option>
                            <?php foreach ($categorias as $categoria): ?>
                            <?php if ($tipo === 'todos' || $categoria['tipo'] === $tipo): ?>
                            <option value="<?php echo $categoria['id']; ?>" <?php echo $categoria_id == $categoria['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($categoria['nome']); ?> (<?php echo $categoria['tipo'] === 'receita' ? 'Receita' : 'Despesa'; ?>)
                            </option>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Conta -->
                    <div>
                        <label for="conta_id" class="block text-sm font-medium text-gray-700 mb-1">Conta</label>
                        <select name="conta_id" id="conta_id" class="form-select w-full">
                            <option value="">Selecione uma conta</option>
                            <?php foreach ($contas as $conta): ?>
                            <option value="<?php echo $conta['id']; ?>" <?php echo $conta_id == $conta['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($conta['nome']); ?> (R$ <?php echo number_format($conta['saldo_atual'], 2, ',', '.'); ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Forma de Pagamento -->
                    <div>
                        <label for="forma_pagamento" class="block text-sm font-medium text-gray-700 mb-1">Forma de Pagamento</label>
                        <select name="forma_pagamento" id="forma_pagamento" class="form-select w-full">
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
                    
                    <!-- Comprovante -->
                    <div>
                        <label for="comprovante" class="block text-sm font-medium text-gray-700 mb-1">Comprovante</label>
                        <input type="file" name="comprovante" id="comprovante" class="form-input w-full">
                        <?php if (isset($transacao['comprovante_path']) && !empty($transacao['comprovante_path'])): ?>
                        <p class="mt-1 text-sm text-gray-500">
                            <a href="../<?php echo $transacao['comprovante_path']; ?>" target="_blank" class="text-blue-600 hover:text-blue-800">
                                <i class="fas fa-file-alt mr-1"></i> Ver comprovante atual
                            </a>
                        </p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Observações -->
                    <div class="md:col-span-2">
                        <label for="observacoes" class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                        <textarea name="observacoes" id="observacoes" rows="3" class="form-textarea w-full"><?php echo htmlspecialchars($observacoes); ?></textarea>
                    </div>
                </div>
                
                <div class="mt-6 flex justify-end space-x-3">
                    <a href="transacoes.php<?php echo isset($_GET['tipo']) && $_GET['tipo'] !== 'todos' ? '?tipo=' . $_GET['tipo'] : ''; ?>" class="btn-secondary">
                        Cancelar
                    </a>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save mr-2"></i> Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Formata o campo de valor para moeda
    document.addEventListener('DOMContentLoaded', function() {
        const valorInput = document.getElementById('valor');
        
        valorInput.addEventListener('input', function(e) {
            let value = e.target.value;
            
            // Remove tudo que não é número ou vírgula
            value = value.replace(/[^\d,]/g, '');
            
            // Substitui vírgula por ponto para cálculos
            const numericValue = value.replace(',', '.');
            
            // Formata o valor
            if (numericValue !== '') {
                const formattedValue = parseFloat(numericValue).toLocaleString('pt-BR', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
                
                e.target.value = formattedValue;
            }
        });
        
        // Formata o valor inicial
        if (valorInput.value !== '') {
            const numericValue = valorInput.value.replace(',', '.');
            const formattedValue = parseFloat(numericValue).toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
            
            valorInput.value = formattedValue;
        }
    });
    
    // Filtra as categorias com base no tipo de transação selecionado
    document.addEventListener('DOMContentLoaded', function() {
        const tipoRadios = document.querySelectorAll('input[name="tipo"]');
        const categoriaSelect = document.getElementById('categoria_id');
        const categorias = <?php echo json_encode($categorias); ?>;
        
        function filtrarCategorias() {
            const tipoSelecionado = document.querySelector('input[name="tipo"]:checked').value;
            
            // Limpa o select
            categoriaSelect.innerHTML = '<option value="">Selecione uma categoria</option>';
            
            // Adiciona apenas as categorias do tipo selecionado
            categorias.forEach(function(categoria) {
                if (categoria.tipo === tipoSelecionado || tipoSelecionado === 'transferencia') {
                    const option = document.createElement('option');
                    option.value = categoria.id;
                    option.textContent = categoria.nome + ' (' + (categoria.tipo === 'receita' ? 'Receita' : 'Despesa') + ')';
                    
                    // Seleciona a categoria se for a mesma do formulário
                    if (categoria.id == <?php echo json_encode($categoria_id); ?>) {
                        option.selected = true;
                    }
                    
                    categoriaSelect.appendChild(option);
                }
            });
        }
        
        // Filtra as categorias quando o tipo de transação muda
        tipoRadios.forEach(function(radio) {
            radio.addEventListener('change', filtrarCategorias);
        });
        
        // Filtra as categorias inicialmente
        filtrarCategorias();
    });
</script>
