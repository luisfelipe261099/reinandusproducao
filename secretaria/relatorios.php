<?php
// Inicializa o sistema
require_once __DIR__ . '/includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Instancia o banco de dados
$db = Database::getInstance();

// Verifica as permissões do usuário
if (!Auth::hasPermission('relatorios', 'visualizar')) {
    $_SESSION['mensagem'] = 'Você não tem permissão para acessar esta página.';
    $_SESSION['mensagem_tipo'] = 'erro';
    header('Location: index.php');
    exit;
}

// Define o nível de acesso
$permissoes = [
    'nivel_acesso' => Auth::getUserType() === 'admin_master' ? 'total' :
                     (Auth::hasPermission('relatorios', 'editar') ? 'editar' :
                     (Auth::hasPermission('relatorios', 'criar') ? 'criar' : 'visualizar'))
];

// Obtém o tipo de relatório a ser exibido
$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : 'desempenho';

// Variável para armazenar mensagens de erro
$mensagens_erro = [];
if (isset($_SESSION['mensagem'])) {
    $mensagens_erro[] = $_SESSION['mensagem'];
    unset($_SESSION['mensagem']);
    unset($_SESSION['mensagem_tipo']);
}

// Define o título da página
$titulo_pagina = 'Relatórios - ' . ucfirst($tipo);





// Inicia a saída HTML
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faciência ERP - <?php echo $titulo_pagina; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <?php include 'includes/header.php'; ?>

            <!-- Main -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
                <div class="container mx-auto">
                    <div class="flex justify-between items-center mb-6">
                        <h1 class="text-2xl font-bold text-gray-800"><?php echo $titulo_pagina; ?></h1>

                        <div class="flex space-x-2">
                            <div class="dropdown relative">
                                <button class="btn-primary dropdown-toggle">
                                    <i class="fas fa-chart-line mr-2"></i> Tipo de Relatório <i class="fas fa-chevron-down ml-2"></i>
                                </button>
                                <div class="dropdown-menu hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10">
                                    <a href="relatorios.php?tipo=desempenho" class="dropdown-item <?php echo $tipo === 'desempenho' ? 'active' : ''; ?>">
                                        <i class="fas fa-chart-line"></i>
                                        <span>Desempenho</span>
                                    </a>
                                    <a href="relatorios.php?tipo=estatisticas" class="dropdown-item <?php echo $tipo === 'estatisticas' ? 'active' : ''; ?>">
                                        <i class="fas fa-chart-pie"></i>
                                        <span>Estatísticas</span>
                                    </a>
                                    <a href="relatorios.php?tipo=documentos" class="dropdown-item <?php echo $tipo === 'documentos' ? 'active' : ''; ?>">
                                        <i class="fas fa-file-alt"></i>
                                        <span>Documentos</span>
                                    </a>
                                    <a href="relatorios.php?tipo=chamados" class="dropdown-item <?php echo $tipo === 'chamados' ? 'active' : ''; ?>">
                                        <i class="fas fa-ticket-alt"></i>
                                        <span>Chamados</span>
                                    </a>
                                    <a href="relatorios.php?tipo=alunos" class="dropdown-item <?php echo $tipo === 'alunos' ? 'active' : ''; ?>">
                                        <i class="fas fa-user-graduate"></i>
                                        <span>Alunos</span>
                                    </a>
                                    <a href="relatorios.php?tipo=polos" class="dropdown-item <?php echo $tipo === 'polos' ? 'active' : ''; ?>">
                                        <i class="fas fa-building"></i>
                                        <span>Polos</span>
                                    </a>
                                </div>
                            </div>

                            <div class="dropdown relative">
                                <button class="btn-success dropdown-toggle">
                                    <i class="fas fa-download mr-2"></i> Exportar <i class="fas fa-chevron-down ml-2"></i>
                                </button>
                                <div class="dropdown-menu hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10">
                                    <a href="exportar_relatorio.php?tipo=<?php echo $tipo; ?><?php echo isset($_GET['curso_id']) ? '&curso_id=' . $_GET['curso_id'] : ''; ?><?php echo isset($_GET['polo_id']) ? '&polo_id=' . $_GET['polo_id'] : ''; ?><?php echo isset($_GET['turma_id']) ? '&turma_id=' . $_GET['turma_id'] : ''; ?><?php echo isset($_GET['periodo']) ? '&periodo=' . $_GET['periodo'] : ''; ?><?php echo isset($_GET['data_inicio']) ? '&data_inicio=' . $_GET['data_inicio'] : ''; ?><?php echo isset($_GET['data_fim']) ? '&data_fim=' . $_GET['data_fim'] : ''; ?>&formato=excel" class="dropdown-item" target="_blank">
                                        <i class="fas fa-file-excel text-green-600"></i>
                                        <span>Excel</span>
                                    </a>
                                    <a href="exportar_relatorio.php?tipo=<?php echo $tipo; ?><?php echo isset($_GET['curso_id']) ? '&curso_id=' . $_GET['curso_id'] : ''; ?><?php echo isset($_GET['polo_id']) ? '&polo_id=' . $_GET['polo_id'] : ''; ?><?php echo isset($_GET['turma_id']) ? '&turma_id=' . $_GET['turma_id'] : ''; ?><?php echo isset($_GET['periodo']) ? '&periodo=' . $_GET['periodo'] : ''; ?><?php echo isset($_GET['data_inicio']) ? '&data_inicio=' . $_GET['data_inicio'] : ''; ?><?php echo isset($_GET['data_fim']) ? '&data_fim=' . $_GET['data_fim'] : ''; ?>&formato=pdf" class="dropdown-item" target="_blank">
                                        <i class="fas fa-file-pdf text-red-600"></i>
                                        <span>PDF</span>
                                    </a>
                                </div>
                            </div>

                            <button onclick="window.print()" class="btn-secondary">
                                <i class="fas fa-print mr-2"></i> Imprimir
                            </button>
                        </div>
                    </div>

                    <?php if (!empty($mensagens_erro)): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
                        <ul class="list-disc list-inside">
                            <?php foreach ($mensagens_erro as $erro): ?>
                            <li><?php echo $erro; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <?php
                    // Carrega a página de relatório específica
                    switch ($tipo) {
                        case 'estatisticas':
                            include 'views/relatorios/estatisticas.php';
                            break;
                        case 'documentos':
                            include 'views/relatorios/documentos.php';
                            break;
                        case 'financeiro':
                            include 'views/relatorios/financeiro.php';
                            break;
                        case 'chamados':
                            include 'views/relatorios/chamados.php';
                            break;
                        case 'alunos':
                            include 'views/relatorios/alunos.php';
                            break;
                        case 'polos':
                            include 'views/relatorios/polos.php';
                            break;
                        case 'desempenho':
                        default:
                            include 'views/relatorios/desempenho.php';
                            break;
                    }
                    ?>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="js/scripts.js"></script>
    <script>
        // Inicializa os dropdowns
        document.addEventListener('DOMContentLoaded', function() {
            const dropdownToggles = document.querySelectorAll('.dropdown-toggle');

            dropdownToggles.forEach(function(toggle) {
                toggle.addEventListener('click', function() {
                    const dropdownMenu = this.nextElementSibling;
                    dropdownMenu.classList.toggle('hidden');
                });
            });

            // Fecha os dropdowns quando clicar fora deles
            document.addEventListener('click', function(event) {
                if (!event.target.closest('.dropdown')) {
                    document.querySelectorAll('.dropdown-menu').forEach(function(menu) {
                        menu.classList.add('hidden');
                    });
                }
            });
        });
    </script>
</body>
</html>
