<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/includes/boleto_functions_compativel.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    
    $teste = [
        'status' => 'sucesso',
        'timestamp' => date('Y-m-d H:i:s'),
        'testes' => []
    ];
    
    // 1. Verificar estrutura da tabela boletos
    try {
        $colunas = $db->fetchAll("DESCRIBE boletos");
        $nomes_colunas = array_column($colunas, 'Field');
        
        $teste['testes'][] = [
            'nome' => 'Estrutura da tabela boletos',
            'status' => 'OK',
            'detalhes' => 'Encontradas ' . count($colunas) . ' colunas: ' . implode(', ', $nomes_colunas)
        ];
        
        // Verificar colunas específicas
        $colunas_necessarias = ['multa', 'juros', 'desconto', 'ambiente', 'banco', 'carteira'];
        $colunas_encontradas = [];
        $colunas_faltando = [];
        
        foreach ($colunas_necessarias as $coluna) {
            if (in_array($coluna, $nomes_colunas)) {
                $colunas_encontradas[] = $coluna;
            } else {
                $colunas_faltando[] = $coluna;
            }
        }
        
        if (!empty($colunas_encontradas)) {
            $teste['testes'][] = [
                'nome' => 'Colunas novas encontradas',
                'status' => 'OK',
                'detalhes' => 'Encontradas: ' . implode(', ', $colunas_encontradas)
            ];
        }
        
        if (!empty($colunas_faltando)) {
            $teste['testes'][] = [
                'nome' => 'Colunas faltando',
                'status' => 'AVISO',
                'detalhes' => 'Faltando: ' . implode(', ', $colunas_faltando) . ' (função compatível vai funcionar mesmo assim)'
            ];
        }
        
    } catch (Exception $e) {
        $teste['testes'][] = [
            'nome' => 'Estrutura da tabela boletos',
            'status' => 'ERRO',
            'detalhes' => $e->getMessage()
        ];
    }
    
    // 2. Testar função de compatibilidade
    try {
        if (function_exists('salvarBoletoCompativel')) {
            $teste['testes'][] = [
                'nome' => 'Função salvarBoletoCompativel',
                'status' => 'OK',
                'detalhes' => 'Função carregada e disponível'
            ];
        } else {
            $teste['testes'][] = [
                'nome' => 'Função salvarBoletoCompativel',
                'status' => 'ERRO',
                'detalhes' => 'Função não encontrada'
            ];
        }
    } catch (Exception $e) {
        $teste['testes'][] = [
            'nome' => 'Função salvarBoletoCompativel',
            'status' => 'ERRO',
            'detalhes' => $e->getMessage()
        ];
    }
    
    // 3. Simular criação de boleto (sem salvar)
    try {
        $dados_teste = [
            'tipo' => 'mensalidade',
            'referencia_id' => 1,
            'valor' => 100.00,
            'data_vencimento' => date('Y-m-d', strtotime('+30 days')),
            'descricao' => 'Teste de compatibilidade',
            'nome_pagador' => 'Teste Silva',
            'cpf_pagador' => '123.456.789-00',
            'endereco' => 'Rua Teste, 123',
            'bairro' => 'Centro',
            'cidade' => 'Teste City',
            'uf' => 'SP',
            'cep' => '12345-678'
        ];
        
        // Simula a montagem dos dados (sem inserir)
        $colunas_existentes = [];
        $resultado = $db->query("DESCRIBE boletos");
        while ($row = $resultado->fetch()) {
            $colunas_existentes[] = $row['Field'];
        }
        
        $campos_mapeados = 0;
        $mapeamento_campos = [
            'tipo' => 'tipo_entidade',
            'referencia_id' => 'entidade_id',
            'valor' => 'valor',
            'data_vencimento' => 'data_vencimento',
            'descricao' => 'descricao',
            'nome_pagador' => 'nome_pagador',
            'multa' => null,
            'juros' => null,
            'ambiente' => null
        ];
        
        foreach ($mapeamento_campos as $campo_novo => $campo_antigo) {
            if (in_array($campo_novo, $colunas_existentes) || 
                ($campo_antigo && in_array($campo_antigo, $colunas_existentes))) {
                $campos_mapeados++;
            }
        }
        
        $teste['testes'][] = [
            'nome' => 'Simulação de mapeamento de campos',
            'status' => 'OK',
            'detalhes' => "Conseguiu mapear $campos_mapeados campos dos dados de teste"
        ];
        
    } catch (Exception $e) {
        $teste['testes'][] = [
            'nome' => 'Simulação de mapeamento de campos',
            'status' => 'ERRO',
            'detalhes' => $e->getMessage()
        ];
    }
    
    // 4. Verificar método de inserção do Database
    try {
        if (method_exists($db, 'insert')) {
            $teste['testes'][] = [
                'nome' => 'Método Database::insert()',
                'status' => 'OK',
                'detalhes' => 'Método insert() disponível'
            ];
        } else {
            $teste['testes'][] = [
                'nome' => 'Método Database::insert()',
                'status' => 'ERRO',
                'detalhes' => 'Método insert() não encontrado'
            ];
        }
    } catch (Exception $e) {
        $teste['testes'][] = [
            'nome' => 'Método Database::insert()',
            'status' => 'ERRO',
            'detalhes' => $e->getMessage()
        ];
    }
    
    // 5. Script SQL para migração
    $script_sql = __DIR__ . '/../sql/adicionar_colunas_boletos_simples.sql';
    if (file_exists($script_sql)) {
        $teste['testes'][] = [
            'nome' => 'Script de migração SQL',
            'status' => 'OK',
            'detalhes' => 'Script disponível em: ' . basename($script_sql)
        ];
    } else {
        $teste['testes'][] = [
            'nome' => 'Script de migração SQL',
            'status' => 'AVISO',
            'detalhes' => 'Script não encontrado'
        ];
    }
    
    echo json_encode($teste, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'erro',
        'timestamp' => date('Y-m-d H:i:s'),
        'mensagem' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
