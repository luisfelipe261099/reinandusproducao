<?php
/**
 * Página de login alternativa
 * Use esta página se estiver tendo problemas com a página de login normal
 */

// Inicializa o sistema
require_once __DIR__ . '/includes/init.php';

// Processa o formulário de login
if (isset($_POST['email']) && isset($_POST['senha'])) {
    // Obtém os dados do formulário
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    
    // Valida os dados
    $errors = [];
    
    if (empty($email)) {
        $errors[] = 'O e-mail é obrigatório';
    }
    
    if (empty($senha)) {
        $errors[] = 'A senha é obrigatória';
    }
    
    // Se não houver erros, tenta autenticar o usuário
    if (empty($errors)) {
        try {
            // Instancia o banco de dados
            $db = Database::getInstance();
            
            // Busca o usuário pelo e-mail
            $usuario = $db->fetchOne("SELECT * FROM usuarios WHERE email = ? AND status = 'ativo'", [$email]);
            
            if ($usuario && password_verify($senha, $usuario['senha'])) {
                // Limpa a sessão atual
                session_unset();
                
                // Define as variáveis de sessão manualmente
                $_SESSION['user_id'] = $usuario['id'];
                $_SESSION['user_nome'] = $usuario['nome'];
                $_SESSION['user_email'] = $usuario['email'];
                $_SESSION['user_tipo'] = $usuario['tipo'];
                
                // Se for um usuário do tipo polo, busca o ID do polo
                if ($usuario['tipo'] === 'polo') {
                    $sql = "SELECT id FROM polos WHERE responsavel_id = ?";
                    $resultado = $db->fetchOne($sql, [$usuario['id']]);
                    
                    if ($resultado && isset($resultado['id'])) {
                        $_SESSION['polo_id'] = $resultado['id'];
                    }
                }
                
                // Atualiza o último acesso
                $db->update('usuarios', [
                    'ultimo_acesso' => date('Y-m-d H:i:s')
                ], 'id = ?', [$usuario['id']]);
                
                // Redireciona com base no tipo de usuário
                if ($usuario['tipo'] === 'polo') {
                    header('Location: polo/index.php');
                    exit;
                } else {
                    header('Location: index.php');
                    exit;
                }
            } else {
                $errors[] = 'E-mail ou senha inválidos';
            }
        } catch (Exception $e) {
            $errors[] = 'Erro ao processar o login: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Alternativo - Faciência ERP</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        
        .container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
            width: 100%;
            max-width: 400px;
        }
        
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: bold;
        }
        
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box;
        }
        
        button {
            width: 100%;
            padding: 12px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
        }
        
        button:hover {
            background-color: #45a049;
        }
        
        .error-message {
            background-color: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .info-message {
            background-color: #e3f2fd;
            color: #1565c0;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .links {
            text-align: center;
            margin-top: 20px;
        }
        
        .links a {
            color: #2196F3;
            text-decoration: none;
        }
        
        .links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Login Alternativo</h1>
        
        <div class="info-message">
            Esta é uma página de login alternativa para casos em que a página de login normal não esteja funcionando corretamente.
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="error-message">
                <ul style="margin: 0; padding-left: 20px;">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="post" action="login_alternativo.php">
            <div class="form-group">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" required>
            </div>
            
            <button type="submit">Entrar</button>
        </form>
        
        <div class="links">
            <a href="login.php">Voltar para o login normal</a> | 
            <a href="corrigir_redirecionamento.php">Corrigir problemas de redirecionamento</a>
        </div>
    </div>
</body>
</html>
