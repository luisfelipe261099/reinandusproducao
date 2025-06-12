-- ============================================================================
-- SETUP COMPLETO DO MÓDULO FINANCEIRO - FACIÊNCIA ERP (MODO SEGURO)
-- ============================================================================
-- Versão: 2.1
-- Data: 2025-06-11
-- Descrição: Script SQL completo para configurar/atualizar todas as tabelas do módulo financeiro
-- 
-- INSTRUÇÕES:
-- 1. Execute este script diretamente no seu banco de dados MySQL/MariaDB
-- 2. TOTALMENTE SEGURO - Pode ser executado múltiplas vezes sem problemas
-- 3. Usa CREATE TABLE IF NOT EXISTS + ALTER TABLE para colunas que podem faltar
-- 4. Após executar, o módulo financeiro estará 100% funcional
-- ============================================================================

-- Configurações de segurança e charset
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- Desabilitar verificação de chaves estrangeiras temporariamente
SET foreign_key_checks = 0;

-- ============================================================================
-- 1. CATEGORIAS FINANCEIRAS
-- ============================================================================
CREATE TABLE IF NOT EXISTS `categorias_financeiras` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `tipo` enum('receita','despesa') NOT NULL,
  `cor` varchar(7) DEFAULT '#3498db',
  `status` enum('ativo','inativo') NOT NULL DEFAULT 'ativo',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tipo` (`tipo`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 2. CONTAS BANCÁRIAS
-- ============================================================================
CREATE TABLE IF NOT EXISTS `contas_bancarias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `banco` varchar(50) DEFAULT NULL,
  `agencia` varchar(10) DEFAULT NULL,
  `conta` varchar(20) DEFAULT NULL,
  `tipo` enum('corrente','poupanca','investimento','caixa') NOT NULL DEFAULT 'corrente',
  `saldo_inicial` decimal(10,2) NOT NULL DEFAULT 0.00,
  `saldo_atual` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('ativo','inativo') NOT NULL DEFAULT 'ativo',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 3. FUNCIONÁRIOS
-- ============================================================================
CREATE TABLE IF NOT EXISTS `funcionarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `cpf` varchar(14) NOT NULL,
  `rg` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `endereco` varchar(255) DEFAULT NULL,
  `cargo` varchar(100) NOT NULL,
  `salario` decimal(10,2) NOT NULL DEFAULT 0.00,
  `data_admissao` date DEFAULT NULL,
  `data_demissao` date DEFAULT NULL,
  `status` enum('ativo','inativo','demitido') NOT NULL DEFAULT 'ativo',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cpf` (`cpf`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 4. CONTAS A PAGAR
-- ============================================================================
CREATE TABLE IF NOT EXISTS `contas_pagar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `categoria_id` int(11) DEFAULT NULL,
  `fornecedor_nome` varchar(100) DEFAULT NULL,
  `fornecedor_cnpj` varchar(18) DEFAULT NULL,
  `descricao` varchar(255) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `desconto` decimal(10,2) DEFAULT 0.00,
  `acrescimo` decimal(10,2) DEFAULT 0.00,
  `valor_pago` decimal(10,2) DEFAULT 0.00,
  `data_vencimento` date NOT NULL,
  `data_pagamento` date DEFAULT NULL,
  `forma_pagamento` varchar(50) DEFAULT NULL,
  `numero_documento` varchar(50) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `status` enum('pendente','pago','cancelado','vencido') NOT NULL DEFAULT 'pendente',
  `conta_bancaria_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_vencimento` (`data_vencimento`),
  KEY `fk_categoria_pagar` (`categoria_id`),
  KEY `fk_conta_pagar` (`conta_bancaria_id`),
  CONSTRAINT `fk_categoria_pagar` FOREIGN KEY (`categoria_id`) REFERENCES `categorias_financeiras` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_conta_pagar` FOREIGN KEY (`conta_bancaria_id`) REFERENCES `contas_bancarias` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 5. CONTAS A RECEBER
-- ============================================================================
CREATE TABLE IF NOT EXISTS `contas_receber` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `categoria_id` int(11) DEFAULT NULL,
  `cliente_nome` varchar(100) DEFAULT NULL,
  `cliente_cpf_cnpj` varchar(18) DEFAULT NULL,
  `cliente_tipo` enum('aluno','polo','terceiro') DEFAULT 'terceiro',
  `cliente_id` int(11) DEFAULT NULL,
  `descricao` varchar(255) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `desconto` decimal(10,2) DEFAULT 0.00,
  `acrescimo` decimal(10,2) DEFAULT 0.00,
  `valor_recebido` decimal(10,2) DEFAULT 0.00,
  `data_vencimento` date NOT NULL,
  `data_recebimento` date DEFAULT NULL,
  `forma_recebimento` varchar(50) DEFAULT NULL,
  `numero_documento` varchar(50) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `status` enum('pendente','recebido','cancelado','vencido') NOT NULL DEFAULT 'pendente',
  `conta_bancaria_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_vencimento` (`data_vencimento`),
  KEY `idx_cliente_tipo` (`cliente_tipo`, `cliente_id`),
  KEY `fk_categoria_receber` (`categoria_id`),
  KEY `fk_conta_receber` (`conta_bancaria_id`),
  CONSTRAINT `fk_categoria_receber` FOREIGN KEY (`categoria_id`) REFERENCES `categorias_financeiras` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_conta_receber` FOREIGN KEY (`conta_bancaria_id`) REFERENCES `contas_bancarias` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 6. TRANSAÇÕES FINANCEIRAS
-- ============================================================================
CREATE TABLE IF NOT EXISTS `transacoes_financeiras` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `categoria_id` int(11) DEFAULT NULL,
  `conta_bancaria_id` int(11) DEFAULT NULL,
  `tipo` enum('receita','despesa','transferencia') NOT NULL,
  `descricao` varchar(255) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `data_transacao` date NOT NULL,
  `forma_pagamento` varchar(50) DEFAULT NULL,
  `numero_documento` varchar(50) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `status` enum('efetivada','pendente','cancelada') NOT NULL DEFAULT 'efetivada',
  `conta_destino_id` int(11) DEFAULT NULL,
  `referencia_tipo` enum('conta_pagar','conta_receber','folha','boleto','manual') DEFAULT NULL,
  `referencia_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tipo` (`tipo`),
  KEY `idx_data` (`data_transacao`),
  KEY `idx_status` (`status`),
  KEY `fk_categoria_transacao` (`categoria_id`),
  KEY `fk_conta_transacao` (`conta_bancaria_id`),
  KEY `fk_conta_destino` (`conta_destino_id`),
  CONSTRAINT `fk_categoria_transacao` FOREIGN KEY (`categoria_id`) REFERENCES `categorias_financeiras` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_conta_transacao` FOREIGN KEY (`conta_bancaria_id`) REFERENCES `contas_bancarias` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_conta_destino` FOREIGN KEY (`conta_destino_id`) REFERENCES `contas_bancarias` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 7. FOLHA DE PAGAMENTO
-- ============================================================================
CREATE TABLE IF NOT EXISTS `folha_pagamento` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `funcionario_id` int(11) NOT NULL,
  `mes_referencia` date NOT NULL,
  `salario_base` decimal(10,2) NOT NULL,
  `horas_extras` decimal(10,2) DEFAULT 0.00,
  `bonus` decimal(10,2) DEFAULT 0.00,
  `adicional_noturno` decimal(10,2) DEFAULT 0.00,
  `vale_transporte` decimal(10,2) DEFAULT 0.00,
  `vale_refeicao` decimal(10,2) DEFAULT 0.00,
  `plano_saude` decimal(10,2) DEFAULT 0.00,
  `inss` decimal(10,2) DEFAULT 0.00,
  `irrf` decimal(10,2) DEFAULT 0.00,
  `fgts` decimal(10,2) DEFAULT 0.00,
  `outros_descontos` decimal(10,2) DEFAULT 0.00,
  `salario_bruto` decimal(10,2) NOT NULL,
  `total_descontos` decimal(10,2) NOT NULL,
  `salario_liquido` decimal(10,2) NOT NULL,
  `data_pagamento` date DEFAULT NULL,
  `status` enum('calculada','paga','cancelada') NOT NULL DEFAULT 'calculada',
  `observacoes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `funcionario_mes` (`funcionario_id`, `mes_referencia`),
  KEY `idx_mes_referencia` (`mes_referencia`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_folha_funcionario` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 8. MENSALIDADES DE ALUNOS
-- ============================================================================
CREATE TABLE IF NOT EXISTS `mensalidades_alunos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aluno_id` int(11) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `desconto` decimal(10,2) DEFAULT 0.00,
  `acrescimo` decimal(10,2) DEFAULT 0.00,
  `valor_final` decimal(10,2) NOT NULL,
  `data_vencimento` date NOT NULL,
  `data_pagamento` date DEFAULT NULL,
  `mes_referencia` date NOT NULL,
  `forma_pagamento` varchar(50) DEFAULT NULL,
  `numero_documento` varchar(50) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `status` enum('pendente','pago','cancelado','isento','vencido') NOT NULL DEFAULT 'pendente',
  `conta_bancaria_id` int(11) DEFAULT NULL,
  `boleto_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_aluno` (`aluno_id`),
  KEY `idx_mes_referencia` (`mes_referencia`),
  KEY `idx_status` (`status`),
  KEY `idx_vencimento` (`data_vencimento`),
  KEY `fk_mensalidade_conta` (`conta_bancaria_id`),
  CONSTRAINT `fk_mensalidade_conta` FOREIGN KEY (`conta_bancaria_id`) REFERENCES `contas_bancarias` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 9. COBRANÇA DE POLOS
-- ============================================================================
CREATE TABLE IF NOT EXISTS `cobranca_polos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `polo_id` int(11) NOT NULL,
  `descricao` varchar(255) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `desconto` decimal(10,2) DEFAULT 0.00,
  `acrescimo` decimal(10,2) DEFAULT 0.00,
  `valor_final` decimal(10,2) NOT NULL,
  `data_vencimento` date NOT NULL,
  `data_pagamento` date DEFAULT NULL,
  `mes_referencia` date NOT NULL,
  `tipo_cobranca` enum('mensalidade','taxa','licenca','outros') NOT NULL DEFAULT 'mensalidade',
  `forma_pagamento` varchar(50) DEFAULT NULL,
  `numero_documento` varchar(50) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `status` enum('pendente','pago','cancelado','vencido') NOT NULL DEFAULT 'pendente',
  `conta_bancaria_id` int(11) DEFAULT NULL,
  `boleto_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_polo` (`polo_id`),
  KEY `idx_mes_referencia` (`mes_referencia`),
  KEY `idx_status` (`status`),
  KEY `idx_vencimento` (`data_vencimento`),
  KEY `fk_cobranca_conta` (`conta_bancaria_id`),
  CONSTRAINT `fk_cobranca_conta` FOREIGN KEY (`conta_bancaria_id`) REFERENCES `contas_bancarias` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 10. BOLETOS BANCÁRIOS
-- ============================================================================
CREATE TABLE IF NOT EXISTS `boletos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo` enum('mensalidade','polo','avulso','funcionario') NOT NULL,
  `referencia_id` int(11) DEFAULT NULL,
  `valor` decimal(10,2) NOT NULL,
  `data_vencimento` date NOT NULL,
  `data_emissao` date NOT NULL DEFAULT (CURRENT_DATE),
  `descricao` varchar(255) NOT NULL,
  `instrucoes` text DEFAULT NULL,
  `nome_pagador` varchar(255) NOT NULL,
  `cpf_pagador` varchar(14) NOT NULL,
  `endereco` varchar(255) DEFAULT NULL,
  `numero` varchar(20) DEFAULT NULL,
  `complemento` varchar(100) DEFAULT NULL,
  `bairro` varchar(100) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `uf` varchar(2) DEFAULT NULL,
  `cep` varchar(10) DEFAULT NULL,
  `multa` decimal(5,2) DEFAULT 2.00,
  `juros` decimal(5,2) DEFAULT 1.00,
  `desconto` decimal(10,2) DEFAULT 0.00,
  `nosso_numero` varchar(20) DEFAULT NULL,
  `linha_digitavel` varchar(100) DEFAULT NULL,
  `codigo_barras` varchar(100) DEFAULT NULL,
  `url_boleto` varchar(500) DEFAULT NULL,
  `id_externo` varchar(100) DEFAULT NULL,
  `ambiente` enum('teste','producao') DEFAULT 'teste',
  `banco` varchar(50) DEFAULT 'itau',
  `carteira` varchar(10) DEFAULT '109',
  `status` enum('pendente','pago','vencido','cancelado','processando') NOT NULL DEFAULT 'pendente',
  `data_pagamento` date DEFAULT NULL,
  `valor_pago` decimal(10,2) DEFAULT NULL,
  `forma_pagamento` varchar(50) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tipo_referencia` (`tipo`, `referencia_id`),
  KEY `idx_status` (`status`),
  KEY `idx_vencimento` (`data_vencimento`),
  KEY `idx_nosso_numero` (`nosso_numero`),
  KEY `idx_cpf_pagador` (`cpf_pagador`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 11. CONFIGURAÇÕES DO MÓDULO FINANCEIRO
-- ============================================================================
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
  UNIQUE KEY `chave` (`chave`),  KEY `idx_grupo` (`grupo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- ALTERAÇÕES PARA TABELAS EXISTENTES (MODO SEGURO)
-- ============================================================================

-- Adicionar colunas que podem estar faltando nas tabelas existentes
-- Usando procedimentos condicionais para evitar erros

-- Verificar e adicionar coluna 'cor' na tabela categorias_financeiras
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'categorias_financeiras' 
     AND column_name = 'cor' 
     AND table_schema = DATABASE()) > 0,
    "SELECT 'Coluna cor já existe na tabela categorias_financeiras';",
    "ALTER TABLE `categorias_financeiras` ADD COLUMN `cor` varchar(7) DEFAULT '#3498db' AFTER `tipo`;"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar e adicionar coluna 'status' na tabela categorias_financeiras
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'categorias_financeiras' 
     AND column_name = 'status' 
     AND table_schema = DATABASE()) > 0,
    "SELECT 'Coluna status já existe na tabela categorias_financeiras';",
    "ALTER TABLE `categorias_financeiras` ADD COLUMN `status` enum('ativo','inativo') NOT NULL DEFAULT 'ativo' AFTER `cor`;"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar e adicionar coluna 'created_at' na tabela categorias_financeiras
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'categorias_financeiras' 
     AND column_name = 'created_at' 
     AND table_schema = DATABASE()) > 0,
    "SELECT 'Coluna created_at já existe na tabela categorias_financeiras';",
    "ALTER TABLE `categorias_financeiras` ADD COLUMN `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `status`;"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar e adicionar coluna 'updated_at' na tabela categorias_financeiras
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'categorias_financeiras' 
     AND column_name = 'updated_at' 
     AND table_schema = DATABASE()) > 0,
    "SELECT 'Coluna updated_at já existe na tabela categorias_financeiras';",
    "ALTER TABLE `categorias_financeiras` ADD COLUMN `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar e adicionar índices que podem estar faltando
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE table_name = 'categorias_financeiras' 
     AND index_name = 'idx_tipo' 
     AND table_schema = DATABASE()) > 0,
    "SELECT 'Índice idx_tipo já existe na tabela categorias_financeiras';",
    "ALTER TABLE `categorias_financeiras` ADD KEY `idx_tipo` (`tipo`);"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE table_name = 'categorias_financeiras' 
     AND index_name = 'idx_status' 
     AND table_schema = DATABASE()) > 0,
    "SELECT 'Índice idx_status já existe na tabela categorias_financeiras';",
    "ALTER TABLE `categorias_financeiras` ADD KEY `idx_status` (`status`);"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar se existe tabela boletos e criar se necessário
CREATE TABLE IF NOT EXISTS `boletos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo` enum('mensalidade','polo','avulso','funcionario') NOT NULL,
  `referencia_id` int(11) DEFAULT NULL,
  `valor` decimal(10,2) NOT NULL,
  `data_vencimento` date NOT NULL,
  `data_emissao` date NOT NULL DEFAULT (CURRENT_DATE),
  `descricao` varchar(255) NOT NULL,
  `instrucoes` text DEFAULT NULL,
  `nome_pagador` varchar(255) NOT NULL,
  `cpf_pagador` varchar(14) NOT NULL,
  `endereco` varchar(255) DEFAULT NULL,
  `numero` varchar(20) DEFAULT NULL,
  `complemento` varchar(100) DEFAULT NULL,
  `bairro` varchar(100) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `uf` varchar(2) DEFAULT NULL,
  `cep` varchar(10) DEFAULT NULL,
  `multa` decimal(5,2) DEFAULT 2.00,
  `juros` decimal(5,2) DEFAULT 1.00,
  `desconto` decimal(10,2) DEFAULT 0.00,
  `nosso_numero` varchar(20) DEFAULT NULL,
  `linha_digitavel` varchar(100) DEFAULT NULL,
  `codigo_barras` varchar(100) DEFAULT NULL,
  `url_boleto` varchar(500) DEFAULT NULL,
  `id_externo` varchar(100) DEFAULT NULL,
  `ambiente` enum('teste','producao') DEFAULT 'teste',
  `banco` varchar(50) DEFAULT 'itau',
  `carteira` varchar(10) DEFAULT '109',
  `status` enum('pendente','pago','vencido','cancelado','processando') NOT NULL DEFAULT 'pendente',
  `data_pagamento` date DEFAULT NULL,
  `valor_pago` decimal(10,2) DEFAULT NULL,
  `forma_pagamento` varchar(50) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tipo_referencia` (`tipo`, `referencia_id`),
  KEY `idx_status` (`status`),
  KEY `idx_vencimento` (`data_vencimento`),
  KEY `idx_nosso_numero` (`nosso_numero`),
  KEY `idx_cpf_pagador` (`cpf_pagador`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Verificar se existe tabela configuracoes_financeiras e criar se necessário
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

-- ============================================================================
-- INSERÇÃO DE DADOS BÁSICOS
-- ============================================================================

-- Categorias financeiras padrão
INSERT IGNORE INTO `categorias_financeiras` (`nome`, `tipo`, `cor`) VALUES
('Mensalidades de Alunos', 'receita', '#10b981'),
('Cobrança de Polos', 'receita', '#059669'),
('Taxas de Licença', 'receita', '#34d399'),
('Outras Receitas', 'receita', '#6ee7b7'),
('Salários e Encargos', 'despesa', '#ef4444'),
('Fornecedores', 'despesa', '#dc2626'),
('Despesas Administrativas', 'despesa', '#f87171'),
('Impostos e Taxas', 'despesa', '#fca5a5'),
('Marketing e Publicidade', 'despesa', '#fb7185'),
('Infraestrutura e TI', 'despesa', '#e11d48');

-- Conta bancária padrão
INSERT IGNORE INTO `contas_bancarias` (`nome`, `tipo`, `saldo_inicial`, `saldo_atual`) VALUES
('Caixa Geral', 'caixa', 0.00, 0.00),
('Conta Corrente Principal', 'corrente', 0.00, 0.00);

-- Configurações básicas do módulo financeiro
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

-- ============================================================================
-- FINALIZAÇÃO
-- ============================================================================

-- Reabilitar verificação de chaves estrangeiras
SET foreign_key_checks = 1;

-- Commit das alterações
COMMIT;

-- Mensagem de sucesso
SELECT 'SETUP/ATUALIZAÇÃO DO MÓDULO FINANCEIRO CONCLUÍDO COM SUCESSO!' as STATUS,
       'Todas as tabelas foram criadas/atualizadas e os dados básicos foram inseridos.' as DETALHES,
       NOW() as DATA_EXECUCAO,
       'v2.1' as VERSAO;

-- Verificação das tabelas criadas/atualizadas
SELECT 
    'TABELAS VERIFICADAS:' as INFO,
    COUNT(*) as TOTAL_TABELAS_EXISTENTES
FROM information_schema.tables 
WHERE table_schema = DATABASE() 
AND table_name IN (
    'categorias_financeiras',
    'contas_bancarias', 
    'funcionarios',
    'contas_pagar',
    'contas_receber',
    'transacoes_financeiras',
    'folha_pagamento',
    'mensalidades_alunos',
    'cobranca_polos',
    'boletos',
    'configuracoes_financeiras'
);

-- Verificação das configurações inseridas
SELECT 
    'CONFIGURAÇÕES INSERIDAS:' as INFO,
    COUNT(*) as TOTAL_CONFIGURACOES
FROM configuracoes_financeiras
WHERE grupo IN ('sistema', 'configuracao', 'boletos');

-- Status final das principais tabelas
SELECT 
    table_name as TABELA,
    table_rows as LINHAS_APROX,
    ROUND(((data_length + index_length) / 1024 / 1024), 2) as TAMANHO_MB
FROM information_schema.tables 
WHERE table_schema = DATABASE() 
AND table_name IN ('categorias_financeiras', 'boletos', 'configuracoes_financeiras')
ORDER BY table_name;
