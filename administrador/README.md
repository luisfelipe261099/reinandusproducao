# Módulo Administrador - Faciência ERP

## Visão Geral

O módulo administrador é o centro de controle do sistema Faciência ERP, oferecendo ferramentas abrangentes para gerenciamento de usuários, configurações do sistema, auditoria e manutenção.

## Funcionalidades Implementadas

### 🏠 Dashboard Principal (`index.php`)
- **Estatísticas em tempo real**: Usuários ativos, alunos matriculados, logs recentes
- **Gráficos interativos**: Atividade do sistema, crescimento de usuários
- **Ações rápidas**: Acesso direto às funcionalidades mais usadas
- **Monitoramento do sistema**: Status de serviços, uso de recursos
- **Atividade recente**: Últimas ações realizadas no sistema

### 👥 Gerenciamento de Usuários (`usuarios.php`)
- **Listagem completa**: Visualização de todos os usuários com filtros avançados
- **Criação de usuários**: Modal para cadastro com validação completa
- **Edição de perfis**: Atualização de dados pessoais e permissões
- **Controle de status**: Ativar, desativar e bloquear usuários
- **Reset de senhas**: Geração automática ou manual de novas senhas
- **Histórico de atividades**: Visualização das ações por usuário
- **Filtros inteligentes**: Por tipo, status, polo, data de cadastro
- **Paginação otimizada**: Navegação eficiente em grandes volumes

### 📊 Sistema de Logs e Auditoria (`logs.php`)
- **Registro abrangente**: Todas as ações são logadas automaticamente
- **Filtros avançados**: Por módulo, ação, usuário, data, IP
- **Busca textual**: Localização rápida de eventos específicos
- **Exportação CSV**: Download de logs para análise externa
- **Limpeza automática**: Remoção de logs antigos com configuração
- **Detalhes expandidos**: Visualização completa de cada evento
- **Estatísticas**: Contadores por período e tipo de ação

### ⚙️ Configurações do Sistema (`configuracoes.php`)

#### Aba Geral
- **Informações básicas**: Nome, descrição, contatos
- **Configurações regionais**: Fuso horário, idioma
- **Políticas de sessão**: Timeout, retenção de logs

#### Aba Segurança
- **Políticas de senha**: Comprimento mínimo, caracteres especiais
- **Controle de acesso**: HTTPS obrigatório, 2FA, lista de IPs
- **Tentativas de login**: Limite de tentativas, bloqueio automático
- **Auditoria**: Configuração de logs de segurança

#### Aba Email
- **Servidor SMTP**: Configuração completa do servidor de email
- **Autenticação**: Usuário, senha, criptografia TLS
- **Identidade**: Remetente, resposta, assinatura padrão
- **Teste de configuração**: Envio de email de teste

#### Aba Manutenção
- **Backup do sistema**: Completo, apenas estrutura, apenas dados
- **Limpeza de logs**: Remoção de registros antigos
- **Informações do sistema**: Versões, espaço em disco, status

### 🧩 Navegação de Módulos (`modulos.php`)
- **Visão geral**: Cards com informações de cada módulo
- **Status dos módulos**: Ativo, em desenvolvimento, manutenção
- **Ações rápidas**: Acesso direto às funcionalidades principais
- **Estatísticas**: Dados de uso e performance de cada módulo

## Arquitetura e Estrutura

### Organização de Arquivos
```
administrador/
├── index.php              # Dashboard principal
├── usuarios.php           # Gerenciamento de usuários
├── logs.php              # Sistema de logs e auditoria
├── configuracoes.php     # Configurações do sistema
├── modulos.php           # Navegação entre módulos
├── css/
│   └── admin.css        # Estilos específicos do módulo
├── js/
│   └── admin.js         # Scripts JavaScript
├── includes/
│   ├── init.php         # Inicialização e funções
│   ├── ajax.php         # Processamento AJAX
│   └── header.php       # Cabeçalho comum
└── views/
    └── (componentes)    # Componentes reutilizáveis
```

### Sistema de Segurança
- **Controle de acesso**: Apenas usuários `admin_master`
- **Validação de sessão**: Verificação automática em todas as páginas
- **Log de tentativas**: Registro de acessos não autorizados
- **Sanitização**: Validação de entrada em todos os formulários

### Integração com Banco de Dados
- **Tabelas principais**: `usuarios`, `logs_sistema`, `configuracoes_sistema`
- **Queries otimizadas**: Uso de prepared statements
- **Transações**: Operações críticas protegidas
- **Indexação**: Performance otimizada para consultas frequentes

## Funcionalidades AJAX

### Operações Assíncronas
- ✅ Criação e edição de usuários
- ✅ Alteração de status (ativar/desativar/bloquear)
- ✅ Reset de senhas
- ✅ Exportação de logs
- ✅ Limpeza de logs antigos
- ✅ Backup do sistema
- ✅ Teste de configuração de email
- ✅ Atualização de estatísticas em tempo real

### Interface Responsiva
- **Design adaptável**: Funciona em desktop, tablet e mobile
- **Modais interativos**: Formulários em overlays
- **Feedback visual**: Loading states e notificações
- **Navegação intuitiva**: Breadcrumbs e menus contextuais

## Configuração e Instalação

### Pré-requisitos
- PHP 7.4+
- MySQL 5.7+
- Apache/Nginx com mod_rewrite
- Extensões PHP: mysqli, json, session

### Configuração Inicial
1. **Banco de dados**: Execute o script SQL incluído
2. **Permissões**: Configure permissões de escrita nas pastas `temp/` e `backups/`
3. **Primeiro usuário**: Execute o SQL em `criar_admin_master.sql` para criar o usuário administrador
4. **Primeiro login**: Use email `admin@faciencia.com` e senha `Admin@123`
5. **Configurações**: Acesse as configurações e ajuste conforme necessário
6. **Segurança**: Altere a senha padrão imediatamente após o primeiro acesso

### Estrutura do Banco
```sql
-- Tabela de usuários
CREATE TABLE usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    tipo_usuario ENUM('admin_master', 'diretoria', 'secretaria_academica', 'financeiro', 'polo', 'professor', 'aluno'),
    ativo BOOLEAN DEFAULT 1,
    bloqueado BOOLEAN DEFAULT 0,
    data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de logs
CREATE TABLE logs_sistema (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT,
    modulo VARCHAR(50),
    acao VARCHAR(100),
    descricao TEXT,
    ip_address VARCHAR(45),
    dados_extras JSON,
    data_acao DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de configurações
CREATE TABLE configuracoes_sistema (
    id INT PRIMARY KEY AUTO_INCREMENT,
    chave VARCHAR(100) UNIQUE NOT NULL,
    valor TEXT,
    tipo ENUM('string', 'numero', 'booleano', 'json') DEFAULT 'string',
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

## Monitoramento e Manutenção

### Logs do Sistema
- **Localização**: Tabela `logs_sistema`
- **Retenção**: Configurável (padrão 30 dias)
- **Tipos de eventos**: Login, logout, CRUD, configurações, erros
- **Análise**: Filtros por data, usuário, módulo, ação

### Backup e Recuperação
- **Backup automático**: Configurável via cron jobs
- **Tipos de backup**: Completo, estrutura, dados
- **Armazenamento**: Pasta `backups/` com timestamping
- **Restauração**: Manual via phpMyAdmin ou linha de comando

### Performance
- **Cache de consultas**: Implementado para estatísticas
- **Otimização de imagens**: Compressão automática
- **Lazy loading**: Carregamento sob demanda de componentes
- **Minificação**: CSS e JS compactados em produção

## Roadmap e Melhorias Futuras

### Próximas Funcionalidades
- [ ] Autenticação de dois fatores (2FA)
- [ ] Dashboard customizável com widgets
- [ ] Relatórios avançados com gráficos
- [ ] Notificações push em tempo real
- [ ] API REST para integrações
- [ ] Tema escuro/claro
- [ ] Backup na nuvem (AWS S3, Google Drive)
- [ ] Auditoria compliance (LGPD)

### Melhorias Técnicas
- [ ] Implementação de cache Redis
- [ ] Testes automatizados (PHPUnit)
- [ ] Documentação API (Swagger)
- [ ] Monitoramento de performance
- [ ] Containerização (Docker)
- [ ] CI/CD pipeline
- [ ] Análise de segurança automatizada

## Suporte e Contribuição

### Documentação
- **Código**: Comentários detalhados em português
- **API**: Endpoints documentados com exemplos
- **Banco**: Esquema completo com relacionamentos
- **Deploy**: Guias de instalação e configuração

### Troubleshooting
- **Logs de erro**: Verificar `logs_sistema` para problemas
- **Configurações**: Validar configurações de email e banco
- **Permissões**: Verificar permissões de arquivo e pasta
- **PHP**: Conferir versão e extensões necessárias

### Contato
- **Sistema**: Faciência ERP v1.0
- **Desenvolvido**: Sistema integrado de gestão educacional
- **Manutenção**: Time de desenvolvimento interno

---

**Última atualização**: Junho 2025  
**Versão do módulo**: 1.0  
**Status**: Produção ✅
