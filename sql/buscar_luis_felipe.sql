-- ============================================================================
-- BUSCAR BOLETO DO LUIS FELIPE (EXECUTE ESTE COMANDO AGORA)
-- ============================================================================

-- Buscar o boleto do LUIS FELIPE
SELECT 
    id,
    nome_pagador,
    cpf_pagador,
    valor,
    data_vencimento,
    status,
    nosso_numero,
    linha_digitavel,
    codigo_barras,
    url_boleto,
    created_at
FROM boletos 
WHERE cpf_pagador = '083.790.709-84' 
   OR cpf_pagador = '08379070984'
   OR nome_pagador LIKE '%LUIS FELIPE%'
ORDER BY created_at DESC;

-- Se encontrou o boleto, copie o ID e execute este UPDATE (substitua {ID} pelo ID real):
-- UPDATE boletos SET 
--     ambiente = 'producao',
--     banco = 'itau',
--     carteira = '109',
--     multa = 2.00,
--     juros = 1.00,
--     desconto = 0.00
-- WHERE id = {ID_DO_BOLETO_AQUI};
