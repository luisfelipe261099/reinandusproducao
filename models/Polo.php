<?php
/**
 * Classe modelo para Polos
 */

class Polo {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Obtém todos os polos
     * 
     * @param array $filtros Filtros a serem aplicados
     * @return array Lista de polos
     */
    public function getAll($filtros = []) {
        $params = [];
        $where = [];
        
        // Aplica filtros
        if (!empty($filtros['nome'])) {
            $where[] = "p.nome LIKE ?";
            $params[] = "%" . $filtros['nome'] . "%";
        }
        
        if (!empty($filtros['cidade_id'])) {
            $where[] = "p.cidade_id = ?";
            $params[] = $filtros['cidade_id'];
        }
        
        if (!empty($filtros['status'])) {
            $where[] = "p.status = ?";
            $params[] = $filtros['status'];
        }
        
        if (!empty($filtros['status_contrato'])) {
            $where[] = "p.status_contrato = ?";
            $params[] = $filtros['status_contrato'];
        }
        
        // Monta a cláusula WHERE
        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        
        // Consulta SQL
        $sql = "
            SELECT 
                p.*,
                c.nome AS cidade_nome,
                e.nome AS estado_nome,
                e.sigla AS estado_sigla,
                u.nome AS responsavel_nome
            FROM 
                polos p
                LEFT JOIN cidades c ON p.cidade_id = c.id
                LEFT JOIN estados e ON c.estado_id = e.id
                LEFT JOIN usuarios u ON p.responsavel_id = u.id
            {$whereClause}
            ORDER BY p.nome
        ";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Obtém um polo pelo ID
     * 
     * @param int $id ID do polo
     * @return array|false Dados do polo ou false se não encontrado
     */
    public function getById($id) {
        $sql = "
            SELECT 
                p.*,
                c.nome AS cidade_nome,
                e.nome AS estado_nome,
                e.sigla AS estado_sigla,
                u.nome AS responsavel_nome,
                u.email AS responsavel_email
            FROM 
                polos p
                LEFT JOIN cidades c ON p.cidade_id = c.id
                LEFT JOIN estados e ON c.estado_id = e.id
                LEFT JOIN usuarios u ON p.responsavel_id = u.id
            WHERE 
                p.id = ?
        ";
        
        return $this->db->fetchOne($sql, [$id]);
    }
    
    /**
     * Cria um novo polo
     * 
     * @param array $data Dados do polo
     * @return int ID do polo criado
     */
    public function create($data) {
        // Registra log
        Utils::registrarLog(
            'polos',
            'criar',
            'Criação de novo polo: ' . $data['nome'],
            null,
            'polo',
            null,
            $data
        );
        
        return $this->db->insert('polos', $data);
    }
    
    /**
     * Atualiza um polo
     * 
     * @param int $id ID do polo
     * @param array $data Dados a serem atualizados
     * @return int Número de linhas afetadas
     */
    public function update($id, $data) {
        // Obtém dados antigos para o log
        $dadosAntigos = $this->getById($id);
        
        // Registra log
        Utils::registrarLog(
            'polos',
            'editar',
            'Atualização de polo: ' . $data['nome'],
            $id,
            'polo',
            $dadosAntigos,
            $data
        );
        
        return $this->db->update('polos', $data, 'id = ?', [$id]);
    }
    
    /**
     * Exclui um polo
     * 
     * @param int $id ID do polo
     * @return int Número de linhas afetadas
     */
    public function delete($id) {
        // Obtém dados para o log
        $polo = $this->getById($id);
        
        // Registra log
        Utils::registrarLog(
            'polos',
            'excluir',
            'Exclusão de polo: ' . $polo['nome'],
            $id,
            'polo',
            $polo,
            null
        );
        
        return $this->db->delete('polos', 'id = ?', [$id]);
    }
    
    /**
     * Obtém as turmas de um polo
     * 
     * @param int $poloId ID do polo
     * @return array Lista de turmas
     */
    public function getTurmas($poloId) {
        $sql = "
            SELECT 
                t.*,
                c.nome AS curso_nome,
                u.nome AS coordenador_nome
            FROM 
                turmas t
                LEFT JOIN cursos c ON t.curso_id = c.id
                LEFT JOIN usuarios u ON t.professor_coordenador_id = u.id
            WHERE 
                t.polo_id = ?
            ORDER BY 
                t.data_inicio DESC
        ";
        
        return $this->db->fetchAll($sql, [$poloId]);
    }
    
    /**
     * Obtém os alunos de um polo
     * 
     * @param int $poloId ID do polo
     * @param array $filtros Filtros adicionais
     * @return array Lista de alunos
     */
    public function getAlunos($poloId, $filtros = []) {
        $params = [$poloId];
        $where = ["a.polo_id = ?"];
        
        // Aplica filtros adicionais
        if (!empty($filtros['nome'])) {
            $where[] = "a.nome LIKE ?";
            $params[] = "%" . $filtros['nome'] . "%";
        }
        
        if (!empty($filtros['curso_id'])) {
            $where[] = "a.curso_id = ?";
            $params[] = $filtros['curso_id'];
        }
        
        if (!empty($filtros['status'])) {
            $where[] = "a.status = ?";
            $params[] = $filtros['status'];
        }
        
        // Monta a cláusula WHERE
        $whereClause = "WHERE " . implode(" AND ", $where);
        
        // Consulta SQL
        $sql = "
            SELECT 
                a.*,
                c.nome AS curso_nome
            FROM 
                alunos a
                LEFT JOIN cursos c ON a.curso_id = c.id
            {$whereClause}
            ORDER BY a.nome
        ";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Obtém estatísticas de um polo
     * 
     * @param int $poloId ID do polo
     * @return array Estatísticas
     */
    public function getEstatisticas($poloId) {
        $stats = [];
        
        // Total de alunos
        $sql = "SELECT COUNT(*) as total FROM alunos WHERE polo_id = ?";
        $result = $this->db->fetchOne($sql, [$poloId]);
        $stats['total_alunos'] = $result['total'];
        
        // Alunos por status
        $sql = "SELECT status, COUNT(*) as total FROM alunos WHERE polo_id = ? GROUP BY status";
        $stats['alunos_por_status'] = $this->db->fetchAll($sql, [$poloId]);
        
        // Alunos por curso
        $sql = "
            SELECT 
                c.nome AS curso, 
                COUNT(*) as total 
            FROM 
                alunos a
                JOIN cursos c ON a.curso_id = c.id
            WHERE 
                a.polo_id = ?
            GROUP BY 
                a.curso_id
            ORDER BY 
                total DESC
        ";
        $stats['alunos_por_curso'] = $this->db->fetchAll($sql, [$poloId]);
        
        // Total de turmas
        $sql = "SELECT COUNT(*) as total FROM turmas WHERE polo_id = ?";
        $result = $this->db->fetchOne($sql, [$poloId]);
        $stats['total_turmas'] = $result['total'];
        
        // Turmas por status
        $sql = "SELECT status, COUNT(*) as total FROM turmas WHERE polo_id = ? GROUP BY status";
        $stats['turmas_por_status'] = $this->db->fetchAll($sql, [$poloId]);
        
        // Documentos emitidos
        $sql = "
            SELECT 
                COUNT(*) as total 
            FROM 
                documentos_emitidos de
                JOIN solicitacoes_documentos sd ON de.solicitacao_id = sd.id
            WHERE 
                sd.polo_id = ?
        ";
        $result = $this->db->fetchOne($sql, [$poloId]);
        $stats['documentos_emitidos'] = $result['total'];
        
        return $stats;
    }
}
