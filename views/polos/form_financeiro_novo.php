<div class="bg-white shadow-md rounded-lg p-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Informações Financeiras</h3>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Data Inicial -->
        <div>
            <label for="data_inicial" class="block text-sm font-medium text-gray-700 mb-1">Data Inicial</label>
            <input type="date" id="data_inicial" name="financeiro[data_inicial]"
                value="<?php echo isset($financeiro['data_inicial']) ? $financeiro['data_inicial'] : date('Y-m-d'); ?>"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
        </div>

        <!-- Vigência do Contrato em Meses -->
        <div>
            <label for="vigencia_contrato_meses" class="block text-sm font-medium text-gray-700 mb-1">Vigência do Contrato (meses)</label>
            <input type="number" id="vigencia_contrato_meses" name="financeiro[vigencia_contrato_meses]"
                value="<?php echo isset($financeiro['vigencia_contrato_meses']) ? $financeiro['vigencia_contrato_meses'] : '12'; ?>"
                min="1" step="1"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
        </div>

        <!-- Vencimento do Contrato -->
        <div>
            <label for="vencimento_contrato" class="block text-sm font-medium text-gray-700 mb-1">Vencimento do Contrato</label>
            <input type="date" id="vencimento_contrato" name="financeiro[vencimento_contrato]"
                value="<?php echo isset($financeiro['vencimento_contrato']) ? $financeiro['vencimento_contrato'] : ''; ?>"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
        </div>

        <!-- Vigência Pacote Setup -->
        <div>
            <label for="vigencia_pacote_setup" class="block text-sm font-medium text-gray-700 mb-1">Vigência Pacote Setup (meses)</label>
            <input type="number" id="vigencia_pacote_setup" name="financeiro[vigencia_pacote_setup]"
                value="<?php echo isset($financeiro['vigencia_pacote_setup']) ? $financeiro['vigencia_pacote_setup'] : '6'; ?>"
                min="1" step="1"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
        </div>

        <!-- Vencimento Pacote Setup -->
        <div>
            <label for="vencimento_pacote_setup" class="block text-sm font-medium text-gray-700 mb-1">Vencimento Pacote Setup</label>
            <input type="date" id="vencimento_pacote_setup" name="financeiro[vencimento_pacote_setup]"
                value="<?php echo isset($financeiro['vencimento_pacote_setup']) ? $financeiro['vencimento_pacote_setup'] : ''; ?>"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
        </div>

        <!-- Pacotes Adquiridos -->
        <div>
            <label for="pacotes_adquiridos" class="block text-sm font-medium text-gray-700 mb-1">Pacotes Adquiridos</label>
            <input type="number" id="pacotes_adquiridos" name="financeiro[pacotes_adquiridos]"
                value="<?php echo isset($financeiro['pacotes_adquiridos']) ? $financeiro['pacotes_adquiridos'] : '0'; ?>"
                min="0" step="1"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
            <p class="text-xs text-gray-500 mt-1">Cada pacote contém 50 documentos e custa R$ 200,00</p>
        </div>

        <!-- Valor Unitário Normal -->
        <div>
            <label for="valor_unitario_normal" class="block text-sm font-medium text-gray-700 mb-1">Valor Unitário Normal (R$)</label>
            <input type="number" id="valor_unitario_normal" name="financeiro[valor_unitario_normal]"
                value="<?php echo isset($financeiro['valor_unitario_normal']) ? $financeiro['valor_unitario_normal'] : '0.00'; ?>"
                min="0" step="0.01"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
        </div>

        <!-- Quantidade Contratada -->
        <div>
            <label for="quantidade_contratada" class="block text-sm font-medium text-gray-700 mb-1">Quantidade Contratada</label>
            <input type="number" id="quantidade_contratada" name="financeiro[quantidade_contratada]"
                value="<?php echo isset($financeiro['quantidade_contratada']) ? $financeiro['quantidade_contratada'] : '0'; ?>"
                min="0" step="1"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
        </div>

        <!-- Data Primeira Parcela -->
        <div>
            <label for="data_primeira_parcela" class="block text-sm font-medium text-gray-700 mb-1">Data Primeira Parcela</label>
            <input type="date" id="data_primeira_parcela" name="financeiro[data_primeira_parcela]"
                value="<?php echo isset($financeiro['data_primeira_parcela']) ? $financeiro['data_primeira_parcela'] : ''; ?>"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
        </div>

        <!-- Data Última Parcela -->
        <div>
            <label for="data_ultima_parcela" class="block text-sm font-medium text-gray-700 mb-1">Data Última Parcela</label>
            <input type="date" id="data_ultima_parcela" name="financeiro[data_ultima_parcela]"
                value="<?php echo isset($financeiro['data_ultima_parcela']) ? $financeiro['data_ultima_parcela'] : ''; ?>"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
        </div>

        <!-- Quantidade de Parcelas -->
        <div>
            <label for="quantidade_parcelas" class="block text-sm font-medium text-gray-700 mb-1">Quantidade de Parcelas</label>
            <input type="number" id="quantidade_parcelas" name="financeiro[quantidade_parcelas]"
                value="<?php echo isset($financeiro['quantidade_parcelas']) ? $financeiro['quantidade_parcelas'] : '12'; ?>"
                min="1" step="1"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
        </div>

        <!-- Valor Previsto -->
        <div>
            <label for="valor_previsto" class="block text-sm font-medium text-gray-700 mb-1">Valor Previsto (R$)</label>
            <input type="number" id="valor_previsto" name="financeiro[valor_previsto]"
                value="<?php echo isset($financeiro['valor_previsto']) ? $financeiro['valor_previsto'] : '0.00'; ?>"
                min="0" step="0.01"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
        </div>
    </div>

    <!-- Observações -->
    <div class="mt-6">
        <label for="observacoes" class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
        <textarea id="observacoes" name="financeiro[observacoes]" rows="4"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50"><?php echo isset($financeiro['observacoes']) ? $financeiro['observacoes'] : ''; ?></textarea>
    </div>

    <!-- JavaScript para cálculos automáticos -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Função para calcular o vencimento do contrato
            function calcularVencimentoContrato() {
                const dataInicial = document.getElementById('data_inicial').value;
                const vigenciaMeses = parseInt(document.getElementById('vigencia_contrato_meses').value);

                if (dataInicial && vigenciaMeses) {
                    const data = new Date(dataInicial);
                    data.setMonth(data.getMonth() + vigenciaMeses);

                    const ano = data.getFullYear();
                    const mes = String(data.getMonth() + 1).padStart(2, '0');
                    const dia = String(data.getDate()).padStart(2, '0');

                    document.getElementById('vencimento_contrato').value = `${ano}-${mes}-${dia}`;
                }
            }

            // Função para calcular o vencimento do pacote setup
            function calcularVencimentoPacoteSetup() {
                const dataInicial = document.getElementById('data_inicial').value;
                const vigenciaMeses = parseInt(document.getElementById('vigencia_pacote_setup').value);

                if (dataInicial && vigenciaMeses) {
                    const data = new Date(dataInicial);
                    data.setMonth(data.getMonth() + vigenciaMeses);

                    const ano = data.getFullYear();
                    const mes = String(data.getMonth() + 1).padStart(2, '0');
                    const dia = String(data.getDate()).padStart(2, '0');

                    document.getElementById('vencimento_pacote_setup').value = `${ano}-${mes}-${dia}`;
                }
            }

            // Função para calcular a data da última parcela
            function calcularDataUltimaParcela() {
                const dataPrimeiraParcela = document.getElementById('data_primeira_parcela').value;
                const quantidadeParcelas = parseInt(document.getElementById('quantidade_parcelas').value);

                if (dataPrimeiraParcela && quantidadeParcelas) {
                    const data = new Date(dataPrimeiraParcela);
                    data.setMonth(data.getMonth() + quantidadeParcelas - 1);

                    const ano = data.getFullYear();
                    const mes = String(data.getMonth() + 1).padStart(2, '0');
                    const dia = String(data.getDate()).padStart(2, '0');

                    document.getElementById('data_ultima_parcela').value = `${ano}-${mes}-${dia}`;
                }
            }

            // Função para calcular o valor previsto
            function calcularValorPrevisto() {
                const valorUnitario = parseFloat(document.getElementById('valor_unitario_normal').value);
                const quantidadeContratada = parseInt(document.getElementById('quantidade_contratada').value);

                if (valorUnitario && quantidadeContratada) {
                    const valorPrevisto = valorUnitario * quantidadeContratada;
                    document.getElementById('valor_previsto').value = valorPrevisto.toFixed(2);
                }
            }

            // Adiciona os event listeners
            document.getElementById('data_inicial').addEventListener('change', calcularVencimentoContrato);
            document.getElementById('vigencia_contrato_meses').addEventListener('change', calcularVencimentoContrato);

            document.getElementById('data_inicial').addEventListener('change', calcularVencimentoPacoteSetup);
            document.getElementById('vigencia_pacote_setup').addEventListener('change', calcularVencimentoPacoteSetup);

            document.getElementById('data_primeira_parcela').addEventListener('change', calcularDataUltimaParcela);
            document.getElementById('quantidade_parcelas').addEventListener('change', calcularDataUltimaParcela);

            document.getElementById('valor_unitario_normal').addEventListener('change', calcularValorPrevisto);
            document.getElementById('quantidade_contratada').addEventListener('change', calcularValorPrevisto);

            // Calcula os valores iniciais
            calcularVencimentoContrato();
            calcularVencimentoPacoteSetup();
            calcularDataUltimaParcela();
            calcularValorPrevisto();
        });
    </script>
</div>
