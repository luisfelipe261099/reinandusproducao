<?php
/**
 * API para manipulação de turmas
 */

// Inicializa o sistema
require_once __DIR__ . '/../includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica se o usuário tem permissão para acessar o módulo de turmas
// Para as ações 'list' e 'get' usadas no sistema de chamados, permitimos acesso se o usuário tem permissão para chamados
if (isset($_GET['action']) && ($_GET['action'] === 'list' || $_GET['action'] === 'get') && isset($_GET['polo_id'])) {
    exigirPermissao('chamados');
} else {
    exigirPermissao('turmas');
}

// Instancia o banco de dados
$db = Database::getInstance();

// Verifica o tipo de requisição
$action = $_GET['action'] ?? 'list';

// Processa a requisição de acordo com a ação
switch ($action) {
    case 'list':
        // Obtém os filtros da requisição
        $filtros = [];

        if (!empty($_GET['nome'])) {
            $filtros[] = "t.nome LIKE '%" . $db->getConnection()->quote($_GET['nome']) . "%'";
        }

        if (!empty($_GET['curso_id'])) {
            $filtros[] = "t.curso_id = " . (int)$_GET['curso_id'];
        }

        if (!empty($_GET['polo_id'])) {
            $filtros[] = "t.polo_id = " . (int)$_GET['polo_id'];
        }

        if (!empty($_GET['status'])) {
            $filtros[] = "t.status = '" . $db->getConnection()->quote($_GET['status']) . "'";
        }

        if (!empty($_GET['turno'])) {
            $filtros[] = "t.turno = '" . $db->getConnection()->quote($_GET['turno']) . "'";
        }

        // Monta a cláusula WHERE
        $whereClause = !empty($filtros) ? "WHERE " . implode(" AND ", $filtros) : "";

        // Consulta SQL
        $sql = "
            SELECT
                t.*,
                c.nome AS curso_nome,
                p.nome AS polo_nome,
                u.nome AS coordenador_nome
            FROM
                turmas t
                LEFT JOIN cursos c ON t.curso_id = c.id
                LEFT JOIN polos p ON t.polo_id = p.id
                LEFT JOIN usuarios u ON t.professor_coordenador_id = u.id
            {$whereClause}
            ORDER BY t.data_inicio DESC
        ";

        // Obtém as turmas
        $turmas = $db->fetchAll($sql);

        // Formata os dados para a resposta
        $response = [
            'success' => true,
            'data' => $turmas
        ];

        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;

    case 'get':
        // Verifica se o ID foi informado
        if (!isset($_GET['id'])) {
            $response = [
                'success' => false,
                'message' => 'ID da turma não informado'
            ];
        } else {
            // Obtém a turma pelo ID
            $sql = "
                SELECT
                    t.*,
                    c.nome AS curso_nome,
                    p.nome AS polo_nome,
                    u.nome AS coordenador_nome
                FROM
                    turmas t
                    LEFT JOIN cursos c ON t.curso_id = c.id
                    LEFT JOIN polos p ON t.polo_id = p.id
                    LEFT JOIN usuarios u ON t.professor_coordenador_id = u.id
                WHERE
                    t.id = ?
            ";

            $turma = $db->fetchOne($sql, [(int)$_GET['id']]);

            if ($turma) {
                $response = [
                    'success' => true,
                    'data' => $turma
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Turma não encontrada'
                ];
            }
        }

        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;

    case 'create':
        // Verifica se o usuário tem permissão para criar turmas
        exigirPermissao('turmas', 'criar');

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
                'curso_id' => $_POST['curso_id'] ?? '',
                'polo_id' => $_POST['polo_id'] ?? '',
                'professor_coordenador_id' => $_POST['professor_coordenador_id'] ?? null,
                'data_inicio' => $_POST['data_inicio'] ?? date('Y-m-d'),
                'data_fim' => $_POST['data_fim'] ?? null,
                'turno' => $_POST['turno'] ?? 'manha',
                'vagas_total' => $_POST['vagas_total'] ?? 0,
                'vagas_preenchidas' => 0,
                'status' => $_POST['status'] ?? 'planejada',
                'created_at' => date('Y-m-d H:i:s')
            ];

            // Valida os dados
            $errors = [];

            if (empty($data['nome'])) {
                $errors[] = 'O nome é obrigatório';
            }

            if (empty($data['curso_id'])) {
                $errors[] = 'O curso é obrigatório';
            }

            if (empty($data['polo_id'])) {
                $errors[] = 'O polo é obrigatório';
            }

            if (empty($data['data_inicio'])) {
                $errors[] = 'A data de início é obrigatória';
            }

            if (empty($data['vagas_total']) || $data['vagas_total'] <= 0) {
                $errors[] = 'O número de vagas deve ser maior que zero';
            }

            // Se houver erros, retorna a resposta com os erros
            if (!empty($errors)) {
                $response = [
                    'success' => false,
                    'message' => 'Erros de validação',
                    'errors' => $errors
                ];
            } else {
                // Cria a turma
                $id = $db->insert('turmas', $data);

                if ($id) {
                    // Registra o log
                    registrarLog(
                        'turmas',
                        'criar',
                        'Criação de nova turma: ' . $data['nome'],
                        $id,
                        'turma',
                        null,
                        $data
                    );

                    $response = [
                        'success' => true,
                        'message' => 'Turma criada com sucesso',
                        'data' => [
                            'id' => $id
                        ]
                    ];
                } else {
                    $response = [
                        'success' => false,
                        'message' => 'Erro ao criar turma'
                    ];
                }
            }
        }

        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;

    case 'update':
        // Verifica se o usuário tem permissão para editar turmas
        exigirPermissao('turmas', 'editar');

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
                    'message' => 'ID da turma não informado'
                ];
            } else {
                // Obtém os dados do formulário
                $id = (int)$_POST['id'];
                $data = [
                    'nome' => $_POST['nome'] ?? '',
                    'professor_coordenador_id' => $_POST['professor_coordenador_id'] ?? null,
                    'data_inicio' => $_POST['data_inicio'] ?? date('Y-m-d'),
                    'data_fim' => $_POST['data_fim'] ?? null,
                    'turno' => $_POST['turno'] ?? 'manha',
                    'vagas_total' => $_POST['vagas_total'] ?? 0,
                    'status' => $_POST['status'] ?? 'planejada',
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                // Valida os dados
                $errors = [];

                if (empty($data['nome'])) {
                    $errors[] = 'O nome é obrigatório';
                }

                if (empty($data['data_inicio'])) {
                    $errors[] = 'A data de início é obrigatória';
                }

                if (empty($data['vagas_total']) || $data['vagas_total'] <= 0) {
                    $errors[] = 'O número de vagas deve ser maior que zero';
                }

                // Se houver erros, retorna a resposta com os erros
                if (!empty($errors)) {
                    $response = [
                        'success' => false,
                        'message' => 'Erros de validação',
                        'errors' => $errors
                    ];
                } else {
                    // Obtém os dados antigos para o log
                    $turmaAntiga = $db->fetchOne("SELECT * FROM turmas WHERE id = ?", [$id]);

                    // Atualiza a turma
                    $result = $db->update('turmas', $data, 'id = ?', [$id]);

                    if ($result) {
                        // Registra o log
                        registrarLog(
                            'turmas',
                            'editar',
                            'Atualização de turma: ' . $data['nome'],
                            $id,
                            'turma',
                            $turmaAntiga,
                            $data
                        );

                        $response = [
                            'success' => true,
                            'message' => 'Turma atualizada com sucesso'
                        ];
                    } else {
                        $response = [
                            'success' => false,
                            'message' => 'Erro ao atualizar turma'
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
        // Verifica se o usuário tem permissão para excluir turmas
        exigirPermissao('turmas', 'excluir');

        // Verifica se o ID foi informado
        if (!isset($_GET['id'])) {
            $response = [
                'success' => false,
                'message' => 'ID da turma não informado'
            ];
        } else {
            // Obtém o ID da turma
            $id = (int)$_GET['id'];

            // Verifica se a turma tem alunos matriculados
            $matriculas = $db->fetchOne("SELECT COUNT(*) as total FROM matriculas WHERE turma_id = ?", [$id]);

            if ($matriculas['total'] > 0) {
                $response = [
                    'success' => false,
                    'message' => 'Não é possível excluir a turma porque existem alunos matriculados'
                ];
            } else {
                // Obtém os dados da turma para o log
                $turma = $db->fetchOne("SELECT * FROM turmas WHERE id = ?", [$id]);

                // Exclui a turma
                $result = $db->delete('turmas', 'id = ?', [$id]);

                if ($result) {
                    // Registra o log
                    registrarLog(
                        'turmas',
                        'excluir',
                        'Exclusão de turma: ' . $turma['nome'],
                        $id,
                        'turma',
                        $turma,
                        null
                    );

                    $response = [
                        'success' => true,
                        'message' => 'Turma excluída com sucesso'
                    ];
                } else {
                    $response = [
                        'success' => false,
                        'message' => 'Erro ao excluir turma'
                    ];
                }
            }
        }

        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;

    case 'alunos':
        // Verifica se o ID foi informado
        if (!isset($_GET['id'])) {
            $response = [
                'success' => false,
                'message' => 'ID da turma não informado'
            ];
        } else {
            // Obtém o ID da turma
            $id = (int)$_GET['id'];

            // Obtém os alunos da turma
            $sql = "
                SELECT
                    a.*,
                    m.id AS matricula_id,
                    m.data_matricula,
                    m.status AS matricula_status
                FROM
                    alunos a
                    JOIN matriculas m ON a.id = m.aluno_id
                WHERE
                    m.turma_id = ?
                ORDER BY
                    a.nome
            ";

            $alunos = $db->fetchAll($sql, [$id]);

            $response = [
                'success' => true,
                'data' => $alunos
            ];
        }

        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;

    case 'disciplinas':
        // Verifica se o ID foi informado
        if (!isset($_GET['id'])) {
            $response = [
                'success' => false,
                'message' => 'ID da turma não informado'
            ];
        } else {
            // Obtém o ID da turma
            $id = (int)$_GET['id'];

            // Obtém a turma para saber o curso
            $turma = $db->fetchOne("SELECT curso_id FROM turmas WHERE id = ?", [$id]);

            if (!$turma) {
                $response = [
                    'success' => false,
                    'message' => 'Turma não encontrada'
                ];
            } else {
                // Obtém as disciplinas do curso
                $sql = "
                    SELECT
                        d.*,
                        u.nome AS professor_nome
                    FROM
                        disciplinas d
                        LEFT JOIN usuarios u ON d.professor_padrao_id = u.id
                    WHERE
                        d.curso_id = ?
                    ORDER BY
                        d.nome
                ";

                $disciplinas = $db->fetchAll($sql, [$turma['curso_id']]);

                $response = [
                    'success' => true,
                    'data' => $disciplinas
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
