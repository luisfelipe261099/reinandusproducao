<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';

// Verifica se o usuário está logado
if (!Auth::check()) {
    setMensagem('erro', 'Você precisa estar logado para acessar esta página.');
    redirect('../login.php');
    exit;
}

$titulo_pagina_completo = 'Faciência ERP - Teste Completo de Funcionalidades';

// Conecta ao banco
$db = new Database();

// Testa as funcionalidades
$resultados = [];

try {
    // 1. Teste de conexão com banco
    $resultados[] = [
        'categoria' => 'Banco de Dados',
        'teste' => 'Conexão',
        'status' => 'sucesso',
        'mensagem' => 'Conexão com banco de dados estabelecida'
    ];
    
    // 2. Testa se tabelas existem
    $tabelas_necessarias = ['boletos', 'alunos', 'polos', 'usuarios'];
    foreach ($tabelas_necessarias as $tabela) {
        try {
            $db->query("SELECT 1 FROM $tabela LIMIT 1");
            $resultados[] = [
                'categoria' => 'Estrutura',
                'teste' => "Tabela $tabela",
                'status' => 'sucesso',
                'mensagem' => "Tabela $tabela existe e está acessível"
            ];
        } catch (Exception $e) {
            $resultados[] = [
                'categoria' => 'Estrutura',
                'teste' => "Tabela $tabela",
                'status' => 'erro',
                'mensagem' => "Problema com tabela $tabela: " . $e->getMessage()
            ];
        }
    }
    
    // 3. Testa diretórios
    $diretorios = [
        __DIR__ . '/../uploads/boletos' => 'Diretório de boletos',
        __DIR__ . '/../ajax' => 'Diretório AJAX',
        __DIR__ . '/../js' => 'Diretório JavaScript',
        __DIR__ . '/../includes' => 'Diretório includes'
    ];
    
    foreach ($diretorios as $dir => $nome) {
        if (is_dir($dir)) {
            $writable = is_writable($dir) ? ' (gravável)' : ' (somente leitura)';
            $resultados[] = [
                'categoria' => 'Sistema de Arquivos',
                'teste' => $nome,
                'status' => 'sucesso',
                'mensagem' => "$nome existe$writable"
            ];
        } else {
            $resultados[] = [
                'categoria' => 'Sistema de Arquivos',
                'teste' => $nome,
                'status' => 'aviso',
                'mensagem' => "$nome não existe - será criado automaticamente se necessário"
            ];
        }
    }
    
    // 4. Testa arquivos essenciais
    $arquivos = [
        __DIR__ . '/boletos.php' => 'Página de boletos',
        __DIR__ . '/ajax/excluir_boleto.php' => 'AJAX exclusão',
        __DIR__ . '/ajax/buscar_alunos.php' => 'AJAX busca alunos',
        __DIR__ . '/js/boletos.js' => 'JavaScript boletos',
        __DIR__ . '/includes/boleto_functions.php' => 'Funções de boleto',
        __DIR__ . '/includes/boleto_pdf.php' => 'Geração de PDF'
    ];
    
    foreach ($arquivos as $arquivo => $nome) {
        if (file_exists($arquivo)) {
            $resultados[] = [
                'categoria' => 'Arquivos',
                'teste' => $nome,
                'status' => 'sucesso',
                'mensagem' => "$nome existe"
            ];
        } else {
            $resultados[] = [
                'categoria' => 'Arquivos',
                'teste' => $nome,
                'status' => 'erro',
                'mensagem' => "$nome não encontrado"
            ];
        }
    }
    
    // 5. Testa DomPDF
    $dompdf_path = __DIR__ . '/../vendor/dompdf/dompdf/autoload.inc.php';
    if (file_exists($dompdf_path)) {
        require_once $dompdf_path;
        if (class_exists('Dompdf\Dompdf')) {
            $resultados[] = [
                'categoria' => 'Dependências',
                'teste' => 'DomPDF',
                'status' => 'sucesso',
                'mensagem' => 'DomPDF está instalado e disponível'
            ];
        } else {
            $resultados[] = [
                'categoria' => 'Dependências',
                'teste' => 'DomPDF',
                'status' => 'aviso',
                'mensagem' => 'DomPDF encontrado mas classe não disponível'
            ];
        }
    } else {
        $resultados[] = [
            'categoria' => 'Dependências',
            'teste' => 'DomPDF',
            'status' => 'aviso',
            'mensagem' => 'DomPDF não encontrado - PDFs serão gerados em HTML'
        ];
    }
    
    // 6. Testa permissões do usuário
    $permissoes = ['visualizar', 'inserir', 'editar', 'excluir'];
    foreach ($permissoes as $permissao) {
        $tem_permissao = Auth::hasPermission('financeiro', $permissao);
        $resultados[] = [
            'categoria' => 'Permissões',
            'teste' => "Financeiro: $permissao",
            'status' => $tem_permissao ? 'sucesso' : 'aviso',
            'mensagem' => $tem_permissao ? "Permissão $permissao: SIM" : "Permissão $permissao: NÃO"
        ];
    }
    
    // 7. Conta registros
    $count_boletos = $db->fetchOne("SELECT COUNT(*) as total FROM boletos")['total'];
    $count_alunos = $db->fetchOne("SELECT COUNT(*) as total FROM alunos")['total'];
    
    $resultados[] = [
        'categoria' => 'Dados',
        'teste' => 'Contagem de registros',
        'status' => 'info',
        'mensagem' => "Boletos: $count_boletos | Alunos: $count_alunos"
    ];
    
    // 8. Testa estrutura da tabela boletos
    $colunas_boletos = $db->query("DESCRIBE boletos");
    $colunas_esperadas = ['id', 'tipo_entidade', 'entidade_id', 'valor', 'data_vencimento', 'status'];
    $colunas_existentes = array_column($colunas_boletos, 'Field');
    
    foreach ($colunas_esperadas as $coluna) {
        if (in_array($coluna, $colunas_existentes)) {
            $resultados[] = [
                'categoria' => 'Estrutura Boletos',
                'teste' => "Coluna $coluna",
                'status' => 'sucesso',
                'mensagem' => "Coluna $coluna existe"
            ];
        } else {
            $resultados[] = [
                'categoria' => 'Estrutura Boletos',
                'teste' => "Coluna $coluna",
                'status' => 'erro',
                'mensagem' => "Coluna $coluna não encontrada"
            ];
        }
    }
    
} catch (Exception $e) {
    $resultados[] = [
        'categoria' => 'Erro Geral',
        'teste' => 'Execução de testes',
        'status' => 'erro',
        'mensagem' => 'Erro durante testes: ' . $e->getMessage()
    ];
}

// Agrupa resultados por categoria
$grupos = [];
foreach ($resultados as $resultado) {
    $grupos[$resultado['categoria']][] = $resultado;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo_pagina_completo; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white shadow-lg rounded-lg overflow-hidden">
            <div class="bg-green-600 text-white p-6">
                <h1 class="text-3xl font-bold flex items-center">
                    <i class="fas fa-check-circle mr-3"></i>
                    Teste Completo de Funcionalidades
                </h1>
                <p class="text-green-100 mt-2">Verificação de sistema, banco de dados e funcionalidades</p>
            </div>
            
            <div class="p-6">
                <!-- Resumo -->
                <?php
                $total_testes = count($resultados);
                $sucessos = count(array_filter($resultados, fn($r) => $r['status'] === 'sucesso'));
                $avisos = count(array_filter($resultados, fn($r) => $r['status'] === 'aviso'));
                $erros = count(array_filter($resultados, fn($r) => $r['status'] === 'erro'));
                ?>
                
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-center">
                        <div class="text-2xl font-bold text-blue-600"><?php echo $total_testes; ?></div>
                        <div class="text-blue-800">Total de Testes</div>
                    </div>
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center">
                        <div class="text-2xl font-bold text-green-600"><?php echo $sucessos; ?></div>
                        <div class="text-green-800">Sucessos</div>
                    </div>
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-center">
                        <div class="text-2xl font-bold text-yellow-600"><?php echo $avisos; ?></div>
                        <div class="text-yellow-800">Avisos</div>
                    </div>
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-center">
                        <div class="text-2xl font-bold text-red-600"><?php echo $erros; ?></div>
                        <div class="text-red-800">Erros</div>
                    </div>
                </div>
                
                <!-- Resultados por categoria -->
                <?php foreach ($grupos as $categoria => $testes): ?>
                <div class="mb-8">
                    <h2 class="text-xl font-bold text-gray-800 mb-4 border-b-2 border-gray-200 pb-2">
                        <?php echo $categoria; ?>
                    </h2>
                    <div class="space-y-2">
                        <?php foreach ($testes as $teste): ?>
                        <div class="flex items-center justify-between p-3 rounded-lg border
                            <?php
                            switch($teste['status']) {
                                case 'sucesso': echo 'bg-green-50 border-green-200'; break;
                                case 'aviso': echo 'bg-yellow-50 border-yellow-200'; break;
                                case 'erro': echo 'bg-red-50 border-red-200'; break;
                                default: echo 'bg-gray-50 border-gray-200';
                            }
                            ?>">
                            <div class="flex items-center">
                                <i class="fas fa-<?php
                                    switch($teste['status']) {
                                        case 'sucesso': echo 'check-circle text-green-600'; break;
                                        case 'aviso': echo 'exclamation-triangle text-yellow-600'; break;
                                        case 'erro': echo 'times-circle text-red-600'; break;
                                        default: echo 'info-circle text-gray-600';
                                    }
                                ?> mr-3"></i>
                                <div>
                                    <div class="font-medium"><?php echo $teste['teste']; ?></div>
                                    <div class="text-sm text-gray-600"><?php echo $teste['mensagem']; ?></div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <!-- Botões de ação -->
                <div class="flex gap-4 mt-8">
                    <a href="boletos.php" class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 flex items-center">
                        <i class="fas fa-file-invoice-dollar mr-2"></i>
                        Ir para Boletos
                    </a>
                    <button onclick="window.location.reload()" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 flex items-center">
                        <i class="fas fa-sync-alt mr-2"></i>
                        Executar Novamente
                    </button>
                    <a href="../secretaria/alunos.php" class="bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 flex items-center">
                        <i class="fas fa-user-graduate mr-2"></i>
                        Importar Alunos
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
