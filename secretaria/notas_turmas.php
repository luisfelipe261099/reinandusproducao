<?php
/**
 * Sistema de Lançamento de Notas por Turma-Disciplina
 */

// Carrega as configurações
require_once 'config/config.php';
require_once 'includes/Database.php';
require_once 'includes/functions.php';

// Verifica autenticação
verificarAutenticacao();

// Conecta ao banco de dados
$db = Database::getInstance();

// Obtém parâmetros
$action = $_GET['action'] ?? 'selecionar_turma';
$turma_id = $_GET['turma_id'] ?? null;
$disciplina_id = $_GET['disciplina_id'] ?? null;

// Processa as ações
switch ($action) {
    case 'selecionar_turma':
        $titulo_pagina = 'Lançamento de Notas - Selecionar Turma';
        
        // Busca turmas com disciplinas vinculadas
        $sql = "SELECT DISTINCT
                    t.id,
                    t.nome AS turma_nome,
                    c.nome AS curso_nome,
                    p.nome AS polo_nome,
                    COUNT(td.disciplina_id) AS total_disciplinas,
                    (SELECT COUNT(*) FROM matriculas m WHERE m.turma_id = t.id AND m.status = 'ativo') AS total_alunos
                FROM turmas t
                JOIN cursos c ON t.curso_id = c.id
                JOIN polos p ON t.polo_id = p.id
                JOIN turmas_disciplinas td ON t.id = td.turma_id
                WHERE t.status IN ('em_andamento', 'planejada')
                GROUP BY t.id
                HAVING total_disciplinas > 0
                ORDER BY t.nome";
        
        $turmas = $db->fetchAll($sql);
        $view = 'selecionar_turma';
        break;
        
    case 'selecionar_disciplina':
        if (!$turma_id) {
            redirect('notas_turmas.php');
        }
        
        // Busca dados da turma
        $sql = "SELECT t.*, c.nome AS curso_nome, p.nome AS polo_nome 
                FROM turmas t 
                JOIN cursos c ON t.curso_id = c.id 
                JOIN polos p ON t.polo_id = p.id 
                WHERE t.id = ?";
        $turma = $db->fetchOne($sql, [$turma_id]);
        
        if (!$turma) {
            redirect('notas_turmas.php');
        }
        
        $titulo_pagina = 'Selecionar Disciplina - ' . $turma['nome'];
        
        // Busca disciplinas da turma
        $sql = "SELECT td.*, d.nome AS disciplina_nome, d.codigo,
                       prof.nome AS professor_nome,
                       (SELECT COUNT(*) FROM notas_disciplinas nd 
                        JOIN matriculas m ON nd.matricula_id = m.id 
                        WHERE m.turma_id = td.turma_id AND nd.disciplina_id = td.disciplina_id) AS total_notas_lancadas,
                       (SELECT COUNT(*) FROM matriculas m WHERE m.turma_id = td.turma_id AND m.status = 'ativo') AS total_alunos
                FROM turmas_disciplinas td
                JOIN disciplinas d ON td.disciplina_id = d.id
                LEFT JOIN professores prof ON td.professor_id = prof.id
                WHERE td.turma_id = ?
                ORDER BY d.nome";
        
        $disciplinas = $db->fetchAll($sql, [$turma_id]);
        $view = 'selecionar_disciplina';
        break;
        
    case 'lancar_notas':
        if (!$turma_id || !$disciplina_id) {
            redirect('notas_turmas.php');
        }
        
        // Busca dados da turma e disciplina
        $sql = "SELECT t.nome AS turma_nome, d.nome AS disciplina_nome, d.codigo,
                       c.nome AS curso_nome, p.nome AS polo_nome,
                       td.professor_id, prof.nome AS professor_nome
                FROM turmas t
                JOIN turmas_disciplinas td ON t.id = td.turma_id
                JOIN disciplinas d ON td.disciplina_id = d.id
                JOIN cursos c ON t.curso_id = c.id
                JOIN polos p ON t.polo_id = p.id
                LEFT JOIN professores prof ON td.professor_id = prof.id
                WHERE t.id = ? AND d.id = ?";
        
        $info = $db->fetchOne($sql, [$turma_id, $disciplina_id]);
        
        if (!$info) {
            redirect('notas_turmas.php');
        }
        
        $titulo_pagina = 'Lançar Notas - ' . $info['disciplina_nome'];
        
        // Busca alunos da turma com suas notas
        $sql = "SELECT 
                    a.id AS aluno_id,
                    a.nome AS aluno_nome,
                    a.cpf,
                    m.id AS matricula_id,
                    m.numero_matricula,
                    nd.id AS nota_id,
                    nd.nota,
                    nd.frequencia,
                    nd.situacao,
                    nd.data_lancamento,
                    nd.observacoes
                FROM matriculas m
                JOIN alunos a ON m.aluno_id = a.id
                LEFT JOIN notas_disciplinas nd ON (nd.matricula_id = m.id AND nd.disciplina_id = ?)
                WHERE m.turma_id = ? 
                AND m.status = 'ativo'
                ORDER BY a.nome";
        
        $alunos = $db->fetchAll($sql, [$disciplina_id, $turma_id]);
        $view = 'lancar_notas';
        break;
        
    case 'salvar_notas':
        if (!isPost() || !$turma_id || !$disciplina_id) {
            redirect('notas_turmas.php');
        }
        
        $notas = $_POST['notas'] ?? [];
        $erros = [];
        $sucessos = 0;
        
        try {
            $db->beginTransaction();
            
            foreach ($notas as $matricula_id => $dados) {
                $nota = $dados['nota'] ?? null;
                $frequencia = $dados['frequencia'] ?? null;
                $situacao = $dados['situacao'] ?? 'cursando';
                $observacoes = $dados['observacoes'] ?? '';
                
                // Pula se não tiver dados
                if ($nota === '' && $frequencia === '') {
                    continue;
                }
                
                // Valida dados
                if ($nota !== null && $nota !== '' && ($nota < 0 || $nota > 10)) {
                    $erros[] = "Nota inválida para matrícula {$matricula_id}";
                    continue;
                }
                
                if ($frequencia !== null && $frequencia !== '' && ($frequencia < 0 || $frequencia > 100)) {
                    $erros[] = "Frequência inválida para matrícula {$matricula_id}";
                    continue;
                }
                
                // Verifica se já existe nota
                $sql = "SELECT id FROM notas_disciplinas WHERE matricula_id = ? AND disciplina_id = ?";
                $nota_existente = $db->fetchOne($sql, [$matricula_id, $disciplina_id]);
                
                $dados_nota = [
                    'matricula_id' => $matricula_id,
                    'disciplina_id' => $disciplina_id,
                    'nota' => $nota !== '' ? $nota : null,
                    'frequencia' => $frequencia !== '' ? $frequencia : null,
                    'situacao' => $situacao,
                    'observacoes' => $observacoes,
                    'data_lancamento' => date('Y-m-d'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                if ($nota_existente) {
                    // Atualiza nota existente
                    unset($dados_nota['matricula_id'], $dados_nota['disciplina_id']);
                    $db->update('notas_disciplinas', $dados_nota, ['id' => $nota_existente['id']]);
                } else {
                    // Insere nova nota
                    $dados_nota['created_at'] = date('Y-m-d H:i:s');
                    $db->insert('notas_disciplinas', $dados_nota);
                }
                
                $sucessos++;
            }
            
            $db->commit();
            
            if (empty($erros)) {
                $_SESSION['success'] = "Notas salvas com sucesso! ({$sucessos} registros processados)";
            } else {
                $_SESSION['warning'] = "Algumas notas foram salvas com problemas: " . implode(', ', $erros);
            }
            
        } catch (Exception $e) {
            $db->rollback();
            $_SESSION['error'] = 'Erro ao salvar notas: ' . $e->getMessage();
        }
        
        redirect("notas_turmas.php?action=lancar_notas&turma_id={$turma_id}&disciplina_id={$disciplina_id}");
        break;
        
    default:
        redirect('notas_turmas.php');
}

// Inclui o cabeçalho
include 'includes/header.php';
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?php echo $titulo_pagina; ?></h1>
        
        <?php if ($action !== 'selecionar_turma'): ?>
        <a href="notas_turmas.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
        <?php endif; ?>
    </div>

    <?php include 'views/notas_turmas/' . $view . '.php'; ?>
</div>

<?php include 'includes/footer.php'; ?>
