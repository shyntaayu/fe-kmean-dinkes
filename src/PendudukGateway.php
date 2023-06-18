<?php

use PhpOffice\PhpSpreadsheet\IOFactory;

class PendudukGateway
{
    private PDO $conn;

    public function __construct(Database $database)
    {
        $this->conn = $database->getConnection();
    }

    public function getAll($pilihan): array
    {
        if ($pilihan == 'tahun') {
            // Build the query to calculate the sum of jumlah, grouped by tahun
            $query = "SELECT tahun, SUM(jumlah) AS total_jumlah
        FROM daerah_penyakit
        GROUP BY tahun";
        } else {
            $query = "SELECT d.daerah_name, SUM(dp.jumlah) AS total_jumlah
            FROM daerah_penyakit dp 
            JOIN daerah d ON dp.daerah_id = d.daerah_id
            GROUP BY dp.daerah_id";
        }

        // Prepare and execute the query
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        // Fetch the results
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Return the data
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
            $query = "INSERT INTO penduduk_daerah (tahun,jumlah, daerah_id) 
            VALUES (:tahun,:jumlah,
            (SELECT daerah_id FROM daerah WHERE daerah_name=:daerah_name)
            );";
            $stmt = $this->conn->prepare($query);

            $jml = 0;
            // Iterate over the rows and insert data into the database
            for ($row = 2; $row <= $highestRow; $row++) { // Assuming the data starts from the second row
                $rowData = [];

                foreach ($columnIndexes as $columnIndex) {
                    $cellValue = $worksheet->getCell($columnIndex . $row)->getValue();
                    // Check if the cell value is not null
                    if (!is_null($cellValue) || !empty($cellValue)) {
                        $rowData[] = $cellValue;
                    }
                }

                // Execute the database insert query if rowData is not empty
                if (!empty($rowData)) {
                    $jml++;
                    // Execute the database insert query
                    $stmt->bindValue(":tahun", $data["tahun"], PDO::PARAM_STR);
                    $stmt->bindValue(":jumlah", $rowData[1], PDO::PARAM_INT);
                    $stmt->bindValue(":daerah_name", $rowData[0], PDO::PARAM_STR);
                    $stmt->execute();
                }
            }

            // Close the statement and perform any additional cleanup

            // Return true on success
            return [
                'result' => true,
                'insertedData' => $jml
            ];
        } catch (Exception $e) {
            // Return false on error
            return [
                'result' => false,
                'insertedData' => 0, 'message' => $e->getMessage()
            ];
        }
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
