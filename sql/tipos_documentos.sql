-- Tabela de tipos de documentos
CREATE TABLE IF NOT EXISTS `tipos_documentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `status` enum('ativo','inativo') NOT NULL DEFAULT 'ativo',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir alguns tipos de documentos padrão
INSERT INTO `tipos_documentos` (`nome`, `descricao`, `status`) VALUES
('RG', 'Registro Geral', 'ativo'),
('CPF', 'Cadastro de Pessoa Física', 'ativo'),
('Certidão de Nascimento', 'Certidão de Nascimento', 'ativo'),
('Histórico Escolar', 'Histórico Escolar', 'ativo'),
('Diploma', 'Diploma de Conclusão de Curso', 'ativo'),
('Certificado', 'Certificado de Conclusão de Curso', 'ativo'),
('Comprovante de Residência', 'Comprovante de Residência', 'ativo'),
('Título de Eleitor', 'Título de Eleitor', 'ativo'),
('Carteira de Trabalho', 'Carteira de Trabalho e Previdência Social', 'ativo'),
('Passaporte', 'Passaporte', 'ativo'),
('CNH', 'Carteira Nacional de Habilitação', 'ativo'),
('Atestado Médico', 'Atestado Médico', 'ativo'),
('Declaração', 'Declaração Diversa', 'ativo'),
('Contrato', 'Contrato', 'ativo'),
('Procuração', 'Procuração', 'ativo'),
('Outros', 'Outros Documentos', 'ativo');

-- Tabela de documentos
CREATE TABLE IF NOT EXISTS `documentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) NOT NULL,
  `tipo_documento_id` int(11) NOT NULL,
  `aluno_id` int(11) DEFAULT NULL,
  `numero` varchar(100) DEFAULT NULL,
  `data_emissao` date DEFAULT NULL,
  `data_validade` date DEFAULT NULL,
  `orgao_emissor` varchar(100) DEFAULT NULL,
  `arquivo_path` varchar(255) DEFAULT NULL,
  `arquivo_nome` varchar(255) DEFAULT NULL,
  `arquivo_tipo` varchar(100) DEFAULT NULL,
  `arquivo_tamanho` int(11) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `status` enum('ativo','inativo') NOT NULL DEFAULT 'ativo',
  `id_legado` varchar(50) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `tipo_documento_id` (`tipo_documento_id`),
  KEY `aluno_id` (`aluno_id`),
  CONSTRAINT `documentos_ibfk_1` FOREIGN KEY (`tipo_documento_id`) REFERENCES `tipos_documentos` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `documentos_ibfk_2` FOREIGN KEY (`aluno_id`) REFERENCES `alunos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
