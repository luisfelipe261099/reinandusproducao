<?php
/**
 * Script para atualizar a tabela de usuários com o ID do polo
 * Este script deve ser executado uma vez para garantir que todos os usuários do tipo polo
 * tenham o ID do polo associado corretamente na tabela de usuários
 */

// Inicializa o sistema
require_once 'includes/init.php';

// Verifica se o usuário está autenticado e é administrador
exigirLogin();
if (getUsuarioTipo() !== 'admin') {
    setMensagem('erro', 'Você não tem permissão para acessar esta página.');
    redirect('index.php');
    exit;
}

// Instancia o banco de dados
$db = Database::getInstance();

try {
    echo "<h1>Atualização da Tabela de Usuários com ID do Polo</h1>";
    
    // Verifica se a coluna polo_id existe na tabela de usuários
    $sql = "SHOW COLUMNS FROM usuarios LIKE 'polo_id'";
    $coluna = $db->fetchOne($sql);
    
    if (!$coluna) {
        // Adiciona a coluna polo_id à tabela de usuários
        echo "<p>Adicionando coluna polo_id à tabela de usuários...</p>";
        $sql = "ALTER TABLE usuarios ADD COLUMN polo_id INT NULL";
        $db->query($sql);
        echo "<p style='color: green;'>Coluna polo_id adicionada com sucesso!</p>";
    } else {
        echo "<p>A coluna polo_id já existe na tabela de usuários.</p>";
    }
    
    // Busca todos os usuários do tipo polo
    $sql = "SELECT id FROM usuarios WHERE tipo = 'polo'";
    $usuarios = $db->fetchAll($sql);
    
    echo "<p>Encontrados " . count($usuarios) . " usuários do tipo polo.</p>";
    
    $atualizados = 0;
    
    // Para cada usuário do tipo polo, busca o ID do polo associado
    foreach ($usuarios as $usuario) {
        $usuario_id = $usuario['id'];
        
        // Busca o polo pelo responsavel_id
        $sql = "SELECT id FROM polos WHERE responsavel_id = ?";
        $polo = $db->fetchOne($sql, [$usuario_id]);
        
        if ($polo && isset($polo['id'])) {
            // Atualiza o usuário com o ID do polo
            $sql = "UPDATE usuarios SET polo_id = ? WHERE id = ?";
            $db->query($sql, [$polo['id'], $usuario_id]);
            
            echo "<p>Usuário ID " . $usuario_id . " atualizado com polo_id = " . $polo['id'] . "</p>";
            $atualizados++;
        } else {
            echo "<p style='color: orange;'>Não foi encontrado polo para o usuário ID " . $usuario_id . "</p>";
        }
    }
    
    echo "<h2>Resumo</h2>";
    echo "<p>" . $atualizados . " usuários atualizados com sucesso!</p>";
    
    // Verifica a tabela ava_polos_acesso
    echo "<h2>Verificação da Tabela ava_polos_acesso</h2>";
    
    // Verifica se a tabela existe
    $sql = "SHOW TABLES LIKE 'ava_polos_acesso'";
    $tabela = $db->fetchOne($sql);
    
    if (!$tabela) {
        echo "<p style='color: red;'>A tabela ava_polos_acesso não existe. Execute o script de criação das tabelas do AVA.</p>";
    } else {
        // Busca os registros na tabela
        $sql = "SELECT * FROM ava_polos_acesso";
        $acessos = $db->fetchAll($sql);
        
        echo "<p>Encontrados " . count($acessos) . " registros na tabela ava_polos_acesso.</p>";
        
        // Lista os registros
        if (count($acessos) > 0) {
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>Polo ID</th><th>Liberado</th><th>Data Liberação</th></tr>";
            
            foreach ($acessos as $acesso) {
                echo "<tr>";
                echo "<td>" . $acesso['id'] . "</td>";
                echo "<td>" . $acesso['polo_id'] . "</td>";
                echo "<td>" . ($acesso['liberado'] ? 'Sim' : 'Não') . "</td>";
                echo "<td>" . ($acesso['data_liberacao'] ?? 'N/A') . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        }
    }
    
    echo "<p><a href='index.php'>Voltar para a página inicial</a></p>";
    
} catch (Exception $e) {
    echo "<h2>Erro</h2>";
    echo "<p style='color: red;'>Ocorreu um erro durante a atualização: " . $e->getMessage() . "</p>";
    echo "<p><a href='index.php'>Voltar para a página inicial</a></p>";
}
?>
