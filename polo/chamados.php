<?php
/**
 * Gerenciamento de Chamados do Polo
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

// Processa o formulário de novo chamado
if (isPost() && $action === 'novo') {
    $titulo = $_POST['titulo'] ?? '';
    $descricao = $_POST['descricao'] ?? '';
    $categoria_id = $_POST['categoria_id'] ?? '';

    // Validação básica
    $errors = [];

    if (empty($titulo)) {
        $errors[] = 'O título é obrigatório';
    }

    if (empty($descricao)) {
        $errors[] = 'A descrição é obrigatória';
    }

    if (empty($categoria_id)) {
        $errors[] = 'A categoria é obrigatória';
    }

    // Se não houver erros, salva o chamado
    if (empty($errors)) {
        try {
            // Gera um código único para o chamado
            $ano = date('Y');
            $sql = "SELECT COUNT(*) as total FROM chamados WHERE YEAR(data_abertura) = ?";
            $resultado = $db->fetchOne($sql, [$ano]);
            $numero = ($resultado['total'] ?? 0) + 1;
            $codigo = 'TICK-' . $ano . '-' . str_pad($numero, 4, '0', STR_PAD_LEFT);

            // Insere o chamado
            $chamado_id = $db->insert('chamados', [
                'codigo' => $codigo,
                'titulo' => $titulo,
                'descricao' => $descricao,
                'categoria_id' => $categoria_id,
                'tipo' => 'polo',
                'prioridade' => 'media',
                'status' => 'aberto',
                'solicitante_id' => $usuario_id,
                'polo_id' => $polo_id,
                'data_abertura' => date('Y-m-d H:i:s'),
                'data_ultima_atualizacao' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            // Registra o histórico
            $db->insert('chamados_historico', [
                'chamado_id' => $chamado_id,
                'usuario_id' => $usuario_id,
                'acao' => 'abertura',
                'descricao' => 'Chamado aberto pelo polo',
                'data_hora' => date('Y-m-d H:i:s')
            ]);

            // Redireciona para a visualização do chamado
            setMensagem('sucesso', 'Chamado aberto com sucesso!');
            redirect('chamados.php?action=visualizar&id=' . $chamado_id);
            exit;
        } catch (Exception $e) {
            error_log('Erro ao abrir chamado: ' . $e->getMessage());
            $errors[] = 'Erro ao abrir chamado. Por favor, tente novamente.';
        }
    }
}

// Processa o formulário de resposta ao chamado
if (isPost() && $action === 'responder') {
    $chamado_id = $_POST['chamado_id'] ?? '';
    $mensagem = $_POST['mensagem'] ?? '';

    // Validação básica
    $errors = [];

    if (empty($chamado_id)) {
        $errors[] = 'ID do chamado inválido';
    }

    if (empty($mensagem)) {
        $errors[] = 'A mensagem é obrigatória';
    }

    // Verifica se o chamado pertence ao polo
    if (!empty($chamado_id)) {
        $sql = "SELECT id FROM chamados WHERE id = ? AND polo_id = ?";
        $resultado = $db->fetchOne($sql, [$chamado_id, $polo_id]);

        if (!$resultado) {
            $errors[] = 'Chamado não encontrado ou não pertence ao seu polo';
        }
    }

    // Se não houver erros, salva a resposta
    if (empty($errors)) {
        try {
            // Insere a resposta
            $resposta_id = $db->insert('chamados_respostas', [
                'chamado_id' => $chamado_id,
                'usuario_id' => $usuario_id,
                'mensagem' => $mensagem,
                'tipo' => 'resposta',
                'visivel_solicitante' => 1,
                'data_resposta' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            // Atualiza o status do chamado para "aguardando resposta"
            $db->update('chamados', [
                'status' => 'aguardando_resposta',
                'data_ultima_atualizacao' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$chamado_id]);

            // Registra o histórico
            $db->insert('chamados_historico', [
                'chamado_id' => $chamado_id,
                'usuario_id' => $usuario_id,
                'acao' => 'resposta',
                'descricao' => 'Resposta adicionada pelo polo',
                'data_hora' => date('Y-m-d H:i:s')
            ]);

            // Redireciona para a visualização do chamado
            setMensagem('sucesso', 'Resposta enviada com sucesso!');
            redirect('chamados.php?action=visualizar&id=' . $chamado_id);
            exit;
        } catch (Exception $e) {
            error_log('Erro ao responder chamado: ' . $e->getMessage());
            $errors[] = 'Erro ao responder chamado. Por favor, tente novamente.';
        }
    }
}

// Carrega os dados conforme a ação
switch ($action) {
    case 'novo':
        // Carrega as categorias de chamados para polos
        $sql = "SELECT id, nome FROM categorias_chamados WHERE tipo = 'polo' AND status = 'ativo' ORDER BY nome";
        $categorias = $db->fetchAll($sql);

        // Define o título da página
        $titulo_pagina = 'Abrir Novo Chamado';
        break;

    case 'visualizar':
        $chamado_id = $_GET['id'] ?? 0;

        // Carrega os dados do chamado
        $sql = "SELECT c.*, cc.nome as categoria_nome, u.nome as solicitante_nome
                FROM chamados c
                JOIN categorias_chamados cc ON c.categoria_id = cc.id
                JOIN usuarios u ON c.solicitante_id = u.id
                WHERE c.id = ? AND c.polo_id = ?";
        $chamado = $db->fetchOne($sql, [$chamado_id, $polo_id]);

        if (!$chamado) {
            setMensagem('erro', 'Chamado não encontrado ou não pertence ao seu polo.');
            redirect('chamados.php');
            exit;
        }

        // Carrega as respostas do chamado
        $sql = "SELECT cr.*, u.nome as usuario_nome, u.tipo as usuario_tipo
                FROM chamados_respostas cr
                JOIN usuarios u ON cr.usuario_id = u.id
                WHERE cr.chamado_id = ? AND (cr.visivel_solicitante = 1 OR cr.usuario_id = ?)
                ORDER BY cr.data_resposta ASC";
        $respostas = $db->fetchAll($sql, [$chamado_id, $usuario_id]);

        // Define o título da página
        $titulo_pagina = 'Visualizar Chamado #' . $chamado['codigo'];
        break;

    default: // listar
        // Carrega os chamados do polo
        $sql = "SELECT c.id, c.codigo, c.titulo, c.status, c.prioridade, c.data_abertura, c.data_ultima_atualizacao,
                       cc.nome as categoria_nome
                FROM chamados c
                JOIN categorias_chamados cc ON c.categoria_id = cc.id
                WHERE c.polo_id = ?
                ORDER BY c.data_ultima_atualizacao DESC";
        $chamados = $db->fetchAll($sql, [$polo_id]);

        // Define o título da página
        $titulo_pagina = 'Gerenciar Chamados';
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

        .message-bubble {
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
            position: relative;
        }
        .message-bubble.user {
            background-color: #E5EDFF;
            border-left: 4px solid #3B82F6;
        }
        .message-bubble.admin {
            background-color: #F3F4F6;
            border-left: 4px solid #6B7280;
        }
        .message-bubble.system {
            background-color: #FEF3C7;
            border-left: 4px solid #F59E0B;
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
                        <?php if ($action === 'listar'): ?>
                        <a href="chamados.php?action=novo" class="btn-primary">
                            <i class="fas fa-plus mr-2"></i> Abrir Novo Chamado
                        </a>
                        <?php endif; ?>
                    </div>

                    <?php if (isset($_SESSION['mensagem'])): ?>
                    <div class="bg-<?php echo isset($_SESSION['mensagem_tipo']) && $_SESSION['mensagem_tipo'] === 'sucesso' ? 'green' : 'red'; ?>-100 border-l-4 border-<?php echo isset($_SESSION['mensagem_tipo']) && $_SESSION['mensagem_tipo'] === 'sucesso' ? 'green' : 'red'; ?>-500 text-<?php echo isset($_SESSION['mensagem_tipo']) && $_SESSION['mensagem_tipo'] === 'sucesso' ? 'green' : 'red'; ?>-700 p-4 mb-6">
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
                    if (isset($_SESSION['mensagem_tipo'])) {
                        unset($_SESSION['mensagem_tipo']);
                    }
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
                    <!-- Lista de Chamados -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-800">Meus Chamados</h2>
                        </div>
                        <div class="p-6">
                            <?php if (empty($chamados)): ?>
                            <div class="text-center text-gray-500 py-4">
                                <p>Você ainda não abriu nenhum chamado.</p>
                                <a href="chamados.php?action=novo" class="btn-primary inline-block mt-4">Abrir Novo Chamado</a>
                            </div>
                            <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Título</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categoria</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($chamados as $chamado): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900"><?php echo $chamado['codigo']; ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($chamado['titulo']); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($chamado['categoria_nome']); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="badge <?php
                                                    echo $chamado['status'] === 'aberto' ? 'badge-danger' :
                                                        ($chamado['status'] === 'em_andamento' ? 'badge-warning' :
                                                        ($chamado['status'] === 'resolvido' ? 'badge-success' :
                                                        ($chamado['status'] === 'aguardando_resposta' ? 'badge-primary' : 'badge-secondary')));
                                                ?>">
                                                    <?php
                                                        echo $chamado['status'] === 'aberto' ? 'Aberto' :
                                                            ($chamado['status'] === 'em_andamento' ? 'Em Andamento' :
                                                            ($chamado['status'] === 'resolvido' ? 'Resolvido' :
                                                            ($chamado['status'] === 'aguardando_resposta' ? 'Aguardando Resposta' :
                                                            ucfirst(str_replace('_', ' ', $chamado['status'])))));
                                                    ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900"><?php echo date('d/m/Y H:i', strtotime($chamado['data_ultima_atualizacao'])); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <a href="chamados.php?action=visualizar&id=<?php echo $chamado['id']; ?>" class="text-blue-600 hover:text-blue-900">Visualizar</a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php elseif ($action === 'novo'): ?>
                    <!-- Formulário de Novo Chamado -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-800">Abrir Novo Chamado</h2>
                        </div>
                        <div class="p-6">
                            <form method="post" action="chamados.php?action=novo">
                                <div class="mb-4">
                                    <label for="categoria_id" class="block text-sm font-medium text-gray-700 mb-1">Categoria</label>
                                    <select id="categoria_id" name="categoria_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" required>
                                        <option value="">Selecione uma categoria</option>
                                        <?php foreach ($categorias as $categoria): ?>
                                        <option value="<?php echo $categoria['id']; ?>" <?php echo isset($_POST['categoria_id']) && $_POST['categoria_id'] == $categoria['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($categoria['nome']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-4">
                                    <label for="titulo" class="block text-sm font-medium text-gray-700 mb-1">Título</label>
                                    <input type="text" id="titulo" name="titulo" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" value="<?php echo isset($_POST['titulo']) ? htmlspecialchars($_POST['titulo']) : ''; ?>" required>
                                </div>
                                <div class="mb-4">
                                    <label for="descricao" class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
                                    <textarea id="descricao" name="descricao" rows="6" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" required><?php echo isset($_POST['descricao']) ? htmlspecialchars($_POST['descricao']) : ''; ?></textarea>
                                </div>
                                <div class="flex justify-end">
                                    <a href="chamados.php" class="btn-secondary mr-2">Cancelar</a>
                                    <button type="submit" class="btn-primary">Abrir Chamado</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php elseif ($action === 'visualizar' && isset($chamado)): ?>
                    <!-- Visualização de Chamado -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <div class="flex justify-between items-center">
                                <h2 class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($chamado['titulo']); ?></h2>
                                <span class="badge <?php
                                    echo $chamado['status'] === 'aberto' ? 'badge-danger' :
                                        ($chamado['status'] === 'em_andamento' ? 'badge-warning' :
                                        ($chamado['status'] === 'resolvido' ? 'badge-success' :
                                        ($chamado['status'] === 'aguardando_resposta' ? 'badge-primary' : 'badge-secondary')));
                                ?>">
                                    <?php
                                        echo $chamado['status'] === 'aberto' ? 'Aberto' :
                                            ($chamado['status'] === 'em_andamento' ? 'Em Andamento' :
                                            ($chamado['status'] === 'resolvido' ? 'Resolvido' :
                                            ($chamado['status'] === 'aguardando_resposta' ? 'Aguardando Resposta' :
                                            ucfirst(str_replace('_', ' ', $chamado['status'])))));
                                    ?>
                                </span>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                                <div>
                                    <p class="text-sm text-gray-500">Código</p>
                                    <p class="font-medium"><?php echo $chamado['codigo']; ?></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Categoria</p>
                                    <p class="font-medium"><?php echo htmlspecialchars($chamado['categoria_nome']); ?></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Data de Abertura</p>
                                    <p class="font-medium"><?php echo date('d/m/Y H:i', strtotime($chamado['data_abertura'])); ?></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Última Atualização</p>
                                    <p class="font-medium"><?php echo date('d/m/Y H:i', strtotime($chamado['data_ultima_atualizacao'])); ?></p>
                                </div>
                            </div>

                            <div class="mb-6">
                                <p class="text-sm text-gray-500 mb-2">Descrição</p>
                                <div class="bg-gray-50 p-4 rounded-md">
                                    <?php echo nl2br(htmlspecialchars($chamado['descricao'])); ?>
                                </div>
                            </div>

                            <div class="border-t border-gray-200 pt-6">
                                <h3 class="text-lg font-medium text-gray-800 mb-4">Histórico de Respostas</h3>

                                <?php if (empty($respostas)): ?>
                                <div class="text-center text-gray-500 py-4">
                                    <p>Ainda não há respostas para este chamado.</p>
                                </div>
                                <?php else: ?>
                                <div class="space-y-4">
                                    <?php foreach ($respostas as $resposta): ?>
                                    <div class="message-bubble <?php echo $resposta['usuario_tipo'] === 'polo' ? 'user' : 'admin'; ?>">
                                        <div class="flex justify-between items-start mb-2">
                                            <div>
                                                <span class="font-medium"><?php echo htmlspecialchars($resposta['usuario_nome']); ?></span>
                                                <span class="text-sm text-gray-500 ml-2"><?php echo date('d/m/Y H:i', strtotime($resposta['data_resposta'])); ?></span>
                                            </div>
                                            <span class="text-xs text-gray-500"><?php echo $resposta['usuario_tipo'] === 'polo' ? 'Polo' : 'Atendente'; ?></span>
                                        </div>
                                        <div>
                                            <?php echo nl2br(htmlspecialchars($resposta['mensagem'])); ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>

                                <?php if ($chamado['status'] !== 'fechado' && $chamado['status'] !== 'cancelado'): ?>
                                <div class="mt-6">
                                    <h3 class="text-lg font-medium text-gray-800 mb-4">Responder</h3>
                                    <form method="post" action="chamados.php?action=responder">
                                        <input type="hidden" name="chamado_id" value="<?php echo $chamado['id']; ?>">
                                        <div class="mb-4">
                                            <textarea id="mensagem" name="mensagem" rows="4" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" placeholder="Digite sua resposta..." required></textarea>
                                        </div>
                                        <div class="flex justify-end">
                                            <button type="submit" class="btn-primary">Enviar Resposta</button>
                                        </div>
                                    </form>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-between">
                        <a href="chamados.php" class="btn-secondary">Voltar para a Lista</a>
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
