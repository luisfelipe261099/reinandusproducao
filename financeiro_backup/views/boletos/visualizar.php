<?php
// Exibe mensagens de erro ou sucesso
if (isset($_SESSION['mensagem'])) {
    if (is_array($_SESSION['mensagem'])) {
        $tipo = $_SESSION['mensagem']['tipo'];
        $texto = $_SESSION['mensagem']['texto'];
    } else {
        // Compatibilidade com o formato antigo
        $tipo = isset($_SESSION['mensagem_tipo']) ? $_SESSION['mensagem_tipo'] : 'erro';
        $texto = $_SESSION['mensagem'];
    }

    echo '<div class="mb-4 ' . ($tipo == 'erro' ? 'bg-red-100 border-red-400 text-red-700' : 'bg-green-100 border-green-400 text-green-700') . ' px-4 py-3 rounded relative border" role="alert">';
    echo '<span class="block sm:inline">' . $texto . '</span>';
    echo '</div>';

    unset($_SESSION['mensagem']);
    if (isset($_SESSION['mensagem_tipo'])) {
        unset($_SESSION['mensagem_tipo']);
    }
}
?>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-semibold text-gray-800">Detalhes do Boleto</h3>
            <div class="flex space-x-2">
                <?php if (!empty($boleto['url_boleto'])): ?>
                <a href="<?php echo $boleto['url_boleto']; ?>" target="_blank" class="btn-success">
                    <i class="fas fa-file-invoice-dollar mr-2"></i> Abrir Boleto
                </a>
                <?php endif; ?>
                <a href="download_boleto.php?id=<?php echo $boleto['id']; ?>" target="_blank" class="btn-primary">
                    <i class="fas fa-download mr-2"></i> Download PDF
                </a>
                <?php if (!empty($boleto['nosso_numero'])): ?>
                <a href="verificar_boleto.php?id=<?php echo $boleto['id']; ?>" class="btn-info">
                    <i class="fas fa-search mr-2"></i> Verificar na API
                </a>
                <a href="testar_api.php?boleto_id=<?php echo $boleto['id']; ?>&action=testar" class="btn-warning">
                    <i class="fas fa-vial mr-2"></i> Testar API
                </a>
                <a href="verificar_ambiente_api.php?action=testar" class="bg-purple-500 hover:bg-purple-600 text-white font-bold py-2 px-4 rounded">
                    <i class="fas fa-server mr-2"></i> Verificar Ambiente
                </a>
                <?php endif; ?>
                <a href="gerar_boleto.php?action=listar" class="btn-secondary">
                    <i class="fas fa-arrow-left mr-2"></i> Voltar
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Informações do Boleto -->
            <div class="bg-gray-50 p-4 rounded-lg">
                <h4 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Informações do Boleto</h4>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">ID:</span>
                        <span class="font-medium"><?php echo $boleto['id']; ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Descrição:</span>
                        <span class="font-medium"><?php echo htmlspecialchars($boleto['descricao']); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Valor:</span>
                        <span class="font-medium">R$ <?php echo number_format($boleto['valor'], 2, ',', '.'); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Data de Emissão:</span>
                        <span class="font-medium"><?php echo date('d/m/Y', strtotime($boleto['data_emissao'])); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Data de Vencimento:</span>
                        <span class="font-medium"><?php echo date('d/m/Y', strtotime($boleto['data_vencimento'])); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Status:</span>
                        <span class="status-badge <?php
                            if ($boleto['status'] == 'pendente') echo 'status-pendente';
                            elseif ($boleto['status'] == 'pago') echo 'status-pago';
                            elseif ($boleto['status'] == 'cancelado') echo 'status-cancelado';
                            elseif ($boleto['status'] == 'vencido') echo 'status-vencido';
                        ?>">
                            <?php
                                if ($boleto['status'] == 'pendente') echo 'Pendente';
                                elseif ($boleto['status'] == 'pago') echo 'Pago';
                                elseif ($boleto['status'] == 'cancelado') echo 'Cancelado';
                                elseif ($boleto['status'] == 'vencido') echo 'Vencido';
                            ?>
                        </span>
                    </div>
                    <?php if ($boleto['status'] == 'pago' && !empty($boleto['data_pagamento'])): ?>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Data de Pagamento:</span>
                        <span class="font-medium"><?php echo date('d/m/Y', strtotime($boleto['data_pagamento'])); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($boleto['nosso_numero'])): ?>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Nosso Número:</span>
                        <span class="font-medium"><?php echo $boleto['nosso_numero']; ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Nosso Número (Itaú):</span>
                        <span class="font-medium">
                            <?php
                            require_once __DIR__ . '/../../includes/corrigir_nosso_numero.php';
                            echo formatarNossoNumeroItau($boleto['nosso_numero']);
                            ?>
                        </span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($boleto['linha_digitavel'])): ?>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Linha Digitável:</span>
                        <span class="font-medium"><?php echo $boleto['linha_digitavel']; ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($boleto['codigo_barras'])): ?>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Código de Barras:</span>
                        <span class="font-medium"><?php echo $boleto['codigo_barras']; ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Informações do Pagador -->
            <div class="bg-gray-50 p-4 rounded-lg">
                <h4 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Informações do Pagador</h4>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Tipo:</span>
                        <span class="font-medium">
                            <?php
                                if ($boleto['tipo_entidade'] == 'aluno') echo 'Aluno';
                                elseif ($boleto['tipo_entidade'] == 'polo') echo 'Polo';
                                else echo 'Avulso';
                            ?>
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Nome:</span>
                        <span class="font-medium"><?php echo htmlspecialchars($boleto['nome_pagador']); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">CPF/CNPJ:</span>
                        <span class="font-medium"><?php echo htmlspecialchars($boleto['cpf_pagador']); ?></span>
                    </div>
                    <?php if (!empty($boleto['endereco'])): ?>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Endereço:</span>
                        <span class="font-medium"><?php echo htmlspecialchars($boleto['endereco']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($boleto['cidade']) && !empty($boleto['uf'])): ?>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Cidade/UF:</span>
                        <span class="font-medium"><?php echo htmlspecialchars($boleto['cidade'] . '/' . $boleto['uf']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($boleto['cep'])): ?>
                    <div class="flex justify-between">
                        <span class="text-gray-600">CEP:</span>
                        <span class="font-medium"><?php echo htmlspecialchars($boleto['cep']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($boleto['status'] == 'pendente'): ?>
        <div class="mt-6 flex justify-end space-x-3">
            <a href="gerar_boleto.php?action=marcar_pago&id=<?php echo $boleto['id']; ?>" class="btn-success" onclick="return confirm('Deseja marcar este boleto como pago?');">
                <i class="fas fa-check-circle mr-2"></i> Marcar como Pago
            </a>
            <a href="verificar_boleto.php?id=<?php echo $boleto['id']; ?>" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                <i class="fas fa-search mr-2"></i> Verificar no Itaú
            </a>
            <a href="cancelar_boleto.php?id=<?php echo $boleto['id']; ?>" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                <i class="fas fa-ban mr-2"></i> Cancelar Boleto
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>
