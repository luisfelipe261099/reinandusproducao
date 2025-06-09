<?php
/**
 * Função para gerar um documento em PDF
 *
 * @param array $documento Informações do documento
 * @param string $tipo Tipo de documento (historico, declaracao)
 * @return string Caminho do arquivo PDF gerado
 */
function gerarPDF($documento, $tipo = 'historico') {
    // Log para depuração
    error_log('Iniciando geração de PDF do tipo: ' . $tipo);
    error_log('Documento recebido: ' . json_encode($documento));

    // Verifica se o documento existe
    if (empty($documento) || !isset($documento['dados_documento'])) {
        error_log('Documento inválido: ' . json_encode($documento));
        throw new Exception("Documento inválido para geração de PDF.");
    }

    // Decodifica os dados do documento
    $dados = is_string($documento['dados_documento']) ?
             json_decode($documento['dados_documento'], true) :
             $documento['dados_documento'];

    if (empty($dados)) {
        error_log('Dados do documento vazios após decodificação');
        throw new Exception("Dados do documento inválidos.");
    }

    error_log('Dados decodificados: ' . json_encode($dados));

    // Define o caminho do arquivo PDF - usando caminho relativo simples
    $diretorio_upload = 'uploads/documentos/';
    $diretorio_upload_absoluto = __DIR__ . '/../../' . $diretorio_upload;
    error_log('Diretório de upload (relativo): ' . $diretorio_upload);
    error_log('Diretório de upload (absoluto): ' . $diretorio_upload_absoluto);

    // Garante que o diretório de upload tenha uma barra no final
    if (substr($diretorio_upload, -1) !== '/') {
        $diretorio_upload .= '/';
    }
    if (substr($diretorio_upload_absoluto, -1) !== '/') {
        $diretorio_upload_absoluto .= '/';
    }

    // Verifica se o diretório existe e tenta criá-lo se não existir
    if (!is_dir($diretorio_upload_absoluto)) {
        error_log('Diretório não existe, tentando criar: ' . $diretorio_upload_absoluto);
        try {
            if (!mkdir($diretorio_upload_absoluto, 0755, true)) {
                $erro = error_get_last();
                error_log('ERRO ao criar diretório: ' . ($erro ? $erro['message'] : 'Desconhecido'));
                throw new Exception("Não foi possível criar o diretório para salvar o documento.");
            }
            error_log('Diretório criado com sucesso');
        } catch (Exception $e) {
            error_log('Exceção ao criar diretório: ' . $e->getMessage());
            // Tenta usar um diretório alternativo
            $diretorio_upload = 'temp/';
            $diretorio_upload_absoluto = __DIR__ . '/../../' . $diretorio_upload;
            error_log('Tentando diretório alternativo: ' . $diretorio_upload_absoluto);

            if (!is_dir($diretorio_upload_absoluto)) {
                if (!mkdir($diretorio_upload_absoluto, 0755, true)) {
                    throw new Exception("Não foi possível criar o diretório alternativo.");
                }
            }
        }
    }

    // Verifica permissões
    if (!is_writable($diretorio_upload_absoluto)) {
        error_log('ERRO: Diretório sem permissão de escrita: ' . $diretorio_upload_absoluto);
        // Tenta corrigir as permissões
        try {
            chmod($diretorio_upload_absoluto, 0755);
            error_log('Tentativa de corrigir permissões do diretório');

            if (!is_writable($diretorio_upload_absoluto)) {
                // Se ainda não for gravável, tenta um diretório alternativo
                $diretorio_upload = 'temp/';
                $diretorio_upload_absoluto = __DIR__ . '/../../' . $diretorio_upload;
                error_log('Tentando diretório alternativo: ' . $diretorio_upload_absoluto);

                if (!is_dir($diretorio_upload_absoluto)) {
                    if (!mkdir($diretorio_upload_absoluto, 0755, true)) {
                        throw new Exception("Não foi possível criar o diretório alternativo.");
                    }
                }

                if (!is_writable($diretorio_upload_absoluto)) {
                    throw new Exception("Sem permissão de escrita no diretório de documentos.");
                }
            }
        } catch (Exception $e) {
            error_log('Exceção ao corrigir permissões: ' . $e->getMessage());
            throw $e;
        }
    }
    error_log('Diretório tem permissão de escrita: ' . $diretorio_upload_absoluto);

    $nome_arquivo = sanitizarNomeArquivo($tipo . '_' . ($dados['aluno_nome'] ?? 'documento') . '_' . date('Y-m-d'));
    $arquivo_nome = uniqid() . '_' . $nome_arquivo . '.html';

    // Armazena o caminho relativo para o banco de dados - garantindo que seja apenas o caminho relativo
    $arquivo_html = $diretorio_upload . $arquivo_nome;
    // Caminho absoluto para operações de arquivo
    $arquivo_html_absoluto = $diretorio_upload_absoluto . $arquivo_nome;

    // Define variáveis globais para uso em outras funções
    global $arquivo_html_absoluto;

    // Garante que o caminho salvo no banco de dados seja sempre relativo
    // Remove qualquer caminho absoluto que possa ter sido adicionado
    $arquivo_html = preg_replace('/^(\/.*\/public_html\/[^\/]+\/)?/', '', $arquivo_html);

    error_log('Arquivo HTML a ser gerado (caminho relativo limpo): ' . $arquivo_html);
    error_log('Arquivo HTML a ser gerado (caminho absoluto): ' . $arquivo_html_absoluto);

    // Inicia o buffer de saída
    ob_start();

    // Gera o HTML do documento com base no tipo
    if ($tipo == 'historico') {
        error_log('Gerando HTML do histórico escolar');
        gerarHTMLHistorico($dados);
    } elseif ($tipo == 'declaracao') {
        error_log('Gerando HTML da declaração');
        gerarHTMLDeclaracao($dados);
    } else {
        throw new Exception("Tipo de documento não suportado para geração de PDF.");
    }

    // Obtém o conteúdo do buffer
    $html = ob_get_clean();
    error_log('HTML gerado com ' . strlen($html) . ' caracteres');

    if (empty($html)) {
        error_log('ALERTA: HTML gerado está vazio!');
    }

    // Usa a abordagem simples para gerar o documento
    try {
        $resultado = gerarPDFSimples($html, $arquivo_html);
        error_log('Documento gerado com sucesso: ' . $resultado);
        return $resultado;
    } catch (Exception $e) {
        error_log('Erro ao gerar documento: ' . $e->getMessage());
        throw $e;
    }
}

/**
 * Função para gerar um documento HTML que pode ser impresso como PDF
 */
function gerarPDFSimples($html, $arquivo_html) {
    global $arquivo_html_absoluto; // Usa a variável global para o caminho absoluto

    // Log para depuração
    error_log('Iniciando gerarPDFSimples');
    error_log('Tamanho do HTML: ' . strlen($html) . ' caracteres');
    error_log('Arquivo de destino (relativo): ' . $arquivo_html);
    error_log('Arquivo de destino (absoluto): ' . $arquivo_html_absoluto);

    // Adiciona estilos e scripts para melhorar a aparência e facilitar a impressão
    $html_completo = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Documento</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                font-size: 12pt;
                line-height: 1.5;
                margin: 2cm;
                color: #333;
                background-color: #fff;
                position: relative;
            }

            /* Marca dagua */
            .watermark {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                text-align: center;
                opacity: 0.1;
                z-index: -1;
                pointer-events: none;
                transform: rotate(-45deg);
                font-size: 60px;
                color: #f0f0f0;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            table {
                width: 100%;
                border-collapse: collapse;
                margin: 20px 0;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            }

            table, th, td {
                border: 1px solid #ddd;
            }

            th, td {
                padding: 12px;
                text-align: left;
            }

            th {
                background-color: #6a0dad;
                color: white;
                font-weight: 500;
            }

            /* Cabeçalho do documento */
            .header {
                text-align: center;
                margin-bottom: 40px;
                border-bottom: 2px solid #6a0dad;
                padding-bottom: 20px;
                position: relative;
            }

            .header img {
                max-height: 80px;
                margin-bottom: 15px;
            }

            .header h1 {
                color: #6a0dad;
                font-size: 22pt;
                font-weight: 700;
                margin: 0 0 5px 0;
                text-transform: uppercase;
            }

            .header p {
                color: #555;
                font-size: 10pt;
                margin: 5px 0;
            }

            .header h2 {
                font-size: 18pt;
                font-weight: 700;
                margin: 25px 0 0 0;
                color: #333;
                text-transform: uppercase;
                letter-spacing: 2px;
                border-top: 1px solid #ddd;
                padding-top: 15px;
            }

            /* Conteúdo do documento */
            .content {
                text-align: justify;
                margin: 30px 0;
                line-height: 1.8;
                font-size: 12pt;
            }

            .content p {
                margin: 15px 0;
            }

            .content strong {
                font-weight: 700;
                color: #6a0dad;
            }

            /* Rodapé do documento */
            .footer {
                text-align: center;
                margin-top: 50px;
                font-size: 10pt;
                color: #555;
                border-top: 1px solid #ddd;
                padding-top: 20px;
            }

            .signature {
                margin-top: 60px;
                text-align: center;
            }

            .signature img {
                max-height: 60px;
                margin-bottom: 10px;
            }

            .signature p {
                margin: 5px 0;
            }

            .signature .line {
                width: 250px;
                border-bottom: 1px solid #333;
                margin: 10px auto;
            }

            /* QR Code */
            .qrcode {
                text-align: center;
                margin-top: 30px;
            }

            .qrcode img {
                width: 100px;
                height: 100px;
            }

            .qrcode p {
                font-size: 8pt;
                color: #666;
                margin-top: 5px;
            }

            /* Informações de contato */
            .contact-info {
                position: fixed;
                bottom: 1cm;
                left: 0;
                right: 0;
                text-align: center;
                font-size: 8pt;
                color: #666;
                border-top: 1px solid #eee;
                padding-top: 10px;
            }

            /* Configurações de impressão */
            .page-break {
                page-break-after: always;
            }

            @media print {
                body {
                    margin: 0;
                    padding: 2cm;
                }
                @page {
                    size: A4 portrait;
                    margin: 2cm;
                    /* Remover cabeçalhos e rodapés de impressão */
                    margin-header: 0;
                    margin-footer: 0;
                }
                /* Ocultar URL, data e outros elementos de impressão */
                @page :first {
                    margin-top: 0;
                }
            }

            /* Botão de impressão */
            .print-button {
                display: block;
                margin: 20px auto;
                padding: 12px 24px;
                background-color: #6a0dad;
                color: white;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-size: 14px;
                font-weight: 500;
                transition: background-color 0.3s ease;
            }

            .print-button:hover {
                background-color: #4b0973;
            }

            @media print {
                .print-button {
                    display: none;
                }
            }
        </style>
        <script>
            function printDocument() {
                window.print();
            }
        </script>
    </head>
    <body>
        <button class="print-button" onclick="printDocument()">Imprimir Documento</button>
        ' . $html . '
    </body>
    </html>';

    // Verifica se a variável global está definida
    if (!isset($arquivo_html_absoluto) || empty($arquivo_html_absoluto)) {
        error_log('ALERTA: Variável arquivo_html_absoluto não definida, usando caminho relativo');
        // Tenta construir o caminho absoluto
        $arquivo_html_absoluto = __DIR__ . '/../../' . $arquivo_html;
    }

    error_log('Tentando salvar arquivo em: ' . $arquivo_html_absoluto);

    // Tenta salvar o arquivo
    try {
        // Salva o HTML no arquivo usando o caminho absoluto
        $resultado = file_put_contents($arquivo_html_absoluto, $html_completo);

        if ($resultado === false) {
            error_log('Erro ao salvar o arquivo HTML: ' . $arquivo_html_absoluto);

            // Tenta salvar em um local alternativo
            $arquivo_alternativo = __DIR__ . '/../../temp/' . basename($arquivo_html);
            error_log('Tentando salvar em local alternativo: ' . $arquivo_alternativo);

            // Cria o diretório se não existir
            $dir_alternativo = dirname($arquivo_alternativo);
            if (!is_dir($dir_alternativo)) {
                mkdir($dir_alternativo, 0755, true);
            }

            $resultado = file_put_contents($arquivo_alternativo, $html_completo);

            if ($resultado === false) {
                throw new Exception("Não foi possível salvar o documento HTML em nenhum local.");
            }

            // Atualiza o caminho relativo para o novo local
            $arquivo_html = 'temp/' . basename($arquivo_html);
            error_log('Arquivo salvo com sucesso no local alternativo. Novo caminho relativo: ' . $arquivo_html);
        } else {
            error_log('Arquivo HTML salvo com sucesso: ' . $arquivo_html_absoluto);
        }
    } catch (Exception $e) {
        error_log('Exceção ao salvar arquivo: ' . $e->getMessage());
        throw $e;
    }

    // Retorna o caminho relativo para armazenar no banco de dados
    return $arquivo_html;
}

/**
 * Função para gerar o HTML do histórico escolar
 */
function gerarHTMLHistorico($dados) {
    error_log('Iniciando gerarHTMLHistorico com dados: ' . json_encode($dados));

    // Inclui o arquivo com a função simplificada
    require_once __DIR__ . '/gerar_historico_simples.php';

    // Usa a função simplificada para gerar o HTML
    echo gerarHistoricoSimples($dados);

    error_log('Finalizando gerarHTMLHistorico');
}

/**
 * Função para sanitizar o nome de um arquivo
 */
function sanitizarNomeArquivo($nome) {
    // Remove acentos
    $nome = preg_replace('/[áàãâä]/ui', 'a', $nome);
    $nome = preg_replace('/[éèêë]/ui', 'e', $nome);
    $nome = preg_replace('/[íìîï]/ui', 'i', $nome);
    $nome = preg_replace('/[óòõôö]/ui', 'o', $nome);
    $nome = preg_replace('/[úùûü]/ui', 'u', $nome);
    $nome = preg_replace('/[ç]/ui', 'c', $nome);

    // Remove caracteres especiais
    $nome = preg_replace('/[^a-z0-9_\-\.]/i', '_', $nome);

    // Remove underscores duplicados
    $nome = preg_replace('/_+/', '_', $nome);

    return $nome;
}

/**
 * Função para gerar o HTML da declaração de matrícula
 */
function gerarHTMLDeclaracao($dados) {
    error_log('Iniciando gerarHTMLDeclaracao com dados: ' . json_encode($dados));

    // Inclui o arquivo com a função simplificada
    require_once __DIR__ . '/gerar_declaracao_simples.php';

    // Usa a função simplificada para gerar o HTML
    echo gerarDeclaracaoSimples($dados);

    error_log('Finalizando gerarHTMLDeclaracao');
}
?>
