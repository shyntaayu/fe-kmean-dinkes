<?php
class JumlahController
{
    public function __construct(private JumlahGateway $gateway)
    {
    }
    public function processRequest(string $method, ?string $id): void
    {
        if ($id) {
            $this->processResourceRequest($method, $id);
        } else {
            // $this->processCollectionRequest($method);
        }
    }

    private function processResourceRequest(string $method, string $param): void
    {
        $tahun = $_GET['tahun'] ?? null;
        $daerah = $this->gateway->get($param, $tahun);
        if (!$daerah) {
            http_response_code(404);
            echo json_encode(["message" => "Jumlah tidak ditemukan"]);
            return;
        }
        switch ($method) {
            case "GET":
                echo json_encode(["total" => $daerah]);
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
                // echo json_encode(["message" => "Jumlah $id diperbarui", "rows" => $rows]);
                break;
            default:
                http_response_code(405);
                header("Allow: GET, POST");
        }
    }
    private function processCollectionRequest(string $method): void
    {
        switch ($method) {
            case "GET":
                echo json_encode($this->gateway->getAll());
                break;
            case "POST":

                // $data = (array) json_decode($_POST, true);
                $data = $_POST;
                $errors = $this->getValidationErrors($data);

                if (!empty($errors)) {
                    http_response_code(422);
                    echo json_encode(["errors" => $errors]);
                    break;
                }

                $id = $this->gateway->create($_POST);
                http_response_code(201);
                echo json_encode(["message" => "Jumlah berhasil dibuat", "id" => $id]);
                break;

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
