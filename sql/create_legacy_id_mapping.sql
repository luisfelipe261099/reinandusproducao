-- Tabela para mapeamento de IDs legados
CREATE TABLE IF NOT EXISTS `mapeamento_ids_legados` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entidade` varchar(50) NOT NULL COMMENT 'Nome da tabela/entidade',
  `id_atual` int(11) NOT NULL COMMENT 'ID no sistema atual',
  `id_legado` varchar(50) NOT NULL COMMENT 'ID no sistema legado',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_entidade_id_atual` (`entidade`, `id_atual`),
  UNIQUE KEY `uk_entidade_id_legado` (`entidade`, `id_legado`),
  KEY `idx_entidade` (`entidade`),
  KEY `idx_id_atual` (`id_atual`),
  KEY `idx_id_legado` (`id_legado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Mapeamento entre IDs do sistema atual e IDs do sistema legado';
