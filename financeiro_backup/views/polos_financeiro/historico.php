<div class="mb-6">
    <div class="flex justify-between items-center mb-4">
        <div>
            <a href="polos_financeiro.php" class="text-blue-600 hover:text-blue-800">
                <i class="fas fa-arrow-left mr-1"></i> Voltar para a lista
            </a>
        </div>
        
        <div>
            <a href="polos_financeiro.php?action=editar&id=<?php echo $polo['id']; ?>" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded inline-flex items-center">
                <i class="fas fa-edit mr-2"></i> Editar Configurações
            </a>
        </div>
    </div>
    
    <!-- Informações do Polo -->
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Informações do Polo</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
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
        </div>
    </div>
    
    <!-- Histórico Financeiro -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-800">Histórico Financeiro</h2>
        </div>
        
        <?php if (empty($historico)): ?>
        <div class="p-6 text-center text-gray-500">
            <p>Nenhuma transação financeira registrada para este polo.</p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo de Polo</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Transação</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descrição</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantidade</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Valor</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Usuário</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($historico as $item): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                <?php echo date('d/m/Y', strtotime($item['data_transacao'])); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                <?php echo htmlspecialchars($item['tipo_nome']); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium">
                                <?php 
                                switch ($item['tipo_transacao']) {
                                    case 'taxa_inicial':
                                        echo '<span class="text-blue-600">Taxa Inicial</span>';
                                        break;
                                    case 'pacote':
                                        echo '<span class="text-green-600">Pacote</span>';
                                        break;
                                    case 'documento':
                                        echo '<span class="text-purple-600">Documento</span>';
                                        break;
                                    default:
                                        echo '<span class="text-gray-600">Outro</span>';
                                }
                                ?>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900">
                                <?php echo htmlspecialchars($item['descricao']); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900 text-center">
                                <?php echo $item['quantidade']; ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <div class="text-sm font-medium <?php echo $item['valor'] >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                R$ <?php echo number_format(abs($item['valor']), 2, ',', '.'); ?>
                                <?php echo $item['valor'] < 0 ? '(estorno)' : ''; ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <div class="text-sm text-gray-900">
                                <?php echo htmlspecialchars($item['usuario_nome'] ?? 'Sistema'); ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
