<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faciência ERP - Listagem de Alunos</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            </main>
        </div>
    </div>

    <!-- Modal - Filter -->
    <div id="filter-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-bold text-gray-800">Filtrar Alunos</h3>
                <button id="close-filter-modal" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Todos</option>
                        <option value="ativo">Ativo</option>
                        <option value="trancado">Trancado</option>
                        <option value="desistente">Desistente</option>
                        <option value="formado">Formado</option>
                        <option value="cancelado">Cancelado</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Curso</label>
                    <select class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Todos</option>
                        <option value="administracao">Administração</option>
                        <option value="direito">Direito</option>
                        <option value="sistemas">Sistemas de Informação</option>
                        <option value="pedagogia">Pedagogia</option>
                        <option value="contabeis">Ciências Contábeis</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Polo</label>
                    <select class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Todos</option>
                        <option value="central">Central</option>
                        <option value="norte">Norte</option>
                        <option value="sul">Sul</option>
                        <option value="leste">Leste</option>
                        <option value="oeste">Oeste</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Data de Ingresso</label>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs text-gray-500">De</label>
                            <input type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500">Até</label>
                            <input type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Situação de Documentos</label>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="checkbox" class="w-4 h-4 text-blue-600">
                            <span class="ml-2 text-sm text-gray-700">Pendente de CPF</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" class="w-4 h-4 text-blue-600">
                            <span class="ml-2 text-sm text-gray-700">Pendente de RG</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" class="w-4 h-4 text-blue-600">
                            <span class="ml-2 text-sm text-gray-700">Pendente de Diploma</span>
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 mt-6">
                <button class="btn-outline">Limpar</button>
                <button class="btn-primary">Aplicar Filtros</button>
            </div>
        </div>
    </div>

    <script>
        // Toggle sidebar
        document.getElementById('toggle-sidebar').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            const labels = document.querySelectorAll('.sidebar-label');
            const logoFull = document.querySelector('.sidebar-logo-full');
            
            if (sidebar.classList.contains('sidebar-expanded')) {
                sidebar.classList.remove('sidebar-expanded');
                sidebar.classList.add('sidebar-collapsed');
                labels.forEach(label => label.style.display = 'none');
                logoFull.style.display = 'none';
                this.innerHTML = '<i class="fas fa-chevron-right"></i>';
            } else {
                sidebar.classList.remove('sidebar-collapsed');
                sidebar.classList.add('sidebar-expanded');
                labels.forEach(label => label.style.display = 'inline');
                logoFull.style.display = 'flex';
                this.innerHTML = '<i class="fas fa-chevron-left"></i>';
            }
        });
        
        // User menu dropdown
        document.getElementById('user-menu-button').addEventListener('click', function() {
            const menu = document.getElementById('user-menu');
            menu.classList.toggle('hidden');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const userMenuButton = document.getElementById('user-menu-button');
            const userMenu = document.getElementById('user-menu');
            
            if (!userMenuButton.contains(event.target) && !userMenu.contains(event.target)) {
                userMenu.classList.add('hidden');
            }
        });
        
        // Checkbox functionality
        document.querySelectorAll('.checkbox').forEach(function(checkbox) {
            checkbox.addEventListener('click', function() {
                this.classList.toggle('checked');
                
                // If it's the "select all" checkbox
                if (this.id === 'select-all') {
                    const allCheckboxes = document.querySelectorAll('.checkbox:not(#select-all)');
                    if (this.classList.contains('checked')) {
                        allCheckboxes.forEach(cb => cb.classList.add('checked'));
                    } else {
                        allCheckboxes.forEach(cb => cb.classList.remove('checked'));
                    }
                }
            });
        });
    </script>
</body>
</html>--primary: #3B82F6;
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
        
        .badge {
            border-radius: 9999px;
            padding: 0.25rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-primary {
            background-color: var(--primary);
            color: white;
        }
        
        .badge-warning {
            background-color: var(--warning);
            color: white;
        }
        
        .badge-danger {
            background-color: var(--danger);
            color: white;
        }
        
        .badge-success {
            background-color: var(--secondary);
            color: white;
        }
        
        .search-bar {
            border-radius: 9999px;
            padding: 0.5rem 1rem;
            border: 1px solid #E5E7EB;
            width: 100%;
            transition: all 0.3s;
        }
        
        .search-bar:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
            border-color: var(--primary);
        }
        
        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            width: 18px;
            height: 18px;
            background-color: var(--danger);
            color: white;
            border-radius: 50%;
            font-size: 0.6rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .table-container {
            overflow-x: auto;
            border-radius: 1rem;
            background-color: white;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        }
        
        .data-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .data-table th {
            background-color: #F9FAFB;
            padding: 0.75rem 1.5rem;
            text-align: left;
            font-weight: 600;
            color: #374151;
            font-size: 0.875rem;
            border-bottom: 1px solid #E5E7EB;
        }
        
        .data-table td {
            padding: 1rem 1.5rem;
            color: #4B5563;
            font-size: 0.875rem;
            border-bottom: 1px solid #E5E7EB;
        }
        
        .data-table tr:last-child td {
            border-bottom: none;
        }
        
        .data-table tr:hover {
            background-color: #F9FAFB;
        }
        
        .data-table .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .status-badge.active {
            background-color: #D1FAE5;
            color: #065F46;
        }
        
        .status-badge.inactive {
            background-color: #FEE2E2;
            color: #991B1B;
        }
        
        .status-badge.pending {
            background-color: #FEF3C7;
            color: #92400E;
        }
        
        .dropdown {
            position: relative;
        }
        
        .dropdown-menu {
            position: absolute;
            right: 0;
            top: 100%;
            margin-top: 0.5rem;
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            min-width: 200px;
            z-index: 10;
        }
        
        .dropdown-item {
            padding: 0.75rem 1rem;
            transition: background-color 0.2s;
            display: flex;
            align-items: center;
        }
        
        .dropdown-item:hover {
            background-color: #F3F4F6;
        }
        
        .dropdown-item i {
            margin-right: 0.5rem;
            width: 1rem;
        }
        
        .hidden {
            display: none;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: white;
            border-radius: 0.5rem;
            padding: 0.5rem 1rem;
            font-weight: 500;
            transition: background-color 0.2s;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
        }
        
        .btn-outline {
            border: 1px solid #E5E7EB;
            color: #4B5563;
            border-radius: 0.5rem;
            padding: 0.5rem 1rem;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .btn-outline:hover {
            background-color: #F3F4F6;
        }
        
        .checkbox {
            width: 1.25rem;
            height: 1.25rem;
            border-radius: 0.25rem;
            border: 1px solid #D1D5DB;
            position: relative;
            transition: all 0.2s;
            cursor: pointer;
        }
        
        .checkbox.checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .checkbox.checked:after {
            content: '✓';
            position: absolute;
            color: white;
            font-size: 0.75rem;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        
        .pagination-item {
            width: 2.5rem;
            height: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.5rem;
            margin: 0 0.25rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .pagination-item:hover {
            background-color: #F3F4F6;
        }
        
        .pagination-item.active {
            background-color: var(--primary);
            color: white;
        }
        
        .pagination-item.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .filter-tag {
            display: inline-flex;
            align-items: center;
            background-color: #EFF6FF;
            border-radius: 9999px;
            padding: 0.25rem 0.75rem;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
            font-size: 0.75rem;
            color: var(--primary);
        }
        
        .filter-tag button {
            margin-left: 0.5rem;
            color: #6B7280;
            transition: color 0.2s;
        }
        
        .filter-tag button:hover {
            color: #1F2937;
        }
    </style>
</head>
<body>
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div id="sidebar" class="sidebar sidebar-expanded text-white">
            <div class="p-4 flex items-center justify-between">
                <div class="flex items-center sidebar-logo-full">
                    <img src="/api/placeholder/40/40" alt="Logo" class="w-10 h-10 rounded-md">
                    <span class="ml-3 text-xl font-bold">Faciência</span>
                </div>
                <button id="toggle-sidebar" class="text-white focus:outline-none">
                    <i class="fas fa-chevron-left"></i>
                </button>
            </div>
            <div class="px-4 py-6">
                <p class="text-xs text-gray-400 uppercase font-semibold">Menu Principal</p>
                <nav class="mt-4">
                    <a href="#" class="nav-item flex items-center py-3 px-4 text-white">
                        <i class="fas fa-tachometer-alt w-6"></i>
                        <span class="sidebar-label ml-3">Dashboard</span>
                    </a>
                    <a href="#" class="nav-item active flex items-center py-3 px-4 text-white">
                        <i class="fas fa-user-graduate w-6"></i>
                        <span class="sidebar-label ml-3">Alunos</span>
                    </a>
                    <a href="#" class="nav-item flex items-center py-3 px-4 text-white">
                        <i class="fas fa-graduation-cap w-6"></i>
                        <span class="sidebar-label ml-3">Cursos</span>
                    </a>
                    <a href="#" class="nav-item flex items-center py-3 px-4 text-white">
                        <i class="fas fa-users w-6"></i>
                        <span class="sidebar-label ml-3">Turmas</span>
                    </a>
                    <a href="#" class="nav-item flex items-center py-3 px-4 text-white">
                        <i class="fas fa-file-alt w-6"></i>
                        <span class="sidebar-label ml-3">Matrículas</span>
                    </a>
                    <a href="#" class="nav-item flex items-center py-3 px-4 text-white">
                        <i class="fas fa-book w-6"></i>
                        <span class="sidebar-label ml-3">Disciplinas</span>
                    </a>
                    <a href="#" class="nav-item flex items-center py-3 px-4 text-white">
                        <i class="fas fa-certificate w-6"></i>
                        <span class="sidebar-label ml-3">Documentos</span>
                    </a>
                </nav>
                <p class="text-xs text-gray-400 uppercase font-semibold mt-8">Relatórios</p>
                <nav class="mt-4">
                    <a href="#" class="nav-item flex items-center py-3 px-4 text-white">
                        <i class="fas fa-chart-bar w-6"></i>
                        <span class="sidebar-label ml-3">Desempenho</span>
                    </a>
                    <a href="#" class="nav-item flex items-center py-3 px-4 text-white">
                        <i class="fas fa-chart-pie w-6"></i>
                        <span class="sidebar-label ml-3">Estatísticas</span>
                    </a>
                </nav>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Navigation -->
            <header class="bg-white shadow-sm">
                <div class="flex items-center justify-between p-4">
                    <div class="flex items-center space-x-4">
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                                <i class="fas fa-search text-gray-400"></i>
                            </span>
                            <input type="text" class="search-bar pl-10" placeholder="Buscar aluno por nome, CPF ou email...">
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="relative">
                            <button class="relative p-2 rounded-full hover:bg-gray-100 focus:outline-none">
                                <i class="fas fa-bell text-gray-600"></i>
                                <span class="notification-badge">3</span>
                            </button>
                        </div>
                        <div class="dropdown">
                            <button id="user-menu-button" class="flex items-center space-x-3 focus:outline-none">
                                <img src="/api/placeholder/40/40" alt="Avatar" class="avatar">
                                <div class="text-left hidden md:block">
                                    <p class="text-sm font-medium text-gray-700">Ana Silva</p>
                                    <p class="text-xs text-gray-500">Secretaria Acadêmica</p>
                                </div>
                                <i class="fas fa-chevron-down text-gray-400 text-xs ml-2"></i>
                            </button>
                            <div id="user-menu" class="dropdown-menu hidden">
                                <a href="#" class="dropdown-item">
                                    <i class="fas fa-user"></i>
                                    <span>Meu Perfil</span>
                                </a>
                                <a href="#" class="dropdown-item">
                                    <i class="fas fa-cog"></i>
                                    <span>Configurações</span>
                                </a>
                                <a href="#" class="dropdown-item border-t border-gray-200">
                                    <i class="fas fa-sign-out-alt"></i>
                                    <span>Sair</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto bg-gray-50 p-6">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Alunos</h1>
                        <p class="text-gray-600">Gerencie todos os alunos da instituição</p>
                    </div>
                    <div class="flex space-x-3">
                        <button class="btn-outline flex items-center">
                            <i class="fas fa-filter mr-2"></i>
                            <span>Filtros</span>
                        </button>
                        <button class="btn-outline flex items-center">
                            <i class="fas fa-download mr-2"></i>
                            <span>Exportar</span>
                        </button>
                        <button class="btn-primary flex items-center">
                            <i class="fas fa-user-plus mr-2"></i>
                            <span>Novo Aluno</span>
                        </button>
                    </div>
                </div>
                
                <!-- Applied Filters -->
                <div class="mb-6">
                    <div class="flex flex-wrap items-center">
                        <span class="text-gray-700 text-sm font-medium mr-3">Filtros aplicados:</span>
                        
                        <div class="filter-tag">
                            <span>Curso: Administração</span>
                            <button><i class="fas fa-times"></i></button>
                        </div>
                        
                        <div class="filter-tag">
                            <span>Status: Ativo</span>
                            <button><i class="fas fa-times"></i></button>
                        </div>
                        
                        <div class="filter-tag">
                            <span>Polo: Central</span>
                            <button><i class="fas fa-times"></i></button>
                        </div>
                        
                        <button class="text-sm text-primary-600 font-medium hover:underline">
                            Limpar todos
                        </button>
                    </div>
                </div>
                
                <!-- Table Container -->
                <div class="table-container mb-6">
                    <div class="flex justify-between items-center p-4 border-b border-gray-200">
                        <div class="flex items-center">
                            <div class="checkbox" id="select-all"></div>
                            <span class="ml-3 text-sm text-gray-500">Selecionar todos</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <button class="text-gray-500 hover:text-gray-700">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                            <div class="dropdown">
                                <button class="text-gray-500 hover:text-gray-700">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th class="w-12"></th>
                                <th>Nome</th>
                                <th>CPF</th>
                                <th>Curso</th>
                                <th>Polo</th>
                                <th>Status</th>
                                <th>Data Ingresso</th>
                                <th class="w-20">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <div class="checkbox"></div>
                                </td>
                                <td>
                                    <div class="flex items-center">
                                        <img src="/api/placeholder/36/36" alt="Avatar" class="w-9 h-9 rounded-full mr-3">
                                        <div>
                                            <p class="font-medium text-gray-800">Ana Carolina Oliveira</p>
                                            <p class="text-gray-500 text-xs">anacarolina@email.com</p>
                                        </div>
                                    </div>
                                </td>
                                <td>123.456.789-10</td>
                                <td>Administração</td>
                                <td>Central</td>
                                <td>
                                    <span class="status-badge active">Ativo</span>
                                </td>
                                <td>10/02/2025</td>
                                <td>
                                    <div class="flex items-center space-x-3">
                                        <button class="text-gray-500 hover:text-gray-700" title="Visualizar">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="text-gray-500 hover:text-blue-600" title="Editar">
                                            <i class="fas fa-pen"></i>
                                        </button>
                                        <button class="text-gray-500 hover:text-red-600" title="Excluir">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            
                            <tr>
                                <td>
                                    <div class="checkbox"></div>
                                </td>
                                <td>
                                    <div class="flex items-center">
                                        <img src="/api/placeholder/36/36" alt="Avatar" class="w-9 h-9 rounded-full mr-3">
                                        <div>
                                            <p class="font-medium text-gray-800">Bruno Almeida Santos</p>
                                            <p class="text-gray-500 text-xs">bruno.almeida@email.com</p>
                                        </div>
                                    </div>
                                </td>
                                <td>987.654.321-00</td>
                                <td>Sistemas de Informação</td>
                                <td>Norte</td>
                                <td>
                                    <span class="status-badge active">Ativo</span>
                                </td>
                                <td>15/03/2024</td>
                                <td>
                                    <div class="flex items-center space-x-3">
                                        <button class="text-gray-500 hover:text-gray-700" title="Visualizar">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="text-gray-500 hover:text-blue-600" title="Editar">
                                            <i class="fas fa-pen"></i>
                                        </button>
                                        <button class="text-gray-500 hover:text-red-600" title="Excluir">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            
                            <tr>
                                <td>
                                    <div class="checkbox"></div>
                                </td>
                                <td>
                                    <div class="flex items-center">
                                        <img src="/api/placeholder/36/36" alt="Avatar" class="w-9 h-9 rounded-full mr-3">
                                        <div>
                                            <p class="font-medium text-gray-800">Carla Mendes Ferreira</p>
                                            <p class="text-gray-500 text-xs">carla.mendes@email.com</p>
                                        </div>
                                    </div>
                                </td>
                                <td>456.789.123-45</td>
                                <td>Pedagogia</td>
                                <td>Central</td>
                                <td>
                                    <span class="status-badge pending">Trancado</span>
                                </td>
                                <td>05/08/2024</td>
                                <td>
                                    <div class="flex items-center space-x-3">
                                        <button class="text-gray-500 hover:text-gray-700" title="Visualizar">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="text-gray-500 hover:text-blue-600" title="Editar">
                                            <i class="fas fa-pen"></i>
                                        </button>
                                        <button class="text-gray-500 hover:text-red-600" title="Excluir">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            
                            <tr>
                                <td>
                                    <div class="checkbox"></div>
                                </td>
                                <td>
                                    <div class="flex items-center">
                                        <img src="/api/placeholder/36/36" alt="Avatar" class="w-9 h-9 rounded-full mr-3">
                                        <div>
                                            <p class="font-medium text-gray-800">Daniel Costa Silva</p>
                                            <p class="text-gray-500 text-xs">daniel.costa@email.com</p>
                                        </div>
                                    </div>
                                </td>
                                <td>789.123.456-78</td>
                                <td>Administração</td>
                                <td>Sul</td>
                                <td>
                                    <span class="status-badge inactive">Desistente</span>
                                </td>
                                <td>22/11/2023</td>
                                <td>
                                    <div class="flex items-center space-x-3">
                                        <button class="text-gray-500 hover:text-gray-700" title="Visualizar">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="text-gray-500 hover:text-blue-600" title="Editar">
                                            <i class="fas fa-pen"></i>
                                        </button>
                                        <button class="text-gray-500 hover:text-red-600" title="Excluir">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            
                            <tr>
                                <td>
                                    <div class="checkbox"></div>
                                </td>
                                <td>
                                    <div class="flex items-center">
                                        <img src="/api/placeholder/36/36" alt="Avatar" class="w-9 h-9 rounded-full mr-3">
                                        <div>
                                            <p class="font-medium text-gray-800">Eduarda Pereira Nunes</p>
                                            <p class="text-gray-500 text-xs">eduarda.pereira@email.com</p>
                                        </div>
                                    </div>
                                </td>
                                <td>321.654.987-32</td>
                                <td>Direito</td>
                                <td>Leste</td>
                                <td>
                                    <span class="status-badge active">Ativo</span>
                                </td>
                                <td>03/01/2025</td>
                                <td>
                                    <div class="flex items-center space-x-3">
                                        <button class="text-gray-500 hover:text-gray-700" title="Visualizar">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="text-gray-500 hover:text-blue-600" title="Editar">
                                            <i class="fas fa-pen"></i>
                                        </button>
                                        <button class="text-gray-500 hover:text-red-600" title="Excluir">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            
                            <tr>
                                <td>
                                    <div class="checkbox"></div>
                                </td>
                                <td>
                                    <div class="flex items-center">
                                        <img src="/api/placeholder/36/36" alt="Avatar" class="w-9 h-9 rounded-full mr-3">
                                        <div>
                                            <p class="font-medium text-gray-800">Felipe Ribeiro Oliveira</p>
                                            <p class="text-gray-500 text-xs">felipe.ribeiro@email.com</p>
                                        </div>
                                    </div>
                                </td>
                                <td>159.753.852-96</td>
                                <td>Ciências Contábeis</td>
                                <td>Oeste</td>
                                <td>
                                    <span class="status-badge active">Ativo</span>
                                </td>
                                <td>12/02/2025</td>
                                <td>
                                    <div class="flex items-center space-x-3">
                                        <button class="text-gray-500 hover:text-gray-700" title="Visualizar">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="text-gray-500 hover:text-blue-600" title="Editar">
                                            <i class="fas fa-pen"></i>
                                        </button>
                                        <button class="text-gray-500 hover:text-red-600" title="Excluir">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="flex justify-between items-center">
                    <div class="text-sm text-gray-600">
                        Mostrando 1-6 de 284 alunos
                    </div>
                    <div class="flex items-center">
                        <div class="pagination-item disabled">
                            <i class="fas fa-chevron-left"></i>
                        </div>
                        <div class="pagination-item active">1</div>
                        <div class="pagination-item">2</div>
                        <div class="pagination-item">3</div>
                        <div class="pagination-item">4</div>
                        <div class="pagination-item">5</div>
                        <div class="pagination-item">
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    </div>
                </div>