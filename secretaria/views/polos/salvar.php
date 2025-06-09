<?php
// Verifica se o formulário foi enviado via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Redireciona para a listagem com mensagem de erro
    $_SESSION['mensagem'] = 'Método de requisição inválido.';
    $_SESSION['mensagem_tipo'] = 'erro';
    header('Location: polos.php');
    exit;
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

    // Validação do CNPJ
    if (!empty($dados['cnpj'])) {
        // Remove caracteres não numéricos para validar
        $cnpj_limpo = preg_replace('/[^0-9]/', '', $dados['cnpj']);
        if (!validarCnpj($cnpj_limpo)) {
            $erros[] = 'O CNPJ informado é inválido.';
        }
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
$endereco = trim($_POST['endereco'] ?? '');
$cidade = trim($_POST['cidade'] ?? '');
$cidade_ibge = isset($_POST['cidade_ibge']) ? (int)$_POST['cidade_ibge'] : null;
$cep = trim($_POST['cep'] ?? '');
$telefone = trim($_POST['telefone'] ?? '');
$email = trim($_POST['email'] ?? '');
$site = trim($_POST['site'] ?? '');
$status = isset($_POST['status']) ? (int)$_POST['status'] : 1;

// Preparar dados para inserção/atualização
$dados = [
    'nome' => $nome,
    'mec' => $mec,
    'razao_social' => $razao_social,
    'cnpj' => $cnpj,
    'endereco' => $endereco,
    'cidade' => $cidade,
    'cidade_ibge' => $cidade_ibge,
    'cep' => $cep,
    'telefone' => $telefone,
    'email' => $email,
    'site' => $site,
    'status' => $status,
    'updated_at' => date('Y-m-d H:i:s')
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

// Captura os tipos de polo selecionados
$tipos_polo = isset($_POST['tipos_polo']) ? $_POST['tipos_polo'] : [];

// Verifica se pelo menos um tipo de polo foi selecionado
if (empty($tipos_polo)) {
    $_SESSION['mensagem'] = 'Erro ao salvar o polo: Selecione pelo menos um tipo de polo.';
    $_SESSION['mensagem_tipo'] = 'erro';
    $_SESSION['form_data'] = $dados; // Salva os dados do formulário para preencher novamente

    if ($id) {
        header('Location: polos.php?action=editar&id=' . $id);
    } else {
        header('Location: polos.php?action=novo');
    }
    exit;
}

// Captura as informações financeiras
$financeiro = isset($_POST['financeiro']) ? $_POST['financeiro'] : [];

try {
    // Inicia a transação
    $db->beginTransaction();

    // Verifica se é uma atualização ou inserção
    if ($id) {
        // Atualização - usando apenas campos que existem na tabela
        $sql = "UPDATE polos SET
                nome = ?,
                razao_social = ?,
                cnpj = ?,
                telefone = ?,
                email = ?,
                endereco = ?,
                cidade = ?,
                cep = ?,
                site = ?,
                status = ?,
                updated_at = NOW()
                WHERE id = ?";

        $params = [
            $nome,
            $razao_social,
            $cnpj,
            $telefone,
            $email,
            $endereco,
            $cidade,
            $cep,
            $site,
            $status,
            $id
        ];

        $db->query($sql, $params);
        $polo_id = $id;

        // Remove os tipos de polo existentes
        $sql = "DELETE FROM polos_tipos WHERE polo_id = ?";
        $db->query($sql, [$polo_id]);
    } else {
        // Inserção - usando apenas campos que existem na tabela
        $sql = "INSERT INTO polos (
                nome,
                razao_social,
                cnpj,
                telefone,
                email,
                endereco,
                cidade,
                cep,
                site,
                status,
                created_at,
                updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

        $params = [
            $nome,
            $razao_social,
            $cnpj,
            $telefone,
            $email,
            $endereco,
            $cidade,
            $cep,
            $site,
            $status
        ];

        $db->query($sql, $params);
        $polo_id = $db->getConnection()->lastInsertId();
    }

    // Insere os tipos de polo selecionados
    foreach ($tipos_polo as $tipo_polo_id) {
        $sql = "INSERT INTO polos_tipos (polo_id, tipo_polo_id, created_at)
                VALUES (?, ?, NOW())";
        $db->query($sql, [$polo_id, $tipo_polo_id]);

        // Busca as configurações financeiras do tipo de polo
        $sql = "SELECT * FROM tipos_polos_financeiro WHERE tipo_polo_id = ?";
        $config_financeira = $db->fetchOne($sql, [$tipo_polo_id]);

        if ($config_financeira) {
            // Processa as informações financeiras
            $taxa_inicial = isset($financeiro[$tipo_polo_id]['taxa_inicial']) ? (float)$financeiro[$tipo_polo_id]['taxa_inicial'] : $config_financeira['taxa_inicial'];
            $valor_por_documento = isset($financeiro[$tipo_polo_id]['valor_por_documento']) ? (float)$financeiro[$tipo_polo_id]['valor_por_documento'] : $config_financeira['valor_documento'];
            $taxa_inicial_paga = isset($financeiro[$tipo_polo_id]['taxa_inicial_paga']) ? (int)$financeiro[$tipo_polo_id]['taxa_inicial_paga'] : 0;
            $data_pagamento_taxa = !empty($financeiro[$tipo_polo_id]['data_pagamento_taxa']) ? $financeiro[$tipo_polo_id]['data_pagamento_taxa'] : null;
            $pacotes_adquiridos = isset($financeiro[$tipo_polo_id]['pacotes_adquiridos']) ? (int)$financeiro[$tipo_polo_id]['pacotes_adquiridos'] : 0;
            $observacoes_financeiras = isset($financeiro[$tipo_polo_id]['observacoes']) ? trim($financeiro[$tipo_polo_id]['observacoes']) : '';

            // Calcula documentos disponíveis com base nos pacotes
            $documentos_disponiveis = 0;
            if ($config_financeira['pacote_documentos'] > 0 && $pacotes_adquiridos > 0) {
                $documentos_disponiveis = $config_financeira['pacote_documentos'] * $pacotes_adquiridos;
            }

            // Calcula o valor total pago
            $valor_total_pago = 0;
            if ($taxa_inicial_paga) {
                $valor_total_pago += $taxa_inicial; // Usa o valor personalizado da taxa inicial
            }
            if ($pacotes_adquiridos > 0) {
                $valor_total_pago += $config_financeira['valor_pacote'] * $pacotes_adquiridos;
            }

            // Verifica se já existe um registro financeiro para este polo e tipo
            $sql = "SELECT id FROM polos_financeiro WHERE polo_id = ? AND tipo_polo_id = ?";
            $financeiro_existente = $db->fetchOne($sql, [$polo_id, $tipo_polo_id]);

            if ($financeiro_existente) {
                // Atualiza o registro existente
                $sql = "UPDATE polos_financeiro SET
                        taxa_inicial = ?,
                        valor_por_documento = ?,
                        taxa_inicial_paga = ?,
                        data_pagamento_taxa = ?,
                        pacotes_adquiridos = ?,
                        documentos_disponiveis = ?,
                        valor_total_pago = ?,
                        observacoes = ?,
                        updated_at = NOW()
                        WHERE polo_id = ? AND tipo_polo_id = ?";

                $params = [
                    $taxa_inicial,
                    $valor_por_documento,
                    $taxa_inicial_paga,
                    $data_pagamento_taxa,
                    $pacotes_adquiridos,
                    $documentos_disponiveis,
                    $valor_total_pago,
                    $observacoes_financeiras,
                    $polo_id,
                    $tipo_polo_id
                ];
            } else {
                // Insere um novo registro
                $sql = "INSERT INTO polos_financeiro (
                        polo_id,
                        tipo_polo_id,
                        taxa_inicial,
                        valor_por_documento,
                        taxa_inicial_paga,
                        data_pagamento_taxa,
                        pacotes_adquiridos,
                        documentos_disponiveis,
                        documentos_emitidos,
                        valor_total_pago,
                        observacoes,
                        created_at,
                        updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, NOW(), NOW())";

                $params = [
                    $polo_id,
                    $tipo_polo_id,
                    $taxa_inicial,
                    $valor_por_documento,
                    $taxa_inicial_paga,
                    $data_pagamento_taxa,
                    $pacotes_adquiridos,
                    $documentos_disponiveis,
                    $valor_total_pago,
                    $observacoes_financeiras
                ];
            }

            $db->query($sql, $params);
        }
    }

    // Confirma a transação
    $db->commit();

    $_SESSION['mensagem'] = $id ? 'Polo atualizado com sucesso!' : 'Polo cadastrado com sucesso!';
    $_SESSION['mensagem_tipo'] = 'sucesso';
    header('Location: polos.php?action=visualizar&id=' . $polo_id);
} catch (Exception $e) {
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


