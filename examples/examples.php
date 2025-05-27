<?php

declare(strict_types=1);

require 'vendor/autoload.php';

use SimpleDatabase\Database;
use SimpleDatabase\DatabaseException;
use Dotenv\Dotenv;

// Load environment variables from .env file
Dotenv::createImmutable(__DIR__ . '/..')->load();

// Initialize the Database class with environment variables
try {
    $db = new Database(
        $_ENV['DB_TYPE'] ?? 'mysql',
        $_ENV['DB_HOST'] ?? 'localhost',
        $_ENV['DB_NAME'] ?? 'testdb',
        $_ENV['DB_USER'] ?? 'root',
        $_ENV['DB_PASS'] ?? '',
        $_ENV['DB_CHARSET'] ?? 'utf8mb4'
    );

    // Example 1: Insert a new user
    echo "Example 1: Inserting a new user\n";
    $userData = [
        'name' => 'John Doe',
        'age' => 30,
    ];
    $userId = $db->insert('users', $userData);
    echo "Inserted user with ID: $userId\n";

    // Example 2: Fetch a single user
    echo "\nExample 2: Fetching a single user\n";
    $user = $db->fetch('SELECT * FROM users WHERE id = :id', ['id' => $userId]);
    if ($user) {
        echo "User found: {$user['name']} (Age: {$user['age']})\n";
    } else {
        echo "User not found\n";
    }

    // Example 3: Update a user
    echo "\nExample 3: Updating a user\n";
    $updateData = [
        'name' => 'John Smith',
        'age' => 31,
    ];
    $affectedRows = $db->update('users', $updateData, ['id' => $userId]);
    echo "Updated $affectedRows user(s)\n";

    // Example 4: Fetch all users
    echo "\nExample 4: Fetching all users\n";
    $users = $db->fetchAll('SELECT * FROM users');
    foreach ($users as $user) {
        echo "User: {$user['name']} (Age: {$user['age']})\n";
    }

    // Example 5: Delete a user
    echo "\nExample 5: Deleting a user\n";
    $affectedRows = $db->delete('users', ['id' => $userId]);
    echo "Deleted $affectedRows user(s)\n";

    // Example 6: Transaction (insert multiple users)
    echo "\nExample 6: Using a transaction\n";
    try {
        $db->beginTransaction();
        $db->insert('users', ['name' => 'Alice', 'age' => 25]);
        $db->insert('users', ['name' => 'Bob', 'age' => 35]);
        $db->commit();
        echo "Transaction committed: 2 users inserted\n";
    } catch (DatabaseException $e) {
        $db->rollBack();
        echo "Transaction failed: {$e->getMessage()}\n";
    }

    // Disconnect from the database
    $db->disconnect();
} catch (DatabaseException $e) {
    echo "Error: {$e->getMessage()}\n";
}
