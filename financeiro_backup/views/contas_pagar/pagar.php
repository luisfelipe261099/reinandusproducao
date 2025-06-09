<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800">Pagar Conta</h2>
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
    $data_vencimento = $form_data['data_vencimento'] ?? $conta['data_vencimento'] ?? '';
    $data_pagamento = $form_data['data_pagamento'] ?? $conta['data_pagamento'] ?? date('Y-m-d');
    $forma_pagamento = $form_data['forma_pagamento'] ?? $conta['forma_pagamento'] ?? '';
    $categoria_id = $form_data['categoria_id'] ?? $conta['categoria_id'] ?? '';
    $observacoes = $form_data['observacoes'] ?? $conta['observacoes'] ?? '';
    ?>
    
    <div class="p-6">
        <div class="bg-blue-50 border-l-4 border-blue-500 text-blue-700 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-info-circle"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium">Você está registrando o pagamento da seguinte conta:</p>
                    <p class="text-sm mt-1"><strong>Descrição:</strong> <?php echo htmlspecialchars($descricao); ?></p>
                    <p class="text-sm mt-1"><strong>Valor:</strong> R$ <?php echo number_format($valor, 2, ',', '.'); ?></p>
                    <p class="text-sm mt-1"><strong>Vencimento:</strong> <?php echo date('d/m/Y', strtotime($data_vencimento)); ?></p>
                </div>
            </div>
        </div>
        
        <form action="contas_pagar.php?action=salvar" method="post" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?php echo $id; ?>">
            <input type="hidden" name="status" value="pago">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label for="data_pagamento" class="block text-sm font-medium text-gray-700 mb-1">Data de Pagamento <span class="text-red-600">*</span></label>
                    <input type="date" name="data_pagamento" id="data_pagamento" value="<?php echo htmlspecialchars($data_pagamento); ?>" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                </div>
                
                <div>
                    <label for="forma_pagamento" class="block text-sm font-medium text-gray-700 mb-1">Forma de Pagamento <span class="text-red-600">*</span></label>
                    <select name="forma_pagamento" id="forma_pagamento" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
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
                    <label for="comprovante" class="block text-sm font-medium text-gray-700 mb-1">Comprovante</label>
                    <input type="file" name="comprovante" id="comprovante" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                </div>
            </div>
            
            <div class="mb-6">
                <label for="observacoes" class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                <textarea name="observacoes" id="observacoes" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50"><?php echo htmlspecialchars($observacoes); ?></textarea>
            </div>
            
            <div class="flex justify-end space-x-3">
                <a href="contas_pagar.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">Cancelar</a>
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                    <i class="fas fa-money-bill-wave mr-1"></i> Confirmar Pagamento
                </button>
            </div>
        </form>
    </div>
</div>
