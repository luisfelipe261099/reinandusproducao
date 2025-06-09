<?php
// Define o título da página
$titulo_pagina = 'Meu Perfil';

// Inclui o cabeçalho
include 'includes/header.php';

// Obtém os dados do aluno
$aluno_id = $_SESSION['aluno_id'];
$aluno = $db->getById('alunos', $aluno_id);

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

// Obtém o polo do aluno
$polo = null;
if ($matricula && $matricula['polo_id']) {
    $polo = $db->query("SELECT * FROM polos WHERE id = ?", [$matricula['polo_id']]);
    $polo = $polo[0] ?? null;
}

// Obtém os documentos do aluno
$documentos = $db->query("SELECT * FROM documentos_alunos WHERE aluno_id = ? ORDER BY tipo", [$aluno_id]);

// Processa o formulário de atualização de perfil
$mensagem = '';
$tipo_mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atualizar_perfil'])) {
    // Dados básicos
    $nome = $_POST['nome'] ?? '';
    $email = $_POST['email'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $celular = $_POST['celular'] ?? '';
    $data_nascimento = $_POST['data_nascimento'] ?? '';
    $cpf = $_POST['cpf'] ?? '';
    $rg = $_POST['rg'] ?? '';
    $endereco = $_POST['endereco'] ?? '';
    $numero = $_POST['numero'] ?? '';
    $complemento = $_POST['complemento'] ?? '';
    $bairro = $_POST['bairro'] ?? '';
    $cidade = $_POST['cidade'] ?? '';
    $estado = $_POST['estado'] ?? '';
    $cep = $_POST['cep'] ?? '';
    $biografia = $_POST['biografia'] ?? '';
    
    // Validação básica
    if (empty($nome) || empty($email) || empty($cpf)) {
        $mensagem = 'Por favor, preencha todos os campos obrigatórios.';
        $tipo_mensagem = 'erro';
    } else {
        // Atualiza os dados do aluno
        $dados_atualizados = [
            'nome' => $nome,
            'email' => $email,
            'telefone' => $telefone,
            'celular' => $celular,
            'data_nascimento' => $data_nascimento,
            'cpf' => $cpf,
            'rg' => $rg,
            'endereco' => $endereco,
            'numero' => $numero,
            'complemento' => $complemento,
            'bairro' => $bairro,
            'cidade' => $cidade,
            'estado' => $estado,
            'cep' => $cep,
            'biografia' => $biografia
        ];
        
        // Processa a foto de perfil
        if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
            $foto_tmp = $_FILES['foto_perfil']['tmp_name'];
            $foto_nome = $_FILES['foto_perfil']['name'];
            $foto_extensao = strtolower(pathinfo($foto_nome, PATHINFO_EXTENSION));
            
            // Verifica a extensão
            $extensoes_permitidas = ['jpg', 'jpeg', 'png'];
            if (in_array($foto_extensao, $extensoes_permitidas)) {
                // Gera um nome único para o arquivo
                $novo_nome = 'aluno_' . $aluno_id . '_' . time() . '.' . $foto_extensao;
                $caminho_destino = '../uploads/alunos/' . $novo_nome;
                
                // Cria o diretório se não existir
                if (!is_dir('../uploads/alunos/')) {
                    mkdir('../uploads/alunos/', 0755, true);
                }
                
                // Move o arquivo
                if (move_uploaded_file($foto_tmp, $caminho_destino)) {
                    $dados_atualizados['foto_perfil'] = $caminho_destino;
                } else {
                    $mensagem = 'Erro ao fazer upload da foto de perfil.';
                    $tipo_mensagem = 'erro';
                }
            } else {
                $mensagem = 'Formato de arquivo não permitido. Use apenas JPG, JPEG ou PNG.';
                $tipo_mensagem = 'erro';
            }
        }
        
        // Atualiza no banco de dados
        if (empty($mensagem)) {
            $atualizado = $db->update('alunos', $aluno_id, $dados_atualizados);
            
            if ($atualizado) {
                $mensagem = 'Perfil atualizado com sucesso!';
                $tipo_mensagem = 'sucesso';
                
                // Atualiza os dados do aluno na sessão
                $aluno = $db->getById('alunos', $aluno_id);
                
                // Registra a atividade
                $db->insert('alunos_atividades', [
                    'aluno_id' => $aluno_id,
                    'tipo' => 'perfil',
                    'descricao' => 'Atualizou informações do perfil',
                    'ip' => $_SERVER['REMOTE_ADDR']
                ]);
            } else {
                $mensagem = 'Erro ao atualizar o perfil. Tente novamente.';
                $tipo_mensagem = 'erro';
            }
        }
    }
}

// Processa o upload de documentos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_documento'])) {
    $tipo_documento = $_POST['tipo_documento'] ?? '';
    
    if (empty($tipo_documento)) {
        $mensagem = 'Por favor, selecione o tipo de documento.';
        $tipo_mensagem = 'erro';
    } elseif (!isset($_FILES['arquivo_documento']) || $_FILES['arquivo_documento']['error'] !== UPLOAD_ERR_OK) {
        $mensagem = 'Por favor, selecione um arquivo para upload.';
        $tipo_mensagem = 'erro';
    } else {
        $arquivo_tmp = $_FILES['arquivo_documento']['tmp_name'];
        $arquivo_nome = $_FILES['arquivo_documento']['name'];
        $arquivo_extensao = strtolower(pathinfo($arquivo_nome, PATHINFO_EXTENSION));
        
        // Verifica a extensão
        $extensoes_permitidas = ['pdf', 'jpg', 'jpeg', 'png'];
        if (in_array($arquivo_extensao, $extensoes_permitidas)) {
            // Gera um nome único para o arquivo
            $novo_nome = 'doc_' . $aluno_id . '_' . $tipo_documento . '_' . time() . '.' . $arquivo_extensao;
            $caminho_destino = '../uploads/documentos/' . $novo_nome;
            
            // Cria o diretório se não existir
            if (!is_dir('../uploads/documentos/')) {
                mkdir('../uploads/documentos/', 0755, true);
            }
            
            // Move o arquivo
            if (move_uploaded_file($arquivo_tmp, $caminho_destino)) {
                // Verifica se já existe um documento deste tipo
                $documento_existente = $db->query("SELECT * FROM documentos_alunos WHERE aluno_id = ? AND tipo = ?", [$aluno_id, $tipo_documento]);
                
                if (count($documento_existente) > 0) {
                    // Atualiza o documento existente
                    $atualizado = $db->update('documentos_alunos', $documento_existente[0]['id'], [
                        'arquivo' => $caminho_destino,
                        'status' => 'pendente',
                        'observacoes' => 'Documento atualizado pelo aluno'
                    ]);
                    
                    if ($atualizado) {
                        $mensagem = 'Documento atualizado com sucesso!';
                        $tipo_mensagem = 'sucesso';
                    } else {
                        $mensagem = 'Erro ao atualizar o documento. Tente novamente.';
                        $tipo_mensagem = 'erro';
                    }
                } else {
                    // Insere um novo documento
                    $inserido = $db->insert('documentos_alunos', [
                        'aluno_id' => $aluno_id,
                        'tipo' => $tipo_documento,
                        'arquivo' => $caminho_destino,
                        'status' => 'pendente',
                        'data_envio' => date('Y-m-d H:i:s')
                    ]);
                    
                    if ($inserido) {
                        $mensagem = 'Documento enviado com sucesso!';
                        $tipo_mensagem = 'sucesso';
                    } else {
                        $mensagem = 'Erro ao enviar o documento. Tente novamente.';
                        $tipo_mensagem = 'erro';
                    }
                }
                
                // Atualiza a lista de documentos
                $documentos = $db->query("SELECT * FROM documentos_alunos WHERE aluno_id = ? ORDER BY tipo", [$aluno_id]);
                
                // Registra a atividade
                $db->insert('alunos_atividades', [
                    'aluno_id' => $aluno_id,
                    'tipo' => 'documento',
                    'descricao' => 'Enviou documento: ' . $tipo_documento,
                    'ip' => $_SERVER['REMOTE_ADDR']
                ]);
            } else {
                $mensagem = 'Erro ao fazer upload do documento.';
                $tipo_mensagem = 'erro';
            }
        } else {
            $mensagem = 'Formato de arquivo não permitido. Use apenas PDF, JPG, JPEG ou PNG.';
            $tipo_mensagem = 'erro';
        }
    }
}

// Função para obter o nome do tipo de documento
function getNomeTipoDocumento($tipo) {
    $tipos = [
        'rg' => 'RG',
        'cpf' => 'CPF',
        'cnh' => 'CNH',
        'certidao_nascimento' => 'Certidão de Nascimento',
        'comprovante_residencia' => 'Comprovante de Residência',
        'diploma' => 'Diploma',
        'historico_escolar' => 'Histórico Escolar',
        'titulo_eleitor' => 'Título de Eleitor',
        'certificado_reservista' => 'Certificado de Reservista',
        'foto_3x4' => 'Foto 3x4',
        'outros' => 'Outros'
    ];
    
    return $tipos[$tipo] ?? $tipo;
}

// Função para obter a classe de cor com base no status do documento
function getStatusDocumentoClasse($status) {
    $classes = [
        'pendente' => 'bg-yellow-100 text-yellow-800',
        'aprovado' => 'bg-green-100 text-green-800',
        'rejeitado' => 'bg-red-100 text-red-800'
    ];
    
    return $classes[$status] ?? 'bg-gray-100 text-gray-800';
}

// Função para obter o ícone com base no status do documento
function getStatusDocumentoIcone($status) {
    $icones = [
        'pendente' => 'fa-clock',
        'aprovado' => 'fa-check-circle',
        'rejeitado' => 'fa-times-circle'
    ];
    
    return $icones[$status] ?? 'fa-question-circle';
}
?>

<div class="profile-header">
    <img src="<?php echo !empty($aluno['foto_perfil']) ? $aluno['foto_perfil'] : '../assets/img/avatar-placeholder.png'; ?>" alt="Foto de Perfil" class="profile-avatar">
    
    <div class="profile-info">
        <h1><?php echo $aluno['nome']; ?></h1>
        <p><?php echo $aluno['email']; ?></p>
        
        <?php if ($curso): ?>
        <div class="flex items-center mt-2">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 mr-2">
                <i class="fas fa-graduation-cap mr-1"></i> <?php echo $curso['nome']; ?>
            </span>
            
            <?php if ($turma): ?>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 mr-2">
                <i class="fas fa-users mr-1"></i> <?php echo $turma['nome']; ?>
            </span>
            <?php endif; ?>
            
            <?php if ($polo): ?>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                <i class="fas fa-map-marker-alt mr-1"></i> <?php echo $polo['nome']; ?>
            </span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
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

<div class="profile-tabs">
    <div class="profile-tab active" data-tab="informacoes">Informações Pessoais</div>
    <div class="profile-tab" data-tab="documentos">Documentos</div>
    <div class="profile-tab" data-tab="senha">Alterar Senha</div>
</div>

<!-- Tab: Informações Pessoais -->
<div class="tab-content active" id="tab-informacoes">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Informações Pessoais</h3>
        </div>
        <div class="card-body">
            <form action="" method="post" enctype="multipart/form-data">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="col-span-2">
                        <label for="foto_perfil" class="block text-sm font-medium text-gray-700 mb-1">Foto de Perfil</label>
                        <div class="flex items-center">
                            <div class="mr-4">
                                <img src="<?php echo !empty($aluno['foto_perfil']) ? $aluno['foto_perfil'] : '../assets/img/avatar-placeholder.png'; ?>" alt="Foto de Perfil" class="w-20 h-20 rounded-full object-cover">
                            </div>
                            <div>
                                <input type="file" name="foto_perfil" id="foto_perfil" class="hidden" accept="image/jpeg,image/png">
                                <label for="foto_perfil" class="btn btn-outline cursor-pointer">
                                    <i class="fas fa-upload mr-2"></i> Alterar Foto
                                </label>
                                <p class="text-xs text-gray-500 mt-1">JPG ou PNG. Máximo 2MB.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <label for="nome" class="block text-sm font-medium text-gray-700 mb-1">Nome Completo *</label>
                        <input type="text" name="nome" id="nome" value="<?php echo $aluno['nome']; ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required>
                    </div>
                    
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">E-mail *</label>
                        <input type="email" name="email" id="email" value="<?php echo $aluno['email']; ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required>
                    </div>
                    
                    <div>
                        <label for="cpf" class="block text-sm font-medium text-gray-700 mb-1">CPF *</label>
                        <input type="text" name="cpf" id="cpf" value="<?php echo $aluno['cpf']; ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required>
                    </div>
                    
                    <div>
                        <label for="rg" class="block text-sm font-medium text-gray-700 mb-1">RG</label>
                        <input type="text" name="rg" id="rg" value="<?php echo $aluno['rg'] ?? ''; ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                    </div>
                    
                    <div>
                        <label for="data_nascimento" class="block text-sm font-medium text-gray-700 mb-1">Data de Nascimento</label>
                        <input type="date" name="data_nascimento" id="data_nascimento" value="<?php echo $aluno['data_nascimento'] ?? ''; ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                    </div>
                    
                    <div>
                        <label for="telefone" class="block text-sm font-medium text-gray-700 mb-1">Telefone</label>
                        <input type="text" name="telefone" id="telefone" value="<?php echo $aluno['telefone'] ?? ''; ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                    </div>
                    
                    <div>
                        <label for="celular" class="block text-sm font-medium text-gray-700 mb-1">Celular</label>
                        <input type="text" name="celular" id="celular" value="<?php echo $aluno['celular'] ?? ''; ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                    </div>
                    
                    <div class="col-span-2">
                        <h4 class="font-medium text-gray-900 mb-2">Endereço</h4>
                    </div>
                    
                    <div>
                        <label for="cep" class="block text-sm font-medium text-gray-700 mb-1">CEP</label>
                        <input type="text" name="cep" id="cep" value="<?php echo $aluno['cep'] ?? ''; ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                    </div>
                    
                    <div>
                        <label for="endereco" class="block text-sm font-medium text-gray-700 mb-1">Endereço</label>
                        <input type="text" name="endereco" id="endereco" value="<?php echo $aluno['endereco'] ?? ''; ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                    </div>
                    
                    <div>
                        <label for="numero" class="block text-sm font-medium text-gray-700 mb-1">Número</label>
                        <input type="text" name="numero" id="numero" value="<?php echo $aluno['numero'] ?? ''; ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                    </div>
                    
                    <div>
                        <label for="complemento" class="block text-sm font-medium text-gray-700 mb-1">Complemento</label>
                        <input type="text" name="complemento" id="complemento" value="<?php echo $aluno['complemento'] ?? ''; ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                    </div>
                    
                    <div>
                        <label for="bairro" class="block text-sm font-medium text-gray-700 mb-1">Bairro</label>
                        <input type="text" name="bairro" id="bairro" value="<?php echo $aluno['bairro'] ?? ''; ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                    </div>
                    
                    <div>
                        <label for="cidade" class="block text-sm font-medium text-gray-700 mb-1">Cidade</label>
                        <input type="text" name="cidade" id="cidade" value="<?php echo $aluno['cidade'] ?? ''; ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                    </div>
                    
                    <div>
                        <label for="estado" class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                        <select name="estado" id="estado" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <option value="">Selecione...</option>
                            <option value="AC" <?php echo ($aluno['estado'] ?? '') === 'AC' ? 'selected' : ''; ?>>Acre</option>
                            <option value="AL" <?php echo ($aluno['estado'] ?? '') === 'AL' ? 'selected' : ''; ?>>Alagoas</option>
                            <option value="AP" <?php echo ($aluno['estado'] ?? '') === 'AP' ? 'selected' : ''; ?>>Amapá</option>
                            <option value="AM" <?php echo ($aluno['estado'] ?? '') === 'AM' ? 'selected' : ''; ?>>Amazonas</option>
                            <option value="BA" <?php echo ($aluno['estado'] ?? '') === 'BA' ? 'selected' : ''; ?>>Bahia</option>
                            <option value="CE" <?php echo ($aluno['estado'] ?? '') === 'CE' ? 'selected' : ''; ?>>Ceará</option>
                            <option value="DF" <?php echo ($aluno['estado'] ?? '') === 'DF' ? 'selected' : ''; ?>>Distrito Federal</option>
                            <option value="ES" <?php echo ($aluno['estado'] ?? '') === 'ES' ? 'selected' : ''; ?>>Espírito Santo</option>
                            <option value="GO" <?php echo ($aluno['estado'] ?? '') === 'GO' ? 'selected' : ''; ?>>Goiás</option>
                            <option value="MA" <?php echo ($aluno['estado'] ?? '') === 'MA' ? 'selected' : ''; ?>>Maranhão</option>
                            <option value="MT" <?php echo ($aluno['estado'] ?? '') === 'MT' ? 'selected' : ''; ?>>Mato Grosso</option>
                            <option value="MS" <?php echo ($aluno['estado'] ?? '') === 'MS' ? 'selected' : ''; ?>>Mato Grosso do Sul</option>
                            <option value="MG" <?php echo ($aluno['estado'] ?? '') === 'MG' ? 'selected' : ''; ?>>Minas Gerais</option>
                            <option value="PA" <?php echo ($aluno['estado'] ?? '') === 'PA' ? 'selected' : ''; ?>>Pará</option>
                            <option value="PB" <?php echo ($aluno['estado'] ?? '') === 'PB' ? 'selected' : ''; ?>>Paraíba</option>
                            <option value="PR" <?php echo ($aluno['estado'] ?? '') === 'PR' ? 'selected' : ''; ?>>Paraná</option>
                            <option value="PE" <?php echo ($aluno['estado'] ?? '') === 'PE' ? 'selected' : ''; ?>>Pernambuco</option>
                            <option value="PI" <?php echo ($aluno['estado'] ?? '') === 'PI' ? 'selected' : ''; ?>>Piauí</option>
                            <option value="RJ" <?php echo ($aluno['estado'] ?? '') === 'RJ' ? 'selected' : ''; ?>>Rio de Janeiro</option>
                            <option value="RN" <?php echo ($aluno['estado'] ?? '') === 'RN' ? 'selected' : ''; ?>>Rio Grande do Norte</option>
                            <option value="RS" <?php echo ($aluno['estado'] ?? '') === 'RS' ? 'selected' : ''; ?>>Rio Grande do Sul</option>
                            <option value="RO" <?php echo ($aluno['estado'] ?? '') === 'RO' ? 'selected' : ''; ?>>Rondônia</option>
                            <option value="RR" <?php echo ($aluno['estado'] ?? '') === 'RR' ? 'selected' : ''; ?>>Roraima</option>
                            <option value="SC" <?php echo ($aluno['estado'] ?? '') === 'SC' ? 'selected' : ''; ?>>Santa Catarina</option>
                            <option value="SP" <?php echo ($aluno['estado'] ?? '') === 'SP' ? 'selected' : ''; ?>>São Paulo</option>
                            <option value="SE" <?php echo ($aluno['estado'] ?? '') === 'SE' ? 'selected' : ''; ?>>Sergipe</option>
                            <option value="TO" <?php echo ($aluno['estado'] ?? '') === 'TO' ? 'selected' : ''; ?>>Tocantins</option>
                        </select>
                    </div>
                    
                    <div class="col-span-2">
                        <label for="biografia" class="block text-sm font-medium text-gray-700 mb-1">Biografia</label>
                        <textarea name="biografia" id="biografia" rows="4" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"><?php echo $aluno['biografia'] ?? ''; ?></textarea>
                        <p class="text-xs text-gray-500 mt-1">Uma breve descrição sobre você.</p>
                    </div>
                </div>
                
                <div class="mt-6 flex justify-end">
                    <button type="submit" name="atualizar_perfil" class="btn btn-primary">
                        <i class="fas fa-save mr-2"></i> Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
