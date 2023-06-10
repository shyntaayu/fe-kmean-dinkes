<?php
class DaerahGateway
{
    private PDO $conn;

    public function __construct(Database $database)
    {
        $this->conn = $database->getConnection();
    }

    public function getAll(): array
    {
        $sql = "SELECT * FROM daerah";
        $stmt = $this->conn->query($sql);
        $data = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $data[] = $row;
        }
        return $data;
    }
    public function create(array $data): string
    {
        $sql = "INSERT INTO daerah (daerah_name) VALUES (:daerah_name)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":daerah_name", $data["daerah_name"], PDO::PARAM_STR);
        $stmt->execute();

        return $this->conn->lastInsertId();
    }

    public function get(string $id): array|false
    {
        $sql = "SELECT * FROM daerah WHERE daerah_id =:id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data;
    }

    public function update(array $current, array $new): int
    {
        $sql = "UPDATE daerah SET daerah_name=:daerah_name 
        WHERE daerah_id=:daerah_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":daerah_name", $new["daerah_name"] ?? $current["daerah_name"], PDO::PARAM_STR);
        $stmt->bindValue(":daerah_id", $current["daerah_id"], PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount();
    }

    public function delete(string $id): int
    {
        $sql = "DELETE FROM daerah WHERE daerah_id = $:daerah_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue("daerah_id", $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount();
    }
}
