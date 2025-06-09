<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800">Detalhes da Conta a Pagar</h2>
    </div>
    
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-1">Descrição</h3>
                <p class="text-base text-gray-900"><?php echo htmlspecialchars($conta['descricao']); ?></p>
            </div>
            
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-1">Valor</h3>
                <p class="text-base text-gray-900 font-semibold">R$ <?php echo number_format($conta['valor'], 2, ',', '.'); ?></p>
            </div>
            
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-1">Data de Vencimento</h3>
                <p class="text-base text-gray-900"><?php echo date('d/m/Y', strtotime($conta['data_vencimento'])); ?></p>
            </div>
            
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-1">Status</h3>
                <span class="status-badge <?php echo 'status-' . $conta['status']; ?>">
                    <?php
                    switch ($conta['status']) {
                        case 'pendente':
                            echo '<i class="fas fa-clock mr-1"></i> Pendente';
                            break;
                        case 'pago':
                            echo '<i class="fas fa-check-circle mr-1"></i> Pago';
                            break;
                        case 'cancelado':
                            echo '<i class="fas fa-ban mr-1"></i> Cancelado';
                            break;
                        default:
                            echo htmlspecialchars($conta['status']);
                    }
                    ?>
                </span>
            </div>
            
            <?php if ($conta['data_pagamento']): ?>
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-1">Data de Pagamento</h3>
                <p class="text-base text-gray-900"><?php echo date('d/m/Y', strtotime($conta['data_pagamento'])); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if ($conta['categoria_nome']): ?>
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-1">Categoria</h3>
                <p class="text-base text-gray-900"><?php echo htmlspecialchars($conta['categoria_nome']); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if ($conta['fornecedor_nome']): ?>
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-1">Fornecedor</h3>
                <p class="text-base text-gray-900"><?php echo htmlspecialchars($conta['fornecedor_nome']); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if ($conta['forma_pagamento']): ?>
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-1">Forma de Pagamento</h3>
                <p class="text-base text-gray-900">
                    <?php
                    switch ($conta['forma_pagamento']) {
                        case 'dinheiro':
                            echo 'Dinheiro';
                            break;
                        case 'pix':
                            echo 'PIX';
                            break;
                        case 'cartao_credito':
                            echo 'Cartão de Crédito';
                            break;
                        case 'cartao_debito':
                            echo 'Cartão de Débito';
                            break;
                        case 'boleto':
                            echo 'Boleto';
                            break;
                        case 'transferencia':
                            echo 'Transferência Bancária';
                            break;
                        case 'outro':
                            echo 'Outro';
                            break;
                        default:
                            echo htmlspecialchars($conta['forma_pagamento']);
                    }
                    ?>
                </p>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if ($conta['observacoes']): ?>
        <div class="mb-6">
            <h3 class="text-sm font-medium text-gray-500 mb-1">Observações</h3>
            <p class="text-base text-gray-900"><?php echo nl2br(htmlspecialchars($conta['observacoes'])); ?></p>
        </div>
        <?php endif; ?>
        
        <?php if (isset($conta['comprovante_path']) && !empty($conta['comprovante_path'])): ?>
        <div class="mb-6">
            <h3 class="text-sm font-medium text-gray-500 mb-1">Comprovante</h3>
            <div class="mt-2">
                <a href="../<?php echo htmlspecialchars($conta['comprovante_path']); ?>" target="_blank" class="text-blue-600 hover:text-blue-800 inline-flex items-center">
                    <i class="fas fa-file-alt mr-1"></i> Visualizar comprovante
                </a>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="flex justify-between items-center mt-8">
            <div>
                <a href="contas_pagar.php" class="text-blue-600 hover:text-blue-800 inline-flex items-center">
                    <i class="fas fa-arrow-left mr-1"></i> Voltar para a lista
                </a>
            </div>
            
            <div class="flex space-x-3">
                <?php if ($conta['status'] === 'pendente'): ?>
                <a href="contas_pagar.php?action=pagar&id=<?php echo $conta['id']; ?>" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 inline-flex items-center">
                    <i class="fas fa-money-bill-wave mr-1"></i> Pagar
                </a>
                <?php endif; ?>
                
                <a href="contas_pagar.php?action=editar&id=<?php echo $conta['id']; ?>" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 inline-flex items-center">
                    <i class="fas fa-edit mr-1"></i> Editar
                </a>
                
                <button onclick="confirmarExclusao(<?php echo $conta['id']; ?>)" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 inline-flex items-center">
                    <i class="fas fa-trash-alt mr-1"></i> Excluir
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmação de exclusão -->
<div id="modal-exclusao" class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 max-w-md w-full">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Confirmar Exclusão</h3>
        <p class="text-gray-700 mb-6">Tem certeza que deseja excluir esta conta a pagar? Esta ação não pode ser desfeita.</p>
        <div class="flex justify-end space-x-3">
            <button id="btn-cancelar" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">Cancelar</button>
            <a id="btn-excluir" href="#" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">Excluir</a>
        </div>
    </div>
</div>

<script>
    function confirmarExclusao(id) {
        const modal = document.getElementById('modal-exclusao');
        const btnExcluir = document.getElementById('btn-excluir');
        const btnCancelar = document.getElementById('btn-cancelar');
        
        modal.classList.remove('hidden');
        btnExcluir.href = `contas_pagar.php?action=excluir&id=${id}`;
        
        btnCancelar.addEventListener('click', function() {
            modal.classList.add('hidden');
        });
        
        // Fechar modal ao clicar fora dele
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.classList.add('hidden');
            }
        });
    }
</script>
