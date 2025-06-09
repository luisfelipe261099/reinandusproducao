-- Script para atualizar a tabela polos_financeiro com os campos necessários
-- Executar este script para adicionar os campos necessários para o funcionamento do financeiro dos polos

-- Verifica se a tabela existe, se não existir, cria
CREATE TABLE IF NOT EXISTS `polos_financeiro` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `polo_id` int(11) NOT NULL,
  `tipo_polo_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `polo_id` (`polo_id`),
  KEY `tipo_polo_id` (`tipo_polo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Adiciona os campos necessários se não existirem
-- Campos de datas
ALTER TABLE `polos_financeiro` ADD COLUMN IF NOT EXISTS `data_inicial` date DEFAULT NULL;
ALTER TABLE `polos_financeiro` ADD COLUMN IF NOT EXISTS `vencimento_contrato` date DEFAULT NULL;
ALTER TABLE `polos_financeiro` ADD COLUMN IF NOT EXISTS `vencimento_pacote_setup` date DEFAULT NULL;
ALTER TABLE `polos_financeiro` ADD COLUMN IF NOT EXISTS `data_primeira_parcela` date DEFAULT NULL;
ALTER TABLE `polos_financeiro` ADD COLUMN IF NOT EXISTS `data_ultima_parcela` date DEFAULT NULL;

-- Campos numéricos inteiros
ALTER TABLE `polos_financeiro` ADD COLUMN IF NOT EXISTS `vigencia_contrato_meses` int(11) DEFAULT NULL;
ALTER TABLE `polos_financeiro` ADD COLUMN IF NOT EXISTS `vigencia_pacote_setup` int(11) DEFAULT NULL;
ALTER TABLE `polos_financeiro` ADD COLUMN IF NOT EXISTS `pacotes_adquiridos` int(11) DEFAULT 0;
ALTER TABLE `polos_financeiro` ADD COLUMN IF NOT EXISTS `documentos_disponiveis` int(11) DEFAULT 0;
ALTER TABLE `polos_financeiro` ADD COLUMN IF NOT EXISTS `quantidade_contratada` int(11) DEFAULT NULL;
ALTER TABLE `polos_financeiro` ADD COLUMN IF NOT EXISTS `quantidade_parcelas` int(11) DEFAULT NULL;

-- Campos numéricos decimais
ALTER TABLE `polos_financeiro` ADD COLUMN IF NOT EXISTS `valor_unitario_normal` decimal(10,2) DEFAULT NULL;
ALTER TABLE `polos_financeiro` ADD COLUMN IF NOT EXISTS `valor_previsto` decimal(10,2) DEFAULT NULL;

-- Campos de texto
ALTER TABLE `polos_financeiro` ADD COLUMN IF NOT EXISTS `observacoes` text DEFAULT NULL;

-- Campos para compatibilidade com versões anteriores
ALTER TABLE `polos_financeiro` ADD COLUMN IF NOT EXISTS `taxa_inicial` decimal(10,2) DEFAULT NULL;
ALTER TABLE `polos_financeiro` ADD COLUMN IF NOT EXISTS `valor_por_documento` decimal(10,2) DEFAULT NULL;
ALTER TABLE `polos_financeiro` ADD COLUMN IF NOT EXISTS `taxa_inicial_paga` tinyint(1) DEFAULT 0;
ALTER TABLE `polos_financeiro` ADD COLUMN IF NOT EXISTS `data_pagamento_taxa` date DEFAULT NULL;
ALTER TABLE `polos_financeiro` ADD COLUMN IF NOT EXISTS `documentos_emitidos` int(11) DEFAULT 0;
ALTER TABLE `polos_financeiro` ADD COLUMN IF NOT EXISTS `valor_total_pago` decimal(10,2) DEFAULT 0.00;
