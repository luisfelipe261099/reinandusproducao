<?php
// Script para baixar e extrair a biblioteca FPDF

// URL da biblioteca FPDF
$fpdf_url = 'http://www.fpdf.org/en/download/fpdf184.zip';
$zip_file = 'fpdf.zip';

// Baixa o arquivo ZIP
echo "Baixando FPDF...\n";
file_put_contents($zip_file, file_get_contents($fpdf_url));

// Extrai o arquivo ZIP
echo "Extraindo arquivos...\n";
$zip = new ZipArchive;
if ($zip->open($zip_file) === TRUE) {
    $zip->extractTo('vendor/');
    $zip->close();
    echo "Extração concluída!\n";
} else {
    echo "Falha ao extrair o arquivo ZIP.\n";
}

// Remove o arquivo ZIP
unlink($zip_file);

echo "FPDF instalado com sucesso!\n";
?>
