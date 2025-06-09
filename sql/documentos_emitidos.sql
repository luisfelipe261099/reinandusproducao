-- Cria a tabela documentos_emitidos
CREATE TABLE IF NOT EXISTS `documentos_emitidos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aluno_id` int(11) NOT NULL,
  `matricula_id` int(11) DEFAULT NULL,
  `curso_id` int(11) DEFAULT NULL,
  `polo_id` int(11) DEFAULT NULL,
  `tipo_documento_id` int(11) NOT NULL,
  `data_emissao` date NOT NULL,
  `data_validade` date DEFAULT NULL,
  `codigo_verificacao` varchar(20) NOT NULL,
  `arquivo_path` varchar(255) DEFAULT NULL,
  `dados_documento` text DEFAULT NULL,
  `status` enum('emitido','cancelado') NOT NULL DEFAULT 'emitido',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `aluno_id` (`aluno_id`),
  KEY `matricula_id` (`matricula_id`),
  KEY `curso_id` (`curso_id`),
  KEY `polo_id` (`polo_id`),
  KEY `tipo_documento_id` (`tipo_documento_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cria a tabela tipos_documentos se não existir
CREATE TABLE IF NOT EXISTS `tipos_documentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `status` enum('ativo','inativo') NOT NULL DEFAULT 'ativo',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insere tipos de documentos padrão se a tabela estiver vazia
INSERT INTO `tipos_documentos` (`nome`, `descricao`, `status`, `created_at`, `updated_at`)
SELECT 'Histórico Escolar', 'Histórico Escolar do Aluno', 'ativo', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `tipos_documentos` WHERE `nome` = 'Histórico Escolar');

INSERT INTO `tipos_documentos` (`nome`, `descricao`, `status`, `created_at`, `updated_at`)
SELECT 'Declaração de Matrícula', 'Declaração de Matrícula', 'ativo', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `tipos_documentos` WHERE `nome` = 'Declaração de Matrícula');

-- Adiciona coluna documentos_emitidos na tabela polos se não existir
ALTER TABLE `polos` 
ADD COLUMN IF NOT EXISTS `documentos_emitidos` int(11) NOT NULL DEFAULT 0,
ADD COLUMN IF NOT EXISTS `limite_documentos` int(11) DEFAULT NULL;
