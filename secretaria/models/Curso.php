<?php
/**
 * Classe modelo para Cursos
 */

class Curso {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Obtém todos os cursos
     * 
     * @param array $filtros Filtros a serem aplicados
     * @return array Lista de cursos
     */
    public function getAll($filtros = []) {
        $params = [];
        $where = [];
        
        // Aplica filtros
        if (!empty($filtros['nome'])) {
            $where[] = "c.nome LIKE ?";
            $params[] = "%" . $filtros['nome'] . "%";
        }
        
        if (!empty($filtros['status'])) {
            $where[] = "c.status = ?";
            $params[] = $filtros['status'];
        }
        
        if (!empty($filtros['modalidade'])) {
            $where[] = "c.modalidade = ?";
            $params[] = $filtros['modalidade'];
        }
        
        if (!empty($filtros['nivel'])) {
            $where[] = "c.nivel = ?";
            $params[] = $filtros['nivel'];
        }
        
        if (!empty($filtros['area_conhecimento_id'])) {
            $where[] = "c.area_conhecimento_id = ?";
            $params[] = $filtros['area_conhecimento_id'];
        }
        
        // Monta a cláusula WHERE
        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        
        // Consulta SQL
        $sql = "
            SELECT 
                c.*,
                a.nome AS area_conhecimento,
                u.nome AS coordenador
            FROM 
                cursos c
                LEFT JOIN areas_conhecimento a ON c.area_conhecimento_id = a.id
                LEFT JOIN usuarios u ON c.coordenador_id = u.id
            {$whereClause}
            ORDER BY c.nome
        ";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Obtém um curso pelo ID
     * 
     * @param int $id ID do curso
     * @return array|false Dados do curso ou false se não encontrado
     */
    public function getById($id) {
        $sql = "
            SELECT 
                c.*,
                a.nome AS area_conhecimento,
                u.nome AS coordenador
            FROM 
                cursos c
                LEFT JOIN areas_conhecimento a ON c.area_conhecimento_id = a.id
                LEFT JOIN usuarios u ON c.coordenador_id = u.id
            WHERE 
                c.id = ?
        ";
        
        return $this->db->fetchOne($sql, [$id]);
    }
    
    /**
     * Cria um novo curso
     * 
     * @param array $data Dados do curso
     * @return int ID do curso criado
     */
    public function create($data) {
        // Registra log
        Utils::registrarLog(
            'cursos',
            'criar',
            'Criação de novo curso: ' . $data['nome'],
            null,
            'curso',
            null,
            $data
        );
        
        return $this->db->insert('cursos', $data);
    }
    
    /**
     * Atualiza um curso
     * 
     * @param int $id ID do curso
     * @param array $data Dados a serem atualizados
     * @return int Número de linhas afetadas
     */
    public function update($id, $data) {
        // Obtém dados antigos para o log
        $dadosAntigos = $this->getById($id);
        
        // Registra log
        Utils::registrarLog(
            'cursos',
            'editar',
            'Atualização de curso: ' . $data['nome'],
            $id,
            'curso',
            $dadosAntigos,
            $data
        );
        
        return $this->db->update('cursos', $data, 'id = ?', [$id]);
    }
    
    /**
     * Exclui um curso
     * 
     * @param int $id ID do curso
     * @return int Número de linhas afetadas
     */
    public function delete($id) {
        // Obtém dados para o log
        $curso = $this->getById($id);
        
        // Registra log
        Utils::registrarLog(
            'cursos',
            'excluir',
            'Exclusão de curso: ' . $curso['nome'],
            $id,
            'curso',
            $curso,
            null
        );
        
        return $this->db->delete('cursos', 'id = ?', [$id]);
    }
    
    /**
     * Obtém as disciplinas de um curso
     * 
     * @param int $cursoId ID do curso
     * @return array Lista de disciplinas
     */
    public function getDisciplinas($cursoId) {
        $sql = "
            SELECT 
                d.*,
                u.nome AS professor_nome
            FROM 
                disciplinas d
                LEFT JOIN usuarios u ON d.professor_padrao_id = u.id
            WHERE 
                d.curso_id = ?
            ORDER BY 
                d.nome
        ";
        
        return $this->db->fetchAll($sql, [$cursoId]);
    }
    
    /**
     * Obtém as turmas de um curso
     * 
     * @param int $cursoId ID do curso
     * @return array Lista de turmas
     */
    public function getTurmas($cursoId) {
        $sql = "
            SELECT 
                t.*,
                p.nome AS polo_nome,
                u.nome AS coordenador_nome
            FROM 
                turmas t
                LEFT JOIN polos p ON t.polo_id = p.id
                LEFT JOIN usuarios u ON t.professor_coordenador_id = u.id
            WHERE 
                t.curso_id = ?
            ORDER BY 
                t.data_inicio DESC
        ";
        
        return $this->db->fetchAll($sql, [$cursoId]);
    }
    
    /**
     * Obtém estatísticas de um curso
     * 
     * @param int $cursoId ID do curso
     * @return array Estatísticas
     */
    public function getEstatisticas($cursoId) {
        $stats = [];
        
        // Total de alunos
        $sql = "SELECT COUNT(*) as total FROM alunos WHERE curso_id = ?";
        $result = $this->db->fetchOne($sql, [$cursoId]);
        $stats['total_alunos'] = $result['total'];
        
        // Alunos por status
        $sql = "SELECT status, COUNT(*) as total FROM alunos WHERE curso_id = ? GROUP BY status";
        $stats['alunos_por_status'] = $this->db->fetchAll($sql, [$cursoId]);
        
        // Total de disciplinas
        $sql = "SELECT COUNT(*) as total FROM disciplinas WHERE curso_id = ?";
        $result = $this->db->fetchOne($sql, [$cursoId]);
        $stats['total_disciplinas'] = $result['total'];
        
        // Total de turmas
        $sql = "SELECT COUNT(*) as total FROM turmas WHERE curso_id = ?";
        $result = $this->db->fetchOne($sql, [$cursoId]);
        $stats['total_turmas'] = $result['total'];
        
        // Turmas por status
        $sql = "SELECT status, COUNT(*) as total FROM turmas WHERE curso_id = ? GROUP BY status";
        $stats['turmas_por_status'] = $this->db->fetchAll($sql, [$cursoId]);
        
        return $stats;
    }
}
