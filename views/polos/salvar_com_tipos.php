<?php
// Verifica se o formulário foi enviado via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Redireciona para a listagem com mensagem de erro
    $_SESSION['mensagem'] = 'Método de requisição inválido.';
    $_SESSION['mensagem_tipo'] = 'erro';
    header('Location: polos.php');
    exit;
}

// Log para depuração
error_log('=== INÍCIO SALVAMENTO POLO ===');
error_log('Formulário recebido em salvar_com_tipos.php');
error_log('POST: ' . print_r($_POST, true));
error_log('Método: ' . $_SERVER['REQUEST_METHOD']);
error_log('URL: ' . $_SERVER['REQUEST_URI']);

// Log específico para dados financeiros
if (isset($_POST['financeiro_novo'])) {
    error_log('Dados financeiros recebidos: ' . print_r($_POST['financeiro_novo'], true));
} else {
    error_log('Nenhum dado financeiro recebido no formulário');
}

// Função para validar os dados do formulário
function validarDados($dados) {
    $erros = [];

    // Validações básicas
    if (empty($dados['nome'])) {
        $erros[] = 'O nome do polo é obrigatório.';
    }

    if (empty($dados['razao_social'])) {
        $erros[] = 'A razão social é obrigatória.';
    }

    if (empty($dados['telefone'])) {
        $erros[] = 'O telefone é obrigatório.';
    }

    if (empty($dados['email'])) {
        $erros[] = 'O e-mail é obrigatório.';
    } elseif (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
        $erros[] = 'O e-mail informado é inválido.';
    }

    if (empty($dados['endereco'])) {
        $erros[] = 'O endereço é obrigatório.';
    }

    if (empty($dados['status'])) {
        $erros[] = 'O status é obrigatório.';
    }

    // Formatação do CNPJ (sem validação)
    if (!empty($dados['cnpj'])) {
        // Remove caracteres não numéricos
        $cnpj_limpo = preg_replace('/[^0-9]/', '', $dados['cnpj']);

        // Formata o CNPJ apenas se parecer com um CNPJ (tem pelo menos 8 dígitos)
        if (strlen($cnpj_limpo) >= 8) {
            $dados['cnpj'] = formatarCnpj($cnpj_limpo);
        }
        // Se não parecer um CNPJ, mantém o valor original
    }

    // Validação dos tipos de polo
    if (empty($dados['tipos_polo'])) {
        $erros[] = 'Selecione pelo menos um tipo de polo.';
    }

    return $erros;
}

// A função validarCnpj já está definida no arquivo init.php

// Captura os dados do formulário
$id = isset($_POST['id']) ? (int)$_POST['id'] : null;
$nome = trim($_POST['nome'] ?? '');
$mec = trim($_POST['mec'] ?? ''); // Campo MEC para o nome oficial do polo no MEC
$razao_social = trim($_POST['razao_social'] ?? '');
$cnpj = trim($_POST['cnpj'] ?? '');

// Formata o CNPJ apenas se parecer com um CNPJ (tem pelo menos 8 dígitos)
if (!empty($cnpj)) {
    $cnpj_limpo = preg_replace('/[^0-9]/', '', $cnpj);
    if (strlen($cnpj_limpo) >= 8) {
        $cnpj = formatarCnpj($cnpj_limpo);
    }
    // Se não parecer um CNPJ, mantém o valor original
}

$telefone = trim($_POST['telefone'] ?? '');
$email = trim($_POST['email'] ?? '');
$endereco = trim($_POST['endereco'] ?? '');
// Usa o campo cidade como texto simples
$cidade = trim($_POST['cidade'] ?? '');
$cep = trim($_POST['cep'] ?? '');
$site = trim($_POST['site'] ?? '');
$responsavel = trim($_POST['responsavel'] ?? '');
$data_inicio_parceria = !empty($_POST['data_inicio_parceria']) ? $_POST['data_inicio_parceria'] : null;
$data_fim_contrato = !empty($_POST['data_fim_contrato']) ? $_POST['data_fim_contrato'] : null;
$status_contrato = $_POST['status_contrato'] ?? 'ativo';
$observacoes = trim($_POST['observacoes'] ?? '');
$status = $_POST['status'] ?? 'ativo';
$limite_documentos = isset($_POST['limite_documentos']) ? (int)$_POST['limite_documentos'] : 100;
$documentos_emitidos = isset($_POST['documentos_emitidos']) ? (int)$_POST['documentos_emitidos'] : 0;

// Captura os tipos de polo selecionados
$tipos_polo = isset($_POST['tipos_polo']) ? $_POST['tipos_polo'] : [];

// Removidas as capturas de informações financeiras

// Organiza os dados em um array
$dados = [
    'nome' => $nome,
    'mec' => $mec,
    'razao_social' => $razao_social,
    'cnpj' => $cnpj,
    'telefone' => $telefone,
    'email' => $email,
    'endereco' => $endereco,
    'cidade' => $cidade,
    'responsavel' => $responsavel,
    'data_inicio_parceria' => $data_inicio_parceria,
    'data_fim_contrato' => $data_fim_contrato,
    'status_contrato' => $status_contrato,
    'observacoes' => $observacoes,
    'status' => $status,
    'limite_documentos' => $limite_documentos,
    'documentos_emitidos' => $documentos_emitidos,
    'tipos_polo' => $tipos_polo
];

// Valida os dados
$erros = validarDados($dados);

// Se houver erros, redireciona de volta com mensagem de erro
if (!empty($erros)) {
    $_SESSION['mensagem'] = 'Erro ao salvar o polo: ' . implode(' ', $erros);
    $_SESSION['mensagem_tipo'] = 'erro';
    $_SESSION['form_data'] = $dados; // Salva os dados do formulário para preencher novamente

    if ($id) {
        header('Location: polos.php?action=editar&id=' . $id);
    } else {
        header('Location: polos.php?action=novo');
    }
    exit;
}

try {
    // Inicia a transação
    $db->beginTransaction();

    // Verifica se é uma atualização ou inserção
    if ($id) {
        error_log("=== ATUALIZANDO POLO ID: {$id} ===");
        error_log("Dados recebidos - Nome: {$nome}, Email: {$email}, Telefone: {$telefone}");

        // Atualização - incluindo o campo mec
        $sql = "UPDATE polos SET
                nome = ?,
                mec = ?,
                razao_social = ?,
                cnpj = ?,
                telefone = ?,
                email = ?,
                endereco = ?,
                cidade = ?,
                status = ?,
                updated_at = NOW()
                WHERE id = ?";

        $params = [
            $nome,
            $mec,
            $razao_social,
            $cnpj,
            $telefone,
            $email,
            $endereco,
            $cidade,
            $status,
            $id
        ];

        error_log("SQL UPDATE: " . $sql);
        error_log("Parâmetros: " . print_r($params, true));

        $result = $db->query($sql, $params);
        error_log("Resultado do UPDATE: " . ($result ? 'SUCESSO' : 'FALHA'));

        // Verifica quantas linhas foram afetadas
        $rowCount = $result ? $result->rowCount() : 0;
        error_log("Linhas afetadas pelo UPDATE: " . $rowCount);

        // Remove os tipos de polo existentes
        $sql = "DELETE FROM polos_tipos WHERE polo_id = ?";
        $db->query($sql, [$id]);

        $polo_id = $id;
    } else {
        // Inserção - incluindo o campo mec
        $sql = "INSERT INTO polos (
                nome,
                mec,
                razao_social,
                cnpj,
                telefone,
                email,
                endereco,
                cidade,
                status,
                created_at,
                updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

        $params = [
            $nome,
            $mec,
            $razao_social,
            $cnpj,
            $telefone,
            $email,
            $endereco,
            $cidade,
            $status
        ];

        $db->query($sql, $params);
        $polo_id = $db->lastInsertId();
    }

    // Primeiro, remove todos os tipos de polo existentes
    $sql = "DELETE FROM polos_tipos WHERE polo_id = ?";
    $db->query($sql, [$polo_id]);

    // Garante que não há duplicatas na lista de tipos
    // Isso é importante: um polo pode ter até 3 tipos DIFERENTES, mas não o mesmo tipo repetido
    $tipos_polo = array_unique($tipos_polo);

    // Registra os tipos para debug
    error_log('Tipos de polo a serem inseridos: ' . implode(', ', $tipos_polo));

    // Insere os tipos de polo um por um
    foreach ($tipos_polo as $tipo_polo_id) {
        // Converte para inteiro para garantir tipo correto
        $tipo_polo_id = (int)$tipo_polo_id;

        // Verifica se o tipo já foi inserido (dupla verificação)
        $sql_check = "SELECT COUNT(*) as total FROM polos_tipos WHERE polo_id = ? AND tipo_polo_id = ?";
        $existe = $db->fetchOne($sql_check, [$polo_id, $tipo_polo_id]);

        if ($existe && $existe['total'] > 0) {
            error_log("Tipo de polo {$tipo_polo_id} já existe para o polo {$polo_id}, pulando.");
            continue;
        }

        try {
            // Insere o tipo de polo
            $sql = "INSERT INTO polos_tipos (polo_id, tipo_polo_id, created_at) VALUES (?, ?, NOW())";
            $db->query($sql, [$polo_id, $tipo_polo_id]);

            // Registra sucesso
            error_log("Tipo de polo {$tipo_polo_id} inserido com sucesso para o polo {$polo_id}");
        } catch (Exception $e) {
            // Registra o erro, mas continua a execução
            error_log("Erro ao inserir tipo de polo {$tipo_polo_id}: " . $e->getMessage());
            // Continua com o próximo tipo
            continue;
        }

        // Removido o processamento de informações financeiras
    }

    // Processamento das novas informações financeiras
    if (isset($_POST['financeiro_novo']) && is_array($_POST['financeiro_novo'])) {
        $financeiro_novo = $_POST['financeiro_novo'];

        // Log para depuração
        error_log('Dados financeiros recebidos: ' . print_r($financeiro_novo, true));

        // Para cada tipo de polo selecionado, salva as informações financeiras
        foreach ($tipos_polo as $tipo_polo_id) {
            // Converte para inteiro para garantir tipo correto
            $tipo_polo_id = (int)$tipo_polo_id;

            // Prepara os dados para inserção
            $dados_financeiro = [
                'polo_id' => $polo_id,
                'tipo_polo_id' => $tipo_polo_id,
                'data_inicial' => $financeiro_novo['data_inicial'] ?? null,
                'vigencia_contrato_meses' => isset($financeiro_novo['vigencia_contrato_meses']) ? (int)$financeiro_novo['vigencia_contrato_meses'] : null,
                'vencimento_contrato' => $financeiro_novo['vencimento_contrato'] ?? null,
                'vigencia_pacote_setup' => isset($financeiro_novo['vigencia_pacote_setup']) ? (int)$financeiro_novo['vigencia_pacote_setup'] : null,
                'vencimento_pacote_setup' => $financeiro_novo['vencimento_pacote_setup'] ?? null,
                'pacotes_adquiridos' => isset($financeiro_novo['pacotes_adquiridos']) ? (int)$financeiro_novo['pacotes_adquiridos'] : 0,
                'documentos_disponiveis' => isset($financeiro_novo['pacotes_adquiridos']) ? (int)$financeiro_novo['pacotes_adquiridos'] * 50 : 0,
                'valor_unitario_normal' => isset($financeiro_novo['valor_unitario_normal']) ? (float)$financeiro_novo['valor_unitario_normal'] : null,
                'quantidade_contratada' => isset($financeiro_novo['quantidade_contratada']) ? (int)$financeiro_novo['quantidade_contratada'] : null,
                'data_primeira_parcela' => $financeiro_novo['data_primeira_parcela'] ?? null,
                'data_ultima_parcela' => $financeiro_novo['data_ultima_parcela'] ?? null,
                'quantidade_parcelas' => isset($financeiro_novo['quantidade_parcelas']) ? (int)$financeiro_novo['quantidade_parcelas'] : null,
                'valor_previsto' => isset($financeiro_novo['valor_previsto']) ? (float)$financeiro_novo['valor_previsto'] : null,
                'observacoes' => $financeiro_novo['observacoes'] ?? null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            // Verifica se já existe um registro financeiro para este polo e tipo
            $sql = "SELECT id FROM polos_financeiro WHERE polo_id = ? AND tipo_polo_id = ?";
            $financeiro_existente = $db->fetchOne($sql, [$polo_id, $tipo_polo_id]);

            // Log para depuração
            error_log("Verificando registro financeiro existente para polo_id={$polo_id}, tipo_polo_id={$tipo_polo_id}: " .
                      ($financeiro_existente ? "Encontrado ID={$financeiro_existente['id']}" : "Não encontrado"));

            if ($financeiro_existente) {
                // Atualiza o registro existente
                $sql = "UPDATE polos_financeiro SET
                        data_inicial = ?,
                        vigencia_contrato_meses = ?,
                        vencimento_contrato = ?,
                        vigencia_pacote_setup = ?,
                        vencimento_pacote_setup = ?,
                        pacotes_adquiridos = ?,
                        documentos_disponiveis = ?,
                        valor_unitario_normal = ?,
                        quantidade_contratada = ?,
                        data_primeira_parcela = ?,
                        data_ultima_parcela = ?,
                        quantidade_parcelas = ?,
                        valor_previsto = ?,
                        observacoes = ?,
                        updated_at = NOW()
                        WHERE polo_id = ? AND tipo_polo_id = ?";

                $params = [
                    $dados_financeiro['data_inicial'],
                    $dados_financeiro['vigencia_contrato_meses'],
                    $dados_financeiro['vencimento_contrato'],
                    $dados_financeiro['vigencia_pacote_setup'],
                    $dados_financeiro['vencimento_pacote_setup'],
                    $dados_financeiro['pacotes_adquiridos'],
                    $dados_financeiro['documentos_disponiveis'],
                    $dados_financeiro['valor_unitario_normal'],
                    $dados_financeiro['quantidade_contratada'],
                    $dados_financeiro['data_primeira_parcela'],
                    $dados_financeiro['data_ultima_parcela'],
                    $dados_financeiro['quantidade_parcelas'],
                    $dados_financeiro['valor_previsto'],
                    $dados_financeiro['observacoes'],
                    $polo_id,
                    $tipo_polo_id
                ];

                try {
                    $db->query($sql, $params);
                    error_log("Informações financeiras atualizadas para o polo ID: {$polo_id}, tipo_polo_id: {$tipo_polo_id}");
                } catch (Exception $e) {
                    error_log("ERRO ao atualizar informações financeiras: " . $e->getMessage());
                    error_log("SQL: " . $sql);
                    error_log("Params: " . print_r($params, true));
                    throw $e; // Re-lança a exceção para ser capturada pelo bloco try/catch principal
                }
            } else {
                // Insere um novo registro
                $sql = "INSERT INTO polos_financeiro (
                        polo_id,
                        tipo_polo_id,
                        data_inicial,
                        vigencia_contrato_meses,
                        vencimento_contrato,
                        vigencia_pacote_setup,
                        vencimento_pacote_setup,
                        pacotes_adquiridos,
                        documentos_disponiveis,
                        valor_unitario_normal,
                        quantidade_contratada,
                        data_primeira_parcela,
                        data_ultima_parcela,
                        quantidade_parcelas,
                        valor_previsto,
                        observacoes,
                        created_at,
                        updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

                $params = [
                    $polo_id,
                    $tipo_polo_id,
                    $dados_financeiro['data_inicial'],
                    $dados_financeiro['vigencia_contrato_meses'],
                    $dados_financeiro['vencimento_contrato'],
                    $dados_financeiro['vigencia_pacote_setup'],
                    $dados_financeiro['vencimento_pacote_setup'],
                    $dados_financeiro['pacotes_adquiridos'],
                    $dados_financeiro['documentos_disponiveis'],
                    $dados_financeiro['valor_unitario_normal'],
                    $dados_financeiro['quantidade_contratada'],
                    $dados_financeiro['data_primeira_parcela'],
                    $dados_financeiro['data_ultima_parcela'],
                    $dados_financeiro['quantidade_parcelas'],
                    $dados_financeiro['valor_previsto'],
                    $dados_financeiro['observacoes']
                ];

                try {
                    $db->query($sql, $params);
                    error_log("Novas informações financeiras inseridas para o polo ID: {$polo_id}, tipo_polo_id: {$tipo_polo_id}");
                } catch (Exception $e) {
                    error_log("ERRO ao inserir informações financeiras: " . $e->getMessage());
                    error_log("SQL: " . $sql);
                    error_log("Params: " . print_r($params, true));
                    throw $e; // Re-lança a exceção para ser capturada pelo bloco try/catch principal
                }
            }
        }
    } else {
        error_log("Nenhuma informação financeira recebida para o polo ID: {$polo_id}");
    }

    // Confirma a transação
    $db->commit();

    $_SESSION['mensagem'] = $id ? 'Polo atualizado com sucesso!' : 'Polo cadastrado com sucesso!';
    $_SESSION['mensagem_tipo'] = 'sucesso';
    header('Location: polos.php?action=visualizar&id=' . $polo_id);
} catch (Exception $e) {
    // Desfaz a transação em caso de erro
    $db->rollBack();

    // Registra o erro no log
    error_log('Erro ao salvar polo: ' . $e->getMessage());

    // Redireciona com mensagem de erro
    $_SESSION['mensagem'] = 'Erro ao salvar o polo: ' . $e->getMessage();
    $_SESSION['mensagem_tipo'] = 'erro';
    $_SESSION['form_data'] = $dados; // Salva os dados do formulário para preencher novamente

    if ($id) {
        header('Location: polos.php?action=editar&id=' . $id);
    } else {
        header('Location: polos.php?action=novo');
    }
    exit;
}
