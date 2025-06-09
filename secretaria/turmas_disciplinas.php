<?php
/**
 * Sistema de Gerenciamento de Vínculos Turmas-Disciplinas
 */

// Carrega as configurações
require_once 'config/config.php';
require_once 'includes/Database.php';
require_once 'includes/functions.php';

// Verifica autenticação
verificarAutenticacao();

// Conecta ao banco de dados
$db = Database::getInstance();

// Obtém a ação
$action = $_GET['action'] ?? 'listar';
$turma_id = $_GET['turma_id'] ?? null;
$disciplina_id = $_GET['disciplina_id'] ?? null;

// Processa as ações
switch ($action) {
    case 'listar':
        $titulo_pagina = 'Vínculos Turmas-Disciplinas';
        
        // Busca turmas com suas disciplinas
        $sql = "SELECT 
                    t.id,
                    t.nome AS turma_nome,
                    c.nome AS curso_nome,
                    p.nome AS polo_nome,
                    t.status,
                    COUNT(td.id) AS total_disciplinas,
                    (SELECT COUNT(*) FROM matriculas m WHERE m.turma_id = t.id AND m.status = 'ativo') AS total_alunos
                FROM turmas t
                JOIN cursos c ON t.curso_id = c.id
                JOIN polos p ON t.polo_id = p.id
                LEFT JOIN turmas_disciplinas td ON t.id = td.turma_id
                WHERE t.status IN ('planejada', 'em_andamento')
                GROUP BY t.id
                ORDER BY t.nome";
        
        $turmas = $db->fetchAll($sql);
        $view = 'listar';
        break;
        
    case 'gerenciar':
        if (!$turma_id) {
            redirect('turmas_disciplinas.php');
        }
        
        // Busca dados da turma
        $sql = "SELECT t.*, c.nome AS curso_nome, p.nome AS polo_nome 
                FROM turmas t 
                JOIN cursos c ON t.curso_id = c.id 
                JOIN polos p ON t.polo_id = p.id 
                WHERE t.id = ?";
        $turma = $db->fetchOne($sql, [$turma_id]);
        
        if (!$turma) {
            redirect('turmas_disciplinas.php');
        }
        
        $titulo_pagina = 'Gerenciar Disciplinas - ' . $turma['turma_nome'];
        
        // Busca disciplinas já vinculadas à turma
        $sql = "SELECT td.*, d.nome AS disciplina_nome, d.codigo, d.carga_horaria AS carga_padrao,
                       prof.nome AS professor_nome
                FROM turmas_disciplinas td
                JOIN disciplinas d ON td.disciplina_id = d.id
                LEFT JOIN professores prof ON td.professor_id = prof.id
                WHERE td.turma_id = ?
                ORDER BY d.nome";
        $disciplinas_vinculadas = $db->fetchAll($sql, [$turma_id]);
        
        // Busca disciplinas disponíveis para vincular (do mesmo curso)
        $sql = "SELECT d.id, d.nome, d.codigo, d.carga_horaria
                FROM disciplinas d
                WHERE d.curso_id = ? 
                AND d.status = 'ativo'
                AND d.id NOT IN (
                    SELECT disciplina_id FROM turmas_disciplinas WHERE turma_id = ?
                )
                ORDER BY d.nome";
        $disciplinas_disponiveis = $db->fetchAll($sql, [$turma['curso_id'], $turma_id]);
        
        // Busca professores ativos
        $sql = "SELECT id, nome FROM professores WHERE status = 'ativo' ORDER BY nome";
        $professores = $db->fetchAll($sql);
        
        $view = 'gerenciar';
        break;
        
    case 'vincular':
        if (!isPost() || !$turma_id || !$disciplina_id) {
            redirect('turmas_disciplinas.php');
        }
        
        $professor_id = $_POST['professor_id'] ?? null;
        $periodo_letivo = $_POST['periodo_letivo'] ?? '';
        $data_inicio = $_POST['data_inicio'] ?? null;
        $data_fim = $_POST['data_fim'] ?? null;
        $carga_horaria_turma = $_POST['carga_horaria_turma'] ?? null;
        $observacoes = $_POST['observacoes'] ?? '';
        
        try {
            $dados = [
                'turma_id' => $turma_id,
                'disciplina_id' => $disciplina_id,
                'professor_id' => $professor_id ?: null,
                'periodo_letivo' => $periodo_letivo,
                'data_inicio' => $data_inicio ?: null,
                'data_fim' => $data_fim ?: null,
                'carga_horaria_turma' => $carga_horaria_turma ?: null,
                'observacoes' => $observacoes,
                'status' => 'planejada'
            ];
            
            $db->insert('turmas_disciplinas', $dados);
            
            $_SESSION['success'] = 'Disciplina vinculada à turma com sucesso!';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Erro ao vincular disciplina: ' . $e->getMessage();
        }
        
        redirect('turmas_disciplinas.php?action=gerenciar&turma_id=' . $turma_id);
        break;
        
    case 'desvincular':
        if (!$turma_id || !$disciplina_id) {
            redirect('turmas_disciplinas.php');
        }
        
        try {
            $sql = "DELETE FROM turmas_disciplinas WHERE turma_id = ? AND disciplina_id = ?";
            $db->query($sql, [$turma_id, $disciplina_id]);
            
            $_SESSION['success'] = 'Disciplina desvinculada da turma com sucesso!';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Erro ao desvincular disciplina: ' . $e->getMessage();
        }
        
        redirect('turmas_disciplinas.php?action=gerenciar&turma_id=' . $turma_id);
        break;
        
    case 'atualizar_vinculo':
        if (!isPost() || !$turma_id || !$disciplina_id) {
            redirect('turmas_disciplinas.php');
        }
        
        $professor_id = $_POST['professor_id'] ?? null;
        $periodo_letivo = $_POST['periodo_letivo'] ?? '';
        $data_inicio = $_POST['data_inicio'] ?? null;
        $data_fim = $_POST['data_fim'] ?? null;
        $carga_horaria_turma = $_POST['carga_horaria_turma'] ?? null;
        $status = $_POST['status'] ?? 'planejada';
        $observacoes = $_POST['observacoes'] ?? '';
        
        try {
            $dados = [
                'professor_id' => $professor_id ?: null,
                'periodo_letivo' => $periodo_letivo,
                'data_inicio' => $data_inicio ?: null,
                'data_fim' => $data_fim ?: null,
                'carga_horaria_turma' => $carga_horaria_turma ?: null,
                'status' => $status,
                'observacoes' => $observacoes,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $where = ['turma_id' => $turma_id, 'disciplina_id' => $disciplina_id];
            $db->update('turmas_disciplinas', $dados, $where);
            
            $_SESSION['success'] = 'Vínculo atualizado com sucesso!';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Erro ao atualizar vínculo: ' . $e->getMessage();
        }
        
        redirect('turmas_disciplinas.php?action=gerenciar&turma_id=' . $turma_id);
        break;
        
    default:
        redirect('turmas_disciplinas.php');
}

// Inclui o cabeçalho
include 'includes/header.php';
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?php echo $titulo_pagina; ?></h1>
        
        <?php if ($action === 'gerenciar'): ?>
        <a href="turmas_disciplinas.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
        <?php endif; ?>
    </div>

    <?php include 'views/turmas_disciplinas/' . $view . '.php'; ?>
</div>

<?php include 'includes/footer.php'; ?>
