<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Informações do Aluno -->
    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="p-6">
            <div class="flex justify-between items-start">
                <div class="flex items-center">
                    <div class="h-16 w-16 rounded-full bg-blue-100 flex items-center justify-center">
                        <span class="text-blue-600 font-bold text-2xl"><?php echo strtoupper(substr($aluno['nome'], 0, 1)); ?></span>
                    </div>
                    <div class="ml-4">
                        <h2 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($aluno['nome']); ?></h2>
                        <p class="text-gray-600">
                            <?php if (!empty($aluno['id_legado'])): ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 mr-2">
                                ID Legado: <?php echo htmlspecialchars($aluno['id_legado']); ?>
                            </span>
                            <?php endif; ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $aluno['status'] === 'ativo' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo $aluno['status'] === 'ativo' ? 'Ativo' : 'Inativo'; ?>
                            </span>
                        </p>
                    </div>
                </div>
                <div class="flex space-x-2">
                    <a href="alunos.php?action=editar&id=<?php echo $aluno['id']; ?>" class="btn-secondary py-2">
                        <i class="fas fa-edit mr-2"></i> Editar
                    </a>
                    <a href="documentos_aluno.php?id=<?php echo $aluno['id']; ?>" class="btn-primary py-2">
                        <i class="fas fa-id-card mr-2"></i> Ver Documentos Pessoais
                    </a>
                </div>
            </div>

            <div class="mt-6 border-t border-gray-200 pt-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Informações Pessoais</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Nome Completo</p>
                        <p class="mt-1"><?php echo htmlspecialchars($aluno['nome']); ?></p>
                    </div>

                    <?php if (!empty($aluno['nome_social'])): ?>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Nome Social</p>
                        <p class="mt-1"><?php echo htmlspecialchars($aluno['nome_social']); ?></p>
                    </div>
                    <?php endif; ?>

                    <div>
                        <p class="text-sm font-medium text-gray-500">CPF</p>
                        <p class="mt-1"><?php echo formatarCpf($aluno['cpf']); ?></p>
                    </div>

                    <?php if (!empty($aluno['rg'])): ?>
                    <div>
                        <p class="text-sm font-medium text-gray-500">RG</p>
                        <p class="mt-1"><?php echo htmlspecialchars($aluno['rg']); ?></p>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($aluno['orgao_expedidor'])): ?>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Órgão Expedidor</p>
                        <p class="mt-1"><?php echo htmlspecialchars($aluno['orgao_expedidor']); ?></p>
                    </div>
                    <?php endif; ?>

                    <div>
                        <p class="text-sm font-medium text-gray-500">Data de Nascimento</p>
                        <p class="mt-1"><?php echo !empty($aluno['data_nascimento']) ? date('d/m/Y', strtotime($aluno['data_nascimento'])) : 'Não informada'; ?></p>
                    </div>

                    <?php if (!empty($aluno['sexo'])): ?>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Sexo</p>
                        <p class="mt-1"><?php echo ucfirst($aluno['sexo']); ?></p>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($aluno['estado_civil_id'])): ?>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Estado Civil</p>
                        <p class="mt-1">
                            <?php
                            $estados_civis = [
                                1 => 'Solteiro(a)',
                                2 => 'Casado(a)',
                                3 => 'Divorciado(a)',
                                4 => 'Viúvo(a)',
                                5 => 'Separado(a)',
                                6 => 'União Estável'
                            ];
                            echo $estados_civis[$aluno['estado_civil_id']] ?? 'Não informado';
                            ?>
                        </p>
                    </div>
                    <?php endif; ?>

                    <div>
                        <p class="text-sm font-medium text-gray-500">E-mail</p>
                        <p class="mt-1"><?php echo htmlspecialchars($aluno['email']); ?></p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500">Telefone</p>
                        <p class="mt-1"><?php echo !empty($aluno['telefone']) ? htmlspecialchars($aluno['telefone']) : 'Não informado'; ?></p>
                    </div>

                    <?php if (!empty($aluno['celular'])): ?>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Celular</p>
                        <p class="mt-1"><?php echo htmlspecialchars($aluno['celular']); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mt-6 border-t border-gray-200 pt-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Endereço</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <p class="text-sm font-medium text-gray-500">Endereço</p>
                        <p class="mt-1"><?php echo !empty($aluno['endereco']) ? htmlspecialchars($aluno['endereco']) : 'Não informado'; ?></p>
                    </div>

                    <?php if (!empty($aluno['numero'])): ?>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Número</p>
                        <p class="mt-1"><?php echo htmlspecialchars($aluno['numero']); ?></p>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($aluno['bairro'])): ?>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Bairro</p>
                        <p class="mt-1"><?php echo htmlspecialchars($aluno['bairro']); ?></p>
                    </div>
                    <?php endif; ?>

                    <div>
                        <p class="text-sm font-medium text-gray-500">Cidade</p>
                        <p class="mt-1"><?php echo !empty($aluno['cidade']) ? htmlspecialchars($aluno['cidade']) : 'Não informada'; ?></p>
                    </div>



                    <div>
                        <p class="text-sm font-medium text-gray-500">CEP</p>
                        <p class="mt-1"><?php echo !empty($aluno['cep']) ? htmlspecialchars($aluno['cep']) : 'Não informado'; ?></p>
                    </div>
                </div>
            </div>

            <div class="mt-6 border-t border-gray-200 pt-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Informações Acadêmicas</h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Polo</p>
                        <p class="mt-1">
                            <?php if (!empty($aluno['polo_id']) && isset($polo_nome)): ?>
                                <?php echo htmlspecialchars($polo_nome); ?>
                            <?php else: ?>
                                Não definido
                            <?php endif; ?>
                        </p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500">Curso</p>
                        <p class="mt-1">
                            <?php if (!empty($aluno['curso_id']) && isset($curso_nome)): ?>
                                <?php echo htmlspecialchars($curso_nome); ?>
                            <?php else: ?>
                                Não definido
                            <?php endif; ?>
                        </p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500">Turma</p>
                        <p class="mt-1">
                            <?php if (!empty($aluno['turma_id']) && isset($turma_nome)): ?>
                                <?php echo htmlspecialchars($turma_nome); ?>
                            <?php else: ?>
                                Não definida
                            <?php endif; ?>
                        </p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500">Data de Ingresso</p>
                        <p class="mt-1">
                            <?php if (!empty($aluno['data_ingresso'])): ?>
                                <?php echo date('d/m/Y', strtotime($aluno['data_ingresso'])); ?>
                            <?php elseif (!empty($aluno['curso_inicio'])): ?>
                                <?php echo date('d/m/Y', strtotime($aluno['curso_inicio'])); ?> <span class="text-xs text-gray-500">(data de início do curso)</span>
                            <?php else: ?>
                                Não informada
                            <?php endif; ?>
                        </p>
                    </div>

                    <?php if (!empty($aluno['curso_inicio'])): ?>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Início do Curso</p>
                        <p class="mt-1"><?php echo date('d/m/Y', strtotime($aluno['curso_inicio'])); ?></p>
                    </div>
                    <?php endif; ?>

                    <div>
                        <p class="text-sm font-medium text-gray-500">Fim do Curso</p>
                        <p class="mt-1">
                            <?php if (!empty($aluno['curso_fim'])): ?>
                                <?php echo date('d/m/Y', strtotime($aluno['curso_fim'])); ?>
                            <?php else: ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Em andamento</span>
                            <?php endif; ?>
                        </p>
                    </div>

                    <?php if (!empty($aluno['previsao_conclusao'])): ?>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Previsão de Conclusão</p>
                        <p class="mt-1"><?php echo date('d/m/Y', strtotime($aluno['previsao_conclusao'])); ?></p>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($aluno['mono_titulo']) || !empty($aluno['mono_data']) || !empty($aluno['mono_nota']) || !empty($aluno['mono_prazo'])): ?>
                <div class="mt-4 border-t border-gray-200 pt-4">
                    <h4 class="text-md font-semibold text-gray-700 mb-3">Informações da Monografia</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php if (!empty($aluno['mono_titulo'])): ?>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Título</p>
                            <p class="mt-1"><?php echo htmlspecialchars($aluno['mono_titulo']); ?></p>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($aluno['mono_data'])): ?>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Data da Apresentação</p>
                            <p class="mt-1"><?php echo date('d/m/Y', strtotime($aluno['mono_data'])); ?></p>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($aluno['mono_nota'])): ?>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Nota</p>
                            <p class="mt-1"><?php echo number_format($aluno['mono_nota'], 2, ',', '.'); ?></p>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($aluno['mono_prazo'])): ?>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Prazo</p>
                            <p class="mt-1"><?php echo date('d/m/Y', strtotime($aluno['mono_prazo'])); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($aluno['bolsa']) || !empty($aluno['desconto'])): ?>
                <div class="mt-4 border-t border-gray-200 pt-4">
                    <h4 class="text-md font-semibold text-gray-700 mb-3">Informações Financeiras</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php if (!empty($aluno['bolsa'])): ?>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Bolsa</p>
                            <p class="mt-1">R$ <?php echo number_format($aluno['bolsa'], 2, ',', '.'); ?></p>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($aluno['desconto'])): ?>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Desconto</p>
                            <p class="mt-1">R$ <?php echo number_format($aluno['desconto'], 2, ',', '.'); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="mt-6 border-t border-gray-200 pt-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Informações do Sistema</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Data de Cadastro</p>
                        <p class="mt-1"><?php echo date('d/m/Y H:i', strtotime($aluno['created_at'])); ?></p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500">Última Atualização</p>
                        <p class="mt-1"><?php echo date('d/m/Y H:i', strtotime($aluno['updated_at'])); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="lg:col-span-1 space-y-6">
        <!-- Matrículas -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Matrículas</h3>
                    <a href="matriculas.php?action=nova&aluno_id=<?php echo $aluno['id']; ?>" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                        <i class="fas fa-plus mr-1"></i> Nova Matrícula
                    </a>
                </div>

                <?php if (empty($matriculas)): ?>
                <p class="text-gray-500 text-sm">Nenhuma matrícula encontrada.</p>
                <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($matriculas as $matricula): ?>
                    <div class="border border-gray-200 rounded-lg p-3">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="font-medium text-gray-800"><?php echo htmlspecialchars($matricula['curso_nome']); ?></p>
                                <p class="text-sm text-gray-600">Turma: <?php echo htmlspecialchars($matricula['turma_nome'] ?? 'Não definida'); ?></p>
                                <p class="text-sm text-gray-600">Polo: <?php echo htmlspecialchars($matricula['polo_nome'] ?? 'Não definido'); ?></p>
                                <div class="mt-1 flex items-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium <?php
                                        switch ($matricula['status']) {
                                            case 'ativo':
                                                echo 'bg-green-100 text-green-800';
                                                break;
                                            case 'pendente':
                                                echo 'bg-yellow-100 text-yellow-800';
                                                break;
                                            case 'cancelado':
                                                echo 'bg-red-100 text-red-800';
                                                break;
                                            case 'concluido':
                                                echo 'bg-blue-100 text-blue-800';
                                                break;
                                            default:
                                                echo 'bg-gray-100 text-gray-800';
                                        }
                                    ?>">
                                        <?php
                                            switch ($matricula['status']) {
                                                case 'ativo':
                                                    echo 'Ativo';
                                                    break;
                                                case 'pendente':
                                                    echo 'Pendente';
                                                    break;
                                                case 'cancelado':
                                                    echo 'Cancelado';
                                                    break;
                                                case 'concluido':
                                                    echo 'Concluído';
                                                    break;
                                                default:
                                                    echo ucfirst($matricula['status']);
                                            }
                                        ?>
                                    </span>
                                    <span class="text-xs text-gray-500 ml-2">
                                        <?php echo date('d/m/Y', strtotime($matricula['created_at'])); ?>
                                    </span>
                                </div>
                            </div>
                            <a href="matriculas.php?action=visualizar&id=<?php echo $matricula['id']; ?>" class="text-blue-600 hover:text-blue-800">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Documentos -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Documentos</h3>
                    <a href="documentos.php?action=solicitar&aluno_id=<?php echo $aluno['id']; ?>" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                        <i class="fas fa-plus mr-1"></i> Solicitar Documento
                    </a>
                </div>

                <?php if (empty($documentos)): ?>
                <p class="text-gray-500 text-sm">Nenhum documento encontrado.</p>
                <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($documentos as $documento): ?>
                    <div class="border border-gray-200 rounded-lg p-3">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="font-medium text-gray-800"><?php echo htmlspecialchars($documento['tipo_documento_nome']); ?></p>
                                <div class="mt-1 flex items-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium <?php
                                        switch ($documento['status']) {
                                            case 'solicitado':
                                                echo 'bg-yellow-100 text-yellow-800';
                                                break;
                                            case 'processando':
                                                echo 'bg-blue-100 text-blue-800';
                                                break;
                                            case 'concluido':
                                                echo 'bg-green-100 text-green-800';
                                                break;
                                            case 'cancelado':
                                                echo 'bg-red-100 text-red-800';
                                                break;
                                            default:
                                                echo 'bg-gray-100 text-gray-800';
                                        }
                                    ?>">
                                        <?php
                                            switch ($documento['status']) {
                                                case 'solicitado':
                                                    echo 'Solicitado';
                                                    break;
                                                case 'processando':
                                                    echo 'Processando';
                                                    break;
                                                case 'concluido':
                                                    echo 'Concluído';
                                                    break;
                                                case 'cancelado':
                                                    echo 'Cancelado';
                                                    break;
                                                default:
                                                    echo ucfirst($documento['status']);
                                            }
                                        ?>
                                    </span>
                                    <span class="text-xs text-gray-500 ml-2">
                                        <?php echo date('d/m/Y', strtotime($documento['created_at'])); ?>
                                    </span>
                                </div>
                            </div>
                            <a href="documentos.php?action=visualizar&id=<?php echo $documento['id']; ?>" class="text-blue-600 hover:text-blue-800">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Ações Rápidas -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Ações Rápidas</h3>

                <div class="grid grid-cols-2 gap-3">
                    <a href="matriculas.php?action=nova&aluno_id=<?php echo $aluno['id']; ?>" class="flex flex-col items-center justify-center bg-blue-50 hover:bg-blue-100 transition-all p-4 rounded-lg">
                        <div class="bg-blue-100 p-3 rounded-full mb-2">
                            <i class="fas fa-file-alt text-blue-500"></i>
                        </div>
                        <span class="text-sm font-medium">Nova Matrícula</span>
                    </a>

                    <a href="documentos.php?action=solicitar&aluno_id=<?php echo $aluno['id']; ?>" class="flex flex-col items-center justify-center bg-purple-50 hover:bg-purple-100 transition-all p-4 rounded-lg">
                        <div class="bg-purple-100 p-3 rounded-full mb-2">
                            <i class="fas fa-certificate text-purple-500"></i>
                        </div>
                        <span class="text-sm font-medium">Solicitar Documento</span>
                    </a>

                 <!-- Modifique este trecho na seção "Ações Rápidas" do arquivo views/alunos/visualizar.php -->
<a href="javascript:void(0);" onclick="abrirModalNotas(<?php echo $aluno['id']; ?>)" class="flex flex-col items-center justify-center bg-green-50 hover:bg-green-100 transition-all p-4 rounded-lg">
    <div class="bg-green-100 p-3 rounded-full mb-2">
        <i class="fas fa-graduation-cap text-green-500"></i>
    </div>
    <span class="text-sm font-medium">Ver Notas</span>
</a>

                    <a href="financeiro.php?aluno_id=<?php echo $aluno['id']; ?>" class="flex flex-col items-center justify-center bg-yellow-50 hover:bg-yellow-100 transition-all p-4 rounded-lg">
                        <div class="bg-yellow-100 p-3 rounded-full mb-2">
                            <i class="fas fa-dollar-sign text-yellow-500"></i>
                        </div>
                        <span class="text-sm font-medium">Financeiro</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Adicione este código ao final do arquivo views/alunos/visualizar.php -->

<!-- Modal de Visualização de Notas -->
<div id="modal-notas" class="fixed z-10 inset-0 overflow-y-auto hidden">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                        <i class="fas fa-graduation-cap text-blue-600"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-grow">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            Histórico de Notas - <span id="aluno-nome"></span>
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">
                                Visualização completa de todas as notas e frequências do aluno.
                            </p>
                        </div>
                    </div>
                    <button type="button" onclick="fecharModalNotas()" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <div class="mt-5 max-h-96 overflow-y-auto" id="notas-container">
                    <div id="loading-notas" class="py-10 text-center">
                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-blue-500"></div>
                        <p class="mt-2 text-gray-600">Carregando notas...</p>
                    </div>

                    <div id="notas-error" class="py-10 text-center hidden">
                        <div class="inline-block rounded-full h-12 w-12 bg-red-100 flex items-center justify-center">
                            <i class="fas fa-exclamation-triangle text-red-500 text-lg"></i>
                        </div>
                        <p class="mt-2 text-red-600" id="error-message">Erro ao carregar notas</p>
                    </div>

                    <div id="notas-empty" class="py-10 text-center hidden">
                        <div class="inline-block rounded-full h-12 w-12 bg-yellow-100 flex items-center justify-center">
                            <i class="fas fa-info-circle text-yellow-500 text-lg"></i>
                        </div>
                        <p class="mt-2 text-gray-600">Nenhuma nota encontrada para este aluno.</p>
                    </div>

                    <div id="notas-content" class="hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Curso/Turma</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Disciplina</th>
                                        <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Nota</th>
                                        <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Freq. (%)</th>
                                        <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Horas</th>
                                        <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                                        <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Situação</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200" id="notas-tbody">
                                    <!-- As notas serão inseridas aqui via JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" onclick="fecharModalNotas()" class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                    Fechar
                </button>
                <button type="button" onclick="imprimirNotas()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    <i class="fas fa-print mr-2"></i> Imprimir
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Modifique o link "Ver Notas" para abrir o modal
    document.addEventListener('DOMContentLoaded', function() {
        // Encontra todos os links de "Ver Notas"
        const verNotasLinks = document.querySelectorAll('a[href^="notas.php?aluno_id="]');

        verNotasLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const url = new URL(link.href);
                const alunoId = url.searchParams.get('aluno_id');

                if (alunoId) {
                    abrirModalNotas(alunoId);
                }
            });
        });
    });

    // Função para abrir o modal e carregar as notas
    function abrirModalNotas(alunoId) {
        // Exibe o modal
        document.getElementById('modal-notas').classList.remove('hidden');

        // Exibe o loading
        document.getElementById('loading-notas').classList.remove('hidden');
        document.getElementById('notas-error').classList.add('hidden');
        document.getElementById('notas-empty').classList.add('hidden');
        document.getElementById('notas-content').classList.add('hidden');

        // Carrega as notas via AJAX
        fetch(`notas_aluno.php?aluno_id=${alunoId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Preenche o nome do aluno
                    document.getElementById('aluno-nome').textContent = data.aluno.nome;

                    if (data.notas && data.notas.length > 0) {
                        // Preenche a tabela de notas
                        const tbody = document.getElementById('notas-tbody');
                        tbody.innerHTML = '';

                        data.notas.forEach(nota => {
                            const tr = document.createElement('tr');
                            tr.className = 'hover:bg-gray-50';

                            // Formata a nota e frequência
                            const notaFormatada = nota.nota !== null ? parseFloat(nota.nota).toFixed(1).replace('.', ',') : '-';
                            const frequenciaFormatada = nota.frequencia !== null ? parseFloat(nota.frequencia).toFixed(1).replace('.', ',') : '-';
                            const horasFormatadas = nota.horas_aula !== null ? parseFloat(nota.horas_aula).toFixed(1).replace('.', ',') : '-';
                            const dataFormatada = nota.data_lancamento ? new Date(nota.data_lancamento).toLocaleDateString('pt-BR') : '-';

                            // Define a cor da situação
                            let situacaoClass = '';
                            switch(nota.situacao) {
                                case 'aprovado':
                                    situacaoClass = 'bg-green-100 text-green-800';
                                    break;
                                case 'reprovado':
                                    situacaoClass = 'bg-red-100 text-red-800';
                                    break;
                                default:
                                    situacaoClass = 'bg-yellow-100 text-yellow-800';
                            }

                            tr.innerHTML = `
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">${nota.curso_nome || 'Não definido'}</div>
                                    <div class="text-xs text-gray-500">${nota.turma_nome || 'Não definida'}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">${nota.disciplina_nome}</div>
                                    <div class="text-xs text-gray-500">${nota.disciplina_codigo || '-'}</div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-center">
                                    <span class="text-sm font-medium ${parseFloat(nota.nota) >= 6 ? 'text-green-600' : 'text-red-600'}">${notaFormatada}</span>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-center">
                                    <span class="text-sm ${parseFloat(nota.frequencia) >= 75 ? 'text-green-600' : 'text-red-600'}">${frequenciaFormatada}</span>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-center">
                                    <span class="text-sm text-gray-600">${horasFormatadas}</span>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-center">
                                    <span class="text-sm text-gray-600">${dataFormatada}</span>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-center">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${situacaoClass}">
                                        ${nota.situacao.charAt(0).toUpperCase() + nota.situacao.slice(1)}
                                    </span>
                                </td>
                            `;

                            tbody.appendChild(tr);
                        });

                        // Mostra a tabela de notas
                        document.getElementById('notas-content').classList.remove('hidden');
                    } else {
                        // Mostra mensagem de nenhuma nota encontrada
                        document.getElementById('notas-empty').classList.remove('hidden');
                    }
                } else {
                    // Mostra mensagem de erro
                    document.getElementById('error-message').textContent = data.message || 'Erro ao carregar notas';
                    document.getElementById('notas-error').classList.remove('hidden');
                }
            })
            .catch(error => {
                console.error('Erro ao carregar notas:', error);
                document.getElementById('error-message').textContent = 'Erro de conexão ao carregar notas';
                document.getElementById('notas-error').classList.remove('hidden');
            })
            .finally(() => {
                // Esconde o loading
                document.getElementById('loading-notas').classList.add('hidden');
            });
    }

    // Função para fechar o modal
    function fecharModalNotas() {
        document.getElementById('modal-notas').classList.add('hidden');
    }

    // Fecha o modal quando clicar fora dele
    document.getElementById('modal-notas').addEventListener('click', function(e) {
        if (e.target === this) {
            fecharModalNotas();
        }
    });

    // Função para imprimir as notas
    function imprimirNotas() {
        const alunoNome = document.getElementById('aluno-nome').textContent;
        const notasTable = document.getElementById('notas-content').querySelector('table').cloneNode(true);

        // Cria uma nova janela para impressão
        const printWindow = window.open('', '_blank');

        // Define o conteúdo da janela de impressão
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Histórico de Notas - ${alunoNome}</title>
                <meta charset="UTF-8">
                <style>
                    body { font-family: Arial, sans-serif; }
                    h1 { text-align: center; font-size: 20px; margin-bottom: 20px; }
                    table { width: 100%; border-collapse: collapse; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f2f2f2; }
                    tr:nth-child(even) { background-color: #f9f9f9; }
                    .text-center { text-align: center; }
                    .nota-aprovada { color: green; font-weight: bold; }
                    .nota-reprovada { color: red; font-weight: bold; }
                    .situacao-aprovado { background-color: #d1fae5; color: #065f46; padding: 2px 6px; border-radius: 10px; }
                    .situacao-reprovado { background-color: #fee2e2; color: #b91c1c; padding: 2px 6px; border-radius: 10px; }
                    .situacao-cursando { background-color: #fef3c7; color: #92400e; padding: 2px 6px; border-radius: 10px; }
                    @media print {
                        @page { margin: 2cm; }
                        body { margin: 0; }
                        h1 { margin-top: 0; }
                    }
                </style>
            </head>
            <body>
                <h1>Histórico de Notas - ${alunoNome}</h1>
                ${notasTable.outerHTML}
                <div style="text-align: right; margin-top: 20px; font-size: 14px;">
                    <p>Data de Emissão: ${new Date().toLocaleDateString('pt-BR')}</p>
                    <p>Documento para fins informativos</p>
                </div>
            </body>
            </html>
        `);

        // Aplica estilos específicos para as notas e situações na impressão
        const rows = printWindow.document.querySelectorAll('tr');
        rows.forEach(row => {
            const notaCell = row.querySelector('td:nth-child(3)');
            const freqCell = row.querySelector('td:nth-child(4)');
            const situacaoCell = row.querySelector('td:nth-child(7)');

            if (notaCell) {
                const nota = parseFloat(notaCell.textContent.replace(',', '.'));
                if (!isNaN(nota)) {
                    notaCell.className = nota >= 6 ? 'text-center nota-aprovada' : 'text-center nota-reprovada';
                } else {
                    notaCell.className = 'text-center';
                }
            }

            if (freqCell) {
                const freq = parseFloat(freqCell.textContent.replace(',', '.'));
                if (!isNaN(freq)) {
                    freqCell.className = freq >= 75 ? 'text-center nota-aprovada' : 'text-center nota-reprovada';
                } else {
                    freqCell.className = 'text-center';
                }
            }

            if (situacaoCell) {
                const situacao = situacaoCell.textContent.trim().toLowerCase();
                if (situacao === 'aprovado') {
                    situacaoCell.innerHTML = `<span class="situacao-aprovado">Aprovado</span>`;
                } else if (situacao === 'reprovado') {
                    situacaoCell.innerHTML = `<span class="situacao-reprovado">Reprovado</span>`;
                } else {
                    situacaoCell.innerHTML = `<span class="situacao-cursando">Cursando</span>`;
                }
                situacaoCell.className = 'text-center';
            }
        });

        // Fecha o documento e imprime
        printWindow.document.close();
        setTimeout(() => {
            printWindow.print();
        }, 500);
    }
</script>
