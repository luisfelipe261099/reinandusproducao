<?php
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
$action = $_GET['action'] ?? 'listar';

// Processa as ações
switch ($action) {
    case 'recorrente':
        // Verifica permissão para criar
        if (!Auth::hasPermission('financeiro', 'criar')) {
            setMensagem('erro', 'Você não tem permissão para criar mensalidades.');
            redirect('mensalidades.php');
            exit;
        }

        // Parâmetros de paginação para alunos
        $pagina_alunos = isset($_GET['pagina_alunos']) ? (int)$_GET['pagina_alunos'] : 1;
        $por_pagina_alunos = 100; // Exibe 100 alunos por página
        $offset_alunos = ($pagina_alunos - 1) * $por_pagina_alunos;

        // Busca parâmetros de filtro
        $busca_aluno = isset($_GET['busca_aluno']) ? trim($_GET['busca_aluno']) : '';
        $filtro_status = isset($_GET['filtro_status']) ? $_GET['filtro_status'] : '';
        $filtro_polo = isset($_GET['filtro_polo']) ? (int)$_GET['filtro_polo'] : 0;
        $filtro_curso = isset($_GET['filtro_curso']) ? (int)$_GET['filtro_curso'] : 0;

        // Constrói a consulta SQL com filtros
        $where_alunos = [];
        $params_alunos = [];

        if (!empty($busca_aluno)) {
            // Limpa e prepara o termo de busca
            $termo_busca = trim($busca_aluno);
            $where_alunos[] = "(a.nome LIKE ? OR a.cpf LIKE ? OR a.email LIKE ?)";
            $params_alunos[] = "%{$termo_busca}%";
            $params_alunos[] = "%{$termo_busca}%";
            $params_alunos[] = "%{$termo_busca}%";

            // Log para debug
            error_log("Busca de aluno: '{$termo_busca}' - SQL: " . implode(" AND ", $where_alunos));
        }

        if (!empty($filtro_status)) {
            $where_alunos[] = "a.status = ?";
            $params_alunos[] = $filtro_status;
        }

        // Busca total de alunos para paginação
        $sql_count = "SELECT COUNT(*) as total FROM alunos a";
        if (!empty($where_alunos)) {
            $sql_count .= " WHERE " . implode(" AND ", $where_alunos);
        }
        $total_result = $db->fetchOne($sql_count, $params_alunos);
        $total_alunos = $total_result['total'] ?? 0;
        $total_paginas_alunos = ceil($total_alunos / $por_pagina_alunos);

        // Busca os alunos com paginação
        $sql = "SELECT a.id, a.nome, a.cpf, a.email, a.telefone, a.status,
                (SELECT m.id FROM matriculas m WHERE m.aluno_id = a.id ORDER BY m.id DESC LIMIT 1) as matricula_id,
                (SELECT c.nome FROM matriculas m JOIN cursos c ON m.curso_id = c.id WHERE m.aluno_id = a.id ORDER BY m.id DESC LIMIT 1) as curso_nome,
                (SELECT p.nome FROM matriculas m JOIN polos p ON m.polo_id = p.id WHERE m.aluno_id = a.id ORDER BY m.id DESC LIMIT 1) as polo_nome
                FROM alunos a";

        if (!empty($where_alunos)) {
            $sql .= " WHERE " . implode(" AND ", $where_alunos);
        }

        $sql .= " ORDER BY a.nome LIMIT {$offset_alunos}, {$por_pagina_alunos}";
        $alunos = $db->fetchAll($sql, $params_alunos);

        // Busca polos e cursos para filtros
        $sql_polos = "SELECT id, nome FROM polos WHERE status = 'ativo' ORDER BY nome";
        $polos_filtro = $db->fetchAll($sql_polos);

        $sql_cursos = "SELECT id, nome FROM cursos WHERE status = 'ativo' ORDER BY nome";
        $cursos_filtro = $db->fetchAll($sql_cursos);

        // Busca as categorias financeiras de receita
        $sql = "SELECT * FROM categorias_financeiras WHERE tipo = 'receita' ORDER BY nome";
        $categorias = $db->fetchAll($sql);

        // Verifica se existem categorias financeiras
        if (empty($categorias)) {
            // Redireciona para o script de criação de categorias
            setMensagem('info', 'Não existem categorias financeiras cadastradas. Você será redirecionado para criar categorias padrão.');
            redirect('scripts/criar_categorias.php');
            exit;
        }

        // Busca o plano de contas
        $sql = "SELECT * FROM plano_contas WHERE tipo IN ('receita', 'ambos') ORDER BY codigo";
        $plano_contas = $db->fetchAll($sql);

        // Verifica se existe plano de contas
        if (empty($plano_contas)) {
            // Redireciona para o script de criação de plano de contas
            setMensagem('info', 'Não existe plano de contas cadastrado. Você será redirecionado para criar um plano de contas padrão.');
            redirect('scripts/criar_plano_contas.php');
            exit;
        }

        // Define o título da página
        $titulo_pagina = 'Mensalidades Recorrentes';
        $view = 'recorrente';
        break;

    case 'nova':
        // Verifica permissão para criar
        if (!Auth::hasPermission('financeiro', 'criar')) {
            setMensagem('erro', 'Você não tem permissão para criar mensalidades.');
            redirect('mensalidades.php');
            exit;
        }

        // Busca todos os alunos sem restrições
        $sql = "SELECT a.id, a.nome, a.cpf, a.email, a.telefone,
                m.id as matricula_id, c.nome as curso_nome, p.nome as polo_nome
                FROM alunos a
                LEFT JOIN matriculas m ON a.id = m.aluno_id
                LEFT JOIN cursos c ON m.curso_id = c.id
                LEFT JOIN polos p ON m.polo_id = p.id
                ORDER BY a.nome";
        $alunos = $db->fetchAll($sql);

        // Busca as categorias financeiras de receita
        $sql = "SELECT * FROM categorias_financeiras WHERE tipo = 'receita' ORDER BY nome";
        $categorias = $db->fetchAll($sql);

        // Verifica se existem categorias financeiras
        if (empty($categorias)) {
            // Redireciona para o script de criação de categorias
            setMensagem('info', 'Não existem categorias financeiras cadastradas. Você será redirecionado para criar categorias padrão.');
            redirect('scripts/criar_categorias.php');
            exit;
        }

        // Busca o plano de contas
        $sql = "SELECT * FROM plano_contas WHERE tipo IN ('receita', 'ambos') ORDER BY codigo";
        $plano_contas = $db->fetchAll($sql);

        // Verifica se existe plano de contas
        if (empty($plano_contas)) {
            // Redireciona para o script de criação de plano de contas
            setMensagem('info', 'Não existe plano de contas cadastrado. Você será redirecionado para criar um plano de contas padrão.');
            redirect('scripts/criar_plano_contas.php');
            exit;
        }

        // Define o título da página
        $titulo_pagina = 'Nova Mensalidade';
        $view = 'form';
        break;

    case 'editar':
        // Verifica permissão para editar
        if (!Auth::hasPermission('financeiro', 'editar')) {
            setMensagem('erro', 'Você não tem permissão para editar mensalidades.');
            redirect('mensalidades.php');
            exit;
        }

        // Verifica se o ID foi informado
        if (!isset($_GET['id']) || empty($_GET['id'])) {
            setMensagem('erro', 'ID da mensalidade não informado.');
            redirect('mensalidades.php');
            exit;
        }

        // Busca a mensalidade
        $id = (int)$_GET['id'];
        $sql = "SELECT lf.*, a.nome as aluno_nome, a.cpf as aluno_cpf, c.nome as curso_nome, p.nome as polo_nome
                FROM lancamentos_financeiros lf
                LEFT JOIN alunos a ON lf.aluno_id = a.id
                LEFT JOIN matriculas m ON a.id = m.aluno_id AND m.status = 'ativo'
                LEFT JOIN cursos c ON m.curso_id = c.id
                LEFT JOIN polos p ON m.polo_id = p.id
                WHERE lf.id = ?";
        $mensalidade = $db->fetchOne($sql, [$id]);

        if (!$mensalidade) {
            setMensagem('erro', 'Mensalidade não encontrada.');
            redirect('mensalidades.php');
            exit;
        }

        // Busca as categorias financeiras de receita
        $sql = "SELECT * FROM categorias_financeiras WHERE tipo = 'receita' ORDER BY nome";
        $categorias = $db->fetchAll($sql);

        // Verifica se existem categorias financeiras
        if (empty($categorias)) {
            // Redireciona para o script de criação de categorias
            setMensagem('info', 'Não existem categorias financeiras cadastradas. Você será redirecionado para criar categorias padrão.');
            redirect('scripts/criar_categorias.php');
            exit;
        }

        // Busca o plano de contas
        $sql = "SELECT * FROM plano_contas WHERE tipo IN ('receita', 'ambos') ORDER BY codigo";
        $plano_contas = $db->fetchAll($sql);

        // Verifica se existe plano de contas
        if (empty($plano_contas)) {
            // Redireciona para o script de criação de plano de contas
            setMensagem('info', 'Não existe plano de contas cadastrado. Você será redirecionado para criar um plano de contas padrão.');
            redirect('scripts/criar_plano_contas.php');
            exit;
        }

        // Define o título da página
        $titulo_pagina = 'Editar Mensalidade';
        $view = 'form';
        break;

    case 'salvar':
        // Verifica permissão para criar/editar
        if (!Auth::hasPermission('financeiro', 'criar') && !Auth::hasPermission('financeiro', 'editar')) {
            setMensagem('erro', 'Você não tem permissão para criar/editar mensalidades.');
            redirect('mensalidades.php');
            exit;
        }

        // Verifica se é POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            setMensagem('erro', 'Método inválido.');
            redirect('mensalidades.php');
            exit;
        }

        // Obtém os dados do formulário
        $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
        $aluno_id = isset($_POST['aluno_id']) ? (int)$_POST['aluno_id'] : null;
        $categoria_id = isset($_POST['categoria_id']) ? (int)$_POST['categoria_id'] : null;
        $plano_conta_id = isset($_POST['plano_conta_id']) ? (int)$_POST['plano_conta_id'] : null;
        $descricao = $_POST['descricao'] ?? '';
        $valor = str_replace(',', '.', $_POST['valor'] ?? 0);
        $desconto = str_replace(',', '.', $_POST['desconto'] ?? 0);
        $acrescimo = str_replace(',', '.', $_POST['acrescimo'] ?? 0);
        $data_vencimento = $_POST['data_vencimento'] ?? date('Y-m-d');
        $forma_pagamento = $_POST['forma_pagamento'] ?? null;
        $status = $_POST['status'] ?? 'pendente';
        $data_pagamento = !empty($_POST['data_pagamento']) ? $_POST['data_pagamento'] : null;
        $gerar_parcelas = isset($_POST['gerar_parcelas']) && $_POST['gerar_parcelas'] == '1';
        $total_parcelas = isset($_POST['total_parcelas']) ? (int)$_POST['total_parcelas'] : 1;
        $intervalo_dias = isset($_POST['intervalo_dias']) ? (int)$_POST['intervalo_dias'] : 30;

        // Validação básica
        $erros = [];

        if (empty($aluno_id)) {
            $erros[] = 'O aluno é obrigatório.';
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

        if (empty($data_vencimento) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_vencimento)) {
            $erros[] = 'A data de vencimento é obrigatória e deve estar no formato correto.';
        }

        if (!empty($data_pagamento) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_pagamento)) {
            $erros[] = 'A data de pagamento deve estar no formato correto.';
        }

        if ($gerar_parcelas && ($total_parcelas <= 0 || $total_parcelas > 36)) {
            $erros[] = 'O número de parcelas deve estar entre 1 e 36.';
        }

        // Se houver erros, redireciona de volta para o formulário
        if (!empty($erros)) {
            $_SESSION['form_data'] = $_POST;
            $_SESSION['form_errors'] = $erros;

            if ($id) {
                redirect("mensalidades.php?action=editar&id=$id");
            } else {
                redirect('mensalidades.php?action=nova');
            }
            exit;
        }

        try {
            // Inicia a transação
            $db->beginTransaction();

            // Prepara os dados para inserção/atualização
            $dados = [
                'aluno_id' => $aluno_id,
                'categoria_id' => $categoria_id,
                'plano_conta_id' => $plano_conta_id,
                'tipo' => 'receita',
                'descricao' => $descricao,
                'valor' => $valor,
                'desconto' => $desconto,
                'acrescimo' => $acrescimo,
                'data_vencimento' => $data_vencimento,
                'forma_pagamento' => $forma_pagamento,
                'status' => $status,
                'data_pagamento' => $data_pagamento,
                'data_lancamento' => date('Y-m-d'),
                'usuario_id' => $_SESSION['usuario']['id']
            ];

            // Se for uma edição simples (sem gerar parcelas)
            if ($id && !$gerar_parcelas) {
                $db->update('lancamentos_financeiros', $dados, 'id = ?', [$id]);
                $mensagem = 'Mensalidade atualizada com sucesso.';
            }
            // Se for uma nova mensalidade com parcelas
            else if (!$id && $gerar_parcelas) {
                // Gera as parcelas
                $data_base = new DateTime($data_vencimento);

                for ($i = 1; $i <= $total_parcelas; $i++) {
                    $dados_parcela = $dados;
                    $dados_parcela['descricao'] = $descricao . ' - Parcela ' . $i . '/' . $total_parcelas;
                    $dados_parcela['data_vencimento'] = $data_base->format('Y-m-d');
                    $dados_parcela['numero_parcela'] = $i;
                    $dados_parcela['total_parcelas'] = $total_parcelas;
                    $dados_parcela['created_at'] = date('Y-m-d H:i:s');
                    $dados_parcela['updated_at'] = date('Y-m-d H:i:s');

                    $db->insert('lancamentos_financeiros', $dados_parcela);

                    // Avança para o próximo vencimento
                    $data_base->modify('+' . $intervalo_dias . ' days');
                }

                $mensagem = 'Mensalidades geradas com sucesso.';
            }
            // Se for uma nova mensalidade sem parcelas
            else {
                $dados['numero_parcela'] = 1;
                $dados['total_parcelas'] = 1;
                $dados['created_at'] = date('Y-m-d H:i:s');
                $dados['updated_at'] = date('Y-m-d H:i:s');

                $db->insert('lancamentos_financeiros', $dados);
                $mensagem = 'Mensalidade cadastrada com sucesso.';
            }

            // Confirma a transação
            $db->commit();

            // Define a mensagem de sucesso
            setMensagem('sucesso', $mensagem);

            // Redireciona para a listagem
            redirect('mensalidades.php');
            exit;

        } catch (Exception $e) {
            // Desfaz a transação em caso de erro
            $db->rollBack();

            // Define a mensagem de erro
            setMensagem('erro', 'Erro ao salvar a mensalidade: ' . $e->getMessage());

            // Redireciona de volta para o formulário
            if ($id) {
                redirect("mensalidades.php?action=editar&id=$id");
            } else {
                redirect('mensalidades.php?action=nova');
            }
            exit;
        }
        break;

    case 'excluir':
        // Verifica permissão para excluir
        if (!Auth::hasPermission('financeiro', 'excluir')) {
            setMensagem('erro', 'Você não tem permissão para excluir mensalidades.');
            redirect('mensalidades.php');
            exit;
        }

        // Verifica se o ID foi informado
        if (!isset($_GET['id']) || empty($_GET['id'])) {
            setMensagem('erro', 'ID da mensalidade não informado.');
            redirect('mensalidades.php');
            exit;
        }

        // Busca a mensalidade
        $id = (int)$_GET['id'];
        $sql = "SELECT * FROM lancamentos_financeiros WHERE id = ?";
        $mensalidade = $db->fetchOne($sql, [$id]);

        if (!$mensalidade) {
            setMensagem('erro', 'Mensalidade não encontrada.');
            redirect('mensalidades.php');
            exit;
        }

        try {
            // Exclui a mensalidade
            $db->delete('lancamentos_financeiros', 'id = ?', [$id]);

            // Define a mensagem de sucesso
            setMensagem('sucesso', 'Mensalidade excluída com sucesso.');

        } catch (Exception $e) {
            // Define a mensagem de erro
            setMensagem('erro', 'Erro ao excluir a mensalidade: ' . $e->getMessage());
        }

        // Redireciona para a listagem
        redirect('mensalidades.php');
        exit;
        break;

    case 'pagar':
        // Verifica permissão para editar
        if (!Auth::hasPermission('financeiro', 'editar')) {
            setMensagem('erro', 'Você não tem permissão para registrar pagamentos.');
            redirect('mensalidades.php');
            exit;
        }

        // Verifica se o ID foi informado
        if (!isset($_GET['id']) || empty($_GET['id'])) {
            setMensagem('erro', 'ID da mensalidade não informado.');
            redirect('mensalidades.php');
            exit;
        }

        // Busca a mensalidade
        $id = (int)$_GET['id'];
        $sql = "SELECT lf.*, a.nome as aluno_nome, a.cpf as aluno_cpf, c.nome as curso_nome, p.nome as polo_nome
                FROM lancamentos_financeiros lf
                LEFT JOIN alunos a ON lf.aluno_id = a.id
                LEFT JOIN matriculas m ON a.id = m.aluno_id AND m.status = 'ativo'
                LEFT JOIN cursos c ON m.curso_id = c.id
                LEFT JOIN polos p ON m.polo_id = p.id
                WHERE lf.id = ?";
        $mensalidade = $db->fetchOne($sql, [$id]);

        if (!$mensalidade) {
            setMensagem('erro', 'Mensalidade não encontrada.');
            redirect('mensalidades.php');
            exit;
        }

        // Se a mensalidade já foi paga, redireciona
        if ($mensalidade['status'] === 'pago') {
            setMensagem('info', 'Esta mensalidade já foi paga.');
            redirect('mensalidades.php');
            exit;
        }

        // Define o título da página
        $titulo_pagina = 'Registrar Pagamento de Mensalidade';
        $view = 'pagar';
        break;

    case 'salvar_recorrente':
        // Verifica permissão para criar
        if (!Auth::hasPermission('financeiro', 'criar')) {
            setMensagem('erro', 'Você não tem permissão para criar mensalidades.');
            redirect('mensalidades.php');
            exit;
        }

        // Preserva os parâmetros de busca para retornar à mesma página após o processamento
        $busca_aluno = isset($_GET['busca_aluno']) ? $_GET['busca_aluno'] : '';
        $filtro_status = isset($_GET['filtro_status']) ? $_GET['filtro_status'] : '';
        $pagina_alunos = isset($_GET['pagina_alunos']) ? (int)$_GET['pagina_alunos'] : 1;

        $redirect_params = [];
        if (!empty($busca_aluno)) $redirect_params['busca_aluno'] = $busca_aluno;
        if (!empty($filtro_status)) $redirect_params['filtro_status'] = $filtro_status;
        if ($pagina_alunos > 1) $redirect_params['pagina_alunos'] = $pagina_alunos;

        // Verifica se é POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            setMensagem('erro', 'Método inválido.');
            redirect('mensalidades.php');
            exit;
        }

        // Verifica se o formulário foi enviado corretamente
        if (!isset($_POST['form_submitted'])) {
            error_log("Formulário não foi enviado corretamente. Falta o campo form_submitted.");
            setMensagem('erro', 'O formulário não foi enviado corretamente. Por favor, tente novamente.');
            redirect('mensalidades.php?action=recorrente');
            exit;
        }

        // Log para debug
        error_log("POST data: " . json_encode($_POST));

        // Obtém os dados do formulário
        $aluno_ids = $_POST['aluno_ids'] ?? [];
        $categoria_id = isset($_POST['categoria_id']) ? (int)$_POST['categoria_id'] : null;
        $plano_conta_id = isset($_POST['plano_conta_id']) ? (int)$_POST['plano_conta_id'] : null;
        $descricao = $_POST['descricao'] ?? '';
        $valor = str_replace(',', '.', $_POST['valor'] ?? 0);
        $desconto = str_replace(',', '.', $_POST['desconto'] ?? 0);
        $acrescimo = str_replace(',', '.', $_POST['acrescimo'] ?? 0);
        $data_vencimento_inicial = $_POST['data_vencimento_inicial'] ?? date('Y-m-d');
        $forma_pagamento = $_POST['forma_pagamento'] ?? null;
        $dia_vencimento = isset($_POST['dia_vencimento']) ? (int)$_POST['dia_vencimento'] : date('d');
        $total_meses = isset($_POST['total_meses']) ? (int)$_POST['total_meses'] : 12;

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

        if (empty($data_vencimento_inicial) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_vencimento_inicial)) {
            $erros[] = 'A data de vencimento inicial é obrigatória e deve estar no formato correto.';
        }

        if ($dia_vencimento < 1 || $dia_vencimento > 28) {
            $erros[] = 'O dia de vencimento deve estar entre 1 e 28.';
        }

        if ($total_meses <= 0 || $total_meses > 36) {
            $erros[] = 'O número de meses deve estar entre 1 e 36.';
        }

        // Se houver erros, redireciona de volta para o formulário
        if (!empty($erros)) {
            $_SESSION['form_data'] = $_POST;
            $_SESSION['form_errors'] = $erros;

            // Redireciona mantendo os filtros
            if (empty($redirect_params)) {
                redirect('mensalidades.php?action=recorrente');
            } else {
                $query_string = http_build_query($redirect_params);
                redirect('mensalidades.php?action=recorrente&' . $query_string);
            }
            exit;
        }

        try {
            // Inicia a transação
            $db->beginTransaction();

            // Contador de mensalidades geradas
            $mensalidades_geradas = 0;

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
                    continue; // Pula este aluno se não for encontrado
                }

                // Define valores padrão para campos que podem estar vazios
                $matricula_id = $aluno['matricula_id'] ?? null;
                $curso_id = $aluno['curso_id'] ?? null;
                $polo_id = $aluno['polo_id'] ?? null;

                // Gera as mensalidades para este aluno
                $data_base = new DateTime($data_vencimento_inicial);

                // Log para debug
                error_log("Processando aluno: {$aluno['nome']} (ID: $aluno_id), Polo ID: $polo_id, Curso ID: $curso_id");

                // Ajusta para o dia de vencimento escolhido a partir do segundo mês
                $primeiro_mes = true;

                for ($i = 1; $i <= $total_meses; $i++) {
                    // Se não for o primeiro mês, ajusta para o dia de vencimento escolhido
                    if (!$primeiro_mes) {
                        // Obtém o ano e mês atual
                        $ano = $data_base->format('Y');
                        $mes = $data_base->format('m');

                        // Cria uma nova data com o dia de vencimento escolhido
                        $data_base = new DateTime("$ano-$mes-$dia_vencimento");
                    }

                    $dados_mensalidade = [
                        'aluno_id' => $aluno_id,
                        'categoria_id' => $categoria_id,
                        'plano_conta_id' => $plano_conta_id,
                        'tipo' => 'receita',
                        'descricao' => $descricao . ' - ' . $data_base->format('m/Y'),
                        'valor' => $valor,
                        'desconto' => $desconto,
                        'acrescimo' => $acrescimo,
                        'data_vencimento' => $data_base->format('Y-m-d'),
                        'forma_pagamento' => $forma_pagamento,
                        'status' => 'pendente',
                        'data_pagamento' => null,
                        'data_lancamento' => date('Y-m-d'),
                        'usuario_id' => Auth::getUserId(), // Usando a função correta para obter o ID do usuário
                        'matricula_id' => $matricula_id,
                        'curso_id' => $curso_id,
                        'polo_id' => $polo_id,
                        'numero_parcela' => $i,
                        'total_parcelas' => $total_meses,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];

                    // Log antes da inserção
                    error_log("Tentando inserir mensalidade para aluno {$aluno['nome']} (ID: $aluno_id), vencimento: {$dados_mensalidade['data_vencimento']}");

                    // Insere a mensalidade
                    $mensalidade_id = $db->insert('lancamentos_financeiros', $dados_mensalidade);
                    $mensalidades_geradas++;

                    // Log após a inserção
                    error_log("Mensalidade gerada com sucesso: ID=$mensalidade_id, Aluno={$aluno['nome']}, Valor={$valor}, Vencimento={$dados_mensalidade['data_vencimento']}");

                    // Avança para o próximo mês
                    $data_base->modify('+1 month');
                    $primeiro_mes = false;
                }
            }

            // Confirma a transação
            $db->commit();

            // Define a mensagem de sucesso
            setMensagem('sucesso', "Foram geradas $mensalidades_geradas mensalidades com sucesso.");

            // Redireciona para a listagem de mensalidades
            redirect('mensalidades.php');
            exit;

        } catch (Exception $e) {
            // Desfaz a transação em caso de erro
            $db->rollBack();

            // Log detalhado do erro
            error_log('ERRO ao gerar mensalidades recorrentes: ' . $e->getMessage());
            error_log('Trace: ' . $e->getTraceAsString());

            // Define a mensagem de erro
            setMensagem('erro', 'Erro ao gerar mensalidades recorrentes: ' . $e->getMessage());

            // Redireciona de volta para o formulário mantendo os filtros
            if (empty($redirect_params)) {
                redirect('mensalidades.php?action=recorrente');
            } else {
                $query_string = http_build_query($redirect_params);
                redirect('mensalidades.php?action=recorrente&' . $query_string);
            }
            exit;
        }
        break;

    case 'registrar_pagamento':
        // Verifica permissão para editar
        if (!Auth::hasPermission('financeiro', 'editar')) {
            setMensagem('erro', 'Você não tem permissão para registrar pagamentos.');
            redirect('mensalidades.php');
            exit;
        }

        // Verifica se é POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            setMensagem('erro', 'Método inválido.');
            redirect('mensalidades.php');
            exit;
        }

        // Obtém os dados do formulário
        $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
        $data_pagamento = $_POST['data_pagamento'] ?? date('Y-m-d');
        $forma_pagamento = $_POST['forma_pagamento'] ?? null;
        $valor_pago = str_replace(',', '.', $_POST['valor_pago'] ?? 0);
        $desconto = str_replace(',', '.', $_POST['desconto'] ?? 0);
        $acrescimo = str_replace(',', '.', $_POST['acrescimo'] ?? 0);

        // Validação básica
        $erros = [];

        if (empty($id)) {
            $erros[] = 'ID da mensalidade não informado.';
        }

        if (empty($data_pagamento) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_pagamento)) {
            $erros[] = 'A data de pagamento é obrigatória e deve estar no formato correto.';
        }

        if (empty($forma_pagamento)) {
            $erros[] = 'A forma de pagamento é obrigatória.';
        }

        if (empty($valor_pago) || !is_numeric($valor_pago) || $valor_pago <= 0) {
            $erros[] = 'O valor pago deve ser um número maior que zero.';
        }

        // Se houver erros, redireciona de volta para o formulário
        if (!empty($erros)) {
            $_SESSION['form_data'] = $_POST;
            $_SESSION['form_errors'] = $erros;
            redirect("mensalidades.php?action=pagar&id=$id");
            exit;
        }

        try {
            // Busca a mensalidade
            $sql = "SELECT * FROM lancamentos_financeiros WHERE id = ?";
            $mensalidade = $db->fetchOne($sql, [$id]);

            if (!$mensalidade) {
                setMensagem('erro', 'Mensalidade não encontrada.');
                redirect('mensalidades.php');
                exit;
            }

            // Atualiza a mensalidade
            $dados = [
                'status' => 'pago',
                'data_pagamento' => $data_pagamento,
                'forma_pagamento' => $forma_pagamento,
                'desconto' => $desconto,
                'acrescimo' => $acrescimo,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $db->update('lancamentos_financeiros', $dados, 'id = ?', [$id]);

            // Define a mensagem de sucesso
            setMensagem('sucesso', 'Pagamento registrado com sucesso.');

            // Redireciona para a listagem
            redirect('mensalidades.php');
            exit;

        } catch (Exception $e) {
            // Define a mensagem de erro
            setMensagem('erro', 'Erro ao registrar o pagamento: ' . $e->getMessage());

            // Redireciona de volta para o formulário
            redirect("mensalidades.php?action=pagar&id=$id");
            exit;
        }
        break;

    case 'listar':
    default:
        // Parâmetros de filtro e paginação
        $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
        $por_pagina = 20;
        $offset = ($pagina - 1) * $por_pagina;

        // Filtros
        $filtros = [];
        $params = [];
        $where = ["lf.tipo = 'receita'"];

        // Filtro por aluno
        if (isset($_GET['aluno']) && !empty($_GET['aluno'])) {
            $where[] = "(a.nome LIKE ? OR a.cpf LIKE ?)";
            $params[] = '%' . $_GET['aluno'] . '%';
            $params[] = '%' . $_GET['aluno'] . '%';
            $filtros['aluno'] = $_GET['aluno'];
        }

        // Filtro por status
        if (isset($_GET['status']) && !empty($_GET['status'])) {
            $where[] = "lf.status = ?";
            $params[] = $_GET['status'];
            $filtros['status'] = $_GET['status'];
        }

        // Filtro por período de vencimento
        if (isset($_GET['data_vencimento_inicio']) && !empty($_GET['data_vencimento_inicio'])) {
            $where[] = "lf.data_vencimento >= ?";
            $params[] = $_GET['data_vencimento_inicio'];
            $filtros['data_vencimento_inicio'] = $_GET['data_vencimento_inicio'];
        }

        if (isset($_GET['data_vencimento_fim']) && !empty($_GET['data_vencimento_fim'])) {
            $where[] = "lf.data_vencimento <= ?";
            $params[] = $_GET['data_vencimento_fim'];
            $filtros['data_vencimento_fim'] = $_GET['data_vencimento_fim'];
        }

        // Filtro por polo
        if (isset($_GET['polo_id']) && !empty($_GET['polo_id'])) {
            $where[] = "m.polo_id = ?";
            $params[] = (int)$_GET['polo_id'];
            $filtros['polo_id'] = (int)$_GET['polo_id'];
        }

        // Filtro por curso
        if (isset($_GET['curso_id']) && !empty($_GET['curso_id'])) {
            $where[] = "m.curso_id = ?";
            $params[] = (int)$_GET['curso_id'];
            $filtros['curso_id'] = (int)$_GET['curso_id'];
        }

        // Monta a cláusula WHERE
        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        // Consulta para contar o total de registros
        $sql = "SELECT COUNT(*) as total
                FROM lancamentos_financeiros lf
                LEFT JOIN alunos a ON lf.aluno_id = a.id
                LEFT JOIN matriculas m ON a.id = m.aluno_id AND m.status = 'ativo'
                $whereClause";
        $resultado = $db->fetchOne($sql, $params);
        $total_registros = $resultado['total'] ?? 0;

        // Calcula o total de páginas
        $total_paginas = ceil($total_registros / $por_pagina);

        // Consulta para buscar as mensalidades
        $sql = "SELECT lf.*, a.nome as aluno_nome, a.cpf as aluno_cpf, c.nome as curso_nome, p.nome as polo_nome
                FROM lancamentos_financeiros lf
                LEFT JOIN alunos a ON lf.aluno_id = a.id
                LEFT JOIN matriculas m ON a.id = m.aluno_id AND m.status = 'ativo'
                LEFT JOIN cursos c ON m.curso_id = c.id
                LEFT JOIN polos p ON m.polo_id = p.id
                $whereClause
                ORDER BY lf.data_vencimento ASC, lf.id DESC
                LIMIT $offset, $por_pagina";
        $mensalidades = $db->fetchAll($sql, $params);

        // Busca os polos para o filtro
        $sql = "SELECT id, nome FROM polos WHERE status = 'ativo' ORDER BY nome";
        $polos = $db->fetchAll($sql);

        // Busca os cursos para o filtro
        $sql = "SELECT id, nome FROM cursos WHERE status = 'ativo' ORDER BY nome";
        $cursos = $db->fetchAll($sql);

        // Define o título da página
        $titulo_pagina = 'Mensalidades';
        $view = 'listar';
        break;
}

// Define o título da página
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
    <style>
        .page-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 4rem;
            height: 0.25rem;
            background: linear-gradient(to right, #3b82f6, #8b5cf6);
            border-radius: 0.125rem;
        }

        /* Estilos específicos para mensalidades */
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 9999px;
        }

        .status-pendente {
            background-color: #FEF3C7;
            color: #D97706;
        }

        .status-pago {
            background-color: #D1FAE5;
            color: #059669;
        }

        .status-parcial {
            background-color: #E0E7FF;
            color: #4F46E5;
        }

        .status-cancelado {
            background-color: #FEE2E2;
            color: #DC2626;
        }

        .vencido {
            color: #DC2626;
            font-weight: 600;
        }

        .card {
            border-radius: 1rem;
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
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <?php include 'includes/header.php'; ?>

            <!-- Main -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
                <div class="container mx-auto">
                    <h1 class="text-3xl font-bold text-gray-800 mb-6 page-header relative pb-3"><?php echo $titulo_pagina; ?></h1>

                    <!-- Conteúdo da página -->
                    <?php
                    // Verifica se a view existe
                    $view_file = __DIR__ . "/views/mensalidades/$view.php";
                    if (file_exists($view_file)) {
                        include $view_file;
                    } else {
                        echo '<div class="bg-white rounded-lg shadow-sm p-6">';
                        echo '<p class="text-gray-600">A visualização solicitada não foi encontrada. Por favor, crie o arquivo <strong>' . $view_file . '</strong>.</p>';
                        echo '<p class="mt-4"><a href="mensalidades.php" class="text-blue-600 hover:text-blue-800">Voltar para a listagem</a></p>';
                        echo '</div>';
                    }
                    ?>
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
