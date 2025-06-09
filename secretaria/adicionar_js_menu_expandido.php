<?php
/**
 * Script para adicionar a referência ao arquivo force-expanded-menu.js em todas as páginas do sistema
 */

// Inicializa o sistema
require_once 'includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Define o diretório raiz
$rootDir = __DIR__;

// Define os diretórios a serem verificados
$directories = [
    $rootDir,
    $rootDir . '/ava',
    $rootDir . '/polo',
    $rootDir . '/secretaria',
    $rootDir . '/chamados',
    $rootDir . '/financeiro'
];

// Define os arquivos a serem ignorados
$ignoreFiles = [
    'adicionar_js_menu_expandido.php',
    'login.php',
    'logout.php',
    'index.php',
    'config.php',
    'init.php',
    'functions.php',
    'database.php',
    'auth.php',
    'utils.php'
];

// Contador de arquivos modificados
$modifiedFiles = 0;
$failedFiles = 0;
$skippedFiles = 0;

// Lista de arquivos modificados
$modifiedFilesList = [];
$failedFilesList = [];
$skippedFilesList = [];

// Função para verificar se o arquivo já contém a referência ao script
function containsScriptReference($content, $scriptPath) {
    return strpos($content, $scriptPath) !== false;
}

// Função para adicionar a referência ao script antes do fechamento da tag </body>
function addScriptReference($content, $scriptPath) {
    // Verifica se o arquivo já contém a referência ao script
    if (containsScriptReference($content, $scriptPath)) {
        return [false, $content];
    }
    
    // Adiciona a referência ao script antes do fechamento da tag </body>
    $pattern = '/<\/body>/i';
    $replacement = "    <script src=\"$scriptPath\"></script>\n</body>";
    $newContent = preg_replace($pattern, $replacement, $content);
    
    // Verifica se a substituição foi bem-sucedida
    if ($newContent === $content) {
        return [false, $content];
    }
    
    return [true, $newContent];
}

// Função para processar um arquivo
function processFile($filePath, $scriptPath) {
    global $modifiedFiles, $failedFiles, $skippedFiles;
    global $modifiedFilesList, $failedFilesList, $skippedFilesList;
    
    // Verifica se o arquivo existe
    if (!file_exists($filePath)) {
        $skippedFiles++;
        $skippedFilesList[] = $filePath . ' (arquivo não existe)';
        return;
    }
    
    // Verifica se o arquivo é um arquivo PHP ou HTML
    $extension = pathinfo($filePath, PATHINFO_EXTENSION);
    if ($extension !== 'php' && $extension !== 'html') {
        $skippedFiles++;
        $skippedFilesList[] = $filePath . ' (não é um arquivo PHP ou HTML)';
        return;
    }
    
    // Lê o conteúdo do arquivo
    $content = file_get_contents($filePath);
    
    // Verifica se o arquivo contém a tag </body>
    if (strpos($content, '</body>') === false) {
        $skippedFiles++;
        $skippedFilesList[] = $filePath . ' (não contém a tag </body>)';
        return;
    }
    
    // Adiciona a referência ao script
    list($modified, $newContent) = addScriptReference($content, $scriptPath);
    
    // Se o arquivo foi modificado, salva o novo conteúdo
    if ($modified) {
        if (file_put_contents($filePath, $newContent)) {
            $modifiedFiles++;
            $modifiedFilesList[] = $filePath;
        } else {
            $failedFiles++;
            $failedFilesList[] = $filePath . ' (erro ao salvar o arquivo)';
        }
    } else {
        $skippedFiles++;
        $skippedFilesList[] = $filePath . ' (já contém a referência ao script ou não foi possível adicionar)';
    }
}

// Função para processar um diretório
function processDirectory($directory, $scriptPath) {
    global $ignoreFiles;
    
    // Verifica se o diretório existe
    if (!is_dir($directory)) {
        return;
    }
    
    // Abre o diretório
    $dir = opendir($directory);
    
    // Processa cada arquivo no diretório
    while (($file = readdir($dir)) !== false) {
        // Ignora os diretórios . e ..
        if ($file === '.' || $file === '..') {
            continue;
        }
        
        // Ignora os arquivos na lista de ignorados
        if (in_array($file, $ignoreFiles)) {
            continue;
        }
        
        // Caminho completo do arquivo
        $filePath = $directory . '/' . $file;
        
        // Se for um diretório, processa recursivamente
        if (is_dir($filePath)) {
            processDirectory($filePath, $scriptPath);
        } else {
            // Processa o arquivo
            processFile($filePath, $scriptPath);
        }
    }
    
    // Fecha o diretório
    closedir($dir);
}

// Caminho relativo do script
$scriptPath = 'js/force-expanded-menu.js';

// Processa cada diretório
foreach ($directories as $directory) {
    processDirectory($directory, $scriptPath);
}

// Exibe o resultado
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adicionar JS Menu Expandido</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .result-container {
            margin-top: 20px;
            padding: 20px;
            background-color: #f9fafb;
            border-radius: 8px;
        }
        .file-list {
            max-height: 300px;
            overflow-y: auto;
            margin-top: 10px;
            padding: 10px;
            background-color: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
        }
        .success {
            color: #10b981;
        }
        .warning {
            color: #f59e0b;
        }
        .error {
            color: #ef4444;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <?php include 'includes/header.php'; ?>

            <!-- Main -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
                <div class="container mx-auto">
                    <div class="flex justify-between items-center mb-6">
                        <h1 class="text-2xl font-bold text-gray-800">Adicionar JS Menu Expandido</h1>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">Resultado da Operação</h2>
                        
                        <div class="result-container">
                            <p class="mb-2"><i class="fas fa-check-circle success"></i> <strong>Arquivos modificados:</strong> <?php echo $modifiedFiles; ?></p>
                            <p class="mb-2"><i class="fas fa-exclamation-circle warning"></i> <strong>Arquivos ignorados:</strong> <?php echo $skippedFiles; ?></p>
                            <p class="mb-2"><i class="fas fa-times-circle error"></i> <strong>Falhas:</strong> <?php echo $failedFiles; ?></p>
                            
                            <?php if (!empty($modifiedFilesList)): ?>
                            <h3 class="font-semibold mt-4 mb-2">Arquivos Modificados:</h3>
                            <div class="file-list">
                                <ul class="list-disc pl-5">
                                    <?php foreach ($modifiedFilesList as $file): ?>
                                    <li><?php echo htmlspecialchars($file); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($failedFilesList)): ?>
                            <h3 class="font-semibold mt-4 mb-2">Falhas:</h3>
                            <div class="file-list">
                                <ul class="list-disc pl-5">
                                    <?php foreach ($failedFilesList as $file): ?>
                                    <li><?php echo htmlspecialchars($file); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mt-6">
                            <a href="index.php" class="btn-primary px-4 py-2 rounded-lg">Voltar para o Dashboard</a>
                        </div>
                    </div>
                </div>
            </main>

            <!-- Footer -->
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script src="js/force-expanded-menu.js"></script>
</body>
</html>
