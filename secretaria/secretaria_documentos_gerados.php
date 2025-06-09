
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faciência ERP - Documentos Gerados</title>
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
        
        .btn-danger {
            background-color: var(--danger);
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #DC2626;
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
                    <h1 class="text-2xl font-semibold text-gray-800 ml-4">Documentos Gerados</h1>
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
                <div class="bg-white shadow-md rounded-lg p-6">
                    <div class="flex justify-between items-center mb-6">
                        <div class="flex space-x-4">
                            <select id="courseFilter" class="form-select">
                                <option value="">Todos os Cursos</option>
                                <option value="ads">Análise e Desenvolvimento de Sistemas</option>
                                <option value="enfermagem">Enfermagem</option>
                                <option value="direito">Direito</option>
                            </select>
                            <select id="documentTypeFilter" class="form-select">
                                <option value="">Todos os Tipos</option>
                                <option value="declaracao">Declaração</option>
                                <option value="historico">Histórico Escolar</option>
                                <option value="atestado">Atestado</option>
                                <option value="certificado">Certificado</option>
                            </select>
                            <select id="statusFilter" class="form-select">
                                <option value="">Todos os Status</option>
                                <option value="solicitado">Solicitado</option>
                                <option value="processando">Processando</option>
                                <option value="pronto">Pronto</option>
                                <option value="entregue">Entregue</option>
                                <option value="cancelado">Cancelado</option>
                            </select>
                        </div>
                        <div class="flex space-x-4">
                            <div class="relative">
                                <input type="text" id="searchInput" placeholder="Pesquisar..." class="form-input pl-10">
                                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                            </div>
                            <button id="exportDocuments" class="btn btn-outline btn-sm">
                                <i class="fas fa-file-export mr-2"></i>
                                Exportar
                            </button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="data-table w-full">
                            <thead>
                                <tr>
                                    <th>
                                        <input type="checkbox" id="selectAllDocuments" class="form-checkbox">
                                    </th>
                                    <th>Documento</th>
                                    <th>Aluno</th>
                                    <th>Curso</th>
                                    <th>Data Geração</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody id="documentsTableBody">
                                <tr>
                                    <td><input type="checkbox" class="form-checkbox document-checkbox"></td>
                                    <td>Declaração</td>
                                    <td>João Silva Santos</td>
                                    <td>Análise e Desenvolvimento de Sistemas</td>
                                    <td>14/04/2025 10:30</td>
                                    <td>
                                        <span class="status-badge pronto">Pronto</span>
                                    </td>
                                    <td>
                                        <div class="flex space-x-2">
                                            <button class="text-blue-600 hover:text-blue-800 view-document" title="Visualizar">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="text-green-600 hover:text-green-800 download-document" title="Baixar">
                                                <i class="fas fa-download"></i>
                                            </button>
                                            <button class="text-red-600 hover:text-red-800 delete-document" title="Excluir">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><input type="checkbox" class="form-checkbox document-checkbox"></td>
                                    <td>Histórico Escolar</td>
                                    <td>Maria Aparecida Oliveira</td>
                                    <td>Enfermagem</td>
                                    <td>12/04/2025 15:45</td>
                                    <td>
                                        <span class="status-badge processando">Processando</span>
                                    </td>
                                    <td>
                                        <div class="flex space-x-2">
                                            <button class="text-blue-600 hover:text-blue-800 view-document" title="Visualizar">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="text-green-600 hover:text-green-800 download-document disabled:opacity-50 disabled:cursor-not-allowed" title="Baixar" disabled>
                                                <i class="fas fa-download"></i>
                                            </button>
                                            <button class="text-red-600 hover:text-red-800 cancel-document" title="Cancelar">
                                                <i class="fas fa-times-circle"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><input type="checkbox" class="form-checkbox document-checkbox"></td>
                                    <td>Atestado</td>
                                    <td>Pedro Henrique Souza</td>
                                    <td>Direito</td>
                                    <td>10/04/2025 09:15</td>
                                    <td>
                                        <span class="status-badge solicitado">Solicitado</span>
                                    </td>
                                    <td>
                                       <div class="flex space-x-2">
                                            <button class="text-blue-600 hover:text-blue-800 view-document" title="Visualizar">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="text-green-600 hover:text-green-800 download-document disabled:opacity-50 disabled:cursor-not-allowed" title="Baixar" disabled>
                                                <i class="fas fa-download"></i>
                                            </button>
                                            <button class="text-red-600 hover:text-red-800 cancel-document" title="Cancelar">
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
                            <span id="selectedDocumentsCount">0</span> documentos selecionados
                        </div>
                        <div class="flex space-x-2">
                            <button id="bulkActionBtn" class="btn btn-outline btn-sm" disabled>
                                Ações em Massa
                                <i class="fas fa-chevron-down ml-2"></i>
                            </button>
                            <div class="relative">
                                <button id="paginationBtn" class="btn btn-outline btn-sm">
                                    1-10 de 250
                                    <i class="fas fa-chevron-down ml-2"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modais -->
    <!-- Modal de Visualização de Documento -->
    <div id="documentViewModal" class="modal">
        <div class="modal-content w-3/4 max-w-4xl">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-semibold text-gray-800">Visualização de Documento</h3>
                <button id="closeDocumentView" class="text-gray-600 hover:text-gray-800">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            <div class="bg-gray-100 rounded-lg p-6">
                <iframe id="documentPreviewFrame" class="w-full h-[600px] bg-white" src=""></iframe>
            </div>
            <div class="mt-4 flex justify-end space-x-4">
                <button id="downloadDocumentBtn" class="btn btn-primary">
                    <i class="fas fa-download mr-2"></i>
                    Baixar Documento
                </button>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmação de Exclusão -->
    <div id="deleteConfirmModal" class="modal">
        <div class="modal-content">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Confirmar Exclusão</h3>
            <p class="text-gray-600 mb-6">Tem certeza que deseja excluir o(s) documento(s) selecionado(s)?</p>
            <div class="flex justify-end space-x-4">
                <button id="cancelDelete" class="btn btn-outline">Cancelar</button>
                <button id="confirmDelete" class="btn btn-danger">
                    <i class="fas fa-trash mr-2"></i>
                    Excluir
                </button>
            </div>
        </div>
    </div>

    <!-- Dropdown de Ações em Massa -->
    <div id="bulkActionsDropdown" class="absolute hidden bg-white shadow-lg rounded-lg border border-gray-200 z-50 w-48">
        <ul class="py-1">
            <li>
                <button class="w-full text-left px-4 py-2 hover:bg-gray-100" id="downloadSelectedBtn">
                    <i class="fas fa-download mr-2 text-green-600"></i>
                    Baixar Selecionados
                </button>
            </li>
            <li>
                <button class="w-full text-left px-4 py-2 hover:bg-gray-100" id="deleteSelectedBtn">
                    <i class="fas fa-trash mr-2 text-red-600"></i>
                    Excluir Selecionados
                </button>
            </li>
        </ul>
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

        // Document Selection
        const selectAllDocuments = document.getElementById('selectAllDocuments');
        const documentCheckboxes = document.querySelectorAll('.document-checkbox');
        const selectedDocumentsCount = document.getElementById('selectedDocumentsCount');
        const bulkActionBtn = document.getElementById('bulkActionBtn');
        const bulkActionsDropdown = document.getElementById('bulkActionsDropdown');

        // Select/Deselect All Documents
        selectAllDocuments.addEventListener('change', (e) => {
            documentCheckboxes.forEach(checkbox => {
                checkbox.checked = e.target.checked;
            });
            updateSelectedDocumentsCount();
        });

        // Update Selected Documents Count
        function updateSelectedDocumentsCount() {
            const selectedCount = Array.from(documentCheckboxes)
                .filter(checkbox => checkbox.checked).length;
            
            selectedDocumentsCount.textContent = selectedCount;
            bulkActionBtn.disabled = selectedCount === 0;
            selectAllDocuments.checked = selectedCount === documentCheckboxes.length;
        }

        // Add event listener to individual checkboxes
        documentCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateSelectedDocumentsCount);
        });

        // Bulk Actions Dropdown
        bulkActionBtn.addEventListener('click', (e) => {
            const rect = bulkActionBtn.getBoundingClientRect();
            bulkActionsDropdown.style.top = `${rect.bottom + 10}px`;
            bulkActionsDropdown.style.left = `${rect.right - bulkActionsDropdown.offsetWidth}px`;
            bulkActionsDropdown.classList.toggle('hidden');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!bulkActionBtn.contains(e.target) && !bulkActionsDropdown.contains(e.target)) {
                bulkActionsDropdown.classList.add('hidden');
            }
        });

        // Filtering
        const courseFilter = document.getElementById('courseFilter');
        const documentTypeFilter = document.getElementById('documentTypeFilter');
        const statusFilter = document.getElementById('statusFilter');
        const searchInput = document.getElementById('searchInput');

        function applyFilters() {
            const courseValue = courseFilter.value;
            const documentTypeValue = documentTypeFilter.value;
            const statusValue = statusFilter.value;
            const searchTerm = searchInput.value.toLowerCase();

            const rows = document.querySelectorAll('#documentsTableBody tr');
            
            rows.forEach(row => {
                const courseCell = row.children[3].textContent.toLowerCase();
                const documentTypeCell = row.children[1].textContent.toLowerCase();
                const statusCell = row.children[5].querySelector('.status-badge').textContent.toLowerCase();
                const studentCell = row.children[2].textContent.toLowerCase();

                const matchesCourse = !courseValue || courseCell.includes(courseValue);
                const matchesDocumentType = !documentTypeValue || documentTypeCell.includes(documentTypeValue);
                const matchesStatus = !statusValue || statusCell.includes(statusValue);
                const matchesSearch = !searchTerm || 
                    studentCell.includes(searchTerm) || 
                    documentTypeCell.includes(searchTerm);

                row.style.display = (matchesCourse && matchesDocumentType && matchesStatus && matchesSearch)
                    ? '' 
                    : 'none';
            });
        }

        // Add event listeners to filters
        courseFilter.addEventListener('change', applyFilters);
        documentTypeFilter.addEventListener('change', applyFilters);
        statusFilter.addEventListener('change', applyFilters);
        searchInput.addEventListener('input', applyFilters);

        // Document Actions
        const documentViewModal = document.getElementById('documentViewModal');
        const documentPreviewFrame = document.getElementById('documentPreviewFrame');
        const closeDocumentView = document.getElementById('closeDocumentView');
        const downloadDocumentBtn = document.getElementById('downloadDocumentBtn');
        const deleteConfirmModal = document.getElementById('deleteConfirmModal');
        const cancelDelete = document.getElementById('cancelDelete');
        const confirmDelete = document.getElementById('confirmDelete');

        // View Document
        document.querySelectorAll('.view-document').forEach(btn => {
            btn.addEventListener('click', () => {
                // In a real application, this would load the actual document
                documentPreviewFrame.src = '/api/placeholder/800/600';
                documentViewModal.style.display = 'flex';
            });
        });

        // Close Document View Modal
        closeDocumentView.addEventListener('click', () => {
            documentViewModal.style.display = 'none';
        });

        // Download Document
        document.querySelectorAll('.download-document').forEach(btn => {
            btn.addEventListener('click', () => {
                alert('Iniciando download do documento');
            });
        });

        // Bulk Download
        document.getElementById('downloadSelectedBtn').addEventListener('click', () => {
            const selectedDocs = Array.from(documentCheckboxes)
                .filter(checkbox => checkbox.checked);
            
            alert(`Baixando ${selectedDocs.length} documento(s)`);
            bulkActionsDropdown.classList.add('hidden');
        });

        // Delete Document
        document.querySelectorAll('.delete-document').forEach(btn => {
            btn.addEventListener('click', () => {
                deleteConfirmModal.style.display = 'flex';
            });
        });

        // Bulk Delete
        document.getElementById('deleteSelectedBtn').addEventListener('click', () => {
            const selectedDocs = Array.from(documentCheckboxes)
                .filter(checkbox => checkbox.checked);
            
            deleteConfirmModal.style.display = 'flex';
            bulkActionsDropdown.classList.add('hidden');
        });

        // Cancel Delete
        cancelDelete.addEventListener('click', () => {
            deleteConfirmModal.style.display = 'none';
        });

        // Confirm Delete
        confirmDelete.addEventListener('click', () => {
            alert('Documentos excluídos com sucesso');
            deleteConfirmModal.style.display = 'none';
        });

        // Export Documents
        document.getElementById('exportDocuments').addEventListener('click', () => {
            alert('Exportando documentos');
        });

        // Close modals when clicking outside
        [documentViewModal, deleteConfirmModal].forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>