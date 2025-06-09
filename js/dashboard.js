/**
 * Script para integração do dashboard com o backend
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Dashboard JS carregado');

    // Verifica se os elementos existem na página
    if (!document.getElementById('total-alunos')) {
        console.error('Elemento #total-alunos não encontrado');
    }
    if (!document.getElementById('matriculas-ativas')) {
        console.error('Elemento #matriculas-ativas não encontrado');
    }
    if (!document.getElementById('documentos-pendentes')) {
        console.error('Elemento #documentos-pendentes não encontrado');
    }
    if (!document.getElementById('turmas-ativas')) {
        console.error('Elemento #turmas-ativas não encontrado');
    }
    if (!document.getElementById('pendencias-container')) {
        console.error('Elemento #pendencias-container não encontrado');
    }
    if (!document.getElementById('atividades-container')) {
        console.error('Elemento #atividades-container não encontrado');
    }

    // Carrega as estatísticas gerais
    carregarEstatisticas();

    // Carrega as pendências da secretaria
    carregarPendencias();

    // Carrega as atividades recentes
    carregarAtividadesRecentes();

    // Carrega os próximos eventos
    carregarProximosEventos();
});

/**
 * Carrega as estatísticas gerais
 */
function carregarEstatisticas() {
    console.log('Carregando estatísticas...');
    fetch('api/dashboard.php?action=stats')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Atualiza os cards de estatísticas
                document.getElementById('total-alunos').textContent = formatarNumero(data.data.total_alunos || 0);
                document.getElementById('matriculas-ativas').textContent = formatarNumero(data.data.matriculas_ativas || 0);
                document.getElementById('documentos-pendentes').textContent = formatarNumero(data.data.documentos_pendentes || 0);
                document.getElementById('turmas-ativas').textContent = formatarNumero(data.data.turmas_ativas || 0);
            } else {
                console.error('Erro ao carregar estatísticas:', data.message);
            }
        })
        .catch(error => {
            console.error('Erro ao carregar estatísticas:', error);
        });
}

/**
 * Carrega as pendências da secretaria
 */
function carregarPendencias() {
    console.log('Carregando pendências...');
    fetch('api/dashboard.php?action=tarefas_pendentes')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const container = document.getElementById('pendencias-container');

                // Limpa o container
                container.innerHTML = '';

                // Verifica se há pendências
                if (data.data.length === 0) {
                    container.innerHTML = '<div class="text-center text-gray-500 py-4">Não há pendências no momento.</div>';
                    return;
                }

                // Adiciona as pendências
                data.data.forEach(tarefa => {
                    // Determina a classe da tarefa com base no tipo
                    let classeCartao = 'normal';
                    let classeBadge = 'badge-primary';
                    let textoTipo = 'Normal';

                    if (tarefa.tipo === 'documento') {
                        classeCartao = 'urgent';
                        classeBadge = 'badge-danger';
                        textoTipo = 'Urgente';
                    } else if (tarefa.tipo === 'matricula') {
                        classeCartao = 'important';
                        classeBadge = 'badge-warning';
                        textoTipo = 'Importante';
                    }

                    // Formata a data
                    const data = new Date(tarefa.data_matricula || tarefa.data_solicitacao);
                    const dataFormatada = data.toLocaleDateString('pt-BR');

                    // Cria o HTML da tarefa
                    const html = `
                        <div class="task-card ${classeCartao}">
                            <div class="flex justify-between items-start">
                                <div>
                                    <div class="flex items-center">
                                        <h3 class="font-semibold">${tarefa.descricao}</h3>
                                        <span class="badge ${classeBadge} ml-3">${textoTipo}</span>
                                    </div>
                                    <p class="text-gray-600 text-sm mt-1">${tarefa.aluno_nome}</p>
                                </div>
                                <div class="flex items-center">
                                    <button class="text-gray-400 hover:text-gray-600">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="flex items-center justify-between mt-4">
                                <div class="flex items-center text-sm text-gray-500">
                                    <i class="far fa-clock mr-2"></i>
                                    <span>${dataFormatada}</span>
                                </div>
                                <button class="btn-primary px-4 py-2 rounded-lg text-sm">
                                    Processar agora
                                </button>
                            </div>
                        </div>
                    `;

                    // Adiciona ao container
                    container.innerHTML += html;
                });
            } else {
                console.error('Erro ao carregar pendências:', data.message);
            }
        })
        .catch(error => {
            console.error('Erro ao carregar pendências:', error);
        });
}

/**
 * Carrega as atividades recentes
 */
function carregarAtividadesRecentes() {
    console.log('Carregando atividades recentes...');
    fetch('api/dashboard.php?action=atividades_recentes')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const container = document.getElementById('atividades-container');

                // Limpa o container
                container.innerHTML = '';

                // Verifica se há atividades
                if (data.data.length === 0) {
                    container.innerHTML = '<div class="text-center text-gray-500 py-4">Não há atividades recentes.</div>';
                    return;
                }

                // Adiciona as atividades
                data.data.forEach(atividade => {
                    // Formata a data
                    const data = new Date(atividade.data_atividade);
                    const agora = new Date();
                    const diff = agora - data;

                    let dataFormatada = '';
                    if (diff < 60 * 1000) {
                        dataFormatada = 'Agora mesmo';
                    } else if (diff < 60 * 60 * 1000) {
                        const minutos = Math.floor(diff / (60 * 1000));
                        dataFormatada = `Há ${minutos} minuto${minutos > 1 ? 's' : ''}`;
                    } else if (diff < 24 * 60 * 60 * 1000) {
                        const horas = Math.floor(diff / (60 * 60 * 1000));
                        dataFormatada = `Há ${horas} hora${horas > 1 ? 's' : ''}`;
                    } else {
                        dataFormatada = data.toLocaleDateString('pt-BR') + ' às ' + data.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
                    }

                    // Determina o ícone com base no módulo
                    let icone = 'fa-file-alt';
                    let corIcone = 'blue';

                    if (atividade.modulo === 'alunos') {
                        icone = 'fa-user-graduate';
                        corIcone = 'blue';
                    } else if (atividade.modulo === 'matriculas') {
                        icone = 'fa-file-alt';
                        corIcone = 'green';
                    } else if (atividade.modulo === 'documentos') {
                        icone = 'fa-certificate';
                        corIcone = 'yellow';
                    } else if (atividade.modulo === 'usuarios') {
                        icone = 'fa-user';
                        corIcone = 'purple';
                    }

                    // Cria o HTML da atividade
                    const html = `
                        <div class="flex items-start">
                            <div class="relative mr-3">
                                <div class="w-8 h-8 rounded-full bg-${corIcone}-100 flex items-center justify-center">
                                    <i class="fas ${icone} text-${corIcone}-500 text-sm"></i>
                                </div>
                            </div>
                            <div>
                                <p class="text-sm">
                                    <span class="font-medium">${atividade.usuario_nome || 'Sistema'}</span>
                                    <span class="text-gray-600">${atividade.descricao}</span>
                                </p>
                                <p class="text-xs text-gray-500 mt-1">${dataFormatada}</p>
                            </div>
                        </div>
                    `;

                    // Adiciona ao container
                    container.innerHTML += html;
                });
            } else {
                console.error('Erro ao carregar atividades recentes:', data.message);
            }
        })
        .catch(error => {
            console.error('Erro ao carregar atividades recentes:', error);
        });
}

/**
 * Carrega os próximos eventos
 */
function carregarProximosEventos() {
    // Esta função pode ser implementada quando houver uma API para eventos
    // Por enquanto, mantemos os eventos estáticos
}

/**
 * Formata um número para exibição
 *
 * @param {number} numero Número a ser formatado
 * @return {string} Número formatado
 */
function formatarNumero(numero) {
    return numero.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}
