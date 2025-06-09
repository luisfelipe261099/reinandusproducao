-- Adiciona o módulo de notas às permissões
INSERT INTO modulos (nome, descricao, icone, ordem, status)
SELECT 'notas', 'Notas e Frequências', 'fas fa-clipboard-list', 7, 'ativo'
WHERE NOT EXISTS (SELECT 1 FROM modulos WHERE nome = 'notas');

-- Adiciona permissões para o módulo de notas para administradores
INSERT INTO permissoes (usuario_id, modulo, visualizar, criar, editar, excluir)
SELECT u.id, 'notas', 1, 1, 1, 1
FROM usuarios u
WHERE u.tipo = 'administrador'
AND NOT EXISTS (SELECT 1 FROM permissoes WHERE usuario_id = u.id AND modulo = 'notas');

-- Adiciona permissões para o módulo de notas para professores (apenas visualizar e editar)
INSERT INTO permissoes (usuario_id, modulo, visualizar, criar, editar, excluir)
SELECT u.id, 'notas', 1, 0, 1, 0
FROM usuarios u
WHERE u.tipo = 'professor'
AND NOT EXISTS (SELECT 1 FROM permissoes WHERE usuario_id = u.id AND modulo = 'notas');

-- Adiciona permissões para o módulo de notas para secretários
INSERT INTO permissoes (usuario_id, modulo, visualizar, criar, editar, excluir)
SELECT u.id, 'notas', 1, 1, 1, 0
FROM usuarios u
WHERE u.tipo = 'secretario'
AND NOT EXISTS (SELECT 1 FROM permissoes WHERE usuario_id = u.id AND modulo = 'notas');
