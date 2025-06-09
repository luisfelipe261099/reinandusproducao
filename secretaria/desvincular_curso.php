<?php
/**
 * Desvincular curso do polo atual e vincular ao polo temporário
 */

// Inicializa o sistema
require_once __DIR__ . '/includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica se o usuário tem permissão para gerenciar cursos
exigirPermissao('cursos', 'editar');

// Verifica se o ID do curso foi informado
if (!isset($_POST['curso_id']) || empty($_POST['curso_id'])) {
    setMensagem('erro', 'ID do curso não informado.');
    redirect('cursos.php');
    exit;
}

// Verifica se o ID do polo foi informado
if (!isset($_POST['polo_id']) || empty($_POST['polo_id'])) {
    setMensagem('erro', 'ID do polo não informado.');
    redirect('cursos.php');
    exit;
}

$curso_id = (int)$_POST['curso_id'];
$polo_id = (int)$_POST['polo_id'];
$polo_temporario_id = 1; // ID do polo temporário

// Instancia o banco de dados
$db = Database::getInstance();

// Verifica se o curso existe
$sql = "SELECT * FROM cursos WHERE id = ?";
$curso = $db->fetchOne($sql, [$curso_id]);

if (!$curso) {
    setMensagem('erro', 'Curso não encontrado.');
    redirect('cursos.php');
    exit;
}

// Verifica se o polo temporário existe
$sql = "SELECT * FROM polos WHERE id = ?";
$polo_temporario = $db->fetchOne($sql, [$polo_temporario_id]);

if (!$polo_temporario) {
    setMensagem('erro', 'Polo temporário não encontrado. Por favor, crie um polo com ID 1 para ser usado como temporário.');
    redirect('cursos.php');
    exit;
}

try {
    // Inicia a transação
    $db->beginTransaction();

    // Atualiza o polo_id do curso para o polo temporário
    $db->update('cursos', [
        'polo_id' => $polo_temporario_id,
        'updated_at' => date('Y-m-d H:i:s')
    ], 'id = ?', [$curso_id]);

    // Registra o log
    registrarLog(
        'cursos',
        'desvincular_polo',
        "Curso {$curso['nome']} (ID: {$curso_id}) desvinculado do polo ID: {$polo_id} e vinculado ao polo temporário ID: {$polo_temporario_id}",
        $curso_id,
        'curso'
    );

    // Confirma a transação
    $db->commit();

    setMensagem('sucesso', "O curso {$curso['nome']} foi desvinculado do polo atual e vinculado ao polo temporário com sucesso.");
} catch (Exception $e) {
    // Desfaz a transação em caso de erro
    $db->rollBack();

    setMensagem('erro', 'Erro ao desvincular o curso: ' . $e->getMessage());
}

// Redireciona para a página do polo
redirect('polos.php?action=visualizar&id=' . $polo_id);
