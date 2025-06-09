<?php
// Define o título da página
$titulo_pagina = 'Meus Documentos';

// Inclui o cabeçalho
include 'includes/header.php';

// Obtém o ID do aluno
$aluno_id = $_SESSION['aluno_id'];

// Obtém a matrícula do aluno
$matricula = $db->query("SELECT * FROM matriculas WHERE aluno_id = ? AND status = 'ativo' ORDER BY data_matricula DESC LIMIT 1", [$aluno_id]);
$matricula = $matricula[0] ?? null;

// Obtém o curso do aluno
$curso = null;
if ($matricula) {
    $curso = $db->query("SELECT * FROM cursos WHERE id = ?", [$matricula['curso_id']]);
    $curso = $curso[0] ?? null;
}

// Obtém a turma do aluno
$turma = null;
if ($matricula && $matricula['turma_id']) {
    $turma = $db->query("SELECT * FROM turmas WHERE id = ?", [$matricula['turma_id']]);
    $turma = $turma[0] ?? null;
}

// Obtém os documentos emitidos para o aluno
$documentos_emitidos = $db->query("SELECT * FROM documentos_emitidos 
                                  WHERE aluno_id = ? 
                                  ORDER BY data_emissao DESC", [$aluno_id]);

// Obtém os documentos pessoais do aluno
$documentos_pessoais = $db->query("SELECT * FROM documentos_alunos 
                                  WHERE aluno_id = ? 
                                  ORDER BY tipo", [$aluno_id]);

// Obtém os certificados do AVA (se o aluno tiver acesso)
$certificados_ava = [];
if ($aluno['acesso_ava']) {
    $certificados_ava = $db->query("SELECT c.*, am.curso_id, ac.titulo as curso_titulo 
                                   FROM ava_certificados c 
                                   INNER JOIN ava_matriculas am ON c.matricula_id = am.id 
                                   INNER JOIN ava_cursos ac ON am.curso_id = ac.id 
                                   WHERE am.aluno_id = ? AND c.arquivo_path IS NOT NULL 
                                   ORDER BY c.data_emissao DESC", [$aluno_id]);
}

// Processa o formulário de solicitação de documento
$mensagem = '';
$tipo_mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['solicitar_documento'])) {
    $tipo_documento = $_POST['tipo_documento'] ?? '';
    $observacoes = $_POST['observacoes'] ?? '';
    
    if (empty($tipo_documento)) {
        $mensagem = 'Por favor, selecione o tipo de documento.';
        $tipo_mensagem = 'erro';
    } else {
        // Verifica se já existe uma solicitação pendente para este tipo de documento
        $solicitacao_existente = $db->query("SELECT * FROM solicitacoes_documentos 
                                            WHERE aluno_id = ? AND tipo = ? AND status = 'pendente'", 
                                            [$aluno_id, $tipo_documento]);
        
        if (count($solicitacao_existente) > 0) {
            $mensagem = 'Já existe uma solicitação pendente para este tipo de documento.';
            $tipo_mensagem = 'erro';
        } else {
            // Insere a solicitação
            $inserido = $db->insert('solicitacoes_documentos', [
                'aluno_id' => $aluno_id,
                'tipo' => $tipo_documento,
                'observacoes' => $observacoes,
                'status' => 'pendente',
                'data_solicitacao' => date('Y-m-d H:i:s')
            ]);
            
            if ($inserido) {
                $mensagem = 'Solicitação enviada com sucesso! Acompanhe o status na aba "Solicitações".';
                $tipo_mensagem = 'sucesso';
                
                // Registra a atividade
                $db->insert('alunos_atividades', [
                    'aluno_id' => $aluno_id,
                    'tipo' => 'solicitacao',
                    'descricao' => 'Solicitou documento: ' . $tipo_documento,
                    'ip' => $_SERVER['REMOTE_ADDR']
                ]);
            } else {
                $mensagem = 'Erro ao enviar a solicitação. Tente novamente.';
                $tipo_mensagem = 'erro';
            }
        }
    }
}

// Obtém as solicitações de documentos do aluno
$solicitacoes = $db->query("SELECT * FROM solicitacoes_documentos 
                           WHERE aluno_id = ? 
                           ORDER BY data_solicitacao DESC", [$aluno_id]);

// Função para obter o nome do tipo de documento
function getNomeTipoDocumento($tipo) {
    $tipos = [
        'historico' => 'Histórico Escolar',
        'declaracao_matricula' => 'Declaração de Matrícula',
        'declaracao_conclusao' => 'Declaração de Conclusão',
        'certificado' => 'Certificado',
        'diploma' => 'Diploma',
        'outros' => 'Outros'
    ];
    
    return $tipos[$tipo] ?? $tipo;
}

// Função para obter a classe de cor com base no status da solicitação
function getStatusSolicitacaoClasse($status) {
    $classes = [
        'pendente' => 'bg-yellow-100 text-yellow-800',
        'em_processamento' => 'bg-blue-100 text-blue-800',
        'concluido' => 'bg-green-100 text-green-800',
        'rejeitado' => 'bg-red-100 text-red-800'
    ];
    
    return $classes[$status] ?? 'bg-gray-100 text-gray-800';
}

// Função para obter o ícone com base no status da solicitação
function getStatusSolicitacaoIcone($status) {
    $icones = [
        'pendente' => 'fa-clock',
        'em_processamento' => 'fa-spinner',
        'concluido' => 'fa-check-circle',
        'rejeitado' => 'fa-times-circle'
    ];
    
    return $icones[$status] ?? 'fa-question-circle';
}
?>

<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-800 mb-2">Meus Documentos</h2>
    <p class="text-gray-600">Acesse seus documentos acadêmicos, solicite novos documentos e acompanhe suas solicitações.</p>
</div>

<?php if (!empty($mensagem)): ?>
<div class="bg-<?php echo $tipo_mensagem === 'sucesso' ? 'green' : 'red'; ?>-100 border-l-4 border-<?php echo $tipo_mensagem === 'sucesso' ? 'green' : 'red'; ?>-500 text-<?php echo $tipo_mensagem === 'sucesso' ? 'green' : 'red'; ?>-700 p-4 mb-6">
    <div class="flex">
        <div class="flex-shrink-0">
            <i class="fas fa-<?php echo $tipo_mensagem === 'sucesso' ? 'check' : 'exclamation'; ?>-circle"></i>
        </div>
        <div class="ml-3">
            <p><?php echo $mensagem; ?></p>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="profile-tabs mb-6">
    <div class="profile-tab active" data-tab="documentos-emitidos">Documentos Emitidos</div>
    <div class="profile-tab" data-tab="solicitar">Solicitar Documento</div>
    <div class="profile-tab" data-tab="solicitacoes">Minhas Solicitações</div>
    <div class="profile-tab" data-tab="documentos-pessoais">Documentos Pessoais</div>
    <?php if ($aluno['acesso_ava'] && count($certificados_ava) > 0): ?>
    <div class="profile-tab" data-tab="certificados-ava">Certificados AVA</div>
    <?php endif; ?>
</div>

<!-- Tab: Documentos Emitidos -->
<div class="tab-content active" id="tab-documentos-emitidos">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Documentos Emitidos</h3>
        </div>
        <div class="card-body p-0">
            <?php if (count($documentos_emitidos) > 0): ?>
            <div class="divide-y divide-gray-100">
                <?php foreach ($documentos_emitidos as $documento): ?>
                <div class="p-4 hover:bg-gray-50">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center mr-4">
                            <?php if ($documento['tipo'] === 'historico'): ?>
                            <i class="fas fa-file-alt text-indigo-600"></i>
                            <?php elseif ($documento['tipo'] === 'declaracao_matricula' || $documento['tipo'] === 'declaracao_conclusao'): ?>
                            <i class="fas fa-file-contract text-indigo-600"></i>
                            <?php elseif ($documento['tipo'] === 'certificado' || $documento['tipo'] === 'diploma'): ?>
                            <i class="fas fa-certificate text-indigo-600"></i>
                            <?php else: ?>
                            <i class="fas fa-file text-indigo-600"></i>
                            <?php endif; ?>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-medium text-gray-900"><?php echo getNomeTipoDocumento($documento['tipo']); ?></h4>
                            <p class="text-xs text-gray-500 mt-1">
                                Emitido em <?php echo date('d/m/Y', strtotime($documento['data_emissao'])); ?>
                            </p>
                        </div>
                        <div>
                            <?php if (!empty($documento['arquivo'])): ?>
                            <a href="<?php echo $documento['arquivo']; ?>" target="_blank" class="btn btn-outline btn-sm">
                                <i class="fas fa-download mr-2"></i> Baixar
                            </a>
                            <?php else: ?>
                            <span class="text-sm text-gray-500">Arquivo não disponível</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="text-center py-8">
                <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-file-alt text-gray-400 text-2xl"></i>
                </div>
                <h4 class="font-medium text-gray-900 mb-1">Nenhum documento emitido</h4>
                <p class="text-sm text-gray-500">Você ainda não possui documentos emitidos pela instituição.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Tab: Solicitar Documento -->
<div class="tab-content" id="tab-solicitar">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Solicitar Novo Documento</h3>
        </div>
        <div class="card-body">
            <form action="" method="post">
                <div class="mb-4">
                    <label for="tipo_documento" class="block text-sm font-medium text-gray-700 mb-1">Tipo de Documento *</label>
                    <select name="tipo_documento" id="tipo_documento" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required>
                        <option value="">Selecione...</option>
                        <option value="historico">Histórico Escolar</option>
                        <option value="declaracao_matricula">Declaração de Matrícula</option>
                        <option value="declaracao_conclusao">Declaração de Conclusão</option>
                        <option value="certificado">Certificado</option>
                        <option value="diploma">Diploma</option>
                        <option value="outros">Outros</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label for="observacoes" class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                    <textarea name="observacoes" id="observacoes" rows="4" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"></textarea>
                    <p class="text-xs text-gray-500 mt-1">Informe detalhes adicionais sobre sua solicitação, se necessário.</p>
                </div>
                
                <div class="mt-6 flex justify-end">
                    <button type="submit" name="solicitar_documento" class="btn btn-primary">
                        <i class="fas fa-paper-plane mr-2"></i> Enviar Solicitação
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Tab: Minhas Solicitações -->
<div class="tab-content" id="tab-solicitacoes">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Minhas Solicitações</h3>
        </div>
        <div class="card-body p-0">
            <?php if (count($solicitacoes) > 0): ?>
            <div class="divide-y divide-gray-100">
                <?php foreach ($solicitacoes as $solicitacao): ?>
                <div class="p-4 hover:bg-gray-50">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center mr-4 <?php echo getStatusSolicitacaoClasse($solicitacao['status']); ?>">
                            <i class="fas <?php echo getStatusSolicitacaoIcone($solicitacao['status']); ?>"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-medium text-gray-900"><?php echo getNomeTipoDocumento($solicitacao['tipo']); ?></h4>
                            <p class="text-xs text-gray-500 mt-1">
                                Solicitado em <?php echo date('d/m/Y H:i', strtotime($solicitacao['data_solicitacao'])); ?>
                            </p>
                            <?php if (!empty($solicitacao['observacoes'])): ?>
                            <p class="text-xs text-gray-500 mt-1">
                                <i class="fas fa-comment-alt mr-1"></i> <?php echo $solicitacao['observacoes']; ?>
                            </p>
                            <?php endif; ?>
                        </div>
                        <div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo getStatusSolicitacaoClasse($solicitacao['status']); ?>">
                                <?php 
                                if ($solicitacao['status'] === 'pendente') {
                                    echo 'Pendente';
                                } elseif ($solicitacao['status'] === 'em_processamento') {
                                    echo 'Em Processamento';
                                } elseif ($solicitacao['status'] === 'concluido') {
                                    echo 'Concluído';
                                } elseif ($solicitacao['status'] === 'rejeitado') {
                                    echo 'Rejeitado';
                                } else {
                                    echo ucfirst($solicitacao['status']);
                                }
                                ?>
                            </span>
                        </div>
                    </div>
                    
                    <?php if ($solicitacao['status'] === 'concluido' && !empty($solicitacao['arquivo'])): ?>
                    <div class="mt-3 ml-14">
                        <a href="<?php echo $solicitacao['arquivo']; ?>" target="_blank" class="text-indigo-600 hover:text-indigo-800">
                            <i class="fas fa-download mr-1"></i> Baixar Documento
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($solicitacao['status'] === 'rejeitado' && !empty($solicitacao['motivo_rejeicao'])): ?>
                    <div class="mt-3 ml-14 p-3 bg-red-50 rounded-md">
                        <p class="text-sm text-red-700">
                            <i class="fas fa-info-circle mr-1"></i> Motivo da rejeição: <?php echo $solicitacao['motivo_rejeicao']; ?>
                        </p>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="text-center py-8">
                <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-clipboard-list text-gray-400 text-2xl"></i>
                </div>
                <h4 class="font-medium text-gray-900 mb-1">Nenhuma solicitação encontrada</h4>
                <p class="text-sm text-gray-500">Você ainda não solicitou nenhum documento.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Tab: Documentos Pessoais -->
<div class="tab-content" id="tab-documentos-pessoais">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Documentos Pessoais</h3>
            <a href="perfil.php#tab-documentos" class="text-sm text-blue-600 hover:text-blue-800">
                <i class="fas fa-upload mr-1"></i> Enviar Novo Documento
            </a>
        </div>
        <div class="card-body p-0">
            <?php if (count($documentos_pessoais) > 0): ?>
            <div class="divide-y divide-gray-100">
                <?php foreach ($documentos_pessoais as $documento): ?>
                <div class="p-4 hover:bg-gray-50">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center mr-4 <?php echo getStatusDocumentoClasse($documento['status']); ?>">
                            <i class="fas <?php echo getStatusDocumentoIcone($documento['status']); ?>"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-medium text-gray-900"><?php echo getNomeTipoDocumento($documento['tipo']); ?></h4>
                            <p class="text-xs text-gray-500 mt-1">
                                Enviado em <?php echo date('d/m/Y H:i', strtotime($documento['data_envio'])); ?>
                            </p>
                            <?php if (!empty($documento['observacoes'])): ?>
                            <p class="text-xs text-gray-500 mt-1">
                                <i class="fas fa-comment-alt mr-1"></i> <?php echo $documento['observacoes']; ?>
                            </p>
                            <?php endif; ?>
                        </div>
                        <div class="flex items-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo getStatusDocumentoClasse($documento['status']); ?> mr-4">
                                <?php echo ucfirst($documento['status']); ?>
                            </span>
                            <a href="<?php echo $documento['arquivo']; ?>" target="_blank" class="text-indigo-600 hover:text-indigo-800">
                                <i class="fas fa-eye"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="text-center py-8">
                <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-id-card text-gray-400 text-2xl"></i>
                </div>
                <h4 class="font-medium text-gray-900 mb-1">Nenhum documento pessoal encontrado</h4>
                <p class="text-sm text-gray-500">Você ainda não enviou nenhum documento pessoal.</p>
                <div class="mt-4">
                    <a href="perfil.php#tab-documentos" class="btn btn-primary">
                        <i class="fas fa-upload mr-2"></i> Enviar Documentos
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($aluno['acesso_ava'] && count($certificados_ava) > 0): ?>
<!-- Tab: Certificados AVA -->
<div class="tab-content" id="tab-certificados-ava">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Certificados de Cursos Online</h3>
        </div>
        <div class="card-body p-0">
            <div class="divide-y divide-gray-100">
                <?php foreach ($certificados_ava as $certificado): ?>
                <div class="p-4 hover:bg-gray-50">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center mr-4">
                            <i class="fas fa-certificate text-green-600"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-medium text-gray-900"><?php echo $certificado['curso_titulo']; ?></h4>
                            <p class="text-xs text-gray-500 mt-1">
                                Emitido em <?php echo date('d/m/Y', strtotime($certificado['data_emissao'])); ?>
                            </p>
                            <p class="text-xs text-gray-500 mt-1">
                                <i class="fas fa-fingerprint mr-1"></i> Código: <?php echo $certificado['codigo']; ?>
                            </p>
                        </div>
                        <div>
                            <a href="<?php echo $certificado['arquivo_path']; ?>" target="_blank" class="btn btn-outline btn-sm">
                                <i class="fas fa-download mr-2"></i> Baixar
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
// Scripts específicos da página
$page_scripts = '
// Tabs
const profileTabs = document.querySelectorAll(".profile-tab");
const tabContents = document.querySelectorAll(".tab-content");

profileTabs.forEach(tab => {
    tab.addEventListener("click", function() {
        const tabName = this.getAttribute("data-tab");
        
        // Remove a classe active de todas as tabs
        profileTabs.forEach(t => t.classList.remove("active"));
        
        // Adiciona a classe active na tab clicada
        this.classList.add("active");
        
        // Esconde todos os conteúdos
        tabContents.forEach(content => content.classList.remove("active"));
        
        // Mostra o conteúdo correspondente
        document.getElementById("tab-" + tabName).classList.add("active");
    });
});

// Verifica se há um hash na URL para abrir uma tab específica
if (window.location.hash) {
    const tabName = window.location.hash.substring(1);
    const tab = document.querySelector(`.profile-tab[data-tab="${tabName}"]`);
    if (tab) {
        tab.click();
    }
}
';

// Inclui o rodapé
include 'includes/footer.php';
?>
