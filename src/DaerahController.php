<?php
class DaerahController
{
    public function __construct(private DaerahGateway $gateway)
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
            echo json_encode(["message" => "Daerah tidak ditemukan"]);
            return;
        }
        switch ($method) {
            case "GET":
                echo json_encode($daerah);
                break;
            case "POST":
                $json = file_get_contents("php://input");
                $data = $this->objectToArrayPHP($json);
                $errors = $this->getValidationErrors($data, false);

                if (!empty($errors)) {
                    http_response_code(422);
                    echo json_encode(["errors" => $errors]);
                    break;
                }

                $rows = $this->gateway->update($daerah, $data);
                echo json_encode(["message" => "Daerah $id diperbarui", "rows" => $rows]);
                break;
            case "DELETE":
                $rows = $this->gateway->delete($id);
                echo json_encode(["message" => "Daerah $id terhapus", "rows" => $rows]);
                break;
            case "OPTIONS":
                break;
            default:
                http_response_code(405);
                header("Allow: GET, POST,  DELETE,OPTIONS");
        }
    }
    private function processCollectionRequest(string $method): void
    {
        switch ($method) {
            case "GET":
                echo json_encode($this->gateway->getAll());
                break;
            case "POST":
                $json = file_get_contents("php://input");
                $data = $this->objectToArrayPHP($json);
                $errors = $this->getValidationErrors($data, false);

                if (!empty($errors)) {
                    http_response_code(422);
                    echo json_encode(["errors" => $errors]);
                    break;
                }

                $id = $this->gateway->create($data);
                http_response_code(201);
                echo json_encode(["message" => "Daerah berhasil dibuat", "id" => $id]);
                break;
            case "OPTIONS":
                break;

            default:
                http_response_code(405);
                header("Allow:GET, POST,OPTIONS");
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
    function objectToArrayPHP($param)
    {
        $data = json_decode($param, true);
        return $data;
    }
}
