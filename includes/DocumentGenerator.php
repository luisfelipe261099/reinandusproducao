<?php
/**
 * Classe para geração de documentos
 * 
 * Esta classe gerencia a geração de documentos com suporte a IDs legados
 */

class DocumentGenerator {
    private $db;
    private $mapper;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->mapper = new LegacyIdMapper();
    }
    
    /**
     * Gera um documento para um aluno
     * 
     * @param int|string $alunoId ID ou ID legado do aluno
     * @param int $tipoDocumentoId ID do tipo de documento
     * @param array $params Parâmetros adicionais para o documento
     * @return array|false Dados do documento gerado ou false em caso de erro
     */
    public function generateStudentDocument($alunoId, $tipoDocumentoId, $params = []) {
        // Verifica se é um ID legado
        $isLegacyId = !is_numeric($alunoId) || strpos($alunoId, '-') !== false || strlen($alunoId) > 10;
        
        // Obtém o aluno
        $aluno = null;
        $alunoModel = new Aluno();
        
        if ($isLegacyId) {
            $aluno = $alunoModel->getByLegacyId($alunoId);
            if (!$aluno) {
                // Tenta buscar pelo mapeamento
                $currentId = $this->mapper->getCurrentId('alunos', $alunoId);
                if ($currentId) {
                    $aluno = $alunoModel->getById($currentId);
                }
            }
        } else {
            $aluno = $alunoModel->getById($alunoId);
        }
        
        if (!$aluno) {
            return false;
        }
        
        // Obtém o tipo de documento
        $documentoModel = new Documento();
        $tipoDocumento = $documentoModel->getTipoDocumentoById($tipoDocumentoId);
        
        if (!$tipoDocumento) {
            return false;
        }
        
        // Cria a solicitação de documento
        $solicitacaoData = [
            'aluno_id' => $aluno['id'],
            'polo_id' => $aluno['polo_id'],
            'tipo_documento_id' => $tipoDocumentoId,
            'quantidade' => 1,
            'finalidade' => $params['finalidade'] ?? 'Geração automática',
            'status' => 'processando',
            'pago' => $params['pago'] ?? 1,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $solicitacaoId = $documentoModel->criarSolicitacao($solicitacaoData);
        
        if (!$solicitacaoId) {
            return false;
        }
        
        // Gera o documento
        $documentoData = [
            'solicitacao_id' => $solicitacaoId,
            'numero_documento' => $params['numero_documento'] ?? gerarNumeroDocumento(time()),
            'data_emissao' => date('Y-m-d'),
            'status' => 'ativo'
        ];
        
        // Se houver um arquivo, adiciona ao documento
        if (!empty($params['arquivo'])) {
            $documentoData['arquivo'] = $params['arquivo'];
        }
        
        try {
            $documentoId = $documentoModel->emitirDocumento($documentoData);
            
            if ($documentoId) {
                // Obtém os dados do documento emitido
                $documento = $documentoModel->getDocumentoEmitidoById($documentoId);
                
                // Adiciona o ID legado do aluno ao resultado
                $documento['aluno_id_legado'] = $aluno['id_legado'];
                
                return $documento;
            }
        } catch (Exception $e) {
            // Registra o erro
            registrarLog(
                'documentos',
                'erro_geracao',
                'Erro ao gerar documento: ' . $e->getMessage(),
                $solicitacaoId,
                'solicitacao_documento'
            );
        }
        
        return false;
    }
    
    /**
     * Gera um documento de matrícula
     * 
     * @param int|string $matriculaId ID ou ID legado da matrícula
     * @param array $params Parâmetros adicionais para o documento
     * @return array|false Dados do documento gerado ou false em caso de erro
     */
    public function generateEnrollmentDocument($matriculaId, $params = []) {
        // Verifica se é um ID legado
        $isLegacyId = !is_numeric($matriculaId) || strpos($matriculaId, '-') !== false || strlen($matriculaId) > 10;
        
        // Obtém a matrícula
        $matricula = null;
        $matriculaModel = new Matricula();
        
        if ($isLegacyId) {
            // Busca pelo mapeamento
            $currentId = $this->mapper->getCurrentId('matriculas', $matriculaId);
            if ($currentId) {
                $matricula = $matriculaModel->getById($currentId);
            } else {
                // Busca diretamente na tabela
                $sql = "SELECT * FROM matriculas WHERE id_legado = ?";
                $matricula = $this->db->fetchOne($sql, [$matriculaId]);
            }
        } else {
            $matricula = $matriculaModel->getById($matriculaId);
        }
        
        if (!$matricula) {
            return false;
        }
        
        // Obtém o tipo de documento (Declaração de Matrícula)
        $sql = "SELECT id FROM tipos_documentos WHERE nome LIKE '%Declaração de Matrícula%' LIMIT 1";
        $tipoDocumento = $this->db->fetchOne($sql);
        
        if (!$tipoDocumento) {
            // Se não encontrou, usa o primeiro tipo de documento
            $sql = "SELECT id FROM tipos_documentos LIMIT 1";
            $tipoDocumento = $this->db->fetchOne($sql);
            
            if (!$tipoDocumento) {
                return false;
            }
        }
        
        // Gera o documento para o aluno
        return $this->generateStudentDocument($matricula['aluno_id'], $tipoDocumento['id'], [
            'finalidade' => 'Comprovante de Matrícula',
            'pago' => 1,
            'numero_documento' => 'MAT-' . date('Y') . '-' . str_pad($matricula['id'], 6, '0', STR_PAD_LEFT)
        ]);
    }
    
    /**
     * Gera um histórico escolar
     * 
     * @param int|string $alunoId ID ou ID legado do aluno
     * @param array $params Parâmetros adicionais para o documento
     * @return array|false Dados do documento gerado ou false em caso de erro
     */
    public function generateTranscript($alunoId, $params = []) {
        // Verifica se é um ID legado
        $isLegacyId = !is_numeric($alunoId) || strpos($alunoId, '-') !== false || strlen($alunoId) > 10;
        
        // Obtém o aluno
        $aluno = null;
        $alunoModel = new Aluno();
        
        if ($isLegacyId) {
            $aluno = $alunoModel->getByLegacyId($alunoId);
            if (!$aluno) {
                // Tenta buscar pelo mapeamento
                $currentId = $this->mapper->getCurrentId('alunos', $alunoId);
                if ($currentId) {
                    $aluno = $alunoModel->getById($currentId);
                }
            }
        } else {
            $aluno = $alunoModel->getById($alunoId);
        }
        
        if (!$aluno) {
            return false;
        }
        
        // Obtém o tipo de documento (Histórico Escolar)
        $sql = "SELECT id FROM tipos_documentos WHERE nome LIKE '%Histórico%' LIMIT 1";
        $tipoDocumento = $this->db->fetchOne($sql);
        
        if (!$tipoDocumento) {
            // Se não encontrou, usa o primeiro tipo de documento
            $sql = "SELECT id FROM tipos_documentos LIMIT 1";
            $tipoDocumento = $this->db->fetchOne($sql);
            
            if (!$tipoDocumento) {
                return false;
            }
        }
        
        // Gera o documento para o aluno
        return $this->generateStudentDocument($aluno['id'], $tipoDocumento['id'], [
            'finalidade' => 'Histórico Escolar',
            'pago' => 1,
            'numero_documento' => 'HIST-' . date('Y') . '-' . str_pad($aluno['id'], 6, '0', STR_PAD_LEFT)
        ]);
    }
}
