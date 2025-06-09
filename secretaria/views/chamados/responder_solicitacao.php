<div class="bg-white shadow-md rounded-lg p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800"><?php echo $titulo_pagina; ?></h1>

        <div>
            <a href="ver_solicitacao.php?id=<?php echo $id; ?>" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
                <i class="fas fa-arrow-left mr-2"></i> Voltar
            </a>
        </div>
    </div>

    <?php if (!empty($erros)): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
        <div class="font-bold">Erro ao responder a solicitação:</div>
        <ul class="list-disc pl-5">
            <?php foreach ($erros as $erro): ?>
            <li><?php echo $erro; ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <!-- Detalhes da Solicitação -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-gray-50 p-4 rounded-lg shadow-sm">
            <h2 class="text-lg font-semibold mb-4 text-blue-700 border-b pb-2">Informações do Aluno</h2>

            <div class="mb-3">
                <span class="block text-sm font-medium text-gray-700">Nome:</span>
                <span class="block text-sm text-gray-900 font-semibold"><?php echo htmlspecialchars($solicitacao['aluno_nome'] ?? ''); ?></span>
            </div>

            <div class="mb-3">
                <span class="block text-sm font-medium text-gray-700">CPF:</span>
                <span class="block text-sm text-gray-900"><?php echo formatarCpf($solicitacao['aluno_cpf']); ?></span>
            </div>

            <div class="mb-3">
                <span class="block text-sm font-medium text-gray-700">E-mail:</span>
                <span class="block text-sm text-gray-900"><?php echo htmlspecialchars($solicitacao['aluno_email'] ?? ''); ?></span>
            </div>

            <?php if (!empty($solicitacao['curso_nome'])): ?>
            <div class="mb-3">
                <span class="block text-sm font-medium text-gray-700">Curso:</span>
                <span class="block text-sm text-gray-900"><?php echo htmlspecialchars($solicitacao['curso_nome']); ?></span>
            </div>
            <?php endif; ?>
        </div>

        <div class="bg-gray-50 p-4 rounded-lg shadow-sm">
            <h2 class="text-lg font-semibold mb-4 text-blue-700 border-b pb-2">Detalhes do Documento</h2>

            <div class="mb-3">
                <span class="block text-sm font-medium text-gray-700">Tipo de Documento:</span>
                <span class="block text-sm text-gray-900 font-semibold"><?php echo htmlspecialchars($solicitacao['tipo_documento_nome'] ?? ''); ?></span>
            </div>

            <div class="mb-3">
                <span class="block text-sm font-medium text-gray-700">Quantidade:</span>
                <span class="block text-sm text-gray-900"><?php echo $solicitacao['quantidade']; ?></span>
            </div>

            <?php if (!empty($solicitacao['finalidade'])): ?>
            <div class="mb-3">
                <span class="block text-sm font-medium text-gray-700">Finalidade:</span>
                <span class="block text-sm text-gray-900"><?php echo htmlspecialchars($solicitacao['finalidade']); ?></span>
            </div>
            <?php endif; ?>

            <?php if (isset($solicitacao['valor_total'])): ?>
            <div class="mb-3">
                <span class="block text-sm font-medium text-gray-700">Valor Total:</span>
                <span class="block text-sm text-gray-900">
                    <?php echo !empty($solicitacao['valor_total']) ? 'R$ ' . number_format($solicitacao['valor_total'], 2, ',', '.') : 'Gratuito'; ?>
                </span>
            </div>
            <?php endif; ?>
        </div>

        <div class="bg-gray-50 p-4 rounded-lg shadow-sm">
            <h2 class="text-lg font-semibold mb-4 text-blue-700 border-b pb-2">Informações da Solicitação</h2>

            <div class="mb-3">
                <span class="block text-sm font-medium text-gray-700">Polo:</span>
                <span class="block text-sm text-gray-900"><?php echo htmlspecialchars($solicitacao['polo_nome'] ?? ''); ?></span>
            </div>

            <div class="mb-3">
                <span class="block text-sm font-medium text-gray-700">Data da Solicitação:</span>
                <span class="block text-sm text-gray-900">
                    <?php
                    $data_campo = isset($solicitacao['data_solicitacao']) ? 'data_solicitacao' : 'created_at';
                    echo date('d/m/Y H:i', strtotime($solicitacao[$data_campo]));
                    ?>
                </span>
            </div>

            <div class="mb-3">
                <span class="block text-sm font-medium text-gray-700">Status Atual:</span>
                <span class="block text-sm">
                    <?php
                    $status_class = '';
                    $status_text = '';

                    switch ($solicitacao['status']) {
                        case 'solicitado':
                            $status_class = 'bg-yellow-100 text-yellow-800';
                            $status_text = 'Solicitado';
                            break;
                        case 'processando':
                            $status_class = 'bg-blue-100 text-blue-800';
                            $status_text = 'Processando';
                            break;
                        case 'pronto':
                            $status_class = 'bg-green-100 text-green-800';
                            $status_text = 'Pronto';
                            break;
                        case 'entregue':
                            $status_class = 'bg-indigo-100 text-indigo-800';
                            $status_text = 'Entregue';
                            break;
                        case 'cancelado':
                            $status_class = 'bg-red-100 text-red-800';
                            $status_text = 'Cancelado';
                            break;
                    }
                    ?>
                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                        <?php echo $status_text; ?>
                    </span>
                </span>
            </div>

            <div class="mb-3">
                <span class="block text-sm font-medium text-gray-700">Solicitante:</span>
                <span class="block text-sm text-gray-900">
                    <?php echo htmlspecialchars($solicitacao['solicitante_nome'] ?? 'Não informado'); ?>
                </span>
            </div>

            <?php if (!empty($solicitacao['observacoes'])): ?>
            <div class="mt-4">
                <span class="block text-sm font-medium text-gray-700">Observações Anteriores:</span>
                <div class="bg-white p-3 rounded border border-gray-200 mt-1">
                    <p class="text-sm text-gray-900 whitespace-pre-line"><?php echo htmlspecialchars($solicitacao['observacoes']); ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Formulário de Resposta -->
    <div class="bg-white shadow-md rounded-lg p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4 text-blue-700 border-b pb-2">Responder Solicitação</h2>

        <form action="responder_solicitacao.php?id=<?php echo $id; ?>" method="post">
            <div class="mb-4">
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Alterar Status</label>
                <select id="status" name="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" required>
                    <option value="">Selecione o status</option>
                    <option value="processando" <?php echo isset($_POST['status']) && $_POST['status'] == 'processando' ? 'selected' : ''; ?>>Processando</option>
                    <option value="pronto" <?php echo isset($_POST['status']) && $_POST['status'] == 'pronto' ? 'selected' : ''; ?>>Pronto (Gera o documento automaticamente)</option>
                    <option value="cancelado" <?php echo isset($_POST['status']) && $_POST['status'] == 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                </select>
                <p class="mt-1 text-sm text-gray-500">
                    <i class="fas fa-info-circle text-blue-500 mr-1"></i> Selecionar "Pronto" irá gerar o documento automaticamente após salvar.
                </p>
            </div>

            <div class="mb-6">
                <label for="observacoes" class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                <textarea id="observacoes" name="observacoes" rows="4" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" placeholder="Informe aqui observações sobre esta resposta"><?php echo isset($_POST['observacoes']) ? htmlspecialchars($_POST['observacoes']) : ''; ?></textarea>
                <p class="mt-1 text-sm text-gray-500">
                    <i class="fas fa-lightbulb text-yellow-500 mr-1"></i> Adicione informações relevantes sobre o processamento desta solicitação.
                </p>
            </div>

            <div class="flex justify-end space-x-3">
                <a href="ver_solicitacao.php?id=<?php echo $id; ?>" class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 transition-colors duration-200">
                    <i class="fas fa-times mr-2"></i> Cancelar
                </a>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors duration-200">
                    <i class="fas fa-paper-plane mr-2"></i> Responder Solicitação
                </button>
            </div>
        </form>
    </div>

    <script>
        // Script para confirmar antes de selecionar "Pronto"
        document.addEventListener('DOMContentLoaded', function() {
            const statusSelect = document.getElementById('status');
            const form = statusSelect.closest('form');

            form.addEventListener('submit', function(e) {
                if (statusSelect.value === 'pronto') {
                    if (!confirm('Ao selecionar "Pronto", o sistema irá gerar automaticamente o documento. Deseja continuar?')) {
                        e.preventDefault();
                        return false;
                    }
                } else if (statusSelect.value === 'cancelado') {
                    if (!confirm('Tem certeza que deseja cancelar esta solicitação?')) {
                        e.preventDefault();
                        return false;
                    }
                }
            });
        });
    </script>
</div>