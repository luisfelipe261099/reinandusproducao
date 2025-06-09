<?php
/**
 * Criação de Novo Curso no AVA para o Polo
 * Permite ao polo criar um novo curso no AVA
 */

// Inicializa o sistema
require_once '../includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica se o usuário é do tipo polo
if (getUsuarioTipo() !== 'polo') {
    setMensagem('erro', 'Você não tem permissão para acessar esta página.');
    redirect('../index.php');
    exit;
}

// Instancia o banco de dados
$db = Database::getInstance();

// Obtém o ID do polo
$polo_id = getUsuarioPoloId();

// Verifica se o polo tem acesso ao AVA
$sql = "SELECT * FROM ava_polos_acesso WHERE polo_id = ? AND liberado = 1";
$acesso = $db->fetchOne($sql, [$polo_id]);

if (!$acesso) {
    setMensagem('erro', 'Seu polo não possui acesso liberado ao AVA. Entre em contato com a secretaria para mais informações.');
    redirect('index.php');
    exit;
}

// Busca as categorias disponíveis
$sql = "SELECT * FROM ava_categorias WHERE status = 'ativo' ORDER BY nome";
$categorias = $db->fetchAll($sql);

// Processa o formulário de criação de curso
if (isPost()) {
    $titulo = $_POST['titulo'] ?? '';
    $descricao = $_POST['descricao'] ?? '';
    $carga_horaria = $_POST['carga_horaria'] ?? null;
    $categoria = $_POST['categoria'] ?? null;
    $nivel = $_POST['nivel'] ?? 'basico';
    $objetivos = $_POST['objetivos'] ?? '';
    $publico_alvo = $_POST['publico_alvo'] ?? '';
    $pre_requisitos = $_POST['pre_requisitos'] ?? '';
    
    // Validação básica
    $errors = [];
    
    if (empty($titulo)) {
        $errors[] = 'O título do curso é obrigatório.';
    }
    
    if (empty($descricao)) {
        $errors[] = 'A descrição do curso é obrigatória.';
    }
    
    if (empty($carga_horaria) || !is_numeric($carga_horaria) || $carga_horaria <= 0) {
        $errors[] = 'A carga horária deve ser um número positivo.';
    }
    
    // Verifica se a categoria existe
    if (!empty($categoria)) {
        $sql = "SELECT * FROM ava_categorias WHERE nome = ? AND status = 'ativo'";
        $cat = $db->fetchOne($sql, [$categoria]);
        
        if (!$cat) {
            $errors[] = 'A categoria selecionada não existe ou não está ativa.';
        }
    }
    
    // Verifica se o nível é válido
    $niveis_validos = ['basico', 'intermediario', 'avancado'];
    if (!in_array($nivel, $niveis_validos)) {
        $errors[] = 'O nível selecionado não é válido.';
    }
    
    // Processa o upload da imagem
    $imagem = null;
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
        $arquivo = $_FILES['imagem'];
        $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
        $extensoes_permitidas = ['jpg', 'jpeg', 'png'];
        
        if (!in_array($extensao, $extensoes_permitidas)) {
            $errors[] = 'Tipo de arquivo não permitido para a imagem. Envie apenas JPG, JPEG ou PNG.';
        } else {
            // Verifica o tamanho do arquivo (máximo 2MB)
            $tamanho_maximo = 2 * 1024 * 1024; // 2MB em bytes
            if ($arquivo['size'] > $tamanho_maximo) {
                $errors[] = 'A imagem é muito grande. O tamanho máximo permitido é 2MB.';
            } else {
                // Cria o diretório de upload se não existir
                $diretorio_upload = '../uploads/ava/cursos/';
                if (!file_exists($diretorio_upload)) {
                    mkdir($diretorio_upload, 0777, true);
                }
                
                // Gera um nome único para o arquivo
                $nome_arquivo = 'curso_' . time() . '_' . uniqid() . '.' . $extensao;
                $caminho_arquivo = $diretorio_upload . $nome_arquivo;
                
                // Move o arquivo para o diretório de upload
                if (move_uploaded_file($arquivo['tmp_name'], $caminho_arquivo)) {
                    $imagem = 'uploads/ava/cursos/' . $nome_arquivo;
                } else {
                    $errors[] = 'Erro ao fazer upload da imagem. Tente novamente.';
                }
            }
        }
    }
    
    // Processa o upload do vídeo de apresentação
    $video_apresentacao = null;
    if (!empty($_POST['video_apresentacao'])) {
        $video_apresentacao = $_POST['video_apresentacao'];
        
        // Verifica se é uma URL válida
        if (!filter_var($video_apresentacao, FILTER_VALIDATE_URL)) {
            $errors[] = 'A URL do vídeo de apresentação não é válida.';
        }
    }
    
    // Se não houver erros, cria o curso
    if (empty($errors)) {
        try {
            // Insere o curso no banco de dados
            $sql = "INSERT INTO ava_cursos (
                        polo_id, titulo, descricao, carga_horaria, categoria, nivel, 
                        imagem, video_apresentacao, objetivos, publico_alvo, pre_requisitos, 
                        status, created_at, updated_at
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'rascunho', NOW(), NOW()
                    )";
            
            $db->query($sql, [
                $polo_id, $titulo, $descricao, $carga_horaria, $categoria, $nivel,
                $imagem, $video_apresentacao, $objetivos, $publico_alvo, $pre_requisitos
            ]);
            
            $curso_id = $db->lastInsertId();
            
            setMensagem('sucesso', 'Curso criado com sucesso! Agora você pode adicionar módulos e aulas.');
            redirect('ava_curso_conteudo.php?id=' . $curso_id);
            exit;
        } catch (Exception $e) {
            $errors[] = 'Erro ao criar o curso: ' . $e->getMessage();
        }
    }
}

// Define o título da página
$titulo_pagina = 'Criar Novo Curso';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faciência ERP - <?php echo $titulo_pagina; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <?php include '../includes/sidebar_polo.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <?php include '../includes/header_polo.php'; ?>

            <!-- Main -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
                <div class="container mx-auto">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-800"><?php echo $titulo_pagina; ?></h1>
                            <p class="text-gray-600">Preencha o formulário para criar um novo curso no AVA</p>
                        </div>
                        <a href="ava_cursos.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gray-600 hover:bg-gray-700">
                            <i class="fas fa-arrow-left mr-2"></i> Voltar
                        </a>
                    </div>

                    <?php if (isset($_SESSION['mensagem']) && isset($_SESSION['mensagem_tipo'])): ?>
                    <div class="bg-<?php echo $_SESSION['mensagem_tipo'] === 'sucesso' ? 'green' : 'red'; ?>-100 border-l-4 border-<?php echo $_SESSION['mensagem_tipo'] === 'sucesso' ? 'green' : 'red'; ?>-500 text-<?php echo $_SESSION['mensagem_tipo'] === 'sucesso' ? 'green' : 'red'; ?>-700 p-4 mb-6">
                        <?php echo is_array($_SESSION['mensagem']) ? implode(', ', $_SESSION['mensagem']) : $_SESSION['mensagem']; ?>
                    </div>
                    <?php
                    // Limpa a mensagem da sessão
                    unset($_SESSION['mensagem']);
                    unset($_SESSION['mensagem_tipo']);
                    endif;
                    ?>

                    <?php if (!empty($errors)): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
                        <h3 class="font-medium">Corrija os seguintes erros:</h3>
                        <ul class="mt-2 ml-4 list-disc">
                            <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <!-- Formulário de Criação de Curso -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-800">Informações do Curso</h2>
                        </div>
                        <div class="p-6">
                            <form action="ava_cursos_novo.php" method="post" enctype="multipart/form-data">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                    <div>
                                        <label for="titulo" class="block text-sm font-medium text-gray-700 mb-1">Título do Curso*</label>
                                        <input type="text" id="titulo" name="titulo" value="<?php echo isset($_POST['titulo']) ? htmlspecialchars($_POST['titulo']) : ''; ?>" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-500 focus:ring-opacity-50">
                                    </div>
                                    
                                    <div>
                                        <label for="categoria" class="block text-sm font-medium text-gray-700 mb-1">Categoria</label>
                                        <select id="categoria" name="categoria" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-500 focus:ring-opacity-50">
                                            <option value="">Selecione uma categoria</option>
                                            <?php foreach ($categorias as $categoria): ?>
                                            <option value="<?php echo $categoria['nome']; ?>" <?php echo isset($_POST['categoria']) && $_POST['categoria'] === $categoria['nome'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($categoria['nome']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="mb-6">
                                    <label for="descricao" class="block text-sm font-medium text-gray-700 mb-1">Descrição do Curso*</label>
                                    <textarea id="descricao" name="descricao" rows="4" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-500 focus:ring-opacity-50"><?php echo isset($_POST['descricao']) ? htmlspecialchars($_POST['descricao']) : ''; ?></textarea>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                    <div>
                                        <label for="carga_horaria" class="block text-sm font-medium text-gray-700 mb-1">Carga Horária (horas)*</label>
                                        <input type="number" id="carga_horaria" name="carga_horaria" value="<?php echo isset($_POST['carga_horaria']) ? htmlspecialchars($_POST['carga_horaria']) : ''; ?>" required min="1" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-500 focus:ring-opacity-50">
                                    </div>
                                    
                                    <div>
                                        <label for="nivel" class="block text-sm font-medium text-gray-700 mb-1">Nível</label>
                                        <select id="nivel" name="nivel" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-500 focus:ring-opacity-50">
                                            <option value="basico" <?php echo isset($_POST['nivel']) && $_POST['nivel'] === 'basico' ? 'selected' : ''; ?>>Básico</option>
                                            <option value="intermediario" <?php echo isset($_POST['nivel']) && $_POST['nivel'] === 'intermediario' ? 'selected' : ''; ?>>Intermediário</option>
                                            <option value="avancado" <?php echo isset($_POST['nivel']) && $_POST['nivel'] === 'avancado' ? 'selected' : ''; ?>>Avançado</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                    <div>
                                        <label for="imagem" class="block text-sm font-medium text-gray-700 mb-1">Imagem de Capa</label>
                                        <input type="file" id="imagem" name="imagem" accept=".jpg,.jpeg,.png" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-500 focus:ring-opacity-50">
                                        <p class="mt-1 text-xs text-gray-500">Formatos aceitos: JPG, JPEG, PNG. Tamanho máximo: 2MB.</p>
                                    </div>
                                    
                                    <div>
                                        <label for="video_apresentacao" class="block text-sm font-medium text-gray-700 mb-1">URL do Vídeo de Apresentação</label>
                                        <input type="url" id="video_apresentacao" name="video_apresentacao" value="<?php echo isset($_POST['video_apresentacao']) ? htmlspecialchars($_POST['video_apresentacao']) : ''; ?>" placeholder="https://www.youtube.com/watch?v=..." class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-500 focus:ring-opacity-50">
                                        <p class="mt-1 text-xs text-gray-500">Cole a URL do vídeo do YouTube, Vimeo ou outra plataforma.</p>
                                    </div>
                                </div>
                                
                                <div class="mb-6">
                                    <label for="objetivos" class="block text-sm font-medium text-gray-700 mb-1">Objetivos do Curso</label>
                                    <textarea id="objetivos" name="objetivos" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-500 focus:ring-opacity-50"><?php echo isset($_POST['objetivos']) ? htmlspecialchars($_POST['objetivos']) : ''; ?></textarea>
                                    <p class="mt-1 text-xs text-gray-500">Descreva os principais objetivos de aprendizagem do curso.</p>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                    <div>
                                        <label for="publico_alvo" class="block text-sm font-medium text-gray-700 mb-1">Público-Alvo</label>
                                        <textarea id="publico_alvo" name="publico_alvo" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-500 focus:ring-opacity-50"><?php echo isset($_POST['publico_alvo']) ? htmlspecialchars($_POST['publico_alvo']) : ''; ?></textarea>
                                        <p class="mt-1 text-xs text-gray-500">Para quem este curso é destinado?</p>
                                    </div>
                                    
                                    <div>
                                        <label for="pre_requisitos" class="block text-sm font-medium text-gray-700 mb-1">Pré-Requisitos</label>
                                        <textarea id="pre_requisitos" name="pre_requisitos" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-500 focus:ring-opacity-50"><?php echo isset($_POST['pre_requisitos']) ? htmlspecialchars($_POST['pre_requisitos']) : ''; ?></textarea>
                                        <p class="mt-1 text-xs text-gray-500">Conhecimentos ou habilidades necessários para o curso.</p>
                                    </div>
                                </div>
                                
                                <div class="flex justify-end space-x-2">
                                    <a href="ava_cursos.php" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                        Cancelar
                                    </a>
                                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                                        <i class="fas fa-save mr-2"></i> Criar Curso
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </main>

            <!-- Footer -->
            <?php include '../includes/footer_polo.php'; ?>
        </div>
    </div>

    <script>
        // Toggle sidebar
        document.getElementById('toggle-sidebar').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('sidebar-collapsed');
            sidebar.classList.toggle('sidebar-expanded');

            const labels = document.querySelectorAll('.sidebar-label');
            labels.forEach(label => {
                label.classList.toggle('hidden');
            });
        });

        // Toggle user menu
        document.getElementById('user-menu-button').addEventListener('click', function() {
            const menu = document.getElementById('user-menu');
            menu.classList.toggle('hidden');
        });
    </script>
</body>
</html>
