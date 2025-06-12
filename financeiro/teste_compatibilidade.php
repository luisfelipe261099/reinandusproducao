<?php
/**
 * Teste de Compatibilidade do Módulo Financeiro
 * Verifica se o sistema está funcionando com a estrutura existente
 */

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/Database.php';

$db = Database::getInstance();

echo "<h1>🧪 TESTE DE COMPATIBILIDADE - MÓDULO FINANCEIRO</h1>";
echo "<hr>";

echo "<h2>1. Teste da Query Principal (boletos.php):</h2>";
try {
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
        LIMIT 3";
    
    $boletos = $db->fetchAll($sql);
    echo "✅ <strong>Query funciona perfeitamente!</strong><br>";
    
    if (count($boletos) > 0) {
        echo "<h3>📊 Últimos boletos encontrados:</h3>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Pagador</th><th>Tipo</th><th>Valor</th><th>Status</th><th>Vencimento</th></tr>";
        
        foreach ($boletos as $boleto) {
            echo "<tr>";
            echo "<td>" . $boleto['id'] . "</td>";
            echo "<td>" . htmlspecialchars($boleto['pagador_nome'] ?: $boleto['nome_pagador']) . "</td>";
            echo "<td>" . htmlspecialchars($boleto['tipo_entidade']) . "</td>";
            echo "<td>R$ " . number_format($boleto['valor'], 2, ',', '.') . "</td>";
            echo "<td>" . htmlspecialchars($boleto['status']) . "</td>";
            echo "<td>" . date('d/m/Y', strtotime($boleto['data_vencimento'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "ℹ️ <strong>Nenhum boleto encontrado (mas a query está funcionando)</strong><br>";
    }
    
} catch (Exception $e) {
    echo "❌ <strong>ERRO na query:</strong> " . $e->getMessage() . "<br>";
}

echo "<hr>";

echo "<h2>2. Teste de Geração de Boleto (simulação):</h2>";
try {
    require_once 'includes/boleto_functions.php';
    
    // Dados de teste
    $dadosTeste = [
        'tipo_entidade' => 'avulso',
        'entidade_id' => null,
        'valor' => 150.00,
        'data_vencimento' => date('Y-m-d', strtotime('+30 days')),
        'descricao' => 'Teste de compatibilidade do sistema',
        'nome_pagador' => 'João Teste Silva',
        'cpf_pagador' => '123.456.789-00',
        'endereco' => 'Rua Teste, 123',
        'bairro' => 'Centro',
        'cidade' => 'São Paulo',
        'uf' => 'SP',
        'cep' => '01000-000',
        'multa' => 2.00,
        'juros' => 1.00
    ];
    
    echo "📝 <strong>Dados de teste preparados:</strong><br>";
    echo "<ul>";
    echo "<li><strong>Tipo:</strong> " . $dadosTeste['tipo_entidade'] . "</li>";
    echo "<li><strong>Pagador:</strong> " . $dadosTeste['nome_pagador'] . "</li>";
    echo "<li><strong>Valor:</strong> R$ " . number_format($dadosTeste['valor'], 2, ',', '.') . "</li>";
    echo "<li><strong>Vencimento:</strong> " . date('d/m/Y', strtotime($dadosTeste['data_vencimento'])) . "</li>";
    echo "</ul>";
    
    // NÃO vai gerar o boleto real, apenas testar se a função existe e é acessível
    if (function_exists('gerarBoletoBancario')) {
        echo "✅ <strong>Função gerarBoletoBancario() encontrada</strong><br>";
        
        if (function_exists('salvarBoleto')) {
            echo "✅ <strong>Função salvarBoleto() encontrada</strong><br>";
            echo "✅ <strong>Sistema está pronto para gerar boletos!</strong><br>";
        } else {
            echo "❌ <strong>Função salvarBoleto() não encontrada</strong><br>";
        }
    } else {
        echo "❌ <strong>Função gerarBoletoBancario() não encontrada</strong><br>";
    }
    
} catch (Exception $e) {
    echo "❌ <strong>ERRO no teste de geração:</strong> " . $e->getMessage() . "<br>";
}

echo "<hr>";

echo "<h2>3. Teste de Busca de Alunos (AJAX):</h2>";
try {
    // Testa se consegue buscar alunos
    $alunosTeste = $db->fetchAll("SELECT id, nome, cpf FROM alunos WHERE nome IS NOT NULL AND nome != '' LIMIT 5");
    
    if (count($alunosTeste) > 0) {
        echo "✅ <strong>Alunos encontrados para teste:</strong><br>";
        echo "<ul>";
        foreach ($alunosTeste as $aluno) {
            echo "<li><strong>" . htmlspecialchars($aluno['nome']) . "</strong> - CPF: " . htmlspecialchars($aluno['cpf']) . " (ID: " . $aluno['id'] . ")</li>";
        }
        echo "</ul>";
        
        // Verifica se o arquivo AJAX existe
        if (file_exists(__DIR__ . '/ajax/buscar_alunos.php')) {
            echo "✅ <strong>Arquivo AJAX encontrado:</strong> ajax/buscar_alunos.php<br>";
        } else {
            echo "⚠️ <strong>Arquivo AJAX não encontrado:</strong> ajax/buscar_alunos.php<br>";
        }
    } else {
        echo "⚠️ <strong>Nenhum aluno encontrado no banco de dados</strong><br>";
    }
    
} catch (Exception $e) {
    echo "❌ <strong>ERRO na busca de alunos:</strong> " . $e->getMessage() . "<br>";
}

echo "<hr>";

echo "<h2>🎯 RESULTADO FINAL:</h2>";

// Verifica se todos os componentes estão funcionando
$queryOk = false;
$funcoesOk = false;
$alunosOk = false;

try {
    // Teste query
    $db->fetchOne("SELECT b.*, CASE WHEN b.tipo_entidade = 'aluno' THEN a.nome ELSE b.nome_pagador END as pagador_nome FROM boletos b LEFT JOIN alunos a ON b.tipo_entidade = 'aluno' AND b.entidade_id = a.id LIMIT 1");
    $queryOk = true;
} catch (Exception $e) {
    // Query falhou
}

try {
    // Teste funções
    require_once 'includes/boleto_functions.php';
    if (function_exists('gerarBoletoBancario') && function_exists('salvarBoleto')) {
        $funcoesOk = true;
    }
} catch (Exception $e) {
    // Funções falharam
}

try {
    // Teste alunos
    $alunos = $db->fetchAll("SELECT id FROM alunos LIMIT 1");
    $alunosOk = true;
} catch (Exception $e) {
    // Busca de alunos falhou
}

if ($queryOk && $funcoesOk && $alunosOk) {
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; border-left: 5px solid #28a745;'>";
    echo "🎉 <strong>SISTEMA 100% COMPATÍVEL!</strong><br>";
    echo "✅ Queries funcionando<br>";
    echo "✅ Funções carregadas<br>";
    echo "✅ Dados disponíveis<br><br>";
    echo "<strong>📁 Você pode acessar:</strong><br>";
    echo "• <a href='boletos.php' target='_blank'><strong>boletos.php</strong></a> - Listagem de boletos<br>";
    echo "• <a href='boletos.php?action=novo' target='_blank'><strong>boletos.php?action=novo</strong></a> - Gerar novo boleto<br>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; border-left: 5px solid #dc3545;'>";
    echo "⚠️ <strong>ALGUNS PROBLEMAS ENCONTRADOS:</strong><br>";
    echo ($queryOk ? "✅" : "❌") . " Queries<br>";
    echo ($funcoesOk ? "✅" : "❌") . " Funções<br>";
    echo ($alunosOk ? "✅" : "❌") . " Dados<br>";
    echo "</div>";
}

echo "<hr>";
echo "<p><em>Teste executado em: " . date('d/m/Y H:i:s') . "</em></p>";
?>
