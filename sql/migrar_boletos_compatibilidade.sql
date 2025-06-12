-- ============================================================================
-- MIGRAÇÃO DE COMPATIBILIDADE - TABELA BOLETOS
-- ============================================================================
-- Data: 2025-06-11
-- Descrição: Migra a estrutura antiga da tabela boletos para a nova estrutura
-- compatível com o módulo financeiro atual
-- ============================================================================

-- Backup da tabela atual (caso algo dê errado)
CREATE TABLE IF NOT EXISTS `boletos_backup_20250611` AS SELECT * FROM `boletos`;

-- Desabilitar verificação de chaves estrangeiras temporariamente
SET foreign_key_checks = 0;

-- 1. Adicionar as novas colunas necessárias
-- ============================================================================

-- Adicionar coluna 'tipo' (nova nomenclatura)
ALTER TABLE `boletos` 
ADD COLUMN `tipo` enum('mensalidade','polo','avulso','funcionario') NOT NULL DEFAULT 'avulso' 
AFTER `id`;

-- Adicionar coluna 'referencia_id' (substitui entidade_id)
ALTER TABLE `boletos` 
ADD COLUMN `referencia_id` int(11) DEFAULT NULL 
AFTER `tipo`;

-- Adicionar colunas específicas do novo módulo financeiro
ALTER TABLE `boletos` 
ADD COLUMN `instrucoes` text DEFAULT NULL 
AFTER `descricao`;

ALTER TABLE `boletos` 
ADD COLUMN `numero` varchar(20) DEFAULT NULL 
AFTER `endereco`;

ALTER TABLE `boletos` 
ADD COLUMN `complemento` varchar(100) DEFAULT NULL 
AFTER `numero`;

ALTER TABLE `boletos` 
ADD COLUMN `multa` decimal(5,2) DEFAULT 2.00 
AFTER `cep`;

ALTER TABLE `boletos` 
ADD COLUMN `juros` decimal(5,2) DEFAULT 1.00 
AFTER `multa`;

ALTER TABLE `boletos` 
ADD COLUMN `desconto` decimal(10,2) DEFAULT 0.00 
AFTER `juros`;

ALTER TABLE `boletos` 
ADD COLUMN `id_externo` varchar(100) DEFAULT NULL 
AFTER `url_boleto`;

ALTER TABLE `boletos` 
ADD COLUMN `ambiente` enum('teste','producao') DEFAULT 'teste' 
AFTER `id_externo`;

ALTER TABLE `boletos` 
ADD COLUMN `banco` varchar(50) DEFAULT 'itau' 
AFTER `ambiente`;

ALTER TABLE `boletos` 
ADD COLUMN `carteira` varchar(10) DEFAULT '109' 
AFTER `banco`;

ALTER TABLE `boletos` 
ADD COLUMN `valor_pago` decimal(10,2) DEFAULT NULL 
AFTER `data_pagamento`;

ALTER TABLE `boletos` 
ADD COLUMN `forma_pagamento` varchar(50) DEFAULT NULL 
AFTER `valor_pago`;

-- 2. Migrar dados das colunas antigas para as novas
-- ============================================================================

-- Migrar tipo_entidade para tipo
UPDATE `boletos` SET 
  `tipo` = CASE 
    WHEN `tipo_entidade` = 'aluno' THEN 'mensalidade'
    WHEN `tipo_entidade` = 'polo' THEN 'polo' 
    WHEN `tipo_entidade` = 'avulso' THEN 'avulso'
    ELSE 'avulso'
  END;

-- Migrar entidade_id para referencia_id
UPDATE `boletos` SET `referencia_id` = `entidade_id`;

-- Migrar api_ambiente para ambiente (se tiver dados)
UPDATE `boletos` SET 
  `ambiente` = CASE 
    WHEN `api_ambiente` LIKE '%prod%' THEN 'producao'
    WHEN `api_ambiente` LIKE '%test%' THEN 'teste'
    ELSE 'teste'
  END 
WHERE `api_ambiente` IS NOT NULL AND `api_ambiente` != '';

-- 3. Atualizar estrutura de STATUS
-- ============================================================================

-- Atualizar enum de status para incluir 'processando'
ALTER TABLE `boletos` 
MODIFY COLUMN `status` enum('pendente','pago','vencido','cancelado','processando') NOT NULL DEFAULT 'pendente';

-- 4. Ajustar tipos de colunas para compatibilidade
-- ============================================================================

-- Ajustar tamanho do campo cpf_pagador
ALTER TABLE `boletos` 
MODIFY COLUMN `cpf_pagador` varchar(14) NOT NULL;

-- Ajustar tamanho do campo url_boleto
ALTER TABLE `boletos` 
MODIFY COLUMN `url_boleto` varchar(500) DEFAULT NULL;

-- Ajustar campos de data
ALTER TABLE `boletos` 
MODIFY COLUMN `data_emissao` date NOT NULL DEFAULT (CURRENT_DATE);

-- Modificar updated_at para ter ON UPDATE CURRENT_TIMESTAMP
ALTER TABLE `boletos` 
MODIFY COLUMN `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP;

-- Tornar created_at com default CURRENT_TIMESTAMP se não tiver
ALTER TABLE `boletos` 
MODIFY COLUMN `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP;

-- 5. Adicionar índices necessários
-- ============================================================================

-- Adicionar índices para performance
ALTER TABLE `boletos` 
ADD KEY `idx_tipo_referencia` (`tipo`, `referencia_id`);

ALTER TABLE `boletos` 
ADD KEY `idx_vencimento` (`data_vencimento`);

ALTER TABLE `boletos` 
ADD KEY `idx_nosso_numero` (`nosso_numero`);

ALTER TABLE `boletos` 
ADD KEY `idx_cpf_pagador` (`cpf_pagador`);

-- 6. Remover campos desnecessários (OPCIONAL - descomente se desejar)
-- ============================================================================

-- Para preservar dados, vamos manter as colunas antigas comentadas
-- Descomente as linhas abaixo apenas se tiver certeza de que não precisa dos dados antigos:

-- ALTER TABLE `boletos` DROP COLUMN `tipo_entidade`;
-- ALTER TABLE `boletos` DROP COLUMN `entidade_id`;
-- ALTER TABLE `boletos` DROP COLUMN `grupo_boletos`;
-- ALTER TABLE `boletos` DROP COLUMN `mensalidade_id`;
-- ALTER TABLE `boletos` DROP COLUMN `data_cancelamento`;
-- ALTER TABLE `boletos` DROP COLUMN `api_ambiente`;
-- ALTER TABLE `boletos` DROP COLUMN `api_tipo`;
-- ALTER TABLE `boletos` DROP COLUMN `api_token_id`;
-- ALTER TABLE `boletos` DROP COLUMN `api_request_data`;

-- 7. Inserir dados de configuração do módulo financeiro
-- ============================================================================

-- Criar tabela de configurações se não existir
CREATE TABLE IF NOT EXISTS `configuracoes_financeiras` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chave` varchar(100) NOT NULL,
  `valor` text DEFAULT NULL,
  `descricao` varchar(255) DEFAULT NULL,
  `tipo` enum('texto','numero','boolean','json') DEFAULT 'texto',
  `grupo` varchar(50) DEFAULT 'geral',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `chave` (`chave`),
  KEY `idx_grupo` (`grupo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir configurações básicas
INSERT IGNORE INTO `configuracoes_financeiras` (`chave`, `valor`, `descricao`, `tipo`, `grupo`) VALUES
('modulo_ativo', '1', 'Indica se o módulo financeiro está ativo', 'boolean', 'sistema'),
('data_instalacao', NOW(), 'Data de instalação do módulo financeiro', 'texto', 'sistema'),
('versao_modulo', '2.1', 'Versão atual do módulo financeiro', 'texto', 'sistema'),
('conta_padrao_id', '1', 'ID da conta bancária padrão', 'numero', 'configuracao'),
('dias_vencimento_padrao', '30', 'Dias padrão para vencimento de boletos', 'numero', 'boletos'),
('multa_padrao', '2.00', 'Percentual padrão de multa por atraso', 'numero', 'boletos'),
('juros_padrao', '1.00', 'Percentual padrão de juros por mês', 'numero', 'boletos'),
('banco_padrao', 'itau', 'Banco padrão para geração de boletos', 'texto', 'boletos'),
('carteira_padrao', '109', 'Carteira padrão para boletos', 'texto', 'boletos'),
('ambiente_boleto', 'teste', 'Ambiente para geração de boletos (teste/producao)', 'texto', 'boletos');

-- Reabilitar verificação de chaves estrangeiras
SET foreign_key_checks = 1;

-- ============================================================================
-- FINALIZAÇÃO
-- ============================================================================

-- Mensagem de sucesso
SELECT 'MIGRAÇÃO DE COMPATIBILIDADE CONCLUÍDA COM SUCESSO!' as STATUS,
       'Tabela boletos migrada para nova estrutura.' as DETALHES,
       NOW() as DATA_MIGRACAO,
       'v2.1' as VERSAO;

-- Verificação dos dados migrados
SELECT 
    'VERIFICAÇÃO PÓS-MIGRAÇÃO:' as INFO,
    COUNT(*) as TOTAL_BOLETOS_MIGRADOS
FROM boletos;

-- Status final da tabela
SELECT 
    'NOVA ESTRUTURA BOLETOS:' as INFO,
    COLUMN_NAME as COLUNA,
    DATA_TYPE as TIPO,
    IS_NULLABLE as PERMITE_NULL
FROM information_schema.columns 
WHERE table_schema = DATABASE() 
AND table_name = 'boletos'
AND COLUMN_NAME IN ('tipo', 'referencia_id', 'ambiente', 'banco', 'carteira')
ORDER BY ORDINAL_POSITION;
