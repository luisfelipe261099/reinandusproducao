<?php
/**
 * Página para atualizar o status de um boleto
 */

// Inclui os arquivos necessários
require_once __DIR__ . '/../includes/init.php';

// Verifica se o usuário está logado
exigirLogin();

// Verifica se o usuário tem permissão para acessar esta página
if (!Auth::hasPermission('financeiro', 'editar')) {
    setMensagem('erro', 'Você não tem permissão para atualizar o status de boletos.');
    redirect('gerar_boleto.php?action=listar');
    exit;
}

// Inicializa a conexão com o banco de dados
$db = Database::getInstance();

// Verifica se foi passado um ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Verifica se foi passado um status
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Verifica se o status é válido
$status_validos = ['pendente', 'pago', 'cancelado', 'vencido'];
if (!in_array($status, $status_validos)) {
    setMensagem('erro', 'Status inválido.');
    redirect('gerar_boleto.php?action=visualizar&id=' . $id);
    exit;
}

// Busca os dados do boleto
$boleto = $db->fetchOne("SELECT * FROM boletos WHERE id = ?", [$id]);

// Verifica se o boleto existe
if (!$boleto) {
    setMensagem('erro', 'Boleto não encontrado.');
    redirect('gerar_boleto.php?action=listar');
    exit;
}

// Verifica se o status é diferente do atual
if ($boleto['status'] === $status) {
    setMensagem('aviso', 'O boleto já está com o status ' . ucfirst($status) . '.');
    redirect('gerar_boleto.php?action=visualizar&id=' . $id);
    exit;
}

// Atualiza o status do boleto
try {
    $dados_update = [
        'status' => $status,
        'observacoes' => ($boleto['observacoes'] ? $boleto['observacoes'] . "\n" : "") . 
                        "Status atualizado manualmente de '" . ucfirst($boleto['status']) . "' para '" . ucfirst($status) . "' em " . date('d/m/Y H:i:s')
    ];
    
    // Se o status for cancelado, atualiza a data de cancelamento
    if ($status === 'cancelado') {
        $dados_update['data_cancelamento'] = date('Y-m-d H:i:s');
    }
    
    // Se o status for pago, atualiza a data de pagamento
    if ($status === 'pago') {
        $dados_update['data_pagamento'] = date('Y-m-d H:i:s');
    }
    
    $result = $db->update('boletos', $dados_update, 'id = ?', [$id]);
    
    if ($result === false) {
        throw new Exception("Erro ao atualizar o status do boleto.");
    }
    
    // Registra o histórico
    $db->insert('boletos_historico', [
        'boleto_id' => $id,
        'acao' => 'atualizacao_status',
        'data' => date('Y-m-d H:i:s'),
        'usuario_id' => isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : null,
        'detalhes' => "Status atualizado manualmente de '" . ucfirst($boleto['status']) . "' para '" . ucfirst($status) . "'"
    ]);
    
    setMensagem('sucesso', 'Status do boleto atualizado com sucesso para ' . ucfirst($status) . '.');
} catch (Exception $e) {
    setMensagem('erro', 'Erro ao atualizar o status do boleto: ' . $e->getMessage());
}

// Redireciona para a página de visualização do boleto
redirect('gerar_boleto.php?action=visualizar&id=' . $id);
?>
