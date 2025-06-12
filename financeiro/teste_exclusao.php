<?php
/**
 * Teste rápido para verificar funcionamento da exclusão de boletos
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/Auth.php';

header('Content-Type: application/json');

try {
    // Testa a conexão com o banco
    $db = Database::getInstance();
    
    $resultado = [
        'status' => 'sucesso',
        'testes' => []
    ];
    
    // Teste 1: Conexão com banco
    try {
        $teste_conexao = $db->fetchOne("SELECT 1 as teste");
        $resultado['testes'][] = [
            'nome' => 'Conexão Database',
            'status' => 'OK',
            'mensagem' => 'Conexão estabelecida com sucesso'
        ];
    } catch (Exception $e) {
        $resultado['testes'][] = [
            'nome' => 'Conexão Database',
            'status' => 'ERRO',
            'mensagem' => $e->getMessage()
        ];
    }
    
    // Teste 2: Verificar tabela boletos
    try {
        $count_boletos = $db->fetchOne("SELECT COUNT(*) as total FROM boletos");
        $resultado['testes'][] = [
            'nome' => 'Tabela Boletos',
            'status' => 'OK',
            'mensagem' => 'Tabela existe com ' . $count_boletos['total'] . ' registros'
        ];
    } catch (Exception $e) {
        $resultado['testes'][] = [
            'nome' => 'Tabela Boletos',
            'status' => 'ERRO',
            'mensagem' => $e->getMessage()
        ];
    }
    
    // Teste 3: Verificar métodos da classe Database
    $metodos_necessarios = ['fetchOne', 'fetchAll', 'insert', 'update', 'delete', 'beginTransaction', 'commit', 'rollback'];
    foreach ($metodos_necessarios as $metodo) {
        if (method_exists($db, $metodo)) {
            $resultado['testes'][] = [
                'nome' => "Método $metodo",
                'status' => 'OK',
                'mensagem' => "Método $metodo existe"
            ];
        } else {
            $resultado['testes'][] = [
                'nome' => "Método $metodo",
                'status' => 'ERRO',
                'mensagem' => "Método $metodo não encontrado"
            ];
        }
    }
    
    // Teste 4: Verificar se a classe Auth funciona
    try {
        // Não vamos fazer login real, apenas verificar se a classe existe
        if (class_exists('Auth')) {
            $resultado['testes'][] = [
                'nome' => 'Classe Auth',
                'status' => 'OK',
                'mensagem' => 'Classe Auth carregada com sucesso'
            ];
        } else {
            $resultado['testes'][] = [
                'nome' => 'Classe Auth',
                'status' => 'ERRO',
                'mensagem' => 'Classe Auth não encontrada'
            ];
        }
    } catch (Exception $e) {
        $resultado['testes'][] = [
            'nome' => 'Classe Auth',
            'status' => 'ERRO',
            'mensagem' => $e->getMessage()
        ];
    }
    
    // Teste 5: Verificar arquivos necessários
    $arquivos = [
        __DIR__ . '/boletos.php' => 'Página principal',
        __DIR__ . '/ajax/excluir_boleto.php' => 'AJAX exclusão',
        __DIR__ . '/js/boletos.js' => 'JavaScript',
        __DIR__ . '/includes/boleto_functions.php' => 'Funções'
    ];
    
    foreach ($arquivos as $arquivo => $nome) {
        if (file_exists($arquivo)) {
            $resultado['testes'][] = [
                'nome' => $nome,
                'status' => 'OK',
                'mensagem' => "$nome existe"
            ];
        } else {
            $resultado['testes'][] = [
                'nome' => $nome,
                'status' => 'AVISO',
                'mensagem' => "$nome não encontrado"
            ];
        }
    }
    
    echo json_encode($resultado, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'erro',
        'mensagem' => 'Erro geral: ' . $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
