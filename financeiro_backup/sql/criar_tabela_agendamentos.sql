-- Tabela para agendamentos de pagamentos
CREATE TABLE IF NOT EXISTS `agendamentos_pagamentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `funcionario_id` int(11) NOT NULL,
  `tipo` enum('salario','adiantamento','bonus','ferias','13_salario','outros') NOT NULL DEFAULT 'salario',
  `valor` decimal(10,2) NOT NULL,
  `dia_vencimento` int(11) NOT NULL COMMENT 'Dia do mês para pagamento',
  `forma_pagamento` enum('pix','transferencia','cheque','dinheiro') NOT NULL DEFAULT 'transferencia',
  `status` enum('ativo','inativo') NOT NULL DEFAULT 'ativo',
  `observacoes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `funcionario_id` (`funcionario_id`),
  CONSTRAINT `agendamentos_pagamentos_ibfk_1` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Adicionar campos adicionais à tabela funcionarios se não existirem
ALTER TABLE `funcionarios` 
  ADD COLUMN IF NOT EXISTS `rg` varchar(20) DEFAULT NULL AFTER `cpf`,
  ADD COLUMN IF NOT EXISTS `data_nascimento` date DEFAULT NULL AFTER `rg`,
  ADD COLUMN IF NOT EXISTS `data_demissao` date DEFAULT NULL AFTER `data_admissao`,
  ADD COLUMN IF NOT EXISTS `banco` varchar(100) DEFAULT NULL AFTER `status`,
  ADD COLUMN IF NOT EXISTS `agencia` varchar(20) DEFAULT NULL AFTER `banco`,
  ADD COLUMN IF NOT EXISTS `conta` varchar(20) DEFAULT NULL AFTER `agencia`,
  ADD COLUMN IF NOT EXISTS `tipo_conta` varchar(20) DEFAULT NULL AFTER `conta`,
  ADD COLUMN IF NOT EXISTS `pix` varchar(100) DEFAULT NULL AFTER `tipo_conta`,
  ADD COLUMN IF NOT EXISTS `email` varchar(255) DEFAULT NULL AFTER `pix`,
  ADD COLUMN IF NOT EXISTS `telefone` varchar(20) DEFAULT NULL AFTER `email`,
  ADD COLUMN IF NOT EXISTS `dia_pagamento` int(11) DEFAULT NULL AFTER `telefone`,
  ADD COLUMN IF NOT EXISTS `forma_pagamento` varchar(20) DEFAULT NULL AFTER `dia_pagamento`,
  ADD COLUMN IF NOT EXISTS `gerar_pagamento_automatico` tinyint(1) NOT NULL DEFAULT 0 AFTER `forma_pagamento`,
  ADD COLUMN IF NOT EXISTS `observacoes` text DEFAULT NULL AFTER `gerar_pagamento_automatico`,
  MODIFY COLUMN IF EXISTS `status` enum('ativo','inativo','afastado','ferias') NOT NULL DEFAULT 'ativo';

-- Adicionar categoria 'folha_pagamento' à tabela contas_pagar se não existir
-- (Isso depende da estrutura da tabela contas_pagar, ajuste conforme necessário)
-- ALTER TABLE `contas_pagar` MODIFY COLUMN `categoria` enum('fornecedor','servico','imposto','folha_pagamento','outros') NOT NULL DEFAULT 'outros';
