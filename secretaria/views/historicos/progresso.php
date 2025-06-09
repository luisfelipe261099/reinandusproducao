<?php
/**
 * View para exibir o progresso da geração de documentos em lotes
 */

// Log para depuração
error_log("Carregando página de progresso.php. Total de lotes: {$total_lotes}, Total de alunos: " . $_SESSION['processamento']['total_alunos']);
?>

<!-- Botões de navegação no topo da página -->
<div class="mb-4 flex justify-between items-center">
    <div>
        <a href="documentos.php?action=selecionar_aluno" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
            <i class="fas fa-arrow-left mr-2"></i> Voltar para seleção de alunos
        </a>
    </div>
    <div>
        <a href="#" id="btn-topo" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
            <i class="fas fa-arrow-up mr-2"></i> Topo
        </a>
    </div>
</div>

<script>
// Adiciona comportamento ao botão de topo
document.addEventListener('DOMContentLoaded', function() {
    const btnTopo = document.getElementById('btn-topo');
    if (btnTopo) {
        btnTopo.addEventListener('click', function(e) {
            e.preventDefault();
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }
});
</script>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="p-6 border-b border-gray-200">
        <h3 class="text-xl font-semibold text-gray-800">Gerando Documentos</h3>
    </div>
    <div class="p-6">
        <div class="mb-6">
            <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-4">
                <p class="font-medium">Os documentos estão sendo gerados em lotes de <?php echo TAMANHO_LOTE; ?> alunos.</p>
                <p class="mt-2">Você selecionou <?php echo $_SESSION['processamento']['total_alunos']; ?> alunos, que serão processados em <?php echo $total_lotes; ?> lotes.</p>
                <p class="mt-2">Este processo pode levar vários minutos para turmas grandes. Por favor, não feche esta janela ou navegue para outra página até que o processo seja concluído.</p>
            </div>

            <div class="mt-6">
                <div class="w-full bg-gray-200 rounded-full h-4 mb-2">
                    <div id="progress-bar" class="bg-blue-500 h-4 rounded-full" style="width: 0%"></div>
                </div>
                <div class="flex justify-between text-sm text-gray-600">
                    <span id="progress-text">Processando lote 1 de <?php echo $total_lotes; ?></span>
                    <span id="progress-percentage">0%</span>
                </div>
            </div>

            <div id="status-messages" class="mt-6 p-4 bg-gray-50 rounded-lg max-h-60 overflow-y-auto">
                <p class="text-gray-600">Iniciando processamento...</p>
            </div>
        </div>
    </div>
    <div class="p-6 bg-gray-50 border-t border-gray-200">
        <button id="btn-cancelar" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded">
            <i class="fas fa-times mr-2"></i> Cancelar processamento
        </button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const progressBar = document.getElementById('progress-bar');
    const progressText = document.getElementById('progress-text');
    const progressPercentage = document.getElementById('progress-percentage');
    const statusMessages = document.getElementById('status-messages');
    // Não precisamos mais do botão voltar, pois ele foi movido para o topo
    // const btnVoltar = document.getElementById('btn-voltar');
    const btnCancelar = document.getElementById('btn-cancelar');

    // Função para adicionar mensagem de status
    function addStatusMessage(message) {
        const p = document.createElement('p');
        p.className = 'text-gray-600 mb-1';
        p.textContent = message;
        statusMessages.appendChild(p);
        statusMessages.scrollTop = statusMessages.scrollHeight;
    }

    // Função para atualizar a barra de progresso
    function updateProgress(percent, message) {
        progressBar.style.width = percent + '%';
        progressPercentage.textContent = percent + '%';
        if (message) {
            progressText.textContent = message;
            addStatusMessage(message);
        }

        if (percent >= 100) {
            progressText.textContent = 'Processamento concluído!';
            addStatusMessage('Processamento concluído! Preparando download...');
            // Não precisamos mais mostrar o botão voltar, pois ele já está no topo
            // btnVoltar.style.display = 'inline-block';
            btnCancelar.style.display = 'none';
        }
    }

    // Função para criar e baixar o ZIP
    function criarEBaixarZip() {
        addStatusMessage('Iniciando criação do arquivo ZIP...');

        // Inicia a criação do ZIP
        fetch('documentos.php?action=criar_zip&session_id=<?php echo session_id(); ?>')
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Erro HTTP: ${response.status} - ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Resposta da criação do ZIP:', data);

                if (data.error) {
                    addStatusMessage('Erro ao criar ZIP: ' + data.error);
                    return;
                }

                if (data.status === 'iniciado') {
                    addStatusMessage('Criação do ZIP iniciada. Verificando progresso...');
                    verificarStatusZip();
                } else {
                    addStatusMessage('Erro inesperado ao criar ZIP.');
                }
            })
            .catch(error => {
                console.error('Erro ao iniciar criação do ZIP:', error);
                addStatusMessage('Erro ao iniciar criação do ZIP: ' + error);
            });
    }

    // Função para verificar o status do ZIP
    function verificarStatusZip() {
        fetch('documentos.php?action=verificar_zip&session_id=<?php echo session_id(); ?>')
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Erro HTTP: ${response.status} - ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Status do ZIP:', data);

                if (data.error) {
                    addStatusMessage('Erro ao verificar status do ZIP: ' + data.error);
                    return;
                }

                if (data.status === 'concluido') {
                    addStatusMessage('ZIP criado com sucesso! Iniciando download...');

                    // Cria um link invisível para download e clica nele
                    const downloadLink = document.createElement('a');
                    downloadLink.href = 'documentos.php?action=baixar_zip&session_id=<?php echo session_id(); ?>&zip_id=' + data.zip_id;
                    downloadLink.style.display = 'none';
                    document.body.appendChild(downloadLink);
                    downloadLink.click();
                    document.body.removeChild(downloadLink);

                    addStatusMessage('Download iniciado! Você pode fechar esta página quando o download for concluído.');
                } else if (data.status === 'processando') {
                    // Atualiza a mensagem com o progresso
                    const percentConcluido = data.percent || 0;
                    addStatusMessage(`Criando ZIP: ${percentConcluido}% concluído...`);

                    // Verifica novamente após um intervalo
                    setTimeout(verificarStatusZip, 2000);
                } else {
                    addStatusMessage('Status desconhecido: ' + data.status);
                }
            })
            .catch(error => {
                console.error('Erro ao verificar status do ZIP:', error);
                addStatusMessage('Erro ao verificar status do ZIP: ' + error);

                // Tenta novamente após um intervalo
                setTimeout(verificarStatusZip, 5000);
            });
    }

    // Função para processar um lote
    function processarLote(lote, totalLotes) {
        console.log(`Iniciando processamento do lote ${lote} de ${totalLotes}`);
        addStatusMessage(`Iniciando processamento do lote ${lote} de ${totalLotes}...`);

        const url = `documentos.php?action=processar_lote&lote=${lote}&total_lotes=${totalLotes}&session_id=<?php echo session_id(); ?>`;
        console.log(`URL de processamento: ${url}`);

        fetch(url)
            .then(response => {
                console.log(`Resposta recebida para o lote ${lote}. Status: ${response.status}`);
                if (!response.ok) {
                    throw new Error(`Erro HTTP: ${response.status} - ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                console.log(`Dados recebidos para o lote ${lote}:`, data);

                if (data.error) {
                    console.error(`Erro no lote ${lote}:`, data.error);
                    addStatusMessage('Erro: ' + data.error);
                    return;
                }

                const percentComplete = Math.round((lote / totalLotes) * 100);
                const alunosProcessados = data.total_processados;
                const totalAlunos = <?php echo $_SESSION['processamento']['total_alunos']; ?>;

                console.log(`Progresso: ${percentComplete}%, Alunos processados: ${alunosProcessados}/${totalAlunos}`);

                updateProgress(
                    percentComplete,
                    `Processado lote ${lote} de ${totalLotes} (${data.processados} documentos neste lote, ${alunosProcessados} de ${totalAlunos} no total)`
                );

                if (lote < totalLotes) {
                    // Processa o próximo lote com um atraso maior para turmas grandes
                    const delay = totalLotes > 100 ? 2000 : 1000; // Atraso maior para muitos lotes

                    console.log(`Aguardando ${delay/1000} segundos antes de processar o próximo lote...`);
                    addStatusMessage(`Aguardando ${delay/1000} segundos antes de processar o próximo lote...`);

                    setTimeout(() => {
                        processarLote(lote + 1, totalLotes);
                    }, delay);
                } else {
                    // Todos os lotes foram processados
                    console.log(`Todos os lotes processados! Total: ${alunosProcessados} documentos`);
                    updateProgress(100, 'Todos os lotes processados com sucesso!');
                    addStatusMessage(`Processamento concluído! Total de ${alunosProcessados} documentos gerados.`);
                    addStatusMessage('Preparando arquivo ZIP para download...');

                    // Inicia a criação do ZIP e verifica quando estiver pronto
                    console.log('Iniciando criação do ZIP...');
                    criarEBaixarZip();
                }
            })
            .catch(error => {
                console.error(`Erro ao processar lote ${lote}:`, error);
                addStatusMessage('Erro ao processar lote: ' + error);

                // Tenta novamente após um atraso em caso de erro
                console.log(`Tentando novamente o lote ${lote} em 5 segundos...`);
                setTimeout(() => {
                    addStatusMessage('Tentando novamente...');
                    processarLote(lote, totalLotes);
                }, 5000);
            });
    }

    // Log para depuração
    console.log("Iniciando processamento de lotes. Total de lotes: <?php echo $total_lotes; ?>");

    // Inicia o processamento do primeiro lote
    processarLote(1, <?php echo $total_lotes; ?>);

    // Botão cancelar
    btnCancelar.addEventListener('click', function() {
        console.log('Botão cancelar clicado');
        if (confirm('Tem certeza que deseja cancelar o processamento?')) {
            console.log('Cancelamento confirmado, redirecionando...');
            const cancelUrl = 'historico.php?action=cancelar_processamento&session_id=<?php echo session_id(); ?>';
            console.log(`Redirecionando para: ${cancelUrl}`);
            window.location.href = cancelUrl;
        } else {
            console.log('Cancelamento cancelado pelo usuário');
        }
    });
    // Adiciona botão flutuante para voltar ao topo
    const scrollToTopBtn = document.createElement('button');
    scrollToTopBtn.innerHTML = '<i class="fas fa-arrow-up"></i>';
    scrollToTopBtn.className = 'fixed bottom-6 right-6 bg-blue-500 hover:bg-blue-600 text-white rounded-full w-12 h-12 flex items-center justify-center shadow-lg';
    scrollToTopBtn.style.display = 'none';
    scrollToTopBtn.title = 'Voltar ao topo';
    document.body.appendChild(scrollToTopBtn);

    // Mostra/oculta o botão com base na posição da rolagem
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            scrollToTopBtn.style.display = 'flex';
        } else {
            scrollToTopBtn.style.display = 'none';
        }
    });

    // Rola para o topo quando o botão é clicado
    scrollToTopBtn.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
});
</script>
