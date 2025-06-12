-- ============================================================================
-- VERSÃO SIMPLES - ADICIONAR COLUNAS FALTANTES
-- ============================================================================
-- Execute comando por comando no seu phpMyAdmin ou cliente MySQL
-- ============================================================================

-- 1. Adicionar colunas básicas
ALTER TABLE boletos ADD COLUMN multa decimal(5,2) DEFAULT 2.00;
ALTER TABLE boletos ADD COLUMN juros decimal(5,2) DEFAULT 1.00;
ALTER TABLE boletos ADD COLUMN desconto decimal(10,2) DEFAULT 0.00;

-- 2. Adicionar colunas de controle
ALTER TABLE boletos ADD COLUMN ambiente enum('teste','producao') DEFAULT 'teste';
ALTER TABLE boletos ADD COLUMN banco varchar(50) DEFAULT 'itau';
ALTER TABLE boletos ADD COLUMN carteira varchar(10) DEFAULT '109';

-- 3. Adicionar colunas opcionais
ALTER TABLE boletos ADD COLUMN instrucoes text DEFAULT NULL;
ALTER TABLE boletos ADD COLUMN valor_pago decimal(10,2) DEFAULT NULL;
ALTER TABLE boletos ADD COLUMN forma_pagamento varchar(50) DEFAULT NULL;

-- 4. Verificar se deu certo
DESCRIBE boletos;
