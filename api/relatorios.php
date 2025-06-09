<?php
/**
 * API para geração de relatórios
 */

// Inicializa o sistema
require_once __DIR__ . '/../includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica se o usuário tem permissão para acessar o módulo de relatórios
exigirPermissao('relatorios');

// Instancia o banco de dados
$db = Database::getInstance();

// Verifica o tipo de requisição
$action = $_GET['action'] ?? 'list';

// Processa a requisição de acordo com a ação
switch ($action) {
    case 'alunos':
        // Obtém os filtros da requisição
        $filtros = [];
        $params = [];
        
        if (!empty($_GET['nome'])) {
            $filtros[] = "a.nome LIKE ?";
            $params[] = "%" . $_GET['nome'] . "%";
        }
        
        if (!empty($_GET['cpf'])) {
            $filtros[] = "a.cpf LIKE ?";
            $params[] = "%" . $_GET['cpf'] . "%";
        }
        
        if (!empty($_GET['status'])) {
            $filtros[] = "a.status = ?";
            $params[] = $_GET['status'];
        }
        
        if (!empty($_GET['curso_id'])) {
            $filtros[] = "a.curso_id = ?";
            $params[] = $_GET['curso_id'];
        }
        
        if (!empty($_GET['polo_id'])) {
            $filtros[] = "a.polo_id = ?";
            $params[] = $_GET['polo_id'];
        }
        
        if (!empty($_GET['data_ingresso_inicio'])) {
            $filtros[] = "a.data_ingresso >= ?";
            $params[] = $_GET['data_ingresso_inicio'];
        }
        
        if (!empty($_GET['data_ingresso_fim'])) {
            $filtros[] = "a.data_ingresso <= ?";
            $params[] = $_GET['data_ingresso_fim'];
        }
        
        // Monta a cláusula WHERE
        $whereClause = !empty($filtros) ? "WHERE " . implode(" AND ", $filtros) : "";
        
        // Consulta SQL
        $sql = "
            SELECT 
                a.*,
                c.nome AS curso_nome,
                p.nome AS polo_nome
            FROM 
                alunos a
                LEFT JOIN cursos c ON a.curso_id = c.id
                LEFT JOIN polos p ON a.polo_id = p.id
            {$whereClause}
            ORDER BY a.nome
        ";
        
        // Obtém os alunos
        $alunos = $db->fetchAll($sql, $params);
        
        // Formata os dados para a resposta
        $response = [
            'success' => true,
            'data' => $alunos
        ];
        
        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;
        
    case 'matriculas':
        // Obtém os filtros da requisição
        $filtros = [];
        $params = [];
        
        if (!empty($_GET['aluno_id'])) {
            $filtros[] = "m.aluno_id = ?";
            $params[] = $_GET['aluno_id'];
        }
        
        if (!empty($_GET['curso_id'])) {
            $filtros[] = "m.curso_id = ?";
            $params[] = $_GET['curso_id'];
        }
        
        if (!empty($_GET['polo_id'])) {
            $filtros[] = "m.polo_id = ?";
            $params[] = $_GET['polo_id'];
        }
        
        if (!empty($_GET['turma_id'])) {
            $filtros[] = "m.turma_id = ?";
            $params[] = $_GET['turma_id'];
        }
        
        if (!empty($_GET['status'])) {
            $filtros[] = "m.status = ?";
            $params[] = $_GET['status'];
        }
        
        if (!empty($_GET['data_inicio'])) {
            $filtros[] = "m.data_matricula >= ?";
            $params[] = $_GET['data_inicio'];
        }
        
        if (!empty($_GET['data_fim'])) {
            $filtros[] = "m.data_matricula <= ?";
            $params[] = $_GET['data_fim'];
        }
        
        // Monta a cláusula WHERE
        $whereClause = !empty($filtros) ? "WHERE " . implode(" AND ", $filtros) : "";
        
        // Consulta SQL
        $sql = "
            SELECT 
                m.*,
                a.nome AS aluno_nome,
                a.cpf AS aluno_cpf,
                c.nome AS curso_nome,
                p.nome AS polo_nome,
                t.nome AS turma_nome
            FROM 
                matriculas m
                JOIN alunos a ON m.aluno_id = a.id
                JOIN cursos c ON m.curso_id = c.id
                LEFT JOIN polos p ON m.polo_id = p.id
                LEFT JOIN turmas t ON m.turma_id = t.id
            {$whereClause}
            ORDER BY m.data_matricula DESC
        ";
        
        // Obtém as matrículas
        $matriculas = $db->fetchAll($sql, $params);
        
        // Formata os dados para a resposta
        $response = [
            'success' => true,
            'data' => $matriculas
        ];
        
        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;
        
    case 'documentos':
        // Obtém os filtros da requisição
        $filtros = [];
        $params = [];
        
        if (!empty($_GET['aluno_id'])) {
            $filtros[] = "s.aluno_id = ?";
            $params[] = $_GET['aluno_id'];
        }
        
        if (!empty($_GET['polo_id'])) {
            $filtros[] = "s.polo_id = ?";
            $params[] = $_GET['polo_id'];
        }
        
        if (!empty($_GET['tipo_documento_id'])) {
            $filtros[] = "s.tipo_documento_id = ?";
            $params[] = $_GET['tipo_documento_id'];
        }
        
        if (!empty($_GET['status'])) {
            $filtros[] = "s.status = ?";
            $params[] = $_GET['status'];
        }
        
        if (!empty($_GET['pago'])) {
            $filtros[] = "s.pago = ?";
            $params[] = $_GET['pago'];
        }
        
        if (!empty($_GET['data_inicio'])) {
            $filtros[] = "s.created_at >= ?";
            $params[] = $_GET['data_inicio'] . ' 00:00:00';
        }
        
        if (!empty($_GET['data_fim'])) {
            $filtros[] = "s.created_at <= ?";
            $params[] = $_GET['data_fim'] . ' 23:59:59';
        }
        
        // Monta a cláusula WHERE
        $whereClause = !empty($filtros) ? "WHERE " . implode(" AND ", $filtros) : "";
        
        // Consulta SQL
        $sql = "
            SELECT 
                s.*,
                a.nome AS aluno_nome,
                a.cpf AS aluno_cpf,
                p.nome AS polo_nome,
                t.nome AS tipo_documento_nome,
                t.valor AS tipo_documento_valor
            FROM 
                solicitacoes_documentos s
                JOIN alunos a ON s.aluno_id = a.id
                JOIN polos p ON s.polo_id = p.id
                JOIN tipos_documentos t ON s.tipo_documento_id = t.id
            {$whereClause}
            ORDER BY s.created_at DESC
        ";
        
        // Obtém as solicitações de documentos
        $documentos = $db->fetchAll($sql, $params);
        
        // Formata os dados para a resposta
        $response = [
            'success' => true,
            'data' => $documentos
        ];
        
        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;
        
    case 'desempenho':
        // Verifica se o curso_id foi informado
        if (!isset($_GET['curso_id'])) {
            $response = [
                'success' => false,
                'message' => 'ID do curso não informado'
            ];
        } else {
            // Obtém o ID do curso
            $cursoId = (int)$_GET['curso_id'];
            
            // Obtém as disciplinas do curso
            $sql = "
                SELECT 
                    d.id,
                    d.nome,
                    d.codigo
                FROM 
                    disciplinas d
                WHERE 
                    d.curso_id = ?
                ORDER BY 
                    d.nome
            ";
            
            $disciplinas = $db->fetchAll($sql, [$cursoId]);
            
            // Obtém as notas por disciplina
            $desempenho = [];
            
            foreach ($disciplinas as $disciplina) {
                $sql = "
                    SELECT 
                        AVG(nd.nota) AS media,
                        COUNT(CASE WHEN nd.situacao = 'aprovado' THEN 1 END) AS aprovados,
                        COUNT(CASE WHEN nd.situacao = 'reprovado' THEN 1 END) AS reprovados,
                        COUNT(CASE WHEN nd.situacao = 'cursando' THEN 1 END) AS cursando
                    FROM 
                        notas_disciplinas nd
                        JOIN matriculas m ON nd.matricula_id = m.id
                    WHERE 
                        nd.disciplina_id = ?
                        AND m.curso_id = ?
                ";
                
                $stats = $db->fetchOne($sql, [$disciplina['id'], $cursoId]);
                
                $desempenho[] = [
                    'disciplina_id' => $disciplina['id'],
                    'disciplina_nome' => $disciplina['nome'],
                    'disciplina_codigo' => $disciplina['codigo'],
                    'media' => $stats['media'] ?? 0,
                    'aprovados' => $stats['aprovados'] ?? 0,
                    'reprovados' => $stats['reprovados'] ?? 0,
                    'cursando' => $stats['cursando'] ?? 0
                ];
            }
            
            $response = [
                'success' => true,
                'data' => $desempenho
            ];
        }
        
        // Retorna a resposta em formato JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        break;
        
    case 'estatisticas':
        // Obtém as estatísticas gerais
        $stats = [];
        
        // Total de alunos
        $result = $db->fetchOne("SELECT COUNT(*) as total FROM alunos");
        $stats['total_alunos'] = $result['total'];
        
        // Alunos por status
        $sql = "SELECT status, COUNT(*) as total FROM alunos GROUP BY status";
        $stats['alunos_por_status'] = $db->fetchAll($sql);
        
        // Alunos por curso
        $sql = "
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
        ";
        $stats['alunos_por_curso'] = $db->fetchAll($sql);
        
        // Alunos por polo
        $sql = "
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
        ";
        $stats['alunos_por_polo'] = $db->fetchAll($sql);
        
        // Matrículas por status
        $sql = "SELECT status, COUNT(*) as total FROM matriculas GROUP BY status";
        $stats['matriculas_por_status'] = $db->fetchAll($sql);
        
        // Documentos por status
        $sql = "SELECT status, COUNT(*) as total FROM solicitacoes_documentos GROUP BY status";
        $stats['documentos_por_status'] = $db->fetchAll($sql);
        
        // Documentos por tipo
        $sql = "
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
        ";
        $stats['documentos_por_tipo'] = $db->fetchAll($sql);
        
        // Turmas por status
        $sql = "SELECT status, COUNT(*) as total FROM turmas GROUP BY status";
        $stats['turmas_por_status'] = $db->fetchAll($sql);
        
        // Formata os dados para a resposta
        $response = [
            'success' => true,
            'data' => $stats
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
        header('Content-Type: application/json');
        echo json_encode($response);
        break;
}
