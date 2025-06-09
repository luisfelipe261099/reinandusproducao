<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faciência ERP - Relatórios</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4F46E5;
            --primary-dark: #4338CA;
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
        
        .sidebar {
            background-color: var(--dark);
            min-height: 100vh;
            transition: all 0.3s;
        }
        
        .sidebar-collapsed {
            width: 80px;
        }
        
        .sidebar-expanded {
            width: 250px;
        }
        
        .nav-item {
            border-radius: 0.5rem;
            margin: 0.25rem 0;
            transition: all 0.2s;
        }
        
        .nav-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .nav-item.active {
            background-color: var(--primary);
        }
        
        .report-card {
            background-color: white;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            padding: 1.5rem;
        }
        
        .report-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #E5E7EB;
        }
        
        .report-item:last-child {
            border-bottom: none;
        }
        
        .report-item-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex">
        <!-- Sidebar -->
        <aside class="sidebar sidebar-expanded text-white">
            <div class="p-4">
                <div class="flex items-center justify-between mb-8">
                    <img src="/api/placeholder/50/50" alt="Faciência ERP Logo" class="w-12 h-12">
                    <button id="toggleSidebar" class="text-white hover:text-gray-300">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
                
                <nav>
                    <ul>
                        <li class="nav-item">
                            <a href="secretaria_dashboard.php" class="flex items-center p-3 text-white">
                                <i class="fas fa-home mr-3"></i>
                                <span class="sidebar-text">Início</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="secretaria_gerar_documento.php" class="flex items-center p-3 text-white">
                                <i class="fas fa-file-alt mr-3"></i>
                                <span class="sidebar-text">Gerar Documentos</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="secretaria_desempenho.php" class="flex items-center p-3 text-white">
                                <i class="fas fa-chart-line mr-3"></i>
                                <span class="sidebar-text">Desempenho</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="secretaria_estatistica.php" class="flex items-center p-3 text-white">
                                <i class="fas fa-chart-pie mr-3"></i>
                                <span class="sidebar-text">Estatísticas</span>
                            </a>
                        </li>
                        <li class="nav-item active">
                            <a href="#" class="flex items-center p-3 text-white">
                                <i class="fas fa-file-signature mr-3"></i>
                                <span class="sidebar-text">Relatórios</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-grow bg-gray-50 min-h-screen">
            <header class="bg-white shadow-sm p-4 flex justify-between items-center">
                <div class="flex items-center">
                    <h1 class="text-2xl font-semibold text-gray-800 ml-4">Relatórios</h1>
                </div>
                <div class="flex items-center">
                    <div class="relative mr-4">
                        <i class="fas fa-bell text-gray-600"></i>
                        <span class="notification-badge">3</span>
                    </div>
                    <div class="flex items-center">
                        <img src="/api/placeholder/40/40" alt="Usuário" class="avatar mr-2">
                        <div>
                            <p class="text-sm font-medium text-gray-700">Maria Silva</p>
                            <p class="text-xs text-gray-500">Secretária</p>
                        </div>
                    </div>
                </div>
            </header>

            <div class="p-6">
                <div class="grid grid-cols-3 gap-6">
                    <div class="report-card col-span-2">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-xl font-semibold text-gray-800">Geração de Documentos</h2>
                            <div class="flex space-x-2">
                                <select id="reportCourseFilter" class="form-select">
                                    <option value="">Todos os Cursos</option>
                                    <option value="ads">Análise e Desenvolvimento de Sistemas</option>
                                    <option value="enfermagem">Enfermagem</option>
                                    <option value="direito">Direito</option>
                                </select>
                                <button class="btn btn-outline btn-sm">
                                    <i class="fas fa-filter mr-2"></i>Filtrar
                                </button>
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <div class="bg-gray-100 rounded-lg p-4 text-center">
                                <h3 class="text-sm text-gray-600">Total de Documentos</h3>
                                <p class="text-2xl font-bold text-primary">352</p>
                            </div>
                            <div class="bg-gray-100 rounded-lg p-4 text-center">
                                <h3 class="text-sm text-gray-600">Documentos Prontos</h3>
                                <p class="text-2xl font-bold text-green-600">285</p>
                            </div>
                            <div class="bg-gray-100 rounded-lg p-4 text-center">
                                <h3 class="text-sm text-gray-600">Documentos Pendentes</h3>
                                <p class="text-2xl font-bold text-yellow-600">67</p>
                            </div>
                        </div>
                    </div>

                    <div class="report-card">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-xl font-semibold text-gray-800">Relatórios Recentes</h2>
                            <button class="btn btn-outline btn-sm">
                                <i class="fas fa-plus mr-2"></i>Novo
                            </button>
                        </div>
                        <div>
                            <div class="report-item hover:bg-gray-50 cursor-pointer">
                                <div class="flex items-center">
                                    <div class="report-item-icon bg-primary/10 text-primary">
                                        <i class="fas fa-file-alt"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-gray-800">Relatório Mensal</h3>
                                        <p class="text-sm text-gray-600">Abril 2025</p>
                                    </div>
                                </div>
                                <div class="text-sm text-gray-500">
                                    <i class="fas fa-download mr-2"></i>
                                </div>
                            </div>
                            <div class="report-item hover:bg-gray-50 cursor-pointer">
                                <div class="flex items-center">
                                    <div class="report-item-icon bg-secondary/10 text-secondary">
                                        <i class="fas fa-chart-pie"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-gray-800">Estatística Trimestral</h3>
                                        <p class="text-sm text-gray-600">Jan-Mar 2025</p>
                                    </div>
                                </div>
                                <div class="text-sm text-gray-500">
                                    <i class="fas fa-download mr-2"></i>
                                </div>
                            </div>
                            <div class="report-item hover:bg-gray-50 cursor-pointer">
                                <div class="flex items-center">
                                    <div class="report-item-icon bg-accent/10 text-accent">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-gray-800">Tempo de Processamento</h3>
                                        <p class="text-sm text-gray-600">Fevereiro 2025</p>
                                    </div>
                                </div>
                                <div class="text-sm text-gray-500">
                                    <i class="fas fa-download mr-2"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="report-card col-span-3">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-xl font-semibold text-gray-800">Modelos de Relatórios</h2>
                            <button class="btn btn-primary btn-sm">
                                <i class="fas fa-plus mr-2"></i>Criar Modelo
                            </button>
                        </div>
                        <div class="grid grid-cols-4 gap-4">
                            <div class="bg-gray-100 rounded-lg p-4 text-center">
                                <div class="report-item-icon bg-primary/10 text-primary mx-auto mb-2">
                                    <i class="fas fa-file-invoice"></i>
                                </div>
                                <h3 class="font-semibold text-gray-800">Mensal</h3>
                                <p class="text-sm text-gray-600">Resumo Detalhado</p>
                            </div>
                            <div class="bg-gray-100 rounded-lg p-4 text-center">
                                <div class="report-item-icon bg-secondary/10 text-secondary mx-auto mb-2">
                                    <i class="fas fa-chart-bar"></i>
                                </div>
                                <h3 class="font-semibold text-gray-800">Trimestral</h3>
                                <p class="text-sm text-gray-600">Análise Completa</p>
                            </div>
                            <div class="bg-gray-100 rounded-lg p-4 text-center">
                                <div class="report-item-icon bg-accent/10 text-accent mx-auto mb-2">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                                <h3 class="font-semibold text-gray-800">Semestral</h3>
                                <p class="text-sm text-gray-600">Projeções</p>
                            </div>
                            <div class="bg-gray-100 rounded-lg p-4 text-center">
                                <div class="report-item-icon bg-danger/10 text-danger mx-auto mb-2">
                                    <i class="fas fa-file-signature"></i>
                                </div>
                                <h3 class="font-semibold text-gray-800">Anual</h3>
                                <p class="text-sm text-gray-600">Consolidado</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Sidebar Toggle
        const sidebar = document.querySelector('.sidebar');
        const toggleSidebar = document.getElementById('toggleSidebar');
        const sidebarTexts = document.querySelectorAll('.sidebar-text');

        toggleSidebar.addEventListener('click', () => {
            sidebar.classList.toggle('sidebar-expanded');
            sidebar.classList.toggle('sidebar-collapsed');
            
            sidebarTexts.forEach(text => {
                text.classList.toggle('hidden');
            });
        });

        // Recent Reports Download
        document.querySelectorAll('.report-item').forEach(item => {
            item.addEventListener('click', (e) => {
                const reportName = item.querySelector('h3').textContent;
                alert(`Baixando relatório: ${reportName}`);
            });
        });
    </script>
</body>
</html>
