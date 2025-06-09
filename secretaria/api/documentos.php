<?php
/**
 * API para manipulação de documentos
 */

// Inicializa o sistema
require_once __DIR__ . '/../includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica se o usuário tem permissão para acessar o módulo de documentos
exigirPermissao('documentos');

// Instancia o modelo de documento
$documentoModel = new Documento();

// Verifica o tipo de requisição
$action = $_GET['action'] ?? 'list';

// Processa a requisição de acordo com a ação
switch ($action) {
    case 'tipos':
        // Obtém os tipos de documentos
        $tipos = $documentoModel->getTiposDocumentos();
        
        // Formata os dados para a resposta
        $response = [
            'success' => true,
            'data' => $tipos
        ];
        
        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;
        
    case 'tipo_get':
        // Verifica se o ID foi informado
        if (!isset($_GET['id'])) {
            $response = [
                'success' => false,
                'message' => 'ID do tipo de documento não informado'
            ];
        } else {
            // Obtém o tipo de documento pelo ID
            $tipo = $documentoModel->getTipoDocumentoById($_GET['id']);
            
            if ($tipo) {
                $response = [
                    'success' => true,
                    'data' => $tipo
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Tipo de documento não encontrado'
                ];
            }
        }
        
        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;
        
    case 'solicitacoes':
        // Obtém os filtros da requisição
        $filtros = [
            'aluno_id' => $_GET['aluno_id'] ?? '',
            'polo_id' => $_GET['polo_id'] ?? '',
            'tipo_documento_id' => $_GET['tipo_documento_id'] ?? '',
            'status' => $_GET['status'] ?? '',
            'pago' => $_GET['pago'] ?? '',
            'data_inicio' => $_GET['data_inicio'] ?? '',
            'data_fim' => $_GET['data_fim'] ?? ''
        ];
        
        // Obtém as solicitações de documentos
        $solicitacoes = $documentoModel->getSolicitacoes($filtros);
        
        // Formata os dados para a resposta
        $response = [
            'success' => true,
            'data' => $solicitacoes
        ];
        
        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;
        
    case 'solicitacao_get':
        // Verifica se o ID foi informado
        if (!isset($_GET['id'])) {
            $response = [
                'success' => false,
                'message' => 'ID da solicitação não informado'
            ];
        } else {
            // Obtém a solicitação pelo ID
            $solicitacao = $documentoModel->getSolicitacaoById($_GET['id']);
            
            if ($solicitacao) {
                $response = [
                    'success' => true,
                    'data' => $solicitacao
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Solicitação não encontrada'
                ];
            }
        }
        
        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;
        
    case 'solicitacao_create':
        // Verifica se o usuário tem permissão para criar solicitações
        exigirPermissao('documentos', 'criar');
        
        // Verifica se é uma requisição POST
        if (!isPost()) {
            $response = [
                'success' => false,
                'message' => 'Método não permitido'
            ];
        } else {
            // Obtém os dados do formulário
            $data = [
                'aluno_id' => $_POST['aluno_id'] ?? '',
                'polo_id' => $_POST['polo_id'] ?? '',
                'tipo_documento_id' => $_POST['tipo_documento_id'] ?? '',
                'quantidade' => $_POST['quantidade'] ?? 1,
                'finalidade' => $_POST['finalidade'] ?? null,
                'status' => 'solicitado',
                'pago' => 0,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // Valida os dados
            $errors = [];
            
            if (empty($data['aluno_id'])) {
                $errors[] = 'O aluno é obrigatório';
            }
            
            if (empty($data['polo_id'])) {
                $errors[] = 'O polo é obrigatório';
            }
            
            if (empty($data['tipo_documento_id'])) {
                $errors[] = 'O tipo de documento é obrigatório';
            }
            
            if (empty($data['quantidade']) || $data['quantidade'] <= 0) {
                $errors[] = 'A quantidade deve ser maior que zero';
            }
            
            // Se houver erros, retorna a resposta com os erros
            if (!empty($errors)) {
                $response = [
                    'success' => false,
                    'message' => 'Erros de validação',
                    'errors' => $errors
                ];
            } else {
                // Cria a solicitação
                $id = $documentoModel->criarSolicitacao($data);
                
                if ($id) {
                    $response = [
                        'success' => true,
                        'message' => 'Solicitação criada com sucesso',
                        'data' => [
                            'id' => $id
                        ]
                    ];
                } else {
                    $response = [
                        'success' => false,
                        'message' => 'Erro ao criar solicitação'
                    ];
                }
            }
        }
        
        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;
        
    case 'solicitacao_status':
        // Verifica se o usuário tem permissão para editar solicitações
        exigirPermissao('documentos', 'editar');
        
        // Verifica se é uma requisição POST
        if (!isPost()) {
            $response = [
                'success' => false,
                'message' => 'Método não permitido'
            ];
        } else {
            // Verifica se o ID foi informado
            if (!isset($_POST['id'])) {
                $response = [
                    'success' => false,
                    'message' => 'ID da solicitação não informado'
                ];
            } else {
                // Obtém os dados do formulário
                $id = $_POST['id'];
                $status = $_POST['status'] ?? '';
                
                // Valida os dados
                $errors = [];
                
                if (empty($status)) {
                    $errors[] = 'O status é obrigatório';
                }
                
                // Se houver erros, retorna a resposta com os erros
                if (!empty($errors)) {
                    $response = [
                        'success' => false,
                        'message' => 'Erros de validação',
                        'errors' => $errors
                    ];
                } else {
                    // Atualiza o status da solicitação
                    $result = $documentoModel->atualizarStatusSolicitacao($id, $status);
                    
                    if ($result) {
                        $response = [
                            'success' => true,
                            'message' => 'Status da solicitação atualizado com sucesso'
                        ];
                    } else {
                        $response = [
                            'success' => false,
                            'message' => 'Erro ao atualizar status da solicitação'
                        ];
                    }
                }
            }
        }
        
        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;
        
    case 'solicitacao_pago':
        // Verifica se o usuário tem permissão para editar solicitações
        exigirPermissao('documentos', 'editar');
        
        // Verifica se é uma requisição POST
        if (!isPost()) {
            $response = [
                'success' => false,
                'message' => 'Método não permitido'
            ];
        } else {
            // Verifica se o ID foi informado
            if (!isset($_POST['id'])) {
                $response = [
                    'success' => false,
                    'message' => 'ID da solicitação não informado'
                ];
            } else {
                // Obtém o ID da solicitação
                $id = $_POST['id'];
                
                // Marca a solicitação como paga
                $result = $documentoModel->marcarComoPago($id);
                
                if ($result) {
                    $response = [
                        'success' => true,
                        'message' => 'Solicitação marcada como paga com sucesso'
                    ];
                } else {
                    $response = [
                        'success' => false,
                        'message' => 'Erro ao marcar solicitação como paga'
                    ];
                }
            }
        }
        
        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;
        
    case 'documentos_emitidos':
        // Obtém os filtros da requisição
        $filtros = [
            'solicitacao_id' => $_GET['solicitacao_id'] ?? '',
            'aluno_id' => $_GET['aluno_id'] ?? '',
            'polo_id' => $_GET['polo_id'] ?? '',
            'tipo_documento_id' => $_GET['tipo_documento_id'] ?? '',
            'status' => $_GET['status'] ?? '',
            'data_inicio' => $_GET['data_inicio'] ?? '',
            'data_fim' => $_GET['data_fim'] ?? ''
        ];
        
        // Obtém os documentos emitidos
        $documentos = $documentoModel->getDocumentosEmitidos($filtros);
        
        // Formata os dados para a resposta
        $response = [
            'success' => true,
            'data' => $documentos
        ];
        
        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;
        
    case 'documento_get':
        // Verifica se o ID foi informado
        if (!isset($_GET['id'])) {
            $response = [
                'success' => false,
                'message' => 'ID do documento não informado'
            ];
        } else {
            // Obtém o documento pelo ID
            $documento = $documentoModel->getDocumentoEmitidoById($_GET['id']);
            
            if ($documento) {
                $response = [
                    'success' => true,
                    'data' => $documento
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Documento não encontrado'
                ];
            }
        }
        
        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;
        
    case 'documento_emitir':
        // Verifica se o usuário tem permissão para emitir documentos
        exigirPermissao('documentos', 'criar');
        
        // Verifica se é uma requisição POST
        if (!isPost()) {
            $response = [
                'success' => false,
                'message' => 'Método não permitido'
            ];
        } else {
            // Obtém os dados do formulário
            $data = [
                'solicitacao_id' => $_POST['solicitacao_id'] ?? '',
                'numero_documento' => $_POST['numero_documento'] ?? gerarNumeroDocumento(time()),
                'data_emissao' => date('Y-m-d'),
                'status' => 'ativo'
            ];
            
            // Verifica se foi enviado um arquivo
            if (isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] === UPLOAD_ERR_OK) {
                $arquivo = $_FILES['arquivo'];
                $extensao = getExtensaoArquivo($arquivo['name']);
                
                // Verifica se a extensão é permitida
                if (extensaoPermitida($extensao, ['pdf', 'doc', 'docx'])) {
                    // Gera um nome único para o arquivo
                    $nomeArquivo = gerarNomeArquivoUnico('documento', $extensao);
                    
                    // Define o caminho do arquivo
                    $caminhoArquivo = getDocumentosDir() . '/' . $nomeArquivo;
                    
                    // Move o arquivo para o diretório de documentos
                    if (move_uploaded_file($arquivo['tmp_name'], $caminhoArquivo)) {
                        $data['arquivo'] = $nomeArquivo;
                    } else {
                        $response = [
                            'success' => false,
                            'message' => 'Erro ao fazer upload do arquivo'
                        ];
                        
                        // Retorna a resposta em formato JSON
                        header('Content-Type: application/json');
                        echo json_encode($response);
                        break;
                    }
                } else {
                    $response = [
                        'success' => false,
                        'message' => 'Extensão de arquivo não permitida'
                    ];
                    
                    // Retorna a resposta em formato JSON
                    header('Content-Type: application/json');
                    echo json_encode($response);
                    break;
                }
            }
            
            // Valida os dados
            $errors = [];
            
            if (empty($data['solicitacao_id'])) {
                $errors[] = 'A solicitação é obrigatória';
            }
            
            // Se houver erros, retorna a resposta com os erros
            if (!empty($errors)) {
                $response = [
                    'success' => false,
                    'message' => 'Erros de validação',
                    'errors' => $errors
                ];
            } else {
                try {
                    // Emite o documento
                    $id = $documentoModel->emitirDocumento($data);
                    
                    $response = [
                        'success' => true,
                        'message' => 'Documento emitido com sucesso',
                        'data' => [
                            'id' => $id
                        ]
                    ];
                } catch (Exception $e) {
                    $response = [
                        'success' => false,
                        'message' => 'Erro ao emitir documento: ' . $e->getMessage()
                    ];
                }
            }
        }
        
        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;
        
    case 'documento_entregar':
        // Verifica se o usuário tem permissão para editar documentos
        exigirPermissao('documentos', 'editar');
        
        // Verifica se é uma requisição POST
        if (!isPost()) {
            $response = [
                'success' => false,
                'message' => 'Método não permitido'
            ];
        } else {
            // Verifica se o ID foi informado
            if (!isset($_POST['id'])) {
                $response = [
                    'success' => false,
                    'message' => 'ID do documento não informado'
                ];
            } else {
                // Obtém o ID do documento
                $id = $_POST['id'];
                
                try {
                    // Marca o documento como entregue
                    $result = $documentoModel->marcarComoEntregue($id);
                    
                    $response = [
                        'success' => true,
                        'message' => 'Documento marcado como entregue com sucesso'
                    ];
                } catch (Exception $e) {
                    $response = [
                        'success' => false,
                        'message' => 'Erro ao marcar documento como entregue: ' . $e->getMessage()
                    ];
                }
            }
        }
        
        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;
        
    case 'documento_cancelar':
        // Verifica se o usuário tem permissão para editar documentos
        exigirPermissao('documentos', 'editar');
        
        // Verifica se é uma requisição POST
        if (!isPost()) {
            $response = [
                'success' => false,
                'message' => 'Método não permitido'
            ];
        } else {
            // Verifica se o ID foi informado
            if (!isset($_POST['id'])) {
                $response = [
                    'success' => false,
                    'message' => 'ID do documento não informado'
                ];
            } else {
                // Obtém o ID do documento
                $id = $_POST['id'];
                
                try {
                    // Cancela o documento
                    $result = $documentoModel->cancelarDocumento($id);
                    
                    $response = [
                        'success' => true,
                        'message' => 'Documento cancelado com sucesso'
                    ];
                } catch (Exception $e) {
                    $response = [
                        'success' => false,
                        'message' => 'Erro ao cancelar documento: ' . $e->getMessage()
                    ];
                }
            }
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
