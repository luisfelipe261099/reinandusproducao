<div class="mb-6">
    <div class="flex justify-between items-center mb-4">
        <div>
            <a href="polos_financeiro.php" class="text-blue-600 hover:text-blue-800">
                <i class="fas fa-arrow-left mr-1"></i> Voltar para a lista
            </a>
        </div>
        
        <div>
            <a href="polos_financeiro.php?action=historico&id=<?php echo $polo['id']; ?>" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded inline-flex items-center">
                <i class="fas fa-history mr-2"></i> Ver Histórico
            </a>
        </div>
    </div>
    
    <!-- Informações do Polo -->
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Informações do Polo</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-1">Nome</h3>
                <p class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($polo['nome']); ?></p>
            </div>
            
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-1">Razão Social</h3>
                <p class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($polo['razao_social'] ?? 'Não informado'); ?></p>
            </div>
            
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-1">CNPJ</h3>
                <p class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($polo['cnpj'] ?? 'Não informado'); ?></p>
            </div>
            
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-1">Status</h3>
                <p class="text-lg font-medium">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $polo['status'] === 'ativo' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                        <?php echo $polo['status'] === 'ativo' ? 'Ativo' : 'Inativo'; ?>
                    </span>
                </p>
            </div>
            
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-1">Data de Início da Parceria</h3>
                <p class="text-lg font-medium text-gray-900">
                    <?php echo $polo['data_inicio_parceria'] ? date('d/m/Y', strtotime($polo['data_inicio_parceria'])) : 'Não informado'; ?>
                </p>
            </div>
            
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-1">Data de Fim do Contrato</h3>
                <p class="text-lg font-medium text-gray-900">
                    <?php echo $polo['data_fim_contrato'] ? date('d/m/Y', strtotime($polo['data_fim_contrato'])) : 'Não informado'; ?>
                </p>
            </div>
        </div>
    </div>
    
    <!-- Tipos de Polo -->
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold text-gray-800">Tipos de Polo</h2>
            
            <button type="button" id="btn-adicionar-tipo" class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded inline-flex items-center">
                <i class="fas fa-plus mr-2"></i> Adicionar Tipo
            </button>
        </div>
        
        <?php if (empty($configuracoes)): ?>
        <div class="text-center text-gray-500 py-4">
            <p>Nenhum tipo de polo configurado.</p>
            <p class="mt-2 text-sm">Clique no botão "Adicionar Tipo" para configurar um tipo de polo.</p>
        </div>
        <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <?php foreach ($configuracoes as $config): ?>
            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                <div class="flex justify-between items-start mb-3">
                    <h3 class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($config['tipo_nome']); ?></h3>
                    <a href="polos_financeiro.php?action=remover_tipo&polo_id=<?php echo $polo['id']; ?>&tipo_id=<?php echo $config['tipo_polo_id']; ?>" class="text-red-600 hover:text-red-800" onclick="return confirm('Tem certeza que deseja remover este tipo de polo?');">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
                
                <form action="polos_financeiro.php?action=salvar" method="post" class="space-y-4">
                    <input type="hidden" name="polo_id" value="<?php echo $polo['id']; ?>">
                    <input type="hidden" name="tipo_polo_id" value="<?php echo $config['tipo_polo_id']; ?>">
                    
                    <div>
                        <label for="taxa_inicial_<?php echo $config['tipo_polo_id']; ?>" class="block text-sm font-medium text-gray-700 mb-1">Taxa Inicial (R$)</label>
                        <input type="text" name="taxa_inicial" id="taxa_inicial_<?php echo $config['tipo_polo_id']; ?>" value="<?php echo number_format($config['taxa_inicial'] ?? 0, 2, ',', '.'); ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    </div>
                    
                    <div>
                        <label for="valor_por_documento_<?php echo $config['tipo_polo_id']; ?>" class="block text-sm font-medium text-gray-700 mb-1">Valor por Documento (R$)</label>
                        <input type="text" name="valor_por_documento" id="valor_por_documento_<?php echo $config['tipo_polo_id']; ?>" value="<?php echo number_format($config['valor_por_documento'] ?? 0, 2, ',', '.'); ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" name="taxa_inicial_paga" id="taxa_inicial_paga_<?php echo $config['tipo_polo_id']; ?>" <?php echo $config['taxa_inicial_paga'] ? 'checked' : ''; ?> class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                        <label for="taxa_inicial_paga_<?php echo $config['tipo_polo_id']; ?>" class="ml-2 block text-sm text-gray-700">Taxa Inicial Paga</label>
                    </div>
                    
                    <div>
                        <label for="data_pagamento_taxa_<?php echo $config['tipo_polo_id']; ?>" class="block text-sm font-medium text-gray-700 mb-1">Data de Pagamento da Taxa</label>
                        <input type="date" name="data_pagamento_taxa" id="data_pagamento_taxa_<?php echo $config['tipo_polo_id']; ?>" value="<?php echo $config['data_pagamento_taxa'] ?? ''; ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    </div>
                    
                    <div>
                        <label for="pacotes_adquiridos_<?php echo $config['tipo_polo_id']; ?>" class="block text-sm font-medium text-gray-700 mb-1">Pacotes Adquiridos</label>
                        <input type="number" name="pacotes_adquiridos" id="pacotes_adquiridos_<?php echo $config['tipo_polo_id']; ?>" value="<?php echo $config['pacotes_adquiridos'] ?? 0; ?>" min="0" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    </div>
                    
                    <div>
                        <label for="observacoes_<?php echo $config['tipo_polo_id']; ?>" class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                        <textarea name="observacoes" id="observacoes_<?php echo $config['tipo_polo_id']; ?>" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50"><?php echo htmlspecialchars($config['observacoes'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="pt-2">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded">
                            <i class="fas fa-save mr-2"></i> Salvar Configurações
                        </button>
                    </div>
                </form>
                
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <h4 class="text-sm font-medium text-gray-500 mb-1">Documentos Disponíveis</h4>
                            <p class="text-lg font-medium text-green-600"><?php echo $config['documentos_disponiveis'] ?? 0; ?></p>
                        </div>
                        
                        <div>
                            <h4 class="text-sm font-medium text-gray-500 mb-1">Documentos Emitidos</h4>
                            <p class="text-lg font-medium text-blue-600"><?php echo $config['documentos_emitidos'] ?? 0; ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal para adicionar tipo de polo -->
<div id="modal-adicionar-tipo" class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 max-w-md w-full">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Adicionar Tipo de Polo</h3>
        
        <form action="polos_financeiro.php?action=adicionar_tipo" method="post">
            <input type="hidden" name="polo_id" value="<?php echo $polo['id']; ?>">
            
            <div class="mb-4">
                <label for="tipo_polo_id" class="block text-sm font-medium text-gray-700 mb-1">Tipo de Polo</label>
                <select name="tipo_polo_id" id="tipo_polo_id" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    <option value="">Selecione um tipo</option>
                    <?php foreach ($tipos_polos as $tipo): ?>
                    <option value="<?php echo $tipo['id']; ?>"><?php echo htmlspecialchars($tipo['nome']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" id="btn-cancelar" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Adicionar</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Modal de adicionar tipo
    const modalAdicionarTipo = document.getElementById('modal-adicionar-tipo');
    const btnAdicionarTipo = document.getElementById('btn-adicionar-tipo');
    const btnCancelar = document.getElementById('btn-cancelar');
    
    btnAdicionarTipo.addEventListener('click', function() {
        modalAdicionarTipo.classList.remove('hidden');
    });
    
    btnCancelar.addEventListener('click', function() {
        modalAdicionarTipo.classList.add('hidden');
    });
    
    // Fechar modal ao clicar fora dele
    modalAdicionarTipo.addEventListener('click', function(e) {
        if (e.target === modalAdicionarTipo) {
            modalAdicionarTipo.classList.add('hidden');
        }
    });
    
    // Formatar campos de valor
    document.querySelectorAll('input[name="taxa_inicial"], input[name="valor_por_documento"]').forEach(function(input) {
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = (parseInt(value) / 100).toFixed(2);
            e.target.value = value.replace('.', ',');
        });
    });
</script>
