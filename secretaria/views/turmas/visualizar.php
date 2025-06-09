<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Informações da Turma -->
    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="p-6">
            <div class="flex justify-between items-start">
                <div class="flex items-center">
                    <div class="h-16 w-16 rounded-full bg-blue-100 flex items-center justify-center">
                        <span class="text-blue-600 font-bold text-2xl"><?php echo strtoupper(substr($turma['nome'], 0, 1)); ?></span>
                    </div>
                    <div class="ml-4">
                        <h2 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($turma['nome']); ?></h2>
                        <p class="text-gray-600">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 mr-2">
                                Código: <?php echo isset($turma['codigo']) ? htmlspecialchars($turma['codigo']) : 'N/A'; ?>
                            </span>
                            <?php if (!empty($turma['id_legado'])): ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 mr-2">
                                ID Legado: <?php echo htmlspecialchars($turma['id_legado']); ?>
                            </span>
                            <?php endif; ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                <?php
                                if (isset($turma['status'])) {
                                    switch ($turma['status']) {
                                        case 'planejada':
                                            echo 'bg-yellow-100 text-yellow-800';
                                            break;
                                        case 'em_andamento':
                                            echo 'bg-green-100 text-green-800';
                                            break;
                                        case 'concluida':
                                            echo 'bg-blue-100 text-blue-800';
                                            break;
                                        case 'cancelada':
                                            echo 'bg-red-100 text-red-800';
                                            break;
                                        default:
                                            echo 'bg-gray-100 text-gray-800';
                                    }
                                } else {
                                    echo 'bg-gray-100 text-gray-800';
                                }
                                ?>">
                                <?php
                                if (isset($turma['status'])) {
                                    switch ($turma['status']) {
                                        case 'planejada':
                                            echo 'Planejada';
                                            break;
                                        case 'em_andamento':
                                            echo 'Em Andamento';
                                            break;
                                        case 'concluida':
                                            echo 'Concluída';
                                            break;
                                        case 'cancelada':
                                            echo 'Cancelada';
                                            break;
                                        default:
                                            echo ucfirst($turma['status']);
                                    }
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="mt-6 border-t border-gray-200 pt-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Informações do Curso</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Curso</p>
                        <p class="mt-1">
                            <?php if (isset($turma['curso_nome']) && !empty($turma['curso_nome'])): ?>
                            <a href="cursos.php?action=visualizar&id=<?php echo $turma['curso_id']; ?>" class="text-blue-600 hover:text-blue-800">
                                <?php echo htmlspecialchars($turma['curso_nome']); ?>
                            </a>
                            <?php else: ?>
                            Não definido
                            <?php endif; ?>
                        </p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500">Polo</p>
                        <p class="mt-1">
                            <?php if (isset($turma['polo_nome']) && !empty($turma['polo_nome'])): ?>
                            <a href="polos.php?action=visualizar&id=<?php echo $turma['polo_id']; ?>" class="text-blue-600 hover:text-blue-800">
                                <?php echo htmlspecialchars($turma['polo_nome']); ?>
                            </a>
                            <?php else: ?>
                            Não definido
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>

            <div class="mt-6 border-t border-gray-200 pt-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Período e Horários</h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Data de Início</p>
                        <p class="mt-1">
                            <?php
                            if (isset($turma['data_inicio']) && !empty($turma['data_inicio'])) {
                                try {
                                    echo date('d/m/Y', strtotime($turma['data_inicio']));
                                } catch (Exception $e) {
                                    echo 'Data inválida';
                                }
                            } else {
                                echo 'Não definida';
                            }
                            ?>
                        </p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500">Data de Término</p>
                        <p class="mt-1">
                            <?php
                            if (isset($turma['data_fim']) && !empty($turma['data_fim'])) {
                                try {
                                    echo date('d/m/Y', strtotime($turma['data_fim']));
                                } catch (Exception $e) {
                                    echo 'Data inválida';
                                }
                            } else {
                                echo 'Não definida';
                            }
                            ?>
                        </p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500">Horário</p>
                        <p class="mt-1"><?php echo isset($turma['horario']) && !empty($turma['horario']) ? htmlspecialchars($turma['horario']) : 'Não definido'; ?></p>
                    </div>
                </div>
            </div>

            <div class="mt-6 border-t border-gray-200 pt-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Informações Adicionais</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Professor Responsável</p>
                        <p class="mt-1">
                            <?php if (isset($turma['professor_nome']) && !empty($turma['professor_nome'])): ?>
                            <a href="professores.php?action=visualizar&id=<?php echo $turma['professor_id']; ?>" class="text-blue-600 hover:text-blue-800">
                                <?php echo htmlspecialchars($turma['professor_nome']); ?>
                            </a>
                            <?php else: ?>
                            Não definido
                            <?php endif; ?>
                        </p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500">Vagas</p>
                        <p class="mt-1">
                            <?php
                            $vagas = isset($turma['vagas']) ? (int)$turma['vagas'] : 0;
                            $ocupadas = isset($alunos) ? count($alunos) : 0;
                            $disponiveis = max(0, $vagas - $ocupadas);

                            echo "{$ocupadas} / {$vagas} (";

                            if ($vagas > 0) {
                                $percentual = round(($ocupadas / $vagas) * 100);
                                echo "{$percentual}% ocupadas, {$disponiveis} disponíveis";
                            } else {
                                echo "0% ocupadas";
                            }

                            echo ")";
                            ?>
                        </p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500">Carga Horária</p>
                        <p class="mt-1">
                            <?php
                            if (isset($turma['carga_horaria']) && !empty($turma['carga_horaria'])) {
                                echo $turma['carga_horaria'] . ' horas';
                            } else {
                                echo 'Não definida';
                            }
                            ?>
                        </p>
                    </div>
                </div>

                <?php if (isset($turma['observacoes']) && !empty($turma['observacoes'])): ?>
                <div class="mt-4">
                    <p class="text-sm font-medium text-gray-500">Observações</p>
                    <div class="mt-1 p-3 bg-gray-50 rounded-md">
                        <?php echo nl2br(htmlspecialchars($turma['observacoes'])); ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="mt-6 border-t border-gray-200 pt-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Informações do Sistema</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Data de Cadastro</p>
                        <p class="mt-1">
                            <?php
                            if (isset($turma['created_at']) && !empty($turma['created_at'])) {
                                try {
                                    echo date('d/m/Y H:i', strtotime($turma['created_at']));
                                } catch (Exception $e) {
                                    echo 'Data inválida';
                                }
                            } else {
                                echo 'Não disponível';
                            }
                            ?>
                        </p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500">Última Atualização</p>
                        <p class="mt-1">
                            <?php
                            if (isset($turma['updated_at']) && !empty($turma['updated_at'])) {
                                try {
                                    echo date('d/m/Y H:i', strtotime($turma['updated_at']));
                                } catch (Exception $e) {
                                    echo 'Data inválida';
                                }
                            } else {
                                echo 'Não disponível';
                            }
                            ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="lg:col-span-1 space-y-6">
        <!-- Alunos Matriculados -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Alunos Matriculados</h3>
                    <a href="matriculas.php?action=nova&turma_id=<?php echo $turma['id']; ?>" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                        <i class="fas fa-plus mr-1"></i> Nova Matrícula
                    </a>
                </div>

                <?php if (empty($alunos)): ?>
                <p class="text-gray-500 text-sm">Nenhum aluno matriculado nesta turma.</p>
                <?php else: ?>
                <div class="space-y-3 max-h-96 overflow-y-auto">
                    <?php foreach ($alunos as $aluno): ?>
                    <div class="border border-gray-200 rounded-lg p-3">
                        <div class="flex justify-between items-start">
                            <div>
                                <a href="alunos.php?action=visualizar&id=<?php echo $aluno['aluno_id']; ?>" class="font-medium text-gray-800 hover:text-blue-600">
                                    <?php echo htmlspecialchars($aluno['aluno_nome']); ?>
                                </a>
                                <div class="mt-1 text-xs text-gray-500">
                                    <?php echo isset($aluno['aluno_email']) ? htmlspecialchars($aluno['aluno_email']) : ''; ?>
                                    <?php if (isset($aluno['aluno_cpf']) && !empty($aluno['aluno_cpf'])): ?>
                                    <span class="mx-1">|</span> CPF: <?php echo htmlspecialchars($aluno['aluno_cpf']); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="mt-1 flex items-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                        <?php
                                        if (isset($aluno['matricula_status'])) {
                                            switch ($aluno['matricula_status']) {
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
                                                case 'trancado':
                                                    echo 'bg-gray-100 text-gray-800';
                                                    break;
                                                default:
                                                    echo 'bg-gray-100 text-gray-800';
                                            }
                                        } else {
                                            echo 'bg-gray-100 text-gray-800';
                                        }
                                        ?>">
                                        <?php
                                        if (isset($aluno['matricula_status'])) {
                                            switch ($aluno['matricula_status']) {
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
                                                case 'trancado':
                                                    echo 'Trancado';
                                                    break;
                                                default:
                                                    echo ucfirst($aluno['matricula_status']);
                                            }
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </span>
                                    <?php if (isset($aluno['data_matricula']) && !empty($aluno['data_matricula'])): ?>
                                    <span class="text-xs text-gray-500 ml-2">
                                        Matrícula: <?php echo date('d/m/Y', strtotime($aluno['data_matricula'])); ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="flex space-x-1">
                                <a href="matriculas.php?action=visualizar&id=<?php echo $aluno['matricula_id']; ?>" class="text-blue-600 hover:text-blue-800" title="Visualizar Matrícula">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="matriculas.php?action=editar&id=<?php echo $aluno['matricula_id']; ?>" class="text-indigo-600 hover:text-indigo-800" title="Editar Matrícula">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php if (count($alunos) > 5): ?>
                <div class="mt-4 text-center">
                    <a href="matriculas.php?turma_id=<?php echo $turma['id']; ?>" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                        Ver Todas as Matrículas <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Disciplinas do Curso -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Disciplinas do Curso</h3>
                    <?php if (isset($turma['curso_id']) && !empty($turma['curso_id'])): ?>
                    <a href="disciplinas.php?curso_id=<?php echo $turma['curso_id']; ?>" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                        <i class="fas fa-list mr-1"></i> Ver Todas
                    </a>
                    <?php endif; ?>
                </div>

                <?php if (empty($disciplinas)): ?>
                <p class="text-gray-500 text-sm">Nenhuma disciplina cadastrada para este curso.</p>
                <?php else: ?>
                <div class="space-y-3 max-h-96 overflow-y-auto">
                    <?php foreach ($disciplinas as $disciplina): ?>
                    <div class="border border-gray-200 rounded-lg p-3">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="font-medium text-gray-800"><?php echo htmlspecialchars($disciplina['nome']); ?></p>
                                <?php if (isset($disciplina['carga_horaria']) && !empty($disciplina['carga_horaria'])): ?>
                                <p class="text-xs text-gray-500 mt-1">
                                    <i class="fas fa-clock mr-1"></i> <?php echo $disciplina['carga_horaria']; ?> horas
                                </p>
                                <?php endif; ?>
                                <?php if (isset($disciplina['professor_nome']) && !empty($disciplina['professor_nome'])): ?>
                                <p class="text-xs text-gray-500 mt-1">
                                    <i class="fas fa-user mr-1"></i> Prof.: <?php echo htmlspecialchars($disciplina['professor_nome']); ?>
                                </p>
                                <?php endif; ?>
                            </div>
                            <?php if (isset($disciplina['id'])): ?>
                            <a href="disciplinas.php?action=visualizar&id=<?php echo $disciplina['id']; ?>" class="text-blue-600 hover:text-blue-800">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                            <?php endif; ?>
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
                    <a href="matriculas.php?action=nova&turma_id=<?php echo $turma['id']; ?>" class="flex flex-col items-center justify-center bg-blue-50 hover:bg-blue-100 transition-all p-4 rounded-lg">
                        <div class="bg-blue-100 p-3 rounded-full mb-2">
                            <i class="fas fa-user-plus text-blue-500"></i>
                        </div>
                        <span class="text-sm font-medium">Nova Matrícula</span>
                    </a>

                    <a href="turmas.php?action=editar&id=<?php echo $turma['id']; ?>" class="flex flex-col items-center justify-center bg-green-50 hover:bg-green-100 transition-all p-4 rounded-lg">
                        <div class="bg-green-100 p-3 rounded-full mb-2">
                            <i class="fas fa-edit text-green-500"></i>
                        </div>
                        <span class="text-sm font-medium">Editar Turma</span>
                    </a>

                    <a href="relatorios.php?tipo=turma&id=<?php echo $turma['id']; ?>" class="flex flex-col items-center justify-center bg-purple-50 hover:bg-purple-100 transition-all p-4 rounded-lg">
                        <div class="bg-purple-100 p-3 rounded-full mb-2">
                            <i class="fas fa-chart-bar text-purple-500"></i>
                        </div>
                        <span class="text-sm font-medium">Relatórios</span>
                    </a>

                    <a href="documentos.php?turma_id=<?php echo $turma['id']; ?>" class="flex flex-col items-center justify-center bg-yellow-50 hover:bg-yellow-100 transition-all p-4 rounded-lg">
                        <div class="bg-yellow-100 p-3 rounded-full mb-2">
                            <i class="fas fa-file-alt text-yellow-500"></i>
                        </div>
                        <span class="text-sm font-medium">Documentos</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
