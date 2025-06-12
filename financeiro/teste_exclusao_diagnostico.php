<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/Auth.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    
    $testes = [];
    
    // Teste 1: Verificar estrutura da tabela boletos
    try {
        $colunas = $db->fetchAll("DESCRIBE boletos");
        $nomes_colunas = array_column($colunas, 'Field');
        
        $testes[] = [
            'teste' => 'Estrutura da tabela boletos',
            'status' => 'OK',
            'detalhes' => 'Colunas encontradas: ' . implode(', ', $nomes_colunas)
        ];
        
        // Verifica se a coluna arquivo_pdf existe
        if (in_array('arquivo_pdf', $nomes_colunas)) {
            $testes[] = [
                'teste' => 'Coluna arquivo_pdf',
                'status' => 'INFO',
                'detalhes' => 'Coluna arquivo_pdf existe na tabela'
            ];
        } else {
            $testes[] = [
                'teste' => 'Coluna arquivo_pdf',
                'status' => 'AVISO',
                'detalhes' => 'Coluna arquivo_pdf NÃO existe - usando padrão de nomenclatura'
            ];
        }
        
    } catch (Exception $e) {
        $testes[] = [
            'teste' => 'Estrutura da tabela boletos',
            'status' => 'ERRO',
            'detalhes' => $e->getMessage()
        ];
    }
    
    // Teste 2: Contar boletos existentes
    try {
        $count = $db->fetchOne("SELECT COUNT(*) as total FROM boletos");
        $testes[] = [
            'teste' => 'Contagem de boletos',
            'status' => 'OK',
            'detalhes' => 'Total de boletos: ' . $count['total']
        ];
    } catch (Exception $e) {
        $testes[] = [
            'teste' => 'Contagem de boletos',
            'status' => 'ERRO',
            'detalhes' => $e->getMessage()
        ];
    }
    
    // Teste 3: Verificar método delete da classe Database
    try {
        if (method_exists($db, 'delete')) {
            $testes[] = [
                'teste' => 'Método Database::delete()',
                'status' => 'OK',
                'detalhes' => 'Método delete() está disponível'
            ];
        } else {
            $testes[] = [
                'teste' => 'Método Database::delete()',
                'status' => 'ERRO',
                'detalhes' => 'Método delete() não encontrado'
            ];
        }
    } catch (Exception $e) {
        $testes[] = [
            'teste' => 'Método Database::delete()',
            'status' => 'ERRO',
            'detalhes' => $e->getMessage()
        ];
    }
    
    // Teste 4: Verificar diretório de uploads
    $upload_dir = __DIR__ . '/../uploads/boletos';
    if (is_dir($upload_dir)) {
        $arquivos = glob($upload_dir . '/*.{pdf,html}', GLOB_BRACE);
        $testes[] = [
            'teste' => 'Diretório de uploads',
            'status' => 'OK',
            'detalhes' => 'Diretório existe com ' . count($arquivos) . ' arquivos'
        ];
    } else {
        $testes[] = [
            'teste' => 'Diretório de uploads',
            'status' => 'AVISO',
            'detalhes' => 'Diretório não existe: ' . $upload_dir
        ];
    }
    
    // Teste 5: Simular validação de boleto (sem excluir)
    try {
        $boleto_teste = $db->fetchOne("SELECT id FROM boletos LIMIT 1");
        if ($boleto_teste) {
            $testes[] = [
                'teste' => 'Simulação de busca de boleto',
                'status' => 'OK',
                'detalhes' => 'Boleto encontrado para teste: ID ' . $boleto_teste['id']
            ];
        } else {
            $testes[] = [
                'teste' => 'Simulação de busca de boleto',
                'status' => 'AVISO',
                'detalhes' => 'Nenhum boleto encontrado para teste'
            ];
        }
    } catch (Exception $e) {
        $testes[] = [
            'teste' => 'Simulação de busca de boleto',
            'status' => 'ERRO',
            'detalhes' => $e->getMessage()
        ];
    }
    
    echo json_encode([
        'status' => 'sucesso',
        'timestamp' => date('Y-m-d H:i:s'),
        'testes' => $testes
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'erro',
        'timestamp' => date('Y-m-d H:i:s'),
        'mensagem' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
