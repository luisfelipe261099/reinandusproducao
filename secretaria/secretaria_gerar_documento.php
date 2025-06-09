<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faciência ERP - Geração de Documentos</title>
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
        
        .status-badge.solicitado {
            background-color: #E0E7FF;
            color: #4338CA;
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
            background-color: #BFDBFE;
            color: #1E40AF;
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
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        
        .modal-content {
            background-color: white;
            border-radius: 0.75rem;
            width: 90%;
            max-width: 500px;
            padding: 1.5rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
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
                            <a href="#" class="flex items-center p-3 text-white">
                                <i class="fas fa-home mr-3"></i>
                                <span class="sidebar-text">Início</span>
                            </a>
                        </li>
                        <li class="nav-item active">
                            <a href="#" class="flex items-center p-3 text-white">
                                <i class="fas fa-file-alt mr-3"></i>
                                <span class="sidebar-text">Gerar Documentos</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="flex items-center p-3 text-white">
                                <i class="fas fa-list mr-3"></i>
                                <span class="sidebar-text">Documentos Gerados</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="flex items-center p-3 text-white">
                                <i class="fas fa-cog mr-3"></i>
                                <span class="sidebar-text">Configurações</span>
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
                    <h1 class="text-2xl font-semibold text-gray-800 ml-4">Geração de Documentos</h1>
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
                <div class="form-card p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-6">Gerar Novo Documento</h2>
                    
                    <form id="documentGenerationForm">
                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <label for="documentType" class="form-label">Tipo de Documento</label>
                                <select id="documentType" class="form-select" required>
                                    <option value="">Selecione o tipo de documento</option>
                                    <option value="declaracao">Declaração</option>
                                    <option value="historico">Histórico Escolar</option>
                                    <option value="atestado">Atestado</option>
                                    <option value="certificado">Certificado</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="studentName" class="form-label">Nome do Aluno</label>
                                <input type="text" id="studentName" class="form-input" placeholder="Digite o nome completo" required>
                            </div>
                            
                            <div>
                                <label for="courseSelect" class="form-label">Curso</label>
                                <select id="courseSelect" class="form-select" required>
                                    <option value="">Selecione o curso</option>
                                    <option value="ads">Análise e Desenvolvimento de Sistemas</option>
                                    <option value="enfermagem">Enfermagem</option>
                                    <option value="direito">Direito</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="registrationNumber" class="form-label">Matrícula</label>
                                <input type="text" id="registrationNumber" class="form-input" placeholder="Número de matrícula" required>
                            </div>
                        </div>
                        
                        <div class="mt-6">
                            <label for="additionalObservations" class="form-label">Observações Adicionais</label>
                            <textarea id="additionalObservations" class="form-textarea" rows="3" placeholder="Adicione observações complementares (opcional)"></textarea>
                        </div>
                        
                        <div class="mt-6 flex justify-end space-x-4">
                            <button type="button" class="btn btn-outline">Cancelar</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-file-upload mr-2"></i>
                                Gerar Documento
                            </button>
                        </div>
                    </form>
                </div>

                <div class="mt-8">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-semibold text-gray-800">Documentos Gerados Recentemente</h2>
                        <div class="flex items-center space-x-4">
                            <div class="relative flex-grow">
                                <input type="text" placeholder="Pesquisar documentos..." class="form-input pl-10">
                                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                            </div>
                            <button class="btn btn-outline btn-sm">
                                <i class="fas fa-filter mr-2"></i>
                                Filtros
                            </button>
                        </div>
                    </div>

                    <div class="table-container mt-4">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>
                                        <input type="checkbox" class="form-checkbox">
                                    </th>
                                    <th>Documento</th>
                                    <th>Aluno</th>
                                    <th>Curso</th>
                                    <th>Data Geração</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <input type="checkbox" class="form-checkbox">
                                    </td>
                                    <td>Declaração</td>
                                    <td>João Silva Santos</td>
                                    <td>Análise e Desenvolvimento de Sistemas</td>
                                    <td>14/04/2025 10:30</td>
                                    <td>
                                        <span class="status-badge pronto">Pronto</span>
                                    </td>
                                    <td>
                                        <div class="flex space-x-2">
                                            <button class="text-blue-600 hover:text-blue-800" title="Visualizar">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="text-green-600 hover:text-green-800" title="Baixar">
                                                <i class="fas fa-download"></i>
                                            </button>
                                            <button class="text-red-600 hover:text-red-800" title="Excluir">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <input type="checkbox" class="form-checkbox">
                                    </td>
                                    <td>Histórico Escolar</td>
                                    <td>Maria Aparecida Oliveira</td>
                                    <td>Enfermagem</td>
                                    <td>12/04/2025 15:45</td>
                                    <td>
                                        <span class="status-badge processando">Processando</span>
                                    </td>
                                    <td>
                                        <div class="flex space-x-2">
                                            <button class="text-blue-600 hover:text-blue-800" title="Visualizar">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="text-green-600 hover:text-green-800" title="Baixar" disabled>
                                                <i class="fas fa-download"></i>
                                            </button>
                                            <button class="text-red-600 hover:text-red-800" title="Cancelar">
                                                <i class="fas fa-times-circle"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <input type="checkbox" class="form-checkbox">
                                    </td>
                                    <td>Atestado</td>
                                    <td>Pedro Henrique Souza</td>
                                    <td>Direito</td>
                                    <td>10/04/2025 09:15</td>
                                    <td>
                                        <span class="status-badge solicitado">Solicitado</span>
                                    </td>
                                    <td>
                                        <div class="flex space-x-2">
                                            <button class="text-blue-600 hover:text-blue-800" title="Visualizar">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="text-green-600 hover:text-green-800" title="Baixar" disabled>
                                                <i class="fas fa-download"></i>
                                            </button>
                                            <button class="text-red-600 hover:text-red-800" title="Cancelar">
                                                <i class="fas fa-times-circle"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 flex justify-between items-center">
                        <div class="text-sm text-gray-600">
                            Mostrando 3 de 25 documentos
                        </div>
                        <div class="flex space-x-2">
                            <button class="btn btn-outline btn-sm">
                                <i class="fas fa-chevron-left mr-2"></i>
                                Anterior
                            </button>
                            <button class="btn btn-outline btn-sm">
                                Próximo
                                <i class="fas fa-chevron-right ml-2"></i>
                            </button>
                        </div>
                    </div>
                </div>
                                <div class="form-card p-6 mt-6">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-xl font-semibold text-gray-800">Geração em Massa de Documentos</h2>
                            <div class="flex items-center space-x-2">
                                <span class="text-sm text-gray-600">Método de Importação:</span>
                                <select id="bulkImportMethod" class="form-select w-auto">
                                    <option value="csv">Importar CSV</option>
                                    <option value="excel">Importar Excel</option>
                                    <option value="manual">Seleção Manual</option>
                                </select>
                            </div>
                        </div>

                        <div id="csvImportSection">
                            <div class="border-dashed border-2 border-gray-300 rounded-lg p-6 text-center">
                                <i class="fas fa-file-upload text-4xl text-gray-400 mb-4"></i>
                                <p class="text-gray-600 mb-4">Arraste e solte seu arquivo CSV aqui ou</p>
                                <label for="csvUpload" class="btn btn-primary cursor-pointer">
                                    <i class="fas fa-upload mr-2"></i>
                                    Selecionar Arquivo
                                </label>
                                <input type="file" id="csvUpload" class="hidden" accept=".csv" />
                            </div>
                            <div id="csvPreview" class="mt-4 hidden">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-lg font-medium text-gray-800">Pré-visualização do Arquivo</h3>
                                    <button id="clearCsvUpload" class="text-red-600 hover:text-red-800">
                                        <i class="fas fa-trash mr-2"></i>Limpar
                                    </button>
                                </div>
                                <div class="table-container max-h-64 overflow-y-auto">
                                    <table class="data-table" id="csvPreviewTable">
                                        <thead>
                                            <tr>
                                                <th>Nome</th>
                                                <th>Curso</th>
                                                <th>Matrícula</th>
                                                <th>Tipo Documento</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Rows will be dynamically populated -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div id="manualSelectionSection" class="hidden">
                            <div class="grid grid-cols-2 gap-6">
                                <div>
                                    <label for="bulkCourseSelect" class="form-label">Filtrar por Curso</label>
                                    <select id="bulkCourseSelect" class="form-select">
                                        <option value="">Todos os Cursos</option>
                                        <option value="ads">Análise e Desenvolvimento de Sistemas</option>
                                        <option value="enfermagem">Enfermagem</option>
                                        <option value="direito">Direito</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="bulkDocumentType" class="form-label">Tipo de Documento</label>
                                    <select id="bulkDocumentType" class="form-select">
                                        <option value="">Selecione o tipo</option>
                                        <option value="declaracao">Declaração</option>
                                        <option value="historico">Histórico Escolar</option>
                                        <option value="atestado">Atestado</option>
                                        <option value="certificado">Certificado</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mt-6 table-container max-h-64 overflow-y-auto">
                                <table class="data-table" id="bulkStudentTable">
                                    <thead>
                                        <tr>
                                            <th>
                                                <input type="checkbox" id="selectAllStudents" class="form-checkbox">
                                            </th>
                                            <th>Nome</th>
                                            <th>Curso</th>
                                            <th>Matrícula</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Rows will be dynamically populated -->
                                        <tr>
                                            <td><input type="checkbox" class="form-checkbox student-checkbox"></td>
                                            <td>João Silva Santos</td>
                                            <td>Análise e Desenvolvimento de Sistemas</td>
                                            <td>2023001</td>
                                        </tr>
                                        <tr>
                                            <td><input type="checkbox" class="form-checkbox student-checkbox"></td>
                                            <td>Maria Aparecida Oliveira</td>
                                            <td>Enfermagem</td>
                                            <td>2023002</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end space-x-4">
                            <button id="generateBulkDocuments" class="btn btn-primary">
                                <i class="fas fa-file-upload mr-2"></i>
                                Gerar Documentos em Massa
                            </button>
                        </div>
                    </div>

                    <!-- Bulk Generation Modal -->
                    <div id="bulkGenerationModal" class="modal">
                        <div class="modal-content">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Geração em Massa</h3>
                            <div class="mb-4">
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-spinner text-blue-600 mr-3 animate-spin"></i>
                                    <span class="text-gray-700">Gerando documentos...</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2.5">
                                    <div id="bulkProgressBar" class="bg-blue-600 h-2.5 rounded-full" style="width: 0%"></div>
                                </div>
                            </div>
                            <div id="bulkGenerationDetails" class="text-sm text-gray-600 mb-4">
                                Total de documentos: <span id="totalDocuments">0</span><br>
                                Documentos gerados: <span id="generatedDocuments">0</span><br>
                                Documentos com erro: <span id="errorDocuments">0</span>
                            </div>
                            <div class="flex justify-end space-x-4">
                                <button id="cancelBulkGeneration" class="btn btn-outline">Cancelar</button>
                                <button id="downloadBulkDocuments" class="btn btn-primary hidden">
                                    <i class="fas fa-download mr-2"></i>
                                    Baixar Documentos
                                </button>
                            </div>
                        </div>
                    </div>

                    <script>
                        // Existing sidebar and form scripts...

                        // Bulk Document Generation Scripts
                        const bulkImportMethod = document.getElementById('bulkImportMethod');
                        const csvImportSection = document.getElementById('csvImportSection');
                        const manualSelectionSection = document.getElementById('manualSelectionSection');
                        const csvUpload = document.getElementById('csvUpload');
                        const csvPreview = document.getElementById('csvPreview');
                        const clearCsvUpload = document.getElementById('clearCsvUpload');
                        const bulkGenerationModal = document.getElementById('bulkGenerationModal');
                        const generateBulkDocuments = document.getElementById('generateBulkDocuments');
                        const cancelBulkGeneration = document.getElementById('cancelBulkGeneration');
                        const downloadBulkDocuments = document.getElementById('downloadBulkDocuments');
                        const bulkProgressBar = document.getElementById('bulkProgressBar');
                        const totalDocumentsSpan = document.getElementById('totalDocuments');
                        const generatedDocumentsSpan = document.getElementById('generatedDocuments');
                        const errorDocumentsSpan = document.getElementById('errorDocuments');

                        // Import Method Toggle
                        bulkImportMethod.addEventListener('change', (e) => {
                            switch(e.target.value) {
                                case 'csv':
                                    csvImportSection.classList.remove('hidden');
                                    manualSelectionSection.classList.add('hidden');
                                    break;
                                case 'manual':
                                    csvImportSection.classList.add('hidden');
                                    manualSelectionSection.classList.remove('hidden');
                                    break;
                                case 'excel':
                                    alert('Importação de Excel em desenvolvimento.');
                                    break;
                            }
                        });

                        // CSV Upload
                        csvUpload.addEventListener('change', (e) => {
                            const file = e.target.files[0];
                            if (file) {
                                csvPreview.classList.remove('hidden');
                                // Here you would typically parse the CSV and populate the preview table
                                // For demonstration, we'll just show a mock preview
                                const previewTable = document.getElementById('csvPreviewTable').querySelector('tbody');
                                previewTable.innerHTML = `
                                    <tr>
                                        <td>João Silva Santos</td>
                                        <td>ADS</td>
                                        <td>2023001</td>
                                        <td>Declaração</td>
                                    </tr>
                                    <tr>
                                        <td>Maria Aparecida</td>
                                        <td>Enfermagem</td>
                                        <td>2023002</td>
                                        <td>Histórico</td>
                                    </tr>
                                `;
                            }
                        });

                        // Clear CSV Upload
                        clearCsvUpload.addEventListener('click', () => {
                            csvUpload.value = '';
                            csvPreview.classList.add('hidden');
                        });

                        // Bulk Document Generation
                        generateBulkDocuments.addEventListener('click', () => {
                            bulkGenerationModal.style.display = 'flex';
                            
                            // Simulate bulk document generation
                            const totalDocs = 25;
                            let generated = 0;
                            let errors = 0;

                            totalDocumentsSpan.textContent = totalDocs;

                            const simulateGeneration = setInterval(() => {
                                generated++;
                                const progress = (generated / totalDocs) * 100;
                                bulkProgressBar.style.width = `${progress}%`;
                                generatedDocumentsSpan.textContent = generated;

                                if (generated >= totalDocs) {
                                    clearInterval(simulateGeneration);
                                    downloadBulkDocuments.classList.remove('hidden');
                                }
                            }, 200);
                        });

                        // Cancel Bulk Generation
                        cancelBulkGeneration.addEventListener('click', () => {
                            bulkGenerationModal.style.display = 'none';
                        });

                        // Download Bulk Documents
                        downloadBulkDocuments.addEventListener('click', () => {
                            alert('Baixando documentos gerados em massa');
                        });

                        // Select All Students in Manual Selection
                        const selectAllStudents = document.getElementById('selectAllStudents');
                        const studentCheckboxes = document.querySelectorAll('.student-checkbox');

                        selectAllStudents.addEventListener('change', (e) => {
                            studentCheckboxes.forEach(checkbox => {
                                checkbox.checked = e.target.checked;
                            });
                        });
                    </script>