<?php
/**
 * Página de login
 */

// Inicializa o sistema
require_once __DIR__ . '/includes/init.php';

// Verifica se o usuário já está autenticado
if (usuarioAutenticado()) {
    // Redireciona para a página inicial
    redirect('secretaria/index.php');
}

// Função para verificar o reCAPTCHA
function verificarReCaptcha($recaptchaResponse) {
    $secretKey = "6Ld41jIrAAAAAJtpARM043MVk1ab4cXZFBWJByiY";
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = [
        'secret' => $secretKey,
        'response' => $recaptchaResponse,
        'remoteip' => $_SERVER['REMOTE_ADDR']
    ];

    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];

    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    $resultJson = json_decode($result, true);

    return $resultJson['success'] ?? false;
}

// Função para verificar tentativas de login
function verificarTentativasLogin($email, $ip) {
    $db = Database::getInstance();
    $limite = 5; // Número máximo de tentativas
    $intervalo = 15; // Intervalo de tempo em minutos

    // Busca tentativas de login nos últimos X minutos
    $sql = "SELECT COUNT(*) as total FROM logs_sistema
            WHERE (acao = 'login_falha' OR acao = 'login_recaptcha_falha')
            AND (dados_novos LIKE ? OR dados_novos LIKE ?)
            AND created_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)";

    $emailPattern = '%"email":"' . $email . '"%';
    $ipPattern = '%"ip":"' . $ip . '"%';

    $resultado = $db->fetchOne($sql, [$emailPattern, $ipPattern, $intervalo]);

    return [
        'bloqueado' => ($resultado['total'] ?? 0) >= $limite,
        'tentativas' => $resultado['total'] ?? 0,
        'limite' => $limite,
        'intervalo' => $intervalo
    ];
}

// Processa o formulário de login
if (isPost()) {
    // Obtém os dados do formulário
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    $lembrar = isset($_POST['lembrar']) && $_POST['lembrar'] === 'on';
    $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';
    $ip = $_SERVER['REMOTE_ADDR'];

    // Verifica se o usuário está bloqueado por excesso de tentativas
    $verificacaoTentativas = verificarTentativasLogin($email, $ip);

    // Valida os dados
    $errors = [];

    // Se estiver bloqueado, impede o login
    if ($verificacaoTentativas['bloqueado']) {
        $errors[] = "Muitas tentativas de login. Por favor, aguarde {$verificacaoTentativas['intervalo']} minutos antes de tentar novamente.";

        // Registra a tentativa bloqueada
        $dadosLog = [
            'email' => $email,
            'ip' => $ip,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Desconhecido',
            'data_hora' => date('Y-m-d H:i:s'),
            'tentativas' => $verificacaoTentativas['tentativas'],
            'limite' => $verificacaoTentativas['limite']
        ];
        registrarLog('usuarios', 'login_bloqueado', 'Tentativa de login bloqueada por excesso de tentativas', null, 'usuario', null, $dadosLog);
    }

    if (empty($email)) {
        $errors[] = 'O e-mail é obrigatório';
    }

    if (empty($senha)) {
        $errors[] = 'A senha é obrigatória';
    }

    // Verifica o reCAPTCHA
    if (empty($recaptchaResponse)) {
        $errors[] = 'Por favor, confirme que você não é um robô';
    } else if (!verificarReCaptcha($recaptchaResponse)) {
        $errors[] = 'Verificação de reCAPTCHA falhou. Por favor, tente novamente';

        // Registra tentativa de login com reCAPTCHA inválido
        $dadosLog = [
            'email' => $email,
            'ip' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Desconhecido',
            'data_hora' => date('Y-m-d H:i:s')
        ];
        registrarLog('usuarios', 'login_recaptcha_falha', 'Tentativa de login com reCAPTCHA inválido', null, 'usuario', null, $dadosLog);
    }

    // Se não houver erros, tenta autenticar o usuário
    if (empty($errors)) {
        // Instancia o banco de dados
        $db = Database::getInstance();

        // Busca o usuário pelo e-mail
        $usuario = $db->fetchOne("SELECT * FROM usuarios WHERE email = ? AND status = 'ativo'", [$email]);

        if ($usuario && password_verify($senha, $usuario['senha'])) {
            // Autentica o usuário
            Auth::login($usuario);

            // Define o cookie de "lembrar-me" se solicitado
            if ($lembrar) {
                $token = gerarToken();
                $expiracao = date('Y-m-d H:i:s', strtotime('+30 days'));

                // Atualiza o token no banco de dados
                $db->update('usuarios', [
                    'token_recuperacao' => $token,
                    'token_expiracao' => $expiracao
                ], 'id = ?', [$usuario['id']]);

                // Define o cookie
                setcookie('remember_token', $token, time() + (86400 * 30), '/');
            }

            // Registra o log de login com informações adicionais
            $dadosLog = [
                'ip' => $_SERVER['REMOTE_ADDR'],
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Desconhecido',
                'data_hora' => date('Y-m-d H:i:s')
            ];            registrarLog('usuarios', 'login', 'Login realizado com sucesso', $usuario['id'], 'usuario', null, $dadosLog);

            // Redireciona com base no tipo de usuário
            if ($usuario['tipo'] === 'admin_master') {
                redirect('administrador/index.php');
            } else if ($usuario['tipo'] === 'polo') {
                redirect('polo/index.php');
            } else {
                redirect('secretaria/index.php');
            }
        } else {
            // Registra o log de tentativa de login inválida com informações adicionais
            $dadosLog = [
                'email' => $email,
                'ip' => $_SERVER['REMOTE_ADDR'],
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Desconhecido',
                'data_hora' => date('Y-m-d H:i:s')
            ];
            registrarLog('usuarios', 'login_falha', 'Tentativa de login inválida', null, 'usuario', null, $dadosLog);

            $errors[] = 'E-mail ou senha inválidos';
        }
    }
} else {
    // Verifica se existe um cookie de "lembrar-me"
    if (isset($_COOKIE['remember_token'])) {
        $token = $_COOKIE['remember_token'];

        // Instancia o banco de dados
        $db = Database::getInstance();

        // Busca o usuário pelo token
        $usuario = $db->fetchOne("
            SELECT * FROM usuarios
            WHERE token_recuperacao = ?
            AND token_expiracao > NOW()
            AND status = 'ativo'
        ", [$token]);

        if ($usuario) {
            // Autentica o usuário
            Auth::login($usuario);

            // Registra o log de login automático com informações adicionais
            $dadosLog = [
                'ip' => $_SERVER['REMOTE_ADDR'],
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Desconhecido',
                'data_hora' => date('Y-m-d H:i:s'),
                'metodo' => 'cookie'
            ];            registrarLog('usuarios', 'login_automatico', 'Login automático realizado com sucesso', $usuario['id'], 'usuario', null, $dadosLog);

            // Redireciona com base no tipo de usuário
            if ($usuario['tipo'] === 'admin_master') {
                redirect('administrador/index.php');
            } else if ($usuario['tipo'] === 'polo') {
                redirect('polo/index.php');
            } else {
                redirect('secretaria/index.php');
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Faciência ERP</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
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

        .form-check {
            display: flex;
            align-items: center;
        }

        .form-check-input {
            width: 1rem;
            height: 1rem;
            margin-right: 0.5rem;
        }

        .form-check-label {
            font-size: 0.875rem;
            color: #4B5563;
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

        /* Estilo para o reCAPTCHA */
        .g-recaptcha {
            display: flex;
            justify-content: center;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-logo">

                <h1 class="text-2xl font-bold text-gray-800 ml-3">Faciência ERP</h1>
            </div>

            <h2 class="login-title">Acesso ao Sistema</h2>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="list-disc list-inside">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['logout']) && $_GET['logout'] === 'success'): ?>
                <div class="alert alert-success">
                    Você saiu do sistema com sucesso.
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['reset']) && $_GET['reset'] === 'success'): ?>
                <div class="alert alert-success">
                    Sua senha foi redefinida com sucesso. Faça login com a nova senha.
                </div>
            <?php endif; ?>

            <form method="post" action="login.php">
                <div class="form-group">
                    <label for="email" class="form-label">E-mail</label>
                    <input type="email" id="email" name="email" class="form-input" placeholder="Seu e-mail" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="senha" class="form-label">Senha</label>
                    <input type="password" id="senha" name="senha" class="form-input" placeholder="Sua senha" required>
                </div>

                <div class="form-group">
                    <div class="flex items-center justify-between">
                        <div class="form-check">
                            <input type="checkbox" id="lembrar" name="lembrar" class="form-check-input">
                            <label for="lembrar" class="form-check-label">Lembrar-me</label>
                        </div>

                        <a href="recuperar_senha.php" class="text-sm text-primary hover:underline">Esqueceu a senha?</a>
                    </div>
                </div>

                <div class="form-group">
                    <div class="g-recaptcha" data-sitekey="6Ld41jIrAAAAAKu0A7zqJMjmx2lT08VO6yQfVriu"></div>
                </div>

                <button type="submit" class="btn-primary">Entrar</button>
            </form>

            <div class="login-footer">
                <p>&copy; <?php echo date('Y'); ?> Faciência ERP. Todos os direitos reservados.</p>
            </div>
        </div>
    </div>
</body>
</html>
