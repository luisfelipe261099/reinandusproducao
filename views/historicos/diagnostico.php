<div class="container mx-auto px-4 py-6">
    <h1 class="text-2xl font-bold mb-6">Diagnóstico de Documentos</h1>
    
    <div class="bg-white shadow-md rounded-lg p-6 mb-6">
        <h2 class="text-xl font-semibold mb-4">Verificação de Diretórios</h2>
        
        <?php
        $diretorios = [
            'uploads' => is_dir('uploads'),
            'uploads/documentos' => is_dir('uploads/documentos'),
            'temp' => is_dir('temp')
        ];
        
        foreach ($diretorios as $dir => $existe):
            $permissao = $existe ? (is_writable($dir) ? 'Gravável' : 'Somente leitura') : 'N/A';
        ?>
            <div class="flex items-center mb-2">
                <span class="w-1/3"><?php echo $dir; ?></span>
                <span class="w-1/3">
                    <?php if ($existe): ?>
                        <span class="text-green-600"><i class="fas fa-check-circle"></i> Existe</span>
                    <?php else: ?>
                        <span class="text-red-600"><i class="fas fa-times-circle"></i> Não existe</span>
                    <?php endif; ?>
                </span>
                <span class="w-1/3"><?php echo $permissao; ?></span>
            </div>
        <?php endforeach; ?>
        
        <div class="mt-4">
            <button onclick="criarDiretorios()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Criar diretórios faltantes
            </button>
        </div>
    </div>
    
    <div class="bg-white shadow-md rounded-lg p-6">
        <h2 class="text-xl font-semibold mb-4">Últimos Documentos Gerados</h2>
        
        <table class="min-w-full divide-y divide-gray-200">
            <thead>
                <tr>
                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aluno</th>
                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Arquivo</th>
                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($documentos as $doc): ?>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $doc['id']; ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($doc['aluno_nome']); ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($doc['tipo_documento_nome']); ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo date('d/m/Y', strtotime($doc['data_emissao'])); ?></td>
                    <td class="px-6 py-4 text-sm text-gray-900"><?php echo htmlspecialchars($doc['arquivo']); ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php if ($doc['arquivo_existe']): ?>
                            <span class="text-green-600"><i class="fas fa-check-circle"></i> Arquivo encontrado</span>
                        <?php elseif (isset($doc['arquivo_temp_existe']) && $doc['arquivo_temp_existe']): ?>
                            <span class="text-yellow-600"><i class="fas fa-exclamation-circle"></i> Arquivo em pasta temporária</span>
                        <?php else: ?>
                            <span class="text-red-600"><i class="fas fa-times-circle"></i> Arquivo não encontrado</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <?php if ($doc['arquivo_existe'] || (isset($doc['arquivo_temp_existe']) && $doc['arquivo_temp_existe'])): ?>
                            <a href="documentos.php?action=download&id=<?php echo $doc['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                <i class="fas fa-download"></i> Baixar
                            </a>
                            <a href="documentos.php?action=visualizar&id=<?php echo $doc['id']; ?>" class="text-green-600 hover:text-green-900">
                                <i class="fas fa-eye"></i> Visualizar
                            </a>
                        <?php else: ?>
                            <button onclick="corrigirDocumento(<?php echo $doc['id']; ?>)" class="text-yellow-600 hover:text-yellow-900">
                                <i class="fas fa-wrench"></i> Tentar corrigir
                            </button>
                        <?php endif; ?>
                        </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function criarDiretorios() {
    fetch('documentos.php?action=criar_diretorios', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Diretórios criados com sucesso!');
            location.reload();
        } else {
            alert('Erro ao criar diretórios: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Ocorreu um erro ao processar a solicitação.');
    });
}

function corrigirDocumento(id) {
    fetch('documentos.php?action=corrigir_documento&id=' + id, {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Documento corrigido com sucesso!');
            location.reload();
        } else {
            alert('Erro ao corrigir documento: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Ocorreu um erro ao processar a solicitação.');
    });
}
</script>