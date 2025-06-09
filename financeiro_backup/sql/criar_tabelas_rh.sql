-- Tabela de funcionários
CREATE TABLE IF NOT EXISTS `funcionarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `cpf` varchar(14) NOT NULL,
  `rg` varchar(20) DEFAULT NULL,
  `data_nascimento` date DEFAULT NULL,
  `data_admissao` date NOT NULL,
  `data_demissao` date DEFAULT NULL,
  `cargo` varchar(100) NOT NULL,
  `departamento` varchar(100) DEFAULT NULL,
  `salario` decimal(10,2) NOT NULL,
  `banco` varchar(100) DEFAULT NULL,
  `agencia` varchar(20) DEFAULT NULL,
  `conta` varchar(20) DEFAULT NULL,
  `tipo_conta` varchar(20) DEFAULT NULL,
  `pix` varchar(100) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `endereco` text DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `estado` varchar(2) DEFAULT NULL,
  `cep` varchar(10) DEFAULT NULL,
  `status` enum('ativo','inativo','afastado','ferias') NOT NULL DEFAULT 'ativo',
  `observacoes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `cpf` (`cpf`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de pagamentos
CREATE TABLE IF NOT EXISTS `pagamentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `funcionario_id` int(11) NOT NULL,
  `tipo` enum('salario','adiantamento','bonus','ferias','13_salario','outros') NOT NULL DEFAULT 'salario',
  `valor` decimal(10,2) NOT NULL,
  `data_pagamento` date NOT NULL,
  `data_competencia` date NOT NULL,
  `forma_pagamento` enum('pix','transferencia','cheque','dinheiro') NOT NULL DEFAULT 'transferencia',
  `status` enum('pendente','pago','cancelado') NOT NULL DEFAULT 'pendente',
  `observacoes` text DEFAULT NULL,
  `comprovante` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `funcionario_id` (`funcionario_id`),
  CONSTRAINT `pagamentos_ibfk_1` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de folha de pagamento
CREATE TABLE IF NOT EXISTS `folha_pagamento` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mes_referencia` int(11) NOT NULL,
  `ano_referencia` int(11) NOT NULL,
  `data_geracao` datetime NOT NULL,
  `valor_total` decimal(10,2) NOT NULL,
  `status` enum('aberta','fechada','paga') NOT NULL DEFAULT 'aberta',
  `observacoes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `mes_ano` (`mes_referencia`,`ano_referencia`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de itens da folha de pagamento
CREATE TABLE IF NOT EXISTS `folha_pagamento_itens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `folha_id` int(11) NOT NULL,
  `funcionario_id` int(11) NOT NULL,
  `salario_base` decimal(10,2) NOT NULL,
  `inss` decimal(10,2) NOT NULL DEFAULT 0.00,
  `irrf` decimal(10,2) NOT NULL DEFAULT 0.00,
  `fgts` decimal(10,2) NOT NULL DEFAULT 0.00,
  `outros_descontos` decimal(10,2) NOT NULL DEFAULT 0.00,
  `outros_proventos` decimal(10,2) NOT NULL DEFAULT 0.00,
  `valor_liquido` decimal(10,2) NOT NULL,
  `observacoes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `folha_id` (`folha_id`),
  KEY `funcionario_id` (`funcionario_id`),
  CONSTRAINT `folha_pagamento_itens_ibfk_1` FOREIGN KEY (`folha_id`) REFERENCES `folha_pagamento` (`id`) ON DELETE CASCADE,
  CONSTRAINT `folha_pagamento_itens_ibfk_2` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela para integração com contas a pagar
CREATE TABLE IF NOT EXISTS `contas_pagar_rh` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pagamento_id` int(11) NOT NULL,
  `conta_pagar_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `pagamento_id` (`pagamento_id`),
  KEY `conta_pagar_id` (`conta_pagar_id`),
  CONSTRAINT `contas_pagar_rh_ibfk_1` FOREIGN KEY (`pagamento_id`) REFERENCES `pagamentos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `contas_pagar_rh_ibfk_2` FOREIGN KEY (`conta_pagar_id`) REFERENCES `contas_pagar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
