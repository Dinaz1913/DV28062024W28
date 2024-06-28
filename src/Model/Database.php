<?php

namespace Reelz222z\Cryptoexchange\Model;

use PDO;
use Dotenv\Dotenv;

class Database
{
    private static ?self $instance = null;
    private PDO $pdo;

    private function __construct()
    {
        // Load environment variables
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
        $dotenv->load();

        $dbPath = $_ENV['DB_DATABASE'];

        $this->pdo = new PDO('sqlite:' . $dbPath);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): PDO
    {
        return $this->pdo;
    }
}
