<?php
// Inicializa o sistema
require_once __DIR__ . '/../includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica se o usuário tem permissão para acessar o módulo financeiro
if (!Auth::hasPermission('financeiro', 'visualizar')) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Sem permissão para acessar este recurso.']);
    exit;
}

// Verifica se o ID do boleto foi informado
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'ID do boleto não informado.']);
    exit;
}

// Instancia o banco de dados
$db = Database::getInstance();

// Busca o boleto
$id = (int)$_GET['id'];
$sql = "SELECT * FROM boletos WHERE id = ?";
$boleto = $db->fetchOne($sql, [$id]);

if (!$boleto) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Boleto não encontrado.']);
    exit;
}

// Carrega a classe para gerar o PDF
require_once __DIR__ . '/includes/boleto_pdf.php';

try {
    // Gera o HTML do boleto
    $pdf = new BoletoPDF($boleto);
    $html = $pdf->gerarHTML();

    // Define os cabeçalhos para exibir o HTML
    header('Content-Type: text/html; charset=utf-8');

    // Exibe o HTML
    echo '<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boleto #' . $boleto['id'] . '</title>
</head>
<body>
    ' . $html . '
    <script>
        // Imprimir automaticamente
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>';

    exit;
} catch (Exception $e) {
    // Em caso de erro, redireciona para a página de visualização com mensagem de erro
    setMensagem('erro', 'Erro ao gerar o PDF do boleto: ' . $e->getMessage());
    redirect('gerar_boleto.php?action=visualizar&id=' . $id);
    exit;
}
?>
