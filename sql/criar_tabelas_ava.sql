-- Script para criar as tabelas necessárias para o Ambiente Virtual de Aprendizagem (AVA)
-- Este script deve ser executado no banco de dados

-- Tabela para controlar quais polos têm acesso ao AVA
CREATE TABLE IF NOT EXISTS `ava_polos_acesso` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `polo_id` int(11) NOT NULL,
  `liberado` tinyint(1) NOT NULL DEFAULT 0,
  `data_liberacao` datetime DEFAULT NULL,
  `liberado_por` int(11) DEFAULT NULL, -- ID do usuário da secretaria que liberou
  `observacoes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `polo_id` (`polo_id`),
  KEY `liberado_por` (`liberado_por`),
  CONSTRAINT `fk_ava_polos_acesso_polo` FOREIGN KEY (`polo_id`) REFERENCES `polos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ava_polos_acesso_usuario` FOREIGN KEY (`liberado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de cursos do AVA
CREATE TABLE IF NOT EXISTS `ava_cursos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `polo_id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `carga_horaria` int(11) DEFAULT NULL,
  `categoria` varchar(100) DEFAULT NULL,
  `nivel` enum('basico', 'intermediario', 'avancado') DEFAULT 'basico',
  `imagem` varchar(255) DEFAULT NULL,
  `video_apresentacao` varchar(255) DEFAULT NULL,
  `objetivos` text DEFAULT NULL,
  `publico_alvo` text DEFAULT NULL,
  `pre_requisitos` text DEFAULT NULL,
  `status` enum('rascunho', 'revisao', 'publicado', 'arquivado') DEFAULT 'rascunho',
  `data_publicacao` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `polo_id` (`polo_id`),
  KEY `status` (`status`),
  CONSTRAINT `fk_ava_cursos_polo` FOREIGN KEY (`polo_id`) REFERENCES `polos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de módulos dos cursos
CREATE TABLE IF NOT EXISTS `ava_modulos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `curso_id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `ordem` int(11) NOT NULL DEFAULT 0,
  `status` enum('ativo', 'inativo') DEFAULT 'ativo',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `curso_id` (`curso_id`),
  CONSTRAINT `fk_ava_modulos_curso` FOREIGN KEY (`curso_id`) REFERENCES `ava_cursos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de aulas dos módulos
CREATE TABLE IF NOT EXISTS `ava_aulas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `modulo_id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `tipo` enum('video', 'texto', 'quiz', 'arquivo', 'link') NOT NULL,
  `conteudo` text DEFAULT NULL, -- Para aulas do tipo texto
  `video_url` varchar(255) DEFAULT NULL, -- Para aulas do tipo vídeo
  `arquivo_path` varchar(255) DEFAULT NULL, -- Para aulas do tipo arquivo
  `link_url` varchar(255) DEFAULT NULL, -- Para aulas do tipo link
  `duracao_minutos` int(11) DEFAULT NULL, -- Para aulas do tipo vídeo
  `ordem` int(11) NOT NULL DEFAULT 0,
  `status` enum('ativo', 'inativo') DEFAULT 'ativo',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `modulo_id` (`modulo_id`),
  CONSTRAINT `fk_ava_aulas_modulo` FOREIGN KEY (`modulo_id`) REFERENCES `ava_modulos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de materiais complementares
CREATE TABLE IF NOT EXISTS `ava_materiais` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aula_id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `tipo` enum('pdf', 'doc', 'xls', 'ppt', 'zip', 'imagem', 'audio', 'video', 'link') NOT NULL,
  `arquivo_path` varchar(255) DEFAULT NULL,
  `link_url` varchar(255) DEFAULT NULL,
  `ordem` int(11) NOT NULL DEFAULT 0,
  `status` enum('ativo', 'inativo') DEFAULT 'ativo',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `aula_id` (`aula_id`),
  CONSTRAINT `fk_ava_materiais_aula` FOREIGN KEY (`aula_id`) REFERENCES `ava_aulas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de questões para quizzes
CREATE TABLE IF NOT EXISTS `ava_questoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aula_id` int(11) NOT NULL,
  `pergunta` text NOT NULL,
  `tipo` enum('multipla_escolha', 'verdadeiro_falso', 'resposta_curta', 'correspondencia') NOT NULL,
  `opcoes` text DEFAULT NULL, -- JSON com as opções para múltipla escolha
  `resposta_correta` text NOT NULL, -- Resposta correta ou JSON com respostas
  `explicacao` text DEFAULT NULL, -- Explicação da resposta
  `pontos` int(11) NOT NULL DEFAULT 1,
  `ordem` int(11) NOT NULL DEFAULT 0,
  `status` enum('ativo', 'inativo') DEFAULT 'ativo',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `aula_id` (`aula_id`),
  CONSTRAINT `fk_ava_questoes_aula` FOREIGN KEY (`aula_id`) REFERENCES `ava_aulas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de matrículas de alunos nos cursos do AVA
CREATE TABLE IF NOT EXISTS `ava_matriculas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aluno_id` int(11) NOT NULL,
  `curso_id` int(11) NOT NULL,
  `data_matricula` datetime NOT NULL DEFAULT current_timestamp(),
  `status` enum('ativo', 'concluido', 'cancelado', 'trancado') DEFAULT 'ativo',
  `progresso` int(11) NOT NULL DEFAULT 0, -- Porcentagem de conclusão (0-100)
  `data_conclusao` datetime DEFAULT NULL,
  `nota_final` decimal(5,2) DEFAULT NULL,
  `certificado_emitido` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `aluno_curso` (`aluno_id`, `curso_id`),
  KEY `aluno_id` (`aluno_id`),
  KEY `curso_id` (`curso_id`),
  CONSTRAINT `fk_ava_matriculas_aluno` FOREIGN KEY (`aluno_id`) REFERENCES `alunos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ava_matriculas_curso` FOREIGN KEY (`curso_id`) REFERENCES `ava_cursos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de progresso dos alunos nas aulas
CREATE TABLE IF NOT EXISTS `ava_progresso_aulas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `matricula_id` int(11) NOT NULL,
  `aula_id` int(11) NOT NULL,
  `status` enum('nao_iniciada', 'em_andamento', 'concluida') DEFAULT 'nao_iniciada',
  `data_inicio` datetime DEFAULT NULL,
  `data_conclusao` datetime DEFAULT NULL,
  `tempo_total_segundos` int(11) DEFAULT 0,
  `nota` decimal(5,2) DEFAULT NULL, -- Para aulas do tipo quiz
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `matricula_aula` (`matricula_id`, `aula_id`),
  KEY `matricula_id` (`matricula_id`),
  KEY `aula_id` (`aula_id`),
  CONSTRAINT `fk_ava_progresso_aulas_matricula` FOREIGN KEY (`matricula_id`) REFERENCES `ava_matriculas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ava_progresso_aulas_aula` FOREIGN KEY (`aula_id`) REFERENCES `ava_aulas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de respostas dos alunos às questões
CREATE TABLE IF NOT EXISTS `ava_respostas_alunos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `progresso_aula_id` int(11) NOT NULL,
  `questao_id` int(11) NOT NULL,
  `resposta` text NOT NULL,
  `correta` tinyint(1) NOT NULL DEFAULT 0,
  `pontos_obtidos` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `progresso_questao` (`progresso_aula_id`, `questao_id`),
  KEY `progresso_aula_id` (`progresso_aula_id`),
  KEY `questao_id` (`questao_id`),
  CONSTRAINT `fk_ava_respostas_alunos_progresso` FOREIGN KEY (`progresso_aula_id`) REFERENCES `ava_progresso_aulas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ava_respostas_alunos_questao` FOREIGN KEY (`questao_id`) REFERENCES `ava_questoes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de certificados emitidos
CREATE TABLE IF NOT EXISTS `ava_certificados` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `matricula_id` int(11) NOT NULL,
  `codigo` varchar(50) NOT NULL,
  `data_emissao` datetime NOT NULL,
  `arquivo_path` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `matricula_id` (`matricula_id`),
  UNIQUE KEY `codigo` (`codigo`),
  CONSTRAINT `fk_ava_certificados_matricula` FOREIGN KEY (`matricula_id`) REFERENCES `ava_matriculas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de categorias de cursos
CREATE TABLE IF NOT EXISTS `ava_categorias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `cor` varchar(20) DEFAULT '#6A5ACD', -- Cor padrão roxa
  `icone` varchar(50) DEFAULT 'fas fa-book',
  `ordem` int(11) NOT NULL DEFAULT 0,
  `status` enum('ativo', 'inativo') DEFAULT 'ativo',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nome` (`nome`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir categorias padrão
INSERT INTO `ava_categorias` (`nome`, `descricao`, `cor`, `icone`, `ordem`, `status`) VALUES
('Tecnologia', 'Cursos de tecnologia e programação', '#6A5ACD', 'fas fa-laptop-code', 1, 'ativo'),
('Negócios', 'Cursos de administração e negócios', '#4682B4', 'fas fa-briefcase', 2, 'ativo'),
('Marketing', 'Cursos de marketing e vendas', '#2E8B57', 'fas fa-bullhorn', 3, 'ativo'),
('Design', 'Cursos de design gráfico e UX/UI', '#CD5C5C', 'fas fa-paint-brush', 4, 'ativo'),
('Idiomas', 'Cursos de idiomas', '#DAA520', 'fas fa-language', 5, 'ativo'),
('Saúde', 'Cursos da área de saúde', '#20B2AA', 'fas fa-heartbeat', 6, 'ativo'),
('Educação', 'Cursos para educadores', '#9370DB', 'fas fa-graduation-cap', 7, 'ativo');

-- Adicionar permissões para o AVA
INSERT INTO `modulos` (`nome`, `descricao`, `icone`, `ordem`, `status`)
SELECT 'ava', 'Ambiente Virtual de Aprendizagem', 'fas fa-laptop', 8, 'ativo'
WHERE NOT EXISTS (SELECT 1 FROM `modulos` WHERE `nome` = 'ava');
