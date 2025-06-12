<?php
require_once 'vendor/autoload.php';

use Picqer\Barcode\BarcodeGeneratorPNG;

echo "Testando geração de código de barras...\n";

try {
    $generator = new BarcodeGeneratorPNG();
    $codigo = '34191234567890101112131415161718250612000001000';
    
    echo "Código numérico: $codigo\n";
    echo "Tamanho do código: " . strlen($codigo) . " dígitos\n";
    
    $barcode_image = $generator->getBarcode($codigo, BarcodeGeneratorPNG::TYPE_CODE_128, 2, 50);
    $barcode_base64 = 'data:image/png;base64,' . base64_encode($barcode_image);
    
    echo "Código de barras gerado com sucesso!\n";
    echo "Tamanho da imagem: " . strlen($barcode_image) . " bytes\n";
    echo "Base64 gerado: " . (strlen($barcode_base64) > 100 ? "OK" : "ERRO") . "\n";
    
    // Salvar arquivo para teste
    file_put_contents('test_barcode.png', $barcode_image);
    echo "Arquivo test_barcode.png salvo para verificação\n";
    
} catch (Exception $e) {
    echo "ERRO ao gerar código de barras: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\nTestando logo do Itaú...\n";
$logo_url = "https://logodownload.org/wp-content/uploads/2014/07/itau-logo-1-1.png";
$headers = get_headers($logo_url);
echo "Status do logo: " . $headers[0] . "\n";
?>
