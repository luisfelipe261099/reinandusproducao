<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizar Documento - <?php echo $dados_documento['aluno_nome']; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../css/tailwind.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f9fafb;
        }
        .document-iframe {
            width: 100%;
            height: 800px;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            background-color: white;
        }
        .document-header {
            background-color: white;
            border-bottom: 1px solid #e5e7eb;
            padding: 1rem;
            border-top-left-radius: 0.375rem;
            border-top-right-radius: 0.375rem;
        }
        .document-container {
            background-color: white;
            border-radius: 0.375rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            margin-bottom: 2rem;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 500;
            font-size: 0.875rem;
            transition: all 0.2s;
        }
        .btn-white {
            background-color: white;
            color: #4b5563;
            border: 1px solid #e5e7eb;
        }
        .btn-white:hover {
            background-color: #f9fafb;
        }
        .btn-primary {
            background-color: var(--roxo-faciencia, #6366f1);
            color: white;
        }
        .btn-primary:hover {
            background-color: var(--roxo-escuro, #4f46e5);
        }
    </style>
</head>
<body>
    <div class="container mx-auto py-8 px-4">
        <div class="document-container">
            <div class="document-header flex justify-between items-center">
                <div>
                    <h1 class="text-xl font-semibold text-gray-800"><?php echo $dados_documento['tipo_documento_nome'] ?? 'Documento'; ?></h1>
                    <p class="text-sm text-gray-500">
                        Aluno: <?php echo $dados_documento['aluno_nome']; ?> |
                        Curso: <?php echo $dados_documento['curso_nome']; ?> |
                        Emitido em: <?php echo date('d/m/Y', strtotime($dados_documento['data_emissao'])); ?>
                    </p>
                </div>
                <div>
                    <a href="documentos.php?action=download&id=<?php echo $documento_id; ?>" class="btn btn-primary" id="btn-download">
                        <i class="fas fa-download mr-2"></i> Baixar Documento
                    </a>
                </div>
            </div>

            <div class="p-6">
                <div class="bg-blue-50 p-6 rounded-lg mb-6 border border-blue-200 text-center">
                    <p><i class="fas fa-file-pdf text-blue-500 text-5xl mb-4"></i></p>
                    <h3 class="text-xl font-semibold mb-3 text-blue-800">Documento Disponível para Download</h3>
                    <p class="text-md text-blue-700 mb-4">O documento está pronto para ser baixado. Clique no botão "Baixar Documento" acima para fazer o download do arquivo PDF.</p>
                    <div class="mt-4">
                        <a href="documentos.php?action=download&id=<?php echo $documento_id; ?>" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg text-lg inline-flex items-center" id="btn-download-large">
                            <i class="fas fa-download mr-2"></i> Baixar Documento Agora
                        </a>
                    </div>
                </div>
            </div>

            <div class="p-4 bg-gray-50 border-t border-gray-200">
                <div class="flex justify-between items-center">
                    <div class="text-sm text-gray-500">
                        <p>Código de verificação: <?php echo $dados_documento['codigo_verificacao']; ?></p>
                    </div>
                    <a href="documentos.php" class="btn btn-white">
                        <i class="fas fa-arrow-left mr-2"></i> Voltar
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Configura os botões de download para funcionar corretamente
        document.addEventListener('DOMContentLoaded', function() {
            // Configura todos os botões de download
            const downloadButtons = document.querySelectorAll('#btn-download, #btn-download-large');

            downloadButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();

                    // Obtém o URL de download
                    const downloadUrl = this.getAttribute('href');

                    // Cria um elemento <a> temporário para forçar o download
                    const tempLink = document.createElement('a');
                    tempLink.href = downloadUrl;
                    tempLink.setAttribute('download', '');
                    tempLink.setAttribute('target', '_blank');
                    document.body.appendChild(tempLink);
                    tempLink.click();
                    document.body.removeChild(tempLink);
                });
            });

            // Inicia o download automaticamente após 1 segundo
            setTimeout(function() {
                const mainDownloadButton = document.getElementById('btn-download-large');
                if (mainDownloadButton) {
                    mainDownloadButton.click();
                }
            }, 1000);
        });
    </script>
</body>
</html>
