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

// Obtém a estrutura da tabela polos_financeiro
try {
    $sql = "SHOW CREATE TABLE polos_financeiro";
    $resultado = $db->fetchOne($sql);
    $estrutura = $resultado['Create Table'] ?? '';

    echo "<h1>Estrutura da tabela polos_financeiro</h1>";
    echo "<pre>" . htmlspecialchars($estrutura) . "</pre>";

    // Verifica se os campos necessários existem
    $sql = "DESCRIBE polos_financeiro";
    $colunas = $db->fetchAll($sql);

    echo "<h2>Colunas da tabela polos_financeiro</h2>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Chave</th><th>Padrão</th><th>Extra</th></tr>";

    $campos_necessarios = [
        'id', 'polo_id', 'tipo_polo_id', 'data_inicial', 'vigencia_contrato_meses',
        'vencimento_contrato', 'vigencia_pacote_setup', 'vencimento_pacote_setup',
        'pacotes_adquiridos', 'documentos_disponiveis', 'valor_unitario_normal',
        'quantidade_contratada', 'data_primeira_parcela', 'data_ultima_parcela',
        'quantidade_parcelas', 'valor_previsto', 'observacoes'
    ];

    $campos_existentes = [];

    foreach ($colunas as $coluna) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($coluna['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($coluna['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($coluna['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($coluna['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($coluna['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($coluna['Extra']) . "</td>";
        echo "</tr>";

        $campos_existentes[] = $coluna['Field'];
    }

    echo "</table>";

    // Verifica campos faltantes
    $campos_faltantes = array_diff($campos_necessarios, $campos_existentes);

    if (!empty($campos_faltantes)) {
        echo "<h2>Campos faltantes</h2>";
        echo "<ul>";
        foreach ($campos_faltantes as $campo) {
            echo "<li>" . htmlspecialchars($campo) . "</li>";
        }
        echo "</ul>";

        // Sugere SQL para adicionar os campos faltantes
        echo "<h2>SQL para adicionar campos faltantes</h2>";
        echo "<pre>";

        foreach ($campos_faltantes as $campo) {
            $tipo = '';
            switch ($campo) {
                case 'data_inicial':
                case 'vencimento_contrato':
                case 'vencimento_pacote_setup':
                case 'data_primeira_parcela':
                case 'data_ultima_parcela':
                    $tipo = 'DATE NULL';
                    break;
                case 'vigencia_contrato_meses':
                case 'vigencia_pacote_setup':
                case 'pacotes_adquiridos':
                case 'documentos_disponiveis':
                case 'quantidade_contratada':
                case 'quantidade_parcelas':
                    $tipo = 'INT NULL';
                    break;
                case 'valor_unitario_normal':
                case 'valor_previsto':
                    $tipo = 'DECIMAL(10,2) NULL';
                    break;
                case 'observacoes':
                    $tipo = 'TEXT NULL';
                    break;
                default:
                    $tipo = 'VARCHAR(255) NULL';
            }

            echo "ALTER TABLE polos_financeiro ADD COLUMN {$campo} {$tipo};\n";
        }

        echo "</pre>";
    } else {
        echo "<h2>Todos os campos necessários existem na tabela</h2>";
    }

} catch (Exception $e) {
    echo "<h1>Erro ao verificar estrutura da tabela</h1>";
    echo "<p>Mensagem: " . htmlspecialchars($e->getMessage()) . "</p>";

    // Verifica se a tabela existe
    try {
        $sql = "SHOW TABLES LIKE 'polos_financeiro'";
        $tabela_existe = $db->fetchOne($sql);

        if (!$tabela_existe) {
            echo "<h2>A tabela polos_financeiro não existe</h2>";
            echo "<h3>SQL para criar a tabela</h3>";
            echo "<pre>
CREATE TABLE `polos_financeiro` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `polo_id` int(11) NOT NULL,
  `tipo_polo_id` int(11) NOT NULL,
  `data_inicial` date DEFAULT NULL,
  `vigencia_contrato_meses` int(11) DEFAULT NULL,
  `vencimento_contrato` date DEFAULT NULL,
  `vigencia_pacote_setup` int(11) DEFAULT NULL,
  `vencimento_pacote_setup` date DEFAULT NULL,
  `pacotes_adquiridos` int(11) DEFAULT NULL,
  `documentos_disponiveis` int(11) DEFAULT NULL,
  `valor_unitario_normal` decimal(10,2) DEFAULT NULL,
  `quantidade_contratada` int(11) DEFAULT NULL,
  `data_primeira_parcela` date DEFAULT NULL,
  `data_ultima_parcela` date DEFAULT NULL,
  `quantidade_parcelas` int(11) DEFAULT NULL,
  `valor_previsto` decimal(10,2) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `polo_id` (`polo_id`),
  KEY `tipo_polo_id` (`tipo_polo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            </pre>";
        }
    } catch (Exception $innerEx) {
        echo "<p>Erro adicional: " . htmlspecialchars($innerEx->getMessage()) . "</p>";
    }
}

// Verifica os dados da tabela polos_financeiro
$sql = "SELECT * FROM polos_financeiro LIMIT 5";
$dados = $db->fetchAll($sql);

echo "<h2>Dados da tabela polos_financeiro</h2>";
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
?>
