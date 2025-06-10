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
1. [Visão Geral do Sistema](#visão-geral-do-sistema)
2. [Arquitetura e Tecnologias](#arquitetura-e-tecnologias)
3. [Estrutura de Diretórios](#estrutura-de-diretórios)
4. [Banco de Dados](#banco-de-dados)
5. [Módulos do Sistema](#módulos-do-sistema)
6. [Configuração e Instalação](#configuração-e-instalação)
7. [Manutenção e Troubleshooting](#manutenção-e-troubleshooting)
8. [Segurança](#segurança)
9. [APIs e Integrações](#apis-e-integrações)
10. [Backup e Recuperação](#backup-e-recuperação)

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
- **Relatórios e Analytics**: Dashboards e relatórios gerenciais

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
- **CSS3**: Estilização
- **TailwindCSS**: Framework CSS utilitário
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
```

---

## 3. Estrutura de Diretórios

### Diretório Raiz
```
reinandusproducao/
├── ajax/                    # Scripts AJAX
├── aluno/                   # Portal do aluno
├── api/                     # APIs REST
├── assets/                  # Recursos estáticos
├── ava/                     # Ambiente Virtual de Aprendizagem
├── certificados/            # Certificados digitais
├── chamados/                # Sistema de chamados
├── config/                  # Configurações do sistema
├── css/                     # Arquivos CSS
├── financeiro/              # Módulo financeiro
├── includes/                # Classes e funções PHP
├── js/                      # Arquivos JavaScript
├── models/                  # Modelos de dados
├── polo/                    # Portal dos polos
├── scripts/                 # Scripts de manutenção
├── secretaria/              # Portal da secretaria
├── sql/                     # Scripts SQL
├── templates/               # Templates de documentos
├── uploads/                 # Arquivos enviados
├── vendor/                  # Dependências Composer
├── views/                   # Views do sistema
├── index.php               # Página inicial
├── login.php               # Página de login
└── composer.json           # Configuração Composer
```

### Principais Diretórios

#### `/config/`
- `config.php`: Configurações gerais
- `database.php`: Configurações do banco de dados

#### `/includes/`
- `init.php`: Inicialização do sistema
- `functions.php`: Funções utilitárias
- `Database.php`: Classe de conexão com BD
- `Auth.php`: Sistema de autenticação
- `Utils.php`: Utilitários diversos
- `DocumentGenerator.php`: Geração de documentos

#### `/models/`
- `Aluno.php`: Modelo de alunos
- `Curso.php`: Modelo de cursos
- `Matricula.php`: Modelo de matrículas
- `Documento.php`: Modelo de documentos
- `Polo.php`: Modelo de polos

#### `/views/`
- Contém as views organizadas por módulo
- Templates reutilizáveis
- Layouts padrão

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
- `usuarios`: Dados dos usuários do sistema
- `permissoes`: Controle de acesso por módulo
- `logs_sistema`: Auditoria de ações

#### Tabelas Acadêmicas
- `alunos`: Dados dos estudantes
- `cursos`: Informações dos cursos
- `disciplinas`: Disciplinas dos cursos
- `turmas`: Turmas e cronogramas
- `matriculas`: Matrículas dos alunos
- `notas`: Notas e avaliações

#### Tabelas de Documentos
- `tipos_documentos`: Tipos de documentos disponíveis
- `solicitacoes_documentos`: Solicitações de documentos
- `documentos_emitidos`: Histórico de documentos gerados

#### Tabelas de Polos
- `polos`: Informações dos polos
- `polos_configuracoes`: Configurações específicas

#### Tabelas Financeiras
- `mensalidades`: Controle de mensalidades
- `boletos`: Boletos gerados
- `funcionarios`: Dados dos funcionários
- `folha_pagamento`: Folha de pagamento

#### Tabelas de Suporte
- `chamados`: Sistema de tickets
- `categorias_chamados`: Categorias de chamados
- `respostas_chamados`: Respostas aos chamados

### Relacionamentos Principais
```sql
alunos → cursos (curso_id)
alunos → polos (polo_id)
matriculas → alunos (aluno_id)
matriculas → cursos (curso_id)
solicitacoes_documentos → alunos (aluno_id)
chamados → usuarios (solicitante_id)
```

---

## 5. Módulos do Sistema

### 5.1 Módulo de Autenticação (`/includes/Auth.php`)

#### Funcionalidades
- Login/logout de usuários
- Controle de sessões
- Verificação de permissões
- Tipos de usuário: admin_master, diretoria, secretaria_academica, secretaria_documentos, financeiro, polo, professor, aluno

#### Métodos Principais
```php
Auth::login($user)           // Autentica usuário
Auth::logout()               // Encerra sessão
Auth::isLoggedIn()          // Verifica se está logado
Auth::hasPermission()       // Verifica permissões
Auth::requireLogin()        // Força login
```

### 5.2 Módulo de Alunos (`/models/Aluno.php`)

#### Funcionalidades
- CRUD completo de alunos
- Busca avançada com filtros
- Histórico acadêmico
- Controle de status (ativo, trancado, formado, etc.)

#### Campos Principais
- Dados pessoais (nome, CPF, RG, etc.)
- Endereço completo
- Dados acadêmicos (curso, polo, datas)
- Status e observações

### 5.3 Módulo de Documentos (`/includes/DocumentGenerator.php`)

#### Tipos de Documentos
- Declarações de matrícula
- Históricos escolares
- Certificados de conclusão
- Declarações personalizadas

#### Processo de Geração
1. Solicitação via sistema
2. Validação de dados
3. Geração usando templates
4. Assinatura digital (opcional)
5. Entrega/download

### 5.4 Sistema Financeiro (`/financeiro/`)

#### Funcionalidades
- Gestão de mensalidades
- Geração de boletos
- Controle de inadimplência
- Folha de pagamento
- Relatórios financeiros

#### Integrações
- API do Banco Itaú
- Sistemas de cobrança
- Gateways de pagamento

### 5.5 AVA - Ambiente Virtual (`/ava/`)

#### Funcionalidades
- Cursos online
- Aulas em vídeo
- Material didático
- Progresso do aluno
- Avaliações online

### 5.6 Sistema de Polos (`/polo/`)

#### Funcionalidades
- Gestão multi-polo
- Controle de limites
- Relatórios por polo
- Acesso restrito por polo

---

## 6. Configuração e Instalação

### Requisitos do Sistema
- **Servidor Web**: Apache 2.4+ ou Nginx
- **PHP**: 7.4 ou superior
- **MySQL**: 8.0 ou superior
- **Extensões PHP**: PDO, mysqli, gd, curl, zip, xml
- **Composer**: Para gerenciamento de dependências

### Instalação Passo a Passo

#### 1. Configuração do Servidor
```bash
# Apache - habilitar mod_rewrite
sudo a2enmod rewrite

# PHP - extensões necessárias
sudo apt-get install php-mysql php-gd php-curl php-zip php-xml
```

#### 2. Configuração do Banco de Dados
```sql
-- Criar banco de dados
CREATE DATABASE u682219090_faciencia_erp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Importar estrutura
mysql -u root -p u682219090_faciencia_erp < u682219090_faciencia_erp.sql
```

#### 3. Configuração do Sistema
```php
// config/database.php - Ajustar credenciais
define('DB_HOST', 'localhost');
define('DB_NAME', 'nome_do_banco');
define('DB_USER', 'usuario');
define('DB_PASS', 'senha');
```

#### 4. Permissões de Diretórios
```bash
# Permissões para uploads
chmod 755 uploads/
chmod 755 uploads/documentos/
chmod 755 uploads/temp/

# Permissões para logs
chmod 755 logs/
```

#### 5. Instalação de Dependências
```bash
composer install
```

### Configurações Importantes

#### Configurações PHP (php.ini)
```ini
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 300
memory_limit = 256M
```

#### Configurações Apache (.htaccess)
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

---

## 7. Manutenção e Troubleshooting

### Logs do Sistema

#### Localização dos Logs
- **PHP Errors**: `/var/log/apache2/error.log`
- **Sistema**: Logs são gravados via `error_log()`
- **Banco de Dados**: Tabela `logs_sistema`

#### Monitoramento
```php
// Verificar logs de erro
tail -f /var/log/apache2/error.log

// Logs do sistema no banco
SELECT * FROM logs_sistema ORDER BY created_at DESC LIMIT 100;
```

### Problemas Comuns

#### 1. Erro de Conexão com Banco
```
Sintoma: "Erro de conexão com o banco de dados"
Solução: 
- Verificar credenciais em config/database.php
- Verificar se MySQL está rodando
- Verificar permissões do usuário
```

#### 2. Erro de Upload de Arquivos
```
Sintoma: Falha no upload de documentos
Solução:
- Verificar permissões do diretório uploads/
- Verificar configurações PHP (upload_max_filesize)
- Verificar espaço em disco
```

#### 3. Erro de Geração de PDF
```
Sintoma: PDFs não são gerados
Solução:
- Verificar se TCPDF está instalado
- Verificar permissões de escrita
- Verificar memória PHP
```

### Scripts de Manutenção

#### Limpeza de Arquivos Temporários
```bash
#!/bin/bash
# scripts/cleanup_temp.sh
find uploads/temp/ -type f -mtime +7 -delete
```

#### Backup Automático
```bash
#!/bin/bash
# scripts/backup.sh
mysqldump -u user -p database > backup_$(date +%Y%m%d).sql
```

---

## 8. Segurança

### Medidas de Segurança Implementadas

#### 1. Autenticação e Autorização
- Senhas criptografadas com bcrypt
- Controle de sessões
- Verificação de permissões por módulo
- Timeout de sessão

#### 2. Proteção contra Ataques
- **SQL Injection**: Uso de prepared statements
- **XSS**: Sanitização de inputs com `htmlspecialchars()`
- **CSRF**: Tokens de validação (a implementar)
- **File Upload**: Validação de tipos e tamanhos

#### 3. Logs de Auditoria
- Todas as ações são logadas
- Rastreamento de alterações
- Logs de acesso

### Configurações de Segurança

#### Headers de Segurança
```php
// includes/security.php
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
```

#### Validação de Inputs
```php
// Sempre sanitizar inputs
$input = sanitize($_POST['data']);

// Validar CPF/CNPJ
if (!validarCpf($cpf)) {
    throw new Exception('CPF inválido');
}
```

---

## 9. APIs e Integrações

### APIs Internas

#### Estrutura das APIs (`/api/`)
- `alunos.php`: CRUD de alunos
- `cursos.php`: Gestão de cursos
- `documentos.php`: Geração de documentos
- `relatorios.php`: Relatórios em JSON

#### Exemplo de Uso
```javascript
// Buscar alunos via AJAX
fetch('/api/alunos.php?action=search&nome=João')
    .then(response => response.json())
    .then(data => console.log(data));
```

### Integrações Externas

#### API Bancária (Itaú)
- Geração de boletos
- Consulta de status
- Webhooks de pagamento

#### APIs de CEP
- Busca automática de endereços
- Validação de CEPs

---

## 10. Backup e Recuperação

### Estratégia de Backup

#### 1. Backup do Banco de Dados
```bash
# Backup diário
mysqldump -u user -p --single-transaction database > backup_$(date +%Y%m%d).sql

# Backup com compressão
mysqldump -u user -p database | gzip > backup_$(date +%Y%m%d).sql.gz
```

#### 2. Backup de Arquivos
```bash
# Backup dos uploads
tar -czf uploads_backup_$(date +%Y%m%d).tar.gz uploads/

# Backup completo do sistema
tar -czf sistema_backup_$(date +%Y%m%d).tar.gz --exclude='vendor' .
```

### Procedimento de Recuperação

#### 1. Restaurar Banco de Dados
```bash
mysql -u user -p database < backup_20241201.sql
```

#### 2. Restaurar Arquivos
```bash
tar -xzf uploads_backup_20241201.tar.gz
```

### Automação de Backups

#### Script de Backup Automático
```bash
#!/bin/bash
# /scripts/backup_automatico.sh

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backups"

# Backup do banco
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME > $BACKUP_DIR/db_$DATE.sql

# Backup dos arquivos
tar -czf $BACKUP_DIR/files_$DATE.tar.gz uploads/

# Limpar backups antigos (manter 30 dias)
find $BACKUP_DIR -name "*.sql" -mtime +30 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +30 -delete
```

#### Crontab para Execução Automática
```bash
# Backup diário às 2h da manhã
0 2 * * * /path/to/scripts/backup_automatico.sh
```

---

## 17. Módulo Administrador (`/administrador/`)

### 17.1 Visão Geral do Módulo

O módulo administrador é o centro de controle do sistema Faciência ERP, fornecendo acesso completo a todas as funcionalidades de gestão, configuração e monitoramento do sistema. Este módulo foi implementado em Dezembro de 2024 e atualizado em Junho de 2025.

#### Funcionalidades Principais
- **Dashboard Executivo**: Visão geral com KPIs e estatísticas em tempo real
- **Gestão de Usuários**: CRUD completo com controle de permissões
- **Configurações Globais**: Parâmetros gerais do sistema
- **Sistema de Logs**: Auditoria completa de ações
- **Gestão de Módulos**: Controle e acesso a todos os módulos do sistema

### 17.2 Estrutura de Arquivos

```
administrador/
├── index.php               # Dashboard principal
├── usuarios.php            # Gestão de usuários
├── configuracoes.php       # Configurações do sistema
├── logs.php               # Visualização de logs
├── modulos.php            # Gestão de módulos
├── criar_admin_master.sql # Script de criação do admin master
├── INSTALACAO.md          # Instruções de instalação
├── LOGS_IMPLEMENTADOS.md  # Documentação dos logs
├── CORRECOES.md           # Histórico de correções
├── README.md              # Documentação específica
├── css/                   # Estilos específicos do módulo
│   └── admin.css
├── includes/              # Arquivos de apoio
│   ├── header.php
│   ├── footer.php
│   ├── init.php
│   └── functions.php
├── js/                    # Scripts JavaScript
│   └── admin.js
└── views/                 # Templates específicos
    ├── dashboard.php
    ├── usuarios.php
    └── configuracoes.php
```

### 17.3 Dashboard Administrativo

#### Estatísticas Principais
- **Total de Usuários Ativos**: Contagem em tempo real
- **Módulos do Sistema**: Status de cada módulo
- **Acessos Hoje**: Número de logins do dia
- **Status do Sistema**: Indicadores de saúde

#### Implementação das Estatísticas
```php
// administrador/includes/init.php
function contarModulosAtivos() {
    $modulos = [
        'secretaria', 'polo', 'financeiro', 'ava', 
        'aluno', 'chamados', 'administrador'
    ];
    return count($modulos);
}

function contarUsuariosOnline() {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT usuario_id) 
        FROM logs_sistema 
        WHERE acao = 'login' 
        AND created_at >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)
    ");
    $stmt->execute();
    return $stmt->fetchColumn();
}

function contarAcessosHoje() {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM logs_sistema 
        WHERE acao = 'login' 
        AND DATE(created_at) = CURDATE()
    ");
    $stmt->execute();
    return $stmt->fetchColumn();
}

function verificarStatusSistema() {
    // Verifica conectividade do banco, espaço em disco, etc.
    try {
        global $pdo;
        $pdo->query("SELECT 1");
        return "Operacional";
    } catch (Exception $e) {
        return "Problema";
    }
}
```

### 17.4 Sistema de Logs

#### Tipos de Logs Implementados
- **Login/Logout**: Autenticação de usuários
- **CRUD Operations**: Create, Read, Update, Delete
- **Acesso a Módulos**: Rastreamento de navegação
- **Erros de Sistema**: Falhas e exceções
- **Configurações**: Alterações de configuração

#### Estrutura da Tabela de Logs
```sql
CREATE TABLE logs_sistema (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    acao VARCHAR(100) NOT NULL,
    descricao TEXT,
    tabela_afetada VARCHAR(100),
    registro_id INT,
    dados_anteriores JSON,
    dados_novos JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usuario_id (usuario_id),
    INDEX idx_acao (acao),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
);
```

#### Função de Log Automático
```php
// includes/functions.php
function registrarLog($acao, $descricao, $tabela = null, $registro_id = null, $dados_anteriores = null, $dados_novos = null) {
    global $pdo;
    
    $usuario_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO logs_sistema 
            (usuario_id, acao, descricao, tabela_afetada, registro_id, dados_anteriores, dados_novos, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $usuario_id,
            $acao,
            $descricao,
            $tabela,
            $registro_id,
            $dados_anteriores ? json_encode($dados_anteriores, JSON_UNESCAPED_UNICODE) : null,
            $dados_novos ? json_encode($dados_novos, JSON_UNESCAPED_UNICODE) : null,
            $ip_address,
            $user_agent
        ]);
        
        return true;
    } catch (Exception $e) {
        error_log("Erro ao registrar log: " . $e->getMessage());
        return false;
    }
}
```

### 17.5 Gestão de Usuários

#### Tipos de Usuário Suportados
- **admin_master**: Acesso total ao sistema
- **diretoria**: Acesso a relatórios e gestão geral
- **secretaria_academica**: Gestão acadêmica completa
- **secretaria_documentos**: Foco em documentos
- **financeiro**: Módulo financeiro
- **polo**: Acesso restrito ao polo específico
- **professor**: Portal do professor
- **aluno**: Portal do aluno

#### Controle de Permissões
```php
// includes/Auth.php
class Auth {
    public static function hasPermission($modulo, $acao = 'view') {
        if (!self::isLoggedIn()) {
            return false;
        }
        
        $userType = $_SESSION['user_type'];
        
        // Admin master tem acesso total
        if ($userType === 'admin_master') {
            return true;
        }
        
        // Mapeamento de permissões por tipo de usuário
        $permissions = [
            'diretoria' => ['secretaria', 'financeiro', 'relatorios', 'administrador'],
            'secretaria_academica' => ['secretaria', 'aluno', 'ava'],
            'secretaria_documentos' => ['secretaria', 'documentos'],
            'financeiro' => ['financeiro', 'aluno'],
            'polo' => ['polo', 'aluno', 'chamados'],
            'professor' => ['ava', 'aluno'],
            'aluno' => ['aluno', 'ava']
        ];
        
        return in_array($modulo, $permissions[$userType] ?? []);
    }
}
```

### 17.6 Configurações do Sistema

#### Tipos de Configurações
- **Configurações Gerais**: Nome da instituição, endereço, contatos
- **Configurações de Email**: SMTP, templates de email
- **Configurações de Upload**: Limites, tipos permitidos
- **Configurações de Documentos**: Templates, numeração
- **Configurações de Backup**: Frequência, local de armazenamento

#### Interface de Configuração
```php
// administrador/configuracoes.php
// Interface para gerenciar configurações dinâmicas
// Permite edição de valores sem alterar código

$configuracoes = [
    'instituicao_nome' => 'Nome da Instituição',
    'instituicao_endereco' => 'Endereço',
    'email_smtp_host' => 'Servidor SMTP',
    'email_smtp_port' => 'Porta SMTP',
    'upload_max_size' => 'Tamanho Máximo Upload (MB)',
    'documento_prefixo' => 'Prefixo dos Documentos'
];
```

### 17.7 Gestão de Módulos

#### Módulos do Sistema
1. **Secretaria**: Portal da secretaria acadêmica
2. **Polo**: Portal dos polos de ensino
3. **Financeiro**: Gestão financeira e mensalidades
4. **AVA**: Ambiente Virtual de Aprendizagem
5. **Aluno**: Portal do aluno
6. **Chamados**: Sistema de suporte
7. **Administrador**: Módulo de administração

#### Interface de Módulos
```php
// administrador/modulos.php
$modulos = [
    'secretaria' => [
        'nome' => 'Secretaria Acadêmica',
        'descricao' => 'Gestão de alunos, cursos e matrículas',
        'url' => '../secretaria/',
        'icone' => 'fas fa-graduation-cap',
        'status' => 'ativo'
    ],
    'financeiro' => [
        'nome' => 'Financeiro',
        'descricao' => 'Controle de mensalidades e boletos',
        'url' => '../financeiro/',
        'icone' => 'fas fa-dollar-sign',
        'status' => 'ativo'
    ],
    // ... outros módulos
];
```

### 17.8 Instalação do Módulo Administrador

#### Script de Criação do Admin Master
```sql
-- criar_admin_master.sql
INSERT INTO usuarios (
    nome, 
    email, 
    senha, 
    tipo_usuario, 
    status, 
    created_at
) VALUES (
    'Administrador Master',
    'admin@faciencia.edu.br',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- senha: password
    'admin_master',
    'ativo',
    NOW()
);
```

#### Passos de Instalação
1. **Criar usuário admin master**: Executar script SQL
2. **Configurar permissões**: Verificar estrutura de pastas
3. **Testar acesso**: Login com credenciais padrão
4. **Configurar sistema**: Definir configurações iniciais

### 17.9 Monitoramento e Manutenção

#### Indicadores de Performance
- **Tempo de Resposta**: Monitoramento de páginas
- **Uso de Recursos**: CPU, memória, disco
- **Logs de Erro**: Falhas do sistema
- **Backups**: Status e frequência

#### Manutenção Preventiva
- **Limpeza de Logs**: Remoção automática de logs antigos
- **Otimização de Banco**: Rebuild de índices
- **Backup Automático**: Agendamento de backups
- **Monitoramento de Espaço**: Alertas de capacidade

---

## 18. Atualizações Recentes (Dezembro 2024 - Junho 2025)

### 18.1 Principais Melhorias Implementadas

#### Módulo Administrador (Dezembro 2024)
- **Implementação Completa**: Criação do módulo administrador
- **Dashboard Executivo**: KPIs e estatísticas em tempo real
- **Sistema de Logs**: Auditoria completa de ações
- **Gestão de Usuários**: CRUD com controle de permissões
- **Configurações Globais**: Interface para parâmetros do sistema

#### Correções de Bugs (Junho 2025)
- **Fix do modulos.php**: Correção do erro "Failed to open stream"
- **Estrutura de Header**: Padronização do layout administrativo
- **Navigation**: Implementação de navegação responsiva
- **Layout Moderno**: Atualização para TailwindCSS

#### Sistema de Logging (Janeiro 2025)
- **Logs Automáticos**: Registro automático de todas as ações
- **Auditoria**: Rastreamento completo de alterações
- **Performance**: Otimização de consultas de log
- **Interface**: Visualização amigável dos logs

### 18.2 Melhorias de Segurança

#### Autenticação Aprimorada
- **Controle de Sessão**: Timeout e renovação automática
- **Verificação de Permissões**: Validação em cada ação
- **Logs de Segurança**: Registro de tentativas de acesso
- **Proteção CSRF**: Implementação de tokens (em desenvolvimento)

#### Validação de Dados
- **Sanitização**: Limpeza automática de inputs
- **Validação**: Verificação de tipos e formatos
- **Escape**: Proteção contra XSS
- **Prepared Statements**: Proteção contra SQL Injection

### 18.3 Otimizações de Performance

#### Banco de Dados
- **Índices Otimizados**: Criação de índices estratégicos
- **Consultas Melhoradas**: Refatoração de queries lentas
- **Cache de Dados**: Implementação de cache simples
- **Pool de Conexões**: Otimização de conexões

#### Frontend
- **CSS Minificado**: Redução do tamanho dos arquivos
- **JavaScript Otimizado**: Compressão e cache
- **Imagens Comprimidas**: Otimização de recursos
- **Lazy Loading**: Carregamento sob demanda

### 18.4 Documentação Atualizada

#### Documentação Técnica
- **Versão 2.0**: Documentação completamente atualizada
- **Exemplos de Código**: Mais exemplos práticos
- **Troubleshooting**: Seção expandida de solução de problemas
- **APIs**: Documentação das APIs internas

#### Guias de Usuário
- **Manual do Administrador**: Guia completo do módulo
- **Manual da Secretaria**: Procedimentos atualizados
- **Manual dos Polos**: Instruções específicas
- **FAQ**: Perguntas frequentes e respostas

---

## 19. Roadmap Futuro

### 19.1 Próximas Versões

#### Versão 2.1 (Julho 2025)
- **API REST Completa**: Endpoints para todos os módulos
- **Mobile App**: Aplicativo móvel para alunos
- **Notificações Push**: Sistema de notificações em tempo real
- **Integração WhatsApp**: Envio de documentos via WhatsApp

#### Versão 2.2 (Setembro 2025)
- **Business Intelligence**: Dashboard analítico avançado
- **Machine Learning**: Predição de inadimplência
- **Blockchain**: Certificados digitais com blockchain
- **Multi-tenancy**: Suporte a múltiplas instituições

### 19.2 Melhorias Planejadas

#### Segurança
- **Two-Factor Authentication**: Autenticação em dois fatores
- **Single Sign-On**: Integração com sistemas externos
- **Auditoria Avançada**: Logs mais detalhados
- **Compliance**: Adequação à LGPD

#### Performance
- **Microserviços**: Arquitetura distribuída
- **CDN**: Content Delivery Network
- **Cache Distribuído**: Redis/Memcached
- **Load Balancing**: Balanceamento de carga

---

## Contatos e Suporte

### Informações do Desenvolvedor
- **Sistema**: Faciência ERP
- **Versão Atual**: 2.0
- **Última Atualização**: Junho 2025
- **Linguagem Principal**: PHP 7.4+
- **Banco de Dados**: MySQL 8.0+
- **Framework CSS**: TailwindCSS 3.0

### Histórico de Versões
- **v1.0** (2024): Versão inicial
- **v1.1** (Dezembro 2024): Módulo administrador
- **v1.2** (Janeiro 2025): Sistema de logs
- **v2.0** (Junho 2025): Correções e melhorias

### Para Manutenção Futura
- Manter documentação atualizada com cada versão
- Realizar backups regulares (diário/semanal)
- Monitorar logs de erro e performance
- Atualizar dependências e bibliotecas regularmente
- Implementar melhorias de segurança continuamente
- Realizar testes de penetração periodicamente
- Manter compatibilidade com versões PHP/MySQL mais recentes

---

## 11. Detalhamento dos Módulos

### 11.1 Portal da Secretaria (`/secretaria/`)

#### Funcionalidades Principais
- **Dashboard**: Visão geral com estatísticas e pendências
- **Gestão de Alunos**: CRUD completo, busca avançada, histórico
- **Gestão de Cursos**: Criação e manutenção de cursos
- **Matrículas**: Processo completo de matrícula
- **Documentos**: Geração e controle de documentos acadêmicos
- **Relatórios**: Relatórios gerenciais e acadêmicos

#### Arquivos Principais
```
secretaria/
├── index.php              # Dashboard principal
├── alunos.php             # Gestão de alunos
├── cursos.php             # Gestão de cursos
├── matriculas.php         # Sistema de matrículas
├── documentos.php         # Geração de documentos
├── relatorios.php         # Relatórios
├── turmas.php             # Gestão de turmas
├── disciplinas.php        # Gestão de disciplinas
├── notas.php              # Lançamento de notas
└── includes/              # Arquivos de apoio
```

#### Fluxo de Trabalho Típico
1. **Cadastro de Aluno**: secretaria/alunos.php?action=novo
2. **Matrícula**: secretaria/matriculas.php?action=nova
3. **Geração de Documentos**: secretaria/documentos.php
4. **Acompanhamento**: Dashboard com pendências

### 11.2 Portal dos Polos (`/polo/`)

#### Características Específicas
- **Acesso Restrito**: Cada polo vê apenas seus dados
- **Funcionalidades Limitadas**: Visualização e solicitações
- **Controle de Limites**: Limite de documentos por polo

#### Arquivos Principais
```
polo/
├── index.php              # Dashboard do polo
├── alunos.php             # Visualizar alunos do polo
├── documentos.php         # Solicitar documentos
├── matriculas.php         # Visualizar matrículas
├── chamados.php           # Sistema de suporte
└── includes/              # Headers e funções específicas
```

#### Controle de Acesso
```php
// Verificação automática do polo do usuário
$polo_id = getUsuarioPoloId();
if (!$polo_id) {
    redirect('login.php');
}

// Filtro automático por polo em todas as consultas
$sql = "SELECT * FROM alunos WHERE polo_id = ?";
```

### 11.3 Sistema AVA (`/ava/`)

#### Estrutura do AVA
- **Cursos Online**: Gestão de cursos EAD
- **Aulas**: Vídeos e materiais didáticos
- **Progresso**: Acompanhamento do aluno
- **Avaliações**: Sistema de provas online

#### Arquivos Principais
```
ava/
├── dashboard.php          # Dashboard do AVA
├── cursos.php             # Gestão de cursos EAD
├── aulas.php              # Gestão de aulas
├── alunos.php             # Alunos no AVA
├── progresso.php          # Acompanhamento
├── matriculas.php         # Matrículas EAD
└── includes/              # Layout e funções AVA
```

#### Funcionalidades Específicas
- **Upload de Vídeos**: Sistema de upload para aulas
- **Controle de Progresso**: Marcação de aulas assistidas
- **Certificados**: Emissão automática ao concluir curso
- **Relatórios**: Desempenho e engajamento

### 11.4 Sistema Financeiro (`/financeiro/`)

#### Módulos Financeiros
- **Mensalidades**: Controle de pagamentos de alunos
- **Boletos**: Geração via API bancária
- **Folha de Pagamento**: Gestão de funcionários
- **Contas a Pagar/Receber**: Controle financeiro geral
- **Relatórios**: Análises financeiras

#### Arquivos Principais
```
financeiro/
├── index.php              # Dashboard financeiro
├── mensalidades.php       # Controle de mensalidades
├── boletos.php            # Geração de boletos
├── funcionarios.php       # Cadastro de funcionários
├── folha_pagamento.php    # Folha de pagamento
├── contas_pagar.php       # Contas a pagar
├── contas_receber.php     # Contas a receber
├── relatorios.php         # Relatórios financeiros
└── ajax/                  # Scripts AJAX
```

#### Integração Bancária
```php
// Exemplo de geração de boleto
$boleto = new BoletoItau();
$boleto->setDados([
    'valor' => $mensalidade['valor'],
    'vencimento' => $mensalidade['vencimento'],
    'sacado' => $aluno['nome'],
    'cpf' => $aluno['cpf']
]);
$boleto->gerar();
```

### 11.5 Sistema de Chamados (`/chamados/`)

#### Tipos de Chamados
- **Internos**: Para funcionários da instituição
- **Polos**: Para suporte aos polos
- **Categorias**: Diferentes tipos de solicitações

#### Workflow de Chamados
1. **Abertura**: Usuário cria chamado
2. **Triagem**: Sistema categoriza automaticamente
3. **Atribuição**: Chamado é direcionado ao departamento
4. **Resolução**: Técnico resolve o problema
5. **Fechamento**: Usuário confirma resolução

#### Arquivos Principais
```
chamados/
├── index.php              # Lista de chamados
├── novo.php               # Criar novo chamado
├── visualizar.php         # Ver detalhes do chamado
├── responder.php          # Responder chamado
├── processar.php          # Processar ações
└── ajax/                  # Scripts AJAX
```

---

## 12. Sistema de Documentos

### 12.1 Tipos de Documentos Suportados

#### Documentos Acadêmicos
- **Declaração de Matrícula**: Comprova vínculo com a instituição
- **Histórico Escolar**: Registro completo das disciplinas
- **Certificado de Conclusão**: Documento de formatura
- **Declaração de Conclusão**: Declaração temporária
- **Declaração Personalizada**: Documentos sob demanda

#### Templates de Documentos
```
templates/
├── declaracao_matricula.html
├── historico_escolar.html
├── certificado_conclusao.html
├── declaracao_conclusao.html
└── declaracao_personalizada.html
```

### 12.2 Processo de Geração

#### Fluxo Completo
1. **Solicitação**: Via sistema ou portal
2. **Validação**: Verificação de dados e permissões
3. **Geração**: Processamento do template
4. **Assinatura**: Assinatura digital (opcional)
5. **Entrega**: Download ou envio por email

#### Código de Geração
```php
// includes/DocumentGenerator.php
class DocumentGenerator {
    public function gerarDocumento($tipo, $aluno_id, $dados_extras = []) {
        // 1. Buscar dados do aluno
        $aluno = $this->buscarDadosAluno($aluno_id);

        // 2. Carregar template
        $template = $this->carregarTemplate($tipo);

        // 3. Substituir variáveis
        $html = $this->processarTemplate($template, $aluno, $dados_extras);

        // 4. Gerar PDF
        $pdf = $this->gerarPDF($html);

        // 5. Salvar e retornar
        return $this->salvarDocumento($pdf, $tipo, $aluno_id);
    }
}
```

### 12.3 Controle de Numeração

#### Sistema de Numeração
- **Prefixo**: FAC-DOC-
- **Ano**: 2024
- **Sequencial**: 000001
- **Formato Final**: FAC-DOC-2024-000001

#### Implementação
```php
function gerarNumeroDocumento($id) {
    return DOCUMENTO_PREFIX . DOCUMENTO_YEAR . str_pad($id, 6, '0', STR_PAD_LEFT);
}
```

---

## 13. Sistema de Relatórios

### 13.1 Tipos de Relatórios

#### Relatórios Acadêmicos
- **Relatório de Alunos**: Lista com filtros avançados
- **Relatório de Matrículas**: Matrículas por período
- **Relatório de Documentos**: Documentos emitidos
- **Relatório de Turmas**: Situação das turmas
- **Relatório de Notas**: Desempenho acadêmico

#### Relatórios Financeiros
- **Relatório de Mensalidades**: Situação de pagamentos
- **Relatório de Inadimplência**: Alunos em atraso
- **Relatório de Boletos**: Boletos gerados
- **Relatório de Receitas**: Receitas por período
- **Fluxo de Caixa**: Entradas e saídas

#### Relatórios Gerenciais
- **Dashboard Executivo**: KPIs principais
- **Relatório de Polos**: Desempenho por polo
- **Relatório de Cursos**: Estatísticas por curso
- **Relatório de Chamados**: Suporte e atendimento

### 13.2 Formatos de Exportação

#### Formatos Suportados
- **PDF**: Para impressão e arquivo
- **Excel**: Para análise de dados
- **CSV**: Para importação em outros sistemas
- **JSON**: Para APIs e integrações

#### Implementação
```php
// Exportação para Excel
function exportarExcel($dados, $colunas) {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Cabeçalhos
    $col = 'A';
    foreach ($colunas as $coluna) {
        $sheet->setCellValue($col . '1', $coluna);
        $col++;
    }

    // Dados
    $row = 2;
    foreach ($dados as $linha) {
        $col = 'A';
        foreach ($linha as $valor) {
            $sheet->setCellValue($col . $row, $valor);
            $col++;
        }
        $row++;
    }

    // Salvar
    $writer = new Xlsx($spreadsheet);
    $writer->save('relatorio.xlsx');
}
```

---

## 14. Configurações Avançadas

### 14.1 Configurações do Sistema

#### Arquivo de Configuração Principal
```php
// config/config.php

// URLs e Caminhos
define('BASE_URL', 'http://localhost');
define('ROOT_DIR', dirname(__DIR__));
define('UPLOADS_DIR', ROOT_DIR . '/uploads');

// Configurações de Upload
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10MB

// Configurações de Sessão
define('SESSION_NAME', 'faciencia_erp_session');
define('SESSION_LIFETIME', 360000); // 1 hora

// Configurações de Email
define('MAIL_FROM', 'sistema@faciencia.edu.br');
define('MAIL_FROM_NAME', 'Sistema Faciência ERP');

// Configurações de Documentos
define('DOCUMENTO_PREFIX', 'FAC-DOC-');
define('MATRICULA_PREFIX', 'FAC-');
```

#### Configurações Dinâmicas
```sql
-- Tabela de configurações dinâmicas
CREATE TABLE configuracoes_sistema (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chave VARCHAR(100) NOT NULL UNIQUE,
    valor TEXT,
    tipo ENUM('string', 'numero', 'booleano', 'json') DEFAULT 'string',
    descricao TEXT,
    grupo VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### 14.2 Configurações por Módulo

#### Configurações do AVA
- **Limite de Upload**: Tamanho máximo para vídeos
- **Formatos Aceitos**: Tipos de arquivo permitidos
- **Tempo de Sessão**: Timeout específico para AVA

#### Configurações Financeiras
- **Juros e Multa**: Percentuais de atraso
- **Dias de Vencimento**: Padrão para boletos
- **Desconto**: Regras de desconto automático

#### Configurações de Documentos
- **Templates**: Personalização por instituição
- **Assinaturas**: Configuração de assinaturas digitais
- **Numeração**: Padrões de numeração

---

## 15. Monitoramento e Performance

### 15.1 Monitoramento do Sistema

#### Métricas Importantes
- **Tempo de Resposta**: Páginas e APIs
- **Uso de Memória**: Consumo PHP
- **Conexões de Banco**: Pool de conexões
- **Espaço em Disco**: Uploads e logs
- **Erros**: Taxa de erro por módulo

#### Scripts de Monitoramento
```bash
#!/bin/bash
# scripts/monitor.sh

# Verificar espaço em disco
df -h | grep -E "/$|/var|/uploads"

# Verificar processos PHP
ps aux | grep php | wc -l

# Verificar conexões MySQL
mysql -e "SHOW PROCESSLIST;" | wc -l

# Verificar logs de erro
tail -n 100 /var/log/apache2/error.log | grep -i error | wc -l
```

### 15.2 Otimização de Performance

#### Otimizações de Banco de Dados
```sql
-- Índices importantes
CREATE INDEX idx_alunos_nome ON alunos(nome);
CREATE INDEX idx_alunos_cpf ON alunos(cpf);
CREATE INDEX idx_alunos_status ON alunos(status);
CREATE INDEX idx_matriculas_aluno ON matriculas(aluno_id);
CREATE INDEX idx_documentos_aluno ON solicitacoes_documentos(aluno_id);

-- Otimização de consultas
EXPLAIN SELECT * FROM alunos WHERE nome LIKE '%João%';
```

#### Cache de Dados
```php
// Implementação simples de cache
class SimpleCache {
    private static $cache = [];

    public static function get($key) {
        return self::$cache[$key] ?? null;
    }

    public static function set($key, $value, $ttl = 3600) {
        self::$cache[$key] = [
            'value' => $value,
            'expires' => time() + $ttl
        ];
    }

    public static function isValid($key) {
        return isset(self::$cache[$key]) &&
               self::$cache[$key]['expires'] > time();
    }
}
```

---

## 16. Troubleshooting Avançado

### 16.1 Problemas Comuns e Soluções

#### Erro: "Class 'Database' not found"
```
Causa: Autoload não está funcionando ou classe não foi incluída
Solução:
1. Verificar se includes/init.php está sendo carregado
2. Verificar se Database.php existe em includes/
3. Verificar configuração do autoload em config.php
```

#### Erro: "Call to undefined function exigirLogin()"
```
Causa: functions.php não foi carregado
Solução:
1. Verificar se includes/functions.php existe
2. Verificar se está sendo incluído em init.php
3. Verificar ordem de carregamento dos arquivos
```

#### Erro: "TCPDF error: Unable to create output file"
```
Causa: Permissões de escrita ou espaço em disco
Solução:
1. chmod 755 uploads/documentos/
2. Verificar espaço em disco: df -h
3. Verificar se diretório existe
4. Verificar configuração de temp_dir no PHP
```

#### Erro: "MySQL server has gone away"
```
Causa: Timeout de conexão ou consulta muito longa
Solução:
1. Aumentar wait_timeout no MySQL
2. Implementar reconexão automática
3. Otimizar consultas lentas
4. Verificar max_allowed_packet
```

### 16.2 Logs e Debugging

#### Ativação de Debug
```php
// config/config.php - Modo debug
define('DEBUG_MODE', true);
define('LOG_LEVEL', 'debug');

// Exibir erros PHP
if (DEBUG_MODE) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}
```

#### Logs Personalizados
```php
// Função para log detalhado
function debugLog($message, $data = null) {
    if (DEBUG_MODE) {
        $log = date('Y-m-d H:i:s') . " - " . $message;
        if ($data) {
            $log .= " - Data: " . json_encode($data);
        }
        error_log($log);
    }
}

// Uso
debugLog("Iniciando processo de matrícula", ['aluno_id' => 123]);
```

#### Monitoramento de Performance
```php
// Medição de tempo de execução
$start_time = microtime(true);

// ... código a ser medido ...

$end_time = microtime(true);
$execution_time = ($end_time - $start_time);
debugLog("Tempo de execução: " . $execution_time . " segundos");
```

### 16.3 Ferramentas de Diagnóstico

#### Script de Diagnóstico do Sistema
```php
// scripts/diagnostico.php
<?php
require_once '../includes/init.php';

echo "=== DIAGNÓSTICO DO SISTEMA FACIÊNCIA ERP ===\n\n";

// 1. Verificar PHP
echo "PHP Version: " . phpversion() . "\n";
echo "Memory Limit: " . ini_get('memory_limit') . "\n";
echo "Upload Max Size: " . ini_get('upload_max_filesize') . "\n";
echo "Post Max Size: " . ini_get('post_max_size') . "\n\n";

// 2. Verificar extensões
$required_extensions = ['pdo', 'pdo_mysql', 'gd', 'curl', 'zip'];
foreach ($required_extensions as $ext) {
    echo "Extensão $ext: " . (extension_loaded($ext) ? "OK" : "FALTANDO") . "\n";
}
echo "\n";

// 3. Verificar banco de dados
try {
    $db = Database::getInstance();
    echo "Conexão com banco: OK\n";

    $result = $db->fetchOne("SELECT COUNT(*) as total FROM usuarios");
    echo "Total de usuários: " . $result['total'] . "\n";
} catch (Exception $e) {
    echo "Erro no banco: " . $e->getMessage() . "\n";
}
echo "\n";

// 4. Verificar diretórios
$directories = [
    'uploads' => UPLOADS_DIR,
    'documentos' => DOCUMENTOS_DIR,
    'temp' => TEMP_DIR
];

foreach ($directories as $name => $path) {
    echo "Diretório $name: ";
    if (file_exists($path)) {
        echo is_writable($path) ? "OK (escrita)" : "SEM PERMISSÃO";
    } else {
        echo "NÃO EXISTE";
    }
    echo "\n";
}
?>
```

---

## 17. APIs e Integrações Detalhadas

### 17.1 APIs Internas

#### Estrutura Padrão das APIs
```php
// api/base.php
<?php
header('Content-Type: application/json');
require_once '../includes/init.php';

// Verificar autenticação
if (!Auth::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autenticado']);
    exit;
}

// Função padrão de resposta
function apiResponse($data = null, $error = null, $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => $error === null,
        'data' => $data,
        'error' => $error,
        'timestamp' => date('c')
    ]);
    exit;
}
?>
```

#### API de Alunos
```php
// api/alunos.php
<?php
require_once 'base.php';

$action = $_GET['action'] ?? '';
$aluno = new Aluno();

switch ($action) {
    case 'search':
        $filtros = [
            'nome' => $_GET['nome'] ?? '',
            'cpf' => $_GET['cpf'] ?? '',
            'status' => $_GET['status'] ?? ''
        ];

        $resultados = $aluno->getAll($filtros, 50, 0);
        apiResponse($resultados);
        break;

    case 'get':
        $id = $_GET['id'] ?? 0;
        if (!$id) {
            apiResponse(null, 'ID obrigatório', 400);
        }

        $dados = $aluno->getById($id);
        if (!$dados) {
            apiResponse(null, 'Aluno não encontrado', 404);
        }

        apiResponse($dados);
        break;

    case 'create':
        if (!isPost()) {
            apiResponse(null, 'Método não permitido', 405);
        }

        $dados = json_decode(file_get_contents('php://input'), true);

        try {
            $id = $aluno->create($dados);
            apiResponse(['id' => $id], null, 201);
        } catch (Exception $e) {
            apiResponse(null, $e->getMessage(), 400);
        }
        break;

    default:
        apiResponse(null, 'Ação não encontrada', 404);
}
?>
```

### 17.2 Integração com APIs Externas

#### API de CEP (ViaCEP)
```php
// includes/CepService.php
class CepService {
    public static function buscarCep($cep) {
        $cep = preg_replace('/[^0-9]/', '', $cep);

        if (strlen($cep) !== 8) {
            throw new Exception('CEP inválido');
        }

        $url = "https://viacep.com.br/ws/{$cep}/json/";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception('Erro ao consultar CEP');
        }

        $data = json_decode($response, true);

        if (isset($data['erro'])) {
            throw new Exception('CEP não encontrado');
        }

        return [
            'logradouro' => $data['logradouro'],
            'bairro' => $data['bairro'],
            'cidade' => $data['localidade'],
            'uf' => $data['uf'],
            'cep' => $data['cep']
        ];
    }
}
```

#### API Bancária (Itaú)
```php
// includes/BoletoItau.php
class BoletoItau {
    private $client_id;
    private $client_secret;
    private $access_token;

    public function __construct() {
        $this->client_id = getConfiguracao('itau_client_id');
        $this->client_secret = getConfiguracao('itau_client_secret');
    }

    public function gerarBoleto($dados) {
        $this->autenticar();

        $payload = [
            'data_vencimento' => $dados['vencimento'],
            'valor' => $dados['valor'],
            'sacado' => [
                'nome' => $dados['nome'],
                'cpf' => $dados['cpf'],
                'endereco' => $dados['endereco']
            ],
            'instrucoes' => $dados['instrucoes'] ?? ''
        ];

        $response = $this->fazerRequisicao('POST', '/boletos', $payload);

        return [
            'codigo_barras' => $response['codigo_barras'],
            'linha_digitavel' => $response['linha_digitavel'],
            'url_pdf' => $response['url_pdf'],
            'nosso_numero' => $response['nosso_numero']
        ];
    }

    private function autenticar() {
        $payload = [
            'grant_type' => 'client_credentials',
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret
        ];

        $response = $this->fazerRequisicao('POST', '/oauth/token', $payload);
        $this->access_token = $response['access_token'];
    }

    private function fazerRequisicao($method, $endpoint, $data = null) {
        $url = 'https://api.itau.com.br' . $endpoint;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        $headers = ['Content-Type: application/json'];
        if ($this->access_token) {
            $headers[] = 'Authorization: Bearer ' . $this->access_token;
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 400) {
            throw new Exception('Erro na API do Itaú: ' . $response);
        }

        return json_decode($response, true);
    }
}
```

---

## 18. Procedimentos de Manutenção

### 18.1 Manutenção Preventiva

#### Checklist Semanal
```bash
#!/bin/bash
# scripts/manutencao_semanal.sh

echo "=== MANUTENÇÃO SEMANAL - $(date) ==="

# 1. Backup do banco de dados
echo "1. Realizando backup do banco..."
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME > /backups/weekly_$(date +%Y%m%d).sql

# 2. Limpeza de arquivos temporários
echo "2. Limpando arquivos temporários..."
find uploads/temp/ -type f -mtime +7 -delete
find logs/ -name "*.log" -mtime +30 -delete

# 3. Verificação de espaço em disco
echo "3. Verificando espaço em disco..."
df -h | grep -E "/$|/var"

# 4. Verificação de logs de erro
echo "4. Verificando logs de erro..."
tail -n 100 /var/log/apache2/error.log | grep -i "fatal\|error" | wc -l

# 5. Otimização do banco de dados
echo "5. Otimizando banco de dados..."
mysql -u $DB_USER -p$DB_PASS -e "OPTIMIZE TABLE alunos, matriculas, documentos_emitidos;"

# 6. Verificação de integridade
echo "6. Verificando integridade dos dados..."
php /scripts/verificar_integridade.php

echo "=== MANUTENÇÃO CONCLUÍDA ==="
```

#### Checklist Mensal
```bash
#!/bin/bash
# scripts/manutencao_mensal.sh

echo "=== MANUTENÇÃO MENSAL - $(date) ==="

# 1. Backup completo
echo "1. Backup completo do sistema..."
tar -czf /backups/sistema_completo_$(date +%Y%m).tar.gz \
    --exclude='vendor' \
    --exclude='node_modules' \
    --exclude='*.log' \
    /var/www/faciencia/

# 2. Atualização de dependências
echo "2. Verificando atualizações..."
cd /var/www/faciencia/
composer outdated

# 3. Análise de performance
echo "3. Analisando performance..."
mysql -u $DB_USER -p$DB_PASS -e "
    SELECT table_name,
           ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)'
    FROM information_schema.TABLES
    WHERE table_schema = '$DB_NAME'
    ORDER BY (data_length + index_length) DESC;
"

# 4. Relatório de uso
echo "4. Gerando relatório de uso..."
php /scripts/relatorio_uso_mensal.php

echo "=== MANUTENÇÃO MENSAL CONCLUÍDA ==="
```

### 18.2 Scripts de Verificação

#### Verificação de Integridade dos Dados
```php
// scripts/verificar_integridade.php
<?php
require_once '../includes/init.php';

echo "=== VERIFICAÇÃO DE INTEGRIDADE ===\n";

$db = Database::getInstance();
$problemas = [];

// 1. Verificar alunos sem curso
$sql = "SELECT COUNT(*) as total FROM alunos WHERE curso_id IS NULL OR curso_id = 0";
$result = $db->fetchOne($sql);
if ($result['total'] > 0) {
    $problemas[] = "Encontrados {$result['total']} alunos sem curso definido";
}

// 2. Verificar matrículas órfãs
$sql = "SELECT COUNT(*) as total FROM matriculas m
        LEFT JOIN alunos a ON m.aluno_id = a.id
        WHERE a.id IS NULL";
$result = $db->fetchOne($sql);
if ($result['total'] > 0) {
    $problemas[] = "Encontradas {$result['total']} matrículas órfãs";
}

// 3. Verificar documentos sem aluno
$sql = "SELECT COUNT(*) as total FROM solicitacoes_documentos sd
        LEFT JOIN alunos a ON sd.aluno_id = a.id
        WHERE a.id IS NULL";
$result = $db->fetchOne($sql);
if ($result['total'] > 0) {
    $problemas[] = "Encontrados {$result['total']} documentos órfãos";
}

// 4. Verificar usuários sem tipo
$sql = "SELECT COUNT(*) as total FROM usuarios WHERE tipo IS NULL OR tipo = ''";
$result = $db->fetchOne($sql);
if ($result['total'] > 0) {
    $problemas[] = "Encontrados {$result['total']} usuários sem tipo definido";
}

// Relatório
if (empty($problemas)) {
    echo "✓ Nenhum problema de integridade encontrado\n";
} else {
    echo "⚠ Problemas encontrados:\n";
    foreach ($problemas as $problema) {
        echo "  - $problema\n";
    }
}

echo "\n=== VERIFICAÇÃO CONCLUÍDA ===\n";
?>
```

### 18.3 Atualizações do Sistema

#### Processo de Atualização
```bash
#!/bin/bash
# scripts/atualizar_sistema.sh

echo "=== ATUALIZAÇÃO DO SISTEMA ==="

# 1. Backup antes da atualização
echo "1. Criando backup de segurança..."
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME > /backups/pre_update_$(date +%Y%m%d_%H%M%S).sql

# 2. Colocar sistema em manutenção
echo "2. Ativando modo manutenção..."
touch /var/www/faciencia/maintenance.flag

# 3. Atualizar código
echo "3. Atualizando código..."
git pull origin main

# 4. Atualizar dependências
echo "4. Atualizando dependências..."
composer install --no-dev --optimize-autoloader

# 5. Executar migrações
echo "5. Executando migrações..."
php scripts/migrate.php

# 6. Limpar cache
echo "6. Limpando cache..."
rm -rf cache/*
rm -rf uploads/temp/*

# 7. Verificar sistema
echo "7. Verificando sistema..."
php scripts/diagnostico.php

# 8. Remover modo manutenção
echo "8. Removendo modo manutenção..."
rm -f /var/www/faciencia/maintenance.flag

echo "=== ATUALIZAÇÃO CONCLUÍDA ==="
```

---

## 19. Segurança Avançada

### 19.1 Hardening do Sistema

#### Configurações de Segurança PHP
```ini
; php.ini - Configurações de segurança
expose_php = Off
display_errors = Off
log_errors = On
error_log = /var/log/php_errors.log

; Limitar uploads
file_uploads = On
upload_max_filesize = 10M
max_file_uploads = 5

; Desabilitar funções perigosas
disable_functions = exec,passthru,shell_exec,system,proc_open,popen

; Configurações de sessão
session.cookie_httponly = 1
session.cookie_secure = 1
session.use_strict_mode = 1
session.cookie_samesite = "Strict"
```

#### Headers de Segurança
```php
// includes/security_headers.php
<?php
// Prevenir XSS
header('X-XSS-Protection: 1; mode=block');

// Prevenir clickjacking
header('X-Frame-Options: DENY');

// Prevenir MIME sniffing
header('X-Content-Type-Options: nosniff');

// Content Security Policy
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' cdnjs.cloudflare.com; img-src 'self' data:;");

// HSTS (apenas em HTTPS)
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}
?>
```

### 19.2 Validação e Sanitização

#### Classe de Validação
```php
// includes/Validator.php
class Validator {
    public static function cpf($cpf) {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);

        if (strlen($cpf) != 11 || preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }

        // Validação dos dígitos verificadores
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }

        return true;
    }

    public static function email($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function telefone($telefone) {
        $telefone = preg_replace('/[^0-9]/', '', $telefone);
        return strlen($telefone) >= 10 && strlen($telefone) <= 11;
    }

    public static function cep($cep) {
        $cep = preg_replace('/[^0-9]/', '', $cep);
        return strlen($cep) === 8;
    }

    public static function senha($senha) {
        // Mínimo 8 caracteres, pelo menos 1 maiúscula, 1 minúscula e 1 número
        return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d@$!%*?&]{8,}$/', $senha);
    }
}
```

#### Sanitização de Dados
```php
// includes/Sanitizer.php
class Sanitizer {
    public static function string($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    public static function email($email) {
        return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    }

    public static function cpf($cpf) {
        return preg_replace('/[^0-9]/', '', $cpf);
    }

    public static function telefone($telefone) {
        return preg_replace('/[^0-9]/', '', $telefone);
    }

    public static function cep($cep) {
        return preg_replace('/[^0-9]/', '', $cep);
    }

    public static function filename($filename) {
        // Remove caracteres perigosos de nomes de arquivo
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
        return substr($filename, 0, 255);
    }
}
```

---

## 20. Deployment e Produção

### 20.1 Preparação para Produção

#### Checklist de Deploy
```bash
# 1. Configurações de produção
- [ ] Desabilitar debug mode
- [ ] Configurar logs de produção
- [ ] Configurar backup automático
- [ ] Configurar SSL/HTTPS
- [ ] Configurar firewall
- [ ] Otimizar configurações PHP
- [ ] Configurar cache
- [ ] Testar todas as funcionalidades

# 2. Segurança
- [ ] Alterar senhas padrão
- [ ] Configurar headers de segurança
- [ ] Implementar rate limiting
- [ ] Configurar WAF (Web Application Firewall)
- [ ] Remover arquivos de desenvolvimento
```

#### Configurações de Produção
```php
// config/production.php
<?php
// Configurações específicas para produção

// Debug desabilitado
define('DEBUG_MODE', false);
ini_set('display_errors', 0);
error_reporting(0);

// Logs de produção
define('LOG_ENABLED', true);
define('LOG_LEVEL', 'error');

// Cache habilitado
define('CACHE_ENABLED', true);
define('CACHE_TTL', 3600);

// Configurações de sessão seguras
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);

// Configurações de upload mais restritivas
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB em produção
?>
```

### 20.2 Configuração de Servidor

#### Apache Virtual Host
```apache
<VirtualHost *:443>
    ServerName faciencia.edu.br
    DocumentRoot /var/www/faciencia

    # SSL Configuration
    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/faciencia.crt
    SSLCertificateKeyFile /etc/ssl/private/faciencia.key

    # Security Headers
    Header always set X-Frame-Options DENY
    Header always set X-Content-Type-Options nosniff
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"

    # PHP Configuration
    php_admin_value upload_max_filesize 5M
    php_admin_value post_max_size 5M
    php_admin_value memory_limit 256M
    php_admin_value max_execution_time 300

    # Directory Protection
    <Directory "/var/www/faciencia">
        AllowOverride All
        Require all granted
    </Directory>

    # Protect sensitive directories
    <Directory "/var/www/faciencia/config">
        Require all denied
    </Directory>

    <Directory "/var/www/faciencia/includes">
        Require all denied
    </Directory>

    # Logs
    ErrorLog ${APACHE_LOG_DIR}/faciencia_error.log
    CustomLog ${APACHE_LOG_DIR}/faciencia_access.log combined
</VirtualHost>
```

#### Nginx Configuration (Alternativa)
```nginx
server {
    listen 443 ssl http2;
    server_name faciencia.edu.br;
    root /var/www/faciencia;
    index index.php index.html;

    # SSL Configuration
    ssl_certificate /etc/ssl/certs/faciencia.crt;
    ssl_certificate_key /etc/ssl/private/faciencia.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512;

    # Security Headers
    add_header X-Frame-Options DENY;
    add_header X-Content-Type-Options nosniff;
    add_header X-XSS-Protection "1; mode=block";
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains";

    # PHP Processing
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;

        # PHP Configuration
        fastcgi_param PHP_VALUE "upload_max_filesize=5M
                                post_max_size=5M
                                memory_limit=256M
                                max_execution_time=300";
    }

    # Protect sensitive files
    location ~ ^/(config|includes|scripts)/ {
        deny all;
    }

    # Static files
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Logs
    access_log /var/log/nginx/faciencia_access.log;
    error_log /var/log/nginx/faciencia_error.log;
}
```

---

## 21. Migração de Dados

### 21.1 Migração de Sistema Legado

#### Script de Migração de Alunos
```php
// scripts/migrate_alunos.php
<?php
require_once '../includes/init.php';

class MigradorAlunos {
    private $db_origem;
    private $db_destino;

    public function __construct() {
        // Conexão com banco legado
        $this->db_origem = new PDO(
            "mysql:host=localhost;dbname=sistema_antigo",
            "user_antigo",
            "senha_antiga"
        );

        // Conexão com novo sistema
        $this->db_destino = Database::getInstance();
    }

    public function migrarAlunos() {
        echo "Iniciando migração de alunos...\n";

        // Buscar alunos do sistema antigo
        $sql_origem = "SELECT * FROM estudantes ORDER BY id";
        $stmt = $this->db_origem->query($sql_origem);

        $total = 0;
        $erros = 0;

        while ($aluno_antigo = $stmt->fetch(PDO::FETCH_ASSOC)) {
            try {
                // Mapear campos do sistema antigo para o novo
                $aluno_novo = $this->mapearCampos($aluno_antigo);

                // Inserir no novo sistema
                $aluno = new Aluno();
                $id_novo = $aluno->create($aluno_novo, $aluno_antigo['id']);

                echo "Migrado: {$aluno_novo['nome']} (ID antigo: {$aluno_antigo['id']}, ID novo: {$id_novo})\n";
                $total++;

            } catch (Exception $e) {
                echo "Erro ao migrar aluno ID {$aluno_antigo['id']}: {$e->getMessage()}\n";
                $erros++;
            }
        }

        echo "\nMigração concluída:\n";
        echo "Total migrados: {$total}\n";
        echo "Erros: {$erros}\n";
    }

    private function mapearCampos($aluno_antigo) {
        return [
            'nome' => $aluno_antigo['nome_completo'],
            'cpf' => $this->formatarCpf($aluno_antigo['documento']),
            'rg' => $aluno_antigo['rg'],
            'data_nascimento' => $aluno_antigo['nascimento'],
            'email' => $aluno_antigo['email'],
            'telefone' => $aluno_antigo['telefone'],
            'endereco' => $aluno_antigo['endereco'],
            'numero' => $aluno_antigo['numero'],
            'bairro' => $aluno_antigo['bairro'],
            'cep' => $aluno_antigo['cep'],
            'curso_id' => $this->mapearCurso($aluno_antigo['curso']),
            'polo_id' => $this->mapearPolo($aluno_antigo['unidade']),
            'data_ingresso' => $aluno_antigo['data_matricula'],
            'status' => $this->mapearStatus($aluno_antigo['situacao']),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
    }

    private function formatarCpf($cpf) {
        return preg_replace('/[^0-9]/', '', $cpf);
    }

    private function mapearCurso($curso_antigo) {
        $mapeamento = [
            'ADM' => 1, // Administração
            'DIR' => 2, // Direito
            'ENG' => 3, // Engenharia
            // ... outros cursos
        ];

        return $mapeamento[$curso_antigo] ?? 1;
    }

    private function mapearPolo($polo_antigo) {
        $mapeamento = [
            'SEDE' => 1,
            'FILIAL_A' => 2,
            'FILIAL_B' => 3,
            // ... outros polos
        ];

        return $mapeamento[$polo_antigo] ?? 1;
    }

    private function mapearStatus($status_antigo) {
        $mapeamento = [
            'ATIVO' => 'ativo',
            'TRANCADO' => 'trancado',
            'FORMADO' => 'formado',
            'CANCELADO' => 'cancelado'
        ];

        return $mapeamento[$status_antigo] ?? 'ativo';
    }
}

// Executar migração
$migrador = new MigradorAlunos();
$migrador->migrarAlunos();
?>
```

### 21.2 Validação Pós-Migração

#### Script de Validação
```php
// scripts/validar_migracao.php
<?php
require_once '../includes/init.php';

echo "=== VALIDAÇÃO PÓS-MIGRAÇÃO ===\n\n";

$db = Database::getInstance();

// 1. Verificar totais
echo "1. Verificando totais:\n";
$total_alunos = $db->fetchOne("SELECT COUNT(*) as total FROM alunos")['total'];
echo "   Total de alunos: {$total_alunos}\n";

$total_cursos = $db->fetchOne("SELECT COUNT(*) as total FROM cursos")['total'];
echo "   Total de cursos: {$total_cursos}\n";

$total_polos = $db->fetchOne("SELECT COUNT(*) as total FROM polos")['total'];
echo "   Total de polos: {$total_polos}\n\n";

// 2. Verificar integridade referencial
echo "2. Verificando integridade referencial:\n";

$alunos_sem_curso = $db->fetchOne("
    SELECT COUNT(*) as total
    FROM alunos a
    LEFT JOIN cursos c ON a.curso_id = c.id
    WHERE c.id IS NULL
")['total'];
echo "   Alunos sem curso válido: {$alunos_sem_curso}\n";

$alunos_sem_polo = $db->fetchOne("
    SELECT COUNT(*) as total
    FROM alunos a
    LEFT JOIN polos p ON a.polo_id = p.id
    WHERE p.id IS NULL
")['total'];
echo "   Alunos sem polo válido: {$alunos_sem_polo}\n\n";

// 3. Verificar dados obrigatórios
echo "3. Verificando dados obrigatórios:\n";

$alunos_sem_nome = $db->fetchOne("
    SELECT COUNT(*) as total
    FROM alunos
    WHERE nome IS NULL OR nome = ''
")['total'];
echo "   Alunos sem nome: {$alunos_sem_nome}\n";

$alunos_sem_cpf = $db->fetchOne("
    SELECT COUNT(*) as total
    FROM alunos
    WHERE cpf IS NULL OR cpf = ''
")['total'];
echo "   Alunos sem CPF: {$alunos_sem_cpf}\n";

// 4. Verificar duplicatas
echo "\n4. Verificando duplicatas:\n";

$cpfs_duplicados = $db->fetchOne("
    SELECT COUNT(*) as total
    FROM (
        SELECT cpf
        FROM alunos
        WHERE cpf IS NOT NULL AND cpf != ''
        GROUP BY cpf
        HAVING COUNT(*) > 1
    ) as duplicatas
")['total'];
echo "   CPFs duplicados: {$cpfs_duplicados}\n";

echo "\n=== VALIDAÇÃO CONCLUÍDA ===\n";
?>
```

---

## 22. Monitoramento em Produção

### 22.1 Métricas e Alertas

#### Script de Monitoramento
```bash
#!/bin/bash
# scripts/monitor_producao.sh

LOG_FILE="/var/log/faciencia_monitor.log"
EMAIL_ADMIN="admin@faciencia.edu.br"

log_message() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" >> $LOG_FILE
}

send_alert() {
    echo "$1" | mail -s "ALERTA - Sistema Faciência" $EMAIL_ADMIN
    log_message "ALERTA: $1"
}

# 1. Verificar espaço em disco
DISK_USAGE=$(df / | awk 'NR==2 {print $5}' | sed 's/%//')
if [ $DISK_USAGE -gt 85 ]; then
    send_alert "Espaço em disco crítico: ${DISK_USAGE}%"
fi

# 2. Verificar conexões MySQL
MYSQL_CONNECTIONS=$(mysql -e "SHOW STATUS LIKE 'Threads_connected';" | awk 'NR==2 {print $2}')
if [ $MYSQL_CONNECTIONS -gt 100 ]; then
    send_alert "Muitas conexões MySQL: $MYSQL_CONNECTIONS"
fi

# 3. Verificar logs de erro
ERROR_COUNT=$(tail -n 1000 /var/log/apache2/error.log | grep -c "$(date '+%Y-%m-%d')")
if [ $ERROR_COUNT -gt 50 ]; then
    send_alert "Muitos erros no Apache hoje: $ERROR_COUNT"
fi

# 4. Verificar se o site está respondendo
HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" https://faciencia.edu.br)
if [ $HTTP_STATUS -ne 200 ]; then
    send_alert "Site não está respondendo. Status: $HTTP_STATUS"
fi

# 5. Verificar backup
BACKUP_TODAY=$(find /backups -name "*$(date '+%Y%m%d')*" | wc -l)
if [ $BACKUP_TODAY -eq 0 ]; then
    send_alert "Backup não foi realizado hoje"
fi

log_message "Monitoramento executado - Disk: ${DISK_USAGE}%, MySQL: $MYSQL_CONNECTIONS, Errors: $ERROR_COUNT, HTTP: $HTTP_STATUS"
```

### 22.2 Dashboard de Monitoramento

#### Página de Status do Sistema
```php
// admin/status.php
<?php
require_once '../includes/init.php';
exigirPermissao('sistema', 'visualizar');

$db = Database::getInstance();

// Métricas do sistema
$metricas = [
    'usuarios_online' => $db->fetchOne("
        SELECT COUNT(*) as total
        FROM usuarios
        WHERE ultimo_acesso > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
    ")['total'],

    'alunos_ativos' => $db->fetchOne("
        SELECT COUNT(*) as total
        FROM alunos
        WHERE status = 'ativo'
    ")['total'],

    'documentos_hoje' => $db->fetchOne("
        SELECT COUNT(*) as total
        FROM documentos_emitidos
        WHERE DATE(created_at) = CURDATE()
    ")['total'],

    'chamados_abertos' => $db->fetchOne("
        SELECT COUNT(*) as total
        FROM chamados
        WHERE status IN ('aberto', 'em_andamento')
    ")['total']
];

// Status dos serviços
$servicos = [
    'banco_dados' => verificarBancoDados(),
    'espaco_disco' => verificarEspacoDisco(),
    'backup' => verificarBackup(),
    'ssl' => verificarSSL()
];

function verificarBancoDados() {
    try {
        $db = Database::getInstance();
        $db->fetchOne("SELECT 1");
        return ['status' => 'ok', 'message' => 'Conectado'];
    } catch (Exception $e) {
        return ['status' => 'erro', 'message' => $e->getMessage()];
    }
}

function verificarEspacoDisco() {
    $total = disk_total_space('/');
    $livre = disk_free_space('/');
    $usado = (($total - $livre) / $total) * 100;

    $status = $usado > 90 ? 'erro' : ($usado > 80 ? 'alerta' : 'ok');

    return [
        'status' => $status,
        'message' => number_format($usado, 1) . '% usado'
    ];
}

function verificarBackup() {
    $backup_hoje = glob('/backups/*' . date('Ymd') . '*');

    return [
        'status' => count($backup_hoje) > 0 ? 'ok' : 'erro',
        'message' => count($backup_hoje) . ' backups hoje'
    ];
}

function verificarSSL() {
    $context = stream_context_create([
        "ssl" => [
            "capture_peer_cert" => true,
        ],
    ]);

    $socket = @stream_socket_client(
        "ssl://faciencia.edu.br:443",
        $errno,
        $errstr,
        30,
        STREAM_CLIENT_CONNECT,
        $context
    );

    if ($socket) {
        $cert = stream_context_get_params($socket)["options"]["ssl"]["peer_certificate"];
        $cert_data = openssl_x509_parse($cert);
        $expiry = $cert_data['validTo_time_t'];
        $days_left = ($expiry - time()) / (60 * 60 * 24);

        $status = $days_left < 30 ? 'alerta' : 'ok';
        $message = number_format($days_left) . ' dias restantes';

        fclose($socket);

        return ['status' => $status, 'message' => $message];
    }

    return ['status' => 'erro', 'message' => 'Não foi possível verificar'];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status do Sistema - Faciência ERP</title>
    <link rel="stylesheet" href="../css/styles.css">
    <meta http-equiv="refresh" content="60"> <!-- Auto-refresh a cada minuto -->
</head>
<body>
    <div class="container mx-auto p-6">
        <h1 class="text-3xl font-bold mb-6">Status do Sistema</h1>

        <!-- Métricas -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-semibold">Usuários Online</h3>
                <p class="text-3xl font-bold text-blue-600"><?= $metricas['usuarios_online'] ?></p>
            </div>

            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-semibold">Alunos Ativos</h3>
                <p class="text-3xl font-bold text-green-600"><?= number_format($metricas['alunos_ativos']) ?></p>
            </div>

            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-semibold">Documentos Hoje</h3>
                <p class="text-3xl font-bold text-purple-600"><?= $metricas['documentos_hoje'] ?></p>
            </div>

            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-semibold">Chamados Abertos</h3>
                <p class="text-3xl font-bold text-orange-600"><?= $metricas['chamados_abertos'] ?></p>
            </div>
        </div>

        <!-- Status dos Serviços -->
        <div class="bg-white p-6 rounded-lg shadow">
            <h2 class="text-2xl font-bold mb-4">Status dos Serviços</h2>

            <div class="space-y-4">
                <?php foreach ($servicos as $nome => $info): ?>
                <div class="flex items-center justify-between p-4 border rounded">
                    <span class="font-medium"><?= ucfirst(str_replace('_', ' ', $nome)) ?></span>
                    <div class="flex items-center">
                        <span class="mr-2"><?= $info['message'] ?></span>
                        <span class="px-3 py-1 rounded-full text-sm font-medium
                            <?= $info['status'] === 'ok' ? 'bg-green-100 text-green-800' :
                                ($info['status'] === 'alerta' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') ?>">
                            <?= ucfirst($info['status']) ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="mt-6 text-center text-gray-500">
            Última atualização: <?= date('d/m/Y H:i:s') ?>
        </div>
    </div>
</body>
</html>
```

---

## 23. Considerações Finais

### 23.1 Roadmap de Melhorias

#### Curto Prazo (1-3 meses)
- [ ] Implementar sistema de cache Redis
- [ ] Adicionar autenticação de dois fatores (2FA)
- [ ] Melhorar interface mobile
- [ ] Implementar API REST completa
- [ ] Adicionar testes automatizados

#### Médio Prazo (3-6 meses)
- [ ] Migrar para PHP 8.x
- [ ] Implementar microserviços
- [ ] Adicionar sistema de notificações push
- [ ] Integrar com sistemas de videoconferência
- [ ] Implementar analytics avançado

#### Longo Prazo (6-12 meses)
- [ ] Migrar para framework moderno (Laravel/Symfony)
- [ ] Implementar arquitetura de containers (Docker)
- [ ] Adicionar inteligência artificial para relatórios
- [ ] Implementar sistema de workflow avançado
- [ ] Criar aplicativo mobile nativo

### 23.2 Boas Práticas para Manutenção

#### Desenvolvimento
1. **Sempre fazer backup antes de alterações**
2. **Testar em ambiente de desenvolvimento primeiro**
3. **Documentar todas as mudanças**
4. **Seguir padrões de código estabelecidos**
5. **Implementar logs detalhados**

#### Segurança
1. **Manter sistema sempre atualizado**
2. **Monitorar logs de segurança regularmente**
3. **Realizar auditorias de segurança periódicas**
4. **Implementar políticas de senha forte**
5. **Treinar usuários sobre segurança**

#### Performance
1. **Monitorar métricas de performance**
2. **Otimizar consultas de banco de dados**
3. **Implementar cache quando necessário**
4. **Monitorar uso de recursos do servidor**
5. **Realizar testes de carga periodicamente**

### 23.3 Contatos e Suporte

#### Informações Técnicas
- **Sistema**: Faciência ERP v1.0
- **Desenvolvido em**: 2025
- **Tecnologia Principal**: PHP 7.4+ / MySQL 8.0+
- **Arquitetura**: MVC com padrão Singleton
- **Licença**: Proprietária

#### Para Emergências
1. **Verificar logs**: `/var/log/apache2/error.log`
2. **Verificar status**: `admin/status.php`
3. **Executar diagnóstico**: `scripts/diagnostico.php`
4. **Restaurar backup**: `scripts/restore_backup.sh`

#### Documentação Adicional
- **Manual do Usuário**: `docs/manual_usuario.pdf`
- **Guia de Instalação**: `docs/instalacao.md`
- **API Documentation**: `docs/api.md`
- **Changelog**: `CHANGELOG.md`

---

## 24. Anexos

### 24.1 Comandos Úteis

#### MySQL
```sql
-- Verificar tamanho das tabelas
SELECT
    table_name AS 'Tabela',
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Tamanho (MB)'
FROM information_schema.TABLES
WHERE table_schema = 'u682219090_faciencia_erp'
ORDER BY (data_length + index_length) DESC;

-- Verificar conexões ativas
SHOW PROCESSLIST;

-- Otimizar todas as tabelas
OPTIMIZE TABLE alunos, cursos, matriculas, documentos_emitidos;

-- Verificar status do MySQL
SHOW STATUS LIKE 'Threads_connected';
SHOW STATUS LIKE 'Queries';
```

#### Sistema
```bash
# Verificar espaço em disco
df -h

# Verificar uso de memória
free -h

# Verificar processos PHP
ps aux | grep php

# Verificar logs em tempo real
tail -f /var/log/apache2/error.log

# Verificar conexões de rede
netstat -tulpn | grep :80
netstat -tulpn | grep :443

# Limpar cache do sistema
sync && echo 3 > /proc/sys/vm/drop_caches
```

### 24.2 Estrutura de Banco Resumida

#### Tabelas Principais
```
usuarios (id, nome, email, tipo, status)
├── permissoes (usuario_id, modulo, nivel_acesso)
└── logs_sistema (usuario_id, modulo, acao, descricao)

alunos (id, nome, cpf, email, curso_id, polo_id, status)
├── matriculas (id, aluno_id, curso_id, data_matricula, status)
├── solicitacoes_documentos (id, aluno_id, tipo_documento_id, status)
└── notas (id, aluno_id, disciplina_id, nota, periodo)

cursos (id, nome, carga_horaria, modalidade, nivel)
├── disciplinas (id, curso_id, nome, carga_horaria)
└── turmas (id, curso_id, nome, data_inicio, data_fim)

polos (id, nome, responsavel_id, limite_documentos)
└── polos_configuracoes (polo_id, chave, valor)

chamados (id, titulo, categoria_id, status, solicitante_id)
├── categorias_chamados (id, nome, tipo, departamento_responsavel)
└── respostas_chamados (id, chamado_id, usuario_id, resposta)
```

---

**Esta documentação foi criada para garantir a continuidade e manutenção do Sistema Faciência ERP. Mantenha-a sempre atualizada conforme o sistema evolui.**

**Última atualização**:  10/06/2025
**Versão da documentação**: 1.0
**Sistema**: Faciência ERP v1.0
