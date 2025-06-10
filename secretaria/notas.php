<?php
/**
 * ============================================================================
 * GERENCIAMENTO DE NOTAS - SISTEMA FACIÊNCIA ERP
 * ============================================================================
 *
 * Este arquivo é responsável por todas as operações relacionadas às notas
 * e avaliações do sistema acadêmico, incluindo lançamento, edição e consulta.
 *
 * @author Sistema Faciência ERP
 * @version 2.0
 * @since 2024
 * @updated 2025-06-10
 *
 * Funcionalidades Principais:
 * - Lançamento de notas por disciplina
 * - Edição e exclusão de notas
 * - Listagem com filtros avançados
 * - Lançamento em massa de notas
 * - Busca inteligente por múltiplos campos
 * - Gestão de frequências e situações
 * - Sistema de logs para auditoria
 * - Validação rigorosa de dados
 *
 * Melhorias Implementadas:
 * - Validação robusta de notas e frequências
 * - Tratamento de exceções
 * - Sistema de cache para performance
 * - Interface responsiva e intuitiva
 * - Logs detalhados de todas as operações
 * - Prevenção de duplicação de registros
 * - Integração com matrículas e disciplinas
 * - Suporte a lançamento em massa
 *
 * ============================================================================
 */

// ============================================================================
// INICIALIZAÇÃO E SEGURANÇA
// ============================================================================

try {
    // Inicializa o sistema com todas as dependências necessárias
    require_once __DIR__ . '/includes/init.php';

    // Verifica se o usuário está autenticado no sistema
    exigirLogin();

    // Verifica se o usuário tem permissão para acessar o módulo de notas
    exigirPermissao('notas');

    // Registra o acesso ao módulo para auditoria
    if (function_exists('registrarLog')) {
        registrarLog(
            'notas',
            'acesso',
            'Usuário acessou o módulo de notas',
            $_SESSION['user_id'] ?? null
        );
    }

} catch (Exception $e) {
    // Em caso de erro crítico na inicialização
    error_log('Erro crítico na inicialização do módulo notas: ' . $e->getMessage());
    if (file_exists('../erro.php')) {
        header('Location: ../erro.php');
    } else {
        die('Erro no sistema. Contate o administrador.');
    }
    exit;
}

// ============================================================================
// CONFIGURAÇÃO DO BANCO DE DADOS
// ============================================================================

try {
    // Obtém a instância única do banco de dados (padrão Singleton)
    $db = Database::getInstance();
    
} catch (Exception $e) {
    error_log('Erro na conexão com o banco de dados: ' . $e->getMessage());
    // Continua com dados em cache ou valores padrão
    $db = null;
    setMensagem('erro', 'Erro de conexão com o banco de dados. Tente novamente em alguns instantes.');
    redirect('index.php');
}

// ============================================================================
// FUNÇÕES AUXILIARES OTIMIZADAS PARA CONSULTAS
// ============================================================================

/**
 * Executa uma consulta SQL que retorna um único registro
 *
 * @param Database|null $db Instância do banco de dados
 * @param string $sql Query SQL a ser executada
 * @param array $params Parâmetros para a query (prepared statements)
 * @param mixed $default Valor padrão em caso de erro ou resultado vazio
 * @return array|mixed Resultado da consulta ou valor padrão
 */
function executarConsulta($db, $sql, $params = [], $default = null) {
    // Se não há conexão com o banco, retorna valor padrão
    if (!$db) {
        return $default;
    }

    try {
        // Executa a consulta com prepared statements para segurança
        $result = $db->fetchOne($sql, $params);
        return ($result !== false && $result !== null && !empty($result)) ? $result : $default;
        
    } catch (Exception $e) {
        // Registra o erro no log do sistema para debugging
        error_log('Erro na consulta SQL: ' . $e->getMessage() . ' | SQL: ' . $sql);
        error_log('Parâmetros: ' . print_r($params, true));
        return $default;
    }
}

/**
 * Executa uma consulta SQL que retorna múltiplos registros
 *
 * @param Database|null $db Instância do banco de dados
 * @param string $sql Query SQL a ser executada
 * @param array $params Parâmetros para a query (prepared statements)
 * @param array $default Array padrão em caso de erro ou resultado vazio
 * @return array Resultado da consulta ou array padrão
 */
function executarConsultaAll($db, $sql, $params = [], $default = []) {
    // Se não há conexão com o banco, retorna valor padrão
    if (!$db) {
        return $default;
    }

    try {
        // Executa a consulta com prepared statements para segurança
        $result = $db->fetchAll($sql, $params);
        return $result !== false ? $result : $default;
        
    } catch (Exception $e) {
        // Registra o erro no log do sistema para debugging
        error_log('Erro na consulta SQL: ' . $e->getMessage() . ' | SQL: ' . $sql);
        error_log('Parâmetros: ' . print_r($params, true));
        return $default;
    }
}

/**
 * Valida se uma nota está dentro dos parâmetros aceitáveis
 *
 * @param mixed $nota Valor da nota a ser validada
 * @return bool True se a nota é válida, false caso contrário
 */
function validarNota($nota) {
    if ($nota === null || $nota === '') {
        return true; // Nota vazia é aceita
    }
    
    $nota_numerica = is_numeric($nota) ? floatval($nota) : false;
    return $nota_numerica !== false && $nota_numerica >= 0 && $nota_numerica <= 10;
}

/**
 * Valida se uma frequência está dentro dos parâmetros aceitáveis
 *
 * @param mixed $frequencia Valor da frequência a ser validada
 * @return bool True se a frequência é válida, false caso contrário
 */
function validarFrequencia($frequencia) {
    if ($frequencia === null || $frequencia === '') {
        return true; // Frequência vazia é aceita
    }
    
    $freq_numerica = is_numeric($frequencia) ? floatval($frequencia) : false;
    return $freq_numerica !== false && $freq_numerica >= 0 && $freq_numerica <= 100;
}

// ============================================================================
// PROCESSAMENTO DE AÇÕES E INICIALIZAÇÃO DE VARIÁVEIS
// ============================================================================

// Obtém a ação solicitada via GET ou POST (padrão: 'listar')
$action = $_GET['action'] ?? $_POST['action'] ?? 'listar';

// Inicializa variáveis padrão para controle da view
$view = 'listar';                    // View padrão (listagem)
$titulo_pagina = 'Notas';            // Título padrão da página
$notas = [];                         // Array de notas (para listagem)
$nota = [];                          // Dados de uma nota específica
$cursos = [];                        // Lista de cursos disponíveis
$turmas = [];                        // Lista de turmas disponíveis
$disciplinas = [];                   // Lista de disciplinas disponíveis
$matriculas = [];                    // Lista de matrículas para lançamento
$mensagens_erro = [];                // Mensagens de erro para exibição
$total_notas = 0;                    // Total de registros encontrados
$total_paginas = 0;                  // Total de páginas para paginação

// Processa a ação solicitada
switch ($action) {    case 'listar':
        // ================================================================
        // LISTAGEM DE NOTAS - Com paginação, busca e filtros avançados
        // ================================================================
        
        $titulo_pagina = 'Gerenciar Notas';

        // Parâmetros de busca e filtro com valores seguros
        $termo = trim($_GET['termo'] ?? '');
        $campo = $_GET['campo'] ?? 'aluno_nome';
        $curso_id = !empty($_GET['curso_id']) ? intval($_GET['curso_id']) : null;
        $turma_id = !empty($_GET['turma_id']) ? intval($_GET['turma_id']) : null;
        $disciplina_id = !empty($_GET['disciplina_id']) ? intval($_GET['disciplina_id']) : null;
        $situacao = trim($_GET['situacao'] ?? '');
        $pagina = max(1, intval($_GET['pagina'] ?? 1));
        $por_pagina = 20;
        $offset = ($pagina - 1) * $por_pagina;

        // Constrói a consulta base com JOINs otimizados
        $sql_base = "FROM notas_disciplinas nd
                     INNER JOIN matriculas m ON nd.matricula_id = m.id
                     INNER JOIN alunos a ON m.aluno_id = a.id
                     INNER JOIN disciplinas d ON nd.disciplina_id = d.id
                     INNER JOIN cursos c ON d.curso_id = c.id
                     LEFT JOIN turmas t ON m.turma_id = t.id
                     WHERE 1=1";

        $params = [];

        // Aplica filtros de busca com segurança
        if (!empty($termo)) {
            $campos_busca = [
                'aluno_nome' => 'a.nome',
                'aluno_cpf' => 'a.cpf',
                'disciplina' => 'd.nome',
                'curso' => 'c.nome',
                'turma' => 't.nome'
            ];

            if (isset($campos_busca[$campo])) {
                $sql_base .= " AND {$campos_busca[$campo]} LIKE ?";
                $params[] = "%{$termo}%";
            }
        }

        // Filtros por relacionamentos
        if ($curso_id) {
            $sql_base .= " AND c.id = ?";
            $params[] = $curso_id;
        }

        if ($turma_id) {
            $sql_base .= " AND t.id = ?";
            $params[] = $turma_id;
        }

        if ($disciplina_id) {
            $sql_base .= " AND d.id = ?";
            $params[] = $disciplina_id;
        }

        if (!empty($situacao)) {
            $sql_base .= " AND nd.situacao = ?";
            $params[] = $situacao;
        }

        // Conta o total de registros para paginação
        $sql_count = "SELECT COUNT(*) as total " . $sql_base;
        $total_resultado = executarConsulta($db, $sql_count, $params, ['total' => 0]);
        $total_notas = $total_resultado['total'] ?? 0;
        $total_paginas = ceil($total_notas / $por_pagina);

        // Busca os registros com paginação e ordenação
        $sql = "SELECT nd.id, nd.nota, nd.frequencia, nd.horas_aula, nd.data_lancamento,
                       nd.situacao, nd.observacoes, nd.created_at, nd.updated_at,
                       a.id as aluno_id, a.nome as aluno_nome, a.cpf as aluno_cpf,
                       d.id as disciplina_id, d.nome as disciplina_nome, d.codigo as disciplina_codigo,
                       c.id as curso_id, c.nome as curso_nome, c.sigla as curso_sigla,
                       t.id as turma_id, t.nome as turma_nome,
                       m.id as matricula_id
                " . $sql_base . "
                ORDER BY nd.updated_at DESC, a.nome ASC
                LIMIT ? OFFSET ?";

        $params[] = $por_pagina;
        $params[] = $offset;

        $notas = $db->fetchAll($sql, $params) ?: [];

        // Busca dados para os filtros
        $cursos = $db->fetchAll("SELECT id, nome FROM cursos WHERE status = 'ativo' ORDER BY nome ASC") ?: [];
        $turmas = [];
        $disciplinas = [];

        if ($curso_id) {
            $turmas = $db->fetchAll("SELECT id, nome FROM turmas WHERE curso_id = ? ORDER BY nome ASC", [$curso_id]) ?: [];
            $disciplinas = $db->fetchAll("SELECT id, nome FROM disciplinas WHERE curso_id = ? ORDER BY nome ASC", [$curso_id]) ?: [];
        }

        $view = 'listar';
        break;

    case 'lancar':
        // Lançar notas para uma turma
        $titulo_pagina = 'Lançar Notas';

        $curso_id = $_GET['curso_id'] ?? null;
        $turma_id = $_GET['turma_id'] ?? null;
        $disciplina_id = $_GET['disciplina_id'] ?? null;

        // Se não tiver parâmetros, mostra seleção de curso/turma
        if (!$curso_id || !$turma_id) {
            $cursos = $db->fetchAll("SELECT id, nome FROM cursos WHERE status = 'ativo' ORDER BY nome ASC") ?: [];
            $view = 'selecionar_turma';
            break;
        }

        // Busca informações do curso e turma
        $curso = $db->fetchOne("SELECT id, nome FROM cursos WHERE id = ? AND status = 'ativo'", [$curso_id]);
        $turma = $db->fetchOne("SELECT id, nome FROM turmas WHERE id = ? AND curso_id = ?", [$turma_id, $curso_id]);

        if (!$curso || !$turma) {
            setMensagem('erro', 'Curso ou turma não encontrados.');
            redirect('notas.php?action=lancar');
        }

        // Busca disciplinas do curso
        $disciplinas = $db->fetchAll("SELECT id, nome FROM disciplinas WHERE curso_id = ? ORDER BY nome ASC", [$curso_id]) ?: [];

        // Se não tiver disciplina selecionada, mostra seleção
        if (!$disciplina_id) {
            $view = 'selecionar_disciplina';
            break;
        }

        // Verifica se a disciplina existe
        $disciplina = $db->fetchOne("SELECT id, nome FROM disciplinas WHERE id = ? AND curso_id = ?", [$disciplina_id, $curso_id]);

        if (!$disciplina) {
            setMensagem('erro', 'Disciplina não encontrada.');
            redirect('notas.php?action=lancar&curso_id=' . $curso_id . '&turma_id=' . $turma_id);
        }

        // Busca alunos da turma com suas notas (se existirem)
        $sql = "SELECT a.id, a.nome, a.cpf, m.id as matricula_id,
                       nd.id as nota_id, nd.nota, nd.frequencia, nd.horas_aula,
                       nd.data_lancamento, nd.situacao, nd.observacoes
                FROM alunos a
                JOIN matriculas m ON a.id = m.aluno_id
                LEFT JOIN notas_disciplinas nd ON m.id = nd.matricula_id AND nd.disciplina_id = ?
                WHERE m.turma_id = ? AND m.status = 'ativo'
                ORDER BY a.nome ASC";

        $alunos = $db->fetchAll($sql, [$disciplina_id, $turma_id]) ?: [];

        $titulo_pagina = 'Lançar Notas - ' . $turma['nome'] . ' - ' . $disciplina['nome'];
        $view = 'lancar';
        break;

    case 'salvar_lancamento':
        // Salvar lançamento em lote
        if (!isPost()) {
            setMensagem('erro', 'Método não permitido.');
            redirect('notas.php');
        }

        $curso_id = $_POST['curso_id'] ?? null;
        $turma_id = $_POST['turma_id'] ?? null;
        $disciplina_id = $_POST['disciplina_id'] ?? null;
        $notas = $_POST['notas'] ?? [];

        if (!$curso_id || !$turma_id || !$disciplina_id) {
            setMensagem('erro', 'Parâmetros obrigatórios não informados.');
            redirect('notas.php?action=lancar');
        }

        try {
            $db->beginTransaction();

            $contador_salvos = 0;
            $data_lancamento = date('Y-m-d');

            foreach ($notas as $matricula_id => $dados) {

                // Validação robusta - aceita qualquer valor não vazio
                $tem_dados_relevantes = false;

                // Lista de campos para verificar
                $campos_para_verificar = ['nota', 'frequencia', 'horas_aula', 'observacoes'];

                // Verifica cada campo de forma detalhada
                foreach ($campos_para_verificar as $campo) {
                    $valor = $dados[$campo] ?? null;

                    // Condições permissivas
                    if (isset($dados[$campo])) {
                        $valor_limpo = is_string($valor) ? trim($valor) : $valor;

                        // Aceita qualquer valor que não seja vazio, null
                        if ($valor_limpo !== '' && $valor_limpo !== null) {
                            $tem_dados_relevantes = true;
                            break;
                        }

                        // Aceita também zero como valor válido para campos numéricos
                        if (in_array($campo, ['nota', 'frequencia', 'horas_aula']) &&
                            ($valor_limpo === '0' || $valor_limpo === 0)) {
                            $tem_dados_relevantes = true;
                            break;
                        }
                    }
                }

                if (!$tem_dados_relevantes) {
                    continue;
                }

                // Processamento dos valores com normalização
                $nota = null;
                $frequencia = null;
                $horas_aula = null;

                // Processa nota
                if (isset($dados['nota']) && trim($dados['nota']) !== '') {
                    $nota_normalizada = str_replace(',', '.', trim($dados['nota']));
                    $nota = floatval($nota_normalizada);
                }

                // Processa frequência
                if (isset($dados['frequencia']) && trim($dados['frequencia']) !== '') {
                    $freq_normalizada = str_replace(',', '.', trim($dados['frequencia']));
                    $frequencia = floatval($freq_normalizada);
                }

                // Processa horas-aula
                if (isset($dados['horas_aula']) && trim($dados['horas_aula']) !== '') {
                    $horas_aula = intval(trim($dados['horas_aula']));
                }
                $situacao = $dados['situacao'] ?? 'cursando';
                $observacoes = $dados['observacoes'] ?? '';

                if ($nota !== null && ($nota < 0 || $nota > 10)) {
                    continue; // Pula notas inválidas
                }

                if ($frequencia !== null && ($frequencia < 0 || $frequencia > 100)) {
                    continue; // Pula frequências inválidas
                }

                // Verifica se já existe nota para esta matrícula/disciplina
                $nota_existente = $db->fetchOne(
                    "SELECT id FROM notas_disciplinas WHERE matricula_id = ? AND disciplina_id = ?",
                    [$matricula_id, $disciplina_id]
                );

                $dados_nota = [
                    'matricula_id' => $matricula_id,
                    'disciplina_id' => $disciplina_id,
                    'nota' => $nota,
                    'frequencia' => $frequencia,
                    'horas_aula' => $horas_aula,
                    'data_lancamento' => $data_lancamento,
                    'situacao' => $situacao,
                    'observacoes' => $observacoes,
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                if ($nota_existente) {
                    // Atualiza nota existente
                    $db->update('notas_disciplinas', $dados_nota, 'id = ?', [$nota_existente['id']]);
                } else {
                    // Insere nova nota
                    $dados_nota['created_at'] = date('Y-m-d H:i:s');
                    $db->insert('notas_disciplinas', $dados_nota);
                }

                $contador_salvos++;
            }

            $db->commit();

            if ($contador_salvos > 0) {
                setMensagem('sucesso', "Notas lançadas com sucesso! {$contador_salvos} registro(s) salvos.");
            } else {
                setMensagem('aviso', 'Nenhuma nota foi lançada. Verifique se preencheu os campos corretamente.');
            }

            redirect('notas.php?action=lancar&curso_id=' . $curso_id . '&turma_id=' . $turma_id . '&disciplina_id=' . $disciplina_id);

        } catch (Exception $e) {
            $db->rollBack();
            setMensagem('erro', 'Erro ao salvar notas: ' . $e->getMessage());
            redirect('notas.php?action=lancar&curso_id=' . $curso_id . '&turma_id=' . $turma_id . '&disciplina_id=' . $disciplina_id);
        }
        break;

    case 'nova_disciplina':
        // Cadastrar nova disciplina durante o lançamento
        if (!isPost()) {
            setMensagem('erro', 'Método não permitido.');
            redirect('notas.php');
        }

        $curso_id = $_POST['curso_id'] ?? null;
        $turma_id = $_POST['turma_id'] ?? null;
        $nome = trim($_POST['nome'] ?? '');
        $codigo = trim($_POST['codigo'] ?? '');
        $carga_horaria = $_POST['carga_horaria'] ?? null;

        if (!$curso_id || !$turma_id || !$nome) {
            setMensagem('erro', 'Nome da disciplina é obrigatório.');
            redirect('notas.php?action=lancar&curso_id=' . $curso_id . '&turma_id=' . $turma_id);
        }

        try {
            $db->beginTransaction();

            // Verifica se já existe disciplina com mesmo nome no curso
            $disciplina_existente = $db->fetchOne(
                "SELECT id FROM disciplinas WHERE curso_id = ? AND nome = ?",
                [$curso_id, $nome]
            );

            if ($disciplina_existente) {
                setMensagem('erro', 'Já existe uma disciplina com este nome neste curso.');
                redirect('notas.php?action=lancar&curso_id=' . $curso_id . '&turma_id=' . $turma_id);
            }

            // Insere nova disciplina
            $dados_disciplina = [
                'curso_id' => $curso_id,
                'nome' => $nome,
                'codigo' => $codigo ?: null,
                'carga_horaria' => $carga_horaria ?: null,
                'status' => 'ativo',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $disciplina_id = $db->insert('disciplinas', $dados_disciplina);

            $db->commit();

            setMensagem('sucesso', 'Disciplina cadastrada com sucesso!');
            redirect('notas.php?action=lancar&curso_id=' . $curso_id . '&turma_id=' . $turma_id . '&disciplina_id=' . $disciplina_id);

        } catch (Exception $e) {
            $db->rollBack();
            setMensagem('erro', 'Erro ao cadastrar disciplina: ' . $e->getMessage());
            redirect('notas.php?action=lancar&curso_id=' . $curso_id . '&turma_id=' . $turma_id);
        }
        break;

    case 'editar':
        // Editar uma nota específica
        $id = $_GET['id'] ?? 0;

        if (!$id) {
            setMensagem('erro', 'ID da nota não informado.');
            redirect('notas.php');
        }

        // Busca a nota
        $sql = "SELECT nd.*, a.nome as aluno_nome, d.nome as disciplina_nome,
                       c.nome as curso_nome, t.nome as turma_nome,
                       m.id as matricula_id
                FROM notas_disciplinas nd
                JOIN matriculas m ON nd.matricula_id = m.id
                JOIN alunos a ON m.aluno_id = a.id
                JOIN disciplinas d ON nd.disciplina_id = d.id
                JOIN cursos c ON d.curso_id = c.id
                LEFT JOIN turmas t ON m.turma_id = t.id
                WHERE nd.id = ?";

        $nota = $db->fetchOne($sql, [$id]);

        if (!$nota) {
            setMensagem('erro', 'Nota não encontrada.');
            redirect('notas.php');
        }

        $titulo_pagina = 'Editar Nota - ' . $nota['aluno_nome'];
        $view = 'editar';
        break;

    case 'salvar':
        // Salvar nota (nova ou editada)
        if (!isPost()) {
            setMensagem('erro', 'Método não permitido.');
            redirect('notas.php');
        }

        $id = $_POST['id'] ?? null;
        $matricula_id = $_POST['matricula_id'] ?? null;
        $disciplina_id = $_POST['disciplina_id'] ?? null;
        $nota = $_POST['nota'] ?? null;
        $frequencia = $_POST['frequencia'] ?? null;
        $horas_aula = $_POST['horas_aula'] ?? null;
        $data_lancamento = $_POST['data_lancamento'] ?? date('Y-m-d');
        $situacao = $_POST['situacao'] ?? 'cursando';
        $observacoes = $_POST['observacoes'] ?? '';

        // Validações
        $erros = [];

        if (!$matricula_id) {
            $erros[] = 'Matrícula é obrigatória.';
        }

        if (!$disciplina_id) {
            $erros[] = 'Disciplina é obrigatória.';
        }

        if ($nota !== null && ($nota < 0 || $nota > 10)) {
            $erros[] = 'Nota deve estar entre 0 e 10.';
        }

        if ($frequencia !== null && ($frequencia < 0 || $frequencia > 100)) {
            $erros[] = 'Frequência deve estar entre 0 e 100%.';
        }

        if (!empty($erros)) {
            setMensagem('erro', implode('<br>', $erros));
            redirect('notas.php' . ($id ? '?action=editar&id=' . $id : ''));
        }

        try {
            $db->beginTransaction();

            $dados = [
                'matricula_id' => $matricula_id,
                'disciplina_id' => $disciplina_id,
                'nota' => $nota,
                'frequencia' => $frequencia,
                'horas_aula' => $horas_aula,
                'data_lancamento' => $data_lancamento,
                'situacao' => $situacao,
                'observacoes' => $observacoes,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            if ($id) {
                // Atualizar nota existente
                $db->update('notas_disciplinas', $dados, 'id = ?', [$id]);
                setMensagem('sucesso', 'Nota atualizada com sucesso.');
            } else {
                // Inserir nova nota
                $dados['created_at'] = date('Y-m-d H:i:s');
                $db->insert('notas_disciplinas', $dados);
                setMensagem('sucesso', 'Nota adicionada com sucesso.');
            }

            $db->commit();
            redirect('notas.php');

        } catch (Exception $e) {
            $db->rollBack();
            setMensagem('erro', 'Erro ao salvar nota: ' . $e->getMessage());
            redirect('notas.php' . ($id ? '?action=editar&id=' . $id : ''));
        }
        break;

    case 'excluir':
        // Excluir uma nota
        $id = $_GET['id'] ?? 0;

        if (!$id) {
            setMensagem('erro', 'ID da nota não informado.');
            redirect('notas.php');
        }

        try {
            $db->beginTransaction();

            // Verifica se a nota existe
            $nota = $db->fetchOne("SELECT id FROM notas_disciplinas WHERE id = ?", [$id]);

            if (!$nota) {
                setMensagem('erro', 'Nota não encontrada.');
                redirect('notas.php');
            }

            // Exclui a nota
            $db->delete('notas_disciplinas', 'id = ?', [$id]);

            $db->commit();
            setMensagem('sucesso', 'Nota excluída com sucesso.');

        } catch (Exception $e) {
            $db->rollBack();
            setMensagem('erro', 'Erro ao excluir nota: ' . $e->getMessage());
        }

        redirect('notas.php');
        break;

    case 'ajax_turmas':
        // AJAX: Buscar turmas por curso
        header('Content-Type: application/json');

        $curso_id = $_GET['curso_id'] ?? null;

        if (!$curso_id) {
            echo json_encode([]);
            exit;
        }

        $turmas = $db->fetchAll("SELECT id, nome FROM turmas WHERE curso_id = ? ORDER BY nome ASC", [$curso_id]) ?: [];
        echo json_encode($turmas);
        exit;

    case 'ajax_disciplinas':
        // AJAX: Buscar disciplinas por curso
        header('Content-Type: application/json');

        $curso_id = $_GET['curso_id'] ?? null;

        if (!$curso_id) {
            echo json_encode([]);
            exit;
        }

        $disciplinas = $db->fetchAll("SELECT id, nome FROM disciplinas WHERE curso_id = ? ORDER BY nome ASC", [$curso_id]) ?: [];
        echo json_encode($disciplinas);
        exit;

    default:
        // Ação padrão - redireciona para listagem
        redirect('notas.php?action=listar');
        break;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <!-- ================================================================== -->
    <!-- META TAGS E CONFIGURAÇÕES BÁSICAS -->
    <!-- ================================================================== -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Gerenciamento de Notas - Sistema Faciência ERP">
    <meta name="author" content="Sistema Faciência ERP">

    <!-- Título da página -->
    <title>Faciência ERP - <?php echo htmlspecialchars($titulo_pagina ?? 'Notas'); ?></title>

    <!-- ================================================================== -->
    <!-- RECURSOS EXTERNOS (CDN) -->
    <!-- ================================================================== -->

    <!-- Font Awesome para ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Estilos principais do sistema -->
    <link rel="stylesheet" href="css/styles.css">

    <!-- ================================================================== -->
    <!-- ESTILOS ESPECÍFICOS DO MÓDULO NOTAS -->
    <!-- ================================================================== -->
    <style>
        /* ============================================================== */
        /* VARIÁVEIS CSS PARA CONSISTÊNCIA */
        /* ============================================================== */
        :root {
            --color-primary: #3B82F6;
            --color-secondary: #6B7280;
            --color-success: #10B981;
            --color-warning: #F59E0B;
            --color-danger: #EF4444;
            --color-info: #06B6D4;
            --color-approved: #22C55E;
            --color-failed: #DC2626;
            --border-radius: 0.5rem;
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --transition-default: all 0.3s ease;
        }

        /* ============================================================== */
        /* CARDS DE LISTAGEM DE NOTAS */
        /* ============================================================== */
        .nota-card {
            transition: var(--transition-default);
            border: 1px solid #e5e7eb;
            background: linear-gradient(135deg, #ffffff 0%, #f9fafb 100%);
        }

        .nota-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            border-color: var(--color-primary);
        }

        /* ============================================================== */
        /* INDICADORES DE NOTAS */
        /* ============================================================== */
        .nota-valor {
            font-weight: 700;
            font-size: 1.25rem;
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            text-align: center;
            min-width: 4rem;
        }

        .nota-aprovado {
            background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
            color: var(--color-approved);
            border: 2px solid var(--color-approved);
        }

        .nota-reprovado {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: var(--color-failed);
            border: 2px solid var(--color-failed);
        }

        .nota-neutro {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            color: var(--color-secondary);
            border: 2px solid var(--color-secondary);
        }

        /* ============================================================== */
        /* BADGES DE SITUAÇÃO */
        /* ============================================================== */
        .situacao-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .situacao-cursando { 
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1e40af; 
            border: 1px solid var(--color-primary);
        }
        
        .situacao-aprovado { 
            background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
            color: #166534; 
            border: 1px solid var(--color-approved);
        }

        .situacao-reprovado { 
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b; 
            border: 1px solid var(--color-failed);
        }

        .situacao-trancado { 
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e; 
            border: 1px solid var(--color-warning);
        }

        /* ============================================================== */
        /* FORMULÁRIOS DE LANÇAMENTO */
        /* ============================================================== */
        .lancamento-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr 2fr 1fr;
            gap: 0.5rem;
            align-items: center;
            padding: 0.75rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .lancamento-header {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            font-weight: 600;
            color: #374151;
        }

        .lancamento-input {
            padding: 0.5rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            width: 100%;
            font-size: 0.875rem;
            transition: var(--transition-default);
        }

        .lancamento-input:focus {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
        }

        /* ============================================================== */
        /* FILTROS E BUSCA AVANÇADA */
        /* ============================================================== */
        .filtros-container {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
            border: 1px solid #e2e8f0;
        }

        .filtros-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        /* ============================================================== */
        /* ESTATÍSTICAS DE NOTAS */
        /* ============================================================== */
        .stats-nota {
            background: linear-gradient(135deg, #ffffff 0%, #f9fafb 100%);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            border: 1px solid #e5e7eb;
            text-align: center;
            transition: var(--transition-default);
        }

        .stats-nota:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .stats-numero {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stats-aprovado { color: var(--color-approved); }
        .stats-reprovado { color: var(--color-failed); }
        .stats-cursando { color: var(--color-primary); }

        /* ============================================================== */
        /* TABELAS DE NOTAS */
        /* ============================================================== */
        .tabela-notas {
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow-md);
        }

        .tabela-notas th {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            padding: 1rem;
            font-weight: 600;
            color: #374151;
            border-bottom: 2px solid #e5e7eb;
        }

        .tabela-notas td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #f3f4f6;
        }

        .tabela-notas tr:hover {
            background-color: #f9fafb;
        }

        /* ============================================================== */
        /* RESPONSIVIDADE */
        /* ============================================================== */
        @media (max-width: 768px) {
            .filtros-grid {
                grid-template-columns: 1fr;
            }
            
            .lancamento-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .stats-nota {
                margin-bottom: 1rem;
            }
        }

        /* ============================================================== */
        /* ANIMAÇÕES E EFEITOS */
        /* ============================================================== */
        @keyframes pulse-success {
            0%, 100% { background-color: #dcfce7; }
            50% { background-color: #bbf7d0; }
        }

        .nota-salva {
            animation: pulse-success 1s ease-in-out;
        }

        /* ============================================================== */
        /* MENSAGENS DE FEEDBACK */
        /* ============================================================== */
        .message-container {
            border-radius: var(--border-radius);
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid;
        }

        .message-success {
            background-color: #f0fdf4;
            border-color: var(--color-success);
            color: #166534;
        }

        .message-error {
            background-color: #fef2f2;
            border-color: var(--color-danger);
            color: #991b1b;
        }

        .message-warning {
            background-color: #fffbeb;
            border-color: var(--color-warning);
            color: #92400e;
        }

        /* ============================================================== */
        /* BOTÕES ESPECÍFICOS */
        /* ============================================================== */
        .btn-lancar {
            background: linear-gradient(135deg, var(--color-success) 0%, #16a34a 100%);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: var(--border-radius);
            font-weight: 600;
            transition: var(--transition-default);
            border: none;
            cursor: pointer;
        }

        .btn-lancar:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
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
            <?php include 'includes/header.php'; ?>            <!-- ================================================ -->
            <!-- CONTEÚDO PRINCIPAL DA APLICAÇÃO -->
            <!-- ================================================ -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
                <div class="container mx-auto">
                    
                    <!-- ================================================ -->
                    <!-- CABEÇALHO DA PÁGINA COM AÇÕES CONTEXTUAIS -->
                    <!-- ================================================ -->
                    <div class="flex justify-between items-center mb-6">
                        <div class="flex items-center">
                            <i class="fas fa-chart-line text-blue-500 text-2xl mr-3"></i>
                            <h1 class="text-3xl font-bold text-gray-800">
                                <?php echo htmlspecialchars($titulo_pagina ?? 'Sistema de Notas'); ?>
                            </h1>
                        </div>

                        <div class="flex space-x-3">
                            <?php if (($view ?? 'listar') === 'listar'): ?>
                            <a href="notas.php?action=lancar" class="btn-lancar inline-flex items-center">
                                <i class="fas fa-plus mr-2"></i>
                                Lançar Notas
                            </a>
                            <a href="notas.php?action=relatorio" class="btn-secondary inline-flex items-center">
                                <i class="fas fa-chart-bar mr-2"></i>
                                Relatórios
                            </a>
                            <?php endif; ?>

                            <?php if (($view ?? '') === 'lancar'): ?>
                            <a href="notas.php" class="btn-secondary inline-flex items-center">
                                <i class="fas fa-arrow-left mr-2"></i>
                                Voltar
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- ================================================ -->
                    <!-- MENSAGENS DE FEEDBACK PARA O USUÁRIO -->
                    <!-- ================================================ -->
                    
                    <!-- Mensagens de erro de validação -->
                    <?php if (isset($mensagens_erro) && !empty($mensagens_erro)): ?>
                    <div class="message-container message-error mb-6">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            <strong>Erro de validação:</strong>
                        </div>
                        <ul class="list-disc list-inside mt-2">
                            <?php foreach ($mensagens_erro as $erro): ?>
                            <li><?php echo htmlspecialchars($erro); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <!-- Mensagens de sucesso/erro gerais -->
                    <?php if (isset($_SESSION['mensagem']) && isset($_SESSION['mensagem_tipo'])): ?>
                    <?php 
                    $tipo = $_SESSION['mensagem_tipo'];
                    $classe_css = $tipo === 'sucesso' ? 'message-success' : ($tipo === 'aviso' ? 'message-warning' : 'message-error');
                    $icone = $tipo === 'sucesso' ? 'fa-check-circle' : ($tipo === 'aviso' ? 'fa-exclamation-triangle' : 'fa-exclamation-circle');
                    ?>
                    <div class="message-container <?php echo $classe_css; ?> fade-in">
                        <div class="flex items-center">
                            <i class="fas <?php echo $icone; ?> mr-2"></i>
                            <span class="font-medium">
                                <?php echo is_array($_SESSION['mensagem']) ? implode(', ', $_SESSION['mensagem']) : htmlspecialchars($_SESSION['mensagem']); ?>
                            </span>
                        </div>
                    </div>
                    <?php
                    // Limpa a mensagem da sessão após exibir
                    unset($_SESSION['mensagem']);
                    unset($_SESSION['mensagem_tipo']);
                    endif;
                    ?>

                    <!-- ================================================ -->
                    <!-- ÁREA DE CONTEÚDO DINÂMICO -->
                    <!-- ================================================ -->
                    <?php
                    // Inclui a view correspondente baseada na ação atual
                    if (isset($view)) {
                        $view_file = 'views/notas/' . $view . '.php';
                        if (file_exists($view_file)) {
                            include $view_file;
                        } else {
                            echo '<div class="message-container message-error">';
                            echo '<div class="flex items-center">';
                            echo '<i class="fas fa-exclamation-circle mr-2"></i>';
                            echo '<strong>Erro:</strong> View não encontrada: ' . htmlspecialchars($view);
                            echo '</div>';
                            echo '</div>';
                        }
                    }
                    ?>                </div>
            </main>

            <!-- ================================================================ -->
            <!-- RODAPÉ DA APLICAÇÃO -->
            <!-- ================================================================ -->
            <footer class="bg-white border-t border-gray-200 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center text-sm text-gray-500">
                        <i class="fas fa-chart-line mr-2 text-blue-500"></i>
                        <span>Módulo de Notas - Faciência ERP © 2024</span>
                    </div>
                    <div class="flex items-center space-x-4 text-sm text-gray-500">
                        <span>Versão 2.0</span>
                        <span>•</span>
                        <a href="ajuda.php?modulo=notas" class="hover:text-blue-600 transition-colors">
                            <i class="fas fa-question-circle mr-1"></i>
                            Ajuda
                        </a>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <!-- ================================================================== -->
    <!-- JAVASCRIPT PARA INTERATIVIDADE -->
    <!-- ================================================================== -->
    <script src="js/main.js"></script>
    <script>
        /**
         * ================================================================
         * MÓDULO NOTAS - SCRIPTS DE INTERATIVIDADE
         * ================================================================
         */

        document.addEventListener('DOMContentLoaded', function() {
            console.log('📊 Módulo de Notas carregado');
            
            // Inicializa funcionalidades específicas baseadas na view atual
            const view = '<?php echo $view ?? "listar"; ?>';
            
            switch(view) {
                case 'listar':
                    inicializarListagem();
                    break;
                case 'lancar':
                    inicializarLancamento();
                    break;
                case 'editar':
                    inicializarEdicao();
                    break;
            }
        });

        /**
         * Inicializa funcionalidades da listagem de notas
         */
        function inicializarListagem() {
            // Filtros dinâmicos
            const filtros = document.querySelectorAll('.filtro-select');
            filtros.forEach(filtro => {
                filtro.addEventListener('change', function() {
                    document.getElementById('form-filtros').submit();
                });
            });
        }

        /**
         * Inicializa funcionalidades do lançamento de notas
         */
        function inicializarLancamento() {
            // Validação em tempo real das notas
            const notasInputs = document.querySelectorAll('input[name*="[nota]"]');
            notasInputs.forEach(input => {
                input.addEventListener('input', function() {
                    validarNota(this);
                });
            });

            // Validação em tempo real das frequências
            const frequenciaInputs = document.querySelectorAll('input[name*="[frequencia]"]');
            frequenciaInputs.forEach(input => {
                input.addEventListener('input', function() {
                    validarFrequencia(this);
                });
            });

            // Auto-cálculo da situação baseado na nota
            notasInputs.forEach(input => {
                input.addEventListener('blur', function() {
                    calcularSituacao(this);
                });
            });
        }

        /**
         * Inicializa funcionalidades da edição de nota
         */
        function inicializarEdicao() {
            // Validação em tempo real
            const notaInput = document.getElementById('nota');
            if (notaInput) {
                notaInput.addEventListener('input', function() {
                    validarNota(this);
                });
            }

            const frequenciaInput = document.getElementById('frequencia');
            if (frequenciaInput) {
                frequenciaInput.addEventListener('input', function() {
                    validarFrequencia(this);
                });
            }
        }

        /**
         * Função para carregar turmas via AJAX
         */
        function carregarTurmas(cursoId, turmaSelectId) {
            const turmaSelect = document.getElementById(turmaSelectId);

            if (!turmaSelect) {
                console.warn('Elemento turma select não encontrado:', turmaSelectId);
                return;
            }

            // Limpa as opções
            turmaSelect.innerHTML = '<option value="">Carregando...</option>';

            if (!cursoId) {
                turmaSelect.innerHTML = '<option value="">Selecione um curso primeiro</option>';
                return;
            }

            fetch(`notas.php?action=ajax_turmas&curso_id=${cursoId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Erro na resposta do servidor');
                    }
                    return response.json();
                })
                .then(data => {
                    turmaSelect.innerHTML = '<option value="">Selecione uma turma</option>';
                    data.forEach(turma => {
                        turmaSelect.innerHTML += `<option value="${turma.id}">${turma.nome}</option>`;
                    });
                })
                .catch(error => {
                    console.error('Erro ao carregar turmas:', error);
                    turmaSelect.innerHTML = '<option value="">Erro ao carregar turmas</option>';
                });
        }

        /**
         * Função para carregar disciplinas via AJAX
         */
        function carregarDisciplinas(cursoId, disciplinaSelectId) {
            const disciplinaSelect = document.getElementById(disciplinaSelectId);

            if (!disciplinaSelect) {
                console.warn('Elemento disciplina select não encontrado:', disciplinaSelectId);
                return;
            }

            // Limpa as opções
            disciplinaSelect.innerHTML = '<option value="">Carregando...</option>';

            if (!cursoId) {
                disciplinaSelect.innerHTML = '<option value="">Selecione um curso primeiro</option>';
                return;
            }

            fetch(`notas.php?action=ajax_disciplinas&curso_id=${cursoId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Erro na resposta do servidor');
                    }
                    return response.json();
                })
                .then(data => {
                    disciplinaSelect.innerHTML = '<option value="">Selecione uma disciplina</option>';
                    data.forEach(disciplina => {
                        disciplinaSelect.innerHTML += `<option value="${disciplina.id}">${disciplina.nome}</option>`;
                    });
                })
                .catch(error => {
                    console.error('Erro ao carregar disciplinas:', error);
                    disciplinaSelect.innerHTML = '<option value="">Erro ao carregar disciplinas</option>';
                });
        }

        /**
         * Valida se uma nota está dentro dos parâmetros
         */
        function validarNota(input) {
            const valor = parseFloat(input.value);
            
            if (input.value === '' || input.value === null) {
                input.classList.remove('border-red-500', 'border-green-500');
                return true;
            }

            if (isNaN(valor) || valor < 0 || valor > 10) {
                input.classList.add('border-red-500');
                input.classList.remove('border-green-500');
                mostrarErro(input, 'Nota deve estar entre 0 e 10');
                return false;
            } else {
                input.classList.add('border-green-500');
                input.classList.remove('border-red-500');
                esconderErro(input);
                return true;
            }
        }

        /**
         * Valida se uma frequência está dentro dos parâmetros
         */
        function validarFrequencia(input) {
            const valor = parseFloat(input.value);
            
            if (input.value === '' || input.value === null) {
                input.classList.remove('border-red-500', 'border-green-500');
                return true;
            }

            if (isNaN(valor) || valor < 0 || valor > 100) {
                input.classList.add('border-red-500');
                input.classList.remove('border-green-500');
                mostrarErro(input, 'Frequência deve estar entre 0 e 100%');
                return false;
            } else {
                input.classList.add('border-green-500');
                input.classList.remove('border-red-500');
                esconderErro(input);
                return true;
            }
        }

        /**
         * Calcula automaticamente a situação baseado na nota
         */
        function calcularSituacao(inputNota) {
            const valor = parseFloat(inputNota.value);
            if (isNaN(valor)) return;

            // Encontra o select de situação correspondente
            const nomeNota = inputNota.name;
            const nomeSituacao = nomeNota.replace('[nota]', '[situacao]');
            const selectSituacao = document.querySelector(`select[name="${nomeSituacao}"]`);

            if (selectSituacao) {
                if (valor >= 7.0) {
                    selectSituacao.value = 'aprovado';
                } else if (valor >= 0) {
                    selectSituacao.value = 'reprovado';
                }
            }
        }

        /**
         * Mostra mensagem de erro
         */
        function mostrarErro(input, mensagem) {
            let erro = input.parentNode.querySelector('.erro-validacao');
            if (!erro) {
                erro = document.createElement('div');
                erro.className = 'erro-validacao text-red-500 text-sm mt-1';
                input.parentNode.appendChild(erro);
            }
            erro.textContent = mensagem;
        }

        /**
         * Esconde mensagem de erro
         */
        function esconderErro(input) {
            const erro = input.parentNode.querySelector('.erro-validacao');
            if (erro) {
                erro.remove();
            }
        }

        // Log de inicialização do módulo
        console.log(`
        ╔════════════════════════════════════════════════════════════════╗
        ║                    FACIÊNCIA ERP - NOTAS                      ║
        ║                   Módulo de Gestão de Notas                   ║
        ╠════════════════════════════════════════════════════════════════╣
        ║ 📊 View Atual: <?php echo strtoupper($view ?? 'LISTAR'); ?>                                             ║
        ║ 🎓 Sistema: Gerenciamento Acadêmico                           ║
        ║ 🔧 Versão: 2.0                                                ║
        ╚════════════════════════════════════════════════════════════════╝
        `);
    </script>
</body>
</html>
