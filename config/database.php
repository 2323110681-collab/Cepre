<?php

declare(strict_types=1);

final class Database
{
    private string $host = '127.0.0.1';
    private int $port = 3306;
    private string $dbName = 'cepre_universidad';
    private string $username = 'root';
    private string $password = '';
    private ?PDO $conn = null;

    public function getConnection(): PDO
    {
        if ($this->conn instanceof PDO) {
            return $this->conn;
        }

        $this->conn = new PDO(
            "mysql:host={$this->host};port={$this->port};dbname={$this->dbName};charset=utf8mb4",
            $this->username,
            $this->password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );

        return $this->conn;
    }
}

function database(): PDO
{
    static $database;
    $database ??= new Database();

    return $database->getConnection();
}
