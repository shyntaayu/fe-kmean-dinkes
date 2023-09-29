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
                    $stmt->bindValue(":penyakit_name", $data["penyakit_name"], PDO::PARAM_STR);
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

    public function createMulti(array $data): array
    {
        try {
            // Prepare the database query
            $query = "INSERT INTO daerah_penyakit (tahun,jumlah, penyakit_id, daerah_id) 
            VALUES (:tahun,:jumlah,:penyakit_id, 
            (SELECT daerah_id FROM daerah WHERE daerah_name=:daerah_name)
            );";
            $stmt = $this->conn->prepare($query);

            $jml = 0;
            // Iterate over the rows and insert data into the database
            foreach ($data as $row) {

                // Execute the database insert query if rowData is not empty
                if (!empty($row)) {
                    $jml++;
                    // Execute the database insert query
                    $stmt->bindValue(":tahun", $row["tahun"], PDO::PARAM_STR);
                    $stmt->bindValue(":jumlah", $row["jumlah"], PDO::PARAM_INT);
                    $stmt->bindValue(":penyakit_id", $row["penyakit"], PDO::PARAM_STR);
                    $stmt->bindValue(":daerah_name", $row["daerah_name"], PDO::PARAM_STR);
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

    public function createSingle(array $data): array
    {
        try {
            // Prepare the database query
            $query = "INSERT INTO daerah_penyakit (tahun,jumlah, penyakit_id, daerah_id) VALUES (:tahun,:jumlah,:penyakit_id,:daerah_id);";
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(":tahun", $data["tahun"], PDO::PARAM_INT);
            $stmt->bindValue(":jumlah", $data["jumlah"], PDO::PARAM_INT);
            $stmt->bindValue(":penyakit_id", $data["penyakit"], PDO::PARAM_INT);
            $stmt->bindValue(":daerah_id", $data["daerah_id"], PDO::PARAM_INT);
            $stmt->execute();

            // Close the statement and perform any additional cleanup

            // Return true on success
            return [
                'result' => true,
                'insertedData' => $this->conn->lastInsertId()
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

    public function getDataByParams($daerahNameP, $penyakitNameP, $tahunP, $pilihanP, $barisP, $kolomP)
    {
        try {
            $query = "SELECT dp.*, p.penyakit_name, d.daerah_name 
                      FROM daerah_penyakit dp
                      JOIN penyakit p ON dp.penyakit_id = p.penyakit_id
                      JOIN daerah d ON dp.daerah_id = d.daerah_id";
            if (!empty($daerahNameP)) {
                $query .= " WHERE d.daerah_id = (SELECT daerah_id FROM daerah WHERE daerah_name=:daerah_name)";
            }
            if (!empty($penyakitNameP)) {
                $query .= " WHERE p.penyakit_id = (SELECT penyakit_id FROM penyakit WHERE penyakit_name=:penyakit_name)";
            }
            if (!empty($tahunP)) {
                $query .= " WHERE dp.tahun = :tahun";
            }
            $stmt = $this->conn->prepare($query);

            // Bind the parameter if it exists
            if (!empty($daerahNameP)) {
                $stmt->bindParam(':daerah_name', $daerahNameP, PDO::PARAM_STR);
            }
            if (!empty($penyakitNameP)) {
                $stmt->bindParam(':penyakit_name', $penyakitNameP, PDO::PARAM_STR);
            }
            if (!empty($tahunP)) {
                $stmt->bindParam(':tahun', $tahunP, PDO::PARAM_STR);
            }

            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Perform any additional processing or return the results as needed

            $pilihan = $this->getPilihan($pilihanP);
            $baris = $this->getPilihan($barisP);
            $kolom = $this->getPilihan($kolomP);

            return $this->getPenyakit($results, $pilihan, $baris, $kolom);
        } catch (PDOException $e) {
            // Handle the exception or return an error message
            return [];
        }
    }

    public function getPilihan($nama)
    {
        $hasil = "";
        switch ($nama) {
            case "daerah":
                $hasil = "daerah_name";
                break;
            case "tahun":
                $hasil = "tahun";
                break;
            case "penyakit":
                $hasil = "penyakit_name";
                break;
        }
        return $hasil;
    }


    public function getPenyakit($results, $pilihan, $baris, $kolom)
    {
        // Perform restructuring of data
        // Create an empty result array
        $restructuredData = [];

        // Restructure the data
        foreach ($results as $row) {
            $penyakitName = $row[$pilihan];
            $daerahName = $row[$baris];
            $tahun = $row[$kolom];
            $jumlah = $row['jumlah'];

            // Check if penyakit exists in the result array
            if (!isset($restructuredData[$penyakitName])) {
                $restructuredData[$penyakitName] = [
                    $pilihan => $penyakitName,
                    'list_baris' => []
                ];
            }

            // Check if daerah exists in the list_baris
            $daerahExists = false;
            foreach ($restructuredData[$penyakitName]['list_baris'] as &$daerah) {
                if ($daerah[$baris] === $daerahName) {
                    $daerahExists = true;
                    $daerah['list_kolom'][] = [
                        $kolom => $tahun,
                        'jumlah' => $jumlah
                    ];
                    break;
                }
            }

            // If daerah does not exist, add it to the list_baris
            if (!$daerahExists) {
                $restructuredData[$penyakitName]['list_baris'][] = [
                    $baris => $daerahName,
                    'list_kolom' => [
                        [
                            $kolom => $tahun,
                            'jumlah' => $jumlah
                        ]
                    ]
                ];
            }
        }

        // Convert the associative array to indexed array
        $restructuredData = array_values($restructuredData);

        // Return the restructured data
        return $restructuredData;
    }
}
