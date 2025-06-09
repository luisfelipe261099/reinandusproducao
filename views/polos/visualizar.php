<?php
// As funções executarConsulta e executarConsultaAll já estão definidas no arquivo principal

// Verifica se o ID foi informado
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Redireciona para a listagem com mensagem de erro
    $_SESSION['mensagem'] = 'ID do polo não informado.';

    header('Location: polos.php');
    exit;
}

$id = (int)$_GET['id'];

// Busca os dados do polo
$sql = "SELECT * FROM polos WHERE id = ?";
$polo = executarConsulta($db, $sql, [$id]);

// Verifica se o polo existe
if (!$polo) {
    // Redireciona para a listagem com mensagem de erro
    $_SESSION['mensagem'] = 'Polo não encontrado.';

    header('Location: polos.php');
    exit;
}

// Busca os tipos de polo associados
$sql = "SELECT pt.*, tp.nome as tipo_nome, tp.descricao as tipo_descricao
        FROM polos_tipos pt
        JOIN tipos_polos tp ON pt.tipo_polo_id = tp.id
        WHERE pt.polo_id = ?
        ORDER BY tp.nome ASC";
$tipos_polo = executarConsultaAll($db, $sql, [$id]);
// Busca as informações financeiras do polo - usando método direto da classe Database
try {
    $financeiro_novo = null;

    // Debug detalhado
    error_log('=== DEBUG FINANCEIRO POLO ' . $id . ' ===');

    // Primeiro tenta na tabela nova
    $sql = "SELECT * FROM polos_financeiro_novo WHERE polo_id = ? LIMIT 1";
    error_log('SQL: ' . $sql . ' com polo_id: ' . $id);

    $result = $db->fetchOne($sql, [$id]);
    error_log('Resultado bruto fetchOne: ' . var_export($result, true));
    error_log('Tipo do resultado: ' . gettype($result));

    // Verifica se o resultado é válido (não é false, null ou array vazio)
    if ($result && is_array($result) && !empty($result)) {
        $financeiro_novo = $result;
        error_log('✅ Dados encontrados em polos_financeiro_novo: ' . json_encode($financeiro_novo));
    } else {
        error_log('❌ Nenhum dado válido em polos_financeiro_novo');

        // Tenta na tabela antiga
        $sql_old = "SELECT * FROM polos_financeiro WHERE polo_id = ? LIMIT 1";
        error_log('Tentando SQL antigo: ' . $sql_old);

        $result_old = $db->fetchOne($sql_old, [$id]);
        error_log('Resultado tabela antiga: ' . var_export($result_old, true));

        if ($result_old && is_array($result_old) && !empty($result_old)) {
            $financeiro_novo = $result_old;
            error_log('✅ Dados encontrados em polos_financeiro: ' . json_encode($financeiro_novo));
        } else {
            error_log('❌ Nenhum dado válido em polos_financeiro');
        }
    }

    // Se ainda não encontrou, verifica se existem dados em qualquer uma das tabelas
    if (!$financeiro_novo) {
        $sql_check_novo = "SELECT COUNT(*) as total FROM polos_financeiro_novo WHERE polo_id = ?";
        $check_novo = $db->fetchOne($sql_check_novo, [$id]);
        error_log('Total registros em polos_financeiro_novo: ' . ($check_novo ? $check_novo['total'] : 0));

        $sql_check_old = "SELECT COUNT(*) as total FROM polos_financeiro WHERE polo_id = ?";
        $check_old = $db->fetchOne($sql_check_old, [$id]);
        error_log('Total registros em polos_financeiro: ' . ($check_old ? $check_old['total'] : 0));

        // Lista alguns registros para debug
        $sql_sample = "SELECT polo_id, valor_previsto FROM polos_financeiro_novo LIMIT 5";
        $sample = $db->fetchAll($sql_sample);
        error_log('Amostra de registros em polos_financeiro_novo: ' . json_encode($sample));
    }

    error_log('Financeiro final para polo ' . $id . ': ' . ($financeiro_novo ? 'Encontrado' : 'Não encontrado'));

} catch (Exception $e) {
    error_log('ERRO ao buscar informações financeiras: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    $financeiro_novo = null;
}

// Busca os cursos associados ao polo - Consulta simplificada para depuração
$sql = "SELECT c.id, c.nome, c.sigla as codigo, c.status,
        IFNULL(COUNT(DISTINCT m.id), 0) as total_alunos
        FROM cursos c
        LEFT JOIN matriculas m ON c.id = m.curso_id AND m.status = 'ativo'
        WHERE c.polo_id = ?
        GROUP BY c.id, c.nome, c.sigla, c.status
        ORDER BY c.nome ASC";

// Log para depuração
error_log('SQL para buscar cursos: ' . $sql);
error_log('Polo ID: ' . $id);

// Tenta executar a consulta diretamente com o banco de dados
try {
    $cursos = $db->fetchAll($sql, [$id]);
    error_log('Cursos encontrados (fetchAll direto): ' . count($cursos));
} catch (Exception $e) {
    error_log('Erro ao buscar cursos: ' . $e->getMessage());
    // Tenta uma consulta mais simples
    $sql_simple = "SELECT id, nome, sigla as codigo, status, 0 as total_alunos FROM cursos WHERE polo_id = ?";
    try {
        $cursos = $db->fetchAll($sql_simple, [$id]);
        error_log('Cursos encontrados (consulta simples): ' . count($cursos));
    } catch (Exception $e) {
        error_log('Erro na consulta simples: ' . $e->getMessage());
        $cursos = [];
    }
}

// Se ainda não encontrou cursos, verifica diretamente no banco
if (empty($cursos)) {
    error_log('Nenhum curso encontrado, verificando diretamente no banco');
    // Verifica se há cursos no banco de dados
    $sql_check = "SELECT COUNT(*) as total FROM cursos WHERE polo_id = ?";
    $check = $db->fetchOne($sql_check, [$id]);
    error_log('Total de cursos no banco: ' . ($check ? $check['total'] : 0));

    // Se houver cursos, busca-os com uma consulta muito simples
    if ($check && $check['total'] > 0) {
        $sql_basic = "SELECT id, nome, sigla as codigo, status, 0 as total_alunos FROM cursos WHERE polo_id = ?";
        $cursos = $db->fetchAll($sql_basic, [$id]);
        error_log('Cursos recuperados com consulta básica: ' . count($cursos));
    }
}

// Abordagem completamente simplificada para buscar turmas do polo
error_log('ID do polo para buscar turmas: ' . $id);

// Verifica se o ID do polo é válido
if (empty($id) || !is_numeric($id)) {
    error_log('ERRO: ID do polo inválido: ' . $id);
    $turmas = [];
} else {
    // Consulta direta e simples na tabela turmas
    $sql_turmas = "SELECT * FROM turmas WHERE polo_id = ?";
    error_log('SQL para buscar turmas: ' . $sql_turmas);

    try {
        // Executa a consulta diretamente
        $turmas_raw = $db->fetchAll($sql_turmas, [$id]);
        error_log('Turmas encontradas (consulta direta): ' . count($turmas_raw));

        // Formata os resultados para o formato esperado pelo template
        $turmas = [];
        foreach ($turmas_raw as $turma) {
            // Busca o nome do curso para cada turma
            $curso_nome = '';
            if (!empty($turma['curso_id'])) {
                try {
                    $sql_curso = "SELECT nome FROM cursos WHERE id = ?";
                    $curso = $db->fetchOne($sql_curso, [$turma['curso_id']]);
                    if ($curso) {
                        $curso_nome = $curso['nome'];
                    }
                } catch (Exception $e) {
                    error_log('Erro ao buscar nome do curso: ' . $e->getMessage());
                }
            }

            // Conta os alunos da turma
            $total_alunos = 0;
            try {
                $sql_alunos = "SELECT COUNT(*) as total FROM matriculas WHERE turma_id = ? AND status = 'ativo'";
                $alunos = $db->fetchOne($sql_alunos, [$turma['id']]);
                if ($alunos) {
                    $total_alunos = $alunos['total'];
                }
            } catch (Exception $e) {
                error_log('Erro ao contar alunos: ' . $e->getMessage());
            }

            // Adiciona a turma formatada ao array de turmas
            $turmas[] = [
                'id' => $turma['id'],
                'nome' => $turma['nome'],
                'codigo' => $turma['codigo_turma'] ?? '',
                'status' => $turma['status'],
                'curso_nome' => $curso_nome,
                'total_alunos' => $total_alunos
            ];
        }

        error_log('Turmas formatadas: ' . count($turmas));
    } catch (Exception $e) {
        error_log('ERRO ao buscar turmas: ' . $e->getMessage());
        error_log('Stack trace: ' . $e->getTraceAsString());
        $turmas = [];
    }

    // Se não encontrou nenhuma turma, faz um dump da tabela para verificar
    if (empty($turmas)) {
        try {
            $sql_dump = "SELECT id, nome, polo_id FROM turmas LIMIT 10";
            $dump = $db->fetchAll($sql_dump);
            error_log('Dump das primeiras 10 turmas: ' . json_encode($dump));
        } catch (Exception $e) {
            error_log('Erro ao fazer dump da tabela turmas: ' . $e->getMessage());
        }
    }
}

?>
<div class="container mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Detalhes do Polo</h1>
        <div class="flex space-x-2">
            <?php
            // Verifica se o polo já tem um usuário responsável
            $tem_acesso = !empty($polo['responsavel_id']);
            $btn_acesso_class = $tem_acesso ? "bg-orange-500 hover:bg-orange-600" : "bg-green-500 hover:bg-green-600";
            $btn_acesso_text = $tem_acesso ? "Redefinir Acesso" : "Criar Acesso";
            $btn_acesso_icon = $tem_acesso ? "fas fa-key" : "fas fa-user-plus";
            ?>
            <button type="button" id="btn-acesso-polo" class="<?php echo $btn_acesso_class; ?> text-white font-bold py-2 px-4 rounded shadow-md transition duration-300 ease-in-out transform hover:scale-105" onclick="document.getElementById('modal-acesso-polo').classList.remove('hidden'); document.getElementById('modal-acesso-polo').style.display = 'flex';">
                <i class="<?php echo $btn_acesso_icon; ?> mr-2"></i> <?php echo $btn_acesso_text; ?>
            </button>

            <a href="polos.php?action=editar_financeiro_novo&id=<?php echo $polo['id']; ?>" class="text-sm bg-purple-500 hover:bg-purple-600 text-white font-medium py-1 px-3 rounded">
                <i class="fas fa-edit mr-1"></i> Editar Financeiro
            </a>

            <a href="polos.php?action=editar&id=<?php echo $polo['id']; ?>" class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded">
                <i class="fas fa-edit mr-2"></i> Editar
            </a>
            <a href="polos.php" class="bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded">
                <i class="fas fa-arrow-left mr-2"></i> Voltar
            </a>
        </div>
    </div>



    <?php if (isset($_SESSION['mensagem'])): ?>
    <?php
    // Determina o tipo de mensagem e a cor
    $tipo_mensagem = 'erro';
    $cor = 'red';

    if (isset($_SESSION['mensagem_tipo']) && $_SESSION['mensagem_tipo'] === 'sucesso') {
        $tipo_mensagem = 'sucesso';
        $cor = 'green';
    } elseif (is_array($_SESSION['mensagem']) && isset($_SESSION['mensagem']['tipo'])) {
        $tipo_mensagem = $_SESSION['mensagem']['tipo'];
        $cor = ($tipo_mensagem === 'sucesso') ? 'green' : 'red';
    }
    ?>
    <div class="bg-<?php echo $cor; ?>-100 border-l-4 border-<?php echo $cor; ?>-500 text-<?php echo $cor; ?>-700 p-4 mb-6">
        <?php
        // Exibe a mensagem de forma adequada
        if (is_array($_SESSION['mensagem'])) {
            if (isset($_SESSION['mensagem']['texto'])) {
                echo $_SESSION['mensagem']['texto'];
            } else {
                echo "Ocorreu um erro no sistema.";
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

    <?php if (isset($_SESSION['alerta'])): ?>
    <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6">
        <?php
        // Verifica se o alerta é um array e converte para string se necessário
        if (is_array($_SESSION['alerta'])) {
            echo "Alerta do sistema: " . print_r($_SESSION['alerta'], true);
        } else {
            echo $_SESSION['alerta'];
        }
        ?>
    </div>
    <?php
    // Limpa o alerta da sessão
    unset($_SESSION['alerta']);
    unset($_SESSION['alerta_tipo']);
    endif;
    ?>

    <!-- Tipos de Polo -->
    <div class="bg-white shadow-md rounded-lg overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800">Tipos de Polo</h2>
        </div>

        <div class="p-6">
            <?php if (empty($tipos_polo)): ?>
                <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-4">
                    <p class="font-medium">Este polo não possui tipos associados.</p>
                    <p class="mt-1">Por favor, <a href="polos.php?action=editar&id=<?php echo $polo['id']; ?>" class="underline font-medium">edite o polo</a> e selecione pelo menos um tipo.</p>
                    <p class="mt-1 text-sm">Os tipos de polo (Graduação, Pós-Graduação, Extensão) são necessários para definir as categorias do polo.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <?php foreach ($tipos_polo as $tipo): ?>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="font-medium text-gray-900 mb-2"><?php echo htmlspecialchars($tipo['tipo_nome']); ?></h3>
                            <p class="text-sm text-gray-600"><?php echo htmlspecialchars($tipo['tipo_descricao']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Informações Financeiras -->
    <div id="financeiro" class="bg-white shadow-md rounded-lg overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-800">Informações Financeiras</h2>
            <a href="polos.php?action=editar_financeiro_novo&id=<?php echo $polo['id']; ?>" class="text-sm bg-purple-500 hover:bg-purple-600 text-white font-medium py-1 px-3 rounded">
                <i class="fas fa-edit mr-1"></i> Editar Financeiro
            </a>
        </div>

        <div class="p-6">
            <?php
            // Debug adicional para verificar o que está sendo exibido
            error_log('=== DEBUG EXIBIÇÃO FINANCEIRO ===');
            error_log('$financeiro_novo é empty? ' . (empty($financeiro_novo) ? 'SIM' : 'NÃO'));
            error_log('$financeiro_novo conteúdo: ' . json_encode($financeiro_novo));
            if ($financeiro_novo && isset($financeiro_novo['valor_previsto'])) {
                error_log('valor_previsto específico: ' . var_export($financeiro_novo['valor_previsto'], true));
            }
            ?>

            <?php if (empty($financeiro_novo)): ?>
                <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-4">
                    <p class="font-medium">Este polo não possui informações financeiras cadastradas.</p>
                    <p class="mt-1">Por favor, <a href="polos.php?action=editar_financeiro_novo&id=<?php echo $polo['id']; ?>" class="underline font-medium">cadastre as informações financeiras</a>.</p>
                </div>
            <?php else: ?>
                <!-- Debug visual para o usuário -->
                <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-4">
                    <p class="font-medium">🔍 DEBUG: Informações financeiras encontradas</p>
                    <p class="mt-1 text-xs">Valor previsto bruto: <?php echo var_export($financeiro_novo['valor_previsto'] ?? 'NULL', true); ?></p>
                    <p class="mt-1 text-xs">Tipo: <?php echo gettype($financeiro_novo['valor_previsto'] ?? null); ?></p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Data Inicial</h3>
                        <p class="mt-1 text-sm text-gray-900">
                            <?php echo !empty($financeiro_novo['data_inicial']) ? date('d/m/Y', strtotime($financeiro_novo['data_inicial'])) : 'Não informada'; ?>
                        </p>
                    </div>

                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Vigência do Contrato (meses)</h3>
                        <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($financeiro_novo['vigencia_contrato_meses'] ?? ''); ?></p>
                    </div>

                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Vencimento do Contrato</h3>
                        <p class="mt-1 text-sm text-gray-900">
                            <?php echo !empty($financeiro_novo['vencimento_contrato']) ? date('d/m/Y', strtotime($financeiro_novo['vencimento_contrato'])) : 'Não informada'; ?>
                        </p>
                    </div>

                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Vigência Pacote Setup (meses)</h3>
                        <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($financeiro_novo['vigencia_pacote_setup'] ?? ''); ?></p>
                    </div>

                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Vencimento Pacote Setup</h3>
                        <p class="mt-1 text-sm text-gray-900">
                            <?php echo !empty($financeiro_novo['vencimento_pacote_setup']) ? date('d/m/Y', strtotime($financeiro_novo['vencimento_pacote_setup'])) : 'Não informada'; ?>
                        </p>
                    </div>

                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Pacotes Adquiridos</h3>
                        <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($financeiro_novo['pacotes_adquiridos'] ?? '0'); ?></p>
                    </div>

                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Valor Unitário Normal</h3>
                        <p class="mt-1 text-sm text-gray-900">R$ <?php echo number_format($financeiro_novo['valor_unitario_normal'] ?? 0, 2, ',', '.'); ?></p>
                    </div>

                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Quantidade Contratada</h3>
                        <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($financeiro_novo['quantidade_contratada'] ?? '0'); ?></p>
                    </div>

                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Data Primeira Parcela</h3>
                        <p class="mt-1 text-sm text-gray-900">
                            <?php echo !empty($financeiro_novo['data_primeira_parcela']) ? date('d/m/Y', strtotime($financeiro_novo['data_primeira_parcela'])) : 'Não informada'; ?>
                        </p>
                    </div>

                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Data Última Parcela</h3>
                        <p class="mt-1 text-sm text-gray-900">
                            <?php echo !empty($financeiro_novo['data_ultima_parcela']) ? date('d/m/Y', strtotime($financeiro_novo['data_ultima_parcela'])) : 'Não informada'; ?>
                        </p>
                    </div>

                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Quantidade de Parcelas</h3>
                        <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($financeiro_novo['quantidade_parcelas'] ?? '0'); ?></p>
                    </div>

                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Valor Previsto</h3>
                        <p class="mt-1 text-sm text-gray-900">R$ <?php echo number_format($financeiro_novo['valor_previsto'] ?? 0, 2, ',', '.'); ?></p>
                    </div>

                    <?php if (!empty($financeiro_novo['observacoes'])): ?>
                    <div class="md:col-span-2">
                        <h3 class="text-sm font-medium text-gray-500">Observações Financeiras</h3>
                        <p class="mt-1 text-sm text-gray-900"><?php echo nl2br(htmlspecialchars($financeiro_novo['observacoes'])); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Informações do Polo -->
    <div class="bg-white shadow-md rounded-lg overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h2 class="text-lg font-semibold text-gray-800">Informações do Polo</h2>
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $polo['status'] === 'ativo' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                    <?php echo $polo['status'] === 'ativo' ? 'Ativo' : 'Inativo'; ?>
                </span>
            </div>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Nome do Polo</h3>
                    <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($polo['nome']); ?></p>
                </div>



                <div>
                    <div class="flex justify-between items-center">
                        <h3 class="text-sm font-medium text-gray-500">Nome MEC do Polo</h3>
                        <a href="polos.php?action=editar&id=<?php echo $polo['id']; ?>" class="text-xs bg-blue-500 hover:bg-blue-600 text-white font-medium py-1 px-2 rounded">
                            <i class="fas fa-edit"></i> Editar
                        </a>
                    </div>
                    <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($polo['mec'] ?? $polo['nome']); ?></p>
                    <p class="mt-1 text-xs text-gray-500">Este nome será exibido nas declarações como "Polo de Apoio Presencial".</p>
                </div>

                <div>
                    <h3 class="text-sm font-medium text-gray-500">Razão Social</h3>
                    <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($polo['razao_social'] ?? ''); ?></p>
                </div>

                <div>
                    <h3 class="text-sm font-medium text-gray-500">CNPJ</h3>
                    <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($polo['cnpj'] ?? 'Não informado'); ?></p>
                </div>

                <div>
                    <h3 class="text-sm font-medium text-gray-500">Responsável</h3>
                    <p class="mt-1 text-sm text-gray-900"><?php echo !empty($polo['responsavel']) ? htmlspecialchars($polo['responsavel']) : 'Não informado'; ?></p>
                </div>

                <div>
                    <h3 class="text-sm font-medium text-gray-500">Telefone</h3>
                    <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($polo['telefone']); ?></p>
                </div>

                <div>
                    <h3 class="text-sm font-medium text-gray-500">E-mail</h3>
                    <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($polo['email']); ?></p>
                </div>

                <div class="md:col-span-2">
                    <h3 class="text-sm font-medium text-gray-500">Endereço Completo</h3>
                    <p class="mt-1 text-sm text-gray-900">
                        <?php echo htmlspecialchars($polo['endereco'] ?? 'Não informado'); ?>

                        <?php if (!empty($polo['cidade'])): ?>
                            - <?php echo htmlspecialchars($polo['cidade']); ?>
                        <?php endif; ?>
                    </p>
                </div>

                <div>
                    <h3 class="text-sm font-medium text-gray-500">Data de Início da Parceria</h3>
                    <p class="mt-1 text-sm text-gray-900">
                        <?php echo !empty($polo['data_inicio_parceria']) ? date('d/m/Y', strtotime($polo['data_inicio_parceria'])) : 'Não informada'; ?>
                    </p>
                </div>

                <div>
                    <h3 class="text-sm font-medium text-gray-500">Data de Fim do Contrato</h3>
                    <p class="mt-1 text-sm text-gray-900">
                        <?php echo !empty($polo['data_fim_contrato']) ? date('d/m/Y', strtotime($polo['data_fim_contrato'])) : 'Não informada'; ?>
                    </p>
                </div>

                <div>
                    <h3 class="text-sm font-medium text-gray-500">Status do Contrato</h3>
                    <p class="mt-1 text-sm text-gray-900">
                        <?php
                        $status_contrato = $polo['status_contrato'] ?? 'ativo';
                        $status_contrato_texto = 'Ativo';
                        $status_contrato_cor = 'green';

                        if ($status_contrato === 'suspenso') {
                            $status_contrato_texto = 'Suspenso';
                            $status_contrato_cor = 'yellow';
                        } elseif ($status_contrato === 'encerrado') {
                            $status_contrato_texto = 'Encerrado';
                            $status_contrato_cor = 'red';
                        }
                        ?>
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-<?php echo $status_contrato_cor; ?>-100 text-<?php echo $status_contrato_cor; ?>-800">
                            <?php echo $status_contrato_texto; ?>
                        </span>
                    </p>
                </div>

                <?php if (!empty($polo['observacoes'])): ?>
                <div class="md:col-span-2">
                    <h3 class="text-sm font-medium text-gray-500">Observações</h3>
                    <p class="mt-1 text-sm text-gray-900"><?php echo nl2br(htmlspecialchars($polo['observacoes'])); ?></p>
                </div>
                <?php endif; ?>

                <div>
                    <h3 class="text-sm font-medium text-gray-500">Limite de Documentos</h3>
                    <div class="mt-1">
                        <?php
                        $percentual = $polo['limite_documentos'] > 0 ? ($polo['documentos_emitidos'] / $polo['limite_documentos'] * 100) : 0;
                        $cor = 'green';
                        if ($percentual >= 90) {
                            $cor = 'red';
                        } elseif ($percentual >= 75) {
                            $cor = 'yellow';
                        } elseif ($percentual >= 50) {
                            $cor = 'blue';
                        }
                        ?>
                        <div class="w-full bg-gray-200 rounded-full h-2.5 mb-2">
                            <div class="bg-<?php echo $cor; ?>-500 h-2.5 rounded-full" style="width: <?php echo min(100, $percentual); ?>%"></div>
                        </div>
                        <div class="flex justify-between text-xs text-gray-500">
                            <span><?php echo number_format($polo['documentos_emitidos'], 0, ',', '.'); ?> emitidos</span>
                            <span><?php echo number_format($percentual, 0); ?>% usado</span>
                            <span>Limite: <?php echo number_format($polo['limite_documentos'], 0, ',', '.'); ?></span>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="text-sm font-medium text-gray-500">Data de Cadastro</h3>
                    <p class="mt-1 text-sm text-gray-900">
                        <?php echo date('d/m/Y H:i', strtotime($polo['created_at'])); ?>
                    </p>
                </div>

                <div>
                    <h3 class="text-sm font-medium text-gray-500">Última Atualização</h3>
                    <p class="mt-1 text-sm text-gray-900">
                        <?php echo date('d/m/Y H:i', strtotime($polo['updated_at'])); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Cursos do Polo -->
    <div class="bg-white shadow-md rounded-lg overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h2 class="text-lg font-semibold text-gray-800">Cursos do Polo</h2>
                <div class="flex space-x-2">
                    <button type="button" onclick="abrirModalVincularCursos()" class="bg-green-500 hover:bg-green-600 text-white font-medium py-1 px-3 rounded text-sm">
                        <i class="fas fa-link mr-1"></i> Vincular Cursos
                    </button>
                    <a href="cursos.php?action=novo&polo_id=<?php echo $polo['id']; ?>" class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-1 px-3 rounded text-sm">
                        <i class="fas fa-plus mr-1"></i> Novo Curso
                    </a>
                    <a href="polos.php?action=visualizar&id=<?php echo $polo['id']; ?>&refresh=<?php echo time(); ?>" class="bg-purple-500 hover:bg-purple-600 text-white font-medium py-1 px-3 rounded text-sm">
                        <i class="fas fa-sync-alt mr-1"></i> Atualizar
                    </a>
                </div>
            </div>
        </div>

        <?php if (empty($cursos)): ?>
        <div class="p-6 text-center text-gray-500">
            <p>Nenhum curso cadastrado para este polo.</p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código</th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($cursos as $curso): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">
                                <a href="cursos.php?action=visualizar&id=<?php echo $curso['id']; ?>" class="hover:text-blue-600">
                                    <?php echo htmlspecialchars($curso['nome']); ?>
                                </a>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                <?php echo htmlspecialchars($curso['codigo']); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <div class="text-sm text-gray-900">
                                <?php echo $curso['total_alunos']; ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $curso['status'] === 'ativo' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo $curso['status'] === 'ativo' ? 'Ativo' : 'Inativo'; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                            <a href="cursos.php?action=visualizar&id=<?php echo $curso['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3" title="Visualizar">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="cursos.php?action=editar&id=<?php echo $curso['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="javascript:void(0);" onclick="confirmarDesvincular(<?php echo $curso['id']; ?>, <?php echo $polo['id']; ?>, '<?php echo addslashes($curso['nome']); ?>')" class="bg-orange-100 text-orange-600 hover:bg-orange-200 hover:text-orange-900 p-1 rounded mr-3" title="Desvincular do Polo">
                                <i class="fas fa-unlink"></i>
                            </a>
                            <a href="cursos.php?action=excluir&id=<?php echo $curso['id']; ?>" class="text-red-600 hover:text-red-900" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir este curso?');">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Turmas do Polo -->
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h2 class="text-lg font-semibold text-gray-800">Turmas do Polo</h2>
                <div class="flex space-x-2">
                    <button type="button" onclick="abrirModalVincularTurmas()" class="bg-green-500 hover:bg-green-600 text-white font-medium py-1 px-3 rounded text-sm">
                        <i class="fas fa-link mr-1"></i> Vincular Turmas
                    </button>
                    <a href="turmas.php?action=nova&polo_id=<?php echo $polo['id']; ?>" class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-1 px-3 rounded text-sm">
                        <i class="fas fa-plus mr-1"></i> Nova Turma
                    </a>
                    <a href="polos.php?action=visualizar&id=<?php echo $polo['id']; ?>&refresh=<?php echo time(); ?>" class="bg-purple-500 hover:bg-purple-600 text-white font-medium py-1 px-3 rounded text-sm">
                        <i class="fas fa-sync-alt mr-1"></i> Atualizar
                    </a>
                </div>
            </div>
        </div>

        <?php if (empty($turmas)): ?>
        <div class="p-6 text-center text-gray-500">
            <p>Nenhuma turma cadastrada para este polo.</p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Curso</th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Alunos</th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($turmas as $turma): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">
                                <a href="turmas.php?action=visualizar&id=<?php echo $turma['id']; ?>" class="hover:text-blue-600">
                                    <?php echo htmlspecialchars($turma['nome']); ?>
                                </a>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                <?php echo htmlspecialchars($turma['codigo']); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                <?php echo htmlspecialchars($turma['curso_nome']); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <div class="text-sm text-gray-900">
                                <?php echo $turma['total_alunos']; ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <?php
                            $statusClass = 'bg-gray-100 text-gray-800';
                            $statusText = 'Desconhecido';

                            switch($turma['status']) {
                                case 'planejada':
                                    $statusClass = 'bg-blue-100 text-blue-800';
                                    $statusText = 'Planejada';
                                    break;
                                case 'em_andamento':
                                    $statusClass = 'bg-green-100 text-green-800';
                                    $statusText = 'Em Andamento';
                                    break;
                                case 'concluida':
                                    $statusClass = 'bg-purple-100 text-purple-800';
                                    $statusText = 'Concluída';
                                    break;
                                case 'cancelada':
                                    $statusClass = 'bg-red-100 text-red-800';
                                    $statusText = 'Cancelada';
                                    break;
                                case 'ativo':
                                    $statusClass = 'bg-green-100 text-green-800';
                                    $statusText = 'Ativo';
                                    break;
                                case 'inativo':
                                    $statusClass = 'bg-red-100 text-red-800';
                                    $statusText = 'Inativo';
                                    break;
                            }
                            ?>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $statusClass; ?>">
                                <?php echo $statusText; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                            <a href="turmas.php?action=visualizar&id=<?php echo $turma['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3" title="Visualizar">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="turmas.php?action=editar&id=<?php echo $turma['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="turmas.php?action=excluir&id=<?php echo $turma['id']; ?>" class="text-red-600 hover:text-red-900" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir esta turma?');">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal para Desvincular Curso -->
<div id="modalDesvincularCurso" class="fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-2xl w-full max-w-md max-h-screen overflow-hidden flex flex-col border-t-4 border-orange-500">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center bg-gray-50">
            <h3 class="text-lg font-bold text-gray-800">
                <i class="fas fa-unlink text-orange-500 mr-2"></i> Desvincular Curso do Polo
            </h3>
            <button type="button" onclick="fecharModalDesvincularCurso()" class="text-gray-400 hover:text-gray-500">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-6">
            <p class="mb-4 text-lg font-medium" id="mensagem-desvincular">Tem certeza que deseja desvincular este curso do polo?</p>
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-yellow-500 text-xl"></i>
                    </div>
                    <div class="ml-3">
                        <p class="font-medium text-yellow-800 mb-1">Atenção!</p>
                        <p class="text-sm text-yellow-700">
                            Esta ação irá mover o curso para o polo temporário (ID 1), permitindo que você exclua o curso posteriormente se necessário.
                        </p>
                    </div>
                </div>
            </div>
            <div class="flex justify-end space-x-2 mt-6">
                <button type="button" onclick="fecharModalDesvincularCurso()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-medium py-2 px-4 rounded">
                    Cancelar
                </button>
                <form id="form-desvincular-curso" method="post" action="desvincular_curso.php">
                    <input type="hidden" name="curso_id" id="curso_id_desvincular" value="">
                    <input type="hidden" name="polo_id" id="polo_id_desvincular" value="">
                    <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white font-bold py-2 px-6 rounded shadow-md">
                        <i class="fas fa-unlink mr-2"></i> Desvincular
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Vincular Cursos e Turmas -->
<div id="modalVincularCursos" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-screen overflow-hidden flex flex-col">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-800">Vincular Cursos ao Polo</h3>
            <button type="button" onclick="fecharModalVincularCursos()" class="text-gray-400 hover:text-gray-500">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="p-6 overflow-y-auto" style="max-height: calc(100vh - 200px);">
            <form id="formVincularCursos" method="post" action="polos.php?action=vincular_cursos">
                <input type="hidden" name="polo_id" value="<?php echo $polo['id']; ?>">

                <div class="mb-4">
                    <label for="busca_curso" class="block text-sm font-medium text-gray-700 mb-1">Buscar Cursos</label>
                    <div class="flex">
                        <input type="text" id="busca_curso" class="flex-1 rounded-l-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" placeholder="Digite o nome do curso...">
                        <button type="button" onclick="buscarCursos()" class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-r-md">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>

                <div id="resultados_cursos" class="mb-4">
                    <div class="bg-gray-50 p-4 rounded-md">
                        <p class="text-gray-500 text-center">Use a busca acima para encontrar cursos para vincular.</p>
                    </div>
                </div>

                <div class="bg-gray-50 p-4 rounded-md mb-4">
                    <h4 class="font-medium text-gray-700 mb-2">Cursos Selecionados</h4>
                    <div id="cursos_selecionados" class="space-y-2">
                        <p class="text-gray-500 text-center">Nenhum curso selecionado.</p>
                    </div>
                </div>

                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="fecharModalVincularCursos()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-medium py-2 px-4 rounded">
                        Cancelar
                    </button>
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded">
                        Salvar Vinculações
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Funções para o modal de desvincular curso
function confirmarDesvincular(cursoId, poloId, cursoNome) {
    document.getElementById('curso_id_desvincular').value = cursoId;
    document.getElementById('polo_id_desvincular').value = poloId;
    document.getElementById('mensagem-desvincular').innerHTML = `Tem certeza que deseja desvincular o curso <span class="text-orange-600 font-bold">${cursoNome}</span> do polo?`;

    // Exibe o modal com efeito de fade-in
    const modal = document.getElementById('modalDesvincularCurso');
    modal.classList.remove('hidden');

    // Foca no botão de desvincular para facilitar o uso
    setTimeout(() => {
        const botaoDesvincular = document.querySelector('#form-desvincular-curso button[type="submit"]');
        if (botaoDesvincular) {
            botaoDesvincular.focus();
        }
    }, 100);
}

function fecharModalDesvincularCurso() {
    document.getElementById('modalDesvincularCurso').classList.add('hidden');
}

// Funções para o modal de vincular cursos
function abrirModalVincularCursos() {
    document.getElementById('modalVincularCursos').classList.remove('hidden');
}

function fecharModalVincularCursos() {
    document.getElementById('modalVincularCursos').classList.add('hidden');
}

// Variável para armazenar os cursos já selecionados
let cursosSelecionados = [];

// Função para buscar cursos
function buscarCursos() {
    const termo = document.getElementById('busca_curso').value.trim();
    if (!termo) {
        document.getElementById('resultados_cursos').innerHTML = '<div class="bg-yellow-100 p-4 rounded-md text-yellow-700">Digite um termo para buscar cursos.</div>';
        return;
    }

    // Mostrar indicador de carregamento
    document.getElementById('resultados_cursos').innerHTML = '<div class="text-center p-4"><i class="fas fa-spinner fa-spin text-blue-500 text-2xl"></i></div>';

    // Fazer a requisição AJAX
    fetch(`polos.php?action=buscar_cursos&termo=${encodeURIComponent(termo)}&polo_id=<?php echo $polo['id']; ?>`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                document.getElementById('resultados_cursos').innerHTML = `<div class="bg-red-100 p-4 rounded-md text-red-700">${data.error}</div>`;
                return;
            }

            if (!data.cursos || data.cursos.length === 0) {
                document.getElementById('resultados_cursos').innerHTML = '<div class="bg-yellow-100 p-4 rounded-md text-yellow-700">Nenhum curso encontrado com este termo.</div>';
                return;
            }

            // Construir a tabela de resultados
            let html = '<table class="min-w-full divide-y divide-gray-200">';
            html += '<thead class="bg-gray-50">';
            html += '<tr>';
            html += '<th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Selecionar</th>';
            html += '<th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>';
            html += '<th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sigla</th>';
            html += '<th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nível</th>';
            html += '<th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Modalidade</th>';
            html += '<th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>';
            html += '</tr></thead>';
            html += '<tbody class="bg-white divide-y divide-gray-200">';

            data.cursos.forEach(curso => {
                // Verificar se o curso já está selecionado
                const jaSelecionado = cursosSelecionados.some(c => c.id === curso.id);
                // Verificar se o curso já está vinculado a outro polo
                const jaVinculado = curso.polo_id && curso.polo_id != <?php echo $polo['id']; ?> && curso.polo_id > 0;

                html += '<tr class="hover:bg-gray-50">';
                html += `<td class="px-6 py-4 whitespace-nowrap">
                            <input type="checkbox" id="curso_${curso.id}" value="${curso.id}"
                                ${jaSelecionado ? 'checked' : ''}
                                onchange="toggleCursoSelecionado(${curso.id}, '${curso.nome.replace(/'/g, "\\'")}')"
                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        </td>`;
                html += `<td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">${curso.nome}</div>
                            ${jaVinculado ? `<div class="text-xs text-orange-500">Atualmente vinculado ao polo: ${curso.polo_nome || 'Outro polo'}</div>` : ''}
                        </td>`;
                html += `<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${curso.sigla || '-'}</td>`;
                html += `<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${curso.nivel || '-'}</td>`;
                html += `<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${curso.modalidade || '-'}</td>`;
                html += `<td class="px-6 py-4 whitespace-nowrap text-center">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${curso.status === 'ativo' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                ${curso.status || 'Indefinido'}
                            </span>
                        </td>`;
                html += '</tr>';
            });

            html += '</tbody></table>';
            document.getElementById('resultados_cursos').innerHTML = html;
        })
        .catch(error => {
            console.error('Erro ao buscar cursos:', error);
            document.getElementById('resultados_cursos').innerHTML = '<div class="bg-red-100 p-4 rounded-md text-red-700">Erro ao buscar cursos. Tente novamente.</div>';
        });
}

// Função para adicionar/remover curso da lista de selecionados
function toggleCursoSelecionado(cursoId, cursoNome) {
    const checkbox = document.getElementById(`curso_${cursoId}`);

    if (checkbox.checked) {
        // Adicionar curso à lista de selecionados
        cursosSelecionados.push({ id: cursoId, nome: cursoNome });
    } else {
        // Remover curso da lista de selecionados
        cursosSelecionados = cursosSelecionados.filter(curso => curso.id !== cursoId);
    }

    // Atualizar a exibição dos cursos selecionados
    atualizarCursosSelecionados();
}

// Função para atualizar a exibição dos cursos selecionados
function atualizarCursosSelecionados() {
    const container = document.getElementById('cursos_selecionados');

    if (cursosSelecionados.length === 0) {
        container.innerHTML = '<p class="text-gray-500 text-center">Nenhum curso selecionado.</p>';
        return;
    }

    let html = '';
    cursosSelecionados.forEach(curso => {
        html += `
        <div class="flex justify-between items-center bg-white p-2 rounded border">
            <span class="text-sm">${curso.nome}</span>
            <input type="hidden" name="cursos_ids[]" value="${curso.id}">
            <button type="button" onclick="removerCursoSelecionado(${curso.id})" class="text-red-500 hover:text-red-700">
                <i class="fas fa-times"></i>
            </button>
        </div>`;
    });

    container.innerHTML = html;
}

// Função para remover um curso da lista de selecionados
// Adiciona rolagem suave para os links de âncora
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();

            const targetId = this.getAttribute('href').substring(1);
            const targetElement = document.getElementById(targetId);

            if (targetElement) {
                window.scrollTo({
                    top: targetElement.offsetTop - 20,
                    behavior: 'smooth'
                });
            }
        });
    });
});

function removerCursoSelecionado(cursoId) {
    cursosSelecionados = cursosSelecionados.filter(curso => curso.id !== cursoId);

    // Desmarcar o checkbox se estiver visível
    const checkbox = document.getElementById(`curso_${cursoId}`);
    if (checkbox) {
        checkbox.checked = false;
        checkbox.disabled = false;
    }

    // Atualizar a exibição dos cursos selecionados
    atualizarCursosSelecionados();
}

// Inicializar os cursos já vinculados ao polo
document.addEventListener('DOMContentLoaded', function() {
    // Pré-carregar os cursos já vinculados ao polo
    <?php if (!empty($cursos)): ?>
    <?php foreach ($cursos as $curso): ?>
    cursosSelecionados.push({ id: <?php echo $curso['id']; ?>, nome: '<?php echo addslashes($curso['nome']); ?>' });
    <?php endforeach; ?>
    atualizarCursosSelecionados();
    <?php endif; ?>
});

// Adicionar um evento para garantir que os cursos já vinculados sejam incluídos no formulário
document.getElementById('formVincularCursos').addEventListener('submit', function(e) {
    // Se não houver cursos selecionados, adiciona os cursos já vinculados ao formulário
    if (document.querySelectorAll('input[name="cursos_ids[]"]').length === 0 && cursosSelecionados.length > 0) {
        // Adiciona campos ocultos para cada curso já vinculado
        cursosSelecionados.forEach(function(curso) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'cursos_ids[]';
            input.value = curso.id;
            this.appendChild(input);
        }, this);
    }

    // Adiciona um campo oculto para forçar a atualização da página
    const refreshInput = document.createElement('input');
    refreshInput.type = 'hidden';
    refreshInput.name = 'force_refresh';
    refreshInput.value = 'true';
    this.appendChild(refreshInput);

    // Mostra uma mensagem de carregamento
    const loadingDiv = document.createElement('div');
    loadingDiv.className = 'fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50';
    loadingDiv.innerHTML = '<div class="bg-white p-4 rounded-lg shadow-lg"><i class="fas fa-spinner fa-spin mr-2"></i> Salvando vinculações...</div>';
    document.body.appendChild(loadingDiv);
});
</script>

<!-- Modal para Vincular Turmas -->
<div id="modalVincularTurmas" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-screen overflow-hidden flex flex-col">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-800">Vincular Turmas ao Polo</h3>
            <button type="button" onclick="fecharModalVincularTurmas()" class="text-gray-400 hover:text-gray-500">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="p-6 overflow-y-auto" style="max-height: calc(100vh - 200px);">
            <form id="formVincularTurmas" method="post" action="polos.php?action=vincular_turmas">
                <input type="hidden" name="polo_id" value="<?php echo $polo['id']; ?>">

                <div class="mb-4">
                    <label for="busca_turma" class="block text-sm font-medium text-gray-700 mb-1">Buscar Turmas</label>
                    <div class="flex">
                        <input type="text" id="busca_turma" class="flex-1 rounded-l-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" placeholder="Digite o nome da turma...">
                        <button type="button" onclick="buscarTurmas()" class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-r-md">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>

                <div id="resultados_turmas" class="mb-4">
                    <div class="bg-gray-50 p-4 rounded-md">
                        <p class="text-gray-500 text-center">Use a busca acima para encontrar turmas para vincular.</p>
                    </div>
                </div>

                <div class="bg-gray-50 p-4 rounded-md mb-4">
                    <h4 class="font-medium text-gray-700 mb-2">Turmas Selecionadas</h4>
                    <div id="turmas_selecionadas" class="space-y-2">
                        <p class="text-gray-500 text-center">Nenhuma turma selecionada.</p>
                    </div>
                </div>

                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="fecharModalVincularTurmas()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-medium py-2 px-4 rounded">
                        Cancelar
                    </button>
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded">
                        Salvar Vinculações
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de Confirmação de Acesso -->
<div id="modal-acesso-polo" class="fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center z-50 hidden">
    <div class="modal-content max-w-md bg-white rounded-lg shadow-2xl border-t-4 border-<?php echo $tem_acesso ? 'orange' : 'green'; ?>-500">
        <div class="modal-header flex justify-between items-center p-4 border-b border-gray-200 bg-gray-50">
            <h3 class="text-lg font-bold text-gray-800" id="modal-acesso-titulo">
                <i class="<?php echo $btn_acesso_icon; ?> text-<?php echo $tem_acesso ? 'orange' : 'green'; ?>-500 mr-2"></i>
                <?php echo $tem_acesso ? 'Redefinir Acesso do Polo' : 'Criar Acesso para o Polo'; ?>
            </h3>
            <button type="button" class="close-modal text-gray-400 hover:text-gray-600 p-1 rounded-full hover:bg-gray-200" onclick="document.getElementById('modal-acesso-polo').classList.add('hidden'); document.getElementById('modal-acesso-polo').style.display = 'none';">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body p-6">
            <?php if ($tem_acesso): ?>
                <p class="mb-4">Você está prestes a redefinir o acesso do polo <strong><?php echo htmlspecialchars($polo['nome']); ?></strong>.</p>
                <p class="mb-4">A senha será redefinida para o padrão e o usuário poderá acessar o sistema com as credenciais atualizadas.</p>
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">
                                Esta ação irá redefinir a senha do usuário para o padrão.
                            </p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <p class="mb-4">Você está prestes a criar um acesso para o polo <strong><?php echo htmlspecialchars($polo['nome']); ?></strong>.</p>
                <p class="mb-4">Será criado um usuário com o tipo "polo" que poderá acessar o sistema com as credenciais geradas.</p>
                <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-info-circle text-blue-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-700">
                                O e-mail do polo será usado como login e uma senha padrão será gerada.
                            </p>
                        </div>
                    </div>
                </div>
                <?php if (empty($polo['email'])): ?>
                <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle text-red-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700">
                                <strong>Atenção:</strong> O polo não possui um e-mail cadastrado. Por favor, edite o polo e adicione um e-mail antes de criar o acesso.
                            </p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="bg-gray-50 p-4 rounded-md mb-4">
                <h4 class="text-sm font-medium text-gray-700 mb-2">Informações de Acesso:</h4>
                <p class="text-sm"><strong>Login:</strong> <?php echo htmlspecialchars($polo['email'] ?? 'Não definido'); ?></p>
                <p class="text-sm"><strong>Senha:</strong> Polo@<?php echo date('Y'); ?> (senha padrão)</p>
            </div>
        </div>
        <div class="modal-footer flex justify-end space-x-2 p-4 bg-gray-50 rounded-b-lg">
            <button type="button" class="close-modal bg-gray-300 hover:bg-gray-400 text-gray-800 font-medium py-2 px-4 rounded" onclick="document.getElementById('modal-acesso-polo').classList.add('hidden'); document.getElementById('modal-acesso-polo').style.display = 'none';">
                <i class="fas fa-times mr-2"></i> Cancelar
            </button>
            <form method="post" action="polos_acesso.php" id="form-acesso-polo">
                <input type="hidden" name="polo_id" value="<?php echo $polo['id']; ?>">
                <button type="submit" id="btn-confirmar-acesso" class="<?php echo $tem_acesso ? 'bg-orange-500 hover:bg-orange-600' : 'bg-green-500 hover:bg-green-600'; ?> text-white font-bold py-2 px-6 rounded shadow-md" <?php echo empty($polo['email']) ? 'disabled' : ''; ?>>
                    <i class="fas fa-<?php echo $tem_acesso ? 'key' : 'user-plus'; ?> mr-2"></i> Confirmar
                </button>
            </form>
            <script>
                // Adiciona um evento para o formulário de acesso
                document.addEventListener('DOMContentLoaded', function() {
                    const formAcessoPolo = document.getElementById('form-acesso-polo');
                    if (formAcessoPolo) {
                        console.log('Formulário de acesso encontrado');
                        formAcessoPolo.addEventListener('submit', function(e) {
                            console.log('Formulário de acesso enviado');

                            // Desabilita o botão para evitar múltiplos envios
                            const btnConfirmar = document.getElementById('btn-confirmar-acesso');
                            if (btnConfirmar) {
                                btnConfirmar.disabled = true;
                                btnConfirmar.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Processando...';
                            }

                            // Mostra uma mensagem de carregamento
                            const loadingDiv = document.createElement('div');
                            loadingDiv.id = 'loading-overlay';
                            loadingDiv.className = 'fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center z-50';
                            loadingDiv.innerHTML = '<div class="bg-white p-6 rounded-lg shadow-xl"><i class="fas fa-spinner fa-spin mr-2 text-blue-500 text-xl"></i> Processando solicitação...</div>';
                            document.body.appendChild(loadingDiv);

                            // Permite que o formulário seja enviado
                            return true;
                        });
                    } else {
                        console.error('Formulário de acesso não encontrado');
                    }
                });
            </script>
        </div>
    </div>
</div>

<script>
// Funções para o modal de vincular turmas
function abrirModalVincularTurmas() {
    document.getElementById('modalVincularTurmas').classList.remove('hidden');
}

function fecharModalVincularTurmas() {
    document.getElementById('modalVincularTurmas').classList.add('hidden');
}

// Variável para armazenar as turmas já selecionadas
let turmasSelecionadas = [];

// Função para buscar turmas
function buscarTurmas() {
    const termoBusca = document.getElementById('busca_turma').value.trim();
    if (termoBusca.length < 2) {
        alert('Digite pelo menos 2 caracteres para buscar.');
        return;
    }

    // Mostrar indicador de carregamento
    document.getElementById('resultados_turmas').innerHTML = '<div class="text-center p-4"><i class="fas fa-spinner fa-spin"></i> Buscando turmas...</div>';

    // Fazer a requisição AJAX
    fetch(`polos.php?action=buscar_turmas&termo=${encodeURIComponent(termoBusca)}&polo_id=<?php echo $polo['id']; ?>`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                document.getElementById('resultados_turmas').innerHTML = `<div class="bg-red-100 p-4 rounded-md text-red-700">${data.error}</div>`;
                return;
            }

            if (data.turmas.length === 0) {
                document.getElementById('resultados_turmas').innerHTML = '<div class="bg-gray-50 p-4 rounded-md"><p class="text-gray-500 text-center">Nenhuma turma encontrada com este termo.</p></div>';
                return;
            }

            // Renderizar os resultados
            let html = '<div class="bg-white border rounded-md overflow-hidden">';
            html += '<table class="min-w-full divide-y divide-gray-200">';
            html += '<thead class="bg-gray-50"><tr>';
            html += '<th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Selecionar</th>';
            html += '<th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>';
           html += '<th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Curso</th>';
            html += '<th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Turno</th>';
            html += '<th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>';
            html += '</tr></thead>';
            html += '<tbody class="bg-white divide-y divide-gray-200">';

            data.turmas.forEach(turma => {
                // Verificar se a turma já está selecionada
                const jaSelecionada = turmasSelecionadas.some(t => t.id === turma.id);
                // Verificar se a turma já está vinculada a outro polo
                const jaVinculada = turma.polo_id && turma.polo_id != <?php echo $polo['id']; ?> && turma.polo_id > 0;

                html += '<tr class="hover:bg-gray-50">';
                html += `<td class="px-6 py-4 whitespace-nowrap">
                            <input type="checkbox" id="turma_${turma.id}" value="${turma.id}"
                                ${jaSelecionada ? 'checked disabled' : ''}
                                onchange="toggleTurmaSelecionada(${turma.id}, '${turma.nome.replace(/'/g, "\\'")}')"
                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        </td>`;
                html += `<td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">${turma.nome}</div>
                            ${jaVinculada ? `<div class="text-xs text-red-500">Vinculada ao polo: ${turma.polo_nome || 'Outro polo'}</div>` : ''}
                        </td>`;
                html += `<td class="px-6 py-4 whitespace-nowrap"><div class="text-sm text-gray-900">${turma.curso_nome}</div></td>`;
                html += `<td class="px-6 py-4 whitespace-nowrap"><div class="text-sm text-gray-900">${turma.turno}</div></td>`;
                html += `<td class="px-6 py-4 whitespace-nowrap text-center">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${turma.status === 'em_andamento' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'}">
                                ${turma.status === 'em_andamento' ? 'Em andamento' : turma.status === 'planejada' ? 'Planejada' : turma.status === 'concluida' ? 'Concluída' : 'Cancelada'}
                            </span>
                        </td>`;
                html += '</tr>';
            });

            html += '</tbody></table></div>';
            document.getElementById('resultados_turmas').innerHTML = html;
        })
        .catch(error => {
            console.error('Erro ao buscar turmas:', error);
            document.getElementById('resultados_turmas').innerHTML = '<div class="bg-red-100 p-4 rounded-md text-red-700">Erro ao buscar turmas. Tente novamente.</div>';
        });
}

// Função para adicionar/remover turma da lista de selecionadas
function toggleTurmaSelecionada(turmaId, turmaNome) {
    const checkbox = document.getElementById(`turma_${turmaId}`);

    if (checkbox.checked) {
        // Adicionar turma à lista de selecionadas
        turmasSelecionadas.push({ id: turmaId, nome: turmaNome });
    } else {
        // Remover turma da lista de selecionadas
        turmasSelecionadas = turmasSelecionadas.filter(turma => turma.id !== turmaId);
    }

    // Atualizar a exibição das turmas selecionadas
    atualizarTurmasSelecionadas();
}

// Função para atualizar a exibição das turmas selecionadas
function atualizarTurmasSelecionadas() {
    const container = document.getElementById('turmas_selecionadas');

    if (turmasSelecionadas.length === 0) {
        container.innerHTML = '<p class="text-gray-500 text-center">Nenhuma turma selecionada.</p>';
        return;
    }

    let html = '';
    turmasSelecionadas.forEach(turma => {
        html += `
        <div class="flex justify-between items-center bg-white p-2 rounded border">
            <span class="text-sm">${turma.nome}</span>
            <input type="hidden" name="turmas_ids[]" value="${turma.id}">
            <button type="button" onclick="removerTurmaSelecionada(${turma.id})" class="text-red-500 hover:text-red-700">
                <i class="fas fa-times"></i>
            </button>
        </div>`;
    });

    container.innerHTML = html;
}

// Função para remover uma turma da lista de selecionadas
function removerTurmaSelecionada(turmaId) {
    turmasSelecionadas = turmasSelecionadas.filter(turma => turma.id !== turmaId);

    // Desmarcar o checkbox se estiver visível
    const checkbox = document.getElementById(`turma_${turmaId}`);
    if (checkbox) {
        checkbox.checked = false;
        checkbox.disabled = false;
    }

    // Atualizar a exibição das turmas selecionadas
    atualizarTurmasSelecionadas();
}

// Inicializar as turmas já vinculadas ao polo e configurar eventos
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM carregado - Inicializando scripts da página de visualização de polo');

    // Pré-carregar as turmas já vinculadas ao polo
    <?php if (!empty($turmas)): ?>
    <?php foreach ($turmas as $turma): ?>
    turmasSelecionadas.push({ id: <?php echo $turma['id']; ?>, nome: '<?php echo addslashes($turma['nome']); ?>' });
    <?php endforeach; ?>
    atualizarTurmasSelecionadas();
    <?php endif; ?>

    // Inicializa o botão de vincular turmas
    const btnVincularTurmas = document.getElementById('btnVincularTurmas');
    if (btnVincularTurmas) {
        btnVincularTurmas.addEventListener('click', abrirModalVincularTurmas);
        console.log('Evento de clique adicionado ao botão de vincular turmas');
    }

    // Inicializa o botão de acesso do polo
    const btnAcessoPolo = document.getElementById('btn-acesso-polo');
    if (btnAcessoPolo) {
        console.log('Botão de acesso do polo encontrado:', btnAcessoPolo);

        // Adiciona o evento de clique diretamente
        btnAcessoPolo.onclick = function(e) {
            e.preventDefault();
            console.log('Botão de acesso do polo clicado');

            const modal = document.getElementById('modal-acesso-polo');
            if (modal) {
                console.log('Modal encontrado, exibindo...');
                // Remove a classe hidden e define o display como flex
                modal.classList.remove('hidden');
                modal.style.display = 'flex';

                // Adiciona uma mensagem de log para confirmar que o modal está visível
                console.log('Modal deve estar visível agora. Display:', modal.style.display, 'Classes:', modal.className);

                // Foca no botão de confirmar para facilitar o uso
                const btnConfirmar = document.getElementById('btn-confirmar-acesso');
                if (btnConfirmar && !btnConfirmar.disabled) {
                    setTimeout(() => btnConfirmar.focus(), 100);
                }
            } else {
                console.error('Modal não encontrado');
                alert('Erro: Modal de acesso não encontrado. Por favor, recarregue a página e tente novamente.');
            }

            return false;
        };

        // Adiciona um log para confirmar que o evento foi adicionado
        console.log('Evento de clique adicionado ao botão de acesso do polo');
    } else {
        console.error('Botão de acesso do polo não encontrado');
        // Tenta encontrar o botão novamente após um pequeno atraso
        setTimeout(() => {
            const btnRetry = document.getElementById('btn-acesso-polo');
            if (btnRetry) {
                console.log('Botão encontrado após retry');
                btnRetry.onclick = function() {
                    document.getElementById('modal-acesso-polo').classList.remove('hidden');
                    document.getElementById('modal-acesso-polo').style.display = 'flex';
                    return false;
                };
            }
        }, 500);
    }

    // Fecha os modais quando clica no botão de fechar
    document.querySelectorAll('.close-modal').forEach(function(button) {
        console.log('Botão de fechar modal encontrado:', button);
        button.addEventListener('click', function() {
            console.log('Botão de fechar modal clicado');
            // Fecha o modal de acesso do polo
            const modalAcesso = document.getElementById('modal-acesso-polo');
            if (modalAcesso) {
                console.log('Fechando modal de acesso');
                modalAcesso.classList.add('hidden');
                modalAcesso.style.display = 'none';
            }

            // Fecha outros modais
            document.querySelectorAll('.modal').forEach(function(modal) {
                console.log('Fechando modal:', modal.id);
                modal.classList.add('hidden');
            });
        });
    });
});

// Adicionar um evento para garantir que as turmas já vinculadas sejam incluídas no formulário
document.getElementById('formVincularTurmas').addEventListener('submit', function(e) {
    // Se não houver turmas selecionadas, adiciona as turmas já vinculadas ao formulário
    if (document.querySelectorAll('input[name="turmas_ids[]"]').length === 0 && turmasSelecionadas.length > 0) {
        // Adiciona campos ocultos para cada turma já vinculada
        turmasSelecionadas.forEach(function(turma) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'turmas_ids[]';
            input.value = turma.id;
            this.appendChild(input);
        }, this);
    }

    // Adiciona um campo oculto para forçar a atualização da página
    const refreshInput = document.createElement('input');
    refreshInput.type = 'hidden';
    refreshInput.name = 'force_refresh';
    refreshInput.value = 'true';
    this.appendChild(refreshInput);

    // Mostra uma mensagem de carregamento
    const loadingDiv = document.createElement('div');
    loadingDiv.className = 'fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50';
    loadingDiv.innerHTML = '<div class="bg-white p-4 rounded-lg shadow-lg"><i class="fas fa-spinner fa-spin mr-2"></i> Salvando vinculações...</div>';
    document.body.appendChild(loadingDiv);
});
</script>
