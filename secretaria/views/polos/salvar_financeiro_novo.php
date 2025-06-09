<?php
// Inicializa o sistema
require_once __DIR__ . '/../../includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setMensagem('erro', 'Método não permitido.');
    redirect('polos.php');
    exit;
}

// Obtém o ID do polo
$polo_id = isset($_POST['polo_id']) ? (int)$_POST['polo_id'] : 0;

// Obtém o ID do tipo de polo
$tipo_polo_id = isset($_POST['tipo_polo_id']) ? (int)$_POST['tipo_polo_id'] : 0;

// Verifica se o polo existe
$db = Database::getInstance();
$sql = "SELECT id, nome FROM polos WHERE id = ?";
$polo = $db->fetchOne($sql, [$polo_id]);

if (!$polo) {
    setMensagem('erro', 'Polo não encontrado.');
    redirect('polos.php');
    exit;
}

// Verifica se o tipo de polo está associado a este polo
$sql = "SELECT 1 FROM polos_tipos WHERE polo_id = ? AND tipo_polo_id = ?";
$tipo_associado = $db->fetchOne($sql, [$polo_id, $tipo_polo_id]);

if (!$tipo_associado) {
    setMensagem('erro', 'Tipo de polo não associado a este polo.');
    redirect('polos.php?action=financeiro&id=' . $polo_id);
    exit;
}

// Obtém os dados financeiros do formulário
$financeiro = $_POST['financeiro'] ?? [];

// Inicia a transação
$db->beginTransaction();

try {
    // Verifica se já existe um registro financeiro para este polo e tipo
    $sql = "SELECT id FROM polos_financeiro WHERE polo_id = ? AND tipo_polo_id = ?";
    $financeiro_existente = $db->fetchOne($sql, [$polo_id, $tipo_polo_id]);

    // Prepara os dados para inserção/atualização
    $dados = [
        'data_inicial' => $financeiro['data_inicial'] ?? null,
        'vigencia_contrato_meses' => isset($financeiro['vigencia_contrato_meses']) ? (int)$financeiro['vigencia_contrato_meses'] : null,
        'vencimento_contrato' => $financeiro['vencimento_contrato'] ?? null,
        'vigencia_pacote_setup' => isset($financeiro['vigencia_pacote_setup']) ? (int)$financeiro['vigencia_pacote_setup'] : null,
        'vencimento_pacote_setup' => $financeiro['vencimento_pacote_setup'] ?? null,
        'pacotes_adquiridos' => isset($financeiro['pacotes_adquiridos']) ? (int)$financeiro['pacotes_adquiridos'] : 0,
        'documentos_disponiveis' => isset($financeiro['pacotes_adquiridos']) ? (int)$financeiro['pacotes_adquiridos'] * 50 : 0,
        'valor_unitario_normal' => isset($financeiro['valor_unitario_normal']) ? (float)$financeiro['valor_unitario_normal'] : null,
        'quantidade_contratada' => isset($financeiro['quantidade_contratada']) ? (int)$financeiro['quantidade_contratada'] : null,
        'data_primeira_parcela' => $financeiro['data_primeira_parcela'] ?? null,
        'data_ultima_parcela' => $financeiro['data_ultima_parcela'] ?? null,
        'quantidade_parcelas' => isset($financeiro['quantidade_parcelas']) ? (int)$financeiro['quantidade_parcelas'] : null,
        'valor_previsto' => isset($financeiro['valor_previsto']) ? (float)$financeiro['valor_previsto'] : null,
        'observacoes' => $financeiro['observacoes'] ?? null
    ];

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

        $params = array_values($dados);
        $params[] = $polo_id;
        $params[] = $tipo_polo_id;

        $db->query($sql, $params);
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

        $params = [$polo_id, $tipo_polo_id];
        $params = array_merge($params, array_values($dados));

        $db->query($sql, $params);
    }

    // Confirma a transação
    $db->commit();

    setMensagem('sucesso', 'Informações financeiras do polo atualizadas com sucesso.');
    redirect('polos.php?action=financeiro&id=' . $polo_id . '&tipo=' . $tipo_polo_id);
} catch (Exception $e) {
    // Desfaz a transação em caso de erro
    $db->rollBack();

    // Registra o erro no log
    error_log('Erro ao salvar informações financeiras: ' . $e->getMessage());

    setMensagem('erro', 'Erro ao salvar informações financeiras: ' . $e->getMessage());
    redirect('polos.php?action=financeiro&id=' . $polo_id . '&tipo=' . $tipo_polo_id);
}
?>
