# Simple Database PHP Class

A lightweight and simple PHP class for managing database connections and queries using PDO.

## Features
- Easy-to-use PDO-based database connection.
- Support for basic CRUD operations (Create, Read, Update, Delete).
- Secure and configurable for different database types.

## Prerequisites
- PHP 7.4 or higher
- PDO extension enabled
- A supported database (e.g., MySQL, PostgreSQL)

## Installation
1. Clone the repository:
   ```bash
   git clone https://github.com/mohamad-slime/simple-database-php-class.git
   ```
2. Navigate to the project directory:
   ```bash
   cd simple-database-php-class
   ```
3. Install dependencies using Composer:
   ```bash
   composer install
   ```
4. Configure your database connection in a `.env` file (see [Environment Variables](#environment-variables)).

## Usage
```php
require 'vendor/autoload.php';

use SimpleDatabase\Database;

try {
    $db = new Database('mysql', 'localhost', 'mydb', 'username', 'password');
    $results = $db->select('users', ['id', 'name'], ['age' => 25]);
    print_r($results);
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
```
## Running the Example
1. Create a `.env` file in the project root with your database credentials (see `.env.example`).
2. Run the example:
   ```bash
   php examples/example.php
   ```
   
## Environment Variables
Create a `.env` file in the project root and add your database credentials:
```
DB_HOST=localhost
DB_NAME=mydb
DB_USER=username
DB_PASS=password
DB_TYPE=mysql
```

## Running Tests
Run unit tests using PHPUnit:
```bash
vendor/bin/phpunit tests
```

## Contributing
1. Fork the repository.
2. Create a new branch (`git checkout -b feature-branch`).
3. Make your changes and commit (`git commit -m 'Add new feature'`).
4. Push to the branch (`git push origin feature-branch`).
5. Create a Pull Request.

## License
This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.