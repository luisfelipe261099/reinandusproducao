<?php
/**
 * API para manipulação de disciplinas
 */

// Inicializa o sistema
require_once __DIR__ . '/../includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica se o usuário tem permissão para acessar o módulo de disciplinas
exigirPermissao('disciplinas');

// Instancia o modelo de disciplina
$disciplinaModel = new Disciplina();

// Verifica o tipo de requisição
$action = $_GET['action'] ?? 'list';

// Processa a requisição de acordo com a ação
switch ($action) {
    case 'list':
        // Obtém os filtros da requisição
        $filtros = [
            'nome' => $_GET['nome'] ?? '',
            'codigo' => $_GET['codigo'] ?? '',
            'curso_id' => $_GET['curso_id'] ?? '',
            'status' => $_GET['status'] ?? ''
        ];
        
        // Obtém as disciplinas
        $disciplinas = $disciplinaModel->getAll($filtros);
        
        // Formata os dados para a resposta
        $response = [
            'success' => true,
            'data' => $disciplinas
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
                'message' => 'ID da disciplina não informado'
            ];
        } else {
            // Obtém a disciplina pelo ID
            $disciplina = $disciplinaModel->getById($_GET['id']);
            
            if ($disciplina) {
                $response = [
                    'success' => true,
                    'data' => $disciplina
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Disciplina não encontrada'
                ];
            }
        }
        
        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;
        
    case 'create':
        // Verifica se o usuário tem permissão para criar disciplinas
        exigirPermissao('disciplinas', 'criar');
        
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
                'codigo' => $_POST['codigo'] ?? '',
                'curso_id' => $_POST['curso_id'] ?? '',
                'professor_padrao_id' => $_POST['professor_padrao_id'] ?? null,
                'carga_horaria' => $_POST['carga_horaria'] ?? 0,
                'ementa' => $_POST['ementa'] ?? null,
                'bibliografia' => $_POST['bibliografia'] ?? null,
                'status' => $_POST['status'] ?? 'ativo',
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
            
            if (empty($data['carga_horaria']) || $data['carga_horaria'] <= 0) {
                $errors[] = 'A carga horária deve ser maior que zero';
            }
            
            // Se houver erros, retorna a resposta com os erros
            if (!empty($errors)) {
                $response = [
                    'success' => false,
                    'message' => 'Erros de validação',
                    'errors' => $errors
                ];
            } else {
                // Cria a disciplina
                $id = $disciplinaModel->create($data);
                
                if ($id) {
                    $response = [
                        'success' => true,
                        'message' => 'Disciplina criada com sucesso',
                        'data' => [
                            'id' => $id
                        ]
                    ];
                } else {
                    $response = [
                        'success' => false,
                        'message' => 'Erro ao criar disciplina'
                    ];
                }
            }
        }
        
        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;
        
    case 'update':
        // Verifica se o usuário tem permissão para editar disciplinas
        exigirPermissao('disciplinas', 'editar');
        
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
                    'message' => 'ID da disciplina não informado'
                ];
            } else {
                // Obtém os dados do formulário
                $id = $_POST['id'];
                $data = [
                    'nome' => $_POST['nome'] ?? '',
                    'codigo' => $_POST['codigo'] ?? '',
                    'professor_padrao_id' => $_POST['professor_padrao_id'] ?? null,
                    'carga_horaria' => $_POST['carga_horaria'] ?? 0,
                    'ementa' => $_POST['ementa'] ?? null,
                    'bibliografia' => $_POST['bibliografia'] ?? null,
                    'status' => $_POST['status'] ?? 'ativo',
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                // Valida os dados
                $errors = [];
                
                if (empty($data['nome'])) {
                    $errors[] = 'O nome é obrigatório';
                }
                
                if (empty($data['carga_horaria']) || $data['carga_horaria'] <= 0) {
                    $errors[] = 'A carga horária deve ser maior que zero';
                }
                
                // Se houver erros, retorna a resposta com os erros
                if (!empty($errors)) {
                    $response = [
                        'success' => false,
                        'message' => 'Erros de validação',
                        'errors' => $errors
                    ];
                } else {
                    // Atualiza a disciplina
                    $result = $disciplinaModel->update($id, $data);
                    
                    if ($result) {
                        $response = [
                            'success' => true,
                            'message' => 'Disciplina atualizada com sucesso'
                        ];
                    } else {
                        $response = [
                            'success' => false,
                            'message' => 'Erro ao atualizar disciplina'
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
        // Verifica se o usuário tem permissão para excluir disciplinas
        exigirPermissao('disciplinas', 'excluir');
        
        // Verifica se o ID foi informado
        if (!isset($_GET['id'])) {
            $response = [
                'success' => false,
                'message' => 'ID da disciplina não informado'
            ];
        } else {
            // Exclui a disciplina
            $result = $disciplinaModel->delete($_GET['id']);
            
            if ($result) {
                $response = [
                    'success' => true,
                    'message' => 'Disciplina excluída com sucesso'
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Erro ao excluir disciplina'
                ];
            }
        }
        
        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;
        
    case 'notas':
        // Verifica se o ID foi informado
        if (!isset($_GET['id'])) {
            $response = [
                'success' => false,
                'message' => 'ID da disciplina não informado'
            ];
        } else {
            // Obtém as notas da disciplina
            $turmaId = $_GET['turma_id'] ?? null;
            $notas = $disciplinaModel->getNotas($_GET['id'], $turmaId);
            
            $response = [
                'success' => true,
                'data' => $notas
            ];
        }
        
        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;
        
    case 'atualizar_nota':
        // Verifica se o usuário tem permissão para editar notas
        exigirPermissao('notas', 'editar');
        
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
                    'message' => 'ID da nota não informado'
                ];
            } else {
                // Obtém os dados do formulário
                $id = $_POST['id'];
                $data = [
                    'nota' => $_POST['nota'] ?? null,
                    'frequencia' => $_POST['frequencia'] ?? null,
                    'situacao' => $_POST['situacao'] ?? 'cursando',
                    'observacoes' => $_POST['observacoes'] ?? null,
                    'data_lancamento' => date('Y-m-d'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                // Atualiza a nota
                $result = $disciplinaModel->atualizarNota($id, $data);
                
                if ($result) {
                    $response = [
                        'success' => true,
                        'message' => 'Nota atualizada com sucesso'
                    ];
                } else {
                    $response = [
                        'success' => false,
                        'message' => 'Erro ao atualizar nota'
                    ];
                }
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
