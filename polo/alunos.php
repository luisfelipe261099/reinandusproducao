<?php
/**
 * Gerenciamento de Alunos do Polo
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

// Carrega os dados conforme a ação
switch ($action) {
    case 'documentos':
        // Redireciona para a página de documentos do aluno
        $aluno_id = $_GET['id'] ?? 0;

        // Verifica se o aluno pertence ao polo
        $sql = "SELECT id FROM alunos WHERE id = ? AND (polo_id = ? OR EXISTS (SELECT 1 FROM matriculas WHERE aluno_id = ? AND polo_id = ?))";
        $aluno = $db->fetchOne($sql, [$aluno_id, $polo_id, $aluno_id, $polo_id]);

        if (!$aluno) {
            setMensagem('erro', 'Aluno não encontrado ou não pertence ao seu polo.');
            redirect('alunos.php');
            exit;
        }

        // Redireciona para a página de documentos
        redirect('alunos_documentos.php?id=' . $aluno_id);
        exit;

    case 'atualizar':
        if (isPost()) {
            $aluno_id = $_GET['id'] ?? 0;

            // Verifica se o aluno pertence ao polo
            $sql = "SELECT id FROM alunos WHERE id = ? AND (polo_id = ? OR EXISTS (SELECT 1 FROM matriculas WHERE aluno_id = ? AND polo_id = ?))";
            $aluno = $db->fetchOne($sql, [$aluno_id, $polo_id, $aluno_id, $polo_id]);

            if (!$aluno) {
                setMensagem('erro', 'Aluno não encontrado ou não pertence ao seu polo.');
                redirect('alunos.php');
                exit;
            }

            // Obtém os dados do formulário
            $nome = $_POST['nome'] ?? '';
            $cpf = $_POST['cpf'] ?? '';
            $email = $_POST['email'] ?? '';
            $telefone = $_POST['telefone'] ?? '';
            $data_nascimento = $_POST['data_nascimento'] ?? '';
            $curso_id = $_POST['curso_id'] ?? null;
            $endereco = $_POST['endereco'] ?? '';
            $numero = $_POST['numero'] ?? '';
            $complemento = $_POST['complemento'] ?? '';
            $bairro = $_POST['bairro'] ?? '';
            $cidade = $_POST['cidade'] ?? '';
            $estado = $_POST['estado'] ?? '';
            $cep = $_POST['cep'] ?? '';
            $status = $_POST['status'] ?? 'ativo';

            // Validação básica
            $errors = [];

            if (empty($nome)) {
                $errors[] = 'O nome é obrigatório';
            }

            if (empty($cpf)) {
                $errors[] = 'O CPF é obrigatório';
            } else {
                // Remove caracteres não numéricos
                $cpf = preg_replace('/[^0-9]/', '', $cpf);

                // Verifica se o CPF já está cadastrado para outro aluno
                $sql = "SELECT id FROM alunos WHERE cpf = ? AND id != ?";
                $aluno_existente = $db->fetchOne($sql, [$cpf, $aluno_id]);

                if ($aluno_existente) {
                    $errors[] = 'Este CPF já está cadastrado para outro aluno';
                }
            }

            if (empty($email)) {
                $errors[] = 'O e-mail é obrigatório';
            } else {
                // Verifica se o e-mail já está cadastrado para outro aluno
                $sql = "SELECT id FROM alunos WHERE email = ? AND id != ?";
                $aluno_existente = $db->fetchOne($sql, [$email, $aluno_id]);

                if ($aluno_existente) {
                    $errors[] = 'Este e-mail já está cadastrado para outro aluno';
                }
            }

            // Se não houver erros, atualiza o aluno
            if (empty($errors)) {
                try {
                    // Prepara os dados para atualização
                    $dados_aluno = [
                        'nome' => $nome,
                        'cpf' => $cpf,
                        'email' => $email,
                        'telefone' => $telefone,
                        'data_nascimento' => !empty($data_nascimento) ? $data_nascimento : null,
                        'curso_id' => !empty($curso_id) ? $curso_id : null,
                        'endereco' => $endereco,
                        'numero' => $numero,
                        'complemento' => $complemento,
                        'bairro' => $bairro,
                        'cidade' => $cidade,
                        'estado' => $estado,
                        'cep' => $cep,
                        'status' => $status,
                        'updated_at' => date('Y-m-d H:i:s')
                    ];

                    // Atualiza o aluno no banco de dados
                    $db->update('alunos', $dados_aluno, ['id' => $aluno_id]);

                    // Redireciona para a visualização do aluno
                    setMensagem('sucesso', 'Aluno atualizado com sucesso!');
                    redirect('alunos.php?action=visualizar&id=' . $aluno_id);
                    exit;
                } catch (Exception $e) {
                    $errors[] = 'Erro ao atualizar aluno: ' . $e->getMessage();
                }
            }

            // Se houver erros, exibe-os e redireciona para a página de edição
            if (!empty($errors)) {
                $_SESSION['mensagem'] = implode('<br>', $errors);
                $_SESSION['mensagem_tipo'] = 'erro';
                redirect('alunos.php?action=editar&id=' . $aluno_id);
                exit;
            }
        } else {
            // Se não for um POST, redireciona para a lista de alunos
            redirect('alunos.php');
            exit;
        }
        break;

    case 'editar':
        $aluno_id = $_GET['id'] ?? 0;

        // Carrega os dados do aluno
        $sql = "SELECT a.*,
                       COALESCE(c1.nome, c2.nome) as curso_nome,
                       t.nome as turma_nome
                FROM alunos a
                LEFT JOIN matriculas m ON a.id = m.aluno_id AND (m.polo_id = ? OR a.polo_id = ?)
                LEFT JOIN cursos c1 ON m.curso_id = c1.id
                LEFT JOIN cursos c2 ON a.curso_id = c2.id
                LEFT JOIN turmas t ON m.turma_id = t.id
                WHERE a.id = ? AND (m.polo_id = ? OR a.polo_id = ?)
                LIMIT 1";
        $aluno = $db->fetchOne($sql, [$polo_id, $polo_id, $aluno_id, $polo_id, $polo_id]);

        if (!$aluno) {
            setMensagem('erro', 'Aluno não encontrado ou não pertence ao seu polo.');
            redirect('alunos.php');
            exit;
        }

        // Carrega os cursos disponíveis
        $sql = "SELECT id, nome FROM cursos WHERE status = 'ativo' ORDER BY nome";
        $cursos = $db->fetchAll($sql);

        // Define o título da página
        $titulo_pagina = 'Editar Aluno';
        break;

    case 'visualizar':
        $aluno_id = $_GET['id'] ?? 0;

        // Carrega os dados do aluno
        $sql = "SELECT a.*,
                       COALESCE(c1.nome, c2.nome) as curso_nome,
                       t.nome as turma_nome
                FROM alunos a
                LEFT JOIN matriculas m ON a.id = m.aluno_id AND (m.polo_id = ? OR a.polo_id = ?)
                LEFT JOIN cursos c1 ON m.curso_id = c1.id
                LEFT JOIN cursos c2 ON a.curso_id = c2.id
                LEFT JOIN turmas t ON m.turma_id = t.id
                WHERE a.id = ? AND (m.polo_id = ? OR a.polo_id = ?)
                LIMIT 1";
        $aluno = $db->fetchOne($sql, [$polo_id, $polo_id, $aluno_id, $polo_id, $polo_id]);

        if (!$aluno) {
            setMensagem('erro', 'Aluno não encontrado ou não pertence ao seu polo.');
            redirect('alunos.php');
            exit;
        }

        // Carrega as matrículas do aluno
        $sql = "SELECT m.*, c.nome as curso_nome, t.nome as turma_nome
                FROM matriculas m
                LEFT JOIN cursos c ON m.curso_id = c.id
                LEFT JOIN turmas t ON m.turma_id = t.id
                WHERE m.aluno_id = ? AND m.polo_id = ?
                ORDER BY m.data_matricula DESC";
        $matriculas = $db->fetchAll($sql, [$aluno_id, $polo_id]);

        // Define o título da página
        $titulo_pagina = 'Visualizar Aluno';
        break;

    default: // listar
        // Parâmetros de busca
        $busca = $_GET['busca'] ?? '';
        $status = $_GET['status'] ?? 'ativo';
        $pagina = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
        $por_pagina = 5; // Número de alunos por página

        // Calcula o offset para a consulta SQL
        $offset = ($pagina - 1) * $por_pagina;

        // Monta a consulta SQL para contar o total de alunos
        $sql_count = "SELECT COUNT(DISTINCT a.id) as total
                FROM alunos a
                LEFT JOIN matriculas m ON a.id = m.aluno_id AND m.polo_id = ?
                WHERE (m.polo_id = ? OR a.polo_id = ?)";

        $params_count = [$polo_id, $polo_id, $polo_id];

        if (!empty($busca)) {
            $sql_count .= " AND (a.nome LIKE ? OR a.cpf LIKE ? OR a.email LIKE ?)";
            $busca_param = "%{$busca}%";
            $params_count[] = $busca_param;
            $params_count[] = $busca_param;
            $params_count[] = $busca_param;
        }

        if ($status !== 'todos') {
            $sql_count .= " AND a.status = ?";
            $params_count[] = $status;
        }

        // Executa a consulta de contagem
        $result_count = $db->fetchOne($sql_count, $params_count);
        $total_alunos = $result_count['total'] ?? 0;

        // Calcula o total de páginas
        $total_paginas = ceil($total_alunos / $por_pagina);

        // Ajusta a página atual se necessário
        if ($pagina < 1) $pagina = 1;
        if ($pagina > $total_paginas && $total_paginas > 0) $pagina = $total_paginas;

        // Monta a consulta SQL para buscar alunos tanto pela tabela matriculas quanto diretamente
        $sql = "SELECT DISTINCT a.id, a.nome, a.cpf, a.email, a.telefone, a.status, a.data_ingresso, a.curso_id,
                       COALESCE(c1.nome, c2.nome) as curso_nome,
                       CASE
                           WHEN m.id IS NOT NULL THEN 'matricula'
                           WHEN a.polo_id = ? THEN 'direto'
                           ELSE 'desconhecido'
                       END as origem
                FROM alunos a
                LEFT JOIN matriculas m ON a.id = m.aluno_id AND m.polo_id = ?
                LEFT JOIN cursos c1 ON m.curso_id = c1.id
                LEFT JOIN cursos c2 ON a.curso_id = c2.id
                WHERE (m.polo_id = ? OR a.polo_id = ?)";

        $params = [$polo_id, $polo_id, $polo_id, $polo_id];

        if (!empty($busca)) {
            $sql .= " AND (a.nome LIKE ? OR a.cpf LIKE ? OR a.email LIKE ?)";
            $busca_param = "%{$busca}%";
            $params[] = $busca_param;
            $params[] = $busca_param;
            $params[] = $busca_param;
        }

        if ($status !== 'todos') {
            $sql .= " AND a.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY a.nome LIMIT ? OFFSET ?";
        $params[] = $por_pagina;
        $params[] = $offset;

        // Executa a consulta
        $alunos = $db->fetchAll($sql, $params);

        // Define o título da página
        $titulo_pagina = 'Gerenciar Alunos';
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
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <?php include 'includes/header.php'; ?>

            <!-- Main -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
                <div class="container mx-auto">
                    <div class="flex justify-between items-center mb-6">
                        <h1 class="text-2xl font-bold text-gray-800"><?php echo $titulo_pagina; ?></h1>
                        <?php if ($action === 'listar'): ?>
                        <a href="matriculas.php?action=nova" class="btn-primary">
                            <i class="fas fa-user-plus mr-2"></i> Matricular Novo Aluno
                        </a>
                        <?php endif; ?>
                    </div>

                    <?php if (isset($_SESSION['mensagem'])): ?>
                    <div class="bg-<?php echo $_SESSION['mensagem_tipo'] === 'sucesso' ? 'green' : 'red'; ?>-100 border-l-4 border-<?php echo $_SESSION['mensagem_tipo'] === 'sucesso' ? 'green' : 'red'; ?>-500 text-<?php echo $_SESSION['mensagem_tipo'] === 'sucesso' ? 'green' : 'red'; ?>-700 p-4 mb-6">
                        <?php
                        // Verifica se a mensagem é um array e converte para string se necessário
                        if (is_array($_SESSION['mensagem'])) {
                            echo "Mensagem do sistema: " . print_r($_SESSION['mensagem'], true);
                        } else {
                            echo $_SESSION['mensagem'];
                        }
                        ?>
                    </div>
                    <?php
                    // Limpa a mensagem da sessão
                    unset($_SESSION['mensagem']);
                    unset($_SESSION['mensagem_tipo']);
                    endif;
                    ?>

                    <?php if ($action === 'listar'): ?>
                    <!-- Filtros de Busca -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-800">Filtros</h2>
                        </div>
                        <div class="p-6">
                            <form method="get" action="alunos.php">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label for="busca" class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                                        <input type="text" id="busca" name="busca" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" placeholder="Nome, CPF ou e-mail" value="<?php echo htmlspecialchars($busca); ?>">
                                    </div>
                                    <div>
                                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                        <select id="status" name="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                                            <option value="ativo" <?php echo $status === 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                                            <option value="inativo" <?php echo $status === 'inativo' ? 'selected' : ''; ?>>Inativo</option>
                                            <option value="trancado" <?php echo $status === 'trancado' ? 'selected' : ''; ?>>Trancado</option>
                                            <option value="todos" <?php echo $status === 'todos' ? 'selected' : ''; ?>>Todos</option>
                                        </select>
                                    </div>
                                    <div class="flex items-end">
                                        <button type="submit" class="btn-primary">
                                            <i class="fas fa-search mr-2"></i> Buscar
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Lista de Alunos -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-800">Alunos do Polo</h2>
                        </div>
                        <div class="p-6">
                            <?php if (empty($alunos)): ?>
                            <div class="text-center text-gray-500 py-4">
                                <p>Nenhum aluno encontrado com os filtros selecionados.</p>
                            </div>
                            <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CPF</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contato</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Curso</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($alunos as $aluno): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($aluno['nome']); ?></div>
                                                <div class="text-xs text-gray-500">
                                                    <?php if (!empty($aluno['data_ingresso'])): ?>
                                                        Desde <?php echo date('d/m/Y', strtotime($aluno['data_ingresso'])); ?>
                                                    <?php else: ?>
                                                        Data de ingresso não informada
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900"><?php echo formatarCpf($aluno['cpf']); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($aluno['email']); ?></div>
                                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($aluno['telefone'] ?? 'Não informado'); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($aluno['curso_nome'] ?? 'Não definido'); ?></div>
                                                <div class="text-xs text-gray-500">
                                                    <?php
                                                        if (isset($aluno['origem'])) {
                                                            if ($aluno['origem'] === 'matricula') {
                                                                echo "Via matrícula";
                                                            } elseif ($aluno['origem'] === 'direto') {
                                                                echo "Vinculado diretamente";
                                                            }
                                                        }
                                                    ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="badge <?php
                                                    echo $aluno['status'] === 'ativo' ? 'badge-success' :
                                                        ($aluno['status'] === 'inativo' ? 'badge-danger' :
                                                        ($aluno['status'] === 'trancado' ? 'badge-warning' : 'badge-secondary'));
                                                ?>">
                                                    <?php
                                                        echo $aluno['status'] === 'ativo' ? 'Ativo' :
                                                            ($aluno['status'] === 'inativo' ? 'Inativo' :
                                                            ($aluno['status'] === 'trancado' ? 'Trancado' :
                                                            ucfirst($aluno['status'])));
                                                    ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="flex flex-wrap justify-center gap-2">
                                                    <a href="alunos.php?action=visualizar&id=<?php echo $aluno['id']; ?>" class="inline-flex items-center px-2.5 py-1.5 bg-blue-100 text-blue-800 text-xs font-medium rounded hover:bg-blue-200">
                                                        <i class="fas fa-eye mr-1"></i> Ver
                                                    </a>
                                                    <a href="alunos.php?action=editar&id=<?php echo $aluno['id']; ?>" class="inline-flex items-center px-2.5 py-1.5 bg-yellow-100 text-yellow-800 text-xs font-medium rounded hover:bg-yellow-200">
                                                        <i class="fas fa-edit mr-1"></i> Editar
                                                    </a>
                                                    <a href="alunos_documentos.php?id=<?php echo $aluno['id']; ?>" class="inline-flex items-center px-2.5 py-1.5 bg-purple-100 text-purple-800 text-xs font-medium rounded hover:bg-purple-200">
                                                        <i class="fas fa-file-alt mr-1"></i> Docs
                                                    </a>
                                                    <a href="documentos.php?action=solicitar&aluno_id=<?php echo $aluno['id']; ?>" class="inline-flex items-center px-2.5 py-1.5 bg-green-100 text-green-800 text-xs font-medium rounded hover:bg-green-200">
                                                        <i class="fas fa-file-download mr-1"></i> Solicitar
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>

                            <?php if ($total_paginas > 1): ?>
                            <!-- Paginação -->
                            <div class="mt-6 flex justify-center">
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Paginação">
                                    <?php
                                    // Link para a página anterior
                                    if ($pagina > 1):
                                        $prev_url = "alunos.php?pagina=" . ($pagina - 1);
                                        if (!empty($busca)) $prev_url .= "&busca=" . urlencode($busca);
                                        if ($status !== 'ativo') $prev_url .= "&status=" . urlencode($status);
                                    ?>
                                    <a href="<?php echo $prev_url; ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                        <span class="sr-only">Anterior</span>
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                    <?php else: ?>
                                    <span class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400 cursor-not-allowed">
                                        <span class="sr-only">Anterior</span>
                                        <i class="fas fa-chevron-left"></i>
                                    </span>
                                    <?php endif; ?>

                                    <?php
                                    // Determina quais páginas mostrar
                                    $start_page = max(1, $pagina - 2);
                                    $end_page = min($total_paginas, $start_page + 4);

                                    if ($end_page - $start_page < 4) {
                                        $start_page = max(1, $end_page - 4);
                                    }

                                    // Links para as páginas
                                    for ($i = $start_page; $i <= $end_page; $i++):
                                        $page_url = "alunos.php?pagina=" . $i;
                                        if (!empty($busca)) $page_url .= "&busca=" . urlencode($busca);
                                        if ($status !== 'ativo') $page_url .= "&status=" . urlencode($status);

                                        if ($i == $pagina):
                                    ?>
                                    <span class="relative inline-flex items-center px-4 py-2 border border-blue-500 bg-blue-50 text-sm font-medium text-blue-600">
                                        <?php echo $i; ?>
                                    </span>
                                    <?php else: ?>
                                    <a href="<?php echo $page_url; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                                        <?php echo $i; ?>
                                    </a>
                                    <?php endif; endfor; ?>

                                    <?php
                                    // Link para a próxima página
                                    if ($pagina < $total_paginas):
                                        $next_url = "alunos.php?pagina=" . ($pagina + 1);
                                        if (!empty($busca)) $next_url .= "&busca=" . urlencode($busca);
                                        if ($status !== 'ativo') $next_url .= "&status=" . urlencode($status);
                                    ?>
                                    <a href="<?php echo $next_url; ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                        <span class="sr-only">Próxima</span>
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                    <?php else: ?>
                                    <span class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400 cursor-not-allowed">
                                        <span class="sr-only">Próxima</span>
                                        <i class="fas fa-chevron-right"></i>
                                    </span>
                                    <?php endif; ?>
                                </nav>
                            </div>

                            <div class="mt-2 text-center text-sm text-gray-500">
                                Mostrando <?php echo count($alunos); ?> de <?php echo $total_alunos; ?> alunos
                                (Página <?php echo $pagina; ?> de <?php echo $total_paginas; ?>)
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php elseif ($action === 'editar' && isset($aluno)): ?>
                    <!-- Edição de Aluno -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-800">Editar Aluno</h2>
                        </div>
                        <div class="p-6">
                            <form method="post" action="alunos.php?action=atualizar&id=<?php echo $aluno['id']; ?>">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="nome" class="block text-sm font-medium text-gray-700 mb-1">Nome Completo</label>
                                        <input type="text" id="nome" name="nome" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" value="<?php echo htmlspecialchars($aluno['nome']); ?>" required>
                                    </div>
                                    <div>
                                        <label for="cpf" class="block text-sm font-medium text-gray-700 mb-1">CPF</label>
                                        <input type="text" id="cpf" name="cpf" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" value="<?php echo htmlspecialchars($aluno['cpf']); ?>" required>
                                    </div>
                                    <div>
                                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                                        <input type="email" id="email" name="email" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" value="<?php echo htmlspecialchars($aluno['email']); ?>" required>
                                    </div>
                                    <div>
                                        <label for="telefone" class="block text-sm font-medium text-gray-700 mb-1">Telefone</label>
                                        <input type="text" id="telefone" name="telefone" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" value="<?php echo htmlspecialchars($aluno['telefone'] ?? ''); ?>">
                                    </div>
                                    <div>
                                        <label for="data_nascimento" class="block text-sm font-medium text-gray-700 mb-1">Data de Nascimento</label>
                                        <input type="date" id="data_nascimento" name="data_nascimento" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" value="<?php echo !empty($aluno['data_nascimento']) ? date('Y-m-d', strtotime($aluno['data_nascimento'])) : ''; ?>">
                                    </div>
                                    <div>
                                        <label for="curso_id" class="block text-sm font-medium text-gray-700 mb-1">Curso</label>
                                        <select id="curso_id" name="curso_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                                            <option value="">Selecione um curso</option>
                                            <?php foreach ($cursos as $curso): ?>
                                            <option value="<?php echo $curso['id']; ?>" <?php echo $aluno['curso_id'] == $curso['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($curso['nome']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="endereco" class="block text-sm font-medium text-gray-700 mb-1">Endereço</label>
                                        <input type="text" id="endereco" name="endereco" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" value="<?php echo htmlspecialchars($aluno['endereco'] ?? ''); ?>">
                                    </div>
                                    <div>
                                        <label for="numero" class="block text-sm font-medium text-gray-700 mb-1">Número</label>
                                        <input type="text" id="numero" name="numero" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" value="<?php echo htmlspecialchars($aluno['numero'] ?? ''); ?>">
                                    </div>
                                    <div>
                                        <label for="complemento" class="block text-sm font-medium text-gray-700 mb-1">Complemento</label>
                                        <input type="text" id="complemento" name="complemento" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" value="<?php echo htmlspecialchars($aluno['complemento'] ?? ''); ?>">
                                    </div>
                                    <div>
                                        <label for="bairro" class="block text-sm font-medium text-gray-700 mb-1">Bairro</label>
                                        <input type="text" id="bairro" name="bairro" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" value="<?php echo htmlspecialchars($aluno['bairro'] ?? ''); ?>">
                                    </div>
                                    <div>
                                        <label for="cidade" class="block text-sm font-medium text-gray-700 mb-1">Cidade</label>
                                        <input type="text" id="cidade" name="cidade" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" value="<?php echo htmlspecialchars($aluno['cidade'] ?? ''); ?>">
                                    </div>
                                    <div>
                                        <label for="estado" class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                                        <input type="text" id="estado" name="estado" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" value="<?php echo htmlspecialchars($aluno['estado'] ?? ''); ?>">
                                    </div>
                                    <div>
                                        <label for="cep" class="block text-sm font-medium text-gray-700 mb-1">CEP</label>
                                        <input type="text" id="cep" name="cep" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" value="<?php echo htmlspecialchars($aluno['cep'] ?? ''); ?>">
                                    </div>
                                    <div>
                                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                        <select id="status" name="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" required>
                                            <option value="ativo" <?php echo $aluno['status'] === 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                                            <option value="inativo" <?php echo $aluno['status'] === 'inativo' ? 'selected' : ''; ?>>Inativo</option>
                                            <option value="trancado" <?php echo $aluno['status'] === 'trancado' ? 'selected' : ''; ?>>Trancado</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="flex justify-end mt-6">
                                    <a href="alunos.php?action=visualizar&id=<?php echo $aluno['id']; ?>" class="btn-secondary mr-2">Cancelar</a>
                                    <button type="submit" class="btn-primary">Salvar Alterações</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <?php elseif ($action === 'visualizar' && isset($aluno)): ?>
                    <!-- Visualização de Aluno -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <div class="flex justify-between items-center">
                                <h2 class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($aluno['nome']); ?></h2>
                                <span class="badge <?php
                                    echo $aluno['status'] === 'ativo' ? 'badge-success' :
                                        ($aluno['status'] === 'inativo' ? 'badge-danger' :
                                        ($aluno['status'] === 'trancado' ? 'badge-warning' : 'badge-secondary'));
                                ?>">
                                    <?php
                                        echo $aluno['status'] === 'ativo' ? 'Ativo' :
                                            ($aluno['status'] === 'inativo' ? 'Inativo' :
                                            ($aluno['status'] === 'trancado' ? 'Trancado' :
                                            ucfirst($aluno['status'])));
                                    ?>
                                </span>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                                <div>
                                    <p class="text-sm text-gray-500">CPF</p>
                                    <p class="font-medium"><?php echo formatarCpf($aluno['cpf']); ?></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Data de Nascimento</p>
                                    <p class="font-medium">
                                        <?php if (!empty($aluno['data_nascimento'])): ?>
                                            <?php echo date('d/m/Y', strtotime($aluno['data_nascimento'])); ?>
                                        <?php else: ?>
                                            Não informada
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">E-mail</p>
                                    <p class="font-medium"><?php echo htmlspecialchars($aluno['email']); ?></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Telefone</p>
                                    <p class="font-medium"><?php echo htmlspecialchars($aluno['telefone'] ?? 'Não informado'); ?></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Endereço</p>
                                    <p class="font-medium">
                                        <?php echo !empty($aluno['endereco']) ? htmlspecialchars($aluno['endereco']) : 'Endereço não informado'; ?>
                                        <?php echo !empty($aluno['numero']) ? ', ' . htmlspecialchars($aluno['numero']) : ''; ?>
                                        <?php echo !empty($aluno['complemento']) ? ', ' . htmlspecialchars($aluno['complemento']) : ''; ?>
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        <?php echo !empty($aluno['bairro']) ? htmlspecialchars($aluno['bairro']) : 'Bairro não informado'; ?>
                                        <?php if (!empty($aluno['cidade']) || !empty($aluno['estado'])): ?>,<?php endif; ?>
                                        <?php echo !empty($aluno['cidade']) ? htmlspecialchars($aluno['cidade']) : ''; ?>
                                        <?php if (!empty($aluno['cidade']) && !empty($aluno['estado'])): ?> - <?php endif; ?>
                                        <?php echo !empty($aluno['estado']) ? htmlspecialchars($aluno['estado']) : ''; ?>
                                        <?php if (!empty($aluno['cep'])): ?>,
                                            <?php
                                            // Formata o CEP (00000-000)
                                            $cep = preg_replace('/[^0-9]/', '', $aluno['cep']);
                                            if (strlen($cep) == 8) {
                                                echo substr($cep, 0, 5) . '-' . substr($cep, 5, 3);
                                            } else {
                                                echo $cep;
                                            }
                                            ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Data de Ingresso</p>
                                    <p class="font-medium">
                                        <?php if (!empty($aluno['data_ingresso'])): ?>
                                            <?php echo date('d/m/Y', strtotime($aluno['data_ingresso'])); ?>
                                        <?php else: ?>
                                            Não informada
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Curso Atual</p>
                                    <p class="font-medium"><?php echo htmlspecialchars($aluno['curso_nome'] ?? 'Não definido'); ?></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Turma Atual</p>
                                    <p class="font-medium"><?php echo htmlspecialchars($aluno['turma_nome'] ?? 'Não definido'); ?></p>
                                </div>
                            </div>

                            <!-- Matrículas do Aluno -->
                            <div class="mt-8">
                                <h3 class="text-lg font-medium text-gray-800 mb-4">Matrículas</h3>

                                <?php if (empty($matriculas)): ?>
                                <div class="text-center text-gray-500 py-4">
                                    <p>Nenhuma matrícula encontrada para este aluno.</p>
                                </div>
                                <?php else: ?>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Curso</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Turma</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <?php foreach ($matriculas as $matricula): ?>
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm font-medium text-gray-900"><?php echo $matricula['id']; ?></div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($matricula['curso_nome'] ?? 'Não definido'); ?></div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($matricula['turma_nome'] ?? 'Não definido'); ?></div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm text-gray-900">
                                                        <?php if (!empty($matricula['data_matricula'])): ?>
                                                            <?php echo date('d/m/Y', strtotime($matricula['data_matricula'])); ?>
                                                        <?php else: ?>
                                                            Data não informada
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="badge <?php
                                                        echo $matricula['status'] === 'ativo' ? 'badge-success' :
                                                            ($matricula['status'] === 'inativo' ? 'badge-danger' :
                                                            ($matricula['status'] === 'trancado' ? 'badge-warning' : 'badge-secondary'));
                                                    ?>">
                                                        <?php
                                                            echo $matricula['status'] === 'ativo' ? 'Ativo' :
                                                                ($matricula['status'] === 'inativo' ? 'Inativo' :
                                                                ($matricula['status'] === 'trancado' ? 'Trancado' :
                                                                ucfirst($matricula['status'])));
                                                        ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-between">
                        <a href="alunos.php" class="btn-secondary">
                            <i class="fas fa-arrow-left mr-1"></i> Voltar para a Lista
                        </a>
                        <div>
                            <a href="alunos.php?action=editar&id=<?php echo $aluno['id']; ?>" class="btn-secondary mr-2">
                                <i class="fas fa-edit mr-1"></i> Editar Aluno
                            </a>
                            <a href="alunos_documentos.php?id=<?php echo $aluno['id']; ?>" class="btn-secondary mr-2">
                                <i class="fas fa-file-alt mr-1"></i> Documentos Pessoais
                            </a>
                            <a href="documentos.php?action=solicitar&aluno_id=<?php echo $aluno['id']; ?>" class="btn-primary">
                                <i class="fas fa-file-download mr-1"></i> Solicitar Documento
                            </a>
                        </div>
                    </div>
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
