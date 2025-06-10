<?php
require_once 'includes/init.php';
exigirAcessoAdministrador();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Módulos do Sistema - Faciência ERP</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/admin.css" rel="stylesheet">
    <style>
        .modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .module-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .module-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }

        .module-header {
            padding: 20px;
            color: white;
            text-align: center;
        }

        .module-header i {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .module-header h3 {
            margin: 0;
            font-size: 1.4rem;
        }

        .module-body {
            padding: 20px;
        }

        .module-description {
            color: #666;
            margin-bottom: 15px;
            line-height: 1.5;
        }

        .module-features {
            list-style: none;
            padding: 0;
            margin: 0 0 20px 0;
        }

        .module-features li {
            padding: 5px 0;
            color: #555;
            font-size: 0.9rem;
        }

        .module-features li i {
            color: #dc3545;
            margin-right: 8px;
            width: 15px;
        }

        .module-actions {
            border-top: 1px solid #eee;
            padding-top: 15px;
            display: flex;
            gap: 10px;
        }

        .btn-module {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            text-align: center;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #dc3545;
            color: white;
        }

        .btn-primary:hover {
            background: #c82333;
            color: white;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            color: white;
        }

        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
            margin-bottom: 10px;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-maintenance {
            background: #fff3cd;
            color: #856404;
        }

        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }

        /* Cores específicas para cada módulo */
        .admin-header { background: linear-gradient(135deg, #dc3545, #c82333); }
        .financial-header { background: linear-gradient(135deg, #28a745, #20c997); }
        .secretary-header { background: linear-gradient(135deg, #007bff, #6610f2); }
        .student-header { background: linear-gradient(135deg, #fd7e14, #e83e8c); }
        .ava-header { background: linear-gradient(135deg, #20c997, #17a2b8); }
        .polo-header { background: linear-gradient(135deg, #6f42c1, #e83e8c); }
        .reports-header { background: linear-gradient(135deg, #6c757d, #495057); }    </style>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex flex-col">
        <!-- ================================================================== -->
        <!-- HEADER ADMINISTRATIVO -->
        <!-- ================================================================== -->
        <header class="admin-nav text-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center">
                        <i class="fas fa-th-large text-2xl mr-3"></i>
                        <h1 class="text-xl font-bold">Módulos do Sistema</h1>
                    </div>
                    
                    <nav class="hidden md:flex space-x-1">
                        <a href="index.php" class="admin-nav-item">
                            <i class="fas fa-chart-line mr-2"></i>Dashboard
                        </a>
                        <a href="usuarios.php" class="admin-nav-item">
                            <i class="fas fa-users mr-2"></i>Usuários
                        </a>
                        <a href="logs.php" class="admin-nav-item">
                            <i class="fas fa-file-alt mr-2"></i>Logs
                        </a>
                        <a href="configuracoes.php" class="admin-nav-item">
                            <i class="fas fa-cogs mr-2"></i>Configurações
                        </a>
                        <a href="modulos.php" class="admin-nav-item active">
                            <i class="fas fa-th-large mr-2"></i>Módulos
                        </a>
                    </nav>
                    <div class="flex items-center space-x-4">
                        <div class="text-sm">
                            <span class="opacity-75">Logado como:</span>
                            <span class="font-semibold"><?php echo htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['user']['nome'] ?? 'Usuário'); ?></span>
                        </div>
                        <a href="../logout.php" class="admin-nav-item">
                            <i class="fas fa-sign-out-alt mr-2"></i>Sair
                        </a>
                    </div>
                </div>
                
                <!-- Menu móvel -->
                <div class="md:hidden">
                    <nav class="flex flex-wrap gap-2 pb-4">
                        <a href="index.php" class="admin-nav-item text-sm">Dashboard</a>
                        <a href="usuarios.php" class="admin-nav-item text-sm">Usuários</a>
                        <a href="logs.php" class="admin-nav-item text-sm">Logs</a>
                        <a href="configuracoes.php" class="admin-nav-item text-sm">Config</a>
                        <a href="modulos.php" class="admin-nav-item active text-sm">Módulos</a>
                    </nav>
                </div>
            </div>
        </header>

        <!-- ================================================================== -->
        <!-- CONTEÚDO PRINCIPAL -->
        <!-- ================================================================== -->
        <main class="flex-1 p-6">
            <div class="max-w-7xl mx-auto">
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">
                        <i class="fas fa-th-large mr-2 text-red-600"></i>
                        Módulos do Sistema
                    </h2>                    <p class="text-gray-600">Navegue entre os diferentes módulos do Faciência ERP</p>
                </div>

                <div class="modules-grid">
                <!-- Módulo Administrador -->
                <div class="module-card">
                    <div class="module-header admin-header">
                        <i class="fas fa-cogs"></i>
                        <h3>Administrador</h3>
                    </div>
                    <div class="module-body">
                        <span class="status-badge status-active">Ativo</span>
                        <p class="module-description">
                            Módulo principal de administração do sistema, controle de usuários, logs e configurações.
                        </p>
                        <ul class="module-features">
                            <li><i class="fas fa-check"></i> Gerenciamento de usuários</li>
                            <li><i class="fas fa-check"></i> Logs e auditoria</li>
                            <li><i class="fas fa-check"></i> Configurações do sistema</li>
                            <li><i class="fas fa-check"></i> Controle de acesso</li>
                        </ul>
                        <div class="module-actions">
                            <a href="index.php" class="btn-module btn-primary">
                                <i class="fas fa-home"></i> Dashboard
                            </a>
                            <a href="usuarios.php" class="btn-module btn-secondary">
                                <i class="fas fa-users"></i> Usuários
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Módulo Financeiro -->
                <div class="module-card">
                    <div class="module-header financial-header">
                        <i class="fas fa-dollar-sign"></i>
                        <h3>Financeiro</h3>
                    </div>
                    <div class="module-body">
                        <span class="status-badge status-active">Ativo</span>
                        <p class="module-description">
                            Controle financeiro completo com mensalidades, pagamentos, inadimplência e relatórios.
                        </p>
                        <ul class="module-features">
                            <li><i class="fas fa-check"></i> Controle de mensalidades</li>
                            <li><i class="fas fa-check"></i> Gestão de pagamentos</li>
                            <li><i class="fas fa-check"></i> Relatórios financeiros</li>
                            <li><i class="fas fa-check"></i> Controle de inadimplência</li>
                        </ul>
                        <div class="module-actions">
                            <a href="../financeiro/index.php" class="btn-module btn-primary">
                                <i class="fas fa-chart-line"></i> Acessar
                            </a>
                            <a href="../financeiro/relatorios.php" class="btn-module btn-secondary">
                                <i class="fas fa-file-alt"></i> Relatórios
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Módulo Secretaria -->
                <div class="module-card">
                    <div class="module-header secretary-header">
                        <i class="fas fa-clipboard"></i>
                        <h3>Secretaria</h3>
                    </div>
                    <div class="module-body">
                        <span class="status-badge status-active">Ativo</span>
                        <p class="module-description">
                            Gestão acadêmica completa com matrículas, documentos, turmas e controle acadêmico.
                        </p>
                        <ul class="module-features">
                            <li><i class="fas fa-check"></i> Gestão de matrículas</li>
                            <li><i class="fas fa-check"></i> Controle de turmas</li>
                            <li><i class="fas fa-check"></i> Emissão de documentos</li>
                            <li><i class="fas fa-check"></i> Histórico acadêmico</li>
                        </ul>
                        <div class="module-actions">
                            <a href="../secretaria/index.php" class="btn-module btn-primary">
                                <i class="fas fa-graduation-cap"></i> Acessar
                            </a>
                            <a href="../secretaria/matriculas.php" class="btn-module btn-secondary">
                                <i class="fas fa-user-plus"></i> Matrículas
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Módulo Aluno -->
                <div class="module-card">
                    <div class="module-header student-header">
                        <i class="fas fa-user-graduate"></i>
                        <h3>Portal do Aluno</h3>
                    </div>
                    <div class="module-body">
                        <span class="status-badge status-active">Ativo</span>
                        <p class="module-description">
                            Portal dedicado aos alunos com acesso a cursos, notas, documentos e perfil acadêmico.
                        </p>
                        <ul class="module-features">
                            <li><i class="fas fa-check"></i> Acesso a cursos</li>
                            <li><i class="fas fa-check"></i> Consulta de notas</li>
                            <li><i class="fas fa-check"></i> Download de documentos</li>
                            <li><i class="fas fa-check"></i> Atualização de perfil</li>
                        </ul>
                        <div class="module-actions">
                            <a href="../aluno/index.php" class="btn-module btn-primary">
                                <i class="fas fa-book-open"></i> Acessar
                            </a>
                            <a href="../aluno/cursos.php" class="btn-module btn-secondary">
                                <i class="fas fa-graduation-cap"></i> Cursos
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Módulo AVA -->
                <div class="module-card">
                    <div class="module-header ava-header">
                        <i class="fas fa-laptop"></i>
                        <h3>AVA - Ambiente Virtual</h3>
                    </div>
                    <div class="module-body">
                        <span class="status-badge status-active">Ativo</span>
                        <p class="module-description">
                            Ambiente Virtual de Aprendizagem com cursos online, aulas, materiais e avaliações.
                        </p>
                        <ul class="module-features">
                            <li><i class="fas fa-check"></i> Cursos online</li>
                            <li><i class="fas fa-check"></i> Aulas interativas</li>
                            <li><i class="fas fa-check"></i> Material didático</li>
                            <li><i class="fas fa-check"></i> Sistema de avaliação</li>
                        </ul>
                        <div class="module-actions">
                            <a href="../ava/index.html" class="btn-module btn-primary">
                                <i class="fas fa-play-circle"></i> Acessar
                            </a>
                            <a href="../ava/cursos.php" class="btn-module btn-secondary">
                                <i class="fas fa-book"></i> Cursos
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Módulo Polo -->
                <div class="module-card">
                    <div class="module-header polo-header">
                        <i class="fas fa-map-marker-alt"></i>
                        <h3>Gestão de Polos</h3>
                    </div>
                    <div class="module-body">
                        <span class="status-badge status-active">Ativo</span>
                        <p class="module-description">
                            Gerenciamento completo de polos presenciais, coordenadores e atividades regionais.
                        </p>
                        <ul class="module-features">
                            <li><i class="fas fa-check"></i> Cadastro de polos</li>
                            <li><i class="fas fa-check"></i> Gestão de coordenadores</li>
                            <li><i class="fas fa-check"></i> Controle regional</li>
                            <li><i class="fas fa-check"></i> Relatórios por polo</li>
                        </ul>
                        <div class="module-actions">
                            <a href="../polo/index.php" class="btn-module btn-primary">
                                <i class="fas fa-building"></i> Acessar
                            </a>
                            <a href="../polo/cadastro.php" class="btn-module btn-secondary">
                                <i class="fas fa-plus"></i> Novo Polo
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Módulo Relatórios -->
                <div class="module-card">
                    <div class="module-header reports-header">
                        <i class="fas fa-chart-bar"></i>
                        <h3>Relatórios e Analytics</h3>
                    </div>
                    <div class="module-body">
                        <span class="status-badge status-maintenance">Em Desenvolvimento</span>
                        <p class="module-description">
                            Centro de relatórios avançados, dashboards e análises de dados do sistema.
                        </p>
                        <ul class="module-features">
                            <li><i class="fas fa-clock"></i> Relatórios personalizados</li>
                            <li><i class="fas fa-clock"></i> Dashboards interativos</li>
                            <li><i class="fas fa-clock"></i> Análise de dados</li>
                            <li><i class="fas fa-clock"></i> Exportação de dados</li>
                        </ul>
                        <div class="module-actions">
                            <a href="#" class="btn-module btn-secondary" onclick="alert('Módulo em desenvolvimento')">
                                <i class="fas fa-tools"></i> Em Breve
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Módulo API -->
                <div class="module-card">
                    <div class="module-header" style="background: linear-gradient(135deg, #343a40, #495057);">
                        <i class="fas fa-code"></i>
                        <h3>API e Integrações</h3>
                    </div>
                    <div class="module-body">
                        <span class="status-badge status-active">Ativo</span>
                        <p class="module-description">
                            Interface de programação para integrações externas e desenvolvimento de aplicações.
                        </p>
                        <ul class="module-features">
                            <li><i class="fas fa-check"></i> Endpoints REST</li>
                            <li><i class="fas fa-check"></i> Autenticação JWT</li>
                            <li><i class="fas fa-check"></i> Documentação API</li>
                            <li><i class="fas fa-check"></i> Logs de acesso</li>
                        </ul>
                        <div class="module-actions">
                            <a href="../api/" class="btn-module btn-primary">
                                <i class="fas fa-plug"></i> Documentação
                            </a>
                            <a href="logs.php?filtro_modulo=api" class="btn-module btn-secondary">
                                <i class="fas fa-list"></i> Logs API
                            </a>
                        </div>
                    </div>                </div>

                <!-- Estatísticas dos Módulos -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 mt-6">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">
                            <i class="fas fa-chart-pie mr-2 text-red-600"></i>
                            Estatísticas dos Módulos
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-red-600"><?php echo contarModulosAtivos(); ?></div>
                                <div class="text-sm text-gray-600">Módulos Ativos</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-green-600"><?php echo contarUsuariosOnline(); ?></div>
                                <div class="text-sm text-gray-600">Usuários Online</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-blue-600"><?php echo contarAcessosHoje(); ?></div>
                                <div class="text-sm text-gray-600">Acessos Hoje</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-purple-600"><?php echo verificarStatusSistema(); ?></div>
                                <div class="text-sm text-gray-600">Status Sistema</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ações Rápidas -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 mt-6">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">
                            <i class="fas fa-bolt mr-2 text-red-600"></i>
                            Ações Rápidas
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="flex flex-wrap gap-3">
                            <a href="usuarios.php?acao=novo" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                                <i class="fas fa-user-plus mr-2"></i>Novo Usuário
                            </a>
                            <a href="logs.php" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                                <i class="fas fa-list mr-2"></i>Ver Logs
                            </a>
                            <a href="configuracoes.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                                <i class="fas fa-cog mr-2"></i>Configurações
                            </a>                            <a href="../" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                                <i class="fas fa-home mr-2"></i>Página Inicial
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="js/admin.js"></script>
    <script>
        // Atualizar estatísticas a cada 30 segundos
        setInterval(function() {
            fetch('includes/ajax.php?acao=estatisticas_modulos')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Atualizar números
                        document.querySelector('.stat-item:nth-child(2) .stat-number').textContent = data.usuarios_online;
                        document.querySelector('.stat-item:nth-child(3) .stat-number').textContent = data.acessos_hoje;
                    }
                });
        }, 30000);

        // Registrar acesso à página
        registrarAcao('modulos', 'visualizar', 'Acessou página de módulos');
    </script>
</body>
</html>
