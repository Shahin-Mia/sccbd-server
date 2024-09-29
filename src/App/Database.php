<?php

declare(strict_types=1);

namespace App;

use PDO;

class Database
{

    private $host;
    private $dbname;
    private $userName;
    private $password;

    public function __construct(string $host, string $dbname, string $userName, string $password)
    {
        $this->host = $host;
        $this->dbname = $dbname;
        $this->userName = $userName;
        $this->password = $password;
    }

    public function getConnection(): PDO
    {
        $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset=utf8";

        $pdo = new PDO($dsn, $this->userName, $this->password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        return $pdo;
    }
}
