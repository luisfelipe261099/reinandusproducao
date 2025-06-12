<?php
/**
 * Debug: Listar todas as turmas e polos disponíveis
 */
require_once '../includes/init.php';

try {
    $db = Database::getInstance();
    
    echo "<h1>Debug: Turmas e Polos Disponíveis</h1>";
    
    // Listar todos os polos
    echo "<h2>POLOS:</h2>";
    $sql = "SELECT id, nome, cidade, estado, status FROM polos ORDER BY nome ASC";
    $polos = $db->fetchAll($sql);
    
    if (empty($polos)) {
        echo "<p><strong>Nenhum polo encontrado!</strong></p>";
    } else {
        echo "<ul>";
        foreach ($polos as $polo) {
            echo "<li>ID: {$polo['id']} - {$polo['nome']} (Status: {$polo['status']})";
            if (!empty($polo['cidade'])) {
                echo " - {$polo['cidade']}";
            }
            if (!empty($polo['estado'])) {
                echo "/{$polo['estado']}";
            }
            echo "</li>";
        }
        echo "</ul>";
        echo "<p><strong>Total de polos: " . count($polos) . "</strong></p>";
    }
    
    // Listar todos os cursos
    echo "<h2>CURSOS:</h2>";
    $sql = "SELECT id, nome, status, polo_id FROM cursos ORDER BY nome ASC";
    $cursos = $db->fetchAll($sql);
    
    if (empty($cursos)) {
        echo "<p><strong>Nenhum curso encontrado!</strong></p>";
    } else {
        echo "<ul>";
        foreach ($cursos as $curso) {
            echo "<li>ID: {$curso['id']} - {$curso['nome']} (Status: {$curso['status']}, Polo ID: {$curso['polo_id']})</li>";
        }
        echo "</ul>";
        echo "<p><strong>Total de cursos: " . count($cursos) . "</strong></p>";
    }
    
    // Listar todas as turmas
    echo "<h2>TURMAS:</h2>";
    $sql = "SELECT t.id, t.nome, t.status, t.curso_id, t.polo_id, c.nome as curso_nome, p.nome as polo_nome 
            FROM turmas t 
            LEFT JOIN cursos c ON t.curso_id = c.id 
            LEFT JOIN polos p ON t.polo_id = p.id 
            ORDER BY t.nome ASC";
    $turmas = $db->fetchAll($sql);
    
    if (empty($turmas)) {
        echo "<p><strong>Nenhuma turma encontrada!</strong></p>";
    } else {
        echo "<ul>";
        foreach ($turmas as $turma) {
            echo "<li>ID: {$turma['id']} - {$turma['nome']} (Status: {$turma['status']}) - Curso: {$turma['curso_nome']} (ID: {$turma['curso_id']}) - Polo: {$turma['polo_nome']} (ID: {$turma['polo_id']})</li>";
        }
        echo "</ul>";
        echo "<p><strong>Total de turmas: " . count($turmas) . "</strong></p>";
    }
    
    // Verificar especificamente polos ativos
    echo "<h2>POLOS ATIVOS:</h2>";
    $sql = "SELECT id, nome, cidade, estado FROM polos WHERE status = 'ativo' ORDER BY nome ASC";
    $polos_ativos = $db->fetchAll($sql);
    
    if (empty($polos_ativos)) {
        echo "<p><strong>Nenhum polo ativo encontrado!</strong></p>";
    } else {
        echo "<ul>";
        foreach ($polos_ativos as $polo) {
            echo "<li>ID: {$polo['id']} - {$polo['nome']}";
            if (!empty($polo['cidade'])) {
                echo " - {$polo['cidade']}";
            }
            if (!empty($polo['estado'])) {
                echo "/{$polo['estado']}";
            }
            echo "</li>";
        }
        echo "</ul>";
        echo "<p><strong>Total de polos ativos: " . count($polos_ativos) . "</strong></p>";
    }
    
    // Verificar especificamente turmas ativas
    echo "<h2>TURMAS ATIVAS:</h2>";
    $sql = "SELECT t.id, t.nome, t.curso_id, t.polo_id, c.nome as curso_nome, p.nome as polo_nome 
            FROM turmas t 
            LEFT JOIN cursos c ON t.curso_id = c.id 
            LEFT JOIN polos p ON t.polo_id = p.id 
            WHERE t.status = 'ativo' 
            ORDER BY t.nome ASC";
    $turmas_ativas = $db->fetchAll($sql);
    
    if (empty($turmas_ativas)) {
        echo "<p><strong>Nenhuma turma ativa encontrada!</strong></p>";
    } else {
        echo "<ul>";
        foreach ($turmas_ativas as $turma) {
            echo "<li>ID: {$turma['id']} - {$turma['nome']} - Curso: {$turma['curso_nome']} (ID: {$turma['curso_id']}) - Polo: {$turma['polo_nome']} (ID: {$turma['polo_id']})</li>";
        }
        echo "</ul>";
        echo "<p><strong>Total de turmas ativas: " . count($turmas_ativas) . "</strong></p>";
    }
    
} catch (Exception $e) {
    echo "<p><strong>Erro:</strong> " . $e->getMessage() . "</p>";
}
?>
