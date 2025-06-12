<?php
require_once '../includes/init.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

Auth::requireLogin();
$userType = Auth::getUserType();
if (!in_array($userType, ['financeiro', 'admin_master'])) {
    $_SESSION['error'] = 'Você não tem permissão para acessar o módulo financeiro.';
    header('Location: ../index.php');
    exit;
}

$db = Database::getInstance();
$action = $_GET['action'] ?? 'listar';

// Processa ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';    if ($postAction === 'gerar_boleto') {
        require_once 'includes/boleto_functions_compativel.php';

        // Dados do formulário (funciona com qualquer estrutura)
        $dados = [
            'tipo' => $_POST['tipo'],
            'tipo_entidade' => $_POST['tipo'], // Compatibilidade
            'referencia_id' => $_POST['referencia_id'],
            'entidade_id' => $_POST['referencia_id'], // Compatibilidade
            'valor' => str_replace(['R$', '.', ','], ['', '', '.'], $_POST['valor']),
            'data_vencimento' => $_POST['data_vencimento'],
            'descricao' => $_POST['descricao'],
            'nome_pagador' => $_POST['nome_pagador'],
            'cpf_pagador' => $_POST['cpf_pagador'],
            'endereco' => $_POST['endereco'],
            'bairro' => $_POST['bairro'],
            'cidade' => $_POST['cidade'],            'bairro' => $_POST['bairro'],
            'cidade' => $_POST['cidade'],
            'uf' => $_POST['uf'],
            'cep' => $_POST['cep'],
            'multa' => floatval($_POST['multa'] ?? 2),
            'juros' => floatval($_POST['juros'] ?? 1)
        ];

        $resultado = gerarBoletoBancarioCompativel($db, $dados);

        if ($resultado['status'] === 'sucesso') {
            $_SESSION['success'] = $resultado['mensagem'];
        } else {
            $_SESSION['error'] = $resultado['mensagem'];
        }

        header('Location: boletos.php');
        exit;
    }

    if ($postAction === 'excluir_boleto') {
        $boletoId = intval($_POST['boleto_id']);
        
        if ($boletoId > 0) {            try {
                // Primeiro, verificar se o boleto existe
                $boleto = $db->fetchOne("SELECT id FROM boletos WHERE id = ?", [$boletoId]);
                
                if (!$boleto) {
                    $_SESSION['error'] = 'Boleto não encontrado.';
                } else {
                    // Remover arquivos PDF relacionados (baseado no padrão de nomenclatura)
                    $arquivosPdf = [
                        '../uploads/boletos/boleto_' . $boletoId . '.pdf',
                        '../uploads/boletos/' . $boletoId . '.pdf',
                        '../uploads/boletos/boleto_' . $boletoId . '.html'
                    ];
                    
                    foreach ($arquivosPdf as $arquivoPdf) {
                        if (file_exists($arquivoPdf)) {
                            unlink($arquivoPdf);
                            error_log("Arquivo removido: $arquivoPdf");
                        }
                    }
                    
                    // Excluir o boleto do banco de dados
                    $result = $db->delete('boletos', 'id = ?', [$boletoId]);
                    
                    if ($result > 0) {
                        $_SESSION['success'] = 'Boleto excluído com sucesso!';
                    } else {
                        $_SESSION['error'] = 'Erro ao excluir boleto do banco de dados.';
                    }
                }
            } catch (Exception $e) {
                $_SESSION['error'] = 'Erro ao excluir boleto: ' . $e->getMessage();
            }
        } else {
            $_SESSION['error'] = 'ID do boleto inválido.';
        }
        
        header('Location: boletos.php');
        exit;
    }
}

// Busca dados
try {
    if ($action === 'listar') {
        // Configuração da paginação
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = 20; // Boletos por página
        $offset = ($page - 1) * $limit;
        
        // Busca total de registros para paginação
        $totalRecords = $db->fetchOne("
            SELECT COUNT(*) as total
            FROM boletos b
            LEFT JOIN alunos a ON b.tipo_entidade = 'aluno' AND b.entidade_id = a.id
            LEFT JOIN polos p ON b.tipo_entidade = 'polo' AND b.entidade_id = p.id
        ");
        $totalPages = ceil($totalRecords['total'] / $limit);
        
        // Query adaptada para a estrutura existente da tabela boletos com paginação
        $boletos = $db->fetchAll("
            SELECT b.*,
                   CASE
                       WHEN b.tipo_entidade = 'aluno' THEN a.nome
                       WHEN b.tipo_entidade = 'polo' THEN p.nome
                       ELSE b.nome_pagador
                   END as pagador_nome
            FROM boletos b
            LEFT JOIN alunos a ON b.tipo_entidade = 'aluno' AND b.entidade_id = a.id
            LEFT JOIN polos p ON b.tipo_entidade = 'polo' AND b.entidade_id = p.id
            ORDER BY b.created_at DESC
            LIMIT $limit OFFSET $offset
        ");
    } else {
        $totalPages = 0;
        $page = 1;
        $totalRecords = ['total' => 0];
    }

    // Busca polos para seleção
    $polos = $db->fetchAll("SELECT id, nome FROM polos ORDER BY nome");

} catch (Exception $e) {
    $_SESSION['error'] = 'Erro ao carregar dados: ' . $e->getMessage();
    $boletos = [];
    $polos = [];
    $totalPages = 0;
    $page = 1;
    $totalRecords = ['total' => 0];
}

$pageTitle = 'Boletos Bancários';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Faciência ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/financeiro.css">
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <?php include 'includes/sidebar.php'; ?>

        <div class="flex-1 flex flex-col ml-64">
            <?php include 'includes/header.php'; ?>            <main class="flex-1 p-6 overflow-y-auto">
                <div class="max-w-7xl mx-auto">

                    <!-- Mensagens de sucesso/erro -->
                    <?php if (isset($_SESSION['success'])): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm"><?php echo $_SESSION['success']; ?></p>
                            </div>
                        </div>
                    </div>
                    <?php unset($_SESSION['success']); endif; ?>                    <?php if (isset($_SESSION['error'])): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-circle"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm"><?php echo $_SESSION['error']; ?></p>
                            </div>
                        </div>
                    </div>
                    <?php unset($_SESSION['error']); endif; ?>

                    <?php if ($action === 'listar'): ?>
                    <!-- Listagem de Boletos -->
                    <div class="mb-8">
                        <div class="flex justify-between items-center mb-6">
                            <div>
                                <h1 class="text-3xl font-bold text-gray-900">Boletos Bancários</h1>
                                <p class="text-gray-600 mt-2">Gerencie boletos via API do Itaú</p>
                            </div>
                            <a href="boletos.php?action=novo" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                                <i class="fas fa-plus mr-2"></i>Gerar Boleto
                            </a>
                        </div>

                        <!-- Tabela de Boletos -->
                        <div class="bg-white rounded-lg shadow overflow-hidden">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-green-600">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Pagador</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Tipo</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Valor</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Vencimento</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Ações</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php if (empty($boletos)): ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                            <?php if (isset($tabelasNaoExistem)): ?>
                                            Configure o módulo financeiro primeiro.
                                            <?php else: ?>
                                            Nenhum boleto encontrado.
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($boletos as $boleto): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($boleto['pagador_nome'] ?? $boleto['nome_pagador']); ?>
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                <?php echo htmlspecialchars($boleto['cpf_pagador']); ?>
                                            </div>
                                        </td>                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                                <?php
                                                switch($boleto['tipo_entidade']) {
                                                    case 'aluno': echo 'bg-blue-100 text-blue-800'; break;
                                                    case 'polo': echo 'bg-purple-100 text-purple-800'; break;
                                                    default: echo 'bg-gray-100 text-gray-800';
                                                }
                                                ?>">
                                                <?php echo ucfirst($boleto['tipo_entidade']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            R$ <?php echo number_format($boleto['valor'], 2, ',', '.'); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo date('d/m/Y', strtotime($boleto['data_vencimento'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                                <?php
                                                switch($boleto['status']) {
                                                    case 'pago': echo 'bg-green-100 text-green-800'; break;
                                                    case 'vencido': echo 'bg-red-100 text-red-800'; break;
                                                    default: echo 'bg-yellow-100 text-yellow-800';
                                                }
                                                ?>">
                                                <?php echo ucfirst($boleto['status']); ?>
                                            </span>
                                        </td>                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <?php if (!empty($boleto['linha_digitavel'])): ?>
                                            <button onclick="mostrarLinhaDigitavel('<?php echo $boleto['linha_digitavel']; ?>')"
                                                    class="text-green-600 hover:text-green-900 mr-2" title="Linha Digitável">
                                                <i class="fas fa-barcode"></i>
                                            </button>
                                            <?php endif; ?>

                                            <!-- PDF Visualizar/Baixar -->
                                            <?php if (!empty($boleto['linha_digitavel']) || !empty($boleto['codigo_barras'])): ?>
                                            <a href="boleto_pdf.php?id=<?php echo $boleto['id']; ?>&action=visualizar" target="_blank"
                                               class="text-red-600 hover:text-red-900 mr-2" title="Visualizar PDF">
                                                <i class="fas fa-file-pdf"></i>
                                            </a>
                                            <a href="boleto_pdf.php?id=<?php echo $boleto['id']; ?>&action=download"
                                               class="text-orange-600 hover:text-orange-900 mr-2" title="Baixar PDF">
                                                <i class="fas fa-download"></i>
                                            </a>
                                            <?php endif; ?>

                                            <!-- URL do Itaú -->
                                            <?php if (!empty($boleto['url_boleto']) && strpos($boleto['url_boleto'], 'itau.com.br') !== false): ?>
                                            <a href="<?php echo $boleto['url_boleto']; ?>" target="_blank"
                                               class="text-blue-600 hover:text-blue-900 mr-2" title="Ver no Site do Itaú">
                                                <i class="fas fa-external-link-alt"></i>
                                            </a>
                                            <?php endif; ?>                                            <button onclick="verDetalhes(<?php echo $boleto['id']; ?>)"
                                                    class="text-indigo-600 hover:text-indigo-900 mr-2" title="Detalhes">
                                                <i class="fas fa-eye"></i>
                                            </button>

                                            <button onclick="excluirBoleto(<?php echo $boleto['id']; ?>, '<?php echo addslashes($boleto['descricao']); ?>')"
                                                    class="text-red-600 hover:text-red-900" title="Excluir Boleto">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>                                </tbody>
                            </table>
                        </div>

                        <!-- Paginação -->
                        <?php if ($totalPages > 1): ?>
                        <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                            <div class="flex-1 flex justify-between sm:hidden">
                                <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                    Anterior
                                </a>
                                <?php endif; ?>
                                <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo $page + 1; ?>" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                    Próximo
                                </a>
                                <?php endif; ?>
                            </div>
                            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm text-gray-700">
                                        Mostrando
                                        <span class="font-medium"><?php echo (($page - 1) * 20) + 1; ?></span>
                                        até
                                        <span class="font-medium"><?php echo min($page * 20, $totalRecords['total']); ?></span>
                                        de
                                        <span class="font-medium"><?php echo $totalRecords['total']; ?></span>
                                        resultados
                                    </p>
                                </div>
                                <div>
                                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                        <?php if ($page > 1): ?>
                                        <a href="?page=<?php echo $page - 1; ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                            <span class="sr-only">Anterior</span>
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                        <?php endif; ?>

                                        <?php
                                        // Mostrar páginas
                                        $start = max(1, $page - 2);
                                        $end = min($totalPages, $page + 2);
                                        
                                        if ($start > 1): ?>
                                        <a href="?page=1" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">1</a>
                                        <?php if ($start > 2): ?>
                                        <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>
                                        <?php endif; ?>
                                        <?php endif; ?>

                                        <?php for ($i = $start; $i <= $end; $i++): ?>
                                        <a href="?page=<?php echo $i; ?>" 
                                           class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium <?php echo $i == $page ? 'bg-green-50 border-green-500 text-green-600' : 'bg-white text-gray-700 hover:bg-gray-50'; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                        <?php endfor; ?>

                                        <?php if ($end < $totalPages): ?>
                                        <?php if ($end < $totalPages - 1): ?>
                                        <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>
                                        <?php endif; ?>
                                        <a href="?page=<?php echo $totalPages; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50"><?php echo $totalPages; ?></a>
                                        <?php endif; ?>

                                        <?php if ($page < $totalPages): ?>
                                        <a href="?page=<?php echo $page + 1; ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                            <span class="sr-only">Próximo</span>
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                        <?php endif; ?>
                                    </nav>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <?php elseif ($action === 'novo'): ?>
                    <!-- Formulário de Novo Boleto -->
                    <div class="mb-8">
                        <div class="flex items-center mb-6">
                            <a href="boletos.php" class="text-green-600 hover:text-green-800 mr-4">
                                <i class="fas fa-arrow-left"></i>
                            </a>
                            <div>
                                <h1 class="text-3xl font-bold text-gray-900">Gerar Novo Boleto</h1>
                                <p class="text-gray-600 mt-2">Preencha os dados para gerar o boleto via API do Itaú</p>
                            </div>
                        </div>

                        <form method="POST" class="bg-white rounded-lg shadow p-6">
                            <input type="hidden" name="action" value="gerar_boleto">

                            <!-- Tipo de Boleto -->
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Boleto *</label>
                                <select name="tipo" id="tipo-boleto" required onchange="alterarTipoBoleto()"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                    <option value="">Selecione o tipo</option>
                                    <option value="mensalidade">Mensalidade de Aluno</option>
                                    <option value="polo">Cobrança de Polo</option>
                                    <option value="avulso">Boleto Avulso</option>
                                </select>
                            </div>

                            <!-- Seleção de Aluno (para mensalidade) -->
                            <div id="secao-aluno" class="mb-6 hidden">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Aluno *</label>
                                <div class="relative">
                                    <input type="text" id="busca-aluno" placeholder="Digite o nome ou CPF do aluno..."
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                                           autocomplete="off">
                                    <input type="hidden" name="aluno_id" id="aluno-id-hidden">

                                    <!-- Lista de resultados -->
                                    <div id="resultados-aluno" class="absolute z-10 w-full bg-white border border-gray-300 rounded-md shadow-lg mt-1 max-h-60 overflow-y-auto hidden">
                                        <!-- Resultados serão inseridos aqui via JavaScript -->
                                    </div>

                                    <!-- Loading indicator -->
                                    <div id="loading-aluno" class="absolute right-3 top-3 hidden">
                                        <i class="fas fa-spinner fa-spin text-gray-400"></i>
                                    </div>
                                </div>

                                <!-- Aluno selecionado -->
                                <div id="aluno-selecionado" class="mt-2 p-3 bg-green-50 border border-green-200 rounded-md hidden">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="font-medium text-green-800" id="nome-aluno-selecionado"></div>
                                            <div class="text-sm text-green-600" id="cpf-aluno-selecionado"></div>
                                        </div>
                                        <button type="button" onclick="limparSelecaoAluno()" class="text-green-600 hover:text-green-800">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Seleção de Polo -->
                            <div id="secao-polo" class="mb-6 hidden">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Polo *</label>
                                <select name="polo_id" id="polo-select" onchange="preencherDadosPolo()"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                    <option value="">Selecione o polo</option>
                                    <?php foreach ($polos as $polo): ?>
                                    <option value="<?php echo $polo['id']; ?>" data-nome="<?php echo htmlspecialchars($polo['nome']); ?>">
                                        <?php echo htmlspecialchars($polo['nome']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <input type="hidden" name="referencia_id" id="referencia-id">
                            <input type="hidden" name="aluno_id" id="aluno-id-form">

                            <!-- Dados do Boleto -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Descrição *</label>
                                    <input type="text" name="descricao" id="descricao" required
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Valor *</label>
                                    <input type="text" name="valor" required data-mask="currency"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Data de Vencimento *</label>
                                    <input type="date" name="data_vencimento" required
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                </div>
                            </div>

                            <!-- Dados do Pagador -->
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Dados do Pagador</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome Completo *</label>
                                    <input type="text" name="nome_pagador" id="nome-pagador" required
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">CPF *</label>
                                    <input type="text" name="cpf_pagador" id="cpf-pagador" required data-mask="cpf"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Endereço *</label>
                                    <input type="text" name="endereco" required
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Bairro *</label>
                                    <input type="text" name="bairro" required
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Cidade *</label>
                                    <input type="text" name="cidade" required
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">UF *</label>
                                    <select name="uf" required
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                        <option value="">Selecione</option>
                                        <option value="AC">AC</option><option value="AL">AL</option><option value="AP">AP</option>
                                        <option value="AM">AM</option><option value="BA">BA</option><option value="CE">CE</option>
                                        <option value="DF">DF</option><option value="ES">ES</option><option value="GO">GO</option>
                                        <option value="MA">MA</option><option value="MT">MT</option><option value="MS">MS</option>
                                        <option value="MG">MG</option><option value="PA">PA</option><option value="PB">PB</option>
                                        <option value="PR">PR</option><option value="PE">PE</option><option value="PI">PI</option>
                                        <option value="RJ">RJ</option><option value="RN">RN</option><option value="RS">RS</option>
                                        <option value="RO">RO</option><option value="RR">RR</option><option value="SC">SC</option>
                                        <option value="SP">SP</option><option value="SE">SE</option><option value="TO">TO</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">CEP *</label>
                                    <input type="text" name="cep" required data-mask="cep"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                </div>
                            </div>

                            <!-- Configurações de Multa e Juros -->
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Configurações de Cobrança</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Multa (%)</label>
                                    <input type="number" name="multa" value="2" step="0.01" min="0" max="20"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Juros ao Mês (%)</label>
                                    <input type="number" name="juros" value="1" step="0.01" min="0" max="20"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                </div>
                            </div>

                            <div class="flex justify-end space-x-3">
                                <a href="boletos.php" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400">
                                    Cancelar
                                </a>
                                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                                    <i class="fas fa-file-invoice-dollar mr-2"></i>Gerar Boleto
                                </button>
                            </div>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
            </main>        </div>
    </div>

    <!-- Modal para mostrar linha digitável -->
    <div id="modal-linha-digitavel" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <h3 class="text-lg font-medium text-gray-900">Linha Digitável</h3>
                <div class="mt-2 px-7 py-3">
                    <p id="linha-digitavel-texto" class="text-sm text-gray-500 font-mono"></p>
                </div>
                <div class="items-center px-4 py-3">
                    <button onclick="copiarLinhaDigitavel()" class="px-4 py-2 bg-green-500 text-white text-base font-medium rounded-md w-24 mr-2 hover:bg-green-600">
                        Copiar
                    </button>
                    <button onclick="fecharModal()" class="px-4 py-2 bg-gray-500 text-white text-base font-medium rounded-md w-24 hover:bg-gray-600">
                        Fechar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Funções para o modal
        function mostrarLinhaDigitavel(linha) {
            document.getElementById('linha-digitavel-texto').textContent = linha;
            document.getElementById('modal-linha-digitavel').classList.remove('hidden');
        }

        function fecharModal() {
            document.getElementById('modal-linha-digitavel').classList.add('hidden');
        }

        function copiarLinhaDigitavel() {
            const texto = document.getElementById('linha-digitavel-texto').textContent;
            navigator.clipboard.writeText(texto).then(function() {
                alert('Linha digitável copiada!');
            });
        }        function verDetalhes(id) {
            // Implementar visualização de detalhes
            alert('Funcionalidade de detalhes será implementada. ID: ' + id);
        }

        function excluirBoleto(id, descricao) {
            if (confirm('Tem certeza que deseja excluir o boleto "' + descricao + '"?\n\nEsta ação não pode ser desfeita.')) {
                // Criar um formulário para enviar a requisição POST
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'boletos.php';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'excluir_boleto';
                
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'boleto_id';
                idInput.value = id;
                
                form.appendChild(actionInput);
                form.appendChild(idInput);
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Função para alterar tipo de boleto
        function alterarTipoBoleto() {
            const tipo = document.getElementById('tipo-boleto').value;
            const secaoAluno = document.getElementById('secao-aluno');
            const secaoPolo = document.getElementById('secao-polo');
            
            // Esconde todas as seções
            secaoAluno.classList.add('hidden');
            secaoPolo.classList.add('hidden');
            
            // Mostra a seção apropriada
            if (tipo === 'mensalidade') {
                secaoAluno.classList.remove('hidden');
                document.getElementById('referencia-id').value = '';
            } else if (tipo === 'polo') {
                secaoPolo.classList.remove('hidden');
                document.getElementById('referencia-id').value = '';
            }
        }

        function preencherDadosPolo() {
            const select = document.getElementById('polo-select');
            const option = select.options[select.selectedIndex];
            
            if (option.value) {
                document.getElementById('referencia-id').value = option.value;
                document.getElementById('nome-pagador').value = option.dataset.nome;
                document.getElementById('descricao').value = 'Cobrança do polo: ' + option.dataset.nome;
            }
        }

        function limparSelecaoAluno() {
            document.getElementById('aluno-selecionado').classList.add('hidden');
            document.getElementById('busca-aluno').value = '';
            document.getElementById('aluno-id-hidden').value = '';
            document.getElementById('referencia-id').value = '';
        }
    </script>

    <script src="js/financeiro.js"></script>
    <script src="js/boletos.js"></script>
</body>
</html>
