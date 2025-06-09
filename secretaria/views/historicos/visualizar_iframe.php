<?php
/**
 * View para visualizar documentos com iframe
 */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faciência ERP - <?php echo $titulo_pagina; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        /* Estilos específicos para a página de visualização de documentos */
        :root {
            --roxo-faciencia: #6a1b9a;
            --roxo-claro: #9c4dcc;
            --roxo-escuro: #38006b;
            --cinza-claro: #f5f5f5;
        }

        .document-container {
            max-width: 1000px;
            margin: 0 auto;
            background-color: white;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
        }

        .document-header {
            background-color: var(--roxo-faciencia);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .document-header h2 {
            margin: 0;
            font-size: 18px;
        }

        .document-iframe {
            width: 100%;
            height: 800px;
            border: none;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 8px 15px;
            border-radius: 4px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .btn-white {
            background-color: white;
            color: var(--roxo-faciencia);
        }

        .btn-white:hover {
            background-color: #f0f0f0;
        }

        .btn-secondary {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .btn-secondary:hover {
            background-color: rgba(255, 255, 255, 0.3);
        }

        .btn i {
            margin-right: 8px;
        }

        @media (max-width: 768px) {
            .document-iframe {
                height: 600px;
            }

            .action-buttons {
                flex-wrap: wrap;
            }

            .btn {
                font-size: 12px;
                padding: 6px 12px;
            }
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <?php include 'includes/header.php'; ?>

            <!-- Main -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
                <div class="container mx-auto">
                    <div class="flex justify-between items-center mb-6">
                        <h1 class="text-2xl font-bold text-gray-800"><?php echo $titulo_pagina; ?></h1>

                        <div class="action-buttons">
                            <a href="documentos.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Voltar
                            </a>
                        </div>
                    </div>

                    <div class="document-container">
                        <div class="document-header">
                            <h2>
                                <?php
                                if (strpos(strtolower($documento['tipo_documento_nome'] ?? ''), 'histórico') !== false) {
                                    echo 'Histórico Acadêmico';
                                } else {
                                    echo 'Declaração de Matrícula';
                                }
                                ?> - <?php echo htmlspecialchars($documento['aluno_nome'] ?? ''); ?>
                            </h2>

                            <div class="action-buttons">
                                <a href="documentos.php?action=download&id=<?php echo $documento_id; ?>" class="btn btn-white">
                                    <i class="fas fa-download"></i> Baixar
                                </a>
                                <button onclick="printIframe()" class="btn btn-secondary">
                                    <i class="fas fa-print"></i> Imprimir
                                </button>
                            </div>
                        </div>

                        <?php if (isset($dados_documento['arquivo_encontrado']) && !$dados_documento['arquivo_encontrado']): ?>
                        <div class="bg-yellow-50 p-4 rounded-lg mb-6 border border-yellow-200 text-center">
                            <p><i class="fas fa-exclamation-triangle text-yellow-500 text-3xl mb-3"></i></p>
                            <h3 class="text-lg font-semibold mb-2 text-yellow-800">Arquivo não encontrado</h3>
                            <p class="text-sm text-yellow-700 mb-4">O arquivo do documento não foi encontrado no servidor. Você pode tentar baixá-lo usando o botão acima ou solicitar a regeneração do documento.</p>
                        </div>
                        <?php else: ?>
                        <iframe id="document-frame" class="document-iframe" src="visualizar_documento.php?id=<?php echo $documento_id; ?>&formato=raw&t=<?php echo time(); ?>" onload="checkIframeLoaded()"></iframe>
                        <?php endif; ?>

                        <!-- Mensagem de carregamento -->
                        <div id="loading-message" style="display: none; text-align: center; padding: 20px; font-size: 16px;">
                            <p><i class="fas fa-spinner fa-spin" style="font-size: 24px; color: var(--roxo-faciencia); margin-bottom: 10px;"></i></p>
                            <p>Carregando documento...</p>
                        </div>

                        <!-- Mensagem de erro -->
                        <div id="error-message" style="display: none; text-align: center; padding: 20px; background-color: #fff3f3; border-left: 4px solid #d32f2f; margin: 20px;">
                            <p><i class="fas fa-exclamation-triangle" style="font-size: 24px; color: #d32f2f; margin-bottom: 10px;"></i></p>
                            <p>Não foi possível carregar o documento no iframe. Por favor, tente uma das opções abaixo:</p>
                            <div style="display: flex; justify-content: center; gap: 10px; margin-top: 15px;">
                                <a href="documentos.php?action=download&id=<?php echo $documento_id; ?>" class="btn btn-white" style="background-color: var(--roxo-faciencia); color: white;">
                                    <i class="fas fa-download"></i> Baixar Documento
                                </a>
                                <a href="visualizar_documento.php?id=<?php echo $documento_id; ?>&formato=raw&t=<?php echo time(); ?>" target="_blank" class="btn btn-white" style="background-color: var(--roxo-claro); color: white;">
                                    <i class="fas fa-external-link-alt"></i> Abrir em Nova Aba
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            <!-- Footer -->
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>

    <script>
        // Mostra a mensagem de carregamento ao iniciar
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('loading-message').style.display = 'block';
            document.getElementById('document-frame').style.display = 'none';
        });

        // Verifica se o iframe carregou corretamente
        function checkIframeLoaded() {
            const iframe = document.getElementById('document-frame');
            const loadingMessage = document.getElementById('loading-message');
            const errorMessage = document.getElementById('error-message');

            try {
                // Tenta acessar o conteúdo do iframe
                const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;

                // Verifica se o iframe carregou corretamente
                if (!iframeDoc || !iframeDoc.body) {
                    throw new Error('Iframe sem documento ou corpo');
                }

                // Verifica se o conteúdo está vazio
                const content = iframeDoc.body.innerHTML.trim();
                if (content === '') {
                    throw new Error('Conteúdo do iframe vazio');
                }

                // Verifica se há mensagem de erro no conteúdo
                if (content.includes('Erro ao ler o documento') ||
                    content.includes('Documento não encontrado') ||
                    content.includes('Arquivo não encontrado')) {
                    throw new Error('Mensagem de erro detectada no iframe');
                }

                // Se chegou até aqui, o iframe carregou com sucesso
                console.log('Iframe carregado com sucesso');

                // Esconde a mensagem de carregamento
                loadingMessage.style.display = 'none';

                // Mostra o iframe
                iframe.style.display = 'block';

                // Tenta ajustar a altura do iframe para o conteúdo
                try {
                    const height = iframeDoc.body.scrollHeight;
                    if (height > 300) {
                        iframe.style.height = (height + 50) + 'px';
                    }
                } catch (heightError) {
                    console.warn('Não foi possível ajustar a altura do iframe:', heightError);
                }
            } catch (e) {
                // Se ocorrer um erro ao acessar o iframe
                console.error('Erro ao verificar iframe:', e);

                // Tenta carregar o documento diretamente
                const directUrl = iframe.src;

                // Faz uma verificação adicional
                fetch(directUrl)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.text();
                    })
                    .then(text => {
                        if (text.trim() !== '' &&
                            !text.includes('Erro ao ler o documento') &&
                            !text.includes('Documento não encontrado') &&
                            !text.includes('Arquivo não encontrado')) {

                            // O conteúdo parece válido, tenta recarregar o iframe
                            iframe.src = directUrl + '&retry=' + new Date().getTime();

                            // Mantém a mensagem de carregamento
                            return;
                        }

                        // Se chegou aqui, o conteúdo não é válido
                        throw new Error('Conteúdo inválido');
                    })
                    .catch(fetchError => {
                        console.error('Erro ao buscar documento diretamente:', fetchError);

                        // Esconde o iframe e a mensagem de carregamento
                        iframe.style.display = 'none';
                        loadingMessage.style.display = 'none';

                        // Mostra a mensagem de erro
                        errorMessage.style.display = 'block';
                    });
            }
        }

        // Função para imprimir o conteúdo do iframe
        function printIframe() {
            const iframe = document.getElementById('document-frame');
            if (iframe.style.display !== 'none') {
                iframe.contentWindow.print();
            } else {
                alert('Não é possível imprimir o documento. Por favor, baixe-o primeiro.');
            }
        }
    </script>
    <script src="js/main.js"></script>
</body>
</html>
