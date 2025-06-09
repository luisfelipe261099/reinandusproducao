<?php
// Ativa o modo de exibição de erros para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inicializa o sistema
require_once __DIR__ . '/includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Instancia o banco de dados
$db = Database::getInstance();

// Função para obter a estrutura de uma tabela
function obterEstrutura($db, $tabela) {
    $sql = "SHOW CREATE TABLE `{$tabela}`";
    $resultado = $db->fetchOne($sql);
    return $resultado['Create Table'] ?? '';
}

// Função para obter os dados de uma tabela
function obterDados($db, $tabela, $limite = 10) {
    $sql = "SELECT * FROM `{$tabela}` LIMIT {$limite}";
    return $db->fetchAll($sql);
}

// Função para formatar os dados para inserção SQL
function formatarDadosParaSQL($dados) {
    $valores = [];
    foreach ($dados as $linha) {
        $valores_linha = [];
        foreach ($linha as $valor) {
            if ($valor === null) {
                $valores_linha[] = 'NULL';
            } elseif (is_numeric($valor)) {
                $valores_linha[] = $valor;
            } else {
                $valores_linha[] = "'" . addslashes($valor) . "'";
            }
        }
        $valores[] = '(' . implode(', ', $valores_linha) . ')';
    }
    return $valores;
}

// Obtém a lista de tabelas
$sql = "SHOW TABLES";
$tabelas = $db->fetchAll($sql);

// Prepara o arquivo SQL
$sql_file = "-- Exportação da estrutura do banco de dados\n";
$sql_file .= "-- Data: " . date('Y-m-d H:i:s') . "\n\n";
$sql_file .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
$sql_file .= "SET time_zone = \"+00:00\";\n\n";
$sql_file .= "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n";
$sql_file .= "/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\n";
$sql_file .= "/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\n";
$sql_file .= "/*!40101 SET NAMES utf8mb4 */;\n\n";
$sql_file .= "-- --------------------------------------------------------\n\n";

// Adiciona a estrutura de cada tabela
foreach ($tabelas as $tabela) {
    $nome_tabela = reset($tabela);
    $estrutura = obterEstrutura($db, $nome_tabela);
    
    $sql_file .= "-- Estrutura da tabela `{$nome_tabela}`\n";
    $sql_file .= "DROP TABLE IF EXISTS `{$nome_tabela}`;\n";
    $sql_file .= $estrutura . ";\n\n";
    
    // Adiciona alguns dados de exemplo (opcional)
    $dados = obterDados($db, $nome_tabela, 5);
    if (!empty($dados)) {
        $sql_file .= "-- Dados de exemplo para a tabela `{$nome_tabela}`\n";
        $sql_file .= "INSERT INTO `{$nome_tabela}` VALUES\n";
        $sql_file .= implode(",\n", formatarDadosParaSQL($dados)) . ";\n\n";
    }
    
    $sql_file .= "-- --------------------------------------------------------\n\n";
}

// Finaliza o arquivo SQL
$sql_file .= "/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\n";
$sql_file .= "/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\n";
$sql_file .= "/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;\n";

// Salva o arquivo
$arquivo = 'u682219090_faciencia_erp_atualizado.sql';
file_put_contents($arquivo, $sql_file);

echo "<h1>Exportação concluída</h1>";
echo "<p>O arquivo <strong>{$arquivo}</strong> foi gerado com sucesso!</p>";
echo "<p>Tamanho do arquivo: " . number_format(filesize($arquivo) / 1024, 2) . " KB</p>";
echo "<p><a href='{$arquivo}' download>Baixar arquivo SQL</a></p>";
?>
