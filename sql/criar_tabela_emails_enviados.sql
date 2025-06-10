-- Script para criar a tabela emails_enviados
CREATE TABLE IF NOT EXISTS `emails_enviados` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `solicitacao_id` int(11) NOT NULL,
  `assunto` varchar(255) NOT NULL,
  `mensagem` text NOT NULL,
  `arquivo_nome` varchar(255) DEFAULT NULL,
  `data_envio` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `usuario_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `solicitacao_id` (`solicitacao_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `fk_emails_solicitacao` FOREIGN KEY (`solicitacao_id`) REFERENCES `solicitacoes_s` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_emails_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
