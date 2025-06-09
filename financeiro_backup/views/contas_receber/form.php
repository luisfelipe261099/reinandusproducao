<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800"><?php echo isset($conta) ? 'Editar Conta a Receber' : 'Nova Conta a Receber'; ?></h2>
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
    $id = $conta['id'] ?? $form_data['id'] ?? '';
    $descricao = $form_data['descricao'] ?? $conta['descricao'] ?? '';
    $valor = $form_data['valor'] ?? $conta['valor'] ?? '';
    $data_vencimento = $form_data['data_vencimento'] ?? $conta['data_vencimento'] ?? date('Y-m-d');
    $data_recebimento = $form_data['data_recebimento'] ?? $conta['data_recebimento'] ?? '';
    $cliente_id = $form_data['cliente_id'] ?? $conta['cliente_id'] ?? '';
    $cliente_nome = $form_data['cliente_nome'] ?? $conta['cliente_nome'] ?? '';
    $categoria_id = $form_data['categoria_id'] ?? $conta['categoria_id'] ?? '';
    $forma_recebimento = $form_data['forma_recebimento'] ?? $conta['forma_recebimento'] ?? '';
    $status = $form_data['status'] ?? $conta['status'] ?? 'pendente';
    $observacoes = $form_data['observacoes'] ?? $conta['observacoes'] ?? '';
    ?>
    
    <form action="contas_receber.php?action=salvar" method="post" enctype="multipart/form-data" class="p-6">
        <?php if ($id): ?>
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <?php endif; ?>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
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
                <label for="categoria_id" class="block text-sm font-medium text-gray-700 mb-1">Categoria</label>
                <select name="categoria_id" id="categoria_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    <option value="">Selecione uma categoria</option>
                    <?php foreach ($categorias as $categoria): ?>
                    <option value="<?php echo $categoria['id']; ?>" <?php echo $categoria_id == $categoria['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($categoria['nome']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label for="cliente_nome" class="block text-sm font-medium text-gray-700 mb-1">Cliente</label>
                <input type="text" name="cliente_nome" id="cliente_nome" value="<?php echo htmlspecialchars($cliente_nome); ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                <input type="hidden" name="cliente_id" id="cliente_id" value="<?php echo htmlspecialchars($cliente_id); ?>">
            </div>
            
            <div>
                <label for="forma_recebimento" class="block text-sm font-medium text-gray-700 mb-1">Forma de Recebimento</label>
                <select name="forma_recebimento" id="forma_recebimento" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    <option value="">Selecione</option>
                    <option value="dinheiro" <?php echo $forma_recebimento === 'dinheiro' ? 'selected' : ''; ?>>Dinheiro</option>
                    <option value="pix" <?php echo $forma_recebimento === 'pix' ? 'selected' : ''; ?>>PIX</option>
                    <option value="cartao_credito" <?php echo $forma_recebimento === 'cartao_credito' ? 'selected' : ''; ?>>Cartão de Crédito</option>
                    <option value="cartao_debito" <?php echo $forma_recebimento === 'cartao_debito' ? 'selected' : ''; ?>>Cartão de Débito</option>
                    <option value="boleto" <?php echo $forma_recebimento === 'boleto' ? 'selected' : ''; ?>>Boleto</option>
                    <option value="transferencia" <?php echo $forma_recebimento === 'transferencia' ? 'selected' : ''; ?>>Transferência Bancária</option>
                    <option value="outro" <?php echo $forma_recebimento === 'outro' ? 'selected' : ''; ?>>Outro</option>
                </select>
            </div>
            
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status <span class="text-red-600">*</span></label>
                <select name="status" id="status" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    <option value="pendente" <?php echo $status === 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                    <option value="recebido" <?php echo $status === 'recebido' ? 'selected' : ''; ?>>Recebido</option>
                    <option value="cancelado" <?php echo $status === 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                </select>
            </div>
            
            <div id="data_recebimento_container" class="<?php echo $status !== 'recebido' ? 'hidden' : ''; ?>">
                <label for="data_recebimento" class="block text-sm font-medium text-gray-700 mb-1">Data de Recebimento</label>
                <input type="date" name="data_recebimento" id="data_recebimento" value="<?php echo htmlspecialchars($data_recebimento); ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
            </div>
        </div>
        
        <div class="mb-6">
            <label for="observacoes" class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
            <textarea name="observacoes" id="observacoes" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50"><?php echo htmlspecialchars($observacoes); ?></textarea>
        </div>
        
        <div class="mb-6">
            <label for="comprovante" class="block text-sm font-medium text-gray-700 mb-1">Comprovante</label>
            <input type="file" name="comprovante" id="comprovante" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
            <?php if (isset($conta['comprovante_path']) && !empty($conta['comprovante_path'])): ?>
            <div class="mt-2">
                <a href="../<?php echo htmlspecialchars($conta['comprovante_path']); ?>" target="_blank" class="text-blue-600 hover:text-blue-800">
                    <i class="fas fa-file-alt mr-1"></i> Ver comprovante atual
                </a>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="flex justify-end space-x-3">
            <a href="contas_receber.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">Cancelar</a>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Salvar</button>
        </div>
    </form>
</div>

<script>
    // Mostrar/ocultar campo de data de recebimento conforme o status
    document.getElementById('status').addEventListener('change', function() {
        const dataRecebimentoContainer = document.getElementById('data_recebimento_container');
        if (this.value === 'recebido') {
            dataRecebimentoContainer.classList.remove('hidden');
            document.getElementById('data_recebimento').value = document.getElementById('data_recebimento').value || '<?php echo date('Y-m-d'); ?>';
        } else {
            dataRecebimentoContainer.classList.add('hidden');
        }
    });
    
    // Formatar campo de valor
    document.getElementById('valor').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        value = (parseInt(value) / 100).toFixed(2);
        e.target.value = value.replace('.', ',');
    });
</script>
