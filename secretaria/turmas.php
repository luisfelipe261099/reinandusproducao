<?php
/**
 * ================================================================
 *                    SISTEMA FACIÊNCIA ERP
 * ================================================================
 * 
 * Módulo: Gerenciamento de Turmas
 * Descrição: Interface principal para gerenciamento de turmas acadêmicas
 * Versão: 2.0
 * Data de Atualização: 2024-12-19
 * 
 * Funcionalidades:
 * - Dashboard com estatísticas e métricas de turmas
 * - Cadastro, edição e exclusão de turmas
 * - Visualização detalhada de turmas e matrículas
 * - Gerenciamento de status (planejada, em andamento, concluída, cancelada)
 * - Vinculação com cursos e polos
 * - Controle de cronograma e datas importantes
 * - Relatórios de performance e ocupação
 * 
 * Estrutura de Navegação:
 * - dashboard: Visão geral com estatísticas
 * - listar: Listagem paginada de turmas
 * - novo: Formulário para criar nova turma
 * - editar: Formulário para editar turma existente
 * - visualizar: Detalhes completos da turma
 * - salvar: Processamento de dados do formulário
 * - excluir: Remoção de turma (com validações)
 * - buscar: Pesquisa avançada de turmas
 * 
 * ================================================================
 */

// ================================================================
// CONFIGURAÇÕES INICIAIS E CONSTANTES
// ================================================================

// Carregamento do sistema base
require_once __DIR__ . '/includes/init.php';

// Configurações específicas do módulo
ini_set('memory_limit', '256M'); // Aumenta limite de memória para relatórios

// ================================================================
// VERIFICAÇÃO DE AUTENTICAÇÃO E PERMISSÕES
// ================================================================

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica se o usuário tem permissão para acessar o módulo de turmas
exigirPermissao('turmas');

// Registra log de acesso ao módulo de turmas
if (function_exists('registrarLog')) {
    registrarLog(
        'secretaria',
        'acesso_turmas',
        'Usuário acessou o módulo de gestão de turmas',
        null,
        null,
        null,
        [
            'user_id' => getUsuarioId(),
            'user_type' => getUsuarioTipo(),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Desconhecido',
            'timestamp' => date('Y-m-d H:i:s')
        ]
    );
}

// ================================================================
// INICIALIZAÇÃO DE COMPONENTES
// ================================================================

// Instancia o banco de dados
$db = Database::getInstance();

// Define a ação atual baseada no parâmetro GET
$action = $_GET['action'] ?? 'listar';

// Força ação 'listar' se há parâmetros de filtro na URL
if (isset($_GET['curso_id']) || isset($_GET['polo_id']) || isset($_GET['status'])) {
    $action = $_GET['action'] ?? 'listar';
}

// ================================================================
// FUNÇÕES AUXILIARES
// ================================================================

/**
 * Executa consulta SQL retornando um único registro
 * 
 * @param Database $db Instância do banco de dados
 * @param string $sql Query SQL a ser executada
 * @param array $params Parâmetros para prepared statement
 * @param mixed $default Valor padrão em caso de erro ou resultado vazio
 * @return array|mixed Resultado da consulta ou valor padrão
 */
function executarConsulta($db, $sql, $params = [], $default = null) {
    try {
        return $db->fetchOne($sql, $params);
    } catch (Exception $e) {
        // Registra o erro no log
        error_log('Erro na consulta SQL: ' . $e->getMessage());
        return $default;
    }
}

/**
 * Executa consulta SQL retornando múltiplos registros
 * 
 * @param Database $db Instância do banco de dados
 * @param string $sql Query SQL a ser executada
 * @param array $params Parâmetros para prepared statement
 * @param array $default Valor padrão em caso de erro
 * @return array Resultado da consulta ou array vazio
 */
function executarConsultaAll($db, $sql, $params = [], $default = []) {
    try {
        error_log('Executando consulta: ' . $sql);
        $result = $db->fetchAll($sql, $params);
        error_log('Resultado da consulta: ' . ($result ? count($result) . ' registros' : 'nenhum registro'));
        return $result ?: $default;
    } catch (Exception $e) {
        // Registra o erro no log
        error_log('Erro na consulta SQL: ' . $e->getMessage() . ' - SQL: ' . $sql);
        return $default;
    }
}

// ================================================================
// CONTROLADOR PRINCIPAL - PROCESSAMENTO DE AÇÕES
// ================================================================

/**
 * Roteamento principal da aplicação
 * Processa a ação solicitada e define os dados necessários para cada view
 */
switch ($action) {
    // ============================================================
    // DASHBOARD - VISÃO GERAL E ESTATÍSTICAS DE TURMAS
    // ============================================================
    case 'dashboard':
        // Define título e view
        $titulo_pagina = 'Dashboard de Turmas';
        $view = 'dashboard';

        // === VERIFICAÇÃO DE EXISTÊNCIA DAS TABELAS ===
        
        // Verifica se a tabela turmas existe
        try {
            $sql = "SHOW TABLES LIKE 'turmas'";
            $turmas_table_exists = $db->fetchOne($sql) ? true : false;
        } catch (Exception $e) {
            $turmas_table_exists = false;
            error_log('Erro ao verificar tabela turmas: ' . $e->getMessage());
        }

        // Verifica se a tabela cursos existe
        try {
            $sql = "SHOW TABLES LIKE 'cursos'";
            $cursos_table_exists = $db->fetchOne($sql) ? true : false;
        } catch (Exception $e) {
            $cursos_table_exists = false;
            error_log('Erro ao verificar tabela cursos: ' . $e->getMessage());
        }

        // Verifica se a tabela polos existe
        try {
            $sql = "SHOW TABLES LIKE 'polos'";
            $polos_table_exists = $db->fetchOne($sql) ? true : false;
        } catch (Exception $e) {
            $polos_table_exists = false;
            error_log('Erro ao verificar tabela polos: ' . $e->getMessage());
        }

        // Verifica se a tabela matriculas existe
        try {
            $sql = "SHOW TABLES LIKE 'matriculas'";
            $matriculas_table_exists = $db->fetchOne($sql) ? true : false;
        } catch (Exception $e) {
            $matriculas_table_exists = false;
            error_log('Erro ao verificar tabela matriculas: ' . $e->getMessage());
        }

        // === ESTATÍSTICAS BÁSICAS ===
        $stats = [];

        // Total de turmas
        if ($turmas_table_exists) {
            try {
                $sql = "SELECT COUNT(*) as total FROM turmas";
                $resultado = $db->fetchOne($sql);
                $stats['total_turmas'] = $resultado['total'] ?? 0;
                error_log('Total de turmas: ' . $stats['total_turmas']);
            } catch (Exception $e) {
                error_log('Erro ao contar turmas: ' . $e->getMessage());
                $stats['total_turmas'] = 0;
            }

            // === DISTRIBUIÇÃO POR STATUS ===
            try {
                $sql = "SELECT status, COUNT(*) as total FROM turmas GROUP BY status";
                $resultados = $db->fetchAll($sql);

                // Inicializa contadores de status
                $stats['turmas_planejadas'] = 0;
                $stats['turmas_em_andamento'] = 0;
                $stats['turmas_concluidas'] = 0;
                $stats['turmas_canceladas'] = 0;

                foreach ($resultados as $resultado) {
                    if (isset($resultado['status'])) {
                        switch ($resultado['status']) {
                            case 'planejada':
                                $stats['turmas_planejadas'] = $resultado['total'];
                                break;
                            case 'em_andamento':
                                $stats['turmas_em_andamento'] = $resultado['total'];
                                break;
                            case 'concluida':
                                $stats['turmas_concluidas'] = $resultado['total'];
                                break;
                            case 'cancelada':
                                $stats['turmas_canceladas'] = $resultado['total'];
                                break;
                        }
                    }
                }

                error_log('Turmas por status: planejadas=' . $stats['turmas_planejadas'] .
                          ', em_andamento=' . $stats['turmas_em_andamento'] .
                          ', concluidas=' . $stats['turmas_concluidas'] .
                          ', canceladas=' . $stats['turmas_canceladas']);
            } catch (Exception $e) {
                error_log('Erro ao buscar turmas por status: ' . $e->getMessage());
            }
        } else {
            $stats['total_turmas'] = 0;
            $stats['turmas_planejadas'] = 0;
            $stats['turmas_em_andamento'] = 0;
            $stats['turmas_concluidas'] = 0;
            $stats['turmas_canceladas'] = 0;
        }

        // === TOTAL DE ALUNOS MATRICULADOS ===
        if ($matriculas_table_exists) {
            $sql = "SELECT COUNT(DISTINCT aluno_id) as total FROM matriculas";            $resultado = executarConsulta($db, $sql);
            $stats['total_alunos_matriculados'] = $resultado['total'] ?? 0;
        } else {
            $stats['total_alunos_matriculados'] = 0;
        }

        // === TURMAS POR POLO ===
        if ($turmas_table_exists && $polos_table_exists) {
            try {
                $sql = "SELECT p.nome as polo_nome, COUNT(t.id) as total_turmas
                        FROM turmas t
                        JOIN polos p ON t.polo_id = p.id
                        GROUP BY p.id, p.nome
                        ORDER BY total_turmas DESC
                        LIMIT 5";
                $turmas_por_polo = $db->fetchAll($sql);
                error_log('Turmas por polo encontradas: ' . count($turmas_por_polo));
            } catch (Exception $e) {
                error_log('Erro ao buscar turmas por polo: ' . $e->getMessage());
                $turmas_por_polo = [];
            }
        } else {
            $turmas_por_polo = [];
            error_log('Tabelas turmas ou polos não existem');
        }

        // Não cria dados fictícios - usa dados reais apenas
        if (empty($turmas_por_polo)) {
            error_log('Nenhuma turma por polo encontrada no banco de dados');
            $turmas_por_polo = [];
        }

        // === TURMAS POR CURSO ===
        if ($turmas_table_exists && $cursos_table_exists) {
            try {
                $sql = "SELECT c.nome as curso_nome, COUNT(t.id) as total_turmas
                        FROM turmas t
                        JOIN cursos c ON t.curso_id = c.id
                        GROUP BY c.id, c.nome
                        ORDER BY total_turmas DESC
                        LIMIT 5";
                $turmas_por_curso = $db->fetchAll($sql);
                error_log('Turmas por curso encontradas: ' . count($turmas_por_curso));
            } catch (Exception $e) {
                error_log('Erro ao buscar turmas por curso: ' . $e->getMessage());
                $turmas_por_curso = [];
            }
        } else {
            $turmas_por_curso = [];
            error_log('Tabelas turmas ou cursos não existem');
        }

        // Não cria dados fictícios - usa dados reais apenas
        if (empty($turmas_por_curso)) {
            error_log('Nenhuma turma por curso encontrada no banco de dados');
            $turmas_por_curso = [];
        }

        // === MATRÍCULAS POR MÊS (ÚLTIMOS 6 MESES) ===
        if ($matriculas_table_exists) {
            try {
                // Verifica se a coluna created_at existe
                $sql = "SHOW COLUMNS FROM matriculas LIKE 'created_at'";
                $coluna_exists = $db->fetchOne($sql) ? true : false;

                if ($coluna_exists) {
                    $sql = "SELECT
                            DATE_FORMAT(created_at, '%Y-%m') as mes,
                            DATE_FORMAT(created_at, '%b/%Y') as mes_nome,
                            COUNT(*) as total
                        FROM matriculas
                        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                        GROUP BY mes, mes_nome
                        ORDER BY mes ASC";
                    $matriculas_por_mes = $db->fetchAll($sql);
                    error_log('Matrículas por mês encontradas: ' . count($matriculas_por_mes));
                } else {
                    $matriculas_por_mes = [];
                    error_log('Coluna created_at não existe na tabela matriculas');
                }
            } catch (Exception $e) {
                error_log('Erro ao buscar matrículas por mês: ' . $e->getMessage());
                $matriculas_por_mes = [];
            }
        } else {
            $matriculas_por_mes = [];
            error_log('Tabela matriculas não existe');
        }

        // Não cria dados fictícios - usa dados reais apenas
        if (empty($matriculas_por_mes)) {
            error_log('Nenhuma matrícula por mês encontrada no banco de dados');
            $matriculas_por_mes = [];
        }

        // === TURMAS RECENTES ===
        if ($turmas_table_exists) {
            try {
                // Verifica se a coluna created_at existe
                $sql = "SHOW COLUMNS FROM turmas LIKE 'created_at'";
                $coluna_exists = $db->fetchOne($sql) ? true : false;

                if ($coluna_exists) {
                    $sql = "SELECT t.*,
                           c.nome as curso_nome,
                           po.nome as polo_nome,
                           (SELECT COUNT(*) FROM matriculas m WHERE m.turma_id = t.id) as total_alunos
                    FROM turmas t
                    LEFT JOIN cursos c ON t.curso_id = c.id
                    LEFT JOIN polos po ON t.polo_id = po.id
                    ORDER BY t.created_at DESC
                    LIMIT 5";
                } else {
                    // Se não existir a coluna created_at, ordena por ID
                    $sql = "SELECT t.*,
                           c.nome as curso_nome,
                           po.nome as polo_nome,
                           (SELECT COUNT(*) FROM matriculas m WHERE m.turma_id = t.id) as total_alunos
                    FROM turmas t
                    LEFT JOIN cursos c ON t.curso_id = c.id                    LEFT JOIN polos po ON t.polo_id = po.id
                    ORDER BY t.id DESC
                    LIMIT 5";
                }

                $turmas_recentes = $db->fetchAll($sql);
                error_log('Turmas recentes encontradas: ' . count($turmas_recentes));

                // Fallback para consulta mais simples se necessário
                if (empty($turmas_recentes)) {
                    $sql = "SELECT * FROM turmas LIMIT 5";
                    $turmas_recentes = $db->fetchAll($sql);
                    error_log('Turmas recentes (consulta simples): ' . count($turmas_recentes));
                }
            } catch (Exception $e) {
                error_log('Erro ao buscar turmas recentes: ' . $e->getMessage());
                $turmas_recentes = [];
            }
        } else {
            $turmas_recentes = [];
            error_log('Tabela turmas não existe');
        }

        // Não cria dados fictícios - usa dados reais apenas
        if (empty($turmas_recentes)) {
            error_log('Nenhuma turma encontrada no banco de dados');
            $turmas_recentes = [];
        }

        // === CARREGAMENTO DE DADOS AUXILIARES ===
        
        // Carrega os cursos para o filtro
        if ($cursos_table_exists) {
            $sql = "SELECT id, nome FROM cursos ORDER BY nome ASC";
            $cursos = executarConsultaAll($db, $sql);
        } else {
            $cursos = [];
        }

        // Carrega os polos para o filtro
        if ($polos_table_exists) {
            $sql = "SELECT id, nome FROM polos ORDER BY nome ASC";
            $polos = executarConsultaAll($db, $sql);
        } else {
            $polos = [];
        }
        break;    // ============================================================
    // NOVA TURMA - FORMULÁRIO DE CRIAÇÃO
    // ============================================================
    case 'nova':
        $titulo_pagina = 'Nova Turma';
        $view = 'form';
        $turma = []; // Inicializa uma turma vazia

        // === PRÉ-SELEÇÃO DE CURSO ===
        if (isset($_GET['curso_id'])) {
            $turma['curso_id'] = $_GET['curso_id'];

            // Busca o curso para exibir informações
            $sql = "SELECT * FROM cursos WHERE id = ?";
            $curso = $db->fetchOne($sql, [$turma['curso_id']]);
            error_log('Curso encontrado: ' . ($curso ? 'Sim' : 'Não'));

            if ($curso) {
                $titulo_pagina = 'Nova Turma - ' . $curso['nome'];
            }
        }

        // === PRÉ-SELEÇÃO DE POLO ===
        if (isset($_GET['polo_id'])) {
            $turma['polo_id'] = $_GET['polo_id'];
            error_log('Polo ID pré-selecionado: ' . $turma['polo_id']);

            // Busca o polo para exibir informações
            $sql = "SELECT * FROM polos WHERE id = ?";
            $polo = $db->fetchOne($sql, [$turma['polo_id']]);
            error_log('Polo encontrado: ' . ($polo ? 'Sim' : 'Não'));

            if ($polo) {
                $titulo_pagina = 'Nova Turma - Polo ' . $polo['nome'];
            }
        }

        // === CARREGAMENTO DE DADOS AUXILIARES ===
        
        // Carrega os cursos para o formulário
        $sql = "SELECT id, nome FROM cursos ORDER BY nome ASC";
        $cursos = $db->fetchAll($sql) ?: [];
        error_log('Cursos carregados para o formulário: ' . count($cursos));

        // Carrega os professores/usuários para o formulário
        try {
            $sql = "SELECT id, nome FROM usuarios WHERE tipo IN ('professor', 'admin', 'coordenador') AND status = 'ativo' ORDER BY nome ASC";
            $professores = $db->fetchAll($sql) ?: [];
            error_log('Professores/usuários encontrados: ' . count($professores));
        } catch (Exception $e) {
            error_log('Erro ao buscar professores/usuários: ' . $e->getMessage());
            $professores = [];
        }

        // Carrega os polos para o formulário
        $sql = "SELECT id, nome FROM polos ORDER BY nome ASC";
        $polos = $db->fetchAll($sql) ?: [];        error_log('Polos carregados para o formulário: ' . count($polos));
        break;

    // ============================================================
    // EDITAR TURMA - FORMULÁRIO DE EDIÇÃO
    // ============================================================
    case 'editar':
        // Exibe o formulário para editar uma turma existente
        $id = $_GET['id'] ?? 0;

        // Busca a turma pelo ID
        $sql = "SELECT * FROM turmas WHERE id = ?";
        $turma = $db->fetchOne($sql, [$id]);
        error_log('Turma encontrada: ' . ($turma ? 'Sim' : 'Não'));

        if (!$turma) {
            // Turma não encontrada, redireciona para a listagem
            setMensagem('erro', 'Turma não encontrada.');
            redirect('turmas.php');
        }

        // Carrega os cursos para o formulário - usando consulta direta
        $sql = "SELECT id, nome FROM cursos ORDER BY nome ASC";
        $cursos = $db->fetchAll($sql) ?: [];
        error_log('Cursos carregados para o formulário (editar - consulta direta): ' . count($cursos));

        // Carrega os professores/usuários para o formulário
        try {
            // Busca usuários que podem ser professores coordenadores
            $sql = "SELECT id, nome FROM usuarios WHERE tipo IN ('professor', 'admin', 'coordenador') AND status = 'ativo' ORDER BY nome ASC";
            $professores = $db->fetchAll($sql) ?: [];
            error_log('Professores/usuários encontrados (editar): ' . count($professores));
        } catch (Exception $e) {
            error_log('Erro ao buscar professores/usuários (editar): ' . $e->getMessage());
            $professores = [];
        }

        // Carrega os polos para o formulário - usando consulta direta
        $sql = "SELECT id, nome FROM polos ORDER BY nome ASC";
        $polos = $db->fetchAll($sql) ?: [];
        error_log('Polos carregados para o formulário (editar - consulta direta): ' . count($polos));

        // Busca o curso para exibir informações
        if (!empty($turma['curso_id'])) {
            $sql = "SELECT * FROM cursos WHERE id = ?";
            $curso = $db->fetchOne($sql, [$turma['curso_id']]);
            error_log('Curso encontrado (editar): ' . ($curso ? 'Sim' : 'Não'));
        }

        $titulo_pagina = 'Editar Turma';
        $view = 'form';
        break;

    case 'salvar':
        // Salva os dados da turma (nova ou existente)
        if (!isPost()) {
            // Método não permitido
            setMensagem('erro', 'Método não permitido.');
            redirect('turmas.php');
        }

        // Obtém os dados do formulário
        $id = $_POST['id'] ?? null;
        $nome = $_POST['nome'] ?? '';
        $curso_id = $_POST['curso_id'] ?? null;
        $professor_id = $_POST['professor_id'] ?? null;
        $polo_id = $_POST['polo_id'] ?? null;
        $data_inicio = $_POST['data_inicio'] ?? null;
        $data_fim = $_POST['data_fim'] ?? null;
        $horario = $_POST['horario'] ?? '';
        $vagas = $_POST['vagas'] ?? 0;
        $turno = $_POST['turno'] ?? 'noite';
        $status = $_POST['status'] ?? 'planejada';
        $observacoes = $_POST['observacoes'] ?? '';
        $id_legado = $_POST['id_legado'] ?? '';

        // Valida os dados
        $erros = [];

        if (empty($nome)) {
            $erros[] = 'O nome é obrigatório.';
        }

        if (empty($curso_id)) {
            $erros[] = 'O curso é obrigatório.';
        }

        if (empty($polo_id)) {
            $erros[] = 'O polo é obrigatório.';
        }

        if (!empty($erros)) {
            // Há erros de validação, exibe o formulário novamente
            $titulo_pagina = $id ? 'Editar Turma' : 'Nova Turma';
            $view = 'form';
            $turma = $_POST;
            $mensagens_erro = $erros;

            // Carrega os cursos para o formulário
            $sql = "SELECT id, nome, codigo FROM cursos ORDER BY nome ASC";
            $cursos = executarConsultaAll($db, $sql);

            // Verifica se a tabela professores existe
            try {
                $sql = "SHOW TABLES LIKE 'professores'";
                $professores_table_exists = $db->fetchOne($sql) ? true : false;
            } catch (Exception $e) {
                $professores_table_exists = false;
                error_log('Erro ao verificar tabela professores: ' . $e->getMessage());
            }

            // Carrega os professores para o formulário
            if ($professores_table_exists) {
                $sql = "SELECT id, nome FROM professores ORDER BY nome ASC";
                $professores = executarConsultaAll($db, $sql);
            } else {
                $professores = [];
                error_log('Tabela professores não existe. Usando array vazio para o formulário.');
            }

            // Carrega os polos para o formulário
            $sql = "SELECT id, nome FROM polos ORDER BY nome ASC";
            $polos = executarConsultaAll($db, $sql);

            break;
        }

        // Valida o professor_coordenador_id se fornecido
        if (!empty($professor_id)) {
            try {
                // Verifica se o professor existe na tabela usuarios
                $sql = "SELECT id FROM usuarios WHERE id = ? LIMIT 1";
                $professor_existe = $db->fetchOne($sql, [$professor_id]);

                if (!$professor_existe) {
                    // Professor não existe na tabela usuarios, define como null
                    error_log("Professor ID {$professor_id} não encontrado na tabela usuarios. Definindo como null.");
                    $professor_id = null;
                }
            } catch (Exception $e) {
                error_log("Erro ao validar professor: " . $e->getMessage());
                $professor_id = null;
            }
        }

        // Prepara os dados para salvar
        $dados = [
            'nome' => $nome,
            'curso_id' => $curso_id,
            'polo_id' => $polo_id,
            'professor_coordenador_id' => $professor_id ?: null,
            'data_inicio' => $data_inicio ?: null,
            'data_fim' => $data_fim ?: null,
            'turno' => $turno,
            'vagas_total' => $vagas,
            'carga_horaria' => !empty($_POST['carga_horaria']) ? (int)$_POST['carga_horaria'] : null,
            'status' => $status,
            'observacoes' => $observacoes,
            'id_legado' => $id_legado,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        try {
            // Inicia uma transação
            $db->beginTransaction();

            if ($id) {
                // Atualiza uma turma existente
                $db->update('turmas', $dados, 'id = ?', [$id]);

                // Registra o log
                registrarLog(
                    'turmas',
                    'editar',
                    "Turma {$nome} (ID: {$id}) atualizada",
                    $id,
                    'turmas'
                );

                setMensagem('sucesso', 'Turma atualizada com sucesso.');
            } else {
                // Adiciona a data de criação
                $dados['created_at'] = date('Y-m-d H:i:s');

                // Insere uma nova turma
                $id = $db->insert('turmas', $dados);

                // Registra o log
                registrarLog(
                    'turmas',
                    'criar',
                    "Turma {$nome} (ID: {$id}) criada",
                    $id,
                    'turmas'
                );

                setMensagem('sucesso', 'Turma adicionada com sucesso.');
            }

            // Confirma a transação
            $db->commit();

            // Redireciona para a visualização da turma
            redirect('turmas.php?action=visualizar&id=' . $id);
        } catch (Exception $e) {
            // Desfaz a transação em caso de erro
            $db->rollBack();

            // Erro ao salvar
            $titulo_pagina = $id ? 'Editar Turma' : 'Nova Turma';
            $view = 'form';
            $turma = $_POST;
            $mensagens_erro = ['Erro ao salvar a turma: ' . $e->getMessage()];

            // Carrega os cursos para o formulário
            $sql = "SELECT id, nome, codigo FROM cursos ORDER BY nome ASC";
            $cursos = executarConsultaAll($db, $sql);

            // Carrega os professores/usuários para o formulário
            try {
                $sql = "SELECT id, nome FROM usuarios WHERE tipo IN ('professor', 'admin', 'coordenador') AND status = 'ativo' ORDER BY nome ASC";
                $professores = $db->fetchAll($sql) ?: [];
            } catch (Exception $e) {
                error_log('Erro ao buscar professores/usuários (erro): ' . $e->getMessage());
                $professores = [];
            }

            // Carrega os polos para o formulário
            $sql = "SELECT id, nome FROM polos ORDER BY nome ASC";
            $polos = executarConsultaAll($db, $sql);
        }
        break;

    case 'excluir':
        // Exclui uma turma
        $id = $_GET['id'] ?? 0;
        $forcar = $_GET['forcar'] ?? false; // Parâmetro para forçar exclusão desvinculando matrículas

        // Verifica se o usuário tem permissão para excluir
        exigirPermissao('turmas', 'excluir');

        // Busca a turma pelo ID
        $sql = "SELECT * FROM turmas WHERE id = ?";
        $turma = $db->fetchOne($sql, [$id]);

        if (!$turma) {
            // Turma não encontrada, redireciona para a listagem
            setMensagem('erro', 'Turma não encontrada.');
            redirect('turmas.php');
        }

        try {
            // Inicia uma transação
            $db->beginTransaction();

            // Verifica se há matrículas vinculadas à turma
            $sql = "SELECT COUNT(*) as total FROM matriculas WHERE turma_id = ?";
            $resultado = $db->fetchOne($sql, [$id]);
            $total_matriculas = $resultado['total'] ?? 0;

            if ($total_matriculas > 0 && !$forcar) {
                // Há matrículas vinculadas, mostra opção para desvincular
                $db->rollBack();

                $titulo_pagina = 'Confirmar Exclusão da Turma';
                $view = 'confirmar_exclusao';
                $turma_dados = $turma;
                $total_matriculas_vinculadas = $total_matriculas;
                break;
            }

            if ($total_matriculas > 0 && $forcar) {
                // Desvincula as matrículas (remove turma_id)
                $sql = "UPDATE matriculas SET turma_id = NULL WHERE turma_id = ?";
                $db->query($sql, [$id]);

                // Registra o log da desvinculação
                registrarLog(
                    'matriculas',
                    'desvincular',
                    "Desvinculadas {$total_matriculas} matrículas da turma {$turma['nome']} (ID: {$id})",
                    $id,
                    'turmas'
                );
            }

            // Exclui a turma
            $db->delete('turmas', 'id = ?', [$id]);

            // Registra o log
            registrarLog(
                'turmas',
                'excluir',
                "Turma {$turma['nome']} (ID: {$id}) excluída" . ($total_matriculas > 0 ? " com {$total_matriculas} matrículas desvinculadas" : ""),
                $id,
                'turmas'
            );

            // Confirma a transação
            $db->commit();

            if ($total_matriculas > 0) {
                setMensagem('sucesso', "Turma excluída com sucesso. {$total_matriculas} matrículas foram desvinculadas.");
            } else {
                setMensagem('sucesso', 'Turma excluída com sucesso.');
            }
        } catch (Exception $e) {
            // Desfaz a transação em caso de erro
            $db->rollBack();

            // Erro ao excluir
            setMensagem('erro', 'Erro ao excluir a turma: ' . $e->getMessage());
        }

        // Redireciona para a listagem
        redirect('turmas.php');
        break;

    case 'visualizar':
        // Exibe os detalhes de uma turma
        $id = $_GET['id'] ?? 0;

        // Busca a turma pelo ID
        $sql = "SELECT t.*,
                   c.nome as curso_nome,
                   u.nome as professor_nome,
                   po.nome as polo_nome
            FROM turmas t
            LEFT JOIN cursos c ON t.curso_id = c.id
            LEFT JOIN usuarios u ON t.professor_coordenador_id = u.id
            LEFT JOIN polos po ON t.polo_id = po.id
            WHERE t.id = ?";
        $turma = $db->fetchOne($sql, [$id]);
        error_log('Turma encontrada (visualizar): ' . ($turma ? 'Sim' : 'Não'));

        if (!$turma) {
            // Turma não encontrada, redireciona para a listagem
            setMensagem('erro', 'Turma não encontrada.');
            redirect('turmas.php');
        }

        // Busca os alunos matriculados na turma
        try {
            $sql = "SELECT m.id as matricula_id, m.status as matricula_status, m.created_at as data_matricula,
                       a.id as aluno_id, a.nome as aluno_nome, a.email as aluno_email, a.cpf as aluno_cpf
                FROM matriculas m
                JOIN alunos a ON m.aluno_id = a.id
                WHERE m.turma_id = ?
                ORDER BY a.nome ASC";
            $alunos = $db->fetchAll($sql, [$id]) ?: [];
            error_log('Alunos matriculados encontrados: ' . count($alunos));
        } catch (Exception $e) {
            error_log('Erro ao buscar alunos matriculados: ' . $e->getMessage());
            $alunos = [];
        }

        // Busca as disciplinas do curso
        if (!empty($turma['curso_id'])) {
            try {
                // Verifica se a tabela professores existe
                try {
                    $sql = "SHOW TABLES LIKE 'professores'";
                    $professores_table_exists = $db->fetchOne($sql) ? true : false;
                } catch (Exception $e) {
                    $professores_table_exists = false;
                    error_log('Erro ao verificar tabela professores: ' . $e->getMessage());
                }

                if ($professores_table_exists) {
                    $sql = "SELECT d.*,
                               p.nome as professor_nome
                        FROM disciplinas d
                        LEFT JOIN professores p ON d.professor_id = p.id
                        WHERE d.curso_id = ?
                        ORDER BY d.nome ASC";
                } else {
                    $sql = "SELECT d.*,
                               '' as professor_nome
                        FROM disciplinas d
                        WHERE d.curso_id = ?
                        ORDER BY d.nome ASC";
                }
                $disciplinas = $db->fetchAll($sql, [$turma['curso_id']]) ?: [];
                error_log('Disciplinas encontradas: ' . count($disciplinas));
            } catch (Exception $e) {
                error_log('Erro ao buscar disciplinas: ' . $e->getMessage());
                $disciplinas = [];
            }
        } else {
            $disciplinas = [];
        }

        $titulo_pagina = 'Detalhes da Turma';
        $view = 'visualizar';
        break;

    case 'gerar_relatorio':
        // Gera relatório Excel da turma
        $id = $_GET['id'] ?? 0;

        // Busca a turma com informações completas
        $sql = "SELECT t.*,
                       c.nome as curso_nome,
                       COALESCE(c.sigla, '') as curso_codigo,
                       po.nome as polo_nome,
                       COALESCE(po.cidade, '') as polo_cidade,
                       pr.nome as professor_nome
                FROM turmas t
                LEFT JOIN cursos c ON t.curso_id = c.id
                LEFT JOIN polos po ON t.polo_id = po.id
                LEFT JOIN usuarios pr ON t.professor_coordenador_id = pr.id
                WHERE t.id = ?";
        $turma = $db->fetchOne($sql, [$id]);

        if (!$turma) {
            setMensagem('erro', 'Turma não encontrada.');
            redirect('turmas.php');
        }

        // Busca todos os alunos matriculados com informações completas
        $sql = "SELECT m.id as matricula_id, m.numero_matricula, m.status as matricula_status,
                       m.created_at as data_matricula, m.data_inicio, m.data_fim,
                       a.id as aluno_id, a.nome as aluno_nome, a.email as aluno_email,
                       a.cpf as aluno_cpf, a.rg as aluno_rg, a.data_nascimento,
                       a.telefone, a.celular, a.endereco, a.cidade, a.estado, a.cep,
                       a.sexo, a.status as aluno_status
                FROM matriculas m
                JOIN alunos a ON m.aluno_id = a.id
                WHERE m.turma_id = ?
                ORDER BY a.nome ASC";
        $alunos = $db->fetchAll($sql, [$id]) ?: [];

        // Gera o arquivo Excel
        require_once 'includes/relatorio_turma_excel.php';
        gerarRelatorioTurmaExcel($turma, $alunos);
        exit;
        break;

    case 'buscar':
        // Busca turmas por termo
        $termo = $_GET['termo'] ?? '';
        $campo = $_GET['campo'] ?? 'nome';
        $status = $_GET['status'] ?? 'todos';
        $curso_id = $_GET['curso_id'] ?? null;
        $polo_id = $_GET['polo_id'] ?? null;

        // Trata valores vazios como null
        if (empty($curso_id)) $curso_id = null;
        if (empty($polo_id)) $polo_id = null;

        if (empty($termo) && empty($curso_id) && empty($polo_id)) {
            redirect('turmas.php');
        }

        // Define os campos permitidos para busca
        $campos_permitidos = ['nome', 'codigo', 'id_legado'];

        if (!in_array($campo, $campos_permitidos)) {
            $campo = 'nome';
        }

        // Monta a consulta SQL
        $where = [];
        $params = [];

        if (!empty($termo)) {
            $where[] = "t.{$campo} LIKE ?";
            $params[] = "%{$termo}%";
        }

        if ($status !== 'todos') {
            $where[] = "t.status = ?";
            $params[] = $status;
        }

        if (!empty($curso_id)) {
            $where[] = "t.curso_id = ?";
            $params[] = $curso_id;
        }

        if (!empty($polo_id)) {
            $where[] = "t.polo_id = ?";
            $params[] = $polo_id;
        }

        // Monta a cláusula WHERE
        $whereClause = "WHERE " . implode(" AND ", $where);

        // Consulta principal
        $sql = "SELECT t.*,
                       c.nome as curso_nome,
                       po.nome as polo_nome,
                       (SELECT COUNT(*) FROM matriculas m WHERE m.turma_id = t.id) as total_alunos
                FROM turmas t
                LEFT JOIN cursos c ON t.curso_id = c.id
                LEFT JOIN polos po ON t.polo_id = po.id
                {$whereClause}
                ORDER BY t.nome ASC";
        $turmas = executarConsultaAll($db, $sql, $params);

        // Carrega os cursos para o filtro
        $sql = "SELECT id, nome FROM cursos ORDER BY nome ASC";
        $cursos = executarConsultaAll($db, $sql);

        // Carrega os polos para o filtro
        $sql = "SELECT id, nome FROM polos ORDER BY nome ASC";
        $polos = executarConsultaAll($db, $sql);

        $titulo_pagina = 'Resultado da Busca';
        $view = 'listar';
        break;

    case 'listar':
    default:
        // Lista todas as turmas
        $status = $_GET['status'] ?? 'todos';
        $curso_id = $_GET['curso_id'] ?? null;
        $polo_id = $_GET['polo_id'] ?? null;
        $pagina = $_GET['pagina'] ?? 1;
        $por_pagina = 20;
        $offset = ($pagina - 1) * $por_pagina;

        // Trata valores vazios como null
        if (empty($curso_id)) $curso_id = null;
        if (empty($polo_id)) $polo_id = null;

        // Monta a consulta SQL
        $where = [];
        $params = [];

        if ($status !== 'todos') {
            $where[] = "t.status = ?";
            $params[] = $status;
        }

        if (!empty($curso_id)) {
            $where[] = "t.curso_id = ?";
            $params[] = $curso_id;
        }

        if (!empty($polo_id)) {
            $where[] = "t.polo_id = ?";
            $params[] = $polo_id;
        }

        // Monta a cláusula WHERE
        $whereClause = '';
        if (!empty($where)) {
            $whereClause = "WHERE " . implode(" AND ", $where);
        }

        // Verifica se as tabelas existem
        try {
            $sql = "SHOW TABLES LIKE 'turmas'";
            $turmas_table_exists = $db->fetchOne($sql) ? true : false;
        } catch (Exception $e) {
            $turmas_table_exists = false;
            error_log('Erro ao verificar tabela turmas: ' . $e->getMessage());
        }

        try {
            $sql = "SHOW TABLES LIKE 'cursos'";
            $cursos_table_exists = $db->fetchOne($sql) ? true : false;
        } catch (Exception $e) {
            $cursos_table_exists = false;
            error_log('Erro ao verificar tabela cursos: ' . $e->getMessage());
        }

        try {
            $sql = "SHOW TABLES LIKE 'polos'";
            $polos_table_exists = $db->fetchOne($sql) ? true : false;
        } catch (Exception $e) {
            $polos_table_exists = false;
            error_log('Erro ao verificar tabela polos: ' . $e->getMessage());
        }

        // Consulta principal
        if ($turmas_table_exists && $cursos_table_exists && $polos_table_exists) {
            try {
                $sql = "SELECT t.*,
                           c.nome as curso_nome,
                           po.nome as polo_nome,
                           (SELECT COUNT(*) FROM matriculas m WHERE m.turma_id = t.id) as total_alunos
                    FROM turmas t
                    LEFT JOIN cursos c ON t.curso_id = c.id
                    LEFT JOIN polos po ON t.polo_id = po.id
                    {$whereClause}
                    ORDER BY t.nome ASC
                    LIMIT {$offset}, {$por_pagina}";
                $turmas = executarConsultaAll($db, $sql, $params);

                // Conta o total de turmas
                $sql = "SELECT COUNT(*) as total
                        FROM turmas t
                        {$whereClause}";
                $resultado = executarConsulta($db, $sql, $params);
                $total_turmas = $resultado['total'] ?? 0;
            } catch (Exception $e) {
                error_log('Erro na consulta principal: ' . $e->getMessage());
                $turmas = [];
                $total_turmas = 0;
            }
        } else {
            $turmas = [];
            $total_turmas = 0;
        }

        // Calcula o total de páginas
        $total_paginas = ceil($total_turmas / $por_pagina);

        // Carrega os cursos para o filtro
        if ($cursos_table_exists) {
            $sql = "SELECT id, nome FROM cursos ORDER BY nome ASC";
            $cursos = executarConsultaAll($db, $sql);
        } else {
            $cursos = [];
        }

        // Carrega os polos para o filtro
        if ($polos_table_exists) {
            $sql = "SELECT id, nome FROM polos ORDER BY nome ASC";
            $polos = executarConsultaAll($db, $sql);
        } else {
            $polos = [];
        }

        $titulo_pagina = 'Turmas';
        $view = 'listar';
        break;
}
?>
<!DOCTYPE html>
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
                <div class="container mx-auto">
                    <div class="flex justify-between items-center mb-6">
                        <h1 class="text-2xl font-bold text-gray-800"><?php echo $titulo_pagina; ?></h1>

                        <div class="flex space-x-2">
                            <?php if ($view === 'dashboard'): ?>
                            <a href="turmas.php?action=listar" class="btn-secondary">
                                <i class="fas fa-list mr-2"></i> Ver Listagem
                            </a>
                            <?php endif; ?>

                            <?php if ($view === 'listar'): ?>
                            <a href="turmas.php" class="btn-secondary">
                                <i class="fas fa-chart-bar mr-2"></i> Ver Dashboard
                            </a>
                            <?php endif; ?>

                            <?php if ($view === 'listar' || $view === 'dashboard'): ?>
                            <a href="turmas.php?action=nova" class="btn-primary">
                                <i class="fas fa-plus mr-2"></i> Nova Turma
                            </a>
                            <?php endif; ?>

                            <?php if ($view === 'visualizar'): ?>
                            <a href="turmas.php?action=gerar_relatorio&id=<?php echo $turma['id']; ?>" class="btn-success">
                                <i class="fas fa-file-excel mr-2"></i> Relatório
                            </a>
                            <a href="turmas.php?action=editar&id=<?php echo $turma['id']; ?>" class="btn-secondary">
                                <i class="fas fa-edit mr-2"></i> Editar
                            </a>
                            <a href="javascript:void(0);" onclick="confirmarExclusao(<?php echo $turma['id']; ?>, '<?php echo addslashes($turma['nome']); ?>')" class="btn-danger">
                                <i class="fas fa-trash mr-2"></i> Excluir
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (isset($mensagens_erro) && !empty($mensagens_erro)): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
                        <ul class="list-disc list-inside">
                            <?php foreach ($mensagens_erro as $erro): ?>
                            <li><?php echo $erro; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['mensagem'])): ?>
                    <div class="bg-<?php echo $_SESSION['mensagem_tipo'] === 'sucesso' ? 'green' : 'red'; ?>-100 border-l-4 border-<?php echo $_SESSION['mensagem_tipo'] === 'sucesso' ? 'green' : 'red'; ?>-500 text-<?php echo $_SESSION['mensagem_tipo'] === 'sucesso' ? 'green' : 'red'; ?>-700 p-4 mb-6">
                        <?php echo $_SESSION['mensagem']; ?>
                    </div>
                    <?php
                    // Limpa a mensagem da sessão
                    unset($_SESSION['mensagem']);
                    unset($_SESSION['mensagem_tipo']);
                    endif;
                    ?>

                    <?php
                    // Inclui a view correspondente
                    switch ($view) {
                        case 'form':
                            include 'views/turmas/form.php';
                            break;
                        case 'visualizar':
                            include 'views/turmas/visualizar.php';
                            break;
                        case 'dashboard':
                            include 'views/turmas/dashboard.php';
                            break;
                        case 'confirmar_exclusao':
                            include 'views/turmas/confirmar_exclusao.php';
                            break;
                        case 'listar':
                        default:
                            include 'views/turmas/listar.php';
                            break;
                    }
                    ?>
                </div>
            </main>

            <!-- Footer -->
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>

    <!-- Modal de Confirmação de Exclusão -->
    <div id="modal-exclusao" class="fixed z-10 inset-0 overflow-y-auto hidden">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-exclamation-triangle text-red-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Confirmar Exclusão
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500" id="modal-message">
                                    Tem certeza que deseja excluir esta turma?
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <a href="#" id="btn-confirmar-exclusao" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Confirmar
                    </a>
                    <button type="button" onclick="fecharModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script>
        function confirmarExclusao(id, nome) {
            console.log('Função confirmarExclusao chamada com ID:', id, 'Nome:', nome);

            const modalMessage = document.getElementById('modal-message');
            const btnConfirmar = document.getElementById('btn-confirmar-exclusao');
            const modal = document.getElementById('modal-exclusao');

            if (!modalMessage) {
                console.error('Elemento modal-message não encontrado!');
                return;
            }

            if (!btnConfirmar) {
                console.error('Elemento btn-confirmar-exclusao não encontrado!');
                return;
            }

            if (!modal) {
                console.error('Elemento modal-exclusao não encontrado!');
                return;
            }

            modalMessage.textContent = `Tem certeza que deseja excluir a turma "${nome}"?`;
            btnConfirmar.href = `turmas.php?action=excluir&id=${id}`;
            modal.classList.remove('hidden');

            console.log('Modal aberto para exclusão da turma ID:', id);
        }

        function fecharModal() {
            const modal = document.getElementById('modal-exclusao');
            if (modal) {
                modal.classList.add('hidden');
            }
        }

        // Debug: verifica se os elementos existem quando a página carrega
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM carregado');

            const modalMessage = document.getElementById('modal-message');
            const btnConfirmar = document.getElementById('btn-confirmar-exclusao');
            const modal = document.getElementById('modal-exclusao');

            console.log('Modal message encontrado:', !!modalMessage);
            console.log('Botão confirmar encontrado:', !!btnConfirmar);
            console.log('Modal encontrado:', !!modal);
        });
    </script>
</body>
</html>
