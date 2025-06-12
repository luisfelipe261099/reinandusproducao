<?php
// Verificação rápida da instalação do módulo financeiro
include '../includes/config.php';

echo "<h2>Verificação da Instalação do Módulo Financeiro</h2>";

// Lista das tabelas que devem existir
$tabelas_necessarias = [
    'categorias_financeiras',
    'contas_bancarias', 
    'funcionarios',
    'contas_pagar',
    'contas_receber',
    'transacoes_financeiras',
    'folha_pagamento',
    'mensalidades_alunos',
    'cobranca_polos',
    'boletos',
    'configuracoes_financeiras'
];

echo "<h3>1. Verificando Tabelas:</h3>";
echo "<ul>";

$tabelas_ok = 0;
foreach ($tabelas_necessarias as $tabela) {
    $query = "SHOW TABLES LIKE '$tabela'";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) > 0) {
        echo "<li style='color: green;'>✓ $tabela - OK</li>";
        $tabelas_ok++;
    } else {
        echo "<li style='color: red;'>✗ $tabela - NÃO ENCONTRADA</li>";
    }
}
echo "</ul>";

echo "<h3>2. Verificando Configurações:</h3>";
if ($tabelas_ok == count($tabelas_necessarias)) {
    $query = "SELECT * FROM configuracoes_financeiras WHERE chave = 'modulo_ativo'";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $config = mysqli_fetch_assoc($result);
        if ($config['valor'] == '1') {
            echo "<p style='color: green;'>✓ Módulo financeiro está ATIVO</p>";
            
            // Verificar dados básicos
            $query_categorias = "SELECT COUNT(*) as total FROM categorias_financeiras";
            $result_categorias = mysqli_query($conn, $query_categorias);
            $categorias = mysqli_fetch_assoc($result_categorias);
            
            $query_contas = "SELECT COUNT(*) as total FROM contas_bancarias";
            $result_contas = mysqli_query($conn, $query_contas);
            $contas = mysqli_fetch_assoc($result_contas);
            
            echo "<p>📊 Categorias cadastradas: " . $categorias['total'] . "</p>";
            echo "<p>🏦 Contas bancárias: " . $contas['total'] . "</p>";
            
        } else {
            echo "<p style='color: orange;'>⚠ Módulo financeiro está INATIVO</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Configurações do módulo não encontradas</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Algumas tabelas estão faltando. Execute o SQL primeiro.</p>";
}

echo "<h3>3. Status Final:</h3>";
if ($tabelas_ok == count($tabelas_necessarias)) {
    echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px;'>";
    echo "<h4 style='color: #155724; margin: 0;'>🎉 INSTALAÇÃO CONCLUÍDA COM SUCESSO!</h4>";
    echo "<p style='margin: 5px 0 0 0;'>O módulo financeiro está pronto para uso.</p>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px;'>";
    echo "<h4 style='color: #721c24; margin: 0;'>❌ INSTALAÇÃO INCOMPLETA</h4>";
    echo "<p style='margin: 5px 0 0 0;'>Execute o arquivo setup_modulo_financeiro.sql no seu banco de dados.</p>";
    echo "</div>";
}
?>
