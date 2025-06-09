<?php
// As funções executarConsulta e executarConsultaAll já estão definidas no arquivo principal

// Busca os tipos de polos disponíveis
$sql = "SELECT id, nome, descricao FROM tipos_polos WHERE status = 'ativo' ORDER BY nome ASC";
$tipos_polos = executarConsultaAll($db, $sql);

// Removidas as consultas relacionadas ao financeiro
?>
<div class="container mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Editar Polo Educacional: <?php echo htmlspecialchars($polo['nome']); ?></h1>
        <a href="polos.php" class="bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded">
            <i class="fas fa-arrow-left mr-2"></i> Voltar
        </a>
    </div>

    <div class="bg-white shadow-md rounded-lg p-6">
        <form action="polos.php?action=salvar_com_tipos" method="POST" class="space-y-6">
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
                        <label for="mec" class="block text-sm font-medium text-gray-700 mb-1">Nome MEC do Polo</label>
                        <input type="text" id="mec" name="mec" value="<?php echo htmlspecialchars($polo['mec'] ?? ''); ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                        <p class="mt-1 text-xs text-gray-500">Este nome será exibido nas declarações como "Polo de Apoio Presencial".</p>
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
                        <label for="cidade" class="block text-sm font-medium text-gray-700 mb-1">Cidade</label>
                        <input type="text" id="cidade" name="cidade"
                               placeholder="Digite o nome da cidade"
                               value="<?php echo htmlspecialchars($polo['cidade'] ?? ''); ?>"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
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
                        <textarea id="observacoes" name="observacoes" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50"><?php echo htmlspecialchars($polo['observacoes'] ?? ''); ?></textarea>
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

            <!-- Conteúdo da aba Financeiro removido -->

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

// Funcionalidades de abas
</script>

<!-- Nenhum script de busca de cidades é necessário -->
