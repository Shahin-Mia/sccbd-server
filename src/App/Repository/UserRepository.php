<?php

declare(strict_types=1);

namespace App\Repository;

use App\Database;
use PDO;

class UserRepository
{
    public function __construct(private Database $database) {}

    public function create(array $data): void
    {
        $sql = "INSERT INTO USERS (username, email, password, role, profile_image, api_key, api_key_hash)
                VALUES (:username, :email, :password, :role, :profile_image, :api_key, :api_key_hash)";

        $pdo = $this->database->getConnection();

        $stmt = $pdo->prepare($sql);

        $stmt->bindValue(":username", $data["username"]);
        $stmt->bindValue(":email", $data["email"]);
        $stmt->bindValue(":password", $data["password_hash"]);
        $stmt->bindValue(":role", $data["role"]);
        $stmt->bindValue(":profile_image", $data["profile_image"]);
        $stmt->bindValue(":api_key", $data["api_key"]);
        $stmt->bindValue(":api_key_hash", $data["api_key_hash"]);

        $stmt->execute();
    }

    public function find(string $column, $value): array | bool
    {
        $sql = "SELECT *
                FROM USERS
                WHERE $column = :value";

        $pdo = $this->database->getConnection();

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(":value", $value);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createStudent(array $data): void
    {
        $sql = "INSERT INTO USERS (username, email, password, role, profile_image, phone, api_key, api_key_hash, email_activation_token)
                VALUES (:username, :email, :password, :role, :profile_image, :phone, :api_key, :api_key_hash, :email_activation_token)";

        $pdo = $this->database->getConnection();

        $stmt = $pdo->prepare($sql);

        $stmt->bindValue(":username", $data["username"]);
        $stmt->bindValue(":email", $data["email"]);
        $stmt->bindValue(":password", $data["password_hash"]);
        $stmt->bindValue(":role", $data["role"]);
        $stmt->bindValue(":profile_image", "");
        $stmt->bindValue(":phone", $data["phone"]);
        $stmt->bindValue(":api_key", $data["api_key"]);
        $stmt->bindValue(":api_key_hash", $data["api_key_hash"]);
        $stmt->bindValue(":email_activation_token", $data["email_activation_token"]);

        $stmt->execute();
    }

    public function updateActivationToken(int $id)
    {
        $sql = "UPDATE USERS
                SET email_activation_token = :email_activation_token
                WHERE id = :id";

        $pdo = $this->database->getConnection();

        $stmt = $pdo->prepare($sql);

        $stmt->bindValue(":email_activation_token", null);
        $stmt->bindValue(":id", $id);

        $stmt->execute();
    }

    public function updatePassword(int $id, string $password)
    {
        $sql = "UPDATE USERS
                SET password = :password
                WHERE id = :id";

        $pdo = $this->database->getConnection();

        $stmt = $pdo->prepare($sql);

        $stmt->bindValue(":password", $password);
        $stmt->bindValue(":id", $id);

        $stmt->execute();
    }
}
