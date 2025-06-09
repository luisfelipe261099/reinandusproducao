<?php
/**
 * Script para adicionar o arquivo CSS de correções de layout em todas as páginas
 * Este script adiciona a linha <link rel="stylesheet" href="../css/layout-fixes.css"> em todas as páginas PHP
 */

// Diretórios a serem verificados
$directories = [
    'polo',
    'ava',
    'financeiro'
];

// Padrão a ser encontrado (após o qual inseriremos o novo CSS)
$pattern_to_find = '<link rel="stylesheet" href="../css/styles.css">';
$pattern_to_find_alt = '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">';

// Linha a ser adicionada
$line_to_add = '<link rel="stylesheet" href="../css/layout-fixes.css">';

// Contador de arquivos corrigidos
$fixed_files = 0;
$total_files = 0;

// Função para processar um diretório
function process_directory($dir) {
    global $pattern_to_find, $pattern_to_find_alt, $line_to_add, $fixed_files, $total_files;
    
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
                    $modified = false;
                    
                    // Tenta inserir após o padrão principal
                    if (strpos($content, $pattern_to_find) !== false) {
                        $new_content = str_replace(
                            $pattern_to_find, 
                            $pattern_to_find . "\n    " . $line_to_add, 
                            $content
                        );
                        $modified = true;
                    } 
                    // Se não encontrar o padrão principal, tenta o alternativo
                    elseif (strpos($content, $pattern_to_find_alt) !== false) {
                        $new_content = str_replace(
                            $pattern_to_find_alt, 
                            $pattern_to_find_alt . "\n    " . $line_to_add, 
                            $content
                        );
                        $modified = true;
                    }
                    
                    // Se o arquivo foi modificado, salva as alterações
                    if ($modified) {
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
echo "<h1>Adição de CSS de Layout</h1>";
echo "<p>Iniciando adição do CSS de layout em todas as páginas...</p>";

foreach ($directories as $directory) {
    echo "<h2>Processando diretório: $directory</h2>";
    process_directory($directory);
}

echo "<h2>Resumo</h2>";
echo "<p>Total de arquivos verificados: $total_files</p>";
echo "<p>Total de arquivos corrigidos: $fixed_files</p>";

if ($fixed_files > 0) {
    echo "<p style='color: green;'>Adição concluída com sucesso!</p>";
} else {
    echo "<p style='color: orange;'>Nenhum arquivo precisou ser corrigido.</p>";
}

echo "<p><a href='index.php'>Voltar para a página inicial</a></p>";
?>
