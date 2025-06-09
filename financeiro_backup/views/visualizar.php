<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800"><?php echo $titulo_pagina; ?></h1>
        
        <div class="flex space-x-2">
            <?php if (Auth::hasPermission('financeiro', 'editar')): ?>
            <a href="transacoes.php?action=editar&id=<?php echo $transacao['id']; ?>" class="btn-primary">
                <i class="fas fa-edit mr-2"></i> Editar
            </a>
            <?php endif; ?>
            <a href="transacoes.php" class="btn-secondary">
                <i class="fas fa-arrow-left mr-2"></i> Voltar
            </a>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-800">Detalhes da Transação</h2>
            <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full 
                <?php echo $transacao['tipo'] === 'receita' ? 'bg-green-100 text-green-800' : 
                        ($transacao['tipo'] === 'despesa' ? 'bg-red-100 text-red-800' : 
                        'bg-blue-100 text-blue-800'); ?>">
                <?php echo $transacao['tipo'] === 'receita' ? 'Receita' : 
                        ($transacao['tipo'] === 'despesa' ? 'Despesa' : 'Transferência'); ?>
            </span>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Descrição</h3>
                    <p class="mt-1 text-lg text-gray-900"><?php echo htmlspecialchars($transacao['descricao']); ?></p>
                </div>
                
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Valor</h3>
                    <p class="mt-1 text-lg font-bold <?php echo $transacao['tipo'] === 'receita' ? 'text-green-600' : 'text-red-600'; ?>">
                        <?php echo $transacao['tipo'] === 'receita' ? '+' : '-'; ?> R$ <?php echo number_format($transacao['valor'], 2, ',', '.'); ?>
                    </p>
                </div>
                
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Data da Transação</h3>
                    <p class="mt-1 text-lg text-gray-900"><?php echo date('d/m/Y', strtotime($transacao['data_transacao'])); ?></p>
                </div>
                
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Status</h3>
                    <p class="mt-1">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                            <?php echo $transacao['status'] === 'efetivada' ? 'bg-green-100 text-green-800' : 
                                    ($transacao['status'] === 'pendente' ? 'bg-yellow-100 text-yellow-800' : 
                                    'bg-red-100 text-red-800'); ?>">
                            <?php echo $transacao['status'] === 'efetivada' ? 'Efetivada' : 
                                    ($transacao['status'] === 'pendente' ? 'Pendente' : 'Cancelada'); ?>
                        </span>
                    </p>
                </div>
                
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Categoria</h3>
                    <p class="mt-1 text-lg text-gray-900"><?php echo htmlspecialchars($transacao['categoria_nome'] ?? 'Sem categoria'); ?></p>
                </div>
                
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Conta</h3>
                    <p class="mt-1 text-lg text-gray-900"><?php echo htmlspecialchars($transacao['conta_nome'] ?? 'Sem conta'); ?></p>
                </div>
                
                <?php if (!empty($transacao['forma_pagamento'])): ?>
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Forma de Pagamento</h3>
                    <p class="mt-1 text-lg text-gray-900">
                        <?php 
                        $formas_pagamento = [
                            'dinheiro' => 'Dinheiro',
                            'pix' => 'PIX',
                            'cartao_credito' => 'Cartão de Crédito',
                            'cartao_debito' => 'Cartão de Débito',
                            'boleto' => 'Boleto',
                            'transferencia' => 'Transferência Bancária',
                            'outro' => 'Outro'
                        ];
                        echo $formas_pagamento[$transacao['forma_pagamento']] ?? $transacao['forma_pagamento'];
                        ?>
                    </p>
                </div>
                <?php endif; ?>
                
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Data de Registro</h3>
                    <p class="mt-1 text-lg text-gray-900"><?php echo date('d/m/Y H:i', strtotime($transacao['created_at'])); ?></p>
                </div>
                
                <?php if (!empty($transacao['updated_at'])): ?>
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Última Atualização</h3>
                    <p class="mt-1 text-lg text-gray-900"><?php echo date('d/m/Y H:i', strtotime($transacao['updated_at'])); ?></p>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($transacao['observacoes'])): ?>
                <div class="md:col-span-2">
                    <h3 class="text-sm font-medium text-gray-500">Observações</h3>
                    <p class="mt-1 text-lg text-gray-900"><?php echo nl2br(htmlspecialchars($transacao['observacoes'])); ?></p>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($transacao['comprovante_path'])): ?>
                <div class="md:col-span-2">
                    <h3 class="text-sm font-medium text-gray-500">Comprovante</h3>
                    <div class="mt-2">
                        <a href="../<?php echo $transacao['comprovante_path']; ?>" target="_blank" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            <i class="fas fa-file-download mr-2"></i> Baixar Comprovante
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="flex justify-between">
        <a href="transacoes.php" class="text-blue-600 hover:text-blue-800">
            <i class="fas fa-arrow-left mr-1"></i> Voltar para a lista de transações
        </a>
        
        <?php if (Auth::hasPermission('financeiro', 'excluir')): ?>
        <a href="javascript:void(0);" onclick="confirmarExclusao(<?php echo $transacao['id']; ?>)" class="text-red-600 hover:text-red-800">
            <i class="fas fa-trash mr-1"></i> Excluir Transação
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Modal de confirmação de exclusão -->
<div id="modal-exclusao" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                        <i class="fas fa-exclamation-triangle text-red-600"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            Confirmar Exclusão
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">
                                Tem certeza que deseja excluir esta transação? Esta ação não pode ser desfeita.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <a id="btn-confirmar-exclusao" href="#" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                    Confirmar
                </a>
                <button type="button" onclick="fecharModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Cancelar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function confirmarExclusao(id) {
        const modal = document.getElementById('modal-exclusao');
        const btnConfirmar = document.getElementById('btn-confirmar-exclusao');
        
        modal.classList.remove('hidden');
        btnConfirmar.href = 'transacoes.php?action=excluir&id=' + id;
    }
    
    function fecharModal() {
        const modal = document.getElementById('modal-exclusao');
        modal.classList.add('hidden');
    }
</script>
