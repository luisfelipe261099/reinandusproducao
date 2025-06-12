<?php
/**
 * Classe de conexão com o banco de dados
 *
 * Esta classe gerencia a conexão com o banco de dados usando PDO
 */

class Database {
    private static $instance = null;
    private $conn;

    /**
     * Construtor privado para implementar o padrão Singleton
     */
    private function __construct() {
        require_once __DIR__ . '/../config/database.php';

        try {
            error_log("Database::__construct - Connecting to database: " . DB_NAME . " on host: " . DB_HOST);

            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            $this->conn = new PDO($dsn, DB_USER, DB_PASS, $options);
            error_log("Database::__construct - Connection successful");

            // Verifica se a conexão está funcionando
            $test = $this->conn->query("SELECT 1");
            if ($test) {
                error_log("Database::__construct - Test query successful");
            } else {
                error_log("Database::__construct - Test query failed");
            }
        } catch (PDOException $e) {
            error_log("Database::__construct - Connection error: " . $e->getMessage());
            die("Erro de conexão com o banco de dados: " . $e->getMessage());
        } catch (Exception $e) {
            error_log("Database::__construct - General error: " . $e->getMessage());
            die("Erro geral: " . $e->getMessage());
        }
    }

    /**
     * Obtém a instância única da conexão (Singleton)
     *
     * @return Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Obtém a conexão PDO
     *
     * @return PDO
     */
    public function getConnection() {
        return $this->conn;
    }

    /**
     * Executa uma consulta SQL
     *
     * @param string $sql A consulta SQL
     * @param array $params Parâmetros para a consulta
     * @return PDOStatement
     */
    public function query($sql, $params = []) {
        try {
            error_log("Database::query - Preparing SQL: {$sql}");
            $stmt = $this->conn->prepare($sql);

            if (!$stmt) {
                $error = $this->conn->errorInfo();
                error_log("Database::query - Prepare error: " . json_encode($error));
                throw new PDOException("Prepare failed: " . $error[2]);
            }

            error_log("Database::query - Executing with params: " . json_encode($params));
            $result = $stmt->execute($params);

            if (!$result) {
                $error = $stmt->errorInfo();
                error_log("Database::query - Execute error: " . json_encode($error));
                throw new PDOException("Execute failed: " . $error[2]);
            }

            error_log("Database::query - Success, row count: " . $stmt->rowCount());
            return $stmt;
        } catch (PDOException $e) {
            error_log("Database::query - PDO Exception: " . $e->getMessage());
            throw $e;
        } catch (Exception $e) {
            error_log("Database::query - General Exception: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtém um único registro
     *
     * @param string $sql A consulta SQL
     * @param array $params Parâmetros para a consulta
     * @return array|false
     */
    public function fetchOne($sql, $params = []) {
        try {
            error_log("Database::fetchOne - SQL: {$sql}");
            error_log("Database::fetchOne - Params: " . json_encode($params));

            $stmt = $this->query($sql, $params);
            $result = $stmt->fetch();

            error_log("Database::fetchOne - Result: " . ($result ? json_encode($result) : 'false'));
            return $result;
        } catch (Exception $e) {
            error_log("Database::fetchOne - Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtém todos os registros
     *
     * @param string $sql A consulta SQL
     * @param array $params Parâmetros para a consulta
     * @return array
     */
    public function fetchAll($sql, $params = []) {
        try {
            error_log("Database::fetchAll - SQL: {$sql}");
            error_log("Database::fetchAll - Params: " . json_encode($params));

            $stmt = $this->query($sql, $params);
            $result = $stmt->fetchAll();

            error_log("Database::fetchAll - Result count: " . count($result));
            return $result;
        } catch (Exception $e) {
            error_log("Database::fetchAll - Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Insere um registro e retorna o ID
     *
     * @param string $table Nome da tabela
     * @param array $data Dados a serem inseridos
     * @return int O ID do registro inserido
     */
    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->query($sql, array_values($data));

        return $this->conn->lastInsertId();
    }

    /**
     * Atualiza um registro
     *
     * @param string $table Nome da tabela
     * @param array $data Dados a serem atualizados
     * @param string $where Condição WHERE
     * @param array $params Parâmetros para a condição WHERE
     * @return int Número de linhas afetadas
     */
    public function update($table, $data, $where, $params = []) {
        try {
            error_log("Database::update - Table: {$table}, Data: " . json_encode($data) . ", Where: {$where}");

            $set = [];
            foreach (array_keys($data) as $column) {
                $set[] = "{$column} = ?";
            }
            $set = implode(', ', $set);

            $sql = "UPDATE {$table} SET {$set} WHERE {$where}";
            error_log("Database::update - SQL: {$sql}");

            $allParams = array_merge(array_values($data), $params);
            error_log("Database::update - All params: " . json_encode($allParams));

            $stmt = $this->query($sql, $allParams);
            $rowCount = $stmt->rowCount();

            error_log("Database::update - Rows affected: {$rowCount}");
            return $rowCount;
        } catch (Exception $e) {
            error_log("Database::update - Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Exclui um registro
     *
     * @param string $table Nome da tabela
     * @param string $where Condição WHERE
     * @param array $params Parâmetros para a condição WHERE
     * @return int Número de linhas afetadas
     */
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $this->query($sql, $params);

        return $stmt->rowCount();
    }

    /**
     * Inicia uma transação
     */
    public function beginTransaction() {
        $this->conn->beginTransaction();
    }

    /**
     * Confirma uma transação
     */
    public function commit() {
        // Verifica se há uma transação ativa antes de fazer commit
        if ($this->conn->inTransaction()) {
            $this->conn->commit();
        }
    }

    /**
     * Reverte uma transação
     */
    public function rollback() {
        // Verifica se há uma transação ativa antes de fazer rollback
        if ($this->conn->inTransaction()) {
            $this->conn->rollBack();
        }
    }

    /**
     * Retorna o ID do último registro inserido
     *
     * @return string O ID do último registro inserido
     */
    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }

    /**
     * Prepara uma consulta SQL (método de compatibilidade)
     * 
     * @param string $sql A consulta SQL
     * @return PDOStatement
     */
    public function prepare($sql) {
        try {
            error_log("Database::prepare - Preparing SQL: {$sql}");
            $stmt = $this->conn->prepare($sql);
            
            if (!$stmt) {
                $error = $this->conn->errorInfo();
                error_log("Database::prepare - Prepare error: " . json_encode($error));
                throw new PDOException("Prepare failed: " . $error[2]);
            }
            
            return $stmt;
        } catch (PDOException $e) {
            error_log("Database::prepare - PDO Exception: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtém a conexão PDO diretamente (método de compatibilidade)
     * 
     * @return PDO
     */
    public function getPDO() {
        return $this->conn;
    }
}
