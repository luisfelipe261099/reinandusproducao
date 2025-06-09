<?php
// Inicializa o sistema
require_once __DIR__ . '/../../includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica se o usuário tem permissão para acessar o módulo financeiro
if (!Auth::hasPermission('financeiro', 'criar')) {
    setMensagem('erro', 'Você não tem permissão para criar categorias financeiras.');
    redirect('../index.php');
    exit;
}

// Instancia o banco de dados
$db = Database::getInstance();

// Categorias de receita padrão
$categorias_receita = [
    ['nome' => 'Mensalidade', 'tipo' => 'receita'],
    ['nome' => 'Matrícula', 'tipo' => 'receita'],
    ['nome' => 'Taxa de Inscrição', 'tipo' => 'receita'],
    ['nome' => 'Material Didático', 'tipo' => 'receita'],
    ['nome' => 'Certificado', 'tipo' => 'receita'],
    ['nome' => 'Declaração', 'tipo' => 'receita'],
    ['nome' => 'Histórico Escolar', 'tipo' => 'receita'],
    ['nome' => 'Multa por Atraso', 'tipo' => 'receita'],
    ['nome' => 'Outros Serviços', 'tipo' => 'receita']
];

// Categorias de despesa padrão
$categorias_despesa = [
    ['nome' => 'Salários', 'tipo' => 'despesa'],
    ['nome' => 'Aluguel', 'tipo' => 'despesa'],
    ['nome' => 'Água', 'tipo' => 'despesa'],
    ['nome' => 'Energia Elétrica', 'tipo' => 'despesa'],
    ['nome' => 'Internet', 'tipo' => 'despesa'],
    ['nome' => 'Telefone', 'tipo' => 'despesa'],
    ['nome' => 'Material de Escritório', 'tipo' => 'despesa'],
    ['nome' => 'Material de Limpeza', 'tipo' => 'despesa'],
    ['nome' => 'Manutenção', 'tipo' => 'despesa'],
    ['nome' => 'Marketing', 'tipo' => 'despesa'],
    ['nome' => 'Impostos', 'tipo' => 'despesa'],
    ['nome' => 'Taxas Bancárias', 'tipo' => 'despesa'],
    ['nome' => 'Outras Despesas', 'tipo' => 'despesa']
];

// Combina todas as categorias
$categorias = array_merge($categorias_receita, $categorias_despesa);

// Conta quantas categorias já existem
$sql = "SELECT COUNT(*) as total FROM categorias_financeiras";
$resultado = $db->fetchOne($sql);
$total_existente = $resultado['total'] ?? 0;

// Inicia a transação
$db->beginTransaction();

try {
    // Insere as categorias
    $categorias_inseridas = 0;
    
    foreach ($categorias as $categoria) {
        // Verifica se a categoria já existe
        $sql = "SELECT id FROM categorias_financeiras WHERE nome = ? AND tipo = ?";
        $existe = $db->fetchOne($sql, [$categoria['nome'], $categoria['tipo']]);
        
        if (!$existe) {
            // Insere a categoria
            $db->insert('categorias_financeiras', $categoria);
            $categorias_inseridas++;
        }
    }
    
    // Confirma a transação
    $db->commit();
    
    // Exibe mensagem de sucesso
    echo "<div style='background-color: #d1fae5; color: #065f46; padding: 15px; margin: 20px; border-radius: 5px;'>";
    echo "<h2>Categorias Financeiras</h2>";
    echo "<p>Total de categorias existentes: $total_existente</p>";
    echo "<p>Categorias inseridas: $categorias_inseridas</p>";
    echo "<p>Total de categorias agora: " . ($total_existente + $categorias_inseridas) . "</p>";
    echo "<p><a href='../mensalidades.php?action=nova' style='color: #065f46; text-decoration: underline;'>Voltar para Nova Mensalidade</a></p>";
    echo "</div>";
    
} catch (Exception $e) {
    // Desfaz a transação em caso de erro
    $db->rollBack();
    
    // Exibe mensagem de erro
    echo "<div style='background-color: #fee2e2; color: #b91c1c; padding: 15px; margin: 20px; border-radius: 5px;'>";
    echo "<h2>Erro ao criar categorias financeiras</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<p><a href='../mensalidades.php?action=nova' style='color: #b91c1c; text-decoration: underline;'>Voltar para Nova Mensalidade</a></p>";
    echo "</div>";
}
?>
