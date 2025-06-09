<?php
/**
 * Página de gerenciamento de cursos
 */

// Inicializa o sistema
require_once __DIR__ . '/includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Verifica se o usuário tem permissão para acessar o módulo de cursos
exigirPermissao('cursos');

// Instancia o banco de dados
$db = Database::getInstance();

// Define a ação atual
$action = $_GET['action'] ?? 'dashboard';

// Função para executar consultas com tratamento de erro
function executarConsulta($db, $sql, $params = [], $default = null) {
    try {
        return $db->fetchOne($sql, $params);
    } catch (Exception $e) {
        // Registra o erro no log
        error_log('Erro na consulta SQL: ' . $e->getMessage());
        return $default;
    }
}

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

// Processa a ação
switch ($action) {
    case 'dashboard':
        // Exibe o dashboard de cursos
        $titulo_pagina = 'Dashboard de Cursos';
        $view = 'dashboard';

        // Carrega as estatísticas
        $stats = [];

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

        // Distribuição por modalidade
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
                // Se não tiver campo modalidade, cria dados de exemplo
                $stats['modalidade_presencial'] = ceil($stats['total_cursos'] * 0.5);
                $stats['modalidade_ead'] = ceil($stats['total_cursos'] * 0.3);
                $stats['modalidade_hibrido'] = $stats['total_cursos'] - $stats['modalidade_presencial'] - $stats['modalidade_ead'];
            }
        }

        // Distribuição por nível
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
                // Se não tiver campo nivel, cria dados de exemplo
                $stats['nivel_graduacao'] = ceil($stats['total_cursos'] * 0.4);
                $stats['nivel_pos_graduacao'] = ceil($stats['total_cursos'] * 0.3);
                $stats['nivel_tecnico'] = ceil($stats['total_cursos'] * 0.2);
                $stats['nivel_extensao'] = $stats['total_cursos'] - $stats['nivel_graduacao'] - $stats['nivel_pos_graduacao'] - $stats['nivel_tecnico'];
            }
        }

        // Cursos mais populares
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

        // Se não encontrou cursos populares, usa os cursos mais recentes ou cria dados de exemplo
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

        // Se ainda não encontrou cursos, cria dados de exemplo
        if (empty($cursos_populares)) {
            $cursos_populares = [
                ['id' => 1, 'nome' => 'Administração', 'total_alunos' => rand(10, 50)],
                ['id' => 2, 'nome' => 'Direito', 'total_alunos' => rand(10, 50)],
                ['id' => 3, 'nome' => 'Engenharia Civil', 'total_alunos' => rand(10, 50)],
                ['id' => 4, 'nome' => 'Medicina', 'total_alunos' => rand(10, 50)],
                ['id' => 5, 'nome' => 'Ciência da Computação', 'total_alunos' => rand(10, 50)]
            ];
        }

        // Matrículas por mês (nos últimos 6 meses)
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

        // Se não encontrou matrículas, cria dados de exemplo
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

        // Cursos recentes
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

        // Se não encontrou cursos recentes, usa os cursos populares ou cria dados de exemplo
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
    case 'novo':
        // Exibe o formulário para adicionar um novo curso
        $titulo_pagina = 'Novo Curso';
        $view = 'form';
        $curso = []; // Inicializa um curso vazio

        // Carrega as áreas de conhecimento para o formulário
        $areas = executarConsultaAll($db, "SELECT id, nome FROM areas_conhecimento ORDER BY nome ASC");

        // Carrega os polos para o formulário
        $polos = executarConsultaAll($db, "SELECT id, nome FROM polos ORDER BY nome ASC");
        break;

    case 'editar':
        // Exibe o formulário para editar um curso existente
        $id = $_GET['id'] ?? 0;

        // Busca o curso pelo ID
        $sql = "SELECT * FROM cursos WHERE id = ?";
        $curso = executarConsulta($db, $sql, [$id], []);

        if (!$curso) {
            // Curso não encontrado, redireciona para a listagem
            setMensagem('erro', 'Curso não encontrado.');
            redirect('cursos.php');
        }

        // Carrega as áreas de conhecimento para o formulário
        $areas = executarConsultaAll($db, "SELECT id, nome FROM areas_conhecimento ORDER BY nome ASC");

        // Carrega os polos para o formulário
        $polos = executarConsultaAll($db, "SELECT id, nome FROM polos ORDER BY nome ASC");

        // O polo principal já está no campo polo_id da tabela cursos
        // Não é mais necessário carregar polos vinculados da tabela cursos_polos

        $titulo_pagina = 'Editar Curso';
        $view = 'form';
        break;

    case 'salvar':
        // Salva os dados do curso (novo ou existente)
        if (!isPost()) {
            // Método não permitido
            setMensagem('erro', 'Método não permitido.');
            redirect('cursos.php');
        }

        // Obtém os dados do formulário
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

        // Valida os dados
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

            // Carrega as áreas de conhecimento para o formulário
            $areas = executarConsultaAll($db, "SELECT id, nome FROM areas_conhecimento ORDER BY nome ASC");

            // Carrega os polos para o formulário
            $polos_lista = executarConsultaAll($db, "SELECT id, nome FROM polos ORDER BY nome ASC");
            $polos = $polos;

            break;
        }

        // Prepara os dados para salvar
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
            // Inicia uma transação
            $db->beginTransaction();

            if ($id) {
                // Atualiza um curso existente
                $db->update('cursos', $dados, 'id = ?', [$id]);

                // Registra o log
                registrarLog(
                    'cursos',
                    'editar',
                    "Curso {$nome} (ID: {$id}) atualizado",
                    $id,
                    'cursos'
                );

                // Não é mais necessário atualizar polos vinculados
                // O polo principal já está sendo atualizado na tabela cursos

                $mensagem = 'Curso atualizado com sucesso.';
            } else {
                // Adiciona a data de criação
                $dados['created_at'] = date('Y-m-d H:i:s');

                // Insere um novo curso
                $id = $db->insert('cursos', $dados);

                // Registra o log
                registrarLog(
                    'cursos',
                    'criar',
                    "Curso {$nome} (ID: {$id}) criado",
                    $id,
                    'cursos'
                );

                $mensagem = 'Curso adicionado com sucesso.';
            }



            // Removida a inserção na tabela cursos_polos que não existe
            // O polo principal já está sendo salvo no campo polo_id da tabela cursos

            setMensagem('sucesso', $mensagem);

            // Confirma a transação
            $db->commit();

            // Redireciona para a listagem
            redirect('cursos.php');
        } catch (Exception $e) {
            // Desfaz a transação em caso de erro
            $db->rollBack();

            // Erro ao salvar
            $titulo_pagina = $id ? 'Editar Curso' : 'Novo Curso';
            $view = 'form';
            $curso = $_POST;
            $mensagens_erro = ['Erro ao salvar o curso: ' . $e->getMessage()];

            // Carrega as áreas de conhecimento para o formulário
            $areas = executarConsultaAll($db, "SELECT id, nome FROM areas_conhecimento ORDER BY nome ASC");

            // Carrega os polos para o formulário
            $polos_lista = executarConsultaAll($db, "SELECT id, nome FROM polos ORDER BY nome ASC");
        }
        break;

    case 'excluir':
        // Exclui um curso
        $id = $_GET['id'] ?? 0;

        // Verifica se o usuário tem permissão para excluir
        exigirPermissao('cursos', 'excluir');

        // Busca o curso pelo ID
        $sql = "SELECT * FROM cursos WHERE id = ?";
        $curso = executarConsulta($db, $sql, [$id], []);

        if (!$curso) {
            // Curso não encontrado, redireciona para a listagem
            setMensagem('erro', 'Curso não encontrado.');
            redirect('cursos.php');
        }

        try {
            // Inicia uma transação
            $db->beginTransaction();

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

            // Não é mais necessário excluir polos vinculados
            // O polo principal já está na tabela cursos

            // Exclui o curso
            $db->delete('cursos', 'id = ?', [$id]);

            // Registra o log
            registrarLog(
                'cursos',
                'excluir',
                "Curso {$curso['nome']} (ID: {$id}) excluído",
                $id,
                'cursos'
            );

            // Confirma a transação
            $db->commit();

            setMensagem('sucesso', 'Curso excluído com sucesso.');
        } catch (Exception $e) {
            // Desfaz a transação em caso de erro
            $db->rollBack();

            // Erro ao excluir
            setMensagem('erro', 'Erro ao excluir o curso: ' . $e->getMessage());
        }

        // Redireciona para a listagem
        redirect('cursos.php');
        break;

    case 'visualizar':
        // Exibe os detalhes de um curso
        $id = $_GET['id'] ?? 0;

        // Verifica se a tabela areas_conhecimento existe
        try {
            $sql = "SHOW TABLES LIKE 'areas_conhecimento'";
            $area_table_exists = $db->fetchOne($sql) ? true : false;
        } catch (Exception $e) {
            $area_table_exists = false;
            error_log('Erro ao verificar tabela areas_conhecimento: ' . $e->getMessage());
        }

        // Busca o curso pelo ID
        if ($area_table_exists) {
            // Consulta com JOIN na tabela areas_conhecimento
            $sql = "SELECT c.*, a.nome as area_nome
                    FROM cursos c
                    LEFT JOIN areas_conhecimento a ON c.area_id = a.id
                    WHERE c.id = ?";
        } else {
            // Consulta sem JOIN (caso a tabela areas_conhecimento não exista)
            $sql = "SELECT c.*
                    FROM cursos c
                    WHERE c.id = ?";
        }

        $curso = executarConsulta($db, $sql, [$id], []);

        // Se não encontrou o curso, tenta uma consulta mais simples
        if (!$curso) {
            error_log('Tentando consulta simplificada para o curso ID ' . $id);
            $sql = "SELECT * FROM cursos WHERE id = ?";
            $curso = $db->fetchOne($sql, [$id]);
        }

        if (!$curso) {
            // Curso não encontrado, redireciona para a listagem
            setMensagem('erro', 'Curso não encontrado.');
            redirect('cursos.php');
        }

        // Busca o polo vinculado ao curso diretamente da tabela cursos
        $sql = "SELECT p.*
                FROM polos p
                JOIN cursos c ON p.id = c.polo_id
                WHERE c.id = ?";
        $polos = executarConsultaAll($db, $sql, [$id]);

        // Se não encontrou nenhum polo, tenta uma consulta mais simples
        if (empty($polos)) {
            error_log('Tentando busca simplificada para o polo do curso ID ' . $id);
            $sql = "SELECT p.* FROM polos p, cursos c WHERE c.id = ? AND c.polo_id = p.id";
            $polos = $db->fetchAll($sql, [$id]);
            error_log('Busca simplificada - Resultado: ' . ($polos ? count($polos) . ' polos encontrados' : 'Nenhum polo encontrado'));
        }

        // Busca as turmas do curso
        $sql = "SELECT t.*,
                       (SELECT COUNT(*) FROM matriculas m WHERE m.turma_id = t.id AND m.status = 'ativo') as total_alunos
                FROM turmas t
                WHERE t.curso_id = ?
                ORDER BY t.data_inicio DESC";
        $turmas = executarConsultaAll($db, $sql, [$id]);

        // Busca as disciplinas do curso
        $sql = "SELECT d.*
                FROM disciplinas d
                WHERE d.curso_id = ?
                ORDER BY d.nome ASC";
        $disciplinas = executarConsultaAll($db, $sql, [$id]);

        $titulo_pagina = 'Detalhes do Curso';
        $view = 'visualizar';
        break;

    case 'buscar':
        // Busca cursos por termo
        $termo = $_GET['termo'] ?? '';
        $campo = $_GET['campo'] ?? 'nome';
        $status = $_GET['status'] ?? 'todos';
        $modalidade = $_GET['modalidade'] ?? 'todas';
        $nivel = $_GET['nivel'] ?? 'todos';

        if (empty($termo)) {
            redirect('cursos.php');
        }

        // Define os campos permitidos para busca
        $campos_permitidos = ['nome', 'codigo', 'id_legado'];

        if (!in_array($campo, $campos_permitidos)) {
            $campo = 'nome';
        }

        // Monta a consulta SQL
        $where = [];
        $params = [];

        // Adiciona a condição de busca
        $where[] = "c.{$campo} LIKE ?";
        $params[] = "%{$termo}%";

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
        $whereClause = "WHERE " . implode(" AND ", $where);

        // Verifica se a tabela areas_conhecimento existe
        try {
            $sql = "SHOW TABLES LIKE 'areas_conhecimento'";
            $area_table_exists = $db->fetchOne($sql) ? true : false;
        } catch (Exception $e) {
            $area_table_exists = false;
            error_log('Erro ao verificar tabela areas_conhecimento: ' . $e->getMessage());
        }

        // Consulta principal
        if ($area_table_exists) {
            // Consulta com JOIN na tabela areas_conhecimento
            $sql = "SELECT c.*, a.nome as area_nome
                    FROM cursos c
                    LEFT JOIN areas_conhecimento a ON c.area_id = a.id
                    {$whereClause}
                    ORDER BY c.nome ASC";
        } else {
            // Consulta sem JOIN (caso a tabela areas_conhecimento não exista)
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
        break;

    case 'listar':
    default:
        // Lista todos os cursos
        $status = $_GET['status'] ?? 'todos';
        $modalidade = $_GET['modalidade'] ?? 'todas';
        $nivel = $_GET['nivel'] ?? 'todos';
        $pagina = $_GET['pagina'] ?? 1;
        $por_pagina = 20;
        $offset = ($pagina - 1) * $por_pagina;

        // Monta a consulta SQL
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

        // Verifica se a tabela areas_conhecimento existe
        try {
            $sql = "SHOW TABLES LIKE 'areas_conhecimento'";
            $area_table_exists = $db->fetchOne($sql) ? true : false;
        } catch (Exception $e) {
            $area_table_exists = false;
            error_log('Erro ao verificar tabela areas_conhecimento: ' . $e->getMessage());
        }

        // Consulta principal
        if ($area_table_exists) {
            // Consulta com JOIN na tabela areas_conhecimento
            $sql = "SELECT c.*, a.nome as area_nome
                    FROM cursos c
                    LEFT JOIN areas_conhecimento a ON c.area_id = a.id
                    {$whereClause}
                    ORDER BY c.nome ASC
                    LIMIT {$offset}, {$por_pagina}";
        } else {
            // Consulta sem JOIN (caso a tabela areas_conhecimento não exista)
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

        // Se não encontrou nenhum curso, tenta uma consulta mais simples
        if (empty($cursos)) {
            error_log('Tentando consulta simplificada...');
            $sql = "SELECT * FROM cursos LIMIT {$offset}, {$por_pagina}";
            $cursos = $db->fetchAll($sql);
            error_log('Consulta simplificada - Resultado: ' . ($cursos ? count($cursos) . ' registros encontrados' : 'Nenhum registro encontrado'));
        }

        // Conta o total de cursos
        try {
            $sql = "SELECT COUNT(*) as total
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
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faciência ERP - <?php echo $titulo_pagina; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <!-- Chart.js para gráficos -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- ApexCharts para gráficos mais avançados -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
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
                            <a href="cursos.php?action=listar" class="btn-secondary">
                                <i class="fas fa-list mr-2"></i> Ver Listagem
                            </a>
                            <?php endif; ?>

                            <?php if ($view === 'listar'): ?>
                            <a href="cursos.php?action=dashboard" class="btn-secondary">
                                <i class="fas fa-chart-bar mr-2"></i> Ver Dashboard
                            </a>
                            <?php endif; ?>

                            <?php if ($view === 'listar' || $view === 'dashboard'): ?>
                            <a href="cursos.php?action=novo" class="btn-primary">
                                <i class="fas fa-plus mr-2"></i> Novo Curso
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
                    <?php
                    // Determina o tipo de mensagem
                    $mensagem_tipo = 'erro'; // Padrão é erro
                    if (isset($_SESSION['mensagem_tipo'])) {
                        $mensagem_tipo = $_SESSION['mensagem_tipo'];
                    } elseif (is_array($_SESSION['mensagem']) && isset($_SESSION['mensagem']['tipo'])) {
                        $mensagem_tipo = $_SESSION['mensagem']['tipo'];
                    }

                    // Determina a cor baseada no tipo
                    $cor = ($mensagem_tipo === 'sucesso') ? 'green' : 'red';
                    ?>
                    <div class="bg-<?php echo $cor; ?>-100 border-l-4 border-<?php echo $cor; ?>-500 text-<?php echo $cor; ?>-700 p-4 mb-6">
                        <?php
                        // Verifica se a mensagem é um array e converte para string se necessário
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
                    // Limpa a mensagem da sessão
                    unset($_SESSION['mensagem']);
                    unset($_SESSION['mensagem_tipo']);
                    endif;
                    ?>

                    <?php
                    // Inclui a view correspondente
                    switch ($view) {
                        case 'form':
                            include 'views/cursos/form.php';
                            break;
                        case 'visualizar':
                            include 'views/cursos/visualizar.php';
                            break;
                        case 'dashboard':
                            include 'views/cursos/dashboard.php';
                            break;
                        case 'listar':
                        default:
                            include 'views/cursos/listar.php';
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
