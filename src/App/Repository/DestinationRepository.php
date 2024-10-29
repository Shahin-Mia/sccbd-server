<?php

declare(strict_types=1);

namespace App\Repository;

use App\Database;
use PDO;

class DestinationRepository
{
    public function __construct(private Database $database) {}

    public function create(array $data): void
    {
        $sql = "INSERT INTO DESTINATIONS (destination_name, destination_thumbnail, destination_images, description, published, created_by)
                VALUES (:destination_name, :destination_thumbnail, :destination_images, :description, :published, :created_by)";

        $pdo = $this->database->getConnection();

        $stmt = $pdo->prepare($sql);

        $stmt->bindValue(":destination_name", $data["destination_name"]);
        $stmt->bindValue(":destination_thumbnail", $data["destination_thumbnail"]);
        $stmt->bindValue(":destination_images", $data["destination_images"]);
        $stmt->bindValue(":description", $data["description"]);
        if ($data["published"] === true) {
            $stmt->bindValue(":published", 1, PDO::PARAM_BOOL);
        } else {
            $stmt->bindValue(":published", 0, PDO::PARAM_BOOL);
        }
        $stmt->bindValue(":created_by", $data["created_by"]);

        $stmt->execute();
    }

    public function find(string $column, $value): array | bool
    {
        $sql = "SELECT *
                FROM DESTINATIONS
                WHERE $column = :value";

        $pdo = $this->database->getConnection();

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(":value", $value);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }


    public function getAll(): array
    {
        $pdo = $this->database->getConnection();

        $stmt = $pdo->query("SELECT * FROM DESTINATIONS");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }

    public function delete(int $id): int
    {
        $sql = "DELETE FROM DESTINATIONS
                WHERE id = :id";

        $pdo = $this->database->getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(":id", $id, PDO::PARAM_INT);

        $stmt->execute();

        return $stmt->rowCount();
    }

    public function getById(int $id): array | bool
    {
        $sql = "SELECT * FROM DESTINATIONS WHERE id = :id";
        $pdo = $this->database->getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(":id", $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update($previousData, $newData): void
    {
        $sql = "UPDATE DESTINATIONS
                SET destination_name = :destination_name,
                 destination_thumbnail = :destination_thumbnail,
                 destination_images = :destination_images,
                 description = :description,
                 published = :published
                WHERE id = :id";

        $pdo = $this->database->getConnection();
        $stmt = $pdo->prepare($sql);

        $stmt->bindValue(
            ":destination_name",
            isset($newData["destination_name"]) ? $newData["destination_name"] : $previousData["destination_name"]
        );
        $stmt->bindValue(
            ":destination_thumbnail",
            isset($newData["destination_thumbnail"]) ? $newData["destination_thumbnail"] : $previousData["destination_thumbnail"]
        );
        $stmt->bindValue(
            ":destination_images",
            isset($newData["destination_images"]) ? $newData["destination_images"] : $previousData["destination_images"]
        );
        $stmt->bindValue(
            ":description",
            isset($newData["description"]) ? $newData["description"] : $previousData["description"]
        );
        if (isset($newData["published"])) {
            if ($newData["published"] === "true") {
                $stmt->bindValue(":published", 1, PDO::PARAM_BOOL);
            } else {
                $stmt->bindValue(":published", 0, PDO::PARAM_BOOL);
            }
        } else {
            $stmt->bindValue(
                ":published",
                $previousData["published"]
            );
        }

        $stmt->bindValue(":id", $previousData["id"]);

        $stmt->execute();
    }
}
