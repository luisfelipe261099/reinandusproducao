<?php
/**
 * API para geração de documentos com suporte a IDs legados
 */

// Inicializa o sistema
require_once __DIR__ . '/../includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica se o usuário tem permissão para acessar o módulo de documentos
exigirPermissao('documentos');

// Instancia o gerador de documentos
$generator = new DocumentGenerator();

// Verifica o tipo de requisição
$action = $_GET['action'] ?? 'documento_aluno';

// Processa a requisição de acordo com a ação
switch ($action) {
    case 'documento_aluno':
        // Verifica se os parâmetros foram informados
        if (!isset($_GET['aluno_id']) || !isset($_GET['tipo_documento_id'])) {
            $response = [
                'success' => false,
                'message' => 'Parâmetros insuficientes. Informe o ID do aluno e o tipo de documento.'
            ];
        } else {
            $alunoId = $_GET['aluno_id'];
            $tipoDocumentoId = $_GET['tipo_documento_id'];
            $params = [
                'finalidade' => $_GET['finalidade'] ?? 'Solicitação via API',
                'pago' => isset($_GET['pago']) ? (int)$_GET['pago'] : 1
            ];
            
            // Gera o documento
            $documento = $generator->generateStudentDocument($alunoId, $tipoDocumentoId, $params);
            
            if ($documento) {
                $response = [
                    'success' => true,
                    'message' => 'Documento gerado com sucesso',
                    'data' => $documento
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Erro ao gerar documento'
                ];
            }
        }
        
        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;
        
    case 'documento_matricula':
        // Verifica se o ID da matrícula foi informado
        if (!isset($_GET['matricula_id'])) {
            $response = [
                'success' => false,
                'message' => 'ID da matrícula não informado'
            ];
        } else {
            $matriculaId = $_GET['matricula_id'];
            $params = [
                'finalidade' => $_GET['finalidade'] ?? 'Comprovante de Matrícula',
                'pago' => isset($_GET['pago']) ? (int)$_GET['pago'] : 1
            ];
            
            // Gera o documento
            $documento = $generator->generateEnrollmentDocument($matriculaId, $params);
            
            if ($documento) {
                $response = [
                    'success' => true,
                    'message' => 'Documento de matrícula gerado com sucesso',
                    'data' => $documento
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Erro ao gerar documento de matrícula'
                ];
            }
        }
        
        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;
        
    case 'historico':
        // Verifica se o ID do aluno foi informado
        if (!isset($_GET['aluno_id'])) {
            $response = [
                'success' => false,
                'message' => 'ID do aluno não informado'
            ];
        } else {
            $alunoId = $_GET['aluno_id'];
            $params = [
                'finalidade' => $_GET['finalidade'] ?? 'Histórico Escolar',
                'pago' => isset($_GET['pago']) ? (int)$_GET['pago'] : 1
            ];
            
            // Gera o histórico
            $documento = $generator->generateTranscript($alunoId, $params);
            
            if ($documento) {
                $response = [
                    'success' => true,
                    'message' => 'Histórico escolar gerado com sucesso',
                    'data' => $documento
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Erro ao gerar histórico escolar'
                ];
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
