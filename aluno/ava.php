<?php
// Define o título da página
$titulo_pagina = 'Ambiente Virtual de Aprendizagem';

// Inclui o cabeçalho
include 'includes/header.php';

// Verifica se o aluno tem acesso ao AVA
if (!$aluno['acesso_ava']) {
    echo '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <div class="ml-3">
                <p>Você não tem acesso ao Ambiente Virtual de Aprendizagem.</p>
                <p class="mt-2">Entre em contato com a secretaria para mais informações.</p>
            </div>
        </div>
    </div>';
    
    include 'includes/footer.php';
    exit;
}

// Obtém o ID do aluno
$aluno_id = $_SESSION['aluno_id'];

// Verifica se foi solicitado um curso específico
$curso_id = isset($_GET['curso_id']) ? intval($_GET['curso_id']) : 0;
$aula_id = isset($_GET['aula_id']) ? intval($_GET['aula_id']) : 0;

// Registra o acesso ao AVA
$db->insert('ava_acessos', [
    'aluno_id' => $aluno_id,
    'data_acesso' => date('Y-m-d H:i:s'),
    'ip' => $_SERVER['REMOTE_ADDR'],
    'user_agent' => $_SERVER['HTTP_USER_AGENT'],
    'pagina' => $_SERVER['REQUEST_URI'],
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s')
]);

// Obtém os cursos do AVA do aluno
$cursos_ava = $db->query("SELECT ac.*, am.progresso, am.id as matricula_id 
                         FROM ava_cursos ac 
                         INNER JOIN ava_matriculas am ON ac.id = am.curso_id 
                         WHERE am.aluno_id = ? AND am.status = 'ativo'
                         ORDER BY am.data_matricula DESC", [$aluno_id]);

// Se não houver cursos, exibe mensagem
if (count($cursos_ava) === 0) {
    echo '<div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-info-circle"></i>
            </div>
            <div class="ml-3">
                <p>Você ainda não está matriculado em nenhum curso no AVA.</p>
                <p class="mt-2">Entre em contato com a secretaria para mais informações.</p>
            </div>
        </div>
    </div>';
    
    include 'includes/footer.php';
    exit;
}

// Se um curso específico foi solicitado
if ($curso_id > 0) {
    // Verifica se o aluno está matriculado neste curso
    $matricula = null;
    $curso_atual = null;
    
    foreach ($cursos_ava as $curso) {
        if ($curso['id'] == $curso_id) {
            $matricula = $curso['matricula_id'];
            $curso_atual = $curso;
            break;
        }
    }
    
    // Se não estiver matriculado, redireciona para a página principal do AVA
    if (!$matricula) {
        header('Location: ava.php');
        exit;
    }
    
    // Se uma aula específica foi solicitada
    if ($aula_id > 0) {
        // Inclui a página de visualização da aula
        include 'ava_visualizar_aula.php';
    } else {
        // Inclui a página de visualização do curso
        include 'ava_visualizar_curso.php';
    }
} else {
    // Exibe a lista de cursos disponíveis
?>

<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-800 mb-2">Meus Cursos no AVA</h2>
    <p class="text-gray-600">Bem-vindo ao Ambiente Virtual de Aprendizagem. Aqui você pode acessar seus cursos online.</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($cursos_ava as $curso): ?>
    <div class="card animate-fade-in" style="opacity: 0; transform: translateY(10px);">
        <div class="relative">
            <img src="<?php echo !empty($curso['imagem']) ? $curso['imagem'] : '../assets/img/curso-placeholder.jpg'; ?>" alt="<?php echo $curso['titulo']; ?>" class="w-full h-48 object-cover rounded-t-lg">
            <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 to-transparent p-4">
                <h4 class="text-white font-semibold"><?php echo $curso['titulo']; ?></h4>
            </div>
        </div>
        <div class="p-4">
            <div class="flex justify-between items-center mb-2">
                <span class="text-sm text-gray-500"><?php echo $curso['categoria'] ?? 'Geral'; ?></span>
                <span class="text-xs px-2 py-1 bg-blue-100 text-blue-800 rounded-full"><?php echo $curso['carga_horaria']; ?> horas</span>
            </div>
            
            <?php if (!empty($curso['descricao'])): ?>
            <p class="text-sm text-gray-600 mb-4 line-clamp-2"><?php echo $curso['descricao']; ?></p>
            <?php endif; ?>
            
            <div class="mb-4">
                <div class="flex justify-between text-sm mb-1">
                    <span>Progresso</span>
                    <span><?php echo $curso['progresso']; ?>%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo $curso['progresso']; ?>%"></div>
                </div>
            </div>
            
            <a href="ava.php?curso_id=<?php echo $curso['id']; ?>" class="btn btn-primary w-full">
                <?php if ($curso['progresso'] > 0): ?>
                <i class="fas fa-play-circle mr-2"></i> Continuar Curso
                <?php else: ?>
                <i class="fas fa-book-open mr-2"></i> Iniciar Curso
                <?php endif; ?>
            </a>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php
}

// Inclui o rodapé
include 'includes/footer.php';
?>
