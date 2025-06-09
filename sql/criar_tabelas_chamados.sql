-- Tabela de chamados
CREATE TABLE chamados (
    id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    tipo VARCHAR(50) NOT NULL, -- 'documento' para este caso
    subtipo VARCHAR(50) NOT NULL, -- 'declaracao', 'historico', 'certificado', 'diploma'
    polo_id INT(10) UNSIGNED NULL,
    aberto_por INT(11) UNSIGNED NOT NULL, -- ID do usuário que abriu o chamado
    status ENUM('aberto', 'em_andamento', 'concluido', 'cancelado') NOT NULL DEFAULT 'aberto',
    data_abertura DATETIME NOT NULL,
    data_atualizacao DATETIME NOT NULL,
    observacoes TEXT NULL,
    PRIMARY KEY (id),
    KEY idx_polo (polo_id),
    KEY idx_aberto_por (aberto_por),
    KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de alunos relacionados ao chamado
CREATE TABLE chamados_alunos (
    id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    chamado_id INT(11) UNSIGNED NOT NULL,
    aluno_id INT(11) UNSIGNED NOT NULL,
    documento_gerado TINYINT(1) NOT NULL DEFAULT 0,
    arquivo_path VARCHAR(255) NULL,
    data_geracao DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_chamado_aluno (chamado_id, aluno_id),
    KEY idx_chamado (chamado_id),
    KEY idx_aluno (aluno_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de histórico de chamados
CREATE TABLE chamados_historico (
    id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    chamado_id INT(11) UNSIGNED NOT NULL,
    usuario_id INT(11) UNSIGNED NOT NULL,
    acao VARCHAR(50) NOT NULL,
    descricao TEXT NOT NULL,
    data_hora DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_chamado (chamado_id),
    KEY idx_usuario (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Adiciona chaves estrangeiras
ALTER TABLE chamados
    ADD CONSTRAINT fk_chamados_polo FOREIGN KEY (polo_id) REFERENCES polos (id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_chamados_usuario FOREIGN KEY (aberto_por) REFERENCES usuarios (id) ON DELETE CASCADE;

ALTER TABLE chamados_alunos
    ADD CONSTRAINT fk_chamados_alunos_chamado FOREIGN KEY (chamado_id) REFERENCES chamados (id) ON DELETE CASCADE,
    ADD CONSTRAINT fk_chamados_alunos_aluno FOREIGN KEY (aluno_id) REFERENCES alunos (id) ON DELETE CASCADE;

ALTER TABLE chamados_historico
    ADD CONSTRAINT fk_chamados_historico_chamado FOREIGN KEY (chamado_id) REFERENCES chamados (id) ON DELETE CASCADE,
    ADD CONSTRAINT fk_chamados_historico_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE;
