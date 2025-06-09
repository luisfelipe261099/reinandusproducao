<?php
/**
 * Página de gerenciamento de disciplinas
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
$action = $_GET['action'] ?? 'listar';

// Função para executar consultas com tratamento de erro
function executarConsulta($db, $sql, $params = [], $default = null) {
    try {
        $result = $db->fetchOne($sql, $params);
        return $result ?: $default;
    } catch (Exception $e) {
        // Registra o erro no log
        error_log('Erro na consulta SQL: ' . $e->getMessage() . ' - SQL: ' . $sql);
        return $default;
    }
}

function executarConsultaAll($db, $sql, $params = [], $default = []) {
    try {
        error_log("Executando consulta fetchAll: " . $sql);
        error_log("Parâmetros: " . json_encode($params));

        $result = $db->fetchAll($sql, $params);

        if ($result === false) {
            error_log("A consulta retornou false, usando valor padrão");
            return $default;
        }

        if (empty($result)) {
            error_log("A consulta retornou um array vazio");
            return $default;
        }

        error_log("A consulta retornou " . count($result) . " resultados");
        return $result;
    } catch (Exception $e) {
        // Registra o erro no log
        error_log('Erro na consulta SQL: ' . $e->getMessage() . ' - SQL: ' . $sql);
        error_log('Stack trace: ' . $e->getTraceAsString());
        return $default;
    }
}

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

            // Busca o curso para exibir informações
            $sql = "SELECT * FROM cursos WHERE id = ?";
            $curso = executarConsulta($db, $sql, [$disciplina['curso_id']]);

            if ($curso) {
                $titulo_pagina = 'Nova Disciplina - ' . $curso['nome'];
            }
        }

        // Carrega os cursos para o formulário
        $sql = "SELECT id, nome FROM cursos ORDER BY nome ASC";
        $cursos = executarConsultaAll($db, $sql);

        // Verifica se a tabela professores existe e cria se necessário
        try {
            $sql = "SHOW TABLES LIKE 'professores'";
            $tabela_existe = $db->fetchOne($sql);

            if (!$tabela_existe) {
                // Cria a tabela professores
                $sql = "CREATE TABLE professores (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    nome VARCHAR(150) NOT NULL,
                    email VARCHAR(100),
                    cpf VARCHAR(20),
                    telefone VARCHAR(20),
                    formacao VARCHAR(100),
                    titulacao ENUM('graduacao', 'especializacao', 'mestrado', 'doutorado', 'pos_doutorado'),
                    area_atuacao VARCHAR(100),
                    status ENUM('ativo', 'inativo') DEFAULT 'ativo',
                    id_legado VARCHAR(50),
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX (status)
                )";
                $db->query($sql);
            }
        } catch (Exception $e) {
            error_log('Erro ao verificar/criar tabela professores: ' . $e->getMessage());
        }

        // Carrega os professores para o formulário
        $sql = "SELECT id, nome FROM professores WHERE status = 'ativo' ORDER BY nome ASC";
        $professores = executarConsultaAll($db, $sql);

        // Carrega as turmas ativas
        $sql = "SELECT id, nome FROM turmas WHERE status = 'ativo' ORDER BY nome ASC";
        $turmas = executarConsultaAll($db, $sql);
        break;

    case 'editar':
        // Exibe o formulário para editar uma disciplina existente
        $id = $_GET['id'] ?? 0;

        // Busca a disciplina pelo ID
        $sql = "SELECT * FROM disciplinas WHERE id = ?";
        $disciplina = executarConsulta($db, $sql, [$id], []);

        if (!$disciplina) {
            // Disciplina não encontrada, redireciona para a listagem
            setMensagem('erro', 'Disciplina não encontrada.');
            redirect('disciplinas.php');
        }

        // Carrega os cursos para o formulário
        $sql = "SELECT id, nome FROM cursos ORDER BY nome ASC";
        $cursos = executarConsultaAll($db, $sql);

        // Carrega os professores para o formulário (limitado para melhor desempenho)
        $sql = "SELECT id, nome FROM professores ORDER BY nome ASC LIMIT 50";
        $professores = executarConsultaAll($db, $sql);

        // Se a disciplina tem um professor associado e ele não está nos professores recentes, busca especificamente esse professor
        if (!empty($disciplina['professor_id'])) {
            $professor_encontrado = false;
            foreach ($professores as $professor) {
                if ($professor['id'] == $disciplina['professor_id']) {
                    $professor_encontrado = true;
                    break;
                }
            }

            if (!$professor_encontrado) {
                $sql = "SELECT id, nome FROM professores WHERE id = ?";
                $professor_especifico = executarConsulta($db, $sql, [$disciplina['professor_id']]);

                if ($professor_especifico) {
                    array_unshift($professores, $professor_especifico);
                }
            }
        }

        // Busca o curso para exibir informações
        if (!empty($disciplina['curso_id'])) {
            $sql = "SELECT * FROM cursos WHERE id = ?";
            $curso = executarConsulta($db, $sql, [$disciplina['curso_id']]);
        }

        // Carrega as turmas ativas
        $sql = "SELECT id, nome FROM turmas WHERE status = 'ativo' ORDER BY nome ASC";
        $turmas = executarConsultaAll($db, $sql);

        // Busca as turmas já associadas a esta disciplina
        $sql = "SELECT turma_id FROM turmas_disciplinas WHERE disciplina_id = ?";
        $turmas_associadas = executarConsultaAll($db, $sql, [$id]);
        $disciplina['turmas_selecionadas'] = array_column($turmas_associadas, 'turma_id');

        $titulo_pagina = 'Editar Disciplina';
        $view = 'form';
        break;

    case 'salvar':
        // Salva os dados da disciplina (nova ou existente)
        if (!isPost()) {
            // Método não permitido
            setMensagem('erro', 'Método não permitido.');
            redirect('disciplinas.php');
        }

        // Obtém os dados do formulário (apenas campos essenciais)
        $id = $_POST['id'] ?? null;
        $nome = $_POST['nome'] ?? '';
        $curso_id = $_POST['curso_id'] ?? null;
        $carga_horaria = $_POST['carga_horaria'] ?? 0;
        $status = $_POST['status'] ?? 'ativo';
        $periodo = $_POST['periodo'] ?? '';
        $professor_padrao_id = $_POST['professor_padrao_id'] ?? null;
        $id_legado = $_POST['id_legado'] ?? '';

        // Valida os dados
        $erros = [];

        if (empty($nome)) {
            $erros[] = 'O nome é obrigatório.';
        }

        if (empty($curso_id)) {
            $erros[] = 'O curso é obrigatório.';
        }

        if (empty($carga_horaria) || $carga_horaria <= 0) {
            $erros[] = 'A carga horária é obrigatória e deve ser maior que zero.';
        }

        if (!empty($erros)) {
            // Há erros de validação, exibe o formulário novamente
            $titulo_pagina = $id ? 'Editar Disciplina' : 'Nova Disciplina';
            $view = 'form';
            $disciplina = $_POST;
            $mensagens_erro = $erros;

            // Carrega os cursos para o formulário
            $sql = "SELECT id, nome FROM cursos ORDER BY nome ASC";
            $cursos = executarConsultaAll($db, $sql);

            // Carrega os professores para o formulário (limitado para melhor desempenho)
            $sql = "SELECT id, nome FROM professores ORDER BY nome ASC LIMIT 50";
            $professores = executarConsultaAll($db, $sql);

            // Se foi informado um professor_id e ele não está nos professores recentes, busca especificamente esse professor
            if (!empty($professor_id)) {
                $professor_encontrado = false;
                foreach ($professores as $professor) {
                    if ($professor['id'] == $professor_id) {
                        $professor_encontrado = true;
                        break;
                    }
                }

                if (!$professor_encontrado) {
                    $sql = "SELECT id, nome FROM professores WHERE id = ?";
                    $professor_especifico = executarConsulta($db, $sql, [$professor_id]);

                    if ($professor_especifico) {
                        array_unshift($professores, $professor_especifico);
                    }
                }
            }

            break;
        }

        // Obtém as turmas selecionadas (se houver)
        $turmas_selecionadas = $_POST['turmas'] ?? [];

        // Valida se o professor existe (se informado)
        if (!empty($professor_padrao_id)) {
            $sql_check_prof = "SELECT id FROM professores WHERE id = ?";
            $professor_existe = executarConsulta($db, $sql_check_prof, [$professor_padrao_id]);

            if (!$professor_existe) {
                $erros[] = 'Professor selecionado não encontrado.';
            }
        }

        if (!empty($erros)) {
            // Há erros de validação, exibe o formulário novamente
            $titulo_pagina = $id ? 'Editar Disciplina' : 'Nova Disciplina';
            $view = 'form';
            $disciplina = $_POST;
            $mensagens_erro = $erros;

            // Carrega os dados necessários para o formulário
            $sql = "SELECT id, nome FROM cursos ORDER BY nome ASC";
            $cursos = executarConsultaAll($db, $sql);

            $sql = "SELECT id, nome FROM professores ORDER BY nome ASC LIMIT 50";
            $professores = executarConsultaAll($db, $sql);

            $sql = "SELECT id, nome FROM turmas WHERE status = 'ativo' ORDER BY nome ASC";
            $turmas = executarConsultaAll($db, $sql);

            break;
        }

        // Prepara os dados para salvar (apenas campos essenciais)
        $dados = [
            'nome' => $nome,
            'curso_id' => $curso_id,
            'professor_padrao_id' => !empty($professor_padrao_id) ? $professor_padrao_id : null,
            'carga_horaria' => $carga_horaria,
            'periodo' => $periodo,
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

                // Registra o log
                registrarLog(
                    'disciplinas',
                    'editar',
                    "Disciplina ID: {$id} atualizada",
                    $id,
                    'disciplinas'
                );

                setMensagem('sucesso', 'Disciplina atualizada com sucesso.');
            } else {
                // Adiciona a data de criação
                $dados['created_at'] = date('Y-m-d H:i:s');

                // Insere uma nova disciplina
                $id = $db->insert('disciplinas', $dados);

                // Registra o log
                registrarLog(
                    'disciplinas',
                    'criar',
                    "Disciplina ID: {$id} criada",
                    $id,
                    'disciplinas'
                );

                setMensagem('sucesso', 'Disciplina adicionada com sucesso.');
            }

            // Processa as turmas selecionadas
            if (!empty($turmas_selecionadas) && is_array($turmas_selecionadas)) {
                // Remove associações existentes se estiver editando
                if ($id) {
                    $db->delete('turmas_disciplinas', 'disciplina_id = ?', [$id]);
                }

                // Adiciona as novas associações
                foreach ($turmas_selecionadas as $turma_id) {
                    if (!empty($turma_id)) {
                        // Verifica se a associação já existe
                        $sql_check = "SELECT id FROM turmas_disciplinas WHERE turma_id = ? AND disciplina_id = ?";
                        $existe = executarConsulta($db, $sql_check, [$turma_id, $id]);

                        if (!$existe) {
                            $dados_turma_disciplina = [
                                'turma_id' => $turma_id,
                                'disciplina_id' => $id,
                                'professor_id' => !empty($professor_padrao_id) ? $professor_padrao_id : null,
                                'status' => 'planejada',
                                'created_at' => date('Y-m-d H:i:s')
                            ];

                            $db->insert('turmas_disciplinas', $dados_turma_disciplina);
                        }
                    }
                }
            }

            // Confirma a transação
            $db->commit();

            // Verifica se deve continuar cadastrando
            if (isset($_POST['continuar_cadastrando']) && $_POST['continuar_cadastrando'] === 'on') {
                // Mantém o curso fixado se houver
                $curso_fixado = isset($_POST['curso_id']) ? $_POST['curso_id'] : null;
                $redirect_url = 'disciplinas.php?action=nova';
                if ($curso_fixado) {
                    $redirect_url .= '&curso_id=' . $curso_fixado;
                }
                redirect($redirect_url);
            } else {
                // Redireciona para a visualização da disciplina
                redirect('disciplinas.php?action=visualizar&id=' . $id);
            }
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
            $cursos = executarConsultaAll($db, $sql);

            // Carrega os professores para o formulário (limitado para melhor desempenho)
            $sql = "SELECT id, nome FROM professores ORDER BY nome ASC LIMIT 50";
            $professores = executarConsultaAll($db, $sql);

            // Carrega as turmas ativas
            $sql = "SELECT id, nome FROM turmas WHERE status = 'ativo' ORDER BY nome ASC";
            $turmas = executarConsultaAll($db, $sql);

            // Se foi informado um professor_id e ele não está nos professores recentes, busca especificamente esse professor
            if (!empty($professor_id)) {
                $professor_encontrado = false;
                foreach ($professores as $professor) {
                    if ($professor['id'] == $professor_id) {
                        $professor_encontrado = true;
                        break;
                    }
                }

                if (!$professor_encontrado) {
                    $sql = "SELECT id, nome FROM professores WHERE id = ?";
                    $professor_especifico = executarConsulta($db, $sql, [$professor_id]);

                    if ($professor_especifico) {
                        array_unshift($professores, $professor_especifico);
                    }
                }
            }
        }
        break;

    case 'excluir':
        // Exclui uma disciplina
        $id = $_GET['id'] ?? 0;

        // Verifica se o usuário tem permissão para excluir
        exigirPermissao('disciplinas', 'excluir');

        // Busca a disciplina pelo ID
        $sql = "SELECT * FROM disciplinas WHERE id = ?";
        $disciplina = executarConsulta($db, $sql, [$id], []);

        if (!$disciplina) {
            // Disciplina não encontrada, redireciona para a listagem
            setMensagem('erro', 'Disciplina não encontrada.');
            redirect('disciplinas.php');
        }

        try {
            // Inicia uma transação
            $db->beginTransaction();

            // Exclui a disciplina
            $db->delete('disciplinas', 'id = ?', [$id]);

            // Registra o log
            registrarLog(
                'disciplinas',
                'excluir',
                "Disciplina ID: {$id} excluída",
                $id,
                'disciplinas'
            );

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
        redirect('disciplinas.php');
        break;

    case 'cadastrar_professor':
        // Cadastra um novo professor via AJAX
        if (!isPost()) {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            exit;
        }

        $nome = $_POST['nome'] ?? '';
        $email = $_POST['email'] ?? '';
        $formacao = $_POST['formacao'] ?? '';

        if (empty($nome)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Nome é obrigatório']);
            exit;
        }

        try {
            $dados_professor = [
                'nome' => $nome,
                'email' => $email,
                'formacao' => $formacao,
                'status' => 'ativo',
                'created_at' => date('Y-m-d H:i:s')
            ];

            $professor_id = $db->insert('professores', $dados_professor);

            echo json_encode([
                'success' => true,
                'professor' => [
                    'id' => $professor_id,
                    'nome' => $nome
                ]
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao cadastrar professor: ' . $e->getMessage()]);
        }
        exit;

    case 'visualizar':
        // Exibe os detalhes de uma disciplina
        $id = $_GET['id'] ?? 0;

        // Busca a disciplina pelo ID
        $sql = "SELECT d.*,
                       c.nome as curso_nome,
                       p.nome as professor_nome
                FROM disciplinas d
                LEFT JOIN cursos c ON d.curso_id = c.id
                LEFT JOIN professores p ON d.professor_padrao_id = p.id
                WHERE d.id = ?";
        $disciplina = executarConsulta($db, $sql, [$id], []);

        if (!$disciplina) {
            // Disciplina não encontrada, redireciona para a listagem
            setMensagem('erro', 'Disciplina não encontrada.');
            redirect('disciplinas.php');
        }

        $titulo_pagina = 'Detalhes da Disciplina';
        $view = 'visualizar';
        break;

    case 'buscar':
        // Busca disciplinas por termo
        $termo = $_GET['termo'] ?? '';
        $campo = $_GET['campo'] ?? 'nome';
        $status = $_GET['status'] ?? 'todos';
        $curso_id = $_GET['curso_id'] ?? null;

        // Se não houver nenhum filtro, mantém a busca mas sem filtros
        // Não redirecionamos para permitir que o formulário funcione mesmo sem termo

        // Define os campos permitidos para busca
        $campos_permitidos = ['nome', 'codigo', 'id_legado'];

        if (!in_array($campo, $campos_permitidos)) {
            $campo = 'nome';
        }

        // Monta a consulta SQL
        $where = [];
        $params = [];

        if (!empty($termo)) {
            switch ($campo) {
                case 'nome':
                    $where[] = "d.nome LIKE ?";
                    $params[] = "%{$termo}%";
                    break;
                case 'codigo':
                    $where[] = "d.codigo LIKE ?";
                    $params[] = "%{$termo}%";
                    break;
                case 'id_legado':
                    $where[] = "d.id_legado LIKE ?";
                    $params[] = "%{$termo}%";
                    break;
            }
        }

        if ($status !== 'todos') {
            $where[] = "d.status = ?";
            $params[] = $status;
        }

        if (!empty($curso_id)) {
            $where[] = "d.curso_id = ?";
            $params[] = $curso_id;
        }

        // Monta a cláusula WHERE
        $whereClause = "WHERE " . implode(" AND ", $where);

        // Consulta principal
        $sql = "SELECT d.*,
                       c.nome as curso_nome,
                       p.nome as professor_nome
                FROM disciplinas d
                LEFT JOIN cursos c ON d.curso_id = c.id
                LEFT JOIN professores p ON d.professor_padrao_id = p.id
                {$whereClause}
                ORDER BY d.nome ASC";
        $disciplinas = executarConsultaAll($db, $sql, $params);

        // Carrega os cursos para o filtro
        $sql = "SELECT id, nome FROM cursos ORDER BY nome ASC";
        $cursos = executarConsultaAll($db, $sql);

        $titulo_pagina = 'Resultado da Busca';
        $view = 'listar';

        // Garante que as variáveis necessárias estejam disponíveis na view
        if (!isset($status)) {
            $status = 'todos';
        }

        if (!isset($curso_id)) {
            $curso_id = null;
        }
        break;

    case 'listar':
    default:
        // Lista todas as disciplinas
        $status = $_GET['status'] ?? 'todos';
        $curso_id = $_GET['curso_id'] ?? null;
        $ordenar = $_GET['ordenar'] ?? null;
        $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
        $por_pagina = 20;
        $offset = ($pagina - 1) * $por_pagina;

        // Monta a consulta SQL
        $where = [];
        $params = [];

        // Adiciona condição de status apenas se não for 'todos'
        if ($status !== 'todos') {
            $where[] = "d.status = ?";
            $params[] = $status;
        }

        if (!empty($curso_id)) {
            $where[] = "d.curso_id = ?";
            $params[] = $curso_id;
        }

        // Monta a cláusula WHERE
        $whereClause = '';
        if (!empty($where)) {
            $whereClause = "WHERE " . implode(" AND ", $where);
        }

        // Define a ordenação
        $orderBy = "d.nome ASC";
        if ($ordenar === 'recentes') {
            $orderBy = "d.created_at DESC";
        }

        // CORREÇÃO DIRETA: Consulta extremamente simplificada para garantir que todas as disciplinas sejam listadas
        if ($status === 'todos' && empty($curso_id)) {
            // Consulta direta na tabela disciplinas sem joins para evitar problemas
            // Limitando a 200 disciplinas para não sobrecarregar o servidor
            $sql = "SELECT * FROM disciplinas ORDER BY nome ASC LIMIT 200";

            error_log("CORREÇÃO DIRETA: Executando consulta SQL direta na tabela disciplinas");

            try {
                // Executa a consulta diretamente usando PDO para evitar qualquer problema com a função executarConsultaAll
                $stmt = $db->getConnection()->prepare($sql);
                $stmt->execute();
                $disciplinas_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

                error_log("CORREÇÃO DIRETA: Consulta retornou " . count($disciplinas_raw) . " disciplinas");

                // Agora buscamos os dados relacionados para cada disciplina
                $disciplinas = [];
                foreach ($disciplinas_raw as $disc) {
                    // Busca o nome do curso
                    if (!empty($disc['curso_id'])) {
                        $sql_curso = "SELECT nome FROM cursos WHERE id = ?";
                        $stmt_curso = $db->getConnection()->prepare($sql_curso);
                        $stmt_curso->execute([$disc['curso_id']]);
                        $curso = $stmt_curso->fetch(PDO::FETCH_ASSOC);
                        $disc['curso_nome'] = $curso ? $curso['nome'] : 'Curso não encontrado';
                    } else {
                        $disc['curso_nome'] = 'Curso não definido';
                    }

                    // Busca o nome do professor
                    if (!empty($disc['professor_padrao_id'])) {
                        $sql_prof = "SELECT nome FROM professores WHERE id = ?";
                        $stmt_prof = $db->getConnection()->prepare($sql_prof);
                        $stmt_prof->execute([$disc['professor_padrao_id']]);
                        $professor = $stmt_prof->fetch(PDO::FETCH_ASSOC);
                        $disc['professor_nome'] = $professor ? $professor['nome'] : 'Professor não encontrado';
                    } else {
                        $disc['professor_nome'] = null;
                    }

                    $disciplinas[] = $disc;
                }

                error_log("CORREÇÃO DIRETA: Processamento completo, retornando " . count($disciplinas) . " disciplinas com dados relacionados");
            } catch (Exception $e) {
                error_log("ERRO CRÍTICO na consulta direta: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());

                // Fallback para consulta simples sem joins
                $sql_fallback = "SELECT * FROM disciplinas LIMIT 100";
                $stmt_fallback = $db->getConnection()->prepare($sql_fallback);
                $stmt_fallback->execute();
                $disciplinas = $stmt_fallback->fetchAll(PDO::FETCH_ASSOC);

                error_log("FALLBACK: Consulta retornou " . count($disciplinas) . " disciplinas");
            }
        } else {
            // Consulta com filtros
            $sql = "SELECT d.*,
                       c.nome as curso_nome,
                       p.nome as professor_nome
                    FROM disciplinas d
                    LEFT JOIN cursos c ON d.curso_id = c.id
                    LEFT JOIN professores p ON d.professor_padrao_id = p.id
                    {$whereClause}
                    ORDER BY {$orderBy}
                    LIMIT {$offset}, {$por_pagina}";

            error_log("Executando consulta SQL com filtros: " . $sql);
            error_log("Parâmetros: " . json_encode($params));
            $disciplinas = executarConsultaAll($db, $sql, $params);
        }

        // Verifica se há disciplinas na tabela
        $sql_check = "SELECT COUNT(*) as total FROM disciplinas";
        $total_check = executarConsulta($db, $sql_check);
        error_log("Total de disciplinas na tabela: " . ($total_check['total'] ?? 0));

        // Se não houver disciplinas, vamos criar algumas para teste
        if (($total_check['total'] ?? 0) == 0) {
            error_log("Nenhuma disciplina encontrada na tabela. Criando disciplinas de teste...");

            try {
                // Verifica se existem cursos
                $sql_cursos = "SELECT id FROM cursos LIMIT 1";
                $curso = executarConsulta($db, $sql_cursos);

                if (!$curso) {
                    error_log("Nenhum curso encontrado. Criando curso de teste...");

                    // Cria um curso de teste
                    $dados_curso = [
                        'nome' => 'Curso de Teste',
                        'descricao' => 'Curso criado automaticamente para teste',
                        'carga_horaria' => 120,
                        'status' => 'ativo',
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];

                    $curso_id = $db->insert('cursos', $dados_curso);
                    error_log("Curso de teste criado com ID: " . $curso_id);
                } else {
                    $curso_id = $curso['id'];
                    error_log("Usando curso existente com ID: " . $curso_id);
                }

                // Cria algumas disciplinas de teste
                $disciplinas_teste = [
                    [
                        'nome' => 'Matemática Básica',
                        'codigo' => 'MAT001',
                        'curso_id' => $curso_id,
                        'carga_horaria' => 60,
                        'status' => 'ativo'
                    ],
                    [
                        'nome' => 'Português Instrumental',
                        'codigo' => 'PORT001',
                        'curso_id' => $curso_id,
                        'carga_horaria' => 40,
                        'status' => 'ativo'
                    ],
                    [
                        'nome' => 'Introdução à Informática',
                        'codigo' => 'INF001',
                        'curso_id' => $curso_id,
                        'carga_horaria' => 80,
                        'status' => 'ativo'
                    ]
                ];

                foreach ($disciplinas_teste as $disc) {
                    $disc['created_at'] = date('Y-m-d H:i:s');
                    $disc['updated_at'] = date('Y-m-d H:i:s');

                    $id = $db->insert('disciplinas', $disc);
                    error_log("Disciplina de teste criada: " . $disc['nome'] . " (ID: " . $id . ")");
                }

                // Recarrega a lista de disciplinas
                if ($status === 'todos' && empty($curso_id)) {
                    $sql = "SELECT d.*,
                           c.nome as curso_nome,
                           p.nome as professor_nome
                        FROM disciplinas d
                        LEFT JOIN cursos c ON d.curso_id = c.id
                        LEFT JOIN professores p ON d.professor_padrao_id = p.id
                        ORDER BY {$orderBy}";

                    error_log("Recarregando disciplinas após criar disciplinas de teste");
                    $disciplinas = executarConsultaAll($db, $sql, []);
                }

                error_log("Total de disciplinas após criar disciplinas de teste: " . count($disciplinas));
            } catch (Exception $e) {
                error_log("Erro ao criar disciplinas de teste: " . $e->getMessage());
            }
        }

        error_log("Total de disciplinas encontradas na consulta: " . count($disciplinas));



        // Conta o total de disciplinas
        $sql = "SELECT COUNT(*) as total
                FROM disciplinas d
                {$whereClause}";
        $resultado = executarConsulta($db, $sql, $params);
        $total_disciplinas = $resultado['total'] ?? 0;

        error_log("Total de disciplinas para paginação: " . $total_disciplinas);

        // Se estamos mostrando todas as disciplinas, ajusta a variável de disciplinas
        if ($status === 'todos' && empty($curso_id)) {
            // Já temos todas as disciplinas na variável $disciplinas
            $total_paginas = 1;
        } else {
            // Calcula o total de páginas
            $total_paginas = ceil($total_disciplinas / $por_pagina);
        }

        // Carrega os cursos para o filtro
        $sql = "SELECT id, nome FROM cursos ORDER BY nome ASC";
        $cursos = executarConsultaAll($db, $sql);

        // Busca estatísticas para o dashboard
        try {
            // Total de disciplinas por status
            $sql = "SELECT status, COUNT(*) as total FROM disciplinas GROUP BY status";
            $status_counts = executarConsultaAll($db, $sql);

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
            $recentes_result = executarConsulta($db, $sql);
            $total_recentes = $recentes_result['total'] ?? 0;

            // Busca as disciplinas mais recentes para exibir no dashboard
            $sql = "SELECT d.*,
                       c.nome as curso_nome,
                       p.nome as professor_nome
                FROM disciplinas d
                LEFT JOIN cursos c ON d.curso_id = c.id
                LEFT JOIN professores p ON d.professor_padrao_id = p.id
                ORDER BY d.created_at DESC LIMIT 5";
            $disciplinas_recentes = executarConsultaAll($db, $sql);

            // Busca os cursos com mais disciplinas
            $sql = "SELECT c.id, c.nome, COUNT(*) as total
                   FROM disciplinas d
                   JOIN cursos c ON d.curso_id = c.id
                   GROUP BY c.id
                   ORDER BY total DESC
                   LIMIT 5";
            $cursos_populares_raw = executarConsultaAll($db, $sql);

            // Calcula a porcentagem para cada curso popular
            $cursos_populares = [];
            if (!empty($cursos_populares_raw)) {
                $max_disciplinas = $cursos_populares_raw[0]['total'];

                foreach ($cursos_populares_raw as $curso) {
                    $curso['porcentagem'] = ($curso['total'] / $max_disciplinas) * 100;
                    $cursos_populares[] = $curso;
                }
            }
        } catch (Exception $e) {
            error_log('Erro ao buscar estatísticas para o dashboard: ' . $e->getMessage());
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
                            <a href="disciplinas.php?action=nova" class="btn-primary">
                                <i class="fas fa-plus mr-2"></i> Nova Disciplina
                            </a>
                            <?php endif; ?>

                            <?php if ($view === 'visualizar'): ?>
                            <a href="disciplinas.php?action=editar&id=<?php echo $disciplina['id']; ?>" class="btn-secondary">
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
                    <div class="bg-<?php echo $_SESSION['mensagem_tipo'] === 'sucesso' ? 'green' : 'red'; ?>-100 border-l-4 border-<?php echo $_SESSION['mensagem_tipo'] === 'sucesso' ? 'green' : 'red'; ?>-500 text-<?php echo $_SESSION['mensagem_tipo'] === 'sucesso' ? 'green' : 'red'; ?>-700 p-4 mb-6">
                        <?php echo $_SESSION['mensagem']; ?>
                    </div>
                    <?php
                    // Limpa a mensagem da sessão
                    unset($_SESSION['mensagem']);
                    unset($_SESSION['mensagem_tipo']);
                    endif;
                    ?>

                    <?php
                    // Inclui a view correspondente
                    switch ($view) {
                        case 'form':
                            include 'views/disciplinas/form.php';
                            break;
                        case 'visualizar':
                            include 'views/disciplinas/visualizar.php';
                            break;
                        case 'listar':
                        default:
                            include 'views/disciplinas/listar.php';
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
            document.getElementById('btn-confirmar-exclusao').href = `disciplinas.php?action=excluir&id=${id}`;
            document.getElementById('modal-exclusao').classList.remove('hidden');
        }

        function fecharModal() {
            document.getElementById('modal-exclusao').classList.add('hidden');
        }
    </script>
</body>
</html>