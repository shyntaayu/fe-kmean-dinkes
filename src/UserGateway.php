<?php

use PhpOffice\PhpSpreadsheet\IOFactory;

class UserGateway
{
    private PDO $conn;

    public function __construct(Database $database)
    {
        $this->conn = $database->getConnection();
    }

    public function getAll(): array
    {
        $sql = "SELECT dp.user_id, dp.user_name, dp.email, dp.role_id, dp.daerah_id, p.role_name, d.daerah_name 
        FROM user dp
        JOIN role p ON dp.role_id = p.role_id
        JOIN daerah d ON dp.daerah_id = d.daerah_id order by dp.user_id desc";
        $stmt = $this->conn->query($sql);
        $data = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $row["password"] = 12345;
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
            $query = "INSERT INTO user (user_name,password, role_id, daerah_id) 
            VALUES (:user_name,:password,
            (SELECT role_id FROM role WHERE role_name=:role_name), 
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
                    $stmt->bindValue(":user_name", $rowData[1], PDO::PARAM_STR);
                    $stmt->bindValue(":password", password_hash($rowData[2], PASSWORD_BCRYPT), PDO::PARAM_STR);
                    $stmt->bindValue(":role_name", $rowData[3], PDO::PARAM_STR);
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
        $sql = "SELECT * FROM user WHERE user_id =:id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data;
    }

    public function update(array $current, array $new): int
    {
        $sql = "UPDATE user SET user_name=:user_name, email=:email, password=:password, daerah_id=:daerah_id, role_id=:role_id
        WHERE user_id=:user_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":user_name", $new["user_name"] ?? $current["user_name"], PDO::PARAM_STR);
        $stmt->bindValue(":email", $new["email"] ?? $current["email"], PDO::PARAM_STR);
        if (array_key_exists("password", $new))
            $stmt->bindValue(":password", password_hash($new["password"], PASSWORD_BCRYPT) ?? $current["password"], PDO::PARAM_STR);
        else $stmt->bindValue(":password", $current["password"], PDO::PARAM_STR);
        $stmt->bindValue(":daerah_id", $new["daerah_id"] ?? $current["daerah_id"], PDO::PARAM_INT);
        $stmt->bindValue(":role_id", $new["role_id"] ?? $current["role_id"], PDO::PARAM_INT);
        $stmt->bindValue(":user_id", $current["user_id"], PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount();
    }

    public function delete(string $id): int
    {
        $sql = "DELETE FROM user WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue("user_id", $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount();
    }

    public function loginUser($username, $password)
    { {
            $query = "SELECT user_id, user_name, password, role_id FROM user WHERE user_name = :username";
            $stmt = $this->conn->prepare($query);
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                $sql = "SELECT dp.user_id, dp.user_name, dp.email, dp.role_id, dp.daerah_id, p.role_name, d.daerah_name 
                FROM user dp
                LEFT JOIN role p ON dp.role_id = p.role_id
               LEFT JOIN daerah d ON dp.daerah_id = d.daerah_id
                 WHERE dp.user_id = :user_id";
                $stmt = $this->conn->prepare($sql);
                $stmt->bindValue(":user_id", $user["user_id"], PDO::PARAM_INT);
                $stmt->execute();
                $res = $stmt->fetch(PDO::FETCH_ASSOC);
                return [
                    'result' => true,
                    'data' => $res,
                ];
            }

            return [
                'result' => false,
                'data' => [],
            ];
        }
    }

    public function createOne(array $data)
    {
        $query = "INSERT INTO user (user_name,password, role_id, daerah_id) 
            VALUES (:user_name,:password, :role_id, :daerah_id);";
        $stmt = $this->conn->prepare($query);
        // Execute the database insert query
        $stmt->bindValue(":user_name", $data["user_name"], PDO::PARAM_STR);
        $stmt->bindValue(":password", password_hash($data["password"], PASSWORD_BCRYPT), PDO::PARAM_STR);
        $stmt->bindValue(":role_id", $data["role_id"], PDO::PARAM_STR);
        $stmt->bindValue(":daerah_id", $data["daerah_id"], PDO::PARAM_STR);
        $stmt->execute();
        return $this->conn->lastInsertId();
    }
}
