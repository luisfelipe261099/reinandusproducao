<?php
// Verifica se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['mensagem'] = 'Método de requisição inválido.';
    $_SESSION['mensagem_tipo'] = 'erro';
    header('Location: chamados.php');
    exit;
}

// Obtém os dados do formulário
$categoria_id = isset($_POST['categoria_id']) ? (int)$_POST['categoria_id'] : 0;
$titulo = isset($_POST['titulo']) ? trim($_POST['titulo']) : '';
$descricao = isset($_POST['descricao']) ? trim($_POST['descricao']) : '';
$prioridade = isset($_POST['prioridade']) ? $_POST['prioridade'] : 'media';
$departamento = isset($_POST['departamento']) ? $_POST['departamento'] : null;
$polo_id = isset($_POST['polo_id']) && !empty($_POST['polo_id']) ? (int)$_POST['polo_id'] : null;
$aluno_id = isset($_POST['aluno_id']) && !empty($_POST['aluno_id']) ? (int)$_POST['aluno_id'] : null;
$tipo = isset($_POST['tipo']) ? $_POST['tipo'] : ($is_polo ? 'polo' : 'interno');

// Validação básica
$erros = [];

if (empty($categoria_id)) {
    $erros[] = 'A categoria é obrigatória.';
}

if (empty($titulo)) {
    $erros[] = 'O título é obrigatório.';
}

if (empty($descricao)) {
    $erros[] = 'A descrição é obrigatória.';
}

if (!in_array($prioridade, ['baixa', 'media', 'alta', 'urgente'])) {
    $erros[] = 'Prioridade inválida.';
}

if (!$is_polo && empty($departamento)) {
    $erros[] = 'O departamento é obrigatório.';
}

// Verifica se a categoria existe
if ($categoria_id > 0) {
    $sql = "SELECT * FROM categorias_chamados WHERE id = ? AND status = 'ativo'";
    $categoria = $db->fetchOne($sql, [$categoria_id]);
    
    if (!$categoria) {
        $erros[] = 'Categoria inválida.';
    } else {
        // Verifica se a categoria é compatível com o tipo de usuário
        if ($is_polo && $categoria['tipo'] !== 'polo') {
            $erros[] = 'Categoria inválida para polos.';
        } elseif (!$is_polo && $categoria['tipo'] !== 'interno') {
            $erros[] = 'Categoria inválida para usuários internos.';
        }
    }
}

// Verifica se o polo existe (se informado)
if ($polo_id) {
    $sql = "SELECT * FROM polos WHERE id = ? AND status = 'ativo'";
    $polo = $db->fetchOne($sql, [$polo_id]);
    
    if (!$polo) {
        $erros[] = 'Polo inválido.';
    }
}

// Verifica se o aluno existe (se informado)
if ($aluno_id) {
    $sql = "SELECT * FROM alunos WHERE id = ? AND status = 'ativo'";
    $aluno = $db->fetchOne($sql, [$aluno_id]);
    
    if (!$aluno) {
        $erros[] = 'Aluno inválido.';
    }
}

// Se houver erros, redireciona de volta para o formulário
if (!empty($erros)) {
    $_SESSION['mensagem'] = implode('<br>', $erros);
    $_SESSION['mensagem_tipo'] = 'erro';
    header('Location: chamados.php?action=novo');
    exit;
}

// Gera um código único para o chamado
$codigo = 'TICK-' . date('Ymd') . '-' . mt_rand(1000, 9999);

// Insere o chamado no banco de dados
$sql = "INSERT INTO chamados (
            codigo,
            titulo,
            descricao,
            categoria_id,
            tipo,
            prioridade,
            status,
            solicitante_id,
            departamento,
            polo_id,
            aluno_id,
            data_abertura,
            data_ultima_atualizacao,
            created_at,
            updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, 'aberto', ?, ?, ?, ?, NOW(), NOW(), NOW(), NOW())";

$params = [
    $codigo,
    $titulo,
    $descricao,
    $categoria_id,
    $tipo,
    $prioridade,
    Auth::getUserId(),
    $departamento,
    $polo_id,
    $aluno_id
];

try {
    $db->execute($sql, $params);
    $chamado_id = $db->lastInsertId();
    
    // Processa os anexos
    if (isset($_FILES['anexos']) && !empty($_FILES['anexos']['name'][0])) {
        $diretorio_anexos = 'uploads/chamados/' . $chamado_id . '/';
        
        // Cria o diretório se não existir
        if (!is_dir($diretorio_anexos)) {
            mkdir($diretorio_anexos, 0755, true);
        }
        
        // Formatos permitidos
        $formatos_permitidos = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png'];
        
        // Tamanho máximo (5MB)
        $tamanho_maximo = 5 * 1024 * 1024;
        
        // Processa cada arquivo
        $total_arquivos = count($_FILES['anexos']['name']);
        
        for ($i = 0; $i < $total_arquivos; $i++) {
            if ($_FILES['anexos']['error'][$i] === UPLOAD_ERR_OK) {
                $nome_arquivo = $_FILES['anexos']['name'][$i];
                $tamanho_arquivo = $_FILES['anexos']['size'][$i];
                $tipo_arquivo = $_FILES['anexos']['type'][$i];
                $arquivo_temp = $_FILES['anexos']['tmp_name'][$i];
                
                // Verifica o tamanho do arquivo
                if ($tamanho_arquivo > $tamanho_maximo) {
                    continue;
                }
                
                // Verifica a extensão do arquivo
                $extensao = strtolower(pathinfo($nome_arquivo, PATHINFO_EXTENSION));
                if (!in_array($extensao, $formatos_permitidos)) {
                    continue;
                }
                
                // Gera um nome único para o arquivo
                $nome_arquivo_unico = uniqid() . '.' . $extensao;
                $caminho_arquivo = $diretorio_anexos . $nome_arquivo_unico;
                
                // Move o arquivo para o diretório de destino
                if (move_uploaded_file($arquivo_temp, $caminho_arquivo)) {
                    // Insere o anexo no banco de dados
                    $sql = "INSERT INTO chamados_anexos (
                                chamado_id,
                                nome_arquivo,
                                caminho_arquivo,
                                tipo_arquivo,
                                tamanho_arquivo,
                                usuario_id,
                                created_at
                            ) VALUES (?, ?, ?, ?, ?, ?, NOW())";
                    
                    $params_anexo = [
                        $chamado_id,
                        $nome_arquivo,
                        $caminho_arquivo,
                        $tipo_arquivo,
                        $tamanho_arquivo,
                        Auth::getUserId()
                    ];
                    
                    $db->execute($sql, $params_anexo);
                }
            }
        }
    }
    
    // Registra a abertura do chamado no histórico
    $sql = "INSERT INTO chamados_respostas (
                chamado_id,
                usuario_id,
                mensagem,
                tipo,
                visivel_solicitante,
                data_resposta,
                created_at,
                updated_at
            ) VALUES (?, ?, ?, 'sistema', 1, NOW(), NOW(), NOW())";
    
    $mensagem = "Chamado aberto com sucesso.";
    $params_resposta = [
        $chamado_id,
        Auth::getUserId(),
        $mensagem
    ];
    
    $db->execute($sql, $params_resposta);
    
    $_SESSION['mensagem'] = 'Chamado aberto com sucesso. Código: ' . $codigo;
    $_SESSION['mensagem_tipo'] = 'sucesso';
    header('Location: chamados.php?action=visualizar&id=' . $chamado_id);
    exit;
} catch (Exception $e) {
    $_SESSION['mensagem'] = 'Erro ao abrir o chamado: ' . $e->getMessage();
    $_SESSION['mensagem_tipo'] = 'erro';
    header('Location: chamados.php?action=novo');
    exit;
}
?>
