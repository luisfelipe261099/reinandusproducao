<?php
/**
 * Classe de utilitários
 * 
 * Esta classe contém funções utilitárias para o sistema
 */

class Utils {
    /**
     * Formata uma data para o formato brasileiro
     * 
     * @param string $date Data no formato Y-m-d
     * @return string Data no formato d/m/Y
     */
    public static function formatDate($date) {
        if (empty($date)) {
            return '';
        }
        
        $timestamp = strtotime($date);
        return date('d/m/Y', $timestamp);
    }
    
    /**
     * Formata um valor monetário
     * 
     * @param float $value Valor a ser formatado
     * @return string Valor formatado
     */
    public static function formatMoney($value) {
        return 'R$ ' . number_format($value, 2, ',', '.');
    }
    
    /**
     * Formata um CPF
     * 
     * @param string $cpf CPF a ser formatado
     * @return string CPF formatado
     */
    public static function formatCpf($cpf) {
        if (empty($cpf)) {
            return '';
        }
        
        // Remove caracteres não numéricos
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        
        // Formata o CPF
        return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
    }
    
    /**
     * Formata um CNPJ
     * 
     * @param string $cnpj CNPJ a ser formatado
     * @return string CNPJ formatado
     */
    public static function formatCnpj($cnpj) {
        if (empty($cnpj)) {
            return '';
        }
        
        // Remove caracteres não numéricos
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
        
        // Formata o CNPJ
        return substr($cnpj, 0, 2) . '.' . substr($cnpj, 2, 3) . '.' . substr($cnpj, 5, 3) . '/' . substr($cnpj, 8, 4) . '-' . substr($cnpj, 12, 2);
    }
    
    /**
     * Limita o tamanho de um texto
     * 
     * @param string $text Texto a ser limitado
     * @param int $length Tamanho máximo
     * @param string $suffix Sufixo a ser adicionado quando o texto for cortado
     * @return string Texto limitado
     */
    public static function limitText($text, $length = 100, $suffix = '...') {
        if (strlen($text) <= $length) {
            return $text;
        }
        
        return substr($text, 0, $length) . $suffix;
    }
    
    /**
     * Gera um slug a partir de um texto
     * 
     * @param string $text Texto a ser convertido
     * @return string Slug gerado
     */
    public static function slugify($text) {
        // Remove acentos
        $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
        
        // Converte para minúsculas
        $text = strtolower($text);
        
        // Remove caracteres especiais
        $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
        
        // Substitui espaços por hífens
        $text = preg_replace('/[\s-]+/', '-', $text);
        
        // Remove hífens do início e do fim
        $text = trim($text, '-');
        
        return $text;
    }
    
    /**
     * Gera um token aleatório
     * 
     * @param int $length Tamanho do token
     * @return string Token gerado
     */
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }
    
    /**
     * Verifica se uma string é um JSON válido
     * 
     * @param string $string String a ser verificada
     * @return bool
     */
    public static function isJson($string) {
        if (!is_string($string)) {
            return false;
        }
        
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
    
    /**
     * Obtém o endereço IP do cliente
     * 
     * @return string Endereço IP
     */
    public static function getClientIp() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }
    
    /**
     * Obtém informações sobre o dispositivo do cliente
     * 
     * @return string Informações do dispositivo
     */
    public static function getClientDevice() {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'Desconhecido';
    }
    
    /**
     * Registra um log no sistema
     * 
     * @param string $modulo Módulo relacionado
     * @param string $acao Ação realizada
     * @param string $descricao Descrição da ação
     * @param int|null $objetoId ID do objeto relacionado
     * @param string|null $objetoTipo Tipo do objeto relacionado
     * @param array|null $dadosAntigos Dados antigos do objeto
     * @param array|null $dadosNovos Dados novos do objeto
     * @return void
     */
    public static function registrarLog($modulo, $acao, $descricao, $objetoId = null, $objetoTipo = null, $dadosAntigos = null, $dadosNovos = null) {
        $db = Database::getInstance();
        
        $usuarioId = Auth::getUserId();
        $ipOrigem = self::getClientIp();
        $dispositivo = self::getClientDevice();
        
        $dadosAntigosJson = $dadosAntigos ? json_encode($dadosAntigos) : null;
        $dadosNovosJson = $dadosNovos ? json_encode($dadosNovos) : null;
        
        $db->insert('logs_sistema', [
            'usuario_id' => $usuarioId,
            'modulo' => $modulo,
            'acao' => $acao,
            'descricao' => $descricao,
            'ip_origem' => $ipOrigem,
            'dispositivo' => $dispositivo,
            'objeto_id' => $objetoId,
            'objeto_tipo' => $objetoTipo,
            'dados_antigos' => $dadosAntigosJson,
            'dados_novos' => $dadosNovosJson
        ]);
    }
}
