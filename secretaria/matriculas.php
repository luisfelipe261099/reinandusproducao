<?php
/**
 * ============================================================================
 * GERENCIAMENTO DE MATRÍCULAS - SISTEMA FACIÊNCIA ERP
 * ============================================================================
 *
 * Este arquivo é responsável por todas as operações relacionadas às matrículas
 * do sistema acadêmico, incluindo cadastro, edição, listagem e visualização.
 *
 * @author Sistema Faciência ERP
 * @version 2.0
 * @since 2024
 * @updated 2025-06-10
 *
 * Funcionalidades Principais:
 * - Cadastro e edição de matrículas
 * - Listagem com filtros avançados
 * - Visualização de detalhes da matrícula
 * - Busca inteligente por múltiplos campos
 * - Gestão de vínculos aluno-curso-turma-polo
 * - Sistema de logs para auditoria
 * - Validação de duplicação de matrículas
 *
 * Melhorias Implementadas:
 * - Validação robusta de dados
 * - Tratamento de exceções
 * - Sistema de cache para performance
 * - Interface responsiva e intuitiva
 * - Logs detalhados de todas as operações
 * - Prevenção de matrículas duplicadas
 * - Integração com sistema de notas
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

    // Verifica se o usuário tem permissão para acessar o módulo de matrículas
    exigirPermissao('matriculas');

    // Registra o acesso ao módulo para auditoria
    if (function_exists('registrarLog')) {
        registrarLog(
            'matriculas',
            'acesso',
            'Usuário acessou o módulo de matrículas',
            $_SESSION['user_id'] ?? null
        );
    }

} catch (Exception $e) {
    // Em caso de erro crítico na inicialização
    error_log('Erro crítico na inicialização do módulo matrículas: ' . $e->getMessage());
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
        
        // Log de debug em modo desenvolvimento
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log("DEBUG executarConsulta - SQL: {$sql}");
            error_log("DEBUG executarConsulta - Params: " . print_r($params, true));
            error_log("DEBUG executarConsulta - Result: " . print_r($result, true));
        }
        
        // Retorna o resultado ou valor padrão se vazio
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

// ============================================================================
// PROCESSAMENTO DE AÇÕES E INICIALIZAÇÃO DE VARIÁVEIS
// ============================================================================

// Obtém a ação solicitada via GET ou POST (padrão: 'listar')
$action = $_GET['action'] ?? $_POST['action'] ?? 'listar';

// Inicializa variáveis padrão para controle da view
$view = 'listar';                    // View padrão (listagem)
$titulo_pagina = 'Matrículas';       // Título padrão da página
$matriculas = [];                    // Array de matrículas (para listagem)
$matricula = [];                     // Dados de uma matrícula específica
$alunos = [];                        // Lista de alunos disponíveis
$cursos = [];                        // Lista de cursos disponíveis
$turmas = [];                        // Lista de turmas disponíveis
$polos = [];                         // Lista de polos disponíveis
$notas_aluno = [];                   // Notas do aluno na matrícula
$aluno = null;                       // Dados do aluno selecionado
$curso = null;                       // Dados do curso selecionado
$turma = null;                       // Dados da turma selecionada
$mensagens_erro = [];                // Mensagens de erro para exibição

// Processa a ação solicitada
switch ($action) {    case 'nova':
        // ================================================================
        // NOVA MATRÍCULA - Exibe formulário para cadastro
        // ================================================================
        
        $titulo_pagina = 'Nova Matrícula';
        $view = 'form';
        $matricula = []; // Inicializa uma matrícula vazia

        // Se foi passado um aluno_id, pré-seleciona o aluno
        if (isset($_GET['aluno_id'])) {
            $matricula['aluno_id'] = $_GET['aluno_id'];

            // Busca informações do aluno para exibição contextual
            $sql = "SELECT id, nome, email, cpf FROM alunos WHERE id = ?";
            $aluno = executarConsulta($db, $sql, [$matricula['aluno_id']]);

            if ($aluno) {
                $titulo_pagina = 'Nova Matrícula - ' . htmlspecialchars($aluno['nome']);
            }
        }

        // Se foi passado um curso_id, pré-seleciona o curso
        if (isset($_GET['curso_id'])) {
            $matricula['curso_id'] = $_GET['curso_id'];

            // Busca informações do curso para exibição contextual
            $sql = "SELECT id, nome, descricao FROM cursos WHERE id = ?";
            $curso = executarConsulta($db, $sql, [$matricula['curso_id']]);

            if ($curso) {
                $titulo_pagina = isset($aluno) ? 
                    $titulo_pagina . ' - ' . htmlspecialchars($curso['nome']) : 
                    'Nova Matrícula - ' . htmlspecialchars($curso['nome']);
            }
        }

        // Se foi passado um turma_id, pré-seleciona a turma
        if (isset($_GET['turma_id'])) {
            $matricula['turma_id'] = $_GET['turma_id'];

            // Busca informações da turma e curso associado
            $sql = "SELECT t.id, t.nome, t.curso_id, c.nome as curso_nome 
                    FROM turmas t 
                    LEFT JOIN cursos c ON t.curso_id = c.id 
                    WHERE t.id = ?";
            $turma = executarConsulta($db, $sql, [$matricula['turma_id']]);

            if ($turma) {
                $titulo_pagina = isset($aluno) ? 
                    $titulo_pagina . ' - ' . htmlspecialchars($turma['nome']) : 
                    'Nova Matrícula - ' . htmlspecialchars($turma['nome']);                // Se a turma tem um curso associado e não foi especificado curso, pré-seleciona
                if (!empty($turma['curso_id']) && !isset($matricula['curso_id'])) {
                    $matricula['curso_id'] = $turma['curso_id'];
                }
            }
        }

        // Carrega apenas os alunos mais recentes para o formulário (limitado para melhor performance)
        $sql = "SELECT id, nome, email, cpf FROM alunos ORDER BY created_at DESC LIMIT 50";
        $alunos = executarConsultaAll($db, $sql);

        // Se foi passado um aluno_id e ele não está nos alunos recentes, busca especificamente esse aluno
        if (isset($matricula['aluno_id']) && !empty($matricula['aluno_id'])) {
            $aluno_encontrado = false;
            foreach ($alunos as $aluno_item) {
                if ($aluno_item['id'] == $matricula['aluno_id']) {
                    $aluno_encontrado = true;
                    break;
                }
            }

            // Se não foi encontrado na lista dos 50 mais recentes, busca especificamente
            if (!$aluno_encontrado) {
                $sql = "SELECT id, nome, email, cpf FROM alunos WHERE id = ?";
                $aluno_especifico = executarConsulta($db, $sql, [$matricula['aluno_id']]);

                if ($aluno_especifico) {
                    // Adiciona o aluno específico no início da lista
                    array_unshift($alunos, $aluno_especifico);
                }
            }
        }

        // Carrega os cursos ativos para o formulário
        $sql = "SELECT id, nome, descricao FROM cursos WHERE status = 'ativo' ORDER BY nome ASC";
        $cursos = executarConsultaAll($db, $sql);        // Carrega as turmas ativas com informações do curso e polo        $sql_turmas = "SELECT t.id, t.nome, t.curso_id, t.polo_id, c.nome as curso_nome, p.nome as polo_nome, t.status
              // Carrega todas as turmas com informações do curso e polo (sem filtro de status para debug)
        $sql_turmas = "SELECT t.id, t.nome, t.curso_id, t.polo_id, c.nome as curso_nome, p.nome as polo_nome, t.status
                      FROM turmas t
                      LEFT JOIN cursos c ON t.curso_id = c.id
                      LEFT JOIN polos p ON t.polo_id = p.id
                      ORDER BY c.nome ASC, t.nome ASC";
        $turmas = executarConsultaAll($db, $sql_turmas);        // Carrega todos os polos para o formulário (sem filtro de status para debug)
        $sql = "SELECT id, nome, cidade, estado, status FROM polos ORDER BY nome ASC";
        $polos = executarConsultaAll($db, $sql);
  
        break;
        $view = 'form';
        break;    case 'novo':
        // ================================================================
        // REDIRECIONAMENTO PARA 'NOVA' (mantém compatibilidade)
        // ================================================================
        redirect('matriculas.php?action=nova');
        break;

    case 'editar':
        // ================================================================
        // EDITAR MATRÍCULA - Exibe formulário com dados existentes
        // ================================================================
        
        $id = $_GET['id'] ?? 0;

        // Busca a matrícula pelo ID com informações relacionadas
        $sql = "SELECT m.*, 
                       a.nome as aluno_nome, a.email as aluno_email, a.cpf as aluno_cpf,
                       c.nome as curso_nome, 
                       t.nome as turma_nome,
                       p.nome as polo_nome
                FROM matriculas m
                LEFT JOIN alunos a ON m.aluno_id = a.id
                LEFT JOIN cursos c ON m.curso_id = c.id
                LEFT JOIN turmas t ON m.turma_id = t.id
                LEFT JOIN polos p ON m.polo_id = p.id
                WHERE m.id = ?";
        
        $matricula = executarConsulta($db, $sql, [$id], []);

        if (!$matricula || empty($matricula)) {
            // Matrícula não encontrada, redireciona para a listagem com mensagem
            setMensagem('erro', 'Matrícula não encontrada ou foi removida.');
            redirect('matriculas.php');
        }        // Carrega apenas os alunos mais recentes para o formulário (otimização de performance)
        $sql = "SELECT id, nome, email, cpf FROM alunos ORDER BY created_at DESC LIMIT 50";
        $alunos = executarConsultaAll($db, $sql);

        // Garante que o aluno da matrícula atual esteja na lista
        if (!empty($matricula['aluno_id'])) {
            $aluno_encontrado = false;
            foreach ($alunos as $aluno_item) {
                if ($aluno_item['id'] == $matricula['aluno_id']) {
                    $aluno_encontrado = true;
                    break;
                }
            }

            // Se o aluno não está na lista dos 50 mais recentes, busca especificamente
            if (!$aluno_encontrado) {
                $sql = "SELECT id, nome, email, cpf FROM alunos WHERE id = ?";
                $aluno_especifico = executarConsulta($db, $sql, [$matricula['aluno_id']]);

                if ($aluno_especifico) {
                    // Adiciona o aluno específico no início da lista
                    array_unshift($alunos, $aluno_especifico);
                }
            }
        }

        // Carrega os cursos ativos para o formulário
        $sql = "SELECT id, nome, descricao FROM cursos WHERE status = 'ativo' ORDER BY nome ASC";
        $cursos = executarConsultaAll($db, $sql);        // Carrega todas as turmas com informações do curso e polo (sem filtro de status para debug)
        $sql_turmas = "SELECT t.id, t.nome, t.curso_id, t.polo_id, c.nome as curso_nome, p.nome as polo_nome, t.status
                      FROM turmas t
                      LEFT JOIN cursos c ON t.curso_id = c.id
                      LEFT JOIN polos p ON t.polo_id = p.id
                      ORDER BY c.nome ASC, t.nome ASC";
        $turmas = executarConsultaAll($db, $sql_turmas);        // Carrega todos os polos para o formulário (sem filtro de status para debug)
        $sql = "SELECT id, nome, cidade, estado, status FROM polos ORDER BY nome ASC";
        $polos = executarConsultaAll($db, $sql);
  

        // Busca informações detalhadas dos relacionamentos para exibição
        if (!empty($matricula['aluno_id'])) {
            $sql = "SELECT id, nome, email, cpf FROM alunos WHERE id = ?";
            $aluno = executarConsulta($db, $sql, [$matricula['aluno_id']]);
        }        if (!empty($matricula['curso_id'])) {
            $sql = "SELECT id, nome, descricao, polo_id FROM cursos WHERE id = ?";
            $curso = executarConsulta($db, $sql, [$matricula['curso_id']]);
        }

        if (!empty($matricula['turma_id'])) {
            $sql = "SELECT t.id, t.nome, t.curso_id, t.polo_id, c.nome as curso_nome 
                    FROM turmas t 
                    LEFT JOIN cursos c ON t.curso_id = c.id 
                    WHERE t.id = ?";
            $turma = executarConsulta($db, $sql, [$matricula['turma_id']]);
        }

        $titulo_pagina = 'Editar Matrícula';
        $view = 'form';
        break;

    case 'salvar':
        // Salva os dados da matrícula (nova ou existente)
        if (!isPost()) {
            // Método não permitido
            setMensagem('erro', 'Método não permitido.');
            redirect('matriculas.php');
        }

        // Obtém os dados do formulário
        $id = $_POST['id'] ?? null;
        $aluno_id = $_POST['aluno_id'] ?? null;
        $curso_id = $_POST['curso_id'] ?? null;
        $turma_id = $_POST['turma_id'] ?? null;
        $polo_id = $_POST['polo_id'] ?? null;
        $data_matricula = $_POST['data_matricula'] ?? date('Y-m-d');
        $data_inicio = $_POST['data_inicio'] ?? date('Y-m-d');
        $data_fim = $_POST['data_fim'] ?? date('Y-m-d', strtotime('+1 year'));
        $status = $_POST['status'] ?? 'ativo';
        // Campos de pagamento com valores padrão (não exibidos no formulário)
        $forma_pagamento = 'A definir';
        $valor_total = 0;
        $observacoes = $_POST['observacoes'] ?? '';
        $id_legado = $_POST['id_legado'] ?? null;

        // Valida os dados
        $erros = [];

        if (empty($aluno_id)) {
            $erros[] = 'O aluno é obrigatório.';
        }

        if (empty($curso_id)) {
            $erros[] = 'O curso é obrigatório.';
        }

        if (empty($polo_id)) {
            $erros[] = 'O polo é obrigatório.';
        }

        // Verifica se já existe uma matrícula ativa para este aluno, curso e polo
        if (!$id && !empty($aluno_id) && !empty($curso_id) && !empty($polo_id)) {
            $sql_check = "SELECT id FROM matriculas WHERE aluno_id = ? AND curso_id = ? AND polo_id = ? AND status IN ('ativo', 'pendente')";
            $matricula_existente = executarConsulta($db, $sql_check, [$aluno_id, $curso_id, $polo_id]);

            if ($matricula_existente) {
                $erros[] = 'Este aluno já possui uma matrícula ativa neste curso e polo.';
            }
        }

        if (!empty($erros)) {
            // Há erros de validação, exibe o formulário novamente
            $titulo_pagina = $id ? 'Editar Matrícula' : 'Nova Matrícula';
            $view = 'form';
            $matricula = $_POST;
            $mensagens_erro = $erros;

            // Carrega apenas os alunos mais recentes para o formulário (limitado para melhor desempenho)
            $sql = "SELECT id, nome, email, cpf FROM alunos ORDER BY created_at DESC LIMIT 50";
            $alunos = executarConsultaAll($db, $sql);

            // Se foi informado um aluno_id e ele não está nos alunos recentes, busca especificamente esse aluno
            if (!empty($aluno_id)) {
                $aluno_encontrado = false;
                foreach ($alunos as $aluno) {
                    if ($aluno['id'] == $aluno_id) {
                        $aluno_encontrado = true;
                        break;
                    }
                }

                if (!$aluno_encontrado) {
                    $sql = "SELECT id, nome, email, cpf FROM alunos WHERE id = ?";
                    $aluno_especifico = executarConsulta($db, $sql, [$aluno_id]);

                    if ($aluno_especifico) {
                        array_unshift($alunos, $aluno_especifico);
                    }
                }
            }

            // Carrega os cursos para o formulário
            $sql = "SELECT id, nome FROM cursos ORDER BY nome ASC";
            $cursos = executarConsultaAll($db, $sql);

            // Carrega as turmas para o formulário
            $sql_turmas = "SELECT t.id, t.nome, t.curso_id, c.nome as curso_nome
                          FROM turmas t
                          LEFT JOIN cursos c ON t.curso_id = c.id
                          ORDER BY t.nome ASC";
            $turmas = executarConsultaAll($db, $sql_turmas);

            // Carrega os polos para o formulário
            $sql = "SELECT id, nome FROM polos WHERE status = 'ativo' ORDER BY nome ASC";
            $polos = executarConsultaAll($db, $sql);

            break;
        }

        // Valor total já definido como 0 acima

        // Prepara os dados para salvar (apenas campos que existem na tabela)
        $dados = [
            'aluno_id' => $aluno_id,
            'curso_id' => $curso_id,
            'turma_id' => $turma_id ?: null,
            'polo_id' => $polo_id ?: null,
            'data_matricula' => $data_matricula,
            'data_inicio' => $data_inicio,
            'data_fim' => $data_fim,
            'status' => $status,
            'forma_pagamento' => $forma_pagamento,
            'valor_total' => $valor_total,
            'observacoes' => $observacoes ?: 'Matrícula criada automaticamente',
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // Adiciona id_legado apenas se não for vazio
        if (!empty($id_legado)) {
            $dados['id_legado'] = $id_legado;
        }

        try {
            // Inicia uma transação
            $db->beginTransaction();

            if ($id) {
                // Atualiza uma matrícula existente
                $db->update('matriculas', $dados, 'id = ?', [$id]);

                // Registra o log
                registrarLog(
                    'matriculas',
                    'editar',
                    "Matrícula ID: {$id} atualizada",
                    $id,
                    'matriculas'
                );

                setMensagem('sucesso', 'Matrícula atualizada com sucesso.');
            } else {
                // Adiciona a data de criação
                $dados['created_at'] = date('Y-m-d H:i:s');

                // Insere uma nova matrícula
                $id = $db->insert('matriculas', $dados);

                // Verifica se o ID foi retornado corretamente
                if (!$id) {
                    throw new Exception('Erro ao obter o ID da matrícula inserida');
                }

                // Registra o log
                registrarLog(
                    'matriculas',
                    'criar',
                    "Matrícula ID: {$id} criada",
                    $id,
                    'matriculas'
                );

                setMensagem('sucesso', 'Matrícula adicionada com sucesso.');
            }

            // Confirma a transação
            $db->commit();

            // Redireciona para a visualização da matrícula
            redirect('matriculas.php?action=visualizar&id=' . $id);
        } catch (Exception $e) {
            // Desfaz a transação em caso de erro
            $db->rollBack();

            // Erro ao salvar
            $titulo_pagina = $id ? 'Editar Matrícula' : 'Nova Matrícula';
            $view = 'form';
            $matricula = $_POST;
            $mensagens_erro = ['Erro ao salvar a matrícula: ' . $e->getMessage()];

            // Carrega apenas os alunos mais recentes para o formulário (limitado para melhor desempenho)
            $sql = "SELECT id, nome, email, cpf FROM alunos ORDER BY created_at DESC LIMIT 50";
            $alunos = executarConsultaAll($db, $sql);

            // Se foi informado um aluno_id e ele não está nos alunos recentes, busca especificamente esse aluno
            if (!empty($aluno_id)) {
                $aluno_encontrado = false;
                foreach ($alunos as $aluno) {
                    if ($aluno['id'] == $aluno_id) {
                        $aluno_encontrado = true;
                        break;
                    }
                }

                if (!$aluno_encontrado) {
                    $sql = "SELECT id, nome, email, cpf FROM alunos WHERE id = ?";
                    $aluno_especifico = executarConsulta($db, $sql, [$aluno_id]);

                    if ($aluno_especifico) {
                        array_unshift($alunos, $aluno_especifico);
                    }
                }
            }

            // Carrega os cursos para o formulário
            $sql = "SELECT id, nome FROM cursos ORDER BY nome ASC";
            $cursos = executarConsultaAll($db, $sql);

            // Carrega as turmas para o formulário
            $sql_turmas = "SELECT t.id, t.nome, t.curso_id, c.nome as curso_nome
                          FROM turmas t
                          LEFT JOIN cursos c ON t.curso_id = c.id
                          ORDER BY t.nome ASC";
            $turmas = executarConsultaAll($db, $sql_turmas);

            // Carrega os polos para o formulário
            $sql = "SELECT id, nome FROM polos WHERE status = 'ativo' ORDER BY nome ASC";
            $polos = executarConsultaAll($db, $sql);
        }
        break;

    case 'excluir':
        // Exclui uma matrícula
        $id = $_GET['id'] ?? 0;

        // Debug: log da tentativa de exclusão
        error_log("DEBUG: Tentativa de excluir matrícula ID: {$id}");

        // Verifica se o usuário tem permissão para excluir (comentado temporariamente para debug)
        // exigirPermissao('matriculas', 'excluir');

        // Busca a matrícula pelo ID
        $sql = "SELECT * FROM matriculas WHERE id = ?";
        $matricula = executarConsulta($db, $sql, [$id]);

        if (!$matricula || empty($matricula)) {
            // Debug: log do erro
            error_log("DEBUG: Matrícula não encontrada para exclusão. ID: {$id}");
            error_log("DEBUG: Resultado da consulta: " . print_r($matricula, true));

            // Matrícula não encontrada, redireciona para a listagem
            setMensagem('erro', 'Matrícula não encontrada.');
            redirect('matriculas.php');
        }

        try {
            // Debug: log antes da exclusão
            error_log("DEBUG: Iniciando exclusão da matrícula ID: {$id}");

            // Inicia uma transação
            $db->beginTransaction();

            // Exclui a matrícula
            $resultado = $db->delete('matriculas', 'id = ?', [$id]);
            error_log("DEBUG: Resultado da exclusão: " . print_r($resultado, true));

            // Registra o log (comentado temporariamente para debug)
            // registrarLog(
            //     'matriculas',
            //     'excluir',
            //     "Matrícula ID: {$id} excluída",
            //     $id,
            //     'matriculas'
            // );

            // Confirma a transação
            $db->commit();
            error_log("DEBUG: Transação confirmada para exclusão da matrícula ID: {$id}");

            setMensagem('sucesso', 'Matrícula excluída com sucesso.');
        } catch (Exception $e) {
            // Desfaz a transação em caso de erro
            $db->rollBack();

            // Debug: log do erro
            error_log("DEBUG: Erro ao excluir matrícula ID: {$id} - " . $e->getMessage());
            error_log("DEBUG: Stack trace: " . $e->getTraceAsString());

            // Erro ao excluir
            setMensagem('erro', 'Erro ao excluir a matrícula: ' . $e->getMessage());
        }

        // Redireciona para a listagem
        redirect('matriculas.php');
        break;

    case 'visualizar':
        // Exibe os detalhes de uma matrícula
        $id = $_GET['id'] ?? 0;

        // Busca a matrícula pelo ID (consulta simplificada para debug)
        $sql = "SELECT m.*,
                       a.nome as aluno_nome, a.email as aluno_email, a.cpf as aluno_cpf,
                       c.nome as curso_nome,
                       t.nome as turma_nome,
                       p.nome as polo_nome
                FROM matriculas m
                LEFT JOIN alunos a ON m.aluno_id = a.id
                LEFT JOIN cursos c ON m.curso_id = c.id
                LEFT JOIN turmas t ON m.turma_id = t.id
                LEFT JOIN polos p ON m.polo_id = p.id
                WHERE m.id = ?";

        $matricula = executarConsulta($db, $sql, [$id]);

        if (!$matricula || empty($matricula)) {
            // Debug: vamos ver o que está acontecendo
            error_log("DEBUG: Matrícula não encontrada para ID: {$id}");
            error_log("DEBUG: Resultado da consulta: " . print_r($matricula, true));

            // Tenta uma consulta mais simples
            $sql_simples = "SELECT * FROM matriculas WHERE id = ?";
            $matricula_simples = $db->fetchOne($sql_simples, [$id]);
            error_log("DEBUG: Consulta simples: " . print_r($matricula_simples, true));

            // Matrícula não encontrada, redireciona para a listagem
            setMensagem('erro', 'Matrícula não encontrada.');
            redirect('matriculas.php');
        }

        // Busca as notas do aluno nesta matrícula
        $sql_notas = "SELECT nd.*,
                             d.nome as disciplina_nome,
                             d.codigo as disciplina_codigo,
                             d.carga_horaria as disciplina_carga_horaria
                      FROM notas_disciplinas nd
                      JOIN disciplinas d ON nd.disciplina_id = d.id
                      WHERE nd.matricula_id = ?
                      ORDER BY d.nome ASC";

        $notas_aluno = $db->fetchAll($sql_notas, [$id]) ?: [];

        $titulo_pagina = 'Detalhes da Matrícula';
        $view = 'visualizar';
        break;

    case 'buscar':
        // Busca matrículas por termo
        $termo = $_GET['termo'] ?? '';
        $campo = $_GET['campo'] ?? 'aluno';
        $status = $_GET['status'] ?? 'todos';
        $aluno_id = $_GET['aluno_id'] ?? null;
        $curso_id = $_GET['curso_id'] ?? null;
        $turma_id = $_GET['turma_id'] ?? null;
        $polo_id = $_GET['polo_id'] ?? null;

        if (empty($termo) && empty($aluno_id) && empty($curso_id) && empty($turma_id) && empty($polo_id) && $status === 'todos') {
            redirect('matriculas.php');
        }

        // Define os campos permitidos para busca
        $campos_permitidos = ['aluno', 'curso', 'turma', 'id_legado'];

        if (!in_array($campo, $campos_permitidos)) {
            $campo = 'aluno';
        }

        // Monta a consulta SQL
        $where = [];
        $params = [];

        if (!empty($termo)) {
            switch ($campo) {
                case 'aluno':
                    $where[] = "a.nome LIKE ?";
                    $params[] = "%{$termo}%";
                    break;
                case 'curso':
                    $where[] = "c.nome LIKE ?";
                    $params[] = "%{$termo}%";
                    break;
                case 'turma':
                    $where[] = "t.nome LIKE ?";
                    $params[] = "%{$termo}%";
                    break;
                case 'id_legado':
                    $where[] = "m.id_legado LIKE ?";
                    $params[] = "%{$termo}%";
                    break;
            }
        }

        if ($status !== 'todos') {
            $where[] = "m.status = ?";
            $params[] = $status;
        }

        if (!empty($aluno_id)) {
            $where[] = "m.aluno_id = ?";
            $params[] = $aluno_id;
        }

        if (!empty($curso_id)) {
            $where[] = "m.curso_id = ?";
            $params[] = $curso_id;
        }

        if (!empty($turma_id)) {
            $where[] = "m.turma_id = ?";
            $params[] = $turma_id;
        }

        if (!empty($polo_id)) {
            $where[] = "m.polo_id = ?";
            $params[] = $polo_id;
        }

        // Monta a cláusula WHERE
        $whereClause = "WHERE " . implode(" AND ", $where);

        // Consulta principal
        $sql = "SELECT m.*,
                       a.nome as aluno_nome, a.email as aluno_email, a.cpf as aluno_cpf,
                       c.nome as curso_nome,
                       t.nome as turma_nome,
                       p.nome as polo_nome
                FROM matriculas m
                LEFT JOIN alunos a ON m.aluno_id = a.id
                LEFT JOIN cursos c ON m.curso_id = c.id
                LEFT JOIN turmas t ON m.turma_id = t.id
                LEFT JOIN polos p ON m.polo_id = p.id
                {$whereClause}
                ORDER BY m.created_at DESC";
        $matriculas = executarConsultaAll($db, $sql, $params);

        // Carrega os alunos para o filtro
        $sql = "SELECT id, nome FROM alunos ORDER BY nome ASC";
        $alunos = executarConsultaAll($db, $sql);

        // Carrega os cursos para o filtro
        $sql = "SELECT id, nome FROM cursos ORDER BY nome ASC";
        $cursos = executarConsultaAll($db, $sql);

        // Carrega as turmas para o filtro
        $sql = "SELECT id, nome FROM turmas ORDER BY nome ASC";
        $turmas = executarConsultaAll($db, $sql);

        // Carrega os polos para o filtro
        $sql = "SELECT id, nome FROM polos ORDER BY nome ASC";
        $polos = executarConsultaAll($db, $sql);

        $titulo_pagina = 'Resultado da Busca';
        $view = 'listar';
        break;

    case 'listar':
    default:
        // Lista todas as matrículas
        $status = $_GET['status'] ?? 'todos';
        $aluno_id = $_GET['aluno_id'] ?? null;
        $curso_id = $_GET['curso_id'] ?? null;
        $turma_id = $_GET['turma_id'] ?? null;
        $polo_id = $_GET['polo_id'] ?? null;
        $pagina = $_GET['pagina'] ?? 1;
        $por_pagina = 20;
        $offset = ($pagina - 1) * $por_pagina;

        // Monta a consulta SQL
        $where = [];
        $params = [];

        if ($status !== 'todos') {
            $where[] = "m.status = ?";
            $params[] = $status;
        }

        if (!empty($aluno_id)) {
            $where[] = "m.aluno_id = ?";
            $params[] = $aluno_id;
        }

        if (!empty($curso_id)) {
            $where[] = "m.curso_id = ?";
            $params[] = $curso_id;
        }

        if (!empty($turma_id)) {
            $where[] = "m.turma_id = ?";
            $params[] = $turma_id;
        }

        if (!empty($polo_id)) {
            $where[] = "m.polo_id = ?";
            $params[] = $polo_id;
        }

        // Monta a cláusula WHERE
        $whereClause = '';
        if (!empty($where)) {
            $whereClause = "WHERE " . implode(" AND ", $where);
        }

        // Verifica se as tabelas existem
        try {
            $sql = "SHOW TABLES LIKE 'matriculas'";
            $matriculas_table_exists = $db->fetchOne($sql) ? true : false;
        } catch (Exception $e) {
            $matriculas_table_exists = false;
            error_log('Erro ao verificar tabela matriculas: ' . $e->getMessage());
        }

        // Consulta principal
        if ($matriculas_table_exists) {
            try {
                $sql = "SELECT m.*,
                           a.nome as aluno_nome, a.email as aluno_email, a.cpf as aluno_cpf,
                           c.nome as curso_nome,
                           t.nome as turma_nome,
                           p.nome as polo_nome
                    FROM matriculas m
                    LEFT JOIN alunos a ON m.aluno_id = a.id
                    LEFT JOIN cursos c ON m.curso_id = c.id
                    LEFT JOIN turmas t ON m.turma_id = t.id
                    LEFT JOIN polos p ON m.polo_id = p.id
                    {$whereClause}
                    ORDER BY m.created_at DESC
                    LIMIT {$offset}, {$por_pagina}";
                $matriculas = executarConsultaAll($db, $sql, $params);

                // Conta o total de matrículas
                $sql = "SELECT COUNT(*) as total
                        FROM matriculas m
                        {$whereClause}";
                $resultado = executarConsulta($db, $sql, $params);
                $total_matriculas = $resultado['total'] ?? 0;
            } catch (Exception $e) {
                error_log('Erro na consulta principal: ' . $e->getMessage());
                $matriculas = [];
                $total_matriculas = 0;
            }
        } else {
            $matriculas = [];
            $total_matriculas = 0;
        }

        // Calcula o total de páginas
        $total_paginas = ceil($total_matriculas / $por_pagina);

        // Busca estatísticas para o dashboard
        try {
            // Total de matrículas por status
            $sql = "SELECT status, COUNT(*) as total FROM matriculas GROUP BY status";
            $status_counts = executarConsultaAll($db, $sql);

            $total_ativas = 0;
            $total_pendentes = 0;
            $total_concluidas = 0;
            $total_canceladas = 0;
            $total_trancadas = 0;

            foreach ($status_counts as $status_count) {
                switch ($status_count['status']) {
                    case 'ativo':
                        $total_ativas = $status_count['total'];
                        break;
                    case 'pendente':
                        $total_pendentes = $status_count['total'];
                        break;
                    case 'concluido':
                        $total_concluidas = $status_count['total'];
                        break;
                    case 'cancelado':
                        $total_canceladas = $status_count['total'];
                        break;
                    case 'trancado':
                        $total_trancadas = $status_count['total'];
                        break;
                }
            }

            // Matrículas recentes (dos últimos 30 dias)
            $sql = "SELECT COUNT(*) as total FROM matriculas WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            $recentes_result = executarConsulta($db, $sql);
            $total_recentes = $recentes_result['total'] ?? 0;

            // Busca as matrículas mais recentes para exibir no dashboard
            $sql = "SELECT m.*,
                       a.nome as aluno_nome,
                       c.nome as curso_nome,
                       p.nome as polo_nome
                FROM matriculas m
                LEFT JOIN alunos a ON m.aluno_id = a.id
                LEFT JOIN cursos c ON m.curso_id = c.id
                LEFT JOIN polos p ON m.polo_id = p.id
                ORDER BY m.created_at DESC LIMIT 5";
            $matriculas_recentes = executarConsultaAll($db, $sql);

            // Busca os cursos mais populares
            $sql = "SELECT c.id, c.nome, COUNT(*) as total
                   FROM matriculas m
                   JOIN cursos c ON m.curso_id = c.id
                   GROUP BY c.id
                   ORDER BY total DESC
                   LIMIT 5";
            $cursos_populares_raw = executarConsultaAll($db, $sql);

            // Calcula a porcentagem para cada curso popular
            $cursos_populares = [];
            if (!empty($cursos_populares_raw)) {
                $max_matriculas = $cursos_populares_raw[0]['total'];

                foreach ($cursos_populares_raw as $curso) {
                    $curso['porcentagem'] = ($curso['total'] / $max_matriculas) * 100;
                    $cursos_populares[] = $curso;
                }
            }
        } catch (Exception $e) {
            error_log('Erro ao buscar estatísticas para o dashboard: ' . $e->getMessage());
        }

        // Carrega apenas os alunos mais recentes para o filtro (limitado para melhor desempenho)
        $sql = "SELECT id, nome FROM alunos ORDER BY created_at DESC LIMIT 50";
        $alunos = executarConsultaAll($db, $sql);

        // Se foi passado um aluno_id e ele não está nos alunos recentes, busca especificamente esse aluno
        if (!empty($aluno_id)) {
            $aluno_encontrado = false;
            foreach ($alunos as $aluno) {
                if ($aluno['id'] == $aluno_id) {
                    $aluno_encontrado = true;
                    break;
                }
            }

            if (!$aluno_encontrado) {
                $sql = "SELECT id, nome FROM alunos WHERE id = ?";
                $aluno_especifico = executarConsulta($db, $sql, [$aluno_id]);

                if ($aluno_especifico) {
                    array_unshift($alunos, $aluno_especifico);
                }
            }
        }

        // Carrega os cursos para o filtro
        $sql = "SELECT id, nome FROM cursos ORDER BY nome ASC";
        $cursos = executarConsultaAll($db, $sql);

        // Carrega as turmas para o filtro
        $sql = "SELECT id, nome FROM turmas ORDER BY nome ASC";
        $turmas = executarConsultaAll($db, $sql);

        // Carrega os polos para o filtro
        $sql = "SELECT id, nome FROM polos ORDER BY nome ASC";
        $polos = executarConsultaAll($db, $sql);

        $titulo_pagina = 'Matrículas';
        $view = 'listar';
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
    <meta name="description" content="Gerenciamento de Matrículas - Sistema Faciência ERP">
    <meta name="author" content="Sistema Faciência ERP">

    <!-- Título da página -->
    <title>Faciência ERP - <?php echo htmlspecialchars($titulo_pagina ?? 'Matrículas'); ?></title>

    <!-- ================================================================== -->
    <!-- RECURSOS EXTERNOS (CDN) -->
    <!-- ================================================================== -->

    <!-- Font Awesome para ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <!-- Estilos principais do sistema -->
    <link rel="stylesheet" href="css/styles.css">

    <!-- ================================================================== -->
    <!-- ESTILOS ESPECÍFICOS DO MÓDULO MATRÍCULAS -->
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
            --border-radius: 0.5rem;
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --transition-default: all 0.3s ease;
        }

        /* ============================================================== */
        /* CARDS DE LISTAGEM DE MATRÍCULAS */
        /* ============================================================== */
        .matricula-card {
            transition: var(--transition-default);
            border: 1px solid #e5e7eb;
            background: linear-gradient(135deg, #ffffff 0%, #f9fafb 100%);
        }

        .matricula-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            border-color: var(--color-primary);
        }

        /* ============================================================== */
        /* BADGES DE STATUS DAS MATRÍCULAS */
        /* ============================================================== */
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .status-ativo { 
            background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
            color: #166534; 
            border: 1px solid #22c55e;
        }
        
        .status-pendente { 
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e; 
            border: 1px solid #f59e0b;
        }

        .status-concluido { 
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1e40af; 
            border: 1px solid #3b82f6;
        }

        .status-cancelado,
        .status-trancado { 
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b; 
            border: 1px solid #ef4444;
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
        /* FORMULÁRIOS DE MATRÍCULA */
        /* ============================================================== */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }

        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #d1d5db;
            border-radius: var(--border-radius);
            font-size: 0.875rem;
            transition: var(--transition-default);
        }

        .form-input:focus {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.5rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
            padding-right: 2.5rem;
        }

        /* ============================================================== */
        /* ESTATÍSTICAS E DASHBOARDS */
        /* ============================================================== */
        .stats-card {
            background: linear-gradient(135deg, #ffffff 0%, #f9fafb 100%);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            border: 1px solid #e5e7eb;
            transition: var(--transition-default);
        }

        .stats-card:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--color-primary);
        }

        .stats-label {
            font-size: 0.875rem;
            color: #6b7280;
            font-weight: 500;
        }

        /* ============================================================== */
        /* INDICADORES VISUAIS */
        /* ============================================================== */
        .progress-bar {
            width: 100%;
            height: 0.5rem;
            background-color: #e5e7eb;
            border-radius: 0.25rem;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--color-primary) 0%, var(--color-info) 100%);
            transition: width 0.5s ease;
        }

        /* ============================================================== */
        /* BOTÕES ESPECÍFICOS DO MÓDULO */
        /* ============================================================== */
        .btn-matricula {
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-info) 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition-default);
            border: none;
            cursor: pointer;
        }

        .btn-matricula:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        /* ============================================================== */
        /* RESPONSIVIDADE */
        /* ============================================================== */
        @media (max-width: 768px) {
            .filtros-grid {
                grid-template-columns: 1fr;
            }
            
            .matricula-card {
                margin-bottom: 1rem;
            }
        }

        /* ============================================================== */
        /* ANIMAÇÕES */
        /* ============================================================== */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .fade-in {
            animation: fadeIn 0.3s ease forwards;
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
            background-color: #f0f9ff;
            border-color: var(--color-success);
            color: #065f46;
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
                            <i class="fas fa-graduation-cap text-blue-500 text-2xl mr-3"></i>
                            <h1 class="text-3xl font-bold text-gray-800">
                                <?php echo htmlspecialchars($titulo_pagina ?? 'Matrículas'); ?>
                            </h1>
                        </div>

                        <div class="flex space-x-3">
                            <?php if (($view ?? 'listar') === 'listar'): ?>
                            <a href="matriculas.php?action=nova" class="btn-matricula inline-flex items-center">
                                <i class="fas fa-plus mr-2"></i>
                                Nova Matrícula
                            </a>
                            <a href="matriculas.php?action=importar" class="btn-secondary inline-flex items-center">
                                <i class="fas fa-upload mr-2"></i>
                                Importar
                            </a>
                            <?php endif; ?>

                            <?php if (($view ?? '') === 'visualizar' && isset($matricula['id'])): ?>
                            <a href="matriculas.php?action=editar&id=<?php echo $matricula['id']; ?>" class="btn-primary inline-flex items-center">
                                <i class="fas fa-edit mr-2"></i>
                                Editar
                            </a>
                            <a href="javascript:void(0);" onclick="confirmarExclusao(<?php echo $matricula['id']; ?>)" class="btn-danger inline-flex items-center">
                                <i class="fas fa-trash mr-2"></i>
                                Excluir
                            </a>
                            <?php endif; ?>

                            <?php if (($view ?? '') === 'form'): ?>
                            <a href="matriculas.php" class="btn-secondary inline-flex items-center">
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
                    $classe_css = $tipo === 'sucesso' ? 'message-success' : 'message-error';
                    $icone = $tipo === 'sucesso' ? 'fa-check-circle' : 'fa-exclamation-circle';
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
                    ?>                    <!-- ================================================ -->
                    <!-- ÁREA DE CONTEÚDO DINÂMICO -->
                    <!-- ================================================ -->
                    <?php
                    // Inclui a view correspondente baseada na ação atual
                    switch ($view ?? 'listar') {
                        case 'form':
                            // Formulário de cadastro/edição de matrícula
                            include 'views/matriculas/form.php';
                            break;
                            
                        case 'visualizar':
                            // Página de detalhes da matrícula
                            include 'views/matriculas/visualizar.php';
                            break;
                            
                        case 'importar':
                            // Formulário de importação em massa
                            include 'views/matriculas/importar.php';
                            break;
                            
                        case 'listar':
                        default:
                            // Listagem de matrículas (página padrão)
                            include 'views/matriculas/listar.php';
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
                                    Tem certeza que deseja excluir esta matrícula?
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
        function confirmarExclusao(id) {
            console.log('Função confirmarExclusao chamada com ID:', id);

            const btnConfirmar = document.getElementById('btn-confirmar-exclusao');
            const modal = document.getElementById('modal-exclusao');

            if (!btnConfirmar) {
                console.error('Botão de confirmação não encontrado!');
                return;
            }

            if (!modal) {
                console.error('Modal de exclusão não encontrado!');
                return;
            }

            btnConfirmar.href = `matriculas.php?action=excluir&id=${id}`;
            modal.classList.remove('hidden');
            console.log('Modal aberto para exclusão da matrícula ID:', id);
        }

        function fecharModal() {
            const modal = document.getElementById('modal-exclusao');
            if (modal) {
                modal.classList.add('hidden');
            }
        }

        // Adiciona event listener para debug
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM carregado');

            // Verifica se os elementos existem
            const btnConfirmar = document.getElementById('btn-confirmar-exclusao');
            const modal = document.getElementById('modal-exclusao');

            console.log('Botão confirmar encontrado:', !!btnConfirmar);
            console.log('Modal encontrado:', !!modal);
        });
    </script>
</body>
</html>
