<?php
// login.php
session_start();

// Verificar se o usuário já está logado
if (isset($_SESSION['aluno_id'])) {
    header("Location: index.php");
    exit;
}

// Processar formulário de login (simulação)
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    
    // Simulação de autenticação para desenvolvimento
    if ($email === 'bruno.gui@gmail.com' && $senha === 'senha123') {
        $_SESSION['aluno_id'] = 14409;
        $_SESSION['aluno_nome'] = 'Bruno Guilherme Souza';
        $_SESSION['aluno_email'] = 'bruno.gui@gmail.com';
        $_SESSION['aluno_foto'] = null;
        header("Location: index.php");
        exit;
    } else {
        $error = 'E-mail ou senha inválidos';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Faciência EAD</title>
    
    <!-- CSS Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- CSS Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS Personalizado -->
    <style>
        :root {
            --primary-color: #6A5ACD;
            --secondary-color: #4682B4;
            --accent-color: #20B2AA;
            --light-color: #F8F9FA;
            --dark-color: #212529;
            --success-color: #2E8B57;
            --warning-color: #DAA520;
            --danger-color: #CD5C5C;
            --border-radius: 0.5rem;
            --card-border-radius: 1rem;
            --box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f5f7fb;
            height: 100vh;
        }
        
        .login-container {
            max-width: 900px;
            height: 100%;
        }
        
        .login-card {
            border: none;
            border-radius: var(--card-border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
        }
        
        .login-image {
            background-image: url('assets/img/login-bg.jpg');
            background-size: cover;
            background-position: center;
            min-height: 400px;
        }
        
        .login-form {
            padding: 3rem;
        }
        
        .login-form h2 {
            color: var(--primary-color);
            margin-bottom: 2rem;
        }
        
        .form-control {
            border-radius: var(--border-radius);
            padding: 0.75rem 1rem;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            border-radius: var(--border-radius);
            padding: 0.75rem 1.5rem;
            font-weight: 600;
        }
        
        .btn-primary:hover {
            background-color: #5a4ab2;
            border-color: #5a4ab2;
        }
        
        .logo {
            max-width: 200px;
            margin-bottom: 2rem;
        }
        
        .login-divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 1.5rem 0;
            color: #6c757d;
        }
        
        .login-divider::before,
        .login-divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #ced4da;
        }
        
        .login-divider::before {
            margin-right: 1rem;
        }
        
        .login-divider::after {
            margin-left: 1rem;
        }
        
        .social-login {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .social-btn {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem;
            border-radius: var(--border-radius);
            color: white;
            text-decoration: none;
        }
        
        .google-btn {
            background-color: #DB4437;
        }
        
        .facebook-btn {
            background-color: #4267B2;
        }
        
        .social-btn i {
            margin-right: 0.5rem;
        }
        
        @media (max-width: 767.98px) {
            .login-image {
                min-height: 200px;
            }
            
            .login-form {
                padding: 2rem;
            }
        }
    </style>
</head>
<body class="d-flex align-items-center">
    <div class="container login-container">
        <div class="card login-card">
            <div class="row g-0">
                <div class="col-md-6 d-none d-md-block">
                    <div class="login-image h-100 d-flex align-items-center justify-content-center">
                        <div class="text-center text-white p-4" style="background-color: rgba(0, 0, 0, 0.5); border-radius: 1rem;">
                            <h3>Bem-vindo ao Ambiente Virtual de Aprendizagem</h3>
                            <p class="mb-0">Aprenda no seu ritmo, em qualquer lugar</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="login-form">
                        <div class="text-center">
                            <img src="assets/img/logo-faciencia.png" alt="Logo Faciência EAD" class="logo">
                        </div>
                        
                        <h2 class="text-center">Acesse sua conta</h2>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>
                        
                        <form method="post" action="">
                            <div class="mb-3">
                                <label for="email" class="form-label">E-mail</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" placeholder="Seu e-mail" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="d-flex justify-content-between">
                                    <label for="senha" class="form-label">Senha</label>
                                    <a href="recuperar-senha.php" class="text-decoration-none small">Esqueceu a senha?</a>
                                </div>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="senha" name="senha" placeholder="Sua senha" required>
                                </div>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="lembrar" name="lembrar">
                                <label class="form-check-label" for="lembrar">Lembrar de mim</label>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Entrar</button>
                            </div>
                        </form>
                        
                        <div class="login-divider">ou</div>
                        
                        <div class="social-login">
                            <a href="#" class="social-btn google-btn">
                                <i class="fab fa-google"></i> Google
                            </a>
                            <a href="#" class="social-btn facebook-btn">
                                <i class="fab fa-facebook-f"></i> Facebook
                            </a>
                        </div>
                        
                        <div class="text-center">
                            <p class="mb-0">Não tem uma conta? <a href="cadastro.php" class="text-decoration-none">Cadastre-se</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JS Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>