-- ============================================================================
-- SQL PARA CORRIGIR POLO_ID NAS MATRÍCULAS BASEADO NO CURSO
-- ============================================================================
-- 
-- Este script atualiza o campo polo_id da tabela matriculas 
-- baseado no polo_id configurado na tabela cursos
--
-- IMPORTANTE: Faça backup antes de executar!
--
-- ============================================================================

-- 1. VERIFICAR DADOS ANTES DA CORREÇÃO
-- ============================================================================

-- Verificar matrículas sem polo ou com polo incorreto
SELECT 
    m.id as matricula_id,
    m.aluno_id,
    m.curso_id,
    m.polo_id as polo_atual_matricula,
    c.nome as curso_nome,
    c.polo_id as polo_correto_do_curso,
    p.nome as nome_polo_correto
FROM matriculas m
LEFT JOIN cursos c ON m.curso_id = c.id
LEFT JOIN polos p ON c.polo_id = p.id
WHERE m.polo_id IS NULL 
   OR m.polo_id != c.polo_id
ORDER BY m.id;

-- Contar quantas matrículas serão afetadas
SELECT COUNT(*) as total_matriculas_para_corrigir
FROM matriculas m
LEFT JOIN cursos c ON m.curso_id = c.id
WHERE m.polo_id IS NULL 
   OR m.polo_id != c.polo_id;

-- ============================================================================
-- 2. SQL DE CORREÇÃO PRINCIPAL
-- ============================================================================

-- Atualizar polo_id nas matrículas baseado no curso
UPDATE matriculas m
INNER JOIN cursos c ON m.curso_id = c.id
SET m.polo_id = c.polo_id
WHERE m.polo_id IS NULL 
   OR m.polo_id != c.polo_id;

-- ============================================================================
-- 3. VERIFICAR RESULTADOS APÓS A CORREÇÃO
-- ============================================================================

-- Verificar se ainda há matrículas sem polo
SELECT COUNT(*) as matriculas_sem_polo
FROM matriculas 
WHERE polo_id IS NULL;

-- Verificar algumas matrículas corrigidas
SELECT 
    m.id as matricula_id,
    m.aluno_id,
    c.nome as curso_nome,
    p.nome as polo_nome,
    m.polo_id
FROM matriculas m
LEFT JOIN cursos c ON m.curso_id = c.id
LEFT JOIN polos p ON m.polo_id = p.id
ORDER BY m.id DESC
LIMIT 10;

-- ============================================================================
-- 4. SQL ALTERNATIVO PARA CASOS ESPECÍFICOS
-- ============================================================================

-- Se algum curso não tiver polo_id configurado, 
-- você pode definir um polo padrão (substitua X pelo ID do polo padrão)
/*
UPDATE matriculas m
INNER JOIN cursos c ON m.curso_id = c.id
SET m.polo_id = COALESCE(c.polo_id, 1) -- substitua 1 pelo ID do polo padrão
WHERE m.polo_id IS NULL;
*/

-- ============================================================================
-- 5. RELATÓRIO FINAL
-- ============================================================================

-- Relatório completo após correção
SELECT 
    p.nome as polo_nome,
    COUNT(m.id) as total_matriculas,
    COUNT(CASE WHEN m.status = 'ativo' THEN 1 END) as matriculas_ativas
FROM matriculas m
LEFT JOIN polos p ON m.polo_id = p.id
GROUP BY m.polo_id, p.nome
ORDER BY total_matriculas DESC;

-- Verificar se há cursos sem polo configurado
SELECT 
    c.id,
    c.nome,
    c.polo_id
FROM cursos c
WHERE c.polo_id IS NULL
ORDER BY c.nome;
