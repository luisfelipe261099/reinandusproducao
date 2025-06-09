<?php
/**
 * Classe modelo para Matrículas
 */

class Matricula {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Obtém todas as matrículas
     *
     * @param array $filtros Filtros a serem aplicados
     * @return array Lista de matrículas
     */
    public function getAll($filtros = []) {
        $params = [];
        $where = [];

        // Aplica filtros
        if (!empty($filtros['aluno_id'])) {
            $where[] = "m.aluno_id = ?";
            $params[] = $filtros['aluno_id'];
        }

        if (!empty($filtros['curso_id'])) {
            $where[] = "m.curso_id = ?";
            $params[] = $filtros['curso_id'];
        }

        if (!empty($filtros['polo_id'])) {
            $where[] = "m.polo_id = ?";
            $params[] = $filtros['polo_id'];
        }

        if (!empty($filtros['turma_id'])) {
            $where[] = "m.turma_id = ?";
            $params[] = $filtros['turma_id'];
        }

        if (!empty($filtros['status'])) {
            $where[] = "m.status = ?";
            $params[] = $filtros['status'];
        }

        if (!empty($filtros['data_inicio'])) {
            $where[] = "m.data_matricula >= ?";
            $params[] = $filtros['data_inicio'];
        }

        if (!empty($filtros['data_fim'])) {
            $where[] = "m.data_matricula <= ?";
            $params[] = $filtros['data_fim'];
        }

        // Monta a cláusula WHERE
        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

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

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Obtém uma matrícula pelo ID
     *
     * @param int $id ID da matrícula
     * @return array|false Dados da matrícula ou false se não encontrada
     */
    public function getById($id) {
        $sql = "
            SELECT
                m.*,
                a.nome AS aluno_nome,
                a.cpf AS aluno_cpf,
                a.email AS aluno_email,
                c.nome AS curso_nome,
                c.carga_horaria AS curso_carga_horaria,
                p.nome AS polo_nome,
                t.nome AS turma_nome
            FROM
                matriculas m
                JOIN alunos a ON m.aluno_id = a.id
                JOIN cursos c ON m.curso_id = c.id
                LEFT JOIN polos p ON m.polo_id = p.id
                LEFT JOIN turmas t ON m.turma_id = t.id
            WHERE
                m.id = ?
        ";

        return $this->db->fetchOne($sql, [$id]);
    }

    /**
     * Cria uma nova matrícula
     *
     * @param array $data Dados da matrícula
     * @return int ID da matrícula criada
     */
    public function create($data) {
        // Inicia transação
        $this->db->beginTransaction();

        try {
            // Insere a matrícula
            $matriculaId = $this->db->insert('matriculas', $data);

            // Se houver turma, incrementa o contador de vagas preenchidas
            if (!empty($data['turma_id'])) {
                $this->db->query(
                    "UPDATE turmas SET vagas_preenchidas = vagas_preenchidas + 1 WHERE id = ?",
                    [$data['turma_id']]
                );
            }

            // Obtém as disciplinas do curso
            $disciplinas = $this->db->fetchAll(
                "SELECT id FROM disciplinas WHERE curso_id = ? AND status = 'ativo'",
                [$data['curso_id']]
            );

            // Cria registros de notas para cada disciplina
            foreach ($disciplinas as $disciplina) {
                $this->db->insert('notas_disciplinas', [
                    'matricula_id' => $matriculaId,
                    'disciplina_id' => $disciplina['id'],
                    'situacao' => 'cursando',
                    'data_lancamento' => date('Y-m-d')
                ]);
            }

            // Registra log
            Utils::registrarLog(
                'matriculas',
                'criar',
                'Nova matrícula criada para o aluno ID ' . $data['aluno_id'],
                $matriculaId,
                'matricula',
                null,
                $data
            );

            // Confirma a transação
            $this->db->commit();

            return $matriculaId;
        } catch (Exception $e) {
            // Reverte a transação em caso de erro
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Atualiza uma matrícula
     *
     * @param int $id ID da matrícula
     * @param array $data Dados a serem atualizados
     * @return int Número de linhas afetadas
     */
    public function update($id, $data) {
        // Obtém dados antigos para o log
        $dadosAntigos = $this->getById($id);

        // Inicia transação
        $this->db->beginTransaction();

        try {
            // Se a turma foi alterada, atualiza os contadores
            if (isset($data['turma_id']) && $data['turma_id'] != $dadosAntigos['turma_id']) {
                // Decrementa o contador da turma antiga
                if (!empty($dadosAntigos['turma_id'])) {
                    $this->db->query(
                        "UPDATE turmas SET vagas_preenchidas = vagas_preenchidas - 1 WHERE id = ?",
                        [$dadosAntigos['turma_id']]
                    );
                }

                // Incrementa o contador da nova turma
                if (!empty($data['turma_id'])) {
                    $this->db->query(
                        "UPDATE turmas SET vagas_preenchidas = vagas_preenchidas + 1 WHERE id = ?",
                        [$data['turma_id']]
                    );
                }
            }

            // Atualiza a matrícula
            $result = $this->db->update('matriculas', $data, 'id = ?', [$id]);

            // Registra log
            Utils::registrarLog(
                'matriculas',
                'editar',
                'Atualização de matrícula ID ' . $id,
                $id,
                'matricula',
                $dadosAntigos,
                $data
            );

            // Confirma a transação
            $this->db->commit();

            return $result;
        } catch (Exception $e) {
            // Reverte a transação em caso de erro
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Cancela uma matrícula
     *
     * @param int $id ID da matrícula
     * @param string $motivo Motivo do cancelamento
     * @return int Número de linhas afetadas
     */
    public function cancelar($id, $motivo) {
        // Obtém dados para o log
        $matricula = $this->getById($id);

        // Inicia transação
        $this->db->beginTransaction();

        try {
            // Atualiza o status da matrícula
            $data = [
                'status' => 'cancelado',
                'observacoes' => $motivo
            ];

            $result = $this->db->update('matriculas', $data, 'id = ?', [$id]);

            // Se houver turma, decrementa o contador de vagas preenchidas
            if (!empty($matricula['turma_id'])) {
                $this->db->query(
                    "UPDATE turmas SET vagas_preenchidas = vagas_preenchidas - 1 WHERE id = ?",
                    [$matricula['turma_id']]
                );
            }

            // Registra log
            Utils::registrarLog(
                'matriculas',
                'cancelar',
                'Cancelamento de matrícula ID ' . $id . '. Motivo: ' . $motivo,
                $id,
                'matricula',
                $matricula,
                $data
            );

            // Confirma a transação
            $this->db->commit();

            return $result;
        } catch (Exception $e) {
            // Reverte a transação em caso de erro
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Obtém as notas de uma matrícula
     *
     * @param int $matriculaId ID da matrícula
     * @return array Lista de notas
     */
    public function getNotas($matriculaId) {
        $sql = "
            SELECT
                nd.*,
                d.nome AS disciplina_nome,
                d.codigo AS disciplina_codigo,
                d.carga_horaria AS disciplina_carga_horaria
            FROM
                notas_disciplinas nd
                JOIN disciplinas d ON nd.disciplina_id = d.id
            WHERE
                nd.matricula_id = ?
            ORDER BY
                d.nome
        ";

        return $this->db->fetchAll($sql, [$matriculaId]);
    }

    /**
     * Calcula o desempenho acadêmico de uma matrícula
     *
     * @param int $matriculaId ID da matrícula
     * @return array Dados de desempenho
     */
    public function calcularDesempenho($matriculaId) {
        $desempenho = [];

        // Obtém as notas
        $notas = $this->getNotas($matriculaId);

        // Inicializa contadores
        $totalDisciplinas = count($notas);
        $disciplinasCursadas = 0;
        $disciplinasAprovadas = 0;
        $disciplinasReprovadas = 0;
        $somaNotas = 0;
        $somaCargaHoraria = 0;
        $cargaHorariaConcluida = 0;

        // Calcula estatísticas
        foreach ($notas as $nota) {
            if ($nota['situacao'] != 'cursando') {
                $disciplinasCursadas++;
                $somaNotas += $nota['nota'];

                if ($nota['situacao'] == 'aprovado') {
                    $disciplinasAprovadas++;
                    $cargaHorariaConcluida += $nota['disciplina_carga_horaria'];
                } else {
                    $disciplinasReprovadas++;
                }
            }

            $somaCargaHoraria += $nota['disciplina_carga_horaria'];
        }

        // Calcula médias e percentuais
        $desempenho['total_disciplinas'] = $totalDisciplinas;
        $desempenho['disciplinas_cursadas'] = $disciplinasCursadas;
        $desempenho['disciplinas_aprovadas'] = $disciplinasAprovadas;
        $desempenho['disciplinas_reprovadas'] = $disciplinasReprovadas;
        $desempenho['media_geral'] = $disciplinasCursadas > 0 ? $somaNotas / $disciplinasCursadas : 0;
        $desempenho['percentual_aprovacao'] = $disciplinasCursadas > 0 ? ($disciplinasAprovadas / $disciplinasCursadas) * 100 : 0;
        $desempenho['percentual_conclusao'] = $somaCargaHoraria > 0 ? ($cargaHorariaConcluida / $somaCargaHoraria) * 100 : 0;
        $desempenho['carga_horaria_total'] = $somaCargaHoraria;
        $desempenho['carga_horaria_concluida'] = $cargaHorariaConcluida;

        return $desempenho;
    }
}
