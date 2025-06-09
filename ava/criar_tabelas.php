<?php
/**
 * Script para criar as tabelas necessárias para o AVA
 * Este script deve ser executado uma vez para garantir que todas as tabelas necessárias existam
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
    echo "<h1>Criação de Tabelas do AVA</h1>";

    // Array com as tabelas a serem criadas
    $tabelas = [
        'ava_polos_acesso' => "CREATE TABLE IF NOT EXISTS ava_polos_acesso (
            id INT(11) NOT NULL AUTO_INCREMENT,
            polo_id INT(11) NOT NULL,
            liberado TINYINT(1) NOT NULL DEFAULT 0,
            data_liberacao DATETIME NULL,
            liberado_por INT(11) NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY idx_polo_id (polo_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'ava_categorias' => "CREATE TABLE IF NOT EXISTS ava_categorias (
            id INT(11) NOT NULL AUTO_INCREMENT,
            nome VARCHAR(100) NOT NULL,
            descricao TEXT NULL,
            cor VARCHAR(20) NOT NULL DEFAULT '#6A5ACD',
            status ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY idx_nome (nome)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'ava_cursos' => "CREATE TABLE IF NOT EXISTS ava_cursos (
            id INT(11) NOT NULL AUTO_INCREMENT,
            polo_id INT(11) NOT NULL,
            titulo VARCHAR(255) NOT NULL,
            descricao TEXT NULL,
            categoria VARCHAR(100) NULL,
            carga_horaria INT(11) NULL,
            status ENUM('rascunho', 'revisao', 'publicado', 'arquivado') NOT NULL DEFAULT 'rascunho',
            imagem VARCHAR(255) NULL,
            preco DECIMAL(10,2) NULL,
            preco_promocional DECIMAL(10,2) NULL,
            data_inicio DATE NULL,
            data_fim DATE NULL,
            requisitos TEXT NULL,
            publico_alvo TEXT NULL,
            objetivos TEXT NULL,
            metodologia TEXT NULL,
            avaliacao TEXT NULL,
            certificacao TEXT NULL,
            destaque TINYINT(1) NOT NULL DEFAULT 0,
            visibilidade ENUM('publico', 'privado') NOT NULL DEFAULT 'publico',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY idx_polo_id (polo_id),
            KEY idx_categoria (categoria),
            KEY idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'ava_modulos' => "CREATE TABLE IF NOT EXISTS ava_modulos (
            id INT(11) NOT NULL AUTO_INCREMENT,
            curso_id INT(11) NOT NULL,
            titulo VARCHAR(255) NOT NULL,
            descricao TEXT NULL,
            ordem INT(11) NOT NULL DEFAULT 0,
            status ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY idx_curso_id (curso_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'ava_aulas' => "CREATE TABLE IF NOT EXISTS ava_aulas (
            id INT(11) NOT NULL AUTO_INCREMENT,
            modulo_id INT(11) NOT NULL,
            titulo VARCHAR(255) NOT NULL,
            descricao TEXT NULL,
            tipo ENUM('video', 'texto', 'arquivo', 'quiz') NOT NULL DEFAULT 'texto',
            conteudo TEXT NULL,
            url_video VARCHAR(255) NULL,
            arquivo VARCHAR(255) NULL,
            duracao INT(11) NULL,
            ordem INT(11) NOT NULL DEFAULT 0,
            status ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY idx_modulo_id (modulo_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'ava_matriculas' => "CREATE TABLE IF NOT EXISTS ava_matriculas (
            id INT(11) NOT NULL AUTO_INCREMENT,
            aluno_id INT(11) NOT NULL,
            curso_id INT(11) NOT NULL,
            status ENUM('ativo', 'inativo', 'pendente', 'concluido') NOT NULL DEFAULT 'ativo',
            data_matricula DATETIME NULL,
            data_inicio DATE NULL,
            data_fim DATE NULL,
            nota DECIMAL(5,2) NULL,
            observacoes TEXT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY idx_aluno_curso (aluno_id, curso_id),
            KEY idx_aluno_id (aluno_id),
            KEY idx_curso_id (curso_id),
            KEY idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'ava_progresso' => "CREATE TABLE IF NOT EXISTS ava_progresso (
            id INT(11) NOT NULL AUTO_INCREMENT,
            matricula_id INT(11) NOT NULL,
            aula_id INT(11) NOT NULL,
            concluido TINYINT(1) NOT NULL DEFAULT 0,
            data_conclusao DATETIME NULL,
            tempo_gasto INT(11) NULL,
            nota DECIMAL(5,2) NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY idx_matricula_aula (matricula_id, aula_id),
            KEY idx_matricula_id (matricula_id),
            KEY idx_aula_id (aula_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'ava_certificados' => "CREATE TABLE IF NOT EXISTS ava_certificados (
            id INT(11) NOT NULL AUTO_INCREMENT,
            matricula_id INT(11) NOT NULL,
            codigo VARCHAR(50) NOT NULL,
            data_emissao DATETIME NOT NULL,
            data_validade DATE NULL,
            arquivo VARCHAR(255) NULL,
            status ENUM('emitido', 'revogado') NOT NULL DEFAULT 'emitido',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY idx_matricula_id (matricula_id),
            UNIQUE KEY idx_codigo (codigo)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    ];

    // Cria as tabelas
    foreach ($tabelas as $nome => $sql) {
        echo "<h2>Tabela: $nome</h2>";

        // Verifica se a tabela já existe
        $sql_check = "SHOW TABLES LIKE '$nome'";
        $tabela_existe = $db->fetchOne($sql_check);

        if ($tabela_existe) {
            echo "<p style='color: green;'>A tabela $nome já existe.</p>";
        } else {
            // Cria a tabela
            $db->query($sql);
            echo "<p style='color: green;'>Tabela $nome criada com sucesso!</p>";
        }
    }

    // Insere categorias padrão se não existirem
    $categorias_padrao = [
        ['nome' => 'Tecnologia', 'descricao' => 'Cursos de tecnologia e informática', 'cor' => '#3B82F6'],
        ['nome' => 'Saúde', 'descricao' => 'Cursos da área de saúde', 'cor' => '#10B981'],
        ['nome' => 'Educação', 'descricao' => 'Cursos para educadores', 'cor' => '#F59E0B'],
        ['nome' => 'Negócios', 'descricao' => 'Cursos de administração e negócios', 'cor' => '#6366F1'],
        ['nome' => 'Idiomas', 'descricao' => 'Cursos de idiomas', 'cor' => '#EC4899']
    ];

    echo "<h2>Categorias Padrão</h2>";

    foreach ($categorias_padrao as $categoria) {
        $sql = "SELECT id FROM ava_categorias WHERE nome = ?";
        $categoria_existe = $db->fetchOne($sql, [$categoria['nome']]);

        if (!$categoria_existe) {
            $dados = [
                'nome' => $categoria['nome'],
                'descricao' => $categoria['descricao'],
                'cor' => $categoria['cor'],
                'status' => 'ativo',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $db->insert('ava_categorias', $dados);
            echo "<p style='color: green;'>Categoria {$categoria['nome']} criada com sucesso!</p>";
        } else {
            echo "<p>Categoria {$categoria['nome']} já existe.</p>";
        }
    }

    // Verifica se existem polos sem acesso ao AVA
    $sql = "SELECT p.id, p.nome
            FROM polos p
            LEFT JOIN ava_polos_acesso apa ON p.id = apa.polo_id
            WHERE apa.id IS NULL";
    $polos_sem_acesso = $db->fetchAll($sql);

    if (!empty($polos_sem_acesso)) {
        echo "<h2>Polos sem Acesso ao AVA</h2>";
        echo "<p>Os seguintes polos não possuem registro de acesso ao AVA:</p>";
        echo "<ul>";

        foreach ($polos_sem_acesso as $polo) {
            echo "<li>{$polo['nome']} (ID: {$polo['id']})</li>";

            // Cria um registro de acesso para o polo (inicialmente não liberado)
            $dados = [
                'polo_id' => $polo['id'],
                'liberado' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $db->insert('ava_polos_acesso', $dados);
        }

        echo "</ul>";
        echo "<p style='color: green;'>Registros de acesso criados com sucesso!</p>";
    } else {
        echo "<h2>Polos sem Acesso ao AVA</h2>";
        echo "<p>Todos os polos já possuem registro de acesso ao AVA.</p>";
    }

    echo "<h2>Conclusão</h2>";
    echo "<p style='color: green;'>Todas as tabelas necessárias para o AVA foram criadas com sucesso!</p>";
    echo "<p><a href='../index.php'>Voltar para a página inicial</a></p>";

} catch (Exception $e) {
    echo "<h2>Erro</h2>";
    echo "<p style='color: red;'>Ocorreu um erro durante a criação das tabelas: " . $e->getMessage() . "</p>";
    echo "<p><a href='../index.php'>Voltar para a página inicial</a></p>";
}
?>
