<?php
/**
 * Script para verificar os logs de debug das notas
 */

echo "<h1>🔍 Verificação de Logs - Notas</h1>";

// Caminhos possíveis para o log de erro
$log_paths = [
    'error_log',
    'logs/error.log',
    'logs/php_errors.log',
    '/var/log/apache2/error.log',
    '/var/log/nginx/error.log',
    ini_get('error_log'),
    $_SERVER['DOCUMENT_ROOT'] . '/error_log'
];

echo "<h2>📂 Procurando arquivos de log...</h2>";

$log_encontrado = false;

foreach ($log_paths as $path) {
    if (empty($path)) continue;
    
    echo "<p>Verificando: <code>" . htmlspecialchars($path) . "</code> - ";
    
    if (file_exists($path) && is_readable($path)) {
        echo "<span style='color: green;'>✅ Encontrado</span></p>";
        
        // Lê as últimas linhas do log
        $linhas = file($path);
        $linhas_notas = [];
        
        // Filtra apenas as linhas relacionadas a NOTAS DEBUG
        foreach ($linhas as $linha) {
            if (strpos($linha, 'NOTAS DEBUG') !== false) {
                $linhas_notas[] = $linha;
            }
        }
        
        if (!empty($linhas_notas)) {
            echo "<h3>📋 Logs de Debug das Notas (últimas " . count($linhas_notas) . " entradas):</h3>";
            echo "<div style='background: #f5f5f5; padding: 10px; border-radius: 5px; font-family: monospace; font-size: 12px; max-height: 400px; overflow-y: auto;'>";
            
            // Mostra apenas as últimas 50 linhas para não sobrecarregar
            $linhas_recentes = array_slice($linhas_notas, -50);
            
            foreach ($linhas_recentes as $linha) {
                echo htmlspecialchars($linha) . "<br>";
            }
            echo "</div>";
            
            $log_encontrado = true;
        } else {
            echo "<p style='color: orange;'>⚠️ Arquivo encontrado, mas sem logs de debug das notas.</p>";
        }
        
    } else {
        echo "<span style='color: red;'>❌ Não encontrado ou não legível</span></p>";
    }
}

if (!$log_encontrado) {
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0; border: 1px solid #ffeaa7;'>";
    echo "<h3>ℹ️ Como verificar os logs:</h3>";
    echo "<ol>";
    echo "<li>Acesse a página de lançamento de notas</li>";
    echo "<li>Preencha alguns campos e clique em 'Salvar Notas'</li>";
    echo "<li>Volte aqui para ver os logs de debug</li>";
    echo "</ol>";
    echo "<p><strong>Configuração do PHP:</strong></p>";
    echo "<ul>";
    echo "<li><strong>log_errors:</strong> " . (ini_get('log_errors') ? 'Ativado' : 'Desativado') . "</li>";
    echo "<li><strong>error_log:</strong> " . (ini_get('error_log') ?: 'Não definido') . "</li>";
    echo "<li><strong>display_errors:</strong> " . (ini_get('display_errors') ? 'Ativado' : 'Desativado') . "</li>";
    echo "</ul>";
    echo "</div>";
}

// Informações sobre a configuração atual
echo "<hr>";
echo "<h2>⚙️ Configuração Atual</h2>";
echo "<table border='1' cellpadding='5' cellspacing='0'>";
echo "<tr><th>Configuração</th><th>Valor</th></tr>";
echo "<tr><td>log_errors</td><td>" . (ini_get('log_errors') ? 'Ativado' : 'Desativado') . "</td></tr>";
echo "<tr><td>error_log</td><td>" . (ini_get('error_log') ?: 'Não definido') . "</td></tr>";
echo "<tr><td>display_errors</td><td>" . (ini_get('display_errors') ? 'Ativado' : 'Desativado') . "</td></tr>";
echo "<tr><td>error_reporting</td><td>" . error_reporting() . "</td></tr>";
echo "</table>";

// Teste de log
echo "<h2>🧪 Teste de Log</h2>";
echo "<p>Gerando uma entrada de teste no log...</p>";

error_log("NOTAS DEBUG: Teste de log gerado em " . date('Y-m-d H:i:s') . " pelo script verificar_logs_notas.php");

echo "<p style='color: green;'>✅ Log de teste gerado. Recarregue a página para ver se aparece acima.</p>";

echo "<hr>";
echo "<h2>🔗 Links Úteis</h2>";
echo "<p><a href='notas.php?action=lancar&curso_id=414&turma_id=355&disciplina_id=1747' target='_blank'>Página de lançamento de notas</a></p>";
echo "<p><a href='debug_notas_salvamento.php?curso_id=414&turma_id=355&disciplina_id=1747' target='_blank'>Debug específico do salvamento</a></p>";
echo "<p><a href='verificar_logs_notas.php'>Recarregar esta página</a></p>";

// Botão para limpar logs antigos
if ($_GET['limpar_logs'] ?? false) {
    foreach ($log_paths as $path) {
        if (file_exists($path) && is_writable($path)) {
            // Mantém apenas as últimas 100 linhas
            $linhas = file($path);
            if (count($linhas) > 100) {
                $linhas_mantidas = array_slice($linhas, -100);
                file_put_contents($path, implode('', $linhas_mantidas));
                echo "<p style='color: green;'>✅ Log limpo: " . htmlspecialchars($path) . "</p>";
            }
        }
    }
}

echo "<p><a href='verificar_logs_notas.php?limpar_logs=1' onclick='return confirm(\"Tem certeza que deseja limpar os logs antigos?\")'>🗑️ Limpar logs antigos</a></p>";
?>
