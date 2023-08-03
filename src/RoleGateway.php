<?php
class RoleGateway
{
    private PDO $conn;

    public function __construct(Database $database)
    {
        $this->conn = $database->getConnection();
    }

    public function getAll(): array
    {
        $sql = "SELECT * FROM role";
        $stmt = $this->conn->query($sql);
        $data = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $data[] = $row;
        }
        return $data;
    }
    public function create(array $data): string
    {
        $sql = "INSERT INTO role (role_name) VALUES (:role_name)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":role_name", $data["role_name"], PDO::PARAM_STR);
        $stmt->execute();

        return $this->conn->lastInsertId();
    }

    public function get(string $id): array|false
    {
        $sql = "SELECT * FROM role WHERE role_id =:id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data;
    }

    public function update(array $current, array $new): int
    {
        $sql = "UPDATE role SET role_name=:role_name 
        WHERE role_id=:role_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":role_name", $new["role_name"] ?? $current["role_name"], PDO::PARAM_STR);
        $stmt->bindValue(":role_id", $current["role_id"], PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount();
    }

    public function delete(string $id): int
    {
        $sql = "DELETE FROM role WHERE role_id = :role_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue("role_id", $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount();
    }
}
