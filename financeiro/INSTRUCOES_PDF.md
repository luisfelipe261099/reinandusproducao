# 📋 INSTRUÇÕES PARA INSTALAÇÃO DO GERADOR DE PDF

## 🎯 Objetivo
Estas instruções vão te ajudar a instalar o gerador de PDFs para boletos bancários no módulo financeiro.

## 📦 Método 1: Instalação via Composer (Recomendado)

### Passo 1: Verificar se o Composer está instalado
Abra o terminal/prompt de comando e digite:
```bash
composer --version
```

Se não estiver instalado, baixe de: https://getcomposer.org/

### Passo 2: Navegar até o diretório do projeto
```bash
cd c:\Users\desen\OneDrive\Documentos\GitHub\reinandusproducao
```

### Passo 3: Instalar dependências
```bash
composer require dompdf/dompdf
```

### Passo 4: Verificar instalação
Acesse: `http://seudominio.com/financeiro/instalar_dependencias.php`

## 🔧 Método 2: Sem Composer (Alternativo)

Se não conseguir instalar o Composer, o sistema funcionará gerando arquivos HTML otimizados para impressão ao invés de PDFs.

### Características do modo HTML:
- ✅ Funciona sem dependências externas
- ✅ Pode ser impresso como PDF pelo navegador
- ✅ Layout idêntico ao PDF
- ⚠️ Arquivo fica em formato HTML

## 🧪 Teste da Funcionalidade

### 1. Gerar um boleto de teste:
- Acesse: `financeiro/boletos.php?action=novo`
- Preencha os dados e gere um boleto

### 2. Verificar se o PDF/HTML foi gerado:
- Na listagem de boletos, clique no ícone de PDF (vermelho)
- Deve abrir uma nova aba com o boleto

### 3. Baixar o arquivo:
- Clique no ícone de download (laranja)
- O arquivo deve ser baixado automaticamente

## 📁 Estrutura de Arquivos

### Arquivos criados:
- `financeiro/includes/boleto_pdf.php` - Classe para gerar PDFs
- `financeiro/boleto_pdf.php` - Script para visualizar/baixar
- `uploads/boletos/` - Diretório para armazenar arquivos
- `uploads/boletos/.htaccess` - Proteção do diretório

### Como funciona:
1. Quando um boleto é gerado via API do Itaú
2. O sistema automaticamente cria o PDF/HTML
3. O arquivo fica disponível para visualização e download
4. Links são exibidos na tabela de boletos

## 🎨 Funcionalidades Implementadas

### ✅ Paginação
- 20 boletos por página
- Navegação com números de página
- Contador de registros

### ✅ Geração de PDF/HTML
- PDF via DomPDF (se disponível)
- HTML otimizado para impressão (fallback)
- Layout profissional do boleto

### ✅ Visualização e Download
- Visualizar no navegador
- Baixar arquivo
- Proteção de diretório

### ✅ Integração com API do Itaú
- Geração automática após criar boleto
- Preserva dados da API oficial
- URL do Itaú + PDF local

## 🔍 Resolução de Problemas

### Erro: "Biblioteca DomPDF não encontrada"
**Solução:** Execute `composer require dompdf/dompdf`

### Erro: "Permission denied" ao salvar PDF
**Solução:** 
1. Verifique permissões da pasta `uploads/boletos/`
2. Execute: `chmod 755 uploads/boletos/` (Linux/Mac)

### PDF não abre ou está corrompido
**Solução:**
1. Acesse `financeiro/instalar_dependencias.php`
2. Execute o teste de geração
3. Verifique se todas as dependências estão OK

### Boleto aparece sem botões de PDF
**Causa:** Boleto não tem linha digitável nem código de barras
**Solução:** Regere o boleto via API do Itaú

## 📞 Suporte

Se ainda tiver problemas:
1. Execute: `financeiro/teste_compatibilidade.php`
2. Execute: `financeiro/instalar_dependencias.php`
3. Verifique os logs de erro do PHP
4. Entre em contato com o suporte técnico

---
*Última atualização: 11/06/2025*
