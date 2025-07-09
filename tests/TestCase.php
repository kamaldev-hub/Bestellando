<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use App\Core\Database; // To potentially interact with the database
use PDO;

abstract class TestCase extends BaseTestCase
{
    protected static ?PDO $pdo = null;
    protected static bool $migrationsRun = false;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        // Define ROOT_PATH if not already defined (e.g., when running tests standalone)
        if (!defined('ROOT_PATH')) {
            define('ROOT_PATH', dirname(__DIR__));
        }

        // Autoloading should be handled by phpunit bootstrap="vendor/autoload.php"
        // but ensure .env variables for tests are loaded if not by phpunit.xml
        // Note: phpunit.xml <env> variables are automatically set in $_ENV and $_SERVER
        // So, Database class should pick them up.

        // Initialize database connection for tests if not already done
        if (self::$pdo === null) {
            try {
                // Database class uses $_ENV or getenv() which PHPUnit sets from phpunit.xml
                self::$pdo = Database::getInstance()->getConnection();
                // We might want a separate Database::getTestInstance() or configure Database
                // to use test DB credentials when APP_ENV=testing (set in phpunit.xml)
            } catch (\PDOException $e) {
                // Fail fast if test DB connection is not possible
                die("FATAL: Could not connect to test database. Check phpunit.xml settings and ensure MySQL server is running and accessible.\nError: " . $e->getMessage());
            }
        }

        // Basic schema setup (run once per test suite execution)
        // More sophisticated migration/seeding tools would be used in a larger project.
        if (self::$pdo && !self::$migrationsRun) {
            try {
                // Option 1: Execute schema.sql directly
                $sql = file_get_contents(ROOT_PATH . '/schema.sql');
                if ($sql === false) {
                    die("FATAL: Could not read schema.sql for test database setup.");
                }
                // Remove comments and split statements (basic approach)
                $sql = preg_replace('/--.*/m', '', $sql); // Remove -- comments
                $sql = preg_replace('/\/\*.*?\*\//s', '', $sql); // Remove /* */ comments
                $sqlStatements = array_filter(array_map('trim', explode(';', $sql)));

                foreach ($sqlStatements as $statement) {
                    if (!empty($statement)) {
                        self::$pdo->exec($statement);
                    }
                }
                self::$migrationsRun = true;
                // echo "Test database schema initialized.\n";

                // Optionally, add some baseline seed data common to all tests here
                // self::seedBasicData();

            } catch (\PDOException $e) {
                die("FATAL: Could not initialize test database schema from schema.sql.\nError: " . $e->getMessage());
            }
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        // Start a transaction before each test
        if (self::$pdo && !self::$pdo->inTransaction()) {
            self::$pdo->beginTransaction();
        }
    }

    protected function tearDown(): void
    {
        // Roll back the transaction after each test to isolate tests
        if (self::$pdo && self::$pdo->inTransaction()) {
            self::$pdo->rollBack();
        }
        parent::tearDown();
    }

    public static function tearDownAfterClass(): void
    {
        // Optional: Clean up (e.g., drop tables or close connection)
        // However, for speed, often the test database is left intact between runs
        // and re-initialized at the start of the next run.
        // self::$pdo = null; // Close connection
        parent::tearDownAfterClass();
    }

    /**
     * Helper to get the PDO instance for tests.
     */
    protected function getPdo(): ?PDO
    {
        return self::$pdo;
    }

    /**
     * Example seeder for very basic, always-needed data.
     * More complex seeding would be per-test or use fixtures.
     */
    protected static function seedBasicData(): void
    {
        if (self::$pdo) {
            // Example: Ensure at least one admin user exists for auth tests
            // This should use the User model if possible, or raw SQL if necessary for bootstrap
            // $userModel = new \App\Models\User();
            // if (!$userModel->findByUsername('testadmin')) {
            //     $userModel->createUser('testadmin', 'password123', \App\Models\User::ROLE_ADMIN);
            // }
        }
    }
}
