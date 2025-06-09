<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faciência ERP - Gestão de Turmas</title>
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
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        
        .data-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .data-table th {
            background-color: #F9FAFB;
            padding: 0.75rem 1rem;
            text-align: left;
            font-weight: 600;
            color: #4B5563;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid #E5E7EB;
        }
        
        .data-table td {
            padding: 1rem;
            color: #4B5563;
            font-size: 0.875rem;
            border-bottom: 1px solid #E5E7EB;
            background-color: white;
        }
        
        .data-table tr:last-child td {
            border-bottom: none;
        }
        
        .data-table tbody tr:hover td {
            background-color: #F9FAFB;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .status-badge.ativo {
            background-color: #D1FAE5;
            color: #065F46;
        }
        
        .status-badge.inativo {
            background-color: #FEE2E2;
            color: #991B1B;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.5rem;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            font-size: 0.875rem;
            transition: all 0.2s;
            cursor: pointer;
        }
        
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.75rem;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
        }
        
        .btn-outline {
            border: 1px solid #D1D5DB;
            color: #4B5563;
            background-color: white;
        }
        
        .btn-outline:hover {
            background-color: #F3F4F6;
        }
        
        .btn-success {
            background-color: var(--secondary);
            color: white;
        }
        
        .btn-success:hover {
            background-color: #0D9488;
        }
        
        .btn-danger {
            background-color: var(--danger);
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #DC2626;
        }
        
        .form-card {
            background-color: white;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        
        .form-label {
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
            display: block;
        }
        
        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #D1D5DB;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            background-color: white;
            transition: all 0.2s;
        }
        
        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2);
        }
        
        .form-select {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.75rem center;
            background-repeat: no-repeat;
            background-size: 1rem;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
        }
        
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 50;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s, visibility 0.3s;
        }
        
        .modal.open {
            opacity: 1;
            visibility: visible;
        }
        
        .modal-content {
            background-color: white;
            border-radius: 0.75rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            width: 100%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .class-card {
            transition: all 0.2s;
        }
        
        .class-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        .student-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
            background-color: #E0E7FF;
            color: #4338CA;
            margin-right: 0.25rem;
            margin-bottom: 0.25rem;
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
                    <a href="secretaria_dashboard.php" class="nav-item flex items-center py-3 px-4 text-white">
                        <i class="fas fa-tachometer-alt w-6"></i>
                        <span class="sidebar-label ml-3">Dashboard</span>
                    </a>
                    <a href="secretaria_lista_aluno.php" class="nav-item flex items-center py-3 px-4 text-white">
                        <i class="fas fa-user-graduate w-6"></i>
                        <span class="sidebar-label ml-3">Alunos</span>
                    </a>
                    <a href="#" class="nav-item flex items-center py-3 px-4 text-white">
                        <i class="fas fa-graduation-cap w-6"></i>
                        <span class="sidebar-label ml-3">Cursos</span>
                    </a>
                    <a href="secretaria_turmas.php" class="nav-item active flex items-center py-3 px-4 text-white">
                        <i class="fas fa-users w-6"></i>
                        <span class="sidebar-label ml-3">Turmas</span>
                    </a>
                    <a href="secretaria_matricula.php" class="nav-item flex items-center py-3 px-4 text-white">
                        <i class="fas fa-file-alt w-6"></i>
                        <span class="sidebar-label ml-3">Matrículas</span>
                    </a>
                    <a href="secretaria_disciplinas.php" class="nav-item flex items-center py-3 px-4 text-white">
                        <i class="fas fa-book w-6"></i>
                        <span class="sidebar-label ml-3">Disciplinas</span>
                    </a>
                    <a href="secretaria_solicit_documento.php" class="nav-item flex items-center py-3 px-4 text-white">
                        <i class="fas fa-certificate w-6"></i>
                        <span class="sidebar-label ml-3">Documentos</span>
                    </a>
                    <a href="secretaria_notas.php" class="nav-item flex items-center py-3 px-4 text-white">
                        <i class="fas fa-chart-line w-6"></i>
                        <span class="sidebar-label ml-3">Notas</span>
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
                            <input type="text" class="pl-10 pr-4 py-2 border border-gray-300 rounded-full text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 w-64" placeholder="Buscar turma...">
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="relative">
                            <button class="relative p-2 rounded-full hover:bg-gray-100 focus:outline-none">
                                <i class="fas fa-bell text-gray-600"></i>
                                <span class="notification-badge">3</span>
                            </button>
                        </div>
                        <div class="dropdown relative">
                            <button id="user-menu-button" class="flex items-center space-x-3 focus:outline-none">
                                <img src="/api/placeholder/40/40" alt="Avatar" class="avatar">
                                <div class="text-left hidden md:block">
                                    <p class="text-sm font-medium text-gray-700">Ana Silva</p>
                                    <p class="text-xs text-gray-500">Secretaria Acadêmica</p>
                                </div>
                                <i class="fas fa-chevron-down text-gray-400 text-xs ml-2"></i>
                            </button>
                            <div id="user-menu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10">
                                <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Meu Perfil</a>
                                <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Configurações</a>
                                <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Sair</a>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto bg-gray-50 p-6">
                <div class="max-w-7xl mx-auto">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-800">Gestão de Turmas</h1>
                            <p class="text-gray-600">Gerencie as turmas e seus alunos</p>
                        </div>
                        <div>
                            <button id="new-class-btn" class="btn btn-primary">
                                <i class="fas fa-plus mr-2"></i>
                                <span>Nova Turma</span>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Filters -->
                    <div class="bg-white p-4 rounded-lg border border-gray-200 mb-6">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                            <div class="flex space-x-2">
                                <div class="relative">
                                    <select class="form-select py-2 pl-3 pr-10 text-sm" id="filter-curso">
                                        <option value="">Todos os Cursos</option>
                                        <option value="1">Administração</option>
                                        <option value="2">Direito</option>
                                        <option value="3">Sistemas de Informação</option>
                                        <option value="4">Pedagogia</option>
                                        <option value="5">Ciências Contábeis</option>
                                    </select>
                                </div>
                                
                                <div class="relative">
                                    <select class="form-select py-2 pl-3 pr-10 text-sm" id="filter-periodo">
                                        <option value="">Todos os Períodos</option>
                                        <option value="2023/1">2023/1</option>
                                        <option value="2023/2">2023/2</option>
                                        <option value="2022/2">2022/2</option>
                                        <option value="2022/1">2022/1</option>
                                    </select>
                                </div>
                                
                                <div class="relative">
                                    <select class="form-select py-2 pl-3 pr-10 text-sm" id="filter-status">
                                        <option value="">Todos os Status</option>
                                        <option value="ativo">Ativas</option>
                                        <option value="inativo">Encerradas</option>
                                    </select>
                                </div>
                                
                                <button class="btn btn-sm btn-outline">
                                    <i class="fas fa-filter mr-1"></i> Filtrar
                                </button>
                            </div>
                            
                            <div class="flex items-center">
                                <span class="text-sm text-gray-500 mr-2">Visualização:</span>
                                <button class="btn btn-sm btn-outline view-btn active" data-view="grid">
                                    <i class="fas fa-th"></i>
                                </button>
                                <button class="btn btn-sm btn-outline view-btn ml-2" data-view="list">
                                    <i class="fas fa-list"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Grid View -->
                    <div id="grid-view" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <!-- Turma Card 1 -->
                        <div class="class-card bg-white rounded-lg shadow-md overflow-hidden">
                            <div class="bg-indigo-600 p-4 text-white">
                                <div class="flex justify-between items-center">
                                    <h3 class="font-bold text-lg">SI-2023/1</h3>
                                    <span class="status-badge ativo bg-white text-indigo-700">Ativa</span>
                                </div>
                                <p class="text-indigo-100">Sistemas de Informação</p>
                            </div>
                            
                            <div class="p-4">
                                <div class="flex justify-between items-center mb-4">
                                    <div>
                                        <p class="text-sm text-gray-500">Período</p>
                                        <p class="font-medium">2023/1</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Total de Alunos</p>
                                        <p class="font-medium">32</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Disciplinas</p>
                                        <p class="font-medium">8</p>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <p class="text-sm text-gray-500 mb-2">Coordenador</p>
                                    <div class="flex items-center">
                                        <img src="/api/placeholder/32/32" alt="Coordenador" class="h-8 w-8 rounded-full mr-2">
                                        <span class="font-medium">Prof. João Silva</span>
                                    </div>
                                </div>
                                
                                <div class="flex justify-end space-x-2">
                                    <button class="btn btn-sm btn-outline view-class-btn" data-id="1">
                                        <i class="fas fa-eye mr-1"></i> Detalhes
                                    </button>
                                    <button class="btn btn-sm btn-primary edit-class-btn" data-id="1">
                                        <i class="fas fa-edit mr-1"></i> Editar
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Turma Card 2 -->
                        <div class="class-card bg-white rounded-lg shadow-md overflow-hidden">
                            <div class="bg-blue-600 p-4 text-white">
                                <div class="flex justify-between items-center">
                                    <h3 class="font-bold text-lg">ADM-2023/1</h3>
                                    <span class="status-badge ativo bg-white text-blue-700">Ativa</span>
                                </div>
                                <p class="text-blue-100">Administração</p>
                            </div>
                            
                            <div class="p-4">
                                <div class="flex justify-between items-center mb-4">
                                    <div>
                                        <p class="text-sm text-gray-500">Período</p>
                                        <p class="font-medium">2023/1</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Total de Alunos</p>
                                        <p class="font-medium">28</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Disciplinas</p>
                                        <p class="font-medium">7</p>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <p class="text-sm text-gray-500 mb-2">Coordenador</p>
                                    <div class="flex items-center">
                                        <img src="/api/placeholder/32/32" alt="Coordenador" class="h-8 w-8 rounded-full mr-2">
                                        <span class="font-medium">Prof. Carlos Alberto</span>
                                    </div>
                                </div>
                                
                                <div class="flex justify-end space-x-2">
                                    <button class="btn btn-sm btn-outline view-class-btn" data-id="2">
                                        <i class="fas fa-eye mr-1"></i> Detalhes
                                    </button>
                                    <button class="btn btn-sm btn-primary edit-class-btn" data-id="2">
                                        <i class="fas fa-edit mr-1"></i> Editar
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Turma Card 3 -->
                        <div class="class-card bg-white rounded-lg shadow-md overflow-hidden">
                            <div class="bg-purple-600 p-4 text-white">
                                <div class="flex justify-between items-center">
                                    <h3 class="font-bold text-lg">DIR-2023/1</h3>
                                    <span class="status-badge ativo bg-white text-purple-700">Ativa</span>
                                </div>
                                <p class="text-purple-100">Direito</p>
                            </div>
                            
                            <div class="p-4">
                                <div class="flex justify-between items-center mb-4">
                                    <div>
                                        <p class="text-sm text-gray-500">Período</p>
                                        <p class="font-medium">2023/1</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Total de Alunos</p>
                                        <p class="font-medium">35</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Disciplinas</p>
                                        <p class="font-medium">8</p>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <p class="text-sm text-gray-500 mb-2">Coordenador</p>
                                    <div class="flex items-center">
                                        <img src="/api/placeholder/32/32" alt="Coordenador" class="h-8 w-8 rounded-full mr-2">
                                        <span class="font-medium">Profa. Maria Santos</span>
                                    </div>
                                </div>
                                
                                <div class="flex justify-end space-x-2">
                                    <button class="btn btn-sm btn-outline view-class-btn" data-id="3">
                                        <i class="fas fa-eye mr-1"></i> Detalhes
                                    </button>
                                    <button class="btn btn-sm btn-primary edit-class-btn" data-id="3">
                                        <i class="fas fa-edit mr-1"></i> Editar
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Turma Card 4 -->
                        <div class="class-card bg-white rounded-lg shadow-md overflow-hidden">
                            <div class="bg-green-600 p-4 text-white">
                                <div class="flex justify-between items-center">
                                    <h3 class="font-bold text-lg">PED-2023/1</h3>
                                    <span class="status-badge ativo bg-white text-green-700">Ativa</span>
                                </div>
                                <p class="text-green-100">Pedagogia</p>
                            </div>
                            
                            <div class="p-4">
                                <div class="flex justify-between items-center mb-4">
                                    <div>
                                        <p class="text-sm text-gray-500">Período</p>
                                        <p class="font-medium">2023/1</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Total de Alunos</p>
                                        <p class="font-medium">25</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Disciplinas</p>
                                        <p class="font-medium">6</p>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <p class="text-sm text-gray-500 mb-2">Coordenador</p>
                                    <div class="flex items-center">
                                        <img src="/api/placeholder/32/32" alt="Coordenador" class="h-8 w-8 rounded-full mr-2">
                                        <span class="font-medium">Profa. Amanda Oliveira</span>
                                    </div>
                                </div>
                                
                                <div class="flex justify-end space-x-2">
                                    <button class="btn btn-sm btn-outline view-class-btn" data-id="4">
                                        <i class="fas fa-eye mr-1"></i> Detalhes
                                    </button>
                                    <button class="btn btn-sm btn-primary edit-class-btn" data-id="4">
                                        <i class="fas fa-edit mr-1"></i> Editar
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Turma Card 5 -->
                        <div class="class-card bg-white rounded-lg shadow-md overflow-hidden">
                            <div class="bg-yellow-600 p-4 text-white">
                                <div class="flex justify-between items-center">
                                    <h3 class="font-bold text-lg">CONT-2023/1</h3>
                                    <span class="status-badge ativo bg-white text-yellow-700">Ativa</span>
                                </div>
                                <p class="text-yellow-100">Ciências Contábeis</p>
                            </div>
                            
                            <div class="p-4">
                                <div class="flex justify-between items-center mb-4">
                                    <div>
                                        <p class="text-sm text-gray-500">Período</p>
                                        <p class="font-medium">2023/1</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Total de Alunos</p>
                                        <p class="font-medium">22</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Disciplinas</p>
                                        <p class="font-medium">7</p>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <p class="text-sm text-gray-500 mb-2">Coordenador</p>
                                    <div class="flex items-center">
                                        <img src="/api/placeholder/32/32" alt="Coordenador" class="h-8 w-8 rounded-full mr-2">
                                        <span class="font-medium">Prof. Ricardo Melo</span>
                                    </div>
                                </div>
                                
                                <div class="flex justify-end space-x-2">
                                    <button class="btn btn-sm btn-outline view-class-btn" data-id="5">
                                        <i class="fas fa-eye mr-1"></i> Detalhes
                                    </button>
                                    <button class="btn btn-sm btn-primary edit-class-btn" data-id="5">
                                        <i class="fas fa-edit mr-1"></i> Editar
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Turma Card 6 -->
                        <div class="class-card bg-white rounded-lg shadow-md overflow-hidden">
                            <div class="bg-gray-600 p-4 text-white">
                                <div class="flex justify-between items-center">
                                    <h3 class="font-bold text-lg">ADM-2022/2</h3>
                                    <span class="status-badge inativo bg-white text-gray-700">Encerrada</span>
                                </div>
                                <p class="text-gray-300">Administração</p>
                            </div>
                            
                            <div class="p-4">
                                <div class="flex justify-between items-center mb-4">
                                    <div>
                                        <p class="text-sm text-gray-500">Período</p>
                                        <p class="font-medium">2022/2</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Total de Alunos</p>
                                        <p class="font-medium">30</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Disciplinas</p>
                                        <p class="font-medium">7</p>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <p class="text-sm text-gray-500 mb-2">Coordenador</p>
                                    <div class="flex items-center">
                                        <img src="/api/placeholder/32/32" alt="Coordenador" class="h-8 w-8 rounded-full mr-2">
                                        <span class="font-medium">Prof. Carlos Alberto</span>
                                    </div>
                                </div>
                                
                                <div class="flex justify-end space-x-2">
                                    <button class="btn btn-sm btn-outline view-class-btn" data-id="6">
                                        <i class="fas fa-eye mr-1"></i> Detalhes
                                    </button>
                                    <button class="btn btn-sm btn-primary edit-class-btn" data-id="6">
                                        <i class="fas fa-edit mr-1"></i> Editar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                      <!-- List View (Hidden by default) -->
                    <div id="list-view" class="hidden">
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Turma</th>
                                        <th>Curso</th>
                                        <th>Período</th>
                                        <th>Coordenador</th>
                                        <th>Alunos</th>
                                        <th>Disciplinas</th>
                                        <th>Status</th>
                                        <th class="w-20 text-right">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="font-medium">SI-2023/1</td>
                                        <td>Sistemas de Informação</td>
                                        <td>2023/1</td>
                                        <td>Prof. João Silva</td>
                                        <td>32</td>
                                        <td>8</td>
                                        <td><span class="status-badge ativo">Ativa</span></td>
                                        <td>
                                            <div class="flex justify-end space-x-1">
                                                <button class="btn btn-sm btn-outline view-class-btn" data-id="1">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline edit-class-btn" data-id="1">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline delete-class-btn" data-id="1">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="font-medium">ADM-2023/1</td>
                                        <td>Administração</td>
                                        <td>2023/1</td>
                                        <td>Prof. Carlos Alberto</td>
                                        <td>28</td>
                                        <td>7</td>
                                        <td><span class="status-badge ativo">Ativa</span></td>
                                        <td>
                                            <div class="flex justify-end space-x-1">
                                                <button class="btn btn-sm btn-outline view-class-btn" data-id="2">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline edit-class-btn" data-id="2">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline delete-class-btn" data-id="2">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="font-medium">DIR-2023/1</td>
                                        <td>Direito</td>
                                        <td>2023/1</td>
                                        <td>Profa. Maria Santos</td>
                                        <td>35</td>
                                        <td>8</td>
                                        <td><span class="status-badge ativo">Ativa</span></td>
                                        <td>
                                            <div class="flex justify-end space-x-1">
                                                <button class="btn btn-sm btn-outline view-class-btn" data-id="3">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline edit-class-btn" data-id="3">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline delete-class-btn" data-id="3">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="font-medium">PED-2023/1</td>
                                        <td>Pedagogia</td>
                                        <td>2023/1</td>
                                        <td>Profa. Amanda Oliveira</td>
                                        <td>25</td>
                                        <td>6</td>
                                        <td><span class="status-badge ativo">Ativa</span></td>
                                        <td>
                                            <div class="flex justify-end space-x-1">
                                                <button class="btn btn-sm btn-outline view-class-btn" data-id="4">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline edit-class-btn" data-id="4">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline delete-class-btn" data-id="4">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="font-medium">CONT-2023/1</td>
                                        <td>Ciências Contábeis</td>
                                        <td>2023/1</td>
                                        <td>Prof. Ricardo Melo</td>
                                        <td>22</td>
                                        <td>7</td>
                                        <td><span class="status-badge ativo">Ativa</span></td>
                                        <td>
                                            <div class="flex justify-end space-x-1">
                                                <button class="btn btn-sm btn-outline view-class-btn" data-id="5">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline edit-class-btn" data-id="5">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline delete-class-btn" data-id="5">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="font-medium">ADM-2022/2</td>
                                        <td>Administração</td>
                                        <td>2022/2</td>
                                        <td>Prof. Carlos Alberto</td>
                                        <td>30</td>
                                        <td>7</td>
                                        <td><span class="status-badge inativo">Encerrada</span></td>
                                        <td>
                                            <div class="flex justify-end space-x-1">
                                                <button class="btn btn-sm btn-outline view-class-btn" data-id="6">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline edit-class-btn" data-id="6">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline delete-class-btn" data-id="6">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="flex items-center justify-between mt-6 bg-white p-4 rounded-lg shadow-sm">
                        <div class="text-sm text-gray-600">
                            Mostrando 1-6 de 12 turmas
                        </div>
                        <div class="flex items-center space-x-2">
                            <button class="btn btn-sm btn-outline opacity-50 cursor-not-allowed">
                                <i class="fas fa-chevron-left mr-1"></i> Anterior
                            </button>
                            <div class="flex space-x-1">
                                <button class="w-8 h-8 flex items-center justify-center rounded-md bg-indigo-100 text-indigo-700 font-medium">1</button>
                                <button class="w-8 h-8 flex items-center justify-center rounded-md hover:bg-gray-100">2</button>
                            </div>
                            <button class="btn btn-sm btn-outline">
                                Próxima <i class="fas fa-chevron-right ml-1"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal de Nova/Editar Turma -->
    <div id="class-modal" class="modal">
        <div class="modal-content max-w-2xl">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h2 id="modal-title" class="text-xl font-bold text-gray-800">Nova Turma</h2>
                    <button class="text-gray-400 hover:text-gray-600 close-modal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            
            <div class="p-6">
                <form id="class-form">
                    <input type="hidden" id="class_id" name="id" value="">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="nome" class="form-label">Nome da Turma <span class="text-red-500">*</span></label>
                            <input type="text" id="nome" name="nome" class="form-input" placeholder="Ex: SI-2023/1" required>
                        </div>
                        
                        <div>
                            <label for="curso_id" class="form-label">Curso <span class="text-red-500">*</span></label>
                            <select id="curso_id" name="curso_id" class="form-select" required>
                                <option value="">Selecione o curso</option>
                                <option value="1">Administração</option>
                                <option value="2">Direito</option>
                                <option value="3">Sistemas de Informação</option>
                                <option value="4">Pedagogia</option>
                                <option value="5">Ciências Contábeis</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="periodo" class="form-label">Período <span class="text-red-500">*</span></label>
                            <select id="periodo" name="periodo" class="form-select" required>
                                <option value="">Selecione o período</option>
                                <option value="2023/1">2023/1</option>
                                <option value="2023/2">2023/2</option>
                                <option value="2022/2">2022/2</option>
                                <option value="2022/1">2022/1</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="coordenador_id" class="form-label">Coordenador <span class="text-red-500">*</span></label>
                            <select id="coordenador_id" name="coordenador_id" class="form-select" required>
                                <option value="">Selecione o coordenador</option>
                                <option value="1">Prof. João Silva</option>
                                <option value="2">Prof. Carlos Alberto</option>
                                <option value="3">Profa. Maria Santos</option>
                                <option value="4">Profa. Amanda Oliveira</option>
                                <option value="5">Prof. Ricardo Melo</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="data_inicio" class="form-label">Data de Início <span class="text-red-500">*</span></label>
                            <input type="date" id="data_inicio" name="data_inicio" class="form-input" required>
                        </div>
                        
                        <div>
                            <label for="data_fim" class="form-label">Data de Término</label>
                            <input type="date" id="data_fim" name="data_fim" class="form-input">
                        </div>
                        
                        <div>
                            <label for="vagas" class="form-label">Número de Vagas <span class="text-red-500">*</span></label>
                            <input type="number" id="vagas" name="vagas" class="form-input" min="1" value="40" required>
                        </div>
                        
                        <div>
                            <label for="status" class="form-label">Status <span class="text-red-500">*</span></label>
                            <select id="status" name="status" class="form-select" required>
                                <option value="ativo">Ativa</option>
                                <option value="inativo">Encerrada</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <div class="flex justify-between items-center mb-2">
                            <label class="form-label m-0">Disciplinas</label>
                            <button type="button" class="text-sm text-indigo-600 hover:text-indigo-800">
                                <i class="fas fa-plus-circle"></i> Adicionar Disciplina
                            </button>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead>
                                        <tr>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Disciplina</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Professor</th>
                                            <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Carga Horária</th>
                                            <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        <tr>
                                            <td class="px-3 py-2">Banco de Dados</td>
                                            <td class="px-3 py-2">Prof. João Silva</td>
                                            <td class="px-3 py-2 text-center">80h</td>
                                            <td class="px-3 py-2 text-center">
                                                <button type="button" class="text-red-500 hover:text-red-700">
                                                    <i class="fas fa-times-circle"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="px-3 py-2">Programação Web</td>
                                            <td class="px-3 py-2">Profa. Luciana Ferreira</td>
                                            <td class="px-3 py-2 text-center">60h</td>
                                            <td class="px-3 py-2 text-center">
                                                <button type="button" class="text-red-500 hover:text-red-700">
                                                    <i class="fas fa-times-circle"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <label for="observacoes" class="form-label">Observações</label>
                        <textarea id="observacoes" name="observacoes" class="form-textarea" rows="3" placeholder="Informações adicionais sobre a turma"></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" class="btn btn-outline close-modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="submit-btn">Salvar Turma</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal de Visualização de Turma -->
    <div id="view-class-modal" class="modal">
        <div class="modal-content max-w-4xl">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-bold text-gray-800">Detalhes da Turma</h2>
                    <button class="text-gray-400 hover:text-gray-600 close-modal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            
            <div class="p-6">
                <div class="bg-indigo-50 p-4 rounded-lg mb-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="text-lg font-bold text-indigo-800" id="view-turma-nome">SI-2023/1</h3>
                            <p class="text-indigo-600" id="view-curso">Sistemas de Informação</p>
                        </div>
                        <div>
                            <span class="status-badge ativo" id="view-status">Ativa</span>
                        </div>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div class="bg-white p-4 rounded-lg border border-gray-200">
                        <p class="text-sm text-gray-500">Período</p>
                        <p class="font-medium" id="view-periodo">2023/1</p>
                    </div>
                    <div class="bg-white p-4 rounded-lg border border-gray-200">
                        <p class="text-sm text-gray-500">Data de Início</p>
                        <p class="font-medium" id="view-data-inicio">01/02/2023</p>
                    </div>
                    <div class="bg-white p-4 rounded-lg border border-gray-200">
                        <p class="text-sm text-gray-500">Data de Término</p>
                        <p class="font-medium" id="view-data-fim">30/06/2023</p>
                    </div>
                    <div class="bg-white p-4 rounded-lg border border-gray-200">
                        <p class="text-sm text-gray-500">Coordenador</p>
                        <div class="flex items-center mt-1">
                            <img src="/api/placeholder/32/32" alt="Coordenador" class="h-8 w-8 rounded-full mr-2">
                            <span class="font-medium" id="view-coordenador">Prof. João Silva</span>
                        </div>
                    </div>
                    <div class="bg-white p-4 rounded-lg border border-gray-200">
                        <p class="text-sm text-gray-500">Total de Alunos</p>
                        <p class="font-medium" id="view-total-alunos">32 / 40 vagas</p>
                    </div>
                    <div class="bg-white p-4 rounded-lg border border-gray-200">
                        <p class="text-sm text-gray-500">Total de Disciplinas</p>
                        <p class="font-medium" id="view-total-disciplinas">8 disciplinas</p>
                    </div>
                </div>
                
                <div class="mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">Disciplinas</h3>
                        <button class="btn btn-sm btn-outline">
                            <i class="fas fa-print mr-1"></i> Imprimir Horário
                        </button>
                    </div>
                    
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Disciplina</th>
                                    <th>Professor</th>
                                    <th>Carga Horária</th>
                                    <th>Horário</th>
                                    <th>Alunos</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>SI302</td>
                                    <td class="font-medium">Banco de Dados</td>
                                    <td>Prof. João Silva</td>
                                    <td class="text-center">80h</td>
                                    <td>Seg/Qua 19h-22h</td>
                                    <td class="text-center">32</td>
                                </tr>
                                <tr>
                                    <td>SI304</td>
                                    <td class="font-medium">Programação Web</td>
                                    <td>Profa. Luciana Ferreira</td>
                                    <td class="text-center">60h</td>
                                    <td>Ter/Qui 19h-21h30</td>
                                    <td class="text-center">30</td>
                                </tr>
                                <tr>
                                    <td>SI305</td>
                                    <td class="font-medium">Engenharia de Software</td>
                                    <td>Prof. Roberto Almeida</td>
                                    <td class="text-center">80h</td>
                                    <td>Seg/Qua 14h-17h</td>
                                    <td class="text-center">28</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">Alunos</h3>
                        <div>
                            <button class="btn btn-sm btn-outline mr-2">
                                <i class="fas fa-user-plus mr-1"></i> Adicionar Aluno
                            </button>
                            <button class="btn btn-sm btn-outline">
                                <i class="fas fa-print mr-1"></i> Imprimir Lista
                            </button>
                        </div>
                    </div>
                    
                    <div class="bg-white p-4 rounded-lg border border-gray-200">
                        <div class="flex flex-wrap">
                            <div class="student-badge">
                                <img src="/api/placeholder/24/24" alt="Aluno" class="h-6 w-6 rounded-full mr-1">
                                João da Silva
                            </div>
                            <div class="student-badge">
                                <img src="/api/placeholder/24/24" alt="Aluno" class="h-6 w-6 rounded-full mr-1">
                                Maria Oliveira
                            </div>
                            <div class="student-badge">
                                <img src="/api/placeholder/24/24" alt="Aluno" class="h-6 w-6 rounded-full mr-1">
                                Carlos Santos
                            </div>
                            <div class="student-badge">
                                <img src="/api/placeholder/24/24" alt="Aluno" class="h-6 w-6 rounded-full mr-1">
                                Ana Souza
                            </div>
                            <div class="student-badge">
                                <img src="/api/placeholder/24/24" alt="Aluno" class="h-6 w-6 rounded-full mr-1">
                                Paulo Lima
                            </div>
                            <div class="student-badge">
                                <img src="/api/placeholder/24/24" alt="Aluno" class="h-6 w-6 rounded-full mr-1">
                                Luciana Costa
                            </div>
                            <div class="student-badge">
                                <img src="/api/placeholder/24/24" alt="Aluno" class="h-6 w-6 rounded-full mr-1">
                                Roberto Alves
                            </div>
                            <button class="student-badge bg-gray-200 text-gray-700">
                                +25 alunos
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Observações</h3>
                    <div class="bg-white p-4 rounded-lg border border-gray-200">
                        <p id="view-observacoes">
                            Turma do período noturno com alta demanda. Alguns alunos estão com pendência de documentação.
                        </p>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" class="btn btn-outline close-modal">Fechar</button>
                    <button type="button" class="btn btn-primary edit-class-btn" data-id="1">
                        <i class="fas fa-edit mr-2"></i> Editar
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal de Confirmação de Exclusão -->
    <div id="delete-modal" class="modal">
        <div class="modal-content max-w-md">
            <div class="p-6">
                <div class="flex items-center justify-center mb-4">
                    <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle text-3xl text-red-500"></i>
                    </div>
                </div>
                
                <h2 class="text-xl font-bold text-gray-800 text-center mb-4">Confirmar Exclusão</h2>
                
                <p class="text-gray-600 text-center mb-6">
                    Tem certeza que deseja excluir a turma "<span id="delete-class-name">SI-2023/1</span>"? Esta ação não pode ser desfeita.
                </p>
                
                <div class="flex justify-center space-x-3">
                    <button type="button" class="btn btn-outline close-modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="confirm-delete-btn">
                        <i class="fas fa-trash mr-2"></i> Excluir
                    </button>
                </div>
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
        
        // Toggle views (grid/list)
        const viewButtons = document.querySelectorAll('.view-btn');
        const gridView = document.getElementById('grid-view');
        const listView = document.getElementById('list-view');
        
        viewButtons.forEach(button => {
            button.addEventListener('click', function() {
                const viewType = this.getAttribute('data-view');
                
                // Remove active class from all buttons
                viewButtons.forEach(btn => btn.classList.remove('active'));
                
                // Add active class to clicked button
                this.classList.add('active');
                
                // Show/hide views
                if (viewType === 'grid') {
                    gridView.classList.remove('hidden');
                    listView.classList.add('hidden');
                } else {
                    gridView.classList.add('hidden');
                    listView.classList.remove('hidden');
                }
            });
        });
        
        // Modal management
        const modals = document.querySelectorAll('.modal');
        const newClassBtn = document.getElementById('new-class-btn');
        const viewClassButtons = document.querySelectorAll('.view-class-btn');
        const editClassButtons = document.querySelectorAll('.edit-class-btn');
        const deleteClassButtons = document.querySelectorAll('.delete-class-btn');
        const closeButtons = document.querySelectorAll('.close-modal');
        
        // Open new class modal
        newClassBtn.addEventListener('click', function() {
            // Reset form
            document.getElementById('class-form').reset();
            document.getElementById('class_id').value = '';
            document.getElementById('modal-title').textContent = 'Nova Turma';
            document.getElementById('submit-btn').textContent = 'Salvar Turma';
            
            // Open modal
            document.getElementById('class-modal').classList.add('open');
        });
        
        // Open view class modal
        viewClassButtons.forEach(button => {
            button.addEventListener('click', function() {
                const classId = this.getAttribute('data-id');
                console.log('Viewing class:', classId);
                
                // Em uma aplicação real, você buscaria os dados da turma
                // e preencheria o modal com os dados corretos
                
                document.getElementById('view-class-modal').classList.add('open');
            });
        });
        
        // Open edit class modal
        editClassButtons.forEach(button => {
            button.addEventListener('click', function() {
                const classId = this.getAttribute('data-id');
                console.log('Editing class:', classId);
                
                // Em uma aplicação real, você buscaria os dados da turma
                // e preencheria o formulário com os dados corretos
                
                document.getElementById('class_id').value = classId;
                document.getElementById('modal-title').textContent = 'Editar Turma';
                document.getElementById('submit-btn').textContent = 'Atualizar Turma';
                
                document.getElementById('class-modal').classList.add('open');
                
                // Fechar o modal de visualização se estiver aberto
                document.getElementById('view-class-modal').classList.remove('open');
            });
        });
        
        // Open delete confirmation modal
        deleteClassButtons.forEach(button => {
            button.addEventListener('click', function() {
                const classId = this.getAttribute('data-id');
                console.log('Deleting class:', classId);
                
                // Em uma aplicação real, você buscaria o nome da turma
                // para exibir na confirmação
                
                document.getElementById('delete-modal').classList.add('open');
            });
        });
        
        // Confirm delete
        document.getElementById('confirm-delete-btn').addEventListener('click', function() {
            // Em uma aplicação real, você enviaria uma requisição para excluir a turma
            console.log('Class deleted');
            
            // Fechar o modal
            document.getElementById('delete-modal').classList.remove('open');
            
            // Feedback para o usuário
            alert('Turma excluída com sucesso!');
            
            // Recarregar a lista de turmas
            // window.location.reload();
        });
        
        // Close modals
        closeButtons.forEach(button => {
            button.addEventListener('click', function() {
                modals.forEach(modal => {
                    modal.classList.remove('open');
                });
            });
        });
        
        // Close modal when clicking outside
        modals.forEach(modal => {
            modal.addEventListener('click', function(event) {
                if (event.target === this) {
                    this.classList.remove('open');
                }
            });
        });
        
        // Form submission
        document.getElementById('class-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Em uma aplicação real, você enviaria os dados para o servidor
            console.log('Form submitted');
            
            // Fechar o modal
            document.getElementById('class-modal').classList.remove('open');
            
            // Feedback para o usuário
            const classId = document.getElementById('class_id').value;
            const message = classId ? 'Turma atualizada com sucesso!' : 'Turma criada com sucesso!';
            alert(message);
            
            // Recarregar a lista de turmas
            // window.location.reload();
        });
    </script>
</body>
</html>