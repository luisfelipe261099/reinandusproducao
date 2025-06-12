-- ============================================================================
-- ADICIONAR COLUNAS BOLETOS - VERSÃO SIMPLES
-- ============================================================================
-- Execute este script no seu banco de dados
-- Se uma coluna já existir, o comando vai dar erro mas pode ignorar
-- ============================================================================

-- Adicionar colunas uma por uma (ignore erros se já existirem)

ALTER TABLE boletos ADD COLUMN multa decimal(5,2) DEFAULT 2.00;

ALTER TABLE boletos ADD COLUMN juros decimal(5,2) DEFAULT 1.00;

ALTER TABLE boletos ADD COLUMN desconto decimal(10,2) DEFAULT 0.00;

ALTER TABLE boletos ADD COLUMN ambiente enum('teste','producao') DEFAULT 'teste';

ALTER TABLE boletos ADD COLUMN banco varchar(50) DEFAULT 'itau';

ALTER TABLE boletos ADD COLUMN carteira varchar(10) DEFAULT '109';

ALTER TABLE boletos ADD COLUMN instrucoes text DEFAULT NULL;

ALTER TABLE boletos ADD COLUMN valor_pago decimal(10,2) DEFAULT NULL;

ALTER TABLE boletos ADD COLUMN forma_pagamento varchar(50) DEFAULT NULL;

ALTER TABLE boletos ADD COLUMN tipo enum('mensalidade','polo','avulso','funcionario') DEFAULT 'avulso';

ALTER TABLE boletos ADD COLUMN referencia_id int(11) DEFAULT NULL;

ALTER TABLE boletos ADD COLUMN id_externo varchar(100) DEFAULT NULL;

ALTER TABLE boletos ADD COLUMN numero varchar(20) DEFAULT NULL;

ALTER TABLE boletos ADD COLUMN complemento varchar(100) DEFAULT NULL;

-- Atualizar enum de status se necessário
ALTER TABLE boletos MODIFY COLUMN status enum('pendente','pago','vencido','cancelado','processando') DEFAULT 'pendente';

-- Criar índices (ignore erros se já existirem)
ALTER TABLE boletos ADD INDEX idx_vencimento (data_vencimento);
ALTER TABLE boletos ADD INDEX idx_nosso_numero (nosso_numero);
ALTER TABLE boletos ADD INDEX idx_cpf_pagador (cpf_pagador);

-- Verificação final - isso vai mostrar as colunas da tabela
DESCRIBE boletos;
