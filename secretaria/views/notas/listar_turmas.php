<?php
/**
 * View: listar_turmas.php
 * Lista todas as turmas de um curso
 */
?>
<div class="card mb-6">
    <div class="flex items-center">
        <div class="flex-shrink-0 mr-4">
            <i class="fas fa-graduation-cap text-indigo-500 text-3xl"></i>
        </div>
        <div>
            <h2 class="text-xl font-semibold text-gray-800"><?php echo htmlspecialchars($curso['nome']); ?></h2>
            <div class="flex flex-wrap gap-2 mt-2">
                <?php if (!empty($curso['sigla'])): ?>
                <span class="badge badge-secondary"><?php echo htmlspecialchars($curso['sigla']); ?></span>
                <?php endif; ?>
                <span class="badge badge-primary"><?php echo htmlspecialchars(ucfirst($curso['modalidade'])); ?></span>
                <span class="badge badge-primary"><?php echo htmlspecialchars(str_replace('_', ' ', ucfirst($curso['nivel']))); ?></span>
            </div>
        </div>
    </div>
</div>

<?php if (empty($turmas)): ?>
<div class="card bg-yellow-50 border-l-4 border-yellow-500">
    <div class="flex items-center">
        <div class="flex-shrink-0">
            <i class="fas fa-exclamation-circle text-yellow-500 text-2xl mr-4"></i>
        </div>
        <div>
            <h3 class="text-lg font-medium text-yellow-800">Nenhuma turma encontrada</h3>
            <p class="text-yellow-700 mt-1">Não há turmas ativas disponíveis para este curso.</p>
            <p class="text-yellow-700 mt-2">
                <a href="notas.php" class="text-indigo-600 hover:text-indigo-800 underline">Voltar para a lista de cursos</a>
            </p>
        </div>
    </div>
</div>
<?php else: ?>
<div class="table-responsive">
    <table class="min-w-full bg-white">
        <thead class="bg-gray-100">
            <tr>
                <th class="py-3 px-4 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">Turma</th>
                <th class="py-3 px-4 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">Turno</th>
                <th class="py-3 px-4 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">Período</th>
                <th class="py-3 px-4 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="py-3 px-4 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">Alunos</th>
               <th class="py-3 px-4 text-right text-sm font-medium text-gray-500 uppercase tracking-wider">Ações</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            <?php foreach ($turmas as $turma): ?>
            <tr class="hover:bg-gray-50">
                <td class="py-4 px-4 text-sm font-medium text-gray-900">
                    <?php echo htmlspecialchars($turma['nome']); ?>
                </td>
                <td class="py-4 px-4 text-sm text-gray-500">
                    <?php 
                    $turnos = [
                        'manha' => 'Manhã',
                        'tarde' => 'Tarde',
                        'noite' => 'Noite',
                        'integral' => 'Integral'
                    ];
                    echo isset($turnos[$turma['turno']]) ? $turnos[$turma['turno']] : ucfirst($turma['turno']);
                    ?>
                </td>
                <td class="py-4 px-4 text-sm text-gray-500">
                    <?php
                    $data_inicio = !empty($turma['data_inicio']) ? date('d/m/Y', strtotime($turma['data_inicio'])) : '-';
                    $data_fim = !empty($turma['data_fim']) ? date('d/m/Y', strtotime($turma['data_fim'])) : 'Em andamento';
                    echo $data_inicio . ' a ' . $data_fim;
                    ?>
                </td>
                <td class="py-4 px-4 text-sm text-gray-500">
                    <?php
                    $status_badges = [
                        'planejada' => '<span class="badge badge-warning">Planejada</span>',
                        'em_andamento' => '<span class="badge badge-success">Em andamento</span>',
                        'concluida' => '<span class="badge badge-secondary">Concluída</span>',
                        'cancelada' => '<span class="badge badge-danger">Cancelada</span>'
                    ];
                    echo isset($status_badges[$turma['status']]) ? $status_badges[$turma['status']] : '<span class="badge badge-secondary">' . ucfirst($turma['status']) . '</span>';
                    ?>
                </td>
                <td class="py-4 px-4 text-sm text-gray-500">
                    <span class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-indigo-100 bg-indigo-700 rounded-full">
                        <?php echo intval($turma['total_alunos']); ?>
                    </span>
                </td>
                <td class="py-4 px-4 text-sm text-gray-500 text-right space-x-1">
                    <a href="notas.php?action=listar_disciplinas&curso_id=<?php echo $curso_id; ?>&turma_id=<?php echo $turma['id']; ?>" 
                       class="text-indigo-600 hover:text-indigo-900" title="Lançar Notas">
                        <i class="fas fa-clipboard-list"></i> Lançar Notas
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="mt-6">
    <a href="notas.php" class="btn-secondary">
        <i class="fas fa-arrow-left mr-2"></i> Voltar para Cursos
    </a>
</div>
<?php endif; ?>