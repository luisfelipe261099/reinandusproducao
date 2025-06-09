<?php
/**
 * Script para corrigir o layout em todas as páginas
 * Este script busca e corrige o problema do conteúdo principal aparecendo por baixo do menu lateral
 */

// Diretórios a serem verificados
$directories = [
    'polo',
    'ava',
    'financeiro'
];

// Padrão a ser encontrado e substituído
$pattern_to_find = '<div class="flex-1 flex flex-col overflow-hidden">';
$replacement = '<div class="main-content flex-1 flex flex-col overflow-hidden">';

// Padrão alternativo
$alt_pattern_to_find = '<div id="content-wrapper" class="flex-1 flex flex-col overflow-hidden">';
$alt_replacement = '<div class="main-content flex-1 flex flex-col overflow-hidden">';

// Contador de arquivos corrigidos
$fixed_files = 0;
$total_files = 0;

// Função para processar um diretório
function process_directory($dir) {
    global $pattern_to_find, $replacement, $alt_pattern_to_find, $alt_replacement, $fixed_files, $total_files;
    
    $files = scandir($dir);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $path = $dir . '/' . $file;
        
        if (is_dir($path)) {
            // Ignora diretórios específicos
            if ($file === 'includes' || $file === 'vendor' || $file === 'node_modules') continue;
            
            // Processa o diretório recursivamente
            process_directory($path);
        } else {
            // Verifica se é um arquivo PHP
            if (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
                $total_files++;
                
                // Lê o conteúdo do arquivo
                $content = file_get_contents($path);
                
                // Verifica se o padrão existe no arquivo
                if (strpos($content, $pattern_to_find) !== false || strpos($content, $alt_pattern_to_find) !== false) {
                    // Substitui o padrão
                    $new_content = str_replace($pattern_to_find, $replacement, $content);
                    $new_content = str_replace($alt_pattern_to_find, $alt_replacement, $new_content);
                    
                    // Escreve o conteúdo modificado de volta no arquivo
                    file_put_contents($path, $new_content);
                    
                    $fixed_files++;
                    echo "Corrigido: $path<br>";
                }
            }
        }
    }
}

// Processa cada diretório
echo "<h1>Correção de Layout</h1>";
echo "<p>Iniciando correção de layout em todas as páginas...</p>";

foreach ($directories as $directory) {
    echo "<h2>Processando diretório: $directory</h2>";
    process_directory($directory);
}

echo "<h2>Resumo</h2>";
echo "<p>Total de arquivos verificados: $total_files</p>";
echo "<p>Total de arquivos corrigidos: $fixed_files</p>";

if ($fixed_files > 0) {
    echo "<p style='color: green;'>Correção concluída com sucesso!</p>";
} else {
    echo "<p style='color: orange;'>Nenhum arquivo precisou ser corrigido.</p>";
}

echo "<p><a href='index.php'>Voltar para a página inicial</a></p>";
?>
