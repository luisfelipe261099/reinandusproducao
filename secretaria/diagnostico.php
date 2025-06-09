<?php
/**
 * Arquivo de diagnóstico para identificar problemas de sessão e redirecionamento
 */

// Inicializa o sistema sem verificar autenticação
require_once __DIR__ . '/includes/init.php';

// Função para exibir informações de forma legível
function exibirInfo($titulo, $dados) {
    echo "<h3>$titulo</h3>";
    echo "<pre>";
    print_r($dados);
    echo "</pre>";
    echo "<hr>";
}

// Cabeçalho HTML
echo "<!DOCTYPE html>
<html>
<head>
    <title>Diagnóstico do Sistema</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1, h2 { color: #333; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow: auto; }
        .error { color: red; }
        .success { color: green; }
        hr { border: 1px solid #ddd; margin: 20px 0; }
    </style>
</head>
<body>
    <h1>Diagnóstico do Sistema</h1>
    <p>Este arquivo exibe informações de diagnóstico para ajudar a identificar problemas no sistema.</p>
";

// Verifica se a sessão está iniciada
echo "<h2>Informações da Sessão</h2>";
echo "Status da sessão: " . (session_status() === PHP_SESSION_ACTIVE ? "<span class='success'>Ativa</span>" : "<span class='error'>Inativa</span>") . "<br>";
echo "ID da sessão: " . session_id() . "<br>";
echo "Caminho da sessão: " . session_save_path() . "<br><br>";

// Exibe as variáveis de sessão
exibirInfo("Variáveis de Sessão", $_SESSION);

// Verifica se o usuário está autenticado
echo "<h2>Status de Autenticação</h2>";
echo "Usuário autenticado: " . (usuarioAutenticado() ? "<span class='success'>Sim</span>" : "<span class='error'>Não</span>") . "<br>";

if (usuarioAutenticado()) {
    echo "ID do usuário: " . getUsuarioId() . "<br>";
    echo "Nome do usuário: " . getUsuarioNome() . "<br>";
    echo "Tipo do usuário: " . getUsuarioTipo() . "<br><br>";
    
    // Verifica permissões
    echo "<h2>Verificação de Permissões</h2>";
    
    // Lista de módulos para verificar
    $modulos = ['secretaria', 'alunos', 'matriculas', 'cursos', 'documentos', 'financeiro', 'relatorios', 'chamados'];
    
    echo "<table border='1' cellpadding='5' cellspacing='0'>
        <tr>
            <th>Módulo</th>
            <th>Tem Permissão?</th>
            <th>Detalhes</th>
        </tr>";
    
    foreach ($modulos as $modulo) {
        try {
            $permissao = verificarPermissoes($modulo);
            $temPermissao = $permissao !== false;
            
            echo "<tr>
                <td>$modulo</td>
                <td>" . ($temPermissao ? "<span class='success'>Sim</span>" : "<span class='error'>Não</span>") . "</td>
                <td>";
            
            if ($temPermissao) {
                echo "Nível: " . $permissao['nivel_acesso'] . "<br>";
                echo "Restrições: " . ($permissao['restricoes'] ? $permissao['restricoes'] : "Nenhuma");
            } else {
                echo "Sem permissão";
            }
            
            echo "</td>
            </tr>";
        } catch (Exception $e) {
            echo "<tr>
                <td>$modulo</td>
                <td><span class='error'>Erro</span></td>
                <td>" . $e->getMessage() . "</td>
            </tr>";
        }
    }
    
    echo "</table>";
}

// Verifica a estrutura da tabela de permissões
echo "<h2>Estrutura da Tabela de Permissões</h2>";

try {
    $db = Database::getInstance();
    $sql = "DESCRIBE permissoes";
    $colunas = $db->fetchAll($sql);
    
    if ($colunas) {
        echo "<table border='1' cellpadding='5' cellspacing='0'>
            <tr>
                <th>Campo</th>
                <th>Tipo</th>
                <th>Nulo</th>
                <th>Chave</th>
                <th>Padrão</th>
                <th>Extra</th>
            </tr>";
        
        foreach ($colunas as $coluna) {
            echo "<tr>
                <td>" . $coluna['Field'] . "</td>
                <td>" . $coluna['Type'] . "</td>
                <td>" . $coluna['Null'] . "</td>
                <td>" . $coluna['Key'] . "</td>
                <td>" . ($coluna['Default'] ?? 'NULL') . "</td>
                <td>" . $coluna['Extra'] . "</td>
            </tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p class='error'>Não foi possível obter a estrutura da tabela de permissões.</p>";
    }
    
    // Verifica se existem registros na tabela de permissões
    $sql = "SELECT COUNT(*) as total FROM permissoes";
    $resultado = $db->fetchOne($sql);
    
    echo "<p>Total de registros na tabela de permissões: " . $resultado['total'] . "</p>";
    
    // Exibe alguns registros de exemplo
    if ($resultado['total'] > 0) {
        $sql = "SELECT * FROM permissoes LIMIT 5";
        $permissoes = $db->fetchAll($sql);
        
        exibirInfo("Exemplos de Permissões", $permissoes);
    }
} catch (Exception $e) {
    echo "<p class='error'>Erro ao verificar a tabela de permissões: " . $e->getMessage() . "</p>";
}

// Verifica a estrutura da tabela de usuários
echo "<h2>Estrutura da Tabela de Usuários</h2>";

try {
    $db = Database::getInstance();
    $sql = "DESCRIBE usuarios";
    $colunas = $db->fetchAll($sql);
    
    if ($colunas) {
        echo "<table border='1' cellpadding='5' cellspacing='0'>
            <tr>
                <th>Campo</th>
                <th>Tipo</th>
                <th>Nulo</th>
                <th>Chave</th>
                <th>Padrão</th>
                <th>Extra</th>
            </tr>";
        
        foreach ($colunas as $coluna) {
            echo "<tr>
                <td>" . $coluna['Field'] . "</td>
                <td>" . $coluna['Type'] . "</td>
                <td>" . $coluna['Null'] . "</td>
                <td>" . $coluna['Key'] . "</td>
                <td>" . ($coluna['Default'] ?? 'NULL') . "</td>
                <td>" . $coluna['Extra'] . "</td>
            </tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p class='error'>Não foi possível obter a estrutura da tabela de usuários.</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>Erro ao verificar a tabela de usuários: " . $e->getMessage() . "</p>";
}

// Verifica a função exigirPermissao
echo "<h2>Análise da Função exigirPermissao</h2>";

// Obtém o código da função
$funcaoExigirPermissao = new ReflectionFunction('exigirPermissao');
$arquivo = $funcaoExigirPermissao->getFileName();
$inicio = $funcaoExigirPermissao->getStartLine();
$fim = $funcaoExigirPermissao->getEndLine();

echo "<p>Função definida em: $arquivo (linhas $inicio-$fim)</p>";

// Exibe o código da função
$linhas = file($arquivo);
$codigo = implode("", array_slice($linhas, $inicio - 1, $fim - $inicio + 1));

echo "<pre>";
highlight_string("<?php\n" . $codigo . "\n?>");
echo "</pre>";

// Verifica a função verificarPermissoes
echo "<h2>Análise da Função verificarPermissoes</h2>";

// Obtém o código da função
$funcaoVerificarPermissoes = new ReflectionFunction('verificarPermissoes');
$arquivo = $funcaoVerificarPermissoes->getFileName();
$inicio = $funcaoVerificarPermissoes->getStartLine();
$fim = $funcaoVerificarPermissoes->getEndLine();

echo "<p>Função definida em: $arquivo (linhas $inicio-$fim)</p>";

// Exibe o código da função
$linhas = file($arquivo);
$codigo = implode("", array_slice($linhas, $inicio - 1, $fim - $inicio + 1));

echo "<pre>";
highlight_string("<?php\n" . $codigo . "\n?>");
echo "</pre>";

// Verifica a função Auth::requirePermission
echo "<h2>Análise da Função Auth::requirePermission</h2>";

try {
    $metodo = new ReflectionMethod('Auth', 'requirePermission');
    $arquivo = $metodo->getFileName();
    $inicio = $metodo->getStartLine();
    $fim = $metodo->getEndLine();
    
    echo "<p>Método definido em: $arquivo (linhas $inicio-$fim)</p>";
    
    // Exibe o código do método
    $linhas = file($arquivo);
    $codigo = implode("", array_slice($linhas, $inicio - 1, $fim - $inicio + 1));
    
    echo "<pre>";
    highlight_string("<?php\n" . $codigo . "\n?>");
    echo "</pre>";
} catch (Exception $e) {
    echo "<p class='error'>Erro ao analisar o método Auth::requirePermission: " . $e->getMessage() . "</p>";
}

// Verifica a função Auth::hasPermission
echo "<h2>Análise da Função Auth::hasPermission</h2>";

try {
    $metodo = new ReflectionMethod('Auth', 'hasPermission');
    $arquivo = $metodo->getFileName();
    $inicio = $metodo->getStartLine();
    $fim = $metodo->getEndLine();
    
    echo "<p>Método definido em: $arquivo (linhas $inicio-$fim)</p>";
    
    // Exibe o código do método
    $linhas = file($arquivo);
    $codigo = implode("", array_slice($linhas, $inicio - 1, $fim - $inicio + 1));
    
    echo "<pre>";
    highlight_string("<?php\n" . $codigo . "\n?>");
    echo "</pre>";
} catch (Exception $e) {
    echo "<p class='error'>Erro ao analisar o método Auth::hasPermission: " . $e->getMessage() . "</p>";
}

// Rodapé HTML
echo "
</body>
</html>
";
?>
