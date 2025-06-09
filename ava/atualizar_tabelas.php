 <?php
/**
 * Script para atualizar as tabelas do AVA
 * Este script adiciona colunas faltantes às tabelas existentes
 */

// Inicializa o sistema
require_once '../includes/init.php';

// Verifica se o usuário está autenticado e é administrador
exigirLogin();
if (getUsuarioTipo() !== 'admin') {
    setMensagem('erro', 'Você não tem permissão para acessar esta página.');
    redirect('../index.php');
    exit;
}

// Instancia o banco de dados
$db = Database::getInstance();

try {
    echo "<h1>Atualização de Tabelas do AVA</h1>";
    
    // Verifica se a tabela ava_cursos existe
    $sql = "SHOW TABLES LIKE 'ava_cursos'";
    $tabela_existe = $db->fetchOne($sql);
    
    if (!$tabela_existe) {
        echo "<p style='color: red;'>A tabela ava_cursos não existe. Execute o script de criação de tabelas primeiro.</p>";
    } else {
        echo "<p style='color: green;'>A tabela ava_cursos existe.</p>";
        
        // Verifica as colunas da tabela ava_cursos
        $sql = "DESCRIBE ava_cursos";
        $colunas = $db->fetchAll($sql);
        
        $colunas_existentes = [];
        foreach ($colunas as $coluna) {
            $colunas_existentes[] = $coluna['Field'];
        }
        
        echo "<p>Colunas existentes: " . implode(', ', $colunas_existentes) . "</p>";
        
        // Lista de colunas que devem existir na tabela ava_cursos
        $colunas_necessarias = [
            'id', 'polo_id', 'titulo', 'descricao', 'categoria', 'carga_horaria', 
            'status', 'imagem', 'preco', 'preco_promocional', 'data_inicio', 'data_fim', 
            'requisitos', 'publico_alvo', 'objetivos', 'metodologia', 'avaliacao', 
            'certificacao', 'destaque', 'visibilidade', 'created_at', 'updated_at'
        ];
        
        // Verifica quais colunas estão faltando
        $colunas_faltantes = array_diff($colunas_necessarias, $colunas_existentes);
        
        if (empty($colunas_faltantes)) {
            echo "<p style='color: green;'>Todas as colunas necessárias já existem na tabela ava_cursos.</p>";
        } else {
            echo "<p>Colunas faltantes: " . implode(', ', $colunas_faltantes) . "</p>";
            
            // Adiciona as colunas faltantes
            foreach ($colunas_faltantes as $coluna) {
                $sql = "";
                
                switch ($coluna) {
                    case 'preco':
                        $sql = "ALTER TABLE ava_cursos ADD COLUMN preco DECIMAL(10,2) NULL AFTER imagem";
                        break;
                    case 'preco_promocional':
                        $sql = "ALTER TABLE ava_cursos ADD COLUMN preco_promocional DECIMAL(10,2) NULL AFTER preco";
                        break;
                    case 'data_inicio':
                        $sql = "ALTER TABLE ava_cursos ADD COLUMN data_inicio DATE NULL AFTER preco_promocional";
                        break;
                    case 'data_fim':
                        $sql = "ALTER TABLE ava_cursos ADD COLUMN data_fim DATE NULL AFTER data_inicio";
                        break;
                    case 'requisitos':
                        $sql = "ALTER TABLE ava_cursos ADD COLUMN requisitos TEXT NULL AFTER data_fim";
                        break;
                    case 'publico_alvo':
                        $sql = "ALTER TABLE ava_cursos ADD COLUMN publico_alvo TEXT NULL AFTER requisitos";
                        break;
                    case 'objetivos':
                        $sql = "ALTER TABLE ava_cursos ADD COLUMN objetivos TEXT NULL AFTER publico_alvo";
                        break;
                    case 'metodologia':
                        $sql = "ALTER TABLE ava_cursos ADD COLUMN metodologia TEXT NULL AFTER objetivos";
                        break;
                    case 'avaliacao':
                        $sql = "ALTER TABLE ava_cursos ADD COLUMN avaliacao TEXT NULL AFTER metodologia";
                        break;
                    case 'certificacao':
                        $sql = "ALTER TABLE ava_cursos ADD COLUMN certificacao TEXT NULL AFTER avaliacao";
                        break;
                    case 'destaque':
                        $sql = "ALTER TABLE ava_cursos ADD COLUMN destaque TINYINT(1) NOT NULL DEFAULT 0 AFTER certificacao";
                        break;
                    case 'visibilidade':
                        $sql = "ALTER TABLE ava_cursos ADD COLUMN visibilidade ENUM('publico', 'privado') NOT NULL DEFAULT 'publico' AFTER destaque";
                        break;
                    default:
                        // Para outras colunas que possam estar faltando
                        $sql = "ALTER TABLE ava_cursos ADD COLUMN $coluna VARCHAR(255) NULL";
                        break;
                }
                
                if (!empty($sql)) {
                    try {
                        $db->query($sql);
                        echo "<p style='color: green;'>Coluna $coluna adicionada com sucesso!</p>";
                    } catch (Exception $e) {
                        echo "<p style='color: red;'>Erro ao adicionar coluna $coluna: " . $e->getMessage() . "</p>";
                    }
                }
            }
        }
    }
    
    // Verifica se a tabela ava_matriculas existe
    $sql = "SHOW TABLES LIKE 'ava_matriculas'";
    $tabela_existe = $db->fetchOne($sql);
    
    if (!$tabela_existe) {
        echo "<p style='color: red;'>A tabela ava_matriculas não existe. Execute o script de criação de tabelas primeiro.</p>";
    } else {
        echo "<p style='color: green;'>A tabela ava_matriculas existe.</p>";
        
        // Verifica se a coluna data_matricula existe
        $sql = "SHOW COLUMNS FROM ava_matriculas LIKE 'data_matricula'";
        $coluna_existe = $db->fetchOne($sql);
        
        if (!$coluna_existe) {
            echo "<p>A coluna data_matricula não existe na tabela ava_matriculas. Adicionando coluna...</p>";
            
            try {
                $sql = "ALTER TABLE ava_matriculas ADD COLUMN data_matricula DATETIME NULL AFTER status";
                $db->query($sql);
                echo "<p style='color: green;'>Coluna data_matricula adicionada com sucesso!</p>";
                
                // Atualiza os registros existentes para definir data_matricula = created_at
                $sql = "UPDATE ava_matriculas SET data_matricula = created_at WHERE data_matricula IS NULL";
                $db->query($sql);
                echo "<p style='color: green;'>Registros existentes atualizados com data_matricula = created_at.</p>";
            } catch (Exception $e) {
                echo "<p style='color: red;'>Erro ao adicionar coluna data_matricula: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p style='color: green;'>A coluna data_matricula já existe na tabela ava_matriculas.</p>";
        }
    }
    
    // Verifica se a tabela ava_categorias existe
    $sql = "SHOW TABLES LIKE 'ava_categorias'";
    $tabela_existe = $db->fetchOne($sql);
    
    if (!$tabela_existe) {
        echo "<p style='color: red;'>A tabela ava_categorias não existe. Execute o script de criação de tabelas primeiro.</p>";
    } else {
        echo "<p style='color: green;'>A tabela ava_categorias existe.</p>";
        
        // Verifica se existem categorias na tabela
        $sql = "SELECT COUNT(*) as total FROM ava_categorias";
        $resultado = $db->fetchOne($sql);
        
        if ($resultado['total'] == 0) {
            echo "<p>Não existem categorias cadastradas. Adicionando categorias padrão...</p>";
            
            // Insere categorias padrão
            $categorias_padrao = [
                ['nome' => 'Tecnologia', 'descricao' => 'Cursos de tecnologia e informática', 'cor' => '#3B82F6'],
                ['nome' => 'Saúde', 'descricao' => 'Cursos da área de saúde', 'cor' => '#10B981'],
                ['nome' => 'Educação', 'descricao' => 'Cursos para educadores', 'cor' => '#F59E0B'],
                ['nome' => 'Negócios', 'descricao' => 'Cursos de administração e negócios', 'cor' => '#6366F1'],
                ['nome' => 'Idiomas', 'descricao' => 'Cursos de idiomas', 'cor' => '#EC4899']
            ];
            
            foreach ($categorias_padrao as $categoria) {
                $dados = [
                    'nome' => $categoria['nome'],
                    'descricao' => $categoria['descricao'],
                    'cor' => $categoria['cor'],
                    'status' => 'ativo',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                try {
                    $db->insert('ava_categorias', $dados);
                    echo "<p style='color: green;'>Categoria {$categoria['nome']} adicionada com sucesso!</p>";
                } catch (Exception $e) {
                    echo "<p style='color: red;'>Erro ao adicionar categoria {$categoria['nome']}: " . $e->getMessage() . "</p>";
                }
            }
        } else {
            echo "<p style='color: green;'>Já existem categorias cadastradas.</p>";
        }
    }
    
    // Verifica se a pasta de uploads existe
    $diretorio_upload = '../uploads/ava/cursos/';
    if (!file_exists($diretorio_upload)) {
        echo "<p>O diretório de upload não existe. Criando diretório...</p>";
        
        if (mkdir($diretorio_upload, 0755, true)) {
            echo "<p style='color: green;'>Diretório de upload criado com sucesso!</p>";
        } else {
            echo "<p style='color: red;'>Erro ao criar diretório de upload.</p>";
        }
    } else {
        echo "<p style='color: green;'>O diretório de upload já existe.</p>";
    }
    
    echo "<h2>Conclusão</h2>";
    echo "<p style='color: green;'>Atualização das tabelas concluída com sucesso!</p>";
    echo "<p><a href='../index.php'>Voltar para a página inicial</a></p>";
    
} catch (Exception $e) {
    echo "<h2>Erro</h2>";
    echo "<p style='color: red;'>Ocorreu um erro durante a atualização das tabelas: " . $e->getMessage() . "</p>";
    echo "<p><a href='../index.php'>Voltar para a página inicial</a></p>";
}
?>
