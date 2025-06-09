<?php
/**
 * Página de gerenciamento de disciplinas - Versão simplificada
 */

// Inicializa o sistema
require_once __DIR__ . '/includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica se o usuário tem permissão para acessar o módulo de disciplinas
exigirPermissao('disciplinas');

// Instancia o banco de dados
$db = Database::getInstance();

// Define a ação atual
$action = isset($_GET['action']) ? $_GET['action'] : 'listar';

// Processa a ação
switch ($action) {
    case 'nova':
        // Exibe o formulário para adicionar uma nova disciplina
        $titulo_pagina = 'Nova Disciplina';
        $view = 'form';
        $disciplina = []; // Inicializa uma disciplina vazia

        // Se foi passado um curso_id, pré-seleciona o curso
        if (isset($_GET['curso_id'])) {
            $disciplina['curso_id'] = $_GET['curso_id'];
        }

        // Carrega os cursos para o formulário
        $sql = "SELECT id, nome FROM cursos ORDER BY nome ASC";
        $cursos = $db->fetchAll($sql);

        // Não carregamos professores pois a tabela não existe
        $professores = [];
        break;

    case 'editar':
        // Exibe o formulário para editar uma disciplina existente
        $id = isset($_GET['id']) ? $_GET['id'] : 0;

        // Busca a disciplina pelo ID
        $sql = "SELECT * FROM disciplinas WHERE id = ?";
        $disciplina = $db->fetchOne($sql, [$id]);

        if (!$disciplina) {
            // Disciplina não encontrada, redireciona para a listagem
            setMensagem('erro', 'Disciplina não encontrada.');
            redirect('disciplinas_simples.php?action=listar');
        }

        // Carrega os cursos para o formulário
        $sql = "SELECT id, nome FROM cursos ORDER BY nome ASC";
        $cursos = $db->fetchAll($sql);

        // Não carregamos professores pois a tabela não existe
        $professores = [];

        $titulo_pagina = 'Editar Disciplina';
        $view = 'form';
        break;

    case 'salvar':
        // Salva os dados da disciplina (nova ou existente)
        if (!isPost()) {
            // Método não permitido
            setMensagem('erro', 'Método não permitido.');
            redirect('disciplinas_simples.php?action=listar');
        }

        // Obtém os dados do formulário
        $id = isset($_POST['id']) ? $_POST['id'] : null;
        $nome = isset($_POST['nome']) ? $_POST['nome'] : '';
        $codigo = isset($_POST['codigo']) ? $_POST['codigo'] : '';
        $curso_id = isset($_POST['curso_id']) ? $_POST['curso_id'] : null;
        $professor_padrao_id = isset($_POST['professor_padrao_id']) ? $_POST['professor_padrao_id'] : null;
        $carga_horaria = isset($_POST['carga_horaria']) ? $_POST['carga_horaria'] : 0;
        $ementa = isset($_POST['ementa']) ? $_POST['ementa'] : '';
        $bibliografia = isset($_POST['bibliografia']) ? $_POST['bibliografia'] : '';
        $status = isset($_POST['status']) ? $_POST['status'] : 'ativo';
        $id_legado = isset($_POST['id_legado']) ? $_POST['id_legado'] : '';

        // Valida os dados
        $erros = [];

        if (empty($nome)) {
            $erros[] = 'O nome é obrigatório.';
        }

        if (empty($curso_id)) {
            $erros[] = 'O curso é obrigatório.';
        }

        if (!empty($erros)) {
            // Há erros de validação, exibe o formulário novamente
            $titulo_pagina = $id ? 'Editar Disciplina' : 'Nova Disciplina';
            $view = 'form';
            $disciplina = $_POST;
            $mensagens_erro = $erros;

            // Carrega os cursos para o formulário
            $sql = "SELECT id, nome FROM cursos ORDER BY nome ASC";
            $cursos = $db->fetchAll($sql);

            // Não carregamos professores pois a tabela não existe
            $professores = [];

            break;
        }

        // Prepara os dados para salvar
        $dados = [
            'nome' => $nome,
            'codigo' => $codigo,
            'curso_id' => $curso_id,
            'professor_padrao_id' => $professor_padrao_id ?: null,
            'carga_horaria' => $carga_horaria,
            'ementa' => $ementa,
            'bibliografia' => $bibliografia,
            'status' => $status,
            'id_legado' => $id_legado,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        try {
            // Inicia uma transação
            $db->beginTransaction();

            if ($id) {
                // Atualiza uma disciplina existente
                $db->update('disciplinas', $dados, 'id = ?', [$id]);
                setMensagem('sucesso', 'Disciplina atualizada com sucesso.');
            } else {
                // Adiciona a data de criação
                $dados['created_at'] = date('Y-m-d H:i:s');

                // Insere uma nova disciplina
                $id = $db->insert('disciplinas', $dados);
                setMensagem('sucesso', 'Disciplina adicionada com sucesso.');
            }

            // Confirma a transação
            $db->commit();

            // Redireciona para a visualização da disciplina
            redirect('disciplinas_simples.php?action=visualizar&id=' . $id);
        } catch (Exception $e) {
            // Desfaz a transação em caso de erro
            $db->rollBack();

            // Erro ao salvar
            $titulo_pagina = $id ? 'Editar Disciplina' : 'Nova Disciplina';
            $view = 'form';
            $disciplina = $_POST;
            $mensagens_erro = ['Erro ao salvar a disciplina: ' . $e->getMessage()];

            // Carrega os cursos para o formulário
            $sql = "SELECT id, nome FROM cursos ORDER BY nome ASC";
            $cursos = $db->fetchAll($sql);

            // Não carregamos professores pois a tabela não existe
            $professores = [];
        }
        break;

    case 'excluir':
        // Exclui uma disciplina
        $id = isset($_GET['id']) ? $_GET['id'] : 0;

        // Verifica se o usuário tem permissão para excluir
        exigirPermissao('disciplinas', 'excluir');

        // Busca a disciplina pelo ID
        $sql = "SELECT * FROM disciplinas WHERE id = ?";
        $disciplina = $db->fetchOne($sql, [$id]);

        if (!$disciplina) {
            // Disciplina não encontrada, redireciona para a listagem
            setMensagem('erro', 'Disciplina não encontrada.');
            redirect('disciplinas_simples.php?action=listar');
        }

        try {
            // Inicia uma transação
            $db->beginTransaction();

            // Exclui a disciplina
            $db->delete('disciplinas', 'id = ?', [$id]);

            // Confirma a transação
            $db->commit();

            setMensagem('sucesso', 'Disciplina excluída com sucesso.');
        } catch (Exception $e) {
            // Desfaz a transação em caso de erro
            $db->rollBack();

            // Erro ao excluir
            setMensagem('erro', 'Erro ao excluir a disciplina: ' . $e->getMessage());
        }

        // Redireciona para a listagem
        redirect('disciplinas_simples.php?action=listar');
        break;

    case 'visualizar':
        // Exibe os detalhes de uma disciplina
        $id = isset($_GET['id']) ? $_GET['id'] : 0;

        // Busca a disciplina pelo ID com informações do curso
        $sql = "SELECT d.*,
                       c.nome as curso_nome
                FROM disciplinas d
                LEFT JOIN cursos c ON d.curso_id = c.id
                WHERE d.id = ?";
        $disciplina = $db->fetchOne($sql, [$id]);

        if (!$disciplina) {
            // Tenta buscar apenas a disciplina sem os joins
            $sql = "SELECT * FROM disciplinas WHERE id = ?";
            $disciplina = $db->fetchOne($sql, [$id]);

            if (!$disciplina) {
                // Disciplina não encontrada, redireciona para a listagem
                setMensagem('erro', 'Disciplina não encontrada.');
                redirect('disciplinas_simples.php?action=listar');
            }

            // Busca o curso separadamente
            if (!empty($disciplina['curso_id'])) {
                $sql = "SELECT nome FROM cursos WHERE id = ?";
                $curso = $db->fetchOne($sql, [$disciplina['curso_id']]);
                if ($curso) {
                    $disciplina['curso_nome'] = $curso['nome'];
                }
            }
        }

        $titulo_pagina = 'Detalhes da Disciplina';
        $view = 'visualizar';
        break;

    case 'listar':
    default:
        // Lista todas as disciplinas
        $status = isset($_GET['status']) ? $_GET['status'] : 'todos';
        $curso_id = isset($_GET['curso_id']) ? $_GET['curso_id'] : null;
        $ordenar = isset($_GET['ordenar']) ? $_GET['ordenar'] : null;
        $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
        $por_pagina = 20;
        $offset = ($pagina - 1) * $por_pagina;

        // Monta a consulta SQL
        $where = [];
        $params = [];

        // Adiciona condição de status apenas se não for 'todos'
        if ($status !== 'todos') {
            $where[] = "status = ?";
            $params[] = $status;
        }

        if (!empty($curso_id)) {
            $where[] = "curso_id = ?";
            $params[] = $curso_id;
        }

        // Monta a cláusula WHERE
        $whereClause = '';
        if (!empty($where)) {
            $whereClause = "WHERE " . implode(" AND ", $where);
        }

        // Define a ordenação
        $orderBy = "nome ASC";
        if ($ordenar === 'recentes') {
            $orderBy = "created_at DESC";
        }

        // Consulta principal
        $sql = "SELECT * FROM disciplinas {$whereClause} ORDER BY {$orderBy} LIMIT {$offset}, {$por_pagina}";
        $disciplinas = $db->fetchAll($sql, $params);

        // Adiciona informações de curso e professor
        foreach ($disciplinas as $key => $disciplina) {
            // Busca o curso
            if (!empty($disciplina['curso_id'])) {
                $sql = "SELECT nome FROM cursos WHERE id = ?";
                $curso = $db->fetchOne($sql, [$disciplina['curso_id']]);
                $disciplinas[$key]['curso_nome'] = $curso ? $curso['nome'] : 'Curso não encontrado';
            }

            // Não buscamos professor pois a tabela não existe
            $disciplinas[$key]['professor_nome'] = 'N/A';
        }

        // Conta o total de disciplinas
        $sql = "SELECT COUNT(*) as total FROM disciplinas {$whereClause}";
        $resultado = $db->fetchOne($sql, $params);
        $total_disciplinas = $resultado['total'] ?? 0;

        // Calcula o total de páginas
        $total_paginas = ceil($total_disciplinas / $por_pagina);

        // Carrega os cursos para o filtro
        $sql = "SELECT id, nome FROM cursos ORDER BY nome ASC";
        $cursos = $db->fetchAll($sql);

        // Busca estatísticas para o dashboard
        try {
            // Total de disciplinas por status
            $sql = "SELECT status, COUNT(*) as total FROM disciplinas GROUP BY status";
            $status_counts = $db->fetchAll($sql);

            $total_ativas = 0;
            $total_inativas = 0;

            foreach ($status_counts as $status_count) {
                switch ($status_count['status']) {
                    case 'ativo':
                        $total_ativas = $status_count['total'];
                        break;
                    case 'inativo':
                        $total_inativas = $status_count['total'];
                        break;
                }
            }

            // Disciplinas recentes (dos últimos 30 dias)
            $sql = "SELECT COUNT(*) as total FROM disciplinas WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            $recentes_result = $db->fetchOne($sql);
            $total_recentes = $recentes_result['total'] ?? 0;

            // Busca as disciplinas mais recentes para exibir no dashboard
            $sql = "SELECT * FROM disciplinas ORDER BY created_at DESC LIMIT 5";
            $disciplinas_recentes = $db->fetchAll($sql);

            // Adiciona informações de curso e professor para as disciplinas recentes
            foreach ($disciplinas_recentes as $key => $disciplina) {
                // Busca o curso
                if (!empty($disciplina['curso_id'])) {
                    $sql = "SELECT nome FROM cursos WHERE id = ?";
                    $curso = $db->fetchOne($sql, [$disciplina['curso_id']]);
                    $disciplinas_recentes[$key]['curso_nome'] = $curso ? $curso['nome'] : 'Curso não encontrado';
                }

                // Não buscamos professor pois a tabela não existe
                $disciplinas_recentes[$key]['professor_nome'] = 'N/A';
            }
        } catch (Exception $e) {
            $total_ativas = 0;
            $total_inativas = 0;
            $total_recentes = 0;
            $disciplinas_recentes = [];
        }

        $titulo_pagina = 'Disciplinas';
        $view = 'listar';
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
    <link rel="stylesheet" href="css/styles.css">
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

                        <div class="flex space-x-2">
                            <?php if ($view === 'listar'): ?>
                            <a href="disciplinas_simples.php?action=nova" class="btn-primary">
                                <i class="fas fa-plus mr-2"></i> Nova Disciplina
                            </a>
                            <?php endif; ?>

                            <?php if ($view === 'visualizar'): ?>
                            <a href="disciplinas_simples.php?action=editar&id=<?php echo $disciplina['id']; ?>" class="btn-secondary">
                                <i class="fas fa-edit mr-2"></i> Editar
                            </a>
                            <a href="javascript:void(0);" onclick="confirmarExclusao(<?php echo $disciplina['id']; ?>)" class="btn-danger">
                                <i class="fas fa-trash mr-2"></i> Excluir
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (isset($mensagens_erro) && !empty($mensagens_erro)): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
                        <ul class="list-disc list-inside">
                            <?php foreach ($mensagens_erro as $erro): ?>
                            <li><?php echo $erro; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['mensagem'])): ?>
                    <div class="bg-<?php echo isset($_SESSION['mensagem_tipo']) && $_SESSION['mensagem_tipo'] === 'sucesso' ? 'green' : 'red'; ?>-100 border-l-4 border-<?php echo isset($_SESSION['mensagem_tipo']) && $_SESSION['mensagem_tipo'] === 'sucesso' ? 'green' : 'red'; ?>-500 text-<?php echo isset($_SESSION['mensagem_tipo']) && $_SESSION['mensagem_tipo'] === 'sucesso' ? 'green' : 'red'; ?>-700 p-4 mb-6">
                        <?php echo is_array($_SESSION['mensagem']) ? implode('<br>', $_SESSION['mensagem']) : $_SESSION['mensagem']; ?>
                    </div>
                    <?php
                    // Limpa a mensagem da sessão
                    unset($_SESSION['mensagem']);
                    if (isset($_SESSION['mensagem_tipo'])) {
                        unset($_SESSION['mensagem_tipo']);
                    }
                    endif;
                    ?>

                    <?php
                    // Inclui a view correspondente
                    switch ($view) {
                        case 'form':
                            include 'views/disciplinas/form.php';
                            break;
                        case 'visualizar':
                            include 'views/disciplinas/visualizar_simples.php';
                            break;
                        case 'listar':
                        default:
                            include 'views/disciplinas/listar_simples.php';
                            break;
                    }
                    ?>
                </div>
            </main>

            <!-- Footer -->
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>

    <!-- Modal de Confirmação de Exclusão -->
    <div id="modal-exclusao" class="fixed z-10 inset-0 overflow-y-auto hidden">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-exclamation-triangle text-red-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Confirmar Exclusão
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500" id="modal-message">
                                    Tem certeza que deseja excluir esta disciplina?
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <a href="#" id="btn-confirmar-exclusao" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Confirmar
                    </a>
                    <button type="button" onclick="fecharModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script>
        function confirmarExclusao(id) {
            document.getElementById('btn-confirmar-exclusao').href = `disciplinas_simples.php?action=excluir&id=${id}`;
            document.getElementById('modal-exclusao').classList.remove('hidden');
        }

        function fecharModal() {
            document.getElementById('modal-exclusao').classList.add('hidden');
        }
    </script>
</body>
</html>
