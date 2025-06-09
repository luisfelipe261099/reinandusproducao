<?php
/**
 * Página de Documentos Pessoais dos Alunos
 * Permite visualizar, aprovar e rejeitar documentos pessoais dos alunos
 */

// Inicializa o sistema
require_once 'includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica se o usuário tem permissão de secretaria
if (getUsuarioTipo() !== 'secretaria' && getUsuarioTipo() !== 'admin') {
    setMensagem('erro', 'Você não tem permissão para acessar esta página.');
    redirect('index.php');
    exit;
}

// Instancia o banco de dados
$db = Database::getInstance();

// Define a ação padrão
$action = $_GET['action'] ?? 'listar';

// Processa as ações
switch ($action) {
    case 'anexar':
        // Verifica se o ID do aluno foi informado
        if (!isset($_GET['aluno_id']) || empty($_GET['aluno_id'])) {
            setMensagem('erro', 'ID do aluno não informado.');
            redirect('documentos_pessoais.php');
            exit;
        }

        // Busca o aluno
        $aluno_id = (int)$_GET['aluno_id'];
        $sql = "SELECT * FROM alunos WHERE id = ?";
        $aluno = $db->fetchOne($sql, [$aluno_id]);

        if (!$aluno) {
            setMensagem('erro', 'Aluno não encontrado.');
            redirect('documentos_pessoais.php');
            exit;
        }

        // Busca os tipos de documentos pessoais
        $sql = "SELECT * FROM tipos_documentos_pessoais WHERE status = 'ativo' ORDER BY nome";
        $tipos_documentos = $db->fetchAll($sql);

        // Define o título da página
        $titulo_pagina = 'Anexar Documento para ' . $aluno['nome'];
        break;

    case 'salvar_documento':
        // Verifica se o formulário foi enviado
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            setMensagem('erro', 'Método inválido.');
            redirect('documentos_pessoais.php');
            exit;
        }

        // Verifica se o ID do aluno foi informado
        if (!isset($_POST['aluno_id']) || empty($_POST['aluno_id'])) {
            setMensagem('erro', 'ID do aluno não informado.');
            redirect('documentos_pessoais.php');
            exit;
        }

        // Verifica se o tipo de documento foi selecionado
        if (!isset($_POST['tipo_documento_id']) || empty($_POST['tipo_documento_id'])) {
            setMensagem('erro', 'Selecione o tipo de documento.');
            redirect('documentos_pessoais.php?action=anexar&aluno_id=' . $_POST['aluno_id']);
            exit;
        }

        // Verifica se um arquivo foi enviado
        if (!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
            setMensagem('erro', 'Selecione um arquivo para upload.');
            redirect('documentos_pessoais.php?action=anexar&aluno_id=' . $_POST['aluno_id']);
            exit;
        }

        $aluno_id = (int)$_POST['aluno_id'];
        $tipo_documento_id = (int)$_POST['tipo_documento_id'];
        $observacoes = $_POST['observacoes'] ?? '';

        // Verifica o tipo de arquivo
        $arquivo = $_FILES['arquivo'];
        $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
        $extensoes_permitidas = ['pdf', 'jpg', 'jpeg', 'png'];

        if (!in_array($extensao, $extensoes_permitidas)) {
            setMensagem('erro', 'Tipo de arquivo não permitido. Envie apenas PDF, JPG, JPEG ou PNG.');
            redirect('documentos_pessoais.php?action=anexar&aluno_id=' . $aluno_id);
            exit;
        }

        // Verifica o tamanho do arquivo (máximo 5MB)
        $tamanho_maximo = 5 * 1024 * 1024; // 5MB em bytes
        if ($arquivo['size'] > $tamanho_maximo) {
            setMensagem('erro', 'O arquivo é muito grande. O tamanho máximo permitido é 5MB.');
            redirect('documentos_pessoais.php?action=anexar&aluno_id=' . $aluno_id);
            exit;
        }

        // Cria o diretório de upload se não existir
        $diretorio_upload = 'uploads/documentos_alunos/';
        if (!file_exists($diretorio_upload)) {
            mkdir($diretorio_upload, 0777, true);
        }

        // Gera um nome único para o arquivo
        $nome_arquivo = 'doc_' . $aluno_id . '_' . $tipo_documento_id . '_' . time() . '.' . $extensao;
        $caminho_arquivo = $diretorio_upload . $nome_arquivo;

        // Move o arquivo para o diretório de upload
        if (!move_uploaded_file($arquivo['tmp_name'], $caminho_arquivo)) {
            setMensagem('erro', 'Erro ao fazer upload do arquivo. Tente novamente.');
            redirect('documentos_pessoais.php?action=anexar&aluno_id=' . $aluno_id);
            exit;
        }

        // Salva o documento no banco de dados
        $sql = "INSERT INTO documentos_alunos (aluno_id, tipo_documento_id, arquivo, data_upload, status, observacoes, created_at, updated_at)
                VALUES (?, ?, ?, NOW(), 'pendente', ?, NOW(), NOW())";
        $db->query($sql, [$aluno_id, $tipo_documento_id, $nome_arquivo, $observacoes]);

        setMensagem('sucesso', 'Documento anexado com sucesso.');
        redirect('documentos_pessoais.php?aluno_id=' . $aluno_id);
        break;

    case 'aprovar':
        // Verifica se o ID foi informado
        if (!isset($_GET['id']) || empty($_GET['id'])) {
            setMensagem('erro', 'ID do documento não informado.');
            redirect('documentos_pessoais.php');
            exit;
        }

        // Busca o documento
        $id = (int)$_GET['id'];
        $sql = "SELECT * FROM documentos_alunos WHERE id = ?";
        $documento = $db->fetchOne($sql, [$id]);

        if (!$documento) {
            setMensagem('erro', 'Documento não encontrado.');
            redirect('documentos_pessoais.php');
            exit;
        }

        // Atualiza o status do documento
        $sql = "UPDATE documentos_alunos SET status = 'aprovado', updated_at = NOW() WHERE id = ?";
        $db->query($sql, [$id]);

        setMensagem('sucesso', 'Documento aprovado com sucesso.');
        redirect('documentos_pessoais.php?aluno_id=' . $documento['aluno_id']);
        break;

    case 'rejeitar':
        // Verifica se o ID foi informado
        if (!isset($_GET['id']) || empty($_GET['id'])) {
            setMensagem('erro', 'ID do documento não informado.');
            redirect('documentos_pessoais.php');
            exit;
        }

        // Busca o documento
        $id = (int)$_GET['id'];
        $sql = "SELECT * FROM documentos_alunos WHERE id = ?";
        $documento = $db->fetchOne($sql, [$id]);

        if (!$documento) {
            setMensagem('erro', 'Documento não encontrado.');
            redirect('documentos_pessoais.php');
            exit;
        }

        // Atualiza o status do documento
        $sql = "UPDATE documentos_alunos SET status = 'rejeitado', updated_at = NOW() WHERE id = ?";
        $db->query($sql, [$id]);

        setMensagem('sucesso', 'Documento rejeitado com sucesso.');
        redirect('documentos_pessoais.php?aluno_id=' . $documento['aluno_id']);
        break;

    case 'listar':
    default:
        // Verifica se há um filtro por aluno
        $aluno_id = $_GET['aluno_id'] ?? null;

        if ($aluno_id) {
            // Busca o aluno
            $sql = "SELECT * FROM alunos WHERE id = ?";
            $aluno = $db->fetchOne($sql, [$aluno_id]);

            if (!$aluno) {
                setMensagem('erro', 'Aluno não encontrado.');
                redirect('alunos.php');
                exit;
            }

            // Busca os documentos do aluno
            $sql = "SELECT da.*, tda.nome as tipo_documento_nome, tda.descricao as tipo_documento_descricao
                    FROM documentos_alunos da
                    JOIN tipos_documentos_pessoais tda ON da.tipo_documento_id = tda.id
                    WHERE da.aluno_id = ?
                    ORDER BY da.status, da.updated_at DESC";
            $documentos = $db->fetchAll($sql, [$aluno_id]);

            // Define o título da página
            $titulo_pagina = 'Documentos Pessoais de ' . $aluno['nome'];
        } else {
            // Busca todos os documentos pendentes
            $sql = "SELECT da.*, tda.nome as tipo_documento_nome, a.nome as aluno_nome, a.id as aluno_id
                    FROM documentos_alunos da
                    JOIN tipos_documentos_pessoais tda ON da.tipo_documento_id = tda.id
                    JOIN alunos a ON da.aluno_id = a.id
                    WHERE da.status = 'pendente'
                    ORDER BY da.created_at DESC";
            $documentos = $db->fetchAll($sql);

            // Define o título da página
            $titulo_pagina = 'Documentos Pessoais Pendentes';
        }
        break;
}

// Define o título completo da página
$titulo_pagina_completo = 'Faciência ERP - ' . $titulo_pagina;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo_pagina_completo; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 9999px;
        }
        .status-pendente { background-color: #FEF3C7; color: #D97706; }
        .status-aprovado { background-color: #D1FAE5; color: #059669; }
        .status-rejeitado { background-color: #FEE2E2; color: #DC2626; }

        .document-card {
            border: 1px solid #E5E7EB;
            border-radius: 0.5rem;
            overflow: hidden;
            transition: all 0.2s;
        }
        .document-card:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .document-preview {
            height: 200px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #F3F4F6;
        }
        .document-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        .document-preview .pdf-icon {
            font-size: 4rem;
            color: #EF4444;
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
                        <h1 class="text-2xl font-bold text-gray-800"><?php echo $titulo_pagina; ?></h1>
                        <?php if (isset($aluno_id) && $aluno_id): ?>
                        <div class="flex space-x-2">
                            <a href="documentos_pessoais.php?action=anexar&aluno_id=<?php echo $aluno_id; ?>" class="btn-primary">
                                <i class="fas fa-plus mr-2"></i> Anexar Documento
                            </a>
                            <a href="alunos.php?action=visualizar&id=<?php echo $aluno_id; ?>" class="btn-secondary">
                                <i class="fas fa-arrow-left mr-2"></i> Voltar para o Aluno
                            </a>
                        </div>
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

                    <?php if ($action === 'anexar'): ?>
                    <!-- Formulário para anexar documento -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-800">Anexar Novo Documento</h2>
                        </div>
                        <div class="p-6">
                            <form action="documentos_pessoais.php?action=salvar_documento" method="post" enctype="multipart/form-data" class="space-y-6">
                                <input type="hidden" name="aluno_id" value="<?php echo $aluno_id; ?>">

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="tipo_documento_id" class="block text-sm font-medium text-gray-700 mb-1">Tipo de Documento*</label>
                                        <select id="tipo_documento_id" name="tipo_documento_id" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                                            <option value="">Selecione o tipo de documento</option>
                                            <?php foreach ($tipos_documentos as $tipo): ?>
                                            <option value="<?php echo $tipo['id']; ?>"><?php echo htmlspecialchars($tipo['nome']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div>
                                        <label for="arquivo" class="block text-sm font-medium text-gray-700 mb-1">Arquivo*</label>
                                        <input type="file" id="arquivo" name="arquivo" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" accept=".pdf,.jpg,.jpeg,.png">
                                        <p class="mt-1 text-xs text-gray-500">Formatos aceitos: PDF, JPG, JPEG, PNG. Tamanho máximo: 5MB.</p>
                                    </div>
                                </div>

                                <div>
                                    <label for="observacoes" class="block text-sm font-medium text-gray-700 mb-1">Observações (opcional)</label>
                                    <textarea id="observacoes" name="observacoes" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50"></textarea>
                                </div>

                                <div class="flex justify-end space-x-2">
                                    <a href="documentos_pessoais.php?aluno_id=<?php echo $aluno_id; ?>" class="btn-secondary">Cancelar</a>
                                    <button type="submit" class="btn-primary">Anexar Documento</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php elseif (empty($documentos)): ?>
                    <div class="bg-white rounded-xl shadow-sm p-6 text-center">
                        <p class="text-gray-500">Nenhum documento encontrado.</p>
                        <?php if (isset($aluno_id) && $aluno_id): ?>
                        <p class="mt-2 text-sm text-gray-500">O aluno ainda não possui documentos pessoais anexados.</p>
                        <div class="mt-4">
                            <a href="documentos_pessoais.php?action=anexar&aluno_id=<?php echo $aluno_id; ?>" class="btn-primary">
                                <i class="fas fa-plus mr-2"></i> Anexar Documento
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>

                    <?php if (!isset($aluno_id)): ?>
                    <!-- Lista de documentos pendentes de todos os alunos -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-800">Documentos Pendentes de Aprovação</h2>
                        </div>
                        <div class="p-6">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aluno</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Documento</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data de Envio</th>
                                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($documentos as $documento): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <a href="alunos.php?action=visualizar&id=<?php echo $documento['aluno_id']; ?>" class="hover:text-blue-600">
                                                        <?php echo htmlspecialchars($documento['aluno_nome']); ?>
                                                    </a>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($documento['tipo_documento_nome']); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900"><?php echo date('d/m/Y H:i', strtotime($documento['data_upload'])); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex flex-wrap justify-center gap-2">
                                                    <a href="documentos_pessoais.php?aluno_id=<?php echo $documento['aluno_id']; ?>" class="inline-flex items-center px-2.5 py-1.5 bg-blue-100 text-blue-800 text-xs font-medium rounded hover:bg-blue-200">
                                                        <i class="fas fa-eye mr-1"></i> Ver Todos
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <!-- Documentos de um aluno específico -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-800">Informações do Aluno</h2>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <p class="text-sm text-gray-500">Nome</p>
                                    <p class="font-medium"><?php echo htmlspecialchars($aluno['nome']); ?></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">CPF</p>
                                    <p class="font-medium"><?php echo formatarCpf($aluno['cpf']); ?></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Email</p>
                                    <p class="font-medium"><?php echo htmlspecialchars($aluno['email']); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($documentos as $documento): ?>
                        <div class="document-card bg-white">
                            <div class="document-preview">
                                <?php
                                $arquivo = $documento['arquivo'];
                                $caminho_arquivo = 'uploads/documentos_alunos/' . $arquivo;
                                $extensao = pathinfo($arquivo, PATHINFO_EXTENSION);

                                if (file_exists($caminho_arquivo)) {
                                    if (in_array(strtolower($extensao), ['jpg', 'jpeg', 'png'])) {
                                        echo '<img src="' . $caminho_arquivo . '" alt="Documento">';
                                    } else {
                                        echo '<div class="pdf-icon"><i class="far fa-file-pdf"></i></div>';
                                    }
                                } else {
                                    echo '<div class="text-gray-400"><i class="fas fa-exclamation-circle mr-2"></i> Arquivo não encontrado</div>';
                                }
                                ?>
                            </div>
                            <div class="p-4">
                                <div class="flex justify-between items-start mb-2">
                                    <h3 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($documento['tipo_documento_nome']); ?></h3>
                                    <span class="status-badge status-<?php echo $documento['status']; ?>">
                                        <?php
                                        if ($documento['status'] === 'pendente') echo 'Pendente';
                                        elseif ($documento['status'] === 'aprovado') echo 'Aprovado';
                                        elseif ($documento['status'] === 'rejeitado') echo 'Rejeitado';
                                        ?>
                                    </span>
                                </div>
                                <p class="text-sm text-gray-500 mb-4">
                                    Enviado em <?php echo date('d/m/Y H:i', strtotime($documento['data_upload'])); ?>
                                </p>

                                <div class="flex space-x-2">
                                    <a href="<?php echo $caminho_arquivo; ?>" target="_blank" class="flex-1 inline-flex justify-center items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                        <i class="fas fa-external-link-alt mr-2"></i> Abrir
                                    </a>

                                    <?php if ($documento['status'] === 'pendente'): ?>
                                    <a href="documentos_pessoais.php?action=aprovar&id=<?php echo $documento['id']; ?>" class="flex-1 inline-flex justify-center items-center px-3 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                                        <i class="fas fa-check mr-2"></i> Aprovar
                                    </a>
                                    <a href="documentos_pessoais.php?action=rejeitar&id=<?php echo $documento['id']; ?>" class="flex-1 inline-flex justify-center items-center px-3 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700">
                                        <i class="fas fa-times mr-2"></i> Rejeitar
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <?php endif; ?>
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
