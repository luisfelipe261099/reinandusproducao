<?php
/**
 * Classe modelo para Alunos
 */

class Aluno {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Obtém todos os alunos com filtros opcionais
     *
     * @param array $filtros Filtros a serem aplicados
     * @param int $limit Limite de registros
     * @param int $offset Deslocamento
     * @return array Lista de alunos
     */
    public function getAll($filtros = [], $limit = 100, $offset = 0) {
        $params = [];
        $where = [];

        // Aplica filtros
        if (!empty($filtros['nome'])) {
            $where[] = "a.nome LIKE ?";
            $params[] = "%" . $filtros['nome'] . "%";
        }

        if (!empty($filtros['cpf'])) {
            $where[] = "a.cpf LIKE ?";
            $params[] = "%" . $filtros['cpf'] . "%";
        }

        if (!empty($filtros['email'])) {
            $where[] = "a.email LIKE ?";
            $params[] = "%" . $filtros['email'] . "%";
        }

        if (!empty($filtros['status'])) {
            $where[] = "a.status = ?";
            $params[] = $filtros['status'];
        }

        if (!empty($filtros['curso_id'])) {
            $where[] = "a.curso_id = ?";
            $params[] = $filtros['curso_id'];
        }

        if (!empty($filtros['polo_id'])) {
            $where[] = "a.polo_id = ?";
            $params[] = $filtros['polo_id'];
        }

        // Filtro por ID legado
        if (!empty($filtros['id_legado'])) {
            $where[] = "a.id_legado = ?";
            $params[] = $filtros['id_legado'];
        }

        if (!empty($filtros['data_ingresso_inicio'])) {
            $where[] = "a.data_ingresso >= ?";
            $params[] = $filtros['data_ingresso_inicio'];
        }

        if (!empty($filtros['data_ingresso_fim'])) {
            $where[] = "a.data_ingresso <= ?";
            $params[] = $filtros['data_ingresso_fim'];
        }

        // Monta a cláusula WHERE
        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

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
            LIMIT ? OFFSET ?
        ";

        // Adiciona limit e offset aos parâmetros
        $params[] = $limit;
        $params[] = $offset;

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Conta o total de alunos com filtros opcionais
     *
     * @param array $filtros Filtros a serem aplicados
     * @return int Total de alunos
     */
    public function count($filtros = []) {
        $params = [];
        $where = [];

        // Aplica filtros
        if (!empty($filtros['nome'])) {
            $where[] = "nome LIKE ?";
            $params[] = "%" . $filtros['nome'] . "%";
        }

        if (!empty($filtros['cpf'])) {
            $where[] = "cpf LIKE ?";
            $params[] = "%" . $filtros['cpf'] . "%";
        }

        if (!empty($filtros['email'])) {
            $where[] = "email LIKE ?";
            $params[] = "%" . $filtros['email'] . "%";
        }

        if (!empty($filtros['status'])) {
            $where[] = "status = ?";
            $params[] = $filtros['status'];
        }

        if (!empty($filtros['curso_id'])) {
            $where[] = "curso_id = ?";
            $params[] = $filtros['curso_id'];
        }

        if (!empty($filtros['polo_id'])) {
            $where[] = "polo_id = ?";
            $params[] = $filtros['polo_id'];
        }

        // Monta a cláusula WHERE
        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        // Consulta SQL
        $sql = "SELECT COUNT(*) as total FROM alunos {$whereClause}";

        $result = $this->db->fetchOne($sql, $params);
        return $result['total'];
    }

    /**
     * Obtém um aluno pelo ID
     *
     * @param int $id ID do aluno
     * @return array|false Dados do aluno ou false se não encontrado
     */
    public function getById($id) {
        $sql = "
            SELECT
                a.*,
                c.nome AS curso_nome,
                p.nome AS polo_nome,
                cid.nome AS cidade_nome,
                e.nome AS estado_nome,
                e.sigla AS estado_sigla
            FROM
                alunos a
                LEFT JOIN cursos c ON a.curso_id = c.id
                LEFT JOIN polos p ON a.polo_id = p.id
                LEFT JOIN cidades cid ON a.cidade_id = cid.id
                LEFT JOIN estados e ON cid.estado_id = e.id
            WHERE
                a.id = ?
        ";

        return $this->db->fetchOne($sql, [$id]);
    }

    /**
     * Obtém um aluno pelo ID legado
     *
     * @param string $idLegado ID legado do aluno
     * @return array|false Dados do aluno ou false se não encontrado
     */
    public function getByLegacyId($idLegado) {
        $sql = "
            SELECT
                a.*,
                c.nome AS curso_nome,
                p.nome AS polo_nome,
                cid.nome AS cidade_nome,
                e.nome AS estado_nome,
                e.sigla AS estado_sigla
            FROM
                alunos a
                LEFT JOIN cursos c ON a.curso_id = c.id
                LEFT JOIN polos p ON a.polo_id = p.id
                LEFT JOIN cidades cid ON a.cidade_id = cid.id
                LEFT JOIN estados e ON cid.estado_id = e.id
            WHERE
                a.id_legado = ?
        ";

        return $this->db->fetchOne($sql, [$idLegado]);
    }

    /**
     * Cria um novo aluno
     *
     * @param array $data Dados do aluno
     * @param string|null $idLegado ID legado do aluno (opcional)
     * @return int ID do aluno criado
     */
    public function create($data, $idLegado = null) {
        // Adiciona o ID legado se fornecido
        if ($idLegado !== null) {
            $data['id_legado'] = $idLegado;
        }

        // Registra log
        Utils::registrarLog(
            'alunos',
            'criar',
            'Criação de novo aluno: ' . $data['nome'],
            null,
            'aluno',
            null,
            $data
        );

        $id = $this->db->insert('alunos', $data);

        // Se tiver ID legado, registra o mapeamento
        if (!empty($data['id_legado'])) {
            $mapper = new LegacyIdMapper();
            $mapper->registerMapping('alunos', $id, $data['id_legado']);
        }

        return $id;
    }

    /**
     * Atualiza um aluno
     *
     * @param int $id ID do aluno
     * @param array $data Dados a serem atualizados
     * @param string|null $idLegado ID legado do aluno (opcional)
     * @return int Número de linhas afetadas
     */
    public function update($id, $data, $idLegado = null) {
        // Adiciona o ID legado se fornecido
        if ($idLegado !== null) {
            $data['id_legado'] = $idLegado;
        }

        // Obtém dados antigos para o log
        $dadosAntigos = $this->getById($id);

        // Registra log
        Utils::registrarLog(
            'alunos',
            'editar',
            'Atualização de aluno: ' . $data['nome'],
            $id,
            'aluno',
            $dadosAntigos,
            $data
        );

        $result = $this->db->update('alunos', $data, 'id = ?', [$id]);

        // Se tiver ID legado, registra o mapeamento
        if (!empty($data['id_legado'])) {
            $mapper = new LegacyIdMapper();
            $mapper->registerMapping('alunos', $id, $data['id_legado']);
        }

        return $result;
    }

    /**
     * Exclui um aluno
     *
     * @param int $id ID do aluno
     * @return int Número de linhas afetadas
     */
    public function delete($id) {
        // Obtém dados para o log
        $aluno = $this->getById($id);

        // Registra log
        Utils::registrarLog(
            'alunos',
            'excluir',
            'Exclusão de aluno: ' . $aluno['nome'],
            $id,
            'aluno',
            $aluno,
            null
        );

        return $this->db->delete('alunos', 'id = ?', [$id]);
    }

    /**
     * Obtém estatísticas de alunos
     *
     * @return array Estatísticas
     */
    public function getEstatisticas() {
        $stats = [];

        // Total de alunos
        $sql = "SELECT COUNT(*) as total FROM alunos";
        $result = $this->db->fetchOne($sql);
        $stats['total'] = $result['total'];

        // Alunos por status
        $sql = "SELECT status, COUNT(*) as total FROM alunos GROUP BY status";
        $stats['por_status'] = $this->db->fetchAll($sql);

        // Alunos por curso
        $sql = "
            SELECT
                c.nome AS curso,
                COUNT(*) as total
            FROM
                alunos a
                JOIN cursos c ON a.curso_id = c.id
            GROUP BY
                a.curso_id
            ORDER BY
                total DESC
        ";
        $stats['por_curso'] = $this->db->fetchAll($sql);

        // Alunos por polo
        $sql = "
            SELECT
                p.nome AS polo,
                COUNT(*) as total
            FROM
                alunos a
                JOIN polos p ON a.polo_id = p.id
            GROUP BY
                a.polo_id
            ORDER BY
                total DESC
        ";
        $stats['por_polo'] = $this->db->fetchAll($sql);

        return $stats;
    }
}
