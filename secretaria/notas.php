<?php
/**
 * Sistema de Lançamento de Notas
 * Versão reformulada com paginação, busca e filtros
 */

// Inicializa o sistema
require_once __DIR__ . '/includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica se o usuário tem permissão para acessar o módulo de notas
exigirPermissao('notas');

// Instancia o banco de dados
$db = Database::getInstance();

// Define a ação atual
$action = $_GET['action'] ?? $_POST['action'] ?? 'listar';

// Processar as ações
switch ($action) {
    case 'listar':
        // Lista todas as notas com paginação, busca e filtros
        $titulo_pagina = 'Gerenciar Notas';

        // Parâmetros de busca e filtro
        $termo = $_GET['termo'] ?? '';
        $campo = $_GET['campo'] ?? 'aluno_nome';
        $curso_id = $_GET['curso_id'] ?? null;
        $turma_id = $_GET['turma_id'] ?? null;
        $disciplina_id = $_GET['disciplina_id'] ?? null;
        $situacao = $_GET['situacao'] ?? '';
        $pagina = $_GET['pagina'] ?? 1;
        $por_pagina = 20;
        $offset = ($pagina - 1) * $por_pagina;

        // Trata valores vazios como null
        if (empty($curso_id)) $curso_id = null;
        if (empty($turma_id)) $turma_id = null;
        if (empty($disciplina_id)) $disciplina_id = null;

        // Constrói a consulta base
        $sql_base = "FROM notas_disciplinas nd
                     JOIN matriculas m ON nd.matricula_id = m.id
                     JOIN alunos a ON m.aluno_id = a.id
                     JOIN disciplinas d ON nd.disciplina_id = d.id
                     JOIN cursos c ON d.curso_id = c.id
                     LEFT JOIN turmas t ON m.turma_id = t.id
                     WHERE 1=1";

        $params = [];

        // Aplica filtros
        if (!empty($termo)) {
            $campos_busca = [
                'aluno_nome' => 'a.nome',
                'aluno_cpf' => 'a.cpf',
                'disciplina' => 'd.nome',
                'curso' => 'c.nome',
                'turma' => 't.nome'
            ];

            if (isset($campos_busca[$campo])) {
                $sql_base .= " AND {$campos_busca[$campo]} LIKE ?";
                $params[] = "%{$termo}%";
            }
        }

        if ($curso_id) {
            $sql_base .= " AND c.id = ?";
            $params[] = $curso_id;
        }

        if ($turma_id) {
            $sql_base .= " AND t.id = ?";
            $params[] = $turma_id;
        }

        if ($disciplina_id) {
            $sql_base .= " AND d.id = ?";
            $params[] = $disciplina_id;
        }

        if (!empty($situacao)) {
            $sql_base .= " AND nd.situacao = ?";
            $params[] = $situacao;
        }

        // Conta o total de registros
        $sql_count = "SELECT COUNT(*) as total " . $sql_base;
        $total_resultado = $db->fetchOne($sql_count, $params);
        $total_notas = $total_resultado['total'] ?? 0;
        $total_paginas = ceil($total_notas / $por_pagina);

        // Busca os registros com paginação
        $sql = "SELECT nd.id, nd.nota, nd.frequencia, nd.horas_aula, nd.data_lancamento,
                       nd.situacao, nd.observacoes, nd.created_at, nd.updated_at,
                       a.id as aluno_id, a.nome as aluno_nome, a.cpf as aluno_cpf,
                       d.id as disciplina_id, d.nome as disciplina_nome, d.codigo as disciplina_codigo,
                       c.id as curso_id, c.nome as curso_nome, c.sigla as curso_sigla,
                       t.id as turma_id, t.nome as turma_nome,
                       m.id as matricula_id
                " . $sql_base . "
                ORDER BY nd.updated_at DESC, a.nome ASC
                LIMIT ? OFFSET ?";

        $params[] = $por_pagina;
        $params[] = $offset;

        $notas = $db->fetchAll($sql, $params) ?: [];

        // Busca dados para os filtros
        $cursos = $db->fetchAll("SELECT id, nome FROM cursos WHERE status = 'ativo' ORDER BY nome ASC") ?: [];
        $turmas = [];
        $disciplinas = [];

        if ($curso_id) {
            $turmas = $db->fetchAll("SELECT id, nome FROM turmas WHERE curso_id = ? ORDER BY nome ASC", [$curso_id]) ?: [];
            $disciplinas = $db->fetchAll("SELECT id, nome FROM disciplinas WHERE curso_id = ? ORDER BY nome ASC", [$curso_id]) ?: [];
        }

        $view = 'listar';
        break;

    case 'lancar':
        // Lançar notas para uma turma
        $titulo_pagina = 'Lançar Notas';

        $curso_id = $_GET['curso_id'] ?? null;
        $turma_id = $_GET['turma_id'] ?? null;
        $disciplina_id = $_GET['disciplina_id'] ?? null;

        // Se não tiver parâmetros, mostra seleção de curso/turma
        if (!$curso_id || !$turma_id) {
            $cursos = $db->fetchAll("SELECT id, nome FROM cursos WHERE status = 'ativo' ORDER BY nome ASC") ?: [];
            $view = 'selecionar_turma';
            break;
        }

        // Busca informações do curso e turma
        $curso = $db->fetchOne("SELECT id, nome FROM cursos WHERE id = ? AND status = 'ativo'", [$curso_id]);
        $turma = $db->fetchOne("SELECT id, nome FROM turmas WHERE id = ? AND curso_id = ?", [$turma_id, $curso_id]);

        if (!$curso || !$turma) {
            setMensagem('erro', 'Curso ou turma não encontrados.');
            redirect('notas.php?action=lancar');
        }

        // Busca disciplinas do curso
        $disciplinas = $db->fetchAll("SELECT id, nome FROM disciplinas WHERE curso_id = ? ORDER BY nome ASC", [$curso_id]) ?: [];

        // Se não tiver disciplina selecionada, mostra seleção
        if (!$disciplina_id) {
            $view = 'selecionar_disciplina';
            break;
        }

        // Verifica se a disciplina existe
        $disciplina = $db->fetchOne("SELECT id, nome FROM disciplinas WHERE id = ? AND curso_id = ?", [$disciplina_id, $curso_id]);

        if (!$disciplina) {
            setMensagem('erro', 'Disciplina não encontrada.');
            redirect('notas.php?action=lancar&curso_id=' . $curso_id . '&turma_id=' . $turma_id);
        }

        // Busca alunos da turma com suas notas (se existirem)
        $sql = "SELECT a.id, a.nome, a.cpf, m.id as matricula_id,
                       nd.id as nota_id, nd.nota, nd.frequencia, nd.horas_aula,
                       nd.data_lancamento, nd.situacao, nd.observacoes
                FROM alunos a
                JOIN matriculas m ON a.id = m.aluno_id
                LEFT JOIN notas_disciplinas nd ON m.id = nd.matricula_id AND nd.disciplina_id = ?
                WHERE m.turma_id = ? AND m.status = 'ativo'
                ORDER BY a.nome ASC";

        $alunos = $db->fetchAll($sql, [$disciplina_id, $turma_id]) ?: [];

        $titulo_pagina = 'Lançar Notas - ' . $turma['nome'] . ' - ' . $disciplina['nome'];
        $view = 'lancar';
        break;

    case 'salvar_lancamento':
        // Salvar lançamento em lote
        if (!isPost()) {
            setMensagem('erro', 'Método não permitido.');
            redirect('notas.php');
        }

        $curso_id = $_POST['curso_id'] ?? null;
        $turma_id = $_POST['turma_id'] ?? null;
        $disciplina_id = $_POST['disciplina_id'] ?? null;
        $notas = $_POST['notas'] ?? [];

        if (!$curso_id || !$turma_id || !$disciplina_id) {
            setMensagem('erro', 'Parâmetros obrigatórios não informados.');
            redirect('notas.php?action=lancar');
        }

        try {
            $db->beginTransaction();

            $contador_salvos = 0;
            $data_lancamento = date('Y-m-d');

            foreach ($notas as $matricula_id => $dados) {

                // Validação robusta - aceita qualquer valor não vazio
                $tem_dados_relevantes = false;

                // Lista de campos para verificar
                $campos_para_verificar = ['nota', 'frequencia', 'horas_aula', 'observacoes'];

                // Verifica cada campo de forma detalhada
                foreach ($campos_para_verificar as $campo) {
                    $valor = $dados[$campo] ?? null;

                    // Condições permissivas
                    if (isset($dados[$campo])) {
                        $valor_limpo = is_string($valor) ? trim($valor) : $valor;

                        // Aceita qualquer valor que não seja vazio, null
                        if ($valor_limpo !== '' && $valor_limpo !== null) {
                            $tem_dados_relevantes = true;
                            break;
                        }

                        // Aceita também zero como valor válido para campos numéricos
                        if (in_array($campo, ['nota', 'frequencia', 'horas_aula']) &&
                            ($valor_limpo === '0' || $valor_limpo === 0)) {
                            $tem_dados_relevantes = true;
                            break;
                        }
                    }
                }

                if (!$tem_dados_relevantes) {
                    continue;
                }

                // Processamento dos valores com normalização
                $nota = null;
                $frequencia = null;
                $horas_aula = null;

                // Processa nota
                if (isset($dados['nota']) && trim($dados['nota']) !== '') {
                    $nota_normalizada = str_replace(',', '.', trim($dados['nota']));
                    $nota = floatval($nota_normalizada);
                }

                // Processa frequência
                if (isset($dados['frequencia']) && trim($dados['frequencia']) !== '') {
                    $freq_normalizada = str_replace(',', '.', trim($dados['frequencia']));
                    $frequencia = floatval($freq_normalizada);
                }

                // Processa horas-aula
                if (isset($dados['horas_aula']) && trim($dados['horas_aula']) !== '') {
                    $horas_aula = intval(trim($dados['horas_aula']));
                }
                $situacao = $dados['situacao'] ?? 'cursando';
                $observacoes = $dados['observacoes'] ?? '';

                if ($nota !== null && ($nota < 0 || $nota > 10)) {
                    continue; // Pula notas inválidas
                }

                if ($frequencia !== null && ($frequencia < 0 || $frequencia > 100)) {
                    continue; // Pula frequências inválidas
                }

                // Verifica se já existe nota para esta matrícula/disciplina
                $nota_existente = $db->fetchOne(
                    "SELECT id FROM notas_disciplinas WHERE matricula_id = ? AND disciplina_id = ?",
                    [$matricula_id, $disciplina_id]
                );

                $dados_nota = [
                    'matricula_id' => $matricula_id,
                    'disciplina_id' => $disciplina_id,
                    'nota' => $nota,
                    'frequencia' => $frequencia,
                    'horas_aula' => $horas_aula,
                    'data_lancamento' => $data_lancamento,
                    'situacao' => $situacao,
                    'observacoes' => $observacoes,
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                if ($nota_existente) {
                    // Atualiza nota existente
                    $db->update('notas_disciplinas', $dados_nota, 'id = ?', [$nota_existente['id']]);
                } else {
                    // Insere nova nota
                    $dados_nota['created_at'] = date('Y-m-d H:i:s');
                    $db->insert('notas_disciplinas', $dados_nota);
                }

                $contador_salvos++;
            }

            $db->commit();

            if ($contador_salvos > 0) {
                setMensagem('sucesso', "Notas lançadas com sucesso! {$contador_salvos} registro(s) salvos.");
            } else {
                setMensagem('aviso', 'Nenhuma nota foi lançada. Verifique se preencheu os campos corretamente.');
            }

            redirect('notas.php?action=lancar&curso_id=' . $curso_id . '&turma_id=' . $turma_id . '&disciplina_id=' . $disciplina_id);

        } catch (Exception $e) {
            $db->rollBack();
            setMensagem('erro', 'Erro ao salvar notas: ' . $e->getMessage());
            redirect('notas.php?action=lancar&curso_id=' . $curso_id . '&turma_id=' . $turma_id . '&disciplina_id=' . $disciplina_id);
        }
        break;

    case 'nova_disciplina':
        // Cadastrar nova disciplina durante o lançamento
        if (!isPost()) {
            setMensagem('erro', 'Método não permitido.');
            redirect('notas.php');
        }

        $curso_id = $_POST['curso_id'] ?? null;
        $turma_id = $_POST['turma_id'] ?? null;
        $nome = trim($_POST['nome'] ?? '');
        $codigo = trim($_POST['codigo'] ?? '');
        $carga_horaria = $_POST['carga_horaria'] ?? null;

        if (!$curso_id || !$turma_id || !$nome) {
            setMensagem('erro', 'Nome da disciplina é obrigatório.');
            redirect('notas.php?action=lancar&curso_id=' . $curso_id . '&turma_id=' . $turma_id);
        }

        try {
            $db->beginTransaction();

            // Verifica se já existe disciplina com mesmo nome no curso
            $disciplina_existente = $db->fetchOne(
                "SELECT id FROM disciplinas WHERE curso_id = ? AND nome = ?",
                [$curso_id, $nome]
            );

            if ($disciplina_existente) {
                setMensagem('erro', 'Já existe uma disciplina com este nome neste curso.');
                redirect('notas.php?action=lancar&curso_id=' . $curso_id . '&turma_id=' . $turma_id);
            }

            // Insere nova disciplina
            $dados_disciplina = [
                'curso_id' => $curso_id,
                'nome' => $nome,
                'codigo' => $codigo ?: null,
                'carga_horaria' => $carga_horaria ?: null,
                'status' => 'ativo',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $disciplina_id = $db->insert('disciplinas', $dados_disciplina);

            $db->commit();

            setMensagem('sucesso', 'Disciplina cadastrada com sucesso!');
            redirect('notas.php?action=lancar&curso_id=' . $curso_id . '&turma_id=' . $turma_id . '&disciplina_id=' . $disciplina_id);

        } catch (Exception $e) {
            $db->rollBack();
            setMensagem('erro', 'Erro ao cadastrar disciplina: ' . $e->getMessage());
            redirect('notas.php?action=lancar&curso_id=' . $curso_id . '&turma_id=' . $turma_id);
        }
        break;

    case 'editar':
        // Editar uma nota específica
        $id = $_GET['id'] ?? 0;

        if (!$id) {
            setMensagem('erro', 'ID da nota não informado.');
            redirect('notas.php');
        }

        // Busca a nota
        $sql = "SELECT nd.*, a.nome as aluno_nome, d.nome as disciplina_nome,
                       c.nome as curso_nome, t.nome as turma_nome,
                       m.id as matricula_id
                FROM notas_disciplinas nd
                JOIN matriculas m ON nd.matricula_id = m.id
                JOIN alunos a ON m.aluno_id = a.id
                JOIN disciplinas d ON nd.disciplina_id = d.id
                JOIN cursos c ON d.curso_id = c.id
                LEFT JOIN turmas t ON m.turma_id = t.id
                WHERE nd.id = ?";

        $nota = $db->fetchOne($sql, [$id]);

        if (!$nota) {
            setMensagem('erro', 'Nota não encontrada.');
            redirect('notas.php');
        }

        $titulo_pagina = 'Editar Nota - ' . $nota['aluno_nome'];
        $view = 'editar';
        break;

    case 'salvar':
        // Salvar nota (nova ou editada)
        if (!isPost()) {
            setMensagem('erro', 'Método não permitido.');
            redirect('notas.php');
        }

        $id = $_POST['id'] ?? null;
        $matricula_id = $_POST['matricula_id'] ?? null;
        $disciplina_id = $_POST['disciplina_id'] ?? null;
        $nota = $_POST['nota'] ?? null;
        $frequencia = $_POST['frequencia'] ?? null;
        $horas_aula = $_POST['horas_aula'] ?? null;
        $data_lancamento = $_POST['data_lancamento'] ?? date('Y-m-d');
        $situacao = $_POST['situacao'] ?? 'cursando';
        $observacoes = $_POST['observacoes'] ?? '';

        // Validações
        $erros = [];

        if (!$matricula_id) {
            $erros[] = 'Matrícula é obrigatória.';
        }

        if (!$disciplina_id) {
            $erros[] = 'Disciplina é obrigatória.';
        }

        if ($nota !== null && ($nota < 0 || $nota > 10)) {
            $erros[] = 'Nota deve estar entre 0 e 10.';
        }

        if ($frequencia !== null && ($frequencia < 0 || $frequencia > 100)) {
            $erros[] = 'Frequência deve estar entre 0 e 100%.';
        }

        if (!empty($erros)) {
            setMensagem('erro', implode('<br>', $erros));
            redirect('notas.php' . ($id ? '?action=editar&id=' . $id : ''));
        }

        try {
            $db->beginTransaction();

            $dados = [
                'matricula_id' => $matricula_id,
                'disciplina_id' => $disciplina_id,
                'nota' => $nota,
                'frequencia' => $frequencia,
                'horas_aula' => $horas_aula,
                'data_lancamento' => $data_lancamento,
                'situacao' => $situacao,
                'observacoes' => $observacoes,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            if ($id) {
                // Atualizar nota existente
                $db->update('notas_disciplinas', $dados, 'id = ?', [$id]);
                setMensagem('sucesso', 'Nota atualizada com sucesso.');
            } else {
                // Inserir nova nota
                $dados['created_at'] = date('Y-m-d H:i:s');
                $db->insert('notas_disciplinas', $dados);
                setMensagem('sucesso', 'Nota adicionada com sucesso.');
            }

            $db->commit();
            redirect('notas.php');

        } catch (Exception $e) {
            $db->rollBack();
            setMensagem('erro', 'Erro ao salvar nota: ' . $e->getMessage());
            redirect('notas.php' . ($id ? '?action=editar&id=' . $id : ''));
        }
        break;

    case 'excluir':
        // Excluir uma nota
        $id = $_GET['id'] ?? 0;

        if (!$id) {
            setMensagem('erro', 'ID da nota não informado.');
            redirect('notas.php');
        }

        try {
            $db->beginTransaction();

            // Verifica se a nota existe
            $nota = $db->fetchOne("SELECT id FROM notas_disciplinas WHERE id = ?", [$id]);

            if (!$nota) {
                setMensagem('erro', 'Nota não encontrada.');
                redirect('notas.php');
            }

            // Exclui a nota
            $db->delete('notas_disciplinas', 'id = ?', [$id]);

            $db->commit();
            setMensagem('sucesso', 'Nota excluída com sucesso.');

        } catch (Exception $e) {
            $db->rollBack();
            setMensagem('erro', 'Erro ao excluir nota: ' . $e->getMessage());
        }

        redirect('notas.php');
        break;

    case 'ajax_turmas':
        // AJAX: Buscar turmas por curso
        header('Content-Type: application/json');

        $curso_id = $_GET['curso_id'] ?? null;

        if (!$curso_id) {
            echo json_encode([]);
            exit;
        }

        $turmas = $db->fetchAll("SELECT id, nome FROM turmas WHERE curso_id = ? ORDER BY nome ASC", [$curso_id]) ?: [];
        echo json_encode($turmas);
        exit;

    case 'ajax_disciplinas':
        // AJAX: Buscar disciplinas por curso
        header('Content-Type: application/json');

        $curso_id = $_GET['curso_id'] ?? null;

        if (!$curso_id) {
            echo json_encode([]);
            exit;
        }

        $disciplinas = $db->fetchAll("SELECT id, nome FROM disciplinas WHERE curso_id = ? ORDER BY nome ASC", [$curso_id]) ?: [];
        echo json_encode($disciplinas);
        exit;

    default:
        // Ação padrão - redireciona para listagem
        redirect('notas.php?action=listar');
        break;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Notas - <?php echo $titulo_pagina ?? 'Notas'; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
                        <h1 class="text-2xl font-bold text-gray-800"><?php echo $titulo_pagina ?? 'Sistema de Notas'; ?></h1>
                    </div>

                    <?php if (isset($_SESSION['mensagem'])): ?>
                    <div class="bg-<?php echo $_SESSION['mensagem_tipo'] === 'sucesso' ? 'green' : ($_SESSION['mensagem_tipo'] === 'aviso' ? 'yellow' : 'red'); ?>-100 border-l-4 border-<?php echo $_SESSION['mensagem_tipo'] === 'sucesso' ? 'green' : ($_SESSION['mensagem_tipo'] === 'aviso' ? 'yellow' : 'red'); ?>-500 text-<?php echo $_SESSION['mensagem_tipo'] === 'sucesso' ? 'green' : ($_SESSION['mensagem_tipo'] === 'aviso' ? 'yellow' : 'red'); ?>-700 p-4 mb-6">
                        <?php echo $_SESSION['mensagem']; ?>
                    </div>
                    <?php
                    // Limpa a mensagem da sessão
                    unset($_SESSION['mensagem']);
                    unset($_SESSION['mensagem_tipo']);
                    endif;
                    ?>

                    <?php
                    // Incluir a view correspondente
                    if (isset($view)) {
                        $view_file = 'views/notas/' . $view . '.php';
                        if (file_exists($view_file)) {
                            include $view_file;
                        } else {
                            echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">View não encontrada: ' . htmlspecialchars($view) . '</div>';
                        }
                    }
                    ?>
                </div>
            </main>

            <!-- Footer -->
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script>
        // Função para carregar turmas via AJAX
        function carregarTurmas(cursoId, turmaSelectId) {
            const turmaSelect = document.getElementById(turmaSelectId);

            if (!turmaSelect) return;

            // Limpa as opções
            turmaSelect.innerHTML = '<option value="">Carregando...</option>';

            if (!cursoId) {
                turmaSelect.innerHTML = '<option value="">Selecione um curso primeiro</option>';
                return;
            }

            fetch(`notas.php?action=ajax_turmas&curso_id=${cursoId}`)
                .then(response => response.json())
                .then(data => {
                    turmaSelect.innerHTML = '<option value="">Selecione uma turma</option>';
                    data.forEach(turma => {
                        turmaSelect.innerHTML += `<option value="${turma.id}">${turma.nome}</option>`;
                    });
                })
                .catch(error => {
                    console.error('Erro ao carregar turmas:', error);
                    turmaSelect.innerHTML = '<option value="">Erro ao carregar turmas</option>';
                });
        }

        // Função para carregar disciplinas via AJAX
        function carregarDisciplinas(cursoId, disciplinaSelectId) {
            const disciplinaSelect = document.getElementById(disciplinaSelectId);

            if (!disciplinaSelect) return;

            // Limpa as opções
            disciplinaSelect.innerHTML = '<option value="">Carregando...</option>';

            if (!cursoId) {
                disciplinaSelect.innerHTML = '<option value="">Selecione um curso primeiro</option>';
                return;
            }

            fetch(`notas.php?action=ajax_disciplinas&curso_id=${cursoId}`)
                .then(response => response.json())
                .then(data => {
                    disciplinaSelect.innerHTML = '<option value="">Selecione uma disciplina</option>';
                    data.forEach(disciplina => {
                        disciplinaSelect.innerHTML += `<option value="${disciplina.id}">${disciplina.nome}</option>`;
                    });
                })
                .catch(error => {
                    console.error('Erro ao carregar disciplinas:', error);
                    disciplinaSelect.innerHTML = '<option value="">Erro ao carregar disciplinas</option>';
                });
        }
    </script>
</body>
</html>
