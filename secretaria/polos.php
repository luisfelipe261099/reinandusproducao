<?php
/**
 * ================================================================
 * FACIÊNCIA ERP - MÓDULO DE GERENCIAMENTO DE POLOS EDUCACIONAIS
 * ================================================================
 * 
 * Sistema completo de gestão de polos educacionais
 * Permite criar, editar, visualizar e gerenciar polos de ensino
 * com funcionalidades avançadas de vinculação de cursos e turmas
 * 
 * @version 2.0.0
 * @author Faciência ERP Development Team
 * @created 2024
 * @updated 2025-06-10
 * 
 * Funcionalidades:
 * - Cadastro e edição de polos educacionais
 * - Vinculação de cursos aos polos
 * - Vinculação de turmas aos polos
 * - Busca inteligente de cidades (cache local + API)
 * - Gestão de tipos de polo
 * - Visualização detalhada com relatórios
 * - Sistema de permissões integrado
 * ================================================================
 */

// ================================================================
// INICIALIZAÇÃO DO SISTEMA E VERIFICAÇÕES DE SEGURANÇA
// ================================================================

// Inicializa o sistema
require_once __DIR__ . '/includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica se o usuário tem permissão para acessar o módulo de polos
exigirPermissao('polos');

// ================================================================
// CONFIGURAÇÃO DO BANCO DE DADOS E FUNÇÕES AUXILIARES
// ================================================================

// Instancia o banco de dados
$db = Database::getInstance();

/**
 * Executa uma consulta SQL que retorna um único resultado
 * 
 * @param object $db Instância do banco de dados
 * @param string $sql Query SQL a ser executada
 * @param array $params Parâmetros para a query
 * @param mixed $default Valor padrão se não encontrar resultado
 * @return mixed Resultado da consulta ou valor padrão
 */
function executarConsulta($db, $sql, $params = [], $default = null) {
    try {
        return $db->fetchOne($sql, $params) ?: $default;
    } catch (Exception $e) {
        error_log('Erro na consulta SQL: ' . $e->getMessage());
        return $default;
    }
}

/**
 * Executa uma consulta SQL que retorna múltiplos resultados
 * 
 * @param object $db Instância do banco de dados
 * @param string $sql Query SQL a ser executada
 * @param array $params Parâmetros para a query
 * @param array $default Valor padrão se não encontrar resultados
 * @return array Resultados da consulta ou array vazio
 */
function executarConsultaAll($db, $sql, $params = [], $default = []) {
    try {
        return $db->fetchAll($sql, $params) ?: $default;
    } catch (Exception $e) {
        error_log('Erro na consulta SQL: ' . $e->getMessage());
        return $default;
    }
}

// ================================================================
// CONTROLADOR PRINCIPAL - PROCESSAMENTO DE AÇÕES
// ================================================================

// Define a ação solicitada (padrão: listar)
$action = $_GET['action'] ?? 'listar';

// Define o título da página
$titulo_pagina = 'Gerenciamento de Polos';

// ================================================================
// PROCESSAMENTO DE AÇÕES ESPECIAIS (AJAX/API)
// ================================================================
// Verifica se é uma ação que não precisa de HTML completo
// (retorna JSON ou faz redirecionamentos diretos)

if ($action === 'salvar' || $action === 'salvar_com_tipos' || $action === 'salvar_financeiro' || $action === 'salvar_financeiro_novo' || $action === 'excluir' || $action === 'buscar_cursos' || $action === 'vincular_cursos' || $action === 'buscar_turmas' || $action === 'vincular_turmas' || $action === 'buscar_cidades' || $action === 'buscar_responsaveis' || $action === 'buscar_tipos_polos' || $action === 'buscar_financeiro_polo') {
    
    // ============================================================
    // BUSCA DE CURSOS (AJAX)
    // ============================================================
    if ($action === 'buscar_cursos') {
        // Verifica se o termo de busca foi informado
        if (!isset($_GET['termo']) || empty($_GET['termo'])) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Termo de busca não informado.']);
            exit;
        }

        $termo = trim($_GET['termo']);
        $polo_id = isset($_GET['polo_id']) ? (int)$_GET['polo_id'] : 0;

        // Busca todos os cursos, independente de estarem vinculados a algum polo
        $sql = "SELECT c.id, c.nome, c.sigla, c.nivel, c.modalidade, c.status, c.polo_id, p.nome as polo_nome
                FROM cursos c
                LEFT JOIN polos p ON c.polo_id = p.id
                WHERE (c.nome LIKE ? OR c.sigla LIKE ? OR CAST(? AS CHAR) = '')
                ORDER BY c.nome ASC
                LIMIT 50";

        try {
            $cursos = $db->fetchAll($sql, ["%{$termo}%", "%{$termo}%", $termo]);

            header('Content-Type: application/json');
            echo json_encode(['cursos' => $cursos ?: []]);
            exit;
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Erro ao buscar cursos: ' . $e->getMessage()]);
            exit;
        }
    } elseif ($action === 'vincular_cursos') {
        // Verifica se o ID do polo foi informado
        if (!isset($_POST['polo_id']) || empty($_POST['polo_id'])) {
            $_SESSION['mensagem'] = 'ID do polo não informado.';
            $_SESSION['mensagem_tipo'] = 'erro';
            header('Location: polos.php');
            exit;
        }

        $polo_id = (int)$_POST['polo_id'];
        $cursos_ids = $_POST['cursos_ids'] ?? [];

        // Verifica se o polo existe
        $sql = "SELECT id FROM polos WHERE id = ?";
        $polo = $db->fetchOne($sql, [$polo_id]);

        if (!$polo) {
            $_SESSION['mensagem'] = 'Polo não encontrado.';
            $_SESSION['mensagem_tipo'] = 'erro';
            header('Location: polos.php');
            exit;
        }

        try {
            // Inicia uma transação
            $db->beginTransaction();

            // Verifica se algum curso já está vinculado a outro polo (apenas para informar ao usuário)
            $cursos_vinculados = [];
            if (!empty($cursos_ids)) {
                $placeholders = implode(',', array_fill(0, count($cursos_ids), '?'));
                $sql = "SELECT id, nome, polo_id FROM cursos WHERE id IN ({$placeholders}) AND polo_id IS NOT NULL AND polo_id != ? AND polo_id > 0";
                $params = array_merge($cursos_ids, [$polo_id]);
                $cursos_com_polo = $db->fetchAll($sql, $params);

                if (!empty($cursos_com_polo)) {
                    // Busca os nomes dos polos para exibir mensagem mais informativa
                    foreach ($cursos_com_polo as $curso) {
                        $sql_polo = "SELECT nome FROM polos WHERE id = ?";
                        $polo_nome = $db->fetchOne($sql_polo, [$curso['polo_id']]);
                        $cursos_vinculados[] = $curso['nome'] . ' (anteriormente vinculado ao polo ' . ($polo_nome ? $polo_nome['nome'] : 'ID: ' . $curso['polo_id']) . ')';
                    }
                }
            }

            // Se houver cursos já vinculados, exibe alerta informativo
            if (!empty($cursos_vinculados)) {
                $_SESSION['alerta'] = 'Os seguintes cursos foram transferidos de outros polos: ' . implode(', ', $cursos_vinculados);
                $_SESSION['alerta_tipo'] = 'aviso';
            }

            // Atualiza os cursos selecionados para vincular ao polo
            if (!empty($cursos_ids)) {
                $sql = "UPDATE cursos SET polo_id = ? WHERE id IN (" . implode(',', array_fill(0, count($cursos_ids), '?')) . ")";
                $params = array_merge([$polo_id], $cursos_ids);
                $db->query($sql, $params);
            }

            // Confirma a transação
            $db->commit();

            $_SESSION['mensagem'] = 'Cursos vinculados com sucesso ao polo.';
            $_SESSION['mensagem_tipo'] = 'sucesso';
        } catch (Exception $e) {
            // Desfaz a transação em caso de erro
            $db->rollBack();

            $_SESSION['mensagem'] = 'Erro ao vincular cursos ao polo: ' . $e->getMessage();
            $_SESSION['mensagem_tipo'] = 'erro';
        }

        // Adiciona um parâmetro para forçar a atualização da página e evitar cache
        $timestamp = time();

        // Redireciona para a página de visualização do polo com parâmetro de cache-busting
        header("Location: polos.php?action=visualizar&id={$polo_id}&refresh={$timestamp}");
        exit;
    } elseif ($action === 'buscar_cidades') {
        // Verifica se o termo de busca foi informado
        if (!isset($_GET['termo']) || empty($_GET['termo'])) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Termo de busca não informado.']);
            exit;
        }

        $termo = trim($_GET['termo']);

        // Verifica se o termo tem pelo menos 2 caracteres
        if (strlen($termo) < 2) {
            header('Content-Type: application/json');
            echo json_encode(['cidades' => []]);
            exit;
        }

        // Chave de cache para armazenar resultados temporariamente
        $cache_key = 'cidade_' . md5($termo);
        $cache_file = __DIR__ . '/cache/' . $cache_key . '.json';
        $cache_time = 3600; // 1 hora de cache

        // Verifica se existe cache válido
        if (file_exists($cache_file) && (time() - filemtime($cache_file) < $cache_time)) {
            $cache_data = file_get_contents($cache_file);
            if ($cache_data) {
                header('Content-Type: application/json');
                echo $cache_data;
                exit;
            }
        }

        try {
            // Tenta usar o banco de dados local primeiro por ser mais rápido
            // Busca mais precisa: prioriza cidades que começam com o termo
            $sql = "SELECT c.id, c.nome, e.sigla
                    FROM cidades c
                    JOIN estados e ON c.estado_id = e.id
                    WHERE c.nome LIKE ?
                    ORDER BY
                        CASE
                            WHEN c.nome LIKE ? THEN 0
                            ELSE 1
                        END,
                        c.nome ASC
                    LIMIT 20";

            error_log("Buscando cidades no banco de dados local com termo: " . $termo);
            $cidades = $db->fetchAll($sql, ["%{$termo}%", "{$termo}%"]) ?: [];
            error_log("Resultados encontrados no banco local: " . count($cidades));

            // Se não houver resultados locais, tenta buscar diretamente na API do IBGE
            if (empty($cidades)) {
                // Configuração para requisição HTTP
                $options = [
                    'http' => [
                        'method' => 'GET',
                        'header' => [
                            'User-Agent: PHP/Sistema-Polos',
                            'Accept: application/json'
                        ],
                        'timeout' => 3 // timeout reduzido para 3 segundos
                    ]
                ];
                $context = stream_context_create($options);

                // Tenta a API do IBGE diretamente
                $url = "https://servicodados.ibge.gov.br/api/v1/localidades/municipios?nome=" . urlencode($termo);
                error_log("Buscando cidades na API do IBGE: " . $url);
                $response = @file_get_contents($url, false, $context);
                error_log("Resposta da API do IBGE: " . ($response !== false ? "Sucesso" : "Falha"));

                if ($response !== false) {
                    $municipios = json_decode($response, true);

                    if (is_array($municipios)) {
                        foreach ($municipios as $municipio) {
                            if (isset($municipio['nome']) && isset($municipio['id'])) {
                                $cidades[] = [
                                    'id' => $municipio['id'],
                                    'nome' => $municipio['nome'],
                                    'sigla' => $municipio['microrregiao']['mesorregiao']['UF']['sigla'] ?? ''
                                ];
                            }
                        }
                    }
                }

                // Se ainda não tiver resultados, tenta a API do Brasil API como backup
                if (empty($cidades)) {
                    $url = "https://brasilapi.com.br/api/ibge/municipios/v1?providers=dados-abertos-br,gov,wikipedia&search=" . urlencode($termo);
                    $response = @file_get_contents($url, false, $context);

                    if ($response !== false) {
                        $municipios = json_decode($response, true);

                        if (is_array($municipios)) {
                            foreach ($municipios as $municipio) {
                                if (isset($municipio['nome']) && isset($municipio['codigo_ibge'])) {
                                    $cidades[] = [
                                        'id' => $municipio['codigo_ibge'],
                                        'nome' => $municipio['nome'],
                                        'sigla' => $municipio['uf'] ?? ''
                                    ];
                                }
                            }
                        }
                    }
                }
            }

            // Limita a quantidade de resultados
            $cidades = array_slice($cidades, 0, 20);

            // Prepara a resposta
            $response_data = json_encode(['cidades' => $cidades]);

            // Salva no cache se houver resultados
            if (!empty($cidades)) {
                // Cria o diretório de cache se não existir
                if (!is_dir(__DIR__ . '/cache')) {
                    mkdir(__DIR__ . '/cache', 0755, true);
                }

                // Salva os dados no cache
                file_put_contents($cache_file, $response_data);
            }

            // Retorna a resposta
            header('Content-Type: application/json');
            echo $response_data;
            exit;
        } catch (Exception $e) {
            // Em caso de erro, tenta retornar o que tiver do banco de dados local
            try {
                $sql = "SELECT c.id, c.nome, e.sigla
                        FROM cidades c
                        JOIN estados e ON c.estado_id = e.id
                        WHERE c.nome LIKE ?
                        ORDER BY
                            CASE
                                WHEN c.nome LIKE ? THEN 0
                                ELSE 1
                            END,
                            c.nome ASC
                        LIMIT 20";

                $cidades = $db->fetchAll($sql, ["%{$termo}%", "{$termo}%"]) ?: [];

                header('Content-Type: application/json');
                echo json_encode(['cidades' => $cidades]);
                exit;
            } catch (Exception $innerException) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Erro ao buscar cidades: ' . $e->getMessage()]);
                exit;
            }
        }
    } elseif ($action === 'buscar_responsaveis') {
        // Verifica se o termo de busca foi informado
        if (!isset($_GET['termo']) || empty($_GET['termo'])) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Termo de busca não informado.']);
            exit;
        }

        $termo = trim($_GET['termo']);

        // Busca usuários que podem ser responsáveis por polos
        $sql = "SELECT id, nome
                FROM usuarios
                WHERE (nome LIKE ? OR email LIKE ?)
                AND tipo IN ('admin_master', 'diretoria', 'secretaria_academica', 'polo')
                AND status = 'ativo'
                ORDER BY nome
                LIMIT 20";

        try {
            $responsaveis = $db->fetchAll($sql, ["%{$termo}%", "%{$termo}%"]);

            header('Content-Type: application/json');
            echo json_encode(['responsaveis' => $responsaveis ?: []]);
            exit;
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Erro ao buscar responsáveis: ' . $e->getMessage()]);
            exit;
        }
    } elseif ($action === 'buscar_turmas') {
        // Verifica se o termo de busca foi informado
        if (!isset($_GET['termo']) || empty($_GET['termo'])) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Termo de busca não informado.']);
            exit;
        }

        $termo = trim($_GET['termo']);
        $polo_id = isset($_GET['polo_id']) ? (int)$_GET['polo_id'] : 0;

        // Busca todas as turmas, independente de estarem vinculadas a algum polo
        $sql = "SELECT t.id, t.nome, t.turno, t.status, t.polo_id, t.curso_id, c.nome as curso_nome, p.nome as polo_nome
                FROM turmas t
                LEFT JOIN cursos c ON t.curso_id = c.id
                LEFT JOIN polos p ON t.polo_id = p.id
                WHERE (t.nome LIKE ? OR c.nome LIKE ? OR CAST(? AS CHAR) = '')
                ORDER BY t.nome ASC
                LIMIT 50";

        try {
            $turmas = $db->fetchAll($sql, ["%{$termo}%", "%{$termo}%", $termo]);

            header('Content-Type: application/json');
            echo json_encode(['turmas' => $turmas ?: []]);
            exit;
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Erro ao buscar turmas: ' . $e->getMessage()]);
            exit;
        }
    } elseif ($action === 'vincular_turmas') {
        // Verifica se o ID do polo foi informado
        if (!isset($_POST['polo_id']) || empty($_POST['polo_id'])) {
            $_SESSION['mensagem'] = 'ID do polo não informado.';
            $_SESSION['mensagem_tipo'] = 'erro';
            header('Location: polos.php');
            exit;
        }

        $polo_id = (int)$_POST['polo_id'];
        $turmas_ids = $_POST['turmas_ids'] ?? [];

        // Verifica se o polo existe
        $sql = "SELECT id FROM polos WHERE id = ?";
        $polo = $db->fetchOne($sql, [$polo_id]);

        if (!$polo) {
            $_SESSION['mensagem'] = 'Polo não encontrado.';
            $_SESSION['mensagem_tipo'] = 'erro';
            header('Location: polos.php');
            exit;
        }

        try {
            // Inicia uma transação
            $db->beginTransaction();

            // Verifica se alguma turma já está vinculada a outro polo (apenas para informar ao usuário)
            $turmas_vinculadas = [];
            if (!empty($turmas_ids)) {
                $placeholders = implode(',', array_fill(0, count($turmas_ids), '?'));
                $sql = "SELECT t.id, t.nome, t.polo_id, p.nome as polo_nome
                        FROM turmas t
                        LEFT JOIN polos p ON t.polo_id = p.id
                        WHERE t.id IN ({$placeholders}) AND t.polo_id IS NOT NULL AND t.polo_id != ? AND t.polo_id > 0";
                $params = array_merge($turmas_ids, [$polo_id]);
                $turmas_com_polo = $db->fetchAll($sql, $params);

                if (!empty($turmas_com_polo)) {
                    foreach ($turmas_com_polo as $turma) {
                        $turmas_vinculadas[] = $turma['nome'] . ' (anteriormente vinculada ao polo ' . ($turma['polo_nome'] ? $turma['polo_nome'] : 'ID: ' . $turma['polo_id']) . ')';
                    }
                }
            }

            // Se houver turmas já vinculadas, exibe alerta informativo
            if (!empty($turmas_vinculadas)) {
                $_SESSION['alerta'] = 'As seguintes turmas foram transferidas de outros polos: ' . implode(', ', $turmas_vinculadas);
                $_SESSION['alerta_tipo'] = 'aviso';

                // Não remove os IDs das turmas já vinculadas, permitindo a transferência
                // Todas as turmas selecionadas serão vinculadas ao polo atual
            }

            // Atualiza as turmas selecionadas para vincular ao polo
            if (!empty($turmas_ids)) {
                $sql = "UPDATE turmas SET polo_id = ? WHERE id IN (" . implode(',', array_fill(0, count($turmas_ids), '?')) . ")";
                $params = array_merge([$polo_id], $turmas_ids);
                $db->query($sql, $params);
            }

            // Confirma a transação
            $db->commit();

            $_SESSION['mensagem'] = 'Turmas vinculadas com sucesso ao polo.';
            $_SESSION['mensagem_tipo'] = 'sucesso';
        } catch (Exception $e) {
            // Desfaz a transação em caso de erro
            $db->rollBack();

            $_SESSION['mensagem'] = 'Erro ao vincular turmas ao polo: ' . $e->getMessage();
            $_SESSION['mensagem_tipo'] = 'erro';
        }

        // Adiciona um parâmetro para forçar a atualização da página e evitar cache
        $timestamp = time();

        // Redireciona para a página de visualização do polo com parâmetro de cache-busting
        header("Location: polos.php?action=visualizar&id={$polo_id}&refresh={$timestamp}");
        exit;
    } else {
        // Inclui apenas o arquivo de processamento para salvar ou excluir
        include 'views/polos/' . $action . '.php';
        exit;
    }
}

// Inicia o buffer de saída para as views
ob_start();

// Função para buscar tipos de polos
function buscarTiposPolos($db) {
    $sql = "SELECT id, nome, descricao FROM tipos_polos WHERE status = 'ativo' ORDER BY nome ASC";
    return executarConsultaAll($db, $sql);
}

// Função para buscar configurações financeiras dos tipos de polos
function buscarConfiguracoesFinanceiras($db) {
    $sql = "SELECT tpf.*, tp.nome as tipo_nome
            FROM tipos_polos_financeiro tpf
            JOIN tipos_polos tp ON tpf.tipo_polo_id = tp.id";
    $configs = executarConsultaAll($db, $sql);

    $resultado = [];
    foreach ($configs as $config) {
        $resultado[$config['tipo_polo_id']] = $config;
    }

    return $resultado;
}

// Inclui a view correspondente à ação
switch ($action) {
    case 'novo':
        // Exibe o formulário para cadastro de novo polo com tipos
        $titulo_pagina = 'Novo Polo';
        include 'views/polos/novo_com_tipos.php';
        break;

    case 'editar_mec':
        // Exibe o formulário para edição do campo MEC do polo
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        if (!$id) {
            $_SESSION['mensagem'] = 'ID do polo não informado.';
            $_SESSION['mensagem_tipo'] = 'erro';
            header('Location: polos.php');
            exit;
        }

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
                $_SESSION['mensagem'] = 'Erro ao adicionar coluna mec na tabela polos: ' . $e->getMessage();
                $_SESSION['mensagem_tipo'] = 'erro';
                header('Location: polos.php');
                exit;
            }
        }

        // Busca os dados do polo
        $sql = "SELECT * FROM polos WHERE id = ?";
        $polo = executarConsulta($db, $sql, [$id]);

        if (!$polo) {
            $_SESSION['mensagem'] = 'Polo não encontrado.';
            $_SESSION['mensagem_tipo'] = 'erro';
            header('Location: polos.php');
            exit;
        }

        $titulo_pagina = 'Editar Nome MEC do Polo';
        include 'views/polos/editar_mec.php';
        break;

    case 'salvar_mec':
        // Salva o campo MEC do polo
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $mec = isset($_POST['mec']) ? trim($_POST['mec']) : '';

        if (!$id) {
            $_SESSION['mensagem'] = 'ID do polo não informado.';
            $_SESSION['mensagem_tipo'] = 'erro';
            header('Location: polos.php');
            exit;
        }

        if (empty($mec)) {
            $_SESSION['mensagem'] = 'O nome MEC do polo é obrigatório.';
            $_SESSION['mensagem_tipo'] = 'erro';
            header("Location: polos.php?action=editar_mec&id={$id}");
            exit;
        }

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
                $_SESSION['mensagem'] = 'Erro ao adicionar coluna mec na tabela polos: ' . $e->getMessage();
                $_SESSION['mensagem_tipo'] = 'erro';
                header('Location: polos.php');
                exit;
            }
        }

        // Atualiza o campo mec do polo
        try {
            $db->update('polos', ['mec' => $mec], 'id = ?', [$id]);

            $_SESSION['mensagem'] = 'Nome MEC do polo atualizado com sucesso.';
            $_SESSION['mensagem_tipo'] = 'sucesso';
            header('Location: polos.php?action=visualizar&id=' . $id);
            exit;
        } catch (Exception $e) {
            $_SESSION['mensagem'] = 'Erro ao atualizar o nome MEC do polo: ' . $e->getMessage();
            $_SESSION['mensagem_tipo'] = 'erro';
            header("Location: polos.php?action=editar_mec&id={$id}");
            exit;
        }
        break;

    case 'editar':
        // Exibe o formulário para edição de polo com tipos
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        if (!$id) {
            $_SESSION['mensagem'] = 'ID do polo não informado.';
            $_SESSION['mensagem_tipo'] = 'erro';
            header('Location: polos.php');
            exit;
        }

        // Busca os dados do polo
        $sql = "SELECT * FROM polos WHERE id = ?";
        $polo = executarConsulta($db, $sql, [$id]);

        if (!$polo) {
            $_SESSION['mensagem'] = 'Polo não encontrado.';
            $_SESSION['mensagem_tipo'] = 'erro';
            header('Location: polos.php');
            exit;
        }

        // Busca os tipos de polo associados
        $sql = "SELECT tipo_polo_id FROM polos_tipos WHERE polo_id = ?";
        $tipos_polo_result = executarConsultaAll($db, $sql, [$id]);
        $tipos_polo_selecionados = array_column($tipos_polo_result, 'tipo_polo_id');

        // Removidas as consultas relacionadas ao financeiro

        $titulo_pagina = 'Editar Polo';
        include 'views/polos/editar_com_tipos.php';
        break;

    case 'financeiro':
        // Exibe as informações financeiras do polo
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        if (!$id) {
            $_SESSION['mensagem'] = 'ID do polo não informado.';
            $_SESSION['mensagem_tipo'] = 'erro';
            header('Location: polos.php');
            exit;
        }

        $titulo_pagina = 'Financeiro do Polo';
        include 'views/polos/editar_financeiro_novo.php';
        break;

    case 'editar_financeiro':
        // Redirecionar para o novo formato
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id) {
            header('Location: polos.php?action=editar_financeiro_novo&id=' . $id);
        } else {
            $_SESSION['mensagem'] = 'ID do polo não informado.';
            $_SESSION['mensagem_tipo'] = 'erro';
            header('Location: polos.php');
        }
        exit;
        break;

    case 'editar_financeiro_novo':
        // Exibe o formulário para edição das novas informações financeiras do polo
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        if (!$id) {
            $_SESSION['mensagem'] = 'ID do polo não informado.';
            $_SESSION['mensagem_tipo'] = 'erro';
            header('Location: polos.php');
            exit;
        }

        $titulo_pagina = 'Editar Informações Financeiras do Polo';
        include 'views/polos/editar_financeiro_novo.php';
        break;

    case 'visualizar':
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        if (!$id) {
            $_SESSION['mensagem'] = 'ID do polo não informado.';
            $_SESSION['mensagem_tipo'] = 'erro';
            header('Location: polos.php');
            exit;
        }

        // Verifica se o polo tem tipos associados
        $sql = "SELECT COUNT(*) as total FROM polos_tipos WHERE polo_id = ?";
        $result = executarConsulta($db, $sql, [$id]);

        if ($result && $result['total'] == 0) {
            $_SESSION['mensagem'] = 'Este polo não possui tipos associados. Por favor, edite o polo e selecione pelo menos um tipo.';
            $_SESSION['mensagem_tipo'] = 'aviso';
        }

        include 'views/polos/visualizar.php';
        break;

    case 'listar':
    default:
        include 'views/polos/listar.php';
        break;
}

// Captura o conteúdo da view
$conteudo = ob_get_clean();
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faciência ERP - <?php echo $titulo_pagina; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
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
                <?php echo $conteudo; ?>
            </main>

            <!-- Footer -->
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>

    <script src="js/main.js"></script>
</body>
</html>
