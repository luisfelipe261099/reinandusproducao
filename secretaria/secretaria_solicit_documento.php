<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faciência ERP - Gestão de Documentos</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4F46E5;
            --primary-dark: #4338CA;
            --secondary: #0EA5E9;
            --accent: #8B5CF6;
            --danger: #EF4444;
            --warning: #F59E0B;
            --success: #10B981;
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
        
        .status-badge.solicitado {
            background-color: #DBEAFE;
            color: #1E40AF;
        }
        
        .status-badge.processando {
            background-color: #FEF3C7;
            color: #92400E;
        }
        
        .status-badge.pronto {
            background-color: #D1FAE5;
            color: #065F46;
        }
        
        .status-badge.entregue {
            background-color: #E0E7FF;
            color: #3730A3;
        }
        
        .status-badge.cancelado {
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
            background-color: var(--success);
            color: white;
        }
        
        .btn-success:hover {
            background-color: #0D9488;
        }
        
        .btn-warning {
            background-color: var(--warning);
            color: white;
        }
        
        .btn-warning:hover {
            background-color: #D97706;
        }
        
        .btn-danger {
            background-color: var(--danger);
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #DC2626;
        }
        
        .tab {
            padding: 0.75rem 1.25rem;
            border-bottom: 2px solid transparent;
            font-weight: 500;
            font-size: 0.875rem;
            color: #6B7280;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .tab:hover {
            color: #4B5563;
        }
        
        .tab.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
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
        
        /* Novos estilos para os detalhes da solicitação */
        .timeline {
            position: relative;
            padding-left: 2rem;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            top: 0;
            bottom: 0;
            left: 1rem;
            width: 2px;
            background-color: #E5E7EB;
            transform: translateX(-50%);
        }
        
        .timeline-item {
            position: relative;
            padding-bottom: 1.5rem;
        }
        
        .timeline-item:last-child {
            padding-bottom: 0;
        }
        
        .timeline-marker {
            position: absolute;
            top: 0;
            left: -1rem;
            width: 1rem;
            height: 1rem;
            border-radius: 50%;
            background-color: white;
            border: 2px solid #E5E7EB;
            transform: translateX(-50%);
        }
        
        .timeline-marker.solicitado {
            border-color: var(--primary);
        }
        
        .timeline-marker.processando {
            border-color: var(--warning);
        }
        
        .timeline-marker.pronto {
            border-color: var(--success);
        }
        
        .timeline-marker.entregue {
            border-color: var(--accent);
        }
        
        .timeline-marker.cancelado {
            border-color: var(--danger);
        }
        
        .price-input-container {
            position: relative;
        }
        
        .price-input-container::before {
            content: 'R$';
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6B7280;
        }
        
        .price-input {
            padding-left: 2.5rem !important;
        }
        
        /* Tooltip */
        .tooltip {
            position: relative;
            display: inline-block;
        }
        
        .tooltip .tooltip-text {
            visibility: hidden;
            width: 200px;
            background-color: #374151;
            color: white;
            text-align: center;
            border-radius: 0.375rem;
            padding: 0.5rem;
            position: absolute;
            z-index: 10;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%);
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 0.75rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        
        .tooltip .tooltip-text::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: #374151 transparent transparent transparent;
        }
        
        .tooltip:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
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
                    <a href="#" class="nav-item flex items-center py-3 px-4 text-white">
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
                    <a href="#" class="nav-item active flex items-center py-3 px-4 text-white">
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
                            <input type="text" class="pl-10 pr-4 py-2 border border-gray-300 rounded-full text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 w-64" placeholder="Buscar...">
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
                            <h1 class="text-2xl font-bold text-gray-800">Solicitações de Documentos</h1>
                            <p class="text-gray-600">Gerencie todas as solicitações de documentos dos polos e alunos</p>
                        </div>
                        <div>
                            <button id="new-request-btn" class="btn btn-primary">
                                <i class="fas fa-plus mr-2"></i>
                                <span>Nova Solicitação</span>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Filters and Tabs -->
                    <div class="bg-white p-4 rounded-t-lg border border-gray-200 mb-0">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                            <div class="flex space-x-1 overflow-x-auto">
                                <button class="tab active" data-status="all">Todas</button>
                                <button class="tab" data-status="solicitado">Solicitadas</button>
                                <button class="tab" data-status="processando">Em Processamento</button>
                                <button class="tab" data-status="pronto">Prontas</button>
                                <button class="tab" data-status="entregue">Entregues</button>
                                <button class="tab" data-status="cancelado">Canceladas</button>
                            </div>
                            
                            <div class="flex space-x-2">
                                <div class="relative">
                                    <select class="form-select py-2 pl-3 pr-10 text-sm" id="filter-polo">
                                        <option value="">Todos os Polos</option>
                                        <option value="1">Polo Central</option>
                                        <option value="2">Polo Norte</option>
                                        <option value="3">Polo Sul</option>
                                        <option value="4">Polo Leste</option>
                                        <option value="5">Polo Oeste</option>
                                    </select>
                                </div>
                                
                                <div class="relative">
                                    <select class="form-select py-2 pl-3 pr-10 text-sm" id="filter-document">
                                        <option value="">Todos os Documentos</option>
                                        <option value="1">Histórico Escolar</option>
                                        <option value="2">Declaração de Matrícula</option>
                                        <option value="3">Declaração de Conclusão</option>
                                        <option value="4">Diploma</option>
                                        <option value="5">Outros</option>
                                    </select>
                                </div>
                                
                                <button class="btn btn-sm btn-outline">
                                    <i class="fas fa-filter mr-1"></i> Filtrar
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Table -->
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Aluno</th>
                                    <th>Documento</th>
                                    <th>Polo</th>
                                    <th>Data</th>
                                    <th>Status</th>
                                    <th>Pagamento</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>#1234</td>
                                    <td>
                                        <div class="flex items-center">
                                            <img src="/api/placeholder/32/32" alt="Avatar" class="w-8 h-8 rounded-full mr-3">
                                            <div>
                                                <p class="font-medium text-gray-800">Ana Carolina Oliveira</p>
                                                <p class="text-gray-500 text-xs">Administração</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <p class="font-medium">Histórico Escolar</p>
                                            <p class="text-gray-500 text-xs">2 cópias</p>
                                        </div>
                                    </td>
                                    <td>Polo Central</td>
                                    <td>12/04/2025</td>
                                    <td><span class="status-badge solicitado">Solicitado</span></td>
                                    <td>
                                        <div class="flex items-center">
                                            <span class="w-2 h-2 bg-red-500 rounded-full mr-2"></span>
                                            <span>Pendente</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex space-x-1">
                                            <button class="btn btn-sm btn-outline view-btn" data-id="1234">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline edit-status-btn" data-id="1234">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>#1233</td>
                                    <td>
                                        <div class="flex items-center">
                                            <img src="/api/placeholder/32/32" alt="Avatar" class="w-8 h-8 rounded-full mr-3">
                                            <div>
                                                <p class="font-medium text-gray-800">Bruno Almeida Santos</p>
                                                <p class="text-gray-500 text-xs">Sistemas de Informação</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <p class="font-medium">Declaração de Matrícula</p>
                                            <p class="text-gray-500 text-xs">1 cópia</p>
                                        </div>
                                    </td>
                                    <td>Polo Norte</td>
                                    <td>10/04/2025</td>
                                    <td><span class="status-badge processando">Processando</span></td>
                                    <td>
                                        <div class="flex items-center">
                                            <span class="w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                                            <span>Pago</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex space-x-1">
                                            <button class="btn btn-sm btn-outline view-btn" data-id="1233">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline edit-status-btn" data-id="1233">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>#1232</td>
                                    <td>
                                        <div class="flex items-center">
                                            <img src="/api/placeholder/32/32" alt="Avatar" class="w-8 h-8 rounded-full mr-3">
                                            <div>
                                                <p class="font-medium text-gray-800">Carla Mendes Ferreira</p>
                                                <p class="text-gray-500 text-xs">Pedagogia</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <p class="font-medium">Diploma</p>
                                            <p class="text-gray-500 text-xs">1 cópia</p>
                                        </div>
                                    </td>
                                    <td>Polo Sul</td>
                                    <td>05/04/2025</td>
                                    <td><span class="status-badge pronto">Pronto</span></td>
                                    <td>
                                        <div class="flex items-center">
                                            <span class="w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                                            <span>Pago</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex space-x-1">
                                            <button class="btn btn-sm btn-outline view-btn" data-id="1232">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline edit-status-btn" data-id="1232">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>#1231</td>
                                    <td>
                                        <div class="flex items-center">
                                            <img src="/api/placeholder/32/32" alt="Avatar" class="w-8 h-8 rounded-full mr-3">
                                            <div>
                                                <p class="font-medium text-gray-800">Daniel Costa Silva</p>
                                                <p class="text-gray-500 text-xs">Administração</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <p class="font-medium">Histórico Escolar</p>
                                            <p class="text-gray-500 text-xs">3 cópias</p>
                                        </div>
                                    </td>
                                    <td>Polo Leste</td>
                                    <td>01/04/2025</td>
                                    <td><span class="status-badge entregue">Entregue</span></td>
                                    <td>
                                        <div class="flex items-center">
                                            <span class="w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                                            <span>Pago</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex space-x-1">
                                            <button class="btn btn-sm btn-outline view-btn" data-id="1231">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline edit-status-btn" data-id="1231">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>#1230</td>
                                    <td>
                                        <div class="flex items-center">
                                            <img src="/api/placeholder/32/32" alt="Avatar" class="w-8 h-8 rounded-full mr-3">
                                            <div>
                                                <p class="font-medium text-gray-800">Eduarda Pereira Nunes</p>
                                                <p class="text-gray-500 text-xs">Direito</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <p class="font-medium">Declaração de Conclusão</p>
                                            <p class="text-gray-500 text-xs">2 cópias</p>
                                        </div>
                                    </td>
                                    <td>Polo Oeste</td>
                                    <td>28/03/2025</td>
                                    <td><span class="status-badge cancelado">Cancelado</span></td>
                                    <td>
                                        <div class="flex items-center">
                                            <span class="w-2 h-2 bg-gray-500 rounded-full mr-2"></span>
                                            <span>N/A</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex space-x-1">
                                            <button class="btn btn-sm btn-outline view-btn" data-id="1230">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline edit-status-btn" data-id="1230">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="flex items-center justify-between mt-4 bg-white p-4 rounded-lg shadow-sm">
                        <div class="text-sm text-gray-600">
                            Mostrando 1-5 de 42 solicitações
                        </div>
                        <div class="flex items-center space-x-2">
                            <button class="btn btn-sm btn-outline opacity-50 cursor-not-allowed">
                                <i class="fas fa-chevron-left mr-1"></i> Anterior
                            </button>
                            <div class="flex space-x-1">
                                <button class="w-8 h-8 flex items-center justify-center rounded-md bg-indigo-100 text-indigo-700 font-medium">1</button>
                                <button class="w-8 h-8 flex items-center justify-center rounded-md hover:bg-gray-100">2</button>
                                <button class="w-8 h-8 flex items-center justify-center rounded-md hover:bg-gray-100">3</button>
                                <button class="w-8 h-8 flex items-center justify-center rounded-md hover:bg-gray-100">4</button>
                                <button class="w-8 h-8 flex items-center justify-center rounded-md hover:bg-gray-100">5</button>
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

    <!-- Modal de Nova Solicitação -->
    <div id="new-request-modal" class="modal">
        <div class="modal-content max-w-3xl">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-bold text-gray-800">Nova Solicitação de Documento</h2>
                    <button class="text-gray-400 hover:text-gray-600 close-modal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            
            <div class="p-6">
                <form id="new-request-form">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <!-- Origem da solicitação -->
                        <div class="md:col-span-2">
                            <label class="form-label">Origem da Solicitação</label>
                            <div class="flex space-x-4">
                                <label class="inline-flex items-center">
                                    <input type="radio" name="origem" value="secretaria" class="form-radio" checked>
                                    <span class="ml-2">Secretaria</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="radio" name="origem" value="polo" class="form-radio">
                                    <span class="ml-2">Polo</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="radio" name="origem" value="aluno" class="form-radio">
                                    <span class="ml-2">Aluno</span>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Aluno -->
                        <div class="md:col-span-2">
                            <label for="aluno_id" class="form-label">Aluno <span class="text-red-500">*</span></label>
                            <select id="aluno_id" name="aluno_id" class="form-select" required>
                                <option value="">Selecione o aluno</option>
                                <option value="1">Ana Carolina Oliveira - Administração</option>
                                <option value="2">Bruno Almeida Santos - Sistemas de Informação</option>
                                <option value="3">Carla Mendes Ferreira - Pedagogia</option>
                                <option value="4">Daniel Costa Silva - Administração</option>
                                <option value="5">Eduarda Pereira Nunes - Direito</option>
                            </select>
                        </div>
                        
                        <!-- Polo e Tipo de Documento -->
                        <div>
                            <label for="polo_id" class="form-label">Polo <span class="text-red-500">*</span></label>
                            <select id="polo_id" name="polo_id" class="form-select" required>
                                <option value="">Selecione o polo</option>
                                <option value="1">Polo Central</option>
                                <option value="2">Polo Norte</option>
                                <option value="3">Polo Sul</option>
                                <option value="4">Polo Leste</option>
                                <option value="5">Polo Oeste</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="tipo_documento_id" class="form-label">Tipo de Documento <span class="text-red-500">*</span></label>
                            <select id="tipo_documento_id" name="tipo_documento_id" class="form-select" required>
                                <option value="">Selecione o tipo de documento</option>
                                <option value="1">Histórico Escolar</option>
                                <option value="2">Declaração de Matrícula</option>
                                <option value="3">Declaração de Conclusão</option>
                                <option value="4">Diploma</option>
                                <option value="5">Conteúdo Programático</option>
                                <option value="6">Outro Documento</option>
                            </select>
                        </div>
                        
                        <!-- Outro documento (condicional) -->
                        <div id="outro-documento-container" class="md:col-span-2 hidden">
                            <label for="outro_documento" class="form-label">Especifique o Documento <span class="text-red-500">*</span></label>
                            <input type="text" id="outro_documento" name="outro_documento" class="form-input" placeholder="Digite o nome do documento">
                        </div>
                        
                        <!-- Quantidade e Urgência -->
                        <div>
                            <label for="quantidade" class="form-label">Quantidade <span class="text-red-500">*</span></label>
                            <input type="number" id="quantidade" name="quantidade" class="form-input" min="1" max="10" value="1" required>
                        </div>
                        
                        <div>
                            <label class="form-label">Urgência</label>
                            <div class="flex space-x-4">
                                <label class="inline-flex items-center">
                                    <input type="radio" name="urgencia" value="normal" class="form-radio" checked>
                                    <span class="ml-2">Normal (5 dias úteis)</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="radio" name="urgencia" value="urgente" class="form-radio">
                                    <span class="ml-2">Urgente (2 dias úteis)</span>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Finalidade -->
                        <div class="md:col-span-2">
                            <label for="finalidade" class="form-label">Finalidade</label>
                            <textarea id="finalidade" name="finalidade" class="form-textarea" rows="2" placeholder="Descreva a finalidade de uso do documento"></textarea>
                        </div>
                        
                        <!-- Preço e Pagamento -->
                        <div>
                            <label for="valor" class="form-label">Valor (R$) <span class="text-red-500">*</span></label>
                            <div class="price-input-container">
                                <input type="text" id="valor" name="valor" class="form-input price-input" placeholder="0,00" required>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">
                                <span class="tooltip">
                                    <i class="fas fa-info-circle text-gray-400"></i>
                                    <span class="tooltip-text">O valor varia conforme polo e contrato. Verifique a tabela de valores específicos.</span>
                                </span>
                                O valor varia conforme contrato com o polo.
                            </p>
                        </div>
                        
                        <div>
                            <label for="status_pagamento" class="form-label">Status do Pagamento</label>
                            <select id="status_pagamento" name="pago" class="form-select">
                                <option value="0">Pendente</option>
                                <option value="1">Pago</option>
                            </select>
                        </div>
                        
                        <!-- Observações -->
                        <div class="md:col-span-2">
                            <label for="observacoes" class="form-label">Observações</label>
                            <textarea id="observacoes" name="observacoes" class="form-textarea" rows="2" placeholder="Observações adicionais sobre a solicitação"></textarea>
                        </div>
                    </div>
                
                    <div class="flex justify-end space-x-3">
                        <button type="button" class="btn btn-outline close-modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Registrar Solicitação</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal de Detalhes da Solicitação -->
    <div id="view-request-modal" class="modal">
        <div class="modal-content max-w-3xl">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-bold text-gray-800">Detalhes da Solicitação #1234</h2>
                    <button class="text-gray-400 hover:text-gray-600 close-modal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div class="md:col-span-2">
                        <!-- Informações do Aluno -->
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-3">Informações do Aluno</h3>
                            <div class="flex items-start">
                                <img src="/api/placeholder/64/64" alt="Avatar" class="w-16 h-16 rounded-full mr-4">
                                <div>
                                    <h4 class="text-base font-medium">Ana Carolina Oliveira</h4>
                                    <p class="text-gray-600">Administração</p>
                                    <p class="text-sm text-gray-500">ID: 12345 | Matrícula: 2025001234</p>
                                    <p class="text-sm text-gray-500">CPF: 123.456.789-10</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Detalhes da Solicitação -->
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-3">Detalhes da Solicitação</h3>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-sm text-gray-500">Documento</p>
                                        <p class="font-medium">Histórico Escolar</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Quantidade</p>
                                        <p class="font-medium">2 cópias</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Polo</p>
                                        <p class="font-medium">Polo Central</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Data da Solicitação</p>
                                        <p class="font-medium">12/04/2025</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Status</p>
                                        <p><span class="status-badge solicitado">Solicitado</span></p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Urgência</p>
                                        <p class="font-medium">Normal (5 dias úteis)</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Valor</p>
                                        <p class="font-medium">R$ 25,00</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Pagamento</p>
                                        <p class="font-medium text-red-600">Pendente</p>
                                    </div>
                                    <div class="md:col-span-2">
                                        <p class="text-sm text-gray-500">Finalidade</p>
                                        <p class="font-medium">Apresentação em processo seletivo de pós-graduação</p>
                                    </div>
                                    <div class="md:col-span-2">
                                        <p class="text-sm text-gray-500">Observações</p>
                                        <p class="font-medium">Aluno solicitou que o documento contenha informações sobre atividades complementares.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Timeline do Status -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-3">Histórico da Solicitação</h3>
                        <div class="timeline">
                            <div class="timeline-item">
                                <div class="timeline-marker solicitado"></div>
                                <div>
                                    <p class="font-medium">Solicitado</p>
                                    <p class="text-sm text-gray-500">12/04/2025 10:23</p>
                                    <p class="text-sm">Solicitação registrada pelo Polo Central</p>
                                </div>
                            </div>
                            <div class="timeline-item">
                                <div class="timeline-marker"></div>
                                <div>
                                    <p class="font-medium text-gray-400">Processando</p>
                                    <p class="text-sm text-gray-400">Pendente</p>
                                </div>
                            </div>
                            <div class="timeline-item">
                                <div class="timeline-marker"></div>
                                <div>
                                    <p class="font-medium text-gray-400">Pronto</p>
                                    <p class="text-sm text-gray-400">Pendente</p>
                                </div>
                            </div>
                            <div class="timeline-item">
                                <div class="timeline-marker"></div>
                                <div>
                                    <p class="font-medium text-gray-400">Entregue</p>
                                    <p class="text-sm text-gray-400">Pendente</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-between border-t border-gray-200 pt-4 mt-4">
                    <div>
                        <button class="btn btn-danger">
                            <i class="fas fa-times mr-2"></i> Cancelar Solicitação
                        </button>
                    </div>
                    <div class="flex space-x-3">
                        <button class="btn btn-outline close-modal">Fechar</button>
                        <button class="btn btn-primary edit-status-btn" data-id="1234">
                            <i class="fas fa-edit mr-2"></i> Atualizar Status
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal de Atualização de Status -->
    <div id="edit-status-modal" class="modal">
        <div class="modal-content max-w-lg">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-bold text-gray-800">Atualizar Status da Solicitação</h2>
                    <button class="text-gray-400 hover:text-gray-600 close-modal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            
            <div class="p-6">
                <form id="update-status-form">
                    <div class="mb-6">
                        <div class="bg-blue-50 p-4 rounded-lg mb-6">
                            <div class="flex items-start">
                                <div class="flex-shrink-0 mt-0.5">
                                    <i class="fas fa-info-circle text-blue-500"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-blue-700">
                                        Você está atualizando o status da solicitação <strong>#1234</strong> para o aluno <strong>Ana Carolina Oliveira</strong>.
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <label for="novo_status" class="form-label">Novo Status <span class="text-red-500">*</span></label>
                        <select id="novo_status" name="novo_status" class="form-select" required>
                            <option value="">Selecione o novo status</option>
                            <option value="solicitado">Solicitado</option>
                            <option value="processando">Processando</option>
                            <option value="pronto">Pronto</option>
                            <option value="entregue">Entregue</option>
                            <option value="cancelado">Cancelado</option>
                        </select>
                    </div>
                    
                    <div class="mb-6">
                        <label for="status_pagamento" class="form-label">Status do Pagamento</label>
                        <select id="status_pagamento_edit" name="pago" class="form-select">
                            <option value="0">Pendente</option>
                            <option value="1">Pago</option>
                        </select>
                    </div>
                    
                    <div class="mb-6">
                        <label for="observacoes_atualizacao" class="form-label">Observações</label>
                        <textarea id="observacoes_atualizacao" name="observacoes_atualizacao" class="form-textarea" rows="3" placeholder="Observações sobre a atualização de status"></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" class="btn btn-outline close-modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Atualizar Status</button>
                    </div>
                </form>
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
        
        // Tab switching
        const tabs = document.querySelectorAll('.tab');
        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                tabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                // Aqui você filtraria os dados com base no status selecionado
                const status = this.getAttribute('data-status');
                console.log('Filtrando por status:', status);
                
                // Em uma aplicação real, você faria uma chamada AJAX para buscar os dados filtrados
                // ou filtraria os dados já carregados
            });
        });
        
        // Modal management
        const modals = document.querySelectorAll('.modal');
        const newRequestBtn = document.getElementById('new-request-btn');
        const viewButtons = document.querySelectorAll('.view-btn');
        const editStatusButtons = document.querySelectorAll('.edit-status-btn');
        const closeButtons = document.querySelectorAll('.close-modal');
        
        // Open new request modal
        newRequestBtn.addEventListener('click', function() {
            document.getElementById('new-request-modal').classList.add('open');
        });
        
        // Open view request modal
        viewButtons.forEach(button => {
            button.addEventListener('click', function() {
                const requestId = this.getAttribute('data-id');
                console.log('Viewing request:', requestId);
                
                // Em uma aplicação real, você buscaria os dados da solicitação
                // e preencheria o modal com os dados corretos
                
                document.getElementById('view-request-modal').classList.add('open');
            });
        });
        
        // Open edit status modal
        editStatusButtons.forEach(button => {
            button.addEventListener('click', function() {
                const requestId = this.getAttribute('data-id');
                console.log('Editing status for request:', requestId);
                
                // Em uma aplicação real, você buscaria os dados da solicitação
                // e preencheria o modal com os dados corretos
                
                document.getElementById('edit-status-modal').classList.add('open');
            });
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
        
        // Custom document select
        document.getElementById('tipo_documento_id').addEventListener('change', function() {
            const outroDocumentoContainer = document.getElementById('outro-documento-container');
            
            if (this.value === '6') { // Outro Documento
                outroDocumentoContainer.classList.remove('hidden');
            } else {
                outroDocumentoContainer.classList.add('hidden');
            }
        });
        
        // Price input formatting
        document.getElementById('valor').addEventListener('input', function() {
            let value = this.value.replace(/\D/g, '');
            
            // Convert to decimal
            value = (parseFloat(value) / 100).toFixed(2);
            
            // Format as currency
            value = value.replace('.', ',');
            
            // Only update if value has changed to avoid cursor jumping
            if (this.value !== value) {
                this.value = value;
            }
        });
        
        // Form submissions
        document.getElementById('new-request-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Em uma aplicação real, você enviaria os dados para o servidor
            console.log('Submitting new request form');
            
            // Fechar o modal
            document.getElementById('new-request-modal').classList.remove('open');
            
            // Feedback para o usuário
            alert('Solicitação registrada com sucesso!');
            
            // Recarregar a lista de solicitações
            // window.location.reload();
        });
        
        document.getElementById('update-status-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Em uma aplicação real, você enviaria os dados para o servidor
            console.log('Submitting status update form');
            
            // Fechar o modal
            document.getElementById('edit-status-modal').classList.remove('open');
            
            // Feedback para o usuário
            alert('Status atualizado com sucesso!');
            
            // Recarregar a lista de solicitações
            // window.location.reload();
        });
    </script>
</body>
</html>