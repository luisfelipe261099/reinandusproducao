<?php
/**
 * ============================================================================
 * INICIALIZAÇÃO DO MÓDULO ADMINISTRADOR - SISTEMA FACIÊNCIA ERP
 * ============================================================================
 *
 * Este arquivo é responsável por inicializar o módulo administrador do sistema.
 * Deve ser incluído no início de todas as páginas do módulo administrador.
 *
 * @author Sistema Faciência ERP
 * @version 1.0
 * @since 2025-06-10
 *
 * Funcionalidades:
 * - Controle de acesso administrativo
 * - Inicialização de dependências
 * - Carregamento de configurações específicas
 * - Validação de permissões de super usuário
 *
 * ============================================================================
 */

// Carrega as configurações principais do sistema
require_once __DIR__ . '/../../includes/init.php';

// ============================================================================
// CONTROLE DE ACESSO ADMINISTRATIVO
// ============================================================================

/**
 * Verifica se o usuário possui acesso administrativo
 * Apenas usuários do tipo 'admin_master' podem acessar este módulo
 */
function exigirAcessoAdministrador() {
    // Verifica se o usuário está autenticado
    exigirLogin();
    
    // Obtém os dados do usuário da sessão
    $tipoUsuario = $_SESSION['user_tipo'] ?? null;
    $userId = $_SESSION['user_id'] ?? null;
    
    // Verifica se é um administrador master
    if ($tipoUsuario !== 'admin_master') {
        // Registra tentativa de acesso não autorizado
        if (function_exists('registrarLog')) {
            $dadosLog = [
                'ip' => $_SERVER['REMOTE_ADDR'],
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Desconhecido',
                'url_tentativa' => $_SERVER['REQUEST_URI'] ?? 'Desconhecida',
                'tipo_usuario_atual' => $tipoUsuario,
                'data_hora' => date('Y-m-d H:i:s')
            ];
            
            registrarLog(
                'administrador', 
                'acesso_negado', 
                'Tentativa de acesso não autorizado ao módulo administrador', 
                $userId, 
                'usuario', 
                null, 
                $dadosLog
            );
        }
        
        // Define mensagem de erro
        setMensagem('erro', 'Acesso negado! Apenas administradores master podem acessar este módulo.');
        
        // Redireciona para a página inicial baseada no tipo de usuário
        switch ($tipoUsuario) {
            case 'polo':
                redirect('../polo/index.php');
                break;
            case 'financeiro':
                redirect('../financeiro/index.php');
                break;
            case 'secretaria_academica':
            case 'secretaria_documentos':
                redirect('../secretaria/index.php');
                break;
            default:
                redirect('../login.php');
                break;
        }
        exit;
    }
    
    // Registra acesso autorizado ao módulo
    if (function_exists('registrarLog')) {
        $dadosLog = [
            'ip' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Desconhecido',
            'url_acesso' => $_SERVER['REQUEST_URI'] ?? 'Desconhecida',
            'data_hora' => date('Y-m-d H:i:s')
        ];
        
        registrarLog(
            'administrador', 
            'acesso_autorizado', 
            'Acesso autorizado ao módulo administrador', 
            $userId, 
            'usuario', 
            null, 
            $dadosLog
        );
    }
}

// ============================================================================
// FUNÇÕES AUXILIARES DO MÓDULO ADMINISTRADOR
// ============================================================================

/**
 * Verifica se o usuário atual é um administrador master
 * 
 * @return bool True se for admin master, false caso contrário
 */
function isAdminMaster() {
    return ($_SESSION['user_tipo'] ?? null) === 'admin_master';
}

/**
 * Obtém estatísticas gerais do sistema para o dashboard administrativo
 * 
 * @return array Array com estatísticas do sistema
 */
function obterEstatisticasGerais() {
    try {
        $db = Database::getInstance();
        $stats = [];
        
        // Total de usuários por tipo
        $sql = "SELECT tipo, COUNT(*) as total FROM usuarios WHERE status = 'ativo' GROUP BY tipo";
        $usuarios_por_tipo = $db->fetchAll($sql);
        $stats['usuarios_por_tipo'] = [];
        foreach ($usuarios_por_tipo as $row) {
            $stats['usuarios_por_tipo'][$row['tipo']] = $row['total'];
        }
        
        // Total geral de usuários ativos
        $sql = "SELECT COUNT(*) as total FROM usuarios WHERE status = 'ativo'";
        $resultado = $db->fetchOne($sql);
        $stats['total_usuarios_ativos'] = $resultado['total'] ?? 0;
        
        // Total de usuários inativos/bloqueados
        $sql = "SELECT COUNT(*) as total FROM usuarios WHERE status IN ('inativo', 'bloqueado')";
        $resultado = $db->fetchOne($sql);
        $stats['total_usuarios_inativos'] = $resultado['total'] ?? 0;
        
        // Total de alunos
        $sql = "SELECT COUNT(*) as total FROM alunos WHERE status = 'ativo'";
        $resultado = $db->fetchOne($sql);
        $stats['total_alunos'] = $resultado['total'] ?? 0;
        
        // Total de polos
        $sql = "SELECT COUNT(*) as total FROM polos WHERE status = 'ativo'";
        $resultado = $db->fetchOne($sql);
        $stats['total_polos'] = $resultado['total'] ?? 0;
        
        // Logs das últimas 24 horas
        $sql = "SELECT COUNT(*) as total FROM logs_sistema WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
        $resultado = $db->fetchOne($sql);
        $stats['logs_24h'] = $resultado['total'] ?? 0;
        
        // Logins nas últimas 24 horas
        $sql = "SELECT COUNT(*) as total FROM logs_sistema WHERE acao = 'login' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
        $resultado = $db->fetchOne($sql);
        $stats['logins_24h'] = $resultado['total'] ?? 0;
        
        return $stats;
        
    } catch (Exception $e) {
        error_log('Erro ao obter estatísticas gerais: ' . $e->getMessage());
        return [];
    }
}

/**
 * Obtém os logs mais recentes do sistema
 * 
 * @param int $limite Número máximo de logs a retornar
 * @param string $filtro_modulo Filtro por módulo (opcional)
 * @param string $filtro_acao Filtro por ação (opcional)
 * @return array Array com os logs
 */
function obterLogsRecentes($limite = 50, $filtro_modulo = null, $filtro_acao = null) {
    try {
        $db = Database::getInstance();
        
        $where = [];
        $params = [];
        
        if ($filtro_modulo) {
            $where[] = "l.modulo = ?";
            $params[] = $filtro_modulo;
        }
        
        if ($filtro_acao) {
            $where[] = "l.acao = ?";
            $params[] = $filtro_acao;
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $sql = "SELECT 
                    l.*,
                    u.nome as usuario_nome,
                    u.email as usuario_email,
                    u.tipo as usuario_tipo
                FROM logs_sistema l
                LEFT JOIN usuarios u ON l.usuario_id = u.id
                {$whereClause}
                ORDER BY l.created_at DESC
                LIMIT ?";
        
        $params[] = $limite;
        
        return $db->fetchAll($sql, $params);
        
    } catch (Exception $e) {
        error_log('Erro ao obter logs recentes: ' . $e->getMessage());
        return [];
    }
}

/**
 * Obtém informações detalhadas sobre tentativas de login
 * 
 * @param int $limite Número máximo de registros
 * @return array Array com informações de login
 */
function obterTentativasLogin($limite = 100) {
    try {
        $db = Database::getInstance();
        
        $sql = "SELECT 
                    l.*,
                    u.nome as usuario_nome,
                    u.email as usuario_email,
                    u.tipo as usuario_tipo
                FROM logs_sistema l
                LEFT JOIN usuarios u ON l.usuario_id = u.id
                WHERE l.acao IN ('login', 'login_falha', 'login_bloqueado', 'login_recaptcha_falha', 'login_automatico')
                ORDER BY l.created_at DESC
                LIMIT ?";
        
        return $db->fetchAll($sql, [$limite]);
        
    } catch (Exception $e) {
        error_log('Erro ao obter tentativas de login: ' . $e->getMessage());
        return [];
    }
}

/**
 * Registra ação administrativa no sistema
 * 
 * @param string $acao Ação realizada
 * @param string $descricao Descrição da ação
 * @param array $dados_adicionais Dados adicionais (opcional)
 */
function registrarAcaoAdministrativa($acao, $descricao, $dados_adicionais = []) {
    $dados_completos = array_merge($dados_adicionais, [
        'ip' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Desconhecido',
        'data_hora' => date('Y-m-d H:i:s'),
        'usuario_id' => $_SESSION['user_id'] ?? null,
        'usuario_nome' => $_SESSION['user_name'] ?? 'Desconhecido'
    ]);
    
    if (function_exists('registrarLog')) {
        registrarLog(
            'administrador',
            $acao,
            $descricao,
            $_SESSION['user_id'] ?? null,
            'admin_action',
            null,
            $dados_completos
        );
    }
}

// ============================================================================
// CONFIGURAÇÕES ESPECÍFICAS DO MÓDULO
// ============================================================================

// Define o título base do módulo
if (!defined('MODULO_TITULO')) {
    define('MODULO_TITULO', 'Administração do Sistema');
}

// Define a cor tema do módulo
if (!defined('MODULO_COR_TEMA')) {
    define('MODULO_COR_TEMA', '#DC2626'); // Vermelho para indicar área administrativa
}

// Define o ícone do módulo
if (!defined('MODULO_ICONE')) {
    define('MODULO_ICONE', 'fas fa-shield-alt');
}

// ============================================================================
// LOG DE INICIALIZAÇÃO
// ============================================================================

// Registra a inicialização do módulo administrador
if (isset($_SESSION['user_id'])) {
    registrarAcaoAdministrativa(
        'modulo_inicializado',
        'Módulo administrador inicializado com sucesso',
        [
            'arquivo' => basename($_SERVER['SCRIPT_NAME']),
            'url_completa' => $_SERVER['REQUEST_URI'] ?? 'Desconhecida'
        ]
    );
}

// ============================================================================
// FUNÇÕES AUXILIARES PARA ESTATÍSTICAS DOS MÓDULOS
// ============================================================================

/**
 * Conta o número de módulos ativos no sistema
 */
function contarModulosAtivos() {
    // Lista hardcoded dos módulos disponíveis
    $modulos = [
        'administrador',
        'financeiro', 
        'secretaria',
        'aluno',
        'ava',
        'polo',
        'api'
    ];
    
    return count($modulos);
}

/**
 * Conta usuários online (que fizeram login nos últimos 15 minutos)
 */
function contarUsuariosOnline() {
    try {
        $conn = obterConexao();
        $stmt = $conn->prepare("
            SELECT COUNT(DISTINCT usuario_id) as total 
            FROM logs_sistema 
            WHERE data_acao >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)
            AND acao IN ('login', 'acesso_pagina', 'acao_usuario')
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['total'] ?? 0;
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Conta acessos realizados hoje
 */
function contarAcessosHoje() {
    try {
        $conn = obterConexao();
        $stmt = $conn->prepare("
            SELECT COUNT(*) as total 
            FROM logs_sistema 
            WHERE DATE(data_acao) = CURDATE()
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['total'] ?? 0;
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Verifica o status geral do sistema
 */
function verificarStatusSistema() {
    try {
        // Testa conexão com banco de dados
        $conn = obterConexao();
        $stmt = $conn->prepare("SELECT 1");
        $stmt->execute();
        
        return 'Online';
    } catch (Exception $e) {
        return 'Offline';
    }
}

/**
 * Formata número de bytes em formato legível
 */
function formatarBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

/**
 * Obtém informações de uso de disco
 */
function obterInfoDisco() {
    try {
        $total = disk_total_space('.');
        $free = disk_free_space('.');
        
        if ($total && $free) {
            $used = $total - $free;
            $percent = round(($used / $total) * 100, 1);
            
            return [
                'total' => $total,
                'used' => $used,
                'free' => $free,
                'percent_used' => $percent,
                'total_formatted' => formatarBytes($total),
                'used_formatted' => formatarBytes($used),
                'free_formatted' => formatarBytes($free)
            ];
        }
        
        return null;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Obtém estatísticas gerais do sistema
 */
function obterEstatisticasSistema() {
    try {
        $conn = obterConexao();
        
        // Total de usuários
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM usuarios WHERE ativo = 1");
        $stmt->execute();
        $usuarios = $stmt->get_result()->fetch_assoc()['total'];
        
        // Total de alunos
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM alunos WHERE status = 'ativo'");
        $stmt->execute();
        $alunos = $stmt->get_result()->fetch_assoc()['total'];
        
        // Total de logs dos últimos 30 dias
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM logs_sistema WHERE data_acao >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $stmt->execute();
        $logs_30d = $stmt->get_result()->fetch_assoc()['total'];
        
        // Usuários online
        $usuarios_online = contarUsuariosOnline();
        
        // Acessos hoje
        $acessos_hoje = contarAcessosHoje();
        
        return [
            'usuarios_ativos' => $usuarios,
            'alunos_ativos' => $alunos,
            'logs_30_dias' => $logs_30d,
            'usuarios_online' => $usuarios_online,
            'acessos_hoje' => $acessos_hoje,
            'status_sistema' => verificarStatusSistema(),
            'info_disco' => obterInfoDisco()
        ];
        
    } catch (Exception $e) {
        return [
            'usuarios_ativos' => 0,
            'alunos_ativos' => 0,
            'logs_30_dias' => 0,
            'usuarios_online' => 0,
            'acessos_hoje' => 0,
            'status_sistema' => 'Erro',
            'info_disco' => null
        ];
    }
}

// ============================================================================
// FUNÇÃO DE COMPATIBILIDADE PARA AJAX
// ============================================================================

/**
 * Obtém uma conexão MySQLi para compatibilidade com funções antigas
 * 
 * @return mysqli Conexão MySQLi
 */
function obterConexao() {
    // Carrega as configurações do banco
    require_once __DIR__ . '/../../config/database.php';
    
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Erro de conexão: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    
    return $conn;
}
