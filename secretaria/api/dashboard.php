<?php
/**
 * API para o dashboard
 */

// Inicializa o sistema
require_once __DIR__ . '/../includes/init.php';

// Configura o cabeçalho para JSON
header('Content-Type: application/json');

// Tratamento de erros
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    $response = [
        'success' => false,
        'message' => 'Erro interno do servidor: ' . $errstr,
        'error_code' => $errno
    ];
    echo json_encode($response);
    exit;
});

try {
    // Verifica se o usuário está autenticado
    if (!isset($_SESSION['user_id'])) {
        $response = [
            'success' => false,
            'message' => 'Usuário não autenticado'
        ];
        echo json_encode($response);
        exit;
    }

    // Instancia o banco de dados
    $db = Database::getInstance();

    // Verifica o tipo de requisição
    $action = $_GET['action'] ?? 'stats';

// Processa a requisição de acordo com a ação
switch ($action) {
    case 'stats':
        // Obtém as estatísticas gerais
        $stats = [];

        // Total de alunos
        $result = $db->fetchOne("SELECT COUNT(*) as total FROM alunos");
        $stats['total_alunos'] = $result['total'] ?? 0;

        // Matrículas ativas
        $result = $db->fetchOne("SELECT COUNT(*) as total FROM matriculas WHERE status = 'ativo'");
        $stats['matriculas_ativas'] = $result['total'] ?? 0;

        // Documentos pendentes
        $result = $db->fetchOne("SELECT COUNT(*) as total FROM solicitacoes_documentos WHERE status IN ('solicitado', 'processando')");
        $stats['documentos_pendentes'] = $result['total'] ?? 0;

        // Turmas ativas
        $result = $db->fetchOne("SELECT COUNT(*) as total FROM turmas WHERE status = 'em_andamento'");
        $stats['turmas_ativas'] = $result['total'] ?? 0;

        // Formata os dados para a resposta
        $response = [
            'success' => true,
            'data' => $stats
        ];

        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;

    case 'tarefas_pendentes':
        // Obtém as tarefas pendentes da secretaria
        $tarefas = [];

        // Documentos pendentes
        $sql = "SELECT
                    sd.id,
                    'documento' as tipo,
                    CONCAT('Solicitação de ', td.nome) as descricao,
                    a.nome as aluno_nome,
                    sd.data_solicitacao
                FROM
                    solicitacoes_documentos sd
                    JOIN alunos a ON sd.aluno_id = a.id
                    JOIN tipos_documentos td ON sd.tipo_documento_id = td.id
                WHERE
                    sd.status IN ('solicitado', 'processando')
                ORDER BY
                    sd.data_solicitacao ASC
                LIMIT 5";

        $documentos = $db->fetchAll($sql);
        $tarefas = array_merge($tarefas, $documentos);

        // Matrículas pendentes
        $sql = "SELECT
                    m.id,
                    'matricula' as tipo,
                    'Matrícula pendente de aprovação' as descricao,
                    a.nome as aluno_nome,
                    m.data_matricula
                FROM
                    matriculas m
                    JOIN alunos a ON m.aluno_id = a.id
                WHERE
                    m.status = 'pendente'
                ORDER BY
                    m.data_matricula ASC
                LIMIT 5";

        $matriculas = $db->fetchAll($sql);
        $tarefas = array_merge($tarefas, $matriculas);

        // Ordena as tarefas por data
        usort($tarefas, function($a, $b) {
            $dataA = strtotime($a['data_solicitacao'] ?? $a['data_matricula']);
            $dataB = strtotime($b['data_solicitacao'] ?? $b['data_matricula']);
            return $dataA - $dataB;
        });

        // Limita a 5 tarefas
        $tarefas = array_slice($tarefas, 0, 5);

        // Formata os dados para a resposta
        $response = [
            'success' => true,
            'data' => $tarefas
        ];

        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;

    case 'atividades_recentes':
        // Obtém as atividades recentes do sistema
        $sql = "SELECT
                    l.id,
                    l.modulo,
                    l.acao,
                    l.descricao,
                    u.nome as usuario_nome,
                    l.created_at as data_atividade
                FROM
                    logs_sistema l
                    LEFT JOIN usuarios u ON l.usuario_id = u.id
                ORDER BY
                    l.created_at DESC
                LIMIT 10";

        $atividades = $db->fetchAll($sql);

        // Formata os dados para a resposta
        $response = [
            'success' => true,
            'data' => $atividades
        ];

        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;

    case 'alunos_por_status':
        // Obtém os alunos por status
        $result = $db->fetchAll("SELECT status, COUNT(*) as total FROM alunos GROUP BY status");

        // Formata os dados para a resposta
        $response = [
            'success' => true,
            'data' => $result
        ];

        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;

    case 'alunos_por_curso':
        // Obtém os alunos por curso
        $result = $db->fetchAll("
            SELECT
                c.nome AS curso,
                COUNT(a.id) as total
            FROM
                cursos c
                LEFT JOIN alunos a ON c.id = a.curso_id
            GROUP BY
                c.id
            ORDER BY
                total DESC
            LIMIT 5
        ");

        // Formata os dados para a resposta
        $response = [
            'success' => true,
            'data' => $result
        ];

        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;

    case 'alunos_por_polo':
        // Obtém os alunos por polo
        $result = $db->fetchAll("
            SELECT
                p.nome AS polo,
                COUNT(a.id) as total
            FROM
                polos p
                LEFT JOIN alunos a ON p.id = a.polo_id
            GROUP BY
                p.id
            ORDER BY
                total DESC
            LIMIT 5
        ");

        // Formata os dados para a resposta
        $response = [
            'success' => true,
            'data' => $result
        ];

        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;

    case 'documentos_por_tipo':
        // Obtém os documentos por tipo
        $result = $db->fetchAll("
            SELECT
                t.nome AS tipo,
                COUNT(s.id) as total
            FROM
                tipos_documentos t
                LEFT JOIN solicitacoes_documentos s ON t.id = s.tipo_documento_id
            GROUP BY
                t.id
            ORDER BY
                total DESC
            LIMIT 5
        ");

        // Formata os dados para a resposta
        $response = [
            'success' => true,
            'data' => $result
        ];

        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;

    case 'documentos_por_status':
        // Obtém os documentos por status
        $result = $db->fetchAll("SELECT status, COUNT(*) as total FROM solicitacoes_documentos GROUP BY status");

        // Formata os dados para a resposta
        $response = [
            'success' => true,
            'data' => $result
        ];

        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;

    case 'tarefas_pendentes':
        // Obtém as tarefas pendentes
        $tarefas = [];

        // Documentos solicitados
        $result = $db->fetchAll("
            SELECT
                s.id,
                a.nome AS aluno_nome,
                t.nome AS tipo_documento,
                s.created_at AS data_solicitacao,
                'documento' AS tipo,
                'Solicitação de documento pendente' AS descricao
            FROM
                solicitacoes_documentos s
                JOIN alunos a ON s.aluno_id = a.id
                JOIN tipos_documentos t ON s.tipo_documento_id = t.id
            WHERE
                s.status = 'solicitado'
            ORDER BY
                s.created_at DESC
            LIMIT 5
        ");

        $tarefas = array_merge($tarefas, $result);

        // Matrículas recentes
        $result = $db->fetchAll("
            SELECT
                m.id,
                a.nome AS aluno_nome,
                c.nome AS curso_nome,
                m.data_matricula,
                'matricula' AS tipo,
                'Matrícula recente' AS descricao
            FROM
                matriculas m
                JOIN alunos a ON m.aluno_id = a.id
                JOIN cursos c ON m.curso_id = c.id
            ORDER BY
                m.data_matricula DESC
            LIMIT 5
        ");

        $tarefas = array_merge($tarefas, $result);

        // Ordena as tarefas por data (mais recentes primeiro)
        usort($tarefas, function($a, $b) {
            $dateA = strtotime($a['data_matricula'] ?? $a['data_solicitacao']);
            $dateB = strtotime($b['data_matricula'] ?? $b['data_solicitacao']);
            return $dateB - $dateA;
        });

        // Limita a 10 tarefas
        $tarefas = array_slice($tarefas, 0, 10);

        // Formata os dados para a resposta
        $response = [
            'success' => true,
            'data' => $tarefas
        ];

        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;

    case 'atividades_recentes':
        // Obtém as atividades recentes
        $atividades = $db->fetchAll("
            SELECT
                l.id,
                u.nome AS usuario_nome,
                l.modulo,
                l.acao,
                l.descricao,
                l.created_at AS data_atividade
            FROM
                logs_sistema l
                LEFT JOIN usuarios u ON l.usuario_id = u.id
            ORDER BY
                l.created_at DESC
            LIMIT 10
        ");

        // Formata os dados para a resposta
        $response = [
            'success' => true,
            'data' => $atividades
        ];

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
        echo json_encode($response);
        break;
}

} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => 'Erro: ' . $e->getMessage()
    ];
    echo json_encode($response);
}
