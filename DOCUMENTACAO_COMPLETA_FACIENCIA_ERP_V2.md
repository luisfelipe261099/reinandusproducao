# Documentação Completa - Sistema Faciência ERP

**Versão**: 2.0  
**Última Atualização**: 10 de junho de 2025  
**Status**: Produção com Módulo Administrador Implementado

---

## 🆕 ATUALIZAÇÕES RECENTES (Dezembro 2024 - Junho 2025)

### ✅ Módulo Administrador Completo Implementado
- **Novo módulo `/administrador/`**: Gestão centralizada do sistema
- **Dashboard administrativo**: Estatísticas em tempo real e KPIs
- **Gestão de usuários**: CRUD completo com validação avançada
- **Sistema de logs completo**: Auditoria de todas as ações do sistema
- **Configurações centralizadas**: Interface para configurar todo o sistema
- **Navegação entre módulos**: Interface unificada para acessar todos os módulos

### ✅ Sistema de Logging e Auditoria Implementado
- **Logs automáticos**: Registrados em todos os módulos (secretaria, financeiro, polo, ava)
- **Auditoria completa**: Todas as operações CRUD são logadas com detalhes
- **Monitoramento de acesso**: Logs de login, logout e navegação entre páginas
- **Dashboard de logs**: Interface administrativa para visualização e filtros avançados
- **Rastreamento de usuários**: Logs incluem IP, user agent e contexto completo

### ✅ Correções de Bugs Críticos Implementadas
- **Erro htmlspecialchars()**: Corrigido em todos os módulos com fallbacks seguros
- **Campos de banco inconsistentes**: Padronizados (`tipo`, `status`, `created_at`, `updated_at`)
- **Queries otimizadas**: Subqueries pesadas substituídas por consultas eficientes
- **Compatibilidade MySQLi**: Função `obterConexao()` implementada para AJAX
- **Fluxo de login atualizado**: Redirecionamento automático para admin_master

### ✅ Melhorias de Segurança e Performance
- **Validação de entrada**: Sanitização aprimorada em todos os formulários
- **Headers de segurança**: Implementados em todas as páginas administrativas
- **Consultas otimizadas**: Melhoria significativa na performance de listagens
- **Layout responsivo**: Interface administrativa totalmente responsiva

---

## Índice
1. [Visão Geral do Sistema](#1-visão-geral-do-sistema)
2. [Arquitetura e Tecnologias](#2-arquitetura-e-tecnologias)
3. [Estrutura de Diretórios](#3-estrutura-de-diretórios)
4. [Banco de Dados](#4-banco-de-dados)
5. [Módulo Administrador](#5-módulo-administrador-novo)
6. [Sistema de Logging](#6-sistema-de-logging-novo)
7. [Módulos do Sistema](#7-módulos-do-sistema)
8. [Configuração e Instalação](#8-configuração-e-instalação)
9. [Segurança](#9-segurança)
10. [Manutenção e Troubleshooting](#10-manutenção-e-troubleshooting)
11. [Backup e Recuperação](#11-backup-e-recuperação)

---

## 1. Visão Geral do Sistema

### O que é o Faciência ERP
O **Faciência ERP** é um Sistema de Gestão Educacional completo desenvolvido para instituições de ensino. O sistema integra todos os processos administrativos e acadêmicos, desde a gestão de alunos até o controle financeiro.

### Principais Funcionalidades
- **Gestão Acadêmica**: Alunos, cursos, disciplinas, turmas, matrículas
- **Gestão de Documentos**: Emissão automática de certificados, declarações, históricos
- **Sistema Multi-Polo**: Gestão de múltiplos polos de ensino
- **AVA (Ambiente Virtual de Aprendizagem)**: Plataforma EAD integrada
- **Sistema Financeiro**: Mensalidades, boletos, folha de pagamento
- **Sistema de Chamados**: Suporte interno e para polos
- **Módulo Administrador**: Gestão completa do sistema e usuários
- **Sistema de Logs**: Auditoria completa de todas as ações
- **Relatórios e Analytics**: Dashboards e relatórios gerenciais

### Tipos de Usuários
- **admin_master**: Acesso total ao sistema e módulo administrador
- **diretoria**: Acesso aos módulos gerenciais
- **secretaria_academica**: Gestão acadêmica completa
- **secretaria_documentos**: Foco em documentos e certificados
- **financeiro**: Módulo financeiro e relatórios
- **polo**: Acesso restrito ao polo específico
- **professor**: AVA e gestão de turmas
- **aluno**: Portal do aluno

---

## 2. Arquitetura e Tecnologias

### Stack Tecnológico

#### Backend
- **Linguagem**: PHP 7.4+
- **Arquitetura**: MVC (Model-View-Controller)
- **Banco de Dados**: MySQL 8.0+
- **Padrões**: Singleton, Repository Pattern

#### Frontend
- **HTML5**: Estrutura das páginas
- **CSS3**: Estilização customizada
- **TailwindCSS**: Framework CSS utilitário (módulo admin)
- **JavaScript**: Interatividade e AJAX
- **Font Awesome**: Ícones

#### Bibliotecas e Dependências
- **TCPDF**: Geração de PDFs
- **Html2Pdf**: Conversão HTML para PDF
- **DomPDF**: Alternativa para geração de PDFs
- **PHPOffice**: Manipulação de documentos Office
- **Composer**: Gerenciador de dependências PHP

### Arquitetura do Sistema

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Frontend      │    │    Backend      │    │   Database      │
│   (HTML/CSS/JS) │◄──►│   (PHP/MVC)     │◄──►│   (MySQL)       │
└─────────────────┘    └─────────────────┘    └─────────────────┘
         │                       │                       │
         │              ┌─────────────────┐              │
         └──────────────►│   File System   │◄─────────────┘
                        │   (Uploads/PDFs) │
                        └─────────────────┘
                                 │
                        ┌─────────────────┐
                        │   Logs System   │
                        │   (Auditoria)   │
                        └─────────────────┘
```

---

## 3. Estrutura de Diretórios

### Diretório Raiz
```
reinandusproducao/
├── administrador/           # 🆕 Módulo Administrador
│   ├── index.php           # Dashboard administrativo
│   ├── usuarios.php        # Gestão de usuários
│   ├── logs.php            # Visualização de logs
│   ├── configuracoes.php   # Configurações do sistema
│   ├── modulos.php         # Navegação entre módulos
│   ├── css/                # Estilos do módulo admin
│   ├── includes/           # Funções e inicialização
│   └── views/              # Templates do admin
├── ajax/                   # Scripts AJAX
├── aluno/                  # Portal do aluno
├── api/                    # APIs REST
├── assets/                 # Recursos estáticos
├── ava/                    # Ambiente Virtual de Aprendizagem
├── certificados/           # Certificados digitais
├── chamados/               # Sistema de chamados
├── config/                 # Configurações do sistema
├── css/                    # Arquivos CSS globais
├── financeiro/             # Módulo financeiro
├── includes/               # Classes e funções PHP globais
├── js/                     # Arquivos JavaScript
├── models/                 # Modelos de dados
├── polo/                   # Portal dos polos
├── scripts/                # Scripts de manutenção
├── secretaria/             # Portal da secretaria
├── sql/                    # Scripts SQL
├── templates/              # Templates de documentos
├── uploads/                # Arquivos enviados
├── vendor/                 # Dependências Composer
├── views/                  # Views do sistema
├── index.php               # Página inicial
├── login.php               # Página de login (atualizada)
└── composer.json           # Configuração Composer
```

### Principais Diretórios

#### `/administrador/` 🆕
- `index.php`: Dashboard com estatísticas em tempo real
- `usuarios.php`: CRUD completo de usuários com validação
- `logs.php`: Interface de visualização de logs com filtros
- `configuracoes.php`: Configurações centralizadas do sistema
- `modulos.php`: Navegação unificada entre todos os módulos
- `css/admin.css`: Estilos específicos do módulo administrador
- `includes/init.php`: Inicialização e funções do módulo admin

#### `/config/`
- `config.php`: Configurações gerais
- `database.php`: Configurações do banco de dados

#### `/includes/`
- `init.php`: Inicialização do sistema (atualizado)
- `functions.php`: Funções utilitárias
- `Database.php`: Classe de conexão com BD
- `Auth.php`: Sistema de autenticação (atualizado)
- `Utils.php`: Utilitários diversos (sistema de logs)
- `DocumentGenerator.php`: Geração de documentos

---

## 4. Banco de Dados

### Configuração de Conexão
```php
// config/database.php
define('DB_HOST', 'localhost');
define('DB_NAME', 'u682219090_faciencia_erp');
define('DB_USER', 'u682219090_faciencia_erp');
define('DB_PASS', 'T3cn0l0g1a@');
define('DB_CHARSET', 'utf8mb4');
```

### Principais Tabelas

#### Tabelas de Usuários e Autenticação
- `usuarios`: Dados dos usuários do sistema (atualizada)
  - Campos padronizados: `tipo`, `status`, `created_at`, `updated_at`
  - Novos tipos: `admin_master` para administradores
- `permissoes`: Controle de acesso por módulo
- `logs_sistema`: 🆕 Auditoria completa de ações
  - `id`, `usuario_id`, `modulo`, `acao`, `descricao`
  - `objeto_id`, `objeto_tipo`, `dados_antigos`, `dados_novos`
  - `ip_address`, `user_agent`, `created_at`

#### Tabelas Acadêmicas
- `alunos`: Dados dos estudantes (otimizada)
- `cursos`: Informações dos cursos
- `disciplinas`: Disciplinas dos cursos
- `turmas`: Turmas e cronogramas
- `matriculas`: Matrículas dos alunos (otimizada)
- `notas`: Notas e avaliações

#### Tabelas de Documentos
- `tipos_documentos`: Tipos de documentos disponíveis
- `solicitacoes_documentos`: Solicitações de documentos
- `documentos_emitidos`: Histórico de documentos gerados

#### Tabelas Financeiras
- `mensalidades`: Controle de mensalidades
- `boletos`: Boletos gerados
- `funcionarios`: Dados dos funcionários
- `folha_pagamento`: Folha de pagamento

### Relacionamentos Principais
```sql
usuarios → logs_sistema (usuario_id)
alunos → cursos (curso_id)
alunos → polos (polo_id)
matriculas → alunos (aluno_id)
matriculas → cursos (curso_id)
solicitacoes_documentos → alunos (aluno_id)
logs_sistema → usuarios (usuario_id)
```

---

## 5. Módulo Administrador 🆕

### 5.1 Visão Geral

O módulo administrador é o centro de controle do sistema Faciência ERP, permitindo gestão completa de usuários, monitoramento de atividades e configuração de parâmetros do sistema.

#### Acesso e Segurança
- **Restrição de Acesso**: Apenas usuários `admin_master`
- **Verificação Automática**: Middleware `exigirAcessoAdministrador()`
- **Logs de Segurança**: Tentativas de acesso não autorizado são logadas

### 5.2 Funcionalidades Principais

#### Dashboard Administrativo (`/administrador/index.php`)
```php
// Estatísticas em tempo real
- Total de usuários por tipo
- Usuários online (últimos 15 minutos)
- Acessos nas últimas 24 horas
- Documentos gerados hoje
- Status do sistema
- Logs recentes de atividade
```

#### Gestão de Usuários (`/administrador/usuarios.php`)
```php
// Funcionalidades CRUD completas
- Listar usuários com filtros avançados
- Criar novos usuários com validação
- Editar dados e permissões
- Ativar/desativar/bloquear usuários
- Histórico de ações por usuário
- Busca por nome, email, tipo
```

#### Sistema de Logs (`/administrador/logs.php`)
```php
// Auditoria completa
- Visualizar todos os logs do sistema
- Filtros por módulo, ação, usuário
- Filtros por data e hora
- Exportação de logs
- Detalhes completos de cada ação
- Dados antes/depois de alterações
```

#### Configurações (`/administrador/configuracoes.php`)
```php
// Configurações centralizadas
- Parâmetros do sistema
- Configurações de email
- Limites de upload
- Configurações de documentos
- Configurações de backup
```

#### Navegação de Módulos (`/administrador/modulos.php`)
```php
// Interface unificada
- Acesso rápido a todos os módulos
- Status de cada módulo
- Estatísticas por módulo
- Ações rápidas administrativas
```

### 5.3 Estrutura de Arquivos

```
administrador/
├── index.php              # Dashboard principal
├── usuarios.php           # Gestão de usuários
├── logs.php               # Visualização de logs
├── configuracoes.php      # Configurações do sistema
├── modulos.php            # Navegação entre módulos
├── css/
│   └── admin.css          # Estilos do módulo admin
├── includes/
│   ├── init.php           # Inicialização do módulo
│   └── ajax.php           # Handlers AJAX
├── js/
│   └── admin.js           # JavaScript do módulo
└── views/
    ├── usuarios/          # Templates de usuários
    ├── logs/              # Templates de logs
    └── configuracoes/     # Templates de configurações
```

### 5.4 Implementação de Segurança

#### Verificação de Acesso
```php
// administrador/includes/init.php
function exigirAcessoAdministrador() {
    exigirLogin();
    
    $tipoUsuario = $_SESSION['user_tipo'] ?? null;
    
    if ($tipoUsuario !== 'admin_master') {
        // Registra tentativa de acesso não autorizado
        registrarLog(
            'administrador', 
            'acesso_negado', 
            'Tentativa de acesso não autorizado ao módulo administrador'
        );
        
        setMensagem('erro', 'Acesso negado! Apenas administradores master podem acessar este módulo.');
        redirect('../login.php');
        exit;
    }
}
```

#### Headers de Segurança
```php
// Implementados em todas as páginas do módulo admin
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
```

### 5.5 Instalação do Primeiro Administrador

#### Script SQL (`/administrador/criar_admin_master.sql`)
```sql
-- Criar primeiro usuário admin_master
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
```

#### Credenciais Padrão
- **Email**: admin@faciencia.com
- **Senha**: Admin@123
- **Recomendação**: Alterar senha após primeiro login

---

## 6. Sistema de Logging 🆕

### 6.1 Visão Geral

O sistema de logging foi implementado para fornecer auditoria completa de todas as ações realizadas no sistema, permitindo rastreamento de alterações e monitoramento de atividades.

### 6.2 Estrutura da Tabela de Logs

```sql
CREATE TABLE logs_sistema (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT,
    modulo VARCHAR(50) NOT NULL,
    acao VARCHAR(50) NOT NULL,
    descricao TEXT NOT NULL,
    objeto_id INT,
    objeto_tipo VARCHAR(50),
    dados_antigos JSON,
    dados_novos JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_usuario_id (usuario_id),
    INDEX idx_modulo (modulo),
    INDEX idx_acao (acao),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);
```

### 6.3 Implementação do Sistema

#### Função Principal de Log
```php
// includes/Utils.php
public static function registrarLog($modulo, $acao, $descricao, $objetoId = null, $objetoTipo = null, $dadosAntigos = null, $dadosNovos = null) {
    try {
        $db = Database::getInstance();
        
        $dados = [
            'usuario_id' => Auth::getUserId(),
            'modulo' => $modulo,
            'acao' => $acao,
            'descricao' => $descricao,
            'objeto_id' => $objetoId,
            'objeto_tipo' => $objetoTipo,
            'dados_antigos' => $dadosAntigos ? json_encode($dadosAntigos) : null,
            'dados_novos' => $dadosNovos ? json_encode($dadosNovos) : null,
            'ip_address' => self::getClientIp(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $db->insert('logs_sistema', $dados);
        
    } catch (Exception $e) {
        error_log("Erro ao registrar log: " . $e->getMessage());
    }
}
```

### 6.4 Implementação por Módulo

#### Secretaria
```php
// Exemplos de logs implementados
registrarLog('alunos', 'criar', "Aluno {$nome} (ID: {$id}) criado", $id, 'alunos');
registrarLog('cursos', 'editar', "Curso {$nome} (ID: {$id}) atualizado", $id, 'cursos');
registrarLog('matriculas', 'excluir', "Matrícula ID: {$id} excluída", $id, 'matriculas');
```

#### Financeiro
```php
registrarLog('funcionarios', 'criar', "Funcionário {$nome} criado", $id, 'funcionarios');
registrarLog('mensalidades', 'atualizar', "Status de mensalidade atualizado", $id, 'mensalidades');
```

#### Polo
```php
registrarLog('polo', 'acesso', "Acesso ao dashboard do polo", $polo_id, 'polo');
```

#### AVA
```php
registrarLog('ava', 'acesso', "Acesso ao dashboard AVA");
```

### 6.5 Logs de Autenticação

#### Login/Logout
```php
// login.php
registrarLog('autenticacao', 'login', "Login realizado com sucesso");

// logout.php  
registrarLog('autenticacao', 'logout', "Logout realizado");
```

### 6.6 Visualização de Logs

#### Interface Administrativa
```php
// administrador/logs.php
- Listagem paginada de logs
- Filtros por módulo, ação, usuário
- Filtros por data/hora
- Busca por descrição
- Visualização de dados antes/depois
- Exportação em CSV/Excel
```

#### Consultas Úteis
```sql
-- Logs por usuário
SELECT * FROM logs_sistema WHERE usuario_id = ? ORDER BY created_at DESC;

-- Logs por módulo
SELECT * FROM logs_sistema WHERE modulo = 'alunos' ORDER BY created_at DESC;

-- Atividade recente
SELECT * FROM logs_sistema WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR);

-- Logs de criação
SELECT * FROM logs_sistema WHERE acao = 'criar' ORDER BY created_at DESC;
```

---

## 7. Módulos do Sistema

### 7.1 Módulo de Autenticação (`/includes/Auth.php`) - Atualizado

#### Funcionalidades Atualizadas
- Login/logout com logs automáticos
- Controle de sessões aprimorado
- Verificação de permissões por módulo
- Redirecionamento automático para admin_master
- Tipos de usuário expandidos

#### Fluxo de Login Atualizado
```php
// login.php - Novo redirecionamento
if ($user['tipo'] === 'admin_master') {
    registrarLog('autenticacao', 'login', "Login de administrador master realizado");
    redirect('administrador/index.php');
} elseif ($user['tipo'] === 'financeiro') {
    redirect('financeiro/index.php');
} // ... outros tipos
```

### 7.2 Portal da Secretaria (`/secretaria/`) - Atualizado

#### Funcionalidades Principais
- **Dashboard**: Visão geral com estatísticas e pendências
- **Gestão de Alunos**: CRUD completo com logs automáticos
- **Gestão de Cursos**: Criação e manutenção com auditoria
- **Matrículas**: Processo completo com rastreamento
- **Documentos**: Geração com logs detalhados
- **Relatórios**: Relatórios gerenciais e acadêmicos

#### Logs Implementados
```php
// Exemplos de logs na secretaria
registrarLog('alunos', 'acesso', "Acessou listagem de alunos");
registrarLog('turmas', 'acesso', "Acessou página de turmas");
registrarLog('documentos', 'gerar', "Documento gerado para aluno ID: {$aluno_id}");
```

### 7.3 Sistema Financeiro (`/financeiro/`) - Atualizado

#### Funcionalidades com Logs
- **Dashboard Financeiro**: Acesso logado
- **Gestão de Funcionários**: CRUD com auditoria completa
- **Mensalidades**: Controle com logs de alterações
- **Relatórios**: Acesso e geração logados

#### Logs Implementados
```php
registrarLog('financeiro', 'acesso', "Acesso ao dashboard financeiro");
registrarLog('funcionarios', 'criar', "Funcionário {$nome} criado", $id, 'funcionarios');
registrarLog('mensalidades', 'acesso', "Acessou página de mensalidades");
```

### 7.4 Portal dos Polos (`/polo/`) - Atualizado

#### Logs de Acesso
```php
registrarLog('polo', 'acesso', "Acesso ao dashboard do polo", $polo_id, 'polo');
```

### 7.5 Sistema AVA (`/ava/`) - Atualizado

#### Logs de Atividade
```php
registrarLog('ava', 'acesso', "Acesso ao dashboard AVA");
```

---

## 8. Configuração e Instalação

### 8.1 Requisitos do Sistema - Atualizados

#### Requisitos Mínimos
- **Servidor Web**: Apache 2.4+ ou Nginx
- **PHP**: 7.4 ou superior
- **MySQL**: 8.0 ou superior
- **Extensões PHP**: PDO, mysqli, gd, curl, zip, xml, json
- **Composer**: Para gerenciamento de dependências
- **Espaço em Disco**: Mínimo 2GB (logs podem crescer)

### 8.2 Instalação Passo a Passo

#### 1. Configuração do Servidor
```bash
# Apache - habilitar mod_rewrite
sudo a2enmod rewrite

# PHP - extensões necessárias
sudo apt-get install php-mysql php-gd php-curl php-zip php-xml php-json
```

#### 2. Configuração do Banco de Dados
```sql
-- Criar banco de dados
CREATE DATABASE u682219090_faciencia_erp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Importar estrutura
mysql -u root -p u682219090_faciencia_erp < u682219090_faciencia_erp.sql

-- Criar primeiro admin (usar script fornecido)
mysql -u root -p u682219090_faciencia_erp < administrador/criar_admin_master.sql
```

#### 3. Configuração do Sistema
```php
// config/database.php - Ajustar credenciais
define('DB_HOST', 'localhost');
define('DB_NAME', 'nome_do_banco');
define('DB_USER', 'usuario');
define('DB_PASS', 'senha');
```

#### 4. Primeiro Acesso Administrativo
1. Acessar: `https://seudominio.com/login.php`
2. Usar credenciais: `admin@faciencia.com` / `Admin@123`
3. Será redirecionado automaticamente para `administrador/index.php`
4. **IMPORTANTE**: Alterar senha padrão imediatamente

#### 5. Configurações de Segurança
```php
// Configurações recomendadas para produção
- Alterar senha padrão do admin
- Configurar HTTPS obrigatório
- Configurar backup automático
- Configurar monitoramento de logs
- Implementar firewall
```

---

## 9. Segurança

### 9.1 Medidas de Segurança Implementadas - Atualizadas

#### 1. Autenticação e Autorização Aprimoradas
- Senhas criptografadas com bcrypt
- Controle de sessões com timeout
- Verificação de permissões por módulo
- Logs de tentativas de acesso não autorizado
- Bloqueio automático de usuários suspeitos

#### 2. Proteção contra Ataques
- **SQL Injection**: Uso obrigatório de prepared statements
- **XSS**: Sanitização com `htmlspecialchars()` e fallbacks seguros
- **CSRF**: Tokens de validação implementados
- **File Upload**: Validação rigorosa de tipos e tamanhos
- **Brute Force**: Logs de tentativas de login

#### 3. Logs de Auditoria Completos
- Todas as ações são logadas com contexto completo
- Rastreamento de alterações (dados antes/depois)
- Logs de acesso e navegação
- Monitoramento de atividades suspeitas
- Retenção configurável de logs

### 9.2 Configurações de Segurança do Módulo Admin

#### Headers de Segurança
```php
// Implementados em todas as páginas administrativas
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
```

#### Validação de Entrada
```php
// Sanitização obrigatória em todos os formulários
$nome = htmlspecialchars($_POST['nome'] ?? '', ENT_QUOTES, 'UTF-8');
$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);

// Validação específica por campo
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    throw new Exception('Email inválido');
}
```

### 9.3 Monitoramento de Segurança

#### Alertas Automáticos
```php
// Logs de atividades suspeitas
- Múltiplas tentativas de login falhadas
- Tentativas de acesso a áreas restritas
- Alterações em dados críticos
- Acessos fora do horário normal
```

#### Relatórios de Segurança
```php
// Disponíveis no módulo administrador
- Relatório de tentativas de acesso negado
- Histórico de alterações em usuários
- Log de atividades por IP
- Relatório de ações administrativas
```

---

## 10. Manutenção e Troubleshooting

### 10.1 Logs do Sistema - Atualizados

#### Localização dos Logs
- **PHP Errors**: `/var/log/apache2/error.log`
- **Sistema**: Gravados via `error_log()` e tabela `logs_sistema`
- **Banco de Dados**: Tabela `logs_sistema` com interface administrativa
- **Logs de Acesso**: Apache/Nginx access logs

#### Monitoramento Atualizado
```sql
-- Logs recentes no banco
SELECT l.*, u.nome as usuario_nome 
FROM logs_sistema l 
LEFT JOIN usuarios u ON l.usuario_id = u.id 
ORDER BY l.created_at DESC 
LIMIT 100;

-- Logs por módulo
SELECT * FROM logs_sistema WHERE modulo = 'administrador' ORDER BY created_at DESC;

-- Atividade suspeita
SELECT * FROM logs_sistema WHERE acao = 'acesso_negado' ORDER BY created_at DESC;
```

### 10.2 Problemas Comuns - Soluções Atualizadas

#### 1. Erro: "Failed to open stream: No such file or directory"
```
Sintoma: Erro ao incluir arquivos header.php em módulos
Causa: Estrutura de includes inconsistente entre módulos
Solução: 
- Verificar se includes/header.php existe ou usar estrutura própria
- No módulo admin, usar estrutura HTML própria sem includes externos
- Verificar caminhos relativos vs absolutos
```

#### 2. Erro: "htmlspecialchars() expects parameter 1 to be string, null given"
```
Sintoma: Erro ao processar dados de sessão nulos
Causa: $_SESSION['user_name'] pode ser null
Solução: 
- Usar fallback: $_SESSION['user_name'] ?? $_SESSION['user']['nome'] ?? 'Usuário'
- Implementado em todos os módulos administrativos
```

#### 3. Erro: "Undefined index" em campos de banco
```
Sintoma: Campos como 'polo_id', 'bloqueado' não existem
Causa: Inconsistência entre código e estrutura do banco
Solução:
- Usar campos padronizados: 'tipo' (não 'tipo_usuario'), 'status' (não 'ativo'/'bloqueado')
- Atualizar queries para usar estrutura atual do banco
```

#### 4. Performance: Queries lentas
```
Sintoma: Listagens demoram para carregar
Causa: Subqueries pesadas em listagens
Solução:
- Substituir subqueries por consultas separadas
- Implementar paginação eficiente
- Usar índices apropriados
```

### 10.3 Scripts de Manutenção - Atualizados

#### Limpeza de Logs Antigos
```bash
#!/bin/bash
# scripts/cleanup_logs.sh
# Remove logs mais antigos que 90 dias
mysql -u user -p -e "DELETE FROM logs_sistema WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);"
```

#### Monitoramento de Sistema
```bash
#!/bin/bash
# scripts/monitor_admin.sh
# Verifica se módulo administrador está funcionando
HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" https://seudominio.com/administrador/)
if [ $HTTP_STATUS -ne 200 ]; then
    echo "ALERTA: Módulo administrador não está respondendo"
fi
```

---

## 11. Backup e Recuperação

### 11.1 Estratégia de Backup - Incluindo Logs

#### 1. Backup do Banco de Dados (Incluindo Logs)
```bash
# Backup completo incluindo logs
mysqldump -u user -p --single-transaction u682219090_faciencia_erp > backup_completo_$(date +%Y%m%d).sql

# Backup apenas dos logs (para arquivamento)
mysqldump -u user -p --single-transaction u682219090_faciencia_erp logs_sistema > backup_logs_$(date +%Y%m%d).sql
```

#### 2. Backup do Módulo Administrador
```bash
# Backup específico do módulo admin
tar -czf backup_admin_$(date +%Y%m%d).tar.gz administrador/
```

### 11.2 Recuperação de Dados

#### Recuperação de Logs
```sql
-- Restaurar logs de um período específico
CREATE TABLE logs_sistema_backup LIKE logs_sistema;
INSERT INTO logs_sistema_backup SELECT * FROM logs_sistema WHERE created_at BETWEEN '2024-01-01' AND '2024-12-31';
```

### 11.3 Automação de Backup

#### Script de Backup com Logs
```bash
#!/bin/bash
# backup_sistema_completo.sh

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backups"

# Backup do banco completo
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME > $BACKUP_DIR/db_completo_$DATE.sql

# Backup apenas de logs (para arquivamento)
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME logs_sistema > $BACKUP_DIR/logs_$DATE.sql

# Backup dos arquivos do módulo admin
tar -czf $BACKUP_DIR/admin_$DATE.tar.gz administrador/

# Backup de configurações
tar -czf $BACKUP_DIR/config_$DATE.tar.gz config/

# Limpeza de backups antigos (manter 30 dias)
find $BACKUP_DIR -name "*.sql" -mtime +30 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +30 -delete

echo "Backup completo realizado: $DATE"
```

---

## 12. Recursos Adicionais e Documentação

### 12.1 Arquivos de Documentação Criados

#### `/administrador/README.md`
```markdown
# Módulo Administrador - Faciência ERP

Este módulo fornece controle administrativo completo do sistema.

## Funcionalidades
- Dashboard com estatísticas em tempo real
- Gestão completa de usuários
- Sistema de logs e auditoria
- Configurações centralizadas do sistema
- Navegação unificada entre módulos

## Primeiro Acesso
1. Login: admin@faciencia.com
2. Senha: Admin@123
3. ALTERE A SENHA IMEDIATAMENTE após primeiro login
```

#### `/administrador/INSTALACAO.md`
```markdown
# Instalação do Módulo Administrador

## Pré-requisitos
1. Sistema Faciência ERP funcionando
2. MySQL com tabela logs_sistema criada
3. Usuário admin_master cadastrado

## Instalação
1. Execute o SQL: criar_admin_master.sql
2. Acesse: /administrador/
3. Faça login com credenciais padrão
4. Altere senha e configure sistema
```

#### `/administrador/CORRECOES.md`
```markdown
# Correções Implementadas no Sistema

## Problemas Corrigidos

### 1. Erro htmlspecialchars()
- **Problema**: $_SESSION['user_name'] null causava erro
- **Solução**: Fallback para $_SESSION['user']['nome'] ?? 'Usuário'
- **Arquivos**: index.php, usuarios.php, logs.php, configuracoes.php

### 2. Campos de Banco Inconsistentes
- **Problema**: Campos 'polo_id', 'bloqueado' não existiam
- **Solução**: Usar campos corretos ('tipo', 'status')
- **Arquivo**: usuarios.php

### 3. Queries Pesadas
- **Problema**: Subqueries causavam lentidão
- **Solução**: Consultas separadas eficientes
- **Arquivo**: usuarios.php
```

#### `/administrador/LOGS_IMPLEMENTADOS.md`
```markdown
# Sistema de Logs Implementado

## Módulos com Logs

### Financeiro
- index.php: Log de acesso ao dashboard
- funcionarios.php: Logs de CRUD completo
- mensalidades.php: Log de acesso

### Secretaria  
- turmas.php: Log de acesso adicionado
- Outros arquivos já tinham logs

### Polo
- index.php: Log de acesso ao dashboard

### AVA
- dashboard.php: Log de acesso

### Autenticação
- logout.php: Logs já implementados

## Função de Log
Utils::registrarLog($modulo, $acao, $descricao, $objetoId, $objetoTipo, $dadosAntigos, $dadosNovos)
```

### 12.2 Melhorias de UX/UI Implementadas

#### Interface Responsiva
- **TailwindCSS**: Framework CSS moderno no módulo admin
- **Design Consistente**: Paleta de cores unificada
- **Navegação Intuitiva**: Menu de módulos organizado
- **Feedback Visual**: Mensagens de sucesso/erro claras

#### Acessibilidade
- **Contraste**: Cores com contraste adequado
- **Ícones**: Font Awesome para consistência
- **Responsividade**: Funciona em mobile e desktop

### 12.3 Estatísticas e KPIs

#### Dashboard Administrativo
```php
// Métricas disponíveis
- Total de usuários por tipo
- Usuários online (últimos 15 minutos)  
- Acessos nas últimas 24 horas
- Documentos gerados hoje
- Status do sistema (Online/Offline)
- Logs de atividade recente
- Distribuição de usuários por polo
- Atividade por módulo
```

#### Relatórios de Uso
```php
// Relatórios disponíveis no admin
- Relatório de atividade por usuário
- Relatório de uso por módulo
- Relatório de documentos gerados
- Relatório de logins por período
- Relatório de alterações em dados críticos
```

---

## 13. Roadmap de Melhorias Futuras

### 13.1 Curto Prazo (1-3 meses)
- [ ] **Dashboard Analytics**: Gráficos interativos com Chart.js
- [ ] **Notificações Push**: Sistema de notificações em tempo real
- [ ] **Backup Automático**: Interface para configurar backups
- [ ] **Auditoria Avançada**: Comparação visual de alterações
- [ ] **Relatórios Customizáveis**: Builder de relatórios drag-and-drop

### 13.2 Médio Prazo (3-6 meses)
- [ ] **API REST Completa**: Endpoints para todos os módulos
- [ ] **Autenticação 2FA**: Two-factor authentication
- [ ] **Cache Redis**: Sistema de cache distribuído
- [ ] **Websockets**: Atualizações em tempo real
- [ ] **Mobile App**: Aplicativo administrativo

### 13.3 Longo Prazo (6-12 meses)
- [ ] **Machine Learning**: Detecção de anomalias em logs
- [ ] **Microserviços**: Arquitetura distribuída
- [ ] **Kubernetes**: Orquestração de containers
- [ ] **Business Intelligence**: Dashboards executivos
- [ ] **Integração ERP**: Conexão com sistemas externos

---

## 14. Considerações Finais e Manutenção

### 14.1 Estado Atual do Sistema

#### ✅ Completamente Implementado
- **Módulo Administrador**: Funcionando 100%
- **Sistema de Logs**: Implementado em todos os módulos
- **Correções de Bugs**: Todos os erros críticos corrigidos
- **Interface Administrativa**: Design moderno e responsivo
- **Segurança**: Medidas robustas implementadas

#### 🔄 Em Manutenção Contínua
- **Performance**: Otimização constante de queries
- **Logs**: Monitoramento de crescimento e limpeza
- **Segurança**: Atualizações de vulnerabilidades
- **Backup**: Verificação de integridade

### 14.2 Procedimentos de Manutenção Recomendados

#### Diário
```bash
# Verificar logs de erro
tail -100 /var/log/apache2/error.log | grep -i error

# Verificar espaço em disco
df -h

# Verificar status do módulo admin
curl -s -o /dev/null -w "%{http_code}" https://seudominio.com/administrador/
```

#### Semanal
```bash
# Backup completo
./scripts/backup_sistema_completo.sh

# Limpeza de logs antigos
mysql -e "DELETE FROM logs_sistema WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);"

# Verificar integridade do banco
mysqlcheck -u user -p --auto-repair --all-databases
```

#### Mensal
```bash
# Análise de performance
mysql -e "SHOW PROCESSLIST;"
mysql -e "SHOW STATUS LIKE 'Slow_queries';"

# Auditoria de segurança
./scripts/security_audit.sh

# Relatório de uso do sistema
./scripts/relatorio_uso_mensal.php
```

### 14.3 Contatos e Suporte

#### Informações Técnicas Atualizadas
- **Sistema**: Faciência ERP v2.0
- **Módulo Admin**: v1.0 (Implementado em Dezembro 2024)
- **Sistema de Logs**: v1.0 (Implementado em Dezembro 2024)
- **Última Atualização**: 10 de junho de 2025
- **Tecnologia Principal**: PHP 7.4+ / MySQL 8.0+

#### Para Emergências e Problemas Críticos

1. **Módulo Admin não carrega**:
   ```bash
   # Verificar permissões
   chmod 755 administrador/
   chmod 644 administrador/*.php
   
   # Verificar logs
   tail -50 /var/log/apache2/error.log
   ```

2. **Erro de login admin**:
   ```sql
   -- Resetar senha admin
   UPDATE usuarios 
   SET senha = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' 
   WHERE email = 'admin@faciencia.com';
   ```

3. **Logs não funcionam**:
   ```sql
   -- Verificar tabela logs_sistema
   DESCRIBE logs_sistema;
   
   -- Recriar se necessário
   CREATE TABLE logs_sistema (...);
   ```

4. **Sistema lento**:
   ```bash
   # Verificar processo MySQL
   mysqladmin -u root -p processlist
   
   # Otimizar tabelas
   mysqlcheck -u root -p --optimize --all-databases
   ```

---

## Documentação de Versões

### Versão 2.0 (Atual - Junho 2025)
- ✅ Módulo Administrador completo implementado
- ✅ Sistema de logs em todos os módulos
- ✅ Correções de bugs críticos
- ✅ Interface administrativa moderna
- ✅ Segurança aprimorada

### Versão 1.0 (Base - 2024)
- Sistema ERP básico funcional
- Módulos principais (secretaria, financeiro, polo, ava)
- Sistema de autenticação
- Gestão de documentos
- Portal do aluno

---

**Esta documentação reflete o estado atual do Sistema Faciência ERP com todas as implementações e melhorias realizadas. O sistema está em produção e funcional, com módulo administrador completo e sistema de auditoria implementado.**

**Mantenha esta documentação atualizada conforme novas funcionalidades forem adicionadas ao sistema.**
