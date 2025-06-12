<?php
/**
 * Visualizar/Baixar PDF do Boleto
 */

require_once '../includes/init.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

Auth::requireLogin();
$userType = Auth::getUserType();
if (!in_array($userType, ['financeiro', 'admin_master'])) {
    $_SESSION['error'] = 'Você não tem permissão para acessar este recurso.';
    header('Location: ../index.php');
    exit;
}

$db = Database::getInstance();

// Verifica se foi passado o ID do boleto
$boleto_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$action = $_GET['action'] ?? 'visualizar'; // visualizar ou download

if (!$boleto_id) {
    $_SESSION['error'] = 'ID do boleto não informado.';
    header('Location: boletos.php');
    exit;
}

try {
    // Busca o boleto
    $boleto = $db->fetchOne("
        SELECT b.*,
               CASE
                   WHEN b.tipo_entidade = 'aluno' THEN a.nome
                   WHEN b.tipo_entidade = 'polo' THEN p.nome
                   ELSE b.nome_pagador
               END as pagador_nome
        FROM boletos b
        LEFT JOIN alunos a ON b.tipo_entidade = 'aluno' AND b.entidade_id = a.id
        LEFT JOIN polos p ON b.tipo_entidade = 'polo' AND b.entidade_id = p.id
        WHERE b.id = ?
    ", [$boleto_id]);
    
    if (!$boleto) {
        $_SESSION['error'] = 'Boleto não encontrado.';
        header('Location: boletos.php');
        exit;
    }
    
    // Verifica se o boleto tem dados para gerar PDF
    if (empty($boleto['linha_digitavel']) && empty($boleto['codigo_barras'])) {
        $_SESSION['error'] = 'Este boleto não possui dados suficientes para gerar o PDF.';
        header('Location: boletos.php');
        exit;
    }
    
    // Inclui a classe de PDF
    require_once 'includes/boleto_pdf.php';
    
    // Cria o gerador de PDF
    $pdfGenerator = new BoletoPDF($boleto);
    
    // Verifica se deve baixar ou visualizar
    if ($action === 'download') {
        $pdfGenerator->downloadPDF();
    } else {
        $pdfGenerator->visualizarPDF();
    }
    
} catch (Exception $e) {
    error_log('Erro ao gerar PDF do boleto: ' . $e->getMessage());
    $_SESSION['error'] = 'Erro ao gerar o PDF do boleto: ' . $e->getMessage();
    header('Location: boletos.php');
    exit;
}
?>
