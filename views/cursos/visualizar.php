<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Informações do Curso -->
    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="p-6">
            <div class="flex justify-between items-start">
                <div class="flex items-center">
                    <div class="h-16 w-16 rounded-full bg-blue-100 flex items-center justify-center">
                        <span class="text-blue-600 font-bold text-2xl"><?php echo strtoupper(substr($curso['nome'], 0, 1)); ?></span>
                    </div>
                    <div class="ml-4">
                        <h2 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($curso['nome']); ?></h2>
                        <p class="text-gray-600">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 mr-2">
                                Código: <?php echo isset($curso['codigo']) ? htmlspecialchars($curso['codigo']) : 'N/A'; ?>
                            </span>
                            <?php if (!empty($curso['id_legado'])): ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 mr-2">
                                ID Legado: <?php echo htmlspecialchars($curso['id_legado']); ?>
                            </span>
                            <?php endif; ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo isset($curso['status']) && $curso['status'] === 'ativo' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo isset($curso['status']) && $curso['status'] === 'ativo' ? 'Ativo' : 'Inativo'; ?>
                            </span>
                        </p>
                    </div>
                </div>
                <div class="flex space-x-2">
                    <a href="cursos.php?action=editar&id=<?php echo $curso['id']; ?>" class="btn-secondary py-2">
                        <i class="fas fa-edit mr-2"></i> Editar
                    </a>
                </div>
            </div>

            <div class="mt-6 border-t border-gray-200 pt-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Descrição</h3>
                <div class="prose max-w-none">
                    <?php if (!empty($curso['descricao'])): ?>
                    <p><?php echo nl2br(htmlspecialchars($curso['descricao'])); ?></p>
                    <?php else: ?>
                    <p class="text-gray-500">Nenhuma descrição disponível.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mt-6 border-t border-gray-200 pt-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Informações Acadêmicas</h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Nível</p>
                        <p class="mt-1">
                            <?php
                            $niveis = [
                                'graduacao' => 'Graduação',
                                'pos_graduacao' => 'Pós-Graduação',
                                'mestrado' => 'Mestrado',
                                'doutorado' => 'Doutorado',
                                'tecnico' => 'Técnico',
                                'extensao' => 'Extensão'
                            ];
                            echo isset($curso['nivel']) ? ($niveis[$curso['nivel']] ?? $curso['nivel']) : 'N/A';
                            ?>
                        </p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500">Modalidade</p>
                        <p class="mt-1">
                            <?php
                            $modalidades = [
                                'presencial' => 'Presencial',
                                'ead' => 'EAD',
                                'hibrido' => 'Híbrido'
                            ];
                            echo isset($curso['modalidade']) ? ($modalidades[$curso['modalidade']] ?? $curso['modalidade']) : 'N/A';
                            ?>
                        </p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500">Área de Conhecimento</p>
                        <p class="mt-1">
                            <?php
                            if (isset($curso['area_nome']) && !empty($curso['area_nome'])) {
                                echo htmlspecialchars($curso['area_nome']);
                            } else if (!empty($curso['area_id'])) {
                                echo 'Área ID: ' . $curso['area_id'];
                            } else {
                                echo 'Não definida';
                            }
                            ?>
                        </p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500">Carga Horária</p>
                        <p class="mt-1"><?php echo !empty($curso['carga_horaria']) ? $curso['carga_horaria'] . ' horas' : 'Não definida'; ?></p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500">Duração</p>
                        <p class="mt-1"><?php echo !empty($curso['duracao_meses']) ? $curso['duracao_meses'] . ' meses' : 'Não definida'; ?></p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500">Valor</p>
                        <p class="mt-1"><?php echo !empty($curso['valor']) ? 'R$ ' . number_format($curso['valor'], 2, ',', '.') : 'Não definido'; ?></p>
                    </div>
                </div>
            </div>

            <div class="mt-6 border-t border-gray-200 pt-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Polos Disponíveis</h3>

                <?php if (empty($polos)): ?>
                <p class="text-gray-500">Este curso não está disponível em nenhum polo.</p>
                <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <?php foreach ($polos as $polo): ?>
                    <div class="bg-gray-50 rounded-lg p-3">
                        <p class="font-medium text-gray-800"><?php echo htmlspecialchars($polo['nome']); ?></p>
                        <?php if (!empty($polo['cidade'])): ?>
                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($polo['cidade']); ?><?php echo !empty($polo['estado']) ? ' - ' . htmlspecialchars($polo['estado']) : ''; ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="mt-6 border-t border-gray-200 pt-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Informações do Sistema</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Data de Cadastro</p>
                        <p class="mt-1"><?php echo isset($curso['created_at']) ? date('d/m/Y H:i', strtotime($curso['created_at'])) : 'N/A'; ?></p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500">Última Atualização</p>
                        <p class="mt-1"><?php echo isset($curso['updated_at']) ? date('d/m/Y H:i', strtotime($curso['updated_at'])) : 'N/A'; ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="lg:col-span-1 space-y-6">
        <!-- Turmas -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Turmas</h3>
                    <a href="turmas.php?action=nova&curso_id=<?php echo $curso['id']; ?>" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                        <i class="fas fa-plus mr-1"></i> Nova Turma
                    </a>
                </div>

                <?php if (empty($turmas)): ?>
                <p class="text-gray-500 text-sm">Nenhuma turma encontrada.</p>
                <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($turmas as $turma): ?>
                    <div class="border border-gray-200 rounded-lg p-3">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="font-medium text-gray-800"><?php echo htmlspecialchars($turma['nome']); ?></p>
                                <div class="mt-1 flex items-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium <?php
                                        if (isset($turma['status'])) {
                                            switch ($turma['status']) {
                                                case 'em_andamento':
                                                    echo 'bg-green-100 text-green-800';
                                                    break;
                                                case 'planejada':
                                                    echo 'bg-yellow-100 text-yellow-800';
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
                                                    case 'em_andamento':
                                                        echo 'Em Andamento';
                                                        break;
                                                    case 'planejada':
                                                        echo 'Planejada';
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
                                    <span class="text-xs text-gray-500 ml-2">
                                        <?php
                                        if (isset($turma['data_inicio']) && !empty($turma['data_inicio'])) {
                                            try {
                                                echo date('d/m/Y', strtotime($turma['data_inicio']));
                                            } catch (Exception $e) {
                                                echo 'Data inválida';
                                            }
                                        } else {
                                            echo 'Data não definida';
                                        }
                                        ?>
                                    </span>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">
                                    <i class="fas fa-users mr-1"></i> <?php echo isset($turma['total_alunos']) ? $turma['total_alunos'] : '0'; ?> alunos
                                </p>
                            </div>
                            <a href="turmas.php?action=visualizar&id=<?php echo $turma['id']; ?>" class="text-blue-600 hover:text-blue-800">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Disciplinas -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Disciplinas</h3>
                    <a href="disciplinas.php?action=nova&curso_id=<?php echo $curso['id']; ?>" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                        <i class="fas fa-plus mr-1"></i> Nova Disciplina
                    </a>
                </div>

                <?php if (empty($disciplinas)): ?>
                <p class="text-gray-500 text-sm">Nenhuma disciplina encontrada.</p>
                <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($disciplinas as $disciplina): ?>
                    <div class="border border-gray-200 rounded-lg p-3">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="font-medium text-gray-800"><?php echo htmlspecialchars($disciplina['nome']); ?></p>
                                <p class="text-xs text-gray-500 mt-1">
                                    <i class="fas fa-clock mr-1"></i> <?php echo $disciplina['carga_horaria']; ?> horas
                                </p>
                            </div>
                            <a href="disciplinas.php?action=visualizar&id=<?php echo $disciplina['id']; ?>" class="text-blue-600 hover:text-blue-800">
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
                    <a href="turmas.php?action=nova&curso_id=<?php echo $curso['id']; ?>" class="flex flex-col items-center justify-center bg-blue-50 hover:bg-blue-100 transition-all p-4 rounded-lg">
                        <div class="bg-blue-100 p-3 rounded-full mb-2">
                            <i class="fas fa-users text-blue-500"></i>
                        </div>
                        <span class="text-sm font-medium">Nova Turma</span>
                    </a>

                    <a href="disciplinas.php?action=nova&curso_id=<?php echo $curso['id']; ?>" class="flex flex-col items-center justify-center bg-purple-50 hover:bg-purple-100 transition-all p-4 rounded-lg">
                        <div class="bg-purple-100 p-3 rounded-full mb-2">
                            <i class="fas fa-book text-purple-500"></i>
                        </div>
                        <span class="text-sm font-medium">Nova Disciplina</span>
                    </a>

                    <a href="relatorios.php?tipo=curso&id=<?php echo $curso['id']; ?>" class="flex flex-col items-center justify-center bg-green-50 hover:bg-green-100 transition-all p-4 rounded-lg">
                        <div class="bg-green-100 p-3 rounded-full mb-2">
                            <i class="fas fa-chart-bar text-green-500"></i>
                        </div>
                        <span class="text-sm font-medium">Relatórios</span>
                    </a>

                    <a href="matriculas.php?curso_id=<?php echo $curso['id']; ?>" class="flex flex-col items-center justify-center bg-yellow-50 hover:bg-yellow-100 transition-all p-4 rounded-lg">
                        <div class="bg-yellow-100 p-3 rounded-full mb-2">
                            <i class="fas fa-file-alt text-yellow-500"></i>
                        </div>
                        <span class="text-sm font-medium">Matrículas</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
