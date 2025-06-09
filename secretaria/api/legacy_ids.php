<?php
/**
 * API para manipulação de IDs legados
 */

// Inicializa o sistema
require_once __DIR__ . '/../includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Instancia o mapeador de IDs legados
$mapper = new LegacyIdMapper();

// Verifica o tipo de requisição
$action = $_GET['action'] ?? 'get';

// Processa a requisição de acordo com a ação
switch ($action) {
    case 'get':
        // Verifica se os parâmetros foram informados
        if (!isset($_GET['entidade']) || (!isset($_GET['id_atual']) && !isset($_GET['id_legado']))) {
            $response = [
                'success' => false,
                'message' => 'Parâmetros insuficientes. Informe a entidade e o ID atual ou ID legado.'
            ];
        } else {
            $entidade = $_GET['entidade'];
            $result = null;
            
            // Busca pelo ID atual
            if (isset($_GET['id_atual'])) {
                $idAtual = $_GET['id_atual'];
                $idLegado = $mapper->getLegacyId($entidade, $idAtual);
                
                if ($idLegado) {
                    $result = [
                        'entidade' => $entidade,
                        'id_atual' => $idAtual,
                        'id_legado' => $idLegado
                    ];
                }
            }
            // Busca pelo ID legado
            else if (isset($_GET['id_legado'])) {
                $idLegado = $_GET['id_legado'];
                $idAtual = $mapper->getCurrentId($entidade, $idLegado);
                
                if ($idAtual) {
                    $result = [
                        'entidade' => $entidade,
                        'id_atual' => $idAtual,
                        'id_legado' => $idLegado
                    ];
                }
            }
            
            if ($result) {
                $response = [
                    'success' => true,
                    'data' => $result
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Mapeamento não encontrado'
                ];
            }
        }
        
        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;
        
    case 'map':
        // Verifica se o usuário tem permissão para editar
        exigirPermissao('sistema', 'editar');
        
        // Verifica se é uma requisição POST
        if (!isPost()) {
            $response = [
                'success' => false,
                'message' => 'Método não permitido'
            ];
        } else {
            // Verifica se os parâmetros foram informados
            if (!isset($_POST['entidade']) || !isset($_POST['id_atual']) || !isset($_POST['id_legado'])) {
                $response = [
                    'success' => false,
                    'message' => 'Parâmetros insuficientes. Informe a entidade, ID atual e ID legado.'
                ];
            } else {
                $entidade = $_POST['entidade'];
                $idAtual = $_POST['id_atual'];
                $idLegado = $_POST['id_legado'];
                
                // Registra o mapeamento
                $result = $mapper->registerMapping($entidade, $idAtual, $idLegado);
                
                if ($result) {
                    // Tenta atualizar o campo id_legado na tabela da entidade
                    $mapper->updateLegacyId($entidade, $idAtual, $idLegado);
                    
                    // Registra o log
                    registrarLog(
                        'sistema',
                        'mapear_id_legado',
                        "Mapeamento de ID legado: {$entidade} - ID atual: {$idAtual}, ID legado: {$idLegado}",
                        $idAtual,
                        $entidade
                    );
                    
                    $response = [
                        'success' => true,
                        'message' => 'Mapeamento registrado com sucesso',
                        'data' => [
                            'entidade' => $entidade,
                            'id_atual' => $idAtual,
                            'id_legado' => $idLegado
                        ]
                    ];
                } else {
                    $response = [
                        'success' => false,
                        'message' => 'Erro ao registrar mapeamento'
                    ];
                }
            }
        }
        
        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;
        
    case 'find':
        // Verifica se os parâmetros foram informados
        if (!isset($_GET['entidade']) || !isset($_GET['id_legado'])) {
            $response = [
                'success' => false,
                'message' => 'Parâmetros insuficientes. Informe a entidade e o ID legado.'
            ];
        } else {
            $entidade = $_GET['entidade'];
            $idLegado = $_GET['id_legado'];
            
            // Busca a entidade pelo ID legado
            $result = $mapper->findByLegacyId($entidade, $idLegado);
            
            if ($result) {
                $response = [
                    'success' => true,
                    'data' => $result
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Registro não encontrado'
                ];
            }
        }
        
        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;
        
    case 'find_multiple':
        // Verifica se os parâmetros foram informados
        if (!isset($_GET['entidade']) || !isset($_GET['ids_legados'])) {
            $response = [
                'success' => false,
                'message' => 'Parâmetros insuficientes. Informe a entidade e os IDs legados.'
            ];
        } else {
            $entidade = $_GET['entidade'];
            $idsLegados = explode(',', $_GET['ids_legados']);
            
            // Busca as entidades pelos IDs legados
            $result = $mapper->findByLegacyIds($entidade, $idsLegados);
            
            $response = [
                'success' => true,
                'data' => $result
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
