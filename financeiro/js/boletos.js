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
});
