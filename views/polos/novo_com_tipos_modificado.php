<?php
// As funções executarConsulta e executarConsultaAll já estão definidas no arquivo principal

// Busca os tipos de polos disponíveis
$sql = "SELECT id, nome, descricao FROM tipos_polos WHERE status = 'ativo' ORDER BY nome ASC";
$tipos_polos = executarConsultaAll($db, $sql);
?>
<div class="container mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Novo Polo Educacional</h1>
        <a href="polos.php" class="bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded">
            <i class="fas fa-arrow-left mr-2"></i> Voltar
        </a>
    </div>

    <div class="bg-white shadow-md rounded-lg p-6">
        <form action="polos.php?action=salvar" method="POST" class="space-y-6">
            <!-- Abas para organizar o formulário -->
            <div class="mb-6">
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-8">
                        <button type="button" class="tab-button active py-4 px-1 border-b-2 border-blue-500 font-medium text-sm text-blue-600" data-tab="informacoes-gerais">
                            Informações Gerais
                        </button>
                        <button type="button" class="tab-button py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300" data-tab="tipos-polo">
                            Tipos de Polo
                        </button>
                        <button type="button" class="tab-button py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300" data-tab="financeiro">
                            Financeiro
                        </button>
                    </nav>
                </div>
            </div>

            <!-- Conteúdo da aba Informações Gerais -->
            <div id="informacoes-gerais" class="tab-content">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="nome" class="block text-sm font-medium text-gray-700 mb-1">Nome do Polo <span class="text-red-500">*</span></label>
                        <input type="text" id="nome" name="nome" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    </div>

                    <div>
                        <label for="razao_social" class="block text-sm font-medium text-gray-700 mb-1">Razão Social <span class="text-red-500">*</span></label>
                        <input type="text" id="razao_social" name="razao_social" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    </div>

                    <div>
                        <label for="cnpj" class="block text-sm font-medium text-gray-700 mb-1">CNPJ</label>
                        <input type="text" id="cnpj" name="cnpj" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    </div>

                    <div>
                        <label for="telefone" class="block text-sm font-medium text-gray-700 mb-1">Telefone <span class="text-red-500">*</span></label>
                        <input type="text" id="telefone" name="telefone" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">E-mail <span class="text-red-500">*</span></label>
                        <input type="email" id="email" name="email" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    </div>

                    <div>
                        <label for="endereco" class="block text-sm font-medium text-gray-700 mb-1">Endereço <span class="text-red-500">*</span></label>
                        <input type="text" id="endereco" name="endereco" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    </div>

                    <div>
                        <label for="cidade_id" class="block text-sm font-medium text-gray-700 mb-1">Cidade</label>
                        <div class="relative">
                            <input type="text" id="cidade_busca"
                                   placeholder="Digite para buscar uma cidade..."
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                            <input type="hidden" id="cidade_id" name="cidade_id" value="">
                            <div id="cidade_resultados" class="absolute z-10 w-full mt-1 bg-white shadow-lg rounded-md overflow-hidden hidden"></div>
                        </div>
                    </div>

                    <div>
                        <label for="responsavel" class="block text-sm font-medium text-gray-700 mb-1">Responsável</label>
                        <input type="text" id="responsavel" name="responsavel"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50"
                               placeholder="Nome do responsável pelo polo">
                    </div>

                    <div>
                        <label for="data_inicio_parceria" class="block text-sm font-medium text-gray-700 mb-1">Data de Início da Parceria</label>
                        <input type="date" id="data_inicio_parceria" name="data_inicio_parceria" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    </div>

                    <div>
                        <label for="data_fim_contrato" class="block text-sm font-medium text-gray-700 mb-1">Data de Fim do Contrato</label>
                        <input type="date" id="data_fim_contrato" name="data_fim_contrato" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    </div>

                    <div>
                        <label for="status_contrato" class="block text-sm font-medium text-gray-700 mb-1">Status do Contrato</label>
                        <select id="status_contrato" name="status_contrato" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                            <option value="ativo">Ativo</option>
                            <option value="suspenso">Suspenso</option>
                            <option value="encerrado">Encerrado</option>
                        </select>
                    </div>

                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status do Polo <span class="text-red-500">*</span></label>
                        <select id="status" name="status" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                            <option value="ativo">Ativo</option>
                            <option value="inativo">Inativo</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label for="observacoes" class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                        <textarea id="observacoes" name="observacoes" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50"></textarea>
                    </div>
                </div>
            </div>

            <!-- Conteúdo da aba Tipos de Polo -->
            <div id="tipos-polo" class="tab-content hidden">
                <div class="mb-4">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Selecione os tipos de polo</h3>
                    <p class="text-sm text-gray-500 mb-4">Um polo pode ter múltiplos tipos.</p>

                    <div class="space-y-4">
                        <?php foreach ($tipos_polos as $tipo): ?>
                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                                <input id="tipo_polo_<?php echo $tipo['id']; ?>" name="tipos_polo[]" value="<?php echo $tipo['id']; ?>" type="checkbox" class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="tipo_polo_<?php echo $tipo['id']; ?>" class="font-medium text-gray-700"><?php echo htmlspecialchars($tipo['nome']); ?></label>
                                <p class="text-gray-500"><?php echo htmlspecialchars($tipo['descricao']); ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Conteúdo da aba Financeiro -->
            <div id="financeiro" class="tab-content hidden">
                <div class="mb-4">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Informações Financeiras</h3>
                    <p class="text-sm text-gray-500 mb-4">Preencha com as informações financeiras do polo.</p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Data Inicial -->
                        <div>
                            <label for="data_inicial" class="block text-sm font-medium text-gray-700 mb-1">Data Inicial</label>
                            <input type="date" id="data_inicial" name="financeiro_novo[data_inicial]"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                        </div>

                        <!-- Vigência do Contrato em Meses -->
                        <div>
                            <label for="vigencia_contrato_meses" class="block text-sm font-medium text-gray-700 mb-1">Vigência do Contrato (meses)</label>
                            <input type="number" id="vigencia_contrato_meses" name="financeiro_novo[vigencia_contrato_meses]"
                                min="1" step="1"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                        </div>

                        <!-- Vencimento do Contrato -->
                        <div>
                            <label for="vencimento_contrato" class="block text-sm font-medium text-gray-700 mb-1">Vencimento do Contrato</label>
                            <input type="date" id="vencimento_contrato" name="financeiro_novo[vencimento_contrato]"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                        </div>

                        <!-- Vigência Pacote Setup -->
                        <div>
                            <label for="vigencia_pacote_setup" class="block text-sm font-medium text-gray-700 mb-1">Vigência Pacote Setup (meses)</label>
                            <input type="number" id="vigencia_pacote_setup" name="financeiro_novo[vigencia_pacote_setup]"
                                min="1" step="1"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                        </div>

                        <!-- Vencimento Pacote Setup -->
                        <div>
                            <label for="vencimento_pacote_setup" class="block text-sm font-medium text-gray-700 mb-1">Vencimento Pacote Setup</label>
                            <input type="date" id="vencimento_pacote_setup" name="financeiro_novo[vencimento_pacote_setup]"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                        </div>

                        <!-- Pacotes Adquiridos -->
                        <div>
                            <label for="pacotes_adquiridos" class="block text-sm font-medium text-gray-700 mb-1">Pacotes Adquiridos</label>
                            <input type="number" id="pacotes_adquiridos" name="financeiro_novo[pacotes_adquiridos]"
                                min="0" step="1"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                        </div>

                        <!-- Valor Unitário Normal -->
                        <div>
                            <label for="valor_unitario_normal" class="block text-sm font-medium text-gray-700 mb-1">Valor Unitário Normal (R$)</label>
                            <input type="number" id="valor_unitario_normal" name="financeiro_novo[valor_unitario_normal]"
                                min="0" step="0.01"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                        </div>

                        <!-- Quantidade Contratada -->
                        <div>
                            <label for="quantidade_contratada" class="block text-sm font-medium text-gray-700 mb-1">Quantidade Contratada</label>
                            <input type="number" id="quantidade_contratada" name="financeiro_novo[quantidade_contratada]"
                                min="0" step="1"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                        </div>

                        <!-- Data Primeira Parcela -->
                        <div>
                            <label for="data_primeira_parcela" class="block text-sm font-medium text-gray-700 mb-1">Data Primeira Parcela</label>
                            <input type="date" id="data_primeira_parcela" name="financeiro_novo[data_primeira_parcela]"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                        </div>

                        <!-- Data Última Parcela -->
                        <div>
                            <label for="data_ultima_parcela" class="block text-sm font-medium text-gray-700 mb-1">Data Última Parcela</label>
                            <input type="date" id="data_ultima_parcela" name="financeiro_novo[data_ultima_parcela]"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                        </div>

                        <!-- Quantidade de Parcelas -->
                        <div>
                            <label for="quantidade_parcelas" class="block text-sm font-medium text-gray-700 mb-1">Quantidade de Parcelas</label>
                            <input type="number" id="quantidade_parcelas" name="financeiro_novo[quantidade_parcelas]"
                                min="1" step="1"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                        </div>

                        <!-- Valor Previsto -->
                        <div>
                            <label for="valor_previsto" class="block text-sm font-medium text-gray-700 mb-1">Valor Previsto (R$)</label>
                            <input type="number" id="valor_previsto" name="financeiro_novo[valor_previsto]"
                                min="0" step="0.01"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                        </div>
                    </div>

                    <!-- Observações Financeiras -->
                    <div class="mt-6">
                        <label for="observacoes_financeiras" class="block text-sm font-medium text-gray-700 mb-1">Observações Financeiras</label>
                        <textarea id="observacoes_financeiras" name="financeiro_novo[observacoes]" rows="4"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50"></textarea>
                    </div>
                </div>
            </div>

            <div class="flex justify-end space-x-3">
                <a href="polos.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-medium py-2 px-4 rounded">
                    Cancelar
                </a>
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded">
                    <i class="fas fa-save mr-2"></i> Salvar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Função para alternar entre as abas
document.querySelectorAll('.tab-button').forEach(button => {
    button.addEventListener('click', function() {
        // Remove a classe active de todos os botões
        document.querySelectorAll('.tab-button').forEach(btn => {
            btn.classList.remove('active');
            btn.classList.remove('border-blue-500');
            btn.classList.remove('text-blue-600');
            btn.classList.add('border-transparent');
            btn.classList.add('text-gray-500');
        });

        // Adiciona a classe active ao botão clicado
        this.classList.add('active');
        this.classList.add('border-blue-500');
        this.classList.add('text-blue-600');
        this.classList.remove('border-transparent');
        this.classList.remove('text-gray-500');

        // Esconde todos os conteúdos
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.add('hidden');
        });

        // Mostra o conteúdo correspondente
        const tabId = this.getAttribute('data-tab');
        document.getElementById(tabId).classList.remove('hidden');
    });
});

</script>

<script src="js/ibge-city-search.js"></script>
<script>
// Inicializa a busca de cidades usando o IBGE
document.addEventListener('DOMContentLoaded', function() {
    // Inicializa a busca de cidades com IBGE
    initIBGECitySearch({
        inputSelector: '#cidade_busca',
        resultsSelector: '#cidade_resultados',
        hiddenInputSelector: '#cidade_id'
    });
});
</script>