<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    private static ?PDO $instance = null;

    public static function connection(): PDO
    {
        if (self::$instance instanceof PDO) {
            return self::$instance;
        }

        $config = require BASE_PATH . '/config/config.php';
        $db = $config['db'];

        if (
            empty($db['host']) ||
            empty($db['database']) ||
            empty($db['username'])
        ) {
            throw new RuntimeException('Database configuration is incomplete.');
        }

        $dsn = sprintf(
            'pgsql:host=%s;port=%d;dbname=%s;sslmode=%s',
            $db['host'],
            (int) $db['port'],
                       $db['database'],
                       $db['sslmode'] ?: 'require'
        );

        try {
            self::$instance = new PDO(
                $dsn,
                $db['username'],
                (string) $db['password'],
                                      [
                                          PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                                      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                                      PDO::ATTR_EMULATE_PREPARES => false,
                                      ]
            );

            return self::$instance;
        } catch (PDOException $e) {
            throw new RuntimeException('Database connection failed: ' . $e->getMessage(), 0, $e);
        }
    }
}
