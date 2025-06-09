<?php
// Inicializa o sistema
require_once __DIR__ . '/../../includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica se o usuário tem permissão para acessar o módulo financeiro
if (!Auth::hasPermission('financeiro', 'criar')) {
    setMensagem('erro', 'Você não tem permissão para criar plano de contas.');
    redirect('../index.php');
    exit;
}

// Instancia o banco de dados
$db = Database::getInstance();

// Verifica se a tabela plano_contas existe
try {
    $tabelas = $db->fetchAll("SHOW TABLES LIKE 'plano_contas'");
    
    if (empty($tabelas)) {
        // Cria a tabela se não existir
        $sql = "CREATE TABLE IF NOT EXISTS plano_contas (
            id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            codigo VARCHAR(20) NOT NULL,
            descricao VARCHAR(255) NOT NULL,
            tipo ENUM('receita', 'despesa', 'ambos') NOT NULL DEFAULT 'ambos',
            status ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
            created_at TIMESTAMP NULL DEFAULT NULL,
            updated_at TIMESTAMP NULL DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY (codigo)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        $db->execute($sql);
        echo "<p>Tabela plano_contas criada com sucesso.</p>";
    }
} catch (Exception $e) {
    echo "<p>Erro ao verificar/criar tabela plano_contas: " . $e->getMessage() . "</p>";
}

// Plano de contas padrão
$plano_contas = [
    // Receitas
    ['codigo' => '1.1.1', 'descricao' => 'Mensalidades', 'tipo' => 'receita'],
    ['codigo' => '1.1.2', 'descricao' => 'Matrículas', 'tipo' => 'receita'],
    ['codigo' => '1.1.3', 'descricao' => 'Taxas de Inscrição', 'tipo' => 'receita'],
    ['codigo' => '1.1.4', 'descricao' => 'Material Didático', 'tipo' => 'receita'],
    ['codigo' => '1.1.5', 'descricao' => 'Certificados e Documentos', 'tipo' => 'receita'],
    ['codigo' => '1.1.6', 'descricao' => 'Multas e Juros', 'tipo' => 'receita'],
    ['codigo' => '1.1.7', 'descricao' => 'Outras Receitas', 'tipo' => 'receita'],
    
    // Despesas
    ['codigo' => '2.1.1', 'descricao' => 'Folha de Pagamento', 'tipo' => 'despesa'],
    ['codigo' => '2.1.2', 'descricao' => 'Encargos Sociais', 'tipo' => 'despesa'],
    ['codigo' => '2.2.1', 'descricao' => 'Aluguel', 'tipo' => 'despesa'],
    ['codigo' => '2.2.2', 'descricao' => 'Água e Energia', 'tipo' => 'despesa'],
    ['codigo' => '2.2.3', 'descricao' => 'Internet e Telefone', 'tipo' => 'despesa'],
    ['codigo' => '2.3.1', 'descricao' => 'Material de Escritório', 'tipo' => 'despesa'],
    ['codigo' => '2.3.2', 'descricao' => 'Material de Limpeza', 'tipo' => 'despesa'],
    ['codigo' => '2.4.1', 'descricao' => 'Manutenção', 'tipo' => 'despesa'],
    ['codigo' => '2.5.1', 'descricao' => 'Marketing e Publicidade', 'tipo' => 'despesa'],
    ['codigo' => '2.6.1', 'descricao' => 'Impostos e Taxas', 'tipo' => 'despesa'],
    ['codigo' => '2.7.1', 'descricao' => 'Despesas Bancárias', 'tipo' => 'despesa'],
    ['codigo' => '2.8.1', 'descricao' => 'Outras Despesas', 'tipo' => 'despesa']
];

// Conta quantos planos de contas já existem
$sql = "SELECT COUNT(*) as total FROM plano_contas";
$resultado = $db->fetchOne($sql);
$total_existente = $resultado['total'] ?? 0;

// Inicia a transação
$db->beginTransaction();

try {
    // Insere os planos de contas
    $planos_inseridos = 0;
    
    foreach ($plano_contas as $plano) {
        // Adiciona timestamps
        $plano['created_at'] = date('Y-m-d H:i:s');
        $plano['updated_at'] = date('Y-m-d H:i:s');
        
        // Verifica se o plano já existe
        $sql = "SELECT id FROM plano_contas WHERE codigo = ?";
        $existe = $db->fetchOne($sql, [$plano['codigo']]);
        
        if (!$existe) {
            // Insere o plano
            $db->insert('plano_contas', $plano);
            $planos_inseridos++;
        }
    }
    
    // Confirma a transação
    $db->commit();
    
    // Exibe mensagem de sucesso
    echo "<div style='background-color: #d1fae5; color: #065f46; padding: 15px; margin: 20px; border-radius: 5px;'>";
    echo "<h2>Plano de Contas</h2>";
    echo "<p>Total de planos existentes: $total_existente</p>";
    echo "<p>Planos inseridos: $planos_inseridos</p>";
    echo "<p>Total de planos agora: " . ($total_existente + $planos_inseridos) . "</p>";
    echo "<p><a href='../mensalidades.php?action=nova' style='color: #065f46; text-decoration: underline;'>Voltar para Nova Mensalidade</a></p>";
    echo "</div>";
    
} catch (Exception $e) {
    // Desfaz a transação em caso de erro
    $db->rollBack();
    
    // Exibe mensagem de erro
    echo "<div style='background-color: #fee2e2; color: #b91c1c; padding: 15px; margin: 20px; border-radius: 5px;'>";
    echo "<h2>Erro ao criar plano de contas</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<p><a href='../mensalidades.php?action=nova' style='color: #b91c1c; text-decoration: underline;'>Voltar para Nova Mensalidade</a></p>";
    echo "</div>";
}
?>
