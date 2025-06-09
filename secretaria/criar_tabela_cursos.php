<?php
/**
 * Script para criar a tabela de cursos
 */

// Inicializa o sistema
require_once __DIR__ . '/includes/init.php';

// Instancia o banco de dados
$db = Database::getInstance();

try {
    // Verifica se a tabela cursos existe
    $sql = "SHOW TABLES LIKE 'cursos'";
    $result = $db->fetchOne($sql);
    
    if (!$result) {
        echo "A tabela 'cursos' não existe no banco de dados.<br>";
        
        // Cria a tabela se não existir
        echo "Criando a tabela 'cursos'...<br>";
        
        $sql = "CREATE TABLE cursos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(100) NOT NULL,
            codigo VARCHAR(20) NOT NULL,
            descricao TEXT,
            carga_horaria INT DEFAULT 0,
            duracao_meses INT DEFAULT 0,
            area_id INT,
            nivel ENUM('graduacao', 'pos_graduacao', 'mestrado', 'doutorado', 'tecnico', 'extensao') NOT NULL,
            modalidade ENUM('presencial', 'ead', 'hibrido') NOT NULL,
            valor DECIMAL(10,2) DEFAULT 0.00,
            status ENUM('ativo', 'inativo') DEFAULT 'ativo',
            id_legado VARCHAR(50),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (area_id),
            INDEX (nivel),
            INDEX (modalidade),
            INDEX (status)
        )";
        
        $db->query($sql);
        
        echo "Tabela 'cursos' criada com sucesso.<br>";
        
        // Cria a tabela cursos_polos
        echo "Criando a tabela 'cursos_polos'...<br>";
        
        $sql = "CREATE TABLE IF NOT EXISTS cursos_polos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            curso_id INT NOT NULL,
            polo_id INT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY (curso_id, polo_id),
            INDEX (curso_id),
            INDEX (polo_id)
        )";
        
        $db->query($sql);
        
        echo "Tabela 'cursos_polos' criada com sucesso.<br>";
        
        // Insere alguns cursos de exemplo
        echo "Inserindo cursos de exemplo...<br>";
        
        $cursos = [
            [
                'nome' => 'Administração',
                'codigo' => 'ADM001',
                'descricao' => 'Curso de Administração de Empresas',
                'carga_horaria' => 3000,
                'duracao_meses' => 48,
                'nivel' => 'graduacao',
                'modalidade' => 'presencial',
                'valor' => 800.00,
                'status' => 'ativo'
            ],
            [
                'nome' => 'Direito',
                'codigo' => 'DIR001',
                'descricao' => 'Curso de Direito',
                'carga_horaria' => 3700,
                'duracao_meses' => 60,
                'nivel' => 'graduacao',
                'modalidade' => 'presencial',
                'valor' => 1200.00,
                'status' => 'ativo'
            ],
            [
                'nome' => 'Análise e Desenvolvimento de Sistemas',
                'codigo' => 'ADS001',
                'descricao' => 'Curso de Análise e Desenvolvimento de Sistemas',
                'carga_horaria' => 2400,
                'duracao_meses' => 30,
                'nivel' => 'graduacao',
                'modalidade' => 'ead',
                'valor' => 450.00,
                'status' => 'ativo'
            ],
            [
                'nome' => 'MBA em Gestão de Projetos',
                'codigo' => 'MBA001',
                'descricao' => 'MBA em Gestão de Projetos',
                'carga_horaria' => 360,
                'duracao_meses' => 18,
                'nivel' => 'pos_graduacao',
                'modalidade' => 'hibrido',
                'valor' => 600.00,
                'status' => 'ativo'
            ],
            [
                'nome' => 'Técnico em Enfermagem',
                'codigo' => 'TEC001',
                'descricao' => 'Curso Técnico em Enfermagem',
                'carga_horaria' => 1800,
                'duracao_meses' => 24,
                'nivel' => 'tecnico',
                'modalidade' => 'presencial',
                'valor' => 350.00,
                'status' => 'ativo'
            ]
        ];
        
        foreach ($cursos as $curso) {
            $db->insert('cursos', $curso);
            echo "Curso '{$curso['nome']}' inserido com sucesso.<br>";
        }
        
        echo "Cursos de exemplo inseridos com sucesso.<br>";
    } else {
        echo "A tabela 'cursos' já existe no banco de dados.<br>";
        
        // Conta o número de registros
        $sql = "SELECT COUNT(*) as total FROM cursos";
        $result = $db->fetchOne($sql);
        
        echo "Total de registros na tabela 'cursos': " . $result['total'] . "<br>";
        
        if ($result['total'] == 0) {
            echo "A tabela 'cursos' está vazia. Inserindo cursos de exemplo...<br>";
            
            // Insere alguns cursos de exemplo
            $cursos = [
                [
                    'nome' => 'Administração',
                    'codigo' => 'ADM001',
                    'descricao' => 'Curso de Administração de Empresas',
                    'carga_horaria' => 3000,
                    'duracao_meses' => 48,
                    'nivel' => 'graduacao',
                    'modalidade' => 'presencial',
                    'valor' => 800.00,
                    'status' => 'ativo'
                ],
                [
                    'nome' => 'Direito',
                    'codigo' => 'DIR001',
                    'descricao' => 'Curso de Direito',
                    'carga_horaria' => 3700,
                    'duracao_meses' => 60,
                    'nivel' => 'graduacao',
                    'modalidade' => 'presencial',
                    'valor' => 1200.00,
                    'status' => 'ativo'
                ],
                [
                    'nome' => 'Análise e Desenvolvimento de Sistemas',
                    'codigo' => 'ADS001',
                    'descricao' => 'Curso de Análise e Desenvolvimento de Sistemas',
                    'carga_horaria' => 2400,
                    'duracao_meses' => 30,
                    'nivel' => 'graduacao',
                    'modalidade' => 'ead',
                    'valor' => 450.00,
                    'status' => 'ativo'
                ],
                [
                    'nome' => 'MBA em Gestão de Projetos',
                    'codigo' => 'MBA001',
                    'descricao' => 'MBA em Gestão de Projetos',
                    'carga_horaria' => 360,
                    'duracao_meses' => 18,
                    'nivel' => 'pos_graduacao',
                    'modalidade' => 'hibrido',
                    'valor' => 600.00,
                    'status' => 'ativo'
                ],
                [
                    'nome' => 'Técnico em Enfermagem',
                    'codigo' => 'TEC001',
                    'descricao' => 'Curso Técnico em Enfermagem',
                    'carga_horaria' => 1800,
                    'duracao_meses' => 24,
                    'nivel' => 'tecnico',
                    'modalidade' => 'presencial',
                    'valor' => 350.00,
                    'status' => 'ativo'
                ]
            ];
            
            foreach ($cursos as $curso) {
                $db->insert('cursos', $curso);
                echo "Curso '{$curso['nome']}' inserido com sucesso.<br>";
            }
            
            echo "Cursos de exemplo inseridos com sucesso.<br>";
        }
    }
    
    // Verifica se a tabela cursos_polos existe
    $sql = "SHOW TABLES LIKE 'cursos_polos'";
    $result = $db->fetchOne($sql);
    
    if (!$result) {
        echo "A tabela 'cursos_polos' não existe no banco de dados.<br>";
        
        // Cria a tabela se não existir
        echo "Criando a tabela 'cursos_polos'...<br>";
        
        $sql = "CREATE TABLE cursos_polos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            curso_id INT NOT NULL,
            polo_id INT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY (curso_id, polo_id),
            INDEX (curso_id),
            INDEX (polo_id)
        )";
        
        $db->query($sql);
        
        echo "Tabela 'cursos_polos' criada com sucesso.<br>";
    } else {
        echo "A tabela 'cursos_polos' já existe no banco de dados.<br>";
    }
    
    // Verifica se a tabela polos existe
    $sql = "SHOW TABLES LIKE 'polos'";
    $result = $db->fetchOne($sql);
    
    if (!$result) {
        echo "A tabela 'polos' não existe no banco de dados.<br>";
        
        // Cria a tabela se não existir
        echo "Criando a tabela 'polos'...<br>";
        
        $sql = "CREATE TABLE polos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(100) NOT NULL,
            codigo VARCHAR(20) NOT NULL,
            endereco TEXT,
            cidade VARCHAR(100),
            estado VARCHAR(2),
            cep VARCHAR(10),
            telefone VARCHAR(20),
            email VARCHAR(100),
            responsavel VARCHAR(100),
            status ENUM('ativo', 'inativo') DEFAULT 'ativo',
            id_legado VARCHAR(50),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (status)
        )";
        
        $db->query($sql);
        
        echo "Tabela 'polos' criada com sucesso.<br>";
        
        // Insere alguns polos de exemplo
        echo "Inserindo polos de exemplo...<br>";
        
        $polos = [
            [
                'nome' => 'Polo Central',
                'codigo' => 'POL001',
                'cidade' => 'São Paulo',
                'estado' => 'SP',
                'status' => 'ativo'
            ],
            [
                'nome' => 'Polo Norte',
                'codigo' => 'POL002',
                'cidade' => 'Manaus',
                'estado' => 'AM',
                'status' => 'ativo'
            ],
            [
                'nome' => 'Polo Sul',
                'codigo' => 'POL003',
                'cidade' => 'Porto Alegre',
                'estado' => 'RS',
                'status' => 'ativo'
            ],
            [
                'nome' => 'Polo Leste',
                'codigo' => 'POL004',
                'cidade' => 'Recife',
                'estado' => 'PE',
                'status' => 'ativo'
            ],
            [
                'nome' => 'Polo Oeste',
                'codigo' => 'POL005',
                'cidade' => 'Cuiabá',
                'estado' => 'MT',
                'status' => 'ativo'
            ]
        ];
        
        foreach ($polos as $polo) {
            $db->insert('polos', $polo);
            echo "Polo '{$polo['nome']}' inserido com sucesso.<br>";
        }
        
        echo "Polos de exemplo inseridos com sucesso.<br>";
    } else {
        echo "A tabela 'polos' já existe no banco de dados.<br>";
    }
    
    echo "<br><a href='cursos.php' class='btn-primary'>Voltar para a página de cursos</a>";
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
