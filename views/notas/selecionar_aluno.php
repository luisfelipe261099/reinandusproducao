<div class="bg-white rounded-xl shadow-sm p-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Selecionar Aluno para Visualizar Boletim</h3>
    
    <div class="mb-6">
        <div class="relative">
            <label for="aluno_busca" class="block text-sm font-medium text-gray-700 mb-1">Buscar Aluno:</label>
            <input type="text" id="aluno_busca" class="form-input w-full" placeholder="Digite o nome, CPF ou e-mail do aluno..." autocomplete="off">
            <div id="alunos_resultados" class="absolute z-10 w-full mt-1 bg-white shadow-lg rounded-md border border-gray-300 hidden max-h-60 overflow-y-auto"></div>
        </div>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CPF</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">E-mail</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($alunos)): ?>
                <tr>
                    <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                        Nenhum aluno encontrado. Use a busca acima para encontrar um aluno.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($alunos as $aluno): ?>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($aluno['nome']); ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-500"><?php echo !empty($aluno['cpf']) ? htmlspecialchars($aluno['cpf']) : '-'; ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-500"><?php echo !empty($aluno['email']) ? htmlspecialchars($aluno['email']) : '-'; ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <a href="notas.php?action=boletim&aluno_id=<?php echo $aluno['id']; ?>" class="text-blue-600 hover:text-blue-900">
                            <i class="fas fa-file-alt mr-1"></i> Ver Boletim
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Busca de alunos
        const alunoBuscaInput = document.getElementById('aluno_busca');
        const alunosResultados = document.getElementById('alunos_resultados');
        
        if (alunoBuscaInput) {
            // Evento de digitação na busca
            let timeoutId;
            alunoBuscaInput.addEventListener('input', function() {
                clearTimeout(timeoutId);
                const termo = this.value.trim();
                
                if (termo.length < 3) {
                    alunosResultados.innerHTML = '';
                    alunosResultados.classList.add('hidden');
                    return;
                }
                
                timeoutId = setTimeout(function() {
                    // Faz uma requisição para buscar alunos
                    fetch(`notas.php?action=buscar_aluno&termo=${encodeURIComponent(termo)}`)
                        .then(response => response.json())
                        .then(data => {
                            alunosResultados.innerHTML = '';
                            
                            if (data.length === 0) {
                                const noResultsItem = document.createElement('div');
                                noResultsItem.className = 'p-3 text-gray-500 text-center';
                                noResultsItem.textContent = 'Nenhum aluno encontrado';
                                alunosResultados.appendChild(noResultsItem);
                            } else {
                                data.forEach(aluno => {
                                    const item = document.createElement('div');
                                    item.className = 'p-3 hover:bg-gray-100 cursor-pointer';
                                    
                                    const nome = document.createElement('div');
                                    nome.className = 'font-medium';
                                    nome.textContent = aluno.nome;
                                    
                                    const info = document.createElement('div');
                                    info.className = 'text-xs text-gray-500';
                                    info.textContent = aluno.cpf ? `CPF: ${aluno.cpf}` : '';
                                    if (aluno.email) {
                                        info.textContent += aluno.cpf ? ` | ${aluno.email}` : aluno.email;
                                    }
                                    
                                    item.appendChild(nome);
                                    item.appendChild(info);
                                    
                                    item.addEventListener('click', function() {
                                        window.location.href = `notas.php?action=boletim&aluno_id=${aluno.id}`;
                                    });
                                    
                                    alunosResultados.appendChild(item);
                                });
                            }
                            
                            alunosResultados.classList.remove('hidden');
                        })
                        .catch(error => console.error('Erro ao buscar alunos:', error));
                }, 300);
            });
            
            // Fecha os resultados ao clicar fora
            document.addEventListener('click', function(e) {
                if (!alunosResultados.contains(e.target) && e.target !== alunoBuscaInput) {
                    alunosResultados.classList.add('hidden');
                }
            });
        }
    });
</script>
