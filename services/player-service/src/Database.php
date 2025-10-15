<?php

declare(strict_types=1);

namespace PlayerService;

use PDO;
use PDOException;

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $host = getenv('MYSQL_HOST') ?: 'player-mysql';
        $port = getenv('MYSQL_PORT') ?: '3306';
        $db   = getenv('MYSQL_DATABASE') ?: 'players_db';
        $user = getenv('MYSQL_USER') ?: 'players_app';
        $pass = getenv('MYSQL_PASSWORD') ?: 'players_pass';

        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $db);

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            self::$connection = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'database_connection_failed',
                'message' => $e->getMessage(),
            ]);
            exit;
        }

        self::ensureSchema();

        return self::$connection;
    }

    private static function ensureSchema(): void
    {
        if (!(self::$connection instanceof PDO)) {
            return;
        }

        self::$connection->exec(
            'CREATE TABLE IF NOT EXISTS players (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                team_id INT NOT NULL,
                number INT NULL,
                name VARCHAR(120) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_team (team_id),
                INDEX idx_team_number (team_id, number)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
    }
}
