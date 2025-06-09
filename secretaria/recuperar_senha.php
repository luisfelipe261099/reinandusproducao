<?php
/**
 * Página de recuperação de senha
 */

// Inicializa o sistema
require_once __DIR__ . '/includes/init.php';

// Verifica se o usuário já está autenticado
if (usuarioAutenticado()) {
    // Redireciona para a página inicial
    redirect('index.php');
}

// Processa o formulário de recuperação de senha
if (isPost()) {
    // Obtém os dados do formulário
    $email = $_POST['email'] ?? '';
    
    // Valida os dados
    $errors = [];
    
    if (empty($email)) {
        $errors[] = 'O e-mail é obrigatório';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'E-mail inválido';
    }
    
    // Se não houver erros, tenta recuperar a senha
    if (empty($errors)) {
        // Instancia o banco de dados
        $db = Database::getInstance();
        
        // Busca o usuário pelo e-mail
        $usuario = $db->fetchOne("SELECT * FROM usuarios WHERE email = ? AND status = 'ativo'", [$email]);
        
        if ($usuario) {
            // Gera um token de recuperação
            $token = gerarToken();
            $expiracao = date('Y-m-d H:i:s', strtotime('+24 hours'));
            
            // Atualiza o token no banco de dados
            $db->update('usuarios', [
                'token_recuperacao' => $token,
                'token_expiracao' => $expiracao
            ], 'id = ?', [$usuario['id']]);
            
            // Registra o log de solicitação de recuperação de senha
            registrarLog('usuarios', 'recuperar_senha', 'Solicitação de recuperação de senha', $usuario['id'], 'usuario');
            
            // Envia o e-mail de recuperação de senha
            $linkRecuperacao = getBaseUrl() . '/redefinir_senha.php?token=' . $token;
            
            // Aqui você deve implementar o envio de e-mail
            // Por enquanto, apenas exibe uma mensagem de sucesso
            $success = 'Um e-mail com instruções para redefinir sua senha foi enviado para ' . $email;
        } else {
            // Registra o log de tentativa de recuperação de senha inválida
            registrarLog('usuarios', 'recuperar_senha_falha', 'Tentativa de recuperação de senha inválida', null, 'usuario', ['email' => $email]);
            
            $success = 'Um e-mail com instruções para redefinir sua senha foi enviado para ' . $email;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha - Faciência ERP</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #3B82F6;
            --primary-dark: #2563EB;
            --secondary: #10B981;
            --accent: #8B5CF6;
            --danger: #EF4444;
            --warning: #F59E0B;
            --info: #3B82F6;
            --light: #F3F4F6;
            --dark: #1F2937;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #F9FAFB;
        }
        
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-card {
            background-color: white;
            border-radius: 1rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            width: 100%;
            max-width: 400px;
            padding: 2rem;
        }
        
        .login-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
        }
        
        .login-logo img {
            height: 60px;
        }
        
        .login-title {
            text-align: center;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: #4B5563;
            margin-bottom: 0.5rem;
        }
        
        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #D1D5DB;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            transition: border-color 0.2s;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
        }
        
        .btn-primary {
            display: block;
            width: 100%;
            padding: 0.75rem 1rem;
            background-color: var(--primary);
            color: white;
            font-weight: 500;
            text-align: center;
            border-radius: 0.5rem;
            transition: background-color 0.2s;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
        }
        
        .login-footer {
            margin-top: 1.5rem;
            text-align: center;
            font-size: 0.875rem;
            color: #6B7280;
        }
        
        .login-footer a {
            color: var(--primary);
            text-decoration: none;
        }
        
        .login-footer a:hover {
            text-decoration: underline;
        }
        
        .alert {
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
        }
        
        .alert-danger {
            background-color: #FEE2E2;
            color: #991B1B;
            border: 1px solid #F87171;
        }
        
        .alert-success {
            background-color: #D1FAE5;
            color: #065F46;
            border: 1px solid #34D399;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-logo">
                <img src="/api/placeholder/60/60" alt="Logo Faciência">
                <h1 class="text-2xl font-bold text-gray-800 ml-3">Faciência ERP</h1>
            </div>
            
            <h2 class="login-title">Recuperar Senha</h2>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="list-disc list-inside">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success">
                    <?php echo $success; ?>
                </div>
                
                <div class="text-center mt-4">
                    <a href="login.php" class="text-primary hover:underline">Voltar para o login</a>
                </div>
            <?php else: ?>
                <p class="text-gray-600 text-sm mb-6">
                    Informe seu e-mail cadastrado para receber instruções de recuperação de senha.
                </p>
                
                <form method="post" action="recuperar_senha.php">
                    <div class="form-group">
                        <label for="email" class="form-label">E-mail</label>
                        <input type="email" id="email" name="email" class="form-input" placeholder="Seu e-mail" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                    </div>
                    
                    <button type="submit" class="btn-primary">Recuperar Senha</button>
                </form>
                
                <div class="login-footer">
                    <a href="login.php">Voltar para o login</a>
                </div>
            <?php endif; ?>
            
            <div class="login-footer mt-8">
                <p>&copy; <?php echo date('Y'); ?> Faciência ERP. Todos os direitos reservados.</p>
            </div>
        </div>
    </div>
</body>
</html>
