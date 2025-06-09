<?php
/**
 * Script para adicionar o arquivo JavaScript de correções de layout em todas as páginas
 * Este script adiciona a linha <script src="../js/layout-fixes.js"></script> em todas as páginas PHP
 */

// Diretórios a serem verificados
$directories = [
    'polo',
    'ava',
    'financeiro'
];

// Padrão a ser encontrado (antes do qual inseriremos o novo JavaScript)
$pattern_to_find = '<script>';
$pattern_to_find_alt = '</div>\s*</div>\s*<script>';

// Linha a ser adicionada
$line_to_add = '<script src="../js/layout-fixes.js"></script>';

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
                
                // Verifica se o arquivo já inclui o JavaScript de layout-fixes
                if (strpos($content, 'layout-fixes.js') === false) {
                    $modified = false;
                    
                    // Verifica se o arquivo tem a estrutura esperada
                    if (preg_match('/<\/div>\s*<\/div>\s*<script>/', $content)) {
                        // Substitui o padrão encontrado
                        $new_content = preg_replace(
                            '/(<\/div>\s*<\/div>)\s*(<script>)/', 
                            "$1\n\n    " . $line_to_add . "\n    $2", 
                            $content
                        );
                        $modified = true;
                    } 
                    // Se não encontrar o padrão principal, tenta o alternativo
                    elseif (strpos($content, '<script>') !== false) {
                        // Encontra a primeira ocorrência de <script>
                        $pos = strpos($content, '<script>');
                        $new_content = substr($content, 0, $pos) . $line_to_add . "\n    " . substr($content, $pos);
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
echo "<h1>Adição de JavaScript de Layout</h1>";
echo "<p>Iniciando adição do JavaScript de layout em todas as páginas...</p>";

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
