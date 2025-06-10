# INSTALAÇÃO COMPLETA DO MÓDULO ADMINISTRADOR

## 🎯 GUIA RÁPIDO DE INSTALAÇÃO

### Passo 1: Verificar Estrutura
✅ A pasta `administrador/` já está criada no diretório raiz  
✅ Todas as pastas necessárias (`backups/`, `temp/`) foram criadas  
✅ Todos os arquivos estão no lugar correto  

### Passo 2: Configurar Banco de Dados
Execute o SQL para criar o primeiro usuário administrador:

```sql
-- Execute no seu banco MySQL
USE seu_banco_faciencia;
source administrador/criar_admin_master.sql;
```

**OU copie e cole no phpMyAdmin:**
```sql
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
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin_master',
    'ativo',
    NOW(),
    NOW()
);
```

### Passo 3: Configurar Permissões
Configure as permissões das pastas (Linux/Mac):
```bash
chmod 755 administrador/
chmod 777 backups/
chmod 777 temp/
```

No Windows: Clique com botão direito → Propriedades → Segurança → Permitir controle total

### Passo 4: Primeiro Acesso
1. **Acesse:** `http://seudominio.com/login.php`
2. **Email:** `admin@faciencia.com`
3. **Senha:** `Admin@123`
4. **Resultado:** Será redirecionado para `administrador/index.php`

### Passo 5: Configuração Inicial (IMPORTANTE!)
Após o primeiro login:

1. **Alterar senha:**
   - Vá em Usuários → Editar seu perfil
   - Altere a senha padrão

2. **Configurar sistema:**
   - Vá em Configurações → Aba Geral
   - Configure nome do sistema, fuso horário

3. **Configurar email:**
   - Vá em Configurações → Aba Email
   - Configure SMTP para envio de emails

4. **Configurar segurança:**
   - Vá em Configurações → Aba Segurança
   - Revise políticas de senha e segurança

## 🔧 RECURSOS DISPONÍVEIS

### Dashboard Principal
- ✅ Estatísticas em tempo real
- ✅ Gráficos de atividade
- ✅ Ações rápidas
- ✅ Monitoramento do sistema

### Gerenciamento de Usuários
- ✅ Criar, editar, remover usuários
- ✅ Controle de status (ativo/inativo/bloqueado)
- ✅ Reset de senhas
- ✅ Filtros e busca avançada

### Sistema de Logs
- ✅ Auditoria completa
- ✅ Filtros avançados
- ✅ Exportação CSV
- ✅ Limpeza automática

### Configurações
- ✅ Configurações gerais
- ✅ Políticas de segurança
- ✅ Configuração de email
- ✅ Ferramentas de manutenção

### Backup e Manutenção
- ✅ Backup completo do banco
- ✅ Backup apenas estrutura
- ✅ Backup apenas dados
- ✅ Download automático

## 🚨 TROUBLESHOOTING

### Erro: "Acesso negado"
- Verifique se o usuário tem tipo `admin_master`
- Verifique se o status é `ativo`

### Erro: "Não consegue fazer login"
- Email: `admin@faciencia.com`
- Senha: `Admin@123` (case sensitive)
- Verifique no banco se o usuário foi criado

### Erro: "Página não encontrada"
- Verifique se a pasta `administrador/` está no diretório raiz
- Verifique se o `.htaccess` está configurado

### Erro: "Erro de banco de dados"
- Verifique as configurações em `includes/config.php`
- Verifique se o banco de dados está acessível

## 📞 SUPORTE

Se encontrar problemas:
1. Verifique os logs do sistema em `administrador/logs.php`
2. Verifique os logs do servidor (error_log)
3. Confirme se todas as extensões PHP estão instaladas
4. Verifique permissões de arquivo

## ✅ CHECKLIST FINAL

- [ ] Usuário admin_master criado no banco
- [ ] Login funcionando com credenciais padrão
- [ ] Redirecionamento para administrador/index.php funcionando
- [ ] Dashboard carregando estatísticas
- [ ] Senha padrão alterada
- [ ] Configurações básicas definidas
- [ ] Email SMTP configurado (opcional)
- [ ] Backup testado

**🎉 MÓDULO ADMINISTRADOR PRONTO PARA USO!**

---
**Data:** Junho 2025  
**Versão:** 1.0 Final  
**Status:** ✅ Produção
