<?php
/**
 * Verificação Completa do Módulo Financeiro
 * 
 * Este script verifica se o módulo financeiro está devidamente configurado
 * e faz diagnósticos detalhados dos possíveis problemas.
 */

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/Database.php';

$db = Database::getInstance();

echo "=== DIAGNÓSTICO COMPLETO DO MÓDULO FINANCEIRO ===\n\n";

$problemas = [];
$sucessos = [];

// 1. Verificar se a tabela boletos existe
echo "1. Verificando existência da tabela 'boletos':\n";
try {
    $tableExists = $db->fetchOne("SHOW TABLES LIKE 'boletos'");
    if ($tableExists) {
        $sucessos[] = "✓ Tabela 'boletos' existe";
        echo "   ✓ Tabela 'boletos' existe\n";
        
        // Verificar estrutura da tabela
        $columns = $db->fetchAll("SHOW COLUMNS FROM boletos");
        $colunas_encontradas = array_column($columns, 'Field');
        
        // Colunas obrigatórias da nova estrutura
        $colunas_necessarias = ['tipo', 'referencia_id', 'ambiente', 'banco', 'carteira'];
        $colunas_faltando = array_diff($colunas_necessarias, $colunas_encontradas);
        
        if (empty($colunas_faltando)) {
            $sucessos[] = "✓ Estrutura da tabela 'boletos' está atualizada";
            echo "   ✓ Estrutura da tabela está atualizada\n";
        } else {
            $problemas[] = "❌ Estrutura da tabela 'boletos' está desatualizada";
            echo "   ❌ Colunas faltando: " . implode(', ', $colunas_faltando) . "\n";
            echo "   ➤ Execute o script: sql/migrar_boletos_compatibilidade.sql\n";
        }
        
        // Verificar se tem dados
        $count = $db->fetchOne("SELECT COUNT(*) as total FROM boletos");
        echo "   📊 Total de boletos: " . $count['total'] . "\n";
        
    } else {
        $problemas[] = "❌ Tabela 'boletos' não existe";
        echo "   ❌ Tabela 'boletos' não existe\n";
        echo "   ➤ Execute o script: sql/setup_modulo_financeiro.sql\n";
    }
} catch (Exception $e) {
    $problemas[] = "❌ Erro ao verificar tabela 'boletos': " . $e->getMessage();
    echo "   ❌ Erro: " . $e->getMessage() . "\n";
}

echo "\n";

// 2. Verificar tabela de configurações financeiras
echo "2. Verificando tabela 'configuracoes_financeiras':\n";
try {
    $tableExists = $db->fetchOne("SHOW TABLES LIKE 'configuracoes_financeiras'");
    if ($tableExists) {
        $sucessos[] = "✓ Tabela 'configuracoes_financeiras' existe";
        echo "   ✓ Tabela 'configuracoes_financeiras' existe\n";
        
        // Verificar se tem configurações básicas
        $config = $db->fetchOne("SELECT COUNT(*) as total FROM configuracoes_financeiras WHERE chave = 'modulo_ativo'");
        if ($config && $config['total'] > 0) {
            $modulo_ativo = $db->fetchOne("SELECT valor FROM configuracoes_financeiras WHERE chave = 'modulo_ativo'");
            if ($modulo_ativo && $modulo_ativo['valor'] == '1') {
                $sucessos[] = "✓ Módulo financeiro está ativado";
                echo "   ✓ Módulo financeiro está ativado\n";
            } else {
                $problemas[] = "⚠️ Módulo financeiro está desativado";
                echo "   ⚠️ Módulo financeiro está desativado\n";
            }
        } else {
            $problemas[] = "❌ Configurações básicas não encontradas";
            echo "   ❌ Configurações básicas não encontradas\n";
            echo "   ➤ Execute o script: sql/setup_modulo_financeiro.sql\n";
        }
        
        // Listar todas as configurações
        $configs = $db->fetchAll("SELECT chave, valor, grupo FROM configuracoes_financeiras ORDER BY grupo, chave");
        echo "   📋 Configurações encontradas:\n";
        foreach ($configs as $cfg) {
            echo "      - {$cfg['chave']} = {$cfg['valor']} ({$cfg['grupo']})\n";
        }
        
    } else {
        $problemas[] = "❌ Tabela 'configuracoes_financeiras' não existe";
        echo "   ❌ Tabela 'configuracoes_financeiras' não existe\n";
        echo "   ➤ Execute o script: sql/setup_modulo_financeiro.sql\n";
    }
} catch (Exception $e) {
    $problemas[] = "❌ Erro ao verificar configurações: " . $e->getMessage();
    echo "   ❌ Erro: " . $e->getMessage() . "\n";
}

echo "\n";

// 3. Verificar outras tabelas do módulo financeiro
echo "3. Verificando outras tabelas do módulo financeiro:\n";
$tabelas_financeiras = [
    'categorias_financeiras' => 'Categorias de receitas e despesas',
    'contas_bancarias' => 'Contas bancárias',
    'funcionarios' => 'Cadastro de funcionários',
    'contas_pagar' => 'Contas a pagar',
    'contas_receber' => 'Contas a receber',
    'transacoes_financeiras' => 'Transações financeiras',
    'folha_pagamento' => 'Folha de pagamento',
    'mensalidades_alunos' => 'Mensalidades de alunos',
    'cobranca_polos' => 'Cobrança de polos'
];

foreach ($tabelas_financeiras as $tabela => $descricao) {
    try {
        $exists = $db->fetchOne("SHOW TABLES LIKE '$tabela'");
        if ($exists) {
            $count = $db->fetchOne("SELECT COUNT(*) as total FROM $tabela");
            $sucessos[] = "✓ Tabela '$tabela' existe";
            echo "   ✓ $descricao: " . $count['total'] . " registros\n";
        } else {
            $problemas[] = "⚠️ Tabela '$tabela' não existe";
            echo "   ⚠️ $descricao: tabela não existe\n";
        }
    } catch (Exception $e) {
        $problemas[] = "❌ Erro ao verificar '$tabela': " . $e->getMessage();
        echo "   ❌ Erro ao verificar '$tabela': " . $e->getMessage() . "\n";
    }
}

echo "\n";

// 4. Verificar compatibilidade do código boletos.php
echo "4. Verificando compatibilidade do código:\n";
try {
    // Testar a query que o boletos.php usa
    $sql = "SELECT b.*,
               CASE
                   WHEN b.tipo = 'mensalidade' THEN a.nome
                   WHEN b.tipo = 'polo' THEN p.nome
                   ELSE b.nome_pagador
               END as pagador_nome
        FROM boletos b
        LEFT JOIN alunos a ON b.tipo = 'mensalidade' AND b.referencia_id = a.id
        LEFT JOIN polos p ON b.tipo = 'polo' AND b.referencia_id = p.id
        ORDER BY b.created_at DESC
        LIMIT 1";
    
    $teste = $db->fetchOne($sql);
    $sucessos[] = "✓ Query do boletos.php está funcionando";
    echo "   ✓ Query do boletos.php está funcionando\n";
    
} catch (Exception $e) {
    $problemas[] = "❌ Query do boletos.php falhou: " . $e->getMessage();
    echo "   ❌ Query do boletos.php falhou: " . $e->getMessage() . "\n";
    echo "   ➤ Problema na estrutura da tabela boletos\n";
}

echo "\n";

// 5. RESUMO FINAL
echo "=== RESUMO FINAL ===\n\n";

if (empty($problemas)) {
    echo "🎉 MÓDULO FINANCEIRO ESTÁ 100% FUNCIONAL!\n";
    echo "Todos os testes passaram com sucesso.\n\n";
} else {
    echo "⚠️ PROBLEMAS ENCONTRADOS:\n";
    foreach ($problemas as $problema) {
        echo "$problema\n";
    }
    echo "\n";
}

echo "✅ SUCESSOS:\n";
foreach ($sucessos as $sucesso) {
    echo "$sucesso\n";
}

echo "\n";

// 6. RECOMENDAÇÕES
echo "=== RECOMENDAÇÕES ===\n\n";

if (in_array("❌ Estrutura da tabela 'boletos' está desatualizada", $problemas)) {
    echo "🔧 AÇÃO PRIORITÁRIA:\n";
    echo "1. Execute o script: sql/migrar_boletos_compatibilidade.sql\n";
    echo "   Este script migra a estrutura atual da tabela boletos para a nova estrutura.\n\n";
}

if (in_array("❌ Tabela 'configuracoes_financeiras' não existe", $problemas)) {
    echo "🔧 AÇÃO NECESSÁRIA:\n";
    echo "1. Execute o script: sql/setup_modulo_financeiro.sql\n";
    echo "   Este script cria todas as tabelas necessárias do módulo financeiro.\n\n";
}

if (empty($problemas)) {
    echo "✨ O módulo financeiro está pronto para uso!\n";
    echo "Você pode acessar: financeiro/boletos.php\n";
}

echo "\n=== FIM DO DIAGNÓSTICO ===\n";
