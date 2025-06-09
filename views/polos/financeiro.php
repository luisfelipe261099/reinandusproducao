<?php
// Verifica se o ID do polo foi informado
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['mensagem'] = 'ID do polo não informado.';
    $_SESSION['mensagem_tipo'] = 'erro';
    header('Location: polos.php');
    exit;
}

$polo_id = (int)$_GET['id'];

// Busca as informações do polo
$sql = "SELECT * FROM polos WHERE id = ?";
$polo = executarConsulta($db, $sql, [$polo_id]);

if (!$polo) {
    $_SESSION['mensagem'] = 'Polo não encontrado.';
    $_SESSION['mensagem_tipo'] = 'erro';
    header('Location: polos.php');
    exit;
}

// Busca os tipos de polo associados
$sql = "SELECT pt.*, tp.nome as tipo_nome, tp.descricao as tipo_descricao
        FROM polos_tipos pt
        JOIN tipos_polos tp ON pt.tipo_polo_id = tp.id
        WHERE pt.polo_id = ?
        ORDER BY tp.nome ASC";
$tipos_polo = executarConsultaAll($db, $sql, [$polo_id]);

// Busca as informações financeiras do polo
$sql = "SELECT pf.*, tp.nome as tipo_nome, tpf.taxa_inicial, tpf.taxa_por_documento, 
               tpf.pacote_documentos, tpf.valor_pacote
        FROM polos_financeiro pf
        JOIN tipos_polos tp ON pf.tipo_polo_id = tp.id
        JOIN tipos_polos_financeiro tpf ON pf.tipo_polo_id = tpf.tipo_polo_id
        WHERE pf.polo_id = ?
        ORDER BY tp.nome ASC";
$financeiro = executarConsultaAll($db, $sql, [$polo_id]);

// Busca o histórico financeiro do polo
$sql = "SELECT pfh.*, tp.nome as tipo_nome, u.nome as usuario_nome
        FROM polos_financeiro_historico pfh
        JOIN tipos_polos tp ON pfh.tipo_polo_id = tp.id
        LEFT JOIN usuarios u ON pfh.usuario_id = u.id
        WHERE pfh.polo_id = ?
        ORDER BY pfh.data_transacao DESC, pfh.created_at DESC";
$historico = executarConsultaAll($db, $sql, [$polo_id]);
?>

<div class="container mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Financeiro do Polo: <?php echo htmlspecialchars($polo['nome']); ?></h1>
        <div class="flex space-x-2">
            <a href="polos.php?action=visualizar&id=<?php echo $polo_id; ?>" class="bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded">
                <i class="fas fa-arrow-left mr-2"></i> Voltar
            </a>
            <a href="polos.php?action=editar_financeiro_novo&id=<?php echo $polo_id; ?>" class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded">
                <i class="fas fa-edit mr-2"></i> Editar Financeiro
            </a>
        </div>
    </div>

    <!-- Resumo Financeiro -->
    <div class="bg-white shadow-md rounded-lg p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Resumo Financeiro</h2>
        
        <?php if (empty($financeiro)): ?>
            <p class="text-gray-500">Não há informações financeiras disponíveis para este polo.</p>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <?php foreach ($financeiro as $item): ?>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="font-medium text-gray-900 mb-2"><?php echo htmlspecialchars($item['tipo_nome']); ?></h3>
                        
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Taxa Inicial:</span>
                                <span class="font-medium">
                                    R$ <?php echo number_format($item['taxa_inicial'], 2, ',', '.'); ?>
                                    <?php if ($item['taxa_inicial_paga']): ?>
                                        <span class="text-green-600 ml-1"><i class="fas fa-check"></i> Paga</span>
                                    <?php else: ?>
                                        <span class="text-red-600 ml-1"><i class="fas fa-times"></i> Pendente</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                            
                            <?php if ($item['data_pagamento_taxa']): ?>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Data do Pagamento:</span>
                                    <span class="font-medium"><?php echo date('d/m/Y', strtotime($item['data_pagamento_taxa'])); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($item['pacote_documentos'] > 0): ?>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Pacotes Adquiridos:</span>
                                    <span class="font-medium"><?php echo $item['pacotes_adquiridos']; ?> x R$ <?php echo number_format($item['valor_pacote'], 2, ',', '.'); ?></span>
                                </div>
                                
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Documentos por Pacote:</span>
                                    <span class="font-medium"><?php echo $item['pacote_documentos']; ?></span>
                                </div>
                            <?php else: ?>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Taxa por Documento:</span>
                                    <span class="font-medium">R$ <?php echo number_format($item['taxa_por_documento'], 2, ',', '.'); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="flex justify-between">
                                <span class="text-gray-600">Documentos Disponíveis:</span>
                                <span class="font-medium"><?php echo $item['documentos_disponiveis']; ?></span>
                            </div>
                            
                            <div class="flex justify-between">
                                <span class="text-gray-600">Documentos Emitidos:</span>
                                <span class="font-medium"><?php echo $item['documentos_emitidos']; ?></span>
                            </div>
                            
                            <div class="flex justify-between">
                                <span class="text-gray-600">Valor Total Pago:</span>
                                <span class="font-medium">R$ <?php echo number_format($item['valor_total_pago'], 2, ',', '.'); ?></span>
                            </div>
                        </div>
                        
                        <?php if (!empty($item['observacoes'])): ?>
                            <div class="mt-3 pt-3 border-t border-gray-200">
                                <p class="text-sm text-gray-600"><?php echo nl2br(htmlspecialchars($item['observacoes'])); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Histórico Financeiro -->
    <div class="bg-white shadow-md rounded-lg p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Histórico Financeiro</h2>
        
        <?php if (empty($historico)): ?>
            <p class="text-gray-500">Não há histórico financeiro disponível para este polo.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo de Polo</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Transação</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valor</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantidade</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descrição</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuário</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($historico as $item): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('d/m/Y', strtotime($item['data_transacao'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($item['tipo_nome']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php 
                                        $tipos_transacao = [
                                            'taxa_inicial' => 'Taxa Inicial',
                                            'pacote' => 'Pacote de Documentos',
                                            'documento' => 'Documento Emitido',
                                            'outro' => 'Outro'
                                        ];
                                        echo $tipos_transacao[$item['tipo_transacao']] ?? $item['tipo_transacao'];
                                    ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    R$ <?php echo number_format($item['valor'], 2, ',', '.'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo $item['quantidade']; ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    <?php echo htmlspecialchars($item['descricao']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($item['usuario_nome'] ?? 'N/A'); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>




