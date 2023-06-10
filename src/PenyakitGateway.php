<?php
class PenyakitGateway
{
    private PDO $conn;

    public function __construct(Database $database)
    {
        $this->conn = $database->getConnection();
    }

    public function getAll(): array
    {
        $sql = "SELECT * FROM penyakit";
        $stmt = $this->conn->query($sql);
        $data = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $data[] = $row;
        }
        return $data;
    }
    public function create(array $data): string
    {
        $sql = "INSERT INTO penyakit (penyakit_name) VALUES (:penyakit_name)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":penyakit_name", $data["penyakit_name"], PDO::PARAM_STR);
        $stmt->execute();

        return $this->conn->lastInsertId();
    }

    public function get(string $id): array|false
    {
        $sql = "SELECT * FROM penyakit WHERE penyakit_id =:id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data;
    }

    public function update(array $current, array $new): int
    {
        $sql = "UPDATE penyakit SET penyakit_name=:penyakit_name 
        WHERE penyakit_id=:penyakit_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":penyakit_name", $new["penyakit_name"] ?? $current["penyakit_name"], PDO::PARAM_STR);
        $stmt->bindValue(":penyakit_id", $current["penyakit_id"], PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount();
    }

    public function delete(string $id): int
    {
        $sql = "DELETE FROM penyakit WHERE penyakit_id = $:penyakit_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue("penyakit_id", $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount();
    }
}
