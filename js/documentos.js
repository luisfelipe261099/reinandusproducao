/**
 * Script para gerenciar documentos e modais
 */
document.addEventListener('DOMContentLoaded', function() {
    // Função para fechar o modal de geração de documento
    window.fecharModal = function() {
        // Fecha o modal de geração de documento
        const modalGeracao = document.getElementById('modal-gerar-documento');
        if (modalGeracao) {
            modalGeracao.classList.add('hidden');
        }

        // Fecha o modal de exclusão
        const modalExclusao = document.getElementById('modal-exclusao');
        if (modalExclusao) {
            modalExclusao.classList.add('hidden');
        }

        // Fecha qualquer outro modal com a classe 'modal'
        const modais = document.querySelectorAll('.modal');
        modais.forEach(modal => {
            if (modal.style.display === 'flex' || modal.style.display === 'block') {
                modal.style.display = 'none';
            }
        });
    };

    // Adiciona evento de clique no botão de fechar do modal
    const botoesFechar = document.querySelectorAll('.modal button[onclick="fecharModal()"], .modal .close-modal, .modal .fa-times, button[type="button"][onclick="fecharModal()"]');
    botoesFechar.forEach(botao => {
        botao.addEventListener('click', function(e) {
            e.preventDefault();
            fecharModal();
        });
    });

    // Adiciona evento de clique fora do modal para fechá-lo
    const modais = document.querySelectorAll('.modal, [id^="modal-"]');
    modais.forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                fecharModal();
            }
        });
    });

    // Adiciona evento de tecla Escape para fechar o modal
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            fecharModal();
        }
    });

    // Corrige os links de visualização e download
    function corrigirLinks() {
        // Corrige links de visualização
        const linksVisualizacao = document.querySelectorAll('a[href^="documentos.php?action=visualizar&id="], a#btn-visualizar');
        linksVisualizacao.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const href = this.getAttribute('href');
                if (href) {
                    // Abre o documento em uma nova aba
                    window.open(href, '_blank');
                }
            });
        });

        // Corrige links de download
        const linksDownload = document.querySelectorAll('a[href^="documentos.php?action=download&id="], a#btn-download');
        linksDownload.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const href = this.getAttribute('href');
                if (href) {
                    // Cria um elemento <a> temporário para forçar o download
                    const tempLink = document.createElement('a');
                    tempLink.href = href;
                    tempLink.setAttribute('download', '');
                    tempLink.setAttribute('target', '_blank');
                    document.body.appendChild(tempLink);
                    tempLink.click();
                    document.body.removeChild(tempLink);
                }
            });
        });
    }

    // Executa a correção de links quando o DOM estiver pronto
    corrigirLinks();

    // Executa a correção de links quando o conteúdo do modal for atualizado
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                corrigirLinks();
            }
        });
    });

    const modalConteudo = document.getElementById('modal-conteudo');
    if (modalConteudo) {
        observer.observe(modalConteudo, { childList: true, subtree: true });
    }
});
