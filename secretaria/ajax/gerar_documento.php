<?php
// Inicializa o sistema
require_once __DIR__ . '/../includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica as permissões do usuário
if (!Auth::hasPermission('documentos', 'criar')) {
    echo json_encode([
        'success' => false,
        'message' => 'Você não tem permissão para gerar documentos.'
    ]);
    exit;
}

// Verifica se os parâmetros necessários foram enviados
if (!isset($_POST['aluno_id']) || !isset($_POST['tipo_documento_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Parâmetros inválidos.'
    ]);
    exit;
}

// Obtém os parâmetros
$aluno_id = (int)$_POST['aluno_id'];
$matricula_id = isset($_POST['matricula_id']) && !empty($_POST['matricula_id']) ? (int)$_POST['matricula_id'] : null;
$tipo_documento_id = (int)$_POST['tipo_documento_id'];
$data_emissao = isset($_POST['data_emissao']) && !empty($_POST['data_emissao']) ? $_POST['data_emissao'] : date('Y-m-d');

// Instancia o banco de dados
$db = Database::getInstance();

// Verifica se o aluno existe
$sql = "SELECT * FROM alunos WHERE id = ?";
$aluno = $db->fetchOne($sql, [$aluno_id]);

if (!$aluno) {
    echo json_encode([
        'success' => false,
        'message' => 'Aluno não encontrado.'
    ]);
    exit;
}

// Se não foi informada uma matrícula, busca a matrícula do aluno (qualquer status)
if (!$matricula_id) {
    // Busca diretamente a matrícula mais recente do aluno
    $sql = "SELECT id FROM matriculas WHERE aluno_id = ? ORDER BY id DESC LIMIT 1";
    $matricula_result = $db->fetchOne($sql, [$aluno_id]);

    if ($matricula_result) {
        $matricula_id = $matricula_result['id'];
        error_log('Encontrada matrícula ID: ' . $matricula_id . ' para o aluno ID: ' . $aluno_id);
    } else {
        error_log('Nenhuma matrícula encontrada para o aluno ID: ' . $aluno_id);
    }

    // Debug para o aluno Michel Souza Silva (ID 24346)
    if ($aluno_id == 24346) {
        $sql_debug = "SELECT * FROM matriculas WHERE aluno_id = 24346";
        $matriculas_michel = $db->fetchAll($sql_debug);
        error_log('Matrículas do Michel (ajax): ' . json_encode($matriculas_michel));
    }
}

// Verifica se o tipo de documento existe
$sql = "SELECT * FROM tipos_documentos WHERE id = ?";
$tipo_documento = $db->fetchOne($sql, [$tipo_documento_id]);

if (!$tipo_documento) {
    echo json_encode([
        'success' => false,
        'message' => 'Tipo de documento não encontrado.'
    ]);
    exit;
}

// Busca informações da matrícula, curso e polo - abordagem direta
$sql = "SELECT m.*, c.nome as curso_nome, p.nome as polo_nome, t.nome as turma_nome
        FROM matriculas m
        LEFT JOIN cursos c ON m.curso_id = c.id
        LEFT JOIN polos p ON m.polo_id = p.id
        LEFT JOIN turmas t ON m.turma_id = t.id
        WHERE m.id = ?";
$matricula = $db->fetchOne($sql, [$matricula_id]);

// Debug para verificar os dados da matrícula
error_log('Matricula ID ' . $matricula_id . ': ' . json_encode($matricula));

// Se não encontrou a matrícula, tenta buscar diretamente pelo aluno_id
if (!$matricula && $aluno_id) {
    $sql = "SELECT m.*, c.nome as curso_nome, p.nome as polo_nome, t.nome as turma_nome
            FROM matriculas m
            LEFT JOIN cursos c ON m.curso_id = c.id
            LEFT JOIN polos p ON m.polo_id = p.id
            LEFT JOIN turmas t ON m.turma_id = t.id
            WHERE m.aluno_id = ?
            ORDER BY m.id DESC
            LIMIT 1";
    $matricula = $db->fetchOne($sql, [$aluno_id]);
    error_log('Matricula pelo aluno_id ' . $aluno_id . ': ' . json_encode($matricula));

    if ($matricula) {
        $matricula_id = $matricula['id'];
    }
}

// Consulta especial para o Michel (ID 24346)
if ($aluno_id == 24346) {
    $sql_michel = "SELECT m.*, c.nome as curso_nome, p.nome as polo_nome, t.nome as turma_nome
                  FROM matriculas m
                  LEFT JOIN cursos c ON m.curso_id = c.id
                  LEFT JOIN polos p ON m.polo_id = p.id
                  LEFT JOIN turmas t ON m.turma_id = t.id
                  WHERE m.aluno_id = 24346
                  ORDER BY m.id DESC
                  LIMIT 1";
    $matricula_michel = $db->fetchOne($sql_michel);
    error_log('Matricula Michel (consulta especial): ' . json_encode($matricula_michel));

    if ($matricula_michel && !$matricula) {
        $matricula = $matricula_michel;
        $matricula_id = $matricula['id'];
    }
}

// Se não encontrou a matrícula, retorna erro
if (!$matricula && $matricula_id) {
    echo json_encode([
        'success' => false,
        'message' => 'Matrícula não encontrada.'
    ]);
    exit;
}

// Verifica se o polo tem limite de documentos
if ($matricula && isset($matricula['polo_id'])) {
    $sql = "SELECT limite_documentos, documentos_emitidos FROM polos WHERE id = ?";
    $polo = $db->fetchOne($sql, [$matricula['polo_id']]);

    if ($polo && $polo['limite_documentos'] > 0 && $polo['documentos_emitidos'] >= $polo['limite_documentos']) {
        echo json_encode([
            'success' => false,
            'message' => 'O polo atingiu o limite de documentos.'
        ]);
        exit;
    }
}

// Prepara os dados do documento
$dados_documento = [];

// Dados comuns para todos os tipos de documentos
$dados_documento['aluno_nome'] = $aluno['nome'];
$dados_documento['aluno_cpf'] = $aluno['cpf'];
$dados_documento['data_emissao'] = date('d/m/Y', strtotime($data_emissao));
$dados_documento['codigo_verificacao'] = strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
$dados_documento['instituicao'] = 'Faciência';
$dados_documento['instituicao_info'] = 'CNPJ: XX.XXX.XXX/0001-XX';
$dados_documento['cidade'] = 'São Paulo';
$dados_documento['responsavel'] = 'Secretaria Acadêmica';
$dados_documento['cargo_responsavel'] = 'Secretário(a) Acadêmico(a)';

// Adiciona dados da matrícula, se existir
if ($matricula) {
    error_log('Usando dados da matrícula: ' . json_encode($matricula));
    $dados_documento['matricula_numero'] = 'ID: ' . $matricula['id'];
    $dados_documento['curso_nome'] = $matricula['curso_nome'] ?? 'N/A';
    $dados_documento['polo_nome'] = $matricula['polo_nome'] ?? 'N/A';
    $dados_documento['turma_nome'] = $matricula['turma_nome'] ?? 'N/A';
    $dados_documento['data_inicio'] = isset($matricula['data_matricula']) ? date('d/m/Y', strtotime($matricula['data_matricula'])) : 'N/A';
    $dados_documento['situacao'] = ucfirst($matricula['status'] ?? 'ativo');

    // Para declaração de matrícula
    if (strpos(strtolower($tipo_documento['nome']), 'declaração') !== false) {
        // Calcula a data prevista de término (1 ano após o início)
        if (isset($matricula['data_matricula'])) {
            $data_inicio = new DateTime($matricula['data_matricula']);
            $data_previsao_termino = clone $data_inicio;
            $data_previsao_termino->add(new DateInterval('P1Y'));
            $dados_documento['data_previsao_termino'] = $data_previsao_termino->format('d/m/Y');
        } else {
            $dados_documento['data_previsao_termino'] = 'N/A';
        }
    }

    // Para histórico escolar
    if (strpos(strtolower($tipo_documento['nome']), 'histórico') !== false) {
        // Busca as disciplinas e notas do aluno
        $sql = "SELECT d.nome as disciplina_nome, d.carga_horaria, nd.nota, nd.frequencia, nd.situacao
                FROM notas_disciplinas nd
                JOIN disciplinas d ON nd.disciplina_id = d.id
                WHERE nd.matricula_id = ?
                ORDER BY d.nome ASC";
        $disciplinas = $db->fetchAll($sql, [$matricula_id]);

        // Formata as disciplinas para o histórico
        $html_disciplinas = '';
        foreach ($disciplinas as $disciplina) {
            $html_disciplinas .= '<tr>';
            $html_disciplinas .= '<td>' . htmlspecialchars($disciplina['disciplina_nome']) . '</td>';
            $html_disciplinas .= '<td>' . htmlspecialchars($disciplina['carga_horaria']) . '</td>';
            $html_disciplinas .= '<td>' . htmlspecialchars($disciplina['nota'] ?? '-') . '</td>';
            $html_disciplinas .= '<td>' . htmlspecialchars($disciplina['frequencia'] ?? '-') . '%</td>';
            $html_disciplinas .= '<td>' . htmlspecialchars(ucfirst($disciplina['situacao'] ?? '-')) . '</td>';
            $html_disciplinas .= '</tr>';
        }

        $dados_documento['disciplinas'] = $html_disciplinas;
    }
} else {
    error_log('Matrícula não encontrada para o aluno ID: ' . $aluno_id);

    // Busca informações do curso, polo e turma diretamente
    if ($matricula_id) {
        $sql = "SELECT curso_id, polo_id, turma_id FROM matriculas WHERE id = ?";
        $matricula_info = $db->fetchOne($sql, [$matricula_id]);
        error_log('Informações da matrícula: ' . json_encode($matricula_info));

        if ($matricula_info) {
            // Busca informações do curso
            if (!empty($matricula_info['curso_id'])) {
                $sql = "SELECT nome FROM cursos WHERE id = ?";
                $curso = $db->fetchOne($sql, [$matricula_info['curso_id']]);
                $dados_documento['curso_nome'] = $curso ? $curso['nome'] : 'N/A';
            }

            // Busca informações do polo
            if (!empty($matricula_info['polo_id'])) {
                $sql = "SELECT nome FROM polos WHERE id = ?";
                $polo = $db->fetchOne($sql, [$matricula_info['polo_id']]);
                $dados_documento['polo_nome'] = $polo ? $polo['nome'] : 'N/A';
            }

            // Busca informações da turma
            if (!empty($matricula_info['turma_id'])) {
                $sql = "SELECT nome FROM turmas WHERE id = ?";
                $turma = $db->fetchOne($sql, [$matricula_info['turma_id']]);
                $dados_documento['turma_nome'] = $turma ? $turma['nome'] : 'N/A';
            }
        }
    }
}

// Gera o documento
try {
    // Inclui a função de geração de PDF
    error_log('Iniciando geração de documento');
    error_log('Caminho para gerar_pdf.php: ' . __DIR__ . '/../views/documentos/gerar_pdf.php');

    if (!file_exists(__DIR__ . '/../views/documentos/gerar_pdf.php')) {
        error_log('ERRO: Arquivo gerar_pdf.php não encontrado');
        throw new Exception("Arquivo de geração de PDF não encontrado.");
    }

    require_once __DIR__ . '/../views/documentos/gerar_pdf.php';
    error_log('Arquivo gerar_pdf.php incluído com sucesso');

    // Determina o tipo de documento
    $tipo = strpos(strtolower($tipo_documento['nome']), 'histórico') !== false ? 'historico' : 'declaracao';
    error_log('Tipo de documento: ' . $tipo);
    error_log('Dados do documento: ' . json_encode($dados_documento));

    // Verifica se a função gerarPDF existe
    if (!function_exists('gerarPDF')) {
        error_log('ERRO: Função gerarPDF não encontrada');
        throw new Exception("Função de geração de PDF não encontrada.");
    }

    // Gera o documento HTML
    try {
        error_log('Chamando função gerarPDF');
        $arquivo_html = gerarPDF(['dados_documento' => $dados_documento], $tipo);
        error_log('Arquivo HTML gerado: ' . $arquivo_html);

        // Verifica se o arquivo foi realmente criado
        $caminho_base = __DIR__ . '/../';
        $caminho_absoluto = $caminho_base . $arquivo_html;
        error_log('Verificando arquivo em: ' . $caminho_absoluto);

        if (!file_exists($caminho_absoluto)) {
            error_log('ERRO: Arquivo HTML não foi criado: ' . $caminho_absoluto);
            throw new Exception("O arquivo do documento não foi criado corretamente.");
        } else {
            error_log('Arquivo encontrado em: ' . $caminho_absoluto);
            // Mantemos o caminho relativo para o banco de dados
            // NÃO substituir $arquivo_html pelo caminho absoluto!
        }
    } catch (Exception $e) {
        error_log('ERRO ao gerar PDF: ' . $e->getMessage());
        error_log('Stack trace: ' . $e->getTraceAsString());
        throw $e;
    }

    // Registra o documento no banco de dados com base na estrutura correta da tabela
    try {
        // Prepara os dados para inserção com base na estrutura conhecida da tabela
        $dados_insercao = [
            'aluno_id' => $aluno_id,
            'matricula_id' => $matricula_id,
            'curso_id' => $matricula['curso_id'] ?? null,
            'polo_id' => $matricula['polo_id'] ?? null,
            'tipo_documento_id' => $tipo_documento_id,
            'data_emissao' => $data_emissao,
            'data_validade' => date('Y-m-d', strtotime('+30 days', strtotime($data_emissao))),
            'codigo_verificacao' => intval(substr($dados_documento['codigo_verificacao'], 0, 8)), // Converte para inteiro
            'arquivo' => $arquivo_html,
            'status' => 'ativo'
        ];

        // Verifica se precisamos adicionar a coluna solicitacao_id (obrigatória)
        if (!isset($dados_insercao['solicitacao_id'])) {
            // Abordagem simplificada: verifica se existe alguma solicitação na tabela
            try {
                // Primeiro verifica se existe alguma solicitação na tabela
                $sql_any = "SELECT id FROM solicitacoes_documentos LIMIT 1";
                $any_solicitacao = $db->fetchOne($sql_any);

                if ($any_solicitacao) {
                    // Usa a primeira solicitação encontrada
                    $solicitacao_id = $any_solicitacao['id'];
                    error_log('Usando solicitação existente ID: ' . $solicitacao_id);
                } else {
                    // Tenta criar uma solicitação simples
                    try {
                        // Primeiro, encontra um polo_id válido
                        $sql_polo = "SELECT id FROM polos LIMIT 1";
                        $polo = $db->fetchOne($sql_polo);

                        if (!$polo) {
                            throw new Exception('Não foi possível encontrar um polo válido');
                        }

                        // Insere a solicitação com o polo_id válido
                        $solicitacao_id = $db->insert('solicitacoes_documentos', [
                            'aluno_id' => $aluno_id,
                            'tipo_documento_id' => $tipo_documento_id,
                            'polo_id' => $polo['id'],
                            'status' => 'concluido'
                        ]);
                        error_log('Nova solicitação criada com ID: ' . $solicitacao_id);
                    } catch (Exception $e) {
                        error_log('ERRO ao inserir solicitação: ' . $e->getMessage());
                        throw new Exception('Não foi possível criar uma solicitação: ' . $e->getMessage());
                    }
                }
            } catch (Exception $e) {
                error_log('ERRO ao verificar/inserir solicitação: ' . $e->getMessage());
                throw new Exception('Não foi possível criar ou encontrar uma solicitação válida: ' . $e->getMessage());
            }

            $dados_insercao['solicitacao_id'] = $solicitacao_id;
        }

        error_log('Dados para inserção: ' . json_encode($dados_insercao));

        // Registra o documento na tabela documentos_emitidos
        $documento_emitido_id = $db->insert('documentos_emitidos', $dados_insercao);
        error_log('Documento emitido inserido com ID: ' . $documento_emitido_id);

        // Agora, registra o mesmo documento na tabela documentos
        try {
            // Prepara os dados para inserção na tabela documentos
            $dados_documento = [
                'titulo' => $tipo_documento['nome'] . ' - ' . $aluno['nome'],
                'tipo_documento_id' => $tipo_documento_id,
                'aluno_id' => $aluno_id,
                'numero' => $dados_documento['codigo_verificacao'],
                'data_emissao' => $data_emissao,
                'data_validade' => date('Y-m-d', strtotime('+30 days', strtotime($data_emissao))),
                'orgao_emissor' => 'Faciência',
                'observacoes' => 'Documento gerado automaticamente pelo sistema',
                'arquivo_path' => $arquivo_html,
                'arquivo_nome' => basename($arquivo_html),
                'arquivo_tipo' => 'text/html',
                'status' => 'ativo',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            // Insere na tabela documentos
            $documento_id = $db->insert('documentos', $dados_documento);
            error_log('Documento inserido na tabela documentos com ID: ' . $documento_id);
        } catch (Exception $e) {
            error_log('ERRO ao inserir na tabela documentos: ' . $e->getMessage());
            // Não interrompe o processo se falhar, já que o documento foi registrado em documentos_emitidos
        }
    } catch (Exception $e) {
        error_log('ERRO ao inserir documento: ' . $e->getMessage());
        throw new Exception('Erro ao inserir documento no banco de dados: ' . $e->getMessage());
    }

    // Atualiza o contador de documentos do polo
    try {
        // Atualiza o contador no polo
        if ($matricula && isset($matricula['polo_id'])) {
            $sql = "UPDATE polos SET documentos_emitidos = documentos_emitidos + 1, updated_at = NOW() WHERE id = ?";
            $db->query($sql, [$matricula['polo_id']]);
            error_log('Contador de documentos atualizado para o polo ID: ' . $matricula['polo_id']);
        } else if (isset($dados_insercao['polo_id']) && $dados_insercao['polo_id']) {
            $sql = "UPDATE polos SET documentos_emitidos = documentos_emitidos + 1, updated_at = NOW() WHERE id = ?";
            $db->query($sql, [$dados_insercao['polo_id']]);
            error_log('Contador de documentos atualizado para o polo ID: ' . $dados_insercao['polo_id']);
        }

        // Verifica se a tabela estatisticas existe
        try {
            $sql = "SHOW TABLES LIKE 'estatisticas'";
            $tabela_existe = $db->fetchOne($sql);

            if ($tabela_existe) {
                // Verifica se já existe um registro para documentos_emitidos
                $sql = "SELECT COUNT(*) as total FROM estatisticas WHERE chave = 'documentos_emitidos'";
                $resultado = $db->fetchOne($sql);

                if ($resultado && $resultado['total'] > 0) {
                    // Atualiza o contador existente
                    $sql = "UPDATE estatisticas SET valor = valor + 1, updated_at = NOW() WHERE chave = 'documentos_emitidos'";
                    $db->query($sql);
                } else {
                    // Cria um novo registro
                    $sql = "INSERT INTO estatisticas (chave, valor, created_at, updated_at)
                            VALUES ('documentos_emitidos', 1, NOW(), NOW())";
                    $db->query($sql);
                }

                error_log('Estatísticas de documentos atualizadas');
            } else {
                error_log('Tabela estatisticas não existe, pulando atualização de estatísticas');
            }
        } catch (Exception $e) {
            error_log('Erro ao atualizar estatísticas: ' . $e->getMessage());
        }
    } catch (Exception $e) {
        // Apenas loga o erro, mas não interrompe o processo
        error_log('ERRO ao atualizar contadores: ' . $e->getMessage());
    }

    // Retorna sucesso
    echo json_encode([
        'success' => true,
        'message' => 'Documento gerado com sucesso.',
        'documento_emitido_id' => $documento_emitido_id,
        'documento_id' => $documento_id ?? null,
        'arquivo_path' => $arquivo_html,
        'visualizar_url' => 'documentos.php?action=visualizar&id=' . $documento_emitido_id,
        'download_url' => 'documentos.php?action=download&id=' . $documento_emitido_id . '&tipo=emitido'
    ]);
} catch (Exception $e) {
    // Log detalhado do erro
    error_log('ERRO ao gerar documento: ' . $e->getMessage());
    error_log('Trace: ' . $e->getTraceAsString());

    // Retorna erro
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao gerar o documento: ' . $e->getMessage()
    ]);
}
