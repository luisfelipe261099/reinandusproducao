<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faciência ERP - Gestão de Disciplinas</title>
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
                    <a href="#" class="nav-item active flex items-center py-3 px-4 text-white">
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
                            <input type="text" class="pl-10 pr-4 py-2 border border-gray-300 rounded-full text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 w-64" placeholder="Buscar disciplina...">
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
                            <h1 class="text-2xl font-bold text-gray-800">Gestão de Disciplinas</h1>
                            <p class="text-gray-600">Gerencie todas as disciplinas dos cursos</p>
                        </div>
                        <div>
                            <button id="new-discipline-btn" class="btn btn-primary">
                                <i class="fas fa-plus mr-2"></i>
                                <span>Nova Disciplina</span>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Filters -->
                    <div class="bg-white p-4 rounded-t-lg border border-gray-200 mb-0">
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
                                    <select class="form-select py-2 pl-3 pr-10 text-sm" id="filter-status">
                                        <option value="">Todos os Status</option>
                                        <option value="ativo">Ativas</option>
                                        <option value="inativo">Inativas</option>
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
                                    <th class="w-16">Código</th>
                                    <th>Nome</th>
                                    <th>Curso</th>
                                    <th>Professor</th>
                                    <th>Carga Horária</th>
                                    <th>Status</th>
                                    <th class="w-20 text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>ADM101</td>
                                    <td>
                                        <div>
                                            <p class="font-medium text-gray-800">Introdução à Administração</p>
                                        </div>
                                    </td>
                                    <td>Administração</td>
                                    <td>Prof. Carlos Alberto</td>
                                    <td>60h</td>
                                    <td><span class="status-badge ativo">Ativo</span></td>
                                    <td>
                                        <div class="flex justify-end space-x-1">
                                            <button class="btn btn-sm btn-outline view-btn" data-id="1">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline edit-btn" data-id="1">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline delete-btn" data-id="1">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>DIR201</td>
                                    <td>
                                        <div>
                                            <p class="font-medium text-gray-800">Direito Civil</p>
                                        </div>
                                    </td>
                                    <td>Direito</td>
                                    <td>Profa. Maria Santos</td>
                                    <td>80h</td>
                                    <td><span class="status-badge ativo">Ativo</span></td>
                                    <td>
                                        <div class="flex justify-end space-x-1">
                                            <button class="btn btn-sm btn-outline view-btn" data-id="2">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline edit-btn" data-id="2">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline delete-btn" data-id="2">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>SI302</td>
                                    <td>
                                        <div>
                                            <p class="font-medium text-gray-800">Banco de Dados</p>
                                        </div>
                                    </td>
                                    <td>Sistemas de Informação</td>
                                    <td>Prof. João Silva</td>
                                    <td>80h</td>
                                    <td><span class="status-badge ativo">Ativo</span></td>
                                    <td>
                                        <div class="flex justify-end space-x-1">
                                            <button class="btn btn-sm btn-outline view-btn" data-id="3">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline edit-btn" data-id="3">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline delete-btn" data-id="3">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>PED105</td>
                                    <td>
                                        <div>
                                            <p class="font-medium text-gray-800">Psicologia da Educação</p>
                                        </div>
                                    </td>
                                    <td>Pedagogia</td>
                                    <td>Profa. Amanda Oliveira</td>
                                    <td>60h</td>
                                    <td><span class="status-badge ativo">Ativo</span></td>
                                    <td>
                                        <div class="flex justify-end space-x-1">
                                            <button class="btn btn-sm btn-outline view-btn" data-id="4">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline edit-btn" data-id="4">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline delete-btn" data-id="4">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>CONT203</td>
                                    <td>
                                        <div>
                                            <p class="font-medium text-gray-800">Contabilidade Avançada</p>
                                        </div>
                                    </td>
                                    <td>Ciências Contábeis</td>
                                    <td>Prof. Ricardo Melo</td>
                                    <td>80h</td>
                                    <td><span class="status-badge inativo">Inativo</span></td>
                                    <td>
                                        <div class="flex justify-end space-x-1">
                                            <button class="btn btn-sm btn-outline view-btn" data-id="5">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline edit-btn" data-id="5">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline delete-btn" data-id="5">
                                                <i class="fas fa-trash"></i>
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
                            Mostrando 1-5 de 42 disciplinas
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

    <!-- Modal de Nova/Editar Disciplina -->
    <div id="discipline-modal" class="modal">
        <div class="modal-content max-w-2xl">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h2 id="modal-title" class="text-xl font-bold text-gray-800">Nova Disciplina</h2>
                    <button class="text-gray-400 hover:text-gray-600 close-modal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            
            <div class="p-6">
                <form id="discipline-form">
                    <input type="hidden" id="discipline_id" name="id" value="">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="codigo" class="form-label">Código <span class="text-red-500">*</span></label>
                            <input type="text" id="codigo" name="codigo" class="form-input" placeholder="Ex: ADM101" required>
                        </div>
                        
                        <div>
                            <label for="nome" class="form-label">Nome <span class="text-red-500">*</span></label>
                            <input type="text" id="nome" name="nome" class="form-input" placeholder="Nome da disciplina" required>
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
                            <label for="professor_padrao_id" class="form-label">Professor Padrão</label>
                            <select id="professor_padrao_id" name="professor_padrao_id" class="form-select">
                                <option value="">Selecione o professor</option>
                                <option value="1">Prof. Carlos Alberto</option>
                                <option value="2">Profa. Maria Santos</option>
                                <option value="3">Prof. João Silva</option>
                                <option value="4">Profa. Amanda Oliveira</option>
                                <option value="5">Prof. Ricardo Melo</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="carga_horaria" class="form-label">Carga Horária (horas) <span class="text-red-500">*</span></label>
                            <input type="number" id="carga_horaria" name="carga_horaria" class="form-input" min="1" placeholder="Ex: 60" required>
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
                        <label for="ementa" class="form-label">Ementa</label>
                        <textarea id="ementa" name="ementa" class="form-textarea" rows="4" placeholder="Conteúdo programático da disciplina"></textarea>
                    </div>
                    
                    <div class="mb-6">
                        <label for="bibliografia" class="form-label">Bibliografia</label>
                        <textarea id="bibliografia" name="bibliografia" class="form-textarea" rows="4" placeholder="Bibliografia básica e complementar"></textarea>
                    </div>
                
                    <div class="flex justify-end space-x-3">
                        <button type="button" class="btn btn-outline close-modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="submit-btn">Salvar Disciplina</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal de Visualização de Disciplina -->
    <div id="view-discipline-modal" class="modal">
        <div class="modal-content max-w-2xl">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-bold text-gray-800">Detalhes da Disciplina</h2>
                    <button class="text-gray-400 hover:text-gray-600 close-modal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            
            <div class="p-6">
                <div class="bg-gray-50 p-4 rounded-lg mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-500">Código</p>
                            <p class="font-medium" id="view-codigo">ADM101</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Nome</p>
                            <p class="font-medium" id="view-nome">Introdução à Administração</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Curso</p>
                            <p class="font-medium" id="view-curso">Administração</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Professor</p>
                            <p class="font-medium" id="view-professor">Prof. Carlos Alberto</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Carga Horária</p>
                            <p class="font-medium" id="view-carga-horaria">60h</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Status</p>
                            <p id="view-status"><span class="status-badge ativo">Ativo</span></p>
                        </div>
                    </div>
                </div>
                
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Ementa</h3>
                    <div class="bg-white p-4 rounded-lg border border-gray-200">
                        <p id="view-ementa">
                            Introdução à administração. Teorias administrativas: abordagem clássica, humanística, neoclássica, estruturalista, comportamental, sistêmica e contingencial. Funções administrativas: planejamento, organização, direção e controle. Papéis e habilidades do administrador. Áreas funcionais da administração: produção, marketing, finanças e recursos humanos.
                        </p>
                    </div>
                </div>
                
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Bibliografia</h3>
                    <div class="bg-white p-4 rounded-lg border border-gray-200">
                        <p id="view-bibliografia">
                            <strong>Bibliografia Básica:</strong><br>
                            CHIAVENATO, Idalberto. Introdução à teoria geral da administração. 9. ed. Rio de Janeiro: Elsevier, 2014.<br>
                            MAXIMIANO, Antonio Cesar Amaru. Introdução à administração. 8. ed. São Paulo: Atlas, 2011.<br>
                            ROBBINS, Stephen P.; DECENZO, David A.; WOLTER, Robert. Fundamentos de gestão. São Paulo: Saraiva, 2012.<br><br>
                            
                            <strong>Bibliografia Complementar:</strong><br>
                            DRUCKER, Peter F. Introdução à administração. São Paulo: Pioneira Thomson Learning, 2002.<br>
                            STONER, James A. F.; FREEMAN, R. Edward. Administração. 5. ed. Rio de Janeiro: LTC, 2009.
                        </p>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" class="btn btn-outline close-modal">Fechar</button>
                    <button type="button" class="btn btn-primary edit-btn" data-id="1">
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
                    Tem certeza que deseja excluir a disciplina "<span id="delete-discipline-name">Introdução à Administração</span>"? Esta ação não pode ser desfeita.
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
        
        // Modal management
        const modals = document.querySelectorAll('.modal');
        const newDisciplineBtn = document.getElementById('new-discipline-btn');
        const viewButtons = document.querySelectorAll('.view-btn');
        const editButtons = document.querySelectorAll('.edit-btn');
        const deleteButtons = document.querySelectorAll('.delete-btn');
        const closeButtons = document.querySelectorAll('.close-modal');
        
        // Open new discipline modal
        newDisciplineBtn.addEventListener('click', function() {
            // Reset form
            document.getElementById('discipline-form').reset();
            document.getElementById('discipline_id').value = '';
            document.getElementById('modal-title').textContent = 'Nova Disciplina';
            document.getElementById('submit-btn').textContent = 'Salvar Disciplina';
            
            // Open modal
            document.getElementById('discipline-modal').classList.add('open');
        });
        
        // Open view discipline modal
        viewButtons.forEach(button => {
            button.addEventListener('click', function() {
                const disciplineId = this.getAttribute('data-id');
                console.log('Viewing discipline:', disciplineId);
                
                // Em uma aplicação real, você buscaria os dados da disciplina
                // e preencheria o modal com os dados corretos
                
                document.getElementById('view-discipline-modal').classList.add('open');
            });
        });
        
        // Open edit discipline modal
        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                const disciplineId = this.getAttribute('data-id');
                console.log('Editing discipline:', disciplineId);
                
                // Em uma aplicação real, você buscaria os dados da disciplina
                // e preencheria o formulário com os dados corretos
                
                document.getElementById('discipline_id').value = disciplineId;
                document.getElementById('modal-title').textContent = 'Editar Disciplina';
                document.getElementById('submit-btn').textContent = 'Atualizar Disciplina';
                
                document.getElementById('discipline-modal').classList.add('open');
                
                // Fechar o modal de visualização se estiver aberto
                document.getElementById('view-discipline-modal').classList.remove('open');
            });
        });
        
        // Open delete confirmation modal
        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const disciplineId = this.getAttribute('data-id');
                console.log('Deleting discipline:', disciplineId);
                
                // Em uma aplicação real, você buscaria o nome da disciplina
                // para exibir na confirmação
                
                document.getElementById('delete-modal').classList.add('open');
            });
        });
        
        // Confirm delete
        document.getElementById('confirm-delete-btn').addEventListener('click', function() {
            // Em uma aplicação real, você enviaria uma requisição para excluir a disciplina
            console.log('Discipline deleted');
            
            // Fechar o modal
            document.getElementById('delete-modal').classList.remove('open');
            
            // Feedback para o usuário
            alert('Disciplina excluída com sucesso!');
            
            // Recarregar a lista de disciplinas
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
        document.getElementById('discipline-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Em uma aplicação real, você enviaria os dados para o servidor
            console.log('Form submitted');
            
            // Fechar o modal
            document.getElementById('discipline-modal').classList.remove('open');
            
            // Feedback para o usuário
            const disciplineId = document.getElementById('discipline_id').value;
            const message = disciplineId ? 'Disciplina atualizada com sucesso!' : 'Disciplina criada com sucesso!';
            alert(message);
            
            // Recarregar a lista de disciplinas
            // window.location.reload();
        });
    </script>
</body>
</html>