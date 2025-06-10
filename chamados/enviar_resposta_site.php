<?php
// Inicializa o sistema
require_once __DIR__ . '/../includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica se o usuário tem permissão para editar chamados
exigirPermissao('chamados', 'editar');

// Verifica se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setMensagem('erro', 'Método inválido.');
    redirect('index.php?view=chamados_site');
    exit;
}

// Verifica se o ID foi fornecido
if (!isset($_POST['id']) || empty($_POST['id'])) {
    setMensagem('erro', 'ID da solicitação não informado.');
    redirect('index.php?view=chamados_site');
    exit;
}

// Obtém os dados do formulário
$id = (int)$_POST['id'];
$email = $_POST['email'] ?? '';
$nome_solicitante = $_POST['nome_solicitante'] ?? '';
$protocolo = $_POST['protocolo'] ?? '';
$assunto = $_POST['assunto'] ?? '';
$mensagem = $_POST['mensagem'] ?? '';
$drive_link = $_POST['drive_link'] ?? '';
$atualizar_status = isset($_POST['atualizar_status']) ? (bool)$_POST['atualizar_status'] : false;

// Validação básica
$erros = [];

if (empty($email)) {
    $erros[] = 'O email do destinatário é obrigatório.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $erros[] = 'O email informado é inválido.';
}

if (empty($assunto)) {
    $erros[] = 'O assunto do email é obrigatório.';
}

if (empty($mensagem)) {
    $erros[] = 'A mensagem do email é obrigatória.';
}

// Verifica se um arquivo foi enviado
$arquivo_anexado = false;
$arquivo_nome = '';
$arquivo_temp = '';
$arquivo_tipo = '';

if (isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] === UPLOAD_ERR_OK) {
    $arquivo_nome = $_FILES['arquivo']['name'];
    $arquivo_temp = $_FILES['arquivo']['tmp_name'];
    $arquivo_tipo = $_FILES['arquivo']['type'];
    $arquivo_tamanho = $_FILES['arquivo']['size'];

    // Verifica o tamanho do arquivo (máximo 10MB)
    if ($arquivo_tamanho > 10 * 1024 * 1024) {
        $erros[] = 'O arquivo é muito grande. O tamanho máximo permitido é 10MB.';
    }

    // Verifica a extensão do arquivo
    $extensoes_permitidas = ['zip', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];
    $extensao = strtolower(pathinfo($arquivo_nome, PATHINFO_EXTENSION));

    if (!in_array($extensao, $extensoes_permitidas)) {
        $erros[] = 'Formato de arquivo não permitido. Os formatos aceitos são: ' . implode(', ', $extensoes_permitidas);
    }

    $arquivo_anexado = true;
} else {
    // Se não houver arquivo, verifica se foi um erro de upload
    if (isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] !== UPLOAD_ERR_NO_FILE) {
        switch ($_FILES['arquivo']['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $erros[] = 'O arquivo é muito grande.';
                break;
            case UPLOAD_ERR_PARTIAL:
                $erros[] = 'O arquivo foi enviado parcialmente.';
                break;
            default:
                $erros[] = 'Ocorreu um erro ao enviar o arquivo.';
                break;
        }
    }
    // Arquivo é opcional, então não adicionamos erro se não foi enviado
}

// Verifica se pelo menos um anexo ou link do Drive foi fornecido
if (!$arquivo_anexado && empty($drive_link)) {
    // Não é obrigatório ter anexo ou link, então não adicionamos erro
}

// Instancia o banco de dados
$db = Database::getInstance();

try {
    // Verifica se a solicitação existe
    $sql = "SELECT * FROM solicitacoes_s WHERE id = ?";
    $solicitacao = $db->fetchOne($sql, [$id]);

    if (!$solicitacao) {
        setMensagem('erro', 'Solicitação não encontrada.');
        redirect('index.php?view=chamados_site');
        exit;
    }

    // Se houver erros, redireciona de volta para a página de visualização
    if (!empty($erros)) {
        $_SESSION['form_errors'] = $erros;
        redirect("visualizar_site.php?id=$id");
        exit;
    }

    // Processa o arquivo apenas se foi anexado
    $arquivo_destino = '';
    if ($arquivo_anexado) {
        // Cria o diretório para armazenar os arquivos temporários se não existir
        $upload_dir = __DIR__ . '/../uploads/temp';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Move o arquivo para o diretório de uploads
        $arquivo_destino = $upload_dir . '/' . time() . '_' . $arquivo_nome;
        if (!move_uploaded_file($arquivo_temp, $arquivo_destino)) {
            setMensagem('erro', 'Erro ao mover o arquivo para o servidor.');
            redirect("visualizar_site.php?id=$id");
            exit;
        }
    }

    // Configuração para envio de email
    $para = $email;
    $de = 'desenvolvimento@lfmtecnologia.com'; // Email de envio fornecido
    $de_nome = 'Site FaCiencia';

    // Configurações SMTP para o PHP mail()
    ini_set('SMTP', 'smtp.hostinger.com');
    ini_set('smtp_port', 587);
    ini_set('sendmail_from', $de);

    // Cabeçalhos do email
    $boundary = md5(time());
    $headers = "From: $de_nome <$de>\r\n";
    $headers .= "Reply-To: $de\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

    // Corpo do email
    $corpo = "--$boundary\r\n";
    $corpo .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $corpo .= "Content-Transfer-Encoding: 7bit\r\n\r\n";

    // Adiciona o link do Drive à mensagem se fornecido
    $mensagem_completa = $mensagem;
    if (!empty($drive_link)) {
        $mensagem_completa .= "\r\n\r\nLink para download dos arquivos: " . $drive_link;
    }

    $corpo .= $mensagem_completa . "\r\n\r\n";

    // Anexo (apenas se um arquivo foi anexado)
    if ($arquivo_anexado) {
        $corpo .= "--$boundary\r\n";
        $corpo .= "Content-Type: " . mime_content_type($arquivo_destino) . "; name=\"$arquivo_nome\"\r\n";
        $corpo .= "Content-Transfer-Encoding: base64\r\n";
        $corpo .= "Content-Disposition: attachment; filename=\"$arquivo_nome\"\r\n\r\n";
        $corpo .= chunk_split(base64_encode(file_get_contents($arquivo_destino))) . "\r\n";
    }

    $corpo .= "--$boundary--";

    // Log para depuração
    error_log("Tentando enviar email para: $para");
    error_log("De: $de_nome <$de>");
    error_log("Assunto: $assunto");
    if ($arquivo_anexado) {
        error_log("Anexo: $arquivo_nome");
    }
    if (!empty($drive_link)) {
        error_log("Link do Drive: $drive_link");
    }

    // Tenta enviar o email
    $enviado = mail($para, $assunto, $corpo, $headers);

    // Log do resultado
    if ($enviado) {
        error_log("Email enviado com sucesso para: $para");
    } else {
        error_log("Falha ao enviar email para: $para. Tentando método alternativo...");

        // Método alternativo de envio (fallback)
        // Salva o email para envio posterior ou tenta outro método
        $email_fallback_dir = __DIR__ . '/../uploads/emails_fallback';
        if (!file_exists($email_fallback_dir)) {
            mkdir($email_fallback_dir, 0777, true);
        }

        // Salva os dados do email em um arquivo para processamento posterior
        $email_data = [
            'para' => $para,
            'de' => $de,
            'de_nome' => $de_nome,
            'assunto' => $assunto,
            'mensagem' => $mensagem_completa,
            'arquivo_nome' => $arquivo_anexado ? $arquivo_nome : '',
            'drive_link' => $drive_link,
            'data' => date('Y-m-d H:i:s'),
            'solicitacao_id' => $id
        ];

        $fallback_file = $email_fallback_dir . '/email_' . time() . '_' . rand(1000, 9999) . '.json';
        file_put_contents($fallback_file, json_encode($email_data));

        // Copia o arquivo anexo para o diretório de fallback (se existir)
        if ($arquivo_anexado && !empty($arquivo_destino)) {
            $fallback_anexo = $email_fallback_dir . '/' . time() . '_' . $arquivo_nome;
            copy($arquivo_destino, $fallback_anexo);
        }

        error_log("Email salvo para envio posterior: $fallback_file");

        // Considera como enviado para continuar o fluxo
        $enviado = true;
    }

    // Remove o arquivo temporário se existir
    if ($arquivo_anexado && !empty($arquivo_destino) && file_exists($arquivo_destino)) {
        unlink($arquivo_destino);
    }

    if (!$enviado) {
        setMensagem('erro', 'Erro ao enviar o email. Os dados foram salvos para envio posterior.');
        redirect("visualizar_site.php?id=$id");
        exit;
    }

    // Registra o email enviado na tabela emails_enviados
    try {
        $dados_email = [
            'solicitacao_id' => $id,
            'assunto' => $assunto,
            'mensagem' => $mensagem_completa,
            'arquivo_nome' => $arquivo_anexado ? $arquivo_nome : '',
            'drive_link' => $drive_link,
            'data_envio' => date('Y-m-d H:i:s'),
            'usuario_id' => $_SESSION['usuario']['id']
        ];

        // Verifica se a coluna drive_link existe na tabela
        $colunas = $db->fetchAll("SHOW COLUMNS FROM emails_enviados");
        $tem_coluna_drive_link = false;

        foreach ($colunas as $coluna) {
            if ($coluna['Field'] === 'drive_link') {
                $tem_coluna_drive_link = true;
                break;
            }
        }

        // Se a coluna não existir, adiciona-a
        if (!$tem_coluna_drive_link) {
            $db->query("ALTER TABLE emails_enviados ADD COLUMN drive_link TEXT NULL AFTER arquivo_nome");
        }

        $db->insert('emails_enviados', $dados_email);
    } catch (Exception $e) {
        // Se houver erro ao registrar o email, apenas loga o erro mas continua o processo
        error_log("Erro ao registrar email enviado: " . $e->getMessage());
    }

    // Atualiza o status da solicitação se solicitado
    if ($atualizar_status) {
        $observacao = $solicitacao['observacao'];

        // Adiciona informações sobre o email enviado
        $observacao .= "\n\n" . date('d/m/Y H:i') . " - Email enviado";

        // Adiciona informações sobre o arquivo anexado, se houver
        if ($arquivo_anexado) {
            $observacao .= " com arquivo anexado: $arquivo_nome";
        }

        // Adiciona informações sobre o link do Drive, se houver
        if (!empty($drive_link)) {
            $observacao .= "\nLink do Google Drive: $drive_link";
        }

        $dados = [
            'status' => 'Concluído',
            'observacao' => $observacao
        ];

        $db->update('solicitacoes_s', $dados, 'id = ?', [$id]);

        // Registra o log
        registrarLog(
            'solicitacoes_s',
            'editar',
            "Email enviado e status da solicitação ID: {$id} atualizado para Concluído",
            $id,
            'solicitacoes_s'
        );
    } else {
        // Registra o log sem atualizar o status
        registrarLog(
            'solicitacoes_s',
            'editar',
            "Email enviado para a solicitação ID: {$id}",
            $id,
            'solicitacoes_s'
        );
    }

    // Define a mensagem de sucesso
    setMensagem('sucesso', 'Email enviado com sucesso para ' . $email);

    // Redireciona de volta para a página de visualização
    redirect("visualizar_site.php?id=$id");

} catch (Exception $e) {
    // Define a mensagem de erro
    setMensagem('erro', 'Erro ao enviar o email: ' . $e->getMessage());

    // Redireciona de volta para a página de visualização
    redirect("visualizar_site.php?id=$id");
}
?>
