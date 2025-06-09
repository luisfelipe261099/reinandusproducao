<?php
/**
 * Funções para exportar relatórios em diferentes formatos
 */

/**
 * Exporta dados para Excel
 * 
 * @param array $dados Dados a serem exportados
 * @param array $colunas Colunas a serem incluídas no arquivo
 * @param string $nome_arquivo Nome do arquivo a ser gerado
 */
function exportarParaExcel($dados, $colunas, $nome_arquivo) {
    // Define o tipo de conteúdo e cabeçalhos para download
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="' . $nome_arquivo . '.xls"');
    header('Cache-Control: max-age=0');
    
    // Inicia a saída do arquivo
    echo '<table border="1">';
    
    // Cabeçalho
    echo '<tr>';
    foreach ($colunas as $coluna) {
        echo '<th>' . htmlspecialchars($coluna) . '</th>';
    }
    echo '</tr>';
    
    // Dados
    foreach ($dados as $linha) {
        echo '<tr>';
        foreach ($colunas as $chave => $valor) {
            echo '<td>' . (isset($linha[$chave]) ? htmlspecialchars($linha[$chave]) : '') . '</td>';
        }
        echo '</tr>';
    }
    
    echo '</table>';
    exit;
}

/**
 * Exporta dados para PDF
 * 
 * @param array $dados Dados a serem exportados
 * @param array $colunas Colunas a serem incluídas no arquivo
 * @param string $nome_arquivo Nome do arquivo a ser gerado
 * @param string $titulo Título do relatório
 * @param string $orientacao Orientação do papel (P = Retrato, L = Paisagem)
 */
function exportarParaPDF($dados, $colunas, $nome_arquivo, $titulo, $orientacao = 'P') {
    // Verifica se a biblioteca TCPDF está disponível
    if (!class_exists('TCPDF')) {
        // Se não estiver disponível, usa uma abordagem mais simples com HTML e CSS
        exportarParaPDFSimples($dados, $colunas, $nome_arquivo, $titulo, $orientacao);
        return;
    }
    
    // Cria uma nova instância do TCPDF
    $pdf = new TCPDF($orientacao, 'mm', 'A4', true, 'UTF-8', false);
    
    // Define informações do documento
    $pdf->SetCreator('Faciência ERP');
    $pdf->SetAuthor('Faciência ERP');
    $pdf->SetTitle($titulo);
    $pdf->SetSubject($titulo);
    
    // Remove cabeçalho e rodapé padrão
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Define margens
    $pdf->SetMargins(10, 10, 10);
    
    // Adiciona uma página
    $pdf->AddPage();
    
    // Define a fonte
    $pdf->SetFont('helvetica', 'B', 14);
    
    // Adiciona o título
    $pdf->Cell(0, 10, $titulo, 0, 1, 'C');
    $pdf->Ln(5);
    
    // Define a fonte para a tabela
    $pdf->SetFont('helvetica', '', 10);
    
    // Calcula a largura das colunas
    $num_colunas = count($colunas);
    $largura_coluna = ($pdf->getPageWidth() - 20) / $num_colunas;
    
    // Cabeçalho da tabela
    $pdf->SetFillColor(220, 220, 220);
    $pdf->SetTextColor(0);
    $pdf->SetDrawColor(128, 128, 128);
    $pdf->SetLineWidth(0.3);
    $pdf->SetFont('', 'B');
    
    foreach ($colunas as $coluna) {
        $pdf->Cell($largura_coluna, 7, $coluna, 1, 0, 'C', 1);
    }
    $pdf->Ln();
    
    // Dados da tabela
    $pdf->SetFillColor(245, 245, 245);
    $pdf->SetTextColor(0);
    $pdf->SetFont('');
    $fill = 0;
    
    foreach ($dados as $linha) {
        foreach ($colunas as $chave => $valor) {
            $pdf->Cell($largura_coluna, 6, isset($linha[$chave]) ? $linha[$chave] : '', 1, 0, 'L', $fill);
        }
        $pdf->Ln();
        $fill = !$fill;
    }
    
    // Saída do PDF
    $pdf->Output($nome_arquivo . '.pdf', 'D');
    exit;
}

/**
 * Exporta dados para PDF usando HTML e CSS (alternativa simples quando TCPDF não está disponível)
 * 
 * @param array $dados Dados a serem exportados
 * @param array $colunas Colunas a serem incluídas no arquivo
 * @param string $nome_arquivo Nome do arquivo a ser gerado
 * @param string $titulo Título do relatório
 * @param string $orientacao Orientação do papel (P = Retrato, L = Paisagem)
 */
function exportarParaPDFSimples($dados, $colunas, $nome_arquivo, $titulo, $orientacao = 'P') {
    // Define o tipo de conteúdo
    header('Content-Type: text/html; charset=utf-8');
    
    // Estilos CSS
    $css = '
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        h1 {
            text-align: center;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            color: #666;
        }
        @media print {
            body {
                margin: 0;
                padding: 15mm;
            }
            @page {
                size: ' . ($orientacao == 'L' ? 'landscape' : 'portrait') . ';
                margin: 10mm;
            }
        }
    </style>
    ';
    
    // Início do HTML
    echo '<!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>' . htmlspecialchars($titulo) . '</title>
        ' . $css . '
        <script>
            window.onload = function() {
                window.print();
                setTimeout(function() {
                    window.close();
                }, 500);
            };
        </script>
    </head>
    <body>
        <h1>' . htmlspecialchars($titulo) . '</h1>
        
        <table>
            <thead>
                <tr>';
    
    // Cabeçalho da tabela
    foreach ($colunas as $coluna) {
        echo '<th>' . htmlspecialchars($coluna) . '</th>';
    }
    
    echo '</tr>
            </thead>
            <tbody>';
    
    // Dados da tabela
    foreach ($dados as $linha) {
        echo '<tr>';
        foreach ($colunas as $chave => $valor) {
            echo '<td>' . (isset($linha[$chave]) ? htmlspecialchars($linha[$chave]) : '') . '</td>';
        }
        echo '</tr>';
    }
    
    // Fim do HTML
    echo '</tbody>
        </table>
        
        <div class="footer">
            Relatório gerado em ' . date('d/m/Y H:i:s') . ' - Faciência ERP
        </div>
    </body>
    </html>';
    
    exit;
}
