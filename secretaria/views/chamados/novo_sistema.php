<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Novo Chamado</h1>
        <a href="index.php" class="bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded">
            <i class="fas fa-arrow-left mr-2"></i> Voltar
        </a>
    </div>

    <div class="bg-white shadow-md rounded-lg p-6">
        <form action="processar.php" method="POST" class="space-y-6">
            <input type="hidden" name="acao" value="criar">

            <!-- Tipo de Documento -->
            <div>
                <label for="subtipo" class="block text-sm font-medium text-gray-700 mb-1">Tipo de Documento <span class="text-red-500">*</span></label>
                <select id="subtipo" name="subtipo" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    <option value="">Selecione o tipo de documento</option>
                    <option value="declaracao">Declaração</option>
                    <option value="historico">Histórico</option>
                    <option value="certificado">Certificado</option>
                    <option value="diploma">Diploma</option>
                </select>
            </div>

            <!-- Polo (apenas para usuários que não são do tipo polo) -->
            <?php if ($tipo_usuario != 'polo'): ?>
            <div>
                <label for="polo_id" class="block text-sm font-medium text-gray-700 mb-1">Polo</label>
                <select id="polo_id" name="polo_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    <option value="">Todos os polos</option>
                    <?php foreach ($polos as $polo): ?>
                    <option value="<?php echo $polo['id']; ?>">
                        <?php echo htmlspecialchars($polo['nome']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php else: ?>
            <input type="hidden" name="polo_id" value="<?php echo $polo_id; ?>">
            <?php endif; ?>

            <!-- Seleção de Alunos ou Turma -->
            <div class="space-y-4">
                <h3 class="text-lg font-medium text-gray-900">Selecione os Alunos ou uma Turma</h3>

                <div class="flex space-x-4">
                    <div class="flex items-center">
                        <input type="radio" id="opcao_alunos" name="opcao_selecao" value="alunos" checked class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                        <label for="opcao_alunos" class="ml-2 block text-sm text-gray-900">Buscar Alunos</label>
                    </div>
                    <div class="flex items-center">
                        <input type="radio" id="opcao_turma" name="opcao_selecao" value="turma" class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                        <label for="opcao_turma" class="ml-2 block text-sm text-gray-900">Selecionar Turma</label>
                    </div>
                    <div class="flex items-center">
                        <input type="radio" id="opcao_manual" name="opcao_selecao" value="manual" class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                        <label for="opcao_manual" class="ml-2 block text-sm text-gray-900">Entrada Manual</label>
                    </div>
                    <div class="flex items-center">
                        <input type="radio" id="opcao_planilha" name="opcao_selecao" value="planilha" class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                        <label for="opcao_planilha" class="ml-2 block text-sm text-gray-900">Importar Planilha</label>
                    </div>
                </div>

                <!-- Busca de Alunos -->
                <div id="selecao_alunos" class="space-y-4">
                    <div class="flex space-x-2">
                        <input type="text" id="busca_aluno" placeholder="Digite o nome, matrícula ou CPF do aluno" class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                        <button type="button" id="btn_buscar_aluno" class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                    </div>

                    <div id="resultados_alunos" class="border border-gray-300 rounded-md p-4 max-h-60 overflow-y-auto hidden">
                        <div class="text-center text-gray-500 py-4">
                            Nenhum aluno encontrado.
                        </div>
                    </div>

                    <div id="alunos_selecionados" class="border border-gray-300 rounded-md p-4 max-h-60 overflow-y-auto">
                        <div class="text-center text-gray-500 py-4">
                            Nenhum aluno selecionado.
                        </div>
                    </div>
                </div>

                <!-- Seleção de Turma -->
                <div id="selecao_turma" class="space-y-4 hidden">
                    <div class="flex space-x-2">
                        <input type="text" id="busca_turma" placeholder="Digite o nome da turma ou curso" class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                        <button type="button" id="btn_buscar_turma" class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                    </div>

                    <div id="resultados_turmas" class="border border-gray-300 rounded-md p-4 max-h-60 overflow-y-auto hidden">
                        <div class="text-center text-gray-500 py-4">
                            Nenhuma turma encontrada.
                        </div>
                    </div>

                    <div id="turma_selecionada" class="border border-gray-300 rounded-md p-4 hidden">
                        <div class="text-center text-gray-500 py-4">
                            Nenhuma turma selecionada.
                        </div>
                    </div>
                </div>

                <!-- Entrada Manual de Alunos -->
                <div id="entrada_manual" class="space-y-4 hidden">
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700">
                                    Digite os nomes dos alunos, um por linha. Se possível, inclua a matrícula ou CPF para melhor identificação.
                                </p>
                            </div>
                        </div>
                    </div>

                    <textarea id="alunos_manual" name="alunos_manual" rows="10" placeholder="Nome do Aluno - Matrícula/CPF (opcional)&#10;Ex: João da Silva - 123456&#10;Maria Oliveira - 789.456.123-00" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50"></textarea>

                    <div class="text-right">
                        <span class="text-sm text-gray-500" id="contador_alunos">0 alunos</span>
                    </div>
                </div>

                <!-- Importação de Planilha -->
                <div id="importacao_planilha" class="space-y-4 hidden">
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700">
                                    Importe uma planilha Excel ou CSV com os dados dos alunos. A planilha deve conter pelo menos uma coluna com o nome do aluno.
                                </p>
                                <p class="text-sm text-yellow-700 mt-2">
                                    <a href="#" class="font-medium underline">Baixar modelo de planilha</a>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-center w-full">
                        <label for="arquivo_planilha" class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100">
                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                <i class="fas fa-cloud-upload-alt text-gray-400 text-3xl mb-3"></i>
                                <p class="mb-2 text-sm text-gray-500"><span class="font-semibold">Clique para selecionar</span> ou arraste e solte</p>
                                <p class="text-xs text-gray-500">Excel ou CSV (máx. 5MB)</p>
                            </div>
                            <input id="arquivo_planilha" name="arquivo_planilha" type="file" class="hidden" accept=".xlsx,.xls,.csv" />
                        </label>
                    </div>

                    <div id="preview_planilha" class="hidden">
                        <div class="flex items-center justify-between bg-gray-100 p-2 rounded-t-md">
                            <span class="text-sm font-medium text-gray-700" id="nome_arquivo">arquivo.xlsx</span>
                            <button type="button" id="remover_arquivo" class="text-red-600 hover:text-red-800">
                                <i class="fas fa-times"></i> Remover
                            </button>
                        </div>
                        <div class="border border-gray-300 rounded-b-md p-4 max-h-60 overflow-y-auto">
                            <div class="text-center text-gray-500 py-4" id="info_arquivo">
                                Processando arquivo...
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Observações -->
            <div>
                <label for="observacoes" class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                <textarea id="observacoes" name="observacoes" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50"></textarea>
            </div>

            <div class="flex justify-end space-x-3">
                <a href="index.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-medium py-2 px-4 rounded">
                    Cancelar
                </a>
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded">
                    <i class="fas fa-save mr-2"></i> Abrir Chamado
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Elementos do DOM
    const opcaoAlunos = document.getElementById('opcao_alunos');
    const opcaoTurma = document.getElementById('opcao_turma');
    const opcaoManual = document.getElementById('opcao_manual');
    const opcaoPlanilha = document.getElementById('opcao_planilha');
    const selecaoAlunos = document.getElementById('selecao_alunos');
    const selecaoTurma = document.getElementById('selecao_turma');
    const entradaManual = document.getElementById('entrada_manual');
    const importacaoPlanilha = document.getElementById('importacao_planilha');
    const poloSelect = document.getElementById('polo_id');
    const btnBuscarAluno = document.getElementById('btn_buscar_aluno');
    const btnBuscarTurma = document.getElementById('btn_buscar_turma');
    const buscaAluno = document.getElementById('busca_aluno');
    const buscaTurma = document.getElementById('busca_turma');
    const resultadosAlunos = document.getElementById('resultados_alunos');
    const resultadosTurmas = document.getElementById('resultados_turmas');
    const alunosSelecionados = document.getElementById('alunos_selecionados');
    const turmaSelecionada = document.getElementById('turma_selecionada');
    const alunosManual = document.getElementById('alunos_manual');
    const contadorAlunos = document.getElementById('contador_alunos');
    const arquivoPlanilha = document.getElementById('arquivo_planilha');
    const previewPlanilha = document.getElementById('preview_planilha');
    const nomeArquivo = document.getElementById('nome_arquivo');
    const infoArquivo = document.getElementById('info_arquivo');
    const removerArquivo = document.getElementById('remover_arquivo');

    // Variáveis para armazenar os alunos e turma selecionados
    let alunos = [];
    let turma = null;

    // Função para alternar entre as opções de seleção
    function toggleSelecao() {
        // Esconde todas as opções
        selecaoAlunos.classList.add('hidden');
        selecaoTurma.classList.add('hidden');
        entradaManual.classList.add('hidden');
        importacaoPlanilha.classList.add('hidden');

        // Mostra a opção selecionada
        if (opcaoAlunos.checked) {
            selecaoAlunos.classList.remove('hidden');
        } else if (opcaoTurma.checked) {
            selecaoTurma.classList.remove('hidden');
        } else if (opcaoManual.checked) {
            entradaManual.classList.remove('hidden');
        } else if (opcaoPlanilha.checked) {
            importacaoPlanilha.classList.remove('hidden');
        }
    }

    // Adiciona event listeners para os radio buttons
    opcaoAlunos.addEventListener('change', toggleSelecao);
    opcaoTurma.addEventListener('change', toggleSelecao);
    opcaoManual.addEventListener('change', toggleSelecao);
    opcaoPlanilha.addEventListener('change', toggleSelecao);

    // Função para obter o ID do polo selecionado
    function getPoloId() {
        <?php if ($tipo_usuario == 'polo'): ?>
        return <?php echo $polo_id; ?>;
        <?php else: ?>
        return poloSelect ? poloSelect.value : '';
        <?php endif; ?>
    }

    // Função para buscar alunos
    function buscarAlunos() {
        const poloId = getPoloId();
        const termo = buscaAluno.value.trim();

        if (termo.length < 3) {
            alert('Digite pelo menos 3 caracteres para buscar.');
            return;
        }

        // Faz a requisição AJAX
        let url = `buscar_alunos.php?termo=${encodeURIComponent(termo)}`;
        if (poloId) {
            url += `&polo_id=${poloId}`;
        }

        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.erro) {
                    alert(data.erro);
                    return;
                }

                // Exibe os resultados
                if (data.alunos && data.alunos.length > 0) {
                    let html = '<ul class="divide-y divide-gray-200">';

                    data.alunos.forEach(aluno => {
                        // Verifica se o aluno já está selecionado
                        const jaSelecionado = alunos.some(a => a.id === aluno.id);

                        html += `
                        <li class="py-2 flex justify-between items-center">
                            <div>
                                <p class="text-sm font-medium text-gray-900">${aluno.nome}</p>
                                <p class="text-xs text-gray-500">Matrícula: ${aluno.matricula} | CPF: ${aluno.cpf}</p>
                                <p class="text-xs text-gray-500">${aluno.curso_nome ? aluno.curso_nome : 'Sem curso'} - ${aluno.turma_nome ? aluno.turma_nome : 'Sem turma'}</p>
                            </div>
                            <button type="button" class="text-blue-600 hover:text-blue-900 text-sm font-medium ${jaSelecionado ? 'hidden' : ''}"
                                    data-id="${aluno.id}"
                                    data-nome="${aluno.nome}"
                                    data-matricula="${aluno.matricula}"
                                    data-cpf="${aluno.cpf}"
                                    data-curso="${aluno.curso_nome || ''}"
                                    data-turma="${aluno.turma_nome || ''}"
                                    onclick="selecionarAluno(this)">
                                <i class="fas fa-plus-circle"></i> Adicionar
                            </button>
                        </li>`;
                    });

                    html += '</ul>';
                    resultadosAlunos.innerHTML = html;
                } else {
                    resultadosAlunos.innerHTML = '<div class="text-center text-gray-500 py-4">Nenhum aluno encontrado.</div>';
                }

                resultadosAlunos.classList.remove('hidden');
            })
            .catch(error => {
                console.error('Erro ao buscar alunos:', error);
                alert('Erro ao buscar alunos. Tente novamente.');
            });
    }

    // Função para buscar turmas
    function buscarTurmas() {
        const poloId = getPoloId();
        const termo = buscaTurma.value.trim();

        // Para turmas, ainda precisamos de um polo
        if (!poloId) {
            alert('Selecione um polo para buscar turmas.');
            return;
        }

        // Faz a requisição AJAX
        fetch(`buscar_turmas.php?polo_id=${poloId}&termo=${encodeURIComponent(termo)}`)
            .then(response => response.json())
            .then(data => {
                if (data.erro) {
                    alert(data.erro);
                    return;
                }

                // Exibe os resultados
                if (data.turmas && data.turmas.length > 0) {
                    let html = '<ul class="divide-y divide-gray-200">';

                    data.turmas.forEach(turma => {
                        html += `
                        <li class="py-2 flex justify-between items-center">
                            <div>
                                <p class="text-sm font-medium text-gray-900">${turma.nome}</p>
                                <p class="text-xs text-gray-500">Curso: ${turma.curso_nome} | Turno: ${turma.turno}</p>
                                <p class="text-xs text-gray-500">Total de alunos: ${turma.total_alunos}</p>
                            </div>
                            <button type="button" class="text-blue-600 hover:text-blue-900 text-sm font-medium"
                                    data-id="${turma.id}"
                                    data-nome="${turma.nome}"
                                    data-curso="${turma.curso_nome}"
                                    data-turno="${turma.turno}"
                                    data-total="${turma.total_alunos}"
                                    onclick="selecionarTurma(this)">
                                <i class="fas fa-check-circle"></i> Selecionar
                            </button>
                        </li>`;
                    });

                    html += '</ul>';
                    resultadosTurmas.innerHTML = html;
                } else {
                    resultadosTurmas.innerHTML = '<div class="text-center text-gray-500 py-4">Nenhuma turma encontrada.</div>';
                }

                resultadosTurmas.classList.remove('hidden');
            })
            .catch(error => {
                console.error('Erro ao buscar turmas:', error);
                alert('Erro ao buscar turmas. Tente novamente.');
            });
    }

    // Adiciona event listeners para os botões de busca
    btnBuscarAluno.addEventListener('click', buscarAlunos);
    btnBuscarTurma.addEventListener('click', buscarTurmas);

    // Permite buscar ao pressionar Enter
    buscaAluno.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            buscarAlunos();
        }
    });

    buscaTurma.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            buscarTurmas();
        }
    });

    // Função para selecionar um aluno
    window.selecionarAluno = function(button) {
        const id = button.getAttribute('data-id');
        const nome = button.getAttribute('data-nome');
        const matricula = button.getAttribute('data-matricula');
        const cpf = button.getAttribute('data-cpf');
        const curso = button.getAttribute('data-curso');
        const turma = button.getAttribute('data-turma');

        // Adiciona o aluno à lista de selecionados
        alunos.push({
            id: id,
            nome: nome,
            matricula: matricula,
            cpf: cpf,
            curso: curso,
            turma: turma
        });

        // Atualiza a exibição dos alunos selecionados
        atualizarAlunosSelecionados();

        // Esconde o botão de adicionar
        button.classList.add('hidden');
    };

    // Função para remover um aluno da seleção
    window.removerAluno = function(id) {
        alunos = alunos.filter(aluno => aluno.id !== id);
        atualizarAlunosSelecionados();

        // Mostra novamente o botão de adicionar nos resultados da busca
        const button = resultadosAlunos.querySelector(`button[data-id="${id}"]`);
        if (button) {
            button.classList.remove('hidden');
        }
    };

    // Função para atualizar a exibição dos alunos selecionados
    function atualizarAlunosSelecionados() {
        if (alunos.length === 0) {
            alunosSelecionados.innerHTML = '<div class="text-center text-gray-500 py-4">Nenhum aluno selecionado.</div>';
            return;
        }

        let html = '<ul class="divide-y divide-gray-200">';

        alunos.forEach(aluno => {
            html += `
            <li class="py-2 flex justify-between items-center">
                <div>
                    <p class="text-sm font-medium text-gray-900">${aluno.nome}</p>
                    <p class="text-xs text-gray-500">Matrícula: ${aluno.matricula} | CPF: ${aluno.cpf}</p>
                    <p class="text-xs text-gray-500">${aluno.curso ? aluno.curso : 'Sem curso'} - ${aluno.turma ? aluno.turma : 'Sem turma'}</p>
                </div>
                <div>
                    <button type="button" class="text-red-600 hover:text-red-900 text-sm font-medium" onclick="removerAluno('${aluno.id}')">
                        <i class="fas fa-times-circle"></i> Remover
                    </button>
                    <input type="hidden" name="alunos[]" value="${aluno.id}">
                </div>
            </li>`;
        });

        html += '</ul>';
        alunosSelecionados.innerHTML = html;
    }

    // Função para selecionar uma turma
    window.selecionarTurma = function(button) {
        const id = button.getAttribute('data-id');
        const nome = button.getAttribute('data-nome');
        const curso = button.getAttribute('data-curso');
        const turno = button.getAttribute('data-turno');
        const total = button.getAttribute('data-total');

        // Define a turma selecionada
        turma = {
            id: id,
            nome: nome,
            curso: curso,
            turno: turno,
            total: total
        };

        // Atualiza a exibição da turma selecionada
        turmaSelecionada.innerHTML = `
        <div class="flex justify-between items-center">
            <div>
                <p class="text-sm font-medium text-gray-900">${nome}</p>
                <p class="text-xs text-gray-500">Curso: ${curso} | Turno: ${turno}</p>
                <p class="text-xs text-gray-500">Total de alunos: ${total}</p>
            </div>
            <div>
                <button type="button" class="text-red-600 hover:text-red-900 text-sm font-medium" onclick="removerTurma()">
                    <i class="fas fa-times-circle"></i> Remover
                </button>
                <input type="hidden" name="turma_id" value="${id}">
            </div>
        </div>`;

        turmaSelecionada.classList.remove('hidden');
        resultadosTurmas.classList.add('hidden');
    };

    // Função para remover a turma selecionada
    window.removerTurma = function() {
        turma = null;
        turmaSelecionada.innerHTML = '<div class="text-center text-gray-500 py-4">Nenhuma turma selecionada.</div>';
        turmaSelecionada.classList.add('hidden');
    };

    // Contador de linhas para entrada manual
    alunosManual.addEventListener('input', function() {
        const linhas = this.value.split('\n').filter(linha => linha.trim() !== '').length;
        contadorAlunos.textContent = linhas + (linhas === 1 ? ' aluno' : ' alunos');
    });

    // Manipulação do upload de planilha
    arquivoPlanilha.addEventListener('change', function(e) {
        const arquivo = e.target.files[0];
        if (!arquivo) return;

        // Verifica o tamanho do arquivo (máx. 5MB)
        if (arquivo.size > 5 * 1024 * 1024) {
            alert('O arquivo é muito grande. O tamanho máximo permitido é 5MB.');
            this.value = '';
            return;
        }

        // Verifica a extensão do arquivo
        const extensao = arquivo.name.split('.').pop().toLowerCase();
        if (!['xlsx', 'xls', 'csv'].includes(extensao)) {
            alert('Formato de arquivo inválido. Apenas arquivos Excel (.xlsx, .xls) ou CSV (.csv) são permitidos.');
            this.value = '';
            return;
        }

        // Exibe o preview do arquivo
        nomeArquivo.textContent = arquivo.name;
        infoArquivo.textContent = `Arquivo selecionado: ${arquivo.name} (${formatFileSize(arquivo.size)})`;
        previewPlanilha.classList.remove('hidden');
    });

    // Remover arquivo
    removerArquivo.addEventListener('click', function() {
        arquivoPlanilha.value = '';
        previewPlanilha.classList.add('hidden');
    });

    // Função para formatar o tamanho do arquivo
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    // Adiciona event listener para o formulário
    document.querySelector('form').addEventListener('submit', function(e) {
        const poloId = getPoloId();

        if (!poloId) {
            e.preventDefault();
            alert('Selecione um polo.');
            return;
        }

        if (opcaoAlunos.checked && alunos.length === 0) {
            e.preventDefault();
            alert('Selecione pelo menos um aluno.');
            return;
        }

        if (opcaoTurma.checked && !turma) {
            e.preventDefault();
            alert('Selecione uma turma.');
            return;
        }

        if (opcaoManual.checked && alunosManual.value.trim() === '') {
            e.preventDefault();
            alert('Digite os nomes dos alunos.');
            return;
        }

        if (opcaoPlanilha.checked && !arquivoPlanilha.files[0]) {
            e.preventDefault();
            alert('Selecione um arquivo de planilha.');
            return;
        }
    });

    // Se o usuário for do tipo polo, carrega os alunos automaticamente
    <?php if ($tipo_usuario == 'polo'): ?>
    // Busca alunos ao carregar a página
    setTimeout(function() {
        buscarAlunos();
    }, 500);
    <?php endif; ?>
});
</script>
