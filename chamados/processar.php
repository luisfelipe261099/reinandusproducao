<?php
// Inicializa o sistema
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/chamados_functions.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Instancia o banco de dados
$db = Database::getInstance();

// Verifica se a ação foi informada
if (!isset($_POST['acao']) || empty($_POST['acao'])) {
    $_SESSION['mensagem'] = 'Ação não informada.';
    $_SESSION['mensagem_tipo'] = 'erro';
    header('Location: index.php');
    exit;
}

$acao = $_POST['acao'];

// Processa a ação
switch ($acao) {
    case 'criar':
        // Verifica se o usuário tem permissão para criar chamados
        exigirPermissao('chamados', 'criar');

        // Valida os dados do formulário
        $erros = [];

        if (empty($_POST['subtipo'])) {
            $erros[] = 'O tipo de documento é obrigatório.';
        }

        if (getUsuarioTipo() != 'polo' && empty($_POST['polo_id'])) {
            $erros[] = 'O polo é obrigatório.';
        }

        if (empty($_POST['alunos']) && empty($_POST['turma_id']) && empty($_POST['alunos_manual'])) {
            $erros[] = 'Selecione pelo menos um aluno ou uma turma ou use a entrada manual.';
        }

        // Se houver erros, redireciona de volta com mensagem de erro
        if (!empty($erros)) {
            $_SESSION['mensagem'] = 'Erro ao criar chamado: ' . implode(' ', $erros);
            $_SESSION['mensagem_tipo'] = 'erro';
            $_SESSION['form_data'] = $_POST; // Salva os dados do formulário para preencher novamente
            header('Location: novo.php');
            exit;
        }

        // Prepara os dados do chamado
        $dados = [
            'tipo' => 'documento',
            'subtipo' => $_POST['subtipo'],
            'solicitante_id' => getUsuarioId(),
            'observacoes' => $_POST['observacoes'] ?? null,
            'titulo' => 'Solicitação de ' . ucfirst($_POST['subtipo']),
            'codigo' => 'DOC-' . date('Ymd') . '-' . mt_rand(1000, 9999),
            'categoria_id' => 1, // Ajuste para a categoria correta
            'prioridade' => 'media',
            'departamento' => 'secretaria'
        ];

        // Define o polo_id
        if (getUsuarioTipo() == 'polo') {
            // Se for usuário de polo, usa o polo do usuário
            $dados['polo_id'] = $db->fetchOne("SELECT polo_id FROM usuarios WHERE id = ?", [getUsuarioId()])['polo_id'];
        } else if (!empty($_POST['polo_id'])) {
            // Se não for usuário de polo e um polo foi selecionado, usa o polo selecionado
            $dados['polo_id'] = (int)$_POST['polo_id'];
        } else {
            // Se não for usuário de polo e nenhum polo foi selecionado, deixa o polo_id como NULL
            $dados['polo_id'] = null;
        }

        // Prepara a lista de alunos
        $alunos = [];

        // Verifica a opção de seleção escolhida
        $opcao_selecao = $_POST['opcao_selecao'] ?? 'alunos';

        switch ($opcao_selecao) {
            case 'turma':
                // Se foi selecionada uma turma, busca todos os alunos da turma
                if (!empty($_POST['turma_id'])) {
                    $turma_id = (int)$_POST['turma_id'];
                    $alunos_turma = buscarAlunosPorTurma($db, $turma_id);
                    $alunos = array_column($alunos_turma, 'id');
                }
                break;

            case 'manual':
                // Se foi usada a entrada manual, cria registros temporários para os alunos
                if (!empty($_POST['alunos_manual'])) {
                    $linhas = explode("\n", $_POST['alunos_manual']);
                    $polo_id = $dados['polo_id'];
                    $alunos = [];
                    
                    foreach ($linhas as $linha) {
                        $linha = trim($linha);
                        if (!empty($linha)) {
                            // Aqui você pode processar cada linha conforme necessário
                            // Por exemplo, extrair nome e matrícula se estiverem no formato "Nome - Matrícula"
                            
                            // Para simplificar, vamos apenas adicionar o nome como descrição
                            $dados['descricao'] = "Solicitação de {$dados['subtipo']} para alunos:\n\n";
                            $dados['descricao'] .= $_POST['alunos_manual'];
                            
                            // Se precisar criar registros de alunos, faça aqui
                            // Por enquanto, apenas marcamos que temos dados manuais
                            $dados['tem_dados_manuais'] = true;
                        }
                    }
                }
                break;

            case 'planilha':
                // Se foi feito upload de planilha, processa o arquivo
                if (isset($_FILES['arquivo_planilha']) && $_FILES['arquivo_planilha']['error'] === UPLOAD_ERR_OK) {
                    // Aqui você implementaria o processamento da planilha
                    // Por enquanto, apenas registramos um erro
                    $_SESSION['mensagem'] = 'O processamento de planilhas ainda não está implementado.';
                    $_SESSION['mensagem_tipo'] = 'aviso';
                }
                break;

            case 'alunos':
            default:
                // Usa os alunos selecionados individualmente
                if (isset($_POST['alunos']) && is_array($_POST['alunos'])) {
                    $alunos = $_POST['alunos'];
                }
                break;
        }

        $dados['alunos'] = $alunos;

        // Cria o chamado
        $chamado_id = criarChamado($db, $dados);

        if ($chamado_id) {
            // Se tiver dados manuais, registra uma observação adicional
            if (isset($dados['tem_dados_manuais']) && $dados['tem_dados_manuais']) {
                $sql = "INSERT INTO chamados_respostas (
                            chamado_id, usuario_id, mensagem, tipo, visivel_solicitante, data_resposta, created_at, updated_at
                        ) VALUES (?, ?, ?, 'nota_interna', 1, NOW(), NOW(), NOW())";
                
                $mensagem = "Chamado criado com entrada manual de alunos.";
                $db->execute($sql, [$chamado_id, getUsuarioId(), $mensagem]);
            }

            $_SESSION['mensagem'] = 'Chamado criado com sucesso!';
            $_SESSION['mensagem_tipo'] = 'sucesso';
            header('Location: visualizar.php?id=' . $chamado_id);
        } else {
            $_SESSION['mensagem'] = 'Erro ao criar chamado. Tente novamente.';
            $_SESSION['mensagem_tipo'] = 'erro';
            $_SESSION['form_data'] = $_POST; // Salva os dados do formulário para preencher novamente
            header('Location: novo.php');
        }
        break;

    case 'atualizar_status':
        // Verifica se o usuário tem permissão para atualizar chamados
        exigirPermissao('chamados', 'editar');

        // Valida os dados do formulário
        if (!isset($_POST['chamado_id']) || empty($_POST['chamado_id'])) {
            $_SESSION['mensagem'] = 'ID do chamado não informado.';
            $_SESSION['mensagem_tipo'] = 'erro';
            header('Location: index.php');
            exit;
        }

        if (!isset($_POST['novo_status']) || empty($_POST['novo_status'])) {
            $_SESSION['mensagem'] = 'Novo status não informado.';
            $_SESSION['mensagem_tipo'] = 'erro';
            header('Location: visualizar.php?id=' . $_POST['chamado_id']);
            exit;
        }

        $chamado_id = (int)$_POST['chamado_id'];
        $novo_status = $_POST['novo_status'];
        $observacao = $_POST['observacao'] ?? null;

        // Verifica se o usuário tem permissão para acessar este chamado
        if (!usuarioTemPermissaoChamado($db, $chamado_id, getUsuarioId(), getUsuarioTipo())) {
            $_SESSION['mensagem'] = 'Você não tem permissão para atualizar este chamado.';
            $_SESSION['mensagem_tipo'] = 'erro';
            header('Location: index.php');
            exit;
        }

        // Atualiza o status do chamado
        $resultado = atualizarStatusChamado($db, $chamado_id, $novo_status, getUsuarioId(), $observacao);

        if ($resultado) {
            $_SESSION['mensagem'] = 'Status do chamado atualizado com sucesso!';
            $_SESSION['mensagem_tipo'] = 'sucesso';
        } else {
            $_SESSION['mensagem'] = 'Erro ao atualizar status do chamado. Tente novamente.';
            $_SESSION['mensagem_tipo'] = 'erro';
        }

        header('Location: visualizar.php?id=' . $chamado_id);
        break;

    default:
        $_SESSION['mensagem'] = 'Ação inválida.';
        $_SESSION['mensagem_tipo'] = 'erro';
        header('Location: index.php');
        break;
}

exit;







