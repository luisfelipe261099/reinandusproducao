                   <!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faciência ERP - Gestão de Polos</title>
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
        
        .status-badge.suspenso {
            background-color: #FEF3C7;
            color: #92400E;
        }
        
        .status-badge.encerrado {
            background-color: #F3F4F6;
            color: #4B5563;
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
        
        .polo-card {
            transition: all 0.2s;
        }
        
        .polo-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        .progress-bar {
            height: 8px;
            border-radius: 4px;
            background-color: #E5E7EB;
            overflow: hidden;
        }
        
        .progress-bar-fill {
            height: 100%;
            background-color: var(--primary);
        }
        
        .progress-bar-fill.warning {
            background-color: var(--warning);
        }
        
        .progress-bar-fill.danger {
            background-color: var(--danger);
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
                    <a href="secretaria_turmas.php" class="nav-item flex items-center py-3 px-4 text-white">
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
                    <a href="secretaria_polo.php" class="nav-item active flex items-center py-3 px-4 text-white">
                        <i class="fas fa-building w-6"></i>
                        <span class="sidebar-label ml-3">Polos</span>
                    </a>
                    <a href="secretaria_solicit_documento.php" class="nav-item flex items-center py-3 px-4 text-white">
                        <i class="fas fa-certificate w-6"></i>
                        <span class="sidebar-label ml-3">Documentos</span>
                    </a>
                    <a href="secretaria_notas.php" class="nav-item flex items-center py-3 px-4 text-white">
                        <i class="fas fa-chart-line w-6"></i>
                        <span class="sidebar-label ml-3">Notas</span>
                    </a>
                    <a href="secretaria_planos.php" class="nav-item flex items-center py-3 px-4 text-white">
                        <i class="fas fa-clipboard-list w-6"></i>
                        <span class="sidebar-label ml-3">Planos</span>
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
                            <input type="text" class="pl-10 pr-4 py-2 border border-gray-300 rounded-full text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 w-64" placeholder="Buscar polo...">
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
                            <h1 class="text-2xl font-bold text-gray-800">Gestão de Polos</h1>
                            <p class="text-gray-600">Gerencie os polos parceiros e seus limites de documentos</p>
                        </div>
                        <div>
                            <button id="new-polo-btn" class="btn btn-primary">
                                <i class="fas fa-plus mr-2"></i>
                                <span>Novo Polo</span>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Filters -->
                    <div class="bg-white p-4 rounded-lg border border-gray-200 mb-6">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                            <div class="flex space-x-2">
                                <div class="relative">
                                    <select class="form-select py-2 pl-3 pr-10 text-sm" id="filter-estado">
                                        <option value="">Todos os Estados</option>
                                        <option value="SP">São Paulo</option>
                                        <option value="MG">Minas Gerais</option>
                                        <option value="RJ">Rio de Janeiro</option>
                                        <option value="ES">Espírito Santo</option>
                                        <option value="BA">Bahia</option>
                                    </select>
                                </div>
                                
                                <div class="relative">
                                    <select class="form-select py-2 pl-3 pr-10 text-sm" id="filter-status">
                                        <option value="">Todos os Status</option>
                                        <option value="ativo">Ativos</option>
                                        <option value="suspenso">Suspensos</option>
                                        <option value="encerrado">Encerrados</option>
                                        <option value="inativo">Inativos</option>
                                    </select>
                                </div>
                                
                                <div class="relative">
                                    <select class="form-select py-2 pl-3 pr-10 text-sm" id="filter-limite">
                                        <option value="">Todos os Limites</option>
                                        <option value="normal">Dentro do Limite</option>
                                        <option value="alerta">Em Alerta (>75%)</option>
                                        <option value="excedido">Excedido (100%)</option>
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
                        <!-- Polo Card 1 -->
                        <div class="polo-card bg-white rounded-lg shadow-md overflow-hidden">
                            <div class="bg-blue-600 p-4 text-white">
                                <div class="flex justify-between items-center">
                                    <h3 class="font-bold text-lg">Polo São Paulo</h3>
                                    <span class="status-badge ativo bg-white text-blue-700">Ativo</span>
                                </div>
                                <p class="text-blue-100">São Paulo, SP</p>
                            </div>
                            
                            <div class="p-4">
                                <div class="mb-4">
                                    <div class="flex justify-between mb-1">
                                        <p class="text-sm text-gray-600">Limite de Documentos</p>
                                        <p class="text-sm font-medium">65/100</p>
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress-bar-fill" style="width: 65%"></div>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <p class="text-sm text-gray-500">Responsável</p>
                                        <div class="flex items-center mt-1">
                                            <img src="/api/placeholder/24/24" alt="Responsável" class="h-6 w-6 rounded-full mr-2">
                                            <span class="font-medium text-sm">Ricardo Santos</span>
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Contrato</p>
                                        <p class="font-medium text-sm">Até 31/12/2025</p>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <p class="text-sm text-gray-500">Alunos</p>
                                        <p class="font-medium">156</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Turmas</p>
                                        <p class="font-medium">8</p>
                                    </div>
                                </div>
                                
                                <div class="flex justify-end space-x-2">
                                    <button class="btn btn-sm btn-outline view-polo-btn" data-id="1">
                                        <i class="fas fa-eye mr-1"></i> Detalhes
                                    </button>
                                    <button class="btn btn-sm btn-primary edit-polo-btn" data-id="1">
                                        <i class="fas fa-edit mr-1"></i> Editar
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Polo Card 2 -->
                        <div class="polo-card bg-white rounded-lg shadow-md overflow-hidden">
                            <div class="bg-purple-600 p-4 text-white">
                                <div class="flex justify-between items-center">
                                    <h3 class="font-bold text-lg">Polo Belo Horizonte</h3>
                                    <span class="status-badge ativo bg-white text-purple-700">Ativo</span>
                                </div>
                                <p class="text-purple-100">Belo Horizonte, MG</p>
                            </div>
                            
                            <div class="p-4">
                                <div class="mb-4">
                                    <div class="flex justify-between mb-1">
                                        <p class="text-sm text-gray-600">Limite de Documentos</p>
                                        <p class="text-sm font-medium">82/100</p>
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress-bar-fill warning" style="width: 82%"></div>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <p class="text-sm text-gray-500">Responsável</p>
                                        <div class="flex items-center mt-1">
                                            <img src="/api/placeholder/24/24" alt="Responsável" class="h-6 w-6 rounded-full mr-2">
                                            <span class="font-medium text-sm">Ana Oliveira</span>
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Contrato</p>
                                        <p class="font-medium text-sm">Até 15/08/2025</p>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <p class="text-sm text-gray-500">Alunos</p>
                                        <p class="font-medium">124</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Turmas</p>
                                        <p class="font-medium">6</p>
                                    </div>
                                </div>
                                
                                <div class="flex justify-end space-x-2">
                                    <button class="btn btn-sm btn-outline view-polo-btn" data-id="2">
                                        <i class="fas fa-eye mr-1"></i> Detalhes
                                    </button>
                                    <button class="btn btn-sm btn-primary edit-polo-btn" data-id="2">
                                        <i class="fas fa-edit mr-1"></i> Editar
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Polo Card 3 -->
                        <div class="polo-card bg-white rounded-lg shadow-md overflow-hidden">
                            <div class="bg-green-600 p-4 text-white">
                                <div class="flex justify-between items-center">
                                    <h3 class="font-bold text-lg">Polo Rio de Janeiro</h3>
                                    <span class="status-badge ativo bg-white text-green-700">Ativo</span>
                                </div>
                                <p class="text-green-100">Rio de Janeiro, RJ</p>
                            </div>
                            
                            <div class="p-4">
                                <div class="mb-4">
                                    <div class="flex justify-between mb-1">
                                        <p class="text-sm text-gray-600">Limite de Documentos</p>
                                        <p class="text-sm font-medium">45/100</p>
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress-bar-fill" style="width: 45%"></div>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <p class="text-sm text-gray-500">Responsável</p>
                                        <div class="flex items-center mt-1">
                                            <img src="/api/placeholder/24/24" alt="Responsável" class="h-6 w-6 rounded-full mr-2">
                                            <span class="font-medium text-sm">Carlos Mendes</span>
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Contrato</p>
                                        <p class="font-medium text-sm">Até 20/03/2026</p>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <p class="text-sm text-gray-500">Alunos</p>
                                        <p class="font-medium">142</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Turmas</p>
                                        <p class="font-medium">7</p>
                                    </div>
                                </div>
                                
                                <div class="flex justify-end space-x-2">
                                    <button class="btn btn-sm btn-outline view-polo-btn" data-id="3">
                                        <i class="fas fa-eye mr-1"></i> Detalhes
                                    </button>
                                    <button class="btn btn-sm btn-primary edit-polo-btn" data-id="3">
                                        <i class="fas fa-edit mr-1"></i> Editar
                                    </button>
                                </div>
                            </div>
                        </div>
                        </div>
                        
                        <!-- Polo Card 4 -->
                        <div class="polo-card bg-white rounded-lg shadow-md overflow-hidden">
                            <div class="bg-red-600 p-4 text-white">
                                <div class="flex justify-between items-center">
                                    <h3 class="font-bold text-lg">Polo Salvador</h3>
                                    <span class="status-badge ativo bg-white text-red-700">Ativo</span>
                                </div>
                                <p class="text-red-100">Salvador, BA</p>
                            </div>
                            
                            <div class="p-4">
                                <div class="mb-4">
                                    <div class="flex justify-between mb-1">
                                        <p class="text-sm text-gray-600">Limite de Documentos</p>
                                        <p class="text-sm font-medium">98/100</p>
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress-bar-fill danger" style="width: 98%"></div>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <p class="text-sm text-gray-500">Responsável</p>
                                        <div class="flex items-center mt-1">
                                            <img src="/api/placeholder/24/24" alt="Responsável" class="h-6 w-6 rounded-full mr-2">
                                            <span class="font-medium text-sm">Mariana Costa</span>
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Contrato</p>
                                        <p class="font-medium text-sm">Até 10/11/2025</p>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <p class="text-sm text-gray-500">Alunos</p>
                                        <p class="font-medium">118</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Turmas</p>
                                        <p class="font-medium">5</p>
                                    </div>
                                </div>
                                
                                <div class="flex justify-end space-x-2">
                                    <button class="btn btn-sm btn-outline view-polo-btn" data-id="4">
                                        <i class="fas fa-eye mr-1"></i> Detalhes
                                    </button>
                                    <button class="btn btn-sm btn-primary edit-polo-btn" data-id="4">
                                        <i class="fas fa-edit mr-1"></i> Editar
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Polo Card 5 -->
                        <div class="polo-card bg-white rounded-lg shadow-md overflow-hidden">
                            <div class="bg-yellow-600 p-4 text-white">
                                <div class="flex justify-between items-center">
                                    <h3 class="font-bold text-lg">Polo Vitória</h3>
                                    <span class="status-badge ativo bg-white text-yellow-700">Ativo</span>
                                </div>
                                <p class="text-yellow-100">Vitória, ES</p>
                            </div>
                            
                            <div class="p-4">
                                <div class="mb-4">
                                    <div class="flex justify-between mb-1">
                                        <p class="text-sm text-gray-600">Limite de Documentos</p>
                                        <p class="text-sm font-medium">53/100</p>
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress-bar-fill" style="width: 53%"></div>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <p class="text-sm text-gray-500">Responsável</p>
                                        <div class="flex items-center mt-1">
                                            <img src="/api/placeholder/24/24" alt="Responsável" class="h-6 w-6 rounded-full mr-2">
                                            <span class="font-medium text-sm">Pedro Almeida</span>
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Contrato</p>
                                        <p class="font-medium text-sm">Até 05/09/2025</p>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <p class="text-sm text-gray-500">Alunos</p>
                                        <p class="font-medium">88</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Turmas</p>
                                        <p class="font-medium">4</p>
                                    </div>
                                </div>
                                
                                <div class="flex justify-end space-x-2">
                                    <button class="btn btn-sm btn-outline view-polo-btn" data-id="5">
                                        <i class="fas fa-eye mr-1"></i> Detalhes
                                    </button>
                                    <button class="btn btn-sm btn-primary edit-polo-btn" data-id="5">
                                        <i class="fas fa-edit mr-1"></i> Editar
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Polo Card 6 -->
                        <div class="polo-card bg-white rounded-lg shadow-md overflow-hidden">
                            <div class="bg-gray-600 p-4 text-white">
                                <div class="flex justify-between items-center">
                                    <h3 class="font-bold text-lg">Polo Campinas</h3>
                                    <span class="status-badge suspenso bg-white text-gray-700">Suspenso</span>
                                </div>
                                <p class="text-gray-300">Campinas, SP</p>
                            </div>
                            
                            <div class="p-4">
                                <div class="mb-4">
                                    <div class="flex justify-between mb-1">
                                        <p class="text-sm text-gray-600">Limite de Documentos</p>
                                        <p class="text-sm font-medium">100/100</p>
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress-bar-fill danger" style="width: 100%"></div>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <p class="text-sm text-gray-500">Responsável</p>
                                        <div class="flex items-center mt-1">
                                            <img src="/api/placeholder/24/24" alt="Responsável" class="h-6 w-6 rounded-full mr-2">
                                            <span class="font-medium text-sm">Fernando Silva</span>
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Contrato</p>
                                        <p class="font-medium text-sm">Até 15/06/2025</p>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <p class="text-sm text-gray-500">Alunos</p>
                                        <p class="font-medium">72</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Turmas</p>
                                        <p class="font-medium">3</p>
                                    </div>
                                </div>
                                
                                <div class="flex justify-end space-x-2">
                                    <button class="btn btn-sm btn-outline view-polo-btn" data-id="6">
                                        <i class="fas fa-eye mr-1"></i> Detalhes
                                    </button>
                                    <button class="btn btn-sm btn-primary edit-polo-btn" data-id="6">
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
                                        <th>Nome</th>
                                        <th>Cidade/UF</th>
                                        <th>Responsável</th>
                                        <th>Alunos</th>
                                        <th>Documentos</th>
                                        <th>Contrato</th>
                                        <th>Status</th>
                                        <th class="w-20 text-right">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="font-medium">Polo São Paulo</td>
                                        <td>São Paulo, SP</td>
                                        <td>Ricardo Santos</td>
                                        <td>156</td>
                                        <td>
                                            <div class="flex items-center">
                                                <div class="w-20 h-2 bg-gray-200 rounded mr-2">
                                                    <div class="h-2 bg-blue-500 rounded" style="width: 65%"></div>
                                                </div>
                                                <span class="text-sm">65/100</span>
                                            </div>
                                        </td>
                                        <td>31/12/2025</td>
                                        <td><span class="status-badge ativo">Ativo</span></td>
                                        <td>
                                            <div class="flex justify-end space-x-1">
                                                <button class="btn btn-sm btn-outline view-polo-btn" data-id="1">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline edit-polo-btn" data-id="1">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline suspend-polo-btn" data-id="1">
                                                    <i class="fas fa-pause"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="font-medium">Polo Belo Horizonte</td>
                                        <td>Belo Horizonte, MG</td>
                                        <td>Ana Oliveira</td>
                                        <td>124</td>
                                        <td>
                                            <div class="flex items-center">
                                                <div class="w-20 h-2 bg-gray-200 rounded mr-2">
                                                    <div class="h-2 bg-yellow-500 rounded" style="width: 82%"></div>
                                                </div>
                                                <span class="text-sm">82/100</span>
                                            </div>
                                        </td>
                                        <td>15/08/2025</td>
                                        <td><span class="status-badge ativo">Ativo</span></td>
                                        <td>
                                            <div class="flex justify-end space-x-1">
                                                <button class="btn btn-sm btn-outline view-polo-btn" data-id="2">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline edit-polo-btn" data-id="2">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline suspend-polo-btn" data-id="2">
                                                    <i class="fas fa-pause"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="font-medium">Polo Rio de Janeiro</td>
                                        <td>Rio de Janeiro, RJ</td>
                                        <td>Carlos Mendes</td>
                                        <td>142</td>
                                        <td>
                                            <div class="flex items-center">
                                                <div class="w-20 h-2 bg-gray-200 rounded mr-2">
                                                    <div class="h-2 bg-blue-500 rounded" style="width: 45%"></div>
                                                </div>
                                                <span class="text-sm">45/100</span>
                                            </div>
                                        </td>
                                        <td>20/03/2026</td>
                                        <td><span class="status-badge ativo">Ativo</span></td>
                                        <td>
                                            <div class="flex justify-end space-x-1">
                                                <button class="btn btn-sm btn-outline view-polo-btn" data-id="3">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline edit-polo-btn" data-id="3">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline suspend-polo-btn" data-id="3">
                                                    <i class="fas fa-pause"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="font-medium">Polo Salvador</td>
                                        <td>Salvador, BA</td>
                                        <td>Mariana Costa</td>
                                        <td>118</td>
                                        <td>
                                            <div class="flex items-center">
                                                <div class="w-20 h-2 bg-gray-200 rounded mr-2">
                                                    <div class="h-2 bg-red-500 rounded" style="width: 98%"></div>
                                                </div>
                                                <span class="text-sm">98/100</span>
                                            </div>
                                        </td>
                                        <td>10/11/2025</td>
                                        <td><span class="status-badge ativo">Ativo</span></td>
                                        <td>
                                            <div class="flex justify-end space-x-1">
                                                <button class="btn btn-sm btn-outline view-polo-btn" data-id="4">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline edit-polo-btn" data-id="4">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline suspend-polo-btn" data-id="4">
                                                    <i class="fas fa-pause"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="font-medium">Polo Vitória</td>
                                        <td>Vitória, ES</td>
                                        <td>Pedro Almeida</td>
                                        <td>88</td>
                                        <td>
                                            <div class="flex items-center">
                                                <div class="w-20 h-2 bg-gray-200 rounded mr-2">
                                                    <div class="h-2 bg-blue-500 rounded" style="width: 53%"></div>
                                                </div>
                                                <span class="text-sm">53/100</span>
                                            </div>
                                        </td>
                                        <td>05/09/2025</td>
                                        <td><span class="status-badge ativo">Ativo</span></td>
                                        <td>
                                            <div class="flex justify-end space-x-1">
                                                <button class="btn btn-sm btn-outline view-polo-btn" data-id="5">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline edit-polo-btn" data-id="5">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline suspend-polo-btn" data-id="5">
                                                    <i class="fas fa-pause"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="font-medium">Polo Campinas</td>
                                        <td>Campinas, SP</td>
                                        <td>Fernando Silva</td>
                                        <td>72</td>
                                        <td>
                                            <div class="flex items-center">
                                                <div class="w-20 h-2 bg-gray-200 rounded mr-2">
                                                    <div class="h-2 bg-red-500 rounded" style="width: 100%"></div>
                                                </div>
                                                <span class="text-sm">100/100</span>
                                            </div>
                                        </td>
                                        <td>15/06/2025</td>
                                        <td><span class="status-badge suspenso">Suspenso</span></td>
                                        <td>
                                            <div class="flex justify-end space-x-1">
                                                <button class="btn btn-sm btn-outline view-polo-btn" data-id="6">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline edit-polo-btn" data-id="6">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline activate-polo-btn" data-id="6">
                                                    <i class="fas fa-play"></i>
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
                            Mostrando 1-6 de 12 polos
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

    <!-- Modal de Novo/Editar Polo -->
    <div id="polo-modal" class="modal">
        <div class="modal-content max-w-2xl">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h2 id="modal-title" class="text-xl font-bold text-gray-800">Novo Polo</h2>
                    <button class="text-gray-400 hover:text-gray-600 close-modal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            
            <div class="p-6">
                <form id="polo-form">
                    <input type="hidden" id="polo_id" name="id" value="">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="nome" class="form-label">Nome do Polo <span class="text-red-500">*</span></label>
                            <input type="text" id="nome" name="nome" class="form-input" placeholder="Ex: Polo São Paulo" required>
                        </div>
                        
                        <div>
                            <label for="razao_social" class="form-label">Razão Social <span class="text-red-500">*</span></label>
                            <input type="text" id="razao_social" name="razao_social" class="form-input" placeholder="Razão social da instituição" required>
                        </div>
                        
                        <div>
                            <label for="cnpj" class="form-label">CNPJ <span class="text-red-500">*</span></label>
                            <input type="text" id="cnpj" name="cnpj" class="form-input" placeholder="Ex: 00.000.000/0000-00" required>
                        </div>
                        
                        <div>
                            <label for="cidade_id" class="form-label">Cidade <span class="text-red-500">*</span></label>
                            <select id="cidade_id" name="cidade_id" class="form-select" required>
                                <option value="">Selecione a cidade</option>
                                <option value="1">São Paulo - SP</option>
                                <option value="2">Belo Horizonte - MG</option>
                                <option value="3">Rio de Janeiro - RJ</option>
                                <option value="4">Salvador - BA</option>
                                <option value="5">Vitória - ES</option>
                                <option value="6">Campinas - SP</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="endereco" class="form-label">Endereço <span class="text-red-500">*</span></label>
                            <input type="text" id="endereco" name="endereco" class="form-input" placeholder="Endereço completo" required>
                        </div>
                        
                        <div>
                            <label for="responsavel_id" class="form-label">Responsável <span class="text-red-500">*</span></label>
                            <select id="responsavel_id" name="responsavel_id" class="form-select" required>
                                <option value="">Selecione o responsável</option>
                                <option value="1">Ricardo Santos</option>
                                <option value="2">Ana Oliveira</option>
                                <option value="3">Carlos Mendes</option>
                                <option value="4">Mariana Costa</option>
                                <option value="5">Pedro Almeida</option>
                                <option value="6">Fernando Silva</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="email" class="form-label">E-mail <span class="text-red-500">*</span></label>
                            <input type="email" id="email" name="email" class="form-input" placeholder="email@exemplo.com" required>
                        </div>
                        
                        <div>
                            <label for="telefone" class="form-label">Telefone <span class="text-red-500">*</span></label>
                            <input type="text" id="telefone" name="telefone" class="form-input" placeholder="(00) 0000-0000" required>
                        </div>
                        
                        <div>
                            <label for="data_inicio_parceria" class="form-label">Data de Início da Parceria <span class="text-red-500">*</span></label>
                            <input type="date" id="data_inicio_parceria" name="data_inicio_parceria" class="form-input" required>
                        </div>
                        
                        <div>
                            <label for="data_fim_contrato" class="form-label">Data de Fim do Contrato <span class="text-red-500">*</span></label>
                            <input type="date" id="data_fim_contrato" name="data_fim_contrato" class="form-input" required>
                        </div>
                        
                        <div>
                            <label for="limite_documentos" class="form-label">Limite de Documentos <span class="text-red-500">*</span></label>
                            <input type="number" id="limite_documentos" name="limite_documentos" class="form-input" min="1" value="100" required>
                        </div>
                        
                        <div>
                            <label for="status_contrato" class="form-label">Status do Contrato <span class="text-red-500">*</span></label>
                            <select id="status_contrato" name="status_contrato" class="form-select" required>
                                <option value="ativo">Ativo</option>
                                <option value="suspenso">Suspenso</option>
                                <option value="encerrado">Encerrado</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="status" class="form-label">Status <span class="text-red-500">*</span></label>
                            <select id="status" name="status" class="form-select" required>
                                <option value="ativo">Ativo</option>
                                <option value="inativo">Inativo</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <label for="observacoes" class="form-label">Observações</label>
                        <textarea id="observacoes" name="observacoes" class="form-textarea" rows="3" placeholder="Informações adicionais sobre o polo"></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" class="btn btn-outline close-modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="submit-btn">Salvar Polo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal de Visualização de Polo -->
    <div id="view-polo-modal" class="modal">
        <div class="modal-content max-w-4xl">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-bold text-gray-800">Detalhes do Polo</h2>
                    <button class="text-gray-400 hover:text-gray-600 close-modal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            
            <div class="p-6">
                <div class="bg-blue-50 p-4 rounded-lg mb-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="text-lg font-bold text-blue-800" id="view-polo-nome">Polo São Paulo</h3>
                            <p class="text-blue-600" id="view-razao-social">Centro Educacional São Paulo LTDA</p>
                        </div>
                        <div>
                            <span class="status-badge ativo" id="view-status">Ativo</span>
                        </div>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <div class="bg-white p-4 rounded-lg border border-gray-200 mb-6">
                            <h4 class="font-semibold text-gray-700 mb-4">Informações Gerais</h4>
                            <div class="grid grid-cols-1 gap-3">
                                <div>
                                    <p class="text-sm text-gray-500">CNPJ</p>
                                    <p class="font-medium" id="view-cnpj">12.345.678/0001-90</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Endereço</p>
                                    <p class="font-medium" id="view-endereco">Av. Paulista, 1000, São Paulo - SP</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Contato</p>
                                    <p class="font-medium" id="view-contato">contato@polosaopaulo.com.br | (11) 3456-7890</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-white p-4 rounded-lg border border-gray-200">
                            <h4 class="font-semibold text-gray-700 mb-4">Responsável</h4>
                            <div class="flex items-center mb-3">
                                <img src="/api/placeholder/48/48" alt="Responsável" class="h-12 w-12 rounded-full mr-3">
                                <div>
                                    <p class="font-medium" id="view-responsavel-nome">Ricardo Santos</p>
                                    <p class="text-sm text-gray-500" id="view-responsavel-email">ricardo@polosaopaulo.com.br</p>
                                </div>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Telefone</p>
                                <p class="font-medium" id="view-responsavel-telefone">(11) 99876-5432</p>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="bg-white p-4 rounded-lg border border-gray-200 mb-6">
                            <h4 class="font-semibold text-gray-700 mb-4">Contrato e Documentos</h4>
                            <div class="grid grid-cols-1 gap-3">
                                <div>
                                    <p class="text-sm text-gray-500">Data de Início da Parceria</p>
                                    <p class="font-medium" id="view-data-inicio">01/01/2020</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Data de Fim do Contrato</p>
                                    <p class="font-medium" id="view-data-fim">31/12/2025</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Status do Contrato</p>
                                    <p class="font-medium" id="view-status-contrato"><span class="status-badge ativo">Ativo</span></p>
                                </div>
                                <div>
                                    <div class="flex justify-between mb-1">
                                        <p class="text-sm text-gray-500">Limite de Documentos</p>
                                        <p class="text-sm font-medium" id="view-docs-limite">65/100</p>
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress-bar-fill" style="width: 65%" id="view-docs-barra"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-white p-4 rounded-lg border border-gray-200">
                            <h4 class="font-semibold text-gray-700 mb-4">Estatísticas</h4>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-sm text-gray-500">Total de Alunos</p>
                                    <p class="font-medium text-xl" id="view-total-alunos">156</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Total de Turmas</p>
                                    <p class="font-medium text-xl" id="view-total-turmas">8</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Documentos Emitidos</p>
                                    <p class="font-medium text-xl" id="view-docs-emitidos">65</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Solicitações Pendentes</p>
                                    <p class="font-medium text-xl" id="view-solicitacoes">12</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">Turmas Ativas</h3>
                        <a href="#" class="text-sm text-indigo-600 hover:text-indigo-800">Ver todas as turmas</a>
                    </div>
                    
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Turma</th>
                                    <th>Curso</th>
                                    <th>Data Início</th>
                                    <th>Professor Coordenador</th>
                                    <th>Alunos</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="font-medium">SI-2023/1</td>
                                    <td>Sistemas de Informação</td>
                                    <td>01/02/2023</td>
                                    <td>Prof. João Silva</td>
                                    <td>32/40</td>
                                    <td><span class="status-badge ativo">Em andamento</span></td>
                                </tr>
                                <tr>
                                    <td class="font-medium">ADM-2023/1</td>
                                    <td>Administração</td>
                                    <td>01/02/2023</td>
                                    <td>Prof. Carlos Alberto</td>
                                    <td>28/40</td>
                                    <td><span class="status-badge ativo">Em andamento</span></td>
                                </tr>
                                <tr>
                                    <td class="font-medium">DIR-2023/1</td>
                                    <td>Direito</td>
                                    <td>01/02/2023</td>
                                    <td>Profa. Maria Santos</td>
                                    <td>35/40</td>
                                    <td><span class="status-badge ativo">Em andamento</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">Últimos Documentos Emitidos</h3>
                        <a href="secretaria_gerar_documento.php?polo_id=1" class="text-sm text-indigo-600 hover:text-indigo-800">Ver todos os documentos</a>
                    </div>
                    
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Nº Documento</th>
                                    <th>Aluno</th>
                                    <th>Tipo de Documento</th>
                                    <th>Data Emissão</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="font-medium">DOC-2025042</td>
                                    <td>João da Silva</td>
                                    <td>Histórico Escolar</td>
                                    <td>10/04/2025</td>
                                    <td><span class="status-badge ativo">Ativo</span></td>
                                </tr>
                                <tr>
                                    <td class="font-medium">DOC-2025041</td>
                                    <td>Maria Oliveira</td>
                                    <td>Declaração de Matrícula</td>
                                    <td>09/04/2025</td>
                                    <td><span class="status-badge ativo">Ativo</span></td>
                                </tr>
                                <tr>
                                    <td class="font-medium">DOC-2025038</td>
                                    <td>Carlos Santos</td>
                                    <td>Certificado de Conclusão</td>
                                    <td>05/04/2025</td>
                                    <td><span class="status-badge ativo">Ativo</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Observações</h3>
                    <div class="bg-white p-4 rounded-lg border border-gray-200">
                        <p id="view-observacoes">
                            Polo com boa infraestrutura e desempenho constante. Interesse em expandir para novos cursos na área de saúde em 2025.
                        </p>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" class="btn btn-outline close-modal">Fechar</button>
                    <button type="button" class="btn btn-primary edit-polo-btn" data-id="1">
                        <i class="fas fa-edit mr-2"></i> Editar
                    </button>
                    <a href="secretaria_planos.php?polo_id=1" class="btn btn-success">
                        <i class="fas fa-clipboard-list mr-2"></i> Gerenciar Plano
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal de Confirmação de Suspensão -->
    <div id="suspend-modal" class="modal">
        <div class="modal-content max-w-md">
            <div class="p-6">
                <div class="flex items-center justify-center mb-4">
                    <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-pause text-3xl text-yellow-500"></i>
                    </div>
                </div>
                
                <h2 class="text-xl font-bold text-gray-800 text-center mb-4">Confirmar Suspensão</h2>
                
                <p class="text-gray-600 text-center mb-6">
                    Tem certeza que deseja suspender o polo "<span id="suspend-polo-name">Polo São Paulo</span>"? O polo não poderá emitir novos documentos durante a suspensão.
                </p>
                
                <div class="flex justify-center space-x-3">
                    <button type="button" class="btn btn-outline close-modal">Cancelar</button>
                    <button type="button" class="btn btn-warning" id="confirm-suspend-btn">
                        <i class="fas fa-pause mr-2"></i> Suspender
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal de Confirmação de Ativação -->
    <div id="activate-modal" class="modal">
        <div class="modal-content max-w-md">
            <div class="p-6">
                <div class="flex items-center justify-center mb-4">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-play text-3xl text-green-500"></i>
                    </div>
                </div>
                
                <h2 class="text-xl font-bold text-gray-800 text-center mb-4">Confirmar Ativação</h2>
                
                <p class="text-gray-600 text-center mb-6">
                    Tem certeza que deseja ativar o polo "<span id="activate-polo-name">Polo Campinas</span>"? O polo poderá retomar as atividades normais.
                </p>
                
                <div class="flex justify-center space-x-3">
                    <button type="button" class="btn btn-outline close-modal">Cancelar</button>
                    <button type="button" class="btn btn-success" id="confirm-activate-btn">
                        <i class="fas fa-play mr-2"></i> Ativar
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
        const newPoloBtn = document.getElementById('new-polo-btn');
        const viewPoloButtons = document.querySelectorAll('.view-polo-btn');
        const editPoloButtons = document.querySelectorAll('.edit-polo-btn');
        const suspendPoloButtons = document.querySelectorAll('.suspend-polo-btn');
        const activatePoloButtons = document.querySelectorAll('.activate-polo-btn');
        const closeButtons = document.querySelectorAll('.close-modal');
        
        // Open new polo modal
        newPoloBtn.addEventListener('click', function() {
            // Reset form
            document.getElementById('polo-form').reset();
            document.getElementById('polo_id').value = '';
            document.getElementById('modal-title').textContent = 'Novo Polo';
            document.getElementById('submit-btn').textContent = 'Salvar Polo';
            
            // Open modal
            document.getElementById('polo-modal').classList.add('open');
        });
        
        // Open view polo modal
        viewPoloButtons.forEach(button => {
            button.addEventListener('click', function() {
                const poloId = this.getAttribute('data-id');
                console.log('Viewing polo:', poloId);
                
                // Em uma aplicação real, você buscaria os dados do polo
                // e preencheria o modal com os dados corretos
                
                document.getElementById('view-polo-modal').classList.add('open');
            });
        });
        
        // Open edit polo modal
        editPoloButtons.forEach(button => {
            button.addEventListener('click', function() {
                const poloId = this.getAttribute('data-id');
                console.log('Editing polo:', poloId);
                
                // Em uma aplicação real, você buscaria os dados do polo
                // e preencheria o formulário com os dados corretos
                
                document.getElementById('polo_id').value = poloId;
                document.getElementById('modal-title').textContent = 'Editar Polo';
                document.getElementById('submit-btn').textContent = 'Atualizar Polo';
                
                document.getElementById('polo-modal').classList.add('open');
                
                // Fechar o modal de visualização se estiver aberto
                document.getElementById('view-polo-modal').classList.remove('open');
            });
        });
        
        // Open suspend confirmation modal
        suspendPoloButtons.forEach(button => {
            button.addEventListener('click', function() {
                const poloId = this.getAttribute('data-id');
                console.log('Suspending polo:', poloId);
                
                // Em uma aplicação real, você buscaria o nome do polo
                // para exibir na confirmação
                
                document.getElementById('suspend-modal').classList.add('open');
            });
        });
        
        // Open activate confirmation modal
        activatePoloButtons.forEach(button => {
            button.addEventListener('click', function() {
                const poloId = this.getAttribute('data-id');
                console.log('Activating polo:', poloId);
                
                // Em uma aplicação real, você buscaria o nome do polo
                // para exibir na confirmação
                
                document.getElementById('activate-modal').classList.add('open');
            });
        });
        
        // Confirm suspend
        document.getElementById('confirm-suspend-btn').addEventListener('click', function() {
            // Em uma aplicação real, você enviaria uma requisição para suspender o polo
            console.log('Polo suspended');
            
            // Fechar o modal
            document.getElementById('suspend-modal').classList.remove('open');
            
            // Feedback para o usuário
            alert('Polo suspenso com sucesso!');
            
            // Recarregar a lista de polos
            // window.location.reload();
        });
        
        // Confirm activate
        document.getElementById('confirm-activate-btn').addEventListener('click', function() {
            // Em uma aplicação real, você enviaria uma requisição para ativar o polo
            console.log('Polo activated');
            
            // Fechar o modal
            document.getElementById('activate-modal').classList.remove('open');
            
            // Feedback para o usuário
            alert('Polo ativado com sucesso!');
            
            // Recarregar a lista de polos
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
        document.getElementById('polo-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Em uma aplicação real, você enviaria os dados para o servidor
            console.log('Form submitted');
            
            // Fechar o modal
            document.getElementById('polo-modal').classList.remove('open');
            
            // Feedback para o usuário
            const poloId = document.getElementById('polo_id').value;
            const message = poloId ? 'Polo atualizado com sucesso!' : 'Polo criado com sucesso!';
            alert(message);
            
            // Recarregar a lista de polos
            // window.location.reload();
        });
    </script>
</body>
</html>