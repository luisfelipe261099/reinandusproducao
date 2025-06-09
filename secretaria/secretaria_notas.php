<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faciência ERP - Gestão de Notas</title>
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
        
        .grade-input {
            width: 60px;
            text-align: center;
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
                    <a href="secretaria_solicit_documento.php" class="nav-item flex items-center py-3 px-4 text-white">
                        <i class="fas fa-certificate w-6"></i>
                        <span class="sidebar-label ml-3">Documentos</span>
                    </a>
                    <a href="secretaria_notas.php" class="nav-item active flex items-center py-3 px-4 text-white">
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
                            <input type="text" class="pl-10 pr-4 py-2 border border-gray-300 rounded-full text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 w-64" placeholder="Buscar aluno...">
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
                            <h1 class="text-2xl font-bold text-gray-800">Gestão de Notas</h1>
                            <p class="text-gray-600">Lançamento e visualização de notas de alunos</p>
                        </div>
                        <div>
                            <button id="launch-grades-btn" class="btn btn-primary">
                                <i class="fas fa-plus mr-2"></i>
                                <span>Lançar Notas</span>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Filters -->
                    <div class="bg-white p-4 rounded-t-lg border border-gray-200 mb-0">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                            <div class="flex space-x-2">
                                <div class="relative">
                                    <select class="form-select py-2 pl-3 pr-10 text-sm" id="filter-turma">
                                        <option value="">Todas as Turmas</option>
                                        <option value="1">SI-2023/1</option>
                                        <option value="2">ADM-2023/1</option>
                                        <option value="3">DIR-2023/1</option>
                                        <option value="4">PED-2023/1</option>
                                        <option value="5">CONT-2023/1</option>
                                    </select>
                                </div>
                                
                                <div class="relative">
                                    <select class="form-select py-2 pl-3 pr-10 text-sm" id="filter-disciplina">
                                        <option value="">Todas as Disciplinas</option>
                                        <option value="1">Banco de Dados</option>
                                        <option value="2">Introdução à Administração</option>
                                        <option value="3">Direito Civil</option>
                                        <option value="4">Psicologia da Educação</option>
                                        <option value="5">Contabilidade Avançada</option>
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
                                
                                <button class="btn btn-sm btn-outline">
                                    <i class="fas fa-filter mr-1"></i> Filtrar
                                </button>
                            </div>
                            
                            <div class="flex items-center">
                                <span class="text-sm text-gray-500 mr-3">Exibir:</span>
                                <select class="form-select py-1 pl-2 pr-8 text-sm">
                                    <option value="10">10 itens</option>
                                    <option value="25">25 itens</option>
                                    <option value="50">50 itens</option>
                                    <option value="100">100 itens</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Table -->
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Aluno</th>
                                    <th>Matrícula</th>
                                    <th>Turma</th>
                                    <th>Disciplina</th>
                                    <th>Período</th>
                                    <th>Nota 1</th>
                                    <th>Nota 2</th>
                                    <th>Média</th>
                                    <th>Situação</th>
                                    <th class="w-20 text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <div class="flex items-center">
                                            <img src="/api/placeholder/32/32" alt="Foto do aluno" class="h-8 w-8 rounded-full mr-2">
                                            <p class="font-medium text-gray-800">João da Silva</p>
                                        </div>
                                    </td>
                                    <td>202301001</td>
                                    <td>SI-2023/1</td>
                                    <td>Banco de Dados</td>
                                    <td>2023/1</td>
                                    <td>8.5</td>
                                    <td>7.0</td>
                                    <td>7.8</td>
                                    <td><span class="status-badge ativo">Aprovado</span></td>
                                    <td>
                                        <div class="flex justify-end space-x-1">
                                            <button class="btn btn-sm btn-outline edit-grade-btn" data-id="1">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline history-btn" data-id="1">
                                                <i class="fas fa-history"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="flex items-center">
                                            <img src="/api/placeholder/32/32" alt="Foto do aluno" class="h-8 w-8 rounded-full mr-2">
                                            <p class="font-medium text-gray-800">Maria Oliveira</p>
                                        </div>
                                    </td>
                                    <td>202301002</td>
                                    <td>SI-2023/1</td>
                                    <td>Banco de Dados</td>
                                    <td>2023/1</td>
                                    <td>9.0</td>
                                    <td>8.5</td>
                                    <td>8.8</td>
                                    <td><span class="status-badge ativo">Aprovado</span></td>
                                    <td>
                                        <div class="flex justify-end space-x-1">
                                            <button class="btn btn-sm btn-outline edit-grade-btn" data-id="2">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline history-btn" data-id="2">
                                                <i class="fas fa-history"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="flex items-center">
                                            <img src="/api/placeholder/32/32" alt="Foto do aluno" class="h-8 w-8 rounded-full mr-2">
                                            <p class="font-medium text-gray-800">Carlos Santos</p>
                                        </div>
                                    </td>
                                    <td>202301003</td>
                                    <td>ADM-2023/1</td>
                                    <td>Introdução à Administração</td>
                                    <td>2023/1</td>
                                    <td>6.5</td>
                                    <td>5.0</td>
                                    <td>5.8</td>
                                    <td><span class="status-badge inativo">Reprovado</span></td>
                                    <td>
                                        <div class="flex justify-end space-x-1">
                                            <button class="btn btn-sm btn-outline edit-grade-btn" data-id="3">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline history-btn" data-id="3">
                                                <i class="fas fa-history"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="flex items-center">
                                            <img src="/api/placeholder/32/32" alt="Foto do aluno" class="h-8 w-8 rounded-full mr-2">
                                            <p class="font-medium text-gray-800">Ana Souza</p>
                                        </div>
                                    </td>
                                    <td>202301004</td>
                                    <td>DIR-2023/1</td>
                                    <td>Direito Civil</td>
                                    <td>2023/1</td>
                                    <td>7.0</td>
                                    <td>7.2</td>
                                    <td>7.1</td>
                                    <td><span class="status-badge ativo">Aprovado</span></td>
                                    <td>
                                        <div class="flex justify-end space-x-1">
                                            <button class="btn btn-sm btn-outline edit-grade-btn" data-id="4">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline history-btn" data-id="4">
                                                <i class="fas fa-history"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="flex items-center">
                                            <img src="/api/placeholder/32/32" alt="Foto do aluno" class="h-8 w-8 rounded-full mr-2">
                                            <p class="font-medium text-gray-800">Paulo Lima</p>
                                        </div>
                                    </td>
                                    <td>202301005</td>
                                    <td>PED-2023/1</td>
                                    <td>Psicologia da Educação</td>
                                    <td>2023/1</td>
                                    <td>8.0</td>
                                    <td>8.0</td>
                                    <td>8.0</td>
                                    <td><span class="status-badge ativo">Aprovado</span></td>
                                    <td>
                                        <div class="flex justify-end space-x-1">
                                            <button class="btn btn-sm btn-outline edit-grade-btn" data-id="5">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline history-btn" data-id="5">
                                                <i class="fas fa-history"></i>
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
                            Mostrando 1-5 de 48 registros
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

    <!-- Modal de Lançamento de Notas -->
    <div id="grades-modal" class="modal">
        <div class="modal-content max-w-4xl">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h2 id="modal-title" class="text-xl font-bold text-gray-800">Lançamento de Notas</h2>
                    <button class="text-gray-400 hover:text-gray-600 close-modal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            
            <div class="p-6">
                <form id="grades-form">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div>
                            <label for="turma_id" class="form-label">Turma <span class="text-red-500">*</span></label>
                            <select id="turma_id" name="turma_id" class="form-select" required>
                                <option value="">Selecione a turma</option>
                                <option value="1">SI-2023/1</option>
                                <option value="2">ADM-2023/1</option>
                                <option value="3">DIR-2023/1</option>
                                <option value="4">PED-2023/1</option>
                                <option value="5">CONT-2023/1</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="disciplina_id" class="form-label">Disciplina <span class="text-red-500">*</span></label>
                            <select id="disciplina_id" name="disciplina_id" class="form-select" required>
                                <option value="">Selecione a disciplina</option>
                                <option value="1">Banco de Dados</option>
                                <option value="2">Introdução à Administração</option>
                                <option value="3">Direito Civil</option>
                                <option value="4">Psicologia da Educação</option>
                                <option value="5">Contabilidade Avançada</option>
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
                    </div>
                    
                    <div class="mb-6">
                        <button type="button" id="load-students-btn" class="btn btn-outline">
                            <i class="fas fa-sync-alt mr-2"></i> Carregar Alunos
                        </button>
                    </div>
                    
                    <div id="students-list" class="mb-6 hidden">
                        <div class="bg-gray-50 p-4 rounded-lg mb-4">
                            <h3 class="font-semibold text-gray-700 mb-2">Lista de Alunos</h3>
                            <p class="text-sm text-gray-600">Preencha as notas para cada aluno. Para valores decimais, utilize ponto (.) como separador.</p>
                        </div>
                        
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Aluno</th>
                                        <th>Matrícula</th>
                                        <th>Nota 1</th>
                                        <th>Nota 2</th>
                                        <th>Média</th>
                                        <th>Situação</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <div class="flex items-center">
                                                <img src="/api/placeholder/32/32" alt="Foto do aluno" class="h-8 w-8 rounded-full mr-2">
                                                <p class="font-medium text-gray-800">João da Silva</p>
                                                <input type="hidden" name="aluno_id[]" value="1">
                                            </div>
                                        </td>
                                        <td>202301001</td>
                                        <td>
                                            <input type="number" name="nota1[]" class="form-input grade-input nota1" min="0" max="10" step="0.1" value="0" data-row="0">
                                        </td>
                                        <td>
                                            <input type="number" name="nota2[]" class="form-input grade-input nota2" min="0" max="10" step="0.1" value="0" data-row="0">
                                        </td>
                                        <td class="media" data-row="0">0.0</td>
                                        <td class="situacao" data-row="0"><span class="status-badge inativo">Pendente</span></td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="flex items-center">
                                                <img src="/api/placeholder/32/32" alt="Foto do aluno" class="h-8 w-8 rounded-full mr-2">
                                                <p class="font-medium text-gray-800">Maria Oliveira</p>
                                                <input type="hidden" name="aluno_id[]" value="2">
                                            </div>
                                        </td>
                                        <td>202301002</td>
                                        <td>
                                            <input type="number" name="nota1[]" class="form-input grade-input nota1" min="0" max="10" step="0.1" value="0" data-row="1">
                                        </td>
                                        <td>
                                            <input type="number" name="nota2[]" class="form-input grade-input nota2" min="0" max="10" step="0.1" value="0" data-row="1">
                                        </td>
                                        <td class="media" data-row="1">0.0</td>
                                        <td class="situacao" data-row="1"><span class="status-badge inativo">Pendente</span></td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="flex items-center">
                                                <img src="/api/placeholder/32/32" alt="Foto do aluno" class="h-8 w-8 rounded-full mr-2">
                                                <p class="font-medium text-gray-800">Carlos Santos</p>
                                                <input type="hidden" name="aluno_id[]" value="3">
                                            </div>
                                        </td>
                                        <td>202301003</td>
                                        <td>
                                            <input type="number" name="nota1[]" class="form-input grade-input nota1" min="0" max="10" step="0.1" value="0" data-row="2">
                                        </td>
                                        <td>
                                            <input type="number" name="nota2[]" class="form-input grade-input nota2" min="0" max="10" step="0.1" value="0" data-row="2">
                                        </td>
                                        <td class="media" data-row="2">0.0</td>
                                        <td class="situacao" data-row="2"><span class="status-badge inativo">Pendente</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div id="form-actions" class="flex justify-end space-x-3 hidden">
                        <button type="button" class="btn btn-outline close-modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="submit-btn">Salvar Notas</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal de Edição de Nota Individual -->
    <div id="edit-grade-modal" class="modal">
        <div class="modal-content max-w-lg">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-bold text-gray-800">Editar Notas</h2>
                    <button class="text-gray-400 hover:text-gray-600 close-modal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            
            <div class="p-6">
                <form id="edit-grade-form">
                    <input type="hidden" id="edit_aluno_id" name="aluno_id" value="">
                    <input type="hidden" id="edit_disciplina_id" name="disciplina_id" value="">
                    <input type="hidden" id="edit_turma_id" name="turma_id" value="">
                    <input type="hidden" id="edit_periodo" name="periodo" value="">
                    
                    <div class="bg-gray-50 p-4 rounded-lg mb-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-500">Aluno</p>
                                <p class="font-medium" id="edit-aluno">João da Silva</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Matrícula</p>
                                <p class="font-medium" id="edit-matricula">202301001</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Turma</p>
                                <p class="font-medium" id="edit-turma">SI-2023/1</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Disciplina</p>
                                <p class="font-medium" id="edit-disciplina">Banco de Dados</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Período</p>
                                <p class="font-medium" id="edit-periodo-text">2023/1</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="edit_nota1" class="form-label">Nota 1 <span class="text-red-500">*</span></label>
                            <input type="number" id="edit_nota1" name="nota1" class="form-input" min="0" max="10" step="0.1" value="8.5" required>
                        </div>
                        <div>
                            <label for="edit_nota2" class="form-label">Nota 2 <span class="text-red-500">*</span></label>
                            <input type="number" id="edit_nota2" name="nota2" class="form-input" min="0" max="10" step="0.1" value="7.0" required>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 p-4 rounded-lg mb-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-500">Média</p>
                                <p class="font-medium" id="edit-media">7.8</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Situação</p>
                                <p id="edit-situacao"><span class="status-badge ativo">Aprovado</span></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <label for="edit_observacao" class="form-label">Observação</label>
                        <textarea id="edit_observacao" name="observacao" class="form-textarea" rows="3" placeholder="Observações sobre o desempenho ou justificativa para alteração de nota"></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" class="btn btn-outline close-modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="edit-submit-btn">Atualizar Notas</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal de Histórico de Notas -->
    <div id="history-modal" class="modal">
        <div class="modal-content max-w-2xl">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-bold text-gray-800">Histórico de Notas</h2>
                    <button class="text-gray-400 hover:text-gray-600 close-modal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            
            <div class="p-6">
                <div class="bg-gray-50 p-4 rounded-lg mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-500">Aluno</p>
                            <p class="font-medium" id="history-aluno">João da Silva</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Matrícula</p>
                            <p class="font-medium" id="history-matricula">202301001</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Turma</p>
                            <p class="font-medium" id="history-turma">SI-2023/1</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Disciplina</p>
                            <p class="font-medium" id="history-disciplina">Banco de Dados</p>
                        </div>
                    </div>
                </div>
                
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Alterações de Notas</h3>
                    
                    <div class="space-y-4">
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <span class="font-medium">Lançamento inicial</span>
                                    <p class="text-xs text-gray-500">15/03/2023 às 10:45 - Ana Silva</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-sm text-gray-500">Nota 1: <span class="font-medium">8.0</span></p>
                                    <p class="text-sm text-gray-500">Nota 2: <span class="font-medium">7.0</span></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Média: <span class="font-medium">7.5</span></p>
                                    <p class="text-sm text-gray-500">Situação: <span class="font-medium text-green-600">Aprovado</span></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <span class="font-medium">Ajuste de nota</span>
                                    <p class="text-xs text-gray-500">22/03/2023 às 14:20 - Pedro Santos</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-sm text-gray-500">Nota 1: <span class="font-medium">8.5</span></p>
                                    <p class="text-sm text-gray-500">Nota 2: <span class="font-medium">7.0</span></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Média: <span class="font-medium">7.8</span></p>
                                    <p class="text-sm text-gray-500">Situação: <span class="font-medium text-green-600">Aprovado</span></p>
                                </div>
                            </div>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">Observação:</p>
                                <p class="text-sm">Ajuste da nota 1 após revisão da prova solicitada pelo aluno.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end">
                    <button type="button" class="btn btn-outline close-modal">Fechar</button>
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
        
        // Modal management
        const modals = document.querySelectorAll('.modal');
        const launchGradesBtn = document.getElementById('launch-grades-btn');
        const loadStudentsBtn = document.getElementById('load-students-btn');
        const editGradeButtons = document.querySelectorAll('.edit-grade-btn');
        const historyButtons = document.querySelectorAll('.history-btn');
        const closeButtons = document.querySelectorAll('.close-modal');
        
        // Open launch grades modal
        launchGradesBtn.addEventListener('click', function() {
            // Reset form
            document.getElementById('grades-form').reset();
            document.getElementById('students-list').classList.add('hidden');
            document.getElementById('form-actions').classList.add('hidden');
            
            // Open modal
            document.getElementById('grades-modal').classList.add('open');
        });
        
        // Load students button click
        loadStudentsBtn.addEventListener('click', function() {
            const turmaId = document.getElementById('turma_id').value;
            const disciplinaId = document.getElementById('disciplina_id').value;
            const periodo = document.getElementById('periodo').value;
            
            if (!turmaId || !disciplinaId || !periodo) {
                alert('Por favor, selecione turma, disciplina e período para carregar os alunos.');
                return;
            }
            
            // Em uma aplicação real, você faria uma requisição AJAX para buscar os alunos
            // Aqui vamos apenas mostrar a lista estática
            document.getElementById('students-list').classList.remove('hidden');
            document.getElementById('form-actions').classList.remove('hidden');
        });
        
        // Calculate average and update status
        const gradeInputs = document.querySelectorAll('.grade-input');
        
        gradeInputs.forEach(input => {
            input.addEventListener('input', function() {
                const row = this.getAttribute('data-row');
                const nota1 = parseFloat(document.querySelector(`.nota1[data-row="${row}"]`).value) || 0;
                const nota2 = parseFloat(document.querySelector(`.nota2[data-row="${row}"]`).value) || 0;
                
                const media = ((nota1 + nota2) / 2).toFixed(1);
                document.querySelector(`.media[data-row="${row}"]`).textContent = media;
                
                const situacaoEl = document.querySelector(`.situacao[data-row="${row}"]`);
                
                if (media >= 6) {
                    situacaoEl.innerHTML = '<span class="status-badge ativo">Aprovado</span>';
                } else {
                    situacaoEl.innerHTML = '<span class="status-badge inativo">Reprovado</span>';
                }
            });
        });
        
        // Open edit grade modal
        editGradeButtons.forEach(button => {
            button.addEventListener('click', function() {
                const studentId = this.getAttribute('data-id');
                console.log('Editing grade for student:', studentId);
                
                // Em uma aplicação real, você buscaria os dados do aluno
                // e preencheria o modal com os dados corretos
                
                document.getElementById('edit-grade-modal').classList.add('open');
                
                // Exemplo de cálculo de média e situação ao editar
                const calcAverage = () => {
                    const nota1 = parseFloat(document.getElementById('edit_nota1').value) || 0;
                    const nota2 = parseFloat(document.getElementById('edit_nota2').value) || 0;
                    
                    const media = ((nota1 + nota2) / 2).toFixed(1);
                    document.getElementById('edit-media').textContent = media;
                    
                    const situacaoEl = document.getElementById('edit-situacao');
                    
                    if (media >= 6) {
                        situacaoEl.innerHTML = '<span class="status-badge ativo">Aprovado</span>';
                    } else {
                        situacaoEl.innerHTML = '<span class="status-badge inativo">Reprovado</span>';
                    }
                };
                
                // Adicionar eventos para recalcular ao mudar as notas
                document.getElementById('edit_nota1').addEventListener('input', calcAverage);
                document.getElementById('edit_nota2').addEventListener('input', calcAverage);
            });
        });
        
        // Open history modal
        historyButtons.forEach(button => {
            button.addEventListener('click', function() {
                const studentId = this.getAttribute('data-id');
                console.log('Viewing history for student:', studentId);
                
                // Em uma aplicação real, você buscaria o histórico do aluno
                // e preencheria o modal com os dados corretos
                
                document.getElementById('history-modal').classList.add('open');
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
        
        // Form submission
        document.getElementById('grades-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Em uma aplicação real, você enviaria os dados para o servidor
            console.log('Grades form submitted');
            
            // Fechar o modal
            document.getElementById('grades-modal').classList.remove('open');
            
            // Feedback para o usuário
            alert('Notas lançadas com sucesso!');
            
            // Recarregar a lista de notas
            // window.location.reload();
        });
        
        // Edit Grade Form submission
        document.getElementById('edit-grade-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Em uma aplicação real, você enviaria os dados para o servidor
            console.log('Edit grade form submitted');
            
            // Fechar o modal
            document.getElementById('edit-grade-modal').classList.remove('open');
            
            // Feedback para o usuário
            alert('Notas atualizadas com sucesso!');
            
            // Recarregar a lista de notas
            // window.location.reload();
        });
    </script>
</body>
</html>