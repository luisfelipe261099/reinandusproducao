-- Adiciona o campo mec na tabela polos
ALTER TABLE polos ADD COLUMN mec VARCHAR(255) NULL COMMENT 'Nome do polo registrado no MEC';

-- Atualiza o campo mec com o valor do campo nome para todos os polos
UPDATE polos SET mec = nome WHERE mec IS NULL;
