<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faciência ERP - Cadastro de Aluno</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #3B82F6;
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
        
        .form-card {
            background-color: white;
            border-radius: 1rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        }
        
        .form-section {
            border-bottom: 1px solid #E5E7EB;
        }
        
        .form-section:last-child {
            border-bottom: none;
        }
        
        .form-label {
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
            display: block;
        }
        
        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #D1D5DB;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            transition: all 0.3s;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
        }
        
        .form-input.error {
            border-color: var(--danger);
        }
        
        .form-error {
            color: var(--danger);
            font-size: 0.75rem;
            margin-top: 0.25rem;
        }
        
        .form-select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #D1D5DB;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.75rem center;
            background-repeat: no-repeat;
            background-size: 1rem;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            transition: all 0.3s;
        }
        
        .form-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
        }
        
        .form-radio, .form-checkbox {
            width: 1.25rem;
            height: 1.25rem;
            border: 1px solid #D1D5DB;
            transition: all 0.3s;
        }
        
        .form-radio:checked, .form-checkbox:checked {
            border-color: var(--primary);
            background-color: var(--primary);
        }
        
        .tab {
            padding: 0.75rem 1.5rem;
            border-bottom: 2px solid transparent;
            font-weight: 500;
            font-size: 0.875rem;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .tab.active {
            border-bottom-color: var(--primary);
            color: var(--primary);
        }
        
        .tab:hover:not(.active) {
            border-bottom-color: #E5E7EB;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: white;
            border-radius: 0.5rem;
            padding: 0.75rem 1.5rem;
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
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .btn-outline:hover {
            background-color: #F3F4F6;
        }
        
        .btn-danger {
            background-color: var(--danger);
            color: white;
            border-radius: 0.5rem;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: background-color 0.2s;
        }
        
        .btn-danger:hover {
            background-color: #DC2626;
        }
        
        .photo-upload {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background-color: #F3F4F6;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            overflow: hidden;
            position: relative;
        }
        
        .photo-upload:hover {
            background-color: #E5E7EB;
        }
        
        .photo-upload img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .photo-upload .upload-icon {
            position: absolute;
            bottom: 0;
            right: 0;
            background-color: var(--primary);
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 3px solid white;
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
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .steps {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin-bottom: 2rem;
        }
        
        .steps::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: #E5E7EB;
            z-index: 1;
        }
        
        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 2;
        }
        
        .step-number {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: #F3F4F6;
            border: 2px solid #E5E7EB;
            color: #6B7280;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-bottom: 0.5rem;
            transition: all 0.3s;
        }
        
        .step-text {
            font-size: 0.75rem;
            color: #6B7280;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .step.active .step-number {
            background-color: var(--primary);
            border-color: var(--primary);
            color: white;
        }
        
        .step.active .step-text {
            color: var(--primary);
        }
        
        .step.completed .step-number {
            background-color: var(--secondary);
            border-color: var(--secondary);
            color: white;
        }
        
        .step.completed .step-text {
            color: var(--secondary);
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
                            <input type="text" class="search-bar pl-10" placeholder="Buscar aluno, curso ou documentos...">
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
                        <h1 class="text-2xl font-bold text-gray-800">Cadastrar Novo Aluno</h1>
                        <p class="text-gray-600">Preencha as informações para cadastrar um novo aluno no sistema</p>
                    </div>
                    <div>
                        <a href="#" class="btn-outline flex items-center">
                            <i class="fas fa-arrow-left mr-2"></i>
                            <span>Voltar</span>
                        </a>
                    </div>
                </div>
                
                <!-- Steps -->
                <div class="steps mb-8">
                    <div class="step active">
                        <div class="step-number">1</div>
                        <div class="step-text">Dados Pessoais</div>
                    </div>
                    <div class="step">
                        <div class="step-number">2</div>
                        <div class="step-text">Contato</div>
                    </div>
                    <div class="step">
                        <div class="step-number">3</div>
                        <div class="step-text">Acadêmico</div>
                    </div>
                    <div class="step">
                        <div class="step-number">4</div>
                        <div class="step-text">Documentos</div>
                    </div>
                    <div class="step">
                        <div class="step-number">5</div>
                        <div class="step-text">Financeiro</div>
                    </div>
                </div>
                
                <!-- Form -->
                <form action="" method="post" class="form-card">
                    <!-- Tab Navigation -->
                    <div class="bg-gray-50 p-4 flex border-b border-gray-200">
                        <div class="tab active" data-tab="dados-pessoais">Dados Pessoais</div>
                        <div class="tab" data-tab="contato">Contato</div>
                        <div class="tab" data-tab="academico">Acadêmico</div>
                        <div class="tab" data-tab="documentos">Documentos</div>
                        <div class="tab" data-tab="financeiro">Financeiro</div>
                    </div>
                    
                    <!-- Tab Content - Dados Pessoais -->
                    <div class="tab-content active p-6" id="dados-pessoais">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
                            <div class="md:col-span-3 flex justify-center">
                                <div class="photo-upload">
                                    <input type="file" class="hidden" id="photo-input">
                                    <img id="preview-photo" src="/api/placeholder/150/150" alt="Foto do Aluno" class="hidden">
                                    <div id="upload-placeholder" class="flex flex-col items-center justify-center">
                                        <i class="fas fa-user text-gray-400 text-4xl mb-2"></i>
                                        <p class="text-gray-500 text-sm text-center">Clique para<br>adicionar foto</p>
                                    </div>
                                    <div class="upload-icon">
                                        <i class="fas fa-camera"></i>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="md:col-span-9 grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="md:col-span-2">
                                    <label for="nome" class="form-label">Nome Completo <span class="text-red-500">*</span></label>
                                    <input type="text" id="nome" name="nome" class="form-input" placeholder="Digite o nome completo" required>
                                </div>
                                
                                <div>
                                    <label for="nome_social" class="form-label">Nome Social</label>
                                    <input type="text" id="nome_social" name="nome_social" class="form-input" placeholder="Se aplicável">
                                </div>
                                
                                <div>
                                    <label for="data_nascimento" class="form-label">Data de Nascimento <span class="text-red-500">*</span></label>
                                    <input type="date" id="data_nascimento" name="data_nascimento" class="form-input" required>
                                </div>
                                
                                <div>
                                    <label for="cpf" class="form-label">CPF <span class="text-red-500">*</span></label>
                                    <input type="text" id="cpf" name="cpf" class="form-input" placeholder="000.000.000-00" required>
                                </div>
                                
                                <div>
                                    <label for="rg" class="form-label">RG</label>
                                    <input type="text" id="rg" name="rg" class="form-input" placeholder="00.000.000-0">
                                </div>
                                
                                <div>
                                    <label class="form-label">Sexo <span class="text-red-500">*</span></label>
                                    <div class="flex space-x-4 mt-2">
                                        <label class="flex items-center">
                                            <input type="radio" name="sexo" value="masculino" class="form-radio mr-2" required>
                                            <span>Masculino</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="radio" name="sexo" value="feminino" class="form-radio mr-2">
                                            <span>Feminino</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="radio" name="sexo" value="outro" class="form-radio mr-2">
                                            <span>Outro</span>
                                        </label>
                                    </div>
                                </div>
                                
                                <div>
                                    <label for="naturalidade" class="form-label">Naturalidade</label>
                                    <select id="naturalidade" name="naturalidade_id" class="form-select">
                                        <option value="">Selecione</option>
                                        <option value="1">São Paulo - SP</option>
                                        <option value="2">Rio de Janeiro - RJ</option>
                                        <option value="3">Belo Horizonte - MG</option>
                                        <option value="4">Salvador - BA</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label for="estado_civil" class="form-label">Estado Civil</label>
                                    <select id="estado_civil" name="estado_civil_id" class="form-select">
                                        <option value="">Selecione</option>
                                        <option value="1">Solteiro(a)</option>
                                        <option value="2">Casado(a)</option>
                                        <option value="3">Divorciado(a)</option>
                                        <option value="4">Viúvo(a)</option>
                                        <option value="5">União Estável</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex justify-end mt-8">
                            <button type="button" class="btn-primary next-step" data-next="contato">
                                Continuar <i class="fas fa-arrow-right ml-2"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Tab Content - Contato -->
                    <div class="tab-content p-6" id="contato">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="email" class="form-label">Email <span class="text-red-500">*</span></label>
                                <input type="email" id="email" name="email" class="form-input" placeholder="email@exemplo.com" required>
                            </div>
                            
                            <div>
                                <label for="telefone" class="form-label">Telefone</label>
                                <input type="text" id="telefone" name="telefone" class="form-input" placeholder="(00) 0000-0000">
                            </div>
                            
                            <div>
                                <label for="celular" class="form-label">Celular <span class="text-red-500">*</span></label>
                                <input type="text" id="celular" name="celular" class="form-input" placeholder="(00) 00000-0000" required>
                            </div>
                            
                            <div class="md:col-span-2">
                                <label for="endereco" class="form-label">Endereço <span class="text-red-500">*</span></label>
                                <input type="text" id="endereco" name="endereco" class="form-input" placeholder="Rua, Avenida, etc." required>
                            </div>
                            
                            <div>
                                <label for="numero" class="form-label">Número <span class="text-red-500">*</span></label>
                                <input type="text" id="numero" name="numero" class="form-input" placeholder="Número" required>
                            </div>
                            
                            <div>
                                <label for="bairro" class="form-label">Bairro <span class="text-red-500">*</span></label>
                                <input type="text" id="bairro" name="bairro" class="form-input" placeholder="Bairro" required>
                            </div>
                            
                            <div>
                                <label for="cidade" class="form-label">Cidade <span class="text-red-500">*</span></label>
                                <select id="cidade" name="cidade_id" class="form-select" required>
                                    <option value="">Selecione</option>
                                    <option value="1">São Paulo</option>
                                    <option value="2">Rio de Janeiro</option>
                                    <option value="3">Belo Horizonte</option>
                                    <option value="4">Salvador</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="cep" class="form-label">CEP <span class="text-red-500">*</span></label>
                                <input type="text" id="cep" name="cep" class="form-input" placeholder="00000-000" required>
                            </div>
                        </div>
                        
                        <div class="flex justify-between mt-8">
                            <button type="button" class="btn-outline prev-step" data-prev="dados-pessoais">
                                <i class="fas fa-arrow-left mr-2"></i> Voltar
                            </button>
                            <button type="button" class="btn-primary next-step" data-next="academico">
                                Continuar <i class="fas fa-arrow-right ml-2"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Tab Content - Acadêmico -->
                    <div class="tab-content p-6" id="academico">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="polo" class="form-label">Polo <span class="text-red-500">*</span></label>
                                <select id="polo" name="polo_id" class="form-select" required>
                                    <option value="">Selecione</option>
                                    <option value="1">Polo Central</option>
                                    <option value="2">Polo Norte</option>
                                    <option value="3">Polo Sul</option>
                                    <option value="4">Polo Leste</option>
                                    <option value="5">Polo Oeste</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="curso" class="form-label">Curso <span class="text-red-500">*</span></label>
                                <select id="curso" name="curso_id" class="form-select" required>
                                    <option value="">Selecione</option>
                                    <option value="1">Administração</option>
                                    <option value="2">Direito</option>
                                    <option value="3">Sistemas de Informação</option>
                                    <option value="4">Pedagogia</option>
                                    <option value="5">Ciências Contábeis</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="professor_orientador" class="form-label">Professor Orientador</label>
                                <select id="professor_orientador" name="professor_orientador_id" class="form-select">
                                    <option value="">Selecione</option>
                                    <option value="1">Prof. Carlos Alberto</option>
                                    <option value="2">Profa. Maria Santos</option>
                                    <option value="3">Prof. João Silva</option>
                                    <option value="4">Profa. Amanda Oliveira</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="data_ingresso" class="form-label">Data de Ingresso <span class="text-red-500">*</span></label>
                                <input type="date" id="data_ingresso" name="data_ingresso" class="form-input" required>
                            </div>
                            
                            <div>
                                <label for="curso_inicio" class="form-label">Início do Curso <span class="text-red-500">*</span></label>
                                <input type="date" id="curso_inicio" name="curso_inicio" class="form-input" required>
                            </div>
                            
                            <div>
                                <label for="curso_fim" class="form-label">Previsão de Fim do Curso</label>
                                <input type="date" id="curso_fim" name="curso_fim" class="form-input">
                            </div>
                            
                            <div>
                                <label for="status" class="form-label">Status <span class="text-red-500">*</span></label>
                                <select id="status" name="status" class="form-select" required>
                                    <option value="ativo">Ativo</option>
                                    <option value="trancado">Trancado</option>
                                    <option value="cancelado">Cancelado</option>
                                    <option value="formado">Formado</option>
                                    <option value="desistente">Desistente</option>
                                </select>
                            </div>
                            
                            <div class="md:col-span-2">
                                <label for="observacoes" class="form-label">Observações Acadêmicas</label>
                                <textarea id="observacoes" name="observacoes" class="form-input" rows="3" placeholder="Informações adicionais sobre o aluno..."></textarea>
                            </div>
                        </div>
                        
                        <div class="flex justify-between mt-8">
                            <button type="button" class="btn-outline prev-step" data-prev="contato">
                                <i class="fas fa-arrow-left mr-2"></i> Voltar
                            </button>
                            <button type="button" class="btn-primary next-step" data-next="documentos">
                                Continuar <i class="fas fa-arrow-right ml-2"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Tab Content - Documentos -->
                    <div class="tab-content p-6" id="documentos">
                        <div class="mb-8">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Documentação Obrigatória</h3>
                            <div class="bg-blue-50 p-4 rounded-lg mb-6">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0 mt-0.5">
                                        <i class="fas fa-info-circle text-blue-500"></i>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-blue-700">
                                            Os documentos marcados abaixo foram entregues pelo aluno. Você pode atualizar o status de cada documento conforme necessário.
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="bg-white p-4 rounded-lg border border-gray-200">
                                    <label class="flex items-center space-x-3">
                                        <input type="checkbox" name="entregou_cpf" value="1" class="form-checkbox">
                                        <span class="text-gray-700">Cópia do CPF</span>
                                    </label>
                                </div>
                                
                                <div class="bg-white p-4 rounded-lg border border-gray-200">
                                    <label class="flex items-center space-x-3">
                                        <input type="checkbox" name="entregou_rg" value="1" class="form-checkbox">
                                        <span class="text-gray-700">Cópia do RG</span>
                                    </label>
                                </div>
                                
                                <div class="bg-white p-4 rounded-lg border border-gray-200">
                                    <label class="flex items-center space-x-3">
                                        <input type="checkbox" name="entregou_certidao" value="1" class="form-checkbox">
                                        <span class="text-gray-700">Certidão de Nascimento/Casamento</span>
                                    </label>
                                </div>
                                
                                <div class="bg-white p-4 rounded-lg border border-gray-200">
                                    <label class="flex items-center space-x-3">
                                        <input type="checkbox" name="entregou_historico" value="1" class="form-checkbox">
                                        <span class="text-gray-700">Histórico Escolar</span>
                                    </label>
                                </div>
                                
                                <div class="bg-white p-4 rounded-lg border border-gray-200">
                                    <label class="flex items-center space-x-3">
                                        <input type="checkbox" name="entregou_comprovante_residencia" value="1" class="form-checkbox">
                                        <span class="text-gray-700">Comprovante de Residência</span>
                                    </label>
                                </div>
                                
                                <div class="bg-white p-4 rounded-lg border border-gray-200">
                                    <label class="flex items-center space-x-3">
                                        <input type="checkbox" name="entregou_foto" value="1" class="form-checkbox">
                                        <span class="text-gray-700">Foto 3x4</span>
                                    </label>
                                </div>
                                
                                <div class="bg-white p-4 rounded-lg border border-gray-200">
                                    <label class="flex items-center space-x-3">
                                        <input type="checkbox" name="entregou_diploma" value="1" class="form-checkbox">
                                        <span class="text-gray-700">Diploma Anterior</span>
                                    </label>
                                </div>
                                
                                <div class="bg-white p-4 rounded-lg border border-gray-200">
                                    <label class="flex items-center space-x-3">
                                        <input type="checkbox" name="entregou_contrato" value="1" class="form-checkbox">
                                        <span class="text-gray-700">Contrato Assinado</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Anexar Documentos</h3>
                            <div class="bg-yellow-50 p-4 rounded-lg mb-6">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0 mt-0.5">
                                        <i class="fas fa-exclamation-triangle text-yellow-500"></i>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-yellow-700">
                                            Esta funcionalidade permitirá anexar arquivos digitais dos documentos. Atualmente em implementação.
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="bg-white p-6 rounded-lg border border-gray-200 flex items-center justify-center">
                                <div class="text-center">
                                    <i class="fas fa-file-upload text-gray-400 text-4xl mb-3"></i>
                                    <p class="text-gray-500 mb-2">Arraste e solte os arquivos aqui ou</p>
                                    <button type="button" class="btn-outline">
                                        <i class="fas fa-plus mr-2"></i> Selecionar Arquivos
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex justify-between mt-8">
                            <button type="button" class="btn-outline prev-step" data-prev="academico">
                                <i class="fas fa-arrow-left mr-2"></i> Voltar
                            </button>
                            <button type="button" class="btn-primary next-step" data-next="financeiro">
                                Continuar <i class="fas fa-arrow-right ml-2"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Tab Content - Financeiro -->
                    <div class="tab-content p-6" id="financeiro">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="bolsa" class="form-label">Bolsa (%)</label>
                                <input type="number" id="bolsa" name="bolsa" class="form-input" min="0" max="100" step="0.1" placeholder="0">
                                <p class="text-sm text-gray-500 mt-1">Deixe em branco ou 0 se não houver bolsa</p>
                            </div>
                            
                            <div>
                                <label for="desconto" class="form-label">Desconto (%)</label>
                                <input type="number" id="desconto" name="desconto" class="form-input" min="0" max="100" step="0.1" placeholder="0">
                                <p class="text-sm text-gray-500 mt-1">Deixe em branco ou 0 se não houver desconto</p>
                            </div>
                            
                            <div>
                                <label for="valor_mensalidade" class="form-label">Valor da Mensalidade (R$)</label>
                                <input type="text" id="valor_mensalidade" name="valor_mensalidade" class="form-input" placeholder="0,00">
                            </div>
                            
                            <div>
                                <label for="dia_vencimento" class="form-label">Dia de Vencimento</label>
                                <select id="dia_vencimento" name="dia_vencimento" class="form-select">
                                    <option value="">Selecione</option>
                                    <?php for ($i = 1; $i <= 28; $i++): ?>
                                        <option value="<?= $i ?>"><?= $i ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            
                            <div class="md:col-span-2">
                                <label for="forma_pagamento" class="form-label">Forma de Pagamento Preferencial</label>
                                <select id="forma_pagamento" name="forma_pagamento" class="form-select">
                                    <option value="">Selecione</option>
                                    <option value="boleto">Boleto Bancário</option>
                                    <option value="cartao_credito">Cartão de Crédito</option>
                                    <option value="pix">PIX</option>
                                    <option value="transferencia">Transferência Bancária</option>
                                </select>
                            </div>
                            
                            <div class="md:col-span-2">
                                <label for="observacoes_financeiras" class="form-label">Observações Financeiras</label>
                                <textarea id="observacoes_financeiras" name="observacoes_financeiras" class="form-input" rows="3" placeholder="Informações adicionais sobre pagamento, descontos especiais, etc..."></textarea>
                            </div>
                        </div>
                        
                        <div class="mt-8 p-4 bg-gray-50 rounded-lg">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Resumo Financeiro</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div class="bg-white p-4 rounded-lg border border-gray-200">
                                    <p class="text-sm font-medium text-gray-500">Valor Integral</p>
                                    <p class="text-xl font-bold text-gray-800 mt-1">R$ <span id="valor-integral">0,00</span></p>
                                </div>
                                
                                <div class="bg-white p-4 rounded-lg border border-gray-200">
                                    <p class="text-sm font-medium text-gray-500">Descontos Aplicados</p>
                                    <p class="text-xl font-bold text-red-600 mt-1">- R$ <span id="valor-descontos">0,00</span></p>
                                </div>
                                
                                <div class="bg-white p-4 rounded-lg border border-gray-200">
                                    <p class="text-sm font-medium text-gray-500">Valor Final</p>
                                    <p class="text-xl font-bold text-green-600 mt-1">R$ <span id="valor-final">0,00</span></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex justify-between mt-8">
                            <button type="button" class="btn-outline prev-step" data-prev="documentos">
                                <i class="fas fa-arrow-left mr-2"></i> Voltar
                            </button>
                            <button type="submit" class="btn-primary">
                                <i class="fas fa-save mr-2"></i> Salvar Cadastro
                            </button>
                        </div>
                    </div>
                </form>
            </main>
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
        
        // Photo upload
        const photoUpload = document.querySelector('.photo-upload');
        const photoInput = document.getElementById('photo-input');
        const previewPhoto = document.getElementById('preview-photo');
        const uploadPlaceholder = document.getElementById('upload-placeholder');
        
        photoUpload.addEventListener('click', function() {
            photoInput.click();
        });
        
        photoInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    previewPhoto.src = e.target.result;
                    previewPhoto.classList.remove('hidden');
                    uploadPlaceholder.classList.add('hidden');
                }
                
                reader.readAsDataURL(this.files[0]);
            }
        });
        
        // Tabs
        const tabs = document.querySelectorAll('.tab');
        const tabContents = document.querySelectorAll('.tab-content');
        const steps = document.querySelectorAll('.step');
        
        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const tabId = this.getAttribute('data-tab');
                
                // Update active tab
                tabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                // Show corresponding tab content
                tabContents.forEach(content => {
                    content.classList.remove('active');
                    if (content.id === tabId) {
                        content.classList.add('active');
                    }
                });
                
                // Update steps
                updateSteps(tabId);
            });
        });
        
        // Next and Previous buttons
        const nextButtons = document.querySelectorAll('.next-step');
        const prevButtons = document.querySelectorAll('.prev-step');
        
        nextButtons.forEach(button => {
            button.addEventListener('click', function() {
                const nextTabId = this.getAttribute('data-next');
                
                // Update active tab
                tabs.forEach(tab => {
                    tab.classList.remove('active');
                    if (tab.getAttribute('data-tab') === nextTabId) {
                        tab.classList.add('active');
                    }
                });
                
                // Show corresponding tab content
                tabContents.forEach(content => {
                    content.classList.remove('active');
                    if (content.id === nextTabId) {
                        content.classList.add('active');
                    }
                });
                
                // Update steps
                updateSteps(nextTabId);
            });
        });
        
        prevButtons.forEach(button => {
            button.addEventListener('click', function() {
                const prevTabId = this.getAttribute('data-prev');
                
                // Update active tab
                tabs.forEach(tab => {
                    tab.classList.remove('active');
                    if (tab.getAttribute('data-tab') === prevTabId) {
                        tab.classList.add('active');
                    }
                });
                
                // Show corresponding tab content
                tabContents.forEach(content => {
                    content.classList.remove('active');
                    if (content.id === prevTabId) {
                        content.classList.add('active');
                    }
                });
                
                // Update steps
                updateSteps(prevTabId);
            });
        });
        
        // Update steps function
        function updateSteps(activeTabId) {
            const stepMapping = {
                'dados-pessoais': 0,
                'contato': 1,
                'academico': 2,
                'documentos': 3,
                'financeiro': 4
            };
            
            const activeIndex = stepMapping[activeTabId];
            
            steps.forEach((step, index) => {
                step.classList.remove('active', 'completed');
                
                if (index === activeIndex) {
                    step.classList.add('active');
                } else if (index < activeIndex) {
                    step.classList.add('completed');
                }
            });
        }
        
        // Mask for CPF
        const cpfInput = document.getElementById('cpf');
        cpfInput.addEventListener('input', function(e) {
            let value = e.target.value;
            
            // Remove non-digits
            value = value.replace(/\D/g, '');
            
            // Apply mask
            if (value.length <= 3) {
                // Do nothing
            } else if (value.length <= 6) {
                value = value.replace(/(\d{3})(\d+)/, '$1.$2');
            } else if (value.length <= 9) {
                value = value.replace(/(\d{3})(\d{3})(\d+)/, '$1.$2.$3');
            } else {
                value = value.replace(/(\d{3})(\d{3})(\d{3})(\d+)/, '$1.$2.$3-$4');
            }
            
            e.target.value = value;
        });
        
        // Mask for CEP
        const cepInput = document.getElementById('cep');
        cepInput.addEventListener('input', function(e) {
            let value = e.target.value;
            
            // Remove non-digits
            value = value.replace(/\D/g, '');
            
            // Apply mask
            if (value.length <= 5) {
                // Do nothing
            } else {
                value = value.replace(/(\d{5})(\d+)/, '$1-$2');
            }
            
            e.target.value = value;
        });
        
        // Mask for phone
        const telefoneInput = document.getElementById('telefone');
        telefoneInput.addEventListener('input', function(e) {
            let value = e.target.value;
            
            // Remove non-digits
            value = value.replace(/\D/g, '');
            
            // Apply mask
            if (value.length <= 2) {
                // Do nothing
            } else if (value.length <= 6) {
                value = value.replace(/(\d{2})(\d+)/, '($1) $2');
            } else {
                value = value.replace(/(\d{2})(\d{4})(\d+)/, '($1) $2-$3');
            }
            
            e.target.value = value;
        });
        
        // Mask for cellphone
        const celularInput = document.getElementById('celular');
        celularInput.addEventListener('input', function(e) {
            let value = e.target.value;
            
            // Remove non-digits
            value = value.replace(/\D/g, '');
            
            // Apply mask
            if (value.length <= 2) {
                // Do nothing
            } else if (value.length <= 7) {
                value = value.replace(/(\d{2})(\d+)/, '($1) $2');
            } else {
                value = value.replace(/(\d{2})(\d{5})(\d+)/, '($1) $2-$3');
            }
            
            e.target.value = value;
        });
        
        // Mask for money
        const moneyInput = document.getElementById('valor_mensalidade');
        moneyInput.addEventListener('input', function(e) {
            let value = e.target.value;
            
            // Remove non-digits
            value = value.replace(/\D/g, '');
            
            // Convert to decimal
            value = (parseFloat(value) / 100).toFixed(2);
            
            // Format as currency
            value = value.replace('.', ',');
            value = value.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            
            e.target.value = value;
        });
        
        // Calculate financial summary
        const valorMensalidadeInput = document.getElementById('valor_mensalidade');
        const bolsaInput = document.getElementById('bolsa');
        const descontoInput = document.getElementById('desconto');
        
        function updateFinancialSummary() {
            let valorIntegral = valorMensalidadeInput.value.replace(/\./g, '').replace(',', '.');
            valorIntegral = parseFloat(valorIntegral) || 0;
            
            const bolsaPercent = parseFloat(bolsaInput.value) || 0;
            const descontoPercent = parseFloat(descontoInput.value) || 0;
            
            const valorBolsa = valorIntegral * (bolsaPercent / 100);
            const valorDesconto = valorIntegral * (descontoPercent / 100);
            const totalDescontos = valorBolsa + valorDesconto;
            const valorFinal = valorIntegral - totalDescontos;
            
            document.getElementById('valor-integral').textContent = valorIntegral.toFixed(2).replace('.', ',');
            document.getElementById('valor-descontos').textContent = totalDescontos.toFixed(2).replace('.', ',');
            document.getElementById('valor-final').textContent = valorFinal.toFixed(2).replace('.', ',');
        }
        
        valorMensalidadeInput.addEventListener('input', updateFinancialSummary);
        bolsaInput.addEventListener('input', updateFinancialSummary);
        descontoInput.addEventListener('input', updateFinancialSummary);
        
        // Initialize financial summary
        updateFinancialSummary();
    </script>
</body>
</html>