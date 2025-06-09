<?php
// As funções executarConsulta e executarConsultaAll já estão definidas no arquivo principal
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
                    <label for="responsavel_id" class="block text-sm font-medium text-gray-700 mb-1">Responsável</label>
                    <div class="relative">
                        <input type="text" id="responsavel_busca"
                               placeholder="Digite para buscar um responsável..."
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                        <input type="hidden" id="responsavel_id" name="responsavel_id" value="">
                        <div id="responsavel_resultados" class="absolute z-10 w-full mt-1 bg-white shadow-lg rounded-md overflow-hidden hidden"></div>
                    </div>
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

                <div>
                    <label for="limite_documentos" class="block text-sm font-medium text-gray-700 mb-1">Limite de Documentos</label>
                    <input type="number" id="limite_documentos" name="limite_documentos" value="100" min="0" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    <p class="text-xs text-gray-500 mt-1">Quantidade máxima de documentos que podem ser emitidos para este polo</p>
                </div>

                <div>
                    <label for="documentos_emitidos" class="block text-sm font-medium text-gray-700 mb-1">Documentos Emitidos</label>
                    <input type="number" id="documentos_emitidos" name="documentos_emitidos" value="0" min="0" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    <p class="text-xs text-gray-500 mt-1">Quantidade de documentos já emitidos para este polo</p>
                </div>

                <div class="md:col-span-2">
                    <label for="observacoes" class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                    <textarea id="observacoes" name="observacoes" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50"></textarea>
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

// Função para buscar responsáveis
let timeoutResponsavel;
document.getElementById('responsavel_busca').addEventListener('input', function() {
    const termo = this.value.trim();
    clearTimeout(timeoutResponsavel);

    // Limpa os resultados se o campo estiver vazio
    if (termo.length === 0) {
        document.getElementById('responsavel_resultados').classList.add('hidden');
        document.getElementById('responsavel_resultados').innerHTML = '';
        document.getElementById('responsavel_id').value = '';
        return;
    }

    // Aguarda um pouco antes de fazer a busca para evitar muitas requisições
    timeoutResponsavel = setTimeout(() => {
        if (termo.length < 2) return;

        // Faz a requisição AJAX
        fetch(`polos.php?action=buscar_responsaveis&termo=${encodeURIComponent(termo)}`)
            .then(response => response.json())
            .then(data => {
                const resultadosDiv = document.getElementById('responsavel_resultados');

                if (data.error) {
                    resultadosDiv.innerHTML = `<div class="p-2 text-red-500">${data.error}</div>`;
                    resultadosDiv.classList.remove('hidden');
                    return;
                }

                if (data.responsaveis.length === 0) {
                    resultadosDiv.innerHTML = '<div class="p-2 text-gray-500">Nenhum responsável encontrado</div>';
                    resultadosDiv.classList.remove('hidden');
                    return;
                }

                // Renderiza os resultados
                let html = '';
                data.responsaveis.forEach(responsavel => {
                    html += `<div class="p-2 hover:bg-gray-100 cursor-pointer"
                                 onclick="selecionarResponsavel(${responsavel.id}, '${responsavel.nome.replace(/'/g, "\\'")}')">
                                ${responsavel.nome}
                             </div>`;
                });

                resultadosDiv.innerHTML = html;
                resultadosDiv.classList.remove('hidden');
            })
            .catch(error => {
                console.error('Erro ao buscar responsáveis:', error);
                document.getElementById('responsavel_resultados').innerHTML = '<div class="p-2 text-red-500">Erro ao buscar responsáveis</div>';
                document.getElementById('responsavel_resultados').classList.remove('hidden');
            });
    }, 300);
});

// Função para selecionar um responsável
function selecionarResponsavel(id, nome) {
    document.getElementById('responsavel_id').value = id;
    document.getElementById('responsavel_busca').value = nome;
    document.getElementById('responsavel_resultados').classList.add('hidden');
}

// Fecha os resultados do responsável ao clicar fora
document.addEventListener('click', function(event) {
    if (!event.target.closest('#responsavel_busca') && !event.target.closest('#responsavel_resultados')) {
        document.getElementById('responsavel_resultados').classList.add('hidden');
    }
});
});
</script>
