-- Script para atualizar a estrutura da tabela matriculas para permitir múltiplas matrículas
-- Este script remove a restrição de unicidade (aluno_id, curso_id) para permitir que um aluno
-- possa estar matriculado em múltiplos cursos e polos

-- Verifica se existe a restrição de unicidade na tabela matriculas
SET @constraint_name = NULL;
SELECT CONSTRAINT_NAME INTO @constraint_name
FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
WHERE TABLE_NAME = 'matriculas'
AND CONSTRAINT_TYPE = 'UNIQUE'
AND CONSTRAINT_SCHEMA = DATABASE()
AND CONSTRAINT_NAME = 'uk_aluno_curso';

-- Remove a restrição de unicidade se existir
SET @sql = IF(@constraint_name IS NOT NULL,
              CONCAT('ALTER TABLE matriculas DROP INDEX ', @constraint_name, ';'),
              'SELECT "Restrição uk_aluno_curso não encontrada" AS message;');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adiciona um índice não único para manter a performance de consultas
ALTER TABLE matriculas ADD INDEX idx_aluno_curso (aluno_id, curso_id);

-- Cria uma tabela para armazenar as matrículas ativas do aluno (opcional)
CREATE TABLE IF NOT EXISTS alunos_matriculas_ativas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  aluno_id INT UNSIGNED NOT NULL,
  matricula_id INT UNSIGNED NOT NULL,
  is_primary BOOLEAN NOT NULL DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_aluno_matricula (aluno_id, matricula_id),
  INDEX idx_aluno_id (aluno_id),
  INDEX idx_matricula_id (matricula_id)
);

-- Adiciona comentário para explicar a mudança
SELECT 'Estrutura da tabela matriculas atualizada para permitir múltiplas matrículas por aluno' AS message;
SELECT 'Tabela alunos_matriculas_ativas criada para rastrear matrículas principais do aluno' AS message;
