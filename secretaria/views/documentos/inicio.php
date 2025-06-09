<?php
/**
 * View para a página inicial de documentos
 */
?>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Card para Emitir Declaração de Matrícula -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden hover:shadow-md transition-shadow duration-300">
        <div class="p-6 text-center">
            <div class="flex items-center justify-center w-16 h-16 mx-auto bg-blue-100 text-blue-500 rounded-full mb-4">
                <i class="fas fa-file-alt text-2xl"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Declaração de Matrícula</h3>
            <p class="text-gray-600 mb-4">Emita declarações para alunos matriculados</p>
            <a href="documentos.php?action=selecionar_aluno" class="inline-block bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded transition-colors duration-300">
                <i class="fas fa-plus mr-2"></i> Emitir
            </a>
        </div>
    </div>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <!-- Cartões existentes -->
    
    <!-- Novo cartão para solicitações de documentos -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden card">
        <div class="px-6 py-4 bg-blue-600 text-white">
            <div class="flex items-center">
                <i class="fas fa-inbox text-3xl mr-4"></i>
                <h3 class="text-lg font-bold">Solicitações de Documentos</h3>
            </div>
        </div>
        <div class="px-6 py-4">
            <p class="text-gray-700 mb-4">Visualize e responda às solicitações de documentos enviadas pelos polos.</p>
            <a href="../chamados/index.php?view=solicitacoes" class="block text-center bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition-colors duration-200">
                Acessar Solicitações
            </a>
        </div>
    </div>
</div>
    <!-- Card para Emitir Histórico Escolar -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden hover:shadow-md transition-shadow duration-300">
        <div class="p-6 text-center">
            <div class="flex items-center justify-center w-16 h-16 mx-auto bg-green-100 text-green-500 rounded-full mb-4">
                <i class="fas fa-graduation-cap text-2xl"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Histórico Escolar</h3>
            <p class="text-gray-600 mb-4">Gere históricos com notas e frequências</p>
            <a href="documentos.php?action=selecionar_aluno" class="inline-block bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded transition-colors duration-300">
                <i class="fas fa-plus mr-2"></i> Emitir
            </a>
        </div>
    </div>

    <!-- Card para Consultar Documentos -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden hover:shadow-md transition-shadow duration-300">
        <div class="p-6 text-center">
            <div class="flex items-center justify-center w-16 h-16 mx-auto bg-purple-100 text-purple-500 rounded-full mb-4">
                <i class="fas fa-search text-2xl"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Consultar Documentos</h3>
            <p class="text-gray-600 mb-4">Consulte documentos já emitidos</p>
            <a href="documentos.php?action=listar" class="inline-block bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded transition-colors duration-300">
                <i class="fas fa-list mr-2"></i> Consultar
            </a>
        </div>
    </div>

    <!-- Card para Configurações -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden hover:shadow-md transition-shadow duration-300">
        <div class="p-6 text-center">
            <div class="flex items-center justify-center w-16 h-16 mx-auto bg-gray-100 text-gray-500 rounded-full mb-4">
                <i class="fas fa-cog text-2xl"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Configurações</h3>
            <p class="text-gray-600 mb-4">Configure tipos de documentos</p>
            <a href="documentos.php?action=configuracoes" class="inline-block bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded transition-colors duration-300">
                <i class="fas fa-cog mr-2"></i> Configurar
            </a>
        </div>
    </div>
</div>

<!-- Estatísticas de Documentos -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <?php
    // Total de documentos
    $sql = "SELECT COUNT(*) as total FROM documentos_emitidos";
    $resultado = executarConsulta($db, $sql);
    $total_documentos = $resultado['total'] ?? 0;

    // Documentos por tipo
    $sql = "SELECT COUNT(*) as total FROM documentos_emitidos WHERE tipo_documento_id = 1";
    $resultado = executarConsulta($db, $sql);
    $total_declaracoes = $resultado['total'] ?? 0;

    $sql = "SELECT COUNT(*) as total FROM documentos_emitidos WHERE tipo_documento_id = 2";
    $resultado = executarConsulta($db, $sql);
    $total_historicos = $resultado['total'] ?? 0;

    // Documentos emitidos hoje
    $sql = "SELECT COUNT(*) as total FROM documentos_emitidos WHERE DATE(data_emissao) = CURDATE()";
    $resultado = executarConsulta($db, $sql);
    $emitidos_hoje = $resultado['total'] ?? 0;
    ?>

    <!-- Total de Documentos -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0 bg-blue-100 rounded-full p-3">
                <i class="fas fa-file-alt text-blue-500"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">Total de Documentos</p>
                <p class="text-2xl font-semibold text-gray-800"><?php echo $total_documentos; ?></p>
            </div>
        </div>
    </div>

    <!-- Declarações Emitidas -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0 bg-green-100 rounded-full p-3">
                <i class="fas fa-file-signature text-green-500"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">Declarações</p>
                <p class="text-2xl font-semibold text-gray-800"><?php echo $total_declaracoes; ?></p>
            </div>
        </div>
    </div>

    <!-- Históricos Emitidos -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0 bg-purple-100 rounded-full p-3">
                <i class="fas fa-graduation-cap text-purple-500"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">Históricos</p>
                <p class="text-2xl font-semibold text-gray-800"><?php echo $total_historicos; ?></p>
            </div>
        </div>
    </div>

    <!-- Emitidos Hoje -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0 bg-yellow-100 rounded-full p-3">
                <i class="fas fa-calendar-day text-yellow-500"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">Emitidos Hoje</p>
                <p class="text-2xl font-semibold text-gray-800"><?php echo $emitidos_hoje; ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Documentos recentes -->
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="p-6 border-b border-gray-200">
        <h3 class="text-xl font-semibold text-gray-800">Documentos Recentes</h3>
    </div>
    <div class="overflow-x-auto">
        <?php
        // Busca os documentos recentes
        $sql = "SELECT d.id, d.titulo, d.numero, d.data_emissao, a.nome as aluno_nome, td.nome as tipo_documento
                FROM documentos_emitidos d
                LEFT JOIN alunos a ON d.aluno_id = a.id
                LEFT JOIN tipos_documentos td ON d.tipo_documento_id = td.id
                ORDER BY d.data_emissao DESC
                LIMIT 10";
        $documentos_recentes = executarConsultaAll($db, $sql);

        if (count($documentos_recentes) > 0):
        ?>
        <table class="min-w-full bg-white">
            <thead>
                <tr class="bg-gray-100">
                    <th class="py-3 px-4 text-left font-medium text-gray-600 uppercase tracking-wider border-b">Número</th>
                    <th class="py-3 px-4 text-left font-medium text-gray-600 uppercase tracking-wider border-b">Título</th>
                    <th class="py-3 px-4 text-left font-medium text-gray-600 uppercase tracking-wider border-b">Aluno</th>
                    <th class="py-3 px-4 text-left font-medium text-gray-600 uppercase tracking-wider border-b">Tipo</th>
                    <th class="py-3 px-4 text-left font-medium text-gray-600 uppercase tracking-wider border-b">Data</th>
                    <th class="py-3 px-4 text-left font-medium text-gray-600 uppercase tracking-wider border-b">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach ($documentos_recentes as $doc): ?>
                <tr class="hover:bg-gray-50">
                    <td class="py-3 px-4 border-b"><?php echo htmlspecialchars($doc['numero'] ?? '-'); ?></td>
                    <td class="py-3 px-4 border-b"><?php echo htmlspecialchars($doc['titulo']); ?></td>
                    <td class="py-3 px-4 border-b"><?php echo htmlspecialchars($doc['aluno_nome']); ?></td>
                    <td class="py-3 px-4 border-b"><?php echo htmlspecialchars($doc['tipo_documento']); ?></td>
                    <td class="py-3 px-4 border-b"><?php echo date('d/m/Y', strtotime($doc['data_emissao'])); ?></td>
                    <td class="py-3 px-4 border-b">
                        <div class="flex space-x-2">
                            <a href="documentos.php?action=visualizar&id=<?php echo $doc['id']; ?>" class="text-blue-500 hover:text-blue-700" title="Visualizar">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="documentos.php?action=download&id=<?php echo $doc['id']; ?>" class="text-green-500 hover:text-green-700" title="Download">
                                <i class="fas fa-download"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="p-6 text-center text-gray-500">
            Nenhum documento emitido recentemente.
        </div>
        <?php endif; ?>
    </div>
    <div class="p-4 bg-gray-50 border-t border-gray-200 text-right">
        <a href="documentos.php?action=listar" class="text-blue-500 hover:text-blue-700">
            <i class="fas fa-list mr-1"></i> Ver todos os documentos
        </a>
    </div>
</div>