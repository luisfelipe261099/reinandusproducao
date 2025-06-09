<?php
// Incluir configurações do banco de dados
require_once 'config/database.php';

// Obter instância do banco de dados
$db = Database::getInstance();

// Verificar estrutura da tabela polos
echo "<h2>Estrutura da tabela polos</h2>";
$colunas = $db->fetchAll("SHOW COLUMNS FROM polos");
echo "<pre>";
print_r($colunas);
echo "</pre>";

// Verificar se existe o campo mec
$campo_mec_existe = false;
foreach ($colunas as $coluna) {
    if ($coluna['Field'] === 'mec') {
        $campo_mec_existe = true;
        break;
    }
}

if ($campo_mec_existe) {
    echo "<p>O campo 'mec' já existe na tabela polos.</p>";
} else {
    echo "<p>O campo 'mec' não existe na tabela polos.</p>";
}
