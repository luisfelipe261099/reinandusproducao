-- Adiciona o módulo de secretaria às permissões
INSERT INTO modulos (nome, descricao, icone, ordem, status)
SELECT 'secretaria', 'Secretaria Acadêmica', 'fas fa-university', 1, 'ativo'
WHERE NOT EXISTS (SELECT 1 FROM modulos WHERE nome = 'secretaria');

-- Adiciona permissões para o módulo de secretaria para administradores
INSERT INTO permissoes (usuario_id, modulo, visualizar, criar, editar, excluir)
SELECT u.id, 'secretaria', 1, 1, 1, 1
FROM usuarios u
WHERE u.tipo = 'administrador'
AND NOT EXISTS (SELECT 1 FROM permissoes WHERE usuario_id = u.id AND modulo = 'secretaria');

-- Adiciona permissões para o módulo de secretaria para secretários
INSERT INTO permissoes (usuario_id, modulo, visualizar, criar, editar, excluir)
SELECT u.id, 'secretaria', 1, 1, 1, 0
FROM usuarios u
WHERE u.tipo = 'secretario'
AND NOT EXISTS (SELECT 1 FROM permissoes WHERE usuario_id = u.id AND modulo = 'secretaria');
