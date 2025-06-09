<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="p-6">
        <!-- Cabeçalho com informações principais -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
            <div>
                <h2 class="text-xl font-bold text-gray-800">
                    Matrícula #<?php echo $matricula['id']; ?>
                    <?php if (!empty($matricula['id_legado'])): ?>
                    <span class="text-sm text-gray-500 ml-2">(ID Legado: <?php echo htmlspecialchars($matricula['id_legado']); ?>)</span>
                    <?php endif; ?>
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    Criada em: <?php echo date('d/m/Y H:i', strtotime($matricula['created_at'])); ?>
                    <?php if ($matricula['created_at'] != $matricula['updated_at']): ?>
                    | Atualizada em: <?php echo date('d/m/Y H:i', strtotime($matricula['updated_at'])); ?>
                    <?php endif; ?>
                </p>
            </div>

            <div class="mt-4 md:mt-0">
                <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full
                    <?php
                    switch ($matricula['status']) {
                        case 'ativo':
                            echo 'bg-green-100 text-green-800';
                            break;
                        case 'pendente':
                            echo 'bg-yellow-100 text-yellow-800';
                            break;
                        case 'concluido':
                            echo 'bg-blue-100 text-blue-800';
                            break;
                        case 'cancelado':
                            echo 'bg-red-100 text-red-800';
                            break;
                        case 'trancado':
                            echo 'bg-gray-300 text-gray-800';
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
                        case 'concluido':
                            echo 'Concluído';
                            break;
                        case 'cancelado':
                            echo 'Cancelado';
                            break;
                        case 'trancado':
                            echo 'Trancado';
                            break;
                        default:
                            echo ucfirst($matricula['status']);
                    }
                    ?>
                </span>
            </div>
        </div>

        <!-- Informações do Aluno -->
        <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">Informações do Aluno</h3>
            <div class="bg-gray-50 rounded-lg p-4">
                <div class="flex items-center mb-4">
                    <div class="flex-shrink-0 h-12 w-12">
                        <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center">
                            <span class="text-blue-600 font-bold text-lg"><?php echo isset($matricula['aluno_nome']) ? strtoupper(substr($matricula['aluno_nome'], 0, 1)) : '?'; ?></span>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h4 class="text-lg font-medium text-gray-900">
                            <?php if (isset($matricula['aluno_nome'])): ?>
                            <a href="alunos.php?action=visualizar&id=<?php echo $matricula['aluno_id']; ?>" class="hover:text-blue-600">
                                <?php echo htmlspecialchars($matricula['aluno_nome']); ?>
                            </a>
                            <?php else: ?>
                            <span class="text-gray-500">Aluno não encontrado</span>
                            <?php endif; ?>
                        </h4>
                        <div class="mt-1 flex flex-col sm:flex-row sm:flex-wrap sm:mt-0 sm:space-x-6">
                            <?php if (isset($matricula['aluno_email']) && !empty($matricula['aluno_email'])): ?>
                            <div class="mt-2 flex items-center text-sm text-gray-500 sm:mt-0">
                                <i class="fas fa-envelope mr-1.5 text-gray-400"></i>
                                <?php echo htmlspecialchars($matricula['aluno_email']); ?>
                            </div>
                            <?php endif; ?>

                            <?php if (isset($matricula['aluno_cpf']) && !empty($matricula['aluno_cpf'])): ?>
                            <div class="mt-2 flex items-center text-sm text-gray-500 sm:mt-0">
                                <i class="fas fa-id-card mr-1.5 text-gray-400"></i>
                                CPF: <?php echo htmlspecialchars($matricula['aluno_cpf']); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Dados detalhados do aluno -->
                <div class="border-t border-gray-200 pt-4 mt-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <!-- Documentos -->
                        <div>
                            <h5 class="font-semibold text-gray-700 mb-2">Documentos</h5>
                            <?php if (isset($matricula['aluno_rg']) && !empty($matricula['aluno_rg'])): ?>
                            <p class="text-sm text-gray-600 mb-1">
                                <span class="font-medium">RG:</span> <?php echo htmlspecialchars($matricula['aluno_rg']); ?>
                            </p>
                            <?php endif; ?>

                            <?php if (isset($matricula['aluno_expedidor']) && !empty($matricula['aluno_expedidor'])): ?>
                            <p class="text-sm text-gray-600 mb-1">
                                <span class="font-medium">Órgão Expedidor:</span> <?php echo htmlspecialchars($matricula['aluno_expedidor']); ?>
                            </p>
                            <?php endif; ?>

                            <?php if (isset($matricula['aluno_data_nascimento']) && !empty($matricula['aluno_data_nascimento'])): ?>
                            <p class="text-sm text-gray-600 mb-1">
                                <span class="font-medium">Data de Nascimento:</span> <?php echo date('d/m/Y', strtotime($matricula['aluno_data_nascimento'])); ?>
                            </p>
                            <?php endif; ?>
                        </div>

                        <!-- Contato -->
                        <div>
                            <h5 class="font-semibold text-gray-700 mb-2">Contato</h5>
                            <?php if (isset($matricula['aluno_telefone']) && !empty($matricula['aluno_telefone'])): ?>
                            <p class="text-sm text-gray-600 mb-1">
                                <span class="font-medium">Telefone:</span> <?php echo htmlspecialchars($matricula['aluno_telefone']); ?>
                            </p>
                            <?php endif; ?>

                            <?php if (isset($matricula['aluno_celular']) && !empty($matricula['aluno_celular'])): ?>
                            <p class="text-sm text-gray-600 mb-1">
                                <span class="font-medium">Celular:</span> <?php echo htmlspecialchars($matricula['aluno_celular']); ?>
                            </p>
                            <?php endif; ?>
                        </div>

                        <!-- Endereço -->
                        <div>
                            <h5 class="font-semibold text-gray-700 mb-2">Endereço</h5>
                            <?php if (isset($matricula['aluno_endereco']) && !empty($matricula['aluno_endereco'])): ?>
                            <p class="text-sm text-gray-600 mb-1">
                                <?php echo htmlspecialchars($matricula['aluno_endereco']); ?>
                                <?php if (isset($matricula['aluno_numero']) && !empty($matricula['aluno_numero'])): ?>
                                , <?php echo htmlspecialchars($matricula['aluno_numero']); ?>
                                <?php endif; ?>
                                <?php if (isset($matricula['aluno_complemento']) && !empty($matricula['aluno_complemento'])): ?>
                                - <?php echo htmlspecialchars($matricula['aluno_complemento']); ?>
                                <?php endif; ?>
                            </p>
                            <?php endif; ?>

                            <?php if (isset($matricula['aluno_bairro']) && !empty($matricula['aluno_bairro'])): ?>
                            <p class="text-sm text-gray-600 mb-1">
                                <?php echo htmlspecialchars($matricula['aluno_bairro']); ?>
                            </p>
                            <?php endif; ?>

                            <?php if ((isset($matricula['aluno_cidade']) && !empty($matricula['aluno_cidade'])) ||
                                     (isset($matricula['aluno_estado']) && !empty($matricula['aluno_estado']))): ?>
                            <p class="text-sm text-gray-600 mb-1">
                                <?php if (isset($matricula['aluno_cidade']) && !empty($matricula['aluno_cidade'])): ?>
                                <?php echo htmlspecialchars($matricula['aluno_cidade']); ?>
                                <?php endif; ?>
                                <?php if (isset($matricula['aluno_estado']) && !empty($matricula['aluno_estado'])): ?>
                                - <?php echo htmlspecialchars($matricula['aluno_estado']); ?>
                                <?php endif; ?>
                            </p>
                            <?php endif; ?>

                            <?php if (isset($matricula['aluno_cep']) && !empty($matricula['aluno_cep'])): ?>
                            <p class="text-sm text-gray-600 mb-1">
                                <span class="font-medium">CEP:</span> <?php echo htmlspecialchars($matricula['aluno_cep']); ?>
                            </p>
                            <?php endif; ?>
                        </div>

                        <!-- Datas do Curso -->
                        <div>
                            <h5 class="font-semibold text-gray-700 mb-2">Datas do Curso</h5>
                            <?php if (isset($matricula['aluno_data_ingresso']) && !empty($matricula['aluno_data_ingresso'])): ?>
                            <p class="text-sm text-gray-600 mb-1">
                                <span class="font-medium">Data de Ingresso:</span> <?php echo date('d/m/Y', strtotime($matricula['aluno_data_ingresso'])); ?>
                            </p>
                            <?php endif; ?>

                            <?php if (isset($matricula['aluno_curso_inicio']) && !empty($matricula['aluno_curso_inicio'])): ?>
                            <p class="text-sm text-gray-600 mb-1">
                                <span class="font-medium">Início do Curso:</span> <?php echo date('d/m/Y', strtotime($matricula['aluno_curso_inicio'])); ?>
                            </p>
                            <?php endif; ?>

                            <?php if (isset($matricula['aluno_curso_fim']) && !empty($matricula['aluno_curso_fim'])): ?>
                            <p class="text-sm text-gray-600 mb-1">
                                <span class="font-medium">Fim do Curso:</span> <?php echo date('d/m/Y', strtotime($matricula['aluno_curso_fim'])); ?>
                            </p>
                            <?php else: ?>
                            <p class="text-sm text-gray-600 mb-1">
                                <span class="font-medium">Status do Curso:</span> Em andamento
                            </p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Observações do Aluno -->
                    <?php if (isset($matricula['aluno_observacoes']) && !empty($matricula['aluno_observacoes'])): ?>
                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <h5 class="font-semibold text-gray-700 mb-2">Observações do Aluno</h5>
                        <p class="text-sm text-gray-600 whitespace-pre-line"><?php echo nl2br(htmlspecialchars($matricula['aluno_observacoes'])); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Informações do Curso, Turma e Polo -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Curso -->
            <div>
                <h3 class="text-lg font-semibold text-gray-800 mb-3">Informações do Curso</h3>
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="text-lg font-medium text-gray-900">
                        <?php if (isset($matricula['curso_nome'])): ?>
                        <a href="cursos.php?action=visualizar&id=<?php echo $matricula['curso_id']; ?>" class="hover:text-blue-600">
                            <?php echo htmlspecialchars($matricula['curso_nome']); ?>
                        </a>
                        <?php else: ?>
                        <span class="text-gray-500">Curso não encontrado</span>
                        <?php endif; ?>
                    </h4>
                    <div class="mt-2 flex flex-col space-y-2">
                        <div class="flex items-center text-sm text-gray-500">
                            <i class="fas fa-calendar-alt mr-1.5 text-gray-400"></i>
                            Início:
                            <?php
                            if (isset($matricula['data_inicio']) && !empty($matricula['data_inicio'])) {
                                echo date('d/m/Y', strtotime($matricula['data_inicio']));
                            } else {
                                echo 'Não definido';
                            }
                            ?>
                        </div>
                        <div class="flex items-center text-sm text-gray-500">
                            <i class="fas fa-calendar-check mr-1.5 text-gray-400"></i>
                            Término:
                            <?php
                            if (isset($matricula['data_fim']) && !empty($matricula['data_fim'])) {
                                echo date('d/m/Y', strtotime($matricula['data_fim']));
                            } else {
                                echo 'Não definido';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Turma -->
            <div>
                <h3 class="text-lg font-semibold text-gray-800 mb-3">Informações da Turma</h3>
                <div class="bg-gray-50 rounded-lg p-4">
                    <?php if (isset($matricula['turma_nome']) && !empty($matricula['turma_nome'])): ?>
                    <h4 class="text-lg font-medium text-gray-900">
                        <a href="turmas.php?action=visualizar&id=<?php echo $matricula['turma_id']; ?>" class="hover:text-blue-600">
                            <?php echo htmlspecialchars($matricula['turma_nome']); ?>
                        </a>
                    </h4>
                    <?php else: ?>
                    <p class="text-gray-500">Nenhuma turma associada a esta matrícula.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Polo -->
            <div>
                <h3 class="text-lg font-semibold text-gray-800 mb-3">Informações do Polo</h3>
                <div class="bg-gray-50 rounded-lg p-4">
                    <?php if (isset($matricula['polo_nome']) && !empty($matricula['polo_nome'])): ?>
                    <h4 class="text-lg font-medium text-gray-900">
                        <a href="polos.php?action=visualizar&id=<?php echo $matricula['polo_id']; ?>" class="hover:text-blue-600">
                            <?php echo htmlspecialchars($matricula['polo_nome']); ?>
                        </a>
                    </h4>
                    <?php else: ?>
                    <p class="text-gray-500">Nenhum polo associado a esta matrícula.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Informações Financeiras -->
        <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">Informações Financeiras</h3>
            <div class="bg-gray-50 rounded-lg p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Forma de Pagamento:</p>
                        <p class="font-medium">
                            <?php
                            if (isset($matricula['forma_pagamento']) && !empty($matricula['forma_pagamento'])) {
                                switch ($matricula['forma_pagamento']) {
                                    case 'boleto':
                                        echo 'Boleto';
                                        break;
                                    case 'cartao':
                                        echo 'Cartão de Crédito';
                                        break;
                                    case 'pix':
                                        echo 'PIX';
                                        break;
                                    case 'transferencia':
                                        echo 'Transferência Bancária';
                                        break;
                                    case 'dinheiro':
                                        echo 'Dinheiro';
                                        break;
                                    default:
                                        echo ucfirst($matricula['forma_pagamento']);
                                }
                            } else {
                                echo 'Não definida';
                            }
                            ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Valor Total:</p>
                        <p class="font-medium">
                            R$ <?php echo isset($matricula['valor_total']) ? number_format($matricula['valor_total'], 2, ',', '.') : '0,00'; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notas do Aluno -->
        <?php if (!empty($notas_aluno)): ?>
        <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">
                <i class="fas fa-graduation-cap mr-2"></i>
                Notas e Frequência
            </h3>
            <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                <!-- Desktop Table -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Disciplina
                                </th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Nota
                                </th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Frequência
                                </th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    H. Aula
                                </th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Situação
                                </th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Data Lançamento
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($notas_aluno as $nota): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($nota['disciplina_nome']); ?>
                                        </div>
                                        <?php if (!empty($nota['disciplina_codigo'])): ?>
                                        <div class="text-xs text-gray-500">
                                            Código: <?php echo htmlspecialchars($nota['disciplina_codigo']); ?>
                                        </div>
                                        <?php endif; ?>
                                        <?php if (!empty($nota['disciplina_carga_horaria'])): ?>
                                        <div class="text-xs text-gray-500">
                                            CH: <?php echo $nota['disciplina_carga_horaria']; ?>h
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <?php if ($nota['nota'] !== null): ?>
                                    <span class="text-lg font-semibold <?php echo $nota['nota'] >= 7 ? 'text-green-600' : 'text-red-600'; ?>">
                                        <?php echo number_format($nota['nota'], 1, ',', '.'); ?>
                                    </span>
                                    <?php else: ?>
                                    <span class="text-gray-400">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <?php if ($nota['frequencia'] !== null): ?>
                                    <span class="text-sm font-medium <?php echo $nota['frequencia'] >= 75 ? 'text-green-600' : 'text-red-600'; ?>">
                                        <?php echo number_format($nota['frequencia'], 1, ',', '.'); ?>%
                                    </span>
                                    <?php else: ?>
                                    <span class="text-gray-400">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <?php if ($nota['horas_aula'] !== null): ?>
                                    <span class="text-sm text-gray-900">
                                        <?php echo $nota['horas_aula']; ?>h
                                    </span>
                                    <?php else: ?>
                                    <span class="text-gray-400">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full
                                        <?php
                                        switch ($nota['situacao']) {
                                            case 'aprovado':
                                                echo 'bg-green-100 text-green-800';
                                                break;
                                            case 'reprovado':
                                                echo 'bg-red-100 text-red-800';
                                                break;
                                            case 'cursando':
                                            default:
                                                echo 'bg-blue-100 text-blue-800';
                                        }
                                        ?>">
                                        <?php
                                        switch ($nota['situacao']) {
                                            case 'aprovado':
                                                echo 'Aprovado';
                                                break;
                                            case 'reprovado':
                                                echo 'Reprovado';
                                                break;
                                            case 'cursando':
                                            default:
                                                echo 'Cursando';
                                        }
                                        ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                                    <?php if ($nota['data_lancamento']): ?>
                                    <?php echo date('d/m/Y', strtotime($nota['data_lancamento'])); ?>
                                    <?php else: ?>
                                    <span class="text-gray-400">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php if (!empty($nota['observacoes'])): ?>
                            <tr class="bg-gray-50">
                                <td colspan="6" class="px-6 py-2">
                                    <div class="text-xs text-gray-600">
                                        <strong>Observações:</strong> <?php echo htmlspecialchars($nota['observacoes']); ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Cards -->
                <div class="md:hidden divide-y divide-gray-200">
                    <?php foreach ($notas_aluno as $nota): ?>
                    <div class="p-4">
                        <div class="flex justify-between items-start mb-3">
                            <div class="flex-1">
                                <h4 class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($nota['disciplina_nome']); ?>
                                </h4>
                                <?php if (!empty($nota['disciplina_codigo'])): ?>
                                <p class="text-xs text-gray-500">
                                    Código: <?php echo htmlspecialchars($nota['disciplina_codigo']); ?>
                                </p>
                                <?php endif; ?>
                            </div>
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full
                                <?php
                                switch ($nota['situacao']) {
                                    case 'aprovado':
                                        echo 'bg-green-100 text-green-800';
                                        break;
                                    case 'reprovado':
                                        echo 'bg-red-100 text-red-800';
                                        break;
                                    case 'cursando':
                                    default:
                                        echo 'bg-blue-100 text-blue-800';
                                }
                                ?>">
                                <?php
                                switch ($nota['situacao']) {
                                    case 'aprovado':
                                        echo 'Aprovado';
                                        break;
                                    case 'reprovado':
                                        echo 'Reprovado';
                                        break;
                                    case 'cursando':
                                    default:
                                        echo 'Cursando';
                                }
                                ?>
                            </span>
                        </div>

                        <div class="grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <span class="text-gray-500">Nota:</span>
                                <?php if ($nota['nota'] !== null): ?>
                                <span class="font-semibold <?php echo $nota['nota'] >= 7 ? 'text-green-600' : 'text-red-600'; ?>">
                                    <?php echo number_format($nota['nota'], 1, ',', '.'); ?>
                                </span>
                                <?php else: ?>
                                <span class="text-gray-400">-</span>
                                <?php endif; ?>
                            </div>
                            <div>
                                <span class="text-gray-500">Frequência:</span>
                                <?php if ($nota['frequencia'] !== null): ?>
                                <span class="font-medium <?php echo $nota['frequencia'] >= 75 ? 'text-green-600' : 'text-red-600'; ?>">
                                    <?php echo number_format($nota['frequencia'], 1, ',', '.'); ?>%
                                </span>
                                <?php else: ?>
                                <span class="text-gray-400">-</span>
                                <?php endif; ?>
                            </div>
                            <div>
                                <span class="text-gray-500">H. Aula:</span>
                                <?php if ($nota['horas_aula'] !== null): ?>
                                <span class="text-gray-900"><?php echo $nota['horas_aula']; ?>h</span>
                                <?php else: ?>
                                <span class="text-gray-400">-</span>
                                <?php endif; ?>
                            </div>
                            <div>
                                <span class="text-gray-500">Data:</span>
                                <?php if ($nota['data_lancamento']): ?>
                                <span class="text-gray-900"><?php echo date('d/m/Y', strtotime($nota['data_lancamento'])); ?></span>
                                <?php else: ?>
                                <span class="text-gray-400">-</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if (!empty($nota['observacoes'])): ?>
                        <div class="mt-3 pt-3 border-t border-gray-200">
                            <div class="text-xs text-gray-600">
                                <strong>Observações:</strong> <?php echo htmlspecialchars($nota['observacoes']); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Resumo das Notas -->
                <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
                        <?php
                        $total_disciplinas = count($notas_aluno);
                        $aprovadas = 0;
                        $reprovadas = 0;
                        $cursando = 0;
                        $soma_notas = 0;
                        $count_notas = 0;
                        $soma_frequencia = 0;
                        $count_frequencia = 0;

                        foreach ($notas_aluno as $nota) {
                            switch ($nota['situacao']) {
                                case 'aprovado':
                                    $aprovadas++;
                                    break;
                                case 'reprovado':
                                    $reprovadas++;
                                    break;
                                case 'cursando':
                                default:
                                    $cursando++;
                            }

                            if ($nota['nota'] !== null) {
                                $soma_notas += $nota['nota'];
                                $count_notas++;
                            }

                            if ($nota['frequencia'] !== null) {
                                $soma_frequencia += $nota['frequencia'];
                                $count_frequencia++;
                            }
                        }

                        $media_notas = $count_notas > 0 ? $soma_notas / $count_notas : 0;
                        $media_frequencia = $count_frequencia > 0 ? $soma_frequencia / $count_frequencia : 0;
                        ?>

                        <div>
                            <div class="text-lg font-semibold text-gray-900"><?php echo $total_disciplinas; ?></div>
                            <div class="text-xs text-gray-500">Total Disciplinas</div>
                        </div>
                        <div>
                            <div class="text-lg font-semibold text-green-600"><?php echo $aprovadas; ?></div>
                            <div class="text-xs text-gray-500">Aprovadas</div>
                        </div>
                        <div>
                            <div class="text-lg font-semibold <?php echo $media_notas >= 7 ? 'text-green-600' : 'text-red-600'; ?>">
                                <?php echo $count_notas > 0 ? number_format($media_notas, 1, ',', '.') : '-'; ?>
                            </div>
                            <div class="text-xs text-gray-500">Média Geral</div>
                        </div>
                        <div>
                            <div class="text-lg font-semibold <?php echo $media_frequencia >= 75 ? 'text-green-600' : 'text-red-600'; ?>">
                                <?php echo $count_frequencia > 0 ? number_format($media_frequencia, 1, ',', '.') . '%' : '-'; ?>
                            </div>
                            <div class="text-xs text-gray-500">Freq. Média</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">
                <i class="fas fa-graduation-cap mr-2"></i>
                Notas e Frequência
            </h3>
            <div class="bg-gray-50 rounded-lg p-6 text-center">
                <i class="fas fa-clipboard-list text-gray-400 text-3xl mb-3"></i>
                <p class="text-gray-600">Nenhuma nota foi lançada para este aluno ainda.</p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Observações -->
        <?php if (isset($matricula['observacoes']) && !empty($matricula['observacoes'])): ?>
        <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">Observações</h3>
            <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-gray-700 whitespace-pre-line"><?php echo nl2br(htmlspecialchars($matricula['observacoes'])); ?></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Documentos Emitidos -->
        <?php
        // Buscar documentos já emitidos para este aluno
        try {
            $db = Database::getInstance();
            $documentos_emitidos = $db->fetchAll(
                "SELECT de.id, de.titulo, de.arquivo, de.data_emissao, de.status, de.codigo_verificacao,
                        td.nome as tipo_nome, td.id as tipo_id
                 FROM documentos_emitidos de
                 JOIN tipos_documentos td ON de.tipo_documento_id = td.id
                 WHERE de.aluno_id = ? AND de.status = 'ativo'
                 ORDER BY de.data_emissao DESC",
                [$matricula['aluno_id']]
            ) ?: [];
        } catch (Exception $e) {
            $documentos_emitidos = [];
        }
        ?>

        <?php if (!empty($documentos_emitidos)): ?>
        <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">
                <i class="fas fa-file-alt mr-2"></i>
                Documentos Emitidos
            </h3>
            <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                <div class="divide-y divide-gray-200">
                    <?php foreach ($documentos_emitidos as $doc): ?>
                    <div class="p-4 flex items-center justify-between hover:bg-gray-50">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <?php
                                // Ícone baseado no tipo de documento
                                if (stripos($doc['tipo_nome'], 'declaração') !== false || stripos($doc['tipo_nome'], 'declaracao') !== false): ?>
                                <i class="fas fa-certificate text-blue-500 text-xl"></i>
                                <?php elseif (stripos($doc['tipo_nome'], 'histórico') !== false || stripos($doc['tipo_nome'], 'historico') !== false): ?>
                                <i class="fas fa-scroll text-green-500 text-xl"></i>
                                <?php elseif (stripos($doc['tipo_nome'], 'certificado') !== false): ?>
                                <i class="fas fa-award text-yellow-500 text-xl"></i>
                                <?php else: ?>
                                <i class="fas fa-file-alt text-gray-500 text-xl"></i>
                                <?php endif; ?>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($doc['titulo'] ?: $doc['tipo_nome']); ?>
                                </div>
                                <div class="text-sm text-gray-500">
                                    Emitido em: <?php echo date('d/m/Y H:i', strtotime($doc['data_emissao'])); ?>
                                    <?php if (!empty($doc['codigo_verificacao'])): ?>
                                    <br>Código: <?php echo htmlspecialchars($doc['codigo_verificacao']); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <?php if (!empty($doc['arquivo']) && file_exists($doc['arquivo'])): ?>
                            <a href="<?php echo htmlspecialchars($doc['arquivo']); ?>" target="_blank"
                               class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                <i class="fas fa-download mr-1"></i>
                                Download
                            </a>
                            <?php endif; ?>
                            <?php
                            // Determina qual página usar baseado no tipo de documento
                            $pagina_visualizar = 'documento.php';
                            if (stripos($doc['tipo_nome'], 'declaração') !== false || stripos($doc['tipo_nome'], 'declaracao') !== false) {
                                $pagina_visualizar = 'declaracoes.php';
                            } elseif (stripos($doc['tipo_nome'], 'histórico') !== false || stripos($doc['tipo_nome'], 'historico') !== false) {
                                $pagina_visualizar = 'historicos.php';
                            }
                            ?>
                            <a href="<?php echo $pagina_visualizar; ?>?action=visualizar&aluno_id=<?php echo $matricula['aluno_id']; ?>&doc_id=<?php echo $doc['id']; ?>"
                               target="_blank"
                               class="text-green-600 hover:text-green-800 text-sm font-medium">
                                <i class="fas fa-eye mr-1"></i>
                                Visualizar
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Botões de Ação -->
        <div class="mt-8 space-y-4">
            <!-- Botões de Documentos -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h4 class="text-sm font-semibold text-blue-800 mb-3">
                    <i class="fas fa-file-alt mr-2"></i>
                    Emitir Documentos
                </h4>
                <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3">
                    <a href="declaracoes.php?action=selecionar_aluno&aluno_id=<?php echo $matricula['aluno_id']; ?>&matricula_id=<?php echo $matricula['id']; ?>"
                       class="inline-flex items-center px-4 py-2 border border-blue-300 rounded-md shadow-sm text-sm font-medium text-blue-700 bg-white hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                        <i class="fas fa-certificate mr-2"></i>
                        Emitir Declaração
                    </a>
                    <a href="historicos.php?action=selecionar_aluno&aluno_id=<?php echo $matricula['aluno_id']; ?>&matricula_id=<?php echo $matricula['id']; ?>"
                       class="inline-flex items-center px-4 py-2 border border-green-300 rounded-md shadow-sm text-sm font-medium text-green-700 bg-white hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200">
                        <i class="fas fa-scroll mr-2"></i>
                        Emitir Histórico Escolar
                    </a>
                </div>
            </div>

            <!-- Botões de Navegação -->
            <div class="flex flex-col sm:flex-row sm:justify-end space-y-3 sm:space-y-0 sm:space-x-3">
                <a href="matriculas.php" class="btn-secondary">
                    <i class="fas fa-arrow-left mr-2"></i> Voltar para a Lista
                </a>
                <a href="matriculas.php?action=editar&id=<?php echo $matricula['id']; ?>" class="btn-primary">
                    <i class="fas fa-edit mr-2"></i> Editar Matrícula
                </a>
            </div>
        </div>
    </div>
</div>
