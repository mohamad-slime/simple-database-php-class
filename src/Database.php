<?php

declare(strict_types=1);

namespace SimpleDatabase;

use PDO;
use PDOException;
use PDOStatement;

/**
 * Custom exception for database-related errors.
 */


/**
 * A simple PDO-based database management class.
 */
class Database
{
    private ?PDO $pdo = null;

    /**
     * @param string $dbType   Database type (e.g., 'mysql', 'sqlite')
     * @param string $host     Database host (e.g., 'localhost') or empty for SQLite
     * @param string $dbname   Database name or path (e.g., ':memory:' for SQLite)
     * @param string $username Database username (empty for SQLite)
     * @param string $password Database password (empty for SQLite)
     * @param string $charset  Database charset (default: 'utf8mb4', ignored for SQLite)
     */
    private $dbType;
    private $host;
    private $dbname;
    private $username;
    private $password;
    private $charset;

    public function __construct(
        string $dbType,
        string $host,
        string $dbname,
        string $username,
        string $password,
        string $charset = 'utf8mb4'
    ) {
        $this->dbType = $dbType;
        $this->host = $host;
        $this->dbname = $dbname;
        $this->username = $username;
        $this->password = $password;
        $this->charset = $charset;
    }

    /**
     * Establishes a PDO connection to the database.
     *
     * @throws DatabaseException If the connection fails
     */
    private function connect(): PDO
    {
        if ($this->pdo !== null) {
            return $this->pdo;
        }

        try {
            if ($this->dbType === 'sqlite') {
                $dsn = "sqlite:{$this->dbname}";
                $this->pdo = new PDO($dsn, null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } else {
                $dsn = "{$this->dbType}:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
                $this->pdo = new PDO($dsn, $this->username, $this->password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            }
            return $this->pdo;
        } catch (PDOException $e) {
            throw new DatabaseException("Failed to connect to database: {$e->getMessage()}", (int)$e->getCode(), $e);
        }
    }

    /**
     * Executes a prepared SQL query with optional parameters.
     *
     * @param string $sql    The SQL query to execute
     * @param array  $params Parameters to bind to the query
     * @return PDOStatement The executed statement
     * @throws DatabaseException If the query fails
     */
    public function query(string $sql, array $params = []): PDOStatement
    {
        try {
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new DatabaseException("Query execution failed: {$e->getMessage()}. SQL: {$sql}", 500, $e);
        }
    }

    /**
     * Inserts a new record into a table and returns the last insert ID.
     *
     * @param string $table Table name
     * @param array  $data  Associative array of column names and values
     * @return string The last inserted ID
     * @throws DatabaseException If the insert fails or data is empty
     */
    public function insert(string $table, array $data): string
    {
        if (empty($data)) {
            throw new DatabaseException('Insert data cannot be empty');
        }

        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";

        $this->query($sql, $data);
        return $this->connect()->lastInsertId();
    }

    /**
     * Selects records from a table based on conditions.
     *
     * @param string $table      Table name
     * @param array  $columns    Array of column names to select (default: ['*'])
     * @param array  $conditions Associative array of where conditions (column => value)
     * @return array The selected records
     * @throws DatabaseException If the select fails
     */

    public function select(string $table, array $columns = ['*'], array $conditions = []): array
    {
        $columnClause = implode(', ', $columns);

        $where = array_map(fn($key) => "{$key} = :{$key}", array_keys($conditions));
        $whereClause = implode(' AND ', $where);

        $sql = "SELECT {$columnClause} FROM {$table}";
        if (!empty($conditions)) {
            $sql .= " WHERE {$whereClause}";
        }

        return $this->query($sql, $conditions)->fetchAll();
    }

    /**
     * Updates records in a table based on conditions.
     *
     * @param string $table       Table name
     * @param array  $data        Associative array of column names and values to update
     * @param array  $conditions  Associative array of where conditions (column => value)
     * @return int The number of affected rows
     * @throws DatabaseException If data or conditions are empty or update fails
     */
    public function update(string $table, array $data, array $conditions): int
    {
        if (empty($data)) {
            throw new DatabaseException('Update data cannot be empty');
        }
        if (empty($conditions)) {
            throw new DatabaseException('Update conditions cannot be empty');
        }

        $set = array_map(fn($key) => "{$key} = :set_{$key}", array_keys($data));
        $setClause = implode(', ', $set);

        $where = array_map(fn($key) => "{$key} = :where_{$key}", array_keys($conditions));
        $whereClause = implode(' AND ', $where);

        $sql = "UPDATE {$table} SET {$setClause} WHERE {$whereClause}";

        $params = [];
        foreach ($data as $key => $value) {
            $params["set_{$key}"] = $value;
        }
        foreach ($conditions as $key => $value) {
            $params["where_{$key}"] = $value;
        }

        return $this->query($sql, $params)->rowCount();
    }

    /**
     * Deletes records from a table based on conditions.
     *
     * @param string $table      Table name
     * @param array  $conditions Associative array of where conditions (column => value)
     * @return int The number of affected rows
     * @throws DatabaseException If conditions are empty or delete fails
     */
    public function delete(string $table, array $conditions): int
    {
        if (empty($conditions)) {
            throw new DatabaseException('Delete conditions cannot be empty');
        }

        $where = array_map(fn($key) => "{$key} = :{$key}", array_keys($conditions));
        $whereClause = implode(' AND ', $where);
        $sql = "DELETE FROM {$table} WHERE {$whereClause}";

        return $this->query($sql, $conditions)->rowCount();
    }

    /**
     * Fetches a single row from a query.
     *
     * @param string $sql    The SQL query to execute
     * @param array  $params Parameters to bind to the query
     * @return array|null The fetched row or null if no results
     * @throws DatabaseException If the query fails
     */
    public function fetch(string $sql, array $params = []): ?array
    {
        return $this->query($sql, $params)->fetch() ?: null;
    }

    /**
     * Fetches all rows from a query.
     *
     * @param string $sql    The SQL query to execute
     * @param array  $params Parameters to bind to the query
     * @return array The fetched rows
     * @throws DatabaseException If the query fails
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * Starts a database transaction.
     *
     * @throws DatabaseException If the transaction fails
     */
    public function beginTransaction(): void
    {
        try {
            $this->connect()->beginTransaction();
        } catch (PDOException $e) {
            throw new DatabaseException("Failed to start transaction: {$e->getMessage()}", (int)$e->getCode(), $e);
        }
    }

    /**
     * Commits a database transaction.
     *
     * @throws DatabaseException If the commit fails
     */
    public function commit(): void
    {
        try {
            $this->connect()->commit();
        } catch (PDOException $e) {
            throw new DatabaseException("Failed to commit transaction: {$e->getMessage()}", (int)$e->getCode(), $e);
        }
    }

    /**
     * Rolls back a database transaction.
     *
     * @throws DatabaseException If the rollback fails
     */
    public function rollBack(): void
    {
        try {
            $this->connect()->rollBack();
        } catch (PDOException $e) {
            throw new DatabaseException("Failed to roll back transaction: {$e->getMessage()}", (int)$e->getCode(), $e);
        }
    }

    /**
     * Gets the current PDO connection.
     *
     * @return PDO The active PDO connection
     */
    public function getConnection(): PDO
    {
        return $this->connect();
    }

    /**
     * Closes the PDO connection.
     */
    public function disconnect(): void
    {
        $this->pdo = null;
    }
}

class DatabaseException extends \Exception {}
