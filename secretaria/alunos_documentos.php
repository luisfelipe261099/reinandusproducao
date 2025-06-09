<?php
/**
 * Gerenciamento de Documentos dos Alunos - Secretaria
 */

// Inicializa o sistema
require_once 'includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Instancia o banco de dados
$db = Database::getInstance();

// Verifica se a tabela documentos_alunos existe
$tabela_existe = false;
try {
    $result = $db->query("SHOW TABLES LIKE 'documentos_alunos'");
    $tabela_existe = !empty($result);

    if (!$tabela_existe) {
        // Redireciona para a página de verificação de tabelas
        setMensagem('erro', 'A tabela de documentos dos alunos não existe. Por favor, contate o administrador do sistema.');
        redirect('index.php');
        exit;
    }
} catch (Exception $e) {
    error_log('Erro ao verificar tabela documentos_alunos: ' . $e->getMessage());
    setMensagem('erro', 'Erro ao verificar a estrutura do banco de dados. Por favor, contate o administrador do sistema.');
    redirect('index.php');
    exit;
}

// Define a ação padrão
$action = $_GET['action'] ?? 'listar';

// Obtém o ID do aluno
$aluno_id = $_GET['id'] ?? null;

// Verifica se o aluno existe
if ($aluno_id) {
    $sql = "SELECT id, nome, cpf, email, polo_id FROM alunos WHERE id = ?";
    $aluno = $db->fetchOne($sql, [$aluno_id]);

    if (!$aluno) {
        setMensagem('erro', 'Aluno não encontrado.');
        redirect('alunos.php');
        exit;
    }

    // Busca o nome do polo
    if (!empty($aluno['polo_id'])) {
        $sql = "SELECT nome FROM polos WHERE id = ?";
        $polo = $db->fetchOne($sql, [$aluno['polo_id']]);
        $polo_nome = $polo ? $polo['nome'] : 'Polo não encontrado';
    } else {
        $polo_nome = 'Não definido';
    }
}

// Processa o upload de documento
if (isPost() && $action === 'upload') {
    $tipo_documento_id = $_POST['tipo_documento_id'] ?? '';
    $numero_documento = $_POST['numero_documento'] ?? '';
    $orgao_emissor = $_POST['orgao_emissor'] ?? '';
    $data_validade = $_POST['data_validade'] ?? '';
    $observacoes = $_POST['observacoes'] ?? '';

    // Validação básica
    $errors = [];

    if (empty($tipo_documento_id)) {
        $errors[] = 'O tipo de documento é obrigatório';
    }

    // Verifica se foi enviado um arquivo
    if (empty($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'É necessário enviar um arquivo';
    } else {
        // Verifica o tipo de arquivo
        $allowed_types = ['application/pdf', 'image/jpeg', 'image/png'];
        $file_type = $_FILES['arquivo']['type'];

        if (!in_array($file_type, $allowed_types)) {
            $errors[] = 'Tipo de arquivo não permitido. Apenas PDF, JPEG e PNG são aceitos.';
        }

        // Verifica o tamanho do arquivo (máximo 5MB)
        $max_size = 5 * 1024 * 1024; // 5MB em bytes
        if ($_FILES['arquivo']['size'] > $max_size) {
            $errors[] = 'O arquivo é muito grande. O tamanho máximo permitido é 5MB.';
        }
    }

    // Se não houver erros, salva o documento
    if (empty($errors)) {
        try {
            // Cria o diretório para armazenar os documentos
            $upload_dir = 'uploads/documentos_alunos/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // Gera um nome único para o arquivo
            $file_extension = pathinfo($_FILES['arquivo']['name'], PATHINFO_EXTENSION);
            $file_name = 'doc_' . $aluno_id . '_' . $tipo_documento_id . '_' . time() . '.' . $file_extension;
            $file_path = $upload_dir . $file_name;

            // Move o arquivo para o diretório de upload
            if (move_uploaded_file($_FILES['arquivo']['tmp_name'], $file_path)) {
                // Prepara os dados para inserção
                $dados_documento = [
                    'aluno_id' => $aluno_id,
                    'tipo_documento_id' => $tipo_documento_id,
                    'arquivo' => $file_name,
                    'data_upload' => date('Y-m-d H:i:s'),
                    'data_validade' => !empty($data_validade) ? $data_validade : null,
                    'numero_documento' => $numero_documento,
                    'orgao_emissor' => $orgao_emissor,
                    'observacoes' => $observacoes,
                    'status' => 'aprovado', // A secretaria aprova automaticamente
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                // Insere o documento no banco de dados
                $documento_id = $db->insert('documentos_alunos', $dados_documento);

                if (!$documento_id) {
                    throw new Exception("Falha ao inserir documento no banco de dados");
                }

                // Redireciona para a visualização dos documentos do aluno
                setMensagem('sucesso', 'Documento enviado com sucesso!');
                redirect('alunos_documentos.php?id=' . $aluno_id);
                exit;
            } else {
                throw new Exception("Falha ao mover o arquivo para o diretório de upload");
            }
        } catch (Exception $e) {
            $errors[] = 'Erro ao enviar documento: ' . $e->getMessage();
        }
    }
}

// Processa a aprovação ou rejeição de documento
if ($action === 'aprovar' || $action === 'rejeitar') {
    $documento_id = $_GET['documento_id'] ?? null;

    if (!$documento_id) {
        setMensagem('erro', 'ID do documento não informado.');
        redirect('alunos_documentos.php?id=' . $aluno_id);
        exit;
    }

    // Verifica se o documento existe e pertence ao aluno
    $sql = "SELECT * FROM documentos_alunos WHERE id = ? AND aluno_id = ?";
    $documento = $db->fetchOne($sql, [$documento_id, $aluno_id]);

    if (!$documento) {
        setMensagem('erro', 'Documento não encontrado ou não pertence ao aluno.');
        redirect('alunos_documentos.php?id=' . $aluno_id);
        exit;
    }

    // Atualiza o status do documento
    $status = $action === 'aprovar' ? 'aprovado' : 'rejeitado';
    $sql = "UPDATE documentos_alunos SET status = ?, updated_at = NOW() WHERE id = ?";
    $db->query($sql, [$status, $documento_id]);

    // Redireciona para a visualização dos documentos do aluno
    setMensagem('sucesso', 'Documento ' . ($status === 'aprovado' ? 'aprovado' : 'rejeitado') . ' com sucesso!');
    redirect('alunos_documentos.php?id=' . $aluno_id);
    exit;
}

// Carrega os dados conforme a ação
switch ($action) {
    case 'upload':
        // Carrega os tipos de documentos disponíveis
        $sql = "SELECT id, nome, descricao FROM tipos_documentos_pessoais WHERE status = 'ativo' ORDER BY nome";
        $tipos_documentos = $db->fetchAll($sql);

        // Define o título da página
        $titulo_pagina = 'Enviar Documento';
        break;

    default: // listar
        // Carrega os documentos do aluno
        $sql = "SELECT da.*, tdp.nome as tipo_documento_nome, tdp.obrigatorio
                FROM documentos_alunos da
                JOIN tipos_documentos_pessoais tdp ON da.tipo_documento_id = tdp.id
                WHERE da.aluno_id = ?
                ORDER BY da.created_at DESC";
        $documentos = $db->fetchAll($sql, [$aluno_id]);

        // Carrega os tipos de documentos obrigatórios que o aluno ainda não enviou
        $sql = "SELECT tdp.*
                FROM tipos_documentos_pessoais tdp
                WHERE tdp.obrigatorio = 1 AND tdp.status = 'ativo'
                AND NOT EXISTS (
                    SELECT 1 FROM documentos_alunos da
                    WHERE da.aluno_id = ? AND da.tipo_documento_id = tdp.id AND da.status != 'rejeitado'
                )
                ORDER BY tdp.nome";
        $documentos_pendentes = $db->fetchAll($sql, [$aluno_id]);

        // Define o título da página
        $titulo_pagina = 'Documentos do Aluno';
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
    <link rel="stylesheet" href="css/styles.css">
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

        .btn-success {
            background-color: #10B981;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 500;
            transition: background-color 0.2s;
        }
        .btn-success:hover { background-color: #059669; }

        .btn-danger {
            background-color: #EF4444;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 500;
            transition: background-color 0.2s;
        }
        .btn-danger:hover { background-color: #DC2626; }
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
                        <h1 class="text-2xl font-bold text-gray-800"><?php echo $titulo_pagina; ?></h1>
                        <?php if ($action === 'listar'): ?>
                        <a href="alunos_documentos.php?action=upload&id=<?php echo $aluno_id; ?>" class="btn-primary">
                            <i class="fas fa-upload mr-2"></i> Enviar Documento
                        </a>
                        <?php endif; ?>
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
                        <ul class="list-disc list-inside">
                            <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <!-- Informações do Aluno -->
                    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">Informações do Aluno</h2>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <p class="text-sm text-gray-500">Nome</p>
                                <p class="font-medium"><?php echo htmlspecialchars($aluno['nome']); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">CPF</p>
                                <p class="font-medium"><?php echo formatarCpf($aluno['cpf']); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">E-mail</p>
                                <p class="font-medium"><?php echo htmlspecialchars($aluno['email']); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Polo</p>
                                <p class="font-medium"><?php echo htmlspecialchars($polo_nome); ?></p>
                            </div>
                        </div>
                    </div>

                    <?php if ($action === 'listar'): ?>

                    <?php if (!empty($documentos_pendentes)): ?>
                    <!-- Documentos Pendentes -->
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-yellow-800">Documentos Obrigatórios Pendentes</h3>
                                <div class="mt-2 text-sm text-yellow-700">
                                    <p>O aluno possui os seguintes documentos obrigatórios pendentes:</p>
                                    <ul class="mt-2 pl-5 list-disc">
                                        <?php foreach ($documentos_pendentes as $documento): ?>
                                        <li><?php echo htmlspecialchars($documento['nome']); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                    <a href="alunos_documentos.php?action=upload&id=<?php echo $aluno_id; ?>" class="inline-flex items-center mt-2 text-blue-600 hover:text-blue-800">
                                        <i class="fas fa-upload mr-1"></i> Enviar documentos pendentes
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Lista de Documentos -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-800">Documentos do Aluno</h2>
                        </div>
                        <div class="p-6">
                            <?php if (empty($documentos)): ?>
                            <div class="text-center text-gray-500 py-4">
                                <p>Nenhum documento enviado.</p>
                                <a href="alunos_documentos.php?action=upload&id=<?php echo $aluno_id; ?>" class="btn-primary inline-block mt-4">Enviar Documento</a>
                            </div>
                            <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo de Documento</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data de Upload</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Obrigatório</th>
                                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($documentos as $documento): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($documento['tipo_documento_nome']); ?></div>
                                                <?php if (!empty($documento['numero_documento'])): ?>
                                                <div class="text-sm text-gray-500">Nº <?php echo htmlspecialchars($documento['numero_documento']); ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900"><?php echo date('d/m/Y', strtotime($documento['data_upload'])); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="badge <?php
                                                    echo $documento['status'] === 'pendente' ? 'badge-warning' :
                                                        ($documento['status'] === 'aprovado' ? 'badge-success' : 'badge-danger');
                                                ?>">
                                                    <?php
                                                        echo $documento['status'] === 'pendente' ? 'Pendente' :
                                                            ($documento['status'] === 'aprovado' ? 'Aprovado' : 'Rejeitado');
                                                    ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900"><?php echo $documento['obrigatorio'] ? 'Sim' : 'Não'; ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex flex-wrap justify-center gap-2">
                                                    <a href="uploads/documentos_alunos/<?php echo $documento['arquivo']; ?>" target="_blank" class="inline-flex items-center px-2.5 py-1.5 bg-blue-100 text-blue-800 text-xs font-medium rounded hover:bg-blue-200">
                                                        <i class="fas fa-eye mr-1"></i> Visualizar
                                                    </a>

                                                    <?php if ($documento['status'] === 'pendente'): ?>
                                                    <a href="alunos_documentos.php?action=aprovar&id=<?php echo $aluno_id; ?>&documento_id=<?php echo $documento['id']; ?>" class="inline-flex items-center px-2.5 py-1.5 bg-green-100 text-green-800 text-xs font-medium rounded hover:bg-green-200">
                                                        <i class="fas fa-check mr-1"></i> Aprovar
                                                    </a>
                                                    <a href="alunos_documentos.php?action=rejeitar&id=<?php echo $aluno_id; ?>&documento_id=<?php echo $documento['id']; ?>" class="inline-flex items-center px-2.5 py-1.5 bg-red-100 text-red-800 text-xs font-medium rounded hover:bg-red-200">
                                                        <i class="fas fa-times mr-1"></i> Rejeitar
                                                    </a>
                                                    <?php endif; ?>

                                                    <?php if ($documento['status'] === 'rejeitado'): ?>
                                                    <a href="alunos_documentos.php?action=upload&id=<?php echo $aluno_id; ?>&tipo=<?php echo $documento['tipo_documento_id']; ?>" class="inline-flex items-center px-2.5 py-1.5 bg-yellow-100 text-yellow-800 text-xs font-medium rounded hover:bg-yellow-200">
                                                        <i class="fas fa-redo mr-1"></i> Reenviar
                                                    </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php elseif ($action === 'upload'): ?>
                    <!-- Formulário de Upload de Documento -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-800">Enviar Documento</h2>
                        </div>
                        <div class="p-6">
                            <form method="post" action="alunos_documentos.php?action=upload&id=<?php echo $aluno_id; ?>" enctype="multipart/form-data">
                                <div class="mb-4">
                                    <label for="tipo_documento_id" class="block text-sm font-medium text-gray-700 mb-1">Tipo de Documento</label>
                                    <select id="tipo_documento_id" name="tipo_documento_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" required>
                                        <option value="">Selecione um tipo de documento</option>
                                        <?php foreach ($tipos_documentos as $tipo_documento): ?>
                                        <option value="<?php echo $tipo_documento['id']; ?>" <?php echo isset($_GET['tipo']) && $_GET['tipo'] == $tipo_documento['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($tipo_documento['nome']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-4">
                                    <label for="arquivo" class="block text-sm font-medium text-gray-700 mb-1">Arquivo (PDF, JPEG ou PNG, máx. 5MB)</label>
                                    <input type="file" id="arquivo" name="arquivo" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" required>
                                </div>
                                <div class="mb-4">
                                    <label for="numero_documento" class="block text-sm font-medium text-gray-700 mb-1">Número do Documento (opcional)</label>
                                    <input type="text" id="numero_documento" name="numero_documento" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                                </div>
                                <div class="mb-4">
                                    <label for="orgao_emissor" class="block text-sm font-medium text-gray-700 mb-1">Órgão Emissor (opcional)</label>
                                    <input type="text" id="orgao_emissor" name="orgao_emissor" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                                </div>
                                <div class="mb-4">
                                    <label for="data_validade" class="block text-sm font-medium text-gray-700 mb-1">Data de Validade (opcional)</label>
                                    <input type="date" id="data_validade" name="data_validade" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                                </div>
                                <div class="mb-4">
                                    <label for="observacoes" class="block text-sm font-medium text-gray-700 mb-1">Observações (opcional)</label>
                                    <textarea id="observacoes" name="observacoes" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50"></textarea>
                                </div>
                                <div class="flex justify-end">
                                    <a href="alunos_documentos.php?id=<?php echo $aluno_id; ?>" class="btn-secondary mr-2">Cancelar</a>
                                    <button type="submit" class="btn-primary">Enviar Documento</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="mt-6">
                        <a href="alunos.php?action=visualizar&id=<?php echo $aluno_id; ?>" class="btn-secondary">
                            <i class="fas fa-arrow-left mr-2"></i> Voltar para o Aluno
                        </a>
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

        // Close user menu when clicking outside
        document.addEventListener('click', function(event) {
            const menu = document.getElementById('user-menu');
            const button = document.getElementById('user-menu-button');

            if (!menu.contains(event.target) && !button.contains(event.target)) {
                menu.classList.add('hidden');
            }
        });
    </script>
</body>
</html>
