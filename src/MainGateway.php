<?php

use PhpOffice\PhpSpreadsheet\IOFactory;

class MainGateway
{
    private PDO $conn;

    public function __construct(Database $database)
    {
        $this->conn = $database->getConnection();
    }

    public function getAll(): array
    {
        $sql = "SELECT * FROM daerah_penyakit";
        $stmt = $this->conn->query($sql);
        $data = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $data[] = $row;
        }
        return $data;
    }
    public function create(array $data, $filePath): array
    {
        try {
            // Load the Excel file
            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();

            // Get the highest row and column indices
            $highestRow = $worksheet->getHighestRow();
            $highestColumn = $worksheet->getHighestColumn();
            $columnIndexes = range('A', $highestColumn);
            // Prepare the database query
            $query = "INSERT INTO daerah_penyakit (tahun,jumlah, penyakit_id, daerah_id) 
            VALUES (:tahun,:jumlah,
            (SELECT penyakit_id FROM penyakit WHERE penyakit_name=:penyakit_name), 
            (SELECT daerah_id FROM daerah WHERE daerah_name=:daerah_name)
            );";
            $stmt = $this->conn->prepare($query);

            // Iterate over the rows and insert data into the database
            for ($row = 2; $row <= $highestRow; $row++) { // Assuming the data starts from the second row
                $rowData = [];

                foreach ($columnIndexes as $columnIndex) {
                    $cellValue = $worksheet->getCell($columnIndex . $row)->getValue();
                    $rowData[] = $cellValue;
                }

                // Execute the database insert query
                $stmt->bindValue(":tahun", $data["tahun"], PDO::PARAM_STR);
                $stmt->bindValue(":jumlah", $rowData[1], PDO::PARAM_INT);
                $stmt->bindValue(":penyakit_name", $data["penyakit_name"], PDO::PARAM_STR);
                $stmt->bindValue(":daerah_name", $rowData[0], PDO::PARAM_STR);
                $stmt->execute();
            }

            // Close the statement and perform any additional cleanup

            // Return true on success
            return [
                'result' => true,
                'insertedData' => $highestRow
            ];
        } catch (Exception $e) {
            // Return false on error
            return [
                'result' => false,
                'insertedData' => 0
            ];
        }
    }

    public function get(string $id): array|false
    {
        $sql = "SELECT * FROM daerah_penyakit WHERE main_id =:id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data;
    }

    public function update(array $current, array $new): int
    {
        $sql = "UPDATE daerah_penyakit SET main_name=:main_name 
        WHERE main_id=:main_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":main_name", $new["main_name"] ?? $current["main_name"], PDO::PARAM_STR);
        $stmt->bindValue(":main_id", $current["main_id"], PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount();
    }

    public function delete(string $id): int
    {
        $sql = "DELETE FROM daerah_penyakit WHERE main_id = $:main_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue("main_id", $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount();
    }
}
