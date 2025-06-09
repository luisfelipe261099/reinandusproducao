<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="p-6">
        <!-- Instruções Minimizáveis -->
        <div class="mb-6">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 cursor-pointer hover:bg-blue-100 transition-colors duration-200" onclick="toggleInstrucoes()">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0">
                            <i class="fas fa-info-circle text-blue-500 text-xl"></i>
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-gray-800">Instruções para Importação</h2>
                            <p class="text-sm text-gray-600">Formato da planilha, colunas obrigatórias e dicas importantes</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span id="instrucoes-status" class="text-sm text-blue-600 font-medium">Clique para ver</span>
                        <i id="instrucoes-icon" class="fas fa-chevron-down text-blue-500 transition-transform duration-200"></i>
                    </div>
                </div>
            </div>

            <div id="instrucoes-content" class="hidden mt-4 bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
                <div class="mb-4">
                    <h3 class="text-md font-semibold text-gray-800 mb-2">
                        <i class="fas fa-file-excel text-green-500 mr-2"></i>
                        Formato do Arquivo
                    </h3>
                    <p class="text-gray-600">
                        Faça o upload de um arquivo Excel (.xlsx, .xls) ou CSV (.csv) contendo os dados dos alunos a serem importados.
                        O arquivo deve conter as seguintes colunas na ordem abaixo:
                    </p>
                </div>
            <div class="bg-gray-50 p-4 rounded-lg mb-4">
                <ul class="list-disc list-inside text-sm text-gray-600">
                    <li><strong>Nome</strong> (obrigatório) - Nome completo do aluno</li>
                    <li><strong>CPF</strong> - CPF do aluno (formato: 000.000.000-00)</li>
                    <li><strong>RG</strong> - RG do aluno</li>
                    <li><strong>Orgão expedidor</strong> - Órgão expedidor do RG</li>
                    <li><strong>Nacionalidade</strong> - Nacionalidade do aluno</li>
                    <li><strong>Estado Civil</strong> - Estado civil do aluno (Solteiro(a), Casado(a), etc.)</li>
                    <li><strong>Sexo</strong> - Sexo do aluno (F ou M)</li>
                    <li><strong>Nascimento</strong> - Data de nascimento (formato: DD/MM/AAAA)</li>
                    <li><strong>Naturalidade</strong> - Cidade de nascimento</li>
                    <li><strong>Curso id</strong> - ID ou nome do curso</li>
                    <li><strong>Curso inicio</strong> - Data de início do curso (formato: DD/MM/AAAA)</li>
                    <li><strong>Curso fim</strong> - Data de término do curso (formato: DD/MM/AAAA)</li>
                    <li><strong>Situação</strong> - Situação do aluno (Ativo, Inativo, etc.)</li>
                    <li><strong>Email</strong> - Endereço de e-mail do aluno</li>
                    <li><strong>Endereço</strong> - Endereço completo</li>
                    <li><strong>Complemento</strong> - Complemento do endereço</li>
                    <li><strong>Cidade</strong> - Nome da cidade</li>
                    <li><strong>Cep</strong> - CEP do endereço</li>
                    <li><strong>Nome Social</strong> - Nome social do aluno (se aplicável)</li>
                    <li><strong>Celular</strong> - Número de celular do aluno</li>
                    <li><strong>Bairro</strong> - Bairro do endereço</li>
                    <li><strong>Data Ingresso</strong> - Data de ingresso na instituição (formato: DD/MM/AAAA)</li>
                    <li><strong>Previsão Conclusão</strong> - Data prevista para conclusão (formato: DD/MM/AAAA)</li>
                    <li><strong>Mono Título</strong> - Título da monografia</li>
                    <li><strong>Mono Data</strong> - Data da apresentação da monografia (formato: DD/MM/AAAA)</li>
                    <li><strong>Mono Nota</strong> - Nota da monografia (formato: 0.00)</li>
                    <li><strong>Mono Prazo</strong> - Prazo para entrega da monografia (formato: DD/MM/AAAA)</li>
                    <li><strong>Bolsa</strong> - Valor da bolsa (formato: 0.00)</li>
                    <li><strong>Desconto</strong> - Valor do desconto (formato: 0.00)</li>
                </ul>
            </div>
            <div class="flex items-center mb-4">
                <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                <p class="text-sm text-blue-600">
                    A primeira linha do arquivo será considerada como cabeçalho e será ignorada durante a importação.
                </p>
            </div>
            <div class="flex items-center mb-4">
                <i class="fas fa-exclamation-triangle text-yellow-500 mr-2"></i>
                <p class="text-sm text-yellow-600">
                    Se um aluno com o mesmo CPF já existir no sistema, seus dados serão atualizados.
                </p>
            </div>
            <div class="flex items-center mb-4">
                <i class="fas fa-info-circle text-green-500 mr-2"></i>
                <p class="text-sm text-green-600">
                    Utilize a opção "Apenas Validar" para verificar se sua planilha está correta antes de importar.
                    Isso não alterará o banco de dados, apenas mostrará um relatório de validação.
                </p>
            </div>
            <div class="flex items-center mb-4">
                <i class="fas fa-exclamation-circle text-red-500 mr-2"></i>
                <p class="text-sm text-red-600">
                    <strong>Importante:</strong> É recomendável selecionar um curso e uma turma para a importação.
                    Se não houver uma turma disponível para o curso selecionado, o sistema tentará criar uma turma padrão automaticamente.
                </p>
            </div>
            <div class="flex items-center mb-4">
                <i class="fas fa-graduation-cap text-blue-500 mr-2"></i>
                <p class="text-sm text-blue-600">
                    <strong>Matrículas:</strong> Ao importar alunos com curso e turma selecionados, o sistema criará automaticamente
                    matrículas para os alunos no curso e turma especificados. Para alunos já existentes, suas matrículas serão
                    atualizadas ou criadas se não existirem.
                </p>
            </div>

            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">Atenção aos formatos</h3>
                        <div class="mt-2 text-sm text-yellow-700">
                            <ul class="list-disc list-inside">
                                <li><strong>CPF:</strong> Pode ser informado com ou sem formatação (123.456.789-00 ou 12345678900)</li>
                                <li><strong>RG:</strong> Pode ser informado com ou sem formatação (12.345.678-9 ou 123456789)</li>
                                <li><strong>Datas:</strong> Podem estar no formato DD/MM/AAAA (31/01/1990) ou M/DD/YYYY (1/31/1990) como exportado pelo Excel</li>
                                <li><strong>Sexo:</strong> Use "F" para feminino ou "M" para masculino</li>
                                <li><strong>Estado Civil:</strong> Use "Solteiro(a)", "Casado(a)", "Divorciado(a)" ou "Viúvo(a)"</li>
                                <li><strong>Estado:</strong> Deve ser a sigla do estado com 2 letras (ex: SP, RJ, MG)</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-info-circle text-blue-400"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">Modelo de planilha</h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <p>Você pode criar uma planilha com as seguintes colunas:</p>
                            <pre class="mt-2 bg-gray-100 p-2 rounded text-xs overflow-x-auto">
Nome | CPF | RG | Orgão expedidor | Nacionalidade | Estado Civil | Sexo | Nascimento | Naturalidade | Curso id | Curso inicio | Curso fim | Situação | Email | Endereço | Complemento | Cidade | Cep | Nome Social | Celular | Bairro | Data Ingresso | Previsão Conclusão | Mono Título | Mono Data | Mono Nota | Mono Prazo | Bolsa | Desconto
João da Silva | 123.456.789-00 | 12.345.678-9 | SSP/SP | Brasileira | Solteiro(a) | M | 01/01/1990 | São Paulo | 1 | 01/01/2023 | 31/12/2023 | Ativo | joao.silva@email.com | Rua das Flores, 123 | Apto 101 | São Paulo | 01234-567 | | (11) 98765-4321 | Centro | 01/01/2023 | 31/12/2024 | | | | | |
Maria Oliveira | 987.654.321-00 | 98.765.432-1 | SSP/RJ | Brasileira | Casado(a) | F | 15/05/1985 | Rio de Janeiro | 2 | 01/02/2023 | 28/02/2024 | Ativo | maria.oliveira@email.com | Av. Principal, 456 | Bloco B | Rio de Janeiro | 20000-000 | | (21) 98765-4321 | Copacabana | 01/02/2023 | 28/02/2025 | Estudo sobre Educação | 15/12/2024 | 9.5 | 01/12/2024 | 500.00 | 100.00
                            </pre>
                        </div>
                    </div>
                </div>
            </div>
                <div class="flex justify-center mb-4">
                    <a href="templates/modelo_importacao_alunos.xlsx" download class="btn-secondary">
                        <i class="fas fa-download mr-2"></i> Baixar Modelo de Planilha
                    </a>
                </div>
            </div>
        </div>

        <form action="alunos.php?action=processar_importacao" method="post" enctype="multipart/form-data" class="space-y-6">
            <!-- Upload de Arquivo -->
            <div>
                <label for="arquivo" class="block text-sm font-medium text-gray-700 mb-1">Arquivo de Importação *</label>
                <div id="drop-area" class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                    <div class="space-y-1 text-center">
                        <i class="fas fa-file-upload text-gray-400 text-3xl mb-2"></i>
                        <div class="flex text-sm text-gray-600">
                            <label for="arquivo" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none">
                                <span>Selecione um arquivo</span>
                                <input id="arquivo" name="arquivo" type="file" class="sr-only" accept=".xlsx,.xls,.csv" required>
                            </label>
                            <p class="pl-1">ou arraste e solte aqui</p>
                        </div>
                        <p class="text-xs text-gray-500">
                            Excel (.xlsx, .xls) ou CSV (.csv) até 5MB
                        </p>
                    </div>
                </div>
                <div id="arquivo-selecionado" class="mt-2 text-sm text-gray-500 hidden">
                    Arquivo selecionado: <span id="nome-arquivo" class="font-medium"></span>
                </div>
            </div>

            <!-- Informações Adicionais -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Polo -->
                <div>
                    <label for="polo_id" class="block text-sm font-medium text-gray-700 mb-1">Polo</label>
                    <select name="polo_id" id="polo_id" class="form-select w-full">
                        <option value="">Selecione um polo...</option>
                        <?php foreach ($polos as $polo): ?>
                        <option value="<?php echo $polo['id']; ?>">
                            <?php echo htmlspecialchars($polo['nome']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="mt-1 text-xs text-gray-500">
                        Todos os alunos importados serão associados a este polo.
                    </p>
                </div>

                <!-- Curso -->
                <div>
                    <label for="curso_id" class="block text-sm font-medium text-gray-700 mb-1">Curso</label>
                    <select name="curso_id" id="curso_id" class="form-select w-full">
                        <option value="">Selecione um curso...</option>
                        <?php foreach ($cursos as $curso): ?>
                        <option value="<?php echo $curso['id']; ?>">
                            <?php echo htmlspecialchars($curso['nome']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="mt-1 text-xs text-gray-500">
                        Todos os alunos importados serão associados a este curso.
                    </p>
                </div>

                <!-- Turma -->
                <div>
                    <label for="turma_id" class="block text-sm font-medium text-gray-700 mb-1">Turma</label>
                    <select name="turma_id" id="turma_id" class="form-select w-full">
                        <option value="">Selecione uma turma...</option>
                        <?php foreach ($turmas as $turma): ?>
                        <option value="<?php echo $turma['id']; ?>" data-curso="<?php echo $turma['curso_id']; ?>">
                            <?php echo htmlspecialchars($turma['nome']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="mt-1 text-xs text-gray-500">
                        Todos os alunos importados serão associados a esta turma e terão matrículas criadas automaticamente.
                        Se não selecionar uma turma e o curso tiver uma turma ativa, ela será usada automaticamente.
                    </p>
                </div>
            </div>

            <!-- Opções de Importação -->
            <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                <h3 class="text-sm font-medium text-gray-700 mb-3">Opções de Importação</h3>

                <div class="space-y-3">
                    <!-- Opção para atualizar alunos existentes -->
                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input id="atualizar_existentes" name="atualizar_existentes" type="checkbox" value="1" checked class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="atualizar_existentes" class="font-medium text-gray-700">Atualizar alunos existentes</label>
                            <p class="text-gray-500">Se marcado, o sistema atualizará os dados de alunos que já existem no sistema (identificados pelo CPF ou email). Se desmarcado, apenas novos alunos serão inseridos.</p>
                        </div>
                    </div>

                    <!-- Opção para identificar por email -->
                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input id="identificar_por_email" name="identificar_por_email" type="checkbox" value="1" checked class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="identificar_por_email" class="font-medium text-gray-700">Identificar alunos também pelo email</label>
                            <p class="text-gray-500">Se marcado, o sistema tentará identificar alunos existentes pelo email caso não encontre pelo CPF. Isso é útil quando o CPF não está disponível ou é diferente.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end space-x-3">
                <a href="alunos.php" class="btn-secondary">Cancelar</a>
                <button type="submit" formaction="alunos.php?action=validar_importacao" class="btn-secondary">
                    <i class="fas fa-check-circle mr-2"></i> Apenas Validar
                </button>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-file-import mr-2"></i> Importar Alunos
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Função para alternar a exibição das instruções
function toggleInstrucoes() {
    const content = document.getElementById('instrucoes-content');
    const icon = document.getElementById('instrucoes-icon');
    const status = document.getElementById('instrucoes-status');

    if (content.classList.contains('hidden')) {
        // Expandir
        content.classList.remove('hidden');
        icon.classList.remove('fa-chevron-down');
        icon.classList.add('fa-chevron-up');
        icon.style.transform = 'rotate(180deg)';
        status.textContent = 'Clique para ocultar';

        // Adiciona animação suave
        content.style.opacity = '0';
        content.style.transform = 'translateY(-10px)';
        setTimeout(() => {
            content.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            content.style.opacity = '1';
            content.style.transform = 'translateY(0)';
        }, 10);
    } else {
        // Minimizar
        content.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
        content.style.opacity = '0';
        content.style.transform = 'translateY(-10px)';

        setTimeout(() => {
            content.classList.add('hidden');
            icon.classList.remove('fa-chevron-up');
            icon.classList.add('fa-chevron-down');
            icon.style.transform = 'rotate(0deg)';
            status.textContent = 'Clique para ver';
        }, 300);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Elementos do DOM
    const inputArquivo = document.getElementById('arquivo');
    const divArquivoSelecionado = document.getElementById('arquivo-selecionado');
    const spanNomeArquivo = document.getElementById('nome-arquivo');
    const dropArea = document.getElementById('drop-area');

    // Função para validar o arquivo
    function validarArquivo(arquivo) {
        // Verifica o tamanho do arquivo (máx. 5MB)
        if (arquivo.size > 5 * 1024 * 1024) {
            alert('O arquivo é muito grande. O tamanho máximo permitido é 5MB.');
            inputArquivo.value = '';
            divArquivoSelecionado.classList.add('hidden');
            return false;
        }

        // Verifica a extensão do arquivo
        const extensao = arquivo.name.split('.').pop().toLowerCase();
        if (!['xlsx', 'xls', 'csv'].includes(extensao)) {
            alert('Formato de arquivo inválido. Apenas arquivos Excel (.xlsx, .xls) ou CSV (.csv) são permitidos.');
            inputArquivo.value = '';
            divArquivoSelecionado.classList.add('hidden');
            return false;
        }

        return true;
    }

    // Função para exibir o arquivo selecionado
    function exibirArquivoSelecionado(arquivo) {
        if (validarArquivo(arquivo)) {
            spanNomeArquivo.textContent = arquivo.name;
            divArquivoSelecionado.classList.remove('hidden');

            // Adiciona classe de destaque ao drop area
            dropArea.classList.add('border-blue-500');
            dropArea.classList.remove('border-gray-300');
        }
    }

    // Evento de mudança no input de arquivo
    inputArquivo.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            exibirArquivoSelecionado(this.files[0]);
        } else {
            divArquivoSelecionado.classList.add('hidden');
            dropArea.classList.remove('border-blue-500');
            dropArea.classList.add('border-gray-300');
        }
    });

    // Eventos de drag and drop
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, function(e) {
            e.preventDefault();
            e.stopPropagation();
        }, false);
    });

    ['dragenter', 'dragover'].forEach(eventName => {
        dropArea.addEventListener(eventName, function() {
            dropArea.classList.add('border-blue-500');
            dropArea.classList.remove('border-gray-300');
            dropArea.classList.add('bg-blue-50');
        }, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, function() {
            dropArea.classList.remove('border-blue-500');
            dropArea.classList.add('border-gray-300');
            dropArea.classList.remove('bg-blue-50');
        }, false);
    });

    dropArea.addEventListener('drop', function(e) {
        const dt = e.dataTransfer;
        const files = dt.files;

        if (files.length > 0) {
            inputArquivo.files = files;
            exibirArquivoSelecionado(files[0]);
        }
    }, false);

    // Filtra as turmas de acordo com o curso selecionado
    const selectCurso = document.getElementById('curso_id');
    const selectTurma = document.getElementById('turma_id');

    selectCurso.addEventListener('change', function() {
        const cursoId = this.value;

        // Limpa a seleção atual
        selectTurma.value = '';

        // Mostra/esconde as opções de turma de acordo com o curso
        Array.from(selectTurma.options).forEach(function(option) {
            if (option.value === '') {
                // Mantém a opção "Selecione uma turma..." sempre visível
                option.style.display = '';
            } else {
                const cursoDaTurma = option.getAttribute('data-curso');
                if (!cursoId || cursoDaTurma === cursoId) {
                    option.style.display = '';
                } else {
                    option.style.display = 'none';
                }
            }
        });
    });
});
</script>
