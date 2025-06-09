<?php
/**
 * Classe modelo para Disciplinas
 */

class Disciplina {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Obtém todas as disciplinas
     * 
     * @param array $filtros Filtros a serem aplicados
     * @return array Lista de disciplinas
     */
    public function getAll($filtros = []) {
        $params = [];
        $where = [];
        
        // Aplica filtros
        if (!empty($filtros['nome'])) {
            $where[] = "d.nome LIKE ?";
            $params[] = "%" . $filtros['nome'] . "%";
        }
        
        if (!empty($filtros['codigo'])) {
            $where[] = "d.codigo LIKE ?";
            $params[] = "%" . $filtros['codigo'] . "%";
        }
        
        if (!empty($filtros['curso_id'])) {
            $where[] = "d.curso_id = ?";
            $params[] = $filtros['curso_id'];
        }
        
        if (!empty($filtros['status'])) {
            $where[] = "d.status = ?";
            $params[] = $filtros['status'];
        }
        
        // Monta a cláusula WHERE
        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        
        // Consulta SQL
        $sql = "
            SELECT 
                d.*,
                c.nome AS curso_nome,
                u.nome AS professor_nome
            FROM 
                disciplinas d
                LEFT JOIN cursos c ON d.curso_id = c.id
                LEFT JOIN usuarios u ON d.professor_padrao_id = u.id
            {$whereClause}
            ORDER BY d.nome
        ";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Obtém uma disciplina pelo ID
     * 
     * @param int $id ID da disciplina
     * @return array|false Dados da disciplina ou false se não encontrada
     */
    public function getById($id) {
        $sql = "
            SELECT 
                d.*,
                c.nome AS curso_nome,
                u.nome AS professor_nome
            FROM 
                disciplinas d
                LEFT JOIN cursos c ON d.curso_id = c.id
                LEFT JOIN usuarios u ON d.professor_padrao_id = u.id
            WHERE 
                d.id = ?
        ";
        
        return $this->db->fetchOne($sql, [$id]);
    }
    
    /**
     * Cria uma nova disciplina
     * 
     * @param array $data Dados da disciplina
     * @return int ID da disciplina criada
     */
    public function create($data) {
        // Registra log
        Utils::registrarLog(
            'disciplinas',
            'criar',
            'Criação de nova disciplina: ' . $data['nome'],
            null,
            'disciplina',
            null,
            $data
        );
        
        return $this->db->insert('disciplinas', $data);
    }
    
    /**
     * Atualiza uma disciplina
     * 
     * @param int $id ID da disciplina
     * @param array $data Dados a serem atualizados
     * @return int Número de linhas afetadas
     */
    public function update($id, $data) {
        // Obtém dados antigos para o log
        $dadosAntigos = $this->getById($id);
        
        // Registra log
        Utils::registrarLog(
            'disciplinas',
            'editar',
            'Atualização de disciplina: ' . $data['nome'],
            $id,
            'disciplina',
            $dadosAntigos,
            $data
        );
        
        return $this->db->update('disciplinas', $data, 'id = ?', [$id]);
    }
    
    /**
     * Exclui uma disciplina
     * 
     * @param int $id ID da disciplina
     * @return int Número de linhas afetadas
     */
    public function delete($id) {
        // Obtém dados para o log
        $disciplina = $this->getById($id);
        
        // Registra log
        Utils::registrarLog(
            'disciplinas',
            'excluir',
            'Exclusão de disciplina: ' . $disciplina['nome'],
            $id,
            'disciplina',
            $disciplina,
            null
        );
        
        return $this->db->delete('disciplinas', 'id = ?', [$id]);
    }
    
    /**
     * Obtém as notas de uma disciplina
     * 
     * @param int $disciplinaId ID da disciplina
     * @param int $turmaId ID da turma (opcional)
     * @return array Lista de notas
     */
    public function getNotas($disciplinaId, $turmaId = null) {
        $params = [$disciplinaId];
        $whereClause = "nd.disciplina_id = ?";
        
        if ($turmaId) {
            $whereClause .= " AND m.turma_id = ?";
            $params[] = $turmaId;
        }
        
        $sql = "
            SELECT 
                nd.*,
                a.nome AS aluno_nome,
                a.cpf AS aluno_cpf,
                m.id AS matricula_id,
                t.nome AS turma_nome
            FROM 
                notas_disciplinas nd
                JOIN matriculas m ON nd.matricula_id = m.id
                JOIN alunos a ON m.aluno_id = a.id
                LEFT JOIN turmas t ON m.turma_id = t.id
            WHERE 
                {$whereClause}
            ORDER BY 
                a.nome
        ";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Atualiza a nota de um aluno em uma disciplina
     * 
     * @param int $notaId ID da nota
     * @param array $data Dados a serem atualizados
     * @return int Número de linhas afetadas
     */
    public function atualizarNota($notaId, $data) {
        // Obtém dados antigos para o log
        $sql = "
            SELECT 
                nd.*,
                a.nome AS aluno_nome,
                d.nome AS disciplina_nome
            FROM 
                notas_disciplinas nd
                JOIN matriculas m ON nd.matricula_id = m.id
                JOIN alunos a ON m.aluno_id = a.id
                JOIN disciplinas d ON nd.disciplina_id = d.id
            WHERE 
                nd.id = ?
        ";
        $dadosAntigos = $this->db->fetchOne($sql, [$notaId]);
        
        // Registra log
        Utils::registrarLog(
            'notas',
            'editar',
            'Atualização de nota do aluno ' . $dadosAntigos['aluno_nome'] . ' na disciplina ' . $dadosAntigos['disciplina_nome'],
            $notaId,
            'nota_disciplina',
            $dadosAntigos,
            $data
        );
        
        return $this->db->update('notas_disciplinas', $data, 'id = ?', [$notaId]);
    }
}
