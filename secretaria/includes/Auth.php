<?php
/**
 * Classe de autenticação
 *
 * Esta classe gerencia a autenticação e sessão de usuários
 */

class Auth {
    /**
     * Inicia a sessão se ainda não estiver iniciada
     */
    public static function init() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Verifica se o usuário está autenticado
     *
     * @return bool
     */
    public static function isLoggedIn() {
        self::init();
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    /**
     * Autentica um usuário
     *
     * @param array $user Dados do usuário
     * @return void
     */
    public static function login($user) {
        self::init();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_nome'] = $user['nome'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_tipo'] = $user['tipo'];

        // Se for um usuário do tipo polo, busca o ID do polo
        if ($user['tipo'] === 'polo') {
            $db = Database::getInstance();

            // Tenta buscar o polo pelo responsavel_id
            $sql = "SELECT id FROM polos WHERE responsavel_id = ?";
            $resultado = $db->fetchOne($sql, [$user['id']]);

            if ($resultado && isset($resultado['id'])) {
                $_SESSION['polo_id'] = $resultado['id'];
            } else {
                // Tenta buscar diretamente na tabela de usuários (caso exista o campo polo_id)
                try {
                    $sql = "SELECT polo_id FROM usuarios WHERE id = ?";
                    $usuario = $db->fetchOne($sql, [$user['id']]);

                    if ($usuario && isset($usuario['polo_id']) && !empty($usuario['polo_id'])) {
                        $_SESSION['polo_id'] = $usuario['polo_id'];
                    }
                } catch (Exception $e) {
                    // Ignora o erro se a coluna não existir
                }
            }
        }

        // Atualiza o último acesso
        $db = Database::getInstance();
        $db->update('usuarios', [
            'ultimo_acesso' => date('Y-m-d H:i:s')
        ], 'id = ?', [$user['id']]);
    }

    /**
     * Encerra a sessão do usuário
     *
     * @return void
     */
    public static function logout() {
        self::init();
        session_unset();
        session_destroy();
    }

    /**
     * Obtém o ID do usuário autenticado
     *
     * @return int|null
     */
    public static function getUserId() {
        self::init();
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Obtém o nome do usuário autenticado
     *
     * @return string|null
     */
    public static function getUserName() {
        self::init();
        return $_SESSION['user_nome'] ?? null;
    }

    /**
     * Obtém o tipo do usuário autenticado
     *
     * @return string|null
     */
    public static function getUserType() {
        self::init();
        return $_SESSION['user_tipo'] ?? null;
    }

    /**
     * Verifica se o usuário tem permissão para acessar um módulo
     *
     * @param string $modulo Nome do módulo
     * @param string $nivel Nível de acesso mínimo requerido
     * @return bool
     */
    public static function hasPermission($modulo, $nivel = 'visualizar') {
        if (!self::isLoggedIn()) {
            return false;
        }

        $userId = self::getUserId();
        $userType = self::getUserType();

        // Durante a fase de homologação, permitir acesso a todos os módulos para todos os usuários
        // Isso deve ser removido em produção
        return true;

        /* Código original comentado para referência futura
        // Admin master tem acesso total a tudo
        if ($userType === 'admin_master' || $userType === 'admin') {
            return true;
        }

        // Verifica permissões específicas no banco de dados
        $db = Database::getInstance();
        $permissao = $db->fetchOne(
            "SELECT nivel_acesso FROM permissoes WHERE usuario_id = ? AND modulo = ?",
            [$userId, $modulo]
        );

        // Níveis de acesso em ordem crescente
        $niveis = [
            'nenhum' => 0,
            'visualizar' => 1,
            'criar' => 2,
            'editar' => 3,
            'excluir' => 4,
            'total' => 5
        ];

        if (!$permissao) {
            // Se não encontrou permissão específica, verifica se o usuário tem permissão geral
            // baseada no tipo de usuário

            switch ($userType) {
                case 'diretoria':
                    // Diretoria tem acesso total a todos os módulos
                    return true;

                case 'secretaria_academica':
                case 'secretaria_documentos':
                case 'secretaria':
                    // Secretaria tem acesso a módulos acadêmicos
                    if (in_array($modulo, ['alunos', 'matriculas', 'cursos', 'turmas', 'disciplinas', 'documentos', 'relatorios', 'chamados'])) {
                        return $niveis['editar'] >= $niveis[$nivel];
                    }
                    break;

                case 'financeiro':
                    // Financeiro tem acesso a módulos financeiros
                    if (in_array($modulo, ['financeiro', 'relatorios', 'chamados'])) {
                        return $niveis['editar'] >= $niveis[$nivel];
                    }
                    break;

                case 'suporte':
                    // Suporte tem acesso a módulos de suporte
                    if (in_array($modulo, ['chamados', 'relatorios'])) {
                        return $niveis['editar'] >= $niveis[$nivel];
                    }
                    break;

                case 'polo':
                    // Polo tem acesso a módulos específicos
                    if (in_array($modulo, ['alunos', 'matriculas', 'documentos', 'chamados'])) {
                        return $niveis['visualizar'] >= $niveis[$nivel];
                    }
                    break;
            }

            return false;
        }

        // Verifica se o nível de acesso do usuário é suficiente
        return $niveis[$permissao['nivel_acesso']] >= $niveis[$nivel];
        */
    }

    /**
     * Redireciona para a página de login se o usuário não estiver autenticado
     *
     * @return void
     */
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header('Location: login.php');
            exit;
        }
    }

    /**
     * Redireciona para a página inicial se o usuário não tiver permissão
     *
     * @param string $modulo Nome do módulo
     * @param string $nivel Nível de acesso mínimo requerido
     * @return void
     */
    public static function requirePermission($modulo, $nivel = 'visualizar') {
        self::requireLogin();

        // Durante a fase de homologação, permitir acesso a todos os módulos para todos os usuários
        // Isso deve ser removido em produção
        return;

        /* Código original comentado para referência futura
        if (!self::hasPermission($modulo, $nivel)) {
            $_SESSION['error'] = 'Você não tem permissão para acessar esta página.';
            header('Location: index.php');
            exit;
        }
        */
    }
}
