<?php
// Inicializa a sessão
session_start();

// Inclui os arquivos necessários
require_once '../config.php';
require_once '../includes/functions.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['usuario_tipo']) || $_SESSION['usuario_tipo'] !== 'polo') {
    setMensagem('erro', 'Acesso não autorizado.');
    redirect('index.php');
    exit;
}

// Define o ID do polo para teste
$_SESSION['polo_id'] = isset($_SESSION['polo_id']) ? $_SESSION['polo_id'] : 1;

// Verifica se o diretório de uploads existe
$upload_dir = '../uploads/ava/imagens/' . $_SESSION['polo_id'];
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
}

// Verifica se a tabela ava_imagens existe
try {
    require_once '../includes/db.php';
    $db = new DB();
    
    // Tenta fazer uma consulta simples
    $result = $db->query("SHOW TABLES LIKE 'ava_imagens'");
    if (count($result) > 0) {
        echo "A tabela ava_imagens existe no banco de dados.<br>";
    } else {
        echo "ERRO: A tabela ava_imagens NÃO existe no banco de dados.<br>";
        echo "Execute o SQL: <pre>".file_get_contents('../sql/create_ava_imagens_table.sql')."</pre>";
    }
} catch (Exception $e) {
    echo "ERRO ao verificar a tabela: " . $e->getMessage() . "<br>";
}

// Formulário de teste
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Upload de Imagem</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css">
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-md mx-auto bg-white p-6 rounded-lg shadow-md">
        <h1 class="text-2xl font-bold mb-6">Teste de Upload de Imagem</h1>
        
        <form id="upload-form" enctype="multipart/form-data" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Selecione uma imagem</label>
                <input type="file" name="imagem" id="imagem" accept="image/*" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700">Texto alternativo</label>
                <input type="text" name="alt_text" id="alt_text" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700">ID da Aula (opcional)</label>
                <input type="number" name="aula_id" id="aula_id" value="0" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
            </div>
            
            <button type="button" onclick="uploadImage()" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700">
                Fazer Upload
            </button>
        </form>
        
        <div id="result" class="mt-6 p-4 border border-gray-200 rounded-md hidden">
            <h2 class="text-lg font-medium mb-2">Resultado:</h2>
            <pre id="result-content" class="bg-gray-100 p-3 rounded text-sm overflow-auto max-h-60"></pre>
        </div>
        
        <div id="image-preview" class="mt-6 hidden">
            <h2 class="text-lg font-medium mb-2">Imagem enviada:</h2>
            <img id="preview-img" src="" alt="Preview" class="max-w-full h-auto border border-gray-200 rounded-md">
        </div>
    </div>
    
    <script>
        function uploadImage() {
            const form = document.getElementById('upload-form');
            const formData = new FormData(form);
            
            // Verifica se um arquivo foi selecionado
            const fileInput = document.getElementById('imagem');
            if (!fileInput.files.length) {
                alert('Por favor, selecione uma imagem para upload.');
                return;
            }
            
            // Esconde resultados anteriores
            document.getElementById('result').classList.add('hidden');
            document.getElementById('image-preview').classList.add('hidden');
            
            // Cria uma requisição AJAX
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'upload_imagem.php', true);
            
            // Quando o upload for concluído
            xhr.onload = function() {
                const resultDiv = document.getElementById('result');
                const resultContent = document.getElementById('result-content');
                
                resultDiv.classList.remove('hidden');
                resultContent.textContent = xhr.responseText;
                
                // Tenta processar a resposta como JSON
                try {
                    const response = JSON.parse(xhr.responseText);
                    
                    if (response.success && response.file_url) {
                        // Mostra a imagem enviada
                        document.getElementById('image-preview').classList.remove('hidden');
                        document.getElementById('preview-img').src = response.file_url;
                    }
                } catch (e) {
                    console.error('Erro ao processar resposta:', e);
                }
            };
            
            // Em caso de erro
            xhr.onerror = function() {
                const resultDiv = document.getElementById('result');
                const resultContent = document.getElementById('result-content');
                
                resultDiv.classList.remove('hidden');
                resultContent.textContent = 'Erro de conexão. Verifique sua internet.';
            };
            
            // Envia os dados
            xhr.send(formData);
        }
    </script>
</body>
</html>
