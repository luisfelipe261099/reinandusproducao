<?php
/**
 * API para manipulação de usuários
 */

// Inicializa o sistema
require_once __DIR__ . '/../includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica se o usuário tem permissão para acessar o módulo de usuários
exigirPermissao('usuarios');

// Instancia o banco de dados
$db = Database::getInstance();

// Verifica o tipo de requisição
$action = $_GET['action'] ?? 'list';

// Processa a requisição de acordo com a ação
switch ($action) {
    case 'list':
        // Verifica se o usuário tem permissão para visualizar usuários
        exigirPermissao('usuarios', 'visualizar');
        
        // Obtém os filtros da requisição
        $filtros = [];
        $params = [];
        
        if (!empty($_GET['nome'])) {
            $filtros[] = "nome LIKE ?";
            $params[] = "%" . $_GET['nome'] . "%";
        }
        
        if (!empty($_GET['email'])) {
            $filtros[] = "email LIKE ?";
            $params[] = "%" . $_GET['email'] . "%";
        }
        
        if (!empty($_GET['tipo'])) {
            $filtros[] = "tipo = ?";
            $params[] = $_GET['tipo'];
        }
        
        if (!empty($_GET['status'])) {
            $filtros[] = "status = ?";
            $params[] = $_GET['status'];
        }
        
        // Monta a cláusula WHERE
        $whereClause = !empty($filtros) ? "WHERE " . implode(" AND ", $filtros) : "";
        
        // Consulta SQL
        $sql = "
            SELECT 
                id, 
                nome, 
                email, 
                cpf, 
                tipo, 
                status, 
                ultimo_acesso, 
                created_at
            FROM 
                usuarios
            {$whereClause}
            ORDER BY nome
        ";
        
        // Obtém os usuários
        $usuarios = $db->fetchAll($sql, $params);
        
        // Formata os dados para a resposta
        $response = [
            'success' => true,
            'data' => $usuarios
        ];
        
        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;
        
    case 'get':
        // Verifica se o usuário tem permissão para visualizar usuários
        exigirPermissao('usuarios', 'visualizar');
        
        // Verifica se o ID foi informado
        if (!isset($_GET['id'])) {
            $response = [
                'success' => false,
                'message' => 'ID do usuário não informado'
            ];
        } else {
            // Obtém o usuário pelo ID
            $sql = "
                SELECT 
                    id, 
                    nome, 
                    email, 
                    cpf, 
                    tipo, 
                    status, 
                    ultimo_acesso, 
                    created_at
                FROM 
                    usuarios
                WHERE 
                    id = ?
            ";
            
            $usuario = $db->fetchOne($sql, [(int)$_GET['id']]);
            
            if ($usuario) {
                // Obtém as permissões do usuário
                $sql = "
                    SELECT 
                        modulo, 
                        nivel_acesso, 
                        restricoes
                    FROM 
                        permissoes
                    WHERE 
                        usuario_id = ?
                ";
                
                $permissoes = $db->fetchAll($sql, [(int)$_GET['id']]);
                
                $usuario['permissoes'] = $permissoes;
                
                $response = [
                    'success' => true,
                    'data' => $usuario
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Usuário não encontrado'
                ];
            }
        }
        
        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;
        
    case 'create':
        // Verifica se o usuário tem permissão para criar usuários
        exigirPermissao('usuarios', 'criar');
        
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
                'email' => $_POST['email'] ?? '',
                'cpf' => $_POST['cpf'] ?? null,
                'senha' => $_POST['senha'] ?? '',
                'tipo' => $_POST['tipo'] ?? '',
                'status' => $_POST['status'] ?? 'ativo',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // Valida os dados
            $errors = [];
            
            if (empty($data['nome'])) {
                $errors[] = 'O nome é obrigatório';
            }
            
            if (empty($data['email'])) {
                $errors[] = 'O e-mail é obrigatório';
            } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'E-mail inválido';
            } else {
                // Verifica se o e-mail já está em uso
                $usuario = $db->fetchOne("SELECT id FROM usuarios WHERE email = ?", [$data['email']]);
                
                if ($usuario) {
                    $errors[] = 'Este e-mail já está em uso';
                }
            }
            
            if (!empty($data['cpf']) && !validarCpf($data['cpf'])) {
                $errors[] = 'CPF inválido';
            }
            
            if (empty($data['senha'])) {
                $errors[] = 'A senha é obrigatória';
            } elseif (strlen($data['senha']) < 6) {
                $errors[] = 'A senha deve ter pelo menos 6 caracteres';
            }
            
            if (empty($data['tipo'])) {
                $errors[] = 'O tipo de usuário é obrigatório';
            }
            
            // Se houver erros, retorna a resposta com os erros
            if (!empty($errors)) {
                $response = [
                    'success' => false,
                    'message' => 'Erros de validação',
                    'errors' => $errors
                ];
            } else {
                // Criptografa a senha
                $data['senha'] = password_hash($data['senha'], PASSWORD_DEFAULT);
                
                // Inicia uma transação
                $db->beginTransaction();
                
                try {
                    // Cria o usuário
                    $id = $db->insert('usuarios', $data);
                    
                    // Cria as permissões padrão
                    $permissoes = $_POST['permissoes'] ?? [];
                    
                    foreach ($permissoes as $modulo => $nivelAcesso) {
                        $db->insert('permissoes', [
                            'usuario_id' => $id,
                            'modulo' => $modulo,
                            'nivel_acesso' => $nivelAcesso,
                            'created_at' => date('Y-m-d H:i:s')
                        ]);
                    }
                    
                    // Registra o log
                    registrarLog(
                        'usuarios',
                        'criar',
                        'Criação de novo usuário: ' . $data['nome'],
                        $id,
                        'usuario',
                        null,
                        array_merge($data, ['senha' => '********'])
                    );
                    
                    // Confirma a transação
                    $db->commit();
                    
                    $response = [
                        'success' => true,
                        'message' => 'Usuário criado com sucesso',
                        'data' => [
                            'id' => $id
                        ]
                    ];
                } catch (Exception $e) {
                    // Reverte a transação em caso de erro
                    $db->rollback();
                    
                    $response = [
                        'success' => false,
                        'message' => 'Erro ao criar usuário: ' . $e->getMessage()
                    ];
                }
            }
        }
        
        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;
        
    case 'update':
        // Verifica se o usuário tem permissão para editar usuários
        exigirPermissao('usuarios', 'editar');
        
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
                    'message' => 'ID do usuário não informado'
                ];
            } else {
                // Obtém os dados do formulário
                $id = (int)$_POST['id'];
                $data = [
                    'nome' => $_POST['nome'] ?? '',
                    'email' => $_POST['email'] ?? '',
                    'cpf' => $_POST['cpf'] ?? null,
                    'tipo' => $_POST['tipo'] ?? '',
                    'status' => $_POST['status'] ?? 'ativo',
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                // Verifica se a senha foi informada
                if (!empty($_POST['senha'])) {
                    $data['senha'] = password_hash($_POST['senha'], PASSWORD_DEFAULT);
                }
                
                // Valida os dados
                $errors = [];
                
                if (empty($data['nome'])) {
                    $errors[] = 'O nome é obrigatório';
                }
                
                if (empty($data['email'])) {
                    $errors[] = 'O e-mail é obrigatório';
                } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'E-mail inválido';
                } else {
                    // Verifica se o e-mail já está em uso por outro usuário
                    $usuario = $db->fetchOne("SELECT id FROM usuarios WHERE email = ? AND id != ?", [$data['email'], $id]);
                    
                    if ($usuario) {
                        $errors[] = 'Este e-mail já está em uso';
                    }
                }
                
                if (!empty($data['cpf']) && !validarCpf($data['cpf'])) {
                    $errors[] = 'CPF inválido';
                }
                
                if (!empty($_POST['senha']) && strlen($_POST['senha']) < 6) {
                    $errors[] = 'A senha deve ter pelo menos 6 caracteres';
                }
                
                if (empty($data['tipo'])) {
                    $errors[] = 'O tipo de usuário é obrigatório';
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
                    $usuarioAntigo = $db->fetchOne("SELECT * FROM usuarios WHERE id = ?", [$id]);
                    
                    // Inicia uma transação
                    $db->beginTransaction();
                    
                    try {
                        // Atualiza o usuário
                        $result = $db->update('usuarios', $data, 'id = ?', [$id]);
                        
                        // Atualiza as permissões
                        $permissoes = $_POST['permissoes'] ?? [];
                        
                        // Remove as permissões existentes
                        $db->delete('permissoes', 'usuario_id = ?', [$id]);
                        
                        // Cria as novas permissões
                        foreach ($permissoes as $modulo => $nivelAcesso) {
                            $db->insert('permissoes', [
                                'usuario_id' => $id,
                                'modulo' => $modulo,
                                'nivel_acesso' => $nivelAcesso,
                                'created_at' => date('Y-m-d H:i:s')
                            ]);
                        }
                        
                        // Registra o log
                        registrarLog(
                            'usuarios',
                            'editar',
                            'Atualização de usuário: ' . $data['nome'],
                            $id,
                            'usuario',
                            array_merge($usuarioAntigo, ['senha' => '********']),
                            array_merge($data, ['senha' => '********'])
                        );
                        
                        // Confirma a transação
                        $db->commit();
                        
                        $response = [
                            'success' => true,
                            'message' => 'Usuário atualizado com sucesso'
                        ];
                    } catch (Exception $e) {
                        // Reverte a transação em caso de erro
                        $db->rollback();
                        
                        $response = [
                            'success' => false,
                            'message' => 'Erro ao atualizar usuário: ' . $e->getMessage()
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
        // Verifica se o usuário tem permissão para excluir usuários
        exigirPermissao('usuarios', 'excluir');
        
        // Verifica se o ID foi informado
        if (!isset($_GET['id'])) {
            $response = [
                'success' => false,
                'message' => 'ID do usuário não informado'
            ];
        } else {
            // Obtém o ID do usuário
            $id = (int)$_GET['id'];
            
            // Verifica se o usuário existe
            $usuario = $db->fetchOne("SELECT * FROM usuarios WHERE id = ?", [$id]);
            
            if (!$usuario) {
                $response = [
                    'success' => false,
                    'message' => 'Usuário não encontrado'
                ];
            } else {
                // Verifica se o usuário está tentando excluir a si mesmo
                if ($id === getUsuarioId()) {
                    $response = [
                        'success' => false,
                        'message' => 'Você não pode excluir seu próprio usuário'
                    ];
                } else {
                    // Inicia uma transação
                    $db->beginTransaction();
                    
                    try {
                        // Remove as permissões do usuário
                        $db->delete('permissoes', 'usuario_id = ?', [$id]);
                        
                        // Exclui o usuário
                        $result = $db->delete('usuarios', 'id = ?', [$id]);
                        
                        // Registra o log
                        registrarLog(
                            'usuarios',
                            'excluir',
                            'Exclusão de usuário: ' . $usuario['nome'],
                            $id,
                            'usuario',
                            array_merge($usuario, ['senha' => '********']),
                            null
                        );
                        
                        // Confirma a transação
                        $db->commit();
                        
                        $response = [
                            'success' => true,
                            'message' => 'Usuário excluído com sucesso'
                        ];
                    } catch (Exception $e) {
                        // Reverte a transação em caso de erro
                        $db->rollback();
                        
                        $response = [
                            'success' => false,
                            'message' => 'Erro ao excluir usuário: ' . $e->getMessage()
                        ];
                    }
                }
            }
        }
        
        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;
        
    case 'permissoes':
        // Verifica se o usuário tem permissão para visualizar permissões
        exigirPermissao('usuarios', 'visualizar');
        
        // Verifica se o ID foi informado
        if (!isset($_GET['id'])) {
            $response = [
                'success' => false,
                'message' => 'ID do usuário não informado'
            ];
        } else {
            // Obtém o ID do usuário
            $id = (int)$_GET['id'];
            
            // Obtém as permissões do usuário
            $sql = "
                SELECT 
                    modulo, 
                    nivel_acesso, 
                    restricoes
                FROM 
                    permissoes
                WHERE 
                    usuario_id = ?
            ";
            
            $permissoes = $db->fetchAll($sql, [$id]);
            
            $response = [
                'success' => true,
                'data' => $permissoes
            ];
        }
        
        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;
        
    case 'atualizar_permissoes':
        // Verifica se o usuário tem permissão para editar permissões
        exigirPermissao('usuarios', 'editar');
        
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
                    'message' => 'ID do usuário não informado'
                ];
            } else {
                // Obtém o ID do usuário
                $id = (int)$_POST['id'];
                
                // Obtém as permissões
                $permissoes = $_POST['permissoes'] ?? [];
                
                // Inicia uma transação
                $db->beginTransaction();
                
                try {
                    // Remove as permissões existentes
                    $db->delete('permissoes', 'usuario_id = ?', [$id]);
                    
                    // Cria as novas permissões
                    foreach ($permissoes as $modulo => $nivelAcesso) {
                        $db->insert('permissoes', [
                            'usuario_id' => $id,
                            'modulo' => $modulo,
                            'nivel_acesso' => $nivelAcesso,
                            'created_at' => date('Y-m-d H:i:s')
                        ]);
                    }
                    
                    // Registra o log
                    registrarLog(
                        'usuarios',
                        'atualizar_permissoes',
                        'Atualização de permissões do usuário ID ' . $id,
                        $id,
                        'permissao',
                        null,
                        $permissoes
                    );
                    
                    // Confirma a transação
                    $db->commit();
                    
                    $response = [
                        'success' => true,
                        'message' => 'Permissões atualizadas com sucesso'
                    ];
                } catch (Exception $e) {
                    // Reverte a transação em caso de erro
                    $db->rollback();
                    
                    $response = [
                        'success' => false,
                        'message' => 'Erro ao atualizar permissões: ' . $e->getMessage()
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
