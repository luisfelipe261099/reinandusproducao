<?php
// Inclui o arquivo de configuração
require_once 'config.php';

// Verifica se os tipos de documentos 'Histórico' e 'Declaração' existem
$db = Database::getInstance();

$sql = "SELECT id, nome FROM tipos_documentos WHERE nome IN ('Histórico', 'Declaração')";
$tipos = $db->fetchAll($sql);

if (empty($tipos)) {
    // Cria os tipos de documentos
    $db->insert('tipos_documentos', [
        'nome' => 'Histórico',
        'descricao' => 'Histórico Escolar do Aluno',
        'status' => 'ativo',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ]);
    
    $db->insert('tipos_documentos', [
        'nome' => 'Declaração',
        'descricao' => 'Declaração de Matrícula',
        'status' => 'ativo',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ]);
    
    echo "Tipos de documentos 'Histórico' e 'Declaração' criados com sucesso.";
} else {
    echo "Tipos de documentos encontrados:<br>";
    foreach ($tipos as $tipo) {
        echo "- {$tipo['nome']} (ID: {$tipo['id']})<br>";
    }
}
