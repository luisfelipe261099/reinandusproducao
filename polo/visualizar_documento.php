<?php
/**
 * Página para visualizar documentos emitidos (versão para polo)
 */

// Inicializa o sistema
require_once __DIR__ . '/../includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica se o usuário é do tipo polo
if (getUsuarioTipo() !== 'polo') {
    setMensagem('erro', 'Você não tem permissão para acessar esta página.');
    redirect('../polo/index.php');
    exit;
}

// Instancia o banco de dados
$db = Database::getInstance();

// Obtém o ID do polo associado ao usuário
$usuario_id = getUsuarioId();
$sql = "SELECT id FROM polos WHERE responsavel_id = ?";
$resultado = $db->fetchOne($sql, [$usuario_id]);
$polo_id = $resultado['id'] ?? null;

if (!$polo_id) {
    setMensagem('erro', 'Não foi possível identificar o polo associado ao seu usuário.');
    redirect('index.php');
    exit;
}

// Verifica se o ID do documento foi informado
$documento_id = $_GET['id'] ?? null;

if (empty($documento_id)) {
    setMensagem('erro', 'Documento não informado.');
    redirect('documentos.php');
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
$documento = $db->fetchOne($sql, [$documento_id]);

if (!$documento) {
    setMensagem('erro', 'Documento não encontrado.');
    redirect('documentos.php');
    exit;
}

// Verifica se o documento pertence ao polo
if ($documento['polo_id'] != $polo_id) {
    // Verifica se o aluno pertence ao polo
    $sql = "SELECT id FROM alunos WHERE id = ? AND polo_id = ?";
    $aluno = $db->fetchOne($sql, [$documento['aluno_id'], $polo_id]);

    if (!$aluno) {
        setMensagem('erro', 'Este documento não pertence ao seu polo.');
        redirect('documentos.php');
        exit;
    }
}

// Verifica se é para exibir o conteúdo direto (formato=raw)
if (isset($_GET['formato']) && $_GET['formato'] === 'raw') {
    // Verifica se o arquivo existe
    $arquivo = '../uploads/documentos/' . $documento['arquivo'];
    $arquivo_encontrado = false;
    $novo_arquivo = null;

    // Log para depuração
    error_log("Tentando visualizar arquivo: " . $arquivo);
    error_log("O arquivo existe? " . (file_exists($arquivo) ? "Sim" : "Não"));

    if (file_exists($arquivo)) {
        $arquivo_encontrado = true;
    } else {
        // Tenta encontrar o arquivo pelo nome em uploads/documentos
        $dir_uploads = '../uploads/documentos/';
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
            }
        }

        // Se ainda não encontrou, verifica na pasta temp
        if (!$arquivo_encontrado) {
            $arquivo_temp = '../temp/' . basename($documento['arquivo']);
            error_log("Verificando arquivo em temp: " . $arquivo_temp);

            if (file_exists($arquivo_temp)) {
                $arquivo = $arquivo_temp;
                $arquivo_encontrado = true;
                error_log("Arquivo encontrado em temp: " . $arquivo);
            }
        }
    }

    // Se não encontrou o arquivo, exibe mensagem de erro
    if (!$arquivo_encontrado) {
        setMensagem('erro', 'Arquivo não encontrado no servidor.');
        redirect('documentos.php');
        exit;
    }

    // Define o tipo de conteúdo com base na extensão do arquivo
    $extension = pathinfo($arquivo, PATHINFO_EXTENSION);
    $content_type = $extension === 'html' ? 'text/html' : 'application/pdf';
    header('Content-Type: ' . $content_type);

    // Exibe o conteúdo do arquivo
    readfile($arquivo);
    exit;
} else {
    // Prepara os dados para a visualização
    $titulo_pagina = 'Visualizar Documento';

    // Verifica se o arquivo existe antes de mostrar o iframe
    $arquivo = '../uploads/documentos/' . $documento['arquivo'];
    $arquivo_encontrado = false;

    if (file_exists($arquivo)) {
        $arquivo_encontrado = true;
    } else {
        // Tenta encontrar o arquivo pelo nome em uploads/documentos
        $dir_uploads = '../uploads/documentos/';
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
            $arquivo_temp = '../temp/' . basename($documento['arquivo']);
            if (file_exists($arquivo_temp)) {
                $arquivo = $arquivo_temp;
                $arquivo_encontrado = true;
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
