<!-- Cidade (usando API do IBGE) -->
<div class="col-span-2">
    <label for="cidade" class="block text-sm font-medium text-gray-700 mb-1">Cidade</label>
    <input type="text" id="cidade" name="cidade" value="<?php echo htmlspecialchars($polo['cidade'] ?? ''); ?>" 
           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
    <input type="hidden" id="cidade_ibge" name="cidade_ibge" value="<?php echo htmlspecialchars($polo['cidade_ibge'] ?? ''); ?>">
</div>

<script>
    // Integração com API do IBGE para cidades
    document.addEventListener('DOMContentLoaded', function() {
        const cidadeInput = document.getElementById('cidade');
        const cidadeIbgeInput = document.getElementById('cidade_ibge');
        let timeout = null;
        
        cidadeInput.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                const termo = cidadeInput.value.trim();
                if (termo.length >= 3) {
                    fetch(`https://servicodados.ibge.gov.br/api/v1/localidades/municipios?nome=${encodeURIComponent(termo)}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data && data.length > 0) {
                                // Criar lista de sugestões
                                const sugestoes = document.createElement('div');
                                sugestoes.id = 'sugestoes-cidades';
                                sugestoes.className = 'absolute z-10 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-y-auto w-full';
                                
                                data.forEach(cidade => {
                                    const item = document.createElement('div');
                                    item.className = 'px-4 py-2 hover:bg-gray-100 cursor-pointer';
                                    item.textContent = `${cidade.nome} - ${cidade.microrregiao.mesorregiao.UF.sigla}`;
                                    item.onclick = function() {
                                        cidadeInput.value = `${cidade.nome} - ${cidade.microrregiao.mesorregiao.UF.sigla}`;
                                        cidadeIbgeInput.value = cidade.id;
                                        sugestoes.remove();
                                    };
                                    sugestoes.appendChild(item);
                                });
                                
                                // Remover sugestões anteriores
                                const sugestoesAnteriores = document.getElementById('sugestoes-cidades');
                                if (sugestoesAnteriores) {
                                    sugestoesAnteriores.remove();
                                }
                                
                                // Adicionar novas sugestões
                                cidadeInput.parentNode.style.position = 'relative';
                                cidadeInput.parentNode.appendChild(sugestoes);
                            }
                        })
                        .catch(error => console.error('Erro ao buscar cidades:', error));
                }
            }, 300);
        });
        
        // Fechar sugestões ao clicar fora
        document.addEventListener('click', function(e) {
            if (e.target !== cidadeInput) {
                const sugestoes = document.getElementById('sugestoes-cidades');
                if (sugestoes) {
                    sugestoes.remove();
                }
            }
        });
    });
</script>