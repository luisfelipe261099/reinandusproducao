<?php
/**
 * Gerenciamento de Documentos do Polo
 */

// Inicializa o sistema
require_once __DIR__ . '/../includes/init.php';

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

// Obtém o ID do polo associado ao usuário
$usuario_id = getUsuarioId();
$sql = "SELECT id FROM polos WHERE responsavel_id = ?";
$resultado = $db->fetchOne($sql, [$usuario_id]);
$polo_id = $resultado['id'] ?? null;

if (!$polo_id) {
    setMensagem('erro', 'Não foi possível identificar o polo associado ao seu usuário.');
    redirect('index.php');
    exit;
}

// Define a ação padrão
$action = $_GET['action'] ?? 'listar';

// Processa o formulário de solicitação de documento
if (isPost() && $action === 'solicitar') {
    $aluno_id = $_POST['aluno_id'] ?? '';
    $tipo_documento_id = $_POST['tipo_documento_id'] ?? '';
    $quantidade = $_POST['quantidade'] ?? 1;
    $observacoes = $_POST['observacoes'] ?? '';

    // Validação básica
    $errors = [];

    if (empty($aluno_id)) {
        $errors[] = 'O aluno é obrigatório';
    }

    if (empty($tipo_documento_id)) {
        $errors[] = 'O tipo de documento é obrigatório';
    }

    if ($quantidade < 1) {
        $errors[] = 'A quantidade deve ser maior que zero';
    }

    // Verifica se o aluno pertence ao polo
    if (!empty($aluno_id)) {
        $sql = "SELECT id FROM alunos WHERE id = ? AND polo_id = ?";
        $resultado = $db->fetchOne($sql, [$aluno_id, $polo_id]);

        if (!$resultado) {
            $errors[] = 'Aluno não encontrado ou não pertence ao seu polo';
        }
    }

    // Verifica o limite de documentos do polo
    $sql = "SELECT limite_documentos, documentos_emitidos FROM polos WHERE id = ?";
    $polo = $db->fetchOne($sql, [$polo_id]);

    if ($polo) {
        $limite_documentos = $polo['limite_documentos'] ?? 0;
        $documentos_emitidos = $polo['documentos_emitidos'] ?? 0;

        if ($documentos_emitidos + $quantidade > $limite_documentos) {
            $errors[] = 'Limite de documentos excedido. Você só pode solicitar mais ' . ($limite_documentos - $documentos_emitidos) . ' documento(s).';
        }
    }

    // Se não houver erros, salva a solicitação
    if (empty($errors)) {
        try {
            // Verifica se a tabela tipos_documentos existe
            try {
                $tabelas = $db->fetchAll("SHOW TABLES LIKE 'tipos_documentos'");
                if (empty($tabelas)) {
                    error_log("ATENÇÃO: Tabela tipos_documentos não existe. Criando tabela...");

                    // Cria a tabela tipos_documentos se não existir
                    $sql_criar_tabela_tipos = "CREATE TABLE IF NOT EXISTS tipos_documentos (
                        id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                        nome VARCHAR(100) NOT NULL,
                        descricao TEXT DEFAULT NULL,
                        valor DECIMAL(10,2) DEFAULT NULL,
                        status ENUM('ativo','inativo') NOT NULL DEFAULT 'ativo',
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        PRIMARY KEY (id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

                    $db->query($sql_criar_tabela_tipos);
                    error_log("Tabela tipos_documentos criada com sucesso.");

                    // Insere tipos de documentos padrão
                    $db->query("INSERT INTO tipos_documentos (nome, descricao, status) VALUES
                        ('Histórico Escolar', 'Histórico Escolar completo do Aluno', 'ativo'),
                        ('Declaração de Matrícula', 'Declaração de Matrícula do Aluno', 'ativo'),
                        ('Certificado', 'Certificado de Conclusão de Curso', 'ativo'),
                        ('Diploma', 'Diploma de Conclusão de Curso', 'ativo')");
                    error_log("Tipos de documentos padrão inseridos com sucesso.");
                }
            } catch (Exception $e) {
                error_log("Erro ao verificar/criar tabela tipos_documentos: " . $e->getMessage());
            }

            // Verifica se a tabela solicitacoes_documentos existe
            try {
                $tabelas = $db->fetchAll("SHOW TABLES LIKE 'solicitacoes_documentos'");
                if (empty($tabelas)) {
                    error_log("ATENÇÃO: Tabela solicitacoes_documentos não existe. Criando tabela...");

                    // Cria a tabela se não existir
                    $sql_criar_tabela = "CREATE TABLE IF NOT EXISTS solicitacoes_documentos (
                        id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                        aluno_id INT(10) UNSIGNED NOT NULL,
                        polo_id INT(10) UNSIGNED NOT NULL,
                        tipo_documento_id INT(10) UNSIGNED NOT NULL,
                        quantidade INT(11) NOT NULL DEFAULT 1,
                        observacoes TEXT NULL DEFAULT NULL,
                        finalidade VARCHAR(255) NULL DEFAULT NULL,
                        status ENUM('solicitado', 'processando', 'pronto', 'entregue', 'cancelado') NOT NULL DEFAULT 'solicitado',
                        pago TINYINT(1) NOT NULL DEFAULT 0,
                        data_solicitacao DATETIME DEFAULT CURRENT_TIMESTAMP,
                        solicitante_id INT(10) UNSIGNED NULL,
                        created_at TIMESTAMP NULL DEFAULT NULL,
                        updated_at TIMESTAMP NULL DEFAULT NULL,
                        PRIMARY KEY (id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

                    $db->query($sql_criar_tabela);
                    error_log("Tabela solicitacoes_documentos criada com sucesso.");
                }
            } catch (Exception $e) {
                error_log("Erro ao verificar/criar tabela solicitacoes_documentos: " . $e->getMessage());
            }

            // Verifica se o aluno existe
            $aluno_existe = $db->fetchOne("SELECT id FROM alunos WHERE id = ?", [$aluno_id]);
            if (!$aluno_existe) {
                throw new Exception("Aluno não encontrado (ID: $aluno_id)");
            }

            // Verifica se o tipo de documento existe
            $tipo_documento_existe = $db->fetchOne("SELECT id FROM tipos_documentos WHERE id = ?", [$tipo_documento_id]);
            if (!$tipo_documento_existe) {
                throw new Exception("Tipo de documento não encontrado (ID: $tipo_documento_id)");
            }

            // Prepara os dados para inserção
            $dados_solicitacao = [
                'polo_id' => $polo_id,
                'aluno_id' => $aluno_id,
                'tipo_documento_id' => $tipo_documento_id,
                'quantidade' => $quantidade,
                'observacoes' => $observacoes,
                'status' => 'solicitado',
                'data_solicitacao' => date('Y-m-d H:i:s'),
                'solicitante_id' => $usuario_id,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            // Insere a solicitação
            error_log("Tentando inserir solicitação: " . json_encode($dados_solicitacao));
            $solicitacao_id = $db->insert('solicitacoes_documentos', $dados_solicitacao);

            if (!$solicitacao_id) {
                throw new Exception("Falha ao inserir solicitação no banco de dados");
            }

            // Registra um log da solicitação
            error_log("Solicitação de documento criada com sucesso: ID=$solicitacao_id, Polo=$polo_id, Aluno=$aluno_id, Tipo=$tipo_documento_id");

            // Atualiza o contador de documentos emitidos do polo
            $db->update('polos', [
                'documentos_emitidos' => $documentos_emitidos + $quantidade,
                'updated_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$polo_id]);

            // Redireciona para a visualização da solicitação
            setMensagem('sucesso', 'Documento solicitado com sucesso!');
            redirect('documentos.php?action=visualizar&id=' . $solicitacao_id);
            exit;
        } catch (Exception $e) {
            $erro_mensagem = $e->getMessage();
            error_log('Erro ao solicitar documento: ' . $erro_mensagem);

            // Mensagens de erro mais específicas para o usuário
            if (strpos($erro_mensagem, 'Aluno não encontrado') !== false) {
                $errors[] = 'O aluno selecionado não foi encontrado. Por favor, selecione outro aluno.';
            } else if (strpos($erro_mensagem, 'Tipo de documento não encontrado') !== false) {
                $errors[] = 'O tipo de documento selecionado não foi encontrado. Por favor, selecione outro tipo de documento.';
            } else if (strpos($erro_mensagem, 'Duplicate entry') !== false) {
                $errors[] = 'Já existe uma solicitação idêntica para este aluno e documento. Por favor, verifique a lista de solicitações.';
            } else {
                $errors[] = 'Erro ao solicitar documento: ' . $erro_mensagem;
            }

            // Registra detalhes adicionais para depuração
            error_log('Detalhes da solicitação que falhou: Aluno=' . $aluno_id . ', Tipo=' . $tipo_documento_id . ', Polo=' . $polo_id);
        }
    }
}

// Carrega os dados conforme a ação
switch ($action) {
    case 'download':
        // Faz o download do documento
        $documento_id = $_GET['id'] ?? null;

        if (empty($documento_id)) {
            setMensagem('erro', 'Documento não informado.');
            redirect('documentos.php');
            exit;
        }

        // Busca o documento
        $sql = "SELECT d.*, a.nome as aluno_nome, td.nome as tipo_documento_nome
                FROM documentos_emitidos d
                LEFT JOIN alunos a ON d.aluno_id = a.id
                LEFT JOIN tipos_documentos td ON d.tipo_documento_id = td.id
                WHERE d.id = ?";
        $documento = $db->fetchOne($sql, [$documento_id]);

        if (!$documento) {
            setMensagem('erro', 'Documento não encontrado.');
            redirect('documentos.php');
            exit;
        }

        // Verifica se o documento pertence ao polo
        if ($documento['polo_id'] != $polo_id) {
            // Verifica se o aluno pertence ao polo
            $sql = "SELECT id FROM alunos WHERE id = ? AND polo_id = ?";
            $aluno = $db->fetchOne($sql, [$documento['aluno_id'], $polo_id]);

            if (!$aluno) {
                setMensagem('erro', 'Este documento não pertence ao seu polo.');
                redirect('documentos.php');
                exit;
            }
        }

        // Verifica se o arquivo existe
        $arquivo = '../uploads/documentos/' . $documento['arquivo'];
        $arquivo_encontrado = false;

        if (file_exists($arquivo)) {
            $arquivo_encontrado = true;
        } else {
            // Tenta encontrar o arquivo pelo nome em uploads/documentos
            $dir_uploads = '../uploads/documentos/';
            if (is_dir($dir_uploads)) {
                $arquivos = scandir($dir_uploads);
                $nome_arquivo = basename($documento['arquivo']);

                foreach ($arquivos as $arq) {
                    if (strtolower($arq) === strtolower($nome_arquivo)) {
                        $arquivo = $dir_uploads . $arq;
                        $arquivo_encontrado = true;
                        break;
                    }
                }
            }

            // Tenta encontrar o arquivo na pasta temp
            if (!$arquivo_encontrado) {
                $arquivo_temp = '../temp/' . basename($documento['arquivo']);
                if (file_exists($arquivo_temp)) {
                    $arquivo = $arquivo_temp;
                    $arquivo_encontrado = true;
                }
            }
        }

        if (!$arquivo_encontrado) {
            setMensagem('erro', 'Arquivo não encontrado no servidor.');
            redirect('documentos.php');
            exit;
        }

        // Define o tipo de conteúdo com base na extensão do arquivo
        $extension = pathinfo($arquivo, PATHINFO_EXTENSION);
        $content_type = $extension === 'html' ? 'text/html' : 'application/pdf';

        // Define o nome do arquivo para download
        $nome_download = $documento['tipo_documento_nome'] . ' - ' . $documento['aluno_nome'] . '.' . $extension;
        $nome_download = str_replace(' ', '_', $nome_download);

        // Configura os cabeçalhos para download
        header('Content-Type: ' . $content_type);
        header('Content-Disposition: attachment; filename="' . $nome_download . '"');
        header('Content-Length: ' . filesize($arquivo));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Access-Control-Allow-Origin: *'); // Permite acesso de qualquer origem

        // Log para depuração
        error_log("Enviando arquivo para download: " . $arquivo);
        error_log("Content-Type: " . $content_type);
        error_log("Content-Disposition: attachment; filename=\"" . $nome_download . "\"");
        error_log("Content-Length: " . filesize($arquivo));

        // Limpa qualquer saída anterior
        ob_clean();
        flush();

        // Envia o arquivo
        readfile($arquivo);
        exit;

    case 'solicitar':
        // Carrega os alunos do polo
        $sql = "SELECT id, nome, cpf, email FROM alunos WHERE polo_id = ? AND status = 'ativo' ORDER BY nome";
        $alunos = $db->fetchAll($sql, [$polo_id]);

        // Carrega os tipos de documentos disponíveis
        $sql = "SELECT id, nome, descricao, valor FROM tipos_documentos WHERE status = 'ativo' ORDER BY nome";
        $tipos_documentos = $db->fetchAll($sql);

        // Verifica se a tabela documentos_alunos existe
        $tabela_existe = false;
        try {
            $result = $db->query("SHOW TABLES LIKE 'documentos_alunos'");
            $tabela_existe = !empty($result);
        } catch (Exception $e) {
            error_log('Erro ao verificar tabela documentos_alunos: ' . $e->getMessage());
        }

        // Verifica documentos pendentes do aluno selecionado
        $aluno_id = $_GET['aluno_id'] ?? $_POST['aluno_id'] ?? null;
        $documentos_pendentes = [];

        if ($tabela_existe && $aluno_id) {
            try {
                // Busca tipos de documentos obrigatórios
                $sql = "SELECT id, nome FROM tipos_documentos_pessoais WHERE obrigatorio = 1 AND status = 'ativo'";
                $tipos_documentos_obrigatorios = $db->fetchAll($sql);

                if (!empty($tipos_documentos_obrigatorios)) {
                    foreach ($tipos_documentos_obrigatorios as $tipo) {
                        // Verifica se o aluno tem o documento
                        $sql = "SELECT id FROM documentos_alunos
                                WHERE aluno_id = ? AND tipo_documento_id = ? AND status != 'rejeitado'";
                        $documento = $db->fetchOne($sql, [$aluno_id, $tipo['id']]);

                        if (!$documento) {
                            $documentos_pendentes[] = $tipo['nome'];
                        }
                    }
                }
            } catch (Exception $e) {
                error_log('Erro ao buscar documentos pendentes do aluno: ' . $e->getMessage());
            }
        }

        // Se foi passado um aluno_id na URL, carrega os dados do aluno
        $aluno_id = $_GET['aluno_id'] ?? null;
        if ($aluno_id) {
            $sql = "SELECT id, nome, cpf, email FROM alunos WHERE id = ? AND polo_id = ?";
            $aluno = $db->fetchOne($sql, [$aluno_id, $polo_id]);

            if (!$aluno) {
                setMensagem('erro', 'Aluno não encontrado ou não pertence ao seu polo.');
                redirect('documentos.php');
                exit;
            }
        }

        // Define o título da página
        $titulo_pagina = 'Solicitar Documento';
        break;

    case 'visualizar':
        // Verifica se é um ID de documento ou de solicitação
        if (isset($_GET['documento_id'])) {
            $documento_id = $_GET['documento_id'];

            // Busca o documento
            $sql = "SELECT d.*, a.nome as aluno_nome, td.nome as tipo_documento_nome
                    FROM documentos_emitidos d
                    LEFT JOIN alunos a ON d.aluno_id = a.id
                    LEFT JOIN tipos_documentos td ON d.tipo_documento_id = td.id
                    WHERE d.id = ?";
            $documento = $db->fetchOne($sql, [$documento_id]);

            if (!$documento) {
                setMensagem('erro', 'Documento não encontrado.');
                redirect('documentos.php');
                exit;
            }

            // Verifica se o documento pertence ao polo
            if ($documento['polo_id'] != $polo_id) {
                // Verifica se o aluno pertence ao polo
                $sql = "SELECT id FROM alunos WHERE id = ? AND polo_id = ?";
                $aluno = $db->fetchOne($sql, [$documento['aluno_id'], $polo_id]);

                if (!$aluno) {
                    setMensagem('erro', 'Este documento não pertence ao seu polo.');
                    redirect('documentos.php');
                    exit;
                }
            }

            // Verifica se o arquivo existe
            $arquivo = '../uploads/documentos/' . $documento['arquivo'];
            $arquivo_encontrado = false;

            if (file_exists($arquivo)) {
                $arquivo_encontrado = true;
            } else {
                // Tenta encontrar o arquivo pelo nome em uploads/documentos
                $dir_uploads = '../uploads/documentos/';
                if (is_dir($dir_uploads)) {
                    $arquivos = scandir($dir_uploads);
                    $nome_arquivo = basename($documento['arquivo']);

                    foreach ($arquivos as $arq) {
                        if (strtolower($arq) === strtolower($nome_arquivo)) {
                            $arquivo = $dir_uploads . $arq;
                            $arquivo_encontrado = true;
                            break;
                        }
                    }
                }

                // Tenta encontrar o arquivo na pasta temp
                if (!$arquivo_encontrado) {
                    $arquivo_temp = '../temp/' . basename($documento['arquivo']);
                    if (file_exists($arquivo_temp)) {
                        $arquivo = $arquivo_temp;
                        $arquivo_encontrado = true;
                    }
                }
            }

            if (!$arquivo_encontrado) {
                setMensagem('erro', 'Arquivo não encontrado no servidor.');
                redirect('documentos.php');
                exit;
            }

            // Define o tipo de conteúdo com base na extensão do arquivo
            $extension = pathinfo($arquivo, PATHINFO_EXTENSION);
            $content_type = $extension === 'html' ? 'text/html' : 'application/pdf';

            // Define os cabeçalhos para exibir o documento diretamente no navegador
            header('Content-Type: ' . $content_type);
            header('Content-Disposition: inline; filename="' . basename($arquivo) . '"');

            // Exibe o conteúdo do arquivo
            readfile($arquivo);
            exit;
        } else {
            // Visualização de solicitação
            $solicitacao_id = $_GET['id'] ?? 0;

            // Carrega os dados da solicitação
            $sql = "SELECT sd.*, a.nome as aluno_nome, a.cpf as aluno_cpf, a.email as aluno_email,
                           td.nome as tipo_documento_nome, td.descricao as tipo_documento_descricao,
                           u.nome as solicitante_nome
                    FROM solicitacoes_documentos sd
                    JOIN alunos a ON sd.aluno_id = a.id
                    JOIN tipos_documentos td ON sd.tipo_documento_id = td.id
                    LEFT JOIN usuarios u ON sd.solicitante_id = u.id
                    WHERE sd.id = ? AND sd.polo_id = ?";
            $solicitacao = $db->fetchOne($sql, [$solicitacao_id, $polo_id]);

            if (!$solicitacao) {
                setMensagem('erro', 'Solicitação não encontrada ou não pertence ao seu polo.');
                redirect('documentos.php');
                exit;
            }

            // Define o título da página
            $titulo_pagina = 'Visualizar Solicitação de Documento';
        }
        break;

    default: // listar
        // Carrega as solicitações do polo
        $sql = "SELECT sd.id, sd.created_at as data_solicitacao, sd.status, sd.quantidade,
                       td.nome as tipo_documento_nome,
                       a.nome as aluno_nome
                FROM solicitacoes_documentos sd
                JOIN tipos_documentos td ON sd.tipo_documento_id = td.id
                JOIN alunos a ON sd.aluno_id = a.id
                WHERE sd.polo_id = ?
                ORDER BY sd.created_at DESC";
        $solicitacoes = $db->fetchAll($sql, [$polo_id]);

        // Define o título da página
        $titulo_pagina = 'Gerenciar Documentos';
        break;
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
        .badge {
            border-radius: 9999px;
            padding: 0.25rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-primary { background-color: #3B82F6; color: white; }
        .badge-warning { background-color: #F59E0B; color: white; }
        .badge-danger { background-color: #EF4444; color: white; }
        .badge-success { background-color: #10B981; color: white; }

        .btn-primary {
            background-color: #3B82F6;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 500;
            transition: background-color 0.2s;
        }
        .btn-primary:hover { background-color: #2563EB; }

        .btn-secondary {
            background-color: #6B7280;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 500;
            transition: background-color 0.2s;
        }
        .btn-secondary:hover { background-color: #4B5563; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <?php include 'includes/header.php'; ?>

            <!-- Main -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
                <div class="container mx-auto">
                    <div class="flex justify-between items-center mb-6">
                        <h1 class="text-2xl font-bold text-gray-800"><?php echo $titulo_pagina; ?></h1>
                        <?php if ($action === 'listar'): ?>
                        <a href="documentos.php?action=solicitar" class="btn-primary">
                            <i class="fas fa-plus mr-2"></i> Solicitar Documento
                        </a>
                        <?php endif; ?>
                    </div>

                  <?php if (isset($_SESSION['mensagem']) && isset($_SESSION['mensagem_tipo'])): ?>
<div class="bg-<?php echo $_SESSION['mensagem_tipo'] === 'sucesso' ? 'green' : 'red'; ?>-100 border-l-4 border-<?php echo $_SESSION['mensagem_tipo'] === 'sucesso' ? 'green' : 'red'; ?>-500 text-<?php echo $_SESSION['mensagem_tipo'] === 'sucesso' ? 'green' : 'red'; ?>-700 p-4 mb-6">
    <?php echo $_SESSION['mensagem']; ?>
</div>
<?php
// Limpa a mensagem da sessão
unset($_SESSION['mensagem']);
unset($_SESSION['mensagem_tipo']);
endif;
?>

                    <?php if (!empty($errors)): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
                        <ul class="list-disc list-inside">
                            <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <?php if ($action === 'listar'): ?>
                    <!-- Lista de Solicitações -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-800">Minhas Solicitações de Documentos</h2>
                        </div>
                        <div class="p-6">
                            <?php if (empty($solicitacoes)): ?>
                            <div class="text-center text-gray-500 py-4">
                                <p>Você ainda não solicitou nenhum documento.</p>
                                <a href="documentos.php?action=solicitar" class="btn-primary inline-block mt-4">Solicitar Documento</a>
                            </div>
                            <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aluno</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Documento</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantidade</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($solicitacoes as $solicitacao): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($solicitacao['aluno_nome']); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($solicitacao['tipo_documento_nome']); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900"><?php echo $solicitacao['quantidade']; ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="badge <?php
                                                    echo $solicitacao['status'] === 'solicitado' ? 'badge-warning' :
                                                        ($solicitacao['status'] === 'processando' ? 'badge-primary' :
                                                        ($solicitacao['status'] === 'pronto' ? 'badge-success' :
                                                        ($solicitacao['status'] === 'entregue' ? 'badge-success' : 'badge-danger')));
                                                ?>">
                                                    <?php
                                                        echo $solicitacao['status'] === 'solicitado' ? 'Solicitado' :
                                                            ($solicitacao['status'] === 'processando' ? 'Processando' :
                                                            ($solicitacao['status'] === 'pronto' ? 'Pronto' :
                                                            ($solicitacao['status'] === 'entregue' ? 'Entregue' : 'Cancelado')));
                                                    ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900"><?php echo date('d/m/Y', strtotime($solicitacao['data_solicitacao'])); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <a href="documentos.php?action=visualizar&id=<?php echo $solicitacao['id']; ?>" class="text-blue-600 hover:text-blue-900">Visualizar</a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php elseif ($action === 'solicitar'): ?>
                    <!-- Formulário de Solicitação de Documento -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-800">Solicitar Documento</h2>
                        </div>
                        <div class="p-6">
                            <?php if (!empty($documentos_pendentes)): ?>
                            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-yellow-800">Atenção: Documentos Pendentes</h3>
                                        <div class="mt-2 text-sm text-yellow-700">
                                            <p>O aluno selecionado possui documentos obrigatórios pendentes:</p>
                                            <ul class="mt-2 pl-5 list-disc">
                                                <?php foreach ($documentos_pendentes as $documento): ?>
                                                <li><?php echo htmlspecialchars($documento); ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                            <p class="mt-2">Você pode continuar com a solicitação, mas recomendamos que os documentos sejam atualizados.</p>
                                            <a href="alunos.php?action=documentos&id=<?php echo $aluno_id; ?>" class="inline-flex items-center mt-2 text-blue-600 hover:text-blue-800">
                                                <i class="fas fa-upload mr-1"></i> Adicionar documentos
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            <form method="post" action="documentos.php?action=solicitar">
                                <div class="mb-4">
                                    <label for="aluno_id" class="block text-sm font-medium text-gray-700 mb-1">Aluno</label>
                                    <select id="aluno_id" name="aluno_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" required>
                                        <option value="">Selecione um aluno</option>
                                        <?php foreach ($alunos as $aluno_item): ?>
                                        <option value="<?php echo $aluno_item['id']; ?>" <?php echo (isset($aluno) && $aluno['id'] == $aluno_item['id']) || (isset($_POST['aluno_id']) && $_POST['aluno_id'] == $aluno_item['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($aluno_item['nome']); ?> (<?php echo formatarCpf($aluno_item['cpf']); ?>)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-4">
                                    <label for="tipo_documento_id" class="block text-sm font-medium text-gray-700 mb-1">Tipo de Documento</label>
                                    <select id="tipo_documento_id" name="tipo_documento_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" required>
                                        <option value="">Selecione um tipo de documento</option>
                                        <?php foreach ($tipos_documentos as $tipo_documento): ?>
                                        <option value="<?php echo $tipo_documento['id']; ?>" <?php echo isset($_POST['tipo_documento_id']) && $_POST['tipo_documento_id'] == $tipo_documento['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($tipo_documento['nome']); ?> (R$ <?php echo isset($tipo_documento['valor']) && $tipo_documento['valor'] !== null ? number_format($tipo_documento['valor'], 2, ',', '.') : '0,00'; ?>)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-4">
                                    <label for="quantidade" class="block text-sm font-medium text-gray-700 mb-1">Quantidade</label>
                                    <input type="number" id="quantidade" name="quantidade" min="1" max="10" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" value="<?php echo isset($_POST['quantidade']) ? htmlspecialchars($_POST['quantidade']) : '1'; ?>" required>
                                </div>
                                <div class="mb-4">
                                    <label for="observacoes" class="block text-sm font-medium text-gray-700 mb-1">Observações (opcional)</label>
                                    <textarea id="observacoes" name="observacoes" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50"><?php echo isset($_POST['observacoes']) ? htmlspecialchars($_POST['observacoes']) : ''; ?></textarea>
                                </div>
                                <div class="flex justify-end">
                                    <a href="documentos.php" class="btn-secondary mr-2">Cancelar</a>
                                    <button type="submit" class="btn-primary">Solicitar Documento</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php elseif ($action === 'visualizar' && isset($solicitacao)): ?>
                    <!-- Visualização de Solicitação -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <div class="flex justify-between items-center">
                                <h2 class="text-lg font-semibold text-gray-800">Solicitação de <?php echo htmlspecialchars($solicitacao['tipo_documento_nome']); ?></h2>
                                <span class="badge <?php
                                    echo $solicitacao['status'] === 'solicitado' ? 'badge-warning' :
                                        ($solicitacao['status'] === 'processando' ? 'badge-primary' :
                                        ($solicitacao['status'] === 'pronto' ? 'badge-success' :
                                        ($solicitacao['status'] === 'entregue' ? 'badge-success' : 'badge-danger')));
                                ?>">
                                    <?php
                                        echo $solicitacao['status'] === 'solicitado' ? 'Solicitado' :
                                            ($solicitacao['status'] === 'processando' ? 'Processando' :
                                            ($solicitacao['status'] === 'pronto' ? 'Pronto' :
                                            ($solicitacao['status'] === 'entregue' ? 'Entregue' : 'Cancelado')));
                                    ?>
                                </span>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                                <div>
                                    <p class="text-sm text-gray-500">Aluno</p>
                                    <p class="font-medium"><?php echo htmlspecialchars($solicitacao['aluno_nome']); ?></p>
                                    <p class="text-sm text-gray-500"><?php echo formatarCpf($solicitacao['aluno_cpf']); ?> | <?php echo htmlspecialchars($solicitacao['aluno_email']); ?></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Documento</p>
                                    <p class="font-medium"><?php echo htmlspecialchars($solicitacao['tipo_documento_nome']); ?></p>
                                    <p class="text-sm text-gray-500"><?php echo htmlspecialchars($solicitacao['tipo_documento_descricao']); ?></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Quantidade</p>
                                    <p class="font-medium"><?php echo $solicitacao['quantidade']; ?></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Data da Solicitação</p>
                                    <p class="font-medium"><?php echo date('d/m/Y H:i', strtotime($solicitacao['data_solicitacao'])); ?></p>
                                </div>
                            </div>

                            <?php if (!empty($solicitacao['observacoes'])): ?>
                            <div class="mb-6">
                                <p class="text-sm text-gray-500 mb-2">Observações</p>
                                <div class="bg-gray-50 p-4 rounded-md">
                                    <?php echo nl2br(htmlspecialchars($solicitacao['observacoes'])); ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if ($solicitacao['status'] === 'pronto'): ?>
                            <div class="bg-green-50 p-4 rounded-md mb-6">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-check-circle text-green-400"></i>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-green-800">Documento pronto</h3>
                                        <div class="mt-2 text-sm text-green-700">
                                            <p>O documento solicitado está pronto e disponível para download.</p>
                                        </div>
                                        <?php if (!empty($solicitacao['documento_id'])): ?>
                                        <div class="mt-3">
                                            <a href="../documentos.php?action=download&id=<?php echo $solicitacao['documento_id']; ?>" class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded hover:bg-green-700">
                                                <i class="fas fa-download mr-1"></i> Baixar Documento
                                            </a>
                                        </div>
                                        <?php else: ?>
                                        <div class="mt-3">
                                            <p class="text-sm text-yellow-600">O documento ainda não está disponível para download. Por favor, aguarde alguns instantes e atualize a página.</p>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if ($solicitacao['status'] === 'entregue'): ?>
                            <div class="bg-green-50 p-4 rounded-md mb-6">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-check-circle text-green-400"></i>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-green-800">Documento entregue</h3>
                                        <div class="mt-2 text-sm text-green-700">
                                            <p>O documento solicitado foi entregue em <?php echo date('d/m/Y', strtotime($solicitacao['data_entrega'] ?? $solicitacao['updated_at'])); ?>.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="flex justify-between">
                        <a href="documentos.php" class="btn-secondary">Voltar para a Lista</a>
                        <?php if ($solicitacao['status'] === 'solicitado'): ?>
                        <a href="documentos.php?action=solicitar" class="btn-primary">Solicitar Outro Documento</a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </main>

            <!-- Footer -->
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>

    <script src="../js/layout-fixes.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle sidebar
            const toggleButton = document.getElementById('toggle-sidebar');
            if (toggleButton) {
                toggleButton.addEventListener('click', function() {
                    const sidebar = document.getElementById('sidebar');
                    sidebar.classList.toggle('sidebar-collapsed');
                    sidebar.classList.toggle('sidebar-expanded');

                    const labels = document.querySelectorAll('.sidebar-label');
                    labels.forEach(label => {
                        label.classList.toggle('hidden');
                    });
                });
            }

            // Toggle user menu
            const userMenuButton = document.getElementById('user-menu-button');
            if (userMenuButton) {
                userMenuButton.addEventListener('click', function() {
                    const menu = document.getElementById('user-menu');
                    menu.classList.toggle('hidden');
                });
            }

            // Close user menu when clicking outside
            document.addEventListener('click', function(event) {
                const menu = document.getElementById('user-menu');
                const button = document.getElementById('user-menu-button');

                if (menu && button && !menu.contains(event.target) && !button.contains(event.target)) {
                    menu.classList.add('hidden');
                }
            });
        });
    </script>
</body>
</html>
