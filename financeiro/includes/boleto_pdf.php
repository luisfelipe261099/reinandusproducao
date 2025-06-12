<?php
/**
 * Classe para gerar PDFs de boletos bancários
 */

// Inclui o autoload do Composer para carregar a biblioteca de código de barras e DomPDF
// $autoloaderPath = __DIR__ . '/../../vendor/autoload.php'; // Commented out, as it's now loaded in init.php
// if (file_exists($autoloaderPath)) { // Commented out
//     require_once $autoloaderPath; // Commented out
//     error_log("[BoletoPDF] Composer autoloader included from: " . $autoloaderPath); // Commented out
// } else { // Commented out
//     error_log("[BoletoPDF] CRITICAL: Composer autoloader NOT FOUND at: " . $autoloaderPath . ". This will cause class not found errors."); // Commented out
// } // Commented out

use Picqer\Barcode\BarcodeGeneratorPNG;

class BoletoPDF {
    private $boleto;
    
    public function __construct($boleto) {
        $this->boleto = $boleto;
    }
    
    /**
     * Gera o HTML do boleto para conversão em PDF
     */
    public function gerarHTML() {
        // Formata os dados
        $valor = number_format($this->boleto['valor'], 2, ',', '.');
        $data_vencimento = date('d/m/Y', strtotime($this->boleto['data_vencimento']));
        $data_emissao = isset($this->boleto['data_emissao']) ? date('d/m/Y', strtotime($this->boleto['data_emissao'])) : date('d/m/Y');
        $data_processamento = date('d/m/Y');

        $linha_digitavel = $this->formatarLinhaDigitavel($this->boleto['linha_digitavel'] ?? '');
        $codigo_barras_numerico = $this->boleto['codigo_barras'] ?? '';        // Gera o código de barras em Base64
        $barcode_image_base64 = '';
        if (!empty($codigo_barras_numerico)) {
            error_log("[BoletoPDF] Attempting to generate barcode. Checking class Picqer\Barcode\BarcodeGeneratorPNG existence: " . (class_exists('Picqer\Barcode\BarcodeGeneratorPNG') ? 'Exists' : 'NOT FOUND'));
            try {
                $generator = new BarcodeGeneratorPNG();
                // Using BarcodeGeneratorPNG::TYPE_CODE_128 for clarity and robustness
                $barcode_image_data = $generator->getBarcode($codigo_barras_numerico, BarcodeGeneratorPNG::TYPE_CODE_128, 2, 50);
                $barcode_image_base64 = 'data:image/png;base64,' . base64_encode($barcode_image_data);
                error_log("[BoletoPDF] Barcode generated successfully for: " . $codigo_barras_numerico . " - Size: " . strlen($barcode_image_data) . " bytes");
            } catch (Throwable $e) { // Catch Throwable for broader error catching, including Error objects like class not found
                error_log("[BoletoPDF] CRITICAL ERROR generating barcode: " . $e->getMessage() . " on line " . $e->getLine() . " in " . $e->getFile());
                error_log("[BoletoPDF] Barcode Throwable Stack Trace: " . $e->getTraceAsString());
                $barcode_image_base64 = ''; // Deixa em branco se houver erro
            }
        } else {
            error_log("[BoletoPDF] Numeric barcode data is empty, cannot generate barcode image.");
        }
        
        // Se não conseguiu gerar o código de barras, vamos criar um fallback visual
        if (empty($barcode_image_base64) && !empty($codigo_barras_numerico)) {
            error_log("[BoletoPDF] Creating fallback text display for barcode: " . $codigo_barras_numerico);
            $barcode_image_base64 = ''; // Manterá vazio para mostrar apenas o texto
        }$nome_beneficiario = "FACULDADE FACIENCIA"; 
        $cnpj_beneficiario = "09.038.742/0001-80"; // CNPJ Atualizado
        $agencia_codigo_beneficiario = "AG XXXX / CC XXXXX-X"; // Substituir pela Agência/Conta real
        
        // Logo do Itaú como base64 (embutido para evitar problemas de rede)
        $logo_itau_base64 = "data:image/png;base64,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";

        $html = <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Boleto Bancário - {$this->boleto['nosso_numero']}</title>
    <style>
        @page { 
            margin: 5mm 8mm 5mm 8mm; /* Margens: topo, direita, baixo, esquerda */
        }
        body { 
            font-family: 'Arial', sans-serif; 
            font-size: 9pt; 
            margin: 0; 
            padding: 0; 
            color: #000; 
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .boleto-container { 
            width: 100%; 
            max-width: 185mm; /* Ajustado para caber melhor em A4 com margens */
            margin: 0 auto; 
            border: none;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            table-layout: fixed; 
        }
        td, th { 
            border: 1px solid #000; 
            padding: 2px 3px; /* Padding ajustado */
            vertical-align: top; 
            font-size: 7.5pt; /* Tamanho de fonte base para células */
            word-wrap: break-word;
        }
        .header-banco { height: 38px; }
        .logo-banco { 
            width: 100px; 
            text-align: center;
            padding: 1px;
        }
        .logo-banco img {
            max-width: 90px; 
            max-height: 32px;
            margin-top: 2px;
        }
        .codigo-banco-dv { 
            font-size: 11pt; 
            font-weight: bold; 
            text-align: center; 
            border-left: 2px solid #000 !important; 
            border-right: 2px solid #000 !important;
            width: 55px; 
        }
        .linha-digitavel-header { 
            font-size: 9.5pt; 
            font-weight: bold; 
            text-align: right; 
            padding-right: 4px; 
            letter-spacing: 0.5px;
        }
        .label { 
            font-size: 6.5pt; 
            color: #222; 
            margin-bottom: 0; 
            display: block; 
            line-height: 1;
        }
        .value { 
            font-size: 8pt; 
            font-weight: bold; 
            line-height: 1.1;
            padding-top: 1px; /* Pequeno espaço acima do valor */
        }
         .value-small { /* Para valores que precisam ser menores */
            font-size: 7.5pt;
            font-weight: normal;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .cut-line { 
            border-top: 1px dashed #000; 
            margin: 4mm 0; 
            text-align: right; 
            font-size: 6.5pt; 
            padding-right: 5mm;
        }
        .barcode-area { 
            height: 55px; 
            text-align: center; 
            padding: 2px 0 0 0; /* Reduzido padding superior */
            margin-top: 2px; /* Espaço antes do código de barras */
        }
        .barcode-area img {
            max-width: 98%; /* Para não encostar nas bordas da célula */
            height: 45px; 
            display: block; /* Para centralizar com margin auto */
            margin: 0 auto;
        }
        .instrucoes { 
            font-size: 7pt; 
            padding: 1px 2px;
            line-height: 1.2;
        }
        .small-text { font-size: 7pt; line-height: 1.1; }

        /* Ajustes de colunas */
        .col-vencimento { width: 85px; }
        .col-ag-cod-cedente { width: 115px; }
        .col-data-doc { width: 60px; }
        .col-num-doc { width: 80px; }
        .col-especie-doc { width: 35px; }
        .col-aceite { width: 30px; }
        .col-data-proc { width: 60px; }
        .col-nosso-numero { width: 90px; }
        .col-uso-banco { width: 70px; }
        .col-carteira { width: 45px; }
        .col-moeda { width: 35px; }
        .col-qtd-moeda { width: 70px; }
        .col-valor-doc { width: 90px; }
        .col-descontos { width: 90px; } /* Coluna para descontos e outros valores à direita */

    </style>
</head>
<body>
    <div class="boleto-container">        <!-- Recibo do Sacado -->
        <table>
            <tr>
                <td class="logo-banco header-banco" style="border-right: 2px solid #000 !important;">
                    <img src="{$logo_itau_base64}" alt="Itaú">
                </td>
                <td class="codigo-banco-dv header-banco">341-7</td>
                <td class="linha-digitavel-header header-banco" colspan="4">{$linha_digitavel}</td>
            </tr>
            <tr>
                <td colspan="5">
                    <span class="label">Local de Pagamento</span>
                    <span class="value">PAGÁVEL PREFERENCIALMENTE NAS AGÊNCIAS DO ITAÚ OU QUALQUER BANCO ATÉ O VENCIMENTO</span>
                </td>
                <td class="col-vencimento">
                    <span class="label">Vencimento</span>
                    <span class="value text-right">{$data_vencimento}</span>
                </td>
            </tr>
            <tr>
                <td colspan="5">
                    <span class="label">Beneficiário</span>
                    <span class="value">{$nome_beneficiario} - CNPJ: {$cnpj_beneficiario}</span>
                </td>
                <td class="col-ag-cod-cedente">
                    <span class="label">Agência/Código Beneficiário</span>
                    <span class="value text-right">{$agencia_codigo_beneficiario}</span>
                </td>
            </tr>
            <tr>
                <td class="col-data-doc">
                    <span class="label">Data do Documento</span>
                    <span class="value">{$data_emissao}</span>
                </td>
                <td class="col-num-doc">
                    <span class="label">Nº do Documento</span>
                    <span class="value">{$this->boleto['id']}</span>
                </td>
                <td class="col-especie-doc">
                    <span class="label">Espécie Doc.</span>
                    <span class="value">DM</span>
                </td>
                <td class="col-aceite">
                    <span class="label">Aceite</span>
                    <span class="value">N</span>
                </td>
                <td class="col-data-proc">
                    <span class="label">Data Processamento</span>
                    <span class="value">{$data_processamento}</span>
                </td>
                <td class="col-nosso-numero">
                    <span class="label">Nosso Número</span>
                    <span class="value text-right">{$this->boleto['nosso_numero']}</span>
                </td>
            </tr>
            <tr>
                <td class="col-uso-banco">
                     <span class="label">Uso do Banco</span>
                     <span class="value">&nbsp;</span>
                </td>
                <td class="col-carteira">
                    <span class="label">Carteira</span>
                    <span class="value">109</span>
                </td>
                <td class="col-moeda">
                    <span class="label">Espécie Moeda</span>
                    <span class="value">R$</span>
                </td>
                <td class="col-qtd-moeda">
                    <span class="label">Quantidade Moeda</span>
                    <span class="value">&nbsp;</span>
                </td>
                <td class="col-valor-doc" colspan="2">
                    <span class="label">(=) Valor do Documento</span>
                    <span class="value text-right">R$ {$valor}</span>
                </td>
            </tr>
            <tr>
                <td colspan="4" rowspan="5" style="vertical-align: top;">
                    <span class="label">Instruções (Texto de responsabilidade do BENEFICIÁRIO)</span>
                    <div class="instrucoes">
                        Após o vencimento, cobrar multa de {$this->boleto['multa']}%<br>
                        Após o vencimento, cobrar juros de {$this->boleto['juros']}% ao mês (calculado pró-rata dia)<br>
                        Não receber após 30 (trinta) dias do vencimento.<br>
                        Referente a: {$this->boleto['descricao']}
                    </div>
                </td>
                <td class="col-descontos" colspan="2">
                    <span class="label">(-) Desconto / Abatimento</span>
                    <span class="value text-right">&nbsp;</span>
                </td>
            </tr>
            <tr><td class="col-descontos" colspan="2"><span class="label">(-) Outras Deduções</span><span class="value text-right">&nbsp;</span></td></tr>
            <tr><td class="col-descontos" colspan="2"><span class="label">(+) Mora / Multa</span><span class="value text-right">&nbsp;</span></td></tr>
            <tr><td class="col-descontos" colspan="2"><span class="label">(+) Outros Acréscimos</span><span class="value text-right">&nbsp;</span></td></tr>
            <tr>
                <td class="col-descontos" colspan="2">
                    <span class="label">(=) Valor Cobrado</span>
                    <span class="value text-right">&nbsp;</span>
                </td>
            </tr>
            <tr>
                <td colspan="6">
                    <span class="label">Pagador</span>
                    <span class="value">{$this->boleto['nome_pagador']} - CPF/CNPJ: {$this->boleto['cpf_pagador']}</span><br>
                    <span class="small-text">{$this->boleto['endereco']}, {$this->boleto['bairro']}</span><br>
                    <span class="small-text">{$this->boleto['cidade']}/{$this->boleto['uf']} - CEP: {$this->boleto['cep']}</span>
                </td>
            </tr>
             <tr>
                <td colspan="4" style="border-bottom: none; height: 25px;"> <!-- Altura ajustada -->
                    <span class="label">Sacador/Avalista</span>
                     <span class="value">&nbsp;</span>
                </td>
                <td colspan="2" class="text-right" style="border-bottom: none;">
                     <span class="label">Autenticação Mecânica - Recibo do Sacado</span>
                </td>
            </tr>
        </table>
        <div class="cut-line">Destacar aqui para autenticação</div>        <!-- Ficha de Compensação -->
        <table>
             <tr>
                <td class="logo-banco header-banco" style="border-right: 2px solid #000 !important;">
                     <img src="{$logo_itau_base64}" alt="Itaú">
                </td>
                <td class="codigo-banco-dv header-banco">341-7</td>
                <td class="linha-digitavel-header header-banco" colspan="4">{$linha_digitavel}</td>
            </tr>
            <tr>
                <td colspan="4" style="height: 38px;">
                    <span class="label">Local de Pagamento</span>
                    <span class="value">PAGÁVEL PREFERENCIALMENTE NAS AGÊNCIAS DO ITAÚ OU QUALQUER BANCO ATÉ O VENCIMENTO</span>
                </td>
                <td class="col-vencimento">
                    <span class="label">Vencimento</span>
                    <span class="value text-right">{$data_vencimento}</span>
                </td>
            </tr>
            <tr>
                <td colspan="4">
                    <span class="label">Beneficiário</span>
                    <span class="value">{$nome_beneficiario} - CNPJ: {$cnpj_beneficiario}</span>
                </td>
                <td class="col-ag-cod-cedente">
                    <span class="label">Agência/Código Beneficiário</span>
                    <span class="value text-right">{$agencia_codigo_beneficiario}</span>
                </td>
            </tr>
            <tr>
                <td class="col-data-doc">
                    <span class="label">Data do Documento</span>
                    <span class="value">{$data_emissao}</span>
                </td>
                <td class="col-num-doc">
                    <span class="label">Nº do Documento</span>
                    <span class="value">{$this->boleto['id']}</span>
                </td>
                <td class="col-especie-doc">
                    <span class="label">Espécie Doc.</span>
                    <span class="value">DM</span>
                </td>
                <td class="col-aceite">
                    <span class="label">Aceite</span>
                    <span class="value">N</span>
                </td>
                <td class="col-data-proc">
                    <span class="label">Data Processamento</span>
                    <span class="value">{$data_processamento}</span>
                </td>
                <td class="col-nosso-numero">
                    <span class="label">Nosso Número</span>
                    <span class="value text-right">{$this->boleto['nosso_numero']}</span>
                </td>
            </tr>
            <tr>
                <td class="col-uso-banco">
                     <span class="label">Uso do Banco</span>
                     <span class="value">&nbsp;</span>
                </td>
                <td class="col-carteira">
                    <span class="label">Carteira</span>
                    <span class="value">109</span>
                </td>
                <td class="col-moeda">
                    <span class="label">Espécie Moeda</span>
                    <span class="value">R$</span>
                </td>
                <td class="col-qtd-moeda">
                    <span class="label">Quantidade Moeda</span>
                     <span class="value">&nbsp;</span>
                </td>
                <td class="col-valor-doc" colspan="2">
                    <span class="label">(=) Valor do Documento</span>
                    <span class="value text-right">R$ {$valor}</span>
                </td>
            </tr>
            <tr>
                <td colspan="4" rowspan="5" style="vertical-align: top;">
                    <span class="label">Instruções (Texto de responsabilidade do BENEFICIÁRIO)</span>
                     <div class="instrucoes">
                        Após o vencimento, cobrar multa de {$this->boleto['multa']}%<br>
                        Após o vencimento, cobrar juros de {$this->boleto['juros']}% ao mês (calculado pró-rata dia)<br>
                        Não receber após 30 (trinta) dias do vencimento.<br>
                        Referente a: {$this->boleto['descricao']}
                    </div>
                </td>
                <td class="col-descontos" colspan="2">
                    <span class="label">(-) Desconto / Abatimento</span>
                    <span class="value text-right">&nbsp;</span>
                </td>
            </tr>
            <tr><td class="col-descontos" colspan="2"><span class="label">(-) Outras Deduções</span><span class="value text-right">&nbsp;</span></td></tr>
            <tr><td class="col-descontos" colspan="2"><span class="label">(+) Mora / Multa</span><span class="value text-right">&nbsp;</span></td></tr>
            <tr><td class="col-descontos" colspan="2"><span class="label">(+) Outros Acréscimos</span><span class="value text-right">&nbsp;</span></td></tr>
            <tr>
                <td class="col-descontos" colspan="2">
                    <span class="label">(=) Valor Cobrado</span>
                    <span class="value text-right">&nbsp;</span>
                </td>
            </tr>
            <tr>
                <td colspan="6" style="padding-bottom: 0; border-bottom: none;">
                    <span class="label">Pagador</span>
                    <span class="value">{$this->boleto['nome_pagador']} - CPF/CNPJ: {$this->boleto['cpf_pagador']}</span><br>
                    <span class="small-text">{$this->boleto['endereco']}, {$this->boleto['bairro']}</span><br>
                    <span class="small-text">{$this->boleto['cidade']}/{$this->boleto['uf']} - CEP: {$this->boleto['cep']}</span>
                </td>
            </tr>             <tr>
                <td colspan="6" style="border-top: none; padding-top: 0; height: 70px;"> <!-- Altura ajustada para barcode -->
                    <div class="barcode-area">
                        <img id="barcodeImage" src="{$barcode_image_base64}" alt="Código de Barras: {$codigo_barras_numerico}" style="max-width: 100%; height: 45px; display: block; margin: 0 auto;" />
                        <div class="barcode-fallback" style="font-family: 'Courier New', monospace; font-size: 7pt; text-align: center; padding: 5px; margin-top: 5px;">
                            {$codigo_barras_numerico}
                        </div>
                    </div>
                     <span class="label text-right" style="margin-top: -3px; display:block;">Autenticação Mecânica - Ficha de Compensação</span>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
HTML;

        return $html;
    }
    
    /**
     * Formata a linha digitável para exibição
     */
    private function formatarLinhaDigitavel($linha) {
        if (empty($linha)) return '';
        
        // Remove espaços e pontos
        $linha = preg_replace('/[^0-9]/', '', $linha);
        
        // Formata com espaços
        if (strlen($linha) >= 44) { // Standard length is 47, but check for minimum needed for formatting
            return substr($linha, 0, 5) . '.' . substr($linha, 5, 5) . ' ' .
                   substr($linha, 10, 5) . '.' . substr($linha, 15, 6) . ' ' .
                   substr($linha, 21, 5) . '.' . substr($linha, 26, 6) . ' ' .
                   substr($linha, 32, 1) . ' ' .
                   substr($linha, 33);
        }
        
        return $linha;
    }
      /**
     * Gera o PDF do boleto
     */
    public function gerarPDF($salvarArquivo = false) {
        // Primeiro tenta usar DomPDF se disponível
        $autoloadPath = __DIR__ . '/../../vendor/autoload.php';
        if (file_exists($autoloadPath)) {
            // Ensure DomPDF classes are loaded if not already
            if (!class_exists('\Dompdf\Dompdf')) {
                // require_once $autoloadPath; // Autoloader already included at the top of the file
            }
            if (class_exists('\Dompdf\Dompdf')) {
                return $this->gerarPDFComDomPDF($salvarArquivo);
            } else {
                error_log("DomPDF autoloaded, but class \Dompdf\Dompdf not found. Falling back to HTML.");
                return $this->gerarPDFAlternativo($salvarArquivo);
            }
        }
        
        // Método alternativo usando mPDF ou TCPDF (ou fallback para HTML)
        error_log("DomPDF not found at {$autoloadPath}. Falling back to HTML representation for boleto PDF.");
        return $this->gerarPDFAlternativo($salvarArquivo);
    }
    
    /**
     * Gera PDF usando DomPDF
     */
    private function gerarPDFComDomPDF($salvarArquivo = false) {
        error_log("[BoletoPDF] Entered gerarPDFComDomPDF. salvarArquivo: " . ($salvarArquivo ? 'true' : 'false'));
        // require_once __DIR__ . '/../../vendor/autoload.php'; // Already included at the top
        
        $dompdf = new \Dompdf\Dompdf();
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true); // Enable remote images (like the Itau logo)
        $options->set('isHtml5ParserEnabled', true);
        // Add a log for DomPDF font directory if issues persist with fonts
        // $options->setFontDir(__DIR__ . '/../../vendor/dompdf/dompdf/lib/fonts/'); // Example, adjust if needed
        // $options->setTempDir(sys_get_temp_dir()); // Ensure temp dir is writable
        $dompdf->setOptions($options);
        
        $dompdf->loadHtml($this->gerarHTML());
        
        // Configurações do PDF
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        if ($salvarArquivo) {
            // Salva o arquivo
            $nomeArquivo = 'boleto_' . $this->boleto['id'] . '_' . date('Y-m-d') . '.pdf';
            $caminhoArquivo = __DIR__ . '/../../uploads/boletos/' . $nomeArquivo;
            
            // Cria o diretório se não existir
            $diretorio = dirname($caminhoArquivo);
            if (!is_dir($diretorio)) {
                if (!mkdir($diretorio, 0755, true)) {
                    error_log("[BoletoPDF] FAILED to create directory: " . $diretorio);
                    return [
                        'arquivo' => $nomeArquivo,
                        'caminho' => null,
                        'url' => null,
                        'error' => 'Failed to create PDF directory.'
                    ];
                }
            }
            
            error_log("[BoletoPDF] Attempting to save PDF to: " . $caminhoArquivo);
            if (file_put_contents($caminhoArquivo, $dompdf->output()) === false) {
                error_log("[BoletoPDF] FAILED to write PDF file to: " . $caminhoArquivo);
                 return [
                    'arquivo' => $nomeArquivo,
                    'caminho' => null,
                    'url' => null,
                    'error' => 'Failed to write PDF file.'
                ];
            } else {
                error_log("[BoletoPDF] Successfully wrote PDF file to: " . $caminhoArquivo);
            }
            
            return [
                'arquivo' => $nomeArquivo,
                'caminho' => $caminhoArquivo,
                'url' => '../uploads/boletos/' . $nomeArquivo
            ];
        }
        
        // Retorna o PDF para download direto
        return $dompdf->output();
    }
    
    /**
     * Método alternativo usando HTML puro (sem biblioteca PDF)
     */
    private function gerarPDFAlternativo($salvarArquivo = false) {
        error_log("[BoletoPDF] Entered gerarPDFAlternativo. salvarArquivo: " . ($salvarArquivo ? 'true' : 'false'));
        $html = $this->gerarHTMLImpressao();
        
        if ($salvarArquivo) {
            $nomeArquivo = 'boleto_' . $this->boleto['id'] . '_' . date('Y-m-d') . '.html';
            $caminhoArquivo = __DIR__ . '/../../uploads/boletos/' . $nomeArquivo;
            
            // Cria o diretório se não existir
            $diretorio = dirname($caminhoArquivo);
            if (!is_dir($diretorio)) {
                 if (!mkdir($diretorio, 0755, true)) {
                    error_log("[BoletoPDF] FAILED to create directory for HTML fallback: " . $diretorio);
                    return [
                        'arquivo' => $nomeArquivo,
                        'caminho' => null,
                        'url' => null,
                        'error' => 'Failed to create HTML fallback directory.'
                    ];
                }
            }
            
            error_log("[BoletoPDF] Attempting to save HTML fallback to: " . $caminhoArquivo);
            if (file_put_contents($caminhoArquivo, $html) === false) {
                error_log("[BoletoPDF] FAILED to write HTML file to: " . $caminhoArquivo);
                return [
                    'arquivo' => $nomeArquivo,
                    'caminho' => null,
                    'url' => null,
                    'error' => 'Failed to write HTML fallback file.'
                ];
            } else {
                error_log("[BoletoPDF] Successfully wrote HTML file to: " . $caminhoArquivo);
            }
            
            return [
                'arquivo' => $nomeArquivo,
                'caminho' => $caminhoArquivo,
                'url' => '../uploads/boletos/' . $nomeArquivo
            ];
        }
        
        return $html;
    }
    
    /**
     * Gera HTML otimizado para impressão
     */
    private function gerarHTMLImpressao() {
        $html = $this->gerarHTML();
        
        // Adiciona CSS específico para impressão
        $cssImpressao = '
        <style media="print">
            @page {
                margin: 1cm;
                size: A4;
            }
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .no-print {
                display: none !important;
            }
        </style>
        <script>
            window.onload = function() {
                // Auto-impressão opcional
                // window.print();
            }
        </script>';
        
        return str_replace('</head>', $cssImpressao . '</head>', $html);
    }
      /**
     * Força o download do PDF
     */
    public function downloadPDF() {
        $nomeArquivo = 'boleto_' . $this->boleto['id'] . '_' . date('Y-m-d');
        $conteudo = $this->gerarPDF();
        
        // Verifica se é PDF ou HTML
        $isPDF = strpos($conteudo, '%PDF') === 0;
        
        if ($isPDF) {
            $nomeArquivo .= '.pdf';
            header('Content-Type: application/pdf');
        } else {
            $nomeArquivo .= '.html';
            header('Content-Type: text/html; charset=utf-8');
        }
        
        header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        
        echo $conteudo;
        exit;
    }
    
    /**
     * Exibe o PDF no navegador
     */
    public function visualizarPDF() {
        $nomeArquivo = 'boleto_' . $this->boleto['id'] . '_' . date('Y-m-d');
        $conteudo = $this->gerarPDF();
        
        // Verifica se é PDF ou HTML
        $isPDF = strpos($conteudo, '%PDF') === 0;
        
        if ($isPDF) {
            $nomeArquivo .= '.pdf';
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . $nomeArquivo . '"');
        } else {
            $nomeArquivo .= '.html';
            header('Content-Type: text/html; charset=utf-8');
            // Para HTML, não definimos Content-Disposition
        }
        
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        
        echo $conteudo;
        exit;
    }
}
?>
