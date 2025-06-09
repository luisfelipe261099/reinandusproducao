<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Upload Simplificado</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1 { color: #333; }
        form { margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="file"] { margin-bottom: 15px; }
        button { background: #4CAF50; color: white; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer; }
        button:hover { background: #45a049; }
        #result { margin-top: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; display: none; }
        .success { color: #4CAF50; }
        .error { color: #f44336; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; }
        img { max-width: 100%; height: auto; margin-top: 15px; border: 1px solid #ddd; }
    </style>
</head>
<body>
    <h1>Teste de Upload Simplificado</h1>
    
    <p>Esta página testa o upload de imagens usando um script PHP simplificado.</p>
    
    <form id="uploadForm">
        <div>
            <label for="imagem">Selecione uma imagem:</label>
            <input type="file" id="imagem" name="imagem" accept="image/*" required>
        </div>
        <button type="submit">Enviar</button>
    </form>
    
    <div id="result">
        <h2>Resultado:</h2>
        <div id="resultContent"></div>
    </div>
    
    <script>
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const resultDiv = document.getElementById('result');
            const resultContent = document.getElementById('resultContent');
            
            resultDiv.style.display = 'none';
            
            // Verifica se um arquivo foi selecionado
            const fileInput = document.getElementById('imagem');
            if (!fileInput.files.length) {
                alert('Por favor, selecione uma imagem.');
                return;
            }
            
            // Envia o arquivo
            fetch('upload_simples.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(text => {
                resultDiv.style.display = 'block';
                
                try {
                    const data = JSON.parse(text);
                    
                    if (data.success) {
                        resultContent.innerHTML = `
                            <div class="success">
                                <p><strong>Sucesso!</strong> ${data.message}</p>
                                <p>URL do arquivo: ${data.file_url}</p>
                                <p>Nome do arquivo: ${data.file_name}</p>
                                <p>Caminho completo: ${data.file_path}</p>
                                <p>Caminho base: ${data.base_path}</p>
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
                        <p><strong>Erro de conexão:</strong> ${error.message}</p>
                    </div>
                `;
            });
        });
    </script>
</body>
</html>
