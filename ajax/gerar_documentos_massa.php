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
if (!isset($_POST['alunos_ids']) || !isset($_POST['tipo_documento_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Parâmetros inválidos.'
    ]);
    exit;
}

// Obtém os parâmetros
$alunos_ids = explode(',', $_POST['alunos_ids']);
$matriculas_ids = isset($_POST['matriculas_ids']) ? explode(',', $_POST['matriculas_ids']) : [];
$tipo_documento_id = (int)$_POST['tipo_documento_id'];
$data_emissao = isset($_POST['data_emissao']) && !empty($_POST['data_emissao']) ? $_POST['data_emissao'] : date('Y-m-d');

// Instancia o banco de dados
$db = Database::getInstance();

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

// Prepara o diretório para os documentos
$diretorio_upload = 'uploads/documentos/';
if (!is_dir($diretorio_upload)) {
    mkdir($diretorio_upload, 0755, true);
}

// Diretório para o ZIP
$diretorio_zip = 'uploads/documentos/zip/';
if (!is_dir($diretorio_zip)) {
    mkdir($diretorio_zip, 0755, true);
}

// Nome do arquivo ZIP
$nome_zip = 'documentos_' . date('YmdHis') . '.zip';
$caminho_zip = $diretorio_zip . $nome_zip;

// Cria o arquivo ZIP
$zip = new ZipArchive();
if ($zip->open($caminho_zip, ZipArchive::CREATE) !== TRUE) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao criar o arquivo ZIP.'
    ]);
    exit;
}

// Inclui a função de geração de PDF
require_once __DIR__ . '/../views/documentos/gerar_pdf.php';

// Contador de documentos gerados
$documentos_gerados = 0;
$erros = [];

// Processa cada aluno
foreach ($alunos_ids as $index => $aluno_id) {
    $aluno_id = (int)$aluno_id;
    $matricula_id = isset($matriculas_ids[$index]) && !empty($matriculas_ids[$index]) ? (int)$matriculas_ids[$index] : null;

    // Verifica se o aluno existe
    $sql = "SELECT * FROM alunos WHERE id = ?";
    $aluno = $db->fetchOne($sql, [$aluno_id]);

    if (!$aluno) {
        $erros[] = "Aluno ID {$aluno_id} não encontrado.";
        continue;
    }

    // Se não foi informada uma matrícula, busca a matrícula ativa do aluno
    if (!$matricula_id) {
        $sql = "SELECT id FROM matriculas WHERE aluno_id = ? AND status = 'ativo' ORDER BY id DESC LIMIT 1";
        $matricula_result = $db->fetchOne($sql, [$aluno_id]);

        if ($matricula_result) {
            $matricula_id = $matricula_result['id'];
        }
    }

    // Busca informações da matrícula, curso e polo
    $sql = "SELECT m.*, c.nome as curso_nome, p.nome as polo_nome, t.nome as turma_nome
            FROM matriculas m
            LEFT JOIN cursos c ON m.curso_id = c.id
            LEFT JOIN polos p ON m.polo_id = p.id
            LEFT JOIN turmas t ON m.turma_id = t.id
            WHERE m.id = ?";
    $matricula = $db->fetchOne($sql, [$matricula_id]);

    // Se não encontrou a matrícula, pula para o próximo aluno
    if (!$matricula && $matricula_id) {
        $erros[] = "Matrícula ID {$matricula_id} não encontrada para o aluno {$aluno['nome']}.";
        continue;
    }

    // Verifica se o polo tem limite de documentos
    if ($matricula && isset($matricula['polo_id'])) {
        $sql = "SELECT limite_documentos, documentos_emitidos FROM polos WHERE id = ?";
        $polo = $db->fetchOne($sql, [$matricula['polo_id']]);

        if ($polo && $polo['limite_documentos'] > 0 && $polo['documentos_emitidos'] >= $polo['limite_documentos']) {
            $erros[] = "O polo {$matricula['polo_nome']} atingiu o limite de documentos para o aluno {$aluno['nome']}.";
            continue;
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
        $dados_documento['matricula_numero'] = 'ID: ' . $matricula['id'];
        $dados_documento['curso_nome'] = $matricula['curso_nome'];
        $dados_documento['polo_nome'] = $matricula['polo_nome'];
        $dados_documento['turma_nome'] = $matricula['turma_nome'];
        $dados_documento['data_inicio'] = date('d/m/Y', strtotime($matricula['data_matricula']));
        $dados_documento['situacao'] = ucfirst($matricula['status']);

        // Para declaração de matrícula
        if (strpos(strtolower($tipo_documento['nome']), 'declaração') !== false) {
            // Calcula a data prevista de término (1 ano após o início)
            $data_inicio = new DateTime($matricula['data_matricula']);
            $data_previsao_termino = clone $data_inicio;
            $data_previsao_termino->add(new DateInterval('P1Y'));
            $dados_documento['data_previsao_termino'] = $data_previsao_termino->format('d/m/Y');
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
    }

    // Gera o documento
    try {
        // Determina o tipo de documento
        $tipo = strpos(strtolower($tipo_documento['nome']), 'histórico') !== false ? 'historico' : 'declaracao';

        // Gera o documento HTML
        try {
            $arquivo_html = gerarPDF(['dados_documento' => $dados_documento], $tipo);
            error_log('Arquivo HTML gerado: ' . $arquivo_html);

            // Verifica se o arquivo foi realmente criado
            if (!file_exists($arquivo_html)) {
                error_log('ERRO: Arquivo HTML não foi criado: ' . $arquivo_html);
                throw new Exception("O arquivo do documento não foi criado corretamente.");
            }
        } catch (Exception $e) {
            error_log('ERRO ao gerar PDF: ' . $e->getMessage());
            throw $e;
        }

        // Verifica a estrutura da tabela documentos_emitidos
        try {
            $colunas = $db->fetchAll("SHOW COLUMNS FROM documentos_emitidos");
            $colunas_nomes = array_column($colunas, 'Field');
            error_log('Colunas da tabela documentos_emitidos: ' . json_encode($colunas_nomes));

            // Prepara os dados para inserção, verificando se as colunas existem
            $dados_insercao = [];

            // Verifica cada coluna antes de adicionar ao array de inserção
            if (in_array('aluno_id', $colunas_nomes)) {
                $dados_insercao['aluno_id'] = $aluno_id;
            } else if (in_array('id_aluno', $colunas_nomes)) {
                $dados_insercao['id_aluno'] = $aluno_id;
            }

            if (in_array('matricula_id', $colunas_nomes)) {
                $dados_insercao['matricula_id'] = $matricula_id;
            } else if (in_array('id_matricula', $colunas_nomes)) {
                $dados_insercao['id_matricula'] = $matricula_id;
            }

            if (in_array('curso_id', $colunas_nomes)) {
                $dados_insercao['curso_id'] = $matricula['curso_id'] ?? null;
            } else if (in_array('id_curso', $colunas_nomes)) {
                $dados_insercao['id_curso'] = $matricula['curso_id'] ?? null;
            }

            if (in_array('polo_id', $colunas_nomes)) {
                $dados_insercao['polo_id'] = $matricula['polo_id'] ?? null;
            } else if (in_array('id_polo', $colunas_nomes)) {
                $dados_insercao['id_polo'] = $matricula['polo_id'] ?? null;
            }

            if (in_array('tipo_documento_id', $colunas_nomes)) {
                $dados_insercao['tipo_documento_id'] = $tipo_documento_id;
            } else if (in_array('id_tipo_documento', $colunas_nomes)) {
                $dados_insercao['id_tipo_documento'] = $tipo_documento_id;
            }

            // Limpa os dados de inserção e usa apenas os campos corretos
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

            // Registra o documento no banco de dados
            $documento_id = $db->insert('documentos_emitidos', $dados_insercao);
        } catch (Exception $e) {
            error_log('ERRO ao verificar estrutura da tabela ou inserir documento: ' . $e->getMessage());
            throw new Exception('Erro ao inserir documento no banco de dados: ' . $e->getMessage());
        }

        // Atualiza o contador de documentos do polo
        if ($matricula && isset($matricula['polo_id'])) {
            $sql = "UPDATE polos SET documentos_emitidos = documentos_emitidos + 1, updated_at = NOW() WHERE id = ?";
            $db->query($sql, [$matricula['polo_id']]);
        }

        // Adiciona o arquivo ao ZIP
        $nome_arquivo_zip = sanitizarNomeArquivo($tipo . '_' . $aluno['nome'] . '_' . date('Y-m-d')) . '.html';
        $zip->addFile($arquivo_html, $nome_arquivo_zip);

        $documentos_gerados++;
    } catch (Exception $e) {
        $erros[] = "Erro ao gerar documento para {$aluno['nome']}: " . $e->getMessage();
    }
}

// Fecha o arquivo ZIP
$zip->close();

// Retorna o resultado
if ($documentos_gerados > 0) {
    echo json_encode([
        'success' => true,
        'message' => "Foram gerados {$documentos_gerados} documentos com sucesso." . (count($erros) > 0 ? " Ocorreram " . count($erros) . " erros." : ""),
        'documentos_gerados' => $documentos_gerados,
        'erros' => $erros,
        'download_url' => $caminho_zip
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => "Não foi possível gerar nenhum documento. " . implode(" ", $erros),
        'erros' => $erros
    ]);
}

/**
 * Função para sanitizar o nome de um arquivo
 */
function sanitizarNomeArquivo($nome) {
    // Remove acentos
    $nome = preg_replace('/[áàãâä]/ui', 'a', $nome);
    $nome = preg_replace('/[éèêë]/ui', 'e', $nome);
    $nome = preg_replace('/[íìîï]/ui', 'i', $nome);
    $nome = preg_replace('/[óòõôö]/ui', 'o', $nome);
    $nome = preg_replace('/[úùûü]/ui', 'u', $nome);
    $nome = preg_replace('/[ç]/ui', 'c', $nome);

    // Remove caracteres especiais
    $nome = preg_replace('/[^a-z0-9_\-\.]/i', '_', $nome);

    // Remove underscores duplicados
    $nome = preg_replace('/_+/', '_', $nome);

    return $nome;
}
