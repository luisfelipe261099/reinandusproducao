<?php
/**
 * Script para verificar o acesso do polo ao AVA
 * Este script exibe informações detalhadas sobre o acesso do polo ao AVA
 */

// Inicializa o sistema
require_once 'includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Instancia o banco de dados
$db = Database::getInstance();

try {
    echo "<h1>Verificação de Acesso ao AVA</h1>";
    
    // Exibe informações do usuário
    echo "<h2>Informações do Usuário</h2>";
    echo "<p>ID do Usuário: " . getUsuarioId() . "</p>";
    echo "<p>Nome do Usuário: " . getUsuarioNome() . "</p>";
    echo "<p>Tipo do Usuário: " . getUsuarioTipo() . "</p>";
    
    // Se for um usuário do tipo polo, exibe informações do polo
    if (getUsuarioTipo() === 'polo') {
        echo "<h2>Informações do Polo</h2>";
        
        // Obtém o ID do polo usando a função getUsuarioPoloId()
        $polo_id = getUsuarioPoloId();
        echo "<p>ID do Polo (via getUsuarioPoloId): " . ($polo_id ?? 'Não encontrado') . "</p>";
        
        // Verifica se o ID do polo está na sessão
        echo "<p>ID do Polo (na sessão): " . ($_SESSION['polo_id'] ?? 'Não encontrado') . "</p>";
        
        // Busca o polo pelo ID do usuário
        $usuario_id = getUsuarioId();
        $sql = "SELECT id, nome FROM polos WHERE responsavel_id = ?";
        $polo = $db->fetchOne($sql, [$usuario_id]);
        
        if ($polo) {
            echo "<p>ID do Polo (via consulta direta): " . $polo['id'] . "</p>";
            echo "<p>Nome do Polo: " . $polo['nome'] . "</p>";
        } else {
            echo "<p style='color: red;'>Não foi encontrado polo para este usuário via responsavel_id.</p>";
            
            // Tenta buscar na tabela de usuários
            $sql = "SELECT polo_id FROM usuarios WHERE id = ?";
            $usuario = $db->fetchOne($sql, [$usuario_id]);
            
            if ($usuario && isset($usuario['polo_id']) && !empty($usuario['polo_id'])) {
                echo "<p>ID do Polo (via tabela de usuários): " . $usuario['polo_id'] . "</p>";
                
                // Busca o nome do polo
                $sql = "SELECT nome FROM polos WHERE id = ?";
                $polo = $db->fetchOne($sql, [$usuario['polo_id']]);
                
                if ($polo) {
                    echo "<p>Nome do Polo: " . $polo['nome'] . "</p>";
                }
            } else {
                echo "<p style='color: red;'>Não foi encontrado polo_id na tabela de usuários.</p>";
            }
        }
        
        // Verifica o acesso ao AVA
        echo "<h2>Acesso ao AVA</h2>";
        
        if ($polo_id) {
            $sql = "SELECT * FROM ava_polos_acesso WHERE polo_id = ?";
            $acesso = $db->fetchOne($sql, [$polo_id]);
            
            if ($acesso) {
                echo "<p>ID do Registro: " . $acesso['id'] . "</p>";
                echo "<p>Polo ID: " . $acesso['polo_id'] . "</p>";
                echo "<p>Liberado: " . ($acesso['liberado'] ? 'Sim' : 'Não') . "</p>";
                echo "<p>Data de Liberação: " . ($acesso['data_liberacao'] ?? 'N/A') . "</p>";
                
                if ($acesso['liberado']) {
                    echo "<p style='color: green;'>O polo tem acesso liberado ao AVA.</p>";
                } else {
                    echo "<p style='color: red;'>O polo não tem acesso liberado ao AVA.</p>";
                }
            } else {
                echo "<p style='color: red;'>Não foi encontrado registro de acesso ao AVA para este polo.</p>";
            }
        } else {
            echo "<p style='color: red;'>Não foi possível verificar o acesso ao AVA porque o ID do polo não foi encontrado.</p>";
        }
        
        // Exibe todos os registros da tabela ava_polos_acesso
        echo "<h2>Todos os Registros de Acesso ao AVA</h2>";
        
        $sql = "SELECT apa.*, p.nome as polo_nome 
                FROM ava_polos_acesso apa 
                JOIN polos p ON apa.polo_id = p.id";
        $acessos = $db->fetchAll($sql);
        
        if (count($acessos) > 0) {
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>Polo ID</th><th>Nome do Polo</th><th>Liberado</th><th>Data Liberação</th></tr>";
            
            foreach ($acessos as $acesso) {
                echo "<tr>";
                echo "<td>" . $acesso['id'] . "</td>";
                echo "<td>" . $acesso['polo_id'] . "</td>";
                echo "<td>" . $acesso['polo_nome'] . "</td>";
                echo "<td>" . ($acesso['liberado'] ? 'Sim' : 'Não') . "</td>";
                echo "<td>" . ($acesso['data_liberacao'] ?? 'N/A') . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        } else {
            echo "<p>Nenhum registro encontrado na tabela ava_polos_acesso.</p>";
        }
    } else {
        echo "<p>Este usuário não é do tipo polo.</p>";
    }
    
    echo "<p><a href='index.php'>Voltar para a página inicial</a></p>";
    
} catch (Exception $e) {
    echo "<h2>Erro</h2>";
    echo "<p style='color: red;'>Ocorreu um erro durante a verificação: " . $e->getMessage() . "</p>";
    echo "<p><a href='index.php'>Voltar para a página inicial</a></p>";
}
?>
