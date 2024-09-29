<?php

declare(strict_types=1);

namespace App\Repository;

use App\Database;
use PDO;

class ProductRepository
{
    private $database;

    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    public function getAll(): array
    {
        $pdo = $this->database->getConnection();

        $stmt = $pdo->query("SELECT * FROM PRODUCTS");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }

    public function getById(int $id): array | bool
    {
        $sql = "SELECT * FROM PRODUCTS WHERE id = :id";
        $pdo = $this->database->getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(":id", $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create(array $data): string
    {
        $sql = "INSERT INTO PRODUCTS (name, size, is_available)
                 VALUES (:name, :size, :is_available)";

        $pdo = $this->database->getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(":name", $data["name"], PDO::PARAM_STR);
        $stmt->bindValue(":size", $data["size"], PDO::PARAM_INT);
        if (!empty($data["is_available"])) {
            $stmt->bindValue(":is_available", $data["is_available"], PDO::PARAM_BOOL);
        } else {
            $stmt->bindValue(":is_available", 0, PDO::PARAM_INT);
        }
        $stmt->execute();

        return $pdo->lastInsertId();
    }

    public function update(int $id, array $data): int
    {
        $sql = "UPDATE PRODUCTS
                SET name = :name,
                size = :size,
                is_available = :is_available
                WHERE id = :id";

        $pdo = $this->database->getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(":name", $data["name"], PDO::PARAM_STR);
        $stmt->bindValue(":size", $data["size"], PDO::PARAM_INT);
        if (!empty($data["is_available"])) {
            $stmt->bindValue(":is_available", $data["is_available"], PDO::PARAM_BOOL);
        } else {
            $stmt->bindValue(":is_available", 0, PDO::PARAM_INT);
        }
        $stmt->bindValue(":id", $id);
        $stmt->execute();

        return $stmt->rowCount();
    }

    public function delete(int $id): int
    {
        $sql = "DELETE FROM PRODUCTS
                WHERE id = :id";

        $pdo = $this->database->getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(":id", $id, PDO::PARAM_INT);

        $stmt->execute();

        return $stmt->rowCount();
    }
}
