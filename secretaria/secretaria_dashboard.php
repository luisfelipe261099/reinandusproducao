<?php
/**
 * Dashboard da Secretaria Acadêmica
 */

// Inicializa o sistema
require_once __DIR__ . '/includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica se o usuário tem permissão para acessar o módulo de secretaria
exigirPermissao('secretaria');

// Instancia o banco de dados
$db = Database::getInstance();

// Função para executar consultas com tratamento de erro
function executarConsulta($db, $sql, $params = [], $default = null) {
    try {
        return $db->fetchOne($sql, $params);
    } catch (Exception $e) {
        // Registra o erro no log
        error_log('Erro na consulta SQL: ' . $e->getMessage());
        return $default;
    }
}

function executarConsultaAll($db, $sql, $params = [], $default = []) {
    try {
        return $db->fetchAll($sql, $params) ?: $default;
    } catch (Exception $e) {
        // Registra o erro no log
        error_log('Erro na consulta SQL: ' . $e->getMessage());
        return $default;
    }
}

// Carrega os dados para o dashboard
try {
    // Estatísticas gerais
    $stats = [];

    // Total de alunos
    $sql = "SELECT COUNT(*) as total FROM alunos WHERE status = 'ativo'";
    $resultado = executarConsulta($db, $sql);
    $stats['total_alunos'] = $resultado['total'] ?? 0;

    // Total de matrículas ativas
    $sql = "SELECT COUNT(*) as total FROM matriculas WHERE status = 'ativo'";
    $resultado = executarConsulta($db, $sql);
    $stats['matriculas_ativas'] = $resultado['total'] ?? 0;

    // Total de documentos pendentes
    $sql = "SELECT COUNT(*) as total FROM solicitacoes_documentos WHERE status = 'solicitado'";
    $resultado = executarConsulta($db, $sql);
    $stats['documentos_pendentes'] = $resultado['total'] ?? 0;

    // Total de chamados abertos (sistema interno)
    $sql = "SELECT COUNT(*) as total FROM chamados WHERE status IN ('aberto', 'em_andamento')";
    $resultado = executarConsulta($db, $sql);
    $stats['chamados_abertos'] = $resultado['total'] ?? 0;

    // Total de solicitações do site (externas)
    $sql = "SELECT COUNT(*) as total FROM solicitacoes_s WHERE status IN ('Pendente', 'Em Andamento')";
    $resultado = executarConsulta($db, $sql);
    $stats['solicitacoes_s'] = $resultado['total'] ?? 0;

    // Total de turmas ativas
    $sql = "SELECT COUNT(*) as total FROM turmas WHERE status IN ('planejada', 'em_andamento')";
    $resultado = executarConsulta($db, $sql);
    $stats['turmas_ativas'] = $resultado['total'] ?? 0;

    // Novos alunos este mês
    $sql = "SELECT COUNT(*) as total FROM alunos WHERE status = 'ativo' AND DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    $resultado = executarConsulta($db, $sql);
    $stats['novos_alunos_mes'] = $resultado['total'] ?? 0;

    // Documentos emitidos hoje
    $sql = "SELECT COUNT(*) as total FROM solicitacoes_documentos WHERE status = 'emitido' AND DATE(data_emissao) = CURDATE()";
    $resultado = executarConsulta($db, $sql);
    $stats['documentos_hoje'] = $resultado['total'] ?? 0;

    // Solicitações processadas hoje (sistema + site)
    $sql = "SELECT
                (SELECT COUNT(*) FROM chamados WHERE status = 'resolvido' AND DATE(data_resolucao) = CURDATE()) +
                (SELECT COUNT(*) FROM solicitacoes_s WHERE status = 'Processado' AND DATE(data_solicitacao) = CURDATE()) as total";
    $resultado = executarConsulta($db, $sql);
    $stats['chamados_resolvidos_hoje'] = $resultado['total'] ?? 0;

    // Solicitações do site pendentes há mais de 3 dias
    $sql = "SELECT COUNT(*) as total FROM solicitacoes_s
            WHERE status IN ('Pendente', 'Em Andamento') AND DATEDIFF(CURDATE(), data_solicitacao) > 3";
    $resultado = executarConsulta($db, $sql);
    $stats['solicitacoes_s_atrasadas'] = $resultado['total'] ?? 0;

    // Polos próximos do limite de documentos
    $sql = "SELECT id, nome, limite_documentos, documentos_emitidos,
                  (documentos_emitidos / limite_documentos * 100) as percentual_usado
           FROM polos
           WHERE status = 'ativo' AND limite_documentos > 0
           ORDER BY percentual_usado DESC
           LIMIT 5";
    $polos_limite = executarConsultaAll($db, $sql);

    // Pendências da secretaria
    $tarefas = [];

    // Documentos pendentes
    $sql = "SELECT sd.id, sd.tipo_documento_id, sd.data_solicitacao, 'documento' as tipo,
                   CONCAT('Solicitação de ', td.nome) as descricao, a.nome as aluno_nome,
                   DATEDIFF(CURDATE(), sd.data_solicitacao) as dias_pendente
            FROM solicitacoes_documentos sd
            JOIN alunos a ON sd.aluno_id = a.id
            JOIN tipos_documentos td ON sd.tipo_documento_id = td.id
            WHERE sd.status = 'solicitado'
            ORDER BY sd.data_solicitacao ASC
            LIMIT 5";
    $documentos_pendentes = executarConsultaAll($db, $sql);
    $tarefas = array_merge($tarefas, $documentos_pendentes);

    // Chamados urgentes (sistema interno)
    $sql = "SELECT c.id, c.data_abertura as data_solicitacao, 'chamado' as tipo,
                   CONCAT('Chamado: ', c.assunto) as descricao,
                   COALESCE(a.nome, c.nome_solicitante) as aluno_nome,
                   c.prioridade, c.categoria,
                   DATEDIFF(CURDATE(), c.data_abertura) as dias_pendente
            FROM chamados c
            LEFT JOIN alunos a ON c.aluno_id = a.id
            WHERE c.status IN ('aberto', 'em_andamento') AND c.prioridade IN ('alta', 'urgente')
            ORDER BY
                CASE c.prioridade
                    WHEN 'urgente' THEN 1
                    WHEN 'alta' THEN 2
                    ELSE 3
                END,
                c.data_abertura ASC
            LIMIT 3";
    $chamados_urgentes = executarConsultaAll($db, $sql);
    $tarefas = array_merge($tarefas, $chamados_urgentes);

    // Solicitações do site (externas)
    $sql = "SELECT ss.id, ss.data_solicitacao, 'solicitacao_site' as tipo,
                   CONCAT('Site: ', ss.tipo_solicitacao, ' - ', ss.nome_empresa) as descricao,
                   ss.nome_solicitante as aluno_nome,
                   ss.tipo_solicitacao as categoria,
                   DATEDIFF(CURDATE(), ss.data_solicitacao) as dias_pendente,
                   CASE
                       WHEN DATEDIFF(CURDATE(), ss.data_solicitacao) > 7 THEN 'urgente'
                       WHEN DATEDIFF(CURDATE(), ss.data_solicitacao) > 3 THEN 'alta'
                       ELSE 'normal'
                   END as prioridade
            FROM solicitacoes_s ss
            WHERE ss.status IN ('Pendente', 'Em Andamento')
            ORDER BY
                CASE
                    WHEN DATEDIFF(CURDATE(), ss.data_solicitacao) > 7 THEN 1
                    WHEN DATEDIFF(CURDATE(), ss.data_solicitacao) > 3 THEN 2
                    ELSE 3
                END,
                ss.data_solicitacao ASC
            LIMIT 3";
    $solicitacoes_s = executarConsultaAll($db, $sql);
    $tarefas = array_merge($tarefas, $solicitacoes_s);

    // Matrículas recentes que precisam de revisão
    $sql = "SELECT m.id, m.data_matricula, 'matricula' as tipo,
                   'Matrícula para revisão' as descricao, a.nome as aluno_nome,
                   DATEDIFF(CURDATE(), m.data_matricula) as dias_pendente
            FROM matriculas m
            JOIN alunos a ON m.aluno_id = a.id
            WHERE m.status = 'pendente'
            ORDER BY m.data_matricula DESC
            LIMIT 5";
    $matriculas_pendentes = executarConsultaAll($db, $sql);
    $tarefas = array_merge($tarefas, $matriculas_pendentes);

    // Ordena as tarefas por data
    usort($tarefas, function($a, $b) {
        $data_a = strtotime($a['data_solicitacao'] ?? $a['data_matricula']);
        $data_b = strtotime($b['data_solicitacao'] ?? $b['data_matricula']);
        return $data_a - $data_b;
    });

    // Limita a 5 tarefas
    $tarefas = array_slice($tarefas, 0, 5);

    // Atividades recentes
    $sql = "SELECT l.id, l.usuario_id, l.modulo, l.acao, l.descricao, l.created_at,
                   u.nome as usuario_nome
            FROM logs l
            LEFT JOIN usuarios u ON l.usuario_id = u.id
            ORDER BY l.created_at DESC
            LIMIT 10";
    $atividades_raw = executarConsultaAll($db, $sql);

    // Formata as datas das atividades
    $atividades = [];
    foreach ($atividades_raw as $atividade) {
        $data = strtotime($atividade['created_at']);
        $atividade['data_formatada'] = date('d/m/Y H:i', $data);
        $atividades[] = $atividade;
    }

    // Chamados recentes por categoria
    $sql = "SELECT categoria, COUNT(*) as total,
                   AVG(DATEDIFF(COALESCE(data_resolucao, CURDATE()), data_abertura)) as tempo_medio_resolucao
            FROM chamados
            WHERE data_abertura >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY categoria
            ORDER BY total DESC
            LIMIT 5";
    $chamados_por_categoria = executarConsultaAll($db, $sql);

    // Próximos eventos
    $eventos = [];

    // Verifica se a tabela eventos existe
    $sql = "SHOW TABLES LIKE 'eventos'";
    $tabela_existe = $db->fetchOne($sql);

    if ($tabela_existe) {
        $sql = "SELECT id, titulo, data_inicio, local, tipo
                FROM eventos
                WHERE data_inicio >= CURRENT_DATE()
                ORDER BY data_inicio ASC
                LIMIT 5";
        $eventos_raw = executarConsultaAll($db, $sql);

        // Formata as datas dos eventos
        foreach ($eventos_raw as $evento) {
            $data = strtotime($evento['data_inicio']);
            $evento['dia'] = date('d', $data);
            $evento['mes'] = date('M', $data);
            $evento['data_formatada'] = date('d/m/Y', $data);
            $eventos[] = $evento;
        }
    }

    // Alertas do sistema
    $alertas = [];

    // Alerta de documentos pendentes há mais de 7 dias
    if ($stats['documentos_pendentes'] > 0) {
        $sql = "SELECT COUNT(*) as total FROM solicitacoes_documentos
                WHERE status = 'solicitado' AND DATEDIFF(CURDATE(), data_solicitacao) > 7";
        $resultado = executarConsulta($db, $sql);
        $docs_atrasados = $resultado['total'] ?? 0;

        if ($docs_atrasados > 0) {
            $alertas[] = [
                'tipo' => 'warning',
                'titulo' => 'Documentos Atrasados',
                'mensagem' => "{$docs_atrasados} documento(s) pendente(s) há mais de 7 dias",
                'link' => 'documentos.php?status=atrasados'
            ];
        }
    }

    // Alerta de chamados urgentes (sistema)
    if ($stats['chamados_abertos'] > 0) {
        $sql = "SELECT COUNT(*) as total FROM chamados
                WHERE status IN ('aberto', 'em_andamento') AND prioridade = 'urgente'";
        $resultado = executarConsulta($db, $sql);
        $chamados_urgentes_count = $resultado['total'] ?? 0;

        if ($chamados_urgentes_count > 0) {
            $alertas[] = [
                'tipo' => 'danger',
                'titulo' => 'Chamados Urgentes (Sistema)',
                'mensagem' => "{$chamados_urgentes_count} chamado(s) urgente(s) aguardando atendimento",
                'link' => 'chamados.php?prioridade=urgente'
            ];
        }
    }

    // Alerta de solicitações do site atrasadas
    if ($stats['solicitacoes_s_atrasadas'] > 0) {
        $alertas[] = [
            'tipo' => 'warning',
            'titulo' => 'Solicitações do Site Atrasadas',
            'mensagem' => "{$stats['solicitacoes_s_atrasadas']} solicitação(ões) do site pendente(s) há mais de 3 dias",
            'link' => 'chamados/index.php?view=chamados_site&status=atrasadas'
        ];
    }

    // Alerta de solicitações do site urgentes
    if ($stats['solicitacoes_s'] > 0) {
        $sql = "SELECT COUNT(*) as total FROM solicitacoes_s
                WHERE status IN ('Pendente', 'Em Andamento') AND DATEDIFF(CURDATE(), data_solicitacao) > 7";
        $resultado = executarConsulta($db, $sql);
        $solicitacoes_s_urgentes = $resultado['total'] ?? 0;

        if ($solicitacoes_s_urgentes > 0) {
            $alertas[] = [
                'tipo' => 'danger',
                'titulo' => 'Solicitações Urgentes do Site',
                'mensagem' => "{$solicitacoes_s_urgentes} solicitação(ões) urgente(s) do site (mais de 7 dias)",
                'link' => 'chamados/index.php?view=chamados_site&status=urgentes'
            ];
        }
    }
} catch (Exception $e) {
    error_log('Erro ao carregar dados para o dashboard: ' . $e->getMessage());
}

// Define o título da página
$titulo_pagina = 'Dashboard da Secretaria';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faciência ERP - <?php echo $titulo_pagina; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/secretaria.css">
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

        .task-card {
            border-left: 4px solid;
            background-color: white;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        }

        .task-card.urgent {
            border-left-color: #EF4444;
        }

        .task-card.important {
            border-left-color: #F59E0B;
        }

        .task-card.normal {
            border-left-color: #3B82F6;
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
    <div class="flex h-screen bg-gray-100">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col ml-64">
            <!-- Header -->
            <?php include 'includes/header.php'; ?>

            <!-- Main -->
            <main class="flex-1 overflow-y-auto p-6">
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
                        <p class="text-gray-600">Bem-vind<?php echo (isset($_SESSION['user_gender']) && $_SESSION['user_gender'] == 'feminino') ? 'a' : 'o'; ?>, <?php echo explode(' ', $_SESSION['user_name'] ?? 'Usuário')[0]; ?>! Aqui está um resumo das atividades e pendências do dia.</p>
                    </div>

                    <!-- Alertas do Sistema -->
                    <?php if (!empty($alertas)): ?>
                    <div class="mb-6">
                        <?php foreach ($alertas as $alerta): ?>
                        <div class="bg-<?php echo $alerta['tipo'] === 'danger' ? 'red' : 'yellow'; ?>-50 border-l-4 border-<?php echo $alerta['tipo'] === 'danger' ? 'red' : 'yellow'; ?>-400 p-4 mb-4">
                            <div class="flex items-center justify-between">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-<?php echo $alerta['tipo'] === 'danger' ? 'exclamation-triangle' : 'exclamation-circle'; ?> text-<?php echo $alerta['tipo'] === 'danger' ? 'red' : 'yellow'; ?>-400"></i>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-<?php echo $alerta['tipo'] === 'danger' ? 'red' : 'yellow'; ?>-800">
                                            <?php echo $alerta['titulo']; ?>
                                        </p>
                                        <p class="text-sm text-<?php echo $alerta['tipo'] === 'danger' ? 'red' : 'yellow'; ?>-700 mt-1">
                                            <?php echo $alerta['mensagem']; ?>
                                        </p>
                                    </div>
                                </div>
                                <a href="<?php echo $alerta['link']; ?>" class="text-<?php echo $alerta['tipo'] === 'danger' ? 'red' : 'yellow'; ?>-600 hover:text-<?php echo $alerta['tipo'] === 'danger' ? 'red' : 'yellow'; ?>-900 text-sm font-medium">
                                    Ver detalhes →
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Stats Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
                        <div class="card bg-white p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Total de Alunos</p>
                                    <p class="text-3xl font-bold text-gray-800 mt-1" id="total-alunos"><?php echo number_format($stats['total_alunos'], 0, ',', '.'); ?></p>
                                </div>
                                <div class="bg-blue-100 p-3 rounded-full">
                                    <i class="fas fa-user-graduate text-blue-500"></i>
                                </div>
                            </div>
                            <div class="mt-4 flex items-center justify-between">
                                <div>
                                    <span class="text-green-500 text-sm font-medium flex items-center">
                                        <i class="fas fa-plus mr-1"></i> <?php echo $stats['novos_alunos_mes']; ?> novos
                                    </span>
                                    <span class="text-gray-500 text-xs">Este mês</span>
                                </div>
                                <a href="alunos.php" class="text-blue-600 text-sm hover:underline">Ver todos</a>
                            </div>
                        </div>

                        <div class="card bg-white p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Chamados Sistema</p>
                                    <p class="text-3xl font-bold text-gray-800 mt-1" id="chamados-abertos"><?php echo number_format($stats['chamados_abertos'], 0, ',', '.'); ?></p>
                                </div>
                                <div class="bg-orange-100 p-3 rounded-full">
                                    <i class="fas fa-headset text-orange-500"></i>
                                </div>
                            </div>
                            <div class="mt-4 flex items-center justify-between">
                                <div>
                                    <span class="text-green-500 text-sm font-medium flex items-center">
                                        <i class="fas fa-check mr-1"></i> Resolvidos hoje
                                    </span>
                                    <span class="text-gray-500 text-xs"><?php echo $stats['chamados_resolvidos_hoje']; ?> total</span>
                                </div>
                                <a href="chamados.php" class="text-orange-600 text-sm hover:underline">Gerenciar</a>
                            </div>
                        </div>

                        <div class="card bg-white p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Solicitações Site</p>
                                    <p class="text-3xl font-bold text-gray-800 mt-1" id="solicitacoes-site"><?php echo number_format($stats['solicitacoes_s'], 0, ',', '.'); ?></p>
                                </div>
                                <div class="bg-purple-100 p-3 rounded-full">
                                    <i class="fas fa-globe text-purple-500"></i>
                                </div>
                            </div>
                            <div class="mt-4 flex items-center justify-between">
                                <div>
                                    <?php if ($stats['solicitacoes_s_atrasadas'] > 0): ?>
                                    <span class="text-red-500 text-sm font-medium flex items-center">
                                        <i class="fas fa-exclamation-triangle mr-1"></i> <?php echo $stats['solicitacoes_s_atrasadas']; ?> atrasadas
                                    </span>
                                    <span class="text-gray-500 text-xs">Mais de 3 dias</span>
                                    <?php else: ?>
                                    <span class="text-green-500 text-sm font-medium flex items-center">
                                        <i class="fas fa-check mr-1"></i> Em dia
                                    </span>
                                    <span class="text-gray-500 text-xs">Nenhuma atrasada</span>
                                    <?php endif; ?>
                                </div>
                                <a href="chamados/index.php?view=chamados_site" class="text-purple-600 text-sm hover:underline">Gerenciar</a>
                            </div>
                        </div>

                        <div class="card bg-white p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Documentos Pendentes</p>
                                    <p class="text-3xl font-bold text-gray-800 mt-1" id="documentos-pendentes"><?php echo number_format($stats['documentos_pendentes'], 0, ',', '.'); ?></p>
                                </div>
                                <div class="bg-yellow-100 p-3 rounded-full">
                                    <i class="fas fa-certificate text-yellow-500"></i>
                                </div>
                            </div>
                            <div class="mt-4 flex items-center justify-between">
                                <div>
                                    <span class="text-blue-500 text-sm font-medium flex items-center">
                                        <i class="fas fa-file-check mr-1"></i> <?php echo $stats['documentos_hoje']; ?> emitidos
                                    </span>
                                    <span class="text-gray-500 text-xs">Hoje</span>
                                </div>
                                <a href="documentos.php" class="text-yellow-600 text-sm hover:underline">Processar</a>
                            </div>
                        </div>

                        <div class="card bg-white p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Matrículas Ativas</p>
                                    <p class="text-3xl font-bold text-gray-800 mt-1" id="matriculas-ativas"><?php echo number_format($stats['matriculas_ativas'], 0, ',', '.'); ?></p>
                                </div>
                                <div class="bg-green-100 p-3 rounded-full">
                                    <i class="fas fa-file-alt text-green-500"></i>
                                </div>
                            </div>
                            <div class="mt-4 flex items-center justify-between">
                                <div>
                                    <span class="text-purple-500 text-sm font-medium flex items-center">
                                        <i class="fas fa-users mr-1"></i> <?php echo $stats['turmas_ativas']; ?> turmas
                                    </span>
                                    <span class="text-gray-500 text-xs">Ativas</span>
                                </div>
                                <a href="matriculas.php" class="text-green-600 text-sm hover:underline">Ver todas</a>
                            </div>
                        </div>
                    </div>

                    <!-- Two Column Layout -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <!-- Tasks Column -->
                        <div class="lg:col-span-2">
                            <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                                <div class="flex justify-between items-center mb-6">
                                    <h2 class="text-lg font-bold text-gray-800">Pendências da Secretaria</h2>
                                    <div class="flex space-x-2">
                                        <button class="filter-btn px-3 py-1 bg-gray-100 rounded-md text-gray-700 text-sm hover:bg-gray-200 transition-colors">Todas</button>
                                        <button class="filter-btn px-3 py-1 bg-white rounded-md text-gray-700 text-sm hover:bg-gray-100 transition-colors border border-gray-200">Urgentes</button>
                                        <button class="filter-btn px-3 py-1 bg-white rounded-md text-gray-700 text-sm hover:bg-gray-100 transition-colors border border-gray-200">Resolvidas</button>
                                    </div>
                                </div>
                                <div class="space-y-4" id="pendencias-container">
                                    <?php if (empty($tarefas)): ?>
                                    <div class="text-center text-gray-500 py-8">
                                        <i class="fas fa-check-circle text-4xl text-green-300 mb-3"></i>
                                        <p class="text-lg font-medium">Parabéns!</p>
                                        <p>Não há pendências no momento.</p>
                                    </div>
                                    <?php else: ?>
                                        <?php foreach ($tarefas as $tarefa):
                                            // Determina a classe da tarefa com base no tipo e prioridade
                                            $classeCartao = 'normal';
                                            $classeBadge = 'badge-primary';
                                            $textoTipo = 'Normal';
                                            $icone = 'fa-file-alt';
                                            $link = '#';

                                            if ($tarefa['tipo'] === 'documento') {
                                                $diasPendente = $tarefa['dias_pendente'] ?? 0;
                                                if ($diasPendente > 7) {
                                                    $classeCartao = 'urgent';
                                                    $classeBadge = 'badge-danger';
                                                    $textoTipo = 'Urgente';
                                                } else if ($diasPendente > 3) {
                                                    $classeCartao = 'important';
                                                    $classeBadge = 'badge-warning';
                                                    $textoTipo = 'Importante';
                                                }
                                                $icone = 'fa-certificate';
                                                $link = 'documentos.php?action=processar&id=' . $tarefa['id'];
                                            } else if ($tarefa['tipo'] === 'chamado') {
                                                $prioridade = $tarefa['prioridade'] ?? 'normal';
                                                if ($prioridade === 'urgente') {
                                                    $classeCartao = 'urgent';
                                                    $classeBadge = 'badge-danger';
                                                    $textoTipo = 'Urgente';
                                                } else if ($prioridade === 'alta') {
                                                    $classeCartao = 'important';
                                                    $classeBadge = 'badge-warning';
                                                    $textoTipo = 'Alta';
                                                }
                                                $icone = 'fa-headset';
                                                $link = 'chamados.php?action=visualizar&id=' . $tarefa['id'];
                                            } else if ($tarefa['tipo'] === 'solicitacao_site') {
                                                $prioridade = $tarefa['prioridade'] ?? 'normal';
                                                $diasPendente = $tarefa['dias_pendente'] ?? 0;

                                                if ($prioridade === 'urgente') {
                                                    $classeCartao = 'urgent';
                                                    $classeBadge = 'badge-danger';
                                                    $textoTipo = 'Urgente';
                                                } else if ($prioridade === 'alta' || $diasPendente > 3) {
                                                    $classeCartao = 'important';
                                                    $classeBadge = 'badge-warning';
                                                    $textoTipo = $diasPendente > 3 ? 'Atrasada' : 'Alta';
                                                }
                                                $icone = 'fa-globe';
                                                $link = 'chamados/index.php?view=chamados_site&protocolo=' . $tarefa['id'];
                                            } else if ($tarefa['tipo'] === 'matricula') {
                                                $classeCartao = 'important';
                                                $classeBadge = 'badge-warning';
                                                $textoTipo = 'Importante';
                                                $icone = 'fa-file-alt';
                                                $link = 'matriculas.php?action=revisar&id=' . $tarefa['id'];
                                            }

                                            // Formata a data
                                            $data = strtotime($tarefa['data_solicitacao'] ?? $tarefa['data_matricula']);
                                            $dataFormatada = date('d/m/Y', $data);
                                            $diasPendente = $tarefa['dias_pendente'] ?? 0;
                                        ?>
                                        <div class="task-card <?php echo $classeCartao; ?>">
                                            <div class="flex justify-between items-start">
                                                <div class="flex items-start space-x-3">
                                                    <div class="flex-shrink-0 mt-1">
                                                        <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center">
                                                            <i class="fas <?php echo $icone; ?> text-gray-500 text-sm"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-1">
                                                        <div class="flex items-center">
                                                            <h3 class="font-semibold text-gray-900"><?php echo htmlspecialchars($tarefa['descricao']); ?></h3>
                                                            <span class="badge <?php echo $classeBadge; ?> ml-3"><?php echo $textoTipo; ?></span>
                                                        </div>
                                                        <p class="text-gray-600 text-sm mt-1">
                                                            <i class="fas fa-user mr-1"></i>
                                                            <?php echo htmlspecialchars($tarefa['aluno_nome']); ?>
                                                        </p>
                                                        <?php if (isset($tarefa['categoria'])): ?>
                                                        <p class="text-gray-500 text-xs mt-1">
                                                            <i class="fas fa-tag mr-1"></i>
                                                            Categoria: <?php echo htmlspecialchars($tarefa['categoria']); ?>
                                                        </p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="flex items-center space-x-2">
                                                    <?php if ($diasPendente > 0): ?>
                                                    <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">
                                                        <?php echo $diasPendente; ?> dia<?php echo $diasPendente > 1 ? 's' : ''; ?>
                                                    </span>
                                                    <?php endif; ?>
                                                    <button class="text-gray-400 hover:text-gray-600">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="flex items-center justify-between mt-4">
                                                <div class="flex items-center text-sm text-gray-500">
                                                    <i class="far fa-clock mr-2"></i>
                                                    <span><?php echo $dataFormatada; ?></span>
                                                </div>
                                                <a href="<?php echo $link; ?>" class="btn-primary px-4 py-2 rounded-lg text-sm hover:bg-blue-700 transition-colors">
                                                    <i class="fas fa-arrow-right mr-1"></i>
                                                    Processar
                                                </a>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <div class="mt-6 text-center">
                                    <a href="chamados/index.php?view=solicitacoes&status=solicitado" class="text-blue-600 text-sm font-medium hover:underline">
                                        Ver todas as solicitações pendentes
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Sidebar Content -->
                        <div class="lg:col-span-1 space-y-6">
                            <!-- Estatísticas de Chamados -->
                            <?php if (!empty($chamados_por_categoria)): ?>
                            <div class="bg-white rounded-xl shadow-sm p-6">
                                <h2 class="text-lg font-bold text-gray-800 mb-4">
                                    <i class="fas fa-chart-pie mr-2 text-orange-500"></i>
                                    Chamados por Categoria
                                </h2>

                                <div class="space-y-3">
                                    <?php foreach ($chamados_por_categoria as $categoria): ?>
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <div class="w-3 h-3 rounded-full bg-orange-400 mr-3"></div>
                                            <span class="text-sm font-medium text-gray-700">
                                                <?php echo htmlspecialchars(ucfirst($categoria['categoria'])); ?>
                                            </span>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-sm font-bold text-gray-900"><?php echo $categoria['total']; ?></span>
                                            <p class="text-xs text-gray-500">
                                                ~<?php echo round($categoria['tempo_medio_resolucao']); ?> dias
                                            </p>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>

                                <div class="mt-4 text-center">
                                    <a href="chamados.php?view=relatorios" class="text-orange-600 text-sm font-medium hover:underline">
                                        Ver relatório completo
                                    </a>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Upcoming Events -->
                            <div class="bg-white rounded-xl shadow-sm p-6">
                                <h2 class="text-lg font-bold text-gray-800 mb-4">
                                    <i class="fas fa-calendar-alt mr-2 text-blue-500"></i>
                                    Próximos Eventos
                                </h2>

                                <div class="space-y-4">
                                    <?php if (empty($eventos)): ?>
                                    <div class="text-center text-gray-500 py-4">
                                        <i class="fas fa-calendar-times text-2xl text-gray-300 mb-2"></i>
                                        <p>Não há eventos próximos.</p>
                                    </div>
                                    <?php else: ?>
                                        <?php foreach ($eventos as $evento):
                                            // Define cores baseadas no tipo de evento
                                            $cores_tipo = [
                                                'academico' => 'blue',
                                                'administrativo' => 'purple',
                                'reuniao' => 'green',
                                'evento' => 'yellow',
                                'feriado' => 'red'
                            ];
                            $cor = $cores_tipo[$evento['tipo'] ?? 'evento'] ?? 'blue';
                                        ?>
                                        <div class="flex items-start hover:bg-gray-50 p-2 rounded-lg transition-colors">
                                            <div class="bg-<?php echo $cor; ?>-100 rounded-lg p-3 text-center mr-4 flex-shrink-0">
                                                <p class="text-<?php echo $cor; ?>-700 font-bold text-lg"><?php echo $evento['dia']; ?></p>
                                                <p class="text-<?php echo $cor; ?>-700 text-xs uppercase"><?php echo $evento['mes']; ?></p>
                                            </div>
                                            <div class="flex-1">
                                                <h3 class="font-medium text-gray-900"><?php echo htmlspecialchars($evento['titulo']); ?></h3>
                                                <p class="text-gray-500 text-sm">
                                                    <i class="fas fa-map-marker-alt mr-1"></i>
                                                    <?php echo htmlspecialchars($evento['local']); ?>
                                                </p>
                                                <p class="text-gray-400 text-xs mt-1">
                                                    <?php echo $evento['data_formatada']; ?>
                                                </p>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>

                                <div class="mt-4 text-center">
                                    <a href="eventos.php" class="text-blue-600 text-sm font-medium hover:underline">
                                        <i class="fas fa-calendar mr-1"></i>
                                        Ver calendário completo
                                    </a>
                                </div>
                            </div>

                            <!-- Polos próximos do limite de documentos -->
                            <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                                <h2 class="text-lg font-bold text-gray-800 mb-4">Limite de Documentos</h2>

                                <div class="space-y-4">
                                    <?php if (empty($polos_limite)): ?>
                                    <div class="text-center text-gray-500 py-4">Nenhum polo próximo do limite.</div>
                                    <?php else: ?>
                                        <?php foreach ($polos_limite as $polo):
                                            // Define a cor com base no percentual usado
                                            $percentual = $polo['percentual_usado'];
                                            $cor = 'green';
                                            $alerta = 'Normal';

                                            if ($percentual >= 90) {
                                                $cor = 'red';
                                                $alerta = 'Crítico';
                                            } elseif ($percentual >= 75) {
                                                $cor = 'yellow';
                                                $alerta = 'Atenção';
                                            } elseif ($percentual >= 50) {
                                                $cor = 'blue';
                                                $alerta = 'Moderado';
                                            }
                                        ?>
                                        <div class="border-b pb-3">
                                            <div class="flex justify-between items-center mb-2">
                                                <a href="polos.php?action=visualizar&id=<?php echo $polo['id']; ?>" class="font-medium hover:text-blue-600">
                                                    <?php echo htmlspecialchars($polo['nome']); ?>
                                                </a>
                                                <span class="badge badge-<?php echo $cor === 'green' ? 'success' : ($cor === 'red' ? 'danger' : ($cor === 'yellow' ? 'warning' : 'primary')); ?>">
                                                    <?php echo $alerta; ?>
                                                </span>
                                            </div>
                                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                                <div class="bg-<?php echo $cor; ?>-500 h-2.5 rounded-full" style="width: <?php echo min(100, $percentual); ?>%"></div>
                                            </div>
                                            <div class="flex justify-between text-xs text-gray-500 mt-1">
                                                <span><?php echo number_format($polo['documentos_emitidos'], 0, ',', '.'); ?> emitidos</span>
                                                <span><?php echo number_format($percentual, 0); ?>% usado</span>
                                                <span>Limite: <?php echo number_format($polo['limite_documentos'], 0, ',', '.'); ?></span>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>

                                <div class="mt-4 text-center">
                                    <a href="polos.php" class="text-blue-600 text-sm font-medium hover:underline">
                                        Gerenciar polos
                                    </a>
                                </div>
                            </div>

                            <!-- Recent Activity -->
                            <div class="bg-white rounded-xl shadow-sm p-6">
                                <h2 class="text-lg font-bold text-gray-800 mb-4">Atividades Recentes</h2>

                                <div class="space-y-4" id="atividades-container">
                                    <?php if (empty($atividades)): ?>
                                    <div class="text-center text-gray-500 py-4">Não há atividades recentes.</div>
                                    <?php else: ?>
                                        <?php
                                        $count = 0;
                                        foreach ($atividades as $atividade):
                                            $count++;
                                            // Determina o ícone com base no módulo
                                            $icone = 'fa-file-alt';
                                            $corIcone = 'blue';

                                            if ($atividade['modulo'] === 'alunos') {
                                                $icone = 'fa-user-graduate';
                                                $corIcone = 'blue';
                                            } else if ($atividade['modulo'] === 'matriculas') {
                                                $icone = 'fa-file-alt';
                                                $corIcone = 'green';
                                            } else if ($atividade['modulo'] === 'documentos') {
                                                $icone = 'fa-certificate';
                                                $corIcone = 'yellow';
                                            } else if ($atividade['modulo'] === 'usuarios') {
                                                $icone = 'fa-user';
                                                $corIcone = 'purple';
                                            }
                                        ?>
                                        <div class="flex items-start">
                                            <div class="relative mr-3">
                                                <div class="w-8 h-8 rounded-full bg-<?php echo $corIcone; ?>-100 flex items-center justify-center">
                                                    <i class="fas <?php echo $icone; ?> text-<?php echo $corIcone; ?>-500 text-sm"></i>
                                                </div>
                                            </div>
                                            <div>
                                                <p class="text-sm">
                                                    <span class="font-medium"><?php echo htmlspecialchars($atividade['usuario_nome'] ?? 'Sistema'); ?></span>
                                                    <span class="text-gray-600"><?php echo htmlspecialchars($atividade['descricao']); ?></span>
                                                </p>
                                                <p class="text-xs text-gray-500 mt-1"><?php echo $atividade['data_formatada']; ?></p>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>

                                <div class="mt-4 text-center">
                                    <button class="text-primary-600 text-sm font-medium hover:underline">
                                        Ver todas as atividades
                                    </button>
                                </div>
                            </div>

                            <!-- Quick Actions -->
                            <div class="bg-white rounded-xl shadow-sm p-6">
                                <h2 class="text-lg font-bold text-gray-800 mb-4">
                                    <i class="fas fa-bolt mr-2 text-yellow-500"></i>
                                    Ações Rápidas
                                </h2>

                                <div class="grid grid-cols-2 lg:grid-cols-3 gap-3">
                                    <a href="alunos.php?action=novo" class="group flex flex-col items-center justify-center bg-blue-50 hover:bg-blue-100 transition-all p-4 rounded-lg border border-blue-200 hover:border-blue-300">
                                        <div class="bg-blue-100 group-hover:bg-blue-200 p-3 rounded-full mb-2 transition-colors">
                                            <i class="fas fa-user-plus text-blue-500"></i>
                                        </div>
                                        <span class="text-sm font-medium text-blue-700">Novo Aluno</span>
                                    </a>

                                    <a href="chamados.php?action=novo" class="group flex flex-col items-center justify-center bg-orange-50 hover:bg-orange-100 transition-all p-4 rounded-lg border border-orange-200 hover:border-orange-300">
                                        <div class="bg-orange-100 group-hover:bg-orange-200 p-3 rounded-full mb-2 transition-colors">
                                            <i class="fas fa-headset text-orange-500"></i>
                                        </div>
                                        <span class="text-sm font-medium text-orange-700">Chamado Sistema</span>
                                    </a>

                                    <a href="chamados/index.php?view=chamados_site" class="group flex flex-col items-center justify-center bg-purple-50 hover:bg-purple-100 transition-all p-4 rounded-lg border border-purple-200 hover:border-purple-300">
                                        <div class="bg-purple-100 group-hover:bg-purple-200 p-3 rounded-full mb-2 transition-colors">
                                            <i class="fas fa-globe text-purple-500"></i>
                                        </div>
                                        <span class="text-sm font-medium text-purple-700">Solicitações Site</span>
                                    </a>

                                    <a href="matriculas.php?action=nova" class="group flex flex-col items-center justify-center bg-green-50 hover:bg-green-100 transition-all p-4 rounded-lg border border-green-200 hover:border-green-300">
                                        <div class="bg-green-100 group-hover:bg-green-200 p-3 rounded-full mb-2 transition-colors">
                                            <i class="fas fa-file-alt text-green-500"></i>
                                        </div>
                                        <span class="text-sm font-medium text-green-700">Nova Matrícula</span>
                                    </a>

                                    <a href="documentos.php?action=emitir" class="group flex flex-col items-center justify-center bg-purple-50 hover:bg-purple-100 transition-all p-4 rounded-lg border border-purple-200 hover:border-purple-300">
                                        <div class="bg-purple-100 group-hover:bg-purple-200 p-3 rounded-full mb-2 transition-colors">
                                            <i class="fas fa-certificate text-purple-500"></i>
                                        </div>
                                        <span class="text-sm font-medium text-purple-700">Emitir Documento</span>
                                    </a>

                                    <a href="alunos.php?action=importar" class="group flex flex-col items-center justify-center bg-indigo-50 hover:bg-indigo-100 transition-all p-4 rounded-lg border border-indigo-200 hover:border-indigo-300">
                                        <div class="bg-indigo-100 group-hover:bg-indigo-200 p-3 rounded-full mb-2 transition-colors">
                                            <i class="fas fa-file-import text-indigo-500"></i>
                                        </div>
                                        <span class="text-sm font-medium text-indigo-700">Importar Alunos</span>
                                    </a>

                                    <a href="busca_avancada.php" class="group flex flex-col items-center justify-center bg-yellow-50 hover:bg-yellow-100 transition-all p-4 rounded-lg border border-yellow-200 hover:border-yellow-300">
                                        <div class="bg-yellow-100 group-hover:bg-yellow-200 p-3 rounded-full mb-2 transition-colors">
                                            <i class="fas fa-search text-yellow-500"></i>
                                        </div>
                                        <span class="text-sm font-medium text-yellow-700">Busca Avançada</span>
                                    </a>
                                </div>

                                <!-- Ações Administrativas -->
                                <div class="mt-6 pt-4 border-t border-gray-200">
                                    <h3 class="text-sm font-semibold text-gray-700 mb-3">Administrativo</h3>
                                    <div class="space-y-2">
                                        <a href="relatorios.php" class="flex items-center p-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg transition-colors">
                                            <i class="fas fa-chart-bar w-4 mr-3 text-gray-400"></i>
                                            Relatórios
                                        </a>
                                        <a href="backup.php" class="flex items-center p-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg transition-colors">
                                            <i class="fas fa-database w-4 mr-3 text-gray-400"></i>
                                            Backup do Sistema
                                        </a>
                                        <a href="configuracoes.php" class="flex items-center p-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg transition-colors">
                                            <i class="fas fa-cog w-4 mr-3 text-gray-400"></i>
                                            Configurações
                                        </a>
                                    </div>
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

    <script src="js/main.js"></script>
    <script>
        // Atualização automática dos dados do dashboard
        document.addEventListener('DOMContentLoaded', function() {
            // Função para atualizar estatísticas
            function atualizarEstatisticas() {
                fetch('api/dashboard_stats.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Atualiza os números nos cards
                            document.getElementById('total-alunos').textContent = data.stats.total_alunos.toLocaleString();
                            document.getElementById('chamados-abertos').textContent = data.stats.chamados_abertos.toLocaleString();
                            document.getElementById('documentos-pendentes').textContent = data.stats.documentos_pendentes.toLocaleString();
                            document.getElementById('matriculas-ativas').textContent = data.stats.matriculas_ativas.toLocaleString();
                        }
                    })
                    .catch(error => console.log('Erro ao atualizar estatísticas:', error));
            }

            // Função para filtrar pendências
            function filtrarPendencias(tipo) {
                const container = document.getElementById('pendencias-container');
                const cards = container.querySelectorAll('.task-card');

                cards.forEach(card => {
                    if (tipo === 'todas') {
                        card.style.display = 'block';
                    } else if (tipo === 'urgentes') {
                        card.style.display = card.classList.contains('urgent') ? 'block' : 'none';
                    } else if (tipo === 'resolvidas') {
                        card.style.display = 'none'; // Não temos resolvidas no dashboard
                    }
                });

                // Atualiza botões ativos
                document.querySelectorAll('.filter-btn').forEach(btn => {
                    btn.classList.remove('bg-gray-100');
                    btn.classList.add('bg-white');
                });
                event.target.classList.remove('bg-white');
                event.target.classList.add('bg-gray-100');
            }

            // Adiciona event listeners para os botões de filtro
            const filterButtons = document.querySelectorAll('.filter-btn');
            filterButtons.forEach((btn, index) => {
                btn.addEventListener('click', function() {
                    const tipos = ['todas', 'urgentes', 'resolvidas'];
                    filtrarPendencias(tipos[index]);
                });
            });

            // Atualiza estatísticas a cada 5 minutos
            setInterval(atualizarEstatisticas, 300000);

            // Adiciona animação aos cards de estatísticas
            const statsCards = document.querySelectorAll('.card');
            statsCards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
                card.classList.add('animate-fade-in');
            });

            // Função para marcar tarefa como concluída
            window.marcarConcluida = function(id, tipo) {
                if (confirm('Marcar esta tarefa como concluída?')) {
                    fetch('api/marcar_concluida.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            id: id,
                            tipo: tipo
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Remove o card da lista
                            document.querySelector(`[data-task-id="${id}"]`).remove();

                            // Mostra notificação de sucesso
                            mostrarNotificacao('Tarefa marcada como concluída!', 'success');

                            // Atualiza estatísticas
                            atualizarEstatisticas();
                        } else {
                            mostrarNotificacao('Erro ao marcar tarefa como concluída', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Erro:', error);
                        mostrarNotificacao('Erro ao marcar tarefa como concluída', 'error');
                    });
                }
            };

            // Função para mostrar notificações
            function mostrarNotificacao(mensagem, tipo) {
                const notification = document.createElement('div');
                notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
                    tipo === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
                }`;
                notification.textContent = mensagem;

                document.body.appendChild(notification);

                // Remove após 3 segundos
                setTimeout(() => {
                    notification.remove();
                }, 3000);
            }

            // Adiciona tooltips aos ícones
            const tooltipElements = document.querySelectorAll('[data-tooltip]');
            tooltipElements.forEach(element => {
                element.addEventListener('mouseenter', function() {
                    const tooltip = document.createElement('div');
                    tooltip.className = 'absolute bg-gray-800 text-white text-xs rounded py-1 px-2 z-50';
                    tooltip.textContent = this.getAttribute('data-tooltip');
                    tooltip.style.top = this.offsetTop - 30 + 'px';
                    tooltip.style.left = this.offsetLeft + 'px';
                    this.parentNode.appendChild(tooltip);
                });

                element.addEventListener('mouseleave', function() {
                    const tooltip = this.parentNode.querySelector('.absolute.bg-gray-800');
                    if (tooltip) {
                        tooltip.remove();
                    }
                });
            });
        });
    </script>

    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animate-fade-in {
            animation: fadeIn 0.6s ease-out forwards;
        }

        .task-card {
            transition: all 0.3s ease;
        }

        .task-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .filter-btn {
            transition: all 0.2s ease;
        }

        .filter-btn:hover {
            transform: translateY(-1px);
        }
    </style>
</body>
</html>
