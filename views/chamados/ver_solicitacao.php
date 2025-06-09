<div class="bg-white shadow-md rounded-lg p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800"><?php echo $titulo_pagina; ?></h1>

        <div class="flex space-x-2">
            <a href="index.php?view=solicitacoes" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
                <i class="fas fa-arrow-left mr-2"></i> Voltar
            </a>

            <?php if (!$is_polo && $solicitacao['status'] == 'solicitado'): ?>
            <a href="responder_solicitacao.php?id=<?php echo $id; ?>" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                <i class="fas fa-reply mr-2"></i> Responder
            </a>
            <?php endif; ?>

            <?php if ($is_polo && $solicitacao['status'] == 'solicitado'): ?>
            <a href="editar_solicitacao.php?id=<?php echo $id; ?>" class="bg-yellow-600 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded">
                <i class="fas fa-edit mr-2"></i> Editar
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Detalhes da Solicitação -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-gray-50 p-4 rounded-lg">
            <h2 class="text-lg font-semibold mb-4">Informações do Aluno</h2>

            <div class="mb-3">
                <span class="block text-sm font-medium text-gray-700">Nome:</span>
                <span class="block text-sm text-gray-900"><?php echo htmlspecialchars($solicitacao['aluno_nome']); ?></span>
            </div>

            <div class="mb-3">
                <span class="block text-sm font-medium text-gray-700">CPF:</span>
                <span class="block text-sm text-gray-900"><?php echo formatarCpf($solicitacao['aluno_cpf']); ?></span>
            </div>

            <div class="mb-3">
                <span class="block text-sm font-medium text-gray-700">Email:</span>
                <span class="block text-sm text-gray-900"><?php echo htmlspecialchars($solicitacao['aluno_email']); ?></span>
            </div>

            <?php if (!empty($solicitacao['curso_nome'])): ?>
            <div class="mb-3">
                <span class="block text-sm font-medium text-gray-700">Curso:</span>
                <span class="block text-sm text-gray-900"><?php echo htmlspecialchars($solicitacao['curso_nome']); ?></span>
            </div>
            <?php endif; ?>
        </div>

        <div class="bg-gray-50 p-4 rounded-lg">
            <h2 class="text-lg font-semibold mb-4">Informações da Solicitação</h2>

            <div class="mb-3">
                <span class="block text-sm font-medium text-gray-700">Tipo de Documento:</span>
                <span class="block text-sm text-gray-900"><?php echo htmlspecialchars($solicitacao['tipo_documento_nome']); ?></span>
            </div>

           <div class="mb-3">
                <span class="block text-sm font-medium text-gray-700">Quantidade:</span>
                <span class="block text-sm text-gray-900"><?php echo $solicitacao['quantidade']; ?></span>
            </div>

            <div class="mb-3">
                <span class="block text-sm font-medium text-gray-700">Finalidade:</span>
                <span class="block text-sm text-gray-900"><?php echo !empty($solicitacao['finalidade']) ? htmlspecialchars($solicitacao['finalidade']) : 'Não informada'; ?></span>
            </div>

            <div class="mb-3">
                <span class="block text-sm font-medium text-gray-700">Valor Total:</span>
                <span class="block text-sm text-gray-900">
                    <?php echo !empty($solicitacao['valor_total']) ? 'R$ ' . number_format($solicitacao['valor_total'], 2, ',', '.') : 'Gratuito'; ?>
                </span>
            </div>

            <div class="mb-3">
                <span class="block text-sm font-medium text-gray-700">Pago:</span>
                <span class="block text-sm text-gray-900">
                    <?php echo $solicitacao['pago'] ? 'Sim' : 'Não'; ?>
                </span>
            </div>
        </div>

        <div class="bg-gray-50 p-4 rounded-lg">
            <h2 class="text-lg font-semibold mb-4">Informações Adicionais</h2>

            <div class="mb-3">
                <span class="block text-sm font-medium text-gray-700">Status:</span>
                <?php
                $status_class = '';
                $status_text = '';

                switch ($solicitacao['status']) {
                    case 'solicitado':
                        $status_class = 'bg-yellow-100 text-yellow-800';
                        $status_text = 'Solicitado';
                        break;
                    case 'processando':
                        $status_class = 'bg-blue-100 text-blue-800';
                        $status_text = 'Processando';
                        break;
                    case 'pronto':
                        $status_class = 'bg-green-100 text-green-800';
                        $status_text = 'Pronto';
                        break;
                    case 'entregue':
                        $status_class = 'bg-indigo-100 text-indigo-800';
                        $status_text = 'Entregue';
                        break;
                    case 'cancelado':
                        $status_class = 'bg-red-100 text-red-800';
                        $status_text = 'Cancelado';
                        break;
                }
                ?>
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                    <?php echo $status_text; ?>
                </span>
            </div>

            <div class="mb-3">
                <span class="block text-sm font-medium text-gray-700">Polo:</span>
                <span class="block text-sm text-gray-900"><?php echo htmlspecialchars($solicitacao['polo_nome']); ?></span>
            </div>

            <div class="mb-3">
                <span class="block text-sm font-medium text-gray-700">Solicitante:</span>
                <span class="block text-sm text-gray-900">
                    <?php echo htmlspecialchars($solicitacao['solicitante_nome']); ?>
                    <?php if (!empty($solicitacao['solicitante_tipo'])): ?>
                    (<?php
                        switch($solicitacao['solicitante_tipo']) {
                            case 'admin_master':
                                echo 'Administrador';
                                break;
                            case 'secretaria_academica':
                                echo 'Secretaria Acadêmica';
                                break;
                            case 'polo':
                                echo 'Polo';
                                break;
                            default:
                                echo ucfirst($solicitacao['solicitante_tipo']);
                        }
                    ?>)
                    <?php endif; ?>
                </span>
            </div>

            <div class="mb-3">
                <span class="block text-sm font-medium text-gray-700">Data da Solicitação:</span>
                <span class="block text-sm text-gray-900">
                    <?php echo date('d/m/Y', strtotime($solicitacao['created_at'])); ?> às
                    <?php echo date('H:i', strtotime($solicitacao['created_at'])); ?>
                </span>
            </div>

            <?php if (!empty($solicitacao['updated_at']) && $solicitacao['updated_at'] != $solicitacao['created_at']): ?>
            <div class="mb-3">
                <span class="block text-sm font-medium text-gray-700">Última Atualização:</span>
                <span class="block text-sm text-gray-900">
                    <?php echo date('d/m/Y', strtotime($solicitacao['updated_at'])); ?> às
                    <?php echo date('H:i', strtotime($solicitacao['updated_at'])); ?>
                </span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Observações -->
    <?php if (!empty($solicitacao['observacoes'])): ?>
    <div class="bg-gray-50 p-4 rounded-lg mb-6">
        <h2 class="text-lg font-semibold mb-2">Observações</h2>
        <p class="text-sm text-gray-900 whitespace-pre-line"><?php echo htmlspecialchars($solicitacao['observacoes']); ?></p>
    </div>
    <?php endif; ?>

    <!-- Documento Emitido -->
    <?php if ($documento): ?>
    <div class="bg-green-50 p-4 rounded-lg mb-6 border border-green-200">
        <h2 class="text-lg font-semibold mb-2 text-green-800">Documento Emitido</h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <span class="block text-sm font-medium text-green-700">Número do Documento:</span>
                <span class="block text-sm text-green-900"><?php echo htmlspecialchars($documento['numero_documento']); ?></span>
            </div>

            <div>
                <span class="block text-sm font-medium text-green-700">Data de Emissão:</span>
                <span class="block text-sm text-green-900"><?php echo date('d/m/Y', strtotime($documento['data_emissao'])); ?></span>
            </div>

            <div>
                <span class="block text-sm font-medium text-green-700">Código de Verificação:</span>
                <span class="block text-sm text-green-900"><?php echo $documento['codigo_verificacao']; ?></span>
            </div>
        </div>

        <div class="mt-4">
            <a href="<?php echo dirname($_SERVER['PHP_SELF']) == '/chamados' ? '../documentos.php' : 'documentos.php'; ?>?action=download&id=<?php echo $documento['id']; ?>" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-md" target="_blank">
                <i class="fas fa-download mr-2"></i> Baixar Documento
            </a>

            <a href="<?php echo dirname($_SERVER['PHP_SELF']) == '/chamados' ? '../documentos.php' : 'documentos.php'; ?>?action=visualizar&id=<?php echo $documento['id']; ?>" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md ml-2" target="_blank">
                <i class="fas fa-eye mr-2"></i> Visualizar Documento
            </a>
        </div>
    </div>
    <?php elseif ($solicitacao['status'] == 'pronto'): ?>
    <div id="documento-pronto" class="bg-yellow-50 p-4 rounded-lg mb-6 border border-yellow-200">
        <h2 class="text-lg font-semibold mb-2 text-yellow-800">Documento Pronto</h2>
        <p class="text-sm text-yellow-700 mb-4">O documento foi marcado como pronto e está disponível para download.</p>

        <!-- Debug para verificar as condições -->
        <!-- Status: <?php echo $solicitacao['status']; ?>, Is Polo: <?php echo $is_polo ? 'Sim' : 'Não'; ?>, Documento ID: <?php echo $solicitacao['documento_id'] ?? 'Não definido'; ?> -->

        <?php if (!empty($solicitacao['documento_id'])): ?>
        <div class="flex flex-wrap gap-2">
            <a href="<?php echo dirname($_SERVER['PHP_SELF']) == '/chamados' ? '../documentos.php' : 'documentos.php'; ?>?action=download&id=<?php echo $solicitacao['documento_id']; ?>" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-md">
                <i class="fas fa-download mr-2"></i> Baixar Documento
            </a>
            <a href="<?php echo dirname($_SERVER['PHP_SELF']) == '/chamados' ? '../documentos.php' : 'documentos.php'; ?>?action=visualizar&id=<?php echo $solicitacao['documento_id']; ?>" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md">
                <i class="fas fa-eye mr-2"></i> Visualizar Documento
            </a>
        </div>
        <?php else: ?>
            <?php if ($is_polo): ?>
            <p class="text-sm text-yellow-700 mb-4">O documento ainda não está disponível para download. Por favor, aguarde alguns instantes e atualize a página.</p>
            <?php else: ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Área de carregamento (inicialmente oculta) -->
    <div id="documento-gerando" class="bg-blue-50 p-4 rounded-lg mb-6 border border-blue-200" style="display: none;">
        <h2 class="text-lg font-semibold mb-2 text-blue-800">Gerando Documento</h2>
        <p class="text-sm text-blue-700 mb-4">Por favor, aguarde enquanto o documento está sendo gerado...</p>
        <div class="flex justify-center">
            <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-700"></div>
        </div>
    </div>

    <!-- Área de sucesso (inicialmente oculta) -->
    <div id="documento-sucesso" class="bg-green-50 p-4 rounded-lg mb-6 border border-green-200" style="display: none;">
        <h2 class="text-lg font-semibold mb-2 text-green-800">Documento Gerado com Sucesso</h2>
        <p class="text-sm text-green-700 mb-4">O documento foi gerado com sucesso e está pronto para visualização.</p>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4" id="documento-info">
            <!-- Será preenchido via JavaScript -->
        </div>

        <div class="mt-4">
            <a id="link-download" href="#" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-md" target="_blank">
                <i class="fas fa-download mr-2"></i> Baixar Documento
            </a>

            <a id="link-visualizar" href="#" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md ml-2" target="_blank">
                <i class="fas fa-eye mr-2"></i> Visualizar Documento
            </a>
        </div>
    </div>

    <!-- Área de erro (inicialmente oculta) -->
    <div id="documento-erro" class="bg-red-50 p-4 rounded-lg mb-6 border border-red-200" style="display: none;">
        <h2 class="text-lg font-semibold mb-2 text-red-800">Erro ao Gerar Documento</h2>
        <p id="erro-mensagem" class="text-sm text-red-700 mb-4">Ocorreu um erro ao gerar o documento.</p>

        <button onclick="gerarDocumento(<?php echo $id; ?>)" class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-md">
            <i class="fas fa-sync-alt mr-2"></i> Tentar Novamente
        </button>
    </div>
    <?php endif; ?>

    <script>
    function gerarDocumento(id) {
        // Oculta a área de documento pronto
        document.getElementById('documento-pronto').style.display = 'none';
        document.getElementById('documento-sucesso').style.display = 'none';
        document.getElementById('documento-erro').style.display = 'none';

        // Mostra a área de carregamento
        document.getElementById('documento-gerando').style.display = 'block';

        // Cria um objeto FormData
        const formData = new FormData();
        formData.append('id', id);

        // Faz a requisição AJAX
        fetch('../chamados/ajax_gerar_documento.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            // Verifica se a resposta é um JSON válido
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                return response.json().then(data => {
                    return { ok: response.ok, status: response.status, data };
                });
            } else {
                // Se não for JSON, lê como texto para mostrar o erro
                return response.text().then(text => {
                    throw new Error(`Resposta não é JSON válido: ${text}`);
                });
            }
        })
        .then(result => {
            // Oculta a área de carregamento
            document.getElementById('documento-gerando').style.display = 'none';

            const data = result.data;

            if (data.success) {
                // Preenche as informações do documento
                document.getElementById('documento-info').innerHTML = `
                    <div>
                        <span class="block text-sm font-medium text-green-700">ID do Documento:</span>
                        <span class="block text-sm text-green-900">${data.documento_id}</span>
                    </div>

                    <div>
                        <span class="block text-sm font-medium text-green-700">Arquivo:</span>
                        <span class="block text-sm text-green-900">${data.arquivo}</span>
                    </div>

                    <div>
                        <span class="block text-sm font-medium text-green-700">Data de Geração:</span>
                        <span class="block text-sm text-green-900">${new Date().toLocaleDateString()}</span>
                    </div>
                `;

                // Atualiza os links
                document.getElementById('link-download').href = data.download_url;
                document.getElementById('link-visualizar').href = data.visualizar_url;

                // Mostra a área de sucesso
                document.getElementById('documento-sucesso').style.display = 'block';

                // Recarrega a página após 5 segundos para atualizar o status
                setTimeout(() => {
                    window.location.reload();
                }, 5000);
            } else {
                // Preenche a mensagem de erro
                document.getElementById('erro-mensagem').textContent = data.message || 'Ocorreu um erro ao gerar o documento.';

                // Mostra a área de erro
                document.getElementById('documento-erro').style.display = 'block';

                // Log do erro para diagnóstico
                console.error('Erro retornado pelo servidor:', data.message);
            }
        })
        .catch(error => {
            // Oculta a área de carregamento
            document.getElementById('documento-gerando').style.display = 'none';

            // Preenche a mensagem de erro
            document.getElementById('erro-mensagem').textContent = 'Erro de conexão: ' + error.message;

            // Mostra a área de erro
            document.getElementById('documento-erro').style.display = 'block';

            // Log do erro para diagnóstico
            console.error('Erro de conexão:', error);
        });
    }
    </script>

    <?php if (!$is_polo): ?>
    <div class="mt-6">
        <?php if ($solicitacao['status'] == 'solicitado'): ?>
        <a href="responder_solicitacao.php?id=<?php echo $id; ?>" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md mr-2">
            <i class="fas fa-reply mr-2"></i> Responder Solicitação
        </a>
        <?php endif; ?>

        <?php if (!$documento): ?>
       
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>