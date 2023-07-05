<?php
class TahunGateway
{
    private PDO $conn;

    public function __construct(Database $database)
    {
        $this->conn = $database->getConnection();
    }

    public function getAll(): array
    {
        $sql = "SELECT tahun FROM daerah_penyakit GROUP by tahun";
        $stmt = $this->conn->query($sql);
        $data = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $data[] = $row;
        }
        return $data;
    }
    public function create(array $data): string
    {
        $sql = "INSERT INTO tahun (tahun_name) VALUES (:tahun_name)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":tahun_name", $data["tahun_name"], PDO::PARAM_STR);
        $stmt->execute();

        return $this->conn->lastInsertId();
    }

    public function get(string $id): array|false
    {
        $sql = "SELECT * FROM tahun WHERE tahun_id =:id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data;
    }

    public function update(array $current, array $new): int
    {
        $sql = "UPDATE tahun SET tahun_name=:tahun_name 
        WHERE tahun_id=:tahun_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":tahun_name", $new["tahun_name"] ?? $current["tahun_name"], PDO::PARAM_STR);
        $stmt->bindValue(":tahun_id", $current["tahun_id"], PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount();
    }

    public function delete(string $id): int
    {
        $sql = "DELETE FROM tahun WHERE tahun_id = :tahun_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue("tahun_id", $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount();
    }
}
