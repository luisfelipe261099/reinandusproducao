/**
 * Implementação minimalista de busca de cidades
 * Esta versão é otimizada para ambientes de produção e evita problemas de performance
 */

/**
 * Inicializa a busca de cidades minimalista
 * @param {Object} options Opções de configuração
 */
function initMinimalCitySearch(options) {
    const inputElement = document.querySelector(options.inputSelector);
    const hiddenInputElement = document.querySelector(options.hiddenInputSelector);
    
    if (!inputElement || !hiddenInputElement) {
        console.error('Elementos necessários não encontrados');
        return;
    }
    
    // Adiciona o botão de busca
    const searchButton = document.createElement('button');
    searchButton.type = 'button';
    searchButton.className = 'mt-2 bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md';
    searchButton.innerHTML = 'Buscar Cidade';
    
    // Insere o botão após o campo de entrada
    inputElement.parentNode.appendChild(searchButton);
    
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
                    <h3 class="text-lg font-medium">Selecionar Cidade</h3>
                    <button type="button" class="text-gray-400 hover:text-gray-500" id="closeModal">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Digite o nome da cidade e UF (ex: São Paulo/SP)</label>
                    <div class="flex">
                        <input type="text" id="cityNameInput" class="flex-1 px-3 py-2 border border-gray-300 rounded-l-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" placeholder="Nome da cidade">
                        <input type="text" id="cityUfInput" class="w-20 px-3 py-2 border border-gray-300 border-l-0 rounded-r-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" placeholder="UF" maxlength="2">
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-medium py-2 px-4 rounded" id="cancelCitySearch">
                        Cancelar
                    </button>
                    <button type="button" class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded" id="confirmCitySelection">
                        Selecionar
                    </button>
                </div>
            </div>
        `;
        
        // Adiciona o modal ao body
        document.body.appendChild(modal);
        
        // Foca no campo de nome da cidade
        const cityNameInput = document.getElementById('cityNameInput');
        setTimeout(() => cityNameInput.focus(), 100);
        
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
        
        // Evento para confirmar a seleção
        document.getElementById('confirmCitySelection').addEventListener('click', function() {
            const cityName = document.getElementById('cityNameInput').value.trim();
            const cityUf = document.getElementById('cityUfInput').value.trim().toUpperCase();
            
            if (!cityName) {
                alert('Por favor, digite o nome da cidade.');
                return;
            }
            
            if (!cityUf || cityUf.length !== 2) {
                alert('Por favor, digite a UF da cidade (2 letras).');
                return;
            }
            
            // Atualiza o campo de exibição
            inputElement.value = `${cityName}/${cityUf}`;
            
            // Gera um ID temporário baseado no nome e UF
            // Isso será substituído por um ID real quando o formulário for enviado
            const tempId = `temp_${cityName.replace(/[^a-zA-Z0-9]/g, '')}_${cityUf}`;
            hiddenInputElement.value = tempId;
            
            // Fecha o modal
            closeModal();
        });
    });
}
