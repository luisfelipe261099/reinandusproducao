-- ============================================
-- TABELAS DO MÓDULO FINANCEIRO
-- Sistema Faciência ERP
-- ============================================

-- 1. Tabela de categorias financeiras
CREATE TABLE IF NOT EXISTS `categorias_financeiras` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `tipo` enum('receita','despesa') NOT NULL,
  `cor` varchar(7) DEFAULT '#3498db',
  `icone` varchar(50) DEFAULT NULL,
  `status` enum('ativo','inativo') NOT NULL DEFAULT 'ativo',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_tipo` (`tipo`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabela de contas bancárias
CREATE TABLE IF NOT EXISTS `contas_bancarias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `banco` varchar(100) DEFAULT NULL,
  `agencia` varchar(20) DEFAULT NULL,
  `conta` varchar(20) DEFAULT NULL,
  `tipo` enum('corrente','poupanca','investimento','caixa') NOT NULL DEFAULT 'corrente',
  `saldo_inicial` decimal(10,2) NOT NULL DEFAULT 0.00,
  `saldo_atual` decimal(10,2) NOT NULL DEFAULT 0.00,
  `data_saldo` date NOT NULL,
  `status` enum('ativo','inativo') NOT NULL DEFAULT 'ativo',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabela de funcionários (atualizada para o financeiro)
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
  `status` enum('ativo','inativo') NOT NULL DEFAULT 'ativo',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `cpf` (`cpf`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tabela de contas a pagar
CREATE TABLE IF NOT EXISTS `contas_pagar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `descricao` varchar(255) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `data_vencimento` date NOT NULL,
  `data_pagamento` date DEFAULT NULL,
  `fornecedor_id` int(11) DEFAULT NULL,
  `fornecedor_nome` varchar(100) DEFAULT NULL,
  `categoria_id` int(11) DEFAULT NULL,
  `conta_bancaria_id` int(11) DEFAULT NULL,
  `forma_pagamento` varchar(50) DEFAULT NULL,
  `status` enum('pendente','pago','cancelado') NOT NULL DEFAULT 'pendente',
  `observacoes` text DEFAULT NULL,
  `comprovante_path` varchar(255) DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `categoria_id` (`categoria_id`),
  KEY `conta_bancaria_id` (`conta_bancaria_id`),
  KEY `usuario_id` (`usuario_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_data_vencimento` (`data_vencimento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Tabela de contas a receber
CREATE TABLE IF NOT EXISTS `contas_receber` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `descricao` varchar(255) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `data_vencimento` date NOT NULL,
  `data_recebimento` date DEFAULT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `cliente_nome` varchar(100) DEFAULT NULL,
  `cliente_tipo` enum('aluno','polo','terceiro') DEFAULT 'terceiro',
  `categoria_id` int(11) DEFAULT NULL,
  `conta_bancaria_id` int(11) DEFAULT NULL,
  `forma_recebimento` varchar(50) DEFAULT NULL,
  `status` enum('pendente','recebido','cancelado') NOT NULL DEFAULT 'pendente',
  `observacoes` text DEFAULT NULL,
  `comprovante_path` varchar(255) DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `categoria_id` (`categoria_id`),
  KEY `conta_bancaria_id` (`conta_bancaria_id`),
  KEY `usuario_id` (`usuario_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_data_vencimento` (`data_vencimento`),
  INDEX `idx_cliente_tipo` (`cliente_tipo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Tabela de transações financeiras (movimentações)
CREATE TABLE IF NOT EXISTS `transacoes_financeiras` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo` enum('receita','despesa','transferencia') NOT NULL,
  `descricao` varchar(255) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `data_transacao` date NOT NULL,
  `categoria_id` int(11) DEFAULT NULL,
  `conta_bancaria_id` int(11) DEFAULT NULL,
  `conta_destino_id` int(11) DEFAULT NULL,
  `forma_pagamento` varchar(50) DEFAULT NULL,
  `referencia_tipo` enum('conta_pagar','conta_receber','folha_pagamento','outros') DEFAULT NULL,
  `referencia_id` int(11) DEFAULT NULL,
  `status` enum('efetivada','pendente','cancelada') NOT NULL DEFAULT 'efetivada',
  `observacoes` text DEFAULT NULL,
  `comprovante_path` varchar(255) DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `categoria_id` (`categoria_id`),
  KEY `conta_bancaria_id` (`conta_bancaria_id`),
  KEY `conta_destino_id` (`conta_destino_id`),
  KEY `usuario_id` (`usuario_id`),
  INDEX `idx_tipo` (`tipo`),
  INDEX `idx_data_transacao` (`data_transacao`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Tabela de folha de pagamento
CREATE TABLE IF NOT EXISTS `folha_pagamento` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `funcionario_id` int(11) NOT NULL,
  `mes_referencia` date NOT NULL,
  `salario_base` decimal(10,2) NOT NULL,
  `horas_extras` decimal(5,2) DEFAULT 0.00,
  `valor_horas_extras` decimal(10,2) DEFAULT 0.00,
  `adicional_noturno` decimal(10,2) DEFAULT 0.00,
  `adicional_periculosidade` decimal(10,2) DEFAULT 0.00,
  `adicional_insalubridade` decimal(10,2) DEFAULT 0.00,
  `outros_proventos` decimal(10,2) DEFAULT 0.00,
  `descricao_outros_proventos` text DEFAULT NULL,
  `inss` decimal(10,2) DEFAULT 0.00,
  `irrf` decimal(10,2) DEFAULT 0.00,
  `vale_transporte` decimal(10,2) DEFAULT 0.00,
  `vale_refeicao` decimal(10,2) DEFAULT 0.00,
  `plano_saude` decimal(10,2) DEFAULT 0.00,
  `outros_descontos` decimal(10,2) DEFAULT 0.00,
  `descricao_outros_descontos` text DEFAULT NULL,
  `salario_liquido` decimal(10,2) NOT NULL,
  `data_pagamento` date DEFAULT NULL,
  `status` enum('calculada','paga','cancelada') NOT NULL DEFAULT 'calculada',
  `observacoes` text DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `funcionario_id` (`funcionario_id`),
  KEY `usuario_id` (`usuario_id`),
  INDEX `idx_mes_referencia` (`mes_referencia`),
  INDEX `idx_status` (`status`),
  UNIQUE KEY `funcionario_mes` (`funcionario_id`, `mes_referencia`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Tabela de mensalidades de alunos específicos
CREATE TABLE IF NOT EXISTS `mensalidades_alunos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aluno_id` int(11) NOT NULL,
  `curso_id` int(11) DEFAULT NULL,
  `valor` decimal(10,2) NOT NULL,
  `data_vencimento` date NOT NULL,
  `data_pagamento` date DEFAULT NULL,
  `mes_referencia` date NOT NULL,
  `desconto` decimal(10,2) DEFAULT 0.00,
  `multa` decimal(10,2) DEFAULT 0.00,
  `juros` decimal(10,2) DEFAULT 0.00,
  `valor_pago` decimal(10,2) DEFAULT NULL,
  `forma_pagamento` varchar(50) DEFAULT NULL,
  `status` enum('pendente','pago','cancelado','isento') NOT NULL DEFAULT 'pendente',
  `observacoes` text DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `aluno_id` (`aluno_id`),
  KEY `curso_id` (`curso_id`),
  KEY `usuario_id` (`usuario_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_data_vencimento` (`data_vencimento`),
  INDEX `idx_mes_referencia` (`mes_referencia`),
  UNIQUE KEY `aluno_mes` (`aluno_id`, `mes_referencia`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Tabela de cobrança de polos
CREATE TABLE IF NOT EXISTS `cobranca_polos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `polo_id` int(11) NOT NULL,
  `descricao` varchar(255) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `data_vencimento` date NOT NULL,
  `data_pagamento` date DEFAULT NULL,
  `mes_referencia` date NOT NULL,
  `tipo_cobranca` enum('mensalidade','taxa','outros') NOT NULL DEFAULT 'mensalidade',
  `forma_pagamento` varchar(50) DEFAULT NULL,
  `status` enum('pendente','pago','cancelado') NOT NULL DEFAULT 'pendente',
  `observacoes` text DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `polo_id` (`polo_id`),
  KEY `usuario_id` (`usuario_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_data_vencimento` (`data_vencimento`),
  INDEX `idx_mes_referencia` (`mes_referencia`),
  INDEX `idx_tipo_cobranca` (`tipo_cobranca`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- INSERÇÃO DE DADOS PADRÃO
-- ============================================

-- Categorias financeiras padrão
INSERT IGNORE INTO `categorias_financeiras` (`nome`, `descricao`, `tipo`, `cor`) VALUES
('Mensalidades de Alunos', 'Receitas provenientes de mensalidades de alunos específicos', 'receita', '#10b981'),
('Cobrança de Polos', 'Receitas provenientes de cobrança de polos', 'receita', '#059669'),
('Outras Receitas', 'Outras receitas diversas', 'receita', '#34d399'),
('Salários e Encargos', 'Pagamentos de funcionários CLT', 'despesa', '#ef4444'),
('Fornecedores', 'Pagamentos a fornecedores', 'despesa', '#dc2626'),
('Terceiros', 'Pagamentos a terceiros', 'despesa', '#f87171'),
('Despesas Operacionais', 'Despesas operacionais da instituição', 'despesa', '#fca5a5'),
('Aluguel', 'Pagamentos de aluguel', 'despesa', '#b91c1c'),
('Utilidades', 'Luz, água, telefone, internet', 'despesa', '#991b1b');

-- Conta bancária padrão
INSERT IGNORE INTO `contas_bancarias` (`nome`, `tipo`, `saldo_inicial`, `saldo_atual`, `data_saldo`) VALUES
('Caixa Geral', 'caixa', 0.00, 0.00, CURDATE()),
('Conta Corrente Principal', 'corrente', 0.00, 0.00, CURDATE());
