<?php
/**
 * Edição de Curso do AVA
 * Permite editar um curso existente no Ambiente Virtual de Aprendizagem
 */

// Inicializa o sistema
require_once '../includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica se o usuário é do tipo polo
if (getUsuarioTipo() !== 'polo') {
    setMensagem('erro', 'Você não tem permissão para acessar esta página.');
    redirect('../polo/index.php');
    exit;
}

// Instancia o banco de dados
$db = Database::getInstance();

// Obtém o ID do polo
$polo_id = getUsuarioPoloId();

// Verifica se o polo tem acesso ao AVA
if (!$polo_id) {
    setMensagem('erro', 'Não foi possível identificar o polo associado ao seu usuário. Entre em contato com a secretaria para mais informações.');
    redirect('../polo/index.php');
    exit;
}

// Verifica se o polo existe
$sql = "SELECT * FROM polos WHERE id = ?";
$polo = $db->fetchOne($sql, [$polo_id]);

if (!$polo) {
    setMensagem('erro', 'Polo não encontrado no sistema. Entre em contato com a secretaria para mais informações.');
    redirect('../polo/index.php');
    exit;
}

// Verifica se o polo tem acesso ao AVA
$sql = "SELECT * FROM ava_polos_acesso WHERE polo_id = ?";
$acesso = $db->fetchOne($sql, [$polo_id]);

if (!$acesso || $acesso['liberado'] != 1) {
    setMensagem('erro', 'Seu polo não possui acesso liberado ao AVA. Entre em contato com a secretaria para mais informações.');
    redirect('../polo/index.php');
    exit;
}

// Verifica se o ID do curso foi informado
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setMensagem('erro', 'Curso não informado.');
    redirect('cursos.php');
    exit;
}

$curso_id = (int)$_GET['id'];

// Busca o curso
$sql = "SELECT * FROM ava_cursos WHERE id = ? AND polo_id = ?";
$curso = $db->fetchOne($sql, [$curso_id, $polo_id]);

if (!$curso) {
    setMensagem('erro', 'Curso não encontrado ou você não tem permissão para editá-lo.');
    redirect('cursos.php');
    exit;
}

// Busca as categorias disponíveis
$sql = "SELECT * FROM ava_categorias WHERE status = 'ativo' ORDER BY nome";
$categorias = $db->fetchAll($sql);

// Processa o formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtém os dados do formulário
    $titulo = $_POST['titulo'] ?? '';
    $descricao = $_POST['descricao'] ?? '';
    $categoria = $_POST['categoria'] ?? '';
    $carga_horaria = $_POST['carga_horaria'] ?? '';
    $status = $_POST['status'] ?? 'rascunho';
    $preco = $_POST['preco'] ?? '';
    $preco_promocional = $_POST['preco_promocional'] ?? '';
    $data_inicio = $_POST['data_inicio'] ?? '';
    $data_fim = $_POST['data_fim'] ?? '';
    $requisitos = $_POST['requisitos'] ?? '';
    $publico_alvo = $_POST['publico_alvo'] ?? '';
    $objetivos = $_POST['objetivos'] ?? '';
    $metodologia = $_POST['metodologia'] ?? '';
    $avaliacao = $_POST['avaliacao'] ?? '';
    $certificacao = $_POST['certificacao'] ?? '';
    $destaque = isset($_POST['destaque']) ? 1 : 0;
    $visibilidade = $_POST['visibilidade'] ?? 'publico';

    // Validação dos dados
    $erros = [];

    if (empty($titulo)) {
        $erros[] = 'O título do curso é obrigatório.';
    }

    if (empty($descricao)) {
        $erros[] = 'A descrição do curso é obrigatória.';
    }

    if (empty($categoria)) {
        $erros[] = 'A categoria do curso é obrigatória.';
    }

    if (empty($carga_horaria)) {
        $erros[] = 'A carga horária do curso é obrigatória.';
    } elseif (!is_numeric($carga_horaria) || $carga_horaria <= 0) {
        $erros[] = 'A carga horária deve ser um número positivo.';
    }

    // Processa a imagem do curso
    $imagem = $curso['imagem']; // Mantém a imagem atual por padrão

    if (!empty($_FILES['imagem']['name'])) {
        $arquivo_tmp = $_FILES['imagem']['tmp_name'];
        $arquivo_nome = $_FILES['imagem']['name'];
        $arquivo_extensao = strtolower(pathinfo($arquivo_nome, PATHINFO_EXTENSION));
        $arquivo_tamanho = $_FILES['imagem']['size'];

        // Validação da imagem
        $extensoes_permitidas = ['jpg', 'jpeg', 'png', 'gif'];
        $tamanho_maximo = 2 * 1024 * 1024; // 2MB

        if (!in_array($arquivo_extensao, $extensoes_permitidas)) {
            $erros[] = 'A imagem deve ser do tipo JPG, JPEG, PNG ou GIF.';
        } elseif ($arquivo_tamanho > $tamanho_maximo) {
            $erros[] = 'A imagem deve ter no máximo 2MB.';
        } else {
            // Cria o diretório de upload se não existir
            $diretorio_upload = '../uploads/ava/cursos/';
            if (!file_exists($diretorio_upload)) {
                mkdir($diretorio_upload, 0755, true);
            }

            // Gera um nome único para o arquivo
            $arquivo_nome_unico = uniqid() . '.' . $arquivo_extensao;
            $arquivo_caminho = $diretorio_upload . $arquivo_nome_unico;

            // Faz o upload do arquivo
            if (move_uploaded_file($arquivo_tmp, $arquivo_caminho)) {
                // Remove a imagem antiga se existir
                if (!empty($curso['imagem']) && file_exists('..' . $curso['imagem'])) {
                    unlink('..' . $curso['imagem']);
                }

                $imagem = '/uploads/ava/cursos/' . $arquivo_nome_unico;
            } else {
                $erros[] = 'Erro ao fazer upload da imagem.';
            }
        }
    }

    // Se não houver erros, atualiza o curso
    if (empty($erros)) {
        try {
            // Prepara os dados para atualização
            $dados = [
                'titulo' => $titulo,
                'descricao' => $descricao,
                'categoria' => $categoria,
                'carga_horaria' => $carga_horaria,
                'status' => $status,
                'imagem' => $imagem,
                'preco' => $preco ? str_replace(',', '.', $preco) : null,
                'preco_promocional' => $preco_promocional ? str_replace(',', '.', $preco_promocional) : null,
                'data_inicio' => $data_inicio ? date('Y-m-d', strtotime($data_inicio)) : null,
                'data_fim' => $data_fim ? date('Y-m-d', strtotime($data_fim)) : null,
                'requisitos' => $requisitos,
                'publico_alvo' => $publico_alvo,
                'objetivos' => $objetivos,
                'metodologia' => $metodologia,
                'avaliacao' => $avaliacao,
                'certificacao' => $certificacao,
                'destaque' => $destaque,
                'visibilidade' => $visibilidade,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            // Atualiza o curso no banco de dados
            $db->update('ava_cursos', $dados, "id = ?", [$curso_id]);

            setMensagem('sucesso', 'Curso atualizado com sucesso!');
            redirect("curso_visualizar.php?id=$curso_id");
            exit;
        } catch (Exception $e) {
            $erros[] = 'Erro ao atualizar o curso: ' . $e->getMessage();
        }
    }

    if (!empty($erros)) {
        setMensagem('erro', implode('<br>', $erros));
    }
}

// Define o título da página
$titulo_pagina = 'Editar Curso';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faciência ERP - <?php echo $titulo_pagina; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .form-section {
            margin-bottom: 2rem;
        }
        .form-section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #4B5563;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #E5E7EB;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: #4B5563;
            margin-bottom: 0.5rem;
        }
        .form-input {
            width: 100%;
            padding: 0.5rem 0.75rem;
            border: 1px solid #D1D5DB;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            color: #1F2937;
        }
        .form-input:focus {
            outline: none;
            border-color: #6A5ACD;
            box-shadow: 0 0 0 3px rgba(106, 90, 205, 0.1);
        }
        .form-textarea {
            width: 100%;
            padding: 0.5rem 0.75rem;
            border: 1px solid #D1D5DB;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            color: #1F2937;
            min-height: 100px;
            resize: vertical;
        }
        .form-textarea:focus {
            outline: none;
            border-color: #6A5ACD;
            box-shadow: 0 0 0 3px rgba(106, 90, 205, 0.1);
        }
        .form-select {
            width: 100%;
            padding: 0.5rem 0.75rem;
            border: 1px solid #D1D5DB;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            color: #1F2937;
            background-color: white;
        }
        .form-select:focus {
            outline: none;
            border-color: #6A5ACD;
            box-shadow: 0 0 0 3px rgba(106, 90, 205, 0.1);
        }
        .form-checkbox {
            margin-right: 0.5rem;
        }
        .form-checkbox-label {
            font-size: 0.875rem;
            color: #4B5563;
        }
        .form-help-text {
            font-size: 0.75rem;
            color: #6B7280;
            margin-top: 0.25rem;
        }
        .form-error {
            color: #DC2626;
            font-size: 0.75rem;
            margin-top: 0.25rem;
        }
        .btn-primary {
            background-color: #6A5ACD;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 500;
            font-size: 0.875rem;
            border: none;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .btn-primary:hover {
            background-color: #5D4FB8;
        }
        .btn-secondary {
            background-color: white;
            color: #4B5563;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 500;
            font-size: 0.875rem;
            border: 1px solid #D1D5DB;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .btn-secondary:hover {
            background-color: #F3F4F6;
        }
        .current-image {
            max-width: 100%;
            max-height: 200px;
            border-radius: 0.375rem;
            margin-top: 0.5rem;
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
                        <div>
                            <h1 class="text-2xl font-bold text-gray-800"><?php echo $titulo_pagina; ?></h1>
                            <p class="text-gray-600">Editar curso no Ambiente Virtual de Aprendizagem</p>
                        </div>
                        <div class="flex space-x-2">
                            <a href="curso_visualizar.php?id=<?php echo $curso_id; ?>" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                <i class="fas fa-eye mr-2"></i> Visualizar
                            </a>
                            <a href="cursos.php" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                <i class="fas fa-arrow-left mr-2"></i> Voltar
                            </a>
                        </div>
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

                    <!-- Formulário de Edição de Curso -->
                    <form action="curso_editar.php?id=<?php echo $curso_id; ?>" method="post" enctype="multipart/form-data" class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <div class="p-6">
                            <!-- Informações Básicas -->
                            <div class="form-section">
                                <h2 class="form-section-title">Informações Básicas</h2>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="form-group md:col-span-2">
                                        <label for="titulo" class="form-label">Título do Curso <span class="text-red-500">*</span></label>
                                        <input type="text" id="titulo" name="titulo" value="<?php echo htmlspecialchars($curso['titulo']); ?>" class="form-input" required>
                                    </div>
                                    <div class="form-group md:col-span-2">
                                        <label for="descricao" class="form-label">Descrição <span class="text-red-500">*</span></label>
                                        <textarea id="descricao" name="descricao" class="form-textarea" required><?php echo htmlspecialchars($curso['descricao']); ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label for="categoria" class="form-label">Categoria <span class="text-red-500">*</span></label>
                                        <select id="categoria" name="categoria" class="form-select" required>
                                            <option value="">Selecione uma categoria</option>
                                            <?php foreach ($categorias as $categoria): ?>
                                            <option value="<?php echo htmlspecialchars($categoria['nome']); ?>" <?php echo $curso['categoria'] === $categoria['nome'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($categoria['nome']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="carga_horaria" class="form-label">Carga Horária (horas) <span class="text-red-500">*</span></label>
                                        <input type="number" id="carga_horaria" name="carga_horaria" value="<?php echo htmlspecialchars($curso['carga_horaria']); ?>" min="1" class="form-input" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="status" class="form-label">Status <span class="text-red-500">*</span></label>
                                        <select id="status" name="status" class="form-select" required>
                                            <option value="rascunho" <?php echo $curso['status'] === 'rascunho' ? 'selected' : ''; ?>>Rascunho</option>
                                            <option value="revisao" <?php echo $curso['status'] === 'revisao' ? 'selected' : ''; ?>>Em Revisão</option>
                                            <option value="publicado" <?php echo $curso['status'] === 'publicado' ? 'selected' : ''; ?>>Publicado</option>
                                            <option value="arquivado" <?php echo $curso['status'] === 'arquivado' ? 'selected' : ''; ?>>Arquivado</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="imagem" class="form-label">Imagem do Curso</label>
                                        <input type="file" id="imagem" name="imagem" class="form-input" accept="image/jpeg,image/png,image/gif">
                                        <p class="form-help-text">Formatos aceitos: JPG, PNG, GIF. Tamanho máximo: 2MB.</p>
                                        <?php if (!empty($curso['imagem'])): ?>
                                        <div class="mt-2">
                                            <p class="text-sm text-gray-600">Imagem atual:</p>
                                            <img src="<?php echo htmlspecialchars($curso['imagem']); ?>" alt="Imagem do curso" class="current-image">
                                            <p class="text-xs text-gray-500 mt-1">Faça upload de uma nova imagem apenas se desejar substituir a atual.</p>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Informações de Preço e Datas -->
                            <div class="form-section">
                                <h2 class="form-section-title">Preço e Datas</h2>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="form-group">
                                        <label for="preco" class="form-label">Preço (R$)</label>
                                        <input type="text" id="preco" name="preco" value="<?php echo htmlspecialchars($curso['preco']); ?>" class="form-input" placeholder="0,00">
                                    </div>
                                    <div class="form-group">
                                        <label for="preco_promocional" class="form-label">Preço Promocional (R$)</label>
                                        <input type="text" id="preco_promocional" name="preco_promocional" value="<?php echo htmlspecialchars($curso['preco_promocional']); ?>" class="form-input" placeholder="0,00">
                                    </div>
                                    <div class="form-group">
                                        <label for="data_inicio" class="form-label">Data de Início</label>
                                        <input type="date" id="data_inicio" name="data_inicio" value="<?php echo !empty($curso['data_inicio']) ? date('Y-m-d', strtotime($curso['data_inicio'])) : ''; ?>" class="form-input">
                                    </div>
                                    <div class="form-group">
                                        <label for="data_fim" class="form-label">Data de Término</label>
                                        <input type="date" id="data_fim" name="data_fim" value="<?php echo !empty($curso['data_fim']) ? date('Y-m-d', strtotime($curso['data_fim'])) : ''; ?>" class="form-input">
                                    </div>
                                </div>
                            </div>

                            <!-- Detalhes do Curso -->
                            <div class="form-section">
                                <h2 class="form-section-title">Detalhes do Curso</h2>
                                <div class="grid grid-cols-1 gap-4">
                                    <div class="form-group">
                                        <label for="requisitos" class="form-label">Requisitos</label>
                                        <textarea id="requisitos" name="requisitos" class="form-textarea"><?php echo htmlspecialchars($curso['requisitos']); ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label for="publico_alvo" class="form-label">Público-Alvo</label>
                                        <textarea id="publico_alvo" name="publico_alvo" class="form-textarea"><?php echo htmlspecialchars($curso['publico_alvo']); ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label for="objetivos" class="form-label">Objetivos</label>
                                        <textarea id="objetivos" name="objetivos" class="form-textarea"><?php echo htmlspecialchars($curso['objetivos']); ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label for="metodologia" class="form-label">Metodologia</label>
                                        <textarea id="metodologia" name="metodologia" class="form-textarea"><?php echo htmlspecialchars($curso['metodologia']); ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label for="avaliacao" class="form-label">Avaliação</label>
                                        <textarea id="avaliacao" name="avaliacao" class="form-textarea"><?php echo htmlspecialchars($curso['avaliacao']); ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label for="certificacao" class="form-label">Certificação</label>
                                        <textarea id="certificacao" name="certificacao" class="form-textarea"><?php echo htmlspecialchars($curso['certificacao']); ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Configurações Adicionais -->
                            <div class="form-section">
                                <h2 class="form-section-title">Configurações Adicionais</h2>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="form-group">
                                        <label for="visibilidade" class="form-label">Visibilidade</label>
                                        <select id="visibilidade" name="visibilidade" class="form-select">
                                            <option value="publico" <?php echo $curso['visibilidade'] === 'publico' ? 'selected' : ''; ?>>Público</option>
                                            <option value="privado" <?php echo $curso['visibilidade'] === 'privado' ? 'selected' : ''; ?>>Privado</option>
                                        </select>
                                    </div>
                                    <div class="form-group flex items-center">
                                        <input type="checkbox" id="destaque" name="destaque" class="form-checkbox" <?php echo $curso['destaque'] ? 'checked' : ''; ?>>
                                        <label for="destaque" class="form-checkbox-label ml-2">Destacar na página inicial</label>
                                    </div>
                                </div>
                            </div>

                            <!-- Botões de Ação -->
                            <div class="flex justify-end space-x-2 mt-6">
                                <a href="curso_visualizar.php?id=<?php echo $curso_id; ?>" class="btn-secondary">Cancelar</a>
                                <button type="submit" class="btn-primary">Salvar Alterações</button>
                            </div>
                        </div>
                    </form>
                </div>
            </main>

            <!-- Footer -->
            <?php include 'includes/footer.php'; ?>
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
