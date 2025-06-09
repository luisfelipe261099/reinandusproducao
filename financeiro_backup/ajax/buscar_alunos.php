<?php
// Inicializa o sistema
require_once __DIR__ . '/../../includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica se o usuário tem permissão para acessar o módulo financeiro
if (!Auth::hasPermission('financeiro', 'visualizar')) {
    header('Content-Type: application/json');
    echo json_encode(['erro' => 'Sem permissão']);
    exit;
}

// Obtém os parâmetros da busca
$termo = isset($_GET['termo']) ? trim($_GET['termo']) : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$por_pagina = 20; // Número de resultados por página

// Instancia o banco de dados
$db = Database::getInstance();

// Constrói a consulta SQL com filtros
$where = [];
$params = [];

if (!empty($termo)) {
    $where[] = "(a.nome LIKE ? OR a.cpf LIKE ? OR a.email LIKE ?)";
    $params[] = "%{$termo}%";
    $params[] = "%{$termo}%";
    $params[] = "%{$termo}%";
}

if (!empty($status)) {
    $where[] = "a.status = ?";
    $params[] = $status;
}

// Calcula o offset para paginação
$offset = ($pagina - 1) * $por_pagina;

// Constrói a consulta SQL
$sql = "SELECT a.id, a.nome, a.cpf, a.email, a.telefone, a.status,
        (SELECT m.id FROM matriculas m WHERE m.aluno_id = a.id ORDER BY m.id DESC LIMIT 1) as matricula_id,
        (SELECT c.nome FROM matriculas m JOIN cursos c ON m.curso_id = c.id WHERE m.aluno_id = a.id ORDER BY m.id DESC LIMIT 1) as curso_nome,
        (SELECT p.nome FROM matriculas m JOIN polos p ON m.polo_id = p.id WHERE m.aluno_id = a.id ORDER BY m.id DESC LIMIT 1) as polo_nome
        FROM alunos a";

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY a.nome LIMIT {$offset}, {$por_pagina}";

// Executa a consulta
$alunos = $db->fetchAll($sql, $params);

// Conta o total de resultados para paginação
$sql_count = "SELECT COUNT(*) as total FROM alunos a";
if (!empty($where)) {
    $sql_count .= " WHERE " . implode(" AND ", $where);
}
$total_result = $db->fetchOne($sql_count, $params);
$total = $total_result['total'] ?? 0;
$total_paginas = ceil($total / $por_pagina);

// Prepara o HTML dos resultados
$html = '';

if (empty($alunos)) {
    $html = '<p class="text-gray-500 p-3">Nenhum aluno encontrado.</p>';
} else {
    $html .= '<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">';

    foreach ($alunos as $aluno) {
        $html .= '<div class="flex items-start">';
        $html .= '<input type="checkbox" name="aluno_ids[]" id="aluno_' . $aluno['id'] . '" value="' . $aluno['id'] . '" class="mt-1 aluno-checkbox" form="form-mensalidades-recorrentes">';
        $html .= '<label for="aluno_' . $aluno['id'] . '" class="ml-2 text-sm text-gray-700">';
        $html .= '<span class="font-medium">' . htmlspecialchars($aluno['nome']) . '</span>';

        if (!empty($aluno['cpf'])) {
            $html .= '<br><span class="text-xs text-gray-500">CPF: ' . htmlspecialchars($aluno['cpf']) . '</span>';
        }

        if (!empty($aluno['curso_nome'])) {
            $html .= '<br><span class="text-xs text-gray-500">Curso: ' . htmlspecialchars($aluno['curso_nome']) . '</span>';
        }

        if (!empty($aluno['status'])) {
            $status_class = $aluno['status'] === 'ativo' ? 'text-green-600' : 'text-red-600';
            $html .= '<br><span class="text-xs ' . $status_class . '">Status: ' . ucfirst(htmlspecialchars($aluno['status'])) . '</span>';
        }

        $html .= '</label>';
        $html .= '</div>';
    }

    $html .= '</div>';

    // Adiciona paginação se necessário
    if ($total_paginas > 1) {
        $html .= '<div class="mt-4 flex justify-between items-center">';
        $html .= '<div class="text-sm text-gray-600">Página ' . $pagina . ' de ' . $total_paginas . '</div>';
        $html .= '<div class="flex space-x-1">';

        // Botões de navegação
        if ($pagina > 1) {
            $html .= '<button type="button" class="px-3 py-1 rounded-md bg-gray-200 text-gray-700 hover:bg-gray-300 pagina-btn" data-pagina="1"><i class="fas fa-angle-double-left"></i></button>';
            $html .= '<button type="button" class="px-3 py-1 rounded-md bg-gray-200 text-gray-700 hover:bg-gray-300 pagina-btn" data-pagina="' . ($pagina - 1) . '"><i class="fas fa-angle-left"></i></button>';
        }

        // Números de página
        $inicio = max(1, $pagina - 2);
        $fim = min($total_paginas, $pagina + 2);

        for ($i = $inicio; $i <= $fim; $i++) {
            $active_class = $i === $pagina ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300';
            $html .= '<button type="button" class="px-3 py-1 rounded-md ' . $active_class . ' pagina-btn" data-pagina="' . $i . '">' . $i . '</button>';
        }

        if ($pagina < $total_paginas) {
            $html .= '<button type="button" class="px-3 py-1 rounded-md bg-gray-200 text-gray-700 hover:bg-gray-300 pagina-btn" data-pagina="' . ($pagina + 1) . '"><i class="fas fa-angle-right"></i></button>';
            $html .= '<button type="button" class="px-3 py-1 rounded-md bg-gray-200 text-gray-700 hover:bg-gray-300 pagina-btn" data-pagina="' . $total_paginas . '"><i class="fas fa-angle-double-right"></i></button>';
        }

        $html .= '</div>';
        $html .= '</div>';
    }
}

// Retorna os resultados em formato JSON
header('Content-Type: application/json');
echo json_encode([
    'html' => $html,
    'total' => $total,
    'paginas' => $total_paginas
]);
