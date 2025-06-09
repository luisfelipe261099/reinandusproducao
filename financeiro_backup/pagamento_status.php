<?php
/**
 * Página para alterar o status de um pagamento
 */

// Inclui os arquivos necessários
require_once __DIR__ . '/../includes/init.php';

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
    // Inicia uma transação para garantir a integridade dos dados
    $db->beginTransaction();

    // Atualiza o pagamento
    $result = $db->update('pagamentos', $dados, ['id' => $id]);

    if ($result) {
        // Busca o funcionário
        $funcionario = $db->fetchOne("SELECT * FROM funcionarios WHERE id = ?", [$pagamento['funcionario_id']]);

        if (!$funcionario) {
            throw new Exception('Funcionário não encontrado.');
        }

        // Determina o tipo de pagamento com base no tipo do pagamento
        $tipo_pagamento = 'salário';
        if (isset($pagamento['tipo'])) {
            switch ($pagamento['tipo']) {
                case '13_salario':
                    $tipo_pagamento = '13º salário';
                    break;
                case 'ferias':
                    $tipo_pagamento = 'férias';
                    break;
                case 'adiantamento':
                    $tipo_pagamento = 'adiantamento';
                    break;
                case 'bonus':
                    $tipo_pagamento = 'bônus';
                    break;
            }
        }

        // Verifica se existe uma conta a pagar relacionada
        $conta_pagar_rh = $db->fetchOne("SELECT cpr.*, cp.transacao_id
                                       FROM contas_pagar_rh cpr
                                       JOIN contas_pagar cp ON cpr.conta_pagar_id = cp.id
                                       WHERE cpr.pagamento_id = ?", [$id]);

        // Prepara os dados da conta a pagar
        $conta_pagar = [
            'status' => $status,
            'data_pagamento' => $status == 'pago' ? date('Y-m-d') : null,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if ($conta_pagar_rh) {
            // Atualiza a conta a pagar existente
            $db->update('contas_pagar', $conta_pagar, ['id' => $conta_pagar_rh['conta_pagar_id']]);
            $conta_pagar_id = $conta_pagar_rh['conta_pagar_id'];
            $transacao_id = $conta_pagar_rh['transacao_id'];
        } else {
            // Cria uma nova conta a pagar
            $conta_pagar_completo = [
                'descricao' => 'Pagamento de ' . $tipo_pagamento . ' - ' . $funcionario['nome'],
                'valor' => $pagamento['valor'],
                'data_vencimento' => $pagamento['data_pagamento'],
                'data_pagamento' => $status == 'pago' ? date('Y-m-d') : null,
                'categoria' => 'folha_pagamento',
                'fornecedor' => $funcionario['nome'],
                'forma_pagamento' => $pagamento['forma_pagamento'] ?? 'transferencia',
                'status' => $status,
                'observacoes' => $pagamento['observacoes'] ?? 'Pagamento gerado pelo módulo de RH',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $conta_pagar_id = $db->insert('contas_pagar', $conta_pagar_completo);

            if (!$conta_pagar_id) {
                throw new Exception('Erro ao criar conta a pagar.');
            }

            // Cria a relação entre o pagamento e a conta a pagar
            $db->insert('contas_pagar_rh', [
                'pagamento_id' => $id,
                'conta_pagar_id' => $conta_pagar_id,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            $transacao_id = null;
        }

        // Se o status for alterado para pago, registra a transação financeira
        if ($status == 'pago' && $pagamento['status'] != 'pago') {
            // Busca a conta bancária padrão ou a primeira conta ativa
            $conta_bancaria = $db->fetchOne("SELECT * FROM contas_bancarias WHERE status = 'ativo' ORDER BY id LIMIT 1");
            $conta_id = $conta_bancaria ? $conta_bancaria['id'] : null;

            // Verifica se a tabela de transações existe
            $tabela_transacoes_existe = $db->fetchOne("SHOW TABLES LIKE 'transacoes'");
            if ($tabela_transacoes_existe) {
                // Se já existe uma transação, atualiza
                if ($transacao_id) {
                    $transacao_update = [
                        'status' => 'efetivada',
                        'data_transacao' => date('Y-m-d'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                    $db->update('transacoes', $transacao_update, ['id' => $transacao_id]);
                } else {
                    // Cria uma nova transação
                    $transacao = [
                        'tipo' => 'despesa',
                        'descricao' => 'Pagamento de ' . $tipo_pagamento . ' - ' . $funcionario['nome'],
                        'valor' => $pagamento['valor'],
                        'data_transacao' => date('Y-m-d'),
                        'categoria_id' => null,
                        'conta_id' => $conta_id,
                        'forma_pagamento' => $pagamento['forma_pagamento'] ?? 'transferencia',
                        'status' => 'efetivada',
                        'observacoes' => $pagamento['observacoes'] ?? 'Pagamento gerado pelo módulo de RH',
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];

                    $transacao_id = $db->insert('transacoes', $transacao);

                    // Atualiza a conta a pagar com o ID da transação
                    if ($transacao_id) {
                        $db->update('contas_pagar', ['transacao_id' => $transacao_id], ['id' => $conta_pagar_id]);
                    }
                }

                // Atualiza o saldo da conta bancária, se existir
                if ($conta_id) {
                    // Subtrai o valor do saldo atual
                    $db->query("UPDATE contas_bancarias SET saldo_atual = saldo_atual - ?, data_saldo = ?, updated_at = ? WHERE id = ?", [
                        $pagamento['valor'],
                        date('Y-m-d'),
                        date('Y-m-d H:i:s'),
                        $conta_id
                    ]);
                }
            }
        }
        // Se o status for alterado para cancelado, cancela a transação financeira
        else if ($status == 'cancelado' && $pagamento['status'] != 'cancelado' && $transacao_id) {
            $tabela_transacoes_existe = $db->fetchOne("SHOW TABLES LIKE 'transacoes'");
            if ($tabela_transacoes_existe) {
                // Busca a transação
                $transacao = $db->fetchOne("SELECT * FROM transacoes WHERE id = ?", [$transacao_id]);

                if ($transacao) {
                    // Atualiza o status da transação
                    $db->update('transacoes', [
                        'status' => 'cancelada',
                        'updated_at' => date('Y-m-d H:i:s')
                    ], ['id' => $transacao_id]);

                    // Se a transação estava efetivada, reverte o saldo da conta
                    if ($transacao['status'] == 'efetivada' && $transacao['conta_id']) {
                        // Adiciona o valor de volta ao saldo atual
                        $db->query("UPDATE contas_bancarias SET saldo_atual = saldo_atual + ?, data_saldo = ?, updated_at = ? WHERE id = ?", [
                            $transacao['valor'],
                            date('Y-m-d'),
                            date('Y-m-d H:i:s'),
                            $transacao['conta_id']
                        ]);
                    }
                }
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

        // Confirma a transação
        $db->commit();
    } else {
        // Reverte a transação em caso de erro
        $db->rollBack();
        setMensagem('erro', 'Erro ao atualizar o status do pagamento.');
    }
} catch (Exception $e) {
    // Reverte a transação em caso de exceção
    $db->rollBack();
    setMensagem('erro', 'Erro ao atualizar o status do pagamento: ' . $e->getMessage());
}

// Redireciona de volta para a página de pagamentos
redirect('pagamentos.php');
exit;
