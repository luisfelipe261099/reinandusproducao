<div class="bg-white rounded-xl shadow-sm overflow-hidden print:shadow-none">
    <?php if (empty($matriculas)): ?>
    <div class="p-6 text-center">
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700">
                        Este aluno não possui matrículas ativas.
                    </p>
                </div>
            </div>
        </div>
        <a href="notas.php?action=boletim" class="btn-secondary print:hidden">
            <i class="fas fa-arrow-left mr-2"></i> Voltar
        </a>
    </div>
    <?php else: ?>
    <!-- Cabeçalho do Boletim -->
    <div class="p-6 border-b border-gray-200">
        <div class="flex flex-col md:flex-row md:justify-between md:items-center">
            <div>
                <h2 class="text-xl font-bold text-gray-800">Boletim Escolar</h2>
                <p class="text-sm text-gray-600">Ano Letivo: <?php echo date('Y'); ?></p>
            </div>
            <div class="mt-4 md:mt-0">
                <img src="img/logo.png" alt="Logo da Instituição" class="h-12">
            </div>
        </div>
    </div>

    <!-- Informações do Aluno -->
    <div class="p-6 border-b border-gray-200 bg-gray-50">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <h3 class="text-sm font-medium text-gray-500">Aluno</h3>
                <p class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($aluno['nome']); ?></p>
                <?php if (!empty($aluno['cpf'])): ?>
                <p class="text-sm text-gray-600">CPF: <?php echo htmlspecialchars($aluno['cpf']); ?></p>
                <?php endif; ?>
            </div>

            <!-- Seletor de Matrícula (apenas na visualização, não na impressão) -->
            <div class="print:hidden">
                <h3 class="text-sm font-medium text-gray-500">Matrícula</h3>
                <div class="mt-1">
                    <select id="matricula_id" class="form-select w-full" onchange="window.location.href = 'notas.php?action=boletim&aluno_id=<?php echo $aluno_id; ?>&matricula_id=' + this.value">
                        <?php foreach ($matriculas as $matricula): ?>
                        <option value="<?php echo $matricula['id']; ?>" <?php echo $matricula_id == $matricula['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($matricula['curso_nome']); ?> - <?php echo htmlspecialchars($matricula['turma_nome']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Informações da Matrícula (na impressão) -->
            <div class="hidden print:block">
                <h3 class="text-sm font-medium text-gray-500">Matrícula</h3>
                <p class="text-lg font-semibold text-gray-800">
                    <?php echo htmlspecialchars($matricula_atual['curso_nome']); ?> - <?php echo htmlspecialchars($matricula_atual['turma_nome']); ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Estatísticas do Boletim -->
    <div class="p-6 border-b border-gray-200">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-blue-50 rounded-lg p-3 text-center">
                <h4 class="text-sm font-medium text-blue-700">Média Geral</h4>
                <p class="text-2xl font-bold text-blue-800"><?php echo $media_geral > 0 ? number_format($media_geral, 1, ',', '.') : '-'; ?></p>
            </div>

            <div class="bg-green-50 rounded-lg p-3 text-center">
                <h4 class="text-sm font-medium text-green-700">Aprovadas</h4>
                <p class="text-2xl font-bold text-green-800"><?php echo $total_aprovadas; ?></p>
            </div>

            <div class="bg-red-50 rounded-lg p-3 text-center">
                <h4 class="text-sm font-medium text-red-700">Reprovadas</h4>
                <p class="text-2xl font-bold text-red-800"><?php echo $total_reprovadas; ?></p>
            </div>

            <div class="bg-yellow-50 rounded-lg p-3 text-center">
                <h4 class="text-sm font-medium text-yellow-700">Em Andamento</h4>
                <p class="text-2xl font-bold text-yellow-800"><?php echo $total_em_andamento; ?></p>
            </div>
        </div>
    </div>

    <!-- Tabela de Notas -->
    <div class="p-6">
        <?php if (empty($notas)): ?>
        <div class="text-center text-gray-500 py-6">
            <p>Não há notas registradas para este aluno nesta matrícula.</p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Disciplina</th>
                        <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Nota</th>
                        <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Freq. (%)</th>
                        <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Horas Aula</th>
                        <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Data Lançamento</th>
                        <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Situação</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($notas as $nota): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($nota['disciplina_nome']); ?>
                                <?php if (!empty($nota['disciplina_codigo'])): ?>
                                <span class="text-xs text-gray-500">(<?php echo htmlspecialchars($nota['disciplina_codigo']); ?>)</span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($nota['professor_nome'])): ?>
                            <div class="text-xs text-gray-500">Prof.: <?php echo htmlspecialchars($nota['professor_nome']); ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="px-3 py-4 whitespace-nowrap text-center font-medium">
                            <div class="text-sm <?php echo !empty($nota['nota']) && $nota['nota'] >= 6 ? 'text-green-600' : (!empty($nota['nota']) ? 'text-red-600' : 'text-gray-500'); ?>">
                                <?php echo !empty($nota['nota']) ? number_format($nota['nota'], 1, ',', '.') : '-'; ?>
                            </div>
                        </td>
                        <td class="px-3 py-4 whitespace-nowrap text-center">
                            <div class="text-sm <?php echo !empty($nota['frequencia']) && $nota['frequencia'] >= 75 ? 'text-green-600' : (!empty($nota['frequencia']) ? 'text-red-600' : 'text-gray-500'); ?>">
                                <?php echo !empty($nota['frequencia']) ? $nota['frequencia'] . '%' : '-'; ?>
                            </div>
                        </td>
                        <td class="px-3 py-4 whitespace-nowrap text-center">
                            <div class="text-sm text-gray-900">
                                <?php echo !empty($nota['horas_aula']) ? $nota['horas_aula'] : '-'; ?>
                            </div>
                        </td>
                        <td class="px-3 py-4 whitespace-nowrap text-center">
                            <div class="text-sm text-gray-900">
                                <?php echo !empty($nota['data_lancamento']) ? date('d/m/Y', strtotime($nota['data_lancamento'])) : '-'; ?>
                            </div>
                        </td>
                        <td class="px-3 py-4 whitespace-nowrap text-center">
                            <?php if (!empty($nota['situacao'])): ?>
                                <?php if ($nota['situacao'] === 'cursando'): ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    Cursando
                                </span>
                                <?php else: ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                    <?php echo $nota['situacao'] === 'aprovado' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                    <?php echo $nota['situacao'] === 'aprovado' ? 'Aprovado' : 'Reprovado'; ?>
                                </span>
                                <?php endif; ?>
                            <?php else: ?>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                Cursando
                            </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Observações e Assinaturas (apenas na impressão) -->
    <div class="hidden print:block p-6 border-t border-gray-200">
        <div class="mt-8">
            <h3 class="text-sm font-medium text-gray-700 mb-2">Observações:</h3>
            <div class="border-b border-gray-300 h-16"></div>
        </div>

        <div class="mt-12 grid grid-cols-3 gap-8">
            <div class="text-center">
                <div class="border-t border-gray-300 pt-2">
                    <p class="text-sm text-gray-700">Coordenador(a)</p>
                </div>
            </div>

            <div class="text-center">
                <div class="border-t border-gray-300 pt-2">
                    <p class="text-sm text-gray-700">Secretário(a)</p>
                </div>
            </div>

            <div class="text-center">
                <div class="border-t border-gray-300 pt-2">
                    <p class="text-sm text-gray-700">Diretor(a)</p>
                </div>
            </div>
        </div>

        <div class="mt-8 text-center text-xs text-gray-500">
            <p>Documento emitido em <?php echo date('d/m/Y H:i:s'); ?></p>
        </div>
    </div>

    <!-- Botões de Ação (apenas na visualização, não na impressão) -->
    <div class="p-6 border-t border-gray-200 print:hidden">
        <div class="flex justify-end space-x-2">
            <a href="notas.php?action=boletim" class="btn-secondary">
                <i class="fas fa-arrow-left mr-2"></i> Voltar
            </a>
            <button onclick="window.print()" class="btn-primary">
                <i class="fas fa-print mr-2"></i> Imprimir Boletim
            </button>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
    @media print {
        body {
            font-size: 12pt;
            color: #000;
            background-color: #fff;
        }

        .print\:hidden {
            display: none !important;
        }

        .print\:block {
            display: block !important;
        }

        .print\:shadow-none {
            box-shadow: none !important;
        }

        @page {
            size: A4;
            margin: 1cm;
        }
    }
</style>
