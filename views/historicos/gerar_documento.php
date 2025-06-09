<?php
/**
 * Função para gerar um documento acadêmico (histórico ou declaração)
 *
 * @param int $tipo_documento_id ID do tipo de documento
 * @param int $aluno_id ID do aluno
 * @param int $matricula_id ID da matrícula
 * @param array $opcoes Opções adicionais para o documento
 * @return array Informações sobre o documento gerado
 */
function gerarDocumentoAcademico($db, $tipo_documento_id, $aluno_id, $matricula_id, $opcoes = []) {
    // Busca o tipo de documento
    $sql = "SELECT * FROM tipos_documentos WHERE id = ?";
    $tipo_documento = executarConsulta($db, $sql, [$tipo_documento_id]);

    if (!$tipo_documento) {
        throw new Exception("Tipo de documento não encontrado.");
    }

    // Busca o aluno
    $sql = "SELECT * FROM alunos WHERE id = ?";
    $aluno = executarConsulta($db, $sql, [$aluno_id]);

    if (!$aluno) {
        throw new Exception("Aluno não encontrado.");
    }

    // Busca a matrícula
    $sql = "SELECT m.*, c.nome as curso_nome, c.nivel as curso_nivel, c.modalidade as curso_modalidade,
                   c.polo_id, p.nome as polo_nome, t.nome as turma_nome
            FROM matriculas m
            LEFT JOIN cursos c ON m.curso_id = c.id
            LEFT JOIN polos p ON c.polo_id = p.id
            LEFT JOIN turmas t ON m.turma_id = t.id
            WHERE m.id = ? AND m.aluno_id = ?";
    $matricula = executarConsulta($db, $sql, [$matricula_id, $aluno_id]);

    if (!$matricula) {
        throw new Exception("Matrícula não encontrada ou não pertence ao aluno informado.");
    }

    // Busca as configurações de documentos
    $sql = "SELECT * FROM configuracoes_documentos";
    $configuracoes_array = executarConsultaAll($db, $sql);

    $configuracoes = [];
    foreach ($configuracoes_array as $config) {
        $configuracoes[$config['chave']] = $config['valor'];
    }

    // Prepara os dados básicos para o documento
    $dados_documento = [
        'instituicao' => $configuracoes['instituicao'] ?? 'Faciência - Faculdade de Ciências e Tecnologia',
        'instituicao_info' => $configuracoes['instituicao_info'] ?? 'CNPJ: XX.XXX.XXX/0001-XX',
        'cidade' => $configuracoes['cidade'] ?? 'São Paulo',
        'responsavel' => $configuracoes['responsavel'] ?? 'Nome do Responsável',
        'cargo_responsavel' => $configuracoes['cargo_responsavel'] ?? 'Secretário(a) Acadêmico(a)',
        'data_emissao' => formatarDataPorExtenso(date('Y-m-d')),
        'aluno_nome' => $aluno['nome'],
        'aluno_cpf' => formatarCpf($aluno['cpf']),
        'matricula_numero' => $matricula['numero'],
        'curso_nome' => $matricula['curso_nome'],
        'polo_nome' => $matricula['polo_nome'],
        'data_inicio' => formatarData($matricula['data_inicio']),
        'situacao' => getStatusMatricula($matricula['status']),
        'data_previsao_termino' => formatarData($matricula['data_previsao_termino'])
    ];

    // Adiciona opções personalizadas
    foreach ($opcoes as $chave => $valor) {
        $dados_documento[$chave] = $valor;
    }

    // Gera conteúdo específico para cada tipo de documento
    $conteudo_html = '';

    if ($tipo_documento['nome'] === 'Histórico Escolar' || $tipo_documento['nome'] === 'Histórico' || stripos($tipo_documento['nome'], 'Histórico') !== false) {
        // Busca as disciplinas e notas do aluno
        $sql = "SELECT nd.*, d.nome as disciplina_nome, d.carga_horaria
                FROM notas_disciplinas nd
                JOIN disciplinas d ON nd.disciplina_id = d.id
                WHERE nd.matricula_id = ?
                ORDER BY d.nome ASC";
        $disciplinas = executarConsultaAll($db, $sql, [$matricula_id]);

        // Gera o HTML das disciplinas
        $disciplinas_html = '';

        if (!empty($disciplinas)) {
            foreach ($disciplinas as $disciplina) {
                $situacao = getSituacaoDisciplina($disciplina['nota'], $disciplina['frequencia']);

                $disciplinas_html .= '<tr>
                    <td>' . htmlspecialchars($disciplina['disciplina_nome']) . '</td>
                    <td>' . htmlspecialchars($disciplina['carga_horaria']) . 'h</td>
                    <td>' . htmlspecialchars(number_format($disciplina['nota'], 1, ',', '.')) . '</td>
                    <td>' . htmlspecialchars(number_format($disciplina['frequencia'], 1, ',', '.')) . '%</td>
                    <td>' . htmlspecialchars($situacao) . '</td>
                </tr>';
            }
        } else {
            $disciplinas_html = '<tr><td colspan="5" style="text-align: center;">Não há disciplinas cursadas até o momento.</td></tr>';
        }

        $dados_documento['disciplinas'] = $disciplinas_html;

        // Verifica se o template existe no banco de dados
        if (!empty($tipo_documento['template'])) {
            // Usa o template do tipo de documento do banco
            $conteudo_html = $tipo_documento['template'];
        } else {
            // Usa o template simplificado
            require_once 'views/documentos/gerar_historico_simples.php';
            $conteudo_html = gerarHistoricoSimples($dados_documento);
        }

        // Se estiver usando o template do banco, substitui as variáveis
        if (!empty($tipo_documento['template'])) {
            foreach ($dados_documento as $chave => $valor) {
                $conteudo_html = str_replace('{{' . $chave . '}}', $valor, $conteudo_html);
            }
        }
    } elseif ($tipo_documento['nome'] === 'Declaração de Matrícula' || $tipo_documento['nome'] === 'Declaração' || stripos($tipo_documento['nome'], 'Declaração') !== false) {
        // Verifica se o template existe no banco de dados
        if (!empty($tipo_documento['template'])) {
            // Usa o template do tipo de documento do banco
            $conteudo_html = $tipo_documento['template'];

            // Substitui as variáveis no template
            foreach ($dados_documento as $chave => $valor) {
                $conteudo_html = str_replace('{{' . $chave . '}}', $valor, $conteudo_html);
            }
        } else {
            // Usa o template simplificado
            require_once 'views/documentos/gerar_declaracao_simples.php';
            $conteudo_html = gerarDeclaracaoSimples($dados_documento);
        }
    } else {
        throw new Exception("Tipo de documento não suportado para geração automática.");
    }

    // Gera um código de verificação único
    $codigo_verificacao = gerarCodigoVerificacao();

    // Gera um hash do conteúdo para verificação de autenticidade
    $hash = hash('sha256', $conteudo_html);

    // Define o nome do arquivo
    $nome_arquivo = sanitizarNomeArquivo($tipo_documento['nome'] . '_' . $aluno['nome'] . '_' . date('Y-m-d'));
    $arquivo_nome = $nome_arquivo . '.pdf';

    // Define o caminho do arquivo
    $diretorio_upload = 'uploads/documentos/';
    if (!is_dir($diretorio_upload)) {
        mkdir($diretorio_upload, 0755, true);
    }

    $arquivo_path = $diretorio_upload . uniqid() . '_' . $arquivo_nome;

    // Gera o PDF usando a biblioteca DOMPDF
    require_once 'vendor/autoload.php';

    try {
        // Verifica se a biblioteca DOMPDF está disponível
        if (class_exists('\\Dompdf\\Dompdf')) {
            // Configura o DOMPDF
            $options = new \Dompdf\Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'Arial');

            $dompdf = new \Dompdf\Dompdf($options);
            $dompdf->loadHtml($conteudo_html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            // Salva o PDF no arquivo
            file_put_contents($arquivo_path, $dompdf->output());

            // Registra o sucesso no log
            error_log("PDF gerado com sucesso: " . $arquivo_path);
        } else {
            // Se a biblioteca DOMPDF não estiver disponível, salva o HTML
            throw new Exception("Biblioteca DOMPDF não encontrada");
        }
    } catch (Exception $e) {
        // Se falhar ao gerar o PDF, salva o HTML como fallback
        error_log("Erro ao gerar PDF, salvando HTML como fallback: " . $e->getMessage());
        $arquivo_nome = $nome_arquivo . '.html';
        $arquivo_path = $diretorio_upload . uniqid() . '_' . $arquivo_nome;
        file_put_contents($arquivo_path, $conteudo_html);
    }

    // Prepara os dados para salvar no banco
    $dados_emissao = [
        'tipo_documento_id' => $tipo_documento_id,
        'aluno_id' => $aluno_id,
        'matricula_id' => $matricula_id,
        'polo_id' => $matricula['polo_id'],
        'curso_id' => $matricula['curso_id'],
        'turma_id' => $matricula['turma_id'],
        'data_emissao' => date('Y-m-d'),
        'data_validade' => ($tipo_documento['nome'] === 'Declaração de Matrícula') ?
                           date('Y-m-d', strtotime('+' . ($configuracoes['validade_declaracao'] ?? 30) . ' days')) : null,
        'arquivo_path' => $arquivo_path,
        'arquivo_nome' => $arquivo_nome,
        'hash' => $hash,
        'codigo_verificacao' => $codigo_verificacao,
        'dados_documento' => json_encode($dados_documento),
        'observacoes' => $opcoes['observacoes'] ?? null,
        'emitido_por' => $_SESSION['usuario_id'] ?? null,
        'status' => 'emitido',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];

    // Salva o documento emitido no banco
    $documento_emitido_id = $db->insert('documentos_emitidos', $dados_emissao);

    // Agora, registra o mesmo documento na tabela documentos
    try {
        // Prepara os dados para inserção na tabela documentos
        $dados_documento_registro = [
            'titulo' => $tipo_documento['nome'] . ' - ' . $aluno['nome'],
            'tipo_documento_id' => $tipo_documento_id,
            'aluno_id' => $aluno_id,
            'numero' => $codigo_verificacao,
            'data_emissao' => date('Y-m-d'),
            'data_validade' => $dados_emissao['data_validade'],
            'orgao_emissor' => 'Faciência',
            'observacoes' => $opcoes['observacoes'] ?? 'Documento gerado automaticamente pelo sistema',
            'arquivo_path' => $arquivo_path,
            'arquivo_nome' => $arquivo_nome,
            'arquivo_tipo' => 'text/html',
            'status' => 'ativo',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // Insere na tabela documentos
        $documento_id = $db->insert('documentos', $dados_documento_registro);
        error_log('Documento inserido na tabela documentos com ID: ' . $documento_id);
    } catch (Exception $e) {
        error_log('ERRO ao inserir na tabela documentos: ' . $e->getMessage());
        // Não interrompe o processo se falhar, já que o documento foi registrado em documentos_emitidos
        $documento_id = null;
    }

    // Incrementa o contador de documentos emitidos do polo
    if (!empty($matricula['polo_id'])) {
        $sql = "UPDATE polos SET documentos_emitidos = documentos_emitidos + 1 WHERE id = ?";
        $db->query($sql, [$matricula['polo_id']]);
    }

    // Retorna as informações do documento gerado
    return [
        'id' => $documento_emitido_id,
        'documento_id' => $documento_id,
        'tipo' => $tipo_documento['nome'],
        'aluno' => $aluno['nome'],
        'matricula' => $matricula['numero'],
        'curso' => $matricula['curso_nome'],
        'polo' => $matricula['polo_nome'],
        'data_emissao' => date('Y-m-d'),
        'codigo_verificacao' => $codigo_verificacao,
        'arquivo_path' => $arquivo_path,
        'arquivo_nome' => $arquivo_nome
    ];
}

/**
 * Função para formatar CPF
 */
function formatarCpf($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    if (strlen($cpf) != 11) {
        return $cpf;
    }
    return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
}

/**
 * Função para formatar data
 */
function formatarData($data) {
    if (empty($data)) {
        return '';
    }
    $timestamp = strtotime($data);
    return date('d/m/Y', $timestamp);
}

/**
 * Função para formatar data por extenso
 */
function formatarDataPorExtenso($data) {
    if (empty($data)) {
        return '';
    }

    $timestamp = strtotime($data);
    $dia = date('d', $timestamp);

    $meses = [
        1 => 'janeiro', 2 => 'fevereiro', 3 => 'março', 4 => 'abril',
        5 => 'maio', 6 => 'junho', 7 => 'julho', 8 => 'agosto',
        9 => 'setembro', 10 => 'outubro', 11 => 'novembro', 12 => 'dezembro'
    ];

    $mes = $meses[date('n', $timestamp)];
    $ano = date('Y', $timestamp);

    return $dia . ' de ' . $mes . ' de ' . $ano;
}

/**
 * Função para obter o status da matrícula formatado
 */
function getStatusMatricula($status) {
    $status_map = [
        'ativo' => 'Ativo',
        'trancado' => 'Trancado',
        'concluído' => 'Concluído',
        'cancelado' => 'Cancelado'
    ];

    return $status_map[$status] ?? ucfirst($status);
}

/**
 * Função para obter a situação da disciplina com base na nota e frequência
 */
function getSituacaoDisciplina($nota, $frequencia) {
    if ($frequencia < 75) {
        return 'Reprovado por Frequência';
    }

    if ($nota < 6) {
        return 'Reprovado por Nota';
    }

    return 'Aprovado';
}

/**
 * Função para gerar um código de verificação único
 */
function gerarCodigoVerificacao() {
    return strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
}

/**
 * Função para sanitizar o nome do arquivo
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

    // Limita o tamanho
    if (strlen($nome) > 50) {
        $nome = substr($nome, 0, 50);
    }

    return $nome;
}
