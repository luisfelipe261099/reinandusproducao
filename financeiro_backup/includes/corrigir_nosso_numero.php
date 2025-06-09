<?php
/**
 * Funções para corrigir o formato do nosso número para a API do Itaú
 */

/**
 * Corrige o formato do nosso número para o padrão exigido pela API do Itaú
 *
 * @param string $nosso_numero Nosso número original
 * @return string Nosso número corrigido
 */
function corrigirFormatoNossoNumero($nosso_numero) {
    // Remove caracteres especiais (barra, traço, espaços)
    $limpo = preg_replace('/[\/\-\s]/', '', $nosso_numero);

    // Log para depuração
    error_log("Nosso número original: $nosso_numero");

    // Verifica se o nosso número está no formato 109/XXXXXXXX-Y ou similar
    if (preg_match('/^109(\d{8})(\d?)$/', $limpo, $matches)) {
        // Extrai apenas os 8 dígitos do meio (sem carteira e sem DV)
        $numero_base = $matches[1];
        error_log("Nosso número extraído do formato 109/XXXXXXXX-Y: $numero_base");
        return $numero_base;
    }

    // Remove todos os caracteres não numéricos para garantir
    $nosso_numero_limpo = preg_replace('/[^0-9]/', '', $nosso_numero);

    // Se começar com 109 (código da carteira), remove
    if (strpos($nosso_numero_limpo, '109') === 0) {
        $nosso_numero_limpo = substr($nosso_numero_limpo, 3);
        error_log("Removido prefixo 109: $nosso_numero_limpo");
    }

    // Se tiver 9 dígitos e o último for um dígito verificador, remove-o
    if (strlen($nosso_numero_limpo) == 9) {
        $nosso_numero_limpo = substr($nosso_numero_limpo, 0, 8);
        error_log("Removido dígito verificador: $nosso_numero_limpo");
    }

    // Para a carteira 109 do Itaú, o nosso número deve ter 8 dígitos
    // Se tiver mais de 8 dígitos, pega os 8 últimos
    if (strlen($nosso_numero_limpo) > 8) {
        $nosso_numero_limpo = substr($nosso_numero_limpo, -8);
        error_log("Truncado para 8 dígitos: $nosso_numero_limpo");
    }

    // Se tiver menos de 8 dígitos, completa com zeros à esquerda
    $resultado = str_pad($nosso_numero_limpo, 8, '0', STR_PAD_LEFT);

    // Log do resultado final
    error_log("Nosso número formatado final: $resultado");

    return $resultado;
}

/**
 * Verifica se o nosso número está no formato correto para a API do Itaú
 *
 * @param string $nosso_numero Nosso número a ser verificado
 * @return bool True se o formato estiver correto, false caso contrário
 */
function verificarFormatoNossoNumero($nosso_numero) {
    // Remove caracteres não numéricos
    $nosso_numero_limpo = preg_replace('/[^0-9]/', '', $nosso_numero);

    // Para a carteira 109 do Itaú, o nosso número deve ter 8 dígitos
    return strlen($nosso_numero_limpo) == 8;
}

/**
 * Extrai o nosso número de 8 dígitos de um nosso número composto
 *
 * @param string $nosso_numero Nosso número composto (pode incluir carteira, agência, etc.)
 * @return string Nosso número de 8 dígitos
 */
function extrairNossoNumero8Digitos($nosso_numero) {
    // Remove caracteres não numéricos
    $nosso_numero_limpo = preg_replace('/[^0-9]/', '', $nosso_numero);

    // Se começar com 109 (código da carteira), remove
    if (strpos($nosso_numero_limpo, '109') === 0) {
        $nosso_numero_limpo = substr($nosso_numero_limpo, 3);
    }

    // Pega os 8 últimos dígitos
    if (strlen($nosso_numero_limpo) > 8) {
        return substr($nosso_numero_limpo, -8);
    }

    // Se tiver menos de 8 dígitos, completa com zeros à esquerda
    return str_pad($nosso_numero_limpo, 8, '0', STR_PAD_LEFT);
}

/**
 * Formata o nosso número no padrão visual do Itaú (109/XXXXXXXX-Y)
 *
 * @param string $nosso_numero Nosso número (8 dígitos)
 * @return string Nosso número formatado
 */
function formatarNossoNumeroItau($nosso_numero) {
    // Extrai os 8 dígitos principais
    $numero_base = extrairNossoNumero8Digitos($nosso_numero);

    // Calcula o dígito verificador
    $dv = calcularDigitoVerificadorItau($numero_base);

    // Formata no padrão 109/XXXXXXXX-Y
    return "109/{$numero_base}-{$dv}";
}

/**
 * Calcula o dígito verificador do nosso número no padrão Itaú
 *
 * @param string $nosso_numero Nosso número (8 dígitos)
 * @return int Dígito verificador
 */
function calcularDigitoVerificadorItau($nosso_numero) {
    // Garante que o nosso número tenha 8 dígitos
    $nosso_numero = str_pad($nosso_numero, 8, '0', STR_PAD_LEFT);

    // Agência e conta (fixos para a Faciência)
    $agencia = '0978';
    $conta = '27155';

    // Carteira
    $carteira = '109';

    // Concatena agência + conta + carteira + nosso número
    $numero_completo = $agencia . $conta . $carteira . $nosso_numero;

    // Pesos para o cálculo do dígito verificador
    $pesos = [2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1, 2];

    // Calcula a soma ponderada
    $soma = 0;
    for ($i = 0; $i < strlen($numero_completo); $i++) {
        $produto = (int)$numero_completo[$i] * $pesos[$i];
        $soma += ($produto > 9) ? intval($produto / 10) + ($produto % 10) : $produto;
    }

    // Calcula o dígito verificador
    $resto = $soma % 10;
    $dv = ($resto == 0) ? 0 : 10 - $resto;

    return $dv;
}
?>
