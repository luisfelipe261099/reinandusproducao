<?php
/**
 * Gestão de Funcionários - Módulo Financeiro
 */

require_once '../includes/init.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

// Verifica autenticação e permissão
Auth::requireLogin();
$userType = Auth::getUserType();
if (!in_array($userType, ['financeiro', 'admin_master'])) {
    $_SESSION['error'] = 'Você não tem permissão para acessar o módulo financeiro.';
    header('Location: ../index.php');
    exit;
}

$db = Database::getInstance();
$action = $_GET['action'] ?? 'listar';
$funcionarioId = $_GET['id'] ?? null;

// Processa ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'salvar') {
        try {
            $dados = [
                'nome' => $_POST['nome'],
                'cpf' => $_POST['cpf'],
                'rg' => $_POST['rg'] ?? null,
                'data_nascimento' => $_POST['data_nascimento'] ?? null,
                'data_admissao' => $_POST['data_admissao'],
                'cargo' => $_POST['cargo'],
                'departamento' => $_POST['departamento'] ?? null,
                'salario' => str_replace(['R$', '.', ','], ['', '', '.'], $_POST['salario']),
                'banco' => $_POST['banco'] ?? null,
                'agencia' => $_POST['agencia'] ?? null,
                'conta' => $_POST['conta'] ?? null,
                'tipo_conta' => $_POST['tipo_conta'] ?? null,
                'pix' => $_POST['pix'] ?? null,
                'email' => $_POST['email'] ?? null,
                'telefone' => $_POST['telefone'] ?? null,
                'endereco' => $_POST['endereco'] ?? null,
                'cidade' => $_POST['cidade'] ?? null,
                'estado' => $_POST['estado'] ?? null,
                'cep' => $_POST['cep'] ?? null,
                'status' => $_POST['status'] ?? 'ativo'
            ];
            
            if ($funcionarioId) {
                $dados['updated_at'] = date('Y-m-d H:i:s');
                $db->update('funcionarios', $dados, 'id = ?', [$funcionarioId]);
                $_SESSION['success'] = 'Funcionário atualizado com sucesso!';
            } else {
                $db->insert('funcionarios', $dados);
                $_SESSION['success'] = 'Funcionário cadastrado com sucesso!';
            }
            
            header('Location: funcionarios.php');
            exit;
            
        } catch (Exception $e) {
            $_SESSION['error'] = 'Erro ao salvar funcionário: ' . $e->getMessage();
        }
    }
    
    if ($action === 'excluir' && $funcionarioId) {
        try {
            $db->update('funcionarios', ['status' => 'inativo'], 'id = ?', [$funcionarioId]);
            $_SESSION['success'] = 'Funcionário inativado com sucesso!';
            header('Location: funcionarios.php');
            exit;
        } catch (Exception $e) {
            $_SESSION['error'] = 'Erro ao inativar funcionário: ' . $e->getMessage();
        }
    }
}

// Busca dados para exibição
if ($action === 'editar' && $funcionarioId) {
    $funcionario = $db->fetchOne("SELECT * FROM funcionarios WHERE id = ?", [$funcionarioId]);
    if (!$funcionario) {
        $_SESSION['error'] = 'Funcionário não encontrado.';
        header('Location: funcionarios.php');
        exit;
    }
}

if ($action === 'listar') {
    $filtro = $_GET['filtro'] ?? '';
    $busca = $_GET['busca'] ?? '';
    
    $where = "1=1";
    $params = [];
    
    if ($filtro === 'ativos') {
        $where .= " AND status = 'ativo'";
    } elseif ($filtro === 'inativos') {
        $where .= " AND status = 'inativo'";
    }
    
    if ($busca) {
        $where .= " AND (nome LIKE ? OR cpf LIKE ? OR cargo LIKE ?)";
        $params[] = "%$busca%";
        $params[] = "%$busca%";
        $params[] = "%$busca%";
    }
    
    $funcionarios = $db->fetchAll("
        SELECT * FROM funcionarios 
        WHERE $where 
        ORDER BY nome ASC
    ", $params);
}

$pageTitle = 'Gestão de Funcionários';
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
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col ml-64">
            <!-- Header -->
            <?php include 'includes/header.php'; ?>
            
            <!-- Content -->
            <main class="flex-1 p-6 overflow-y-auto">
                <div class="max-w-7xl mx-auto">
                    
                    <?php if ($action === 'listar'): ?>
                    <!-- Listagem de Funcionários -->
                    <div class="mb-8">
                        <div class="flex justify-between items-center mb-6">
                            <div>
                                <h1 class="text-3xl font-bold text-gray-900">Funcionários</h1>
                                <p class="text-gray-600 mt-2">Gerencie os funcionários da instituição</p>
                            </div>
                            <a href="funcionarios.php?action=novo" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                                <i class="fas fa-plus mr-2"></i>Novo Funcionário
                            </a>
                        </div>

                        <!-- Filtros -->
                        <div class="bg-white rounded-lg shadow p-4 mb-6">
                            <form method="GET" class="flex flex-wrap gap-4 items-end">
                                <input type="hidden" name="action" value="listar">
                                <div class="flex-1 min-w-64">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                                    <input type="text" name="busca" value="<?php echo htmlspecialchars($_GET['busca'] ?? ''); ?>" 
                                           placeholder="Nome, CPF ou cargo..." 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                    <select name="filtro" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                        <option value="">Todos</option>
                                        <option value="ativos" <?php echo ($_GET['filtro'] ?? '') === 'ativos' ? 'selected' : ''; ?>>Ativos</option>
                                        <option value="inativos" <?php echo ($_GET['filtro'] ?? '') === 'inativos' ? 'selected' : ''; ?>>Inativos</option>
                                    </select>
                                </div>
                                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                                    <i class="fas fa-search mr-2"></i>Buscar
                                </button>
                            </form>
                        </div>

                        <!-- Tabela -->
                        <div class="bg-white rounded-lg shadow overflow-hidden">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-green-600">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Nome</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">CPF</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Cargo</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Salário</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Ações</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php if (empty($funcionarios)): ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                            Nenhum funcionário encontrado.
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($funcionarios as $funcionario): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($funcionario['nome']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($funcionario['email'] ?? ''); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo htmlspecialchars($funcionario['cpf']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?php echo htmlspecialchars($funcionario['cargo']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($funcionario['departamento'] ?? ''); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            R$ <?php echo number_format($funcionario['salario'], 2, ',', '.'); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                <?php echo $funcionario['status'] === 'ativo' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                <?php echo ucfirst($funcionario['status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="funcionarios.php?action=editar&id=<?php echo $funcionario['id']; ?>" 
                                               class="text-green-600 hover:text-green-900 mr-3">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($funcionario['status'] === 'ativo'): ?>
                                            <button onclick="confirmarExclusao(<?php echo $funcionario['id']; ?>)" 
                                                    class="text-red-600 hover:text-red-900">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <?php else: ?>
                    <!-- Formulário de Funcionário -->
                    <div class="mb-8">
                        <div class="flex items-center mb-6">
                            <a href="funcionarios.php" class="text-green-600 hover:text-green-800 mr-4">
                                <i class="fas fa-arrow-left"></i>
                            </a>
                            <div>
                                <h1 class="text-3xl font-bold text-gray-900">
                                    <?php echo $action === 'novo' ? 'Novo Funcionário' : 'Editar Funcionário'; ?>
                                </h1>
                                <p class="text-gray-600 mt-2">Preencha os dados do funcionário</p>
                            </div>
                        </div>

                        <form method="POST" class="bg-white rounded-lg shadow p-6">
                            <input type="hidden" name="action" value="salvar">
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Dados Pessoais -->
                                <div class="md:col-span-2">
                                    <h3 class="text-lg font-medium text-gray-900 mb-4">Dados Pessoais</h3>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome Completo *</label>
                                    <input type="text" name="nome" required 
                                           value="<?php echo htmlspecialchars($funcionario['nome'] ?? ''); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">CPF *</label>
                                    <input type="text" name="cpf" required data-mask="cpf"
                                           value="<?php echo htmlspecialchars($funcionario['cpf'] ?? ''); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">RG</label>
                                    <input type="text" name="rg" 
                                           value="<?php echo htmlspecialchars($funcionario['rg'] ?? ''); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Data de Nascimento</label>
                                    <input type="date" name="data_nascimento" 
                                           value="<?php echo $funcionario['data_nascimento'] ?? ''; ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                </div>
                                
                                <!-- Dados Profissionais -->
                                <div class="md:col-span-2 mt-6">
                                    <h3 class="text-lg font-medium text-gray-900 mb-4">Dados Profissionais</h3>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Data de Admissão *</label>
                                    <input type="date" name="data_admissao" required 
                                           value="<?php echo $funcionario['data_admissao'] ?? ''; ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Cargo *</label>
                                    <input type="text" name="cargo" required 
                                           value="<?php echo htmlspecialchars($funcionario['cargo'] ?? ''); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Departamento</label>
                                    <input type="text" name="departamento" 
                                           value="<?php echo htmlspecialchars($funcionario['departamento'] ?? ''); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Salário *</label>
                                    <input type="text" name="salario" required data-mask="currency"
                                           value="<?php echo isset($funcionario['salario']) ? 'R$ ' . number_format($funcionario['salario'], 2, ',', '.') : ''; ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                    <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                        <option value="ativo" <?php echo ($funcionario['status'] ?? 'ativo') === 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                                        <option value="inativo" <?php echo ($funcionario['status'] ?? '') === 'inativo' ? 'selected' : ''; ?>>Inativo</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mt-8 flex justify-end space-x-3">
                                <a href="funcionarios.php" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400">
                                    Cancelar
                                </a>
                                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                                    <i class="fas fa-save mr-2"></i>Salvar
                                </button>
                            </div>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Scripts -->
    <script src="js/financeiro.js"></script>
    <script>
    function confirmarExclusao(id) {
        if (confirm('Tem certeza que deseja inativar este funcionário?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="excluir">
            `;
            document.body.appendChild(form);
            form.submit();
            window.location.href = `funcionarios.php?action=excluir&id=${id}`;
        }
    }
    </script>
</body>
</html>
