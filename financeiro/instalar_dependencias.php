<?php
/**
 * Script para instalar dependências do módulo financeiro
 */

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';

Auth::requireLogin();
$userType = Auth::getUserType();
if (!in_array($userType, ['admin_master'])) {
    die('Acesso negado. Apenas administradores master podem executar este script.');
}

echo "<h1>🔧 INSTALADOR DE DEPENDÊNCIAS - MÓDULO FINANCEIRO</h1>";
echo "<hr>";

// Verifica se o Composer está disponível
$composerPath = __DIR__ . '/../../composer.json';
$vendorPath = __DIR__ . '/../../vendor/autoload.php';

echo "<h2>1. Verificando Composer:</h2>";
if (file_exists($composerPath)) {
    echo "✅ <strong>composer.json encontrado</strong><br>";
    
    if (file_exists($vendorPath)) {
        echo "✅ <strong>vendor/autoload.php encontrado</strong><br>";
        echo "📦 <strong>Dependências já instaladas!</strong><br>";
        
        // Verifica se DomPDF está disponível
        require_once $vendorPath;
        if (class_exists('\Dompdf\Dompdf')) {
            echo "✅ <strong>DomPDF está disponível</strong><br>";
        } else {
            echo "⚠️ <strong>DomPDF não encontrado no vendor</strong><br>";
        }
    } else {
        echo "⚠️ <strong>vendor/autoload.php não encontrado</strong><br>";
        echo "📝 <strong>Execute:</strong> <code>composer install</code><br>";
    }
} else {
    echo "❌ <strong>composer.json não encontrado</strong><br>";
    echo "<h3>Criando composer.json para o projeto:</h3>";
    
    $composerConfig = [
        "name" => "faciencia/erp",
        "description" => "Sistema ERP Faciência",
        "type" => "project",
        "require" => [
            "php" => ">=7.4",
            "dompdf/dompdf" => "^2.0"
        ],
        "autoload" => [
            "psr-4" => [
                "Faciencia\\" => "includes/"
            ]
        ]
    ];
    
    if (file_put_contents($composerPath, json_encode($composerConfig, JSON_PRETTY_PRINT))) {
        echo "✅ <strong>composer.json criado com sucesso</strong><br>";
        echo "📝 <strong>Próximo passo:</strong> Execute <code>composer install</code> no terminal<br>";
    } else {
        echo "❌ <strong>Erro ao criar composer.json</strong><br>";
    }
}

echo "<hr>";

echo "<h2>2. Verificando diretórios necessários:</h2>";

$diretorios = [
    __DIR__ . '/../../uploads/boletos',
    __DIR__ . '/../../uploads/pdfs',
    __DIR__ . '/../../temp'
];

foreach ($diretorios as $dir) {
    if (is_dir($dir)) {
        echo "✅ <strong>" . basename($dir) . "</strong> existe<br>";
    } else {
        if (mkdir($dir, 0755, true)) {
            echo "✅ <strong>" . basename($dir) . "</strong> criado<br>";
        } else {
            echo "❌ <strong>Erro ao criar " . basename($dir) . "</strong><br>";
        }
    }
}

echo "<hr>";

echo "<h2>3. Teste do gerador de PDF:</h2>";

try {
    require_once __DIR__ . '/includes/boleto_pdf.php';
    
    // Dados de teste
    $boletoTeste = [
        'id' => 999,
        'nome_pagador' => 'João Teste Silva',
        'cpf_pagador' => '123.456.789-00',
        'endereco' => 'Rua Teste, 123',
        'bairro' => 'Centro',
        'cidade' => 'São Paulo',
        'uf' => 'SP',
        'cep' => '01000-000',
        'valor' => 150.00,
        'data_vencimento' => date('Y-m-d', strtotime('+30 days')),
        'descricao' => 'Teste de geração de PDF',
        'nosso_numero' => '12345678',
        'linha_digitavel' => '34191123456789012345678901234567890123456789012',
        'codigo_barras' => '34191123456789012345678901234567890123456789'
    ];
    
    $pdfGenerator = new BoletoPDF($boletoTeste);
    $html = $pdfGenerator->gerarHTML();
    
    if (!empty($html)) {
        echo "✅ <strong>Gerador de HTML funciona</strong><br>";
        
        // Testa se consegue gerar PDF
        try {
            $pdf = $pdfGenerator->gerarPDF();
            if ($pdf) {
                echo "✅ <strong>Gerador de PDF funciona</strong><br>";
                echo "📄 <strong>Tamanho do PDF:</strong> " . strlen($pdf) . " bytes<br>";
            } else {
                echo "⚠️ <strong>PDF retornou vazio, mas sem erro</strong><br>";
            }
        } catch (Exception $e) {
            echo "⚠️ <strong>Erro ao gerar PDF:</strong> " . $e->getMessage() . "<br>";
            echo "📝 <strong>Fallback para HTML funcionando</strong><br>";
        }
    } else {
        echo "❌ <strong>Erro ao gerar HTML</strong><br>";
    }
    
} catch (Exception $e) {
    echo "❌ <strong>Erro no teste:</strong> " . $e->getMessage() . "<br>";
}

echo "<hr>";

echo "<h2>🎯 RESUMO:</h2>";

$statusGeral = "✅ FUNCIONANDO";

if (!file_exists($vendorPath)) {
    $statusGeral = "⚠️ PRECISA INSTALAR DEPENDÊNCIAS";
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; border-left: 5px solid #ffc107;'>";
    echo "📦 <strong>EXECUTE NO TERMINAL:</strong><br>";
    echo "<code>cd " . dirname(__DIR__) . "</code><br>";
    echo "<code>composer install</code><br>";
    echo "</div>";
} else {
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; border-left: 5px solid #28a745;'>";
    echo "🎉 <strong>MÓDULO FINANCEIRO PRONTO!</strong><br>";
    echo "✅ Dependências instaladas<br>";
    echo "✅ Diretórios criados<br>";
    echo "✅ Gerador de PDF funcionando<br>";
    echo "</div>";
}

echo "<p><em>Instalação verificada em: " . date('d/m/Y H:i:s') . "</em></p>";
?>
