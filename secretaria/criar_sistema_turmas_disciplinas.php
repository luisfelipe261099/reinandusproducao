<?php
/**
 * Script para criar o sistema de vínculo entre turmas e disciplinas
 */

// Carrega as configurações
require_once 'config/config.php';
require_once 'includes/Database.php';

try {
    // Conecta ao banco de dados
    $db = Database::getInstance();
    
    echo "<h1>Criando Sistema de Vínculo Turmas-Disciplinas</h1>";
    
    // 1. Criar tabela turmas_disciplinas
    echo "<h2>1. Criando tabela turmas_disciplinas</h2>";
    
    $sql = "CREATE TABLE IF NOT EXISTS turmas_disciplinas (
        id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        turma_id INT(10) UNSIGNED NOT NULL,
        disciplina_id INT(10) UNSIGNED NOT NULL,
        professor_id INT(10) UNSIGNED NULL COMMENT 'Professor específico para esta disciplina nesta turma',
        periodo_letivo VARCHAR(50) NULL COMMENT 'Ex: 1º Semestre 2024, Módulo 1, etc.',
        data_inicio DATE NULL,
        data_fim DATE NULL,
        carga_horaria_turma INT(11) NULL COMMENT 'Carga horária específica para esta turma (pode diferir da disciplina padrão)',
        status ENUM('planejada', 'em_andamento', 'concluida', 'cancelada') DEFAULT 'planejada',
        observacoes TEXT NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY unique_turma_disciplina (turma_id, disciplina_id),
        KEY idx_turma_id (turma_id),
        KEY idx_disciplina_id (disciplina_id),
        KEY idx_professor_id (professor_id),
        KEY idx_status (status),
        CONSTRAINT fk_turmas_disciplinas_turma 
            FOREIGN KEY (turma_id) REFERENCES turmas(id) 
            ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT fk_turmas_disciplinas_disciplina 
            FOREIGN KEY (disciplina_id) REFERENCES disciplinas(id) 
            ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT fk_turmas_disciplinas_professor 
            FOREIGN KEY (professor_id) REFERENCES professores(id) 
            ON DELETE SET NULL ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
    COMMENT='Tabela de relacionamento entre turmas e disciplinas'";
    
    $db->query($sql);
    echo "✓ Tabela turmas_disciplinas criada com sucesso!<br>";
    
    // 2. Criar view para facilitar consultas
    echo "<h2>2. Criando view para consultas otimizadas</h2>";
    
    $sql = "CREATE OR REPLACE VIEW view_turmas_disciplinas AS
    SELECT 
        td.id,
        td.turma_id,
        td.disciplina_id,
        td.professor_id,
        td.periodo_letivo,
        td.data_inicio,
        td.data_fim,
        td.carga_horaria_turma,
        td.status,
        td.observacoes,
        t.nome AS turma_nome,
        t.curso_id,
        t.polo_id,
        c.nome AS curso_nome,
        p.nome AS polo_nome,
        d.nome AS disciplina_nome,
        d.codigo AS disciplina_codigo,
        d.carga_horaria AS disciplina_carga_horaria_padrao,
        prof.nome AS professor_nome,
        prof.email AS professor_email,
        (SELECT COUNT(*) FROM matriculas m WHERE m.turma_id = t.id AND m.status = 'ativo') AS total_alunos
    FROM turmas_disciplinas td
    JOIN turmas t ON td.turma_id = t.id
    JOIN disciplinas d ON td.disciplina_id = d.id
    JOIN cursos c ON t.curso_id = c.id
    JOIN polos p ON t.polo_id = p.id
    LEFT JOIN professores prof ON td.professor_id = prof.id";
    
    $db->query($sql);
    echo "✓ View view_turmas_disciplinas criada com sucesso!<br>";
    
    // 3. Criar função para obter disciplinas de uma turma
    echo "<h2>3. Criando stored procedures auxiliares</h2>";
    
    $sql = "DROP PROCEDURE IF EXISTS sp_get_disciplinas_turma";
    $db->query($sql);
    
    $sql = "CREATE PROCEDURE sp_get_disciplinas_turma(IN p_turma_id INT)
    BEGIN
        SELECT 
            td.*,
            d.nome AS disciplina_nome,
            d.codigo AS disciplina_codigo,
            prof.nome AS professor_nome
        FROM turmas_disciplinas td
        JOIN disciplinas d ON td.disciplina_id = d.id
        LEFT JOIN professores prof ON td.professor_id = prof.id
        WHERE td.turma_id = p_turma_id
        ORDER BY d.nome;
    END";
    
    $db->query($sql);
    echo "✓ Stored procedure sp_get_disciplinas_turma criada!<br>";
    
    // 4. Criar função para obter alunos e suas notas por turma/disciplina
    $sql = "DROP PROCEDURE IF EXISTS sp_get_alunos_notas_turma_disciplina";
    $db->query($sql);
    
    $sql = "CREATE PROCEDURE sp_get_alunos_notas_turma_disciplina(
        IN p_turma_id INT, 
        IN p_disciplina_id INT
    )
    BEGIN
        SELECT 
            a.id AS aluno_id,
            a.nome AS aluno_nome,
            a.cpf AS aluno_cpf,
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
        LEFT JOIN notas_disciplinas nd ON (nd.matricula_id = m.id AND nd.disciplina_id = p_disciplina_id)
        WHERE m.turma_id = p_turma_id 
        AND m.status = 'ativo'
        ORDER BY a.nome;
    END";
    
    $db->query($sql);
    echo "✓ Stored procedure sp_get_alunos_notas_turma_disciplina criada!<br>";
    
    echo "<h2>4. Sistema criado com sucesso!</h2>";
    echo "<p><strong>Próximos passos:</strong></p>";
    echo "<ul>";
    echo "<li>1. Vincular disciplinas às turmas existentes</li>";
    echo "<li>2. Criar interface para gerenciar vínculos turma-disciplina</li>";
    echo "<li>3. Adaptar o sistema de notas para usar os novos vínculos</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<h2>❌ Erro durante a criação:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
