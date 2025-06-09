<?php
// Verifica se o formulário foi enviado via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Redireciona para a listagem com mensagem de erro
    $_SESSION['mensagem'] = 'Método de requisição inválido.';
    $_SESSION['mensagem_tipo'] = 'erro';
    header('Location: polos.php');
    exit;
}

// Captura os dados do formulário
$polo_id = isset($_POST['polo_id']) ? (int)$_POST['polo_id'] : null;
$financeiro = isset($_POST['financeiro']) ? $_POST['financeiro'] : [];
$transacao = isset($_POST['transacao']) ? $_POST['transacao'] : [];

// Verifica se o polo existe
$sql = "SELECT id, nome FROM polos WHERE id = ?";
$polo = executarConsulta($db, $sql, [$polo_id]);

if (!$polo) {
    $_SESSION['mensagem'] = 'Polo não encontrado.';
    $_SESSION['mensagem_tipo'] = 'erro';
    header('Location: polos.php');
    exit;
}

try {
    // Inicia a transação
    $db->beginTransaction();

    // Processa as informações financeiras
    foreach ($financeiro as $tipo_polo_id => $dados) {
        $tipo_polo_id = (int)$tipo_polo_id;

        // Verifica se o tipo de polo está associado ao polo
        $sql = "SELECT id FROM polos_tipos WHERE polo_id = ? AND tipo_polo_id = ?";
        $polo_tipo = executarConsulta($db, $sql, [$polo_id, $tipo_polo_id]);

        if (!$polo_tipo) {
            continue; // Pula se o tipo de polo não estiver associado
        }

        // Busca as configurações financeiras do tipo de polo
        $sql = "SELECT * FROM tipos_polos_financeiro WHERE tipo_polo_id = ?";
        $config_financeira = executarConsulta($db, $sql, [$tipo_polo_id]);

        if (!$config_financeira) {
            continue; // Pula se não houver configurações financeiras
        }

        // Processa os dados
        $id = isset($dados['id']) ? (int)$dados['id'] : null;
        $taxa_inicial = isset($dados['taxa_inicial']) ? (float)$dados['taxa_inicial'] : $config_financeira['taxa_inicial'];
        $valor_por_documento = isset($dados['valor_por_documento']) ? (float)$dados['valor_por_documento'] : $config_financeira['valor_documento'];
        $taxa_inicial_paga = isset($dados['taxa_inicial_paga']) ? (int)$dados['taxa_inicial_paga'] : 0;
        $data_pagamento_taxa = !empty($dados['data_pagamento_taxa']) ? $dados['data_pagamento_taxa'] : null;
        $pacotes_adquiridos = isset($dados['pacotes_adquiridos']) ? (int)$dados['pacotes_adquiridos'] : 0;
        $documentos_disponiveis = isset($dados['documentos_disponiveis']) ? (int)$dados['documentos_disponiveis'] : 0;
        $documentos_emitidos = isset($dados['documentos_emitidos']) ? (int)$dados['documentos_emitidos'] : 0;
        $valor_total_pago = isset($dados['valor_total_pago']) ? (float)$dados['valor_total_pago'] : 0;
        $observacoes = isset($dados['observacoes']) ? trim($dados['observacoes']) : '';

        // Verifica se já existe registro financeiro para este polo e tipo
        $sql = "SELECT id FROM polos_financeiro WHERE polo_id = ? AND tipo_polo_id = ?";
        $financeiro_existente = executarConsulta($db, $sql, [$polo_id, $tipo_polo_id]);

        if ($financeiro_existente) {
            // Atualiza o registro existente
            $sql = "UPDATE polos_financeiro SET
                    taxa_inicial = ?,
                    valor_por_documento = ?,
                    taxa_inicial_paga = ?,
                    data_pagamento_taxa = ?,
                    pacotes_adquiridos = ?,
                    documentos_disponiveis = ?,
                    documentos_emitidos = ?,
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
                $documentos_emitidos,
                $valor_total_pago,
                $observacoes,
                $polo_id,
                $tipo_polo_id
            ];

            $db->query($sql, $params);
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
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

            $params = [
                $polo_id,
                $tipo_polo_id,
                $taxa_inicial,
                $valor_por_documento,
                $taxa_inicial_paga,
                $data_pagamento_taxa,
                $pacotes_adquiridos,
                $documentos_disponiveis,
                $documentos_emitidos,
                $valor_total_pago,
                $observacoes
            ];

            $db->query($sql, $params);
        }
    }

    // Processa as novas transações
    foreach ($transacao as $tipo_polo_id => $dados) {
        $tipo_polo_id = (int)$tipo_polo_id;

        // Verifica se o tipo de polo está associado ao polo
        $sql = "SELECT id FROM polos_tipos WHERE polo_id = ? AND tipo_polo_id = ?";
        $polo_tipo = executarConsulta($db, $sql, [$polo_id, $tipo_polo_id]);

        if (!$polo_tipo) {
            continue; // Pula se o tipo de polo não estiver associado
        }

        // Verifica se os dados da transação foram preenchidos
        if (empty($dados['tipo_transacao']) || empty($dados['valor']) || empty($dados['data_transacao'])) {
            continue; // Pula se os dados obrigatórios não foram preenchidos
        }

        $tipo_transacao = $dados['tipo_transacao'];
        $valor = (float)$dados['valor'];
        $quantidade = isset($dados['quantidade']) ? (int)$dados['quantidade'] : 1;
        $data_transacao = $dados['data_transacao'];
        $descricao = isset($dados['descricao']) ? trim($dados['descricao']) : '';

        // Insere a transação no histórico
        $sql = "INSERT INTO polos_financeiro_historico (
                polo_id,
                tipo_polo_id,
                tipo_transacao,
                valor,
                quantidade,
                data_transacao,
                descricao,
                usuario_id,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $params = [
            $polo_id,
            $tipo_polo_id,
            $tipo_transacao,
            $valor,
            $quantidade,
            $data_transacao,
            $descricao,
            $_SESSION['usuario_id'] ?? null
        ];

        $db->query($sql, $params);

        // Atualiza as informações financeiras com base na transação
        $sql = "SELECT * FROM polos_financeiro WHERE polo_id = ? AND tipo_polo_id = ?";
        $info_financeira = executarConsulta($db, $sql, [$polo_id, $tipo_polo_id]);

        if ($info_financeira) {
            $atualizacoes = [];
            $params = [];

            // Atualiza com base no tipo de transação
            if ($tipo_transacao === 'taxa_inicial') {
                $atualizacoes[] = "taxa_inicial_paga = 1";
                $atualizacoes[] = "data_pagamento_taxa = ?";
                $params[] = $data_transacao;
                $atualizacoes[] = "valor_total_pago = valor_total_pago + ?";
                $params[] = $valor;
                // Se o valor for diferente da taxa inicial padrão, atualiza o valor da taxa inicial
                $atualizacoes[] = "taxa_inicial = ?";
                $params[] = $valor;
            } elseif ($tipo_transacao === 'pacote') {
                // Busca as configurações financeiras do tipo de polo
                $sql = "SELECT * FROM tipos_polos_financeiro WHERE tipo_polo_id = ?";
                $config_financeira = executarConsulta($db, $sql, [$tipo_polo_id]);

                if ($config_financeira && $config_financeira['pacote_documentos'] > 0) {
                    $documentos_adicionados = $config_financeira['pacote_documentos'] * $quantidade;
                    $atualizacoes[] = "pacotes_adquiridos = pacotes_adquiridos + ?";
                    $params[] = $quantidade;
                    $atualizacoes[] = "documentos_disponiveis = documentos_disponiveis + ?";
                    $params[] = $documentos_adicionados;
                    $atualizacoes[] = "valor_total_pago = valor_total_pago + ?";
                    $params[] = $valor;
                }
            } elseif ($tipo_transacao === 'documento') {
                $atualizacoes[] = "documentos_emitidos = documentos_emitidos + ?";
                $params[] = $quantidade;
                $atualizacoes[] = "documentos_disponiveis = GREATEST(0, documentos_disponiveis - ?)";
                $params[] = $quantidade;
            } elseif ($tipo_transacao === 'outro') {
                $atualizacoes[] = "valor_total_pago = valor_total_pago + ?";
                $params[] = $valor;
            }

            if (!empty($atualizacoes)) {
                $sql = "UPDATE polos_financeiro SET " . implode(", ", $atualizacoes) . ", updated_at = NOW() WHERE polo_id = ? AND tipo_polo_id = ?";
                $params[] = $polo_id;
                $params[] = $tipo_polo_id;
                $db->query($sql, $params);
            }
        }
    }

    // Confirma a transação
    $db->commit();

    $_SESSION['mensagem'] = 'Informações financeiras atualizadas com sucesso!';
    $_SESSION['mensagem_tipo'] = 'sucesso';
    header('Location: polos.php?action=financeiro&id=' . $polo_id);
} catch (Exception $e) {
    // Desfaz a transação em caso de erro
    $db->rollBack();

    // Registra o erro no log
    error_log('Erro ao salvar informações financeiras: ' . $e->getMessage());

    // Redireciona com mensagem de erro
    $_SESSION['mensagem'] = 'Erro ao salvar informações financeiras: ' . $e->getMessage();
    $_SESSION['mensagem_tipo'] = 'erro';
    header('Location: polos.php?action=editar_financeiro&id=' . $polo_id);
    exit;
}
