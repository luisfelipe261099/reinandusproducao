<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800">Registrar Pagamento de Mensalidade</h2>
    </div>
    
    <div class="p-6">
        <div class="bg-blue-50 border-l-4 border-blue-500 text-blue-700 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-info-circle"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm">
                        Você está registrando o pagamento da mensalidade <strong><?php echo htmlspecialchars($mensalidade['descricao']); ?></strong> 
                        do aluno <strong><?php echo htmlspecialchars($mensalidade['aluno_nome']); ?></strong>
                        no valor de <strong>R$ <?php echo number_format($mensalidade['valor'], 2, ',', '.'); ?></strong>.
                    </p>
                </div>
            </div>
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
        $data_pagamento = $form_data['data_pagamento'] ?? date('Y-m-d');
        $forma_pagamento = $form_data['forma_pagamento'] ?? $mensalidade['forma_pagamento'] ?? '';
        $valor_pago = $form_data['valor_pago'] ?? $mensalidade['valor'];
        $desconto = $form_data['desconto'] ?? $mensalidade['desconto'] ?? '';
        $acrescimo = $form_data['acrescimo'] ?? $mensalidade['acrescimo'] ?? '';
        ?>
        
        <form action="mensalidades.php?action=registrar_pagamento" method="post" class="space-y-6">
            <input type="hidden" name="id" value="<?php echo $mensalidade['id']; ?>">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
                    <label for="valor_pago" class="block text-sm font-medium text-gray-700 mb-1">Valor Pago (R$) <span class="text-red-600">*</span></label>
                    <input type="text" name="valor_pago" id="valor_pago" value="<?php echo number_format($valor_pago, 2, ',', '.'); ?>" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                </div>
                
                <div>
                    <label for="desconto" class="block text-sm font-medium text-gray-700 mb-1">Desconto (R$)</label>
                    <input type="text" name="desconto" id="desconto" value="<?php echo number_format($desconto, 2, ',', '.'); ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                </div>
                
                <div>
                    <label for="acrescimo" class="block text-sm font-medium text-gray-700 mb-1">Acréscimo (R$)</label>
                    <input type="text" name="acrescimo" id="acrescimo" value="<?php echo number_format($acrescimo, 2, ',', '.'); ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Valor Total</label>
                    <div id="valor_total" class="text-xl font-bold text-green-600 py-2">
                        R$ <?php echo number_format($mensalidade['valor'] + $acrescimo - $desconto, 2, ',', '.'); ?>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 pt-4">
                <a href="mensalidades.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">Cancelar</a>
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                    <i class="fas fa-check mr-2"></i> Confirmar Pagamento
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Formatar campos de valor
    document.querySelectorAll('input[name="valor_pago"], input[name="desconto"], input[name="acrescimo"]').forEach(function(input) {
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = (parseInt(value) / 100).toFixed(2);
            e.target.value = value.replace('.', ',');
            
            // Atualiza o valor total
            calcularValorTotal();
        });
    });
    
    // Calcular valor total
    function calcularValorTotal() {
        const valorOriginal = <?php echo $mensalidade['valor']; ?>;
        const valorPago = parseFloat(document.getElementById('valor_pago').value.replace(',', '.')) || valorOriginal;
        const desconto = parseFloat(document.getElementById('desconto').value.replace(',', '.')) || 0;
        const acrescimo = parseFloat(document.getElementById('acrescimo').value.replace(',', '.')) || 0;
        
        const valorTotal = valorPago + acrescimo - desconto;
        
        document.getElementById('valor_total').textContent = 'R$ ' + valorTotal.toFixed(2).replace('.', ',');
    }
    
    // Inicializa o cálculo
    calcularValorTotal();
</script>
