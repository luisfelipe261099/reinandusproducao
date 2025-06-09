-- Tabela para armazenar histórico de documentos emitidos
CREATE TABLE IF NOT EXISTS documentos_emitidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    matricula_id INT NOT NULL,
    tipo ENUM('declaracao', 'historico') NOT NULL,
    data_emissao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    arquivo_path VARCHAR(500) NULL,
    dados_documento TEXT NULL COMMENT 'JSON com dados do documento no momento da emissão',
    usuario_id INT NULL COMMENT 'ID do usuário que emitiu o documento',
    observacoes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Índices
    INDEX idx_matricula_id (matricula_id),
    INDEX idx_tipo (tipo),
    INDEX idx_data_emissao (data_emissao),
    
    -- Chaves estrangeiras
    FOREIGN KEY (matricula_id) REFERENCES matriculas(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Comentários da tabela
ALTER TABLE documentos_emitidos COMMENT = 'Histórico de documentos emitidos para alunos (declarações e históricos escolares)';

-- Inserir alguns dados de exemplo (opcional)
-- INSERT INTO documentos_emitidos (matricula_id, tipo, arquivo_path, dados_documento, observacoes) VALUES
-- (15229, 'declaracao', 'documentos/declaracoes/declaracao_15229_20241201.pdf', '{"aluno_nome":"João Silva","curso":"Técnico em Informática","data_emissao":"2024-12-01"}', 'Declaração emitida para fins de trabalho'),
-- (15229, 'historico', 'documentos/historicos/historico_15229_20241201.pdf', '{"aluno_nome":"João Silva","curso":"Técnico em Informática","disciplinas":[]}', 'Histórico completo do curso');
