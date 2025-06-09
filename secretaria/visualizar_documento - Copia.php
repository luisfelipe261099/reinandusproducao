<?php
/**
 * Página para visualizar documentos emitidos
 */

// Carrega as configurações
require_once 'config/config.php';

// Carrega as classes necessárias
require_once 'includes/Database.php';
require_once 'includes/Auth.php';
require_once 'includes/Utils.php';

// Carrega as funções
require_once 'includes/functions.php';
require_once 'includes/init.php';

// Inicia a sessão se ainda não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica se o usuário está logado - verifica várias possibilidades de sessão
if (!isset($_SESSION['user_id']) && !isset($_SESSION['usuario_id']) && !isset($_SESSION['id'])) {
    // Registra o erro para depuração
    error_log("Usuário não autenticado. Sessão: " . print_r($_SESSION, true));

    // Se for uma solicitação de formato raw, não redireciona
    if (isset($_GET['formato']) && $_GET['formato'] === 'raw') {
        // Permite a visualização mesmo sem autenticação para o iframe
        // Isso é seguro porque ainda verificamos o ID do documento
    } else {
        // Redireciona para a página de login
        header('Location: login.php');
        exit;
    }
}

// Conecta ao banco de dados
$db = Database::getInstance();

// Função para executar consultas com tratamento de erro
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

// Verifica se o ID do documento foi informado
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
$sql = "SELECT d.*, a.nome as aluno_nome, td.nome as tipo_documento_nome,
        c.nome as curso_nome, p.nome as polo_nome, m.numero as matricula_numero
        FROM documentos_emitidos d
        LEFT JOIN alunos a ON d.aluno_id = a.id
        LEFT JOIN tipos_documentos td ON d.tipo_documento_id = td.id
        LEFT JOIN cursos c ON d.curso_id = c.id
        LEFT JOIN polos p ON d.polo_id = p.id
        LEFT JOIN matriculas m ON d.matricula_id = m.id
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

// Verifica se é para exibir o conteúdo direto (formato=raw)
if (isset($_GET['formato']) && $_GET['formato'] === 'raw') {
    // Verifica se o arquivo existe
    $arquivo = 'uploads/documentos/' . $documento['arquivo'];
    $arquivo_encontrado = false;
    $novo_arquivo = null;

    // Log para depuração detalhada
    error_log("Tentando visualizar arquivo: " . $arquivo);
    error_log("O arquivo existe? " . (file_exists($arquivo) ? "Sim" : "Não"));
    error_log("Documento ID: " . $documento_id);
    error_log("Arquivo registrado no banco: " . $documento['arquivo']);
    error_log("Caminho completo: " . realpath(dirname($arquivo)) . '/' . basename($arquivo));
    error_log("Diretório uploads/documentos existe? " . (is_dir('uploads/documentos') ? "Sim" : "Não"));

    // Verifica permissões
    if (is_dir('uploads/documentos')) {
        error_log("Permissões do diretório uploads/documentos: " . substr(sprintf('%o', fileperms('uploads/documentos')), -4));
    }

    // Lista arquivos no diretório para depuração
    if (is_dir('uploads/documentos')) {
        $arquivos_dir = scandir('uploads/documentos');
        error_log("Arquivos no diretório uploads/documentos: " . implode(", ", $arquivos_dir));
    }

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
        }

        // Se ainda não encontrou, verifica na pasta temp
        if (!$arquivo_encontrado) {
            $arquivo_temp = 'temp/' . basename($documento['arquivo']);
            error_log("Verificando arquivo em temp: " . $arquivo_temp);

            if (file_exists($arquivo_temp)) {
                $arquivo = $arquivo_temp;
                $arquivo_encontrado = true;
                error_log("Arquivo encontrado em temp: " . $arquivo);
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
                // Determina o tipo de documento e gera o conteúdo
                if ($documento['tipo_documento_id'] == 1) {
                    // Declaração de matrícula - Gera diretamente o conteúdo sem chamar a função completa
                    error_log("Regenerando declaração de matrícula para o aluno ID: " . $documento['aluno_id']);

                    // Inclui apenas as funções necessárias
                    if (!function_exists('gerarConteudoDeclaracao')) {
                        require_once 'documentos.php';
                    }

                    // Gera apenas o conteúdo HTML sem registrar no banco de dados
                    $html_content = gerarConteudoDeclaracao($aluno, $documento['codigo_verificacao']);

                } else if ($documento['tipo_documento_id'] == 2) {
                    // Histórico acadêmico - Gera diretamente o conteúdo sem chamar a função completa
                    error_log("Regenerando histórico acadêmico para o aluno ID: " . $documento['aluno_id']);

                    // Inclui apenas as funções necessárias
                    if (!function_exists('gerarConteudoHistorico')) {
                        require_once 'documentos.php';
                    }

                    // Busca as notas do aluno
                    $sql_notas = "SELECT n.*, d.nome as disciplina_nome, d.carga_horaria as disciplina_carga_horaria
                                 FROM notas n
                                 LEFT JOIN disciplinas d ON n.disciplina_id = d.id
                                 WHERE n.aluno_id = ?";
                    $notas = executarConsultaAll($db, $sql_notas, [$documento['aluno_id']]);

                    // Gera apenas o conteúdo HTML sem registrar no banco de dados
                    $html_content = gerarConteudoHistorico($aluno, $notas, $documento['codigo_verificacao']);

                } else {
                    // Tipo de documento desconhecido
                    $_SESSION['mensagem'] = [
                        'tipo' => 'erro',
                        'texto' => 'Tipo de documento desconhecido.'
                    ];
                    header('Location: documentos.php');
                    exit;
                }

                // Salva o conteúdo regenerado
                if (!file_exists('uploads/documentos')) {
                    mkdir('uploads/documentos', 0777, true);
                }

                $arquivo = 'uploads/documentos/' . $documento['arquivo'];
                file_put_contents($arquivo, $html_content);

                if (file_exists($arquivo)) {
                    $arquivo_encontrado = true;
                    error_log("Documento regenerado com sucesso: " . $arquivo);
                }
            }
        }

        // Se não encontrou o arquivo, exibe mensagem de erro
        if (!$arquivo_encontrado) {
            $_SESSION['mensagem'] = [
                'tipo' => 'erro',
                'texto' => 'Arquivo não encontrado no servidor.'
            ];
            header('Location: documentos.php');
            exit;
        }
    }

    // Define o tipo de conteúdo com base na extensão do arquivo
    $extension = pathinfo($arquivo, PATHINFO_EXTENSION);
    $content_type = $extension === 'html' ? 'text/html' : 'application/pdf';
    header('Content-Type: ' . $content_type);

    // Log final antes de exibir o arquivo
    error_log("Exibindo arquivo: " . $arquivo . " com Content-Type: " . $content_type);

    // Lê o conteúdo do arquivo
    $conteudo = file_get_contents($arquivo);

    if ($conteudo === false) {
        error_log("Erro ao ler o conteúdo do arquivo: " . $arquivo);
        echo "<html><body><h1>Erro ao ler o documento</h1><p>Não foi possível ler o conteúdo do documento. Por favor, tente baixá-lo em vez de visualizá-lo.</p></body></html>";
    } else {
        // Exibe o conteúdo do arquivo
        echo $conteudo;
    }
    exit;
} else {
    // Prepara os dados para a visualização
    $titulo_pagina = 'Visualizar Documento';

    // Verifica se o arquivo existe antes de mostrar o iframe
    $arquivo = 'uploads/documentos/' . $documento['arquivo'];
    $arquivo_encontrado = false;

    if (file_exists($arquivo)) {
        $arquivo_encontrado = true;
    } else {
        // Tenta encontrar o arquivo pelo nome em uploads/documentos
        $dir_uploads = 'uploads/documentos/';
        if (is_dir($dir_uploads)) {
            $arquivos = scandir($dir_uploads);
            $nome_arquivo = basename($documento['arquivo']);

            foreach ($arquivos as $arq) {
                if (strtolower($arq) === strtolower($nome_arquivo)) {
                    $arquivo = $dir_uploads . $arq;
                    $arquivo_encontrado = true;
                    break;
                }
            }
        }

        // Tenta encontrar o arquivo na pasta temp
        if (!$arquivo_encontrado) {
            $arquivo_temp = 'temp/' . basename($documento['arquivo']);
            if (file_exists($arquivo_temp)) {
                $arquivo = $arquivo_temp;
                $arquivo_encontrado = true;
            }
        }
    }

    // Se o arquivo não foi encontrado, tenta regenerá-lo
    if (!$arquivo_encontrado && !empty($documento['aluno_id'])) {
        error_log("Arquivo não encontrado. Tentando regenerar o documento ID: " . $documento_id);

        // Busca dados do aluno
        $sql = "SELECT a.*, c.nome as curso_nome, c.carga_horaria as curso_carga_horaria, p.nome as polo_nome
                FROM alunos a
                LEFT JOIN cursos c ON a.curso_id = c.id
                LEFT JOIN polos p ON a.polo_id = p.id
                WHERE a.id = ?";
        $aluno = executarConsulta($db, $sql, [$documento['aluno_id']]);

        if ($aluno) {
            // Determina o tipo de documento e gera o conteúdo
            if ($documento['tipo_documento_id'] == 1) {
                // Declaração de matrícula
                $html_content = gerarConteudoDeclaracao($aluno, $documento['codigo_verificacao']);
            } else if ($documento['tipo_documento_id'] == 2) {
                // Histórico acadêmico
                $sql_notas = "SELECT n.*, d.nome as disciplina_nome, d.carga_horaria as disciplina_carga_horaria
                             FROM notas n
                             LEFT JOIN disciplinas d ON n.disciplina_id = d.id
                             WHERE n.aluno_id = ?";
                $notas = executarConsultaAll($db, $sql_notas, [$documento['aluno_id']]);
                $html_content = gerarConteudoHistorico($aluno, $notas, $documento['codigo_verificacao']);
            }

            if (isset($html_content)) {
                // Cria o diretório se não existir
                if (!is_dir('uploads/documentos')) {
                    mkdir('uploads/documentos', 0777, true);
                }

                // Salva o conteúdo no arquivo
                $arquivo = 'uploads/documentos/' . $documento['arquivo'];
                if (file_put_contents($arquivo, $html_content) !== false) {
                    $arquivo_encontrado = true;
                    error_log("Documento regenerado com sucesso: " . $arquivo);
                }
            }
        }
    }

    // Prepara os dados do documento para exibição
    $dados_documento = [
        'aluno_nome' => $documento['aluno_nome'],
        'aluno_cpf' => '', // Buscar CPF do aluno se necessário
        'matricula_numero' => $documento['matricula_numero'],
        'curso_nome' => $documento['curso_nome'],
        'polo_nome' => $documento['polo_nome'],
        'data_emissao' => $documento['data_emissao'],
        'codigo_verificacao' => $documento['codigo_verificacao'],
        'instituicao' => 'Faculdade FaCiência',
        'cidade' => 'Curitiba/PR',
        'responsavel' => 'Secretaria Acadêmica',
        'arquivo_encontrado' => $arquivo_encontrado
    ];

    // Inclui a view para exibir o documento em um iframe
    include 'views/documentos/visualizar_iframe.php';
}

