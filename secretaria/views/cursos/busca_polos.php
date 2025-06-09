<div class="modal-overlay fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden" id="modal-busca-polos" onclick="fecharModalBuscaPolosClicandoFora(event)">
    <div class="modal-container bg-white w-11/12 md:max-w-2xl mx-auto rounded shadow-lg z-50 overflow-y-auto">
        <div class="modal-content py-4 text-left px-6">
            <!-- Cabeçalho -->
            <div class="flex justify-between items-center pb-3 border-b">
                <p class="text-xl font-bold">Buscar Polo</p>
                <button type="button" class="modal-close text-gray-500 hover:text-gray-800 focus:outline-none" onclick="fecharModalBuscaPolos()">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <!-- Campo de busca -->
            <div class="mb-4">
                <div class="flex">
                    <input type="text" id="busca-polo-termo" class="form-input flex-grow" placeholder="Digite o nome do polo...">
                    <button id="btn-limpar-busca" class="btn-secondary ml-2" type="button" onclick="limparBusca()">
                        <i class="fas fa-times mr-2"></i> Limpar
                    </button>
                    <button id="btn-buscar-polo" class="btn-primary ml-2" type="button" onclick="buscarPolosManualmente()">
                        <i class="fas fa-search mr-2"></i> Buscar
                    </button>
                </div>
            </div>

            <!-- Resultados da busca -->
            <div class="overflow-x-auto">
                <div class="text-xs text-gray-500 mb-2 italic">
                    <i class="fas fa-info-circle text-blue-500 mr-1"></i> Dica: Clique duplo em um polo para selecioná-lo rapidamente.
                </div>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Selecionar
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Nome
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Cidade/UF
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200" id="resultados-busca-polos">
                        <!-- Os resultados serão inseridos aqui via JavaScript -->
                    </tbody>
                </table>
            </div>

            <!-- Paginação -->
            <div class="flex justify-between items-center mt-4" id="paginacao-busca-polos">
                <div>
                    <span class="text-sm text-gray-700" id="info-paginacao-polos">
                        Mostrando <span id="inicio-registros">0</span> a <span id="fim-registros">0</span> de <span id="total-registros">0</span> registros
                    </span>
                </div>
                <div class="flex space-x-1">
                    <button id="btn-pagina-anterior" class="btn-secondary px-3 py-1 text-sm" disabled>
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button id="btn-pagina-proxima" class="btn-secondary px-3 py-1 text-sm" disabled>
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>

            <!-- Botões de ação -->
            <div class="flex justify-end pt-4 border-t mt-4">
                <button type="button" class="btn-secondary mr-2" onclick="fecharModalBuscaPolos()">
                    <i class="fas fa-times mr-2"></i> Cancelar
                </button>
                <button type="button" class="btn-primary" id="btn-selecionar-polo" onclick="selecionarPoloEFechar()" disabled>
                    <i class="fas fa-check mr-2"></i> Selecionar
                </button>
            </div>
        </div>
    </div>
</div>
