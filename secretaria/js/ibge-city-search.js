/**
 * IBGE City Search Component
 *
 * This script provides a reusable function to implement city search using the IBGE API.
 * It handles debouncing, loading states, and error handling.
 */

/**
 * Initialize IBGE city search on an input element
 *
 * @param {Object} options Configuration options
 * @param {string} options.inputSelector Selector for the search input field
 * @param {string} options.resultsSelector Selector for the results container
 * @param {string} options.hiddenInputSelector Selector for the hidden input that will store the city ID
 * @param {Function} options.onSelect Callback function when a city is selected (optional)
 */
function initIBGECitySearch(options) {
    const inputElement = document.querySelector(options.inputSelector);
    const resultsElement = document.querySelector(options.resultsSelector);
    const hiddenInputElement = document.querySelector(options.hiddenInputSelector);

    if (!inputElement || !resultsElement || !hiddenInputElement) {
        console.error('IBGE City Search: Required elements not found');
        return;
    }

    let searchTimeout;

    // Add input event listener with debouncing
    inputElement.addEventListener('input', function() {
        const searchTerm = this.value.trim();
        clearTimeout(searchTimeout);

        // Clear results if search term is empty
        if (searchTerm.length === 0) {
            resultsElement.classList.add('hidden');
            resultsElement.innerHTML = '';
            hiddenInputElement.value = '';
            return;
        }

        // Show loading indicator
        resultsElement.innerHTML = '<div class="p-2 text-gray-500">Buscando cidades...</div>';
        resultsElement.classList.remove('hidden');

        // Debounce the search to avoid excessive API calls
        searchTimeout = setTimeout(() => {
            // Only search if term has at least 3 characters to avoid too many results
            if (searchTerm.length < 3) {
                resultsElement.innerHTML = '<div class="p-2 text-gray-500">Digite pelo menos 3 caracteres para buscar</div>';
                resultsElement.classList.remove('hidden');
                return;
            }

            // Show loading indicator
            resultsElement.innerHTML = '<div class="p-2 text-gray-500"><div class="animate-pulse flex"><div class="flex-1 space-y-2"><div class="h-4 bg-gray-200 rounded"></div><div class="h-4 bg-gray-200 rounded w-5/6"></div></div></div></div>';
            resultsElement.classList.remove('hidden');

            // Make the API request to our backend endpoint which handles IBGE API
            fetch(`polos.php?action=buscar_cidades&termo=${encodeURIComponent(searchTerm)}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Falha na resposta da API');
                    }
                    return response.json();
                })
                .then(data => {
                    // Handle errors
                    if (data.error) {
                        resultsElement.innerHTML = `<div class="p-2 text-red-500">${data.error}</div>`;
                        resultsElement.classList.remove('hidden');
                        return;
                    }

                    // Handle empty results
                    if (!data.cidades || data.cidades.length === 0) {
                        resultsElement.innerHTML = '<div class="p-2 text-gray-500">Nenhuma cidade encontrada</div>';
                        resultsElement.classList.remove('hidden');
                        return;
                    }

                    // Render results (limit to 8 to prevent freezing)
                    let html = '';
                    const limitedResults = data.cidades.slice(0, 8);

                    // Highlight the matching part of the city name
                    limitedResults.forEach(cidade => {
                        const nomeCidade = cidade.nome;
                        const uf = cidade.sigla;
                        const id = cidade.id;

                        // Highlight the matching part (case insensitive)
                        let displayName = nomeCidade;
                        const searchTermLower = searchTerm.toLowerCase();
                        const indexOfMatch = nomeCidade.toLowerCase().indexOf(searchTermLower);

                        if (indexOfMatch >= 0) {
                            const beforeMatch = nomeCidade.substring(0, indexOfMatch);
                            const match = nomeCidade.substring(indexOfMatch, indexOfMatch + searchTerm.length);
                            const afterMatch = nomeCidade.substring(indexOfMatch + searchTerm.length);
                            displayName = `${beforeMatch}<strong class="text-blue-600">${match}</strong>${afterMatch}`;
                        }

                        html += `<div class="p-2 hover:bg-gray-100 cursor-pointer"
                                     data-id="${id}"
                                     data-nome="${nomeCidade}"
                                     data-uf="${uf}">
                                    ${displayName} - ${uf}
                                </div>`;
                    });

                    // Add a message if there are more results
                    if (data.cidades.length > 8) {
                        const remaining = data.cidades.length - 8;
                        html += `<div class="p-2 text-gray-500 text-center text-sm italic">+ ${remaining} resultados. Digite mais caracteres para refinar a busca.</div>`;
                    }

                    resultsElement.innerHTML = html;
                    resultsElement.classList.remove('hidden');

                    // Add click event listeners to results
                    resultsElement.querySelectorAll('div[data-id]').forEach(item => {
                        item.addEventListener('click', function() {
                            const id = this.getAttribute('data-id');
                            const nome = this.getAttribute('data-nome');
                            const uf = this.getAttribute('data-uf');

                            // Update input and hidden field
                            inputElement.value = `${nome}/${uf}`;
                            hiddenInputElement.value = id;
                            resultsElement.classList.add('hidden');

                            // Call the onSelect callback if provided
                            if (typeof options.onSelect === 'function') {
                                options.onSelect(id, nome, uf);
                            }
                        });
                    });
                })
                .catch(error => {
                    console.error('Erro ao buscar cidades:', error);
                    resultsElement.innerHTML = '<div class="p-2 text-red-500">Erro ao buscar cidades. Tente novamente.</div>';
                    resultsElement.classList.remove('hidden');
                });
        }, 200); // 200ms debounce para resposta mais rápida
    });

    // Close results when clicking outside
    document.addEventListener('click', function(event) {
        if (!event.target.closest(options.inputSelector) && !event.target.closest(options.resultsSelector)) {
            resultsElement.classList.add('hidden');
        }
    });

    // Handle keyboard navigation
    inputElement.addEventListener('keydown', function(event) {
        // If results are hidden, don't do anything
        if (resultsElement.classList.contains('hidden')) {
            return;
        }

        const items = resultsElement.querySelectorAll('div[data-id]');
        const activeItem = resultsElement.querySelector('.bg-gray-100');
        let index = -1;

        if (activeItem) {
            // Find the index of the active item
            for (let i = 0; i < items.length; i++) {
                if (items[i] === activeItem) {
                    index = i;
                    break;
                }
            }
        }

        // Handle arrow down
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            if (index < items.length - 1) {
                if (activeItem) activeItem.classList.remove('bg-gray-100');
                items[index + 1].classList.add('bg-gray-100');
                items[index + 1].scrollIntoView({ block: 'nearest' });
            }
        }

        // Handle arrow up
        else if (event.key === 'ArrowUp') {
            event.preventDefault();
            if (index > 0) {
                if (activeItem) activeItem.classList.remove('bg-gray-100');
                items[index - 1].classList.add('bg-gray-100');
                items[index - 1].scrollIntoView({ block: 'nearest' });
            }
        }

        // Handle enter key
        else if (event.key === 'Enter') {
            event.preventDefault();
            if (activeItem) {
                activeItem.click();
            }
        }

        // Handle escape key
        else if (event.key === 'Escape') {
            event.preventDefault();
            resultsElement.classList.add('hidden');
        }
    });
}
