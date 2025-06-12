<?php
require_once 'config/database.php';

echo "<h1>Correção Final dos Polos - Diagnóstico e Correção</h1>\n";

try {
    // 1. Verificar polos existentes
    echo "<h2>1. Polos Existentes:</h2>\n";
    $stmt = $pdo->query("SELECT id, nome, status FROM polos ORDER BY id");
    $polos_existentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($polos_existentes)) {
        echo "<p style='color: red;'>PROBLEMA: Nenhum polo encontrado na tabela!</p>\n";
        
        // Criar polo padrão se não existir nenhum
        echo "<h3>Criando polo padrão...</h3>\n";
        $stmt = $pdo->prepare("INSERT INTO polos (nome, status) VALUES (?, ?)");
        $stmt->execute(['Polo Principal', 'ativo']);
        $polo_padrao_id = $pdo->lastInsertId();
        echo "<p style='color: green;'>Polo padrão criado com ID: $polo_padrao_id</p>\n";
        
        // Recarregar polos
        $stmt = $pdo->query("SELECT id, nome, status FROM polos ORDER BY id");
        $polos_existentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    foreach ($polos_existentes as $polo) {
        echo "<p>ID: {$polo['id']} - Nome: {$polo['nome']} - Status: {$polo['status']}</p>\n";
    }
    
    $ids_polos_validos = array_column($polos_existentes, 'id');
    $primeiro_polo_id = $ids_polos_validos[0];
    
    // 2. Verificar cursos com polo_id inválido
    echo "<h2>2. Cursos com polo_id inválido:</h2>\n";
    $placeholders = str_repeat('?,', count($ids_polos_validos) - 1) . '?';
    $stmt = $pdo->prepare("
        SELECT id, nome, polo_id 
        FROM cursos 
        WHERE polo_id IS NOT NULL 
        AND polo_id NOT IN ($placeholders)
    ");
    $stmt->execute($ids_polos_validos);
    $cursos_invalidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($cursos_invalidos)) {
        echo "<p style='color: green;'>Nenhum curso com polo_id inválido encontrado!</p>\n";
    } else {
        echo "<p style='color: orange;'>Encontrados " . count($cursos_invalidos) . " cursos com polo_id inválido:</p>\n";
        foreach ($cursos_invalidos as $curso) {
            echo "<p>Curso ID: {$curso['id']} - Nome: {$curso['nome']} - polo_id inválido: {$curso['polo_id']}</p>\n";
        }
        
        // Corrigir cursos com polo_id inválido
        echo "<h3>Corrigindo cursos com polo_id inválido...</h3>\n";
        $stmt = $pdo->prepare("
            UPDATE cursos 
            SET polo_id = ? 
            WHERE polo_id IS NOT NULL 
            AND polo_id NOT IN ($placeholders)
        ");
        $params = array_merge([$primeiro_polo_id], $ids_polos_validos);
        $stmt->execute($params);
        $affected = $stmt->rowCount();
        echo "<p style='color: green;'>$affected cursos corrigidos!</p>\n";
    }
    
    // 3. Verificar matrículas com polo_id inválido
    echo "<h2>3. Matrículas com polo_id inválido:</h2>\n";
    $stmt = $pdo->prepare("
        SELECT id, aluno_id, curso_id, polo_id 
        FROM matriculas 
        WHERE polo_id IS NOT NULL 
        AND polo_id NOT IN ($placeholders)
        LIMIT 10
    ");
    $stmt->execute($ids_polos_validos);
    $matriculas_invalidas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Contar total
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM matriculas 
        WHERE polo_id IS NOT NULL 
        AND polo_id NOT IN ($placeholders)
    ");
    $stmt->execute($ids_polos_validos);
    $total_matriculas_invalidas = $stmt->fetchColumn();
    
    if ($total_matriculas_invalidas == 0) {
        echo "<p style='color: green;'>Nenhuma matrícula com polo_id inválido encontrada!</p>\n";
    } else {
        echo "<p style='color: orange;'>Total de $total_matriculas_invalidas matrículas com polo_id inválido</p>\n";
        echo "<p>Primeiras 10:</p>\n";
        foreach ($matriculas_invalidas as $matricula) {
            echo "<p>Matrícula ID: {$matricula['id']} - Aluno: {$matricula['aluno_id']} - Curso: {$matricula['curso_id']} - polo_id inválido: {$matricula['polo_id']}</p>\n";
        }
        
        // Corrigir matrículas com polo_id inválido
        echo "<h3>Corrigindo matrículas com polo_id inválido...</h3>\n";
        $stmt = $pdo->prepare("
            UPDATE matriculas 
            SET polo_id = ? 
            WHERE polo_id IS NOT NULL 
            AND polo_id NOT IN ($placeholders)
        ");
        $params = array_merge([$primeiro_polo_id], $ids_polos_validos);
        $stmt->execute($params);
        $affected = $stmt->rowCount();
        echo "<p style='color: green;'>$affected matrículas corrigidas!</p>\n";
    }
    
    // 4. Testar uma query de UPDATE de matrícula
    echo "<h2>4. Teste de UPDATE de matrícula:</h2>\n";
    $stmt = $pdo->query("SELECT id FROM matriculas LIMIT 1");
    $test_matricula = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($test_matricula) {
        $test_id = $test_matricula['id'];
        echo "<p>Testando UPDATE na matrícula ID: $test_id</p>\n";
        
        try {
            $stmt = $pdo->prepare("UPDATE matriculas SET polo_id = ? WHERE id = ?");
            $stmt->execute([$primeiro_polo_id, $test_id]);
            echo "<p style='color: green;'>UPDATE de teste executado com sucesso!</p>\n";
        } catch (Exception $e) {
            echo "<p style='color: red;'>Erro no UPDATE de teste: " . $e->getMessage() . "</p>\n";
        }
    }
    
    // 5. Verificar estrutura das tabelas
    echo "<h2>5. Verificação final:</h2>\n";
    
    // Verificar se ainda há problemas
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM cursos 
        WHERE polo_id IS NOT NULL 
        AND polo_id NOT IN ($placeholders)
    ");
    $stmt->execute($ids_polos_validos);
    $cursos_problema = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM matriculas 
        WHERE polo_id IS NOT NULL 
        AND polo_id NOT IN ($placeholders)
    ");
    $stmt->execute($ids_polos_validos);
    $matriculas_problema = $stmt->fetchColumn();
    
    if ($cursos_problema == 0 && $matriculas_problema == 0) {
        echo "<p style='color: green; font-weight: bold;'>✓ Todos os problemas de polo_id foram corrigidos!</p>\n";
        echo "<p style='color: green;'>O formulário de matrícula deve funcionar corretamente agora.</p>\n";
    } else {
        echo "<p style='color: red;'>Ainda existem problemas:</p>\n";
        echo "<p>Cursos com problema: $cursos_problema</p>\n";
        echo "<p>Matrículas com problema: $matriculas_problema</p>\n";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Erro: " . $e->getMessage() . "</p>\n";
    echo "<p>Stack trace: " . $e->getTraceAsString() . "</p>\n";
}

echo "<p><strong>Execução concluída!</strong></p>\n";
?>
