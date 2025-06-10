<?php
/**
 * ================================================================
 *                        FACIÊNCIA ERP
 * ================================================================
 * 
 * Sistema de Gestão Educacional
 * 
 * ARQUIVO: relatorios.php
 * VERSÃO: 2.4.0
 * DATA: 2024-01-15
 * DESENVOLVEDOR: Equipe Faciência
 * 
 * DESCRIÇÃO:
 * Sistema centralizado de relatórios do ERP educacional.
 * Permite visualização e análise de dados através de diferentes 
 * tipos de relatórios: desempenho, estatísticas, documentos,
 * chamados, alunos e polos.
 * 
 * FUNCIONALIDADES:
 * - Relatórios de desempenho acadêmico
 * - Estatísticas gerais do sistema
 * - Relatórios de documentos emitidos e solicitações
 * - Análise de chamados de suporte
 * - Relatórios de alunos e matrículas
 * - Relatórios por polo de ensino
 * - Exportação em Excel e PDF
 * - Filtros avançados e períodos personalizados
 * - Gráficos interativos com Chart.js
 * 
 * DEPENDÊNCIAS:
 * - Sistema de autenticação (Auth)
 * - Classe Database para conexão com MySQL
 * - Chart.js para visualização de dados
 * - Sistema de permissões por módulo
 * 
 * TABELAS UTILIZADAS:
 * - documentos_emitidos: Documentos já gerados
 * - solicitacoes_documentos: Solicitações pendentes
 * - alunos: Dados dos estudantes
 * - cursos: Informações dos cursos
 * - turmas: Dados das turmas
 * - polos: Polos de ensino
 * - chamados: Sistema de suporte
 * - usuarios: Dados dos usuários
 * 
 * ================================================================
 */

// ================================================================
//                    CONFIGURAÇÕES INICIAIS
// ================================================================

// Inicializa o sistema
require_once __DIR__ . '/includes/init.php';

// ================================================================
//                  VERIFICAÇÃO DE AUTENTICAÇÃO
// ================================================================

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

// ================================================================
//                   CONFIGURAÇÃO DE PERMISSÕES
// ================================================================

// Define o nível de acesso
$permissoes = [
    'nivel_acesso' => Auth::getUserType() === 'admin_master' ? 'total' :
                     (Auth::hasPermission('relatorios', 'editar') ? 'editar' :
                     (Auth::hasPermission('relatorios', 'criar') ? 'criar' : 'visualizar'))
];

// ================================================================
//                  PROCESSAMENTO DE PARÂMETROS
// ================================================================

// Obtém o tipo de relatório a ser exibido
$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : 'desempenho';

// Parâmetros de paginação
$pagina_atual = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$registros_por_pagina = isset($_GET['per_page']) ? max(10, min(100, (int)$_GET['per_page'])) : 25;
$offset = ($pagina_atual - 1) * $registros_por_pagina;

// Variável para armazenar mensagens de erro
$mensagens_erro = [];
if (isset($_SESSION['mensagem'])) {
    $mensagens_erro[] = $_SESSION['mensagem'];
    unset($_SESSION['mensagem']);
    unset($_SESSION['mensagem_tipo']);
}

// Define o título da página
$titulo_pagina = 'Relatórios - ' . ucfirst($tipo);





// ================================================================
//                     INTERFACE GRÁFICA
// ================================================================

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
                            </div>                            <div class="dropdown relative">
                                <button class="btn-success dropdown-toggle">
                                    <i class="fas fa-download mr-2"></i> Exportar <i class="fas fa-chevron-down ml-2"></i>
                                </button>
                                <div class="dropdown-menu hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10">
                                    <?php
                                    // Constrói os parâmetros de URL para exportação
                                    $params_exportacao = ['tipo' => $tipo];
                                    
                                    // Adiciona parâmetros gerais
                                    if (isset($_GET['curso_id'])) $params_exportacao['curso_id'] = $_GET['curso_id'];
                                    if (isset($_GET['polo_id'])) $params_exportacao['polo_id'] = $_GET['polo_id'];
                                    if (isset($_GET['turma_id'])) $params_exportacao['turma_id'] = $_GET['turma_id'];
                                    if (isset($_GET['periodo'])) $params_exportacao['periodo'] = $_GET['periodo'];
                                    if (isset($_GET['data_inicio'])) $params_exportacao['data_inicio'] = $_GET['data_inicio'];
                                    if (isset($_GET['data_fim'])) $params_exportacao['data_fim'] = $_GET['data_fim'];
                                    
                                    // Adiciona parâmetros específicos para documentos
                                    if ($tipo === 'documentos') {
                                        if (isset($_GET['tab'])) $params_exportacao['tab'] = $_GET['tab'];
                                        if (isset($_GET['tipo_documento_id'])) $params_exportacao['tipo_documento_id'] = $_GET['tipo_documento_id'];
                                        if (isset($_GET['status'])) $params_exportacao['status'] = $_GET['status'];
                                    }
                                    
                                    $url_base = 'exportar_relatorio.php?' . http_build_query($params_exportacao);
                                    ?>
                                    <a href="<?php echo $url_base; ?>&formato=excel" class="dropdown-item" target="_blank">
                                        <i class="fas fa-file-excel text-green-600"></i>
                                        <span>Excel</span>
                                    </a>
                                    <a href="<?php echo $url_base; ?>&formato=pdf" class="dropdown-item" target="_blank">
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
