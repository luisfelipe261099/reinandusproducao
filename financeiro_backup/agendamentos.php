<?php
/**
 * Página para gerenciar agendamentos de pagamentos
 */

// Inclui os arquivos necessários
require_once __DIR__ . '/../includes/init.php';

// Verifica se o usuário está logado
exigirLogin();

// Verifica se o usuário tem permissão para acessar esta página
if (!Auth::hasPermission('financeiro', 'visualizar')) {
    setMensagem('erro', 'Você não tem permissão para acessar esta página.');
    redirect('index.php');
    exit;
}

// Inicializa a conexão com o banco de dados
$db = Database::getInstance();

// Verifica a ação a ser executada
$action = isset($_GET['action']) ? $_GET['action'] : 'listar';

// Processa as ações
switch ($action) {
    case 'gerar_pagamentos':
        // Verifica se o usuário tem permissão para editar
        if (!Auth::hasPermission('financeiro', 'editar')) {
            setMensagem('erro', 'Você não tem permissão para gerar pagamentos.');
            redirect('agendamentos.php');
            exit;
        }

        try {
            // Inicia uma transação
            $db->beginTransaction();

            // Busca todos os agendamentos ativos
            $agendamentos = $db->fetchAll("SELECT a.*, f.nome as funcionario_nome
                                          FROM agendamentos_pagamentos a
                                          JOIN funcionarios f ON a.funcionario_id = f.id
                                          WHERE a.status = 'ativo'");

            $mes_atual = date('Y-m-01');
            $ultimo_dia_mes = date('Y-m-t');
            $contador = 0;

            foreach ($agendamentos as $agendamento) {
                // Calcula a data de vencimento para o mês atual
                $dia_vencimento = min((int)$agendamento['dia_vencimento'], (int)date('t'));
                $data_vencimento = date('Y-m-d', strtotime($mes_atual . ' + ' . ($dia_vencimento - 1) . ' days'));

                // Verifica se já existe uma conta a pagar para este agendamento no mês atual
                $conta_existente = $db->fetchOne("SELECT * FROM contas_pagar
                                                 WHERE fornecedor_nome = ?
                                                 AND data_vencimento BETWEEN ? AND ?
                                                 AND descricao LIKE ?",
                                                 [
                                                     $agendamento['funcionario_nome'],
                                                     $mes_atual,
                                                     $ultimo_dia_mes,
                                                     '%' . $agendamento['tipo'] . '%'
                                                 ]);

                if (!$conta_existente) {
                    // Busca a categoria de folha de pagamento ou salários
                    $categoria = $db->fetchOne("SELECT id FROM categorias_financeiras WHERE tipo = 'despesa' AND (nome LIKE '%folha%' OR nome LIKE '%salário%' OR nome LIKE '%salario%') LIMIT 1");
                    $categoria_id = $categoria ? $categoria['id'] : null;

                    // Cria uma nova conta a pagar
                    $conta_pagar = [
                        'descricao' => 'Pagamento de ' . $agendamento['tipo'] . ' - ' . $agendamento['funcionario_nome'],
                        'valor' => $agendamento['valor'],
                        'data_vencimento' => $data_vencimento,
                        'categoria_id' => $categoria_id,
                        'fornecedor_nome' => $agendamento['funcionario_nome'],
                        'forma_pagamento' => $agendamento['forma_pagamento'],
                        'status' => 'pendente',
                        'observacoes' => 'Gerado automaticamente pelo sistema de agendamentos',
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];

                    try {
                        error_log("Tentando inserir conta a pagar: " . json_encode($conta_pagar));
                        $conta_id = $db->insert('contas_pagar', $conta_pagar);
                        error_log("Conta a pagar inserida com sucesso. ID: " . $conta_id);
                        $contador++;
                    } catch (Exception $e) {
                        error_log("Erro ao inserir conta a pagar: " . $e->getMessage());
                        throw $e;
                    }
                }
            }

            // Confirma a transação
            $db->commit();

            if ($contador > 0) {
                setMensagem('sucesso', "Foram gerados $contador pagamentos para o mês atual.");
            } else {
                setMensagem('aviso', "Não foram gerados novos pagamentos. Todos os agendamentos já possuem contas a pagar para este mês.");
            }
        } catch (Exception $e) {
            // Reverte a transação em caso de erro
            $db->rollBack();
            setMensagem('erro', 'Erro ao gerar pagamentos: ' . $e->getMessage());
        }

        redirect('agendamentos.php');
        break;

    case 'ativar':
    case 'desativar':
        // Verifica se o usuário tem permissão para editar
        if (!Auth::hasPermission('financeiro', 'editar')) {
            setMensagem('erro', 'Você não tem permissão para alterar agendamentos.');
            redirect('agendamentos.php');
            exit;
        }

        // Verifica se o ID foi informado
        if (!isset($_GET['id'])) {
            setMensagem('erro', 'ID do agendamento não informado.');
            redirect('agendamentos.php');
            exit;
        }

        $id = (int)$_GET['id'];
        $status = ($action === 'ativar') ? 'ativo' : 'inativo';

        try {
            // Atualiza o status do agendamento
            $result = $db->update('agendamentos_pagamentos',
                                 ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')],
                                 ['id' => $id]);

            if ($result) {
                $mensagem = ($action === 'ativar') ? 'Agendamento ativado com sucesso.' : 'Agendamento desativado com sucesso.';
                setMensagem('sucesso', $mensagem);
            } else {
                setMensagem('erro', 'Erro ao atualizar o agendamento.');
            }
        } catch (Exception $e) {
            setMensagem('erro', 'Erro ao atualizar o agendamento: ' . $e->getMessage());
        }

        redirect('agendamentos.php');
        break;

    case 'listar':
    default:
        // Busca todos os agendamentos
        $agendamentos = $db->fetchAll("SELECT a.*, f.nome as funcionario_nome
                                      FROM agendamentos_pagamentos a
                                      JOIN funcionarios f ON a.funcionario_id = f.id
                                      ORDER BY a.status DESC, f.nome ASC");

        // Define o título da página
        $titulo_pagina = 'Agendamentos de Pagamentos';
        break;
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

                    <!-- Mensagens -->
                    <?php include __DIR__ . '/../includes/mensagens.php'; ?>

                    <!-- Ações -->
                    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                        <div class="flex flex-wrap items-center justify-between">
                            <div>
                                <h2 class="text-xl font-semibold text-gray-800 mb-2">Gerenciar Agendamentos</h2>
                                <p class="text-gray-600">Gerencie os agendamentos de pagamentos automáticos para funcionários.</p>
                            </div>
                            <div class="mt-4 md:mt-0">
                                <a href="agendamentos.php?action=gerar_pagamentos" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                    <i class="fas fa-money-bill-wave mr-2"></i> Gerar Pagamentos do Mês
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Lista de Agendamentos -->
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <div class="p-6 border-b">
                            <h2 class="text-xl font-bold text-gray-800">Agendamentos Cadastrados</h2>
                        </div>

                        <?php if (empty($agendamentos)): ?>
                            <div class="p-6 text-center text-gray-500">
                                <p>Nenhum agendamento encontrado.</p>
                                <p class="mt-2">
                                    <a href="funcionarios.php" class="text-blue-600 hover:text-blue-800">
                                        <i class="fas fa-user-plus mr-1"></i> Cadastrar Funcionário com Agendamento
                                    </a>
                                </p>
                            </div>
                        <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full bg-white">
                                    <thead>
                                        <tr class="bg-gray-100 text-gray-600 uppercase text-sm leading-normal">
                                            <th class="py-3 px-6 text-left">Funcionário</th>
                                            <th class="py-3 px-6 text-left">Tipo</th>
                                            <th class="py-3 px-6 text-center">Dia do Mês</th>
                                            <th class="py-3 px-6 text-right">Valor</th>
                                            <th class="py-3 px-6 text-center">Forma de Pagamento</th>
                                            <th class="py-3 px-6 text-center">Status</th>
                                            <th class="py-3 px-6 text-center">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody class="text-gray-600 text-sm">
                                        <?php foreach ($agendamentos as $agendamento): ?>
                                            <tr class="border-b border-gray-200 hover:bg-gray-50">
                                                <td class="py-3 px-6">
                                                    <a href="funcionario_form.php?id=<?php echo $agendamento['funcionario_id']; ?>" class="text-blue-600 hover:text-blue-800">
                                                        <?php echo $agendamento['funcionario_nome']; ?>
                                                    </a>
                                                </td>
                                                <td class="py-3 px-6">
                                                    <?php
                                                    $tipos = [
                                                        'salario' => 'Salário',
                                                        'adiantamento' => 'Adiantamento',
                                                        'bonus' => 'Bônus',
                                                        'ferias' => 'Férias',
                                                        '13_salario' => '13º Salário',
                                                        'outros' => 'Outros'
                                                    ];
                                                    echo $tipos[$agendamento['tipo']] ?? $agendamento['tipo'];
                                                    ?>
                                                </td>
                                                <td class="py-3 px-6 text-center"><?php echo $agendamento['dia_vencimento']; ?></td>
                                                <td class="py-3 px-6 text-right">R$ <?php echo number_format($agendamento['valor'], 2, ',', '.'); ?></td>
                                                <td class="py-3 px-6 text-center">
                                                    <?php
                                                    $formas = [
                                                        'pix' => 'PIX',
                                                        'transferencia' => 'Transferência',
                                                        'cheque' => 'Cheque',
                                                        'dinheiro' => 'Dinheiro'
                                                    ];
                                                    echo $formas[$agendamento['forma_pagamento']] ?? $agendamento['forma_pagamento'];
                                                    ?>
                                                </td>
                                                <td class="py-3 px-6 text-center">
                                                    <?php if ($agendamento['status'] === 'ativo'): ?>
                                                        <span class="bg-green-100 text-green-800 py-1 px-3 rounded-full text-xs">Ativo</span>
                                                    <?php else: ?>
                                                        <span class="bg-gray-100 text-gray-800 py-1 px-3 rounded-full text-xs">Inativo</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="py-3 px-6 text-center">
                                                    <div class="flex item-center justify-center">
                                                        <?php if ($agendamento['status'] === 'ativo'): ?>
                                                            <a href="agendamentos.php?action=desativar&id=<?php echo $agendamento['id']; ?>" class="text-yellow-600 hover:text-yellow-900 mx-1" title="Desativar" onclick="return confirm('Tem certeza que deseja desativar este agendamento?');">
                                                                <i class="fas fa-pause-circle"></i>
                                                            </a>
                                                        <?php else: ?>
                                                            <a href="agendamentos.php?action=ativar&id=<?php echo $agendamento['id']; ?>" class="text-green-600 hover:text-green-900 mx-1" title="Ativar">
                                                                <i class="fas fa-play-circle"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                        <a href="funcionario_form.php?id=<?php echo $agendamento['funcionario_id']; ?>" class="text-blue-600 hover:text-blue-900 mx-1" title="Editar Funcionário">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>

            <!-- Footer -->
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>

    <script>
        // Toggle sidebar
        document.getElementById('toggle-sidebar').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('hidden');
        });
    </script>
</body>
</html>
