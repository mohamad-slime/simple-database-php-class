<?php

declare(strict_types=1);

namespace SimpleDatabase\Tests;

use PHPUnit\Framework\TestCase;
use SimpleDatabase\Database;
use SimpleDatabase\DatabaseException;

final class DatabaseTest extends TestCase
{
    private Database $db;

    /**
     * Set up the test environment by creating an in-memory SQLite database.
     */
    protected function setUp(): void
    {
        // Use SQLite in-memory database for testing
        $this->db = new Database(
            'sqlite',
            '',
            ':memory:',
            '',
            '',
            'utf8mb4'
        );

        // Create a test table
        $this->db->query('
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                age INTEGER NOT NULL
            )
        ');
    }

    /**
     * Clean up the database after each test.
     */
    protected function tearDown(): void
    {
        $this->db->disconnect();
    }

    /**
     * Test successful database connection.
     */
    public function testConnection(): void
    {
        $this->assertInstanceOf(\PDO::class, $this->db->getConnection());
    }

    /**
     * Test insert method with valid data.
     */
    public function testInsertSuccess(): void
    {
        $data = ['name' => 'John Doe', 'age' => 30];
        $id = $this->db->insert('users', $data);
        $this->assertIsString($id);
        $this->assertGreaterThan(0, (int)$id);

        $result = $this->db->fetch('SELECT * FROM users WHERE id = :id', ['id' => $id]);
        $this->assertEquals($data, ['name' => $result['name'], 'age' => $result['age']]);
    }

    /**
     * Test insert method with empty data throws exception.
     */
    public function testInsertWithEmptyData(): void
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('Insert data cannot be empty');
        $this->db->insert('users', []);
    }

    /**
     * Test update method with valid data and conditions.
     */
    public function testUpdateSuccess(): void
    {
        $this->db->insert('users', ['name' => 'Jane Doe', 'age' => 25]);
        $affectedRows = $this->db->update(
            'users',
            ['name' => 'Jane Smith', 'age' => 26],
            ['name' => 'Jane Doe']
        );
        $this->assertEquals(1, $affectedRows);

        $result = $this->db->fetch('SELECT * FROM users WHERE name = :name', ['name' => 'Jane Smith']);
        $this->assertEquals(['name' => 'Jane Smith', 'age' => 26], ['name' => $result['name'], 'age' => $result['age']]);
    }

    /**
     * Test update method with empty data throws exception.
     */
    public function testUpdateWithEmptyData(): void
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('Update data cannot be empty');
        $this->db->update('users', [], ['name' => 'John']);
    }

    /**
     * Test update method with empty conditions throws exception.
     */
    public function testUpdateWithEmptyConditions(): void
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('Update conditions cannot be empty');
        $this->db->update('users', ['name' => 'John'], []);
    }

    /**
     * Test delete method with valid conditions.
     */
    public function testDeleteSuccess(): void
    {
        $this->db->insert('users', ['name' => 'Alice', 'age' => 40]);
        $affectedRows = $this->db->delete('users', ['name' => 'Alice']);
        $this->assertEquals(1, $affectedRows);

        $result = $this->db->fetch('SELECT * FROM users WHERE name = :name', ['name' => 'Alice']);
        $this->assertNull($result);
    }

    /**
     * Test delete method with empty conditions throws exception.
     */
    public function testDeleteWithEmptyConditions(): void
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('Delete conditions cannot be empty');
        $this->db->delete('users', []);
    }

    /**
     * Test fetch method with valid query.
     */
    public function testFetchSuccess(): void
    {
        $this->db->insert('users', ['name' => 'Bob', 'age' => 35]);
        $result = $this->db->fetch('SELECT * FROM users WHERE name = :name', ['name' => 'Bob']);
        $this->assertIsArray($result);
        $this->assertEquals('Bob', $result['name']);
        $this->assertEquals(35, $result['age']);
    }

    /**
     * Test fetch method returns null when no results.
     */
    public function testFetchNoResults(): void
    {
        $result = $this->db->fetch('SELECT * FROM users WHERE name = :name', ['name' => 'NonExistent']);
        $this->assertNull($result);
    }

    /**
     * Test fetchAll method with valid query.
     */
    public function testFetchAllSuccess(): void
    {
        $this->db->insert('users', ['name' => 'User1', 'age' => 20]);
        $this->db->insert('users', ['name' => 'User2', 'age' => 22]);
        $results = $this->db->fetchAll('SELECT * FROM users');
        $this->assertCount(2, $results);
        $this->assertEquals('User1', $results[0]['name']);
        $this->assertEquals('User2', $results[1]['name']);
    }

    /**
     * Test transaction handling.
     */
    public function testTransactionSuccess(): void
    {
        $this->db->beginTransaction();
        $this->db->insert('users', ['name' => 'Transactional User', 'age' => 50]);
        $this->db->commit();

        $result = $this->db->fetch('SELECT * FROM users WHERE name = :name', ['name' => 'Transactional User']);
        $this->assertIsArray($result);
        $this->assertEquals('Transactional User', $result['name']);
    }

    /**
     * Test transaction rollback.
     */
    public function testTransactionRollback(): void
    {
        $this->db->beginTransaction();
        $this->db->insert('users', ['name' => 'Rollback User', 'age' => 60]);
        $this->db->rollBack();

        $result = $this->db->fetch('SELECT * FROM users WHERE name = :name', ['name' => 'Rollback User']);
        $this->assertNull($result);
    }

    /**
     * Test transaction failure throws exception.
     */
    public function testTransactionFailure(): void
    {
        $this->db->beginTransaction();
        $this->expectException(DatabaseException::class);
        $this->db->query('INVALID SQL QUERY'); // Simulate a query failure
    }
}
