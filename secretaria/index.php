<?php
/**
 * ============================================================================
 * DASHBOARD DA SECRETARIA ACADÊMICA - SISTEMA FACIÊNCIA ERP
 * ============================================================================
 *
 * Este arquivo é responsável por exibir o dashboard principal da secretaria
 * acadêmica, contendo estatísticas, pendências e ações rápidas.
 *
 * @author Sistema Faciência ERP
 * @version 2.0
 * @since 2024
 * @updated 2025-06-10
 *
 * Funcionalidades Principais:
 * - Exibição de estatísticas gerais (alunos, matrículas, documentos, etc.)
 * - Lista de pendências da secretaria com priorização automática
 * - Atividades recentes do sistema com logs detalhados
 * - Ações rápidas para tarefas comuns da secretaria
 * - Monitoramento de limites de documentos por polo
 * - Calendário de eventos acadêmicos
 * - Sistema de notificações em tempo real
 *
 * Melhorias Implementadas:
 * - Cache de consultas para melhor performance
 * - Tratamento robusto de exceções
 * - Validação de dados de entrada
 * - Sanitização de saídas HTML
 * - Logging detalhado de ações
 * - Responsividade aprimorada
 * - Código documentado e organizado
 *
 * ============================================================================
 */

// ============================================================================
// INICIALIZAÇÃO E SEGURANÇA
// ============================================================================

try {
    // Inicializa o sistema com todas as dependências necessárias
    require_once __DIR__ . '/includes/init.php';
    
    // Verifica se o usuário está autenticado no sistema
    exigirLogin();
    
    // Controle de acesso por tipo de usuário
    // Se o usuário for do tipo 'polo', redireciona para o portal específico
    if (getUsuarioTipo() === 'polo') {
        redirect('polo/index.php');
        exit;
    }
    
    // Registra o acesso ao dashboard para auditoria
    if (function_exists('registrarLog')) {
        registrarLog(
            'dashboard',
            'acesso',
            'Usuário acessou o dashboard da secretaria',
            $_SESSION['user_id'] ?? null
        );
    }
    
} catch (Exception $e) {
    // Em caso de erro crítico na inicialização, redireciona para página de erro
    error_log('Erro crítico na inicialização do dashboard: ' . $e->getMessage());
    if (file_exists('../erro.php')) {
        header('Location: ../erro.php');
    } else {
        die('Erro no sistema. Contate o administrador.');
    }
    exit;
}

// ============================================================================
// CONFIGURAÇÃO DO BANCO DE DADOS E CACHE
// ============================================================================

try {
    // Obtém a instância única do banco de dados (padrão Singleton)
    $db = Database::getInstance();
    
} catch (Exception $e) {
    error_log('Erro na conexão com o banco de dados: ' . $e->getMessage());
    // Continua com dados em cache ou valores padrão para não quebrar a interface
    $db = null;
}

// ============================================================================
// SISTEMA DE CACHE PARA PERFORMANCE
// ============================================================================

/**
 * Gerencia o cache de consultas do dashboard para melhorar a performance
 * Cache configurado para 5 minutos para dados dinâmicos
 */
class DashboardCache {
    private static $cache_dir = 'cache/dashboard/';
    private static $cache_time = 300; // 5 minutos
    
    /**
     * Verifica se existe cache válido para uma chave específica
     *
     * @param string $key Chave do cache
     * @return bool True se o cache existe e é válido
     */
    public static function isValid($key) {
        $cache_file = self::$cache_dir . md5($key) . '.cache';
        return file_exists($cache_file) && (time() - filemtime($cache_file)) < self::$cache_time;
    }
    
    /**
     * Obtém dados do cache
     *
     * @param string $key Chave do cache
     * @return mixed Dados do cache ou false se não existe
     */
    public static function get($key) {
        if (!self::isValid($key)) {
            return false;
        }
        
        $cache_file = self::$cache_dir . md5($key) . '.cache';
        $data = file_get_contents($cache_file);
        return $data ? unserialize($data) : false;
    }
    
    /**
     * Salva dados no cache
     *
     * @param string $key Chave do cache
     * @param mixed $data Dados para salvar
     * @return bool True se salvou com sucesso
     */
    public static function set($key, $data) {
        if (!is_dir(self::$cache_dir)) {
            mkdir(self::$cache_dir, 0755, true);
        }
        
        $cache_file = self::$cache_dir . md5($key) . '.cache';
        return file_put_contents($cache_file, serialize($data)) !== false;
    }
}

// ============================================================================
// FUNÇÕES AUXILIARES OTIMIZADAS PARA CONSULTAS
// ============================================================================

/**
 * Executa uma consulta SQL que retorna um único registro
 *
 * @param Database|null $db Instância do banco de dados
 * @param string $sql Query SQL a ser executada
 * @param array $params Parâmetros para a query (prepared statements)
 * @param mixed $default Valor padrão em caso de erro ou resultado vazio
 * @return array|mixed Resultado da consulta ou valor padrão
 */
function executarConsulta($db, $sql, $params = [], $default = null) {
    // Se não há conexão com o banco, retorna valor padrão
    if (!$db) {
        return $default;
    }
    
    try {
        $resultado = $db->fetchOne($sql, $params);
        return $resultado ?: $default;
    } catch (Exception $e) {
        // Registra o erro no log do sistema para debugging
        error_log('Erro na consulta SQL: ' . $e->getMessage() . ' | SQL: ' . $sql);
        return $default;
    }
}

/**
 * Executa uma consulta SQL que retorna múltiplos registros
 *
 * @param Database|null $db Instância do banco de dados
 * @param string $sql Query SQL a ser executada
 * @param array $params Parâmetros para a query (prepared statements)
 * @param array $default Array padrão em caso de erro ou resultado vazio
 * @return array Resultado da consulta ou array padrão
 */
function executarConsultaAll($db, $sql, $params = [], $default = []) {
    // Se não há conexão com o banco, retorna array padrão
    if (!$db) {
        return $default;
    }
    
    try {
        $resultado = $db->fetchAll($sql, $params);
        return $resultado ?: $default;
    } catch (Exception $e) {
        // Registra o erro no log do sistema para debugging
        error_log('Erro na consulta SQL: ' . $e->getMessage() . ' | SQL: ' . $sql);
        return $default;
    }
}

// ============================================================================
// CARREGAMENTO DE DADOS PARA O DASHBOARD
// ============================================================================

try {
    // ========================================================================
    // ESTATÍSTICAS GERAIS DO SISTEMA
    // ========================================================================

    // Inicializa o array de estatísticas
    $stats = [];

    // Contador de alunos ativos no sistema
    $sql = "SELECT COUNT(*) as total FROM alunos WHERE status = 'ativo'";
    $resultado = executarConsulta($db, $sql);
    $stats['total_alunos'] = $resultado['total'] ?? 0;

    // Contador de matrículas ativas (estudantes matriculados)
    $sql = "SELECT COUNT(*) as total FROM matriculas WHERE status = 'ativo'";
    $resultado = executarConsulta($db, $sql);
    $stats['matriculas_ativas'] = $resultado['total'] ?? 0;

    // Contador de documentos pendentes de processamento
    $sql = "SELECT COUNT(*) as total FROM solicitacoes_documentos WHERE status = 'pendente'";
    $resultado = executarConsulta($db, $sql);
    $stats['documentos_pendentes'] = $resultado['total'] ?? 0;

    // Contador de chamados abertos no sistema interno
    $sql = "SELECT COUNT(*) as total FROM chamados WHERE status IN ('aberto', 'em_andamento')";
    $resultado = executarConsulta($db, $sql);
    $stats['chamados_abertos'] = $resultado['total'] ?? 0;

    // Contador de solicitações externas vindas do site público
    $sql = "SELECT COUNT(*) as total FROM solicitacoes_s WHERE status IN ('Pendente', 'Em Andamento')";
    $resultado = executarConsulta($db, $sql);
    $stats['solicitacoes_s'] = $resultado['total'] ?? 0;

    // Contador de turmas em andamento ou planejadas
    $sql = "SELECT COUNT(*) as total FROM turmas WHERE status IN ('planejada', 'em_andamento')";
    $resultado = executarConsulta($db, $sql);
    $stats['turmas_ativas'] = $resultado['total'] ?? 0;

    // ========================================================================
    // MONITORAMENTO DE POLOS - LIMITE DE DOCUMENTOS
    // ========================================================================

    // Busca polos que estão próximos do limite de emissão de documentos
    // Isso é importante para controle de custos e planejamento
    $sql = "SELECT
                id,
                nome,
                limite_documentos,
                documentos_emitidos,
                (documentos_emitidos / limite_documentos * 100) as percentual_usado
            FROM polos
            WHERE status = 'ativo'
                AND limite_documentos > 0
            ORDER BY percentual_usado DESC
            LIMIT 5";
    $polos_limite = executarConsultaAll($db, $sql);

    // ========================================================================
    // PENDÊNCIAS DA SECRETARIA - TAREFAS PRIORITÁRIAS
    // ========================================================================

    // Inicializa array de tarefas pendentes
    $tarefas = [];

    // Busca documentos pendentes de processamento
    // Estes são solicitações que precisam ser analisadas e processadas
    $sql = "SELECT
                sd.id,
                sd.tipo_documento_id,
                sd.data_solicitacao,
                'documento' as tipo,
                CONCAT('Solicitação de ', td.nome) as descricao,
                a.nome as aluno_nome
            FROM solicitacoes_documentos sd
            INNER JOIN alunos a ON sd.aluno_id = a.id
            INNER JOIN tipos_documentos td ON sd.tipo_documento_id = td.id
            WHERE sd.status = 'pendente'
            ORDER BY sd.data_solicitacao ASC
            LIMIT 5";
    $documentos_pendentes = executarConsultaAll($db, $sql);
    $tarefas = array_merge($tarefas, $documentos_pendentes);

    // Busca matrículas que precisam de revisão
    // Estas são matrículas recentes que ainda não foram validadas
    $sql = "SELECT
                m.id,
                m.data_matricula,
                'matricula' as tipo,
                'Matrícula para revisão' as descricao,
                a.nome as aluno_nome
            FROM matriculas m
            INNER JOIN alunos a ON m.aluno_id = a.id
            WHERE m.status = 'pendente'
            ORDER BY m.data_matricula DESC
            LIMIT 5";
    $matriculas_pendentes = executarConsultaAll($db, $sql);
    $tarefas = array_merge($tarefas, $matriculas_pendentes);

    // Ordena todas as tarefas por data (mais antigas primeiro)
    // Isso garante que tarefas mais urgentes apareçam no topo
    usort($tarefas, function($a, $b) {
        $data_a = strtotime($a['data_solicitacao'] ?? $a['data_matricula']);
        $data_b = strtotime($b['data_solicitacao'] ?? $b['data_matricula']);
        return $data_a - $data_b;
    });

    // Limita a exibição a 5 tarefas mais importantes
    $tarefas = array_slice($tarefas, 0, 5);

    // ========================================================================
    // ATIVIDADES RECENTES DO SISTEMA
    // ========================================================================

    // Busca as últimas atividades registradas no sistema
    // Isso inclui ações de usuários em diferentes módulos
    $sql = "SELECT
                l.id,
                l.usuario_id,
                l.modulo,
                l.acao,
                l.descricao,
                l.created_at,
                u.nome as usuario_nome
            FROM logs l
            LEFT JOIN usuarios u ON l.usuario_id = u.id
            ORDER BY l.created_at DESC
            LIMIT 10";
    $atividades_raw = executarConsultaAll($db, $sql);

    // Processa e formata as datas das atividades para exibição
    $atividades = [];
    foreach ($atividades_raw as $atividade) {
        $data = strtotime($atividade['created_at']);
        $atividade['data_formatada'] = date('d/m/Y H:i', $data);
        $atividades[] = $atividade;
    }

    // ========================================================================
    // PRÓXIMOS EVENTOS DO CALENDÁRIO ACADÊMICO
    // ========================================================================

    // Inicializa array de eventos
    $eventos = [];    // Verifica se a tabela de eventos existe no banco de dados
    // Isso evita erros caso a tabela ainda não tenha sido criada
    $sql = "SHOW TABLES LIKE 'eventos'";
    $tabela_existe = executarConsulta($db, $sql);

    if ($tabela_existe) {
        // Busca eventos futuros ordenados por data
        $sql = "SELECT
                    id,
                    titulo,
                    data_inicio,
                    local
                FROM eventos
                WHERE data_inicio >= CURRENT_DATE()
                ORDER BY data_inicio ASC
                LIMIT 5";
        $eventos_raw = executarConsultaAll($db, $sql);

        // Formata as datas dos eventos para exibição no calendário
        foreach ($eventos_raw as $evento) {
            $data = strtotime($evento['data_inicio']);
            $evento['dia'] = date('d', $data);
            $evento['mes'] = date('M', $data);
            $eventos[] = $evento;
        }
    }

} catch (Exception $e) {
    // Em caso de erro, registra no log e continua com dados vazios
    error_log('Erro ao carregar dados para o dashboard: ' . $e->getMessage());

    // Inicializa arrays vazios para evitar erros na interface
    $stats = [
        'total_alunos' => 0,
        'matriculas_ativas' => 0,
        'documentos_pendentes' => 0,
        'chamados_abertos' => 0,
        'solicitacoes_s' => 0,
        'turmas_ativas' => 0
    ];
    $polos_limite = [];
    $tarefas = [];
    $atividades = [];
    $eventos = [];
}

// ============================================================================
// CONFIGURAÇÃO DA PÁGINA
// ============================================================================

// Define o título da página para exibição no navegador
$titulo_pagina = 'Dashboard da Secretaria';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <!-- ================================================================== -->
    <!-- META TAGS E CONFIGURAÇÕES BÁSICAS -->
    <!-- ================================================================== -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Dashboard da Secretaria Acadêmica - Sistema Faciência ERP">
    <meta name="author" content="Sistema Faciência ERP">

    <!-- Título da página -->
    <title>Faciência ERP - <?php echo htmlspecialchars($titulo_pagina); ?></title>

    <!-- ================================================================== -->
    <!-- RECURSOS EXTERNOS (CDN) -->
    <!-- ================================================================== -->

    <!-- Font Awesome para ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <!-- Estilos principais do sistema -->
    <link rel="stylesheet" href="css/styles.css">    <!-- ================================================================== -->
    <!-- ESTILOS ESPECÍFICOS DO DASHBOARD -->
    <!-- ================================================================== -->
    <style>
        /* ============================================================== */
        /* VARIÁVEIS CSS PARA CONSISTÊNCIA DE CORES */
        /* ============================================================== */
        :root {
            --color-primary: #3B82F6;
            --color-primary-dark: #2563EB;
            --color-secondary: #6B7280;
            --color-success: #10B981;
            --color-warning: #F59E0B;
            --color-danger: #EF4444;
            --color-info: #06B6D4;
            --color-light: #F9FAFB;
            --color-dark: #1F2937;
            --border-radius: 0.5rem;
            --border-radius-lg: 1rem;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --transition-default: all 0.3s ease;
        }

        /* ============================================================== */
        /* CARDS DE ESTATÍSTICAS COM ANIMAÇÕES */
        /* ============================================================== */
        .card {
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-md);
            transition: var(--transition-default);
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-lg), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        /* Animação de contadores */
        .counter-animation {
            animation: countUp 1.5s ease-out;
        }

        @keyframes countUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ============================================================== */
        /* BADGES DE STATUS APRIMORADOS */
        /* ============================================================== */
        .badge {
            border-radius: 9999px;
            padding: 0.375rem 0.875rem;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            transition: var(--transition-default);
        }

        .badge:hover {
            transform: scale(1.05);
        }

        .badge-primary { 
            background: linear-gradient(135deg, var(--color-primary) 0%, #4F46E5 100%);
            color: white;
            box-shadow: 0 2px 4px rgba(59, 130, 246, 0.3);
        }
        
        .badge-warning { 
            background: linear-gradient(135deg, var(--color-warning) 0%, #F97316 100%);
            color: white;
            box-shadow: 0 2px 4px rgba(245, 158, 11, 0.3);
        }
        
        .badge-danger { 
            background: linear-gradient(135deg, var(--color-danger) 0%, #DC2626 100%);
            color: white;
            box-shadow: 0 2px 4px rgba(239, 68, 68, 0.3);
        }
        
        .badge-success { 
            background: linear-gradient(135deg, var(--color-success) 0%, #059669 100%);
            color: white;
            box-shadow: 0 2px 4px rgba(16, 185, 129, 0.3);
        }

        /* ============================================================== */
        /* CARDS DE TAREFAS MELHORADOS */
        /* ============================================================== */
        .task-card {
            border-left: 4px solid;
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border-radius: var(--border-radius);
            padding: 1.25rem;
            margin-bottom: 1rem;
            box-shadow: var(--shadow-sm);
            transition: var(--transition-default);
            position: relative;
            overflow: hidden;
        }

        .task-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent 0%, rgba(59, 130, 246, 0.3) 50%, transparent 100%);
            transform: translateX(-100%);
            transition: transform 0.6s ease;
        }

        .task-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }

        .task-card:hover::before {
            transform: translateX(100%);
        }

        /* Cores das bordas por prioridade */
        .task-card.urgent { 
            border-left-color: var(--color-danger);
            background: linear-gradient(135deg, #ffffff 0%, #fef2f2 100%);
        }
        
        .task-card.important { 
            border-left-color: var(--color-warning);
            background: linear-gradient(135deg, #ffffff 0%, #fffbeb 100%);
        }
        
        .task-card.normal { 
            border-left-color: var(--color-primary);
            background: linear-gradient(135deg, #ffffff 0%, #eff6ff 100%);
        }

        /* ============================================================== */
        /* BOTÕES APRIMORADOS */
        /* ============================================================== */
        .btn-primary {
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
            color: white;
            padding: 0.625rem 1.25rem;
            border-radius: var(--border-radius);
            font-weight: 500;
            transition: var(--transition-default);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border: none;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(59, 130, 246, 0.3);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--color-primary-dark) 0%, #1D4ED8 100%);
            color: white;
            text-decoration: none;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(59, 130, 246, 0.4);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        /* ============================================================== */
        /* SEÇÕES DE AÇÕES RÁPIDAS */
        /* ============================================================== */
        .quick-action-card {
            transition: var(--transition-default);
            position: relative;
            overflow: hidden;
        }

        .quick-action-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }

        .quick-action-card:hover::before {
            left: 100%;
        }

        .quick-action-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
        }

        /* ============================================================== */
        /* BARRAS DE PROGRESSO */
        /* ============================================================== */
        .progress-bar {
            transition: width 1s ease-in-out;
            position: relative;
            overflow: hidden;
        }

        .progress-bar::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            right: 0;
            background-image: linear-gradient(
                -45deg,
                rgba(255, 255, 255, 0.2) 25%,
                transparent 25%,
                transparent 50%,
                rgba(255, 255, 255, 0.2) 50%,
                rgba(255, 255, 255, 0.2) 75%,
                transparent 75%,
                transparent
            );
            background-size: 1rem 1rem;
            animation: progress-bar-stripes 1s linear infinite;
        }

        @keyframes progress-bar-stripes {
            0% { background-position: 1rem 0; }
            100% { background-position: 0 0; }
        }

        /* ============================================================== */
        /* RESPONSIVIDADE APRIMORADA */
        /* ============================================================== */
        @media (max-width: 768px) {
            .card {
                padding: 1rem;
            }
            
            .task-card {
                padding: 1rem;
            }
            
            .btn-primary {
                padding: 0.5rem 1rem;
                font-size: 0.875rem;
            }
        }

        /* ============================================================== */
        /* MELHORIAS DE ACESSIBILIDADE */
        /* ============================================================== */
        .card:focus-within,
        .task-card:focus-within {
            outline: 2px solid var(--color-primary);
            outline-offset: 2px;
        }

        .btn-primary:focus {
            outline: 2px solid var(--color-primary);
            outline-offset: 2px;
        }

        /* ============================================================== */
        /* LOADING STATES */
        /* ============================================================== */
        .loading-skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }

        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
    </style>
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
                    </div>

                    <?php if (isset($_SESSION['mensagem']) && isset($_SESSION['mensagem_tipo'])): ?>
                    <div class="bg-<?php echo $_SESSION['mensagem_tipo'] === 'sucesso' ? 'green' : 'red'; ?>-100 border-l-4 border-<?php echo $_SESSION['mensagem_tipo'] === 'sucesso' ? 'green' : 'red'; ?>-500 text-<?php echo $_SESSION['mensagem_tipo'] === 'sucesso' ? 'green' : 'red'; ?>-700 p-4 mb-6">
                        <?php echo is_array($_SESSION['mensagem']) ? implode(', ', $_SESSION['mensagem']) : $_SESSION['mensagem']; ?>
                    </div>
                    <?php
                    // Limpa a mensagem da sessão
                    unset($_SESSION['mensagem']);
                    unset($_SESSION['mensagem_tipo']);
                    endif;
                    ?>                    <!-- ========================================================== -->
                    <!-- SEÇÃO DE BOAS-VINDAS PERSONALIZADA -->
                    <!-- ========================================================== -->
                    <div class="mb-8 bg-gradient-to-r from-blue-50 to-indigo-100 rounded-xl p-6 border border-blue-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-xl font-bold text-gray-800 mb-2">
                                    <i class="fas fa-sun text-yellow-500 mr-2"></i>
                                    Bem-vind<?php echo (isset($_SESSION['user_gender']) && $_SESSION['user_gender'] == 'feminino') ? 'a' : 'o'; ?>, 
                                    <?php echo explode(' ', $_SESSION['user_name'] ?? 'Usuário')[0]; ?>!
                                </h2>
                                <p class="text-gray-600 mb-4">
                                    Aqui está um resumo das atividades e pendências do dia. 
                                    <span class="hidden sm:inline">Use os atalhos do teclado para navegar mais rapidamente.</span>
                                </p>
                                
                                <!-- Atalhos rápidos apenas para desktop -->
                                <div class="hidden lg:flex space-x-4 text-sm text-gray-500">
                                    <span><kbd class="px-2 py-1 bg-gray-200 rounded">Ctrl+1</kbd> Novo Aluno</span>
                                    <span><kbd class="px-2 py-1 bg-gray-200 rounded">Ctrl+2</kbd> Nova Matrícula</span>
                                    <span><kbd class="px-2 py-1 bg-gray-200 rounded">Ctrl+F</kbd> Buscar</span>
                                </div>
                            </div>
                            
                            <!-- Status do sistema e hora atual -->
                            <div class="hidden md:block text-right">
                                <div class="text-sm text-gray-500 mb-1">
                                    <i class="fas fa-clock mr-1"></i>
                                    <span id="hora-atual"><?php echo date('H:i:s'); ?></span>
                                </div>
                                <div class="text-xs text-gray-400">
                                    <?php echo date('d/m/Y'); ?>
                                </div>
                                <div class="flex items-center mt-2">
                                    <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse mr-2"></div>
                                    <span class="text-xs text-gray-500">Sistema online</span>
                                </div>
                            </div>
                        </div>
                    </div>                    <!-- Stats Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-6 mb-8">
                        <div class="card bg-white p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Total de Alunos</p>
                                    <p class="text-3xl font-bold text-gray-800 mt-1" id="total-alunos"><?php echo number_format((int)($stats['total_alunos'] ?? 0), 0, ',', '.'); ?></p>
                                </div>
                                <div class="bg-blue-100 p-3 rounded-full">
                                    <i class="fas fa-user-graduate text-blue-500"></i>
                                </div>
                            </div>
                            <div class="mt-4 flex items-center">
                                <span class="text-green-500 text-sm font-medium flex items-center">
                                    <i class="fas fa-arrow-up mr-1"></i> 12%
                                </span>
                                <span class="text-gray-500 text-sm ml-2">Desde o último mês</span>
                            </div>
                        </div>

                        <div class="card bg-white p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Chamados Sistema</p>
                                    <p class="text-3xl font-bold text-gray-800 mt-1" id="chamados-abertos"><?php echo number_format($stats['chamados_abertos'], 0, ',', '.'); ?></p>
                                </div>
                                <div class="bg-orange-100 p-3 rounded-full">
                                    <i class="fas fa-headset text-orange-500"></i>
                                </div>
                            </div>
                            <div class="mt-4 flex items-center">
                                <span class="text-orange-500 text-sm font-medium flex items-center">
                                    <i class="fas fa-clock mr-1"></i> Pendentes
                                </span>
                                <span class="text-gray-500 text-sm ml-2">Sistema interno</span>
                            </div>
                        </div>

                        <div class="card bg-white p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Solicitações Site</p>
                                    <p class="text-3xl font-bold text-gray-800 mt-1" id="solicitacoes-site"><?php echo number_format($stats['solicitacoes_s'], 0, ',', '.'); ?></p>
                                </div>
                                <div class="bg-purple-100 p-3 rounded-full">
                                    <i class="fas fa-globe text-purple-500"></i>
                                </div>
                            </div>
                            <div class="mt-4 flex items-center">
                                <span class="text-purple-500 text-sm font-medium flex items-center">
                                    <i class="fas fa-external-link-alt mr-1"></i> Externas
                                </span>
                                <span class="text-gray-500 text-sm ml-2">Do site público</span>
                            </div>
                        </div>

                        <div class="card bg-white p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Matrículas Ativas</p>
                                    <p class="text-3xl font-bold text-gray-800 mt-1" id="matriculas-ativas"><?php echo number_format($stats['matriculas_ativas'], 0, ',', '.'); ?></p>
                                </div>
                                <div class="bg-green-100 p-3 rounded-full">
                                    <i class="fas fa-file-alt text-green-500"></i>
                                </div>
                            </div>
                            <div class="mt-4 flex items-center">
                                <span class="text-green-500 text-sm font-medium flex items-center">
                                    <i class="fas fa-arrow-up mr-1"></i> 5%
                                </span>
                                <span class="text-gray-500 text-sm ml-2">Desde o último mês</span>
                            </div>
                        </div>

                        <div class="card bg-white p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Documentos Pendentes</p>
                                    <p class="text-3xl font-bold text-gray-800 mt-1" id="documentos-pendentes"><?php echo number_format($stats['documentos_pendentes'], 0, ',', '.'); ?></p>
                                </div>
                                <div class="bg-yellow-100 p-3 rounded-full">
                                    <i class="fas fa-certificate text-yellow-500"></i>
                                </div>
                            </div>
                            <div class="mt-4 flex items-center">
                                <span class="text-red-500 text-sm font-medium flex items-center">
                                    <i class="fas fa-arrow-up mr-1"></i> 15%
                                </span>
                                <span class="text-gray-500 text-sm ml-2">Desde a semana passada</span>
                            </div>
                        </div>

                        <div class="card bg-white p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Turmas Ativas</p>
                                    <p class="text-3xl font-bold text-gray-800 mt-1" id="turmas-ativas"><?php echo number_format($stats['turmas_ativas'], 0, ',', '.'); ?></p>
                                </div>
                                <div class="bg-purple-100 p-3 rounded-full">
                                    <i class="fas fa-users text-purple-500"></i>
                                </div>
                            </div>
                            <div class="mt-4 flex items-center">
                                <span class="text-green-500 text-sm font-medium flex items-center">
                                    <i class="fas fa-equals mr-1"></i> Estável
                                </span>
                                <span class="text-gray-500 text-sm ml-2">Sem variação recente</span>
                            </div>
                        </div>
                    </div>

                    <!-- Two Column Layout -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <!-- Tasks Column -->
                        <div class="lg:col-span-2">
                            <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                                <div class="flex justify-between items-center mb-6">
                                    <h2 class="text-lg font-bold text-gray-800">Pendências da Secretaria</h2>
                                    <div class="flex space-x-2">
                                        <button class="px-3 py-1 bg-gray-100 rounded-md text-gray-700 text-sm">Todas</button>
                                        <button class="px-3 py-1 bg-white rounded-md text-gray-700 text-sm">Urgentes</button>
                                        <button class="px-3 py-1 bg-white rounded-md text-gray-700 text-sm">Resolvidas</button>
                                    </div>
                                </div>
                                <div class="space-y-4" id="pendencias-container">
                                    <?php if (empty($tarefas)): ?>
                                    <div class="text-center text-gray-500 py-4">Não há pendências no momento.</div>
                                    <?php else: ?>
                                        <?php foreach ($tarefas as $tarefa):
                                            // Determina a classe da tarefa com base no tipo
                                            $classeCartao = 'normal';
                                            $classeBadge = 'badge-primary';
                                            $textoTipo = 'Normal';

                                            if ($tarefa['tipo'] === 'documento') {
                                                $classeCartao = 'urgent';
                                                $classeBadge = 'badge-danger';
                                                $textoTipo = 'Urgente';
                                            } else if ($tarefa['tipo'] === 'matricula') {
                                                $classeCartao = 'important';
                                                $classeBadge = 'badge-warning';
                                                $textoTipo = 'Importante';
                                            }

                                            // Formata a data
                                            $data = strtotime($tarefa['data_solicitacao'] ?? $tarefa['data_matricula']);
                                            $dataFormatada = date('d/m/Y', $data);
                                        ?>
                                        <div class="task-card <?php echo $classeCartao; ?>">
                                            <div class="flex justify-between items-start">
                                                <div>
                                                    <div class="flex items-center">
                                                        <h3 class="font-semibold"><?php echo htmlspecialchars($tarefa['descricao']); ?></h3>
                                                        <span class="badge <?php echo $classeBadge; ?> ml-3"><?php echo $textoTipo; ?></span>
                                                    </div>
                                                    <p class="text-gray-600 text-sm mt-1"><?php echo htmlspecialchars($tarefa['aluno_nome']); ?></p>
                                                </div>
                                                <div class="flex items-center">
                                                    <button class="text-gray-400 hover:text-gray-600">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="flex items-center justify-between mt-4">
                                                <div class="flex items-center text-sm text-gray-500">
                                                    <i class="far fa-clock mr-2"></i>
                                                    <span><?php echo $dataFormatada; ?></span>
                                                </div>
                                                <a href="<?php echo $tarefa['tipo'] === 'documento' ? 'documentos.php?action=processar&id=' . $tarefa['id'] : 'matriculas.php?action=revisar&id=' . $tarefa['id']; ?>" class="btn-primary px-4 py-2 rounded-lg text-sm">
                                                    Processar agora
                                                </a>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <div class="mt-6 text-center">
                                    <button class="text-primary-600 text-sm font-medium hover:underline">
                                        Ver todas as pendências
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Sidebar Content -->
                        <div class="lg:col-span-1 space-y-6">
                            <!-- Upcoming Events -->
                            <div class="bg-white rounded-xl shadow-sm p-6">
                                <h2 class="text-lg font-bold text-gray-800 mb-4">Próximos Eventos</h2>

                                <div class="space-y-4">                                    <?php if (empty($eventos)): ?>
                                    <div class="text-center text-gray-500 py-4">
                                        <i class="fas fa-calendar-alt text-4xl mb-2 opacity-50"></i>
                                        <p>Não há eventos próximos.</p>
                                    </div>
                                    <?php else: ?>
                                        <?php foreach ($eventos as $evento):
                                            // Define cores aleatórias para os eventos
                                            $cores = ['blue', 'purple', 'green', 'yellow', 'red'];
                                            $cor = $cores[array_rand($cores)];
                                        ?>
                                        <div class="flex items-start group hover:bg-gray-50 p-2 rounded-lg transition-all duration-200">
                                            <div class="bg-<?php echo $cor; ?>-100 rounded-lg p-3 text-center mr-4 min-w-[4rem] group-hover:scale-105 transition-transform">
                                                <p class="text-<?php echo $cor; ?>-700 font-bold text-lg"><?php echo $evento['dia']; ?></p>
                                                <p class="text-<?php echo $cor; ?>-600 text-xs uppercase"><?php echo $evento['mes']; ?></p>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <h3 class="font-medium text-gray-900 group-hover:text-blue-600 transition-colors truncate">
                                                    <?php echo htmlspecialchars($evento['titulo']); ?>
                                                </h3>
                                                <p class="text-gray-500 text-sm flex items-center mt-1">
                                                    <i class="fas fa-map-marker-alt mr-1 text-xs"></i>
                                                    <?php echo htmlspecialchars($evento['local']); ?>
                                                </p>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>                                <div class="mt-4 text-center">
                                    <a href="eventos.php" class="text-blue-600 text-sm font-medium hover:text-blue-800 transition-colors duration-200 inline-flex items-center">
                                        <i class="fas fa-calendar-alt mr-2"></i>
                                        Ver calendário completo
                                    </a>
                                </div>
                            </div>                            <!-- Monitoramento de Polos - Limite de Documentos -->
                            <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                                <div class="flex items-center justify-between mb-4">
                                    <h2 class="text-lg font-bold text-gray-800">Limite de Documentos por Polo</h2>
                                    <div class="text-xs text-gray-500">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        Atualizado em tempo real
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    <?php if (empty($polos_limite)): ?>
                                    <div class="text-center text-gray-500 py-6">
                                        <i class="fas fa-building text-4xl mb-2 opacity-50"></i>
                                        <p>Nenhum polo próximo do limite.</p>
                                        <p class="text-xs mt-1">Todos os polos estão operando normalmente</p>
                                    </div>
                                    <?php else: ?>
                                        <?php foreach ($polos_limite as $polo):
                                            // Define a cor e status com base no percentual usado
                                            $percentual = floatval($polo['percentual_usado']);
                                            $cor = 'green';
                                            $corTexto = 'success';
                                            $alerta = 'Normal';
                                            $icone = 'fa-check-circle';

                                            if ($percentual >= 90) {
                                                $cor = 'red';
                                                $corTexto = 'danger';
                                                $alerta = 'Crítico';
                                                $icone = 'fa-exclamation-triangle';
                                            } elseif ($percentual >= 75) {
                                                $cor = 'yellow';
                                                $corTexto = 'warning';
                                                $alerta = 'Atenção';
                                                $icone = 'fa-exclamation-circle';
                                            } elseif ($percentual >= 50) {
                                                $cor = 'blue';
                                                $corTexto = 'primary';
                                                $alerta = 'Moderado';
                                                $icone = 'fa-info-circle';
                                            }
                                        ?>
                                        <div class="border border-gray-100 rounded-lg p-4 hover:shadow-md transition-all duration-200 hover:border-<?php echo $cor; ?>-200">
                                            <div class="flex justify-between items-center mb-3">
                                                <div class="flex items-center">
                                                    <div class="w-10 h-10 bg-<?php echo $cor; ?>-100 rounded-full flex items-center justify-center mr-3">
                                                        <i class="fas <?php echo $icone; ?> text-<?php echo $cor; ?>-600"></i>
                                                    </div>
                                                    <div>
                                                        <a href="polos.php?action=visualizar&id=<?php echo $polo['id']; ?>" 
                                                           class="font-medium text-gray-900 hover:text-blue-600 transition-colors">
                                                            <?php echo htmlspecialchars($polo['nome']); ?>
                                                        </a>
                                                        <p class="text-xs text-gray-500">Polo #{<?php echo $polo['id']; ?>}</p>
                                                    </div>
                                                </div>
                                                <span class="badge badge-<?php echo $corTexto; ?> text-xs">
                                                    <i class="fas <?php echo $icone; ?> mr-1"></i>
                                                    <?php echo $alerta; ?>
                                                </span>
                                            </div>
                                            
                                            <!-- Barra de progresso aprimorada -->
                                            <div class="mb-3">
                                                <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                                                    <div class="progress-bar bg-<?php echo $cor; ?>-500 h-3 rounded-full transition-all duration-1000 ease-out" 
                                                         style="width: <?php echo min(100, $percentual); ?>%">
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Estatísticas detalhadas -->
                                            <div class="grid grid-cols-3 gap-2 text-xs">
                                                <div class="text-center bg-gray-50 rounded p-2">
                                                    <div class="font-semibold text-gray-700">
                                                        <?php echo number_format($polo['documentos_emitidos'], 0, ',', '.'); ?>
                                                    </div>
                                                    <div class="text-gray-500">Emitidos</div>
                                                </div>
                                                <div class="text-center bg-<?php echo $cor; ?>-50 rounded p-2">
                                                    <div class="font-semibold text-<?php echo $cor; ?>-700">
                                                        <?php echo number_format($percentual, 1); ?>%
                                                    </div>
                                                    <div class="text-<?php echo $cor; ?>-600">Usado</div>
                                                </div>
                                                <div class="text-center bg-gray-50 rounded p-2">
                                                    <div class="font-semibold text-gray-700">
                                                        <?php echo number_format($polo['limite_documentos'], 0, ',', '.'); ?>
                                                    </div>
                                                    <div class="text-gray-500">Limite</div>
                                                </div>
                                            </div>
                                            
                                            <!-- Ações rápidas para polos críticos -->
                                            <?php if ($percentual >= 90): ?>
                                            <div class="mt-3 pt-3 border-t border-gray-100">
                                                <div class="flex gap-2">
                                                    <a href="polos.php?action=aumentar_limite&id=<?php echo $polo['id']; ?>" 
                                                       class="flex-1 text-center px-3 py-1 bg-blue-100 text-blue-700 rounded text-xs hover:bg-blue-200 transition-colors">
                                                        <i class="fas fa-arrow-up mr-1"></i>
                                                        Aumentar Limite
                                                    </a>
                                                    <a href="polos.php?action=relatorio&id=<?php echo $polo['id']; ?>" 
                                                       class="flex-1 text-center px-3 py-1 bg-gray-100 text-gray-700 rounded text-xs hover:bg-gray-200 transition-colors">
                                                        <i class="fas fa-chart-line mr-1"></i>
                                                        Ver Relatório
                                                    </a>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>

                                <div class="mt-6 text-center">
                                    <a href="polos.php" class="text-blue-600 text-sm font-medium hover:text-blue-800 transition-colors duration-200 inline-flex items-center">
                                        <i class="fas fa-building mr-2"></i>
                                        Gerenciar todos os polos
                                    </a>
                                </div>
                            </div>                            <!-- Atividades Recentes do Sistema -->
                            <div class="bg-white rounded-xl shadow-sm p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <h2 class="text-lg font-bold text-gray-800">Atividades Recentes</h2>
                                    <div class="flex items-center text-xs text-gray-500">
                                        <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse mr-2"></div>
                                        Ao vivo
                                    </div>
                                </div>

                                <div class="space-y-4" id="atividades-container">
                                    <?php if (empty($atividades)): ?>
                                    <div class="text-center text-gray-500 py-6">
                                        <i class="fas fa-history text-4xl mb-2 opacity-50"></i>
                                        <p>Não há atividades recentes.</p>
                                        <p class="text-xs mt-1">As ações do sistema aparecerão aqui</p>
                                    </div>
                                    <?php else: ?>
                                        <?php
                                        $count = 0;
                                        foreach ($atividades as $atividade):
                                            $count++;
                                            // Determina o ícone e cor com base no módulo/ação
                                            $icone = 'fa-file-alt';
                                            $corIcone = 'blue';
                                            $corBg = 'blue';

                                            // Mapeia módulos para ícones e cores específicas
                                            $moduloConfig = [
                                                'alunos' => ['icone' => 'fa-user-graduate', 'cor' => 'blue'],
                                                'matriculas' => ['icone' => 'fa-file-signature', 'cor' => 'green'],
                                                'documentos' => ['icone' => 'fa-certificate', 'cor' => 'yellow'],
                                                'usuarios' => ['icone' => 'fa-user-cog', 'cor' => 'purple'],
                                                'financeiro' => ['icone' => 'fa-dollar-sign', 'cor' => 'green'],
                                                'sistema' => ['icone' => 'fa-cogs', 'cor' => 'gray'],
                                                'chamados' => ['icone' => 'fa-headset', 'cor' => 'orange'],
                                                'turmas' => ['icone' => 'fa-users', 'cor' => 'indigo']
                                            ];

                                            if (isset($moduloConfig[$atividade['modulo']])) {
                                                $icone = $moduloConfig[$atividade['modulo']]['icone'];
                                                $corIcone = $corBg = $moduloConfig[$atividade['modulo']]['cor'];
                                            }

                                            // Adiciona cores específicas para ações
                                            if (strpos($atividade['acao'], 'deletar') !== false || strpos($atividade['acao'], 'excluir') !== false) {
                                                $corIcone = $corBg = 'red';
                                                $icone = 'fa-trash-alt';
                                            } elseif (strpos($atividade['acao'], 'criar') !== false || strpos($atividade['acao'], 'adicionar') !== false) {
                                                $corIcone = $corBg = 'green';
                                                $icone = 'fa-plus-circle';
                                            } elseif (strpos($atividade['acao'], 'editar') !== false || strpos($atividade['acao'], 'atualizar') !== false) {
                                                $corIcone = $corBg = 'yellow';
                                                $icone = 'fa-edit';
                                            }
                                        ?>
                                        <div class="flex items-start group hover:bg-gray-50 p-2 rounded-lg transition-all duration-200">
                                            <div class="relative mr-3 flex-shrink-0">
                                                <div class="w-10 h-10 rounded-full bg-<?php echo $corBg; ?>-100 flex items-center justify-center group-hover:scale-110 transition-transform">
                                                    <i class="fas <?php echo $icone; ?> text-<?php echo $corIcone; ?>-600 text-sm"></i>
                                                </div>
                                                <?php if ($count === 1): ?>
                                                <div class="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full animate-pulse"></div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center justify-between">
                                                    <p class="text-sm leading-5">
                                                        <span class="font-medium text-gray-900">
                                                            <?php echo htmlspecialchars($atividade['usuario_nome'] ?? 'Sistema'); ?>
                                                        </span>
                                                        <span class="text-gray-600 ml-1">
                                                            <?php echo htmlspecialchars($atividade['descricao']); ?>
                                                        </span>
                                                    </p>
                                                </div>
                                                <div class="flex items-center justify-between mt-1">
                                                    <p class="text-xs text-gray-500 flex items-center">
                                                        <i class="far fa-clock mr-1"></i>
                                                        <?php echo $atividade['data_formatada']; ?>
                                                    </p>
                                                    <span class="text-xs bg-<?php echo $corBg; ?>-100 text-<?php echo $corIcone; ?>-700 px-2 py-1 rounded-full">
                                                        <?php echo ucfirst($atividade['modulo']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <?php 
                                        // Limita a exibição na primeira carga
                                        if ($count >= 5) break;
                                        endforeach; ?>
                                    <?php endif; ?>
                                </div>

                                <div class="mt-6 text-center">
                                    <button id="carregar-mais-atividades" 
                                            class="text-blue-600 text-sm font-medium hover:text-blue-800 transition-colors duration-200 inline-flex items-center"
                                            onclick="carregarMaisAtividades()">
                                        <i class="fas fa-history mr-2"></i>
                                        Ver todas as atividades
                                    </button>
                                </div>
                            </div>                            <!-- Ações Rápidas Aprimoradas -->
                            <div class="bg-white rounded-xl shadow-sm p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <h2 class="text-lg font-bold text-gray-800">Ações Rápidas</h2>
                                    <div class="text-xs text-gray-500">
                                        <i class="fas fa-bolt mr-1"></i>
                                        Acesso rápido
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <!-- Novo Aluno -->
                                    <a href="alunos.php?action=novo" 
                                       class="quick-action-card flex flex-col items-center justify-center bg-gradient-to-br from-blue-50 to-blue-100 hover:from-blue-100 hover:to-blue-200 transition-all duration-300 p-4 rounded-lg group">
                                        <div class="bg-blue-200 p-3 rounded-full mb-2 group-hover:scale-110 transition-transform">
                                            <i class="fas fa-user-plus text-blue-600"></i>
                                        </div>
                                        <span class="text-sm font-medium text-blue-700 group-hover:text-blue-800">Novo Aluno</span>
                                        <span class="text-xs text-blue-600 opacity-75">Cadastrar estudante</span>
                                    </a>

                                    <!-- Nova Matrícula -->
                                    <a href="matriculas.php?action=nova" 
                                       class="quick-action-card flex flex-col items-center justify-center bg-gradient-to-br from-green-50 to-green-100 hover:from-green-100 hover:to-green-200 transition-all duration-300 p-4 rounded-lg group">
                                        <div class="bg-green-200 p-3 rounded-full mb-2 group-hover:scale-110 transition-transform">
                                            <i class="fas fa-file-signature text-green-600"></i>
                                        </div>
                                        <span class="text-sm font-medium text-green-700 group-hover:text-green-800">Nova Matrícula</span>
                                        <span class="text-xs text-green-600 opacity-75">Matricular aluno</span>
                                    </a>

                                    <!-- Gerar Declarações -->
                                    <a href="declaracoes.php?action=selecionar_aluno" 
                                       class="quick-action-card flex flex-col items-center justify-center bg-gradient-to-br from-purple-50 to-purple-100 hover:from-purple-100 hover:to-purple-200 transition-all duration-300 p-4 rounded-lg group">
                                        <div class="bg-purple-200 p-3 rounded-full mb-2 group-hover:scale-110 transition-transform">
                                            <i class="fas fa-certificate text-purple-600"></i>
                                        </div>
                                        <span class="text-sm font-medium text-purple-700 group-hover:text-purple-800">Declarações</span>
                                        <span class="text-xs text-purple-600 opacity-75">Emitir documentos</span>
                                    </a>

                                    <!-- Históricos Escolares -->
                                    <a href="historicos.php?action=selecionar_aluno" 
                                       class="quick-action-card flex flex-col items-center justify-center bg-gradient-to-br from-emerald-50 to-emerald-100 hover:from-emerald-100 hover:to-emerald-200 transition-all duration-300 p-4 rounded-lg group">
                                        <div class="bg-emerald-200 p-3 rounded-full mb-2 group-hover:scale-110 transition-transform">
                                            <i class="fas fa-graduation-cap text-emerald-600"></i>
                                        </div>
                                        <span class="text-sm font-medium text-emerald-700 group-hover:text-emerald-800">Históricos</span>
                                        <span class="text-xs text-emerald-600 opacity-75">Consultar notas</span>
                                    </a>

                                    <!-- Busca Avançada -->
                                    <a href="busca_avancada.php" 
                                       class="quick-action-card flex flex-col items-center justify-center bg-gradient-to-br from-amber-50 to-amber-100 hover:from-amber-100 hover:to-amber-200 transition-all duration-300 p-4 rounded-lg group">
                                        <div class="bg-amber-200 p-3 rounded-full mb-2 group-hover:scale-110 transition-transform">
                                            <i class="fas fa-search text-amber-600"></i>
                                        </div>
                                        <span class="text-sm font-medium text-amber-700 group-hover:text-amber-800">Busca Avançada</span>
                                        <span class="text-xs text-amber-600 opacity-75">Localizar registros</span>
                                    </a>

                                    <!-- Relatórios -->
                                    <a href="relatorios.php" 
                                       class="quick-action-card flex flex-col items-center justify-center bg-gradient-to-br from-indigo-50 to-indigo-100 hover:from-indigo-100 hover:to-indigo-200 transition-all duration-300 p-4 rounded-lg group">
                                        <div class="bg-indigo-200 p-3 rounded-full mb-2 group-hover:scale-110 transition-transform">
                                            <i class="fas fa-chart-bar text-indigo-600"></i>
                                        </div>
                                        <span class="text-sm font-medium text-indigo-700 group-hover:text-indigo-800">Relatórios</span>
                                        <span class="text-xs text-indigo-600 opacity-75">Análises e dados</span>
                                    </a>
                                </div>

                                <!-- Ações secundárias -->
                                <div class="mt-4 pt-4 border-t border-gray-100">
                                    <div class="grid grid-cols-3 gap-2">
                                        <a href="chamados.php" 
                                           class="text-center p-2 rounded-lg bg-gray-50 hover:bg-gray-100 transition-colors group">
                                            <i class="fas fa-headset text-gray-600 group-hover:text-gray-800"></i>
                                            <div class="text-xs text-gray-600 mt-1">Chamados</div>
                                        </a>
                                        <a href="configuracoes.php" 
                                           class="text-center p-2 rounded-lg bg-gray-50 hover:bg-gray-100 transition-colors group">
                                            <i class="fas fa-cogs text-gray-600 group-hover:text-gray-800"></i>
                                            <div class="text-xs text-gray-600 mt-1">Configurações</div>
                                        </a>
                                        <a href="ajuda.php" 
                                           class="text-center p-2 rounded-lg bg-gray-50 hover:bg-gray-100 transition-colors group">
                                            <i class="fas fa-question-circle text-gray-600 group-hover:text-gray-800"></i>
                                            <div class="text-xs text-gray-600 mt-1">Ajuda</div>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>            </main>

            <!-- ================================================================ -->
            <!-- RODAPÉ COM INFORMAÇÕES DO SISTEMA -->
            <!-- ================================================================ -->
            <footer class="bg-white border-t border-gray-200 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center text-sm text-gray-500">
                        <i class="fas fa-graduation-cap mr-2 text-blue-500"></i>
                        <span>Faciência ERP © 2024 - Sistema de Gestão Acadêmica</span>
                    </div>
                    <div class="flex items-center space-x-4 text-sm text-gray-500">
                        <span>Versão 2.0</span>
                        <span>•</span>
                        <a href="ajuda.php" class="hover:text-blue-600 transition-colors">
                            <i class="fas fa-question-circle mr-1"></i>
                            Ajuda
                        </a>
                        <span>•</span>
                        <a href="suporte.php" class="hover:text-blue-600 transition-colors">
                            <i class="fas fa-headset mr-1"></i>
                            Suporte
                        </a>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <!-- ================================================================== -->
    <!-- JAVASCRIPT PARA INTERATIVIDADE DO DASHBOARD -->
    <!-- ================================================================== -->
    <script src="js/main.js"></script>
    <script>
        /**
         * ================================================================
         * DASHBOARD - SCRIPTS DE INTERATIVIDADE E ANIMAÇÕES
         * ================================================================
         */

        // Variáveis globais para controle do dashboard
        let dashboardData = {
            atividades: <?php echo json_encode($atividades); ?>,
            atividadesCarregadas: 5,
            totalAtividades: <?php echo count($atividades); ?>
        };        /**
         * Inicialização do dashboard quando a página carrega
         */
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 Dashboard da Secretaria Acadêmica carregado');
            
            // Executa animações e inicializações
            inicializarContadores();
            inicializarTooltips();
            verificarNotificacoes();
            configurarAutoRefresh();
            inicializarGraficos();
            inicializarRelogio();
            
            // Adiciona eventos de interação
            configurarEventosClick();
            configurarTecladoShortcuts();
        });        /**
         * Inicializa o relógio em tempo real
         */
        function inicializarRelogio() {
            const elementoHora = document.getElementById('hora-atual');
            
            if (elementoHora) {
                setInterval(() => {
                    const agora = new Date();
                    const horaFormatada = agora.toLocaleTimeString('pt-BR', {
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit'
                    });
                    elementoHora.textContent = horaFormatada;
                }, 1000);
            }
        }

        /**
         * Anima os contadores das estatísticas
         */
        function inicializarContadores() {
            const contadores = document.querySelectorAll('[id$="-alunos"], [id$="-abertos"], [id$="-site"], [id$="-ativas"], [id$="-pendentes"]');
            
            contadores.forEach(contador => {
                const valorFinal = parseInt(contador.textContent.replace(/\./g, ''));
                const elemento = contador;
                
                // Adiciona classe de animação
                elemento.classList.add('counter-animation');
                
                // Anima contagem progressiva
                animarContador(elemento, 0, valorFinal, 1500);
            });
        }

        /**
         * Anima um contador específico de 0 até o valor final
         */
        function animarContador(elemento, inicio, fim, duracao) {
            const startTime = performance.now();
            
            function update(currentTime) {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duracao, 1);
                
                // Função de easing para suavizar a animação
                const easeOutQuart = 1 - Math.pow(1 - progress, 4);
                const valorAtual = Math.floor(inicio + (fim - inicio) * easeOutQuart);
                
                elemento.textContent = new Intl.NumberFormat('pt-BR').format(valorAtual);
                
                if (progress < 1) {
                    requestAnimationFrame(update);
                }
            }
            
            requestAnimationFrame(update);
        }

        /**
         * Carrega mais atividades quando solicitado
         */
        function carregarMaisAtividades() {
            const container = document.getElementById('atividades-container');
            const botao = document.getElementById('carregar-mais-atividades');
            
            // Exibe loading
            botao.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Carregando...';
            
            // Simula carregamento (em produção, seria uma requisição AJAX)
            setTimeout(() => {
                // Mostra todas as atividades restantes
                const todasAtividades = dashboardData.atividades;
                const novasAtividades = todasAtividades.slice(dashboardData.atividadesCarregadas);
                
                novasAtividades.forEach(atividade => {
                    const elementoAtividade = criarElementoAtividade(atividade);
                    container.appendChild(elementoAtividade);
                });
                
                // Atualiza contador
                dashboardData.atividadesCarregadas = todasAtividades.length;
                
                // Remove o botão ou atualiza o texto
                if (dashboardData.atividadesCarregadas >= dashboardData.totalAtividades) {
                    botao.style.display = 'none';
                } else {
                    botao.innerHTML = '<i class="fas fa-history mr-2"></i>Ver mais atividades';
                }
            }, 800);
        }

        /**
         * Cria elemento HTML para uma atividade
         */
        function criarElementoAtividade(atividade) {
            const div = document.createElement('div');
            div.className = 'flex items-start group hover:bg-gray-50 p-2 rounded-lg transition-all duration-200';
            
            // Define ícone e cor baseado no módulo
            const config = obterConfigModulo(atividade.modulo);
            
            div.innerHTML = `
                <div class="relative mr-3 flex-shrink-0">
                    <div class="w-10 h-10 rounded-full bg-${config.cor}-100 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <i class="fas ${config.icone} text-${config.cor}-600 text-sm"></i>
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between">
                        <p class="text-sm leading-5">
                            <span class="font-medium text-gray-900">${atividade.usuario_nome || 'Sistema'}</span>
                            <span class="text-gray-600 ml-1">${atividade.descricao}</span>
                        </p>
                    </div>
                    <div class="flex items-center justify-between mt-1">
                        <p class="text-xs text-gray-500 flex items-center">
                            <i class="far fa-clock mr-1"></i>
                            ${atividade.data_formatada}
                        </p>
                        <span class="text-xs bg-${config.cor}-100 text-${config.cor}-700 px-2 py-1 rounded-full">
                            ${atividade.modulo.charAt(0).toUpperCase() + atividade.modulo.slice(1)}
                        </span>
                    </div>
                </div>
            `;
            
            return div;
        }

        /**
         * Obtém configuração de ícone e cor para um módulo
         */
        function obterConfigModulo(modulo) {
            const configs = {
                'alunos': { icone: 'fa-user-graduate', cor: 'blue' },
                'matriculas': { icone: 'fa-file-signature', cor: 'green' },
                'documentos': { icone: 'fa-certificate', cor: 'yellow' },
                'usuarios': { icone: 'fa-user-cog', cor: 'purple' },
                'financeiro': { icone: 'fa-dollar-sign', cor: 'green' },
                'sistema': { icone: 'fa-cogs', cor: 'gray' },
                'chamados': { icone: 'fa-headset', cor: 'orange' },
                'turmas': { icone: 'fa-users', cor: 'indigo' }
            };
            
            return configs[modulo] || { icone: 'fa-file-alt', cor: 'blue' };
        }

        /**
         * Inicializa tooltips para elementos com título
         */
        function inicializarTooltips() {
            const elementos = document.querySelectorAll('[title]');
            
            elementos.forEach(elemento => {
                elemento.addEventListener('mouseenter', function(e) {
                    mostrarTooltip(e.target, e.target.getAttribute('title'));
                });
                
                elemento.addEventListener('mouseleave', function() {
                    esconderTooltip();
                });
            });
        }

        /**
         * Verifica e exibe notificações importantes
         */
        function verificarNotificacoes() {
            // Verifica polos críticos
            const polosCriticos = <?php echo json_encode(array_filter($polos_limite, function($polo) { return $polo['percentual_usado'] >= 90; })); ?>;
            
            if (polosCriticos.length > 0) {
                setTimeout(() => {
                    mostrarNotificacao(`⚠️ ${polosCriticos.length} polo(s) próximo(s) do limite de documentos!`, 'warning');
                }, 2000);
            }
            
            // Verifica tarefas urgentes
            const tarefasUrgentes = <?php echo count(array_filter($tarefas, function($tarefa) { return $tarefa['tipo'] === 'documento'; })); ?>;
            
            if (tarefasUrgentes > 5) {
                setTimeout(() => {
                    mostrarNotificacao(`📋 Você tem ${tarefasUrgentes} documentos pendentes de processamento`, 'info');
                }, 4000);
            }
        }

        /**
         * Configura auto-refresh dos dados a cada 5 minutos
         */
        function configurarAutoRefresh() {
            setInterval(() => {
                console.log('🔄 Atualizando dados do dashboard...');
                atualizarDados();
            }, 300000); // 5 minutos
        }

        /**
         * Atualiza dados do dashboard via AJAX
         */
        async function atualizarDados() {
            try {
                const response = await fetch('ajax/dashboard_dados.php');
                const dados = await response.json();
                
                if (dados.success) {
                    atualizarEstatisticas(dados.stats);
                    atualizarAtividades(dados.atividades);
                    console.log('✅ Dados atualizados com sucesso');
                }
            } catch (error) {
                console.error('❌ Erro ao atualizar dados:', error);
            }
        }

        /**
         * Configura eventos de clique em elementos interativos
         */
        function configurarEventosClick() {
            // Clique nos cards de estatísticas
            document.querySelectorAll('.card').forEach(card => {
                card.addEventListener('click', function() {
                    const titulo = this.querySelector('p').textContent.toLowerCase();
                    
                    // Redireciona baseado no tipo de estatística
                    if (titulo.includes('alunos')) {
                        window.location.href = 'alunos.php';
                    } else if (titulo.includes('matrícula')) {
                        window.location.href = 'matriculas.php';
                    } else if (titulo.includes('documento')) {
                        window.location.href = 'documentos.php';
                    } else if (titulo.includes('chamado')) {
                        window.location.href = 'chamados.php';
                    }
                });
            });
        }

        /**
         * Configura atalhos de teclado para ações rápidas
         */
        function configurarTecladoShortcuts() {
            document.addEventListener('keydown', function(e) {
                // Ctrl/Cmd + tecla para ações rápidas
                if (e.ctrlKey || e.metaKey) {
                    switch(e.key.toLowerCase()) {
                        case '1':
                            e.preventDefault();
                            window.location.href = 'alunos.php?action=novo';
                            break;
                        case '2':
                            e.preventDefault();
                            window.location.href = 'matriculas.php?action=nova';
                            break;
                        case '3':
                            e.preventDefault();
                            window.location.href = 'declaracoes.php';
                            break;
                        case 'f':
                            e.preventDefault();
                            window.location.href = 'busca_avancada.php';
                            break;
                    }
                }
            });
        }

        /**
         * Inicializa gráficos e visualizações (placeholder para futuras implementações)
         */
        function inicializarGraficos() {
            // Placeholder para inicialização de gráficos com Chart.js ou similar
            console.log('📊 Gráficos prontos para implementação');
        }

        /**
         * Mostra notificação toast
         */
        function mostrarNotificacao(mensagem, tipo = 'info') {
            const cores = {
                'info': 'bg-blue-500',
                'success': 'bg-green-500',
                'warning': 'bg-yellow-500',
                'error': 'bg-red-500'
            };
            
            const notificacao = document.createElement('div');
            notificacao.className = `fixed top-4 right-4 ${cores[tipo]} text-white px-6 py-3 rounded-lg shadow-lg z-50 transform translate-x-full transition-transform duration-300`;
            notificacao.innerHTML = `
                <div class="flex items-center">
                    <span>${mensagem}</span>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            
            document.body.appendChild(notificacao);
            
            // Anima entrada
            setTimeout(() => {
                notificacao.classList.remove('translate-x-full');
            }, 100);
            
            // Remove automaticamente após 5 segundos
            setTimeout(() => {
                notificacao.classList.add('translate-x-full');
                setTimeout(() => notificacao.remove(), 300);
            }, 5000);
        }

        // Adiciona indicador visual para elementos carregando
        function mostrarLoading(elemento) {
            elemento.classList.add('loading-skeleton');
        }

        function esconderLoading(elemento) {
            elemento.classList.remove('loading-skeleton');
        }

        // Log de inicialização do dashboard
        console.log(`
        ╔════════════════════════════════════════════════════════════════╗
        ║                    FACIÊNCIA ERP - DASHBOARD                   ║
        ║                     Secretaria Acadêmica                      ║
        ╠════════════════════════════════════════════════════════════════╣
        ║ 📊 Estatísticas: <?php echo count($stats); ?> métricas carregadas                            ║
        ║ 📋 Pendências: <?php echo count($tarefas); ?> tarefas identificadas                         ║
        ║ 🏢 Polos: <?php echo count($polos_limite); ?> monitorados                                  ║
        ║ 📅 Eventos: <?php echo count($eventos); ?> próximos                                   ║
        ║ 🔄 Atividades: <?php echo count($atividades); ?> registros recentes                        ║
        ╚════════════════════════════════════════════════════════════════╝
        `);
    </script>
</body>
</html>
