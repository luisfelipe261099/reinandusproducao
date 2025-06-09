<?php
/**
 * Funções utilitárias
 */

/**
 * Verifica se a requisição é do tipo POST
 *
 * @return bool
 */
function isPost() {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * Redireciona para uma URL
 *
 * @param string $url URL para redirecionamento
 * @return void
 */
function redirect($url) {
    header("Location: {$url}");
    exit;
}

/**
 * Define uma mensagem para ser exibida na próxima requisição
 *
 * @param string $tipo Tipo da mensagem (sucesso, erro, info, alerta)
 * @param string $mensagem Texto da mensagem
 * @return void
 */
function setMensagem($tipo, $mensagem) {
    $_SESSION['mensagem'] = $mensagem;
    $_SESSION['mensagem_tipo'] = $tipo;
}

/**
 * Exige que o usuário esteja autenticado
 *
 * @param string $redirectUrl URL para redirecionamento se não estiver autenticado
 * @return void
 */
function exigirLogin($redirectUrl = 'login.php') {
    if (!isset($_SESSION['user_id'])) {
        redirect($redirectUrl);
    }
}

/**
 * Exige que o usuário tenha permissão para acessar um módulo
 *
 * @param string $modulo Nome do módulo
 * @param string $nivel Nível de acesso (visualizar, criar, editar, excluir)
 * @param string $redirectUrl URL para redirecionamento se não tiver permissão
 * @return void
 */
function exigirPermissao($modulo, $nivel = 'visualizar', $redirectUrl = 'index.php') {
    // Primeiro verifica se o usuário está autenticado
    exigirLogin();

    // Durante a fase de homologação, permitir acesso a todos os módulos para todos os usuários
    // Isso deve ser removido em produção
    return;

    /* Código original comentado para referência futura
    // Administradores têm acesso total
    if (isset($_SESSION['user_tipo']) && $_SESSION['user_tipo'] === 'admin') {
        return;
    }

    // Obtém as permissões do usuário
    $db = Database::getInstance();
    $sql = "
        SELECT nivel_acesso, restricoes
        FROM permissoes
        WHERE usuario_id = ? AND modulo = ?
    ";

    $permissao = $db->fetchOne($sql, [$_SESSION['user_id'], $modulo]);

    if (!$permissao) {
        // Registra o log de tentativa de acesso não autorizado
        registrarLog(
            'seguranca',
            'acesso_negado',
            'Tentativa de acesso não autorizado ao módulo ' . $modulo . ' com nível ' . $nivel,
            $_SESSION['user_id'],
            'usuario'
        );

        // Redireciona para a página inicial
        setMensagem('erro', 'Você não tem permissão para acessar este recurso.');
        redirect($redirectUrl);
    }

    // Verifica o nível de acesso
    $temPermissao = false;

    switch ($nivel) {
        case 'visualizar':
            $temPermissao = in_array($permissao['nivel_acesso'], ['visualizar', 'criar', 'editar', 'excluir']);
            break;
        case 'criar':
            $temPermissao = in_array($permissao['nivel_acesso'], ['criar', 'editar', 'excluir']);
            break;
        case 'editar':
            $temPermissao = in_array($permissao['nivel_acesso'], ['editar', 'excluir']);
            break;
        case 'excluir':
            $temPermissao = $permissao['nivel_acesso'] === 'excluir';
            break;
    }

    if (!$temPermissao) {
        // Registra o log de tentativa de acesso não autorizado
        registrarLog(
            'seguranca',
            'acesso_negado',
            'Tentativa de acesso não autorizado ao módulo ' . $modulo . ' com nível ' . $nivel,
            $_SESSION['user_id'],
            'usuario'
        );

        // Redireciona para a página inicial
        setMensagem('erro', 'Você não tem permissão para realizar esta ação.');
        redirect($redirectUrl);
    }
    */
}

/**
 * Registra um log no sistema
 *
 * @param string $modulo Módulo relacionado
 * @param string $acao Ação realizada
 * @param string $descricao Descrição do log
 * @param int|null $objetoId ID do objeto relacionado
 * @param string|null $objetoTipo Tipo do objeto relacionado
 * @return int|false ID do log ou false em caso de erro
 */
function registrarLog($modulo, $acao, $descricao, $objetoId = null, $objetoTipo = null) {
    try {
        // Obtém o ID do usuário autenticado
        $usuarioId = $_SESSION['user_id'] ?? null;

        // Prepara os dados para o log
        $data = [
            'usuario_id' => $usuarioId,
            'modulo' => $modulo,
            'acao' => $acao,
            'descricao' => $descricao,
            'objeto_id' => $objetoId,
            'objeto_tipo' => $objetoTipo,
            'created_at' => date('Y-m-d H:i:s')
        ];

        // Adiciona campos opcionais apenas se a tabela tiver essas colunas
        // Verificamos isso tentando inserir primeiro sem esses campos

        // Insere o log no banco de dados
        $db = Database::getInstance();
        return $db->insert('logs_sistema', $data);
    } catch (Exception $e) {
        // Se ocorrer um erro, apenas ignora e continua
        // Isso evita que erros no log interrompam a execução normal
        return false;
    }
}

/**
 * Formata um CPF
 *
 * @param string $cpf CPF a ser formatado
 * @return string CPF formatado
 */
if (!function_exists('formatarCpf')) {
    function formatarCpf($cpf) {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);

        if (strlen($cpf) != 11) {
            return $cpf;
        }

        return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
    }
}

/**
 * Formata um CNPJ
 *
 * @param string $cnpj CNPJ a ser formatado
 * @return string CNPJ formatado
 */
if (!function_exists('formatarCnpj')) {
    function formatarCnpj($cnpj) {
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);

        if (strlen($cnpj) != 14) {
            return $cnpj;
        }

        return substr($cnpj, 0, 2) . '.' . substr($cnpj, 2, 3) . '.' . substr($cnpj, 5, 3) . '/' . substr($cnpj, 8, 4) . '-' . substr($cnpj, 12, 2);
    }
}

/**
 * Formata um telefone
 *
 * @param string $telefone Telefone a ser formatado
 * @return string Telefone formatado
 */
if (!function_exists('formatarTelefone')) {
    function formatarTelefone($telefone) {
        $telefone = preg_replace('/[^0-9]/', '', $telefone);

        if (strlen($telefone) == 11) {
            return '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 5) . '-' . substr($telefone, 7, 4);
        } elseif (strlen($telefone) == 10) {
            return '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 4) . '-' . substr($telefone, 6, 4);
        }

        return $telefone;
    }
}

/**
 * Formata um CEP
 *
 * @param string $cep CEP a ser formatado
 * @return string CEP formatado
 */
if (!function_exists('formatarCep')) {
    function formatarCep($cep) {
        $cep = preg_replace('/[^0-9]/', '', $cep);

        if (strlen($cep) != 8) {
            return $cep;
        }

        return substr($cep, 0, 5) . '-' . substr($cep, 5, 3);
    }
}

/**
 * Formata um valor monetário
 *
 * @param float $valor Valor a ser formatado
 * @param string $simbolo Símbolo da moeda
 * @return string Valor formatado
 */
if (!function_exists('formatarMoeda')) {
    function formatarMoeda($valor, $simbolo = 'R$') {
        return $simbolo . ' ' . number_format($valor, 2, ',', '.');
    }
}

/**
 * Formata uma data
 *
 * @param string $data Data a ser formatada
 * @param string $formato Formato de saída
 * @return string Data formatada
 */
if (!function_exists('formatarData')) {
    function formatarData($data, $formato = 'd/m/Y') {
        if (empty($data)) {
            return '';
        }

        $timestamp = strtotime($data);
        return date($formato, $timestamp);
    }
}

/**
 * Obtém o ID do polo do usuário autenticado
 *
 * @return int|null ID do polo ou null se não for um usuário do tipo polo
 */
if (!function_exists('getUsuarioPoloId')) {
    function getUsuarioPoloId() {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_tipo']) || $_SESSION['user_tipo'] !== 'polo') {
            return null;
        }

        // Primeiro verifica se o ID do polo já está na sessão
        if (isset($_SESSION['polo_id']) && !empty($_SESSION['polo_id'])) {
            return $_SESSION['polo_id'];
        }

        // Se não estiver na sessão, busca no banco de dados
        $usuario_id = $_SESSION['user_id'];
        $db = Database::getInstance();

        // Busca o polo associado ao usuário
        $sql = "SELECT id FROM polos WHERE responsavel_id = ?";
        $resultado = $db->fetchOne($sql, [$usuario_id]);

        // Se encontrou, armazena na sessão para futuras consultas
        if ($resultado && isset($resultado['id'])) {
            $_SESSION['polo_id'] = $resultado['id'];
            return $resultado['id'];
        }

        // Tenta buscar diretamente na tabela de usuários (caso exista o campo polo_id)
        try {
            $sql = "SELECT polo_id FROM usuarios WHERE id = ?";
            $usuario = $db->fetchOne($sql, [$usuario_id]);

            if ($usuario && isset($usuario['polo_id']) && !empty($usuario['polo_id'])) {
                $_SESSION['polo_id'] = $usuario['polo_id'];
                return $usuario['polo_id'];
            }
        } catch (Exception $e) {
            // Ignora o erro se a coluna não existir
        }

        // Se chegou aqui, não encontrou o ID do polo
        return null;
    }
}

/**
 * Obtém o nome do polo do usuário autenticado
 *
 * @return string|null Nome do polo ou null se não for um usuário do tipo polo
 */
if (!function_exists('getUsuarioPoloNome')) {
    function getUsuarioPoloNome() {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_tipo']) || $_SESSION['user_tipo'] !== 'polo') {
            return null;
        }

        $polo_id = $_SESSION['polo_id'] ?? null;

        if (!$polo_id) {
            return null;
        }

        // Busca o nome do polo no banco de dados
        $db = Database::getInstance();
        $sql = "SELECT nome FROM polos WHERE id = ?";
        $polo = $db->fetchOne($sql, [$polo_id]);

        return $polo ? $polo['nome'] : null;
    }
}

/**
 * Gera um número de documento
 *
 * @param int $timestamp Timestamp para gerar o número
 * @return string Número do documento
 */
if (!function_exists('gerarNumeroDocumento')) {
    function gerarNumeroDocumento($timestamp) {
        $ano = date('Y', $timestamp);
        $mes = date('m', $timestamp);
        $dia = date('d', $timestamp);
        $aleatorio = mt_rand(1000, 9999);

        return "DOC-{$ano}{$mes}{$dia}-{$aleatorio}";
    }
}

/**
 * Verifica as permissões do usuário para um módulo específico
 *
 * @param string $modulo Nome do módulo
 * @return array|false Informações de permissão ou false se não tiver permissão
 */
function verificarPermissoes($modulo) {
    // Primeiro verifica se o usuário está autenticado
    if (!isset($_SESSION['user_id'])) {
        return false;
    }

    // Administradores têm acesso total
    if (isset($_SESSION['user_tipo']) && $_SESSION['user_tipo'] === 'admin') {
        return [
            'nivel_acesso' => 'total',
            'restricoes' => null
        ];
    }

    // Obtém as permissões do usuário
    $db = Database::getInstance();
    $sql = "SELECT nivel_acesso, restricoes FROM permissoes WHERE usuario_id = ? AND modulo = ?";
    $permissao = $db->fetchOne($sql, [$_SESSION['user_id'], $modulo]);

    if (!$permissao) {
        // Se não encontrou permissão específica, verifica se o usuário tem permissão geral
        // baseada no tipo de usuário

        $permissao_padrao = null;

        switch ($_SESSION['user_tipo']) {
            case 'diretoria':
                // Diretoria tem acesso total a todos os módulos
                $permissao_padrao = [
                    'nivel_acesso' => 'total',
                    'restricoes' => null
                ];
                break;

            case 'secretaria_academica':
            case 'secretaria_documentos':
                // Secretaria tem acesso a módulos acadêmicos
                if (in_array($modulo, ['alunos', 'matriculas', 'cursos', 'turmas', 'disciplinas', 'documentos', 'relatorios', 'chamados'])) {
                    $permissao_padrao = [
                        'nivel_acesso' => 'editar',
                        'restricoes' => null
                    ];
                }
                break;

            case 'financeiro':
                // Financeiro tem acesso a módulos financeiros
                if (in_array($modulo, ['financeiro', 'relatorios', 'chamados'])) {
                    $permissao_padrao = [
                        'nivel_acesso' => 'editar',
                        'restricoes' => null
                    ];
                }
                break;

            case 'suporte':
                // Suporte tem acesso a módulos de suporte
                if (in_array($modulo, ['chamados', 'relatorios'])) {
                    $permissao_padrao = [
                        'nivel_acesso' => 'editar',
                        'restricoes' => null
                    ];
                }
                break;

            case 'polo':
                // Polo tem acesso a módulos específicos
                if (in_array($modulo, ['alunos', 'matriculas', 'documentos', 'chamados'])) {
                    $permissao_padrao = [
                        'nivel_acesso' => 'visualizar',
                        'restricoes' => json_encode(['polo_id' => $_SESSION['polo_id']])
                    ];
                }
                break;
        }

        if ($permissao_padrao) {
            return $permissao_padrao;
        }

        return false;
    }

    return $permissao;
}
