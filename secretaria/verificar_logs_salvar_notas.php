<?php
/**
 * Verificar logs específicos do botão "Salvar Notas"
 */

echo "<h1>📋 Logs do Botão 'Salvar Notas'</h1>";

// Caminho do arquivo de log do PHP
$log_paths = [
    'C:\xampp\php\logs\php_error_log',
    'C:\xampp\apache\logs\error.log',
    '/var/log/apache2/error.log',
    '/var/log/php_errors.log',
    ini_get('error_log')
];

$log_content = '';
$log_file_found = '';

foreach ($log_paths as $path) {
    if ($path && file_exists($path)) {
        $log_file_found = $path;
        $log_content = file_get_contents($path);
        break;
    }
}

if (!$log_content) {
    echo "<p style='color: red;'>❌ Arquivo de log não encontrado. Tentei:</p>";
    echo "<ul>";
    foreach ($log_paths as $path) {
        echo "<li>" . ($path ?: 'null') . "</li>";
    }
    echo "</ul>";
    
    echo "<h2>🔧 Como Habilitar Logs:</h2>";
    echo "<p>1. Abra o arquivo <code>php.ini</code></p>";
    echo "<p>2. Defina: <code>log_errors = On</code></p>";
    echo "<p>3. Defina: <code>error_log = C:\\xampp\\php\\logs\\php_error_log</code></p>";
    echo "<p>4. Reinicie o Apache</p>";
    
    exit;
}

echo "<p><strong>Arquivo de log encontrado:</strong> " . $log_file_found . "</p>";

// Filtra apenas as linhas relacionadas ao nosso debug
$lines = explode("\n", $log_content);
$debug_lines = [];

foreach ($lines as $line) {
    if (strpos($line, 'BOTÃO SALVAR NOTAS') !== false || 
        strpos($line, 'Processando matrícula') !== false ||
        strpos($line, 'Campo') !== false ||
        strpos($line, 'PULADA') !== false ||
        strpos($line, 'SERÁ PROCESSADA') !== false) {
        $debug_lines[] = $line;
    }
}

if (empty($debug_lines)) {
    echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>⚠️ Nenhum log de debug encontrado</h3>";
    echo "<p>Isso significa que:</p>";
    echo "<ul>";
    echo "<li>Você ainda não testou o botão 'Salvar Notas' após adicionar os logs</li>";
    echo "<li>Os logs não estão sendo gravados</li>";
    echo "<li>O arquivo de log está em outro local</li>";
    echo "</ul>";
    echo "<p><strong>Próximos passos:</strong></p>";
    echo "<ol>";
    echo "<li>Vá para o formulário de notas</li>";
    echo "<li>Preencha manualmente alguns campos de alunos</li>";
    echo "<li>Clique no botão 'Salvar Notas' (não nos botões individuais)</li>";
    echo "<li>Volte aqui para ver os logs</li>";
    echo "</ol>";
    echo "</div>";
} else {
    echo "<h2>📊 Logs Capturados (" . count($debug_lines) . " linhas)</h2>";
    
    echo "<div style='background: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
    echo "<pre style='white-space: pre-wrap; font-family: monospace; font-size: 12px; line-height: 1.4;'>";
    
    foreach ($debug_lines as $line) {
        // Destaca diferentes tipos de log com cores
        if (strpos($line, 'BOTÃO SALVAR NOTAS') !== false) {
            echo "<span style='color: #007cba; font-weight: bold;'>" . htmlspecialchars($line) . "</span>\n";
        } elseif (strpos($line, 'tem valor') !== false) {
            echo "<span style='color: #28a745;'>" . htmlspecialchars($line) . "</span>\n";
        } elseif (strpos($line, 'PULADA') !== false) {
            echo "<span style='color: #dc3545;'>" . htmlspecialchars($line) . "</span>\n";
        } elseif (strpos($line, 'SERÁ PROCESSADA') !== false) {
            echo "<span style='color: #28a745; font-weight: bold;'>" . htmlspecialchars($line) . "</span>\n";
        } else {
            echo "<span style='color: #6c757d;'>" . htmlspecialchars($line) . "</span>\n";
        }
    }
    
    echo "</pre>";
    echo "</div>";
    
    // Análise dos logs
    $total_processados = 0;
    $total_pulados = 0;
    $total_com_dados = 0;
    
    foreach ($debug_lines as $line) {
        if (strpos($line, 'Processando matrícula') !== false) {
            $total_processados++;
        } elseif (strpos($line, 'PULADA') !== false) {
            $total_pulados++;
        } elseif (strpos($line, 'SERÁ PROCESSADA') !== false) {
            $total_com_dados++;
        }
    }
    
    echo "<h3>📈 Análise dos Logs</h3>";
    echo "<div style='background: #e7f3ff; border: 1px solid #b3d9ff; color: #004085; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
    echo "<p><strong>Total de alunos processados:</strong> " . $total_processados . "</p>";
    echo "<p><strong>Alunos pulados (sem dados):</strong> " . $total_pulados . "</p>";
    echo "<p><strong>Alunos que seriam salvos:</strong> " . $total_com_dados . "</p>";
    
    if ($total_com_dados > 0) {
        echo "<p style='color: #28a745; font-weight: bold;'>✅ Deveria mostrar: 'Notas lançadas com sucesso! " . $total_com_dados . " registro(s) salvos.'</p>";
    } else {
        echo "<p style='color: #dc3545; font-weight: bold;'>❌ Mostra: 'Nenhuma nota foi lançada. Verifique se preencheu os campos corretamente.'</p>";
    }
    echo "</div>";
}

echo "<h2>🔗 Links Úteis</h2>";
echo "<p><a href='notas.php?action=lancar&curso_id=414&turma_id=355&disciplina_id=1748' target='_blank'>Formulário de notas</a></p>";
echo "<p><a href='verificar_logs_salvar_notas.php'>🔄 Recarregar logs</a></p>";

echo "<h2>🧪 Teste Específico</h2>";
echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
echo "<h4>Para testar o botão 'Salvar Notas':</h4>";
echo "<ol>";
echo "<li>Acesse o formulário de notas</li>";
echo "<li>Preencha <strong>manualmente</strong> alguns campos de 2-3 alunos</li>";
echo "<li>Clique no botão <strong>'Salvar Notas'</strong> (botão azul no rodapé)</li>";
echo "<li>Volte aqui para ver os logs detalhados</li>";
echo "</ol>";
echo "<p><strong>NÃO</strong> use os botões individuais verdes - eles já funcionam!</p>";
echo "</div>";
?>
