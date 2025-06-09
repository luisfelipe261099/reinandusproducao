<?php
// Garante que erros PHP não afetem a saída JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Função para retornar erros em formato JSON
function retornarErro($mensagem, $codigo = 500) {
    http_response_code($codigo);
    echo json_encode(['success' => false, 'message' => $mensagem]);
    exit;
}

// Função para sanitizar nomes de arquivos
function sanitizarNomeArquivo($nome) {
    // Remove acentos
    $nome = preg_replace('/[áàãâä]/ui', 'a', $nome);
    $nome = preg_replace('/[éèêë]/ui', 'e', $nome);
    $nome = preg_replace('/[íìîï]/ui', 'i', $nome);
    $nome = preg_replace('/[óòõôö]/ui', 'o', $nome);
    $nome = preg_replace('/[úùûü]/ui', 'u', $nome);
    $nome = preg_replace('/[ç]/ui', 'c', $nome);

    // Remove caracteres especiais
    $nome = preg_replace('/[^a-z0-9_\-\.]/i', '_', $nome);

    // Remove underscores duplicados
    $nome = preg_replace('/_+/', '_', $nome);

    return strtolower($nome);
}

// Captura erros fatais
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $mensagem = "Erro fatal: {$error['message']} em {$error['file']} na linha {$error['line']}";
        error_log($mensagem);
        echo json_encode(['success' => false, 'message' => 'Erro interno do servidor. Verifique os logs para mais detalhes.']);
    }
});

// Configura manipulador de exceções
set_exception_handler(function($e) {
    $mensagem = "Exceção não capturada: " . $e->getMessage() . " em " . $e->getFile() . " na linha " . $e->getLine();
    error_log($mensagem);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
});

try {
    // Inicializa o sistema
    require_once __DIR__ . '/../includes/init.php';

    // Verifica se o usuário está autenticado
    exigirLogin();

    // Verifica se o usuário tem permissão para acessar o módulo de chamados
    // Alterado de 'editar' para 'visualizar' para permitir que mais usuários possam gerar documentos
    exigirPermissao('chamados', 'visualizar');

    // Verifica se é uma requisição AJAX
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
        retornarErro('Acesso não permitido', 403);
    }
} catch (Exception $e) {
    retornarErro($e->getMessage());
}

try {
    // Instancia o banco de dados
    $db = Database::getInstance();

    // Obtém o ID da solicitação
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    // Verifica se o ID é válido
    if ($id <= 0) {
        retornarErro('ID de solicitação inválido', 400);
    }

    // Log para diagnóstico
    error_log("Iniciando geração de documento para solicitação ID: $id");

    // Obtém os dados da solicitação
    $sql = "SELECT sd.*,
                a.nome as aluno_nome, a.cpf as aluno_cpf, a.email as aluno_email,
                a.curso_id, c.nome as curso_nome, c.carga_horaria as curso_carga_horaria,
                p.nome as polo_nome, p.id as polo_id,
                td.nome as tipo_documento_nome, td.id as tipo_documento_id,
                u.nome as solicitante_nome
            FROM solicitacoes_documentos sd
            JOIN alunos a ON sd.aluno_id = a.id
            LEFT JOIN cursos c ON a.curso_id = c.id
            JOIN polos p ON sd.polo_id = p.id
            JOIN tipos_documentos td ON sd.tipo_documento_id = td.id
            LEFT JOIN usuarios u ON sd.solicitante_id = u.id
            WHERE sd.id = ?";
    $solicitacao = $db->fetchOne($sql, [$id]);

    // Verifica se a solicitação existe
    if (!$solicitacao) {
        retornarErro('Solicitação não encontrada', 404);
    }

    error_log("Solicitação encontrada: " . json_encode($solicitacao));

    // Atualiza o status da solicitação para "processando"
    $db->update('solicitacoes_documentos', [
        'status' => 'processando',
        'updated_at' => date('Y-m-d H:i:s')
    ], 'id = ?', [$id]);

    // Determina o tipo de documento e redireciona para a geração apropriada
    $tipo_documento = strtolower($solicitacao['tipo_documento_nome']);
    $aluno_id = $solicitacao['aluno_id'];

    error_log("Tipo de documento: $tipo_documento, Aluno ID: $aluno_id");

    // Busca dados completos do aluno
    $sql = "SELECT a.*, c.nome as curso_nome, c.carga_horaria as curso_carga_horaria, p.nome as polo_nome
            FROM alunos a
            LEFT JOIN cursos c ON a.curso_id = c.id
            LEFT JOIN polos p ON a.polo_id = p.id
            WHERE a.id = ?";
    $aluno = $db->fetchOne($sql, [$aluno_id]);

    if (!$aluno) {
        throw new Exception("Aluno não encontrado");
    }

    error_log("Dados do aluno: " . json_encode($aluno));

    // Gera o documento com base no tipo
    $documento_id = null;
    $arquivo = null;

    if (strpos($tipo_documento, 'declaração') !== false || strpos($tipo_documento, 'declaracao') !== false) {
        // Gera declaração de matrícula
        // Gera um código de verificação único
        $codigo_verificacao = mt_rand(100000, 999999);

        // Cria o diretório para armazenar os documentos
        $diretorio = '../uploads/documentos';
        if (!file_exists($diretorio)) {
            mkdir($diretorio, 0777, true);
        }

        // Nome do arquivo
        $nome_arquivo = 'declaracao_matricula_' . sanitizarNomeArquivo($aluno['nome']) . '_' . date('Ymd_His') . '.html';
        $caminho_arquivo = $diretorio . '/' . $nome_arquivo;

        // Gera o conteúdo HTML da declaração
        require_once __DIR__ . '/../documentos.php';

        // Verifica se a função existe
        if (!function_exists('gerarConteudoDeclaracao')) {
            throw new Exception("Função gerarConteudoDeclaracao não encontrada");
        }

        error_log("Gerando conteúdo da declaração para aluno: " . $aluno['nome']);
        $html_content = gerarConteudoDeclaracao($aluno, $codigo_verificacao);

        // Salva o HTML no arquivo
        if (file_put_contents($caminho_arquivo, $html_content) === false) {
            throw new Exception("Não foi possível salvar o arquivo em $caminho_arquivo");
        }

        // Prepara os dados para inserção
        $base_numero = "DM" . date('Ymd');
        $numero = $base_numero . str_pad($aluno['id'], 6, '0', STR_PAD_LEFT) . mt_rand(1000, 9999);

        // Verifica se o polo_id está definido
        $polo_id = !empty($aluno['polo_id']) ? $aluno['polo_id'] : 1;

        // Verifica se o curso_id está definido
        $curso_id = !empty($aluno['curso_id']) ? $aluno['curso_id'] : 1;

        // Monta os dados para inserção
        $dados_documento = [
            'tipo_documento_id' => 1, // Assumindo que 1 é o ID para declaração de matrícula
            'aluno_id' => $aluno['id'],
            'matricula_id' => 1, // Valor padrão para matricula_id que é obrigatório
            'curso_id' => $curso_id,
            'polo_id' => $polo_id,
            'data_emissao' => date('Y-m-d'),
            'data_validade' => date('Y-m-d', strtotime('+90 days')),
            'codigo_verificacao' => intval($codigo_verificacao),
            'arquivo' => $nome_arquivo,
            'numero_documento' => $numero,
            'status' => 'ativo',
            'data_solicitacao' => date('Y-m-d'),
            'solicitacao_id' => $id
        ];

        // Insere o documento
        $documento_id = $db->insert('documentos_emitidos', $dados_documento);
        $arquivo = $nome_arquivo;

    } else if (strpos($tipo_documento, 'histórico') !== false || strpos($tipo_documento, 'historico') !== false) {
        // Gera histórico acadêmico
        // Gera um código de verificação único
        $codigo_verificacao = mt_rand(100000, 999999);

        // Cria o diretório para armazenar os documentos
        $diretorio = '../uploads/documentos';
        if (!file_exists($diretorio)) {
            mkdir($diretorio, 0777, true);
        }

        // Nome do arquivo
        $nome_arquivo = 'historico_academico_' . sanitizarNomeArquivo($aluno['nome']) . '_' . date('Ymd_His') . '.html';
        $caminho_arquivo = $diretorio . '/' . $nome_arquivo;

        // Busca as notas do aluno
        $sql = "SELECT nd.*, d.nome as disciplina_nome, d.carga_horaria as disciplina_carga_horaria
                FROM notas_disciplinas nd
                JOIN disciplinas d ON nd.disciplina_id = d.id
                WHERE nd.matricula_id IN (SELECT id FROM matriculas WHERE aluno_id = ?)
                ORDER BY d.nome ASC";
        $notas = $db->fetchAll($sql, [$aluno_id]);

        // Gera o conteúdo HTML do histórico
        require_once __DIR__ . '/../documentos.php';

        // Verifica se a função existe
        if (!function_exists('gerarConteudoHistorico')) {
            throw new Exception("Função gerarConteudoHistorico não encontrada");
        }

        error_log("Gerando conteúdo do histórico para aluno: " . $aluno['nome'] . " com " . count($notas) . " notas");
        $html_content = gerarConteudoHistorico($aluno, $notas, $codigo_verificacao);

        // Salva o HTML no arquivo
        if (file_put_contents($caminho_arquivo, $html_content) === false) {
            throw new Exception("Não foi possível salvar o arquivo em $caminho_arquivo");
        }

        // Prepara os dados para inserção
        $base_numero = "HA" . date('Ymd');
        $numero = $base_numero . str_pad($aluno['id'], 6, '0', STR_PAD_LEFT) . mt_rand(1000, 9999);

        // Verifica se o polo_id está definido
        $polo_id = !empty($aluno['polo_id']) ? $aluno['polo_id'] : 1;

        // Verifica se o curso_id está definido
        $curso_id = !empty($aluno['curso_id']) ? $aluno['curso_id'] : 1;

        // Monta os dados para inserção
        $dados_documento = [
            'tipo_documento_id' => 2, // Assumindo que 2 é o ID para histórico acadêmico
            'aluno_id' => $aluno['id'],
            'matricula_id' => 1, // Valor padrão para matricula_id que é obrigatório
            'curso_id' => $curso_id,
            'polo_id' => $polo_id,
            'data_emissao' => date('Y-m-d'),
            'data_validade' => date('Y-m-d', strtotime('+90 days')),
            'codigo_verificacao' => intval($codigo_verificacao),
            'arquivo' => $nome_arquivo,
            'numero_documento' => $numero,
            'status' => 'ativo',
            'data_solicitacao' => date('Y-m-d'),
            'solicitacao_id' => $id
        ];

        // Insere o documento
        $documento_id = $db->insert('documentos_emitidos', $dados_documento);
        $arquivo = $nome_arquivo;

    } else {
        throw new Exception("Tipo de documento não reconhecido: " . $solicitacao['tipo_documento_nome']);
    }

    if (!$documento_id) {
        throw new Exception("Erro ao registrar documento no banco de dados");
    }

    // Verifica se a coluna documento_id existe na tabela solicitacoes_documentos
    try {
        $colunas = $db->fetchAll("SHOW COLUMNS FROM solicitacoes_documentos LIKE 'documento_id'");

        // Se a coluna não existir, adiciona
        if (empty($colunas)) {
            $db->query("ALTER TABLE solicitacoes_documentos ADD COLUMN documento_id INT(10) UNSIGNED NULL DEFAULT NULL");
        }

        // Atualiza a solicitação com o ID do documento gerado
        $db->update('solicitacoes_documentos', [
            'documento_id' => $documento_id,
            'status' => 'pronto',
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$id]);
    } catch (Exception $e) {
        error_log("Erro ao atualizar solicitação com documento_id: " . $e->getMessage());
        // Não interrompe o fluxo se falhar aqui
    }

    // Verifica se o documento foi gerado com sucesso
    if (!$documento_id) {
        throw new Exception("Falha ao gerar o documento. Nenhum ID de documento foi retornado.");
    }

    error_log("Documento gerado com sucesso. ID: $documento_id, Arquivo: $arquivo");

    // Retorna sucesso
    echo json_encode([
        'success' => true,
        'message' => 'Documento gerado com sucesso',
        'documento_id' => $documento_id,
        'arquivo' => $arquivo,
        'visualizar_url' => '../documentos.php?action=visualizar&id=' . $documento_id,
        'download_url' => '../documentos.php?action=download&id=' . $documento_id
    ]);

} catch (Exception $e) {
    // Registra o erro
    error_log('Erro ao gerar documento: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());

    // Tenta atualizar a solicitação para o status anterior
    try {
        if (isset($db) && isset($id)) {
            $db->update('solicitacoes_documentos', [
                'status' => 'pronto',
                'updated_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$id]);
        }
    } catch (Exception $updateEx) {
        error_log('Erro ao restaurar status da solicitação: ' . $updateEx->getMessage());
    }

    // Retorna erro
    retornarErro('Erro ao gerar documento: ' . $e->getMessage());
}
