<?php
// Inicializa a sessão
session_start();

// Inclui os arquivos necessários
require_once 'includes/init.php';
require_once 'includes/auth.php';

// Verifica se o usuário está autenticado
if (!isLoggedIn()) {
    redirect('login.php');
}

// Verifica se o usuário tem permissão de administrador
if (!hasPermission('admin')) {
    setMensagem('erro', 'Você não tem permissão para acessar esta página.');
    redirect('index.php');
}

// Obtém a instância do banco de dados
$db = Database::getInstance();

// Verifica se a tabela já existe
try {
    $result = $db->fetchOne("SHOW TABLES LIKE 'professores'");
    
    if (!$result) {
        echo "A tabela 'professores' não existe no banco de dados.<br>";
        
        // Cria a tabela se não existir
        echo "Criando a tabela 'professores'...<br>";
        
        $sql = "CREATE TABLE professores (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(150) NOT NULL,
            email VARCHAR(100),
            cpf VARCHAR(20),
            telefone VARCHAR(20),
            formacao VARCHAR(100),
            titulacao ENUM('graduacao', 'especializacao', 'mestrado', 'doutorado', 'pos_doutorado'),
            area_atuacao VARCHAR(100),
            lattes_url VARCHAR(255),
            bio TEXT,
            foto VARCHAR(255),
            status ENUM('ativo', 'inativo') DEFAULT 'ativo',
            id_legado VARCHAR(50),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (status)
        )";
        
        $db->query($sql);
        echo "Tabela 'professores' criada com sucesso!<br>";
        
        // Insere alguns professores de exemplo
        echo "Inserindo professores de exemplo...<br>";
        
        $professores = [
            [
                'nome' => 'Prof. João Silva',
                'email' => 'joao.silva@exemplo.com',
                'cpf' => '123.456.789-00',
                'telefone' => '(11) 98765-4321',
                'formacao' => 'Ciência da Computação',
                'titulacao' => 'doutorado',
                'area_atuacao' => 'Inteligência Artificial',
                'status' => 'ativo'
            ],
            [
                'nome' => 'Profa. Maria Santos',
                'email' => 'maria.santos@exemplo.com',
                'cpf' => '987.654.321-00',
                'telefone' => '(11) 91234-5678',
                'formacao' => 'Matemática',
                'titulacao' => 'mestrado',
                'area_atuacao' => 'Estatística',
                'status' => 'ativo'
            ],
            [
                'nome' => 'Prof. Carlos Oliveira',
                'email' => 'carlos.oliveira@exemplo.com',
                'cpf' => '456.789.123-00',
                'telefone' => '(11) 95555-4444',
                'formacao' => 'Engenharia',
                'titulacao' => 'doutorado',
                'area_atuacao' => 'Robótica',
                'status' => 'ativo'
            ],
            [
                'nome' => 'Profa. Ana Pereira',
                'email' => 'ana.pereira@exemplo.com',
                'cpf' => '789.123.456-00',
                'telefone' => '(11) 94444-3333',
                'formacao' => 'Física',
                'titulacao' => 'pos_doutorado',
                'area_atuacao' => 'Física Quântica',
                'status' => 'ativo'
            ],
            [
                'nome' => 'Prof. Roberto Almeida',
                'email' => 'roberto.almeida@exemplo.com',
                'cpf' => '321.654.987-00',
                'telefone' => '(11) 93333-2222',
                'formacao' => 'Biologia',
                'titulacao' => 'mestrado',
                'area_atuacao' => 'Genética',
                'status' => 'ativo'
            ]
        ];
        
        foreach ($professores as $professor) {
            $db->insert('professores', $professor);
            echo "Professor '{$professor['nome']}' inserido.<br>";
        }
        
        echo "<br>Processo concluído com sucesso!<br>";
        echo "<a href='index.php'>Voltar para a página inicial</a>";
    } else {
        echo "A tabela 'professores' já existe no banco de dados.<br>";
        echo "<a href='index.php'>Voltar para a página inicial</a>";
    }
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "<br>";
    echo "<a href='index.php'>Voltar para a página inicial</a>";
}
?>
