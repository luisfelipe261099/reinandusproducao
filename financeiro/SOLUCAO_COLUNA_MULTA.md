# SOLUÇÃO PARA ERRO "Column 'multa' not found"

## 🚨 **PROBLEMA**
```
Erro ao gerar boleto bancário: SQLSTATE[42S22]: Column not found: 1054 Unknown column 'multa' in 'INSERT INTO'
```

## ✅ **DUAS SOLUÇÕES DISPONÍVEIS**

### **SOLUÇÃO 1: Executar Script SQL (RECOMENDADO)**

1. **Acesse seu phpMyAdmin ou cliente MySQL**

2. **Execute este script SQL:**
```sql
-- Adicione as colunas uma por uma (ignore erros se já existirem)
ALTER TABLE boletos ADD COLUMN multa decimal(5,2) DEFAULT 2.00;
ALTER TABLE boletos ADD COLUMN juros decimal(5,2) DEFAULT 1.00;
ALTER TABLE boletos ADD COLUMN desconto decimal(10,2) DEFAULT 0.00;
ALTER TABLE boletos ADD COLUMN ambiente enum('teste','producao') DEFAULT 'teste';
ALTER TABLE boletos ADD COLUMN banco varchar(50) DEFAULT 'itau';
ALTER TABLE boletos ADD COLUMN carteira varchar(10) DEFAULT '109';
ALTER TABLE boletos ADD COLUMN instrucoes text DEFAULT NULL;
ALTER TABLE boletos ADD COLUMN valor_pago decimal(10,2) DEFAULT NULL;
ALTER TABLE boletos ADD COLUMN forma_pagamento varchar(50) DEFAULT NULL;
ALTER TABLE boletos ADD COLUMN tipo enum('mensalidade','polo','avulso','funcionario') DEFAULT 'avulso';
ALTER TABLE boletos ADD COLUMN referencia_id int(11) DEFAULT NULL;
```

3. **Script completo disponível em:**
   - `sql/adicionar_colunas_boletos_simples.sql`

### **SOLUÇÃO 2: Usar Versão Compatível (SEM MIGRAÇÃO)**

Se não conseguir executar o SQL, o sistema já foi atualizado para funcionar:

1. **Arquivo atualizado:** `financeiro/boletos.php`
2. **Nova função:** `includes/boleto_functions_compativel.php`
3. **Detecção automática** de colunas disponíveis
4. **Funciona com qualquer estrutura** de tabela

## 🧪 **TESTES DISPONÍVEIS**

### **Teste de Compatibilidade:**
```
http://seudominio.com/financeiro/teste_compatibilidade_detalhado.php
```

### **Verificar Estrutura da Tabela:**
```
http://seudominio.com/financeiro/verificar_estrutura_boletos.php
```

## 🎯 **COMO VERIFICAR SE FUNCIONOU**

1. **Execute um dos testes acima**
2. **Tente gerar um boleto em:**
   ```
   http://seudominio.com/financeiro/boletos.php
   ```
3. **Verifique os logs se houver erro**

## 📋 **STATUS ATUAL**

- ✅ **Código compatível implementado**
- ✅ **Script SQL criado** 
- ✅ **Detecção automática de estrutura**
- ✅ **Funciona com estrutura antiga e nova**
- ✅ **Testes de diagnóstico disponíveis**

## 🔧 **RECOMENDAÇÃO**

1. **Execute o script SQL** para adicionar as colunas
2. **Se der erro de permissão**, use a versão compatível
3. **Teste a geração de boletos**
4. **Monitore os logs** para verificar funcionamento

**A geração de boletos deve funcionar independente da estrutura da tabela!**
