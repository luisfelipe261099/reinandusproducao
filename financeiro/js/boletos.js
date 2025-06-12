/**
 * JavaScript para funcionalidades de boletos
 */

function alterarTipoBoleto() {
    const tipo = document.getElementById('tipo-boleto').value;
    const secaoAluno = document.getElementById('secao-aluno');
    const secaoPolo = document.getElementById('secao-polo');
    const descricao = document.getElementById('descricao');
    
    // Esconde todas as seções
    secaoAluno.classList.add('hidden');
    secaoPolo.classList.add('hidden');
    
    // Limpa campos
    document.getElementById('aluno-select').value = '';
    document.getElementById('polo-select').value = '';
    document.getElementById('referencia-id').value = '';
    
    // Mostra seção apropriada e define descrição padrão
    switch(tipo) {
        case 'mensalidade':
            secaoAluno.classList.remove('hidden');
            descricao.value = 'Mensalidade - ';
            break;
        case 'polo':
            secaoPolo.classList.remove('hidden');
            descricao.value = 'Cobrança Polo - ';
            break;
        case 'avulso':
            descricao.value = '';
            break;
    }
}

function preencherDadosAluno() {
    const select = document.getElementById('aluno-select');
    const option = select.options[select.selectedIndex];
    
    if (option.value) {
        const nome = option.getAttribute('data-nome');
        const cpf = option.getAttribute('data-cpf');
        
        document.getElementById('referencia-id').value = option.value;
        document.getElementById('nome-pagador').value = nome;
        document.getElementById('cpf-pagador').value = cpf;
        document.getElementById('descricao').value = `Mensalidade - ${nome}`;
    }
}

function preencherDadosPolo() {
    const select = document.getElementById('polo-select');
    const option = select.options[select.selectedIndex];
    
    if (option.value) {
        const nome = option.getAttribute('data-nome');
        
        document.getElementById('referencia-id').value = option.value;
        document.getElementById('descricao').value = `Cobrança Polo - ${nome}`;
    }
}

function mostrarLinhaDigitavel(linha) {
    Financeiro.Modal.show('Linha Digitável do Boleto', `
        <div class="text-center">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Linha Digitável:</label>
                <div class="bg-gray-100 p-4 rounded-lg font-mono text-lg border-2 border-dashed border-gray-300">
                    ${linha}
                </div>
            </div>
            <div class="flex justify-center space-x-3">
                <button onclick="copiarLinhaDigitavel('${linha}')" 
                        class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                    <i class="fas fa-copy mr-2"></i>Copiar
                </button>
                <button onclick="imprimirLinhaDigitavel('${linha}')" 
                        class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                    <i class="fas fa-print mr-2"></i>Imprimir
                </button>
            </div>
        </div>
    `, {
        showConfirm: false,
        showCancel: false,
        customButtons: `
            <button onclick="Financeiro.Modal.hide()" 
                    class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400">
                Fechar
            </button>
        `
    });
}

function copiarLinhaDigitavel(linha) {
    navigator.clipboard.writeText(linha).then(() => {
        Financeiro.showNotification('Linha digitável copiada!', 'success');
    }).catch(() => {
        // Fallback para navegadores mais antigos
        const textArea = document.createElement('textarea');
        textArea.value = linha;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        Financeiro.showNotification('Linha digitável copiada!', 'success');
    });
}

function imprimirLinhaDigitavel(linha) {
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>Linha Digitável</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; }
                .linha { font-family: monospace; font-size: 18px; font-weight: bold; 
                        border: 2px dashed #ccc; padding: 15px; text-align: center; }
            </style>
        </head>
        <body>
            <h2>Linha Digitável do Boleto</h2>
            <div class="linha">${linha}</div>
            <script>window.print();</script>
        </body>
        </html>
    `);
    printWindow.document.close();
}

function verDetalhes(boletoId) {
    // Aqui você pode implementar uma chamada AJAX para buscar detalhes do boleto
    fetch(`ajax/boleto_detalhes.php?id=${boletoId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const boleto = data.boleto;
                Financeiro.Modal.show('Detalhes do Boleto', `
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Nosso Número:</label>
                                <p class="text-sm text-gray-900">${boleto.nosso_numero || '-'}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Status:</label>
                                <p class="text-sm text-gray-900">${boleto.status}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Valor:</label>
                                <p class="text-sm text-gray-900">R$ ${parseFloat(boleto.valor).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Vencimento:</label>
                                <p class="text-sm text-gray-900">${new Date(boleto.data_vencimento).toLocaleDateString('pt-BR')}</p>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Pagador:</label>
                            <p class="text-sm text-gray-900">${boleto.nome_pagador}</p>
                            <p class="text-sm text-gray-500">${boleto.cpf_pagador}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Descrição:</label>
                            <p class="text-sm text-gray-900">${boleto.descricao}</p>
                        </div>
                        
                        ${boleto.linha_digitavel ? `
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Linha Digitável:</label>
                            <div class="bg-gray-100 p-2 rounded font-mono text-xs">
                                ${boleto.linha_digitavel}
                            </div>
                        </div>
                        ` : ''}
                        
                        ${boleto.codigo_barras ? `
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Código de Barras:</label>
                            <div class="bg-gray-100 p-2 rounded font-mono text-xs">
                                ${boleto.codigo_barras}
                            </div>
                        </div>
                        ` : ''}
                        
                        ${boleto.url_boleto ? `
                        <div class="text-center">
                            <a href="${boleto.url_boleto}" target="_blank" 
                               class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                                <i class="fas fa-file-pdf mr-2"></i>Visualizar Boleto
                            </a>
                        </div>
                        ` : ''}
                    </div>
                `, {
                    showConfirm: false,
                    showCancel: false,
                    customButtons: `
                        <button onclick="Financeiro.Modal.hide()" 
                                class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400">
                            Fechar
                        </button>
                    `
                });
            } else {
                Financeiro.showNotification('Erro ao carregar detalhes do boleto', 'error');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            Financeiro.showNotification('Erro ao carregar detalhes do boleto', 'error');
        });
}

// Função para excluir boleto
function excluirBoleto(boletoId, descricaoBoleto) {
    console.log('Função excluirBoleto chamada com ID:', boletoId, 'Descrição:', descricaoBoleto);
    if (confirm(`Tem certeza que deseja excluir o boleto "${descricaoBoleto}" (ID: ${boletoId})? Esta ação não pode ser desfeita.`)) {
        console.log('Usuário confirmou a exclusão.');

        // Criar um formulário dinamicamente
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'boletos.php'; // A action é a própria página que processa o POST

        // Adicionar campo de ação
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'excluir_boleto';
        form.appendChild(actionInput);

        // Adicionar campo com o ID do boleto
        const boletoIdInput = document.createElement('input');
        boletoIdInput.type = 'hidden';
        boletoIdInput.name = 'boleto_id';
        boletoIdInput.value = boletoId;
        form.appendChild(boletoIdInput);

        // Adicionar o formulário ao corpo do documento e submetê-lo
        document.body.appendChild(form);
        console.log('Formulário de exclusão criado e pronto para ser submetido:', form);
        form.submit();
    } else {
        console.log('Usuário cancelou a exclusão.');
    }
}

// Funções para busca de alunos
let buscaAlunoTimeout;

function inicializarBuscaAluno() {
    const campoBusca = document.getElementById('busca-aluno');
    const resultados = document.getElementById('resultados-aluno');
    const loading = document.getElementById('loading-aluno');
    
    if (!campoBusca || !resultados) return;
    
    campoBusca.addEventListener('input', function() {
        clearTimeout(buscaAlunoTimeout);
        const termo = this.value.trim();
        
        if (termo.length < 2) {
            resultados.innerHTML = '';
            resultados.classList.add('hidden');
            return;
        }
        
        loading.classList.remove('hidden');
        
        buscaAlunoTimeout = setTimeout(() => {
            buscarAlunos(termo);
        }, 300);
    });
    
    // Fecha resultados ao clicar fora
    document.addEventListener('click', function(e) {
        if (!campoBusca.contains(e.target) && !resultados.contains(e.target)) {
            resultados.classList.add('hidden');
        }
    });
}

function buscarAlunos(termo) {
    const resultados = document.getElementById('resultados-aluno');
    const loading = document.getElementById('loading-aluno');
    
    fetch(`ajax/buscar_alunos.php?termo=${encodeURIComponent(termo)}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        loading.classList.add('hidden');
        
        if (data.error) {
            resultados.innerHTML = `<div class="p-3 text-red-500">${data.error}</div>`;
            resultados.classList.remove('hidden');
            return;
        }
        
        if (data.alunos.length === 0) {
            resultados.innerHTML = '<div class="p-3 text-gray-500 text-center">Nenhum aluno encontrado</div>';
            resultados.classList.remove('hidden');
            return;
        }
        
        let html = '';
        data.alunos.forEach(aluno => {
            html += `
                <div class="p-3 hover:bg-gray-100 cursor-pointer border-b" 
                     onclick="selecionarAluno(${aluno.id}, '${aluno.nome}', '${aluno.cpf}')">
                    <div class="font-medium text-gray-900">${aluno.nome}</div>
                    <div class="text-sm text-gray-500">CPF: ${aluno.cpf_formatado}</div>
                    ${aluno.email ? `<div class="text-sm text-gray-400">${aluno.email}</div>` : ''}
                </div>`;
        });
        
        resultados.innerHTML = html;
        resultados.classList.remove('hidden');
        
        if (data.limite_atingido) {
            resultados.innerHTML += '<div class="p-2 text-xs text-gray-400 text-center">Digite mais caracteres para refinar a busca</div>';
        }
    })
    .catch(error => {
        loading.classList.add('hidden');
        console.error('Erro na busca:', error);
        resultados.innerHTML = '<div class="p-3 text-red-500">Erro ao buscar alunos</div>';
        resultados.classList.remove('hidden');
    });
}

function selecionarAluno(id, nome, cpf) {
    // Preenche os campos
    document.getElementById('referencia-id').value = id;
    document.getElementById('nome-pagador').value = nome;
    document.getElementById('cpf-pagador').value = cpf;
    document.getElementById('descricao').value = `Mensalidade - ${nome}`;
    
    // Mostra o aluno selecionado
    document.getElementById('nome-aluno-selecionado').textContent = nome;
    document.getElementById('cpf-aluno-selecionado').textContent = `CPF: ${cpf}`;
    document.getElementById('aluno-selecionado').classList.remove('hidden');
    
    // Esconde os resultados
    document.getElementById('resultados-aluno').classList.add('hidden');
    document.getElementById('busca-aluno').value = '';
}

function limparSelecaoAluno() {
    document.getElementById('referencia-id').value = '';
    document.getElementById('nome-pagador').value = '';
    document.getElementById('cpf-pagador').value = '';
    document.getElementById('descricao').value = 'Mensalidade - ';
    document.getElementById('aluno-selecionado').classList.add('hidden');
    document.getElementById('busca-aluno').value = '';
}

// Inicialização quando a página carrega
document.addEventListener('DOMContentLoaded', function() {
    // Aplica máscaras nos campos
    Financeiro.applyMasks();
    
    // Define data mínima como hoje para vencimento
    const dataVencimento = document.querySelector('input[name="data_vencimento"]');
    if (dataVencimento) {
        const hoje = new Date();
        const amanha = new Date(hoje);
        amanha.setDate(hoje.getDate() + 1);
        dataVencimento.min = amanha.toISOString().split('T')[0];
        dataVencimento.value = amanha.toISOString().split('T')[0];
    }
    
    // Inicializa busca de alunos
    inicializarBuscaAluno();
});
