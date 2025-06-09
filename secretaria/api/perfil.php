<?php
/**
 * API para manipulação do perfil do usuário
 */

// Inicializa o sistema
require_once __DIR__ . '/../includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Instancia o banco de dados
$db = Database::getInstance();

// Verifica o tipo de requisição
$action = $_GET['action'] ?? 'get';

// Processa a requisição de acordo com a ação
switch ($action) {
    case 'get':
        // Obtém o ID do usuário autenticado
        $id = getUsuarioId();
        
        // Obtém os dados do usuário
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
        
        $usuario = $db->fetchOne($sql, [$id]);
        
        if ($usuario) {
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
        
        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;
        
    case 'update':
        // Verifica se é uma requisição POST
        if (!isPost()) {
            $response = [
                'success' => false,
                'message' => 'Método não permitido'
            ];
        } else {
            // Obtém o ID do usuário autenticado
            $id = getUsuarioId();
            
            // Obtém os dados do formulário
            $data = [
                'nome' => $_POST['nome'] ?? '',
                'email' => $_POST['email'] ?? '',
                'cpf' => $_POST['cpf'] ?? null,
                'updated_at' => date('Y-m-d H:i:s')
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
                // Verifica se o e-mail já está em uso por outro usuário
                $usuario = $db->fetchOne("SELECT id FROM usuarios WHERE email = ? AND id != ?", [$data['email'], $id]);
                
                if ($usuario) {
                    $errors[] = 'Este e-mail já está em uso';
                }
            }
            
            if (!empty($data['cpf']) && !validarCpf($data['cpf'])) {
                $errors[] = 'CPF inválido';
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
                
                // Atualiza o usuário
                $result = $db->update('usuarios', $data, 'id = ?', [$id]);
                
                if ($result) {
                    // Registra o log
                    registrarLog(
                        'usuarios',
                        'editar_perfil',
                        'Atualização de perfil: ' . $data['nome'],
                        $id,
                        'usuario',
                        array_merge($usuarioAntigo, ['senha' => '********']),
                        array_merge($data, ['senha' => '********'])
                    );
                    
                    $response = [
                        'success' => true,
                        'message' => 'Perfil atualizado com sucesso'
                    ];
                } else {
                    $response = [
                        'success' => false,
                        'message' => 'Erro ao atualizar perfil'
                    ];
                }
            }
        }
        
        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;
        
    case 'change_password':
        // Verifica se é uma requisição POST
        if (!isPost()) {
            $response = [
                'success' => false,
                'message' => 'Método não permitido'
            ];
        } else {
            // Obtém o ID do usuário autenticado
            $id = getUsuarioId();
            
            // Obtém os dados do formulário
            $senhaAtual = $_POST['senha_atual'] ?? '';
            $novaSenha = $_POST['nova_senha'] ?? '';
            $confirmarSenha = $_POST['confirmar_senha'] ?? '';
            
            // Valida os dados
            $errors = [];
            
            if (empty($senhaAtual)) {
                $errors[] = 'A senha atual é obrigatória';
            }
            
            if (empty($novaSenha)) {
                $errors[] = 'A nova senha é obrigatória';
            } elseif (strlen($novaSenha) < 6) {
                $errors[] = 'A nova senha deve ter pelo menos 6 caracteres';
            }
            
            if (empty($confirmarSenha)) {
                $errors[] = 'A confirmação de senha é obrigatória';
            } elseif ($novaSenha !== $confirmarSenha) {
                $errors[] = 'As senhas não conferem';
            }
            
            // Se não houver erros, verifica a senha atual
            if (empty($errors)) {
                // Obtém o usuário
                $usuario = $db->fetchOne("SELECT * FROM usuarios WHERE id = ?", [$id]);
                
                if (!$usuario || !password_verify($senhaAtual, $usuario['senha'])) {
                    $errors[] = 'Senha atual incorreta';
                }
            }
            
            // Se não houver erros, atualiza a senha
            if (empty($errors)) {
                // Criptografa a nova senha
                $senhaCriptografada = password_hash($novaSenha, PASSWORD_DEFAULT);
                
                // Atualiza a senha
                $result = $db->update('usuarios', [
                    'senha' => $senhaCriptografada,
                    'updated_at' => date('Y-m-d H:i:s')
                ], 'id = ?', [$id]);
                
                if ($result) {
                    // Registra o log
                    registrarLog(
                        'usuarios',
                        'alterar_senha',
                        'Alteração de senha realizada com sucesso',
                        $id,
                        'usuario'
                    );
                    
                    $response = [
                        'success' => true,
                        'message' => 'Senha alterada com sucesso'
                    ];
                } else {
                    $response = [
                        'success' => false,
                        'message' => 'Erro ao alterar senha'
                    ];
                }
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Erros de validação',
                    'errors' => $errors
                ];
            }
        }
        
        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;
        
    case 'logs':
        // Obtém os logs do usuário
        $id = getUsuarioId();
        
        // Obtém os logs
        $sql = "
            SELECT 
                id,
                modulo,
                acao,
                descricao,
                created_at
            FROM 
                logs_sistema
            WHERE 
                usuario_id = ?
            ORDER BY 
                created_at DESC
            LIMIT 50
        ";
        
        $logs = $db->fetchAll($sql, [$id]);
        
        $response = [
            'success' => true,
            'data' => $logs
        ];
        
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
