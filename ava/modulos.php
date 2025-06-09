<?php
/**
 * Gerenciamento de Módulos de Curso do AVA
 * Permite gerenciar os módulos de um curso específico do Ambiente Virtual de Aprendizagem
 */

// Inicializa o sistema
require_once '../includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica se o usuário é do tipo polo
if (getUsuarioTipo() !== 'polo') {
    setMensagem('erro', 'Você não tem permissão para acessar esta página.');
    redirect('../polo/index.php');
    exit;
}

// Instancia o banco de dados
$db = Database::getInstance();

// Obtém o ID do polo
$polo_id = getUsuarioPoloId();

// Verifica se o polo tem acesso ao AVA
if (!$polo_id) {
    setMensagem('erro', 'Não foi possível identificar o polo associado ao seu usuário. Entre em contato com a secretaria para mais informações.');
    redirect('../polo/index.php');
    exit;
}

// Verifica se o polo existe
$sql = "SELECT * FROM polos WHERE id = ?";
$polo = $db->fetchOne($sql, [$polo_id]);

if (!$polo) {
    setMensagem('erro', 'Polo não encontrado no sistema. Entre em contato com a secretaria para mais informações.');
    redirect('../polo/index.php');
    exit;
}

// Verifica se o polo tem acesso ao AVA
$sql = "SELECT * FROM ava_polos_acesso WHERE polo_id = ?";
$acesso = $db->fetchOne($sql, [$polo_id]);

if (!$acesso || $acesso['liberado'] != 1) {
    setMensagem('erro', 'Seu polo não possui acesso liberado ao AVA. Entre em contato com a secretaria para mais informações.');
    redirect('../polo/index.php');
    exit;
}

// Verifica se o ID do curso foi informado
if (!isset($_GET['curso_id']) || empty($_GET['curso_id'])) {
    setMensagem('erro', 'Curso não informado.');
    redirect('cursos.php');
    exit;
}

$curso_id = (int)$_GET['curso_id'];

// Busca o curso
$sql = "SELECT * FROM ava_cursos WHERE id = ? AND polo_id = ?";
$curso = $db->fetchOne($sql, [$curso_id, $polo_id]);

if (!$curso) {
    setMensagem('erro', 'Curso não encontrado ou você não tem permissão para acessá-lo.');
    redirect('cursos.php');
    exit;
}

// Verifica se a tabela ava_modulos existe
$sql_check = "SHOW TABLES LIKE 'ava_modulos'";
$tabela_modulos_existe = $db->fetchOne($sql_check);

if (!$tabela_modulos_existe) {
    // Cria a tabela ava_modulos
    $sql = "CREATE TABLE IF NOT EXISTS ava_modulos (
        id INT(11) NOT NULL AUTO_INCREMENT,
        curso_id INT(11) NOT NULL,
        titulo VARCHAR(255) NOT NULL,
        descricao TEXT NULL,
        ordem INT(11) NOT NULL DEFAULT 0,
        status ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY idx_curso_id (curso_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    try {
        $db->query($sql);
    } catch (Exception $e) {
        setMensagem('erro', 'Erro ao criar tabela de módulos: ' . $e->getMessage());
        redirect("curso_visualizar.php?id=$curso_id");
        exit;
    }
}

// Busca os módulos do curso
$sql = "SELECT * FROM ava_modulos WHERE curso_id = ? ORDER BY ordem, id";
$modulos = $db->fetchAll($sql, [$curso_id]);

// Processa o formulário de adição de módulo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['adicionar_modulo'])) {
        $titulo = $_POST['titulo'] ?? '';
        $descricao = $_POST['descricao'] ?? '';
        $ordem = (int)($_POST['ordem'] ?? 0);
        $status = $_POST['status'] ?? 'ativo';

        // Validação
        $erros = [];

        if (empty($titulo)) {
            $erros[] = 'O título do módulo é obrigatório.';
        }

        if (empty($erros)) {
            try {
                $dados = [
                    'curso_id' => $curso_id,
                    'titulo' => $titulo,
                    'descricao' => $descricao,
                    'ordem' => $ordem,
                    'status' => $status,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                $db->insert('ava_modulos', $dados);

                setMensagem('sucesso', 'Módulo adicionado com sucesso!');
                redirect("modulos.php?curso_id=$curso_id");
                exit;
            } catch (Exception $e) {
                $erros[] = 'Erro ao adicionar módulo: ' . $e->getMessage();
            }
        }

        if (!empty($erros)) {
            setMensagem('erro', implode('<br>', $erros));
        }
    } elseif (isset($_POST['editar_modulo'])) {
        $modulo_id = (int)$_POST['modulo_id'];
        $titulo = $_POST['titulo'] ?? '';
        $descricao = $_POST['descricao'] ?? '';
        $ordem = (int)($_POST['ordem'] ?? 0);
        $status = $_POST['status'] ?? 'ativo';

        // Validação
        $erros = [];

        if (empty($titulo)) {
            $erros[] = 'O título do módulo é obrigatório.';
        }

        // Verifica se o módulo existe e pertence ao curso
        $sql = "SELECT * FROM ava_modulos WHERE id = ? AND curso_id = ?";
        $modulo = $db->fetchOne($sql, [$modulo_id, $curso_id]);

        if (!$modulo) {
            $erros[] = 'Módulo não encontrado ou você não tem permissão para editá-lo.';
        }

        if (empty($erros)) {
            try {
                $dados = [
                    'titulo' => $titulo,
                    'descricao' => $descricao,
                    'ordem' => $ordem,
                    'status' => $status,
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                $db->update('ava_modulos', $dados, "id = ?", [$modulo_id]);

                setMensagem('sucesso', 'Módulo atualizado com sucesso!');
                redirect("modulos.php?curso_id=$curso_id");
                exit;
            } catch (Exception $e) {
                $erros[] = 'Erro ao atualizar módulo: ' . $e->getMessage();
            }
        }

        if (!empty($erros)) {
            setMensagem('erro', implode('<br>', $erros));
        }
    } elseif (isset($_POST['excluir_modulo'])) {
        $modulo_id = (int)$_POST['modulo_id'];

        // Verifica se o módulo existe e pertence ao curso
        $sql = "SELECT * FROM ava_modulos WHERE id = ? AND curso_id = ?";
        $modulo = $db->fetchOne($sql, [$modulo_id, $curso_id]);

        if (!$modulo) {
            setMensagem('erro', 'Módulo não encontrado ou você não tem permissão para excluí-lo.');
            redirect("modulos.php?curso_id=$curso_id");
            exit;
        }

        // Verifica se existem aulas associadas ao módulo
        $sql_check = "SHOW TABLES LIKE 'ava_aulas'";
        $tabela_aulas_existe = $db->fetchOne($sql_check);

        if ($tabela_aulas_existe) {
            $sql = "SELECT COUNT(*) as total FROM ava_aulas WHERE modulo_id = ?";
            $resultado = $db->fetchOne($sql, [$modulo_id]);

            if ($resultado['total'] > 0) {
                setMensagem('erro', 'Não é possível excluir o módulo pois existem aulas associadas a ele. Exclua as aulas primeiro.');
                redirect("modulos.php?curso_id=$curso_id");
                exit;
            }
        }

        try {
            $db->delete('ava_modulos', "id = ?", [$modulo_id]);

            setMensagem('sucesso', 'Módulo excluído com sucesso!');
            redirect("modulos.php?curso_id=$curso_id");
            exit;
        } catch (Exception $e) {
            setMensagem('erro', 'Erro ao excluir módulo: ' . $e->getMessage());
            redirect("modulos.php?curso_id=$curso_id");
            exit;
        }
    }
}

// Define o título da página
$titulo_pagina = 'Gerenciar Módulos';

// Módulo para edição (se houver)
$modulo_edicao = null;
if (isset($_GET['editar']) && !empty($_GET['editar'])) {
    $modulo_id = (int)$_GET['editar'];

    $sql = "SELECT * FROM ava_modulos WHERE id = ? AND curso_id = ?";
    $modulo_edicao = $db->fetchOne($sql, [$modulo_id, $curso_id]);

    if (!$modulo_edicao) {
        setMensagem('erro', 'Módulo não encontrado ou você não tem permissão para editá-lo.');
        redirect("modulos.php?curso_id=$curso_id");
        exit;
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
        .module-card {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 4px solid #6A5ACD;
        }
        .module-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        .module-title {
            font-weight: 600;
            color: #111827;
            font-size: 1.1rem;
        }
        .module-status {
            font-size: 0.75rem;
            padding: 0.125rem 0.5rem;
            border-radius: 9999px;
        }
        .module-status-ativo { background-color: #D1FAE5; color: #059669; }
        .module-status-inativo { background-color: #FEE2E2; color: #DC2626; }
        .module-description {
            color: #6B7280;
            font-size: 0.875rem;
            margin-bottom: 0.75rem;
        }
        .module-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.75rem;
            color: #6B7280;
        }
        .module-order {
            background-color: #F3F4F6;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
        }
        .module-actions {
            display: flex;
            gap: 0.5rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }
        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: #4B5563;
            margin-bottom: 0.5rem;
        }
        .form-input {
            width: 100%;
            padding: 0.5rem 0.75rem;
            border: 1px solid #D1D5DB;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            color: #1F2937;
        }
        .form-input:focus {
            outline: none;
            border-color: #6A5ACD;
            box-shadow: 0 0 0 3px rgba(106, 90, 205, 0.1);
        }
        .form-textarea {
            width: 100%;
            padding: 0.5rem 0.75rem;
            border: 1px solid #D1D5DB;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            color: #1F2937;
            min-height: 100px;
            resize: vertical;
        }
        .form-textarea:focus {
            outline: none;
            border-color: #6A5ACD;
            box-shadow: 0 0 0 3px rgba(106, 90, 205, 0.1);
        }
        .form-select {
            width: 100%;
            padding: 0.5rem 0.75rem;
            border: 1px solid #D1D5DB;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            color: #1F2937;
            background-color: white;
        }
        .form-select:focus {
            outline: none;
            border-color: #6A5ACD;
            box-shadow: 0 0 0 3px rgba(106, 90, 205, 0.1);
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
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-800"><?php echo $titulo_pagina; ?></h1>
                            <p class="text-gray-600">Gerenciar módulos do curso: <strong><?php echo htmlspecialchars($curso['titulo']); ?></strong></p>
                        </div>
                        <div class="flex space-x-2">
                            <a href="curso_visualizar.php?id=<?php echo $curso_id; ?>" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                <i class="fas fa-arrow-left mr-2"></i> Voltar
                            </a>
                        </div>
                    </div>

                    <?php if (isset($_SESSION['mensagem']) && isset($_SESSION['mensagem_tipo'])): ?>
                    <div class="bg-<?php echo $_SESSION['mensagem_tipo'] === 'sucesso' ? 'green' : 'red'; ?>-100 border-l-4 border-<?php echo $_SESSION['mensagem_tipo'] === 'sucesso' ? 'green' : 'red'; ?>-500 text-<?php echo $_SESSION['mensagem_tipo'] === 'sucesso' ? 'green' : 'red'; ?>-700 p-4 mb-6">
                        <?php echo is_array($_SESSION['mensagem']) ? implode(', ', $_SESSION['mensagem']) : $_SESSION['mensagem']; ?>
                    </div>
                    <?php
                    // Limpa a mensagem da sessão
                    unset($_SESSION['mensagem']);
                    unset($_SESSION['mensagem_tipo']);
                    endif;
                    ?>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Coluna da Esquerda - Lista de Módulos -->
                        <div class="lg:col-span-2">
                            <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
                                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                                    <h2 class="text-lg font-semibold text-gray-800">Módulos do Curso (<?php echo count($modulos); ?>)</h2>
                                    <?php if (!$modulo_edicao): ?>
                                    <a href="#form-modulo" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                                        <i class="fas fa-plus mr-2"></i> Novo Módulo
                                    </a>
                                    <?php endif; ?>
                                </div>
                                <div class="p-6">
                                    <?php if (empty($modulos)): ?>
                                    <div class="text-center text-gray-500 py-4">
                                        <p>Nenhum módulo cadastrado para este curso.</p>
                                        <a href="#form-modulo" class="inline-flex items-center mt-4 px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                                            <i class="fas fa-plus mr-2"></i> Adicionar Módulo
                                        </a>
                                    </div>
                                    <?php else: ?>
                                    <div class="space-y-4">
                                        <?php foreach ($modulos as $modulo): ?>
                                        <div class="module-card">
                                            <div class="module-header">
                                                <h3 class="module-title"><?php echo htmlspecialchars($modulo['titulo']); ?></h3>
                                                <span class="module-status module-status-<?php echo $modulo['status']; ?>">
                                                    <?php echo $modulo['status'] === 'ativo' ? 'Ativo' : 'Inativo'; ?>
                                                </span>
                                            </div>
                                            <div class="module-description">
                                                <?php echo !empty($modulo['descricao']) ? nl2br(htmlspecialchars($modulo['descricao'])) : '<em class="text-gray-400">Sem descrição</em>'; ?>
                                            </div>
                                            <div class="module-meta">
                                                <div>
                                                    <span class="module-order">Ordem: <?php echo $modulo['ordem']; ?></span>
                                                    <span class="ml-2">Criado em: <?php echo date('d/m/Y', strtotime($modulo['created_at'])); ?></span>
                                                </div>
                                                <div class="module-actions">
                                                    <a href="aulas.php?modulo_id=<?php echo $modulo['id']; ?>" class="inline-flex items-center px-2 py-1 bg-blue-100 text-blue-800 text-xs font-medium rounded hover:bg-blue-200">
                                                        <i class="fas fa-book mr-1"></i> Aulas
                                                    </a>
                                                    <a href="modulos.php?curso_id=<?php echo $curso_id; ?>&editar=<?php echo $modulo['id']; ?>#form-modulo" class="inline-flex items-center px-2 py-1 bg-indigo-100 text-indigo-800 text-xs font-medium rounded hover:bg-indigo-200">
                                                        <i class="fas fa-edit mr-1"></i> Editar
                                                    </a>
                                                    <form method="post" class="inline" onsubmit="return confirm('Tem certeza que deseja excluir este módulo?');">
                                                        <input type="hidden" name="modulo_id" value="<?php echo $modulo['id']; ?>">
                                                        <button type="submit" name="excluir_modulo" class="inline-flex items-center px-2 py-1 bg-red-100 text-red-800 text-xs font-medium rounded hover:bg-red-200">
                                                            <i class="fas fa-trash mr-1"></i> Excluir
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Coluna da Direita - Formulário de Módulo -->
                        <div>
                            <div id="form-modulo" class="bg-white rounded-xl shadow-sm overflow-hidden">
                                <div class="px-6 py-4 border-b border-gray-200">
                                    <h2 class="text-lg font-semibold text-gray-800">
                                        <?php echo $modulo_edicao ? 'Editar Módulo' : 'Novo Módulo'; ?>
                                    </h2>
                                </div>
                                <div class="p-6">
                                    <form method="post" action="modulos.php?curso_id=<?php echo $curso_id; ?>">
                                        <?php if ($modulo_edicao): ?>
                                        <input type="hidden" name="modulo_id" value="<?php echo $modulo_edicao['id']; ?>">
                                        <?php endif; ?>

                                        <div class="form-group">
                                            <label for="titulo" class="form-label">Título <span class="text-red-500">*</span></label>
                                            <input type="text" id="titulo" name="titulo" class="form-input" value="<?php echo $modulo_edicao ? htmlspecialchars($modulo_edicao['titulo']) : ''; ?>" required>
                                        </div>

                                        <div class="form-group">
                                            <label for="descricao" class="form-label">Descrição</label>
                                            <textarea id="descricao" name="descricao" class="form-textarea"><?php echo $modulo_edicao ? htmlspecialchars($modulo_edicao['descricao']) : ''; ?></textarea>
                                        </div>

                                        <div class="grid grid-cols-2 gap-4">
                                            <div class="form-group">
                                                <label for="ordem" class="form-label">Ordem</label>
                                                <input type="number" id="ordem" name="ordem" class="form-input" value="<?php echo $modulo_edicao ? $modulo_edicao['ordem'] : count($modulos); ?>" min="0">
                                            </div>

                                            <div class="form-group">
                                                <label for="status" class="form-label">Status</label>
                                                <select id="status" name="status" class="form-select">
                                                    <option value="ativo" <?php echo $modulo_edicao && $modulo_edicao['status'] === 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                                                    <option value="inativo" <?php echo $modulo_edicao && $modulo_edicao['status'] === 'inativo' ? 'selected' : ''; ?>>Inativo</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="flex justify-end space-x-2 mt-6">
                                            <?php if ($modulo_edicao): ?>
                                            <a href="modulos.php?curso_id=<?php echo $curso_id; ?>" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                                Cancelar
                                            </a>
                                            <button type="submit" name="editar_modulo" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                                                <i class="fas fa-save mr-2"></i> Salvar Alterações
                                            </button>
                                            <?php else: ?>
                                            <button type="submit" name="adicionar_modulo" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                                                <i class="fas fa-plus mr-2"></i> Adicionar Módulo
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </form>
                                </div>
                            </div>
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
            sidebar.classList.toggle('sidebar-collapsed');
            sidebar.classList.toggle('sidebar-expanded');

            const labels = document.querySelectorAll('.sidebar-label');
            labels.forEach(label => {
                label.classList.toggle('hidden');
            });
        });

        // Toggle user menu
        document.getElementById('user-menu-button').addEventListener('click', function() {
            const menu = document.getElementById('user-menu');
            menu.classList.toggle('hidden');
        });
    </script>
</body>
</html>