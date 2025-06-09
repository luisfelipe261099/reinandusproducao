/**
 * Implementação simplificada de busca de cidades usando o IBGE
 * Esta versão é otimizada para evitar travamentos e melhorar a performance
 */

/**
 * Inicializa a busca de cidades simplificada
 * @param {Object} options Opções de configuração
 */
function initSimpleCitySearch(options) {
    const inputElement = document.querySelector(options.inputSelector);
    const hiddenInputElement = document.querySelector(options.hiddenInputSelector);

    if (!inputElement || !hiddenInputElement) {
        console.error('Elementos necessários não encontrados');
        return;
    }

    // Adiciona o campo de busca e o botão
    const searchContainer = document.createElement('div');
    searchContainer.className = 'flex mt-2';

    const searchButton = document.createElement('button');
    searchButton.type = 'button';
    searchButton.className = 'bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md';
    searchButton.innerHTML = 'Buscar Cidade';

    // Insere os elementos no DOM
    inputElement.parentNode.appendChild(searchContainer);
    searchContainer.appendChild(searchButton);

    // Adiciona o evento de clique ao botão
    searchButton.addEventListener('click', function() {
        // Cria o modal
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50';
        modal.id = 'citySearchModal';

        // Conteúdo do modal
        modal.innerHTML = `
            <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-lg">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium">Buscar Cidade</h3>
                    <button type="button" class="text-gray-400 hover:text-gray-500" id="closeModal">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Digite o nome da cidade</label>
                    <input type="text" id="citySearchInput" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" placeholder="Digite pelo menos 3 caracteres...">
                </div>

                <div id="citySearchResults" class="max-h-60 overflow-y-auto mb-4">
                    <div class="text-gray-500 text-center py-4">Digite pelo menos 3 caracteres para buscar</div>
                </div>

                <div class="flex justify-end">
                    <button type="button" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-medium py-2 px-4 rounded mr-2" id="cancelCitySearch">
                        Cancelar
                    </button>
                </div>
            </div>
        `;

        // Adiciona o modal ao body
        document.body.appendChild(modal);

        // Foca no campo de busca
        const searchInput = document.getElementById('citySearchInput');
        setTimeout(() => searchInput.focus(), 100);

        // Função para fechar o modal
        function closeModal() {
            document.body.removeChild(modal);
        }

        // Eventos para fechar o modal
        document.getElementById('closeModal').addEventListener('click', closeModal);
        document.getElementById('cancelCitySearch').addEventListener('click', closeModal);
        modal.addEventListener('click', function(e) {
            if (e.target === modal) closeModal();
        });

        // Variáveis para controle da busca
        let searchTimeout;
        const resultsContainer = document.getElementById('citySearchResults');

        // Evento de busca
        searchInput.addEventListener('input', function() {
            const term = this.value.trim();
            clearTimeout(searchTimeout);

            if (term.length < 3) {
                resultsContainer.innerHTML = '<div class="text-gray-500 text-center py-4">Digite pelo menos 3 caracteres para buscar</div>';
                return;
            }

            // Mostra indicador de carregamento
            resultsContainer.innerHTML = '<div class="text-center py-4"><div class="inline-block animate-spin rounded-full h-6 w-6 border-t-2 border-b-2 border-blue-500"></div><div class="mt-2 text-gray-600">Buscando...</div></div>';

            // Debounce para evitar muitas requisições
            searchTimeout = setTimeout(() => {
                // Faz a requisição para o backend
                console.log(`Buscando cidades com termo: ${term}`);
                fetch(`polos.php?action=buscar_cidades&termo=${encodeURIComponent(term)}`)
                    .then(response => {
                        console.log(`Resposta recebida: ${response.status}`);
                        return response.json();
                    })
                    .then(data => {
                        console.log(`Dados recebidos:`, data);
                        if (data.error) {
                            resultsContainer.innerHTML = `<div class="text-red-500 text-center py-4">${data.error}</div>`;
                            return;
                        }

                        if (!data.cidades || data.cidades.length === 0) {
                            resultsContainer.innerHTML = '<div class="text-gray-500 text-center py-4">Nenhuma cidade encontrada</div>';
                            return;
                        }

                        // Limita a 15 resultados para evitar travamentos
                        const limitedResults = data.cidades.slice(0, 15);
                        let html = '';

                        limitedResults.forEach(cidade => {
                            html += `<div class="p-3 hover:bg-gray-100 cursor-pointer border-b city-result"
                                         data-id="${cidade.id}"
                                         data-nome="${cidade.nome}"
                                         data-uf="${cidade.sigla}">
                                        ${cidade.nome} - ${cidade.sigla}
                                    </div>`;
                        });

                        if (data.cidades.length > 15) {
                            html += `<div class="p-2 text-gray-500 text-center text-sm">+ ${data.cidades.length - 15} resultados. Refine sua busca para ver mais.</div>`;
                        }

                        resultsContainer.innerHTML = html;

                        // Adiciona eventos de clique aos resultados
                        document.querySelectorAll('.city-result').forEach(item => {
                            item.addEventListener('click', function() {
                                const id = this.getAttribute('data-id');
                                const nome = this.getAttribute('data-nome');
                                const uf = this.getAttribute('data-uf');

                                // Atualiza os campos
                                inputElement.value = `${nome}/${uf}`;
                                hiddenInputElement.value = id;

                                // Fecha o modal
                                closeModal();

                                // Dispara evento de change para notificar outros scripts
                                const event = new Event('change', { bubbles: true });
                                hiddenInputElement.dispatchEvent(event);
                            });
                        });
                    })
                    .catch(error => {
                        console.error('Erro ao buscar cidades:', error);
                        resultsContainer.innerHTML = '<div class="text-red-500 text-center py-4">Erro ao buscar cidades. Tente novamente.</div>';
                        console.log('Erro completo:', error);
                    });
            }, 300);
        });
    });
}
