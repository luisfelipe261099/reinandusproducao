<?php
/**
 * View para selecionar um aluno para emissão de documentos
 */
?>

<!-- Botões de navegação no topo -->
<div class="mb-4 flex justify-between items-center">
    <a href="documentos.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
        <i class="fas fa-arrow-left mr-2"></i> Voltar
    </a>
    <div class="flex space-x-2">
        <a href="documentos.php?action=selecionar_aluno" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
            <i class="fas fa-sync-alt mr-2"></i> Atualizar
        </a>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="p-6 border-b border-gray-200">
        <h3 class="text-xl font-semibold text-gray-800">Selecionar Aluno</h3>
    </div>
    <div class="p-6">
        <!-- Formulário de busca -->
        <form action="documentos.php" method="get" class="mb-6">
            <input type="hidden" name="action" value="selecionar_aluno">
            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 mb-4">
                <h4 class="font-semibold text-gray-700 mb-3">Filtros de Busca</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="busca" class="block text-sm font-medium text-gray-700 mb-1">Buscar por nome ou CPF:</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                            <input type="text" id="busca" name="busca" class="w-full pl-10 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Digite o nome ou CPF do aluno" value="<?php echo $_GET['busca'] ?? ''; ?>">
                        </div>
                    </div>
                    <div>
                        <label for="turma_id" class="block text-sm font-medium text-gray-700 mb-1">Filtrar por turma:</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-users text-gray-400"></i>
                            </div>
                            <select id="turma_id" name="turma_id" class="w-full pl-10 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Todas as turmas</option>
                                <?php if (isset($turmas) && count($turmas) > 0): ?>
                                    <?php foreach ($turmas as $turma): ?>
                                        <option value="<?php echo $turma['id']; ?>" <?php echo (isset($_GET['turma_id']) && $_GET['turma_id'] == $turma['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($turma['nome']); ?> - <?php echo htmlspecialchars($turma['curso_nome'] ?? 'Sem curso'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end">
                    <button type="reset" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded mr-2">
                        <i class="fas fa-undo mr-2"></i> Limpar
                    </button>
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                        <i class="fas fa-search mr-2"></i> Buscar
                    </button>
                </div>
            </div>
        </form>

        <!-- Tabela de resultados -->
        <?php if (isset($alunos) && count($alunos) > 0): ?>
            <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-4">
                <h4 class="font-bold text-blue-800 mb-2">Instruções para geração de documentos:</h4>
                <ul class="list-disc pl-5 space-y-1">
                    <li>Selecione um ou mais alunos na lista abaixo</li>
                    <li>Clique em "Gerar Declarações" ou "Gerar Históricos" conforme sua necessidade</li>
                    <li>Os documentos serão gerados em lotes para evitar sobrecarga do servidor</li>
                    <li>O processo pode levar alguns minutos para turmas grandes</li>
                    <li>Você também pode usar os botões flutuantes no canto inferior direito da tela</li>
                </ul>
                <p class="mt-2 font-medium">Aguarde até que todos os documentos sejam gerados. Não feche a página durante o processamento.</p>
            </div>

            <form action="documentos.php" method="post" id="form-documentos-multiplos">
                <!-- Passamos o action via POST para garantir que seja processado corretamente -->
                <input type="hidden" name="action" value="gerar_documentos_multiplos">

                <div class="mb-4">
                    <div class="flex justify-between items-center mb-4">
                        <div class="flex items-center">
                            <input type="checkbox" id="selecionar-todos" class="mr-2 h-5 w-5 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                            <label for="selecionar-todos" class="text-sm font-medium text-gray-700">Selecionar todos</label>
                        </div>

                        <!-- Opções para declarações -->
                        <div class="flex items-center space-x-4">
                            <div class="flex items-center">
                                <input type="checkbox" id="exibir_polo" name="exibir_polo" value="1" checked class="mr-2 h-5 w-5 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                                <label for="exibir_polo" class="text-sm font-medium text-gray-700">Exibir polo nas declarações</label>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-2">
                        <button type="submit" name="tipo_documento" value="declaracao" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded text-sm">
                            <i class="fas fa-file-alt mr-1"></i> Gerar Declarações
                        </button>
                        <button type="submit" name="tipo_documento" value="historico" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded text-sm">
                            <i class="fas fa-file-alt mr-1"></i> Gerar Históricos
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white border border-gray-200">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="py-3 px-4 text-left font-medium text-gray-600 uppercase tracking-wider border-b">Selecionar</th>
                                <th class="py-3 px-4 text-left font-medium text-gray-600 uppercase tracking-wider border-b">Nome</th>
                                <th class="py-3 px-4 text-left font-medium text-gray-600 uppercase tracking-wider border-b">CPF</th>
                                <th class="py-3 px-4 text-left font-medium text-gray-600 uppercase tracking-wider border-b">E-mail</th>
                                <th class="py-3 px-4 text-left font-medium text-gray-600 uppercase tracking-wider border-b">Curso</th>
                                <th class="py-3 px-4 text-left font-medium text-gray-600 uppercase tracking-wider border-b">Turma</th>
                                <th class="py-3 px-4 text-left font-medium text-gray-600 uppercase tracking-wider border-b">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($alunos as $aluno): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="py-3 px-4 border-b">
                                        <input type="checkbox" name="alunos[]" value="<?php echo $aluno['id']; ?>" class="checkbox-aluno h-5 w-5 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                                    </td>
                                    <td class="py-3 px-4 border-b"><?php echo htmlspecialchars($aluno['nome']); ?></td>
                                    <td class="py-3 px-4 border-b"><?php echo formatarCpf($aluno['cpf']); ?></td>
                                    <td class="py-3 px-4 border-b"><?php echo htmlspecialchars($aluno['email']); ?></td>
                                    <td class="py-3 px-4 border-b"><?php echo htmlspecialchars($aluno['curso_nome'] ?? 'Não informado'); ?></td>
                                    <td class="py-3 px-4 border-b"><?php echo htmlspecialchars($aluno['turma_nome'] ?? 'Não informado'); ?></td>
                                    <td class="py-3 px-4 border-b">
                                        <div class="flex space-x-2">
                                            <a href="documentos.php?action=gerar_declaracao&aluno_id=<?php echo $aluno['id']; ?>" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm" title="Gerar Declaração de Matrícula">
                                                <i class="fas fa-file-alt mr-1"></i> Declaração
                                            </a>
                                            <a href="documentos.php?action=gerar_historico&aluno_id=<?php echo $aluno['id']; ?>" class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-sm" title="Gerar Histórico Acadêmico">
                                                <i class="fas fa-file-alt mr-1"></i> Histórico
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </form>

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    // Selecionar todos os checkboxes
                    const selecionarTodos = document.getElementById('selecionar-todos');
                    const checkboxes = document.querySelectorAll('.checkbox-aluno');

                    selecionarTodos.addEventListener('change', function() {
                        checkboxes.forEach(checkbox => {
                            checkbox.checked = selecionarTodos.checked;
                        });
                    });

                    // Atualizar o "selecionar todos" quando os checkboxes individuais forem alterados
                    checkboxes.forEach(checkbox => {
                        checkbox.addEventListener('change', function() {
                            const todosSelecionados = Array.from(checkboxes).every(c => c.checked);
                            const nenhumSelecionado = Array.from(checkboxes).every(c => !c.checked);

                            if (todosSelecionados) {
                                selecionarTodos.checked = true;
                                selecionarTodos.indeterminate = false;
                            } else if (nenhumSelecionado) {
                                selecionarTodos.checked = false;
                                selecionarTodos.indeterminate = false;
                            } else {
                                selecionarTodos.checked = false;
                                selecionarTodos.indeterminate = true;
                            }
                        });
                    });

                    // Validar o formulário antes de enviar
                    document.getElementById('form-documentos-multiplos').addEventListener('submit', function(e) {
                        const alunosSelecionados = document.querySelectorAll('.checkbox-aluno:checked');
                        const totalAlunos = alunosSelecionados.length;

                        if (totalAlunos === 0) {
                            e.preventDefault();
                            alert('Por favor, selecione pelo menos um aluno.');
                            return;
                        }

                        // Verifica se o botão de tipo_documento foi clicado
                        const tipoDocumento = document.activeElement.value;
                        const tipoDocumentoTexto = tipoDocumento === 'declaracao' ? 'declarações' : 'históricos';

                        // Mostra mensagem de confirmação
                        let mensagem = `Você está prestes a gerar ${totalAlunos} ${tipoDocumentoTexto}.\n\n`;

                        if (totalAlunos > 20) {
                            mensagem += `ATENÇÃO: Você selecionou muitos alunos (${totalAlunos}).\n`;
                            mensagem += `O processamento será feito em lotes e pode levar alguns minutos.\n\n`;
                        }

                        mensagem += `Deseja continuar?`;

                        if (confirm(mensagem)) {
                            // Adiciona um elemento de carregamento
                            const loadingDiv = document.createElement('div');
                            loadingDiv.id = 'loading-overlay';
                            loadingDiv.style.position = 'fixed';
                            loadingDiv.style.top = '0';
                            loadingDiv.style.left = '0';
                            loadingDiv.style.width = '100%';
                            loadingDiv.style.height = '100%';
                            loadingDiv.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
                            loadingDiv.style.display = 'flex';
                            loadingDiv.style.justifyContent = 'center';
                            loadingDiv.style.alignItems = 'center';
                            loadingDiv.style.zIndex = '9999';

                            const loadingContent = document.createElement('div');
                            loadingContent.style.backgroundColor = 'white';
                            loadingContent.style.padding = '20px';
                            loadingContent.style.borderRadius = '5px';
                            loadingContent.style.textAlign = 'center';

                            const loadingText = document.createElement('p');
                            loadingText.textContent = `Preparando para gerar ${totalAlunos} documentos em lotes. Por favor, aguarde...`;
                            loadingText.style.marginBottom = '10px';

                            const spinner = document.createElement('div');
                            spinner.style.border = '4px solid #f3f3f3';
                            spinner.style.borderTop = '4px solid #3498db';
                            spinner.style.borderRadius = '50%';
                            spinner.style.width = '30px';
                            spinner.style.height = '30px';
                            spinner.style.animation = 'spin 2s linear infinite';
                            spinner.style.margin = '0 auto';

                            const style = document.createElement('style');
                            style.textContent = '@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }';

                            document.head.appendChild(style);
                            loadingContent.appendChild(loadingText);
                            loadingContent.appendChild(spinner);
                            loadingDiv.appendChild(loadingContent);
                            document.body.appendChild(loadingDiv);
                        } else {
                            e.preventDefault();
                        }
                    });
                });

                // Adiciona botões flutuantes para facilitar a navegação
                const actionButtons = document.createElement('div');
                actionButtons.className = 'fixed bottom-6 right-6 flex flex-col space-y-2';

                // Botão para voltar ao topo
                const scrollToTopBtn = document.createElement('button');
                scrollToTopBtn.innerHTML = '<i class="fas fa-arrow-up"></i>';
                scrollToTopBtn.className = 'bg-blue-500 hover:bg-blue-600 text-white rounded-full w-12 h-12 flex items-center justify-center shadow-lg';
                scrollToTopBtn.title = 'Voltar ao topo';

                // Botão para gerar declarações
                const gerarDeclaracoesBtn = document.createElement('button');
                gerarDeclaracoesBtn.innerHTML = '<i class="fas fa-file-alt"></i>';
                gerarDeclaracoesBtn.className = 'bg-green-500 hover:bg-green-600 text-white rounded-full w-12 h-12 flex items-center justify-center shadow-lg';
                gerarDeclaracoesBtn.title = 'Gerar Declarações';

                // Adiciona os botões ao container
                actionButtons.appendChild(scrollToTopBtn);
                actionButtons.appendChild(gerarDeclaracoesBtn);
                document.body.appendChild(actionButtons);

                // Mostra/oculta o botão de voltar ao topo com base na posição da rolagem
                window.addEventListener('scroll', function() {
                    if (window.pageYOffset > 300) {
                        scrollToTopBtn.style.display = 'flex';
                    } else {
                        scrollToTopBtn.style.display = 'none';
                    }
                });

                // Inicialmente oculta o botão de voltar ao topo
                scrollToTopBtn.style.display = 'none';

                // Rola para o topo quando o botão é clicado
                scrollToTopBtn.addEventListener('click', function() {
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                });

                // Gera declarações quando o botão é clicado
                gerarDeclaracoesBtn.addEventListener('click', function() {
                    const alunosSelecionados = document.querySelectorAll('.checkbox-aluno:checked');
                    if (alunosSelecionados.length > 0) {
                        const submitBtn = document.querySelector('button[name="tipo_documento"][value="declaracao"]');
                        if (submitBtn) {
                            submitBtn.click();
                        }
                    } else {
                        alert('Por favor, selecione pelo menos um aluno.');
                    }
                });
            </script>
        <?php elseif (isset($_GET['busca'])): ?>
            <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-4">
                <p class="font-medium">Nenhum aluno encontrado com os critérios de busca informados.</p>
            </div>
        <?php else: ?>
            <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-4">
                <p class="font-medium">Digite um nome ou CPF para buscar alunos.</p>
            </div>
        <?php endif; ?>
    </div>
    <!-- Rodapé com informações adicionais -->
    <div class="p-6 bg-gray-50 border-t border-gray-200">
        <p class="text-sm text-gray-600">Selecione um ou mais alunos para gerar documentos. Você pode gerar documentos individualmente ou em lotes.</p>
    </div>
</div>
