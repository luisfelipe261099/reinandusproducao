<?php
/**
 * Interceptor para debug de requisições POST para notas.php
 */

require_once 'includes/config.php';
require_once 'includes/Database.php';

// Verifica se o usuário está autenticado
if (!isset($_SESSION['usuario'])) {
    echo "<p style='color: red;'>❌ Usuário não está logado!</p>";
    echo "<p><a href='login.php'>Fazer login</a></p>";
    exit;
}

echo "<h1>🕵️ Interceptor de Requisições - Notas</h1>";

// Captura todas as informações da requisição
echo "<h2>📊 Informações da Requisição</h2>";
echo "<table border='1' cellpadding='5' cellspacing='0' style='width: 100%;'>";
echo "<tr><th>Propriedade</th><th>Valor</th></tr>";
echo "<tr><td><strong>Método</strong></td><td>" . $_SERVER['REQUEST_METHOD'] . "</td></tr>";
echo "<tr><td><strong>URL</strong></td><td>" . $_SERVER['REQUEST_URI'] . "</td></tr>";
echo "<tr><td><strong>User Agent</strong></td><td>" . ($_SERVER['HTTP_USER_AGENT'] ?? 'N/A') . "</td></tr>";
echo "<tr><td><strong>Referer</strong></td><td>" . ($_SERVER['HTTP_REFERER'] ?? 'N/A') . "</td></tr>";
echo "<tr><td><strong>Content Type</strong></td><td>" . ($_SERVER['CONTENT_TYPE'] ?? 'N/A') . "</td></tr>";
echo "<tr><td><strong>Content Length</strong></td><td>" . ($_SERVER['CONTENT_LENGTH'] ?? 'N/A') . "</td></tr>";
echo "</table>";

// Se for POST, mostra os dados
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h2>📨 Dados POST Recebidos</h2>";
    
    if (empty($_POST)) {
        echo "<p style='color: red;'>❌ Nenhum dado POST foi recebido!</p>";
        
        // Tenta ler o raw input
        $raw_input = file_get_contents('php://input');
        if (!empty($raw_input)) {
            echo "<h3>📄 Raw Input</h3>";
            echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px; max-height: 300px; overflow-y: auto;'>";
            echo htmlspecialchars($raw_input);
            echo "</pre>";
        }
    } else {
        echo "<p style='color: green;'>✅ Dados POST encontrados!</p>";
        echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px; max-height: 400px; overflow-y: auto;'>";
        print_r($_POST);
        echo "</pre>";
        
        // Analisa os dados específicos
        echo "<h3>🔍 Análise dos Dados</h3>";
        
        $action = $_POST['action'] ?? 'NÃO DEFINIDO';
        echo "<p><strong>Action:</strong> " . $action . "</p>";
        
        if ($action === 'salvar_lancamento') {
            echo "<p style='color: green;'>✅ Action correta detectada!</p>";
            
            $curso_id = $_POST['curso_id'] ?? null;
            $turma_id = $_POST['turma_id'] ?? null;
            $disciplina_id = $_POST['disciplina_id'] ?? null;
            $notas = $_POST['notas'] ?? [];
            
            echo "<ul>";
            echo "<li><strong>Curso ID:</strong> " . ($curso_id ?: 'VAZIO') . "</li>";
            echo "<li><strong>Turma ID:</strong> " . ($turma_id ?: 'VAZIO') . "</li>";
            echo "<li><strong>Disciplina ID:</strong> " . ($disciplina_id ?: 'VAZIO') . "</li>";
            echo "<li><strong>Notas:</strong> " . (empty($notas) ? 'VAZIO' : count($notas) . ' registros') . "</li>";
            echo "</ul>";
            
            if (!empty($notas)) {
                echo "<h4>📝 Detalhes das Notas</h4>";
                echo "<table border='1' cellpadding='5' cellspacing='0' style='width: 100%;'>";
                echo "<tr><th>Matrícula ID</th><th>Nota</th><th>Frequência</th><th>H. Aula</th><th>Situação</th><th>Observações</th></tr>";
                
                foreach ($notas as $matricula_id => $dados) {
                    echo "<tr>";
                    echo "<td>" . $matricula_id . "</td>";
                    echo "<td>" . ($dados['nota'] ?? 'vazio') . "</td>";
                    echo "<td>" . ($dados['frequencia'] ?? 'vazio') . "</td>";
                    echo "<td>" . ($dados['horas_aula'] ?? 'vazio') . "</td>";
                    echo "<td>" . ($dados['situacao'] ?? 'vazio') . "</td>";
                    echo "<td>" . ($dados['observacoes'] ?? 'vazio') . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
                
                // Simula a validação
                echo "<h4>✅ Simulação de Validação</h4>";
                $registros_validos = 0;
                
                foreach ($notas as $matricula_id => $dados) {
                    $tem_dados = !empty($dados['nota']) || !empty($dados['frequencia']) || !empty($dados['horas_aula']) || !empty($dados['observacoes']);
                    
                    if ($tem_dados) {
                        $registros_validos++;
                        echo "<p style='color: green;'>✅ Matrícula $matricula_id: TEM DADOS VÁLIDOS</p>";
                    } else {
                        echo "<p style='color: orange;'>⚠️ Matrícula $matricula_id: sem dados relevantes</p>";
                    }
                }
                
                echo "<p><strong>Total de registros que seriam processados:</strong> $registros_validos</p>";
            }
            
            // Agora vamos simular o redirecionamento para notas.php
            echo "<h3>🔄 Simulando Redirecionamento</h3>";
            echo "<p>Agora vou redirecionar esta requisição para notas.php para processamento real...</p>";
            
            // Cria um formulário oculto para reenviar os dados
            echo "<form id='redirect-form' method='POST' action='notas.php' style='display: none;'>";
            
            // Adiciona todos os campos POST
            foreach ($_POST as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $subkey => $subvalue) {
                        if (is_array($subvalue)) {
                            foreach ($subvalue as $subsubkey => $subsubvalue) {
                                echo "<input type='hidden' name='" . htmlspecialchars($key) . "[" . htmlspecialchars($subkey) . "][" . htmlspecialchars($subsubkey) . "]' value='" . htmlspecialchars($subsubvalue) . "'>";
                            }
                        } else {
                            echo "<input type='hidden' name='" . htmlspecialchars($key) . "[" . htmlspecialchars($subkey) . "]' value='" . htmlspecialchars($subvalue) . "'>";
                        }
                    }
                } else {
                    echo "<input type='hidden' name='" . htmlspecialchars($key) . "' value='" . htmlspecialchars($value) . "'>";
                }
            }
            
            echo "</form>";
            
            echo "<script>";
            echo "setTimeout(function() {";
            echo "  console.log('Redirecionando para notas.php...');";
            echo "  document.getElementById('redirect-form').submit();";
            echo "}, 3000);";
            echo "</script>";
            
            echo "<p style='color: blue; font-weight: bold;'>⏳ Redirecionando em 3 segundos...</p>";
            
        } else {
            echo "<p style='color: red;'>❌ Action incorreta: " . $action . "</p>";
        }
    }
} else {
    // Formulário de teste
    echo "<h2>🧪 Formulário de Teste</h2>";
    echo "<p>Este formulário enviará dados para este interceptor primeiro, que depois redirecionará para notas.php</p>";
    
    echo "<form method='POST' style='background: #f9f9f9; padding: 20px; border-radius: 5px;'>";
    echo "<input type='hidden' name='action' value='salvar_lancamento'>";
    echo "<input type='hidden' name='curso_id' value='414'>";
    echo "<input type='hidden' name='turma_id' value='355'>";
    echo "<input type='hidden' name='disciplina_id' value='1747'>";
    
    echo "<h3>Teste Simples:</h3>";
    echo "<table>";
    echo "<tr>";
    echo "<td>Nota para matrícula 15229:</td>";
    echo "<td><input type='number' name='notas[15229][nota]' value='8.5' min='0' max='10' step='0.1'></td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td>Frequência para matrícula 15229:</td>";
    echo "<td><input type='number' name='notas[15229][frequencia]' value='85.0' min='0' max='100' step='0.1'></td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td>Horas Aula:</td>";
    echo "<td><input type='number' name='notas[15229][horas_aula]' value='20' min='0'></td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td>Situação:</td>";
    echo "<td>";
    echo "<select name='notas[15229][situacao]'>";
    echo "<option value='cursando'>Cursando</option>";
    echo "<option value='aprovado' selected>Aprovado</option>";
    echo "<option value='reprovado'>Reprovado</option>";
    echo "</select>";
    echo "</td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td>Observações:</td>";
    echo "<td><input type='text' name='notas[15229][observacoes]' value='Teste via interceptor'></td>";
    echo "</tr>";
    echo "</table>";
    
    echo "<br>";
    echo "<button type='submit' style='padding: 10px 20px; background: #007cba; color: white; border: none; border-radius: 5px;'>🚀 Enviar via Interceptor</button>";
    echo "</form>";
}

echo "<hr>";
echo "<h2>🔗 Links Úteis</h2>";
echo "<p><a href='notas.php?action=lancar&curso_id=414&turma_id=355&disciplina_id=1747' target='_blank'>Página original de lançamento</a></p>";
echo "<p><a href='interceptor_notas.php'>Recarregar interceptor</a></p>";
echo "<p><a href='verificar_logs_notas.php' target='_blank'>Verificar logs</a></p>";
?>
