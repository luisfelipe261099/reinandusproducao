-- ============================================================================
-- CORREÇÃO URGENTE - BOLETO LUIS FELIPE DA SILVA MACHADO
-- ============================================================================
-- Execute este script no seu banco de dados para corrigir o problema
-- ============================================================================

-- 1. PRIMEIRO: Adicionar as colunas que estão faltando (ignore erros se já existirem)
ALTER TABLE boletos ADD COLUMN multa decimal(5,2) DEFAULT 2.00;
ALTER TABLE boletos ADD COLUMN juros decimal(5,2) DEFAULT 1.00;
ALTER TABLE boletos ADD COLUMN desconto decimal(10,2) DEFAULT 0.00;
ALTER TABLE boletos ADD COLUMN ambiente enum('teste','producao') DEFAULT 'teste';
ALTER TABLE boletos ADD COLUMN banco varchar(50) DEFAULT 'itau';
ALTER TABLE boletos ADD COLUMN carteira varchar(10) DEFAULT '109';
ALTER TABLE boletos ADD COLUMN instrucoes text DEFAULT NULL;
ALTER TABLE boletos ADD COLUMN valor_pago decimal(10,2) DEFAULT NULL;
ALTER TABLE boletos ADD COLUMN forma_pagamento varchar(50) DEFAULT NULL;

-- 2. VERIFICAR: Encontrar o boleto do LUIS FELIPE
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
    url_boleto
FROM boletos 
WHERE cpf_pagador = '083.790.709-84' 
   OR nome_pagador LIKE '%LUIS FELIPE%'
ORDER BY created_at DESC;

-- 3. ATUALIZAR: Se o boleto foi encontrado, atualize com informações padrão
-- Substitua {ID_DO_BOLETO} pelo ID real encontrado na query acima
/*
UPDATE boletos SET 
    ambiente = 'producao',
    banco = 'itau',
    carteira = '109',
    multa = 2.00,
    juros = 1.00,
    desconto = 0.00
WHERE id = {ID_DO_BOLETO};
*/

-- 4. VERIFICAÇÃO FINAL
SELECT 'ESTRUTURA CORRIGIDA!' as STATUS;
DESCRIBE boletos;
