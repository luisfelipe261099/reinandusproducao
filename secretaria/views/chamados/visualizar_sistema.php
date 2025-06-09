<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Chamado #<?php echo $chamado['id']; ?></h1>
        <a href="index.php" class="bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded">
            <i class="fas fa-arrow-left mr-2"></i> Voltar
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Informações do Chamado -->
        <div class="md:col-span-2">
            <div class="bg-white shadow-md rounded-lg p-6 mb-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Informações do Chamado</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Tipo de Documento</p>
                        <p class="text-base font-medium"><?php echo isset($chamado['subtipo']) && !is_null($chamado['subtipo']) ? ucfirst($chamado['subtipo']) : 'N/A'; ?></p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Status</p>
                        <?php
                        $status_class = '';
                        $status_text = '';

                        switch ($chamado['status']) {
                            case 'aberto':
                                $status_class = 'bg-yellow-100 text-yellow-800';
                                $status_text = 'Aberto';
                                break;
                            case 'em_andamento':
                                $status_class = 'bg-blue-100 text-blue-800';
                                $status_text = 'Em Andamento';
                                break;
                            case 'concluido':
                                $status_class = 'bg-green-100 text-green-800';
                                $status_text = 'Concluído';
                                break;
                            case 'cancelado':
                                $status_class = 'bg-red-100 text-red-800';
                                $status_text = 'Cancelado';
                                break;
                            default:
                                $status_class = 'bg-gray-100 text-gray-800';
                                $status_text = isset($chamado['status']) && !is_null($chamado['status']) ? ucfirst($chamado['status']) : 'Desconhecido';
                        }
                        ?>
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                            <?php echo $status_text; ?>
                        </span>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Polo</p>
                        <p class="text-base"><?php echo htmlspecialchars($chamado['polo_nome'] ?? 'N/A'); ?></p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Aberto por</p>
                        <p class="text-base"><?php echo htmlspecialchars($chamado['solicitante_nome'] ?? 'N/A'); ?></p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Data de Abertura</p>
                        <p class="text-base"><?php echo date('d/m/Y H:i', strtotime($chamado['data_abertura'])); ?></p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Última Atualização</p>
                        <p class="text-base"><?php echo date('d/m/Y H:i', strtotime($chamado['data_ultima_atualizacao'])); ?></p>
                    </div>
                </div>

                <?php if (!empty($chamado['descricao'])): ?>
                <div class="mt-4">
                    <p class="text-sm text-gray-500">Descrição</p>
                    <p class="text-base"><?php echo nl2br(htmlspecialchars($chamado['descricao'])); ?></p>
                </div>
                <?php endif; ?>

                <!-- Ações do Chamado -->
                <?php if (usuarioTemPermissao('chamados', 'editar') && $chamado['status'] != 'concluido' && $chamado['status'] != 'cancelado'): ?>
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <h3 class="text-base font-medium text-gray-900 mb-2">Ações</h3>

                    <form action="processar.php" method="POST" class="flex items-center space-x-2">
                        <input type="hidden" name="acao" value="atualizar_status">
                        <input type="hidden" name="chamado_id" value="<?php echo $chamado['id']; ?>">

                        <select name="novo_status" class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                            <?php if ($chamado['status'] == 'aberto'): ?>
                            <option value="em_andamento">Em Andamento</option>
                            <option value="concluido">Concluído</option>
                            <option value="cancelado">Cancelado</option>
                            <?php elseif ($chamado['status'] == 'em_andamento'): ?>
                            <option value="concluido">Concluído</option>
                            <option value="cancelado">Cancelado</option>
                            <?php endif; ?>
                        </select>

                        <input type="text" name="observacao" placeholder="Observação (opcional)" class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">

                        <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded">
                            <i class="fas fa-save mr-2"></i> Atualizar
                        </button>
                    </form>
                </div>
                <?php endif; ?>
            </div>

            <!-- Alunos e Documentos -->
            <div class="bg-white shadow-md rounded-lg p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Alunos e Documentos</h2>

                <?php if (empty($alunos)): ?>
                <div class="text-center text-gray-500 py-4">
                    <p>Nenhum aluno encontrado para este chamado.</p>
                </div>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aluno</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Matrícula</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($alunos as $aluno): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($aluno['aluno_nome']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($aluno['cpf']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo isset($aluno['id']) ? htmlspecialchars($aluno['id']) : 'N/A'; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($aluno['documento_gerado']): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        Gerado em <?php echo date('d/m/Y H:i', strtotime($aluno['data_geracao'])); ?>
                                    </span>
                                    <?php else: ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                        Pendente
                                    </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <?php if ($aluno['documento_gerado']): ?>
                                    <a href="<?php echo htmlspecialchars($aluno['arquivo_path']); ?>" target="_blank" class="text-blue-600 hover:text-blue-900 mr-3">
                                        <i class="fas fa-file-pdf"></i> Ver Documento
                                    </a>
                                    <?php elseif (usuarioTemPermissao('chamados', 'editar') && in_array($chamado['status'], ['aberto', 'em_andamento'])): ?>
                                    <form action="gerar_documento.php" method="POST" class="inline">
                                        <input type="hidden" name="chamado_id" value="<?php echo $chamado['id']; ?>">
                                        <input type="hidden" name="aluno_id" value="<?php echo $aluno['aluno_id']; ?>">
                                        <button type="submit" class="text-green-600 hover:text-green-900">
                                            <i class="fas fa-file-pdf"></i> Gerar Documento
                                        </button>
                                    </form>
                                    <?php else: ?>
                                    <span class="text-gray-400">
                                        <i class="fas fa-file-pdf"></i> Gerar Documento
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
        </div>

        <!-- Histórico do Chamado -->
        <div>
            <div class="bg-white shadow-md rounded-lg p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Histórico do Chamado</h2>

                <?php if (empty($historico)): ?>
                <div class="text-center text-gray-500 py-4">
                    <p>Nenhum histórico encontrado para este chamado.</p>
                </div>
                <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($historico as $item): ?>
                    <div class="border-l-4 border-blue-500 pl-4 py-2">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-sm font-medium text-gray-900">
                                    <?php
                                    $acao_text = '';
                                    switch ($item['acao']) {
                                        case 'abertura':
                                            $acao_text = 'Abertura do chamado';
                                            break;
                                        case 'atualizacao_status':
                                            $acao_text = 'Atualização de status';
                                            break;
                                        case 'geracao_documento':
                                            $acao_text = 'Geração de documento';
                                            break;
                                        default:
                                            $acao_text = isset($item['acao']) && !is_null($item['acao']) ? ucfirst($item['acao']) : 'Ação desconhecida';
                                    }
                                    echo $acao_text;
                                    ?>
                                </p>
                                <p class="text-xs text-gray-500">
                                    por <?php echo htmlspecialchars($item['usuario_nome']); ?>
                                </p>
                            </div>
                            <p class="text-xs text-gray-500">
                                <?php echo date('d/m/Y H:i', strtotime($item['data_hora'])); ?>
                            </p>
                        </div>
                        <p class="text-sm text-gray-700 mt-1">
                            <?php echo nl2br(htmlspecialchars($item['descricao'])); ?>
                        </p>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
