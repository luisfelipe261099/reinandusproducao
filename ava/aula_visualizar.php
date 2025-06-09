<?php
/**
 * Página de Visualização de Aula do AVA para Polos
 * Permite visualizar uma aula específica como se fosse um aluno
 */

// Inicializa o sistema
require_once '../includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica se o ID da aula foi informado
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setMensagem('erro', 'ID da aula não informado.');
    redirect('index.php');
    exit;
}

$aula_id = (int)$_GET['id'];
$polo_id = getUsuarioPoloId();

// Instancia o banco de dados
$db = Database::getInstance();

// Busca a aula
$sql = "SELECT a.*, m.titulo as modulo_titulo, m.curso_id, c.titulo as curso_titulo
        FROM ava_aulas a
        LEFT JOIN ava_modulos m ON a.modulo_id = m.id
        LEFT JOIN ava_cursos c ON m.curso_id = c.id
        WHERE a.id = ? AND c.polo_id = ?";
$aula = $db->fetchOne($sql, [$aula_id, $polo_id]);

if (!$aula) {
    setMensagem('erro', 'Aula não encontrada ou você não tem permissão para acessá-la.');
    redirect('index.php');
    exit;
}

// Busca os materiais complementares da aula
$sql = "SELECT * FROM ava_materiais WHERE aula_id = ? ORDER BY ordem, id";
$materiais = $db->fetchAll($sql, [$aula_id]);

// Busca as outras aulas do mesmo módulo
$sql = "SELECT a.id, a.titulo, a.tipo, a.ordem
        FROM ava_aulas a
        WHERE a.modulo_id = ?
        ORDER BY a.ordem, a.id";
$aulas_modulo = $db->fetchAll($sql, [$aula['modulo_id']]);

// Busca as questões do quiz, se for uma aula do tipo quiz
$questoes = [];
if ($aula['tipo'] === 'quiz') {
    $sql = "SELECT * FROM ava_questoes WHERE aula_id = ? ORDER BY ordem, id";
    $questoes = $db->fetchAll($sql, [$aula_id]);

    // Para cada questão, busca as alternativas
    foreach ($questoes as &$questao) {
        $sql = "SELECT * FROM ava_alternativas WHERE questao_id = ? ORDER BY id";
        $alternativas = $db->fetchAll($sql, [$questao['id']]);

        // Se não houver alternativas, cria alternativas simuladas
        if (empty($alternativas)) {
            $alternativas = criarAlternativasSimuladas($questao);
        }

        $questao['alternativas'] = $alternativas;
    }
}

// Função para criar alternativas simuladas
function criarAlternativasSimuladas($questao) {
    $alternativas = [];

    // Verifica se há opções definidas na questão
    $opcoes = [];
    if (!empty($questao['opcoes'])) {
        $opcoes = explode('|', $questao['opcoes']);
    }

    // Se houver opções definidas, usa-as para criar as alternativas
    if (!empty($opcoes)) {
        foreach ($opcoes as $index => $opcao) {
            $alternativas[] = [
                'id' => 'sim_' . ($index + 1),
                'questao_id' => $questao['id'],
                'texto' => trim($opcao),
                'correta' => ($index === 0) ? 1 : 0 // Considera a primeira opção como correta
            ];
        }
    } else {
        // Se não houver opções definidas, cria alternativas genéricas
        $alternativas = [
            [
                'id' => 'sim_1',
                'questao_id' => $questao['id'],
                'texto' => 'Alternativa A',
                'correta' => 1
            ],
            [
                'id' => 'sim_2',
                'questao_id' => $questao['id'],
                'texto' => 'Alternativa B',
                'correta' => 0
            ],
            [
                'id' => 'sim_3',
                'questao_id' => $questao['id'],
                'texto' => 'Alternativa C',
                'correta' => 0
            ],
            [
                'id' => 'sim_4',
                'questao_id' => $questao['id'],
                'texto' => 'Alternativa D',
                'correta' => 0
            ]
        ];
    }

    return $alternativas;
}

// Define o título da página
$titulo_aula = isset($aula['titulo']) ? $aula['titulo'] : 'Aula';
$titulo_curso = isset($aula['curso_titulo']) ? $aula['curso_titulo'] : 'Curso';
$titulo_pagina = $titulo_aula . ' - ' . $titulo_curso;
$titulo_pagina_completo = 'Faciência ERP - ' . $titulo_pagina;

// Registra a visualização da aula (em um sistema real, isso seria usado para tracking)
$usuario_id = getUsuarioId();
$data_atual = date('Y-m-d H:i:s');

// Em um sistema real, você registraria a visualização em uma tabela como ava_visualizacoes
// $sql = "INSERT INTO ava_visualizacoes (aula_id, usuario_id, data_visualizacao) VALUES (?, ?, ?)";
// $db->execute($sql, [$aula_id, $usuario_id, $data_atual]);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo_pagina_completo; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="https://cdn.plyr.io/3.7.2/plyr.css" />
    <script src="https://cdn.plyr.io/3.7.2/plyr.polyfilled.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/themes/prism.min.css" />
    <style>
        :root {
            --primary-color: #4f46e5;
            --primary-hover: #4338ca;
            --secondary-color: #0ea5e9;
            --accent-color: #8b5cf6;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --light-bg: #f9fafb;
            --dark-bg: #1f2937;
            --card-bg: #ffffff;
            --text-primary: #111827;
            --text-secondary: #4b5563;
            --text-light: #9ca3af;
            --border-color: #e5e7eb;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --radius-sm: 0.25rem;
            --radius: 0.5rem;
            --radius-lg: 0.75rem;
            --radius-xl: 1rem;
            --transition: all 0.3s ease;
        }

        /* Estilos gerais */
        body {
            scroll-behavior: smooth;
        }

        .aula-header {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        .aula-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiB2aWV3Qm94PSIwIDAgMTAwIDEwMCIgcHJlc2VydmVBc3BlY3RSYXRpbz0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZGVmcz48bGluZWFyR3JhZGllbnQgaWQ9ImcxIiB4MT0iMCUiIHkxPSIwJSIgeDI9IjEwMCUiIHkyPSIxMDAlIj48c3RvcCBvZmZzZXQ9IjAlIiBzdG9wLWNvbG9yPSIjZmZmZmZmIiBzdG9wLW9wYWNpdHk9IjAuMSIgLz48c3RvcCBvZmZzZXQ9IjEwMCUiIHN0b3AtY29sb3I9IiNmZmZmZmYiIHN0b3Atb3BhY2l0eT0iMC4wNSIgLz48L2xpbmVhckdyYWRpZW50PjwvZGVmcz48cGF0aCBkPSJNMCAwIEwxMDAgMTAwIEwxMDAgMCBaIiBmaWxsPSJ1cmwoI2cxKSIgLz48L3N2Zz4=');
            opacity: 0.1;
            z-index: 0;
        }

        .aula-header-content {
            position: relative;
            z-index: 1;
        }

        .aula-title {
            font-weight: 800;
            letter-spacing: -0.025em;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .aula-meta {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 0.5rem;
        }

        .aula-meta-item {
            display: flex;
            align-items: center;
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.9);
            background-color: rgba(255, 255, 255, 0.1);
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            backdrop-filter: blur(4px);
        }

        .aula-meta-item i {
            margin-right: 0.375rem;
        }

        .aula-nav {
            display: flex;
            gap: 0.75rem;
        }

        .aula-nav-link {
            display: flex;
            align-items: center;
            color: rgba(255, 255, 255, 0.9);
            background-color: rgba(255, 255, 255, 0.1);
            padding: 0.375rem 0.75rem;
            border-radius: var(--radius);
            font-size: 0.875rem;
            font-weight: 500;
            transition: var(--transition);
            backdrop-filter: blur(4px);
        }

        .aula-nav-link:hover {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .aula-nav-link i {
            margin-right: 0.375rem;
        }

        /* Container de vídeo */
        .video-container {
            position: relative;
            padding-bottom: 56.25%; /* 16:9 */
            height: 0;
            overflow: hidden;
            max-width: 100%;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            background-color: #000;
        }

        .video-container iframe,
        .video-container video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border-radius: var(--radius);
        }

        /* Conteúdo da aula */
        .aula-content-card {
            background-color: var(--card-bg);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            overflow: hidden;
            transition: var(--transition);
        }

        .aula-content-card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-2px);
        }

        .aula-content-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .aula-content-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            display: flex;
            align-items: center;
        }

        .aula-content-title i {
            margin-right: 0.75rem;
            width: 2rem;
            height: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 1rem;
        }

        .aula-content-body {
            padding: 1.5rem;
        }

        /* Estilos para o conteúdo de texto */
        .aula-texto-content {
            font-size: 1.05rem;
            line-height: 1.7;
            color: var(--text-primary);
        }

        .aula-texto-content h1,
        .aula-texto-content h2,
        .aula-texto-content h3,
        .aula-texto-content h4,
        .aula-texto-content h5,
        .aula-texto-content h6 {
            margin-top: 1.5em;
            margin-bottom: 0.75em;
            font-weight: 600;
            line-height: 1.3;
            color: var(--text-primary);
        }

        .aula-texto-content h1 {
            font-size: 2rem;
        }

        .aula-texto-content h2 {
            font-size: 1.75rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .aula-texto-content h3 {
            font-size: 1.5rem;
        }

        .aula-texto-content p {
            margin-bottom: 1.25rem;
        }

        .aula-texto-content ul,
        .aula-texto-content ol {
            margin-bottom: 1.25rem;
            padding-left: 1.5rem;
        }

        .aula-texto-content li {
            margin-bottom: 0.5rem;
        }

        .aula-texto-content a {
            color: var(--primary-color);
            text-decoration: none;
            border-bottom: 1px solid transparent;
            transition: var(--transition);
        }

        .aula-texto-content a:hover {
            border-bottom-color: var(--primary-color);
        }

        .aula-texto-content img {
            max-width: 100%;
            height: auto;
            border-radius: var(--radius);
            margin: 1.5rem 0;
            box-shadow: var(--shadow);
        }

        .aula-texto-content blockquote {
            margin: 1.5rem 0;
            padding: 1rem 1.5rem;
            border-left: 4px solid var(--primary-color);
            background-color: var(--light-bg);
            border-radius: 0 var(--radius) var(--radius) 0;
            font-style: italic;
            color: var(--text-secondary);
        }

        .aula-texto-content pre {
            margin: 1.5rem 0;
            padding: 1rem;
            background-color: var(--dark-bg);
            border-radius: var(--radius);
            overflow-x: auto;
            font-family: 'Fira Code', monospace;
            font-size: 0.9rem;
        }

        .aula-texto-content code {
            font-family: 'Fira Code', monospace;
            font-size: 0.9em;
            padding: 0.2em 0.4em;
            background-color: var(--light-bg);
            border-radius: var(--radius-sm);
        }

        .aula-texto-content table {
            width: 100%;
            margin: 1.5rem 0;
            border-collapse: collapse;
        }

        .aula-texto-content th,
        .aula-texto-content td {
            padding: 0.75rem;
            border: 1px solid var(--border-color);
        }

        .aula-texto-content th {
            background-color: var(--light-bg);
            font-weight: 600;
        }

        /* Sidebar da aula */
        .aula-sidebar {
            max-height: calc(100vh - 200px);
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: var(--text-light) transparent;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            background-color: var(--card-bg);
        }

        .aula-sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .aula-sidebar::-webkit-scrollbar-track {
            background: transparent;
        }

        .aula-sidebar::-webkit-scrollbar-thumb {
            background-color: var(--text-light);
            border-radius: 20px;
        }

        .aula-sidebar-header {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--border-color);
            background-color: var(--light-bg);
            font-weight: 600;
            color: var(--text-primary);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .aula-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .aula-list-item {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            transition: var(--transition);
            color: var(--text-secondary);
            text-decoration: none;
        }

        .aula-list-item:hover {
            background-color: var(--light-bg);
            color: var(--text-primary);
        }

        .aula-list-item.active {
            background-color: rgba(79, 70, 229, 0.1);
            border-left: 3px solid var(--primary-color);
            color: var(--primary-color);
            font-weight: 500;
        }

        .aula-list-item-icon {
            width: 2rem;
            height: 2rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin-right: 0.75rem;
            font-size: 0.875rem;
            flex-shrink: 0;
        }

        .aula-video { background-color: #ede9fe; color: #7c3aed; }
        .aula-texto { background-color: #e0f2fe; color: #0369a1; }
        .aula-quiz { background-color: #fef3c7; color: #d97706; }
        .aula-arquivo { background-color: #dbeafe; color: #2563eb; }
        .aula-link { background-color: #d1fae5; color: #059669; }

        .aula-list-item-content {
            flex: 1;
            min-width: 0;
        }

        .aula-list-item-title {
            display: block;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .aula-list-item-meta {
            font-size: 0.75rem;
            color: var(--text-light);
            margin-top: 0.25rem;
        }

        /* Estilos para o quiz */
        .quiz-container {
            background-color: var(--card-bg);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .quiz-header {
            padding: 1.25rem 1.5rem;
            background-color: var(--light-bg);
            border-bottom: 1px solid var(--border-color);
        }

        .quiz-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            display: flex;
            align-items: center;
        }

        .quiz-title i {
            margin-right: 0.75rem;
            color: var(--warning-color);
        }

        .quiz-body {
            padding: 1.5rem;
        }

        .quiz-question {
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid var(--border-color);
            animation: fadeIn 0.5s ease;
        }

        .quiz-question:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .quiz-question-header {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }

        .quiz-question-number {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            background-color: var(--primary-color);
            color: white;
            border-radius: 50%;
            margin-right: 0.75rem;
            font-size: 0.875rem;
            font-weight: 600;
            flex-shrink: 0;
        }

        .quiz-options {
            margin-top: 1rem;
            margin-left: 2.75rem;
        }

        .quiz-option {
            padding: 0.875rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            margin-bottom: 0.75rem;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
        }

        .quiz-option:hover {
            background-color: var(--light-bg);
            border-color: var(--text-light);
        }

        .quiz-option.selected {
            background-color: rgba(79, 70, 229, 0.1);
            border-color: var(--primary-color);
        }

        .quiz-option-marker {
            width: 1.25rem;
            height: 1.25rem;
            border: 2px solid var(--border-color);
            border-radius: 50%;
            margin-right: 0.75rem;
            position: relative;
            flex-shrink: 0;
            transition: var(--transition);
        }

        .quiz-option.selected .quiz-option-marker {
            border-color: var(--primary-color);
        }

        .quiz-option.selected .quiz-option-marker::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 0.625rem;
            height: 0.625rem;
            background-color: var(--primary-color);
            border-radius: 50%;
        }

        .quiz-option-text {
            flex: 1;
            color: var(--text-secondary);
        }

        .quiz-option.selected .quiz-option-text {
            color: var(--text-primary);
            font-weight: 500;
        }

        .quiz-footer {
            margin-top: 2rem;
            display: flex;
            justify-content: flex-end;
        }

        .quiz-submit-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.625rem 1.25rem;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: var(--radius);
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
        }

        .quiz-submit-btn:hover {
            background-color: var(--primary-hover);
        }

        .quiz-submit-btn i {
            margin-right: 0.5rem;
        }

        /* Estilos para materiais complementares */
        .materiais-section {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--border-color);
        }

        .materiais-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }

        .materiais-title i {
            margin-right: 0.75rem;
            color: var(--secondary-color);
        }

        .materiais-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1rem;
        }

        .material-card {
            background-color: var(--card-bg);
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            transition: var(--transition);
            border: 1px solid var(--border-color);
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .material-card:hover {
            box-shadow: var(--shadow);
            transform: translateY(-2px);
            border-color: var(--text-light);
        }

        .material-card-header {
            padding: 1rem;
            background-color: var(--light-bg);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
        }

        .material-icon {
            width: 2.5rem;
            height: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background-color: white;
            box-shadow: var(--shadow-sm);
            margin-right: 0.75rem;
            font-size: 1.25rem;
            color: var(--primary-color);
        }

        .material-title {
            font-weight: 500;
            color: var(--text-primary);
            font-size: 0.9375rem;
            line-height: 1.4;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .material-body {
            padding: 1rem;
            flex: 1;
        }

        .material-description {
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin-bottom: 0.75rem;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            line-clamp: 3;
            -webkit-box-orient: vertical;
        }

        .material-meta {
            font-size: 0.75rem;
            color: var(--text-light);
            display: flex;
            align-items: center;
            margin-top: auto;
        }

        .material-meta i {
            margin-right: 0.25rem;
        }

        .material-footer {
            padding: 0.75rem 1rem;
            border-top: 1px solid var(--border-color);
            background-color: var(--light-bg);
        }

        .material-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 0.5rem 0;
            background-color: white;
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
            border-radius: var(--radius);
            font-size: 0.875rem;
            font-weight: 500;
            transition: var(--transition);
            text-decoration: none;
        }

        .material-action:hover {
            background-color: var(--primary-color);
            color: white;
        }

        .material-action i {
            margin-right: 0.375rem;
        }

        /* Animações */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animate-fade-in {
            animation: fadeIn 0.5s ease;
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .aula-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }

            .materiais-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <?php include '../polo/includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <?php include '../polo/includes/header.php'; ?>

            <!-- Main -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100">
                <!-- Cabeçalho da Aula -->
                <div class="aula-header py-8 px-6">
                    <div class="container mx-auto">
                        <div class="aula-header-content">
                            <div class="flex justify-between items-start">
                                <div>
                                    <div class="aula-nav mb-4">
                                        <a href="curso_visualizar.php?id=<?php echo $aula['curso_id']; ?>" class="aula-nav-link">
                                            <i class="fas fa-arrow-left"></i> Voltar para o Curso
                                        </a>
                                        <a href="index.php" class="aula-nav-link">
                                            <i class="fas fa-graduation-cap"></i> Meus Cursos
                                        </a>
                                    </div>
                                    <h1 class="aula-title text-3xl md:text-4xl"><?php echo htmlspecialchars($titulo_aula); ?></h1>
                                    <div class="aula-meta">
                                        <div class="aula-meta-item">
                                            <i class="fas fa-layer-group"></i> <?php echo htmlspecialchars($aula['modulo_titulo']); ?>
                                        </div>
                                        <div class="aula-meta-item">
                                            <i class="fas fa-clock"></i> <?php echo $aula['duracao'] ?? '30'; ?> minutos
                                        </div>
                                        <?php
                                        $tipo_texto = '';
                                        $tipo_icone = '';
                                        switch ($aula['tipo']) {
                                            case 'video':
                                                $tipo_texto = 'Vídeo';
                                                $tipo_icone = 'fas fa-play-circle';
                                                break;
                                            case 'texto':
                                                $tipo_texto = 'Texto';
                                                $tipo_icone = 'fas fa-file-alt';
                                                break;
                                            case 'quiz':
                                                $tipo_texto = 'Quiz';
                                                $tipo_icone = 'fas fa-question-circle';
                                                break;
                                            case 'arquivo':
                                                $tipo_texto = 'Arquivo';
                                                $tipo_icone = 'fas fa-file-download';
                                                break;
                                            case 'link':
                                                $tipo_texto = 'Link Externo';
                                                $tipo_icone = 'fas fa-link';
                                                break;
                                        }
                                        ?>
                                        <div class="aula-meta-item">
                                            <i class="<?php echo $tipo_icone; ?>"></i> <?php echo $tipo_texto; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="container mx-auto p-6">
                    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                        <!-- Conteúdo da Aula -->
                        <div class="lg:col-span-3">
                            <div class="aula-content-card animate-fade-in">
                                <div class="aula-content-header">
                                    <div class="aula-content-title">
                                        <?php if ($aula['tipo'] === 'video'): ?>
                                            <i class="fas fa-play-circle aula-video"></i> Conteúdo em Vídeo
                                        <?php elseif ($aula['tipo'] === 'texto'): ?>
                                            <i class="fas fa-file-alt aula-texto"></i> Conteúdo em Texto
                                        <?php elseif ($aula['tipo'] === 'quiz'): ?>
                                            <i class="fas fa-question-circle aula-quiz"></i> Quiz Interativo
                                        <?php elseif ($aula['tipo'] === 'arquivo'): ?>
                                            <i class="fas fa-file-download aula-arquivo"></i> Arquivo para Download
                                        <?php elseif ($aula['tipo'] === 'link'): ?>
                                            <i class="fas fa-link aula-link"></i> Link Externo
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <span class="text-sm text-gray-500">
                                            <i class="far fa-calendar-alt mr-1"></i> Atualizado em <?php echo date('d/m/Y', strtotime($aula['updated_at'] ?? date('Y-m-d'))); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="aula-content-body">
                                    <div class="aula-content">
                                        <?php if ($aula['tipo'] === 'video'): ?>
                                            <!-- Conteúdo de Vídeo -->
                                            <div class="video-container">
                                                <?php
                                                $conteudo = isset($aula['conteudo']) ? $aula['conteudo'] : '';
                                                if (!empty($conteudo) && (strpos($conteudo, 'youtube.com') !== false || strpos($conteudo, 'youtu.be') !== false)):
                                                ?>
                                                    <?php
                                                    // Extrair o ID do vídeo do YouTube
                                                    $video_id = '';
                                                    if (preg_match('/(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $conteudo, $matches)) {
                                                        $video_id = $matches[1];
                                                    }
                                                    ?>
                                                    <iframe src="https://www.youtube.com/embed/<?php echo $video_id; ?>" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                                                <?php elseif (!empty($conteudo) && strpos($conteudo, 'vimeo.com') !== false): ?>
                                                    <?php
                                                    // Extrair o ID do vídeo do Vimeo
                                                    $video_id = '';
                                                    if (preg_match('/vimeo\.com\/([0-9]+)/', $conteudo, $matches)) {
                                                        $video_id = $matches[1];
                                                    }
                                                    ?>
                                                    <iframe src="https://player.vimeo.com/video/<?php echo $video_id; ?>" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>
                                                <?php elseif (!empty($conteudo)): ?>
                                                    <video controls crossorigin playsinline id="player">
                                                        <source src="<?php echo $conteudo; ?>" type="video/mp4" />
                                                    </video>
                                                    <script>
                                                        const player = new Plyr('#player', {
                                                            controls: ['play-large', 'play', 'progress', 'current-time', 'mute', 'volume', 'captions', 'settings', 'pip', 'airplay', 'fullscreen'],
                                                        });
                                                    </script>
                                                <?php else: ?>
                                                    <div class="text-center py-8 text-gray-500">
                                                        <p>Nenhum conteúdo de vídeo disponível.</p>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php elseif ($aula['tipo'] === 'texto'): ?>
                                            <!-- Conteúdo de Texto -->
                                            <div class="aula-texto-content">
                                                <?php
                                                if (isset($aula['conteudo']) && !empty($aula['conteudo'])) {
                                                    echo $aula['conteudo'];
                                                } else {
                                                    echo '<div class="flex flex-col items-center justify-center py-8 text-gray-500">
                                                        <i class="fas fa-file-alt text-5xl mb-4 opacity-50"></i>
                                                        <p class="text-lg">Nenhum conteúdo disponível para esta aula.</p>
                                                    </div>';
                                                }
                                                ?>
                                            </div>
                                        <?php elseif ($aula['tipo'] === 'quiz'): ?>
                                            <!-- Conteúdo de Quiz -->
                                            <div class="quiz-container">
                                                <div class="quiz-header">
                                                    <div class="quiz-title">
                                                        <i class="fas fa-question-circle"></i> Teste seus conhecimentos
                                                    </div>
                                                </div>
                                                <div class="quiz-body">
                                                    <?php if (empty($questoes)): ?>
                                                    <div class="flex flex-col items-center justify-center py-8 text-gray-500">
                                                        <i class="fas fa-question-circle text-5xl mb-4 opacity-50"></i>
                                                        <p class="text-lg">Nenhuma questão disponível para este quiz.</p>
                                                    </div>
                                                    <?php else: ?>
                                                    <form id="quiz-form">
                                                        <?php foreach ($questoes as $index => $questao): ?>
                                                        <div class="quiz-question">
                                                            <div class="quiz-question-header">
                                                                <div class="quiz-question-number"><?php echo ($index + 1); ?></div>
                                                                <div><?php echo htmlspecialchars($questao['pergunta']); ?></div>
                                                            </div>
                                                            <div class="quiz-options">
                                                                <?php foreach ($questao['alternativas'] as $alternativa): ?>
                                                                <div class="quiz-option" onclick="selectOption(this, <?php echo $questao['id']; ?>)">
                                                                    <input type="radio" name="questao_<?php echo $questao['id']; ?>" value="<?php echo $alternativa['id']; ?>" class="hidden" />
                                                                    <div class="quiz-option-marker"></div>
                                                                    <div class="quiz-option-text"><?php echo htmlspecialchars($alternativa['texto']); ?></div>
                                                                </div>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        </div>
                                                        <?php endforeach; ?>

                                                        <div class="quiz-footer">
                                                            <button type="button" id="submit-quiz" class="quiz-submit-btn">
                                                                <i class="fas fa-check-circle"></i> Enviar Respostas
                                                            </button>
                                                        </div>
                                                    </form>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php elseif ($aula['tipo'] === 'arquivo'): ?>
                                            <!-- Conteúdo de Arquivo -->
                                            <div class="flex flex-col items-center justify-center py-8">
                                                <div class="w-24 h-24 flex items-center justify-center bg-blue-100 text-blue-600 rounded-full mb-6 animate-fade-in">
                                                    <i class="fas fa-file-download text-5xl"></i>
                                                </div>
                                                <h3 class="text-2xl font-bold mb-2 text-gray-800">Arquivo para Download</h3>
                                                <p class="text-gray-600 mb-8 max-w-md text-center">Este conteúdo está disponível como um arquivo para download. Clique no botão abaixo para baixá-lo.</p>

                                                <?php if (isset($aula['conteudo']) && !empty($aula['conteudo'])): ?>
                                                <div class="bg-white border border-gray-200 rounded-lg p-4 mb-6 w-full max-w-md shadow-sm">
                                                    <div class="flex items-center">
                                                        <?php
                                                        $ext = pathinfo($aula['conteudo'], PATHINFO_EXTENSION);
                                                        $icon = 'fas fa-file';
                                                        $color = 'text-gray-500';

                                                        if (in_array($ext, ['pdf'])) {
                                                            $icon = 'fas fa-file-pdf';
                                                            $color = 'text-red-500';
                                                        } elseif (in_array($ext, ['doc', 'docx'])) {
                                                            $icon = 'fas fa-file-word';
                                                            $color = 'text-blue-500';
                                                        } elseif (in_array($ext, ['xls', 'xlsx'])) {
                                                            $icon = 'fas fa-file-excel';
                                                            $color = 'text-green-500';
                                                        } elseif (in_array($ext, ['ppt', 'pptx'])) {
                                                            $icon = 'fas fa-file-powerpoint';
                                                            $color = 'text-orange-500';
                                                        } elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                                                            $icon = 'fas fa-file-image';
                                                            $color = 'text-purple-500';
                                                        } elseif (in_array($ext, ['zip', 'rar'])) {
                                                            $icon = 'fas fa-file-archive';
                                                            $color = 'text-yellow-500';
                                                        }
                                                        ?>
                                                        <div class="<?php echo $color; ?> text-3xl mr-4">
                                                            <i class="<?php echo $icon; ?>"></i>
                                                        </div>
                                                        <div class="flex-1 min-w-0">
                                                            <h4 class="text-sm font-medium text-gray-900 truncate">
                                                                <?php echo basename($aula['conteudo']); ?>
                                                            </h4>
                                                            <p class="text-xs text-gray-500">
                                                                <?php echo strtoupper($ext); ?> • Tamanho desconhecido
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>

                                                <a href="<?php echo $aula['conteudo']; ?>" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors shadow-md hover:shadow-lg transform hover:-translate-y-1" download>
                                                    <i class="fas fa-download mr-2"></i> Baixar Arquivo
                                                </a>
                                                <?php else: ?>
                                                <div class="bg-red-50 text-red-600 px-4 py-3 rounded-lg flex items-center">
                                                    <i class="fas fa-exclamation-circle mr-2 text-xl"></i>
                                                    <span>O arquivo não está disponível no momento.</span>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php elseif ($aula['tipo'] === 'link'): ?>
                                            <!-- Conteúdo de Link -->
                                            <div class="flex flex-col items-center justify-center py-8">
                                                <div class="w-24 h-24 flex items-center justify-center bg-green-100 text-green-600 rounded-full mb-6 animate-fade-in">
                                                    <i class="fas fa-link text-5xl"></i>
                                                </div>
                                                <h3 class="text-2xl font-bold mb-2 text-gray-800">Link Externo</h3>
                                                <p class="text-gray-600 mb-8 max-w-md text-center">Este conteúdo está disponível através de um link externo. Clique no botão abaixo para acessá-lo.</p>

                                                <?php if (isset($aula['conteudo']) && !empty($aula['conteudo'])): ?>
                                                <div class="bg-white border border-gray-200 rounded-lg p-4 mb-6 w-full max-w-md shadow-sm">
                                                    <div class="flex items-center">
                                                        <div class="text-green-500 text-3xl mr-4">
                                                            <i class="fas fa-globe"></i>
                                                        </div>
                                                        <div class="flex-1 min-w-0">
                                                            <h4 class="text-sm font-medium text-gray-900 truncate">
                                                                <?php
                                                                $url_parts = parse_url($aula['conteudo']);
                                                                echo $url_parts['host'] ?? $aula['conteudo'];
                                                                ?>
                                                            </h4>
                                                            <p class="text-xs text-gray-500">
                                                                Link Externo • Abre em nova janela
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>

                                                <a href="<?php echo $aula['conteudo']; ?>" class="inline-flex items-center px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors shadow-md hover:shadow-lg transform hover:-translate-y-1" target="_blank" rel="noopener noreferrer">
                                                    <i class="fas fa-external-link-alt mr-2"></i> Acessar Conteúdo
                                                </a>
                                                <?php else: ?>
                                                <div class="bg-red-50 text-red-600 px-4 py-3 rounded-lg flex items-center">
                                                    <i class="fas fa-exclamation-circle mr-2 text-xl"></i>
                                                    <span>O link não está disponível no momento.</span>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Materiais Complementares -->
                                    <?php if (!empty($materiais)): ?>
                                    <div class="materiais-section animate-fade-in">
                                        <div class="materiais-title">
                                            <i class="fas fa-book"></i> Materiais Complementares
                                        </div>
                                        <div class="materiais-grid">
                                            <?php foreach ($materiais as $material): ?>
                                            <?php
                                            $icon = 'fas fa-file';
                                            $arquivo_path = $material['arquivo_path'] ?? '';
                                            $link_url = $material['link_url'] ?? '';
                                            $url = !empty($arquivo_path) ? $arquivo_path : $link_url;
                                            $is_link = !empty($link_url);
                                            $bg_color = 'bg-blue-50';
                                            $icon_color = 'text-blue-500';

                                            if (!empty($arquivo_path)) {
                                                $ext = pathinfo($arquivo_path, PATHINFO_EXTENSION);
                                                switch (strtolower($ext)) {
                                                    case 'pdf':
                                                        $icon = 'fas fa-file-pdf';
                                                        $bg_color = 'bg-red-50';
                                                        $icon_color = 'text-red-500';
                                                        break;
                                                    case 'doc':
                                                    case 'docx':
                                                        $icon = 'fas fa-file-word';
                                                        $bg_color = 'bg-blue-50';
                                                        $icon_color = 'text-blue-500';
                                                        break;
                                                    case 'xls':
                                                    case 'xlsx':
                                                        $icon = 'fas fa-file-excel';
                                                        $bg_color = 'bg-green-50';
                                                        $icon_color = 'text-green-500';
                                                        break;
                                                    case 'ppt':
                                                    case 'pptx':
                                                        $icon = 'fas fa-file-powerpoint';
                                                        $bg_color = 'bg-orange-50';
                                                        $icon_color = 'text-orange-500';
                                                        break;
                                                    case 'jpg':
                                                    case 'jpeg':
                                                    case 'png':
                                                    case 'gif':
                                                        $icon = 'fas fa-file-image';
                                                        $bg_color = 'bg-purple-50';
                                                        $icon_color = 'text-purple-500';
                                                        break;
                                                    case 'zip':
                                                    case 'rar':
                                                        $icon = 'fas fa-file-archive';
                                                        $bg_color = 'bg-yellow-50';
                                                        $icon_color = 'text-yellow-500';
                                                        break;
                                                    default:
                                                        $icon = 'fas fa-file';
                                                        $bg_color = 'bg-gray-50';
                                                        $icon_color = 'text-gray-500';
                                                        break;
                                                }
                                            } elseif (!empty($link_url)) {
                                                $icon = 'fas fa-link';
                                                $bg_color = 'bg-green-50';
                                                $icon_color = 'text-green-500';
                                            }
                                            ?>
                                            <div class="material-card">
                                                <div class="material-card-header <?php echo $bg_color; ?>">
                                                    <div class="material-icon <?php echo $icon_color; ?>">
                                                        <i class="<?php echo $icon; ?>"></i>
                                                    </div>
                                                    <div class="material-title"><?php echo htmlspecialchars($material['titulo']); ?></div>
                                                </div>
                                                <div class="material-body">
                                                    <?php if (!empty($material['descricao'])): ?>
                                                    <div class="material-description"><?php echo htmlspecialchars($material['descricao']); ?></div>
                                                    <?php else: ?>
                                                    <div class="material-description text-gray-400">Sem descrição disponível</div>
                                                    <?php endif; ?>

                                                    <div class="material-meta">
                                                        <i class="far fa-clock"></i> Adicionado em <?php echo date('d/m/Y', strtotime($material['created_at'] ?? date('Y-m-d'))); ?>
                                                    </div>
                                                </div>
                                                <div class="material-footer">
                                                    <a href="<?php echo $url; ?>" <?php echo $is_link ? 'target="_blank" rel="noopener noreferrer"' : 'download'; ?> class="material-action">
                                                        <?php if ($is_link): ?>
                                                        <i class="fas fa-external-link-alt"></i> Acessar Link
                                                        <?php else: ?>
                                                        <i class="fas fa-download"></i> Baixar Material
                                                        <?php endif; ?>
                                                    </a>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Sidebar da Aula -->
                        <div class="lg:col-span-1">
                            <div class="aula-sidebar animate-fade-in">
                                <div class="aula-sidebar-header">
                                    <i class="fas fa-list-ul mr-2"></i> Aulas do Módulo
                                </div>
                                <ul class="aula-list">
                                    <?php foreach ($aulas_modulo as $a): ?>
                                        <?php
                                        $icon_class = 'aula-texto';
                                        $icon = 'fas fa-file-alt';
                                        $tipo_texto = 'Texto';
                                        $duracao = $a['duracao'] ?? '30';

                                        switch ($a['tipo']) {
                                            case 'video':
                                                $icon_class = 'aula-video';
                                                $icon = 'fas fa-play';
                                                $tipo_texto = 'Vídeo';
                                                break;
                                            case 'quiz':
                                                $icon_class = 'aula-quiz';
                                                $icon = 'fas fa-question';
                                                $tipo_texto = 'Quiz';
                                                break;
                                            case 'arquivo':
                                                $icon_class = 'aula-arquivo';
                                                $icon = 'fas fa-file-download';
                                                $tipo_texto = 'Arquivo';
                                                break;
                                            case 'link':
                                                $icon_class = 'aula-link';
                                                $icon = 'fas fa-link';
                                                $tipo_texto = 'Link';
                                                break;
                                        }

                                        $is_active = ($a['id'] == $aula_id);
                                        ?>
                                        <li>
                                            <a href="aula_visualizar.php?id=<?php echo $a['id']; ?>" class="aula-list-item <?php echo $is_active ? 'active' : ''; ?>">
                                                <span class="aula-list-item-icon <?php echo $icon_class; ?>">
                                                    <i class="<?php echo $icon; ?>"></i>
                                                </span>
                                                <div class="aula-list-item-content">
                                                    <span class="aula-list-item-title"><?php echo htmlspecialchars($a['titulo']); ?></span>
                                                    <span class="aula-list-item-meta">
                                                        <?php echo $tipo_texto; ?> • <?php echo $duracao; ?> min
                                                    </span>
                                                </div>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            <!-- Footer -->
            <?php include '../polo/includes/footer.php'; ?>
        </div>
    </div>

    <script src="../js/main.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/prism.min.js"></script>
    <script>
        // Inicializa o player de vídeo se existir
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializa o Prism.js para destacar código
            if (typeof Prism !== 'undefined') {
                Prism.highlightAll();
            }

            // Adiciona animações aos elementos
            const animateElements = document.querySelectorAll('.animate-fade-in');
            animateElements.forEach((el, index) => {
                el.style.animationDelay = (index * 0.1) + 's';
            });
        });

        // Função para selecionar uma opção no quiz
        function selectOption(element, questionId) {
            // Remove a classe 'selected' de todas as opções da mesma questão
            document.querySelectorAll(`.quiz-option input[name="questao_${questionId}"]`).forEach(input => {
                input.closest('.quiz-option').classList.remove('selected');
            });

            // Adiciona a classe 'selected' à opção clicada
            element.classList.add('selected');

            // Marca o input radio
            const input = element.querySelector('input');
            input.checked = true;
        }

        // Função para enviar as respostas do quiz
        const submitQuizBtn = document.getElementById('submit-quiz');
        if (submitQuizBtn) {
            submitQuizBtn.addEventListener('click', function() {
                const form = document.getElementById('quiz-form');
                const formData = new FormData(form);
                const respostas = {};

                // Coleta as respostas
                for (const [name, value] of formData.entries()) {
                    respostas[name] = value;
                }

                // Verifica se todas as questões foram respondidas
                const questoes = document.querySelectorAll('.quiz-question');
                let todasRespondidas = true;
                let primeiraQuestaoNaoRespondida = null;

                questoes.forEach((questao, index) => {
                    const questaoId = questao.querySelector('.quiz-option input').name;
                    if (!respostas[questaoId]) {
                        todasRespondidas = false;
                        if (!primeiraQuestaoNaoRespondida) {
                            primeiraQuestaoNaoRespondida = questao;
                        }
                    }
                });

                if (!todasRespondidas) {
                    // Mostra uma mensagem mais amigável
                    const notification = document.createElement('div');
                    notification.className = 'fixed top-4 right-4 bg-yellow-50 text-yellow-800 px-4 py-3 rounded-lg shadow-lg flex items-center z-50 animate-fade-in';
                    notification.innerHTML = `
                        <i class="fas fa-exclamation-circle mr-2 text-xl"></i>
                        <span>Por favor, responda todas as questões antes de enviar.</span>
                    `;
                    document.body.appendChild(notification);

                    // Remove a notificação após 3 segundos
                    setTimeout(() => {
                        notification.classList.add('opacity-0');
                        notification.style.transition = 'opacity 0.5s ease';
                        setTimeout(() => notification.remove(), 500);
                    }, 3000);

                    // Rola até a primeira questão não respondida
                    if (primeiraQuestaoNaoRespondida) {
                        primeiraQuestaoNaoRespondida.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }

                    return;
                }

                // Mostra um indicador de carregamento
                submitQuizBtn.disabled = true;
                submitQuizBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Processando...';

                // Simula um atraso de processamento
                setTimeout(() => {
                    // Em um sistema real, você enviaria as respostas para o servidor
                    // e processaria o resultado

                    // Mostra uma mensagem de sucesso
                    const notification = document.createElement('div');
                    notification.className = 'fixed top-4 right-4 bg-green-50 text-green-800 px-4 py-3 rounded-lg shadow-lg flex items-center z-50 animate-fade-in';
                    notification.innerHTML = `
                        <i class="fas fa-check-circle mr-2 text-xl"></i>
                        <span>Respostas enviadas com sucesso!</span>
                    `;
                    document.body.appendChild(notification);

                    // Remove a notificação após 3 segundos
                    setTimeout(() => {
                        notification.classList.add('opacity-0');
                        notification.style.transition = 'opacity 0.5s ease';
                        setTimeout(() => notification.remove(), 500);
                    }, 3000);

                    // Simula o feedback visual das respostas corretas/incorretas
                    questoes.forEach((questao) => {
                        const options = questao.querySelectorAll('.quiz-option');
                        const questionNumber = questao.querySelector('.quiz-question-number').textContent;

                        // Simula que a primeira opção é sempre a correta
                        let acertou = false;
                        options.forEach((option, optIndex) => {
                            if (optIndex === 0 && option.classList.contains('selected')) {
                                acertou = true;
                            }

                            if (optIndex === 0) {
                                option.classList.add('correct');
                                option.style.backgroundColor = 'rgba(16, 185, 129, 0.1)';
                                option.style.borderColor = 'var(--success-color)';
                            } else if (option.classList.contains('selected')) {
                                option.classList.add('incorrect');
                                option.style.backgroundColor = 'rgba(239, 68, 68, 0.1)';
                                option.style.borderColor = 'var(--danger-color)';
                            }
                        });

                        // Adiciona um indicador de resultado à questão
                        const resultBadge = document.createElement('div');
                        resultBadge.className = acertou ? 'ml-2 text-green-600' : 'ml-2 text-red-600';
                        resultBadge.innerHTML = acertou ?
                            '<i class="fas fa-check-circle"></i> Correto' :
                            '<i class="fas fa-times-circle"></i> Incorreto';

                        questao.querySelector('.quiz-question-header').appendChild(resultBadge);
                    });

                    // Restaura o botão
                    submitQuizBtn.disabled = false;
                    submitQuizBtn.innerHTML = '<i class="fas fa-check-circle mr-2"></i> Respostas Enviadas';
                    submitQuizBtn.classList.add('bg-green-600');
                    submitQuizBtn.classList.add('hover:bg-green-700');
                }, 1500);
            });
        }
    </script>
</body>
</html>