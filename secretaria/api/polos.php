<?php
/**
 * API para manipulação de polos
 */

// Inicializa o sistema
require_once __DIR__ . '/../includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica se o usuário tem permissão para acessar o módulo de polos
exigirPermissao('polos');

// Instancia o modelo de polo
$poloModel = new Polo();

// Verifica o tipo de requisição
$action = $_GET['action'] ?? 'list';

// Processa a requisição de acordo com a ação
switch ($action) {
    case 'list':
        // Obtém os filtros da requisição
        $filtros = [
            'nome' => $_GET['nome'] ?? '',
            'cidade_id' => $_GET['cidade_id'] ?? '',
            'status' => $_GET['status'] ?? '',
            'status_contrato' => $_GET['status_contrato'] ?? ''
        ];
        
        // Obtém os polos
        $polos = $poloModel->getAll($filtros);
        
        // Formata os dados para a resposta
        $response = [
            'success' => true,
            'data' => $polos
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
                'message' => 'ID do polo não informado'
            ];
        } else {
            // Obtém o polo pelo ID
            $polo = $poloModel->getById($_GET['id']);
            
            if ($polo) {
                $response = [
                    'success' => true,
                    'data' => $polo
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Polo não encontrado'
                ];
            }
        }
        
        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;
        
    case 'create':
        // Verifica se o usuário tem permissão para criar polos
        exigirPermissao('polos', 'criar');
        
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
                'razao_social' => $_POST['razao_social'] ?? null,
                'cnpj' => $_POST['cnpj'] ?? null,
                'endereco' => $_POST['endereco'] ?? null,
                'cidade_id' => $_POST['cidade_id'] ?? null,
                'responsavel_id' => $_POST['responsavel_id'] ?? null,
                'data_inicio_parceria' => $_POST['data_inicio_parceria'] ?? null,
                'data_fim_contrato' => $_POST['data_fim_contrato'] ?? null,
                'status_contrato' => $_POST['status_contrato'] ?? 'ativo',
                'limite_documentos' => $_POST['limite_documentos'] ?? 100,
                'documentos_emitidos' => 0,
                'telefone' => $_POST['telefone'] ?? null,
                'email' => $_POST['email'] ?? null,
                'status' => $_POST['status'] ?? 'ativo',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // Valida os dados
            $errors = [];
            
            if (empty($data['nome'])) {
                $errors[] = 'O nome é obrigatório';
            }
            
            if (!empty($data['cnpj']) && !validarCnpj($data['cnpj'])) {
                $errors[] = 'CNPJ inválido';
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
                // Cria o polo
                $id = $poloModel->create($data);
                
                if ($id) {
                    $response = [
                        'success' => true,
                        'message' => 'Polo criado com sucesso',
                        'data' => [
                            'id' => $id
                        ]
                    ];
                } else {
                    $response = [
                        'success' => false,
                        'message' => 'Erro ao criar polo'
                    ];
                }
            }
        }
        
        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;
        
    case 'update':
        // Verifica se o usuário tem permissão para editar polos
        exigirPermissao('polos', 'editar');
        
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
                    'message' => 'ID do polo não informado'
                ];
            } else {
                // Obtém os dados do formulário
                $id = $_POST['id'];
                $data = [
                    'nome' => $_POST['nome'] ?? '',
                    'razao_social' => $_POST['razao_social'] ?? null,
                    'cnpj' => $_POST['cnpj'] ?? null,
                    'endereco' => $_POST['endereco'] ?? null,
                    'cidade_id' => $_POST['cidade_id'] ?? null,
                    'responsavel_id' => $_POST['responsavel_id'] ?? null,
                    'data_inicio_parceria' => $_POST['data_inicio_parceria'] ?? null,
                    'data_fim_contrato' => $_POST['data_fim_contrato'] ?? null,
                    'status_contrato' => $_POST['status_contrato'] ?? 'ativo',
                    'limite_documentos' => $_POST['limite_documentos'] ?? 100,
                    'telefone' => $_POST['telefone'] ?? null,
                    'email' => $_POST['email'] ?? null,
                    'status' => $_POST['status'] ?? 'ativo',
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                // Valida os dados
                $errors = [];
                
                if (empty($data['nome'])) {
                    $errors[] = 'O nome é obrigatório';
                }
                
                if (!empty($data['cnpj']) && !validarCnpj($data['cnpj'])) {
                    $errors[] = 'CNPJ inválido';
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
                    // Atualiza o polo
                    $result = $poloModel->update($id, $data);
                    
                    if ($result) {
                        $response = [
                            'success' => true,
                            'message' => 'Polo atualizado com sucesso'
                        ];
                    } else {
                        $response = [
                            'success' => false,
                            'message' => 'Erro ao atualizar polo'
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
        // Verifica se o usuário tem permissão para excluir polos
        exigirPermissao('polos', 'excluir');
        
        // Verifica se o ID foi informado
        if (!isset($_GET['id'])) {
            $response = [
                'success' => false,
                'message' => 'ID do polo não informado'
            ];
        } else {
            // Exclui o polo
            $result = $poloModel->delete($_GET['id']);
            
            if ($result) {
                $response = [
                    'success' => true,
                    'message' => 'Polo excluído com sucesso'
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Erro ao excluir polo'
                ];
            }
        }
        
        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;
        
    case 'turmas':
        // Verifica se o ID foi informado
        if (!isset($_GET['id'])) {
            $response = [
                'success' => false,
                'message' => 'ID do polo não informado'
            ];
        } else {
            // Obtém as turmas do polo
            $turmas = $poloModel->getTurmas($_GET['id']);
            
            $response = [
                'success' => true,
                'data' => $turmas
            ];
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
                'message' => 'ID do polo não informado'
            ];
        } else {
            // Obtém os filtros adicionais
            $filtros = [
                'nome' => $_GET['nome'] ?? '',
                'curso_id' => $_GET['curso_id'] ?? '',
                'status' => $_GET['status'] ?? ''
            ];
            
            // Obtém os alunos do polo
            $alunos = $poloModel->getAlunos($_GET['id'], $filtros);
            
            $response = [
                'success' => true,
                'data' => $alunos
            ];
        }
        
        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;
        
    case 'stats':
        // Verifica se o ID foi informado
        if (!isset($_GET['id'])) {
            $response = [
                'success' => false,
                'message' => 'ID do polo não informado'
            ];
        } else {
            // Obtém as estatísticas do polo
            $stats = $poloModel->getEstatisticas($_GET['id']);
            
            $response = [
                'success' => true,
                'data' => $stats
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
