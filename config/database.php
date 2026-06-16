<?php
require_once __DIR__ . '/config.php';

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $charset = 'utf8mb4';
    private $pdo;

    public function __construct() {
        if ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_NAME'] === '127.0.0.1') {
            $this->host     = 'localhost';
            $this->db_name  = 'fina';
            $this->username = 'root';
            $this->password = '';
        } else {
            $this->host     = 'localhost';
            $this->db_name  = 'u214219698_financias';
            $this->username = 'u214219698_financias';
            $this->password = 'ffeede!A12';
        }
    }

    public function conectar() {
        if ($this->pdo === null) {
            try {
                $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                ];
                
                $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
            } catch (PDOException $e) {
                throw new Exception("Erro na conexão com o banco de dados: " . $e->getMessage());
            }
        }
        
        return $this->pdo;
    }

    public function select($sql, $params = []) {
        try {
            $stmt = $this->conectar()->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Erro ao executar SELECT: " . $e->getMessage());
        }
    }

    public function insert($sql, $params = []) {
        try {
            $stmt = $this->conectar()->prepare($sql);
            $stmt->execute($params);
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            throw new Exception("Erro ao executar INSERT: " . $e->getMessage());
        }
    }

    public function update($sql, $params = []) {
        try {
            $stmt = $this->conectar()->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new Exception("Erro ao executar UPDATE: " . $e->getMessage());
        }
    }

    public function delete($sql, $params = []) {
        try {
            $stmt = $this->conectar()->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new Exception("Erro ao executar DELETE: " . $e->getMessage());
        }
    }

    public function beginTransaction() {
        return $this->conectar()->beginTransaction();
    }

    public function commit() {
        return $this->conectar()->commit();
    }

    public function rollback() {
        return $this->conectar()->rollback();
    }

    public function query($sql, $params = []) {
        try {
            $stmt = $this->conectar()->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new Exception("Erro ao executar query: " . $e->getMessage());
        }
    }
}

$database = new Database();
?>