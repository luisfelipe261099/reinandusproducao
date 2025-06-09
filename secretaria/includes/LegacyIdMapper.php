<?php
/**
 * Classe para mapeamento de IDs legados
 * 
 * Esta classe gerencia o mapeamento entre IDs do sistema atual e IDs do sistema legado
 */

class LegacyIdMapper {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Obtém o ID atual a partir do ID legado
     * 
     * @param string $entidade Nome da entidade (tabela)
     * @param string $idLegado ID legado
     * @return int|null ID atual ou null se não encontrado
     */
    public function getCurrentId($entidade, $idLegado) {
        // Primeiro tenta buscar diretamente na tabela da entidade
        $sql = "SELECT id FROM {$entidade} WHERE id_legado = ?";
        $result = $this->db->fetchOne($sql, [$idLegado]);
        
        if ($result) {
            return $result['id'];
        }
        
        // Se não encontrou, busca na tabela de mapeamento
        $sql = "
            SELECT id_atual 
            FROM mapeamento_ids_legados 
            WHERE entidade = ? AND id_legado = ?
        ";
        
        $result = $this->db->fetchOne($sql, [$entidade, $idLegado]);
        
        return $result ? $result['id_atual'] : null;
    }
    
    /**
     * Obtém o ID legado a partir do ID atual
     * 
     * @param string $entidade Nome da entidade (tabela)
     * @param int $idAtual ID atual
     * @return string|null ID legado ou null se não encontrado
     */
    public function getLegacyId($entidade, $idAtual) {
        // Primeiro tenta buscar diretamente na tabela da entidade
        $sql = "SELECT id_legado FROM {$entidade} WHERE id = ?";
        $result = $this->db->fetchOne($sql, [$idAtual]);
        
        if ($result && $result['id_legado']) {
            return $result['id_legado'];
        }
        
        // Se não encontrou, busca na tabela de mapeamento
        $sql = "
            SELECT id_legado 
            FROM mapeamento_ids_legados 
            WHERE entidade = ? AND id_atual = ?
        ";
        
        $result = $this->db->fetchOne($sql, [$entidade, $idAtual]);
        
        return $result ? $result['id_legado'] : null;
    }
    
    /**
     * Registra um mapeamento entre ID atual e ID legado
     * 
     * @param string $entidade Nome da entidade (tabela)
     * @param int $idAtual ID atual
     * @param string $idLegado ID legado
     * @return bool Sucesso da operação
     */
    public function registerMapping($entidade, $idAtual, $idLegado) {
        // Verifica se já existe um mapeamento
        $sql = "
            SELECT id 
            FROM mapeamento_ids_legados 
            WHERE entidade = ? AND (id_atual = ? OR id_legado = ?)
        ";
        
        $result = $this->db->fetchOne($sql, [$entidade, $idAtual, $idLegado]);
        
        if ($result) {
            // Atualiza o mapeamento existente
            return $this->db->update(
                'mapeamento_ids_legados',
                [
                    'id_atual' => $idAtual,
                    'id_legado' => $idLegado,
                    'updated_at' => date('Y-m-d H:i:s')
                ],
                'id = ?',
                [$result['id']]
            );
        } else {
            // Cria um novo mapeamento
            return $this->db->insert('mapeamento_ids_legados', [
                'entidade' => $entidade,
                'id_atual' => $idAtual,
                'id_legado' => $idLegado,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }
    }
    
    /**
     * Atualiza o ID legado de uma entidade
     * 
     * @param string $entidade Nome da entidade (tabela)
     * @param int $idAtual ID atual
     * @param string $idLegado ID legado
     * @return bool Sucesso da operação
     */
    public function updateLegacyId($entidade, $idAtual, $idLegado) {
        // Atualiza o ID legado na tabela da entidade
        $result = $this->db->update(
            $entidade,
            ['id_legado' => $idLegado],
            'id = ?',
            [$idAtual]
        );
        
        // Registra também no mapeamento
        $this->registerMapping($entidade, $idAtual, $idLegado);
        
        return $result;
    }
    
    /**
     * Busca entidades pelo ID legado
     * 
     * @param string $entidade Nome da entidade (tabela)
     * @param string $idLegado ID legado
     * @return array Dados da entidade
     */
    public function findByLegacyId($entidade, $idLegado) {
        $sql = "SELECT * FROM {$entidade} WHERE id_legado = ?";
        return $this->db->fetchOne($sql, [$idLegado]);
    }
    
    /**
     * Busca múltiplas entidades pelos IDs legados
     * 
     * @param string $entidade Nome da entidade (tabela)
     * @param array $idsLegados Lista de IDs legados
     * @return array Lista de entidades
     */
    public function findByLegacyIds($entidade, $idsLegados) {
        if (empty($idsLegados)) {
            return [];
        }
        
        $placeholders = implode(',', array_fill(0, count($idsLegados), '?'));
        $sql = "SELECT * FROM {$entidade} WHERE id_legado IN ({$placeholders})";
        
        return $this->db->fetchAll($sql, $idsLegados);
    }
}
