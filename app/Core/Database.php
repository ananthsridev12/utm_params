<?php

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    public static function connection(): PDO
    {
        if (self::$instance === null) {
            $cfg = Config::get('db');
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                $cfg['host'],
                $cfg['name'],
                $cfg['charset'] ?? 'utf8mb4'
            );
            try {
                self::$instance = new PDO($dsn, $cfg['user'], $cfg['pass'], [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                if (Config::get('app.debug')) {
                    throw $e;
                }
                http_response_code(500);
                exit('Database connection failed. Check config/config.php.');
            }
        }
        return self::$instance;
    }
}
