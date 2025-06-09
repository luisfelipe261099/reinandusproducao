<?php
/**
 * Funções específicas para o módulo de Recursos Humanos
 */

/**
 * Calcula o valor do INSS com base no salário
 * @param float $salario Salário bruto
 * @return float Valor do INSS
 */
function calcularINSS($salario) {
    // Tabela INSS 2023
    if ($salario <= 1320.00) {
        return $salario * 0.075;
    } elseif ($salario <= 2571.29) {
        return $salario * 0.09;
    } elseif ($salario <= 3856.94) {
        return $salario * 0.12;
    } elseif ($salario <= 7507.49) {
        return $salario * 0.14;
    } else {
        return 876.97; // Teto do INSS
    }
}

/**
 * Calcula o valor do IRRF com base no salário
 * @param float $salario Salário bruto
 * @param float $inss Valor do INSS
 * @param int $dependentes Número de dependentes
 * @return float Valor do IRRF
 */
function calcularIRRF($salario, $inss, $dependentes = 0) {
    // Dedução por dependente
    $deducao_dependente = 189.59 * $dependentes;
    
    // Base de cálculo
    $base_calculo = $salario - $inss - $deducao_dependente;
    
    // Tabela IRRF 2023
    if ($base_calculo <= 2112.00) {
        return 0;
    } elseif ($base_calculo <= 2826.65) {
        return ($base_calculo * 0.075) - 158.40;
    } elseif ($base_calculo <= 3751.05) {
        return ($base_calculo * 0.15) - 370.40;
    } elseif ($base_calculo <= 4664.68) {
        return ($base_calculo * 0.225) - 651.73;
    } else {
        return ($base_calculo * 0.275) - 884.96;
    }
}

/**
 * Calcula o valor do FGTS com base no salário
 * @param float $salario Salário bruto
 * @return float Valor do FGTS
 */
function calcularFGTS($salario) {
    return $salario * 0.08;
}

/**
 * Gera um item para a folha de pagamento
 * @param array $funcionario Dados do funcionário
 * @param array $extras Valores extras (outros proventos, outros descontos)
 * @return array Item da folha de pagamento
 */
function gerarItemFolha($funcionario, $extras = []) {
    $salario_base = $funcionario['salario'];
    $inss = calcularINSS($salario_base);
    $irrf = calcularIRRF($salario_base, $inss, $funcionario['dependentes'] ?? 0);
    $fgts = calcularFGTS($salario_base);
    
    $outros_proventos = $extras['outros_proventos'] ?? 0;
    $outros_descontos = $extras['outros_descontos'] ?? 0;
    
    $valor_liquido = $salario_base - $inss - $irrf + $outros_proventos - $outros_descontos;
    
    return [
        'funcionario_id' => $funcionario['id'],
        'salario_base' => $salario_base,
        'inss' => $inss,
        'irrf' => $irrf,
        'fgts' => $fgts,
        'outros_proventos' => $outros_proventos,
        'outros_descontos' => $outros_descontos,
        'valor_liquido' => $valor_liquido
    ];
}

/**
 * Cria uma conta a pagar para um pagamento de funcionário
 * @param object $db Objeto de conexão com o banco de dados
 * @param array $pagamento Dados do pagamento
 * @param array $funcionario Dados do funcionário
 * @return int|bool ID da conta a pagar ou false em caso de erro
 */
function criarContaPagarFuncionario($db, $pagamento, $funcionario) {
    try {
        // Formata a descrição da conta
        $descricao = "Pagamento de " . ucfirst($pagamento['tipo']) . " - " . $funcionario['nome'];
        
        // Dados da conta a pagar
        $conta_pagar = [
            'descricao' => $descricao,
            'valor' => $pagamento['valor'],
            'data_vencimento' => $pagamento['data_pagamento'],
            'data_pagamento' => $pagamento['status'] == 'pago' ? $pagamento['data_pagamento'] : null,
            'categoria' => 'folha_pagamento',
            'fornecedor' => $funcionario['nome'],
            'forma_pagamento' => $pagamento['forma_pagamento'],
            'status' => $pagamento['status'],
            'observacoes' => $pagamento['observacoes'],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Insere a conta a pagar
        $conta_pagar_id = $db->insert('contas_pagar', $conta_pagar);
        
        if ($conta_pagar_id) {
            // Cria a relação entre o pagamento e a conta a pagar
            $db->insert('contas_pagar_rh', [
                'pagamento_id' => $pagamento['id'],
                'conta_pagar_id' => $conta_pagar_id
            ]);
            
            return $conta_pagar_id;
        }
        
        return false;
    } catch (Exception $e) {
        error_log('Erro ao criar conta a pagar para funcionário: ' . $e->getMessage());
        return false;
    }
}

/**
 * Atualiza uma conta a pagar relacionada a um pagamento de funcionário
 * @param object $db Objeto de conexão com o banco de dados
 * @param array $pagamento Dados do pagamento
 * @param int $conta_pagar_id ID da conta a pagar
 * @return bool Sucesso ou falha
 */
function atualizarContaPagarFuncionario($db, $pagamento, $conta_pagar_id) {
    try {
        // Dados da conta a pagar
        $conta_pagar = [
            'valor' => $pagamento['valor'],
            'data_vencimento' => $pagamento['data_pagamento'],
            'data_pagamento' => $pagamento['status'] == 'pago' ? $pagamento['data_pagamento'] : null,
            'forma_pagamento' => $pagamento['forma_pagamento'],
            'status' => $pagamento['status'],
            'observacoes' => $pagamento['observacoes'],
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Atualiza a conta a pagar
        return $db->update('contas_pagar', $conta_pagar, ['id' => $conta_pagar_id]);
    } catch (Exception $e) {
        error_log('Erro ao atualizar conta a pagar para funcionário: ' . $e->getMessage());
        return false;
    }
}
