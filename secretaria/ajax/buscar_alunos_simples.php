<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

$host = 'localhost';
$dbname = 'u682219090_reinandus';
$user = 'u682219090_reinandus';
$pass = 'T3cn0l0g1a@';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Erro ao conectar com o banco: ' . $e->getMessage()]);
    exit;
}

$termo = isset($_GET['termo']) ? trim($_GET['termo']) : '';
$polo_id = isset($_GET['polo_id']) ? (int)$_GET['polo_id'] : 0;
$curso_id = isset($_GET['curso_id']) ? (int)$_GET['curso_id'] : 0;

$sql = "SELECT id, nome, cpf, email FROM alunos WHERE 1=1";
$params = [];

if (!empty($termo)) {
    // 🔥 Prioridade total ao termo
    $sql .= " AND (nome LIKE ? OR cpf LIKE ? OR email LIKE ?)";
    $termo_param = "%$termo%";
    $params[] = $termo_param;
    $params[] = $termo_param;
    $params[] = $termo_param;
} else {
    // Aplica filtros apenas se não tiver termo
    if ($polo_id > 0) {
        $sql .= " AND polo_id = ?";
        $params[] = $polo_id;
    }
    if ($curso_id > 0) {
        $sql .= " AND curso_id = ?";
        $params[] = $curso_id;
    }
}

$sql .= " ORDER BY nome ASC LIMIT 100";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'alunos' => $alunos,
        'total' => count($alunos)
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode([
        'error' => 'Erro ao buscar alunos: ' . $e->getMessage(),
        'alunos' => [],
        'total' => 0
    ], JSON_UNESCAPED_UNICODE);
}
