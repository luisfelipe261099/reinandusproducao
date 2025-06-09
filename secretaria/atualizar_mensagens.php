<?php
/**
 * Script para atualizar todos os arquivos que usam a variável $_SESSION['mensagem_tipo']
 * para usar a nova estrutura $_SESSION['mensagem']['tipo']
 */

// Lista de arquivos a serem verificados
$arquivos = glob('./**/*.php');

$contador = 0;

foreach ($arquivos as $arquivo) {
    // Ignora o diretório vendor
    if (strpos($arquivo, './vendor/') === 0) {
        continue;
    }
    
    // Lê o conteúdo do arquivo
    $conteudo = file_get_contents($arquivo);
    
    // Verifica se o arquivo usa a variável $_SESSION['mensagem_tipo']
    if (strpos($conteudo, '$_SESSION[\'mensagem_tipo\']') !== false || strpos($conteudo, '$_SESSION["mensagem_tipo"]') !== false) {
        echo "Atualizando arquivo: $arquivo\n";
        
        // Substitui as atribuições
        $conteudo = preg_replace(
            '/\$_SESSION\[([\'"])mensagem\1\]\s*=\s*(.*?);\s*\$_SESSION\[([\'"])mensagem_tipo\3\]\s*=\s*(.*?);/s',
            '$_SESSION[$1mensagem$1] = [
    \'texto\' => $2,
    \'tipo\' => $4
];',
            $conteudo
        );
        
        // Substitui os acessos
        $conteudo = str_replace('$_SESSION[\'mensagem_tipo\']', '$_SESSION[\'mensagem\'][\'tipo\']', $conteudo);
        $conteudo = str_replace('$_SESSION["mensagem_tipo"]', '$_SESSION["mensagem"]["tipo"]', $conteudo);
        
        // Substitui os unset
        $conteudo = str_replace('unset($_SESSION[\'mensagem\'], $_SESSION[\'mensagem_tipo\']);', 'unset($_SESSION[\'mensagem\']);', $conteudo);
        $conteudo = str_replace('unset($_SESSION["mensagem"], $_SESSION["mensagem_tipo"]);', 'unset($_SESSION["mensagem"]);', $conteudo);
        $conteudo = str_replace('unset($_SESSION[\'mensagem_tipo\']);', '', $conteudo);
        $conteudo = str_replace('unset($_SESSION["mensagem_tipo"]);', '', $conteudo);
        
        // Salva o arquivo
        file_put_contents($arquivo, $conteudo);
        
        $contador++;
    }
}

echo "Total de arquivos atualizados: $contador\n";
echo "Atualização concluída!\n";
?>
