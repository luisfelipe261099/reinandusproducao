<?php
/**
 * Script para processar emails pendentes que não puderam ser enviados
 * Este script pode ser executado manualmente ou via cron job
 */

// Inicializa o sistema
require_once __DIR__ . '/../includes/init.php';

// Diretório onde os emails pendentes estão armazenados
$email_fallback_dir = __DIR__ . '/../uploads/emails_fallback';

// Verifica se o diretório existe
if (!file_exists($email_fallback_dir)) {
    echo "Diretório de emails pendentes não encontrado.\n";
    exit(1);
}

// Busca todos os arquivos JSON no diretório
$arquivos = glob($email_fallback_dir . '/email_*.json');

if (empty($arquivos)) {
    echo "Nenhum email pendente encontrado.\n";
    exit(0);
}

echo "Encontrados " . count($arquivos) . " emails pendentes.\n";

// Processa cada arquivo
foreach ($arquivos as $arquivo) {
    echo "Processando: " . basename($arquivo) . "... ";
    
    // Lê o conteúdo do arquivo
    $conteudo = file_get_contents($arquivo);
    $email_data = json_decode($conteudo, true);
    
    if (!$email_data) {
        echo "ERRO: Formato de arquivo inválido.\n";
        continue;
    }
    
    // Extrai os dados do email
    $para = $email_data['para'];
    $de = $email_data['de'];
    $de_nome = $email_data['de_nome'];
    $assunto = $email_data['assunto'];
    $mensagem = $email_data['mensagem'];
    $arquivo_nome = $email_data['arquivo_nome'];
    
    // Verifica se o arquivo anexo existe
    $arquivo_anexo = $email_fallback_dir . '/' . basename($arquivo, '.json') . '_' . $arquivo_nome;
    if (!file_exists($arquivo_anexo)) {
        // Tenta encontrar o arquivo pelo nome parcial
        $possiveis_anexos = glob($email_fallback_dir . '/*_' . $arquivo_nome);
        if (!empty($possiveis_anexos)) {
            $arquivo_anexo = $possiveis_anexos[0];
        } else {
            echo "ERRO: Arquivo anexo não encontrado.\n";
            continue;
        }
    }
    
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
    $corpo .= $mensagem . "\r\n\r\n";
    
    // Anexo
    if (file_exists($arquivo_anexo)) {
        $corpo .= "--$boundary\r\n";
        $corpo .= "Content-Type: " . mime_content_type($arquivo_anexo) . "; name=\"$arquivo_nome\"\r\n";
        $corpo .= "Content-Transfer-Encoding: base64\r\n";
        $corpo .= "Content-Disposition: attachment; filename=\"$arquivo_nome\"\r\n\r\n";
        $corpo .= chunk_split(base64_encode(file_get_contents($arquivo_anexo))) . "\r\n";
    }
    
    $corpo .= "--$boundary--";
    
    // Tenta enviar o email
    $enviado = mail($para, $assunto, $corpo, $headers);
    
    if ($enviado) {
        echo "ENVIADO COM SUCESSO.\n";
        
        // Registra o email enviado no banco de dados
        try {
            $db = Database::getInstance();
            
            $dados_email = [
                'solicitacao_id' => $email_data['solicitacao_id'],
                'assunto' => $assunto,
                'mensagem' => $mensagem,
                'arquivo_nome' => $arquivo_nome,
                'data_envio' => date('Y-m-d H:i:s'),
                'usuario_id' => 1 // Usuário padrão do sistema
            ];
            
            $db->insert('emails_enviados', $dados_email);
            
            // Remove os arquivos processados
            unlink($arquivo);
            if (file_exists($arquivo_anexo)) {
                unlink($arquivo_anexo);
            }
        } catch (Exception $e) {
            echo "ERRO ao registrar no banco de dados: " . $e->getMessage() . "\n";
        }
    } else {
        echo "FALHA NO ENVIO. Será tentado novamente mais tarde.\n";
    }
}

echo "Processamento concluído.\n";
?>
