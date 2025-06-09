<?php
/**
 * API para manipulação de alunos
 */

// Inicializa o sistema
require_once __DIR__ . '/../includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica se o usuário tem permissão para acessar o módulo de alunos
// Para a ação 'search' usada no sistema de chamados, permitimos acesso se o usuário tem permissão para chamados
if (isset($_GET['action']) && $_GET['action'] === 'search') {
    exigirPermissao('chamados');
} else {
    exigirPermissao('alunos');
}

// Instancia o modelo de aluno
$alunoModel = new Aluno();

// Verifica o tipo de requisição
$action = $_GET['action'] ?? 'list';

// Processa a requisição de acordo com a ação
switch ($action) {
    case 'list':
        // Obtém os filtros da requisição
        $filtros = [
            'nome' => $_GET['nome'] ?? '',
            'cpf' => $_GET['cpf'] ?? '',
            'email' => $_GET['email'] ?? '',
            'status' => $_GET['status'] ?? '',
            'curso_id' => $_GET['curso_id'] ?? '',
            'polo_id' => $_GET['polo_id'] ?? '',
            'data_ingresso_inicio' => $_GET['data_ingresso_inicio'] ?? '',
            'data_ingresso_fim' => $_GET['data_ingresso_fim'] ?? ''
        ];

        // Obtém a página atual
        $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
        $itensPorPagina = getItemsPerPage();
        $offset = ($pagina - 1) * $itensPorPagina;

        // Obtém os alunos
        $alunos = $alunoModel->getAll($filtros, $itensPorPagina, $offset);

        // Obtém o total de alunos
        $totalAlunos = $alunoModel->count($filtros);

        // Calcula o total de páginas
        $totalPaginas = ceil($totalAlunos / $itensPorPagina);

        // Formata os dados para a resposta
        $response = [
            'success' => true,
            'data' => [
                'alunos' => $alunos,
                'total' => $totalAlunos,
                'pagina' => $pagina,
                'totalPaginas' => $totalPaginas,
                'itensPorPagina' => $itensPorPagina
            ]
        ];

        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;

    case 'get':
        // Verifica se o ID foi informado
        if (!isset($_GET['id']) && !isset($_GET['id_legado'])) {
            $response = [
                'success' => false,
                'message' => 'ID ou ID legado do aluno não informado'
            ];
        } else {
            $aluno = null;

            // Busca pelo ID normal
            if (isset($_GET['id'])) {
                $aluno = $alunoModel->getById($_GET['id']);
            }
            // Busca pelo ID legado
            else if (isset($_GET['id_legado'])) {
                $aluno = $alunoModel->getByLegacyId($_GET['id_legado']);
            }

            if ($aluno) {
                $response = [
                    'success' => true,
                    'data' => $aluno
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Aluno não encontrado'
                ];
            }
        }

        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;

    case 'create':
        // Verifica se o usuário tem permissão para criar alunos
        exigirPermissao('alunos', 'criar');

        // Verifica se é uma requisição POST
        if (!isPost()) {
            $response = [
                'success' => false,
                'message' => 'Método não permitido'
            ];
        } else {
            // Obtém os dados do formulário
            $data = [
                'nome' => $_POST['nome'] ?? '',
                'nome_social' => $_POST['nome_social'] ?? null,
                'cpf' => $_POST['cpf'] ?? null,
                'rg' => $_POST['rg'] ?? null,
                'data_nascimento' => $_POST['data_nascimento'] ?? null,
                'sexo' => $_POST['sexo'] ?? null,
                'naturalidade_id' => $_POST['naturalidade_id'] ?? null,
                'estado_civil_id' => $_POST['estado_civil_id'] ?? null,
                'email' => $_POST['email'] ?? null,
                'telefone' => $_POST['telefone'] ?? null,
                'celular' => $_POST['celular'] ?? null,
                'endereco' => $_POST['endereco'] ?? null,
                'numero' => $_POST['numero'] ?? null,
                'bairro' => $_POST['bairro'] ?? null,
                'cidade_id' => $_POST['cidade_id'] ?? null,
                'cep' => $_POST['cep'] ?? null,
                'polo_id' => $_POST['polo_id'] ?? null,
                'curso_id' => $_POST['curso_id'] ?? null,
                'data_ingresso' => $_POST['data_ingresso'] ?? null,
                'status' => $_POST['status'] ?? 'ativo',
                'created_at' => date('Y-m-d H:i:s')
            ];

            // Valida os dados
            $errors = [];

            if (empty($data['nome'])) {
                $errors[] = 'O nome é obrigatório';
            }

            if (!empty($data['cpf']) && !validarCpf($data['cpf'])) {
                $errors[] = 'CPF inválido';
            }

            if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'E-mail inválido';
            }

            // Se houver erros, retorna a resposta com os erros
            if (!empty($errors)) {
                $response = [
                    'success' => false,
                    'message' => 'Erros de validação',
                    'errors' => $errors
                ];
            } else {
                // Verifica se foi informado um ID legado
                $idLegado = $_POST['id_legado'] ?? null;

                // Cria o aluno
                $id = $alunoModel->create($data, $idLegado);

                if ($id) {
                    $response = [
                        'success' => true,
                        'message' => 'Aluno criado com sucesso',
                        'data' => [
                            'id' => $id,
                            'id_legado' => $idLegado
                        ]
                    ];
                } else {
                    $response = [
                        'success' => false,
                        'message' => 'Erro ao criar aluno'
                    ];
                }
            }
        }

        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;

    case 'update':
        // Verifica se o usuário tem permissão para editar alunos
        exigirPermissao('alunos', 'editar');

        // Verifica se é uma requisição POST
        if (!isPost()) {
            $response = [
                'success' => false,
                'message' => 'Método não permitido'
            ];
        } else {
            // Verifica se o ID foi informado
            if (!isset($_POST['id'])) {
                $response = [
                    'success' => false,
                    'message' => 'ID do aluno não informado'
                ];
            } else {
                // Obtém os dados do formulário
                $id = $_POST['id'];
                $data = [
                    'nome' => $_POST['nome'] ?? '',
                    'nome_social' => $_POST['nome_social'] ?? null,
                    'cpf' => $_POST['cpf'] ?? null,
                    'rg' => $_POST['rg'] ?? null,
                    'data_nascimento' => $_POST['data_nascimento'] ?? null,
                    'sexo' => $_POST['sexo'] ?? null,
                    'naturalidade_id' => $_POST['naturalidade_id'] ?? null,
                    'estado_civil_id' => $_POST['estado_civil_id'] ?? null,
                    'email' => $_POST['email'] ?? null,
                    'telefone' => $_POST['telefone'] ?? null,
                    'celular' => $_POST['celular'] ?? null,
                    'endereco' => $_POST['endereco'] ?? null,
                    'numero' => $_POST['numero'] ?? null,
                    'bairro' => $_POST['bairro'] ?? null,
                    'cidade_id' => $_POST['cidade_id'] ?? null,
                    'cep' => $_POST['cep'] ?? null,
                    'polo_id' => $_POST['polo_id'] ?? null,
                    'curso_id' => $_POST['curso_id'] ?? null,
                    'data_ingresso' => $_POST['data_ingresso'] ?? null,
                    'status' => $_POST['status'] ?? 'ativo',
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                // Valida os dados
                $errors = [];

                if (empty($data['nome'])) {
                    $errors[] = 'O nome é obrigatório';
                }

                if (!empty($data['cpf']) && !validarCpf($data['cpf'])) {
                    $errors[] = 'CPF inválido';
                }

                if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'E-mail inválido';
                }

                // Se houver erros, retorna a resposta com os erros
                if (!empty($errors)) {
                    $response = [
                        'success' => false,
                        'message' => 'Erros de validação',
                        'errors' => $errors
                    ];
                } else {
                    // Verifica se foi informado um ID legado
                    $idLegado = $_POST['id_legado'] ?? null;

                    // Atualiza o aluno
                    $result = $alunoModel->update($id, $data, $idLegado);

                    if ($result) {
                        $response = [
                            'success' => true,
                            'message' => 'Aluno atualizado com sucesso',
                            'data' => [
                                'id' => $id,
                                'id_legado' => $idLegado
                            ]
                        ];
                    } else {
                        $response = [
                            'success' => false,
                            'message' => 'Erro ao atualizar aluno'
                        ];
                    }
                }
            }
        }

        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;

    case 'delete':
        // Verifica se o usuário tem permissão para excluir alunos
        exigirPermissao('alunos', 'excluir');

        // Verifica se o ID foi informado
        if (!isset($_GET['id'])) {
            $response = [
                'success' => false,
                'message' => 'ID do aluno não informado'
            ];
        } else {
            // Exclui o aluno
            $result = $alunoModel->delete($_GET['id']);

            if ($result) {
                $response = [
                    'success' => true,
                    'message' => 'Aluno excluído com sucesso'
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Erro ao excluir aluno'
                ];
            }
        }

        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;

    case 'stats':
        // Obtém as estatísticas de alunos
        $stats = $alunoModel->getEstatisticas();

        $response = [
            'success' => true,
            'data' => $stats
        ];

        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;

    case 'search':
        // Busca alunos por termo de busca (para o sistema de chamados)
        if (!isset($_GET['termo']) || empty($_GET['termo'])) {
            $response = [
                'success' => false,
                'message' => 'Termo de busca não informado'
            ];
        } else {
            // Busca alunos pelo termo
            $polo_id = isset($_GET['polo_id']) ? (int)$_GET['polo_id'] : null;
            $termo = $_GET['termo'];

            // Instancia o banco de dados diretamente para usar a função de busca
            $db = Database::getInstance();

            // Consulta SQL para buscar alunos pelo termo
            if ($polo_id) {
                // Se o polo_id foi informado, filtra por polo
                $sql = "SELECT a.id, a.nome, a.matricula, a.cpf, a.email, t.nome as turma_nome, c.nome as curso_nome
                        FROM alunos a
                        LEFT JOIN turmas t ON a.turma_id = t.id
                        LEFT JOIN cursos c ON t.curso_id = c.id
                        WHERE a.polo_id = ? AND
                              (a.nome LIKE ? OR a.matricula LIKE ? OR a.cpf LIKE ? OR a.email LIKE ?)
                        ORDER BY a.nome
                        LIMIT 50";

                $termo = "%{$termo}%";
                $params = [$polo_id, $termo, $termo, $termo, $termo];
            } else {
                // Se o polo_id não foi informado, busca em todos os polos
                $sql = "SELECT a.id, a.nome, a.matricula, a.cpf, a.email, t.nome as turma_nome, c.nome as curso_nome, p.nome as polo_nome
                        FROM alunos a
                        LEFT JOIN turmas t ON a.turma_id = t.id
                        LEFT JOIN cursos c ON t.curso_id = c.id
                        LEFT JOIN polos p ON a.polo_id = p.id
                        WHERE a.nome LIKE ? OR a.matricula LIKE ? OR a.cpf LIKE ? OR a.email LIKE ?
                        ORDER BY a.nome
                        LIMIT 50";

                $termo = "%{$termo}%";
                $params = [$termo, $termo, $termo, $termo];
            }

            try {
                $alunos = $db->fetchAll($sql, $params);

                $response = [
                    'success' => true,
                    'data' => $alunos
                ];
            } catch (Exception $e) {
                // Log do erro para debug
                error_log("Erro na busca de alunos: " . $e->getMessage());

                $response = [
                    'success' => false,
                    'message' => 'Erro ao buscar alunos: ' . $e->getMessage()
                ];
            }
        }

        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;

    default:
        // Ação desconhecida
        $response = [
            'success' => false,
            'message' => 'Ação desconhecida'
        ];

        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;
}
