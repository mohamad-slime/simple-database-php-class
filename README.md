# Simple PHP Database Class

A lightweight, easy-to-use PHP database class that provides a simple and efficient way to interact with MySQL  database using **PDO (PHP Data Objects)**. This class is designed to streamline database operations, reduce boilerplate code, and improve code readability.

---

## Features

- **Simple and Lightweight**: Easy to integrate into any PHP project.
- **PDO-Based**: Uses PDO for secure and flexible database interactions.
- **CRUD Operations**: Supports `insert`, `select`, `update`, and `delete` operations.
- **Prepared Statements**: Protects against SQL injection.
- **Error Handling**: Throws exceptions for database errors.
- **Flexible**: Works with MySQL, PostgreSQL, SQLite, and other PDO-supported databases.

---

## Installation

1. **Download the Class**:
   - Download the `Database.php` file from the repository.

2. **Include the Class in Your Project**:
   ```php
   require 'path/to/Database.php';
   
3. **Configure the Database Connection:**
   - Create an instance of the Database class with your database credentials.
   
## Usage 
1. Create a Database Instance
   ```php
   $db = new Database('localhost', 'dbname', 'username', 'password');

2. **Insert Data**:
   ```php
   $lastInsertId = $db->insert('users', [
    'name' => 'John Doe',
    'email' => 'john@example.com']);
   echo "Last Insert ID: $lastInsertId";
3. **Fetch Data**:
   ```php
   $users = $db->select('users', '*', 'id > :id', [':id' => 5]);
    foreach ($users as $user) {
    echo "Name: {$user['name']}, Email: {$user['email']}\n";}
4. **Update Data**:
   ```php
   $affectedRows = $db->update('users', [
   'name' => 'Jane Doe'], 'id = :id', [':id' => 1]);
   echo "Affected Rows: $affectedRows";
5. **Delete Data**:
   ```php
   $affectedRows = $db->delete('users', 'id = :id', [':id' => 10]);
   echo "Affected Rows: $affectedRows";
     
