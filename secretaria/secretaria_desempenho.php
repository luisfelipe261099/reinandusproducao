<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faciência ERP - Desempenho da Secretaria</title>
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
        
        .performance-card {
            background-color: white;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            padding: 1.5rem;
        }
        
        .performance-metric {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }
        
        .performance-metric-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
        }
        
        .performance-metric-icon.primary {
            background-color: rgba(79, 70, 229, 0.1);
            color: var(--primary);
        }
        
        .performance-metric-icon.success {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--secondary);
        }
        
        .performance-metric-icon.danger {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--danger);
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
                        <li class="nav-item active">
                            <a href="#" class="flex items-center p-3 text-white">
                                <i class="fas fa-chart-line mr-3"></i>
                                <span class="sidebar-text">Desempenho</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="secretaria_relatorio.php" class="flex items-center p-3 text-white">
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
                    <h1 class="text-2xl font-semibold text-gray-800 ml-4">Desempenho da Secretaria</h1>
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

            <div class="p-6 grid grid-cols-3 gap-6">
                <div class="performance-card">
                    <div class="performance-metric">
                        <div class="flex items-center">
                            <div class="performance-metric-icon primary">
                                <i class="fas fa-file-alt text-2xl"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800">Documentos Gerados</h3>
                                <p class="text-sm text-gray-600">Total no mês</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-2xl font-bold text-gray-800">352</span>
                            <p class="text-sm text-green-600">
                                <i class="fas fa-arrow-up mr-1"></i>12% 
                            </p>
                        </div>
                    </div>
                </div>

                <div class="performance-card">
                    <div class="performance-metric">
                        <div class="flex items-center">
                            <div class="performance-metric-icon success">
                                <i class="fas fa-clock text-2xl"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800">Tempo Médio</h3>
                                <p class="text-sm text-gray-600">Geração de Documento</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-2xl font-bold text-gray-800">15 min</span>
                            <p class="text-sm text-green-600">
                                <i class="fas fa-arrow-down mr-1"></i>5% 
                            </p>
                        </div>
                    </div>
                </div>

                <div class="performance-card">
                    <div class="performance-metric">
                        <div class="flex items-center">
                            <div class="performance-metric-icon danger">
                                <i class="fas fa-exclamation-triangle text-2xl"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800">Documentos Pendentes</h3>
                                <p class="text-sm text-gray-600">Aguardando Processamento</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-2xl font-bold text-gray-800">28</span>
                            <p class="text-sm text-red-600">
                                <i class="fas fa-arrow-up mr-1"></i>8% 
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-span-3 performance-card">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-semibold text-gray-800">Desempenho por Curso</h2>
                        <div class="flex space-x-2">
                            <button class="btn btn-outline btn-sm">
                                <i class="fas fa-filter mr-2"></i>Filtrar
                            </button>
                            <button class="btn btn-outline btn-sm">
                                <i class="fas fa-download mr-2"></i>Exportar
                            </button>
                        </div>
                    </div>
                    <div class="grid grid-cols-4 gap-4">
                        <div class="bg-gray-100 rounded-lg p-4 text-center">
                            <h3 class="font-semibold text-gray-800">ADS</h3>
                            <p class="text-2xl font-bold text-primary">145</p>
                            <p class="text-sm text-green-600">+22%</p>
                        </div>
                        <div class="bg-gray-100 rounded-lg p-4 text-center">
                            <h3 class="font-semibold text-gray-800">Enfermagem</h3>
                            <p class="text-2xl font-bold text-secondary">87</p>
                            <p class="text-sm text-green-600">+15%</p>
                        </div>
                        <div class="bg-gray-100 rounded-lg p-4 text-center">
                            <h3 class="font-semibold text-gray-800">Direito</h3>
                            <p class="text-2xl font-bold text-accent">120</p>
                            <p class="text-sm text-green-600">+18%</p>
                        </div>
                        <div class="bg-gray-100 rounded-lg p-4 text-center">
                            <h3 class="font-semibold text-gray-800">Total</h3>
                            <p class="text-2xl font-bold text-gray-800">352</p>
                            <p class="text-sm text-green-600">+12%</p>
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
    </script>
</body>
</html>