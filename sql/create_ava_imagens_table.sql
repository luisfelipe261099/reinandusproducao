-- Tabela para armazenar as imagens enviadas para o AVA
CREATE TABLE IF NOT EXISTS `ava_imagens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `polo_id` int(11) NOT NULL,
  `aula_id` int(11) DEFAULT NULL,
  `arquivo_path` varchar(255) NOT NULL,
  `arquivo_nome` varchar(255) NOT NULL,
  `arquivo_tipo` varchar(100) NOT NULL,
  `arquivo_tamanho` int(11) NOT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `polo_id` (`polo_id`),
  KEY `aula_id` (`aula_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
