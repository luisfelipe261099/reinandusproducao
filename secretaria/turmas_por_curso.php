<?php
/**
 * API para retornar turmas por curso
 * Arquivo auxiliar para requisições AJAX
 */

// Inicializa o sistema
require_once __DIR__ . '/includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Instancia o banco de dados
$db = Database::getInstance();

// Obtém o ID do curso
$curso_id = $_GET['curso_id'] ?? null;

// Se não foi informado o curso, retorna erro
if (empty($curso_id)) {
    header('Content-Type: application/json');
    echo json_encode(['erro' => 'Curso não informado']);
    exit;
}

// Função para buscar todas as turmas por curso
function buscarTurmasPorCurso($db, $curso_id) {
    try {
        // Primeiro, verifica se o curso existe
        $sql_curso = "SELECT nome FROM cursos WHERE id = ?";
        $curso = $db->fetchOne($sql_curso, [$curso_id]);
        
        if (!$curso) {
            return [];
        }
        
        // Busca as turmas ativas
        $sql = "SELECT id, nome FROM turmas WHERE curso_id = ? AND status = 'ativo' ORDER BY nome ASC";
        $turmas = $db->fetchAll($sql, [$curso_id]) ?: [];
        
        // Se não encontrou turmas ativas, busca todas as turmas deste curso
        if (empty($turmas)) {
            $sql_alt = "SELECT id, nome, status FROM turmas WHERE curso_id = ? ORDER BY nome ASC";
            $todas_turmas = $db->fetchAll($sql_alt, [$curso_id]) ?: [];
            
            // Adiciona as turmas não ativas ao resultado, indicando o status
            foreach ($todas_turmas as $turma) {
                $turmas[] = [
                    'id' => $turma['id'],
                    'nome' => $turma['nome'] . ($turma['status'] !== 'ativo' ? ' (' . $turma['status'] . ')' : '')
                ];
            }
            
            // Se ainda não encontrou turmas, adiciona opção para cadastrar nova
            if (empty($turmas)) {
                $turmas[] = [
                    'id' => 'novo',
                    'nome' => 'Nenhuma turma encontrada - Clique para adicionar'
                ];
            }
        }
        
        return $turmas;
    } catch (Exception $e) {
        error_log('Erro ao buscar turmas: ' . $e->getMessage());
        return [];
    }
}

// Busca as turmas
$turmas = buscarTurmasPorCurso($db, $curso_id);

// Retorna as turmas em formato JSON
header('Content-Type: application/json');
echo json_encode($turmas);