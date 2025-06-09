<?php
/**
 * Página de diagnóstico para mensalidades recorrentes
 * Esta página simplifica o processo de geração de mensalidades para facilitar o diagnóstico
 */

// Inicializa o sistema
require_once __DIR__ . '/../includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica se o usuário tem permissão para acessar o módulo financeiro
if (!Auth::hasPermission('financeiro', 'visualizar')) {
    setMensagem('erro', 'Você não tem permissão para acessar o módulo financeiro.');
    redirect('../index.php');
    exit;
}

// Instancia o banco de dados
$db = Database::getInstance();

// Define a ação padrão
$action = $_GET['action'] ?? 'form';

// Parâmetros de paginação
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$por_pagina = 50; // Exibe 50 alunos por página
$offset = ($pagina - 1) * $por_pagina;

// Termo de busca
$busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';

// Consulta para contar o total de alunos
$sql_count = "SELECT COUNT(*) as total FROM alunos a";
if (!empty($busca)) {
    $sql_count .= " WHERE (a.nome LIKE ? OR a.cpf LIKE ? OR a.email LIKE ?)";
    $params_count = ["%$busca%", "%$busca%", "%$busca%"];
    $total_result = $db->fetchOne($sql_count, $params_count);
} else {
    $total_result = $db->fetchOne($sql_count);
}
$total_alunos = $total_result['total'] ?? 0;
$total_paginas = ceil($total_alunos / $por_pagina);

// Busca todos os alunos com paginação e filtro
$sql = "SELECT a.id, a.nome, a.cpf, a.email, a.telefone, a.status,
        (SELECT m.id FROM matriculas m WHERE m.aluno_id = a.id ORDER BY m.id DESC LIMIT 1) as matricula_id,
        (SELECT c.nome FROM matriculas m JOIN cursos c ON m.curso_id = c.id WHERE m.aluno_id = a.id ORDER BY m.id DESC LIMIT 1) as curso_nome,
        (SELECT p.nome FROM matriculas m JOIN polos p ON m.polo_id = p.id WHERE m.aluno_id = a.id ORDER BY m.id DESC LIMIT 1) as polo_nome
        FROM alunos a";

// Adiciona filtro de busca se necessário
if (!empty($busca)) {
    $sql .= " WHERE (a.nome LIKE ? OR a.cpf LIKE ? OR a.email LIKE ?)";
    $params = ["%$busca%", "%$busca%", "%$busca%"];
    $sql .= " ORDER BY a.nome LIMIT $offset, $por_pagina";
    $alunos = $db->fetchAll($sql, $params);
} else {
    $sql .= " ORDER BY a.nome LIMIT $offset, $por_pagina";
    $alunos = $db->fetchAll($sql);
}

// Busca as categorias financeiras de receita
$sql = "SELECT * FROM categorias_financeiras WHERE tipo = 'receita' ORDER BY nome";
$categorias = $db->fetchAll($sql);

// Busca o plano de contas
$sql = "SELECT * FROM plano_contas WHERE tipo IN ('receita', 'ambos') ORDER BY codigo";
$plano_contas = $db->fetchAll($sql);

// Processa o formulário
if ($action === 'processar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Habilita o log de erros
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    // Log para debug
    error_log("POST data: " . json_encode($_POST));

    // Obtém os dados do formulário
    $aluno_ids = $_POST['aluno_ids'] ?? [];
    $categoria_id = isset($_POST['categoria_id']) ? (int)$_POST['categoria_id'] : null;
    $plano_conta_id = isset($_POST['plano_conta_id']) ? (int)$_POST['plano_conta_id'] : null;
    $descricao = $_POST['descricao'] ?? '';
    $valor = str_replace(',', '.', $_POST['valor'] ?? 0);
    $data_vencimento_inicial = $_POST['data_vencimento_inicial'] ?? date('Y-m-d');
    $total_meses = isset($_POST['total_meses']) ? (int)$_POST['total_meses'] : 1;

    // Log para debug
    error_log("Dados processados: aluno_ids=" . json_encode($aluno_ids) .
             ", categoria_id=$categoria_id, plano_conta_id=$plano_conta_id, " .
             "valor=$valor, total_meses=$total_meses");

    // Validação básica
    $erros = [];

    if (empty($aluno_ids)) {
        $erros[] = 'Selecione pelo menos um aluno.';
    }

    if (empty($categoria_id)) {
        $erros[] = 'A categoria é obrigatória.';
    }

    if (empty($plano_conta_id)) {
        $erros[] = 'O plano de contas é obrigatório.';
    }

    if (empty($descricao)) {
        $erros[] = 'A descrição é obrigatória.';
    }

    if (empty($valor) || !is_numeric($valor) || $valor <= 0) {
        $erros[] = 'O valor deve ser um número maior que zero.';
    }

    // Se houver erros, exibe-os
    if (!empty($erros)) {
        $_SESSION['form_errors'] = $erros;
        $_SESSION['form_data'] = $_POST;

        // Preserva os parâmetros de busca e paginação
        $redirect_url = 'mensalidades_debug.php';
        if (!empty($_GET['busca'])) {
            $redirect_url .= '?busca=' . urlencode($_GET['busca']);
            if (isset($_GET['pagina'])) {
                $redirect_url .= '&pagina=' . (int)$_GET['pagina'];
            }
        } elseif (isset($_GET['pagina'])) {
            $redirect_url .= '?pagina=' . (int)$_GET['pagina'];
        }

        redirect($redirect_url);
        exit;
    }

    try {
        // Inicia a transação
        $db->beginTransaction();

        // Contador de mensalidades geradas
        $mensalidades_geradas = 0;
        $log_mensalidades = [];

        // Para cada aluno selecionado
        foreach ($aluno_ids as $aluno_id) {
            // Busca informações do aluno
            $sql = "SELECT a.id, a.nome, m.id as matricula_id, m.curso_id, m.polo_id
                    FROM alunos a
                    LEFT JOIN matriculas m ON a.id = m.aluno_id
                    WHERE a.id = ?
                    LIMIT 1";
            $aluno = $db->fetchOne($sql, [$aluno_id]);

            if (!$aluno) {
                $log_mensalidades[] = "Aluno ID $aluno_id não encontrado, pulando.";
                continue; // Pula este aluno se não for encontrado
            }

            // Define valores padrão para campos que podem estar vazios
            $matricula_id = $aluno['matricula_id'] ?? null;
            $curso_id = $aluno['curso_id'] ?? null;
            $polo_id = $aluno['polo_id'] ?? null;

            // Gera as mensalidades para este aluno
            $data_base = new DateTime($data_vencimento_inicial);

            for ($i = 1; $i <= $total_meses; $i++) {
                $dados_mensalidade = [
                    'aluno_id' => $aluno_id,
                    'categoria_id' => $categoria_id,
                    'plano_conta_id' => $plano_conta_id,
                    'tipo' => 'receita',
                    'descricao' => $descricao . ' - ' . $data_base->format('m/Y'),
                    'valor' => $valor,
                    'desconto' => 0,
                    'acrescimo' => 0,
                    'data_vencimento' => $data_base->format('Y-m-d'),
                    'data_lancamento' => date('Y-m-d'),
                    'usuario_id' => Auth::getUserId(), // Usando a função correta para obter o ID do usuário
                    'polo_id' => $polo_id,
                    'status' => 'pendente',
                    'numero_parcela' => $i,
                    'total_parcelas' => $total_meses,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                // Log para debug
                $log_mensalidades[] = "Tentando inserir mensalidade para aluno {$aluno['nome']} (ID: $aluno_id), vencimento: {$dados_mensalidade['data_vencimento']}";

                // Insere a mensalidade
                $mensalidade_id = $db->insert('lancamentos_financeiros', $dados_mensalidade);
                $mensalidades_geradas++;

                $log_mensalidades[] = "Mensalidade inserida com sucesso: ID $mensalidade_id";

                // Avança para o próximo mês
                $data_base->modify('+1 month');
            }
        }

        // Confirma a transação
        $db->commit();

        // Define a mensagem de sucesso
        setMensagem('sucesso', "Foram geradas $mensalidades_geradas mensalidades com sucesso.");

        // Salva o log para exibição
        $_SESSION['log_mensalidades'] = $log_mensalidades;

        // Preserva os parâmetros de busca e paginação
        $redirect_url = 'mensalidades_debug.php?action=resultado';
        if (!empty($_GET['busca'])) {
            $redirect_url .= '&busca=' . urlencode($_GET['busca']);
        }
        if (isset($_GET['pagina'])) {
            $redirect_url .= '&pagina=' . (int)$_GET['pagina'];
        }

        // Redireciona para a página de resultado
        redirect($redirect_url);
        exit;

    } catch (Exception $e) {
        // Desfaz a transação em caso de erro
        $db->rollBack();

        // Define a mensagem de erro
        setMensagem('erro', 'Erro ao gerar mensalidades: ' . $e->getMessage());

        // Salva o log para exibição
        $_SESSION['log_mensalidades'] = ["ERRO: " . $e->getMessage()];

        // Preserva os parâmetros de busca e paginação
        $redirect_url = 'mensalidades_debug.php?action=resultado';
        if (!empty($_GET['busca'])) {
            $redirect_url .= '&busca=' . urlencode($_GET['busca']);
        }
        if (isset($_GET['pagina'])) {
            $redirect_url .= '&pagina=' . (int)$_GET['pagina'];
        }

        // Redireciona para a página de resultado
        redirect($redirect_url);
        exit;
    }
}

// Define o título da página
$titulo_pagina = 'Diagnóstico de Mensalidades Recorrentes';
$titulo_pagina_completo = 'Faciência ERP - ' . $titulo_pagina;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo_pagina_completo; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../css/styles.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Busca no banco de dados (com botão)
            $('#btn_buscar_db').on('click', function() {
                realizarBuscaNoBanco();
            });

            // Busca no banco de dados (com Enter)
            $('#busca_db').on('keypress', function(e) {
                if (e.which === 13) { // Código da tecla Enter
                    e.preventDefault(); // Previne o comportamento padrão do Enter
                    realizarBuscaNoBanco();
                }
            });

            // Função para realizar a busca no banco de dados
            function realizarBuscaNoBanco() {
                var termo = $('#busca_db').val().trim();
                var url = 'mensalidades_debug.php';

                if (termo !== '') {
                    url += '?busca=' + encodeURIComponent(termo);
                }

                window.location.href = url;
            }

            // Busca em tempo real (filtro local)
            $('#busca_rapida').on('input', function() {
                var termo = $(this).val().toLowerCase().trim();

                // Se o termo estiver vazio, mostra todos os alunos
                if (termo === '') {
                    $('.aluno-item').show();
                    atualizarContador();
                    return;
                }

                // Filtra os alunos
                $('.aluno-item').each(function() {
                    var nome = $(this).data('nome').toString().toLowerCase();
                    var cpf = $(this).data('cpf').toString().toLowerCase();
                    var curso = $(this).data('curso').toString().toLowerCase();
                    var polo = $(this).data('polo').toString().toLowerCase();

                    if (nome.includes(termo) ||
                        cpf.includes(termo) ||
                        curso.includes(termo) ||
                        polo.includes(termo)) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });

                // Atualiza o contador de alunos visíveis
                atualizarContador();
            });

            // Função para atualizar o contador de alunos visíveis
            function atualizarContador() {
                var total = $('.aluno-item').length;
                var visiveis = $('.aluno-item:visible').length;
                $('#contador-alunos-visiveis').text(visiveis);
            }

            // Selecionar/deselecionar todos os alunos visíveis
            $('#selecionar_todos').on('change', function() {
                if ($(this).is(':checked')) {
                    $('.aluno-checkbox:visible').prop('checked', true);
                } else {
                    $('.aluno-checkbox:visible').prop('checked', false);
                }
            });

            // Botão para limpar a busca rápida
            $('#limpar_busca_rapida').on('click', function() {
                $('#busca_rapida').val('').trigger('input');
            });

            // Inicializa o contador
            atualizarContador();
        });
    </script>
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
                    <h1 class="text-3xl font-bold text-gray-800 mb-6"><?php echo $titulo_pagina; ?></h1>

                    <?php if (isset($_SESSION['form_errors']) && !empty($_SESSION['form_errors'])): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
                        <ul class="list-disc pl-5">
                            <?php foreach ($_SESSION['form_errors'] as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php unset($_SESSION['form_errors']); ?>
                    <?php endif; ?>

                    <?php if ($action === 'form'): ?>
                    <!-- Formulário simplificado -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-xl font-semibold mb-4">Gerar Mensalidades Recorrentes</h2>
                        <p class="text-gray-600 mb-6">Esta é uma versão simplificada para diagnóstico do problema de geração de mensalidades.</p>

                        <form action="mensalidades_debug.php?action=processar<?php echo !empty($busca) ? '&busca=' . urlencode($busca) : ''; ?><?php echo isset($_GET['pagina']) ? '&pagina=' . (int)$_GET['pagina'] : ''; ?>" method="post" class="space-y-6">
                            <!-- Seleção de Alunos -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Selecione os Alunos <span class="text-red-600">*</span></label>

                                <!-- Campo de busca no banco de dados -->
                                <div class="mb-3">
                                    <!-- Importante: usando um formulário separado com target="_self" para evitar conflitos -->
                                    <div class="flex">
                                        <a href="mensalidades_debug.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-l-md hover:bg-gray-300">
                                            <i class="fas fa-times"></i>
                                        </a>
                                        <input type="text" name="busca_db" id="busca_db" value="<?php echo htmlspecialchars($busca); ?>" placeholder="Buscar no banco de dados" class="flex-1 border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                                        <button type="button" id="btn_buscar_db" class="px-4 py-2 bg-blue-600 text-white rounded-r-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Campo de busca em tempo real -->
                                <div class="mb-3">
                                    <div class="flex">
                                        <div class="flex-1 relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i class="fas fa-filter text-gray-400"></i>
                                            </div>
                                            <input type="text" id="busca_rapida" placeholder="Filtrar resultados exibidos em tempo real" class="pl-10 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                                <button type="button" id="limpar_busca_rapida" class="text-gray-400 hover:text-gray-600">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <label class="inline-flex items-center">
                                            <input type="checkbox" id="selecionar_todos" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                                            <span class="ml-2 text-sm text-gray-700">Selecionar todos os alunos visíveis</span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Informações sobre total de alunos -->
                                <div class="mb-2 text-sm text-gray-600">
                                    Mostrando <span id="contador-alunos-visiveis"><?php echo count($alunos); ?></span> de <?php echo $total_alunos; ?> alunos |
                                    Página <?php echo $pagina; ?> de <?php echo $total_paginas; ?>
                                </div>

                                <!-- Lista de alunos -->
                                <div class="max-h-60 overflow-y-auto border border-gray-300 rounded-md p-3">
                                    <?php if (empty($alunos)): ?>
                                    <p class="text-gray-500">Nenhum aluno encontrado.</p>
                                    <?php else: ?>
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                        <?php foreach ($alunos as $aluno):
                                            $nome = htmlspecialchars($aluno['nome'] ?? '');
                                            $cpf = htmlspecialchars($aluno['cpf'] ?? '');
                                            $curso = htmlspecialchars($aluno['curso_nome'] ?? '');
                                            $polo = htmlspecialchars($aluno['polo_nome'] ?? '');
                                        ?>
                                        <div class="flex items-start aluno-item"
                                             data-nome="<?php echo $nome; ?>"
                                             data-cpf="<?php echo $cpf; ?>"
                                             data-curso="<?php echo $curso; ?>"
                                             data-polo="<?php echo $polo; ?>">
                                            <input type="checkbox" name="aluno_ids[]" id="aluno_<?php echo $aluno['id']; ?>"
                                                   value="<?php echo $aluno['id']; ?>" class="mt-1 aluno-checkbox">
                                            <label for="aluno_<?php echo $aluno['id']; ?>" class="ml-2 text-sm text-gray-700">
                                                <span class="font-medium"><?php echo $nome; ?></span>
                                                <?php if (!empty($cpf)): ?>
                                                <br><span class="text-xs text-gray-500">CPF: <?php echo $cpf; ?></span>
                                                <?php endif; ?>
                                                <?php if (!empty($curso)): ?>
                                                <br><span class="text-xs text-gray-500">Curso: <?php echo $curso; ?></span>
                                                <?php endif; ?>
                                                <?php if (!empty($aluno['polo_nome'])): ?>
                                                <br><span class="text-xs text-gray-500">Polo: <?php echo htmlspecialchars($aluno['polo_nome']); ?></span>
                                                <?php endif; ?>
                                            </label>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Paginação -->
                                <?php if ($total_paginas > 1): ?>
                                <div class="mt-3 flex justify-center">
                                    <nav class="inline-flex rounded-md shadow-sm">
                                        <?php if ($pagina > 1): ?>
                                        <a href="mensalidades_debug.php?pagina=1<?php echo !empty($busca) ? '&busca=' . urlencode($busca) : ''; ?>" class="px-3 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 rounded-l-md">
                                            <i class="fas fa-angle-double-left"></i>
                                        </a>
                                        <a href="mensalidades_debug.php?pagina=<?php echo $pagina - 1; ?><?php echo !empty($busca) ? '&busca=' . urlencode($busca) : ''; ?>" class="px-3 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                                            <i class="fas fa-angle-left"></i>
                                        </a>
                                        <?php endif; ?>

                                        <?php
                                        // Determina quais páginas mostrar
                                        $start_page = max(1, $pagina - 2);
                                        $end_page = min($total_paginas, $pagina + 2);

                                        for ($i = $start_page; $i <= $end_page; $i++):
                                        ?>
                                        <a href="mensalidades_debug.php?pagina=<?php echo $i; ?><?php echo !empty($busca) ? '&busca=' . urlencode($busca) : ''; ?>" class="px-3 py-2 border border-gray-300 <?php echo $i == $pagina ? 'bg-blue-50 text-blue-600' : 'bg-white text-gray-700 hover:bg-gray-50'; ?> text-sm font-medium">
                                            <?php echo $i; ?>
                                        </a>
                                        <?php endfor; ?>

                                        <?php if ($pagina < $total_paginas): ?>
                                        <a href="mensalidades_debug.php?pagina=<?php echo $pagina + 1; ?><?php echo !empty($busca) ? '&busca=' . urlencode($busca) : ''; ?>" class="px-3 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                                            <i class="fas fa-angle-right"></i>
                                        </a>
                                        <a href="mensalidades_debug.php?pagina=<?php echo $total_paginas; ?><?php echo !empty($busca) ? '&busca=' . urlencode($busca) : ''; ?>" class="px-3 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 rounded-r-md">
                                            <i class="fas fa-angle-double-right"></i>
                                        </a>
                                        <?php endif; ?>
                                    </nav>
                                </div>
                                <?php endif; ?>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Categoria -->
                                <div>
                                    <label for="categoria_id" class="block text-sm font-medium text-gray-700 mb-1">Categoria <span class="text-red-600">*</span></label>
                                    <select name="categoria_id" id="categoria_id" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                                        <option value="">Selecione uma categoria</option>
                                        <?php foreach ($categorias as $categoria): ?>
                                        <option value="<?php echo $categoria['id']; ?>"><?php echo htmlspecialchars($categoria['nome']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Plano de Contas -->
                                <div>
                                    <label for="plano_conta_id" class="block text-sm font-medium text-gray-700 mb-1">Plano de Contas <span class="text-red-600">*</span></label>
                                    <select name="plano_conta_id" id="plano_conta_id" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                                        <option value="">Selecione um plano de contas</option>
                                        <?php foreach ($plano_contas as $plano): ?>
                                        <option value="<?php echo $plano['id']; ?>"><?php echo htmlspecialchars($plano['codigo'] . ' - ' . $plano['descricao']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Descrição -->
                                <div>
                                    <label for="descricao" class="block text-sm font-medium text-gray-700 mb-1">Descrição <span class="text-red-600">*</span></label>
                                    <input type="text" name="descricao" id="descricao" value="Mensalidade" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                                </div>

                                <!-- Valor -->
                                <div>
                                    <label for="valor" class="block text-sm font-medium text-gray-700 mb-1">Valor <span class="text-red-600">*</span></label>
                                    <input type="text" name="valor" id="valor" value="100.00" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Data de Vencimento Inicial -->
                                <div>
                                    <label for="data_vencimento_inicial" class="block text-sm font-medium text-gray-700 mb-1">Data do 1º Vencimento <span class="text-red-600">*</span></label>
                                    <input type="date" name="data_vencimento_inicial" id="data_vencimento_inicial" value="<?php echo date('Y-m-d'); ?>" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                                </div>

                                <!-- Total de Meses -->
                                <div>
                                    <label for="total_meses" class="block text-sm font-medium text-gray-700 mb-1">Total de Meses <span class="text-red-600">*</span></label>
                                    <input type="number" name="total_meses" id="total_meses" value="1" required min="1" max="36" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                                </div>
                            </div>

                            <!-- Botões -->
                            <div class="flex justify-end space-x-3">
                                <a href="mensalidades.php" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    Cancelar
                                </a>
                                <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    Gerar Mensalidades
                                </button>
                            </div>
                        </form>
                    </div>
                    <?php elseif ($action === 'resultado'): ?>
                    <!-- Resultado do processamento -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-xl font-semibold mb-4">Resultado do Processamento</h2>

                        <?php if (isset($_SESSION['log_mensalidades']) && !empty($_SESSION['log_mensalidades'])): ?>
                        <div class="bg-gray-100 p-4 rounded-md mb-4">
                            <h3 class="font-medium mb-2">Log de Processamento:</h3>
                            <pre class="text-sm overflow-x-auto"><?php echo implode("\n", $_SESSION['log_mensalidades']); ?></pre>
                        </div>
                        <?php unset($_SESSION['log_mensalidades']); ?>
                        <?php endif; ?>

                        <div class="flex justify-between mt-6">
                            <a href="mensalidades_debug.php<?php echo !empty($_GET['busca']) || isset($_GET['pagina']) ? '?' : ''; ?><?php echo !empty($_GET['busca']) ? 'busca=' . urlencode($_GET['busca']) : ''; ?><?php echo !empty($_GET['busca']) && isset($_GET['pagina']) ? '&' : ''; ?><?php echo isset($_GET['pagina']) ? 'pagina=' . (int)$_GET['pagina'] : ''; ?>" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Voltar ao Formulário
                            </a>
                            <a href="mensalidades.php" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Ir para Mensalidades
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
</body>
</html>
