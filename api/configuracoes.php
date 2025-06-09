<?php
/**
 * API para manipulação de configurações do sistema
 */

// Inicializa o sistema
require_once __DIR__ . '/../includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica se o usuário tem permissão para acessar o módulo de configurações
exigirPermissao('configuracoes');

// Instancia o banco de dados
$db = Database::getInstance();

// Verifica o tipo de requisição
$action = $_GET['action'] ?? 'list';

// Processa a requisição de acordo com a ação
switch ($action) {
    case 'list':
        // Obtém as configurações do sistema
        $sql = "SELECT * FROM configuracoes_sistema ORDER BY grupo, chave";
        $configuracoes = $db->fetchAll($sql);
        
        // Formata os dados para a resposta
        $response = [
            'success' => true,
            'data' => $configuracoes
        ];
        
        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;
        
    case 'get':
        // Verifica se a chave foi informada
        if (!isset($_GET['chave'])) {
            $response = [
                'success' => false,
                'message' => 'Chave não informada'
            ];
        } else {
            // Obtém a configuração pela chave
            $sql = "SELECT * FROM configuracoes_sistema WHERE chave = ?";
            $configuracao = $db->fetchOne($sql, [$_GET['chave']]);
            
            if ($configuracao) {
                $response = [
                    'success' => true,
                    'data' => $configuracao
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Configuração não encontrada'
                ];
            }
        }
        
        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;
        
    case 'update':
        // Verifica se o usuário tem permissão para editar configurações
        exigirPermissao('configuracoes', 'editar');
        
        // Verifica se é uma requisição POST
        if (!isPost()) {
            $response = [
                'success' => false,
                'message' => 'Método não permitido'
            ];
        } else {
            // Verifica se a chave foi informada
            if (!isset($_POST['chave'])) {
                $response = [
                    'success' => false,
                    'message' => 'Chave não informada'
                ];
            } else {
                // Obtém os dados do formulário
                $chave = $_POST['chave'];
                $valor = $_POST['valor'] ?? '';
                $tipo = $_POST['tipo'] ?? 'string';
                $descricao = $_POST['descricao'] ?? null;
                $grupo = $_POST['grupo'] ?? null;
                
                // Valida os dados
                $errors = [];
                
                if (empty($chave)) {
                    $errors[] = 'A chave é obrigatória';
                }
                
                // Se houver erros, retorna a resposta com os erros
                if (!empty($errors)) {
                    $response = [
                        'success' => false,
                        'message' => 'Erros de validação',
                        'errors' => $errors
                    ];
                } else {
                    // Verifica se a configuração já existe
                    $configuracao = $db->fetchOne("SELECT * FROM configuracoes_sistema WHERE chave = ?", [$chave]);
                    
                    if ($configuracao) {
                        // Atualiza a configuração
                        $result = $db->update('configuracoes_sistema', [
                            'valor' => $valor,
                            'tipo' => $tipo,
                            'descricao' => $descricao,
                            'grupo' => $grupo,
                            'updated_at' => date('Y-m-d H:i:s')
                        ], 'chave = ?', [$chave]);
                        
                        // Registra o log
                        registrarLog(
                            'configuracoes',
                            'editar',
                            'Atualização de configuração: ' . $chave,
                            null,
                            'configuracao',
                            $configuracao,
                            [
                                'chave' => $chave,
                                'valor' => $valor,
                                'tipo' => $tipo,
                                'descricao' => $descricao,
                                'grupo' => $grupo
                            ]
                        );
                        
                        $response = [
                            'success' => true,
                            'message' => 'Configuração atualizada com sucesso'
                        ];
                    } else {
                        // Cria a configuração
                        $result = $db->insert('configuracoes_sistema', [
                            'chave' => $chave,
                            'valor' => $valor,
                            'tipo' => $tipo,
                            'descricao' => $descricao,
                            'grupo' => $grupo,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
                        
                        // Registra o log
                        registrarLog(
                            'configuracoes',
                            'criar',
                            'Criação de configuração: ' . $chave,
                            null,
                            'configuracao',
                            null,
                            [
                                'chave' => $chave,
                                'valor' => $valor,
                                'tipo' => $tipo,
                                'descricao' => $descricao,
                                'grupo' => $grupo
                            ]
                        );
                        
                        $response = [
                            'success' => true,
                            'message' => 'Configuração criada com sucesso'
                        ];
                    }
                }
            }
        }
        
        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;
        
    case 'delete':
        // Verifica se o usuário tem permissão para excluir configurações
        exigirPermissao('configuracoes', 'excluir');
        
        // Verifica se a chave foi informada
        if (!isset($_GET['chave'])) {
            $response = [
                'success' => false,
                'message' => 'Chave não informada'
            ];
        } else {
            // Obtém a chave
            $chave = $_GET['chave'];
            
            // Verifica se a configuração existe
            $configuracao = $db->fetchOne("SELECT * FROM configuracoes_sistema WHERE chave = ?", [$chave]);
            
            if (!$configuracao) {
                $response = [
                    'success' => false,
                    'message' => 'Configuração não encontrada'
                ];
            } else {
                // Exclui a configuração
                $result = $db->delete('configuracoes_sistema', 'chave = ?', [$chave]);
                
                if ($result) {
                    // Registra o log
                    registrarLog(
                        'configuracoes',
                        'excluir',
                        'Exclusão de configuração: ' . $chave,
                        null,
                        'configuracao',
                        $configuracao,
                        null
                    );
                    
                    $response = [
                        'success' => true,
                        'message' => 'Configuração excluída com sucesso'
                    ];
                } else {
                    $response = [
                        'success' => false,
                        'message' => 'Erro ao excluir configuração'
                    ];
                }
            }
        }
        
        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;
        
    case 'grupos':
        // Obtém os grupos de configurações
        $sql = "SELECT DISTINCT grupo FROM configuracoes_sistema WHERE grupo IS NOT NULL ORDER BY grupo";
        $grupos = $db->fetchAll($sql);
        
        // Formata os dados para a resposta
        $response = [
            'success' => true,
            'data' => array_column($grupos, 'grupo')
        ];
        
        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;
        
    case 'por_grupo':
        // Verifica se o grupo foi informado
        if (!isset($_GET['grupo'])) {
            $response = [
                'success' => false,
                'message' => 'Grupo não informado'
            ];
        } else {
            // Obtém as configurações do grupo
            $sql = "SELECT * FROM configuracoes_sistema WHERE grupo = ? ORDER BY chave";
            $configuracoes = $db->fetchAll($sql, [$_GET['grupo']]);
            
            // Formata os dados para a resposta
            $response = [
                'success' => true,
                'data' => $configuracoes
            ];
        }
        
        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;
        
    default:
        // Ação desconhecida
        $response = [
            'success' => false,
            'message' => 'Ação desconhecida'
        ];
        
        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;
}
