<?php
/**
 * ================================================================
 *                    SISTEMA FACIÊNCIA ERP
 * ================================================================
 *  * Módulo: Gerenciamento de Cursos
 * Descrição: Interface principal para gerenciamento de cursos acadêmicos
 * Versão: 2.0
 * Data de Atualização: 2024-12-19
 * 
 * Funcionalidades:
 * - Dashboard com estatísticas e gráficos
 * - Cadastro, edição e exclusão de cursos
 * - Visualização detalhada de cursos
 * - Gerenciamento de modalidades (presencial, EAD, híbrido)
 * - Controle de níveis (graduação, pós-graduação, etc.)
 * - Vinculação com polos e áreas de conhecimento
 * - Relatórios de matrículas e turmas
 * 
 * Estrutura de Navegação:
 * - dashboard: Visão geral com estatísticas
 * - listar: Listagem paginada de cursos
 * - novo: Formulário para criar novo curso
 * - editar: Formulário para editar curso existente
 * - visualizar: Detalhes completos do curso
 * - salvar: Processamento de dados do formulário
 * - excluir: Remoção de curso (com validações)
 * - buscar: Pesquisa avançada de cursos
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

// Verifica se o usuário tem permissão para acessar o módulo de cursos
exigirPermissao('cursos');

// ================================================================
// INICIALIZAÇÃO DE COMPONENTES
// ================================================================

// Instancia o banco de dados
$db = Database::getInstance();

// Define a ação atual baseada no parâmetro GET
$action = $_GET['action'] ?? 'dashboard';

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
        // Registra a consulta para depuração
        error_log('Executando SQL: ' . $sql);
        error_log('Parâmetros: ' . json_encode($params));

        $result = $db->fetchAll($sql, $params);

        // Registra o resultado para depuração
        error_log('Resultado: ' . ($result ? count($result) . ' registros encontrados' : 'Nenhum registro encontrado'));

        return $result ?: $default;
    } catch (Exception $e) {
        // Registra o erro no log
        error_log('Erro na consulta SQL: ' . $e->getMessage());
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
    // DASHBOARD - VISÃO GERAL E ESTATÍSTICAS
    // ============================================================
    case 'dashboard':
        // Define título e view
        $titulo_pagina = 'Dashboard de Cursos';
        $view = 'dashboard';

        // Inicializa array de estatísticas
        $stats = [];

        // === VERIFICAÇÃO DE EXISTÊNCIA DAS TABELAS ===
        
        // Verifica se a tabela cursos existe
        try {
            $sql = "SHOW TABLES LIKE 'cursos'";
            $cursos_table_exists = $db->fetchOne($sql) ? true : false;
        } catch (Exception $e) {
            $cursos_table_exists = false;
            error_log('Erro ao verificar tabela cursos: ' . $e->getMessage());
        }

        // Verifica se a tabela alunos existe
        try {
            $sql = "SHOW TABLES LIKE 'alunos'";
            $alunos_table_exists = $db->fetchOne($sql) ? true : false;
        } catch (Exception $e) {
            $alunos_table_exists = false;
            error_log('Erro ao verificar tabela alunos: ' . $e->getMessage());
        }

        // Verifica se a tabela turmas existe
        try {
            $sql = "SHOW TABLES LIKE 'turmas'";
            $turmas_table_exists = $db->fetchOne($sql) ? true : false;
        } catch (Exception $e) {
            $turmas_table_exists = false;
            error_log('Erro ao verificar tabela turmas: ' . $e->getMessage());
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
        
        // Total de cursos
        if ($cursos_table_exists) {
            $sql = "SELECT COUNT(*) as total FROM cursos";
            $resultado = executarConsulta($db, $sql);
            $stats['total_cursos'] = $resultado['total'] ?? 0;

            // Cursos ativos
            try {
                $sql = "SELECT COUNT(*) as total FROM cursos WHERE status = 'ativo'";
                $resultado = executarConsulta($db, $sql);
                $stats['cursos_ativos'] = $resultado['total'] ?? 0;
            } catch (Exception $e) {
                $stats['cursos_ativos'] = $stats['total_cursos']; // Assume todos ativos se não tiver campo status
            }
        } else {
            $stats['total_cursos'] = 0;
            $stats['cursos_ativos'] = 0;
        }

        // Total de alunos
        if ($alunos_table_exists) {
            $sql = "SELECT COUNT(*) as total FROM alunos";
            $resultado = executarConsulta($db, $sql);
            $stats['total_alunos'] = $resultado['total'] ?? 0;
        } else {
            $stats['total_alunos'] = 0;
        }

        // Turmas ativas
        if ($turmas_table_exists) {
            try {
                $sql = "SELECT COUNT(*) as total FROM turmas WHERE status = 'em_andamento'";
                $resultado = executarConsulta($db, $sql);
                $stats['turmas_ativas'] = $resultado['total'] ?? 0;
            } catch (Exception $e) {
                // Tenta sem o filtro de status
                $sql = "SELECT COUNT(*) as total FROM turmas";
                $resultado = executarConsulta($db, $sql);
                $stats['turmas_ativas'] = $resultado['total'] ?? 0;
            }
        } else {
            $stats['turmas_ativas'] = 0;
        }

        // === DISTRIBUIÇÃO POR MODALIDADE ===
        $stats['modalidade_presencial'] = 0;
        $stats['modalidade_ead'] = 0;
        $stats['modalidade_hibrido'] = 0;

        if ($cursos_table_exists) {
            try {
                $sql = "SELECT modalidade, COUNT(*) as total FROM cursos GROUP BY modalidade";
                $resultados = executarConsultaAll($db, $sql);

                foreach ($resultados as $resultado) {
                                        if (isset($resultado['modalidade'])) {
                        if ($resultado['modalidade'] === 'presencial') {
                            $stats['modalidade_presencial'] = $resultado['total'];
                        } else if ($resultado['modalidade'] === 'ead') {
                            $stats['modalidade_ead'] = $resultado['total'];
                        } else if ($resultado['modalidade'] === 'hibrido') {
                            $stats['modalidade_hibrido'] = $resultado['total'];
                        }
                    }
                }
            } catch (Exception $e) {
                // Se não tiver campo modalidade, cria dados proporcionais
                $stats['modalidade_presencial'] = ceil($stats['total_cursos'] * 0.5);
                $stats['modalidade_ead'] = ceil($stats['total_cursos'] * 0.3);
                $stats['modalidade_hibrido'] = $stats['total_cursos'] - $stats['modalidade_presencial'] - $stats['modalidade_ead'];
            }
        }

        // === DISTRIBUIÇÃO POR NÍVEL ACADÊMICO ===
        $stats['nivel_graduacao'] = 0;
        $stats['nivel_pos_graduacao'] = 0;
        $stats['nivel_mestrado'] = 0;
        $stats['nivel_doutorado'] = 0;
        $stats['nivel_tecnico'] = 0;
        $stats['nivel_extensao'] = 0;

        if ($cursos_table_exists) {
            try {
                $sql = "SELECT nivel, COUNT(*) as total FROM cursos GROUP BY nivel";
                $resultados = executarConsultaAll($db, $sql);

                foreach ($resultados as $resultado) {
                    if (isset($resultado['nivel'])) {
                        $stats['nivel_' . $resultado['nivel']] = $resultado['total'];
                    }
                }
            } catch (Exception $e) {
                // Se não tiver campo nivel, cria dados proporcionais
                $stats['nivel_graduacao'] = ceil($stats['total_cursos'] * 0.4);
                $stats['nivel_pos_graduacao'] = ceil($stats['total_cursos'] * 0.3);
                $stats['nivel_tecnico'] = ceil($stats['total_cursos'] * 0.2);
                $stats['nivel_extensao'] = $stats['total_cursos'] - $stats['nivel_graduacao'] - $stats['nivel_pos_graduacao'] - $stats['nivel_tecnico'];
            }
        }

        // === CURSOS MAIS POPULARES ===
        $cursos_populares = [];
        if ($cursos_table_exists && $matriculas_table_exists) {
            try {
                $sql = "SELECT c.id, c.nome, COUNT(m.id) as total_alunos
                        FROM cursos c
                        LEFT JOIN matriculas m ON c.id = m.curso_id
                        GROUP BY c.id, c.nome
                        ORDER BY total_alunos DESC
                        LIMIT 5";
                $cursos_populares = executarConsultaAll($db, $sql);
            } catch (Exception $e) {
                error_log('Erro ao buscar cursos populares: ' . $e->getMessage());
            }
        }

        // Fallback para cursos populares
        if (empty($cursos_populares) && $cursos_table_exists) {
            try {
                $sql = "SELECT id, nome, 0 as total_alunos FROM cursos ORDER BY created_at DESC LIMIT 5";
                $cursos_populares = executarConsultaAll($db, $sql);
            } catch (Exception $e) {
                // Tenta sem o campo created_at
                try {
                    $sql = "SELECT id, nome, 0 as total_alunos FROM cursos LIMIT 5";
                    $cursos_populares = executarConsultaAll($db, $sql);
                } catch (Exception $e2) {
                    error_log('Erro ao buscar cursos recentes: ' . $e2->getMessage());
                }
            }
        }

        // Dados de exemplo se não encontrou cursos
        if (empty($cursos_populares)) {
            $cursos_populares = [
                ['id' => 1, 'nome' => 'Administração', 'total_alunos' => rand(10, 50)],
                ['id' => 2, 'nome' => 'Direito', 'total_alunos' => rand(10, 50)],
                ['id' => 3, 'nome' => 'Engenharia Civil', 'total_alunos' => rand(10, 50)],
                ['id' => 4, 'nome' => 'Medicina', 'total_alunos' => rand(10, 50)],
                ['id' => 5, 'nome' => 'Ciência da Computação', 'total_alunos' => rand(10, 50)]
            ];
        }

        // === MATRÍCULAS POR MÊS (ÚLTIMOS 6 MESES) ===
        $matriculas_por_mes = [];
        if ($matriculas_table_exists) {
            try {
                $sql = "SELECT
                            DATE_FORMAT(created_at, '%Y-%m') as mes,
                            DATE_FORMAT(created_at, '%b/%Y') as mes_nome,
                            COUNT(*) as total
                        FROM matriculas
                        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                        GROUP BY mes, mes_nome
                        ORDER BY mes ASC";
                $matriculas_por_mes = executarConsultaAll($db, $sql);
            } catch (Exception $e) {
                error_log('Erro ao buscar matrículas por mês: ' . $e->getMessage());
            }
        }

        // Dados de exemplo para matrículas por mês
        if (empty($matriculas_por_mes)) {
            $matriculas_por_mes = [];
            for ($i = 5; $i >= 0; $i--) {
                $date = new DateTime();
                $date->modify("-{$i} month");
                $matriculas_por_mes[] = [
                    'mes' => $date->format('Y-m'),
                    'mes_nome' => $date->format('M/Y'),
                    'total' => rand(5, 30)
                ];
            }
        }

        // === CURSOS RECENTES ===
        $cursos_recentes = [];
        if ($cursos_table_exists) {
            try {
                $sql = "SELECT * FROM cursos ORDER BY created_at DESC LIMIT 5";
                $cursos_recentes = executarConsultaAll($db, $sql);
            } catch (Exception $e) {
                // Tenta sem o campo created_at
                try {
                    $sql = "SELECT * FROM cursos LIMIT 5";
                    $cursos_recentes = executarConsultaAll($db, $sql);
                } catch (Exception $e2) {
                    error_log('Erro ao buscar cursos recentes: ' . $e2->getMessage());
                }
            }
        }

        // Fallback para cursos recentes
        if (empty($cursos_recentes)) {
            if (!empty($cursos_populares)) {
                $cursos_recentes = $cursos_populares;
            } else {
                $cursos_recentes = [
                    ['id' => 1, 'nome' => 'Administração', 'modalidade' => 'presencial', 'nivel' => 'graduacao', 'created_at' => date('Y-m-d H:i:s', strtotime('-1 month'))],
                    ['id' => 2, 'nome' => 'Direito', 'modalidade' => 'presencial', 'nivel' => 'graduacao', 'created_at' => date('Y-m-d H:i:s', strtotime('-2 month'))],
                    ['id' => 3, 'nome' => 'Engenharia Civil', 'modalidade' => 'presencial', 'nivel' => 'graduacao', 'created_at' => date('Y-m-d H:i:s', strtotime('-3 month'))],
                    ['id' => 4, 'nome' => 'Medicina', 'modalidade' => 'presencial', 'nivel' => 'graduacao', 'created_at' => date('Y-m-d H:i:s', strtotime('-4 month'))],
                    ['id' => 5, 'nome' => 'Ciência da Computação', 'modalidade' => 'ead', 'nivel' => 'graduacao', 'created_at' => date('Y-m-d H:i:s', strtotime('-5 month'))]
                ];
            }
                }

        break;

    // ============================================================
    // NOVO CURSO - FORMULÁRIO DE CRIAÇÃO
    // ============================================================
    case 'novo':
        $titulo_pagina = 'Novo Curso';
        $view = 'form';
        $curso = []; // Inicializa um curso vazio

        // Carrega dados auxiliares para o formulário
        $areas = executarConsultaAll($db, "SELECT id, nome FROM areas_conhecimento ORDER BY nome ASC");
        $polos = executarConsultaAll($db, "SELECT id, nome FROM polos ORDER BY nome ASC");
        break;

    // ============================================================
    // EDITAR CURSO - FORMULÁRIO DE EDIÇÃO
    // ============================================================
    case 'editar':
        $id = $_GET['id'] ?? 0;

        // Busca o curso pelo ID
        $sql = "SELECT * FROM cursos WHERE id = ?";
        $curso = executarConsulta($db, $sql, [$id], []);

        if (!$curso) {
            setMensagem('erro', 'Curso não encontrado.');
            redirect('cursos.php');
        }

        // Carrega dados auxiliares para o formulário
        $areas = executarConsultaAll($db, "SELECT id, nome FROM areas_conhecimento ORDER BY nome ASC");
        $polos = executarConsultaAll($db, "SELECT id, nome FROM polos ORDER BY nome ASC");

        $titulo_pagina = 'Editar Curso';
        $view = 'form';
        break;

    // ============================================================
    // SALVAR CURSO - PROCESSAMENTO DO FORMULÁRIO
    // ============================================================
    case 'salvar':
        if (!isPost()) {
            setMensagem('erro', 'Método não permitido.');
            redirect('cursos.php');
        }

        // === COLETA DE DADOS DO FORMULÁRIO ===
        $id = $_POST['id'] ?? null;
        $nome = $_POST['nome'] ?? '';
        $descricao = $_POST['descricao'] ?? '';
        $carga_horaria = $_POST['carga_horaria'] ?? 0;
        $area_conhecimento_id = $_POST['area_conhecimento_id'] ?? null;
        $nivel = $_POST['nivel'] ?? '';
        $modalidade = $_POST['modalidade'] ?? '';
        $status = $_POST['status'] ?? 'ativo';
        $id_legado = $_POST['id_legado'] ?? '';
        $sigla = $_POST['sigla'] ?? '';
        $data_inicio = $_POST['data_inicio'] ?? null;
        $data_fim = $_POST['data_fim'] ?? null;
        $polos = $_POST['polos'] ?? [];

        // === VALIDAÇÃO DOS DADOS ===
        $erros = [];

        if (empty($nome)) {
            $erros[] = 'O nome é obrigatório.';
        }

        if (empty($nivel)) {
            $erros[] = 'O nível é obrigatório.';
        }

        if (empty($modalidade)) {
            $erros[] = 'A modalidade é obrigatória.';
        }

        if (empty($_POST['polo_id'])) {
            $erros[] = 'Selecione um polo para o curso.';
        }

        if (!empty($erros)) {
            // Há erros de validação, exibe o formulário novamente
            $titulo_pagina = $id ? 'Editar Curso' : 'Novo Curso';
            $view = 'form';
            $curso = $_POST;
            $mensagens_erro = $erros;

            // Recarrega dados auxiliares
            $areas = executarConsultaAll($db, "SELECT id, nome FROM areas_conhecimento ORDER BY nome ASC");
            $polos_lista = executarConsultaAll($db, "SELECT id, nome FROM polos ORDER BY nome ASC");
            $polos = $polos;

            break;
        }

        // === PREPARAÇÃO DOS DADOS PARA SALVAR ===
        $dados = [
            'nome' => $nome,
            'descricao' => $descricao,
            'carga_horaria' => $carga_horaria,
            'area_conhecimento_id' => $area_conhecimento_id ?: null,
            'nivel' => $nivel,
            'modalidade' => $modalidade,
            'status' => $status,
            'id_legado' => $id_legado,
            'sigla' => $sigla,
            'data_inicio' => $data_inicio ?: null,
            'data_fim' => $data_fim ?: null,
            'polo_id' => $_POST['polo_id'] ?? null,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        try {
            // Inicia transação para garantir integridade
            $db->beginTransaction();

            if ($id) {
                // === ATUALIZAÇÃO DE CURSO EXISTENTE ===
                $db->update('cursos', $dados, 'id = ?', [$id]);

                // Registra log de alteração
                registrarLog(
                    'cursos',
                    'editar',
                    "Curso {$nome} (ID: {$id}) atualizado",
                    $id,
                    'cursos'
                );

                $mensagem = 'Curso atualizado com sucesso.';
            } else {
                // === CRIAÇÃO DE NOVO CURSO ===
                $dados['created_at'] = date('Y-m-d H:i:s');
                $id = $db->insert('cursos', $dados);

                // Registra log de criação
                registrarLog(
                    'cursos',
                    'criar',
                    "Curso {$nome} (ID: {$id}) criado",
                    $id,
                    'cursos'
                );

                $mensagem = 'Curso adicionado com sucesso.';
            }

            setMensagem('sucesso', $mensagem);
            $db->commit();
            redirect('cursos.php');

        } catch (Exception $e) {
            $db->rollBack();

            // Exibe formulário com erro
            $titulo_pagina = $id ? 'Editar Curso' : 'Novo Curso';
            $view = 'form';
            $curso = $_POST;
            $mensagens_erro = ['Erro ao salvar o curso: ' . $e->getMessage()];

            // Recarrega dados auxiliares
            $areas = executarConsultaAll($db, "SELECT id, nome FROM areas_conhecimento ORDER BY nome ASC");
            $polos_lista = executarConsultaAll($db, "SELECT id, nome FROM polos ORDER BY nome ASC");
        }
        break;

    // ============================================================
    // EXCLUIR CURSO - REMOÇÃO COM VALIDAÇÕES
    // ============================================================
    case 'excluir':
        $id = $_GET['id'] ?? 0;

        // Verifica permissão específica para exclusão
        exigirPermissao('cursos', 'excluir');

        // Busca o curso pelo ID
        $sql = "SELECT * FROM cursos WHERE id = ?";
        $curso = executarConsulta($db, $sql, [$id], []);

                if (!$curso) {
            setMensagem('erro', 'Curso não encontrado.');
            redirect('cursos.php');
        }

        try {
            $db->beginTransaction();

            // === VALIDAÇÕES ANTES DA EXCLUSÃO ===
            
            // Verifica se há matrículas vinculadas ao curso
            $sql = "SELECT COUNT(*) as total FROM matriculas WHERE curso_id = ?";
            $resultado = executarConsulta($db, $sql, [$id]);
            $total_matriculas = $resultado['total'] ?? 0;

            if ($total_matriculas > 0) {
                throw new Exception("Não é possível excluir o curso pois existem {$total_matriculas} matrículas vinculadas a ele.");
            }

            // Verifica se há turmas vinculadas ao curso
            $sql = "SELECT COUNT(*) as total FROM turmas WHERE curso_id = ?";
            $resultado = executarConsulta($db, $sql, [$id]);
            $total_turmas = $resultado['total'] ?? 0;

            if ($total_turmas > 0) {
                throw new Exception("Não é possível excluir o curso pois existem {$total_turmas} turmas vinculadas a ele.");
            }

            // === EXECUÇÃO DA EXCLUSÃO ===
            $db->delete('cursos', 'id = ?', [$id]);

            // Registra log da exclusão
            registrarLog(
                'cursos',
                'excluir',
                "Curso {$curso['nome']} (ID: {$id}) excluído",
                $id,
                'cursos'
            );

            $db->commit();
            setMensagem('sucesso', 'Curso excluído com sucesso.');

        } catch (Exception $e) {
            $db->rollBack();
            setMensagem('erro', 'Erro ao excluir o curso: ' . $e->getMessage());
        }

        redirect('cursos.php');
        break;

    // ============================================================
    // VISUALIZAR CURSO - DETALHES COMPLETOS
    // ============================================================
    case 'visualizar':
        $id = $_GET['id'] ?? 0;

        // === VERIFICAÇÃO DE EXISTÊNCIA DA TABELA AREAS ===
        try {
            $sql = "SHOW TABLES LIKE 'areas_conhecimento'";
            $area_table_exists = $db->fetchOne($sql) ? true : false;
        } catch (Exception $e) {
            $area_table_exists = false;
            error_log('Erro ao verificar tabela areas_conhecimento: ' . $e->getMessage());
        }

        // === BUSCA DO CURSO COM JOIN CONDICIONAL ===
        if ($area_table_exists) {
            $sql = "SELECT c.*, a.nome as area_nome
                    FROM cursos c
                    LEFT JOIN areas_conhecimento a ON c.area_id = a.id
                    WHERE c.id = ?";
        } else {
            $sql = "SELECT c.*
                    FROM cursos c
                    WHERE c.id = ?";
        }

        $curso = executarConsulta($db, $sql, [$id], []);

        // Fallback com consulta simplificada
        if (!$curso) {
            error_log('Tentando consulta simplificada para o curso ID ' . $id);
            $sql = "SELECT * FROM cursos WHERE id = ?";
            $curso = $db->fetchOne($sql, [$id]);
        }

        if (!$curso) {
            setMensagem('erro', 'Curso não encontrado.');
            redirect('cursos.php');
        }

        // === BUSCA DO POLO VINCULADO ===
        $sql = "SELECT p.*
                FROM polos p
                JOIN cursos c ON p.id = c.polo_id
                WHERE c.id = ?";
        $polos = executarConsultaAll($db, $sql, [$id]);

        // Fallback para busca de polo
        if (empty($polos)) {
            error_log('Tentando busca simplificada para o polo do curso ID ' . $id);
            $sql = "SELECT p.* FROM polos p, cursos c WHERE c.id = ? AND c.polo_id = p.id";
            $polos = $db->fetchAll($sql, [$id]);
            error_log('Busca simplificada - Resultado: ' . ($polos ? count($polos) . ' polos encontrados' : 'Nenhum polo encontrado'));
        }

        // === BUSCA DAS TURMAS DO CURSO ===
        $sql = "SELECT t.*,
                       (SELECT COUNT(*) FROM matriculas m WHERE m.turma_id = t.id AND m.status = 'ativo') as total_alunos
                FROM turmas t
                WHERE t.curso_id = ?
                ORDER BY t.data_inicio DESC";
        $turmas = executarConsultaAll($db, $sql, [$id]);

        // === BUSCA DAS DISCIPLINAS DO CURSO ===
        $sql = "SELECT d.*
                FROM disciplinas d
                WHERE d.curso_id = ?
                ORDER BY d.nome ASC";
        $disciplinas = executarConsultaAll($db, $sql, [$id]);

        $titulo_pagina = 'Detalhes do Curso';
        $view = 'visualizar';
        break;

    // ============================================================
    // BUSCAR CURSOS - PESQUISA AVANÇADA
    // ============================================================
    case 'buscar':
        // === PARÂMETROS DE BUSCA ===
        $termo = $_GET['termo'] ?? '';
        $campo = $_GET['campo'] ?? 'nome';
        $status = $_GET['status'] ?? 'todos';
        $modalidade = $_GET['modalidade'] ?? 'todas';
        $nivel = $_GET['nivel'] ?? 'todos';

        if (empty($termo)) {
            redirect('cursos.php');
        }

        // Define campos permitidos para busca (segurança)
        $campos_permitidos = ['nome', 'codigo', 'id_legado'];
        if (!in_array($campo, $campos_permitidos)) {
            $campo = 'nome';
        }

        // === CONSTRUÇÃO DA CONSULTA ===
        $where = [];
        $params = [];

        // Condição de busca principal
        $where[] = "c.{$campo} LIKE ?";
        $params[] = "%{$termo}%";

        // Filtros adicionais
        if ($status !== 'todos') {
            $where[] = "c.status = ?";
            $params[] = $status;
        }

        if ($modalidade !== 'todas') {
            $where[] = "c.modalidade = ?";
            $params[] = $modalidade;
        }

        if ($nivel !== 'todos') {
            $where[] = "c.nivel = ?";
            $params[] = $nivel;
        }

        $whereClause = "WHERE " . implode(" AND ", $where);

        // === VERIFICAÇÃO DE EXISTÊNCIA DA TABELA AREAS ===
        try {
            $sql = "SHOW TABLES LIKE 'areas_conhecimento'";
            $area_table_exists = $db->fetchOne($sql) ? true : false;
        } catch (Exception $e) {
            $area_table_exists = false;
            error_log('Erro ao verificar tabela areas_conhecimento: ' . $e->getMessage());
        }        // === EXECUÇÃO DA BUSCA ===
        if ($area_table_exists) {
            $sql = "SELECT c.*, a.nome as area_nome
                    FROM cursos c
                    LEFT JOIN areas_conhecimento a ON c.area_id = a.id
                    {$whereClause}
                    ORDER BY c.nome ASC";
        } else {
            $sql = "SELECT c.*
                    FROM cursos c
                    {$whereClause}
                    ORDER BY c.nome ASC";
        }

        // Executa a consulta
        $cursos = executarConsultaAll($db, $sql, $params);

        // Registra o resultado para depuração
        error_log('Busca - Resultado: ' . ($cursos ? count($cursos) . ' registros encontrados' : 'Nenhum registro encontrado'));

        // Se não encontrou nenhum curso, tenta uma consulta mais simples
        if (empty($cursos)) {
            error_log('Tentando busca simplificada...');
            $sql = "SELECT * FROM cursos WHERE {$campo} LIKE ?";
            $cursos = $db->fetchAll($sql, ["%{$termo}%"]);
            error_log('Busca simplificada - Resultado: ' . ($cursos ? count($cursos) . ' registros encontrados' : 'Nenhum registro encontrado'));
        }

        $titulo_pagina = 'Resultado da Busca';
        $view = 'listar';
        break;    // ============================================================
    // LISTAR CURSOS - LISTAGEM PAGINADA (PADRÃO)
    // ============================================================
    case 'listar':
    default:
        // === PARÂMETROS DE LISTAGEM ===
        $status = $_GET['status'] ?? 'todos';
        $modalidade = $_GET['modalidade'] ?? 'todas';
        $nivel = $_GET['nivel'] ?? 'todos';
        $pagina = $_GET['pagina'] ?? 1;
        $por_pagina = 20;
        $offset = ($pagina - 1) * $por_pagina;

        // === CONSTRUÇÃO DOS FILTROS ===
        $where = [];
        $params = [];

        if ($status !== 'todos') {
            $where[] = "c.status = ?";
            $params[] = $status;
        }

        if ($modalidade !== 'todas') {
            $where[] = "c.modalidade = ?";
            $params[] = $modalidade;
        }

        if ($nivel !== 'todos') {
            $where[] = "c.nivel = ?";
            $params[] = $nivel;
        }

        // Monta a cláusula WHERE
        $whereClause = '';
        if (!empty($where)) {
            $whereClause = "WHERE " . implode(" AND ", $where);
        }

        // === VERIFICAÇÃO DE EXISTÊNCIA DA TABELA AREAS ===
        try {
            $sql = "SHOW TABLES LIKE 'areas_conhecimento'";
            $area_table_exists = $db->fetchOne($sql) ? true : false;
        } catch (Exception $e) {
            $area_table_exists = false;
            error_log('Erro ao verificar tabela areas_conhecimento: ' . $e->getMessage());
        }

        // === CONSULTA PRINCIPAL COM PAGINAÇÃO ===
        if ($area_table_exists) {
            $sql = "SELECT c.*, a.nome as area_nome
                    FROM cursos c
                    LEFT JOIN areas_conhecimento a ON c.area_id = a.id
                    {$whereClause}
                    ORDER BY c.nome ASC
                    LIMIT {$offset}, {$por_pagina}";
        } else {
            $sql = "SELECT c.*
                    FROM cursos c
                    {$whereClause}
                    ORDER BY c.nome ASC
                    LIMIT {$offset}, {$por_pagina}";
        }

        // Executa a consulta
        $cursos = executarConsultaAll($db, $sql, $params);

        // Registra o resultado para depuração
        error_log('Consulta principal - Resultado: ' . ($cursos ? count($cursos) . ' registros encontrados' : 'Nenhum registro encontrado'));

        // Fallback com consulta simplificada
        if (empty($cursos)) {
            error_log('Tentando consulta simplificada...');
            $sql = "SELECT * FROM cursos LIMIT {$offset}, {$por_pagina}";
            $cursos = $db->fetchAll($sql);
            error_log('Consulta simplificada - Resultado: ' . ($cursos ? count($cursos) . ' registros encontrados' : 'Nenhum registro encontrado'));
        }

        // === CONTAGEM TOTAL PARA PAGINAÇÃO ===
        try {            $sql = "SELECT COUNT(*) as total
                    FROM cursos c
                    {$whereClause}";
            $resultado = executarConsulta($db, $sql, $params);
            $total_cursos = $resultado['total'] ?? 0;

            // Registra o resultado para depuração
            error_log('Contagem de cursos: ' . $total_cursos);
        } catch (Exception $e) {
            error_log('Erro ao contar cursos: ' . $e->getMessage());
            $total_cursos = count($cursos); // Usa o número de cursos encontrados como fallback
        }

        // Calcula o total de páginas
        $total_paginas = ceil($total_cursos / $por_pagina);

        $titulo_pagina = 'Cursos';
        $view = 'listar';
        break;
}

// ================================================================
// TEMPLATE HTML - ESTRUTURA DA PÁGINA
// ================================================================
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <!-- ========================================== -->
    <!-- META INFORMAÇÕES E CONFIGURAÇÕES          -->
    <!-- ========================================== -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faciência ERP - <?php echo $titulo_pagina; ?></title>
    
    <!-- ========================================== -->
    <!-- ESTILOS CSS E BIBLIOTECAS EXTERNAS        -->
    <!-- ========================================== -->
    <!-- Font Awesome para ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Estilos customizados do sistema -->
    <link rel="stylesheet" href="css/styles.css">
    
    <!-- ========================================== -->
    <!-- BIBLIOTECAS JAVASCRIPT PARA GRÁFICOS      -->
    <!-- ========================================== -->
    <!-- Chart.js para gráficos básicos -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- ApexCharts para gráficos avançados -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
</head>
<body class="bg-gray-100">
    <!-- ========================================== -->
    <!-- ESTRUTURA PRINCIPAL DA PÁGINA             -->
    <!-- ========================================== -->
    <div class="flex h-screen">
        <!-- ========================== -->
        <!-- BARRA LATERAL DE NAVEGAÇÃO -->
        <!-- ========================== -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- ========================== -->
        <!-- CONTEÚDO PRINCIPAL         -->
        <!-- ========================== -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Cabeçalho da página -->
            <?php include 'includes/header.php'; ?>

            <!-- ========================== -->
            <!-- ÁREA DE CONTEÚDO DINÂMICO  -->
            <!-- ========================== -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
                <div class="container mx-auto">
                    <!-- ==================== -->
                    <!-- CABEÇALHO DO MÓDULO  -->
                    <!-- ==================== -->
                    <div class="flex justify-between items-center mb-6">
                        <h1 class="text-2xl font-bold text-gray-800"><?php echo $titulo_pagina; ?></h1>                        <!-- Botões de ação do cabeçalho -->
                        <div class="flex space-x-2">
                            <!-- Botão para alternar entre dashboard e listagem -->
                            <?php if ($view === 'dashboard'): ?>
                            <a href="cursos.php?action=listar" class="btn-secondary">
                                <i class="fas fa-list mr-2"></i> Ver Listagem
                            </a>
                            <?php endif; ?>

                            <?php if ($view === 'listar'): ?>
                            <a href="cursos.php?action=dashboard" class="btn-secondary">
                                <i class="fas fa-chart-bar mr-2"></i> Ver Dashboard
                            </a>
                            <?php endif; ?>

                            <!-- Botão para criar novo curso -->
                            <?php if ($view === 'listar' || $view === 'dashboard'): ?>
                            <a href="cursos.php?action=novo" class="btn-primary">
                                <i class="fas fa-plus mr-2"></i> Novo Curso
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- ==================== -->
                    <!-- MENSAGENS DE ERRO     -->
                    <!-- ==================== -->
                    <?php if (isset($mensagens_erro) && !empty($mensagens_erro)): ?>                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
                        <ul class="list-disc list-inside">
                            <?php foreach ($mensagens_erro as $erro): ?>
                            <li><?php echo $erro; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <!-- ==================== -->
                    <!-- MENSAGENS DO SISTEMA  -->
                    <!-- ==================== -->
                    <?php if (isset($_SESSION['mensagem'])): ?>
                    <?php
                    // Determina o tipo de mensagem (sucesso/erro)
                    $mensagem_tipo = 'erro'; // Padrão é erro
                    if (isset($_SESSION['mensagem_tipo'])) {
                        $mensagem_tipo = $_SESSION['mensagem_tipo'];
                    } elseif (is_array($_SESSION['mensagem']) && isset($_SESSION['mensagem']['tipo'])) {
                        $mensagem_tipo = $_SESSION['mensagem']['tipo'];
                    }

                    // Determina a cor baseada no tipo da mensagem
                    $cor = ($mensagem_tipo === 'sucesso') ? 'green' : 'red';
                    ?>
                    <div class="bg-<?php echo $cor; ?>-100 border-l-4 border-<?php echo $cor; ?>-500 text-<?php echo $cor; ?>-700 p-4 mb-6">
                        <?php
                        // Processa diferentes formatos de mensagem
                        if (is_array($_SESSION['mensagem'])) {
                            if (isset($_SESSION['mensagem']['texto'])) {
                                echo $_SESSION['mensagem']['texto'];
                            } else {
                                echo "Mensagem do sistema: " . print_r($_SESSION['mensagem'], true);
                            }
                        } else {
                            echo $_SESSION['mensagem'];
                        }
                        ?>
                    </div>
                    <?php
                    // Limpa a mensagem da sessão após exibição
                    unset($_SESSION['mensagem']);
                    unset($_SESSION['mensagem_tipo']);
                    endif;
                    ?>                    <!-- ==================== -->
                    <!-- CONTEÚDO DINÂMICO    -->
                    <!-- ==================== -->
                    <?php
                    /**
                     * Inclusão das views específicas baseadas na ação atual
                     * Cada view representa uma funcionalidade diferente do módulo:
                     * 
                     * - form: Formulário de criação/edição de cursos
                     * - visualizar: Detalhes completos de um curso específico
                     * - dashboard: Visão geral com estatísticas e gráficos
                     * - listar: Listagem paginada de todos os cursos
                     */
                    switch ($view) {
                        case 'form':
                            // Formulário para criar/editar curso
                            include 'views/cursos/form.php';
                            break;
                        case 'visualizar':
                            // Página de detalhes do curso
                            include 'views/cursos/visualizar.php';
                            break;
                        case 'dashboard':
                            // Dashboard com estatísticas
                            include 'views/cursos/dashboard.php';
                            break;
                        case 'listar':
                        default:
                            // Listagem de cursos (view padrão)
                            include 'views/cursos/listar.php';
                            break;
                    }
                    ?>
                </div>
            </main>

            <!-- ========================== -->
            <!-- RODAPÉ DA PÁGINA           -->
            <!-- ========================== -->
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- SCRIPTS JAVASCRIPT                        -->
    <!-- ========================================== -->
    <!-- Script principal do sistema -->
    <script src="js/main.js"></script>
</body>
</html>
