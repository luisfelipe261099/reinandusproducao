<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="p-6">
        <div class="mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-2">Resultado da Validação</h2>
            <p class="text-gray-600 mb-4">
                Abaixo está o resultado da validação do arquivo <strong><?php echo htmlspecialchars($nome_arquivo); ?></strong>.
                Esta é apenas uma simulação do que aconteceria se você importasse este arquivo. Nenhuma alteração foi feita no banco de dados.
            </p>

            <!-- Resumo da validação -->
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
                <div class="bg-gray-50 p-4 rounded-lg text-center">
                    <div class="text-2xl font-bold text-gray-700"><?php echo $resumo['total']; ?></div>
                    <div class="text-sm text-gray-500">Total de registros</div>
                </div>
                <div class="bg-green-50 p-4 rounded-lg text-center">
                    <div class="text-2xl font-bold text-green-600"><?php echo $resumo['inseridos']; ?></div>
                    <div class="text-sm text-green-500">Serão inseridos</div>
                </div>
                <div class="bg-blue-50 p-4 rounded-lg text-center">
                    <div class="text-2xl font-bold text-blue-600"><?php echo $resumo['atualizados']; ?></div>
                    <div class="text-sm text-blue-500">Serão atualizados</div>
                </div>
                <div class="bg-yellow-50 p-4 rounded-lg text-center">
                    <div class="text-2xl font-bold text-yellow-600"><?php echo $resumo['ignorados']; ?></div>
                    <div class="text-sm text-yellow-500">Serão ignorados</div>
                </div>
                <div class="bg-red-50 p-4 rounded-lg text-center">
                    <div class="text-2xl font-bold text-red-600"><?php echo $resumo['erros']; ?></div>
                    <div class="text-sm text-red-500">Erros encontrados</div>
                </div>
            </div>

            <!-- Tabela de resultados -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Linha</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CPF</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Operação</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mensagem</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($resultados)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
                                Nenhum registro encontrado no arquivo.
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($resultados as $resultado): ?>
                            <tr class="<?php echo $resultado['status'] === 'erro' ? 'bg-red-50' : ''; ?>">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo $resultado['linha']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($resultado['nome']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($resultado['email']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($resultado['cpf']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($resultado['status'] === 'ok'): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        OK
                                    </span>
                                    <?php else: ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                        Erro
                                    </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php if ($resultado['operacao'] === 'inserir'): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        Inserir
                                    </span>
                                    <?php elseif ($resultado['operacao'] === 'atualizar'): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                        Atualizar
                                    </span>
                                    <?php elseif ($resultado['operacao'] === 'ignorar'): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                        Ignorar
                                    </span>
                                    <?php else: ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                        -
                                    </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    <?php echo htmlspecialchars($resultado['mensagem']); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Botões de ação -->
            <div class="mt-6 flex items-center justify-end space-x-3">
                <a href="alunos.php?action=importar" class="btn-secondary">
                    <i class="fas fa-arrow-left mr-2"></i> Voltar para Importação
                </a>

                <?php if ($resumo['erros'] === 0 && $resumo['total'] > 0): ?>
                <form action="alunos.php?action=processar_importacao" method="post" enctype="multipart/form-data" class="inline">
                    <!-- Campos ocultos para reenviar os mesmos dados -->
                    <input type="hidden" name="polo_id" value="<?php echo htmlspecialchars($polo_id ?? ''); ?>">
                    <input type="hidden" name="curso_id" value="<?php echo htmlspecialchars($curso_id ?? ''); ?>">
                    <input type="hidden" name="turma_id" value="<?php echo htmlspecialchars($turma_id ?? ''); ?>">
                    <input type="hidden" name="atualizar_existentes" value="<?php echo isset($atualizar_existentes) && $atualizar_existentes ? '1' : '0'; ?>">
                    <input type="hidden" name="identificar_por_email" value="<?php echo isset($identificar_por_email) && $identificar_por_email ? '1' : '0'; ?>">

                    <!-- Precisamos reenviar o arquivo -->
                    <input type="file" name="arquivo" id="arquivo_reenvio" class="hidden" required>

                    <button type="button" id="btn_importar_agora" class="btn-primary">
                        <i class="fas fa-file-import mr-2"></i> Importar Agora
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Botão para importar após validação
    const btnImportarAgora = document.getElementById('btn_importar_agora');
    if (btnImportarAgora) {
        btnImportarAgora.addEventListener('click', function() {
            // Exibe uma confirmação
            if (confirm('Tem certeza que deseja importar os dados? Esta ação não pode ser desfeita.')) {
                alert('Para realizar a importação, você precisará fazer o upload do arquivo novamente. Por favor, selecione o mesmo arquivo na próxima tela.');
                window.location.href = 'alunos.php?action=importar';
            }
        });
    }
});
</script>
