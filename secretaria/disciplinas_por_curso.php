<?php
/**
 * API para retornar disciplinas por curso
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

// Função para buscar todas as disciplinas por curso
function buscarDisciplinasPorCurso($db, $curso_id) {
    try {
        // Primeiro, verifica se o curso existe
        $sql_curso = "SELECT nome FROM cursos WHERE id = ?";
        $curso = $db->fetchOne($sql_curso, [$curso_id]);
        
        if (!$curso) {
            return [];
        }
        
        // Busca as disciplinas ativas
        $sql = "SELECT id, nome, codigo FROM disciplinas WHERE curso_id = ? AND status = 'ativo' ORDER BY nome ASC";
        $disciplinas = $db->fetchAll($sql, [$curso_id]) ?: [];
        
        // Se não encontrou disciplinas ativas, busca todas as disciplinas deste curso
        if (empty($disciplinas)) {
            $sql_alt = "SELECT id, nome, codigo, status FROM disciplinas WHERE curso_id = ? ORDER BY nome ASC";
            $todas_disciplinas = $db->fetchAll($sql_alt, [$curso_id]) ?: [];
            
            // Adiciona as disciplinas não ativas ao resultado, indicando o status
            foreach ($todas_disciplinas as $disciplina) {
                $disciplinas[] = [
                    'id' => $disciplina['id'],
                    'nome' => $disciplina['nome'] . ($disciplina['status'] !== 'ativo' ? ' (' . $disciplina['status'] . ')' : ''),
                    'codigo' => $disciplina['codigo']
                ];
            }
            
            // Se ainda não encontrou disciplinas, adiciona opção para cadastrar nova
            if (empty($disciplinas)) {
                $disciplinas[] = [
                    'id' => 'novo',
                    'nome' => 'Nenhuma disciplina encontrada - Clique para adicionar',
                    'codigo' => ''
                ];
            }
        }
        
        return $disciplinas;
    } catch (Exception $e) {
        error_log('Erro ao buscar disciplinas: ' . $e->getMessage());
        return [];
    }
}

// Busca as disciplinas
$disciplinas = buscarDisciplinasPorCurso($db, $curso_id);

// Retorna as disciplinas em formato JSON
header('Content-Type: application/json');
echo json_encode($disciplinas);