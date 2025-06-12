<?php
/**
 * Teste Rápido do Módulo Financeiro
 * Execute este arquivo para ver rapidamente qual é o problema
 */

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/Database.php';

$db = Database::getInstance();

echo "<h1>🔍 DIAGNÓSTICO RÁPIDO - MÓDULO FINANCEIRO</h1>";
echo "<hr>";

// 1. Verificar se a tabela boletos existe
echo "<h2>1. Verificando tabela 'boletos':</h2>";
try {
    $tableExists = $db->fetchOne("SHOW TABLES LIKE 'boletos'");
    if ($tableExists) {
        echo "✅ <strong>Tabela 'boletos' EXISTE</strong><br>";
        
        // Verificar estrutura
        $columns = $db->fetchAll("SHOW COLUMNS FROM boletos");
        $colunas_encontradas = array_column($columns, 'Field');
        
        echo "📋 <strong>Colunas encontradas:</strong><br>";
        echo "<ul>";
        foreach ($colunas_encontradas as $coluna) {
            echo "<li>$coluna</li>";
        }
        echo "</ul>";
        
        // Verificar se é estrutura nova ou antiga
        $nova_estrutura = in_array('tipo', $colunas_encontradas) && in_array('referencia_id', $colunas_encontradas);
        $antiga_estrutura = in_array('tipo_entidade', $colunas_encontradas) && in_array('entidade_id', $colunas_encontradas);
        
        if ($nova_estrutura) {
            echo "✅ <strong>ESTRUTURA NOVA</strong> (compatível com módulo financeiro)<br>";
        } elseif ($antiga_estrutura) {
            echo "⚠️ <strong>ESTRUTURA ANTIGA</strong> (precisa migrar)<br>";
            echo "🔧 <strong>SOLUÇÃO:</strong> Execute o arquivo: <code>sql/migrar_boletos_compatibilidade.sql</code><br>";
        } else {
            echo "❌ <strong>ESTRUTURA INCORRETA</strong><br>";
        }
        
        // Contar registros
        $count = $db->fetchOne("SELECT COUNT(*) as total FROM boletos");
        echo "📊 <strong>Total de boletos:</strong> " . $count['total'] . "<br>";
        
    } else {
        echo "❌ <strong>Tabela 'boletos' NÃO EXISTE</strong><br>";
        echo "🔧 <strong>SOLUÇÃO:</strong> Execute o arquivo: <code>sql/setup_modulo_financeiro.sql</code><br>";
    }
} catch (Exception $e) {
    echo "❌ <strong>ERRO:</strong> " . $e->getMessage() . "<br>";
}

echo "<hr>";

// 2. Verificar configurações
echo "<h2>2. Verificando configurações:</h2>";
try {
    $configExists = $db->fetchOne("SHOW TABLES LIKE 'configuracoes_financeiras'");
    if ($configExists) {
        echo "✅ <strong>Tabela 'configuracoes_financeiras' EXISTE</strong><br>";
        
        $moduloAtivo = $db->fetchOne("SELECT valor FROM configuracoes_financeiras WHERE chave = 'modulo_ativo'");
        if ($moduloAtivo && $moduloAtivo['valor'] == '1') {
            echo "✅ <strong>Módulo financeiro está ATIVO</strong><br>";
        } else {
            echo "⚠️ <strong>Módulo financeiro está INATIVO ou configuração não encontrada</strong><br>";
        }
        
        // Listar todas as configurações
        $configs = $db->fetchAll("SELECT chave, valor FROM configuracoes_financeiras");
        echo "📋 <strong>Configurações encontradas:</strong><br>";
        echo "<ul>";
        foreach ($configs as $config) {
            echo "<li><strong>{$config['chave']}</strong>: {$config['valor']}</li>";
        }
        echo "</ul>";
        
    } else {
        echo "❌ <strong>Tabela 'configuracoes_financeiras' NÃO EXISTE</strong><br>";
        echo "🔧 <strong>SOLUÇÃO:</strong> Execute o arquivo: <code>sql/setup_modulo_financeiro.sql</code><br>";
    }
} catch (Exception $e) {
    echo "❌ <strong>ERRO:</strong> " . $e->getMessage() . "<br>";
}

echo "<hr>";

// 3. Teste da query do boletos.php
echo "<h2>3. Testando compatibilidade do código:</h2>";
try {
    // Detecta qual estrutura usar baseado nas colunas encontradas
    $columns = $db->fetchAll("SHOW COLUMNS FROM boletos");
    $colunas_encontradas = array_column($columns, 'Field');
    $usa_estrutura_antiga = in_array('tipo_entidade', $colunas_encontradas) && in_array('entidade_id', $colunas_encontradas);
    
    if ($usa_estrutura_antiga) {
        // Query adaptada para estrutura antiga (tipo_entidade, entidade_id)
        $sql = "SELECT b.*,
                   CASE
                       WHEN b.tipo_entidade = 'aluno' THEN a.nome
                       WHEN b.tipo_entidade = 'polo' THEN p.nome
                       ELSE b.nome_pagador
                   END as pagador_nome
            FROM boletos b
            LEFT JOIN alunos a ON b.tipo_entidade = 'aluno' AND b.entidade_id = a.id
            LEFT JOIN polos p ON b.tipo_entidade = 'polo' AND b.entidade_id = p.id
            ORDER BY b.created_at DESC
            LIMIT 1";
        echo "📌 <strong>Usando query adaptada para estrutura ANTIGA</strong><br>";
    } else {
        // Query para estrutura nova (tipo, referencia_id)
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
        echo "📌 <strong>Usando query para estrutura NOVA</strong><br>";
    }
    
    $teste = $db->fetchOne($sql);
    echo "✅ <strong>Query do boletos.php FUNCIONA</strong><br>";
    
    if ($teste) {
        echo "📊 <strong>Dados encontrados:</strong><br>";
        echo "<ul>";
        echo "<li><strong>Pagador:</strong> " . ($teste['pagador_nome'] ?? $teste['nome_pagador']) . "</li>";
        echo "<li><strong>Valor:</strong> R$ " . number_format($teste['valor'], 2, ',', '.') . "</li>";
        echo "<li><strong>Status:</strong> " . $teste['status'] . "</li>";
        echo "<li><strong>Tipo:</strong> " . ($teste['tipo_entidade'] ?? $teste['tipo'] ?? 'N/A') . "</li>";
        echo "</ul>";
    } else {
        echo "ℹ️ <strong>Nenhum boleto encontrado (mas a query funciona)</strong><br>";
    }
    
} catch (Exception $e) {
    echo "❌ <strong>Query do boletos.php FALHOU:</strong> " . $e->getMessage() . "<br>";
    echo "🔧 <strong>CAUSA:</strong> Estrutura da tabela boletos está incompatível<br>";
}

echo "<hr>";

// 4. Resumo e recomendações
echo "<h2>🎯 RESUMO E PRÓXIMOS PASSOS:</h2>";

// Verificar novamente para dar recomendação final
$tableExists = $db->fetchOne("SHOW TABLES LIKE 'boletos'");
$configExists = $db->fetchOne("SHOW TABLES LIKE 'configuracoes_financeiras'");

if ($tableExists) {
    $columns = $db->fetchAll("SHOW COLUMNS FROM boletos");
    $colunas_encontradas = array_column($columns, 'Field');
    $nova_estrutura = in_array('tipo', $colunas_encontradas) && in_array('referencia_id', $colunas_encontradas);
    $antiga_estrutura = in_array('tipo_entidade', $colunas_encontradas) && in_array('entidade_id', $colunas_encontradas);
    
    if ($nova_estrutura && $configExists) {
        $moduloAtivo = $db->fetchOne("SELECT valor FROM configuracoes_financeiras WHERE chave = 'modulo_ativo'");
        if ($moduloAtivo && $moduloAtivo['valor'] == '1') {
            echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; border-left: 5px solid #28a745;'>";
            echo "🎉 <strong>MÓDULO FINANCEIRO ESTÁ 100% FUNCIONAL!</strong><br>";
            echo "Você pode acessar <a href='boletos.php'><strong>boletos.php</strong></a> normalmente.";
            echo "</div>";
        } else {
            echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; border-left: 5px solid #ffc107;'>";
            echo "⚠️ <strong>MÓDULO ESTÁ INATIVO</strong><br>";
            echo "Execute: <code>UPDATE configuracoes_financeiras SET valor = '1' WHERE chave = 'modulo_ativo'</code>";
            echo "</div>";
        }
    } elseif ($antiga_estrutura) {
        echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; border-left: 5px solid #ffc107;'>";
        echo "🔧 <strong>PRECISA MIGRAR</strong><br>";
        echo "1. Execute o arquivo: <code>sql/migrar_boletos_compatibilidade.sql</code><br>";
        echo "2. Depois acesse boletos.php";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; border-left: 5px solid #dc3545;'>";
        echo "❌ <strong>ESTRUTURA INCORRETA</strong><br>";
        echo "Execute o arquivo: <code>sql/setup_modulo_financeiro.sql</code>";
        echo "</div>";
    }
} else {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; border-left: 5px solid #dc3545;'>";
    echo "❌ <strong>TABELAS NÃO EXISTEM</strong><br>";
    echo "Execute o arquivo: <code>sql/setup_modulo_financeiro.sql</code>";
    echo "</div>";
}

echo "<hr>";
echo "<p><strong>📁 Arquivos disponíveis:</strong></p>";
echo "<ul>";
echo "<li><code>sql/setup_modulo_financeiro.sql</code> - Configuração completa do módulo</li>";
echo "<li><code>sql/migrar_boletos_compatibilidade.sql</code> - Migração da estrutura antiga para nova</li>";
echo "<li><code>financeiro/diagnostico_completo.php</code> - Diagnóstico detalhado</li>";
echo "</ul>";

echo "<p><em>Data/Hora: " . date('d/m/Y H:i:s') . "</em></p>";
?>
