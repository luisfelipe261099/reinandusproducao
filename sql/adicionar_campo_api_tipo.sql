-- Adiciona o campo api_tipo à tabela de boletos
ALTER TABLE `boletos` ADD COLUMN `api_tipo` VARCHAR(50) DEFAULT 'cash_management' COMMENT 'Tipo de API usada para gerar o boleto (cash_management ou cobranca)' AFTER `api_ambiente`;
