-- SQL para criar o primeiro usuário administrador master
-- Execute este script no seu banco de dados para criar o primeiro usuário admin_master

-- Dados do usuário administrador
-- Email: admin@faciencia.com
-- Senha: Admin@123
-- Tipo: admin_master

INSERT INTO usuarios (
    nome, 
    email, 
    senha, 
    tipo, 
    status, 
    created_at, 
    updated_at
) VALUES (
    'Administrador Master',
    'admin@faciencia.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- Senha: Admin@123
    'admin_master',
    'ativo',
    NOW(),
    NOW()
);

-- Verificar se o usuário foi criado corretamente
SELECT id, nome, email, tipo, status, created_at 
FROM usuarios 
WHERE email = 'admin@faciencia.com';

-- IMPORTANTE:
-- 1. A senha padrão é: Admin@123
-- 2. Faça login usando:
--    - Email: admin@faciencia.com
--    - Senha: Admin@123
-- 3. Após o primeiro login, recomenda-se alterar a senha através do módulo administrador
-- 4. O usuário será redirecionado automaticamente para: administrador/index.php

-- Para gerar uma nova senha hash, use este PHP:
-- echo password_hash('SuaNovaSenha', PASSWORD_DEFAULT);
