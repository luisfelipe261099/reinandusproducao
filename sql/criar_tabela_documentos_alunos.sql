-- Script para criar a tabela de documentos dos alunos
-- Esta tabela armazenará os documentos pessoais dos alunos (RG, CPF, comprovante de residência, etc.)

-- Tabela de tipos de documentos pessoais
CREATE TABLE IF NOT EXISTS `tipos_documentos_pessoais` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `obrigatorio` tinyint(1) NOT NULL DEFAULT 1,
  `status` enum('ativo','inativo') NOT NULL DEFAULT 'ativo',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir tipos de documentos pessoais padrão
INSERT INTO `tipos_documentos_pessoais` (`nome`, `descricao`, `obrigatorio`, `status`) VALUES
('RG/CNH', 'Documento de identidade ou Carteira Nacional de Habilitação', 1, 'ativo'),
('CPF', 'Cadastro de Pessoa Física', 1, 'ativo'),
('Comprovante de Residência', 'Comprovante de residência atualizado (últimos 3 meses)', 1, 'ativo'),
('Histórico Escolar', 'Histórico escolar do ensino médio', 1, 'ativo'),
('Histórico de Graduação', 'Histórico escolar de graduação', 0, 'ativo'),
('Certificado de Conclusão', 'Certificado de conclusão de curso', 1, 'ativo'),
('Certidão de Nascimento/Casamento', 'Certidão de nascimento ou casamento', 0, 'ativo'),
('Título de Eleitor', 'Título de eleitor', 0, 'ativo'),
('Certificado de Reservista', 'Certificado de reservista (para homens)', 0, 'ativo');

-- Tabela de documentos dos alunos
CREATE TABLE IF NOT EXISTS `documentos_alunos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aluno_id` int(11) NOT NULL,
  `tipo_documento_id` int(11) NOT NULL,
  `arquivo` varchar(255) NOT NULL,
  `data_upload` datetime NOT NULL DEFAULT current_timestamp(),
  `data_validade` date DEFAULT NULL,
  `numero_documento` varchar(100) DEFAULT NULL,
  `orgao_emissor` varchar(100) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `status` enum('pendente','aprovado','rejeitado') NOT NULL DEFAULT 'pendente',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `aluno_id` (`aluno_id`),
  KEY `tipo_documento_id` (`tipo_documento_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
