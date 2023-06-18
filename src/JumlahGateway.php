<?php
class JumlahGateway
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

    public function get(string $param, $tahun)
    {
        // Prepare the SQL query
        switch ($param) {
            case "daerah":
                $query = "SELECT COUNT(*) AS total_count FROM daerah";
                break;
            case "penyakit":
                $query = "SELECT COUNT(*) AS total_count FROM penyakit";
                break;
            case "data":
                $query = "SELECT COUNT(*) AS total_count FROM daerah_penyakit";
                break;
            case "penduduk":
                $query = "SELECT SUM(jumlah) AS total_count
                    FROM penduduk_daerah
                    WHERE tahun = :tahun
                    GROUP BY tahun";
                break;
            default:
                return false;
        }


        // Execute the query
        $stmt = $this->conn->prepare($query);
        if ($param == "penduduk") {
            $stmt->bindParam(':tahun', $tahun, PDO::PARAM_STR);
        }
        $stmt->execute();

        // Fetch the result
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        // Get the total count
        $totalCount = $result['total_count'];

        // Return the count
        return $totalCount;
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
        $sql = "DELETE FROM daerah WHERE daerah_id = :daerah_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue("daerah_id", $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount();
    }
}
