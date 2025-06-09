<?php
// Ativa o modo de exibição de erros para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define o diretório a ser verificado
$diretorio = 'views/polos';

// Lista de arquivos que devem existir
$arquivos_necessarios = [
    'editar.php',
    'editar_com_tipos.php',
    'editar_financeiro.php',
    'excluir.php',
    'financeiro.php',
    'listar.php',
    'novo.php',
    'novo_com_tipos.php',
    'salvar.php',
    'salvar_com_tipos.php',
    'salvar_financeiro.php',
    'visualizar.php'
];

// Verifica se os arquivos existem
$arquivos_faltando = [];
foreach ($arquivos_necessarios as $arquivo) {
    $caminho_completo = $diretorio . '/' . $arquivo;
    if (!file_exists($caminho_completo)) {
        $arquivos_faltando[] = $arquivo;
    }
}

// Exibe o resultado
echo "<h1>Verificação de Arquivos</h1>";

if (empty($arquivos_faltando)) {
    echo "<p style='color: green;'>Todos os arquivos necessários existem!</p>";
} else {
    echo "<p style='color: red;'>Os seguintes arquivos estão faltando:</p>";
    echo "<ul>";
    foreach ($arquivos_faltando as $arquivo) {
        echo "<li>{$arquivo}</li>";
    }
    echo "</ul>";
}

// Lista todos os arquivos existentes no diretório
$arquivos_existentes = scandir($diretorio);
$arquivos_existentes = array_diff($arquivos_existentes, ['.', '..']);

echo "<h2>Arquivos existentes no diretório:</h2>";
echo "<ul>";
foreach ($arquivos_existentes as $arquivo) {
    echo "<li>{$arquivo}</li>";
}
echo "</ul>";

// Verifica se há arquivos modificados que podem ser usados
$arquivos_modificados = [];
foreach ($arquivos_existentes as $arquivo) {
    if (strpos($arquivo, '_modificado') !== false) {
        $arquivo_original = str_replace('_modificado', '', $arquivo);
        if (in_array($arquivo_original, $arquivos_faltando)) {
            $arquivos_modificados[$arquivo_original] = $arquivo;
        }
    }
}

if (!empty($arquivos_modificados)) {
    echo "<h2>Arquivos modificados que podem ser usados:</h2>";
    echo "<ul>";
    foreach ($arquivos_modificados as $original => $modificado) {
        echo "<li>{$modificado} pode ser usado como {$original}</li>";
    }
    echo "</ul>";
    
    echo "<h2>Comandos para copiar os arquivos:</h2>";
    echo "<pre>";
    foreach ($arquivos_modificados as $original => $modificado) {
        echo "copy {$diretorio}/{$modificado} {$diretorio}/{$original}\n";
    }
    echo "</pre>";
}
?>
