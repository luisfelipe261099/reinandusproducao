<?php
/**
 * ============================================================================
 * GERENCIAMENTO DE ALUNOS - SISTEMA FACIÊNCIA ERP
 * ============================================================================
 *
 * Este arquivo é responsável por todas as operações relacionadas aos alunos
 * do sistema acadêmico, incluindo cadastro, edição, listagem e importação.
 *
 * @author Sistema Faciência ERP
 * @version 2.0
 * @since 2024
 * @updated 2025-06-10
 *
 * Funcionalidades Principais:
 * - Cadastro e edição de alunos
 * - Listagem com filtros avançados
 * - Visualização de detalhes do aluno
 * - Importação em massa via Excel/CSV
 * - Busca inteligente por múltiplos campos
 * - Gestão de matrículas vinculadas
 * - Sistema de logs para auditoria
 *
 * Melhorias Implementadas:
 * - Validação robusta de dados
 * - Tratamento de exceções
 * - Sistema de cache para performance
 * - Importação otimizada com validação prévia
 * - Interface responsiva e intuitiva
 * - Logs detalhados de todas as operações
 *
 * ============================================================================
 */

// ============================================================================
// INICIALIZAÇÃO E SEGURANÇA
// ============================================================================

try {
    // Inicializa o sistema com todas as dependências necessárias
    require_once __DIR__ . '/includes/init.php';

    // Verifica se as funções essenciais estão disponíveis
    if (!function_exists('exigirLogin')) {
        die('Erro: Sistema não inicializado corretamente. Contate o administrador.');
    }

    // Verifica se o usuário está autenticado no sistema
    exigirLogin();

    // Verifica se o usuário tem permissão para acessar o módulo de alunos
    exigirPermissao('alunos');

    // Registra o acesso ao módulo para auditoria
    if (function_exists('registrarLog')) {
        registrarLog(
            'alunos',
            'acesso',
            'Usuário acessou o módulo de alunos',
            $_SESSION['user_id'] ?? null
        );
    }

} catch (Exception $e) {
    // Em caso de erro crítico na inicialização
    error_log('Erro crítico na inicialização do módulo alunos: ' . $e->getMessage());
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
        $resultado = $db->fetchOne($sql, $params);
        return $resultado ?: $default;
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
    // Se não há conexão com o banco, retorna array padrão
    if (!$db) {
        return $default;
    }
    
    try {
        $resultado = $db->fetchAll($sql, $params);
        return $resultado ?: $default;
    } catch (Exception $e) {
        // Registra o erro no log do sistema para debugging
        error_log('Erro na consulta SQL: ' . $e->getMessage() . ' | SQL: ' . $sql);
        error_log('Parâmetros: ' . print_r($params, true));
        return $default;
    }
}

/**
 * Formata datas vindas de planilhas Excel/CSV para o formato do banco
 * Suporta múltiplos formatos de entrada
 *
 * @param string $data Data em formato variado
 * @return string Data formatada para MySQL (YYYY-MM-DD) ou string vazia
 */
function formatarDataPlanilha($data) {
    if (empty($data)) return '';

    // Verifica se a data está no formato DD/MM/AAAA ou M/DD/YYYY (Excel)
    if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $data)) {
        $data_parts = explode('/', $data);
        // Verifica se está no formato americano (M/DD/YYYY)
        if (count($data_parts) == 3 && $data_parts[0] <= 12 && $data_parts[1] > 12) {
            return $data_parts[2] . '-' . str_pad($data_parts[0], 2, '0', STR_PAD_LEFT) . '-' . str_pad($data_parts[1], 2, '0', STR_PAD_LEFT);
        } else {
            // Formato brasileiro (DD/MM/AAAA)
            return $data_parts[2] . '-' . str_pad($data_parts[1], 2, '0', STR_PAD_LEFT) . '-' . str_pad($data_parts[0], 2, '0', STR_PAD_LEFT);
        }
    }
    // Verifica se a data está no formato AAAA-MM-DD
    elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
        return $data;
    }    return ''; // Retorna vazio se não estiver em um formato válido
}

// ============================================================================
// PROCESSAMENTO DE AÇÕES E INICIALIZAÇÃO DE VARIÁVEIS
// ============================================================================

// Obtém a ação solicitada via GET ou POST (padrão: 'listar')
$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : 'listar');

// Inicializa variáveis padrão para controle da view
$view = 'listar';                    // View padrão (listagem)
$titulo_pagina = 'Alunos';          // Título padrão da página
$alunos = [];                       // Array de alunos (para listagem)
$aluno = [];                        // Dados de um aluno específico
$polos = [];                        // Lista de polos disponíveis
$cursos = [];                       // Lista de cursos disponíveis
$turmas = [];                       // Lista de turmas disponíveis
$matriculas = [];                   // Matrículas do aluno
$documentos = [];                   // Documentos do aluno
$mensagens_erro = [];               // Mensagens de erro para exibição

// Variáveis auxiliares para evitar warnings
$polo_nome = '';                    // Nome do polo para exibição
$curso_nome = '';                   // Nome do curso para exibição
$turma_nome = '';                   // Nome da turma para exibição
$total_alunos = 0;                  // Total de alunos para paginação
$total_paginas = 1;                 // Total de páginas para paginação
$pagina = 1;                        // Página atual
$status = 'todos';                  // Status do filtro
$polo_id = null;                    // ID do polo filtrado
$curso_id = null;                   // ID do curso filtrado
$turma_id = null;                   // ID da turma filtrada

// Processa a ação solicitada
switch ($action) {
    case 'novo':
        // Exibe o formulário para adicionar um novo aluno
        $titulo_pagina = 'Novo Aluno';
        $view = 'form';
        $aluno = []; // Inicializa um aluno vazio

        // Carrega os polos, cursos e turmas para o formulário
        $polos = executarConsultaAll($db, "SELECT id, nome FROM polos ORDER BY nome ASC");
        $cursos = executarConsultaAll($db, "SELECT id, nome FROM cursos ORDER BY nome ASC");
        $turmas = executarConsultaAll($db, "SELECT id, nome, curso_id FROM turmas ORDER BY nome ASC");
        break;

    case 'editar':
        // Exibe o formulário para editar um aluno existente
        $id = $_GET['id'] ?? 0;

        // Busca o aluno pelo ID
        $sql = "SELECT * FROM alunos WHERE id = ?";
        $aluno = executarConsulta($db, $sql, [$id], []);

        if (!$aluno) {
            // Aluno não encontrado, redireciona para a listagem
            setMensagem('erro', 'Aluno não encontrado.');
            redirect('alunos.php');
        }

        // Carrega os polos, cursos e turmas para o formulário
        $polos = executarConsultaAll($db, "SELECT id, nome FROM polos ORDER BY nome ASC");
        $cursos = executarConsultaAll($db, "SELECT id, nome FROM cursos ORDER BY nome ASC");
        $turmas = executarConsultaAll($db, "SELECT id, nome, curso_id FROM turmas ORDER BY nome ASC");

        $titulo_pagina = 'Editar Aluno';
        $view = 'form';
        break;

    case 'salvar':
        // Salva os dados do aluno (novo ou existente)
        if (!isPost()) {
            // Método não permitido
            setMensagem('erro', 'Método não permitido.');
            redirect('alunos.php');
        }

        // Obtém os dados do formulário
        $id = $_POST['id'] ?? null;
        $nome = $_POST['nome'] ?? '';
        $email = $_POST['email'] ?? '';
        $cpf = $_POST['cpf'] ?? '';
        $telefone = $_POST['telefone'] ?? '';
        $data_nascimento = $_POST['data_nascimento'] ?? '';
        $endereco = $_POST['endereco'] ?? '';
        $cidade = $_POST['cidade'] ?? '';
        $estado = $_POST['estado'] ?? '';
        $cep = $_POST['cep'] ?? '';
        $id_legado = $_POST['id_legado'] ?? '';
        $status = $_POST['status'] ?? 'ativo';
        $polo_id = $_POST['polo_id'] ?? null;
        $curso_id = $_POST['curso_id'] ?? null;
        $turma_id = $_POST['turma_id'] ?? null;

        // Valida os dados
        $erros = [];

        if (empty($nome)) {
            $erros[] = 'O nome é obrigatório.';
        }

        if (empty($email)) {
            $erros[] = 'O e-mail é obrigatório.';
        } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erros[] = 'O e-mail informado é inválido.';
        }

        if (empty($cpf)) {
            $erros[] = 'O CPF é obrigatório.';
        }

        if (!empty($erros)) {
            // Há erros de validação, exibe o formulário novamente
            $titulo_pagina = $id ? 'Editar Aluno' : 'Novo Aluno';
            $view = 'form';
            $aluno = $_POST;
            $mensagens_erro = $erros;

            // Carrega os polos, cursos e turmas para o formulário
            $polos = executarConsultaAll($db, "SELECT id, nome FROM polos ORDER BY nome ASC");
            $cursos = executarConsultaAll($db, "SELECT id, nome FROM cursos ORDER BY nome ASC");
            $turmas = executarConsultaAll($db, "SELECT id, nome, curso_id FROM turmas ORDER BY nome ASC");

            break;
        }

        // Prepara os dados para salvar
        $dados = [
            'nome' => $nome,
            'email' => $email,
            'telefone' => $telefone,
            'data_nascimento' => $data_nascimento,
            'endereco' => $endereco,
            'cidade' => $cidade,
            'estado' => $estado,
            'cep' => $cep,
            'id_legado' => $id_legado,
            'status' => $status,
            'polo_id' => $polo_id ?: null,
            'curso_id' => $curso_id ?: null,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // Adiciona CPF apenas se não estiver vazio
        if (!empty($cpf)) {
            $dados['cpf'] = $cpf;
        }

        // Adiciona turma_id apenas se for fornecido
        if (!empty($turma_id)) {
            $dados['turma_id'] = $turma_id;
        }

        try {
            if ($id) {
                // Atualiza um aluno existente
                $db->update('alunos', $dados, 'id = ?', [$id]);

                // Registra o log
                registrarLog(
                    'alunos',
                    'editar',
                    "Aluno {$nome} (ID: {$id}) atualizado",
                    $id,
                    'alunos'
                );

                setMensagem('sucesso', 'Aluno atualizado com sucesso.');
            } else {
                // Adiciona a data de criação
                $dados['created_at'] = date('Y-m-d H:i:s');

                // Insere um novo aluno
                $id = $db->insert('alunos', $dados);

                // Registra o log
                registrarLog(
                    'alunos',
                    'criar',
                    "Aluno {$nome} (ID: {$id}) criado",
                    $id,
                    'alunos'
                );

                setMensagem('sucesso', 'Aluno adicionado com sucesso.');
            }

            // Redireciona para a listagem
            redirect('alunos.php');
        } catch (Exception $e) {
            // Erro ao salvar
            $titulo_pagina = $id ? 'Editar Aluno' : 'Novo Aluno';
            $view = 'form';
            $aluno = $_POST;
            $mensagens_erro = ['Erro ao salvar o aluno: ' . $e->getMessage()];

            // Carrega os polos, cursos e turmas para o formulário
            $polos = executarConsultaAll($db, "SELECT id, nome FROM polos ORDER BY nome ASC");
            $cursos = executarConsultaAll($db, "SELECT id, nome FROM cursos ORDER BY nome ASC");
            $turmas = executarConsultaAll($db, "SELECT id, nome, curso_id FROM turmas ORDER BY nome ASC");
        }
        break;

    case 'excluir':
        // Exclui um aluno
        $id = $_GET['id'] ?? 0;

        // Verifica se o usuário tem permissão para excluir
        exigirPermissao('alunos', 'excluir');

        // Busca o aluno pelo ID
        $sql = "SELECT * FROM alunos WHERE id = ?";
        $aluno = executarConsulta($db, $sql, [$id], []);

        if (!$aluno) {
            // Aluno não encontrado, redireciona para a listagem
            setMensagem('erro', 'Aluno não encontrado.');
            redirect('alunos.php');
        }

        try {
            // Exclui o aluno
            $db->delete('alunos', 'id = ?', [$id]);

            // Registra o log
            registrarLog(
                'alunos',
                'excluir',
                "Aluno {$aluno['nome']} (ID: {$id}) excluído",
                $id,
                'alunos'
            );

            setMensagem('sucesso', 'Aluno excluído com sucesso.');
        } catch (Exception $e) {
            // Erro ao excluir
            setMensagem('erro', 'Erro ao excluir o aluno: ' . $e->getMessage());
        }

        // Redireciona para a listagem
        redirect('alunos.php');
        break;

    case 'visualizar':
        // Exibe os detalhes de um aluno
        $id = $_GET['id'] ?? 0;

        // Busca o aluno pelo ID
        $sql = "SELECT * FROM alunos WHERE id = ?";
        $aluno = executarConsulta($db, $sql, [$id], []);

        if (!$aluno) {
            // Aluno não encontrado, redireciona para a listagem
            setMensagem('erro', 'Aluno não encontrado.');
            redirect('alunos.php');
        }

        // Busca as matrículas do aluno
        $sql = "SELECT m.*, c.nome as curso_nome, t.nome as turma_nome, p.nome as polo_nome
                FROM matriculas m
                LEFT JOIN cursos c ON m.curso_id = c.id
                LEFT JOIN turmas t ON m.turma_id = t.id
                LEFT JOIN polos p ON m.polo_id = p.id
                WHERE m.aluno_id = ?
                ORDER BY m.created_at DESC";
        $matriculas = executarConsultaAll($db, $sql, [$id]);

        // Busca os documentos do aluno
        $sql = "SELECT sd.*, td.nome as tipo_documento_nome
                FROM solicitacoes_documentos sd
                LEFT JOIN tipos_documentos td ON sd.tipo_documento_id = td.id
                WHERE sd.aluno_id = ?
                ORDER BY sd.created_at DESC";
        $documentos = executarConsultaAll($db, $sql, [$id]);

        // Busca informações do polo, curso e turma do aluno
        if (!empty($aluno['polo_id'])) {
            $sql = "SELECT nome FROM polos WHERE id = ?";
            $polo = executarConsulta($db, $sql, [$aluno['polo_id']]);
            $polo_nome = $polo ? $polo['nome'] : null;
        }

        if (!empty($aluno['curso_id'])) {
            $sql = "SELECT nome FROM cursos WHERE id = ?";
            $curso = executarConsulta($db, $sql, [$aluno['curso_id']]);
            $curso_nome = $curso ? $curso['nome'] : null;
        }

        if (!empty($aluno['turma_id'])) {
            $sql = "SELECT nome FROM turmas WHERE id = ?";
            $turma = executarConsulta($db, $sql, [$aluno['turma_id']]);
            $turma_nome = $turma ? $turma['nome'] : null;
        }

        $titulo_pagina = 'Detalhes do Aluno';
        $view = 'visualizar';
        break;

    case 'buscar':
        // Busca alunos por termo
        $termo = $_GET['termo'] ?? '';
        $campo = $_GET['campo'] ?? 'nome';
        $status = $_GET['status'] ?? 'todos';
        $polo_id = $_GET['polo_id'] ?? null;
        $curso_id = $_GET['curso_id'] ?? null;
        $turma_id = $_GET['turma_id'] ?? null;

        if (empty($termo)) {
            redirect('alunos.php');
        }

        // Carrega os polos, cursos e turmas para os filtros
        $polos = executarConsultaAll($db, "SELECT id, nome FROM polos ORDER BY nome ASC");
        $cursos = executarConsultaAll($db, "SELECT id, nome FROM cursos ORDER BY nome ASC");
        $turmas = executarConsultaAll($db, "SELECT id, nome, curso_id FROM turmas ORDER BY nome ASC");

        // Define os campos permitidos para busca
        $campos_permitidos = ['nome', 'email', 'cpf', 'id_legado'];

        if (!in_array($campo, $campos_permitidos)) {
            $campo = 'nome';
        }

        // Monta a consulta SQL
        $where = [];
        $params = [];
        $joins = [];

        // Adiciona a condição de busca
        $where[] = "a.{$campo} LIKE ?";
        $params[] = "%{$termo}%";

        if ($status !== 'todos') {
            $where[] = "a.status = ?";
            $params[] = $status;
        }

        if ($polo_id) {
            $where[] = "a.polo_id = ?";
            $params[] = $polo_id;
        }

        if ($curso_id) {
            // Se temos um curso_id, precisamos verificar se o aluno está matriculado neste curso
            $joins[] = "LEFT JOIN matriculas m ON a.id = m.aluno_id";
            $where[] = "m.curso_id = ?";
            $params[] = $curso_id;
        }

        if ($turma_id) {
            // Se temos um turma_id, precisamos verificar se o aluno está matriculado nesta turma
            if (!in_array("LEFT JOIN matriculas m ON a.id = m.aluno_id", $joins)) {
                $joins[] = "LEFT JOIN matriculas m ON a.id = m.aluno_id";
            }
            $where[] = "m.turma_id = ?";
            $params[] = $turma_id;
        }

        // Monta a cláusula WHERE
        $whereClause = "WHERE " . implode(" AND ", $where);

        // Monta a cláusula JOIN
        $joinClause = implode(" ", $joins);

        // Consulta principal com DISTINCT para evitar duplicatas quando usamos JOINs
        $sql = "SELECT DISTINCT a.* FROM alunos a {$joinClause} {$whereClause} ORDER BY a.nome ASC";
        $alunos = executarConsultaAll($db, $sql, $params);

        $titulo_pagina = 'Resultado da Busca';
        $view = 'listar';
        break;

    case 'importar':
        // Exibe o formulário para importar alunos
        $titulo_pagina = 'Importar Alunos';
        $view = 'importar';

        // Carrega os polos, cursos e turmas para o formulário
        $polos = executarConsultaAll($db, "SELECT id, nome FROM polos ORDER BY nome ASC");
        $cursos = executarConsultaAll($db, "SELECT id, nome FROM cursos ORDER BY nome ASC");
        $turmas = executarConsultaAll($db, "SELECT id, nome, curso_id FROM turmas ORDER BY nome ASC");
        break;

    case 'validar_importacao':
    case 'processar_importacao':
        // Determina se é apenas validação ou importação real
        $apenas_validar = ($action === 'validar_importacao');

        // Processa a importação ou validação de alunos
        if (!isPost()) {
            // Método não permitido
            setMensagem('erro', 'Método não permitido.');
            redirect('alunos.php');
        }

        // Verifica se foi enviado um arquivo
        if (!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
            setMensagem('erro', 'Nenhum arquivo foi enviado ou ocorreu um erro no upload.');
            redirect('alunos.php?action=importar');
        }

        // Verifica o tipo de arquivo
        $extensao = strtolower(pathinfo($_FILES['arquivo']['name'], PATHINFO_EXTENSION));
        if (!in_array($extensao, ['xlsx', 'xls', 'csv'])) {
            setMensagem('erro', 'Formato de arquivo inválido. Apenas arquivos Excel (.xlsx, .xls) ou CSV (.csv) são permitidos.');
            redirect('alunos.php?action=importar');
        }

        // Obtém os dados do formulário
        $polo_id = $_POST['polo_id'] ?? null;
        $curso_id = $_POST['curso_id'] ?? null;
        $turma_id = $_POST['turma_id'] ?? null;
        $atualizar_existentes = isset($_POST['atualizar_existentes']) && $_POST['atualizar_existentes'] == '1';
        $identificar_por_email = isset($_POST['identificar_por_email']) && $_POST['identificar_por_email'] == '1';

        // Se tiver curso_id mas não tiver turma_id, tenta encontrar uma turma ativa para o curso
        if (!empty($curso_id) && empty($turma_id)) {
            $sql = "SELECT id FROM turmas WHERE curso_id = ? AND status = 'ativo' LIMIT 1";
            $turma = executarConsulta($db, $sql, [$curso_id]);
            if ($turma) {
                $turma_id = $turma['id'];
                error_log("Turma encontrada automaticamente: {$turma_id} para o curso {$curso_id}");
            } else {
                error_log("Nenhuma turma ativa encontrada para o curso {$curso_id}. Criando uma turma padrão.");

                // Obtém o nome do curso para criar uma turma padrão
                $sql = "SELECT nome FROM cursos WHERE id = ?";
                $curso = executarConsulta($db, $sql, [$curso_id]);

                if ($curso) {
                    // Cria uma turma padrão para o curso
                    $dados_turma = [
                        'nome' => 'Turma Padrão - ' . $curso['nome'],
                        'curso_id' => $curso_id,
                        'polo_id' => $polo_id,
                        'data_inicio' => date('Y-m-d'),
                        'data_fim' => date('Y-m-d', strtotime('+1 year')),
                        'vagas_totais' => 100,
                        'vagas_preenchidas' => 0,
                        'status' => 'ativo',
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];

                    // Insere a turma
                    $turma_id = $db->insert('turmas', $dados_turma);
                    error_log("Turma padrão criada com ID: {$turma_id}");
                }
            }
        }

        // Diretório temporário para o upload
        $temp_dir = sys_get_temp_dir();
        $temp_file = $temp_dir . '/' . uniqid('import_') . '.' . $extensao;

        // Move o arquivo para o diretório temporário
        if (!move_uploaded_file($_FILES['arquivo']['tmp_name'], $temp_file)) {
            setMensagem('erro', 'Falha ao processar o arquivo. Tente novamente.');
            redirect('alunos.php?action=importar');
        }

        try {
            // Carrega a biblioteca PHPSpreadsheet
            require_once 'vendor/autoload.php';

            // Cria o leitor de acordo com o tipo de arquivo
            if ($extensao === 'csv') {
                $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
                $reader->setDelimiter(',');
                $reader->setEnclosure('"');
                $reader->setSheetIndex(0);
            } elseif ($extensao === 'xlsx') {
                $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            } else {
                $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
            }

            // Carrega o arquivo
            $spreadsheet = $reader->load($temp_file);
            $worksheet = $spreadsheet->getActiveSheet();

            // Obtém os dados da planilha
            $dados = $worksheet->toArray();

            // Remove o cabeçalho
            $cabecalho = array_shift($dados);

            // Inicializa contadores
            $total = 0;
            $inseridos = 0;
            $atualizados = 0;
            $ignorados = 0;
            $erros = 0;
            $mensagens_erro = [];            // Inicia uma transação apenas se não for validação
            if (!$apenas_validar) {
                error_log("IMPORTAÇÃO DEBUG: Iniciando transação para importação real");
                $db->beginTransaction();
            } else {
                error_log("IMPORTAÇÃO DEBUG: Modo validação - transação não iniciada");
            }

            // Array para armazenar resultados da validação
            $resultados_validacao = [];

            // Processa cada linha da planilha
            foreach ($dados as $linha) {
                $total++;

                // Verifica se a linha tem dados
                if (empty($linha[0])) {
                    continue;
                }

                // Mapeia os dados da planilha para os campos do banco
                // Seguindo o modelo de importação POLOS com a estrutura completa
                $nome = trim($linha[0] ?? '');
                $cpf = trim($linha[1] ?? '');
                $rg = trim($linha[2] ?? '');
                $orgao_expedidor = trim($linha[3] ?? '');
                $nacionalidade = trim($linha[4] ?? '');
                $estado_civil = trim($linha[5] ?? '');
                $sexo = trim($linha[6] ?? '');
                $data_nascimento = trim($linha[7] ?? '');
                $naturalidade = trim($linha[8] ?? '');
                $curso_id_planilha = trim($linha[9] ?? ''); // Curso ID da planilha
                $curso_inicio = trim($linha[10] ?? '');
                $curso_fim = trim($linha[11] ?? '');
                $situacao = trim($linha[12] ?? '');
                $email = trim($linha[13] ?? '');
                $endereco = trim($linha[14] ?? '');
                $complemento = trim($linha[15] ?? '');
                $cidade = trim($linha[16] ?? '');
                $cep = trim($linha[17] ?? '');
                $nome_social = trim($linha[18] ?? '');
                $celular = trim($linha[19] ?? '');
                $bairro = trim($linha[20] ?? '');
                $data_ingresso = trim($linha[21] ?? '');
                $previsao_conclusao = trim($linha[22] ?? '');
                $mono_titulo = trim($linha[23] ?? '');
                $mono_data = trim($linha[24] ?? '');
                $mono_nota = trim($linha[25] ?? '');
                $mono_prazo = trim($linha[26] ?? '');
                $bolsa = trim($linha[27] ?? '');
                $desconto = trim($linha[28] ?? '');

                // Campos adicionais que podem não estar na planilha
                $estado = ''; // Será preenchido se disponível
                $telefone = ''; // Será preenchido se disponível

                // Se o curso_id não foi fornecido no formulário, tenta usar o da planilha
                if (empty($curso_id) && !empty($curso_id_planilha)) {
                    // Verifica se o curso existe
                    $sql = "SELECT id FROM cursos WHERE id = ? OR nome LIKE ?";
                    $curso_encontrado = $db->fetchOne($sql, [$curso_id_planilha, "%{$curso_id_planilha}%"]);
                    if ($curso_encontrado) {
                        $curso_id = $curso_encontrado['id'];
                    }
                }

                // Usamos a função formatarDataPlanilha definida no início do arquivo

                // Formata todas as datas
                $data_nascimento = formatarDataPlanilha($data_nascimento);
                $curso_inicio = formatarDataPlanilha($curso_inicio);
                $curso_fim = formatarDataPlanilha($curso_fim);

                // Formata a data de ingresso
                $data_ingresso = formatarDataPlanilha($data_ingresso);

                // Sempre usa o valor de curso_inicio para data_ingresso se curso_inicio não estiver vazio
                // Isso garante que a data de ingresso seja sempre exibida na visualização do aluno
                if (!empty($curso_inicio)) {
                    $data_ingresso = $curso_inicio;
                }

                $previsao_conclusao = formatarDataPlanilha($previsao_conclusao);
                $mono_data = formatarDataPlanilha($mono_data);
                $mono_prazo = formatarDataPlanilha($mono_prazo);

                // Formata valores numéricos
                if (!empty($mono_nota)) {
                    $mono_nota = str_replace(',', '.', $mono_nota); // Substitui vírgula por ponto
                    if (!is_numeric($mono_nota)) {
                        $mono_nota = '';
                    }
                }

                if (!empty($bolsa)) {
                    $bolsa = str_replace(',', '.', $bolsa); // Substitui vírgula por ponto
                    if (!is_numeric($bolsa)) {
                        $bolsa = '';
                    }
                }

                if (!empty($desconto)) {
                    $desconto = str_replace(',', '.', $desconto); // Substitui vírgula por ponto
                    if (!is_numeric($desconto)) {
                        $desconto = '';
                    }
                }

                // Mapeia o estado civil para o ID correspondente
                $estado_civil_id = null;
                if (!empty($estado_civil)) {
                    $estado_civil_lower = strtolower($estado_civil);
                    if (strpos($estado_civil_lower, 'solteiro') !== false) {
                        $estado_civil_id = 1; // ID para solteiro
                    } elseif (strpos($estado_civil_lower, 'casado') !== false) {
                        $estado_civil_id = 2; // ID para casado
                    } elseif (strpos($estado_civil_lower, 'divorciado') !== false) {
                        $estado_civil_id = 3; // ID para divorciado
                    } elseif (strpos($estado_civil_lower, 'viuvo') !== false || strpos($estado_civil_lower, 'viúvo') !== false) {
                        $estado_civil_id = 4; // ID para viúvo
                    }
                }

                // Mapeia o sexo para o formato do banco
                if (!empty($sexo)) {
                    $sexo = strtoupper(substr($sexo, 0, 1)) === 'F' ? 'feminino' :
                           (strtoupper(substr($sexo, 0, 1)) === 'M' ? 'masculino' : 'outro');
                }

                // Inicializa o status da linha
                $status_linha = 'ok';
                $mensagem_linha = '';
                $tipo_operacao = '';

                // Valida os dados obrigatórios
                if (empty($nome)) {
                    $erros++;
                    $mensagens_erro[] = "Linha {$total}: Nome é obrigatório.";
                    $status_linha = 'erro';
                    $mensagem_linha = 'Nome é obrigatório';

                    // Adiciona ao resultado da validação
                    $resultados_validacao[] = [
                        'linha' => $total,
                        'nome' => $nome,
                        'email' => $email,
                        'cpf' => $cpf,
                        'status' => $status_linha,
                        'mensagem' => $mensagem_linha,
                        'operacao' => ''
                    ];

                    continue;
                }                // Verifica se o aluno já existe pelo CPF ou email
                $aluno_existente = null;
                if (!empty($cpf)) {
                    // Remove formatação do CPF para comparação
                    $cpf_limpo = preg_replace('/[^0-9]/', '', $cpf);
                    $sql = "SELECT id, nome, cpf FROM alunos WHERE REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', '') = ?";
                    $aluno_existente = executarConsulta($db, $sql, [$cpf_limpo]);
                    
                    // Log para debug
                    error_log("IMPORTAÇÃO DEBUG: Verificando CPF '{$cpf}' (limpo: '{$cpf_limpo}') - Encontrado: " . ($aluno_existente ? "SIM (ID: {$aluno_existente['id']})" : "NÃO"));
                }

                // Se não encontrou pelo CPF e a opção de identificar por email está ativada, tenta encontrar pelo email
                if (!$aluno_existente && $identificar_por_email && !empty($email)) {
                    $sql = "SELECT id, nome, email FROM alunos WHERE email = ?";
                    $aluno_existente = executarConsulta($db, $sql, [$email]);

                    if ($aluno_existente) {
                        $mensagem_linha = "Aluno encontrado pelo email (CPF não corresponde)";
                        error_log("IMPORTAÇÃO DEBUG: Aluno encontrado pelo email '{$email}' - ID: {$aluno_existente['id']}");
                    }
                }
                
                // Log adicional para debug
                if (!$aluno_existente) {
                    error_log("IMPORTAÇÃO DEBUG: Novo aluno será inserido - Nome: '{$nome}', CPF: '{$cpf}', Email: '{$email}'");
                }

                // Prepara os dados para salvar
                $dados_aluno = [
                    'nome' => $nome,
                    'email' => $email,
                    'polo_id' => $polo_id ?: null,
                    'curso_id' => $curso_id ?: null,
                    'status' => !empty($situacao) && strtolower($situacao) === 'inativo' ? 'inativo' : 'ativo',
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                // Adiciona todos os campos da planilha se não estiverem vazios
                $campos_adicionais = [
                    'endereco' => $endereco,
                    'cidade' => $cidade,
                    'data_nascimento' => $data_nascimento,
                    'estado' => $estado,
                    'rg' => $rg,
                    'orgao_expedidor' => $orgao_expedidor, // Adicionado o campo orgao_expedidor
                    'sexo' => $sexo,
                    'estado_civil_id' => $estado_civil_id,
                    'numero' => $complemento, // Usando o campo complemento como número
                    'cep' => $cep,
                    'nome_social' => $nome_social,
                    'celular' => $celular,
                    'bairro' => $bairro,
                    'data_ingresso' => $data_ingresso,
                    'curso_inicio' => $curso_inicio,
                    'curso_fim' => $curso_fim,
                    'previsao_conclusao' => $previsao_conclusao,
                    'mono_titulo' => $mono_titulo,
                    'mono_data' => $mono_data,
                    'mono_nota' => $mono_nota,
                    'mono_prazo' => $mono_prazo,
                    'bolsa' => $bolsa,
                    'desconto' => $desconto
                ];

                // Adiciona cada campo apenas se não estiver vazio
                foreach ($campos_adicionais as $campo => $valor) {
                    if (!empty($valor)) {
                        $dados_aluno[$campo] = $valor;
                    }
                }

                // Adiciona telefone apenas se não estiver vazio
                if (isset($telefone) && !empty($telefone)) {
                    $dados_aluno['telefone'] = $telefone;
                }

                // Adiciona naturalidade se não estiver vazia
                if (!empty($naturalidade)) {
                    // Tenta encontrar a cidade na tabela de cidades
                    try {
                        $sql = "SELECT id FROM cidades WHERE nome LIKE ?";
                        $cidade_encontrada = $db->fetchOne($sql, ["%{$naturalidade}%"]);
                        if ($cidade_encontrada) {
                            $dados_aluno['naturalidade_id'] = $cidade_encontrada['id'];
                        }
                    } catch (Exception $e) {
                        // Se não conseguir encontrar, apenas ignora
                        error_log("Não foi possível encontrar a cidade '{$naturalidade}': " . $e->getMessage());
                    }
                }

                // Define campos de entrega de documentos
                if (!empty($rg)) {
                    $dados_aluno['entregou_rg'] = 1;
                }

                if (!empty($cpf)) {
                    $dados_aluno['entregou_cpf'] = 1;
                }

                // Código removido pois já tratamos as datas anteriormente

                // Adiciona CPF apenas se não estiver vazio
                if (!empty($cpf)) {
                    // Formata o CPF para o padrão XXX.XXX.XXX-XX se não estiver formatado
                    if (preg_match('/^\d{11}$/', $cpf)) {
                        $cpf = substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
                    }
                    $dados_aluno['cpf'] = $cpf;
                }

                // Adiciona turma_id apenas se for fornecido
                if (!empty($turma_id)) {
                    $dados_aluno['turma_id'] = $turma_id;
                }

                try {
                    if ($aluno_existente && $atualizar_existentes) {
                        // Define o tipo de operação
                        $tipo_operacao = 'atualizar';
                        $mensagem_linha = "Será atualizado (ID: {$aluno_existente['id']}, Nome atual: {$aluno_existente['nome']})";

                        // Se não for apenas validação, atualiza o aluno
                        if (!$apenas_validar) {
                            // Atualiza o aluno existente
                            $db->update('alunos', $dados_aluno, 'id = ?', [$aluno_existente['id']]);

                            // Verifica se já existe uma matrícula para este aluno no curso
                            if (!empty($curso_id)) {
                                try {
                                    $sql = "SELECT id FROM matriculas WHERE aluno_id = ? AND curso_id = ?";
                                    $matricula_existente = $db->fetchOne($sql, [$aluno_existente['id'], $curso_id]);

                                    if ($matricula_existente) {
                                        // Atualiza a matrícula existente se necessário
                                        $dados_atualizacao = [
                                            'updated_at' => date('Y-m-d H:i:s')
                                        ];

                                        // Atualiza o polo_id se fornecido
                                        if (!empty($polo_id)) {
                                            $dados_atualizacao['polo_id'] = $polo_id;
                                        }

                                        // Atualiza a turma_id se fornecida
                                        if (!empty($turma_id)) {
                                            $dados_atualizacao['turma_id'] = $turma_id;
                                        }

                                        // Atualiza a matrícula
                                        $db->update('matriculas', $dados_atualizacao, 'id = ?', [$matricula_existente['id']]);
                                        error_log("Matrícula ID {$matricula_existente['id']} atualizada para o aluno ID {$aluno_existente['id']}");
                                    } else {
                                        // Cria uma nova matrícula
                                        $dados_matricula = [
                                            'aluno_id' => $aluno_existente['id'],
                                            'curso_id' => $curso_id,
                                            'polo_id' => $polo_id ?: null,
                                            'turma_id' => $turma_id ?: null,
                                            'data_matricula' => date('Y-m-d'),
                                            'data_inicio' => !empty($curso_inicio) ? $curso_inicio : date('Y-m-d'),
                                            'data_fim' => !empty($curso_fim) ? $curso_fim : date('Y-m-d', strtotime('+1 year')),
                                            'status' => 'ativo',
                                            'forma_pagamento' => 'A definir',
                                            'valor_total' => 0,
                                            'observacoes' => 'Matrícula criada automaticamente via importação',
                                            'created_at' => date('Y-m-d H:i:s'),
                                            'updated_at' => date('Y-m-d H:i:s')
                                        ];



                                        // Insere a matrícula
                                        $matricula_id = $db->insert('matriculas', $dados_matricula);
                                        error_log("Nova matrícula criada para o aluno ID {$aluno_existente['id']} no curso ID {$curso_id}");
                                    }
                                } catch (Exception $e) {
                                    error_log("Erro ao processar matrícula para o aluno ID {$aluno_existente['id']}: " . $e->getMessage());
                                }
                            }

                            // Registra o log
                            registrarLog(
                                'alunos',
                                'editar',
                                "Aluno {$nome} (ID: {$aluno_existente['id']}) atualizado via importação",
                                $aluno_existente['id'],
                                'alunos'
                            );
                        }

                        $atualizados++;
                    } elseif ($aluno_existente && !$atualizar_existentes) {
                        // Aluno existe mas a opção de atualizar está desativada
                        $tipo_operacao = 'ignorar';
                        $mensagem_linha = "Ignorado (aluno já existe e a opção de atualizar está desativada)";

                        $ignorados++;
                    } else {
                        // Define o tipo de operação
                        $tipo_operacao = 'inserir';
                        $mensagem_linha = "Será inserido como novo aluno";                        // Se não for apenas validação, insere o aluno
                        if (!$apenas_validar) {
                            // Log para debug
                            error_log("IMPORTAÇÃO DEBUG: Inserindo novo aluno - Nome: '{$nome}', CPF: '{$cpf}', Email: '{$email}'");
                            error_log("IMPORTAÇÃO DEBUG: Dados do aluno: " . json_encode($dados_aluno));
                            
                            // Adiciona a data de criação
                            $dados_aluno['created_at'] = date('Y-m-d H:i:s');

                            // Insere um novo aluno
                            $id = $db->insert('alunos', $dados_aluno);
                            
                            // Log do resultado
                            error_log("IMPORTAÇÃO DEBUG: Aluno inserido com ID: " . ($id ? $id : 'FALHOU'));

                            // Cria uma matrícula para o aluno se curso_id e turma_id estiverem definidos
                            if (!empty($curso_id)) {                                try {
                                    // Log para debug
                                    error_log("IMPORTAÇÃO DEBUG: Criando matrícula para aluno ID {$id} no curso ID {$curso_id}");
                                    
                                    // Prepara os dados da matrícula
                                    $dados_matricula = [
                                        'aluno_id' => $id,
                                        'curso_id' => $curso_id,
                                        'polo_id' => $polo_id ?: null,
                                        'turma_id' => $turma_id ?: null,
                                        'data_matricula' => date('Y-m-d'),
                                        'data_inicio' => !empty($curso_inicio) ? $curso_inicio : date('Y-m-d'),
                                        'data_fim' => !empty($curso_fim) ? $curso_fim : date('Y-m-d', strtotime('+1 year')),
                                        'status' => 'ativo',
                                        'forma_pagamento' => 'A definir',
                                        'valor_total' => 0,
                                        'observacoes' => 'Matrícula criada automaticamente via importação',
                                        'created_at' => date('Y-m-d H:i:s'),
                                        'updated_at' => date('Y-m-d H:i:s')
                                    ];

                                    // Log dos dados da matrícula
                                    error_log("IMPORTAÇÃO DEBUG: Dados da matrícula: " . json_encode($dados_matricula));

                                    // Insere a matrícula
                                    $matricula_id = $db->insert('matriculas', $dados_matricula);

                                    error_log("IMPORTAÇÃO DEBUG: Matrícula criada com ID: " . ($matricula_id ? $matricula_id : 'FALHOU') . " para o aluno ID {$id} no curso ID {$curso_id}" .
                                              (!empty($turma_id) ? " e turma ID {$turma_id}" : ""));
                                } catch (Exception $e) {
                                    error_log("Erro ao criar matrícula para o aluno ID {$id}: " . $e->getMessage());
                                }
                            }

                            // Registra o log
                            registrarLog(
                                'alunos',
                                'criar',
                                "Aluno {$nome} (ID: {$id}) criado via importação",
                                $id,
                                'alunos'
                            );
                        }

                        $inseridos++;
                    }

                    // Adiciona ao resultado da validação
                    $resultados_validacao[] = [
                        'linha' => $total,
                        'nome' => $nome,
                        'email' => $email,
                        'cpf' => $cpf,
                        'status' => $status_linha,
                        'mensagem' => $mensagem_linha,
                        'operacao' => $tipo_operacao
                    ];

                } catch (Exception $e) {
                    $erros++;
                    $mensagens_erro[] = "Erro ao processar a linha {$total}: " . $e->getMessage();

                    // Adiciona ao resultado da validação
                    $resultados_validacao[] = [
                        'linha' => $total,
                        'nome' => $nome,
                        'email' => $email,
                        'cpf' => $cpf,
                        'status' => 'erro',
                        'mensagem' => $e->getMessage(),
                        'operacao' => $tipo_operacao
                    ];
                }
            }            // Confirma a transação apenas se não for validação
            if (!$apenas_validar) {
                error_log("IMPORTAÇÃO DEBUG: Fazendo commit da transação. Total: {$total}, Inseridos: {$inseridos}, Atualizados: {$atualizados}, Erros: {$erros}");
                $db->commit();
                error_log("IMPORTAÇÃO DEBUG: Commit realizado com sucesso");
            }

            // Remove o arquivo temporário
            @unlink($temp_file);

            // Se for apenas validação, exibe os resultados
            if ($apenas_validar) {
                // Prepara os dados para a view de resultados
                $titulo_pagina = 'Resultado da Validação';
                $view = 'validacao_importacao';
                $nome_arquivo = $_FILES['arquivo']['name'];
                $resultados = $resultados_validacao;
                // Passa as opções de atualização para a view
                $atualizar_existentes = $atualizar_existentes;
                $identificar_por_email = $identificar_por_email;
                $resumo = [
                    'total' => $total,
                    'inseridos' => $inseridos,
                    'atualizados' => $atualizados,
                    'ignorados' => $ignorados,
                    'erros' => $erros
                ];

                // Armazena os erros na sessão para exibição
                if ($erros > 0) {
                    $_SESSION['mensagens_erro'] = $mensagens_erro;
                }

                break;
            } else {
                // Mensagem de sucesso para importação real
                $mensagem = "Importação concluída: {$total} registros processados, {$inseridos} inseridos, {$atualizados} atualizados";
                if ($ignorados > 0) {
                    $mensagem .= ", {$ignorados} ignorados";
                }
                if ($erros > 0) {
                    $mensagem .= ", {$erros} erros.";
                    setMensagem('erro', $mensagem);
                    $_SESSION['mensagens_erro'] = $mensagens_erro;
                } else {
                    $mensagem .= ".";
                    setMensagem('sucesso', $mensagem);
                }

                redirect('alunos.php');
            }
        } catch (Exception $e) {
            // Reverte a transação em caso de erro (apenas se não for validação)
            if (!$apenas_validar) {
                $db->rollback();
            }

            // Remove o arquivo temporário
            @unlink($temp_file);

            // Mensagem de erro
            setMensagem('erro', 'Erro ao processar o arquivo: ' . $e->getMessage());
            redirect('alunos.php?action=importar');
        }
        break;

    case 'listar':
    default:
        // Lista todos os alunos
        $status = $_GET['status'] ?? 'todos';
        $polo_id = $_GET['polo_id'] ?? null;
        $curso_id = $_GET['curso_id'] ?? null;
        $turma_id = $_GET['turma_id'] ?? null;
        $pagina = $_GET['pagina'] ?? 1;
        $por_pagina = 20;
        $offset = ($pagina - 1) * $por_pagina;

        // Carrega os polos, cursos e turmas para os filtros
        $polos = executarConsultaAll($db, "SELECT id, nome FROM polos ORDER BY nome ASC");
        $cursos = executarConsultaAll($db, "SELECT id, nome FROM cursos ORDER BY nome ASC");
        $turmas = executarConsultaAll($db, "SELECT id, nome, curso_id FROM turmas ORDER BY nome ASC");

        // Monta a consulta SQL
        $where = [];
        $params = [];
        $joins = [];

        if ($status !== 'todos') {
            $where[] = "a.status = ?";
            $params[] = $status;
        }

        if ($polo_id) {
            // Se temos um polo_id, precisamos verificar se o aluno está matriculado neste polo
            $joins[] = "LEFT JOIN matriculas m ON a.id = m.aluno_id";
            $where[] = "m.polo_id = ?";
            $params[] = $polo_id;
        }

        if ($curso_id) {
            // Se temos um curso_id, precisamos verificar se o aluno está matriculado neste curso
            if (!in_array("LEFT JOIN matriculas m ON a.id = m.aluno_id", $joins)) {
                $joins[] = "LEFT JOIN matriculas m ON a.id = m.aluno_id";
            }
            $where[] = "m.curso_id = ?";
            $params[] = $curso_id;
        }

        if ($turma_id) {
            // Se temos um turma_id, precisamos verificar se o aluno está matriculado nesta turma
            if (!in_array("LEFT JOIN matriculas m ON a.id = m.aluno_id", $joins)) {
                $joins[] = "LEFT JOIN matriculas m ON a.id = m.aluno_id";
            }
            $where[] = "m.turma_id = ?";
            $params[] = $turma_id;
        }

        // Monta a cláusula WHERE
        $whereClause = '';
        if (!empty($where)) {
            $whereClause = "WHERE " . implode(" AND ", $where);
        }

        // Monta a cláusula JOIN
        $joinClause = implode(" ", $joins);

        // Consulta principal com DISTINCT para evitar duplicatas quando usamos JOINs
        $sql = "SELECT DISTINCT a.* FROM alunos a {$joinClause} {$whereClause} ORDER BY a.nome ASC LIMIT {$offset}, {$por_pagina}";
        $alunos = executarConsultaAll($db, $sql, $params);

        // Conta o total de alunos
        $sql = "SELECT COUNT(DISTINCT a.id) as total FROM alunos a {$joinClause} {$whereClause}";
        $resultado = executarConsulta($db, $sql, $params);
        $total_alunos = $resultado['total'] ?? 0;

        // Calcula o total de páginas
        $total_paginas = ceil($total_alunos / $por_pagina);

        $titulo_pagina = 'Alunos';
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
    <meta name="description" content="Gerenciamento de Alunos - Sistema Faciência ERP">
    <meta name="author" content="Sistema Faciência ERP">    <!-- Título da página -->
    <title>Faciência ERP - <?php echo isset($titulo_pagina) ? htmlspecialchars($titulo_pagina) : 'Alunos'; ?></title>

    <!-- ================================================================== -->
    <!-- RECURSOS EXTERNOS (CDN) -->
    <!-- ================================================================== -->

    <!-- Font Awesome para ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <!-- Estilos principais do sistema -->
    <link rel="stylesheet" href="css/styles.css">

    <!-- ================================================================== -->
    <!-- ESTILOS ESPECÍFICOS DO MÓDULO ALUNOS -->
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
            --border-radius: 0.5rem;
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --transition-default: all 0.3s ease;
        }

        /* ============================================================== */
        /* CARDS DE LISTAGEM DE ALUNOS */
        /* ============================================================== */
        .aluno-card {
            transition: var(--transition-default);
            border: 1px solid #e5e7eb;
        }

        .aluno-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            border-color: var(--color-primary);
        }

        /* ============================================================== */
        /* BADGES DE STATUS */
        /* ============================================================== */
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-ativo { 
            background-color: #dcfce7; 
            color: #166534; 
        }
        
        .status-inativo { 
            background-color: #fee2e2; 
            color: #991b1b; 
        }

        /* ============================================================== */
        /* FILTROS E BUSCA */
        /* ============================================================== */
        .filtros-container {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        /* ============================================================== */
        /* FORMULÁRIOS */
        /* ============================================================== */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: var(--border-radius);
            transition: var(--transition-default);
        }

        .form-input:focus {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        /* ============================================================== */
        /* IMPORTAÇÃO DE DADOS */
        /* ============================================================== */
        .import-area {
            border: 2px dashed #d1d5db;
            border-radius: var(--border-radius);
            padding: 3rem;
            text-align: center;
            transition: var(--transition-default);
        }

        .import-area:hover {
            border-color: var(--color-primary);
            background-color: #f8fafc;
        }

        .import-progress {
            background-color: #f3f4f6;
            border-radius: 9999px;
            height: 0.5rem;
            overflow: hidden;
        }

        .import-progress-bar {
            background-color: var(--color-primary);
            height: 100%;
            transition: width 0.3s ease;
        }

        /* ============================================================== */
        /* VALIDAÇÃO DE IMPORTAÇÃO */
        /* ============================================================== */
        .validation-result {
            border-left: 4px solid;
            padding: 1rem;
            margin-bottom: 0.5rem;
            border-radius: 0 var(--border-radius) var(--border-radius) 0;
        }

        .validation-success { 
            border-left-color: var(--color-success); 
            background-color: #f0fdf4; 
        }
        
        .validation-warning { 
            border-left-color: var(--color-warning); 
            background-color: #fffbeb; 
        }
        
        .validation-error { 
            border-left-color: var(--color-danger); 
            background-color: #fef2f2; 
        }

        /* ============================================================== */
        /* RESPONSIVIDADE */
        /* ============================================================== */
        @media (max-width: 768px) {
            .filtros-container {
                padding: 1rem;
            }
            
            .aluno-card {
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">        <!-- ================================================================ -->
        <!-- SIDEBAR DE NAVEGAÇÃO -->
        <!-- ================================================================ -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- ================================================================ -->
        <!-- CONTEÚDO PRINCIPAL -->
        <!-- ================================================================ -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- ============================================================ -->
            <!-- HEADER DA APLICAÇÃO -->
            <!-- ============================================================ -->
            <?php include 'includes/header.php'; ?>

            <!-- ============================================================ -->
            <!-- ÁREA PRINCIPAL DE CONTEÚDO -->
            <!-- ============================================================ -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
                <div class="container mx-auto">
                    <!-- ================================================ -->
                    <!-- CABEÇALHO DA PÁGINA -->
                    <!-- ================================================ -->
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($titulo_pagina); ?></h1>
                            <p class="text-gray-600 mt-1">
                                <?php 
                                switch($view) {
                                    case 'listar':
                                        echo 'Gerencie todos os alunos cadastrados no sistema';
                                        break;
                                    case 'form':
                                        echo $aluno && isset($aluno['id']) ? 'Edite as informações do aluno' : 'Cadastre um novo aluno no sistema';
                                        break;
                                    case 'visualizar':
                                        echo 'Visualize todos os detalhes do aluno';
                                        break;
                                    case 'importar':
                                        echo 'Importe alunos em massa via Excel ou CSV';
                                        break;
                                    case 'validacao_importacao':
                                        echo 'Revise os dados antes da importação final';
                                        break;
                                }
                                ?>
                            </p>
                        </div>

                        <!-- Botões de ação contextuais -->
                        <?php if ($view === 'listar'): ?>
                        <div class="flex space-x-2">
                            <a href="alunos.php?action=novo" class="btn-primary inline-flex items-center">
                                <i class="fas fa-plus mr-2"></i> 
                                Novo Aluno
                            </a>
                            <a href="alunos.php?action=importar" class="btn-secondary inline-flex items-center">
                                <i class="fas fa-file-import mr-2"></i> 
                                Importar
                            </a>
                        </div>
                        <?php elseif ($view === 'form'): ?>
                        <div class="flex space-x-2">
                            <a href="alunos.php" class="btn-secondary inline-flex items-center">
                                <i class="fas fa-arrow-left mr-2"></i> 
                                Voltar
                            </a>
                        </div>
                        <?php elseif ($view === 'visualizar'): ?>
                        <div class="flex space-x-2">
                            <a href="alunos.php?action=editar&id=<?php echo $aluno['id']; ?>" class="btn-primary inline-flex items-center">
                                <i class="fas fa-edit mr-2"></i> 
                                Editar
                            </a>
                            <a href="alunos.php" class="btn-secondary inline-flex items-center">
                                <i class="fas fa-arrow-left mr-2"></i> 
                                Voltar
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>                    <!-- ================================================ -->
                    <!-- ÁREA DE MENSAGENS DO SISTEMA -->
                    <!-- ================================================ -->

                    <!-- Mensagens de erro de validação -->
                    <?php if (isset($mensagens_erro) && !empty($mensagens_erro)): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-r-lg">
                        <div class="flex items-center mb-2">
                            <i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>
                            <h3 class="text-red-800 font-semibold">Erros de Validação</h3>
                        </div>
                        <ul class="list-disc list-inside text-red-700 space-y-1">
                            <?php foreach ($mensagens_erro as $erro): ?>
                            <li><?php echo htmlspecialchars($erro); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <!-- Mensagens de erro de importação -->
                    <?php if (isset($_SESSION['mensagens_erro']) && !empty($_SESSION['mensagens_erro'])): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-r-lg">
                        <div class="flex items-center mb-2">
                            <i class="fas fa-file-excel text-red-500 mr-2"></i>
                            <h3 class="text-red-800 font-semibold">Erros de Importação</h3>
                        </div>
                        <div class="max-h-64 overflow-y-auto">
                            <ul class="list-disc list-inside text-red-700 space-y-1">
                                <?php foreach ($_SESSION['mensagens_erro'] as $erro): ?>
                                <li class="text-sm"><?php echo htmlspecialchars($erro); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    <?php
                    // Limpa as mensagens de erro da sessão
                    unset($_SESSION['mensagens_erro']);
                    endif;
                    ?>

                    <!-- Mensagens de sucesso/erro gerais -->
                    <?php if (isset($_SESSION['mensagem']) && isset($_SESSION['mensagem_tipo'])): ?>
                    <?php 
                    $tipo = $_SESSION['mensagem_tipo'];
                    $cor = $tipo === 'sucesso' ? 'green' : 'red';
                    $icone = $tipo === 'sucesso' ? 'fa-check-circle' : 'fa-exclamation-circle';
                    ?>
                    <div class="bg-<?php echo $cor; ?>-50 border-l-4 border-<?php echo $cor; ?>-500 p-4 mb-6 rounded-r-lg">
                        <div class="flex items-center">
                            <i class="fas <?php echo $icone; ?> text-<?php echo $cor; ?>-500 mr-2"></i>
                            <span class="text-<?php echo $cor; ?>-800 font-medium">
                                <?php echo is_array($_SESSION['mensagem']) ? implode(', ', $_SESSION['mensagem']) : $_SESSION['mensagem']; ?>
                            </span>
                        </div>
                    </div>
                    <?php
                    // Limpa a mensagem da sessão
                    unset($_SESSION['mensagem']);
                    unset($_SESSION['mensagem_tipo']);
                    endif;
                    ?>                    <!-- ================================================ -->
                    <!-- ÁREA DE CONTEÚDO DINÂMICO -->
                    <!-- ================================================ -->
                    <?php
                    // Inclui a view correspondente baseada na ação atual
                    switch ($view) {
                        case 'form':
                            // Formulário de cadastro/edição de aluno
                            include 'views/alunos/form.php';
                            break;
                            
                        case 'visualizar':
                            // Página de detalhes do aluno
                            include 'views/alunos/visualizar.php';
                            break;
                            
                        case 'importar':
                            // Formulário de importação em massa
                            include 'views/alunos/importar.php';
                            break;
                            
                        case 'validacao_importacao':
                            // Página de validação antes da importação
                            include 'views/alunos/validacao_importacao.php';
                            break;
                            
                        case 'listar':
                        default:
                            // Listagem de alunos (página padrão)
                            include 'views/alunos/listar.php';
                            break;
                    }
                    ?>
                </div>
            </main>

            <!-- ================================================================ -->
            <!-- RODAPÉ DA APLICAÇÃO -->
            <!-- ================================================================ -->
            <footer class="bg-white border-t border-gray-200 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center text-sm text-gray-500">
                        <i class="fas fa-users mr-2 text-blue-500"></i>
                        <span>Módulo de Alunos - Faciência ERP © 2024</span>
                    </div>
                    <div class="flex items-center space-x-4 text-sm text-gray-500">
                        <span>Versão 2.0</span>
                        <span>•</span>
                        <a href="ajuda.php?modulo=alunos" class="hover:text-blue-600 transition-colors">
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
         * MÓDULO ALUNOS - SCRIPTS DE INTERATIVIDADE
         * ================================================================
         */

        document.addEventListener('DOMContentLoaded', function() {
            console.log('🎓 Módulo de Alunos carregado');
              // Inicializa funcionalidades específicas baseadas na view atual
            const view = '<?php echo isset($view) ? $view : 'listar'; ?>';
            
            switch(view) {
                case 'listar':
                    inicializarListagem();
                    break;
                case 'form':
                    inicializarFormulario();
                    break;
                case 'importar':
                    inicializarImportacao();
                    break;
                case 'validacao_importacao':
                    inicializarValidacao();
                    break;
            }
        });

        /**
         * Inicializa funcionalidades da listagem de alunos
         */
        function inicializarListagem() {
            // Adiciona confirmação para exclusão
            document.querySelectorAll('.btn-excluir').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const nomeAluno = this.dataset.nome;
                    
                    if (confirm(`Tem certeza que deseja excluir o aluno "${nomeAluno}"?\n\nEsta ação não pode ser desfeita.`)) {
                        window.location.href = this.href;
                    }
                });
            });

            // Filtros dinâmicos
            const filtros = document.querySelectorAll('.filtro-select');
            filtros.forEach(filtro => {
                filtro.addEventListener('change', function() {
                    document.getElementById('form-filtros').submit();
                });
            });
        }

        /**
         * Inicializa funcionalidades do formulário de aluno
         */
        function inicializarFormulario() {
            // Validação em tempo real do CPF
            const campoCpf = document.getElementById('cpf');
            if (campoCpf) {
                campoCpf.addEventListener('input', function() {
                    formatarCPF(this);
                    validarCPF(this);
                });
            }

            // Validação em tempo real do email
            const campoEmail = document.getElementById('email');
            if (campoEmail) {
                campoEmail.addEventListener('blur', function() {
                    validarEmail(this);
                });
            }

            // Auto-formatação do CEP
            const campoCep = document.getElementById('cep');
            if (campoCep) {
                campoCep.addEventListener('input', function() {
                    formatarCEP(this);
                });
            }
        }

        /**
         * Inicializa funcionalidades da importação
         */
        function inicializarImportacao() {
            const inputArquivo = document.getElementById('arquivo');
            const areaUpload = document.querySelector('.import-area');
            
            if (inputArquivo && areaUpload) {
                // Drag and drop
                areaUpload.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    this.classList.add('border-blue-500', 'bg-blue-50');
                });

                areaUpload.addEventListener('dragleave', function(e) {
                    e.preventDefault();
                    this.classList.remove('border-blue-500', 'bg-blue-50');
                });

                areaUpload.addEventListener('drop', function(e) {
                    e.preventDefault();
                    this.classList.remove('border-blue-500', 'bg-blue-50');
                    
                    const files = e.dataTransfer.files;
                    if (files.length > 0) {
                        inputArquivo.files = files;
                        mostrarNomeArquivo(files[0].name);
                    }
                });

                // Seleção de arquivo
                inputArquivo.addEventListener('change', function() {
                    if (this.files.length > 0) {
                        mostrarNomeArquivo(this.files[0].name);
                    }
                });
            }
        }

        /**
         * Mostra o nome do arquivo selecionado
         */
        function mostrarNomeArquivo(nome) {
            const elemento = document.getElementById('nome-arquivo');
            if (elemento) {
                elemento.textContent = nome;
                elemento.classList.remove('hidden');
            }
        }

        /**
         * Formata o CPF conforme o usuário digita
         */
        function formatarCPF(input) {
            let valor = input.value.replace(/\D/g, '');
            valor = valor.replace(/(\d{3})(\d)/, '$1.$2');
            valor = valor.replace(/(\d{3})(\d)/, '$1.$2');
            valor = valor.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            input.value = valor;
        }

        /**
         * Valida o CPF
         */
        function validarCPF(input) {
            const cpf = input.value.replace(/\D/g, '');
            const isValid = validarCPFNumerico(cpf);
            
            if (cpf.length === 11 && !isValid) {
                input.classList.add('border-red-500');
                mostrarErro(input, 'CPF inválido');
            } else {
                input.classList.remove('border-red-500');
                esconderErro(input);
            }
        }

        /**
         * Validação numérica do CPF
         */
        function validarCPFNumerico(cpf) {
            if (cpf.length !== 11) return false;
            if (/^(\d)\1{10}$/.test(cpf)) return false;

            let soma = 0;
            for (let i = 0; i < 9; i++) {
                soma += parseInt(cpf.charAt(i)) * (10 - i);
            }
            
            let resto = 11 - (soma % 11);
            if (resto === 10 || resto === 11) resto = 0;
            if (resto !== parseInt(cpf.charAt(9))) return false;

            soma = 0;
            for (let i = 0; i < 10; i++) {
                soma += parseInt(cpf.charAt(i)) * (11 - i);
            }
            
            resto = 11 - (soma % 11);
            if (resto === 10 || resto === 11) resto = 0;
            return resto === parseInt(cpf.charAt(10));
        }

        /**
         * Valida email
         */
        function validarEmail(input) {
            const email = input.value;
            const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (email && !regex.test(email)) {
                input.classList.add('border-red-500');
                mostrarErro(input, 'Email inválido');
            } else {
                input.classList.remove('border-red-500');
                esconderErro(input);
            }
        }

        /**
         * Formata CEP
         */
        function formatarCEP(input) {
            let valor = input.value.replace(/\D/g, '');
            valor = valor.replace(/(\d{5})(\d)/, '$1-$2');
            input.value = valor;
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
        ║                    FACIÊNCIA ERP - ALUNOS                     ║
        ║                  Módulo de Gestão de Alunos                   ║
        ╠════════════════════════════════════════════════════════════════╣
        ║ 📚 View Atual: <?php echo isset($view) ? strtoupper($view) : 'LISTAR'; ?>                                             ║
        ║ 👥 Sistema: Gerenciamento Acadêmico                           ║
        ║ 🔧 Versão: 2.0                                                ║
        ╚════════════════════════════════════════════════════════════════╝
        `);
    </script>
</body>
</html>
