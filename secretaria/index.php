<?php
/**
 * Dashboard da Secretaria Acadêmica
 */

// Inicializa o sistema
require_once __DIR__ . '/includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica se o usuário tem permissão para acessar o sistema
// Não verificamos um módulo específico aqui, pois esta é a página inicial
// e todos os usuários autenticados devem poder acessá-la
// Se o usuário for do tipo polo, redirecionamos para a página do polo
if (getUsuarioTipo() === 'polo') {
    redirect('polo/index.php');
}

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
    $sql = "SELECT COUNT(*) as total FROM solicitacoes_documentos WHERE status = 'pendente'";
    $resultado = executarConsulta($db, $sql);
    $stats['documentos_pendentes'] = $resultado['total'] ?? 0;

    // Total de chamados abertos (sistema interno)
    $sql = "SELECT COUNT(*) as total FROM chamados WHERE status IN ('aberto', 'em_andamento')";
    $resultado = executarConsulta($db, $sql);
    $stats['chamados_abertos'] = $resultado['total'] ?? 0;

    // Total de solicitações do site (externas)
    $sql = "SELECT COUNT(*) as total FROM solicitacoes_site WHERE status IN ('Pendente', 'Em Andamento')";
    $resultado = executarConsulta($db, $sql);
    $stats['solicitacoes_site'] = $resultado['total'] ?? 0;

    // Total de turmas ativas
    $sql = "SELECT COUNT(*) as total FROM turmas WHERE status IN ('planejada', 'em_andamento')";
    $resultado = executarConsulta($db, $sql);
    $stats['turmas_ativas'] = $resultado['total'] ?? 0;

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
                   'Solicitação de ' || td.nome as descricao, a.nome as aluno_nome
            FROM solicitacoes_documentos sd
            JOIN alunos a ON sd.aluno_id = a.id
            JOIN tipos_documentos td ON sd.tipo_documento_id = td.id
            WHERE sd.status = 'pendente'
            ORDER BY sd.data_solicitacao ASC
            LIMIT 5";
    $documentos_pendentes = executarConsultaAll($db, $sql);
    $tarefas = array_merge($tarefas, $documentos_pendentes);

    // Matrículas recentes que precisam de revisão
    $sql = "SELECT m.id, m.data_matricula, 'matricula' as tipo,
                   'Matrícula para revisão' as descricao, a.nome as aluno_nome
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

    // Próximos eventos
    $eventos = [];

    // Verifica se a tabela eventos existe
    $sql = "SHOW TABLES LIKE 'eventos'";
    $tabela_existe = $db->fetchOne($sql);

    if ($tabela_existe) {
        $sql = "SELECT id, titulo, data_inicio, local
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
            $eventos[] = $evento;
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
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

                    <div class="mb-8">
                        <p class="text-gray-600">Bem-vind<?php echo (isset($_SESSION['user_gender']) && $_SESSION['user_gender'] == 'feminino') ? 'a' : 'o'; ?>, <?php echo explode(' ', $_SESSION['user_name'] ?? 'Usuário')[0]; ?>! Aqui está um resumo das atividades e pendências do dia.</p>
                    </div>

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
                            <div class="mt-4 flex items-center">
                                <span class="text-green-500 text-sm font-medium flex items-center">
                                    <i class="fas fa-arrow-up mr-1"></i> 12%
                                </span>
                                <span class="text-gray-500 text-sm ml-2">Desde o último mês</span>
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
                            <div class="mt-4 flex items-center">
                                <span class="text-orange-500 text-sm font-medium flex items-center">
                                    <i class="fas fa-clock mr-1"></i> Pendentes
                                </span>
                                <span class="text-gray-500 text-sm ml-2">Sistema interno</span>
                            </div>
                        </div>

                        <div class="card bg-white p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Solicitações Site</p>
                                    <p class="text-3xl font-bold text-gray-800 mt-1" id="solicitacoes-site"><?php echo number_format($stats['solicitacoes_site'], 0, ',', '.'); ?></p>
                                </div>
                                <div class="bg-purple-100 p-3 rounded-full">
                                    <i class="fas fa-globe text-purple-500"></i>
                                </div>
                            </div>
                            <div class="mt-4 flex items-center">
                                <span class="text-purple-500 text-sm font-medium flex items-center">
                                    <i class="fas fa-external-link-alt mr-1"></i> Externas
                                </span>
                                <span class="text-gray-500 text-sm ml-2">Do site público</span>
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
                            <div class="mt-4 flex items-center">
                                <span class="text-green-500 text-sm font-medium flex items-center">
                                    <i class="fas fa-arrow-up mr-1"></i> 5%
                                </span>
                                <span class="text-gray-500 text-sm ml-2">Desde o último mês</span>
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
                            <div class="mt-4 flex items-center">
                                <span class="text-red-500 text-sm font-medium flex items-center">
                                    <i class="fas fa-arrow-up mr-1"></i> 15%
                                </span>
                                <span class="text-gray-500 text-sm ml-2">Desde a semana passada</span>
                            </div>
                        </div>

                        <div class="card bg-white p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Turmas Ativas</p>
                                    <p class="text-3xl font-bold text-gray-800 mt-1" id="turmas-ativas"><?php echo number_format($stats['turmas_ativas'], 0, ',', '.'); ?></p>
                                </div>
                                <div class="bg-purple-100 p-3 rounded-full">
                                    <i class="fas fa-users text-purple-500"></i>
                                </div>
                            </div>
                            <div class="mt-4 flex items-center">
                                <span class="text-green-500 text-sm font-medium flex items-center">
                                    <i class="fas fa-equals mr-1"></i> Estável
                                </span>
                                <span class="text-gray-500 text-sm ml-2">Sem variação recente</span>
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
                                        <button class="px-3 py-1 bg-gray-100 rounded-md text-gray-700 text-sm">Todas</button>
                                        <button class="px-3 py-1 bg-white rounded-md text-gray-700 text-sm">Urgentes</button>
                                        <button class="px-3 py-1 bg-white rounded-md text-gray-700 text-sm">Resolvidas</button>
                                    </div>
                                </div>
                                <div class="space-y-4" id="pendencias-container">
                                    <?php if (empty($tarefas)): ?>
                                    <div class="text-center text-gray-500 py-4">Não há pendências no momento.</div>
                                    <?php else: ?>
                                        <?php foreach ($tarefas as $tarefa):
                                            // Determina a classe da tarefa com base no tipo
                                            $classeCartao = 'normal';
                                            $classeBadge = 'badge-primary';
                                            $textoTipo = 'Normal';

                                            if ($tarefa['tipo'] === 'documento') {
                                                $classeCartao = 'urgent';
                                                $classeBadge = 'badge-danger';
                                                $textoTipo = 'Urgente';
                                            } else if ($tarefa['tipo'] === 'matricula') {
                                                $classeCartao = 'important';
                                                $classeBadge = 'badge-warning';
                                                $textoTipo = 'Importante';
                                            }

                                            // Formata a data
                                            $data = strtotime($tarefa['data_solicitacao'] ?? $tarefa['data_matricula']);
                                            $dataFormatada = date('d/m/Y', $data);
                                        ?>
                                        <div class="task-card <?php echo $classeCartao; ?>">
                                            <div class="flex justify-between items-start">
                                                <div>
                                                    <div class="flex items-center">
                                                        <h3 class="font-semibold"><?php echo htmlspecialchars($tarefa['descricao']); ?></h3>
                                                        <span class="badge <?php echo $classeBadge; ?> ml-3"><?php echo $textoTipo; ?></span>
                                                    </div>
                                                    <p class="text-gray-600 text-sm mt-1"><?php echo htmlspecialchars($tarefa['aluno_nome']); ?></p>
                                                </div>
                                                <div class="flex items-center">
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
                                                <a href="<?php echo $tarefa['tipo'] === 'documento' ? 'documentos.php?action=processar&id=' . $tarefa['id'] : 'matriculas.php?action=revisar&id=' . $tarefa['id']; ?>" class="btn-primary px-4 py-2 rounded-lg text-sm">
                                                    Processar agora
                                                </a>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <div class="mt-6 text-center">
                                    <button class="text-primary-600 text-sm font-medium hover:underline">
                                        Ver todas as pendências
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Sidebar Content -->
                        <div class="lg:col-span-1 space-y-6">
                            <!-- Upcoming Events -->
                            <div class="bg-white rounded-xl shadow-sm p-6">
                                <h2 class="text-lg font-bold text-gray-800 mb-4">Próximos Eventos</h2>

                                <div class="space-y-4">
                                    <?php if (empty($eventos)): ?>
                                    <div class="text-center text-gray-500 py-4">Não há eventos próximos.</div>
                                    <?php else: ?>
                                        <?php foreach ($eventos as $evento):
                                            // Define cores aleatórias para os eventos
                                            $cores = ['blue', 'purple', 'green', 'yellow', 'red'];
                                            $cor = $cores[array_rand($cores)];
                                        ?>
                                        <div class="flex items-start">
                                            <div class="bg-<?php echo $cor; ?>-100 rounded-lg p-3 text-center mr-4">
                                                <p class="text-<?php echo $cor; ?>-700 font-bold"><?php echo $evento['dia']; ?></p>
                                                <p class="text-<?php echo $cor; ?>-700 text-xs"><?php echo $evento['mes']; ?></p>
                                            </div>
                                            <div>
                                                <h3 class="font-medium"><?php echo htmlspecialchars($evento['titulo']); ?></h3>
                                                <p class="text-gray-500 text-sm"><?php echo htmlspecialchars($evento['local']); ?></p>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>

                                <div class="mt-4 text-center">
                                    <button class="text-primary-600 text-sm font-medium hover:underline">
                                        Ver calendário completo
                                    </button>
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
                                <h2 class="text-lg font-bold text-gray-800 mb-4">Ações Rápidas</h2>

                                <div class="grid grid-cols-2 gap-3">
                                    <a href="alunos.php?action=novo" class="flex flex-col items-center justify-center bg-blue-50 hover:bg-blue-100 transition-all p-4 rounded-lg">
                                        <div class="bg-blue-100 p-3 rounded-full mb-2">
                                            <i class="fas fa-user-plus text-blue-500"></i>
                                        </div>
                                        <span class="text-sm font-medium">Novo Aluno</span>
                                    </a>

                                    <a href="matriculas.php?action=nova" class="flex flex-col items-center justify-center bg-green-50 hover:bg-green-100 transition-all p-4 rounded-lg">
                                        <div class="bg-green-100 p-3 rounded-full mb-2">
                                            <i class="fas fa-file-alt text-green-500"></i>
                                        </div>
                                        <span class="text-sm font-medium">Nova Matrícula</span>
                                    </a>

                                    <a href="declaracoes.php?action=selecionar_aluno" class="flex flex-col items-center justify-center bg-purple-50 hover:bg-purple-100 transition-all p-4 rounded-lg">
                                        <div class="bg-purple-100 p-3 rounded-full mb-2">
                                            <i class="fas fa-file-alt text-purple-500"></i>
                                        </div>
                                        <span class="text-sm font-medium">Declarações</span>
                                    </a>

                                    <a href="historicos.php?action=selecionar_aluno" class="flex flex-col items-center justify-center bg-green-50 hover:bg-green-100 transition-all p-4 rounded-lg">
                                        <div class="bg-green-100 p-3 rounded-full mb-2">
                                            <i class="fas fa-graduation-cap text-green-500"></i>
                                        </div>
                                        <span class="text-sm font-medium">Históricos</span>
                                    </a>

                                    <a href="busca_avancada.php" class="flex flex-col items-center justify-center bg-yellow-50 hover:bg-yellow-100 transition-all p-4 rounded-lg">
                                        <div class="bg-yellow-100 p-3 rounded-full mb-2">
                                            <i class="fas fa-search text-yellow-500"></i>
                                        </div>
                                        <span class="text-sm font-medium">Busca Avançada</span>
                                    </a>
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
</body>
</html>
