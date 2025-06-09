<?php
/**
 * Inicialização do AVA
 *
 * Este arquivo verifica se as tabelas do AVA existem e as cria se necessário
 */

// Função para verificar se uma tabela existe
function tabelaExiste($tabela) {
    $db = Database::getInstance();
    $resultado = $db->fetchOne("SHOW TABLES LIKE ?", [$tabela]);
    return !empty($resultado);
}

// Função para executar um script SQL
function executarSQL($sql) {
    $db = Database::getInstance();
    try {
        // Divide o script em comandos individuais
        $comandos = explode(';', $sql);

        foreach ($comandos as $comando) {
            $comando = trim($comando);
            if (!empty($comando)) {
                try {
                    $db->query($comando);
                } catch (Exception $e) {
                    // Log detalhado do erro
                    error_log('Erro ao executar comando SQL: ' . $comando);
                    error_log('Mensagem de erro: ' . $e->getMessage());

                    // Se o erro não for sobre tabela já existente, propaga o erro
                    if (strpos($e->getMessage(), 'already exists') === false) {
                        throw $e;
                    }
                }
            }
        }

        return true;
    } catch (Exception $e) {
        error_log('Erro ao executar SQL: ' . $e->getMessage());
        return false;
    }
}

// Verifica se as tabelas do AVA existem e as cria se necessário
function inicializarTabelasAVA() {
    // Lista de tabelas do AVA
    $tabelas = [
        'ava_cursos',
        'ava_alunos',
        'ava_polos_acesso',
        'ava_polos_cursos',
        'ava_matriculas',
        'ava_acessos'
    ];

    // Verifica se todas as tabelas existem
    $todas_existem = true;
    $tabelas_faltantes = [];

    foreach ($tabelas as $tabela) {
        if (!tabelaExiste($tabela)) {
            $todas_existem = false;
            $tabelas_faltantes[] = $tabela;
        }
    }

    // Se todas as tabelas existem, não faz nada
    if ($todas_existem) {
        return true;
    }

    // Se alguma tabela não existe, cria todas as tabelas
    $sql = file_get_contents(dirname(__DIR__) . '/ava_tables.sql');

    // Executa o script SQL
    $resultado = executarSQL($sql);

    if ($resultado) {
        // Registra as tabelas criadas
        $mensagem = 'Tabelas do AVA criadas com sucesso: ' . implode(', ', $tabelas_faltantes);
        registrarLog('sistema', 'criar_tabelas', $mensagem);
        return true;
    } else {
        // Registra o erro
        $mensagem = 'Erro ao criar tabelas do AVA: ' . implode(', ', $tabelas_faltantes);
        registrarLog('sistema', 'erro', $mensagem);
        return false;
    }
}

// Inicializa as tabelas do AVA
inicializarTabelasAVA();
