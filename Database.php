<?php

namespace App\Database;

use PDO;
use PDOStatement;
use PDOException;

/**
 * Summary of Database
 */
class Database
{

    private string $host, $dbname, $username, $password;
    private string  $charset = 'utf8mb4';
    private ?PDO $pdo = null;
    public function __construct($host, $dbname, $username, $password)
    {
        $this->host = $host;
        $this->dbname = $dbname;
        $this->username = $username;
        $this->password = $password;
    }
    /**
     * Summary of connect
     * @throws \ErrorException
     */
    protected function connect(): PDO
    {
        if ($this->pdo === null) {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
            } catch (PDOException $e) {

                throw new \ErrorException("Database connection error: " . $e->getMessage());
            }
        }
        return $this->pdo;
    }

    /**
     * Summary of query
     * @param mixed $sql
     * @param mixed $params
     * @throws \ErrorException
     * @return bool|PDOStatement
     */
    public function query($sql, $params = []): bool|PDOStatement
    {
        try {
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new \ErrorException("Database error:" . $e->getMessage(), 500);
        }
    }

    /**
     * Summary of insert
     * @param mixed $table
     * @param mixed $data
     * @throws \InvalidArgumentException
     * @return bool|string
     */
    public function insert($table, $data): bool|string
    {
        if (empty($data)) {
            throw new \InvalidArgumentException("Data cannot be empty");
        }

        $columns = implode(', ', array_keys($data));
        $values = ':' . implode(', :', array_keys($data));
        $sql = "INSERT INTO $table ($columns) VALUES ($values)";
        $this->query($sql, $data);
        return $this->connect()->lastInsertId();
    }

    /**
     * Summary of update
     * @param mixed $table
     * @param mixed $data
     * @param mixed $where
     * @param mixed $whereParams
     * @throws \InvalidArgumentException
     * @return bool|PDOStatement
     */
    public function update($table, $data, $where, $whereParams = []): bool|PDOStatement
    {
        if (empty($data)) {
            throw new \InvalidArgumentException("Data cannot be empty");
        }

        if (empty($where)) {
            throw new \InvalidArgumentException("Where clause cannot be empty");
        }
        $set = [];
        foreach ($data as $key => $value) {
            $set[] = "$key = :$key";
        }
        $set = implode(', ', $set);
        $sql = "UPDATE $table SET $set WHERE $where";

        $params = array_merge($data, $whereParams);
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Summary of delete
     * @param mixed $table
     * @param mixed $where
     * @param mixed $whereParams
     * @throws \InvalidArgumentException
     * @return bool|PDOStatement
     */
    public function delete($table, $where, $whereParams = []): bool|PDOStatement
    {
        if (empty($where)) {
            throw new \InvalidArgumentException("Where clause cannot be empty");
        }
        $sql = "DELETE FROM $table WHERE $where";
        $stmt = $this->query($sql, $whereParams);
        return $stmt->rowCount();
    }
    /**
     * Summary of fetch
     * @param mixed $sql
     * @param mixed $params
     * @return mixed
     */
    public function fetch($sql, $params = []): mixed
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }

    public function fetchAll($sql, $params = []): array
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Summary of getConnection
     * @return PDO|null
     */
    public function getConnection(): ?PDO
    {
        return $this->connect();
    }
    /**
     * Summary of disconnect
     * @return void
     */
    public function disconnect(): void
    {
        $this->pdo = null;
    }
}
