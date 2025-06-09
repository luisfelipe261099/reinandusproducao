<?php
/**
 * Marcar Aula como Concluída
 * Permite marcar uma aula como concluída para um aluno
 */

// Inicializa o sistema
require_once '../includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica se o usuário é do tipo polo
if (getUsuarioTipo() !== 'polo') {
    setMensagem('erro', 'Você não tem permissão para acessar esta página.');
    redirect('../polo/index.php');
    exit;
}

// Instancia o banco de dados
$db = Database::getInstance();

// Obtém o ID do polo
$polo_id = getUsuarioPoloId();

// Verifica se o polo tem acesso ao AVA
if (!$polo_id) {
    setMensagem('erro', 'Não foi possível identificar o polo associado ao seu usuário. Entre em contato com a secretaria para mais informações.');
    redirect('../polo/index.php');
    exit;
}

// Verifica se o polo existe
$sql = "SELECT * FROM polos WHERE id = ?";
$polo = $db->fetchOne($sql, [$polo_id]);

if (!$polo) {
    setMensagem('erro', 'Polo não encontrado no sistema. Entre em contato com a secretaria para mais informações.');
    redirect('../polo/index.php');
    exit;
}

// Verifica se o polo tem acesso ao AVA
$sql = "SELECT * FROM ava_polos_acesso WHERE polo_id = ?";
$acesso = $db->fetchOne($sql, [$polo_id]);

if (!$acesso || $acesso['liberado'] != 1) {
    setMensagem('erro', 'Seu polo não possui acesso liberado ao AVA. Entre em contato com a secretaria para mais informações.');
    redirect('../polo/index.php');
    exit;
}

// Verifica se os parâmetros necessários foram informados
if (!isset($_POST['matricula_id']) || empty($_POST['matricula_id']) || !isset($_POST['aula_id']) || empty($_POST['aula_id'])) {
    setMensagem('erro', 'Parâmetros inválidos.');
    redirect('alunos.php');
    exit;
}

$matricula_id = (int)$_POST['matricula_id'];
$aula_id = (int)$_POST['aula_id'];

// Busca a matrícula
$sql = "SELECT am.*, a.polo_id
        FROM ava_matriculas am
        JOIN alunos a ON am.aluno_id = a.id
        WHERE am.id = ? AND a.polo_id = ?";
$matricula = $db->fetchOne($sql, [$matricula_id, $polo_id]);

if (!$matricula) {
    setMensagem('erro', 'Matrícula não encontrada ou você não tem permissão para acessá-la.');
    redirect('alunos.php');
    exit;
}

// Busca a aula
$sql = "SELECT aa.*, am.curso_id
        FROM ava_aulas aa
        JOIN ava_modulos am ON aa.modulo_id = am.id
        WHERE aa.id = ? AND am.curso_id = ?";
$aula = $db->fetchOne($sql, [$aula_id, $matricula['curso_id']]);

if (!$aula) {
    setMensagem('erro', 'Aula não encontrada ou não pertence ao curso da matrícula.');
    redirect("curso_aluno.php?matricula_id=$matricula_id");
    exit;
}

// Verifica se já existe um registro de progresso para esta aula
$sql = "SELECT * FROM ava_progresso WHERE aula_id = ? AND matricula_id = ?";
$progresso = $db->fetchOne($sql, [$aula_id, $matricula_id]);

if ($progresso) {
    // Atualiza o registro existente
    $dados = [
        'concluido' => 1,
        'data_conclusao' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];

    $db->update('ava_progresso', $dados, "id = ?", [$progresso['id']]);
} else {
    // Cria um novo registro - sem usar as colunas data_inicio e data_conclusao para evitar erros
    $dados = [
        'matricula_id' => $matricula_id,
        'aula_id' => $aula_id,
        'concluido' => 1,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];

    $db->insert('ava_progresso', $dados);
}

// Verifica se todas as aulas do curso foram concluídas
$sql = "SELECT
        (SELECT COUNT(*) FROM ava_aulas aa JOIN ava_modulos am ON aa.modulo_id = am.id WHERE am.curso_id = ?) as total_aulas,
        (SELECT COUNT(*) FROM ava_progresso ap
         JOIN ava_aulas aa ON ap.aula_id = aa.id
         JOIN ava_modulos am ON aa.modulo_id = am.id
         WHERE am.curso_id = ? AND ap.matricula_id = ? AND ap.concluido = 1) as aulas_concluidas";
$resultado = $db->fetchOne($sql, [$matricula['curso_id'], $matricula['curso_id'], $matricula_id]);

// Se todas as aulas foram concluídas, atualiza o status da matrícula para concluído
if ($resultado['total_aulas'] > 0 && $resultado['aulas_concluidas'] >= $resultado['total_aulas']) {
    $dados_matricula = [
        'status' => 'concluido',
        'data_conclusao' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];

    $db->update('ava_matriculas', $dados_matricula, "id = ?", [$matricula_id]);

    // Verifica se já existe um certificado para esta matrícula
    $sql = "SELECT * FROM ava_certificados WHERE matricula_id = ?";
    $certificado = $db->fetchOne($sql, [$matricula_id]);

    if (!$certificado) {
        // Gera um código único para o certificado
        $codigo = strtoupper(uniqid() . bin2hex(random_bytes(3)));

        // Cria um novo certificado
        $dados_certificado = [
            'matricula_id' => $matricula_id,
            'codigo' => $codigo,
            'data_emissao' => date('Y-m-d H:i:s'),
            'status' => 'emitido',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $db->insert('ava_certificados', $dados_certificado);
    }
}

setMensagem('sucesso', 'Aula marcada como concluída com sucesso!');
redirect("curso_aluno.php?matricula_id=$matricula_id&aula_id=$aula_id");
exit;
?>
