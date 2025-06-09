<?php
/**
 * Visualização de Cursos do Polo
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
        $curso_id = $_GET['id'] ?? 0;

        // Carrega os dados do curso
        $sql = "SELECT c.*
                FROM cursos c
                WHERE c.id = ? AND c.status = 'ativo'";
        $curso = $db->fetchOne($sql, [$curso_id]);

        if (!$curso) {
            setMensagem('erro', 'Curso não encontrado ou não está ativo.');
            redirect('cursos.php');
            exit;
        }

        // Carrega as turmas do curso para o polo
        $sql = "SELECT t.id, t.nome, t.data_inicio, t.data_fim, t.status,
                       (SELECT COUNT(*) FROM matriculas m WHERE m.turma_id = t.id AND m.polo_id = ?) as total_alunos
                FROM turmas t
                WHERE t.curso_id = ? AND t.polo_id = ?
                ORDER BY t.data_inicio DESC";
        $turmas = $db->fetchAll($sql, [$polo_id, $curso_id, $polo_id]);

        // Se não encontrar turmas, verifica se existem alunos matriculados no curso
        if (empty($turmas)) {
            $sql = "SELECT COUNT(*) as total_alunos
                    FROM alunos a
                    WHERE a.curso_id = ? AND a.polo_id = ?";
            $alunos_curso = $db->fetchOne($sql, [$curso_id, $polo_id]);

            if (isset($alunos_curso['total_alunos']) && intval($alunos_curso['total_alunos']) > 0) {
                error_log("Curso {$curso_id} tem {$alunos_curso['total_alunos']} alunos, mas nenhuma turma no polo {$polo_id}");
            }
        }

        // Define o título da página
        $titulo_pagina = 'Visualizar Curso';
        break;

    default: // listar
        // Carrega os cursos disponíveis para o polo
        $sql = "SELECT c.id, c.nome, c.carga_horaria, c.status,
                       (SELECT COUNT(*) FROM turmas t WHERE t.curso_id = c.id AND t.polo_id = ?) as total_turmas,
                       (SELECT COUNT(*) FROM matriculas m WHERE m.curso_id = c.id AND m.polo_id = ? AND m.status = 'ativo') as total_alunos
                FROM cursos c
                LEFT JOIN turmas t ON c.id = t.curso_id AND t.polo_id = ?
                WHERE c.status = 'ativo' AND
                      (
                          t.polo_id = ? OR
                          c.id IN (SELECT DISTINCT curso_id FROM matriculas WHERE polo_id = ?) OR
                          c.id IN (SELECT DISTINCT curso_id FROM alunos WHERE polo_id = ?)
                      )
                GROUP BY c.id, c.nome, c.carga_horaria, c.status
                ORDER BY c.nome";
        $cursos = $db->fetchAll($sql, [$polo_id, $polo_id, $polo_id, $polo_id, $polo_id, $polo_id]);

        // Define o título da página
        $titulo_pagina = 'Cursos Disponíveis';
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

        .card {
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
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
                    <!-- Lista de Cursos -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php if (empty($cursos)): ?>
                        <div class="col-span-3 text-center text-gray-500 py-4">
                            <p>Nenhum curso disponível para o seu polo.</p>
                        </div>
                        <?php else: ?>
                        <?php foreach ($cursos as $curso): ?>
                        <div class="card bg-white p-6">
                            <div class="flex justify-between items-start mb-4">
                                <h3 class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($curso['nome']); ?></h3>
                                <span class="badge badge-success">Ativo</span>
                            </div>
                            <p class="text-sm text-gray-600 mb-4">Código: <?php echo $curso['id']; ?></p>
                            <div class="flex justify-between items-center mb-4">
                                <div>
                                    <p class="text-sm text-gray-500">Carga Horária</p>
                                    <p class="font-medium"><?php echo $curso['carga_horaria']; ?> horas</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Turmas</p>
                                    <p class="font-medium"><?php echo isset($curso['total_turmas']) ? intval($curso['total_turmas']) : 0; ?></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Alunos</p>
                                    <p class="font-medium"><?php echo isset($curso['total_alunos']) ? intval($curso['total_alunos']) : 0; ?></p>
                                </div>
                            </div>
                            <div class="mt-4">
                                <a href="cursos.php?action=visualizar&id=<?php echo $curso['id']; ?>" class="text-blue-600 hover:text-blue-900 font-medium">Ver detalhes</a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <?php elseif ($action === 'visualizar' && isset($curso)): ?>
                    <!-- Visualização de Curso -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <div class="flex justify-between items-center">
                                <h2 class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($curso['nome']); ?></h2>
                                <span class="badge badge-success">Ativo</span>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                                <div>
                                    <p class="text-sm text-gray-500">Código</p>
                                    <p class="font-medium"><?php echo $curso['id']; ?></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Carga Horária</p>
                                    <p class="font-medium"><?php echo $curso['carga_horaria']; ?> horas</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Nível</p>
                                    <p class="font-medium"><?php echo htmlspecialchars($curso['nivel'] ?? 'Não definido'); ?></p>
                                </div>
                            </div>

                            <?php if (!empty($curso['descricao'])): ?>
                            <div class="mb-6">
                                <p class="text-sm text-gray-500 mb-2">Descrição</p>
                                <div class="bg-gray-50 p-4 rounded-md">
                                    <?php echo nl2br(htmlspecialchars($curso['descricao'])); ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Turmas do Curso -->
                            <div class="mt-8">
                                <h3 class="text-lg font-medium text-gray-800 mb-4">Turmas Disponíveis</h3>

                                <?php if (empty($turmas)): ?>
                                <div class="text-center text-gray-500 py-4">
                                    <p>Nenhuma turma disponível para este curso no seu polo.</p>
                                </div>
                                <?php else: ?>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
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
                    </div>

                    <div class="flex justify-between">
                        <a href="cursos.php" class="btn-secondary">Voltar para a Lista</a>
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
