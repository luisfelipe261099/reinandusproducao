<?php
// Inicializa a sessão
session_start();

// Configuração para exibir erros
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Define um ID de polo para teste
$_SESSION['usuario_id'] = 1;
$_SESSION['usuario_tipo'] = 'polo';
$_SESSION['polo_id'] = 1;

// Verifica se o diretório de uploads existe
$upload_dir = '../uploads/ava/imagens/1';
if (!file_exists($upload_dir)) {
    if (!mkdir($upload_dir, 0755, true)) {
        echo "Erro: Não foi possível criar o diretório de uploads.";
    } else {
        echo "Diretório de uploads criado com sucesso: $upload_dir<br>";
    }
} else {
    echo "Diretório de uploads já existe: $upload_dir<br>";
}

// Verifica permissões
if (is_writable($upload_dir)) {
    echo "O diretório tem permissão de escrita.<br>";
} else {
    echo "ERRO: O diretório NÃO tem permissão de escrita.<br>";
    
    // Tenta corrigir as permissões
    if (chmod($upload_dir, 0755)) {
        echo "Permissões corrigidas para 0755.<br>";
        if (is_writable($upload_dir)) {
            echo "Agora o diretório tem permissão de escrita.<br>";
        } else {
            echo "O diretório ainda não tem permissão de escrita.<br>";
        }
    } else {
        echo "Não foi possível corrigir as permissões.<br>";
    }
}

// Verifica se o PHP tem permissão para fazer upload
$temp_dir = sys_get_temp_dir();
echo "Diretório temporário do PHP: $temp_dir<br>";
if (is_writable($temp_dir)) {
    echo "O diretório temporário tem permissão de escrita.<br>";
} else {
    echo "ERRO: O diretório temporário NÃO tem permissão de escrita.<br>";
}

// Verifica configurações de upload do PHP
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "<br>";
echo "post_max_size: " . ini_get('post_max_size') . "<br>";
echo "max_execution_time: " . ini_get('max_execution_time') . "<br>";
echo "memory_limit: " . ini_get('memory_limit') . "<br>";

// Formulário de teste simples
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Upload Simples</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        form { margin-top: 20px; padding: 20px; border: 1px solid #ccc; border-radius: 5px; }
        .result { margin-top: 20px; padding: 10px; border: 1px solid #ccc; border-radius: 5px; background-color: #f9f9f9; }
        .success { color: green; }
        .error { color: red; }
        img { max-width: 300px; margin-top: 10px; }
    </style>
</head>
<body>
    <h1>Teste de Upload Simples</h1>
    
    <form action="upload_imagem.php" method="post" enctype="multipart/form-data">
        <div>
            <label for="imagem">Selecione uma imagem:</label>
            <input type="file" name="imagem" id="imagem" accept="image/*">
        </div>
        <div style="margin-top: 10px;">
            <label for="alt_text">Texto alternativo:</label>
            <input type="text" name="alt_text" id="alt_text" value="Imagem de teste">
        </div>
        <div style="margin-top: 10px;">
            <label for="aula_id">ID da Aula:</label>
            <input type="number" name="aula_id" id="aula_id" value="0">
        </div>
        <div style="margin-top: 20px;">
            <button type="submit">Enviar</button>
        </div>
    </form>
    
    <div class="result" id="result" style="display: none;">
        <h2>Resultado:</h2>
        <div id="result-content"></div>
    </div>
    
    <script>
        document.querySelector('form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const resultDiv = document.getElementById('result');
            const resultContent = document.getElementById('result-content');
            
            resultDiv.style.display = 'none';
            resultContent.innerHTML = 'Enviando...';
            
            fetch('upload_imagem.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro HTTP: ' + response.status);
                }
                return response.text();
            })
            .then(text => {
                resultDiv.style.display = 'block';
                
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        resultContent.innerHTML = `
                            <div class="success">
                                <p><strong>Sucesso!</strong> ${data.message}</p>
                                <p>URL do arquivo: ${data.file_url}</p>
                                <img src="${data.file_url}" alt="Imagem enviada">
                            </div>
                        `;
                    } else {
                        resultContent.innerHTML = `
                            <div class="error">
                                <p><strong>Erro:</strong> ${data.message}</p>
                                ${data.debug ? `<pre>${JSON.stringify(data.debug, null, 2)}</pre>` : ''}
                            </div>
                        `;
                    }
                } catch (e) {
                    resultContent.innerHTML = `
                        <div class="error">
                            <p><strong>Erro ao processar resposta:</strong> ${e.message}</p>
                            <pre>${text}</pre>
                        </div>
                    `;
                }
            })
            .catch(error => {
                resultDiv.style.display = 'block';
                resultContent.innerHTML = `
                    <div class="error">
                        <p><strong>Erro:</strong> ${error.message}</p>
                    </div>
                `;
            });
        });
    </script>
</body>
</html>
