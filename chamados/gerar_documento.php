<?php
// Inicializa o sistema
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/chamados_functions.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica se o usuário tem permissão para gerar documentos
exigirPermissao('chamados', 'editar');

// Instancia o banco de dados
$db = Database::getInstance();

// Verifica se os parâmetros foram informados
if (!isset($_POST['chamado_id']) || empty($_POST['chamado_id']) ||
    !isset($_POST['aluno_id']) || empty($_POST['aluno_id'])) {
    $_SESSION['mensagem'] = 'Parâmetros inválidos.';
    $_SESSION['mensagem_tipo'] = 'erro';
    header('Location: index.php');
    exit;
}

$chamado_id = (int)$_POST['chamado_id'];
$aluno_id = (int)$_POST['aluno_id'];

// Busca o chamado
$chamado = buscarChamadoPorId($db, $chamado_id);

if (!$chamado) {
    $_SESSION['mensagem'] = 'Chamado não encontrado.';
    $_SESSION['mensagem_tipo'] = 'erro';
    header('Location: index.php');
    exit;
}

// Verifica se o usuário tem permissão para acessar este chamado
if (!usuarioTemPermissaoChamado($db, $chamado_id, getUsuarioId(), getUsuarioTipo())) {
    $_SESSION['mensagem'] = 'Você não tem permissão para acessar este chamado.';
    $_SESSION['mensagem_tipo'] = 'erro';
    header('Location: index.php');
    exit;
}

// Verifica se o aluno está relacionado ao chamado
$sql = "SELECT COUNT(*) as total FROM chamados_alunos WHERE chamado_id = ? AND aluno_id = ?";
$resultado = $db->fetchOne($sql, [$chamado_id, $aluno_id]);

if (!$resultado || $resultado['total'] == 0) {
    $_SESSION['mensagem'] = 'Aluno não encontrado neste chamado.';
    $_SESSION['mensagem_tipo'] = 'erro';
    header('Location: visualizar.php?id=' . $chamado_id);
    exit;
}

// Gera o documento
$arquivo_path = gerarDocumento($db, $chamado_id, $aluno_id, $chamado['subtipo']);

if ($arquivo_path) {
    // Registra a geração do documento
    $resultado = registrarDocumentoGerado($db, $chamado_id, $aluno_id, $arquivo_path, getUsuarioId());

    if ($resultado) {
        $_SESSION['mensagem'] = 'Documento gerado com sucesso!';
        $_SESSION['mensagem_tipo'] = 'sucesso';
    } else {
        $_SESSION['mensagem'] = 'Documento gerado, mas houve um erro ao registrar. Tente novamente.';
        $_SESSION['mensagem_tipo'] = 'alerta';
    }
} else {
    $_SESSION['mensagem'] = 'Erro ao gerar documento. Tente novamente.';
    $_SESSION['mensagem_tipo'] = 'erro';
}

header('Location: visualizar.php?id=' . $chamado_id);
exit;
