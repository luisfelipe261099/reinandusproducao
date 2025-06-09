<?php
/**
 * Formulário para cadastro e edição de funcionários
 */

// Inclui os arquivos necessários
require_once __DIR__ . '/../includes/init.php';

// Verifica se o usuário está logado
exigirLogin();

// Verifica se o usuário tem permissão para acessar esta página
if (!Auth::hasPermission('financeiro', 'editar')) {
    setMensagem('erro', 'Você não tem permissão para acessar esta página.');
    redirect('funcionarios.php');
    exit;
}

// Inicializa a conexão com o banco de dados
$db = Database::getInstance();

// Verifica se é edição ou novo cadastro
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$funcionario = [];

if ($id > 0) {
    // Busca os dados do funcionário
    $funcionario = $db->fetchOne("SELECT * FROM funcionarios WHERE id = ?", [$id]);

    if (!$funcionario) {
        setMensagem('erro', 'Funcionário não encontrado.');
        redirect('funcionarios.php');
        exit;
    }

    $titulo_pagina = 'Editar Funcionário';
} else {
    $titulo_pagina = 'Novo Funcionário';

    // Inicializa com valores padrão
    $funcionario = [
        'nome' => '',
        'cpf' => '',
        'cargo' => '',
        'departamento' => '',
        'salario' => '',
        'data_admissao' => date('Y-m-d'),
        'status' => 'ativo'
    ];
}

// Processa o formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Captura os dados do formulário
    $funcionario = [
        // Dados Pessoais
        'nome' => $_POST['nome'] ?? '',
        'cpf' => $_POST['cpf'] ?? '',
        'rg' => $_POST['rg'] ?? null,
        'data_nascimento' => $_POST['data_nascimento'] ?? null,
        'email' => $_POST['email'] ?? null,
        'telefone' => $_POST['telefone'] ?? null,

        // Dados Profissionais
        'cargo' => $_POST['cargo'] ?? '',
        'departamento' => $_POST['departamento'] ?? '',
        'salario' => str_replace(',', '.', $_POST['salario'] ?? ''),
        'data_admissao' => $_POST['data_admissao'] ?? '',
        'data_demissao' => !empty($_POST['data_demissao']) ? $_POST['data_demissao'] : null,
        'status' => $_POST['status'] ?? 'ativo',

        // Dados Bancários
        'banco' => $_POST['banco'] ?? null,
        'agencia' => $_POST['agencia'] ?? null,
        'conta' => $_POST['conta'] ?? null,
        'tipo_conta' => $_POST['tipo_conta'] ?? null,
        'pix' => $_POST['pix'] ?? null,

        // Configuração de Pagamento
        'dia_pagamento' => !empty($_POST['dia_pagamento']) ? (int)$_POST['dia_pagamento'] : null,
        'forma_pagamento' => $_POST['forma_pagamento'] ?? null,
        'gerar_pagamento_automatico' => isset($_POST['gerar_pagamento_automatico']) ? 1 : 0,

        // Observações
        'observacoes' => $_POST['observacoes'] ?? null,

        // Campos de controle
        'updated_at' => date('Y-m-d H:i:s')
    ];

    // Validação básica
    $erros = [];

    if (empty($funcionario['nome'])) {
        $erros[] = 'O nome é obrigatório.';
    }

    if (empty($funcionario['cpf'])) {
        $erros[] = 'O CPF é obrigatório.';
    }

    if (empty($funcionario['cargo'])) {
        $erros[] = 'O cargo é obrigatório.';
    }

    if (empty($funcionario['salario']) || !is_numeric($funcionario['salario'])) {
        $erros[] = 'O salário é obrigatório e deve ser um valor numérico.';
    }

    if (empty($funcionario['data_admissao'])) {
        $erros[] = 'A data de admissão é obrigatória.';
    }

    // Validações adicionais
    if ($funcionario['gerar_pagamento_automatico'] && empty($funcionario['dia_pagamento'])) {
        $erros[] = 'O dia de pagamento é obrigatório quando o pagamento automático está ativado.';
    }

    if ($funcionario['gerar_pagamento_automatico'] && empty($funcionario['forma_pagamento'])) {
        $erros[] = 'A forma de pagamento é obrigatória quando o pagamento automático está ativado.';
    }

    // Se não houver erros, salva os dados
    if (empty($erros)) {
        try {
            // Inicia uma transação para garantir a integridade dos dados
            $db->beginTransaction();

            if ($id > 0) {
                // Atualiza o funcionário existente
                $result = $db->update('funcionarios', $funcionario, ['id' => $id]);
                $mensagem = 'Funcionário atualizado com sucesso.';
            } else {
                // Adiciona a data de criação para novos funcionários
                $funcionario['created_at'] = date('Y-m-d H:i:s');

                // Insere um novo funcionário
                $result = $db->insert('funcionarios', $funcionario);
                $id = $result;
                $mensagem = 'Funcionário cadastrado com sucesso.';
            }

            if ($result) {
                // Se o pagamento automático está ativado, cria ou atualiza o agendamento
                if ($funcionario['gerar_pagamento_automatico'] && !empty($funcionario['dia_pagamento'])) {
                    // Verifica se já existe um agendamento para este funcionário
                    $agendamento = $db->fetchOne("SELECT * FROM agendamentos_pagamentos WHERE funcionario_id = ? AND tipo = 'salario'", [$id]);

                    // Dados do agendamento
                    $dados_agendamento = [
                        'funcionario_id' => $id,
                        'tipo' => 'salario',
                        'valor' => $funcionario['salario'],
                        'dia_vencimento' => $funcionario['dia_pagamento'],
                        'forma_pagamento' => $funcionario['forma_pagamento'],
                        'status' => $funcionario['status'] === 'ativo' ? 'ativo' : 'inativo',
                        'observacoes' => 'Pagamento mensal de salário',
                        'updated_at' => date('Y-m-d H:i:s')
                    ];

                    if ($agendamento) {
                        // Atualiza o agendamento existente
                        $db->update('agendamentos_pagamentos', $dados_agendamento, ['id' => $agendamento['id']]);
                    } else {
                        // Adiciona a data de criação para novos agendamentos
                        $dados_agendamento['created_at'] = date('Y-m-d H:i:s');

                        // Cria um novo agendamento
                        $db->insert('agendamentos_pagamentos', $dados_agendamento);
                    }

                    // Verifica se já existe uma conta a pagar para o próximo mês
                    $proximo_mes = date('Y-m-d', strtotime('first day of next month'));
                    $data_vencimento = date('Y-m-d', strtotime($proximo_mes . ' + ' . ($funcionario['dia_pagamento'] - 1) . ' days'));

                    $conta_existente = $db->fetchOne("SELECT * FROM contas_pagar WHERE categoria = 'folha_pagamento' AND fornecedor = ? AND data_vencimento = ?", [$funcionario['nome'], $data_vencimento]);

                    if (!$conta_existente && $funcionario['status'] === 'ativo') {
                        // Cria uma conta a pagar para o próximo mês
                        $conta_pagar = [
                            'descricao' => 'Pagamento de salário - ' . $funcionario['nome'],
                            'valor' => $funcionario['salario'],
                            'data_vencimento' => $data_vencimento,
                            'categoria' => 'folha_pagamento',
                            'fornecedor' => $funcionario['nome'],
                            'forma_pagamento' => $funcionario['forma_pagamento'],
                            'status' => 'pendente',
                            'observacoes' => 'Gerado automaticamente pelo sistema',
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ];

                        $db->insert('contas_pagar', $conta_pagar);
                    }
                } else {
                    // Se o pagamento automático está desativado, desativa o agendamento existente
                    $agendamento = $db->fetchOne("SELECT * FROM agendamentos_pagamentos WHERE funcionario_id = ? AND tipo = 'salario'", [$id]);

                    if ($agendamento) {
                        $db->update('agendamentos_pagamentos', ['status' => 'inativo', 'updated_at' => date('Y-m-d H:i:s')], ['id' => $agendamento['id']]);
                    }
                }

                // Confirma a transação
                $db->commit();

                setMensagem('sucesso', $mensagem);
                redirect('funcionarios.php');
                exit;
            } else {
                // Reverte a transação em caso de erro
                $db->rollBack();
                $erros[] = 'Erro ao salvar os dados. Tente novamente.';
            }
        } catch (Exception $e) {
            // Reverte a transação em caso de exceção
            $db->rollBack();
            $erros[] = 'Erro ao salvar os dados: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faciência ERP - <?php echo $titulo_pagina; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        /* Garantir que todas as seções do formulário estejam visíveis */
        form > div.mb-6 {
            display: block !important;
            margin-bottom: 1.5rem !important;
        }

        form > div.mb-6 > h3 {
            font-size: 1.125rem;
            font-weight: 500;
            color: #111827;
            margin-bottom: 0.5rem;
        }

        /* Destacar as seções para melhor visualização */
        form > div.mb-6 {
            border-left: 4px solid #8b5cf6;
            padding-left: 1rem;
            background-color: #f9fafb;
            padding: 1rem;
            border-radius: 0.375rem;
        }

        /* Container com rolagem para o formulário */
        .form-container {
            max-height: 75vh;
            overflow-y: auto;
            padding-right: 1rem;
            display: block !important;
            position: relative;
            z-index: 1;
        }

        /* Garantir que o container de rolagem funcione corretamente */
        .form-container::-webkit-scrollbar {
            width: 8px;
        }

        .form-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .form-container::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        .form-container::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        /* Garantir que os campos não sejam cortados */
        .grid-cols-1 {
            grid-template-columns: 1fr;
        }

        @media (min-width: 768px) {
            .md\:grid-cols-2 {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        /* Espaçamento adequado entre os campos */
        .gap-6 {
            gap: 1.5rem;
        }

        /* Garantir que os campos de texto não sejam cortados */
        input, select, textarea {
            width: 100%;
            box-sizing: border-box;
        }

        /* Garantir que a seção de Dados Profissionais seja exibida corretamente */
        #secao-dados-profissionais {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            height: auto !important;
            overflow: visible !important;
            margin-bottom: 1.5rem !important;
            z-index: 10;
            position: relative;
        }

        /* Garantir que o campo de salário seja exibido corretamente */
        #salario {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            width: 100% !important;
            box-sizing: border-box !important;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <?php include 'includes/header.php'; ?>

            <!-- Main -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
                <div class="container mx-auto">
                    <h1 class="text-3xl font-bold text-gray-800 mb-6"><?php echo $titulo_pagina; ?></h1>

                    <!-- Mensagens de erro -->
                    <?php if (!empty($erros)): ?>
                        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
                            <ul class="list-disc list-inside">
                                <?php foreach ($erros as $erro): ?>
                                    <li><?php echo $erro; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <!-- Formulário -->
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <div class="form-container">
                            <form method="post" class="p-6">
                            <div class="mb-6" id="secao-dados-pessoais">
                                <div class="mb-2">
                                    <h3 class="text-lg font-medium text-gray-900">Dados Pessoais</h3>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Nome -->
                                    <div>
                                        <label for="nome" class="block text-sm font-medium text-gray-700 mb-1">Nome Completo</label>
                                        <input type="text" name="nome" id="nome" value="<?php echo $funcionario['nome']; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500" required>
                                    </div>

                                    <!-- CPF -->
                                    <div>
                                        <label for="cpf" class="block text-sm font-medium text-gray-700 mb-1">CPF</label>
                                        <input type="text" name="cpf" id="cpf" value="<?php echo $funcionario['cpf']; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500" required>
                                    </div>

                                    <!-- RG -->
                                    <div>
                                        <label for="rg" class="block text-sm font-medium text-gray-700 mb-1">RG</label>
                                        <input type="text" name="rg" id="rg" value="<?php echo $funcionario['rg'] ?? ''; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500">
                                    </div>

                                    <!-- Data de Nascimento -->
                                    <div>
                                        <label for="data_nascimento" class="block text-sm font-medium text-gray-700 mb-1">Data de Nascimento</label>
                                        <input type="date" name="data_nascimento" id="data_nascimento" value="<?php echo $funcionario['data_nascimento'] ?? ''; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500">
                                    </div>

                                    <!-- Email -->
                                    <div>
                                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                        <input type="email" name="email" id="email" value="<?php echo $funcionario['email'] ?? ''; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500">
                                    </div>

                                    <!-- Telefone -->
                                    <div>
                                        <label for="telefone" class="block text-sm font-medium text-gray-700 mb-1">Telefone</label>
                                        <input type="text" name="telefone" id="telefone" value="<?php echo $funcionario['telefone'] ?? ''; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-6" id="secao-dados-profissionais">
                                <div class="mb-2">
                                    <h3 class="text-lg font-medium text-gray-900">Dados Profissionais</h3>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Cargo -->
                                    <div>
                                        <label for="cargo" class="block text-sm font-medium text-gray-700 mb-1">Cargo</label>
                                        <input type="text" name="cargo" id="cargo" value="<?php echo $funcionario['cargo']; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500" required>
                                    </div>

                                    <!-- Departamento -->
                                    <div>
                                        <label for="departamento" class="block text-sm font-medium text-gray-700 mb-1">Departamento</label>
                                        <input type="text" name="departamento" id="departamento" value="<?php echo $funcionario['departamento']; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500">
                                    </div>

                                    <!-- Salário -->
                                    <div>
                                        <label for="salario" class="block text-sm font-medium text-gray-700 mb-1">Salário</label>
                                        <input type="text" name="salario" id="salario" value="<?php echo !empty($funcionario['salario']) ? number_format((float)$funcionario['salario'], 2, ',', '.') : '0,00'; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500" required>
                                    </div>

                                    <!-- Data de Admissão -->
                                    <div>
                                        <label for="data_admissao" class="block text-sm font-medium text-gray-700 mb-1">Data de Admissão</label>
                                        <input type="date" name="data_admissao" id="data_admissao" value="<?php echo $funcionario['data_admissao']; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500" required>
                                    </div>

                                    <!-- Data de Demissão -->
                                    <div>
                                        <label for="data_demissao" class="block text-sm font-medium text-gray-700 mb-1">Data de Demissão</label>
                                        <input type="date" name="data_demissao" id="data_demissao" value="<?php echo $funcionario['data_demissao'] ?? ''; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500">
                                    </div>

                                    <!-- Status -->
                                    <div>
                                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                        <select name="status" id="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500">
                                            <option value="ativo" <?php echo $funcionario['status'] === 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                                            <option value="inativo" <?php echo $funcionario['status'] === 'inativo' ? 'selected' : ''; ?>>Inativo</option>
                                            <option value="afastado" <?php echo $funcionario['status'] === 'afastado' ? 'selected' : ''; ?>>Afastado</option>
                                            <option value="ferias" <?php echo $funcionario['status'] === 'ferias' ? 'selected' : ''; ?>>Férias</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-6" id="secao-dados-bancarios">
                                <div class="mb-2">
                                    <h3 class="text-lg font-medium text-gray-900">Dados Bancários</h3>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Banco -->
                                    <div>
                                        <label for="banco" class="block text-sm font-medium text-gray-700 mb-1">Banco</label>
                                        <input type="text" name="banco" id="banco" value="<?php echo $funcionario['banco'] ?? ''; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500">
                                    </div>

                                    <!-- Agência -->
                                    <div>
                                        <label for="agencia" class="block text-sm font-medium text-gray-700 mb-1">Agência</label>
                                        <input type="text" name="agencia" id="agencia" value="<?php echo $funcionario['agencia'] ?? ''; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500">
                                    </div>

                                    <!-- Conta -->
                                    <div>
                                        <label for="conta" class="block text-sm font-medium text-gray-700 mb-1">Conta</label>
                                        <input type="text" name="conta" id="conta" value="<?php echo $funcionario['conta'] ?? ''; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500">
                                    </div>

                                    <!-- Tipo de Conta -->
                                    <div>
                                        <label for="tipo_conta" class="block text-sm font-medium text-gray-700 mb-1">Tipo de Conta</label>
                                        <select name="tipo_conta" id="tipo_conta" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500">
                                            <option value="">Selecione</option>
                                            <option value="corrente" <?php echo ($funcionario['tipo_conta'] ?? '') === 'corrente' ? 'selected' : ''; ?>>Corrente</option>
                                            <option value="poupanca" <?php echo ($funcionario['tipo_conta'] ?? '') === 'poupanca' ? 'selected' : ''; ?>>Poupança</option>
                                            <option value="salario" <?php echo ($funcionario['tipo_conta'] ?? '') === 'salario' ? 'selected' : ''; ?>>Salário</option>
                                        </select>
                                    </div>

                                    <!-- PIX -->
                                    <div>
                                        <label for="pix" class="block text-sm font-medium text-gray-700 mb-1">PIX</label>
                                        <input type="text" name="pix" id="pix" value="<?php echo $funcionario['pix'] ?? ''; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-6" id="secao-configuracao-pagamento">
                                <div class="mb-2">
                                    <h3 class="text-lg font-medium text-gray-900">Configuração de Pagamento</h3>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Dia de Pagamento -->
                                    <div>
                                        <label for="dia_pagamento" class="block text-sm font-medium text-gray-700 mb-1">Dia de Pagamento</label>
                                        <select name="dia_pagamento" id="dia_pagamento" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500">
                                            <option value="">Selecione</option>
                                            <?php for ($i = 1; $i <= 31; $i++): ?>
                                                <option value="<?php echo $i; ?>" <?php echo ($funcionario['dia_pagamento'] ?? '') == $i ? 'selected' : ''; ?>><?php echo $i; ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>

                                    <!-- Forma de Pagamento -->
                                    <div>
                                        <label for="forma_pagamento" class="block text-sm font-medium text-gray-700 mb-1">Forma de Pagamento</label>
                                        <select name="forma_pagamento" id="forma_pagamento" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500">
                                            <option value="">Selecione</option>
                                            <option value="pix" <?php echo ($funcionario['forma_pagamento'] ?? '') === 'pix' ? 'selected' : ''; ?>>PIX</option>
                                            <option value="transferencia" <?php echo ($funcionario['forma_pagamento'] ?? '') === 'transferencia' ? 'selected' : ''; ?>>Transferência Bancária</option>
                                            <option value="cheque" <?php echo ($funcionario['forma_pagamento'] ?? '') === 'cheque' ? 'selected' : ''; ?>>Cheque</option>
                                            <option value="dinheiro" <?php echo ($funcionario['forma_pagamento'] ?? '') === 'dinheiro' ? 'selected' : ''; ?>>Dinheiro</option>
                                        </select>
                                    </div>

                                    <!-- Gerar Pagamento Automático -->
                                    <div class="md:col-span-2">
                                        <div class="flex items-center">
                                            <input type="checkbox" name="gerar_pagamento_automatico" id="gerar_pagamento_automatico" value="1" <?php echo ($funcionario['gerar_pagamento_automatico'] ?? 0) == 1 ? 'checked' : ''; ?> class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded">
                                            <label for="gerar_pagamento_automatico" class="ml-2 block text-sm text-gray-900">Gerar pagamento automático mensalmente</label>
                                        </div>
                                        <p class="mt-1 text-sm text-gray-500">Se marcado, o sistema irá gerar automaticamente um pagamento no dia especificado a cada mês.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-6" id="secao-observacoes">
                                <div class="mb-2">
                                    <h3 class="text-lg font-medium text-gray-900">Observações</h3>
                                </div>
                                <div>
                                    <textarea name="observacoes" id="observacoes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500"><?php echo $funcionario['observacoes'] ?? ''; ?></textarea>
                                </div>
                            </div>

                            <!-- Botões -->
                            <div class="mt-6 flex justify-end">
                                <a href="funcionarios.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded mr-2">
                                    Cancelar
                                </a>
                                <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded">
                                    Salvar
                                </button>
                            </div>
                        </form>
                        </div>
                    </div>
                </div>
            </main>

            <!-- Footer -->
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Garante que todas as seções do formulário estejam visíveis
            const secoes = document.querySelectorAll('form > div.mb-6');
            secoes.forEach(function(secao) {
                secao.style.display = 'block';
                secao.style.visibility = 'visible';
                secao.style.opacity = '1';
                secao.style.height = 'auto';
                secao.style.overflow = 'visible';
            });

            // Garante especificamente que a seção de Dados Profissionais esteja visível
            const secaoDadosProfissionais = document.getElementById('secao-dados-profissionais');
            if (secaoDadosProfissionais) {
                secaoDadosProfissionais.style.display = 'block';
                secaoDadosProfissionais.style.visibility = 'visible';
                secaoDadosProfissionais.style.opacity = '1';
                secaoDadosProfissionais.style.height = 'auto';
                secaoDadosProfissionais.style.overflow = 'visible';
                secaoDadosProfissionais.style.position = 'relative';
                secaoDadosProfissionais.style.zIndex = '10';

                // Verifica se o campo de salário existe e está visível
                const campoSalario = document.getElementById('salario');
                if (campoSalario) {
                    campoSalario.style.display = 'block';
                    campoSalario.style.visibility = 'visible';
                    campoSalario.style.opacity = '1';

                    // Garante que o valor do campo de salário seja válido
                    if (!campoSalario.value || campoSalario.value === 'NaN' || campoSalario.value === 'NaN,NaN') {
                        campoSalario.value = '0,00';
                    }

                    console.log('Campo de salário inicializado com valor:', campoSalario.value);
                } else {
                    console.error('Campo de salário não encontrado!');
                }
            } else {
                console.error('Seção de Dados Profissionais não encontrada!');
            }

            // Ajusta a altura do container de rolagem
            const formContainer = document.querySelector('.form-container');
            if (formContainer) {
                const windowHeight = window.innerHeight;
                const formTop = formContainer.getBoundingClientRect().top;
                const footerHeight = 60; // Altura estimada do footer
                const maxHeight = windowHeight - formTop - footerHeight - 40; // 40px de margem
                formContainer.style.maxHeight = maxHeight + 'px';
            }

            // Remove o botão de debug que não é necessário
            const btnDebug = document.getElementById('btn-debug');
            if (btnDebug) {
                btnDebug.style.display = 'none';
            }
        });

        // Máscara para CPF
        document.getElementById('cpf').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) value = value.slice(0, 11);

            if (value.length > 9) {
                value = value.replace(/^(\d{3})(\d{3})(\d{3})(\d{2}).*/, '$1.$2.$3-$4');
            } else if (value.length > 6) {
                value = value.replace(/^(\d{3})(\d{3})(\d{0,3}).*/, '$1.$2.$3');
            } else if (value.length > 3) {
                value = value.replace(/^(\d{3})(\d{0,3}).*/, '$1.$2');
            }

            e.target.value = value;
        });

        // Máscara para salário
        const campoSalario = document.getElementById('salario');
        if (campoSalario) {
            // Inicializa com um valor válido se necessário
            if (!campoSalario.value || campoSalario.value === 'NaN' || campoSalario.value === 'NaN,NaN') {
                campoSalario.value = '0,00';
            }

            campoSalario.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value === '') {
                    e.target.value = '0,00';
                    return;
                }

                try {
                    value = (parseInt(value) / 100).toFixed(2).replace('.', ',');
                    e.target.value = value;
                } catch (error) {
                    console.error('Erro ao formatar valor do salário:', error);
                    e.target.value = '0,00';
                }
            });

            // Força a formatação inicial
            const event = new Event('input', { bubbles: true });
            campoSalario.dispatchEvent(event);
        } else {
            console.error('Campo de salário não encontrado para aplicar máscara!');
        }

        // Máscara para telefone
        document.getElementById('telefone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) value = value.slice(0, 11);

            if (value.length > 10) {
                value = value.replace(/^(\d{2})(\d{5})(\d{4}).*/, '($1) $2-$3');
            } else if (value.length > 6) {
                value = value.replace(/^(\d{2})(\d{4})(\d{0,4}).*/, '($1) $2-$3');
            } else if (value.length > 2) {
                value = value.replace(/^(\d{2})(\d{0,5}).*/, '($1) $2');
            }

            e.target.value = value;
        });
    </script>
</body>
</html>
