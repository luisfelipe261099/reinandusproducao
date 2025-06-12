-- ============================================================================
-- SQL PARA CORRIGIR POLO_ID NAS MATRÍCULAS (VERSÃO SEGURA)
-- ============================================================================
-- 
-- Este script corrige o problema de foreign key constraint
-- atualizando apenas com polos que realmente existem
--
-- IMPORTANTE: Execute passo a passo para verificar os resultados!
--
-- ============================================================================

-- 1. DIAGNÓSTICO DO PROBLEMA
-- ============================================================================

-- Verificar cursos que têm polo_id que não existe na tabela polos
SELECT 
    c.id as curso_id,
    c.nome as curso_nome,
    c.polo_id as polo_id_curso,
    p.id as polo_existe
FROM cursos c
LEFT JOIN polos p ON c.polo_id = p.id
WHERE c.polo_id IS NOT NULL 
  AND p.id IS NULL;

-- Verificar quais polos realmente existem
SELECT id, nome, status FROM polos ORDER BY id;

-- Verificar matrículas que precisam ser corrigidas
SELECT 
    m.id as matricula_id,
    m.curso_id,
    m.polo_id as polo_atual,
    c.polo_id as polo_do_curso,
    c.nome as curso_nome,
    p.nome as polo_nome
FROM matriculas m
INNER JOIN cursos c ON m.curso_id = c.id
LEFT JOIN polos p ON c.polo_id = p.id
WHERE (m.polo_id IS NULL OR m.polo_id != c.polo_id)
  AND c.polo_id IS NOT NULL
  AND p.id IS NOT NULL  -- Só mostra se o polo realmente existe
ORDER BY m.id;

-- ============================================================================
-- 2. CORREÇÃO SEGURA - APENAS POLOS QUE EXISTEM
-- ============================================================================

-- Atualizar polo_id nas matrículas APENAS quando o polo do curso realmente existe
UPDATE matriculas m
INNER JOIN cursos c ON m.curso_id = c.id
INNER JOIN polos p ON c.polo_id = p.id  -- INNER JOIN garante que o polo existe
SET m.polo_id = c.polo_id
WHERE (m.polo_id IS NULL OR m.polo_id != c.polo_id);

-- ============================================================================
-- 3. CORREÇÃO DOS CURSOS COM POLO_ID INVÁLIDO
-- ============================================================================

-- Primeiro, vamos ver quais cursos têm polo_id inválido
SELECT 
    c.id,
    c.nome,
    c.polo_id as polo_invalido
FROM cursos c
LEFT JOIN polos p ON c.polo_id = p.id
WHERE c.polo_id IS NOT NULL AND p.id IS NULL;

-- Opção A: Definir um polo padrão para cursos com polo_id inválido
-- (Substitua '1' pelo ID de um polo que realmente existe)
/*
UPDATE cursos c
LEFT JOIN polos p ON c.polo_id = p.id
SET c.polo_id = 1  -- Substitua pelo ID de um polo válido
WHERE c.polo_id IS NOT NULL AND p.id IS NULL;
*/

-- Opção B: Limpar polo_id inválido (definir como NULL)
/*
UPDATE cursos c
LEFT JOIN polos p ON c.polo_id = p.id
SET c.polo_id = NULL
WHERE c.polo_id IS NOT NULL AND p.id IS NULL;
*/

-- ============================================================================
-- 4. VERIFICAÇÃO FINAL
-- ============================================================================

-- Verificar matrículas ainda sem polo
SELECT COUNT(*) as matriculas_sem_polo
FROM matriculas 
WHERE polo_id IS NULL;

-- Relatório de matrículas por polo
SELECT 
    p.id as polo_id,
    p.nome as polo_nome,
    COUNT(m.id) as total_matriculas
FROM polos p
LEFT JOIN matriculas m ON p.id = m.polo_id
GROUP BY p.id, p.nome
ORDER BY total_matriculas DESC;

-- Verificar se ainda há problemas de integridade
SELECT 
    m.id as matricula_id,
    m.polo_id,
    p.nome as polo_nome
FROM matriculas m
LEFT JOIN polos p ON m.polo_id = p.id
WHERE m.polo_id IS NOT NULL AND p.id IS NULL
LIMIT 10;

-- ============================================================================
-- 5. SCRIPT PARA CRIAR UM POLO PADRÃO (SE NECESSÁRIO)
-- ============================================================================

-- Se você não tem nenhum polo cadastrado, execute isso primeiro:
/*
INSERT INTO polos (nome, cidade, estado, status) 
VALUES ('Polo Principal', 'Cidade Principal', 'Estado', 'ativo');

-- Depois obtenha o ID do polo criado:
SELECT id FROM polos WHERE nome = 'Polo Principal';

-- E use esse ID para corrigir os cursos:
UPDATE cursos SET polo_id = [ID_DO_POLO_CRIADO] WHERE polo_id IS NULL;
*/
