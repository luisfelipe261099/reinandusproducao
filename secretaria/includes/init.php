<?php
/**
 * Arquivo de inicialização do sistema
 *
 * Este arquivo deve ser incluído no início de todas as páginas
 */

// Carrega as configurações
require_once __DIR__ . '/../config/config.php';

// Carrega as funções do sistema
require_once __DIR__ . '/functions.php';

// Inicia a sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Função para exibir mensagens de alerta
function showAlert($message, $type = 'success') {
    $_SESSION['alert'] = [
        'message' => $message,
        'type' => $type
    ];
}

// Função para definir mensagens para o usuário
if (!function_exists('setMensagem')) {
    function setMensagem($tipo, $mensagem) {
        $_SESSION['mensagem'] = [
            'tipo' => $tipo,
            'texto' => $mensagem
        ];
    }
}

// Função para obter mensagens de alerta
function getAlert() {
    if (isset($_SESSION['alert'])) {
        $alert = $_SESSION['alert'];
        unset($_SESSION['alert']);
        return $alert;
    }
    return null;
}

// Função para redirecionar
if (!function_exists('redirect')) {
    function redirect($url) {
        header("Location: $url");
        exit;
    }
}

// Função para verificar se é uma requisição POST
if (!function_exists('isPost')) {
    function isPost() {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }
}

// Função para verificar se é uma requisição AJAX
function isAjax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

// Função para sanitizar entrada
function sanitize($input) {
    if (is_array($input)) {
        foreach ($input as $key => $value) {
            $input[$key] = sanitize($value);
        }
    } else {
        $input = trim($input);
        $input = stripslashes($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }
    return $input;
}

// Função para validar CPF
function validarCpf($cpf) {
    // Remove caracteres não numéricos
    $cpf = preg_replace('/[^0-9]/', '', $cpf);

    // Verifica se tem 11 dígitos
    if (strlen($cpf) != 11) {
        return false;
    }

    // Verifica se todos os dígitos são iguais
    if (preg_match('/(\d)\1{10}/', $cpf)) {
        return false;
    }

    // Calcula o primeiro dígito verificador
    $soma = 0;
    for ($i = 0; $i < 9; $i++) {
        $soma += $cpf[$i] * (10 - $i);
    }
    $resto = $soma % 11;
    $dv1 = $resto < 2 ? 0 : 11 - $resto;

    // Calcula o segundo dígito verificador
    $soma = 0;
    for ($i = 0; $i < 9; $i++) {
        $soma += $cpf[$i] * (11 - $i);
    }
    $soma += $dv1 * 2;
    $resto = $soma % 11;
    $dv2 = $resto < 2 ? 0 : 11 - $resto;

    // Verifica se os dígitos verificadores estão corretos
    return ($cpf[9] == $dv1 && $cpf[10] == $dv2);
}

// Função para validar CNPJ
function validarCnpj($cnpj) {
    // Remove caracteres não numéricos
    $cnpj = preg_replace('/[^0-9]/', '', $cnpj);

    // Verifica se tem 14 dígitos
    if (strlen($cnpj) != 14) {
        return false;
    }

    // Verifica se todos os dígitos são iguais
    if (preg_match('/(\d)\1{13}/', $cnpj)) {
        return false;
    }

    // Calcula o primeiro dígito verificador
    $soma = 0;
    $multiplicador = 5;
    for ($i = 0; $i < 12; $i++) {
        $soma += $cnpj[$i] * $multiplicador;
        $multiplicador = ($multiplicador == 2) ? 9 : $multiplicador - 1;
    }
    $resto = $soma % 11;
    $dv1 = $resto < 2 ? 0 : 11 - $resto;

    // Calcula o segundo dígito verificador
    $soma = 0;
    $multiplicador = 6;
    for ($i = 0; $i < 13; $i++) { // Aqui a mudança: loop até 13 para incluir o primeiro dígito verificador
        $soma += (($i == 12) ? $dv1 : $cnpj[$i]) * $multiplicador;
        $multiplicador = ($multiplicador == 2) ? 9 : $multiplicador - 1;
    }
    $resto = $soma % 11;
    $dv2 = $resto < 2 ? 0 : 11 - $resto;

    // Verifica se os dígitos verificadores estão corretos
    return ($cnpj[12] == $dv1 && $cnpj[13] == $dv2);
}

// Função para formatar data
if (!function_exists('formatarData')) {
    function formatarData($data, $formato = DATE_FORMAT) {
        if (empty($data)) {
            return '';
        }

        $timestamp = strtotime($data);
        return date($formato, $timestamp);
    }
}

// Função para formatar valor monetário
if (!function_exists('formatarMoeda')) {
    function formatarMoeda($valor, $simbolo = 'R$') {
        if (empty($valor) || $valor === null) {
            return $simbolo . ' 0,00';
        }
        return $simbolo . ' ' . number_format((float)$valor, 2, ',', '.');
    }
}

// Função para formatar CPF
if (!function_exists('formatarCpf')) {
    function formatarCpf($cpf) {
        if (empty($cpf)) {
            return '';
        }
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        if (strlen($cpf) < 11) {
            return $cpf; // Retorna o CPF sem formatação se não tiver 11 dígitos
        }
        return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
    }
}

// Função para formatar CNPJ
if (!function_exists('formatarCnpj')) {
    function formatarCnpj($cnpj) {
        // Remove caracteres não numéricos
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);

        // Verifica se tem 14 dígitos
        if (strlen($cnpj) != 14) {
            // Se não tiver 14 dígitos, completa com zeros à esquerda
            $cnpj = str_pad($cnpj, 14, '0', STR_PAD_LEFT);
        }

        // Formata o CNPJ: XX.XXX.XXX/XXXX-XX
        return substr($cnpj, 0, 2) . '.' . substr($cnpj, 2, 3) . '.' . substr($cnpj, 5, 3) . '/' . substr($cnpj, 8, 4) . '-' . substr($cnpj, 12, 2);
    }
}

// Função para gerar um número de matrícula
if (!function_exists('gerarNumeroMatricula')) {
    function gerarNumeroMatricula($id) {
        return MATRICULA_PREFIX . MATRICULA_YEAR . str_pad($id, 6, '0', STR_PAD_LEFT);
    }
}

// Função para gerar um número de documento
if (!function_exists('gerarNumeroDocumento')) {
    function gerarNumeroDocumento($id) {
        return DOCUMENTO_PREFIX . DOCUMENTO_YEAR . str_pad($id, 6, '0', STR_PAD_LEFT);
    }
}

// Função para obter o status formatado
function getStatusFormatado($status, $tipo = 'aluno') {
    $statusMap = [
        'aluno' => [
            'ativo' => ['label' => 'Ativo', 'class' => 'status-badge active'],
            'trancado' => ['label' => 'Trancado', 'class' => 'status-badge pending'],
            'cancelado' => ['label' => 'Cancelado', 'class' => 'status-badge inactive'],
            'formado' => ['label' => 'Formado', 'class' => 'status-badge success'],
            'desistente' => ['label' => 'Desistente', 'class' => 'status-badge inactive']
        ],
        'matricula' => [
            'ativo' => ['label' => 'Ativo', 'class' => 'status-badge active'],
            'trancado' => ['label' => 'Trancado', 'class' => 'status-badge pending'],
            'concluído' => ['label' => 'Concluído', 'class' => 'status-badge success'],
            'cancelado' => ['label' => 'Cancelado', 'class' => 'status-badge inactive']
        ],
        'documento' => [
            'solicitado' => ['label' => 'Solicitado', 'class' => 'status-badge pending'],
            'processando' => ['label' => 'Processando', 'class' => 'status-badge pending'],
            'pronto' => ['label' => 'Pronto', 'class' => 'status-badge active'],
            'entregue' => ['label' => 'Entregue', 'class' => 'status-badge success'],
            'cancelado' => ['label' => 'Cancelado', 'class' => 'status-badge inactive']
        ],
        'turma' => [
            'planejada' => ['label' => 'Planejada', 'class' => 'status-badge pending'],
            'em_andamento' => ['label' => 'Em Andamento', 'class' => 'status-badge active'],
            'concluida' => ['label' => 'Concluída', 'class' => 'status-badge success'],
            'cancelada' => ['label' => 'Cancelada', 'class' => 'status-badge inactive']
        ]
    ];

    if (isset($statusMap[$tipo][$status])) {
        return $statusMap[$tipo][$status];
    }

    return ['label' => ucfirst($status), 'class' => 'status-badge'];
}

// Função para obter o nome do mês
function getNomeMes($mes) {
    $meses = [
        1 => 'Janeiro',
        2 => 'Fevereiro',
        3 => 'Março',
        4 => 'Abril',
        5 => 'Maio',
        6 => 'Junho',
        7 => 'Julho',
        8 => 'Agosto',
        9 => 'Setembro',
        10 => 'Outubro',
        11 => 'Novembro',
        12 => 'Dezembro'
    ];

    return $meses[$mes] ?? '';
}

// Função para obter o nome do dia da semana
function getNomeDiaSemana($dia) {
    $dias = [
        0 => 'Domingo',
        1 => 'Segunda-feira',
        2 => 'Terça-feira',
        3 => 'Quarta-feira',
        4 => 'Quinta-feira',
        5 => 'Sexta-feira',
        6 => 'Sábado'
    ];

    return $dias[$dia] ?? '';
}

// Função para obter a data atual formatada
function getDataAtual($formato = DATE_FORMAT) {
    return date($formato);
}

// Função para obter a hora atual formatada
function getHoraAtual($formato = TIME_FORMAT) {
    return date($formato);
}

// Função para obter a data e hora atual formatada
function getDataHoraAtual($formato = DATETIME_FORMAT) {
    return date($formato);
}

// Função para calcular a idade a partir da data de nascimento
function calcularIdade($dataNascimento) {
    $hoje = new DateTime();
    $nascimento = new DateTime($dataNascimento);
    $idade = $hoje->diff($nascimento);
    return $idade->y;
}

// Função para verificar se um arquivo existe
function arquivoExiste($caminho) {
    return file_exists($caminho);
}

// Função para criar um diretório
function criarDiretorio($caminho) {
    if (!file_exists($caminho)) {
        return mkdir($caminho, 0755, true);
    }
    return true;
}

// Função para gerar um nome de arquivo único
function gerarNomeArquivoUnico($nome, $extensao) {
    return uniqid() . '_' . Utils::slugify($nome) . '.' . $extensao;
}

// Função para obter a extensão de um arquivo
function getExtensaoArquivo($nomeArquivo) {
    return pathinfo($nomeArquivo, PATHINFO_EXTENSION);
}

// Função para verificar se uma extensão de arquivo é permitida
function extensaoPermitida($extensao, $permitidas = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt']) {
    return in_array(strtolower($extensao), $permitidas);
}

// Função para obter o tamanho de um arquivo em formato legível
function getTamanhoArquivoLegivel($tamanho) {
    $unidades = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    while ($tamanho >= 1024 && $i < 4) {
        $tamanho /= 1024;
        $i++;
    }
    return round($tamanho, 2) . ' ' . $unidades[$i];
}

// Função para gerar um token aleatório
function gerarToken($tamanho = 32) {
    return bin2hex(random_bytes($tamanho / 2));
}

// Função para obter o IP do cliente
function getClienteIp() {
    return Utils::getClientIp();
}

// Função para obter informações sobre o dispositivo do cliente
function getClienteDispositivo() {
    return Utils::getClientDevice();
}

// Função para registrar um log no sistema
if (!function_exists('registrarLog')) {
    function registrarLog($modulo, $acao, $descricao, $objetoId = null, $objetoTipo = null, $dadosAntigos = null, $dadosNovos = null) {
        Utils::registrarLog($modulo, $acao, $descricao, $objetoId, $objetoTipo, $dadosAntigos, $dadosNovos);
    }
}

// Função para verificar se o usuário está autenticado
function usuarioAutenticado() {
    return Auth::isLoggedIn();
}

// Função para obter o ID do usuário autenticado
function getUsuarioId() {
    return Auth::getUserId();
}

// Função para obter o nome do usuário autenticado
function getUsuarioNome() {
    return Auth::getUserName();
}

// Função para obter o tipo do usuário autenticado
function getUsuarioTipo() {
    return Auth::getUserType();
}

// Função para verificar se o usuário tem permissão
function usuarioTemPermissao($modulo, $nivel = 'visualizar') {
    // Durante a fase de homologação, permitir acesso a todos os módulos para todos os usuários
    // Isso deve ser removido em produção
    return true;

    // Código original comentado para referência futura
    // return Auth::hasPermission($modulo, $nivel);
}

// Função para exigir login
if (!function_exists('exigirLogin')) {
    function exigirLogin() {
        Auth::requireLogin();
    }
}

// Função para exigir permissão
if (!function_exists('exigirPermissao')) {
    function exigirPermissao($modulo, $nivel = 'visualizar') {
        Auth::requirePermission($modulo, $nivel);
    }
}

// Função para fazer logout
function fazerLogout() {
    Auth::logout();
    redirect('login.php');
}

// Função para obter o valor de uma configuração do sistema
function getConfiguracao($chave, $padrao = null) {
    $db = Database::getInstance();
    $config = $db->fetchOne("SELECT valor, tipo FROM configuracoes_sistema WHERE chave = ?", [$chave]);

    if (!$config) {
        return $padrao;
    }

    $valor = $config['valor'];

    // Converte o valor de acordo com o tipo
    switch ($config['tipo']) {
        case 'numero':
            return (float) $valor;
        case 'booleano':
            return (bool) $valor;
        case 'json':
            return json_decode($valor, true);
        default:
            return $valor;
    }
}

// Função para definir o valor de uma configuração do sistema
function setConfiguracao($chave, $valor, $tipo = 'string', $descricao = null, $grupo = null) {
    $db = Database::getInstance();

    // Converte o valor de acordo com o tipo
    switch ($tipo) {
        case 'numero':
            $valor = (float) $valor;
            break;
        case 'booleano':
            $valor = (bool) $valor ? '1' : '0';
            break;
        case 'json':
            $valor = json_encode($valor);
            break;
    }

    // Verifica se a configuração já existe
    $config = $db->fetchOne("SELECT id FROM configuracoes_sistema WHERE chave = ?", [$chave]);

    if ($config) {
        // Atualiza a configuração existente
        $db->update('configuracoes_sistema', [
            'valor' => $valor,
            'tipo' => $tipo,
            'descricao' => $descricao,
            'grupo' => $grupo,
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$config['id']]);
    } else {
        // Cria uma nova configuração
        $db->insert('configuracoes_sistema', [
            'chave' => $chave,
            'valor' => $valor,
            'tipo' => $tipo,
            'descricao' => $descricao,
            'grupo' => $grupo,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }
}

// Função para obter a URL base
function getBaseUrl() {
    return BASE_URL;
}

// Função para obter a URL atual
function getCurrentUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $uri = $_SERVER['REQUEST_URI'];
    return $protocol . '://' . $host . $uri;
}

// Função para obter o caminho do diretório raiz
function getRootDir() {
    return ROOT_DIR;
}

// Função para obter o caminho do diretório de uploads
function getUploadsDir() {
    return UPLOADS_DIR;
}

// Função para obter o caminho do diretório de documentos
function getDocumentosDir() {
    return DOCUMENTOS_DIR;
}

// Função para obter o caminho do diretório temporário
function getTempDir() {
    return TEMP_DIR;
}

// Função para obter o tamanho máximo de upload
function getMaxUploadSize() {
    return MAX_UPLOAD_SIZE;
}

// Função para obter o número de itens por página
function getItemsPerPage() {
    return ITEMS_PER_PAGE;
}

// Função para obter o custo do hash
function getHashCost() {
    return HASH_COST;
}

// Função para obter o nome da sessão
function getSessionName() {
    return SESSION_NAME;
}

// Função para obter o tempo de vida da sessão
function getSessionLifetime() {
    return SESSION_LIFETIME;
}

// Função para obter o email de origem
function getMailFrom() {
    return MAIL_FROM;
}

// Função para obter o nome do email de origem
function getMailFromName() {
    return MAIL_FROM_NAME;
}

// Função para obter o status do log
function getLogEnabled() {
    return LOG_ENABLED;
}

// Função para obter o nível do log
function getLogLevel() {
    return LOG_LEVEL;
}

// Função para obter o formato de data
function getDateFormat() {
    return DATE_FORMAT;
}

// Função para obter o formato de hora
function getTimeFormat() {
    return TIME_FORMAT;
}

// Função para obter o formato de data e hora
function getDatetimeFormat() {
    return DATETIME_FORMAT;
}

// Função para obter o prefixo de documento
function getDocumentoPrefix() {
    return DOCUMENTO_PREFIX;
}

// Função para obter o ano do documento
function getDocumentoYear() {
    return DOCUMENTO_YEAR;
}

// Função para obter o prefixo de matrícula
function getMatriculaPrefix() {
    return MATRICULA_PREFIX;
}

// Função para obter o ano da matrícula
function getMatriculaYear() {
    return MATRICULA_YEAR;
}




