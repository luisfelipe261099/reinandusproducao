-- Script para criar a tabela de configurações

CREATE TABLE IF NOT EXISTS `configuracoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chave` varchar(100) NOT NULL,
  `valor` text NOT NULL,
  `descricao` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `chave_unique` (`chave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Inserir configuração padrão para o ambiente da API do Itaú
INSERT INTO `configuracoes` (`chave`, `valor`, `descricao`)
VALUES ('api_itau_ambiente', 'teste', 'Ambiente da API do Itaú (teste ou producao)');

-- Inserir configuração para a URL do token no ambiente de teste
INSERT INTO `configuracoes` (`chave`, `valor`, `descricao`)
VALUES ('api_itau_token_url_teste', 'https://sts.itau.com.br/api/oauth/token', 'URL para obtenção de token no ambiente de teste do Itaú');

-- Inserir configuração para a URL do token no ambiente de produção
INSERT INTO `configuracoes` (`chave`, `valor`, `descricao`)
VALUES ('api_itau_token_url_producao', 'https://api.itau.com.br/api/oauth/token', 'URL para obtenção de token no ambiente de produção do Itaú');

-- Inserir configuração para a URL base da API cash_management no ambiente de teste
INSERT INTO `configuracoes` (`chave`, `valor`, `descricao`)
VALUES ('api_itau_cash_management_url_teste', 'https://api.itau.com.br/cash_management/v2/boletos', 'URL base da API cash_management no ambiente de teste do Itaú');

-- Inserir configuração para a URL base da API cash_management no ambiente de produção
INSERT INTO `configuracoes` (`chave`, `valor`, `descricao`)
VALUES ('api_itau_cash_management_url_producao', 'https://api.itau.com.br/cash_management/v2/boletos', 'URL base da API cash_management no ambiente de produção do Itaú');

-- Inserir configuração para a URL base da API cobranca no ambiente de teste
INSERT INTO `configuracoes` (`chave`, `valor`, `descricao`)
VALUES ('api_itau_cobranca_url_teste', 'https://api.itau.com.br/cobranca/v2/boletos', 'URL base da API cobranca no ambiente de teste do Itaú');

-- Inserir configuração para a URL base da API cobranca no ambiente de produção
INSERT INTO `configuracoes` (`chave`, `valor`, `descricao`)
VALUES ('api_itau_cobranca_url_producao', 'https://api.itau.com.br/cobranca/v2/boletos', 'URL base da API cobranca no ambiente de produção do Itaú');
