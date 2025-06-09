<?php
// As funções executarConsulta e executarConsultaAll já estão definidas no arquivo principal

// Busca os tipos de polos disponíveis
$sql = "SELECT id, nome, descricao FROM tipos_polos WHERE status = 'ativo' ORDER BY nome ASC";
$tipos_polos = executarConsultaAll($db, $sql);

// Busca as configurações financeiras dos tipos de polos
$sql = "SELECT tpf.*, tp.nome as tipo_nome
        FROM tipos_polos_financeiro tpf
        JOIN tipos_polos tp ON tpf.tipo_polo_id = tp.id";
$tipos_polos_financeiro = executarConsultaAll($db, $sql);

// Organiza as configurações financeiras por tipo de polo
$financeiro_por_tipo = [];
foreach ($tipos_polos_financeiro as $config) {
    $financeiro_por_tipo[$config['tipo_polo_id']] = $config;
}
?>
<div class="container mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Editar Polo Educacional: <?php echo htmlspecialchars($polo['nome']); ?></h1>
        <a href="polos.php" class="bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded">
            <i class="fas fa-arrow-left mr-2"></i> Voltar
        </a>
    </div>

    <div class="bg-white shadow-md rounded-lg p-6">
        <form action="polos.php?action=salvar" method="POST" class="space-y-6">
            <input type="hidden" name="id" value="<?php echo $polo['id']; ?>">

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
                        <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($polo['nome']); ?>" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    </div>

                    <div>
                        <label for="razao_social" class="block text-sm font-medium text-gray-700 mb-1">Razão Social <span class="text-red-500">*</span></label>
                        <input type="text" id="razao_social" name="razao_social" value="<?php echo htmlspecialchars($polo['razao_social']); ?>" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    </div>

                    <div>
                        <label for="cnpj" class="block text-sm font-medium text-gray-700 mb-1">CNPJ</label>
                        <input type="text" id="cnpj" name="cnpj" value="<?php echo htmlspecialchars($polo['cnpj']); ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    </div>

                    <div>
                        <label for="telefone" class="block text-sm font-medium text-gray-700 mb-1">Telefone <span class="text-red-500">*</span></label>
                        <input type="text" id="telefone" name="telefone" value="<?php echo htmlspecialchars($polo['telefone']); ?>" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">E-mail <span class="text-red-500">*</span></label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($polo['email']); ?>" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    </div>

                    <div>
                        <label for="endereco" class="block text-sm font-medium text-gray-700 mb-1">Endereço <span class="text-red-500">*</span></label>
                        <input type="text" id="endereco" name="endereco" value="<?php echo htmlspecialchars($polo['endereco']); ?>" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    </div>

                    <div>
                        <label for="cidade_id" class="block text-sm font-medium text-gray-700 mb-1">Cidade</label>
                        <div class="relative">
                            <?php
                            // Busca a cidade atual
                            $cidade_nome = '';
                            if (!empty($polo['cidade_id'])) {
                                $sql = "SELECT nome, sigla FROM cidades WHERE id = ?";
                                $cidade = executarConsulta($db, $sql, [$polo['cidade_id']]);
                                if ($cidade) {
                                    $cidade_nome = $cidade['nome'] . '/' . $cidade['sigla'];
                                }
                            }
                            ?>
                            <input type="text" id="cidade_busca"
                                   placeholder="Digite para buscar uma cidade..."
                                   value="<?php echo htmlspecialchars($cidade_nome); ?>"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                            <input type="hidden" id="cidade_id" name="cidade_id" value="<?php echo $polo['cidade_id']; ?>">
                            <div id="cidade_resultados" class="absolute z-10 w-full mt-1 bg-white shadow-lg rounded-md overflow-hidden hidden"></div>
                        </div>
                    </div>

                    <div>
                        <label for="responsavel" class="block text-sm font-medium text-gray-700 mb-1">Responsável</label>
                        <input type="text" id="responsavel" name="responsavel"
                               value="<?php echo htmlspecialchars($polo['responsavel'] ?? ''); ?>"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50"
                               placeholder="Nome do responsável pelo polo">
                    </div>

                    <div>
                        <label for="data_inicio_parceria" class="block text-sm font-medium text-gray-700 mb-1">Data de Início da Parceria</label>
                        <input type="date" id="data_inicio_parceria" name="data_inicio_parceria" value="<?php echo $polo['data_inicio_parceria']; ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    </div>

                    <div>
                        <label for="data_fim_contrato" class="block text-sm font-medium text-gray-700 mb-1">Data de Fim do Contrato</label>
                        <input type="date" id="data_fim_contrato" name="data_fim_contrato" value="<?php echo $polo['data_fim_contrato']; ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    </div>

                    <div>
                        <label for="status_contrato" class="block text-sm font-medium text-gray-700 mb-1">Status do Contrato</label>
                        <select id="status_contrato" name="status_contrato" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                            <option value="ativo" <?php echo $polo['status_contrato'] === 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                            <option value="suspenso" <?php echo $polo['status_contrato'] === 'suspenso' ? 'selected' : ''; ?>>Suspenso</option>
                            <option value="encerrado" <?php echo $polo['status_contrato'] === 'encerrado' ? 'selected' : ''; ?>>Encerrado</option>
                        </select>
                    </div>

                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status do Polo <span class="text-red-500">*</span></label>
                        <select id="status" name="status" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                            <option value="ativo" <?php echo $polo['status'] === 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                            <option value="inativo" <?php echo $polo['status'] === 'inativo' ? 'selected' : ''; ?>>Inativo</option>
                        </select>
                    </div>

                    <div>
                        <label for="limite_documentos" class="block text-sm font-medium text-gray-700 mb-1">Limite de Documentos</label>
                        <input type="number" id="limite_documentos" name="limite_documentos" value="<?php echo $polo['limite_documentos']; ?>" min="0" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                        <p class="text-xs text-gray-500 mt-1">Quantidade máxima de documentos que podem ser emitidos para este polo</p>
                    </div>

                    <div>
                        <label for="documentos_emitidos" class="block text-sm font-medium text-gray-700 mb-1">Documentos Emitidos</label>
                        <input type="number" id="documentos_emitidos" name="documentos_emitidos" value="<?php echo $polo['documentos_emitidos']; ?>" min="0" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                        <p class="text-xs text-gray-500 mt-1">Quantidade de documentos já emitidos para este polo</p>
                    </div>

                    <div class="md:col-span-2">
                        <label for="observacoes" class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                        <textarea id="observacoes" name="observacoes" rows="3"
          class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50"><?php
    echo htmlspecialchars(valorSeguro($polo, 'observacoes', ''));
?></textarea>
                        </div>
                </div>
            </div>

            <!-- Conteúdo da aba Tipos de Polo -->
            <div id="tipos-polo" class="tab-content hidden">
                <div class="mb-4">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Selecione os tipos de polo</h3>
                    <p class="text-sm text-gray-500 mb-4">Um polo pode ter múltiplos tipos. Cada tipo tem suas próprias regras financeiras.</p>

                    <div class="space-y-4">
                        <?php foreach ($tipos_polos as $tipo): ?>
                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                                <input id="tipo_polo_<?php echo $tipo['id']; ?>"
                                       name="tipos_polo[]"
                                       value="<?php echo $tipo['id']; ?>"
                                       type="checkbox"
                                       <?php echo in_array($tipo['id'], $tipos_polo_selecionados) ? 'checked' : ''; ?>
                                       class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
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

// Função para atualizar as configurações financeiras com base nos tipos de polo selecionados
function atualizarFinanceiro() {
    const tiposPoloSelecionados = Array.from(document.querySelectorAll('input[name="tipos_polo[]"]:checked')).map(checkbox => checkbox.value);
    const financeiroDivs = document.getElementById('financeiro-tipos');

    if (tiposPoloSelecionados.length === 0) {
        financeiroDivs.innerHTML = '<p class="text-center text-gray-500 py-4">Selecione os tipos de polo na aba anterior para ver as configurações financeiras.</p>';
        return;
    }

    // Dados das configurações financeiras (do PHP)
    const tiposPolosFinanceiro = <?php echo json_encode($financeiro_por_tipo); ?>;
    const financeiroExistente = <?php echo json_encode($financeiro_polo ?? []); ?>;

    let html = '';
    tiposPoloSelecionados.forEach(tipoId => {
        const config = tiposPolosFinanceiro[tipoId];
        const existente = financeiroExistente[tipoId] || {};

        if (config) {
            html += `
            <div class="bg-gray-50 p-4 rounded-lg">
                <h4 class="font-medium text-gray-900 mb-2">${config.tipo_nome}</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Taxa Inicial</label>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Valor (R$)</label>
                                <input type="number" name="financeiro[${tipoId}][taxa_inicial]" value="${existente.taxa_inicial || config.taxa_inicial}" min="0" step="0.01" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                                <select name="financeiro[${tipoId}][taxa_inicial_paga]" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                                    <option value="0" ${(!existente || !existente.taxa_inicial_paga) ? 'selected' : ''}>Pendente</option>
                                    <option value="1" ${(existente && existente.taxa_inicial_paga) ? 'selected' : ''}>Paga</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data do Pagamento da Taxa</label>
                        <input type="date" name="financeiro[${tipoId}][data_pagamento_taxa]" value="${existente.data_pagamento_taxa || ''}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    </div>`;

            if (parseFloat(config.valor_pacote) > 0) {
                html += `
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Pacotes Adquiridos</label>
                        <input type="number" name="financeiro[${tipoId}][pacotes_adquiridos]" value="${existente.pacotes_adquiridos || 0}" min="0" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                        <p class="text-xs text-gray-500 mt-1">Cada pacote contém ${config.pacote_documentos} documentos e custa R$ ${parseFloat(config.valor_pacote).toFixed(2)}</p>
                    </div>`;
            }

            html += `
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Observações Financeiras</label>
                        <textarea name="financeiro[${tipoId}][observacoes]" rows="2" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">${existente.observacoes || ''}</textarea>
                    </div>
                </div>
            </div>`;
        }
    });

    financeiroDivs.innerHTML = html;
}

// Adiciona evento de change aos checkboxes de tipos de polo
document.querySelectorAll('input[name="tipos_polo[]"]').forEach(checkbox => {
    checkbox.addEventListener('change', atualizarFinanceiro);
});

// Inicializa as configurações financeiras
atualizarFinanceiro();

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
