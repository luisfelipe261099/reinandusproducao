<?php
// Verifica se há mensagens de erro
if (isset($_SESSION['form_errors']) && !empty($_SESSION['form_errors'])) {
    echo '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">';
    echo '<ul class="list-disc pl-5">';
    foreach ($_SESSION['form_errors'] as $error) {
        echo '<li>' . htmlspecialchars($error) . '</li>';
    }
    echo '</ul>';
    echo '</div>';
    unset($_SESSION['form_errors']);
}

// Recupera os dados do formulário, se houver
$form_data = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_data']);

// Define valores padrão
$aluno_ids = $form_data['aluno_ids'] ?? [];
$categoria_id = $form_data['categoria_id'] ?? '';
$plano_conta_id = $form_data['plano_conta_id'] ?? '';
$descricao = $form_data['descricao'] ?? 'Mensalidade';
$valor = $form_data['valor'] ?? '';
$desconto = $form_data['desconto'] ?? '0.00';
$acrescimo = $form_data['acrescimo'] ?? '0.00';
$data_vencimento_inicial = $form_data['data_vencimento_inicial'] ?? date('Y-m-d');
$forma_pagamento = $form_data['forma_pagamento'] ?? '';
$total_meses = $form_data['total_meses'] ?? 12;
$dia_vencimento = $form_data['dia_vencimento'] ?? date('d');
?>

<div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
    <div class="p-6 border-b border-gray-200">
        <h3 class="text-xl font-semibold text-gray-800"><?php echo $titulo_pagina; ?></h3>
        <p class="text-gray-600 mt-1">Crie mensalidades recorrentes para vários alunos de uma só vez.</p>
    </div>

    <form action="mensalidades_debug.php" method="post" class="p-6 space-y-6" id="form-mensalidades-recorrentes">
        <!-- Seleção de Alunos -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Selecione os Alunos <span class="text-red-600">*</span></label>

            <!-- Filtros de alunos com busca em tempo real -->
            <div class="bg-gray-50 p-4 rounded-md border border-gray-200 mb-4">
                <h4 class="font-medium text-gray-700 mb-3">Filtrar Alunos</h4>
                <div class="filtro-form">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="busca_aluno_live" class="block text-sm font-medium text-gray-700 mb-1">Buscar por Nome/CPF/Email</label>
                            <input type="text" id="busca_aluno_live"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50"
                                   placeholder="Digite para buscar em tempo real...">
                        </div>

                        <div>
                            <label for="filtro_status_live" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select id="filtro_status_live" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                                <option value="">Todos</option>
                                <option value="ativo">Ativo</option>
                                <option value="inativo">Inativo</option>
                                <option value="trancado">Trancado</option>
                            </select>
                        </div>

                        <div class="flex items-end">
                            <button type="button" id="btn-buscar-alunos" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded">
                                <i class="fas fa-search mr-2"></i> Buscar
                            </button>
                            <button type="button" id="btn-limpar-busca" class="ml-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-2 px-4 rounded">
                                <i class="fas fa-times mr-2"></i> Limpar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-3 flex justify-between items-center">
                <div>
                    <button type="button" id="selecionar-todos" class="bg-blue-100 text-blue-700 px-3 py-1 rounded-md text-sm hover:bg-blue-200">Selecionar Todos</button>
                    <button type="button" id="desmarcar-todos" class="bg-gray-100 text-gray-700 px-3 py-1 rounded-md text-sm hover:bg-gray-200 ml-2">Desmarcar Todos</button>
                </div>
                <div class="text-sm text-gray-600">
                    Total: <span class="font-medium" id="total-alunos"><?php echo $total_alunos; ?></span> alunos encontrados
                </div>
            </div>

            <div class="max-h-60 overflow-y-auto border border-gray-300 rounded-md p-3" id="lista-alunos">
                <?php if (empty($alunos)): ?>
                <p class="text-gray-500">Nenhum aluno encontrado.</p>
                <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    <?php foreach ($alunos as $aluno): ?>
                    <div class="flex items-start">
                        <input type="checkbox" name="aluno_ids[]" id="aluno_<?php echo $aluno['id']; ?>" value="<?php echo $aluno['id']; ?>"
                               class="mt-1 aluno-checkbox" <?php echo in_array($aluno['id'], $aluno_ids) ? 'checked' : ''; ?>>
                        <label for="aluno_<?php echo $aluno['id']; ?>" class="ml-2 text-sm text-gray-700">
                            <span class="font-medium"><?php echo htmlspecialchars($aluno['nome']); ?></span>
                            <?php if (!empty($aluno['cpf'])): ?>
                            <br><span class="text-xs text-gray-500">CPF: <?php echo htmlspecialchars($aluno['cpf']); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($aluno['curso_nome'])): ?>
                            <br><span class="text-xs text-gray-500">Curso: <?php echo htmlspecialchars($aluno['curso_nome']); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($aluno['status'])): ?>
                            <br><span class="text-xs <?php echo $aluno['status'] === 'ativo' ? 'text-green-600' : 'text-red-600'; ?>">
                                Status: <?php echo ucfirst(htmlspecialchars($aluno['status'])); ?>
                            </span>
                            <?php endif; ?>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Paginação dinâmica -->
            <div class="mt-4 flex justify-between items-center" id="paginacao-alunos">
                <!-- Será preenchido via JavaScript -->
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Categoria -->
            <div>
                <label for="categoria_id" class="block text-sm font-medium text-gray-700 mb-1">Categoria <span class="text-red-600">*</span></label>
                <select name="categoria_id" id="categoria_id" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    <option value="">Selecione uma categoria</option>
                    <?php foreach ($categorias as $categoria): ?>
                    <option value="<?php echo $categoria['id']; ?>" <?php echo $categoria_id == $categoria['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($categoria['nome']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <p class="text-sm text-gray-500 mt-1">
                    <a href="../scripts/criar_categorias.php" target="_blank" class="text-blue-600 hover:underline">Criar mais categorias</a>
                </p>
            </div>

            <!-- Plano de Contas -->
            <div>
                <label for="plano_conta_id" class="block text-sm font-medium text-gray-700 mb-1">Plano de Contas <span class="text-red-600">*</span></label>
                <select name="plano_conta_id" id="plano_conta_id" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    <option value="">Selecione um plano de contas</option>
                    <?php foreach ($plano_contas as $plano): ?>
                    <option value="<?php echo $plano['id']; ?>" <?php echo $plano_conta_id == $plano['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($plano['codigo'] . ' - ' . $plano['descricao']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <p class="text-sm text-gray-500 mt-1">
                    <a href="../scripts/criar_plano_contas.php" target="_blank" class="text-blue-600 hover:underline">Criar mais planos de contas</a>
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Descrição -->
            <div>
                <label for="descricao" class="block text-sm font-medium text-gray-700 mb-1">Descrição <span class="text-red-600">*</span></label>
                <input type="text" name="descricao" id="descricao" value="<?php echo htmlspecialchars($descricao); ?>" required
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                <p class="text-sm text-gray-500 mt-1">Ex: Mensalidade, Parcela do Curso, etc.</p>
            </div>

            <!-- Valor -->
            <div>
                <label for="valor" class="block text-sm font-medium text-gray-700 mb-1">Valor <span class="text-red-600">*</span></label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="text-gray-500">R$</span>
                    </div>
                    <input type="text" name="valor" id="valor" value="<?php echo htmlspecialchars($valor); ?>" required
                           class="w-full pl-10 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 money-mask">
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Desconto -->
            <div>
                <label for="desconto" class="block text-sm font-medium text-gray-700 mb-1">Desconto</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="text-gray-500">R$</span>
                    </div>
                    <input type="text" name="desconto" id="desconto" value="<?php echo htmlspecialchars($desconto); ?>"
                           class="w-full pl-10 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 money-mask">
                </div>
            </div>

            <!-- Acréscimo -->
            <div>
                <label for="acrescimo" class="block text-sm font-medium text-gray-700 mb-1">Acréscimo</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="text-gray-500">R$</span>
                    </div>
                    <input type="text" name="acrescimo" id="acrescimo" value="<?php echo htmlspecialchars($acrescimo); ?>"
                           class="w-full pl-10 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 money-mask">
                </div>
            </div>

            <!-- Forma de Pagamento -->
            <div>
                <label for="forma_pagamento" class="block text-sm font-medium text-gray-700 mb-1">Forma de Pagamento</label>
                <select name="forma_pagamento" id="forma_pagamento" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    <option value="">Selecione</option>
                    <option value="boleto" <?php echo $forma_pagamento === 'boleto' ? 'selected' : ''; ?>>Boleto</option>
                    <option value="cartao" <?php echo $forma_pagamento === 'cartao' ? 'selected' : ''; ?>>Cartão de Crédito</option>
                    <option value="pix" <?php echo $forma_pagamento === 'pix' ? 'selected' : ''; ?>>PIX</option>
                    <option value="dinheiro" <?php echo $forma_pagamento === 'dinheiro' ? 'selected' : ''; ?>>Dinheiro</option>
                    <option value="transferencia" <?php echo $forma_pagamento === 'transferencia' ? 'selected' : ''; ?>>Transferência</option>
                    <option value="outro" <?php echo $forma_pagamento === 'outro' ? 'selected' : ''; ?>>Outro</option>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Data de Vencimento Inicial -->
            <div>
                <label for="data_vencimento_inicial" class="block text-sm font-medium text-gray-700 mb-1">Data do 1º Vencimento <span class="text-red-600">*</span></label>
                <input type="date" name="data_vencimento_inicial" id="data_vencimento_inicial" value="<?php echo htmlspecialchars($data_vencimento_inicial); ?>" required
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
            </div>

            <!-- Dia de Vencimento -->
            <div>
                <label for="dia_vencimento" class="block text-sm font-medium text-gray-700 mb-1">Dia de Vencimento Mensal <span class="text-red-600">*</span></label>
                <select name="dia_vencimento" id="dia_vencimento" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    <?php for ($i = 1; $i <= 28; $i++): ?>
                    <option value="<?php echo $i; ?>" <?php echo (int)$dia_vencimento === $i ? 'selected' : ''; ?>>
                        Dia <?php echo $i; ?>
                    </option>
                    <?php endfor; ?>
                </select>
                <p class="text-sm text-gray-500 mt-1">Dia fixo para vencimentos mensais</p>
            </div>

            <!-- Total de Meses -->
            <div>
                <label for="total_meses" class="block text-sm font-medium text-gray-700 mb-1">Total de Meses <span class="text-red-600">*</span></label>
                <input type="number" name="total_meses" id="total_meses" value="<?php echo htmlspecialchars($total_meses); ?>" required min="1" max="36"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                <p class="text-sm text-gray-500 mt-1">Quantos meses serão gerados (máx. 36)</p>
            </div>
        </div>

        <!-- Resumo -->
        <div class="bg-gray-50 p-4 rounded-md border border-gray-200">
            <h4 class="font-medium text-gray-700 mb-2">Resumo da Operação</h4>
            <div id="resumo-operacao">
                <p>Selecione pelo menos um aluno para ver o resumo.</p>
            </div>
        </div>

        <!-- Botões -->
        <div class="flex justify-end space-x-3">
            <a href="mensalidades.php" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Cancelar
            </a>
            <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Gerar Mensalidades
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Busca em tempo real
    let timeoutId;
    let paginaAtual = 1;

    // Função para buscar alunos via AJAX
    function buscarAlunos(pagina = 1) {
        const termo = document.getElementById('busca_aluno_live').value;
        const status = document.getElementById('filtro_status_live').value;

        // Exibe indicador de carregamento
        document.getElementById('lista-alunos').innerHTML = '<div class="text-center p-4"><i class="fas fa-spinner fa-spin"></i> Carregando...</div>';

        // Faz a requisição AJAX
        fetch(`../financeiro/ajax/buscar_alunos.php?termo=${encodeURIComponent(termo)}&status=${encodeURIComponent(status)}&pagina=${pagina}`)
            .then(response => response.json())
            .then(data => {
                // Atualiza a lista de alunos
                document.getElementById('lista-alunos').innerHTML = data.html;
                document.getElementById('total-alunos').textContent = data.total;

                // Atualiza a paginação se necessário
                if (data.paginas > 1) {
                    document.getElementById('paginacao-alunos').style.display = 'flex';
                } else {
                    document.getElementById('paginacao-alunos').style.display = 'none';
                }

                // Adiciona eventos aos checkboxes recém-carregados
                document.querySelectorAll('.aluno-checkbox').forEach(function(checkbox) {
                    checkbox.addEventListener('change', atualizarResumo);
                });

                // Adiciona eventos aos botões de paginação
                document.querySelectorAll('.pagina-btn').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        paginaAtual = parseInt(this.getAttribute('data-pagina'));
                        buscarAlunos(paginaAtual);
                    });
                });

                // Atualiza o resumo
                atualizarResumo();
            })
            .catch(error => {
                console.error('Erro ao buscar alunos:', error);
                document.getElementById('lista-alunos').innerHTML = '<p class="text-red-500 p-3">Erro ao buscar alunos. Tente novamente.</p>';
            });
    }

    // Evento de digitação com debounce
    document.getElementById('busca_aluno_live').addEventListener('input', function() {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => {
            paginaAtual = 1; // Volta para a primeira página ao fazer uma nova busca
            buscarAlunos(paginaAtual);
        }, 500); // Aguarda 500ms após o usuário parar de digitar
    });

    // Evento de mudança no filtro de status
    document.getElementById('filtro_status_live').addEventListener('change', function() {
        paginaAtual = 1; // Volta para a primeira página ao mudar o filtro
        buscarAlunos(paginaAtual);
    });

    // Botão de busca
    document.getElementById('btn-buscar-alunos').addEventListener('click', function() {
        paginaAtual = 1;
        buscarAlunos(paginaAtual);
    });

    // Botão de limpar
    document.getElementById('btn-limpar-busca').addEventListener('click', function() {
        document.getElementById('busca_aluno_live').value = '';
        document.getElementById('filtro_status_live').value = '';
        paginaAtual = 1;
        buscarAlunos(paginaAtual);
    });

    // Inicializa a busca ao carregar a página
    buscarAlunos(1);
    // Máscaras para campos monetários - versão simplificada para facilitar a digitação
    const moneyInputs = document.querySelectorAll('.money-mask');
    moneyInputs.forEach(function(input) {
        // Formata o valor inicial
        if (input.value !== '' && !isNaN(parseFloat(input.value))) {
            input.value = parseFloat(input.value).toFixed(2);
        }

        // Adiciona evento de foco para selecionar todo o texto
        input.addEventListener('focus', function(e) {
            setTimeout(() => this.select(), 100);
        });

        // Formata apenas quando o campo perde o foco
        input.addEventListener('blur', function(e) {
            let value = e.target.value;

            // Remove caracteres inválidos
            value = value.replace(/[^\d.,]/g, '');

            // Converte vírgula para ponto
            value = value.replace(',', '.');

            // Garante que há apenas um ponto decimal
            const parts = value.split('.');
            if (parts.length > 2) {
                value = parts[0] + '.' + parts.slice(1).join('');
            }

            // Formata para duas casas decimais se houver um valor válido
            if (value !== '' && !isNaN(parseFloat(value))) {
                value = parseFloat(value).toFixed(2);
            }

            e.target.value = value;

            // Atualiza o resumo após a formatação
            atualizarResumo();
        });
    });

    // Selecionar/Desmarcar todos os alunos
    const selecionarTodos = document.getElementById('selecionar-todos');
    const desmarcarTodos = document.getElementById('desmarcar-todos');
    const checkboxes = document.querySelectorAll('.aluno-checkbox');

    selecionarTodos.addEventListener('click', function() {
        checkboxes.forEach(function(checkbox) {
            checkbox.checked = true;
        });
        atualizarResumo();
    });

    desmarcarTodos.addEventListener('click', function() {
        checkboxes.forEach(function(checkbox) {
            checkbox.checked = false;
        });
        atualizarResumo();
    });

    // Atualizar resumo quando os checkboxes forem alterados
    checkboxes.forEach(function(checkbox) {
        checkbox.addEventListener('change', atualizarResumo);
    });

    // Atualizar resumo quando os campos relevantes forem alterados
    document.getElementById('valor').addEventListener('input', atualizarResumo);
    document.getElementById('desconto').addEventListener('input', atualizarResumo);
    document.getElementById('acrescimo').addEventListener('input', atualizarResumo);
    document.getElementById('total_meses').addEventListener('input', atualizarResumo);

    // Função para atualizar o resumo
    function atualizarResumo() {
        const alunosSelecionados = document.querySelectorAll('.aluno-checkbox:checked').length;
        const valor = parseFloat(document.getElementById('valor').value) || 0;
        const desconto = parseFloat(document.getElementById('desconto').value) || 0;
        const acrescimo = parseFloat(document.getElementById('acrescimo').value) || 0;
        const totalMeses = parseInt(document.getElementById('total_meses').value) || 0;

        const valorPorMensalidade = valor - desconto + acrescimo;
        const totalPorAluno = valorPorMensalidade * totalMeses;
        const totalGeral = totalPorAluno * alunosSelecionados;

        const resumoElement = document.getElementById('resumo-operacao');

        if (alunosSelecionados === 0) {
            resumoElement.innerHTML = '<p>Selecione pelo menos um aluno para ver o resumo.</p>';
            return;
        }

        let html = `
            <ul class="space-y-2 text-sm">
                <li><span class="font-medium">Alunos selecionados:</span> ${alunosSelecionados}</li>
                <li><span class="font-medium">Valor por mensalidade:</span> R$ ${valorPorMensalidade.toFixed(2)}</li>
                <li><span class="font-medium">Total de meses:</span> ${totalMeses}</li>
                <li><span class="font-medium">Total por aluno:</span> R$ ${totalPorAluno.toFixed(2)}</li>
                <li class="text-blue-700 font-medium">Total geral: R$ ${totalGeral.toFixed(2)}</li>
                <li class="text-gray-500">Serão geradas ${alunosSelecionados * totalMeses} mensalidades no total.</li>
            </ul>
        `;

        resumoElement.innerHTML = html;
    }

    // Inicializar o resumo
    atualizarResumo();
});
</script>
