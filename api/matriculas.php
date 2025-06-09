<?php
/**
 * API para manipulação de matrículas
 */

// Inicializa o sistema
require_once __DIR__ . '/../includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica se o usuário tem permissão para acessar o módulo de matrículas
exigirPermissao('matriculas');

// Instancia o modelo de matrícula
$matriculaModel = new Matricula();

// Verifica o tipo de requisição
$action = $_GET['action'] ?? 'list';

// Processa a requisição de acordo com a ação
switch ($action) {
    case 'list':
        // Obtém os filtros da requisição
        $filtros = [
            'aluno_id' => $_GET['aluno_id'] ?? '',
            'curso_id' => $_GET['curso_id'] ?? '',
            'polo_id' => $_GET['polo_id'] ?? '',
            'turma_id' => $_GET['turma_id'] ?? '',
            'status' => $_GET['status'] ?? '',
            'data_inicio' => $_GET['data_inicio'] ?? '',
            'data_fim' => $_GET['data_fim'] ?? ''
        ];
        
        // Obtém as matrículas
        $matriculas = $matriculaModel->getAll($filtros);
        
        // Formata os dados para a resposta
        $response = [
            'success' => true,
            'data' => $matriculas
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
                'message' => 'ID da matrícula não informado'
            ];
        } else {
            // Obtém a matrícula pelo ID
            $matricula = $matriculaModel->getById($_GET['id']);
            
            if ($matricula) {
                $response = [
                    'success' => true,
                    'data' => $matricula
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Matrícula não encontrada'
                ];
            }
        }
        
        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;
        
    case 'create':
        // Verifica se o usuário tem permissão para criar matrículas
        exigirPermissao('matriculas', 'criar');
        
        // Verifica se é uma requisição POST
        if (!isPost()) {
            $response = [
                'success' => false,
                'message' => 'Método não permitido'
            ];
        } else {
            // Obtém os dados do formulário
            $data = [
                'aluno_id' => $_POST['aluno_id'] ?? '',
                'curso_id' => $_POST['curso_id'] ?? '',
                'polo_id' => $_POST['polo_id'] ?? null,
                'turma_id' => $_POST['turma_id'] ?? null,
                'data_matricula' => $_POST['data_matricula'] ?? date('Y-m-d'),
                'status' => $_POST['status'] ?? 'ativo',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // Valida os dados
            $errors = [];
            
            if (empty($data['aluno_id'])) {
                $errors[] = 'O aluno é obrigatório';
            }
            
            if (empty($data['curso_id'])) {
                $errors[] = 'O curso é obrigatório';
            }
            
            if (empty($data['data_matricula'])) {
                $errors[] = 'A data de matrícula é obrigatória';
            }
            
            // Se houver erros, retorna a resposta com os erros
            if (!empty($errors)) {
                $response = [
                    'success' => false,
                    'message' => 'Erros de validação',
                    'errors' => $errors
                ];
            } else {
                try {
                    // Cria a matrícula
                    $id = $matriculaModel->create($data);
                    
                    $response = [
                        'success' => true,
                        'message' => 'Matrícula criada com sucesso',
                        'data' => [
                            'id' => $id
                        ]
                    ];
                } catch (Exception $e) {
                    $response = [
                        'success' => false,
                        'message' => 'Erro ao criar matrícula: ' . $e->getMessage()
                    ];
                }
            }
        }
        
        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;
        
    case 'update':
        // Verifica se o usuário tem permissão para editar matrículas
        exigirPermissao('matriculas', 'editar');
        
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
                    'message' => 'ID da matrícula não informado'
                ];
            } else {
                // Obtém os dados do formulário
                $id = $_POST['id'];
                $data = [
                    'polo_id' => $_POST['polo_id'] ?? null,
                    'turma_id' => $_POST['turma_id'] ?? null,
                    'status' => $_POST['status'] ?? 'ativo',
                    'data_conclusao' => $_POST['data_conclusao'] ?? null,
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                try {
                    // Atualiza a matrícula
                    $result = $matriculaModel->update($id, $data);
                    
                    $response = [
                        'success' => true,
                        'message' => 'Matrícula atualizada com sucesso'
                    ];
                } catch (Exception $e) {
                    $response = [
                        'success' => false,
                        'message' => 'Erro ao atualizar matrícula: ' . $e->getMessage()
                    ];
                }
            }
        }
        
        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;
        
    case 'cancel':
        // Verifica se o usuário tem permissão para editar matrículas
        exigirPermissao('matriculas', 'editar');
        
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
                    'message' => 'ID da matrícula não informado'
                ];
            } else {
                // Obtém os dados do formulário
                $id = $_POST['id'];
                $motivo = $_POST['motivo'] ?? 'Cancelamento solicitado pelo usuário';
                
                try {
                    // Cancela a matrícula
                    $result = $matriculaModel->cancelar($id, $motivo);
                    
                    $response = [
                        'success' => true,
                        'message' => 'Matrícula cancelada com sucesso'
                    ];
                } catch (Exception $e) {
                    $response = [
                        'success' => false,
                        'message' => 'Erro ao cancelar matrícula: ' . $e->getMessage()
                    ];
                }
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
                'message' => 'ID da matrícula não informado'
            ];
        } else {
            // Obtém as notas da matrícula
            $notas = $matriculaModel->getNotas($_GET['id']);
            
            $response = [
                'success' => true,
                'data' => $notas
            ];
        }
        
        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;
        
    case 'desempenho':
        // Verifica se o ID foi informado
        if (!isset($_GET['id'])) {
            $response = [
                'success' => false,
                'message' => 'ID da matrícula não informado'
            ];
        } else {
            // Obtém o desempenho da matrícula
            $desempenho = $matriculaModel->calcularDesempenho($_GET['id']);
            
            $response = [
                'success' => true,
                'data' => $desempenho
            ];
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
