<?php
/**
 * Teste do Módulo Financeiro
 * Verifica se todas as tabelas foram criadas corretamente
 */

require_once '../includes/init.php';
require_once '../includes/Database.php';

try {
    $db = Database::getInstance();
    
    echo "<h2>🔍 TESTE DO MÓDULO FINANCEIRO</h2>\n";
    echo "<p>Verificando se todas as tabelas foram criadas...</p>\n\n";
    
    $tabelasNecessarias = [
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
    
    $tabelasExistentes = 0;
    echo "<ul>\n";
    
    foreach ($tabelasNecessarias as $tabela) {
        $resultado = $db->fetchOne("SHOW TABLES LIKE ?", [$tabela]);
        if ($resultado) {
            echo "<li>✅ {$tabela} - OK</li>\n";
            $tabelasExistentes++;
        } else {
            echo "<li>❌ {$tabela} - NÃO ENCONTRADA</li>\n";
        }
    }
    
    echo "</ul>\n\n";
    
    echo "<p><strong>Resultado:</strong> {$tabelasExistentes} de " . count($tabelasNecessarias) . " tabelas encontradas.</p>\n";
    
    if ($tabelasExistentes >= 8) {
        echo "<p style='color: green;'>🎉 <strong>MÓDULO FINANCEIRO CONFIGURADO COM SUCESSO!</strong></p>\n";
        echo "<p><a href='index.php'>➡️ Acessar Dashboard Financeiro</a></p>\n";
    } else {
        echo "<p style='color: red;'>⚠️ <strong>CONFIGURAÇÃO INCOMPLETA!</strong></p>\n";
        echo "<p>Execute o arquivo SQL: <code>sql/setup_modulo_financeiro.sql</code></p>\n";
    }
    
    // Testa também se a tabela boletos tem dados de teste
    if ($tabelasExistentes >= 8) {
        echo "\n<h3>🧪 TESTE DOS BOLETOS</h3>\n";
        $boletos = $db->fetchAll("SELECT COUNT(*) as total FROM boletos");
        $totalBoletos = $boletos[0]['total'] ?? 0;
        echo "<p>Total de boletos no sistema: <strong>{$totalBoletos}</strong></p>\n";
        
        if ($totalBoletos > 0) {
            $ultimosBoletos = $db->fetchAll("SELECT id, descricao, valor, status, created_at FROM boletos ORDER BY created_at DESC LIMIT 5");
            echo "<p>Últimos boletos criados:</p>\n";
            echo "<ul>\n";
            foreach ($ultimosBoletos as $boleto) {
                echo "<li>#{$boleto['id']} - {$boleto['descricao']} - R$ " . number_format($boleto['valor'], 2, ',', '.') . " - {$boleto['status']} - {$boleto['created_at']}</li>\n";
            }
            echo "</ul>\n";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>ERRO:</strong> " . $e->getMessage() . "</p>\n";
}
?>
