<?php
/**
 * Página para baixar documentos em lote
 * Permite selecionar critérios para baixar documentos em lote
 */
error_log("Executando o arquivo baixar_em_lote.php");
?>

<script>
    console.log("Página de baixar em lote carregada");
</script>

<div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
    <div class="p-6 border-b border-gray-200">
        <h3 class="text-xl font-semibold text-gray-800">Baixar Documentos em Lote</h3>
        <p class="mt-2 text-gray-600">Selecione os critérios para baixar documentos em lote. Se algum documento não existir, o sistema tentará gerá-lo automaticamente.</p>
    </div>

    <div class="p-6">
        <form action="documentos.php" method="post" class="space-y-6">
            <input type="hidden" name="action" value="processar_download_lote">

            <!-- Tipo de Documento -->
            <div>
                <label for="tipo_documento" class="block text-sm font-medium text-gray-700 mb-1">Tipo de Documento</label>
                <select id="tipo_documento" name="tipo_documento" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Selecione o tipo de documento</option>
                    <?php
                    $sql_tipos = "SELECT id, nome FROM tipos_documentos ORDER BY nome";
                    $tipos = executarConsultaAll($db, $sql_tipos);
                    foreach ($tipos as $tipo):
                    ?>
                    <option value="<?php echo $tipo['id']; ?>">
                        <?php echo htmlspecialchars($tipo['nome']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Turma -->
            <div>
                <label for="turma_id" class="block text-sm font-medium text-gray-700 mb-1">Turma (opcional)</label>
                <select id="turma_id" name="turma_id"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Todas as turmas</option>
                    <?php
                    $sql_turmas = "SELECT id, nome FROM turmas ORDER BY nome";
                    $turmas = executarConsultaAll($db, $sql_turmas);
                    foreach ($turmas as $turma):
                    ?>
                    <option value="<?php echo $turma['id']; ?>">
                        <?php echo htmlspecialchars($turma['nome']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Polo -->
            <div>
                <label for="polo_id" class="block text-sm font-medium text-gray-700 mb-1">Polo (opcional)</label>
                <select id="polo_id" name="polo_id"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Todos os polos</option>
                    <?php
                    $sql_polos = "SELECT id, nome FROM polos ORDER BY nome";
                    $polos = executarConsultaAll($db, $sql_polos);
                    foreach ($polos as $polo):
                    ?>
                    <option value="<?php echo $polo['id']; ?>">
                        <?php echo htmlspecialchars($polo['nome']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Período de Emissão -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="data_inicio" class="block text-sm font-medium text-gray-700 mb-1">Data de Início (opcional)</label>
                    <input type="date" id="data_inicio" name="data_inicio"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label for="data_fim" class="block text-sm font-medium text-gray-700 mb-1">Data de Fim (opcional)</label>
                    <input type="date" id="data_fim" name="data_fim"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>

            <!-- Opções de Exibição do Polo -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Exibir Polo nos Documentos</label>
                <div class="mt-2 space-y-2">
                    <div class="flex items-center">
                        <input id="exibir_polo_sim" name="exibir_polo" type="radio" value="1" checked
                               class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                        <label for="exibir_polo_sim" class="ml-3 block text-sm font-medium text-gray-700">
                            Sim, exibir o polo
                        </label>
                    </div>
                    <div class="flex items-center">
                        <input id="exibir_polo_nao" name="exibir_polo" type="radio" value="0"
                               class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                        <label for="exibir_polo_nao" class="ml-3 block text-sm font-medium text-gray-700">
                            Não exibir o polo
                        </label>
                    </div>
                </div>
            </div>

            <div class="flex justify-end space-x-3 pt-4">
                <a href="documentos.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg text-sm">
                    Cancelar
                </a>
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm">
                    <i class="fas fa-download mr-2"></i> Baixar Documentos
                </button>
            </div>
        </form>
    </div>
</div>
