<?php
declare(strict_types=1);

namespace GemMotors\Config;

class Database
{
    private static ?\PDO $instance = null;

    private function __construct()
    {
    }

    public static function getInstance(): \PDO
    {
        if (self::$instance === null) {
            $host = $_ENV['DB_HOST'] ?? 'gem-postgres';
            $port = $_ENV['DB_PORT'] ?? 5432;
            $dbname = $_ENV['DB_NAME'] ?? 'gem_motors';
            $user = $_ENV['DB_USER'] ?? 'gem_user';
            $password = $_ENV['DB_PASSWORD'] ?? 'gem_password';

            $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";

            $options = [
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$instance = new \PDO($dsn, $user, $password, $options);
            } catch (\PDOException $e) {
                throw new \RuntimeException('Error de conexión a la base de datos: ' . $e->getMessage());
            }
        }

        return self::$instance;
    }
}