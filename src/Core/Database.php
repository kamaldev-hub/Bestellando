<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;

class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;

    private string $host;
    private string $dbName;
    private string $user;
    private string $password;
    private string $charset = 'utf8mb4';

    private function __construct()
    {
        // Load configuration from environment variables
        // Ensure these are set in your .env file (or server environment)
        $this->host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'db'; // Default for Docker
        $this->dbName = $_ENV['DB_DATABASE'] ?? getenv('DB_DATABASE') ?: 'bestellando_db';
        $this->user = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'bestellando_user';
        $this->password = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: 'bestellando_password';
        // $this->port = $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: '3306'; // If needed

        $dsn = "mysql:host={$this->host};dbname={$this->dbName};charset={$this->charset}";
        // For specific port: "mysql:host={$this->host};port={$this->port};dbname={$this->dbName};charset={$this->charset}";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false, // Use native prepared statements
        ];

        try {
            $this->pdo = new PDO($dsn, $this->user, $this->password, $options);
        } catch (PDOException $e) {
            // In a real application, log this error and show a user-friendly message
            error_log("Database Connection Error: " . $e->getMessage());
            // Depending on the application stage, you might throw an exception
            // or display a generic error page. For now, we'll die to make it obvious during development.
            throw new PDOException("Database connection failed: " . $e->getMessage(), (int)$e->getCode());
        }
    }

    /**
     * Get the singleton instance of the Database.
     */
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get the PDO connection object.
     */
    public function getConnection(): PDO
    {
        return $this->pdo;
    }

    /**
     * Prepares a statement for execution and returns a statement object.
     *
     * @param string $sql SQL query
     * @return PDOStatement|false
     */
    public function prepare(string $sql): PDOStatement|false
    {
        return $this->pdo->prepare($sql);
    }

    /**
     * Executes an SQL statement, returning a result set as a PDOStatement object.
     *
     * @param string $sql
     * @param array $params
     * @return PDOStatement|false
     */
    public function query(string $sql, array $params = []): PDOStatement|false
    {
        if (empty($params)) {
            return $this->pdo->query($sql);
        }
        $stmt = $this->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Executes a prepared statement with an array of input values.
     *
     * @param PDOStatement $stmt
     * @param array $params
     * @return bool True on success, false on failure.
     */
    public function execute(PDOStatement $stmt, array $params = []): bool
    {
        return $stmt->execute($params);
    }

    /**
     * Returns the ID of the last inserted row or sequence value.
     *
     * @param string|null $name Name of the sequence object from which the ID should be returned.
     * @return string|false Returns a string representing the row ID of the last row that was inserted
     *                      into the database. Returns false if the driver does not support this capability.
     */
    public function lastInsertId(string $name = null): string|false
    {
        return $this->pdo->lastInsertId($name);
    }

    /**
     * Initiates a transaction.
     * @return bool TRUE on success or FALSE on failure.
     */
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commits a transaction.
     * @return bool TRUE on success or FALSE on failure.
     */
    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    /**
     * Rolls back a transaction.
     * @return bool TRUE on success or FALSE on failure.
     */
    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }

    /**
     * Checks if inside a transaction.
     * @return bool TRUE if a transaction is currently active, and FALSE if not.
     */
    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }

    // It's good practice to prevent cloning and unserialization of singletons.
    private function __clone() {}
    public function __wakeup() {
        throw new \Exception("Cannot unserialize a singleton.");
    }
}
