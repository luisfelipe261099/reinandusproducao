<?php
/**
 * Script para incluir o arquivo CSS de correções de layout em todas as páginas
 * Este script deve ser executado uma vez para garantir que todas as páginas incluam o arquivo CSS
 */

// Diretórios a serem verificados
$directories = [
    'polo',
    'ava',
    'financeiro'
];

// Padrão a ser encontrado
$pattern_to_find = '<link rel="stylesheet" href="../css/styles.css">';

// Substituição a ser feita
$replacement = '<link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/layout-fixes.css">';

// Contador de arquivos corrigidos
$fixed_files = 0;
$total_files = 0;

// Função para processar um diretório
function process_directory($dir) {
    global $pattern_to_find, $replacement, $fixed_files, $total_files;
    
    $files = scandir($dir);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $path = $dir . '/' . $file;
        
        if (is_dir($path)) {
            // Ignora diretórios específicos
            if ($file === 'vendor' || $file === 'node_modules') continue;
            
            // Processa o diretório recursivamente
            process_directory($path);
        } else {
            // Verifica se é um arquivo PHP
            if (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
                $total_files++;
                
                // Lê o conteúdo do arquivo
                $content = file_get_contents($path);
                
                // Verifica se o arquivo já inclui o CSS de layout-fixes
                if (strpos($content, 'layout-fixes.css') === false) {
                    // Verifica se o padrão existe no arquivo
                    if (strpos($content, $pattern_to_find) !== false) {
                        // Substitui o padrão
                        $new_content = str_replace($pattern_to_find, $replacement, $content);
                        
                        // Escreve o conteúdo modificado de volta no arquivo
                        file_put_contents($path, $new_content);
                        
                        $fixed_files++;
                        echo "Corrigido: $path<br>";
                    }
                }
            }
        }
    }
}

// Processa cada diretório
echo "<h1>Inclusão de CSS de Layout</h1>";
echo "<p>Iniciando inclusão do CSS de layout em todas as páginas...</p>";

foreach ($directories as $directory) {
    echo "<h2>Processando diretório: $directory</h2>";
    process_directory($directory);
}

echo "<h2>Resumo</h2>";
echo "<p>Total de arquivos verificados: $total_files</p>";
echo "<p>Total de arquivos corrigidos: $fixed_files</p>";

if ($fixed_files > 0) {
    echo "<p style='color: green;'>Inclusão concluída com sucesso!</p>";
} else {
    echo "<p style='color: orange;'>Nenhum arquivo precisou ser corrigido.</p>";
}

echo "<p><a href='index.php'>Voltar para a página inicial</a></p>";
?>
