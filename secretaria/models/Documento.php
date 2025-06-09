<?php
/**
 * Classe modelo para Documentos
 */

class Documento {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Obtém todos os tipos de documentos
     * 
     * @return array Lista de tipos de documentos
     */
    public function getTiposDocumentos() {
        $sql = "SELECT * FROM tipos_documentos ORDER BY nome";
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Obtém um tipo de documento pelo ID
     * 
     * @param int $id ID do tipo de documento
     * @return array|false Dados do tipo de documento ou false se não encontrado
     */
    public function getTipoDocumentoById($id) {
        $sql = "SELECT * FROM tipos_documentos WHERE id = ?";
        return $this->db->fetchOne($sql, [$id]);
    }
    
    /**
     * Obtém todas as solicitações de documentos
     * 
     * @param array $filtros Filtros a serem aplicados
     * @return array Lista de solicitações
     */
    public function getSolicitacoes($filtros = []) {
        $params = [];
        $where = [];
        
        // Aplica filtros
        if (!empty($filtros['aluno_id'])) {
            $where[] = "s.aluno_id = ?";
            $params[] = $filtros['aluno_id'];
        }
        
        if (!empty($filtros['polo_id'])) {
            $where[] = "s.polo_id = ?";
            $params[] = $filtros['polo_id'];
        }
        
        if (!empty($filtros['tipo_documento_id'])) {
            $where[] = "s.tipo_documento_id = ?";
            $params[] = $filtros['tipo_documento_id'];
        }
        
        if (!empty($filtros['status'])) {
            $where[] = "s.status = ?";
            $params[] = $filtros['status'];
        }
        
        if (!empty($filtros['pago'])) {
            $where[] = "s.pago = ?";
            $params[] = $filtros['pago'];
        }
        
        if (!empty($filtros['data_inicio'])) {
            $where[] = "s.created_at >= ?";
            $params[] = $filtros['data_inicio'] . ' 00:00:00';
        }
        
        if (!empty($filtros['data_fim'])) {
            $where[] = "s.created_at <= ?";
            $params[] = $filtros['data_fim'] . ' 23:59:59';
        }
        
        // Monta a cláusula WHERE
        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        
        // Consulta SQL
        $sql = "
            SELECT 
                s.*,
                a.nome AS aluno_nome,
                a.cpf AS aluno_cpf,
                p.nome AS polo_nome,
                t.nome AS tipo_documento_nome
            FROM 
                solicitacoes_documentos s
                JOIN alunos a ON s.aluno_id = a.id
                JOIN polos p ON s.polo_id = p.id
                JOIN tipos_documentos t ON s.tipo_documento_id = t.id
            {$whereClause}
            ORDER BY s.created_at DESC
        ";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Obtém uma solicitação de documento pelo ID
     * 
     * @param int $id ID da solicitação
     * @return array|false Dados da solicitação ou false se não encontrada
     */
    public function getSolicitacaoById($id) {
        $sql = "
            SELECT 
                s.*,
                a.nome AS aluno_nome,
                a.cpf AS aluno_cpf,
                a.email AS aluno_email,
                p.nome AS polo_nome,
                t.nome AS tipo_documento_nome,
                t.valor AS tipo_documento_valor
            FROM 
                solicitacoes_documentos s
                JOIN alunos a ON s.aluno_id = a.id
                JOIN polos p ON s.polo_id = p.id
                JOIN tipos_documentos t ON s.tipo_documento_id = t.id
            WHERE 
                s.id = ?
        ";
        
        return $this->db->fetchOne($sql, [$id]);
    }
    
    /**
     * Cria uma nova solicitação de documento
     * 
     * @param array $data Dados da solicitação
     * @return int ID da solicitação criada
     */
    public function criarSolicitacao($data) {
        // Obtém o valor do tipo de documento
        $tipoDocumento = $this->getTipoDocumentoById($data['tipo_documento_id']);
        
        // Calcula o valor total
        $valorTotal = $tipoDocumento['valor'] * $data['quantidade'];
        $data['valor_total'] = $valorTotal;
        
        // Registra log
        Utils::registrarLog(
            'documentos',
            'solicitar',
            'Nova solicitação de documento tipo ' . $tipoDocumento['nome'],
            null,
            'solicitacao_documento',
            null,
            $data
        );
        
        return $this->db->insert('solicitacoes_documentos', $data);
    }
    
    /**
     * Atualiza o status de uma solicitação
     * 
     * @param int $id ID da solicitação
     * @param string $status Novo status
     * @return int Número de linhas afetadas
     */
    public function atualizarStatusSolicitacao($id, $status) {
        // Obtém dados antigos para o log
        $dadosAntigos = $this->getSolicitacaoById($id);
        
        $data = [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Registra log
        Utils::registrarLog(
            'documentos',
            'atualizar_status',
            'Atualização de status de solicitação para ' . $status,
            $id,
            'solicitacao_documento',
            $dadosAntigos,
            $data
        );
        
        return $this->db->update('solicitacoes_documentos', $data, 'id = ?', [$id]);
    }
    
    /**
     * Marca uma solicitação como paga
     * 
     * @param int $id ID da solicitação
     * @return int Número de linhas afetadas
     */
    public function marcarComoPago($id) {
        // Obtém dados antigos para o log
        $dadosAntigos = $this->getSolicitacaoById($id);
        
        $data = [
            'pago' => 1,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Registra log
        Utils::registrarLog(
            'documentos',
            'marcar_pago',
            'Solicitação de documento marcada como paga',
            $id,
            'solicitacao_documento',
            $dadosAntigos,
            $data
        );
        
        return $this->db->update('solicitacoes_documentos', $data, 'id = ?', [$id]);
    }
    
    /**
     * Obtém todos os documentos emitidos
     * 
     * @param array $filtros Filtros a serem aplicados
     * @return array Lista de documentos emitidos
     */
    public function getDocumentosEmitidos($filtros = []) {
        $params = [];
        $where = [];
        
        // Aplica filtros
        if (!empty($filtros['solicitacao_id'])) {
            $where[] = "d.solicitacao_id = ?";
            $params[] = $filtros['solicitacao_id'];
        }
        
        if (!empty($filtros['aluno_id'])) {
            $where[] = "s.aluno_id = ?";
            $params[] = $filtros['aluno_id'];
        }
        
        if (!empty($filtros['polo_id'])) {
            $where[] = "s.polo_id = ?";
            $params[] = $filtros['polo_id'];
        }
        
        if (!empty($filtros['tipo_documento_id'])) {
            $where[] = "s.tipo_documento_id = ?";
            $params[] = $filtros['tipo_documento_id'];
        }
        
        if (!empty($filtros['status'])) {
            $where[] = "d.status = ?";
            $params[] = $filtros['status'];
        }
        
        if (!empty($filtros['data_inicio'])) {
            $where[] = "d.data_emissao >= ?";
            $params[] = $filtros['data_inicio'];
        }
        
        if (!empty($filtros['data_fim'])) {
            $where[] = "d.data_emissao <= ?";
            $params[] = $filtros['data_fim'];
        }
        
        // Monta a cláusula WHERE
        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        
        // Consulta SQL
        $sql = "
            SELECT 
                d.*,
                s.aluno_id,
                s.polo_id,
                s.tipo_documento_id,
                a.nome AS aluno_nome,
                a.cpf AS aluno_cpf,
                p.nome AS polo_nome,
                t.nome AS tipo_documento_nome
            FROM 
                documentos_emitidos d
                JOIN solicitacoes_documentos s ON d.solicitacao_id = s.id
                JOIN alunos a ON s.aluno_id = a.id
                JOIN polos p ON s.polo_id = p.id
                JOIN tipos_documentos t ON s.tipo_documento_id = t.id
            {$whereClause}
            ORDER BY d.data_emissao DESC
        ";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Obtém um documento emitido pelo ID
     * 
     * @param int $id ID do documento
     * @return array|false Dados do documento ou false se não encontrado
     */
    public function getDocumentoEmitidoById($id) {
        $sql = "
            SELECT 
                d.*,
                s.aluno_id,
                s.polo_id,
                s.tipo_documento_id,
                s.finalidade,
                a.nome AS aluno_nome,
                a.cpf AS aluno_cpf,
                a.email AS aluno_email,
                p.nome AS polo_nome,
                t.nome AS tipo_documento_nome
            FROM 
                documentos_emitidos d
                JOIN solicitacoes_documentos s ON d.solicitacao_id = s.id
                JOIN alunos a ON s.aluno_id = a.id
                JOIN polos p ON s.polo_id = p.id
                JOIN tipos_documentos t ON s.tipo_documento_id = t.id
            WHERE 
                d.id = ?
        ";
        
        return $this->db->fetchOne($sql, [$id]);
    }
    
    /**
     * Emite um novo documento
     * 
     * @param array $data Dados do documento
     * @return int ID do documento emitido
     */
    public function emitirDocumento($data) {
        // Inicia transação
        $this->db->beginTransaction();
        
        try {
            // Insere o documento emitido
            $documentoId = $this->db->insert('documentos_emitidos', $data);
            
            // Atualiza o status da solicitação
            $this->atualizarStatusSolicitacao($data['solicitacao_id'], 'pronto');
            
            // Incrementa o contador de documentos emitidos do polo
            $solicitacao = $this->getSolicitacaoById($data['solicitacao_id']);
            $this->db->query(
                "UPDATE polos SET documentos_emitidos = documentos_emitidos + 1 WHERE id = ?",
                [$solicitacao['polo_id']]
            );
            
            // Registra log
            Utils::registrarLog(
                'documentos',
                'emitir',
                'Emissão de documento para solicitação ID ' . $data['solicitacao_id'],
                $documentoId,
                'documento_emitido',
                null,
                $data
            );
            
            // Confirma a transação
            $this->db->commit();
            
            return $documentoId;
        } catch (Exception $e) {
            // Reverte a transação em caso de erro
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Marca um documento como entregue
     * 
     * @param int $id ID do documento
     * @return int Número de linhas afetadas
     */
    public function marcarComoEntregue($id) {
        // Inicia transação
        $this->db->beginTransaction();
        
        try {
            // Obtém dados do documento
            $documento = $this->getDocumentoEmitidoById($id);
            
            // Atualiza o status do documento
            $result = $this->db->update('documentos_emitidos', [
                'status' => 'entregue'
            ], 'id = ?', [$id]);
            
            // Atualiza o status da solicitação
            $this->atualizarStatusSolicitacao($documento['solicitacao_id'], 'entregue');
            
            // Registra log
            Utils::registrarLog(
                'documentos',
                'entregar',
                'Documento ID ' . $id . ' marcado como entregue',
                $id,
                'documento_emitido',
                $documento,
                ['status' => 'entregue']
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
     * Cancela um documento emitido
     * 
     * @param int $id ID do documento
     * @return int Número de linhas afetadas
     */
    public function cancelarDocumento($id) {
        // Inicia transação
        $this->db->beginTransaction();
        
        try {
            // Obtém dados do documento
            $documento = $this->getDocumentoEmitidoById($id);
            
            // Atualiza o status do documento
            $result = $this->db->update('documentos_emitidos', [
                'status' => 'cancelado'
            ], 'id = ?', [$id]);
            
            // Atualiza o status da solicitação para processando
            $this->atualizarStatusSolicitacao($documento['solicitacao_id'], 'processando');
            
            // Decrementa o contador de documentos emitidos do polo
            $this->db->query(
                "UPDATE polos SET documentos_emitidos = documentos_emitidos - 1 WHERE id = ?",
                [$documento['polo_id']]
            );
            
            // Registra log
            Utils::registrarLog(
                'documentos',
                'cancelar',
                'Documento ID ' . $id . ' cancelado',
                $id,
                'documento_emitido',
                $documento,
                ['status' => 'cancelado']
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
}
