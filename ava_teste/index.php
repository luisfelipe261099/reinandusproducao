<?php
// index.php - Ponto de entrada do sistema
session_start();

// Simulação de login para desenvolvimento (remover em produção)
if (!isset($_SESSION['aluno_id']) && !isset($_GET['login'])) {
    header("Location: login.php");
    exit;
} elseif (isset($_GET['login'])) {
    // Dados fictícios para teste
    $_SESSION['aluno_id'] = 14409;
    $_SESSION['aluno_nome'] = 'Bruno Guilherme Souza';
    $_SESSION['aluno_email'] = 'bruno.gui@gmail.com';
    $_SESSION['aluno_foto'] = null;
    header("Location: index.php");
    exit;
}

// Função para gerar menu ativo
function menuAtivo($pagina) {
    $atual = basename($_SERVER['PHP_SELF']);
    return $atual == $pagina ? 'active' : '';
}

// Simular dados para front-end
$cursos_aluno = [
    [
        'id' => 1,
        'titulo' => 'Desenvolvimento WEB com PHP',
        'descricao' => 'Aprenda a desenvolver aplicações web completas utilizando PHP, HTML, CSS, JavaScript e MySQL.',
        'imagem' => 'assets/img/cursos/php_web.jpg',
        'categoria' => 'Tecnologia',
        'nivel' => 'intermediario',
        'progresso' => 35,
        'status' => 'ativo'
    ],
    [
        'id' => 2,
        'titulo' => 'Design UX/UI Avançado',
        'descricao' => 'Dominie as técnicas avançadas de UX/UI Design para criar interfaces intuitivas e atraentes.',
        'imagem' => 'assets/img/cursos/uxui.jpg',
        'categoria' => 'Design',
        'nivel' => 'avancado',
        'progresso' => 68,
        'status' => 'ativo'
    ]
];

$proximas_aulas = [
    [
        'id' => 5,
        'titulo' => 'Trabalhando com PDO e Banco de Dados',
        'curso' => 'Desenvolvimento WEB com PHP',
        'data_prevista' => '2025-05-18',
        'imagem' => 'assets/img/aulas/pdo.jpg'
    ],
    [
        'id' => 12,
        'titulo' => 'Wireframes de Alta Fidelidade',
        'curso' => 'Design UX/UI Avançado',
        'data_prevista' => '2025-05-17',
        'imagem' => 'assets/img/aulas/wireframes.jpg'
    ]
];

$atividades_pendentes = [
    [
        'id' => 3,
        'titulo' => 'Quiz - Fundamentos de PHP',
        'curso' => 'Desenvolvimento WEB com PHP',
        'prazo' => '2025-05-19',
        'tipo' => 'quiz'
    ],
    [
        'id' => 7,
        'titulo' => 'Projeto - Protótipo de E-commerce',
        'curso' => 'Design UX/UI Avançado',
        'prazo' => '2025-05-22',
        'tipo' => 'projeto'
    ]
];

$certificados = [
    [
        'id' => 1,
        'titulo' => 'HTML5 e CSS3 Fundamental',
        'data_emissao' => '2025-04-10',
        'carga_horaria' => 40
    ]
];

$pagina = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faciência EAD - Ambiente Virtual de Aprendizagem</title>
    
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
            color: var(--dark-color);
        }
        
        .navbar-custom {
            background-color: var(--primary-color);
            box-shadow: var(--box-shadow);
        }
        
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: 280px;
            background-color: white;
            box-shadow: var(--box-shadow);
            z-index: 1000;
            transition: all 0.3s;
            padding-top: 5rem;
        }
        
        .sidebar .nav-link {
            color: var(--dark-color);
            border-radius: 0;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            margin: 0.2rem 0;
        }
        
        .sidebar .nav-link i {
            margin-right: 0.75rem;
            font-size: 1.1rem;
        }
        
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background-color: rgba(106, 90, 205, 0.1);
            color: var(--primary-color);
        }
        
        .sidebar .nav-link.active {
            border-left: 4px solid var(--primary-color);
        }
        
        .main-content {
            margin-left: 280px;
            padding: 2rem;
            min-height: 100vh;
        }
        
        .navbar-brand img {
            height: 40px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid white;
        }
        
        .sidebar-profile {
            padding: 1.5rem;
            text-align: center;
            border-bottom: 1px solid #eee;
            margin-bottom: 1rem;
        }
        
        .sidebar-profile img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .card {
            border: none;
            border-radius: var(--card-border-radius);
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            margin-bottom: 1.5rem;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        
        .card-header {
            background-color: transparent;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.25rem 1.5rem;
            font-weight: 600;
        }
        
        .dashboard-stats .card {
            border-left: 4px solid var(--primary-color);
        }
        
        .dashboard-stats .card-body {
            padding: 1.5rem;
        }
        
        .dashboard-stats .card-title {
            color: #6c757d;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .dashboard-stats .card-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0;
        }
        
        .dashboard-stats .icon {
            font-size: 2.5rem;
            opacity: 0.2;
            position: absolute;
            top: 1rem;
            right: 1rem;
        }
        
        .curso-card .card-img-top {
            height: 180px;
            object-fit: cover;
            border-top-left-radius: var(--card-border-radius);
            border-top-right-radius: var(--card-border-radius);
        }
        
        .curso-card .badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 0.75rem;
            padding: 0.5rem 0.75rem;
        }
        
        .curso-card .card-body {
            padding: 1.5rem;
        }
        
        .curso-card .progress {
            height: 0.5rem;
            border-radius: 1rem;
        }
        
        .aula-card {
            display: flex;
            margin-bottom: 1rem;
            background-color: white;
            border-radius: var(--card-border-radius);
            overflow: hidden;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        
        .aula-card img {
            width: 120px;
            height: 100%;
            object-fit: cover;
        }
        
        .aula-card .content {
            padding: 1rem;
            flex: 1;
        }
        
        .aula-card h5 {
            margin-bottom: 0.25rem;
        }
        
        .aula-card .info {
            color: #6c757d;
            font-size: 0.875rem;
        }
        
        .aula-card .actions {
            display: flex;
            align-items: center;
            padding: 1rem;
        }
        
        .atividade-card {
            display: flex;
            align-items: center;
            padding: 1rem;
            border-radius: var(--card-border-radius);
            background-color: white;
            margin-bottom: 1rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        
        .atividade-card .icon {
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background-color: rgba(106, 90, 205, 0.1);
            color: var(--primary-color);
            margin-right: 1rem;
        }
        
        .atividade-card .icon i {
            font-size: 1.5rem;
        }
        
        .atividade-card .content {
            flex: 1;
        }
        
        .atividade-card h5 {
            margin-bottom: 0.25rem;
        }
        
        .atividade-card .info {
            color: #6c757d;
            font-size: 0.875rem;
        }
        
        .atividade-card .prazo {
            color: var(--danger-color);
            font-weight: 600;
        }
        
        .atividade-card .action {
            padding-left: 1rem;
        }
        
        .certificado-card {
            background-color: #fff;
            border-radius: var(--card-border-radius);
            padding: 1.5rem;
            margin-bottom: 1rem;
            border-left: 4px solid var(--success-color);
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        
        .certificado-card h5 {
            margin-bottom: 0.5rem;
        }
        
        .certificado-card .info {
            color: #6c757d;
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }
        
        .certificado-card .actions {
            display: flex;
            gap: 0.5rem;
        }
        
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .toggle-sidebar {
                display: block;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar superior -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom fixed-top">
        <div class="container-fluid">
            <button class="btn btn-link text-white d-lg-none me-2 toggle-sidebar" type="button">
                <i class="fas fa-bars"></i>
            </button>
            
            <a class="navbar-brand me-auto" href="index.php">
                <img src="assets/img/logo-faciencia-white.png" alt="Logo Faciência EAD">
            </a>
            
            <div class="d-flex align-items-center">
                <div class="dropdown">
                    <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-bell me-2"></i>
                        <span class="badge bg-danger rounded-pill">3</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end">
                        <h6 class="dropdown-header">Notificações</h6>
                        <a class="dropdown-item" href="#">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-book-open text-primary"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <p class="mb-0 fw-bold">Nova aula disponível</p>
                                    <p class="small text-muted mb-0">Desenvolvimento WEB com PHP</p>
                                    <p class="small text-muted mb-0">3 horas atrás</p>
                                </div>
                            </div>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="#">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-exclamation-circle text-warning"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <p class="mb-0 fw-bold">Prazo de atividade se aproximando</p>
                                    <p class="small text-muted mb-0">Quiz - Fundamentos de PHP</p>
                                    <p class="small text-muted mb-0">5 horas atrás</p>
                                </div>
                            </div>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item text-center small text-muted" href="#">Mostrar todas as notificações</a>
                    </div>
                </div>
                
                <div class="dropdown ms-3">
                    <a class="nav-link dropdown-toggle text-white d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                        <?php if ($_SESSION['aluno_foto']): ?>
                            <img src="<?= $_SESSION['aluno_foto'] ?>" class="user-avatar me-2" alt="Avatar">
                        <?php else: ?>
                            <div class="user-avatar me-2 bg-primary d-flex align-items-center justify-content-center">
                                <span class="text-white"><?= substr($_SESSION['aluno_nome'], 0, 1) ?></span>
                            </div>
                        <?php endif; ?>
                        <span class="d-none d-md-inline"><?= $_SESSION['aluno_nome'] ?></span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end">
                        <h6 class="dropdown-header">Olá, <?= explode(' ', $_SESSION['aluno_nome'])[0] ?>!</h6>
                        <a class="dropdown-item" href="perfil.php">
                            <i class="fas fa-user-circle me-2"></i> Meu Perfil
                        </a>
                        <a class="dropdown-item" href="configuracoes.php">
                            <i class="fas fa-cog me-2"></i> Configurações
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i> Sair
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Sidebar lateral -->
    <div class="sidebar">
        <div class="sidebar-profile">
            <?php if ($_SESSION['aluno_foto']): ?>
                <img src="<?= $_SESSION['aluno_foto'] ?>" alt="Avatar">
            <?php else: ?>
                <div style="width: 80px; height: 80px;" class="rounded-circle bg-primary d-flex align-items-center justify-content-center mx-auto">
                    <span class="text-white fs-1"><?= substr($_SESSION['aluno_nome'], 0, 1) ?></span>
                </div>
            <?php endif; ?>
            <h5><?= explode(' ', $_SESSION['aluno_nome'])[0] ?></h5>
            <p class="text-muted mb-0"><?= $_SESSION['aluno_email'] ?></p>
        </div>
        
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= menuAtivo('index.php') ?>" href="index.php">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= menuAtivo('meus-cursos.php') ?>" href="meus-cursos.php">
                    <i class="fas fa-graduation-cap"></i> Meus Cursos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= menuAtivo('calendario.php') ?>" href="calendario.php">
                    <i class="fas fa-calendar-alt"></i> Calendário
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= menuAtivo('atividades.php') ?>" href="atividades.php">
                    <i class="fas fa-tasks"></i> Atividades
                    <span class="badge bg-danger ms-auto">2</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= menuAtivo('notas.php') ?>" href="notas.php">
                    <i class="fas fa-chart-line"></i> Notas e Progresso
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= menuAtivo('mensagens.php') ?>" href="mensagens.php">
                    <i class="fas fa-comments"></i> Mensagens
                    <span class="badge bg-primary ms-auto">5</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= menuAtivo('biblioteca.php') ?>" href="biblioteca.php">
                    <i class="fas fa-book"></i> Biblioteca
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= menuAtivo('certificados.php') ?>" href="certificados.php">
                    <i class="fas fa-certificate"></i> Certificados
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= menuAtivo('suporte.php') ?>" href="suporte.php">
                    <i class="fas fa-headset"></i> Suporte
                </a>
            </li>
        </ul>
    </div>

    <!-- Conteúdo principal -->
    <div class="main-content">
        <?php if ($pagina == 'dashboard'): ?>
            <!-- Dashboard -->
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3">Dashboard</h1>
                    <div class="d-flex">
                        <button class="btn btn-sm btn-outline-primary me-2">
                            <i class="fas fa-sync-alt me-1"></i> Atualizar
                        </button>
                        <button class="btn btn-sm btn-primary">
                            <i class="fas fa-calendar-alt me-1"></i> Maio 2025
                        </button>
                    </div>
                </div>
                
                <!-- Estatísticas -->
                <div class="row dashboard-stats mb-4">
                    <div class="col-md-3 col-sm-6">
                        <div class="card">
                            <div class="card-body position-relative">
                                <h5 class="card-title">Cursos Ativos</h5>
                                <h2 class="card-value"><?= count($cursos_aluno) ?></h2>
                                <i class="fas fa-graduation-cap icon"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="card">
                            <div class="card-body position-relative">
                                <h5 class="card-title">Progresso Médio</h5>
                                <h2 class="card-value">51<small>%</small></h2>
                                <i class="fas fa-chart-pie icon"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="card">
                            <div class="card-body position-relative">
                                <h5 class="card-title">Atividades Pendentes</h5>
                                <h2 class="card-value"><?= count($atividades_pendentes) ?></h2>
                                <i class="fas fa-tasks icon"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="card">
                            <div class="card-body position-relative">
                                <h5 class="card-title">Certificados</h5>
                                <h2 class="card-value"><?= count($certificados) ?></h2>
                                <i class="fas fa-certificate icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Cursos em Andamento -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Meus Cursos</h5>
                        <a href="meus-cursos.php" class="btn btn-sm btn-primary">Ver Todos</a>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($cursos_aluno as $curso): ?>
                                <div class="col-lg-6 col-xl-4 mb-4">
                                    <div class="card curso-card h-100">
                                        <img src="<?= $curso['imagem'] ?>" class="card-img-top" alt="<?= $curso['titulo'] ?>">
                                        <span class="badge bg-<?= $curso['nivel'] == 'basico' ? 'success' : ($curso['nivel'] == 'intermediario' ? 'warning' : 'danger') ?>">
                                            <?= ucfirst($curso['nivel']) ?>
                                        </span>
                                        <div class="card-body d-flex flex-column">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <small class="text-muted"><?= $curso['categoria'] ?></small>
                                                <span class="badge bg-primary"><?= $curso['progresso'] ?>% concluído</span>
                                            </div>
                                            <h5 class="card-title"><?= $curso['titulo'] ?></h5>
                                            <p class="card-text text-muted small"><?= substr($curso['descricao'], 0, 100) ?>...</p>
                                            <div class="mt-auto">
                                                <div class="progress mb-3">
                                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?= $curso['progresso'] ?>%" aria-valuenow="<?= $curso['progresso'] ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                                <div class="d-flex justify-content-between">
                                                    <a href="curso.php?id=<?= $curso['id'] ?>" class="btn btn-outline-primary btn-sm">Detalhes</a>
                                                    <a href="curso.php?id=<?= $curso['id'] ?>&continuar=1" class="btn btn-primary btn-sm">Continuar</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <!-- Próximas Aulas -->
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Próximas Aulas</h5>
                            </div>
                            <div class="card-body p-0">
                                <?php foreach ($proximas_aulas as $aula): ?>
                                    <div class="aula-card">
                                        <img src="<?= $aula['imagem'] ?>" alt="<?= $aula['titulo'] ?>">
                                        <div class="content">
                                            <h5><?= $aula['titulo'] ?></h5>
                                            <div class="info">
                                                <div><i class="fas fa-graduation-cap me-1"></i> <?= $aula['curso'] ?></div>
                                                <div><i class="fas fa-calendar-alt me-1"></i> <?= date('d/m/Y', strtotime($aula['data_prevista'])) ?></div>
                                            </div>
                                        </div>
                                        <div class="actions">
                                            <a href="aula.php?id=<?= $aula['id'] ?>" class="btn btn-primary btn-sm">
                                                <i class="fas fa-play me-1"></i> Assistir
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                <div class="p-3 text-center">
                                    <a href="calendario.php" class="btn btn-link">Ver Calendário Completo</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Atividades Pendentes -->
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Atividades Pendentes</h5>
                            </div>
                            <div class="card-body p-0">
                                <?php foreach ($atividades_pendentes as $atividade): ?>
                                    <div class="atividade-card">
                                        <div class="icon">
                                            <i class="fas fa-<?= $atividade['tipo'] == 'quiz' ? 'question-circle' : 'file-alt' ?>"></i>
                                        </div>
                                        <div class="content">
                                            <h5><?= $atividade['titulo'] ?></h5>
                                            <div class="info">
                                                <div><i class="fas fa-graduation-cap me-1"></i> <?= $atividade['curso'] ?></div>
                                                <div class="prazo"><i class="fas fa-clock me-1"></i> Prazo: <?= date('d/m/Y', strtotime($atividade['prazo'])) ?></div>
                                            </div>
                                        </div>
                                        <div class="action">
                                            <a href="atividade.php?id=<?= $atividade['id'] ?>" class="btn btn-primary btn-sm">
                                                <?= $atividade['tipo'] == 'quiz' ? 'Realizar' : 'Entregar' ?>
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                <div class="p-3 text-center">
                                    <a href="atividades.php" class="btn btn-link">Ver Todas as Atividades</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Certificados -->
                <div class="card mt-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Meus Certificados</h5>
                        <a href="certificados.php" class="btn btn-sm btn-primary">Ver Todos</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($certificados)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-certificate fa-3x text-muted mb-3"></i>
                                <p class="mb-0">Você ainda não possui certificados. Continue estudando para obter seu primeiro certificado!</p>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($certificados as $certificado): ?>
                                    <div class="col-md-6">
                                        <div class="certificado-card">
                                            <h5><?= $certificado['titulo'] ?></h5>
                                            <div class="info">
                                                <p><i class="fas fa-calendar-alt me-1"></i> Emitido em: <?= date('d/m/Y', strtotime($certificado['data_emissao'])) ?></p>
                                                <p><i class="fas fa-clock me-1"></i> Carga horária: <?= $certificado['carga_horaria'] ?> horas</p>
                                            </div>
                                            <div class="actions">
                                                <a href="certificado_download.php?id=<?= $certificado['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-download me-1"></i> Download
                                                </a>
                                                <a href="certificado_visualizar.php?id=<?= $certificado['id'] ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-eye me-1"></i> Visualizar
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- PÁGINA DE MEUS CURSOS (simulada aqui para demonstração) -->
        <?php if ($pagina == 'meus-cursos'): ?>
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3">Meus Cursos</h1>
                    <div class="d-flex">
                        <div class="dropdown me-2">
                            <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-filter me-1"></i> Filtrar
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#">Todos os Cursos</a></li>
                                <li><a class="dropdown-item" href="#">Em Andamento</a></li>
                                <li><a class="dropdown-item" href="#">Concluídos</a></li>
                                <li><a class="dropdown-item" href="#">Não Iniciados</a></li>
                            </ul>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-sort me-1"></i> Ordenar
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#">Data de Matrícula</a></li>
                                <li><a class="dropdown-item" href="#">Progresso</a></li>
                                <li><a class="dropdown-item" href="#">Alfabética</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <!-- Cursos em cards -->
                <div class="row">
                    <?php foreach ($cursos_aluno as $curso): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card curso-card h-100">
                                <img src="<?= $curso['imagem'] ?>" class="card-img-top" alt="<?= $curso['titulo'] ?>">
                                <span class="badge bg-<?= $curso['nivel'] == 'basico' ? 'success' : ($curso['nivel'] == 'intermediario' ? 'warning' : 'danger') ?>">
                                    <?= ucfirst($curso['nivel']) ?>
                                </span>
                                <div class="card-body d-flex flex-column">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <small class="text-muted"><?= $curso['categoria'] ?></small>
                                        <span class="badge bg-primary"><?= $curso['progresso'] ?>% concluído</span>
                                    </div>
                                    <h5 class="card-title"><?= $curso['titulo'] ?></h5>
                                    <p class="card-text text-muted"><?= $curso['descricao'] ?></p>
                                    <div class="mt-auto">
                                        <div class="progress mb-3">
                                            <div class="progress-bar bg-success" role="progressbar" style="width: <?= $curso['progresso'] ?>%" aria-valuenow="<?= $curso['progresso'] ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <a href="curso.php?id=<?= $curso['id'] ?>" class="btn btn-outline-primary">Detalhes</a>
                                            <a href="curso.php?id=<?= $curso['id'] ?>&continuar=1" class="btn btn-primary">Continuar</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- PÁGINA DE VISUALIZAÇÃO DE CURSO (simulada aqui para demonstração) -->
        <?php if ($pagina == 'curso'): ?>
            <?php 
            // Simular dados do curso
            $curso = [
                'id' => 1,
                'titulo' => 'Desenvolvimento WEB com PHP',
                'descricao' => 'Aprenda a desenvolver aplicações web completas utilizando PHP, HTML, CSS, JavaScript e MySQL. Este curso aborda desde os conceitos básicos até técnicas avançadas de desenvolvimento web.',
                'imagem' => 'assets/img/cursos/php_web.jpg',
                'categoria' => 'Tecnologia',
                'nivel' => 'intermediario',
                'carga_horaria' => 80,
                'professor' => 'Pedro Almeida',
                'progresso' => 35,
                'status' => 'ativo'
            ];
            
            // Simular dados de módulos e aulas
            $modulos = [
                [
                    'id' => 1,
                    'titulo' => 'Introdução ao PHP',
                    'descricao' => 'Fundamentos da linguagem PHP e ambiente de desenvolvimento.',
                    'progresso' => 100,
                    'aulas' => [
                        [
                            'id' => 1,
                            'titulo' => 'Introdução ao Desenvolvimento Web',
                            'tipo' => 'video',
                            'duracao' => 15,
                            'status' => 'concluida'
                        ],
                        [
                            'id' => 2,
                            'titulo' => 'Configurando o Ambiente de Desenvolvimento',
                            'tipo' => 'video',
                            'duracao' => 20,
                            'status' => 'concluida'
                        ],
                        [
                            'id' => 3,
                            'titulo' => 'Primeiros Passos com PHP',
                            'tipo' => 'video',
                            'duracao' => 25,
                            'status' => 'concluida'
                        ],
                        [
                            'id' => 4,
                            'titulo' => 'Quiz - Fundamentos de PHP',
                            'tipo' => 'quiz',
                            'status' => 'pendente'
                        ]
                    ]
                ],
                [
                    'id' => 2,
                    'titulo' => 'PHP e Banco de Dados',
                    'descricao' => 'Trabalhando com bancos de dados MySQL usando PHP.',
                    'progresso' => 0,
                    'aulas' => [
                        [
                            'id' => 5,
                            'titulo' => 'Introdução a Bancos de Dados',
                            'tipo' => 'video',
                            'duracao' => 20,
                            'status' => 'bloqueada'
                        ],
                        [
                            'id' => 6,
                            'titulo' => 'Trabalhando com PDO e Banco de Dados',
                            'tipo' => 'video',
                            'duracao' => 30,
                            'status' => 'bloqueada'
                        ],
                        [
                            'id' => 7,
                            'titulo' => 'CRUD com PHP e MySQL',
                            'tipo' => 'video',
                            'duracao' => 40,
                            'status' => 'bloqueada'
                        ],
                        [
                            'id' => 8,
                            'titulo' => 'Projeto - Sistema de Cadastro',
                            'tipo' => 'projeto',
                            'status' => 'bloqueada'
                        ]
                    ]
                ]
            ];
            ?>
            
            <div class="container-fluid">
                <!-- Cabeçalho do curso -->
                <div class="card mb-4">
                    <div class="row g-0">
                        <div class="col-md-4">
                            <img src="<?= $curso['imagem'] ?>" class="img-fluid rounded-start h-100" style="object-fit: cover;" alt="<?= $curso['titulo'] ?>">
                        </div>
                        <div class="col-md-8">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <span class="badge bg-<?= $curso['nivel'] == 'basico' ? 'success' : ($curso['nivel'] == 'intermediario' ? 'warning' : 'danger') ?> mb-2"><?= ucfirst($curso['nivel']) ?></span>
                                        <span class="badge bg-primary mb-2 ms-2"><?= $curso['categoria'] ?></span>
                                        <h1 class="card-title h3"><?= $curso['titulo'] ?></h1>
                                        <p class="text-muted mb-3"><i class="fas fa-user-tie me-1"></i> Professor: <?= $curso['professor'] ?></p>
                                    </div>
                                    <div class="text-end">
                                        <p class="h4 mb-1"><?= $curso['progresso'] ?>%</p>
                                        <p class="text-muted">completo</p>
                                    </div>
                                </div>
                                
                                <p class="card-text"><?= $curso['descricao'] ?></p>
                                
                                <div class="row mb-3">
                                    <div class="col-sm-4">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-clock text-primary me-2 fa-2x"></i>
                                            <div>
                                                <small class="text-muted d-block">Carga Horária</small>
                                                <span><?= $curso['carga_horaria'] ?> horas</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-4">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-list-ol text-primary me-2 fa-2x"></i>
                                            <div>
                                                <small class="text-muted d-block">Módulos</small>
                                                <span><?= count($modulos) ?> módulos</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-4">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-play-circle text-primary me-2 fa-2x"></i>
                                            <div>
                                                <small class="text-muted d-block">Aulas</small>
                                                <span>8 aulas</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="progress mb-3" style="height: 10px;">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?= $curso['progresso'] ?>%" aria-valuenow="<?= $curso['progresso'] ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                
                                <div class="d-flex gap-2">
                                    <a href="aula.php?id=3" class="btn btn-primary">
                                        <i class="fas fa-play-circle me-1"></i> Continuar Estudando
                                    </a>
                                    <button class="btn btn-outline-primary">
                                        <i class="fas fa-file-alt me-1"></i> Material de Apoio
                                    </button>
                                    <button class="btn btn-outline-primary">
                                        <i class="fas fa-bullhorn me-1"></i> Fórum de Discussão
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Conteúdo do curso (módulos e aulas) -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Conteúdo do Curso</h5>
                    </div>
                    <div class="card-body">
                        <div class="accordion" id="accordionModulos">
                            <?php foreach ($modulos as $index => $modulo): ?>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="heading<?= $modulo['id'] ?>">
                                        <button class="accordion-button <?= $index > 0 ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $modulo['id'] ?>" aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>" aria-controls="collapse<?= $modulo['id'] ?>">
                                            <div class="d-flex justify-content-between align-items-center w-100 me-3">
                                                <div>
                                                    <span class="badge bg-primary me-2"><?= $index + 1 ?></span>
                                                    <?= $modulo['titulo'] ?>
                                                </div>
                                                <div class="d-flex align-items-center">
                                                    <div class="me-3">
                                                        <small class="text-muted"><?= count($modulo['aulas']) ?> aulas</small>
                                                    </div>
                                                    <div class="progress" style="width: 100px; height: 8px;">
                                                        <div class="progress-bar bg-success" role="progressbar" style="width: <?= $modulo['progresso'] ?>%" aria-valuenow="<?= $modulo['progresso'] ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                    </div>
                                                    <small class="text-muted ms-2"><?= $modulo['progresso'] ?>%</small>
                                                </div>
                                            </div>
                                        </button>
                                    </h2>
                                    <div id="collapse<?= $modulo['id'] ?>" class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" aria-labelledby="heading<?= $modulo['id'] ?>" data-bs-parent="#accordionModulos">
                                        <div class="accordion-body p-0">
                                            <div class="list-group list-group-flush">
                                                <?php foreach ($modulo['aulas'] as $aula): ?>
                                                    <a href="<?= $aula['status'] == 'bloqueada' ? '#' : 'aula.php?id=' . $aula['id'] ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?= $aula['status'] == 'bloqueada' ? 'disabled' : '' ?>">
                                                        <div class="d-flex align-items-center">
                                                            <div class="aula-status-icon me-3">
                                                                <?php if ($aula['status'] == 'concluida'): ?>
                                                                    <i class="fas fa-check-circle text-success fa-lg"></i>
                                                                <?php elseif ($aula['status'] == 'pendente'): ?>
                                                                    <i class="fas fa-play-circle text-primary fa-lg"></i>
                                                                <?php else: ?>
                                                                    <i class="fas fa-lock text-muted fa-lg"></i>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div>
                                                                <h6 class="mb-0"><?= $aula['titulo'] ?></h6>
                                                                <small class="text-muted">
                                                                    <?php if ($aula['tipo'] == 'video'): ?>
                                                                        <i class="fas fa-video me-1"></i> Vídeo • <?= $aula['duracao'] ?> min
                                                                    <?php elseif ($aula['tipo'] == 'quiz'): ?>
                                                                        <i class="fas fa-question-circle me-1"></i> Quiz
                                                                    <?php else: ?>
                                                                        <i class="fas fa-project-diagram me-1"></i> Projeto
                                                                    <?php endif; ?>
                                                                </small>
                                                            </div>
                                                        </div>
                                                        <?php if ($aula['status'] == 'pendente'): ?>
                                                            <span class="badge bg-warning rounded-pill">Pendente</span>
                                                        <?php elseif ($aula['status'] == 'bloqueada'): ?>
                                                            <span class="badge bg-secondary rounded-pill">Bloqueada</span>
                                                        <?php endif; ?>
                                                    </a>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- PÁGINA DE VISUALIZAÇÃO DE AULA (simulada aqui para demonstração) -->
        <?php if ($pagina == 'aula'): ?>
            <?php 
            // Simular dados da aula
            $aula = [
                'id' => 3,
                'titulo' => 'Primeiros Passos com PHP',
                'descricao' => 'Nesta aula, você aprenderá os fundamentos básicos da linguagem PHP, como variáveis, tipos de dados, operadores e estruturas de controle.',
                'tipo' => 'video',
                'video_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
                'modulo_id' => 1,
                'modulo_titulo' => 'Introdução ao PHP',
                'curso_id' => 1,
                'curso_titulo' => 'Desenvolvimento WEB com PHP'
            ];
            
            // Simular material de apoio
            $materiais = [
                [
                    'id' => 1,
                    'titulo' => 'Slides da Aula',
                    'descricao' => 'Apresentação utilizada durante a aula.',
                    'tipo' => 'pdf',
                    'link_url' => '#'
                ],
                [
                    'id' => 2,
                    'titulo' => 'Código-fonte dos Exemplos',
                    'descricao' => 'Arquivos PHP com os exemplos mostrados durante a aula.',
                    'tipo' => 'zip',
                    'link_url' => '#'
                ]
            ];
            
            // Simular questões
            $questoes = [
                [
                    'id' => 1,
                    'pergunta' => 'Qual é a tag de abertura padrão do PHP?',
                    'tipo' => 'multipla_escolha',
                    'opcoes' => ['<?', '<?php', '<script>', '<php>'],
                    'resposta_correta' => '<?php',
                    'explicacao' => 'A tag de abertura padrão do PHP é <?php. Embora a tag curta <? também possa ser usada em algumas configurações, a tag completa é recomendada para melhor compatibilidade.'
                ],
                [
                    'id' => 2,
                    'pergunta' => 'Qual símbolo é utilizado para declarar uma variável em PHP?',
                    'tipo' => 'multipla_escolha',
                    'opcoes' => ['$', '#', '@', '&'],
                    'resposta_correta' => '$',
                    'explicacao' => 'Em PHP, todas as variáveis começam com o símbolo $ seguido pelo nome da variável.'
                ]
            ];
            ?>
            
            <div class="container-fluid">
                <!-- Trilha de navegação -->
                <nav aria-label="breadcrumb" class="mb-4">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="meus-cursos.php">Meus Cursos</a></li>
                        <li class="breadcrumb-item"><a href="curso.php?id=<?= $aula['curso_id'] ?>"><?= $aula['curso_titulo'] ?></a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?= $aula['titulo'] ?></li>
                    </ol>
                </nav>
                
                <div class="row">
                    <div class="col-lg-8">
                        <!-- Conteúdo da aula -->
                        <div class="card mb-4">
                            <div class="card-body p-0">
                                <?php if ($aula['tipo'] == 'video'): ?>
                                    <div class="ratio ratio-16x9">
                                        <iframe src="<?= $aula['video_url'] ?>" title="<?= $aula['titulo'] ?>" allowfullscreen></iframe>
                                    </div>
                                <?php endif; ?>
                                <div class="p-4">
                                    <h2 class="h4 mb-3"><?= $aula['titulo'] ?></h2>
                                    <p class="text-muted">
                                        <i class="fas fa-layer-group me-1"></i> <?= $aula['modulo_titulo'] ?> • 
                                        <i class="fas fa-graduation-cap me-1"></i> <?= $aula['curso_titulo'] ?>
                                    </p>
                                    <p><?= $aula['descricao'] ?></p>
                                    
                                    <div class="d-flex gap-2 mt-4">
                                        <button class="btn btn-outline-primary">
                                            <i class="fas fa-step-backward me-1"></i> Aula Anterior
                                        </button>
                                        <button class="btn btn-primary">
                                            Marcar como Concluída <i class="fas fa-check ms-1"></i>
                                        </button>
                                        <button class="btn btn-outline-primary">
                                            Próxima Aula <i class="fas fa-step-forward ms-1"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Quiz da aula -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Quiz - Avalie seu Conhecimento</h5>
                            </div>
                            <div class="card-body">
                                <form>
                                    <?php foreach ($questoes as $index => $questao): ?>
                                        <div class="mb-4 questao">
                                            <h5 class="mb-3">
                                                <span class="badge bg-primary me-2"><?= $index + 1 ?></span>
                                                <?= $questao['pergunta'] ?>
                                            </h5>
                                            
                                            <?php if ($questao['tipo'] == 'multipla_escolha'): ?>
                                                <div class="opcoes">
                                                    <?php foreach ($questao['opcoes'] as $opcaoIndex => $opcao): ?>
                                                        <div class="form-check mb-2">
                                                            <input class="form-check-input" type="radio" name="questao_<?= $questao['id'] ?>" id="opcao_<?= $questao['id'] ?>_<?= $opcaoIndex ?>" value="<?= $opcao ?>">
                                                            <label class="form-check-label" for="opcao_<?= $questao['id'] ?>_<?= $opcaoIndex ?>">
                                                                <?= $opcao ?>
                                                            </label>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                    
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-check-circle me-1"></i> Enviar Respostas
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <!-- Material de apoio -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Material de Apoio</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($materiais)): ?>
                                    <p class="text-muted text-center">Nenhum material disponível para esta aula.</p>
                                <?php else: ?>
                                    <div class="list-group">
                                        <?php foreach ($materiais as $material): ?>
                                            <a href="<?= $material['link_url'] ?>" class="list-group-item list-group-item-action d-flex align-items-center" target="_blank">
                                                <div class="material-icon me-3">
                                                    <?php if ($material['tipo'] == 'pdf'): ?>
                                                        <i class="fas fa-file-pdf text-danger fa-2x"></i>
                                                    <?php elseif ($material['tipo'] == 'zip'): ?>
                                                        <i class="fas fa-file-archive text-warning fa-2x"></i>
                                                    <?php elseif ($material['tipo'] == 'doc'): ?>
                                                        <i class="fas fa-file-word text-primary fa-2x"></i>
                                                    <?php elseif ($material['tipo'] == 'ppt'): ?>
                                                        <i class="fas fa-file-powerpoint text-danger fa-2x"></i>
                                                    <?php elseif ($material['tipo'] == 'xls'): ?>
                                                        <i class="fas fa-file-excel text-success fa-2x"></i>
                                                    <?php else: ?>
                                                        <i class="fas fa-file text-secondary fa-2x"></i>
                                                    <?php endif; ?>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0"><?= $material['titulo'] ?></h6>
                                                    <small class="text-muted"><?= $material['descricao'] ?></small>
                                                </div>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Próximas aulas -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Próximas Aulas</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="list-group list-group-flush">
                                    <a href="#" class="list-group-item list-group-item-action d-flex align-items-center p-3 active">
                                        <div class="me-3">
                                            <i class="fas fa-play-circle fa-lg"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0">Primeiros Passos com PHP</h6>
                                            <small><i class="fas fa-video me-1"></i> Aula Atual</small>
                                        </div>
                                    </a>
                                    <a href="#" class="list-group-item list-group-item-action d-flex align-items-center p-3">
                                        <div class="me-3">
                                            <i class="fas fa-question-circle text-warning fa-lg"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0">Quiz - Fundamentos de PHP</h6>
                                            <small><i class="fas fa-question-circle me-1"></i> Quiz</small>
                                        </div>
                                    </a>
                                    <a href="#" class="list-group-item list-group-item-action d-flex align-items-center p-3 disabled">
                                        <div class="me-3">
                                            <i class="fas fa-lock text-muted fa-lg"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0">Introdução a Bancos de Dados</h6>
                                            <small><i class="fas fa-video me-1"></i> Vídeo • 20 min</small>
                                        </div>
                                        <span class="badge bg-secondary ms-auto">Bloqueada</span>
                                    </a>
                                    <a href="#" class="list-group-item list-group-item-action d-flex align-items-center p-3 disabled">
                                        <div class="me-3">
                                            <i class="fas fa-lock text-muted fa-lg"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0">Trabalhando com PDO e Banco de Dados</h6>
                                            <small><i class="fas fa-video me-1"></i> Vídeo • 30 min</small>
                                        </div>
                                        <span class="badge bg-secondary ms-auto">Bloqueada</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Fórum de discussão -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Fórum de Discussão</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="comentario" class="form-label">Tem alguma dúvida sobre esta aula?</label>
                                    <textarea class="form-control" id="comentario" rows="3" placeholder="Escreva sua dúvida ou comentário..."></textarea>
                                </div>
                                <button class="btn btn-primary">Publicar</button>
                                
                                <hr>
                                
                                <div class="comentarios">
                                    <div class="comentario d-flex mb-3">
                                        <div class="flex-shrink-0 me-3">
                                            <div class="avatar bg-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                <span class="text-white">M</span>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="d-flex align-items-center mb-1">
                                                <h6 class="mb-0">Maria Silva</h6>
                                                <small class="text-muted ms-2">há 2 dias</small>
                                            </div>
                                            <p class="mb-1">Como faço para incluir um arquivo PHP em outro?</p>
                                            <div class="d-flex gap-2">
                                                <button class="btn btn-sm btn-link text-muted p-0">Responder</button>
                                                <button class="btn btn-sm btn-link text-muted p-0">
                                                    <i class="fas fa-thumbs-up me-1"></i> 3
                                                </button>
                                            </div>
                                            
                                            <!-- Resposta -->
                                            <div class="comentario resposta d-flex mt-3">
                                                <div class="flex-shrink-0 me-3">
                                                    <div class="avatar bg-success rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                        <span class="text-white">P</span>
                                                    </div>
                                                </div>
                                                <div>
                                                    <div class="d-flex align-items-center mb-1">
                                                        <h6 class="mb-0">Pedro Almeida</h6>
                                                        <small class="badge bg-primary ms-2">Professor</small>
                                                        <small class="text-muted ms-2">há 1 dia</small>
                                                    </div>
                                                    <p class="mb-1">Olá Maria! Você pode usar as funções include(), require(), include_once() ou require_once() para incluir um arquivo PHP em outro. Exemplo: include('arquivo.php');</p>
                                                    <div class="d-flex gap-2">
                                                        <button class="btn btn-sm btn-link text-muted p-0">Responder</button>
                                                        <button class="btn btn-sm btn-link text-muted p-0">
                                                            <i class="fas fa-thumbs-up me-1"></i> 5
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="comentario d-flex">
                                        <div class="flex-shrink-0 me-3">
                                            <div class="avatar bg-info rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                <span class="text-white">J</span>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="d-flex align-items-center mb-1">
                                                <h6 class="mb-0">João Santos</h6>
                                                <small class="text-muted ms-2">há 5 horas</small>
                                            </div>
                                            <p class="mb-1">Excelente aula! Consegui entender perfeitamente os conceitos básicos do PHP.</p>
                                            <div class="d-flex gap-2">
                                                <button class="btn btn-sm btn-link text-muted p-0">Responder</button>
                                                <button class="btn btn-sm btn-link text-muted p-0">
                                                    <i class="fas fa-thumbs-up me-1"></i> 2
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Rodapé -->
    <footer class="bg-light py-4 mt-5" style="margin-left: 280px;">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0 text-muted">&copy; 2025 Faciência EAD. Todos os direitos reservados.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="termos.php" class="text-muted me-3">Termos de Uso</a>
                    <a href="privacidade.php" class="text-muted me-3">Política de Privacidade</a>
                    <a href="suporte.php" class="text-muted">Suporte</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- JS Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- JS personalizado -->
    <script>
        // Toggle da sidebar em dispositivos móveis
        document.addEventListener('DOMContentLoaded', function() {
            const toggleButton = document.querySelector('.toggle-sidebar');
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');
            const footer = document.querySelector('footer');
            
            if (toggleButton) {
                toggleButton.addEventListener('click', function() {
                    sidebar.classList.toggle('show');
                });
            }
            
            // Ajustar layout para dispositivos móveis
            function adjustLayout() {
                if (window.innerWidth < 992) {
                    mainContent.style.marginLeft = '0';
                    footer.style.marginLeft = '0';
                } else {
                    mainContent.style.marginLeft = '280px';
                    footer.style.marginLeft = '280px';
                    sidebar.classList.remove('show');
                }
            }
            
            // Verificar tamanho da tela ao carregar e redimensionar
            adjustLayout();
            window.addEventListener('resize', adjustLayout);
        });
    </script>
</body>
</html>
                