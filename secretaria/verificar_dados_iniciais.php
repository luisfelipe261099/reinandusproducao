<?php
// Inclui o arquivo de configuração
require_once 'config.php';

// Ativa o modo de exibição de erros para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Obtém uma instância do banco de dados
$db = Database::getInstance();

echo "<h1>Verificação de Dados Iniciais</h1>";

// Verifica os tipos de polos
echo "<h2>Tipos de Polos</h2>";
$sql = "SELECT * FROM tipos_polos ORDER BY nome ASC";
$tipos_polos = $db->fetchAll($sql);

if (empty($tipos_polos)) {
    echo "<p style='color: red;'>Não há tipos de polos cadastrados. Execute o script SQL para inserir os dados iniciais.</p>";
    
    // Mostra o SQL para inserir os tipos de polos
    echo "<h3>SQL para inserir os tipos de polos:</h3>";
    echo "<pre>";
    echo "INSERT INTO `tipos_polos` (`nome`, `descricao`, `status`, `created_at`, `updated_at`) VALUES 
('Graduação', 'Polo para cursos de graduação', 'ativo', NOW(), NOW()),
('Pós-Graduação', 'Polo para cursos de pós-graduação', 'ativo', NOW(), NOW()),
('Extensão', 'Polo para cursos de extensão', 'ativo', NOW(), NOW());";
    echo "</pre>";
} else {
    echo "<p style='color: green;'>Existem " . count($tipos_polos) . " tipos de polos cadastrados:</p>";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Nome</th><th>Descrição</th><th>Status</th></tr>";
    
    foreach ($tipos_polos as $tipo) {
        echo "<tr>";
        echo "<td>" . $tipo['id'] . "</td>";
        echo "<td>" . htmlspecialchars($tipo['nome']) . "</td>";
        echo "<td>" . htmlspecialchars($tipo['descricao']) . "</td>";
        echo "<td>" . $tipo['status'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
}

// Verifica as configurações financeiras
echo "<h2>Configurações Financeiras</h2>";
$sql = "SELECT tpf.*, tp.nome as tipo_nome 
        FROM tipos_polos_financeiro tpf 
        JOIN tipos_polos tp ON tpf.tipo_polo_id = tp.id
        ORDER BY tp.nome ASC";
$configuracoes = $db->fetchAll($sql);

if (empty($configuracoes)) {
    echo "<p style='color: red;'>Não há configurações financeiras cadastradas. Execute o script SQL para inserir os dados iniciais.</p>";
    
    // Mostra o SQL para inserir as configurações financeiras
    echo "<h3>SQL para inserir as configurações financeiras:</h3>";
    echo "<pre>";
    echo "INSERT INTO `tipos_polos_financeiro` (`tipo_polo_id`, `taxa_inicial`, `taxa_por_documento`, `pacote_documentos`, `valor_pacote`, `descricao`, `created_at`, `updated_at`) VALUES 
((SELECT id FROM tipos_polos WHERE nome = 'Graduação'), 1000.00, 5.00, 0, 0.00, 'Taxa inicial + valor por documento emitido', NOW(), NOW()),
((SELECT id FROM tipos_polos WHERE nome = 'Pós-Graduação'), 500.00, 3.00, 0, 0.00, 'Taxa inicial + valor por documento emitido', NOW(), NOW()),
((SELECT id FROM tipos_polos WHERE nome = 'Extensão'), 0.00, 0.00, 50, 200.00, 'Pacote de documentos', NOW(), NOW());";
    echo "</pre>";
} else {
    echo "<p style='color: green;'>Existem " . count($configuracoes) . " configurações financeiras cadastradas:</p>";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>Tipo de Polo</th><th>Taxa Inicial</th><th>Taxa por Documento</th><th>Pacote de Documentos</th><th>Valor do Pacote</th><th>Descrição</th></tr>";
    
    foreach ($configuracoes as $config) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($config['tipo_nome']) . "</td>";
        echo "<td>R$ " . number_format($config['taxa_inicial'], 2, ',', '.') . "</td>";
        echo "<td>R$ " . number_format($config['taxa_por_documento'], 2, ',', '.') . "</td>";
        echo "<td>" . $config['pacote_documentos'] . "</td>";
        echo "<td>R$ " . number_format($config['valor_pacote'], 2, ',', '.') . "</td>";
        echo "<td>" . htmlspecialchars($config['descricao']) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
}

// Verifica se há polos com tipos associados
echo "<h2>Polos com Tipos</h2>";
$sql = "SELECT pt.*, p.nome as polo_nome, tp.nome as tipo_nome
        FROM polos_tipos pt
        JOIN polos p ON pt.polo_id = p.id
        JOIN tipos_polos tp ON pt.tipo_polo_id = tp.id
        ORDER BY p.nome ASC, tp.nome ASC
        LIMIT 10";
$polos_tipos = $db->fetchAll($sql);

if (empty($polos_tipos)) {
    echo "<p style='color: orange;'>Não há polos com tipos associados. Isso é normal se você ainda não cadastrou nenhum polo com os novos tipos.</p>";
} else {
    echo "<p style='color: green;'>Existem " . count($polos_tipos) . " associações entre polos e tipos:</p>";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>Polo</th><th>Tipo de Polo</th></tr>";
    
    foreach ($polos_tipos as $pt) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($pt['polo_nome']) . "</td>";
        echo "<td>" . htmlspecialchars($pt['tipo_nome']) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
}

// Verifica se há informações financeiras de polos
echo "<h2>Informações Financeiras de Polos</h2>";
$sql = "SELECT pf.*, p.nome as polo_nome, tp.nome as tipo_nome
        FROM polos_financeiro pf
        JOIN polos p ON pf.polo_id = p.id
        JOIN tipos_polos tp ON pf.tipo_polo_id = tp.id
        ORDER BY p.nome ASC, tp.nome ASC
        LIMIT 10";
$polos_financeiro = $db->fetchAll($sql);

if (empty($polos_financeiro)) {
    echo "<p style='color: orange;'>Não há informações financeiras de polos. Isso é normal se você ainda não cadastrou nenhum polo com os novos tipos.</p>";
} else {
    echo "<p style='color: green;'>Existem " . count($polos_financeiro) . " registros financeiros de polos:</p>";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>Polo</th><th>Tipo de Polo</th><th>Taxa Inicial Paga</th><th>Pacotes Adquiridos</th><th>Documentos Disponíveis</th><th>Documentos Emitidos</th><th>Valor Total Pago</th></tr>";
    
    foreach ($polos_financeiro as $pf) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($pf['polo_nome']) . "</td>";
        echo "<td>" . htmlspecialchars($pf['tipo_nome']) . "</td>";
        echo "<td>" . ($pf['taxa_inicial_paga'] ? 'Sim' : 'Não') . "</td>";
        echo "<td>" . $pf['pacotes_adquiridos'] . "</td>";
        echo "<td>" . $pf['documentos_disponiveis'] . "</td>";
        echo "<td>" . $pf['documentos_emitidos'] . "</td>";
        echo "<td>R$ " . number_format($pf['valor_total_pago'], 2, ',', '.') . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
}

// Verifica se há histórico financeiro
echo "<h2>Histórico Financeiro</h2>";
$sql = "SELECT pfh.*, p.nome as polo_nome, tp.nome as tipo_nome, u.nome as usuario_nome
        FROM polos_financeiro_historico pfh
        JOIN polos p ON pfh.polo_id = p.id
        JOIN tipos_polos tp ON pfh.tipo_polo_id = tp.id
        LEFT JOIN usuarios u ON pfh.usuario_id = u.id
        ORDER BY pfh.created_at DESC
        LIMIT 10";
$historico = $db->fetchAll($sql);

if (empty($historico)) {
    echo "<p style='color: orange;'>Não há histórico financeiro. Isso é normal se você ainda não realizou nenhuma transação financeira.</p>";
} else {
    echo "<p style='color: green;'>Existem " . count($historico) . " registros no histórico financeiro:</p>";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>Data</th><th>Polo</th><th>Tipo de Polo</th><th>Transação</th><th>Valor</th><th>Quantidade</th><th>Descrição</th><th>Usuário</th></tr>";
    
    $tipos_transacao = [
        'taxa_inicial' => 'Taxa Inicial',
        'pacote' => 'Pacote de Documentos',
        'documento' => 'Documento Emitido',
        'outro' => 'Outro'
    ];
    
    foreach ($historico as $h) {
        echo "<tr>";
        echo "<td>" . date('d/m/Y', strtotime($h['data_transacao'])) . "</td>";
        echo "<td>" . htmlspecialchars($h['polo_nome']) . "</td>";
        echo "<td>" . htmlspecialchars($h['tipo_nome']) . "</td>";
        echo "<td>" . ($tipos_transacao[$h['tipo_transacao']] ?? $h['tipo_transacao']) . "</td>";
        echo "<td>R$ " . number_format($h['valor'], 2, ',', '.') . "</td>";
        echo "<td>" . $h['quantidade'] . "</td>";
        echo "<td>" . htmlspecialchars($h['descricao']) . "</td>";
        echo "<td>" . htmlspecialchars($h['usuario_nome'] ?? 'N/A') . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
}

echo "<h2>Links Úteis</h2>";
echo "<ul>";
echo "<li><a href='polos.php?action=novo'>Cadastrar Novo Polo</a></li>";
echo "<li><a href='polos.php'>Listar Polos</a></li>";
echo "</ul>";
?>
