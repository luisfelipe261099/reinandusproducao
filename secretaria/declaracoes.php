<?php
/**
 * Página de gerenciamento de declarações de matrícula
 *
 * Esta página permite selecionar alunos e emitir APENAS declarações de matrícula
 * no formato PDF.
 */

// Ativa a exibição de erros para diagnóstico (remover após correções)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Aumenta os limites de memória e tempo de execução para processar muitos documentos
ini_set('memory_limit', '256M');
ini_set('max_execution_time', 60); // 1 minuto
set_time_limit(60);

// Define o tamanho do lote para processamento
define('TAMANHO_LOTE', 3); // Processa 3 alunos por lote para evitar sobrecarga

// Carrega as configurações
require_once 'config/config.php';

// Carrega as classes necessárias
require_once 'includes/Database.php';
require_once 'includes/Auth.php';
require_once 'includes/Utils.php';

// Carrega as funções
require_once 'includes/functions.php';
require_once 'includes/init.php';

// Verifica se o TCPDF está instalado, caso contrário inclui
if (!class_exists('TCPDF')) {
    // Tenta incluir a biblioteca TCPDF
    $tcpdf_path = 'vendor/tecnickcom/tcpdf/tcpdf.php';
    if (file_exists($tcpdf_path)) {
        require_once $tcpdf_path;
    } else {
        // Se não encontrar, tenta um caminho alternativo
        require_once 'includes/tcpdf/tcpdf.php';
    }
}

// Inicia a sessão se ainda não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    // Redireciona para a página de login
    header('Location: login.php');
    exit;
}

// Inicializa variáveis
$titulo_pagina = 'Declarações de Matrícula';
$view = 'listar';
$mensagem = $_SESSION['mensagem'] ?? null;
if (isset($_SESSION['mensagem'])) {
    unset($_SESSION['mensagem']);
}

// Conecta ao banco de dados
$db = Database::getInstance();

// Funções para executar consultas com tratamento de erro
function executarConsulta($db, $sql, $params = [], $default = null) {
    try {
        $result = $db->fetchOne($sql, $params);

        // Log para depuração
        error_log('executarConsulta - SQL: ' . $sql);
        error_log('executarConsulta - Params: ' . print_r($params, true));
        error_log('executarConsulta - Result: ' . ($result ? 'Dados encontrados' : 'Nenhum dado encontrado'));

        return $result ?: $default;
    } catch (Exception $e) {
        // Registra o erro no log
        error_log('Erro na consulta SQL: ' . $e->getMessage());
        error_log('SQL com erro: ' . $sql);
        error_log('Parâmetros: ' . print_r($params, true));
        return $default;
    }
}

// Função melhorada para buscar dados do aluno incluindo o polo da matrícula
function buscarDadosAlunoCompletoParaDocumento($db, $aluno_id) {
    // Verifica se a coluna mec existe na tabela polos
    $coluna_mec_existe = false;
    try {
        $colunas = $db->fetchAll("SHOW COLUMNS FROM polos LIKE 'mec'");
        $coluna_mec_existe = !empty($colunas);
    } catch (Exception $e) {
        error_log("Erro ao verificar coluna mec: " . $e->getMessage());
    }

    // Se a coluna não existir, tenta criá-la
    if (!$coluna_mec_existe) {
        try {
            $db->query("ALTER TABLE polos ADD COLUMN mec VARCHAR(255) NULL COMMENT 'Nome do polo registrado no MEC'");
            error_log("Coluna mec adicionada à tabela polos");

            // Atualiza os registros existentes com o valor do campo nome
            $db->query("UPDATE polos SET mec = nome WHERE mec IS NULL");
            error_log("Registros atualizados com valores para o campo mec");

            $coluna_mec_existe = true;
        } catch (Exception $e) {
            error_log("Erro ao adicionar coluna mec: " . $e->getMessage());
        }
    }

    // Busca dados detalhados do aluno com polo da matrícula mais recente (primeiro tenta ativa)
    $sql = "SELECT a.*,
               c.nome as curso_nome,
               c.carga_horaria as curso_carga_horaria,
               t.carga_horaria as turma_carga_horaria,
               t.nome as turma_nome,
               t.id as turma_id,
               m.id as matricula_id,
               m.status as matricula_status,
               p.nome as polo_nome,
               p.razao_social as polo_razao_social,
               " . ($coluna_mec_existe ? "p.mec as polo_mec," : "") . "
               p.id as polo_id
            FROM alunos a
            LEFT JOIN cursos c ON a.curso_id = c.id
            LEFT JOIN matriculas m ON a.id = m.aluno_id AND m.status = 'ativo'
            LEFT JOIN turmas t ON m.turma_id = t.id
            LEFT JOIN polos p ON m.polo_id = p.id
            WHERE a.id = ?
            ORDER BY m.created_at DESC
            LIMIT 1";

    $aluno = executarConsulta($db, $sql, [$aluno_id]);

    // Se não encontrou com matrícula ativa OU se não tem carga horária da turma, tenta buscar qualquer matrícula
    if (!$aluno || empty($aluno['polo_nome']) || empty($aluno['turma_carga_horaria'])) {
        $sql = "SELECT a.*,
                   c.nome as curso_nome,
                   c.carga_horaria as curso_carga_horaria,
                   t.carga_horaria as turma_carga_horaria,
                   t.nome as turma_nome,
                   t.id as turma_id,
                   m.id as matricula_id,
                   m.status as matricula_status,
                   p.nome as polo_nome,
                   p.razao_social as polo_razao_social,
                   " . ($coluna_mec_existe ? "p.mec as polo_mec," : "") . "
                   p.id as polo_id
                FROM alunos a
                LEFT JOIN cursos c ON a.curso_id = c.id
                LEFT JOIN matriculas m ON a.id = m.aluno_id
                LEFT JOIN turmas t ON m.turma_id = t.id
                LEFT JOIN polos p ON m.polo_id = p.id
                WHERE a.id = ?
                ORDER BY m.created_at DESC
                LIMIT 1";

        $aluno_temp = executarConsulta($db, $sql, [$aluno_id]);

        // Se encontrou dados na segunda consulta, usa eles
        if ($aluno_temp) {
            // Se a primeira consulta não retornou dados OU se a segunda tem carga horária da turma e a primeira não
            if (!$aluno || (!empty($aluno_temp['turma_carga_horaria']) && empty($aluno['turma_carga_horaria']))) {
                $aluno = $aluno_temp;
            }
        }
    }

    // Se ainda não encontrou o polo na matrícula, tenta pelo polo_id do aluno
    if (!$aluno || empty($aluno['polo_nome']) && !empty($aluno['polo_id'])) {
        $sql_polo = "SELECT nome, razao_social FROM polos WHERE id = ?";
        $polo = executarConsulta($db, $sql_polo, [$aluno['polo_id']]);

        if ($polo && !empty($polo['nome'])) {
            $aluno['polo_nome'] = $polo['nome'];
            $aluno['polo_razao_social'] = $polo['razao_social'];
        }
    }

    // Caso ainda não tenha polo, busca um polo padrão (primeiro ativo)
    if (!$aluno || empty($aluno['polo_nome'])) {
        $sql_polo_padrao = "SELECT id, nome, razao_social FROM polos WHERE status = 'ativo' LIMIT 1";
        $polo_padrao = executarConsulta($db, $sql_polo_padrao, []);

        if ($polo_padrao) {
            $aluno['polo_nome'] = $polo_padrao['nome'] . ' (Padrão)';
            $aluno['polo_razao_social'] = $polo_padrao['razao_social'] ?? $polo_padrao['nome'] . ' (Padrão)';
            $aluno['polo_id'] = $polo_padrao['id'];
        } else {
            $aluno['polo_nome'] = 'Não informado';
            $aluno['polo_razao_social'] = 'Não informado';
            $aluno['polo_id'] = 1; // valor padrão
        }
    }

    // Se razao_social estiver vazia, usa o nome do polo
    if (empty($aluno['polo_razao_social'])) {
        $aluno['polo_razao_social'] = $aluno['polo_nome'];
    }

    // Log básico para auditoria
    if ($aluno) {
        error_log("Dados do aluno carregados - ID: " . $aluno_id . ", Nome: " . ($aluno['nome'] ?? 'N/A'));
    }

    error_log("Dados do aluno completos: " . json_encode($aluno));
    return $aluno;
}

// Função para criar ou obter uma solicitação de documento
function criarOuObterSolicitacaoDocumento($db, $aluno_id, $polo_id, $tipo_documento_id = 2) {
    // NOTA: tipo_documento_id = 1 para histórico acadêmico, tipo_documento_id = 2 para declaração de matrícula
    try {
        // Verifica se a tabela solicitacoes_documentos existe
        try {
            $tabelas = $db->fetchAll("SHOW TABLES LIKE 'solicitacoes_documentos'");
            if (empty($tabelas)) {
                error_log("ATENÇÃO: Tabela solicitacoes_documentos não existe. Criando tabela...");

                // Cria a tabela se não existir
                $sql_criar_tabela = "CREATE TABLE IF NOT EXISTS solicitacoes_documentos (
                    id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                    aluno_id INT(10) UNSIGNED NOT NULL,
                    polo_id INT(10) UNSIGNED NOT NULL,
                    tipo_documento_id INT(10) UNSIGNED NOT NULL,
                    quantidade INT(11) NOT NULL DEFAULT 1,
                    finalidade VARCHAR(255) NULL DEFAULT NULL,
                    status ENUM('solicitado', 'em_andamento', 'concluido', 'cancelado') NOT NULL DEFAULT 'solicitado',
                    pago TINYINT(1) NOT NULL DEFAULT 0,
                    created_at TIMESTAMP NULL DEFAULT NULL,
                    updated_at TIMESTAMP NULL DEFAULT NULL,
                    PRIMARY KEY (id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

                $db->execute($sql_criar_tabela);
                error_log("Tabela solicitacoes_documentos criada com sucesso.");
            } else {
                // Verifica a estrutura da tabela
                $colunas = $db->fetchAll("SHOW COLUMNS FROM solicitacoes_documentos");
                $nomes_colunas = array_column($colunas, 'Field');
                error_log("Colunas da tabela solicitacoes_documentos: " . implode(", ", $nomes_colunas));
            }
        } catch (Exception $e) {
            error_log("Erro ao verificar/criar tabela solicitacoes_documentos: " . $e->getMessage());
        }

        // Verifica se já existe uma solicitação para este aluno e tipo de documento
        $sql = "SELECT id FROM solicitacoes_documentos
                WHERE aluno_id = ? AND tipo_documento_id = ? AND status = 'solicitado'
                ORDER BY id DESC LIMIT 1";
        $solicitacao = $db->fetchOne($sql, [$aluno_id, $tipo_documento_id]);

        if ($solicitacao && isset($solicitacao['id'])) {
            error_log("Solicitação existente encontrada: " . $solicitacao['id']);
            return $solicitacao['id'];
        }

        // Se não encontrou, cria uma nova solicitação
        error_log("Criando nova solicitação de documento para aluno_id: $aluno_id, polo_id: $polo_id");

        $dados_solicitacao = [
            'aluno_id' => $aluno_id,
            'polo_id' => $polo_id,
            'tipo_documento_id' => $tipo_documento_id,
            'quantidade' => 1,
            'finalidade' => 'Geração automática',
            'status' => 'solicitado',
            'pago' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $solicitacao_id = $db->insert('solicitacoes_documentos', $dados_solicitacao);

        if (!$solicitacao_id) {
            throw new Exception("Erro ao criar solicitação de documento");
        }

        error_log("Nova solicitação criada com ID: " . $solicitacao_id);
        return $solicitacao_id;
    } catch (Exception $e) {
        error_log("Erro ao criar/obter solicitação: " . $e->getMessage());
        error_log("Rastreamento: " . $e->getTraceAsString());
        throw $e;
    }
}

function executarConsultaAll($db, $sql, $params = [], $default = []) {
    try {
        $result = $db->fetchAll($sql, $params);

        // Log para depuração
        error_log('executarConsultaAll - SQL: ' . $sql);
        error_log('executarConsultaAll - Params: ' . print_r($params, true));
        error_log('executarConsultaAll - Result count: ' . ($result ? count($result) : 0));

        return $result ?: $default;
    } catch (Exception $e) {
        // Registra o erro no log
        error_log('Erro na consulta SQL (fetchAll): ' . $e->getMessage());
        error_log('SQL com erro: ' . $sql);
        error_log('Parâmetros: ' . print_r($params, true));
        return $default;
    }
}

// Processa a ação solicitada
// Verifica se a ação está no GET ou no POST
$action = $_GET['action'] ?? ($_POST['action'] ?? 'listar');
error_log("Ação solicitada: " . $action);

switch ($action) {
    case 'baixar_em_lote':
        // Exibe a página para baixar documentos em lote
        error_log("Ação 'baixar_em_lote' detectada. Definindo view para 'baixar_em_lote'");
        $view = 'baixar_em_lote';
        error_log("View definida como: " . $view);
        break;

    case 'processar_download_lote':
        // Processa o download de documentos em lote
        error_log("Iniciando processamento de download em lote");

        // Obtém os parâmetros do formulário
        $tipo_documento_id = $_POST['tipo_documento'] ?? null;
        $turma_id = $_POST['turma_id'] ?? null;
        $polo_id = $_POST['polo_id'] ?? null;
        $data_inicio = $_POST['data_inicio'] ?? null;
        $data_fim = $_POST['data_fim'] ?? null;
        $exibir_polo = isset($_POST['exibir_polo']) && $_POST['exibir_polo'] === '1';

        // Valida o tipo de documento
        if (empty($tipo_documento_id)) {
            $_SESSION['mensagem'] = [
                'tipo' => 'erro',
                'texto' => 'Selecione um tipo de documento.'
            ];
            header('Location: documentos.php?action=baixar_em_lote');
            exit;
        }

        // Constrói a consulta SQL para buscar os documentos
        $sql = "SELECT d.*, a.nome as aluno_nome, a.id as aluno_id, a.polo_id as aluno_polo_id
                FROM documentos_emitidos d
                JOIN alunos a ON d.aluno_id = a.id
                WHERE d.tipo_documento_id = ?";
        $params = [$tipo_documento_id];

        // Adiciona filtros opcionais
        if (!empty($turma_id)) {
            $sql .= " AND a.id IN (SELECT aluno_id FROM matriculas WHERE turma_id = ?)";
            $params[] = $turma_id;
        }

        if (!empty($polo_id)) {
            $sql .= " AND (a.polo_id = ? OR EXISTS (SELECT 1 FROM matriculas m WHERE m.aluno_id = a.id AND m.polo_id = ?))";
            $params[] = $polo_id;
            $params[] = $polo_id;
        }

        if (!empty($data_inicio)) {
            $sql .= " AND DATE(d.data_emissao) >= ?";
            $params[] = $data_inicio;
        }

        if (!empty($data_fim)) {
            $sql .= " AND DATE(d.data_emissao) <= ?";
            $params[] = $data_fim;
        }

        $sql .= " ORDER BY d.data_emissao DESC";

        // Executa a consulta
        $documentos = executarConsultaAll($db, $sql, $params);

        // Verifica se encontrou documentos
        if (empty($documentos)) {
            $_SESSION['mensagem'] = [
                'tipo' => 'erro',
                'texto' => 'Nenhum documento encontrado com os critérios selecionados.'
            ];
            header('Location: documentos.php?action=baixar_em_lote');
            exit;
        }

        error_log("Encontrados " . count($documentos) . " documentos para download em lote");

        // Cria diretório temporário para armazenar os arquivos
        $temp_dir = 'temp';
        if (!file_exists($temp_dir)) {
            mkdir($temp_dir, 0777, true);
        }

        // Cria um arquivo ZIP
        $zip_filename = 'documentos_' . date('YmdHis') . '.zip';
        $zip_path = $temp_dir . '/' . $zip_filename;

        // Inicializa o ZipArchive
        $zip = new ZipArchive();
        if ($zip->open($zip_path, ZipArchive::CREATE) !== TRUE) {
            $_SESSION['mensagem'] = [
                'tipo' => 'erro',
                'texto' => 'Erro ao criar arquivo ZIP.'
            ];
            header('Location: documentos.php?action=baixar_em_lote');
            exit;
        }

        // Array para armazenar os arquivos gerados
        $arquivos_gerados = [];
        $arquivos_adicionados = 0;

        // Processa cada documento
        foreach ($documentos as $documento) {
            try {
                $arquivo = $documento['arquivo'];

                // Verifica se o arquivo existe
                if (!empty($arquivo) && file_exists($arquivo)) {
                    // Adiciona o arquivo ao ZIP
                    $nome_arquivo = basename($arquivo);
                    $zip->addFile($arquivo, $nome_arquivo);
                    $arquivos_adicionados++;
                    error_log("Arquivo adicionado ao ZIP: {$nome_arquivo}");
                } else if (!empty($documento['aluno_id'])) {
                    // Se o arquivo não existe, tenta gerar novamente
                    $aluno = buscarDadosAlunoCompletoParaDocumento($db, $documento['aluno_id']);

                    if ($aluno) {
                        // Define se deve exibir o polo
                        $aluno['exibir_polo'] = $exibir_polo;

                        // Gera o documento de acordo com o tipo
                        if ($tipo_documento_id == 2) { // Declaração de matrícula
                            $arquivo_gerado = gerarDeclaracaoMatriculaPDF($aluno, $documento['id'], null, false, false, true);
                        } else if ($tipo_documento_id == 1) { // Histórico acadêmico
                            // Busca as notas do aluno
                            $sql_notas = "SELECT nd.*, d.nome as disciplina_nome, d.carga_horaria as disciplina_carga_horaria
                                        FROM notas_disciplinas nd
                                        JOIN disciplinas d ON nd.disciplina_id = d.id
                                        WHERE nd.matricula_id IN (SELECT id FROM matriculas WHERE aluno_id = ?)
                                        ORDER BY d.nome ASC";
                            $notas = executarConsultaAll($db, $sql_notas, [$aluno['id']]);

                            $arquivo_gerado = gerarHistoricoAcademicoPDF($aluno, $notas, $documento['id'], null, false, false, true);
                        }

                        if (!empty($arquivo_gerado) && file_exists($arquivo_gerado)) {
                            // Adiciona o arquivo ao ZIP
                            $nome_arquivo = basename($arquivo_gerado);
                            $zip->addFile($arquivo_gerado, $nome_arquivo);
                            $arquivos_gerados[] = $arquivo_gerado;
                            $arquivos_adicionados++;
                            error_log("Arquivo gerado e adicionado ao ZIP: {$nome_arquivo}");
                        }
                    }
                }
            } catch (Exception $e) {
                error_log("Erro ao processar documento ID {$documento['id']}: " . $e->getMessage());
            }
        }

        // Fecha o ZIP
        $zip->close();

        // Verifica se algum arquivo foi adicionado ao ZIP
        if ($arquivos_adicionados == 0) {
            $_SESSION['mensagem'] = [
                'tipo' => 'erro',
                'texto' => 'Não foi possível adicionar nenhum arquivo ao ZIP.'
            ];
            header('Location: documentos.php?action=baixar_em_lote');
            exit;
        }

        // Envia o arquivo ZIP para download
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zip_filename . '"');
        header('Content-Length: ' . filesize($zip_path));
        header('Pragma: no-cache');
        header('Expires: 0');

        // Lê e envia o arquivo
        readfile($zip_path);

        // Limpa os arquivos temporários
        @unlink($zip_path);
        foreach ($arquivos_gerados as $arquivo) {
            if (file_exists($arquivo)) {
                @unlink($arquivo);
            }
        }

        exit;
        break;
    case 'gerar_documentos_multiplos':
        // Processa a geração de documentos para múltiplos alunos
        error_log("Iniciando geração de documentos múltiplos");
        error_log("POST: " . print_r($_POST, true));

        $alunos_ids = $_POST['alunos'] ?? [];
        $tipo_documento = $_POST['tipo_documento'] ?? '';

        error_log("Alunos IDs: " . print_r($alunos_ids, true));
        error_log("Tipo de documento: " . $tipo_documento);

        if (empty($alunos_ids)) {
            error_log("ERRO: Nenhum aluno selecionado");
            $_SESSION['mensagem'] = [
                'tipo' => 'erro',
                'texto' => 'Nenhum aluno selecionado.'
            ];
            header('Location: documentos.php?action=selecionar_aluno');
            exit;
        }

        if (!in_array($tipo_documento, ['declaracao', 'historico'])) {
            error_log("ERRO: Tipo de documento inválido: " . $tipo_documento);
            $_SESSION['mensagem'] = [
                'tipo' => 'erro',
                'texto' => 'Tipo de documento inválido: ' . $tipo_documento
            ];
            header('Location: documentos.php?action=selecionar_aluno');
            exit;
        }

        // Calcula o número de lotes necessários
        $total_alunos = count($alunos_ids);
        $total_lotes = ceil($total_alunos / TAMANHO_LOTE);
        error_log("Validação passou: {$total_alunos} alunos selecionados para gerar {$tipo_documento} em {$total_lotes} lotes");

        // Verifica se deve exibir o polo nas declarações
        $exibir_polo = isset($_POST['exibir_polo']) && $_POST['exibir_polo'] === '1';

        // Armazena os dados na sessão para processamento em lotes
        $_SESSION['processamento'] = [
            'alunos_ids' => $alunos_ids,
            'tipo_documento' => $tipo_documento,
            'total_alunos' => $total_alunos,
            'total_lotes' => $total_lotes,
            'lote_atual' => 0,
            'processados' => 0,
            'arquivos_gerados' => [],
            'erros' => [],
            'iniciado' => time(),
            'exibir_polo' => $exibir_polo
        ];

        // Redireciona para a página de progresso
        header('Location: documentos.php?action=mostrar_progresso');
        exit;

        // Cria diretório para armazenar os documentos
        $diretorio = 'uploads/documentos';
        if (!file_exists($diretorio)) {
            if (!mkdir($diretorio, 0777, true)) {
                error_log("ERRO: Não foi possível criar o diretório de uploads: {$diretorio}");
                // Tenta usar um diretório alternativo
                $diretorio = sys_get_temp_dir() . '/documentos';
                if (!file_exists($diretorio)) {
                    mkdir($diretorio, 0777, true);
                }
                error_log("Usando diretório de uploads alternativo: {$diretorio}");
            } else {
                // Garante que o diretório tenha permissões adequadas
                chmod($diretorio, 0777);
                error_log("Diretório de uploads criado com sucesso: {$diretorio}");
            }
        } else {
            // Garante que o diretório tenha permissões adequadas
            chmod($diretorio, 0777);
            error_log("Diretório de uploads já existe: {$diretorio}");
        }

        // Diretório temporário para armazenar os PDFs individuais
        $temp_dir = 'temp';
        if (!file_exists($temp_dir)) {
            if (!mkdir($temp_dir, 0777, true)) {
                error_log("ERRO: Não foi possível criar o diretório temporário: {$temp_dir}");
                // Tenta usar um diretório alternativo
                $temp_dir = sys_get_temp_dir();
                error_log("Usando diretório temporário alternativo: {$temp_dir}");
            } else {
                // Garante que o diretório tenha permissões adequadas
                chmod($temp_dir, 0777);
                error_log("Diretório temporário criado com sucesso: {$temp_dir}");
            }
        } else {
            // Garante que o diretório tenha permissões adequadas
            chmod($temp_dir, 0777);
            error_log("Diretório temporário já existe: {$temp_dir}");
        }

        // Array para armazenar os caminhos dos arquivos gerados
        $arquivos_gerados = [];
        $alunos_processados = 0;
        $erros = [];

        // Processa cada aluno
        $total_alunos = count($alunos_ids);
        error_log("Processando {$total_alunos} alunos");

        foreach ($alunos_ids as $index => $aluno_id) {
            try {
                error_log("Processando aluno ID {$aluno_id} (" . ($index + 1) . " de {$total_alunos})");

                // Busca dados completos do aluno
                $aluno = buscarDadosAlunoCompletoParaDocumento($db, $aluno_id);

                if (!$aluno) {
                    $erros[] = "Aluno ID {$aluno_id} não encontrado.";
                    continue;
                }

                // Gera o documento de acordo com o tipo
                if ($tipo_documento === 'declaracao') {
                    // Cria uma solicitação para o documento
                    $solicitacao_id = criarOuObterSolicitacaoDocumento($db, $aluno_id, $aluno['polo_id'] ?? 1, 2);

                    // Define se deve exibir o polo na declaração
                    $exibir_polo = isset($_SESSION['processamento']['exibir_polo']) ? $_SESSION['processamento']['exibir_polo'] : true;
                    $aluno['exibir_polo'] = $exibir_polo;

                    // Gera a declaração e armazena o caminho do arquivo
                    $arquivo = gerarDeclaracaoMatriculaPDF($aluno, $solicitacao_id, null, false, false, true);
                    if ($arquivo) {
                        $arquivos_gerados[] = $arquivo;
                        $alunos_processados++;
                        error_log("Declaração gerada com sucesso para aluno ID {$aluno_id}: {$arquivo}");
                    }
                } else if ($tipo_documento === 'historico') {
                    // Busca as notas do aluno
                    $sql = "SELECT nd.*, d.nome as disciplina_nome, d.carga_horaria as disciplina_carga_horaria
                            FROM notas_disciplinas nd
                            JOIN disciplinas d ON nd.disciplina_id = d.id
                            WHERE nd.matricula_id IN (SELECT id FROM matriculas WHERE aluno_id = ?)
                            ORDER BY d.nome ASC";
                    $notas = executarConsultaAll($db, $sql, [$aluno_id]);

                    // Cria uma solicitação para o documento
                    $solicitacao_id = criarOuObterSolicitacaoDocumento($db, $aluno_id, $aluno['polo_id'] ?? 1, 1);

                    // Gera o histórico e armazena o caminho do arquivo
                    $arquivo = gerarHistoricoAcademicoPDF($aluno, $notas, $solicitacao_id, null, false, false, true);
                    if ($arquivo) {
                        $arquivos_gerados[] = $arquivo;
                        $alunos_processados++;
                        error_log("Histórico gerado com sucesso para aluno ID {$aluno_id}: {$arquivo}");
                    }
                }

                // Libera memória após processar cada aluno
                gc_collect_cycles();

            } catch (Exception $e) {
                error_log("Erro ao processar aluno ID {$aluno_id}: " . $e->getMessage());
                $erros[] = "Erro ao processar aluno ID {$aluno_id}: " . $e->getMessage();
            }

            // A cada 5 alunos, libera mais memória
            if (($index + 1) % 5 == 0) {
                gc_collect_cycles();
                error_log("Liberando memória após processar 5 alunos");
            }
        }

        // Verifica se algum documento foi gerado
        if (count($arquivos_gerados) > 0) {
            // Cria um arquivo ZIP com todos os documentos gerados
            $zip_filename = 'documentos_' . date('YmdHis') . '.zip';
            $zip_path = $temp_dir . '/' . $zip_filename;

            error_log("Iniciando geração de ZIP para {$alunos_processados} documentos");

            error_log("Iniciando criação do arquivo ZIP com " . count($arquivos_gerados) . " arquivos");

            // Tenta usar a classe ZipArchive se disponível
            $zip_criado = false;

            if (class_exists('ZipArchive')) {
                try {
                    error_log("Usando ZipArchive para criar o arquivo ZIP");
                    // Cria o objeto ZipArchive
                    $zip = new ZipArchive();
                    if ($zip->open($zip_path, ZipArchive::CREATE) === TRUE) {
                        // Adiciona cada arquivo ao ZIP
                        foreach ($arquivos_gerados as $index => $arquivo) {
                            if (file_exists($arquivo)) {
                                error_log("Adicionando arquivo ao ZIP ({$index}): " . basename($arquivo));
                                $result = $zip->addFile($arquivo, basename($arquivo));
                                if (!$result) {
                                    error_log("ERRO ao adicionar arquivo ao ZIP: {$arquivo}");
                                }
                            } else {
                                error_log("ERRO: Arquivo não existe: {$arquivo}");
                            }
                        }

                        error_log("Fechando arquivo ZIP. Total de arquivos: " . $zip->numFiles);
                        $zip->close();

                        if (file_exists($zip_path) && filesize($zip_path) > 0) {
                            $zip_criado = true;
                            error_log("Arquivo ZIP criado com sucesso: {$zip_path}, tamanho: " . filesize($zip_path) . " bytes");
                        } else {
                            error_log("ERRO: Arquivo ZIP não existe ou está vazio após usar ZipArchive: {$zip_path}");
                        }
                    } else {
                        error_log("ERRO: Não foi possível abrir o arquivo ZIP para escrita: {$zip_path}");
                    }
                } catch (Exception $e) {
                    error_log("ERRO ao usar ZipArchive: " . $e->getMessage());
                }
            } else {
                error_log("ZipArchive não está disponível no PHP");
            }

            // Se não conseguiu criar o ZIP com ZipArchive, tenta usar o comando zip do sistema
            if (!$zip_criado) {
                try {
                    error_log("Tentando criar ZIP usando comando do sistema");

                    // Cria um arquivo de lista com os caminhos dos arquivos
                    $lista_arquivos = $temp_dir . '/lista_arquivos.txt';
                    $conteudo_lista = '';
                    foreach ($arquivos_gerados as $arquivo) {
                        if (file_exists($arquivo)) {
                            $conteudo_lista .= '"' . $arquivo . '"' . PHP_EOL;
                        }
                    }
                    file_put_contents($lista_arquivos, $conteudo_lista);

                    // Comando para criar o ZIP
                    $comando = 'cd ' . escapeshellarg(dirname($zip_path)) . ' && zip -j ' . escapeshellarg(basename($zip_path)) . ' ' . implode(' ', array_map('escapeshellarg', $arquivos_gerados));
                    error_log("Executando comando: {$comando}");

                    $output = shell_exec($comando);
                    error_log("Resultado do comando zip: " . ($output ?? 'Nenhum resultado'));

                    if (file_exists($zip_path) && filesize($zip_path) > 0) {
                        $zip_criado = true;
                        error_log("Arquivo ZIP criado com sucesso usando comando do sistema: {$zip_path}, tamanho: " . filesize($zip_path) . " bytes");
                    } else {
                        error_log("ERRO: Arquivo ZIP não existe ou está vazio após usar comando do sistema: {$zip_path}");
                    }

                    // Remove o arquivo de lista
                    @unlink($lista_arquivos);
                } catch (Exception $e) {
                    error_log("ERRO ao usar comando zip: " . $e->getMessage());
                }
            }

            // Se ainda não conseguiu criar o ZIP, tenta uma terceira abordagem usando PHP puro
            if (!$zip_criado) {
                try {
                    error_log("Tentando criar ZIP usando PHP puro");

                    // Cria um arquivo ZIP usando PHP puro
                    $fp = fopen($zip_path, 'w');
                    if ($fp) {
                        // Cabeçalho do arquivo ZIP
                        fwrite($fp, "PK\x03\x04");

                        // Para cada arquivo
                        foreach ($arquivos_gerados as $arquivo) {
                            if (file_exists($arquivo)) {
                                $conteudo = file_get_contents($arquivo);
                                $nome_arquivo = basename($arquivo);

                                // Adiciona o arquivo ao ZIP
                                fwrite($fp, "\x14\x00\x00\x00\x08\x00");
                                fwrite($fp, "\x00\x00\x00\x00");
                                fwrite($fp, pack('V', crc32($conteudo)));
                                fwrite($fp, pack('V', strlen($conteudo)));
                                fwrite($fp, pack('V', strlen($conteudo)));
                                fwrite($fp, pack('v', strlen($nome_arquivo)));
                                fwrite($fp, pack('v', 0));
                                fwrite($fp, $nome_arquivo);
                                fwrite($fp, $conteudo);
                            }
                        }

                        fclose($fp);

                        if (file_exists($zip_path) && filesize($zip_path) > 0) {
                            $zip_criado = true;
                            error_log("Arquivo ZIP criado com sucesso usando PHP puro: {$zip_path}, tamanho: " . filesize($zip_path) . " bytes");
                        } else {
                            error_log("ERRO: Arquivo ZIP não existe ou está vazio após usar PHP puro: {$zip_path}");
                        }
                    } else {
                        error_log("ERRO: Não foi possível abrir o arquivo ZIP para escrita usando PHP puro: {$zip_path}");
                    }
                } catch (Exception $e) {
                    error_log("ERRO ao usar PHP puro para criar ZIP: " . $e->getMessage());
                }
            }

            // Se conseguiu criar o ZIP, prepara o download
            if ($zip_criado) {
                error_log("Preparando download do arquivo ZIP: {$zip_path}");

                // Verifica se o arquivo existe e tem tamanho
                if (!file_exists($zip_path)) {
                    error_log("ERRO CRÍTICO: Arquivo ZIP não existe: {$zip_path}");
                    $_SESSION['mensagem'] = [
                        'tipo' => 'erro',
                        'texto' => "Erro ao criar arquivo ZIP. Arquivo não existe."
                    ];
                    header('Location: documentos.php?action=selecionar_aluno');
                    exit;
                }

                $filesize = filesize($zip_path);
                if ($filesize <= 0) {
                    error_log("ERRO CRÍTICO: Arquivo ZIP tem tamanho zero: {$zip_path}");
                    $_SESSION['mensagem'] = [
                        'tipo' => 'erro',
                        'texto' => "Erro ao criar arquivo ZIP. Arquivo tem tamanho zero."
                    ];
                    header('Location: documentos.php?action=selecionar_aluno');
                    exit;
                }

                error_log("Arquivo ZIP existe e tem tamanho: {$filesize} bytes");

                // Limpa qualquer saída anterior
                if (ob_get_level()) {
                    ob_end_clean();
                }

                // Prepara o download do arquivo ZIP
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="' . $zip_filename . '"');
                header('Content-Length: ' . filesize($zip_path));
                header('Cache-Control: no-cache, no-store, must-revalidate');
                header('Pragma: no-cache');
                header('Expires: 0');

                // Lê e envia o arquivo
                readfile($zip_path);

                // Limpa os arquivos temporários
                foreach ($arquivos_gerados as $arquivo) {
                    if (file_exists($arquivo)) {
                        @unlink($arquivo);
                    }
                }
                @unlink($zip_path);
                exit;
            } else {
                // Se não conseguiu criar o ZIP, disponibiliza os PDFs individuais
                error_log("Não foi possível criar o arquivo ZIP. Disponibilizando PDFs individuais.");

                // Cria uma página HTML com links para os PDFs individuais
                $html = '<!DOCTYPE html>
                <html>
                <head>
                    <title>Documentos Gerados</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        h1 { color: #333; }
                        .container { max-width: 800px; margin: 0 auto; }
                        .alert { padding: 15px; margin-bottom: 20px; border: 1px solid transparent; border-radius: 4px; }
                        .alert-success { color: #3c763d; background-color: #dff0d8; border-color: #d6e9c6; }
                        .list-group { padding-left: 0; margin-bottom: 20px; }
                        .list-group-item { position: relative; display: block; padding: 10px 15px; margin-bottom: -1px; background-color: #fff; border: 1px solid #ddd; }
                        .list-group-item:first-child { border-top-left-radius: 4px; border-top-right-radius: 4px; }
                        .list-group-item:last-child { margin-bottom: 0; border-bottom-left-radius: 4px; border-bottom-right-radius: 4px; }
                        .btn { display: inline-block; padding: 6px 12px; margin-bottom: 0; font-size: 14px; font-weight: 400; line-height: 1.42857143; text-align: center; white-space: nowrap; vertical-align: middle; cursor: pointer; background-image: none; border: 1px solid transparent; border-radius: 4px; }
                        .btn-primary { color: #fff; background-color: #337ab7; border-color: #2e6da4; }
                        .btn-primary:hover { color: #fff; background-color: #286090; border-color: #204d74; }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <h1>Documentos Gerados</h1>
                        <div class="alert alert-success">
                            <strong>Sucesso!</strong> Foram gerados ' . $alunos_processados . ' documentos.
                        </div>
                        <p>Não foi possível criar um arquivo ZIP com todos os documentos. Por favor, faça o download de cada documento individualmente:</p>
                        <div class="list-group">';

                // Adiciona links para cada PDF
                foreach ($arquivos_gerados as $index => $arquivo) {
                    if (file_exists($arquivo)) {
                        $nome_arquivo = basename($arquivo);
                        $link = 'temp/' . $nome_arquivo;
                        $html .= '<a href="' . $link . '" class="list-group-item" target="_blank">' . $nome_arquivo . '</a>';
                    }
                }

                $html .= '</div>
                        <a href="documentos.php?action=selecionar_aluno" class="btn btn-primary">Voltar</a>
                    </div>
                </body>
                </html>';

                // Salva a página HTML
                $html_path = $temp_dir . '/documentos_gerados.html';
                file_put_contents($html_path, $html);

                // Redireciona para a página HTML
                header('Location: ' . $html_path);
                exit;
            }
        } else {
            $_SESSION['mensagem'] = [
                'tipo' => 'erro',
                'texto' => 'Não foi possível gerar nenhum documento. ' . implode(' ', $erros)
            ];
            header('Location: documentos.php?action=selecionar_aluno');
            exit;
        }
        break;

    case 'gerar_documento_solicitacao':
        // Processa uma solicitação de documento e redireciona para a geração apropriada
        $solicitacao_id = $_GET['id'] ?? null;

        if (empty($solicitacao_id)) {
            $_SESSION['mensagem'] = [
                'tipo' => 'erro',
                'texto' => 'ID da solicitação não informado.'
            ];
            header('Location: documentos.php');
            exit;
        }

        // Busca os dados da solicitação
        $sql = "SELECT sd.*, a.id as aluno_id, a.nome as aluno_nome, td.nome as tipo_documento_nome
                FROM solicitacoes_documentos sd
                JOIN alunos a ON sd.aluno_id = a.id
                JOIN tipos_documentos td ON sd.tipo_documento_id = td.id
                WHERE sd.id = ?";
        $solicitacao = executarConsulta($db, $sql, [$solicitacao_id]);

        if (!$solicitacao) {
            $_SESSION['mensagem'] = [
                'tipo' => 'erro',
                'texto' => 'Solicitação não encontrada.'
            ];
            header('Location: documentos.php');
            exit;
        }

        // Atualiza o status da solicitação para "pronto"
        $dados_atualizacao = [
            'status' => 'pronto',
            'updated_at' => date('Y-m-d H:i:s')
        ];

        try {
            $db->update('solicitacoes_documentos', $dados_atualizacao, 'id = ?', [$solicitacao_id]);
            error_log("Solicitação ID {$solicitacao_id} atualizada para status 'pronto'");
        } catch (Exception $e) {
            error_log("Erro ao atualizar status da solicitação: " . $e->getMessage());
        }

        // Determina o tipo de documento e redireciona para a ação apropriada
        $tipo_documento = strtolower($solicitacao['tipo_documento_nome']);
        $aluno_id = $solicitacao['aluno_id'];

        if (strpos($tipo_documento, 'declaração') !== false || strpos($tipo_documento, 'declaracao') !== false) {
            // Redireciona para gerar declaração
            header("Location: documentos.php?action=gerar_declaracao&aluno_id={$aluno_id}&solicitacao_id={$solicitacao_id}");
            exit;
        } else if (strpos($tipo_documento, 'histórico') !== false || strpos($tipo_documento, 'historico') !== false) {
            // Redireciona para gerar histórico
            header("Location: documentos.php?action=gerar_historico&aluno_id={$aluno_id}&solicitacao_id={$solicitacao_id}");
            exit;
        } else {
            // Tipo de documento não reconhecido
            $_SESSION['mensagem'] = [
                'tipo' => 'erro',
                'texto' => 'Tipo de documento não reconhecido: ' . $solicitacao['tipo_documento_nome']
            ];
            header('Location: documentos.php');
            exit;
        }

    case 'gerar_declaracao':
        // Gera declaração de matrícula em PDF
        $aluno_id = $_GET['aluno_id'] ?? null;
        $solicitacao_id = $_GET['solicitacao_id'] ?? null;
        $exibir_polo = isset($_GET['exibir_polo']) ? ($_GET['exibir_polo'] === '1') : true;

        if (empty($aluno_id)) {
            $_SESSION['mensagem'] = [
                'tipo' => 'erro',
                'texto' => 'Aluno não informado.'
            ];
            header('Location: documentos.php');
            exit;
        }

        // Se o formulário não foi enviado, mostra a página de opções
        if (!isset($_GET['confirmar'])) {
            // Busca dados do aluno para exibir na página de opções
            $aluno = buscarDadosAlunoCompletoParaDocumento($db, $aluno_id);

            if (!$aluno) {
                $_SESSION['mensagem'] = [
                    'tipo' => 'erro',
                    'texto' => 'Aluno não encontrado.'
                ];
                header('Location: documentos.php');
                exit;
            }

            // Exibe a página de opções
            $titulo_pagina = 'Opções de Declaração';
            $view = 'opcoes_declaracao';
            break;
        }

        // Busca dados completos do aluno usando a nova função
        $aluno = buscarDadosAlunoCompletoParaDocumento($db, $aluno_id);

        if (!$aluno) {
            $_SESSION['mensagem'] = [
                'tipo' => 'erro',
                'texto' => 'Aluno não encontrado.'
            ];
            header('Location: documentos.php');
            exit;
        }

        // Gera o PDF da declaração com a opção de exibir ou não o polo
        $aluno['exibir_polo'] = $exibir_polo;
        gerarDeclaracaoMatriculaPDF($aluno, $solicitacao_id);
        exit;

    case 'gerar_historico':
        // Gera histórico acadêmico em PDF
        $aluno_id = $_GET['aluno_id'] ?? null;
        $solicitacao_id = $_GET['solicitacao_id'] ?? null;

        if (empty($aluno_id)) {
            $_SESSION['mensagem'] = [
                'tipo' => 'erro',
                'texto' => 'Aluno não informado.'
            ];
            header('Location: documentos.php');
            exit;
        }

        // Busca dados completos do aluno usando a nova função
        $aluno = buscarDadosAlunoCompletoParaDocumento($db, $aluno_id);

        if (!$aluno) {
            $_SESSION['mensagem'] = [
                'tipo' => 'erro',
                'texto' => 'Aluno não encontrado.'
            ];
            header('Location: documentos.php');
            exit;
        }

        // Busca as notas do aluno
        $sql = "SELECT nd.*, d.nome as disciplina_nome, d.carga_horaria as disciplina_carga_horaria
                FROM notas_disciplinas nd
                JOIN disciplinas d ON nd.disciplina_id = d.id
                WHERE nd.matricula_id IN (SELECT id FROM matriculas WHERE aluno_id = ?)
                ORDER BY d.nome ASC";
        $notas = executarConsultaAll($db, $sql, [$aluno_id]);

        // Gera o PDF do histórico
        gerarHistoricoAcademicoPDF($aluno, $notas, $solicitacao_id);
        exit;

    case 'selecionar_aluno':
        // Exibe a página para selecionar um aluno
        $titulo_pagina = 'Selecionar Aluno para Documento';
        $view = 'selecionar_aluno';

        // Busca por nome ou CPF
        $busca = $_GET['busca'] ?? '';
        $turma_id = $_GET['turma_id'] ?? '';
        $where = [];
        $params = [];

        if (!empty($busca)) {
            $where[] = "(a.nome LIKE ? OR a.cpf LIKE ?)";
            $params[] = "%$busca%";
            $params[] = "%$busca%";
        }

        if (!empty($turma_id)) {
            $where[] = "(m.turma_id = ? OR t.id = ?)";
            $params[] = $turma_id;
            $params[] = $turma_id;
        }

        // Monta a cláusula WHERE
        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        // Consulta os alunos
        if (!empty($turma_id)) {
            // Se tiver filtro por turma, usa uma consulta otimizada para buscar todos os alunos da turma
            if (!empty($busca)) {
                // Se também tiver busca por nome ou CPF
                $sql = "SELECT DISTINCT a.id, a.nome, a.cpf, a.email, c.nome as curso_nome, t.nome as turma_nome, t.id as turma_id
                        FROM alunos a
                        INNER JOIN matriculas m ON a.id = m.aluno_id
                        INNER JOIN turmas t ON m.turma_id = t.id
                        LEFT JOIN cursos c ON a.curso_id = c.id
                        WHERE t.id = ? AND (a.nome LIKE ? OR a.cpf LIKE ?)
                        ORDER BY a.nome ASC
                        LIMIT 1000";
                $alunos = executarConsultaAll($db, $sql, [$turma_id, "%$busca%", "%$busca%"]);
            } else {
                // Apenas filtro por turma
                $sql = "SELECT DISTINCT a.id, a.nome, a.cpf, a.email, c.nome as curso_nome, t.nome as turma_nome, t.id as turma_id
                        FROM alunos a
                        INNER JOIN matriculas m ON a.id = m.aluno_id
                        INNER JOIN turmas t ON m.turma_id = t.id
                        LEFT JOIN cursos c ON a.curso_id = c.id
                        WHERE t.id = ?
                        ORDER BY a.nome ASC
                        LIMIT 1000";
                $alunos = executarConsultaAll($db, $sql, [$turma_id]);
            }

            error_log("Filtro por turma ID {$turma_id}" . (!empty($busca) ? " e busca por '{$busca}'" : "") . ": " . count($alunos) . " alunos encontrados");
        } else {
            // Consulta padrão sem filtro por turma ou com outros filtros
            $sql = "SELECT DISTINCT a.id, a.nome, a.cpf, a.email, c.nome as curso_nome, t.nome as turma_nome, t.id as turma_id
                    FROM alunos a
                    LEFT JOIN cursos c ON a.curso_id = c.id
                    LEFT JOIN matriculas m ON a.id = m.aluno_id
                    LEFT JOIN turmas t ON m.turma_id = t.id
                    $whereClause
                    ORDER BY a.nome ASC
                    LIMIT 500";
            $alunos = executarConsultaAll($db, $sql, $params);
        }

        // Carrega todas as turmas para o filtro
        $sql_turmas = "SELECT t.id, t.nome, c.nome as curso_nome
                      FROM turmas t
                      LEFT JOIN cursos c ON t.curso_id = c.id
                      ORDER BY t.nome ASC";
        $turmas = executarConsultaAll($db, $sql_turmas);
        break;

    case 'configuracoes':
        // Exibe a página de configurações
        $titulo_pagina = 'Configurações de Documentos';
        $view = 'configuracoes';
        break;

    case 'salvar_tipo':
        // Salva um novo tipo de documento ou atualiza um existente
        $id = $_POST['id'] ?? null;
        $nome = $_POST['nome'] ?? '';
        $descricao = $_POST['descricao'] ?? '';
        $status = $_POST['status'] ?? 'ativo';

        if (empty($nome)) {
            $_SESSION['mensagem'] = [
                'tipo' => 'erro',
                'texto' => 'O nome do tipo de documento é obrigatório.'
            ];
            header('Location: documentos.php?action=configuracoes');
            exit;
        }

        if ($id) {
            // Atualiza o tipo existente
            $sql = "UPDATE tipos_documentos SET nome = ?, descricao = ?, status = ?, updated_at = NOW() WHERE id = ?";
            $params = [$nome, $descricao, $status, $id];
            $mensagem = 'Tipo de documento atualizado com sucesso.';
        } else {
            // Insere um novo tipo
            $sql = "INSERT INTO tipos_documentos (nome, descricao, status, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())";
            $params = [$nome, $descricao, $status];
            $mensagem = 'Tipo de documento criado com sucesso.';
        }

        executarConsulta($db, $sql, $params);
        $_SESSION['mensagem'] = [
            'tipo' => 'sucesso',
            'texto' => $mensagem
        ];
        header('Location: documentos.php?action=configuracoes');
        exit;

    case 'listar':
        // Exibe a lista de documentos emitidos
        $titulo_pagina = 'Lista de Documentos Emitidos';
        $view = 'listar';

        // Filtros já são processados na view
        break;

    case 'download':
        // Faz o download do documento
        $documento_id = $_GET['id'] ?? null;

        if (empty($documento_id)) {
            $_SESSION['mensagem'] = [
                'tipo' => 'erro',
                'texto' => 'Documento não informado.'
            ];
            header('Location: documentos.php');
            exit;
        }

        // Busca o documento
        $sql = "SELECT d.*, a.nome as aluno_nome, td.nome as tipo_documento_nome
                FROM documentos_emitidos d
                LEFT JOIN alunos a ON d.aluno_id = a.id
                LEFT JOIN tipos_documentos td ON d.tipo_documento_id = td.id
                WHERE d.id = ?";
        $documento = executarConsulta($db, $sql, [$documento_id]);

        if (!$documento) {
            $_SESSION['mensagem'] = [
                'tipo' => 'erro',
                'texto' => 'Documento não encontrado.'
            ];
            header('Location: documentos.php');
            exit;
        }

        // Verifica se o arquivo existe
        $arquivo = 'uploads/documentos/' . $documento['arquivo'];
        $arquivo_encontrado = false;
        $novo_arquivo = null;

        // Registra o caminho para debug
        error_log("Tentando acessar arquivo: " . $arquivo);
        error_log("O arquivo existe? " . (file_exists($arquivo) ? "Sim" : "Não"));

        if (file_exists($arquivo)) {
            $arquivo_encontrado = true;
        } else {
            // Tenta encontrar o arquivo pelo nome em uploads/documentos
            $dir_uploads = 'uploads/documentos/';
            if (is_dir($dir_uploads)) {
                $arquivos = scandir($dir_uploads);
                $nome_arquivo = basename($documento['arquivo']);

                // Extrai o padrão base do nome do arquivo (sem timestamp)
                $partes_nome = explode('_', $nome_arquivo);
                if (count($partes_nome) >= 4) {
                    $tipo_doc = $partes_nome[0] . '_' . $partes_nome[1];
                    $nome_aluno = '';

                    // Reconstrói o nome do aluno da parte do arquivo
                    for ($i = 2; $i < count($partes_nome) - 2; $i++) {
                        $nome_aluno .= $partes_nome[$i] . '_';
                    }
                    $nome_aluno = rtrim($nome_aluno, '_');

                    $padrao_arquivo = $tipo_doc . '_' . $nome_aluno;
                    error_log("Procurando por arquivos com padrão: " . $padrao_arquivo);

                    // Busca por arquivos que correspondam ao padrão
                    foreach ($arquivos as $arq) {
                        error_log("Verificando arquivo: " . $arq);

                        // Verifica se o arquivo corresponde exatamente
                        if (strtolower($arq) === strtolower($nome_arquivo)) {
                            $arquivo = $dir_uploads . $arq;
                            $arquivo_encontrado = true;
                            error_log("Arquivo encontrado com nome exato: " . $arquivo);
                            break;
                        }

                        // Verifica se o arquivo corresponde ao padrão (mesmo tipo e nome de aluno)
                        if (strpos(strtolower($arq), strtolower($padrao_arquivo)) === 0) {
                            $novo_arquivo = $dir_uploads . $arq;
                            error_log("Arquivo encontrado com padrão similar: " . $novo_arquivo);
                            // Não interrompe o loop para tentar encontrar uma correspondência exata primeiro
                        }
                    }

                    // Se não encontrou o arquivo exato, mas encontrou um com padrão similar
                    if (!$arquivo_encontrado && $novo_arquivo) {
                        $arquivo = $novo_arquivo;
                        $arquivo_encontrado = true;

                        // Atualiza o registro no banco de dados com o novo nome de arquivo
                        try {
                            $novo_nome_arquivo = basename($novo_arquivo);
                            $sql_update = "UPDATE documentos_emitidos SET arquivo = ? WHERE id = ?";

                            // Usa o método query do PDO diretamente
                            $stmt = $db->getConnection()->prepare($sql_update);
                            $stmt->execute([$novo_nome_arquivo, $documento_id]);

                            error_log("Registro atualizado no banco de dados com o novo nome de arquivo: " . $novo_nome_arquivo);

                            // Atualiza também o documento na memória
                            $documento['arquivo'] = $novo_nome_arquivo;
                        } catch (Exception $e) {
                            error_log("Erro ao atualizar registro no banco de dados: " . $e->getMessage());
                        }
                    }
                } else {
                    error_log("Nome de arquivo não tem formato esperado: " . $nome_arquivo);
                }
            }

            // Se ainda não encontrou, verifica na pasta temp
            if (!$arquivo_encontrado) {
                $arquivo_temp = 'temp/' . basename($documento['arquivo']);
                error_log("Verificando em pasta alternativa: " . $arquivo_temp);

                if (file_exists($arquivo_temp)) {
                    $arquivo = $arquivo_temp;
                    $arquivo_encontrado = true;
                    error_log("Arquivo encontrado em pasta alternativa: " . $arquivo);
                }
            }

            // Se ainda não encontrou, tenta regenerar o documento
            if (!$arquivo_encontrado) {
                error_log("Arquivo não encontrado. Tentando regenerar o documento.");

                // Busca os dados do aluno
                $sql_aluno = "SELECT a.*, c.nome as curso_nome, c.carga_horaria as curso_carga_horaria, p.nome as polo_nome
                             FROM alunos a
                             LEFT JOIN cursos c ON a.curso_id = c.id
                             LEFT JOIN polos p ON a.polo_id = p.id
                             WHERE a.id = ?";
                $aluno = executarConsulta($db, $sql_aluno, [$documento['aluno_id']]);

                if ($aluno) {
                    if ($documento['tipo_documento_id'] == 2) {
                        // Tipo 2 = Declaração de matrícula - regenerar como PDF
                        gerarDeclaracaoMatriculaPDF($aluno, null, $documento['codigo_verificacao'], true);
                        error_log("Regenerando DECLARAÇÃO DE MATRÍCULA como PDF para o aluno ID: " . $aluno['id']);
                    } else if ($documento['tipo_documento_id'] == 1) {
                        // Tipo 1 = Histórico acadêmico - regenerar como PDF
                        $sql_notas = "SELECT n.*, d.nome as disciplina_nome, d.carga_horaria as disciplina_carga_horaria
                                     FROM notas n
                                     LEFT JOIN disciplinas d ON n.disciplina_id = d.id
                                     WHERE n.aluno_id = ?";
                        $notas = executarConsultaAll($db, $sql_notas, [$documento['aluno_id']]);
                        gerarHistoricoAcademicoPDF($aluno, $notas, null, $documento['codigo_verificacao'], true);
                        error_log("Regenerando HISTÓRICO ACADÊMICO como PDF para o aluno ID: " . $aluno['id']);
                    } else {
                        // Tipo de documento desconhecido
                        $_SESSION['mensagem'] = [
                            'tipo' => 'erro',
                            'texto' => 'Tipo de documento desconhecido.'
                        ];
                        header('Location: documentos.php');
                        exit;
                    }
                    // O download será feito automaticamente pelo método de regeneração
                    exit;
                }
            }

            // Se não encontrou o arquivo, exibe mensagem de erro
            if (!$arquivo_encontrado) {
                error_log("Arquivo não encontrado em nenhum local: " . $documento['arquivo']);
                $_SESSION['mensagem'] = [
                    'tipo' => 'erro',
                    'texto' => 'Arquivo não encontrado no servidor. Caminho registrado: ' . $documento['arquivo']
                ];
                header('Location: documentos.php');
                exit;
            }
        }

        // Define os headers para download
        $extension = pathinfo($arquivo, PATHINFO_EXTENSION);
        $content_type = $extension === 'html' ? 'text/html' : 'application/pdf';

        header('Content-Type: ' . $content_type);
        header('Content-Disposition: attachment; filename="' . basename($arquivo) . '"');
        header('Content-Length: ' . filesize($arquivo));

        // Envia o arquivo
        readfile($arquivo);
        exit;

    case 'visualizar':
        // Redireciona para a página de visualização
        $documento_id = $_GET['id'] ?? null;

        if (empty($documento_id)) {
            $_SESSION['mensagem'] = [
                'tipo' => 'erro',
                'texto' => 'Documento não informado.'
            ];
            header('Location: documentos.php');
            exit;
        }

        // Busca o documento
        $sql = "SELECT d.*, a.nome as aluno_nome, td.nome as tipo_documento_nome
                FROM documentos_emitidos d
                LEFT JOIN alunos a ON d.aluno_id = a.id
                LEFT JOIN tipos_documentos td ON d.tipo_documento_id = td.id
                WHERE d.id = ?";
        $documento = executarConsulta($db, $sql, [$documento_id]);

        if (!$documento) {
            $_SESSION['mensagem'] = [
                'tipo' => 'erro',
                'texto' => 'Documento não encontrado.'
            ];
            header('Location: documentos.php');
            exit;
        }

        // Verifica se o arquivo existe
        $arquivo = 'uploads/documentos/' . $documento['arquivo'];
        $arquivo_encontrado = false;

        error_log("Tentando localizar arquivo: " . $arquivo . " (ID: " . $documento_id . ")");
        error_log("Informações do documento: " . json_encode($documento));

      // Cria o diretório se não existir
       if (!is_dir('uploads/documentos/')) {
           error_log("Criando diretório uploads/documentos/");
           mkdir('uploads/documentos/', 0777, true);
       }

       if (file_exists($arquivo)) {
           $arquivo_encontrado = true;
           error_log("Arquivo encontrado no caminho principal: " . $arquivo);
       } else {
           error_log("Arquivo não encontrado no caminho principal, tentando alternativas");

           // Tenta encontrar o arquivo pelo nome em uploads/documentos (ignorando case)
           $dir_uploads = 'uploads/documentos/';
           if (is_dir($dir_uploads)) {
               $arquivos = scandir($dir_uploads);
               $nome_arquivo = basename($documento['arquivo']);
               error_log("Procurando por arquivo com nome: " . $nome_arquivo . " em " . $dir_uploads);

               foreach ($arquivos as $arq) {
                   error_log("Verificando arquivo: " . $arq);
                   if (strtolower($arq) === strtolower($nome_arquivo)) {
                       $arquivo = $dir_uploads . $arq;
                       $arquivo_encontrado = true;
                       error_log("Arquivo encontrado (case insensitive): " . $arquivo);
                       break;
                   }
               }

               // Se ainda não encontrou, tenta uma busca parcial
               if (!$arquivo_encontrado) {
                   error_log("Tentando busca parcial pelo nome do arquivo");
                   $nome_base = pathinfo($nome_arquivo, PATHINFO_FILENAME);
                   $extensao = pathinfo($nome_arquivo, PATHINFO_EXTENSION);

                   foreach ($arquivos as $arq) {
                       if (strpos(strtolower($arq), strtolower($nome_base)) !== false &&
                           pathinfo($arq, PATHINFO_EXTENSION) === $extensao) {
                           $arquivo = $dir_uploads . $arq;
                           $arquivo_encontrado = true;
                           error_log("Arquivo encontrado por busca parcial: " . $arquivo);
                           break;
                       }
                   }
               }
           }

           // Se ainda não encontrou, tenta regenerar o documento
           if (!$arquivo_encontrado && !empty($documento['aluno_id'])) {
               error_log("Tentando gerar o documento novamente em PDF");

               // Busca os dados do aluno
               $sql_aluno = "SELECT a.*, c.nome as curso_nome, c.carga_horaria as curso_carga_horaria, p.nome as polo_nome, p.id as polo_id
                            FROM alunos a
                            LEFT JOIN cursos c ON a.curso_id = c.id
                            LEFT JOIN polos p ON a.polo_id = p.id
                            WHERE a.id = ?";
               $aluno = executarConsulta($db, $sql_aluno, [$documento['aluno_id']]);

               if ($aluno) {
                   error_log("Aluno encontrado: " . $aluno['nome']);

                   // Regenera o documento de acordo com o tipo
                   if ($documento['tipo_documento_id'] == 1) {
                       // Tipo 1 = Histórico acadêmico
                       $sql_notas = "SELECT n.*, d.nome as disciplina_nome, d.carga_horaria as disciplina_carga_horaria
                                    FROM notas n
                                    LEFT JOIN disciplinas d ON n.disciplina_id = d.id
                                    WHERE n.aluno_id = ?";
                       $notas = executarConsultaAll($db, $sql_notas, [$documento['aluno_id']]);

                       // Regenera o histórico como PDF para visualização
                       gerarHistoricoAcademicoPDF($aluno, $notas, null, $documento['codigo_verificacao'], true, true);
                       exit;
                   } else {
                       // Tipo 2 = Declaração de matrícula (ou outro tipo)
                       // Regenera a declaração como PDF para visualização
                       gerarDeclaracaoMatriculaPDF($aluno, null, $documento['codigo_verificacao'], true, true);
                       exit;
                   }
               } else {
                   error_log("Aluno não encontrado para regenerar o documento");
               }
           }
       }

       if (!$arquivo_encontrado) {
           $_SESSION['mensagem'] = [
               'tipo' => 'erro',
               'texto' => 'Arquivo não encontrado no servidor.'
           ];
           header('Location: documentos.php');
           exit;
       }

       // Define o tipo de conteúdo com base na extensão do arquivo
       $extension = pathinfo($arquivo, PATHINFO_EXTENSION);
       $content_type = $extension === 'html' ? 'text/html' : 'application/pdf';

       // Verifica se o arquivo existe novamente antes de tentar exibi-lo
       if (!file_exists($arquivo)) {
           error_log("ERRO CRÍTICO: Arquivo não encontrado no momento de exibir: " . $arquivo);
           $_SESSION['mensagem'] = [
               'tipo' => 'erro',
               'texto' => 'Arquivo não encontrado no servidor no momento de exibir.'
           ];
           header('Location: documentos.php');
           exit;
       }

       // Define os cabeçalhos para exibir o documento diretamente no navegador
       header('Content-Type: ' . $content_type);
       header('Content-Disposition: inline; filename="' . basename($arquivo) . '"');
       header('Cache-Control: public, max-age=0');

       // Exibe o conteúdo do arquivo
       readfile($arquivo);
       exit;



   case 'mostrar_progresso':
       // Mostra a página de progresso para processamento em lotes
       if (!isset($_SESSION['processamento'])) {
           $_SESSION['mensagem'] = [
               'tipo' => 'erro',
               'texto' => 'Nenhum processamento em andamento.'
           ];
           header('Location: documentos.php?action=selecionar_aluno');
           exit;
       }

       $total_lotes = $_SESSION['processamento']['total_lotes'];
       $titulo_pagina = 'Processando Documentos';
       $view = 'progresso';

       // Log para depuração
       error_log("Mostrando página de progresso. Total de lotes: {$total_lotes}, Total de alunos: " . $_SESSION['processamento']['total_alunos']);
       break;

   case 'processar_lote':
       // Processa um lote de documentos e retorna o resultado como JSON
       header('Content-Type: application/json');

       // Log para depuração
       error_log("Recebida solicitação para processar lote. Parâmetros: " . print_r($_GET, true));

       // Aumenta o tempo limite para este script específico
       set_time_limit(120); // 2 minutos por lote

       // Verifica se há um processamento em andamento
       if (!isset($_SESSION['processamento'])) {
           echo json_encode(['error' => 'Nenhum processamento em andamento.']);
           exit;
       }

       // Obtém os parâmetros
       $lote = (int)($_GET['lote'] ?? 1);
       $total_lotes = (int)($_GET['total_lotes'] ?? 1);

       // Verifica se o lote é válido
       if ($lote < 1 || $lote > $total_lotes) {
           echo json_encode(['error' => 'Lote inválido.']);
           exit;
       }

       // Registra o início do processamento do lote
       error_log("Iniciando processamento do lote {$lote} de {$total_lotes}");
       $tempo_inicio_lote = microtime(true);

       // Calcula o índice inicial e final do lote
       $inicio = ($lote - 1) * TAMANHO_LOTE;
       $fim = min($inicio + TAMANHO_LOTE, $_SESSION['processamento']['total_alunos']);

       // Obtém os IDs dos alunos do lote atual
       $alunos_ids = array_slice($_SESSION['processamento']['alunos_ids'], $inicio, $fim - $inicio);
       $tipo_documento = $_SESSION['processamento']['tipo_documento'];
       $exibir_polo = $_SESSION['processamento']['exibir_polo'] ?? true;

       error_log("Lote {$lote}: Processando " . count($alunos_ids) . " alunos (índices {$inicio} a " . ($fim-1) . ")");

       // Libera memória antes de processar o lote
       gc_collect_cycles();

       // Processa o lote
       $arquivos_gerados = [];
       $erros = [];
       $processados = 0;

       foreach ($alunos_ids as $aluno_id) {
           try {
               // Busca dados completos do aluno
               $aluno = buscarDadosAlunoCompletoParaDocumento($db, $aluno_id);

               if (!$aluno) {
                   $erros[] = "Aluno ID {$aluno_id} não encontrado.";
                   continue;
               }

               // Gera o documento de acordo com o tipo
               if ($tipo_documento === 'declaracao') {
                   // Cria uma solicitação para o documento
                   $solicitacao_id = criarOuObterSolicitacaoDocumento($db, $aluno_id, $aluno['polo_id'] ?? 1, 2);

                   // Define se deve exibir o polo na declaração
                   $aluno['exibir_polo'] = $exibir_polo;

                   // Gera a declaração e armazena o caminho do arquivo
                   $arquivo = gerarDeclaracaoMatriculaPDF($aluno, $solicitacao_id, null, false, false, true);
                   if ($arquivo) {
                       $arquivos_gerados[] = $arquivo;
                       $processados++;
                   }
               } else if ($tipo_documento === 'historico') {
                   // Busca as notas do aluno
                   $sql = "SELECT nd.*, d.nome as disciplina_nome, d.carga_horaria as disciplina_carga_horaria
                           FROM notas_disciplinas nd
                           JOIN disciplinas d ON nd.disciplina_id = d.id
                           WHERE nd.matricula_id IN (SELECT id FROM matriculas WHERE aluno_id = ?)
                           ORDER BY d.nome ASC";
                   $notas = executarConsultaAll($db, $sql, [$aluno_id]);

                   // Cria uma solicitação para o documento
                   $solicitacao_id = criarOuObterSolicitacaoDocumento($db, $aluno_id, $aluno['polo_id'] ?? 1, 1);

                   // Gera o histórico e armazena o caminho do arquivo
                   $arquivo = gerarHistoricoAcademicoPDF($aluno, $notas, $solicitacao_id, null, false, false, true);
                   if ($arquivo) {
                       $arquivos_gerados[] = $arquivo;
                       $processados++;
                   }
               }
           } catch (Exception $e) {
               error_log("Erro ao processar aluno ID {$aluno_id}: " . $e->getMessage());
               $erros[] = "Erro ao processar aluno ID {$aluno_id}: " . $e->getMessage();
           }
       }

       // Calcula o tempo de processamento do lote
       $tempo_fim_lote = microtime(true);
       $tempo_lote = round($tempo_fim_lote - $tempo_inicio_lote, 2);

       // Atualiza os dados do processamento na sessão
       $_SESSION['processamento']['lote_atual'] = $lote;
       $_SESSION['processamento']['processados'] += $processados;
       $_SESSION['processamento']['arquivos_gerados'] = array_merge($_SESSION['processamento']['arquivos_gerados'], $arquivos_gerados);
       $_SESSION['processamento']['erros'] = array_merge($_SESSION['processamento']['erros'], $erros);

       // Registra o fim do processamento do lote
       error_log("Lote {$lote} concluído em {$tempo_lote} segundos. Processados: {$processados}, Total: {$_SESSION['processamento']['processados']}, Erros: " . count($erros));

       // Libera memória após processar o lote
       gc_collect_cycles();

       // Retorna o resultado
       echo json_encode([
           'lote' => $lote,
           'total_lotes' => $total_lotes,
           'processados' => $processados,
           'total_processados' => $_SESSION['processamento']['processados'],
           'erros' => count($erros),
           'tempo_lote' => $tempo_lote
       ]);
       exit;

   case 'criar_zip':
       // Inicia a criação do arquivo ZIP em segundo plano
       header('Content-Type: application/json');

       error_log("Recebida solicitação para criar ZIP. Parâmetros: " . print_r($_GET, true));

       if (!isset($_SESSION['processamento']) || empty($_SESSION['processamento']['arquivos_gerados'])) {
           error_log("ERRO: Nenhum processamento em andamento ou nenhum arquivo gerado.");
           echo json_encode(['error' => 'Nenhum documento gerado para criar ZIP.']);
           exit;
       }

       // Diretório temporário para armazenar o ZIP
       $temp_dir = 'temp';
       if (!file_exists($temp_dir)) {
           mkdir($temp_dir, 0777, true);
       }

       // Nome do arquivo ZIP
       $zip_id = uniqid();
       $zip_filename = 'documentos_' . $zip_id . '.zip';
       $zip_path = $temp_dir . '/' . $zip_filename;

       // Verifica se todos os arquivos existem e são acessíveis
       $arquivos_validos = [];
       foreach ($_SESSION['processamento']['arquivos_gerados'] as $arquivo) {
           if (file_exists($arquivo)) {
               $arquivos_validos[] = $arquivo;
           } else {
               error_log("AVISO: Arquivo não encontrado ao preparar ZIP: {$arquivo}");
           }
       }

       // Registra o número de arquivos válidos
       error_log("Total de arquivos para ZIP: " . count($_SESSION['processamento']['arquivos_gerados']) . ", Arquivos válidos: " . count($arquivos_validos));

       // Armazena informações do ZIP na sessão
       $_SESSION['zip'] = [
           'id' => $zip_id,
           'filename' => $zip_filename,
           'path' => $zip_path,
           'total_arquivos' => count($arquivos_validos),
           'arquivos_gerados' => $arquivos_validos, // Armazena apenas os arquivos válidos
           'arquivos_processados' => 0,
           'status' => 'processando',
           'iniciado' => time(),
           'erro' => null
       ];

       error_log("Iniciando criação do ZIP em segundo plano. ID: {$zip_id}, Total de arquivos: {$_SESSION['zip']['total_arquivos']}");

       // Inicia o processamento em segundo plano
       // Retorna imediatamente para que o cliente possa verificar o status
       echo json_encode([
           'status' => 'iniciado',
           'zip_id' => $zip_id,
           'total_arquivos' => $_SESSION['zip']['total_arquivos']
       ]);
       exit;

   case 'verificar_zip':
       // Verifica o status da criação do ZIP
       header('Content-Type: application/json');

       error_log("Recebida solicitação para verificar status do ZIP. Parâmetros: " . print_r($_GET, true));

       if (!isset($_SESSION['zip'])) {
           error_log("ERRO: Nenhuma criação de ZIP em andamento.");
           echo json_encode(['error' => 'Nenhuma criação de ZIP em andamento.']);
           exit;
       }

       // Se o status ainda é 'processando', processa mais um lote de arquivos
       if ($_SESSION['zip']['status'] === 'processando') {
           // Verifica se o arquivo ZIP já existe
           if (!file_exists($_SESSION['zip']['path'])) {
               // Cria o arquivo ZIP
               try {
                   $zip = new ZipArchive();
                   if ($zip->open($_SESSION['zip']['path'], ZipArchive::CREATE) === TRUE) {
                       // Processa um lote de arquivos (50 por vez)
                       $lote_tamanho = 50;
                       $inicio = $_SESSION['zip']['arquivos_processados'];
                       $fim = min($inicio + $lote_tamanho, $_SESSION['zip']['total_arquivos']);

                       error_log("Processando lote de arquivos para ZIP: {$inicio} a " . ($fim - 1) . " de {$_SESSION['zip']['total_arquivos']}");

                       // Adiciona cada arquivo do lote atual ao ZIP
                       for ($i = $inicio; $i < $fim; $i++) {
                           if (isset($_SESSION['zip']['arquivos_gerados'][$i])) {
                               $arquivo = $_SESSION['zip']['arquivos_gerados'][$i];
                               if (file_exists($arquivo)) {
                                   error_log("Adicionando arquivo ao ZIP: " . basename($arquivo));
                                   $result = $zip->addFile($arquivo, basename($arquivo));
                                   if (!$result) {
                                       error_log("ERRO ao adicionar arquivo ao ZIP: {$arquivo}");
                                   }
                               } else {
                                   error_log("ERRO: Arquivo não existe: {$arquivo}");
                               }
                           }
                       }

                       $zip->close();

                       // Verifica o tamanho do arquivo ZIP após adicionar este lote
                       if (file_exists($_SESSION['zip']['path'])) {
                           $zip_size = filesize($_SESSION['zip']['path']);

                           // Verifica quantos arquivos estão no ZIP
                           $zip_check = new ZipArchive();
                           if ($zip_check->open($_SESSION['zip']['path']) === TRUE) {
                               $num_files = $zip_check->numFiles;
                               error_log("Número de arquivos no ZIP após criar e adicionar primeiro lote: " . $num_files);
                               $zip_check->close();
                           }

                           error_log("Tamanho do arquivo ZIP após criar e adicionar primeiro lote: " . $zip_size . " bytes");
                       } else {
                           error_log("ERRO: Arquivo ZIP não existe após fechar: " . $_SESSION['zip']['path']);
                       }

                       // Atualiza o número de arquivos processados
                       $_SESSION['zip']['arquivos_processados'] = $fim;

                       // Verifica se todos os arquivos foram processados
                       if ($fim >= $_SESSION['zip']['total_arquivos']) {
                           $_SESSION['zip']['status'] = 'concluido';
                           error_log("Criação do ZIP concluída. ID: {$_SESSION['zip']['id']}");
                       }
                   } else {
                       $_SESSION['zip']['status'] = 'erro';
                       $_SESSION['zip']['erro'] = 'Não foi possível abrir o arquivo ZIP para escrita.';
                       error_log("ERRO: Não foi possível abrir o arquivo ZIP para escrita: {$_SESSION['zip']['path']}");
                   }
               } catch (Exception $e) {
                   $_SESSION['zip']['status'] = 'erro';
                   $_SESSION['zip']['erro'] = $e->getMessage();
                   error_log("Erro ao criar ZIP: " . $e->getMessage());
               }
           } else {
               // O arquivo já existe, continua adicionando arquivos
               try {
                   $zip = new ZipArchive();
                   // Abre o arquivo ZIP existente sem sobrescrevê-lo
                   if ($zip->open($_SESSION['zip']['path']) === TRUE) {
                       // Processa um lote de arquivos (50 por vez)
                       $lote_tamanho = 50;
                       $inicio = $_SESSION['zip']['arquivos_processados'];
                       $fim = min($inicio + $lote_tamanho, $_SESSION['zip']['total_arquivos']);

                       error_log("Continuando processamento de lote para ZIP: {$inicio} a " . ($fim - 1) . " de {$_SESSION['zip']['total_arquivos']}");

                       // Adiciona cada arquivo do lote atual ao ZIP
                       for ($i = $inicio; $i < $fim; $i++) {
                           if (isset($_SESSION['zip']['arquivos_gerados'][$i])) {
                               $arquivo = $_SESSION['zip']['arquivos_gerados'][$i];
                               if (file_exists($arquivo)) {
                                   error_log("Adicionando arquivo ao ZIP: " . basename($arquivo));
                                   $result = $zip->addFile($arquivo, basename($arquivo));
                                   if (!$result) {
                                       error_log("ERRO ao adicionar arquivo ao ZIP: {$arquivo}");
                                   }
                               } else {
                                   error_log("ERRO: Arquivo não existe: {$arquivo}");
                               }
                           }
                       }

                       $zip->close();

                       // Verifica o tamanho do arquivo ZIP após adicionar este lote
                       if (file_exists($_SESSION['zip']['path'])) {
                           $zip_size = filesize($_SESSION['zip']['path']);

                           // Verifica quantos arquivos estão no ZIP
                           $zip_check = new ZipArchive();
                           if ($zip_check->open($_SESSION['zip']['path']) === TRUE) {
                               $num_files = $zip_check->numFiles;
                               error_log("Número de arquivos no ZIP após adicionar lote: " . $num_files);
                               $zip_check->close();
                           }

                           error_log("Tamanho do arquivo ZIP após adicionar lote: " . $zip_size . " bytes");
                       } else {
                           error_log("ERRO: Arquivo ZIP não existe após fechar: " . $_SESSION['zip']['path']);
                       }

                       // Atualiza o número de arquivos processados
                       $_SESSION['zip']['arquivos_processados'] = $fim;

                       // Verifica se todos os arquivos foram processados
                       if ($fim >= $_SESSION['zip']['total_arquivos']) {
                           $_SESSION['zip']['status'] = 'concluido';
                           error_log("Criação do ZIP concluída. ID: {$_SESSION['zip']['id']}");
                       }
                   } else {
                       $_SESSION['zip']['status'] = 'erro';
                       $_SESSION['zip']['erro'] = 'Não foi possível abrir o arquivo ZIP existente.';
                       error_log("ERRO: Não foi possível abrir o arquivo ZIP existente: {$_SESSION['zip']['path']}");
                   }
               } catch (Exception $e) {
                   $_SESSION['zip']['status'] = 'erro';
                   $_SESSION['zip']['erro'] = $e->getMessage();
                   error_log("Erro ao continuar criação do ZIP: " . $e->getMessage());
               }
           }
       }

       // Calcula o percentual de conclusão
       $percent = 0;
       if ($_SESSION['zip']['total_arquivos'] > 0) {
           $percent = round(($_SESSION['zip']['arquivos_processados'] / $_SESSION['zip']['total_arquivos']) * 100);
       }

       // Retorna o status atual
       echo json_encode([
           'status' => $_SESSION['zip']['status'],
           'zip_id' => $_SESSION['zip']['id'],
           'percent' => $percent,
           'arquivos_processados' => $_SESSION['zip']['arquivos_processados'],
           'total_arquivos' => $_SESSION['zip']['total_arquivos'],
           'erro' => $_SESSION['zip']['erro']
       ]);
       exit;

   case 'baixar_zip':
       // Baixa o arquivo ZIP
       error_log("Recebida solicitação para baixar ZIP. Parâmetros: " . print_r($_GET, true));

       $zip_id = $_GET['zip_id'] ?? '';

       if (empty($zip_id) || !isset($_SESSION['zip']) || $_SESSION['zip']['id'] !== $zip_id) {
           error_log("ERRO: ID do ZIP inválido ou não encontrado: {$zip_id}");
           $_SESSION['mensagem'] = [
               'tipo' => 'erro',
               'texto' => 'Arquivo ZIP não encontrado.'
           ];
           header('Location: documentos.php?action=selecionar_aluno');
           exit;
       }

       $zip_path = $_SESSION['zip']['path'];
       $zip_filename = $_SESSION['zip']['filename'];

       if (!file_exists($zip_path)) {
           error_log("ERRO: Arquivo ZIP não encontrado: {$zip_path}");
           $_SESSION['mensagem'] = [
               'tipo' => 'erro',
               'texto' => 'Arquivo ZIP não encontrado.'
           ];
           header('Location: documentos.php?action=selecionar_aluno');
           exit;
       }

       // Limpa qualquer saída anterior
       if (ob_get_level()) {
           ob_end_clean();
       }

       // Prepara o download do arquivo ZIP
       header('Content-Type: application/zip');
       header('Content-Disposition: attachment; filename="' . $zip_filename . '"');
       header('Content-Length: ' . filesize($zip_path));
       header('Cache-Control: no-cache, no-store, must-revalidate');
       header('Pragma: no-cache');
       header('Expires: 0');

       // Lê e envia o arquivo
       readfile($zip_path);

       // Limpa os arquivos temporários
       if (isset($_SESSION['zip']['arquivos_gerados'])) {
           foreach ($_SESSION['zip']['arquivos_gerados'] as $arquivo) {
               if (file_exists($arquivo)) {
                   @unlink($arquivo);
               }
           }
       } else if (isset($_SESSION['processamento']['arquivos_gerados'])) {
           foreach ($_SESSION['processamento']['arquivos_gerados'] as $arquivo) {
               if (file_exists($arquivo)) {
                   @unlink($arquivo);
               }
           }
       }
       @unlink($zip_path);

       // Limpa os dados de processamento e ZIP
       unset($_SESSION['processamento']);
       unset($_SESSION['zip']);
       exit;

   case 'cancelar_processamento':
       // Cancela o processamento em andamento
       error_log("Recebida solicitação para cancelar processamento. Parâmetros: " . print_r($_GET, true));

       if (isset($_SESSION['processamento'])) {
           error_log("Cancelando processamento. Dados: " . print_r($_SESSION['processamento'], true));

           // Remove os arquivos gerados
           $arquivos_removidos = 0;
           foreach ($_SESSION['processamento']['arquivos_gerados'] as $arquivo) {
               if (file_exists($arquivo)) {
                   @unlink($arquivo);
                   $arquivos_removidos++;
               }
           }

           error_log("Arquivos removidos: {$arquivos_removidos} de " . count($_SESSION['processamento']['arquivos_gerados']));

           // Limpa os dados de processamento
           unset($_SESSION['processamento']);

           $_SESSION['mensagem'] = [
               'tipo' => 'aviso',
               'texto' => 'Processamento cancelado pelo usuário. ' . $arquivos_removidos . ' arquivos temporários foram removidos.'
           ];
       } else {
           error_log("Nenhum processamento encontrado para cancelar.");

           $_SESSION['mensagem'] = [
               'tipo' => 'aviso',
               'texto' => 'Nenhum processamento em andamento para cancelar.'
           ];
       }

       header('Location: documentos.php?action=selecionar_aluno');
       exit;

   case 'diagnostico':
       // Página de diagnóstico para administradores
       if (!usuarioTemPermissao('admin', 'visualizar')) {
           $_SESSION['mensagem'] = [
               'tipo' => 'erro',
               'texto' => 'Você não tem permissão para acessar esta página.'
           ];
           header('Location: documentos.php');
           exit;
       }

       $titulo_pagina = 'Diagnóstico de Documentos';
       $view = 'diagnostico';

       // Busca os últimos 20 documentos gerados
       $sql = "SELECT d.*, a.nome as aluno_nome, td.nome as tipo_documento_nome
               FROM documentos_emitidos d
               LEFT JOIN alunos a ON d.aluno_id = a.id
               LEFT JOIN tipos_documentos td ON d.tipo_documento_id = td.id
               ORDER BY d.data_emissao DESC
               LIMIT 20";
       $documentos = executarConsultaAll($db, $sql, []);

       // Verifica cada documento
       foreach ($documentos as &$doc) {
           $arquivo_path = 'uploads/documentos/' . $doc['arquivo'];
           $doc['arquivo_existe'] = file_exists($arquivo_path);
           $doc['arquivo_path'] = $arquivo_path;

           // Verifica em locais alternativos
           if (!$doc['arquivo_existe']) {
               $arquivo_temp = 'temp/' . basename($doc['arquivo']);
               $doc['arquivo_temp_existe'] = file_exists($arquivo_temp);
               $doc['arquivo_temp_path'] = $arquivo_temp;
           }
       }
       break;

   default:
       // Página inicial de documentos
       $titulo_pagina = 'Documentos Acadêmicos';
       $view = 'inicio';
       break;
}
/**
 * Função para truncar texto - mantida para compatibilidade, mas não usada mais para truncar
 * nomes de cursos ou polos, que agora usam MultiCell para textos longos
 */
function truncarTexto($texto, $comprimento_maximo = 30, $adicionar_reticencias = true) {
    // Retorna o texto original sem truncar
    return $texto;

    // Código original mantido como comentário para referência
    /*
    if (strlen($texto) <= $comprimento_maximo) {
        return $texto;
    }

    $texto_truncado = substr($texto, 0, $comprimento_maximo);

    return $adicionar_reticencias
        ? $texto_truncado . '...'
        : $texto_truncado;
    */
}

// Funções para geração de documentos em PDF
function gerarDeclaracaoMatriculaPDF($aluno, $solicitacao_id = null, $codigo_verificacao = null, $forcar_download = false, $visualizar = false, $retornar_caminho = false) {
   // Registra a emissão do documento
   global $db;

   // Início da medição de tempo
   $tempo_inicio = microtime(true);
   error_log("Iniciando geração de declaração para aluno ID: " . ($aluno['id'] ?? 'N/A'));

   // Gera um código de verificação único se não fornecido
   if ($codigo_verificacao === null) {
       $codigo_verificacao = mt_rand(100000, 999999);
   }

   // Cria o diretório para armazenar os documentos
   $diretorio = 'uploads/documentos';
   if (!file_exists($diretorio)) {
       mkdir($diretorio, 0777, true);
   }

   // Nome do arquivo - otimizado para evitar caracteres especiais
   $nome_arquivo = 'declaracao_matricula_' . sanitizarNomeArquivo($aluno['nome']) . '_' . date('Ymd_His') . '.pdf';
   $caminho_arquivo = $diretorio . '/' . $nome_arquivo;

   // Verifica se já existe um documento recente para este aluno (menos de 1 hora)
   if (!empty($aluno['id'])) {
       try {
           $uma_hora_atras = date('Y-m-d H:i:s', strtotime('-1 hour'));
           $sql = "SELECT arquivo FROM documentos_emitidos
                   WHERE aluno_id = ? AND tipo_documento_id = 2
                   AND data_emissao >= ?
                   ORDER BY id DESC LIMIT 1";
           $doc_recente = executarConsulta($db, $sql, [$aluno['id'], $uma_hora_atras]);

           if ($doc_recente && !empty($doc_recente['arquivo'])) {
               $arquivo_existente = $diretorio . '/' . $doc_recente['arquivo'];
               if (file_exists($arquivo_existente)) {
                   error_log("Documento recente encontrado para aluno ID " . $aluno['id'] . ": " . $arquivo_existente);
                   return $arquivo_existente;
               }
           }
       } catch (Exception $e) {
           error_log("Erro ao verificar documentos recentes: " . $e->getMessage());
       }
   }

   try {
       // Cria uma instância de TCPDF
       $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

       // Configurações do documento
       $pdf->SetCreator('Faciencia');
       $pdf->SetAuthor('Faciencia');
       $pdf->SetTitle('Declaração de Matrícula');
       $pdf->SetSubject('Declaração de Matrícula');
       $pdf->SetKeywords('Declaração, Matrícula, Faciencia');

       // Remove cabeçalho e rodapé padrão
       $pdf->setPrintHeader(false);
       $pdf->setPrintFooter(false);

       // Define margens
       $pdf->SetMargins(15, 15, 15);
       $pdf->SetAutoPageBreak(true, 20);

       // Adiciona uma página
       $pdf->AddPage();

       // Formata os dados
      $nome_aluno = $aluno['nome'] ?? '';
      $cpf_aluno = formatarCpf($aluno['cpf'] ?? '');
      $curso_nome = $aluno['curso_nome'] ?? '';

      // Verifica se deve exibir o polo e qual nome usar (razao_social ou nome)
      $exibir_polo = isset($aluno['exibir_polo']) ? $aluno['exibir_polo'] : true;
      if ($exibir_polo) {
          // Usa razao_social se disponível, senão usa polo_nome
          $polo_nome = !empty($aluno['polo_razao_social']) ? $aluno['polo_razao_social'] : ($aluno['polo_nome'] ?? 'Não informado');
      } else {
          $polo_nome = ''; // Não exibe o polo
      }

      // Prioriza a carga horária da turma, se não existir usa a do curso
      $curso_carga_horaria = !empty($aluno['turma_carga_horaria']) ? $aluno['turma_carga_horaria'] : ($aluno['curso_carga_horaria'] ?? 0);

      // Busca a matrícula do aluno
      $matricula = '';
      if (!empty($aluno['id'])) {
          try {
              // Verifica se a coluna numero_matricula existe na tabela
              $coluna_existe = false;
              try {
                  $colunas = $db->fetchAll("SHOW COLUMNS FROM matriculas LIKE 'numero_matricula'");
                  $coluna_existe = !empty($colunas);
              } catch (Exception $e) {
                  error_log("Erro ao verificar coluna numero_matricula: " . $e->getMessage());
              }

              // Se a coluna não existir, tenta criá-la
              if (!$coluna_existe) {
                  try {
                      $db->query("ALTER TABLE matriculas ADD COLUMN numero_matricula VARCHAR(50) NULL AFTER id");
                      error_log("Coluna numero_matricula adicionada à tabela matriculas");

                      // Atualiza os registros existentes com um número de matrícula baseado no ID
                      $db->query("UPDATE matriculas SET numero_matricula = CONCAT('MAT', LPAD(id, 6, '0')) WHERE numero_matricula IS NULL");
                      error_log("Registros atualizados com números de matrícula");

                      $coluna_existe = true;
                  } catch (Exception $e) {
                      error_log("Erro ao adicionar coluna numero_matricula: " . $e->getMessage());
                  }
              }

              if ($coluna_existe) {
                  // Busca a matrícula usando a coluna numero_matricula
                  $sql_matricula = "SELECT id, numero_matricula FROM matriculas WHERE aluno_id = ? AND status = 'ativo' ORDER BY id DESC LIMIT 1";
                  $result_matricula = $db->fetchOne($sql_matricula, [$aluno['id']]);

                  if ($result_matricula) {
                      if (!empty($result_matricula['numero_matricula'])) {
                          $matricula = $result_matricula['numero_matricula'];
                      } else {
                          // Se o número de matrícula estiver vazio, usa o ID formatado
                          $matricula = 'MAT' . str_pad($result_matricula['id'], 6, '0', STR_PAD_LEFT);

                          // Atualiza o registro com o número de matrícula gerado
                          try {
                              $db->update('matriculas',
                                  ['numero_matricula' => $matricula],
                                  'id = ?',
                                  [$result_matricula['id']]
                              );
                          } catch (Exception $e) {
                              error_log("Erro ao atualizar número de matrícula: " . $e->getMessage());
                          }
                      }
                  } else {
                      // Se não encontrar matrícula ativa, busca qualquer matrícula
                      $sql_matricula = "SELECT id, numero_matricula FROM matriculas WHERE aluno_id = ? ORDER BY id DESC LIMIT 1";
                      $result_matricula = $db->fetchOne($sql_matricula, [$aluno['id']]);

                      if ($result_matricula) {
                          if (!empty($result_matricula['numero_matricula'])) {
                              $matricula = $result_matricula['numero_matricula'];
                          } else {
                              // Se o número de matrícula estiver vazio, usa o ID formatado
                              $matricula = 'MAT' . str_pad($result_matricula['id'], 6, '0', STR_PAD_LEFT);

                              // Atualiza o registro com o número de matrícula gerado
                              try {
                                  $db->update('matriculas',
                                      ['numero_matricula' => $matricula],
                                      'id = ?',
                                      [$result_matricula['id']]
                                  );
                              } catch (Exception $e) {
                                  error_log("Erro ao atualizar número de matrícula: " . $e->getMessage());
                              }
                          }
                      }
                  }
              } else {
                  // Se a coluna não existir e não puder ser criada, usa o ID da matrícula como número
                  $sql_matricula = "SELECT id FROM matriculas WHERE aluno_id = ? ORDER BY id DESC LIMIT 1";
                  $result_matricula = $db->fetchOne($sql_matricula, [$aluno['id']]);

                  if ($result_matricula && !empty($result_matricula['id'])) {
                      $matricula = 'MAT' . str_pad($result_matricula['id'], 6, '0', STR_PAD_LEFT);
                  }
              }
          } catch (Exception $e) {
              error_log("Erro ao buscar matrícula do aluno: " . $e->getMessage());
          }
      }

       $data_atual = date('d/m/Y');

       $secretario_nome = 'Niceia de Oliveira Rodrigues da Silva';
       $secretario_cpf = '047.860.589-63';
       $diretor_nome = 'Faculdade Faciencia';

       // Logo e título - usando URL direta
       $logo_url = 'https://faciencia.edu.br/logo.png?v=1747241581740';
       $pdf->Image($logo_url, 15, 15, 40, '', 'PNG');

       // Título
       $pdf->SetFont('helvetica', 'B', 10);
       $pdf->SetTextColor(128, 0, 128); // Roxo
       $pdf->SetXY(60, 15);
       $pdf->Cell(135, 10, 'CREDENCIADA PELO MEC - PORTARIA N° 147 - 08/03/2022', 0, 1, 'L');

       $pdf->SetFont('helvetica', '', 11);
       $pdf->SetTextColor(100, 100, 100);
       $pdf->SetXY(60, 25);
       $pdf->Cell(135, 6, 'Documento oficial para fins acadêmicos', 0, 1, 'L');

       // Adiciona as informações de credenciamento
       $pdf->SetXY(60, 31);
       $pdf->Cell(135, 6, 'DECLARAÇÃO DE MATRÍCULA', 0, 1, 'L');

       // Adiciona o departamento
       $pdf->SetXY(60, 37);
       $pdf->Cell(135, 6, 'DEPARTAMENTO DE PÓS-GRADUAÇÃO', 0, 1, 'L');

       // Linha divisória
       $pdf->SetDrawColor(128, 0, 128); // Roxo
       $pdf->Line(15, 45, 195, 45);

       // Calcula a altura necessária para o box
       $altura_box = 30; // Altura base

       // Adiciona espaço para cada campo
       $num_campos = 5; // Nome, CPF, Curso, Carga Horária, Polo (ou espaço equivalente)
       if (!empty($matricula)) {
           $num_campos++; // Adiciona matrícula
       }

       // Calcula altura total (8 pixels por campo)
       $altura_box = 10 + ($num_campos * 8);

       // Box com informações do aluno (fundo cinza claro)
       $pdf->SetFillColor(245, 245, 245);
       $pdf->Rect(15, 55, 180, $altura_box, 'F');

       // Adiciona uma borda roxa à esquerda do box
       $pdf->SetDrawColor(128, 0, 128);
       $pdf->SetLineWidth(1.5);
       $pdf->Line(15, 55, 15, 55 + $altura_box);
       $pdf->SetLineWidth(0.2); // Reset da largura da linha

       // Posição Y inicial para as informações
       $y_pos = 58;
       $espaco_entre_linhas = 7; // Espaçamento entre as linhas

       // Informações do aluno - abordagem simplificada
       $pdf->SetFont('helvetica', 'B', 11);
       $pdf->SetTextColor(0, 0, 0);

       // Nome
       $pdf->SetXY(20, $y_pos);
       $pdf->Cell(25, 6, 'Nome:', 0, 0);
       $pdf->SetFont('helvetica', '', 11);
       $pdf->SetTextColor(128, 0, 128); // Roxo para o valor
       $pdf->Cell(150, 6, $nome_aluno, 0, 1);

       // CPF
       $y_pos += $espaco_entre_linhas;
       $pdf->SetXY(20, $y_pos);
       $pdf->SetFont('helvetica', 'B', 11);
       $pdf->SetTextColor(0, 0, 0);
       $pdf->Cell(25, 6, 'CPF:', 0, 0);
       $pdf->SetFont('helvetica', '', 11);
       $pdf->SetTextColor(128, 0, 128); // Roxo para o valor
       $pdf->Cell(150, 6, $cpf_aluno, 0, 1);

       // Matrícula (se disponível)
       if (!empty($matricula)) {
           $y_pos += $espaco_entre_linhas;
           $pdf->SetXY(20, $y_pos);
           $pdf->SetFont('helvetica', 'B', 11);
           $pdf->SetTextColor(0, 0, 0);
           $pdf->Cell(25, 6, 'Matrícula:', 0, 0);
           $pdf->SetFont('helvetica', '', 11);
           $pdf->SetTextColor(128, 0, 128); // Roxo para o valor
           $pdf->Cell(150, 6, $matricula, 0, 1);
       }

       // Curso
       $y_pos += $espaco_entre_linhas;
       $pdf->SetXY(20, $y_pos);
       $pdf->SetFont('helvetica', 'B', 11);
       $pdf->SetTextColor(0, 0, 0);
       $pdf->Cell(25, 6, 'Curso:', 0, 0);
       $pdf->SetFont('helvetica', '', 11);
       $pdf->SetTextColor(128, 0, 128); // Roxo para o valor
      // Calcula a largura restante para o nome do curso
// Largura total da área (180mm) - Largura do rótulo "Curso:" (25mm) - um pequeno espaçamento (5mm)
$largura_restante = 180 - 25 - 5;

// Usa MultiCell para o nome do curso para permitir quebra de linha automática
// O '6' é a altura mínima da linha, o TCPDF ajustará se o texto precisar de mais linhas
$pdf->MultiCell($largura_restante, 6, $curso_nome, 0, 'L', false, 1, '', '', true, 0, false, true, 0, 'T', false);

// IMPORTANTE: Após usar MultiCell, a posição Y do PDF (vertical) se move para baixo
// para onde o MultiCell terminou. Você precisa atualizar sua variável $y_pos
// para que os próximos campos (Carga Horária, Polo) sejam posicionados corretamente.
$y_pos = $pdf->GetY();

       // Carga Horária
       $y_pos += $espaco_entre_linhas;
       $pdf->SetXY(20, $y_pos);
       $pdf->SetFont('helvetica', 'B', 11);
       $pdf->SetTextColor(0, 0, 0);
       $pdf->Cell(25, 6, 'Carga :', 0, 0);
       $pdf->SetFont('helvetica', '', 11);
       $pdf->SetTextColor(128, 0, 128); // Roxo para o valor
       $pdf->Cell(150, 6, $curso_carga_horaria . ' horas', 0, 1);


   if ($exibir_polo && (!empty($polo_nome) || !empty($aluno['polo_mec']))) {
    $y_pos += $espaco_entre_linhas;
    $pdf->SetXY(20, $y_pos);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(50, 6, 'Polo de Apoio Presencial:', 0, 0); // Diminuído de 60 para 48
    $pdf->SetFont('helvetica', '', 11);
    $pdf->SetTextColor(128, 0, 128); // Roxo para o valor

    // Usa o campo mec se disponível, senão usa o nome do polo
    $nome_polo_exibir = !empty($aluno['polo_mec']) ? $aluno['polo_mec'] : $polo_nome;

    // Verifica se há parênteses no nome do polo e quebra a linha após o primeiro "("
    if (strpos($nome_polo_exibir, '(') !== false) {
        // Encontra a posição do primeiro parênteses
        $pos_parenteses = strpos($nome_polo_exibir, '(');

        // Separa o texto antes e depois do parênteses
        $parte_antes = substr($nome_polo_exibir, 0, $pos_parenteses);
        $parte_depois = substr($nome_polo_exibir, $pos_parenteses);

        // Exibe a primeira parte na mesma linha
        $pdf->Cell(127, 6, trim($parte_antes), 0, 1, 'L');

        // Move para a próxima linha e exibe a segunda parte
        $y_pos += 6; // Altura da linha
        $pdf->SetXY(70, $y_pos); // Alinha com o início do texto do polo (após o label)
        $pdf->Cell(127, 6, trim($parte_depois), 0, 1, 'L');
    } else {
        // Se não há parênteses, exibe normalmente
        $pdf->MultiCell(127, 6, $nome_polo_exibir, 0, 'L', false, 1);
    }
} else {
    // Adiciona um espaçador para manter o layout consistente quando o polo não for exibido
    $y_pos += $espaco_entre_linhas;
    $pdf->SetXY(20, $y_pos);
    $pdf->Cell(175, 6, '', 0, 1); // Linha vazia para manter o espaçamento
}
       // Texto da declaração - posiciona 10mm abaixo do final do box de informações
       $y_texto = $pdf->GetY() + 10; // Usa a posição atual (após o box) + 10mm de espaço

       $pdf->SetFont('helvetica', '', 11);
       $pdf->SetTextColor(0, 0, 0);
       $pdf->SetXY(15, $y_texto);

       // Texto da declaração com o número da matrícula incluído
      $y_texto_calculado = $pdf->GetY() + 10; // Ajuste este cálculo se a altura do box do aluno mudou
        $pdf->SetFont('helvetica', '', 11);     // Define a fonte para o texto principal
        $pdf->SetTextColor(0, 0, 0);           // Define a cor para o texto principal
        $pdf->SetXY(15, $y_texto_calculado);   // Define a posição X e Y para o texto principal

        // Texto da declaração com o número da matrícula incluído
      if (!empty($matricula)) {
    $texto = "Declaramos, para os devidos fins, que o(a) estudante acima identificado(a), portador(a) da matrícula nº {$matricula}, está em conformidade com as normas acadêmicas vigentes e com a legislação brasileira.";
} else {
    $texto = "Declaramos, para os devidos fins, que o(a) estudante acima identificado(a) está regularmente matriculado(a) em nossa instituição no curso mencionado, em conformidade com as normas acadêmicas vigentes e com a legislação educacional brasileira.";
}

        // ***** ADICIONE OU RESTAURE ESTA LINHA AQUI *****
        // Esta linha é crucial para exibir o texto da declaração principal
 $pdf->writeHTMLCell(180, 0, 15, $y_texto_calculado, '<p style="text-align: left;">' . $texto . '</p>', 0, 1, false, true, 'L', false);
        // ***************************************************

        // Agora, o novo bloco de código que você adicionou para "E, por ser verdade..."
        // (que parece estar correto como você o colou na sua pergunta anterior)

        // Adiciona um espaço vertical após o texto principal da declaração.
        $pdf->Ln(10);

        // Configura a fonte e a cor para o novo texto (ajuste se necessário)
        $pdf->SetFont('helvetica', '', 11);
        $pdf->SetTextColor(0, 0, 0);

        // Adiciona a linha: "E, por ser verdade firmamos a presente."
        $pdf->SetX(15);
        $pdf->Cell(0, 6, 'E, por ser verdade firmamos a presente.', 0, 1, 'L');

        // Adiciona a linha da data: "Curitiba/PR, 02 de Abril de 2025."
        $pdf->Ln(5);

        $diaAtual = date('d');
        $mesNumero = date('n'); // Número do mês (1-12)
        $anoAtual = date('Y');

        // Array para converter o número do mês para o nome em português
        $mesesEmPortugues = [
            1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
            5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
            9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
        ];
        $mesAtualExtenso = $mesesEmPortugues[$mesNumero];

        // Define a cidade/UF. Como você está em Curitiba/PR:
        $localidade = "Curitiba/PR";
        $dataParaExibir = "{$localidade}, {$diaAtual} de {$mesAtualExtenso} de {$anoAtual}.";

        // Imprime a data formatada
        // A linha original era: $pdf->Cell(0, 6, 'Curitiba/PR, 02 de Abril de 2025.', 0, 1, 'L');
        // Substitua pela linha abaixo:
        $pdf->Cell(0, 6, $dataParaExibir, 0, 1, 'L');

        // A linha abaixo, que adiciona espaço antes das assinaturas, deve permanecer.
        $pdf->Ln(25);

       // Assinaturas - posições ajustadas para evitar sobreposição com o texto
       // Obtém a posição Y atual após o espaço adicionado
       $assinatura_y = $pdf->GetY();

       // Carrega imagens de assinatura se disponíveis
       if (file_exists('assinatura_secretaria.png')) {
           $pdf->Image('assinatura_secretaria.png', 40, $assinatura_y, 40, '', 'PNG');
       } else {
           // Simular uma assinatura
           $pdf->SetXY(30, $assinatura_y);
           $pdf->SetFont('helvetica', 'I', 12);
           $pdf->Cell(60, 10, '_________________', 0, 0, 'C'); // Alterado para 0 para não quebrar linha
       }

       if (file_exists('assinatura_direcao.png')) {
          $pdf->Image('assinatura_direcao.png', 140, $assinatura_y - 12, 30, '', 'PNG');
       } else {
           // Simular uma assinatura
           $pdf->SetXY(140, $assinatura_y - 15); // sobe 5 unidades

           $pdf->SetFont('helvetica', 'I', 12);
           $pdf->Cell(80, 10, '_________________', 0, 1, 'C');
       }

       // Calcula a posição Y para as linhas de assinatura (15 pontos abaixo da posição da assinatura)
       $linha_y = $assinatura_y + 15;

       // Linhas para assinatura
       $pdf->SetDrawColor(128, 0, 128); // Roxo
       $pdf->Line(30, $linha_y, 90, $linha_y);
       $pdf->Line(120, $linha_y, 180, $linha_y);

       // Nomes abaixo das assinaturas
       $pdf->SetXY(30, $linha_y + 1);
       $pdf->SetFont('helvetica', 'B', 10);
       $pdf->SetTextColor(128, 0, 128); // Roxo
       $pdf->Cell(60, 6, 'Secretária Acadêmica', 0, 0, 'C');

       $pdf->SetXY(120, $linha_y + 1);
       $pdf->SetFont('helvetica', 'B', 10);
       $pdf->SetTextColor(128, 0, 128); // Roxo
       $pdf->Cell(60, 6, 'Direção Geral', 0, 1, 'C');

       $pdf->SetXY(30, $linha_y + 7);
       $pdf->SetFont('helvetica', '', 10);
       $pdf->SetTextColor(0, 0, 0);
       $pdf->Cell(60, 6, 'FaCiencia', 0, 0, 'C');

       $pdf->SetXY(120, $linha_y + 7);
       $pdf->SetFont('helvetica', '', 10);
       $pdf->SetTextColor(0, 0, 0);
       $pdf->Cell(60, 6, 'FaCiencia', 0, 1, 'C');

       // Box para código de verificação - forçando a ficar na mesma página
       // Calcula a posição ideal para o código de verificação
       $verificacao_y = $pdf->GetY() + 5; // Reduz o espaço entre elementos

       // Calcula o espaço necessário para o código de verificação e rodapé
       $espaco_necessario = 60; // 20 para o box de verificação + 40 para o rodapé

       // Se estiver muito próximo do fim da página, ajusta para caber tudo
       if ($pdf->GetPageHeight() - $verificacao_y < $espaco_necessario) {
           // Força o código de verificação a ficar na página atual
           // Reduzindo o espaço entre elementos e ajustando posições
           $verificacao_y = $pdf->GetPageHeight() - $espaco_necessario;

           // Ajusta as assinaturas para uma posição mais compacta
           $pdf->SetXY(30, 130);
           $pdf->SetFont('helvetica', 'I', 12);
           $pdf->Cell(60, 10, '_________________', 0, 0, 'C');

           $pdf->SetXY(120, 130);
           $pdf->Cell(60, 10, '_________________', 0, 1, 'C');

           // Linhas para assinatura
           $pdf->SetDrawColor(128, 0, 128); // Roxo
           $pdf->Line(30, 140, 90, 140);
           $pdf->Line(120, 140, 180, 140);

           // Nomes abaixo das assinaturas - mais compactos
           $pdf->SetXY(30, 141);
           $pdf->SetFont('helvetica', 'B', 10);
           $pdf->SetTextColor(128, 0, 128); // Roxo
           $pdf->Cell(60, 5, 'Secretária Acadêmica', 0, 0, 'C');

           $pdf->SetXY(120, 141);
           $pdf->Cell(60, 5, 'Direção Geral', 0, 1, 'C');

           $pdf->SetXY(30, 146);
           $pdf->SetFont('helvetica', '', 10);
           $pdf->SetTextColor(0, 0, 0);
           $pdf->Cell(60, 5, 'FaCiencia', 0, 0, 'C');

           $pdf->SetXY(120, 146);
           $pdf->Cell(60, 5, 'FaCiencia', 0, 1, 'C');
       }


$pdf->SetXY(25, $verificacao_y);
$pdf->SetFillColor(245, 245, 245);
// Box ainda menor para garantir espaço para o rodapé
$pdf->Rect(25, $verificacao_y, 160, 35, 'F');
// Borda lateral roxa
$pdf->SetDrawColor(128, 0, 128);
$pdf->SetLineWidth(1.5);
$pdf->Line(25, $verificacao_y, 25, $verificacao_y + 35);
$pdf->SetLineWidth(0.2);
// Texto do código de verificação - centralizado
$pdf->SetXY(25, $verificacao_y + 2);
$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(160, 4, 'Código de verificação:', 0, 1, 'C');
$pdf->SetXY(25, $verificacao_y + 6);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetTextColor(128, 0, 128); // Roxo
$pdf->Cell(160, 4, $codigo_verificacao, 0, 1, 'C');
$pdf->SetXY(25, $verificacao_y + 11);
$pdf->SetFont('helvetica', '', 8);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(160, 4, 'Para verificar a autenticidade deste documento, acesse www.faciencia.edu.br/verificar', 0, 1, 'C');
// Adiciona QR code com o código de verificação - centralizado e menor
$url_verificacao = 'https://faciencia.edu.br/verificar?codigo=' . $codigo_verificacao;
$style = array(
    'border' => false,
    'padding' => 0,
    'fgcolor' => array(128, 0, 128), // Roxo
    'bgcolor' => array(255, 255, 255) // Branco
);
// Centraliza o QR code menor ainda
$qr_width = 18; // Reduzido de 20 para 18
$box_width = 160;
$qr_x = 25 + ($box_width - $qr_width) / 2;
$pdf->write2DBarcode($url_verificacao, 'QRCODE,M', $qr_x, $verificacao_y + 16, $qr_width, $qr_width, $style);

// Rodapé - posiciona imediatamente após o box menor com espaço mínimo
$altura_pagina = $pdf->GetPageHeight();
$margem_inferior = 10; // Reduzida para dar mais espaço

// Calcula posição do rodapé com espaço mínimo
$rodape_y = $verificacao_y + 38; // 35 do box + apenas 3 de espaço

// Força o rodapé a ficar na mesma página ajustando para cima se necessário
$espaco_necessario = 8; // Espaço mínimo para as 2 linhas (4+4)
$limite_pagina = $altura_pagina - $margem_inferior;

if (($rodape_y + $espaco_necessario) > $limite_pagina) {
    // Ajusta o rodapé para caber, mesmo que fique muito próximo do QR
    $rodape_y = $limite_pagina - $espaco_necessario;
}

// Adiciona informações institucionais no rodapé com espaçamento compacto
$pdf->SetFont('helvetica', '', 8);
$pdf->SetTextColor(0, 0, 0);

// Primeira linha - CNPJ, telefone e email
$pdf->SetXY(15, $rodape_y);
$pdf->Cell(180, 4, 'CNPJ: 09.038.742/0001-80 • Tel: (41) 9 9256-2500 • Email: secretaria@faciencia.edu.br', 0, 0, 'C');

// Segunda linha - Endereço (imediatamente após a primeira)
$pdf->SetXY(15, $rodape_y + 4);
$pdf->Cell(180, 4, 'Rua Visconde de Nacar, 1510 – 10º Andar – Conj. 1003 – Centro – Curitiba/PR', 0, 0, 'C');

// Marca d'água (opcional)
$pdf->SetAlpha(0.05);
$pdf->SetFont('helvetica', 'B', 70);
$pdf->SetTextColor(128, 0, 128);
$pdf->StartTransform();
$pdf->Rotate(45, 105, 150);
$pdf->Text(40, 150, 'FaCiencia');
$pdf->StopTransform();
$pdf->SetAlpha(1);

       // Salva o PDF usando uma abordagem alternativa
       $pdf_content = $pdf->Output('', 'S'); // 'S' retorna o PDF como string
       file_put_contents($caminho_arquivo, $pdf_content);

       // Verificação adicional
       if (!file_exists($caminho_arquivo)) {
           throw new Exception("Não foi possível salvar o arquivo PDF em $caminho_arquivo");
       }

       // Registra a emissão do documento no banco de dados, se não for uma regeneração
       if (!$forcar_download) {
           // Prepara os dados para inserção - Gera um número único para o documento
           $base_numero = "DM" . date('Ymd');
           $numero = $base_numero . str_pad($aluno['id'], 6, '0', STR_PAD_LEFT) . mt_rand(1000, 9999);

           // Verifica se o número já existe e gera um novo se necessário
           $numero_existe = true;
           $tentativas = 0;

           while ($numero_existe && $tentativas < 10) {
               $sql_check = "SELECT id FROM documentos_emitidos WHERE numero_documento = ?";
               $doc_existente = $db->fetchOne($sql_check, [$numero]);

               if (!$doc_existente) {
                   $numero_existe = false;
               } else {
                   // Gera um novo número com componente aleatório
                   $numero = $base_numero . str_pad($aluno['id'], 6, '0', STR_PAD_LEFT) . mt_rand(1000, 9999);
                   $tentativas++;
               }
           }

           error_log("Número de documento gerado: " . $numero);

           // Verifica se o polo_id está definido
           if (empty($aluno['polo_id'])) {
               error_log("ATENÇÃO: polo_id está vazio ou nulo. Buscando um polo válido...");

               // Tenta buscar um polo válido
               try {
                   $sql_polo = "SELECT id FROM polos WHERE status = 'ativo' LIMIT 1";
                   $polo = $db->fetchOne($sql_polo);

                   if ($polo && isset($polo['id'])) {
                       error_log("Polo encontrado: " . $polo['id']);
                       $polo_id = $polo['id'];
                   } else {
                       // Se não encontrar, usa um valor padrão
                       error_log("Nenhum polo encontrado. Usando valor padrão 1");
                       $polo_id = 1;
                   }
               } catch (Exception $e) {
                   error_log("Erro ao buscar polo: " . $e->getMessage());
                   $polo_id = 1; // Valor padrão em caso de erro
               }
           } else {
               $polo_id = $aluno['polo_id'];
           }

           // Verifica se o curso_id está definido
           $curso_id = !empty($aluno['curso_id']) ? $aluno['curso_id'] : 1;

           // Usa a solicitação_id passada ou cria uma nova
           if (empty($solicitacao_id)) {
               try {
                   $solicitacao_id = criarOuObterSolicitacaoDocumento($db, $aluno['id'], $polo_id, 2); // Tipo 2 para declaração de matrícula
                   error_log("Usando solicitação ID: " . $solicitacao_id);
               } catch (Exception $e) {
                   error_log("Erro ao criar solicitação: " . $e->getMessage());
                   throw new Exception("Erro ao criar solicitação de documento: " . $e->getMessage());
               }
           } else {
               error_log("Usando solicitação ID existente: " . $solicitacao_id);
           }

           // Monta os dados para inserção de acordo com a estrutura exata da tabela
           $dados_documento = [
               'tipo_documento_id' => 2, // ID 2 é para declaração de matrícula
               'aluno_id' => $aluno['id'],
               'matricula_id' => 1, // Valor padrão para matricula_id que é obrigatório
               'curso_id' => $curso_id, // Usa o curso_id do aluno ou valor padrão
               'polo_id' => $polo_id, // Usa o polo_id verificado
               'data_emissao' => date('Y-m-d'),
               'data_validade' => date('Y-m-d', strtotime('+90 days')),
               'codigo_verificacao' => intval($codigo_verificacao), // Convertido para inteiro conforme estrutura da tabela
               'arquivo' => $nome_arquivo,
               'numero_documento' => $numero, // Usando o campo correto numero_documento
               'status' => 'ativo',
               'data_solicitacao' => date('Y-m-d'),
               'solicitacao_id' => $solicitacao_id // Usa a solicitação criada ou encontrada
           ];

           // Log para garantir que o tipo de documento está correto
           error_log("Emitindo DECLARAÇÃO DE MATRÍCULA (tipo_documento_id=2) para o aluno ID: " . $aluno['id'] . " - " . ($aluno['nome'] ?? 'Nome não disponível'));

           // Tenta inserir o documento
           try {
               // Log dos dados que serão inseridos para diagnóstico
               error_log("Tentando inserir documento com os seguintes dados: " . json_encode($dados_documento));

               $documento_id = $db->insert('documentos_emitidos', $dados_documento);
               error_log("Documento inserido com ID: " . $documento_id);

               if (!$documento_id) {
                   error_log("Erro ao inserir registro na tabela documentos_emitidos: " . ($db->error ?? 'Erro desconhecido'));
                   throw new Exception("Erro ao registrar documento no banco de dados");
               }

               // Verifica se a coluna documento_id existe na tabela solicitacoes_documentos
               try {
                   $colunas = $db->fetchAll("SHOW COLUMNS FROM solicitacoes_documentos LIKE 'documento_id'");

                   // Se a coluna não existir, adiciona
                   if (empty($colunas)) {
                       error_log("Adicionando coluna documento_id à tabela solicitacoes_documentos");
                       $db->query("ALTER TABLE solicitacoes_documentos ADD COLUMN documento_id INT(10) UNSIGNED NULL DEFAULT NULL");
                   }

                   // Atualiza a solicitação com o ID do documento gerado
                   error_log("Atualizando solicitação ID {$solicitacao_id} com documento_id {$documento_id}");
                   $db->update('solicitacoes_documentos', [
                       'documento_id' => $documento_id,
                       'status' => 'pronto',
                       'updated_at' => date('Y-m-d H:i:s')
                   ], 'id = ?', [$solicitacao_id]);
               } catch (Exception $e) {
                   error_log("Erro ao atualizar solicitação com documento_id: " . $e->getMessage());
                   // Não interrompe o fluxo se falhar aqui
               }
           } catch (Exception $e) {
               error_log("Erro ao inserir documento: " . $e->getMessage());
               error_log("Rastreamento: " . $e->getTraceAsString());
               throw new Exception("Erro ao registrar documento no banco de dados: " . $e->getMessage());
           }
       }

       // Se for para retornar o caminho, retorna o caminho do arquivo
       if ($retornar_caminho) {
           return $caminho_arquivo;
       }

       // Decide se envia o arquivo para download ou visualização
       if ($visualizar) {
           // Exibe o PDF no navegador
           header('Content-Type: application/pdf');
           header('Content-Disposition: inline; filename="' . basename($caminho_arquivo) . '"');
           header('Content-Length: ' . filesize($caminho_arquivo));
           readfile($caminho_arquivo);
       } else {
           // Envia o PDF para download
           header('Content-Type: application/pdf');
           header('Content-Disposition: attachment; filename="' . basename($caminho_arquivo) . '"');
           header('Content-Length: ' . filesize($caminho_arquivo));
           readfile($caminho_arquivo);
       }

       // Registra o tempo de execução
       $tempo_fim = microtime(true);
       $tempo_execucao = round($tempo_fim - $tempo_inicio, 2);
       error_log("Declaração gerada em {$tempo_execucao} segundos para aluno ID: " . ($aluno['id'] ?? 'N/A'));

       return null;

   } catch (Exception $e) {
       // Registra o erro
       error_log('Erro ao gerar declaração de matrícula em PDF: ' . $e->getMessage());
       error_log('Rastreamento: ' . $e->getTraceAsString());

       // Exibe mensagem de erro
       $_SESSION['mensagem'] = [
           'tipo' => 'erro',
           'texto' => 'Erro ao gerar a declaração de matrícula em PDF: ' . $e->getMessage()
       ];
       header('Location: documentos.php');
   }

   exit;
}

/**
 * Função para gerar o conteúdo HTML do histórico acadêmico
 *
 * @param array $aluno Dados do aluno
 * @param array $notas Notas do aluno
 * @param string $codigo_verificacao Código de verificação do documento
 * @return string Conteúdo HTML do histórico acadêmico
 */
function gerarConteudoHistorico($aluno, $notas, $codigo_verificacao) {
    // Cor principal da FaCiência (roxo)
    $cor_principal = '#6a1b9a';

    // Formata a data por extenso
    $data_atual = date('d/m/Y');
    $partes_data = explode('/', $data_atual);
    $meses = [
        '01' => 'janeiro', '02' => 'fevereiro', '03' => 'março', '04' => 'abril',
        '05' => 'maio', '06' => 'junho', '07' => 'julho', '08' => 'agosto',
        '09' => 'setembro', '10' => 'outubro', '11' => 'novembro', '12' => 'dezembro'
    ];
    $mes_extenso = $meses[$partes_data[1]] ?? $partes_data[1];
    $data_extenso = $partes_data[0] . ' de ' . $mes_extenso . ' de ' . $partes_data[2];

    // Formata o CPF
    $cpf_formatado = '';
    if (!empty($aluno['cpf'])) {
        $cpf = preg_replace('/[^0-9]/', '', $aluno['cpf']);
        if (strlen($cpf) === 11) {
            $cpf_formatado = substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
        } else {
            $cpf_formatado = $aluno['cpf'];
        }
    }

    // Prepara a tabela de disciplinas
    $disciplinas_html = '';
    $total_carga_horaria = 0;
    $soma_notas = 0;
    $count_notas = 0;

    if (!empty($notas)) {
        foreach ($notas as $nota) {
            // Determina a situação da disciplina
            $situacao = 'Aprovado';
            $nota_valor = floatval($nota['nota']);
            $frequencia = floatval($nota['frequencia']);

            if ($nota_valor < 7.0) {
                $situacao = 'Reprovado';
            }

            if ($frequencia < 75.0) {
                $situacao = 'Reprovado por Frequência';
            }

            // Formata os valores para exibição
            $nota_formatada = number_format($nota_valor, 1, ',', '.');
            $frequencia_formatada = number_format($frequencia, 1, ',', '.');
            $carga_horaria = intval($nota['disciplina_carga_horaria']);

            // Acumula para médias
            $total_carga_horaria += $carga_horaria;
            $soma_notas += $nota_valor;
            $count_notas++;

            // Adiciona a linha da disciplina
            $disciplinas_html .= '<tr>
                <td style="padding: 6px; text-align: left; border: 1px solid #ddd; font-size: 9pt;">' . htmlspecialchars($nota['disciplina_nome']) . '</td>
                <td style="padding: 6px; text-align: center; border: 1px solid #ddd; font-size: 9pt;">' . $carga_horaria . '</td>
                <td style="padding: 6px; text-align: center; border: 1px solid #ddd; font-size: 9pt;">' . $nota_formatada . '</td>
                <td style="padding: 6px; text-align: center; border: 1px solid #ddd; font-size: 9pt;">' . $frequencia_formatada . '%</td>
                <td style="padding: 6px; text-align: center; border: 1px solid #ddd; font-size: 9pt;">' . $situacao . '</td>
            </tr>';
        }
    } else {
        $disciplinas_html = '<tr><td colspan="5" style="text-align: center; padding: 10px; border: 1px solid #ddd; font-size: 9pt;">Não há disciplinas cursadas até o momento.</td></tr>';
    }

    // Calcula a média geral
    $media_geral = $count_notas > 0 ? $soma_notas / $count_notas : 0;
    $media_geral_formatada = number_format($media_geral, 1, ',', '.');

    // Adiciona linha de total/média
    $disciplinas_html .= '<tr style="background-color: #f0f0f0; font-weight: bold;">
        <td style="padding: 6px; text-align: right; border: 1px solid #ddd; font-size: 9pt;">Total / Média Geral</td>
        <td style="padding: 6px; text-align: center; border: 1px solid #ddd; font-size: 9pt;">' . $total_carga_horaria . '</td>
        <td style="padding: 6px; text-align: center; border: 1px solid #ddd; font-size: 9pt;">' . $media_geral_formatada . '</td>
        <td style="padding: 6px; text-align: center; border: 1px solid #ddd; font-size: 9pt;"></td>
        <td style="padding: 6px; text-align: center; border: 1px solid #ddd; font-size: 9pt;"></td>
    </tr>';

    // Adiciona legenda
    $disciplinas_html .= '<tr>
        <td colspan="5" style="padding: 6px; text-align: left; border: 1px solid #ddd; font-size: 8pt;">
            <span style="display: inline-block; width: 12px; height: 12px; background-color: #c8e6c9; margin-right: 5px;"></span> Aprovado: nota ≥ 7,0
            &nbsp;&nbsp;&nbsp;
            <span style="display: inline-block; width: 12px; height: 12px; background-color: #ffcdd2; margin-right: 5px;"></span> Reprovado: nota < 7,0
        </td>
    </tr>';

    // Gera o HTML completo
    $html = '<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico Acadêmico - ' . htmlspecialchars($aluno['nome']) . '</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
            font-size: 10pt;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            position: relative;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .logo {
            max-width: 150px;
            margin-bottom: 10px;
        }
        .title {
            color: ' . $cor_principal . ';
            font-size: 18pt;
            font-weight: bold;
            margin: 10px 0;
        }
        .subtitle {
            font-size: 12pt;
            margin-bottom: 20px;
        }
        .student-info {
            background-color: #f9f9f9;
            border-left: 4px solid ' . $cor_principal . ';
            padding: 15px;
            margin-bottom: 20px;
        }
        .info-row {
            margin-bottom: 8px;
        }
        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 120px;
        }
        .info-value {
            color: ' . $cor_principal . ';
        }
        .section-title {
            color: ' . $cor_principal . ';
            font-size: 14pt;
            margin: 15px 0 10px 0;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        th {
            background-color: ' . $cor_principal . ';
            color: white;
            padding: 8px;
            text-align: center;
            border: 1px solid #ddd;
        }
        td {
            padding: 8px;
            border: 1px solid #ddd;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 9pt;
            color: #666;
            border-top: 1px solid #eee;
            padding-top: 15px;
            page-break-before: avoid;
        }
        .verification {
            text-align: center;
            margin-top: 30px;
            font-size: 9pt;
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 5px;
            page-break-before: avoid;
            page-break-inside: avoid;
        }
        .verification-code {
            font-weight: bold;
            color: ' . $cor_principal . ';
            font-size: 12pt;
        }
        .signature {
            margin-top: 50px;
            text-align: center;
            page-break-before: avoid;
            page-break-inside: avoid;
            position: relative;
            clear: both;
        }
        .signature-line {
            width: 200px;
            border-top: 1px solid #000;
            margin: 0 auto;
            padding-top: 5px;
        }
        .signature-name {
            font-weight: bold;
        }
        .signature-title {
            font-size: 9pt;
            color: #666;
        }

        /* Estilos para impressão */
        @media print {
            body {
                padding: 0;
                margin: 0;
            }
            .container {
                max-width: 100%;
            }
            .signature-section {
                margin-top: 50px;
                page-break-before: auto;
                page-break-inside: avoid;
                position: relative;
            }
            table { page-break-inside: avoid; }
            tr { page-break-inside: avoid; }
            td { page-break-inside: avoid; }
            .page-break { page-break-before: always; }
            /* Se a tabela tiver poucas linhas, força a assinatura a ficar mais abaixo */
            table.small-table + .signature-section {
                margin-top: 150px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="https://www.faciencia.edu.br/logo.png?v=1745601920310" alt="Logo Faciência" class="logo">
            <div class="title">HISTÓRICO ACADÊMICO</div>
            <div class="subtitle">Documento oficial para fins acadêmicos</div>
        </div>

        <div class="student-info">
            <div class="info-row">
                <span class="info-label">Nome:</span>
                <span class="info-value">' . htmlspecialchars($aluno['nome']) . '</span>
            </div>
            <div class="info-row">
                <span class="info-label">CPF:</span>
                <span class="info-value">' . $cpf_formatado . '</span>
            </div>
            <div class="info-row">
                <span class="info-label">Curso:</span>
                <span class="info-value">' . htmlspecialchars($aluno['curso_nome'] ?? 'Não informado') . '</span>
            </div>
            <div class="info-row">
                <span class="info-label">Polo:</span>
                <span class="info-value">' . htmlspecialchars($aluno['polo_nome'] ?? 'Não informado') . '</span>
            </div>
        </div>

        <div class="section-title">Disciplinas e Notas</div>

        <table class="' . (count($notas) <= 6 ? 'small-table' : '') . '">
            <thead>
                <tr>
                    <th style="width: 40%;">Disciplina</th>
                    <th style="width: 10%;">C.H.</th>
                    <th style="width: 10%;">Nota</th>
                    <th style="width: 15%;">Frequência</th>
                    <th style="width: 25%;">Situação</th>
                </tr>
            </thead>
            <tbody>
                ' . $disciplinas_html . '
            </tbody>
        </table>

        <div style="clear: both; margin-top: 40px; height: 1px;"></div>

        <!-- Espaçador dinâmico baseado no número de disciplinas -->
        ' . (count($notas) <= 3 ? '<div style="height: 150px;"></div>' :
             (count($notas) <= 6 ? '<div style="height: 100px;"></div>' :
             '<div style="height: 50px;"></div>')) . '

        <!-- Força quebra de página se necessário para evitar sobreposição -->
        ' . (count($notas) <= 5 ? '' : '<div class="page-break"></div>') . '

        <div class="signature-section">
            <div class="verification">
                <p>Para verificar a autenticidade deste documento, acesse www.faciencia.edu.br/verificar</p>
                <p>Código de verificação: <span class="verification-code">' . $codigo_verificacao . '</span></p>
            </div>

            <div style="clear: both; height: 60px;"></div>

            <div class="signature">
                <div class="signature-line"></div>
                <div class="signature-name">Niceia de Oliveira Rodrigues da Silva</div>
                <div class="signature-title">CPF: 047.860.569-63</div>
                <div class="signature-title">Secretária Acadêmica</div>
                <div class="signature-title">Faciencia</div>
            </div>

            <div class="footer">
                <p>' . htmlspecialchars($aluno['polo_nome'] ?? 'Curitiba/PR') . ', ' . $data_extenso . '</p>
            </div>
        </div>
    </div>
</body>
</html>';

    return $html;
}

function gerarHistoricoAcademicoPDF($aluno, $notas, $solicitacao_id = null, $codigo_verificacao = null, $forcar_download = false, $visualizar = false, $retornar_caminho = false) {
   // Registra a emissão do documento
   global $db;

   // Início da medição de tempo
   $tempo_inicio = microtime(true);
   error_log("Iniciando geração de histórico para aluno ID: " . ($aluno['id'] ?? 'N/A'));

   // Gera um código de verificação único se não fornecido
   if ($codigo_verificacao === null) {
       $codigo_verificacao = mt_rand(100000, 999999);
   }

   // Cria o diretório para armazenar os documentos
   $diretorio = 'uploads/documentos';
   if (!file_exists($diretorio)) {
       mkdir($diretorio, 0777, true);
   }

   // Nome do arquivo - otimizado para evitar caracteres especiais
   $nome_arquivo = 'historico_academico_' . sanitizarNomeArquivo($aluno['nome']) . '_' . date('Ymd_His') . '.pdf';
   $caminho_arquivo = $diretorio . '/' . $nome_arquivo;

   // Verifica se já existe um documento recente para este aluno (menos de 1 hora)
   if (!empty($aluno['id'])) {
       try {
           $uma_hora_atras = date('Y-m-d H:i:s', strtotime('-1 hour'));
           $sql = "SELECT arquivo FROM documentos_emitidos
                   WHERE aluno_id = ? AND tipo_documento_id = 1
                   AND data_emissao >= ?
                   ORDER BY id DESC LIMIT 1";
           $doc_recente = executarConsulta($db, $sql, [$aluno['id'], $uma_hora_atras]);

           if ($doc_recente && !empty($doc_recente['arquivo'])) {
               $arquivo_existente = $diretorio . '/' . $doc_recente['arquivo'];
               if (file_exists($arquivo_existente)) {
                   error_log("Histórico recente encontrado para aluno ID " . $aluno['id'] . ": " . $arquivo_existente);
                   return $arquivo_existente;
               }
           }
       } catch (Exception $e) {
           error_log("Erro ao verificar históricos recentes: " . $e->getMessage());
       }
   }

   try {
       // Cria uma instância de TCPDF
       $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

       // Configurações do documento
       $pdf->SetCreator('Faciencia');
       $pdf->SetAuthor('Faciencia');
       $pdf->SetTitle('Histórico Acadêmico');
       $pdf->SetSubject('Histórico Acadêmico');
       $pdf->SetKeywords('Histórico, Acadêmico, Faciencia');

       // Remove cabeçalho e rodapé padrão
       $pdf->setPrintHeader(false);
       $pdf->setPrintFooter(false);

       // Define margens
       $pdf->SetMargins(15, 15, 15);
       $pdf->SetAutoPageBreak(true, 20);

       // Adiciona uma página
       $pdf->AddPage();

       // Formata os dados do aluno
        $nome_aluno = $aluno['nome'] ?? '';
        $cpf_aluno = formatarCpf($aluno['cpf'] ?? '');
        $curso_nome = $aluno['curso_nome'] ?? '';
        $polo_nome = $aluno['polo_nome'] ?? 'Não informado';

        // Busca a matrícula do aluno
        $matricula = '';
        if (!empty($aluno['id'])) {
            try {
                // Verifica se a coluna numero_matricula existe na tabela
                $coluna_existe = false;
                try {
                    $colunas = $db->fetchAll("SHOW COLUMNS FROM matriculas LIKE 'numero_matricula'");
                    $coluna_existe = !empty($colunas);
                } catch (Exception $e) {
                    error_log("Erro ao verificar coluna numero_matricula: " . $e->getMessage());
                }

                // Se a coluna não existir, tenta criá-la
                if (!$coluna_existe) {
                    try {
                        $db->query("ALTER TABLE matriculas ADD COLUMN numero_matricula VARCHAR(50) NULL AFTER id");
                        error_log("Coluna numero_matricula adicionada à tabela matriculas");

                        // Atualiza os registros existentes com um número de matrícula baseado no ID
                        $db->query("UPDATE matriculas SET numero_matricula = CONCAT('MAT', LPAD(id, 6, '0')) WHERE numero_matricula IS NULL");
                        error_log("Registros atualizados com números de matrícula");

                        $coluna_existe = true;
                    } catch (Exception $e) {
                        error_log("Erro ao adicionar coluna numero_matricula: " . $e->getMessage());
                    }
                }

                if ($coluna_existe) {
                    // Busca a matrícula usando a coluna numero_matricula
                    $sql_matricula = "SELECT id, numero_matricula FROM matriculas WHERE aluno_id = ? AND status = 'ativo' ORDER BY id DESC LIMIT 1";
                    $result_matricula = $db->fetchOne($sql_matricula, [$aluno['id']]);

                    if ($result_matricula) {
                        if (!empty($result_matricula['numero_matricula'])) {
                            $matricula = $result_matricula['numero_matricula'];
                        } else {
                            // Se o número de matrícula estiver vazio, usa o ID formatado
                            $matricula = 'MAT' . str_pad($result_matricula['id'], 6, '0', STR_PAD_LEFT);

                            // Atualiza o registro com o número de matrícula gerado
                            try {
                                $db->update('matriculas',
                                    ['numero_matricula' => $matricula],
                                    'id = ?',
                                    [$result_matricula['id']]
                                );
                            } catch (Exception $e) {
                                error_log("Erro ao atualizar número de matrícula: " . $e->getMessage());
                            }
                        }
                    } else {
                        // Se não encontrar matrícula ativa, busca qualquer matrícula
                        $sql_matricula = "SELECT id, numero_matricula FROM matriculas WHERE aluno_id = ? ORDER BY id DESC LIMIT 1";
                        $result_matricula = $db->fetchOne($sql_matricula, [$aluno['id']]);

                        if ($result_matricula) {
                            if (!empty($result_matricula['numero_matricula'])) {
                                $matricula = $result_matricula['numero_matricula'];
                            } else {
                                // Se o número de matrícula estiver vazio, usa o ID formatado
                                $matricula = 'MAT' . str_pad($result_matricula['id'], 6, '0', STR_PAD_LEFT);

                                // Atualiza o registro com o número de matrícula gerado
                                try {
                                    $db->update('matriculas',
                                        ['numero_matricula' => $matricula],
                                        'id = ?',
                                        [$result_matricula['id']]
                                    );
                                } catch (Exception $e) {
                                    error_log("Erro ao atualizar número de matrícula: " . $e->getMessage());
                                }
                            }
                        }
                    }
                } else {
                    // Se a coluna não existir e não puder ser criada, usa o ID da matrícula como número
                    $sql_matricula = "SELECT id FROM matriculas WHERE aluno_id = ? ORDER BY id DESC LIMIT 1";
                    $result_matricula = $db->fetchOne($sql_matricula, [$aluno['id']]);

                    if ($result_matricula && !empty($result_matricula['id'])) {
                        $matricula = 'MAT' . str_pad($result_matricula['id'], 6, '0', STR_PAD_LEFT);
                    }
                }
            } catch (Exception $e) {
                error_log("Erro ao buscar matrícula do aluno: " . $e->getMessage());
            }
        }
       $data_atual = date('d/m/Y');

       // Prepara a tabela de notas e cálculos
       $total_carga_horaria = 0;
       $soma_notas = 0;
       $count_notas = 0;

       foreach ($notas as $nota) {
           $nota_valor = ($nota['nota'] ?? 0);
           $disciplina_carga_horaria = ($nota['disciplina_carga_horaria'] ?? 0);
           $total_carga_horaria += $disciplina_carga_horaria;
           $soma_notas += $nota_valor;
           $count_notas++;
       }

       // Calcula a média geral
       $media_geral = $count_notas > 0 ? number_format($soma_notas / $count_notas, 1, ',', '.') : '0,0';

       // Logo e título - usando URL direta
       $logo_url = 'https://faciencia.edu.br/logo.png?v=1747241581740';
       $pdf->Image($logo_url, 15, 15, 40, '', 'PNG');

       // Título
       $pdf->SetFont('helvetica', 'B', 16);
       $pdf->SetTextColor(128, 0, 128); // Roxo
       $pdf->SetXY(60, 15);
       $pdf->Cell(135, 10, 'HISTÓRICO ACADÊMICO', 0, 1, 'L');

       $pdf->SetFont('helvetica', '', 11);
       $pdf->SetTextColor(100, 100, 100);
       $pdf->SetXY(60, 25);
       $pdf->Cell(135, 6, 'Registro oficial de desempenho do estudante', 0, 1, 'L');

       // Linha divisória
       $pdf->SetDrawColor(128, 0, 128); // Roxo
       $pdf->Line(15, 35, 195, 35);

       // Calcula a altura necessária para o box
       $altura_box = 30; // Altura base

       // Adiciona espaço para cada campo
       $num_campos = 5; // Nome, CPF, Curso, Carga Horária, Polo (ou espaço equivalente)
       if (!empty($matricula)) {
           $num_campos++; // Adiciona matrícula
       }

       // Calcula altura total (8 pixels por campo)
       $altura_box = 10 + ($num_campos * 8);

       // Box com informações do aluno (fundo cinza claro)
       $pdf->SetFillColor(245, 245, 245);
       $pdf->Rect(15, 45, 180, $altura_box, 'F');

       // Adiciona uma borda roxa à esquerda do box
       $pdf->SetDrawColor(128, 0, 128);
       $pdf->SetLineWidth(1.5);
       $pdf->Line(15, 45, 15, 45 + $altura_box);
       $pdf->SetLineWidth(0.2); // Reset da largura da linha

       // Posição Y inicial para as informações
       $y_pos = 48;
       $espaco_entre_linhas = 8; // Espaçamento entre as linhas

       // Informações do aluno - abordagem simplificada
       $pdf->SetFont('helvetica', 'B', 11);
       $pdf->SetTextColor(0, 0, 0);

       // Nome
       $pdf->SetXY(20, $y_pos);
       $pdf->Cell(20, 6, 'Nome:', 0, 0);
       $pdf->SetFont('helvetica', '', 11);
       $pdf->SetTextColor(128, 0, 128); // Roxo para o valor
       $pdf->Cell(160, 6, $nome_aluno, 0, 1);

       // CPF
       $y_pos += $espaco_entre_linhas;
       $pdf->SetXY(20, $y_pos);
       $pdf->SetFont('helvetica', 'B', 11);
       $pdf->SetTextColor(0, 0, 0);
       $pdf->Cell(20, 6, 'CPF:', 0, 0);
       $pdf->SetFont('helvetica', '', 11);
       $pdf->SetTextColor(128, 0, 128); // Roxo para o valor
       $pdf->Cell(160, 6, $cpf_aluno, 0, 1);

       // Matrícula (se disponível)
       if (!empty($matricula)) {
           $y_pos += $espaco_entre_linhas;
           $pdf->SetXY(20, $y_pos);
           $pdf->SetFont('helvetica', 'B', 11);
           $pdf->SetTextColor(0, 0, 0);
           $pdf->Cell(20, 6, 'Matrícula:', 0, 0);
           $pdf->SetFont('helvetica', '', 11);
           $pdf->SetTextColor(128, 0, 128); // Roxo para o valor
           $pdf->Cell(160, 6, $matricula, 0, 1);
       }

       // Curso
       $y_pos += $espaco_entre_linhas;
       $pdf->SetXY(20, $y_pos);
       $pdf->SetFont('helvetica', 'B', 11);
       $pdf->SetTextColor(0, 0, 0);
       $pdf->Cell(20, 6, 'Curso:', 0, 0);
       $pdf->SetFont('helvetica', '', 11);
       $pdf->SetTextColor(128, 0, 128); // Roxo para o valor
       $pdf->Cell(160, 6, $curso_nome, 0, 1);

       // Polo
       $y_pos += $espaco_entre_linhas;
       $pdf->SetXY(20, $y_pos);
       $pdf->SetFont('helvetica', 'B', 11);
       $pdf->SetTextColor(0, 0, 0);
       $pdf->Cell(20, 6, 'Polo:', 0, 0);
       $pdf->SetFont('helvetica', '', 11);
       $pdf->SetTextColor(128, 0, 128); // Roxo para o valor
       $pdf->Cell(160, 6, $polo_nome, 0, 1);

       // Tabela de disciplinas - posiciona 10mm abaixo do final do box de informações
       $pdf->SetY($pdf->GetY() + 10);

       // Cabeçalho da tabela
       $pdf->SetFillColor(128, 0, 128); // Roxo para cabeçalho
       $pdf->SetTextColor(255, 255, 255); // Texto branco
       $pdf->SetFont('helvetica', 'B', 10);

       // Definir larguras das colunas
       $w_disciplina = 85;
       $w_carga = 25;
       $w_nota = 20;
       $w_situacao = 40;

       // Cabeçalho
       $pdf->Cell($w_disciplina, 8, 'Disciplina', 1, 0, 'L', 1);
       $pdf->Cell($w_carga, 8, 'Carga Horária', 1, 0, 'C', 1);
       $pdf->Cell($w_nota, 8, 'Nota', 1, 0, 'C', 1);
       $pdf->Cell($w_situacao, 8, 'Situação', 1, 1, 'C', 1);

       // Dados das disciplinas
       $pdf->SetFont('helvetica', '', 9);
       $pdf->SetTextColor(0, 0, 0);
       $fill = false; // Para alternar cor de fundo
       $current_y = $pdf->GetY();

       // Se há notas para mostrar
       if (count($notas) > 0) {
           $i = 0; // Contador para controle de paginação
           foreach ($notas as $nota) {
               $i++;
               // Verificar se há espaço suficiente na página para mais uma linha
               // Verifica também se estamos próximos do final da página e restam poucas linhas
               // para evitar que apenas 1 ou 2 linhas fiquem em uma página separada
               $linhas_restantes = count($notas) - $i;
               $espaco_necessario = $linhas_restantes * 7; // 7 é a altura de cada linha

               if ($current_y > 220 || ($current_y > 200 && $linhas_restantes <= 3)) {
                   $pdf->AddPage();

                   // Repetir o cabeçalho na nova página
                   $pdf->SetFillColor(128, 0, 128);
                   $pdf->SetTextColor(255, 255, 255);
                   $pdf->SetFont('helvetica', 'B', 10);
                   $pdf->Cell($w_disciplina, 8, 'Disciplina', 1, 0, 'L', 1);
                   $pdf->Cell($w_carga, 8, 'Carga Horária', 1, 0, 'C', 1);
                   $pdf->Cell($w_nota, 8, 'Nota', 1, 0, 'C', 1);
                   $pdf->Cell($w_situacao, 8, 'Situação', 1, 1, 'C', 1);

                   $pdf->SetFont('helvetica', '', 9);
                   $pdf->SetTextColor(0, 0, 0);
                   $current_y = $pdf->GetY();
               }

               $nota_valor = ($nota['nota'] ?? 0);
               $situacao = $nota_valor >= 7 ? 'Aprovado' : 'Reprovado';
               $disciplina_carga_horaria = ($nota['disciplina_carga_horaria'] ?? 0);
               $disciplina_nome = $nota['disciplina_nome'] ?? '';

               // Limitar o nome da disciplina se for muito longo
               if (strlen($disciplina_nome) > 55) {
                   $disciplina_nome = substr($disciplina_nome, 0, 52) . '...';
               }

               // Configura cor de fundo alternada
               $pdf->SetFillColor(245, 245, 245);

               $pdf->Cell($w_disciplina, 7, $disciplina_nome, 1, 0, 'L', $fill);
               $pdf->Cell($w_carga, 7, $disciplina_carga_horaria, 1, 0, 'C', $fill);
               $pdf->Cell($w_nota, 7, number_format($nota_valor, 1, ',', '.'), 1, 0, 'C', $fill);

               // Cor diferente para situação
               if ($nota_valor >= 7) {
                   $pdf->SetTextColor(25, 135, 84); // Verde para aprovado
               } else {
                   $pdf->SetTextColor(220, 53, 69); // Vermelho para reprovado
               }

               $pdf->SetFont('helvetica', 'B', 9);
               $pdf->Cell($w_situacao, 7, $situacao, 1, 1, 'C', $fill);

               // Restaura cor do texto
               $pdf->SetTextColor(0, 0, 0);
               $pdf->SetFont('helvetica', '', 9);

               $fill = !$fill;
               $current_y = $pdf->GetY();
           }
       } else {
           $pdf->Cell(170, 7, 'Não há notas registradas para este aluno.', 1, 1, 'C');
       }

       // Totais/Média
       $pdf->SetFillColor(50, 0, 80); // Roxo escuro
       $pdf->SetTextColor(255, 255, 255);
       $pdf->SetFont('helvetica', 'B', 10);
       $pdf->Cell($w_disciplina, 7, 'Total / Média Geral', 1, 0, 'L', 1);
       $pdf->Cell($w_carga, 7, $total_carga_horaria, 1, 0, 'C', 1);
       $pdf->Cell($w_nota, 7, $media_geral, 1, 0, 'C', 1);
       $pdf->Cell($w_situacao, 7, '', 1, 1, 'C', 1);

       // Legenda
       $pdf->Ln(5);
       $pdf->SetFont('helvetica', '', 9);
       $pdf->SetTextColor(0, 0, 0);

       // Cria caixas coloridas para a legenda
       $pdf->SetFillColor(25, 135, 84); // Verde
       $pdf->Rect(25, $pdf->GetY(), 5, 5, 'F');
       $pdf->SetXY(32, $pdf->GetY());
       $pdf->Cell(60, 5, 'Aprovado: nota ≥ 7,0', 0, 0);

       $pdf->SetFillColor(220, 53, 69); // Vermelho
       $pdf->Rect(100, $pdf->GetY(), 5, 5, 'F');
       $pdf->SetXY(107, $pdf->GetY());
       $pdf->Cell(60, 5, 'Reprovado: nota < 7,0', 0, 1);

       // Posição da assinatura - após legenda
       $assinatura_y = $pdf->GetY() + 10;

       // Calcula o espaço total necessário para assinatura, código de verificação e rodapé
       $espaco_total_necessario = 100; // 30 para assinatura + 30 para código + 40 para rodapé

       // Se não houver espaço suficiente, ajusta a posição para garantir que tudo fique em uma página
       if ($pdf->GetPageHeight() - $assinatura_y < $espaco_total_necessario) {
           // Força tudo a ficar na mesma página, reduzindo o espaço entre elementos
           $assinatura_y = $pdf->GetPageHeight() - $espaco_total_necessario;
       }

       // Assinatura centralizada
       $pdf->SetY($assinatura_y);
       $pdf->SetX(60);
       $pdf->SetFont('helvetica', '', 10);
       $pdf->Cell(70, 5, $data_atual, 0, 1, 'C');

       // Adiciona imagem da assinatura se existir
       if (file_exists('assinatura_secretaria.png')) {
           $pdf->Image('assinatura_secretaria.png', 70, $assinatura_y + 7, 50, '', 'PNG');
       } else {
           // Simular uma assinatura
           $pdf->SetXY(60, $assinatura_y + 10);
           $pdf->SetFont('helvetica', 'I', 12);
           $pdf->Cell(70, 10, '_________________', 0, 1, 'C');
       }

       // Linha da assinatura
       $pdf->SetDrawColor(128, 0, 128); // Roxo
       $pdf->Line(60, $assinatura_y + 25, 130, $assinatura_y + 25);

       // Texto da assinatura
       $pdf->SetY($assinatura_y + 27);
       $pdf->SetX(60);
       $pdf->SetFont('helvetica', 'B', 10);
       $pdf->SetTextColor(128, 0, 128); // Roxo
       $pdf->Cell(70, 5, 'Secretária Acadêmica', 0, 1, 'C');

       $pdf->SetY($pdf->GetY());
       $pdf->SetX(60);
       $pdf->SetFont('helvetica', '', 10);
       $pdf->SetTextColor(0, 0, 0);
       $pdf->Cell(70, 5, 'Faciencia', 0, 1, 'C');

    // Box para código de verificação - mantendo na mesma página
$verificacao_y = $pdf->GetY() + 5;

// Calcula altura necessária: 45 para verificação + 20 para rodapé + 5 de espaço
$altura_necessaria = 70;
$espaco_disponivel = $pdf->GetPageHeight() - $verificacao_y - 20;

if ($espaco_disponivel < $altura_necessaria) {
    $verificacao_y = $pdf->GetPageHeight() - $altura_necessaria - 20;
}

$pdf->SetXY(25, $verificacao_y);
$pdf->SetFillColor(245, 245, 245);
// Box para acomodar o QR code
$pdf->Rect(25, $verificacao_y, 160, 45, 'F');

// Borda lateral roxa
$pdf->SetDrawColor(128, 0, 128);
$pdf->SetLineWidth(1.5);
$pdf->Line(25, $verificacao_y, 25, $verificacao_y + 45);
$pdf->SetLineWidth(0.2);

// Texto do código de verificação - centralizado
$pdf->SetXY(25, $verificacao_y + 2);
$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(160, 4, 'Código de verificação:', 0, 1, 'C');

$pdf->SetXY(25, $verificacao_y + 6);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetTextColor(128, 0, 128); // Roxo
$pdf->Cell(160, 4, $codigo_verificacao, 0, 1, 'C');

$pdf->SetXY(25, $verificacao_y + 11);
$pdf->SetFont('helvetica', '', 8);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(160, 4, 'Para verificar a autenticidade deste documento, acesse www.faciencia.edu.br/verificar', 0, 1, 'C');

// Adiciona QR code com o código de verificação - centralizado
$url_verificacao = 'https://faciencia.edu.br/verificar?codigo=' . $codigo_verificacao;
$style = array(
    'border' => false,
    'padding' => 0,
    'fgcolor' => array(128, 0, 128), // Roxo
    'bgcolor' => array(255, 255, 255) // Branco
);
// Centraliza o QR code
$qr_width = 25;
$box_width = 160;
$qr_x = 25 + ($box_width - $qr_width) / 2;
$pdf->write2DBarcode($url_verificacao, 'QRCODE,M', $qr_x, $verificacao_y + 16, $qr_width, $qr_width, $style);

// Rodapé - posicionado APÓS o box de verificação
$rodape_y = $verificacao_y + 50; // 50mm após o início do box de verificação

// Linha divisória antes do rodapé
$pdf->SetLineWidth(0.1);
$pdf->SetDrawColor(200, 200, 200);
$pdf->Line(15, $rodape_y - 2, 195, $rodape_y - 2);

// Adiciona informações institucionais no rodapé
$pdf->SetFont('helvetica', '', 8);
$pdf->SetTextColor(0, 0, 0);

// Primeira linha
$pdf->SetXY(15, $rodape_y);
$pdf->Cell(180, 4, '', 0, 0, 'C');

// Segunda linha
$pdf->SetXY(15, $rodape_y + 4);
$pdf->Cell(180, 4, 'CNPJ: 09.038.742/0001-80 • Tel: (41) 9 9256-2500 • Email: secretaria@faciencia.edu.br', 0, 0, 'C');

// Terceira linha
$pdf->SetXY(15, $rodape_y + 8);
$pdf->Cell(180, 4, 'Rua Visconde de Nacar, 1510 – 10º Andar – Conj. 1003 – Centro – Curitiba/PR', 0, 0, 'C');

       // Marca d'água (opcional)
       $pdf->SetAlpha(0.05);
       $pdf->SetFont('helvetica', 'B', 70);
       $pdf->SetTextColor(128, 0, 128);
       $pdf->StartTransform();
       $pdf->Rotate(45, 105, 150);
       $pdf->Text(40, 150, 'Faciencia');
       $pdf->StopTransform();
       $pdf->SetAlpha(1);

       // Salva o PDF usando uma abordagem alternativa
       $pdf_content = $pdf->Output('', 'S'); // 'S' retorna o PDF como string
       file_put_contents($caminho_arquivo, $pdf_content);

       // Verificação adicional
       if (!file_exists($caminho_arquivo)) {
           throw new Exception("Não foi possível salvar o arquivo PDF em $caminho_arquivo");
       }

       // Registra a emissão do documento no banco de dados, se não for uma regeneração
       if (!$forcar_download) {
           // Prepara os dados para inserção - Gera um número único para o documento
           $base_numero = "HA" . date('Ymd');
           $numero = $base_numero . str_pad($aluno['id'], 6, '0', STR_PAD_LEFT) . mt_rand(1000, 9999);

           // Verifica se o número já existe e gera um novo se necessário
           $numero_existe = true;
           $tentativas = 0;

           while ($numero_existe && $tentativas < 10) {
               $sql_check = "SELECT id FROM documentos_emitidos WHERE numero_documento = ?";
               $doc_existente = $db->fetchOne($sql_check, [$numero]);

               if (!$doc_existente) {
                   $numero_existe = false;
               } else {
                   // Gera um novo número com componente aleatório
                   $numero = $base_numero . str_pad($aluno['id'], 6, '0', STR_PAD_LEFT) . mt_rand(1000, 9999);
                   $tentativas++;
               }
           }

           error_log("Número de histórico gerado: " . $numero);

           // Verifica se o polo_id está definido
           if (empty($aluno['polo_id'])) {
               error_log("ATENÇÃO: polo_id está vazio ou nulo para histórico. Buscando um polo válido...");

               // Tenta buscar um polo válido
               try {
                   $sql_polo = "SELECT id FROM polos WHERE status = 'ativo' LIMIT 1";
                   $polo = $db->fetchOne($sql_polo);

                   if ($polo && isset($polo['id'])) {
                       error_log("Polo encontrado para histórico: " . $polo['id']);
                       $polo_id = $polo['id'];
                   } else {
                       // Se não encontrar, usa um valor padrão
                       error_log("Nenhum polo encontrado para histórico. Usando valor padrão 1");
                       $polo_id = 1;
                   }
               } catch (Exception $e) {
                   error_log("Erro ao buscar polo para histórico: " . $e->getMessage());
                   $polo_id = 1; // Valor padrão em caso de erro
               }
           } else {
               $polo_id = $aluno['polo_id'];
           }

           // Verifica se o curso_id está definido
           $curso_id = !empty($aluno['curso_id']) ? $aluno['curso_id'] : 1;

           // Usa a solicitação_id passada ou cria uma nova
           if (empty($solicitacao_id)) {
               try {
                   $solicitacao_id = criarOuObterSolicitacaoDocumento($db, $aluno['id'], $polo_id, 1); // Tipo 1 para histórico acadêmico
                   error_log("Usando solicitação ID para histórico: " . $solicitacao_id);
               } catch (Exception $e) {
                   error_log("Erro ao criar solicitação para histórico: " . $e->getMessage());
                   throw new Exception("Erro ao criar solicitação de documento para histórico: " . $e->getMessage());
               }
           } else {
               error_log("Usando solicitação ID existente para histórico: " . $solicitacao_id);
           }

           // Monta os dados para inserção de acordo com a estrutura exata da tabela
           $dados_documento = [
               'tipo_documento_id' => 1, // ID 1 é para histórico acadêmico
               'aluno_id' => $aluno['id'],
               'matricula_id' => 1, // Valor padrão para matricula_id que é obrigatório
               'curso_id' => $curso_id, // Usa o curso_id do aluno ou valor padrão
               'polo_id' => $polo_id, // Usa o polo_id verificado
               'data_emissao' => date('Y-m-d'),
               'data_validade' => date('Y-m-d', strtotime('+90 days')),
               'codigo_verificacao' => intval($codigo_verificacao), // Convertido para inteiro conforme estrutura da tabela
               'arquivo' => $nome_arquivo,
               'numero_documento' => $numero, // Usando o campo correto numero_documento
               'status' => 'ativo',
               'data_solicitacao' => date('Y-m-d'),
               'solicitacao_id' => $solicitacao_id // Usa a solicitação criada ou encontrada
           ];

           // Log para garantir que o tipo de documento está correto
           error_log("Emitindo HISTÓRICO ACADÊMICO (tipo_documento_id=1) para o aluno ID: " . $aluno['id'] . " - " . ($aluno['nome'] ?? 'Nome não disponível'));

           // Tenta inserir o documento
           try {
               // Log dos dados que serão inseridos para diagnóstico
               error_log("Tentando inserir histórico com os seguintes dados: " . json_encode($dados_documento));

               $documento_id = $db->insert('documentos_emitidos', $dados_documento);
               error_log("Documento inserido com ID: " . $documento_id);

               if (!$documento_id) {
                   error_log("Erro ao inserir registro na tabela documentos_emitidos: " . ($db->error ?? 'Erro desconhecido'));
                   throw new Exception("Erro ao registrar documento no banco de dados");
               }

               // Verifica se a coluna documento_id existe na tabela solicitacoes_documentos
               try {
                   $colunas = $db->fetchAll("SHOW COLUMNS FROM solicitacoes_documentos LIKE 'documento_id'");

                   // Se a coluna não existir, adiciona
                   if (empty($colunas)) {
                       error_log("Adicionando coluna documento_id à tabela solicitacoes_documentos");
                       $db->query("ALTER TABLE solicitacoes_documentos ADD COLUMN documento_id INT(10) UNSIGNED NULL DEFAULT NULL");
                   }

                   // Atualiza a solicitação com o ID do documento gerado
                   error_log("Atualizando solicitação ID {$solicitacao_id} com documento_id {$documento_id}");
                   $db->update('solicitacoes_documentos', [
                       'documento_id' => $documento_id,
                       'status' => 'pronto',
                       'updated_at' => date('Y-m-d H:i:s')
                   ], 'id = ?', [$solicitacao_id]);
               } catch (Exception $e) {
                   error_log("Erro ao atualizar solicitação com documento_id: " . $e->getMessage());
                   // Não interrompe o fluxo se falhar aqui
               }
           } catch (Exception $e) {
               error_log("Erro ao inserir documento: " . $e->getMessage());
               error_log("Rastreamento: " . $e->getTraceAsString());
               throw new Exception("Erro ao registrar documento no banco de dados: " . $e->getMessage());
           }
       }

       // Se for para retornar o caminho, retorna o caminho do arquivo
       if ($retornar_caminho) {
           return $caminho_arquivo;
       }

       // Decide se envia o arquivo para download ou visualização
       if ($visualizar) {
           // Exibe o PDF no navegador
           header('Content-Type: application/pdf');
           header('Content-Disposition: inline; filename="' . basename($caminho_arquivo) . '"');
           header('Content-Length: ' . filesize($caminho_arquivo));
           readfile($caminho_arquivo);
       } else {
           // Envia o PDF para download
           header('Content-Type: application/pdf');
           header('Content-Disposition: attachment; filename="' . basename($caminho_arquivo) . '"');
           header('Content-Length: ' . filesize($caminho_arquivo));
           readfile($caminho_arquivo);
       }

       // Registra o tempo de execução
       $tempo_fim = microtime(true);
       $tempo_execucao = round($tempo_fim - $tempo_inicio, 2);
       error_log("Histórico gerado em {$tempo_execucao} segundos para aluno ID: " . ($aluno['id'] ?? 'N/A'));

       return null;

   } catch (Exception $e) {
       // Registra o erro
       error_log('Erro ao gerar histórico acadêmico em PDF: ' . $e->getMessage());
       error_log('Rastreamento: ' . $e->getTraceAsString());

       // Exibe mensagem de erro
       $_SESSION['mensagem'] = [
           'tipo' => 'erro',
           'texto' => 'Erro ao gerar o histórico acadêmico em PDF: ' . $e->getMessage()
       ];
       header('Location: documentos.php');
   }

   exit;
}

// Função auxiliar para formatar CPF
function formatarCpf($cpf) {
   if ($cpf === null || $cpf === '') {
       return '';
   }
   $cpf = preg_replace('/[^0-9]/', '', $cpf);
   if (strlen($cpf) < 11) {
       return $cpf; // Retorna o CPF sem formatação se não tiver 11 dígitos
   }
   return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
}

// Função auxiliar para sanitizar nome de arquivo
function sanitizarNomeArquivo($nome) {
   if ($nome === null || $nome === '') {
       return 'documento';
   }

   // Remove acentos
   $nome = preg_replace('/[áàãâä]/ui', 'a', $nome);
   $nome = preg_replace('/[éèêë]/ui', 'e', $nome);
   $nome = preg_replace('/[íìîï]/ui', 'i', $nome);
   $nome = preg_replace('/[óòõôö]/ui', 'o', $nome);
   $nome = preg_replace('/[úùûü]/ui', 'u', $nome);
   $nome = preg_replace('/[ç]/ui', 'c', $nome);

   // Remove caracteres especiais
   $nome = preg_replace('/[^a-z0-9]/i', '_', $nome);

   // Converte para minúsculas
   $nome = strtolower($nome);

   // Limita o tamanho para evitar nomes de arquivo muito longos
   if (strlen($nome) > 50) {
       $nome = substr($nome, 0, 50);
   }

   return $nome;
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Faciencia ERP - <?php echo $titulo_pagina; ?></title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
   <link rel="stylesheet" href="css/styles.css">
   <style>
       /* Estilos específicos para a página de documentos */
       .card {
           border-radius: 1rem;
           box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
           transition: transform 0.3s, box-shadow 0.3s;
       }

       .card:hover {
           transform: translateY(-5px);
           box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
       }

       .badge {
           border-radius: 9999px;
           padding: 0.25rem 0.75rem;
           font-size: 0.75rem;
           font-weight: 600;
       }

       .badge-primary {
           background-color: #3B82F6;
           color: white;
       }

       .badge-warning {
           background-color: #F59E0B;
           color: white;
       }

       .badge-danger {
           background-color: #EF4444;
           color: white;
       }

       .badge-success {
           background-color: #10B981;
           color: white;
       }

    .btn-primary {
           background-color: #3B82F6;
           color: white;
           padding: 0.5rem 1rem;
           border-radius: 0.375rem;
           font-weight: 500;
           transition: background-color 0.2s;
       }

       .btn-primary:hover {
           background-color: #2563EB;
       }
   </style>
</head>
<body class="bg-gray-100">
   <div class="flex h-screen">
       <!-- Sidebar -->
       <?php include 'includes/sidebar.php'; ?>

       <!-- Main Content -->
       <div class="flex-1 flex flex-col overflow-hidden">
           <!-- Header -->
           <?php include 'includes/header.php'; ?>

           <!-- Main -->
           <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
               <div class="container mx-auto">
                   <div class="flex justify-between items-center mb-6">
                       <h1 class="text-2xl font-bold text-gray-800"><?php echo $titulo_pagina; ?></h1>
                   </div>

                   <?php if ($mensagem): ?>
                   <div class="bg-<?php echo $mensagem['tipo'] === 'sucesso' ? 'green' : ($mensagem['tipo'] === 'erro' ? 'red' : 'blue'); ?>-100 border-l-4 border-<?php echo $mensagem['tipo'] === 'sucesso' ? 'green' : ($mensagem['tipo'] === 'erro' ? 'red' : 'blue'); ?>-500 text-<?php echo $mensagem['tipo'] === 'sucesso' ? 'green' : ($mensagem['tipo'] === 'erro' ? 'red' : 'blue'); ?>-700 p-4 mb-6">
                       <?php echo $mensagem['texto']; ?>
                   </div>
                   <?php endif; ?>

                   <?php
                   // Inclui a view correspondente
                   error_log("Incluindo view: " . $view);
                   switch ($view) {
                       case 'selecionar_aluno':
                           include 'views/declaracoes/selecionar_aluno.php';
                           break;
                       case 'progresso':
                           include 'views/documentos/progresso.php';
                           break;
                       case 'listar':
                           include 'views/declaracoes/listar.php';
                           break;
                       case 'configuracoes':
                           include 'views/documentos/configuracoes.php';
                           break;
                       case 'diagnostico':
                           include 'views/documentos/diagnostico.php';
                           break;
                       case 'opcoes_declaracao':
                           include 'views/documentos/opcoes_declaracao.php';
                           break;
                       case 'baixar_em_lote':
                           error_log("Tentando incluir o arquivo: views/documentos/baixar_em_lote.php");
                           if (file_exists('views/documentos/baixar_em_lote.php')) {
                               error_log("Arquivo views/documentos/baixar_em_lote.php existe");
                               include 'views/documentos/baixar_em_lote.php';
                               error_log("Arquivo views/documentos/baixar_em_lote.php incluído com sucesso");
                           } else {
                               error_log("ERRO: Arquivo views/documentos/baixar_em_lote.php não existe");
                           }
                           break;
                       case 'inicio':
                       default:
                           include 'views/documentos/inicio.php';
                           break;
                   }
                   ?>
               </div>
           </main>

           <!-- Footer -->
           <?php include 'includes/footer.php'; ?>
       </div>
   </div>

   <script src="js/main.js"></script>
</body>
</html>