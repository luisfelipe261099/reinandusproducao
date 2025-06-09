<?php
/**
 * Funções auxiliares para o sistema de chamados
 */

/**
 * Busca chamados com filtros
 *
 * @param object $db Instância do banco de dados
 * @param array $filtros Filtros a serem aplicados
 * @param int $pagina Página atual
 * @param int $itens_por_pagina Itens por página
 * @return array Array com os chamados e informações de paginação
 */
function buscarChamados($db, $filtros = [], $pagina = 1, $itens_por_pagina = 20) {
    // Inicializa variáveis
    $where_conditions = ["1=1"]; // Sempre verdadeiro para facilitar a concatenação
    $params = [];
    $offset = ($pagina - 1) * $itens_por_pagina;

    // Aplica filtros
    if (isset($filtros['tipo']) && $filtros['tipo'] !== '') {
        $where_conditions[] = "c.tipo = ?";
        $params[] = $filtros['tipo'];
    }

    if (!empty($filtros['subtipo'])) {
        $where_conditions[] = "c.subtipo = ?";
        $params[] = $filtros['subtipo'];
    }

    if (!empty($filtros['status'])) {
        $where_conditions[] = "c.status = ?";
        $params[] = $filtros['status'];
    }

    if (!empty($filtros['polo_id'])) {
        $where_conditions[] = "c.polo_id = ?";
        $params[] = $filtros['polo_id'];
    }

    if (!empty($filtros['data_inicio']) && !empty($filtros['data_fim'])) {
        $where_conditions[] = "c.data_abertura BETWEEN ? AND ?";
        $params[] = $filtros['data_inicio'] . ' 00:00:00';
        $params[] = $filtros['data_fim'] . ' 23:59:59';
    } else if (!empty($filtros['data_inicio'])) {
        $where_conditions[] = "c.data_abertura >= ?";
        $params[] = $filtros['data_inicio'] . ' 00:00:00';
    } else if (!empty($filtros['data_fim'])) {
        $where_conditions[] = "c.data_abertura <= ?";
        $params[] = $filtros['data_fim'] . ' 23:59:59';
    }

    if (!empty($filtros['busca'])) {
        $where_conditions[] = "(c.id LIKE ? OR p.nome LIKE ? OR u.nome LIKE ?)";
        $busca = "%" . $filtros['busca'] . "%";
        $params[] = $busca;
        $params[] = $busca;
        $params[] = $busca;
    }

    // Monta a cláusula WHERE
    $where_clause = " WHERE " . implode(" AND ", $where_conditions);

    // Consulta para contar o total de registros
    $sql_count = "SELECT COUNT(*) as total
                 FROM chamados c
                 LEFT JOIN polos p ON c.polo_id = p.id
                 LEFT JOIN usuarios u ON u.id = c.solicitante_id
                 $where_clause";

    $resultado = $db->fetchOne($sql_count, $params);
    $total_registros = $resultado['total'] ?? 0;

    // Cálculo do total de páginas
    $total_paginas = ceil($total_registros / $itens_por_pagina);

    // Consulta para buscar os chamados
    $sql = "SELECT c.*, p.nome as polo_nome, u.nome as solicitante_nome,
                  (SELECT COUNT(*) FROM chamados_alunos ca WHERE ca.chamado_id = c.id) as total_alunos,
                  (SELECT COUNT(*) FROM chamados_alunos ca WHERE ca.chamado_id = c.id AND ca.documento_gerado = 1) as documentos_gerados
           FROM chamados c
           LEFT JOIN polos p ON c.polo_id = p.id
           LEFT JOIN usuarios u ON u.id = c.solicitante_id
           $where_clause
           ORDER BY c.data_abertura DESC
           LIMIT $itens_por_pagina OFFSET $offset";

    $chamados = $db->fetchAll($sql, $params);

    return [
        'chamados' => $chamados,
        'total_registros' => $total_registros,
        'total_paginas' => $total_paginas,
        'pagina_atual' => $pagina
    ];
}

/**
 * Busca um chamado pelo ID
 *
 * @param object $db Instância do banco de dados
 * @param int $id ID do chamado
 * @return array|null Dados do chamado ou null se não encontrado
 */
function buscarChamadoPorId($db, $id) {
    $sql = "SELECT c.*, p.nome as polo_nome, u.nome as solicitante_nome
            FROM chamados c
            LEFT JOIN polos p ON c.polo_id = p.id
            LEFT JOIN usuarios u ON u.id = c.solicitante_id
            WHERE c.id = ?";

    return $db->fetchOne($sql, [$id]);
}

/**
 * Busca alunos relacionados a um chamado
 *
 * @param object $db Instância do banco de dados
 * @param int $chamado_id ID do chamado
 * @return array Lista de alunos
 */
function buscarAlunosDoChamado($db, $chamado_id) {
    $sql = "SELECT ca.*, a.nome as aluno_nome, a.cpf, a.email
            FROM chamados_alunos ca
            JOIN alunos a ON ca.aluno_id = a.id
            WHERE ca.chamado_id = ?
            ORDER BY a.nome";

    return $db->fetchAll($sql, [$chamado_id]);
}

/**
 * Busca o histórico de um chamado
 *
 * @param object $db Instância do banco de dados
 * @param int $chamado_id ID do chamado
 * @return array Histórico do chamado
 */
function buscarHistoricoChamado($db, $chamado_id) {
    $sql = "SELECT h.*, u.nome as usuario_nome
            FROM chamados_historico h
            JOIN usuarios u ON h.usuario_id = u.id
            WHERE h.chamado_id = ?
            ORDER BY h.data_hora DESC";

    return $db->fetchAll($sql, [$chamado_id]);
}

/**
 * Cria um novo chamado
 *
 * @param object $db Instância do banco de dados
 * @param array $dados Dados do chamado
 * @return int|false ID do chamado criado ou false em caso de erro
 */
function criarChamado($db, $dados) {
    try {
        // Inicia a transação
        $db->beginTransaction();

        // Prepara a consulta SQL para inserir o chamado
        $sql = "INSERT INTO chamados (
                    codigo,
                    titulo,
                    descricao,
                    categoria_id,
                    tipo,
                    subtipo,
                    prioridade,
                    status,
                    solicitante_id,
                    departamento,
                    polo_id,
                    data_abertura,
                    data_ultima_atualizacao,
                    created_at,
                    updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'aberto', ?, ?, ?, NOW(), NOW(), NOW(), NOW())";

        $params = [
            $dados['codigo'],
            $dados['titulo'],
            $dados['observacoes'] ?? 'Solicitação de documento',
            $dados['categoria_id'],
            $dados['tipo'],
            $dados['subtipo'],
            $dados['prioridade'],
            $dados['solicitante_id'],
            $dados['departamento'],
            $dados['polo_id']
        ];

        $db->query($sql, $params);
        $chamado_id = $db->lastInsertId();

        // Insere os alunos relacionados
        if (!empty($dados['alunos'])) {
            foreach ($dados['alunos'] as $aluno_id) {
                $sql = "INSERT INTO chamados_alunos (chamado_id, aluno_id)
                        VALUES (?, ?)";
                $db->query($sql, [$chamado_id, $aluno_id]);
            }
        }

        // Registra no histórico
        $sql = "INSERT INTO chamados_historico (
                    chamado_id, usuario_id, acao, descricao, data_hora
                ) VALUES (?, ?, 'abertura', ?, NOW())";

        $descricao = "Chamado aberto para solicitação de {$dados['subtipo']}";
        if (!empty($dados['observacoes'])) {
            $descricao .= ". Observações: {$dados['observacoes']}";
        }

        $db->query($sql, [$chamado_id, $dados['solicitante_id'], $descricao]);

        // Confirma a transação
        $db->commit();

        return $chamado_id;
    } catch (Exception $e) {
        // Desfaz a transação em caso de erro
        $db->rollBack();
        error_log("Erro ao criar chamado: " . $e->getMessage());
        return false;
    }
}

/**
 * Atualiza o status de um chamado
 *
 * @param object $db Instância do banco de dados
 * @param int $chamado_id ID do chamado
 * @param string $novo_status Novo status
 * @param int $usuario_id ID do usuário que está atualizando
 * @param string $observacao Observação opcional
 * @return bool True se atualizado com sucesso, false caso contrário
 */
function atualizarStatusChamado($db, $chamado_id, $novo_status, $usuario_id, $observacao = null) {
    try {
        // Inicia a transação
        $db->beginTransaction();

        // Atualiza o status do chamado
        $sql = "UPDATE chamados
                SET status = ?, data_atualizacao = NOW()
                WHERE id = ?";

        $db->query($sql, [$novo_status, $chamado_id]);

        // Registra no histórico
        $sql = "INSERT INTO chamados_historico (
                    chamado_id, usuario_id, acao, descricao, data_hora
                ) VALUES (?, ?, 'atualizacao_status', ?, NOW())";

        $descricao = "Status atualizado para: " . ucfirst(str_replace('_', ' ', $novo_status));
        if (!empty($observacao)) {
            $descricao .= ". Observação: {$observacao}";
        }

        $db->query($sql, [$chamado_id, $usuario_id, $descricao]);

        // Confirma a transação
        $db->commit();

        return true;
    } catch (Exception $e) {
        // Desfaz a transação em caso de erro
        $db->rollBack();
        error_log("Erro ao atualizar status do chamado: " . $e->getMessage());
        return false;
    }
}

/**
 * Registra a geração de um documento para um aluno
 *
 * @param object $db Instância do banco de dados
 * @param int $chamado_id ID do chamado
 * @param int $aluno_id ID do aluno
 * @param string $arquivo_path Caminho do arquivo gerado
 * @param int $usuario_id ID do usuário que gerou o documento
 * @return bool True se registrado com sucesso, false caso contrário
 */
function registrarDocumentoGerado($db, $chamado_id, $aluno_id, $arquivo_path, $usuario_id) {
    try {
        // Inicia a transação
        $db->beginTransaction();

        // Atualiza o registro do aluno no chamado
        $sql = "UPDATE chamados_alunos
                SET documento_gerado = 1, arquivo_path = ?, data_geracao = NOW()
                WHERE chamado_id = ? AND aluno_id = ?";

        $db->query($sql, [$arquivo_path, $chamado_id, $aluno_id]);

        // Busca informações do aluno
        $sql = "SELECT nome FROM alunos WHERE id = ?";
        $aluno = $db->fetchOne($sql, [$aluno_id]);

        // Busca informações do chamado
        $sql = "SELECT subtipo FROM chamados WHERE id = ?";
        $chamado = $db->fetchOne($sql, [$chamado_id]);

        // Registra no histórico
        $sql = "INSERT INTO chamados_historico (
                    chamado_id, usuario_id, acao, descricao, data_hora
                ) VALUES (?, ?, 'geracao_documento', ?, NOW())";

        $descricao = "Documento ({$chamado['subtipo']}) gerado para o aluno: {$aluno['nome']}";

        $db->query($sql, [$chamado_id, $usuario_id, $descricao]);

        // Verifica se todos os documentos foram gerados
        $sql = "SELECT
                    COUNT(*) as total_alunos,
                    SUM(CASE WHEN documento_gerado = 1 THEN 1 ELSE 0 END) as documentos_gerados
                FROM chamados_alunos
                WHERE chamado_id = ?";

        $resultado = $db->fetchOne($sql, [$chamado_id]);

        // Se todos os documentos foram gerados, atualiza o status do chamado para concluído
        if ($resultado['total_alunos'] == $resultado['documentos_gerados']) {
            $sql = "UPDATE chamados
                    SET status = 'concluido', data_atualizacao = NOW()
                    WHERE id = ?";

            $db->query($sql, [$chamado_id]);

            // Registra no histórico
            $sql = "INSERT INTO chamados_historico (
                        chamado_id, usuario_id, acao, descricao, data_hora
                    ) VALUES (?, ?, 'atualizacao_status', ?, NOW())";

            $descricao = "Chamado concluído automaticamente: todos os documentos foram gerados";

            $db->query($sql, [$chamado_id, $usuario_id, $descricao]);
        }

        // Confirma a transação
        $db->commit();

        return true;
    } catch (Exception $e) {
        // Desfaz a transação em caso de erro
        $db->rollBack();
        error_log("Erro ao registrar documento gerado: " . $e->getMessage());
        return false;
    }
}

/**
 * Busca alunos de um polo
 *
 * @param object $db Instância do banco de dados
 * @param int $polo_id ID do polo
 * @param string $termo Termo de busca (opcional)
 * @return array Lista de alunos
 */
function buscarAlunosPorPolo($db, $polo_id, $termo = null) {
    $params = [$polo_id];
    $where = "WHERE a.polo_id = ?";

    if (!empty($termo)) {
        $where .= " AND (a.nome LIKE ? OR a.cpf LIKE ? OR a.email LIKE ?)";
        $termo = "%{$termo}%";
        $params[] = $termo;
        $params[] = $termo;
        $params[] = $termo;
    }

    $sql = "SELECT a.id, a.nome, a.cpf, a.email, t.nome as turma_nome, c.nome as curso_nome
            FROM alunos a
            LEFT JOIN turmas t ON a.turma_id = t.id
            LEFT JOIN cursos c ON t.curso_id = c.id
            $where
            ORDER BY a.nome
            LIMIT 100";

    try {
        return $db->fetchAll($sql, $params);
    } catch (Exception $e) {
        error_log("Erro ao buscar alunos por polo: " . $e->getMessage());
        return [];
    }
}

/**
 * Busca alunos por turma
 *
 * @param object $db Instância do banco de dados
 * @param int $turma_id ID da turma
 * @return array Lista de alunos
 */
function buscarAlunosPorTurma($db, $turma_id) {
    $sql = "SELECT a.id, a.nome, a.matricula, a.cpf, a.email
            FROM alunos a
            WHERE a.turma_id = ?
            ORDER BY a.nome";

    return $db->fetchAll($sql, [$turma_id]);
}

/**
 * Busca turmas de um polo
 *
 * @param object $db Instância do banco de dados
 * @param int $polo_id ID do polo
 * @param string $termo Termo de busca (opcional)
 * @return array Lista de turmas
 */
function buscarTurmasPorPolo($db, $polo_id, $termo = null) {
    $params = [$polo_id];
    $where = "WHERE t.polo_id = ?";

    if (!empty($termo)) {
        $where .= " AND (t.nome LIKE ? OR c.nome LIKE ?)";
        $termo = "%{$termo}%";
        $params[] = $termo;
        $params[] = $termo;
    }

    $sql = "SELECT t.id, t.nome, t.turno, c.nome as curso_nome,
                  (SELECT COUNT(*) FROM alunos a WHERE a.turma_id = t.id) as total_alunos
            FROM turmas t
            LEFT JOIN cursos c ON t.curso_id = c.id
            $where
            ORDER BY t.nome
            LIMIT 100";

    try {
        return $db->fetchAll($sql, $params);
    } catch (Exception $e) {
        error_log("Erro ao buscar turmas por polo: " . $e->getMessage());
        return [];
    }
}

/**
 * Verifica se um usuário tem permissão para acessar um chamado
 *
 * @param object $db Instância do banco de dados
 * @param int $chamado_id ID do chamado
 * @param int $usuario_id ID do usuário
 * @param string $tipo_usuario Tipo do usuário
 * @return bool True se tem permissão, false caso contrário
 */
function usuarioTemPermissaoChamado($db, $chamado_id, $usuario_id, $tipo_usuario) {
    // Administradores e secretaria têm acesso a todos os chamados
    if (in_array($tipo_usuario, ['admin_master', 'admin', 'secretaria_academica'])) {
        return true;
    }

    // Busca o chamado
    $sql = "SELECT c.*, u.polo_id as usuario_polo_id
            FROM chamados c
            JOIN usuarios u ON u.id = ?
            WHERE c.id = ?";

    $chamado = $db->fetchOne($sql, [$usuario_id, $chamado_id]);

    if (!$chamado) {
        return false;
    }

    // Usuários de polo só podem acessar chamados do seu próprio polo
    if ($tipo_usuario == 'polo') {
        return $chamado['polo_id'] == $chamado['usuario_polo_id'];
    }

    // Para outros tipos de usuário, verifica se foi o próprio que abriu o chamado
    return $chamado['solicitante_id'] == $usuario_id;
}

/**
 * Gera um documento para um aluno
 *
 * @param object $db Instância do banco de dados
 * @param int $chamado_id ID do chamado
 * @param int $aluno_id ID do aluno
 * @param string $tipo_documento Tipo do documento
 * @return string|false Caminho do arquivo gerado ou false em caso de erro
 */
function gerarDocumento($db, $chamado_id, $aluno_id, $tipo_documento) {
    // Busca informações do aluno
    $sql = "SELECT a.*, t.nome as turma_nome, c.nome as curso_nome, c.nivel, c.modalidade,
                  p.nome as polo_nome, p.cidade as polo_cidade
            FROM alunos a
            LEFT JOIN turmas t ON a.turma_id = t.id
            LEFT JOIN cursos c ON t.curso_id = c.id
            LEFT JOIN polos p ON a.polo_id = p.id
            WHERE a.id = ?";

    $aluno = $db->fetchOne($sql, [$aluno_id]);

    if (!$aluno) {
        return false;
    }

    // Diretório para salvar os documentos
    $diretorio = __DIR__ . '/../assets/documentos/gerados/' . $chamado_id;

    // Cria o diretório se não existir
    if (!is_dir($diretorio)) {
        mkdir($diretorio, 0755, true);
    }

    // Nome do arquivo
    $nome_arquivo = $tipo_documento . '_' . $aluno_id . '_' . date('YmdHis') . '.pdf';
    $caminho_arquivo = $diretorio . '/' . $nome_arquivo;

    // Caminho relativo para salvar no banco
    $caminho_relativo = 'assets/documentos/gerados/' . $chamado_id . '/' . $nome_arquivo;

    // Gera o documento de acordo com o tipo
    switch ($tipo_documento) {
        case 'declaracao':
            $resultado = gerarDeclaracao($aluno, $caminho_arquivo);
            break;
        case 'historico':
            $resultado = gerarHistorico($db, $aluno, $caminho_arquivo);
            break;
        case 'certificado':
            $resultado = gerarCertificado($aluno, $caminho_arquivo);
            break;
        case 'diploma':
            $resultado = gerarDiploma($aluno, $caminho_arquivo);
            break;
        default:
            return false;
    }

    if ($resultado) {
        return $caminho_relativo;
    }

    return false;
}

/**
 * Gera uma declaração para um aluno
 *
 * @param array $aluno Dados do aluno
 * @param string $caminho_arquivo Caminho onde o arquivo será salvo
 * @return bool True se gerado com sucesso, false caso contrário
 */
function gerarDeclaracao($aluno, $caminho_arquivo) {
    // Aqui você implementaria a geração do PDF da declaração
    // Por enquanto, vamos apenas criar um arquivo de texto para simular

    $conteudo = "DECLARAÇÃO\n\n";
    $conteudo .= "Declaramos para os devidos fins que o(a) aluno(a) {$aluno['nome']}, ";
    $conteudo .= "portador(a) do CPF {$aluno['cpf']}, matrícula {$aluno['matricula']}, ";
    $conteudo .= "está regularmente matriculado(a) no curso de {$aluno['curso_nome']}, ";
    $conteudo .= "nível {$aluno['nivel']}, modalidade {$aluno['modalidade']}, ";
    $conteudo .= "na turma {$aluno['turma_nome']}, ";
    $conteudo .= "no polo {$aluno['polo_nome']}, localizado em {$aluno['polo_cidade']}.\n\n";
    $conteudo .= "Data: " . date('d/m/Y') . "\n";
    $conteudo .= "Documento gerado automaticamente pelo sistema.";

    // Em uma implementação real, você usaria uma biblioteca como TCPDF, FPDF ou mPDF
    // para gerar um PDF profissional com o layout da instituição

    return file_put_contents($caminho_arquivo, $conteudo) !== false;
}

/**
 * Gera um histórico para um aluno
 *
 * @param object $db Instância do banco de dados
 * @param array $aluno Dados do aluno
 * @param string $caminho_arquivo Caminho onde o arquivo será salvo
 * @return bool True se gerado com sucesso, false caso contrário
 */
function gerarHistorico($db, $aluno, $caminho_arquivo) {
    // Busca as disciplinas e notas do aluno
    $sql = "SELECT d.nome as disciplina, d.carga_horaria, n.nota, n.frequencia
            FROM notas n
            JOIN disciplinas d ON n.disciplina_id = d.id
            WHERE n.aluno_id = ?
            ORDER BY d.nome";

    $disciplinas = $db->fetchAll($sql, [$aluno['id']]);

    // Aqui você implementaria a geração do PDF do histórico
    // Por enquanto, vamos apenas criar um arquivo de texto para simular

    $conteudo = "HISTÓRICO ESCOLAR\n\n";
    $conteudo .= "Aluno(a): {$aluno['nome']}\n";
    $conteudo .= "CPF: {$aluno['cpf']}\n";
    $conteudo .= "Matrícula: {$aluno['matricula']}\n";
    $conteudo .= "Curso: {$aluno['curso_nome']}\n";
    $conteudo .= "Nível: {$aluno['nivel']}\n";
    $conteudo .= "Modalidade: {$aluno['modalidade']}\n";
    $conteudo .= "Turma: {$aluno['turma_nome']}\n";
    $conteudo .= "Polo: {$aluno['polo_nome']} - {$aluno['polo_cidade']}\n\n";

    $conteudo .= "DISCIPLINAS CURSADAS:\n";
    $conteudo .= "-------------------------------------------------------------\n";
    $conteudo .= sprintf("%-40s %10s %10s %10s\n", "Disciplina", "C.H.", "Nota", "Freq. (%)");
    $conteudo .= "-------------------------------------------------------------\n";

    foreach ($disciplinas as $disciplina) {
        $conteudo .= sprintf("%-40s %10d %10.1f %10.1f\n",
                            $disciplina['disciplina'],
                            $disciplina['carga_horaria'],
                            $disciplina['nota'],
                            $disciplina['frequencia']);
    }

    $conteudo .= "-------------------------------------------------------------\n\n";
    $conteudo .= "Data: " . date('d/m/Y') . "\n";
    $conteudo .= "Documento gerado automaticamente pelo sistema.";

    // Em uma implementação real, você usaria uma biblioteca como TCPDF, FPDF ou mPDF
    // para gerar um PDF profissional com o layout da instituição

    return file_put_contents($caminho_arquivo, $conteudo) !== false;
}

/**
 * Gera um certificado para um aluno
 *
 * @param array $aluno Dados do aluno
 * @param string $caminho_arquivo Caminho onde o arquivo será salvo
 * @return bool True se gerado com sucesso, false caso contrário
 */
function gerarCertificado($aluno, $caminho_arquivo) {
    // Aqui você implementaria a geração do PDF do certificado
    // Por enquanto, vamos apenas criar um arquivo de texto para simular

    $conteudo = "CERTIFICADO DE CONCLUSÃO\n\n";
    $conteudo .= "Certificamos que o(a) aluno(a) {$aluno['nome']}, ";
    $conteudo .= "portador(a) do CPF {$aluno['cpf']}, matrícula {$aluno['matricula']}, ";
    $conteudo .= "concluiu com aproveitamento o curso de {$aluno['curso_nome']}, ";
    $conteudo .= "nível {$aluno['nivel']}, modalidade {$aluno['modalidade']}, ";
    $conteudo .= "na turma {$aluno['turma_nome']}, ";
    $conteudo .= "no polo {$aluno['polo_nome']}, localizado em {$aluno['polo_cidade']}.\n\n";
    $conteudo .= "Data: " . date('d/m/Y') . "\n";
    $conteudo .= "Documento gerado automaticamente pelo sistema.";

    // Em uma implementação real, você usaria uma biblioteca como TCPDF, FPDF ou mPDF
    // para gerar um PDF profissional com o layout da instituição

    return file_put_contents($caminho_arquivo, $conteudo) !== false;
}

/**
 * Gera um diploma para um aluno
 *
 * @param array $aluno Dados do aluno
 * @param string $caminho_arquivo Caminho onde o arquivo será salvo
 * @return bool True se gerado com sucesso, false caso contrário
 */
function gerarDiploma($aluno, $caminho_arquivo) {
    // Aqui você implementaria a geração do PDF do diploma
    // Por enquanto, vamos apenas criar um arquivo de texto para simular

    $conteudo = "DIPLOMA\n\n";
    $conteudo .= "A [NOME DA INSTITUIÇÃO] confere o presente diploma a\n\n";
    $conteudo .= "{$aluno['nome']}\n\n";
    $conteudo .= "portador(a) do CPF {$aluno['cpf']}, matrícula {$aluno['matricula']}, ";
    $conteudo .= "por ter concluído o curso de {$aluno['curso_nome']}, ";
    $conteudo .= "nível {$aluno['nivel']}, modalidade {$aluno['modalidade']}, ";
    $conteudo .= "na turma {$aluno['turma_nome']}, ";
    $conteudo .= "no polo {$aluno['polo_nome']}, localizado em {$aluno['polo_cidade']}.\n\n";
    $conteudo .= "Data: " . date('d/m/Y') . "\n";
    $conteudo .= "Documento gerado automaticamente pelo sistema.";

    // Em uma implementação real, você usaria uma biblioteca como TCPDF, FPDF ou mPDF
    // para gerar um PDF profissional com o layout da instituição

    return file_put_contents($caminho_arquivo, $conteudo) !== false;
}

/**
 * Busca todos os alunos do sistema
 *
 * @param object $db Instância do banco de dados
 * @param string $termo Termo de busca (opcional)
 * @return array Lista de alunos
 */
function buscarTodosAlunos($db, $termo = null) {
    $params = [];
    $where = "";

    if (!empty($termo)) {
        $where = "WHERE a.nome LIKE ? OR a.cpf LIKE ? OR a.email LIKE ?";
        $termo = "%{$termo}%";
        $params = [$termo, $termo, $termo];
    }

    $sql = "SELECT a.id, a.nome, a.cpf, a.email, t.nome as turma_nome, c.nome as curso_nome, p.nome as polo_nome
            FROM alunos a
            LEFT JOIN turmas t ON a.turma_id = t.id
            LEFT JOIN cursos c ON t.curso_id = c.id
            LEFT JOIN polos p ON a.polo_id = p.id
            $where
            ORDER BY a.nome
            LIMIT 100";

    try {
        return $db->fetchAll($sql, $params);
    } catch (Exception $e) {
        error_log("Erro ao buscar todos os alunos: " . $e->getMessage());
        return [];
    }
}











