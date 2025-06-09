
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faciência ERP - Estatísticas</title>
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
        
        .statistics-card {
            background-color: white;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            padding: 1.5rem;
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
                        <li class="nav-item active">
                            <a href="#" class="flex items-center p-3 text-white">
                                <i class="fas fa-chart-pie mr-3"></i>
                                <span class="sidebar-text">Estatísticas</span>
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
                    <h1 class="text-2xl font-semibold text-gray-800 ml-4">Estatísticas Detalhadas</h1>
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
                <div class="flex space-x-4 mb-6">
                    <div class="flex-grow">
                        <select id="periodSelect" class="form-select w-full">
                            <option value="current_month">Mês Atual</option>
                            <option value="last_month">Mês Anterior</option>
                            <option value="current_year">Ano Atual</option>
                            <option value="custom">Período Personalizado</option>
                        </select>
                    </div>
                    <div id="customDateRange" class="flex space-x-4 hidden">
                        <input type="date" class="form-input" id="startDate">
                        <input type="date" class="form-input" id="endDate">
                    </div>
                    <button id="applyFilter" class="btn btn-primary">
                        <i class="fas fa-filter mr-2"></i>Aplicar Filtro
                    </button>
                </div>

                <div class="grid grid-cols-4 gap-6">
                    <div class="statistics-card">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-800">Documentos por Tipo</h3>
                            <i class="fas fa-chart-pie text-primary"></i>
                        </div>
                        <div class="text-center">
                            <canvas id="documentTypeChart"></canvas>
                        </div>
                    </div>

                    <div class="statistics-card">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-800">Distribuição por Curso</h3>
                            <i class="fas fa-graduation-cap text-secondary"></i>
                        </div>
                        <div class="text-center">
                            <canvas id="courseDistributionChart"></canvas>
                        </div>
                    </div>

                    <div class="statistics-card">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-800">Status dos Documentos</h3>
                            <i class="fas fa-file-alt text-accent"></i>
                        </div>
                        <div class="text-center">
                            <canvas id="documentStatusChart"></canvas>
                        </div>
                    </div>

                    <div class="statistics-card">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-800">Tempo de Processamento</h3>
                            <i class="fas fa-clock text-danger"></i>
                        </div>
                        <div class="flex flex-col space-y-2">
                            <div class="bg-gray-100 rounded-lg p-3">
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Média</span>
                                    <span class="font-semibold">15 min</span>
                                </div>
                            </div>
                            <div class="bg-gray-100 rounded-lg p-3">
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Mais rápido</span>
                                    <span class="font-semibold">5 min</span>
                                </div>
                            </div>
                            <div class="bg-gray-100 rounded-lg p-3">
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Mais lento</span>
                                    <span class="font-semibold">45 min</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-span-4 statistics-card">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-800">Documentos Gerados ao Longo do Tempo</h3>
                            <div class="flex space-x-2">
                                <button class="btn btn-outline btn-sm">Diário</button>
                                <button class="btn btn-outline btn-sm">Semanal</button>
                                <button class="btn btn-primary btn-sm">Mensal</button>
                            </div>
                        </div>
                        <div>
                            <canvas id="documentTimelineChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.1/chart.min.js"></script>
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

        // Period Selection
        const periodSelect = document.getElementById('periodSelect');
        const customDateRange = document.getElementById('customDateRange');

        periodSelect.addEventListener('change', (e) => {
            customDateRange.classList.toggle('hidden', e.target.value !== 'custom');
        });

        // Charts
        function createDocumentTypeChart() {
            const ctx = document.getElementById('documentTypeChart').getContext('2d');
            new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: ['Declaração', 'Histórico', 'Atestado', 'Certificado'],
                    datasets: [{
                        data: [35, 25, 20, 20],
                        backgroundColor: [
                            '#4F46E5', '#10B981', '#8B5CF6', '#EF4444'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        }
                    }
                }
            });
        }

        function createCourseDistributionChart() {
            const ctx = document.getElementById('courseDistributionChart').getContext('2d');
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['ADS', 'Enfermagem', 'Direito'],
                    datasets: [{
                        data: [45, 30, 25],
                        backgroundColor: [
                            '#4F46E5', '#10B981', '#8B5CF6'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        }
                    }
                }
            });
        }

        function createDocumentStatusChart() {
            const ctx = document.getElementById('documentStatusChart').getContext('2d');
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Pronto', 'Processando', 'Solicitado'],
                    datasets: [{
                        data: [60, 25, 15],
                        backgroundColor: [
                            '#10B981', '#F59E0B', '#4F46E5'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        }
                    }
                }
            });
        }

        function createDocumentTimelineChart() {
            const ctx = document.getElementById('documentTimelineChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun'],
                    datasets: [{
                        label: 'Documentos Gerados',
                        data: [120, 190, 300, 350, 280, 400],
                        borderColor: '#4F46E5',
                        backgroundColor: 'rgba(79, 70, 229, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        // Initialize Charts
        createDocumentTypeChart();
        createCourseDistributionChart();
        createDocumentStatusChart();
        createDocumentTimelineChart();
    </script>
</body>
</html>