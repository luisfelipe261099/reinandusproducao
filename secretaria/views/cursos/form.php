<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <form action="cursos.php?action=salvar" method="post" class="p-6">
        <?php if (isset($curso['id'])): ?>
        <input type="hidden" name="id" value="<?php echo $curso['id']; ?>">
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Informações Básicas -->
            <div class="col-span-1 md:col-span-2">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Informações Básicas</h2>
            </div>

            <!-- Nome -->
            <div class="col-span-1 md:col-span-2">
                <label for="nome" class="block text-sm font-medium text-gray-700 mb-1">Nome do Curso *</label>
                <input type="text" name="nome" id="nome" value="<?php echo isset($curso['nome']) ? htmlspecialchars($curso['nome']) : ''; ?>" required class="form-input w-full">
            </div>

            <!-- ID Legado -->
            <div>
                <label for="id_legado" class="block text-sm font-medium text-gray-700 mb-1">ID Legado</label>
                <input type="text" name="id_legado" id="id_legado" value="<?php echo isset($curso['id_legado']) ? htmlspecialchars($curso['id_legado']) : ''; ?>" class="form-input w-full">
                <p class="text-xs text-gray-500 mt-1">Identificador do curso no sistema legado</p>
            </div>

            <!-- Área de Conhecimento -->
            <div>
                <label for="area_conhecimento_id" class="block text-sm font-medium text-gray-700 mb-1">Área de Conhecimento</label>
                <select name="area_conhecimento_id" id="area_conhecimento_id" class="form-select w-full">
                    <option value="">Selecione uma área...</option>
                    <?php foreach ($areas as $area): ?>
                    <option value="<?php echo $area['id']; ?>" <?php echo isset($curso['area_conhecimento_id']) && $curso['area_conhecimento_id'] == $area['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($area['nome']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Status -->
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" id="status" class="form-select w-full">
                    <option value="ativo" <?php echo isset($curso['status']) && $curso['status'] === 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                    <option value="inativo" <?php echo isset($curso['status']) && $curso['status'] === 'inativo' ? 'selected' : ''; ?>>Inativo</option>
                </select>
            </div>

            <!-- Descrição -->
            <div class="col-span-1 md:col-span-2">
                <label for="descricao" class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
                <textarea name="descricao" id="descricao" rows="4" class="form-textarea w-full"><?php echo isset($curso['descricao']) ? htmlspecialchars($curso['descricao']) : ''; ?></textarea>
            </div>

            <!-- Informações Acadêmicas -->
            <div class="col-span-1 md:col-span-2">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 mt-4">Informações Acadêmicas</h2>
            </div>

            <!-- Nível -->
            <div>
                <label for="nivel" class="block text-sm font-medium text-gray-700 mb-1">Nível *</label>
                <select name="nivel" id="nivel" class="form-select w-full" required>
                    <option value="">Selecione um nível...</option>
                    <option value="graduacao" <?php echo isset($curso['nivel']) && $curso['nivel'] === 'graduacao' ? 'selected' : ''; ?>>Graduação</option>
                    <option value="pos_graduacao" <?php echo isset($curso['nivel']) && $curso['nivel'] === 'pos_graduacao' ? 'selected' : ''; ?>>Pós-Graduação</option>
                    <option value="mestrado" <?php echo isset($curso['nivel']) && $curso['nivel'] === 'mestrado' ? 'selected' : ''; ?>>Mestrado</option>
                    <option value="doutorado" <?php echo isset($curso['nivel']) && $curso['nivel'] === 'doutorado' ? 'selected' : ''; ?>>Doutorado</option>
                    <option value="tecnico" <?php echo isset($curso['nivel']) && $curso['nivel'] === 'tecnico' ? 'selected' : ''; ?>>Técnico</option>
                    <option value="extensao" <?php echo isset($curso['nivel']) && $curso['nivel'] === 'extensao' ? 'selected' : ''; ?>>Extensão</option>
                </select>
            </div>

            <!-- Modalidade -->
            <div>
                <label for="modalidade" class="block text-sm font-medium text-gray-700 mb-1">Modalidade *</label>
                <select name="modalidade" id="modalidade" class="form-select w-full" required>
                    <option value="">Selecione uma modalidade...</option>
                    <option value="presencial" <?php echo isset($curso['modalidade']) && $curso['modalidade'] === 'presencial' ? 'selected' : ''; ?>>Presencial</option>
                    <option value="ead" <?php echo isset($curso['modalidade']) && $curso['modalidade'] === 'ead' ? 'selected' : ''; ?>>EAD</option>
                    <option value="hibrido" <?php echo isset($curso['modalidade']) && $curso['modalidade'] === 'hibrido' ? 'selected' : ''; ?>>Híbrido</option>
                </select>
            </div>

            <!-- Carga Horária -->
            <div>
                <label for="carga_horaria" class="block text-sm font-medium text-gray-700 mb-1">Carga Horária (horas)</label>
                <input type="number" name="carga_horaria" id="carga_horaria" value="<?php echo isset($curso['carga_horaria']) ? $curso['carga_horaria'] : ''; ?>" min="0" class="form-input w-full">
            </div>

            <!-- Sigla -->
            <div>
                <label for="sigla" class="block text-sm font-medium text-gray-700 mb-1">Sigla</label>
                <input type="text" name="sigla" id="sigla" value="<?php echo isset($curso['sigla']) ? htmlspecialchars($curso['sigla']) : ''; ?>" class="form-input w-full">
            </div>

            <!-- Data de Início -->
            <div>
                <label for="data_inicio" class="block text-sm font-medium text-gray-700 mb-1">Data de Início</label>
                <input type="date" name="data_inicio" id="data_inicio" value="<?php echo isset($curso['data_inicio']) ? $curso['data_inicio'] : ''; ?>" class="form-input w-full">
            </div>

            <!-- Data de Fim -->
            <div>
                <label for="data_fim" class="block text-sm font-medium text-gray-700 mb-1">Data de Fim</label>
                <input type="date" name="data_fim" id="data_fim" value="<?php echo isset($curso['data_fim']) ? $curso['data_fim'] : ''; ?>" class="form-input w-full">
            </div>

            <!-- Polos -->
            <div class="col-span-1 md:col-span-2">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 mt-4">Polo Principal *</h2>
                <p class="text-sm text-gray-500 mb-4">Selecione o polo principal onde este curso será oferecido.</p>

                <div class="flex items-center mb-4">
                    <div class="w-full">
                        <div class="flex">
                            <div class="flex-grow">
                                <input type="hidden" name="polo_id" id="polo_id" value="<?php echo isset($curso['polo_id']) ? $curso['polo_id'] : ''; ?>" required>
                                <input type="text" id="polo_nome" class="form-input w-full" placeholder="Selecione um polo..." readonly
                                    value="<?php
                                        if (isset($curso['polo_id']) && !empty($curso['polo_id'])) {
                                            foreach ($polos as $polo) {
                                                if ($polo['id'] == $curso['polo_id']) {
                                                    echo htmlspecialchars($polo['nome']);
                                                    break;
                                                }
                                            }
                                        }
                                    ?>">
                            </div>
                            <button type="button" class="btn-secondary ml-2" onclick="abrirModalBuscaPolos()">
                                <i class="fas fa-search mr-2"></i> Buscar Polo
                            </button>
                        </div>
                        <p class="text-xs text-red-500 mt-1" id="erro-polo" style="display: none;">Selecione um polo para o curso</p>
                    </div>
                </div>
            </div>


        </div>

        <div class="mt-6 flex items-center justify-end space-x-3">
            <a href="cursos.php?action=dashboard" class="btn-secondary">Cancelar</a>
            <button type="submit" class="btn-primary">Salvar</button>
        </div>
    </form>
</div>

<script>
    // Inicializa os campos de data se estiverem vazios
    if (!document.getElementById('data_inicio').value) {
        const hoje = new Date().toISOString().split('T')[0];
        document.getElementById('data_inicio').value = hoje;
    }

    // Validação para garantir que um polo seja selecionado
    document.querySelector('form').addEventListener('submit', function(e) {
        const poloId = document.getElementById('polo_id').value;
        let hasErrors = false;

        // Verifica se o polo foi selecionado
        if (!poloId) {
            e.preventDefault();
            hasErrors = true;
            document.getElementById('erro-polo').style.display = 'block';
            document.getElementById('polo_nome').classList.add('border-red-500');

            // Destaca a seção de polos
            const polosSection = document.querySelector('h2.text-lg.font-semibold');
            if (polosSection) {
                polosSection.scrollIntoView({ behavior: 'smooth' });
                polosSection.classList.add('text-red-600');
                setTimeout(function() {
                    polosSection.classList.remove('text-red-600');
                }, 2000);
            }
        } else {
            document.getElementById('erro-polo').style.display = 'none';
            document.getElementById('polo_nome').classList.remove('border-red-500');
        }


    });



    // Variáveis para paginação de polos
    let paginaAtual = 1;
    let totalPaginas = 1;
    let poloSelecionado = null;

    // Função para abrir o modal de busca de polos
    function abrirModalBuscaPolos() {
        document.getElementById('modal-busca-polos').classList.remove('hidden');
        document.getElementById('busca-polo-termo').focus();

        // Impede que o scroll da página funcione quando a modal está aberta
        document.body.style.overflow = 'hidden';

        // Exibe uma mensagem inicial
        document.getElementById('resultados-busca-polos').innerHTML = `
            <tr>
                <td colspan="3" class="px-6 py-4 text-center">
                    <div class="flex flex-col items-center justify-center py-4">
                        <i class="fas fa-search text-gray-400 text-4xl mb-3"></i>
                        <p class="text-gray-500">Digite o nome do polo para buscar</p>
                    </div>
                </td>
            </tr>
        `;

        // Adiciona evento para fechar a modal com a tecla ESC
        document.addEventListener('keydown', fecharModalComEsc);

        // Carrega a primeira página de polos
        buscarPolos();
    }

    // Função para fechar a modal com a tecla ESC
    function fecharModalComEsc(e) {
        if (e.key === 'Escape') {
            fecharModalBuscaPolos();
        }
    }

    // Função para fechar o modal de busca de polos
    function fecharModalBuscaPolos() {
        document.getElementById('modal-busca-polos').classList.add('hidden');

        // Restaura o scroll da página
        document.body.style.overflow = 'auto';

        // Remove o evento de tecla ESC
        document.removeEventListener('keydown', fecharModalComEsc);
    }

    // Função para fechar a modal clicando fora dela
    function fecharModalBuscaPolosClicandoFora(event) {
        const modalContainer = document.querySelector('.modal-container');
        if (event.target !== modalContainer && !modalContainer.contains(event.target)) {
            fecharModalBuscaPolos();
        }
    }

    // Variável para controlar o tempo de espera entre digitações
    let timeoutBusca;

    // Função para buscar polos
    function buscarPolos() {
        const termo = document.getElementById('busca-polo-termo').value;

        // Mostra indicador de carregamento
        document.getElementById('resultados-busca-polos').innerHTML = `
            <tr>
                <td colspan="3" class="px-6 py-4 text-center">
                    <div class="flex flex-col items-center justify-center py-4">
                        <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-500 mb-3"></div>
                        <p class="text-gray-500">Carregando polos...</p>
                    </div>
                </td>
            </tr>
        `;

        // URL da requisição
        const url = `api/buscar_polos.php?termo=${encodeURIComponent(termo)}&pagina=${paginaAtual}&por_pagina=5`;
        console.log('Buscando polos:', url);

        // Define um timeout para a requisição
        const timeoutPromise = new Promise((_, reject) => {
            setTimeout(() => reject(new Error('Timeout da requisição')), 5000);
        });

        // Faz a requisição AJAX com timeout
        Promise.race([
            fetch(url),
            timeoutPromise
        ])
            .then(response => {
                console.log('Status da resposta:', response.status);
                if (!response.ok) {
                    throw new Error(`Erro HTTP: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Dados recebidos:', data);

                if (data.success) {
                    // Atualiza as variáveis de paginação
                    paginaAtual = data.data.paginacao.pagina;
                    totalPaginas = data.data.paginacao.total_paginas;

                    // Atualiza os botões de paginação
                    document.getElementById('btn-pagina-anterior').disabled = paginaAtual <= 1;
                    document.getElementById('btn-pagina-proxima').disabled = paginaAtual >= totalPaginas;

                    // Atualiza as informações de paginação
                    const inicio = (paginaAtual - 1) * data.data.paginacao.por_pagina + 1;
                    const fim = Math.min(inicio + data.data.paginacao.por_pagina - 1, data.data.paginacao.total);

                    document.getElementById('inicio-registros').textContent = data.data.paginacao.total > 0 ? inicio : 0;
                    document.getElementById('fim-registros').textContent = fim;
                    document.getElementById('total-registros').textContent = data.data.paginacao.total;

                    // Habilita o botão de selecionar se já houver um polo selecionado
                    document.getElementById('btn-selecionar-polo').disabled = !poloSelecionado;

                    // Renderiza os resultados
                    renderizarResultadosPolos(data.data.polos);
                } else {
                    // Exibe mensagem de erro
                    document.getElementById('resultados-busca-polos').innerHTML = `
                        <tr>
                            <td colspan="3" class="px-6 py-4 text-center text-red-500">
                                Erro ao buscar polos: ${data.message}
                            </td>
                        </tr>
                    `;

                    // Tenta carregar polos de forma alternativa
                    carregarPolosFallback(termo);
                }
            })
            .catch(error => {
                console.error('Erro ao buscar polos:', error);
                document.getElementById('resultados-busca-polos').innerHTML = `
                    <tr>
                        <td colspan="3" class="px-6 py-4 text-center text-red-500">
                            Erro ao buscar polos. Tentando método alternativo...
                        </td>
                    </tr>
                `;

                // Tenta carregar polos de forma alternativa
                carregarPolosFallback(termo);
            });
    }

    // Função para carregar polos de forma alternativa em caso de falha
    function carregarPolosFallback(termo) {
        console.log('Tentando carregar polos via fallback');

        // Simula uma busca local com dados mínimos
        setTimeout(() => {
            // Verifica se já existem polos no select de polos
            const polosSelect = document.querySelectorAll('select[name="polo_id"] option');
            const polos = [];

            if (polosSelect.length > 1) {
                // Extrai os polos do select
                polosSelect.forEach(option => {
                    if (option.value) {
                        const nome = option.textContent.trim();
                        // Filtra pelo termo de busca se houver
                        if (!termo || nome.toLowerCase().includes(termo.toLowerCase())) {
                            polos.push({
                                id: option.value,
                                nome: nome,
                                cidade: '',
                                estado: ''
                            });
                        }
                    }
                });
            } else {
                // Adiciona alguns polos de exemplo
                polos.push(
                    { id: '1', nome: 'Polo Central', cidade: 'São Paulo', estado: 'SP' },
                    { id: '2', nome: 'Polo Norte', cidade: 'Manaus', estado: 'AM' },
                    { id: '3', nome: 'Polo Sul', cidade: 'Porto Alegre', estado: 'RS' },
                    { id: '4', nome: 'Polo Leste', cidade: 'Salvador', estado: 'BA' },
                    { id: '5', nome: 'Polo Oeste', cidade: 'Cuiabá', estado: 'MT' }
                );

                // Filtra pelo termo de busca se houver
                if (termo) {
                    const termoLower = termo.toLowerCase();
                    const polosFiltrados = polos.filter(polo =>
                        polo.nome.toLowerCase().includes(termoLower) ||
                        polo.cidade.toLowerCase().includes(termoLower)
                    );
                    polos = polosFiltrados;
                }
            }

            // Atualiza a paginação
            paginaAtual = 1;
            totalPaginas = 1;

            // Atualiza os botões de paginação
            document.getElementById('btn-pagina-anterior').disabled = true;
            document.getElementById('btn-pagina-proxima').disabled = true;

            // Atualiza as informações de paginação
            document.getElementById('inicio-registros').textContent = polos.length > 0 ? 1 : 0;
            document.getElementById('fim-registros').textContent = polos.length;
            document.getElementById('total-registros').textContent = polos.length;

            // Renderiza os resultados
            renderizarResultadosPolos(polos);

            // Exibe mensagem de aviso
            if (polos.length > 0) {
                document.getElementById('resultados-busca-polos').insertAdjacentHTML('afterbegin', `
                    <tr>
                        <td colspan="3" class="px-6 py-4 text-center bg-yellow-50 text-yellow-700">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            Usando dados alternativos. A busca completa não está disponível.
                        </td>
                    </tr>
                `);
            } else {
                document.getElementById('resultados-busca-polos').innerHTML = `
                    <tr>
                        <td colspan="3" class="px-6 py-4 text-center">
                            Nenhum polo encontrado.
                        </td>
                    </tr>
                `;
            }
        }, 500);
    }

    // Função para renderizar os resultados da busca de polos
    function renderizarResultadosPolos(polos) {
        const tbody = document.getElementById('resultados-busca-polos');
        tbody.innerHTML = '';

        console.log('Renderizando polos:', polos);

        if (!polos || polos.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="3" class="px-6 py-4 text-center">
                        Nenhum polo encontrado.
                    </td>
                </tr>
            `;
            return;
        }

        polos.forEach(polo => {
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-gray-50 cursor-pointer';

            // Formata a localização
            let localizacao = 'Não informado';

            if (polo.cidade) {
                localizacao = polo.estado ? `${polo.cidade}/${polo.estado}` : polo.cidade;
            }

            // Escapa os valores para evitar problemas com HTML
            const poloId = polo.id || '';
            const poloNome = (polo.nome || '').replace(/"/g, '&quot;');

            tr.innerHTML = `
                <td class="px-6 py-4">
                    <input type="radio" name="polo_radio" id="polo_radio_${poloId}" value="${poloId}"
                        class="form-radio h-4 w-4 text-blue-600"
                        data-nome="${poloNome}"
                        ${poloSelecionado === poloId ? 'checked' : ''}>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    ${poloNome}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    ${localizacao}
                </td>
            `;

            tbody.appendChild(tr);

            // Adiciona evento de clique na linha inteira
            tr.addEventListener('click', function() {
                const radio = this.querySelector('input[type="radio"]');
                if (radio) {
                    // Desmarca todas as linhas
                    document.querySelectorAll('#resultados-busca-polos tr').forEach(row => {
                        row.classList.remove('bg-blue-50');
                    });

                    // Marca esta linha
                    this.classList.add('bg-blue-50');

                    radio.checked = true;
                    poloSelecionado = radio.value;
                    document.getElementById('btn-selecionar-polo').disabled = false;
                }
            });

            // Adiciona evento de duplo clique para selecionar e fechar
            tr.addEventListener('dblclick', function() {
                const radio = this.querySelector('input[type="radio"]');
                if (radio) {
                    // Desmarca todas as linhas
                    document.querySelectorAll('#resultados-busca-polos tr').forEach(row => {
                        row.classList.remove('bg-blue-50');
                    });

                    // Marca esta linha
                    this.classList.add('bg-blue-50');

                    radio.checked = true;
                    poloSelecionado = radio.value;
                    document.getElementById('btn-selecionar-polo').disabled = false;

                    // Seleciona o polo e fecha a modal
                    selecionarPoloEFechar();
                }
            });
        });

        // Adiciona evento de clique nos radios
        document.querySelectorAll('input[name="polo_radio"]').forEach(radio => {
            radio.addEventListener('change', function() {
                poloSelecionado = this.value;
                document.getElementById('btn-selecionar-polo').disabled = false;
            });
        });
    }

    // Função para buscar polos manualmente (chamada pelo botão)
    function buscarPolosManualmente() {
        console.log('Botão de busca clicado');
        paginaAtual = 1; // Reinicia a paginação
        buscarPolos();
    }

    // Função para limpar a busca (chamada pelo botão)
    function limparBusca() {
        console.log('Botão de limpar clicado');
        document.getElementById('busca-polo-termo').value = '';
        paginaAtual = 1; // Reinicia a paginação
        buscarPolos();
    }

    // Evento para buscar polos ao clicar no botão (backup)
    document.getElementById('btn-buscar-polo').addEventListener('click', function() {
        buscarPolosManualmente();
    });

    // Evento para limpar a busca (backup)
    document.getElementById('btn-limpar-busca').addEventListener('click', function() {
        limparBusca();
    });

    // Evento para buscar polos ao digitar no campo de busca (com debounce)
    document.getElementById('busca-polo-termo').addEventListener('input', function() {
        // Limpa o timeout anterior
        clearTimeout(timeoutBusca);

        // Define um novo timeout para buscar após 300ms de inatividade
        timeoutBusca = setTimeout(function() {
            paginaAtual = 1; // Reinicia a paginação
            buscarPolos();
        }, 300);
    });

    // Evento para buscar polos ao pressionar Enter no campo de busca
    document.getElementById('busca-polo-termo').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            clearTimeout(timeoutBusca); // Limpa o timeout para buscar imediatamente
            paginaAtual = 1; // Reinicia a paginação
            buscarPolos();
        }
    });

    // Eventos para paginação
    document.getElementById('btn-pagina-anterior').addEventListener('click', function() {
        if (paginaAtual > 1) {
            paginaAtual--;
            buscarPolos();
        }
    });

    document.getElementById('btn-pagina-proxima').addEventListener('click', function() {
        if (paginaAtual < totalPaginas) {
            paginaAtual++;
            buscarPolos();
        }
    });

    // Função para selecionar o polo e fechar a modal (chamada pelo botão)
    function selecionarPoloEFechar() {
        console.log('Botão de selecionar polo clicado');
        const radioSelecionado = document.querySelector('input[name="polo_radio"]:checked');

        if (radioSelecionado) {
            const poloId = radioSelecionado.value;
            const poloNome = radioSelecionado.getAttribute('data-nome');

            console.log('Polo selecionado:', poloId, poloNome);

            // Atualiza os campos do formulário
            document.getElementById('polo_id').value = poloId;
            document.getElementById('polo_nome').value = poloNome;
            document.getElementById('erro-polo').style.display = 'none';

            // Adiciona efeito visual para confirmar a seleção
            const poloInput = document.getElementById('polo_nome');
            poloInput.classList.remove('border-red-500');
            poloInput.classList.add('border-green-500');

            setTimeout(function() {
                poloInput.classList.remove('border-green-500');
            }, 2000);

            // Fecha o modal
            fecharModalBuscaPolos();
        } else {
            console.log('Nenhum polo selecionado');
            alert('Por favor, selecione um polo antes de continuar.');
        }
    }

    // Evento para selecionar o polo (backup)
    document.getElementById('btn-selecionar-polo').addEventListener('click', function() {
        selecionarPoloEFechar();
    });
</script>

<!-- Inclui o modal de busca de polos -->
<?php include 'views/cursos/busca_polos.php'; ?>
