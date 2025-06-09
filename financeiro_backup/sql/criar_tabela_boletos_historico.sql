-- Tabela para registrar o histórico de ações em boletos
CREATE TABLE IF NOT EXISTS `boletos_historico` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `boleto_id` int(11) NOT NULL,
  `acao` varchar(50) NOT NULL COMMENT 'cancelamento, cancelamento_local, emissao, pagamento, etc',
  `data` datetime NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `detalhes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `boleto_id` (`boleto_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `boletos_historico_ibfk_1` FOREIGN KEY (`boleto_id`) REFERENCES `boletos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `boletos_historico_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Adicionar campo observacoes à tabela boletos se não existir
ALTER TABLE `boletos` ADD COLUMN IF NOT EXISTS `observacoes` text DEFAULT NULL AFTER `status`;
