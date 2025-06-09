
CREATE TABLE IF NOT EXISTS `alunos_acesso` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aluno_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `token_recuperacao` varchar(255) DEFAULT NULL,
  `token_expiracao` datetime DEFAULT NULL,
  `ultimo_acesso` datetime DEFAULT NULL,
  `status` enum('ativo','inativo') NOT NULL DEFAULT 'ativo',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `aluno_id` (`aluno_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela para armazenar as preferências do aluno
CREATE TABLE IF NOT EXISTS `alunos_preferencias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aluno_id` int(11) NOT NULL,
  `tema` varchar(50) DEFAULT 'light',
  `notificacoes_email` tinyint(1) DEFAULT 1,
  `notificacoes_sistema` tinyint(1) DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `aluno_id` (`aluno_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela para armazenar as sessões ativas dos alunos
CREATE TABLE IF NOT EXISTS `alunos_sessoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aluno_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `aluno_id` (`aluno_id`),
  KEY `token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela para armazenar o histórico de atividades do aluno
CREATE TABLE IF NOT EXISTS `alunos_atividades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aluno_id` int(11) NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `descricao` text NOT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `aluno_id` (`aluno_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Adicionar coluna para controle de acesso ao AVA na tabela de alunos (se necessário)
ALTER TABLE `alunos` 
ADD COLUMN IF NOT EXISTS `acesso_ava` tinyint(1) NOT NULL DEFAULT 0 AFTER `status`;

-- Adicionar coluna para foto de perfil na tabela de alunos (se necessário)
ALTER TABLE `alunos` 
ADD COLUMN IF NOT EXISTS `foto_perfil` varchar(255) DEFAULT NULL AFTER `email`;

-- Adicionar coluna para biografia/descrição na tabela de alunos (se necessário)
ALTER TABLE `alunos` 
ADD COLUMN IF NOT EXISTS `biografia` text DEFAULT NULL AFTER `foto_perfil`;

-- Adicionar coluna para controle de primeiro acesso
ALTER TABLE `alunos` 
ADD COLUMN IF NOT EXISTS `primeiro_acesso` tinyint(1) NOT NULL DEFAULT 1 AFTER `acesso_ava`;

-- Adicionar índices para melhorar a performance das consultas
ALTER TABLE `alunos` 
ADD INDEX IF NOT EXISTS `idx_alunos_polo_id` (`polo_id`),
ADD INDEX IF NOT EXISTS `idx_alunos_curso_id` (`curso_id`),
ADD INDEX IF NOT EXISTS `idx_alunos_turma_id` (`turma_id`),
ADD INDEX IF NOT EXISTS `idx_alunos_status` (`status`);

-- Adicionar índices para a tabela de documentos dos alunos
ALTER TABLE `documentos_alunos` 
ADD INDEX IF NOT EXISTS `idx_documentos_alunos_aluno_id` (`aluno_id`),
ADD INDEX IF NOT EXISTS `idx_documentos_alunos_tipo` (`tipo`);

-- Adicionar índices para a tabela de notas
ALTER TABLE `notas` 
ADD INDEX IF NOT EXISTS `idx_notas_aluno_id` (`aluno_id`),
ADD INDEX IF NOT EXISTS `idx_notas_disciplina_id` (`disciplina_id`);

-- Adicionar índices para a tabela de documentos emitidos
ALTER TABLE `documentos_emitidos` 
ADD INDEX IF NOT EXISTS `idx_documentos_emitidos_aluno_id` (`aluno_id`),
ADD INDEX IF NOT EXISTS `idx_documentos_emitidos_tipo` (`tipo`);
