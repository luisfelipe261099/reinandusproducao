<?php
/**
 * Visualização de Turmas do Polo
 */

// Inicializa o sistema
require_once __DIR__ . '/../includes/init.php';

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
    case 'visualizar':
        $turma_id = $_GET['id'] ?? 0;

        // Carrega os dados da turma
        $sql = "SELECT t.*, c.nome as curso_nome, c.id as curso_id
                FROM turmas t
                LEFT JOIN cursos c ON t.curso_id = c.id
                WHERE t.id = ? AND t.polo_id = ?";
        $turma = $db->fetchOne($sql, [$turma_id, $polo_id]);

        if (!$turma) {
            setMensagem('erro', 'Turma não encontrada ou não pertence ao seu polo.');
            redirect('turmas.php');
            exit;
        }

        // Carrega os alunos da turma
        // Primeiro, obtém o curso_id da turma
        $curso_id = $turma['curso_id'];

        // Busca alunos matriculados nesta turma específica via tabela matriculas
        $sql = "SELECT a.id, a.nome, a.cpf, a.email, a.status, m.status as matricula_status,
                       'matricula' as origem, m.turma_id
                FROM alunos a
                JOIN matriculas m ON a.id = m.aluno_id
                WHERE m.turma_id = ? AND m.polo_id = ?
                ORDER BY a.nome";
        $alunos_turma_matriculas = $db->fetchAll($sql, [$turma_id, $polo_id]);

        // Se não encontrar alunos diretamente na turma, busca alunos matriculados no curso
        if (empty($alunos_turma_matriculas)) {
            $sql = "SELECT a.id, a.nome, a.cpf, a.email, a.status, m.status as matricula_status,
                           'matricula_curso' as origem, m.turma_id
                    FROM alunos a
                    JOIN matriculas m ON a.id = m.aluno_id
                    WHERE m.curso_id = ? AND m.polo_id = ?
                    ORDER BY a.nome";
            $alunos_turma_matriculas = $db->fetchAll($sql, [$curso_id, $polo_id]);
        }

        // Busca alunos vinculados diretamente à turma (legado)
        $sql = "SELECT a.id, a.nome, a.cpf, a.email, a.status, 'ativo' as matricula_status,
                       'direto' as origem, NULL as turma_id
                FROM alunos a
                WHERE a.curso_id = ? AND a.polo_id = ?
                ORDER BY a.nome";
        $alunos_turma_direto = $db->fetchAll($sql, [$curso_id, $polo_id]);

        // Combina os resultados, removendo duplicatas
        $alunos_ids = [];
        $alunos = [];

        // Primeiro adiciona os alunos da tabela matriculas
        foreach ($alunos_turma_matriculas as $aluno) {
            $alunos_ids[$aluno['id']] = true;
            $alunos[] = $aluno;
        }

        // Depois adiciona os alunos vinculados diretamente, se não estiverem já na lista
        foreach ($alunos_turma_direto as $aluno) {
            if (!isset($alunos_ids[$aluno['id']])) {
                $alunos[] = $aluno;
            }
        }

        // Ordena os alunos por nome
        usort($alunos, function($a, $b) {
            return strcmp($a['nome'], $b['nome']);
        });

        // Define o título da página
        $titulo_pagina = 'Visualizar Turma';
        break;

    default: // listar
        // Carrega as turmas do polo
        $sql = "SELECT t.id, t.nome, t.data_inicio, t.data_fim, t.status, t.curso_id,
                       c.nome as curso_nome,
                       (
                           SELECT COUNT(*) FROM matriculas m WHERE m.turma_id = t.id AND m.polo_id = ?
                       ) as total_alunos
                FROM turmas t
                LEFT JOIN cursos c ON t.curso_id = c.id
                WHERE t.polo_id = ?
                ORDER BY t.data_inicio DESC";
        $turmas = $db->fetchAll($sql, [$polo_id, $polo_id]);

        // Define o título da página
        $titulo_pagina = 'Turmas do Polo';
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
                    <!-- Lista de Turmas -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-800">Turmas Disponíveis</h2>
                        </div>
                        <div class="p-6">
                            <?php if (empty($turmas)): ?>
                            <div class="text-center text-gray-500 py-4">
                                <p>Nenhuma turma encontrada para o seu polo.</p>
                            </div>
                            <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Curso</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Período</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alunos</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($turmas as $turma): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900"><?php echo $turma['id']; ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($turma['nome']); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($turma['curso_nome'] ?? 'Não definido'); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">
                                                    <?php echo !empty($turma['data_inicio']) ? date('d/m/Y', strtotime($turma['data_inicio'])) : 'Data não definida'; ?> a
                                                    <?php echo !empty($turma['data_fim']) ? date('d/m/Y', strtotime($turma['data_fim'])) : 'Data não definida'; ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900"><?php echo $turma['total_alunos']; ?> alunos</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="badge <?php
                                                    echo $turma['status'] === 'ativo' ? 'badge-success' :
                                                        ($turma['status'] === 'inativo' ? 'badge-danger' :
                                                        ($turma['status'] === 'em_andamento' ? 'badge-primary' :
                                                        ($turma['status'] === 'concluido' ? 'badge-secondary' : 'badge-warning')));
                                                ?>">
                                                    <?php
                                                        echo $turma['status'] === 'ativo' ? 'Ativo' :
                                                            ($turma['status'] === 'inativo' ? 'Inativo' :
                                                            ($turma['status'] === 'em_andamento' ? 'Em Andamento' :
                                                            ($turma['status'] === 'concluido' ? 'Concluído' :
                                                            ucfirst($turma['status']))));
                                                    ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <a href="turmas.php?action=visualizar&id=<?php echo $turma['id']; ?>" class="text-blue-600 hover:text-blue-900">Visualizar</a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php elseif ($action === 'visualizar' && isset($turma)): ?>
                    <!-- Visualização de Turma -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <div class="flex justify-between items-center">
                                <h2 class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($turma['nome']); ?></h2>
                                <span class="badge <?php
                                    echo $turma['status'] === 'ativo' ? 'badge-success' :
                                        ($turma['status'] === 'inativo' ? 'badge-danger' :
                                        ($turma['status'] === 'em_andamento' ? 'badge-primary' :
                                        ($turma['status'] === 'concluido' ? 'badge-secondary' : 'badge-warning')));
                                ?>">
                                    <?php
                                        echo $turma['status'] === 'ativo' ? 'Ativo' :
                                            ($turma['status'] === 'inativo' ? 'Inativo' :
                                            ($turma['status'] === 'em_andamento' ? 'Em Andamento' :
                                            ($turma['status'] === 'concluido' ? 'Concluído' :
                                            ucfirst($turma['status']))));
                                    ?>
                                </span>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                                <div>
                                    <p class="text-sm text-gray-500">Código</p>
                                    <p class="font-medium"><?php echo $turma['id']; ?></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Curso</p>
                                    <p class="font-medium"><?php echo htmlspecialchars($turma['curso_nome'] ?? 'Não definido'); ?></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Data de Início</p>
                                    <p class="font-medium"><?php echo !empty($turma['data_inicio']) ? date('d/m/Y', strtotime($turma['data_inicio'])) : 'Data não definida'; ?></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Data de Término</p>
                                    <p class="font-medium"><?php echo !empty($turma['data_fim']) ? date('d/m/Y', strtotime($turma['data_fim'])) : 'Data não definida'; ?></p>
                                </div>
                                <?php if (!empty($turma['descricao'])): ?>
                                <div class="md:col-span-2">
                                    <p class="text-sm text-gray-500">Descrição</p>
                                    <p class="font-medium"><?php echo nl2br(htmlspecialchars($turma['descricao'])); ?></p>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Alunos da Turma -->
                            <div class="mt-8">
                                <h3 class="text-lg font-medium text-gray-800 mb-4">Alunos Matriculados</h3>

                                <?php if (empty($alunos)): ?>
                                <div class="text-center text-gray-500 py-4">
                                    <p>Nenhum aluno matriculado nesta turma.</p>
                                </div>
                                <?php else: ?>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CPF</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">E-mail</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <?php foreach ($alunos as $aluno): ?>
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($aluno['nome']); ?></div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm text-gray-900"><?php echo formatarCpf($aluno['cpf']); ?></div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($aluno['email']); ?></div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="badge <?php
                                                        // Prioriza o status da matrícula sobre o status do aluno
                                                        $status = isset($aluno['matricula_status']) ? $aluno['matricula_status'] : $aluno['status'];
                                                        echo $status === 'ativo' ? 'badge-success' :
                                                            ($status === 'inativo' ? 'badge-danger' :
                                                            ($status === 'trancado' ? 'badge-warning' : 'badge-secondary'));
                                                    ?>">
                                                        <?php
                                                            echo $status === 'ativo' ? 'Ativo' :
                                                                ($status === 'inativo' ? 'Inativo' :
                                                                ($status === 'trancado' ? 'Trancado' :
                                                                ucfirst($status)));
                                                        ?>
                                                    </span>
                                                    <div class="text-xs text-gray-500 mt-1">
                                                        <?php
                                                            if (isset($aluno['origem'])) {
                                                                if ($aluno['origem'] === 'matricula') {
                                                                    echo "Matriculado nesta turma";
                                                                } elseif ($aluno['origem'] === 'matricula_curso') {
                                                                    echo "Matriculado no curso";
                                                                } elseif ($aluno['origem'] === 'direto') {
                                                                    echo "Vinculado ao curso";
                                                                }
                                                            }
                                                        ?>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                    <a href="alunos.php?action=visualizar&id=<?php echo $aluno['id']; ?>" class="text-blue-600 hover:text-blue-900">Visualizar</a>
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
                        <a href="turmas.php" class="btn-secondary">Voltar para a Lista</a>
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
