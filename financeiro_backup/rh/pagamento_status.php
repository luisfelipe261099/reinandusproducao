<?php
/**
 * Página para alterar o status de um pagamento
 */

// Inclui os arquivos necessários
require_once __DIR__ . '/../../includes/init.php';

// Verifica se o usuário está logado
exigirLogin();

// Verifica se o usuário tem permissão para acessar esta página
if (!Auth::hasPermission('financeiro', 'editar')) {
    setMensagem('erro', 'Você não tem permissão para acessar esta página.');
    redirect('pagamentos.php');
    exit;
}

// Inicializa a conexão com o banco de dados
$db = Database::getInstance();

// Verifica se o ID e o status foram informados
if (!isset($_GET['id']) || !isset($_GET['status'])) {
    setMensagem('erro', 'Parâmetros inválidos.');
    redirect('pagamentos.php');
    exit;
}

$id = (int)$_GET['id'];
$status = $_GET['status'];

// Verifica se o status é válido
if (!in_array($status, ['pendente', 'pago', 'cancelado'])) {
    setMensagem('erro', 'Status inválido.');
    redirect('pagamentos.php');
    exit;
}

// Busca os dados do pagamento
$pagamento = $db->fetchOne("SELECT * FROM pagamentos WHERE id = ?", [$id]);

if (!$pagamento) {
    setMensagem('erro', 'Pagamento não encontrado.');
    redirect('pagamentos.php');
    exit;
}

// Atualiza o status do pagamento
$dados = [
    'status' => $status
];

// Se o status for "pago", define a data de pagamento como hoje (se ainda não estiver definida)
if ($status == 'pago' && $pagamento['status'] != 'pago') {
    $dados['data_pagamento'] = date('Y-m-d');
}

try {
    // Atualiza o pagamento
    $result = $db->update('pagamentos', $dados, ['id' => $id]);
    
    if ($result) {
        // Busca o funcionário
        $funcionario = $db->fetchOne("SELECT * FROM funcionarios WHERE id = ?", [$pagamento['funcionario_id']]);
        
        // Verifica se existe uma conta a pagar relacionada
        $conta_pagar_rh = $db->fetchOne("SELECT * FROM contas_pagar_rh WHERE pagamento_id = ?", [$id]);
        
        if ($conta_pagar_rh) {
            // Atualiza a conta a pagar
            $conta_pagar = [
                'status' => $status,
                'data_pagamento' => $status == 'pago' ? date('Y-m-d') : null,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $db->update('contas_pagar', $conta_pagar, ['id' => $conta_pagar_rh['conta_pagar_id']]);
        } else {
            // Cria uma nova conta a pagar
            $conta_pagar = [
                'descricao' => 'Pagamento de salário - ' . $funcionario['nome'],
                'valor' => $pagamento['valor'],
                'data_vencimento' => $pagamento['data_pagamento'],
                'data_pagamento' => $status == 'pago' ? date('Y-m-d') : null,
                'categoria' => 'folha_pagamento',
                'fornecedor' => $funcionario['nome'],
                'status' => $status,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $conta_pagar_id = $db->insert('contas_pagar', $conta_pagar);
            
            if ($conta_pagar_id) {
                // Cria a relação entre o pagamento e a conta a pagar
                $db->insert('contas_pagar_rh', [
                    'pagamento_id' => $id,
                    'conta_pagar_id' => $conta_pagar_id
                ]);
            }
        }
        
        // Define a mensagem de sucesso
        if ($status == 'pago') {
            setMensagem('sucesso', 'Pagamento marcado como pago com sucesso.');
        } elseif ($status == 'cancelado') {
            setMensagem('sucesso', 'Pagamento cancelado com sucesso.');
        } else {
            setMensagem('sucesso', 'Status do pagamento atualizado com sucesso.');
        }
    } else {
        setMensagem('erro', 'Erro ao atualizar o status do pagamento.');
    }
} catch (Exception $e) {
    setMensagem('erro', 'Erro ao atualizar o status do pagamento: ' . $e->getMessage());
}

// Redireciona de volta para a página de pagamentos
redirect('pagamentos.php');
exit;
