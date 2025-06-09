<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800">Receber Conta</h2>
    </div>
    
    <div class="p-6">
        <div class="bg-blue-50 border-l-4 border-blue-500 text-blue-700 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-info-circle"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm">
                        Você está registrando o recebimento da conta <strong><?php echo htmlspecialchars($conta['descricao']); ?></strong> 
                        no valor de <strong>R$ <?php echo number_format($conta['valor'], 2, ',', '.'); ?></strong>.
                    </p>
                </div>
            </div>
        </div>
        
        <form action="contas_receber.php?action=salvar" method="post" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?php echo $conta['id']; ?>">
            <input type="hidden" name="descricao" value="<?php echo htmlspecialchars($conta['descricao']); ?>">
            <input type="hidden" name="valor" value="<?php echo htmlspecialchars($conta['valor']); ?>">
            <input type="hidden" name="data_vencimento" value="<?php echo htmlspecialchars($conta['data_vencimento']); ?>">
            <input type="hidden" name="cliente_id" value="<?php echo htmlspecialchars($conta['cliente_id'] ?? ''); ?>">
            <input type="hidden" name="cliente_nome" value="<?php echo htmlspecialchars($conta['cliente_nome'] ?? ''); ?>">
            <input type="hidden" name="categoria_id" value="<?php echo htmlspecialchars($conta['categoria_id'] ?? ''); ?>">
            <input type="hidden" name="observacoes" value="<?php echo htmlspecialchars($conta['observacoes'] ?? ''); ?>">
            <input type="hidden" name="status" value="recebido">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label for="data_recebimento" class="block text-sm font-medium text-gray-700 mb-1">Data de Recebimento <span class="text-red-600">*</span></label>
                    <input type="date" name="data_recebimento" id="data_recebimento" value="<?php echo date('Y-m-d'); ?>" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                </div>
                
                <div>
                    <label for="forma_recebimento" class="block text-sm font-medium text-gray-700 mb-1">Forma de Recebimento <span class="text-red-600">*</span></label>
                    <select name="forma_recebimento" id="forma_recebimento" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                        <option value="">Selecione</option>
                        <option value="dinheiro" <?php echo $conta['forma_recebimento'] === 'dinheiro' ? 'selected' : ''; ?>>Dinheiro</option>
                        <option value="pix" <?php echo $conta['forma_recebimento'] === 'pix' ? 'selected' : ''; ?>>PIX</option>
                        <option value="cartao_credito" <?php echo $conta['forma_recebimento'] === 'cartao_credito' ? 'selected' : ''; ?>>Cartão de Crédito</option>
                        <option value="cartao_debito" <?php echo $conta['forma_recebimento'] === 'cartao_debito' ? 'selected' : ''; ?>>Cartão de Débito</option>
                        <option value="boleto" <?php echo $conta['forma_recebimento'] === 'boleto' ? 'selected' : ''; ?>>Boleto</option>
                        <option value="transferencia" <?php echo $conta['forma_recebimento'] === 'transferencia' ? 'selected' : ''; ?>>Transferência Bancária</option>
                        <option value="outro" <?php echo $conta['forma_recebimento'] === 'outro' ? 'selected' : ''; ?>>Outro</option>
                    </select>
                </div>
            </div>
            
            <div class="mb-6">
                <label for="comprovante" class="block text-sm font-medium text-gray-700 mb-1">Comprovante</label>
                <input type="file" name="comprovante" id="comprovante" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                <p class="mt-1 text-sm text-gray-500">Opcional. Anexe um comprovante do recebimento (PDF, imagem, etc).</p>
            </div>
            
            <div class="flex justify-end space-x-3">
                <a href="contas_receber.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">Cancelar</a>
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">Confirmar Recebimento</button>
            </div>
        </form>
    </div>
</div>
