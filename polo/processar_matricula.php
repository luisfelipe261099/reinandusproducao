<?php
/**
 * Processamento de Nova Matrícula
 */

// Inicializa o sistema
require_once __DIR__ . '/../includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica se o usuário é do tipo polo
if (getUsuarioTipo() !== 'polo') {
    setMensagem('erro', 'Você não tem permissão para acessar esta página.');
    redirect('../index.php');
    exit;
}

// Instancia o banco de dados
$db = Database::getInstance();

// Verifica se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setMensagem('erro', 'Método de requisição inválido.');
    redirect('matriculas.php');
    exit;
}

// Obtém os dados do formulário
$polo_id = $_POST['polo_id'] ?? null;

// Dados pessoais do aluno
$aluno_nome = $_POST['aluno_nome'] ?? '';
$aluno_nome_social = $_POST['aluno_nome_social'] ?? '';
$aluno_cpf = $_POST['aluno_cpf'] ?? '';
$aluno_rg = $_POST['aluno_rg'] ?? '';
$aluno_data_nascimento = $_POST['aluno_data_nascimento'] ?? '';
$aluno_sexo = $_POST['aluno_sexo'] ?? '';

// Dados de contato
$aluno_email = $_POST['aluno_email'] ?? '';
$aluno_telefone = $_POST['aluno_telefone'] ?? '';
$aluno_celular = $_POST['aluno_celular'] ?? '';

// Dados de endereço
$aluno_endereco = $_POST['aluno_endereco'] ?? '';
$aluno_numero = $_POST['aluno_numero'] ?? '';
$aluno_bairro = $_POST['aluno_bairro'] ?? '';
$aluno_cidade = $_POST['aluno_cidade'] ?? '';
$aluno_estado = $_POST['aluno_estado'] ?? '';
$aluno_cep = $_POST['aluno_cep'] ?? '';

// Dados da matrícula
$curso_id = $_POST['curso_id'] ?? '';
$turma_id = $_POST['turma_id'] ?? '';
$data_matricula = $_POST['data_matricula'] ?? date('Y-m-d');
$data_ingresso = $_POST['data_ingresso'] ?? '';
$previsao_conclusao = $_POST['previsao_conclusao'] ?? '';
$status = $_POST['status'] ?? 'ativo';

// Limpa o CPF (remove caracteres não numéricos)
$aluno_cpf = preg_replace('/[^0-9]/', '', $aluno_cpf);

// Validação básica
if (empty($polo_id) || empty($aluno_nome) || empty($aluno_cpf) || empty($aluno_email) ||
    empty($curso_id) || empty($turma_id) || empty($data_matricula) || empty($status)) {
    setMensagem('erro', 'Todos os campos obrigatórios devem ser preenchidos.');
    redirect('matriculas.php?action=nova');
    exit;
}

// Verifica se o polo existe e pertence ao usuário
$usuario_id = getUsuarioId();
$sql = "SELECT id FROM polos WHERE id = ? AND responsavel_id = ?";
$resultado = $db->fetchOne($sql, [$polo_id, $usuario_id]);

if (!$resultado) {
    setMensagem('erro', 'Polo inválido ou não pertence ao seu usuário.');
    redirect('matriculas.php');
    exit;
}

// Verifica se o curso existe e está ativo
$sql = "SELECT id FROM cursos WHERE id = ? AND status = 'ativo'";
$resultado = $db->fetchOne($sql, [$curso_id]);

if (!$resultado) {
    setMensagem('erro', 'Curso inválido ou inativo.');
    redirect('matriculas.php?action=nova');
    exit;
}

// Verifica se a turma existe, está ativa e pertence ao polo
$sql = "SELECT id FROM turmas WHERE id = ? AND status = 'ativo' AND polo_id = ?";
$resultado = $db->fetchOne($sql, [$turma_id, $polo_id]);

if (!$resultado) {
    setMensagem('erro', 'Turma inválida, inativa ou não pertence ao seu polo.');
    redirect('matriculas.php?action=nova');
    exit;
}

try {
    // Inicia uma transação
    $db->beginTransaction();

    // Verifica se o aluno já existe pelo CPF
    $sql = "SELECT id FROM alunos WHERE cpf = ?";
    $aluno = $db->fetchOne($sql, [$aluno_cpf]);

    if ($aluno) {
        // Aluno já existe, usa o ID existente
        $aluno_id = $aluno['id'];

        // Atualiza os dados do aluno
        $sql = "UPDATE alunos SET
                nome = ?,
                nome_social = ?,
                email = ?,
                telefone = ?,
                celular = ?,
                rg = ?,
                data_nascimento = ?,
                sexo = ?,
                endereco = ?,
                numero = ?,
                bairro = ?,
                cep = ?,
                data_ingresso = ?,
                previsao_conclusao = ?,
                curso_id = ?,
                polo_id = ?
                WHERE id = ?";
        $db->query($sql, [
            $aluno_nome,
            $aluno_nome_social ?: null,
            $aluno_email,
            $aluno_telefone ?: null,
            $aluno_celular ?: null,
            $aluno_rg ?: null,
            $aluno_data_nascimento ?: null,
            $aluno_sexo ?: null,
            $aluno_endereco ?: null,
            $aluno_numero ?: null,
            $aluno_bairro ?: null,
            $aluno_cep ?: null,
            $data_ingresso ?: null,
            $previsao_conclusao ?: null,
            $curso_id,
            $polo_id,
            $aluno_id
        ]);
    } else {
        // Cria um novo aluno
        $sql = "INSERT INTO alunos (nome, nome_social, cpf, rg, email, telefone, celular,
                                   data_nascimento, sexo, endereco, numero, bairro,
                                   cidade, estado, cep, data_ingresso, previsao_conclusao,
                                   curso_id, polo_id, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $db->query($sql, [
            $aluno_nome,
            $aluno_nome_social ?: null,
            $aluno_cpf,
            $aluno_rg ?: null,
            $aluno_email,
            $aluno_telefone ?: null,
            $aluno_celular ?: null,
            $aluno_data_nascimento ?: null,
            $aluno_sexo ?: null,
            $aluno_endereco ?: null,
            $aluno_numero ?: null,
            $aluno_bairro ?: null,
            $aluno_cidade ?: null,
            $aluno_estado ?: null,
            $aluno_cep ?: null,
            $data_ingresso ?: null,
            $previsao_conclusao ?: null,
            $curso_id,
            $polo_id,
            $status
        ]);

        // Obtém o ID do aluno recém-criado
        $aluno_id = $db->lastInsertId();
    }

    // Verifica se já existe uma matrícula para este aluno neste curso
    $sql = "SELECT id FROM matriculas WHERE aluno_id = ? AND curso_id = ? AND polo_id = ?";
    $matricula = $db->fetchOne($sql, [$aluno_id, $curso_id, $polo_id]);

    if ($matricula) {
        // Atualiza a matrícula existente
        $sql = "UPDATE matriculas SET
                turma_id = ?,
                data_matricula = ?,
                status = ?
                WHERE id = ?";
        $db->query($sql, [
            $turma_id,
            $data_matricula,
            $status,
            $matricula['id']
        ]);

        $mensagem = 'Matrícula atualizada com sucesso!';
    } else {
        // Cria uma nova matrícula
        $sql = "INSERT INTO matriculas (aluno_id, curso_id, turma_id, polo_id, data_matricula, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $db->query($sql, [
            $aluno_id,
            $curso_id,
            $turma_id,
            $polo_id,
            $data_matricula,
            $status
        ]);

        $mensagem = 'Matrícula realizada com sucesso!';
    }

    // Processa os documentos enviados (se houver)
    if (isset($_FILES['documentos']) && is_array($_FILES['documentos']['name'])) {
        // Verifica se a tabela documentos_alunos existe
        $tabela_existe = false;
        try {
            $result = $db->query("SHOW TABLES LIKE 'documentos_alunos'");
            $tabela_existe = !empty($result);
        } catch (Exception $e) {
            error_log('Erro ao verificar tabela documentos_alunos: ' . $e->getMessage());
        }

        if ($tabela_existe) {
            // Cria o diretório para armazenar os documentos
            $upload_dir = '../uploads/documentos_alunos/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // Processa cada documento
            foreach ($_FILES['documentos']['name'] as $tipo_documento_id => $nome_arquivo) {
                // Verifica se foi enviado um arquivo
                if (empty($nome_arquivo)) {
                    continue;
                }

                // Verifica se o upload foi bem-sucedido
                if ($_FILES['documentos']['error'][$tipo_documento_id] !== UPLOAD_ERR_OK) {
                    continue;
                }

                // Verifica o tipo de arquivo
                $allowed_types = ['application/pdf', 'image/jpeg', 'image/png'];
                $file_type = $_FILES['documentos']['type'][$tipo_documento_id];

                if (!in_array($file_type, $allowed_types)) {
                    continue;
                }

                // Verifica o tamanho do arquivo (máximo 5MB)
                $max_size = 5 * 1024 * 1024; // 5MB em bytes
                if ($_FILES['documentos']['size'][$tipo_documento_id] > $max_size) {
                    continue;
                }

                // Gera um nome único para o arquivo
                $file_extension = pathinfo($_FILES['documentos']['name'][$tipo_documento_id], PATHINFO_EXTENSION);
                $file_name = 'doc_' . $aluno_id . '_' . $tipo_documento_id . '_' . time() . '.' . $file_extension;
                $file_path = $upload_dir . $file_name;

                // Move o arquivo para o diretório de upload
                if (move_uploaded_file($_FILES['documentos']['tmp_name'][$tipo_documento_id], $file_path)) {
                    // Prepara os dados para inserção
                    $dados_documento = [
                        'aluno_id' => $aluno_id,
                        'tipo_documento_id' => $tipo_documento_id,
                        'arquivo' => $file_name,
                        'data_upload' => date('Y-m-d H:i:s'),
                        'status' => 'pendente',
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];

                    // Insere o documento no banco de dados
                    $db->insert('documentos_alunos', $dados_documento);
                }
            }
        }
    }

    // Confirma a transação
    $db->commit();

    setMensagem('sucesso', $mensagem);
    redirect('matriculas.php');

} catch (Exception $e) {
    // Reverte a transação em caso de erro
    $db->rollBack();

    setMensagem('erro', 'Erro ao processar a matrícula: ' . $e->getMessage());
    redirect('matriculas.php?action=nova');
}
