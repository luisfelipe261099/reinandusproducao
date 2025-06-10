<?php
/**
 * Dashboard do Polo
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
    redirect('../index.php');
    exit;
}

// Registra log de acesso ao dashboard do polo
if (function_exists('registrarLog')) {
    registrarLog(
        'polo',
        'acesso_dashboard',
        'Usuário acessou o dashboard do polo',
        $polo_id,
        'polo',
        null,
        [
            'user_id' => $usuario_id,
            'polo_id' => $polo_id,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Desconhecido',
            'timestamp' => date('Y-m-d H:i:s')
        ]
    );
}

// Função para executar consultas com tratamento de erro
function executarConsulta($db, $sql, $params = [], $default = null) {
    try {
        return $db->fetchOne($sql, $params) ?: $default;
    } catch (Exception $e) {
        error_log('Erro na consulta SQL: ' . $e->getMessage());
        return $default;
    }
}

function executarConsultaAll($db, $sql, $params = [], $default = []) {
    try {
        return $db->fetchAll($sql, $params) ?: $default;
    } catch (Exception $e) {
        error_log('Erro na consulta SQL: ' . $e->getMessage());
        return $default;
    }
}

// Carrega os dados para o dashboard
try {
    // Total de alunos do polo
    $sql = "SELECT COUNT(*) as total FROM alunos WHERE polo_id = ? AND status = 'ativo'";
    $resultado = executarConsulta($db, $sql, [$polo_id]);
    $total_alunos = $resultado['total'] ?? 0;

    // Total de matrículas ativas do polo
    $sql = "SELECT COUNT(*) as total FROM matriculas WHERE polo_id = ? AND status = 'ativo'";
    $resultado = executarConsulta($db, $sql, [$polo_id]);
    $total_matriculas = $resultado['total'] ?? 0;

    // Total de chamados abertos pelo polo
    $sql = "SELECT COUNT(*) as total FROM chamados WHERE polo_id = ? AND status IN ('aberto', 'em_andamento', 'aguardando_resposta')";
    $resultado = executarConsulta($db, $sql, [$polo_id]);
    $total_chamados = $resultado['total'] ?? 0;

    // Total de documentos solicitados pelo polo
    $sql = "SELECT COUNT(*) as total FROM solicitacoes_documentos WHERE polo_id = ? AND status IN ('solicitado', 'processando')";
    $resultado = executarConsulta($db, $sql, [$polo_id]);
    $total_documentos = $resultado['total'] ?? 0;

    // Informações do polo
    $sql = "SELECT * FROM polos WHERE id = ?";
    $polo = executarConsulta($db, $sql, [$polo_id]);

    // Limite de documentos
    $limite_documentos = $polo['limite_documentos'] ?? 0;
    $documentos_emitidos = $polo['documentos_emitidos'] ?? 0;
    $documentos_disponiveis = $limite_documentos - $documentos_emitidos;
    $percentual_usado = $limite_documentos > 0 ? ($documentos_emitidos / $limite_documentos) * 100 : 0;

    // Chamados recentes
    $sql = "SELECT c.id, c.codigo, c.titulo, c.status, c.data_abertura, c.data_ultima_atualizacao,
                   cc.nome as categoria_nome
            FROM chamados c
            JOIN categorias_chamados cc ON c.categoria_id = cc.id
            WHERE c.polo_id = ?
            ORDER BY c.data_ultima_atualizacao DESC
            LIMIT 5";
    $chamados_recentes = executarConsultaAll($db, $sql, [$polo_id]);

    // Documentos recentes
    $sql = "SELECT sd.id, sd.data_solicitacao, sd.status, sd.quantidade,
                   td.nome as tipo_documento_nome,
                   a.nome as aluno_nome
            FROM solicitacoes_documentos sd
            JOIN tipos_documentos td ON sd.tipo_documento_id = td.id
            JOIN alunos a ON sd.aluno_id = a.id
            WHERE sd.polo_id = ?
            ORDER BY sd.data_solicitacao DESC
            LIMIT 5";
    $documentos_recentes = executarConsultaAll($db, $sql, [$polo_id]);

    // Alunos recentes
    $sql = "SELECT a.id, a.nome, a.email, a.cpf, a.data_ingresso, c.nome as curso_nome
            FROM alunos a
            LEFT JOIN cursos c ON a.curso_id = c.id
            WHERE a.polo_id = ?
            ORDER BY a.data_ingresso DESC
            LIMIT 5";
    $alunos_recentes = executarConsultaAll($db, $sql, [$polo_id]);

    // Verifica se a tabela documentos_alunos existe
    $tabela_existe = false;
    try {
        $result = $db->query("SHOW TABLES LIKE 'documentos_alunos'");
        $tabela_existe = !empty($result);
    } catch (Exception $e) {
        error_log('Erro ao verificar tabela documentos_alunos: ' . $e->getMessage());
    }

    // Alunos com documentos pendentes
    $alunos_documentos_pendentes = [];
    if ($tabela_existe) {
        try {
            // Busca tipos de documentos obrigatórios
            $sql = "SELECT id, nome FROM tipos_documentos_pessoais WHERE obrigatorio = 1 AND status = 'ativo'";
            $tipos_documentos_obrigatorios = executarConsultaAll($db, $sql);

            if (!empty($tipos_documentos_obrigatorios)) {
                $ids_tipos_obrigatorios = array_column($tipos_documentos_obrigatorios, 'id');

                // Busca alunos ativos do polo
                $sql = "SELECT id, nome FROM alunos WHERE polo_id = ? AND status = 'ativo'";
                $alunos_polo = executarConsultaAll($db, $sql, [$polo_id]);

                foreach ($alunos_polo as $aluno) {
                    $documentos_pendentes = [];

                    foreach ($tipos_documentos_obrigatorios as $tipo) {
                        // Verifica se o aluno tem o documento
                        $sql = "SELECT id FROM documentos_alunos
                                WHERE aluno_id = ? AND tipo_documento_id = ? AND status != 'rejeitado'";
                        $documento = executarConsulta($db, $sql, [$aluno['id'], $tipo['id']]);

                        if (!$documento) {
                            $documentos_pendentes[] = $tipo['nome'];
                        }
                    }

                    if (!empty($documentos_pendentes)) {
                        $aluno['documentos_pendentes'] = $documentos_pendentes;
                        $alunos_documentos_pendentes[] = $aluno;
                    }
                }
            }
        } catch (Exception $e) {
            error_log('Erro ao buscar alunos com documentos pendentes: ' . $e->getMessage());
        }
    }

} catch (Exception $e) {
    error_log('Erro ao carregar dados para o dashboard: ' . $e->getMessage());
}

// Define o título da página
$titulo_pagina = 'Dashboard do Polo';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faciência ERP - <?php echo $titulo_pagina; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/layout-fixes.css">
    <style>
        /* Estilos específicos para o dashboard */
        .card {
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .badge {
            border-radius: 9999px;
            padding: 0.25rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-primary {
            background-color: #3B82F6;
            color: white;
        }

        .badge-warning {
            background-color: #F59E0B;
            color: white;
        }

        .badge-danger {
            background-color: #EF4444;
            color: white;
        }

        .badge-success {
            background-color: #10B981;
            color: white;
        }

        .btn-primary {
            background-color: #3B82F6;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 500;
            transition: background-color 0.2s;
        }

        .btn-primary:hover {
            background-color: #2563EB;
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
                        <?php echo $_SESSION['mensagem']; ?>
                    </div>
                    <?php
                    // Limpa a mensagem da sessão
                    unset($_SESSION['mensagem']);
                    unset($_SESSION['mensagem_tipo']);
                    endif;
                    ?>

                    <div class="mb-8">
                        <p class="text-gray-600">Bem-vind<?php echo (isset($_SESSION['user_gender']) && $_SESSION['user_gender'] == 'feminino') ? 'a' : 'o'; ?>, <?php echo explode(' ', $_SESSION['user_name'] ?? 'Usuário')[0]; ?>! Aqui está um resumo das informações do seu polo.</p>
                    </div>

                    <?php if (!empty($alunos_documentos_pendentes)): ?>
                    <!-- Alerta de Documentos Pendentes -->
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-8">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-yellow-800">Atenção: Documentos Pendentes</h3>
                                <div class="mt-2 text-sm text-yellow-700">
                                    <p>Existem <?php echo count($alunos_documentos_pendentes); ?> alunos com documentos obrigatórios pendentes.</p>
                                    <details class="mt-2">
                                        <summary class="cursor-pointer text-yellow-800 font-medium">Ver detalhes</summary>
                                        <ul class="mt-2 pl-5 list-disc">
                                            <?php foreach ($alunos_documentos_pendentes as $aluno): ?>
                                            <li class="mb-2">
                                                <strong><?php echo htmlspecialchars($aluno['nome']); ?></strong>:
                                                <?php echo implode(', ', $aluno['documentos_pendentes']); ?>
                                                <a href="alunos.php?action=documentos&id=<?php echo $aluno['id']; ?>" class="text-blue-600 hover:underline ml-2">
                                                    <i class="fas fa-upload"></i> Adicionar
                                                </a>
                                            </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </details>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Link para teste do menu -->
                   

                    <!-- Stats Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <div class="card bg-white p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Total de Alunos</p>
                                    <p class="text-3xl font-bold text-gray-800 mt-1"><?php echo number_format($total_alunos, 0, ',', '.'); ?></p>
                                </div>
                                <div class="bg-blue-100 p-3 rounded-full">
                                    <i class="fas fa-user-graduate text-blue-500"></i>
                                </div>
                            </div>
                            <div class="mt-4">
                                <a href="alunos.php" class="text-blue-600 text-sm font-medium hover:underline">Ver todos os alunos</a>
                            </div>
                        </div>

                        <div class="card bg-white p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Matrículas Ativas</p>
                                    <p class="text-3xl font-bold text-gray-800 mt-1"><?php echo number_format($total_matriculas, 0, ',', '.'); ?></p>
                                </div>
                                <div class="bg-green-100 p-3 rounded-full">
                                    <i class="fas fa-id-card text-green-500"></i>
                                </div>
                            </div>
                            <div class="mt-4">
                                <a href="matriculas.php" class="text-green-600 text-sm font-medium hover:underline">Ver todas as matrículas</a>
                            </div>
                        </div>

                        <div class="card bg-white p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Chamados Abertos</p>
                                    <p class="text-3xl font-bold text-gray-800 mt-1"><?php echo number_format($total_chamados, 0, ',', '.'); ?></p>
                                </div>
                                <div class="bg-yellow-100 p-3 rounded-full">
                                    <i class="fas fa-ticket-alt text-yellow-500"></i>
                                </div>
                            </div>
                            <div class="mt-4">
                                <a href="chamados.php" class="text-yellow-600 text-sm font-medium hover:underline">Ver todos os chamados</a>
                            </div>
                        </div>

                        <div class="card bg-white p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Documentos Solicitados</p>
                                    <p class="text-3xl font-bold text-gray-800 mt-1"><?php echo number_format($total_documentos, 0, ',', '.'); ?></p>
                                </div>
                                <div class="bg-purple-100 p-3 rounded-full">
                                    <i class="fas fa-file-alt text-purple-500"></i>
                                </div>
                            </div>
                            <div class="mt-4">
                                <a href="documentos.php" class="text-purple-600 text-sm font-medium hover:underline">Ver todos os documentos</a>
                            </div>
                        </div>
                    </div>

                    <!-- Limite de Documentos -->
                    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4">Limite de Documentos</h2>
                        <div class="mb-4">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-medium text-gray-600">
                                    Documentos Emitidos: <?php echo number_format($documentos_emitidos, 0, ',', '.'); ?> de <?php echo number_format($limite_documentos, 0, ',', '.'); ?>
                                </span>
                                <span class="text-sm font-medium <?php echo $percentual_usado >= 90 ? 'text-red-600' : ($percentual_usado >= 75 ? 'text-yellow-600' : 'text-green-600'); ?>">
                                    <?php echo number_format($percentual_usado, 0); ?>% utilizado
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                <div class="<?php echo $percentual_usado >= 90 ? 'bg-red-600' : ($percentual_usado >= 75 ? 'bg-yellow-500' : 'bg-green-600'); ?> h-2.5 rounded-full" style="width: <?php echo min(100, $percentual_usado); ?>%"></div>
                            </div>
                        </div>
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-sm text-gray-600">Documentos disponíveis para emissão</p>
                                <p class="text-2xl font-bold <?php echo $documentos_disponiveis <= 10 ? 'text-red-600' : 'text-gray-800'; ?>"><?php echo number_format($documentos_disponiveis, 0, ',', '.'); ?></p>
                            </div>
                            <a href="documentos.php?action=solicitar" class="btn-primary">Solicitar Documento</a>
                        </div>
                    </div>

                    <!-- Two Column Layout -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                        <!-- Chamados Recentes -->
                        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                                <h3 class="text-lg font-semibold text-gray-800">Chamados Recentes</h3>
                                <a href="chamados.php" class="text-sm text-blue-600 hover:text-blue-800">Ver todos</a>
                            </div>
                            <div class="p-6">
                                <?php if (empty($chamados_recentes)): ?>
                                <div class="text-center text-gray-500 py-4">Não há chamados recentes.</div>
                                <?php else: ?>
                                <div class="space-y-4">
                                    <?php foreach ($chamados_recentes as $chamado): ?>
                                    <div class="border-b border-gray-200 pb-4 last:border-b-0 last:pb-0">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <a href="chamados.php?action=visualizar&id=<?php echo $chamado['id']; ?>" class="font-medium text-gray-800 hover:text-blue-600">
                                                    <?php echo htmlspecialchars($chamado['titulo']); ?>
                                                </a>
                                                <p class="text-sm text-gray-500 mt-1">
                                                    <span class="badge <?php
                                                        echo $chamado['status'] === 'aberto' ? 'badge-danger' :
                                                            ($chamado['status'] === 'em_andamento' ? 'badge-warning' :
                                                            ($chamado['status'] === 'resolvido' ? 'badge-success' : 'badge-primary'));
                                                    ?>">
                                                        <?php
                                                            echo $chamado['status'] === 'aberto' ? 'Aberto' :
                                                                ($chamado['status'] === 'em_andamento' ? 'Em Andamento' :
                                                                ($chamado['status'] === 'resolvido' ? 'Resolvido' :
                                                                ($chamado['status'] === 'aguardando_resposta' ? 'Aguardando Resposta' :
                                                                ucfirst(str_replace('_', ' ', $chamado['status'])))));
                                                        ?>
                                                    </span>
                                                    <span class="ml-2"><?php echo htmlspecialchars($chamado['categoria_nome']); ?></span>
                                                </p>
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                <?php if (!empty($chamado['data_ultima_atualizacao'])): ?>
                                                    <?php echo date('d/m/Y', strtotime($chamado['data_ultima_atualizacao'])); ?>
                                                <?php else: ?>
                                                    Não informada
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                                <div class="mt-4 text-center">
                                    <a href="chamados.php?action=novo" class="btn-primary">Abrir Novo Chamado</a>
                                </div>
                            </div>
                        </div>

                        <!-- Documentos Recentes -->
                        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                                <h3 class="text-lg font-semibold text-gray-800">Documentos Recentes</h3>
                                <a href="documentos.php" class="text-sm text-blue-600 hover:text-blue-800">Ver todos</a>
                            </div>
                            <div class="p-6">
                                <?php if (empty($documentos_recentes)): ?>
                                <div class="text-center text-gray-500 py-4">Não há documentos recentes.</div>
                                <?php else: ?>
                                <div class="space-y-4">
                                    <?php foreach ($documentos_recentes as $documento): ?>
                                    <div class="border-b border-gray-200 pb-4 last:border-b-0 last:pb-0">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <a href="documentos.php?action=visualizar&id=<?php echo $documento['id']; ?>" class="font-medium text-gray-800 hover:text-blue-600">
                                                    <?php echo htmlspecialchars($documento['tipo_documento_nome']); ?>
                                                </a>
                                                <p class="text-sm text-gray-500 mt-1">
                                                    <span class="badge <?php
                                                        echo $documento['status'] === 'solicitado' ? 'badge-warning' :
                                                            ($documento['status'] === 'processando' ? 'badge-primary' :
                                                            ($documento['status'] === 'pronto' ? 'badge-success' :
                                                            ($documento['status'] === 'entregue' ? 'badge-success' : 'badge-danger')));
                                                    ?>">
                                                        <?php
                                                            echo $documento['status'] === 'solicitado' ? 'Solicitado' :
                                                                ($documento['status'] === 'processando' ? 'Processando' :
                                                                ($documento['status'] === 'pronto' ? 'Pronto' :
                                                                ($documento['status'] === 'entregue' ? 'Entregue' : 'Cancelado')));
                                                        ?>
                                                    </span>
                                                    <span class="ml-2"><?php echo htmlspecialchars($documento['aluno_nome']); ?></span>
                                                </p>
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                <?php if (!empty($documento['data_solicitacao'])): ?>
                                                    <?php echo date('d/m/Y', strtotime($documento['data_solicitacao'])); ?>
                                                <?php else: ?>
                                                    Não informada
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                                <div class="mt-4 text-center">
                                    <a href="documentos.php?action=solicitar" class="btn-primary">Solicitar Documento</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Alunos Recentes -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
                        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                            <h3 class="text-lg font-semibold text-gray-800">Alunos Recentes</h3>
                            <a href="alunos.php" class="text-sm text-blue-600 hover:text-blue-800">Ver todos</a>
                        </div>
                        <div class="p-6">
                            <?php if (empty($alunos_recentes)): ?>
                            <div class="text-center text-gray-500 py-4">Não há alunos recentes.</div>
                            <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CPF</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Curso</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data de Ingresso</th>
                                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($alunos_recentes as $aluno): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($aluno['nome']); ?></div>
                                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($aluno['email']); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900"><?php echo formatarCpf($aluno['cpf']); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($aluno['curso_nome'] ?? 'Não definido'); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">
                                                    <?php if (!empty($aluno['data_ingresso'])): ?>
                                                        <?php echo date('d/m/Y', strtotime($aluno['data_ingresso'])); ?>
                                                    <?php else: ?>
                                                        Não informada
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <a href="alunos.php?action=visualizar&id=<?php echo $aluno['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">Visualizar</a>
                                                <a href="documentos.php?action=solicitar&aluno_id=<?php echo $aluno['id']; ?>" class="text-green-600 hover:text-green-900">Solicitar Documento</a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                            <div class="mt-4 text-center">
                                <a href="matriculas.php?action=nova" class="btn-primary">Matricular Novo Aluno</a>
                            </div>
                        </div>
                    </div>

                    <!-- Ações Rápidas -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4">Ações Rápidas</h2>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <a href="matriculas.php?action=nova" class="flex flex-col items-center justify-center bg-blue-50 hover:bg-blue-100 transition-all p-4 rounded-lg">
                                <div class="bg-blue-100 p-3 rounded-full mb-2">
                                    <i class="fas fa-user-plus text-blue-500"></i>
                                </div>
                                <span class="text-sm font-medium">Matricular Aluno</span>
                            </a>
                            <a href="chamados.php?action=novo" class="flex flex-col items-center justify-center bg-yellow-50 hover:bg-yellow-100 transition-all p-4 rounded-lg">
                                <div class="bg-yellow-100 p-3 rounded-full mb-2">
                                    <i class="fas fa-ticket-alt text-yellow-500"></i>
                                </div>
                                <span class="text-sm font-medium">Abrir Chamado</span>
                            </a>
                            <a href="documentos.php?action=solicitar" class="flex flex-col items-center justify-center bg-purple-50 hover:bg-purple-100 transition-all p-4 rounded-lg">
                                <div class="bg-purple-100 p-3 rounded-full mb-2">
                                    <i class="fas fa-file-alt text-purple-500"></i>
                                </div>
                                <span class="text-sm font-medium">Solicitar Documento</span>
                            </a>
                            <a href="alunos.php" class="flex flex-col items-center justify-center bg-green-50 hover:bg-green-100 transition-all p-4 rounded-lg">
                                <div class="bg-green-100 p-3 rounded-full mb-2">
                                    <i class="fas fa-search text-green-500"></i>
                                </div>
                                <span class="text-sm font-medium">Buscar Alunos</span>
                            </a>
                        </div>
                    </div>
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
