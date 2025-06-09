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

// Verifica a estrutura da tabela polos_financeiro
echo "<h1>Estrutura da tabela polos_financeiro</h1>";
try {
    $sql = "DESCRIBE polos_financeiro";
    $estrutura = $db->fetchAll($sql);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Chave</th><th>Padrão</th><th>Extra</th></tr>";
    
    foreach ($estrutura as $campo) {
        echo "<tr>";
        echo "<td>{$campo['Field']}</td>";
        echo "<td>{$campo['Type']}</td>";
        echo "<td>{$campo['Null']}</td>";
        echo "<td>{$campo['Key']}</td>";
        echo "<td>{$campo['Default']}</td>";
        echo "<td>{$campo['Extra']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Erro ao verificar a estrutura da tabela: " . $e->getMessage() . "</p>";
}

// Verifica se o campo valor_por_documento existe
echo "<h1>Verificação do campo valor_por_documento</h1>";
try {
    $sql = "SELECT COUNT(*) as total FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'polos_financeiro' 
            AND COLUMN_NAME = 'valor_por_documento'";
    $resultado = $db->fetchOne($sql);
    
    if ($resultado && $resultado['total'] > 0) {
        echo "<p style='color: green;'>O campo valor_por_documento existe na tabela polos_financeiro.</p>";
    } else {
        echo "<p style='color: red;'>O campo valor_por_documento NÃO existe na tabela polos_financeiro.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Erro ao verificar o campo: " . $e->getMessage() . "</p>";
}

// Verifica os dados da tabela polos_financeiro
echo "<h1>Dados da tabela polos_financeiro</h1>";
try {
    $sql = "SELECT * FROM polos_financeiro LIMIT 5";
    $dados = $db->fetchAll($sql);
    
    if (empty($dados)) {
        echo "<p>Nenhum registro encontrado na tabela polos_financeiro.</p>";
    } else {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr>";
        foreach (array_keys($dados[0]) as $coluna) {
            echo "<th>{$coluna}</th>";
        }
        echo "</tr>";
        
        foreach ($dados as $registro) {
            echo "<tr>";
            foreach ($registro as $valor) {
                echo "<td>" . (is_null($valor) ? 'NULL' : htmlspecialchars($valor)) . "</td>";
            }
            echo "</tr>";
        }
        
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Erro ao verificar os dados da tabela: " . $e->getMessage() . "</p>";
}
?>
