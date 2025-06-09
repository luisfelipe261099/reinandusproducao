<?php
/**
 * Script para verificar o problema com a página documentos_pessoais.php
 */

// Inicializa o sistema
require_once 'includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Instancia o banco de dados
$db = Database::getInstance();

try {
    echo "<h1>Verificação de Tabelas e Dados</h1>";
    
    // Verifica se a tabela tipos_documentos_pessoais existe
    $result = $db->query("SHOW TABLES LIKE 'tipos_documentos_pessoais'");
    
    if (!$result || count($result) === 0) {
        echo "<p style='color: red;'>A tabela 'tipos_documentos_pessoais' NÃO existe no banco de dados.</p>";
    } else {
        echo "<p style='color: green;'>A tabela 'tipos_documentos_pessoais' existe no banco de dados.</p>";
        
        // Verifica se há registros na tabela
        $count = $db->fetchOne("SELECT COUNT(*) as total FROM tipos_documentos_pessoais");
        echo "<p>Total de registros na tabela 'tipos_documentos_pessoais': " . $count['total'] . "</p>";
        
        // Lista os registros
        $tipos = $db->fetchAll("SELECT * FROM tipos_documentos_pessoais");
        echo "<h2>Registros na tabela 'tipos_documentos_pessoais':</h2>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Nome</th><th>Descrição</th><th>Obrigatório</th><th>Status</th></tr>";
        foreach ($tipos as $tipo) {
            echo "<tr>";
            echo "<td>" . $tipo['id'] . "</td>";
            echo "<td>" . htmlspecialchars($tipo['nome']) . "</td>";
            echo "<td>" . htmlspecialchars($tipo['descricao']) . "</td>";
            echo "<td>" . ($tipo['obrigatorio'] ? 'Sim' : 'Não') . "</td>";
            echo "<td>" . $tipo['status'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Verifica se a tabela documentos_alunos existe
    $result = $db->query("SHOW TABLES LIKE 'documentos_alunos'");
    
    if (!$result || count($result) === 0) {
        echo "<p style='color: red;'>A tabela 'documentos_alunos' NÃO existe no banco de dados.</p>";
    } else {
        echo "<p style='color: green;'>A tabela 'documentos_alunos' existe no banco de dados.</p>";
        
        // Verifica se há registros na tabela
        $count = $db->fetchOne("SELECT COUNT(*) as total FROM documentos_alunos");
        echo "<p>Total de registros na tabela 'documentos_alunos': " . $count['total'] . "</p>";
    }
    
    // Verifica se o arquivo documentos_pessoais.php existe
    if (file_exists('documentos_pessoais.php')) {
        echo "<p style='color: green;'>O arquivo 'documentos_pessoais.php' existe na raiz do projeto.</p>";
    } else {
        echo "<p style='color: red;'>O arquivo 'documentos_pessoais.php' NÃO existe na raiz do projeto.</p>";
    }
    
    // Verifica se o arquivo .htaccess existe e seu conteúdo
    if (file_exists('.htaccess')) {
        echo "<p style='color: green;'>O arquivo '.htaccess' existe na raiz do projeto.</p>";
        echo "<h2>Conteúdo do arquivo .htaccess:</h2>";
        echo "<pre>" . htmlspecialchars(file_get_contents('.htaccess')) . "</pre>";
    } else {
        echo "<p style='color: red;'>O arquivo '.htaccess' NÃO existe na raiz do projeto.</p>";
    }
    
    // Verifica as permissões do usuário
    echo "<h2>Informações do Usuário:</h2>";
    echo "<p>Tipo de usuário: " . getUsuarioTipo() . "</p>";
    echo "<p>ID do usuário: " . getUsuarioId() . "</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Erro: " . $e->getMessage() . "</p>";
}
?>
