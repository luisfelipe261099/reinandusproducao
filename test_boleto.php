<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/init.php';
require_once 'financeiro/includes/boleto_pdf.php';

echo "Testando geração de boleto...\n";

// Dados de teste
$dados = [
    'id' => 1,
    'nosso_numero' => '12345678',
    'valor' => 100.50,
    'data_vencimento' => '2025-07-15',
    'linha_digitavel' => '34191234567890101112131415161718250612000001000',
    'codigo_barras' => '34191234567890101112131415161718250612000001000',
    'descricao' => 'Teste de boleto',
    'nome_pagador' => 'João Silva',
    'cpf_pagador' => '123.456.789-00',
    'endereco' => 'Rua A, 123',
    'bairro' => 'Centro',
    'cidade' => 'São Paulo',
    'uf' => 'SP',
    'cep' => '01000-000',
    'multa' => 2,
    'juros' => 1
];

try {
    echo "Criando instância da classe BoletoPDF...\n";
    $pdf = new BoletoPDF($dados);
    
    echo "Gerando HTML...\n";
    $html = $pdf->gerarHTML();
    
    echo "HTML gerado com sucesso! Tamanho: " . strlen($html) . " bytes\n";
    
    // Salvar HTML para inspeção
    file_put_contents('test_boleto_output.html', $html);
    echo "HTML salvo em test_boleto_output.html\n";
    
    // Verificar se o logo está no HTML
    if (strpos($html, 'data:image/png;base64,') !== false) {
        echo "✓ Logo do Itaú (base64) encontrado no HTML\n";
    } else {
        echo "✗ Logo do Itaú NÃO encontrado no HTML\n";
    }
    
    // Verificar se o código de barras está no HTML
    if (strpos($html, $dados['codigo_barras']) !== false) {
        echo "✓ Código de barras encontrado no HTML\n";
    } else {
        echo "✗ Código de barras NÃO encontrado no HTML\n";
    }
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
