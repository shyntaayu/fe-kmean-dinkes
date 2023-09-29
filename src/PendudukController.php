<?php
class PendudukController
{
    public function __construct(private PendudukGateway $gateway)
    {
    }
    public function processRequest(string $method, ?string $id): void
    {
        if ($id) {
            $this->processResourceRequest($method, $id);
        } else {
            $this->processCollectionRequest($method);
        }
    }

    private function processResourceRequest(string $method, string $id): void
    {
        $daerah = $this->gateway->get($id);
        if (!$daerah) {
            http_response_code(404);
            echo json_encode(["message" => "Penduduk tidak ditemukan"]);
            return;
        }
        switch ($method) {
            case "GET":
                echo json_encode($daerah);
                break;
            case "POST":
                $data = $_POST;
                $errors = $this->getValidationErrors($data, false);

                if (!empty($errors)) {
                    http_response_code(422);
                    echo json_encode(["errors" => $errors]);
                    break;
                }

                $rows = $this->gateway->update($daerah, $_POST);
                echo json_encode(["message" => "Penduduk $id diperbarui", "rows" => $rows]);
                break;
            case "DELETE":
                $rows = $this->gateway->delete($id);
                echo json_encode(["message" => "Penduduk $id terhapus", "rows" => $rows]);
                break;
            default:
                http_response_code(405);
                header("Allow: GET, POST,  DELETE");
        }
    }
    private function processCollectionRequest(string $method): void
    {
        switch ($method) {
            case "GET":
                $pilihan = $_GET['pilihan'] ?? null;
                echo json_encode($this->gateway->getAll($pilihan));
                break;
            case "POST":
                // Check if the file was uploaded successfully
                if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                    $filePath = $_FILES['file']['tmp_name'];

                    // Process the Excel file
                    $result = $this->gateway->create($_POST, $filePath);

                    if ($result["result"]) {
                        http_response_code(201); // Created
                        echo json_encode(array('message' => 'Data berhasil dibuat.', 'data' => $result['insertedData']));
                    } else {
                        http_response_code(500); // Internal Server Error
                        echo json_encode(array('message' => 'Kesalahan saat memproses file Excel.', 'error' => $result['message']));
                    }
                } else {
                    http_response_code(400); // Bad Request
                    echo json_encode(array('message' => 'Pengunggahan file gagal.'));
                }

            default:
                http_response_code(405);
                header("Allow:GET, POST");
        }
    }

    private function getValidationErrors(array $data, bool $is_new = true): array
    {
        $errors = [];
        if ($is_new && empty($data["daerah_name"])) {
            $errors[] = "daerah_name diperlukan";
        }

        if (array_key_exists("size", $data)) {
            if (filter_var($data["size"], FILTER_VALIDATE_INT) === false) {
                $errors[] = "daerah_size harus berupa integer";
            }
        }
        return $errors;
    }
}
