<?php
/**
 * Classe para gerar PDFs de boletos bancários
 */

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
        
        // Formata a linha digitável para exibição
        $linha_digitavel = $this->formatarLinhaDigitavel($this->boleto['linha_digitavel'] ?? '');
        
        $html = <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boleto Bancário - {$this->boleto['nosso_numero']}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 20px;
            color: #000;
        }
        .boleto-container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            border: 1px solid #000;
        }
        .boleto-header {
            background: #f0f0f0;
            padding: 10px;
            text-align: center;
            font-weight: bold;
            border-bottom: 1px solid #000;
        }
        .boleto-section {
            border-bottom: 1px solid #000;
            padding: 10px;
        }
        .boleto-row {
            display: flex;
            border-bottom: 1px solid #ccc;
            min-height: 25px;
        }
        .boleto-col {
            border-right: 1px solid #ccc;
            padding: 5px;
            flex: 1;
        }
        .boleto-col:last-child {
            border-right: none;
        }
        .boleto-col-small {
            flex: 0 0 120px;
        }
        .boleto-col-medium {
            flex: 0 0 200px;
        }
        .boleto-label {
            font-size: 10px;
            color: #666;
            margin-bottom: 2px;
        }
        .boleto-value {
            font-weight: bold;
            font-size: 12px;
        }
        .linha-digitavel {
            font-family: 'Courier New', monospace;
            font-size: 14px;
            font-weight: bold;
            text-align: center;
            padding: 10px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            margin: 10px 0;
        }
        .codigo-barras {
            text-align: center;
            padding: 10px;
            font-family: 'Courier New', monospace;
            font-size: 10px;
        }
        .instrucoes {
            padding: 15px;
            border-top: 1px solid #000;
            margin-top: 20px;
        }
        .banco-logo {
            float: left;
            font-weight: bold;
            font-size: 16px;
            color: #FF6600;
        }
        .codigo-banco {
            float: right;
            font-weight: bold;
            font-size: 16px;
        }
        .clear {
            clear: both;
        }
    </style>
</head>
<body>
    <div class="boleto-container">
        <div class="boleto-header">
            <div class="banco-logo">ITAÚ</div>
            <div class="codigo-banco">341-7</div>
            <div class="clear"></div>
        </div>
        
        <div class="linha-digitavel">
            {$linha_digitavel}
        </div>
        
        <div class="boleto-section">
            <div class="boleto-row">
                <div class="boleto-col">
                    <div class="boleto-label">Beneficiário</div>
                    <div class="boleto-value">FACULDADE DE CIÊNCIAS GERENCIAIS</div>
                </div>
            </div>
        </div>
        
        <div class="boleto-section">
            <div class="boleto-row">
                <div class="boleto-col boleto-col-medium">
                    <div class="boleto-label">Pagador</div>
                    <div class="boleto-value">{$this->boleto['nome_pagador']}</div>
                    <div>{$this->boleto['cpf_pagador']}</div>
                    <div>{$this->boleto['endereco']}</div>
                    <div>{$this->boleto['bairro']} - {$this->boleto['cidade']}/{$this->boleto['uf']}</div>
                    <div>CEP: {$this->boleto['cep']}</div>
                </div>
                <div class="boleto-col boleto-col-small">
                    <div class="boleto-label">Nosso Número</div>
                    <div class="boleto-value">{$this->boleto['nosso_numero']}</div>
                </div>
                <div class="boleto-col boleto-col-small">
                    <div class="boleto-label">Vencimento</div>
                    <div class="boleto-value">{$data_vencimento}</div>
                </div>
                <div class="boleto-col boleto-col-small">
                    <div class="boleto-label">Valor</div>
                    <div class="boleto-value">R$ {$valor}</div>
                </div>
            </div>
        </div>
        
        <div class="boleto-section">
            <div class="boleto-row">
                <div class="boleto-col">
                    <div class="boleto-label">Descrição</div>
                    <div class="boleto-value">{$this->boleto['descricao']}</div>
                </div>
            </div>
        </div>
        
        <div class="instrucoes">
            <div class="boleto-label">Instruções:</div>
            <div>- Após o vencimento, multa de 2% + juros de 1% ao mês</div>
            <div>- Não receber após 30 dias de vencido</div>
            <div>- Em caso de dúvidas, entre em contato conosco</div>
        </div>
        
        <div class="codigo-barras">
            <div class="boleto-label">Código de Barras:</div>
            <div style="font-family: 'Courier New', monospace; word-break: break-all;">
                {$this->boleto['codigo_barras']}
            </div>
        </div>
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
        if (strlen($linha) >= 44) {
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
            return $this->gerarPDFComDomPDF($salvarArquivo);
        }
        
        // Método alternativo usando mPDF ou TCPDF
        return $this->gerarPDFAlternativo($salvarArquivo);
    }
    
    /**
     * Gera PDF usando DomPDF
     */
    private function gerarPDFComDomPDF($salvarArquivo = false) {
        require_once __DIR__ . '/../../vendor/autoload.php';
        
        $dompdf = new \Dompdf\Dompdf();
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
                mkdir($diretorio, 0755, true);
            }
            
            file_put_contents($caminhoArquivo, $dompdf->output());
            
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
        // Se não há biblioteca PDF, salva como HTML otimizado para impressão
        $html = $this->gerarHTMLImpressao();
        
        if ($salvarArquivo) {
            $nomeArquivo = 'boleto_' . $this->boleto['id'] . '_' . date('Y-m-d') . '.html';
            $caminhoArquivo = __DIR__ . '/../../uploads/boletos/' . $nomeArquivo;
            
            // Cria o diretório se não existir
            $diretorio = dirname($caminhoArquivo);
            if (!is_dir($diretorio)) {
                mkdir($diretorio, 0755, true);
            }
            
            file_put_contents($caminhoArquivo, $html);
            
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
