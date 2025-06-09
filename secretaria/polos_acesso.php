<?php
/**
 * Processamento de criação de acesso para polos
 */

// Inicializa o sistema
require_once __DIR__ . '/includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica se o usuário tem permissão para gerenciar polos
exigirPermissao('polos', 'editar');

// Log para depuração
error_log('Requisição recebida em polos_acesso.php');
error_log('POST: ' . print_r($_POST, true));

// Instancia o banco de dados
$db = Database::getInstance();
error_log('Banco de dados inicializado');

// Verifica os tipos de usuário disponíveis
$tipo_usuario = 'secretaria_academica'; // Valor padrão
try {
    $sql = "SHOW COLUMNS FROM usuarios LIKE 'tipo'";
    $result = $db->fetchOne($sql);
    if ($result) {
        preg_match("/^enum\((.*)\)$/", $result['Type'], $matches);
        $tipos = array();
        if (isset($matches[1])) {
            $enum_str = $matches[1];
            $tipos_raw = explode(",", $enum_str);
            foreach ($tipos_raw as $tipo) {
                $tipos[] = trim($tipo, "'");
            }
            error_log('Tipos de usuário disponíveis: ' . print_r($tipos, true));

            // Verifica se o tipo 'polo' existe
            if (in_array('polo', $tipos)) {
                $tipo_usuario = 'polo';
                error_log('Tipo "polo" encontrado e será usado');
            } else {
                error_log('Tipo "polo" não encontrado, usando "secretaria_academica" como alternativa');
            }
        }
    }
} catch (Exception $e) {
    error_log('Erro ao verificar tipos de usuário: ' . $e->getMessage());
}

// Verifica se o ID do polo foi informado
if (!isset($_POST['polo_id']) || empty($_POST['polo_id'])) {
    error_log('ID do polo não informado');
    setMensagem('erro', 'ID do polo não informado.');
    redirect('polos.php');
    exit;
}

$polo_id = (int)$_POST['polo_id'];

// O banco de dados já foi instanciado acima
error_log('Usando instância do banco de dados para buscar dados do polo');

// Busca os dados do polo
$sql = "SELECT * FROM polos WHERE id = ?";
$polo = $db->fetchOne($sql, [$polo_id]);

if (!$polo) {
    setMensagem('erro', 'Polo não encontrado.');
    redirect('polos.php');
    exit;
}

// Verifica se já existe um usuário responsável
$responsavel_id = $polo['responsavel_id'];
$usuario_existente = null;

if ($responsavel_id) {
    $sql = "SELECT * FROM usuarios WHERE id = ?";
    $usuario_existente = $db->fetchOne($sql, [$responsavel_id]);
}

try {
    // Verifica se o banco de dados está disponível
    if (!$db) {
        throw new Exception('Erro: Conexão com o banco de dados não disponível');
    }

    // Inicia a transação
    $db->beginTransaction();
    error_log('Transação iniciada com sucesso');

    // Senha padrão (pode ser alterada posteriormente pelo usuário)
    $senha_padrao = 'Polo@' . date('Y');
    $senha_hash = password_hash($senha_padrao, PASSWORD_DEFAULT);
    error_log('Senha padrão gerada: ' . $senha_padrao . ' (hash: ' . substr($senha_hash, 0, 10) . '...)');

    if ($usuario_existente) {
        error_log('Usuário existente encontrado: ID ' . $responsavel_id);
        // Atualiza o usuário existente
        $db->update('usuarios', [
            'nome' => $polo['nome'],
            'email' => $polo['email'],
            'senha' => $senha_hash,
            'tipo' => $tipo_usuario, // Usando o tipo determinado anteriormente
            'status' => 'ativo',
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$responsavel_id]);
        error_log('Usuário atualizado com sucesso');

        $mensagem = 'Acesso do polo redefinido com sucesso! A senha foi redefinida para o padrão.';
        $tipo_log = 'redefinir_acesso';
    } else {
        error_log('Nenhum usuário existente encontrado, criando novo usuário');
        // Verifica se o polo tem email
        if (empty($polo['email'])) {
            error_log('ERRO: Polo não possui email');
            throw new Exception('O polo não possui um e-mail cadastrado. Por favor, edite o polo e adicione um e-mail antes de criar o acesso.');
        }

        // Cria um novo usuário
        $usuario_id = $db->insert('usuarios', [
            'nome' => $polo['nome'],
            'email' => $polo['email'],
            'senha' => $senha_hash,
            'tipo' => $tipo_usuario, // Usando o tipo determinado anteriormente
            'status' => 'ativo',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        error_log('Novo usuário criado com ID: ' . $usuario_id);

        // Atualiza o polo com o ID do usuário responsável
        $db->update('polos', [
            'responsavel_id' => $usuario_id,
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$polo_id]);
        error_log('Polo atualizado com o ID do usuário responsável');

        $mensagem = 'Acesso do polo criado com sucesso!';
        $tipo_log = 'criar_acesso';
    }

    // Registra o log
    registrarLog(
        'polos',
        $tipo_log,
        $mensagem,
        $polo_id,
        'polo'
    );

    // Confirma a transação
    $db->commit();
    error_log('Transação confirmada com sucesso');

    // Compatibilidade com ambas as versões da função setMensagem
    if (function_exists('setMensagem')) {
        setMensagem('sucesso', $mensagem);
        error_log('Função setMensagem chamada com sucesso para mensagem de sucesso');
    } else {
        // Fallback caso a função não exista
        $_SESSION['mensagem'] = [
            'tipo' => 'sucesso',
            'texto' => $mensagem
        ];
        error_log('Fallback: Definindo $_SESSION[\'mensagem\'] diretamente para mensagem de sucesso');
    }
    error_log('Mensagem de sucesso definida: ' . $mensagem);
} catch (Exception $e) {
    // Desfaz a transação em caso de erro
    if ($db) {
        try {
            $db->rollBack();
            error_log('Transação desfeita com sucesso após erro');
        } catch (Exception $rollbackError) {
            error_log('ERRO ao desfazer transação: ' . $rollbackError->getMessage());
        }
    }

    error_log('ERRO: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());

    // Mensagem de erro mais amigável para o usuário
    $mensagem_erro = 'Erro ao processar acesso do polo: ' . $e->getMessage();
    error_log('Definindo mensagem de erro: ' . $mensagem_erro);

    // Compatibilidade com ambas as versões da função setMensagem
    if (function_exists('setMensagem')) {
        setMensagem('erro', $mensagem_erro);
        error_log('Função setMensagem chamada com sucesso');
    } else {
        // Fallback caso a função não exista
        $_SESSION['mensagem'] = [
            'tipo' => 'erro',
            'texto' => $mensagem_erro
        ];
        error_log('Fallback: Definindo $_SESSION[\'mensagem\'] diretamente');
    }
}

// Redireciona para a página de visualização do polo
$redirect_url = 'polos.php?action=visualizar&id=' . $polo_id;
error_log('Redirecionando para: ' . $redirect_url);
redirect($redirect_url);
