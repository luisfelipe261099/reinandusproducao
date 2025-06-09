<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
        <h2 class="text-lg font-semibold text-gray-800">Detalhes da Conta a Receber</h2>
        
        <div class="flex space-x-2">
            <?php if ($conta['status'] === 'pendente'): ?>
            <a href="contas_receber.php?action=receber&id=<?php echo $conta['id']; ?>" class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded inline-flex items-center">
                <i class="fas fa-hand-holding-usd mr-2"></i> Receber
            </a>
            <?php endif; ?>
            
            <a href="contas_receber.php?action=editar&id=<?php echo $conta['id']; ?>" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded inline-flex items-center">
                <i class="fas fa-edit mr-2"></i> Editar
            </a>
            
            <a href="#" onclick="confirmarExclusao(<?php echo $conta['id']; ?>); return false;" class="bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded inline-flex items-center">
                <i class="fas fa-trash-alt mr-2"></i> Excluir
            </a>
        </div>
    </div>
    
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-1">Descrição</h3>
                <p class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($conta['descricao']); ?></p>
            </div>
            
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-1">Valor</h3>
                <p class="text-lg font-medium text-green-600">R$ <?php echo number_format($conta['valor'], 2, ',', '.'); ?></p>
            </div>
            
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-1">Data de Vencimento</h3>
                <p class="text-lg font-medium text-gray-900">
                    <?php echo date('d/m/Y', strtotime($conta['data_vencimento'])); ?>
                    <?php
                    $hoje = date('Y-m-d');
                    if ($conta['status'] === 'pendente' && $conta['data_vencimento'] < $hoje) {
                        echo '<span class="text-sm text-red-600 ml-2">(vencido)</span>';
                    }
                    ?>
                </p>
            </div>
            
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-1">Status</h3>
                <span class="status-badge <?php echo 'status-' . $conta['status']; ?>">
                    <?php
                    switch ($conta['status']) {
                        case 'pendente':
                            echo '<i class="fas fa-clock mr-1"></i> Pendente';
                            break;
                        case 'recebido':
                            echo '<i class="fas fa-check-circle mr-1"></i> Recebido';
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
            
            <?php if ($conta['data_recebimento']): ?>
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-1">Data de Recebimento</h3>
                <p class="text-lg font-medium text-gray-900"><?php echo date('d/m/Y', strtotime($conta['data_recebimento'])); ?></p>
            </div>
            <?php endif; ?>
            
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-1">Cliente</h3>
                <p class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($conta['cliente_nome'] ?? 'Não informado'); ?></p>
            </div>
            
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-1">Categoria</h3>
                <p class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($conta['categoria_nome'] ?? 'Não categorizado'); ?></p>
            </div>
            
            <?php if ($conta['forma_recebimento']): ?>
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-1">Forma de Recebimento</h3>
                <p class="text-lg font-medium text-gray-900">
                    <?php
                    $formas = [
                        'dinheiro' => 'Dinheiro',
                        'pix' => 'PIX',
                        'cartao_credito' => 'Cartão de Crédito',
                        'cartao_debito' => 'Cartão de Débito',
                        'boleto' => 'Boleto',
                        'transferencia' => 'Transferência Bancária',
                        'outro' => 'Outro'
                    ];
                    echo htmlspecialchars($formas[$conta['forma_recebimento']] ?? $conta['forma_recebimento']);
                    ?>
                </p>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if ($conta['observacoes']): ?>
        <div class="mb-6">
            <h3 class="text-sm font-medium text-gray-500 mb-1">Observações</h3>
            <div class="bg-gray-50 p-4 rounded-md">
                <p class="text-gray-900 whitespace-pre-line"><?php echo htmlspecialchars($conta['observacoes']); ?></p>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (isset($conta['comprovante_path']) && !empty($conta['comprovante_path'])): ?>
        <div class="mb-6">
            <h3 class="text-sm font-medium text-gray-500 mb-1">Comprovante</h3>
            <div class="mt-2">
                <a href="../<?php echo htmlspecialchars($conta['comprovante_path']); ?>" target="_blank" class="bg-blue-100 text-blue-700 px-4 py-2 rounded-md inline-flex items-center hover:bg-blue-200">
                    <i class="fas fa-file-alt mr-2"></i> Visualizar Comprovante
                </a>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="mt-8 pt-6 border-t border-gray-200">
            <div class="flex justify-between items-center">
                <div class="text-sm text-gray-500">
                    <p>Criado em: <?php echo date('d/m/Y H:i', strtotime($conta['created_at'])); ?></p>
                    <?php if ($conta['updated_at']): ?>
                    <p>Última atualização: <?php echo date('d/m/Y H:i', strtotime($conta['updated_at'])); ?></p>
                    <?php endif; ?>
                </div>
                
                <a href="contas_receber.php" class="text-blue-600 hover:text-blue-800">
                    <i class="fas fa-arrow-left mr-1"></i> Voltar para a lista
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmação de exclusão -->
<div id="modal-exclusao" class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 max-w-md w-full">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Confirmar Exclusão</h3>
        <p class="text-gray-700 mb-6">Tem certeza que deseja excluir esta conta a receber? Esta ação não pode ser desfeita.</p>
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
        btnExcluir.href = `contas_receber.php?action=excluir&id=${id}`;
        
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
