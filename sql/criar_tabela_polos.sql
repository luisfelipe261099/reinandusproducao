-- Criação da tabela de polos
CREATE TABLE IF NOT EXISTS `polos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `responsavel` varchar(255) NOT NULL,
  `telefone` varchar(20) NOT NULL,
  `email` varchar(255) NOT NULL,
  `cep` varchar(10) NOT NULL,
  `endereco` varchar(255) NOT NULL,
  `numero` varchar(20) NOT NULL,
  `complemento` varchar(255) DEFAULT NULL,
  `bairro` varchar(255) NOT NULL,
  `cidade` varchar(255) NOT NULL,
  `estado` char(2) NOT NULL,
  `observacoes` text DEFAULT NULL,
  `status` enum('ativo','inativo') NOT NULL DEFAULT 'ativo',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Adiciona a coluna polo_id na tabela cursos, se não existir
ALTER TABLE `cursos` ADD COLUMN IF NOT EXISTS `polo_id` int(11) DEFAULT NULL;

-- Adiciona a chave estrangeira para a tabela polos
ALTER TABLE `cursos` ADD CONSTRAINT IF NOT EXISTS `fk_cursos_polo` FOREIGN KEY (`polo_id`) REFERENCES `polos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- Adiciona o módulo de polos nas permissões
INSERT INTO `modulos` (`nome`, `descricao`, `icone`, `ordem`, `status`)
SELECT 'polos', 'Polos Educacionais', 'fas fa-university', 5, 'ativo'
WHERE NOT EXISTS (SELECT 1 FROM `modulos` WHERE `nome` = 'polos');

-- Adiciona permissões para o módulo de polos para administradores
INSERT INTO `permissoes` (`usuario_id`, `modulo`, `visualizar`, `criar`, `editar`, `excluir`)
SELECT u.id, 'polos', 1, 1, 1, 1
FROM `usuarios` u
WHERE u.tipo = 'administrador'
AND NOT EXISTS (SELECT 1 FROM `permissoes` WHERE `usuario_id` = u.id AND `modulo` = 'polos');

-- Adiciona permissões para o módulo de polos para secretários
INSERT INTO `permissoes` (`usuario_id`, `modulo`, `visualizar`, `criar`, `editar`, `excluir`)
SELECT u.id, 'polos', 1, 1, 1, 0
FROM `usuarios` u
WHERE u.tipo = 'secretario'
AND NOT EXISTS (SELECT 1 FROM `permissoes` WHERE `usuario_id` = u.id AND `modulo` = 'polos');
