<?php
// Ativa o modo de exibição de erros para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verifica se o arquivo existe
$arquivo_original = 'u682219090_faciencia_erp.sql';
if (!file_exists($arquivo_original)) {
    die("O arquivo {$arquivo_original} não foi encontrado.");
}

// Lê o conteúdo do arquivo
$conteudo = file_get_contents($arquivo_original);

// Verifica se o campo valor_por_documento já existe no arquivo
if (strpos($conteudo, 'valor_por_documento') !== false) {
    echo "<h1>Verificação</h1>";
    echo "<p>O campo valor_por_documento já existe no arquivo SQL.</p>";
    exit;
}

// Procura a definição da tabela polos_financeiro
$padrao = '/CREATE TABLE `polos_financeiro` \((.*?)\) ENGINE=/s';
if (preg_match($padrao, $conteudo, $matches)) {
    $definicao_tabela = $matches[1];
    
    // Adiciona o campo valor_por_documento após o campo taxa_inicial
    $nova_definicao = preg_replace(
        '/(.*?`taxa_inicial` decimal\(10,2\) DEFAULT NULL,)/s',
        '$1' . "\n  `valor_por_documento` decimal(10,2) DEFAULT NULL,",
        $definicao_tabela
    );
    
    // Substitui a definição antiga pela nova
    $conteudo = str_replace($definicao_tabela, $nova_definicao, $conteudo);
    
    // Salva o arquivo atualizado
    $arquivo_atualizado = 'u682219090_faciencia_erp_atualizado.sql';
    file_put_contents($arquivo_atualizado, $conteudo);
    
    echo "<h1>Atualização concluída</h1>";
    echo "<p>O arquivo <strong>{$arquivo_atualizado}</strong> foi gerado com sucesso!</p>";
    echo "<p>Tamanho do arquivo: " . number_format(filesize($arquivo_atualizado) / 1024, 2) . " KB</p>";
    echo "<p><a href='{$arquivo_atualizado}' download>Baixar arquivo SQL</a></p>";
} else {
    echo "<h1>Erro</h1>";
    echo "<p>Não foi possível encontrar a definição da tabela polos_financeiro no arquivo SQL.</p>";
}
?>
