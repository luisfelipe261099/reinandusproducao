<?php
require_once 'init.php';
exigirAcessoAdministrador();

header('Content-Type: application/json');

$acao = $_GET['acao'] ?? $_POST['acao'] ?? '';

switch ($acao) {
    case 'criar_usuario':
        criarUsuario();
        break;
        
    case 'editar_usuario':
        editarUsuario();
        break;
        
    case 'alterar_status_usuario':
        alterarStatusUsuario();
        break;
        
    case 'resetar_senha':
        resetarSenha();
        break;
        
    case 'estatisticas_modulos':
        obterEstatisticasModulos();
        break;
        
    case 'exportar_logs':
        exportarLogs();
        break;
        
    case 'limpar_logs':
        limparLogs();
        break;
        
    case 'backup_sistema':
        executarBackup();
        break;
        
    case 'salvar_configuracao':
        salvarConfiguracao();
        break;
        
    case 'enviar_email_teste':
        enviarEmailTeste();
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Ação não encontrada']);
        break;
}

function criarUsuario() {
    try {
        $nome = $_POST['nome'] ?? '';
        $email = $_POST['email'] ?? '';
        $senha = $_POST['senha'] ?? '';
        $tipo_usuario = $_POST['tipo_usuario'] ?? '';
        $polo_id = $_POST['polo_id'] ?? null;
        
        // Validações
        if (empty($nome) || empty($email) || empty($senha) || empty($tipo_usuario)) {
            throw new Exception('Todos os campos obrigatórios devem ser preenchidos');
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Email inválido');
        }
        
        if (strlen($senha) < 6) {
            throw new Exception('A senha deve ter pelo menos 6 caracteres');
        }
        
        // Verificar se email já existe
        $conn = obterConexao();
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            throw new Exception('Email já cadastrado no sistema');
        }
        
        // Criptografar senha
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
          // Inserir usuário
        $stmt = $conn->prepare("
            INSERT INTO usuarios (nome, email, senha, tipo, status, created_at, updated_at) 
            VALUES (?, ?, ?, ?, 'ativo', NOW(), NOW())
        ");
        $stmt->bind_param("ssss", $nome, $email, $senha_hash, $tipo_usuario);
        
        if ($stmt->execute()) {
            $usuario_id = $conn->insert_id;
            
            registrarAcaoAdministrativa(
                'usuarios',
                'criar',
                "Usuário criado: $nome ($email)",
                ['usuario_id' => $usuario_id, 'tipo_usuario' => $tipo_usuario]
            );
            
            echo json_encode([
                'success' => true,
                'message' => 'Usuário criado com sucesso',
                'usuario_id' => $usuario_id
            ]);
        } else {
            throw new Exception('Erro ao criar usuário: ' . $conn->error);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function editarUsuario() {
    try {
        $usuario_id = $_POST['usuario_id'] ?? '';
        $nome = $_POST['nome'] ?? '';
        $email = $_POST['email'] ?? '';
        $tipo_usuario = $_POST['tipo_usuario'] ?? '';
        $polo_id = $_POST['polo_id'] ?? null;
        
        if (empty($usuario_id) || empty($nome) || empty($email) || empty($tipo_usuario)) {
            throw new Exception('Todos os campos obrigatórios devem ser preenchidos');
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Email inválido');
        }
        
        $conn = obterConexao();
        
        // Verificar se email já existe em outro usuário
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            throw new Exception('Email já cadastrado para outro usuário');
        }
          // Atualizar usuário
        $stmt = $conn->prepare("
            UPDATE usuarios 
            SET nome = ?, email = ?, tipo = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param("sssi", $nome, $email, $tipo_usuario, $usuario_id);
        
        if ($stmt->execute()) {
            registrarAcaoAdministrativa(
                'usuarios',
                'editar',
                "Usuário editado: $nome ($email)",
                ['usuario_id' => $usuario_id, 'tipo_usuario' => $tipo_usuario]
            );
            
            echo json_encode([
                'success' => true,
                'message' => 'Usuário atualizado com sucesso'
            ]);
        } else {
            throw new Exception('Erro ao atualizar usuário: ' . $conn->error);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function alterarStatusUsuario() {
    try {
        $usuario_id = $_POST['usuario_id'] ?? '';
        $status = $_POST['status'] ?? '';
        
        if (empty($usuario_id) || empty($status)) {
            throw new Exception('Parâmetros obrigatórios não fornecidos');
        }
        
        $conn = obterConexao();
        
        // Obter dados do usuário
        $stmt = $conn->prepare("SELECT nome, email FROM usuarios WHERE id = ?");
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $usuario = $result->fetch_assoc();
        
        if (!$usuario) {
            throw new Exception('Usuário não encontrado');
        }
          $campo_status = '';
        $valor_status = '';
        $acao_texto = '';
        
        switch ($status) {
            case 'ativar':
                $campo_status = "status = 'ativo'";
                $acao_texto = 'ativado';
                break;
                
            case 'desativar':
                $campo_status = "status = 'inativo'";
                $acao_texto = 'desativado';
                break;
                
            case 'bloquear':
                $campo_status = "status = 'bloqueado'";
                $acao_texto = 'bloqueado';
                break;
                
            case 'desbloquear':
                $campo_status = "status = 'ativo'";
                $acao_texto = 'desbloqueado';
                break;
                
            default:
                throw new Exception('Status inválido');
        }
          $stmt = $conn->prepare("UPDATE usuarios SET $campo_status, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $usuario_id);
        
        if ($stmt->execute()) {
            registrarAcaoAdministrativa(
                'usuarios',
                'alterar_status',
                "Usuário {$acao_texto}: {$usuario['nome']} ({$usuario['email']})",
                ['usuario_id' => $usuario_id, 'novo_status' => $status]
            );
            
            echo json_encode([
                'success' => true,
                'message' => "Usuário {$acao_texto} com sucesso"
            ]);
        } else {
            throw new Exception('Erro ao alterar status do usuário');
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function resetarSenha() {
    try {
        $usuario_id = $_POST['usuario_id'] ?? '';
        $nova_senha = $_POST['nova_senha'] ?? '';
        
        if (empty($usuario_id)) {
            throw new Exception('ID do usuário é obrigatório');
        }
        
        $conn = obterConexao();
        
        // Obter dados do usuário
        $stmt = $conn->prepare("SELECT nome, email FROM usuarios WHERE id = ?");
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $usuario = $result->fetch_assoc();
        
        if (!$usuario) {
            throw new Exception('Usuário não encontrado');
        }
        
        // Se não foi fornecida nova senha, gerar uma aleatória
        if (empty($nova_senha)) {
            $nova_senha = gerarSenhaAleatoria();
        }
        
        if (strlen($nova_senha) < 6) {
            throw new Exception('A senha deve ter pelo menos 6 caracteres');
        }
        
        $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("UPDATE usuarios SET senha = ?, deve_trocar_senha = 1 WHERE id = ?");
        $stmt->bind_param("si", $senha_hash, $usuario_id);
        
        if ($stmt->execute()) {
            registrarAcaoAdministrativa(
                'usuarios',
                'resetar_senha',
                "Senha resetada para: {$usuario['nome']} ({$usuario['email']})",
                ['usuario_id' => $usuario_id]
            );
            
            echo json_encode([
                'success' => true,
                'message' => 'Senha resetada com sucesso',
                'nova_senha' => $nova_senha
            ]);
        } else {
            throw new Exception('Erro ao resetar senha');
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function obterEstatisticasModulos() {
    try {
        $conn = obterConexao();
        
        // Usuários online (últimos 15 minutos)
        $stmt = $conn->query("
            SELECT COUNT(*) as total 
            FROM logs_sistema 
            WHERE data_acao >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)
            AND acao = 'login'
        ");
        $usuarios_online = $stmt->fetch_assoc()['total'];
        
        // Acessos hoje
        $stmt = $conn->query("
            SELECT COUNT(*) as total 
            FROM logs_sistema 
            WHERE DATE(data_acao) = CURDATE()
        ");
        $acessos_hoje = $stmt->fetch_assoc()['total'];
        
        echo json_encode([
            'success' => true,
            'usuarios_online' => $usuarios_online,
            'acessos_hoje' => $acessos_hoje,
            'modulos_ativos' => 7,
            'status_sistema' => 'Online'
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function exportarLogs() {
    try {
        $filtros = [];
        $where_clauses = [];
        $params = [];
        $types = '';
        
        // Processar filtros
        if (!empty($_POST['data_inicio'])) {
            $where_clauses[] = "DATE(data_acao) >= ?";
            $params[] = $_POST['data_inicio'];
            $types .= 's';
        }
        
        if (!empty($_POST['data_fim'])) {
            $where_clauses[] = "DATE(data_acao) <= ?";
            $params[] = $_POST['data_fim'];
            $types .= 's';
        }
        
        if (!empty($_POST['modulo'])) {
            $where_clauses[] = "modulo = ?";
            $params[] = $_POST['modulo'];
            $types .= 's';
        }
        
        if (!empty($_POST['acao'])) {
            $where_clauses[] = "acao = ?";
            $params[] = $_POST['acao'];
            $types .= 's';
        }
        
        $conn = obterConexao();
        
        $sql = "
            SELECT 
                ls.data_acao,
                ls.modulo,
                ls.acao,
                ls.descricao,
                ls.ip_address,
                u.nome as usuario_nome,
                u.email as usuario_email
            FROM logs_sistema ls
            LEFT JOIN usuarios u ON ls.usuario_id = u.id
        ";
        
        if (!empty($where_clauses)) {
            $sql .= " WHERE " . implode(" AND ", $where_clauses);
        }
        
        $sql .= " ORDER BY ls.data_acao DESC LIMIT 10000";
        
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Gerar CSV
        $filename = 'logs_sistema_' . date('Y-m-d_H-i-s') . '.csv';
        $filepath = '../temp/' . $filename;
        
        // Criar diretório temp se não existir
        if (!is_dir('../temp')) {
            mkdir('../temp', 0755, true);
        }
        
        $file = fopen($filepath, 'w');
        
        // Cabeçalho CSV
        fputcsv($file, [
            'Data/Hora',
            'Módulo',
            'Ação',
            'Descrição',
            'IP',
            'Usuário',
            'Email'
        ]);
        
        // Dados
        while ($row = $result->fetch_assoc()) {
            fputcsv($file, [
                $row['data_acao'],
                $row['modulo'],
                $row['acao'],
                $row['descricao'],
                $row['ip_address'],
                $row['usuario_nome'],
                $row['usuario_email']
            ]);
        }
        
        fclose($file);
        
        registrarAcaoAdministrativa(
            'logs',
            'exportar',
            "Logs exportados para arquivo CSV",
            ['arquivo' => $filename, 'total_registros' => $result->num_rows]
        );
        
        echo json_encode([
            'success' => true,
            'message' => 'Logs exportados com sucesso',
            'arquivo' => $filename,
            'url_download' => '../temp/' . $filename
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function limparLogs() {
    try {
        $dias = $_POST['dias'] ?? 30;
        
        if (!is_numeric($dias) || $dias < 1) {
            throw new Exception('Número de dias inválido');
        }
        
        $conn = obterConexao();
        
        // Contar registros que serão removidos
        $stmt = $conn->prepare("
            SELECT COUNT(*) as total 
            FROM logs_sistema 
            WHERE data_acao < DATE_SUB(NOW(), INTERVAL ? DAY)
        ");
        $stmt->bind_param("i", $dias);
        $stmt->execute();
        $result = $stmt->get_result();
        $total_remover = $result->fetch_assoc()['total'];
        
        // Remover logs antigos
        $stmt = $conn->prepare("
            DELETE FROM logs_sistema 
            WHERE data_acao < DATE_SUB(NOW(), INTERVAL ? DAY)
        ");
        $stmt->bind_param("i", $dias);
        
        if ($stmt->execute()) {
            registrarAcaoAdministrativa(
                'logs',
                'limpar',
                "Logs limpos: removidos $total_remover registros com mais de $dias dias",
                ['dias' => $dias, 'registros_removidos' => $total_remover]
            );
            
            echo json_encode([
                'success' => true,
                'message' => "Logs limpos com sucesso. $total_remover registros removidos.",
                'registros_removidos' => $total_remover
            ]);
        } else {
            throw new Exception('Erro ao limpar logs');
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function executarBackup() {
    try {
        $tipo = $_POST['tipo'] ?? 'completo';
        
        $timestamp = date('Y-m-d_H-i-s');
        $backup_dir = '../backups/';
        
        // Criar diretório de backup se não existir
        if (!is_dir($backup_dir)) {
            mkdir($backup_dir, 0755, true);
        }
        
        $filename = "backup_{$tipo}_{$timestamp}.sql";
        $filepath = $backup_dir . $filename;
        
        // Configurações do banco
        $host = 'localhost'; // Ajustar conforme necessário
        $username = 'root'; // Ajustar conforme necessário
        $password = ''; // Ajustar conforme necessário
        $database = 'u682219090_faciencia_erp'; // Ajustar conforme necessário
        
        $comando = "mysqldump -h $host -u $username";
        if (!empty($password)) {
            $comando .= " -p$password";
        }
        
        if ($tipo === 'estrutura') {
            $comando .= " --no-data";
        } elseif ($tipo === 'dados') {
            $comando .= " --no-create-info";
        }
        
        $comando .= " $database > $filepath";
        
        exec($comando, $output, $return_code);
        
        if ($return_code === 0 && file_exists($filepath)) {
            $tamanho = filesize($filepath);
            
            registrarAcaoAdministrativa(
                'sistema',
                'backup',
                "Backup $tipo realizado com sucesso",
                [
                    'arquivo' => $filename,
                    'tamanho' => $tamanho,
                    'tipo' => $tipo
                ]
            );
            
            echo json_encode([
                'success' => true,
                'message' => 'Backup realizado com sucesso',
                'arquivo' => $filename,
                'tamanho' => formatarBytes($tamanho),
                'url_download' => $backup_dir . $filename
            ]);
        } else {
            throw new Exception('Erro ao executar backup. Verifique as configurações do MySQL.');
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function salvarConfiguracao() {
    try {
        $chave = $_POST['chave'] ?? '';
        $valor = $_POST['valor'] ?? '';
        $categoria = $_POST['categoria'] ?? 'geral';
        
        if (empty($chave)) {
            throw new Exception('Chave da configuração é obrigatória');
        }
        
        $conn = obterConexao();
        
        // Verificar se configuração já existe
        $stmt = $conn->prepare("SELECT id FROM configuracoes_sistema WHERE chave = ?");
        $stmt->bind_param("s", $chave);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Atualizar
            $stmt = $conn->prepare("
                UPDATE configuracoes_sistema 
                SET valor = ?, categoria = ?, data_atualizacao = NOW() 
                WHERE chave = ?
            ");
            $stmt->bind_param("sss", $valor, $categoria, $chave);
            $acao = 'atualizar';
        } else {
            // Inserir
            $stmt = $conn->prepare("
                INSERT INTO configuracoes_sistema (chave, valor, categoria, data_criacao, data_atualizacao) 
                VALUES (?, ?, ?, NOW(), NOW())
            ");
            $stmt->bind_param("sss", $chave, $valor, $categoria);
            $acao = 'criar';
        }
        
        if ($stmt->execute()) {
            registrarAcaoAdministrativa(
                'configuracoes',
                $acao,
                "Configuração {$acao}da: $chave = $valor",
                ['chave' => $chave, 'valor' => $valor, 'categoria' => $categoria]
            );
            
            echo json_encode([
                'success' => true,
                'message' => 'Configuração salva com sucesso'
            ]);
        } else {
            throw new Exception('Erro ao salvar configuração');
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function formatarBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

function enviarEmailTeste() {
    try {
        $email = $_POST['email'] ?? '';
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Email inválido');
        }
        
        $conn = obterConexao();
        
        // Obter configurações de email
        $stmt = $conn->query("
            SELECT chave, valor 
            FROM configuracoes_sistema 
            WHERE chave LIKE 'smtp_%' OR chave LIKE 'email_%'
        ");
        
        $config = [];
        while ($row = $stmt->fetch_assoc()) {
            $config[$row['chave']] = $row['valor'];
        }
        
        // Configurações padrão se não existirem
        $smtp_host = $config['smtp_host'] ?? 'smtp.gmail.com';
        $smtp_port = $config['smtp_port'] ?? 587;
        $smtp_username = $config['smtp_username'] ?? '';
        $smtp_password = $config['smtp_password'] ?? '';
        $smtp_encryption = $config['smtp_encryption'] ?? 'tls';
        $smtp_auth = $config['smtp_auth'] ?? '1';
        $from_name = $config['email_from_name'] ?? 'Faciência ERP';
        $from_address = $config['email_from_address'] ?? 'noreply@faciencia.edu.br';
        $signature = $config['email_signature'] ?? 'Equipe Faciência ERP\nSistema de Gestão Educacional';
        
        // Verificar se as configurações básicas estão preenchidas
        if (empty($smtp_host) || empty($smtp_username) || empty($smtp_password)) {
            throw new Exception('Configurações de email incompletas. Verifique o servidor SMTP, usuário e senha.');
        }
        
        // Tentar enviar email usando a função mail() nativa do PHP
        // Em um ambiente de produção, seria recomendado usar PHPMailer ou SwiftMailer
        $subject = 'Teste de Configuração de Email - Faciência ERP';
        $message = "Olá!\n\n";
        $message .= "Este é um email de teste enviado pelo sistema Faciência ERP.\n\n";
        $message .= "Se você recebeu este email, significa que as configurações de email estão funcionando corretamente.\n\n";
        $message .= "Data/Hora do teste: " . date('d/m/Y H:i:s') . "\n";
        $message .= "IP do servidor: " . ($_SERVER['SERVER_ADDR'] ?? 'Não disponível') . "\n\n";
        $message .= "--\n";
        $message .= $signature;
        
        $headers = array();
        $headers[] = "From: $from_name <$from_address>";
        $headers[] = "Reply-To: $from_address";
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-Type: text/plain; charset=UTF-8";
        $headers[] = "X-Mailer: Faciencia ERP";
        
        $headers_string = implode("\r\n", $headers);
        
        // Tentar enviar
        $resultado = mail($email, $subject, $message, $headers_string);
        
        if ($resultado) {
            registrarAcaoAdministrativa(
                'email',
                'teste_envio',
                "Email de teste enviado para: $email",
                ['destinatario' => $email, 'sucesso' => true]
            );
            
            echo json_encode([
                'success' => true,
                'message' => 'Email de teste enviado com sucesso'
            ]);
        } else {
            throw new Exception('Falha ao enviar email. Verifique as configurações do servidor.');
        }
        
    } catch (Exception $e) {
        registrarAcaoAdministrativa(
            'email',
            'teste_envio_erro',
            "Erro ao enviar email de teste: " . $e->getMessage(),
            ['destinatario' => $email ?? 'N/A', 'erro' => $e->getMessage()]
        );
        
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function gerarSenhaAleatoria($tamanho = 8) {
    $caracteres = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $senha = '';
    for ($i = 0; $i < $tamanho; $i++) {
        $senha .= $caracteres[rand(0, strlen($caracteres) - 1)];
    }
    return $senha;
}
?>
