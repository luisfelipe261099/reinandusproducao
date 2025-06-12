-- ============================================================================
-- ADICIONAR COLUNAS FALTANTES - TABELA BOLETOS
-- ============================================================================
-- Execute este script no seu banco de dados para adicionar as colunas que estão faltando
-- ============================================================================

-- Verificar se as colunas já existem antes de adicionar
SET @sql = '';

-- Adicionar coluna 'multa' se não existir
SELECT COUNT(*) INTO @exists FROM information_schema.columns 
WHERE table_schema = DATABASE() AND table_name = 'boletos' AND column_name = 'multa';
SET @sql = IF(@exists = 0, 'ALTER TABLE boletos ADD COLUMN multa decimal(5,2) DEFAULT 2.00;', '');
PREPARE stmt FROM @sql; 
EXECUTE stmt; 
DEALLOCATE PREPARE stmt;

-- Adicionar coluna 'juros' se não existir
SELECT COUNT(*) INTO @exists FROM information_schema.columns 
WHERE table_schema = DATABASE() AND table_name = 'boletos' AND column_name = 'juros';
SET @sql = IF(@exists = 0, 'ALTER TABLE boletos ADD COLUMN juros decimal(5,2) DEFAULT 1.00;', '');
PREPARE stmt FROM @sql; 
EXECUTE stmt; 
DEALLOCATE PREPARE stmt;

-- Adicionar coluna 'desconto' se não existir
SELECT COUNT(*) INTO @exists FROM information_schema.columns 
WHERE table_schema = DATABASE() AND table_name = 'boletos' AND column_name = 'desconto';
SET @sql = IF(@exists = 0, 'ALTER TABLE boletos ADD COLUMN desconto decimal(10,2) DEFAULT 0.00;', '');
PREPARE stmt FROM @sql; 
EXECUTE stmt; 
DEALLOCATE PREPARE stmt;

-- Adicionar coluna 'ambiente' se não existir
SELECT COUNT(*) INTO @exists FROM information_schema.columns 
WHERE table_schema = DATABASE() AND table_name = 'boletos' AND column_name = 'ambiente';
SET @sql = IF(@exists = 0, 'ALTER TABLE boletos ADD COLUMN ambiente enum(\'teste\',\'producao\') DEFAULT \'teste\';', '');
PREPARE stmt FROM @sql; 
EXECUTE stmt; 
DEALLOCATE PREPARE stmt;

-- Adicionar coluna 'banco' se não existir
SELECT COUNT(*) INTO @exists FROM information_schema.columns 
WHERE table_schema = DATABASE() AND table_name = 'boletos' AND column_name = 'banco';
SET @sql = IF(@exists = 0, 'ALTER TABLE boletos ADD COLUMN banco varchar(50) DEFAULT \'itau\';', '');
PREPARE stmt FROM @sql; 
EXECUTE stmt; 
DEALLOCATE PREPARE stmt;

-- Adicionar coluna 'carteira' se não existir
SELECT COUNT(*) INTO @exists FROM information_schema.columns 
WHERE table_schema = DATABASE() AND table_name = 'boletos' AND column_name = 'carteira';
SET @sql = IF(@exists = 0, 'ALTER TABLE boletos ADD COLUMN carteira varchar(10) DEFAULT \'109\';', '');
PREPARE stmt FROM @sql; 
EXECUTE stmt; 
DEALLOCATE PREPARE stmt;

-- Adicionar coluna 'instrucoes' se não existir
SELECT COUNT(*) INTO @exists FROM information_schema.columns 
WHERE table_schema = DATABASE() AND table_name = 'boletos' AND column_name = 'instrucoes';
SET @sql = IF(@exists = 0, 'ALTER TABLE boletos ADD COLUMN instrucoes text DEFAULT NULL;', '');
PREPARE stmt FROM @sql; 
EXECUTE stmt; 
DEALLOCATE PREPARE stmt;

-- Adicionar coluna 'valor_pago' se não existir
SELECT COUNT(*) INTO @exists FROM information_schema.columns 
WHERE table_schema = DATABASE() AND table_name = 'boletos' AND column_name = 'valor_pago';
SET @sql = IF(@exists = 0, 'ALTER TABLE boletos ADD COLUMN valor_pago decimal(10,2) DEFAULT NULL;', '');
PREPARE stmt FROM @sql; 
EXECUTE stmt; 
DEALLOCATE PREPARE stmt;

-- Adicionar coluna 'forma_pagamento' se não existir
SELECT COUNT(*) INTO @exists FROM information_schema.columns 
WHERE table_schema = DATABASE() AND table_name = 'boletos' AND column_name = 'forma_pagamento';
SET @sql = IF(@exists = 0, 'ALTER TABLE boletos ADD COLUMN forma_pagamento varchar(50) DEFAULT NULL;', '');
PREPARE stmt FROM @sql; 
EXECUTE stmt; 
DEALLOCATE PREPARE stmt;

-- Verificação final
SELECT 'COLUNAS ADICIONADAS COM SUCESSO!' as STATUS,
       NOW() as DATA_EXECUCAO;

-- Verificar estrutura final
SELECT 
    COLUMN_NAME as COLUNA,
    DATA_TYPE as TIPO,
    IS_NULLABLE as PERMITE_NULL,
    COLUMN_DEFAULT as VALOR_PADRAO
FROM information_schema.columns 
WHERE table_schema = DATABASE() 
AND table_name = 'boletos'
AND COLUMN_NAME IN ('multa', 'juros', 'desconto', 'ambiente', 'banco', 'carteira', 'instrucoes', 'valor_pago', 'forma_pagamento')
ORDER BY ORDINAL_POSITION;
