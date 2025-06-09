<?php
/**
 * Funções auxiliares para gerenciar configurações do sistema
 */

/**
 * Obtém uma configuração do banco de dados
 * 
 * @param string $chave Chave da configuração
 * @param mixed $valor_padrao Valor padrão caso a configuração não exista
 * @param object $db Objeto de conexão com o banco de dados (opcional)
 * @return mixed Valor da configuração ou valor padrão
 */
function obterConfiguracao($chave, $valor_padrao = null, $db = null) {
    try {
        // Se não foi passado um objeto de conexão, cria um
        if ($db === null) {
            $db = Database::getInstance();
        }
        
        // Verifica se a tabela existe
        try {
            $tabela_existe = $db->fetchOne("SHOW TABLES LIKE 'configuracoes'");
            if (!$tabela_existe) {
                error_log("Tabela de configurações não existe. Retornando valor padrão para $chave");
                return $valor_padrao;
            }
        } catch (Exception $e) {
            error_log("Erro ao verificar se a tabela de configurações existe: " . $e->getMessage());
            return $valor_padrao;
        }
        
        // Busca a configuração
        $config = $db->fetchOne("SELECT valor FROM configuracoes WHERE chave = ?", [$chave]);
        
        if ($config) {
            return $config['valor'];
        } else {
            return $valor_padrao;
        }
    } catch (Exception $e) {
        error_log("Erro ao obter configuração $chave: " . $e->getMessage());
        return $valor_padrao;
    }
}

/**
 * Salva uma configuração no banco de dados
 * 
 * @param string $chave Chave da configuração
 * @param mixed $valor Valor da configuração
 * @param string $descricao Descrição da configuração (opcional)
 * @param object $db Objeto de conexão com o banco de dados (opcional)
 * @return bool True se a operação foi bem-sucedida, false caso contrário
 */
function salvarConfiguracao($chave, $valor, $descricao = null, $db = null) {
    try {
        // Se não foi passado um objeto de conexão, cria um
        if ($db === null) {
            $db = Database::getInstance();
        }
        
        // Verifica se a tabela existe
        try {
            $tabela_existe = $db->fetchOne("SHOW TABLES LIKE 'configuracoes'");
            if (!$tabela_existe) {
                error_log("Tabela de configurações não existe. Criando...");
                
                // Cria a tabela
                $sql = "CREATE TABLE IF NOT EXISTS `configuracoes` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `chave` varchar(100) NOT NULL,
                    `valor` text NOT NULL,
                    `descricao` text,
                    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `chave_unique` (`chave`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
                
                $db->query($sql);
            }
        } catch (Exception $e) {
            error_log("Erro ao verificar/criar tabela de configurações: " . $e->getMessage());
            return false;
        }
        
        // Verifica se a configuração já existe
        $config = $db->fetchOne("SELECT id FROM configuracoes WHERE chave = ?", [$chave]);
        
        if ($config) {
            // Atualiza a configuração existente
            $result = $db->update('configuracoes', [
                'valor' => $valor,
                'descricao' => $descricao !== null ? $descricao : $db->raw('descricao'),
                'updated_at' => date('Y-m-d H:i:s')
            ], 'chave = ?', [$chave]);
        } else {
            // Insere uma nova configuração
            $result = $db->insert('configuracoes', [
                'chave' => $chave,
                'valor' => $valor,
                'descricao' => $descricao,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }
        
        return $result !== false;
    } catch (Exception $e) {
        error_log("Erro ao salvar configuração $chave: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtém a URL da API do Itaú com base no ambiente configurado
 * 
 * @param string $tipo Tipo da API (cash_management ou cobranca)
 * @param object $db Objeto de conexão com o banco de dados (opcional)
 * @return string URL da API
 */
function obterUrlApiItau($tipo = 'cash_management', $db = null) {
    // Se não foi passado um objeto de conexão, cria um
    if ($db === null) {
        $db = Database::getInstance();
    }
    
    // Obtém o ambiente configurado (teste ou producao)
    $ambiente = obterConfiguracao('api_itau_ambiente', 'teste', $db);
    
    // Obtém a URL da API com base no ambiente e tipo
    $chave = "api_itau_{$tipo}_url_{$ambiente}";
    
    // URLs padrão caso não exista configuração
    $urls_padrao = [
        'cash_management_teste' => 'https://api.itau.com.br/cash_management/v2/boletos',
        'cash_management_producao' => 'https://api.itau.com.br/cash_management/v2/boletos',
        'cobranca_teste' => 'https://api.itau.com.br/cobranca/v2/boletos',
        'cobranca_producao' => 'https://api.itau.com.br/cobranca/v2/boletos',
        'token_teste' => 'https://sts.itau.com.br/api/oauth/token',
        'token_producao' => 'https://api.itau.com.br/api/oauth/token'
    ];
    
    $url_padrao = $urls_padrao["{$tipo}_{$ambiente}"] ?? $urls_padrao['cash_management_teste'];
    
    return obterConfiguracao($chave, $url_padrao, $db);
}

/**
 * Obtém a URL para obtenção de token da API do Itaú
 * 
 * @param object $db Objeto de conexão com o banco de dados (opcional)
 * @return string URL para obtenção de token
 */
function obterUrlTokenItau($db = null) {
    // Se não foi passado um objeto de conexão, cria um
    if ($db === null) {
        $db = Database::getInstance();
    }
    
    // Obtém o ambiente configurado (teste ou producao)
    $ambiente = obterConfiguracao('api_itau_ambiente', 'teste', $db);
    
    // Obtém a URL do token com base no ambiente
    $chave = "api_itau_token_url_{$ambiente}";
    
    // URLs padrão caso não exista configuração
    $urls_padrao = [
        'teste' => 'https://sts.itau.com.br/api/oauth/token',
        'producao' => 'https://api.itau.com.br/api/oauth/token'
    ];
    
    $url_padrao = $urls_padrao[$ambiente] ?? $urls_padrao['teste'];
    
    return obterConfiguracao($chave, $url_padrao, $db);
}
?>
