<?php
/**
 * Gerenciamento de Aulas de Módulo do AVA
 * Permite gerenciar as aulas de um módulo específico do Ambiente Virtual de Aprendizagem
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

// Verifica se o ID do módulo foi informado
if (!isset($_GET['modulo_id']) || empty($_GET['modulo_id'])) {
    setMensagem('erro', 'Módulo não informado.');
    redirect('cursos.php');
    exit;
}

$modulo_id = (int)$_GET['modulo_id'];

// Busca o módulo
$sql = "SELECT m.*, c.id as curso_id, c.titulo as curso_titulo, c.polo_id
        FROM ava_modulos m
        JOIN ava_cursos c ON m.curso_id = c.id
        WHERE m.id = ?";
$modulo = $db->fetchOne($sql, [$modulo_id]);

if (!$modulo) {
    setMensagem('erro', 'Módulo não encontrado.');
    redirect('cursos.php');
    exit;
}

// Verifica se o módulo pertence a um curso do polo
if ($modulo['polo_id'] != $polo_id) {
    setMensagem('erro', 'Você não tem permissão para acessar este módulo.');
    redirect('cursos.php');
    exit;
}

// Verifica se a tabela ava_aulas existe
$sql_check = "SHOW TABLES LIKE 'ava_aulas'";
$tabela_aulas_existe = $db->fetchOne($sql_check);

if (!$tabela_aulas_existe) {
    // Cria a tabela ava_aulas
    $sql = "CREATE TABLE IF NOT EXISTS ava_aulas (
        id INT(11) NOT NULL AUTO_INCREMENT,
        modulo_id INT(11) NOT NULL,
        titulo VARCHAR(255) NOT NULL,
        descricao TEXT NULL,
        tipo ENUM('video', 'texto', 'arquivo', 'quiz') NOT NULL DEFAULT 'texto',
        conteudo TEXT NULL,
        url_video VARCHAR(255) NULL,
        arquivo VARCHAR(255) NULL,
        duracao INT(11) NULL,
        ordem INT(11) NOT NULL DEFAULT 0,
        status ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY idx_modulo_id (modulo_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    try {
        $db->query($sql);
    } catch (Exception $e) {
        setMensagem('erro', 'Erro ao criar tabela de aulas: ' . $e->getMessage());
        redirect("modulos.php?curso_id={$modulo['curso_id']}");
        exit;
    }
} else {
    // Verifica se as colunas necessárias existem
    $colunas_necessarias = [
        'url_video' => "ALTER TABLE ava_aulas ADD COLUMN url_video VARCHAR(255) NULL AFTER conteudo",
        'arquivo' => "ALTER TABLE ava_aulas ADD COLUMN arquivo VARCHAR(255) NULL AFTER url_video",
        'duracao' => "ALTER TABLE ava_aulas ADD COLUMN duracao INT(11) NULL AFTER arquivo"
    ];

    foreach ($colunas_necessarias as $coluna => $sql_add) {
        $sql_check = "SHOW COLUMNS FROM ava_aulas LIKE '$coluna'";
        $coluna_existe = $db->fetchOne($sql_check);

        if (!$coluna_existe) {
            try {
                $db->query($sql_add);
                // Não exibimos mensagem para não confundir o usuário
            } catch (Exception $e) {
                // Apenas registramos o erro, mas continuamos
                error_log("Erro ao adicionar coluna $coluna: " . $e->getMessage());
            }
        }
    }
}

// Busca as aulas do módulo
$sql = "SELECT * FROM ava_aulas WHERE modulo_id = ? ORDER BY ordem, id";
$aulas = $db->fetchAll($sql, [$modulo_id]);

// Processa o formulário de adição de aula
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['adicionar_aula'])) {
        $titulo = $_POST['titulo'] ?? '';
        $descricao = $_POST['descricao'] ?? '';
        $tipo = $_POST['tipo'] ?? 'texto';
        $conteudo = $_POST['conteudo'] ?? '';
        $url_video = $_POST['url_video'] ?? '';
        $duracao = (int)($_POST['duracao'] ?? 0);
        $ordem = (int)($_POST['ordem'] ?? 0);
        $status = $_POST['status'] ?? 'ativo';

        // Validação
        $erros = [];

        if (empty($titulo)) {
            $erros[] = 'O título da aula é obrigatório.';
        }

        // Validação específica por tipo
        if ($tipo === 'video' && empty($url_video)) {
            $erros[] = 'A URL do vídeo é obrigatória para aulas do tipo vídeo.';
        } elseif ($tipo === 'texto' && empty($conteudo)) {
            $erros[] = 'O conteúdo é obrigatório para aulas do tipo texto.';
        } elseif ($tipo === 'quiz' && empty($conteudo)) {
            $erros[] = 'O conteúdo é obrigatório para aulas do tipo quiz.';
        }

        // Validação adicional para o quiz
        if ($tipo === 'quiz' && !empty($conteudo)) {
            $linhas = explode("\n", $conteudo);
            $tem_pergunta = false;
            $tem_alternativa_correta = false;

            foreach ($linhas as $linha) {
                $linha = trim($linha);
                if ($linha !== '' && !str_starts_with($linha, '*')) {
                    $tem_pergunta = true;
                }
                if (str_starts_with($linha, '**')) {
                    $tem_alternativa_correta = true;
                }
            }

            if (!$tem_pergunta) {
                $erros[] = 'O quiz deve conter pelo menos uma pergunta. Digite a pergunta em uma linha sem asteriscos.';
            }

            if (!$tem_alternativa_correta) {
                $erros[] = 'O quiz deve conter pelo menos uma alternativa correta marcada com **.';
            }
        }

        // Garante que os campos não utilizados pelo tipo selecionado sejam nulos
        if ($tipo !== 'video') {
            $url_video = null;
        }
        if ($tipo !== 'texto' && $tipo !== 'quiz') {
            $conteudo = null;
        }
        if ($tipo !== 'arquivo') {
            $arquivo = null;
        }

        // Processa o upload de arquivo
        $arquivo = null; // Inicializa como null em vez de string vazia
        if ($tipo === 'arquivo' && !empty($_FILES['arquivo']['name'])) {
            $arquivo_tmp = $_FILES['arquivo']['tmp_name'];
            $arquivo_nome = $_FILES['arquivo']['name'];
            $arquivo_extensao = strtolower(pathinfo($arquivo_nome, PATHINFO_EXTENSION));
            $arquivo_tamanho = $_FILES['arquivo']['size'];

            // Validação do arquivo
            $extensoes_permitidas = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'zip', 'rar', 'txt'];
            $tamanho_maximo = 10 * 1024 * 1024; // 10MB

            if (!in_array($arquivo_extensao, $extensoes_permitidas)) {
                $erros[] = 'Extensão de arquivo não permitida. Extensões permitidas: ' . implode(', ', $extensoes_permitidas);
            } elseif ($arquivo_tamanho > $tamanho_maximo) {
                $erros[] = 'O arquivo deve ter no máximo 10MB.';
            } else {
                // Cria o diretório de upload se não existir
                $diretorio_upload = '../uploads/ava/aulas/';
                if (!file_exists($diretorio_upload)) {
                    mkdir($diretorio_upload, 0755, true);
                }

                // Gera um nome único para o arquivo
                $arquivo_nome_unico = uniqid() . '.' . $arquivo_extensao;
                $arquivo_caminho = $diretorio_upload . $arquivo_nome_unico;

                // Faz o upload do arquivo
                if (move_uploaded_file($arquivo_tmp, $arquivo_caminho)) {
                    $arquivo = '/uploads/ava/aulas/' . $arquivo_nome_unico;
                } else {
                    $erros[] = 'Erro ao fazer upload do arquivo.';
                }
            }
        } elseif ($tipo === 'arquivo' && empty($_FILES['arquivo']['name'])) {
            $erros[] = 'O arquivo é obrigatório para aulas do tipo arquivo.';
        }

        if (empty($erros)) {
            try {
                $dados = [
                    'modulo_id' => $modulo_id,
                    'titulo' => $titulo,
                    'descricao' => $descricao,
                    'tipo' => $tipo,
                    'conteudo' => $conteudo,
                    'url_video' => $url_video,
                    'arquivo' => $arquivo,
                    'duracao' => $duracao,
                    'ordem' => $ordem,
                    'status' => $status,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                $db->insert('ava_aulas', $dados);

                setMensagem('sucesso', 'Aula adicionada com sucesso!');
                redirect("aulas.php?modulo_id=$modulo_id");
                exit;
            } catch (Exception $e) {
                $erros[] = 'Erro ao adicionar aula: ' . $e->getMessage();
            }
        }

        if (!empty($erros)) {
            setMensagem('erro', implode('<br>', $erros));
        }
    } elseif (isset($_POST['editar_aula'])) {
        $aula_id = (int)$_POST['aula_id'];
        $titulo = $_POST['titulo'] ?? '';
        $descricao = $_POST['descricao'] ?? '';
        $tipo = $_POST['tipo'] ?? 'texto';
        $conteudo = $_POST['conteudo'] ?? '';
        $url_video = $_POST['url_video'] ?? '';
        $duracao = (int)($_POST['duracao'] ?? 0);
        $ordem = (int)($_POST['ordem'] ?? 0);
        $status = $_POST['status'] ?? 'ativo';

        // Validação
        $erros = [];

        if (empty($titulo)) {
            $erros[] = 'O título da aula é obrigatório.';
        }

        // Verifica se a aula existe e pertence ao módulo
        $sql = "SELECT * FROM ava_aulas WHERE id = ? AND modulo_id = ?";
        $aula = $db->fetchOne($sql, [$aula_id, $modulo_id]);

        if (!$aula) {
            $erros[] = 'Aula não encontrada ou você não tem permissão para editá-la.';
        } else {
            // Validação específica por tipo
            if ($tipo === 'video' && empty($url_video)) {
                $erros[] = 'A URL do vídeo é obrigatória para aulas do tipo vídeo.';
            } elseif ($tipo === 'texto' && empty($conteudo)) {
                $erros[] = 'O conteúdo é obrigatório para aulas do tipo texto.';
            } elseif ($tipo === 'quiz' && empty($conteudo)) {
                $erros[] = 'O conteúdo é obrigatório para aulas do tipo quiz.';
            }

            // Validação adicional para o quiz
            if ($tipo === 'quiz' && !empty($conteudo)) {
                $linhas = explode("\n", $conteudo);
                $tem_pergunta = false;
                $tem_alternativa_correta = false;

                foreach ($linhas as $linha) {
                    $linha = trim($linha);
                    if ($linha !== '' && !str_starts_with($linha, '*')) {
                        $tem_pergunta = true;
                    }
                    if (str_starts_with($linha, '**')) {
                        $tem_alternativa_correta = true;
                    }
                }

                if (!$tem_pergunta) {
                    $erros[] = 'O quiz deve conter pelo menos uma pergunta. Digite a pergunta em uma linha sem asteriscos.';
                }

                if (!$tem_alternativa_correta) {
                    $erros[] = 'O quiz deve conter pelo menos uma alternativa correta marcada com **.';
                }
            }

            // Garante que os campos não utilizados pelo tipo selecionado sejam nulos
            if ($tipo !== 'video') {
                $url_video = null;
            }
            if ($tipo !== 'texto' && $tipo !== 'quiz') {
                $conteudo = null;
            }
            if ($tipo !== 'arquivo') {
                $arquivo = null; // Define como null se não for do tipo arquivo
            } else {
                $arquivo = $aula['arquivo']; // Mantém o arquivo atual se for do tipo arquivo
            }

            // Processa o upload de arquivo se for do tipo arquivo
            if ($tipo === 'arquivo' && !empty($_FILES['arquivo']['name'])) {
                $arquivo_tmp = $_FILES['arquivo']['tmp_name'];
                $arquivo_nome = $_FILES['arquivo']['name'];
                $arquivo_extensao = strtolower(pathinfo($arquivo_nome, PATHINFO_EXTENSION));
                $arquivo_tamanho = $_FILES['arquivo']['size'];

                // Validação do arquivo
                $extensoes_permitidas = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'zip', 'rar', 'txt'];
                $tamanho_maximo = 10 * 1024 * 1024; // 10MB

                if (!in_array($arquivo_extensao, $extensoes_permitidas)) {
                    $erros[] = 'Extensão de arquivo não permitida. Extensões permitidas: ' . implode(', ', $extensoes_permitidas);
                } elseif ($arquivo_tamanho > $tamanho_maximo) {
                    $erros[] = 'O arquivo deve ter no máximo 10MB.';
                } else {
                    // Cria o diretório de upload se não existir
                    $diretorio_upload = '../uploads/ava/aulas/';
                    if (!file_exists($diretorio_upload)) {
                        mkdir($diretorio_upload, 0755, true);
                    }

                    // Gera um nome único para o arquivo
                    $arquivo_nome_unico = uniqid() . '.' . $arquivo_extensao;
                    $arquivo_caminho = $diretorio_upload . $arquivo_nome_unico;

                    // Faz o upload do arquivo
                    if (move_uploaded_file($arquivo_tmp, $arquivo_caminho)) {
                        // Remove o arquivo antigo se existir
                        if (!empty($aula['arquivo']) && file_exists('..' . $aula['arquivo'])) {
                            unlink('..' . $aula['arquivo']);
                        }

                        $arquivo = '/uploads/ava/aulas/' . $arquivo_nome_unico;
                    } else {
                        $erros[] = 'Erro ao fazer upload do arquivo.';
                    }
                }
            } elseif ($tipo === 'arquivo' && empty($aula['arquivo']) && empty($_FILES['arquivo']['name'])) {
                $erros[] = 'O arquivo é obrigatório para aulas do tipo arquivo.';
            }
        }

        if (empty($erros)) {
            try {
                $dados = [
                    'titulo' => $titulo,
                    'descricao' => $descricao,
                    'tipo' => $tipo,
                    'conteudo' => $conteudo,
                    'url_video' => $url_video,
                    'arquivo' => $arquivo,
                    'duracao' => $duracao,
                    'ordem' => $ordem,
                    'status' => $status,
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                $db->update('ava_aulas', $dados, "id = ?", [$aula_id]);

                setMensagem('sucesso', 'Aula atualizada com sucesso!');
                redirect("aulas.php?modulo_id=$modulo_id");
                exit;
            } catch (Exception $e) {
                $erros[] = 'Erro ao atualizar aula: ' . $e->getMessage();
            }
        }

        if (!empty($erros)) {
            setMensagem('erro', implode('<br>', $erros));
        }
    } elseif (isset($_POST['excluir_aula'])) {
        $aula_id = (int)$_POST['aula_id'];

        // Verifica se a aula existe e pertence ao módulo
        $sql = "SELECT * FROM ava_aulas WHERE id = ? AND modulo_id = ?";
        $aula = $db->fetchOne($sql, [$aula_id, $modulo_id]);

        if (!$aula) {
            setMensagem('erro', 'Aula não encontrada ou você não tem permissão para excluí-la.');
            redirect("aulas.php?modulo_id=$modulo_id");
            exit;
        }

        try {
            // Remove o arquivo se existir
            if (!empty($aula['arquivo']) && file_exists('..' . $aula['arquivo'])) {
                unlink('..' . $aula['arquivo']);
            }

            $db->delete('ava_aulas', "id = ?", [$aula_id]);

            setMensagem('sucesso', 'Aula excluída com sucesso!');
            redirect("aulas.php?modulo_id=$modulo_id");
            exit;
        } catch (Exception $e) {
            setMensagem('erro', 'Erro ao excluir aula: ' . $e->getMessage());
            redirect("aulas.php?modulo_id=$modulo_id");
            exit;
        }
    }
}

// Define o título da página
$titulo_pagina = 'Gerenciar Aulas';

// Aula para edição (se houver)
$aula_edicao = null;
if (isset($_GET['editar']) && !empty($_GET['editar'])) {
    $aula_id = (int)$_GET['editar'];

    $sql = "SELECT * FROM ava_aulas WHERE id = ? AND modulo_id = ?";
    $aula_edicao = $db->fetchOne($sql, [$aula_id, $modulo_id]);

    if (!$aula_edicao) {
        setMensagem('erro', 'Aula não encontrada ou você não tem permissão para editá-la.');
        redirect("aulas.php?modulo_id=$modulo_id");
        exit;
    }
}
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
        .aula-card {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 4px solid #6A5ACD;
        }
        .aula-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        .aula-title {
            font-weight: 600;
            color: #111827;
            font-size: 1.1rem;
        }
        .aula-status {
            font-size: 0.75rem;
            padding: 0.125rem 0.5rem;
            border-radius: 9999px;
        }
        .aula-status-ativo { background-color: #D1FAE5; color: #059669; }
        .aula-status-inativo { background-color: #FEE2E2; color: #DC2626; }
        .aula-description {
            color: #6B7280;
            font-size: 0.875rem;
            margin-bottom: 0.75rem;
        }
        .aula-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.75rem;
            color: #6B7280;
        }
        .aula-type {
            display: inline-flex;
            align-items: center;
            background-color: #F3F4F6;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            margin-right: 0.5rem;
        }
        .aula-type i {
            margin-right: 0.25rem;
        }
        .aula-order {
            background-color: #F3F4F6;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            margin-right: 0.5rem;
        }
        .aula-duration {
            display: inline-flex;
            align-items: center;
        }
        .aula-duration i {
            margin-right: 0.25rem;
        }
        .aula-actions {
            display: flex;
            gap: 0.5rem;
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

        .tipo-fields {
            display: none;
        }
        .tipo-fields.active {
            display: block;
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
                            <p class="text-gray-600">
                                Gerenciar aulas do módulo: <strong><?php echo htmlspecialchars($modulo['titulo']); ?></strong>
                                <span class="text-gray-500">|</span>
                                Curso: <a href="curso_visualizar.php?id=<?php echo $modulo['curso_id']; ?>" class="text-indigo-600 hover:text-indigo-800"><?php echo htmlspecialchars($modulo['curso_titulo']); ?></a>
                            </p>
                        </div>
                        <div class="flex space-x-2">
                            <a href="modulos.php?curso_id=<?php echo $modulo['curso_id']; ?>" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
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

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Coluna da Esquerda - Lista de Aulas -->
                        <div class="lg:col-span-2">
                            <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
                                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                                    <h2 class="text-lg font-semibold text-gray-800">Aulas do Módulo (<?php echo count($aulas); ?>)</h2>
                                    <?php if (!$aula_edicao): ?>
                                    <a href="#form-aula" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                                        <i class="fas fa-plus mr-2"></i> Nova Aula
                                    </a>
                                    <?php endif; ?>
                                </div>
                                <div class="p-6">
                                    <?php if (empty($aulas)): ?>
                                    <div class="text-center text-gray-500 py-4">
                                        <p>Nenhuma aula cadastrada para este módulo.</p>
                                        <a href="#form-aula" class="inline-flex items-center mt-4 px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                                            <i class="fas fa-plus mr-2"></i> Adicionar Aula
                                        </a>
                                    </div>
                                    <?php else: ?>
                                    <div class="space-y-4">
                                        <?php foreach ($aulas as $aula): ?>
                                        <div class="aula-card">
                                            <div class="aula-header">
                                                <h3 class="aula-title"><?php echo htmlspecialchars($aula['titulo']); ?></h3>
                                                <span class="aula-status aula-status-<?php echo $aula['status']; ?>">
                                                    <?php echo $aula['status'] === 'ativo' ? 'Ativo' : 'Inativo'; ?>
                                                </span>
                                            </div>
                                            <div class="aula-description">
                                                <?php echo !empty($aula['descricao']) ? nl2br(htmlspecialchars($aula['descricao'])) : '<em class="text-gray-400">Sem descrição</em>'; ?>
                                            </div>
                                            <div class="aula-meta">
                                                <div>
                                                    <span class="aula-type">
                                                        <?php if ($aula['tipo'] === 'video'): ?>
                                                        <i class="fas fa-video"></i> Vídeo
                                                        <?php elseif ($aula['tipo'] === 'texto'): ?>
                                                        <i class="fas fa-file-alt"></i> Texto
                                                        <?php elseif ($aula['tipo'] === 'arquivo'): ?>
                                                        <i class="fas fa-file"></i> Arquivo
                                                        <?php elseif ($aula['tipo'] === 'quiz'): ?>
                                                        <i class="fas fa-question-circle"></i> Quiz
                                                        <?php endif; ?>
                                                    </span>
                                                    <span class="aula-order">Ordem: <?php echo $aula['ordem']; ?></span>
                                                    <?php if ($aula['duracao']): ?>
                                                    <span class="aula-duration">
                                                        <i class="fas fa-clock"></i> <?php echo $aula['duracao']; ?> min
                                                    </span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="aula-actions">
                                                    <?php if ($aula['tipo'] === 'arquivo' && !empty($aula['arquivo'])): ?>
                                                    <a href="<?php echo $aula['arquivo']; ?>" target="_blank" class="inline-flex items-center px-2 py-1 bg-blue-100 text-blue-800 text-xs font-medium rounded hover:bg-blue-200">
                                                        <i class="fas fa-download mr-1"></i> Download
                                                    </a>
                                                    <?php endif; ?>
                                                    <a href="aula_visualizar.php?id=<?php echo $aula['id']; ?>" class="inline-flex items-center px-2 py-1 bg-green-100 text-green-800 text-xs font-medium rounded hover:bg-green-200">
                                                        <i class="fas fa-eye mr-1"></i> Visualizar
                                                    </a>
                                                    <a href="aulas.php?modulo_id=<?php echo $modulo_id; ?>&editar=<?php echo $aula['id']; ?>#form-aula" class="inline-flex items-center px-2 py-1 bg-indigo-100 text-indigo-800 text-xs font-medium rounded hover:bg-indigo-200">
                                                        <i class="fas fa-edit mr-1"></i> Editar
                                                    </a>
                                                    <form method="post" class="inline" onsubmit="return confirm('Tem certeza que deseja excluir esta aula?');">
                                                        <input type="hidden" name="aula_id" value="<?php echo $aula['id']; ?>">
                                                        <button type="submit" name="excluir_aula" class="inline-flex items-center px-2 py-1 bg-red-100 text-red-800 text-xs font-medium rounded hover:bg-red-200">
                                                            <i class="fas fa-trash mr-1"></i> Excluir
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <!-- Coluna da Direita - Formulário de Aula -->
                        <div>
                            <div id="form-aula" class="bg-white rounded-xl shadow-sm overflow-hidden">
                                <div class="px-6 py-4 border-b border-gray-200">
                                    <h2 class="text-lg font-semibold text-gray-800">
                                        <?php echo $aula_edicao ? 'Editar Aula' : 'Nova Aula'; ?>
                                    </h2>
                                </div>
                                <div class="p-6">
                                    <form method="post" action="aulas.php?modulo_id=<?php echo $modulo_id; ?>" enctype="multipart/form-data">
                                        <?php if ($aula_edicao): ?>
                                        <input type="hidden" name="aula_id" value="<?php echo $aula_edicao['id']; ?>">
                                        <?php endif; ?>

                                        <div class="form-group">
                                            <label for="titulo" class="form-label">Título <span class="text-red-500">*</span></label>
                                            <input type="text" id="titulo" name="titulo" class="form-input" value="<?php echo $aula_edicao ? htmlspecialchars($aula_edicao['titulo']) : ''; ?>" required>
                                        </div>

                                        <div class="form-group">
                                            <label for="descricao" class="form-label">Descrição</label>
                                            <textarea id="descricao" name="descricao" class="form-textarea"><?php echo $aula_edicao ? htmlspecialchars($aula_edicao['descricao']) : ''; ?></textarea>
                                        </div>

                                        <div class="grid grid-cols-2 gap-4">
                                            <div class="form-group">
                                                <label for="tipo" class="form-label">Tipo de Aula <span class="text-red-500">*</span></label>
                                                <select id="tipo" name="tipo" class="form-select" required>
                                                    <option value="texto" <?php echo $aula_edicao && $aula_edicao['tipo'] === 'texto' ? 'selected' : ''; ?>>Texto</option>
                                                    <option value="video" <?php echo $aula_edicao && $aula_edicao['tipo'] === 'video' ? 'selected' : ''; ?>>Vídeo</option>
                                                    <option value="arquivo" <?php echo $aula_edicao && $aula_edicao['tipo'] === 'arquivo' ? 'selected' : ''; ?>>Arquivo</option>
                                                    <option value="quiz" <?php echo $aula_edicao && $aula_edicao['tipo'] === 'quiz' ? 'selected' : ''; ?>>Quiz</option>
                                                </select>
                                            </div>

                                            <div class="form-group">
                                                <label for="ordem" class="form-label">Ordem</label>
                                                <input type="number" id="ordem" name="ordem" class="form-input" value="<?php echo $aula_edicao ? $aula_edicao['ordem'] : count($aulas); ?>" min="0">
                                            </div>
                                        </div>

                                        <!-- Campos específicos por tipo -->
                                        <div id="tipo-texto" class="tipo-fields <?php echo (!$aula_edicao || $aula_edicao['tipo'] === 'texto') ? 'active' : ''; ?>">
                                            <div class="form-group">
                                                <label for="conteudo" class="form-label">Conteúdo <span class="text-red-500">*</span></label>
                                                <div class="border border-gray-300 rounded-md p-2 bg-white">
                                                    <div class="mb-2 flex flex-wrap gap-2 border-b border-gray-200 pb-2">
                                                        <button type="button" onclick="formatText('bold')" class="px-2 py-1 bg-gray-100 rounded hover:bg-gray-200" title="Negrito"><i class="fas fa-bold"></i></button>
                                                        <button type="button" onclick="formatText('italic')" class="px-2 py-1 bg-gray-100 rounded hover:bg-gray-200" title="Itálico"><i class="fas fa-italic"></i></button>
                                                        <button type="button" onclick="formatText('underline')" class="px-2 py-1 bg-gray-100 rounded hover:bg-gray-200" title="Sublinhado"><i class="fas fa-underline"></i></button>
                                                        <button type="button" onclick="formatText('h2')" class="px-2 py-1 bg-gray-100 rounded hover:bg-gray-200" title="Título"><i class="fas fa-heading"></i></button>
                                                        <button type="button" onclick="formatText('ul')" class="px-2 py-1 bg-gray-100 rounded hover:bg-gray-200" title="Lista não ordenada"><i class="fas fa-list-ul"></i></button>
                                                        <button type="button" onclick="formatText('ol')" class="px-2 py-1 bg-gray-100 rounded hover:bg-gray-200" title="Lista ordenada"><i class="fas fa-list-ol"></i></button>
                                                        <button type="button" onclick="formatText('link')" class="px-2 py-1 bg-gray-100 rounded hover:bg-gray-200" title="Link"><i class="fas fa-link"></i></button>
                                                        <button type="button" onclick="formatText('image')" class="px-2 py-1 bg-gray-100 rounded hover:bg-gray-200" title="Imagem"><i class="fas fa-image"></i></button>
                                                    </div>
                                                    <textarea id="conteudo" name="conteudo" class="form-textarea border-0 focus:ring-0 w-full min-h-[300px]" rows="15"><?php echo $aula_edicao && $aula_edicao['tipo'] === 'texto' ? htmlspecialchars($aula_edicao['conteudo']) : ''; ?></textarea>
                                                </div>
                                                <p class="text-xs text-gray-500 mt-1">Você pode usar HTML básico para formatação ou usar os botões acima para formatar o texto.</p>
                                                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mt-2">
                                                    <p class="text-sm text-yellow-700"><strong>Importante:</strong> O conteúdo é obrigatório para aulas do tipo texto. Por favor, preencha este campo com o conteúdo da sua aula.</p>
                                                </div>
                                            </div>
                                        </div>

                                        <div id="tipo-video" class="tipo-fields <?php echo ($aula_edicao && $aula_edicao['tipo'] === 'video') ? 'active' : ''; ?>">
                                            <div class="form-group">
                                                <label for="url_video" class="form-label">URL do Vídeo <span class="text-red-500">*</span></label>
                                                <input type="url" id="url_video" name="url_video" class="form-input" value="<?php echo $aula_edicao && $aula_edicao['tipo'] === 'video' ? htmlspecialchars($aula_edicao['url_video']) : ''; ?>" placeholder="https://www.youtube.com/watch?v=...">
                                                <p class="text-xs text-gray-500 mt-1">Suporta links do YouTube, Vimeo e outros serviços de vídeo.</p>
                                            </div>

                                            <div class="form-group">
                                                <label for="duracao" class="form-label">Duração (minutos)</label>
                                                <input type="number" id="duracao" name="duracao" class="form-input" value="<?php echo $aula_edicao && $aula_edicao['tipo'] === 'video' ? $aula_edicao['duracao'] : ''; ?>" min="0">
                                            </div>
                                        </div>

                                        <div id="tipo-arquivo" class="tipo-fields <?php echo ($aula_edicao && $aula_edicao['tipo'] === 'arquivo') ? 'active' : ''; ?>">
                                            <div class="form-group">
                                                <label for="arquivo" class="form-label">Arquivo <?php echo (!$aula_edicao || empty($aula_edicao['arquivo'])) ? '<span class="text-red-500">*</span>' : ''; ?></label>
                                                <input type="file" id="arquivo" name="arquivo" class="form-input">
                                                <p class="text-xs text-gray-500 mt-1">Formatos permitidos: PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX, ZIP, RAR, TXT. Tamanho máximo: 10MB.</p>

                                                <?php if ($aula_edicao && $aula_edicao['tipo'] === 'arquivo' && !empty($aula_edicao['arquivo'])): ?>
                                                <div class="mt-2">
                                                    <p class="text-sm text-gray-600">Arquivo atual: <a href="<?php echo $aula_edicao['arquivo']; ?>" target="_blank" class="text-indigo-600 hover:text-indigo-800"><?php echo basename($aula_edicao['arquivo']); ?></a></p>
                                                    <p class="text-xs text-gray-500">Faça upload de um novo arquivo apenas se desejar substituir o atual.</p>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div id="tipo-quiz" class="tipo-fields <?php echo ($aula_edicao && $aula_edicao['tipo'] === 'quiz') ? 'active' : ''; ?>">
                                            <div class="form-group">
                                                <label for="conteudo_quiz" class="form-label">Conteúdo do Quiz <span class="text-red-500">*</span></label>
                                                <div class="border border-gray-300 rounded-md p-2 bg-white">
                                                    <textarea id="conteudo_quiz" name="conteudo" class="form-textarea border-0 focus:ring-0 w-full min-h-[300px]" rows="15"><?php echo $aula_edicao && $aula_edicao['tipo'] === 'quiz' ? htmlspecialchars($aula_edicao['conteudo']) : "Qual é a capital do Brasil?\n* São Paulo\n** Brasília\n* Rio de Janeiro\n\nQuem escreveu Dom Casmurro?\n* José de Alencar\n** Machado de Assis\n* Carlos Drummond de Andrade"; ?></textarea>
                                                </div>
                                                <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mt-2">
                                                    <p class="text-sm text-blue-700"><strong>Formato do Quiz:</strong></p>
                                                    <p class="text-sm text-blue-700">1. Digite a pergunta em uma linha</p>
                                                    <p class="text-sm text-blue-700">2. Em cada linha seguinte, digite as alternativas:</p>
                                                    <p class="text-sm text-blue-700">   - Use * para alternativas incorretas: <code>* Alternativa incorreta</code></p>
                                                    <p class="text-sm text-blue-700">   - Use ** para a alternativa correta: <code>** Alternativa correta</code></p>
                                                    <p class="text-sm text-blue-700">3. Deixe uma linha em branco entre as perguntas</p>
                                                </div>
                                                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mt-2">
                                                    <p class="text-sm text-yellow-700"><strong>Importante:</strong> O conteúdo é obrigatório para aulas do tipo quiz. Por favor, preencha este campo com pelo menos uma pergunta e suas alternativas.</p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label for="status" class="form-label">Status</label>
                                            <select id="status" name="status" class="form-select">
                                                <option value="ativo" <?php echo $aula_edicao && $aula_edicao['status'] === 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                                                <option value="inativo" <?php echo $aula_edicao && $aula_edicao['status'] === 'inativo' ? 'selected' : ''; ?>>Inativo</option>
                                            </select>
                                        </div>

                                        <div class="flex justify-end space-x-2 mt-6">
                                            <?php if ($aula_edicao): ?>
                                            <a href="aulas.php?modulo_id=<?php echo $modulo_id; ?>" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                                Cancelar
                                            </a>
                                            <button type="submit" name="editar_aula" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                                                <i class="fas fa-save mr-2"></i> Salvar Alterações
                                            </button>
                                            <?php else: ?>
                                            <button type="submit" name="adicionar_aula" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                                                <i class="fas fa-plus mr-2"></i> Adicionar Aula
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
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

        // Toggle tipo fields
        document.getElementById('tipo').addEventListener('change', function() {
            const tipo = this.value;

            // Hide all tipo fields
            document.querySelectorAll('.tipo-fields').forEach(function(field) {
                field.classList.remove('active');
            });

            // Show the selected tipo field
            document.getElementById('tipo-' + tipo).classList.add('active');
        });

        // Editor de texto simples
        function formatText(command) {
            const textarea = document.getElementById('conteudo');
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const selectedText = textarea.value.substring(start, end);
            let replacement = '';

            switch(command) {
                case 'bold':
                    replacement = '<strong>' + selectedText + '</strong>';
                    break;
                case 'italic':
                    replacement = '<em>' + selectedText + '</em>';
                    break;
                case 'underline':
                    replacement = '<u>' + selectedText + '</u>';
                    break;
                case 'h2':
                    replacement = '<h2>' + selectedText + '</h2>';
                    break;
                case 'ul':
                    if (selectedText.includes('\n')) {
                        const lines = selectedText.split('\n');
                        replacement = '<ul>\n';
                        lines.forEach(line => {
                            if (line.trim() !== '') {
                                replacement += '  <li>' + line + '</li>\n';
                            }
                        });
                        replacement += '</ul>';
                    } else {
                        replacement = '<ul>\n  <li>' + selectedText + '</li>\n</ul>';
                    }
                    break;
                case 'ol':
                    if (selectedText.includes('\n')) {
                        const lines = selectedText.split('\n');
                        replacement = '<ol>\n';
                        lines.forEach(line => {
                            if (line.trim() !== '') {
                                replacement += '  <li>' + line + '</li>\n';
                            }
                        });
                        replacement += '</ol>';
                    } else {
                        replacement = '<ol>\n  <li>' + selectedText + '</li>\n</ol>';
                    }
                    break;
                case 'link':
                    const url = prompt('Digite a URL do link:', 'http://');
                    if (url) {
                        replacement = '<a href="' + url + '">' + (selectedText || 'Link') + '</a>';
                    } else {
                        return;
                    }
                    break;
                case 'image':
                    const imgUrl = prompt('Digite a URL da imagem:', 'http://');
                    if (imgUrl) {
                        const alt = prompt('Digite o texto alternativo da imagem:', '');
                        replacement = '<img src="' + imgUrl + '" alt="' + alt + '" style="max-width: 100%;">';
                    } else {
                        return;
                    }
                    break;
            }

            // Insere o texto formatado
            textarea.focus();
            textarea.value = textarea.value.substring(0, start) + replacement + textarea.value.substring(end);

            // Posiciona o cursor após o texto inserido
            const newCursorPos = start + replacement.length;
            textarea.setSelectionRange(newCursorPos, newCursorPos);
        }

        // Verificar se o conteúdo está preenchido quando o tipo é texto ou quiz
        document.querySelector('form').addEventListener('submit', function(e) {
            const tipo = document.getElementById('tipo').value;

            if (tipo === 'texto') {
                const conteudo = document.getElementById('conteudo').value.trim();
                if (!conteudo) {
                    e.preventDefault();
                    alert('O conteúdo é obrigatório para aulas do tipo texto. Por favor, preencha o campo de conteúdo.');
                    document.getElementById('conteudo').focus();
                    return;
                }
            }

            if (tipo === 'quiz') {
                const conteudo = document.getElementById('conteudo_quiz').value.trim();
                if (!conteudo) {
                    e.preventDefault();
                    alert('O conteúdo é obrigatório para aulas do tipo quiz. Por favor, preencha o campo de conteúdo do quiz.');
                    document.getElementById('conteudo_quiz').focus();
                    return;
                }

                // Verifica se o formato do quiz está correto
                const linhas = conteudo.split('\n');
                let temPergunta = false;
                let temAlternativaCorreta = false;

                for (let i = 0; i < linhas.length; i++) {
                    const linha = linhas[i].trim();
                    if (linha !== '' && !linha.startsWith('*')) {
                        temPergunta = true;
                    }
                    if (linha.startsWith('**')) {
                        temAlternativaCorreta = true;
                    }
                }

                if (!temPergunta) {
                    e.preventDefault();
                    alert('O quiz deve conter pelo menos uma pergunta. Digite a pergunta em uma linha sem asteriscos.');
                    document.getElementById('conteudo_quiz').focus();
                    return;
                }

                if (!temAlternativaCorreta) {
                    e.preventDefault();
                    alert('O quiz deve conter pelo menos uma alternativa correta marcada com **.');
                    document.getElementById('conteudo_quiz').focus();
                    return;
                }
            }
        });
    </script>
</body>
</html>