<?php
/**
 * Página de redefinição de senha
 */

// Inicializa o sistema
require_once __DIR__ . '/includes/init.php';

// Verifica se o usuário já está autenticado
if (usuarioAutenticado()) {
    // Redireciona para a página inicial
    redirect('index.php');
}

// Verifica se o token foi informado
if (!isset($_GET['token']) || empty($_GET['token'])) {
    // Redireciona para a página de recuperação de senha
    redirect('recuperar_senha.php');
}

// Obtém o token
$token = $_GET['token'];

// Instancia o banco de dados
$db = Database::getInstance();

// Busca o usuário pelo token
$usuario = $db->fetchOne("
    SELECT * FROM usuarios 
    WHERE token_recuperacao = ? 
    AND token_expiracao > NOW() 
    AND status = 'ativo'
", [$token]);

// Verifica se o usuário foi encontrado
if (!$usuario) {
    // Token inválido ou expirado
    $tokenInvalido = true;
} else {
    // Processa o formulário de redefinição de senha
    if (isPost()) {
        // Obtém os dados do formulário
        $senha = $_POST['senha'] ?? '';
        $confirmarSenha = $_POST['confirmar_senha'] ?? '';
        
        // Valida os dados
        $errors = [];
        
        if (empty($senha)) {
            $errors[] = 'A senha é obrigatória';
        } elseif (strlen($senha) < 6) {
            $errors[] = 'A senha deve ter pelo menos 6 caracteres';
        }
        
        if (empty($confirmarSenha)) {
            $errors[] = 'A confirmação de senha é obrigatória';
        } elseif ($senha !== $confirmarSenha) {
            $errors[] = 'As senhas não conferem';
        }
        
        // Se não houver erros, redefine a senha
        if (empty($errors)) {
            // Criptografa a senha
            $senhaCriptografada = password_hash($senha, PASSWORD_DEFAULT);
            
            // Atualiza a senha e limpa o token
            $db->update('usuarios', [
                'senha' => $senhaCriptografada,
                'token_recuperacao' => null,
                'token_expiracao' => null,
                'updated_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$usuario['id']]);
            
            // Registra o log de redefinição de senha
            registrarLog('usuarios', 'redefinir_senha', 'Redefinição de senha realizada com sucesso', $usuario['id'], 'usuario');
            
            // Redireciona para a página de login
            redirect('login.php?reset=success');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha - Faciência ERP</title>
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
            
            <h2 class="login-title">Redefinir Senha</h2>
            
            <?php if (isset($tokenInvalido) && $tokenInvalido): ?>
                <div class="alert alert-danger">
                    O link de redefinição de senha é inválido ou expirou.
                </div>
                
                <div class="text-center mt-4">
                    <a href="recuperar_senha.php" class="text-primary hover:underline">Solicitar novo link</a>
                </div>
            <?php else: ?>
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="list-disc list-inside">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <p class="text-gray-600 text-sm mb-6">
                    Digite sua nova senha abaixo.
                </p>
                
                <form method="post" action="redefinir_senha.php?token=<?php echo htmlspecialchars($token); ?>">
                    <div class="form-group">
                        <label for="senha" class="form-label">Nova Senha</label>
                        <input type="password" id="senha" name="senha" class="form-input" placeholder="Nova senha" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirmar_senha" class="form-label">Confirmar Senha</label>
                        <input type="password" id="confirmar_senha" name="confirmar_senha" class="form-input" placeholder="Confirme a nova senha" required>
                    </div>
                    
                    <button type="submit" class="btn-primary">Redefinir Senha</button>
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
